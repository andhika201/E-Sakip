<?php

namespace App\Models\Versi;

use App\Services\Version\VersionScope;
use CodeIgniter\Database\ConnectionInterface;
use RuntimeException;

/**
 * Dasar bersama arsip isi versi (RPJMD & Renstra).
 *
 * Tiga operasi yang dimiliki setiap arsip:
 *
 *   bekukanDariLive()  live  -> arsip   (kondisi sekarang jadi titik awal)
 *   salinDariVersi()   arsip -> arsip   (DEEP COPY, §10)
 *   terapkanKeLive()   arsip -> live    (upsert + PENSIUN, tidak pernah delete)
 *
 * =====================================================================
 * MENGAPA ARSIP MENYIMPAN TEKS, BUKAN SEKADAR ID
 *
 * Di basis produksi 111 dari 214 baris `lakip` sudah yatim karena sumbernya
 * terhapus. Arsip yang isinya cuma penunjuk id akan ikut kosong begitu
 * sumbernya hilang — dan arsip yang bisa ikut hilang bukan arsip.
 *
 * Karena itu tabel `*_versi_*` sengaja TANPA foreign key ke tabel live.
 * `source_*_id` hanya penunjuk penelusuran; yang mengikat adalah salinan
 * teksnya. Satuan pun diterjemahkan saat dibekukan (`satuan_nama`), sebab
 * baris master satuan bisa berubah nama atau dihapus.
 * =====================================================================
 *
 * =====================================================================
 * MENGAPA PENERAPAN TIDAK PERNAH MENGHAPUS
 *
 * `renstra_tujuan.rpjmd_sasaran_id` ber-ON DELETE CASCADE. Menghapus satu
 * `rpjmd_sasaran` merambat ke Renstra 38 OPD, lalu ke Renaksi (`target_rencana`)
 * dan analisis LAKIP, dan mem-NULL-kan `lakip`.
 *
 * Maka baris live yang tidak lagi tercantum di arsip DIPENSIUNKAN
 * (`dihentikan_pada` diisi), bukan dihapus — pola yang sama dengan
 * IkuRevisiModel::pensiunkanYangHilang(). Id live tetap stabil, sehingga
 * Cascading, RKT, Renaksi, MONEV, dan Benchmark tidak kehilangan pegangan.
 * =====================================================================
 */
abstract class ArsipVersiModel
{
    public const UBAH_TETAP      = 'tetap';
    public const UBAH_REVISI     = 'revisi';
    public const UBAH_PENGGANTI  = 'pengganti';
    public const UBAH_BARU       = 'baru';
    public const UBAH_DIHENTIKAN = 'dihentikan';

    protected ConnectionInterface $db;

    private ?bool $siap = null;

    public function __construct(?ConnectionInterface $db = null)
    {
        $this->db = $db ?? db_connect();
    }

    /** Modul yang dilayani arsip ini — dicocokkan dengan dokumen_versi.modul. */
    abstract public function modul(): string;

    /** Seluruh tabel arsip milik modul ini, dari akar ke daun. */
    abstract public function tabelArsip(): array;

    /**
     * Salin kondisi tabel live satu lingkup menjadi isi arsip versi.
     *
     * @return array<string,int> jumlah baris per entitas
     */
    abstract public function bekukanDariLive(int $versiId, VersionScope $scope): array;

    /**
     * DEEP COPY isi satu versi ke versi lain (§10).
     *
     * Seluruh hierarki mendapat ID BARU; menyunting hasil salinan tidak boleh
     * menyentuh versi asalnya sedikit pun. `copied_from_id` menyimpan lineage
     * agar compare (§23) tidak perlu mencocokkan nama.
     *
     * @return array<string,int> jumlah baris per entitas
     */
    abstract public function salinDariVersi(int $dariVersiId, int $keVersiId): array;

    /** Pohon isi arsip, siap ditampilkan/dibandingkan. */
    abstract public function isi(int $versiId): array;

