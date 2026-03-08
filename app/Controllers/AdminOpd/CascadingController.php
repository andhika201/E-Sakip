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
        // dd($rows);

        return view('adminOpd/cascading/cascading', $data);
    }

    public function tambah($indikatorId = null)
    {
        if (!$indikatorId) {
            return redirect()->back()->with('error', 'Indikator tidak ditemukan');
        }

        $indikator = $this->db->table('renstra_indikator_sasaran ris')
            ->select("
            ris.id,
            ris.indikator_sasaran,
            rs.sasaran as sasaran_es2,
            rt.tujuan as tujuan_renstra
        ")
            ->join('renstra_sasaran rs', 'rs.id = ris.renstra_sasaran_id')
            ->join('renstra_tujuan rt', 'rt.id = rs.renstra_tujuan_id')
            ->where('ris.id', $indikatorId)
            ->get()
            ->getRowArray();

        if (!$indikator) {
            return redirect()->back()->with('error', 'Indikator tidak ditemukan');
        }

        $periode = $this->request->getGet('periode');

        return view('adminOpd/cascading/tambah_cascading', [
            'indikator' => $indikator,
            'periode' => $periode
        ]);
    }
    public function save()
    {
        $renstraIndikatorId = $this->request->getPost('renstra_indikator_sasaran_id');
        $sasaranData = $this->request->getPost('sasaran');
        $opdId = session()->get('opd_id');

        if (!$renstraIndikatorId || empty($sasaranData)) {
            return redirect()->back()->with('error', 'Data tidak lengkap');
        }

        $this->db->transStart();

        foreach ($sasaranData as $es3) {

            if (empty($es3['nama']))
                continue;

            // ==========================
            // INSERT SASARAN ESS III
            // ==========================

            $this->db->table('cascading_sasaran_opd')->insert([
                'opd_id' => $opdId,
                'renstra_indikator_sasaran_id' => $renstraIndikatorId,
                'parent_id' => null,
                'level' => 'es3',
                'nama_sasaran' => $es3['nama']
            ]);

            $es3Id = $this->db->insertID();

            if (!empty($es3['indikator'])) {

                foreach ($es3['indikator'] as $indikatorEs3) {

                    if (!empty($indikatorEs3['nama'])) {

                        // ==========================
                        // INSERT INDIKATOR ESS III
                        // ==========================

                        $this->db->table('cascading_indikator_opd')->insert([
                            'cascading_sasaran_id' => $es3Id,
                            'indikator' => $indikatorEs3['nama']
                        ]);

                    }

                    // ==========================
                    // SASARAN ESS IV
                    // ==========================

                    if (!empty($indikatorEs3['sasaran'])) {

                        foreach ($indikatorEs3['sasaran'] as $es4) {

                            if (empty($es4['nama']))
                                continue;

                            $this->db->table('cascading_sasaran_opd')->insert([
                                'opd_id' => $opdId,
                                'renstra_indikator_sasaran_id' => $renstraIndikatorId,
                                'parent_id' => $es3Id,
                                'level' => 'es4',
                                'nama_sasaran' => $es4['nama']
                            ]);

                            $es4Id = $this->db->insertID();

                            // ==========================
                            // INDIKATOR ESS IV
                            // ==========================

                            if (!empty($es4['indikator'])) {

                                foreach ($es4['indikator'] as $indikatorEs4) {

                                    if (empty($indikatorEs4['nama']))
                                        continue;

                                    $this->db->table('cascading_indikator_opd')->insert([
                                        'cascading_sasaran_id' => $es4Id,
                                        'indikator' => $indikatorEs4['nama']
                                    ]);
                                }

                            }

                        }

                    }

                }

            }

        }

        $this->db->transComplete();

        return redirect()->to('adminopd/cascading')
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