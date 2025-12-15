<?php

namespace App\Models;

class DashboardKabupatenModel
{
    protected $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }

    // =========================================================
    // PUBLIC
    // =========================================================
    public function getDashboardData($opdId = null, $year = null): array
    {
        $opdId = ($opdId === '' || $opdId === null) ? null : (int) $opdId;
        $year = ($year === '' || $year === null) ? null : (int) $year;

        $data = [
            'rpjmd' => $this->countRpjmd(),                 // tidak difilter opd/year
            'rkpd' => $this->countRkpdFromRkt($year),      // RKPD = kumpulan RKT (per tahun)
            'renstra' => $this->countRenstra($opdId, $year),
            'rkt' => $this->countRkt($opdId, $year),
            'iku' => $this->countIku($opdId, $year),
            'lakip_kabupaten' => $this->countLakipKabupaten($year),
            'lakip_opd' => $this->countLakipOpd($opdId, $year),

            // untuk view awal (dropdown)
            'opd_list' => $this->getOpdList(),
            'available_years' => $this->getAvailableYears(),
        ];

        return $data;
    }

    public function getSummaryStats(): array
    {
        return [
            'total_rpjmd' => $this->totalRpjmd(),
            'total_renstra' => $this->totalRenstra(),
            'total_rkt' => $this->totalRkt(),
            'total_opd' => $this->totalOpd(),
        ];
    }

    // =========================================================
    // DROPDOWN
    // =========================================================
    private function getOpdList(): array
    {
        if (!$this->tableExists('opd'))
            return [];

        return $this->db->table('opd')
            ->select('id, nama_opd')
            ->orderBy('nama_opd', 'ASC')
            ->get()
            ->getResultArray();
    }

    private function getAvailableYears(): array
    {
        $years = [];

        // prioritas: renstra_target, rpjmd_target, rkt
        if ($this->tableExists('renstra_target') && $this->hasColumn('renstra_target', 'tahun')) {
            $rows = $this->db->table('renstra_target')->select('DISTINCT tahun', false)->orderBy('tahun', 'DESC')->get()->getResultArray();
            foreach ($rows as $r)
                $years[(int) $r['tahun']] = true;
        }

        if ($this->tableExists('rpjmd_target') && $this->hasColumn('rpjmd_target', 'tahun')) {
            $rows = $this->db->table('rpjmd_target')->select('DISTINCT tahun', false)->orderBy('tahun', 'DESC')->get()->getResultArray();
            foreach ($rows as $r)
                $years[(int) $r['tahun']] = true;
        }

        if ($this->tableExists('rkt') && $this->hasColumn('rkt', 'tahun')) {
            $rows = $this->db->table('rkt')->select('DISTINCT tahun', false)->orderBy('tahun', 'DESC')->get()->getResultArray();
            foreach ($rows as $r)
                $years[(int) $r['tahun']] = true;
        }

        $out = array_keys($years);
        rsort($out);

        if (empty($out)) {
            $now = (int) date('Y');
            $out = range($now - 5, $now + 1);
            rsort($out);
        }

        return $out;
    }

    // =========================================================
    // COUNTERS
    // =========================================================

    // --- RPJMD (tidak difilter) ---
    private function countRpjmd(): array
    {
        // kamu punya typo: rpjmd_sasasran
        $table = $this->tableExists('rpjmd_sasaran') ? 'rpjmd_sasaran'
            : ($this->tableExists('rpjmd_sasasran') ? 'rpjmd_sasasran' : null);

        if (!$table)
            return ['draft' => 0, 'selesai' => 0];

        return $this->countDraftSelesai($this->db->table($table));
    }

    private function totalRpjmd(): int
    {
        $table = $this->tableExists('rpjmd_sasaran') ? 'rpjmd_sasaran'
            : ($this->tableExists('rpjmd_sasasran') ? 'rpjmd_sasasran' : null);

        if (!$table)
            return 0;

        return (int) $this->db->table($table)->countAllResults();
    }

    // --- RKPD = KUMPULAN RKT (ringkas) ---
    private function countRkpdFromRkt(?int $year): array
    {
        if (!$this->tableExists('rkt'))
            return ['draft' => 0, 'selesai' => 0];

        $b = $this->db->table('rkt');

        // RKPD per tahun
        if ($year && $this->hasColumn('rkt', 'tahun')) {
            $b->where('tahun', $year);
        }

        return $this->countDraftSelesai($b);
    }

    // --- RENSTRA (filter opd_id + year) ---
    private function countRenstra(?int $opdId, ?int $year): array
    {
        if (!$this->tableExists('renstra_sasaran'))
            return ['draft' => 0, 'selesai' => 0];

        $b = $this->db->table('renstra_sasaran');

        if ($opdId && $this->hasColumn('renstra_sasaran', 'opd_id')) {
            $b->where('opd_id', $opdId);
        }

        // filter tahun RENSTRA biasanya pakai range
        if ($year) {
            if ($this->hasColumn('renstra_sasaran', 'tahun_mulai') && $this->hasColumn('renstra_sasaran', 'tahun_akhir')) {
                $b->groupStart()
                    ->where('tahun_mulai <=', $year)
                    ->where('tahun_akhir >=', $year)
                    ->groupEnd();
            } elseif ($this->hasColumn('renstra_sasaran', 'tahun')) {
                $b->where('tahun', $year);
            }
        }

        return $this->countDraftSelesai($b);
    }

    private function totalRenstra(): int
    {
        if (!$this->tableExists('renstra_sasaran'))
            return 0;
        return (int) $this->db->table('renstra_sasaran')->countAllResults();
    }

    // --- RKT (filter opd_id + year) ---
    private function countRkt(?int $opdId, ?int $year): array
    {
        if (!$this->tableExists('rkt'))
            return ['draft' => 0, 'selesai' => 0];

        $b = $this->db->table('rkt');

        if ($opdId && $this->hasColumn('rkt', 'opd_id')) {
            $b->where('opd_id', $opdId);
        }

        if ($year && $this->hasColumn('rkt', 'tahun')) {
            $b->where('tahun', $year);
        }

        return $this->countDraftSelesai($b);
    }

    private function totalRkt(): int
    {
        if (!$this->tableExists('rkt'))
            return 0;
        return (int) $this->db->table('rkt')->countAllResults();
    }

    private function totalOpd(): int
    {
        if (!$this->tableExists('opd'))
            return 0;
        return (int) $this->db->table('opd')->countAllResults();
    }

    // --- IKU (filter via renstra_indikator_sasaran -> renstra_sasaran) ---
    private function countIku(?int $opdId, ?int $year): array
    {
        if (!$this->tableExists('iku'))
            return ['tercapai' => 0, 'belum' => 0];

        $b = $this->db->table('iku i');

        // join indikator & sasaran renstra untuk filter opd/tahun
        if ($this->tableExists('renstra_indikator_sasaran')) {
            $b->join('renstra_indikator_sasaran ris', 'ris.id = i.renstra_id', 'left');
            if ($this->tableExists('renstra_sasaran')) {
                $b->join('renstra_sasaran rs', 'rs.id = ris.renstra_sasaran_id', 'left');

                if ($opdId && $this->hasColumn('renstra_sasaran', 'opd_id')) {
                    $b->where('rs.opd_id', $opdId);
                }

                if ($year) {
                    if ($this->hasColumn('renstra_sasaran', 'tahun_mulai') && $this->hasColumn('renstra_sasaran', 'tahun_akhir')) {
                        $b->groupStart()
                            ->where('rs.tahun_mulai <=', $year)
                            ->where('rs.tahun_akhir >=', $year)
                            ->groupEnd();
                    } elseif ($this->hasColumn('renstra_sasaran', 'tahun')) {
                        $b->where('rs.tahun', $year);
                    }
                }
            }
        } else {
            // fallback: kalau belum ada tabel join, minimal filter langsung (kalau ada)
            if ($opdId && $this->hasColumn('iku', 'opd_id'))
                $b->where('i.opd_id', $opdId);
            if ($year && $this->hasColumn('iku', 'tahun'))
                $b->where('i.tahun', $year);
        }

        $row = $b->select("
                SUM(CASE WHEN LOWER(i.status)='tercapai' THEN 1 ELSE 0 END) AS tercapai,
                SUM(CASE WHEN LOWER(i.status)='belum' THEN 1 ELSE 0 END) AS belum
            ", false)->get()->getRowArray();

        return [
            'tercapai' => (int) ($row['tercapai'] ?? 0),
            'belum' => (int) ($row['belum'] ?? 0),
        ];
    }

    // --- LAKIP OPD (lakip -> renstra_target -> renstra_indikator_sasaran -> renstra_sasaran) ---
    private function countLakipOpd(?int $opdId, ?int $year): array
    {
        if (!$this->tableExists('lakip'))
            return ['proses' => 0, 'siap' => 0];
        if (!$this->tableExists('renstra_target'))
            return ['proses' => 0, 'siap' => 0];

        $b = $this->db->table('lakip l')
            ->join('renstra_target rt', 'rt.id = l.renstra_target_id', 'left')
            ->where('l.renstra_target_id IS NOT NULL', null, false);

        if ($this->tableExists('renstra_indikator_sasaran')) {
            $b->join('renstra_indikator_sasaran ris', 'ris.id = rt.renstra_indikator_id', 'left');
        }
        if ($this->tableExists('renstra_sasaran')) {
            $b->join('renstra_sasaran rs', 'rs.id = ris.renstra_sasaran_id', 'left');

            if ($opdId && $this->hasColumn('renstra_sasaran', 'opd_id')) {
                $b->where('rs.opd_id', $opdId);
            }
        }

        if ($year && $this->hasColumn('renstra_target', 'tahun')) {
            $b->where('rt.tahun', $year);
        }

        return $this->countProsesSiap($b, 'l.status');
    }

    // --- LAKIP KABUPATEN (lakip -> rpjmd_target) ---
    private function countLakipKabupaten(?int $year): array
    {
        if (!$this->tableExists('lakip'))
            return ['proses' => 0, 'siap' => 0];
        if (!$this->tableExists('rpjmd_target'))
            return ['proses' => 0, 'siap' => 0];

        $b = $this->db->table('lakip l')
            ->join('rpjmd_target rpj', 'rpj.id = l.rpjmd_target_id', 'left')
            ->where('l.rpjmd_target_id IS NOT NULL', null, false);

        if ($year && $this->hasColumn('rpjmd_target', 'tahun')) {
            $b->where('rpj.tahun', $year);
        }

        return $this->countProsesSiap($b, 'l.status');
    }

    // =========================================================
    // GENERIC HELPERS
    // =========================================================
    private function countDraftSelesai($builder): array
    {
        // status: draft/selesai
        $row = $builder->select("
            SUM(CASE WHEN LOWER(status)='selesai' THEN 1 ELSE 0 END) AS selesai,
            SUM(CASE WHEN LOWER(status) IN ('draft','draf') THEN 1 ELSE 0 END) AS draft
        ", false)->get()->getRowArray();

        return [
            'draft' => (int) ($row['draft'] ?? 0),
            'selesai' => (int) ($row['selesai'] ?? 0),
        ];
    }

    private function countProsesSiap($builder, string $statusField = 'status'): array
    {
        // status: proses/siap
        $row = $builder->select("
            SUM(CASE WHEN LOWER($statusField)='siap' THEN 1 ELSE 0 END) AS siap,
            SUM(CASE WHEN LOWER($statusField)='proses' THEN 1 ELSE 0 END) AS proses
        ", false)->get()->getRowArray();

        return [
            'proses' => (int) ($row['proses'] ?? 0),
            'siap' => (int) ($row['siap'] ?? 0),
        ];
    }

    private function tableExists(string $table): bool
    {
        try {
            return $this->db->tableExists($table);
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function hasColumn(string $table, string $col): bool
    {
        try {
            $fields = $this->db->getFieldNames($table) ?? [];
            $fields = array_map('strtolower', $fields);
            return in_array(strtolower($col), $fields, true);
        } catch (\Throwable $e) {
            return false;
        }
    }
}
