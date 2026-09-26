<?php

namespace App\Models\Ikp;

use CodeIgniter\Model;

/**
 * Tabel `cascading_indikator_target` — modul Kinerja Prioritas (IKP) / SAKIP sampai pelaksana.
 * Skema: db/update_2026-09-26_ikp_kinerja.sql.
 */
class CascadingIndikatorTargetModel extends Model
{
    protected $table         = 'cascading_indikator_target';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $protectFields = true;
    protected $allowedFields = ['cascading_indikator_id', 'tahun', 'target', 'target_teks', 'metode', 'ikp_id'];
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';

    /** Tabel tersedia? (instalasi yang belum menjalankan migrasi tidak 500). */
    public function siap(): bool
    {
        return $this->db->tableExists($this->table);
    }
}
