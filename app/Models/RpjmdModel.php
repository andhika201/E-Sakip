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

    /**
     * Get all RPJMD Misi dengan filter status
     */
    public function getAllMisiByStatus($status = null)
    {
        $query = $this->db->table('rpjmd_misi');
        
        if ($status !== null) {
            $query->where('status', $status);
        }
        
        return $query->orderBy('tahun_mulai', 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * Get only completed RPJMD Misi (untuk tampilan user)
     */
    public function getCompletedMisi()
    {
        return $this->db->table('rpjmd_misi')
            ->where('status', 'selesai')
            ->orderBy('tahun_mulai', 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * Update status RPJMD Misi
     */
    public function updateMisiStatus($id, $status)
    {
        if (!in_array($status, ['draft', 'selesai'])) {
            throw new \InvalidArgumentException("Status harus 'draft' atau 'selesai'");
        }
        
        return $this->db->table('rpjmd_misi')
            ->where('id', $id)
            ->update(['status' => $status]);
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
     * Get all RPJMD Sasaran from completed Misi only (for Renstra dropdown)
     */
    public function getAllSasaranFromCompletedMisi()
    {
        return $this->db->table('rpjmd_sasaran s')
            ->select('s.*, t.tujuan_rpjmd, m.misi')
            ->join('rpjmd_tujuan t', 't.id = s.tujuan_id')
            ->join('rpjmd_misi m', 'm.id = t.misi_id')
            ->where('m.status', 'selesai')
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

    /**
     * Get all RPJMD Sasaran with periode information
     */
    public function getAllSasaranWithPeriode()
    {
        return $this->db->table('rpjmd_sasaran')
            ->select('rpjmd_sasaran.id, rpjmd_sasaran.sasaran_rpjmd, rpjmd_misi.tahun_mulai, rpjmd_misi.tahun_akhir')
            ->join('rpjmd_tujuan', 'rpjmd_tujuan.id = rpjmd_sasaran.rpjmd_tujuan_id')
            ->join('rpjmd_misi', 'rpjmd_misi.id = rpjmd_tujuan.rpjmd_misi_id')
            ->orderBy('rpjmd_sasaran.id', 'ASC')
            ->get()
            ->getResultArray();
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
    public function getTargetTahunanByIndikatorId($indikatorId)
    {
        return $this->db->table('rpjmd_target')
            ->where('indikator_sasaran_id', $indikatorId)
            ->orderBy('tahun', 'ASC')
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
            
            if (!empty($misi['tujuan']) && is_array($misi['tujuan'])) {
                foreach ($misi['tujuan'] as &$tujuan) {
                    $tujuan['indikator_tujuan'] = $this->getIndikatorTujuanByTujuanId($tujuan['id']);
                    $tujuan['sasaran'] = $this->getSasaranByTujuanId($tujuan['id']);
                    
                    if (!empty($tujuan['sasaran']) && is_array($tujuan['sasaran'])) {
                        foreach ($tujuan['sasaran'] as &$sasaran) {
                            $sasaran['indikator_sasaran'] = $this->getIndikatorSasaranBySasaranId($sasaran['id']);
                            
                            if (!empty($sasaran['indikator_sasaran']) && is_array($sasaran['indikator_sasaran'])) {
                                foreach ($sasaran['indikator_sasaran'] as &$indikator) {
                                    $indikator['target_tahunan'] = $this->getTargetTahunanByIndikatorId($indikator['id']);
                                }
                            }
                        }
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
            
            if (!empty($misi['tujuan']) && is_array($misi['tujuan'])) {
                foreach ($misi['tujuan'] as &$tujuan) {
                    $tujuan['sasaran'] = $this->getSasaranByTujuanId($tujuan['id']);
                    
                    if (!empty($tujuan['sasaran']) && is_array($tujuan['sasaran'])) {
                        foreach ($tujuan['sasaran'] as &$sasaran) {
                            $sasaran['indikator_sasaran'] = $this->getIndikatorSasaranBySasaranId($sasaran['id']);
                            
                            if (!empty($sasaran['indikator_sasaran']) && is_array($sasaran['indikator_sasaran'])) {
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
                ->orLike('is.definisi_op', $keyword)
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
        $debugFile = WRITEPATH . 'debug_rpjmd.txt';
        
        // Validation
        $required = ['misi', 'tahun_mulai', 'tahun_akhir'];
        foreach ($required as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                throw new \InvalidArgumentException("Field {$field} harus diisi");
            }
        }
        
        $insertData = [
            'misi' => $data['misi'],
            'tahun_mulai' => $data['tahun_mulai'],  
            'tahun_akhir' => $data['tahun_akhir'],
            'status' => $data['status'] ?? 'draft'
        ];
        
        file_put_contents($debugFile, "About to insert misi: " . print_r($insertData, true) . "\n", FILE_APPEND);
        
        $result = $this->db->table('rpjmd_misi')->insert($insertData);
        $insertId = $this->db->insertID();
        
        file_put_contents($debugFile, "Insert result: " . ($result ? 'SUCCESS' : 'FAILED') . "\n", FILE_APPEND);
        file_put_contents($debugFile, "Insert ID: " . $insertId . "\n", FILE_APPEND);
        
        if (!$result) {
            $error = $this->db->error();
            file_put_contents($debugFile, "Database error: " . print_r($error, true) . "\n", FILE_APPEND);
            throw new \Exception("Failed to insert misi: " . $error['message']);
        }
        
        return $insertId;
    }
    
    /**
     * Update RPJMD Misi
     */
    public function updateMisi($id, $data)
    {
        return $this->db->table('rpjmd_misi')->where('id', $id)->update($data);
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
        $debugFile = WRITEPATH . 'debug_rpjmd.txt';
        
        // Validation
        $required = ['misi_id', 'tujuan_rpjmd'];
        foreach ($required as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                throw new \InvalidArgumentException("Field {$field} harus diisi");
            }
        }
        
        $insertData = [
            'misi_id' => $data['misi_id'],
            'tujuan_rpjmd' => $data['tujuan_rpjmd']
        ];
        
        file_put_contents($debugFile, "About to insert tujuan: " . print_r($insertData, true) . "\n", FILE_APPEND);
        
        $result = $this->db->table('rpjmd_tujuan')->insert($insertData);
        $insertId = $this->db->insertID();
        
        file_put_contents($debugFile, "Tujuan insert result: " . ($result ? 'SUCCESS' : 'FAILED') . "\n", FILE_APPEND);
        file_put_contents($debugFile, "Tujuan insert ID: " . $insertId . "\n", FILE_APPEND);
        
        if (!$result) {
            $error = $this->db->error();
            file_put_contents($debugFile, "Tujuan database error: " . print_r($error, true) . "\n", FILE_APPEND);
            throw new \Exception("Failed to insert tujuan: " . $error['message']);
        }
        
        return $insertId;
    }
    
    /**
     * Update RPJMD Tujuan
     */
    public function updateTujuan($id, $data)
    {
        return $this->db->table('rpjmd_tujuan')->where('id', $id)->update($data);
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
        $debugFile = WRITEPATH . 'debug_rpjmd.txt';
        
        // Validation
        $required = ['tujuan_id', 'indikator_tujuan'];
        foreach ($required as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                throw new \InvalidArgumentException("Field {$field} harus diisi");
            }
        }
        
        $insertData = [
            'tujuan_id' => $data['tujuan_id'],
            'indikator_tujuan' => $data['indikator_tujuan']
        ];
        
        file_put_contents($debugFile, "About to insert indikator tujuan: " . print_r($insertData, true) . "\n", FILE_APPEND);
        
        $result = $this->db->table('rpjmd_indikator_tujuan')->insert($insertData);
        $insertId = $this->db->insertID();
        
        file_put_contents($debugFile, "Indikator tujuan insert result: " . ($result ? 'SUCCESS' : 'FAILED') . "\n", FILE_APPEND);
        file_put_contents($debugFile, "Indikator tujuan insert ID: " . $insertId . "\n", FILE_APPEND);
        
        if (!$result) {
            $error = $this->db->error();
            file_put_contents($debugFile, "Indikator tujuan database error: " . print_r($error, true) . "\n", FILE_APPEND);
            throw new \Exception("Failed to insert indikator tujuan: " . $error['message']);
        }
        
        return $insertId;
    }
    
    /**
     * Update RPJMD Indikator Tujuan
     */
    public function updateIndikatorTujuan($id, $data)
    {
        return $this->db->table('rpjmd_indikator_tujuan')->where('id', $id)->update($data);
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
        $debugFile = WRITEPATH . 'debug_rpjmd.txt';
        
        // Validation
        $required = ['tujuan_id', 'sasaran_rpjmd'];
        foreach ($required as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                throw new \InvalidArgumentException("Field {$field} harus diisi");
            }
        }
        
        $insertData = [
            'tujuan_id' => $data['tujuan_id'],
            'sasaran_rpjmd' => $data['sasaran_rpjmd']
        ];
        
        file_put_contents($debugFile, "About to insert sasaran: " . print_r($insertData, true) . "\n", FILE_APPEND);
        
        $result = $this->db->table('rpjmd_sasaran')->insert($insertData);
        $insertId = $this->db->insertID();
        
        file_put_contents($debugFile, "Sasaran insert result: " . ($result ? 'SUCCESS' : 'FAILED') . "\n", FILE_APPEND);
        file_put_contents($debugFile, "Sasaran insert ID: " . $insertId . "\n", FILE_APPEND);
        
        if (!$result) {
            $error = $this->db->error();
            file_put_contents($debugFile, "Sasaran database error: " . print_r($error, true) . "\n", FILE_APPEND);
            throw new \Exception("Failed to insert sasaran: " . $error['message']);
        }
        
        return $insertId;
    }
    
    /**
     * Update RPJMD Sasaran
     */
    public function updateSasaran($id, $data)
    {
        return $this->db->table('rpjmd_sasaran')->where('id', $id)->update($data);
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
        $debugFile = WRITEPATH . 'debug_rpjmd.txt';
        
        // Validation
        $required = ['sasaran_id', 'indikator_sasaran', 'definisi_op', 'satuan'];
        foreach ($required as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                throw new \InvalidArgumentException("Field {$field} harus diisi");
            }
        }
        
        $insertData = [
            'sasaran_id' => $data['sasaran_id'],
            'indikator_sasaran' => $data['indikator_sasaran'],
            'definisi_op' => $data['definisi_op'],
            'satuan' => $data['satuan']
        ];
        
        file_put_contents($debugFile, "About to insert indikator sasaran: " . print_r($insertData, true) . "\n", FILE_APPEND);
        
        $result = $this->db->table('rpjmd_indikator_sasaran')->insert($insertData);
        $insertId = $this->db->insertID();
        
        file_put_contents($debugFile, "Indikator sasaran insert result: " . ($result ? 'SUCCESS' : 'FAILED') . "\n", FILE_APPEND);
        file_put_contents($debugFile, "Indikator sasaran insert ID: " . $insertId . "\n", FILE_APPEND);
        
        if (!$result) {
            $error = $this->db->error();
            file_put_contents($debugFile, "Indikator sasaran database error: " . print_r($error, true) . "\n", FILE_APPEND);
            throw new \Exception("Failed to insert indikator sasaran: " . $error['message']);
        }
        
        return $insertId;
    }
    
    /**
     * Update RPJMD Indikator Sasaran
     */
    public function updateIndikatorSasaran($id, $data)
    {
        return $this->db->table('rpjmd_indikator_sasaran')->where('id', $id)->update($data);
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
        $debugFile = WRITEPATH . 'debug_rpjmd.txt';
        
        // Validation
        $required = ['indikator_sasaran_id', 'tahun'];
        foreach ($required as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                throw new \InvalidArgumentException("Field {$field} harus diisi");
            }
        }
        
        // target_tahunan is optional and can be empty
        $insertData = [
            'indikator_sasaran_id' => $data['indikator_sasaran_id'],
            'tahun' => $data['tahun'],
            'target_tahunan' => $data['target_tahunan'] ?? ''
        ];
        
        file_put_contents($debugFile, "About to insert target tahunan: " . print_r($insertData, true) . "\n", FILE_APPEND);
        
        $result = $this->db->table('rpjmd_target')->insert($insertData);
        $insertId = $this->db->insertID();
        
        file_put_contents($debugFile, "Target tahunan insert result: " . ($result ? 'SUCCESS' : 'FAILED') . "\n", FILE_APPEND);
        file_put_contents($debugFile, "Target tahunan insert ID: " . $insertId . "\n", FILE_APPEND);
        
        if (!$result) {
            $error = $this->db->error();
            file_put_contents($debugFile, "Target tahunan database error: " . print_r($error, true) . "\n", FILE_APPEND);
            throw new \Exception("Failed to insert target tahunan: " . $error['message']);
        }
        
        return $insertId;
    }
    
    /**
     * Update RPJMD Target Tahunan
     */
    public function updateTargetTahunan($id, $data)
    {
        return $this->db->table('rpjmd_target')->where('id', $id)->update($data);
    }
    
    /**
     * Delete RPJMD Target Tahunan
     */
    public function deleteTargetTahunan($id)
    {
        return $this->db->table('rpjmd_target')->delete(['id' => $id]);
    }
    
    /**
     * Delete all RPJMD Target Tahunan by Indikator Sasaran ID
     */
    public function deleteTargetTahunanByIndikatorId($indikatorSasaranId)
    {
        return $this->db->table('rpjmd_target')->delete(['indikator_sasaran_id' => $indikatorSasaranId]);
    }

    // ==================== BATCH OPERATIONS ====================
    
    public function createCompleteRpjmdTransaction($data)
    {
        $debugFile = WRITEPATH . 'debug_rpjmd.txt';
        
        file_put_contents($debugFile, "\n=== createCompleteRpjmdTransaction START ===\n", FILE_APPEND);
        file_put_contents($debugFile, "Input data: " . print_r($data, true) . "\n", FILE_APPEND);
        
        try {
            $this->db->transStart();

            // Create misi WITHOUT TRANSACTION
            file_put_contents($debugFile, "Creating misi with data: " . print_r($data['misi'], true) . "\n", FILE_APPEND);
            $misiId = $this->createMisi($data['misi']);
            file_put_contents($debugFile, "Misi created with ID: " . $misiId . "\n", FILE_APPEND);
            
            // Verify misi was actually inserted
            $misiCheck = $this->db->table('rpjmd_misi')->where('id', $misiId)->get()->getRowArray();
            file_put_contents($debugFile, "Misi verification: " . print_r($misiCheck, true) . "\n", FILE_APPEND);
            
            if (isset($data['tujuan']) && is_array($data['tujuan'])) {
                foreach ($data['tujuan'] as $tujuanData) {
                    $tujuanData['misi_id'] = $misiId;
                    file_put_contents($debugFile, "Creating tujuan with data: " . print_r($tujuanData, true) . "\n", FILE_APPEND);
                    $tujuanId = $this->createTujuan($tujuanData);
                    file_put_contents($debugFile, "Tujuan created with ID: " . $tujuanId . "\n", FILE_APPEND);
                    
                    // Verify tujuan was actually inserted
                    $tujuanCheck = $this->db->table('rpjmd_tujuan')->where('id', $tujuanId)->get()->getRowArray();
                    file_put_contents($debugFile, "Tujuan verification: " . print_r($tujuanCheck, true) . "\n", FILE_APPEND);
                    
                    // Create indikator tujuan
                    if (isset($tujuanData['indikator_tujuan']) && is_array($tujuanData['indikator_tujuan'])) {
                        foreach ($tujuanData['indikator_tujuan'] as $indikatorTujuan) {
                            $indikatorTujuan['tujuan_id'] = $tujuanId;
                            file_put_contents($debugFile, "Creating indikator tujuan: " . print_r($indikatorTujuan, true) . "\n", FILE_APPEND);
                            $this->createIndikatorTujuan($indikatorTujuan);
                        }
                    }
                    
                    // Create sasaran
                    if (isset($tujuanData['sasaran']) && is_array($tujuanData['sasaran'])) {
                        foreach ($tujuanData['sasaran'] as $sasaranData) {
                            $sasaranData['tujuan_id'] = $tujuanId;
                            file_put_contents($debugFile, "Creating sasaran: " . print_r($sasaranData, true) . "\n", FILE_APPEND);
                            $sasaranId = $this->createSasaran($sasaranData);
                            file_put_contents($debugFile, "Sasaran created with ID: " . $sasaranId . "\n", FILE_APPEND);
                            
                            // Create indikator sasaran
                            if (isset($sasaranData['indikator_sasaran']) && is_array($sasaranData['indikator_sasaran'])) {
                                foreach ($sasaranData['indikator_sasaran'] as $indikatorSasaran) {
                                    $indikatorSasaran['sasaran_id'] = $sasaranId;
                                    file_put_contents($debugFile, "Creating indikator sasaran: " . print_r($indikatorSasaran, true) . "\n", FILE_APPEND);
                                    $indikatorId = $this->createIndikatorSasaran($indikatorSasaran);
                                    file_put_contents($debugFile, "Indikator sasaran created with ID: " . $indikatorId . "\n", FILE_APPEND);
                                    
                                    // Create target tahunan
                                    if (isset($indikatorSasaran['target_tahunan']) && is_array($indikatorSasaran['target_tahunan'])) {
                                        foreach ($indikatorSasaran['target_tahunan'] as $target) {
                                            $target['indikator_sasaran_id'] = $indikatorId;
                                            file_put_contents($debugFile, "Creating target: " . print_r($target, true) . "\n", FILE_APPEND);
                                            $targetId = $this->createTargetTahunan($target);
                                            file_put_contents($debugFile, "Target created with ID: " . $targetId . "\n", FILE_APPEND);
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }

            $this->db->transComplete();
            
            if ($this->db->transStatus() === false) {
                throw new \Exception("Database transaction failed. All changes have been rolled back.");
            }

            file_put_contents($debugFile, "=== createCompleteRpjmd END - SUCCESS, MISI ID: $misiId ===\n", FILE_APPEND);
            return $misiId;
            
        } catch (\Exception $e) {
            file_put_contents($debugFile, "EXCEPTION in createCompleteRpjmdTransaction: " . $e->getMessage() . "\n", FILE_APPEND);
            file_put_contents($debugFile, "Stack trace: " . $e->getTraceAsString() . "\n", FILE_APPEND);

            $this->db->transRollback();

            throw $e;
        }
    }
    
    public function updateCompleteRpjmdTransaction($misiId, $data)
    {
        $debugFile = WRITEPATH . 'debug_update.txt';
        
        file_put_contents($debugFile, "\n=== updateCompleteRpjmdTransaction START ===\n", FILE_APPEND);
        file_put_contents($debugFile, "Misi ID: " . $misiId . "\n", FILE_APPEND);
        file_put_contents($debugFile, "Input data: " . print_r($data, true) . "\n", FILE_APPEND);
        
        try {
            $this->db->transStart();

            // Update misi
            if (isset($data['misi'])) {
                file_put_contents($debugFile, "Updating misi with data: " . print_r($data['misi'], true) . "\n", FILE_APPEND);
                $this->updateMisi($misiId, $data['misi']);
            }
            
            // Get existing tujuan IDs to track which ones to keep
            $existingTujuanIds = array_column($this->getTujuanByMisiId($misiId), 'id');
            $processedTujuanIds = [];
            
            // Process tujuan data
            if (isset($data['tujuan']) && is_array($data['tujuan'])) {
                foreach ($data['tujuan'] as $tujuanData) {
                    if (!empty($tujuanData['tujuan_rpjmd'])) {
                        $tujuanInfo = [
                            'misi_id' => $misiId,
                            'tujuan_rpjmd' => $tujuanData['tujuan_rpjmd']
                        ];
                        
                        if (isset($tujuanData['id']) && !empty($tujuanData['id'])) {
                            // Update existing tujuan
                            file_put_contents($debugFile, "Updating tujuan ID " . $tujuanData['id'] . " with data: " . print_r($tujuanInfo, true) . "\n", FILE_APPEND);
                            $this->updateTujuan($tujuanData['id'], $tujuanInfo);
                            $tujuanId = $tujuanData['id'];
                            $processedTujuanIds[] = $tujuanId;
                        } else {
                            // Create new tujuan
                            file_put_contents($debugFile, "Creating new tujuan with data: " . print_r($tujuanInfo, true) . "\n", FILE_APPEND);
                            $tujuanId = $this->createTujuan($tujuanInfo);
                            $processedTujuanIds[] = $tujuanId;
                        }
                        
                        // Get existing indikator tujuan IDs to track which ones to keep
                        $existingIndikatorTujuanIds = array_column($this->getIndikatorTujuanByTujuanId($tujuanId), 'id');
                        $processedIndikatorTujuanIds = [];
                        
                        // Process indikator tujuan
                        if (isset($tujuanData['indikator_tujuan']) && is_array($tujuanData['indikator_tujuan'])) {
                            foreach ($tujuanData['indikator_tujuan'] as $indikatorData) {
                                if (!empty($indikatorData['indikator_tujuan'])) {
                                    $indikatorInfo = [
                                        'tujuan_id' => $tujuanId,
                                        'indikator_tujuan' => $indikatorData['indikator_tujuan']
                                    ];
                                    
                                    if (isset($indikatorData['id']) && !empty($indikatorData['id'])) {
                                        file_put_contents($debugFile, "Updating indikator tujuan ID " . $indikatorData['id'] . "\n", FILE_APPEND);
                                        $this->updateIndikatorTujuan($indikatorData['id'], $indikatorInfo);
                                        $processedIndikatorTujuanIds[] = $indikatorData['id'];
                                    } else {
                                        file_put_contents($debugFile, "Creating new indikator tujuan\n", FILE_APPEND);
                                        $newIndikatorId = $this->createIndikatorTujuan($indikatorInfo);
                                        $processedIndikatorTujuanIds[] = $newIndikatorId;
                                    }
                                }
                            }
                        }
                        
                        // Delete indikator tujuan that were not processed (removed from form)
                        $toDeleteIndikatorTujuan = array_diff($existingIndikatorTujuanIds, $processedIndikatorTujuanIds);
                        foreach ($toDeleteIndikatorTujuan as $deleteId) {
                            file_put_contents($debugFile, "Deleting indikator tujuan ID " . $deleteId . "\n", FILE_APPEND);
                            $this->deleteIndikatorTujuan($deleteId);
                        }
                        
                        // Get existing sasaran IDs to track which ones to keep
                        $existingSasaranIds = array_column($this->getSasaranByTujuanId($tujuanId), 'id');
                        $processedSasaranIds = [];
                        
                        // Process sasaran data
                        if (isset($tujuanData['sasaran']) && is_array($tujuanData['sasaran'])) {
                            foreach ($tujuanData['sasaran'] as $sasaranData) {
                                if (!empty($sasaranData['sasaran_rpjmd'])) {
                                    $sasaranInfo = [
                                        'tujuan_id' => $tujuanId,
                                        'sasaran_rpjmd' => $sasaranData['sasaran_rpjmd']
                                    ];
                                    
                                    if (isset($sasaranData['id']) && !empty($sasaranData['id'])) {
                                        file_put_contents($debugFile, "Updating sasaran ID " . $sasaranData['id'] . "\n", FILE_APPEND);
                                        $this->updateSasaran($sasaranData['id'], $sasaranInfo);
                                        $sasaranId = $sasaranData['id'];
                                        $processedSasaranIds[] = $sasaranId;
                                    } else {
                                        file_put_contents($debugFile, "Creating new sasaran\n", FILE_APPEND);
                                        $sasaranId = $this->createSasaran($sasaranInfo);
                                        $processedSasaranIds[] = $sasaranId;
                                    }
                                    
                                    // Get existing indikator sasaran IDs to track which ones to keep
                                    $existingIndikatorSasaranIds = array_column($this->getIndikatorSasaranBySasaranId($sasaranId), 'id');
                                    $processedIndikatorSasaranIds = [];
                                    
                                    // Process indikator sasaran
                                    if (isset($sasaranData['indikator_sasaran']) && is_array($sasaranData['indikator_sasaran'])) {
                                        foreach ($sasaranData['indikator_sasaran'] as $indikatorSasaranData) {
                                            if (!empty($indikatorSasaranData['indikator_sasaran'])) {
                                                $indikatorSasaranInfo = [
                                                    'sasaran_id' => $sasaranId,
                                                    'indikator_sasaran' => $indikatorSasaranData['indikator_sasaran'],
                                                    'definisi_op' => $indikatorSasaranData['definisi_op'] ?? '',
                                                    'satuan' => $indikatorSasaranData['satuan'] ?? ''
                                                ];
                                                
                                                if (isset($indikatorSasaranData['id']) && !empty($indikatorSasaranData['id'])) {
                                                    file_put_contents($debugFile, "Updating indikator sasaran ID " . $indikatorSasaranData['id'] . "\n", FILE_APPEND);
                                                    $this->updateIndikatorSasaran($indikatorSasaranData['id'], $indikatorSasaranInfo);
                                                    $indikatorSasaranId = $indikatorSasaranData['id'];
                                                    $processedIndikatorSasaranIds[] = $indikatorSasaranId;
                                                } else {
                                                    file_put_contents($debugFile, "Creating new indikator sasaran\n", FILE_APPEND);
                                                    $indikatorSasaranId = $this->createIndikatorSasaran($indikatorSasaranInfo);
                                                    $processedIndikatorSasaranIds[] = $indikatorSasaranId;
                                                }
                                                
                                                // For existing indikator sasaran, delete all target tahunan first to avoid duplicates
                                                if (isset($indikatorSasaranData['id']) && !empty($indikatorSasaranData['id'])) {
                                                    $this->deleteTargetTahunanByIndikatorId($indikatorSasaranId);
                                                }
                                                
                                                // Process target tahunan
                                                if (isset($indikatorSasaranData['target_tahunan']) && is_array($indikatorSasaranData['target_tahunan'])) {
                                                    foreach ($indikatorSasaranData['target_tahunan'] as $targetData) {
                                                        // Create target tahunan if tahun exists (regardless of target_tahunan value)
                                                        if (isset($targetData['tahun']) && $targetData['tahun'] !== '') {
                                                            $targetInfo = [
                                                                'indikator_sasaran_id' => $indikatorSasaranId,
                                                                'tahun' => $targetData['tahun'],
                                                                'target_tahunan' => $targetData['target_tahunan'] ?? ''
                                                            ];
                                                            
                                                            file_put_contents($debugFile, "Creating target tahunan: " . print_r($targetInfo, true) . "\n", FILE_APPEND);
                                                            $this->createTargetTahunan($targetInfo);
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                    
                                    // Delete indikator sasaran that were not processed (removed from form)
                                    $toDeleteIndikatorSasaran = array_diff($existingIndikatorSasaranIds, $processedIndikatorSasaranIds);
                                    foreach ($toDeleteIndikatorSasaran as $deleteId) {
                                        file_put_contents($debugFile, "Deleting indikator sasaran ID " . $deleteId . "\n", FILE_APPEND);
                                        $this->deleteIndikatorSasaran($deleteId);
                                    }
                                }
                            }
                        }
                        
                        // Delete sasaran that were not processed (removed from form)
                        $toDeleteSasaran = array_diff($existingSasaranIds, $processedSasaranIds);
                        foreach ($toDeleteSasaran as $deleteId) {
                            file_put_contents($debugFile, "Deleting sasaran ID " . $deleteId . "\n", FILE_APPEND);
                            $this->deleteSasaran($deleteId);
                        }
                    }
                }
            }
            
            // Delete tujuan that were not processed (removed from form)
            $toDeleteTujuan = array_diff($existingTujuanIds, $processedTujuanIds);
            foreach ($toDeleteTujuan as $deleteId) {
                file_put_contents($debugFile, "Deleting tujuan ID " . $deleteId . "\n", FILE_APPEND);
                $this->deleteTujuan($deleteId);
            }
            
            // Clean up any orphaned records
            $this->cleanupOrphanedRecords($misiId);

            $this->db->transComplete();
            
            if ($this->db->transStatus() === false) {
                throw new \Exception("Database transaction failed. All changes have been rolled back.");
            }

            file_put_contents($debugFile, "=== updateCompleteRpjmdTransaction END - SUCCESS ===\n", FILE_APPEND);
            return true;
            
        } catch (\Exception $e) {
            file_put_contents($debugFile, "EXCEPTION in updateCompleteRpjmdTransaction: " . $e->getMessage() . "\n", FILE_APPEND);
            file_put_contents($debugFile, "Stack trace: " . $e->getTraceAsString() . "\n", FILE_APPEND);

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
     * Clean up orphaned records after update
     */
    public function cleanupOrphanedRecords($misiId)
    {
        // Clean up indikator_tujuan that have no parent tujuan
        $this->db->query("
            DELETE it FROM rpjmd_indikator_tujuan it 
            LEFT JOIN rpjmd_tujuan t ON t.id = it.tujuan_id 
            LEFT JOIN rpjmd_misi m ON m.id = t.misi_id 
            WHERE m.id = ? AND t.id IS NULL
        ", [$misiId]);
        
        // Clean up sasaran that have no parent tujuan
        $this->db->query("
            DELETE s FROM rpjmd_sasaran s 
            LEFT JOIN rpjmd_tujuan t ON t.id = s.tujuan_id 
            LEFT JOIN rpjmd_misi m ON m.id = t.misi_id 
            WHERE m.id = ? AND t.id IS NULL
        ", [$misiId]);
        
        // Clean up indikator_sasaran that have no parent sasaran
        $this->db->query("
            DELETE iss FROM rpjmd_indikator_sasaran iss 
            LEFT JOIN rpjmd_sasaran s ON s.id = iss.sasaran_id 
            LEFT JOIN rpjmd_tujuan t ON t.id = s.tujuan_id 
            LEFT JOIN rpjmd_misi m ON m.id = t.misi_id 
            WHERE m.id = ? AND s.id IS NULL
        ", [$misiId]);
        
        // Clean up target that have no parent indikator_sasaran
        $this->db->query("
            DELETE tt FROM rpjmd_target tt 
            LEFT JOIN rpjmd_indikator_sasaran iss ON iss.id = tt.indikator_sasaran_id 
            LEFT JOIN rpjmd_sasaran s ON s.id = iss.sasaran_id 
            LEFT JOIN rpjmd_tujuan t ON t.id = s.tujuan_id 
            LEFT JOIN rpjmd_misi m ON m.id = t.misi_id 
            WHERE m.id = ? AND iss.id IS NULL
        ", [$misiId]);
    }
    
    /**
     * Helper methods to find parent misi ID for any entity
     */
    
    public function findMisiIdByTujuanId($tujuanId)
    {
        $tujuan = $this->db->table('rpjmd_tujuan')
            ->where('id', $tujuanId)
            ->get()
            ->getRowArray();
        
        return $tujuan ? $tujuan['misi_id'] : null;
    }
    
    public function findMisiIdBySasaranId($sasaranId)
    {
        $result = $this->db->table('rpjmd_sasaran s')
            ->join('rpjmd_tujuan t', 't.id = s.tujuan_id')
            ->where('s.id', $sasaranId)
            ->select('t.misi_id')
            ->get()
            ->getRowArray();
        
        return $result ? $result['misi_id'] : null;
    }
    
    public function findMisiIdByIndikatorSasaranId($indikatorId)
    {
        $result = $this->db->table('rpjmd_indikator_sasaran is')
            ->join('rpjmd_sasaran s', 's.id = is.sasaran_id')
            ->join('rpjmd_tujuan t', 't.id = s.tujuan_id')
            ->where('is.id', $indikatorId)
            ->select('t.misi_id')
            ->get()
            ->getRowArray();
        
        return $result ? $result['misi_id'] : null;
    }
    
    public function findMisiIdByTargetId($targetId)
    {
        $result = $this->db->table('rpjmd_target tt')
            ->join('rpjmd_indikator_sasaran is', 'is.id = tt.indikator_sasaran_id')
            ->join('rpjmd_sasaran s', 's.id = is.sasaran_id')
            ->join('rpjmd_tujuan t', 't.id = s.tujuan_id')
            ->where('tt.id', $targetId)
            ->select('t.misi_id')
            ->get()
            ->getRowArray();
        
        return $result ? $result['misi_id'] : null;
    }
    
    /**
     * Smart method to find misi ID for any entity ID
     * Tries to determine what type of entity the ID belongs to and find the parent misi
     */
    public function findMisiIdForAnyEntity($id)
    {
        // First try as misi ID
        $misi = $this->getMisiById($id);
        if ($misi) {
            return $id;
        }
        
        // Try as tujuan ID
        $misiId = $this->findMisiIdByTujuanId($id);
        if ($misiId) {
            return $misiId;
        }
        
        // Try as sasaran ID
        $misiId = $this->findMisiIdBySasaranId($id);
        if ($misiId) {
            return $misiId;
        }
        
        // Try as indikator sasaran ID
        $misiId = $this->findMisiIdByIndikatorSasaranId($id);
        if ($misiId) {
            return $misiId;
        }
        
        // Try as target ID
        $misiId = $this->findMisiIdByTargetId($id);
        if ($misiId) {
            return $misiId;
        }
        
        return null; // ID not found in any table
    }

    /**
     * Get complete RPJMD hierarchy structure for completed RPJMD only
     */
    public function getCompletedRpjmdStructure()
    {
        $misiList = $this->getCompletedMisi();
        
        foreach ($misiList as &$misi) {
            $misi['tujuan'] = $this->getTujuanByMisiId($misi['id']);
            
            if (!empty($misi['tujuan']) && is_array($misi['tujuan'])) {
                foreach ($misi['tujuan'] as &$tujuan) {
                    $tujuan['indikator_tujuan'] = $this->getIndikatorTujuanByTujuanId($tujuan['id']);
                    $tujuan['sasaran'] = $this->getSasaranByTujuanId($tujuan['id']);
                    
                    if (!empty($tujuan['sasaran']) && is_array($tujuan['sasaran'])) {
                        foreach ($tujuan['sasaran'] as &$sasaran) {
                            $sasaran['indikator_sasaran'] = $this->getIndikatorSasaranBySasaranId($sasaran['id']);
                            
                            if (!empty($sasaran['indikator_sasaran']) && is_array($sasaran['indikator_sasaran'])) {
                                foreach ($sasaran['indikator_sasaran'] as &$indikator) {
                                    $indikator['target_tahunan'] = $this->getTargetTahunanByIndikatorId($indikator['id']);
                                }
                            }
                        }
                    }
                }
            }
        }
        
        return $misiList;
    }
}