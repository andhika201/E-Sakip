<?php

namespace App\Models;

use CodeIgniter\Model;

class OpdModel extends Model
{
    protected $table = 'opd';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = ['nama_opd', 'singkatan', 'kode'];

    // Automatically handle timestamps
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = 'deleted_at';

    // Validation rules
    protected $validationRules = [
        'nama_opd' => 'required|string|max_length[255]',
        'singkatan' => 'permit_empty|string|max_length[50]',
        'kode' => 'permit_empty|string|max_length[50]|is_unique[opd.kode,id,{id}]',
    ];

    protected $validationMessages = [
        'nama_opd' => [
            'required' => 'Nama OPD harus diisi',
            'max_length' => 'Nama OPD maksimal 255 karakter',
        ],
        'kode' => [
            'is_unique' => 'Kode OPD sudah digunakan',
        ],
    ];

    /**
     * Get all OPD data for dropdown
     */
    public function getAllOpd()
    {
        return $this->where('nama_opd !=', 'ADMIN')
                ->orderBy('nama_opd', 'ASC')
                ->findAll();
    }
}
