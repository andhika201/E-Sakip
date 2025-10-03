<?php

namespace App\Controllers\AdminOpd;

use App\Controllers\BaseController;
use App\Models\Opd\RenstraModel;
use App\Models\Opd\IkuModel;
use App\Models\RpjmdModel;
use App\Models\OpdModel;

class IkuController extends BaseController
{
    protected $renstraModel;
    protected $rpjmdModel;
    protected $ikuModel;
    protected $opdModel;

    protected $db;

    public function __construct()
    {
        $this->renstraModel = new RenstraModel();
        $this->rpjmdModel = new RpjmdModel();
        $this->ikuModel = new IkuModel();
        $this->opdModel = new OpdModel();
        $this->db = \Config\Database::connect();

    }

    public function index()
    {
        $session = session();

        $opdId = $session->get('opd_id');
        $role = $session->get('role');
        if (!$opdId) {
            return redirect()->to('/login')->with('error', 'Silakan login terlebih dahulu');
        }

        $status = $this->request->getGet('status');
        $periode = $this->request->getGet('periode');

        $renstraData = $this->renstraModel->getAllRenstra($opdId, null, $periode, $status);
        $rpjmdData = $this->rpjmdModel->getSasaranWithIndikatorAndTarget();

        // dd($rpjmdData);
        // Ambil daftar periode unik untuk dropdown filter
        $db = \Config\Database::connect();
        $periodeList = $db->table('renstra_sasaran')
            ->select('tahun_mulai, tahun_akhir')
            ->groupBy('tahun_mulai, tahun_akhir')
            ->get()->getResultArray();

        // Grouped data untuk header tahun
        $grouped_data = [];
        foreach ($periodeList as $p) {
            $years = [];
            for ($y = $p['tahun_mulai']; $y <= $p['tahun_akhir']; $y++) {
                $years[] = $y;
            }
            $key = $p['tahun_mulai'] . '-' . $p['tahun_akhir'];
            $grouped_data[$key] = [
                'period' => $key,
                'years' => $years
            ];
        }

        if ($role === 'admin_kab') {
            $ikuData = $this->ikuModel->getRPJMDWithPrograms();
        } else {
            $ikuData = $this->ikuModel->getRenstraWithPrograms($opdId);
        }

        dd($ikuData);

        $data = [
            'renstra_data' => $renstraData,
            'rpjmd_data' => $rpjmdData,
            'title' => 'Indikator Kinerja Utama',
            'iku_data' => $ikuData,
            'grouped_data' => $grouped_data,
            'selected_opd' => $opdId,
            'role' => $role,
            'selected_status' => $status,
            'selected_periode' => $periode
        ];

        return view('adminOpd/iku/iku', $data);
    }

    /**
     * Tampilkan form tambah IKU
     * - Hanya bisa diakses jika user sudah login
     * - Mengambil data indikator berdasarkan parameter GET 'indikator'
     * - Jika indikator tidak ditemukan, tampilkan pesan error
     * - Kirim data renstra_sasaran ke view untuk dropdown
     */
    public function tambah($indikatorId = null)
    {
        $session = session();
        $opdId = $session->get('opd_id');
        $role = $session->get('role');

        $status = 'selesai';

        // Cek autentikasi
        if (!$opdId) {
            return redirect()->to('/login')->with('error', 'Silakan login terlebih dahulu');
        }

        // Ambil data indikator berdasarkan $indikatorId
        $indikator = null;
        if ($indikatorId) {
            $db = \Config\Database::connect();
            if ($role == 'admin_kab') {
                // Ambil dari tabel RPJMD
                $indikator = $db->table('rpjmd_indikator_sasaran')
                    ->where('id', $indikatorId)
                    ->get()
                    ->getRowArray();
            } else {
                // Default admin_opd ambil dari tabel Renstra
                $indikator = $db->table('renstra_indikator_sasaran')
                    ->where('id', $indikatorId)
                    ->get()
                    ->getRowArray();
            }
        }

        // Siapkan data untuk view
        $data = [
            'indikator' => $indikator,
            'title' => 'Tambah IKU',
            'role' => $role,
            'validation' => \Config\Services::validation(),
            // 'indikator' => $indikator, // Jika ingin mengirim data indikator ke view
        ];
        // dd($indikator);
        return view('adminOpd/iku/tambah_iku', $data);
    }

