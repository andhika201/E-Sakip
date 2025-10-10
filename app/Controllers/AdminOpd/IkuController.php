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

        // Ambil parameter GET
        $periode = $this->request->getGet('periode');

        // Ambil daftar periode unik untuk dropdown
        $periodeList = $this->db->table('renstra_sasaran')
            ->select('tahun_mulai, tahun_akhir')
            ->groupBy('tahun_mulai, tahun_akhir')
            ->orderBy('tahun_mulai', 'ASC')
            ->get()->getResultArray();

        // Buat array periode untuk dropdown dan header tabel
        $grouped_data = [];
        foreach ($periodeList as $p) {
            $years = range($p['tahun_mulai'], $p['tahun_akhir']);
            $key = "{$p['tahun_mulai']}-{$p['tahun_akhir']}";
            $grouped_data[$key] = [
                'period' => $key,
                'years' => $years
            ];
        }

        // 🔽 Filter Renstra berdasarkan periode yang dipilih
        $renstraData = $this->renstraModel->getAllSasaranWithIndikatorAndTarget($opdId);

        if (!empty($periode) && strpos($periode, '-') !== false) {
            [$tahunMulai, $tahunAkhir] = explode('-', $periode);

            // Filter hanya sasaran dengan tahun sesuai periode
            $renstraData = array_filter($renstraData, function ($sasaran) use ($tahunMulai, $tahunAkhir) {
                return (
                    $sasaran['tahun_mulai'] == (int) $tahunMulai &&
                    $sasaran['tahun_akhir'] == (int) $tahunAkhir
                );
            });

            // Sesuaikan header tahun di tabel
            $grouped_data = [
                $periode => [
                    'period' => $periode,
                    'years' => range($tahunMulai, $tahunAkhir)
                ]
            ];
        }

        // Ambil data IKU (tetap semua, karena nanti dicocokkan per indikator)
        $ikuData = ($role === 'admin_kab')
            ? $this->ikuModel->getRPJMDWithPrograms()
            : $this->ikuModel->getRenstraWithPrograms($opdId);

        // Kirim ke view
        return view('adminOpd/iku/iku', [
            'title' => 'Indikator Kinerja Utama',
            'renstra_data' => $renstraData,
            'iku_data' => $ikuData,
            'grouped_data' => $grouped_data,
            'selected_periode' => $periode,
            'role' => $role,
        ]);
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
        $role = $session->get('role');

        if (!$opdId) {
            return redirect()->to('/login')->with('error', 'Silakan login terlebih dahulu');
        }

        // Ambil data indikator
        $table = ($role === 'admin_kab')
            ? 'rpjmd_indikator_sasaran'
            : 'renstra_indikator_sasaran';

        $indikator = $this->db->table($table)
            ->where('id', $indikatorId)
            ->get()
            ->getRowArray();

        if (!$indikator) {
            return redirect()->back()->with('error', 'Indikator tidak ditemukan.');
        }

        $ikuData = $this->ikuModel->getIkuDetail($indikatorId, $role);

        $data = [
            'title' => 'Edit IKU',
            'indikator' => $indikator,
            'iku_data' => $ikuData,
            'validation' => \Config\Services::validation(),
        ];

        return view('adminOpd/iku/edit_iku', $data);
    }

    /**
     * ==========================
     * UPDATE DATA IKU
     * ==========================
     */
    public function update()
    {
        $session = session();
        $opdId = $session->get('opd_id');
        if (!$opdId) {
            return redirect()->to('/login')->with('error', 'Silakan login terlebih dahulu');
        }

        $data = $this->request->getPost();
        $ikuId = $data['iku_id'] ?? null;

        if (!$ikuId) {
            session()->setFlashdata('error', 'ID IKU tidak ditemukan');
            return redirect()->back()->withInput();
        }

        try {
            // Update definisi IKU
            $updateData = [
                'definisi' => $data['definisi'] ?? null,
            ];
            $this->ikuModel->updateIku($ikuId, $updateData, 'id');

            // Update program pendukung
            $programs = $data['program_pendukung'] ?? [];
            $programIds = $data['program_id'] ?? [];
            $this->ikuModel->updateProgramPendukung($ikuId, $programs, $programIds);

            session()->setFlashdata('success', 'Data IKU berhasil diperbarui');
        } catch (\Throwable $e) {
            session()->setFlashdata('error', 'Gagal mengupdate data IKU: ' . $e->getMessage());
        }

        return redirect()->to(base_url('adminopd/iku'));
    }

    /**
     * ==========================
     * HAPUS IKU
     * ==========================
     */
    public function delete($id)
    {
        try {
            $this->ikuModel->deleteIkuComplete($id);
            session()->setFlashdata('success', 'Data IKU berhasil dihapus.');
        } catch (\Throwable $e) {
            session()->setFlashdata('error', 'Gagal menghapus IKU: ' . $e->getMessage());
        }
        return redirect()->to(base_url('adminopd/iku'));
    }
}