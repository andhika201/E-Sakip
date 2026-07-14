<?php

namespace App\Controllers\AdminKab;

use App\Controllers\BaseController;
use App\Models\CascadingModel;

class CascadingController extends BaseController
{
    protected $cascadingModel;
    protected $db;

    public function __construct()
    {
        $this->cascadingModel = new CascadingModel();
        $this->db = \Config\Database::connect();

    }

    /** Mode tampilan yang valid. */
    private const MODES = ['kabupaten', 'opd', 'keseluruhan'];

    public function index()
    {
        $mode = $this->request->getGet('mode') ?: 'kabupaten';
        if (!in_array($mode, self::MODES, true)) {
            $mode = 'kabupaten';
        }
        $periode = $this->request->getGet('periode');
        $opdId   = $this->request->getGet('opd_id');

        // Tampilan aktif: 'tabel' (Cascading) atau 'pohon' (Pohon Kinerja).
        // Dipisah per menu sidebar -> tiap menu punya halaman/judul sendiri.
        $view = $this->request->getGet('view');
        $view = in_array($view, ['tabel', 'pohon'], true) ? $view : 'tabel';

        // Periode RPJMD
        $periodeList = $this->db->table('rpjmd_misi')
            ->select('tahun_mulai, tahun_akhir')
            ->groupBy(['tahun_mulai', 'tahun_akhir'])
            ->orderBy('tahun_mulai', 'ASC')
            ->get()
            ->getResultArray();

        // Daftar OPD untuk dropdown mode OPD
        $opdList = $this->db->table('opd')
            ->whereNotIn('id', \App\Models\OpdModel::EXCLUDED_OPD_IDS)
            ->orderBy('nama_opd', 'ASC')
            ->get()
            ->getResultArray();

        $rows = [];
        $rowspan = [];
        $firstShow = [];
        $years = [];
        $tree = [];
        $visi = '';
        $tahunMulai = null;
        $tahunAkhir = null;
        $opdName = null;

        if ($periode) {
            [$start, $end] = array_map('intval', explode('-', $periode));
            $tahunMulai = $start;
            $tahunAkhir = $end;
            $years = range($start, $end);

            if ($mode === 'kabupaten') {
                // Matriks RPJMD penuh (Misi -> Tujuan -> Sasaran -> Indikator -> Program
                // -> Perangkat Daerah) + target & kondisi akhir — selaras Cetak Cascading.
                $rows = $this->cascadingModel->getMatrix($start, $end);
                $tree = $this->cascadingModel->getPohonKinerja($start, $end);
                $visi = $this->ambilVisi($start, $end);
            } elseif ($mode === 'opd') {
                if ($opdId) {
                    $rows      = $this->cascadingModel->getCascadingMatrixByOpd($opdId, $start, $end);
                    $this->preprocessEmptyIds($rows);
                    $rowspan   = $this->opdRowspanMeta($rows);
                    $firstShow = $this->opdFirstShowMeta($rows);
                    $tree      = $this->buildOpdTree($rows);
                    $o         = $this->db->table('opd')->select('nama_opd')->where('id', $opdId)->get()->getRowArray();
                    $opdName   = $o['nama_opd'] ?? null;
                }
            } else { // keseluruhan
                $rows      = $this->cascadingModel->getKeseluruhanMatrix($start, $end);
                $this->preprocessEmptyIds($rows);
                $rowspan   = $this->keseluruhanRowspanMeta($rows);
                $firstShow = $this->keseluruhanFirstShowMeta($rows);
                // Pohon Keseluruhan ringkas: mulai dari Perangkat Daerah (tanpa Visi/Misi/Tujuan/Sasaran RPJMD).
                $tree      = $this->cascadingModel->getKeseluruhanByOpd($start, $end);
                $visi      = $this->ambilVisi($start, $end);
            }
        }

        $data = [
            'mode'           => $mode,
            'view'           => $view,
            'title'          => ($view === 'pohon' ? 'Pohon Kinerja' : 'Cascading'),
            // Pohon Kabupaten BERHENTI di Indikator Sasaran RPJMD (tanpa cabang Perangkat Daerah/Program).
            // Pohon OPD tanpa CSF; indikator OPD diberi kode "IDK".
            // Flag dikirim via DATA (bukan arg include options).
            'showOpd'        => ($mode !== 'kabupaten'),
            'showCsf'        => ($mode !== 'opd'),
            'showKode'       => ($mode === 'opd'),
            'opd_list'       => $opdList,
            'opd_id'         => $opdId,
            'opd_name'       => $opdName,
            'rows'           => $rows,
            'rowspan'        => $rowspan,
            'firstShow'      => $firstShow,
            'periode_master' => $periodeList,
            'years'          => $years,
            'tree'           => $tree,
            'visi'           => $visi,
            'tahun_mulai'    => $tahunMulai,
            'tahun_akhir'    => $tahunAkhir,
            'filters'        => [
                'periode' => $periode,
            ],
        ];

        return view('adminKabupaten/cascading/cascading', $data);
    }

