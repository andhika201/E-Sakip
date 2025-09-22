<?php

namespace App\Models\Opd;

use CodeIgniter\Model;

class TargetModel extends Model
{
    protected $table = 'target_rencana';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;

    protected $allowedFields = [
        'renja_sasaran_id',
        'rencana_aksi',
        'capaian',
        'target_triwulan_1',
        'target_triwulan_2',
        'target_triwulan_3',
        'target_triwulan_4',
        'penanggung_jawab'
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    /**
     * Ambil semua target_rencana dengan join ke renja_sasaran dan renstra_sasaran
     */
    public function getAllTargetWithRelasi()
    {
        return $this->select('
                target_rencana.*,
                renja_sasaran.sasaran_renja,
                renja_sasaran.status as status_renja,
                renstra_sasaran.sasaran as sasaran_renstra
            ')
            ->join('renja_sasaran', 'renja_sasaran.id = target_rencana.renja_sasaran_id', 'left')
            ->join('renstra_sasaran', 'renstra_sasaran.id = renja_sasaran.renstra_sasaran_id', 'left')
            ->orderBy('target_rencana.tahun', 'ASC')
            ->findAll();
    }

    /**
     * Ambil 1 target_rencana beserta relasi lengkapnya
     */
    public function getTargetDetail($id)
    {
        return $this->select('
                target_rencana.*,
                renja_sasaran.sasaran_renja,
                renstra_sasaran.sasaran as sasaran_renstra,
                monev.tahun as monev_tahun,
                monev.capaian_triwulan_1,
                monev.capaian_triwulan_2,
                monev.capaian_triwulan_3,
                monev.capaian_triwulan_4
            ')
            ->join('renja_sasaran', 'renja_sasaran.id = target_rencana.renja_sasaran_id', 'left')
            ->join('renstra_sasaran', 'renstra_sasaran.id = renja_sasaran.renstra_sasaran_id', 'left')
            ->join('monev', 'monev.target_rencana_id = target_rencana.id', 'left')
            ->where('target_rencana.id', $id)
            ->first();
    }

    /**
     * Ambil semua target_rencana milik satu renja_sasaran tertentu
     */
    public function getByRenjaSasaran($renjaSasaranId)
    {
        return $this->where('renja_sasaran_id', $renjaSasaranId)
            ->orderBy('tahun', 'ASC')
            ->findAll();
    }

    /**
     * Ambil semua target_rencana berdasarkan tahun
     */
    public function getByTahun($tahun)
    {
        return $this->where('tahun', $tahun)
            ->orderBy('renja_sasaran_id', 'ASC')
            ->findAll();
    }

    /**
     * Ambil semua tahun yang tersedia
     */
    public function getAvailableYears()
    {
        $db = \Config\Database::connect();
        return $db->table('renja_indikator_sasaran')
            ->select('tahun')
            ->distinct()
            ->orderBy('tahun', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function getFullTargetData($tahun = null)
    {
        $builder = $this->db->table('target_rencana')
            ->select('
            target_rencana.*,
            renja_sasaran.sasaran_renja,
            renstra_sasaran.sasaran as sasaran_renstra,
            renstra_tujuan.tujuan as tujuan_renstra,
            renja_indikator_sasaran.indikator_sasaran,
            renja_indikator_sasaran.tahun as indikator_tahun,
            renja_indikator_sasaran.target as indikator_target
        ')
            ->join('renja_sasaran', 'renja_sasaran.id = target_rencana.renja_sasaran_id', 'left')
            ->join('renstra_sasaran', 'renstra_sasaran.id = renja_sasaran.renstra_sasaran_id', 'left')
            ->join('renstra_tujuan', 'renstra_tujuan.id = renstra_sasaran.renstra_tujuan_id', 'left')
            ->join('renja_indikator_sasaran', 'renja_indikator_sasaran.renja_sasaran_id = renja_sasaran.id', 'left');

        if ($tahun) {
            $builder->where('target_rencana.tahun', $tahun);
        }

        return $builder->orderBy('target_rencana.tahun', 'ASC')->get()->getResultArray();
    }

    public function getTargetListByRenja($tahun = null)
    {
        $builder = $this->db->table('renja_sasaran')
            ->select('
            renja_sasaran.id as renja_sasaran_id,
            renja_sasaran.sasaran_renja,
            renstra_sasaran.sasaran as sasaran_renstra,
            renstra_tujuan.tujuan as tujuan_renstra,
            renja_indikator_sasaran.indikator_sasaran,
            renja_indikator_sasaran.satuan as satuan, 
            renja_indikator_sasaran.target as indikator_target, 
            renja_indikator_sasaran.tahun as indikator_tahun, 
            target_rencana.id as target_id,
            target_rencana.rencana_aksi,
            target_rencana.capaian,
            target_rencana.target_triwulan_1,
            target_rencana.target_triwulan_2,
            target_rencana.target_triwulan_3,
            target_rencana.target_triwulan_4,
            target_rencana.penanggung_jawab
        ')
            ->join('renstra_sasaran', 'renstra_sasaran.id = renja_sasaran.renstra_sasaran_id', 'left')
            ->join('renstra_tujuan', 'renstra_tujuan.id = renstra_sasaran.renstra_tujuan_id', 'left')
            ->join('renja_indikator_sasaran', 'renja_indikator_sasaran.renja_sasaran_id = renja_sasaran.id', 'left')
            ->join('target_rencana', 'target_rencana.renja_sasaran_id = renja_sasaran.id', 'left');

        // Filter tahun dari renja_indikator_sasaran
        if ($tahun) {
            $builder->where('renja_indikator_sasaran.tahun', $tahun);
        }

        return $builder->orderBy('renja_sasaran.id', 'ASC')->get()->getResultArray();
    }

    // Untuk update:
    public function updateTarget($id, $data)
    {
        $this->update($id, $data);
    }
}
