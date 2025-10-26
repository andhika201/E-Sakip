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
     * Ambil data monev beserta relasi target_rencana dan seluruh hierarki RENSTRA/RPJMD.
     * - $tahun  : filter berdasarkan rt.tahun (opsional)
     * - $opdId  : filter berdasarkan rs.opd_id (opsional)
     *
     * Pakai RIGHT JOIN ke target_rencana agar baris target tanpa monev tetap muncul.
     */
    public function getMonevWithRelasi(?string $tahun = null, ?int $opdId = null): array
    {
        $b = $this->db->table($this->table . ' AS m')
            ->select('
                rpt.tujuan_rpjmd,
                rps.sasaran_rpjmd,
                rs.sasaran                      AS sasaran_renstra,

                ris.id                          AS indikator_id,
                ris.indikator_sasaran,
                ris.satuan,

                rt.target                       AS indikator_target,
                rt.tahun                        AS indikator_tahun,

                tr.id                           AS target_id,
                tr.rencana_aksi,
                tr.capaian,
                tr.target_triwulan_1,
                tr.target_triwulan_2,
                tr.target_triwulan_3,
                tr.target_triwulan_4,
                tr.penanggung_jawab,

                m.id                            AS monev_id,
                m.capaian_triwulan_1,
                m.capaian_triwulan_2,
                m.capaian_triwulan_3,
                m.capaian_triwulan_4,
                m.total
            ')
            // target_rencana selalu ada, monev bisa belum ada
            ->join('target_rencana               AS tr', 'tr.id = m.target_rencana_id', 'right')
            ->join('renstra_target               AS rt', 'rt.id = tr.renstra_target_id', 'left')
            ->join('renstra_indikator_sasaran    AS ris', 'ris.id = rt.renstra_indikator_id', 'left')
            ->join('renstra_sasaran              AS rs', 'rs.id  = ris.renstra_sasaran_id', 'left')
            ->join('rpjmd_sasaran                AS rps', 'rps.id = rs.rpjmd_sasaran_id', 'left')
            ->join('rpjmd_tujuan                 AS rpt', 'rpt.id = rps.tujuan_id', 'left');

        if ($tahun) {
            $b->where('rt.tahun', $tahun);
        }
        if (!empty($opdId)) {
            $b->where('rs.opd_id', (int) $opdId);
        }

        return $b->orderBy('rs.id', 'ASC')->get()->getResultArray();
    }

    /**
     * Ambil daftar tahun yang tersedia (dari renstra_target).
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
}