    /**
     * Terapkan isi arsip ke tabel live: upsert + pensiun.
     *
     * @return array<string,int> ringkasan: dibuat, diperbarui, dipensiunkan
     */
    abstract public function terapkanKeLive(int $versiId, VersionScope $scope, int $berlakuMulaiTahun): array;

    /** Jumlah entitas per tingkat — untuk ringkasan & pratinjau. */
    abstract public function ringkas(int $versiId): array;

    /**
     * Jumlah baris LIVE yang masih aktif dalam sebuah lingkup.
     *
     * Dipakai untuk memberi tahu operator, SEBELUM ia menetapkan versi kosong,
     * berapa banyak baris yang akan dipensiunkan. Angka yang konkret jauh lebih
     * berarti daripada peringatan umum.
     *
     * @return array<string,int>
     */
    abstract public function hitungLiveAktif(VersionScope $scope): array;

    /**
     * Baris LIVE pada periode ini yang TIDAK ada di arsip versi $versiId,
     * beserta alasannya — bahan panel "kenapa versi ini lebih sedikit dari
     * yang tampil di menu dokumennya".
     *
     * Bawaan kosong: modul yang belum menyediakannya tidak menampilkan panel.
     *
     * @return array<int,array{tingkat:string,id:int,teks:string,induk:string,misi:string,
     *                         alasan:string,dihentikan_pada:?string,berlaku_sampai:?int}>
     */
    public function barisLiveTakTerbekukan(int $versiId, VersionScope $scope): array
    {
        return [];
    }

    /**
     * Peta tabel & kolom arsip per tingkat.
     *
     * Dipakai `simpanSuntingan()` supaya satu penyunting melayani RPJMD maupun
     * Renstra — yang berbeda hanya nama tabel dan nama kolom teksnya, bukan
     * cara kerjanya.
     *
     * Bentuk tiap entri:
     *   ['tabel' => ..., 'teks' => nama kolom teks utama,
     *    'fk' => kolom penunjuk induk (null bila akar),
     *    'induk' => nama tingkat induk (null bila akar),
     *    'extra' => kolom lain yang boleh disunting]
     *
     * @return array<string,array>
     */
    abstract protected function petaKolom(): array;

    /* =========================================================
     * PENYUNTINGAN DRAFT (§9)
     * =======================================================*/

