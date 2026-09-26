<?php

namespace App\Models;

use CodeIgniter\Model;
use RuntimeException;

/**
 * Pengesahan LAKIP per tahun + permintaan pembukaan bila ada typo.
 *
 * =====================================================================
 * MENGAPA TANPA VERSI/SNAPSHOT
 *
 * LAKIP di sini bukan dokumen berjenjang seperti IKU — ia laporan tahunan
 * yang angkanya tunggal. Yang dibutuhkan bukan salinan beku, melainkan
 * KEADAAN: sudah disahkan atau belum, dan bila perlu diperbaiki, siapa yang
 * meminta serta siapa yang mengizinkan.
 *
 * Karena itu tidak ada tabel salinan. `lakip` tetap satu-satunya tempat
 * angka. Yang ditambahkan hanya kunci dan riwayat izin membukanya.
 *
 * =====================================================================
 * ALURNYA
 *
 *   OPD  : sahkan()          -> tahun terkunci
 *   OPD  : ajukanPembukaan() -> permintaan menunggu (alasan WAJIB)
 *   Kab  : setujui()         -> tahun dibuka, OPD boleh menyunting
 *   Kab  : tolak()           -> tetap terkunci, alasannya tercatat
 *   OPD  : sahkan()          -> mengunci lagi sesudah diperbaiki
 *
 * Riwayat permintaan TIDAK PERNAH dihapus — termasuk yang ditolak. Itulah
 * yang membuat alur ini bisa dipertanggungjawabkan, bukan sekadar tombol
 * buka-kunci.
 */
class LakipPengesahanModel extends Model
{
    protected $table         = 'lakip_pengesahan';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;

    public const STATUS_DISAHKAN = 'disahkan';
    public const STATUS_DIBUKA   = 'dibuka';

    public const MINTA_MENUNGGU  = 'menunggu';
    public const MINTA_DISETUJUI = 'disetujui';
    public const MINTA_DITOLAK   = 'ditolak';
    public const MINTA_DITARIK   = 'ditarik';

    /** Fitur ini mati dengan sendirinya bila migrasinya belum dijalankan. */
    public function siap(): bool
    {
        return $this->db->tableExists('lakip_pengesahan')
            && $this->db->tableExists('lakip_buka_permintaan');
    }

    /**
     * Lingkup kabupaten disimpan sebagai opd_id = 0, mengikuti tabel `lakip`.
     *
     * @return array{mode: string, opd_id: int}
     */
    private function kunciLingkup(string $mode, ?int $opdId): array
    {
        return [
            'mode'   => $mode === 'kabupaten' ? 'kabupaten' : 'opd',
            'opd_id' => $mode === 'kabupaten' ? 0 : (int) $opdId,
        ];
    }

    /** @return array<string,mixed>|null keadaan pengesahan, null bila belum pernah disahkan */
    public function keadaan(int $tahun, string $mode, ?int $opdId): ?array
    {
        if (! $this->siap()) {
            return null;
        }

        $k = $this->kunciLingkup($mode, $opdId);

        return $this->db->table('lakip_pengesahan')
            ->where('tahun', $tahun)
            ->where('mode', $k['mode'])
            ->where('opd_id', $k['opd_id'])
            ->get()->getRowArray();
    }

    /**
     * Inti penjaga: bolehkah angka LAKIP lingkup+tahun ini disunting?
     *
     * Dipanggil SEBELUM setiap tulis di controller. Sengaja mengembalikan
     * "tidak terkunci" saat fitur belum terpasang, supaya basis data lama
     * tidak mendadak membeku.
     */
    public function terkunci(int $tahun, string $mode, ?int $opdId): bool
    {
        $keadaan = $this->keadaan($tahun, $mode, $opdId);

        return $keadaan !== null && $keadaan['status'] === self::STATUS_DISAHKAN;
    }

