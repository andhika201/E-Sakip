<?php

namespace App\Controllers;

use App\Controllers\BaseController;

use App\Models\RpjmdModel;
use App\Models\RkpdModel; // biarkan walau tidak dipakai dulu
use App\Models\Opd\RenstraModel;
use App\Models\OpdModel;
use App\Models\Opd\IkuModel;
use App\Models\LakipModel;
use App\Models\RktModel;

class AdminKabupatenController extends BaseController
{
    protected $RpjmdModel;
    protected $RkpdModel;
    protected $LakipKpdModel;
    protected $RenstraModel;
    protected $RktModel;          // RKT (juga dipakai sebagai sumber RKPD)
    protected $OpdModel;
    protected $IkuModel;
    protected $LakipOpdModel;

    protected $db;

    /**
     * Override tabel per modul (akan dipakai kalau tabelnya ada).
     * Urutan = prioritas.
     */
    protected array $tableMap = [
        // dukung typo & nama normal
        'rpjmd' => ['rpjmd_sasasran', 'rpjmd_sasaran'],
        'renstra' => ['renstra_sasaran', 'opd_renstra_sasaran'],

        // SATU tabel 'lakip' dipakai untuk dua mode
        'lakip_kabupaten' => ['lakip_kabupaten', 'lakip'],
        'lakip_opd' => ['lakip_opd', 'lakip'],
        // yang lain fallback ke model->builder()->getTable()
    ];

    public function __construct()
    {
        $this->db = \Config\Database::connect();
        $this->RpjmdModel = class_exists(\App\Models\RpjmdModel::class) ? new RpjmdModel() : null;
        $this->RkpdModel = class_exists(\App\Models\RkpdModel::class) ? new RkpdModel() : null; // opsional
        $this->RenstraModel = class_exists(\App\Models\Opd\RenstraModel::class) ? new RenstraModel() : null;
        $this->OpdModel = class_exists(\App\Models\OpdModel::class) ? new OpdModel() : null;
        $this->IkuModel = class_exists(\App\Models\Opd\IkuModel::class) ? new IkuModel() : null;
        $this->LakipOpdModel = class_exists(\App\Models\LakipModel::class) ? new LakipModel() : null;
        $this->RktModel = class_exists(\App\Models\RktModel::class) ? new RktModel() : null;
    }

    /** GET /adminkab/dashboard */
    public function index()
    {
        try {
            $opdId = $this->request->getGet('opd_id');
            $year = $this->request->getGet('year');

            $opdList = $this->getOpdList();
            $availableYears = $this->getAvailableYears();

            if (!$year && $availableYears) {
                $year = max($availableYears);
            }

            $opdIdInt = $opdId ? (int) $opdId : null;
            $yearInt = $year ? (int) $year : null;

            $data = [
                'dashboard_data' => [
                    'rpjmd' => $this->countFromModel('rpjmd', $this->RpjmdModel, $opdIdInt, $yearInt),

                    // RKPD diambil dari tabel RKT (tanpa filter OPD & tahun)
                    'rkpd' => $this->countFromRkpd(),

                    'renstra' => $this->countFromModel('renstra', $this->RenstraModel, $opdIdInt, $yearInt),
                    'rkt' => $this->countFromModel('rkt', $this->RktModel, $opdIdInt, $yearInt),

                    // === KHUSUS IKU pakai fungsi sendiri ===
                    'iku' => $this->countIku($opdIdInt, $yearInt),

                    // khusus LAKIP pakai relasi indikator
                    'lakip_kabupaten' => $this->countLakipKabupaten($opdIdInt, $yearInt),
                    'lakip_opd' => $this->countLakipOpd($opdIdInt, $yearInt),

                    'opd_list' => $opdList,
                    'available_years' => $availableYears,
                ],
                'summary_stats' => [
                    'total_rpjmd' => $this->totalRows('rpjmd', $this->RpjmdModel, $opdIdInt, $yearInt),
                    'total_renstra' => $this->totalRows('renstra', $this->RenstraModel, $opdIdInt, $yearInt),
                    'total_rkt' => $this->totalRows('rkt', $this->RktModel, $opdIdInt, $yearInt),
                    'total_opd' => $this->countOpd(),
                ],
            ];

            return view('adminKabupaten/dashboard', $data);
        } catch (\Throwable $e) {
            log_message('error', 'Dashboard error: ' . $e->getMessage());

            return view('adminKabupaten/dashboard', [
                'dashboard_data' => [
                    'rpjmd' => ['draft' => 0, 'selesai' => 0],
                    'rkpd' => ['draft' => 0, 'selesai' => 0],
                    'renstra' => ['draft' => 0, 'selesai' => 0],
                    'rkt' => ['draft' => 0, 'selesai' => 0],
                    'iku' => ['tercapai' => 0, 'belum' => 0],
                    'lakip_kabupaten' => ['proses' => 0, 'siap' => 0],
                    'lakip_opd' => ['proses' => 0, 'siap' => 0],
                    'opd_list' => [],
                    'available_years' => [],
                ],
                'summary_stats' => [
                    'total_rpjmd' => 0,
                    'total_renstra' => 0,
                    'total_rkt' => 0,
                    'total_opd' => 0,
                ],
                'error_message' => 'Terjadi kesalahan saat memuat data.',
            ]);
        }
    }

