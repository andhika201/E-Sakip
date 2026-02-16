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

    public function index()
    {
        $periode = $this->request->getGet('periode');

        // ==============================
        // AMBIL PERIODE RPJMD
        // ==============================
        $periodeList = $this->db->table('rpjmd_misi')
            ->select('tahun_mulai, tahun_akhir')
            ->groupBy(['tahun_mulai', 'tahun_akhir'])
            ->orderBy('tahun_mulai', 'DESC')
            ->get()
            ->getResultArray();

        $rows = [];
        $rowspan = [];
        $firstShow = [];
        $years = [];

        // ==============================
        // JIKA PERIODE DIPILIH
        // ==============================
        if ($periode) {

            [$start, $end] = explode('-', $periode);

            // sementara pakai tahun awal
            $tahun = (int) $start;
            $start = (int) $start;
            $end = (int) $end;

            $years = range($start, $end);

            $rows = $this->cascadingModel->getMatrix($start, $end);

            $rowspan = $this->buildRowspanMeta($rows);
            $firstShow = $this->buildFirstShowMeta($rows);
        }
        // dd($periodeList);
        $data = [
            'rows' => $rows,
            'rowspan' => $rowspan,
            'firstShow' => $firstShow,
            'periode_master' => $periodeList,
            'years' => $years,
            'filters' => [
                'periode' => $periode
            ]
        ];

        return view('adminKabupaten/cascading/cascading', $data);
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

        // ambil list OPD
        $opdList = $this->db->table('opd')
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

                if (
                    !$this->cascadingModel
                        ->isProgramBelongsToOpd($programId, $opdId)
                )
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


        return redirect()->to(
            base_url('adminkab/cascading?periode=' .
                $this->request->getGet('periode'))
        )->with('success', 'Mapping Cascading berhasil disimpan');
    }

    public function cetak()
    {
        ob_clean(); // 🔥 BUANG OUTPUT SEBELUMNYA
        ob_start();

        $periode = $this->request->getGet('periode');

        if (!$periode) {
            return redirect()->back()
                ->with('error', 'Periode wajib dipilih');
        }

        [$start, $end] = explode('-', $periode);

        $rows = $this->cascadingModel
            ->getMatrix((int) $start, (int) $end);

        $rowspan = $this->buildRowspanMeta($rows);
        $firstShow = $this->buildFirstShowMeta($rows);
        $years = range((int) $start, (int) $end);

        $html = view(
            'adminKabupaten/cascading/cascading_cetak',
            compact('rows', 'rowspan', 'firstShow', 'years')
        );

        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4-L',
            'tempDir' => sys_get_temp_dir()
        ]);

        $mpdf->WriteHTML($html);

        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="Cascading-' . $periode . '.pdf"');

        $mpdf->Output();
        exit;
    }



}