    public function save()
    {
        try {
            // Ambil data POST
            $data = $this->request->getPost();
            $session = session();
            $opdId = $session->get('opd_id');

            // Cek autentikasi
            if (!$opdId) {
                return redirect()->to('/login')->with('error', 'Silakan login terlebih dahulu');
            }

            // Validasi minimal field wajib
            if (empty($data['definisi'])) {
                throw new \Exception('Definisi IKU wajib diisi.');
            }

            // Kirim data langsung ke model
            $this->ikuModel->createCompleteIku([
                'definisi' => $data['definisi'],
                'rpjmd_id' => $data['rpjmd_id'] ?? null,   // isi jika admin kabupaten
                'renstra_id' => $data['renstra_indikator_sasaran_id'] ?? null, // isi jika admin opd
                'program_pendukung' => $data['program_pendukung'] ?? [] // array
            ]);

            // Sukses
            session()->setFlashdata('success', 'IKU berhasil ditambahkan.');
            return redirect()->to(base_url('adminopd/iku'));
        } catch (\Exception $e) {
            // Gagal
            log_message('error', '[IKU SAVE ERROR] ' . $e->getMessage() . ' | Trace: ' . $e->getTraceAsString());
            session()->setFlashdata('error', 'Gagal menambahkan data IKU: ' . $e->getMessage());
            return redirect()->back()->withInput();
        }
    }

    public function edit($indikatorId = null)
    {
        $session = session();
        $opdId = $session->get('opd_id');

        if (!$opdId) {
            return redirect()->to('/login')->with('error', 'Silakan login terlebih dahulu');
        }

        // Ambil data indikator berdasarkan $indikatorId
        $db = \Config\Database::connect();
        $indikator = $db->table('renstra_indikator_sasaran')
            ->where('id', $indikatorId)
            ->get()
            ->getRowArray();

        if (!$indikator) {
            return redirect()->back()->with('error', 'Indikator tidak ditemukan.');
        }

        $ikuData = $this->ikuModel->getIkuDetail($indikatorId);

        $renstraSasaran = $this->renstraModel->getAllRenstraByStatus('selesai', $opdId);

        $data = [
            'title' => 'Edit IKU',
            'iku_data' => $ikuData,   // null kalau belum ada
            'indikator' => $indikator, // data indikator renstra
            'renstra_sasaran' => $renstraSasaran,
            'validation' => \Config\Services::validation()
        ];

        // dd($ikuData);

        return view('adminOpd/iku/edit_iku', $data);
    }

    public function update()
    {
        try {
            $data = $this->request->getPost();
            $session = session();
            $opdId = $session->get('opd_id');


            // dd($data);
            if (!$opdId) {
                return redirect()->to('/login')->with('error', 'Silakan login terlebih dahulu');
            }

            // Ambil id dari form
            $id = $data['renstra_indikator_sasaran_id'] ?? null;
            if (!$id) {
                session()->setFlashdata('error', 'ID IKU tidak ditemukan');
                return redirect()->back()->withInput();
            }
            $ikuid = $data['iku_id'] ?? null;

            // Data untuk tabel utama IKU
            $updateData = [
                'definisi' => $data['definisi'] ?? null,
            ];

            // Update ke tabel iku
            $this->ikuModel->updateIku($id, $updateData);

            // Update program pendukung (hapus dulu → insert ulang)
            if (!empty($data['program_pendukung'])) {
                $this->ikuModel->updateProgramPendukung($ikuid, $data['program_pendukung']);
            }

            session()->setFlashdata('success', 'Data IKU berhasil diperbarui');
        } catch (\Exception $e) {
            session()->setFlashdata('error', 'Gagal mengupdate data IKU: ' . $e->getMessage());
            return redirect()->back()->withInput();
        }

        return redirect()->to(base_url('adminopd/iku'));
    }


    public function delete($id)
    {
        try {
            $this->ikuModel->delete($id);
            session()->setFlashdata('success', 'Data IKU berhasil dihapus');
        } catch (\Exception $e) {
            session()->setFlashdata('error', 'Gagal menghapus data IKU: ' . $e->getMessage());
        }
        return redirect()->to(base_url('adminopd/iku'));
    }
}
