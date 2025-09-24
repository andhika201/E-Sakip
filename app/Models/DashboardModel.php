<?php

namespace App\Models;

use CodeIgniter\Model;

class DashboardModel extends Model
{
    protected $db;
    
    public function __construct()
    {
        parent::__construct();
        $this->db = \Config\Database::connect();
    }

    /**
     * Get RPJMD statistics by status
     */
    public function getRpjmdStats()
    {
        $stats = $this->db->table('rpjmd_misi')
            ->select('status, COUNT(*) as count')
            ->groupBy('status')
            ->get()
            ->getResultArray();
        
        $result = ['draft' => 0, 'selesai' => 0];
        foreach ($stats as $stat) {
            $result[$stat['status']] = $stat['count'];
        }
        
        return $result;
    }

    /**
     * Get RKPD statistics by status
     */
    public function getRkpdStats()
    {
        $stats = $this->db->table('rkpd_sasaran')
            ->select('status, COUNT(*) as count')
            ->groupBy('status')
            ->get()
            ->getResultArray();
        
        $result = ['draft' => 0, 'selesai' => 0];
        foreach ($stats as $stat) {
            $result[$stat['status']] = $stat['count'];
        }
        
        return $result;
    }

    /**
     * Get RENSTRA statistics by status
     */
    public function getRenstraStats()
    {
        $stats = $this->db->table('renstra_sasaran')
            ->select('status, COUNT(*) as count')
            ->groupBy('status')
            ->get()
            ->getResultArray();
        
        $result = ['draft' => 0, 'selesai' => 0];
        foreach ($stats as $stat) {
            $result[$stat['status']] = $stat['count'];
        }
        
        return $result;
    }

    /**
     * Get RENJA statistics by status
     */
    public function getRenjaStats()
    {
        $stats = $this->db->table('renja_sasaran')
            ->select('status, COUNT(*) as count')
            ->groupBy('status')
            ->get()
            ->getResultArray();
        
        $result = ['draft' => 0, 'selesai' => 0];
        foreach ($stats as $stat) {
            $result[$stat['status']] = $stat['count'];
        }
        
        return $result;
    }

    /**
     * Get IKU statistics by status for current year
     */
    public function getIkuStats()
    {
        // Assuming IKU data is stored in a table, adjust table name as needed
        $stats = $this->db->table('iku_sasaran') 
            ->select('status, COUNT(*) as count')
            ->groupBy('status')
            ->get()
            ->getResultArray();
        
        $result = ['draft' => 0, 'selesai' => 0];
        foreach ($stats as $stat) {
            $result[$stat['status']] = $stat['count'];
        }
        
        return $result;
    }

    /**
     * Get LAKIP Kabupaten statistics by status
     */
    public function getLakipKabupatenStats()
    {
        // Assuming LAKIP Kabupaten data is stored in a table, adjust table name as needed
        $stats = $this->db->table('lakip_kabupaten') // Adjust table name
            ->select('status, COUNT(*) as count')
            ->groupBy('status')
            ->get()
            ->getResultArray();
        
        $result = ['draft' => 0, 'selesai' => 0];
        foreach ($stats as $stat) {
            $result[$stat['status']] = $stat['count'];
        }
        
        return $result;
    }

    /**
     * Get LAKIP OPD statistics by status
     */
    public function getLakipOpdStats()
    {
        // Get stats by joining with OPD table
        $stats = $this->db->table('lakip_opd lo') // Adjust table name
            ->select('lo.status, COUNT(*) as count')
            ->join('opd o', 'o.id = lo.opd_id', 'left')
            ->groupBy('lo.status')
            ->get()
            ->getResultArray();
        
        $result = ['draft' => 0, 'selesai' => 0];
        foreach ($stats as $stat) {
            $result[$stat['status']] = $stat['count'];
        }
        
        return $result;
    }

