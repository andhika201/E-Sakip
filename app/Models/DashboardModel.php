<?php

namespace App\Models;

use CodeIgniter\Model;

class DashboardModel extends Model
{
    /** @var \CodeIgniter\Database\BaseConnection */
    protected $db;

    public function __construct()
    {
        parent::__construct();
        $this->db = \Config\Database::connect();
    }

    /* ========================= HELPERS ========================= */

    /**
     * Normalisasi hasil count per status menjadi ['draft' => x, 'selesai' => y]
     * Menggabungkan variasi status seperti 'draf' => 'draft', 'final/publish' => 'selesai'.
     */
    protected function normalizeStatusCounts(array $rows): array
    {
        $out = ['draft' => 0, 'selesai' => 0];

        foreach ($rows as $r) {
            $status = strtolower(trim((string) ($r['status'] ?? '')));
            $count = (int) ($r['count'] ?? 0);

            if (in_array($status, ['draft', 'draf'])) {
                $out['draft'] += $count;
            } elseif (in_array($status, ['selesai', 'final', 'published', 'publish', 'complete', 'completed'])) {
                $out['selesai'] += $count;
            } else {
                // jika status lain, masukkan ke draft biar tidak hilang
                $out['draft'] += $count;
            }
        }

        return $out;
    }

    protected function applyYearFilter($builder, ?string $year, string $column = 'created_at')
    {
        if (!empty($year)) {
            $builder->where("YEAR({$column})", (int) $year);
        }
        return $builder;
    }

    /* ========================= RPJMD / RKPD / RENSTRA / RENJA / IKU / LAKIP ========================= */

    /** Tabel asumsi: rpjmd_misi (kolom: status, tahun_mulai, tahun_akhir) */
    public function getRpjmdStats(?string $year = null): array
    {
        // Umumnya RPJMD berbasis periode; jika ingin filter per tahun spesifik, kamu bisa menambahkan:
        // ->where("'{$year}' BETWEEN tahun_mulai AND tahun_akhir")
        $q = $this->db->table('rpjmd_misi')
            ->select('status, COUNT(*) as count')
            ->groupBy('status');

        // jika mau pakai filter tahun berdasar rentang periode, buka komentar di bawah
        if (!empty($year)) {
            $q->where("'" . (int) $year . "' BETWEEN tahun_mulai AND tahun_akhir");
        }

        $rows = $q->get()->getResultArray();
        return $this->normalizeStatusCounts($rows);
    }

    /** Tabel asumsi: rkpd_sasaran (kolom: status, created_at) */
    public function getRkpdStats(?string $year = null): array
    {
        $q = $this->db->table('rkpd_sasaran')
            ->select('status, COUNT(*) as count')
            ->groupBy('status');

        $this->applyYearFilter($q, $year);
        $rows = $q->get()->getResultArray();
        return $this->normalizeStatusCounts($rows);
    }

    /** Tabel asumsi: renstra_sasaran (kolom: status, opd_id, created_at) */
    public function getRenstraStats(?int $opdId = null, ?string $year = null): array
    {
        $q = $this->db->table('renstra_sasaran rs')
            ->select('rs.status, COUNT(*) as count')
            ->groupBy('rs.status');

        if (!empty($opdId)) {
            $q->where('rs.opd_id', (int) $opdId);
        }
        $this->applyYearFilter($q, $year, 'rs.created_at');

        $rows = $q->get()->getResultArray();
        return $this->normalizeStatusCounts($rows);
    }

    /**
     * Tabel asumsi: renja_sasaran (kolom: status, created_at)
     * relasi ke OPD melalui renstra_sasaran (rs.opd_id)
     */
    public function getRenjaStats(?int $opdId = null, ?string $year = null): array
    {
        $q = $this->db->table('renja_sasaran rj')
            ->select('rj.status, COUNT(*) as count')
            ->join('renstra_sasaran rs', 'rs.id = rj.renstra_sasaran_id', 'left')
            ->groupBy('rj.status');

        if (!empty($opdId)) {
            $q->where('rs.opd_id', (int) $opdId);
        }
        $this->applyYearFilter($q, $year, 'rj.created_at');

        $rows = $q->get()->getResultArray();
        return $this->normalizeStatusCounts($rows);
    }

    /** Tabel asumsi: iku_sasaran (kolom: status, created_at). Jika perlu per-OPD, tambahkan kolom/relasi ke OPD. */
    public function getIkuStats(?string $year = null): array
    {
        $q = $this->db->table('iku_sasaran')
            ->select('status, COUNT(*) as count')
            ->groupBy('status');

        $this->applyYearFilter($q, $year);
        $rows = $q->get()->getResultArray();
        return $this->normalizeStatusCounts($rows);
    }

    /** Tabel asumsi: lakip_kabupaten (kolom: status, created_at) */
    public function getLakipKabupatenStats(?string $year = null): array
    {
        $q = $this->db->table('lakip_kabupaten')
            ->select('status, COUNT(*) as count')
            ->groupBy('status');

        $this->applyYearFilter($q, $year);
        $rows = $q->get()->getResultArray();
        return $this->normalizeStatusCounts($rows);
    }

