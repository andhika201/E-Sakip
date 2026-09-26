<?php

namespace App\Models\Ikp;

use CodeIgniter\Model;

/**
 * Tabel `cascading_pemilik` — modul Kinerja Prioritas (IKP) / SAKIP sampai pelaksana.
 * Skema: db/update_2026-09-26_ikp_kinerja.sql.
 */
class CascadingPemilikModel extends Model
{
    protected $table         = 'cascading_pemilik';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $protectFields = true;
    protected $allowedFields = ['cascading_sasaran_id', 'opd_id', 'tahun', 'pegawai_id', 'jabatan_teks', 'peran', 'is_plt', 'sumber'];
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';

    /** Tabel tersedia? (instalasi yang belum menjalankan migrasi tidak 500). */
    public function siap(): bool
    {
        return $this->db->tableExists($this->table);
    }
}
