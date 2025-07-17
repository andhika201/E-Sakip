<?php

namespace App\Models;

use CodeIgniter\Model;

class RenstraModel extends Model
{
    protected $DBGroup = 'default';

    public function getRenstraData()
    {
        return $this->db->table('renstra_sasaran rs')
            ->select('
                rs.id AS sasaran_id, rs.indikator_sasaran AS sasaran,
                ris.id AS indikator_id, ris.indikator_sasaran,
                rt.tahun, rt.target, rs.opd_id
            ')
            ->join('renstra_indikator_sasaran ris', 'ris.renstra_sasaran_id = rs.id')
            ->join('renstra_target rt', 'rt.renstra_indikator_id = ris.id')
            ->orderBy('rs.id', 'ASC')
            ->orderBy('rt.tahun', 'ASC')
            ->get()
            ->getResultArray();
    }
}