    /** Tabel asumsi: lakip_opd (kolom: status, opd_id, created_at) */
    public function getLakipOpdStats(?int $opdId = null, ?string $year = null): array
    {
        $q = $this->db->table('lakip_opd lo')
            ->select('lo.status, COUNT(*) as count')
            ->groupBy('lo.status');

        if (!empty($opdId)) {
            $q->where('lo.opd_id', (int) $opdId);
        }
        $this->applyYearFilter($q, $year, 'lo.created_at');

        $rows = $q->get()->getResultArray();
        return $this->normalizeStatusCounts($rows);
    }

    /* ========================= OPD & YEARS ========================= */

    public function getAllOpd(): array
    {
        return $this->db->table('opd')
            ->select('id, nama_opd')
            ->orderBy('nama_opd', 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * Kumpulan tahun dari berbagai sumber, di-union & disortir DESC.
     * Sumber:
     * - RPJMD rentang tahun_mulai..tahun_akhir
     * - YEAR(created_at) dari sejumlah tabel (rkpd_sasaran, renstra_sasaran, renja_sasaran, lakip_opd, lakip_kabupaten, iku_sasaran)
     */
    public function getAvailableYears(): array
    {
        $years = [];

        // Rentang tahun dari RPJMD
        $rpjmd = $this->db->table('rpjmd_misi')->select('tahun_mulai, tahun_akhir')->get()->getResultArray();
        foreach ($rpjmd as $row) {
            $start = (int) ($row['tahun_mulai'] ?? 0);
            $end = (int) ($row['tahun_akhir'] ?? 0);
            if ($start > 0 && $end >= $start) {
                for ($y = $start; $y <= $end; $y++) {
                    $years[] = $y;
                }
            }
        }

        // Koleksi tahun dari created_at beberapa tabel (jika kolom ada)
        $tablesWithYear = [
            'rkpd_sasaran',
            'renstra_sasaran',
            'renja_sasaran',
            'lakip_opd',
            'lakip_kabupaten',
            'iku_sasaran',
        ];

        foreach ($tablesWithYear as $t) {
            $rows = $this->db->table($t)->select('YEAR(created_at) as year')->distinct()->get()->getResultArray();
            foreach ($rows as $r) {
                if (!empty($r['year'])) {
                    $years[] = (int) $r['year'];
                }
            }
        }

        $years = array_values(array_unique(array_filter($years)));
        rsort($years);

        // fallback jika kosong
        if (empty($years)) {
            $years = [date('Y')];
        }

        return $years;
    }

    /* ========================= AGGREGATOR ========================= */

    /**
     * Data lengkap tanpa filter (untuk load awal dashboard)
     */
    public function getDashboardData(): array
    {
        return [
            'rpjmd' => $this->getRpjmdStats(),                 // bisa ditambah year bila perlu
            'rkpd' => $this->getRkpdStats(),
            'renstra' => $this->getRenstraStats(),
            'renja' => $this->getRenjaStats(),
            'iku' => $this->getIkuStats(),
            'lakip_kabupaten' => $this->getLakipKabupatenStats(),
            'lakip_opd' => $this->getLakipOpdStats(),
            'opd_list' => $this->getAllOpd(),
            'available_years' => $this->getAvailableYears(),
        ];
    }

    /**
     * Data dashboard ter-filter.
     * - Jika $opdId diset: RENSTRA/RENJA/LAKIP OPD akan difilter OPD.
     * - Jika $year diset: akan diterapkan ke tabel yang punya created_at / rentang tahun.
     * - Kunci lain (RPJMD/RKPD/IKU/LAKIP KAB) tetap dikembalikan agar grafik tidak kosong;
     *   jika $year diberikan, akan difilter seperlunya (created_at / rentang).
     */
    public function getDashboardDataByOpdAndYear(?int $opdId = null, ?string $year = null): array
    {
        // Stats global (tetap ada supaya grafik tidak kosong saat filter OPD)
        $data = [
            'rpjmd' => $this->getRpjmdStats($year),    // filter berdasar rentang periode (lihat fungsi)
            'rkpd' => $this->getRkpdStats($year),
            'iku' => $this->getIkuStats($year),
            'lakip_kabupaten' => $this->getLakipKabupatenStats($year),
            // yang di bawah ini akan disesuaikan OPD/Year:
            'renstra' => $this->getRenstraStats($opdId, $year),
            'renja' => $this->getRenjaStats($opdId, $year),
            'lakip_opd' => $this->getLakipOpdStats($opdId, $year),
        ];

        // Elemen pendukung untuk dropdown filter
        $data['opd_list'] = $this->getAllOpd();
        $data['available_years'] = $this->getAvailableYears();

        return $data;
    }

    /**
     * Ringkasan untuk widget angka-angka atas.
     */
    public function getSummaryStats(): array
    {
        return [
            'total_rpjmd' => $this->db->table('rpjmd_misi')->countAllResults(),
            'total_rkpd' => $this->db->table('rkpd_sasaran')->countAllResults(),
            'total_renstra' => $this->db->table('renstra_sasaran')->countAllResults(),
            'total_renja' => $this->db->table('renja_sasaran')->countAllResults(),
            'total_opd' => $this->db->table('opd')->countAllResults(),
            'active_year' => date('Y'),
        ];
    }
}
