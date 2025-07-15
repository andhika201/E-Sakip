<?php

namespace App\Models;

use CodeIgniter\Model;

class RenjaModel extends Model
{
    protected $table = 'renja_indikator_sasaran';
    protected $primaryKey = 'id';
    protected $returnType = 'array';

    public function getAllRenja()
    {
        return $this->db->table('renja_sasaran rs')
            ->select('
                rs.id AS sasaran_id,
                rs.sasaran_renja AS sasaran,
                ris.id AS indikator_id,
                ris.indikator_sasaran,
                ris.tahun,
                ris.target
            ')
            ->join('renja_indikator_sasaran ris', 'ris.renja_sasaran_id = rs.id')
            ->orderBy('rs.id', 'ASC')
            ->orderBy('ris.tahun', 'ASC')
            ->get()
            ->getResultArray();
    }
}
