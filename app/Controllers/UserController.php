<?php

namespace App\Controllers;

use App\Controllers\Concerns\CascadingOpdMetaTrait;

class UserController extends BaseController
{
    /**
     * Meta & pohon matriks cascading Perangkat Daerah — implementasi bersama
     * dengan area admin. Halaman publik ikut menampilkan jenjang PELAKSANA
     * tanpa perlu salinan logika sendiri.
     */
    use CascadingOpdMetaTrait;

    public function index()
    {
        // session()->destroy(); // hapus semua session
        // dd(session()->get('role'));
        return view('dashboard');
    }

    public function rpjmd()
    {
        $rpjmdModel = new \App\Models\RpjmdModel();

        // Ambil data RPJMD yang sudah selesai dengan struktur lengkap
        $completedRpjmd = $rpjmdModel->getCompletedRpjmdStructure();

        // Jika tidak ada data selesai, tampilkan pesan
        if (empty($completedRpjmd)) {
            return view('user/rpjmd', [
                'rpjmdGrouped' => [],
                'message' => 'Belum ada data RPJMD yang telah selesai.'
            ]);
        }

        // Group data by period (tahun_mulai - tahun_akhir) seperti di admin kabupaten
        $groupedData = [];
        foreach ($completedRpjmd as $misi) {
            $periodKey = $misi['tahun_mulai'] . '-' . $misi['tahun_akhir'];

            if (!isset($groupedData[$periodKey])) {
                $groupedData[$periodKey] = [
                    'period' => $periodKey,
                    'tahun_mulai' => $misi['tahun_mulai'],
                    'tahun_akhir' => $misi['tahun_akhir'],
                    'years' => range($misi['tahun_mulai'], $misi['tahun_akhir']),
                    'misi_data' => []
                ];
            }

            $groupedData[$periodKey]['misi_data'][] = $misi;
        }

        // Sort periods by tahun_mulai
        ksort($groupedData);

        return view('user/rpjmd', [
            'rpjmdGrouped' => $groupedData
        ]);
    }

    public function rkpd()
    {
        $rktModel = new \App\Models\RktModel();

        $tahun = $this->request->getGet('tahun') ?? 'all';
        $opd_id = $this->request->getGet('opd_id') ?? 'all';
        $rktDataRaw = $rktModel->getIndicatorsForRkpd($opd_id, $tahun, 'selesai');

        $available_years = $rktModel->getAvailableYears();
        sort($available_years);

        $db = \Config\Database::connect();
        $opdList = $db->table('opd')->whereNotIn('id', \App\Models\OpdModel::EXCLUDED_OPD_IDS)->orderBy('nama_opd', 'ASC')->get()->getResultArray();

        return view('user/rkpd', [
            'rkpd_data' => $rktDataRaw,
            'available_years' => $available_years,
            'selected_tahun' => $tahun,
            'selected_opd' => $opd_id,
            'opdList' => $opdList
        ]);
    }

    public function lakip_kabupaten()
    {
        $lakipModel = new \App\Models\LakipModel();

        $available_years = $lakipModel->getAvailableYears();
        $tahun = $this->request->getGet('tahun') ?? (!empty($available_years) ? end($available_years) : date('Y'));

        $data = $lakipModel->getLakipByMode('kabupaten', $tahun, 'selesai');

        $lakipKabupatenData = [];
        foreach ($data['rows'] as $row) {
            $target_id = $row['target_id'];
            $lakip = $data['lakipMap'][$target_id] ?? null;

            if ($lakip) {
                $lakipKabupatenData[] = [
                    'sasaran' => $row['sasaran'],
                    'indikator' => $row['indikator_sasaran'],
                    'capaian_sebelumnya' => $lakip['capaian_lalu'] ?? '-',
                    'target_tahun_ini' => $row['target_tahun_ini'],
                    'capaian_tahun_ini' => $lakip['capaian_tahun_ini'] ?? '-'
                ];
            }
        }

        return view('user/lakip_kabupaten', [
            'lakipKabupatenData' => $lakipKabupatenData,
            'available_years' => $available_years,
            'selected_tahun' => $tahun
        ]);
    }

