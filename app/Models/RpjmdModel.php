<?php

namespace App\Models;

use CodeIgniter\Model;

class RpjmdModel extends Model
{
    protected $db;
    
    public function __construct()
    {
        parent::__construct();
        $this->db = \Config\Database::connect();
    }

    // ==================== RPJMD MISI ====================
    
    /**
     * Get all RPJMD Misi
     */
    public function getAllMisi()
    {
        return $this->db->table('rpjmd_misi')
            ->orderBy('tahun_mulai', 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * Get RPJMD Misi by ID
     */
    public function getMisiById($id)
    {
        return $this->db->table('rpjmd_misi')
            ->where('id', $id)
            ->get()
            ->getRowArray();
    }

    /**
     * Get RPJMD Misi by year range
     */
    public function getMisiByYear($tahun)
    {
        return $this->db->table('rpjmd_misi')
            ->where('tahun_mulai <=', $tahun)
            ->where('tahun_akhir >=', $tahun)
            ->get()
            ->getResultArray();
    }

    // ==================== RPJMD TUJUAN ====================
    
    /**
     * Get all RPJMD Tujuan
     */
    public function getAllTujuan()
    {
        return $this->db->table('rpjmd_tujuan t')
            ->select('t.*, m.misi, m.tahun_mulai, m.tahun_akhir')
            ->join('rpjmd_misi m', 'm.id = t.misi_id')
            ->orderBy('t.misi_id', 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * Get RPJMD Tujuan by Misi ID
     */
    public function getTujuanByMisiId($misiId)
    {
        return $this->db->table('rpjmd_tujuan')
            ->where('misi_id', $misiId)
            ->get()
            ->getResultArray();
    }

    /**
     * Get RPJMD Tujuan by ID
     */
    public function getTujuanById($id)
    {
        return $this->db->table('rpjmd_tujuan t')
            ->select('t.*, m.misi, m.tahun_mulai, m.tahun_akhir')
            ->join('rpjmd_misi m', 'm.id = t.misi_id')
            ->where('t.id', $id)
            ->get()
            ->getRowArray();
    }

    // ==================== RPJMD INDIKATOR TUJUAN ====================
    
    /**
     * Get all RPJMD Indikator Tujuan
     */
    public function getAllIndikatorTujuan()
    {
        return $this->db->table('rpjmd_indikator_tujuan it')
            ->select('it.*, t.tujuan_rpjmd, m.misi')
            ->join('rpjmd_tujuan t', 't.id = it.tujuan_id')
            ->join('rpjmd_misi m', 'm.id = t.misi_id')
            ->orderBy('it.tujuan_id', 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * Get RPJMD Indikator Tujuan by Tujuan ID
     */
    public function getIndikatorTujuanByTujuanId($tujuanId)
    {
        return $this->db->table('rpjmd_indikator_tujuan')
            ->where('tujuan_id', $tujuanId)
            ->get()
            ->getResultArray();
    }

    // ==================== RPJMD SASARAN ====================
    
    /**
     * Get all RPJMD Sasaran
     */
    public function getAllSasaran()
    {
        return $this->db->table('rpjmd_sasaran s')
            ->select('s.*, t.tujuan_rpjmd, m.misi')
            ->join('rpjmd_tujuan t', 't.id = s.tujuan_id')
            ->join('rpjmd_misi m', 'm.id = t.misi_id')
            ->orderBy('s.tujuan_id', 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * Get RPJMD Sasaran by Tujuan ID
     */
    public function getSasaranByTujuanId($tujuanId)
    {
        return $this->db->table('rpjmd_sasaran')
            ->where('tujuan_id', $tujuanId)
            ->get()
            ->getResultArray();
    }

    /**
     * Get RPJMD Sasaran by ID
     */
    public function getSasaranById($id)
    {
        return $this->db->table('rpjmd_sasaran s')
            ->select('s.*, t.tujuan_rpjmd, m.misi')
            ->join('rpjmd_tujuan t', 't.id = s.tujuan_id')
            ->join('rpjmd_misi m', 'm.id = t.misi_id')
            ->where('s.id', $id)
            ->get()
            ->getRowArray();
    }

    // ==================== RPJMD INDIKATOR SASARAN ====================
    
    /**
     * Get all RPJMD Indikator Sasaran
     */
    public function getAllIndikatorSasaran()
    {
        return $this->db->table('rpjmd_indikator_sasaran is')
            ->select('is.*, s.sasaran_rpjmd, t.tujuan_rpjmd, m.misi')
            ->join('rpjmd_sasaran s', 's.id = is.sasaran_id')
            ->join('rpjmd_tujuan t', 't.id = s.tujuan_id')
            ->join('rpjmd_misi m', 'm.id = t.misi_id')
            ->orderBy('is.sasaran_id', 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * Get RPJMD Indikator Sasaran by Sasaran ID
     */
    public function getIndikatorSasaranBySasaranId($sasaranId)
    {
        return $this->db->table('rpjmd_indikator_sasaran')
            ->where('sasaran_id', $sasaranId)
            ->get()
            ->getResultArray();
    }

    /**
     * Get RPJMD Indikator Sasaran by ID
     */
    public function getIndikatorSasaranById($id)
    {
        return $this->db->table('rpjmd_indikator_sasaran is')
            ->select('is.*, s.sasaran_rpjmd, t.tujuan_rpjmd, m.misi')
            ->join('rpjmd_sasaran s', 's.id = is.sasaran_id')
            ->join('rpjmd_tujuan t', 't.id = s.tujuan_id')
            ->join('rpjmd_misi m', 'm.id = t.misi_id')
            ->where('is.id', $id)
            ->get()
            ->getRowArray();
    }

    // ==================== RPJMD TARGET TAHUNAN ====================
    
    /**
     * Get all RPJMD Target Tahunan
     */
    public function getAllTargetTahunan()
    {
        return $this->db->table('rpjmd_target tt')
            ->select('tt.*, is.indikator_sasaran, is.strategi, is.satuan, s.sasaran_rpjmd, t.tujuan_rpjmd, m.misi')
            ->join('rpjmd_indikator_sasaran is', 'is.id = tt.indikator_sasaran_id')
            ->join('rpjmd_sasaran s', 's.id = is.sasaran_id')
            ->join('rpjmd_tujuan t', 't.id = s.tujuan_id')
            ->join('rpjmd_misi m', 'm.id = t.misi_id')
            ->orderBy('tt.tahun', 'ASC')
            ->orderBy('tt.indikator_sasaran_id', 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * Get RPJMD Target Tahunan by Indikator ID
     */
    public function getTargetTahunanByIndikatorId($indikatorId)
    {
        return $this->db->table('rpjmd_target')
            ->where('indikator_sasaran_id', $indikatorId)
            ->orderBy('tahun', 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * Get RPJMD Target Tahunan by Year
     */
    public function getTargetTahunanByYear($tahun)
    {
        return $this->db->table('rpjmd_target tt')
            ->select('tt.*, is.indikator_sasaran, is.strategi, is.satuan, s.sasaran_rpjmd, t.tujuan_rpjmd, m.misi')
            ->join('rpjmd_indikator_sasaran is', 'is.id = tt.indikator_sasaran_id')
            ->join('rpjmd_sasaran s', 's.id = is.sasaran_id')
            ->join('rpjmd_tujuan t', 't.id = s.tujuan_id')
            ->join('rpjmd_misi m', 'm.id = t.misi_id')
            ->where('tt.tahun', $tahun)
            ->get()
            ->getResultArray();
    }

    // ==================== COMPREHENSIVE DATA LOADING ====================
    
    /**
     * Get complete RPJMD hierarchy structure
     */
    public function getCompleteRpjmdStructure()
    {
        $misiList = $this->getAllMisi();
        
        foreach ($misiList as &$misi) {
            $misi['tujuan'] = $this->getTujuanByMisiId($misi['id']);
            
            foreach ($misi['tujuan'] as &$tujuan) {
                $tujuan['indikator_tujuan'] = $this->getIndikatorTujuanByTujuanId($tujuan['id']);
                $tujuan['sasaran'] = $this->getSasaranByTujuanId($tujuan['id']);
                
                foreach ($tujuan['sasaran'] as &$sasaran) {
                    $sasaran['indikator_sasaran'] = $this->getIndikatorSasaranBySasaranId($sasaran['id']);
                    
                    foreach ($sasaran['indikator_sasaran'] as &$indikator) {
                        $indikator['target_tahunan'] = $this->getTargetTahunanByIndikatorId($indikator['id']);
                    }
                }
            }
        }
        
        return $misiList;
    }

    /**
     * Get RPJMD data by specific year with all related data
     */
    public function getRpjmdByYear($tahun)
    {
        $misiList = $this->getMisiByYear($tahun);
        
        foreach ($misiList as &$misi) {
            $misi['tujuan'] = $this->getTujuanByMisiId($misi['id']);
            
            foreach ($misi['tujuan'] as &$tujuan) {
                $tujuan['sasaran'] = $this->getSasaranByTujuanId($tujuan['id']);
                
                foreach ($tujuan['sasaran'] as &$sasaran) {
                    $sasaran['indikator_sasaran'] = $this->getIndikatorSasaranBySasaranId($sasaran['id']);
                    
                    foreach ($sasaran['indikator_sasaran'] as &$indikator) {
                        $indikator['target_tahunan'] = $this->db->table('rpjmd_target')
                            ->where('indikator_sasaran_id', $indikator['id'])
                            ->where('tahun', $tahun)
                            ->get()
                            ->getResultArray();
                    }
                }
            }
        }
        
        return $misiList;
    }

    /**
     * Get summary statistics of RPJMD data
     */
    public function getRpjmdSummary()
    {
        $summary = [
            'total_misi' => $this->db->table('rpjmd_misi')->countAllResults(),
            'total_tujuan' => $this->db->table('rpjmd_tujuan')->countAllResults(),
            'total_sasaran' => $this->db->table('rpjmd_sasaran')->countAllResults(),
            'total_indikator_sasaran' => $this->db->table('rpjmd_indikator_sasaran')->countAllResults(),
            'total_target_tahunan' => $this->db->table('rpjmd_target')->countAllResults(),
            'years_available' => $this->db->table('rpjmd_target')
                ->distinct()
                ->select('tahun')
                ->orderBy('tahun', 'ASC')
                ->get()
                ->getResultArray()
        ];
        
        return $summary;
    }

    // ==================== SEARCH FUNCTIONS ====================
    
    /**
     * Search RPJMD data by keyword
     */
    public function searchRpjmd($keyword)
    {
        $results = [
            'misi' => $this->db->table('rpjmd_misi')
                ->like('misi', $keyword)
                ->get()
                ->getResultArray(),
            'tujuan' => $this->db->table('rpjmd_tujuan t')
                ->select('t.*, m.misi')
                ->join('rpjmd_misi m', 'm.id = t.misi_id')
                ->like('t.tujuan_rpjmd', $keyword)
                ->get()
                ->getResultArray(),
            'sasaran' => $this->db->table('rpjmd_sasaran s')
                ->select('s.*, t.tujuan_rpjmd, m.misi')
                ->join('rpjmd_tujuan t', 't.id = s.tujuan_id')
                ->join('rpjmd_misi m', 'm.id = t.misi_id')
                ->like('s.sasaran_rpjmd', $keyword)
                ->get()
                ->getResultArray(),
            'indikator' => $this->db->table('rpjmd_indikator_sasaran is')
                ->select('is.*, s.sasaran_rpjmd, t.tujuan_rpjmd, m.misi')
                ->join('rpjmd_sasaran s', 's.id = is.sasaran_id')
                ->join('rpjmd_tujuan t', 't.id = s.tujuan_id')
                ->join('rpjmd_misi m', 'm.id = t.misi_id')
                ->groupStart()
                ->like('is.indikator_sasaran', $keyword)
                ->orLike('is.strategi', $keyword)
                ->groupEnd()
                ->get()
                ->getResultArray()
        ];
        
        return $results;
    }

    // ==================== CRUD OPERATIONS FOR RPJMD MISI ====================
    
    /**
     * Create new RPJMD Misi
     */
    public function createMisi($data)
    {
        // Validation
        $required = ['misi', 'tahun_mulai', 'tahun_akhir'];
        foreach ($required as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                throw new \InvalidArgumentException("Field {$field} harus diisi");
            }
        }
        
        return $this->db->table('rpjmd_misi')->insert($data);
    }
    
    /**
     * Update RPJMD Misi
     */
    public function updateMisi($id, $data)
    {
        return $this->db->table('rpjmd_misi')->update(['id' => $id], $data);
    }
    
    /**
     * Delete RPJMD Misi (with cascade delete)
     */
    public function deleteMisi($id)
    {
        $this->db->transStart();
        
        try {
            // Get all related data for cascade delete
            $tujuanList = $this->getTujuanByMisiId($id);
            
            foreach ($tujuanList as $tujuan) {
                $this->deleteTujuan($tujuan['id']);
            }
            
            // Delete the misi
            $result = $this->db->table('rpjmd_misi')->delete(['id' => $id]);
            
            $this->db->transComplete();
            return $result;
            
        } catch (\Exception $e) {
            $this->db->transRollback();
            throw $e;
        }
    }

    // ==================== CRUD OPERATIONS FOR RPJMD TUJUAN ====================
    
    /**
     * Create new RPJMD Tujuan
     */
    public function createTujuan($data)
    {
        // Validation
        $required = ['misi_id', 'tujuan_rpjmd'];
        foreach ($required as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                throw new \InvalidArgumentException("Field {$field} harus diisi");
            }
        }
        
        return $this->db->table('rpjmd_tujuan')->insert($data);
    }
    
    /**
     * Update RPJMD Tujuan
     */
    public function updateTujuan($id, $data)
    {
        return $this->db->table('rpjmd_tujuan')->update(['id' => $id], $data);
    }
    
    /**
     * Delete RPJMD Tujuan (with cascade delete)
     */
    public function deleteTujuan($id)
    {
        $this->db->transStart();
        
        try {
            // Delete related indikator tujuan
            $this->db->table('rpjmd_indikator_tujuan')->delete(['tujuan_id' => $id]);
            
            // Get and delete related sasaran
            $sasaranList = $this->getSasaranByTujuanId($id);
            foreach ($sasaranList as $sasaran) {
                $this->deleteSasaran($sasaran['id']);
            }
            
            // Delete the tujuan
            $result = $this->db->table('rpjmd_tujuan')->delete(['id' => $id]);
            
            $this->db->transComplete();
            return $result;
            
        } catch (\Exception $e) {
            $this->db->transRollback();
            throw $e;
        }
    }

    // ==================== CRUD OPERATIONS FOR RPJMD INDIKATOR TUJUAN ====================
    
    /**
     * Create new RPJMD Indikator Tujuan
     */
    public function createIndikatorTujuan($data)
    {
        // Validation
        $required = ['tujuan_id', 'indikator'];
        foreach ($required as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                throw new \InvalidArgumentException("Field {$field} harus diisi");
            }
        }
        
        return $this->db->table('rpjmd_indikator_tujuan')->insert($data);
    }
    
    /**
     * Update RPJMD Indikator Tujuan
     */
    public function updateIndikatorTujuan($id, $data)
    {
        return $this->db->table('rpjmd_indikator_tujuan')->update(['id' => $id], $data);
    }
    
    /**
     * Delete RPJMD Indikator Tujuan
     */
    public function deleteIndikatorTujuan($id)
    {
        return $this->db->table('rpjmd_indikator_tujuan')->delete(['id' => $id]);
    }

    // ==================== CRUD OPERATIONS FOR RPJMD SASARAN ====================
    
    /**
     * Create new RPJMD Sasaran
     */
    public function createSasaran($data)
    {
        // Validation
        $required = ['tujuan_id', 'sasaran_rpjmd'];
        foreach ($required as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                throw new \InvalidArgumentException("Field {$field} harus diisi");
            }
        }
        
        return $this->db->table('rpjmd_sasaran')->insert($data);
    }
    
    /**
     * Update RPJMD Sasaran
     */
    public function updateSasaran($id, $data)
    {
        return $this->db->table('rpjmd_sasaran')->update(['id' => $id], $data);
    }
    
    /**
     * Delete RPJMD Sasaran (with cascade delete)
     */
    public function deleteSasaran($id)
    {
        $this->db->transStart();
        
        try {
            // Get and delete related indikator sasaran
            $indikatorList = $this->getIndikatorSasaranBySasaranId($id);
            foreach ($indikatorList as $indikator) {
                $this->deleteIndikatorSasaran($indikator['id']);
            }
            
            // Delete the sasaran
            $result = $this->db->table('rpjmd_sasaran')->delete(['id' => $id]);
            
            $this->db->transComplete();
            return $result;
            
        } catch (\Exception $e) {
            $this->db->transRollback();
            throw $e;
        }
    }

    // ==================== CRUD OPERATIONS FOR RPJMD INDIKATOR SASARAN ====================
    
    /**
     * Create new RPJMD Indikator Sasaran
     */
    public function createIndikatorSasaran($data)
    {
        // Validation
        $required = ['sasaran_id', 'indikator_sasaran', 'strategi', 'satuan'];
        foreach ($required as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                throw new \InvalidArgumentException("Field {$field} harus diisi");
            }
        }
        
        return $this->db->table('rpjmd_indikator_sasaran')->insert($data);
    }
    
    /**
     * Update RPJMD Indikator Sasaran
     */
    public function updateIndikatorSasaran($id, $data)
    {
        return $this->db->table('rpjmd_indikator_sasaran')->update(['id' => $id], $data);
    }
    
    /**
     * Delete RPJMD Indikator Sasaran (with cascade delete)
     */
    public function deleteIndikatorSasaran($id)
    {
        $this->db->transStart();
        
        try {
            // Delete related target tahunan
            $this->db->table('rpjmd_target')->delete(['indikator_sasaran_id' => $id]);
            
            // Delete the indikator sasaran
            $result = $this->db->table('rpjmd_indikator_sasaran')->delete(['id' => $id]);
            
            $this->db->transComplete();
            return $result;
            
        } catch (\Exception $e) {
            $this->db->transRollback();
            throw $e;
        }
    }

    // ==================== CRUD OPERATIONS FOR RPJMD TARGET TAHUNAN ====================
    
    /**
     * Create new RPJMD Target Tahunan
     */
    public function createTargetTahunan($data)
    {
        // Validation
        $required = ['indikator_sasaran_id', 'tahun', 'target_tahunan'];
        foreach ($required as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                throw new \InvalidArgumentException("Field {$field} harus diisi");
            }
        }
        
        return $this->db->table('rpjmd_target')->insert($data);
    }
    
    /**
     * Update RPJMD Target Tahunan
     */
    public function updateTargetTahunan($id, $data)
    {
        return $this->db->table('rpjmd_target')->update(['id' => $id], $data);
    }
    
    /**
     * Delete RPJMD Target Tahunan
     */
    public function deleteTargetTahunan($id)
    {
        return $this->db->table('rpjmd_target')->delete(['id' => $id]);
    }

    // ==================== BATCH OPERATIONS ====================
    
    /**
     * Create complete RPJMD structure (misi with all relations)
     */
    public function createCompleteRpjmd($data)
    {
        $this->db->transStart();
        
        try {
            // Create misi
            $misiId = $this->createMisi($data['misi']);
            
            if (isset($data['tujuan']) && is_array($data['tujuan'])) {
                foreach ($data['tujuan'] as $tujuanData) {
                    $tujuanData['misi_id'] = $misiId;
                    $tujuanId = $this->createTujuan($tujuanData);
                    
                    // Create indikator tujuan
                    if (isset($tujuanData['indikator_tujuan']) && is_array($tujuanData['indikator_tujuan'])) {
                        foreach ($tujuanData['indikator_tujuan'] as $indikatorTujuan) {
                            $indikatorTujuan['tujuan_id'] = $tujuanId;
                            $this->createIndikatorTujuan($indikatorTujuan);
                        }
                    }
                    
                    // Create sasaran
                    if (isset($tujuanData['sasaran']) && is_array($tujuanData['sasaran'])) {
                        foreach ($tujuanData['sasaran'] as $sasaranData) {
                            $sasaranData['tujuan_id'] = $tujuanId;
                            $sasaranId = $this->createSasaran($sasaranData);
                            
                            // Create indikator sasaran
                            if (isset($sasaranData['indikator_sasaran']) && is_array($sasaranData['indikator_sasaran'])) {
                                foreach ($sasaranData['indikator_sasaran'] as $indikatorSasaran) {
                                    $indikatorSasaran['sasaran_id'] = $sasaranId;
                                    $indikatorId = $this->createIndikatorSasaran($indikatorSasaran);
                                    
                                    // Create target tahunan
                                    if (isset($indikatorSasaran['target_tahunan']) && is_array($indikatorSasaran['target_tahunan'])) {
                                        foreach ($indikatorSasaran['target_tahunan'] as $target) {
                                            $target['indikator_sasaran_id'] = $indikatorId;
                                            $this->createTargetTahunan($target);
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
            
            $this->db->transComplete();
            return $misiId;
            
        } catch (\Exception $e) {
            $this->db->transRollback();
            throw $e;
        }
    }

    /**
     * Update complete RPJMD structure
     */
    public function updateCompleteRpjmd($misiId, $data)
    {
        $this->db->transStart();
        
        try {
            // Update misi
            if (isset($data['misi'])) {
                $this->updateMisi($misiId, $data['misi']);
            }
            
            // For updating relations, you can implement more sophisticated logic here
            // This is a basic implementation
            
            $this->db->transComplete();
            return true;
            
        } catch (\Exception $e) {
            $this->db->transRollback();
            throw $e;
        }
    }

    /**
     * Bulk insert targets for multiple years
     */
    public function createMultipleTargets($indikatorId, $targets)
    {
        $this->db->transStart();
        
        try {
            $insertData = [];
            foreach ($targets as $target) {
                $insertData[] = [
                    'indikator_sasaran_id' => $indikatorId,
                    'tahun' => $target['tahun'],
                    'target_tahunan' => $target['target_tahunan']
                ];
            }
            
            $result = $this->db->table('rpjmd_target')->insertBatch($insertData);
            
            $this->db->transComplete();
            return $result;
            
        } catch (\Exception $e) {
            $this->db->transRollback();
            throw $e;
        }
    }

    // ==================== HELPER METHODS ====================
    
    /**
     * Check if Misi exists
     */
    public function misiExists($id)
    {
        return $this->db->table('rpjmd_misi')->where('id', $id)->countAllResults() > 0;
    }
    
    /**
     * Check if Tujuan exists
     */
    public function tujuanExists($id)
    {
        return $this->db->table('rpjmd_tujuan')->where('id', $id)->countAllResults() > 0;
    }
    
    /**
     * Check if Sasaran exists
     */
    public function sasaranExists($id)
    {
        return $this->db->table('rpjmd_sasaran')->where('id', $id)->countAllResults() > 0;
    }
    
    /**
     * Check if Indikator Sasaran exists
     */
    public function indikatorSasaranExists($id)
    {
        return $this->db->table('rpjmd_indikator_sasaran')->where('id', $id)->countAllResults() > 0;
    }
    
    /**
     * Get table structure info
     */
    public function getTableInfo($tableName)
    {
        return $this->db->getFieldData($tableName);
    }
}