    /**
     * Simpan suntingan isi sebuah DRAFT.
     *
     * =================================================================
     * YANG BOLEH DIUBAH, DAN MENGAPA
     *
     * Seluruh teks, satuan, baseline, target, serta penanda perubahan boleh
     * disunting — itulah gunanya draft. Yang TIDAK boleh disentuh dari sini:
     * `source_*_id` dan `copied_from_id`. Keduanya adalah silsilah; mengubahnya
     * berarti memalsukan asal-usul, dan penerapan ke tabel live akan
     * menyisipkan baris kembar alih-alih memperbarui yang benar.
     *
     * Baris yang DIHAPUS di sini dibuang dari arsip. Itu bukan penghapusan
     * sejarah: yang dibuang hanyalah usulan yang belum pernah resmi. Efeknya
     * pada tabel live baru terasa saat versi ditetapkan — dan di sana baris
     * yang hilang DIPENSIUNKAN, bukan dihapus.
     * =================================================================
     *
     * @param array $data ['tingkat' => [id => [kolom => nilai]]], plus
     *                    'hapus' => ['indikator:12', ...] dan
     *                    'baru'  => ['indikator' => [indukId => [ ... ]]]
     *
     * @return array<string,int> ringkasan: diubah, ditambah, dihapus
     */
    public function simpanSuntingan(int $versiId, array $data): array
    {
        $this->pastikanDalamTransaksi('penyuntingan draft');

        $peta = $this->petaKolom();
        $n    = ['diubah' => 0, 'ditambah' => 0, 'dihapus' => 0, 'target' => 0];
        $now  = $this->sekarang();

        // ---- 1. Perbarui baris yang ada ----------------------------------
        foreach ($peta as $tingkat => $cfg) {
            foreach ((array) ($data[$tingkat] ?? []) as $id => $nilai) {
                $id = (int) $id;

                if ($id <= 0 || ! is_array($nilai)) {
                    continue;
                }

                $set = $this->kolomSuntingan($tingkat, $cfg, $nilai);

                if ($set !== []) {
                    // Pagar lingkup: baris HARUS milik versi yang sedang disunting.
                    // Tanpa ini, id dari form bisa menyentuh arsip versi lain.
                    $terpengaruh = $this->db->table($cfg['tabel'])
                        ->where('id', $id)->where('version_id', $versiId)
                        ->update($set);

                    if ($terpengaruh) {
                        $n['diubah']++;
                    }
                }

                if ($tingkat === 'indikator' && isset($nilai['target'])) {
                    $n['target'] += $this->simpanTargetArsip($versiId, $id, (array) $nilai['target'], $now);
                }
            }
        }

        // ---- 2. Tambah baris baru ----------------------------------------
        // Urutannya dari akar ke daun supaya induk yang baru dibuat sudah punya
        // id ketika anaknya disisipkan.
        foreach ($peta as $tingkat => $cfg) {
            foreach ((array) ($data['baru'][$tingkat] ?? []) as $indukId => $daftar) {
                foreach ((array) $daftar as $nilai) {
                    if (! is_array($nilai) || trim((string) ($nilai['teks'] ?? '')) === '') {
                        continue; // baris kosong dari form dinamis, bukan galat
                    }

                    $baruId = $this->sisipkanBaris($versiId, $tingkat, $cfg, (int) $indukId, $nilai, $now);

                    if ($baruId > 0) {
                        $n['ditambah']++;

                        if ($tingkat === 'indikator' && isset($nilai['target'])) {
                            $n['target'] += $this->simpanTargetArsip($versiId, $baruId, (array) $nilai['target'], $now);
                        }
                    }
                }
            }
        }

        // ---- 3. Hapus ----------------------------------------------------
        foreach ((array) ($data['hapus'] ?? []) as $penanda) {
            [$tingkat, $id] = array_pad(explode(':', (string) $penanda, 2), 2, null);

            if (! isset($peta[$tingkat]) || (int) $id <= 0) {
                continue;
            }

            $terhapus = $this->db->table($peta[$tingkat]['tabel'])
                ->where('id', (int) $id)->where('version_id', $versiId)
                ->delete();

            if ($terhapus) {
                $n['dihapus']++;
            }
        }

        return $n;
    }

    /**
     * Kolom yang boleh ditulis untuk satu tingkat.
     *
     * Daftar putih, bukan daftar hitam: kolom yang tidak disebut di sini tidak
     * bisa ditulis dari form sama sekali — termasuk `version_id`, `source_*_id`,
     * dan `copied_from_id` yang memang tidak boleh berubah.
     */
    private function kolomSuntingan(string $tingkat, array $cfg, array $nilai): array
    {
        $set = [];

        if (array_key_exists('teks', $nilai)) {
            $teks = trim((string) $nilai['teks']);

            // Teks utama tidak boleh dikosongkan: baris tanpa isi tidak bisa
            // dibedakan dari baris yang seharusnya dihapus.
            if ($teks !== '') {
                $set[$cfg['teks']] = $teks;
            }
        }

        foreach ($cfg['extra'] ?? [] as $kolom) {
            if (array_key_exists($kolom, $nilai)) {
                $set[$kolom] = $this->kosongJadiNull($nilai[$kolom]);
            }
        }

        if (array_key_exists('urutan', $nilai)) {
            $set['urutan'] = (int) $nilai['urutan'];
        }

        if (array_key_exists('catatan_perubahan', $nilai)) {
            $set['catatan_perubahan'] = $this->kosongJadiNull($nilai['catatan_perubahan']);
        }

        // Penanda perubahan hanya bermakna pada indikator.
        if ($tingkat === 'indikator') {
            if (array_key_exists('jenis_perubahan', $nilai)) {
                $jenis = $this->jenisPerubahanSah($nilai['jenis_perubahan']);
                $set['jenis_perubahan'] = $jenis;

                $sebelumnya = (int) ($nilai['indikator_sebelumnya_id'] ?? 0);

                // §11 & Case 14: "pengganti" tanpa silsilah ditolak — tanpa itu
                // tidak ada yang bisa menjelaskan indikator mana yang digantikan.
                if ($jenis === self::UBAH_PENGGANTI && $sebelumnya <= 0) {
                    throw new RuntimeException(
                        'Indikator bertanda "pengganti" wajib menyebutkan indikator mana yang digantikan.'
                    );
                }

                $set['indikator_sebelumnya_id'] = $sebelumnya > 0 ? $sebelumnya : null;
            }

            if (array_key_exists('satuan', $nilai)) {
                // Nama satuan ikut dibekukan ulang supaya arsip tetap terbaca
                // walau master satuan berubah kemudian.
                $set['satuan_nama'] = $this->namaSatuan($nilai['satuan']);
            }

            $set['perubahan_substansial'] = ! empty($nilai['perubahan_substansial']) ? 1 : 0;
        }

        return $set;
    }

