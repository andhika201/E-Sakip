<?php

namespace App\Services\Version;

use App\Models\DokumenVersiModel;
use CodeIgniter\Database\ConnectionInterface;
use RuntimeException;

/**
 * Penyusun timeline versi: menghitung `effective_to` dan menerbitkan versi.
 *
 * §3 menegaskan effective_to "dihitung/diperbarui sistem berdasarkan published
 * version berikutnya, bukan bebas diedit user". Kelas ini satu-satunya yang
 * boleh menulis kolom itu.
 *
 * =====================================================================
 * ATURAN URUTAN YANG WAJIB DIPATUHI: LEPAS DULU, BARU KLAIM
 * =====================================================================
 * Basis data menjamin hanya SATU versi "terbuka" (published + effective_to
 * NULL) per lingkup, lewat UNIQUE `uq_dokver_terbuka` pada generated column.
 * Jaminan itu kuat, tetapi MySQL memeriksanya PER-STATEMENT — bukan ditunda
 * sampai COMMIT.
 *
 * Akibatnya urutan UPDATE bukan soal selera:
 *
 *   BENAR : tutup versi terbuka lama  ->  buka versi baru
 *   SALAH : buka versi baru           ->  tutup versi lama   (ERROR 1062)
 *
 * Penyisipan RETROSPEKTIF (§4, §60) tidak menyentuh slot terbuka sama sekali:
 * versi yang disisipkan di tengah histori lahir sudah ber-effective_to, dan
 * versi terakhir tetap yang terbuka. Itulah sebabnya §60 bisa dilayani tanpa
 * mengendurkan satu pun invariant.
 * =====================================================================
 */
class VersionTimelineService
{
    /** Interval setengah terbuka: versi berakhir TEPAT saat penerusnya mulai. */
    private DokumenVersiModel $versi;

    private ConnectionInterface $db;

    private VersionAuditService $audit;

    public function __construct(
        ?ConnectionInterface $db = null,
        ?DokumenVersiModel $versi = null,
        ?VersionAuditService $audit = null
    ) {
        $this->db    = $db ?? db_connect();
        $this->versi = $versi ?? new DokumenVersiModel($this->db);
        $this->audit = $audit ?? new VersionAuditService($this->db);
    }

    /* =========================================================
     * PERHITUNGAN ULANG
     * =======================================================*/

    /**
     * Susun ulang seluruh effective_to dalam satu lingkup.
     *
     * Idempoten: menjalankannya pada timeline yang sudah benar tidak mengubah
     * apa pun dan mengembalikan array kosong.
     *
     * WAJIB dipanggil di dalam transaksi milik pemanggil — ia menyentuh banyak
     * baris dan setengah jadi berarti timeline bohong.
     *
     * @return array<int,array{id:int,version_no:int,dari:?string,ke:?string}> perubahan yang diterapkan
     */
    public function hitungUlang(VersionScope $scope): array
    {
        $this->pastikanDalamTransaksi('perhitungan ulang timeline');

        // FOR UPDATE: dua admin yang menerbitkan bersamaan harus mengantre,
        // bukan sama-sama membaca timeline lama lalu saling menimpa (§53).
        $rows = $this->versi->publishedUrutMaju($scope, true);

        if ($rows === []) {
            return [];
        }

        $this->tolakTanggalKembar($scope, $rows);

        // Target: setiap versi berakhir tepat saat penerusnya mulai; yang
        // terakhir dibiarkan terbuka.
        $target = [];
        $jml    = count($rows);

        foreach ($rows as $i => $r) {
            $target[(int) $r['id']] = $i + 1 < $jml
                ? (string) $rows[$i + 1]['effective_from']
                : null;
        }

        $perubahan = [];
        $sudah     = [];

        // ---- FASE 1: LEPAS slot terbuka ----------------------------------
        // Versi yang kini terbuka tetapi seharusnya tertutup harus ditutup
        // LEBIH DULU, supaya fase 2 bebas mengklaim slot itu.
        foreach ($rows as $r) {
            $id  = (int) $r['id'];
            $kini = $r['effective_to'];

            if ($kini === null && $target[$id] !== null) {
                $this->tulisEffectiveTo($id, $target[$id]);
                $perubahan[] = ['id' => $id, 'version_no' => (int) $r['version_no'], 'dari' => null, 'ke' => $target[$id]];
                $sudah[$id]  = true;
            }
        }

        // ---- FASE 2: sisanya, termasuk yang mengklaim slot terbuka -------
        foreach ($rows as $r) {
            $id = (int) $r['id'];

            if (isset($sudah[$id])) {
                continue;
            }

            $kini = $r['effective_to'] === null ? null : (string) $r['effective_to'];

            if ($kini === $target[$id]) {
                continue;
            }

            $this->tulisEffectiveTo($id, $target[$id]);
            $perubahan[] = ['id' => $id, 'version_no' => (int) $r['version_no'], 'dari' => $kini, 'ke' => $target[$id]];
        }

        return $perubahan;
    }

