<?php

namespace App\Controllers;

class UserController extends BaseController
{
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
        $opdList = $db->table('opd')->get()->getResultArray();

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

        // Query semua sasaran & indikator PK Bupati berdasarkan tahun
        $rawData = [];
        if ($tahun) {
            $rawData = $db->table('pk p')
                ->select('
                    p.id as pk_id,
                    ps.id as sasaran_id,
                    ps.sasaran,
                    pi.id as indikator_id,
                    pi.indikator,
                    pi.target,
                    s.satuan as satuan_nama
                ')
                ->join('pk_sasaran ps', 'ps.pk_id = p.id', 'inner')
                ->join('pk_indikator pi', 'pi.pk_sasaran_id = ps.id', 'inner')
                ->join('satuan s', 's.id = pi.id_satuan', 'left')
                ->where('p.jenis', 'bupati')
                ->where('p.tahun', $tahun)
                ->orderBy('ps.id', 'ASC')
                ->orderBy('pi.id', 'ASC')
                ->get()->getResultArray();
        }

        // Susun per sasaran
        $sasaranList = [];
        foreach ($rawData as $row) {
            $sid = $row['sasaran_id'];
            if (!isset($sasaranList[$sid])) {
                $sasaranList[$sid] = [
                    'sasaran' => $row['sasaran'],
                    'indikator' => [],
                ];
            }
            if (!empty($row['indikator_id'])) {
                $sasaranList[$sid]['indikator'][] = [
                    'indikator' => $row['indikator'],
                    'target' => $row['target'],
                    'satuan' => $row['satuan_nama'] ?? '-',
                ];
            }
        }
        $sasaranList = array_values($sasaranList);

        return view('user/pk_bupati', [
            'sasaranList' => $sasaranList,
            'availableYears' => $availableYears,
            'tahun' => $tahun,
        ]);
    }
    public function renstra()
    {
        $db = \Config\Database::connect();

        $opd_id = $this->request->getGet('opd_id') ?? 'all';

        // Ambil data Renstra yang sudah selesai
        $query = $db->table('renstra_sasaran rs')
            ->select('rs.id as sasaran_id, o.id as opd_id_val, o.nama_opd, rs.sasaran, ris.indikator_sasaran, ris.id as indikator_id, ris.satuan')
            ->join('opd o', 'o.id = rs.opd_id')
            ->join('renstra_indikator_sasaran ris', 'ris.renstra_sasaran_id = rs.id', 'left')
            ->where('rs.status', 'selesai');

        if ($opd_id !== 'all') {
            $query->where('rs.opd_id', (int) $opd_id);
        }

        $renstraDataRaw = $query->get()->getResultArray();

        // Ambil target renstra
        $targets = $db->table('renstra_target')->get()->getResultArray();
        $targetMap = [];
        $tahun_set = [];
        foreach ($targets as $t) {
            $targetMap[$t['renstra_indikator_id']][$t['tahun']] = $t['target'];
            $tahun_set[$t['tahun']] = true;
        }
        $tahunList = array_keys($tahun_set);
        sort($tahunList);

        $renstraData = [];
        if (!empty($renstraDataRaw)) {
            foreach ($renstraDataRaw as $row) {
                $indikator_id = $row['indikator_id'];
                $tcap = $targetMap[$indikator_id] ?? [];

                $renstraData[] = [
                    'opd' => $row['nama_opd'],
                    'sasaran' => $row['sasaran'],
                    'indikator' => $row['indikator_sasaran'],
                    'satuan' => $row['satuan'],
                    'target_capaian' => $tcap
                ];
            }
        }

        $opdList = $db->table('opd')->get()->getResultArray();

        return view('user/renstra', [
            'tahunList' => $tahunList,
            'opdList' => $opdList,
            'selected_opd' => $opd_id,
            'renstraData' => $renstraData
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
        $opdList = $db->table('opd')->get()->getResultArray();

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
        $opdList = $db->table('opd')->get()->getResultArray();

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

        $opd_id = $this->request->getGet('opd_id') ?? 'all';

        // IKU OPD yg status selesai dan renstra_id tidak null
        $query = $db->table('iku')
            ->select('iku.id as iku_id, o.id as opd_id_val, o.nama_opd, iku.definisi, rs.sasaran, ris.indikator_sasaran as indikator, ris.satuan, iku.renstra_id')
            ->join('renstra_indikator_sasaran ris', 'ris.id = iku.renstra_id')
            ->join('renstra_sasaran rs', 'rs.id = ris.renstra_sasaran_id')
            ->join('opd o', 'o.id = rs.opd_id')
            ->where('iku.status', 'selesai')
            ->where('iku.renstra_id IS NOT NULL');

        if ($opd_id !== 'all') {
            $query->where('rs.opd_id', (int) $opd_id);
        }

        $ikuOpdDataRaw = $query->get()->getResultArray();

        // Ambil target untuk IKU ini dari renstra_target
        $targets = $db->table('renstra_target')->get()->getResultArray();
        $targetMap = [];
        $tahun_set = [];
        foreach ($targets as $t) {
            $targetMap[$t['renstra_indikator_id']][$t['tahun']] = $t['target'];
            $tahun_set[$t['tahun']] = true;
        }
        $tahunList = array_keys($tahun_set);
        sort($tahunList);

        $ikuOpdData = [];
        foreach ($ikuOpdDataRaw as $row) {
            $renstra_id = $row['renstra_id'];
            $row['target_capaian'] = $targetMap[$renstra_id] ?? [];
            $ikuOpdData[] = $row;
        }

        $opdList = $db->table('opd')->get()->getResultArray();

        return view('user/iku_opd', [
            'ikuOpdData' => $ikuOpdData,
            'tahunList' => $tahunList,
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

        if ($periode) {
            [$start, $end] = explode('-', $periode);
            $start = (int) $start;
            $end = (int) $end;
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
            'filters' => ['periode' => $periode]
        ];

        return view('user/cascading_kabupaten', $data);
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

        $opdList = $db->table('opd')->get()->getResultArray();

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
        $rowspan = $this->buildCascadingOpdRowspan($rows);
        $firstShow = $this->buildCascadingOpdFirstShow($rows);

        $html = view('adminOpd/cascading/cascading_cetak', [
            'rows' => $rows,
            'rowspan' => $rowspan,
            'firstShow' => $firstShow,
            'tahun_mulai' => $start,
            'tahun_akhir' => $end,
            'periode' => $periode
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
        $mpdf->SetDisplayMode('fullpage');
        $mpdf->WriteHTML($html);
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="Cascading-OPD-' . $periode . '.pdf"');
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

    private function buildCascadingOpdRowspan($rows)
    {
        $meta = [
            'tujuan' => [],
            'sasaran' => [],
            'tujuan_renstra' => [],
            'sasaran_renstra' => [],
            'indikator' => [],
            'es3' => [],
            'es3_indikator' => [],
            'es4' => [],
        ];
        foreach ($rows as $r) {
            $meta['tujuan'][$r['tujuan_id']] = ($meta['tujuan'][$r['tujuan_id']] ?? 0) + 1;
            $meta['sasaran'][$r['sasaran_id']] = ($meta['sasaran'][$r['sasaran_id']] ?? 0) + 1;
            $meta['tujuan_renstra'][$r['renstra_tujuan_id']] = ($meta['tujuan_renstra'][$r['renstra_tujuan_id']] ?? 0) + 1;
            $meta['sasaran_renstra'][$r['renstra_sasaran_id']] = ($meta['sasaran_renstra'][$r['renstra_sasaran_id']] ?? 0) + 1;
            $meta['indikator'][$r['indikator_id']] = ($meta['indikator'][$r['indikator_id']] ?? 0) + 1;
            if ($r['es3_id'])
                $meta['es3'][$r['es3_id']] = ($meta['es3'][$r['es3_id']] ?? 0) + 1;
            $key = $r['es3_id'] . '_' . ($r['es3_indikator_id'] ?? null);
            $meta['es3_indikator'][$key] = ($meta['es3_indikator'][$key] ?? 0) + 1;
            if ($r['es4_id'])
                $meta['es4'][$r['es4_id']] = ($meta['es4'][$r['es4_id']] ?? 0) + 1;
        }
        return $meta;
    }

    private function buildCascadingOpdFirstShow($rows)
    {
        $shown = [
            'tujuan' => [],
            'sasaran' => [],
            'tujuan_renstra' => [],
            'sasaran_renstra' => [],
            'indikator' => [],
            'es3' => [],
            'es3_indikator' => [],
            'es4' => [],
        ];
        foreach ($rows as $index => $r) {
            if (!isset($shown['tujuan'][$r['tujuan_id']]))
                $shown['tujuan'][$r['tujuan_id']] = $index;
            if (!isset($shown['sasaran'][$r['sasaran_id']]))
                $shown['sasaran'][$r['sasaran_id']] = $index;
            if (!isset($shown['tujuan_renstra'][$r['renstra_tujuan_id']]))
                $shown['tujuan_renstra'][$r['renstra_tujuan_id']] = $index;
            if (!isset($shown['sasaran_renstra'][$r['renstra_sasaran_id']]))
                $shown['sasaran_renstra'][$r['renstra_sasaran_id']] = $index;
            if (!isset($shown['indikator'][$r['indikator_id']]))
                $shown['indikator'][$r['indikator_id']] = $index;
            if ($r['es3_id'] && !isset($shown['es3'][$r['es3_id']]))
                $shown['es3'][$r['es3_id']] = $index;
            $key = $r['es3_id'] . '_' . ($r['es3_indikator_id'] ?? null);
            if (!isset($shown['es3_indikator'][$key]))
                $shown['es3_indikator'][$key] = $index;
            if ($r['es4_id'] && !isset($shown['es4'][$r['es4_id']]))
                $shown['es4'][$r['es4_id']] = $index;
        }
        return $shown;
    }

    private function buildOpdTree($rows)
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
                $tree[$tId]['sasarans'][$sId]['tujuan_renstras'][$rtId] = ['nama' => $r['renstra_tujuan'] ?: '(Tanpa Tujuan Renstra)', 'es2s' => []];
            }
            $rsId = rtrim('_' . ($r['renstra_sasaran_id'] ?? 'none'), '_');
            if (empty($r['renstra_sasaran_id']) && empty($r['renstra_sasaran']))
                continue;
            if (!isset($tree[$tId]['sasarans'][$sId]['tujuan_renstras'][$rtId]['es2s'][$rsId])) {
                $tree[$tId]['sasarans'][$sId]['tujuan_renstras'][$rtId]['es2s'][$rsId] = [
                    'nama' => $r['renstra_sasaran'] ?: '(Tanpa Sasaran ES.II)',
                    'csf' => $r['csf_es2'],
                    'indikators' => [],
                    'es3s' => []
                ];
            }
            $risId = $r['indikator_id'];
            if ($risId)
                $tree[$tId]['sasarans'][$sId]['tujuan_renstras'][$rtId]['es2s'][$rsId]['indikators'][$risId] = $r['indikator_sasaran'];
            $es3Id = $r['es3_id'];
            if ($es3Id) {
                if (!isset($tree[$tId]['sasarans'][$sId]['tujuan_renstras'][$rtId]['es2s'][$rsId]['es3s'][$es3Id])) {
                    $tree[$tId]['sasarans'][$sId]['tujuan_renstras'][$rtId]['es2s'][$rsId]['es3s'][$es3Id] = ['nama' => $r['es3_sasaran'], 'csf' => $r['csf_es3'], 'indikators' => []];
                }
                $es3IndId = $r['es3_indikator_id'];
                if ($es3IndId)
                    $tree[$tId]['sasarans'][$sId]['tujuan_renstras'][$rtId]['es2s'][$rsId]['es3s'][$es3Id]['indikators'][$es3IndId] = $r['es3_indikator'];
            }
        }
        return $tree;
    }

    public function pk_pimpinan()
    {
        $pkPimpinanData = [
            [
                'tahun' => '2023',
                'misi' => 'Meningkatkan kualitas sumber daya manusia yang berdaya saing',
                'sasaran' => 'Meningkatkan kualitas pendidikan',
                'indikator' => 'Angkat Partisipsi Sekolah',
                'target' => '98%',
            ],
            [
                'tahun' => '2023',
                'misi' => 'Meningkatkan kualitas sumber daya manusia yang berdaya saing',
                'sasaran' => 'Meningkatkan kualitas pendidikan',
                'indikator' => 'Angkat Partisipsi Sekolah',
                'target' => '98%',
            ]
        ];

        return view('user/pk_pimpinan', [
            'pkPimpinanData' => $pkPimpinanData
        ]);
    }

    public function pk_administrator()
    {
        $pkAdministratorData = [
            [
                'tahun' => '2023',
                'sasaran' => 'Meningkatkan kualitas pendidikan',
                'indikator' => 'Angkat Partisipsi Sekolah',
                'target' => '98%',
            ],
            [
                'tahun' => '2023',
                'sasaran' => 'Meningkatkan kualitas pendidikan',
                'indikator' => 'Angkat Partisipsi Sekolah',
                'target' => '98%',
            ]
        ];
        return view('user/pk_administrator', [
            'pkAdministratorData' => $pkAdministratorData
        ]);
    }

    public function pk_pengawas()
    {
        $pkPengawasData = [
            [
                'tahun' => '2023',
                'sasaran' => 'Meningkatkan kualitas pendidikan',
                'indikator' => 'Angkat Partisipsi Sekolah',
                'target' => '98%',
            ],
            [
                'tahun' => '2023',
                'sasaran' => 'Meningkatkan kualitas pendidikan',
                'indikator' => 'Angkat Partisipsi Sekolah',
                'target' => '98%',
            ]
        ];

        return view('user/pk_pengawas', [
            'pkPengawasData' => $pkPengawasData
        ]);
    }

    public function tentang_kami()
    {
        return view('user/tentang_kami');
    }

}