    /** Ambil visi RPJMD untuk satu periode. */
    private function ambilVisi(int $start, int $end): string
    {
        $firstMisi = $this->db->table('rpjmd_misi m')
            ->select('rv.visi')
            ->join('rpjmd_visi rv', 'rv.id = m.rpjmd_visi_id', 'left')
            ->where('m.tahun_mulai', $start)
            ->where('m.tahun_akhir', $end)
            ->orderBy('m.id', 'ASC')
            ->get()->getRowArray();
        return $firstMisi['visi'] ?? '';
    }

    // ================= META: MODE KABUPATEN (backbone RPJMD) =================
    private function backboneRowspanMeta($rows): array
    {
        $m = ['tujuan' => [], 'sasaran' => []];
        foreach ($rows as $r) {
            $m['tujuan'][$r['tujuan_id']]   = ($m['tujuan'][$r['tujuan_id']] ?? 0) + 1;
            $m['sasaran'][$r['sasaran_id']] = ($m['sasaran'][$r['sasaran_id']] ?? 0) + 1;
        }
        return $m;
    }
    private function backboneFirstShowMeta($rows): array
    {
        $s = ['tujuan' => [], 'sasaran' => []];
        foreach ($rows as $i => $r) {
            if (!isset($s['tujuan'][$r['tujuan_id']]))   $s['tujuan'][$r['tujuan_id']]   = $i;
            if (!isset($s['sasaran'][$r['sasaran_id']])) $s['sasaran'][$r['sasaran_id']] = $i;
        }
        return $s;
    }

    // ================= META: MODE KESELURUHAN (RPJMD → Renstra OPD) =========
    // Kunci komposit agar baris tanpa renstra (id null) tidak saling tumpang tindih.
    public static function ksOpdKey($r): string { return ($r['sasaran_id'] ?? 'x') . '|' . ($r['opd_id'] ?? 'x'); }
    public static function ksRtKey($r): string  { return self::ksOpdKey($r) . '|' . ($r['renstra_tujuan_id'] ?? 'x'); }
    public static function ksRsKey($r): string  { return self::ksRtKey($r) . '|' . ($r['renstra_sasaran_id'] ?? 'x'); }

