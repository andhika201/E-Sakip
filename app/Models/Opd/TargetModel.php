<?php

namespace App\Models\Opd;

use CodeIgniter\Model;

class TargetModel extends Model
{
    protected $table = 'target_rencana';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;

    protected $allowedFields = [
        // relasi per tahun (mengacu ke renstra_target)
        'renstra_target_id',
        // simpan OPD jika tabel target_rencana memiliki kolom ini
        'opd_id',

        'rencana_aksi',
        'capaian',
        'target_triwulan_1',
        'target_triwulan_2',
        'target_triwulan_3',
        'target_triwulan_4',
        'penanggung_jawab',
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    /**
     * Ambil seluruh target_rencana + relasinya (tanpa filter tahun/OPD)
     * Relasi utama via renstra_target_id -> renstra_target -> renstra_indikator_sasaran -> renstra_sasaran
     */
    public function getAllTargetWithRelasi(): array
    {
        return $this->db->table('target_rencana AS tr')
            ->select('
                tr.*,
                rt.id        AS renstra_target_id,
                rt.tahun     AS indikator_tahun,
                rt.target    AS indikator_target,
                ris.indikator_sasaran,
                ris.satuan,
                rs.sasaran   AS sasaran_renstra
            ')
            ->join('renstra_target             AS rt', 'rt.id = tr.renstra_target_id', 'left')
            ->join('renstra_indikator_sasaran  AS ris', 'ris.id = rt.renstra_indikator_id', 'left')
            ->join('renstra_sasaran            AS rs', 'rs.id = ris.renstra_sasaran_id', 'left')
            ->orderBy('tr.id', 'ASC')
            ->get()->getResultArray();
    }

    /**
     * Detail 1 baris target_rencana + relasi lengkap
     */
    public function getTargetDetail(int $id): ?array
    {
        return $this->db->table('target_rencana AS tr')
            ->select('
                tr.*,
                rt.id        AS renstra_target_id,
                rt.tahun     AS indikator_tahun,
                rt.target    AS indikator_target,

                ris.id       AS indikator_id,
                ris.indikator_sasaran,
                ris.satuan,

                rs.id        AS renstra_sasaran_id,
                rs.sasaran   AS sasaran_renstra
            ')
            ->join('renstra_target             AS rt', 'rt.id = tr.renstra_target_id', 'left')
            ->join('renstra_indikator_sasaran  AS ris', 'ris.id = rt.renstra_indikator_id', 'left')
            ->join('renstra_sasaran            AS rs', 'rs.id = ris.renstra_sasaran_id', 'left')
            ->where('tr.id', $id)
            ->get()->getRowArray();
    }

    /**
     * Ambil semua target_rencana milik satu RENSTRA sasaran
     */
    public function getByRenstraSasaran(int $renstraSasaranId): array
    {
        return $this->db->table('target_rencana AS tr')
            ->select('tr.*')
            ->join('renstra_target             AS rt', 'rt.id = tr.renstra_target_id', 'left')
            ->join('renstra_indikator_sasaran  AS ris', 'ris.id = rt.renstra_indikator_id', 'left')
            ->join('renstra_sasaran            AS rs', 'rs.id = ris.renstra_sasaran_id', 'left')
            ->where('rs.id', $renstraSasaranId)
            ->orderBy('tr.id', 'ASC')
            ->get()->getResultArray();
    }

    /**
     * Ambil seluruh target_rencana berdasarkan TAHUN RENSTRA (renstra_target.tahun)
     * Opsional filter OPD (rs.opd_id) dan/atau tr.opd_id bila dibutuhkan.
     */
    public function getByTahun(string $tahun, ?int $opdId = null): array
    {
        $b = $this->db->table('target_rencana AS tr')
            ->select('
                tr.*,
                rt.tahun     AS indikator_tahun,
                rt.target    AS indikator_target,
                ris.indikator_sasaran,
                ris.satuan,
                rs.sasaran   AS sasaran_renstra
            ')
            ->join('renstra_target             AS rt', 'rt.id = tr.renstra_target_id', 'left')
            ->join('renstra_indikator_sasaran  AS ris', 'ris.id = rt.renstra_indikator_id', 'left')
            ->join('renstra_sasaran            AS rs', 'rs.id = ris.renstra_sasaran_id', 'left')
            ->where('rt.tahun', $tahun);

        if (!empty($opdId)) {
            // tampilkan TR milik OPD tsb
            $b->where('tr.opd_id', $opdId);
        }

        return $b->orderBy('rs.id', 'ASC')->get()->getResultArray();
    }

    /**
     * Daftar tahun tersedia (dari renstra_target)
     */
    public function getAvailableYears(): array
    {
        return $this->db->table('renstra_target')
            ->select('tahun')
            ->distinct()
            ->orderBy('tahun', 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * Dataset lengkap (target_rencana + renstra indikator/sasaran + rpjmd + renstra_target)
     * Opsional filter tahun (rt.tahun) dan OPD (rs.opd_id atau tr.opd_id).
     */
    public function getFullTargetData(?string $tahun = null, ?int $opdId = null): array
    {
        $b = $this->db->table('target_rencana AS tr')
            ->select('
                tr.*,
                rt.target  AS indikator_target,
                rt.tahun   AS indikator_tahun,

                ris.indikator_sasaran,
                ris.satuan,

                rs.sasaran        AS sasaran_renstra,
                rps.sasaran_rpjmd,
                rpt.tujuan_rpjmd
            ')
            ->join('renstra_target             AS rt', 'rt.id = tr.renstra_target_id', 'left')
            ->join('renstra_indikator_sasaran  AS ris', 'ris.id = rt.renstra_indikator_id', 'left')
            ->join('renstra_sasaran            AS rs', 'rs.id = ris.renstra_sasaran_id', 'left')
            ->join('rpjmd_sasaran              AS rps', 'rps.id = rs.rpjmd_sasaran_id', 'left')
            ->join('rpjmd_tujuan               AS rpt', 'rpt.id = rps.tujuan_id', 'left');

        if ($tahun) {
            $b->where('rt.tahun', $tahun);
        }
        if (!empty($opdId)) {
            // jika Anda ingin menampilkan hanya TR milik OPD tertentu, gunakan tr.opd_id
            $b->where('tr.opd_id', $opdId);
        }

        return $b->orderBy('rs.id', 'ASC')->get()->getResultArray();
    }

    /**
     * List indikator RENSTRA + target renstra (per tahun) + data target_rencana (triwulan)
     * Dipakai untuk tampilan daftar per tahun.
     * Join target_rencana melalui renstra_target_id.
     * Opsional filter tahun & OPD.
     *
     * Penting:
     * - Jika $opdId diberikan, join ke TR diberi syarat tambahan "AND tr.opd_id = $opdId"
     *   agar yang ditampilkan hanya TR milik OPD tsb, tapi indikator/tahun yang belum punya TR tetap muncul.
     */
    public function getTargetListByRenstra(?string $tahun = null, ?int $opdId = null): array
    {
        // Join ke TR dengan kondisi dinamis (supaya tidak "mengambil" TR milik OPD lain)
        $trJoin = 'tr.renstra_target_id = rt.id';
        if (!empty($opdId)) {
            $trJoin .= ' AND tr.opd_id = ' . (int) $opdId;
        }

        $b = $this->db->table('renstra_indikator_sasaran AS ris')
            ->select('
                ris.id            AS indikator_id,
                ris.indikator_sasaran,
                ris.satuan,

                rt.id             AS renstra_target_id,
                rt.target         AS indikator_target,
                rt.tahun          AS indikator_tahun,

                rs.id             AS renstra_sasaran_id,
                rs.sasaran        AS sasaran_renstra,
                rs.opd_id         AS opd_id,

                rps.sasaran_rpjmd,
                rpt.tujuan_rpjmd,

                tr.id             AS target_id,
                tr.rencana_aksi,
                tr.capaian,
                tr.target_triwulan_1,
                tr.target_triwulan_2,
                tr.target_triwulan_3,
                tr.target_triwulan_4,
                tr.penanggung_jawab
            ')
            ->join('renstra_target  AS rt', 'rt.renstra_indikator_id = ris.id', 'left')
            ->join('renstra_sasaran AS rs', 'rs.id = ris.renstra_sasaran_id', 'left')
            ->join('rpjmd_sasaran   AS rps', 'rps.id = rs.rpjmd_sasaran_id', 'left')
            ->join('rpjmd_tujuan    AS rpt', 'rpt.id = rps.tujuan_id', 'left')
            // LEFT JOIN ke target_rencana dengan filter OPD tersemat (jika ada)
            ->join('target_rencana  AS tr', $trJoin, 'left');

        if ($tahun) {
            $b->where('rt.tahun', $tahun);
        }
        if (!empty($opdId)) {
            // batasi indikator ke OPD tersebut (yang bertanggung jawab)
            $b->where('rs.opd_id', $opdId);
        }

        return $b->orderBy('rpt.id', 'ASC')
            ->orderBy('rps.id', 'ASC')
            ->orderBy('rs.id', 'ASC')
            ->orderBy('ris.id', 'ASC')
            ->orderBy('rt.tahun', 'ASC')
            ->get()->getResultArray();
    }
    public function getTargetListByRenstraAdminKab(?string $tahun = null, ?int $opdId = null): array
    {
        $trJoin = 'tr.renstra_target_id = rt.id';
        if (!empty($opdId)) {
            $trJoin .= ' AND tr.opd_id = ' . (int) $opdId;
        }

        $b = $this->db->table('renstra_target AS rt')
            // RENSTRA (basis baris)
            ->select('rt.id AS renstra_target_id, rt.tahun AS indikator_tahun, rt.target AS indikator_target')
            // Indikator & Satuan
            ->select('ris.id AS indikator_id, ris.indikator_sasaran, ris.satuan')
            // Sasaran & OPD pemilik
            ->select('rs.id AS renstra_sasaran_id, rs.sasaran AS sasaran_renstra, rs.opd_id AS opd_id')
            // RPJMD
            ->select('rps.sasaran_rpjmd, rpt.tujuan_rpjmd')
            // Target Rencana (bisa NULL)
            ->select('tr.id AS target_id, tr.rencana_aksi, tr.capaian, tr.target_triwulan_1, tr.target_triwulan_2, tr.target_triwulan_3, tr.target_triwulan_4, tr.penanggung_jawab')
            ->join('renstra_indikator_sasaran AS ris', 'ris.id = rt.renstra_indikator_id', 'left')
            ->join('renstra_sasaran          AS rs', 'rs.id  = ris.renstra_sasaran_id', 'left')
            ->join('rpjmd_sasaran            AS rps', 'rps.id = rs.rpjmd_sasaran_id', 'left')
            ->join('rpjmd_tujuan             AS rpt', 'rpt.id = rps.tujuan_id', 'left')
            ->join('target_rencana           AS tr', $trJoin, 'left');

        if (!empty($tahun)) {
            $b->where('rt.tahun', $tahun);
        }
        if (!empty($opdId)) {
            $b->where('rs.opd_id', $opdId);
        }

        return $b->orderBy('rpt.id', 'ASC')
            ->orderBy('rps.id', 'ASC')
            ->orderBy('rs.id', 'ASC')
            ->orderBy('ris.id', 'ASC')
            ->orderBy('rt.tahun', 'ASC')
            ->get()->getResultArray();
    }


    /**
     * Cek apakah sudah ada target_rencana untuk kombinasi OPD + renstra_target_id
     * (mencegah duplikasi entri per tahun per indikator per OPD)
     */
    public function existsFor(int $opdId, int $renstraTargetId): ?array
    {
        return $this->where([
            'opd_id' => $opdId,
            'renstra_target_id' => $renstraTargetId,
        ])->first();
    }

    /** Update helper */
    public function updateTarget(int $id, array $data): bool
    {
        return $this->update($id, $data);
    }
}
