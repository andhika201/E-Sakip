<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\CascadingModel;

class PerangkatDaerahController extends BaseController
{
    private const EXCLUDED_OPD_IDS = \App\Models\OpdModel::EXCLUDED_OPD_IDS;

    protected $db;
    protected $cascadingModel;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
        $this->cascadingModel = new CascadingModel();
    }

    public function index()
    {
        $opd = $this->db->table('opd')
            ->select('id, nama_opd, singkatan')
            ->whereNotIn('id', self::EXCLUDED_OPD_IDS)
            ->orderBy('nama_opd', 'ASC')
            ->get()
            ->getResultArray();

        return $this->respondSuccess(array_map([$this, 'formatOpd'], $opd), [
            'count' => count($opd),
        ]);
    }

    public function show($opdId)
    {
        $opd = $this->findOpd($opdId);

        if (!$opd) {
            return $this->respondError('Perangkat daerah tidak ditemukan.', 404);
        }

        return $this->respondSuccess($opd);
    }

    public function iku($opdId = null)
    {
        $opdId = $this->resolveOpdId($opdId);

        if (!$opdId) {
            return $this->respondError('Parameter opd_id wajib diisi.', 400);
        }

        $opd = $this->findOpd($opdId);

        if (!$opd) {
            return $this->respondError('Perangkat daerah tidak ditemukan.', 404);
        }

        [$periode, $availablePeriods, $periodeError] = $this->resolvePeriod($opdId, false);

        if ($periodeError) {
            return $this->respondError($periodeError, 400);
        }

        $status = strtolower(trim((string) ($this->request->getGet('status') ?? 'selesai')));
        $allowedStatuses = ['selesai', 'draft', 'belum', 'tercapai', 'all'];

        if (!in_array($status, $allowedStatuses, true)) {
            return $this->respondError('Status tidak valid. Gunakan selesai, draft, belum, tercapai, atau all.', 400);
        }

        $builder = $this->db->table('iku i')
            ->select('
                i.id AS iku_id,
                i.renstra_id,
                i.definisi,
                i.status,
                i.created_at,
                i.updated_at,
                o.id AS opd_id,
                o.nama_opd,
                o.singkatan,
                rs.id AS sasaran_id,
                rs.sasaran,
                rs.tahun_mulai,
                rs.tahun_akhir,
                ris.id AS indikator_id,
                ris.indikator_sasaran,
                ris.satuan
            ')
            ->join('renstra_indikator_sasaran ris', 'ris.id = i.renstra_id')
            ->join('renstra_sasaran rs', 'rs.id = ris.renstra_sasaran_id')
            ->join('opd o', 'o.id = rs.opd_id')
            ->where('i.renstra_id IS NOT NULL', null, false)
            ->where('rs.opd_id', $opdId)
            ->orderBy('rs.id', 'ASC')
            ->orderBy('ris.id', 'ASC')
            ->orderBy('i.id', 'ASC');

        if ($status !== 'all') {
            $builder->where('i.status', $status);
        }

        if ($periode) {
            $builder->where('rs.tahun_mulai', $periode['tahun_mulai']);
            $builder->where('rs.tahun_akhir', $periode['tahun_akhir']);
        }

        $rows = $builder->get()->getResultArray();
        $data = $this->formatIkuRows($rows, $periode);

        return $this->respondSuccess($data, [
            'opd' => $opd,
            'periode' => $periode,
            'available_periods' => $availablePeriods,
            'status_filter' => $status,
            'count' => count($data),
        ]);
    }

    public function cascading($opdId = null)
    {
        $opdId = $this->resolveOpdId($opdId);

        if (!$opdId) {
            return $this->respondError('Parameter opd_id wajib diisi.', 400);
        }

        $opd = $this->findOpd($opdId);

        if (!$opd) {
            return $this->respondError('Perangkat daerah tidak ditemukan.', 404);
        }

        [$periode, $availablePeriods, $periodeError] = $this->resolvePeriod($opdId, true);

        if ($periodeError) {
            return $this->respondError($periodeError, 400);
        }

        if (!$periode) {
            return $this->respondError('Periode Renstra perangkat daerah belum tersedia.', 404);
        }

        $rows = $this->cascadingModel->getCascadingMatrixByOpd(
            $opdId,
            $periode['tahun_mulai'],
            $periode['tahun_akhir']
        );

        return $this->respondSuccess($this->formatCascadingRows($rows), [
            'opd' => $opd,
            'periode' => $periode,
            'available_periods' => $availablePeriods,
            'count' => count($rows),
        ]);
    }

    public function pohonKinerja($opdId = null)
    {
        $opdId = $this->resolveOpdId($opdId);

        if (!$opdId) {
            return $this->respondError('Parameter opd_id wajib diisi.', 400);
        }

        $opd = $this->findOpd($opdId);

        if (!$opd) {
            return $this->respondError('Perangkat daerah tidak ditemukan.', 404);
        }

        [$periode, $availablePeriods, $periodeError] = $this->resolvePeriod($opdId, true);

        if ($periodeError) {
            return $this->respondError($periodeError, 400);
        }

        if (!$periode) {
            return $this->respondError('Periode Renstra perangkat daerah belum tersedia.', 404);
        }

        $rows = $this->cascadingModel->getCascadingMatrixByOpd(
            $opdId,
            $periode['tahun_mulai'],
            $periode['tahun_akhir']
        );

        return $this->respondSuccess($this->buildPohonKinerja($rows), [
            'opd' => $opd,
            'periode' => $periode,
            'available_periods' => $availablePeriods,
            'visi' => $this->getVisiByPeriod($periode['tahun_mulai'], $periode['tahun_akhir']),
            'count' => count($rows),
        ]);
    }

    private function formatIkuRows(array $rows, ?array $periode): array
    {
        if (empty($rows)) {
            return [];
        }

        $ikuIds = array_column($rows, 'iku_id');
        $indikatorIds = array_column($rows, 'indikator_id');

        $programRows = $this->db->table('iku_program_pendukung')
            ->select('id, iku_id, program')
            ->whereIn('iku_id', $ikuIds)
            ->orderBy('iku_id', 'ASC')
            ->orderBy('id', 'ASC')
            ->get()
            ->getResultArray();

        $programMap = [];
        foreach ($programRows as $program) {
            $programMap[(int) $program['iku_id']][] = [
                'id' => (int) $program['id'],
                'program' => $program['program'],
            ];
        }

        $targetBuilder = $this->db->table('renstra_target')
            ->select('renstra_indikator_id, tahun, target')
            ->whereIn('renstra_indikator_id', $indikatorIds)
            ->orderBy('tahun', 'ASC');

        if ($periode) {
            $targetBuilder
                ->where('tahun >=', $periode['tahun_mulai'])
                ->where('tahun <=', $periode['tahun_akhir']);
        }

        $targetRows = $targetBuilder->get()->getResultArray();

        $targetMap = [];
        foreach ($targetRows as $target) {
            $targetMap[(int) $target['renstra_indikator_id']][(int) $target['tahun']] = $target['target'];
        }

        $data = [];
        foreach ($rows as $row) {
            $ikuId = (int) $row['iku_id'];
            $indikatorId = (int) $row['indikator_id'];

            $data[] = [
                'id' => $ikuId,
                'renstra_id' => $this->intOrNull($row['renstra_id']),
                'definisi' => $row['definisi'],
                'status' => $row['status'],
                'opd' => [
                    'id' => (int) $row['opd_id'],
                    'nama_opd' => $row['nama_opd'],
                    'singkatan' => $row['singkatan'],
                ],
                'sasaran' => [
                    'id' => (int) $row['sasaran_id'],
                    'nama' => $row['sasaran'],
                ],
                'indikator' => [
                    'id' => $indikatorId,
                    'nama' => $row['indikator_sasaran'],
                    'satuan' => $row['satuan'],
                ],
                'periode' => [
                    'tahun_mulai' => (int) $row['tahun_mulai'],
                    'tahun_akhir' => (int) $row['tahun_akhir'],
                ],
                'target_tahunan' => $targetMap[$indikatorId] ?? [],
                'program_pendukung' => $programMap[$ikuId] ?? [],
                'created_at' => $row['created_at'],
                'updated_at' => $row['updated_at'],
            ];
        }

        return $data;
    }

    private function formatCascadingRows(array $rows): array
    {
        return array_map(function ($row) {
            foreach ([
                'tujuan_id',
                'sasaran_id',
                'renstra_tujuan_id',
                'renstra_sasaran_id',
                'indikator_id',
                'es3_id',
                'es3_indikator_id',
                'es4_id',
                'es4_indikator_id',
            ] as $field) {
                if (array_key_exists($field, $row)) {
                    $row[$field] = $this->intOrNull($row[$field]);
                }
            }

            return $row;
        }, $rows);
    }

    private function buildPohonKinerja(array $rows): array
    {
        $tree = [];
        $tujuanIndex = [];

        foreach ($rows as $row) {
            $tujuanKey = $this->nodeKey($row['tujuan_id'] ?? null);
            if (!isset($tujuanIndex[$tujuanKey])) {
                $tujuanIndex[$tujuanKey] = count($tree);
                $tree[] = [
                    'id' => $this->intOrNull($row['tujuan_id'] ?? null),
                    'nama' => $row['tujuan_rpjmd'] ?: '(Tanpa Tujuan RPJMD)',
                    'sasaran' => [],
                    '_sasaran_index' => [],
                ];
            }

            $tujuan =& $tree[$tujuanIndex[$tujuanKey]];

            $sasaranKey = $this->nodeKey($row['sasaran_id'] ?? null);
            if (!isset($tujuan['_sasaran_index'][$sasaranKey])) {
                $tujuan['_sasaran_index'][$sasaranKey] = count($tujuan['sasaran']);
                $tujuan['sasaran'][] = [
                    'id' => $this->intOrNull($row['sasaran_id'] ?? null),
                    'nama' => $row['sasaran_rpjmd'] ?: '(Tanpa Sasaran RPJMD)',
                    'tujuan_renstra' => [],
                    '_tujuan_renstra_index' => [],
                ];
            }

            $sasaran =& $tujuan['sasaran'][$tujuan['_sasaran_index'][$sasaranKey]];

            $renstraTujuanKey = $this->nodeKey($row['renstra_tujuan_id'] ?? null);
            if (!isset($sasaran['_tujuan_renstra_index'][$renstraTujuanKey])) {
                $sasaran['_tujuan_renstra_index'][$renstraTujuanKey] = count($sasaran['tujuan_renstra']);
                $sasaran['tujuan_renstra'][] = [
                    'id' => $this->intOrNull($row['renstra_tujuan_id'] ?? null),
                    'nama' => $row['renstra_tujuan'] ?: '(Tanpa Tujuan Renstra)',
                    'es2' => [],
                    '_es2_index' => [],
                ];
            }

            $renstraTujuan =& $sasaran['tujuan_renstra'][$sasaran['_tujuan_renstra_index'][$renstraTujuanKey]];

            if (empty($row['renstra_sasaran_id']) && empty($row['renstra_sasaran'])) {
                unset($tujuan, $sasaran, $renstraTujuan);
                continue;
            }

            $es2Key = $this->nodeKey($row['renstra_sasaran_id'] ?? null);
            if (!isset($renstraTujuan['_es2_index'][$es2Key])) {
                $renstraTujuan['_es2_index'][$es2Key] = count($renstraTujuan['es2']);
                $renstraTujuan['es2'][] = [
                    'id' => $this->intOrNull($row['renstra_sasaran_id'] ?? null),
                    'nama' => $row['renstra_sasaran'] ?: '(Tanpa Sasaran ES.II)',
                    'csf' => $row['csf_es2'] ?? null,
                    'indikator' => [],
                    'es3' => [],
                    '_indikator_index' => [],
                    '_es3_index' => [],
                ];
            }

            $es2 =& $renstraTujuan['es2'][$renstraTujuan['_es2_index'][$es2Key]];

            $indikatorId = $this->intOrNull($row['indikator_id'] ?? null);
            if ($indikatorId && !isset($es2['_indikator_index'][$indikatorId])) {
                $es2['_indikator_index'][$indikatorId] = true;
                $es2['indikator'][] = [
                    'id' => $indikatorId,
                    'nama' => $row['indikator_sasaran'],
                    'satuan' => $row['satuan'] ?? null,
                ];
            }

            $es3Id = $this->intOrNull($row['es3_id'] ?? null);
            if (!$es3Id) {
                unset($tujuan, $sasaran, $renstraTujuan, $es2);
                continue;
            }

            if (!isset($es2['_es3_index'][$es3Id])) {
                $es2['_es3_index'][$es3Id] = count($es2['es3']);
                $es2['es3'][] = [
                    'id' => $es3Id,
                    'nama' => $row['es3_sasaran'],
                    'csf' => $row['csf_es3'] ?? null,
                    'indikator' => [],
                    'es4' => [],
                    '_indikator_index' => [],
                    '_es4_index' => [],
                ];
            }

            $es3 =& $es2['es3'][$es2['_es3_index'][$es3Id]];

            $es3IndikatorId = $this->intOrNull($row['es3_indikator_id'] ?? null);
            if ($es3IndikatorId && !isset($es3['_indikator_index'][$es3IndikatorId])) {
                $es3['_indikator_index'][$es3IndikatorId] = true;
                $es3['indikator'][] = [
                    'id' => $es3IndikatorId,
                    'nama' => $row['es3_indikator'],
                ];
            }

            $es4Id = $this->intOrNull($row['es4_id'] ?? null);
            if (!$es4Id) {
                unset($tujuan, $sasaran, $renstraTujuan, $es2, $es3);
                continue;
            }

            if (!isset($es3['_es4_index'][$es4Id])) {
                $es3['_es4_index'][$es4Id] = count($es3['es4']);
                $es3['es4'][] = [
                    'id' => $es4Id,
                    'nama' => $row['es4_sasaran'],
                    'csf' => $row['csf_es4'] ?? null,
                    'indikator' => [],
                    '_indikator_index' => [],
                ];
            }

            $es4 =& $es3['es4'][$es3['_es4_index'][$es4Id]];

            $es4IndikatorId = $this->intOrNull($row['es4_indikator_id'] ?? null);
            if ($es4IndikatorId && !isset($es4['_indikator_index'][$es4IndikatorId])) {
                $es4['_indikator_index'][$es4IndikatorId] = true;
                $es4['indikator'][] = [
                    'id' => $es4IndikatorId,
                    'nama' => $row['es4_indikator'],
                ];
            }

            unset($tujuan, $sasaran, $renstraTujuan, $es2, $es3, $es4);
        }

        return $this->removeInternalKeys($tree);
    }

    private function resolveOpdId($opdId): ?int
    {
        $opdId = $opdId ?? $this->request->getGet('opd_id');

        if ($opdId === null || $opdId === '') {
            return null;
        }

        if (!ctype_digit((string) $opdId)) {
            return null;
        }

        $opdId = (int) $opdId;

        return $opdId > 0 ? $opdId : null;
    }

    private function findOpd($opdId): ?array
    {
        if (!ctype_digit((string) $opdId) || (int) $opdId < 1) {
            return null;
        }

        $opd = $this->db->table('opd')
            ->select('id, nama_opd, singkatan')
            ->where('id', (int) $opdId)
            ->whereNotIn('id', self::EXCLUDED_OPD_IDS)
            ->get()
            ->getRowArray();

        return $opd ? $this->formatOpd($opd) : null;
    }

    private function formatOpd(array $opd): array
    {
        return [
            'id' => (int) $opd['id'],
            'nama_opd' => $opd['nama_opd'],
            'singkatan' => $opd['singkatan'] ?? null,
        ];
    }

    private function resolvePeriod(int $opdId, bool $useLatestWhenEmpty): array
    {
        $availablePeriods = $this->getAvailablePeriods($opdId);
        $periode = trim((string) ($this->request->getGet('periode') ?? ''));
        $start = $this->request->getGet('tahun_mulai') ?? $this->request->getGet('start');
        $end = $this->request->getGet('tahun_akhir') ?? $this->request->getGet('end');

        if ($periode !== '') {
            if (!preg_match('/^\d{4}-\d{4}$/', $periode)) {
                return [null, $availablePeriods, 'Format periode tidak valid. Gunakan format YYYY-YYYY, contoh 2025-2029.'];
            }

            [$start, $end] = explode('-', $periode);

            return [$this->makePeriod((int) $start, (int) $end), $availablePeriods, null];
        }

        if ($start !== null || $end !== null) {
            if (!ctype_digit((string) $start) || !ctype_digit((string) $end)) {
                return [null, $availablePeriods, 'tahun_mulai dan tahun_akhir harus berupa tahun 4 digit.'];
            }

            return [$this->makePeriod((int) $start, (int) $end), $availablePeriods, null];
        }

        if ($useLatestWhenEmpty && !empty($availablePeriods)) {
            return [$availablePeriods[0], $availablePeriods, null];
        }

        return [null, $availablePeriods, null];
    }

    private function makePeriod(int $start, int $end): array
    {
        if ($start > $end) {
            [$start, $end] = [$end, $start];
        }

        return [
            'periode' => $start . '-' . $end,
            'tahun_mulai' => $start,
            'tahun_akhir' => $end,
            'years' => range($start, $end),
        ];
    }

    private function getAvailablePeriods(int $opdId): array
    {
        $rows = $this->db->table('renstra_sasaran')
            ->select('tahun_mulai, tahun_akhir')
            ->where('opd_id', $opdId)
            ->groupBy(['tahun_mulai', 'tahun_akhir'])
            ->orderBy('tahun_mulai', 'DESC')
            ->orderBy('tahun_akhir', 'DESC')
            ->get()
            ->getResultArray();

        $periods = [];
        foreach ($rows as $row) {
            if ($row['tahun_mulai'] === null || $row['tahun_akhir'] === null) {
                continue;
            }

            $periods[] = $this->makePeriod((int) $row['tahun_mulai'], (int) $row['tahun_akhir']);
        }

        return $periods;
    }

    private function getVisiByPeriod(int $start, int $end): string
    {
        $row = $this->db->table('rpjmd_misi m')
            ->select('rv.visi')
            ->join('rpjmd_visi rv', 'rv.id = m.rpjmd_visi_id', 'left')
            ->where('m.tahun_mulai', $start)
            ->where('m.tahun_akhir', $end)
            ->orderBy('m.id', 'ASC')
            ->get()
            ->getRowArray();

        return $row['visi'] ?? '';
    }

    private function intOrNull($value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }

    private function nodeKey($value): string
    {
        return $value === null || $value === '' ? 'none' : (string) $value;
    }

    private function removeInternalKeys($value)
    {
        if (!is_array($value)) {
            return $value;
        }

        foreach ($value as $key => $item) {
            if (is_string($key) && substr($key, 0, 1) === '_') {
                unset($value[$key]);
                continue;
            }

            $value[$key] = $this->removeInternalKeys($item);
        }

        return $value;
    }

    private function respondSuccess($data, array $meta = [], int $statusCode = 200)
    {
        return $this->response
            ->setStatusCode($statusCode)
            ->setJSON([
                'status' => 'success',
                'meta' => $meta,
                'data' => $data,
            ]);
    }

    private function respondError(string $message, int $statusCode = 400, array $errors = [])
    {
        $payload = [
            'status' => 'error',
            'message' => $message,
        ];

        if (!empty($errors)) {
            $payload['errors'] = $errors;
        }

        return $this->response
            ->setStatusCode($statusCode)
            ->setJSON($payload);
    }
}
