<?php

namespace App\Controllers\AdminKab;

use App\Controllers\BaseController;
use App\Controllers\Concerns\LakipAddendumTrait;
use App\Controllers\Concerns\LakipBenchmarkTrait;
use App\Controllers\Concerns\LakipSumberTrait;
use App\Controllers\Concerns\LakipSnapshotTrait;
use App\Models\LakipModel;
use App\Models\OpdModel;

class LakipController extends BaseController
{
    /** Analisis Faktor + Efisiensi Program (dua tabel di bawah tabel utama). */
    use LakipAddendumTrait {
        // Dialiaskan supaya bawaannya masih terpanggil setelah ditimpa di
        // kelas ini (pola yang sama dengan LakipOpdController).
        LakipAddendumTrait::sumberDokumenLakip as private sumberDokumenBawaan;
    }

    /** Pemilih dokumen sumber (IKU Kabupaten / RPJMD + versinya). */
    use LakipSumberTrait;

    /** Chart perbandingan Provinsi Lampung & Nasional (di atas Analisis Faktor). */
    use LakipBenchmarkTrait;

    /** Snapshot tahunan + kunci tahun + penyesuaian kebijakan. */
    use LakipSnapshotTrait {
        barisHidupUntukSnapshot as private barisHidupBawaan;
    }

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

    /** Prefix permission untuk aksi snapshot/finalisasi/penyesuaian. */
    protected function lakipPermPrefix(): string
    {
        return 'lakip_kab';
    }

    /**
     * Tahun laporan bawaan = TAHUN LALU, bukan tahun berjalan.
     *
     * =====================================================================
     * MENGAPA MUNDUR SATU TAHUN
     *
     * LAKIP melaporkan kinerja satu tahun yang SUDAH SELESAI: LAKIP yang
     * disusun sepanjang 2026 menilai capaian 2025. Memakai date('Y') berarti
     * layar terbuka pada tahun yang belum ada realisasinya.
     *
     * Sisi OPD (LakipOpdController) sudah memakai date('Y')-1 sejak awal.
     * Selama layar ini memakai date('Y'), Admin Kabupaten dan Admin OPD
     * membuka "LAKIP" yang sama dan mendarat di TAHUN BERBEDA — kabupaten di
     * 2026 yang masih kosong, OPD di 2025 yang terisi. Itu terbaca sebagai
     * "data IKU-nya salah tahun", padahal yang berbeda hanya tahun bawaannya.
     * =====================================================================
     */
    private function tahunLaporanBawaan(): int
    {
        return (int) date('Y') - 1;
    }

    /**
     * Tahun yang sudah DISAHKAN terkunci — tolak seluruh jalur tulis.
     *
     * =====================================================================
     * MENGAPA DI CONTROLLER, BUKAN CUKUP DI VIEW
     *
     * Layar ini sudah memadamkan tombolnya lewat `lakipCanWrite`, tapi itu
     * hanya menyembunyikan. save(), update(), status(), dan delete() di kelas
     * ini semula TIDAK memeriksa kunci sama sekali — hanya role — sehingga
     * satu POST yang diarahkan langsung ke rutenya tetap menulis ke tahun yang
     * sudah disahkan dan ditandatangani.
     *
     * Kembaran sisi OPD (LakipOpdController::tolakBilaDisahkan) sudah
     * menegakkannya sejak awal dengan alasan yang sama: "tombol yang hilang
     * tidak menghentikan URL yang diketik langsung".
     *
     * Mengembalikan null bila boleh menulis, atau tanggapan penolakan.
     */
    private function tolakBilaDisahkan(int $tahun, string $mode, ?int $opdId)
    {
        $m = new \App\Models\LakipPengesahanModel();

        if (! $m->siap()) {
            return null; // basis data belum dimigrasi: perilaku lama
        }

        if (! $m->terkunci($tahun, $mode, $opdId)) {
            return null;
        }

        $pesan = 'LAKIP tahun ' . $tahun . ' sudah disahkan dan terkunci. '
            . 'Buka dulu lewat panel Pengesahan di halaman LAKIP sebelum menyuntingnya.';

        if ($this->request->isAJAX()) {
            return $this->response->setStatusCode(423)
                ->setJSON(['success' => false, 'status' => 'error', 'message' => $pesan]);
        }

        return redirect()->to(base_url($this->lakipBaseUrl() . '?tahun=' . $tahun . '&mode=' . $mode))
            ->with('error', $pesan);
    }

    public function __construct()
    {
        $this->lakipModel = new LakipModel();
        $this->opdModel = new OpdModel();
        $this->db = \Config\Database::connect();
        helper(['form', 'url']);
    }
    /**
     * Sumber dokumen yang sedang dipilih di layar (menimpa bawaan trait,
     * dialiaskan di blok `use`). Mode 'opd' pada layar ini adalah rekap
     * Renstra lintas OPD — perilaku lamanya dipertahankan.
     */
    protected function sumberDokumenLakip(string $mode): ?string
    {
        if ($mode !== 'kabupaten') {
            return $this->sumberDokumenBawaan($mode);
        }

        [$sumber] = $this->sumberDariPermintaan(
            'kabupaten',
            null,
            (int) ($this->request->getGet('tahun') ?: $this->request->getPost('tahun') ?: $this->tahunLaporanBawaan())
        );

        return $sumber !== '' ? $sumber : 'rpjmd';
    }

