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
        'opd_id',
        'renstra_target_id',
        'rpjmd_target_id',

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

    /* ======================= UTIL DASAR ======================= */

    public function getAvailableYears(): array
    {
        // Tahun diambil dari renstra_target (buat dropdown tahun)
        return $this->db->table('renstra_target')
            ->select('tahun')
            ->distinct()
            ->orderBy('tahun', 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * Cek duplikasi per kombinasi OPD + renstra_target_id (untuk RENSTRA)
     */
    public function existsFor(int $opdId, int $renstraTargetId): ?array
    {
        return $this->where([
            'opd_id' => $opdId,
            'renstra_target_id' => $renstraTargetId,
        ])->first();
    }

    /* ===================== DETAIL TARGET ====================== */

    public function getTargetDetail(int $id): ?array
    {
        return $this->db->table('target_rencana tr')
            ->select("
                tr.*,
                rt.id      AS renstra_target_id,
                rt.tahun   AS indikator_tahun,
                rt.target  AS indikator_target,
                ris.indikator_sasaran,
                ris.satuan,
                rs.sasaran AS sasaran_renstra
            ")
            ->join('renstra_target rt', 'rt.id = tr.renstra_target_id', 'left')
            ->join('renstra_indikator_sasaran ris', 'ris.id = rt.renstra_indikator_id', 'left')
            ->join('renstra_sasaran rs', 'rs.id = ris.renstra_sasaran_id', 'left')
            ->where('tr.id', $id)
            ->get()
            ->getRowArray();
    }

    /* ========== LIST UNTUK ADMIN OPD (RENSTRA OPD SENDIRI) ========= */

    public function getTargetListByRenstra(?string $tahun = null, ?int $opdId = null): array
    {
        $trJoin = "tr.renstra_target_id = rt.id";
        if (!empty($opdId)) {
            $trJoin .= " AND tr.opd_id = " . (int) $opdId;
        }

        $b = $this->db->table('renstra_indikator_sasaran ris')
            ->select("
                ris.id                 AS indikator_id,
                ris.indikator_sasaran,
                ris.satuan,

                rt.id                  AS renstra_target_id,
                rt.target              AS indikator_target,
                rt.tahun               AS indikator_tahun,

                rs.id                  AS renstra_sasaran_id,
                rs.sasaran             AS sasaran_renstra,
                rs.opd_id              AS opd_id,

                tr.id                  AS target_id,
                tr.rencana_aksi,
                tr.capaian,
                tr.target_triwulan_1,
                tr.target_triwulan_2,
                tr.target_triwulan_3,
                tr.target_triwulan_4,
                tr.penanggung_jawab
            ")
            ->join('renstra_target rt', 'rt.renstra_indikator_id = ris.id', 'left')
            ->join('renstra_sasaran rs', 'rs.id = ris.renstra_sasaran_id', 'left')
            ->join('target_rencana tr', $trJoin, 'left');

        if (!empty($tahun)) {
            $b->where('rt.tahun', $tahun);
        }
        if (!empty($opdId)) {
            $b->where('rs.opd_id', $opdId);
        }

        return $b->orderBy('rs.id', 'ASC')
            ->orderBy('ris.id', 'ASC')
            ->orderBy('rt.tahun', 'ASC')
            ->get()
            ->getResultArray();
    }

    /* ========== LIST UNTUK ADMIN KAB (MODE OPD / RENSTRA) ============== */

    public function getTargetListByRenstraAdminKab(?string $tahun = null, ?int $opdId = null): array
    {
        $trJoin = "tr.renstra_target_id = rt.id";
        if (!empty($opdId)) {
            $trJoin .= " AND tr.opd_id = " . (int) $opdId;
        }

        $b = $this->db->table('renstra_target rt')
            ->select("
            rt.id      AS renstra_target_id,
            rt.tahun   AS indikator_tahun,
            rt.target  AS indikator_target,

            ris.id     AS indikator_id,
            ris.indikator_sasaran,
            ris.satuan,

            rs.id      AS renstra_sasaran_id,
            rs.sasaran AS sasaran_renstra,
            rs.opd_id  AS opd_id,
            o.nama_opd AS nama_opd,   

            tr.id      AS target_id,
            tr.rencana_aksi,
            tr.capaian,
            tr.target_triwulan_1,
            tr.target_triwulan_2,
            tr.target_triwulan_3,
            tr.target_triwulan_4,
            tr.penanggung_jawab
        ")
            ->join('renstra_indikator_sasaran ris', 'ris.id = rt.renstra_indikator_id', 'left')
            ->join('renstra_sasaran rs', 'rs.id = ris.renstra_sasaran_id', 'left')
            ->join('opd o', 'o.id = rs.opd_id', 'left')   
            ->join('target_rencana tr', $trJoin, 'left');

        if (!empty($tahun)) {
            $b->where('rt.tahun', $tahun);
        }
        if (!empty($opdId)) {
            $b->where('rs.opd_id', $opdId);
        }

        return $b->orderBy('rs.id', 'ASC')
            ->orderBy('ris.id', 'ASC')
            ->orderBy('rt.tahun', 'ASC')
            ->get()
            ->getResultArray();
    }


    /* ========== LIST UNTUK ADMIN KAB (MODE KABUPATEN / RPJMD) = */


    public function getTargetListByRpjmdKabupaten(?string $tahun = null): array
    {
        // Di schema: rpjmd_target.indikator_sasaran_id → rpjmd_indikator_sasaran.id
        //            rpjmd_indikator_sasaran.sasaran_id → rpjmd_sasaran.id

        $b = $this->db->table('rpjmd_target rpj')
            ->select("
                rpj.id                  AS rpjmd_target_id,
                rpj.tahun               AS indikator_tahun,
                rpj.target_tahunan      AS indikator_target,

                ris.id                  AS indikator_id,
                ris.indikator_sasaran   AS indikator_sasaran,
                ris.satuan              AS satuan,

                rs.id                   AS rpjmd_sasaran_id,
                rs.sasaran_rpjmd        AS sasaran_renstra,

                tr.id                   AS target_id,
                tr.rencana_aksi,
                tr.capaian,
                tr.target_triwulan_1,
                tr.target_triwulan_2,
                tr.target_triwulan_3,
                tr.target_triwulan_4,
                tr.penanggung_jawab
            ")
            ->join('rpjmd_indikator_sasaran ris', 'ris.id = rpj.indikator_sasaran_id', 'left')
            ->join('rpjmd_sasaran rs', 'rs.id = ris.sasaran_id', 'left')
            ->join('target_rencana tr', 'tr.rpjmd_target_id = rpj.id', 'left');

        if (!empty($tahun)) {
            $b->where('rpj.tahun', $tahun);
        }

        return $b->orderBy('rs.id', 'ASC')
            ->orderBy('ris.id', 'ASC')
            ->orderBy('rpj.tahun', 'ASC')
            ->get()
            ->getResultArray();
    }
}
