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
    public function getRenstraWithPrograms($opd_id)
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
            rpjmd_sasaran.sasaran_rpjmd
        ", false)
            ->join('rpjmd_indikator_sasaran', 'rpjmd_indikator_sasaran.id = iku.rpjmd_id', 'left')
            ->join('renstra_indikator_sasaran', 'renstra_indikator_sasaran.id = iku.renstra_id', 'left')
            ->join('renstra_sasaran', 'renstra_sasaran.id = renstra_indikator_sasaran.renstra_sasaran_id', 'left')
            ->join('rpjmd_sasaran', 'rpjmd_sasaran.id = renstra_sasaran.rpjmd_sasaran_id', 'left')
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

    public function getRPJMDWithPrograms()
    {
        $ikuList = $this->db->table('iku')
            ->select("
            iku.*,
            COALESCE(rpjmd_indikator_sasaran.indikator_sasaran, renstra_indikator_sasaran.indikator_sasaran) AS indikator_sasaran,
            COALESCE(rpjmd_indikator_sasaran.satuan, renstra_indikator_sasaran.satuan) AS satuan,
            COALESCE(rpjmd_sasaran.sasaran_rpjmd, renstra_sasaran.sasaran) AS sasaran,
            renstra_indikator_sasaran.id AS renstra_id_fix,
            rpjmd_indikator_sasaran.id AS rpjmd_id_fix
        ", false)
            ->join('rpjmd_indikator_sasaran', 'rpjmd_indikator_sasaran.id = iku.rpjmd_id', 'left')
            ->join('renstra_indikator_sasaran', 'renstra_indikator_sasaran.id = iku.renstra_id', 'left')
            ->join('renstra_sasaran', 'renstra_sasaran.id = renstra_indikator_sasaran.renstra_sasaran_id', 'left')
            ->join('rpjmd_sasaran', 'rpjmd_sasaran.id = renstra_sasaran.rpjmd_sasaran_id', 'left')
            ->where('(iku.rpjmd_id IS NOT NULL OR iku.renstra_id IS NOT NULL)', null, false)
            ->orderBy('iku.id', 'ASC')
            ->get()
            ->getResultArray();

        // Ambil program pendukung
        $programs = $this->db->table('iku_program_pendukung')
            ->select('iku_indikator_id, program')
            ->get()
            ->getResultArray();

        $programMap = [];
        foreach ($programs as $p) {
            $programMap[$p['iku_indikator_id']][] = $p['program'];
        }

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
            rpjmd_sasaran.sasaran_rpjmd,
            GROUP_CONCAT(iku_program_pendukung.program SEPARATOR '; ') AS daftar_program_pendukung
        ", false)
            ->join('rpjmd_indikator_sasaran', 'rpjmd_indikator_sasaran.id = iku.rpjmd_id', 'left')
            ->join('renstra_indikator_sasaran', 'renstra_indikator_sasaran.id = iku.renstra_id', 'left')
            ->join('renstra_sasaran', 'renstra_sasaran.id = renstra_indikator_sasaran.renstra_sasaran_id', 'left')
            ->join('rpjmd_sasaran', 'rpjmd_sasaran.id = renstra_sasaran.rpjmd_sasaran_id', 'left')
            ->join('iku_program_pendukung', 'iku_program_pendukung.iku_indikator_id = iku.id', 'left')
            ->where('iku.renstra_id', $indikatorId)
            ->get()
            ->getRowArray();
    }

    /**
     * ===============================
     * GET DETAIL IKU (UNTUK HALAMAN EDIT)
     * ===============================
     */
    public function getIkuDetail($indikatorId, $role = 'admin_opd')
    {
        // 🔹 Query dasar
        $builder = $this->db->table('iku')
            ->select("
            iku.*,
            rpjmd_indikator_sasaran.indikator_sasaran AS rpjmd_indikator,
            rpjmd_indikator_sasaran.satuan AS rpjmd_satuan,
            renstra_indikator_sasaran.indikator_sasaran AS renstra_indikator,
            renstra_indikator_sasaran.satuan AS renstra_satuan,
            renstra_sasaran.sasaran AS sasaran_renstra,
            rpjmd_sasaran.sasaran_rpjmd
        ")
            ->join('rpjmd_indikator_sasaran', 'rpjmd_indikator_sasaran.id = iku.rpjmd_id', 'left')
            ->join('renstra_indikator_sasaran', 'renstra_indikator_sasaran.id = iku.renstra_id', 'left')
            ->join('renstra_sasaran', 'renstra_sasaran.id = renstra_indikator_sasaran.renstra_sasaran_id', 'left')
            ->join('rpjmd_sasaran', 'rpjmd_sasaran.id = renstra_sasaran.rpjmd_sasaran_id', 'left');

        // ✅ Logika pencarian berdasarkan role
        if ($role === 'admin_kab') {
            // Coba cari berdasarkan RPJMD
            $builder->where('iku.rpjmd_id', $indikatorId);
            $iku = $builder->get()->getRowArray();

            // Jika tidak ditemukan di RPJMD, fallback ke RENSTRA
            if (!$iku) {
                $builder = $this->db->table('iku')
                    ->select("
                    iku.*,
                    renstra_indikator_sasaran.indikator_sasaran AS renstra_indikator,
                    renstra_indikator_sasaran.satuan AS renstra_satuan,
                    renstra_sasaran.sasaran AS sasaran_renstra
                ")
                    ->join('renstra_indikator_sasaran', 'renstra_indikator_sasaran.id = iku.renstra_id', 'left')
                    ->join('renstra_sasaran', 'renstra_sasaran.id = renstra_indikator_sasaran.renstra_sasaran_id', 'left')
                    ->where('iku.renstra_id', $indikatorId);

                $iku = $builder->get()->getRowArray();
            }
        } else {
            // Jika admin_opd, langsung cari dari renstra
            $builder->where('iku.renstra_id', $indikatorId);
            $iku = $builder->get()->getRowArray();
        }

        // 🔹 Jika tetap kosong, return null biar aman
        if (!$iku) {
            return [
                'id' => null,
                'definisi' => '',
                'program_pendukung' => []
            ];
        }

        // 🔹 Ambil program pendukung
        $programs = $this->db->table('iku_program_pendukung')
            ->select('id, program')
            ->where('iku_indikator_id', $iku['id'])
            ->get()
            ->getResultArray();

        $iku['program_pendukung'] = $programs ?? [];

        return $iku;
    }


    /**
     * =====================
     * UPDATE IKU UTAMA
     * =====================
     */
    public function updateIku($id, $data, $by = 'id')
    {
        return $this->db->table('iku')
            ->where($by, $id)
            ->update($data);
    }

    /**
     * ======================================
     * UPDATE PROGRAM PENDUKUNG (EDIT DETAIL)
     * ======================================
     */
    // public function updateProgramPendukung($ikuId, $programs, $programIds = [])
    // {
    //     $table = $this->db->table('iku_program_pendukung');

    //     foreach ($programs as $index => $program) {
    //         $programId = $programIds[$index] ?? null;
    //         if (trim($program) === '')
    //             continue;

    //         if ($programId) {
    //             // Update program lama
    //             $table->where('id', $programId)->update(['program' => $program]);
    //         } else {
    //             // Tambah program baru
    //             $table->insert([
    //                 'iku_indikator_id' => $ikuId,
    //                 'program' => $program
    //             ]);
    //         }
    //     }

    //     // Hapus program yang dihapus user
    //     if (!empty($programIds)) {
    //         $table->where('iku_indikator_id', $ikuId)
    //             ->whereNotIn('id', $programIds)
    //             ->delete();
    //     }
    // }

    public function updateProgramPendukung($ikuId, $programs, $programIds = [])
    {
        $table = $this->db->table('iku_program_pendukung');

        // 🔹 Ambil semua ID lama
        $existingIds = $table->select('id')
            ->where('iku_indikator_id', $ikuId)
            ->get()
            ->getResultArray();

        $existingIds = array_column($existingIds, 'id');
        $keepIds = [];

        foreach ($programs as $index => $program) {
            $program = trim($program);
            if ($program === '')
                continue;

            $programId = $programIds[$index] ?? null;

            if ($programId && in_array($programId, $existingIds)) {
                // 🔸 Update program lama
                $table->where('id', $programId)->update(['program' => $program]);
                $keepIds[] = $programId;
            } else {
                // 🔹 Tambah program baru
                $table->insert([
                    'iku_indikator_id' => $ikuId,
                    'program' => $program,
                ]);
            }
        }

        // 🔻 Hapus program yang tidak dikirim user
        if (!empty($existingIds)) {
            $toDelete = array_diff($existingIds, $keepIds);
            if (!empty($toDelete)) {
                $table->whereIn('id', $toDelete)->delete();
            }
        }
    }

    /**
     * ======================
     * HAPUS IKU & PROGRAMNYA
     * ======================
     */
    public function deleteIkuComplete($id)
    {
        $db = \Config\Database::connect();
        $db->transBegin();

        try {
            $db->table('iku_program_pendukung')->where('iku_indikator_id', $id)->delete();
            $db->table('iku')->where('id', $id)->delete();
            $db->transCommit();
            return true;
        } catch (\Throwable $e) {
            $db->transRollback();
            throw $e;
        }
    }
}