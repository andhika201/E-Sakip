<?php

namespace App\Controllers\AdminKab;

use App\Controllers\BaseController;
use App\Controllers\Concerns\LakipAddendumTrait;
use App\Models\LakipModel;
use App\Models\OpdModel;

class LakipController extends BaseController
{
    /** Analisis Faktor + Efisiensi Program (dua tabel di bawah tabel utama). */
    use LakipAddendumTrait;

    protected $lakipModel;
    protected $opdModel;
    protected $db;

    /**
     * Role yang boleh MEMBACA LAKIP kabupaten (lintas OPD).
     * `bupati` ikut membaca lewat area rute /bupati (read-only, tanpa tombol ubah).
     */
    private const ROLE_BACA = ['admin_kab', 'admin', 'admin_inspektorat', 'bupati'];

    /** Role yang boleh MENULIS (tambah/ubah/hapus/ubah status) LAKIP kabupaten. */
    private const ROLE_TULIS = ['admin_kab', 'admin'];

    /**
     * Prefix rute halaman ini — dipakai LakipAddendumTrait untuk redirect &
     * action form. Role bupati dilayani di area /bupati sehingga tautannya
     * tidak pernah mengarah ke area administratif.
     */
    protected function lakipBaseUrl(): string
    {
        return session()->get('role') === 'bupati' ? 'bupati/lakip' : 'adminkab/lakip';
    }

    public function __construct()
    {
        $this->lakipModel = new LakipModel();
        $this->opdModel = new OpdModel();
        $this->db = \Config\Database::connect();
        helper(['form', 'url']);
    }
    private function xssRule(): string
    {
        return 'regex_match[/^(?!.*<\s*script\b)(?!.*<\/\s*script\s*>)(?!.*javascript\s*:)(?!.*data\s*:\s*text\/html)(?!.*on\w+\s*=)(?!.*<\?php)(?!.*<\?).*$/is]';
    }

    public function index()
    {
        $session = session();
        $role = $session->get('role');
        // Baca LAKIP kabupaten: admin_kab, super admin, inspektorat & bupati
        // (tiga terakhir read-only lintas OPD)
        if (!in_array($role, self::ROLE_BACA, true)) {
            return redirect()->to('/login')->with('error', 'Akses ditolak');
        }

        $mode = $this->request->getGet('mode') ?: 'kabupaten'; // kabupaten | opd
        $tahun = $this->request->getGet('tahun') ?: date('Y');
        $status = $this->request->getGet('status') ?: '';
        $opdId = $this->request->getGet('opd_id'); // boleh kosong = semua opd

        $opdList = $this->opdModel->orderBy('nama_opd', 'ASC')->findAll();
        $availableYears = $this->lakipModel->getAvailableYears();

        $rows = [];
        $lakipMap = [];

        if ($mode === 'opd') {
            $opdIdInt = (!empty($opdId) ? (int) $opdId : null);

            $rows = $this->lakipModel->getIndexRenstraTargets((string) $tahun, $opdIdInt);
            $lakipMap = $this->lakipModel->getLakipMapRenstra((string) $tahun, ($status ?: null), $opdIdInt);
        } else {
            $rows = $this->lakipModel->getIndexRpjmdTargets((string) $tahun);
            $lakipMap = $this->lakipModel->getLakipMapRpjmd((string) $tahun, ($status ?: null));
        }

        // Dua tabel tambahan (Analisis Faktor & Efisiensi Program) memakai
        // tahun + lingkup yang sama dengan tabel utama.
        $scope = $this->lakipScope((string) $tahun, $mode);

        return view('adminKabupaten/lakip/lakip', array_merge($this->lakipAddendumData($scope), [
            'title' => 'LAKIP - Admin Kabupaten',
            'role' => $role,
            'mode' => $mode,
            'availableYears' => $availableYears,
            'opdList' => $opdList,
            'selectedOpdId' => $opdId,
            'filters' => ['tahun' => $tahun, 'status' => $status],
            'rows' => $rows,
            'lakipMap' => $lakipMap,
            'indikatorRows' => $rows,
            'addendumBase' => $this->lakipBaseUrl(),
            // Gate tombol tambah/edit/hapus/ubah-status pada tabel utama +
            // prefix rute agar role read-only (inspektorat/bupati) tidak pernah
            // melihat atau menuju aksi pengubah data.
            'lakipCanWrite' => in_array($role, self::ROLE_TULIS, true),
            'lakipBase' => $this->lakipBaseUrl(),
        ]));
    }

