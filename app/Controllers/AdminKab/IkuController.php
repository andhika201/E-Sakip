<?php

namespace App\Controllers\AdminKab;

use App\Controllers\BaseController;
use App\Models\OpdModel;
use App\Models\Opd\IkuModel as OpdIkuModel;
use Config\Database;

class IkuController extends BaseController
{
    /** @var \CodeIgniter\Database\BaseConnection */
    protected $db;

    /** @var OpdModel */
    protected $opdModel;

    /** @var OpdIkuModel */
    protected $ikuModel;

    public function __construct()
    {
        $this->db = Database::connect();
        $this->opdModel = new OpdModel();
        $this->ikuModel = new OpdIkuModel();
    }

    /* =========================================================
     * INDEX – LIST IKU (MODE OPD / KABUPATEN)
     * =======================================================*/
    public function index()
    {
        $request = service('request');

        // mode tampilan: opd | kabupaten
        $mode = $request->getGet('mode') ?: 'opd';
        $opdFilter = $request->getGet('opd_id');
        $opdFilter = ($opdFilter === '') ? null : $opdFilter;

        // key periode (sekarang cuma 'all', tapi sudah ready kalau nanti dipecah)
        $periodeKey = $request->getGet('periode') ?? null;

        // 1. Periode & Tahun
// DULU: $grouped_data = $this->buildPeriodeOptions();
        $grouped_data = $this->ikuModel->getPeriodeOptions($mode);

        // tahun sekarang
        $currentYear = (int) date('Y');

        // ambil dari GET kalau ada
        $selected_periode = $request->getGet('periode') ?? null;

        // kalau kosong, otomatis pilih periode yang mengandung tahun berjalan
        if (empty($selected_periode) && !empty($grouped_data)) {

            // cari periode yang memuat tahun sekarang
            foreach ($grouped_data as $key => $periode) {
                $years = $periode['years'] ?? [];
                if (in_array($currentYear, $years, true)) {
                    $selected_periode = $key;
                    break;
                }
            }

            // kalau tidak ada yang mengandung tahun sekarang,
            // fallback ke periode pertama
            if (empty($selected_periode)) {
                $keys = array_keys($grouped_data);
                $selected_periode = $keys[0] ?? null;
            }
        }

        // ambil daftar tahun dari periode terpilih
        $yearsForSelected = [];
        if (!empty($selected_periode) && isset($grouped_data[$selected_periode])) {
            $yearsForSelected = $grouped_data[$selected_periode]['years'] ?? [];
        }


        // 2. semua IKU + program pendukung
        $iku_data = $this->ikuModel->getAllIkuWithPrograms();

        // 3. data RENSTRA (mode OPD) dan RPJMD (mode kabupaten)
        $renstra_data = $this->ikuModel->getRenstraMatrix($yearsForSelected);
        $rpjmd_data = $this->ikuModel->getRpjmdMatrix($yearsForSelected);

        // jika mode opd & ada filter OPD
        if ($mode === 'opd' && $opdFilter !== null) {
            $renstra_data = array_values(array_filter(
                $renstra_data,
                static function ($row) use ($opdFilter) {
                    return (int) ($row['opd_id'] ?? 0) === (int) $opdFilter;
                }
            ));
        }

        // 4. daftar OPD untuk dropdown filter
        $opdList = $this->opdModel
            ->orderBy('nama_opd', 'ASC')
            ->findAll();

        // 5. kirim ke view
        return view('adminKabupaten/iku/iku', [
            'title' => 'IKU - e-SAKIP',
            'mode' => $mode,
            'opdFilter' => $opdFilter,
            'opdList' => $opdList,
            'grouped_data' => $grouped_data,
            'selected_periode' => $selected_periode,
            'renstra_data' => $renstra_data,
            'rpjmd_data' => $rpjmd_data,
            'iku_data' => $iku_data,
        ]);
    }

    /* =========================================================
     * TAMBAH IKU
     * route contoh:
     *  GET  adminkab/iku/tambah/(:num)?mode=opd|kabupaten
     * =======================================================*/
    public function tambah($indikatorId)
    {
        $mode = $this->request->getGet('mode') ?: 'opd'; // opd | kabupaten
        $indikatorId = (int) $indikatorId;

        if ($mode === 'kabupaten') {
            // indikator RPJMD
            $indikator = $this->db->table('rpjmd_indikator_sasaran')
                ->where('id', $indikatorId)
                ->get()
                ->getRowArray();
        } else {
            // indikator RENSTRA
            $indikator = $this->db->table('renstra_indikator_sasaran')
                ->where('id', $indikatorId)
                ->get()
                ->getRowArray();
        }

        if (!$indikator) {
            return redirect()->back()->with('error', 'Indikator tidak ditemukan.');
        }

        return view('adminKabupaten/iku/tambah_iku', [
            'title' => 'Tambah IKU',
            'mode' => $mode,
            'indikator' => $indikator,
        ]);
    }


    /* =========================================================
     * SIMPAN IKU BARU
     * POST adminkab/iku/save
     * =======================================================*/
    public function save()
    {
        $mode = $this->request->getPost('mode') ?: 'opd';
        $definisi = trim($this->request->getPost('definisi') ?? '');
        $programs = $this->request->getPost('program_pendukung') ?? [];

        if ($definisi === '') {
            return redirect()->back()->withInput()->with('error', 'Definisi operasional wajib diisi.');
        }

        if ($mode === 'kabupaten') {
            $indikatorId = (int) $this->request->getPost('rpjmd_id');

            if (!$indikatorId) {
                return redirect()->back()->with('error', 'Indikator RPJMD tidak valid.');
            }

            $data = [
                'rpjmd_id' => $indikatorId,
                'renstra_id' => null,
                'definisi' => $definisi,
                'status' => 'belum',
                'program_pendukung' => $programs,
            ];
        } else {
            $indikatorId = (int) $this->request->getPost('renstra_id');

            if (!$indikatorId) {
                return redirect()->back()->with('error', 'Indikator RENSTRA tidak valid.');
            }

            $data = [
                'renstra_id' => $indikatorId,
                'rpjmd_id' => null,
                'definisi' => $definisi,
                'status' => 'belum',
                'program_pendukung' => $programs,
            ];
        }

        $this->ikuModel->createCompleteIku($data);

        return redirect()->to(base_url('adminkab/iku?mode=' . $mode))
            ->with('success', 'IKU berhasil disimpan.');
    }