    private function keseluruhanRowspanMeta($rows): array
    {
        $m = ['tujuan' => [], 'sasaran' => [], 'opd' => [], 'renstra_tujuan' => [], 'renstra_sasaran' => []];
        foreach ($rows as $r) {
            $m['tujuan'][$r['tujuan_id']]               = ($m['tujuan'][$r['tujuan_id']] ?? 0) + 1;
            $m['sasaran'][$r['sasaran_id']]             = ($m['sasaran'][$r['sasaran_id']] ?? 0) + 1;
            $m['opd'][self::ksOpdKey($r)]               = ($m['opd'][self::ksOpdKey($r)] ?? 0) + 1;
            $m['renstra_tujuan'][self::ksRtKey($r)]     = ($m['renstra_tujuan'][self::ksRtKey($r)] ?? 0) + 1;
            $m['renstra_sasaran'][self::ksRsKey($r)]    = ($m['renstra_sasaran'][self::ksRsKey($r)] ?? 0) + 1;
        }
        return $m;
    }
    private function keseluruhanFirstShowMeta($rows): array
    {
        $s = ['tujuan' => [], 'sasaran' => [], 'opd' => [], 'renstra_tujuan' => [], 'renstra_sasaran' => []];
        foreach ($rows as $i => $r) {
            if (!isset($s['tujuan'][$r['tujuan_id']]))            $s['tujuan'][$r['tujuan_id']]            = $i;
            if (!isset($s['sasaran'][$r['sasaran_id']]))          $s['sasaran'][$r['sasaran_id']]          = $i;
            if (!isset($s['opd'][self::ksOpdKey($r)]))            $s['opd'][self::ksOpdKey($r)]            = $i;
            if (!isset($s['renstra_tujuan'][self::ksRtKey($r)]))  $s['renstra_tujuan'][self::ksRtKey($r)]  = $i;
            if (!isset($s['renstra_sasaran'][self::ksRsKey($r)])) $s['renstra_sasaran'][self::ksRsKey($r)] = $i;
        }
        return $s;
    }

    private function preprocessEmptyIds(array &$rows): void
    {
        foreach ($rows as $index => &$r) {
            if (empty($r['tujuan_id'])) {
                $r['tujuan_id'] = 'empty_tujuan_' . $index;
            }
            if (empty($r['sasaran_id'])) {
                $r['sasaran_id'] = 'empty_sasaran_' . $index;
            }
            if (empty($r['renstra_tujuan_id'])) {
                $r['renstra_tujuan_id'] = 'empty_rt_' . $index;
            }
            if (empty($r['indikator_tujuan_id'])) {
                $r['indikator_tujuan_id'] = 'empty_it_' . $r['renstra_tujuan_id'];
            }
            if (empty($r['renstra_sasaran_id'])) {
                $r['renstra_sasaran_id'] = 'empty_rs_' . $index;
            }
            if (isset($r['indikator_id']) && empty($r['indikator_id'])) {
                $r['indikator_id'] = 'empty_ris_' . $index;
            }
            if (isset($r['renstra_indikator_id']) && empty($r['renstra_indikator_id'])) {
                $r['renstra_indikator_id'] = 'empty_ri_' . $index;
            }
        }
        unset($r);
    }

