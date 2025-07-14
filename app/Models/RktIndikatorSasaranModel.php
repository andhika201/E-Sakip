<?php

namespace App\Models;

use CodeIgniter\Model;

class RktIndikatorSasaranModel extends Model
{
    protected $table = 'rkt_indikator_sasaran';
    protected $primaryKey = 'id';
    protected $allowedFields = ['rkt_sasaran_id', 'indikator_sasaran', 'satuan', 'tahun', 'target'];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    // Mendapatkan indikator sasaran berdasarkan RKT sasaran ID
    public function getByRktSasaranId($rktSasaranId)
    {
        return $this->where('rkt_sasaran_id', $rktSasaranId)->findAll();
    }

    // Hapus indikator sasaran berdasarkan RKT sasaran ID
    public function deleteByRktSasaranId($rktSasaranId)
    {
        return $this->where('rkt_sasaran_id', $rktSasaranId)->delete();
    }
}
