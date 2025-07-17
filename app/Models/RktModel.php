<?php

namespace App\Models;

use CodeIgniter\Model;

class RktModel extends Model
{
    public function getAllRkt()
    {
        return $this->db->table('rkt_sasaran rs')
            ->select('
                rs.id AS sasaran_id, rs.sasaran,
                ris.id AS indikator_id, ris.indikator_sasaran, ris.tahun, ris.target
            ')
            ->join('rkt_indikator_sasaran ris', 'ris.rkt_sasaran_id = rs.id')
            ->orderBy('rs.id', 'ASC')
            ->orderBy('ris.tahun', 'ASC')
            ->get()
            ->getResultArray();
    }
}