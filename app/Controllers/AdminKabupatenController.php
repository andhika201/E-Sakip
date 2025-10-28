<?php

namespace App\Controllers;

use App\Controllers\BaseController;

use App\Models\RpjmdModel;
use App\Models\RkpdModel;
use App\Models\LakipKabupatenModel;
use App\Models\Opd\RenstraModel;
use App\Models\OpdModel;
use App\Models\Opd\IkuModel;
use App\Models\Opd\LakipOpdModel;

class AdminKabupatenController extends BaseController
{
    protected $RpjmdModel;
    protected $RkpdModel;
    protected $LakipKpdModel;
    protected $RenstraModel;
    protected $RktModel;          // bisa \App\Models\Opd\RktModel atau \App\Models\RktModel
    protected $OpdModel;
    protected $IkuModel;
    protected $LakipOpdModel;

    protected $db;

    /** Override tabel per modul (akan dipakai kalau tabelnya ada). Urutan = prioritas. */
    protected array $tableMap = [
        'rpjmd' => ['rpjmd_sasasran', 'rpjmd_sasaran'],        // dukung typo "sasasran" & nama normal
        'renstra' => ['renstra_sasaran', 'opd_renstra_sasaran'], // alternatif umum di beberapa skema
        // modul lain pakai tabel bawaannya dari model
    ];

    public function __construct()
    {
        $this->db = \Config\Database::connect();

        $this->RpjmdModel = class_exists(\App\Models\RpjmdModel::class) ? new RpjmdModel() : null;
        $this->RkpdModel = class_exists(\App\Models\RkpdModel::class) ? new RkpdModel() : null;
        $this->LakipKpdModel = class_exists(\App\Models\LakipKabupatenModel::class) ? new LakipKabupatenModel() : null;
        $this->RenstraModel = class_exists(\App\Models\Opd\RenstraModel::class) ? new RenstraModel() : null;
        $this->OpdModel = class_exists(\App\Models\OpdModel::class) ? new OpdModel() : null;
        $this->IkuModel = class_exists(\App\Models\Opd\IkuModel::class) ? new IkuModel() : null;
        $this->LakipOpdModel = class_exists(\App\Models\Opd\LakipOpdModel::class) ? new LakipOpdModel() : null;

        if (class_exists(\App\Models\RktModel::class)) {
            $this->RktModel = new \App\Models\RktModel();
        } elseif (class_exists(\App\Models\RktModel::class)) {
            $this->RktModel = new \App\Models\RktModel();
        } else {
            $this->RktModel = null;
        }
    }