    /** Sisipkan satu baris arsip baru pada tingkat tertentu. */
    private function sisipkanBaris(
        int $versiId,
        string $tingkat,
        array $cfg,
        int $indukId,
        array $nilai,
        string $now
    ): int {
        $row = ['version_id' => $versiId, 'created_at' => $now];

        if (! empty($cfg['fk'])) {
            if ($indukId <= 0) {
                return 0;
            }

            // Induk harus berada di versi yang sama — pagar lingkup yang sama
            // seperti pada pembaruan.
            $indukTabel = $this->petaKolom()[$cfg['induk']]['tabel'] ?? null;

            if ($indukTabel !== null) {
                $ada = $this->db->table($indukTabel)
                    ->where('id', $indukId)->where('version_id', $versiId)
                    ->countAllResults() > 0;

                if (! $ada) {
                    return 0;
                }
            }

            $row[$cfg['fk']] = $indukId;
        }

        $row[$cfg['teks']] = trim((string) $nilai['teks']);
        $row['urutan']     = (int) ($nilai['urutan'] ?? $this->urutanBerikut($cfg, $versiId, $indukId));

        foreach ($cfg['extra'] ?? [] as $kolom) {
            if (array_key_exists($kolom, $nilai)) {
                $row[$kolom] = $this->kosongJadiNull($nilai[$kolom]);
            }
        }

        if ($tingkat === 'indikator') {
            // Baris yang benar-benar baru lahir bertanda 'baru', bukan 'tetap':
            // ia tidak menggantikan apa pun dan tidak punya pendahulu.
            $jenis = $this->jenisPerubahanSah($nilai['jenis_perubahan'] ?? self::UBAH_BARU);
            $sebelumnya = (int) ($nilai['indikator_sebelumnya_id'] ?? 0);

            if ($jenis === self::UBAH_PENGGANTI && $sebelumnya <= 0) {
                throw new RuntimeException(
                    'Indikator baru bertanda "pengganti" wajib menyebutkan indikator mana yang digantikan.'
                );
            }

            $row['jenis_perubahan']         = $jenis;
            $row['indikator_sebelumnya_id'] = $sebelumnya > 0 ? $sebelumnya : null;
            $row['perubahan_substansial']   = ! empty($nilai['perubahan_substansial']) ? 1 : 0;
            $row['satuan_nama']             = $this->namaSatuan($nilai['satuan'] ?? null);
        }

        return $this->sisip($cfg['tabel'], $this->lengkapiBarisBaru($tingkat, $row, $versiId));
    }