    /**
     * Baris yang dibekukan snapshot mengikuti sumber di layar (menimpa
     * bawaan trait, dialiaskan di blok `use`) — membekukan RPJMD dari layar
     * yang menampilkan IKU menghasilkan arsip yang tidak pernah dilihat.
     */
    protected function barisHidupUntukSnapshot(string $mode, string $tahun, string $status, $opd): array
    {
        if ($mode !== 'kabupaten') {
            return $this->barisHidupBawaan($mode, $tahun, $status, $opd);
        }

        [$sumber, $revisiId] = $this->sumberDariPermintaan('kabupaten', null, (int) $tahun);

        if ($sumber !== \App\Services\Version\LakipSourceService::SUMBER_IKU || $revisiId <= 0) {
            return $this->barisHidupBawaan($mode, $tahun, $status, $opd);
        }

        return [
            'rows'            => $this->lakipModel->getIndexIkuTargets($revisiId, (int) $tahun, null),
            'lakipMap'        => $this->lakipModel->getLakipMapIku((int) $tahun, $status !== '' ? $status : null, null),
            'sumber_type'     => \App\Services\Version\LakipSourceService::SUMBER_IKU,
            'sumber_versi_id' => $revisiId,
        ];
    }

    /**
     * Baris tabel + peta realisasi untuk satu lingkup, MENGIKUTI SUMBER YANG
     * BERLAKU (IKU bila versinya ada, cadangan Renstra/RPJMD bila belum).
     *
     * =====================================================================
     * MENGAPA ADA — DAN MENGAPA ANGKA SEMPAT HILANG TANPA INI
     *
     * index(), cetak(), dan cetakExcel() dulu memanggil getIndexRenstraTargets()
     * + getLakipMapRenstra() secara MATI untuk mode 'opd', melewati
     * pilihanSumberLakip() sama sekali. Akibatnya Admin Kabupaten menilai
     * Renstra sementara OPD-nya sendiri menilai IKU — dua orang membuka LAKIP
     * yang sama dan membaca dokumen yang berbeda.
     *
     * Yang membuatnya berbahaya, bukan sekadar tidak konsisten: jalur simpan
     * bersumber IKU menulis `renstra_target_id = NULL`
     * (LakipOpdController::save()), sedangkan getLakipMapRenstra() menyaring
     * `WHERE rt.tahun = ?` di atas join `renstra_target`. Baris tanpa jangkar
     * Renstra karena itu TERBUANG DIAM-DIAM. Setiap realisasi baru yang
     * diketik OPD lewat layar IKU lenyap dari layar DAN dari cetakan Admin
     * Kabupaten, tanpa galat apa pun.
     *
     * Baris warisan hasil migrasi 2026-08-29 masih membawa `renstra_target_id`
     * sebagai jejak asal, jadi kerusakannya belum kelihatan pada data lama —
     * hanya pada yang baru.
     *
     * =====================================================================
     * KUNCI PETA
     *
     * Peta selalu berkunci nilai yang SAMA dengan `target_id` pada barisnya,
     * sehingga view cukup memakai $lakipMap[$targetId] tanpa tahu sumbernya:
     *
     *   IKU     -> id indikator IKU berjalan (getIndexIkuTargets menyetel
     *              target_id = indikator_id, dan getLakipMapIku berkunci
     *              source_entity_id yang nilainya sama)
     *   Renstra -> renstra_target.id
     *   RPJMD   -> rpjmd_target.id
     *
     * @return array{0:array,1:array,2:?array} [rows, lakipMap, pilihanSumber]
     */
    private function barisLakipSumber(string $mode, ?int $opdId, int $tahun, ?string $status): array
    {
        // Mode OPD tanpa OPD terpilih = rekap SELURUH OPD. Tidak ada satu versi
        // IKU yang memayungi semuanya, jadi rekap itu tetap dilayani Renstra.
        //
        // Penjaga ini WAJIB eksplisit: pilihanSumberLakip('opd', null, ...)
        // memetakan opd kosong menjadi opd_key 0, dan 0 adalah lingkup
        // KABUPATEN. Tanpa penjaga, begitu IKU Kabupaten punya revisi, rekap
        // "semua OPD" akan menampilkan indikator kabupaten.
        if ($mode === 'opd' && empty($opdId)) {
            return [
                $this->lakipModel->getIndexRenstraTargets((string) $tahun, null),
                $this->lakipModel->getLakipMapRenstra((string) $tahun, $status, null),
                null,
            ];
        }

        $pilihan = $this->pilihanSumberLakip($mode, $opdId, $tahun);

        if ($pilihan['sumber'] === \App\Services\Version\LakipSourceService::SUMBER_IKU
            && ! empty($pilihan['versi'])) {
            return [
                $this->lakipModel->getIndexIkuTargets((int) $pilihan['versi']['id'], $tahun, $opdId),
                $this->lakipModel->getLakipMapIku($tahun, $status, $opdId),
                $pilihan,
            ];
        }

        if ($mode === 'kabupaten') {
            return [
                $this->lakipModel->getIndexRpjmdTargets((string) $tahun),
                $this->lakipModel->getLakipMapRpjmd((string) $tahun, $status),
                $pilihan,
            ];
        }

        return [
            $this->lakipModel->getIndexRenstraTargets((string) $tahun, $opdId),
            $this->lakipModel->getLakipMapRenstra((string) $tahun, $status, $opdId),
            $pilihan,
        ];
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
        $tahun = $this->request->getGet('tahun') ?: $this->tahunLaporanBawaan();
        $status = $this->request->getGet('status') ?: '';
        $opdId = $this->request->getGet('opd_id'); // boleh kosong = semua opd

        $opdList = $this->opdModel->orderBy('nama_opd', 'ASC')->findAll();
        $availableYears = $this->lakipModel->getAvailableYears();

        $rows = [];
        $lakipMap = [];

        if ($mode === 'opd') {
            $opdIdInt = (!empty($opdId) ? (int) $opdId : null);

            // Sumber diikuti, tidak lagi dipatok Renstra — lihat catatan pada
            // barisLakipSumber(). Petanya berkunci `target_id`, sama dengan
            // yang dibaca view untuk mode opd.
            [$rows, $lakipMap, $pilihanSumberOpd] = $this->barisLakipSumber(
                'opd', $opdIdInt, (int) $tahun, ($status ?: null)
            );
        } else {
            // Kabupaten memakai pemilih sumber yang sama dengan layar OPD:
            // bawaan IKU Kabupaten, cadangan RPJMD (§24). lakipMap dari
            // baganLakip() berkunci INDIKATOR untuk kedua sumber — view
            // menyesuaikan kuncinya saat mode kabupaten.
            $pilihanSumberKab = $this->pilihanSumberLakip('kabupaten', null, (int) $tahun);

            [, $lakipMap, $rows] = $this->baganLakip(
                $pilihanSumberKab, 'kabupaten', null, (int) $tahun, ($status ?: null)
            );
        }

        // Dua tabel tambahan (Analisis Faktor & Efisiensi Program) memakai
        // tahun + lingkup yang sama dengan tabel utama.
        $scope = $this->lakipScope((string) $tahun, $mode);

        // Tahun yang sudah difinalkan dibaca dari arsip beku, bukan dari query
        // hidup. Bentuk $rows/$lakipMap-nya identik sehingga view tidak berubah.
        $sumber   = $this->sumberLakip($scope, (string) $status, ['rows' => $rows, 'lakipMap' => $lakipMap]);
        $rows     = $sumber['rows'];
        $lakipMap = $sumber['lakipMap'];

        // Chart benchmark memakai $rows/$lakipMap SESUDAH snapshot dipakai,
        // supaya angka pada chart selalu sama dengan tabel di atasnya.
        return view('adminKabupaten/lakip/lakip', array_merge(
            $this->addendumLakip($scope, $sumber),
            $this->lakipBenchmarkData($scope, $rows, $lakipMap),
            $this->dataSnapshot($scope, $sumber),
            [
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
            // Tahun terkunci: tombol tambah/ubah/hapus/ubah-status pada tabel
            // utama ikut padam. LAKIP final tidak boleh disunting destruktif;
            // koreksinya lewat Penyesuaian Kebijakan yang tercatat.
            'lakipCanWrite' => in_array($role, self::ROLE_TULIS, true) && empty($sumber['terkunci']),
            'lakipBase' => $this->lakipBaseUrl(),
            'sumberLakip' => $pilihanSumberKab ?? $pilihanSumberOpd ?? null,
        ],
            $this->dataPengesahanKab((int) $tahun, $mode, $opdId)
        ));
    }