    /**
     * Terbitkan sebuah versi (draft/pending -> published) lalu susun timeline.
     *
     * Tidak menulis status approval — itu tugas VersionApprovalService. Kelas
     * ini hanya mengurus konsekuensi timeline-nya, supaya alur approval dan
     * aritmetika tanggal tidak saling menyandera.
     *
     * @return array<int,array> perubahan timeline yang tersapu
     */
    public function terbitkan(int $versiId): array
    {
        $this->pastikanDalamTransaksi('penerbitan versi');

        $baris = $this->versi->ambil($versiId);

        if ($baris === null) {
            throw new RuntimeException('Versi tidak ditemukan: ' . $versiId);
        }

        $scope = VersionScope::dariBaris($baris);
        $mulai = (string) $baris['effective_from'];

        $this->tolakBentrokTanggal($scope, $mulai, $versiId);

        // Kunci lingkup sebelum menghitung posisi. Tanpa ini, dua penerbitan
        // bersamaan bisa sama-sama menyimpulkan dirinya yang terakhir (§53).
        $published = $this->versi->publishedUrutMaju($scope, true);

        $penerus = null;

        foreach ($published as $p) {
            if ((int) $p['id'] === $versiId) {
                continue;
            }

            if ((string) $p['effective_from'] > $mulai) {
                $penerus = $p;
                break; // sudah urut maju, yang pertama ketemu adalah penerus terdekat
            }
        }

        if ($penerus === null) {
            // Versi ini akan menjadi yang terbuka. LEPAS DULU slot itu.
            $terbuka = $this->versi->versiTerbuka($scope, true);

            if ($terbuka !== null && (int) $terbuka['id'] !== $versiId) {
                $this->tulisEffectiveTo((int) $terbuka['id'], $mulai);
            }

            $this->tulisEffectiveTo($versiId, null);
        } else {
            // Disisipkan di tengah histori: lahir sudah tertutup, slot terbuka
            // tidak tersentuh sedikit pun.
            $this->tulisEffectiveTo($versiId, (string) $penerus['effective_from']);
        }

        $this->versi->perbarui($versiId, ['status' => DokumenVersiModel::STATUS_PUBLISHED]);

        // Sapuan akhir: membetulkan pendahulu dan sisa apa pun yang belum rapi.
        // Sekaligus jaring pengaman bila data lama sudah tidak konsisten.
        return $this->hitungUlang($scope);
    }

