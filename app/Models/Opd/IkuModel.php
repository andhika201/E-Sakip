<?php

namespace App\Models\Opd;

use CodeIgniter\Model;

class IkuModel extends Model
{
    // Table utama: iku_sasaran
    protected $table = 'iku_sasaran';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'opd_id',
        'renstra_sasaran_id',
        'sasaran',
        'status',
        'tahun_mulai',
        'tahun_akhir',
        'created_at',
        'updated_at'
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
            // 1. Simpan IKU
            $ikuData = [
                'rpjmd_id' => $data['rpjmd_id'] ?? null, // hanya terisi jika admin kabupaten
                'renstra_id' => $data['renstra_id'] ?? null, // hanya terisi jika admin opd
                'definisi' => $data['definisi'],
            ];
            $db->table('iku')->insert($ikuData);
            $ikuId = $db->insertID();

            // 2. Simpan Program Pendukung (jika ada)
            if (!empty($data['program_pendukung']) && is_array($data['program_pendukung'])) {
                foreach ($data['program_pendukung'] as $program) {
                    if (trim($program) !== '') {
                        $programData = [
                            'iku_indikator_id' => $ikuId,
                            'program' => $program,
                        ];
                        $db->table('iku_program_pendukung')->insert($programData);
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
    public function getIkuWithPrograms($opd_id)
    {
        // Ambil data IKU saja
        $ikuList = $this->db->table('iku')
            ->select("
            iku.*,
            rpjmd_indikator_sasaran.indikator_sasaran AS rpjmd_indikator,
            rpjmd_indikator_sasaran.satuan AS rpjmd_satuan,
            renstra_indikator_sasaran.indikator_sasaran AS renstra_indikator,
            renstra_indikator_sasaran.satuan AS renstra_satuan,
            renstra_sasaran.sasaran AS sasaran_renstra,
            renstra_tujuan.tujuan AS tujuan_renstra
        ", false)
            ->join('rpjmd_indikator_sasaran', 'rpjmd_indikator_sasaran.id = iku.rpjmd_id', 'left')
            ->join('renstra_indikator_sasaran', 'renstra_indikator_sasaran.id = iku.renstra_id', 'left')
            ->join('renstra_sasaran', 'renstra_sasaran.id = renstra_indikator_sasaran.renstra_sasaran_id', 'left')
            ->join('renstra_tujuan', 'renstra_tujuan.id = renstra_sasaran.renstra_tujuan_id', 'left')
            ->where('renstra_sasaran.opd_id', $opd_id)
            ->orderBy('iku.id', 'ASC')
            ->get()
            ->getResultArray();

        // Ambil semua program pendukung
        $programs = $this->db->table('iku_program_pendukung')
            ->select('iku_indikator_id, program')
            ->get()
            ->getResultArray();

        // Mapping program ke IKU
        $programMap = [];
        foreach ($programs as $p) {
            $programMap[$p['iku_indikator_id']][] = $p['program'];
        }

        // Gabungkan ke dalam $ikuList
        foreach ($ikuList as &$iku) {
            $iku['program_pendukung'] = $programMap[$iku['id']] ?? [];
        }

        return $ikuList;
    }


    public function getFullIkuDataById($indikatorId)
    {
        return $this->db->table('iku')
            ->select("
            iku.*,
            rpjmd_indikator_sasaran.indikator_sasaran AS rpjmd_indikator,
            rpjmd_indikator_sasaran.satuan AS rpjmd_satuan,
            renstra_indikator_sasaran.indikator_sasaran AS renstra_indikator,
            renstra_indikator_sasaran.satuan AS renstra_satuan,
            renstra_sasaran.sasaran AS sasaran_renstra,
            renstra_tujuan.tujuan AS tujuan_renstra,
            GROUP_CONCAT(iku_program_pendukung.program SEPARATOR '; ') AS daftar_program_pendukung
        ", false)
            ->join('rpjmd_indikator_sasaran', 'rpjmd_indikator_sasaran.id = iku.rpjmd_id', 'left')
            ->join('renstra_indikator_sasaran', 'renstra_indikator_sasaran.id = iku.renstra_id', 'left')
            ->join('renstra_sasaran', 'renstra_sasaran.id = renstra_indikator_sasaran.renstra_sasaran_id', 'left')
            ->join('renstra_tujuan', 'renstra_tujuan.id = renstra_sasaran.renstra_tujuan_id', 'left')
            ->join('iku_program_pendukung', 'iku_program_pendukung.iku_indikator_id = iku.id', 'left')
            ->where('iku.renstra_id', $indikatorId)
            ->get()
            ->getRowArray();
    }

    public function getIkuDetail($id)
    {
        // Ambil detail IKU
        $iku = $this->db->table('iku')
            ->select("
            iku.*,
            rpjmd_indikator_sasaran.indikator_sasaran AS rpjmd_indikator,
            rpjmd_indikator_sasaran.satuan AS rpjmd_satuan,
            renstra_indikator_sasaran.indikator_sasaran AS renstra_indikator,
            renstra_indikator_sasaran.satuan AS renstra_satuan,
            renstra_sasaran.sasaran AS sasaran_renstra,
            renstra_tujuan.tujuan AS tujuan_renstra
        ", false)
            ->join('rpjmd_indikator_sasaran', 'rpjmd_indikator_sasaran.id = iku.rpjmd_id', 'left')
            ->join('renstra_indikator_sasaran', 'renstra_indikator_sasaran.id = iku.renstra_id', 'left')
            ->join('renstra_sasaran', 'renstra_sasaran.id = renstra_indikator_sasaran.renstra_sasaran_id', 'left')
            ->join('renstra_tujuan', 'renstra_tujuan.id = renstra_sasaran.renstra_tujuan_id', 'left')
            ->where('renstra_indikator_sasaran.id', $id)
            ->get()
            ->getRowArray();

        // Ambil program pendukung
        $programs = $this->db->table('iku_program_pendukung')
            ->select('program')
            ->where('iku_indikator_id', $iku['id'])
            ->get()
            ->getResultArray();

        $iku['program_pendukung'] = array_column($programs, 'program');

        return $iku;
    }


    //update IKU beserta program pendukungnya

    public function updateIku($id, $data)
    {
        return $this->db->table('iku')
            ->where('renstra_id', $id) // atau sesuaikan dengan primary key sebenarnya
            ->update($data);
    }

    public function updateProgramPendukung($ikuId, $programs)
    {
        // Hapus program lama
        $this->db->table('iku_program_pendukung')
            ->where('iku_indikator_id', $ikuId)
            ->delete();

        // Insert program baru
        foreach ($programs as $program) {
            if (trim($program) !== '') {
                $this->db->table('iku_program_pendukung')->insert([
                    'iku_indikator_id' => $ikuId,
                    'program' => $program
                ]);
            }
        }
    }



    // Tambahkan method update & delete bertingkat sesuai kebutuhan
}