    /* =========================================================
     * EDIT IKU
     * route:
     *  GET adminkab/iku/edit/(:num)?mode=opd|kabupaten
     * $indikatorId = id indikator renstra / rpjmd
     * =======================================================*/
    public function edit($indikatorId)
    {
        $request = service('request');
        $mode = $request->getGet('mode') ?: 'opd';

        $indikator = $this->getIndikatorById((int) $indikatorId, $mode);
        if (!$indikator) {
            session()->setFlashdata('error', 'Indikator tidak ditemukan.');
            return redirect()->to(base_url('adminkab/iku'));
        }

        // ambil data IKU terkait indikator ini
        $role = ($mode === 'kabupaten') ? 'admin_kab' : 'admin_opd';
        $iku_data = $this->ikuModel->getIkuDetail((int) $indikatorId, $role);

        return view('adminKabupaten/iku/edit_iku', [
            'title' => 'Edit IKU',
            'indikator' => $indikator,
            'iku_data' => $iku_data,
            'mode' => $mode,
            'role' => $role,
        ]);
    }

    /* =========================================================
     * UPDATE IKU
     * POST adminkab/iku/update
     * =======================================================*/
    public function update()
    {
        $request = service('request');
        $ikuId = (int) $request->getPost('iku_id');
        $definisi = trim($request->getPost('definisi') ?? '');
        $renstraId = $request->getPost('renstra_indikator_sasaran_id');
        $rpjmdId = $request->getPost('rpjmd_id');   // kalau nanti kamu tambahkan hidden ini di edit Kab
        $mode = $request->getPost('mode') ?? 'opd';

        if ($definisi === '' || !$ikuId) {
            session()->setFlashdata('error', 'Definisi dan ID IKU wajib diisi.');
            return redirect()->back()->withInput();
        }

        $dataUpdate = [
            'definisi' => $definisi,
        ];
        if (!empty($renstraId)) {
            $dataUpdate['renstra_id'] = $renstraId;
        }
        if (!empty($rpjmdId)) {
            $dataUpdate['rpjmd_id'] = $rpjmdId;
        }

        try {
            // update data utama IKU
            $this->ikuModel->updateIku($ikuId, $dataUpdate);

            // update program pendukung
            $programs = $request->getPost('program_pendukung') ?? [];
            $programIds = $request->getPost('program_id') ?? [];
            $this->ikuModel->updateProgramPendukung($ikuId, $programs, $programIds);

            session()->setFlashdata('success', 'IKU berhasil diperbarui.');
        } catch (\Throwable $e) {
            session()->setFlashdata('error', 'Gagal mengupdate IKU: ' . $e->getMessage());
        }

        return redirect()->to(base_url('adminkab/iku?mode=' . $mode));
    }

    /* =========================================================
     * UBAH STATUS IKU (BELUM <-> TERCAPAI)
     * route: GET adminkab/iku/change_status/(:num)
     * $id = ID IKU
     * =======================================================*/
    public function change_status($indikatorId)
    {
        $mode = $this->request->getGet('mode') ?: 'opd';
        $indikatorId = (int) $indikatorId;

        $builder = $this->db->table('iku');

        if ($mode === 'kabupaten') {
            $builder->where('rpjmd_id', $indikatorId);
        } else {
            $builder->where('renstra_id', $indikatorId);
        }

        $iku = $builder->get()->getRowArray();

        if (!$iku) {
            return redirect()->back()->with('error', 'IKU belum dibuat untuk indikator ini.');
        }

        $currentStatus = strtolower($iku['status'] ?? 'belum');
        $newStatus = ($currentStatus === 'tercapai') ? 'belum' : 'tercapai';

        // pakai helper updateIku yang sudah ada di model
        $this->ikuModel->updateIku($iku['id'], ['status' => $newStatus]);

        return redirect()->to(base_url('adminkab/iku?mode=' . $mode))
            ->with('success', 'Status IKU berhasil diubah.');
    }


    /* =========================================================
     * HAPUS IKU
     * route: POST /adminkab/iku/delete/(:num)
     * =======================================================*/
    public function delete($id)
    {
        try {
            $this->ikuModel->deleteIkuComplete((int) $id);
            session()->setFlashdata('success', 'IKU berhasil dihapus.');
        } catch (\Throwable $e) {
            session()->setFlashdata('error', 'Gagal menghapus IKU: ' . $e->getMessage());
        }

        return redirect()->to(base_url('adminkab/iku'));
    }

    /* =========================================================
     * HELPER PRIVATE – AMBIL DATA INDIKATOR
     * =======================================================*/
    protected function getIndikatorById(int $indikatorId, string $mode = 'opd'): ?array
    {
        if ($mode === 'kabupaten') {
            // indikator RPJMD
            return $this->db->table('rpjmd_indikator_sasaran')
                ->where('id', $indikatorId)
                ->get()
                ->getRowArray() ?: null;
        }

        // indikator RENSTRA (default mode opd)
        return $this->db->table('renstra_indikator_sasaran')
            ->where('id', $indikatorId)
            ->get()
            ->getRowArray() ?: null;
    }
}