    /**
     * Sahkan (atau sahkan ulang sesudah perbaikan).
     *
     * Menolak mengesahkan tahun yang belum punya satu pun angka: mengunci
     * halaman kosong hanya menyusahkan, tanpa melindungi apa pun.
     *
     * @return array{id: int, jumlah_realisasi: int, sahkan_ulang: bool}
     */
    public function sahkan(int $tahun, string $mode, ?int $opdId, ?int $userId, array $opsi = []): array
    {
        if (! $this->siap()) {
            throw new RuntimeException('Fitur pengesahan LAKIP belum terpasang di basis data ini.');
        }

        $k = $this->kunciLingkup($mode, $opdId);

        // Kedua lingkup kini divalidasi setara. Dahulu OPD hanya dihitung
        // jumlah baris yang realisasinya terisi, sehingga laporan yang belum
        // lengkap pun bisa dikunci — lihat catatan panjang di validasiOpd().
        $verifikasi = $k['mode'] === 'kabupaten'
            ? $this->validasiKabupaten($tahun)
            : $this->validasiOpd($tahun, (int) $k['opd_id']);
        $isi = (int) $verifikasi['jumlah_realisasi'];

        if ($isi < 1) {
            throw new RuntimeException('Belum ada satu pun realisasi yang terisi untuk tahun ' . $tahun . '.');
        }

        $sekarang = date('Y-m-d H:i:s');
        $ada      = $this->keadaan($tahun, $mode, $opdId);

        $data = [
            'status'        => self::STATUS_DISAHKAN,
            'nomor'         => $opsi['nomor']   ?? ($ada['nomor']   ?? null),
            'catatan'       => $opsi['catatan'] ?? ($ada['catatan'] ?? null),
            'disahkan_oleh' => $userId,
            'disahkan_pada' => $sekarang,
            // Jejak pembukaan sebelumnya dibersihkan: kunci baru, keadaan baru.
            'dibuka_oleh'   => null,
            'dibuka_pada'   => null,
        ];

        // Kolom ini hadir bersama document-level binding. Guard menjaga
        // instalasi legacy yang migration-nya belum dijalankan.
        foreach (['source_type', 'source_version_id', 'lakip_dokumen_id'] as $kolom) {
            if ($this->db->fieldExists($kolom, 'lakip_pengesahan')) {
                $data[$kolom] = $verifikasi[$kolom] ?? null;
            }
        }

        $this->db->transStart();
        if ($ada) {
            $this->db->table('lakip_pengesahan')->where('id', $ada['id'])->update($data);
            $id = (int) $ada['id'];
        } else {
            $this->db->table('lakip_pengesahan')->insert($data + [
                'tahun'  => $tahun,
                'mode'   => $k['mode'],
                'opd_id' => $k['opd_id'],
            ]);
            $id = (int) $this->db->insertID();
        }

        $this->db->transComplete();
        if (! $this->db->transStatus()) {
            throw new RuntimeException('Pengesahan gagal disimpan; perubahan dibatalkan.');
        }

        return ['id' => $id, 'jumlah_realisasi' => $isi, 'sahkan_ulang' => $ada !== null];
    }

