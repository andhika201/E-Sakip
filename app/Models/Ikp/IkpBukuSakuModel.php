<?php

namespace App\Models\Ikp;

use CodeIgniter\Model;

/**
 * Tabel `ikp_buku_saku` — modul Kinerja Prioritas (IKP) / SAKIP sampai pelaksana.
 * Skema: db/update_2026-09-26_ikp_kinerja.sql.
 */
class IkpBukuSakuModel extends Model
{
    protected $table         = 'ikp_buku_saku';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $protectFields = true;
    protected $allowedFields = ['rpjmd_misi_id', 'program_unggulan_id', 'program_unggulan_teks', 'sasaran_pembangunan_id', 'sasaran_pembangunan_teks', 'outcome', 'indikator', 'program_opd', 'output_prioritas', 'satuan', 'target_5_tahun', 'bidang_urusan'];
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';

    /** Tabel tersedia? (instalasi yang belum menjalankan migrasi tidak 500). */
    public function siap(): bool
    {
        return $this->db->tableExists($this->table);
    }
}