    public function pk_bupati()
    {
        $db = \Config\Database::connect();
        $model = new \App\Models\UserPublicModel();

        // Daftar tahun tersedia
        $availableYears = $db->table('pk')
            ->select('tahun')
            ->where('jenis', 'bupati')
            ->groupBy('tahun')
            ->orderBy('tahun', 'DESC')
            ->get()
            ->getResultArray();
        $availableYears = array_column($availableYears, 'tahun');

        $tahun = $this->request->getGet('tahun') ?? (!empty($availableYears) ? $availableYears[0] : null);

        $sasaranList = $model->getPkBupatiData($tahun);

        return view('user/pk_bupati', [
            'sasaranList' => $sasaranList,
            'availableYears' => $availableYears,
            'tahun' => $tahun,
        ]);
    }
    public function renstra()
    {
        $db = \Config\Database::connect();
        $model = new \App\Models\UserPublicModel();

        $opd_id = $this->request->getGet('opd_id') ?? 'all';
        $data = $model->getRenstraData($opd_id);

        $opdList = $db->table('opd')->whereNotIn('id', \App\Models\OpdModel::EXCLUDED_OPD_IDS)->orderBy('nama_opd', 'ASC')->get()->getResultArray();

        return view('user/renstra', [
            'tahunList' => $data['tahunList'],
            'opdList' => $opdList,
            'selected_opd' => $opd_id,
            'renstraData' => $data['renstraData']
        ]);
    }

    public function rkt()
    {
        $rktModel = new \App\Models\RktModel();

        $tahun = $this->request->getGet('tahun') ?? 'all';
        $opd_id = $this->request->getGet('opd_id') ?? 'all';

        $rktDataRaw = $rktModel->getIndicatorsForRkpd($opd_id, $tahun, 'selesai');

        $rktData = [];
        $seenIndikator = [];
        foreach ($rktDataRaw as $row) {
            $key = $row['opd_id'] . '-' . $row['indikator_id'];
            if (!isset($seenIndikator[$key])) {
                $rktData[] = [
                    'opd' => $row['nama_opd'],
                    'sasaran' => $row['sasaran'],
                    'indikator' => $row['indikator_sasaran'],
                    'satuan' => $row['satuan'],
                    'target' => $row['target_renstra']
                ];
                $seenIndikator[$key] = true;
            }
        }

        $available_years = $rktModel->getAvailableYears();
        sort($available_years);

        $db = \Config\Database::connect();
        $opdList = $db->table('opd')->whereNotIn('id', \App\Models\OpdModel::EXCLUDED_OPD_IDS)->orderBy('nama_opd', 'ASC')->get()->getResultArray();

        return view('user/rkt', [
            'rktData' => $rktData,
            'available_years' => $available_years,
            'selected_tahun' => $tahun,
            'selected_opd' => $opd_id,
            'opdList' => $opdList
        ]);
    }


    public function lakip_opd()
    {
        $lakipModel = new \App\Models\LakipModel();

        $available_years = $lakipModel->getAvailableYears();
        $tahun = $this->request->getGet('tahun') ?? (!empty($available_years) ? end($available_years) : date('Y'));
        $opd_id = $this->request->getGet('opd_id') ?? 'all';
        $opdIdFilter = ($opd_id === 'all') ? null : (int) $opd_id;

        $data = $lakipModel->getLakipByMode('opd', $tahun, 'selesai', $opdIdFilter);

        $lakipOpdData = [];
        foreach ($data['rows'] as $row) {
            $target_id = $row['target_id'];
            $lakip = $data['lakipMap'][$target_id] ?? null;

            if ($lakip) {
                $lakipOpdData[] = [
                    'opd' => $row['nama_opd'],
                    'sasaran' => $row['sasaran'],
                    'indikator' => $row['indikator_sasaran'],
                    'capaian_sebelumnya' => $lakip['capaian_lalu'] ?? '-',
                    'target_tahun_ini' => $row['target_tahun_ini'],
                    'capaian_tahun_ini' => $lakip['capaian_tahun_ini'] ?? '-'
                ];
            }
        }

        $db = \Config\Database::connect();
        $opdList = $db->table('opd')->whereNotIn('id', \App\Models\OpdModel::EXCLUDED_OPD_IDS)->orderBy('nama_opd', 'ASC')->get()->getResultArray();

        return view('user/lakip_opd', [
            'lakipOpdData' => $lakipOpdData,
            'available_years' => $available_years,
            'selected_tahun' => $tahun,
            'selected_opd' => $opd_id,
            'opdList' => $opdList
        ]);
    }