    /**
     * Validasi formal untuk LAKIP OPD.
     *
     * =====================================================================
     * MENGAPA ADA
     *
     * Sebelum ini jalur OPD hanya menuntut SATU hal: minimal satu realisasi
     * terisi. Jalur Kabupaten menuntut lima. Akibat ketimpangan itu, LAKIP
     * OPD bisa — dan nyatanya sudah — disahkan dalam keadaan:
     *
     *   * tidak satu pun indikatornya berstatus selesai
     *     (Dinas Koperasi 2025: 4 baris, semuanya masih draft);
     *   * hanya sebagian indikator yang dilaporkan
     *     (Kecamatan Adiluwih 2025: 1 baris dari 5 indikator);
     *   * bersumber CAMPURAN dari dua revisi IKU sekaligus
     *     (Dinas Ketahanan Pangan & Dinas Perhubungan 2025 — keduanya sudah
     *     terkunci dalam keadaan itu).
     *
     * Pengesahan adalah penguncian: sesudahnya perbaikan hanya lewat
     * Permintaan Perbaikan. Mengunci laporan yang belum lengkap justru
     * mempersulit yang seharusnya dilindungi.
     *
     * Pemeriksaannya disamakan dengan validasiKabupaten(), dengan satu
     * perbedaan yang perlu: OPD TIDAK punya ikatan `lakip_dokumen` (tabel itu
     * hanya diisi untuk mode kabupaten), jadi sumber acuannya tidak bisa
     * ditanyakan ke ikatan. Yang dipakai adalah sumber yang tercatat pada
     * barisnya sendiri, dan ketunggalannya-lah yang dijaga.
     * =====================================================================
     *
     * @return array{jumlah_realisasi: int, source_type: ?string,
     *               source_version_id: ?int, lakip_dokumen_id: ?int}
     */
    private function validasiOpd(int $tahun, int $opdId): array
    {
        $kolom = 'id, source_type, source_version_id, source_entity_id, renstra_target_id, '
               . 'status, capaian_tahun_ini';
        if ($this->db->fieldExists('lakip_dokumen_id', 'lakip')) {
            $kolom .= ', lakip_dokumen_id';
        }

        $rows = $this->db->table('lakip')
            ->select($kolom)
            ->where('tahun', $tahun)->where('mode', 'opd')->where('opd_id', $opdId)
            ->get()->getResultArray();

        if ($rows === []) {
            throw new RuntimeException(
                'LAKIP tahun ' . $tahun . ' belum memiliki baris untuk disahkan. '
                . 'Isi dulu capaian indikatornya.'
            );
        }

        $sumber    = [];
        $entitas   = [];
        $dokumenId = null;
        $belumSiap = 0;
        $tanpaIsi  = 0;

        foreach ($rows as $row) {
            $tipe = strtolower(trim((string) ($row['source_type'] ?? '')));

            if (! in_array($tipe, ['iku', 'renstra', 'rpjmd'], true)) {
                throw new RuntimeException(
                    'Setiap baris LAKIP harus memiliki sumber yang sah sebelum pengesahan. '
                    . 'Ada baris tanpa sumber — hubungi admin kabupaten untuk memulangkan lingkupnya.'
                );
            }

            if (! in_array(strtolower(trim((string) ($row['status'] ?? ''))), ['selesai', 'siap'], true)) {
                $belumSiap++;
            }

            if (($row['capaian_tahun_ini'] ?? null) === null
                || trim((string) $row['capaian_tahun_ini']) === '') {
                $tanpaIsi++;
            }

            $kunci = $tipe . ':' . (int) ($row['source_version_id'] ?? 0);
            $sumber[$kunci] = [
                'source_type'       => $tipe,
                'source_version_id' => (int) ($row['source_version_id'] ?? 0),
            ];

            // Jangkar entitas berbeda per sumber: IKU memakai source_entity_id
            // (id indikator IKU berjalan), Renstra memakai renstra_target_id.
            $jangkar = $tipe === 'iku'
                ? (int) ($row['source_entity_id'] ?? 0)
                : (int) ($row['renstra_target_id'] ?? 0);

            if ($jangkar > 0) {
                $entitas[$jangkar] = true;
            }

            if (! empty($row['lakip_dokumen_id'])) {
                $dokumenId ??= (int) $row['lakip_dokumen_id'];
            }
        }

        // URUTANNYA DISENGAJA: sumber campuran dilaporkan LEBIH DULU.
        //
        // Baris sisa dari dokumen lama biasanya juga berstatus draft. Kalau
        // "belum berstatus Selesai" muncul lebih dulu, operator akan menandai
        // selesai baris yang justru seharusnya DIBUANG — lalu baru menabrak
        // pesan sumber campuran. Menyebut masalah strukturalnya lebih dulu
        // menghindarkan satu putaran kerja yang salah arah. Pada Dinas
        // Perhubungan 2025, membuang baris nyasarnya sekaligus menuntaskan
        // hitungan status dan jumlah indikatornya.
        if (count($sumber) !== 1) {
            $daftar = implode(', ', array_map(
                static fn ($v) => strtoupper($v['source_type'])
                    . ($v['source_version_id'] ? ' v' . $v['source_version_id'] : ''),
                $sumber
            ));

            throw new RuntimeException(
                'LAKIP ini bersumber dari lebih dari satu dokumen sekaligus (' . $daftar . '). '
                . 'Satu LAKIP hanya boleh dinilai terhadap SATU dokumen — minta admin kabupaten '
                . 'membersihkan baris sisa dari dokumen lama lebih dulu.'
            );
        }

        if ($tanpaIsi > 0) {
            throw new RuntimeException(
                $tanpaIsi . ' dari ' . count($rows) . ' indikator belum memiliki capaian tahun ini. '
                . 'Seluruhnya wajib terisi sebelum pengesahan.'
            );
        }

        if ($belumSiap > 0) {
            throw new RuntimeException(
                $belumSiap . ' dari ' . count($rows) . ' indikator belum berstatus Selesai. '
                . 'Tandai selesai lebih dulu, lalu sahkan.'
            );
        }

        $source = reset($sumber);

        // Jumlah indikator yang dilaporkan harus sama dengan dokumen acuannya;
        // laporan separuh jalan tidak boleh dikunci.
        if ($source['source_type'] === 'iku' && $source['source_version_id'] > 0) {
            $wajib = $this->db->table('iku_revisi_indikator')
                ->where('revisi_id', $source['source_version_id'])
                ->where('jenis_perubahan !=', 'dihentikan')->countAllResults();

            if ($wajib !== count($entitas)) {
                throw new RuntimeException(
                    'Baru ' . count($entitas) . ' dari ' . $wajib . ' indikator IKU yang dilaporkan. '
                    . 'Lengkapi seluruhnya sebelum pengesahan.'
                );
            }
        } elseif ($source['source_type'] === 'renstra') {
            $wajib = $this->db->table('renstra_target t')
                ->join('renstra_indikator_sasaran i', 'i.id = t.renstra_indikator_id', 'inner')
                ->join('renstra_sasaran s', 's.id = i.renstra_sasaran_id', 'inner')
                ->where('s.opd_id', $opdId)->where('t.tahun', $tahun)
                ->countAllResults();

            if ($wajib > 0 && $wajib !== count($entitas)) {
                throw new RuntimeException(
                    'Baru ' . count($entitas) . ' dari ' . $wajib . ' indikator Renstra yang dilaporkan. '
                    . 'Lengkapi seluruhnya sebelum pengesahan.'
                );
            }
        }

        return $source + [
            'lakip_dokumen_id' => $dokumenId,
            'jumlah_realisasi' => count($rows),
        ];
    }