    /**
     * Bahan panel Pengesahan untuk layar LAKIP Kabupaten.
     *
     * Berbeda dari sisi OPD dalam satu hal: layar ini bisa MENENGOK LAKIP OPD
     * lain (mode 'opd' + opd_id terpilih). Dalam keadaan itu panelnya
     * ditampilkan sebagai INFORMASI saja — admin kabupaten mengesahkan LAKIP
     * kabupaten, bukan mengesahkan LAKIP milik OPD.
     *
     * @return array<string,mixed>
     */
    private function dataPengesahanKab(int $tahun, string $mode, ?int $opdId): array
    {
        $m = new \App\Models\LakipPengesahanModel();

        if (! $m->siap()) {
            return ['pengesahanSiap' => false];
        }

        $lingkupOpd = $mode === 'kabupaten' ? null : ($opdId !== null ? (int) $opdId : null);

        // Menengok LAKIP OPD tanpa memilih OPD tertentu: tidak ada lingkup yang
        // jelas untuk ditampilkan.
        if ($mode !== 'kabupaten' && $lingkupOpd === null) {
            return ['pengesahanSiap' => false];
        }

        $keadaan  = $m->keadaan($tahun, $mode, $lingkupOpd);
        $menunggu = $keadaan ? $m->permintaanMenunggu((int) $keadaan['id']) : null;

        return [
            'pengesahanSiap'     => true,
            // Layar kabupaten tidak memasok $tahun sebagai variabel view
            // (hanya lewat $filters), sehingga panel akan jatuh ke bawaan
            // date('Y') dan MENGESAHKAN TAHUN YANG SALAH. Dikirim tegas.
            'tahun'              => $tahun,
            'pengesahan'         => $keadaan,
            'permintaanMenunggu' => $menunggu,
            'riwayatPermintaan'  => $keadaan ? $m->riwayat((int) $keadaan['id']) : [],
            // Hanya lingkup KABUPATEN yang boleh disahkan dari layar ini.
            'bolehSahkan'        => $mode === 'kabupaten' && user_can('lakip_kab.finalisasi'),
            'pengesahanUrl'      => base_url('adminkab/lakip/pengesahan'),
        ];
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
        $tahun = $this->request->getGet('tahun') ?: $this->tahunLaporanBawaan();
        $status = $this->request->getGet('status') ?: '';
        $opdId = $this->request->getGet('opd_id');

        $opdList = $this->opdModel->orderBy('nama_opd', 'ASC')->findAll();
        $opdInfo = null;

        $opdIdInt = (!empty($opdId) ? (int) $opdId : null);

        // Cetakan mengikuti sumber yang SAMA dengan layarnya. Dulu kedua mode
        // dipatok Renstra/RPJMD di sini, sehingga layar menampilkan IKU
        // sementara berkas cetaknya berisi dokumen lain.
        [$rows, $lakipMap] = $this->barisLakipSumber(
            $mode, $opdIdInt, (int) $tahun, ($status ?: null)
        );

        if ($mode === 'opd' && $opdIdInt) {
            $opdInfo = $this->opdModel->find($opdIdInt);
        }

        $unitName = $opdInfo['nama_opd'] ?? (($mode === 'opd') ? 'Seluruh OPD' : 'Kabupaten Pringsewu');

        // Dua tabel tambahan ikut tercetak, memakai tahun & lingkup yang sama.
        $scope = $this->lakipScope((string) $tahun, $mode);

        $sumber   = $this->sumberLakip($scope, (string) $status, ['rows' => $rows, 'lakipMap' => $lakipMap]);
        $rows     = $sumber['rows'];
        $lakipMap = $sumber['lakipMap'];

        $html = view('adminKabupaten/lakip/lakip_cetak', array_merge(
            $this->addendumLakip($scope, $sumber),
            [
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
        // Orientasi POTRAIT (A4 tegak). Margin kiri/kanan dipersempit agar
        // tabel LAKIP yang lebar tetap lega di kertas potrait.
        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_left' => 8,
            'margin_right' => 8,
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
        $tahun = $this->request->getGet('tahun') ?: $this->tahunLaporanBawaan();
        $status = $this->request->getGet('status') ?: '';
        $opdId = $this->request->getGet('opd_id');

        $opdInfo = null;

        $opdIdInt = (!empty($opdId) ? (int) $opdId : null);

        // Sama dengan cetak(): unduhan mengikuti sumber yang berlaku, bukan
        // dipatok Renstra/RPJMD.
        [$rows, $lakipMap] = $this->barisLakipSumber(
            $mode, $opdIdInt, (int) $tahun, ($status ?: null)
        );

        if ($mode === 'opd' && $opdIdInt) {
            $opdInfo = $this->opdModel->find($opdIdInt);
        }

        $unitName = $opdInfo['nama_opd'] ?? (($mode === 'opd') ? 'Seluruh OPD' : 'Kabupaten Pringsewu');

        // Sheet tambahan: Analisis Faktor & Efisiensi Program.
        $scope    = $this->lakipScope((string) $tahun, $mode);
        $sumber   = $this->sumberLakip($scope, (string) $status, ['rows' => $rows, 'lakipMap' => $lakipMap]);
        $rows     = $sumber['rows'];
        $lakipMap = $sumber['lakipMap'];
        $addendum = $this->addendumLakip($scope, $sumber);

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
        $tahun = $this->request->getGet('tahun') ?: $this->tahunLaporanBawaan();
        $selectedOpdId = $this->request->getGet('opd_id');

        if (!$targetId)
            return redirect()->back()->with('error', 'Target tidak valid.');

        // Sumber IKU: parameter rute adalah id INDIKATOR IKU BERJALAN, bukan
        // id rpjmd_target/renstra_target — ruang angka yang berbeda.
        //
        // Mode 'opd' ikut diperiksa sejak tabelnya menampilkan IKU
        // (barisLakipSumber). Rekap lintas OPD (tanpa opd_id) tidak punya satu
        // versi IKU, jadi tetap lewat Renstra.
        $lingkupIku = $mode === 'kabupaten'
            ? null
            : (!empty($selectedOpdId) ? (int) $selectedOpdId : false);

        if ($lingkupIku !== false) {
            [$sumberAktif, $revisiIku] = $this->sumberDariQuery(
                $mode === 'kabupaten' ? 'kabupaten' : 'opd', $lingkupIku, (int) $tahun
            );

            if ($sumberAktif === 'iku') {
                return $this->tambahDariIku((int) $targetId, $revisiIku, (int) $tahun, $role, $lingkupIku);
            }
        }

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

    /** Form tambah untuk baris bersumber IKU Kabupaten (lingkup opd NULL). */
    /**
     * Query string + url kembali untuk satu lingkup bersumber IKU.
     *
     * @return array{0:string,1:string} [querystring form, url kembali]
     */
    private function jalurIku(int $revisiId, int $tahun, ?int $opdId): array
    {
        $lingkup = $opdId === null
            ? 'mode=kabupaten'
            : 'mode=opd&opd_id=' . $opdId;

        return [
            '?' . $lingkup . '&tahun=' . $tahun . '&sumber=iku&sumber_versi=' . $revisiId,
            base_url($this->lakipBaseUrl()) . '?' . $lingkup . '&tahun=' . $tahun,
        ];
    }

    /**
     * Form tambah untuk baris bersumber IKU.
     *
     * `$opdId` null = lingkup kabupaten; terisi = lingkup OPD itu. Dulu jalur
     * ini hanya melayani kabupaten, karena mode 'opd' di layar ini memang
     * dipatok Renstra. Sesudah barisLakipSumber() membuat mode 'opd' ikut
     * menampilkan IKU, tombol tambah/ubah-nya membawa id INDIKATOR IKU — dan
     * tanpa cabang ini id itu akan dicari di `renstra_target`, ruang angka
     * yang sama sekali lain.
     */
    private function tambahDariIku(int $indikatorId, int $revisiId, int $tahun, string $role, ?int $opdId = null)
    {
        [$qs, $kembali] = $this->jalurIku($revisiId, $tahun, $opdId);
        $namaLingkup    = $opdId === null ? 'IKU Kabupaten' : 'IKU Perangkat Daerah';

        if ($revisiId <= 0) {
            return redirect()->to($kembali)
                ->with('error', 'Belum ada versi ' . $namaLingkup . ' yang berlaku untuk tahun ' . $tahun . '.');
        }

        $target = $this->lakipModel->getIkuTargetDetail($revisiId, $indikatorId, $tahun);

        if ($target === null) {
            return redirect()->to($kembali)
                ->with('error', 'Indikator itu tidak ada pada versi IKU yang sedang dipakai.');
        }

        // Anti-IDOR: indikator dari URL harus benar-benar milik lingkup yang
        // sedang dikerjakan. Kabupaten memakai opd_id kosong.
        if ((int) ($target['opd_id'] ?? 0) !== (int) ($opdId ?? 0)) {
            return redirect()->to($kembali)
                ->with('error', 'Indikator itu bukan milik lingkup ' . $namaLingkup . ' yang sedang dibuka.');
        }

        if ($this->lakipModel->getLakipByIku($indikatorId, $tahun, $opdId) !== null) {
            return redirect()->to(base_url($this->lakipBaseUrl() . '/edit/' . $indikatorId) . $qs)
                ->with('info', 'LAKIP sudah ada. Silakan edit.');
        }

        return view('adminKabupaten/lakip/tambah_lakip', [
            'title'         => 'Tambah LAKIP',
            'role'          => $role,
            'mode'          => $opdId === null ? 'kabupaten' : 'opd',
            'tahun'         => (string) $tahun,
            'selectedOpdId' => $opdId,
            'indikator'     => [
                'indikator_sasaran' => $target['indikator_sasaran'] ?? '',
                'satuan'            => $target['satuan'] ?? '',
                'jenis_indikator'   => $target['jenis_indikator'] ?? 'positif',
            ],
            'target'        => $target,
            'opdInfo'       => $opdId !== null ? $this->opdModel->find($opdId) : null,
            'validation'    => \Config\Services::validation(),
            'sumberLakipForm' => [
                'sumber'      => 'iku',
                'versi_id'    => $revisiId,
                'entity_id'   => $indikatorId,
                'target_beku' => $target['target'] ?? null,
            ],
        ]);
    }

    /** Form edit untuk baris bersumber IKU. `$opdId` null = lingkup kabupaten. */
    private function editDariIku(int $indikatorId, int $revisiId, int $tahun, string $role, ?int $opdId = null)
    {
        [$qs, $kembali] = $this->jalurIku($revisiId, $tahun, $opdId);

        $lakip = $this->lakipModel->getLakipByIku($indikatorId, $tahun, $opdId);

        if ($lakip === null) {
            return redirect()->to(base_url($this->lakipBaseUrl() . '/tambah/' . $indikatorId) . $qs)
                ->with('info', 'Realisasi belum pernah diisi. Silakan tambah dulu.');
        }

        // Versi yang dipakai BARISNYA, bukan yang kebetulan dipilih di layar.
        $revisiBaris = (int) ($lakip['source_version_id'] ?? 0) ?: $revisiId;
        $target      = $this->lakipModel->getIkuTargetDetail($revisiBaris, $indikatorId, $tahun);

        if ($target === null || (int) ($target['opd_id'] ?? 0) !== (int) ($opdId ?? 0)) {
            return redirect()->to($kembali)
                ->with('error', 'Indikator IKU tidak sah untuk lingkup yang sedang dibuka.');
        }

        return view('adminKabupaten/lakip/edit_lakip', [
            'title'         => 'Edit LAKIP',
            'role'          => $role,
            'mode'          => $opdId === null ? 'kabupaten' : 'opd',
            'tahun'         => (string) $tahun,
            'selectedOpdId' => $opdId,
            'indikator'     => [
                'indikator_sasaran' => $target['indikator_sasaran'] ?? '',
                'satuan'            => $target['satuan'] ?? '',
            ],
            'target'        => $target,
            'lakip'         => $lakip,
            'validation'    => \Config\Services::validation(),
            'sumberLakipForm' => [
                'sumber'    => 'iku',
                'versi_id'  => $revisiBaris,
                'entity_id' => $indikatorId,
            ],
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
            // Form tambah/edit mengirim medan ini dengan nama `opd_id`;
            // keduanya divalidasi supaya tidak ada jalur yang lolos tanpa saringan.
            'opd_id' => 'permit_empty|string|max_length[20]|' . $rx,

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
            'opd_id' => ['regex_match' => 'OPD terdeteksi mengandung script / input berbahaya.'],

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
        $tahun = $this->request->getPost('tahun') ?: $this->tahunLaporanBawaan();
        // Layar mengirimnya sebagai `selected_opd_id`, form tambah/edit sebagai
        // `opd_id`. Keduanya diterima: sebelumnya hanya nama pertama yang
        // dibaca, sehingga lingkup OPD dari form selalu kosong.
        $selectedOpdId = $this->request->getPost('selected_opd_id')
            ?: $this->request->getPost('opd_id')
            ?: '';

        // Lingkup kabupaten sudah pasti sejak sini. Lingkup OPD baru diketahui
        // setelah jangkar targetnya dibaca (opd pemilik target, bukan opd yang
        // kebetulan terpilih di layar), jadi penjaganya menyusul di cabang itu.
        if ($mode !== 'opd'
            && ($tolak = $this->tolakBilaDisahkan((int) $tahun, 'kabupaten', null))) {
            return $tolak;
        }

        // Sumber IKU memakai kunci tersendiri (source_entity_id), bukan
        // rpjmd_target_id/renstra_target_id. Ditangani lebih dulu supaya cabang
        // di bawah tetap apa adanya.
        if (trim((string) $this->request->getPost('sumber_lakip')) === 'iku') {
            $lingkupIku = $mode === 'kabupaten' ? null : (int) $selectedOpdId;

            if ($lingkupIku !== null && $lingkupIku <= 0) {
                return redirect()->back()->withInput()
                    ->with('error', 'OPD pemilik indikator IKU tidak dikenali.');
            }

            // Lingkup OPD terkunci dijaga di sini; yang kabupaten sudah dijaga
            // di atas.
            if ($lingkupIku !== null
                && ($tolak = $this->tolakBilaDisahkan((int) $tahun, 'opd', $lingkupIku))) {
                return $tolak;
            }

            return $this->simpanDariIku((string) $tahun, $lingkupIku);
        }

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

            // Lingkup dibekukan saat lahir — jangan hanya menyimpan jangkar
            // id: begitu targetnya dihapus di dokumen sumber, FK me-NULL-kan
            // jangkar dan barisnya jadi yatim tak bertuan (asal 123 baris
            // realisasi hilang).
            $ikatan = $this->db->table('renstra_target rt')
                ->select('rt.tahun, rs.opd_id')
                ->join('renstra_indikator_sasaran ris', 'ris.id = rt.renstra_indikator_id')
                ->join('renstra_sasaran rs', 'rs.id = ris.renstra_sasaran_id')
                ->where('rt.id', $renstraTargetId)
                ->get()->getRowArray();

            // Lingkup OPD baru lengkap di sini: tahun & pemilik diambil dari
            // DOKUMEN, bukan dari layar.
            if ($tolak = $this->tolakBilaDisahkan(
                (int) ($ikatan['tahun'] ?? $tahun),
                'opd',
                isset($ikatan['opd_id']) ? (int) $ikatan['opd_id'] : null
            )) {
                return $tolak;
            }

            $insert = array_merge($dataCommon, [
                'renstra_target_id' => $renstraTargetId,
                'rpjmd_target_id' => null,
                'tahun' => isset($ikatan['tahun']) ? (int) $ikatan['tahun'] : null,
                'opd_id' => isset($ikatan['opd_id']) ? (int) $ikatan['opd_id'] : null,
                'mode' => 'opd',
                'source_type' => 'renstra',
                'source_entity_id' => $renstraTargetId,
            ]);
        } else {
            $rpjmdTargetId = (int) $this->request->getPost('rpjmd_target_id');
            if (!$rpjmdTargetId)
                return redirect()->back()->with('error', 'Target RPJMD tidak valid.')->withInput();

            $exist = $this->lakipModel->getLakipByRpjmdTarget($rpjmdTargetId);
            if ($exist)
                return redirect()->back()->with('error', 'LAKIP untuk target ini sudah ada. Silakan edit.')->withInput();

            $rpjmdTarget = $this->db->table('rpjmd_target')
                ->select('tahun')->where('id', $rpjmdTargetId)
                ->get()->getRowArray();

            $insert = array_merge($dataCommon, [
                'renstra_target_id' => null,
                'rpjmd_target_id' => $rpjmdTargetId,
                'tahun' => isset($rpjmdTarget['tahun']) ? (int) $rpjmdTarget['tahun'] : null,
                'opd_id' => 0,
                'mode' => 'kabupaten',
                'source_type' => 'rpjmd',
                'source_entity_id' => $rpjmdTargetId,
            ]);
        }

        $this->lakipModel->insert($insert);

        $qs = '?mode=' . $mode . '&tahun=' . $tahun;
        if ($mode === 'opd')
            $qs .= '&opd_id=' . urlencode($selectedOpdId);

        return redirect()->to(base_url('adminkab/lakip') . $qs)->with('success', 'Data LAKIP berhasil disimpan.');
    }

    /** Simpan realisasi LAKIP Kabupaten bersumber IKU Kabupaten. */
    private function simpanDariIku(string $tahun, ?int $opdId = null)
    {
        $indikator = (int) $this->request->getPost('source_entity_id');
        $revisiId  = (int) $this->request->getPost('source_version_id');
        $tahunForm = (int) ($tahun ?: $this->tahunLaporanBawaan());

        if ($indikator <= 0 || $revisiId <= 0) {
            return redirect()->back()->withInput()->with('error', 'Rujukan indikator IKU tidak sah.');
        }

        // Diperiksa ulang di server: id dari form tidak pernah dipercaya
        // sebagai bukti lingkup.
        $cek = $this->lakipModel->getIkuTargetDetail($revisiId, $indikator, $tahunForm);

        if ($cek === null || (int) ($cek['opd_id'] ?? 0) !== (int) ($opdId ?? 0)) {
            return redirect()->back()->withInput()
                ->with('error', 'Indikator IKU tidak sah untuk lingkup yang sedang dikerjakan.');
        }

        if ($this->lakipModel->getLakipByIku($indikator, $tahunForm, $opdId) !== null) {
            return redirect()->back()->withInput()
                ->with('error', 'LAKIP untuk indikator ini sudah ada. Silakan edit.');
        }

        $targetHitung = $this->request->getPost('target_hitung');

        $this->lakipModel->insert([
            'renstra_target_id' => null,
            'rpjmd_target_id'   => null,
            'tahun'             => $tahunForm,
            'opd_id'            => $opdId ?? 0,
            'mode'              => $opdId === null ? 'kabupaten' : 'opd',

            // Jejak "dinilai terhadap apa", dibekukan saat baris lahir.
            'source_type'       => 'iku',
            'source_version_id' => $revisiId,
            'source_entity_id'  => $indikator,

            'target_lalu'       => $this->request->getPost('target_lalu') ?? '',
            'capaian_lalu'      => $this->request->getPost('capaian_lalu') ?? '',
            'capaian_tahun_ini' => $this->request->getPost('capaian_tahun_ini') ?? '',
            'target_hitung'     => ($targetHitung !== null && $targetHitung !== '')
                ? $targetHitung : ($cek['target'] ?? null),
            'capaian_hitung'    => $this->request->getPost('capaian_hitung') !== ''
                ? $this->request->getPost('capaian_hitung') : null,
            'status'            => $this->request->getPost('status') ?: 'draft',
        ]);

        [, $kembali] = $this->jalurIku($revisiId, $tahunForm, $opdId);

        return redirect()
            ->to($kembali . '&sumber=iku&sumber_versi=' . $revisiId)
            ->with('success', 'Data LAKIP berhasil disimpan.');
    }

    public function edit($indikatorId)
    {
        $session = session();
        $role = $session->get('role');
        // Aksi tulis LAKIP kabupaten: admin_kab & super admin (inspektorat read-only)
        if (!in_array($role, ['admin_kab', 'admin'], true))
            return redirect()->to('/login')->with('error', 'Akses ditolak');

        $mode = $this->request->getGet('mode') ?: 'kabupaten';
        $tahun = $this->request->getGet('tahun') ?: $this->tahunLaporanBawaan();
        $selectedOpdId = $this->request->getGet('opd_id') ?: '';

        // Sama dengan tambah(): mode 'opd' ikut lewat IKU begitu ada versinya.
        $lingkupIku = $mode === 'kabupaten'
            ? null
            : (!empty($selectedOpdId) ? (int) $selectedOpdId : false);

        if ($lingkupIku !== false) {
            [$sumberAktif, $revisiIku] = $this->sumberDariQuery(
                $mode === 'kabupaten' ? 'kabupaten' : 'opd', $lingkupIku, (int) $tahun
            );

            if ($sumberAktif === 'iku') {
                return $this->editDariIku((int) $indikatorId, $revisiIku, (int) $tahun, $role, $lingkupIku);
            }
        }

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
            // Form tambah/edit mengirim medan ini dengan nama `opd_id`;
            // keduanya divalidasi supaya tidak ada jalur yang lolos tanpa saringan.
            'opd_id' => 'permit_empty|string|max_length[20]|' . $rx,

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
            'opd_id' => ['regex_match' => 'OPD terdeteksi mengandung script / input berbahaya.'],
        ];

        if (!$this->validate($rules, $messages)) {
            return redirect()->back()->withInput()
                ->with('error', implode(' ', $this->validator->getErrors()));
        }

        $mode = $this->request->getPost('mode') ?: 'kabupaten';
        $tahun = $this->request->getPost('tahun') ?: $this->tahunLaporanBawaan();
        // Layar mengirimnya sebagai `selected_opd_id`, form tambah/edit sebagai
        // `opd_id`. Keduanya diterima: sebelumnya hanya nama pertama yang
        // dibaca, sehingga lingkup OPD dari form selalu kosong.
        $selectedOpdId = $this->request->getPost('selected_opd_id')
            ?: $this->request->getPost('opd_id')
            ?: '';

        $lakipId = (int) ($this->request->getPost('lakip_id') ?? 0);
        if (!$lakipId)
            return redirect()->back()->with('error', 'ID LAKIP tidak ditemukan')->withInput();

        // Lingkup diambil dari BARISNYA, bukan dari form: tahun/mode di form
        // bisa dikarang, dan yang terkunci adalah tahun tempat baris itu
        // benar-benar berada.
        $barisLakip = $this->lakipModel->find($lakipId);

        if ($barisLakip && ($tolak = $this->tolakBilaDisahkan(
            (int) ($barisLakip['tahun'] ?? 0),
            (string) ($barisLakip['mode'] ?? 'kabupaten'),
            isset($barisLakip['opd_id']) ? (int) $barisLakip['opd_id'] : null
        ))) {
            return $tolak;
        }

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

        $lakip = $this->lakipModel->find((int) $id);

        if ($lakip && ($tolak = $this->tolakBilaDisahkan(
            (int) ($lakip['tahun'] ?? 0),
            (string) ($lakip['mode'] ?? 'kabupaten'),
            isset($lakip['opd_id']) ? (int) $lakip['opd_id'] : null
        ))) {
            return $tolak;
        }

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

        if ($tolak = $this->tolakBilaDisahkan(
            (int) ($lakip['tahun'] ?? 0),
            (string) ($lakip['mode'] ?? 'kabupaten'),
            isset($lakip['opd_id']) ? (int) $lakip['opd_id'] : null
        )) {
            return $tolak;
        }

        if ($this->lakipModel->deleteLakip((int) $id)) {
            return redirect()->back()->with('success', 'LAKIP berhasil dihapus.');
        }

        return redirect()->back()->with('error', 'Gagal menghapus LAKIP.');
    }

    /* =========================================================
     * PENGESAHAN LAKIP KABUPATEN
     *
     * Kembaran sisi OPD, untuk lingkup kabupaten (opd_id = 0). Admin
     * kabupaten mengesahkan LAKIP kabupaten sendiri; LAKIP milik OPD tetap
     * disahkan OPD-nya masing-masing.
     * =======================================================*/

    public function pengesahanSahkan()
    {
        if (! user_can('lakip_kab.finalisasi')) {
            return redirect()->back()->with('error', 'Anda tidak berwenang mengesahkan LAKIP Kabupaten.');
        }

        $tahun = (int) ($this->request->getPost('tahun') ?: (date('Y') - 1));
        $m     = new \App\Models\LakipPengesahanModel();

        try {
            $hasil = $m->sahkan($tahun, 'kabupaten', null, session()->get('user_id'), [
                'nomor'   => $this->request->getPost('nomor') ?: null,
                'catatan' => $this->request->getPost('catatan') ?: null,
            ]);
        } catch (\Throwable $e) {
            return redirect()->to(base_url('adminkab/lakip?mode=kabupaten&tahun=' . $tahun))
                ->with('error', $e->getMessage());
        }

        return redirect()->to(base_url('adminkab/lakip?mode=kabupaten&tahun=' . $tahun))->with(
            'success',
            ($hasil['sahkan_ulang'] ? 'LAKIP Kabupaten ' . $tahun . ' disahkan ulang'
                                    : 'LAKIP Kabupaten ' . $tahun . ' disahkan')
            . ' dan dikunci — ' . $hasil['jumlah_realisasi'] . ' realisasi tercakup.'
        );
    }

    public function pengesahanAjukan()
    {
        if (! user_can('lakip_kab.finalisasi')) {
            return redirect()->back()->with('error', 'Anda tidak berwenang mengajukan perbaikan.');
        }

        $tahun = (int) ($this->request->getPost('tahun') ?: (date('Y') - 1));
        $m     = new \App\Models\LakipPengesahanModel();

        try {
            $m->ajukanPembukaan($tahun, 'kabupaten', null,
                (string) $this->request->getPost('alasan'), session()->get('user_id'));
        } catch (\Throwable $e) {
            return redirect()->to(base_url('adminkab/lakip?mode=kabupaten&tahun=' . $tahun))
                ->with('error', $e->getMessage());
        }

        return redirect()->to(base_url('adminkab/lakip?mode=kabupaten&tahun=' . $tahun))
            ->with('success', 'Permintaan perbaikan dicatat. Buka lewat menu Permintaan Perbaikan.');
    }

    public function pengesahanTarik($permintaanId)
    {
        $tahun = (int) ($this->request->getPost('tahun') ?: (date('Y') - 1));
        $m     = new \App\Models\LakipPengesahanModel();

        $keadaan  = $m->keadaan($tahun, 'kabupaten', null);
        $menunggu = $keadaan ? $m->permintaanMenunggu((int) $keadaan['id']) : null;

        if ($menunggu === null || (int) $menunggu['id'] !== (int) $permintaanId) {
            return redirect()->to(base_url('adminkab/lakip?mode=kabupaten&tahun=' . $tahun))
                ->with('error', 'Permintaan itu bukan milik lingkup kabupaten atau sudah diputuskan.');
        }

        try {
            $m->tarik((int) $permintaanId);
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->to(base_url('adminkab/lakip?mode=kabupaten&tahun=' . $tahun))
            ->with('success', 'Permintaan perbaikan ditarik kembali.');
    }

    /* =========================================================
     * KOTAK MASUK: PERMINTAAN PERBAIKAN LAKIP DARI OPD
     *
     * Kembaran alur verifikasi revisi IKU, disengaja: operator kabupaten
     * tidak perlu menghafal dua tata cara untuk dua dokumen.
     * =======================================================*/

    public function permintaanIndex()
    {
        if (! user_can('lakip_opd.buka_kunci')) {
            return redirect()->to(base_url('adminkab/lakip'))
                ->with('error', 'Anda tidak berwenang memutuskan permintaan pembukaan LAKIP.');
        }

        $m = new \App\Models\LakipPengesahanModel();

        return view('adminKabupaten/lakip/permintaan', [
            'title'      => 'Permintaan Perbaikan LAKIP',
            'permintaan' => $m->menungguKeputusan(),
        ]);
    }

    public function permintaanPutuskan($permintaanId, $keputusan)
    {
        if (! user_can('lakip_opd.buka_kunci')) {
            return redirect()->to(base_url('adminkab/lakip'))
                ->with('error', 'Anda tidak berwenang memutuskan permintaan pembukaan LAKIP.');
        }

        if (! in_array($keputusan, ['setujui', 'tolak'], true)) {
            return redirect()->back()->with('error', 'Keputusan tidak dikenal.');
        }

        $m         = new \App\Models\LakipPengesahanModel();
        $tanggapan = $this->request->getPost('tanggapan') ?: null;

        // Menolak WAJIB beralasan; menyetujui tidak. Penolakan tanpa sebab
        // membuat OPD menebak-nebak apa yang harus diperbaiki.
        if ($keputusan === 'tolak' && trim((string) $tanggapan) === '') {
            return redirect()->back()->with('error', 'Alasan penolakan wajib diisi.');
        }

        try {
            $keputusan === 'setujui'
                ? $m->setujui((int) $permintaanId, session()->get('user_id'), $tanggapan)
                : $m->tolak((int) $permintaanId, session()->get('user_id'), $tanggapan);
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->to(base_url('adminkab/lakip/permintaan'))->with(
            'success',
            $keputusan === 'setujui'
                ? 'Permintaan disetujui — LAKIP tahun itu dibuka untuk diperbaiki OPD.'
                : 'Permintaan ditolak. LAKIP tahun itu tetap terkunci.'
        );
    }
}