    /**
     * Kesempatan bagi tiap modul mengisi kolom WAJIB yang tidak ada di form.
     *
     * Contohnya `renstra_versi_sasaran.tahun_mulai/tahun_akhir/opd_id` dan
     * `rpjmd_versi_misi.tahun_mulai/tahun_akhir` — semuanya NOT NULL, dan
     * semuanya ditentukan LINGKUP VERSI, bukan oleh pemakai. Mengambilnya dari
     * form akan membuka jalan memindahkan kepemilikan sasaran diam-diam (§2.9).
     */
    protected function lengkapiBarisBaru(string $tingkat, array $row, int $versiId): array
    {
        return $row;
    }

    private function urutanBerikut(array $cfg, int $versiId, int $indukId): int
    {
        $b = $this->db->table($cfg['tabel'])->selectMax('urutan', 'maks')->where('version_id', $versiId);

        if (! empty($cfg['fk']) && $indukId > 0) {
            $b->where($cfg['fk'], $indukId);
        }

        $row = $b->get()->getRowArray();

        return (int) ($row['maks'] ?? -1) + 1;
    }

    /**
     * Simpan target per tahun sebuah indikator arsip.
     *
     * Tahun yang dikosongkan DIHAPUS dari arsip — di sini itu aman, karena yang
     * disentuh hanyalah usulan. Yang tidak pernah dihapus adalah target di
     * tabel LIVE (lihat terapkanTarget).
     */
    private function simpanTargetArsip(int $versiId, int $indikatorArsipId, array $target, string $now): int
    {
        $cfg = $this->petaKolom()['target'] ?? null;

        if ($cfg === null) {
            return 0;
        }

        // Pagar lingkup lewat induknya: tabel target tidak punya version_id.
        $indukTabel = $this->petaKolom()['indikator']['tabel'];

        $milikVersi = $this->db->table($indukTabel)
            ->where('id', $indikatorArsipId)->where('version_id', $versiId)
            ->countAllResults() > 0;

        if (! $milikVersi) {
            return 0;
        }

        $jml = 0;

        foreach ($target as $tahun => $nilai) {
            $tahun = (int) $tahun;

            if ($tahun <= 0) {
                continue;
            }

            $nilai = $this->kosongJadiNull($nilai);

            $ada = $this->db->table($cfg['tabel'])->select('id')
                ->where($cfg['fk'], $indikatorArsipId)->where('tahun', $tahun)
                ->get()->getRowArray();

            if ($nilai === null) {
                if ($ada !== null) {
                    $this->db->table($cfg['tabel'])->where('id', (int) $ada['id'])->delete();
                    $jml++;
                }

                continue;
            }

            if ($ada !== null) {
                $this->db->table($cfg['tabel'])->where('id', (int) $ada['id'])
                    ->update([$cfg['nilai'] => $nilai]);
            } else {
                $this->sisip($cfg['tabel'], [
                    $cfg['fk']    => $indikatorArsipId,
                    'tahun'       => $tahun,
                    $cfg['nilai'] => $nilai,
                    'created_at'  => $now,
                ]);
            }

            $jml++;
        }

        return $jml;
    }

    /* =========================================================
     * UMUM
     * =======================================================*/

    public function siap(): bool
    {
        if ($this->siap !== null) {
            return $this->siap;
        }

        try {
            foreach ($this->tabelArsip() as $t) {
                if (! $this->db->tableExists($t)) {
                    return $this->siap = false;
                }
            }

            return $this->siap = true;
        } catch (\Throwable $e) {
            return $this->siap = false;
        }
    }

    /**
     * Buang seluruh isi arsip sebuah versi.
     *
     * Aman hanya untuk versi DRAFT — memanggilnya pada versi published berarti
     * menghapus sejarah. Penjagaannya di service pemanggil, bukan di sini,
     * supaya migrasi data yang memang perlu menata ulang tetap bisa lewat.
     */
    public function kosongkan(int $versiId): void
    {
        // Cukup hapus akar: seluruh tabel turunan ber-ON DELETE CASCADE ke
        // dokumen_versi ATAU ke induknya sesama tabel arsip.
        foreach ($this->tabelArsip() as $t) {
            if ($this->db->fieldExists('version_id', $t)) {
                $this->db->table($t)->where('version_id', $versiId)->delete();
            }
        }
    }

