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

    protected $allowedFields = [
        'opd_id',
        'target_rencana_id',      // SELALU mengarah ke target_rencana.id
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
     * Versi lama (kompatibilitas) – basis RENSTRA, semua target_rencana
     * yang punya renstra_target_id.
     */
    public function getMonevWithRelasi(?string $tahun = null, ?int $opdId = null): array
    {
        $b = $this->db->table($this->table . ' AS m')
            ->select('rs.sasaran AS sasaran_renstra')
            ->select('ris.id AS indikator_id, ris.indikator_sasaran, ris.satuan')
            ->select('rt.id AS renstra_target_id, rt.target AS indikator_target, rt.tahun AS indikator_tahun')
            ->select('
                tr.id AS target_id,
                tr.rencana_aksi,
                tr.capaian,
                tr.target_triwulan_1,
                tr.target_triwulan_2,
                tr.target_triwulan_3,
                tr.target_triwulan_4,
                tr.penanggung_jawab,
                tr.rpjmd_target_id
            ')
            ->select('
                m.id AS monev_id,
                m.opd_id AS monev_opd_id,
                m.capaian_triwulan_1,
                m.capaian_triwulan_2,
                m.capaian_triwulan_3,
                m.capaian_triwulan_4,
                m.total
            ')
            ->join(
                'target_rencana AS tr',
                'tr.id = m.target_rencana_id AND (m.opd_id IS NULL OR m.opd_id = tr.opd_id)',
                'right'
            )
            ->join('renstra_target AS rt', 'rt.id = tr.renstra_target_id', 'left')
            ->join('renstra_indikator_sasaran AS ris', 'ris.id = rt.renstra_indikator_id', 'left')
            ->join('renstra_sasaran AS rs', 'rs.id = ris.renstra_sasaran_id', 'left')
            ->join('opd AS o', 'o.id = tr.opd_id', 'left')
            ->select('o.nama_opd')
            ->where('tr.renstra_target_id IS NOT NULL', null, false);

        if (!empty($tahun)) {
            $b->where('rt.tahun', $tahun);
        }

        if (!empty($opdId)) {
            $b->where('tr.opd_id', (int) $opdId);
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

    /* =========================================================
     *  ADMIN KAB – MODE "OPD"  (RENSTRA)
     * =======================================================*/
    public function getIndexDataAdminKabModeOpd(?string $tahun = null, ?int $opdId = null): array
    {
        $b = $this->db->table('target_rencana AS tr')
            ->select('
                tr.id  AS target_id,
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
                rt.id    AS renstra_target_id,
                rt.tahun AS indikator_tahun,
                rt.target AS indikator_target
            ')
            ->select('
                ris.id AS indikator_id,
                ris.indikator_sasaran,
                ris.satuan
            ')
            ->select('
                rs.id      AS renstra_sasaran_id,
                rs.sasaran AS sasaran_renstra,
                rs.opd_id
            ')
            ->select('
                m.id  AS monev_id,
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
            ->join('opd AS o', 'o.id = tr.opd_id', 'left')
            ->select('o.nama_opd')
            ->join(
                $this->table . ' AS m',
                'm.target_rencana_id = tr.id AND m.opd_id = tr.opd_id',
                'left'
            )
            ->where('tr.renstra_target_id IS NOT NULL', null, false);

        if (!empty($tahun)) {
            $b->where('rt.tahun', $tahun);
        }

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

    /* =========================================================
     *  ADMIN KAB – MODE "KABUPATEN" (RPJMD)
     *  opd_id di monev = NULL
     * =======================================================*/
    public function getIndexDataAdminKabModeKab(?string $tahun = null): array
    {
        $b = $this->db->table('target_rencana AS tr')
            ->select('
                tr.id  AS target_id,
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
            // RPJMD target (tahun + target tahunan)
            ->select('
                rpt.id    AS rpjmd_target_id,
                rpt.tahun AS indikator_tahun,
                rpt.target_tahunan AS indikator_target
            ')
            // indikator & satuan RPJMD
            ->select('
                rpis.id AS indikator_id,
                rpis.indikator_sasaran,
                rpis.satuan
            ')
            // sasaran RPJMD
            ->select('
                rps.id      AS rpjmd_sasaran_id,
                rps.sasaran_rpjmd AS sasaran_renstra
            ')
            ->select('
                m.id  AS monev_id,
                m.opd_id AS monev_opd_id,
                m.capaian_triwulan_1,
                m.capaian_triwulan_2,
                m.capaian_triwulan_3,
                m.capaian_triwulan_4,
                m.total AS monev_total
            ')
            ->join('rpjmd_target AS rpt', 'rpt.id = tr.rpjmd_target_id', 'left')
            ->join('rpjmd_indikator_sasaran AS rpis', 'rpis.id = rpt.indikator_sasaran_id', 'left')
            ->join('rpjmd_sasaran AS rps', 'rps.id = rpis.sasaran_id', 'left')
            ->join('opd AS o', 'o.id = tr.opd_id', 'left')
            ->select('o.nama_opd')
            // >>> di mode KAB, monev.opd_id = NULL <<<
            ->join(
                $this->table . ' AS m',
                'm.target_rencana_id = tr.id AND m.opd_id IS NULL',
                'left'
            )
            ->where('tr.rpjmd_target_id IS NOT NULL', null, false);

        if (!empty($tahun)) {
            $b->where('rpt.tahun', $tahun);
        }

        return $b->orderBy('rps.id', 'ASC')
            ->orderBy('rpis.id', 'ASC')
            ->orderBy('rpt.tahun', 'ASC')
            ->orderBy('tr.id', 'ASC')
            ->get()
            ->getResultArray();
    }

    /* =========================================================
     *  ADMIN OPD – basis RENSTRA (renstra_target_id)
     * =======================================================*/
    public function getIndexDataAdminOpd(int $opdId, ?string $tahun = null): array
    {
        $b = $this->db->table('target_rencana AS tr')
            ->select('
                tr.id  AS target_id,
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
                rt.id    AS renstra_target_id,
                rt.tahun AS indikator_tahun,
                rt.target AS indikator_target
            ')
            ->select('
                ris.id AS indikator_id,
                ris.indikator_sasaran,
                ris.satuan
            ')
            ->select('
                rs.id      AS renstra_sasaran_id,
                rs.sasaran AS sasaran_renstra,
                rs.opd_id
            ')
            ->select('
                m.id  AS monev_id,
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
            ->join('opd AS o', 'o.id = tr.opd_id', 'left')
            ->select('o.nama_opd')
            ->join(
                $this->table . ' AS m',
                'm.target_rencana_id = tr.id AND m.opd_id = tr.opd_id',
                'left'
            )
            ->where('tr.opd_id', (int) $opdId)
            ->where('tr.renstra_target_id IS NOT NULL', null, false);

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
     * Daftar tahun RENSTRA (untuk dropdown).
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
     * Ambil satu baris monev berdasarkan (target_rencana_id, opd_id).
     * Untuk mode KAB: $opdId = null (cari m.opd_id IS NULL).
     */
    public function findByTargetAndOpd(int $targetRencanaId, ?int $opdId): ?array
    {
        $builder = $this->where('target_rencana_id', $targetRencanaId);

        if ($opdId === null) {
            $builder->where('opd_id IS NULL', null, false);
        } else {
            $builder->where('opd_id', $opdId);
        }

        return $builder->first();
    }

    /**
     * Upsert per (target_rencana_id, opd_id).
     * - Mode OPD:  $opdId = id OPD
     * - Mode KAB:  $opdId = null
     */
    public function upsertForTarget(int $targetRencanaId, ?int $opdId, array $payload): array
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
