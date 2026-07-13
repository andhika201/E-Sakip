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
        'pk_indikator_id',

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
                s.satuan AS satuan,

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
            ->join('satuan s', 's.id = ris.satuan', 'left') 
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
        s.satuan AS satuan,

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
            ->join('satuan s', 's.id = ris.satuan', 'left') 
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

    /* ===================== RENCANA AKSI DARI PK ===================== */
    /*
     * Jangkar baru: target_rencana.pk_indikator_id -> pk_indikator.id.
     * Rantai: pk_indikator -> pk_sasaran -> pk (ambil tahun, opd_id, jenis).
     * Alias dibuat seragam dengan list Renstra/RPJMD agar view bisa dipakai
     * ulang (indikator_sasaran, satuan, indikator_target, indikator_tahun,
     * sasaran_renstra, target_id, dsb).
     */

    /**
     * Cek duplikasi Rencana Aksi per indikator PK.
     * - ES3 (administrator): unik per (opd_id, pk_indikator_id)
     * - Bupati: $opdId = pk.opd_id (OPD Bupati)
     */
    public function existsForPkIndikator(int $pkIndikatorId, ?int $opdId): ?array
    {
        $builder = $this->where('pk_indikator_id', $pkIndikatorId);
        if ($opdId === null) {
            $builder->where('opd_id IS NULL', null, false);
        } else {
            $builder->where('opd_id', $opdId);
        }
        return $builder->first();
    }

    /* ---------- LIST: PK BUPATI (pk.jenis='bupati') — admin_kab ---------- */
    public function getTargetListByPkBupati(?string $tahun = null): array
    {
        $b = $this->db->table('pk_indikator pi')
            ->select("
                pi.id              AS pk_indikator_id,
                pi.indikator       AS indikator_sasaran,
                pi.target          AS indikator_target,
                s.satuan           AS satuan,

                pk.id              AS pk_id,
                pk.tahun           AS indikator_tahun,
                pk.opd_id          AS opd_id,

                ps.id              AS pk_sasaran_id,
                ps.sasaran         AS sasaran_renstra,

                tr.id              AS target_id,
                tr.rencana_aksi,
                tr.capaian,
                tr.target_triwulan_1,
                tr.target_triwulan_2,
                tr.target_triwulan_3,
                tr.target_triwulan_4,
                tr.penanggung_jawab
            ")
            ->join('pk_sasaran ps', 'ps.id = pi.pk_sasaran_id', 'left')
            ->join('pk', 'pk.id = ps.pk_id', 'left')
            ->join('satuan s', 's.id = pi.id_satuan', 'left')
            ->join('target_rencana tr', 'tr.pk_indikator_id = pi.id', 'left')
            ->where('pk.jenis', 'bupati');

        if (!empty($tahun)) {
            $b->where('pk.tahun', $tahun);
        }

        return $b->orderBy('ps.id', 'ASC')
            ->orderBy('pi.id', 'ASC')
            ->get()
            ->getResultArray();
    }

    /* ------ LIST: PK OPD / ESELON II-III-IV (jpt|administrator|pengawas) ------ */
    /**
     * Daftar indikator PK milik OPD untuk diturunkan jadi Rencana Aksi.
     * Mencakup Eselon II (jpt), III (administrator), IV (pengawas).
     *
     * @param string|null $tahun     Filter tahun PK (null = semua).
     * @param int|null    $opdId     Scope per OPD (null = semua OPD, untuk admin_kab).
     * @param string|null $eselon    Filter satu eselon: 'jpt'|'administrator'|'pengawas' (null = semua).
     * @param int|null    $pejabatId Filter pejabat pelaksana (pk.pihak_2) (null = semua).
     */
    public function getTargetListByPkOpd(
        ?string $tahun = null,
        ?int $opdId = null,
        ?string $eselon = null,
        ?int $pejabatId = null
    ): array {
        // Renaksi OPD di-scope per OPD (tr.opd_id = pk.opd_id)
        $trJoin = 'tr.pk_indikator_id = pi.id';
        if (!empty($opdId)) {
            $trJoin .= ' AND tr.opd_id = ' . (int) $opdId;
        }

        $b = $this->db->table('pk_indikator pi')
            ->select("
                pi.id              AS pk_indikator_id,
                pi.indikator       AS indikator_sasaran,
                pi.target          AS indikator_target,
                s.satuan           AS satuan,

                pk.id              AS pk_id,
                pk.tahun           AS indikator_tahun,
                pk.opd_id          AS opd_id,
                pk.jenis           AS pk_jenis,
                o.nama_opd         AS nama_opd,

                pj.id              AS pejabat_id,
                pj.nama_pegawai    AS pejabat_nama,
                jb.nama_jabatan    AS pejabat_jabatan,
                jb.eselon          AS pejabat_eselon,

                ps.id              AS pk_sasaran_id,
                ps.sasaran         AS sasaran_renstra,

                tr.id              AS target_id,
                tr.rencana_aksi,
                tr.capaian,
                tr.target_triwulan_1,
                tr.target_triwulan_2,
                tr.target_triwulan_3,
                tr.target_triwulan_4,
                tr.penanggung_jawab
            ")
            ->join('pk_sasaran ps', 'ps.id = pi.pk_sasaran_id', 'left')
            ->join('pk', 'pk.id = ps.pk_id', 'left')
            ->join('opd o', 'o.id = pk.opd_id', 'left')
            ->join('pegawai pj', 'pj.id = pk.pihak_2', 'left')
            ->join('jabatan jb', 'jb.id = pj.jabatan_id', 'left')
            ->join('satuan s', 's.id = pi.id_satuan', 'left')
            ->join('target_rencana tr', $trJoin, 'left');

        if (!empty($eselon) && in_array($eselon, ['jpt', 'camat', 'administrator', 'pengawas'], true)) {
            $b->where('pk.jenis', $eselon);
        } else {
            $b->whereIn('pk.jenis', ['jpt', 'camat', 'administrator', 'pengawas']);
        }
        if (!empty($tahun)) {
            $b->where('pk.tahun', $tahun);
        }
        if (!empty($opdId)) {
            $b->where('pk.opd_id', (int) $opdId);
        }
        if (!empty($pejabatId)) {
            $b->where('pk.pihak_2', (int) $pejabatId);
        }
        $b->where("(COALESCE(LOWER(jb.nama_jabatan), '') NOT LIKE '%bupati%' AND COALESCE(LOWER(pj.nama_pegawai), '') NOT LIKE '%bupati%')", null, false);

        return $b->orderBy('pk.opd_id', 'ASC')
            ->orderBy("FIELD(pk.jenis,'jpt','camat','administrator','pengawas')", '', false)
            ->orderBy('ps.id', 'ASC')
            ->orderBy('pi.id', 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * Tahun PK Bupati (untuk dropdown mode bupati).
     * $jenis: 'bupati'
     */
    public function getAvailableYearsPk(string $jenis): array
    {
        return $this->db->table('pk')
            ->select('tahun')
            ->where('jenis', $jenis)
            ->distinct()
            ->orderBy('tahun', 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * Tahun PK level OPD (Eselon II/III/IV) untuk dropdown, opsional di-scope per OPD.
     */
    public function getAvailableYearsPkOpd(?int $opdId = null): array
    {
        $b = $this->db->table('pk')
            ->select('tahun')
            ->whereIn('jenis', ['jpt', 'camat', 'administrator', 'pengawas'])
            ->distinct()
            ->orderBy('tahun', 'ASC');
        if (!empty($opdId)) {
            $b->where('opd_id', (int) $opdId);
        }
        return $b->get()->getResultArray();
    }
}