    /* =========================================================
     * BANTU
     * =======================================================*/

    /**
     * Terjemahkan `satuan` (id numerik atau teks bebas) menjadi namanya.
     *
     * Pola yang sama dengan IkuRevisiModel::namaSatuan() dan SATUAN_SELECT
     * pada IkuModel::getMatrix(), supaya arsip menampilkan satuan yang persis
     * sama dengan yang dilihat operator di layar.
     */
    protected function namaSatuan(?string $satuan): ?string
    {
        $satuan = trim((string) $satuan);

        if ($satuan === '') {
            return null;
        }

        if (! preg_match('/^[0-9]+$/', $satuan)) {
            return $satuan;
        }

        $row = $this->db->table('satuan')->select('satuan')
            ->where('id', (int) $satuan)->get()->getRowArray();

        return $row['satuan'] ?? $satuan;
    }

    protected function jenisPerubahanSah($nilai): string
    {
        $nilai = strtolower(trim((string) $nilai));

        $sah = [
            self::UBAH_TETAP, self::UBAH_REVISI, self::UBAH_PENGGANTI,
            self::UBAH_BARU, self::UBAH_DIHENTIKAN,
        ];

        return in_array($nilai, $sah, true) ? $nilai : self::UBAH_TETAP;
    }

    protected function kosongJadiNull($nilai): ?string
    {
        if ($nilai === null) {
            return null;
        }

        $nilai = trim((string) $nilai);

        return $nilai === '' ? null : $nilai;
    }

    /**
     * Beberapa kolom live bertipe NOT NULL padahal padanannya di arsip boleh
     * NULL (mis. renstra_indikator_sasaran.baseline & .satuan, dan seluruh
     * kolom target). Menulis NULL ke sana akan DIPOTONG SENYAP menjadi ''
     * oleh koneksi aplikasi, karena Config\Database menyetel strictOn=false.
     * Dijadikan '' secara sadar di sini supaya perilakunya sama di mana pun.
     */
    protected function nullJadiKosong($nilai): string
    {
        return $nilai === null ? '' : (string) $nilai;
    }

    protected function sisip(string $tabel, array $data): int
    {
        $this->db->table($tabel)->insert($data);

        return (int) $this->db->insertID();
    }

    /**
     * Menolak berjalan di luar transaksi.
     *
     * Pembekuan, penyalinan, dan penerapan masing-masing menyentuh tujuh tabel.
     * Setengah jadi berarti arsip yang isinya bohong — jauh lebih sulit
     * ditemukan daripada operasi yang gagal terang-terangan.
     */
    protected function pastikanDalamTransaksi(string $operasi): void
    {
        if ($this->db->transDepth < 1) {
            throw new RuntimeException($operasi . ' harus dijalankan di dalam transaksi.');
        }
    }

    /** Ambil semua id dari hasil query. @return int[] */
    protected function kolomId(array $rows, string $kolom = 'id'): array
    {
        return array_map('intval', array_column($rows, $kolom));
    }

    protected function sekarang(): string
    {
        return date('Y-m-d H:i:s');
    }

    /**
     * Alasan pensiun yang seragam, memuat id versi supaya bisa ditelusuri
     * balik ke keputusan yang menyebabkannya.
     */
    protected function alasanPensiun(int $versiId): string
    {
        return 'Tidak lagi tercantum pada versi yang ditetapkan (dokumen_versi id ' . $versiId . ').';
    }

    /* =========================================================
     * BANTU BERSAMA
     * =======================================================*/

    protected function barisArsip(string $tabel, int $versiId): array
    {
        return $this->db->table($tabel)->where('version_id', $versiId)
            ->orderBy('urutan', 'ASC')->orderBy('id', 'ASC')
            ->get()->getResultArray();
    }