    private function opdRowspanMeta($rows): array
    {
        $meta = [
            'tujuan' => [], 'sasaran' => [], 'tujuan_renstra' => [], 'sasaran_renstra' => [],
            'indikator_tujuan' => [], 'indikator' => [], 'es3' => [], 'es3_indikator' => [], 'es4' => [],
        ];
        foreach ($rows as $r) {
            $meta['tujuan'][$r['tujuan_id']]                 = ($meta['tujuan'][$r['tujuan_id']] ?? 0) + 1;
            $meta['sasaran'][$r['sasaran_id']]               = ($meta['sasaran'][$r['sasaran_id']] ?? 0) + 1;
            $meta['tujuan_renstra'][$r['renstra_tujuan_id']] = ($meta['tujuan_renstra'][$r['renstra_tujuan_id']] ?? 0) + 1;
            $meta['indikator_tujuan'][$r['indikator_tujuan_id']] = ($meta['indikator_tujuan'][$r['indikator_tujuan_id']] ?? 0) + 1;
            $meta['sasaran_renstra'][$r['renstra_sasaran_id']] = ($meta['sasaran_renstra'][$r['renstra_sasaran_id']] ?? 0) + 1;
            $meta['indikator'][$r['indikator_id']]           = ($meta['indikator'][$r['indikator_id']] ?? 0) + 1;
            if ($r['es3_id']) {
                $meta['es3'][$r['es3_id']] = ($meta['es3'][$r['es3_id']] ?? 0) + 1;
            }
            $key = $r['es3_id'] . '_' . ($r['es3_indikator_id'] ?? null);
            $meta['es3_indikator'][$key] = ($meta['es3_indikator'][$key] ?? 0) + 1;
            if ($r['es4_id']) {
                $meta['es4'][$r['es4_id']] = ($meta['es4'][$r['es4_id']] ?? 0) + 1;
            }
        }
        return $meta;
    }
    private function opdFirstShowMeta($rows): array
    {
        $shown = [
            'tujuan' => [], 'sasaran' => [], 'tujuan_renstra' => [], 'sasaran_renstra' => [],
            'indikator_tujuan' => [], 'indikator' => [], 'es3' => [], 'es3_indikator' => [], 'es4' => [],
        ];
        foreach ($rows as $index => $r) {
            if (!isset($shown['tujuan'][$r['tujuan_id']]))                 $shown['tujuan'][$r['tujuan_id']] = $index;
            if (!isset($shown['sasaran'][$r['sasaran_id']]))               $shown['sasaran'][$r['sasaran_id']] = $index;
            if (!isset($shown['tujuan_renstra'][$r['renstra_tujuan_id']])) $shown['tujuan_renstra'][$r['renstra_tujuan_id']] = $index;
            if (!isset($shown['indikator_tujuan'][$r['indikator_tujuan_id']])) $shown['indikator_tujuan'][$r['indikator_tujuan_id']] = $index;
            if (!isset($shown['sasaran_renstra'][$r['renstra_sasaran_id']])) $shown['sasaran_renstra'][$r['renstra_sasaran_id']] = $index;
            if (!isset($shown['indikator'][$r['indikator_id']]))           $shown['indikator'][$r['indikator_id']] = $index;
            if ($r['es3_id'] && !isset($shown['es3'][$r['es3_id']]))       $shown['es3'][$r['es3_id']] = $index;
            $key = $r['es3_id'] . '_' . ($r['es3_indikator_id'] ?? null);
            if (!isset($shown['es3_indikator'][$key]))                     $shown['es3_indikator'][$key] = $index;
            if ($r['es4_id'] && !isset($shown['es4'][$r['es4_id']]))       $shown['es4'][$r['es4_id']] = $index;
        }
        return $shown;
    }
    private function buildOpdTree($rows): array
    {
        $tree = [];
        foreach ($rows as $r) {
            $tId = rtrim('_' . ($r['tujuan_id'] ?? 'none'), '_');
            if (!isset($tree[$tId])) {
                $tree[$tId] = ['nama' => $r['tujuan_rpjmd'] ?: '(Tanpa Tujuan RPJMD)', 'sasarans' => []];
            }
            $sId = rtrim('_' . ($r['sasaran_id'] ?? 'none'), '_');
            if (!isset($tree[$tId]['sasarans'][$sId])) {
                $tree[$tId]['sasarans'][$sId] = ['nama' => $r['sasaran_rpjmd'] ?: '(Tanpa Sasaran RPJMD)', 'tujuan_renstras' => []];
            }
            $rtId = rtrim('_' . ($r['renstra_tujuan_id'] ?? 'none'), '_');
            if (!isset($tree[$tId]['sasarans'][$sId]['tujuan_renstras'][$rtId])) {
                $tree[$tId]['sasarans'][$sId]['tujuan_renstras'][$rtId] = [
                    'nama' => $r['renstra_tujuan'] ?: '(Tanpa Tujuan Renstra)',
                    'indikator_tujuan' => [],
                    'es2s' => [],
                ];
            }
            $itId = $r['indikator_tujuan_id'] ?? null;
            if (!empty($itId) && !empty($r['indikator_tujuan'])) {
                $tree[$tId]['sasarans'][$sId]['tujuan_renstras'][$rtId]['indikator_tujuan'][$itId] = $r['indikator_tujuan'];
            }
            $rsId = rtrim('_' . ($r['renstra_sasaran_id'] ?? 'none'), '_');
            if (empty($r['renstra_sasaran_id']) && empty($r['renstra_sasaran'])) {
                continue;
            }
            if (!isset($tree[$tId]['sasarans'][$sId]['tujuan_renstras'][$rtId]['es2s'][$rsId])) {
                $tree[$tId]['sasarans'][$sId]['tujuan_renstras'][$rtId]['es2s'][$rsId] = [
                    'nama' => $r['renstra_sasaran'] ?: '(Tanpa Sasaran ES.II)',
                    'csf' => $r['csf_es2'], 'indikators' => [], 'es3s' => [],
                ];
            }
            $risId = $r['indikator_id'];
            if ($risId) {
                $tree[$tId]['sasarans'][$sId]['tujuan_renstras'][$rtId]['es2s'][$rsId]['indikators'][$risId] = $r['indikator_sasaran'];
            }
            $es3Id = $r['es3_id'];
            if ($es3Id) {
                if (!isset($tree[$tId]['sasarans'][$sId]['tujuan_renstras'][$rtId]['es2s'][$rsId]['es3s'][$es3Id])) {
                    $tree[$tId]['sasarans'][$sId]['tujuan_renstras'][$rtId]['es2s'][$rsId]['es3s'][$es3Id] = [
                        'nama' => $r['es3_sasaran'], 'csf' => $r['csf_es3'], 'indikators' => [], 'es4s' => [],
                    ];
                }
                $es3IndId = $r['es3_indikator_id'];
                if ($es3IndId) {
                    $tree[$tId]['sasarans'][$sId]['tujuan_renstras'][$rtId]['es2s'][$rsId]['es3s'][$es3Id]['indikators'][$es3IndId] = $r['es3_indikator'];
                }
                $es4Id = $r['es4_id'];
                if ($es4Id) {
                    if (!isset($tree[$tId]['sasarans'][$sId]['tujuan_renstras'][$rtId]['es2s'][$rsId]['es3s'][$es3Id]['es4s'][$es4Id])) {
                        $tree[$tId]['sasarans'][$sId]['tujuan_renstras'][$rtId]['es2s'][$rsId]['es3s'][$es3Id]['es4s'][$es4Id] = [
                            'nama' => $r['es4_sasaran'], 'csf' => $r['csf_es4'], 'indikators' => [],
                        ];
                    }
                    $es4IndId = $r['es4_indikator_id'];
                    if ($es4IndId) {
                        $tree[$tId]['sasarans'][$sId]['tujuan_renstras'][$rtId]['es2s'][$rsId]['es3s'][$es3Id]['es4s'][$es4Id]['indikators'][$es4IndId] = $r['es4_indikator'];
                    }
                }
            }
        }
        return $tree;
    }