    /**
     * Ikatan dokumen LAKIP Kabupaten tahun ini, atau null bila belum diikat.
     *
     * Dibaca langsung dari tabel, bukan lewat LakipDokumenModel, supaya model
     * pengesahan tidak menyeret model lain hanya untuk satu baris — dan tetap
     * bekerja pada basis data lama yang belum punya tabelnya.
     *
     * @return array{source_type: string, source_version_id: int}|null
     */
    private function ikatanKabupaten(int $tahun): ?array
    {
        if (! $this->db->tableExists('lakip_dokumen')) {
            return null;
        }

        $row = $this->db->table('lakip_dokumen')
            ->select('source_type, source_version_id')
            ->where('tahun', $tahun)
            ->where('mode', 'kabupaten')
            ->get()->getRowArray();

        if ($row === null) {
            return null;
        }

        $tipe = strtolower(trim((string) ($row['source_type'] ?? '')));

        if (! in_array($tipe, ['iku', 'rpjmd'], true)) {
            return null;
        }

        return [
            'source_type'       => $tipe,
            'source_version_id' => (int) ($row['source_version_id'] ?? 0),
        ];
    }

    /**
     * Validasi formal untuk LAKIP Kabupaten; legacy RPJMD tetap didukung.
     *
     * =====================================================================
     * MENYARING SESUAI IKATAN DOKUMEN, SAMA SEPERTI LAYAR
     *
     * Dahulu baris diambil hanya dengan saringan tahun + mode + opd_id, lalu
     * dituntut seluruhnya berasal dari SATU (source_type, source_version_id).
     * Itu keliru sejak `lakip_dokumen` ada: tabel `lakip` MEMANG menyimpan
     * sisa dari sumber-sumber lama, karena berpindah sumber tidak menghapus
     * baris yang sudah terisi realisasinya.
     *
     * Akibatnya layar dan pengesahan berbeda pendapat. Layar membaca lewat
     * ikatan dokumen (LakipModel::getLakipMapIku menyaring source_version_id),
     * sehingga operator melihat 11 indikator lengkap dan merasa siap; penjaga
     * ini membaca 26 baris dari dua sumber lalu menolak dengan pesan
     * "source type atau source version campuran" — galat yang benar menurut
     * kodenya sendiri, tetapi tidak bisa diperbaiki operator dari layar mana
     * pun, sebab baris sisanya tidak ditampilkan di sana.
     *
     * Kini penyaringnya mengikuti ikatan yang aktif. Bila belum ada ikatan,
     * perilaku lama dipertahankan apa adanya: seluruh baris diperiksa dan
     * sumber campuran tetap ditolak — itu satu-satunya penjaga yang tersisa
     * ketika tidak ada yang menyatakan sumber mana yang sah.
     * =====================================================================
     */
    private function validasiKabupaten(int $tahun): array
    {
        $kolom = 'id, source_type, source_version_id, source_entity_id, status, capaian_tahun_ini';
        if ($this->db->fieldExists('lakip_dokumen_id', 'lakip')) {
            $kolom .= ', lakip_dokumen_id';
        }

        $b = $this->db->table('lakip')
            ->select($kolom)
            ->where('tahun', $tahun)->where('mode', 'kabupaten')->where('opd_id', 0);

        $ikatan = $this->ikatanKabupaten($tahun);

        if ($ikatan !== null) {
            $b->where('source_type', $ikatan['source_type']);

            // RPJMD lama tidak menyimpan nomor versi; jangan menuntutnya ada.
            if ((int) $ikatan['source_version_id'] > 0) {
                $b->where('source_version_id', (int) $ikatan['source_version_id']);
            }
        }

        $rows = $b->get()->getResultArray();

        if ($rows === []) {
            throw new RuntimeException($ikatan === null
                ? 'LAKIP Kabupaten belum memiliki baris untuk disahkan.'
                : 'LAKIP Kabupaten belum memiliki baris yang bersumber dari dokumen yang '
                  . 'sedang diikat (' . strtoupper((string) $ikatan['source_type'])
                  . ($ikatan['source_version_id'] ? ' v' . (int) $ikatan['source_version_id'] : '')
                  . '). Periksa ikatan sumber LAKIP tahun ' . $tahun . '.');
        }

        $sumber = [];
        $entitas = [];
        $dokumenId = null;
        $isi = 0;
        foreach ($rows as $row) {
            $tipe = strtolower(trim((string) ($row['source_type'] ?? '')));
            if (! in_array($tipe, ['iku', 'rpjmd'], true)) {
                throw new RuntimeException('Setiap baris LAKIP Kabupaten harus memiliki sumber yang sah sebelum pengesahan.');
            }
            if (! in_array(strtolower(trim((string) ($row['status'] ?? ''))), ['selesai', 'siap'], true)) {
                throw new RuntimeException('Seluruh indikator LAKIP Kabupaten harus berstatus selesai sebelum pengesahan.');
            }
            if (($row['capaian_tahun_ini'] ?? '') === null || trim((string) $row['capaian_tahun_ini']) === '') {
                throw new RuntimeException('Seluruh indikator wajib memiliki capaian tahun ini sebelum pengesahan.');
            }
            $kunci = $tipe . ':' . (int) ($row['source_version_id'] ?? 0);
            $sumber[$kunci] = ['source_type' => $tipe, 'source_version_id' => (int) ($row['source_version_id'] ?? 0)];
            $entitas[(int) ($row['source_entity_id'] ?? 0)] = true;
            if (! empty($row['lakip_dokumen_id'])) {
                $dokumenId ??= (int) $row['lakip_dokumen_id'];
                if ($dokumenId !== (int) $row['lakip_dokumen_id']) {
                    throw new RuntimeException('LAKIP Kabupaten memiliki document binding campuran.');
                }
            }
            $isi++;
        }

        if (count($sumber) !== 1) {
            throw new RuntimeException('LAKIP Kabupaten memiliki source type atau source version campuran.');
        }
        $source = reset($sumber);

        if ($source['source_type'] === 'iku') {
            if ($source['source_version_id'] <= 0) {
                throw new RuntimeException('LAKIP bersumber IKU wajib memiliki source version sebelum pengesahan.');
            }
            $wajib = $this->db->table('iku_revisi_indikator')
                ->where('revisi_id', $source['source_version_id'])
                ->where('jenis_perubahan !=', 'dihentikan')->countAllResults();
            if ($wajib !== count($entitas)) {
                throw new RuntimeException('Jumlah indikator IKU yang lengkap belum sama dengan sumber acuan.');
            }
        } else {
            $wajib = $this->db->table('rpjmd_target')->where('tahun', $tahun)->countAllResults();
            if ($wajib !== count($entitas)) {
                throw new RuntimeException('Jumlah indikator RPJMD yang lengkap belum sama dengan sumber acuan.');
            }
        }

        return $source + ['lakip_dokumen_id' => $dokumenId, 'jumlah_realisasi' => $isi];
    }