    /**
     * Ubah tanggal mulai berlaku sebuah versi, lalu susun ulang timeline.
     *
     * =====================================================================
     * KAPAN INI BOLEH — DAN MENGAPA BATASNYA DI SITU
     *
     * DRAFT: bebas. Draft belum pernah menjadi rujukan siapa pun, jadi
     * mengubah tanggalnya tidak mengubah sejarah apa pun. Tanpa ini, salah
     * pilih tanggal berarti membatalkan versi dan menyusun ulang isinya dari
     * awal — hukuman yang tidak sebanding dengan kesalahannya.
     *
     * PUBLISHED: hanya untuk BASELINE yang dibuat otomatis migrasi dan
     * arsipnya masih kosong. Alasannya bukan kelonggaran: tanggal baris itu
     * adalah TEBAKAN SISTEM (1 Januari awal periode), bukan keputusan manusia
     * yang pernah ditetapkan. Tidak ada sejarah di sana untuk dilindungi —
     * yang ada justru penghalang: baseline menempati 1 Januari, sehingga versi
     * historis yang benar-benar berlaku sejak awal periode tidak punya tempat.
     *
     * Versi published yang isinya sungguhan TETAP tidak bisa diubah
     * tanggalnya. §20 menegaskan `effective_from` bukan field yang boleh
     * dikoreksi; menggeser timeline dokumen resmi adalah perubahan substantif,
     * dan jalannya adalah versi baru.
     * =====================================================================
     *
     * @return array{dari:string, ke:string, perubahan:array}
     */
    public function ubahTanggalBerlaku(int $versiId, string $tanggalBaru, ?string $alasan = null, bool $bentrokDraftLunak = false): array
    {
        $this->pastikanDalamTransaksi('perubahan tanggal berlaku');

        $baris = $this->versi->ambil($versiId);

        if ($baris === null) {
            throw new RuntimeException('Versi tidak ditemukan.');
        }

        $scope = VersionScope::dariBaris($baris);
        $baru  = $this->normalkanTanggal($tanggalBaru);
        $lama  = (string) $baris['effective_from'];

        if ($baru === $lama) {
            return ['dari' => $lama, 'ke' => $baru, 'perubahan' => []];
        }

        $tahun = (int) date('Y', strtotime($baru));

        if (! $scope->memuatTahun($tahun)) {
            throw new RuntimeException(
                'Tanggal berlaku (' . $baru . ') di luar periode dokumen '
                . $scope->periodeMulai() . '-' . $scope->periodeAkhir() . '.'
            );
        }

        // Versi terkini (ujung timeline) SEBELUM tanggal diubah. Dipakai untuk
        // mendeteksi bila perubahan tanggal ini memindahkan "siapa yang berlaku
        // sekarang" — lihat penerapan ulang di bawah.
        $ujungSebelum = null;
        if ($baris['status'] === DokumenVersiModel::STATUS_PUBLISHED) {
            foreach ($this->versi->publishedUrutMaju($scope) as $p) {
                if (($p['effective_to'] ?? null) === null) {
                    $ujungSebelum = (int) $p['id'];
                }
            }
        }

        // Bentrok diperiksa hanya terhadap versi PUBLISHED lain — draft boleh
        // berbagi tanggal karena belum mengikat apa pun; yang ditolak §6 adalah
        // dua versi RESMI yang mulai bersamaan.
        if ($baris['status'] === DokumenVersiModel::STATUS_PUBLISHED) {
            // Versi resmi tetap ketat: §6 melarang dua versi published mulai
            // pada tanggal yang sama (ditegakkan pula oleh UNIQUE di basis).
            $this->tolakBentrokTanggal($scope, $baru, $versiId);
        } elseif (! $bentrokDraftLunak) {
            foreach ($this->versi->publishedUrutMaju($scope) as $p) {
                if ((string) $p['effective_from'] === $baru) {
                    throw new RuntimeException(
                        'Sudah ada versi yang ditetapkan berlaku mulai ' . $baru
                        . ' (V' . $p['version_no'] . ' — ' . $p['label'] . '). '
                        . 'Pilih tanggal lain, atau perbaiki dulu tanggal versi tersebut.'
                    );
                }
            }
        }
        // $bentrokDraftLunak = true (dipakai RPJMD): tanggal draft yang bentrok
        // BOLEH disimpan — cuma diperingati di halaman versi. Draft belum
        // mengikat apa pun; bentroknya baru benar-benar menghalangi saat
        // penetapan (validasi() + tolakBentrokTanggal tetap menjaga di sana),
        // jadi operator bisa menyimpan dulu lalu membetulkan tanggalnya.

        $this->db->table('dokumen_versi')->where('id', $versiId)->update([
            'effective_from' => $baru,
        ]);

        // Draft tidak ikut dalam timeline, jadi tidak perlu disusun ulang.
        $perubahan = $baris['status'] === DokumenVersiModel::STATUS_PUBLISHED
            ? $this->hitungUlang($scope)
            : [];

        // =====================================================================
        // TERAPKAN ULANG BILA VERSI TERKINI BERPINDAH
        //
        // Versi yang terbit RETROSPEKTIF (di tengah timeline) sengaja TIDAK
        // diterapkan ke tabel berjalan saat penetapan (§60). Tetapi bila
        // tanggalnya kemudian digeser sehingga ia menjadi versi TERKINI (ujung
        // timeline), isinya WAJIB diterapkan — kalau tidak, tabel berjalan
        // tertinggal pada isi versi lama sementara sistem menyatakan versi baru
        // yang berlaku. (Inilah kasus "V5 memuat 5 indikator, halaman hanya 1".)
        // =====================================================================
        if ($baris['status'] === DokumenVersiModel::STATUS_PUBLISHED) {
            $ujungSesudah = null;

            foreach ($this->versi->publishedUrutMaju($scope) as $p) {
                if (($p['effective_to'] ?? null) === null) {
                    $ujungSesudah = $p;
                }
            }

            if ($ujungSesudah !== null && (int) $ujungSesudah['id'] !== $ujungSebelum) {
                $arsip = (new ArsipRegistry())->untuk($scope->modul());

                if ($arsip !== null && $arsip->siap()) {
                    $mulaiUjung = (string) ($ujungSesudah['effective_from'] ?? '');
                    $tahunUjung = $mulaiUjung !== '' && strtotime($mulaiUjung) !== false
                        ? (int) date('Y', strtotime($mulaiUjung))
                        : $scope->periodeMulai();

                    $arsip->terapkanKeLive((int) $ujungSesudah['id'], $scope, $tahunUjung);

                    $this->audit->catat((int) $ujungSesudah['id'], VersionAuditService::AKSI_APPLIED, [
                        'ringkasan' => 'Diterapkan ke data berjalan karena menjadi versi terkini '
                            . 'setelah tanggal berlaku digeser.',
                    ]);
                }
            }
        }

        $this->audit->catat($versiId, VersionAuditService::AKSI_EDITED_DRAFT, [
            'ringkasan'      => 'Tanggal mulai berlaku diubah dari ' . $lama . ' menjadi ' . $baru,
            'sebelum'        => ['effective_from' => $lama],
            'sesudah'        => ['effective_from' => $baru, 'perubahan_timeline' => $perubahan],
            'alasan'         => $alasan,
            'effective_from' => $baru,
        ]);

        return ['dari' => $lama, 'ke' => $baru, 'perubahan' => $perubahan];
    }