    private function buildRowspanMeta($rows)
    {
        $meta = [
            'tujuan' => [],
            'sasaran' => [],
            'indikator' => [],
            'opd' => []
        ];

        foreach ($rows as $r) {

            $meta['tujuan'][$r['tujuan_id']] =
                ($meta['tujuan'][$r['tujuan_id']] ?? 0) + 1;

            $meta['sasaran'][$r['sasaran_id']] =
                ($meta['sasaran'][$r['sasaran_id']] ?? 0) + 1;

            $meta['indikator'][$r['indikator_id']] =
                ($meta['indikator'][$r['indikator_id']] ?? 0) + 1;

            // ====================
            // GROUP OPD PER INDIKATOR
            // ====================
            $key = $r['indikator_id'] . '-' . $r['nama_opd'];

            $meta['opd'][$key] =
                ($meta['opd'][$key] ?? 0) + 1;
        }

        return $meta;
    }
    private function buildFirstShowMeta($rows)
    {
        $shown = [
            'tujuan' => [],
            'sasaran' => [],
            'indikator' => [],
            'opd' => []
        ];

        foreach ($rows as $index => $r) {

            if (!isset($shown['tujuan'][$r['tujuan_id']])) {
                $shown['tujuan'][$r['tujuan_id']] = $index;
            }

            if (!isset($shown['sasaran'][$r['sasaran_id']])) {
                $shown['sasaran'][$r['sasaran_id']] = $index;
            }

            if (!isset($shown['indikator'][$r['indikator_id']])) {
                $shown['indikator'][$r['indikator_id']] = $index;
            }

            $key = $r['indikator_id'] . '-' . $r['nama_opd'];

            if (!isset($shown['opd'][$key])) {
                $shown['opd'][$key] = $index;
            }
        }

        return $shown;
    }