    public function iku_opd()
    {
        $db = \Config\Database::connect();
        $model = new \App\Models\UserPublicModel();

        $opd_id = $this->request->getGet('opd_id') ?? 'all';
        $data = $model->getIkuOpdData($opd_id);

        $opdList = $db->table('opd')->whereNotIn('id', \App\Models\OpdModel::EXCLUDED_OPD_IDS)->orderBy('nama_opd', 'ASC')->get()->getResultArray();

        return view('user/iku_opd', [
            'ikuOpdData' => $data['ikuOpdData'],
            'tahunList' => $data['tahunList'],
            'selected_opd' => $opd_id,
            'opdList' => $opdList
        ]);
    }

    // ============================================
    // CASCADING KABUPATEN
    // ============================================

    public function cascading_kabupaten()
    {
        $cascadingModel = new \App\Models\CascadingModel();
        $db = \Config\Database::connect();

        $periode = $this->request->getGet('periode');

        $periodeList = $db->table('rpjmd_misi')
            ->select('tahun_mulai, tahun_akhir')
            ->groupBy(['tahun_mulai', 'tahun_akhir'])
            ->orderBy('tahun_mulai', 'ASC')
            ->get()
            ->getResultArray();

        $rows = [];
        $rowspan = [];
        $firstShow = [];
        $years = [];
        $tahunMulai = null;
        $tahunAkhir = null;

        if ($periode) {
            [$start, $end] = explode('-', $periode);
            $start = (int) $start;
            $end = (int) $end;
            $tahunMulai = $start;
            $tahunAkhir = $end;
            $years = range($start, $end);
            $rows = $cascadingModel->getMatrix($start, $end);
            $rowspan = $this->buildCascadingKabRowspan($rows);
            $firstShow = $this->buildCascadingKabFirstShow($rows);
        }

        $data = [
            'rows' => $rows,
            'rowspan' => $rowspan,
            'firstShow' => $firstShow,
            'periode_master' => $periodeList,
            'years' => $years,
            'tahun_mulai' => $tahunMulai,
            'tahun_akhir' => $tahunAkhir,
            'filters' => ['periode' => $periode]
        ];

        return view('user/cascading_kabupaten', $data);
    }

    // Halaman publik terpisah: Pohon Kinerja Kabupaten (tampilan pohon saja)
    public function pohon_kinerja_kabupaten()
    {
        $cascadingModel = new \App\Models\CascadingModel();
        $db = \Config\Database::connect();

        $periode = $this->request->getGet('periode');

        $periodeList = $db->table('rpjmd_misi')
            ->select('tahun_mulai, tahun_akhir')
            ->groupBy(['tahun_mulai', 'tahun_akhir'])
            ->orderBy('tahun_mulai', 'ASC')
            ->get()
            ->getResultArray();

        $tree = [];
        $visi = '';
        $tahunMulai = null;
        $tahunAkhir = null;

        if ($periode) {
            [$start, $end] = explode('-', $periode);
            $start = (int) $start;
            $end = (int) $end;
            $tahunMulai = $start;
            $tahunAkhir = $end;

            $tree = $cascadingModel->getPohonKinerja($start, $end);
            $firstMisi = $db->table('rpjmd_misi m')
                ->select('rv.visi')
                ->join('rpjmd_visi rv', 'rv.id = m.rpjmd_visi_id', 'left')
                ->where('m.tahun_mulai', $start)
                ->where('m.tahun_akhir', $end)
                ->orderBy('m.id', 'ASC')
                ->get()->getRowArray();
            $visi = $firstMisi['visi'] ?? '';
        }

        $data = [
            'periode_master' => $periodeList,
            'tree' => $tree,
            'visi' => $visi,
            'tahun_mulai' => $tahunMulai,
            'tahun_akhir' => $tahunAkhir,
            'filters' => ['periode' => $periode]
        ];

        return view('user/pohon_kinerja_kabupaten', $data);
    }

    public function cascading_kabupaten_excel()
    {
        $periode = $this->request->getGet('periode');
        if (!$periode) {
            return redirect()->back();
        }
        $cascadingModel = new \App\Models\CascadingModel();
        [$start, $end] = explode('-', $periode);
        $rows  = $cascadingModel->getMatrix((int) $start, (int) $end);
        $years = range((int) $start, (int) $end);

        helper('cascading_excel');
        cascading_kab_excel($rows, $years, $periode);
    }

