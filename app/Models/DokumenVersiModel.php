<?php

namespace App\Models;

use App\Models\Concerns\TransaksiAman;
use App\Services\Version\VersionScope;
use CodeIgniter\Database\ConnectionInterface;
use CodeIgniter\Model;
use RuntimeException;

/**
 * Registri versi lintas dokumen (`dokumen_versi`).
 *
 * Model ini SENGAJA TIPIS: ia hanya akses data + penegakan lingkup. Seluruh
 * aturan bisnis (resolusi timeline, approval, deep copy, koreksi) hidup di
 * App\Services\Version\* sesuai §76, supaya satu aturan tidak tersalin ke
 * empat modul.
 *
 * ---------------------------------------------------------------------
 * MENGAPA SATU TABEL UNTUK EMPAT DOKUMEN
 *
 * RPJMD, Renstra, IKU, dan LAKIP punya siklus versi yang identik — hanya ISI
 * arsipnya yang berbeda. Empat tabel kepala berarti empat resolver yang harus
 * dijaga tetap sama, dan §51 melarang tabel duplikat berfungsi sama.
 *
 * Arsip isinya tetap terpisah:
 *   rpjmd   -> rpjmd_versi_*      renstra -> renstra_versi_*
 *   iku     -> iku_revisi_*       lakip   -> lakip_snapshot_*
 * dan `ref_id` menunjuk kepala arsip lama sehingga IkuRevisiModel &
 * LakipSnapshotModel yang sudah ada tetap bekerja tanpa diubah (§42).
 * ---------------------------------------------------------------------
 */
class DokumenVersiModel extends Model
{
    use TransaksiAman;

    protected $table         = 'dokumen_versi';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;

    protected $allowedFields = [
        'modul', 'scope', 'opd_id', 'periode_mulai', 'periode_akhir',
        'version_no', 'label',
        'effective_from', 'effective_to', 'status',
        'copied_from_version_id', 'mulai_dari_kosong',
        'source_type', 'source_version_id', 'source_captured_at',
        'source_override_reason', 'source_override_dasar',
        'alasan_perubahan', 'dasar_perubahan', 'nomor_dasar', 'tanggal_dasar',
        'catatan', 'ref_id',
        'created_by', 'created_at',
        'submitted_by', 'submitted_at',
        'approved_by', 'approved_at',
        'cancelled_by', 'cancelled_at',
        'applied_at',
        // 'tampilan_utama' & kawan-kawannya SENGAJA tidak di sini. perbarui()
        // dipakai jalur yang datanya berasal dari permintaan koreksi; membuka
        // kolom ini berarti membuka jalan menunjuk versi tanpa melewati
        // pemeriksaan status & lingkup di tetapkanTampilanUtama().
    ];

    /** Status lifecycle (§7). Tidak ada 'historical/current/upcoming' — itu dihitung. */
    public const STATUS_DRAFT   = 'draft';
    public const STATUS_PENDING = 'pending_approval';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_CANCELLED = 'cancelled';

    public const SEMUA_STATUS = [
        self::STATUS_DRAFT,
        self::STATUS_PENDING,
        self::STATUS_PUBLISHED,
        self::STATUS_CANCELLED,
    ];

    /** Badge yang dihitung dari timeline, bukan disimpan (§7, §48). */
    public const BADGE_CURRENT    = 'CURRENT';
    public const BADGE_HISTORICAL = 'HISTORICAL';
    public const BADGE_UPCOMING   = 'UPCOMING';

    private ?bool $siap = null;

    /** Kolom tunjukan baru ada setelah migrasi 2026-08-23; dicatat sekali. */
    private ?bool $siapTampilan = null;

    /**
     * @param ConnectionInterface|null $db koneksi khusus (dipakai perintah verify
     *                                     agar bisa menguji di basis salinan
     *                                     tanpa menyentuh basis kerja)
     */
    public function __construct(?ConnectionInterface $db = null)
    {
        parent::__construct($db);
    }

    /**
     * True bila tabel registri sudah terpasang.
     *
     * Dipakai supaya aplikasi tetap hidup di lingkungan yang belum menjalankan
     * db/update_2026-08-20_versioning_dokumen.sql — pola yang sama dipakai
     * IkuRevisiModel::siap() dan LakipSnapshotModel::siap().
     */
    public function siap(): bool
    {
        if ($this->siap !== null) {
            return $this->siap;
        }

        try {
            return $this->siap = $this->db->tableExists('dokumen_versi');
        } catch (\Throwable $e) {
            return $this->siap = false;
        }
    }

