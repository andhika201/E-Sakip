<?php

namespace App\Models;

use CodeIgniter\Model;

class RktModel extends Model
{
    // ------------------------------------------------------------------
    // META
    // ------------------------------------------------------------------
    protected $table = 'rkt';
    protected $primaryKey = 'id';
    protected $allowedFields = ['opd_id', 'tahun', 'indikator_id', 'program_id', 'status'];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    // ------------------------------------------------------------------
    //  HELPERS
    // ------------------------------------------------------------------

    /** Ambil daftar tahun yang ada di tabel rkt (DESC) */
    public function getAvailableYears(): array
    {
        $rows = $this->db->table('rkt')
            ->distinct()
            ->select('tahun')
            ->orderBy('tahun', 'DESC')
            ->get()
            ->getResultArray();

        // kembalikan sebagai array string tahun
        return array_map(static fn($r) => (string) $r['tahun'], $rows);
    }

    /** Hapus 1 RKT dan mapping-nya (kegiatan & subkegiatan) */
    public function deleteRktCascade(int $rktId): bool
    {
        $db = $this->db;
        $db->transStart();

        // ambil id rkt_kegiatan
        $kegs = $db->table('rkt_kegiatan')->select('id')->where('rkt_id', $rktId)->get()->getResultArray();
        $kegIds = array_column($kegs, 'id');

        if (!empty($kegIds)) {
            $db->table('rkt_subkegiatan')->whereIn('rkt_kegiatan_id', $kegIds)->delete();
            $db->table('rkt_kegiatan')->whereIn('id', $kegIds)->delete();
        }

        $db->table('rkt')->where('id', $rktId)->delete();

        $db->transComplete();
        return $db->transStatus();
    }

    // ------------------------------------------------------------------
    //  CREATE
    // ------------------------------------------------------------------