    public function cascading_opd_excel()
    {
        $periode = $this->request->getGet('periode');
        $opd_id  = $this->request->getGet('opd_id');
        if (!$periode || !$opd_id) {
            return redirect()->back();
        }
        $cascadingModel = new \App\Models\CascadingModel();
        $db = \Config\Database::connect();
        [$start, $end] = explode('-', $periode);
        $rows = $cascadingModel->getCascadingMatrixByOpd($opd_id, (int) $start, (int) $end);
        $o = $db->table('opd')->select('nama_opd')->where('id', $opd_id)->get()->getRowArray();

        helper('cascading_excel');
        cascading_opd_excel($rows, $periode, $o['nama_opd'] ?? '');
    }

    public function cascading_kabupaten_cetak()
    {
        ob_clean();
        ob_start();
        $periode = $this->request->getGet('periode');
        if (!$periode)
            return redirect()->back();

        $cascadingModel = new \App\Models\CascadingModel();
        [$start, $end] = explode('-', $periode);
        $rows = $cascadingModel->getMatrix((int) $start, (int) $end);
        $rowspan = $this->buildCascadingKabRowspan($rows);
        $firstShow = $this->buildCascadingKabFirstShow($rows);
        $years = range((int) $start, (int) $end);

        $html = view('adminKabupaten/cascading/cascading_cetak', compact('rows', 'rowspan', 'firstShow', 'years'));

        $mpdf = new \Mpdf\Mpdf([
            'mode'          => 'utf-8',
            'format'        => 'A4-L',
            'margin_left'   => 10,
            'margin_right'  => 10,
            'margin_top'    => 12,
            'margin_bottom' => 10,
            'margin_header' => 0,
            'margin_footer' => 0,
            'tempDir'       => sys_get_temp_dir()
        ]);
        helper('setting');
        $mpdf->SetHTMLFooter(pdf_footer_aksara());
        pdf_watermark_aksara($mpdf); // watermark AKSARA halus di latar
        $mpdf->SetDisplayMode('fullpage');
        $mpdf->WriteHTML($html);
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="Cascading-Kabupaten-' . $periode . '.pdf"');
        $mpdf->Output();
        exit;
    }

    public function cascading_kabupaten_pohon()
    {
        $periode = $this->request->getGet('periode');
        if (!$periode)
            return redirect()->back();

        $cascadingModel = new \App\Models\CascadingModel();
        $db = \Config\Database::connect();

        [$start, $end] = explode('-', $periode);
        $start = (int) $start;
        $end   = (int) $end;

        $tree = $cascadingModel->getPohonKinerja($start, $end);

        // Ambil visi via JOIN rpjmd_visi
        $firstMisi = $db->table('rpjmd_misi m')
            ->select('rv.visi')
            ->join('rpjmd_visi rv', 'rv.id = m.rpjmd_visi_id', 'left')
            ->where('m.tahun_mulai', $start)
            ->where('m.tahun_akhir', $end)
            ->orderBy('m.id', 'ASC')
            ->get()->getRowArray();
        $visi = $firstMisi['visi'] ?? '';

        return view('adminKabupaten/cascading/pohon_kinerja_cetak', [
            'tree'        => $tree,
            'visi'        => $visi,
            'tahun_mulai' => $start,
            'tahun_akhir' => $end,
            'periode'     => $periode
        ]);
    }

    private function buildCascadingKabRowspan($rows)
    {
        $meta = ['tujuan' => [], 'sasaran' => [], 'indikator' => [], 'opd' => []];
        foreach ($rows as $r) {
            $meta['tujuan'][$r['tujuan_id']] = ($meta['tujuan'][$r['tujuan_id']] ?? 0) + 1;
            $meta['sasaran'][$r['sasaran_id']] = ($meta['sasaran'][$r['sasaran_id']] ?? 0) + 1;
            $meta['indikator'][$r['indikator_id']] = ($meta['indikator'][$r['indikator_id']] ?? 0) + 1;
            $key = $r['indikator_id'] . '-' . $r['nama_opd'];
            $meta['opd'][$key] = ($meta['opd'][$key] ?? 0) + 1;
        }
        return $meta;
    }

    private function buildCascadingKabFirstShow($rows)
    {
        $shown = ['tujuan' => [], 'sasaran' => [], 'indikator' => [], 'opd' => []];
        foreach ($rows as $index => $r) {
            if (!isset($shown['tujuan'][$r['tujuan_id']]))
                $shown['tujuan'][$r['tujuan_id']] = $index;
            if (!isset($shown['sasaran'][$r['sasaran_id']]))
                $shown['sasaran'][$r['sasaran_id']] = $index;
            if (!isset($shown['indikator'][$r['indikator_id']]))
                $shown['indikator'][$r['indikator_id']] = $index;
            $key = $r['indikator_id'] . '-' . $r['nama_opd'];
            if (!isset($shown['opd'][$key]))
                $shown['opd'][$key] = $index;
        }
        return $shown;
    }