    public function getPkProgramByOpd()
    {
        $opdId = $this->request->getGet('opd_id');
        $tahun = $this->request->getGet('tahun');

        if (!$opdId || !$tahun) {
            return $this->response->setJSON([]);
        }

        $data = $this->cascadingModel->getPkProgramByOpd($opdId, $tahun);

        return $this->response->setJSON($data);
    }

    public function tambah($indikatorId = null)
    {
        if (!$indikatorId) {
            return redirect()->back()->with('error', 'Indikator tidak ditemukan');
        }

        // ambil indikator rpjmd
        $indikator = $this->db->table('rpjmd_indikator_sasaran')
            ->where('id', $indikatorId)
            ->get()
            ->getRowArray();

        if (!$indikator) {
            return redirect()->back()->with('error', 'Indikator tidak ditemukan');
        }

        $opdList = $this->db->table('opd')
            ->whereNotIn('id', \App\Models\OpdModel::EXCLUDED_OPD_IDS)
            ->orderBy('nama_opd', 'ASC')
            ->get()
            ->getResultArray();

        // ambil periode dari GET
        $periode = $this->request->getGet('periode');


        [$start, $end] = explode('-', $periode);

        $tahun = $this->request->getGet('tahun');

        if (!$tahun) {

            // cari tahun mapping existing
            $existYear = $this->db->table('rpjmd_cascading')
                ->select('tahun')
                ->where('indikator_sasaran_id', $indikatorId)
                ->orderBy('tahun', 'DESC')
                ->get()
                ->getRow();

            if ($existYear) {
                $tahun = $existYear->tahun;
            } else {
                $tahun = date('Y');
            }
        }
        if ($periode && strpos($periode, '-') !== false) {
            [$start, $end] = explode('-', $periode);
            $years = range((int) $start, (int) $end);
        } else {
            $years = [date('Y')];
        }

        // ===========================
        // AMBIL MAPPING LAMA
        // ===========================
        $existing = $this->cascadingModel
            ->getExistingMapping($indikatorId, $tahun);

        // ===========================
        // GROUP BY OPD
        // ===========================
        $grouped = [];

        foreach ($existing as $row) {

            if (!isset($grouped[$row['opd_id']])) {
                $grouped[$row['opd_id']] = [];
            }

            $grouped[$row['opd_id']][] = $row['pk_program_id'];
        }

        return view('adminKabupaten/cascading/tambah_cascading', [
            'indikator' => $indikator,
            'opd_list' => $opdList,
            'existing_mapping' => $grouped,
            'years' => $years,
            'periode' => $periode,
            'selected_tahun' => $tahun
        ]);
    }

    public function save()
    {
        $indikatorId = $this->request->getPost('indikator_id');
        $tahun = $this->request->getPost('tahun');
        $opdData = $this->request->getPost('opd');

        // dd(request()->getPost());
        if (!$indikatorId || !$tahun || empty($opdData)) {
            return redirect()->back()
                ->with('error', 'Data tidak lengkap');
        }

        $insertBatch = [];

        foreach ($opdData as $opd) {

            $opdId = $opd['id'] ?? null;
            $programs = $opd['program'] ?? [];

            if (!$opdId || empty($programs))
                continue;

            foreach ($programs as $programId) {


                if (!$programId)
                    continue;

                $insertBatch[] = [
                    'indikator_sasaran_id' => $indikatorId,
                    'opd_id' => $opdId,
                    'pk_program_id' => $programId,
                    'tahun' => $tahun
                ];
            }
        }

        // ==============================
        // 🔥 EDIT MODE FIX
        // ==============================
        // HAPUS MAPPING LAMA DULU

        $this->db->transStart();

        $this->cascadingModel
            ->deleteByIndikatorAndYear($indikatorId, $tahun);
        
        if (!empty($insertBatch)) {
            $this->cascadingModel
                ->saveBatchMapping($insertBatch);
        }

        $this->db->transComplete();


        // periode dikirim via hidden field form (POST); fallback ke query string
        $periode = $this->request->getPost('periode') ?: $this->request->getGet('periode');

        return redirect()->to(
            base_url('adminkab/cascading?periode=' . $periode)
        )->with('success', 'Mapping Cascading berhasil disimpan');
    }

