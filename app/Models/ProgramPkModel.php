<?php

namespace App\Models;

use CodeIgniter\Model;

class ProgramPkModel extends Model
{
    // ===========================================================
    // KONFIG DASAR MODEL (UTAMA: program_pk)
    // ===========================================================
    protected $table            = 'program_pk';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';

    protected $allowedFields    = [
        'program_kegiatan',
        'anggaran',
        'created_at',
        'updated_at'
    ];

    // created_at & updated_at otomatis untuk tabel program_pk
    protected $useTimestamps = true;

    // ===========================================================
    //  BAGIAN PROGRAM_PK
    // ===========================================================

    /**
     * Ambil semua program + jumlah kegiatan di dalamnya
     */
    public function getAllProgram()
    {
        return $this->select('program_pk.*, COUNT(kegiatan_pk.id) AS total_kegiatan')
            ->join('kegiatan_pk', 'kegiatan_pk.program_id = program_pk.id', 'left')
            ->groupBy('program_pk.id')
            ->orderBy('program_pk.id', 'DESC')
            ->findAll();
    }

    /**
     * Ambil data 1 program berdasarkan ID
     */
    public function getProgramById($id)
    {
        return $this->where('id', $id)->first();
    }

    /**
     * Ambil semua kegiatan milik 1 program
     */
    public function getKegiatanByProgram($programId)
    {
        return $this->db->table('kegiatan_pk')
            ->where('program_id', $programId)
            ->orderBy('id', 'ASC')
            ->get()
            ->getResultArray();
    }

    // ===========================================================
    //  BAGIAN KEGIATAN_PK (masih dalam 1 model)
    // ===========================================================

    /**
     * Ambil semua kegiatan + nama program
     */
    public function getAllKegiatan()
    {
        return $this->db->table('kegiatan_pk')
            ->select('kegiatan_pk.*, program_pk.program_kegiatan')
            ->join('program_pk', 'program_pk.id = kegiatan_pk.program_id', 'left')
            ->orderBy('kegiatan_pk.id', 'DESC')
            ->get()
            ->getResultArray();
    }

    /**
     * Ambil 1 kegiatan berdasarkan ID
     */
    public function getKegiatanById($id)
    {
        return $this->db->table('kegiatan_pk')
            ->where('id', $id)
            ->get()
            ->getRowArray();
    }

    /**
     * Tambah kegiatan baru
     * $data = [
     *   'program_id' => ...,
     *   'kegiatan'   => ...,
     *   'anggaran'   => ...,
     *   'created_at' => date('Y-m-d H:i:s'),
     *   'updated_at' => date('Y-m-d H:i:s'),
     * ];
     */
    public function insertKegiatan(array $data)
    {
        return $this->db->table('kegiatan_pk')->insert($data);
    }

    /**
     * Update kegiatan
     */
    public function updateKegiatan($id, array $data)
    {
        return $this->db->table('kegiatan_pk')
            ->where('id', $id)
            ->update($data);
    }

    /**
     * Hapus kegiatan
     */
    public function deleteKegiatan($id)
    {
        return $this->db->table('kegiatan_pk')
            ->where('id', $id)
            ->delete();
    }

    // ===========================================================
    //  BAGIAN SUB_KEGIATAN_PK (masih dalam 1 model)
    // ===========================================================

    /**
     * Ambil semua sub kegiatan + nama kegiatan induk
     */
    public function getAllSubKegiatan()
    {
        return $this->db->table('sub_kegiatan_pk')
            ->select('sub_kegiatan_pk.*, kegiatan_pk.kegiatan')
            ->join('kegiatan_pk', 'kegiatan_pk.id = sub_kegiatan_pk.kegiatan_id', 'left')
            ->orderBy('sub_kegiatan_pk.id', 'DESC')
            ->get()
            ->getResultArray();
    }

    /**
     * Ambil 1 sub kegiatan berdasarkan ID
     */
    public function getSubKegiatanById($id)
    {
        return $this->db->table('sub_kegiatan_pk')
            ->where('id', $id)
            ->get()
            ->getRowArray();
    }

    /**
     * Ambil semua sub kegiatan milik 1 kegiatan
     */
    public function getSubByKegiatan($kegiatanId)
    {
        return $this->db->table('sub_kegiatan_pk')
            ->where('kegiatan_id', $kegiatanId)
            ->orderBy('id', 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * Tambah sub kegiatan baru
     * $data = [
     *   'kegiatan_id'  => ...,
     *   'sub_kegiatan' => ...,
     *   'anggaran'     => ...,
     *   'created_at'   => date('Y-m-d H:i:s'),
     *   'updated_at'   => date('Y-m-d H:i:s'),
     * ];
     */
    public function insertSubKegiatan(array $data)
    {
        return $this->db->table('sub_kegiatan_pk')->insert($data);
    }

    /**
     * Update sub kegiatan
     */
    public function updateSubKegiatan($id, array $data)
    {
        return $this->db->table('sub_kegiatan_pk')
            ->where('id', $id)
            ->update($data);
    }

    /**
     * Hapus sub kegiatan
     */
    public function deleteSubKegiatan($id)
    {
        return $this->db->table('sub_kegiatan_pk')
            ->where('id', $id)
            ->delete();
    }
}
