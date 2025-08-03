<?php

namespace App\Models\Opd;

use CodeIgniter\Model;

class KegiatanPkModel extends Model
{
    protected $table            = 'kegiatan_opd';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'opd_id',
        'kegiatan',
        'anggaran',
    ];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected array $casts = [];
    protected array $castHandlers = [];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';    // Validation
    protected $validationRules = [
        'opd_id' => 'required|integer',
        'kegiatan' => 'required|min_length[3]',
        'anggaran'         => 'required|numeric',
    ];
    
    protected $validationMessages   = [
        'opd_id' => [
            'required'    => 'ID OPD harus diisi',
            'integer'     => 'ID OPD harus berupa angka',
        ],
        'kegiatan' => [
            'required'    => 'Kegiatan harus diisi',
            'min_length'  => 'Kegiatan minimal 3 karakter',
        ],
        'anggaran' => [
            'required'     => 'Anggaran harus diisi',
            'numeric'      => 'Anggaran harus berupa angka',
        ],
    ];

    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert   = [];
    protected $afterInsert    = [];
    protected $beforeUpdate   = [];
    protected $afterUpdate    = [];
    protected $beforeFind     = [];
    protected $afterFind      = [];
    protected $beforeDelete   = [];
    protected $afterDelete    = [];

    /**
     * Get all programs with formatted anggaran
     */
    public function getAllKegiatans()
    {
        return $this->orderBy('created_at', 'DESC')->findAll();
    }

    /**
     * Get program by ID
     */
    public function getKegiatanById($id)
    {
        return $this->find($id);
    }

    /**
     * Format anggaran as currency
     */
    public function formatAnggaran($anggaran)
    {
        return 'Rp ' . number_format($anggaran, 2, ',', '.');
    }

    /**
     * Search programs
     */
    public function searchKegiatans($keyword)
    {
        return $this->like('kegiatan', $keyword)
                   ->orderBy('created_at', 'DESC')
                   ->findAll();
    }

    /**
     * Get kegiatan by OPD ID
     */
    public function getByOpdId($opdId)
    {
        return $this->where('opd_id', $opdId)
                   ->orderBy('created_at', 'DESC')
                   ->findAll();
    }}