    public function excel()
    {
        $mode = $this->request->getGet('mode') ?: 'kabupaten';
        if (!in_array($mode, self::MODES, true)) {
            $mode = 'kabupaten';
        }
        $periode = $this->request->getGet('periode');
        $opdId   = $this->request->getGet('opd_id');
        if (!$periode) {
            return redirect()->back()->with('error', 'Periode wajib dipilih');
        }
        [$start, $end] = array_map('intval', explode('-', $periode));
        $years = range($start, $end);

        helper('cascading_excel');
        if ($mode === 'opd') {
            if (!$opdId) {
                return redirect()->back()->with('error', 'Perangkat Daerah wajib dipilih');
            }
            $rows = $this->cascadingModel->getCascadingMatrixByOpd($opdId, $start, $end);
            $o    = $this->db->table('opd')->select('nama_opd')->where('id', $opdId)->get()->getRowArray();
            cascading_opd_excel($rows, $periode, $o['nama_opd'] ?? '');
        } elseif ($mode === 'keseluruhan') {
            $rows = $this->cascadingModel->getKeseluruhanMatrix($start, $end);
            cascading_keseluruhan_excel($rows, $periode);
        } else {
            $rows = $this->cascadingModel->getMatrix($start, $end);
            cascading_kab_excel($rows, $years, $periode);
        }
    }

    public function cetak()
    {
        ob_clean(); // 🔥 BUANG OUTPUT SEBELUMNYA
        ob_start();

        $mode = $this->request->getGet('mode') ?: 'kabupaten';
        if (!in_array($mode, self::MODES, true)) {
            $mode = 'kabupaten';
        }
        $periode = $this->request->getGet('periode');
        $opdId   = $this->request->getGet('opd_id');

        if (!$periode) {
            return redirect()->back()
                ->with('error', 'Periode wajib dipilih');
        }

        [$start, $end] = array_map('intval', explode('-', $periode));
        $years = range($start, $end);

        if ($mode === 'opd') {
            if (!$opdId) {
                return redirect()->back()->with('error', 'Perangkat Daerah wajib dipilih');
            }
            $rows      = $this->cascadingModel->getCascadingMatrixByOpd($opdId, $start, $end);
            $this->preprocessEmptyIds($rows);
            $rowspan   = $this->opdRowspanMeta($rows);
            $firstShow = $this->opdFirstShowMeta($rows);
            $o         = $this->db->table('opd')->select('nama_opd')->where('id', $opdId)->get()->getRowArray();
            $namaOpd   = $o['nama_opd'] ?? '';

            $html = view('adminOpd/cascading/cascading_cetak', [
                'rows' => $rows, 'rowspan' => $rowspan, 'firstShow' => $firstShow,
                'tahun_mulai' => $start, 'tahun_akhir' => $end, 'periode' => $periode,
                'nama_opd' => $namaOpd, 'showKode' => true,
            ]);
            $filename = 'Cascading-OPD-' . preg_replace('/[^A-Za-z0-9]+/', '-', $namaOpd) . '-' . $periode . '.pdf';
        } elseif ($mode === 'keseluruhan') {
            $rows      = $this->cascadingModel->getKeseluruhanMatrix($start, $end);
            $this->preprocessEmptyIds($rows);
            $rowspan   = $this->keseluruhanRowspanMeta($rows);
            $firstShow = $this->keseluruhanFirstShowMeta($rows);

            $html = view('adminKabupaten/cascading/cascading_cetak_keseluruhan', [
                'rows' => $rows, 'rowspan' => $rowspan, 'firstShow' => $firstShow,
                'tahun_mulai' => $start, 'tahun_akhir' => $end,
            ]);
            $filename = 'Cascading-Keseluruhan-' . $periode . '.pdf';
        } else { // kabupaten
            // Matriks lengkap RPJMD: Visi + Misi -> Tujuan -> Sasaran -> Indikator
            // -> Program -> Perangkat Daerah (getMatrix), + target per tahun & kondisi akhir.
            $rows = $this->cascadingModel->getMatrix($start, $end);

            $html = view('adminKabupaten/cascading/cascading_cetak_kabupaten', [
                'rows'        => $rows,
                'visi'        => $this->ambilVisi($start, $end),
                'years'       => $years,
                'tahun_mulai' => $start,
                'tahun_akhir' => $end,
            ]);
            $filename = 'Cascading-Kabupaten-' . $periode . '.pdf';
        }

        $mpdf = new \Mpdf\Mpdf([
            'mode'              => 'utf-8',
            'format'            => 'A3-L', // A3 landscape: 14 kolom cascading hanya terbaca di A3 (A4 -> disusutkan mpdf jd kecil)
            'margin_left'       => 7,
            'margin_right'      => 7,
            'margin_top'        => 12,
            'margin_bottom'     => 10,
            'margin_header'     => 0,
            'margin_footer'     => 0,
            'tempDir'           => sys_get_temp_dir()
        ]);
        helper('setting');
        $mpdf->shrink_tables_to_fit = false; // JANGAN susutkan tabel -> font tetap terbaca (bukan mengecil paksa)
        $mpdf->SetHTMLFooter(pdf_footer_aksara());
        pdf_watermark_aksara($mpdf); // watermark AKSARA halus di latar
        $mpdf->SetDisplayMode('fullpage');
        $mpdf->WriteHTML($html);

        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . $filename . '"');

