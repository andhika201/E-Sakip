<?php

namespace App\Models\Opd;

use CodeIgniter\Model;

class MonevModel extends Model
{
    protected $table = 'monev';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $allowedFields = [
        'target_rencana_id',
        'capaian_triwulan_1',
        'capaian_triwulan_2',
        'capaian_triwulan_3',
        'capaian_triwulan_4',
        'total'
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    // Ambil data monev beserta relasi target_rencana dan indikator
    public function getMonevWithRelasi($tahun = null)
    {
        $builder = $this->db->table($this->table . ' as monev')
            ->select('
            rpjmd_tujuan.tujuan_rpjmd,
            rpjmd_sasaran.sasaran_rpjmd,
            renstra_sasaran.sasaran as sasaran_renstra,
            renja_sasaran.sasaran_renja,
            renja_indikator_sasaran.id as indikator_id,
            renja_indikator_sasaran.indikator_sasaran,
            renja_indikator_sasaran.satuan,
            renja_indikator_sasaran.target as indikator_target,
            renja_indikator_sasaran.tahun as indikator_tahun,

            target_rencana.id as target_id,
            target_rencana.rencana_aksi,
            target_rencana.capaian,
            target_rencana.target_triwulan_1,
            target_rencana.target_triwulan_2,
            target_rencana.target_triwulan_3,
            target_rencana.target_triwulan_4,
            target_rencana.penanggung_jawab,

            monev.id as monev_id,
            monev.capaian_triwulan_1,
            monev.capaian_triwulan_2,
            monev.capaian_triwulan_3,
            monev.capaian_triwulan_4,
            monev.total
        ')
            ->join('target_rencana', 'target_rencana.id = monev.target_rencana_id', 'right')
            ->join('renja_indikator_sasaran', 'renja_indikator_sasaran.id = target_rencana.renja_indikator_sasaran_id', 'left')
            ->join('renja_sasaran', 'renja_sasaran.id = renja_indikator_sasaran.renja_sasaran_id', 'left')
            ->join('renstra_sasaran', 'renstra_sasaran.id = renja_sasaran.renstra_sasaran_id', 'left')
            ->join('rpjmd_sasaran', 'rpjmd_sasaran.id = renstra_sasaran.rpjmd_sasaran_id', 'left')
            ->join('rpjmd_tujuan', 'rpjmd_tujuan.id = rpjmd_sasaran.tujuan_id', 'left');


        if ($tahun) {
            $builder->where('renja_indikator_sasaran.tahun', $tahun);
        }

        return $builder->orderBy('renja_sasaran.id', 'ASC')->get()->getResultArray();
    }

    // Ambil tahun-tahun monev yang tersedia
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
}