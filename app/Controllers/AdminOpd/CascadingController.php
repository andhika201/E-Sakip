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
                ->getCascadingMatrixByOpd($this->opdId, $start, $end);

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
        // dd($data['rowspan']);

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

    public function tambahEs3($indikatorId = null)
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

        return view('adminOpd/cascading/tambah_es3', [
            'indikator' => $indikator,
            'periode' => $periode
        ]);
    }

    public function tambahEs4($indikatorEs3Id)
    {
        $indikator = $this->db->table('cascading_indikator_opd i')
            ->select('
            i.id as es3_indikator_id,
            i.indikator as indikator_es3,
            s.id as es3_id,
            s.nama_sasaran as sasaran_es3,
            s.renstra_indikator_sasaran_id
        ')
            ->join('cascading_sasaran_opd s', 's.id=i.cascading_sasaran_id')
            ->where('i.id', $indikatorEs3Id)
            ->get()
            ->getRowArray();

        if (!$indikator) {
            return redirect()->back()->with('error', 'Data tidak ditemukan');
        }

        $periode = $this->request->getGet('periode');


        return view('adminOpd/cascading/tambah_es4', [
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
                        $indikatorEs3Id = $this->db->insertID();
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
                                'es3_indikator_id' => $indikatorEs3Id,
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

    public function saveEs3()
    {
        $renstraIndikatorId = $this->request->getPost('renstra_indikator_sasaran_id');
        $sasaranData = $this->request->getPost('sasaran');
        $opdId = session()->get('opd_id');

        $this->db->transStart();

        foreach ($sasaranData as $es3) {

            if (empty($es3['nama']))
                continue;

            $this->db->table('cascading_sasaran_opd')->insert([
                'opd_id' => $opdId,
                'renstra_indikator_sasaran_id' => $renstraIndikatorId,
                'parent_id' => null,
                'level' => 'es3',
                'nama_sasaran' => $es3['nama']
            ]);

            $es3Id = $this->db->insertID();

            if (!empty($es3['indikator'])) {

                foreach ($es3['indikator'] as $indikator) {

                    if (empty($indikator['nama']))
                        continue;

                    $this->db->table('cascading_indikator_opd')->insert([
                        'cascading_sasaran_id' => $es3Id,
                        'indikator' => $indikator['nama']
                    ]);

                }

            }

        }

        $this->db->transComplete();

        return redirect()->to('adminopd/cascading')
            ->with('success', 'ESS III berhasil disimpan');
    }

    public function saveEs4()
    {
        $indikatorEs3Id = $this->request->getPost('es3_indikator_id');
        $parentId = $this->request->getPost('parent_id');
        $renstraIndikatorId = $this->request->getPost('renstra_indikator_sasaran_id');

        $sasaranData = $this->request->getPost('sasaran');

        $opdId = session()->get('opd_id');

        if (!$sasaranData) {
            return redirect()->back()->with('error', 'Data sasaran kosong');
        }

        $this->db->transStart();

        foreach ($sasaranData as $es4) {

            if (empty($es4['nama']))
                continue;

            $this->db->table('cascading_sasaran_opd')->insert([
                'opd_id' => $opdId,
                'renstra_indikator_sasaran_id' => $renstraIndikatorId,
                'parent_id' => $parentId,
                'es3_indikator_id' => $indikatorEs3Id,
                'level' => 'es4',
                'nama_sasaran' => $es4['nama']
            ]);

            $es4Id = $this->db->insertID();

            if (!empty($es4['indikator'])) {

                foreach ($es4['indikator'] as $indikator) {

                    if (empty($indikator['nama']))
                        continue;

                    $this->db->table('cascading_indikator_opd')->insert([
                        'cascading_sasaran_id' => $es4Id,
                        'indikator' => $indikator['nama']
                    ]);
                }
            }
        }

        $this->db->transComplete();

        return redirect()->to('adminopd/cascading')
            ->with('success', 'ESS IV berhasil disimpan');
    }

    public function editEs3($id)
    {
        $sasaran = $this->db->table('cascading_sasaran_opd')
            ->where('id', $id)
            ->where('level', 'es3')
            ->get()
            ->getRowArray();

        if (!$sasaran) {
            return redirect()->back()->with('error', 'Data tidak ditemukan');
        }

        $indikator = $this->db->table('cascading_indikator_opd')
            ->where('cascading_sasaran_id', $id)
            ->get()
            ->getResultArray();

        return view('adminOpd/cascading/edit_es3', [
            'sasaran' => $sasaran,
            'indikator' => $indikator
        ]);
    }

    public function editEs4($id)
    {
        $sasaran = $this->db->table('cascading_sasaran_opd')
            ->where('id', $id)
            ->where('level', 'es4')
            ->get()
            ->getRowArray();

        if (!$sasaran) {
            return redirect()->back()->with('error', 'Data tidak ditemukan');
        }

        // indikator es4
        $indikator = $this->db->table('cascading_indikator_opd')
            ->where('cascading_sasaran_id', $id)
            ->get()
            ->getResultArray();

        // ambil sasaran es3
        $es3 = $this->db->table('cascading_sasaran_opd')
            ->where('id', $sasaran['parent_id'])
            ->get()
            ->getRowArray();

        // ambil indikator es3
        $indikatorEs3 = $this->db->table('cascading_indikator_opd')
            ->where('id', $sasaran['es3_indikator_id'])
            ->get()
            ->getRowArray();

        return view('adminOpd/cascading/edit_es4', [
            'sasaran' => $sasaran,
            'indikator' => $indikator,
            'es3' => $es3,
            'indikator_es3' => $indikatorEs3
        ]);
    }

    public function updateEs3($id)
    {
        $nama = $this->request->getPost('nama');
        $indikator = $this->request->getPost('indikator');

        $this->db->transStart();

        // update sasaran
        $this->db->table('cascading_sasaran_opd')
            ->where('id', $id)
            ->update([
                'nama_sasaran' => $nama
            ]);

        // hapus indikator lama
        $this->db->table('cascading_indikator_opd')
            ->where('cascading_sasaran_id', $id)
            ->delete();

        // insert indikator baru
        if ($indikator) {
            foreach ($indikator as $i) {

                if (empty($i['nama']))
                    continue;

                $this->db->table('cascading_indikator_opd')->insert([
                    'cascading_sasaran_id' => $id,
                    'indikator' => $i['nama']
                ]);
            }
        }

        $this->db->transComplete();

        return redirect()->to('adminopd/cascading')
            ->with('success', 'Data berhasil diperbarui');
    }
    public function updateEs4($id)
    {
        $nama = $this->request->getPost('nama');
        $indikator = $this->request->getPost('indikator');

        $this->db->transStart();

        // update sasaran
        $this->db->table('cascading_sasaran_opd')
            ->where('id', $id)
            ->update([
                'nama_sasaran' => $nama
            ]);

        // hapus indikator lama
        $this->db->table('cascading_indikator_opd')
            ->where('cascading_sasaran_id', $id)
            ->delete();

        // insert indikator baru
        if ($indikator) {

            foreach ($indikator as $i) {

                if (empty($i['nama']))
                    continue;

                $this->db->table('cascading_indikator_opd')->insert([
                    'cascading_sasaran_id' => $id,
                    'indikator' => $i['nama']
                ]);

            }

        }

        $this->db->transComplete();

        return redirect()->to('adminopd/cascading')
            ->with('success', 'Data ESS IV berhasil diperbarui');
    }
    public function deleteEs3($id)
    {
        $this->db->table('cascading_sasaran_opd')
            ->where('id', $id)
            ->delete();

        return redirect()->to('adminopd/cascading')
            ->with('success', 'Data berhasil dihapus');
    }
    public function deleteEs4($id)
    {
        $this->db->table('cascading_sasaran_opd')
            ->where('id', $id)
            ->delete();

        return redirect()->to('adminopd/cascading')
            ->with('success', 'Data berhasil dihapus');
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
            'sasaran_renstra' => [],
            'indikator' => [],
            'es3' => [],
            'es3_indikator' => [],
            'es4' => [],
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

            $meta['indikator'][$r['indikator_id']] =
                ($meta['indikator'][$r['indikator_id']] ?? 0) + 1;

            if ($r['es3_id']) {
                $meta['es3'][$r['es3_id']] =
                    ($meta['es3'][$r['es3_id']] ?? 0) + 1;
            }

            $key = $r['es3_id'] . '_' . ($r['es3_indikator_id'] ?? null);

            $meta['es3_indikator'][$key] =
                ($meta['es3_indikator'][$key] ?? 0) + 1;

            if ($r['es4_id']) {
                $meta['es4'][$r['es4_id']] =
                    ($meta['es4'][$r['es4_id']] ?? 0) + 1;
            }

        }

        return $meta;
    }

    private function buildFirstShowMeta($rows)
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

            if (!isset($shown['indikator'][$r['indikator_id']])) {
                $shown['indikator'][$r['indikator_id']] = $index;
            }

            if ($r['es3_id'] && !isset($shown['es3'][$r['es3_id']])) {
                $shown['es3'][$r['es3_id']] = $index;
            }

            $key = $r['es3_id'] . '_' . ($r['es3_indikator_id'] ?? null);

            if (!isset($shown['es3_indikator'][$key])) {
                $shown['es3_indikator'][$key] = $index;
            }

            if ($r['es4_id'] && !isset($shown['es4'][$r['es4_id']])) {
                $shown['es4'][$r['es4_id']] = $index;
            }
        }

        return $shown;
    }

    public function saveCsf()
    {
        $id = $this->request->getPost('id');
        $csfVal = $this->request->getPost('csf');
        $level = $this->request->getPost('level'); // es2 or es3

        if ($level === 'es2') {
            $this->db->table('renstra_sasaran')
                ->where('id', $id)
                ->update(['csf' => $csfVal]);
        } elseif ($level === 'es3') {
            $this->db->table('cascading_sasaran_opd')
                ->where('id', $id)
                ->update(['csf' => $csfVal]);
        }

        return $this->response->setJSON(['status' => 'success']);
    }

}