    /** Permintaan pembukaan oleh OPD. Alasan WAJIB — itu inti pertanggungjawabannya. */
    public function ajukanPembukaan(int $tahun, string $mode, ?int $opdId, string $alasan, ?int $userId): int
    {
        $alasan  = trim($alasan);
        $keadaan = $this->keadaan($tahun, $mode, $opdId);

        if ($keadaan === null) {
            throw new RuntimeException('LAKIP tahun ' . $tahun . ' belum disahkan, jadi belum perlu dibuka.');
        }

        if ($keadaan['status'] !== self::STATUS_DISAHKAN) {
            throw new RuntimeException(
                'LAKIP tahun ' . $tahun . ' sedang dalam keadaan terbuka — silakan langsung perbaiki.'
            );
        }

        if ($alasan === '') {
            throw new RuntimeException('Alasan wajib diisi: sebutkan apa yang keliru dan mengapa perlu dibuka.');
        }

        if ($this->permintaanMenunggu((int) $keadaan['id']) !== null) {
            throw new RuntimeException('Sudah ada permintaan yang menunggu keputusan admin kabupaten.');
        }

        $this->db->table('lakip_buka_permintaan')->insert([
            'pengesahan_id' => (int) $keadaan['id'],
            'alasan'        => $alasan,
            'status'        => self::MINTA_MENUNGGU,
            'diminta_oleh'  => $userId,
            'diminta_pada'  => date('Y-m-d H:i:s'),
        ]);

        return (int) $this->db->insertID();
    }