    // ============================================
    // CASCADING OPD
    // ============================================

    public function cascading_opd()
    {
        $cascadingModel = new \App\Models\CascadingModel();
        $db = \Config\Database::connect();

        $periode = $this->request->getGet('periode');
        $opd_id = $this->request->getGet('opd_id');

        $periodeList = $db->table('rpjmd_misi')
            ->select('tahun_mulai, tahun_akhir')
            ->groupBy(['tahun_mulai', 'tahun_akhir'])
            ->orderBy('tahun_mulai', 'ASC')
            ->get()
            ->getResultArray();

        $opdList = $db->table('opd')->whereNotIn('id', \App\Models\OpdModel::EXCLUDED_OPD_IDS)->orderBy('nama_opd', 'ASC')->get()->getResultArray();

        $rows = [];
        $years = [];
        $rowspan = [];
        $firstShow = [];

        if ($periode && $opd_id) {
            [$start, $end] = explode('-', $periode);
            $start = (int) $start;
            $end = (int) $end;
            $years = range($start, $end);

            $rows = $cascadingModel->getCascadingMatrixByOpd($opd_id, $start, $end);
            $this->preprocessCascadingOpdEmptyIds($rows);
            $rowspan = $this->buildCascadingOpdRowspan($rows);
            $firstShow = $this->buildCascadingOpdFirstShow($rows);
        }

        $data = [
            'rows' => $rows,
            'rowspan' => $rowspan,
            'firstShow' => $firstShow,
            'periode_master' => $periodeList,
            'opdList' => $opdList,
            'years' => $years,
            'filters' => [
                'periode' => $periode,
                'opd_id' => $opd_id
            ]
        ];

        return view('user/cascading_opd', $data);
    }

    // Halaman publik terpisah: Pohon Kinerja Perangkat Daerah (tampilan pohon saja)
    public function pohon_kinerja_opd()
    {
        $cascadingModel = new \App\Models\CascadingModel();
        $db = \Config\Database::connect();

        $periode = $this->request->getGet('periode');
        $opd_id  = $this->request->getGet('opd_id');

        $periodeList = $db->table('rpjmd_misi')
            ->select('tahun_mulai, tahun_akhir')
            ->groupBy(['tahun_mulai', 'tahun_akhir'])
            ->orderBy('tahun_mulai', 'ASC')
            ->get()
            ->getResultArray();

        $opdList = $db->table('opd')->whereNotIn('id', \App\Models\OpdModel::EXCLUDED_OPD_IDS)->orderBy('nama_opd', 'ASC')->get()->getResultArray();

        $tree = [];

        if ($periode && $opd_id) {
            [$start, $end] = explode('-', $periode);
            $start = (int) $start;
            $end   = (int) $end;

            $rows = $cascadingModel->getCascadingMatrixByOpd($opd_id, $start, $end);
            $tree = $this->buildOpdTree($rows);
        }

        $data = [
            'periode_master' => $periodeList,
            'opdList' => $opdList,
            'tree' => $tree,
            'filters' => [
                'periode' => $periode,
                'opd_id' => $opd_id
            ]
        ];

        return view('user/pohon_kinerja_opd', $data);
    }