    protected function anak(string $tabel, string $fk, int $indukId): array
    {
        $b = $this->db->table($tabel)->where($fk, $indukId);

        if ($this->db->fieldExists('urutan', $tabel)) {
            $b->orderBy('urutan', 'ASC');
        } elseif ($this->db->fieldExists('tahun', $tabel)) {
            $b->orderBy('tahun', 'ASC');
        }

        return $b->orderBy('id', 'ASC')->get()->getResultArray();
    }

    /** @return array<int,array<int,array>> indukId => baris[] */
    protected function petaAnak(string $tabel, string $fk, int $versiId): array
    {
        $out = [];

        foreach ($this->barisArsip($tabel, $versiId) as $r) {
            $out[(int) $r[$fk]][] = $r;
        }

        return $out;
    }

    /** @return array<int,array<int,array>> indikatorId => target[] */
    protected function petaTarget(string $tabel, string $fk, array $indikatorIds): array
    {
        if ($indikatorIds === []) {
            return [];
        }

        $out = [];

        foreach ($this->db->table($tabel)->whereIn($fk, $indikatorIds)
            ->orderBy('tahun', 'ASC')->get()->getResultArray() as $r) {
            $out[(int) $r[$fk]][] = $r;
        }

        return $out;
    }

    /**
     * Upsert satu baris live.
     *
     * `$sourceId` yang masih menunjuk baris hidup -> UPDATE; selain itu INSERT.
     * Baris yang pernah dipensiunkan lalu muncul lagi di versi baru
     * DIHIDUPKAN KEMBALI (dihentikan_pada dikosongkan), bukan diduplikasi —
     * duplikat akan memutus Cascading/Renaksi yang menunjuk id lama.
     */
    protected function upsert(
        string $tabel,
        $sourceId,
        array $data,
        array $tambahanSaatInsert,
        string $now,
        array &$n
    ): int {
        $sourceId = $sourceId === null ? 0 : (int) $sourceId;

        if ($sourceId > 0) {
            $ada = $this->db->table($tabel)->where('id', $sourceId)->countAllResults() > 0;

            if ($ada) {
                $this->db->table($tabel)->where('id', $sourceId)->update(array_merge($data, [
                    'dihentikan_pada'   => null,
                    'berlaku_sampai'    => null,
                    'alasan_dihentikan' => null,
                    'updated_at'        => $now,
                ]));
                $n['diperbarui']++;

                return $sourceId;
            }
        }

        $baru = $this->sisip($tabel, array_merge($tambahanSaatInsert, $data, ['updated_at' => $now]));
        $n['dibuat']++;

        return $baru;
    }

    /**
     * Tulis balik id live ke arsip.
     *
     * Dilakukan DI DALAM transaksi penerbitan, sebelum arsip "disegel" — yang
     * berubah hanya penunjuk penelusuran, bukan isi. Tanpa ini, penerapan
     * berikutnya akan menyisipkan baris kembar alih-alih memperbarui.
     */
    protected function tautkanArsip(string $tabelArsip, int $arsipId, string $kolom, int $liveId): void
    {
        $this->db->table($tabelArsip)->where('id', $arsipId)->update([$kolom => $liveId]);
    }

    /** Terjemahkan lineage arsip -> lineage live. */
    protected function tautkanLineage(string $tabelLive, string $tabelArsip, int $liveId, array $arsip): void
    {
        $sebelumnyaArsip = $arsip['indikator_sebelumnya_id'] ?? null;

        if (empty($sebelumnyaArsip)) {
            return;
        }

        $row = $this->db->table($tabelArsip)->select('source_indikator_id')
            ->where('id', (int) $sebelumnyaArsip)->get()->getRowArray();

        if ($row !== null && ! empty($row['source_indikator_id'])) {
            $this->db->table($tabelLive)->where('id', $liveId)
                ->update(['indikator_sebelumnya_id' => (int) $row['source_indikator_id']]);
        }
    }

