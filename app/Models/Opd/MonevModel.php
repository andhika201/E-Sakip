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
                s.satuan AS satuan,
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
            ->join('satuan AS s', 's.id = ris.satuan', 'left')
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
                s.satuan AS satuan,
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
            ->join('satuan AS s', 's.id = rpis.satuan', 'left')
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
                s.satuan AS satuan,
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
            ->join('satuan AS s', 's.id = ris.satuan', 'left')
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

    /* =========================================================
     *  MONEV PK BUPATI  (pk.jenis='bupati')
     *  Renaksi: target_rencana.pk_indikator_id; monev.opd_id = NULL
     *  (mengikuti pola mode KAB)
     * =======================================================*/
    public function getIndexDataPkBupati(?string $tahun = null): array
    {
        $b = $this->db->table('target_rencana AS tr')
            ->select('
                tr.id  AS target_id,
                tr.opd_id,
                tr.pk_indikator_id,
                tr.rencana_aksi,
                tr.capaian AS target_capaian,
                tr.target_triwulan_1,
                tr.target_triwulan_2,
                tr.target_triwulan_3,
                tr.target_triwulan_4,
                tr.penanggung_jawab
            ')
            ->select('
                pi.id AS indikator_id,
                pi.indikator AS indikator_sasaran,
                pi.target AS indikator_target,
                s.satuan AS satuan
            ')
            ->select('
                pk.id    AS pk_id,
                pk.tahun AS indikator_tahun
            ')
            ->select('
                ps.id      AS pk_sasaran_id,
                ps.sasaran AS sasaran_renstra
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
            ->join('pk_indikator pi', 'pi.id = tr.pk_indikator_id', 'left')
            ->join('pk_sasaran ps', 'ps.id = pi.pk_sasaran_id', 'left')
            ->join('pk', 'pk.id = ps.pk_id', 'left')
            ->join('satuan s', 's.id = pi.id_satuan', 'left')
            ->join(
                $this->table . ' AS m',
                'm.target_rencana_id = tr.id AND m.opd_id IS NULL',
                'left'
            )
            ->where('tr.pk_indikator_id IS NOT NULL', null, false)
            ->where('pk.jenis', 'bupati');

        if (!empty($tahun)) {
            $b->where('pk.tahun', $tahun);
        }

        return $b->orderBy('ps.id', 'ASC')
            ->orderBy('pi.id', 'ASC')
            ->orderBy('tr.id', 'ASC')
            ->get()
            ->getResultArray();
    }

    /* =========================================================
     *  MONEV PK OPD / KECAMATAN (Eselon II/III/IV)
     *  Renaksi: target_rencana.pk_indikator_id; monev.opd_id = tr.opd_id
     *  (mengikuti pola mode OPD). Bisa difilter per OPD, eselon, & pejabat.
     *
     *  @param string|null $tahun     Filter tahun PK (null = semua).
     *  @param int|null    $opdId     Scope per OPD (null = semua, untuk admin_kab).
     *  @param string|null $eselon    'jpt'|'administrator'|'pengawas' (null = semua).
     *  @param int|null    $pejabatId Filter pejabat pelaksana (pk.pihak_1).
     * =======================================================*/
    public function getIndexDataPkOpd(
        ?string $tahun = null,
        ?int $opdId = null,
        ?string $eselon = null,
        ?int $pejabatId = null
    ): array {
        $b = $this->db->table('target_rencana AS tr')
            ->select('
                tr.id  AS target_id,
                tr.opd_id,
                tr.pk_indikator_id,
                tr.rencana_aksi,
                tr.capaian AS target_capaian,
                tr.target_triwulan_1,
                tr.target_triwulan_2,
                tr.target_triwulan_3,
                tr.target_triwulan_4,
                tr.penanggung_jawab
            ')
            ->select('
                pi.id AS indikator_id,
                pi.indikator AS indikator_sasaran,
                pi.target AS indikator_target,
                s.satuan AS satuan
            ')
            ->select('
                pk.id     AS pk_id,
                pk.tahun  AS indikator_tahun,
                pk.opd_id AS pk_opd_id,
                pk.jenis  AS pk_jenis
            ')
            ->select('
                pj.id           AS pejabat_id,
                pj.nama_pegawai AS pejabat_nama,
                jb.nama_jabatan AS pejabat_jabatan,
                jb.eselon AS pejabat_eselon
            ')
            ->select('
                ps.id      AS pk_sasaran_id,
                ps.sasaran AS sasaran_renstra
            ')
            ->select('o.nama_opd')
            ->select('
                m.id  AS monev_id,
                m.opd_id AS monev_opd_id,
                m.capaian_triwulan_1,
                m.capaian_triwulan_2,
                m.capaian_triwulan_3,
                m.capaian_triwulan_4,
                m.total AS monev_total
            ')
            ->join('pk_indikator pi', 'pi.id = tr.pk_indikator_id', 'left')
            ->join('pk_sasaran ps', 'ps.id = pi.pk_sasaran_id', 'left')
            ->join('pk', 'pk.id = ps.pk_id', 'left')
            ->join('opd o', 'o.id = tr.opd_id', 'left')
            ->join('pegawai pj', 'pj.id = pk.pihak_1', 'left')
            ->join('jabatan jb', 'jb.id = pj.jabatan_id', 'left')
            ->join('satuan s', 's.id = pi.id_satuan', 'left')
            ->join(
                $this->table . ' AS m',
                'm.target_rencana_id = tr.id AND m.opd_id = tr.opd_id',
                'left'
            )
            ->where('tr.pk_indikator_id IS NOT NULL', null, false);

        $jenisScope = match ($eselon) {
            'jpt'           => ['jpt'],
            'administrator',
            'camat'         => ['administrator', 'camat'],
            'pengawas'      => ['pengawas'],
            default         => ['jpt', 'administrator', 'camat', 'pengawas'],
        };
        $b->whereIn('pk.jenis', $jenisScope);
        if (!empty($tahun)) {
            $b->where('pk.tahun', $tahun);
        }
        if (!empty($opdId)) {
            $b->where('tr.opd_id', (int) $opdId);
        }
        if (!empty($pejabatId)) {
            $b->where('pk.pihak_1', (int) $pejabatId);
        }
        $b->where("(COALESCE(LOWER(jb.nama_jabatan), '') NOT LIKE '%bupati%' AND COALESCE(LOWER(pj.nama_pegawai), '') NOT LIKE '%bupati%')", null, false);

        return $b->orderBy('tr.opd_id', 'ASC')
            ->orderBy("FIELD(pk.jenis,'jpt','administrator','camat','pengawas')", '', false)
            ->orderBy('ps.id', 'ASC')
            ->orderBy('pi.id', 'ASC')
            ->orderBy('tr.id', 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * Rollup ringkas realisasi PK (untuk kartu dashboard).
     * $jenis = 'bupati' | 'jpt' | 'camat' | 'administrator' | 'pengawas'.
     * Mengembalikan: indikator (total), renaksi (sudah punya rencana aksi),
     * capaian (renaksi yang sudah ada baris monev).
     */
    public function getPkRealisasiRollup(string $jenis, ?int $opdId = null): array
    {
        // total indikator PK
        $bi = $this->db->table('pk_indikator pi')
            ->join('pk_sasaran ps', 'ps.id = pi.pk_sasaran_id', 'left')
            ->join('pk', 'pk.id = ps.pk_id', 'left')
            ->where('pk.jenis', $jenis);
        if ($opdId) {
            $bi->where('pk.opd_id', (int) $opdId);
        }
        $indikator = (int) $bi->countAllResults();

        // total renaksi PK
        $br = $this->db->table('target_rencana tr')
            ->join('pk_indikator pi', 'pi.id = tr.pk_indikator_id', 'left')
            ->join('pk_sasaran ps', 'ps.id = pi.pk_sasaran_id', 'left')
            ->join('pk', 'pk.id = ps.pk_id', 'left')
            ->where('tr.pk_indikator_id IS NOT NULL', null, false)
            ->where('pk.jenis', $jenis);
        if ($opdId) {
            $br->where('pk.opd_id', (int) $opdId);
        }
        $renaksi = (int) $br->countAllResults();

        // renaksi yang sudah ada capaian (monev)
        $monevJoin = ($jenis === 'bupati')
            ? 'm.target_rencana_id = tr.id AND m.opd_id IS NULL'
            : 'm.target_rencana_id = tr.id AND m.opd_id = tr.opd_id';
        $bc = $this->db->table('target_rencana tr')
            ->join('pk_indikator pi', 'pi.id = tr.pk_indikator_id', 'left')
            ->join('pk_sasaran ps', 'ps.id = pi.pk_sasaran_id', 'left')
            ->join('pk', 'pk.id = ps.pk_id', 'left')
            ->join($this->table . ' m', $monevJoin, 'inner')
            ->where('tr.pk_indikator_id IS NOT NULL', null, false)
            ->where('pk.jenis', $jenis);
        if ($opdId) {
            $bc->where('pk.opd_id', (int) $opdId);
        }
        $capaian = (int) $bc->countAllResults();

        return [
            'indikator' => $indikator,
            'renaksi'   => $renaksi,
            'capaian'   => $capaian,
        ];
    }

    /**
     * Daftar tahun PK Bupati (untuk dropdown mode bupati).
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
     * Daftar tahun PK OPD/Kecamatan, opsional di-scope per OPD.
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
