<?php

namespace App\Models;

use CodeIgniter\Model;

class LakipKabupatenModel extends Model
{
    protected $table = 'lakip_kabupaten';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'sasaran',
        'indikator',
        'capaian_sebelumnya',
        'target_tahun_ini',
        'capaian_tahun_ini',
        'created_at',
        'updated_at'
    ];

    /**
     * Ambil semua data LAKIP Kabupaten
     *
     * @return array
     */
    public function getAllLakipKabupaten()
    {
        return $this->orderBy('id', 'ASC')->findAll();
    }
}
