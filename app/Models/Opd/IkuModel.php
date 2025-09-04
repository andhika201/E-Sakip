<?php

namespace App\Models\Opd;

use CodeIgniter\Model;

class IkuModel extends Model
{
    // Table utama: iku_sasaran
    protected $table = 'iku_sasaran';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'opd_id', 'renstra_sasaran_id', 'sasaran', 'status', 'tahun_mulai', 'tahun_akhir', 'created_at', 'updated_at'
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    // ==================== IKU CRUD BERTINGKAT ====================

    /**
     * Insert IKU lengkap (sasaran, indikator, target tahunan)
     */
    public function createCompleteIku($data)
    {
        $db = \Config\Database::connect();
        $db->transStart();
        try {
            // 1. Simpan Sasaran IKU
            $sasaranData = [
                'opd_id' => $data['opd_id'],
                'renstra_sasaran_id' => $data['renstra_sasaran_id'],
                'sasaran' => $data['sasaran'],
                'status' => $data['status'] ?? 'draft',
                'tahun_mulai' => $data['tahun_mulai'] ?? null,
                'tahun_akhir' => $data['tahun_akhir'] ?? null,
            ];
            $this->insert($sasaranData);
            $ikuSasaranId = $this->getInsertID();

            // 2. Simpan Indikator-indikator
            if (!empty($data['indikator']) && is_array($data['indikator'])) {
                foreach ($data['indikator'] as $indikator) {
                    $indikatorData = [
                        'iku_sasaran_id' => $ikuSasaranId,
                        'indikator_kinerja' => $indikator['indikator_kinerja'],
                        'definisi_formulasi' => $indikator['definisi_formulasi'] ?? null,
                        'satuan' => $indikator['satuan'] ?? null,
                        'program_pendukung' => $indikator['program_pendukung'] ?? null,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ];
                    $db->table('iku_indikator')->insert($indikatorData);
                    $ikuIndikatorId = $db->insertID();

                    // 3. Simpan Target Tahunan
                    if (!empty($indikator['target_tahunan']) && is_array($indikator['target_tahunan'])) {
                        foreach ($indikator['target_tahunan'] as $target) {
                            $targetData = [
                                'iku_indikator_id' => $ikuIndikatorId,
                                'tahun' => $target['tahun'],
                                'target' => $target['target'],
                                'created_at' => date('Y-m-d H:i:s'),
                                'updated_at' => date('Y-m-d H:i:s'),
                            ];
                            $db->table('iku_target_tahunan')->insert($targetData);
                        }
                    }
                }
            }
            $db->transComplete();
            if ($db->transStatus() === false) {
                throw new \Exception('Transaction failed');
            }
            return true;
        } catch (\Exception $e) {
            $db->transRollback();
            throw $e;
        }
    }

    /**
     * Get all IKU sasaran beserta indikator dan target tahunan (nested array)
     */
    public function getAllIkuByOpd($opdId)
    {
        $sasaranList = $this->where('opd_id', $opdId)->findAll();
        $db = \Config\Database::connect();
        foreach ($sasaranList as &$sasaran) {
            $indikatorList = $db->table('iku_indikator')
                ->where('iku_sasaran_id', $sasaran['id'])
                ->get()->getResultArray();
            foreach ($indikatorList as &$indikator) {
                $targetList = $db->table('iku_target_tahunan')
                    ->where('iku_indikator_id', $indikator['id'])
                    ->get()->getResultArray();
                $indikator['target_tahunan'] = $targetList;
            }
            $sasaran['indikator'] = $indikatorList;
        }
        return $sasaranList;
    }

    // Tambahkan method update & delete bertingkat sesuai kebutuhan
}