    /* =========================================================
     * PEMBACAAN
     * =======================================================*/

    public function ambil(int $versiId): ?array
    {
        if (! $this->siap() || $versiId <= 0) {
            return null;
        }

        return $this->db->table($this->table)->where('id', $versiId)->get()->getRowArray() ?: null;
    }

    /**
     * Ambil versi TAPI hanya bila ia benar milik lingkup yang diminta.
     *
     * Ini penjaga anti-IDOR (§13, §55): controller yang menerima id dari URL
     * tidak boleh mempercayainya. Menebak id versi milik OPD lain akan
     * mengembalikan null, bukan datanya.
     */
    public function ambilDalamLingkup(int $versiId, VersionScope $scope): ?array
    {
        $row = $this->ambil($versiId);

        if ($row === null) {
            return null;
        }

        return VersionScope::dariBaris($row)->sama($scope) ? $row : null;
    }

    /** Seluruh versi dalam satu lingkup, terbaru berlaku di atas. */
    public function daftar(VersionScope $scope, ?array $status = null): array
    {
        if (! $this->siap()) {
            return [];
        }

        $b = $this->db->table($this->table)->where($scope->kondisi());

        if ($status !== null && $status !== []) {
            $b->whereIn('status', $status);
        }

        return $b->orderBy('effective_from', 'DESC')
            ->orderBy('version_no', 'DESC')
            ->get()->getResultArray();
    }

    /**
     * Versi published dalam satu lingkup, URUT MAJU menurut effective_from.
     *
     * Urutan ini adalah tulang punggung perhitungan ulang timeline: pasangan
     * berurutan menentukan effective_to satu sama lain.
     *
     * `id` dipakai sebagai pemecah seri agar urutannya deterministik walau
     * effective_from kembar — keadaan yang seharusnya sudah ditolak
     * uq_dokver_mulai, tapi tetap dijaga supaya perhitungan tidak acak bila
     * basis lama belum punya index itu.
     *
     * @param bool $kunci pasang FOR UPDATE (§53 cegah race publish bersamaan)
     */
    public function publishedUrutMaju(VersionScope $scope, bool $kunci = false): array
    {
        if (! $this->siap()) {
            return [];
        }

        $sql = 'SELECT * FROM `dokumen_versi`
                 WHERE `modul` = ? AND `scope_key` = ? AND `opd_key` = ?
                   AND `periode_mulai` = ? AND `periode_akhir` = ?
                   AND `status` = ?
                 ORDER BY `effective_from` ASC, `id` ASC'
            . ($kunci ? ' FOR UPDATE' : '');

        return $this->db->query($sql, [
            $scope->modul(), $scope->scope(), $scope->opdKey(),
            $scope->periodeMulai(), $scope->periodeAkhir(),
            self::STATUS_PUBLISHED,
        ])->getResultArray();
    }

    /**
     * Versi yang masih "terbuka" (published, effective_to NULL).
     *
     * Basis data menjamin paling banyak ada satu lewat UNIQUE uq_dokver_terbuka
     * pada generated column `terbuka_key`.
     */
    public function versiTerbuka(VersionScope $scope, bool $kunci = false): ?array
    {
        $sql = 'SELECT * FROM `dokumen_versi`
                 WHERE `modul` = ? AND `scope_key` = ? AND `opd_key` = ?
                   AND `periode_mulai` = ? AND `periode_akhir` = ?
                   AND `status` = ? AND `effective_to` IS NULL
                 LIMIT 1'
            . ($kunci ? ' FOR UPDATE' : '');

        if (! $this->siap()) {
            return null;
        }

        return $this->db->query($sql, [
            $scope->modul(), $scope->scope(), $scope->opdKey(),
            $scope->periodeMulai(), $scope->periodeAkhir(),
            self::STATUS_PUBLISHED,
        ])->getRowArray() ?: null;
    }

