<?php

namespace App\Models\Ikp;

use CodeIgniter\Model;

/**
 * Tabel `ikp_bulanan` — modul Kinerja Prioritas (IKP) / SAKIP sampai pelaksana.
 * Skema: db/update_2026-09-26_ikp_kinerja.sql.
 */
class IkpBulananModel extends Model
{
    protected $table         = 'ikp_bulanan';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $protectFields = true;
    protected $allowedFields = ['ikp_id', 'tahun', 'bulan', 'target', 'target_teks', 'realisasi', 'realisasi_teks', 'keterangan', 'bukti_url', 'realisasi_oleh', 'realisasi_pada'];
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';

    /** Tabel tersedia? (instalasi yang belum menjalankan migrasi tidak 500). */
    public function siap(): bool
    {
        return $this->db->tableExists($this->table);
    }
}