        $mpdf->Output();
        exit;
    }

    public function saveCsf()
    {
        $sasaranId = $this->request->getPost('sasaran_id');
        $csf = $this->request->getPost('csf');

        if (!$sasaranId) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Sasaran ID tidak ditemukan'
            ]);
        }

        $this->db->table('rpjmd_sasaran')
            ->where('id', $sasaranId)
            ->update(['csf' => $csf]);

        return $this->response->setJSON([
            'status' => 'success',
            'message' => 'CSF berhasil disimpan'
        ]);
    }

    public function cetakPohon()
    {
        $mode = $this->request->getGet('mode') ?: 'kabupaten';
        if (!in_array($mode, self::MODES, true)) {
            $mode = 'kabupaten';
        }
        $periode = $this->request->getGet('periode');
        $opdId   = $this->request->getGet('opd_id');

        if (!$periode) {
            return redirect()->back()
                ->with('error', 'Periode wajib dipilih');
        }

        [$start, $end] = array_map('intval', explode('-', $periode));

        if ($mode === 'opd') {
            if (!$opdId) {
                return redirect()->back()->with('error', 'Perangkat Daerah wajib dipilih');
            }
            $rows    = $this->cascadingModel->getCascadingMatrixByOpd($opdId, $start, $end);
            $tree    = $this->buildOpdTree($rows);
            $o       = $this->db->table('opd')->select('nama_opd')->where('id', $opdId)->get()->getRowArray();
            $namaOpd = $o['nama_opd'] ?? '';

            return view('adminOpd/cascading/pohon_kinerja_cetak', [
                'tree'        => $tree,
                'nama_opd'    => $namaOpd,
                'tahun_mulai' => $start,
                'tahun_akhir' => $end,
                'periode'     => $periode,
                'showCsf'     => false,
                'showKode'    => true,
            ]);
        }

        if ($mode === 'keseluruhan') {
            $tree = $this->cascadingModel->getKeseluruhanByOpd($start, $end);
            return view('adminKabupaten/cascading/pohon_kinerja_cetak_keseluruhan', [
                'tree'        => $tree,
                'visi'        => $this->ambilVisi($start, $end),
                'tahun_mulai' => $start,
                'tahun_akhir' => $end,
                'periode'     => $periode,
            ]);
        }

        // kabupaten — pohon dipangkas sampai indikator (tanpa cabang OPD/Program)
        $tree = $this->cascadingModel->getPohonKinerja($start, $end);
        return view('adminKabupaten/cascading/pohon_kinerja_cetak', [
            'tree'        => $tree,
            'visi'        => $this->ambilVisi($start, $end),
            'tahun_mulai' => $start,
            'tahun_akhir' => $end,
            'periode'     => $periode,
            'showOpd'     => false,
        ]);
    }

}
