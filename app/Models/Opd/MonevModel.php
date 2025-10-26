<?php

namespace App\Models\Opd;

use CodeIgniter\Model;

class MonevModel extends Model
{
    protected $table = 'monev';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';

    protected $allowedFields = [
        'opd_id',
        'target_rencana_id',
        'capaian_triwulan_1',
        'capaian_triwulan_2',
        'capaian_triwulan_3',
        'capaian_triwulan_4',
        'total', // DIISI MANUAL
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    /**
     * (Asal) Ambil monev + relasi, basis monev RIGHT JOIN target_rencana agar target tanpa monev tetap muncul.
     * Tetap dipertahankan untuk kompatibilitas; gunakan yang AdminKab/AdminOpd di bawah untuk listing terbaru.
     */
    public function getMonevWithRelasi(?string $tahun = null, ?int $opdId = null): array
    {
        $b = $this->db->table($this->table . ' AS m')
            ->select('rpt.tujuan_rpjmd, rps.sasaran_rpjmd, rs.sasaran AS sasaran_renstra')
            ->select('ris.id AS indikator_id, ris.indikator_sasaran, ris.satuan')
            ->select('rt.target AS indikator_target, rt.tahun AS indikator_tahun')
            ->select('tr.id AS target_id, tr.rencana_aksi, tr.capaian, tr.target_triwulan_1, tr.target_triwulan_2, tr.target_triwulan_3, tr.target_triwulan_4, tr.penanggung_jawab')
            ->select('m.id AS monev_id, m.opd_id AS monev_opd_id, m.capaian_triwulan_1, m.capaian_triwulan_2, m.capaian_triwulan_3, m.capaian_triwulan_4, m.total')
            // jaga konsistensi OPD pada ON
            ->join('target_rencana AS tr', 'tr.id = m.target_rencana_id AND (m.opd_id IS NULL OR m.opd_id = tr.opd_id)', 'right')
            ->join('renstra_target AS rt', 'rt.id = tr.renstra_target_id', 'left')
            ->join('renstra_indikator_sasaran AS ris', 'ris.id = rt.renstra_indikator_id', 'left')
            ->join('renstra_sasaran AS rs', 'rs.id = ris.renstra_sasaran_id', 'left')
            ->join('rpjmd_sasaran AS rps', 'rps.id = rs.rpjmd_sasaran_id', 'left')
            ->join('rpjmd_tujuan AS rpt', 'rpt.id = rps.tujuan_id', 'left');

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

        return $b->orderBy('rs.id', 'ASC')->get()->getResultArray();
    }

    /**
     * ADMIN KAB: basis RENSTRA → LEFT JOIN Target (dikunci OPD) → LEFT JOIN Monev (dikunci OPD).
     * Menampilkan semua RENSTRA milik OPD terpilih, meski Target/Monev belum ada.
     */
    public function getIndexDataAdminKab(?string $tahun = null, ?int $opdId = null): array
    {
        $trJoin = 'tr.renstra_target_id = rt.id';
        if (!empty($opdId)) {
            $trJoin .= ' AND tr.opd_id = ' . (int) $opdId;
        }

        $mJoin = 'm.target_rencana_id = tr.id';
        if (!empty($opdId)) {
            $mJoin .= ' AND m.opd_id = ' . (int) $opdId;
        }

        $b = $this->db->table('renstra_target AS rt')
            ->select('rt.id AS renstra_target_id, rt.tahun AS indikator_tahun, rt.target AS indikator_target')
            ->select('ris.id AS indikator_id, ris.indikator_sasaran, ris.satuan')
            ->select('rs.id AS renstra_sasaran_id, rs.sasaran AS sasaran_renstra, rs.opd_id AS opd_id')
            ->select('rps.sasaran_rpjmd, rpt.tujuan_rpjmd')
            ->select('tr.id AS target_id, tr.rencana_aksi, tr.capaian AS target_capaian, tr.target_triwulan_1, tr.target_triwulan_2, tr.target_triwulan_3, tr.target_triwulan_4, tr.penanggung_jawab')
            ->select('m.id AS monev_id, m.opd_id AS monev_opd_id, m.capaian_triwulan_1, m.capaian_triwulan_2, m.capaian_triwulan_3, m.capaian_triwulan_4, m.total AS monev_total')
            ->join('renstra_indikator_sasaran AS ris', 'ris.id = rt.renstra_indikator_id', 'left')
            ->join('renstra_sasaran AS rs', 'rs.id = ris.renstra_sasaran_id', 'left')
            ->join('rpjmd_sasaran AS rps', 'rps.id = rs.rpjmd_sasaran_id', 'left')
            ->join('rpjmd_tujuan AS rpt', 'rpt.id = rps.tujuan_id', 'left')
            ->join('target_rencana AS tr', $trJoin, 'left')
            ->join($this->table . ' AS m', $mJoin, 'left');

        if (!empty($tahun)) {
            $b->where('rt.tahun', $tahun);
        }
        if (!empty($opdId)) {
            $b->where('rs.opd_id', (int) $opdId);
        }

        return $b->orderBy('rpt.id', 'ASC')
            ->orderBy('rps.id', 'ASC')
            ->orderBy('rs.id', 'ASC')
            ->orderBy('ris.id', 'ASC')
            ->orderBy('rt.tahun', 'ASC')
            ->get()->getResultArray();
    }

    /**
     * ADMIN OPD: basis Target Rencana OPD → LEFT JOIN Monev (opd terkunci).
     * Menampilkan semua Target OPD meski Monev belum ada.
     */
    public function getIndexDataAdminOpd(?string $tahun = null, int $opdId): array
    {
        $b = $this->db->table('target_rencana AS tr')
            ->select('tr.id AS target_id, tr.opd_id, tr.rencana_aksi, tr.capaian AS target_capaian, tr.target_triwulan_1, tr.target_triwulan_2, tr.target_triwulan_3, tr.target_triwulan_4, tr.penanggung_jawab')
            ->select('rt.id AS renstra_target_id, rt.tahun AS indikator_tahun, rt.target AS indikator_target')
            ->select('ris.id AS indikator_id, ris.indikator_sasaran, ris.satuan')
            ->select('rs.id AS renstra_sasaran_id, rs.sasaran AS sasaran_renstra, rs.opd_id')
            ->select('rps.sasaran_rpjmd, rpt.tujuan_rpjmd')
            ->select('m.id AS monev_id, m.opd_id AS monev_opd_id, m.capaian_triwulan_1, m.capaian_triwulan_2, m.capaian_triwulan_3, m.capaian_triwulan_4, m.total AS monev_total')
            ->join('renstra_target AS rt', 'rt.id = tr.renstra_target_id', 'left')
            ->join('renstra_indikator_sasaran AS ris', 'ris.id = rt.renstra_indikator_id', 'left')
            ->join('renstra_sasaran AS rs', 'rs.id = ris.renstra_sasaran_id', 'left')
            ->join('rpjmd_sasaran AS rps', 'rps.id = rs.rpjmd_sasaran_id', 'left')
            ->join('rpjmd_tujuan AS rpt', 'rpt.id = rps.tujuan_id', 'left')
            // kunci monev pada target & OPD
            ->join($this->table . ' AS m', 'm.target_rencana_id = tr.id AND m.opd_id = tr.opd_id', 'left')
            ->where('tr.opd_id', (int) $opdId);

        if (!empty($tahun)) {
            $b->where('rt.tahun', $tahun);
        }

        return $b->orderBy('rpt.id', 'ASC')
            ->orderBy('rps.id', 'ASC')
            ->orderBy('rs.id', 'ASC')
            ->orderBy('ris.id', 'ASC')
            ->orderBy('rt.tahun', 'ASC')
            ->get()->getResultArray();
    }

    /** Daftar tahun (dari renstra_target) */
    public function getAvailableYears(): array
    {
        return $this->db->table('renstra_target')
            ->select('tahun')
            ->distinct()
            ->orderBy('tahun', 'ASC')
            ->get()
            ->getResultArray();
    }

    /** Ambil 1 baris monev per pasangan (opd_id, target_rencana_id) */
    public function findByTargetAndOpd(int $targetRencanaId, int $opdId): ?array
    {
        return $this->where([
            'target_rencana_id' => $targetRencanaId,
            'opd_id' => $opdId,
        ])->first();
    }

    /**
     * Upsert monev per (opd_id, target_rencana_id) TANPA kalkulasi total otomatis.
     * - Controller bertugas memvalidasi & mewajibkan 'total'.
     * - Jika 'total' tidak dikirim, disimpan NULL (boleh kamu ubah jadi wajib via DB constraint).
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
            // total: MANUAL, tidak dihitung otomatis
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