    /**
     * Simpan RKT baru (header rkt + mapping rkt_kegiatan + rkt_subkegiatan).
     *
     * Struktur $payload:
     * [
     *   'opd_id'=>, 'tahun'=>, 'indikator_id'=>, 'status'=>'draft|selesai',
     *   'program' => [
     *     [
     *       'program_id'=> 1,
     *       'kegiatan'  => [
     *         [
     *           'kegiatan_id'=> 10,
     *           'subkegiatan'=> [
     *              ['sub_kegiatan_id'=>100], ...
     *           ]
     *         ], ...
     *       ]
     *     ], ...
     *   ]
     * ]
     */
    public function saveRkt(array $payload): bool
    {
        $db = $this->db;
        $db->transStart();

        $tblRkt = $db->table('rkt');
        $tblRktKeg = $db->table('rkt_kegiatan');
        $tblRktSub = $db->table('rkt_subkegiatan');

        $programs = $payload['program'] ?? [];
        if (!is_array($programs)) {
            $programs = [];
        }

        foreach ($programs as $prog) {
            if (empty($prog['program_id'])) {
                continue;
            }

            // header RKT
            $tblRkt->insert([
                'opd_id' => $payload['opd_id'] ?? null,
                'tahun' => $payload['tahun'] ?? date('Y'),
                'indikator_id' => $payload['indikator_id'] ?? null,
                'program_id' => $prog['program_id'],
                'status' => $payload['status'] ?? 'draft',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            $rktId = (int) $db->insertID();

            // mapping kegiatan
            $kegiatanList = $prog['kegiatan'] ?? [];
            foreach ($kegiatanList as $keg) {
                if (empty($keg['kegiatan_id'])) {
                    continue;
                }

                $tblRktKeg->insert([
                    'rkt_id' => $rktId,
                    'kegiatan_id' => $keg['kegiatan_id'],
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
                $rktKegId = (int) $db->insertID();

                // mapping subkegiatan
                $subs = $keg['subkegiatan'] ?? [];
                foreach ($subs as $sub) {
                    if (empty($sub['sub_kegiatan_id'])) {
                        continue;
                    }
                    $tblRktSub->insert([
                        'rkt_kegiatan_id' => $rktKegId,
                        'sub_kegiatan_id' => $sub['sub_kegiatan_id'],
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
                }
            }
        }

        $db->transComplete();
        return $db->transStatus();
    }

    // ------------------------------------------------------------------
    //  UPDATE
    // ------------------------------------------------------------------

    /**
     * Update RKT per indikator & tahun.
     *
     * Menerima daftar id yang dihapus:
     *  - deleted_program_ids[]
     *  - deleted_kegiatan_ids[]
     *  - deleted_subkegiatan_ids[]
     *
     * Payload program sama seperti saveRkt tetapi setiap item boleh memiliki
     * 'id' (rkt.id / rkt_kegiatan.id / rkt_subkegiatan.id).
     */
    public function updateRkt(array $payload): bool
    {
        $db = $this->db;
        $db->transStart();

        $tblRkt = $db->table('rkt');
        $tblRktKeg = $db->table('rkt_kegiatan');
        $tblRktSub = $db->table('rkt_subkegiatan');

        // ----------------- DELETE SUB -----------------
        $delSubs = array_filter(array_map('intval', (array) ($payload['deleted_subkegiatan_ids'] ?? [])));
        if (!empty($delSubs)) {
            $tblRktSub->whereIn('id', $delSubs)->delete();
        }

        // ----------------- DELETE KEGIATAN (+ SUB) -----------------
        $delKegs = array_filter(array_map('intval', (array) ($payload['deleted_kegiatan_ids'] ?? [])));
        if (!empty($delKegs)) {
            $tblRktSub->whereIn('rkt_kegiatan_id', $delKegs)->delete();
            $tblRktKeg->whereIn('id', $delKegs)->delete();
        }

        // ----------------- DELETE PROGRAM (+ KEG + SUB) -------------
        $delRkts = array_filter(array_map('intval', (array) ($payload['deleted_program_ids'] ?? [])));
        if (!empty($delRkts)) {
            $kRows = $db->table('rkt_kegiatan')
                ->select('id')
                ->whereIn('rkt_id', $delRkts)
                ->get()
                ->getResultArray();

            $kIds = array_column($kRows, 'id');

            if (!empty($kIds)) {
                $tblRktSub->whereIn('rkt_kegiatan_id', $kIds)->delete();
                $tblRktKeg->whereIn('id', $kIds)->delete();
            }

            $tblRkt->whereIn('id', $delRkts)->delete();
        }

        // ----------------- UPSERT PROGRAM / KEGIATAN / SUB -----------
        $opdId = $payload['opd_id'] ?? session()->get('opd_id');
        $tahun = $payload['tahun'] ?? date('Y');
        $indikatorId = $payload['indikator_id'] ?? null;

        foreach ((array) ($payload['program'] ?? []) as $p) {
            $rktId = !empty($p['id']) ? (int) $p['id'] : null;

            // upsert RKT (header)
            if ($rktId) {
                $exist = $tblRkt->select('id')->where('id', $rktId)->get()->getRowArray();
                if ($exist) {
                    $tblRkt->where('id', $rktId)->update([
                        'program_id' => $p['program_id'] ?? null,
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
                } else {
                    $tblRkt->insert([
                        'opd_id' => $opdId,
                        'tahun' => $tahun,
                        'indikator_id' => $indikatorId,
                        'program_id' => $p['program_id'] ?? null,
                        'status' => $payload['status'] ?? 'draft',
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
                    $rktId = (int) $db->insertID();
                }
            } else {
                $tblRkt->insert([
                    'opd_id' => $opdId,
                    'tahun' => $tahun,
                    'indikator_id' => $indikatorId,
                    'program_id' => $p['program_id'] ?? null,
                    'status' => $payload['status'] ?? 'draft',
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
                $rktId = (int) $db->insertID();
            }

            // kegiatan
            foreach ((array) ($p['kegiatan'] ?? []) as $k) {
                $rktKegId = !empty($k['id']) ? (int) $k['id'] : null;

                if ($rktKegId) {
                    $tblRktKeg->where('id', $rktKegId)->update([
                        'rkt_id' => $rktId,
                        'kegiatan_id' => $k['kegiatan_id'] ?? null,
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
                } else {
                    $tblRktKeg->insert([
                        'rkt_id' => $rktId,
                        'kegiatan_id' => $k['kegiatan_id'] ?? null,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
                    $rktKegId = (int) $db->insertID();
                }

                // subkegiatan
                foreach ((array) ($k['subkegiatan'] ?? []) as $s) {
                    $rktSubId = !empty($s['id']) ? (int) $s['id'] : null;

                    if ($rktSubId) {
                        $tblRktSub->where('id', $rktSubId)->update([
                            'rkt_kegiatan_id' => $rktKegId,
                            'sub_kegiatan_id' => $s['sub_kegiatan_id'] ?? null,
                            'updated_at' => date('Y-m-d H:i:s'),
                        ]);
                    } else {
                        $tblRktSub->insert([
                            'rkt_kegiatan_id' => $rktKegId,
                            'sub_kegiatan_id' => $s['sub_kegiatan_id'] ?? null,
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s'),
                        ]);
                    }
                }
            }
        }

        $db->transComplete();
        return $db->transStatus();
    }

    // ------------------------------------------------------------------
    //  READ (NESTED) – UNTUK OPD
    // ------------------------------------------------------------------

    /** Ambil RKT lengkap untuk satu OPD (nested program → kegiatan → subkegiatan) */
    public function getRktByOpd(int $opdId): array
    {
        $db = $this->db;

        $rows = $db->table('rkt r')
            ->select("
                r.*,
                s.sasaran,
                i.indikator_sasaran,
                i.satuan,
                p.program_kegiatan AS program_nama,
                p.anggaran        AS program_anggaran
            ")
            ->join('renstra_indikator_sasaran i', 'i.id = r.indikator_id', 'left')
            ->join('renstra_sasaran s', 's.id = i.renstra_sasaran_id', 'left')
            ->join('program_pk p', 'p.id = r.program_id', 'left')
            ->where('r.opd_id', $opdId)
            ->orderBy('r.id', 'ASC')
            ->get()
            ->getResultArray();

        foreach ($rows as &$rkt) {
            $kegs = $db->table('rkt_kegiatan rk')
                ->select("
                    rk.*,
                    k.kegiatan AS nama_kegiatan,
                    k.anggaran AS kegiatan_anggaran
                ")
                ->join('kegiatan_pk k', 'k.id = rk.kegiatan_id', 'left')
                ->where('rk.rkt_id', $rkt['id'])
                ->orderBy('rk.id', 'ASC')
                ->get()
                ->getResultArray();

            foreach ($kegs as &$k) {
                $subs = $db->table('rkt_subkegiatan rs')
                    ->select("
                        rs.*,
                        sk.sub_kegiatan AS nama_subkegiatan,
                        sk.anggaran    AS target_anggaran
                    ")
                    ->join('sub_kegiatan_pk sk', 'sk.id = rs.sub_kegiatan_id', 'left')
                    ->where('rs.rkt_kegiatan_id', $k['id'])
                    ->orderBy('rs.id', 'ASC')
                    ->get()
                    ->getResultArray();

                $k['subkegiatan'] = $subs;
            }

            $rkt['kegiatan'] = $kegs;
        }

        return $rows;
    }

    /**
     * Ambil indikator OPD + RKT tahun tertentu (nested).
     * Return: array indikator, masing2 berisi 'rkts' (program → kegiatan → subkegiatan).
     */
    public function getIndicatorsWithRkt(int $opdId, $tahun, string $status = 'all'): array
    {
        $db = $this->db;

        // master indikator + target tahun (correlated subquery)
        $indicators = $db->query("
            SELECT
                i.*,
                s.sasaran,
                s.opd_id,
                (
                  SELECT t2.target
                  FROM renstra_target t2
                  WHERE t2.renstra_indikator_id = i.id
                    AND t2.tahun = ?
                  LIMIT 1
                ) AS target
            FROM renstra_indikator_sasaran i
            JOIN renstra_sasaran s ON s.id = i.renstra_sasaran_id
            WHERE s.opd_id = ?
            ORDER BY s.id ASC, i.id ASC
        ", [$tahun, $opdId])->getResultArray();

        foreach ($indicators as &$ind) {
            $q = $db->table('rkt r')
                ->select("
                    r.*,
                    p.program_kegiatan AS program_nama,
                    p.anggaran        AS program_anggaran
                ")
                ->join('program_pk p', 'p.id = r.program_id', 'left')
                ->where('r.opd_id', $opdId)
                ->where('r.tahun', $tahun)
                ->where('r.indikator_id', $ind['id'])
                ->orderBy('r.id', 'ASC');

            if ($status !== 'all') {
                $q->where('r.status', $status);
            }

            $rkts = $q->get()->getResultArray();

            foreach ($rkts as &$rkt) {
                $kegs = $db->table('rkt_kegiatan rk')
                    ->select("
                        rk.*,
                        k.kegiatan AS nama_kegiatan,
                        k.anggaran AS kegiatan_anggaran
                    ")
                    ->join('kegiatan_pk k', 'k.id = rk.kegiatan_id', 'left')
                    ->where('rk.rkt_id', $rkt['id'])
                    ->orderBy('rk.id', 'ASC')
                    ->get()
                    ->getResultArray();

                foreach ($kegs as &$k) {
                    $subs = $db->table('rkt_subkegiatan rs')
                        ->select("
                            rs.*,
                            sk.sub_kegiatan AS nama_subkegiatan,
                            sk.anggaran    AS target_anggaran
                        ")
                        ->join('sub_kegiatan_pk sk', 'sk.id = rs.sub_kegiatan_id', 'left')
                        ->where('rs.rkt_kegiatan_id', $k['id'])
                        ->orderBy('rs.id', 'ASC')
                        ->get()
                        ->getResultArray();

                    $k['subkegiatan'] = $subs;
                }

                $rkt['kegiatan'] = $kegs;
            }

            $ind['rkts'] = $rkts;
        }

        return $indicators;
    }

    /** Ambil hanya untuk 1 indikator (filter OPD & tahun) – nested. */
    public function getRktbyIndicator(int $opdId, $tahun, int $indicatorId, string $status = 'all'): array
    {
        $db = $this->db;

        $indicators = $db->query("
            SELECT
                i.*,
                s.sasaran,
                s.opd_id,
                (
                  SELECT t2.target
                  FROM renstra_target t2
                  WHERE t2.renstra_indikator_id = i.id
                    AND t2.tahun = ?
                  LIMIT 1
                ) AS target
            FROM renstra_indikator_sasaran i
            JOIN renstra_sasaran s ON s.id = i.renstra_sasaran_id
            WHERE s.opd_id = ? AND i.id = ?
            ORDER BY s.id ASC, i.id ASC
        ", [$tahun, $opdId, $indicatorId])->getResultArray();

        foreach ($indicators as &$ind) {
            $q = $db->table('rkt r')
                ->select("
                    r.*,
                    p.program_kegiatan AS program_nama,
                    p.anggaran        AS program_anggaran
                ")
                ->join('program_pk p', 'p.id = r.program_id', 'left')
                ->where('r.opd_id', $opdId)
                ->where('r.tahun', $tahun)
                ->where('r.indikator_id', $ind['id'])
                ->orderBy('r.id', 'ASC');

            if ($status !== 'all') {
                $q->where('r.status', $status);
            }

            $rkts = $q->get()->getResultArray();

            foreach ($rkts as &$rkt) {
                $kegs = $db->table('rkt_kegiatan rk')
                    ->select("
                        rk.*,
                        k.kegiatan AS nama_kegiatan,
                        k.anggaran AS kegiatan_anggaran
                    ")
                    ->join('kegiatan_pk k', 'k.id = rk.kegiatan_id', 'left')
                    ->where('rk.rkt_id', $rkt['id'])
                    ->orderBy('rk.id', 'ASC')
                    ->get()
                    ->getResultArray();

                foreach ($kegs as &$k) {
                    $subs = $db->table('rkt_subkegiatan rs')
                        ->select("
                            rs.*,
                            sk.sub_kegiatan AS nama_subkegiatan,
                            sk.anggaran    AS target_anggaran
                        ")
                        ->join('sub_kegiatan_pk sk', 'sk.id = rs.sub_kegiatan_id', 'left')
                        ->where('rs.rkt_kegiatan_id', $k['id'])
                        ->orderBy('rs.id', 'ASC')
                        ->get()
                        ->getResultArray();

                    $k['subkegiatan'] = $subs;
                }

                $rkt['kegiatan'] = $kegs;
            }

            $ind['rkts'] = $rkts;
        }

        return $indicators;
    }
    // ------------------------------------------------------------------
//  READ (FLAT) – UNTUK RKPD (RINGKAS)
// ------------------------------------------------------------------

    /**
     * Ambil data flat untuk laporan RKPD.
     *
     * @param mixed  $opdId  'all' atau id OPD
     * @param mixed  $tahun  'all' atau tahun tertentu
     * @param string $status 'all' | 'draft' | 'selesai'
     */
    public function getIndicatorsForRkpd($opdId, $tahun, string $status = 'all'): array
    {
        $b = $this->db->table('rkt r')
            ->select("
            r.id              AS rkt_id,
            r.opd_id,
            r.indikator_id,
            r.tahun,
            r.status,

            s.id              AS sasaran_id,
            s.sasaran,
            i.indikator_sasaran,
            i.satuan,

            o.nama_opd,

            (
                SELECT t2.target
                FROM renstra_target t2
                WHERE t2.renstra_indikator_id = i.id
                  AND t2.tahun = r.tahun
                LIMIT 1
            ) AS target_renstra,

            p.program_kegiatan      AS program_kegiatan,
            k.kegiatan              AS nama_kegiatan,
            sk.sub_kegiatan         AS nama_subkegiatan,
            sk.anggaran             AS target_anggaran
        ")
            ->join('renstra_indikator_sasaran i', 'i.id = r.indikator_id', 'left')
            ->join('renstra_sasaran s', 's.id = i.renstra_sasaran_id', 'left')
            ->join('opd o', 'o.id = s.opd_id', 'left')
            ->join('program_pk p', 'p.id = r.program_id', 'left')
            ->join('rkt_kegiatan rk', 'rk.rkt_id = r.id', 'left')
            ->join('kegiatan_pk k', 'k.id = rk.kegiatan_id', 'left')
            ->join('rkt_subkegiatan rs', 'rs.rkt_kegiatan_id = rk.id', 'left')
            ->join('sub_kegiatan_pk sk', 'sk.id = rs.sub_kegiatan_id', 'left');

        // Filter OPD
        if ($opdId !== 'all') {
            $b->where('r.opd_id', (int) $opdId);
        }

        // Filter tahun
        if ($tahun !== 'all') {
            $b->where('r.tahun', $tahun);
        }

        // Filter status
        if ($status !== 'all') {
            $b->where('r.status', $status);
        }

        return $b
            ->orderBy('o.nama_opd', 'ASC')
            ->orderBy('s.id', 'ASC')
            ->orderBy('i.id', 'ASC')
            ->orderBy('p.id', 'ASC')
            ->orderBy('k.id', 'ASC')
            ->orderBy('sk.id', 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * Wrapper sederhana: ambil data RKPD semua OPD (ringkas).
     * Sama saja dengan getIndicatorsForRkpd('all', $tahun, $status)
     */
    public function getIndicatorsForRkpdAll($tahun, string $status = 'all'): array
    {
        return $this->getIndicatorsForRkpd('all', $tahun, $status);
    }

}
