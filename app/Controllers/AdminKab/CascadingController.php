<?php

namespace App\Controllers\AdminKab;

use App\Controllers\BaseController;
use App\Controllers\Concerns\CascadingOpdMetaTrait;
use App\Models\CascadingModel;

class CascadingController extends BaseController
{
    /**
     * Rowspan/firstShow/pohon matriks cascading Perangkat Daerah — implementasi
     * BERSAMA dengan AdminOpd\CascadingController. Sebelumnya controller ini
     * punya salinan sendiri yang belum mengenal jenjang PELAKSANA, itulah sebab
     * Pelaksana tidak pernah tampil di area admin_kab.
     */
    use CascadingOpdMetaTrait;

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

    // ================= META: MODE KESELURUHAN (RPJMD → Renstra OPD → Eselon III/IV → Pelaksana)
    // Kunci komposit agar baris tanpa renstra/cascade (id null) tidak saling
    // tumpang tindih. Kunci yang sama dipakai ulang di view (layar & cetak).
    public static function ksOpdKey($r): string  { return ($r['sasaran_id'] ?? 'x') . '|' . ($r['opd_id'] ?? 'x'); }
    public static function ksRtKey($r): string   { return self::ksOpdKey($r) . '|' . ($r['renstra_tujuan_id'] ?? 'x'); }
    public static function ksRsKey($r): string   { return self::ksRtKey($r) . '|' . ($r['renstra_sasaran_id'] ?? 'x'); }
    public static function ksRisKey($r): string  { return self::ksRsKey($r) . '|' . ($r['renstra_indikator_id'] ?? 'x'); }
    public static function ksEs3Key($r): string  { return self::ksRisKey($r) . '|e3:' . ($r['es3_id'] ?? 'x'); }
    public static function ksI3Key($r): string   { return self::ksEs3Key($r) . '|i3:' . ($r['es3_indikator_id'] ?? 'x'); }
    public static function ksEs4Key($r): string  { return self::ksI3Key($r) . '|e4:' . ($r['es4_id'] ?? 'x'); }
    public static function ksI4Key($r): string   { return self::ksEs4Key($r) . '|i4:' . ($r['es4_indikator_id'] ?? 'x'); }
    public static function ksPelKey($r): string  { return self::ksI4Key($r) . '|p:' . ($r['pelaksana_id'] ?? 'x'); }

    /** Kunci meta mode keseluruhan (kontrak bersama controller & view). */
    private const KS_META_KEYS = [
        'tujuan', 'sasaran', 'opd', 'renstra_tujuan', 'renstra_sasaran',
        'renstra_indikator', 'es3', 'es3_indikator', 'es4', 'es4_indikator', 'pelaksana',
    ];

    /** @return array<string, callable> kunci meta => pembentuk kunci baris */
    private function keseluruhanKeyMakers(): array
    {
        return [
            'tujuan'            => static fn($r) => $r['tujuan_id'],
            'sasaran'           => static fn($r) => $r['sasaran_id'],
            'opd'               => static fn($r) => self::ksOpdKey($r),
            'renstra_tujuan'    => static fn($r) => self::ksRtKey($r),
            'renstra_sasaran'   => static fn($r) => self::ksRsKey($r),
            'renstra_indikator' => static fn($r) => self::ksRisKey($r),
            // Jenjang cascade internal OPD dihitung HANYA bila barisnya ada,
            // supaya baris tanpa cascade tidak ikut memperbesar rowspan.
            'es3'               => static fn($r) => empty($r['es3_id']) ? null : self::ksEs3Key($r),
            'es3_indikator'     => static fn($r) => empty($r['es3_id']) ? null : self::ksI3Key($r),
            'es4'               => static fn($r) => empty($r['es4_id']) ? null : self::ksEs4Key($r),
            'es4_indikator'     => static fn($r) => empty($r['es4_id']) ? null : self::ksI4Key($r),
            'pelaksana'         => static fn($r) => empty($r['pelaksana_id']) ? null : self::ksPelKey($r),
        ];
    }

    private function keseluruhanRowspanMeta($rows): array
    {
        $m = array_fill_keys(self::KS_META_KEYS, []);
        foreach ($this->keseluruhanKeyMakers() as $nama => $buat) {
            foreach ($rows as $r) {
                $k = $buat($r);
                if ($k === null) {
                    continue;
                }
                $m[$nama][$k] = ($m[$nama][$k] ?? 0) + 1;
            }
        }
        return $m;
    }

    private function keseluruhanFirstShowMeta($rows): array
    {
        $s = array_fill_keys(self::KS_META_KEYS, []);
        foreach ($this->keseluruhanKeyMakers() as $nama => $buat) {
            foreach ($rows as $i => $r) {
                $k = $buat($r);
                if ($k === null || isset($s[$nama][$k])) {
                    continue;
                }
                $s[$nama][$k] = $i;
            }
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

    /* ================= META: MODE OPD (renstra lengkap) =================
       Implementasi tunggal ada di CascadingOpdMetaTrait supaya admin_kab,
       admin_opd, dan halaman publik memakai perhitungan yang sama —
       termasuk jenjang PELAKSANA (es4_indikator + pelaksana). */

    private function opdRowspanMeta($rows): array
    {
        return $this->cascOpdRowspanMeta($rows);
    }

    private function opdFirstShowMeta($rows): array
    {
        return $this->cascOpdFirstShowMeta($rows);
    }

    private function buildOpdTree($rows): array
    {
        return $this->cascOpdTree($rows);
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
