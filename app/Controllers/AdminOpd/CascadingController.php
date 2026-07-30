<?php

namespace App\Controllers\AdminOpd;

use App\Controllers\BaseController;
use App\Controllers\Concerns\CascadingOpdMetaTrait;
use App\Models\CascadingModel;

class CascadingController extends BaseController
{
    /** Rowspan/firstShow/pohon matriks cascading OPD (termasuk jenjang Pelaksana). */
    use CascadingOpdMetaTrait;

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

        // Tampilan dipisah per menu: 'tabel' (Cascading) atau 'pohon' (Pohon Kinerja).
        $view = $this->request->getGet('view');
        $view = in_array($view, ['tabel', 'pohon'], true) ? $view : 'tabel';

        $periodeList = $this->db->table('rpjmd_misi')
            ->select('tahun_mulai, tahun_akhir')
            ->groupBy(['tahun_mulai', 'tahun_akhir'])
            ->orderBy('tahun_mulai', 'ASC')
            ->get()
            ->getResultArray();

        $rows = [];
        $years = [];
        $tree = [];

        if ($periode) {

            [$start, $end] = explode('-', $periode);

            $start = (int) $start;
            $end = (int) $end;

            $years = range($start, $end);

            $rows = $this->cascadingModel
                ->getCascadingMatrixByOpd($this->opdId, $start, $end);

            $this->preprocessEmptyIds($rows);

            $rowspan = $this->buildRowspanMeta($rows);
            $firstShow = $this->buildFirstShowMeta($rows);

            // Pohon Kinerja OPD tampil inline (tidak harus klik cetak)
            $tree = $this->buildOpdTree($rows);
        }
        $data = [
            'rows' => $rows,
            'rowspan' => $rowspan ?? [],
            'firstShow' => $firstShow ?? [],
            'periode_master' => $periodeList,
            'years' => $years,
            'tree' => $tree,
            'view' => $view,
            'title' => ($view === 'pohon' ? 'Pohon Kinerja' : 'Cascading'),
            'opd_missing' => empty($this->opdId),
            'filters' => [
                'periode' => $periode
            ]
        ];
        // dd($data['rowspan']);

