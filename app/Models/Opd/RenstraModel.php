<?php

namespace App\Models\Opd;

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
            ->select('rs.*, rs.sasaran, o.nama_opd, rps.sasaran_rpjmd as rpjmd_sasaran')
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
            ->select('rs.*, o.nama_opd, rps.sasaran_rpjmd as rpjmd_sasaran')
            ->join('opd o', 'o.id = rs.opd_id')
            ->join('rpjmd_sasaran rps', 'rps.id = rs.rpjmd_sasaran_id')
            ->where('rs.id', $id)
            ->get()
            ->getRowArray();
    }

    public function getRenstraById($id)
    {
        return $this->db->table('renstra_sasaran')
            ->where('id', $id)
            ->get()
            ->getRowArray();
    }

    /**
     * Get Renstra Sasaran by OPD ID for dropdown
     */
    public function getRenstraSasaranByOpd($opdId)
    {
        return $this->db->table('renstra_sasaran')
            ->select('id, sasaran as sasaran_renstra')
            ->where('opd_id', $opdId)
            ->where('status', 'selesai') // Only show completed renstra
            ->orderBy('tahun_mulai', 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * Get Renstra Sasaran by OPD ID
     */
    public function getSasaranByOpdId($opdId)
    {
        return $this->db->table('renstra_sasaran rs')
            ->select('rs.*, o.nama_opd, rps.sasaran_rpjmd as rpjmd_sasaran')
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
     * Get all RENSTRA Sasaran dengan filter status
     */
    public function getAllRenstraByStatus($status = null, $opdId = null)
    {
        $query = $this->db->table('renstra_sasaran rs');

        if ($status !== null) {
            $query->where('rs.status', $status);
        }

        if ($opdId !== null) {
            $query->where('rs.opd_id', $opdId);
        }

        return $query->orderBy('rs.tahun_mulai', 'ASC')
            ->get()
            ->getResultArray();
    }

    // ==================== RENSTRA INDIKATOR SASARAN ====================
    
    /**
     * Get all Indikator Sasaran
     */
    public function getAllIndikatorSasaran()
    {
        return $this->db->table('renstra_indikator_sasaran ris')
            ->select('ris.*, rs.sasaran as sasaran_nama, rs.tahun_mulai, rs.tahun_akhir')
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
            ->select('ris.*, rs.sasaran as sasaran_nama, rs.tahun_mulai, rs.tahun_akhir')
            ->join('renstra_sasaran rs', 'rs.id = ris.renstra_sasaran_id')
            ->where('ris.id', $id)
            ->get()
            ->getRowArray();
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
                rs.sasaran,
                rs.tahun_mulai,
                rs.tahun_akhir,
                o.nama_opd,
                rps.sasaran_rpjmd as rpjmd_sasaran,
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

    public function getCompleteRenstraById($id, $opdId)
    {
        $query = $this->db->table('renstra_sasaran rs')
            ->select('
                rs.id as sasaran_id,
                rs.opd_id,
                rs.rpjmd_sasaran_id,
                rs.sasaran,
                rs.tahun_mulai,
                rs.tahun_akhir,
                o.nama_opd,
                rps.sasaran_rpjmd as rpjmd_sasaran,
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
            ->join('renstra_target rt', 'rt.renstra_indikator_id = ris.id', 'left')
            ->where('rs.id', $id)
            ->where('rs.opd_id', $opdId)
            ->orderBy('rs.id', 'ASC')
            ->orderBy('ris.id', 'ASC')
            ->orderBy('rt.tahun', 'ASC')
            ->get()
            ->getResultArray();

        if (!$query) {
            return null;
        }

        // Grouping data
        $result = [
            'sasaran_id' => $query[0]['sasaran_id'],
            'opd_id' => $query[0]['opd_id'],
            'nama_opd' => $query[0]['nama_opd'],
            'rpjmd_sasaran_id' => $query[0]['rpjmd_sasaran_id'],
            'rpjmd_sasaran' => $query[0]['rpjmd_sasaran'],
            'sasaran' => $query[0]['sasaran'],
            'tahun_mulai' => $query[0]['tahun_mulai'],
            'tahun_akhir' => $query[0]['tahun_akhir'],
            'indikator_sasaran' => []
        ];

        foreach ($query as $row) {
            if ($row['indikator_id']) {
                $indikatorId = $row['indikator_id'];
                
                if (!isset($result['indikator_sasaran'][$indikatorId])) {
                    $result['indikator_sasaran'][$indikatorId] = [
                        'indikator_id' => $indikatorId,
                        'indikator_sasaran' => $row['indikator_sasaran'],
                        'satuan' => $row['satuan'],
                        'target_tahunan' => []
                    ];
                }

                if ($row['target_id']) {
                    $result['indikator_sasaran'][$indikatorId]['target_tahunan'][] = [
                        'target_id' => $row['target_id'],
                        'tahun' => $row['tahun'],
                        'target' => $row['target']
                    ];
                }
            }
        }

        // Ubah ke bentuk array numerik untuk indikator_sasaran
        $result['indikator_sasaran'] = array_values($result['indikator_sasaran']);

        return $result;
    }

    /**
     * Get Renstra data for display table (flattened structure)
     */
    public function getAllRenstra($opdId = null)
    {
        $query = $this->db->table('renstra_sasaran rs')
            ->select('
                rs.id as sasaran_id,
                rs.sasaran,
                rs.status,
                ris.id as indikator_id,
                ris.indikator_sasaran,
                ris.satuan,
                rs.tahun_mulai,
                rs.tahun_akhir,
                o.nama_opd,
                o.singkatan,
                rps.sasaran_rpjmd as rpjmd_sasaran
            ')
            ->join('opd o', 'o.id = rs.opd_id')
            ->join('rpjmd_sasaran rps', 'rps.id = rs.rpjmd_sasaran_id', 'left')
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


    // ==================== CRUD OPERATIONS FOR RENSTRA SASARAN ====================

    /* *
     * Create new RENSTRA Sasaran
     */
    public function createSasaran($data)
    {
        // Validation
        $required = ['rpjmd_sasaran_id', 'opd_id', 'sasaran', 'tahun_mulai', 'tahun_akhir'];
        foreach ($required as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                throw new \InvalidArgumentException("Field {$field} harus diisi");
            }
        }
        
        $insertData = [
            'opd_id' => $data['opd_id'],
            'rpjmd_sasaran_id' => $data['rpjmd_sasaran_id'],
            'sasaran' => $data['sasaran'],
            'status' => $data['status'] ?? 'draft',
            'tahun_mulai' => $data['tahun_mulai'],  
            'tahun_akhir' => $data['tahun_akhir']
        ];
        
        $result = $this->db->table('renstra_sasaran')->insert($insertData);
        $insertId = $this->db->insertID();
        
        if (!$result) {
            $error = $this->db->error();
            throw new \Exception("Failed to insert sasaran: " . $error['message']);
        }
        
        return $insertId;
    }

    /**
     * Update RENSTRA Sasaran
     */
    public function updateSasaran($id, $data)
    {
        return $this->db->table('renstra_sasaran')->where('id', $id)->update($data);
    }
    
    /**
     * Delete RENSTRA Sasaran (with cascade delete)
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
            $result = $this->db->table('renstra_sasaran')->delete(['id' => $id]);
            
            $this->db->transComplete();
            return $result;
            
        } catch (\Exception $e) {
            $this->db->transRollback();
            throw $e;
        }
    }


    // ==================== CRUD OPERATIONS FOR RENSTRA INDIAKTOR SASARAN ====================
     /**
     * Create new RENSTRA Indikator Sasaran
     */
    public function createIndikatorSasaran($data)
    {
        // Validation
        $required = ['renstra_sasaran_id', 'indikator_sasaran', 'satuan'];
        foreach ($required as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                throw new \InvalidArgumentException("Field {$field} harus diisi");
            }
        }
        
        $insertData = [
            'renstra_sasaran_id' => $data['renstra_sasaran_id'],
            'indikator_sasaran' => $data['indikator_sasaran'],
            'satuan' => $data['satuan'],
        ];
        
    
        $result = $this->db->table('renstra_indikator_sasaran')->insert($insertData);
        $insertId = $this->db->insertID();
    
        
        if (!$result) {
            $error = $this->db->error();
            throw new \Exception("Failed to insert indikator sasaran: " . $error['message']);
        }
        
        return $insertId;
    }

    /**
     * Update Renstra Indikator Sasaran
     */
    public function updateIndikatorSasaran($id, $data)
    {
        return $this->db->table('renstra_indikator_sasaran')->where('id', $id)->update($data);
    }
    
    /**
     * Delete RENSTRA Indikator Sasaran (with cascade delete)
     */
    public function deleteIndikatorSasaran($id)
    {
        $this->db->transStart();
        
        try {
            // Delete related target tahunan
            $this->db->table('renstra_target')->delete(['renstra_indikator_id' => $id]);
            
            // Delete the indikator sasaran
            $result = $this->db->table('renstra_indikator_sasaran')->delete(['id' => $id]);
            
            $this->db->transComplete();
            return $result;
            
        } catch (\Exception $e) {
            $this->db->transRollback();
            throw $e;
        }
    }


    // ==================== CRUD OPERATIONS FOR RENSTRA TARGET TAHUNAN ====================
    
    /**
     * Create new RENSTRA Target Tahunan
     */
    public function createTargetTahunan($data)
    {
        
        // Validation
        $required = ['renstra_indikator_id', 'tahun'];
        foreach ($required as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                throw new \InvalidArgumentException("Field {$field} harus diisi");
            }
        }
        
        // target_tahunan is optional and can be empty
        $insertData = [
            'renstra_indikator_id' => $data['renstra_indikator_id'],
            'tahun' => $data['tahun'],
            'target' => $data['target'] ?? ''
        ];
        
        $result = $this->db->table('renstra_target')->insert($insertData);
        $insertId = $this->db->insertID();
    
        if (!$result) {
            $error = $this->db->error();
            throw new \Exception("Failed to insert target tahunan: " . $error['message']);
        }
        
        return $insertId;
    }
    
    /**
     * Update RENSTRA Target Tahunan
     */
    public function updateTargetTahunan($id, $data)
    {
        return $this->db->table('renstra_target')->where('id', $id)->update($data);
    }
    
    /**
     * Delete RENSTRA Target Tahunan
     */
    public function deleteTargetTahunan($id)
    {
        return $this->db->table('renstra_target')->where('id', $id)->delete();
    }
    
    /**
     * Delete all RENSTRA Target Tahunan by Indikator Sasaran ID
     */
    public function deleteTargetTahunanByIndikatorId($indikatorId)
    {
        return $this->db->table('renstra_target')
            ->where('renstra_indikator_id', $indikatorId)
            ->delete();
    }


// ==================== COMPLETE RENSTRA OPERATIONS ====================

    /**
     * Save complete Renstra data (Sasaran + Indikator + Target)
     */
    public function createCompleteRenstra($data)
    {
        $this->db->transStart();

        try {
            $sasaranIds = [];

            // Validasi data required
            if (empty($data['sasaran_renstra']) || !is_array($data['sasaran_renstra'])) {
                throw new \Exception('Data sasaran_renstra tidak valid atau kosong');
            }

            // Loop through each sasaran_renstra
            foreach ($data['sasaran_renstra'] as $index => $sasaranItem) {
                
                // Validasi sasaran item
                if (empty($sasaranItem['sasaran'])) {
                    throw new \Exception("Sasaran pada index {$index} tidak boleh kosong");
                }

                // Prepare data untuk insert sasaran
                $sasaranData = [
                    'opd_id' => $data['opd_id'],
                    'rpjmd_sasaran_id' => $data['rpjmd_sasaran_id'],
                    'sasaran' => trim($sasaranItem['sasaran']),
                    'status' => $data['status'] ?? 'draft',
                    'tahun_mulai' => $data['tahun_mulai'],
                    'tahun_akhir' => $data['tahun_akhir'],
                ];
                
                // Insert sasaran ke database
                $sasaranId = $this->createSasaran($sasaranData);
                
                if (!$sasaranId) {
                    throw new \Exception("Gagal menyimpan sasaran pada index {$index}");
                }
                
                $sasaranIds[] = $sasaranId;

                // Process Indikator Sasaran untuk sasaran ini
                if (isset($sasaranItem['indikator_sasaran']) && is_array($sasaranItem['indikator_sasaran'])) {
                    
                    foreach ($sasaranItem['indikator_sasaran'] as $indikatorIndex => $indikator) {
                        
                        // Validasi indikator
                        if (empty($indikator['indikator_sasaran'])) {
                            throw new \Exception("Indikator sasaran pada sasaran {$index}, indikator {$indikatorIndex} tidak boleh kosong");
                        }

                        // Prepare data untuk insert indikator
                        $indikatorData = [
                            'renstra_sasaran_id' => $sasaranId,
                            'indikator_sasaran' => trim($indikator['indikator_sasaran']),
                            'satuan' => trim($indikator['satuan'] ?? ''),
                        ];
                        
                        // Insert indikator ke database
                        $indikatorId = $this->createIndikatorSasaran($indikatorData);
                        
                        if (!$indikatorId) {
                            throw new \Exception("Gagal menyimpan indikator pada sasaran {$index}, indikator {$indikatorIndex}");
                        }

                        // Process Target Tahunan untuk indikator ini
                        if (isset($indikator['target_tahunan']) && is_array($indikator['target_tahunan'])) {
                            
                            foreach ($indikator['target_tahunan'] as $targetIndex => $target) {
                                
                                // Validasi target
                                if (empty($target['tahun']) || empty($target['target'])) {
                                    // Skip jika target kosong, atau bisa throw exception sesuai kebutuhan
                                    log_message('warning', "Target pada sasaran {$index}, indikator {$indikatorIndex}, target {$targetIndex} tidak valid, akan diabaikan");
                                    continue;
                                }

                                // Prepare data untuk insert target
                                $targetData = [
                                    'renstra_indikator_id' => $indikatorId,
                                    'tahun' => $target['tahun'],
                                    'target' => trim($target['target']),
                                ];
                                
                                // Insert target ke database
                                $targetId = $this->createTargetTahunan($targetData);
                                
                                if (!$targetId) {
                                    throw new \Exception("Gagal menyimpan target pada sasaran {$index}, indikator {$indikatorIndex}, target {$targetIndex}");
                                }
                            }
                        }
                    }
                }
            }

            // Commit transaction
            $this->db->transComplete();

            if ($this->db->transStatus() === false) {
                throw new \Exception('Transaction gagal, data tidak tersimpan');
            }

            // Return array of sasaran IDs
            return $sasaranIds;

        } catch (\Exception $e) {
            $this->db->transRollback();
            log_message('error', 'Error in createCompleteRenstra: ' . $e->getMessage());
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
                'sasaran' => $data['sasaran'],
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
                    // Skip if indikator is not an array or is empty
                    if (!is_array($indikator) || empty($indikator)) {
                        continue;
                    }
                    
                    // Skip if required fields are missing
                    if (!isset($indikator['indikator_sasaran']) || empty($indikator['indikator_sasaran']) ||
                        !isset($indikator['satuan']) || empty($indikator['satuan'])) {
                        continue;
                    }
                    
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
                            // Skip if target data is incomplete
                            if (!is_array($target) || !isset($target['tahun']) || !isset($target['target']) ||
                                empty($target['tahun']) || empty($target['target'])) {
                                continue;
                            }
                            
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
            // First, delete all RENJA data that references this RENSTRA sasaran
            $renjaSasaranList = $this->db->table('renja_sasaran')
                ->where('renstra_sasaran_id', $sasaranId)
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
                ->where('renstra_sasaran_id', $sasaranId)
                ->delete();

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

    public function updateRenstraStatus($id, $status)
    {
        if (!in_array($status, ['draft', 'selesai'])) {
            throw new \InvalidArgumentException("Status harus 'draft' atau 'selesai'");
        }

        return $this->db->table('renstra_sasaran')
            ->where('id', $id)
            ->update(['status' => $status]);
    }
}