    public function cetak()
    {
        if (ob_get_level() > 0) {
            @ob_clean();
        }

        helper(['number', 'lakip', 'setting']);

        $session = session();
        $role = $session->get('role');
        // Cetak LAKIP kabupaten: admin_kab, super admin, inspektorat & bupati (read-only)
        if (!in_array($role, self::ROLE_BACA, true)) {
            return redirect()->to('/login')->with('error', 'Akses ditolak');
        }

        $mode = $this->request->getGet('mode') ?: 'kabupaten';
        $tahun = $this->request->getGet('tahun') ?: date('Y');
        $status = $this->request->getGet('status') ?: '';
        $opdId = $this->request->getGet('opd_id');

        $opdList = $this->opdModel->orderBy('nama_opd', 'ASC')->findAll();
        $opdInfo = null;

        if ($mode === 'opd') {
            $opdIdInt = (!empty($opdId) ? (int) $opdId : null);
            $rows = $this->lakipModel->getIndexRenstraTargets((string) $tahun, $opdIdInt);
            $lakipMap = $this->lakipModel->getLakipMapRenstra((string) $tahun, ($status ?: null), $opdIdInt);

            if ($opdIdInt) {
                $opdInfo = $this->opdModel->find($opdIdInt);
            }
        } else {
            $rows = $this->lakipModel->getIndexRpjmdTargets((string) $tahun);
            $lakipMap = $this->lakipModel->getLakipMapRpjmd((string) $tahun, ($status ?: null));
        }

        $unitName = $opdInfo['nama_opd'] ?? (($mode === 'opd') ? 'Seluruh OPD' : 'Kabupaten Pringsewu');

        // Dua tabel tambahan ikut tercetak, memakai tahun & lingkup yang sama.
        $scope = $this->lakipScope((string) $tahun, $mode);

        $html = view('adminKabupaten/lakip/lakip_cetak', array_merge($this->lakipAddendumData($scope), [
            'title' => 'Cetak LAKIP - Admin Kabupaten',
            'role' => $role,
            'mode' => $mode,
            'opdList' => $opdList,
            'opdInfo' => $opdInfo,
            'selectedOpdId' => $opdId,
            'filters' => [
                'tahun' => $tahun,
                'status' => $status,
            ],
            'rows' => $rows,
            'lakipMap' => $lakipMap,
            'unitName' => $unitName,
            'indikatorRows' => $rows,
        ]));

        // ============================================================
        // CETAK LAKIP: TANPA KOP, WATERMARK, HEADER, & FOOTER HALAMAN.
        //
        // Dokumen langsung dimulai dari judul "LAPORAN AKUNTABILITAS KINERJA
        // INSTANSI PEMERINTAH" (lihat view adminKabupaten/lakip/lakip_cetak).
        // Karena itu di sini SENGAJA TIDAK dipanggil:
        //   - $mpdf->SetHTMLHeader() / SetHTMLFooter()   -> tanpa header/footer & nomor halaman
        //   - pdf_watermark_aksara()                     -> tanpa watermark (SetWatermarkImage)
        //   - templates/pdf_kop (di view)                -> tanpa KOP & logo instansi
        // Modul PDF lain (Cascading, RPJMD, Renstra, MONEV, dst.) TIDAK diubah
        // dan tetap memakai kop/footer/watermark standar.
        //
        // Margin dibuat sedikit lebih lega karena tidak ada kop/footer lagi.
        // ============================================================
        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4-L',
            'margin_left' => 12,
            'margin_right' => 12,
            'margin_top' => 14,
            'margin_bottom' => 14,
            'margin_header' => 0,
            'margin_footer' => 0,
            'tempDir' => sys_get_temp_dir(),
        ]);
        // Matikan eksplisit bila ada konfigurasi global mPDF yang menyalakannya.
        $mpdf->showWatermarkText  = false;
        $mpdf->showWatermarkImage = false;
        $mpdf->SetDisplayMode('fullpage');
        $mpdf->WriteHTML($html);

        $this->response->setHeader('Content-Type', 'application/pdf');
        $safeUnit = preg_replace('/[^A-Za-z0-9]+/', '-', (string) $unitName);
        $mpdf->Output('LAKIP-' . trim($safeUnit, '-') . '-' . $tahun . '.pdf', 'I');
        exit;
    }

    public function cetakExcel()
    {
        helper(['number', 'lakip', 'setting', 'lakip_excel']);

        $session = session();
        $role = $session->get('role');
        // Cetak LAKIP kabupaten: admin_kab, super admin, inspektorat & bupati (read-only)
        if (!in_array($role, self::ROLE_BACA, true)) {
            return redirect()->to('/login')->with('error', 'Akses ditolak');
        }

        $mode = $this->request->getGet('mode') ?: 'kabupaten';
        $tahun = $this->request->getGet('tahun') ?: date('Y');
        $status = $this->request->getGet('status') ?: '';
        $opdId = $this->request->getGet('opd_id');

        $opdInfo = null;

        if ($mode === 'opd') {
            $opdIdInt = (!empty($opdId) ? (int) $opdId : null);
            $rows = $this->lakipModel->getIndexRenstraTargets((string) $tahun, $opdIdInt);
            $lakipMap = $this->lakipModel->getLakipMapRenstra((string) $tahun, ($status ?: null), $opdIdInt);

            if ($opdIdInt) {
                $opdInfo = $this->opdModel->find($opdIdInt);
            }
        } else {
            $rows = $this->lakipModel->getIndexRpjmdTargets((string) $tahun);
            $lakipMap = $this->lakipModel->getLakipMapRpjmd((string) $tahun, ($status ?: null));
        }

        $unitName = $opdInfo['nama_opd'] ?? (($mode === 'opd') ? 'Seluruh OPD' : 'Kabupaten Pringsewu');

        // Sheet tambahan: Analisis Faktor & Efisiensi Program.
        $scope    = $this->lakipScope((string) $tahun, $mode);
        $addendum = $this->lakipAddendumData($scope);

        lakip_kab_excel($rows, $lakipMap, $mode, [
            'unit' => $unitName,
            'tahun' => (string) $tahun,
            'status' => (string) $status,
        ], [
            'indikatorRows' => $rows,
            'analisisMap'   => $addendum['analisisMap'],
            'efisiensiRows' => $addendum['efisiensiRows'],
        ]);
    }

    public function tambah($targetId = null)
    {
        $session = session();
        $role = $session->get('role');
        // Aksi tulis LAKIP kabupaten: admin_kab & super admin (inspektorat read-only)
        if (!in_array($role, ['admin_kab', 'admin'], true))
            return redirect()->to('/login')->with('error', 'Akses ditolak');

        $mode = $this->request->getGet('mode') ?: 'kabupaten';
        $tahun = $this->request->getGet('tahun') ?: date('Y');
        $selectedOpdId = $this->request->getGet('opd_id');

        if (!$targetId)
            return redirect()->back()->with('error', 'Target tidak valid.');

        if ($mode === 'kabupaten') {
            $target = $this->db->table('rpjmd_target')->where('id', $targetId)->get()->getRowArray();
            if (!$target)
                return redirect()->back()->with('error', 'Target RPJMD tidak ditemukan.');

            $indikator = $this->db->table('rpjmd_indikator_sasaran')
                ->where('id', $target['indikator_sasaran_id'])
                ->get()->getRowArray();

            if (!$indikator)
                return redirect()->back()->with('error', 'Indikator RPJMD tidak ditemukan.');

            $opdInfo = null;
        } else {
            $target = $this->db->table('renstra_target')->where('id', $targetId)->get()->getRowArray();
            if (!$target)
                return redirect()->back()->with('error', 'Target RENSTRA tidak ditemukan.');

            $indikator = $this->db->table('renstra_indikator_sasaran')
                ->where('id', $target['renstra_indikator_id'])
                ->get()->getRowArray();

            if (!$indikator)
                return redirect()->back()->with('error', 'Indikator RENSTRA tidak ditemukan.');

            $opdInfo = $this->db->table('renstra_sasaran rs')
                ->select('o.*')
                ->join('opd o', 'o.id = rs.opd_id', 'left')
                ->where('rs.id', $indikator['renstra_sasaran_id'])
                ->get()->getRowArray();

            if (!empty($selectedOpdId) && !empty($opdInfo['id']) && (int) $opdInfo['id'] !== (int) $selectedOpdId) {
                return redirect()->back()->with('error', 'Target tidak sesuai OPD yang dipilih.');
            }
        }

        // Kolom `satuan` pada indikator menyimpan id -> resolve ke nama satuan.
        $indikator['satuan'] = $this->lakipModel->resolveSatuanName($indikator['satuan'] ?? null);

        return view('adminKabupaten/lakip/tambah_lakip', [
            'title' => 'Tambah LAKIP',
            'role' => $role,
            'mode' => $mode,
            'tahun' => $tahun,
            'selectedOpdId' => $selectedOpdId,
            'indikator' => $indikator,
            'target' => $target,
            'opdInfo' => $opdInfo,
            'validation' => \Config\Services::validation(),
        ]);
    }

    public function save()
    {
        $session = session();
        $role = $session->get('role');
        // Aksi tulis LAKIP kabupaten: admin_kab & super admin (inspektorat read-only)
        if (!in_array($role, ['admin_kab', 'admin'], true))
            return redirect()->to('/login')->with('error', 'Akses ditolak');

        $rx = $this->xssRule();

        // ============================
        // VALIDASI (ANTI XSS/SCRIPT)
        // ============================
        $rules = [
            'mode' => 'permit_empty|string|max_length[20]|' . $rx,
            'tahun' => 'permit_empty|string|max_length[10]|' . $rx,
            'selected_opd_id' => 'permit_empty|string|max_length[20]|' . $rx,

            'target_lalu' => 'permit_empty|string|max_length[255]|' . $rx,
            'capaian_lalu' => 'permit_empty|string|max_length[255]|' . $rx,
            'capaian_tahun_ini' => 'permit_empty|string|max_length[255]|' . $rx,
            'target_hitung' => 'permit_empty|numeric',
            'capaian_hitung' => 'permit_empty|numeric',
            'status' => 'permit_empty|string|max_length[50]|' . $rx,

            // id target sesuai mode (dibuat required dua-duanya, nanti dicek logika mode)
            'renstra_target_id' => 'permit_empty|integer',
            'rpjmd_target_id' => 'permit_empty|integer',
        ];
        $messages = [
            'mode' => ['regex_match' => 'Mode terdeteksi mengandung script / input berbahaya.'],
            'tahun' => ['regex_match' => 'Tahun terdeteksi mengandung script / input berbahaya.'],
            'selected_opd_id' => ['regex_match' => 'OPD terdeteksi mengandung script / input berbahaya.'],

            'target_lalu' => ['regex_match' => 'Target lalu mengandung script / input berbahaya.'],
            'capaian_lalu' => ['regex_match' => 'Capaian lalu mengandung script / input berbahaya.'],
            'capaian_tahun_ini' => ['regex_match' => 'Capaian tahun ini mengandung script / input berbahaya.'],
            'target_hitung' => ['numeric' => 'Nilai target hitung harus berupa angka desimal/bulat.'],
            'capaian_hitung' => ['numeric' => 'Nilai capaian hitung harus berupa angka desimal/bulat.'],
            'status' => ['regex_match' => 'Status mengandung script / input berbahaya.'],
        ];

        if (!$this->validate($rules, $messages)) {
            return redirect()->back()->withInput()
                ->with('error', implode(' ', $this->validator->getErrors()));
        }

        $mode = $this->request->getPost('mode') ?: 'kabupaten';
        $tahun = $this->request->getPost('tahun') ?: date('Y');
        $selectedOpdId = $this->request->getPost('selected_opd_id') ?: '';

        $dataCommon = [
            'target_lalu' => $this->request->getPost('target_lalu') ?? '',
            'capaian_lalu' => $this->request->getPost('capaian_lalu') ?? '',
            'capaian_tahun_ini' => $this->request->getPost('capaian_tahun_ini') ?? '',
            'target_hitung' => $this->request->getPost('target_hitung') !== '' ? $this->request->getPost('target_hitung') : null,
            'capaian_hitung' => $this->request->getPost('capaian_hitung') !== '' ? $this->request->getPost('capaian_hitung') : null,
            'status' => 'draft',
        ];

        if ($mode === 'opd') {
            $renstraTargetId = (int) $this->request->getPost('renstra_target_id');
            if (!$renstraTargetId)
                return redirect()->back()->with('error', 'Target RENSTRA tidak valid.')->withInput();

            $exist = $this->lakipModel->getLakipByRenstraTarget($renstraTargetId);
            if ($exist)
                return redirect()->back()->with('error', 'LAKIP untuk target ini sudah ada. Silakan edit.')->withInput();

            $insert = array_merge($dataCommon, [
                'renstra_target_id' => $renstraTargetId,
                'rpjmd_target_id' => null,
            ]);
        } else {
            $rpjmdTargetId = (int) $this->request->getPost('rpjmd_target_id');
            if (!$rpjmdTargetId)
                return redirect()->back()->with('error', 'Target RPJMD tidak valid.')->withInput();

            $exist = $this->lakipModel->getLakipByRpjmdTarget($rpjmdTargetId);
            if ($exist)
                return redirect()->back()->with('error', 'LAKIP untuk target ini sudah ada. Silakan edit.')->withInput();

            $insert = array_merge($dataCommon, [
                'renstra_target_id' => null,
                'rpjmd_target_id' => $rpjmdTargetId,
            ]);
        }

        $this->lakipModel->insert($insert);

        $qs = '?mode=' . $mode . '&tahun=' . $tahun;
        if ($mode === 'opd')
            $qs .= '&opd_id=' . urlencode($selectedOpdId);

        return redirect()->to(base_url('adminkab/lakip') . $qs)->with('success', 'Data LAKIP berhasil disimpan.');
    }

    public function edit($indikatorId)
    {
        $session = session();
        $role = $session->get('role');
        // Aksi tulis LAKIP kabupaten: admin_kab & super admin (inspektorat read-only)
        if (!in_array($role, ['admin_kab', 'admin'], true))
            return redirect()->to('/login')->with('error', 'Akses ditolak');

        $mode = $this->request->getGet('mode') ?: 'kabupaten';
        $tahun = $this->request->getGet('tahun') ?: date('Y');
        $selectedOpdId = $this->request->getGet('opd_id') ?: '';

        if ($mode === 'opd') {
            $targetDetail = $this->lakipModel->getRenstraTargetDetailByIndikatorAndYear((int) $indikatorId, (string) $tahun);
            if (!$targetDetail)
                return redirect()->back()->with('error', 'Target RENSTRA tahun ' . $tahun . ' belum diisi.');

            $lakip = $this->lakipModel->getLakipByRenstraTarget((int) $targetDetail['id']);
            if (!$lakip) {
                // ✅ FIX: redirect ke tambah pakai TARGET_ID
                $qs = '?mode=opd&tahun=' . $tahun . '&opd_id=' . urlencode($selectedOpdId);
                return redirect()->to(base_url('adminkab/lakip/tambah/' . $targetDetail['id']) . $qs)
                    ->with('error', 'Data LAKIP belum ada. Silakan tambah.');
            }

            return view('adminKabupaten/lakip/edit_lakip', [
                'title' => 'Edit LAKIP (Mode OPD/RENSTRA)',
                'role' => $role,
                'mode' => $mode,
                'tahun' => $tahun,
                'selectedOpdId' => $selectedOpdId,
                'target' => $targetDetail,
                'lakip' => $lakip,
                'validation' => \Config\Services::validation(),
            ]);
        }

        $targetDetail = $this->lakipModel->getRpjmdTargetDetailByIndikatorAndYear((int) $indikatorId, (string) $tahun);
        if (!$targetDetail)
            return redirect()->back()->with('error', 'Target RPJMD tahun ' . $tahun . ' belum diisi.');

        $lakip = $this->lakipModel->getLakipByRpjmdTarget((int) $targetDetail['id']);
        if (!$lakip) {
            // ✅ FIX: redirect ke tambah pakai TARGET_ID
            $qs = '?mode=kabupaten&tahun=' . $tahun;
            return redirect()->to(base_url('adminkab/lakip/tambah/' . $targetDetail['id']) . $qs)
                ->with('error', 'Data LAKIP belum ada. Silakan tambah.');
        }

        return view('adminKabupaten/lakip/edit_lakip', [
            'title' => 'Edit LAKIP (Mode Kabupaten/RPJMD)',
            'role' => $role,
            'mode' => $mode,
            'tahun' => $tahun,
            'selectedOpdId' => $selectedOpdId,
            'target' => $targetDetail,
            'lakip' => $lakip,
            'validation' => \Config\Services::validation(),
        ]);
    }

    public function update()
    {
        $session = session();
        $role = $session->get('role');
        // Aksi tulis LAKIP kabupaten: admin_kab & super admin (inspektorat read-only)
        if (!in_array($role, ['admin_kab', 'admin'], true))
            return redirect()->to('/login')->with('error', 'Akses ditolak');

        $rx = $this->xssRule();

        // ============================
        // VALIDASI (ANTI XSS/SCRIPT)
        // ============================
        $rules = [
            'lakip_id' => 'required|integer',

            'mode' => 'permit_empty|string|max_length[20]|' . $rx,
            'tahun' => 'permit_empty|string|max_length[10]|' . $rx,
            'selected_opd_id' => 'permit_empty|string|max_length[20]|' . $rx,

            'target_lalu' => 'permit_empty|string|max_length[255]|' . $rx,
            'capaian_lalu' => 'permit_empty|string|max_length[255]|' . $rx,
            'capaian_tahun_ini' => 'permit_empty|string|max_length[255]|' . $rx,
            'target_hitung' => 'permit_empty|numeric',
            'capaian_hitung' => 'permit_empty|numeric',
            'status' => 'permit_empty|string|max_length[50]|' . $rx,
        ];
        $messages = [
            'target_lalu' => ['regex_match' => 'Target lalu mengandung script / input berbahaya.'],
            'capaian_lalu' => ['regex_match' => 'Capaian lalu mengandung script / input berbahaya.'],
            'capaian_tahun_ini' => ['regex_match' => 'Capaian tahun ini mengandung script / input berbahaya.'],
            'target_hitung' => ['numeric' => 'Nilai target hitung harus berupa angka.'],
            'capaian_hitung' => ['numeric' => 'Nilai capaian hitung harus berupa angka.'],
            'status' => ['regex_match' => 'Status mengandung script / input berbahaya.'],
            'mode' => ['regex_match' => 'Mode terdeteksi mengandung script / input berbahaya.'],
            'tahun' => ['regex_match' => 'Tahun terdeteksi mengandung script / input berbahaya.'],
            'selected_opd_id' => ['regex_match' => 'OPD terdeteksi mengandung script / input berbahaya.'],
        ];

        if (!$this->validate($rules, $messages)) {
            return redirect()->back()->withInput()
                ->with('error', implode(' ', $this->validator->getErrors()));
        }

        $mode = $this->request->getPost('mode') ?: 'kabupaten';
        $tahun = $this->request->getPost('tahun') ?: date('Y');
        $selectedOpdId = $this->request->getPost('selected_opd_id') ?: '';

        $lakipId = (int) ($this->request->getPost('lakip_id') ?? 0);
        if (!$lakipId)
            return redirect()->back()->with('error', 'ID LAKIP tidak ditemukan')->withInput();

        $updateData = [
            'target_lalu' => $this->request->getPost('target_lalu') ?? '',
            'capaian_lalu' => $this->request->getPost('capaian_lalu') ?? '',
            'capaian_tahun_ini' => $this->request->getPost('capaian_tahun_ini') ?? '',
            'target_hitung' => $this->request->getPost('target_hitung') !== '' ? $this->request->getPost('target_hitung') : null,
            'capaian_hitung' => $this->request->getPost('capaian_hitung') !== '' ? $this->request->getPost('capaian_hitung') : null,
            'status' => $this->request->getPost('status') ?: 'draft',
        ];

        $this->lakipModel->updateLakip($lakipId, $updateData);

        $qs = '?mode=' . $mode . '&tahun=' . $tahun;
        if ($mode === 'opd')
            $qs .= '&opd_id=' . urlencode($selectedOpdId);

        return redirect()->to(base_url('adminkab/lakip') . $qs)->with('success', 'Data LAKIP berhasil diperbarui');
    }

    public function status($id, $to)
    {
        $session = session();
        $role = $session->get('role');
        // Aksi tulis LAKIP kabupaten: admin_kab & super admin (inspektorat read-only)
        if (!in_array($role, ['admin_kab', 'admin'], true))
            return redirect()->to('/login')->with('error', 'Akses ditolak');

        $allowed = ['draft', 'selesai'];
        if (!in_array($to, $allowed, true))
            return redirect()->back()->with('error', 'Status tidak valid.');

        $this->lakipModel->updateLakip((int) $id, ['status' => $to]);
        return redirect()->back()->with('success', 'Status LAKIP diubah menjadi ' . ucfirst($to));
    }

    public function delete($id)
    {
        $session = session();
        $role = $session->get('role');
        // Hapus LAKIP kabupaten: admin_kab & super admin (inspektorat read-only)
        if (!in_array($role, ['admin_kab', 'admin'], true)) {
            return redirect()->to('/login')->with('error', 'Akses ditolak');
        }

        $lakip = $this->lakipModel->find($id);
        if (!$lakip) {
            return redirect()->back()->with('error', 'Data LAKIP tidak ditemukan.');
        }

        if ($this->lakipModel->deleteLakip((int) $id)) {
            return redirect()->back()->with('success', 'LAKIP berhasil dihapus.');
        }

        return redirect()->back()->with('error', 'Gagal menghapus LAKIP.');
    }
}
