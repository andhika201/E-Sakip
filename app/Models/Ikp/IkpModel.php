<?php

namespace App\Models\Ikp;

use CodeIgniter\Model;

/**
 * Tabel `ikp` — modul Kinerja Prioritas (IKP) / SAKIP sampai pelaksana.
 * Skema: db/update_2026-09-26_ikp_kinerja.sql.
 */
class IkpModel extends Model
{
    protected $table         = 'ikp';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $protectFields = true;
    protected $allowedFields = ['opd_id', 'periode_awal', 'periode_akhir', 'kategori', 'program_unggulan_id', 'rpjmd_misi_id', 'sasaran_pembangunan_id', 'outcome', 'indikator_outcome', 'program_opd', 'bidang_urusan', 'output_prioritas', 'satuan_id', 'satuan_teks', 'metode', 'baseline', 'target_5_tahun', 'target_5_tahun_teks', 'dasar_penugasan', 'buku_saku_id', 'cascading_sasaran_id', 'cascading_indikator_id', 'pj_pegawai_id', 'pj_jabatan_teks', 'urutan', 'legacy_prioritas_ikp_id', 'created_by', 'updated_by', 'dihapus_pada'];
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';

    /** Kategori IKP (kunci => label). Kunci dipakai di DB & query string ?kategori=. */
    public const KATEGORI = [
        'program_unggulan'   => 'Program Unggulan Bupati',
        'program_prioritas'  => 'Program Prioritas',
        'penugasan_khusus'   => 'Penugasan Khusus',
        'penugasan_tambahan' => 'Penugasan Tambahan',
    ];

    /** Metode perhitungan — kosakata yang sama dengan monev & capaian_helper. */
    public const METODE = [
        'sum'         => 'Akumulasi (dijumlah per bulan)',
        'trend_naik'  => 'Posisi akhir, makin tinggi makin baik',
        'trend_turun' => 'Posisi akhir, makin rendah makin baik',
        'trend_flat'  => 'Posisi dipertahankan (sama tiap periode)',
    ];

    /**
     * Query dasar: hanya IKP yang BELUM dihapus (soft delete). eKin merujuk
     * ikp.id, jadi baris tidak pernah dihapus fisik dari antarmuka.
     */
    public function aktif(): self
    {
        return $this->where('ikp.dihapus_pada', null);
    }

    /** Tabel tersedia? (instalasi yang belum menjalankan migrasi tidak 500). */
    public function siap(): bool
    {
        return $this->db->tableExists($this->table);
    }
}
