<?php

namespace App\Models\Opd;

use CodeIgniter\Model;

class RenjaModel extends Model
{
    protected $db;
    
    public function __construct()
    {
        parent::__construct();
        $this->db = \Config\Database::connect();
    }
    
    // ==================== RENJA SASARAN ====================

    /**
     * Get all RENJA Sasaran
     */
    public function getRenjaSasaranById($id) {

        return $this->db->table('renja_sasaran')
            ->where('id', $id)
            ->get()
            ->getRowArray();
    }

    /**
     * Get all RENJA Sasaran with optional status filter
     */
    public function getAllRenjaByStatus($status = null, $opdId = null)
    {
        $query = $this->db->table('renja_sasaran');
        
        if ($status !== null) {
            $query->where('status', $status);
        }

        if ($opdId !== null) {
            $query->where('opd_id', $opdId);
        }

        return $query->orderBy('id', 'ASC')
            ->get()
            ->getResultArray();
    }

   

    /* * Get All RENJA Data Per OPD
     * This method retrieves all RENJA data including indicators
     */
    public function getAllRenja($opdId)
    {
        $builder = $this->db->table('renja_sasaran');
        $builder->select('
            renja_sasaran.id AS renja_sasaran_id,
            renja_sasaran.opd_id,
            renja_sasaran.renstra_sasaran_id,
            renja_sasaran.sasaran_renja,
            renja_sasaran.status,
            renja_indikator_sasaran.id AS indikator_id,
            renja_indikator_sasaran.indikator_sasaran,
            renja_indikator_sasaran.satuan,
            renja_indikator_sasaran.tahun,
            renja_indikator_sasaran.target,
            renstra_sasaran.sasaran AS renstra_sasaran
        ');
        $builder->join('renja_indikator_sasaran', 'renja_indikator_sasaran.renja_sasaran_id = renja_sasaran.id', 'left');
        $builder->join('renstra_sasaran', 'renstra_sasaran.id = renja_sasaran.renstra_sasaran_id', 'left');
        $builder->where('renja_sasaran.opd_id', $opdId);
        $builder->orderBy('renja_sasaran.id', 'ASC');
        $builder->orderBy('renja_indikator_sasaran.tahun', 'ASC');
        
        $query = $builder->get();
        $results = $query->getResultArray();

        // Kelompokkan data berdasarkan renja_sasaran_id
        $grouped = [];
        foreach ($results as $row) {
            $id = $row['renja_sasaran_id'];
            if (!isset($grouped[$id])) {
                $grouped[$id] = [
                    'id' => $row['renja_sasaran_id'],
                    'opd_id' => $row['opd_id'],
                    'renstra_sasaran_id' => $row['renstra_sasaran_id'],
                    'renstra_sasaran' => $row['renstra_sasaran'],
                    'sasaran_renja' => $row['sasaran_renja'],
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
     * Get RENJA data by RPJMD Sasaran ID (for edit)
     */
    public function getRenjaById($id)
    {
        $builder = $this->db->table('renja_sasaran');
        $builder->select('
            renja_sasaran.id AS renja_sasaran_id,
            renja_sasaran.opd_id,
            renja_sasaran.renstra_sasaran_id,
            renja_sasaran.sasaran_renja,
            renja_sasaran.status,
            renja_indikator_sasaran.id AS indikator_id,
            renja_indikator_sasaran.indikator_sasaran,
            renja_indikator_sasaran.satuan,
            renja_indikator_sasaran.tahun,
            renja_indikator_sasaran.target,
            renstra_sasaran.id AS renstra_sasaran_id,
            renstra_sasaran.sasaran AS renstra_sasaran
        ');
        $builder->join('renja_indikator_sasaran', 'renja_indikator_sasaran.renja_sasaran_id = renja_sasaran.id', 'left');
        $builder->join('renstra_sasaran', 'renstra_sasaran.id = renja_sasaran.renstra_sasaran_id', 'left');
        $builder->join('opd', 'opd.id = renja_sasaran.opd_id', 'left');
        $builder->where('renja_sasaran.id', $id);
        $builder->orderBy('renja_sasaran.id', 'ASC');
        $builder->orderBy('renja_indikator_sasaran.tahun', 'ASC');
        
        $query = $builder->get();
        $results = $query->getResultArray();

        // Kelompokkan data berdasarkan renja_sasaran_id
        $grouped = [];
        foreach ($results as $row) {
            $id = $row['renja_sasaran_id'];
            if (!isset($grouped[$id])) {
                $grouped[$id] = [
                    'id' => $row['renja_sasaran_id'],
                    'opd_id' => $row['opd_id'],
                    'renstra_sasaran_id' => $row['renstra_sasaran_id'],
                    'sasaran_renja' => $row['sasaran_renja'],
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
            'renstra_sasaran_id' => !empty($results) ? $results[0]['renstra_sasaran_id'] : '',
            'renstra_sasaran' => !empty($results) ? $results[0]['renstra_sasaran'] : '',
            'sasaran_renja' => array_values($grouped)
        ];
    }

    // ==================== RENJA INDIKATOR SASARAN ====================
    
    /**
     * Get all Indikator Sasaran
     */
    public function getAllIndikatorSasaran()
    {
        return $this->db->table('renja_indikator_sasaran ris')
            ->select('ris.*, rs.sasaran as sasaran_nama')
            ->join('renja_sasaran rs', 'rs.id = ris.renja_sasaran_id')
            ->orderBy('ris.renja_sasaran_id', 'ASC')
            ->orderBy('ris.id', 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * Get Indikator Sasaran by Sasaran ID
     */
    public function getIndikatorSasaranBySasaranId($sasaranId)
    {
        return $this->db->table('renja_indikator_sasaran')
            ->where('renja_sasaran_id', $sasaranId)
            ->get()
            ->getResultArray();
    }

    /**
     * Get Indikator Sasaran by ID
     */
    public function getIndikatorSasaranById($id)
    {
        return $this->db->table('renja_indikator_sasaran ris')
            ->select('ris.*, rs.sasaran as sasaran_nama')
            ->join('renja_sasaran rs', 'rs.id = ris.renja_sasaran_id')
            ->where('ris.id', $id)
            ->get()
            ->getRowArray();
    }


    

// ==================== COMPLETE RENJA OPERATIONS ====================

    // Menyimpan data RENJA beserta indikator sasaran
    public function createCompleteRenja($data)
    {
        $this->db->transStart();

        try {
            if (isset($data['sasaran_renja']) && is_array($data['sasaran_renja'])) {
                foreach ($data['sasaran_renja'] as $sasaranItem) {

                    // Inject foreign key ke dalam data sasaran
                    $sasaranData = [
                        'opd_id' => $data['opd_id'],
                        'renstra_sasaran_id' => $data['renstra_sasaran_id'],
                        'sasaran_renja' => $sasaranItem['sasaran'] ?? '',
                        'status' => $sasaranItem['status'] ?? 'draft',
                    ];
                    
                    // Simpan sasaran RENJA
                    $sasaranRenjaId = $this->createSasaran($sasaranData);

                    // Cek dan simpan indikator-indikator sasaran
                    if (isset($sasaranItem['indikator_sasaran']) && is_array($sasaranItem['indikator_sasaran'])) {
                        foreach ($sasaranItem['indikator_sasaran'] as $indikatorData) {

                            // Inject foreign key ke dalam data indikator
                            $indikatorData['renja_sasaran_id'] = $sasaranRenjaId;

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
            log_message('error', 'Error saving RENJA: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Update complete RENJA data with sasaran and indikator
     */
    public function updateCompleteRenja($renjaSasaranId, $data)
    {
        try {
            $this->db->transStart();

            // Get the selected RPJMD Sasaran ID from form
            $renstraSasaranId = $data['renstra_sasaran_id'];

            // Get existing sasaran IDs to track which ones to keep
            // Since we're editing a specific RENJA sasaran, we start with that ID
            $existingSasaranIds = [$renjaSasaranId];
            
            $processedSasaranIds = [];

            // Process sasaran data
            if (isset($data['sasaran_renja']) && is_array($data['sasaran_renja'])) {
                foreach ($data['sasaran_renja'] as $sasaranData) {
                    $isNewSasaran = false;
                    
                    if (isset($sasaranData['id']) && !empty($sasaranData['id'])) {
                        // Update existing sasaran
                        $sasaranId = $sasaranData['id'];
                        $updateData = [
                            'renstra_sasaran_id' => $renstraSasaranId,
                            'sasaran_renja' => $sasaranData['sasaran'],
                            'status' => $sasaranData['status'] ?? 'draft'
                        ];

                        $this->updateSasaran($sasaranId, $updateData);
                        $processedSasaranIds[] = $sasaranId;
                    } else {
                        // Create new sasaran
                        $sasaranData['opd_id'] = $data['opd_id'];
                        $sasaranData['renstra_sasaran_id'] = $renstraSasaranId;
                        $sasaranData['sasaran_renja'] = $sasaranData['sasaran'] ?? '';
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
                                    
                                    $indikatorData['renja_sasaran_id'] = $sasaranId;
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
            log_message('error', 'Error updating RENJA: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Delete complete RENJA data by RENJA Sasaran ID (with cascade delete)
     */
    public function deleteCompleteRenja($renjaSasaranId)
    {
        $this->db->transStart();
        
        try {
            // Get all RENJA Sasaran for this RENJA Sasaran
            $renjaSasaranList = $this->db->table('renja_sasaran')
                ->where('id', $renjaSasaranId)
                ->get()
                ->getResultArray();
            
            foreach ($renjaSasaranList as $sasaran) {
                // Delete using existing delete method (which handles cascade)
                $this->deleteSasaran($sasaran['id']);
            }
            
            $this->db->transComplete();
            return true;
            
        } catch (\Exception $e) {
            $this->db->transRollback();
            log_message('error', 'Error deleting complete RENJA: ' . $e->getMessage());
            throw $e;
        }
    }

    public function updateRenjaStatus($id, $status)
    {
        if (!in_array($status, ['draft', 'selesai'])) {
            throw new \InvalidArgumentException("Status harus 'draft' atau 'selesai'");
        }

        return $this->db->table('renja_sasaran')
            ->where('id', $id)
            ->update(['status' => $status]);
    }
}
