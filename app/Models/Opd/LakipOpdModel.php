<?php

namespace App\Models\Opd;

use CodeIgniter\Model;

class LakipOpdModel extends Model
{
    protected $table = 'lakip';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;

    protected $returnType = 'array';
    protected $useSoftDeletes = false;

    protected $protectFields = true;

    // Kolom sesuai tabel lakip
    protected $allowedFields = [
        'renstra_indikator_id',    // FK -> renstra_indikator_sasaran.id
        'rpjmd_indikator_id',      // FK -> rpjmd_indikator_sasaran.id
        'target_lalu',
        'capaian_lalu',
        'capaian_tahun_ini',
        'status',                  // enum('draft','selesai')
    ];

    // timestamps
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    /**
     * Detail LAKIP berdasarkan indikator (RENSTRA / RPJMD)
     * Dipakai di form EDIT.
     *
     * @param int    $indikatorId id indikator (renstra / rpjmd)
     * @param string $role        'admin_kab' = pakai RPJMD, selain itu pakai RENSTRA
     */
    public function getLakipDetail(int $indikatorId, string $role = 'admin_opd'): ?array
    {
        $builder = $this->db->table($this->table)
            ->select("
                lakip.*,
                rpjmd_indikator_sasaran.indikator_sasaran AS rpjmd_indikator,
                rpjmd_indikator_sasaran.satuan            AS rpjmd_satuan,
                renstra_indikator_sasaran.indikator_sasaran AS renstra_indikator,
                renstra_indikator_sasaran.satuan            AS renstra_satuan,
                renstra_sasaran.sasaran                   AS sasaran_renstra,
                rpjmd_sasaran.sasaran_rpjmd               AS sasaran_rpjmd
            ")
            // FK ke indikator RPJMD
            ->join(
                'rpjmd_indikator_sasaran',
                'rpjmd_indikator_sasaran.id = lakip.rpjmd_indikator_id',
                'left'
            )
            // FK ke indikator RENSTRA
            ->join(
                'renstra_indikator_sasaran',
                'renstra_indikator_sasaran.id = lakip.renstra_indikator_id',
                'left'
            )
            // FK ke renstra_sasaran
            ->join(
                'renstra_sasaran',
                'renstra_sasaran.id = renstra_indikator_sasaran.renstra_sasaran_id',
                'left'
            )
            // FK ke rpjmd_sasaran lewat rpjmd_indikator_sasaran.sasaran_id
            ->join(
                'rpjmd_sasaran',
                'rpjmd_sasaran.id = rpjmd_indikator_sasaran.sasaran_id',
                'left'
            );

        if ($role === 'admin_kab') {
            // untuk admin_kab, indikatorId datang dari rpjmd_indikator_sasaran
            $builder->where('rpjmd_indikator_sasaran.id', $indikatorId);
        } else {
            // untuk admin_opd, indikatorId datang dari renstra_indikator_sasaran
            $builder->where('renstra_indikator_sasaran.id', $indikatorId);
        }

        $row = $builder->get()->getRowArray();
        return $row ?: null;
    }

    /**
     * Ambil tahun tersedia dari renstra_target (untuk filter tahun di view).
     */
    public function getAvailableYears(): array
    {
        $rows = $this->db->table('renstra_target')
            ->select('DISTINCT tahun', false)
            ->orderBy('tahun', 'ASC')
            ->get()
            ->getResultArray();

        return array_column($rows, 'tahun');
    }

    /**
     * Ambil data LAKIP berdasar RENSTRA (admin_opd / admin_kab mode OPD)
     *
     * @param int         $opd_id  id opd
     * @param string|null $status  draft / selesai (optional)
     */
    public function getRenstra(int $opd_id, ?string $status = null): array
    {
        $builder = $this->db->table($this->table)
            ->select("
                lakip.*,
                rpjmd_indikator_sasaran.indikator_sasaran AS rpjmd_indikator,
                rpjmd_indikator_sasaran.satuan            AS rpjmd_satuan,
                renstra_indikator_sasaran.indikator_sasaran AS renstra_indikator,
                renstra_indikator_sasaran.satuan            AS renstra_satuan,
                renstra_sasaran.sasaran                   AS sasaran_renstra,
                rpjmd_sasaran.sasaran_rpjmd               AS sasaran_rpjmd
            ")
            ->join(
                'rpjmd_indikator_sasaran',
                'rpjmd_indikator_sasaran.id = lakip.rpjmd_indikator_id',
                'left'
            )
            ->join(
                'renstra_indikator_sasaran',
                'renstra_indikator_sasaran.id = lakip.renstra_indikator_id',
                'left'
            )
            ->join(
                'renstra_sasaran',
                'renstra_sasaran.id = renstra_indikator_sasaran.renstra_sasaran_id',
                'left'
            )
            ->join(
                'rpjmd_sasaran',
                'rpjmd_sasaran.id = rpjmd_indikator_sasaran.sasaran_id',
                'left'
            )
            ->where('renstra_sasaran.opd_id', $opd_id);

        if (!empty($status)) {
            $builder->where('lakip.status', $status);
        }

        return $builder
            ->orderBy('lakip.id', 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * Ambil data LAKIP berdasar RPJMD (admin_kab mode KABUPATEN)
     *
     * @param string|null $status draft / selesai (optional)
     */
    public function getRPJMD(?string $status = null): array
    {
        $builder = $this->db->table($this->table)
            ->select("
                lakip.*,
                rpjmd_indikator_sasaran.indikator_sasaran AS rpjmd_indikator,
                rpjmd_indikator_sasaran.satuan            AS rpjmd_satuan,
                renstra_indikator_sasaran.indikator_sasaran AS renstra_indikator,
                renstra_indikator_sasaran.satuan            AS renstra_satuan,
                renstra_sasaran.sasaran                   AS sasaran_renstra,
                rpjmd_sasaran.sasaran_rpjmd               AS sasaran_rpjmd
            ")
            ->join(
                'rpjmd_indikator_sasaran',
                'rpjmd_indikator_sasaran.id = lakip.rpjmd_indikator_id',
                'left'
            )
            ->join(
                'renstra_indikator_sasaran',
                'renstra_indikator_sasaran.id = lakip.renstra_indikator_id',
                'left'
            )
            ->join(
                'renstra_sasaran',
                'renstra_sasaran.id = renstra_indikator_sasaran.renstra_sasaran_id',
                'left'
            )
            ->join(
                'rpjmd_sasaran',
                'rpjmd_sasaran.id = rpjmd_indikator_sasaran.sasaran_id',
                'left'
            );

        if (!empty($status)) {
            $builder->where('lakip.status', $status);
        }

        return $builder
            ->orderBy('lakip.id', 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * Update data LAKIP
     */
    public function updateLakip(int $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    /**
     * Hapus data LAKIP
     */
    public function deleteLakip(int $id): bool
    {
        return $this->delete($id);
    }
}