    /**
     * Kandidat versi yang efektif pada $tanggal.
     *
     * SENGAJA mengembalikan DAFTAR, bukan satu baris: bila hasilnya lebih dari
     * satu, sistem tidak boleh memilih diam-diam (§26, §81). VersionResolver
     * yang memutuskan — dan ia melempar galat administratif.
     *
     * Interval SETENGAH TERBUKA [effective_from, effective_to). §26 menulis
     * "effective_to >= referenceDate", tetapi dengan '>=' tanggal pergantian
     * versi dimiliki DUA versi sekaligus: pada contoh §61, 2026-03-15 akan
     * cocok dengan V1 (yang berakhir di situ) DAN V2 (yang mulai di situ),
     * sehingga resolver melaporkan konflik padahal datanya benar. Dengan '>'
     * hasilnya tepat V2 seperti yang §61 harapkan.
     */
    public function kandidatEfektif(VersionScope $scope, string $tanggal): array
    {
        if (! $this->siap()) {
            return [];
        }

        return $this->db->query(
            'SELECT * FROM `dokumen_versi`
              WHERE `modul` = ? AND `scope_key` = ? AND `opd_key` = ?
                AND `periode_mulai` = ? AND `periode_akhir` = ?
                AND `status` = ?
                AND `effective_from` <= ?
                AND (`effective_to` IS NULL OR `effective_to` > ?)
              ORDER BY `effective_from` DESC, `version_no` DESC',
            [
                $scope->modul(), $scope->scope(), $scope->opdKey(),
                $scope->periodeMulai(), $scope->periodeAkhir(),
                self::STATUS_PUBLISHED,
                $tanggal, $tanggal,
            ]
        )->getResultArray();
    }

    /**
     * Kandidat lintas periode: dipakai LAKIP tahun Y yang belum tahu periode
     * dokumen sumbernya (§28 — RPJMD lama vs RPJMD baru punya periode berbeda).
     */
    public function kandidatEfektifLintasPeriode(
        string $modul,
        string $scopeNama,
        ?int $opdId,
        int $tahun,
        string $tanggal
    ): array {
        if (! $this->siap()) {
            return [];
        }

        return $this->db->query(
            'SELECT * FROM `dokumen_versi`
              WHERE `modul` = ? AND `scope_key` = ? AND `opd_key` = ?
                AND `periode_mulai` <= ? AND `periode_akhir` >= ?
                AND `status` = ?
                AND `effective_from` <= ?
                AND (`effective_to` IS NULL OR `effective_to` > ?)
              ORDER BY `effective_from` DESC, `version_no` DESC',
            [
                $modul, $scopeNama, (int) ($opdId ?? 0),
                $tahun, $tahun,
                self::STATUS_PUBLISHED,
                $tanggal, $tanggal,
            ]
        )->getResultArray();
    }

    /**
     * Seluruh versi PUBLISHED yang periodenya memuat $tahun, lintas periode.
     *
     * Sumber isi dropdown pemilihan versi LAKIP (§27). Draft & pending sengaja
     * tidak ikut: menawarkan pilihan yang pasti ditolak saat disimpan hanya
     * membuang waktu operator (§2.11).
     */
    public function publishedUntukTahun(
        string $modul,
        string $scopeNama,
        ?int $opdId,
        int $tahun
    ): array {
        if (! $this->siap()) {
            return [];
        }

        return $this->db->query(
            'SELECT * FROM `dokumen_versi`
              WHERE `modul` = ? AND `scope_key` = ? AND `opd_key` = ?
                AND `periode_mulai` <= ? AND `periode_akhir` >= ?
                AND `status` = ?
              ORDER BY `effective_from` DESC, `version_no` DESC',
            [$modul, $scopeNama, (int) ($opdId ?? 0), $tahun, $tahun, self::STATUS_PUBLISHED]
        )->getResultArray();
    }

    /** Nomor versi berikutnya dalam satu lingkup. Tidak mengisi lubang bekas cancelled. */
    public function nomorBerikutnya(VersionScope $scope): int
    {
        if (! $this->siap()) {
            return 1;
        }

        $row = $this->db->table($this->table)
            ->selectMax('version_no', 'maks')
            ->where($scope->kondisi())
            ->get()->getRowArray();

        return (int) ($row['maks'] ?? 0) + 1;
    }

