<?php

namespace App\Models\Opd;

use CodeIgniter\Model;

class RenjaModel extends Model
{
    protected $table = 'renja_sasaran';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'rpjmd_sasaran_id',
        'sasaran_renja',
        'status',
        'created_at',
        'updated_at'
    ];

    public function getRenjaData()
    {
        return $this->db->table('renja_sasaran rs')
            ->select('
                rs.sasaran_renja,
                ris.indikator_sasaran,
                ris.satuan,
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
