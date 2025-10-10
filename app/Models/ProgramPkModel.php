<?php

namespace App\Models;

use CodeIgniter\Model;

class ProgramPkModel extends Model
{
    protected $table            = 'program_pk';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'program_kegiatan',
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
        'program_kegiatan' => 'required|min_length[3]',
        'anggaran'         => 'required|numeric',
    ];
    
    protected $validationMessages   = [
        'program_kegiatan' => [
            'required'    => 'Program kegiatan harus diisi',
            'min_length'  => 'Program kegiatan minimal 3 karakter',
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
    public function getAllPrograms()
    {
        return $this->orderBy('created_at', 'DESC')->findAll();
    }

    /**
     * Get program by ID
     */
    public function getProgramById($id)
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
    public function searchPrograms($keyword)
    {
        return $this->like('program_kegiatan', $keyword)
                   ->orderBy('created_at', 'DESC')
                   ->findAll();
    }}