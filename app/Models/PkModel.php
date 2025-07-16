<?php

namespace App\Models;

use CodeIgniter\Model;

class PkModel extends Model
{
    protected $table            = 'pk';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'jenis',
        'pihak_1', 
        'pihak_2',
        'tanggal'
    ];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected array $casts = [];
    protected array $castHandlers = [];

    // Dates - Aktifkan auto timestamps
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    // Validation
    protected $validationRules = [
        'jenis' => 'required|in_list[jpt,administrator,pengawas]',
        'pihak_1' => 'required|integer',
        'pihak_2' => 'required|integer',
        'tanggal' => 'required|valid_date'
    ];
    
    protected $validationMessages = [
        'jenis' => [
            'required' => 'Jenis PK harus dipilih',
            'in_list' => 'Jenis PK tidak valid'
        ],
        'pihak_1' => [
            'required' => 'Pihak 1 harus dipilih',
            'integer' => 'Pihak 1 harus berupa angka'
        ],
        'pihak_2' => [
            'required' => 'Pihak 2 harus dipilih', 
            'integer' => 'Pihak 2 harus berupa angka'
        ],
        'tanggal' => [
            'required' => 'Tanggal harus diisi',
            'valid_date' => 'Format tanggal tidak valid'
        ]
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
     * Get PK with pegawai relationships
     */
    public function getPkWithPegawai($id = null)
    {
        $builder = $this->db->table($this->table . ' pk')
            ->select('pk.*, p1.nama as nama_pihak_1, p2.nama as nama_pihak_2')
            ->join('pegawai p1', 'p1.id = pk.pihak_1', 'left')
            ->join('pegawai p2', 'p2.id = pk.pihak_2', 'left');
            
        if ($id !== null) {
            return $builder->where('pk.id', $id)->get()->getRowArray();
        }
        
        return $builder->get()->getResultArray();
    }
}
