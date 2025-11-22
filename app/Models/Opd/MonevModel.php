<?php

namespace App\Models\Opd;

use CodeIgniter\Model;

class MonevModel extends Model
{
    protected $table = 'monev';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $protectFields = true;

    // Kolom sesuai tabel monev di schema
    protected $allowedFields = [
        'opd_id',
        'target_rencana_id',
        'capaian_triwulan_1',
        'capaian_triwulan_2',
        'capaian_triwulan_3',
        'capaian_triwulan_4',
        'total',
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    /**
     * Versi lama untuk kompatibilitas:
     * basis monev RIGHT JOIN target_rencana.
     */
    public function getMonevWithRelasi(?string $tahun = null, ?int $opdId = null): array
    {
        $b = $this->db->table($this->table . ' AS m')
            ->select('rs.sasaran AS sasaran_renstra')
            ->select('ris.id AS indikator_id, ris.indikator_sasaran, ris.satuan')
            ->select('rt.id AS renstra_target_id, rt.target AS indikator_target, rt.tahun AS indikator_tahun')
            ->select('tr.id AS target_id, tr.rencana_aksi, tr.capaian, tr.target_triwulan_1, tr.target_triwulan_2, tr.target_triwulan_3, tr.target_triwulan_4, tr.penanggung_jawab, tr.rpjmd_target_id')
            ->select('m.id AS monev_id, m.opd_id AS monev_opd_id, m.capaian_triwulan_1, m.capaian_triwulan_2, m.capaian_triwulan_3, m.capaian_triwulan_4, m.total')
            ->join('target_rencana AS tr', 'tr.id = m.target_rencana_id AND (m.opd_id IS NULL OR m.opd_id = tr.opd_id)', 'right')
            ->join('renstra_target AS rt', 'rt.id = tr.renstra_target_id', 'left')
            ->join('renstra_indikator_sasaran AS ris', 'ris.id = rt.renstra_indikator_id', 'left')
            ->join('renstra_sasaran AS rs', 'rs.id = ris.renstra_sasaran_id', 'left');

        if (!empty($tahun)) {
            $b->where('rt.tahun', $tahun);
        }

        if (!empty($opdId)) {
            $b->where('rs.opd_id', (int) $opdId);
            $b->groupStart()
                ->where('m.opd_id', (int) $opdId)
                ->orWhere('m.opd_id IS NULL', null, false)
                ->groupEnd();
        }

        return $b->orderBy('rs.id', 'ASC')
            ->orderBy('ris.id', 'ASC')
            ->orderBy('rt.tahun', 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * ============================
     *  ADMIN KAB - MODE "OPD"
     * ============================
     *
     * - Basis: TARGET_RENCANA (semua OPD)
     * - Bisa filter per tahun & opd_id
     * - Tetap join ke MONEV per (target_rencana_id, opd_id)
     */
    public function getIndexDataAdminKabModeOpd(?string $tahun = null, ?int $opdId = null): array
    {
        $b = $this->db->table('target_rencana AS tr')
            ->select('
                tr.id AS target_id,
                tr.opd_id,
                tr.rencana_aksi,
                tr.capaian AS target_capaian,
                tr.target_triwulan_1,
                tr.target_triwulan_2,
                tr.target_triwulan_3,
                tr.target_triwulan_4,
                tr.penanggung_jawab,
                tr.rpjmd_target_id
            ')
            ->select('
                rt.id AS renstra_target_id,
                rt.tahun AS indikator_tahun,
                rt.target AS indikator_target
            ')
            ->select('
                ris.id AS indikator_id,
                ris.indikator_sasaran,
                ris.satuan
            ')
            ->select('
                rs.id AS renstra_sasaran_id,
                rs.sasaran AS sasaran_renstra,
                rs.opd_id
            ')
            ->select('
                m.id AS monev_id,
                m.opd_id AS monev_opd_id,
                m.capaian_triwulan_1,
                m.capaian_triwulan_2,
                m.capaian_triwulan_3,
                m.capaian_triwulan_4,
                m.total AS monev_total
            ')
            ->join('renstra_target AS rt', 'rt.id = tr.renstra_target_id', 'left')
            ->join('renstra_indikator_sasaran AS ris', 'ris.id = rt.renstra_indikator_id', 'left')
            ->join('renstra_sasaran AS rs', 'rs.id = ris.renstra_sasaran_id', 'left')
            // monev dikunci per (target_rencana_id, opd_id)
            ->join($this->table . ' AS m', 'm.target_rencana_id = tr.id AND m.opd_id = tr.opd_id', 'left');

        // Filter tahun (tahun RENSTRA_TARGET)
        if (!empty($tahun)) {
            $b->where('rt.tahun', $tahun);
        }

        // Jika $opdId dikirim → filter OPD tertentu.
        // Jika null → semua OPD tampil.
        if (!empty($opdId)) {
            $b->where('tr.opd_id', (int) $opdId);
        }

        return $b->orderBy('rs.id', 'ASC')
            ->orderBy('ris.id', 'ASC')
            ->orderBy('rt.tahun', 'ASC')
            ->orderBy('tr.id', 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * ================================
     *  ADMIN KAB - MODE "KABUPATEN"
     * ================================
     *
     * - Basis: TARGET_RENCANA yang punya rpjmd_target_id (bukan NULL)
     * - Tidak difilter per opd_id (semua OPD ikut),
     *   tapi tetap join monev ke target_rencana & opd yang sama.
     * - Dipakai ketika mode = "kab", fokus ke target yang terhubung RPJMD.
     */
    public function getIndexDataAdminKabModeKab(?string $tahun = null): array
    {
        $b = $this->db->table('target_rencana AS tr')
            ->select('
                tr.id AS target_id,
                tr.opd_id,
                tr.rencana_aksi,
                tr.capaian AS target_capaian,
                tr.target_triwulan_1,
                tr.target_triwulan_2,
                tr.target_triwulan_3,
                tr.target_triwulan_4,
                tr.penanggung_jawab,
                tr.rpjmd_target_id
            ')
            ->select('
                rt.id AS renstra_target_id,
                rt.tahun AS indikator_tahun,
                rt.target AS indikator_target
            ')
            ->select('
                ris.id AS indikator_id,
                ris.indikator_sasaran,
                ris.satuan
            ')
            ->select('
                rs.id AS renstra_sasaran_id,
                rs.sasaran AS sasaran_renstra,
                rs.opd_id
            ')
            ->select('
                m.id AS monev_id,
                m.opd_id AS monev_opd_id,
                m.capaian_triwulan_1,
                m.capaian_triwulan_2,
                m.capaian_triwulan_3,
                m.capaian_triwulan_4,
                m.total AS monev_total
            ')
            ->join('renstra_target AS rt', 'rt.id = tr.renstra_target_id', 'left')
            ->join('renstra_indikator_sasaran AS ris', 'ris.id = rt.renstra_indikator_id', 'left')
            ->join('renstra_sasaran AS rs', 'rs.id = ris.renstra_sasaran_id', 'left')
            // monev tetap dikunci per target_rencana & opd,
            // hanya saja TIDAK ada filter opd_id dari parameter.
            ->join($this->table . ' AS m', 'm.target_rencana_id = tr.id AND m.opd_id = tr.opd_id', 'left')
            // Hanya target yang terhubung ke RPJMD (ada rpjmd_target_id)
            ->where('tr.rpjmd_target_id IS NOT NULL', null, false);

        if (!empty($tahun)) {
            $b->where('rt.tahun', $tahun);
        }

        return $b->orderBy('rs.id', 'ASC')
            ->orderBy('ris.id', 'ASC')
            ->orderBy('rt.tahun', 'ASC')
            ->orderBy('tr.id', 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * LISTING ADMIN OPD (seperti sebelumnya).
     * Basis TARGET_RENCANA OPD tertentu.
     */
    public function getIndexDataAdminOpd(?string $tahun = null, int $opdId): array
    {
        $b = $this->db->table('target_rencana AS tr')
            ->select('
                tr.id AS target_id,
                tr.opd_id,
                tr.rencana_aksi,
                tr.capaian AS target_capaian,
                tr.target_triwulan_1,
                tr.target_triwulan_2,
                tr.target_triwulan_3,
                tr.target_triwulan_4,
                tr.penanggung_jawab,
                tr.rpjmd_target_id
            ')
            ->select('
                rt.id AS renstra_target_id,
                rt.tahun AS indikator_tahun,
                rt.target AS indikator_target
            ')
            ->select('
                ris.id AS indikator_id,
                ris.indikator_sasaran,
                ris.satuan
            ')
            ->select('
                rs.id AS renstra_sasaran_id,
                rs.sasaran AS sasaran_renstra,
                rs.opd_id
            ')
            ->select('
                m.id AS monev_id,
                m.opd_id AS monev_opd_id,
                m.capaian_triwulan_1,
                m.capaian_triwulan_2,
                m.capaian_triwulan_3,
                m.capaian_triwulan_4,
                m.total AS monev_total
            ')
            ->join('renstra_target AS rt', 'rt.id = tr.renstra_target_id', 'left')
            ->join('renstra_indikator_sasaran AS ris', 'ris.id = rt.renstra_indikator_id', 'left')
            ->join('renstra_sasaran AS rs', 'rs.id = ris.renstra_sasaran_id', 'left')
            ->join($this->table . ' AS m', 'm.target_rencana_id = tr.id AND m.opd_id = tr.opd_id', 'left')
            ->where('tr.opd_id', (int) $opdId);

        if (!empty($tahun)) {
            $b->where('rt.tahun', $tahun);
        }

        return $b->orderBy('rs.id', 'ASC')
            ->orderBy('ris.id', 'ASC')
            ->orderBy('rt.tahun', 'ASC')
            ->orderBy('tr.id', 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * Daftar tahun dari renstra_target (YEAR)
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
     * Ambil satu baris monev berdasarkan pasangan (target_rencana_id, opd_id)
     */
    public function findByTargetAndOpd(int $targetRencanaId, int $opdId): ?array
    {
        return $this->where([
            'target_rencana_id' => $targetRencanaId,
            'opd_id' => $opdId,
        ])->first();
    }

    /**
     * Upsert (insert / update) per (target_rencana_id, opd_id).
     * Total dihitung / diisi di controller / JS.
     */
    public function upsertForTarget(int $targetRencanaId, int $opdId, array $payload): array
    {
        $row = $this->findByTargetAndOpd($targetRencanaId, $opdId);

        $data = [
            'opd_id' => $opdId,
            'target_rencana_id' => $targetRencanaId,
            'capaian_triwulan_1' => $payload['capaian_triwulan_1'] ?? null,
            'capaian_triwulan_2' => $payload['capaian_triwulan_2'] ?? null,
            'capaian_triwulan_3' => $payload['capaian_triwulan_3'] ?? null,
            'capaian_triwulan_4' => $payload['capaian_triwulan_4'] ?? null,
            'total' => array_key_exists('total', $payload) ? $payload['total'] : null,
        ];

        if ($row) {
            $this->update($row['id'], $data);
            return $this->find($row['id']);
        }

        $id = $this->insert($data, true);
        return $this->find($id);
    }
}