        return view('adminOpd/cascading/cascading', $data);
    }

    public function excel()
    {
        $periode = $this->request->getGet('periode');
        if (!$periode) {
            return redirect()->back()->with('error', 'Periode wajib dipilih');
        }
        [$start, $end] = explode('-', $periode);
        $rows = $this->cascadingModel->getCascadingMatrixByOpd($this->opdId, (int) $start, (int) $end);

        $db = \Config\Database::connect();
        $o  = $db->table('opd')->select('nama_opd')->where('id', $this->opdId)->get()->getRowArray();

        helper('cascading_excel');
        cascading_opd_excel($rows, $periode, $o['nama_opd'] ?? '');
    }

    /**
     * Render ULANG hanya tabel cascading (partial) untuk refresh via AJAX,
     * sehingga update/delete Es3/Es4 tidak perlu reload seluruh halaman.
     */
    public function partialTable()
    {
        $periode = $this->request->getGet('periode');
        if (!$periode || empty($this->opdId)) {
            return ''; // tanpa periode/opd -> tabel kosong
        }

        [$start, $end] = explode('-', $periode);
        $rows = $this->cascadingModel
            ->getCascadingMatrixByOpd($this->opdId, (int) $start, (int) $end);

        $this->preprocessEmptyIds($rows);

        return view('adminOpd/cascading/_table', [
            'rows'      => $rows,
            'rowspan'   => $this->buildRowspanMeta($rows),
            'firstShow' => $this->buildFirstShowMeta($rows),
        ]);
    }

    public function cetak()
    {
        ob_clean(); // BUANG OUTPUT SEBELUMNYA
        ob_start();

        $periode = $this->request->getGet('periode');

        if (!$periode) {
            return redirect()->back()
                ->with('error', 'Periode wajib dipilih');
        }

        [$start, $end] = explode('-', $periode);
        $start = (int) $start;
        $end = (int) $end;

        $rows = $this->cascadingModel
            ->getCascadingMatrixByOpd($this->opdId, $start, $end);

        $this->preprocessEmptyIds($rows);

        $rowspan = $this->buildRowspanMeta($rows);
        $firstShow = $this->buildFirstShowMeta($rows);

        $opd = $this->db->table('opd')
            ->select('nama_opd')
            ->where('id', $this->opdId)
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

        // A3 landscape sejak kolom bertambah jadi 12 (ada jenjang Pelaksana);
        // A4-L membuat tabel terpotong. shrink_tables_to_fit=false supaya mPDF
        // TIDAK menyusutkan font sampai tak terbaca — lebar kolom sudah diatur
        // persen lewat colgroup di view cetaknya.
        $mpdf = new \Mpdf\Mpdf([
            'mode'                 => 'utf-8',
            'format'               => 'A3-L',
            'margin_left'          => 8,
            'margin_right'         => 8,
            'margin_top'           => 12,
            'margin_bottom'        => 10,
            'margin_header'        => 0,
            'margin_footer'        => 0,
            'shrink_tables_to_fit' => false,
            'tempDir'              => sys_get_temp_dir()
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

    public function cetakPohon()
    {
        $periode = $this->request->getGet('periode');

        if (!$periode) {
            return redirect()->back()
                ->with('error', 'Periode wajib dipilih');
        }

        [$start, $end] = explode('-', $periode);
        $start = (int) $start;
        $end   = (int) $end;

        $rows = $this->cascadingModel
            ->getCascadingMatrixByOpd($this->opdId, $start, $end);

        $tree = $this->buildOpdTree($rows);

        // Ambil visi via JOIN rpjmd_visi
        $firstMisi = $this->db->table('rpjmd_misi m')
            ->select('rv.visi')
            ->join('rpjmd_visi rv', 'rv.id = m.rpjmd_visi_id', 'left')
            ->where('m.tahun_mulai', $start)
            ->where('m.tahun_akhir', $end)
            ->orderBy('m.id', 'ASC')
            ->get()->getRowArray();
        $visi = $firstMisi['visi'] ?? '';

        $o       = $this->db->table('opd')->select('nama_opd')->where('id', $this->opdId)->get()->getRowArray();
        $namaOpd = $o['nama_opd'] ?? '';

        return view('adminOpd/cascading/pohon_kinerja_cetak', [
            'tree'        => $tree,
            'visi'        => $visi,
            'nama_opd'    => $namaOpd,
            'tahun_mulai' => $start,
            'tahun_akhir' => $end,
            'periode'     => $periode
        ]);
    }

    /** Pohon Kinerja OPD — implementasi bersama di CascadingOpdMetaTrait. */
    private function buildOpdTree($rows)
    {
        return $this->cascOpdTree($rows);
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

                    // Reset per iterasi agar tidak memakai id indikator dari indikator ES3 sebelumnya
                    // (atau variabel tak terdefinisi) saat indikator ini tanpa nama.
                    $indikatorEs3Id = null;

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

        // Sertakan id + jumlah anak Es4 (untuk konfirmasi hapus berantai di form).
        $indikator = $this->db->table('cascading_indikator_opd i')
            ->select('i.id, i.indikator, '
                . '(SELECT COUNT(*) FROM cascading_sasaran_opd s '
                . 'WHERE s.es3_indikator_id = i.id AND s.level = "es4") AS es4_count', false)
            ->where('i.cascading_sasaran_id', $id)
            ->get()
            ->getResultArray();

        $data = [
            'sasaran' => $sasaran,
            'indikator' => $indikator
        ];
        // AJAX (modal) -> hanya form; navigasi biasa -> halaman penuh.
        if ($this->request->isAJAX()) {
            return view('adminOpd/cascading/_form_es3', $data);
        }
        return view('adminOpd/cascading/edit_es3', $data);
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

        // Indikator es4 + jumlah anak Pelaksana (untuk konfirmasi hapus berantai).
        $indikator = $this->db->table('cascading_indikator_opd i')
            ->select('i.id, i.indikator, '
                . '(SELECT COUNT(*) FROM cascading_sasaran_opd s '
                . 'WHERE s.es3_indikator_id = i.id AND s.level = "pelaksana") AS pelaksana_count', false)
            ->where('i.cascading_sasaran_id', $id)
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

        $data = [
            'sasaran' => $sasaran,
            'indikator' => $indikator,
            'es3' => $es3,
            'indikator_es3' => $indikatorEs3
        ];
        if ($this->request->isAJAX()) {
            return view('adminOpd/cascading/_form_es4', $data);
        }
        return view('adminOpd/cascading/edit_es4', $data);
    }

    public function updateEs3($id)
    {
        $nama = $this->request->getPost('nama');
        $indikator = $this->request->getPost('indikator') ?? [];

        $this->db->transStart();

        // 1) Update nama sasaran (id sasaran tidak berubah).
        $this->db->table('cascading_sasaran_opd')
            ->where('id', $id)
            ->update(['nama_sasaran' => $nama]);

        // 2) Kumpulkan id indikator lama milik sasaran ini.
        $existingIds = array_map(
            static fn($r) => (int) $r['id'],
            $this->db->table('cascading_indikator_opd')
                ->select('id')
                ->where('cascading_sasaran_id', $id)
                ->get()->getResultArray()
        );

        // 3) Proses baris terkirim: UPDATE in-place (id dipertahankan -> Es4 tetap tertaut)
        //    atau INSERT untuk indikator baru (tanpa id).
        $postedIds = [];   // semua id yang MASIH ada di form (tidak dihapus via trash)
        foreach ($indikator as $ind) {
            $namaInd = trim($ind['nama'] ?? '');
            $indId   = (isset($ind['id']) && $ind['id'] !== '') ? (int) $ind['id'] : null;

            if ($indId) {
                $postedIds[] = $indId;
            }
            if ($namaInd === '') {
                // baris kosong: jangan diproses (biarkan data lama bila punya id).
                continue;
            }

            if ($indId && in_array($indId, $existingIds, true)) {
                $this->db->table('cascading_indikator_opd')
                    ->where('id', $indId)
                    ->update(['indikator' => $namaInd]);
            } else {
                $this->db->table('cascading_indikator_opd')->insert([
                    'cascading_sasaran_id' => $id,
                    'indikator'            => $namaInd,
                ]);
            }
        }

        // 4) Indikator yang DIHAPUS user (trash) = ada di DB tapi tak lagi terkirim.
        //    Cascade: hapus Es4 anaknya dulu (FK es3_indikator_id = SET NULL, jadi
        //    Es4 harus dihapus manual agar tidak menjadi orphan), lalu indikatornya.
        $removedIds = array_values(array_diff($existingIds, $postedIds));
        if (!empty($removedIds)) {
            $this->db->table('cascading_sasaran_opd')
                ->where('level', 'es4')
                ->whereIn('es3_indikator_id', $removedIds)
                ->delete(); // indikator Es4 ikut terhapus via FK ON DELETE CASCADE

            $this->db->table('cascading_indikator_opd')
                ->whereIn('id', $removedIds)
                ->delete();
        }

        $this->db->transComplete();

        // insert sasaran baru (jika ada)
        $sasaranBaru = $this->request->getPost('sasaran_baru');
        if (!empty($sasaranBaru)) {
            $currentSasaran = $this->db->table('cascading_sasaran_opd')
                ->where('id', $id)
                ->get()->getRowArray();
                
            if ($currentSasaran) {
                foreach ($sasaranBaru as $es3) {
                    if (empty($es3['nama'])) continue;

                    $this->db->table('cascading_sasaran_opd')->insert([
                        'opd_id' => $currentSasaran['opd_id'],
                        'renstra_indikator_sasaran_id' => $currentSasaran['renstra_indikator_sasaran_id'],
                        'parent_id' => null,
                        'level' => 'es3',
                        'nama_sasaran' => $es3['nama']
                    ]);
                    $newId = $this->db->insertID();

                    if (!empty($es3['indikator'])) {
                        foreach ($es3['indikator'] as $ind) {
                            if (empty($ind['nama'])) continue;
                            $this->db->table('cascading_indikator_opd')->insert([
                                'cascading_sasaran_id' => $newId,
                                'indikator' => $ind['nama']
                            ]);
                        }
                    }
                }
            }
        }

        if ($this->request->isAJAX()) {
            return $this->response->setJSON([
                'success' => $this->db->transStatus() !== false,
                'message' => 'Sasaran Eselon III berhasil diperbarui.',
            ]);
        }
        return redirect()->to('adminopd/cascading')
            ->with('success', 'Data berhasil diperbarui');
    }
    public function updateEs4($id)
    {
        $nama = $this->request->getPost('nama');
        $indikator = $this->request->getPost('indikator');

        $indikator = $indikator ?? [];

        $this->db->transStart();

        // update sasaran
        $this->db->table('cascading_sasaran_opd')
            ->where('id', $id)
            ->update([
                'nama_sasaran' => $nama
            ]);

        // Indikator ES IV di-UPDATE DI TEMPAT (id dipertahankan), bukan
        // dihapus-lalu-disisipkan-ulang. Sejak ada jenjang Pelaksana, baris
        // Pelaksana menempel ke id indikator ES IV — kalau id-nya berubah tiap
        // kali disunting, seluruh Pelaksana di bawahnya jadi yatim.
        // Pola ini sama dengan updateEs3().
        $existingIds = array_map(
            static fn ($r) => (int) $r['id'],
            $this->db->table('cascading_indikator_opd')
                ->select('id')
                ->where('cascading_sasaran_id', $id)
                ->get()->getResultArray()
        );

        $postedIds = [];
        foreach ($indikator as $i) {
            $namaInd = trim($i['nama'] ?? '');
            $indId   = (isset($i['id']) && $i['id'] !== '') ? (int) $i['id'] : null;

            if ($indId) {
                $postedIds[] = $indId;
            }
            if ($namaInd === '') {
                continue;
            }

            if ($indId && in_array($indId, $existingIds, true)) {
                $this->db->table('cascading_indikator_opd')
                    ->where('id', $indId)
                    ->update(['indikator' => $namaInd]);
            } else {
                $this->db->table('cascading_indikator_opd')->insert([
                    'cascading_sasaran_id' => $id,
                    'indikator'            => $namaInd,
                ]);
            }
        }

        // Indikator yang dihapus user: buang Pelaksana anaknya lebih dulu
        // (FK es3_indikator_id = ON DELETE SET NULL, jadi tidak otomatis ikut).
        $removedIds = array_values(array_diff($existingIds, $postedIds));
        if (!empty($removedIds)) {
            $this->db->table('cascading_sasaran_opd')
                ->where('level', 'pelaksana')
                ->whereIn('es3_indikator_id', $removedIds)
                ->delete(); // indikator Pelaksana ikut lewat FK ON DELETE CASCADE

            $this->db->table('cascading_indikator_opd')
                ->whereIn('id', $removedIds)
                ->delete();
        }

        // insert sasaran baru (jika ada)
        $sasaranBaru = $this->request->getPost('sasaran_baru');
        if (!empty($sasaranBaru)) {
            $currentSasaran = $this->db->table('cascading_sasaran_opd')
                ->where('id', $id)
                ->get()->getRowArray();
                
            if ($currentSasaran) {
                foreach ($sasaranBaru as $es4) {
                    if (empty($es4['nama'])) continue;

                    $this->db->table('cascading_sasaran_opd')->insert([
                        'opd_id' => $currentSasaran['opd_id'],
                        'renstra_indikator_sasaran_id' => $currentSasaran['renstra_indikator_sasaran_id'],
                        'parent_id' => $currentSasaran['parent_id'],
                        'es3_indikator_id' => $currentSasaran['es3_indikator_id'],
                        'level' => 'es4',
                        'nama_sasaran' => $es4['nama']
                    ]);
                    $newId = $this->db->insertID();

                    if (!empty($es4['indikator'])) {
                        foreach ($es4['indikator'] as $ind) {
                            if (empty($ind['nama'])) continue;
                            $this->db->table('cascading_indikator_opd')->insert([
                                'cascading_sasaran_id' => $newId,
                                'indikator' => $ind['nama']
                            ]);
                        }
                    }
                }
            }
        }

        $this->db->transComplete();

        if ($this->request->isAJAX()) {
            return $this->response->setJSON([
                'success' => $this->db->transStatus() !== false,
                'message' => 'Sasaran Eselon IV berhasil diperbarui.',
            ]);
        }
        return redirect()->to('adminopd/cascading')
            ->with('success', 'Data ESS IV berhasil diperbarui');
    }
    public function deleteEs3($id)
    {
        $isAjax = $this->request->isAJAX();

        // Otorisasi objek: pastikan sasaran milik OPD user sebelum dihapus (IDOR).
        $row = $this->db->table('cascading_sasaran_opd')->where('id', $id)->get()->getRowArray();
        if (!$row) {
            if ($isAjax) {
                return $this->response->setJSON(['success' => false, 'message' => 'Data tidak ditemukan.']);
            }
            return redirect()->to('adminopd/cascading')->with('error', 'Data tidak ditemukan.');
        }
        if (!$this->canAccessOpd($row['opd_id'] ?? null)) {
            if ($isAjax) {
                return $this->response->setStatusCode(403)
                    ->setJSON(['success' => false, 'message' => 'Anda tidak memiliki akses ke data OPD lain.']);
            }
            return redirect()->to('adminopd/cascading')->with('error', 'Anda tidak memiliki akses ke data OPD lain.');
        }

        $this->db->table('cascading_sasaran_opd')
            ->where('id', $id)
            ->delete();

        if ($isAjax) {
            return $this->response->setJSON(['success' => true, 'message' => 'Sasaran Eselon III berhasil dihapus.']);
        }
        return redirect()->to('adminopd/cascading')
            ->with('success', 'Data berhasil dihapus');
    }
    public function deleteEs4($id)
    {
        $isAjax = $this->request->isAJAX();

        // Otorisasi objek: pastikan sasaran milik OPD user sebelum dihapus (IDOR).
        $row = $this->db->table('cascading_sasaran_opd')->where('id', $id)->get()->getRowArray();
        if (!$row) {
            if ($isAjax) {
                return $this->response->setJSON(['success' => false, 'message' => 'Data tidak ditemukan.']);
            }
            return redirect()->to('adminopd/cascading')->with('error', 'Data tidak ditemukan.');
        }
        if (!$this->canAccessOpd($row['opd_id'] ?? null)) {
            if ($isAjax) {
                return $this->response->setStatusCode(403)
                    ->setJSON(['success' => false, 'message' => 'Anda tidak memiliki akses ke data OPD lain.']);
            }
            return redirect()->to('adminopd/cascading')->with('error', 'Anda tidak memiliki akses ke data OPD lain.');
        }

        $this->db->table('cascading_sasaran_opd')
            ->where('id', $id)
            ->delete();

        if ($isAjax) {
            return $this->response->setJSON(['success' => true, 'message' => 'Sasaran Eselon IV berhasil dihapus.']);
        }
        return redirect()->to('adminopd/cascading')
            ->with('success', 'Data berhasil dihapus');
    }
    /* =========================================================
     * PELAKSANA — jenjang di bawah Eselon IV / JF
     *
     * Strukturnya persis pola Es3 -> Es4: baris `cascading_sasaran_opd`
     * dengan level='pelaksana', menempel ke INDIKATOR ES IV lewat kolom
     * `es3_indikator_id` (kolom "indikator induk"). Indikatornya tetap di
     * `cascading_indikator_opd`.
     * =======================================================*/

    /**
     * Konteks satu indikator ES IV + pemeriksaan kepemilikan OPD.
     * Dipakai semua aksi Pelaksana supaya id dari URL tidak pernah dipercaya.
     */
    private function konteksIndikatorEs4(int $indikatorEs4Id): ?array
    {
        return $this->db->table('cascading_indikator_opd i')
            ->select('
                i.id  as es4_indikator_id,
                i.indikator as indikator_es4,
                s.id  as es4_id,
                s.opd_id,
                s.nama_sasaran as sasaran_es4,
                s.renstra_indikator_sasaran_id
            ')
            ->join('cascading_sasaran_opd s', 's.id = i.cascading_sasaran_id')
            ->where('i.id', $indikatorEs4Id)
            ->where('s.level', 'es4')
            ->get()
            ->getRowArray() ?: null;
    }

    public function tambahPelaksana($indikatorEs4Id = null)
    {
        $indikator = $this->konteksIndikatorEs4((int) $indikatorEs4Id);

        if (!$indikator) {
            return redirect()->back()->with('error', 'Indikator Eselon IV tidak ditemukan');
        }
        if (!$this->canAccessOpd($indikator['opd_id'] ?? null)) {
            return redirect()->to('adminopd/cascading')
                ->with('error', 'Anda tidak memiliki akses ke data OPD lain.');
        }

        return view('adminOpd/cascading/tambah_pelaksana', [
            'indikator' => $indikator,
            'periode'   => $this->request->getGet('periode'),
        ]);
    }

    public function savePelaksana()
    {
        $indikatorEs4Id = (int) $this->request->getPost('es4_indikator_id');
        $sasaranData    = $this->request->getPost('sasaran');

        $indikator = $this->konteksIndikatorEs4($indikatorEs4Id);
        if (!$indikator) {
            return redirect()->back()->with('error', 'Indikator Eselon IV tidak valid.');
        }
        if (!$this->canAccessOpd($indikator['opd_id'] ?? null)) {
            return redirect()->to('adminopd/cascading')
                ->with('error', 'Anda tidak memiliki akses ke data OPD lain.');
        }
        if (empty($sasaranData) || !is_array($sasaranData)) {
            return redirect()->back()->with('error', 'Data sasaran kosong');
        }

        // opd_id, parent, dan renstra indikator SELALU diturunkan dari induk di
        // database — tidak satu pun diambil dari request.
        $this->db->transStart();

        foreach ($sasaranData as $pel) {
            $namaSasaran = trim($pel['nama'] ?? '');
            if ($namaSasaran === '') {
                continue;
            }

            $this->db->table('cascading_sasaran_opd')->insert([
                'opd_id'                       => $indikator['opd_id'],
                'renstra_indikator_sasaran_id' => $indikator['renstra_indikator_sasaran_id'],
                'parent_id'                    => $indikator['es4_id'],
                'es3_indikator_id'             => $indikator['es4_indikator_id'],
                'level'                        => 'pelaksana',
                'nama_sasaran'                 => $namaSasaran,
            ]);

            $pelId = $this->db->insertID();

            foreach ($pel['indikator'] ?? [] as $ind) {
                $namaInd = trim($ind['nama'] ?? '');
                if ($namaInd === '') {
                    continue;
                }
                $this->db->table('cascading_indikator_opd')->insert([
                    'cascading_sasaran_id' => $pelId,
                    'indikator'            => $namaInd,
                ]);
            }
        }

        $this->db->transComplete();

        return redirect()->to('adminopd/cascading')
            ->with('success', 'Sasaran Pelaksana berhasil disimpan');
    }

    public function editPelaksana($id)
    {
        $sasaran = $this->db->table('cascading_sasaran_opd')
            ->where('id', $id)
            ->where('level', 'pelaksana')
            ->get()
            ->getRowArray();

        if (!$sasaran) {
            return redirect()->back()->with('error', 'Data tidak ditemukan');
        }
        if (!$this->canAccessOpd($sasaran['opd_id'] ?? null)) {
            if ($this->request->isAJAX()) {
                return $this->response->setStatusCode(403)
                    ->setJSON(['success' => false, 'message' => 'Anda tidak memiliki akses ke data OPD lain.']);
            }
            return redirect()->to('adminopd/cascading')
                ->with('error', 'Anda tidak memiliki akses ke data OPD lain.');
        }

        $data = [
            'sasaran'   => $sasaran,
            'indikator' => $this->db->table('cascading_indikator_opd')
                ->where('cascading_sasaran_id', $id)
                ->get()->getResultArray(),
            'es4' => $this->db->table('cascading_sasaran_opd')
                ->where('id', $sasaran['parent_id'])
                ->get()->getRowArray(),
            'indikator_es4' => $this->db->table('cascading_indikator_opd')
                ->where('id', $sasaran['es3_indikator_id'])
                ->get()->getRowArray(),
        ];

        if ($this->request->isAJAX()) {
            return view('adminOpd/cascading/_form_pelaksana', $data);
        }
        return view('adminOpd/cascading/edit_pelaksana', $data);
    }

    public function updatePelaksana($id)
    {
        $isAjax = $this->request->isAJAX();

        $sasaran = $this->db->table('cascading_sasaran_opd')
            ->where('id', $id)->where('level', 'pelaksana')
            ->get()->getRowArray();

        if (!$sasaran || !$this->canAccessOpd($sasaran['opd_id'] ?? null)) {
            $pesan = $sasaran ? 'Anda tidak memiliki akses ke data OPD lain.' : 'Data tidak ditemukan.';
            if ($isAjax) {
                return $this->response->setStatusCode($sasaran ? 403 : 404)
                    ->setJSON(['success' => false, 'message' => $pesan]);
            }
            return redirect()->to('adminopd/cascading')->with('error', $pesan);
        }

        $nama = trim((string) $this->request->getPost('nama'));
        if ($nama === '') {
            if ($isAjax) {
                return $this->response->setJSON(['success' => false, 'message' => 'Nama Sasaran Pelaksana wajib diisi.']);
            }
            return redirect()->back()->with('error', 'Nama Sasaran Pelaksana wajib diisi.');
        }

        $indikator = $this->request->getPost('indikator') ?? [];

        $this->db->transStart();

        $this->db->table('cascading_sasaran_opd')
            ->where('id', $id)
            ->update(['nama_sasaran' => $nama]);

        // Pelaksana adalah jenjang terakhir (tidak punya anak), tapi id
        // indikator tetap dipertahankan agar konsisten dengan jenjang di atas.
        $existingIds = array_map(
            static fn ($r) => (int) $r['id'],
            $this->db->table('cascading_indikator_opd')
                ->select('id')->where('cascading_sasaran_id', $id)
                ->get()->getResultArray()
        );

        $postedIds = [];
        foreach ($indikator as $ind) {
            $namaInd = trim($ind['nama'] ?? '');
            $indId   = (isset($ind['id']) && $ind['id'] !== '') ? (int) $ind['id'] : null;

            if ($indId) {
                $postedIds[] = $indId;
            }
            if ($namaInd === '') {
                continue;
            }

            if ($indId && in_array($indId, $existingIds, true)) {
                $this->db->table('cascading_indikator_opd')
                    ->where('id', $indId)->update(['indikator' => $namaInd]);
            } else {
                $this->db->table('cascading_indikator_opd')->insert([
                    'cascading_sasaran_id' => $id,
                    'indikator'            => $namaInd,
                ]);
            }
        }

        $removedIds = array_values(array_diff($existingIds, $postedIds));
        if (!empty($removedIds)) {
            $this->db->table('cascading_indikator_opd')->whereIn('id', $removedIds)->delete();
        }

        // ============================================================
        // SASARAN PELAKSANA BARU (sejenis) — pola kembar updateEs3/updateEs4.
        //
        // Ini pengganti tombol "+" yang dulu ada di kolom Aksi Pelaksana pada
        // tabel Cascading: tombol itu tetap tampil walau data sudah ada dan
        // berulang di setiap Sasaran Pelaksana, sehingga tidak konsisten dengan
        // Aksi ESS III / ESS IV yang hanya berisi edit + hapus.
        //
        // INDUK (opd_id, renstra_indikator_sasaran_id, parent_id, es3_indikator_id)
        // SELALU disalin dari baris Pelaksana yang sedang disunting — tidak
        // satu pun diambil dari request, sehingga tidak bisa dipakai
        // menggantungkan Pelaksana ke OPD / indikator ES IV lain.
        // ============================================================
        $sasaranBaru = $this->request->getPost('sasaran_baru');
        if (!empty($sasaranBaru) && is_array($sasaranBaru)) {
            foreach ($sasaranBaru as $pel) {
                $namaBaru = trim($pel['nama'] ?? '');
                if ($namaBaru === '') {
                    continue;
                }

                $this->db->table('cascading_sasaran_opd')->insert([
                    'opd_id'                       => $sasaran['opd_id'],
                    'renstra_indikator_sasaran_id' => $sasaran['renstra_indikator_sasaran_id'],
                    'parent_id'                    => $sasaran['parent_id'],
                    'es3_indikator_id'             => $sasaran['es3_indikator_id'],
                    'level'                        => 'pelaksana',
                    'nama_sasaran'                 => $namaBaru,
                ]);
                $pelBaruId = $this->db->insertID();

                foreach ($pel['indikator'] ?? [] as $ind) {
                    $namaInd = trim($ind['nama'] ?? '');
                    if ($namaInd === '') {
                        continue;
                    }
                    $this->db->table('cascading_indikator_opd')->insert([
                        'cascading_sasaran_id' => $pelBaruId,
                        'indikator'            => $namaInd,
                    ]);
                }
            }
        }

        $this->db->transComplete();

        if ($isAjax) {
            return $this->response->setJSON([
                'success' => $this->db->transStatus() !== false,
                'message' => 'Sasaran Pelaksana berhasil diperbarui.',
            ]);
        }
        return redirect()->to('adminopd/cascading')
            ->with('success', 'Sasaran Pelaksana berhasil diperbarui');
    }

    public function deletePelaksana($id)
    {
        $isAjax = $this->request->isAJAX();

        $row = $this->db->table('cascading_sasaran_opd')
            ->where('id', $id)->where('level', 'pelaksana')
            ->get()->getRowArray();

        if (!$row) {
            if ($isAjax) {
                return $this->response->setJSON(['success' => false, 'message' => 'Data tidak ditemukan.']);
            }
            return redirect()->to('adminopd/cascading')->with('error', 'Data tidak ditemukan.');
        }
        if (!$this->canAccessOpd($row['opd_id'] ?? null)) {
            if ($isAjax) {
                return $this->response->setStatusCode(403)
                    ->setJSON(['success' => false, 'message' => 'Anda tidak memiliki akses ke data OPD lain.']);
            }
            return redirect()->to('adminopd/cascading')->with('error', 'Anda tidak memiliki akses ke data OPD lain.');
        }

        // Indikator Pelaksana ikut terhapus lewat FK ON DELETE CASCADE.
        $this->db->table('cascading_sasaran_opd')->where('id', $id)->delete();

        if ($isAjax) {
            return $this->response->setJSON(['success' => true, 'message' => 'Sasaran Pelaksana berhasil dihapus.']);
        }
        return redirect()->to('adminopd/cascading')->with('success', 'Sasaran Pelaksana berhasil dihapus');
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

    /* Meta tabel cascading: implementasi bersama di CascadingOpdMetaTrait
       (dipakai juga admin_kab mode OPD & halaman publik). */

    private function preprocessEmptyIds(array &$rows): void
    {
        $this->cascOpdPreprocessEmptyIds($rows);
    }

    private function buildRowspanMeta($rows)
    {
        return $this->cascOpdRowspanMeta($rows);
    }

    private function buildFirstShowMeta($rows)
    {
        return $this->cascOpdFirstShowMeta($rows);
    }

    public function saveCsf()
    {
        $id     = $this->request->getPost('id');
        $csfVal = $this->request->getPost('csf');
        $level  = $this->request->getPost('level'); // es2, es3, or es4

        if ($level === 'es2') {
            $this->db->table('renstra_sasaran')
                ->where('id', $id)
                ->update(['csf' => $csfVal]);
        } elseif ($level === 'es3') {
            $this->db->table('cascading_sasaran_opd')
                ->where('id', $id)
                ->where('level', 'es3')
                ->update(['csf' => $csfVal]);
        } elseif ($level === 'es4' || $level === 'pelaksana') {
            $this->db->table('cascading_sasaran_opd')
                ->where('id', $id)
                ->where('level', $level)
                ->update(['csf' => $csfVal]);
        }

        return $this->response->setJSON(['status' => 'success']);
    }

}
