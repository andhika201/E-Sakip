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
    private function xssRule(): string
    {
        return 'regex_match[/^(?!.*<\s*script\b)(?!.*<\/\s*script\s*>)(?!.*javascript\s*:)(?!.*data\s*:\s*text\/html)(?!.*on\w+\s*=)(?!.*<\?php)(?!.*<\?).*$/is]';
    }

    private function isSafeText($val): bool
    {
        if ($val === null || $val === '')
            return true;
        return (bool) preg_match('/^(?!.*<\s*script\b)(?!.*<\/\s*script\s*>)(?!.*javascript\s*:)(?!.*data\s*:\s*text\/html)(?!.*on\w+\s*=)(?!.*<\?php)(?!.*<\?).*$/is', (string) $val);
    }

    /**
     * INDEX IKU
     */
    public function index()
    {
        $session = session();
        $opdId = $session->get('opd_id');
        $role = $session->get('role');

        if (!$opdId && $role !== 'admin_kab') {
            return redirect()->to('/login')->with('error', 'Silakan login terlebih dahulu');
        }

        // Ambil parameter GET (kalau user pilih manual)
        $periode = $this->request->getGet('periode');

        // Ambil daftar periode unik dari renstra_sasaran
        $periodeList = $this->db->table('renstra_sasaran')
            ->select('tahun_mulai, tahun_akhir')
            ->groupBy('tahun_mulai, tahun_akhir')
            ->orderBy('tahun_mulai', 'ASC')
            ->get()
            ->getResultArray();

        // Susun grouped_data: key "2025-2029" => [period, years => [2025..2029]]
        $grouped_data = [];
        foreach ($periodeList as $p) {
            $years = range($p['tahun_mulai'], $p['tahun_akhir']);
            $key = "{$p['tahun_mulai']}-{$p['tahun_akhir']}";
            $grouped_data[$key] = [
                'period' => $key,
                'years' => $years,
            ];
        }

        // ============================
        // AUTO PILIH PERIODE TAHUN INI
        // ============================
        $currentYear = (int) date('Y');

        if (empty($periode) && !empty($grouped_data)) {
            // Cari periode yang meng-cover tahun sekarang
            foreach ($grouped_data as $key => $p) {
                $start = (int) min($p['years']);
                $end = (int) max($p['years']);
                if ($currentYear >= $start && $currentYear <= $end) {
                    $periode = $key;
                    break;
                }
            }

            // Kalau tidak ada yang cocok, pakai periode pertama
            if (empty($periode)) {
                $periode = array_key_first($grouped_data);
            }
        }

        // Data renstra & rpjmd lengkap dulu
        $renstraData = $this->renstraModel->getAllSasaranWithIndikatorAndTarget($opdId);
        $rpjmdData = $this->rpjmdModel->getSasaranWithIndikatorAndTarget();

        // Filter renstra sesuai periode yang terpilih
        if (!empty($periode) && strpos($periode, '-') !== false) {
            [$tahunMulai, $tahunAkhir] = explode('-', $periode);

            $renstraData = array_filter($renstraData, function ($sasaran) use ($tahunMulai, $tahunAkhir) {
                return (
                    (int) $sasaran['tahun_mulai'] === (int) $tahunMulai &&
                    (int) $sasaran['tahun_akhir'] === (int) $tahunAkhir
                );
            });

            // Header tahun di tabel hanya periode terpilih
            $grouped_data = [
                $periode => [
                    'period' => $periode,
                    'years' => range($tahunMulai, $tahunAkhir),
                ],
            ];
        }

        // Ambil data IKU (RPJMD atau RENSTRA)
        $ikuData = ($role === 'admin_kab')
            ? $this->ikuModel->getRPJMDWithPrograms()
            : $this->ikuModel->getRenstraWithPrograms($opdId);

        return view('adminOpd/iku/iku', [
            'title' => 'Indikator Kinerja Utama',
            'renstra_data' => $renstraData,
            'rpjmd_data' => $rpjmdData,
            'iku_data' => $ikuData,
            'grouped_data' => $grouped_data,
            'selected_periode' => $periode,   // sudah pasti terisi (auto)
            'role' => $role,
        ]);
    }

    /**
     * FORM TAMBAH IKU
     */
    public function tambah($indikatorId = null)
    {
        $session = session();
        $opdId = $session->get('opd_id');
        $role = $session->get('role');

        if (!$opdId && $role !== 'admin_kab') {
            return redirect()->to('/login')->with('error', 'Silakan login terlebih dahulu');
        }

        $indikator = null;
        if ($indikatorId) {
            if ($role === 'admin_kab') {
                $indikator = $this->db->table('rpjmd_indikator_sasaran')
                    ->where('id', $indikatorId)
                    ->get()
                    ->getRowArray();
            } else {
                $indikator = $this->db->table('renstra_indikator_sasaran')
                    ->where('id', $indikatorId)
                    ->get()
                    ->getRowArray();
            }
        }

        $data = [
            'indikator' => $indikator,
            'title' => 'Tambah IKU',
            'role' => $role,
            'validation' => \Config\Services::validation(),
        ];

        return view('adminOpd/iku/tambah_iku', $data);
    }

    /**
     * SIMPAN IKU BARU
     */
    public function save()
    {
        try {
            $data = $this->request->getPost();
            $session = session();
            $opdId = $session->get('opd_id');
            $role = $session->get('role');

            if (!$opdId && $role !== 'admin_kab') {
                return redirect()->to('/login')->with('error', 'Silakan login terlebih dahulu');
            }

            $rx = $this->xssRule();

            // ============================
            // VALIDASI (ANTI XSS/SCRIPT)
            // ============================
            $rules = [
                'definisi' => 'required|string|max_length[10000]|' . $rx,
                'rpjmd_id' => 'permit_empty|integer',
                'renstra_indikator_sasaran_id' => 'permit_empty|integer',
            ];

            $messages = [
                'definisi' => [
                    'required' => 'Definisi IKU wajib diisi.',
                    'regex_match' => 'Definisi IKU terdeteksi mengandung script / input berbahaya.',
                ],
            ];
            if (!$this->validate($rules, $messages)) {
                return redirect()->back()->withInput()
                    ->with('error', implode(' ', $this->validator->getErrors()));
            }

            // validasi manual untuk program_pendukung[] karena array
            $programs = $data['program_pendukung'] ?? [];
            if (!empty($programs) && is_array($programs)) {
                foreach ($programs as $p) {
                    if (!$this->isSafeText($p)) {
                        return redirect()->back()->withInput()
                            ->with('error', 'Program pendukung terdeteksi mengandung script / input berbahaya.');
                    }
                }
            }


            $this->ikuModel->createCompleteIku([
                'definisi' => $data['definisi'],
                'rpjmd_id' => $data['rpjmd_id'] ?? null,
                'renstra_id' => $data['renstra_indikator_sasaran_id'] ?? null,
                'program_pendukung' => $data['program_pendukung'] ?? [],
            ]);

            session()->setFlashdata('success', 'IKU berhasil ditambahkan.');
            return redirect()->to(base_url('adminopd/iku'));
        } catch (\Exception $e) {
            log_message('error', '[IKU SAVE ERROR] ' . $e->getMessage());
            session()->setFlashdata('error', 'Gagal menambahkan data IKU: ' . $e->getMessage());
            return redirect()->back()->withInput();
        }
    }

    /**
     * FORM EDIT IKU
     */
    public function edit($indikatorId = null)
    {
        $session = session();
        $opdId = $session->get('opd_id');
        $role = $session->get('role');

        if (!$opdId && $role !== 'admin_kab') {
            return redirect()->to('/login')->with('error', 'Silakan login terlebih dahulu');
        }

        // Ambil data indikator (rpjmd / renstra)
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

        // Ambil detail IKU + list program pendukung
        $ikuData = $this->ikuModel->getIkuDetail($indikatorId, $role);

        $data = [
            'title' => 'Edit IKU',
            'indikator' => $indikator,
            'iku_data' => $ikuData,
            'validation' => \Config\Services::validation(),
            'role' => $role,   // ✅ supaya tidak undefined di view
        ];

        return view('adminOpd/iku/edit_iku', $data);
    }

    /**
     * UPDATE DATA IKU
     */
    public function update()
    {
        $session = session();
        $opdId = $session->get('opd_id');
        $role = $session->get('role');

        if (!$opdId && $role !== 'admin_kab') {
            return redirect()->to('/login')->with('error', 'Silakan login terlebih dahulu');
        }

        $data = $this->request->getPost();
        $ikuId = $data['iku_id'] ?? null;

        if (!$ikuId) {
            session()->setFlashdata('error', 'ID IKU tidak ditemukan');
            return redirect()->back()->withInput();
        }

        try {
            $rx = $this->xssRule();

            // ============================
            // VALIDASI (ANTI XSS/SCRIPT)
            // ============================
            $rules = [
                'iku_id' => 'required|integer',
                'definisi' => 'required|string|max_length[10000]|' . $rx,
            ];

            $messages = [
                'definisi' => [
                    'required' => 'Definisi IKU wajib diisi.',
                    'regex_match' => 'Definisi IKU terdeteksi mengandung script / input berbahaya.',
                ],
            ];

            if (!$this->validate($rules, $messages)) {
                return redirect()->back()->withInput()
                    ->with('error', implode(' ', $this->validator->getErrors()));
            }
            // validasi manual array program_pendukung[] karena array
            $programs = $data['program_pendukung'] ?? [];
            if (!empty($programs) && is_array($programs)) {
                foreach ($programs as $p) {
                    if (!$this->isSafeText($p)) {
                        return redirect()->back()->withInput()
                            ->with('error', 'Program pendukung terdeteksi mengandung script / input berbahaya.');
                    }
                }
            }
            // Update definisi IKU
            $updateData = [
                'definisi' => $data['definisi'] ?? null,
            ];
            $this->ikuModel->updateIku($ikuId, $updateData, 'id');

            // Update program pendukung (bisa tambah / edit / hapus)
            $programs = $data['program_pendukung'] ?? [];
            $programIds = $data['program_id'] ?? [];

            $this->ikuModel->updateProgramPendukung($ikuId, $programs, $programIds);

            session()->setFlashdata('success', 'Data IKU berhasil diperbarui');
        } catch (\Throwable $e) {
            session()->setFlashdata('error', 'Gagal mengupdate data IKU: ' . $e->getMessage());
            return redirect()->back()->withInput();
        }

        return redirect()->to(base_url('adminopd/iku'));
    }

    /**
     * HAPUS IKU
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
    public function change_status($id)
    {
        $ikuModel = new IkuModel();

        // Ambil data IKU berdasarkan indikator (renstra_id)
        $iku = $ikuModel->where('renstra_id', $id)->first();

        if (!$iku) {
            return redirect()->back()->with('error', 'Data IKU tidak ditemukan.');
        }

        $current = strtolower(trim($iku['status'] ?? ''));

        // Toggle: kalau belum / kosong → Tercapai, kalau Tercapai → Belum
        if ($current === 'tercapai') {
            $newStatus = 'Belum';
        } else {
            $newStatus = 'Tercapai';
        }

        $ikuModel->update($iku['id'], [
            'status' => $newStatus,
        ]);

        return redirect()->back()->with('success', 'Status IKU berhasil diubah menjadi ' . $newStatus . '.');
    }

}
