<?php

namespace App\Models;

use CodeIgniter\Model;

class RenstraModel extends Model
{
    protected $db;
    
    public function __construct()
    {
        parent::__construct();
        $this->db = \Config\Database::connect();
    }

    // ==================== RENSTRA SASARAN ====================
    
    /**
     * Get all Renstra Sasaran
     */
    public function getAllSasaran()
    {
        return $this->db->table('renstra_sasaran rs')
            ->select('rs.*, rs.indikator_sasaran as sasaran, o.nama_opd, rps.sasaran as rpjmd_sasaran')
            ->join('opd o', 'o.id = rs.opd_id')
            ->join('rpjmd_sasaran rps', 'rps.id = rs.rpjmd_sasaran_id')
            ->orderBy('rs.tahun_mulai', 'ASC')
            ->orderBy('rs.id', 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * Get Renstra Sasaran by ID
     */
    public function getSasaranById($id)
    {
        return $this->db->table('renstra_sasaran rs')
            ->select('rs.*, o.nama_opd, rps.sasaran as rpjmd_sasaran')
            ->join('opd o', 'o.id = rs.opd_id')
            ->join('rpjmd_sasaran rps', 'rps.id = rs.rpjmd_sasaran_id')
            ->where('rs.id', $id)
            ->get()
            ->getRowArray();
    }

    /**
     * Get Renstra Sasaran by OPD ID
     */
    public function getSasaranByOpdId($opdId)
    {
        return $this->db->table('renstra_sasaran rs')
            ->select('rs.*, o.nama_opd, rps.sasaran as rpjmd_sasaran')
            ->join('opd o', 'o.id = rs.opd_id')
            ->join('rpjmd_sasaran rps', 'rps.id = rs.rpjmd_sasaran_id')
            ->where('rs.opd_id', $opdId)
            ->orderBy('rs.tahun_mulai', 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * Get Renstra Sasaran by year range
     */
    public function getSasaranByYear($tahun)
    {
        return $this->db->table('renstra_sasaran')
            ->where('tahun_mulai <=', $tahun)
            ->where('tahun_akhir >=', $tahun)
            ->get()
            ->getResultArray();
    }

    /**
     * Insert new Renstra Sasaran
     */
    public function insertSasaran($data)
    {
        return $this->db->table('renstra_sasaran')->insert($data);
    }

    /**
     * Update Renstra Sasaran
     */
    public function updateSasaran($id, $data)
    {
        return $this->db->table('renstra_sasaran')
            ->where('id', $id)
            ->update($data);
    }

    /**
     * Delete Renstra Sasaran
     */
    public function deleteSasaran($id)
    {
        return $this->db->table('renstra_sasaran')
            ->where('id', $id)
            ->delete();
    }

    // ==================== RENSTRA INDIKATOR SASARAN ====================
    
    /**
     * Get all Indikator Sasaran
     */
    public function getAllIndikatorSasaran()
    {
        return $this->db->table('renstra_indikator_sasaran ris')
            ->select('ris.*, rs.indikator_sasaran as sasaran_nama, rs.tahun_mulai, rs.tahun_akhir')
            ->join('renstra_sasaran rs', 'rs.id = ris.renstra_sasaran_id')
            ->orderBy('ris.renstra_sasaran_id', 'ASC')
            ->orderBy('ris.id', 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * Get Indikator Sasaran by Sasaran ID
     */
    public function getIndikatorSasaranBySasaranId($sasaranId)
    {
        return $this->db->table('renstra_indikator_sasaran')
            ->where('renstra_sasaran_id', $sasaranId)
            ->get()
            ->getResultArray();
    }

    /**
     * Get Indikator Sasaran by ID
     */
    public function getIndikatorSasaranById($id)
    {
        return $this->db->table('renstra_indikator_sasaran ris')
            ->select('ris.*, rs.indikator_sasaran as sasaran_nama, rs.tahun_mulai, rs.tahun_akhir')
            ->join('renstra_sasaran rs', 'rs.id = ris.renstra_sasaran_id')
            ->where('ris.id', $id)
            ->get()
            ->getRowArray();
    }

    /**
     * Insert new Indikator Sasaran
     */
    public function insertIndikatorSasaran($data)
    {
        return $this->db->table('renstra_indikator_sasaran')->insert($data);
    }

    /**
     * Update Indikator Sasaran
     */
    public function updateIndikatorSasaran($id, $data)
    {
        return $this->db->table('renstra_indikator_sasaran')
            ->where('id', $id)
            ->update($data);
    }

    /**
     * Delete Indikator Sasaran
     */
    public function deleteIndikatorSasaran($id)
    {
        return $this->db->table('renstra_indikator_sasaran')
            ->where('id', $id)
            ->delete();
    }

    // ==================== RENSTRA TARGET TAHUNAN ====================
    
    /**
     * Get Target Tahunan by Indikator ID
     */
    public function getTargetTahunanByIndikatorId($indikatorId)
    {
        return $this->db->table('renstra_target')
            ->where('renstra_indikator_id', $indikatorId)
            ->orderBy('tahun', 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * Get Target Tahunan by Indikator ID and Year
     */
    public function getTargetTahunanByIndikatorAndYear($indikatorId, $tahun)
    {
        return $this->db->table('renstra_target')
            ->where('renstra_indikator_id', $indikatorId)
            ->where('tahun', $tahun)
            ->get()
            ->getRowArray();
    }

    /**
     * Insert new Target Tahunan
     */
    public function insertTargetTahunan($data)
    {
        return $this->db->table('renstra_target')->insert($data);
    }

    /**
     * Update Target Tahunan
     */
    public function updateTargetTahunan($id, $data)
    {
        return $this->db->table('renstra_target')
            ->where('id', $id)
            ->update($data);
    }

    /**
     * Delete Target Tahunan
     */
    public function deleteTargetTahunan($id)
    {
        return $this->db->table('renstra_target')
            ->where('id', $id)
            ->delete();
    }

    /**
     * Delete Target Tahunan by Indikator ID
     */
    public function deleteTargetTahunanByIndikatorId($indikatorId)
    {
        return $this->db->table('renstra_target')
            ->where('renstra_indikator_id', $indikatorId)
            ->delete();
    }

    // ==================== COMPLETE RENSTRA STRUCTURE ====================
    
    /**
     * Get complete Renstra structure with all related data
     */
    public function getCompleteRenstraStructure($opdId = null)
    {
        $query = $this->db->table('renstra_sasaran rs')
            ->select('
                rs.id as sasaran_id,
                rs.opd_id,
                rs.rpjmd_sasaran_id,
                rs.indikator_sasaran as sasaran,
                rs.tahun_mulai,
                rs.tahun_akhir,
                o.nama_opd,
                rps.sasaran as rpjmd_sasaran,
                ris.id as indikator_id,
                ris.indikator_sasaran,
                ris.satuan,
                rt.id as target_id,
                rt.tahun,
                rt.target
            ')
            ->join('opd o', 'o.id = rs.opd_id')
            ->join('rpjmd_sasaran rps', 'rps.id = rs.rpjmd_sasaran_id')
            ->join('renstra_indikator_sasaran ris', 'ris.renstra_sasaran_id = rs.id', 'left')
            ->join('renstra_target rt', 'rt.renstra_indikator_id = ris.id', 'left');

        if ($opdId !== null) {
            $query->where('rs.opd_id', $opdId);
        }

        $results = $query->orderBy('rs.id', 'ASC')
            ->orderBy('ris.id', 'ASC')
            ->orderBy('rt.tahun', 'ASC')
            ->get()
            ->getResultArray();

        // Group results
        $grouped = [];
        foreach ($results as $row) {
            $sasaranKey = $row['sasaran_id'];
            
            if (!isset($grouped[$sasaranKey])) {
                $grouped[$sasaranKey] = [
                    'sasaran_id' => $row['sasaran_id'],
                    'opd_id' => $row['opd_id'],
                    'nama_opd' => $row['nama_opd'],
                    'rpjmd_sasaran_id' => $row['rpjmd_sasaran_id'],
                    'rpjmd_sasaran' => $row['rpjmd_sasaran'],
                    'sasaran' => $row['sasaran'],
                    'tahun_mulai' => $row['tahun_mulai'],
                    'tahun_akhir' => $row['tahun_akhir'],
                    'indikator_sasaran' => []
                ];
            }

            if ($row['indikator_id']) {
                $indikatorKey = $row['indikator_id'];
                
                if (!isset($grouped[$sasaranKey]['indikator_sasaran'][$indikatorKey])) {
                    $grouped[$sasaranKey]['indikator_sasaran'][$indikatorKey] = [
                        'indikator_id' => $row['indikator_id'],
                        'indikator_sasaran' => $row['indikator_sasaran'],
                        'satuan' => $row['satuan'],
                        'target_tahunan' => []
                    ];
                }

                if ($row['target_id']) {
                    $grouped[$sasaranKey]['indikator_sasaran'][$indikatorKey]['target_tahunan'][] = [
                        'target_id' => $row['target_id'],
                        'tahun' => $row['tahun'],
                        'target' => $row['target']
                    ];
                }
            }
        }

        // Convert to indexed array
        return array_values($grouped);
    }

    /**
     * Get Renstra data for display table (flattened structure)
     */
    public function getRenstraForTable($opdId = null)
    {
        $query = $this->db->table('renstra_sasaran rs')
            ->select('
                rs.id as sasaran_id,
                rs.indikator_sasaran as sasaran,
                ris.id as indikator_id,
                ris.indikator_sasaran,
                ris.satuan,
                rs.tahun_mulai,
                rs.tahun_akhir,
                o.nama_opd
            ')
            ->join('opd o', 'o.id = rs.opd_id')
            ->join('renstra_indikator_sasaran ris', 'ris.renstra_sasaran_id = rs.id');

        if ($opdId !== null) {
            $query->where('rs.opd_id', $opdId);
        }

        $indikatorData = $query->orderBy('rs.id', 'ASC')
            ->orderBy('ris.id', 'ASC')
            ->get()
            ->getResultArray();

        // Get target data for each indikator
        foreach ($indikatorData as &$indikator) {
            if ($indikator['indikator_id']) {
                $targets = $this->getTargetTahunanByIndikatorId($indikator['indikator_id']);
                
                // Convert targets to year-based array
                $indikator['targets'] = [];
                foreach ($targets as $target) {
                    $indikator['targets'][$target['tahun']] = $target['target'];
                }
            }
        }

        return $indikatorData;
    }

    /**
     * Save complete Renstra data (Sasaran + Indikator + Target)
     */
    public function saveCompleteRenstra($data)
    {
        $this->db->transStart();

        try {
            // Insert Sasaran
            $sasaranData = [
                'opd_id' => $data['opd_id'],
                'rpjmd_sasaran_id' => $data['rpjmd_sasaran_id'],
                'indikator_sasaran' => $data['sasaran'],
                'tahun_mulai' => $data['tahun_mulai'],
                'tahun_akhir' => $data['tahun_akhir']
            ];

            $this->db->table('renstra_sasaran')->insert($sasaranData);
            $sasaranId = $this->db->insertID();

            // Insert Indikator Sasaran and Targets
            if (isset($data['indikator_sasaran']) && is_array($data['indikator_sasaran'])) {
                foreach ($data['indikator_sasaran'] as $indikator) {
                    $indikatorData = [
                        'renstra_sasaran_id' => $sasaranId,
                        'indikator_sasaran' => $indikator['indikator_sasaran'],
                        'satuan' => $indikator['satuan']
                    ];

                    $this->db->table('renstra_indikator_sasaran')->insert($indikatorData);
                    $indikatorId = $this->db->insertID();

                    // Insert Target Tahunan
                    if (isset($indikator['target_tahunan']) && is_array($indikator['target_tahunan'])) {
                        foreach ($indikator['target_tahunan'] as $target) {
                            $targetData = [
                                'renstra_indikator_id' => $indikatorId,
                                'tahun' => $target['tahun'],
                                'target' => $target['target']
                            ];

                            $this->db->table('renstra_target')->insert($targetData);
                        }
                    }
                }
            }

            $this->db->transComplete();

            if ($this->db->transStatus() === false) {
                return false;
            }

            return $sasaranId;

        } catch (\Exception $e) {
            $this->db->transRollback();
            throw $e;
        }
    }

    /**
     * Update complete Renstra data
     */
    public function updateCompleteRenstra($sasaranId, $data)
    {
        $this->db->transStart();

        try {
            // Update Sasaran
            $sasaranData = [
                'opd_id' => $data['opd_id'],
                'rpjmd_sasaran_id' => $data['rpjmd_sasaran_id'],
                'indikator_sasaran' => $data['sasaran'],
                'tahun_mulai' => $data['tahun_mulai'],
                'tahun_akhir' => $data['tahun_akhir']
            ];

            $this->db->table('renstra_sasaran')
                ->where('id', $sasaranId)
                ->update($sasaranData);

            // Delete existing indikator and targets
            $existingIndikator = $this->getIndikatorSasaranBySasaranId($sasaranId);
            foreach ($existingIndikator as $indikator) {
                $this->deleteTargetTahunanByIndikatorId($indikator['id']);
            }
            
            $this->db->table('renstra_indikator_sasaran')
                ->where('renstra_sasaran_id', $sasaranId)
                ->delete();

            // Insert new indikator and targets
            if (isset($data['indikator_sasaran']) && is_array($data['indikator_sasaran'])) {
                foreach ($data['indikator_sasaran'] as $indikator) {
                    $indikatorData = [
                        'renstra_sasaran_id' => $sasaranId,
                        'indikator_sasaran' => $indikator['indikator_sasaran'],
                        'satuan' => $indikator['satuan']
                    ];

                    $this->db->table('renstra_indikator_sasaran')->insert($indikatorData);
                    $indikatorId = $this->db->insertID();

                    // Insert Target Tahunan
                    if (isset($indikator['target_tahunan']) && is_array($indikator['target_tahunan'])) {
                        foreach ($indikator['target_tahunan'] as $target) {
                            $targetData = [
                                'renstra_indikator_id' => $indikatorId,
                                'tahun' => $target['tahun'],
                                'target' => $target['target']
                            ];

                            $this->db->table('renstra_target')->insert($targetData);
                        }
                    }
                }
            }

            $this->db->transComplete();

            return $this->db->transStatus() !== false;

        } catch (\Exception $e) {
            $this->db->transRollback();
            throw $e;
        }
    }

    /**
     * Delete complete Renstra data (Sasaran + all related data)
     */
    public function deleteCompleteRenstra($sasaranId)
    {
        $this->db->transStart();

        try {
            // Get indikator IDs first
            $indikatorList = $this->getIndikatorSasaranBySasaranId($sasaranId);
            
            // Delete targets for each indikator
            foreach ($indikatorList as $indikator) {
                $this->deleteTargetTahunanByIndikatorId($indikator['id']);
            }

            // Delete indikator sasaran
            $this->db->table('renstra_indikator_sasaran')
                ->where('renstra_sasaran_id', $sasaranId)
                ->delete();

            // Delete sasaran
            $this->db->table('renstra_sasaran')
                ->where('id', $sasaranId)
                ->delete();

            $this->db->transComplete();

            return $this->db->transStatus() !== false;

        } catch (\Exception $e) {
            $this->db->transRollback();
            throw $e;
        }
    }
}
