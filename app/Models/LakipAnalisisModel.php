<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Analisis Faktor Pencapaian Kinerja (tabel `lakip_analisis_faktor`).
 *
 * Satu indikator LAKIP boleh punya BANYAK baris analisis. Penambatnya adalah
 * TARGET (renstra_target / rpjmd_target) — pola yang sama dengan tabel `lakip`
 * — karena baris target sudah memuat indikator + tahun sekaligus, dan analisis
 * harus tetap bisa diisi walau baris LAKIP-nya belum dibuat.
 */
class LakipAnalisisModel extends Model
{
    protected $table = 'lakip_analisis_faktor';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $protectFields = true;

    protected $allowedFields = [
        'renstra_target_id',
        'rpjmd_target_id',
        'opd_id',
        'tahun',
        'faktor_pendukung',
        'faktor_penghambat',
        'upaya_peningkatan',
        'created_by',
        'updated_by',
    ];

    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    /** Tabelnya opsional: instalasi lama boleh belum menjalankan migrasinya. */
    public function siap(): bool
    {
        return $this->db->tableExists($this->table);
    }

    /**
     * Analisis satu tahun, dikelompokkan per target — siap dipakai view.
     *
     * Satu query untuk seluruh halaman (hindari N+1 per indikator).
     *
     * @param string   $mode  'opd' (renstra) | 'kabupaten' (rpjmd)
     * @param int|null $opdId null/0 = tingkat kabupaten atau lintas OPD
     *
     * @return array<int, array<int, array<string, mixed>>> [target_id => daftar analisis]
     */
    public function getByTahunGrouped(string $tahun, string $mode, ?int $opdId = null): array
    {
        if (!$this->siap()) {
            return [];
        }

        $kolom = ($mode === 'kabupaten') ? 'rpjmd_target_id' : 'renstra_target_id';

        $b = $this->db->table($this->table)
            ->where('tahun', $tahun)
            ->where($kolom . ' IS NOT NULL', null, false)
            ->orderBy('id', 'ASC');

        // Mode kabupaten selalu opd_id = 0; mode OPD dibatasi kalau OPD-nya jelas
        // (admin_kab yang melihat "semua OPD" tidak memfilter).
        if ($mode === 'kabupaten') {
            $b->where('opd_id', 0);
        } elseif (!empty($opdId)) {
            $b->where('opd_id', (int) $opdId);
        }

        $map = [];
        foreach ($b->get()->getResultArray() as $row) {
            $map[(int) $row[$kolom]][] = $row;
        }

        return $map;
    }

    /** Satu baris analisis (null bila tabelnya belum ada / id tidak ketemu). */
    public function ambil(int $id): ?array
    {
        return $this->siap() ? $this->find($id) : null;
    }
}
