<?php

namespace App\Models;

use CodeIgniter\Model;

class SatuanModel extends Model
{
    protected $table = 'satuan';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = ['satuan'];

    protected $useTimestamps = false;

    protected $validationRules = [
        'satuan' => 'required|string|max_length[100]',
    ];
    protected $validationMessages = [
        'satuan' => [
            'required' => 'Nama satuan harus diisi',
        ],
    ];

    public function getAllSatuan(): array
    {
        return $this->orderBy('satuan', 'ASC')->findAll();
    }
}