    /** POST /adminkab/getDashboardData (AJAX “Tampilkan”) */
    public function getDashboardData()
    {
        if (!$this->request->isAJAX()) {
            return $this->response
                ->setJSON(['status' => 'error', 'message' => 'Invalid request'])
                ->setStatusCode(400);
        }

        try {
            $opdId = trim((string) $this->request->getPost('opd_id'));
            $year = trim((string) $this->request->getPost('year'));

            $opdIdInt = $opdId !== '' ? (int) $opdId : null;
            $yearInt = $year !== '' ? (int) $year : null;

            $data = [
                'rpjmd' => $this->countFromModel('rpjmd', $this->RpjmdModel, $opdIdInt, $yearInt),

                // RKPD dari RKT, tidak ikut filter
                'rkpd' => $this->countFromRkpd(),

                'renstra' => $this->countFromModel('renstra', $this->RenstraModel, $opdIdInt, $yearInt),
                'rkt' => $this->countFromModel('rkt', $this->RktModel, $opdIdInt, $yearInt),

                // IKU & LAKIP OPD ikut filter OPD & tahun
                'iku' => $this->countIku($opdIdInt, $yearInt),
                'lakip_kabupaten' => $this->countLakipKabupaten($opdIdInt, $yearInt),
                'lakip_opd' => $this->countLakipOpd($opdIdInt, $yearInt),
            ];

            return $this->response->setJSON([
                'status' => 'success',
                'data' => $data,
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'getDashboardData error: ' . $e->getMessage());

            return $this->response
                ->setJSON(['status' => 'error', 'message' => 'Gagal memuat data'])
                ->setStatusCode(500);
        }
    }

    /* =========================
     * ========== HELPERS ======
     * ========================= */

    /** Ambil nama tabel aktual untuk modul (pakai override jika ada & tersedia). */
    private function getModuleTable(string $moduleKey, $model): ?string
    {
        // 1) Coba override list
        if (isset($this->tableMap[$moduleKey])) {
            foreach ($this->tableMap[$moduleKey] as $t) {
                if ($t && $this->db->tableExists($t)) {
                    return $t;
                }
            }
        }

        // 2) Fallback: tabel dari model
        try {
            return $model ? $model->builder()->getTable() : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function countOpd(): int
    {
        $table = $this->getModuleTable('opd', $this->OpdModel);

        return ($table && $this->db->tableExists($table))
            ? (int) $this->db->table($table)->countAllResults()
            : 0;
    }

    private function getOpdList(): array
    {
        $table = $this->getModuleTable('opd', $this->OpdModel);
        if (!$table || !$this->db->tableExists($table)) {
            return [];
        }

        $name = $this->firstExistingCol($table, ['nama_opd', 'nama', 'name']) ?? 'nama_opd';

        return $this->db->table($table)
            ->select("id, {$name} AS nama_opd")
            ->orderBy($name, 'ASC')
            ->get()
            ->getResultArray();
    }

    private function getAvailableYears(): array
    {
        $candidates = [
            $this->getModuleTable('rpjmd', $this->RpjmdModel),
            $this->getModuleTable('rkpd', $this->RktModel),      // RKPD ikut tahun RKT
            $this->getModuleTable('renstra', $this->RenstraModel),
            $this->getModuleTable('rkt', $this->RktModel),
            $this->getModuleTable('iku', $this->IkuModel),
            $this->getModuleTable('lakip_kabupaten', $this->LakipKpdModel),
            $this->getModuleTable('lakip_opd', $this->LakipOpdModel),
        ];

        $years = [];

        foreach ($candidates as $t) {
            if (!$t || !$this->db->tableExists($t)) {
                continue;
            }

            // range tahun_mulai - tahun_selesai kalau ada
            if ($this->hasCols($t, ['tahun_mulai', 'tahun_selesai'])) {
                $row = $this->db->table($t)
                    ->select('MIN(tahun_mulai) mn, MAX(tahun_selesai) mx')
                    ->get()
                    ->getRowArray();

                if (
                    $row
                    && (int) $row['mn']
                    && (int) $row['mx']
                    && (int) $row['mx'] >= (int) $row['mn']
                    && (int) $row['mx'] - (int) $row['mn'] <= 10
                ) {
                    for ($y = (int) $row['mn']; $y <= (int) $row['mx']; $y++) {
                        $years[$y] = true;
                    }
                    continue;
                }
            }

            // kalau tidak ada range, ambil distinct tahun
            $col = $this->firstExistingCol($t, ['tahun', 'tahun_anggaran']);
            if ($col) {
                $rows = $this->db->table($t)
                    ->select("$col y")
                    ->groupBy('y')
                    ->get()
                    ->getResultArray();

                foreach ($rows as $r) {
                    $v = (int) ($r['y'] ?? 0);
                    if ($v) {
                        $years[$v] = true;
                    }
                }
            }
        }

        $out = array_keys($years);
        rsort($out);

        // fallback: kalau benar-benar tidak ada data, pakai range default
        if (!$out) {
            $now = (int) date('Y');
            $out = range($now - 5, $now + 1);
            rsort($out);
        }

        return $out;
    }

    private function makeFilter(string $table, string $key, ?int $opdId, ?int $year, array $opts = []): \Closure
    {
        return function ($b) use ($table, $key, $opdId, $year, $opts) {
            // Filter Tahun
            if ($year) {
                if ($key === 'renstra' && $this->hasCols($table, ['tahun_mulai', 'tahun_selesai'])) {
                    $b->groupStart()
                        ->where('tahun_mulai <=', $year)
                        ->where('tahun_selesai >=', $year)
                        ->groupEnd();
                } else {
                    $y = $this->firstExistingCol($table, ['tahun', 'tahun_anggaran']);
                    if ($y) {
                        $b->where($y, $year);
                    }
                }
            }

            // Scope OPD
            if (($opts['scope'] ?? '') === 'kabupaten') {
                $opdCol = $this->firstExistingCol($table, ['opd_id', 'id_opd']);
                if ($opdCol) {
                    $b->groupStart()
                        ->where($opdCol, 0)
                        ->orWhere("$opdCol IS NULL", null, false)
                        ->groupEnd();
                }
            } elseif (($opts['scope'] ?? '') === 'opd') {
                $opdCol = $this->firstExistingCol($table, ['opd_id', 'id_opd']);
                if ($opdCol) {
                    if ($opdId) {
                        $b->where($opdCol, $opdId);
                    } else {
                        $b->where("$opdCol >", 0);
                    }
                }
            } else {
                $opdCol = $this->firstExistingCol($table, ['opd_id', 'id_opd']);
                if ($opdCol && $opdId) {
                    $b->where($opdCol, $opdId);
                }
            }
        };
    }

    /** RKPD dari tabel RKT — sengaja tanpa filter OPD & tahun */
    private function countFromRkpd(): array
    {
        $table = $this->getModuleTable('rkpd', $this->RktModel);
        if (!$table || !$this->db->tableExists($table)) {
            return ['draft' => 0, 'selesai' => 0];
        }

        // apply kosong => tidak ada filter
        $apply = function ($b) {
            // no-op
        };

        return $this->countByStatus('rkpd', $table, $apply);
    }

    private function countFromModel(string $moduleKey, $model, ?int $opdId, ?int $year, array $opts = []): array
    {
        $table = $this->getModuleTable($moduleKey, $model);
        if (!$table || !$this->db->tableExists($table)) {
            return ['draft' => 0, 'selesai' => 0];
        }

        $apply = $this->makeFilter($table, $moduleKey, $opdId, $year, $opts);

        return $this->countByStatus($moduleKey, $table, $apply);
    }

    private function totalRows(string $moduleKey, $model, ?int $opdId, ?int $year): int
    {
        $table = $this->getModuleTable($moduleKey, $model);
        if (!$table || !$this->db->tableExists($table)) {
            return 0;
        }

        $apply = $this->makeFilter($table, $moduleKey, $opdId, $year, []);
        $b = $this->db->table($table);
        $apply($b);

        return (int) $b->countAllResults();
    }

    /**
     * Hitung jumlah berdasarkan kolom status.
     * Default: status draft / selesai
     */
    private function countByStatus(string $moduleKey, string $table, \Closure $apply): array
    {
        $statusCol = $this->firstExistingCol($table, ['status', 'is_final', 'progress']);

        // Default: draft / selesai
        if ($statusCol === 'status') {
            $b = $this->db->table($table);
            $apply($b);

            $row = $b->select(
                "SUM(CASE WHEN LOWER(status)='selesai' THEN 1 ELSE 0 END) AS selesai,
                 SUM(CASE WHEN LOWER(status) IN ('draft','draf') THEN 1 ELSE 0 END) AS draft",
                false
            )->get()->getRowArray() ?? ['draft' => 0, 'selesai' => 0];

            return [
                'draft' => (int) $row['draft'],
                'selesai' => (int) $row['selesai'],
            ];
        }

        // is_final = 1 berarti selesai
        if ($statusCol === 'is_final') {
            $b = $this->db->table($table);
            $apply($b);

            $row = $b->select(
                "SUM(CASE WHEN is_final=1 THEN 1 ELSE 0 END) AS selesai,
                 COUNT(*) AS total",
                false
            )->get()->getRowArray() ?? ['selesai' => 0, 'total' => 0];

            $selesai = (int) $row['selesai'];
            $draft = max(0, (int) $row['total'] - $selesai);

            return [
                'draft' => $draft,
                'selesai' => $selesai,
            ];
        }

        // progress >= 100 dianggap selesai
        if ($statusCol === 'progress') {
            $b = $this->db->table($table);
            $apply($b);

            $row = $b->select(
                "SUM(CASE WHEN progress >= 100 THEN 1 ELSE 0 END) AS selesai,
                 COUNT(*) AS total",
                false
            )->get()->getRowArray() ?? ['selesai' => 0, 'total' => 0];

            $selesai = (int) $row['selesai'];
            $draft = max(0, (int) $row['total'] - $selesai);

            return [
                'draft' => $draft,
                'selesai' => $selesai,
            ];
        }

        // fallback: tidak ada kolom status -> semua dianggap selesai
        $b = $this->db->table($table);
        $apply($b);
        $total = (int) $b->countAllResults();

        return ['draft' => 0, 'selesai' => $total];
    }

    /* ===== Helper schema ===== */

    private function getFields(string $table): array
    {
        try {
            return $this->db->getFieldNames($table) ?? [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    private function firstExistingCol(string $table, array $candidates): ?string
    {
        $fieldsLower = array_map('strtolower', $this->getFields($table));

        foreach ($candidates as $c) {
            if (in_array(strtolower($c), $fieldsLower, true)) {
                return $c;
            }
        }

        return null;
    }

    private function hasCols(string $table, array $cols): bool
    {
        $fieldsLower = array_map('strtolower', $this->getFields($table));
        foreach ($cols as $c) {
            if (!in_array(strtolower($c), $fieldsLower, true)) {
                return false;
            }
        }
        return true;
    }

    /* ======== KHUSUS IKU (relasi ke RENSTRA) ======== */

    /**
     * IKU:
     *  - tabel: iku (status enum: 'belum','tercapai')
     *  - FK: iku.renstra_id -> renstra_sasaran.id
     *  - Filter OPD & tahun via renstra_sasaran
     */
    private function countIku(?int $opdId, ?int $year): array
    {
        $ikuTable = $this->getModuleTable('iku', $this->IkuModel);
        if (!$ikuTable || !$this->db->tableExists($ikuTable)) {
            return ['tercapai' => 0, 'belum' => 0];
        }

        $b = $this->db->table($ikuTable . ' i');

        // join ke renstra_sasaran untuk filter opd & tahun
        $renstraTable = $this->getModuleTable('renstra', $this->RenstraModel) ?? 'renstra_sasaran';
        if ($this->db->tableExists($renstraTable)) {
            $b->join($renstraTable . ' rs', 'rs.id = i.renstra_id', 'left');

            if ($opdId) {
                $opdCol = $this->firstExistingCol($renstraTable, ['opd_id', 'id_opd']);
                if ($opdCol) {
                    $b->where('rs.' . $opdCol, $opdId);
                }
            }

            if ($year) {
                if ($this->hasCols($renstraTable, ['tahun_mulai', 'tahun_akhir'])) {
                    $b->groupStart()
                        ->where('rs.tahun_mulai <=', $year)
                        ->where('rs.tahun_akhir >=', $year)
                        ->groupEnd();
                } else {
                    $yCol = $this->firstExistingCol($renstraTable, ['tahun', 'tahun_anggaran']);
                    if ($yCol) {
                        $b->where('rs.' . $yCol, $year);
                    }
                }
            }
        }

        $row = $b->select(
            "SUM(CASE WHEN LOWER(i.status)='tercapai' THEN 1 ELSE 0 END) AS tercapai,
             SUM(CASE WHEN LOWER(i.status)='belum'    THEN 1 ELSE 0 END) AS belum",
            false
        )->get()->getRowArray() ?? ['tercapai' => 0, 'belum' => 0];

        return [
            'tercapai' => (int) $row['tercapai'],
            'belum' => (int) $row['belum'],
        ];
    }

    /* ======== KHUSUS LAKIP (relasi indikator) ======== */

    /**
     * LAKIP Kabupaten:
     *  - FK: lakip.rpjmd_indikator_id -> rpjmd_indikator_sasaran.id
     *  - (Filter tambahan bisa ditambah via join ke tabel RPJMD bila diperlukan)
     */
    private function countLakipKabupaten(?int $opdId, ?int $year): array
    {
        $lakipTable = $this->getModuleTable('lakip_kabupaten', $this->LakipKpdModel);
        if (!$lakipTable || !$this->db->tableExists($lakipTable)) {
            return ['proses' => 0, 'siap' => 0];
        }

        // hanya baris LAKIP Kabupaten (yang punya rpjmd_indikator_id)
        $builder = $this->db->table($lakipTable . ' lk')
            ->where('lk.rpjmd_indikator_id IS NOT NULL', null, false);

        // kalau mau, di sini bisa ditambah join ke tabel RPJMD untuk filter tahun/opd

        $statusCol = $this->firstExistingCol($lakipTable, ['status', 'is_final', 'progress']);

        return $this->countLakipStatusFromBuilder($builder, $statusCol, 'lk');
    }

    /**
     * LAKIP OPD:
     *  - FK: lakip.renstra_indikator_id -> renstra_indikator_sasaran.id
     *  - renstra_indikator_sasaran.renstra_sasaran_id -> renstra_sasaran.id
     *  - Filter OPD & tahun via renstra_sasaran
     */
    private function countLakipOpd(?int $opdId, ?int $year): array
    {
        $lakipTable = $this->getModuleTable('lakip_opd', $this->LakipOpdModel);
        if (!$lakipTable || !$this->db->tableExists($lakipTable)) {
            return ['proses' => 0, 'siap' => 0];
        }

        // hanya baris LAKIP OPD (yang punya renstra_indikator_id)
        $builder = $this->db->table($lakipTable . ' lk')
            ->where('lk.renstra_indikator_id IS NOT NULL', null, false);

        // tabel indikator renstra
        $indikatorTable = null;
        foreach (['renstra_indikator_sasaran', 'opd_renstra_indikator_sasaran'] as $t) {
            if ($this->db->tableExists($t)) {
                $indikatorTable = $t;
                break;
            }
        }

        if ($indikatorTable) {
            $builder->join($indikatorTable . ' ind', 'ind.id = lk.renstra_indikator_id', 'left');

            // join ke renstra_sasaran
            $renstraTable = $this->getModuleTable('renstra', $this->RenstraModel) ?? 'renstra_sasaran';
            if ($this->db->tableExists($renstraTable)) {
                $builder->join($renstraTable . ' rs', 'rs.id = ind.renstra_sasaran_id', 'left');

                if ($opdId) {
                    $opdCol = $this->firstExistingCol($renstraTable, ['opd_id', 'id_opd']);
                    if ($opdCol) {
                        $builder->where('rs.' . $opdCol, $opdId);
                    }
                }

                if ($year) {
                    if ($this->hasCols($renstraTable, ['tahun_mulai', 'tahun_akhir'])) {
                        $builder->groupStart()
                            ->where('rs.tahun_mulai <=', $year)
                            ->where('rs.tahun_akhir >=', $year)
                            ->groupEnd();
                    } else {
                        $yCol = $this->firstExistingCol($renstraTable, ['tahun', 'tahun_anggaran']);
                        if ($yCol) {
                            $builder->where('rs.' . $yCol, $year);
                        }
                    }
                }
            }
        }

        $statusCol = $this->firstExistingCol($lakipTable, ['status', 'is_final', 'progress']);

        return $this->countLakipStatusFromBuilder($builder, $statusCol, 'lk');
    }

    /**
     * Helper kecil untuk hitung status di LAKIP (kabupaten / opd)
     * Menghasilkan array: ['proses' => x, 'siap' => y]
     */
    private function countLakipStatusFromBuilder($builder, ?string $statusCol, string $alias = 'lk'): array
    {
        // status = teks 'proses' / 'siap'
        if ($statusCol === 'status') {
            $row = $builder->select(
                "SUM(CASE WHEN LOWER({$alias}.status)='siap'   THEN 1 ELSE 0 END) AS siap,
                 SUM(CASE WHEN LOWER({$alias}.status)='proses' THEN 1 ELSE 0 END) AS proses",
                false
            )->get()->getRowArray() ?? ['proses' => 0, 'siap' => 0];

            return [
                'proses' => (int) $row['proses'],
                'siap' => (int) $row['siap'],
            ];
        }

        // is_final = 1 -> siap
        if ($statusCol === 'is_final') {
            $row = $builder->select(
                "SUM(CASE WHEN {$alias}.is_final=1 THEN 1 ELSE 0 END) AS siap,
                 COUNT(*) AS total",
                false
            )->get()->getRowArray() ?? ['siap' => 0, 'total' => 0];

            $siap = (int) $row['siap'];
            $proses = max(0, (int) $row['total'] - $siap);

            return [
                'proses' => $proses,
                'siap' => $siap,
            ];
        }

        // progress >= 100 -> siap
        if ($statusCol === 'progress') {
            $row = $builder->select(
                "SUM(CASE WHEN {$alias}.progress >= 100 THEN 1 ELSE 0 END) AS siap,
                 COUNT(*) AS total",
                false
            )->get()->getRowArray() ?? ['siap' => 0, 'total' => 0];

            $siap = (int) $row['siap'];
            $proses = max(0, (int) $row['total'] - $siap);

            return [
                'proses' => $proses,
                'siap' => $siap,
            ];
        }

        // fallback: tidak ada kolom status
        $row = $builder->select('COUNT(*) AS total')->get()->getRowArray() ?? ['total' => 0];

        return [
            'proses' => 0,
            'siap' => (int) $row['total'],
        ];
    }

    /* ---------------- Placeholder halaman lain ---------------- */

    public function pk_bupati()
    {
        return view('adminKabupaten/pk_bupati/pk_bupati');
    }

    public function tambah_pk_bupati()
    {
        return view('adminKabupaten/pk_bupati/tambah_pk_bupati');
    }

    public function edit_pk_bupati()
    {
        return view('adminKabupaten/pk_bupati/edit_pk_bupati');
    }

    public function save_pk_bupati()
    {
        return redirect()->to(base_url('adminkab/pk_bupati'));
    }

    public function lakip_kabupaten()
    {
        return view('adminKabupaten/lakip_kabupaten/lakip_kabupaten');
    }

    public function tambah_lakip_kabupaten()
    {
        return view('adminKabupaten/lakip_kabupaten/tambah_lakip_kabupaten');
    }

    public function edit_lakip_kabupaten()
    {
        return view('adminKabupaten/lakip_kabupaten/edit_lakip_kabupaten');
    }

    public function save_lakip_kabupaten()
    {
        return redirect()->to(base_url('adminkab/lakip_kabupaten'));
    }

    public function tentang_kami()
    {
        return view('adminKabupaten/tentang_kami');
    }

    public function edit_tentang_kami()
    {
        return view('adminKabupaten/edit_tentang_kami');
    }

    public function save_tentang_kami()
    {
        return redirect()->to(base_url('adminkab/tentang_kami'));
    }
}