    /**
     * Get all OPD data for dropdown
     */
    public function getAllOpd()
    {
        return $this->db->table('opd')
            ->select('id, nama_opd')
            ->orderBy('nama_opd', 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * Get available years from various tables
     */
    public function getAvailableYears()
    {
        $years = [];
        
        // Get years from RPJMD
        $rpjmdYears = $this->db->table('rpjmd_misi')
            ->select('tahun_mulai, tahun_akhir')
            ->get()
            ->getResultArray();
        
        foreach ($rpjmdYears as $row) {
            for ($year = $row['tahun_mulai']; $year <= $row['tahun_akhir']; $year++) {
                $years[] = $year;
            }
        }
        
        // Get years from RKPD if exists
        $rkpdYears = $this->db->table('rkpd_sasaran')
            ->select('YEAR(created_at) as year')
            ->distinct()
            ->get()
            ->getResultArray();
        
        foreach ($rkpdYears as $row) {
            if ($row['year']) {
                $years[] = $row['year'];
            }
        }
        
        // Remove duplicates and sort
        $years = array_unique($years);
        rsort($years);
        
        return $years;
    }

    /**
     * Get comprehensive dashboard data
     */
    public function getDashboardData()
    {
        return [
            'rpjmd' => $this->getRpjmdStats(),
            'rkpd' => $this->getRkpdStats(),
            'renstra' => $this->getRenstraStats(),
            'renja' => $this->getRenjaStats(),
            'iku' => $this->getIkuStats(),
            'lakip_kabupaten' => $this->getLakipKabupatenStats(),
            'lakip_opd' => $this->getLakipOpdStats(),
            'opd_list' => $this->getAllOpd(),
            'available_years' => $this->getAvailableYears()
        ];
    }

    /**
     * Get dashboard data filtered by OPD and year
     */
    public function getDashboardDataByOpdAndYear($opdId = null, $year = null)
    {
        $data = [];
        
        if ($opdId) {
            // Get RENSTRA data for specific OPD
            $renstraQuery = $this->db->table('renstra_sasaran rs')
                ->select('rs.status, COUNT(*) as count')
                ->join('opd o', 'o.id = rs.opd_id', 'left')
                ->where('rs.opd_id', $opdId)
                ->groupBy('rs.status');
            
            if ($year) {
                $renstraQuery->where('YEAR(rs.created_at)', $year);
            }
            
            $renstraStats = $renstraQuery->get()->getResultArray();
            $data['renstra'] = ['draft' => 0, 'selesai' => 0];
            foreach ($renstraStats as $stat) {
                $data['renstra'][$stat['status']] = $stat['count'];
            }
            
            // Get RENJA data for specific OPD
            $renjaQuery = $this->db->table('renja_sasaran rs')
                ->select('rs.status, COUNT(*) as count')
                ->join('renstra_sasaran rst', 'rst.id = rs.renstra_sasaran_id', 'left')
                ->where('rst.opd_id', $opdId)
                ->groupBy('rs.status');
            
            if ($year) {
                $renjaQuery->where('YEAR(rs.created_at)', $year);
            }
            
            $renjaStats = $renjaQuery->get()->getResultArray();
            $data['renja'] = ['draft' => 0, 'selesai' => 0];
            foreach ($renjaStats as $stat) {
                $data['renja'][$stat['status']] = $stat['count'];
            }
            
            // Get LAKIP OPD data for specific OPD
            $lakipQuery = $this->db->table('lakip_opd')
                ->select('status, COUNT(*) as count')
                ->where('opd_id', $opdId)
                ->groupBy('status');
            
            if ($year) {
                $lakipQuery->where('YEAR(created_at)', $year);
            }
            
            $lakipStats = $lakipQuery->get()->getResultArray();
            $data['lakip_opd'] = ['draft' => 0, 'selesai' => 0];
            foreach ($lakipStats as $stat) {
                $data['lakip_opd'][$stat['status']] = $stat['count'];
            }
        }
        
        return $data;
    }

    /**
     * Get summary statistics for quick overview
     */
    public function getSummaryStats()
    {
        return [
            'total_rpjmd' => $this->db->table('rpjmd_misi')->countAllResults(),
            'total_rkpd' => $this->db->table('rkpd_sasaran')->countAllResults(),
            'total_renstra' => $this->db->table('renstra_sasaran')->countAllResults(),
            'total_renja' => $this->db->table('renja_sasaran')->countAllResults(),
            'total_opd' => $this->db->table('opd')->countAllResults(),
            'active_year' => date('Y')
        ];
    }
}