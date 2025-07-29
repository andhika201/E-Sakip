<?php

namespace App\Models\Opd;

use CodeIgniter\Model;

class LakipOpdModel extends Model
{
    protected $table = 'lakip_opd'; // Sesuaikan dengan nama tabel di database
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'opd_id',
        'sasaran',
        'indikator',
        'capaian_sebelumnya',
        'target_tahun_ini',
        'capaian_tahun_ini',
        'tahun',
        'created_at',
        'updated_at'
    ];

    public function getAllLakipOpd()
    {
        return $this->orderBy('tahun', 'ASC')->findAll();
    }

    public function getLakipByOpd($opdId)
    {
        return $this->where('opd_id', $opdId)
                    ->orderBy('tahun', 'ASC')
                    ->findAll();
    }

    public function getLakipGroupedByTahun()
    {
        return $this->select('tahun')
                    ->groupBy('tahun')
                    ->orderBy('tahun', 'ASC')
                    ->findAll();
    }

    public function getLakipSummary()
    {
        return $this->select('opd_id, tahun, COUNT(*) as jumlah')
                    ->groupBy(['opd_id', 'tahun'])
                    ->findAll();
    }
}