    /**
     * Terapkan target: upsert per (indikator, tahun).
     *
     * TARGET TIDAK PERNAH DIHAPUS. `rpjmd_target` dirujuk `lakip` (SET NULL),
     * `target_rencana` (SET NULL), dan `lakip_analisis_faktor` (CASCADE) —
     * menghapus satu baris target menghapus narasi LAKIP dan memutus Renaksi.
     *
     * Target live yang tidak lagi tercantum di arsip DIBIARKAN dan DIHITUNG,
     * supaya operator melihat angkanya alih-alih kehilangan data diam-diam.
     */
    protected function terapkanTarget(
        string $tabelLive,
        string $fkLive,
        string $kolomNilaiLive,
        int $indikatorLiveId,
        array $targetArsip,
        string $kolomNilaiArsip,
        string $now,
        array &$n
    ): void {
        $tahunArsip = [];

        foreach ($targetArsip as $tg) {
            $tahun = (int) $tg['tahun'];
            $nilai = $this->nullJadiKosong($tg[$kolomNilaiArsip] ?? null);
            $tahunArsip[] = $tahun;

            $ada = $this->db->table($tabelLive)->select('id')
                ->where($fkLive, $indikatorLiveId)->where('tahun', $tahun)
                ->get()->getRowArray();

            if ($ada !== null) {
                $this->db->table($tabelLive)->where('id', (int) $ada['id'])
                    ->update([$kolomNilaiLive => $nilai, 'updated_at' => $now]);
            } else {
                $this->sisip($tabelLive, [
                    $fkLive          => $indikatorLiveId,
                    'tahun'          => $tahun,
                    $kolomNilaiLive  => $nilai,
                    'created_at'     => $now,
                    'updated_at'     => $now,
                ]);
            }
        }

        $b = $this->db->table($tabelLive)->where($fkLive, $indikatorLiveId);

        if ($tahunArsip !== []) {
            $b->whereNotIn('tahun', $tahunArsip);
        }

        $n['target_live_tak_tercantum'] += (int) $b->countAllResults();
    }

    /**
     * Id yang hilang: turunan dari induk yang masih hidup tetapi tidak dipakai,
     * ditambah SELURUH turunan dari induk yang dipensiunkan.
     *
     * @return int[]
     */
    protected function hilangDiBawah(
        string $tabel,
        string $fk,
        array $indukHidup,
        array $indukHilang,
        array $dipakai
    ): array {
        $hilang = [];

        $indukMasihHidup = array_values(array_diff($indukHidup, $indukHilang));

        if ($indukMasihHidup !== []) {
            $semua = $this->kolomId($this->db->table($tabel)->select('id')
                ->whereIn($fk, $indukMasihHidup)
                ->where('dihentikan_pada IS NULL', null, false)
                ->get()->getResultArray());

            $hilang = array_values(array_diff($semua, array_map('intval', $dipakai)));
        }

        if ($indukHilang !== []) {
            $ikut = $this->kolomId($this->db->table($tabel)->select('id')
                ->whereIn($fk, $indukHilang)
                ->where('dihentikan_pada IS NULL', null, false)
                ->get()->getResultArray());

            $hilang = array_values(array_unique(array_merge($hilang, $ikut)));
        }

        return $hilang;
    }

    protected function pensiunkan(
        string $tabel,
        array $ids,
        int $berlakuSampai,
        string $alasan,
        string $now,
        bool $tandaiJenisPerubahan = false
    ): int {
        if ($ids === []) {
            return 0;
        }

        $data = [
            'dihentikan_pada'   => $now,
            'berlaku_sampai'    => $berlakuSampai,
            'alasan_dihentikan' => $alasan,
            'updated_at'        => $now,
        ];

        if ($tandaiJenisPerubahan) {
            $data['jenis_perubahan'] = self::UBAH_DIHENTIKAN;
        }

        $this->db->table($tabel)->whereIn('id', $ids)->update($data);

        return count($ids);
    }
}
