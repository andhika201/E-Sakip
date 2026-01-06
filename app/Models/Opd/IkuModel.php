<?php

namespace App\Models\Opd;

use CodeIgniter\Model;

class IkuModel extends Model
{
    protected $table = 'iku';
    protected $primaryKey = 'id';
    protected $returnType = 'array';

    protected $allowedFields = [
        'rpjmd_id',
        'renstra_id',
        'definisi',
        'status',
        'created_at',
        'updated_at',
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    /* =========================================================
     * CREATE IKU + PROGRAM PENDUKUNG
     * =======================================================*/
    public function createCompleteIku(array $data)
    {
        $db = \Config\Database::connect();
        $db->transStart();

        try {
            $ikuData = [
                'rpjmd_id' => $data['rpjmd_id'] ?? null,
                'renstra_id' => $data['renstra_id'] ?? null,
                'definisi' => $data['definisi'],
                'status' => $data['status'] ?? 'belum',
            ];

            $db->table('iku')->insert($ikuData);
            $ikuId = $db->insertID();

            if (!empty($data['program_pendukung']) && is_array($data['program_pendukung'])) {
                foreach ($data['program_pendukung'] as $program) {
                    $program = trim($program);
                    if ($program === '') {
                        continue;
                    }

                    $db->table('iku_program_pendukung')->insert([
                        'iku_id' => $ikuId,
                        'program' => $program,
                    ]);
                }
            }

            $db->transComplete();

            if ($db->transStatus() === false) {
                throw new \Exception('Transaksi penyimpanan IKU gagal.');
            }

            return $ikuId;
        } catch (\Throwable $e) {
            $db->transRollback();
            throw $e;
        }
    }

    /* =========================================================
     * LIST IKU UNTUK ADMIN OPD (BERDASARKAN OPD)
     * =======================================================*/
    public function getRenstraWithPrograms($opd_id)
    {
        $ikuList = $this->db->table('iku')
            ->select("
                iku.*,
                renstra_indikator_sasaran.indikator_sasaran AS renstra_indikator,
                renstra_indikator_sasaran.satuan           AS renstra_satuan,
                renstra_sasaran.sasaran                    AS sasaran_renstra
            ", false)
            ->join(
                'renstra_indikator_sasaran',
                'renstra_indikator_sasaran.id = iku.renstra_id',
                'left'
            )
            ->join(
                'renstra_sasaran',
                'renstra_sasaran.id = renstra_indikator_sasaran.renstra_sasaran_id',
                'left'
            )
            ->where('renstra_sasaran.opd_id', $opd_id)
            ->orderBy('iku.id', 'ASC')
            ->get()
            ->getResultArray();

        $programs = $this->db->table('iku_program_pendukung')
            ->select('iku_id, program')
            ->get()
            ->getResultArray();

        $programMap = [];
        foreach ($programs as $p) {
            $programMap[$p['iku_id']][] = $p['program'];
        }

        foreach ($ikuList as &$iku) {
            $iku['program_pendukung'] = $programMap[$iku['id']] ?? [];
        }

        return $ikuList;
    }

    /* =========================================================
     * LIST IKU UNTUK ADMIN KABUPATEN (RPJMD / RENSTRA)
     * =======================================================*/
    public function getRPJMDWithPrograms()
    {
        $ikuList = $this->db->table('iku')
            ->select("
                iku.*,
                COALESCE(rpjmd_indikator_sasaran.indikator_sasaran, renstra_indikator_sasaran.indikator_sasaran) AS indikator_sasaran,
                COALESCE(rpjmd_indikator_sasaran.satuan, renstra_indikator_sasaran.satuan)                       AS satuan
            ", false)
            ->join(
                'rpjmd_indikator_sasaran',
                'rpjmd_indikator_sasaran.id = iku.rpjmd_id',
                'left'
            )
            ->join(
                'renstra_indikator_sasaran',
                'renstra_indikator_sasaran.id = iku.renstra_id',
                'left'
            )
            ->where('(iku.rpjmd_id IS NOT NULL OR iku.renstra_id IS NOT NULL)', null, false)
            ->orderBy('iku.id', 'ASC')
            ->get()
            ->getResultArray();

        $programs = $this->db->table('iku_program_pendukung')
            ->select('iku_id, program')
            ->get()
            ->getResultArray();

        $programMap = [];
        foreach ($programs as $p) {
            $programMap[$p['iku_id']][] = $p['program'];
        }

        foreach ($ikuList as &$iku) {
            $iku['program_pendukung'] = $programMap[$iku['id']] ?? [];
        }

        return $ikuList;
    }

    /* =========================================================
     * DETAIL IKU UNTUK HALAMAN EDIT
     * =======================================================*/
    public function getIkuDetail($indikatorId, $role = 'admin_opd')
    {
        $builder = $this->db->table('iku');

        if ($role === 'admin_kab') {
            $builder->where('rpjmd_id', $indikatorId);
        } else {
            $builder->where('renstra_id', $indikatorId);
        }

        $iku = $builder->get()->getRowArray();

        if (!$iku) {
            return [
                'id' => null,
                'definisi' => '',
                'rpjmd_id' => ($role === 'admin_kab') ? $indikatorId : null,
                'renstra_id' => ($role === 'admin_opd') ? $indikatorId : null,
                'program_pendukung' => [],
            ];
        }

        $programs = $this->db->table('iku_program_pendukung')
            ->select('id, program')
            ->where('iku_id', $iku['id'])
            ->get()
            ->getResultArray();

        $iku['program_pendukung'] = $programs ?? [];

        return $iku;
    }

    /* =========================================================
     * UPDATE / DELETE
     * =======================================================*/
    public function updateIku($id, array $data, string $by = 'id')
    {
        return $this->db->table('iku')
            ->where($by, $id)
            ->update($data);
    }

    public function updateProgramPendukung($ikuId, $programs, $programIds = [])
    {
        $table = $this->db->table('iku_program_pendukung');

        $existingIds = $table->select('id')
            ->where('iku_id', $ikuId)
            ->get()
            ->getResultArray();
        $existingIds = array_column($existingIds, 'id');

        $keepIds = [];

        foreach ($programs as $index => $program) {
            $program = trim($program);
            if ($program === '') {
                continue;
            }

            $programId = $programIds[$index] ?? null;

            if ($programId && in_array($programId, $existingIds)) {
                $table->where('id', $programId)->update(['program' => $program]);
                $keepIds[] = $programId;
            } else {
                $table->insert([
                    'iku_id' => $ikuId,
                    'program' => $program,
                ]);
                $keepIds[] = $this->db->insertID();
            }
        }

        if (!empty($existingIds)) {
            $toDelete = array_diff($existingIds, $keepIds);
            if (!empty($toDelete)) {
                $table->whereIn('id', $toDelete)->delete();
            }
        }
    }

    public function deleteIkuComplete($id)
    {
        $db = \Config\Database::connect();
        $db->transBegin();

        try {
            $db->table('iku_program_pendukung')->where('iku_id', $id)->delete();
            $db->table('iku')->where('id', $id)->delete();

            $db->transCommit();
            return true;
        } catch (\Throwable $e) {
            $db->transRollback();
            throw $e;
        }
    }

    /* =========================================================
     * IKU + PROGRAM UNTUK ADMIN KAB (INDEX SEDERHANA)
     * =======================================================*/
    public function getAllForAdminKab(): array
    {
        $ikuRows = $this->db->table('iku')
            ->orderBy('id', 'ASC')
            ->get()
            ->getResultArray();

        if (empty($ikuRows)) {
            return [];
        }

        $progRows = $this->db->table('iku_program_pendukung')
            ->select('iku_id, program')
            ->orderBy('iku_id', 'ASC')
            ->orderBy('id', 'ASC')
            ->get()
            ->getResultArray();

        $progMap = [];
        foreach ($progRows as $p) {
            $progMap[$p['iku_id']][] = $p['program'];
        }

        foreach ($ikuRows as &$iku) {
            $iku['program_pendukung'] = $progMap[$iku['id']] ?? [];
        }

        return $ikuRows;
    }

    /**
     * Periode filter per 5 tahun
     * - mode 'opd'       => ambil dari renstra_sasaran (tahun_mulai, tahun_akhir)
     * - mode 'kabupaten' => ambil dari rpjmd_misi (tahun_mulai, tahun_akhir)
     *
     * Hasil:
     * [
     *   '2025_2029' => [
     *       'period' => '2025 - 2029',
     *       'years'  => [2025, 2026, 2027, 2028, 2029],
     *   ],
     *   ...
     * ]
     */
    public function getPeriodeOptions(string $mode = 'opd'): array
    {
        $periodes = [];

        if ($mode === 'kabupaten') {
            // ================= RPJMD (mode kabupaten) =================
            $rows = $this->db->table('rpjmd_misi')
                ->select('DISTINCT tahun_mulai, tahun_akhir', false)
                ->orderBy('tahun_mulai', 'ASC')
                ->get()
                ->getResultArray();
        } else {
            // ================= RENSTRA (mode OPD) =====================
            $rows = $this->db->table('renstra_sasaran')
                ->select('DISTINCT tahun_mulai, tahun_akhir', false)
                ->orderBy('tahun_mulai', 'ASC')
                ->get()
                ->getResultArray();
        }

        foreach ($rows as $row) {
            if (empty($row['tahun_mulai']) || empty($row['tahun_akhir'])) {
                continue;
            }

            $start = (int) $row['tahun_mulai'];
            $end = (int) $row['tahun_akhir'];

            // kalau mau strict 5 tahun, aktifkan ini:
            // if ($end - $start !== 4) continue;

            $key = $start . '_' . $end;      // contoh: "2025_2029"
            $years = range($start, $end);      // [2025, 2026, 2027, 2028, 2029]

            $periodes[$key] = [
                'period' => $start . ' - ' . $end,
                'years' => $years,
            ];
        }

        return $periodes;
    }

    /** Semua IKU + program pendukung (dipakai di index admin kab) */
    public function getAllIkuWithPrograms(): array
    {
        return $this->getAllForAdminKab();
    }

    /** Matriks RENSTRA untuk mode OPD */
    public function getRenstraMatrix(array $yearsFilter = []): array
    {
        $builder = $this->db->table('renstra_sasaran rs')
            ->select('
                rs.id      AS sasaran_id,
                rs.sasaran AS sasaran,
                rs.opd_id  AS opd_id,
                o.nama_opd AS nama_opd,
                ris.id     AS indikator_id,
                ris.indikator_sasaran,
                ris.satuan,
                rt.tahun,
                rt.target AS target_tahunan
            ')
            ->join('opd o', 'o.id = rs.opd_id', 'left')
            ->join('renstra_indikator_sasaran ris', 'ris.renstra_sasaran_id = rs.id', 'left')
            ->join('renstra_target rt', 'rt.renstra_indikator_id = ris.id', 'left');

        if (!empty($yearsFilter)) {
            $builder->whereIn('rt.tahun', $yearsFilter);
        }

        $builder->orderBy('o.nama_opd', 'ASC')
            ->orderBy('rs.sasaran', 'ASC')
            ->orderBy('ris.id', 'ASC')
            ->orderBy('rt.tahun', 'ASC');

        $rows = $builder->get()->getResultArray();

        if (empty($rows)) {
            return [];
        }

        $map = [];
        foreach ($rows as $r) {
            if (empty($r['indikator_id'])) {
                continue;
            }

            $opdId = (int) ($r['opd_id'] ?? 0);
            $sasaranId = (int) ($r['sasaran_id'] ?? 0);
            $key = $opdId . '-' . $sasaranId;

            if (!isset($map[$key])) {
                $map[$key] = [
                    'opd_id' => $opdId,
                    'nama_opd' => $r['nama_opd'] ?? '-',
                    'sasaran' => $r['sasaran'] ?? '-',
                    'indikator_index' => [],
                ];
            }

            $indikatorId = (int) $r['indikator_id'];

            if (!isset($map[$key]['indikator_index'][$indikatorId])) {
                $map[$key]['indikator_index'][$indikatorId] = [
                    'id' => $indikatorId,
                    'indikator_sasaran' => $r['indikator_sasaran'] ?? '-',
                    'satuan' => $r['satuan'] ?? '-',
                    'target_tahunan' => [],
                ];
            }

            if (!empty($r['tahun'])) {
                $tahun = (int) $r['tahun'];
                $target = $r['target_tahunan'] ?? null;
                $map[$key]['indikator_index'][$indikatorId]['target_tahunan'][$tahun] = $target;
            }
        }

        $result = [];
        foreach ($map as $entry) {
            $indikatorArr = array_values($entry['indikator_index']);
            unset($entry['indikator_index']);
            $entry['indikator_sasaran'] = $indikatorArr;
            $result[] = $entry;
        }

        return $result;
    }

    /** Matriks RPJMD untuk mode kabupaten */
    public function getRpjmdMatrix(array $yearsFilter = []): array
    {
        $builder = $this->db->table('rpjmd_sasaran rs')
            ->select('
                rs.id             AS sasaran_id,
                rs.sasaran_rpjmd  AS sasaran_rpjmd,
                ris.id            AS indikator_id,
                ris.indikator_sasaran,
                ris.satuan,
                rt.tahun,
                rt.target_tahunan AS target_tahunan
            ')
            ->join('rpjmd_indikator_sasaran ris', 'ris.sasaran_id = rs.id', 'left')
            ->join('rpjmd_target rt', 'rt.indikator_sasaran_id = ris.id', 'left');

        if (!empty($yearsFilter)) {
            $builder->whereIn('rt.tahun', $yearsFilter);
        }

        $builder->orderBy('rs.sasaran_rpjmd', 'ASC')
            ->orderBy('ris.id', 'ASC')
            ->orderBy('rt.tahun', 'ASC');

        $rows = $builder->get()->getResultArray();

        if (empty($rows)) {
            return [];
        }

        $map = [];
        foreach ($rows as $r) {
            if (empty($r['indikator_id'])) {
                continue;
            }

            $sasaranId = (int) ($r['sasaran_id'] ?? 0);
            $key = $sasaranId;

            if (!isset($map[$key])) {
                $map[$key] = [
                    'sasaran_rpjmd' => $r['sasaran_rpjmd'] ?? '-',
                    'indikator_index' => [],
                ];
            }

            $indikatorId = (int) $r['indikator_id'];
            if (!isset($map[$key]['indikator_index'][$indikatorId])) {
                $map[$key]['indikator_index'][$indikatorId] = [
                    'id' => $indikatorId,
                    'indikator_sasaran' => $r['indikator_sasaran'] ?? '-',
                    'satuan' => $r['satuan'] ?? '-',
                    'target_tahunan' => [],
                ];
            }

            if (!empty($r['tahun'])) {
                $tahun = (int) $r['tahun'];
                $target = $r['target_tahunan'] ?? null;
                $map[$key]['indikator_index'][$indikatorId]['target_tahunan'][$tahun] = $target;
            }
        }

        $result = [];
        foreach ($map as $entry) {
            $indikatorArr = array_values($entry['indikator_index']);
            unset($entry['indikator_index']);
            $entry['indikator_sasaran'] = $indikatorArr;
            $result[] = $entry;
        }

        return $result;
    }
}
