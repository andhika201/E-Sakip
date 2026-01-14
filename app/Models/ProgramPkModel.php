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

    protected $allowedFields = [
        'program_kegiatan',
        'anggaran',
    ];

    protected bool $allowEmptyInserts  = false;
    protected bool $updateOnlyChanged  = true;

    protected array $casts        = [];
    protected array $castHandlers = [];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    // Validation
    protected $validationRules = [
        'program_kegiatan' => 'required|min_length[3]',
        'anggaran'         => 'required|numeric',
    ];

    protected $validationMessages = [
        'program_kegiatan' => [
            'required'   => 'Program kegiatan harus diisi',
            'min_length' => 'Program kegiatan minimal 3 karakter',
        ],
        'anggaran' => [
            'required' => 'Anggaran harus diisi',
            'numeric'  => 'Anggaran harus berupa angka',
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
     * Ambil semua program PK
     */
    public function getAllPrograms(): array
    {
        return $this->orderBy('created_at', 'DESC')->findAll();
    }

    /**
     * Ambil 1 program
     */
    public function getProgramById($id): ?array
    {
        return $this->find($id);
    }

    /**
     * Format anggaran jadi rupiah
     */
    public function formatAnggaran($anggaran): string
    {
        return 'Rp ' . number_format((float) $anggaran, 2, ',', '.');
    }

    /**
     * Pencarian program
     */
    public function searchPrograms(string $keyword): array
    {
        return $this->like('program_kegiatan', $keyword)
            ->orderBy('created_at', 'DESC')
            ->findAll();
    }

    /* =========================================================
     *  UNTUK FORM RKT
     * =======================================================*/

    /**
     * Ambil semua kegiatan PK
     * Tabel: kegiatan_pk
     * Kolom penting: id, program_id, kegiatan, anggaran
     */
    public function getAllKegiatan(): array
    {
        return $this->db->table('kegiatan_pk')
            ->select('id, program_id, kegiatan, anggaran')
            ->orderBy('kegiatan', 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * Ambil semua sub kegiatan PK
     * Tabel: sub_kegiatan_pk
     * Kolom penting: id, kegiatan_id, sub_kegiatan, anggaran
     */
    public function getAllSubKegiatan(): array
    {
        return $this->db->table('sub_kegiatan_pk')
            ->select('id, kegiatan_id, sub_kegiatan, anggaran')
            ->orderBy('sub_kegiatan', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function getSubKegiatanByTahun($tahun)
    {
        $subQuery = $this->db->table('sub_kegiatan_pk')
            ->select('MAX(id) as id')
            ->where('tahun_anggaran', $tahun)
            ->groupBy('kegiatan_id, sub_kegiatan')
            ->getCompiledSelect();

        return $this->db->table('sub_kegiatan_pk sk')
            ->select('sk.id, sk.kegiatan_id, sk.sub_kegiatan, sk.anggaran')
            ->join("($subQuery) latest", 'latest.id = sk.id')
            ->orderBy('sk.sub_kegiatan', 'ASC')
            ->get()
            ->getResultArray();
    }
}
