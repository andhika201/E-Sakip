<?php

namespace App\Models;

use CodeIgniter\Model;

class LakipModel extends Model
{
    protected $table = 'lakip';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useAutoIncrement = true;

    /**
     * =====================================================================
     * ENAM KOLOM LINGKUP WAJIB ADA DI DAFTAR INI
     *
     * `allowedFields` adalah PENYARING: medan di luar daftar DIBUANG DIAM-DIAM
     * oleh CI4, bukan ditolak. Daftar ini pernah tertinggal saat kolom
     * `tahun`/`opd_id`/`mode`/`source_*` ditambahkan (migrasi 2026-08-20), dan
     * akibatnya nyata: 134 baris realisasi lahir tanpa lingkup — 98 di
     * antaranya berisi angka yang diketik operator, tidak terbaca layar mana
     * pun. Controller mengirim datanya dengan benar; penyaring inilah yang
     * membuangnya.
     *
     * Jalur IKU paling parah: tampilannya mengunci pada
     * `source_type + tahun + opd_id + source_entity_id`, jadi tanpa keempatnya
     * realisasi yang baru disimpan LENYAP SEKETIKA dari layar.
     * =====================================================================
     */
    protected $allowedFields = [
        'renstra_target_id',
        'rpjmd_target_id',
        'target_hitung',
        'target_lalu',
        'capaian_lalu',
        'capaian_tahun_ini',
        'capaian_hitung',
        'status',
        'tahun',
        'opd_id',
        'mode',
        'source_type',
        'source_version_id',
        'source_entity_id',
        'lakip_version_id',
        'created_at',
        'updated_at',
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    /* =========================================================
     * YEARS (dropdown)
     * =======================================================*/
    public function getAvailableYears(): array
    {
        $sql = "
            SELECT tahun FROM renstra_target
            UNION
            SELECT tahun FROM rpjmd_target
            ORDER BY tahun ASC
        ";
        $rows = $this->db->query($sql)->getResultArray();
        return array_map(static fn($r) => (string) $r['tahun'], $rows);
    }

    /* =========================================================
     * INDEX DATA (TARGET LIST)
     * - dipakai index admin_kab mode opd (renstra) dan kabupaten (rpjmd)
     * =======================================================*/

    /**
     * List target RENSTRA per tahun (bisa semua OPD atau 1 OPD).
     * Return flat rows -> mudah dibuat rowspan di view.
     */
    public function getIndexRenstraTargets(string $tahun, ?int $opdId = null): array
    {
        $b = $this->db->table('renstra_target rt')
            ->select("
                rt.id                    AS target_id,
                rt.tahun                 AS tahun,
                rt.target                AS target_tahun_ini,

                ris.id                   AS indikator_id,
                ris.indikator_sasaran    AS indikator_sasaran,
                s.satuan                 AS satuan,
                ris.jenis_indikator      AS jenis_indikator,

                rs.id                    AS sasaran_id,
                rs.sasaran               AS sasaran,

                o.id                     AS opd_id,
                o.nama_opd               AS nama_opd
            ")
            ->join('renstra_indikator_sasaran ris', 'ris.id = rt.renstra_indikator_id', 'left')
            ->join('renstra_sasaran rs', 'rs.id = ris.renstra_sasaran_id', 'left')
            ->join('satuan s', 's.id = ris.satuan', 'left')
            ->join('opd o', 'o.id = rs.opd_id', 'left')
            ->where('rt.tahun', $tahun);

        if (!empty($opdId)) {
            $b->where('rs.opd_id', $opdId);
        }

        return $b->orderBy('o.nama_opd', 'ASC')
            ->orderBy('rs.id', 'ASC')
            ->orderBy('ris.id', 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * List target RPJMD per tahun.
     */
    public function getIndexRpjmdTargets(string $tahun): array
    {
        $b = $this->db->table('rpjmd_target rpj')
            ->select("
                rpj.id                   AS target_id,
                rpj.tahun                AS tahun,
                rpj.target_tahunan       AS target_tahun_ini,

                ris.id                   AS indikator_id,
                ris.indikator_sasaran    AS indikator_sasaran,
                ris.satuan               AS satuan,
                ris.jenis_indikator      AS jenis_indikator,

                rs.id                    AS sasaran_id,
                rs.sasaran_rpjmd         AS sasaran
            ")
            ->join('rpjmd_indikator_sasaran ris', 'ris.id = rpj.indikator_sasaran_id', 'left')
            ->join('rpjmd_sasaran rs', 'rs.id = ris.sasaran_id', 'left')
            ->join('rpjmd_tujuan rtuj', 'rtuj.id = rs.tujuan_id', 'left')
            ->join('rpjmd_misi rmis', 'rmis.id = rtuj.misi_id', 'left')
            ->where('rpj.tahun', $tahun);

        return $b->orderBy('rmis.id', 'ASC')
            ->orderBy('rtuj.id', 'ASC')
            ->orderBy('rs.id', 'ASC')
            ->orderBy('ris.id', 'ASC')
            ->get()
            ->getResultArray();
    }

    /* =========================================================
     * LAKIP MAP BY TARGET_ID
     * =======================================================*/

    /**
     * Ambil LAKIP map untuk RENSTRA (key = renstra_target_id)
     * Filter status optional, opd optional (via join renstra_sasaran).
     */
    public function getLakipMapRenstra(string $tahun, ?string $status = null, ?int $opdId = null): array
    {
        $b = $this->db->table('lakip l')
            ->select("
            l.*,
            rt.tahun AS tahun_target,
            rt.target AS target_tahun_ini,
            ris.id AS indikator_id,
            rs.id AS sasaran_id
            ")
            ->join('renstra_target rt', 'rt.id = l.renstra_target_id', 'left')
            ->join('renstra_indikator_sasaran ris', 'ris.id = rt.renstra_indikator_id', 'left')
            ->join('renstra_sasaran rs', 'rs.id = ris.renstra_sasaran_id', 'left')
            ->where('rt.tahun', $tahun);


        if (!empty($status)) {
            $b->where('l.status', $status);
        }
        if (!empty($opdId)) {
            $b->where('rs.opd_id', $opdId);
        }

        $rows = $b->get()->getResultArray();

        $map = [];
        foreach ($rows as $r) {
            if (!empty($r['renstra_target_id'])) {
                $map[(int) $r['renstra_target_id']] = $r;
            }
        }
        return $map;
    }

    /* =========================================================
     * SUMBER IKU
     *
     * =====================================================================
     * MENGAPA DARI ARSIP REVISI, BUKAN DARI IKU BERJALAN
     *
     * LAKIP menilai kinerja satu tahun yang sudah lewat. Yang relevan adalah
     * IKU yang berlaku DI TAHUN ITU — bukan yang kebetulan berlaku saat
     * laporannya disusun. Membaca IKU berjalan berarti laporan 2026 ikut
     * berubah setiap kali IKU direvisi di 2028, dan itu membatalkan seluruh
     * gunanya sebagai dokumen pertanggungjawaban.
     *
     * =====================================================================
     * DUA ID, DAN JANGAN TERTUKAR
     *
     * `indikator_id`  -> id indikator IKU BERJALAN (`iku_indikator.id`).
     *                    Inilah kunci tempat realisasi menempel, sehingga
     *                    berganti versi tampilan TIDAK menghilangkan capaian
     *                    yang sudah diisi.
     * `arsip_id`      -> id baris arsip revisi. Hanya untuk penelusuran.
     *
     * Baris arsip yang belum pernah diterapkan ke IKU berjalan tidak punya
     * `indikator_id`; ia tetap ditampilkan tetapi tidak bisa diisi realisasi —
     * dan itu jujur, sebab indikatornya memang belum resmi ada.
     *
     * @return array<int,array<string,mixed>> bentuknya disamakan dengan
     *         getIndexRenstraTargets() supaya view tidak perlu tahu sumbernya
     * =======================================================*/
    public function getIndexIkuTargets(int $revisiId, int $tahun, ?int $opdId = null): array
    {
        if (! $this->db->tableExists('iku_revisi_sasaran')) {
            return [];
        }

        $rows = $this->db->table('iku_revisi_indikator ri')
            ->select("
                ri.id                      AS arsip_id,
                ri.sumber_indikator_id     AS indikator_id,
                ri.indikator               AS indikator_sasaran,
                COALESCE(ri.satuan_nama, ri.satuan) AS satuan,
                ri.jenis_indikator         AS jenis_indikator,
                ri.perubahan_substansial   AS perubahan_substansial,
                ri.jenis_perubahan         AS jenis_perubahan,

                rt.target                  AS target_tahun_ini,

                rs.id                      AS arsip_sasaran_id,
                rs.sumber_sasaran_id       AS sasaran_id,
                rs.sasaran                 AS sasaran,

                r.opd_id                   AS opd_id,
                o.nama_opd                 AS nama_opd
            ", false)
            ->join('iku_revisi_sasaran rs', 'rs.id = ri.revisi_sasaran_id')
            ->join('iku_revisi r', 'r.id = ri.revisi_id')
            ->join('iku_revisi_target rt', 'rt.revisi_indikator_id = ri.id AND rt.tahun = ' . (int) $tahun, 'left', false)
            ->join('opd o', 'o.id = r.opd_id', 'left')
            ->where('ri.revisi_id', $revisiId)
            // Baris nisan "dihentikan" bukan isi dokumen; ia penanda perubahan.
            ->where('ri.jenis_perubahan !=', 'dihentikan');

        if (! empty($opdId)) {
            $rows->where('r.opd_id', $opdId);
        }

        $hasil = $rows->orderBy('rs.urutan', 'ASC')->orderBy('ri.urutan', 'ASC')
            ->orderBy('ri.id', 'ASC')->get()->getResultArray();

        foreach ($hasil as &$r) {
            $r['tahun'] = $tahun;

            // `target_id` dipakai view sebagai kunci baris. Untuk sumber IKU,
            // yang stabil lintas versi adalah id indikator BERJALAN.
            $r['target_id'] = $r['indikator_id'] !== null ? (int) $r['indikator_id'] : null;

            $r['sasaran_id'] = $r['sasaran_id'] !== null
                ? (int) $r['sasaran_id']
                // Sasaran arsip yang belum punya padanan berjalan tetap perlu
                // kunci pengelompokan; dipakai id arsipnya dengan tanda negatif
                // supaya tidak mungkin bertabrakan dengan id berjalan.
                : -((int) $r['arsip_sasaran_id']);
        }
        unset($r);

        return $hasil;
    }

    /**
     * Realisasi LAKIP bersumber IKU, berkunci id indikator IKU BERJALAN.
     *
     * @return array<int,array<string,mixed>>
     */
    public function getLakipMapIku(int $tahun, ?string $status = null, ?int $opdId = null): array
    {
        if (! $this->db->fieldExists('source_entity_id', 'lakip')) {
            return [];
        }

        $b = $this->db->table('lakip l')
            ->select('l.*, l.source_entity_id AS indikator_id')
            ->where('l.source_type', 'iku')
            ->where('l.tahun', $tahun)
            ->where('l.source_entity_id IS NOT NULL', null, false);

        if (! empty($status)) {
            $b->where('l.status', $status);
        }

        if (! empty($opdId)) {
            $b->where('l.opd_id', $opdId);
        } else {
            // null = lingkup KABUPATEN (LAKIP kabupaten bersumber IKU
            // Kabupaten), bukan "tanpa saringan": tanpa cabang ini peta
            // kabupaten ikut memuat realisasi milik seluruh OPD.
            $b->groupStart()
                ->where('l.opd_id IS NULL', null, false)
                ->orWhere('l.opd_id', 0)
                ->groupEnd();
        }

        $map = [];

        foreach ($b->get()->getResultArray() as $r) {
            $map[(int) $r['source_entity_id']] = $r;
        }

        // =================================================================
        // JEMBATAN REALISASI LAMA
        //
        // Sebelum LAKIP membaca IKU, realisasi diinput menempel ke target
        // Renstra/RPJMD. Tanpa jembatan ini, layar bersumber IKU tampil
        // KOSONG padahal capaiannya sudah diketik operator — dan tombol
        // "tambah" yang lalu muncul menggiring mereka mengetik ulang,
        // melahirkan baris kembar.
        //
        // Kuncinya silsilah: `iku_indikator.source_indikator_id` menunjuk
        // indikator sumber yang targetnya dirujuk realisasi lama. Baris
        // bersumber IKU (bila ada) selalu MENANG — jembatan hanya mengisi
        // lubang, tidak menimpa.
        // =================================================================
        foreach ($this->jembatanRealisasiLama($tahun, $status, $opdId) as $k => $r) {
            if (! isset($map[$k])) {
                $map[$k] = $r;
            }
        }

        return $map;
    }

    /**
     * Realisasi lama (berjangkar Renstra/RPJMD) dipetakan ke id indikator IKU
     * berjalan — kunci yang sama dengan getLakipMapIku().
     *
     * @return array<int,array<string,mixed>>
     */
    private function jembatanRealisasiLama(int $tahun, ?string $status, ?int $opdId): array
    {
        if (! $this->db->fieldExists('source_indikator_id', 'iku_indikator')) {
            return [];
        }

        $map = [];

        if (! empty($opdId)) {
            // Lingkup OPD: realisasi lama menempel ke renstra_target.
            $b = $this->db->table('lakip l')
                ->select('l.*, ii.id AS indikator_id', false)
                ->join('renstra_target rt', 'rt.id = l.renstra_target_id')
                ->join('renstra_indikator_sasaran ris', 'ris.id = rt.renstra_indikator_id')
                ->join('renstra_sasaran rs', 'rs.id = ris.renstra_sasaran_id')
                ->join(
                    'iku_indikator ii',
                    "ii.source_indikator_id = ris.id AND ii.source_type = 'renstra'"
                    . ' AND ii.dihentikan_pada IS NULL',
                    'inner',
                    false
                )
                ->where('rt.tahun', $tahun)
                ->where('rs.opd_id', $opdId);
        } else {
            // Lingkup kabupaten: realisasi lama menempel ke rpjmd_target.
            $b = $this->db->table('lakip l')
                ->select('l.*, ii.id AS indikator_id', false)
                ->join('rpjmd_target rpt', 'rpt.id = l.rpjmd_target_id')
                ->join(
                    'iku_indikator ii',
                    "ii.source_indikator_id = rpt.indikator_sasaran_id AND ii.source_type = 'rpjmd'"
                    . ' AND ii.dihentikan_pada IS NULL',
                    'inner',
                    false
                )
                ->join('iku_sasaran iks', 'iks.id = ii.iku_sasaran_id AND iks.opd_id IS NULL', 'inner', false)
                ->where('rpt.tahun', $tahun);
        }

        if (! empty($status)) {
            $b->where('l.status', $status);
        }

        foreach ($b->get()->getResultArray() as $r) {
            $r['jembatan'] = true; // penanda: capaian lama, dinaikkan dari jangkar sumber
            $map[(int) $r['indikator_id']] = $r;
        }

        return $map;
    }

    /**
     * Rincian satu indikator IKU untuk form LAKIP tahun tertentu.
     *
     * Dicari lewat id indikator IKU BERJALAN, bukan id baris arsip — itulah
     * kunci yang stabil ketika versi tampilan diganti.
     *
     * @return array<string,mixed>|null
     */
    public function getIkuTargetDetail(int $revisiId, int $indikatorId, int $tahun): ?array
    {
        if (! $this->db->tableExists('iku_revisi_indikator')) {
            return null;
        }

        $row = $this->db->table('iku_revisi_indikator ri')
            ->select("
                ri.id                      AS arsip_id,
                ri.sumber_indikator_id     AS indikator_id,
                ri.indikator               AS indikator_sasaran,
                COALESCE(ri.satuan_nama, ri.satuan) AS satuan,
                ri.jenis_indikator,
                rt.target                  AS target,
                rs.sasaran                 AS sasaran,
                rs.sumber_sasaran_id       AS sasaran_id,
                r.opd_id                   AS opd_id
            ", false)
            ->join('iku_revisi_sasaran rs', 'rs.id = ri.revisi_sasaran_id')
            ->join('iku_revisi r', 'r.id = ri.revisi_id')
            ->join('iku_revisi_target rt', 'rt.revisi_indikator_id = ri.id AND rt.tahun = ' . (int) $tahun, 'left', false)
            ->where('ri.revisi_id', $revisiId)
            ->where('ri.sumber_indikator_id', $indikatorId)
            ->where('ri.jenis_perubahan !=', 'dihentikan')
            ->get()->getRowArray();

        if ($row === null) {
            return null;
        }

        $row['tahun'] = $tahun;

        return $row;
    }

    /** Baris LAKIP bersumber IKU untuk satu indikator & tahun. */
    public function getLakipByIku(int $indikatorId, int $tahun, ?int $opdId = null): ?array
    {
        if (! $this->db->fieldExists('source_entity_id', 'lakip')) {
            return null;
        }

        $b = $this->db->table('lakip')
            ->where('source_type', 'iku')
            ->where('source_entity_id', $indikatorId)
            ->where('tahun', $tahun);

        if (! empty($opdId)) {
            $b->where('opd_id', $opdId);
        }

        $row = $b->get()->getRowArray();

        return $row ?: null;
    }

    /**
     * Ambil LAKIP map untuk RPJMD (key = rpjmd_target_id)
     */
    public function getLakipMapRpjmd(string $tahun, ?string $status = null): array
    {
        $b = $this->db->table('lakip l')
            ->select("
                l.*,
                rpj.tahun AS tahun_target,
                rpj.target_tahunan AS target_tahun_ini,
                ris.id AS indikator_id,
                rs.id AS sasaran_id
            ")
            ->join('rpjmd_target rpj', 'rpj.id = l.rpjmd_target_id', 'left')
            ->join('rpjmd_indikator_sasaran ris', 'ris.id = rpj.indikator_sasaran_id', 'left')
            ->join('rpjmd_sasaran rs', 'rs.id = ris.sasaran_id', 'left')
            ->where('rpj.tahun', $tahun);


        if (!empty($status)) {
            $b->where('l.status', $status);
        }

        $rows = $b->get()->getResultArray();

        $map = [];
        foreach ($rows as $r) {
            if (!empty($r['rpjmd_target_id'])) {
                $map[(int) $r['rpjmd_target_id']] = $r;
            }
        }
        return $map;
    }

    /**
     * Wrapper biar controller/view gampang:
     * return ['rows'=>..., 'lakipMap'=>...]
     */
    public function getLakipByMode(string $mode, string $tahun, ?string $status = null, ?int $opdId = null): array
    {
        if ($mode === 'opd') {
            return [
                'rows' => $this->getIndexRenstraTargets($tahun, $opdId),
                'lakipMap' => $this->getLakipMapRenstra($tahun, $status, $opdId),
            ];
        }

        return [
            'rows' => $this->getIndexRpjmdTargets($tahun),
            'lakipMap' => $this->getLakipMapRpjmd($tahun, $status),
        ];
    }

    /* =========================================================
     * TARGET + INDICATOR DETAIL (FOR ADD/EDIT)
     * =======================================================*/

    public function getRenstraTargetDetailByIndikatorAndYear(int $indikatorId, string $tahun): ?array
    {
        return $this->db->table('renstra_target rt')
            ->select("
                rt.*,
                ris.indikator_sasaran,
                COALESCE(s.satuan, ris.satuan) AS satuan,
                ris.jenis_indikator,
                rs.sasaran,
                o.nama_opd
            ")
            ->join('renstra_indikator_sasaran ris', 'ris.id = rt.renstra_indikator_id', 'left')
            ->join('renstra_sasaran rs', 'rs.id = ris.renstra_sasaran_id', 'left')
            ->join('satuan s', 's.id = ris.satuan', 'left')
            ->join('opd o', 'o.id = rs.opd_id', 'left')
            ->where('rt.renstra_indikator_id', $indikatorId)
            ->where('rt.tahun', $tahun)
            ->get()
            ->getRowArray();
    }

    public function getRpjmdTargetDetailByIndikatorAndYear(int $indikatorId, string $tahun): ?array
    {
        return $this->db->table('rpjmd_target rpj')
            ->select("
                rpj.*,
                ris.indikator_sasaran,
                COALESCE(s.satuan, ris.satuan) AS satuan,
                ris.jenis_indikator,
                rs.sasaran_rpjmd AS sasaran
            ")
            ->join('rpjmd_indikator_sasaran ris', 'ris.id = rpj.indikator_sasaran_id', 'left')
            ->join('rpjmd_sasaran rs', 'rs.id = ris.sasaran_id', 'left')
            ->join('satuan s', 's.id = ris.satuan', 'left')
            ->where('rpj.indikator_sasaran_id', $indikatorId)
            ->where('rpj.tahun', $tahun)
            ->get()
            ->getRowArray();
    }

    /**
     * Resolve nilai kolom `satuan` (menyimpan id ke tabel satuan) menjadi nama satuan.
     * Kalau nilainya bukan id valid (mis. teks lama seperti "%"), kembalikan apa adanya.
     */
    public function resolveSatuanName($satuan): string
    {
        if ($satuan === null || $satuan === '') {
            return '';
        }
        if (is_numeric($satuan)) {
            $row = $this->db->table('satuan')
                ->select('satuan')
                ->where('id', (int) $satuan)
                ->get()
                ->getRowArray();
            if (!empty($row['satuan'])) {
                return (string) $row['satuan'];
            }
        }
        return (string) $satuan;
    }

    /* =========================================================
     * KOMPATIBILITAS controller lama
     * =======================================================*/

    /**
     * LIST LAKIP RENSTRA (untuk role admin_opd / mode opd)
     * controller lama: getRenstra($opdId, $status, $tahun)
     */
    public function getRenstra(int $opdId, ?string $status = null, ?string $tahun = null): array
    {
        $tahun = $tahun ?: date('Y');

        $b = $this->db->table('lakip l')
            ->select("
                l.*,

                rt.id      AS renstra_target_id,
                rt.tahun   AS indikator_tahun,
                rt.target  AS target_tahun_ini,

                ris.id     AS renstra_indikator_id,
                ris.indikator_sasaran,
                ris.satuan,
                ris.jenis_indikator,

                rs.id      AS renstra_sasaran_id,
                rs.sasaran AS sasaran,
                rs.opd_id  AS opd_id
            ")
            ->join('renstra_target rt', 'rt.id = l.renstra_target_id', 'left')
            ->join('renstra_indikator_sasaran ris', 'ris.id = rt.renstra_indikator_id', 'left')
            ->join('renstra_sasaran rs', 'rs.id = ris.renstra_sasaran_id', 'left')
            ->where('rs.opd_id', $opdId)
            ->where('rt.tahun', $tahun);

        if (!empty($status)) {
            $b->where('l.status', $status);
        }

        return $b->orderBy('rs.id', 'ASC')
            ->orderBy('ris.id', 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * LIST LAKIP RPJMD (untuk mode kabupaten)
     * controller lama: getRPJMD($status, $tahun)
     */
    public function getRPJMD(?string $status = null, ?string $tahun = null): array
    {
        $tahun = $tahun ?: date('Y');

        $b = $this->db->table('lakip l')
            ->select("
                l.*,

                rpj.id             AS rpjmd_target_id,
                rpj.tahun          AS indikator_tahun,
                rpj.target_tahunan AS target_tahun_ini,

                ris.id             AS rpjmd_indikator_id,
                ris.indikator_sasaran,
                ris.satuan,
                ris.jenis_indikator,

                rs.id              AS rpjmd_sasaran_id,
                rs.sasaran_rpjmd   AS sasaran
            ")
            ->join('rpjmd_target rpj', 'rpj.id = l.rpjmd_target_id', 'left')
            ->join('rpjmd_indikator_sasaran ris', 'ris.id = rpj.indikator_sasaran_id', 'left')
            ->join('rpjmd_sasaran rs', 'rs.id = ris.sasaran_id', 'left')
            ->where('rpj.tahun', $tahun);

        if (!empty($status)) {
            $b->where('l.status', $status);
        }

        return $b->orderBy('rs.id', 'ASC')
            ->orderBy('ris.id', 'ASC')
            ->get()
            ->getResultArray();
    }
    /* =========================================================
     * HELPER: GROUP ROWS FOR VIEW (ROWSPAN)
     * =======================================================*/
    public function groupIndexRowsBySasaran(array $rows, string $mode = 'opd'): array
    {
        $grouped = [];

        foreach ($rows as $r) {
            $sasaranId = (int) ($r['sasaran_id'] ?? 0);
            $sasaranText = $r['sasaran'] ?? '';

            if (!isset($grouped[$sasaranId])) {
                $grouped[$sasaranId] = [
                    // samakan key dengan view kamu
                    'sasaran' => $sasaranText,
                    'indikator_sasaran' => [],
                ];
            }

            $grouped[$sasaranId]['indikator_sasaran'][] = $r;
        }

        return array_values($grouped);
    }


    /**
     * DETAIL LAKIP untuk 1 indikator (dipakai form edit controller lama)
     */
    public function getLakipDetail(int $indikatorId, string $role, ?string $tahun = null): ?array
    {
        $tahun = $tahun ?: date('Y');

        if ($role === 'admin_kab') {
            $target = $this->db->table('rpjmd_target')
                ->select('id')
                ->where('indikator_sasaran_id', $indikatorId)
                ->where('tahun', $tahun)
                ->get()
                ->getRowArray();

            if (!$target)
                return null;

            return $this->where('rpjmd_target_id', (int) $target['id'])->first();
        }

        $target = $this->db->table('renstra_target')
            ->select('id')
            ->where('renstra_indikator_id', $indikatorId)
            ->where('tahun', $tahun)
            ->get()
            ->getRowArray();

        if (!$target)
            return null;

        return $this->where('renstra_target_id', (int) $target['id'])->first();
    }

    public function getLakipByRenstraTarget(int $renstraTargetId): ?array
    {
        return $this->where('renstra_target_id', $renstraTargetId)->first();
    }

    public function getLakipByRpjmdTarget(int $rpjmdTargetId): ?array
    {
        return $this->where('rpjmd_target_id', $rpjmdTargetId)->first();
    }

    /* =========================================================
     * MUTATIONS
     * =======================================================*/
    public function updateLakip(int $id, array $data): bool
    {
        return (bool) $this->update($id, $data);
    }

    public function deleteLakip(int $id): bool
    {
        return (bool) $this->delete($id);
    }
}
