<?php

namespace App\Controllers\AdminOpd;

use App\Controllers\BaseController;
use App\Models\CascadingModel;

class CascadingController extends BaseController
{
    protected $cascadingModel;
    protected $db;
    protected $opdId;

    public function __construct()
    {
        $this->cascadingModel = new CascadingModel();
        $this->db = \Config\Database::connect();
        $this->opdId = session()->get('opd_id');
    }

    public function index()
    {
        $periode = $this->request->getGet('periode');

        $periodeList = $this->db->table('rpjmd_misi')
            ->select('tahun_mulai, tahun_akhir')
            ->groupBy(['tahun_mulai', 'tahun_akhir'])
            ->orderBy('tahun_mulai', 'DESC')
            ->get()
            ->getResultArray();

        $rows = [];
        $years = [];

        if ($periode) {

            [$start, $end] = explode('-', $periode);

            $start = (int) $start;
            $end = (int) $end;

            $years = range($start, $end);

            $rows = $this->cascadingModel
                ->getRenstraByOpd($this->opdId);

            $rowspan = $this->buildRowspanMeta($rows);
            $firstShow = $this->buildFirstShowMeta($rows);
        }

        $data = [
            'rows' => $rows,
            'rowspan' => $rowspan ?? [],
            'firstShow' => $firstShow ?? [],
            'periode_master' => $periodeList,
            'years' => $years,
            'filters' => [
                'periode' => $periode
            ]
        ];
        // dd($data);

        return view('adminOpd/cascading/cascading', $data);
    }

    public function tambah($renstraIndikatorId = null)
    {
        if (!$renstraIndikatorId) {
            return redirect()->back()
                ->with('error', 'Indikator tidak ditemukan');
        }

        $indikator = $this->db->table('renstra_indikator_sasaran')
            ->where('id', $renstraIndikatorId)
            ->get()
            ->getRowArray();

        if (!$indikator) {
            return redirect()->back()
                ->with('error', 'Indikator tidak ditemukan');
        }

        $existing = $this->cascadingModel
            ->getCascadingTree($renstraIndikatorId, $this->opdId);

        return view('adminOpd/cascading/tambah', [
            'indikator' => $indikator,
            'existing' => $existing
        ]);
    }

    public function save()
    {
        $renstraIndikatorId = $this->request->getPost('renstra_indikator_sasaran_id');
        $sasaran = $this->request->getPost('sasaran');

        if (!$renstraIndikatorId || empty($sasaran)) {
            return redirect()->back()
                ->with('error', 'Data tidak lengkap');
        }

        $this->db->transStart();

        foreach ($sasaran as $row) {

            $parentId = $row['parent_id'] ?? null;

            $insert = [
                'opd_id' => $this->opdId,
                'renstra_indikator_sasaran_id' => $renstraIndikatorId,
                'parent_id' => $parentId,
                'level' => $row['level'],
                'nama_sasaran' => $row['nama']
            ];

            $sasaranId = $this->cascadingModel
                ->insertSasaran($insert);

            if (!empty($row['indikator'])) {

                foreach ($row['indikator'] as $indikator) {

                    $this->cascadingModel
                        ->insertIndikator([
                            'cascading_sasaran_id' => $sasaranId,
                            'indikator' => $indikator['nama'],
                            'satuan' => $indikator['satuan']
                        ]);
                }
            }
        }

        $this->db->transComplete();

        return redirect()->to(base_url('adminopd/cascading'))
            ->with('success', 'Cascading berhasil disimpan');
    }

    public function getRenstraIndikator()
    {
        $renstraSasaranId = $this->request->getGet('renstra_sasaran_id');

        $data = $this->db->table('renstra_indikator_sasaran')
            ->where('renstra_sasaran_id', $renstraSasaranId)
            ->get()
            ->getResultArray();

        return $this->response->setJSON($data);
    }

    public function getEssChild()
    {
        $parentId = $this->request->getGet('parent_id');

        $data = $this->db->table('cascading_sasaran_opd')
            ->where('parent_id', $parentId)
            ->get()
            ->getResultArray();

        return $this->response->setJSON($data);
    }

    private function buildRowspanMeta($rows)
    {
        $meta = [
            'tujuan' => [],
            'sasaran' => [],
            'tujuan_renstra' => [],
            'sasaran_renstra' => []
        ];

        foreach ($rows as $r) {

            $meta['tujuan'][$r['tujuan_id']] =
                ($meta['tujuan'][$r['tujuan_id']] ?? 0) + 1;

            $meta['sasaran'][$r['sasaran_id']] =
                ($meta['sasaran'][$r['sasaran_id']] ?? 0) + 1;

            $meta['tujuan_renstra'][$r['renstra_tujuan_id']] =
                ($meta['tujuan_renstra'][$r['renstra_tujuan_id']] ?? 0) + 1;

            $meta['sasaran_renstra'][$r['renstra_sasaran_id']] =
                ($meta['sasaran_renstra'][$r['renstra_sasaran_id']] ?? 0) + 1;
        }

        return $meta;
    }

    private function buildFirstShowMeta($rows)
    {
        $shown = [
            'tujuan' => [],
            'sasaran' => [],
            'tujuan_renstra' => [],
            'sasaran_renstra' => []
        ];

        foreach ($rows as $index => $r) {

            if (!isset($shown['tujuan'][$r['tujuan_id']])) {
                $shown['tujuan'][$r['tujuan_id']] = $index;
            }

            if (!isset($shown['sasaran'][$r['sasaran_id']])) {
                $shown['sasaran'][$r['sasaran_id']] = $index;
            }

            if (!isset($shown['tujuan_renstra'][$r['renstra_tujuan_id']])) {
                $shown['tujuan_renstra'][$r['renstra_tujuan_id']] = $index;
            }

            if (!isset($shown['sasaran_renstra'][$r['renstra_sasaran_id']])) {
                $shown['sasaran_renstra'][$r['renstra_sasaran_id']] = $index;
            }
        }

        return $shown;
    }

}