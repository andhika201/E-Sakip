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
            ->select('s.*, t.tujuan_rpjmd, m.misi, m.tahun_mulai, m.tahun_akhir')
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
        
        $insertData = [
            'misi' => $data['misi'],
            'tahun_mulai' => $data['tahun_mulai'],  
            'tahun_akhir' => $data['tahun_akhir'],
            'status' => $data['status'] ?? 'draft'
        ];
                
        $result = $this->db->table('rpjmd_misi')->insert($insertData);
        $insertId = $this->db->insertID();
                
        if (!$result) {
            $error = $this->db->error();
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
    public function deleteMisi($id, $internal = false)
    {
        if (!$internal) {
            $this->db->transStart();
        }
        
        try {
            // Get all related data for cascade delete
            $tujuanList = $this->getTujuanByMisiId($id);
            
            foreach ($tujuanList as $tujuan) {
                $this->deleteTujuan($tujuan['id'], true);
            }
            
            // Delete the misi
            $result = $this->db->table('rpjmd_misi')->delete(['id' => $id]);
            
            if (!$internal) {
                $this->db->transComplete();
            }
            return $result;
            
        } catch (\Exception $e) {
            if (!$internal) {
                $this->db->transRollback();
            }
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
        
        $insertData = [
            'misi_id' => $data['misi_id'],
            'tujuan_rpjmd' => $data['tujuan_rpjmd']
        ];
        
        $result = $this->db->table('rpjmd_tujuan')->insert($insertData);
        $insertId = $this->db->insertID();
 
        if (!$result) {
            $error = $this->db->error();
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
    public function deleteTujuan($id, $internal = false)
    {
        if (!$internal) {
            $this->db->transStart();
        }
        
        try {
            // Delete related indikator tujuan
            $this->db->table('rpjmd_indikator_tujuan')->delete(['tujuan_id' => $id]);
            
            // Get and delete related sasaran
            $sasaranList = $this->getSasaranByTujuanId($id);

            foreach ($sasaranList as $sasaran) {
                $this->deleteSasaran($sasaran['id'], true);
            }
            
            // Delete the tujuan
            $result = $this->db->table('rpjmd_tujuan')->delete(['id' => $id]);

            if (!$internal) {
                $this->db->transComplete();
                
                if ($this->db->transStatus() === false) {
                    throw new \Exception("Transaction failed during tujuan deletion");
                }
            }
            return $result;
            
        } catch (\Exception $e) {
            if (!$internal) {
                $this->db->transRollback();
            }
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
        
        $result = $this->db->table('rpjmd_indikator_tujuan')->insert($insertData);
        $insertId = $this->db->insertID();
        
        if (!$result) {
            $error = $this->db->error();
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
        
        $result = $this->db->table('rpjmd_sasaran')->insert($insertData);
        $insertId = $this->db->insertID();
        
        if (!$result) {
            $error = $this->db->error();
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
    public function deleteSasaran($id, $internal = false)
    {
        if (!$internal) {
            $this->db->transStart();
        }
        
        try {
            // Get and delete related indikator sasaran
            $indikatorList = $this->getIndikatorSasaranBySasaranId($id);
            foreach ($indikatorList as $indikator) {
                $this->deleteIndikatorSasaran($indikator['id'], true);
            }

            // Delete related data in other tables that reference this sasaran
            
            // First handle RENSTRA cascade delete (similar to RenstraModel approach)
            $renstraSasaranList = $this->db->table('renstra_sasaran')->where('rpjmd_sasaran_id', $id)->get()->getResultArray();
            foreach ($renstraSasaranList as $renstraSasaran) {
                $renstraSasaranId = $renstraSasaran['id'];
                
                // Delete RENJA data that references this RENSTRA sasaran
                $renjaSasaranList = $this->db->table('renja_sasaran')
                    ->where('renstra_sasaran_id', $renstraSasaranId)
                    ->get()
                    ->getResultArray();
                
                foreach ($renjaSasaranList as $renjaSasaran) {
                    // Delete RENJA indikator sasaran first
                    $this->db->table('renja_indikator_sasaran')
                        ->where('renja_sasaran_id', $renjaSasaran['id'])
                        ->delete();
                }
                
                // Delete all RENJA sasaran that reference this RENSTRA sasaran
                $this->db->table('renja_sasaran')
                    ->where('renstra_sasaran_id', $renstraSasaranId)
                    ->delete();
                
                // Delete RENSTRA targets first
                $renstraIndikatorList = $this->db->table('renstra_indikator_sasaran')
                    ->where('renstra_sasaran_id', $renstraSasaranId)
                    ->get()
                    ->getResultArray();
                
                foreach ($renstraIndikatorList as $renstraIndikator) {
                    $this->db->table('renstra_target')
                        ->where('renstra_indikator_id', $renstraIndikator['id'])
                        ->delete();
                }
                
                // Delete RENSTRA indikator sasaran
                $this->db->table('renstra_indikator_sasaran')
                    ->where('renstra_sasaran_id', $renstraSasaranId)
                    ->delete();
            }
            
            // Delete RENSTRA sasaran
            $this->db->table('renstra_sasaran')->delete(['rpjmd_sasaran_id' => $id]);
            
            // For RKPD, we need to delete rkpd_indikator_sasaran first, then rkpd_sasaran
            $rkpdSasaranList = $this->db->table('rkpd_sasaran')->where('rpjmd_sasaran_id', $id)->get()->getResultArray();
            foreach ($rkpdSasaranList as $rkpdSasaran) {
                $rkpdSasaranId = $rkpdSasaran['id'];
                $this->db->table('rkpd_indikator_sasaran')->delete(['rkpd_sasaran_id' => $rkpdSasaranId]);
            }
            
            $this->db->table('rkpd_sasaran')->delete(['rpjmd_sasaran_id' => $id]);

            // Delete the sasaran
            $result = $this->db->table('rpjmd_sasaran')->delete(['id' => $id]);

            if (!$internal) {
                $this->db->transComplete();
                
                if ($this->db->transStatus() === false) {
                    throw new \Exception("Transaction failed during sasaran deletion");
                }
            }
            
            return $result;
            
        } catch (\Exception $e) {
            if (!$internal) {
                $this->db->transRollback();
            }
            throw $e;
        }
    }

    // ==================== CRUD OPERATIONS FOR RPJMD INDIKATOR SASARAN ====================
    
    /**
     * Create new RPJMD Indikator Sasaran
     */
    public function createIndikatorSasaran($data)
    {        
        // Debug logging
        $debugFile = WRITEPATH . 'debug_rpjmd_model.txt';
        file_put_contents($debugFile, "=== CREATE INDIKATOR SASARAN - " . date('Y-m-d H:i:s') . " ===\n", FILE_APPEND);
        file_put_contents($debugFile, "Input data: " . print_r($data, true) . "\n", FILE_APPEND);
        
        // Validation
        $required = ['sasaran_id', 'indikator_sasaran', 'definisi_op', 'satuan'];
        foreach ($required as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                $error = "Field {$field} harus diisi";
                file_put_contents($debugFile, "VALIDATION ERROR: {$error}\n", FILE_APPEND);
                throw new \InvalidArgumentException($error);
            }
        }
        
        $insertData = [
            'sasaran_id' => $data['sasaran_id'],
            'indikator_sasaran' => $data['indikator_sasaran'],
            'definisi_op' => $data['definisi_op'],
            'satuan' => $data['satuan']
        ];
        
        file_put_contents($debugFile, "Insert data: " . print_r($insertData, true) . "\n", FILE_APPEND);
                
        $result = $this->db->table('rpjmd_indikator_sasaran')->insert($insertData);
        $insertId = $this->db->insertID();
        
        file_put_contents($debugFile, "Insert result: " . ($result ? 'TRUE' : 'FALSE') . "\n", FILE_APPEND);
        file_put_contents($debugFile, "Insert ID: " . $insertId . "\n", FILE_APPEND);
        
        if (!$result) {
            $error = $this->db->error();
            file_put_contents($debugFile, "DATABASE ERROR: " . print_r($error, true) . "\n", FILE_APPEND);
            throw new \Exception("Failed to insert indikator sasaran: " . $error['message']);
        }
        
        file_put_contents($debugFile, "=== END CREATE INDIKATOR SASARAN ===\n\n", FILE_APPEND);
        
        return $insertId;
    }
    
    /**
     * Update RPJMD Indikator Sasaran
     */
    public function updateIndikatorSasaran($id, $data)
    {
        // Debug logging
        $debugFile = WRITEPATH . 'debug_rpjmd_model.txt';
        file_put_contents($debugFile, "=== UPDATE INDIKATOR SASARAN - " . date('Y-m-d H:i:s') . " ===\n", FILE_APPEND);
        file_put_contents($debugFile, "Indikator Sasaran ID: {$id}\n", FILE_APPEND);
        file_put_contents($debugFile, "Update data: " . print_r($data, true) . "\n", FILE_APPEND);
        
        $result = $this->db->table('rpjmd_indikator_sasaran')->where('id', $id)->update($data);
        
        file_put_contents($debugFile, "Update result: " . ($result ? 'TRUE' : 'FALSE') . "\n", FILE_APPEND);
        file_put_contents($debugFile, "Affected rows: " . $this->db->affectedRows() . "\n", FILE_APPEND);
        
        if (!$result) {
            $error = $this->db->error();
            file_put_contents($debugFile, "UPDATE ERROR: " . print_r($error, true) . "\n", FILE_APPEND);
        }
        
        file_put_contents($debugFile, "=== END UPDATE INDIKATOR SASARAN ===\n\n", FILE_APPEND);
        
        return $result;
    }
    
    /**
     * Delete RPJMD Indikator Sasaran (with cascade delete)
     */
    public function deleteIndikatorSasaran($id, $internal = false)
    {
        if (!$internal) {
            $this->db->transStart();
        }

        try {
            // Delete related target tahunan
            $this->db->table('rpjmd_target')->delete(['indikator_sasaran_id' => $id]);

            // Delete the indikator sasaran
            $result = $this->db->table('rpjmd_indikator_sasaran')->delete(['id' => $id]);
            
            if (!$internal) {
                $this->db->transComplete();
                
                if ($this->db->transStatus() === false) {
                    throw new \Exception("Transaction failed during indikator sasaran deletion");
                }
            }
            return $result;
            
        } catch (\Exception $e) {
            if (!$internal) {
                $this->db->transRollback();
            }
            throw $e;
        }
    }

    // ==================== CRUD OPERATIONS FOR RPJMD TARGET TAHUNAN ====================
    
    /**
     * Create new RPJMD Target Tahunan
     */
    public function createTargetTahunan($data)
    {        
        // Debug logging
        $debugFile = WRITEPATH . 'debug_rpjmd_model.txt';
        file_put_contents($debugFile, "=== CREATE TARGET TAHUNAN - " . date('Y-m-d H:i:s') . " ===\n", FILE_APPEND);
        file_put_contents($debugFile, "Input data: " . print_r($data, true) . "\n", FILE_APPEND);
        
        // Validation
        $required = ['indikator_sasaran_id', 'tahun'];
        foreach ($required as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                $error = "Field {$field} harus diisi";
                file_put_contents($debugFile, "VALIDATION ERROR: {$error}\n", FILE_APPEND);
                throw new \InvalidArgumentException($error);
            }
        }
        
        // target_tahunan is optional and can be empty
        $insertData = [
            'indikator_sasaran_id' => $data['indikator_sasaran_id'],
            'tahun' => $data['tahun'],
            'target_tahunan' => $data['target_tahunan'] ?? ''
        ];
        
        file_put_contents($debugFile, "Insert data: " . print_r($insertData, true) . "\n", FILE_APPEND);
        
        $result = $this->db->table('rpjmd_target')->insert($insertData);
        $insertId = $this->db->insertID();
        
        file_put_contents($debugFile, "Insert result: " . ($result ? 'TRUE' : 'FALSE') . "\n", FILE_APPEND);
        file_put_contents($debugFile, "Insert ID: " . $insertId . "\n", FILE_APPEND);
        
        if (!$result) {
            $error = $this->db->error();
            file_put_contents($debugFile, "DATABASE ERROR: " . print_r($error, true) . "\n", FILE_APPEND);
            throw new \Exception("Failed to insert target tahunan: " . $error['message']);
        }
        
        file_put_contents($debugFile, "=== END CREATE TARGET TAHUNAN ===\n\n", FILE_APPEND);
        
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
        // Debug logging
        $debugFile = WRITEPATH . 'debug_rpjmd_model.txt';
        file_put_contents($debugFile, "=== DELETE TARGET TAHUNAN BY INDIKATOR ID - " . date('Y-m-d H:i:s') . " ===\n", FILE_APPEND);
        file_put_contents($debugFile, "Indikator Sasaran ID: {$indikatorSasaranId}\n", FILE_APPEND);
        
        // First check how many records exist
        $existingCount = $this->db->table('rpjmd_target')
            ->where('indikator_sasaran_id', $indikatorSasaranId)
            ->countAllResults();
        
        file_put_contents($debugFile, "Existing targets to delete: {$existingCount}\n", FILE_APPEND);
        
        $result = $this->db->table('rpjmd_target')->delete(['indikator_sasaran_id' => $indikatorSasaranId]);
        
        file_put_contents($debugFile, "Delete result: " . ($result ? 'TRUE' : 'FALSE') . "\n", FILE_APPEND);
        file_put_contents($debugFile, "Affected rows: " . $this->db->affectedRows() . "\n", FILE_APPEND);
        file_put_contents($debugFile, "=== END DELETE TARGET TAHUNAN ===\n\n", FILE_APPEND);
        
        return $result;
    }

    // ==================== BATCH OPERATIONS ====================
    
    public function createCompleteRpjmdTransaction($data)
    {
       
        try {
            $this->db->transStart();

            // Create misi
            $misiId = $this->createMisi($data['misi']);
            
            // Verify misi was actually inserted
            $misiCheck = $this->db->table('rpjmd_misi')->where('id', $misiId)->get()->getRowArray();
            
            if (isset($data['tujuan']) && is_array($data['tujuan'])) {
                foreach ($data['tujuan'] as $tujuanData) {
                    $tujuanData['misi_id'] = $misiId;
                    $tujuanId = $this->createTujuan($tujuanData);
                    
                    // Verify tujuan was actually inserted
                    $tujuanCheck = $this->db->table('rpjmd_tujuan')->where('id', $tujuanId)->get()->getRowArray();
                    
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
                                            $targetId = $this->createTargetTahunan($target);
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

            return $misiId;
            
        } catch (\Exception $e) {
        
            $this->db->transRollback();

            throw $e;
        }
    }
    
    public function updateCompleteRpjmdTransaction($misiId, $data)
    {
        // Debug logging
        $debugFile = WRITEPATH . 'debug_rpjmd_model.txt';
        file_put_contents($debugFile, "\n=== UPDATE COMPLETE RPJMD TRANSACTION - " . date('Y-m-d H:i:s') . " ===\n", FILE_APPEND);
        file_put_contents($debugFile, "Misi ID: {$misiId}\n", FILE_APPEND);
        file_put_contents($debugFile, "Input data structure:\n" . print_r($data, true) . "\n", FILE_APPEND);
       
        try {
            $this->db->transStart();

            // Update misi
            if (isset($data['misi'])) {
                $this->updateMisi($misiId, $data['misi']);
            }
            
            // Get existing tujuan IDs to track which ones to keep
            $existingTujuanIds = array_column($this->getTujuanByMisiId($misiId), 'id');
            $processedTujuanIds = [];
            
            file_put_contents($debugFile, "Existing tujuan IDs: " . print_r($existingTujuanIds, true) . "\n", FILE_APPEND);
            
            // Process tujuan data
            if (isset($data['tujuan']) && is_array($data['tujuan'])) {
                file_put_contents($debugFile, "Processing " . count($data['tujuan']) . " tujuan items\n", FILE_APPEND);
                
                foreach ($data['tujuan'] as $tujuanIndex => $tujuanData) {
                    file_put_contents($debugFile, "\n--- Processing Tujuan [{$tujuanIndex}] ---\n", FILE_APPEND);
                    file_put_contents($debugFile, "Tujuan data: " . print_r($tujuanData, true) . "\n", FILE_APPEND);
                    
                    if (!empty($tujuanData['tujuan_rpjmd'])) {
                        $tujuanInfo = [
                            'misi_id' => $misiId,
                            'tujuan_rpjmd' => $tujuanData['tujuan_rpjmd']
                        ];
                        
                        if (isset($tujuanData['id']) && !empty($tujuanData['id'])) {
                            // Update existing tujuan
                            file_put_contents($debugFile, "UPDATING existing tujuan ID: {$tujuanData['id']}\n", FILE_APPEND);
                            $this->updateTujuan($tujuanData['id'], $tujuanInfo);
                            $tujuanId = $tujuanData['id'];
                            $processedTujuanIds[] = $tujuanId;
                        } else {
                            // Create new tujuan
                            file_put_contents($debugFile, "CREATING new tujuan\n", FILE_APPEND);
                            $tujuanId = $this->createTujuan($tujuanInfo);
                            $processedTujuanIds[] = $tujuanId;
                            file_put_contents($debugFile, "New tujuan created with ID: {$tujuanId}\n", FILE_APPEND);
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
                                        $this->updateIndikatorTujuan($indikatorData['id'], $indikatorInfo);
                                        $processedIndikatorTujuanIds[] = $indikatorData['id'];
                                    } else {
                                        $newIndikatorId = $this->createIndikatorTujuan($indikatorInfo);
                                        $processedIndikatorTujuanIds[] = $newIndikatorId;
                                    }
                                }
                            }
                        }
                        
                        // Delete indikator tujuan that were not processed (removed from form)
                        $toDeleteIndikatorTujuan = array_diff($existingIndikatorTujuanIds, $processedIndikatorTujuanIds);
                        foreach ($toDeleteIndikatorTujuan as $deleteId) {
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
                                        $this->updateSasaran($sasaranData['id'], $sasaranInfo);
                                        $sasaranId = $sasaranData['id'];
                                        $processedSasaranIds[] = $sasaranId;
                                    } else {
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
                                                    $this->updateIndikatorSasaran($indikatorSasaranData['id'], $indikatorSasaranInfo);
                                                    $indikatorSasaranId = $indikatorSasaranData['id'];
                                                    $processedIndikatorSasaranIds[] = $indikatorSasaranId;
                                                } else {
                                                    $indikatorSasaranId = $this->createIndikatorSasaran($indikatorSasaranInfo);
                                                    $processedIndikatorSasaranIds[] = $indikatorSasaranId;
                                                }
                                                
                                                // For existing indikator sasaran, delete all target tahunan first to avoid duplicates
                                                if (isset($indikatorSasaranData['id']) && !empty($indikatorSasaranData['id'])) {
                                                    file_put_contents($debugFile, "Deleting existing targets for indikator sasaran ID: {$indikatorSasaranId}\n", FILE_APPEND);
                                                    $deleteResult = $this->deleteTargetTahunanByIndikatorId($indikatorSasaranId);
                                                    file_put_contents($debugFile, "Delete result: " . ($deleteResult ? 'SUCCESS' : 'FAILED') . "\n", FILE_APPEND);
                                                }
                                                
                                                // Process target tahunan
                                                if (isset($indikatorSasaranData['target_tahunan']) && is_array($indikatorSasaranData['target_tahunan'])) {
                                                    file_put_contents($debugFile, "Processing " . count($indikatorSasaranData['target_tahunan']) . " targets for indikator sasaran ID: {$indikatorSasaranId}\n", FILE_APPEND);
                                                    
                                                    // Get misi data to determine year range
                                                    $misi = $this->getMisiById($misiId);
                                                    $startYear = $misi['tahun_mulai'] ?? 2025;
                                                    
                                                    foreach ($indikatorSasaranData['target_tahunan'] as $targetIndex => $targetData) {
                                                        file_put_contents($debugFile, "Target [{$targetIndex}]: " . print_r($targetData, true) . "\n", FILE_APPEND);
                                                        
                                                        // Check if tahun is missing and generate it from index
                                                        $tahun = $targetData['tahun'] ?? '';
                                                        if (empty($tahun) && is_numeric($targetIndex)) {
                                                            $tahun = $startYear + $targetIndex;
                                                            file_put_contents($debugFile, "Generated tahun from index: {$tahun} (startYear: {$startYear} + index: {$targetIndex})\n", FILE_APPEND);
                                                        }
                                                        
                                                        // Create target tahunan if we have a valid year (either from data or generated)
                                                        if (!empty($tahun)) {
                                                            $targetInfo = [
                                                                'indikator_sasaran_id' => $indikatorSasaranId,
                                                                'tahun' => $tahun,
                                                                'target_tahunan' => $targetData['target_tahunan'] ?? ''
                                                            ];
                                                            
                                                            file_put_contents($debugFile, "Creating target with data: " . print_r($targetInfo, true) . "\n", FILE_APPEND);
                                                            $targetId = $this->createTargetTahunan($targetInfo);
                                                            file_put_contents($debugFile, "Target created with ID: {$targetId}\n", FILE_APPEND);
                                                        } else {
                                                            file_put_contents($debugFile, "Skipping target - unable to determine tahun (original: '{$targetData['tahun']}', index: {$targetIndex})\n", FILE_APPEND);
                                                        }
                                                    }
                                                } else {
                                                    file_put_contents($debugFile, "No target_tahunan data found for indikator sasaran ID: {$indikatorSasaranId}\n", FILE_APPEND);
                                                }
                                            }
                                        }
                                    }
                                    
                                    // Delete indikator sasaran that were not processed (removed from form)
                                    $toDeleteIndikatorSasaran = array_diff($existingIndikatorSasaranIds, $processedIndikatorSasaranIds);
                                    foreach ($toDeleteIndikatorSasaran as $deleteId) {
                                        $this->deleteIndikatorSasaran($deleteId, true);
                                    }
                                }
                            }
                        }
                        
                        // Delete sasaran that were not processed (removed from form)
                        $toDeleteSasaran = array_diff($existingSasaranIds, $processedSasaranIds);
                        foreach ($toDeleteSasaran as $deleteId) {
                            $this->deleteSasaran($deleteId, true);
                        }
                    }
                }
            }
            
            // Delete tujuan that were not processed (removed from form)
            $toDeleteTujuan = array_diff($existingTujuanIds, $processedTujuanIds);
            file_put_contents($debugFile, "Tujuan to delete: " . print_r($toDeleteTujuan, true) . "\n", FILE_APPEND);
            
            foreach ($toDeleteTujuan as $deleteId) {
                file_put_contents($debugFile, "Deleting tujuan ID: {$deleteId}\n", FILE_APPEND);
                $this->deleteTujuan($deleteId, true);
            }
            
            // Clean up any orphaned records
            file_put_contents($debugFile, "Cleaning up orphaned records for misi ID: {$misiId}\n", FILE_APPEND);
            $this->cleanupOrphanedRecords($misiId);

            $this->db->transComplete();
            
            if ($this->db->transStatus() === false) {
                file_put_contents($debugFile, "ERROR: Database transaction failed!\n", FILE_APPEND);
                throw new \Exception("Database transaction failed. All changes have been rolled back.");
            }
            
            file_put_contents($debugFile, "SUCCESS: Transaction completed successfully\n", FILE_APPEND);
            file_put_contents($debugFile, "=== END UPDATE TRANSACTION ===\n\n", FILE_APPEND);
            
            return true;
            
        } catch (\Exception $e) {
            file_put_contents($debugFile, "EXCEPTION in updateCompleteRpjmdTransaction: " . $e->getMessage() . "\n", FILE_APPEND);
            file_put_contents($debugFile, "Stack trace:\n" . $e->getTraceAsString() . "\n", FILE_APPEND);
            file_put_contents($debugFile, "=== TRANSACTION ROLLED BACK ===\n\n", FILE_APPEND);

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
    public function getSasaranWithIndikatorAndTarget()
    {
        $sasaranList = $this->db->table('rpjmd_sasaran s')
            ->select('s.id, s.sasaran_rpjmd')
            ->orderBy('s.id', 'ASC')
            ->get()
            ->getResultArray();

        foreach ($sasaranList as &$sasaran) {
            // Ambil indikator untuk setiap sasaran
            $indikatorList = $this->db->table('rpjmd_indikator_sasaran i')
                ->select('i.id, i.indikator_sasaran, i.satuan')
                ->where('i.sasaran_id', $sasaran['id'])
                ->get()
                ->getResultArray();

            foreach ($indikatorList as &$indikator) {
                // Ambil target tahunan untuk setiap indikator
                $indikator['target_tahunan'] = $this->db->table('rpjmd_target t')
                    ->select('t.tahun, t.target_tahunan')
                    ->where('t.indikator_sasaran_id', $indikator['id'])
                    ->orderBy('t.tahun', 'ASC')
                    ->get()
                    ->getResultArray();
            }

            $sasaran['indikator_sasaran'] = $indikatorList;
        }

        return $sasaranList;
    }
}