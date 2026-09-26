<?php

namespace App\Models\Ikp;

use CodeIgniter\Model;

/**
 * Tabel `ikp_target_tahunan` — modul Kinerja Prioritas (IKP) / SAKIP sampai pelaksana.
 * Skema: db/update_2026-09-26_ikp_kinerja.sql.
 */
class IkpTargetTahunanModel extends Model
{
    protected $table         = 'ikp_target_tahunan';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $protectFields = true;
    protected $allowedFields = ['ikp_id', 'tahun', 'target', 'target_teks'];
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';

    /** Tabel tersedia? (instalasi yang belum menjalankan migrasi tidak 500). */
    public function siap(): bool
    {
        return $this->db->tableExists($this->table);
    }
}
