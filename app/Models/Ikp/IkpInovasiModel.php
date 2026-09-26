<?php

namespace App\Models\Ikp;

use CodeIgniter\Model;

/**
 * Tabel `ikp_inovasi` — modul Kinerja Prioritas (IKP) / SAKIP sampai pelaksana.
 * Skema: db/update_2026-09-26_ikp_kinerja.sql.
 */
class IkpInovasiModel extends Model
{
    protected $table         = 'ikp_inovasi';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $protectFields = true;
    protected $allowedFields = ['opd_id', 'tahun', 'nama', 'deskripsi', 'ikp_id', 'urutan', 'legacy_prioritas_id'];
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';

    /** Tabel tersedia? (instalasi yang belum menjalankan migrasi tidak 500). */
    public function siap(): bool
    {
        return $this->db->tableExists($this->table);
    }
}