    /** GET /adminkab/dashboard */
    public function index()
    {
        try {
            $opdId = $this->request->getGet('opd_id');
            $year = $this->request->getGet('year');

            $opdList = $this->getOpdList();
            $availableYears = $this->getAvailableYears();
            if (!$year && $availableYears)
                $year = max($availableYears);

            $data = [
                'dashboard_data' => [
                    'rpjmd' => $this->countFromModel('rpjmd', $this->RpjmdModel, $opdId, $year),
                    'rkpd' => $this->countFromModel('rkpd', $this->RkpdModel, $opdId, $year),
                    'renstra' => $this->countFromModel('renstra', $this->RenstraModel, $opdId, $year),
                    'rkt' => $this->countFromModel('rkt', $this->RktModel, $opdId, $year),
                    'iku' => $this->countFromModel('iku', $this->IkuModel, $opdId, $year),
                    'lakip_kabupaten' => $this->countFromModel('lakip_kabupaten', $this->LakipKpdModel, null, $year, ['scope' => 'kabupaten']),
                    'lakip_opd' => $this->countFromModel('lakip_opd', $this->LakipOpdModel, $opdId, $year, ['scope' => 'opd']),
                    'opd_list' => $opdList,
                    'available_years' => $availableYears,
                ],
                'summary_stats' => [
                    'total_rpjmd' => $this->totalRows('rpjmd', $this->RpjmdModel, $opdId, $year),
                    'total_renstra' => $this->totalRows('renstra', $this->RenstraModel, $opdId, $year),
                    'total_rkt' => $this->totalRows('rkt', $this->RktModel, $opdId, $year),
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
                    'iku' => ['draft' => 0, 'selesai' => 0],
                    'lakip_kabupaten' => ['draft' => 0, 'selesai' => 0],
                    'lakip_opd' => ['draft' => 0, 'selesai' => 0],
                    'opd_list' => [],
                    'available_years' => [],
                ],
                'summary_stats' => ['total_rpjmd' => 0, 'total_renstra' => 0, 'total_rkt' => 0, 'total_opd' => 0],
                'error_message' => 'Terjadi kesalahan saat memuat data.',
            ]);
        }
    }

    /** POST /adminkab/getDashboardData (AJAX “Tampilkan”) */
    public function getDashboardData()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Invalid request'])->setStatusCode(400);
        }
        try {
            $opdId = trim((string) $this->request->getPost('opd_id'));
            $year = trim((string) $this->request->getPost('year'));
            $opdId = $opdId !== '' ? (int) $opdId : null;
            $year = $year !== '' ? (int) $year : null;

            $data = [
                'rpjmd' => $this->countFromModel('rpjmd', $this->RpjmdModel, $opdId, $year),
                'rkpd' => $this->countFromModel('rkpd', $this->RkpdModel, $opdId, $year),
                'renstra' => $this->countFromModel('renstra', $this->RenstraModel, $opdId, $year),
                'rkt' => $this->countFromModel('rkt', $this->RktModel, $opdId, $year),
                'iku' => $this->countFromModel('iku', $this->IkuModel, $opdId, $year),
                'lakip_kabupaten' => $this->countFromModel('lakip_kabupaten', $this->LakipKpdModel, null, $year, ['scope' => 'kabupaten']),
                'lakip_opd' => $this->countFromModel('lakip_opd', $this->LakipOpdModel, $opdId, $year, ['scope' => 'opd']),
            ];

            return $this->response->setJSON(['status' => 'success', 'data' => $data]);
        } catch (\Throwable $e) {
            log_message('error', 'getDashboardData error: ' . $e->getMessage());
            return $this->response->setJSON(['status' => 'error', 'message' => 'Gagal memuat data'])->setStatusCode(500);
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
                if ($t && $this->db->tableExists($t))
                    return $t;
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
        if (!$table || !$this->db->tableExists($table))
            return [];
        $name = $this->firstExistingCol($table, ['nama_opd', 'nama', 'name']) ?? 'nama_opd';
        return $this->db->table($table)->select("id, {$name} AS nama_opd")->orderBy($name, 'ASC')->get()->getResultArray();
    }

    private function getAvailableYears(): array
    {
        $candidates = [
            $this->getModuleTable('rpjmd', $this->RpjmdModel),
            $this->getModuleTable('rkpd', $this->RkpdModel),
            $this->getModuleTable('renstra', $this->RenstraModel),
            $this->getModuleTable('rkt', $this->RktModel),
            $this->getModuleTable('iku', $this->IkuModel),
            $this->getModuleTable('lakip_kabupaten', $this->LakipKpdModel),
            $this->getModuleTable('lakip_opd', $this->LakipOpdModel),
        ];

        $years = [];
        foreach ($candidates as $t) {
            if (!$t || !$this->db->tableExists($t))
                continue;

            if ($this->hasCols($t, ['tahun_mulai', 'tahun_selesai'])) {
                $row = $this->db->table($t)->select('MIN(tahun_mulai) mn, MAX(tahun_selesai) mx')->get()->getRowArray();
                if ($row && (int) $row['mn'] && (int) $row['mx'] && (int) $row['mx'] >= (int) $row['mn'] && (int) $row['mx'] - (int) $row['mn'] <= 10) {
                    for ($y = (int) $row['mn']; $y <= (int) $row['mx']; $y++)
                        $years[$y] = true;
                    continue;
                }
            }

            $col = $this->firstExistingCol($t, ['tahun', 'tahun_anggaran']);
            if ($col) {
                foreach ($this->db->table($t)->select("$col y")->groupBy('y')->get()->getResultArray() as $r) {
                    $v = (int) ($r['y'] ?? 0);
                    if ($v)
                        $years[$v] = true;
                }
            }
        }

        $out = array_keys($years);
        rsort($out);
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
            // Tahun
            if ($year) {
                if ($key === 'renstra' && $this->hasCols($table, ['tahun_mulai', 'tahun_selesai'])) {
                    $b->groupStart()->where('tahun_mulai <=', $year)->where('tahun_selesai >=', $year)->groupEnd();
                } else {
                    $y = $this->firstExistingCol($table, ['tahun', 'tahun_anggaran']);
                    if ($y)
                        $b->where($y, $year);
                }
            }
            // OPD scope
            if (($opts['scope'] ?? '') === 'kabupaten') {
                $opdCol = $this->firstExistingCol($table, ['opd_id', 'id_opd']);
                if ($opdCol)
                    $b->groupStart()->where($opdCol, 0)->orWhere("$opdCol IS NULL", null, false)->groupEnd();
            } elseif (($opts['scope'] ?? '') === 'opd') {
                $opdCol = $this->firstExistingCol($table, ['opd_id', 'id_opd']);
                if ($opdCol) {
                    if ($opdId)
                        $b->where($opdCol, $opdId);
                    else
                        $b->where("$opdCol >", 0);
                }
            } else {
                $opdCol = $this->firstExistingCol($table, ['opd_id', 'id_opd']);
                if ($opdCol && $opdId)
                    $b->where($opdCol, $opdId);
            }
        };
    }

    private function countFromModel(string $moduleKey, $model, ?int $opdId, ?int $year, array $opts = []): array
    {
        $table = $this->getModuleTable($moduleKey, $model);
        if (!$table || !$this->db->tableExists($table))
            return ['draft' => 0, 'selesai' => 0];

        $apply = $this->makeFilter($table, $moduleKey, $opdId, $year, $opts);
        return $this->countByStatus($table, $apply);
    }

    private function totalRows(string $moduleKey, $model, ?int $opdId, ?int $year): int
    {
        $table = $this->getModuleTable($moduleKey, $model);
        if (!$table || !$this->db->tableExists($table))
            return 0;

        $apply = $this->makeFilter($table, $moduleKey, $opdId, $year, []);
        $b = $this->db->table($table);
        $apply($b);
        return (int) $b->countAllResults();
    }

    private function countByStatus(string $table, \Closure $apply): array
    {
        $statusCol = $this->firstExistingCol($table, ['status', 'is_final', 'progress']);

        if ($statusCol === 'status') {
            $b = $this->db->table($table);
            $apply($b);
            $row = $b->select(
                "SUM(CASE WHEN LOWER(status)='selesai' THEN 1 ELSE 0 END) AS selesai,
                 SUM(CASE WHEN LOWER(status) IN ('draft','draf') THEN 1 ELSE 0 END) AS draft",
                false
            )->get()->getRowArray() ?? ['draft' => 0, 'selesai' => 0];
            return ['draft' => (int) $row['draft'], 'selesai' => (int) $row['selesai']];
        }

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
            return ['draft' => $draft, 'selesai' => $selesai];
        }

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
            return ['draft' => $draft, 'selesai' => $selesai];
        }

        // fallback: tidak ada kolom status -> semua dianggap selesai
        $b = $this->db->table($table);
        $apply($b);
        $total = (int) $b->countAllResults();
        return ['draft' => 0, 'selesai' => $total];
    }

    /* ===== util schema ===== */

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
        $fields = array_map('strtolower', $this->getFields($table));
        foreach ($candidates as $c)
            if (in_array(strtolower($c), $fields, true))
                return $c;
        return null;
    }

    private function hasCols(string $table, array $cols): bool
    {
        $fields = array_map('strtolower', $this->getFields($table));
        foreach ($cols as $c)
            if (!in_array(strtolower($c), $fields, true))
                return false;
        return true;
    }
    /* ---------------- Placeholder halaman lain (boleh kamu lanjutkan) ---------------- */

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
