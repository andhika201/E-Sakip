<?php

namespace App\Controllers\AdminKab;

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

        // 🔒 Cek login (hanya non-admin_kab yang wajib punya OPD ID)
        if (!$opdId && $role !== 'admin_kab') {
            return redirect()->to('/login')->with('error', 'Silakan login terlebih dahulu.');
        }

        // 🧭 Ambil parameter GET
        $periode = $this->request->getGet('periode');

        // 🗓️ Ambil daftar periode unik
        $periodeList = $this->db->table('renstra_sasaran')
            ->select('tahun_mulai, tahun_akhir')
            ->groupBy('tahun_mulai, tahun_akhir')
            ->orderBy('tahun_mulai', 'ASC')
            ->get()
            ->getResultArray();

        // Susun array periode
        $grouped_data = [];
        foreach ($periodeList as $p) {
            $years = range((int) $p['tahun_mulai'], (int) $p['tahun_akhir']);
            $key = "{$p['tahun_mulai']}-{$p['tahun_akhir']}";
            $grouped_data[$key] = [
                'period' => $key,
                'years' => $years
            ];
        }

        // 📊 Ambil data Renstra sesuai role
        if ($role === 'admin_kab') {
            // admin_kab bisa lihat semua OPD
            $renstraData = $this->renstraModel->getAllSasaranWithIndikatorAndTarget();
        } else {
            // role opd dibatasi oleh opd_id
            $renstraData = $this->renstraModel->getAllSasaranWithIndikatorAndTarget($opdId);
        }

        // 🎯 Filter berdasarkan periode (jika dipilih)
        if (!empty($periode) && preg_match('/^\d{4}-\d{4}$/', $periode)) {
            [$tahunMulai, $tahunAkhir] = explode('-', $periode);

            $renstraData = array_filter($renstraData, function ($sasaran) use ($tahunMulai, $tahunAkhir) {
                return (
                    (int) $sasaran['tahun_mulai'] === (int) $tahunMulai &&
                    (int) $sasaran['tahun_akhir'] === (int) $tahunAkhir
                );
            });

            // Sesuaikan header tabel hanya untuk periode ini
            $grouped_data = [
                $periode => [
                    'period' => $periode,
                    'years' => range((int) $tahunMulai, (int) $tahunAkhir)
                ]
            ];
        }

        // 🧩 Ambil data IKU berdasarkan role
        $ikuData = ($role === 'admin_kab')
            ? $this->ikuModel->getRPJMDWithPrograms() // semua OPD
            : $this->ikuModel->getRenstraWithPrograms($opdId); // hanya opd aktif

        // 📤 Kirim ke view
        return view('adminKabupaten/iku/iku', [
            'title' => 'Indikator Kinerja Utama (IKU)',
            'renstra_data' => $renstraData,
            'iku_data' => $ikuData,
            'grouped_data' => $grouped_data,
            'selected_periode' => $periode,
            'role' => $role,
        ]);
    }
    public function tambah($indikatorId = null)
    {
        $session = session();
        $role = $session->get('role');
        $opdId = $session->get('opd_id');

        // ✅ Cek login — hanya admin_opd wajib punya opd_id
        if (empty($opdId) && $role !== 'admin_kab') {
            return redirect()->to('/login')->with('error', 'Silakan login terlebih dahulu.');
        }

        // ✅ Hanya admin_kab yang boleh akses tambah IKU kabupaten
        if ($role !== 'admin_kab') {
            return redirect()->back()->with('error', 'Akses ditolak. Hanya Admin Kabupaten yang dapat menambah IKU.');
        }

        // ✅ Validasi ID indikator
        if (empty($indikatorId)) {
            return redirect()->back()->with('error', 'ID indikator tidak valid.');
        }

        // ✅ Ambil data indikator dari tabel RPJMD
        $indikator = $this->db->table('rpjmd_indikator_sasaran')
            ->where('id', $indikatorId)
            ->get()
            ->getRowArray();

        if (!$indikator) {
            return redirect()->back()->with('error', 'Indikator tidak ditemukan.');
        }

        // ✅ Kirim data ke view
        return view('adminKabupaten/iku/tambah_iku', [
            'title' => 'Tambah IKU',
            'indikator' => $indikator,
            'role' => $role,
            'validation' => \Config\Services::validation(),
        ]);
    }
    public function save()
    {
        try {
            // Ambil data POST
            $data = $this->request->getPost();
            $session = session();
            $opdId = $session->get('opd_id');
            $role = $session->get('role');

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
            // 🔁 Redirect berdasarkan role
            return redirect()->to(base_url('adminkab/iku'));
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
        $role = $session->get('role');
        $opdId = $session->get('opd_id');
        $db = \Config\Database::connect();

        if (empty($opdId) && $role !== 'admin_kab') {
            return redirect()->to('/login')->with('error', 'Silakan login terlebih dahulu.');
        }

        if (empty($indikatorId)) {
            return redirect()->back()->with('error', 'ID indikator tidak valid.');
        }

        // ✅ Ambil data indikator sesuai role
        if ($role === 'admin_kab') {
            // Coba ambil dari renstra jika rpjmd kosong
            $indikator = $db->table('rpjmd_indikator_sasaran')->where('id', $indikatorId)->get()->getRowArray();

            // Jika tidak ditemukan di RPJMD, fallback ke Renstra
            if (!$indikator) {
                $indikator = $db->table('renstra_indikator_sasaran')->where('id', $indikatorId)->get()->getRowArray();
            }
        } else {
            $indikator = $db->table('renstra_indikator_sasaran')->where('id', $indikatorId)->get()->getRowArray();
        }

        if (!$indikator) {
            return redirect()->back()->with('error', 'Indikator tidak ditemukan di data RPJMD maupun Renstra.');
        }
        // dd($indikator);
        // Ambil data IKU
        $ikuData = $this->ikuModel->getIkuDetail($indikatorId, $role);
        // dd($ikuData);
        return view('adminKabupaten/iku/edit_iku', [
            'title' => 'Edit IKU',
            'indikator' => $indikator,
            'iku_data' => $ikuData,
            'role' => $role,
            'validation' => \Config\Services::validation(),
        ]);
    }
    /**
     * 📝 Update IKU
     */
    public function update()
    {
        $session = session();
        $role = $session->get('role');
        $opdId = $session->get('opd_id');

        if (empty($opdId) && $role !== 'admin_kab') {
            return redirect()->to('/login')->with('error', 'Silakan login terlebih dahulu');
        }

        $data = $this->request->getPost();
        $ikuId = $data['iku_id'] ?? null;

        if (!$ikuId) {
            session()->setFlashdata('error', 'ID IKU tidak ditemukan');
            return redirect()->back()->withInput();
        }

        $this->db->transStart();

        try {
            // Update definisi
            $updateData = [
                'definisi' => trim($data['definisi'] ?? ''),
                // 'updated_at' => date('Y-m-d H:i:s'),
            ];
            $this->ikuModel->updateIku($ikuId, $updateData);

            // Update program pendukung
            $programs = $data['program_pendukung'] ?? [];
            $programIds = $data['program_id'] ?? [];
            $this->ikuModel->updateProgramPendukung($ikuId, $programs, $programIds);

            $this->db->transComplete();

            if ($this->db->transStatus() === false) {
                throw new \Exception('Transaksi gagal.');
            }

            session()->setFlashdata('success', 'Data IKU berhasil diperbarui.');
        } catch (\Throwable $e) {
            $this->db->transRollback();
            session()->setFlashdata('error', 'Gagal menyimpan data: ' . $e->getMessage());
        }

        return redirect()->to(base_url('adminkab/iku'));
    }
}