<?php

namespace App\Models\Opd;

use CodeIgniter\Model;

class IkuOpdModel extends Model
{
    protected $db;
    
    public function __construct()
    {
        parent::__construct();
        $this->db = \Config\Database::connect();
    }

    // ==================== IKU SASARAN ====================
    
    /**
     * Get all IKU Sasaran
     */
    public function getAllSasaran()
    {
        return $this->db->table('iku_sasaran is')
            ->select('is.*, rs.sasaran as sasaran_renstra, o.nama_opd')
            ->join('renstra_sasaran rs', 'rs.id = is.renstra_sasaran_id', 'left')
            ->join('opd o', 'o.id = is.opd_id')
            ->orderBy('is.tahun_mulai', 'ASC')
            ->orderBy('is.id', 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * Get IKU by OPD
     */
    public function getIkuByOpd($opdId)
    {
        return $this->db->table('iku_sasaran is')
            ->select('is.*, rs.sasaran as sasaran_renstra')
            ->join('renstra_sasaran rs', 'rs.id = is.renstra_sasaran_id', 'left')
            ->where('is.opd_id', $opdId)
            ->orderBy('is.tahun_mulai', 'ASC')
            ->orderBy('is.id', 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * Get IKU by ID
     */
    public function getIkuById($id)
    {
        return $this->db->table('iku_sasaran is')
            ->select('is.*, rs.sasaran as sasaran_renstra, o.nama_opd')
            ->join('renstra_sasaran rs', 'rs.id = is.renstra_sasaran_id', 'left')
            ->join('opd o', 'o.id = is.opd_id')
            ->where('is.id', $id)
            ->get()
            ->getRowArray();
    }

    /**
     * Get detailed IKU data with all relations
     */
    public function getDetailedIkuById($id)
    {
        // Get main IKU data
        $iku = $this->getIkuById($id);
        
        if ($iku) {
            // Get indikator kinerja
            $iku['indikator_kinerja'] = $this->getIndikatorKinerjaByIkuId($id);
        }
        
        return $iku;
    }

    /**
     * Get detailed IKU data for all IKU by OPD with all relations
     */
    public function getCompleteIkuByOpd($opdId = null)
    {
        $query = $this->db->table('iku_sasaran is')
            ->select('
                is.id as sasaran_id,
                is.sasaran,
                is.status,
                ik.id as indikator_id,
                ik.indikator_kinerja,
                ik.definisi_formulasi,
                ik.satuan,
                ik.program_pendukung,
                is.tahun_mulai,
                is.tahun_akhir,
                o.nama_opd,
                o.singkatan,
                rs.sasaran as renstra_sasaran
            ')
            ->join('opd o', 'o.id = is.opd_id')
            ->join('renstra_sasaran rs', 'rs.id = is.renstra_sasaran_id', 'left')
            ->join('iku_indikator_kinerja ik', 'ik.iku_sasaran_id = is.id');

        if ($opdId !== null) {
            $query->where('is.opd_id', $opdId);
        }

        $indikatorData = $query->orderBy('is.id', 'ASC')
            ->orderBy('ik.id', 'ASC')
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
     * Get detailed IKU data for all IKU by OPD with all relations
     */
    public function getCompletedIkuOpd($opdId = null)
    {
        $query = $this->db->table('iku_sasaran is')
            ->select('
                is.id as sasaran_id,
                is.sasaran,
                is.opd_id,
                is.status,
                ik.id as indikator_id,
                ik.indikator_kinerja,
                ik.definisi_formulasi,
                ik.satuan,
                ik.program_pendukung,
                is.tahun_mulai,
                is.tahun_akhir,
                o.nama_opd,
                o.singkatan,
                rs.sasaran as renstra_sasaran
            ')
            ->join('opd o', 'o.id = is.opd_id')
            ->join('renstra_sasaran rs', 'rs.id = is.renstra_sasaran_id', 'left')
            ->join('iku_indikator_kinerja ik', 'ik.iku_sasaran_id = is.id');

        if ($opdId !== null) {
            $query->where('is.opd_id', $opdId);
        }

        $indikatorData = $query->orderBy('is.id', 'ASC')
            ->orderBy('ik.id', 'ASC')
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
     * Create new IKU Sasaran
     */
    public function createSasaran($data)
    {
        $this->db->table('iku_sasaran')->insert($data);
        return $this->db->insertID();
    }

    /**
     * Update IKU Sasaran
     */
    public function updateSasaran($id, $data)
    {
        return $this->db->table('iku_sasaran')
            ->where('id', $id)
            ->update($data);
    }

    /**
     * Get sasaran by ID
     */
    public function getSasaranById($id)
    {
        return $this->db->table('iku_sasaran')
            ->where('id', $id)
            ->get()
            ->getRowArray();
    }

    // ==================== INDIKATOR KINERJA ====================
    
    /**
     * Get indikator kinerja by IKU sasaran ID
     */
    public function getIndikatorKinerjaByIkuId($ikuSasaranId)
    {
        $indikators = $this->db->table('iku_indikator_kinerja')
            ->where('iku_sasaran_id', $ikuSasaranId)
            ->orderBy('id', 'ASC')
            ->get()
            ->getResultArray();

        // Get target tahunan for each indikator
        foreach ($indikators as &$indikator) {
            $indikator['target_tahunan'] = $this->getTargetTahunanByIndikatorId($indikator['id']);
        }

        return $indikators;
    }

    /**
     * Create new Indikator Kinerja
     */
    public function createIndikatorKinerja($data)
    {
        $this->db->table('iku_indikator_kinerja')->insert($data);
        return $this->db->insertID();
    }

    /**
     * Update Indikator Kinerja
     */
    public function updateIndikatorKinerja($id, $data)
    {
        return $this->db->table('iku_indikator_kinerja')
            ->where('id', $id)
            ->update($data);
    }

    /**
     * Delete Indikator Kinerja
     */
    public function deleteIndikatorKinerja($id)
    {
        // Delete targets first
        $this->db->table('iku_target_tahunan')
            ->where('iku_indikator_id', $id)
            ->delete();
            
        // Then delete indikator
        return $this->db->table('iku_indikator_kinerja')
            ->where('id', $id)
            ->delete();
    }

    // ==================== TARGET TAHUNAN ====================

    /**
     * Get target tahunan by indikator ID
     */
    public function getTargetTahunanByIndikatorId($indikatorId)
    {
        return $this->db->table('iku_target_tahunan')
            ->where('iku_indikator_id', $indikatorId)
            ->orderBy('tahun', 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * Create new Target Tahunan
     */
    public function createTargetTahunan($data)
    {
        $this->db->table('iku_target_tahunan')->insert($data);
        return $this->db->insertID();
    }

    /**
     * Update Target Tahunan
     */
    public function updateTargetTahunan($id, $data)
    {
        return $this->db->table('iku_target_tahunan')
            ->where('id', $id)
            ->update($data);
    }

    /**
     * Delete Target Tahunan
     */
    public function deleteTargetTahunan($id)
    {
        return $this->db->table('iku_target_tahunan')
            ->where('id', $id)
            ->delete();
    }

    /**
     * Update status (following RENSTRA logic: draft ↔ selesai)
     */
    public function updateStatus($id, $status)
    {
        if (!in_array($status, ['draft', 'selesai'])) {
            throw new \InvalidArgumentException("Status harus 'draft' atau 'selesai'");
        }

        return $this->db->table('iku_sasaran')
            ->where('id', $id)
            ->update(['status' => $status]);
    }

    // ==================== COMPLETE TRANSACTION METHODS (LIKE RENSTRA) ====================
    
    /**
     * Create complete IKU with all related data in single transaction
     */
    public function createCompleteIku($data)
    {
        $this->db->transStart();

        try {
            // Log input data for debugging
            log_message('debug', 'IKU Input Data: ' . json_encode($data));
            
            $sasaranIds = [];

            // Validasi data required
            if (empty($data['sasaran_iku']) || !is_array($data['sasaran_iku'])) {
                throw new \Exception('Data sasaran_iku tidak valid atau kosong');
            }

            // Loop through each sasaran_iku
            foreach ($data['sasaran_iku'] as $index => $sasaranItem) {
                
                // Validasi sasaran item
                if (empty($sasaranItem['sasaran'])) {
                    throw new \Exception("Sasaran pada index {$index} tidak boleh kosong");
                }

                // Prepare data untuk insert sasaran
                $sasaranData = [
                    'opd_id' => $data['opd_id'],
                    'renstra_sasaran_id' => $data['renstra_sasaran_id'],
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

                // Process Indikator Kinerja untuk sasaran ini
                if (!empty($sasaranItem['indikator_kinerja']) && is_array($sasaranItem['indikator_kinerja'])) {
                    foreach ($sasaranItem['indikator_kinerja'] as $ikIndex => $indikatorItem) {
                        
                        // Validasi indikator
                        if (empty($indikatorItem['indikator_kinerja'])) {
                            throw new \Exception("Indikator kinerja pada sasaran {$index}, indikator {$ikIndex} tidak boleh kosong");
                        }

                        // Prepare data untuk insert indikator
                        $indikatorData = [
                            'iku_sasaran_id' => $sasaranId,
                            'indikator_kinerja' => trim($indikatorItem['indikator_kinerja']),
                            'definisi_formulasi' => $indikatorItem['definisi_formulasi'] ?? '',
                            'satuan' => $indikatorItem['satuan'] ?? '',
                            'program_pendukung' => $indikatorItem['program_pendukung'] ?? '',
                        ];

                        // Insert indikator ke database
                        $indikatorId = $this->createIndikatorKinerja($indikatorData);
                        
                        if (!$indikatorId) {
                            throw new \Exception("Gagal menyimpan indikator kinerja pada sasaran {$index}, indikator {$ikIndex}");
                        }

                        // Process Target Tahunan untuk indikator ini
                        if (!empty($indikatorItem['target_tahunan']) && is_array($indikatorItem['target_tahunan'])) {
                            foreach ($indikatorItem['target_tahunan'] as $targetIndex => $targetItem) {
                                
                                // Validasi target - pastikan tahun dan target ada
                                if (empty($targetItem['tahun']) || empty($targetItem['target'])) {
                                    continue; // Skip empty targets
                                }

                                // Prepare data untuk insert target
                                $targetData = [
                                    'iku_indikator_id' => $indikatorId,
                                    'tahun' => intval($targetItem['tahun']),
                                    'target' => trim($targetItem['target']),
                                ];

                                // Insert target ke database
                                $targetId = $this->createTargetTahunan($targetData);
                                
                                if (!$targetId) {
                                    throw new \Exception("Gagal menyimpan target tahunan pada sasaran {$index}, indikator {$ikIndex}, target {$targetIndex}");
                                }
                            }
                        }
                    }
                }
            }

            $this->db->transComplete();
            
            if ($this->db->transStatus() === FALSE) {
                throw new \Exception('Transaction failed');
            }

            return $sasaranIds;

        } catch (\Exception $e) {
            $this->db->transRollback();
            log_message('error', 'Error in createCompleteIku: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Update complete IKU with all related data in single transaction
     */
    public function updateCompleteIku($ikuId, $data)
    {
        $this->db->transStart();

        try {
            // Get existing IKU data
            $existingIku = $this->getSasaranById($ikuId);
            
            if (!$existingIku) {
                throw new \Exception('IKU tidak ditemukan');
            }

            // Update basic IKU info (mengikuti pola Renstra)
            $updateData = [
                'renstra_sasaran_id' => $data['renstra_sasaran_id'],
                'sasaran' => $data['sasaran'], // Direct dari data seperti Renstra
                'tahun_mulai' => $data['tahun_mulai'],
                'tahun_akhir' => $data['tahun_akhir'],
                'updated_at' => date('Y-m-d H:i:s')
            ];

            $updated = $this->updateSasaran($ikuId, $updateData);
            
            if (!$updated) {
                throw new \Exception('Gagal memperbarui data IKU');
            }

            // Delete existing indikator and targets (cascade delete)
            $this->deleteIndikatorByIkuId($ikuId);

            // Re-create indikator and targets with new data 
            if (!empty($data['indikator_kinerja']) && is_array($data['indikator_kinerja'])) {
                foreach ($data['indikator_kinerja'] as $ikIndex => $indikatorItem) {
                    
                    // Skip empty indikator
                    if (empty($indikatorItem['indikator_kinerja']) ||
                        empty($indikatorItem['definisi_formulasi']) ||
                        empty($indikatorItem['satuan']) ||
                        empty($indikatorItem['program_pendukung'])) {
                        continue;
                    }

                    // Create new indikator
                    $indikatorData = [
                        'iku_sasaran_id' => $ikuId,
                        'indikator_kinerja' => trim($indikatorItem['indikator_kinerja']),
                        'definisi_formulasi' => trim($indikatorItem['definisi_formulasi']),
                        'satuan' => trim($indikatorItem['satuan']),
                        'program_pendukung' => trim($indikatorItem['program_pendukung']),
                    ];

                    $indikatorId = $this->createIndikatorKinerja($indikatorData);
                    
                    if (!$indikatorId) {
                        throw new \Exception("Gagal menyimpan indikator kinerja " . ($ikIndex + 1));
                    }

                    // Create targets for this indikator
                    if (!empty($indikatorItem['target_tahunan']) && is_array($indikatorItem['target_tahunan'])) {
                        foreach ($indikatorItem['target_tahunan'] as $targetIndex => $targetItem) {
                            
                            // Skip incomplete targets
                            if (empty($targetItem['tahun']) || empty($targetItem['target'])) {
                                continue;
                            }

                            $targetData = [
                                'iku_indikator_id' => $indikatorId,
                                'tahun' => intval($targetItem['tahun']),
                                'target' => trim($targetItem['target']),
                            ];

                            $targetId = $this->createTargetTahunan($targetData);
                            
                            if (!$targetId) {
                                throw new \Exception("Gagal menyimpan target tahunan " . ($targetIndex + 1));
                            }
                        }
                    }
                }
            }

            $this->db->transComplete();
            
            if ($this->db->transStatus() === FALSE) {
                throw new \Exception('Transaction failed');
            }

            return true;

        } catch (\Exception $e) {
            $this->db->transRollback();
            log_message('error', 'Error in updateCompleteIku: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Delete complete IKU with all related data
     */
    public function deleteIku($ikuId)
    {
        $this->db->transStart();

        try {
            // Delete will cascade to related tables due to foreign key constraints
            $deleted = $this->db->table('iku_sasaran')
                ->where('id', $ikuId)
                ->delete();

            $this->db->transComplete();
            
            return $this->db->transStatus() !== FALSE;

        } catch (\Exception $e) {
            $this->db->transRollback();
            throw $e;
        }
    }

    /**
     * Delete indikator kinerja by IKU sasaran ID (helper method for updates)
     */
    private function deleteIndikatorByIkuId($ikuId)
    {
        // Get all indikator IDs first
        $indikatorIds = $this->db->table('iku_indikator_kinerja')
            ->select('id')
            ->where('iku_sasaran_id', $ikuId)
            ->get()
            ->getResultArray();

        // Delete targets first (to avoid foreign key constraint)
        foreach ($indikatorIds as $indikator) {
            $this->db->table('iku_target_tahunan')
                ->where('iku_indikator_id', $indikator['id'])
                ->delete();
        }

        // Then delete indikators
        return $this->db->table('iku_indikator_kinerja')
            ->where('iku_sasaran_id', $ikuId)
            ->delete();
    }
}