    /** @return array<string,mixed>|null permintaan yang masih menunggu keputusan */
    public function permintaanMenunggu(int $pengesahanId): ?array
    {
        if (! $this->siap()) {
            return null;
        }

        return $this->db->table('lakip_buka_permintaan')
            ->where('pengesahan_id', $pengesahanId)
            ->where('status', self::MINTA_MENUNGGU)
            ->orderBy('id', 'DESC')
            ->get()->getRowArray();
    }

    /** Riwayat lengkap permintaan pada satu lingkup+tahun, terbaru dulu. */
    public function riwayat(int $pengesahanId): array
    {
        if (! $this->siap()) {
            return [];
        }

        return $this->db->table('lakip_buka_permintaan')
            ->where('pengesahan_id', $pengesahanId)
            ->orderBy('id', 'DESC')
            ->get()->getResultArray();
    }

    /** Kotak masuk admin_kab: seluruh permintaan yang menunggu keputusan. */
    public function menungguKeputusan(): array
    {
        if (! $this->siap()) {
            return [];
        }

        return $this->db->table('lakip_buka_permintaan p')
            ->select('p.*, g.tahun, g.mode, g.opd_id, o.nama_opd')
            ->join('lakip_pengesahan g', 'g.id = p.pengesahan_id')
            ->join('opd o', 'o.id = g.opd_id', 'left')
            ->where('p.status', self::MINTA_MENUNGGU)
            ->orderBy('p.diminta_pada', 'ASC')
            ->get()->getResultArray();
    }

