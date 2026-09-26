<?php

namespace App\Models\Ikp;

use CodeIgniter\Model;

/**
 * Tabel `ikp_program_unggulan` — modul Kinerja Prioritas (IKP) / SAKIP sampai pelaksana.
 * Skema: db/update_2026-09-26_ikp_kinerja.sql.
 */
class IkpProgramUnggulanModel extends Model
{
    protected $table         = 'ikp_program_unggulan';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $protectFields = true;
    protected $allowedFields = ['nama', 'slug', 'warna', 'ikon', 'urutan'];
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';

    /** Tabel tersedia? (instalasi yang belum menjalankan migrasi tidak 500). */
    public function siap(): bool
    {
        return $this->db->tableExists($this->table);
    }
}
