<?php

namespace App\Models\Opd;

use CodeIgniter\Model;

class IkuModel extends Model
{
    protected $table = 'iku';
    protected $primaryKey = 'id';
    protected $returnType = 'array';

    protected $allowedFields = [
        'rpjmd_id',
        'renstra_id',
        'definisi',
        'status',
        'created_at',
        'updated_at',
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    // =========================================================
    // CREATE IKU + PROGRAM PENDUKUNG
    // =========================================================
    /**
     * Insert IKU lengkap (definisi + program pendukung)
     * $data = [
     *   'rpjmd_id'         => (int|null),
     *   'renstra_id'       => (int|null),
     *   'definisi'         => '...',
     *   'status'           => 'draft'|'selesai' (opsional),
     *   'program_pendukung'=> ['prog 1', 'prog 2', ...]
     * ]
     */
    public function createCompleteIku(array $data)
    {
        $db = \Config\Database::connect();
        $db->transStart();

        try {
            // 1. Simpan IKU
            $ikuData = [
                'rpjmd_id' => $data['rpjmd_id'] ?? null,
                'renstra_id' => $data['renstra_id'] ?? null,
                'definisi' => $data['definisi'],
                'status' => $data['status'] ?? 'draft',
            ];

            $db->table('iku')->insert($ikuData);
            $ikuId = $db->insertID();

            // 2. Simpan Program Pendukung (jika ada)
            if (!empty($data['program_pendukung']) && is_array($data['program_pendukung'])) {
                foreach ($data['program_pendukung'] as $program) {
                    $program = trim($program);
                    if ($program === '') {
                        continue;
                    }
                    $db->table('iku_program_pendukung')->insert([
                        'iku_id' => $ikuId,
                        'program' => $program,
                    ]);
                }
            }

            $db->transComplete();

            if ($db->transStatus() === false) {
                throw new \Exception('Transaksi penyimpanan IKU gagal.');
            }

            return $ikuId;
        } catch (\Throwable $e) {
            $db->transRollback();
            throw $e;
        }
    }

    // =========================================================
    // LIST IKU UNTUK ADMIN OPD (BERDASARKAN OPD)
    // =========================================================
    public function getRenstraWithPrograms($opd_id)
    {
        // Ambil IKU + info indikator / sasaran untuk OPD tertentu
        $ikuList = $this->db->table('iku')
            ->select("
                iku.*,
                renstra_indikator_sasaran.indikator_sasaran AS renstra_indikator,
                renstra_indikator_sasaran.satuan           AS renstra_satuan,
                renstra_sasaran.sasaran                    AS sasaran_renstra
            ", false)
            ->join(
                'renstra_indikator_sasaran',
                'renstra_indikator_sasaran.id = iku.renstra_id',
                'left'
            )
            ->join(
                'renstra_sasaran',
                'renstra_sasaran.id = renstra_indikator_sasaran.renstra_sasaran_id',
                'left'
            )
            ->where('renstra_sasaran.opd_id', $opd_id)
            ->orderBy('iku.id', 'ASC')
            ->get()
            ->getResultArray();

        // Ambil semua program pendukung
        $programs = $this->db->table('iku_program_pendukung')
            ->select('iku_id, program')
            ->get()
            ->getResultArray();

        // Mapping program ke IKU
        $programMap = [];
        foreach ($programs as $p) {
            $programMap[$p['iku_id']][] = $p['program'];
        }

        // Gabungkan ke dalam list IKU
        foreach ($ikuList as &$iku) {
            $iku['program_pendukung'] = $programMap[$iku['id']] ?? [];
        }

        return $ikuList;
    }

    // =========================================================
    // LIST IKU UNTUK ADMIN KABUPATEN (RPJMD / RENSTRA)
    // =========================================================
    public function getRPJMDWithPrograms()
    {
        $ikuList = $this->db->table('iku')
            ->select("
                iku.*,
                COALESCE(rpjmd_indikator_sasaran.indikator_sasaran, renstra_indikator_sasaran.indikator_sasaran) AS indikator_sasaran,
                COALESCE(rpjmd_indikator_sasaran.satuan, renstra_indikator_sasaran.satuan)                       AS satuan
            ", false)
            ->join(
                'rpjmd_indikator_sasaran',
                'rpjmd_indikator_sasaran.id = iku.rpjmd_id',
                'left'
            )
            ->join(
                'renstra_indikator_sasaran',
                'renstra_indikator_sasaran.id = iku.renstra_id',
                'left'
            )
            ->where('(iku.rpjmd_id IS NOT NULL OR iku.renstra_id IS NOT NULL)', null, false)
            ->orderBy('iku.id', 'ASC')
            ->get()
            ->getResultArray();

        // Ambil program pendukung
        $programs = $this->db->table('iku_program_pendukung')
            ->select('iku_id, program')
            ->get()
            ->getResultArray();

        $programMap = [];
        foreach ($programs as $p) {
            $programMap[$p['iku_id']][] = $p['program'];
        }

        foreach ($ikuList as &$iku) {
            $iku['program_pendukung'] = $programMap[$iku['id']] ?? [];
        }

        return $ikuList;
    }

    // =========================================================
    // DETAIL IKU UNTUK HALAMAN EDIT
    // =========================================================
    public function getIkuDetail($indikatorId, $role = 'admin_opd')
    {
        // Cari IKU berdasarkan renstra_id / rpjmd_id
        $builder = $this->db->table('iku');

        if ($role === 'admin_kab') {
            $builder->where('rpjmd_id', $indikatorId);
        } else {
            $builder->where('renstra_id', $indikatorId);
        }

        $iku = $builder->get()->getRowArray();

        // Jika belum ada IKU (belum pernah dibuat), kembalikan template kosong
        if (!$iku) {
            return [
                'id' => null,
                'definisi' => '',
                'rpjmd_id' => ($role === 'admin_kab') ? $indikatorId : null,
                'renstra_id' => ($role === 'admin_opd') ? $indikatorId : null,
                'program_pendukung' => [],
            ];
        }

        // Ambil program pendukung
        $programs = $this->db->table('iku_program_pendukung')
            ->select('id, program')
            ->where('iku_id', $iku['id'])
            ->get()
            ->getResultArray();

        $iku['program_pendukung'] = $programs ?? [];

        return $iku;
    }

    // =========================================================
    // UPDATE IKU (DEFINSI / STATUS)
    // =========================================================
    public function updateIku($id, array $data, string $by = 'id')
    {
        return $this->db->table('iku')
            ->where($by, $id)
            ->update($data);
    }

    // =========================================================
    // UPDATE PROGRAM PENDUKUNG (EDIT DETAIL)
    // =========================================================
    public function updateProgramPendukung($ikuId, $programs, $programIds = [])
    {
        $table = $this->db->table('iku_program_pendukung');

        // Ambil semua ID lama
        $existingIds = $table->select('id')
            ->where('iku_id', $ikuId)
            ->get()
            ->getResultArray();
        $existingIds = array_column($existingIds, 'id');

        $keepIds = [];

        foreach ($programs as $index => $program) {
            $program = trim($program);
            if ($program === '') {
                continue;
            }

            $programId = $programIds[$index] ?? null;

            if ($programId && in_array($programId, $existingIds)) {
                // Update program lama
                $table->where('id', $programId)->update(['program' => $program]);
                $keepIds[] = $programId;
            } else {
                // Tambah program baru
                $table->insert([
                    'iku_id' => $ikuId,
                    'program' => $program,
                ]);
                $keepIds[] = $this->db->insertID();
            }
        }

        // Hapus program yang tidak dikirim user
        if (!empty($existingIds)) {
            $toDelete = array_diff($existingIds, $keepIds);
            if (!empty($toDelete)) {
                $table->whereIn('id', $toDelete)->delete();
            }
        }
    }

    // =========================================================
    // HAPUS IKU + PROGRAM PENDUKUNG
    // =========================================================
    public function deleteIkuComplete($id)
    {
        $db = \Config\Database::connect();
        $db->transBegin();

        try {
            $db->table('iku_program_pendukung')->where('iku_id', $id)->delete();
            $db->table('iku')->where('id', $id)->delete();

            $db->transCommit();
            return true;
        } catch (\Throwable $e) {
            $db->transRollback();
            throw $e;
        }
    }
}
