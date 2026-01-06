<?php

namespace App\Models;

use CodeIgniter\Model;

class LakipModel extends Model
{
    protected $table = 'lakip';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useAutoIncrement = true;

    protected $allowedFields = [
        'renstra_target_id',
        'rpjmd_target_id',
        'target_lalu',
        'capaian_lalu',
        'capaian_tahun_ini',
        'status',
        'created_at',
        'updated_at',
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    /* =========================================================
     * YEARS (dropdown)
     * =======================================================*/
    public function getAvailableYears(): array
    {
        $sql = "
            SELECT tahun FROM renstra_target
            UNION
            SELECT tahun FROM rpjmd_target
            ORDER BY tahun ASC
        ";
        $rows = $this->db->query($sql)->getResultArray();
        return array_map(static fn($r) => (string) $r['tahun'], $rows);
    }

    /* =========================================================
     * INDEX DATA (TARGET LIST)
     * - dipakai index admin_kab mode opd (renstra) dan kabupaten (rpjmd)
     * =======================================================*/

    /**
     * List target RENSTRA per tahun (bisa semua OPD atau 1 OPD).
     * Return flat rows -> mudah dibuat rowspan di view.
     */
    public function getIndexRenstraTargets(string $tahun, ?int $opdId = null): array
    {
        $b = $this->db->table('renstra_target rt')
            ->select("
                rt.id                    AS target_id,
                rt.tahun                 AS tahun,
                rt.target                AS target_tahun_ini,

                ris.id                   AS indikator_id,
                ris.indikator_sasaran    AS indikator_sasaran,
                ris.satuan               AS satuan,
                ris.jenis_indikator      AS jenis_indikator,

                rs.id                    AS sasaran_id,
                rs.sasaran               AS sasaran,

                o.id                     AS opd_id,
                o.nama_opd               AS nama_opd
            ")
            ->join('renstra_indikator_sasaran ris', 'ris.id = rt.renstra_indikator_id', 'left')
            ->join('renstra_sasaran rs', 'rs.id = ris.renstra_sasaran_id', 'left')
            ->join('opd o', 'o.id = rs.opd_id', 'left')
            ->where('rt.tahun', $tahun);

        if (!empty($opdId)) {
            $b->where('rs.opd_id', $opdId);
        }

        return $b->orderBy('o.nama_opd', 'ASC')
            ->orderBy('rs.id', 'ASC')
            ->orderBy('ris.id', 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * List target RPJMD per tahun.
     */
    public function getIndexRpjmdTargets(string $tahun): array
    {
        $b = $this->db->table('rpjmd_target rpj')
            ->select("
                rpj.id                   AS target_id,
                rpj.tahun                AS tahun,
                rpj.target_tahunan       AS target_tahun_ini,

                ris.id                   AS indikator_id,
                ris.indikator_sasaran    AS indikator_sasaran,
                ris.satuan               AS satuan,
                ris.jenis_indikator      AS jenis_indikator,

                rs.id                    AS sasaran_id,
                rs.sasaran_rpjmd         AS sasaran
            ")
            ->join('rpjmd_indikator_sasaran ris', 'ris.id = rpj.indikator_sasaran_id', 'left')
            ->join('rpjmd_sasaran rs', 'rs.id = ris.sasaran_id', 'left')
            ->where('rpj.tahun', $tahun);

        return $b->orderBy('rs.id', 'ASC')
            ->orderBy('ris.id', 'ASC')
            ->get()
            ->getResultArray();
    }

    /* =========================================================
     * LAKIP MAP BY TARGET_ID
     * =======================================================*/

    /**
     * Ambil LAKIP map untuk RENSTRA (key = renstra_target_id)
     * Filter status optional, opd optional (via join renstra_sasaran).
     */
    public function getLakipMapRenstra(string $tahun, ?string $status = null, ?int $opdId = null): array
    {
        $b = $this->db->table('lakip l')
            ->select("
            l.*,
            rt.tahun AS tahun_target,
            rt.target AS target_tahun_ini,
            ris.id AS indikator_id,
            rs.id AS sasaran_id
            ")
            ->join('renstra_target rt', 'rt.id = l.renstra_target_id', 'left')
            ->join('renstra_indikator_sasaran ris', 'ris.id = rt.renstra_indikator_id', 'left')
            ->join('renstra_sasaran rs', 'rs.id = ris.renstra_sasaran_id', 'left')
            ->where('rt.tahun', $tahun);


        if (!empty($status)) {
            $b->where('l.status', $status);
        }
        if (!empty($opdId)) {
            $b->where('rs.opd_id', $opdId);
        }

        $rows = $b->get()->getResultArray();

        $map = [];
        foreach ($rows as $r) {
            if (!empty($r['renstra_target_id'])) {
                $map[(int) $r['renstra_target_id']] = $r;
            }
        }
        return $map;
    }

    /**
     * Ambil LAKIP map untuk RPJMD (key = rpjmd_target_id)
     */
    public function getLakipMapRpjmd(string $tahun, ?string $status = null): array
    {
        $b = $this->db->table('lakip l')
            ->select("
                l.*,
                rpj.tahun AS tahun_target,
                rpj.target_tahunan AS target_tahun_ini,
                ris.id AS indikator_id,
                rs.id AS sasaran_id
            ")
            ->join('rpjmd_target rpj', 'rpj.id = l.rpjmd_target_id', 'left')
            ->join('rpjmd_indikator_sasaran ris', 'ris.id = rpj.indikator_sasaran_id', 'left')
            ->join('rpjmd_sasaran rs', 'rs.id = ris.sasaran_id', 'left')
            ->where('rpj.tahun', $tahun);


        if (!empty($status)) {
            $b->where('l.status', $status);
        }

        $rows = $b->get()->getResultArray();

        $map = [];
        foreach ($rows as $r) {
            if (!empty($r['rpjmd_target_id'])) {
                $map[(int) $r['rpjmd_target_id']] = $r;
            }
        }
        return $map;
    }

    /**
     * Wrapper biar controller/view gampang:
     * return ['rows'=>..., 'lakipMap'=>...]
     */
    public function getLakipByMode(string $mode, string $tahun, ?string $status = null, ?int $opdId = null): array
    {
        if ($mode === 'opd') {
            return [
                'rows' => $this->getIndexRenstraTargets($tahun, $opdId),
                'lakipMap' => $this->getLakipMapRenstra($tahun, $status, $opdId),
            ];
        }

        return [
            'rows' => $this->getIndexRpjmdTargets($tahun),
            'lakipMap' => $this->getLakipMapRpjmd($tahun, $status),
        ];
    }

    /* =========================================================
     * TARGET + INDICATOR DETAIL (FOR ADD/EDIT)
     * =======================================================*/

    public function getRenstraTargetDetailByIndikatorAndYear(int $indikatorId, string $tahun): ?array
    {
        return $this->db->table('renstra_target rt')
            ->select("
                rt.*,
                ris.indikator_sasaran,
                ris.satuan,
                ris.jenis_indikator,
                rs.sasaran,
                o.nama_opd
            ")
            ->join('renstra_indikator_sasaran ris', 'ris.id = rt.renstra_indikator_id', 'left')
            ->join('renstra_sasaran rs', 'rs.id = ris.renstra_sasaran_id', 'left')
            ->join('opd o', 'o.id = rs.opd_id', 'left')
            ->where('rt.renstra_indikator_id', $indikatorId)
            ->where('rt.tahun', $tahun)
            ->get()
            ->getRowArray();
    }

    public function getRpjmdTargetDetailByIndikatorAndYear(int $indikatorId, string $tahun): ?array
    {
        return $this->db->table('rpjmd_target rpj')
            ->select("
                rpj.*,
                ris.indikator_sasaran,
                ris.satuan,
                ris.jenis_indikator,
                rs.sasaran_rpjmd AS sasaran
            ")
            ->join('rpjmd_indikator_sasaran ris', 'ris.id = rpj.indikator_sasaran_id', 'left')
            ->join('rpjmd_sasaran rs', 'rs.id = ris.sasaran_id', 'left')
            ->where('rpj.indikator_sasaran_id', $indikatorId)
            ->where('rpj.tahun', $tahun)
            ->get()
            ->getRowArray();
    }

    /* =========================================================
     * KOMPATIBILITAS controller lama
     * =======================================================*/

    /**
     * LIST LAKIP RENSTRA (untuk role admin_opd / mode opd)
     * controller lama: getRenstra($opdId, $status, $tahun)
     */
    public function getRenstra(int $opdId, ?string $status = null, ?string $tahun = null): array
    {
        $tahun = $tahun ?: date('Y');

        $b = $this->db->table('lakip l')
            ->select("
                l.*,

                rt.id      AS renstra_target_id,
                rt.tahun   AS indikator_tahun,
                rt.target  AS target_tahun_ini,

                ris.id     AS renstra_indikator_id,
                ris.indikator_sasaran,
                ris.satuan,
                ris.jenis_indikator,

                rs.id      AS renstra_sasaran_id,
                rs.sasaran AS sasaran,
                rs.opd_id  AS opd_id
            ")
            ->join('renstra_target rt', 'rt.id = l.renstra_target_id', 'left')
            ->join('renstra_indikator_sasaran ris', 'ris.id = rt.renstra_indikator_id', 'left')
            ->join('renstra_sasaran rs', 'rs.id = ris.renstra_sasaran_id', 'left')
            ->where('rs.opd_id', $opdId)
            ->where('rt.tahun', $tahun);

        if (!empty($status)) {
            $b->where('l.status', $status);
        }

        return $b->orderBy('rs.id', 'ASC')
            ->orderBy('ris.id', 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * LIST LAKIP RPJMD (untuk mode kabupaten)
     * controller lama: getRPJMD($status, $tahun)
     */
    public function getRPJMD(?string $status = null, ?string $tahun = null): array
    {
        $tahun = $tahun ?: date('Y');

        $b = $this->db->table('lakip l')
            ->select("
                l.*,

                rpj.id             AS rpjmd_target_id,
                rpj.tahun          AS indikator_tahun,
                rpj.target_tahunan AS target_tahun_ini,

                ris.id             AS rpjmd_indikator_id,
                ris.indikator_sasaran,
                ris.satuan,
                ris.jenis_indikator,

                rs.id              AS rpjmd_sasaran_id,
                rs.sasaran_rpjmd   AS sasaran
            ")
            ->join('rpjmd_target rpj', 'rpj.id = l.rpjmd_target_id', 'left')
            ->join('rpjmd_indikator_sasaran ris', 'ris.id = rpj.indikator_sasaran_id', 'left')
            ->join('rpjmd_sasaran rs', 'rs.id = ris.sasaran_id', 'left')
            ->where('rpj.tahun', $tahun);

        if (!empty($status)) {
            $b->where('l.status', $status);
        }

        return $b->orderBy('rs.id', 'ASC')
            ->orderBy('ris.id', 'ASC')
            ->get()
            ->getResultArray();
    }
    /* =========================================================
     * HELPER: GROUP ROWS FOR VIEW (ROWSPAN)
     * =======================================================*/
    public function groupIndexRowsBySasaran(array $rows, string $mode = 'opd'): array
    {
        $grouped = [];

        foreach ($rows as $r) {
            $sasaranId = (int) ($r['sasaran_id'] ?? 0);
            $sasaranText = $r['sasaran'] ?? '';

            if (!isset($grouped[$sasaranId])) {
                $grouped[$sasaranId] = [
                    // samakan key dengan view kamu
                    'sasaran' => $sasaranText,
                    'indikator_sasaran' => [],
                ];
            }

            $grouped[$sasaranId]['indikator_sasaran'][] = $r;
        }

        return array_values($grouped);
    }


    /**
     * DETAIL LAKIP untuk 1 indikator (dipakai form edit controller lama)
     */
    public function getLakipDetail(int $indikatorId, string $role, ?string $tahun = null): ?array
    {
        $tahun = $tahun ?: date('Y');

        if ($role === 'admin_kab') {
            $target = $this->db->table('rpjmd_target')
                ->select('id')
                ->where('indikator_sasaran_id', $indikatorId)
                ->where('tahun', $tahun)
                ->get()
                ->getRowArray();

            if (!$target)
                return null;

            return $this->where('rpjmd_target_id', (int) $target['id'])->first();
        }

        $target = $this->db->table('renstra_target')
            ->select('id')
            ->where('renstra_indikator_id', $indikatorId)
            ->where('tahun', $tahun)
            ->get()
            ->getRowArray();

        if (!$target)
            return null;

        return $this->where('renstra_target_id', (int) $target['id'])->first();
    }

    public function getLakipByRenstraTarget(int $renstraTargetId): ?array
    {
        return $this->where('renstra_target_id', $renstraTargetId)->first();
    }

    public function getLakipByRpjmdTarget(int $rpjmdTargetId): ?array
    {
        return $this->where('rpjmd_target_id', $rpjmdTargetId)->first();
    }

    /* =========================================================
     * MUTATIONS
     * =======================================================*/
    public function updateLakip(int $id, array $data): bool
    {
        return (bool) $this->update($id, $data);
    }

    public function deleteLakip(int $id): bool
    {
        return (bool) $this->delete($id);
    }
}