    public function cascading_opd_cetak()
    {
        ob_clean();
        ob_start();
        $periode = $this->request->getGet('periode');
        $opd_id = $this->request->getGet('opd_id');

        if (!$periode || !$opd_id)
            return redirect()->back();

        $cascadingModel = new \App\Models\CascadingModel();
        [$start, $end] = explode('-', $periode);
        $start = (int) $start;
        $end = (int) $end;

        $rows = $cascadingModel->getCascadingMatrixByOpd($opd_id, $start, $end);
        $this->preprocessCascadingOpdEmptyIds($rows);
        $rowspan = $this->buildCascadingOpdRowspan($rows);
        $firstShow = $this->buildCascadingOpdFirstShow($rows);

        $db = \Config\Database::connect();
        $opd = $db->table('opd')
            ->select('nama_opd')
            ->where('id', $opd_id)
            ->get()
            ->getRowArray();
        $namaOpd = $opd['nama_opd'] ?? '';

        $html = view('adminOpd/cascading/cascading_cetak', [
            'rows' => $rows,
            'rowspan' => $rowspan,
            'firstShow' => $firstShow,
            'tahun_mulai' => $start,
            'tahun_akhir' => $end,
            'periode' => $periode,
            'nama_opd' => $namaOpd,
        ]);

        $mpdf = new \Mpdf\Mpdf([
            'mode'          => 'utf-8',
            'format'        => 'A4-L',
            'margin_left'   => 10,
            'margin_right'  => 10,
            'margin_top'    => 12,
            'margin_bottom' => 10,
            'margin_header' => 0,
            'margin_footer' => 0,
            'tempDir'       => sys_get_temp_dir()
        ]);
        helper('setting');
        $mpdf->SetHTMLFooter(pdf_footer_aksara());
        pdf_watermark_aksara($mpdf); // watermark AKSARA halus di latar
        $mpdf->SetDisplayMode('fullpage');
        $mpdf->WriteHTML($html);
        header('Content-Type: application/pdf');
        $namaFile = $namaOpd !== '' ? preg_replace('/[^A-Za-z0-9]+/', '-', $namaOpd) . '-' : '';
        header('Content-Disposition: inline; filename="Cascading-OPD-' . $namaFile . $periode . '.pdf"');
        $mpdf->Output();
        exit;
    }

    public function cascading_opd_pohon()
    {
        $periode = $this->request->getGet('periode');
        $opd_id  = $this->request->getGet('opd_id');
        if (!$periode || !$opd_id)
            return redirect()->back();

        $cascadingModel = new \App\Models\CascadingModel();

        [$start, $end] = explode('-', $periode);
        $start = (int) $start;
        $end   = (int) $end;

        $rows = $cascadingModel->getCascadingMatrixByOpd($opd_id, $start, $end);
        $tree = $this->buildOpdTree($rows);

        return view('adminOpd/cascading/pohon_kinerja_cetak', [
            'tree'        => $tree,
            'tahun_mulai' => $start,
            'tahun_akhir' => $end,
            'periode'     => $periode
        ]);
    }

    /* Meta & pohon cascading OPD: implementasi tunggal di CascadingOpdMetaTrait. */

    private function preprocessCascadingOpdEmptyIds(array &$rows): void
    {
        $this->cascOpdPreprocessEmptyIds($rows);
    }

    private function buildCascadingOpdRowspan($rows)
    {
        return $this->cascOpdRowspanMeta($rows);
    }

    private function buildCascadingOpdFirstShow($rows)
    {
        return $this->cascOpdFirstShowMeta($rows);
    }

    private function buildOpdTree($rows)
    {
        return $this->cascOpdTree($rows);
    }

    private function getPkDataByJenis($jenis)
    {
        $db = \Config\Database::connect();
        $model = new \App\Models\UserPublicModel();
        
        $opd_id = $this->request->getGet('opd_id') ?? 'all';

        // Daftar tahun tersedia
        $availableYears = $db->table('pk')
            ->select('tahun')
            ->where('jenis', $jenis)
            ->groupBy('tahun')
            ->orderBy('tahun', 'DESC')
            ->get()
            ->getResultArray();
        $availableYears = array_column($availableYears, 'tahun');

        $tahun = $this->request->getGet('tahun') ?? (!empty($availableYears) ? $availableYears[0] : null);

        $pkData = $model->getPkDataByJenis($jenis, $tahun, $opd_id);

        $opdList = $db->table('opd')->whereNotIn('id', \App\Models\OpdModel::EXCLUDED_OPD_IDS)->orderBy('nama_opd', 'ASC')->get()->getResultArray();

        return [
            'pkData' => $pkData,
            'available_years' => $availableYears,
            'selected_tahun' => $tahun,
            'selected_opd' => $opd_id,
            'opdList' => $opdList
        ];
    }

    public function pk_pimpinan()
    {
        return view('user/pk_pimpinan', $this->getPkDataByJenis('jpt'));
    }

    public function pk_administrator()
    {
        return view('user/pk_administrator', $this->getPkDataByJenis('administrator'));
    }

    public function pk_pengawas()
    {
        return view('user/pk_pengawas', $this->getPkDataByJenis('pengawas'));
    }

    public function tentang_kami()
    {
        return view('user/tentang_kami');
    }

}