    /* =========================================================
     * VALIDASI SEBELUM TERBIT (§19)
     * =======================================================*/

    /**
     * Pratinjau dampak timeline sebelum operator menekan "Tetapkan Berlaku".
     *
     * §19 mewajibkan ini, dan untuk versi retrospektif juga peringatan bahwa
     * LAKIP yang sudah ada TIDAK akan ikut berubah (§2.10).
     */
    public function praTinjau(VersionScope $scope, string $effectiveFrom, ?int $abaikanVersiId = null): array
    {
        $mulai     = $this->normalkanTanggal($effectiveFrom);
        $published = $this->versi->publishedUrutMaju($scope);

        $pendahulu = null;
        $penerus   = null;
        $bentrok   = null;

        foreach ($published as $p) {
            if ($abaikanVersiId !== null && (int) $p['id'] === $abaikanVersiId) {
                continue;
            }

            $pf = (string) $p['effective_from'];

            if ($pf === $mulai) {
                $bentrok = $p;
            } elseif ($pf < $mulai) {
                $pendahulu = $p; // urut maju, yang terakhir lebih kecil = terdekat
            } elseif ($penerus === null) {
                $penerus = $p;
            }
        }

        $retrospektif = $penerus !== null;
        $peringatan   = [];

        if ($bentrok !== null) {
            $peringatan[] = 'Sudah ada versi published yang berlaku mulai tanggal ini (V'
                . $bentrok['version_no'] . ' — ' . $bentrok['label'] . '). '
                . 'Penerbitan akan ditolak. Ubah tanggal berlakunya lebih dulu.';
        }

        if ($retrospektif) {
            $peringatan[] = 'Versi ini DISISIPKAN ke dalam histori, bukan ditambahkan di ujung. '
                . 'LAKIP yang sudah pernah dibuat TIDAK akan berubah otomatis.';
        }

        if ($mulai > date('Y-m-d')) {
            $peringatan[] = 'Tanggal berlaku berada di masa depan. Versi akan berstatus '
                . 'UPCOMING dan belum menjadi rujukan sampai tanggal tersebut tiba.';
        }

        $tahunTerdampak = [];

        if ($bentrok === null) {
            $akhir = $penerus !== null
                ? (int) date('Y', strtotime((string) $penerus['effective_from'] . ' -1 day'))
                : $scope->periodeAkhir();

            for ($t = (int) date('Y', strtotime($mulai)); $t <= $akhir; $t++) {
                if ($scope->memuatTahun($t)) {
                    $tahunTerdampak[] = $t;
                }
            }
        }

        return [
            'effective_from'   => $mulai,
            'pendahulu'        => $pendahulu,
            'penerus'          => $penerus,
            'bentrok'          => $bentrok,
            'retrospektif'     => $retrospektif,
            'akan_terbuka'     => $penerus === null && $bentrok === null,
            'tahun_terdampak'  => $tahunTerdampak,
            'peringatan'       => $peringatan,
            'boleh_terbit'     => $bentrok === null,
        ];
    }

