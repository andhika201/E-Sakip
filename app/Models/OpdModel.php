<?php

namespace App\Models;

use CodeIgniter\Model;

class OpdModel extends Model
{
    /**
     * ID OPD yang dikecualikan dari daftar/dropdown OPD (mis. Pemda induk / entitas non-OPD).
     * Sumber tunggal — jangan hardcode ulang angka ini di controller/query lain.
     */
    public const EXCLUDED_OPD_IDS = [1, 46, 209];

    protected $table = 'opd';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = ['nama_opd', 'singkatan', 'alamat_opd'];

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
        'alamat_opd' => 'permit_empty|string|max_length[50]',
    ];

    protected $validationMessages = [
        'nama_opd' => [
            'required' => 'Nama OPD harus diisi',
            'max_length' => 'Nama OPD maksimal 255 karakter',
        ],
    ];

    protected $skipValidation = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert = [];
    protected $afterInsert = [];
    protected $beforeUpdate = [];
    protected $afterUpdate = [];
    protected $beforeFind = [];
    protected $afterFind = [];
    protected $beforeDelete = [];
    protected $afterDelete = [];

    public function getAllOpd()
    {
        return $this->whereNotIn('id', self::EXCLUDED_OPD_IDS)->orderBy('nama_opd', 'ASC')->findAll();
    }
    public function getOpdById(int $opdId)
    {
        $db = \Config\Database::connect();
        return $db->table('opd')->where('id', $opdId)->get()->getRowArray();
    }
}
