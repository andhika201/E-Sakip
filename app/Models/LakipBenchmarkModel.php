<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Benchmark Provinsi Lampung & Nasional untuk indikator LAKIP
 * (tabel `lakip_benchmark`).
 *
 * Penambatnya INDIKATOR + tahun, bukan target:
 *   mode 'kabupaten' -> rpjmd_indikator_id   (rpjmd_indikator_sasaran.id)
 *   mode 'opd'       -> renstra_indikator_id (renstra_indikator_sasaran.id)
 *
 * Satu indikator hanya punya satu baris pembanding per tahun, sehingga tidak
 * ada indikator duplikat khusus benchmark. Nilai Kabupaten/OPD sengaja TIDAK
 * disimpan di sini — tetap dibaca dari `lakip.capaian_tahun_ini`.
 */
class LakipBenchmarkModel extends Model
{
    protected $table = 'lakip_benchmark';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $protectFields = true;

    protected $allowedFields = [
        'rpjmd_indikator_id',
        'renstra_indikator_id',
        'opd_id',
        'tahun',
        'nilai_provinsi',
        'nilai_nasional',
        'sumber_provinsi',
        'sumber_nasional',
        'catatan',
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

    /** Kolom penambat sesuai mode halaman LAKIP. */
    public static function kolomIndikator(string $mode): string
    {
        return $mode === 'kabupaten' ? 'rpjmd_indikator_id' : 'renstra_indikator_id';
    }

    /**
     * Benchmark satu tahun untuk seluruh indikator pada lingkup tertentu,
     * dikunci per indikator_id — satu query untuk seluruh halaman.
     *
     * @param int|null $opdId null = lintas OPD (tidak difilter)
     *
     * @return array<int, array<string, mixed>> [indikator_id => baris benchmark]
     */
    public function getByTahunKeyedByIndikator(string $tahun, string $mode, ?int $opdId = null): array
    {
        if ($tahun === '' || !$this->siap()) {
            return [];
        }

        $kolom = self::kolomIndikator($mode);

        $b = $this->db->table($this->table)
            ->where('tahun', $tahun)
            ->where($kolom . ' IS NOT NULL', null, false);

        // Mode kabupaten selalu opd_id 0. Mode OPD hanya difilter bila OPD-nya
        // memang dipilih; null berarti lintas OPD (halaman baca saja).
        if ($mode === 'kabupaten') {
            $b->where('opd_id', 0);
        } elseif (!empty($opdId)) {
            $b->where('opd_id', (int) $opdId);
        }

        $peta = [];
        foreach ($b->get()->getResultArray() as $baris) {
            $peta[(int) $baris[$kolom]] = $baris;
        }

        return $peta;
    }

    /** Satu baris benchmark milik indikator + tahun tertentu. */
    public function cariByIndikator(int $indikatorId, string $tahun, string $mode): ?array
    {
        if ($indikatorId <= 0 || $tahun === '' || !$this->siap()) {
            return null;
        }

        return $this->db->table($this->table)
            ->where(self::kolomIndikator($mode), $indikatorId)
            ->where('tahun', $tahun)
            ->get()
            ->getRowArray() ?: null;
    }

    /** Satu baris berdasarkan id. */
    public function ambil(int $id): ?array
    {
        if ($id <= 0 || !$this->siap()) {
            return null;
        }

        return $this->db->table($this->table)->where('id', $id)->get()->getRowArray() ?: null;
    }

    /**
     * Pastikan indikator memang ada pada tahun & lingkup tersebut.
     * Pencegah IDOR: id indikator dari request tidak pernah dipercaya.
     *
     * @return array<string, mixed>|null [indikator_id, indikator_sasaran, satuan, opd_id]
     */
    public function indikatorSah(int $indikatorId, string $mode, string $tahun, ?int $opdScope): ?array
    {
        if ($indikatorId <= 0 || $tahun === '') {
            return null;
        }

        if ($mode === 'kabupaten') {
            // Indikator RPJMD dianggap sah bila punya target pada tahun tsb.
            return $this->db->table('rpjmd_indikator_sasaran ris')
                ->select('ris.id AS indikator_id, ris.indikator_sasaran, ris.satuan, 0 AS opd_id')
                ->join('rpjmd_target rpj', 'rpj.indikator_sasaran_id = ris.id', 'inner')
                ->where('ris.id', $indikatorId)
                ->where('rpj.tahun', $tahun)
                ->groupBy('ris.id, ris.indikator_sasaran, ris.satuan')
                ->get()
                ->getRowArray() ?: null;
        }

        $b = $this->db->table('renstra_indikator_sasaran ris')
            ->select('ris.id AS indikator_id, ris.indikator_sasaran, s.satuan AS satuan, rs.opd_id')
            ->join('renstra_target rt', 'rt.renstra_indikator_id = ris.id', 'inner')
            ->join('renstra_sasaran rs', 'rs.id = ris.renstra_sasaran_id', 'left')
            ->join('satuan s', 's.id = ris.satuan', 'left')
            ->where('ris.id', $indikatorId)
            ->where('rt.tahun', $tahun);

        if (!empty($opdScope)) {
            $b->where('rs.opd_id', (int) $opdScope);
        }

        return $b->groupBy('ris.id, ris.indikator_sasaran, s.satuan, rs.opd_id')
            ->get()
            ->getRowArray() ?: null;
    }
}