    /** Daftar lingkup yang sudah punya versi — untuk halaman daftar dokumen (§48). */
    public function daftarLingkup(string $modul): array
    {
        if (! $this->siap()) {
            return [];
        }

        return $this->db->table($this->table)
            ->select('modul, scope, opd_id, opd_key, periode_mulai, periode_akhir,
                      COUNT(*) AS jml_versi,
                      SUM(status = "published") AS jml_published,
                      SUM(status = "draft") AS jml_draft,
                      SUM(status = "pending_approval") AS jml_pending', false)
            ->where('modul', $modul)
            ->groupBy('modul, scope, opd_id, opd_key, periode_mulai, periode_akhir')
            ->orderBy('periode_mulai', 'DESC')
            ->orderBy('opd_key', 'ASC')
            ->get()->getResultArray();
    }

    /** Semua pengajuan menunggu verifikasi — isi menu Verifikasi (§47). */
    public function menungguVerifikasi(?string $modul = null): array
    {
        if (! $this->siap()) {
            return [];
        }

        $b = $this->db->table($this->table . ' d')
            ->select('d.*, o.nama_opd')
            ->join('opd o', 'o.id = d.opd_id', 'left')
            ->where('d.status', self::STATUS_PENDING);

        if ($modul !== null) {
            $b->where('d.modul', $modul);
        }

        return $b->orderBy('d.submitted_at', 'ASC')->get()->getResultArray();
    }

    /* =========================================================
     * PENULISAN
     * =======================================================*/

    /**
     * Sisipkan baris versi baru. TIDAK menjalankan validasi bisnis —
     * itu tugas service pemanggil.
     */

    /* =========================================================
     * TUNJUKAN "TAMPILAN UTAMA"
     *
     * Ini jawaban KEDUA atas pertanyaan "versi mana yang dipakai". Yang
     * pertama adalah rentang tanggal (`effective_from`/`effective_to`) dan
     * dijawab VersionResolver. Keduanya sengaja dipisah: tanggal berlaku
     * adalah fakta hukum dokumen, sedangkan tunjukan adalah keputusan
     * tampilan. Mengarang tanggal demi mengubah tampilan akan merusak riwayat
     * yang justru menjadi alasan seluruh fitur versi ini ada.
     *
     * Kalau keduanya berbeda, yang berlaku untuk TAMPILAN adalah tunjukan —
     * dan perbedaannya WAJIB ditampilkan ke pengguna, bukan didiamkan.
     * =======================================================*/

    /** Apakah basis ini sudah punya kolom tunjukan (migrasi 2026-08-23). */
    public function siapTampilan(): bool
    {
        if ($this->siapTampilan !== null) {
            return $this->siapTampilan;
        }

        try {
            return $this->siapTampilan = $this->siap()
                && $this->db->fieldExists('tampilan_utama', $this->table);
        } catch (\Throwable $e) {
            return $this->siapTampilan = false;
        }
    }

    /** Versi yang ditunjuk sebagai tampilan utama, bila ada. */
    public function tampilanUtama(VersionScope $scope): ?array
    {
        if (! $this->siapTampilan()) {
            return null;
        }

        $baris = $this->db->table($this->table)
            ->where($scope->kondisi())
            ->where('tampilan_utama', 1)
            ->get()->getRowArray();

        return $baris ?: null;
    }

    /**
     * Pasang tunjukan pada satu versi.
     *
     * URUTANNYA MENGIKAT: melepas dulu, baru memasang. MySQL memeriksa UNIQUE
     * per-pernyataan, jadi memasang lebih dulu akan ditolak
     * `uq_dokver_tampilan` selama tunjukan lama belum lepas. Ini kembaran
     * persis dari aturan urutan pada hitungUlang().
     *
     * Hanya versi PUBLISHED yang boleh ditunjuk: menunjuk draft berarti
     * menampilkan usulan yang belum disetujui siapa pun sebagai Renstra resmi.
     *
     * @throws RuntimeException bila versi tidak sah untuk ditunjuk
     */
    public function tetapkanTampilanUtama(int $versiId, VersionScope $scope, ?int $oleh = null): void
    {
        if (! $this->siapTampilan()) {
            throw new RuntimeException(
                'Fitur tampilan utama belum aktif: jalankan db/update_2026-08-23_tampilan_utama_versi.sql lebih dulu.'
            );
        }

        $versi = $this->ambilDalamLingkup($versiId, $scope);

        if ($versi === null) {
            throw new RuntimeException('Versi tidak ditemukan pada lingkup ini.');
        }

        if ($versi['status'] !== self::STATUS_PUBLISHED) {
            throw new RuntimeException(
                'Hanya versi yang sudah ditetapkan yang bisa dijadikan tampilan utama.'
            );
        }

        // Lepas dulu — lihat catatan urutan di atas.
        $this->lepasTampilanUtama($scope);

        $this->db->table($this->table)->where('id', $versiId)->update([
            'tampilan_utama' => 1,
            'tampilan_oleh'  => $oleh,
            'tampilan_pada'  => date('Y-m-d H:i:s'),
        ]);
    }

    /** Lepas tunjukan pada satu lingkup; tampilan kembali ke kondisi berjalan. */
    public function lepasTampilanUtama(VersionScope $scope): void
    {
        if (! $this->siapTampilan()) {
            return;
        }

        $this->db->table($this->table)
            ->where($scope->kondisi())
            ->where('tampilan_utama', 1)
            ->update(['tampilan_utama' => 0, 'tampilan_oleh' => null, 'tampilan_pada' => null]);
    }

    /**
     * Lepas tunjukan pada satu versi tertentu, apa pun lingkupnya.
     *
     * Dipakai ketika sebuah versi berhenti layak ditunjuk — dibatalkan, atau
     * ditarik kembali ke draft. Tanpa ini, tunjukan akan menggantung pada versi
     * yang tidak lagi resmi, dan menu Renstra menampilkan dokumen yang statusnya
     * sudah bukan published.
     */
    public function lepasTampilanVersi(int $versiId): void
    {
        if (! $this->siapTampilan()) {
            return;
        }

        $this->db->table($this->table)->where('id', $versiId)->where('tampilan_utama', 1)
            ->update(['tampilan_utama' => 0, 'tampilan_oleh' => null, 'tampilan_pada' => null]);
    }

    public function sisipkan(array $data): int
    {
        $data['created_at'] ??= date('Y-m-d H:i:s');
        $data['status']     ??= self::STATUS_DRAFT;

        $this->db->table($this->table)->insert($this->saring($data));

        return (int) $this->db->insertID();
    }

    public function perbarui(int $versiId, array $data): bool
    {
        if ($data === []) {
            return true;
        }

        return $this->db->table($this->table)
            ->where('id', $versiId)
            ->update($this->saring($data));
    }


    /* =========================================================
     * PENGHAPUSAN VERSI
     * =======================================================*/

    /**
     * Tabel isi LIVE per modul — baris berjalan yang menyandang `version_id`.
     *
     * Kolom ini TIDAK ber-foreign key ke `dokumen_versi`, jadi basis data tidak
     * akan menahan apa pun. Inilah rujukan paling berbahaya: menghapus versinya
     * membuat isi yang masih tampil di layar menunjuk versi yang sudah tiada.
     *
     * @var array<string, array<string,string>> [modul => [tabel => sebutan]]
     */
    private const ISI_LIVE = [
        'rpjmd' => [
            'rpjmd_misi'              => 'misi RPJMD berjalan',
            'rpjmd_tujuan'            => 'tujuan RPJMD berjalan',
            'rpjmd_sasaran'           => 'sasaran RPJMD berjalan',
            'rpjmd_indikator_tujuan'  => 'indikator tujuan RPJMD berjalan',
            'rpjmd_indikator_sasaran' => 'indikator sasaran RPJMD berjalan',
        ],
        'renstra' => [
            'renstra_tujuan'            => 'tujuan Renstra berjalan',
            'renstra_sasaran'           => 'sasaran Renstra berjalan',
            'renstra_indikator_tujuan'  => 'indikator tujuan Renstra berjalan',
            'renstra_indikator_sasaran' => 'indikator sasaran Renstra berjalan',
        ],
    ];

    /**
     * Siapa saja yang masih merujuk sebuah versi dokumen — penghalang hapus.
     *
     * =====================================================================
     * MENGAPA HARUS DIHITUNG SENDIRI
     *
     * Dari 13 foreign key yang menunjuk `dokumen_versi`, hanya sebagian yang
     * benar-benar menjaga:
     *
     *   * arsip isi (`rpjmd_versi_*`, `renstra_versi_*`) ON DELETE CASCADE —
     *     memang seharusnya ikut terhapus, jadi sengaja tidak didaftar;
     *   * `version_submission_history` & `version_correction_requests`
     *     RESTRICT — basis data memang menahannya, tetapi galatnya berupa
     *     pesan SQL mentah. Didaftar di sini supaya penolakannya terbaca;
     *   * `dokumen_versi.source_version_id` & `copied_from_version_id`
     *     SET NULL — silsilah versi HILANG diam-diam tanpa satu pun galat.
     *
     * Sisanya sama sekali tanpa foreign key: isi live, izin sunting, arsip
     * Renstra yang berjangkar ke RPJMD, dan seluruh rujukan `source_version_id`
     * di IKU/LAKIP.
     *
     * =====================================================================
     * `source_version_id` ITU POLIMORFIK — WAJIB DISARING `source_type`
     *
     * Satu kolom `source_version_id` dipakai bersama oleh sumber 'rpjmd',
     * 'renstra', dan 'iku', dan penomorannya berjalan sendiri-sendiri.
     * Menghitung tanpa `source_type` berarti versi RPJMD #1 tampak dirujuk
     * oleh apa pun yang bersumber dari Renstra #1 atau revisi IKU #1 — pada
     * basis data ini ada 4 baris LAKIP semacam itu, dan semuanya milik IKU.
     * Tanpa saringan ini, penghapusan ditolak karena alasan yang keliru.
     *
     * @return array<string,int> [keterangan => jumlah] yang tidak kosong
     */
    public function penghalangHapus(int $versiId, string $modul): array
    {
        $db  = $this->db;
        $ada = [];

        // Hitung aman: lewati tabel/kolom yang belum ada di lingkungan ini.
        $hitung = static function (string $tabel, array $kondisi) use ($db): int {
            if (! $db->tableExists($tabel)) {
                return 0;
            }

            foreach (array_keys($kondisi) as $kolom) {
                if (! $db->fieldExists($kolom, $tabel)) {
                    return 0;
                }
            }

            return $db->table($tabel)->where($kondisi)->countAllResults();
        };

        // 1. Isi LIVE yang menyandang versi ini.
        foreach (self::ISI_LIVE[$modul] ?? [] as $tabel => $sebutan) {
            $n = $hitung($tabel, ['version_id' => $versiId]);

            if ($n > 0) {
                $ada[$sebutan] = $n;
            }
        }

        // 2. Jejak siklus versi (FK RESTRICT — pasti menahan di tingkat basis).
        $n = $hitung('version_submission_history', ['version_id' => $versiId]);

        if ($n > 0) {
            $ada['catatan riwayat pengajuan'] = $n;
        }

        $n = $hitung('version_correction_requests', ['version_id' => $versiId]);

        if ($n > 0) {
            $ada['permintaan koreksi'] = $n;
        }

        // 3. Izin sunting yang berjangkar ke versi ini.
        $n = $hitung('dokumen_izin_sunting', ['version_id' => $versiId]);

        if ($n > 0) {
            $ada['izin sunting'] = $n;
        }

        // 4. Silsilah antarversi (FK SET NULL — hilang tanpa suara).
        $n = $hitung('dokumen_versi', ['source_version_id' => $versiId]);

        if ($n > 0) {
            $ada['versi lain yang bersumber dari versi ini'] = $n;
        }

        $n = $hitung('dokumen_versi', ['copied_from_version_id' => $versiId]);

        if ($n > 0) {
            $ada['versi lain yang disalin dari versi ini'] = $n;
        }

        // 5. Arsip Renstra yang berjangkar ke sebuah versi RPJMD.
        if ($modul === VersionScope::MODUL_RPJMD) {
            $n = $hitung('renstra_versi_tujuan', ['rpjmd_version_id' => $versiId]);

            if ($n > 0) {
                $ada['tujuan Renstra terarsip yang berjangkar ke versi ini'] = $n;
            }
        }

        // 6. Rujukan bersumber di IKU & LAKIP — SELALU dengan source_type.
        $bersumber = [
            'iku_sasaran'          => 'sasaran IKU berjalan yang bersumber dari versi ini',
            'iku_indikator'        => 'indikator IKU berjalan yang bersumber dari versi ini',
            'iku_revisi_sasaran'   => 'sasaran IKU terarsip yang bersumber dari versi ini',
            'iku_revisi_indikator' => 'indikator IKU terarsip yang bersumber dari versi ini',
            'lakip'                => 'baris LAKIP yang dinilai terhadap versi ini',
            'lakip_snapshot'       => 'snapshot LAKIP yang dibekukan dari versi ini',
            'lakip_snapshot_baris' => 'baris snapshot LAKIP yang menunjuk versi ini',
        ];

        foreach ($bersumber as $tabel => $sebutan) {
            $n = $hitung($tabel, ['source_type' => $modul, 'source_version_id' => $versiId]);

            if ($n > 0) {
                $ada[$sebutan] = $n;
            }
        }

        return $ada;
    }

    /**
     * Alasan sebuah versi TIDAK boleh dihapus menurut statusnya, atau NULL.
     *
     * =====================================================================
     * HANYA DRAFT DAN BATAL YANG BOLEH
     *
     * `published` ditolak karena §16: versi yang sudah ditetapkan bersifat
     * tetap — ia jangkar bagi timeline, perbandingan antarversi, dan seluruh
     * dokumen turunan. Yang keliru pada versi resmi diperbaiki lewat Izin
     * Sunting atau versi baru, bukan dengan menghapus jejaknya.
     *
     * `pending_approval` ditolak karena sedang di meja verifikator: menghapus
     * berkas yang sedang dinilai orang lain membuat antreannya menunjuk
     * ketiadaan. Batalkan dulu, baru hapus.
     */
    public function alasanTolakHapus(array $versi): ?string
    {
        $status = (string) ($versi['status'] ?? '');

        if ($status === self::STATUS_PUBLISHED) {
            return 'Versi yang sudah ditetapkan tidak bisa dihapus. Ia menjadi '
                . 'jangkar bagi dokumen turunan dan perbandingan antarversi. '
                . 'Perbaiki lewat Izin Sunting atau buat versi baru.';
        }

        if ($status === self::STATUS_PENDING) {
            return 'Versi ini sedang menunggu verifikasi. Batalkan dulu '
                . 'pengajuannya, baru versinya bisa dihapus.';
        }

        return null;
    }

    /**
     * HAPUS sebuah versi dokumen beserta arsip isinya.
     *
     * Arsip isi (`rpjmd_versi_*` / `renstra_versi_*`) ikut terhapus lewat
     * foreign key ON DELETE CASCADE. Isi LIVE tidak pernah ikut — dan memang
     * tidak boleh: penghalangHapus() sudah menolak versi yang masih menyandang
     * baris live, jadi bila sampai di sini tidak ada satu pun yang menunjuknya.
     *
     * @return array{nama:string,arsip:int} ringkasan untuk pesan layar
     *
     * @throws RuntimeException bila versinya tidak ada, statusnya menolak,
     *                          atau masih ada yang merujuknya
     */
    public function hapusVersi(int $versiId): array
    {
        return $this->dalamTransaksi(function () use ($versiId) {
            $versi = $this->ambil($versiId);

            if ($versi === null) {
                throw new RuntimeException('Versi tidak ditemukan.');
            }

            $tolak = $this->alasanTolakHapus($versi);

            if ($tolak !== null) {
                throw new RuntimeException($tolak);
            }

            // Diperiksa ULANG di dalam transaksi, bukan hanya di layar: antara
            // halaman dirender dan tombolnya ditekan, orang lain bisa saja
            // menjadikan versi ini sumber versi barunya.
            $penghalang = $this->penghalangHapus($versiId, (string) $versi['modul']);

            if ($penghalang !== []) {
                $rinci = [];

                foreach ($penghalang as $apa => $n) {
                    $rinci[] = $n . ' ' . $apa;
                }

                throw new RuntimeException('Masih dirujuk: ' . implode('; ', $rinci) . '.');
            }

            $arsip = 0;

            foreach (['misi', 'tujuan', 'sasaran'] as $bagian) {
                $tabel = $versi['modul'] . '_versi_' . $bagian;

                if ($this->db->tableExists($tabel)) {
                    $arsip += $this->db->table($tabel)->where('version_id', $versiId)->countAllResults();
                }
            }

            $this->db->table($this->table)->where('id', $versiId)->delete();

            return [
                'nama'  => (string) ($versi['label'] ?? 'versi'),
                'arsip' => $arsip,
            ];
        }, 'hapus versi dokumen');
    }

    /** Hanya kolom yang diizinkan — mencegah controller menulis kolom generated. */
    private function saring(array $data): array
    {
        return array_intersect_key($data, array_flip($this->allowedFields));
    }
}
