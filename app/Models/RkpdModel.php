<?php

namespace App\Models;

use CodeIgniter\Model;

class RkpdModel extends Model
{
    protected $db;
    
    public function __construct()
    {
        parent::__construct();
        $this->db = \Config\Database::connect();
    }
    
    // ==================== RKPD SASARAN ====================

    /**
     * Get all RKPD Sasaran
     */
    public function getRkpdSasaranById($id) {

        return $this->db->table('rkpd_sasaran')
            ->where('id', $id)
            ->get()
            ->getRowArray();
    }

    /**
     * Get all Completed RKPD Sasaran
     */
    public function getCompletedRkpd()
    {
        return $this->db->table('rkpd_sasaran')
            ->where('status', 'selesai')
            ->orderBy('id', 'ASC')
            ->get()
            ->getResultArray();
    }

    
    // Update status RKPD
    public function updateStatus($id, $status)
    {
        return $this->db->table('rkpd_sasaran')
            ->where('id', $id)
            ->update(['status' => $status]);
    }

    /* * Get All RKPD Data
     * This method retrieves all RKPD data including indicators
     */
    public function getAllRkpd()
    {
        $builder = $this->db->table('rkpd_sasaran');
        $builder->select('
            rkpd_sasaran.id AS rkpd_sasaran_id,
            rkpd_sasaran.rpjmd_sasaran_id,
            rkpd_sasaran.sasaran,
            rkpd_sasaran.status,
            rkpd_indikator_sasaran.id AS indikator_id,
            rkpd_indikator_sasaran.indikator_sasaran,
            rkpd_indikator_sasaran.satuan,
            rkpd_indikator_sasaran.tahun,
            rkpd_indikator_sasaran.target,
            rpjmd_sasaran.sasaran_rpjmd AS rpjmd_sasaran
        ');
        $builder->join('rkpd_indikator_sasaran', 'rkpd_indikator_sasaran.rkpd_sasaran_id = rkpd_sasaran.id', 'left');
        $builder->join('rpjmd_sasaran', 'rpjmd_sasaran.id = rkpd_sasaran.rpjmd_sasaran_id', 'left');
        $builder->orderBy('rkpd_sasaran.id', 'ASC');
        $builder->orderBy('rkpd_indikator_sasaran.tahun', 'ASC');
        
        $query = $builder->get();
        $results = $query->getResultArray();

        // Kelompokkan data berdasarkan rkpd_sasaran_id
        $grouped = [];
        foreach ($results as $row) {
            $id = $row['rkpd_sasaran_id'];
            if (!isset($grouped[$id])) {
                $grouped[$id] = [
                    'id' => $row['rkpd_sasaran_id'],
                    'rpjmd_sasaran_id' => $row['rpjmd_sasaran_id'],
                    'rpjmd_sasaran' => $row['rpjmd_sasaran'],
                    'sasaran' => $row['sasaran'],
                    'status' => $row['status'],
                    'indikator' => []
                ];
            }

            // Tambahkan indikator jika ada
            if (!empty($row['indikator_id'])) {
                $grouped[$id]['indikator'][] = [
                    'id' => $row['indikator_id'],
                    'indikator_sasaran' => $row['indikator_sasaran'],
                    'satuan' => $row['satuan'],
                    'tahun' => $row['tahun'],
                    'target' => $row['target'],
                ];
            }
        }

        return array_values($grouped);
    }

    /* * Get All RKPD Data by Status
     * This method retrieves all Completed RKPD data including indicators
     */
    public function getAllRkpdbyStatus($status = null)
    {
        $builder = $this->db->table('rkpd_sasaran');
        $builder->select('
            rkpd_sasaran.id AS rkpd_sasaran_id,
            rkpd_sasaran.rpjmd_sasaran_id,
            rkpd_sasaran.sasaran,
            rkpd_sasaran.status,
            rkpd_indikator_sasaran.id AS indikator_id,
            rkpd_indikator_sasaran.indikator_sasaran,
            rkpd_indikator_sasaran.satuan,
            rkpd_indikator_sasaran.tahun,
            rkpd_indikator_sasaran.target,
            rpjmd_sasaran.sasaran_rpjmd AS rpjmd_sasaran
        ');
        $builder->join('rkpd_indikator_sasaran', 'rkpd_indikator_sasaran.rkpd_sasaran_id = rkpd_sasaran.id', 'left');
        $builder->join('rpjmd_sasaran', 'rpjmd_sasaran.id = rkpd_sasaran.rpjmd_sasaran_id', 'left');
        if ($status !== null) {
            $builder->where('rkpd_sasaran.status', $status);
        }
        $builder->orderBy('rkpd_sasaran.id', 'ASC');
        $builder->orderBy('rkpd_indikator_sasaran.tahun', 'ASC');
        
        $query = $builder->get();
        $results = $query->getResultArray();

        // Kelompokkan data berdasarkan rkpd_sasaran_id
        $grouped = [];
        foreach ($results as $row) {
            $id = $row['rkpd_sasaran_id'];
            if (!isset($grouped[$id])) {
                $grouped[$id] = [
                    'id' => $row['rkpd_sasaran_id'],
                    'rpjmd_sasaran_id' => $row['rpjmd_sasaran_id'],
                    'rpjmd_sasaran' => $row['rpjmd_sasaran'],
                    'sasaran' => $row['sasaran'],
                    'status' => $row['status'],
                    'indikator' => []
                ];
            }

            // Tambahkan indikator jika ada
            if (!empty($row['indikator_id'])) {
                $grouped[$id]['indikator'][] = [
                    'id' => $row['indikator_id'],
                    'indikator_sasaran' => $row['indikator_sasaran'],
                    'satuan' => $row['satuan'],
                    'tahun' => $row['tahun'],
                    'target' => $row['target'],
                ];
            }
        }

        return array_values($grouped);
    }

    /**
     * Get RKPD data by RPJMD Sasaran ID (for edit)
     */
    public function getRkpdById($id)
    {
        $builder = $this->db->table('rkpd_sasaran');
        $builder->select('
            rkpd_sasaran.id AS rkpd_sasaran_id,
            rkpd_sasaran.rpjmd_sasaran_id,
            rkpd_sasaran.sasaran,
            rkpd_sasaran.status,
            rkpd_indikator_sasaran.id AS indikator_id,
            rkpd_indikator_sasaran.indikator_sasaran,
            rkpd_indikator_sasaran.satuan,
            rkpd_indikator_sasaran.tahun,
            rkpd_indikator_sasaran.target,
            rpjmd_sasaran.id AS rpjmd_sasaran_id,
            rpjmd_sasaran.sasaran_rpjmd AS rpjmd_sasaran
        ');
        $builder->join('rkpd_indikator_sasaran', 'rkpd_indikator_sasaran.rkpd_sasaran_id = rkpd_sasaran.id', 'left');
        $builder->join('rpjmd_sasaran', 'rpjmd_sasaran.id = rkpd_sasaran.rpjmd_sasaran_id', 'left');
        $builder->where('rkpd_sasaran.id', $id);
        $builder->orderBy('rkpd_sasaran.id', 'ASC');
        $builder->orderBy('rkpd_indikator_sasaran.tahun', 'ASC');
        
        $query = $builder->get();
        $results = $query->getResultArray();

        // Kelompokkan data berdasarkan rkpd_sasaran_id
        $grouped = [];
        foreach ($results as $row) {
            $id = $row['rkpd_sasaran_id'];
            if (!isset($grouped[$id])) {
                $grouped[$id] = [
                    'id' => $row['rkpd_sasaran_id'],
                    'rpjmd_sasaran_id' => $row['rpjmd_sasaran_id'],
                    'sasaran' => $row['sasaran'],
                    'status' => $row['status'],
                    'indikator' => []
                ];
            }

            // Tambahkan indikator jika ada
            if (!empty($row['indikator_id'])) {
                $grouped[$id]['indikator'][] = [
                    'id' => $row['indikator_id'],
                    'indikator_sasaran' => $row['indikator_sasaran'],
                    'satuan' => $row['satuan'],
                    'tahun' => $row['tahun'],
                    'target' => $row['target'],
                ];
            }
        }

        return [
            'rpjmd_sasaran_id' => !empty($results) ? $results[0]['rpjmd_sasaran_id'] : '',
            'rpjmd_sasaran' => !empty($results) ? $results[0]['rpjmd_sasaran'] : '',
            'sasaran_rkpd' => array_values($grouped)
        ];
    }

    // ==================== RKPD INDIKATOR SASARAN ====================
    
    /**
     * Get all Indikator Sasaran
     */
    public function getAllIndikatorSasaran()
    {
        return $this->db->table('rkpd_indikator_sasaran ris')
            ->select('ris.*, rs.sasaran as sasaran_nama')
            ->join('rkpd_sasaran rs', 'rs.id = ris.rkpd_sasaran_id')
            ->orderBy('ris.rkpd_sasaran_id', 'ASC')
            ->orderBy('ris.id', 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * Get Indikator Sasaran by Sasaran ID
     */
    public function getIndikatorSasaranBySasaranId($sasaranId)
    {
        return $this->db->table('rkpd_indikator_sasaran')
            ->where('rkpd_sasaran_id', $sasaranId)
            ->get()
            ->getResultArray();
    }

    /**
     * Get Indikator Sasaran by ID
     */
    public function getIndikatorSasaranById($id)
    {
        return $this->db->table('rkpd_indikator_sasaran ris')
            ->select('ris.*, rs.sasaran as sasaran_nama')
            ->join('rkpd_sasaran rs', 'rs.id = ris.rkpd_sasaran_id')
            ->where('ris.id', $id)
            ->get()
            ->getRowArray();
    }


    // ==================== CRUD OPERATIONS FOR RKPD SASARAN ====================

    public function createSasaran($data)
    {
        // Validation
        $required = ['rpjmd_sasaran_id', 'sasaran',];
        foreach ($required as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                throw new \InvalidArgumentException("Field {$field} harus diisi");
            }
        }
        
        $insertData = [
            'rpjmd_sasaran_id' => $data['rpjmd_sasaran_id'],
            'sasaran' => $data['sasaran'],
            'status' => $data['status'] ?? 'draft',
        ];
        
        $result = $this->db->table('rkpd_sasaran')->insert($insertData);
        $insertId = $this->db->insertID();

        if (!$result) {
            $error = $this->db->error();
            throw new \Exception("Failed to insert sasaran: " . $error['message']);
        }
        
        return $insertId;
    }

     /**
     * Update RKPD Sasaran
     */
    public function updateSasaran($id, $data)
    {
        return $this->db->table('rkpd_sasaran')->where('id', $id)->update($data);
    }
    
    /**
     * Delete RKPD Sasaran (with cascade delete)
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
            $result = $this->db->table('rkpd_sasaran')->delete(['id' => $id]);
            
            $this->db->transComplete();
            return $result;
            
        } catch (\Exception $e) {
            $this->db->transRollback();
            throw $e;
        }
    }



    // ==================== CRUD OPERATIONS FOR RKPD INDIKATOR SASARAN ====================

    public function createIndikatorSasaran($data)
    {
        // Validation
        $required = ['rkpd_sasaran_id', 'indikator_sasaran', 'satuan', 'tahun', 'target'];
        foreach ($required as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                throw new \InvalidArgumentException("Field {$field} harus diisi");
            }
        }
        
        $insertData = [
            'rkpd_sasaran_id' => $data['rkpd_sasaran_id'],
            'indikator_sasaran' => $data['indikator_sasaran'],
            'satuan' => $data['satuan'],
            'tahun' => $data['tahun'],
            'target' => $data['target']
        ];
        

        $result = $this->db->table('rkpd_indikator_sasaran')->insert($insertData);
        $insertId = $this->db->insertID();

        if (!$result) {
            $error = $this->db->error();
            throw new \Exception("Failed to insert sasaran: " . $error['message']);
        }
        
        return $insertId;
    }

     /**
     * Update RKPD Indikator Sasaran
     */
    public function updateIndikatorSasaran($id, $data)
    {
        return $this->db->table('rkpd_indikator_sasaran')->where('id', $id)->update($data);
    }
    
    /**
     * Delete RKPD Indikator Sasaran
     */ 
    public function deleteIndikatorSasaran($id)
    {
        try {
            $result = $this->db->table('rkpd_indikator_sasaran')->where('id', $id)->delete();
            if (!$result) {
                $error = $this->db->error();
                log_message('error', 'Failed to delete indikator sasaran ID ' . $id . ': ' . $error['message']);
            }
            return $result;
            
        } catch (\Exception $e) {
            $this->db->transRollback();
            throw $e;
        }
    }

// ==================== COMPLETE RKPD OPERATIONS ====================

    // Menyimpan data RKPD beserta indikator sasaran
    public function createCompleteRkpd($data)
    {
        $this->db->transStart();

        try {
            if (isset($data['sasaran_rkpd']) && is_array($data['sasaran_rkpd'])) {
                foreach ($data['sasaran_rkpd'] as $sasaranData) {

                    // Inject foreign key ke dalam data sasaran
                    $sasaranData['rpjmd_sasaran_id'] = $data['rpjmd_sasaran_id'];
                    $sasaranData['status'] = $sasaranData['status'] ?? 'draft';

                    // Simpan sasaran RKPD
                    $sasaranRkpdId = $this->createSasaran($sasaranData);

                    // Cek dan simpan indikator-indikator sasaran
                    if (isset($sasaranData['indikator_sasaran']) && is_array($sasaranData['indikator_sasaran'])) {
                        foreach ($sasaranData['indikator_sasaran'] as $indikatorData) {

                            // Inject foreign key ke dalam data indikator
                            $indikatorData['rkpd_sasaran_id'] = $sasaranRkpdId;

                            // Simpan indikator
                            $this->createIndikatorSasaran($indikatorData);
                        }
                    }
                }
            }

            $this->db->transComplete();

            if ($this->db->transStatus() === false) {
                throw new \Exception('Transaction failed');
            }

            return true;

        } catch (\Exception $e) {
            $this->db->transRollback();
            log_message('error', 'Error saving RKPD: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Update complete RKPD data with sasaran and indikator
     */
    public function updateCompleteRkpd($rkpdSasaranId, $data)
    {
        try {
            $this->db->transStart();

            // Get the selected RPJMD Sasaran ID from form
            $rpjmdSasaranId = $data['rpjmd_sasaran_id'];
            
            // Get existing sasaran IDs to track which ones to keep
            // Since we're editing a specific RKPD sasaran, we start with that ID
            $existingSasaranIds = [$rkpdSasaranId];
            
            $processedSasaranIds = [];

            // Process sasaran data
            if (isset($data['sasaran_rkpd']) && is_array($data['sasaran_rkpd'])) {
                foreach ($data['sasaran_rkpd'] as $sasaranData) {
                    $isNewSasaran = false;
                    
                    if (isset($sasaranData['id']) && !empty($sasaranData['id'])) {
                        // Update existing sasaran
                        $sasaranId = $sasaranData['id'];
                        $updateData = [
                            'rpjmd_sasaran_id' => $rpjmdSasaranId,  // Update foreign key
                            'sasaran' => $sasaranData['sasaran'],
                            'status' => $sasaranData['status'] ?? 'draft'
                        ];
                        $this->updateSasaran($sasaranId, $updateData);
                        $processedSasaranIds[] = $sasaranId;
                    } else {
                        // Create new sasaran
                        $sasaranData['rpjmd_sasaran_id'] = $rpjmdSasaranId;
                        $sasaranData['sasaran'] = $sasaranData['sasaran'] ?? '';
                        $sasaranData['status'] = $sasaranData['status'] ?? 'draft';
                        $sasaranId = $this->createSasaran($sasaranData);
                        $processedSasaranIds[] = $sasaranId;
                        $isNewSasaran = true;
                    }

                    // Get existing indikator IDs for this sasaran to track which ones to keep
                    // For new sasaran, there are no existing indikators
                    $existingIndikatorIds = $isNewSasaran ? [] : array_column($this->getIndikatorSasaranBySasaranId($sasaranId), 'id');
                    $processedIndikatorIds = [];

                    // Process indikator sasaran data
                    if (isset($sasaranData['indikator_sasaran']) && is_array($sasaranData['indikator_sasaran'])) {
                        foreach ($sasaranData['indikator_sasaran'] as $indikatorIndex => $indikatorData) {
                            // Skip if indikatorData is not an array or is empty
                            if (!is_array($indikatorData) || empty($indikatorData)) {
                                continue;
                            }
                            
                            // Skip if required fields are missing (except for delete operations)
                            if ((!isset($indikatorData['indikator_sasaran']) || empty($indikatorData['indikator_sasaran'])) &&
                                (!isset($indikatorData['id']) || empty($indikatorData['id']))) {
                                continue;
                            }
                            
                            if (isset($indikatorData['id']) && !empty($indikatorData['id'])) {
                                // Update existing indikator
                                $indikatorId = $indikatorData['id'];
                                
                                // Validate that we have all required fields for update
                                if (isset($indikatorData['indikator_sasaran']) && 
                                    isset($indikatorData['satuan']) && 
                                    isset($indikatorData['tahun']) && 
                                    isset($indikatorData['target'])) {
                                    
                                    $updateIndikatorData = [
                                        'indikator_sasaran' => $indikatorData['indikator_sasaran'],
                                        'satuan' => $indikatorData['satuan'],
                                        'tahun' => $indikatorData['tahun'],
                                        'target' => $indikatorData['target']
                                    ];
                                    $this->updateIndikatorSasaran($indikatorId, $updateIndikatorData);
                                    $processedIndikatorIds[] = $indikatorId;
                                }
                            } else {
                                // Create new indikator - ensure required fields exist
                                if (isset($indikatorData['indikator_sasaran']) && 
                                    isset($indikatorData['satuan']) && 
                                    isset($indikatorData['tahun']) && 
                                    isset($indikatorData['target']) &&
                                    !empty($indikatorData['indikator_sasaran'])) {
                                    
                                    $indikatorData['rkpd_sasaran_id'] = $sasaranId;
                                    $indikatorId = $this->createIndikatorSasaran($indikatorData);
                                    $processedIndikatorIds[] = $indikatorId;
                                }
                            }
                        }
                    }

                    // Delete indikator that were not processed (removed from form)
                    $toDeleteIndikator = array_diff($existingIndikatorIds, $processedIndikatorIds);
                    foreach ($toDeleteIndikator as $deleteId) {
                        $this->deleteIndikatorSasaran($deleteId);
                    }
                }
            }

            // Delete sasaran that were not processed (removed from form)
            $toDeleteSasaran = array_diff($existingSasaranIds, $processedSasaranIds);
            foreach ($toDeleteSasaran as $deleteId) {
                $this->deleteSasaran($deleteId);
            }

            $this->db->transComplete();

            if ($this->db->transStatus() === false) {
                throw new \Exception('Transaction failed');
            }

            return true;

        } catch (\Exception $e) {
            $this->db->transRollback();
            log_message('error', 'Error updating RKPD: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Delete complete RKPD data by RKPD Sasaran ID (with cascade delete)
     */
    public function deleteCompleteRkpd($id)
    {
        $this->db->transStart();
        
        try {
            // Get all RKPD Sasaran for this RKPD Sasaran
            $rkpdSasaranList = $this->db->table('rkpd_sasaran')
                ->where('id', $id)
                ->get()
                ->getResultArray();
            
            foreach ($rkpdSasaranList as $sasaran) {
                // Delete using existing delete method (which handles cascade)
                $this->deleteSasaran($sasaran['id']);
            }
            
            $this->db->transComplete();
            return true;
            
        } catch (\Exception $e) {
            $this->db->transRollback();
            log_message('error', 'Error deleting complete RKPD: ' . $e->getMessage());
            throw $e;
        }
    }

    public function updateRkpdStatus($id, $status)
    {
        if (!in_array($status, ['draft', 'selesai'])) {
            throw new \InvalidArgumentException("Status harus 'draft' atau 'selesai'");
        }

        return $this->db->table('rkpd_sasaran')
            ->where('id', $id)
            ->update(['status' => $status]);
    }
}