    /** admin_kab menyetujui: tahun dibuka, OPD boleh menyunting. */
    public function setujui(int $permintaanId, ?int $userId, ?string $tanggapan = null): array
    {
        $p = $this->wajibMenunggu($permintaanId);

        $this->db->transBegin();

        try {
            $this->db->table('lakip_buka_permintaan')->where('id', $permintaanId)->update([
                'status'          => self::MINTA_DISETUJUI,
                'tanggapan'       => $tanggapan,
                'ditanggapi_oleh' => $userId,
                'ditanggapi_pada' => date('Y-m-d H:i:s'),
            ]);

            $this->db->table('lakip_pengesahan')->where('id', (int) $p['pengesahan_id'])->update([
                'status'      => self::STATUS_DIBUKA,
                'dibuka_oleh' => $userId,
                'dibuka_pada' => date('Y-m-d H:i:s'),
            ]);

            if ($this->db->transStatus() === false) {
                throw new RuntimeException('Transaksi persetujuan gagal.');
            }

            $this->db->transCommit();
        } catch (\Throwable $e) {
            $this->db->transRollback();

            throw $e;
        }

        return $p;
    }

    /** admin_kab menolak: tetap terkunci, alasan penolakan tercatat. */
    public function tolak(int $permintaanId, ?int $userId, ?string $tanggapan = null): array
    {
        $p = $this->wajibMenunggu($permintaanId);

        $this->db->table('lakip_buka_permintaan')->where('id', $permintaanId)->update([
            'status'          => self::MINTA_DITOLAK,
            'tanggapan'       => $tanggapan,
            'ditanggapi_oleh' => $userId,
            'ditanggapi_pada' => date('Y-m-d H:i:s'),
        ]);

        return $p;
    }

    /** OPD menarik permintaannya sendiri selagi belum diputuskan. */
    public function tarik(int $permintaanId): array
    {
        $p = $this->wajibMenunggu($permintaanId);

        $this->db->table('lakip_buka_permintaan')->where('id', $permintaanId)->update([
            'status' => self::MINTA_DITARIK,
        ]);

        return $p;
    }

    /** @return array<string,mixed> permintaan yang dipastikan masih menunggu */
    private function wajibMenunggu(int $permintaanId): array
    {
        $p = $this->db->table('lakip_buka_permintaan')
            ->where('id', $permintaanId)->get()->getRowArray();

        if ($p === null) {
            throw new RuntimeException('Permintaan tidak ditemukan.');
        }

        if ($p['status'] !== self::MINTA_MENUNGGU) {
            throw new RuntimeException(
                'Permintaan ini sudah diputuskan sebelumnya (' . $p['status'] . ').'
            );
        }

        return $p;
    }
}
