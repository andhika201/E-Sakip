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

        // Sejak IKU berdiri sendiri, periodenya diambil dari `iku_sasaran`,
        // bukan lagi dari renstra.
        [$periode, $availablePeriods, $periodeError] = $this->resolvePeriod($opdId, false, 'iku_sasaran');

        if ($periodeError) {
            return $this->respondError($periodeError, 400);
        }

        $status = strtolower(trim((string) ($this->request->getGet('status') ?? 'selesai')));
        $allowedStatuses = ['selesai', 'draft', 'belum', 'tercapai', 'all'];

        if (!in_array($status, $allowedStatuses, true)) {
            return $this->respondError('Status tidak valid. Gunakan selesai, draft, belum, tercapai, atau all.', 400);
        }

        $builder = $this->db->table('iku_indikator ind')
            ->select("
                ind.id AS indikator_id,
                ind.indikator,
                ind.definisi,
                ind.rumusan_perhitungan,
                ind.sumber_data,
                ind.penanggung_jawab,
                ind.jenis_indikator,
                ind.baseline,
                ind.urutan AS indikator_urutan,
                ind.status,
                ind.created_at,
                ind.updated_at,
                ind.satuan AS satuan_raw,
                COALESCE(sat.satuan, NULLIF(ind.satuan, '')) AS satuan,
                sas.id AS sasaran_id,
                sas.sasaran,
                sas.urutan AS sasaran_urutan,
                sas.tahun_mulai,
                sas.tahun_akhir,
                o.id AS opd_id,
                o.nama_opd,
                o.singkatan
            ", false)
            ->join('iku_sasaran sas', 'sas.id = ind.iku_sasaran_id')
            ->join('opd o', 'o.id = sas.opd_id')
            ->join('satuan sat', "ind.satuan REGEXP '^[0-9]+$' AND sat.id = ind.satuan", 'left', false)
            ->where('sas.opd_id', $opdId)
            ->orderBy('sas.urutan', 'ASC')
            ->orderBy('sas.id', 'ASC')
            ->orderBy('ind.urutan', 'ASC')
            ->orderBy('ind.id', 'ASC');

        if ($status !== 'all') {
            $builder->where('ind.status', $status);
        }

        if ($periode) {
            $builder->where('sas.tahun_mulai', $periode['tahun_mulai']);
            $builder->where('sas.tahun_akhir', $periode['tahun_akhir']);
        }

        $rows = $builder->get()->getResultArray();
        [$data, $years] = $this->formatIkuRows($rows, $periode);

        return $this->respondSuccess($data, [
            'opd' => $opd,
            'periode' => $periode,
            'available_periods' => $availablePeriods,
            // Daftar kolom tahun yang dipakai tabel di web. Kalau periode tidak
            // difilter, isinya gabungan tahun yang benar-benar punya target.
            'years' => $years,
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

    /**
     * @return array{0: array<int, array<string, mixed>>, 1: int[]} [data, daftar tahun kolom]
     */
    private function formatIkuRows(array $rows, ?array $periode): array
    {
        if (empty($rows)) {
            return [[], $periode['years'] ?? []];
        }

        $indikatorIds = array_column($rows, 'indikator_id');

        $programRows = $this->db->table('iku_program')
            ->select('id, iku_indikator_id, program')
            ->whereIn('iku_indikator_id', $indikatorIds)
            ->orderBy('iku_indikator_id', 'ASC')
            ->orderBy('urutan', 'ASC')
            ->orderBy('id', 'ASC')
            ->get()
            ->getResultArray();

        $programMap = [];
        foreach ($programRows as $program) {
            $programMap[(int) $program['iku_indikator_id']][] = [
                'id' => (int) $program['id'],
                'program' => $program['program'],
            ];
        }

        $targetBuilder = $this->db->table('iku_target')
            ->select('iku_indikator_id, tahun, target')
            ->whereIn('iku_indikator_id', $indikatorIds)
            ->orderBy('tahun', 'ASC');

        if ($periode) {
            $targetBuilder
                ->where('tahun >=', $periode['tahun_mulai'])
                ->where('tahun <=', $periode['tahun_akhir']);
        }

        $targetRows = $targetBuilder->get()->getResultArray();

        $targetMap = [];
        $tahunSet = [];
        foreach ($targetRows as $target) {
            $tahun = (int) $target['tahun'];
            $targetMap[(int) $target['iku_indikator_id']][$tahun] = $target['target'];
            $tahunSet[$tahun] = true;
        }

        // Kolom tahun mengikuti tabel di web: seluruh tahun periode bila periode
        // diketahui, kalau tidak gabungan tahun yang punya data.
        if ($periode) {
            $years = $periode['years'];
        } else {
            $years = array_keys($tahunSet);
            sort($years);
        }

        $data = [];
        foreach ($rows as $row) {
            $indikatorId = (int) $row['indikator_id'];

            // Tahun tanpa baris target tetap dimunculkan sebagai null supaya
            // konsumen bisa merender kolom lengkap seperti tampilan web.
            $targetTahunan = [];
            foreach ($years as $tahun) {
                $targetTahunan[$tahun] = $targetMap[$indikatorId][$tahun] ?? null;
            }
            foreach ($targetMap[$indikatorId] ?? [] as $tahun => $nilai) {
                $targetTahunan[$tahun] = $nilai;
            }
            ksort($targetTahunan);

            $data[] = [
                // `id` sekarang id indikator IKU. `renstra_id` dipertahankan sebagai
                // null demi kompatibilitas konsumen lama — IKU tidak lagi terkait renstra.
                'id' => $indikatorId,
                'iku_sasaran_id' => (int) $row['sasaran_id'],
                'renstra_id' => null,
                'definisi' => $row['definisi'],
                'rumusan_perhitungan' => $row['rumusan_perhitungan'],
                'sumber_data' => $row['sumber_data'],
                'penanggung_jawab' => $row['penanggung_jawab'],
                'jenis_indikator' => $row['jenis_indikator'],
                'baseline' => $row['baseline'],
                'status' => $row['status'],
                'opd' => [
                    'id' => (int) $row['opd_id'],
                    'nama_opd' => $row['nama_opd'],
                    'singkatan' => $row['singkatan'],
                ],
                'sasaran' => [
                    'id' => (int) $row['sasaran_id'],
                    'nama' => $row['sasaran'],
                    'urutan' => $this->intOrNull($row['sasaran_urutan']),
                ],
                'indikator' => [
                    'id' => $indikatorId,
                    'nama' => $row['indikator'],
                    'satuan' => $row['satuan'],
                    // `iku_indikator.satuan` bisa berisi id master satuan atau
                    // teks bebas — id-nya diekspos terpisah bila memang numerik.
                    'satuan_id' => ctype_digit((string) $row['satuan_raw']) ? (int) $row['satuan_raw'] : null,
                    'jenis_indikator' => $row['jenis_indikator'],
                    'baseline' => $row['baseline'],
                    'urutan' => $this->intOrNull($row['indikator_urutan']),
                ],
                'periode' => [
                    'periode' => (int) $row['tahun_mulai'] . '-' . (int) $row['tahun_akhir'],
                    'tahun_mulai' => (int) $row['tahun_mulai'],
                    'tahun_akhir' => (int) $row['tahun_akhir'],
                ],
                'target_tahunan' => $targetTahunan,
                'program_pendukung' => $programMap[$indikatorId] ?? [],
                'created_at' => $row['created_at'],
                'updated_at' => $row['updated_at'],
            ];
        }

        return [$data, $years];
    }

    private function formatCascadingRows(array $rows): array
    {
        $satuanMap = $this->satuanMap($rows);

        return array_map(function ($row) use ($satuanMap) {
            foreach ([
                'tujuan_id',
                'sasaran_id',
                'renstra_tujuan_id',
                'indikator_tujuan_id',
                'renstra_sasaran_id',
                'indikator_id',
                'es3_id',
                'es3_indikator_id',
                'es4_id',
                'es4_indikator_id',
                // Jenjang Pelaksana (jenjang ke-7, di bawah Eselon IV/JF).
                'pelaksana_id',
                'pelaksana_indikator_id',
            ] as $field) {
                if (array_key_exists($field, $row)) {
                    $row[$field] = $this->intOrNull($row[$field]);
                }
            }

            // `renstra_indikator_sasaran.satuan` menyimpan id master satuan atau
            // teks bebas; nama terbacanya diekspos terpisah agar `satuan` lama
            // tidak berubah arti bagi konsumen yang sudah ada.
            $row['satuan_nama'] = $this->namaSatuan($row['satuan'] ?? null, $satuanMap);

            return $row;
        }, $rows);
    }

    /**
     * Peta id master satuan -> nama, dari nilai `satuan` yang muncul di baris
     * cascading. Satu query untuk seluruh baris.
     *
     * @return array<int, string>
     */
    private function satuanMap(array $rows): array
    {
        $ids = [];
        foreach ($rows as $row) {
            $satuan = $row['satuan'] ?? null;
            if ($satuan !== null && ctype_digit((string) $satuan)) {
                $ids[(int) $satuan] = true;
            }
        }

        if (empty($ids)) {
            return [];
        }

        $satuanRows = $this->db->table('satuan')
            ->select('id, satuan')
            ->whereIn('id', array_keys($ids))
            ->get()
            ->getResultArray();

        $map = [];
        foreach ($satuanRows as $satuan) {
            $map[(int) $satuan['id']] = $satuan['satuan'];
        }

        return $map;
    }

    private function namaSatuan($satuan, array $satuanMap): ?string
    {
        if ($satuan === null || $satuan === '') {
            return null;
        }

        if (ctype_digit((string) $satuan)) {
            return $satuanMap[(int) $satuan] ?? null;
        }

        return (string) $satuan;
    }

    private function buildPohonKinerja(array $rows): array
    {
        $tree = [];
        $tujuanIndex = [];
        $satuanMap = $this->satuanMap($rows);

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
                    'indikator_tujuan' => [],
                    'es2' => [],
                    '_indikator_tujuan_index' => [],
                    '_es2_index' => [],
                ];
            }

            $renstraTujuan =& $sasaran['tujuan_renstra'][$sasaran['_tujuan_renstra_index'][$renstraTujuanKey]];

            $indikatorTujuanId = $this->intOrNull($row['indikator_tujuan_id'] ?? null);
            if ($indikatorTujuanId && !isset($renstraTujuan['_indikator_tujuan_index'][$indikatorTujuanId])) {
                $renstraTujuan['_indikator_tujuan_index'][$indikatorTujuanId] = true;
                $renstraTujuan['indikator_tujuan'][] = [
                    'id' => $indikatorTujuanId,
                    'nama' => $row['indikator_tujuan'],
                ];
            }

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
                    'satuan_nama' => $this->namaSatuan($row['satuan'] ?? null, $satuanMap),
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
                    'pelaksana' => [],
                    '_indikator_index' => [],
                    '_pelaksana_index' => [],
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

            // Jenjang PELAKSANA — jenjang terakhir, mengikuti pohon di web.
            $pelaksanaId = $this->intOrNull($row['pelaksana_id'] ?? null);
            if (!$pelaksanaId) {
                unset($tujuan, $sasaran, $renstraTujuan, $es2, $es3, $es4);
                continue;
            }

            if (!isset($es4['_pelaksana_index'][$pelaksanaId])) {
                $es4['_pelaksana_index'][$pelaksanaId] = count($es4['pelaksana']);
                $es4['pelaksana'][] = [
                    'id' => $pelaksanaId,
                    'nama' => $row['pelaksana_sasaran'] ?? null,
                    'csf' => $row['csf_pelaksana'] ?? null,
                    'indikator' => [],
                    '_indikator_index' => [],
                ];
            }

            $pelaksana =& $es4['pelaksana'][$es4['_pelaksana_index'][$pelaksanaId]];

            $pelaksanaIndikatorId = $this->intOrNull($row['pelaksana_indikator_id'] ?? null);
            if ($pelaksanaIndikatorId && !isset($pelaksana['_indikator_index'][$pelaksanaIndikatorId])) {
                $pelaksana['_indikator_index'][$pelaksanaIndikatorId] = true;
                $pelaksana['indikator'][] = [
                    'id' => $pelaksanaIndikatorId,
                    'nama' => $row['pelaksana_indikator'] ?? null,
                ];
            }

            unset($tujuan, $sasaran, $renstraTujuan, $es2, $es3, $es4, $pelaksana);
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

    /**
     * @param string $sumberPeriode tabel sumber daftar periode: 'renstra_sasaran'
     *                              (default) atau 'iku_sasaran' untuk endpoint IKU
     *                              yang datanya sudah berdiri sendiri.
     */
    private function resolvePeriod(int $opdId, bool $useLatestWhenEmpty, string $sumberPeriode = 'renstra_sasaran'): array
    {
        $availablePeriods = $this->getAvailablePeriods($opdId, $sumberPeriode);
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

    private function getAvailablePeriods(int $opdId, string $sumberPeriode = 'renstra_sasaran'): array
    {
        // Whitelist supaya nama tabel tidak pernah datang dari input pengguna.
        if (!in_array($sumberPeriode, ['renstra_sasaran', 'iku_sasaran'], true)) {
            $sumberPeriode = 'renstra_sasaran';
        }

        $rows = $this->db->table($sumberPeriode)
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