    /**
     * Validasi lengkap sebelum submit/publish (§19).
     *
     * @return array<int,string> daftar galat; kosong = boleh lanjut
     */
    public function validasi(int $versiId): array
    {
        $baris = $this->versi->ambil($versiId);

        if ($baris === null) {
            return ['Versi tidak ditemukan.'];
        }

        $galat = [];
        $scope = VersionScope::dariBaris($baris);
        $mulai = (string) ($baris['effective_from'] ?? '');

        if ($mulai === '' || strtotime($mulai) === false) {
            $galat[] = 'Tanggal mulai berlaku belum diisi.';
        }

        if ((string) ($baris['label'] ?? '') === '') {
            $galat[] = 'Label versi belum diisi.';
        }

        // Tanggal berlaku di luar periode dokumen hampir selalu salah ketik dan
        // menghasilkan versi yang tidak pernah terpilih resolver mana pun.
        if ($mulai !== '' && strtotime($mulai) !== false) {
            $tahunMulai = (int) date('Y', strtotime($mulai));

            if ($tahunMulai < $scope->periodeMulai() || $tahunMulai > $scope->periodeAkhir()) {
                $galat[] = 'Tanggal mulai berlaku (' . $mulai . ') di luar periode dokumen '
                    . $scope->periodeMulai() . '-' . $scope->periodeAkhir() . '.';
            }

            $pra = $this->praTinjau($scope, $mulai, $versiId);

            if (! $pra['boleh_terbit']) {
                $galat[] = 'Bentrok tanggal berlaku dengan V'
                    . $pra['bentrok']['version_no'] . ' (' . $pra['bentrok']['label'] . ').';
            }
        }

        return $galat;
    }

    /* =========================================================
     * INTERNAL
     * =======================================================*/

    private function tulisEffectiveTo(int $versiId, ?string $nilai): void
    {
        // Lewat query builder langsung, bukan model: `effective_to` sengaja
        // TIDAK boleh ditulis dari jalur biasa mana pun.
        $this->db->table('dokumen_versi')
            ->where('id', $versiId)
            ->update(['effective_to' => $nilai]);
    }

    /**
     * Dua versi published dengan effective_from sama = timeline ambigu (§6).
     *
     * Seharusnya sudah ditolak uq_dokver_mulai, tetapi tetap diperiksa di sini
     * supaya basis lama yang belum punya index itu gagal dengan pesan yang
     * menjelaskan, bukan menghasilkan timeline acak.
     */
    private function tolakTanggalKembar(VersionScope $scope, array $rows): void
    {
        $lihat = [];

        foreach ($rows as $r) {
            $f = (string) $r['effective_from'];

            if (isset($lihat[$f])) {
                throw new VersionConflictException($scope, $f, [$lihat[$f], $r]);
            }

            $lihat[$f] = $r;
        }
    }

    private function tolakBentrokTanggal(VersionScope $scope, string $mulai, int $versiId): void
    {
        foreach ($this->versi->publishedUrutMaju($scope) as $p) {
            if ((int) $p['id'] !== $versiId && (string) $p['effective_from'] === $mulai) {
                throw new RuntimeException(
                    'Sudah ada versi published yang berlaku mulai ' . $mulai
                    . ' (V' . $p['version_no'] . ' — ' . $p['label'] . '). '
                    . 'Penerbitan kedua ditolak; ubah tanggal berlakunya lebih dulu.'
                );
            }
        }
    }

    /**
     * Menolak berjalan di luar transaksi.
     *
     * Perhitungan ulang menyentuh banyak baris dan setengah jadi berarti ada
     * dua versi terbuka atau lubang di timeline — keadaan yang jauh lebih sulit
     * ditemukan daripada operasi yang gagal terang-terangan.
     */
    private function pastikanDalamTransaksi(string $operasi): void
    {
        if ($this->db->transDepth < 1) {
            throw new RuntimeException(
                $operasi . ' harus dijalankan di dalam transaksi.'
            );
        }
    }

    private function normalkanTanggal(string $tanggal): string
    {
        $ts = strtotime($tanggal);

        return $ts === false ? $tanggal : date('Y-m-d', $ts);
    }
}
