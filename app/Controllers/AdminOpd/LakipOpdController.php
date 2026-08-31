<?php

namespace App\Controllers\AdminOpd;

use App\Controllers\BaseController;
use App\Controllers\Concerns\LakipAddendumTrait;
use App\Controllers\Concerns\LakipSumberTrait;
use App\Controllers\Concerns\LakipBenchmarkTrait;
use App\Controllers\Concerns\LakipSnapshotTrait;
use App\Models\LakipModel;
use App\Models\Opd\RenstraModel;
use App\Models\OpdModel;
use App\Models\RpjmdModel;

class LakipOpdController extends BaseController
{
    /**
     * Analisis Faktor + Efisiensi Program (dua tabel di bawah tabel utama).
     *
     * `sumberDokumenLakip` di-alias supaya versi bawaannya masih bisa dipanggil
     * setelah ditimpa di kelas ini — PHP memenangkan metode kelas atas metode
     * trait tanpa menyediakan `parent::` untuk trait.
     */
    use LakipAddendumTrait {
        // Nama trait ditulis lengkap: LakipSnapshotTrait mendeklarasikan
        // metode yang sama sebagai abstract, dan tanpa penunjukan ini PHP
        // menganggap aliasnya ambigu.
        LakipAddendumTrait::sumberDokumenLakip as private sumberDokumenBawaan;
    }

    /** Pemilih dokumen sumber (IKU/Renstra/RPJMD + versi) — dipakai juga AdminKab\LakipController. */
    use LakipSumberTrait;

    /** Chart perbandingan Provinsi Lampung & Nasional (di atas Analisis Faktor). */
    use LakipBenchmarkTrait;

    /**
     * Snapshot tahunan + kunci tahun + penyesuaian kebijakan.
     *
     * `barisHidupUntukSnapshot` di-alias supaya versi bawaannya masih bisa
     * dipanggil setelah ditimpa di kelas ini — PHP memenangkan metode kelas
     * atas metode trait tanpa menyediakan `parent::` untuk trait.
     */
    use LakipSnapshotTrait {
        barisHidupUntukSnapshot as private barisHidupBawaan;
    }

    protected $lakipModel;

    /** Pengesahan LAKIP per tahun (kunci + permintaan pembukaan). */
    protected $pengesahanModel;
    protected $renstraModel;
    protected $rpjmdModel;
    protected $opdModel;
    protected $db;

    public function __construct()
    {
        $this->lakipModel = new LakipModel();
        $this->pengesahanModel = new \App\Models\LakipPengesahanModel();
        $this->renstraModel = new RenstraModel();
        $this->rpjmdModel = new RpjmdModel();
        $this->opdModel = new OpdModel();
        $this->db = \Config\Database::connect();

        helper(['form', 'url']);
    }
    private function xssRule(): string
    {
        return 'regex_match[/^(?!.*<\s*script\b)(?!.*<\/\s*script\s*>)(?!.*javascript\s*:)(?!.*data\s*:\s*text\/html)(?!.*on\w+\s*=)(?!.*<\?php)(?!.*<\?).*$/is]';
    }

    /** Prefix rute halaman ini — dipakai LakipAddendumTrait untuk redirect & action form. */
    protected function lakipBaseUrl(): string
    {
        return 'adminopd/lakip';
    }

    /** Prefix permission untuk aksi snapshot/finalisasi/penyesuaian. */
    protected function lakipPermPrefix(): string
    {
        return 'lakip_opd';
    }

    /**
     * Sumber dokumen yang sedang dipilih di layar.
     *
     * Menimpa seam LakipSnapshotTrait::sumberDokumenLakip() — dialiaskan di
     * blok `use` di atas supaya pembayangannya tercatat sebagai disengaja.
     */
    protected function sumberDokumenLakip(string $mode): ?string
    {
        if ($mode === 'kabupaten') {
            // Kabupaten pun punya pemilih sumber (bawaan IKU Kabupaten, §24).
            // Dipatok 'rpjmd' dulu membuat panel bawah menyaring dengan
            // dokumen yang berbeda dari tabel utamanya.
            [$sumber] = $this->sumberDariPermintaan(
                'kabupaten',
                null,
                (int) ($this->request->getGet('tahun') ?: date('Y'))
            );

            return $sumber !== '' ? $sumber : 'rpjmd';
        }

        // Hanya JENIS sumbernya yang dipakai di sini (untuk menyaring
        // penyesuaian kebijakan), bukan versinya — karena itu lingkup OPD
        // sengaja dibiarkan null: melewatkannya hanya akan menyiratkan bahwa
        // id versi yang dikembalikan sudah tervalidasi untuk OPD tertentu,
        // padahal nilai itu memang dibuang di baris ini.
        [$sumber] = $this->sumberDariPermintaan(
            'opd',
            null,
            (int) ($this->request->getGet('tahun') ?: date('Y'))
        );

        return $sumber !== '' ? $sumber : null;
    }

    /**
     * Baris yang akan DIBEKUKAN snapshot, mengikuti sumber di layar.
     *
     * Menimpa LakipSnapshotTrait::barisHidupUntukSnapshot() dengan SENGAJA —
     * lihat catatan panjang di seam itu. Ringkasnya: default trait selalu
     * mengambil Renstra untuk mode OPD, padahal layar ini punya pemilih sumber.
     * Membekukan Renstra dari layar yang menampilkan IKU menghasilkan dokumen
     * beku yang isinya tidak pernah dilihat siapa pun.
     *
     * Sumber dibaca dari POST (form snapshot) DAN GET (tautan Bandingkan),
     * karena kedua jalur memanggil ke sini.
     */
    protected function barisHidupUntukSnapshot(string $mode, string $tahun, string $status, $opd): array
    {
        $opdId = (int) $opd;

        // Kabupaten ikut dilayani: lingkupnya opd NULL, sumber bawaannya IKU
        // Kabupaten (§24). Mode lain yang tak dikenal jatuh ke bawaan trait.
        if ($mode !== 'opd' && $mode !== 'kabupaten') {
            return $this->barisHidupBawaan($mode, $tahun, $status, $opd);
        }

        if ($mode === 'opd' && $opdId <= 0) {
            return $this->barisHidupBawaan($mode, $tahun, $status, $opd);
        }

        $opdScope = $mode === 'kabupaten' ? null : $opdId;

        [$sumber, $revisiId] = $this->sumberDariPermintaan($mode, $opdScope, (int) $tahun);

        if ($sumber !== \App\Services\Version\LakipSourceService::SUMBER_IKU || $revisiId <= 0) {
            return $this->barisHidupBawaan($mode, $tahun, $status, $opd);
        }

        return [
            'rows'            => $this->lakipModel->getIndexIkuTargets($revisiId, (int) $tahun, $opdScope),
            'lakipMap'        => $this->lakipModel->getLakipMapIku((int) $tahun, $status !== '' ? $status : null, $opdScope),
            'sumber_type'     => \App\Services\Version\LakipSourceService::SUMBER_IKU,
            'sumber_versi_id' => $revisiId,
        ];
    }

    /**
     * Tukar sumber tabel utama ke arsip beku bila halaman ini sedang membaca
     * snapshot.
     *
     * Pengelompokan & pemetaan ulang dikerjakan ULANG dengan fungsi yang sama
     * persis dengan jalur data hidup (groupIndexRowsBySasaran + rekey ke
     * indikator_id), supaya tampilan arsip dan tampilan hidup tidak pernah
     * berbeda bentuk. Jalur OPD memang memakai bentuk yang berbeda dari jalur
     * Kabupaten — itu perilaku lama yang sengaja dipertahankan.
     *
     * @return array{0:array, 1:array, 2:array, 3:array} [rows, dataSource, lakipMap, sumber]
     */
    private function sumberLakipOpd(array $scope, string $status, array $rows, array $lakipMapTarget, array $dataSource, array $lakipMap): array
    {
        $sumber = $this->sumberLakip($scope, $status, ['rows' => $rows, 'lakipMap' => $lakipMapTarget]);

        if (empty($sumber['dariSnapshot'])) {
            return [$rows, $dataSource, $lakipMap, $sumber];
        }

        $rows     = $sumber['rows'];
        $lakipMap = [];

        foreach ($sumber['lakipMap'] as $l) {
            if (! empty($l['indikator_id'])) {
                $lakipMap[(int) $l['indikator_id']] = $l;
            }
        }

        $dataSource = $this->lakipModel->groupIndexRowsBySasaran($rows, (string) $scope['mode']);

        return [$rows, $dataSource, $lakipMap, $sumber];
    }

    private function buildQs(?string $tahun, ?string $status, ?string $mode = null, ?int $opdId = null): string
    {
        $params = [];
        if (!empty($mode))
            $params['mode'] = $mode;
        if (!empty($opdId))
            $params['opd_id'] = $opdId;
        if (!empty($tahun))
            $params['tahun'] = $tahun;
        if (!empty($status))
            $params['status'] = $status;

        // Sumber & versi ikut dibawa ke halaman tambah/edit dan kembali lagi.
        // Tanpa itu, menekan "+" pada baris bersumber IKU akan mendarat di
        // formulir yang mencari targetnya di Renstra — lalu melaporkan
        // "target belum diisi" atas indikator yang targetnya jelas terpampang.
        $sumber = trim((string) ($this->request->getGet('sumber') ?? ''));
        $versi  = (int) ($this->request->getGet('sumber_versi') ?? 0);

        if ($sumber !== '') {
            $params['sumber'] = $sumber;
        }

        if ($versi > 0) {
            $params['sumber_versi'] = $versi;
        }

        return empty($params) ? '' : ('?' . http_build_query($params));
    }

    /**
     * Sumber yang sedang dipakai halaman tambah/edit realisasi.
     *
     * @return array{0:string,1:int} [sumber, id revisi IKU]
     */
    

    /* =========================================================
     * INDEX
     * =======================================================*/

    /* =========================================================
     * PEMILIHAN SUMBER LAKIP (§24-§28)
     *
     * =====================================================================
     * IKU LEBIH DULU, RENSTRA CADANGAN
     *
     * Alur yang benar: LAKIP menilai capaian terhadap INDIKATOR KINERJA
     * UTAMA, bukan terhadap seluruh isi Renstra. Karena itu IKU jadi bawaan.
     *
     * Renstra tetap tersedia — dan itu bukan sekadar kelonggaran: ada tahun
     * laporan yang belum dipayungi revisi IKU mana pun (IKU-nya baru disahkan
     * belakangan). Tanpa cadangan itu, LAKIP tahun tersebut tidak punya
     * sumber sama sekali dan tabelnya kosong tanpa jalan keluar.
     *
     * =====================================================================
     * TANGGAL RUJUKAN: 31 DESEMBER TAHUN LAPORAN
     *
     * Bukan hari ini. LAKIP menilai kinerja satu tahun penuh, jadi yang
     * relevan adalah dokumen yang berlaku di UJUNG tahun itu — bukan yang
     * kebetulan berlaku saat laporannya disusun, yang bisa terpaut bertahun.
     *
     * @return array{
     *     sumber:string, versi:?array, daftar_versi:array,
     *     pilihan_sumber:array, alasan_wajib:bool, catatan:?string
     * }
     */
    

    /**
     * Baris tabel LAKIP dari sumber yang dipilih.
     *
     * @return array{0:array,1:array} [dataSource, lakipMap]
     */
    /**
     * Bagan tabel utama SEKALIGUS baris datarnya.
     *
     * `$rows` datar ikut dikembalikan, bukan dibuang. Dua panel di bawah tabel
     * utama — Perbandingan Provinsi/Nasional dan Analisis Faktor — membaca
     * daftar indikatornya dari sana, bukan dari $dataSource yang sudah
     * dikelompokkan. Ketika baris datar itu hilang, tabel utama tetap terisi
     * sementara kedua panel melapor "belum ada indikator pada tahun ini" —
     * kontradiksi yang tampak seperti data hilang, padahal hanya tidak
     * diteruskan.
     *
     * @return array{0:array, 1:array, 2:array} [dataSource, lakipMap, rows]
     */
    

    public function index()
    {
        $session = session();
        $role = $session->get('role');
        $opdId = (int) $session->get('opd_id');

        $tahun = $this->request->getGet('tahun') ?: (date('Y') - 1);
        $status = $this->request->getGet('status');

        $availableYears = $this->lakipModel->getAvailableYears();

        $mode = 'opd';
        $selectedOpdId = null;
        $opdInfo = null;
        $opdList = [];

        // OUTPUT untuk VIEW BARU kamu
        $dataSource = [];
        $lakipMap = [];
        $qsBase = '';

        if ($role === 'admin_kab') {
            // admin kab boleh mode kabupaten/opd
            $mode = $this->request->getGet('mode') ?: 'kabupaten';
            $selectedOpdId = $this->request->getGet('opd_id') ? (int) $this->request->getGet('opd_id') : null;
            $opdList = $this->opdModel->orderBy('nama_opd', 'ASC')->findAll();

            if ($mode === 'kabupaten') {
                // Kabupaten memakai pemilih sumber yang SAMA dengan OPD:
                // bawaan IKU Kabupaten, cadangan RPJMD (§24).
                $pilihanSumberKab = $this->pilihanSumberLakip('kabupaten', null, (int) $tahun);

                [$dataSource, $lakipMap, $rows] = $this->baganLakip(
                    $pilihanSumberKab, 'kabupaten', null, (int) $tahun, $status
                );

                $qsBase = $this->buildQs((string) $tahun, $status, 'kabupaten', null);
            } else {
                // mode opd (admin_kab wajib pilih OPD)
                if (!empty($selectedOpdId)) {
                    $opdInfo = $this->opdModel->find($selectedOpdId);

                    // Admin Kabupaten melihat LAKIP OPD lewat pemilih sumber yang
                    // SAMA dengan yang dilihat OPD-nya sendiri. Kalau tidak, dua
                    // orang membuka halaman yang sama dan membaca angka berbeda.
                    $pilihanSumberKab = $this->pilihanSumberLakip('opd', $selectedOpdId, (int) $tahun);

                    [$dataSource, $lakipMap, $rows] = $this->baganLakip(
                        $pilihanSumberKab, 'opd', $selectedOpdId, (int) $tahun, $status
                    );
                }

                $qsBase = $this->buildQs((string) $tahun, $status, 'opd', $selectedOpdId);
            }

            $data = [
                'title' => 'LAKIP - Admin Kabupaten',
                'role' => $role,
                'mode' => $mode,
                'opdList' => $opdList,
                'selectedOpdId' => $selectedOpdId,
                'opdInfo' => $opdInfo,
                'availableYears' => $availableYears,

                // ini yang dipakai view kamu
                'dataSource' => $dataSource,
                'lakipMap' => $lakipMap,
                'qsBase' => $qsBase,
                'tahunAktif' => (string) $tahun,

                'filters' => [
                    'tahun' => (string) $tahun,
                    'status' => $status,
                ],

                'sumberLakip' => $pilihanSumberKab ?? null,
            ];

        } else {
            // admin_opd
            if (!$opdId) {
                return redirect()->to('/login')->with('error', 'Session tidak valid');
            }

            $opdInfo = $this->opdModel->find($opdId);

            $pilihanSumber = $this->pilihanSumberLakip('opd', $opdId, (int) $tahun);

            [$dataSource, $lakipMap, $rows] = $this->baganLakip(
                $pilihanSumber, 'opd', $opdId, (int) $tahun, $status
            );

            $qsBase = $this->buildQs((string) $tahun, $status);

            $data = [
                'title' => 'LAKIP OPD - ' . ($opdInfo['nama_opd'] ?? ''),
                'role' => $role,
                'mode' => 'opd',
                'opdInfo' => $opdInfo,
                'availableYears' => $availableYears,

                // ini yang dipakai view kamu
                'dataSource' => $dataSource,
                'lakipMap' => $lakipMap,
                'qsBase' => $qsBase,
                'tahunAktif' => (string) $tahun,

                'filters' => [
                    'tahun' => (string) $tahun,
                    'status' => $status,
                ],

                // Sumber & versi yang sedang dipakai — dipakai pemilih di layar
                // dan keterangan "dibandingkan dengan apa".
                'sumberLakip' => $pilihanSumber,
            ];
        }

        // Dua tabel tambahan (Analisis Faktor & Efisiensi Program) memakai
        // tahun + lingkup yang sama dengan tabel utama. $rows bisa belum ada
        // kalau admin_kab memilih mode OPD tanpa memilih OPD-nya.
        $rows  = $rows ?? [];
        $scope = $this->lakipScope((string) $tahun, $mode);

        [$rows, $dataSource, $lakipMap, $sumber] = $this->sumberLakipOpd(
            $scope,
            (string) ($status ?? ''),
            $rows,
            $lakipMapTarget ?? [],
            $dataSource ?? [],
            $lakipMap ?? []
        );

        $data['dataSource'] = $dataSource;
        $data['lakipMap']   = $lakipMap;

        // Chart benchmark memakai $rows/$lakipMap SESUDAH snapshot dipakai,
        // supaya angka pada chart selalu sama dengan tabel di atasnya.
        $data = array_merge(
            $data,
            $this->addendumLakip($scope, $sumber),
            $this->lakipBenchmarkData($scope, $rows, $lakipMap),
            $this->dataSnapshot($scope, $sumber),
            [
            'indikatorRows' => $rows,
            'addendumBase'  => $this->lakipBaseUrl(),
            // Tahun terkunci: tombol pengubah pada tabel utama ikut padam.
            'lakipTerkunci' => ! empty($sumber['terkunci']),
        ],
            $this->dataPengesahan((int) $tahun, $scope)
        );

        return view('adminOpd/lakip/lakip', $data);
    }

    public function cetak()
    {
        if (ob_get_level() > 0) {
            @ob_clean();
        }

        helper(['number', 'lakip', 'setting']);

        $session = session();
        $role = $session->get('role');
        $opdId = (int) $session->get('opd_id');

        if (!in_array($role, ['admin_opd', 'admin_kecamatan', 'admin_kab'], true)) {
            return redirect()->to('/login')->with('error', 'Akses ditolak');
        }

        $tahun = $this->request->getGet('tahun') ?: (date('Y') - 1);
        $status = $this->request->getGet('status');

        $mode = 'opd';
        $selectedOpdId = null;
        $opdInfo = null;
        $opdList = [];
        $dataSource = [];
        $lakipMap = [];

        if ($role === 'admin_kab') {
            $mode = $this->request->getGet('mode') ?: 'kabupaten';
            $selectedOpdId = $this->request->getGet('opd_id') ? (int) $this->request->getGet('opd_id') : null;
            $opdList = $this->opdModel->orderBy('nama_opd', 'ASC')->findAll();

            if ($mode === 'kabupaten') {
                $rows = $this->lakipModel->getIndexRpjmdTargets((string) $tahun);
                $lakipMapTarget = $this->lakipModel->getLakipMapRpjmd((string) $tahun, $status ?: null);

                foreach ($lakipMapTarget as $l) {
                    if (!empty($l['indikator_id'])) {
                        $lakipMap[(int) $l['indikator_id']] = $l;
                    }
                }

                $dataSource = $this->lakipModel->groupIndexRowsBySasaran($rows, 'kabupaten');
            } else {
                if (!empty($selectedOpdId)) {
                    $opdInfo = $this->opdModel->find($selectedOpdId);
                    $rows = $this->lakipModel->getIndexRenstraTargets((string) $tahun, $selectedOpdId);
                    $lakipMapTarget = $this->lakipModel->getLakipMapRenstra((string) $tahun, $status ?: null, $selectedOpdId);

                    foreach ($lakipMapTarget as $l) {
                        if (!empty($l['indikator_id'])) {
                            $lakipMap[(int) $l['indikator_id']] = $l;
                        }
                    }

                    $dataSource = $this->lakipModel->groupIndexRowsBySasaran($rows, 'opd');
                }
            }
        } else {
            if (!$opdId) {
                return redirect()->to('/login')->with('error', 'Session tidak valid');
            }

            $opdInfo = $this->opdModel->find($opdId);

            /* Cetakan mengikuti apa yang tampil di layar. Tanpa ini, layar
             * yang sedang menampilkan IKU akan menghasilkan PDF berisi
             * Renstra — dua dokumen yang sama-sama tampak resmi, dengan
             * angka target yang berbeda. */
            $pilihanCetak = $this->pilihanSumberLakip('opd', $opdId, (int) $tahun);

            if ($pilihanCetak['sumber'] === \App\Services\Version\LakipSourceService::SUMBER_IKU
                && ! empty($pilihanCetak['versi'])) {
                $rows     = $this->lakipModel->getIndexIkuTargets(
                    (int) $pilihanCetak['versi']['id'], (int) $tahun, $opdId
                );
                $lakipMap       = $this->lakipModel->getLakipMapIku((int) $tahun, $status ?: null, $opdId);
                $lakipMapTarget = $lakipMap;
                $sumberCetak    = $pilihanCetak;
            } else {
                $rows = $this->lakipModel->getIndexRenstraTargets((string) $tahun, $opdId);
                $lakipMapTarget = $this->lakipModel->getLakipMapRenstra((string) $tahun, $status ?: null, $opdId);

                foreach ($lakipMapTarget as $l) {
                    if (!empty($l['indikator_id'])) {
                        $lakipMap[(int) $l['indikator_id']] = $l;
                    }
                }
            }

            $dataSource = $this->lakipModel->groupIndexRowsBySasaran($rows, 'opd');
        }

        $unitName = $opdInfo['nama_opd'] ?? (($mode === 'kabupaten') ? 'Kabupaten Pringsewu' : 'Seluruh OPD');

        // Dua tabel tambahan ikut tercetak, memakai tahun & lingkup yang sama.
        $rows  = $rows ?? [];
        $scope = $this->lakipScope((string) $tahun, $mode);

        [$rows, $dataSource, $lakipMap, $sumber] = $this->sumberLakipOpd(
            $scope,
            (string) ($status ?? ''),
            $rows,
            $lakipMapTarget ?? [],
            $dataSource ?? [],
            $lakipMap ?? []
        );

        $html = view('adminOpd/lakip/lakip_cetak', array_merge($this->addendumLakip($scope, $sumber), [
            'title' => 'Cetak LAKIP',
            'role' => $role,
            'mode' => $mode,
            'opdInfo' => $opdInfo,
            'opdList' => $opdList,
            'selectedOpdId' => $selectedOpdId,
            'dataSource' => $dataSource,
            'lakipMap' => $lakipMap,
            'filters' => [
                'tahun' => (string) $tahun,
                'status' => $status,
            ],
            'unitName' => $unitName,
            'indikatorRows' => $rows,
        ]));

        // ============================================================
        // CETAK LAKIP: TANPA KOP, WATERMARK, HEADER, & FOOTER HALAMAN.
        //
        // Dokumen langsung dimulai dari judul "LAPORAN AKUNTABILITAS KINERJA
        // INSTANSI PEMERINTAH" (lihat view adminOpd/lakip/lakip_cetak).
        // Karena itu di sini SENGAJA TIDAK dipanggil:
        //   - $mpdf->SetHTMLHeader() / SetHTMLFooter()   -> tanpa header/footer & nomor halaman
        //   - pdf_watermark_aksara()                     -> tanpa watermark (SetWatermarkImage)
        //   - templates/pdf_kop (di view)                -> tanpa KOP & logo instansi
        // Modul PDF lain (Cascading, Renstra, RKT, MONEV, dst.) TIDAK diubah dan
        // tetap memakai kop/footer/watermark standar.
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
        $opdId = (int) $session->get('opd_id');

        if (!in_array($role, ['admin_opd', 'admin_kecamatan', 'admin_kab'], true)) {
            return redirect()->to('/login')->with('error', 'Akses ditolak');
        }

        $tahun = $this->request->getGet('tahun') ?: (date('Y') - 1);
        $status = $this->request->getGet('status');

        $mode = 'opd';
        $selectedOpdId = null;
        $opdInfo = null;
        $dataSource = [];
        $lakipMap = [];

        if ($role === 'admin_kab') {
            $mode = $this->request->getGet('mode') ?: 'kabupaten';
            $selectedOpdId = $this->request->getGet('opd_id') ? (int) $this->request->getGet('opd_id') : null;

            if ($mode === 'kabupaten') {
                $rows = $this->lakipModel->getIndexRpjmdTargets((string) $tahun);
                $lakipMapTarget = $this->lakipModel->getLakipMapRpjmd((string) $tahun, $status ?: null);

                foreach ($lakipMapTarget as $l) {
                    if (!empty($l['indikator_id'])) {
                        $lakipMap[(int) $l['indikator_id']] = $l;
                    }
                }

                $dataSource = $this->lakipModel->groupIndexRowsBySasaran($rows, 'kabupaten');
            } else {
                if (!empty($selectedOpdId)) {
                    $opdInfo = $this->opdModel->find($selectedOpdId);
                    $rows = $this->lakipModel->getIndexRenstraTargets((string) $tahun, $selectedOpdId);
                    $lakipMapTarget = $this->lakipModel->getLakipMapRenstra((string) $tahun, $status ?: null, $selectedOpdId);

                    foreach ($lakipMapTarget as $l) {
                        if (!empty($l['indikator_id'])) {
                            $lakipMap[(int) $l['indikator_id']] = $l;
                        }
                    }

                    $dataSource = $this->lakipModel->groupIndexRowsBySasaran($rows, 'opd');
                }
            }
        } else {
            if (!$opdId) {
                return redirect()->to('/login')->with('error', 'Session tidak valid');
            }

            $opdInfo = $this->opdModel->find($opdId);

            // Excel mengikuti layar, sama seperti cetak PDF — lihat catatan di sana.
            $pilihanCetak = $this->pilihanSumberLakip('opd', $opdId, (int) $tahun);

            if ($pilihanCetak['sumber'] === \App\Services\Version\LakipSourceService::SUMBER_IKU
                && ! empty($pilihanCetak['versi'])) {
                $rows = $this->lakipModel->getIndexIkuTargets(
                    (int) $pilihanCetak['versi']['id'], (int) $tahun, $opdId
                );
                $lakipMap       = $this->lakipModel->getLakipMapIku((int) $tahun, $status ?: null, $opdId);
                $lakipMapTarget = $lakipMap;
            } else {
                $rows = $this->lakipModel->getIndexRenstraTargets((string) $tahun, $opdId);
                $lakipMapTarget = $this->lakipModel->getLakipMapRenstra((string) $tahun, $status ?: null, $opdId);

                foreach ($lakipMapTarget as $l) {
                    if (!empty($l['indikator_id'])) {
                        $lakipMap[(int) $l['indikator_id']] = $l;
                    }
                }
            }

            $dataSource = $this->lakipModel->groupIndexRowsBySasaran($rows, 'opd');
        }

        $unitName = $opdInfo['nama_opd'] ?? (($mode === 'kabupaten') ? 'Kabupaten Pringsewu' : 'Seluruh OPD');

        // Sheet tambahan: Analisis Faktor & Efisiensi Program.
        $rows  = $rows ?? [];
        $scope = $this->lakipScope((string) $tahun, $mode);

        [$rows, $dataSource, $lakipMap, $sumber] = $this->sumberLakipOpd(
            $scope,
            (string) ($status ?? ''),
            $rows,
            $lakipMapTarget ?? [],
            $dataSource ?? [],
            $lakipMap ?? []
        );

        $addendum = $this->addendumLakip($scope, $sumber);

        lakip_opd_excel($dataSource, $lakipMap, [
            'unit' => $unitName,
            'mode' => $mode,
            'tahun' => (string) $tahun,
            'status' => (string) ($status ?? ''),
        ], [
            'indikatorRows' => $rows,
            'analisisMap'   => $addendum['analisisMap'],
            'efisiensiRows' => $addendum['efisiensiRows'],
        ]);
    }
    /* =========================================================
     * FORM TAMBAH (FIX redirect back)
     * =======================================================*/

    /**
     * Form tambah realisasi untuk baris bersumber IKU.
     *
     * Dipisah dari cabang Renstra, bukan disisipkan ke dalamnya: keduanya
     * memeriksa kepemilikan lewat jalur yang berbeda (IKU punya `opd_id`
     * sendiri; Renstra harus ditelusuri lewat sasarannya), dan menggabungkan
     * dua pemeriksaan berbeda dalam satu rangkaian if adalah cara termudah
     * membuat salah satunya terlewat.
     */
    private function tambahDariIku(
        int $indikatorId,
        int $revisiId,
        int $tahun,
        int $opdId,
        string $role,
        string $qsBack
    ) {
        $kembali = base_url('adminopd/lakip') . $qsBack;

        if ($revisiId <= 0) {
            return redirect()->to($kembali)
                ->with('error', 'Belum ada versi IKU yang berlaku untuk tahun ' . $tahun . '.');
        }

        $target = $this->lakipModel->getIkuTargetDetail($revisiId, $indikatorId, $tahun);

        if ($target === null) {
            return redirect()->to($kembali)
                ->with('error', 'Indikator itu tidak ada pada versi IKU yang sedang dipakai.');
        }

        // Kepemilikan diperiksa dari IKU-nya sendiri, bukan dari sesi saja.
        if ((int) ($target['opd_id'] ?? 0) !== $opdId) {
            return redirect()->to($kembali)
                ->with('error', 'Akses ditolak: indikator bukan milik OPD Anda.');
        }

        if ($this->lakipModel->getLakipByIku($indikatorId, $tahun, $opdId) !== null) {
            return redirect()->to(base_url('adminopd/lakip/edit/' . $indikatorId) . $qsBack)
                ->with('info', 'LAKIP sudah ada. Silakan edit.');
        }

        return view('adminOpd/lakip/tambah_lakip', [
            'title'      => 'Tambah LAKIP',
            'role'       => $role,
            'indikator'  => [
                'id'                => $indikatorId,
                'indikator_sasaran' => $target['indikator_sasaran'] ?? '',
                'satuan'            => $target['satuan'] ?? '',
                'jenis_indikator'   => $target['jenis_indikator'] ?? 'positif',
                'sasaran'           => $target['sasaran'] ?? '',
            ],
            'target'     => $target,
            'opdInfo'    => $this->opdModel->find($opdId),
            'tahun'      => (string) $tahun,
            'qsBase'     => $qsBack,
            'validation' => \Config\Services::validation(),

            // Dibawa ke form supaya penyimpanan tahu sumbernya, dan supaya
            // baris LAKIP membekukan dari dokumen mana ia dinilai.
            'sumberLakipForm' => [
                'sumber'      => 'iku',
                'versi_id'    => $revisiId,
                'entity_id'   => $indikatorId,
                'target_beku' => $target['target'] ?? null,
            ],
        ]);
    }

    public function tambah($indikatorId = null)
    {
        $session = session();
        $role = $session->get('role');
        $opdId = (int) $session->get('opd_id');

        if (!in_array($role, ['admin_opd', 'admin_kecamatan'], true) || !$opdId) {
            return redirect()->to('/login')->with('error', 'Session tidak valid');
        }

        // Bawaan = TAHUN LALU, sama dengan index()/cetak(): LAKIP menilai
        // tahun yang sudah selesai. Sebelumnya di sini bawaannya tahun
        // BERJALAN, sehingga membuka form dari layar 2025 tanpa parameter
        // diam-diam berpindah ke 2026 dan menolak dengan alasan
        // "target belum diisi".
        $tahun = $this->request->getGet('tahun') ?: (date('Y') - 1);
        $status = $this->request->getGet('status');
        $qsBack = $this->buildQs((string) $tahun, $status);

        $indikatorId = (int) $indikatorId;
        if (!$indikatorId) {
            return redirect()->to(base_url('adminopd/lakip') . $qsBack)->with('error', 'Indikator tidak valid.');
        }

        [$sumberAktif, $revisiIku] = $this->sumberDariQuery('opd', $opdId, (int) $tahun);

        if ($sumberAktif === 'iku') {
            return $this->tambahDariIku($indikatorId, $revisiIku, (int) $tahun, $opdId, $role, $qsBack);
        }

        // ambil target detail via MODEL (lebih aman)
        $target = $this->lakipModel->getRenstraTargetDetailByIndikatorAndYear($indikatorId, (string) $tahun);

        if (!$target) {
            return redirect()->to(base_url('adminopd/lakip') . $qsBack)
                ->with('error', 'Target tahun ' . $tahun . ' untuk indikator ini belum diisi.');
        }

        // validasi indikator milik OPD login
        $cekOpd = $this->db->table('renstra_target rt')
            ->select('rs.opd_id')
            ->join('renstra_indikator_sasaran ris', 'ris.id = rt.renstra_indikator_id', 'left')
            ->join('renstra_sasaran rs', 'rs.id = ris.renstra_sasaran_id', 'left')
            ->where('rt.renstra_indikator_id', $indikatorId)
            ->where('rt.tahun', $tahun)
            ->get()->getRowArray();

        if ((int) ($cekOpd['opd_id'] ?? 0) !== $opdId) {
            return redirect()->to(base_url('adminopd/lakip') . $qsBack)
                ->with('error', 'Akses ditolak: indikator bukan milik OPD anda.');
        }

        // cegah dobel LAKIP
        $exist = $this->lakipModel->getLakipByRenstraTarget((int) $target['id']);
        if ($exist) {
            return redirect()->to(base_url('adminopd/lakip/edit/' . $indikatorId) . $qsBack)
                ->with('info', 'LAKIP sudah ada. Silakan edit.');
        }

        return view('adminOpd/lakip/tambah_lakip', [
            'title' => 'Tambah LAKIP',
            'role' => $role,
            'indikator' => [
                'id' => $indikatorId,
                'indikator_sasaran' => $target['indikator_sasaran'] ?? '',
                'satuan' => $target['satuan'] ?? '',
                'jenis_indikator' => $target['jenis_indikator'] ?? 'indikator positif',
                'sasaran' => $target['sasaran'] ?? '',
            ],
            'target' => $target, // rt.*
            'opdInfo' => $this->opdModel->find($opdId),
            'tahun' => (string) $tahun,
            'qsBase' => $qsBack,
            'validation' => \Config\Services::validation(),
        ]);
    }

    /* =========================================================
     * SIMPAN
     * =======================================================*/
    /**
     * Bahan panel Pengesahan. Dipisah supaya index() tidak makin panjang, dan
     * supaya layar cetak/lainnya bisa memakainya kelak tanpa menyalin.
     *
     * @return array<string,mixed>
     */
    private function dataPengesahan(int $tahun, array $scope): array
    {
        if (! $this->pengesahanModel->siap()) {
            return ['pengesahanSiap' => false];
        }

        $mode  = $scope['mode'] ?? 'opd';
        $opdId = $mode === 'kabupaten' ? null : ($scope['opdScope'] ?? session()->get('opd_id'));

        $keadaan  = $this->pengesahanModel->keadaan($tahun, $mode, $opdId !== null ? (int) $opdId : null);
        $menunggu = $keadaan ? $this->pengesahanModel->permintaanMenunggu((int) $keadaan['id']) : null;

        return [
            'pengesahanSiap'     => true,
            // Dikirim tegas agar panel tidak pernah jatuh ke bawaan date('Y')
            // dan mengesahkan tahun yang salah — kekeliruan yang sempat
            // terjadi pada layar kabupaten.
            'tahun'              => $tahun,
            'pengesahan'         => $keadaan,
            'permintaanMenunggu' => $menunggu,
            'riwayatPermintaan'  => $keadaan ? $this->pengesahanModel->riwayat((int) $keadaan['id']) : [],
            // Menyahkan hanya boleh oleh yang berwenang DAN pada lingkupnya sendiri.
            'bolehSahkan'        => user_can('lakip_opd.finalisasi') && ! empty($scope['canWrite']),
        ];
    }

    /* =========================================================
     * PENGESAHAN LAKIP (kunci tahun) + PERMINTAAN PERBAIKAN
     *
     * Sengaja TANPA versi/snapshot: yang disimpan keadaan dan riwayat izin,
     * bukan salinan angka. Lihat LakipPengesahanModel untuk alasannya.
     * =======================================================*/

    /** Lingkup pengesahan milik sesi ini. @return array{0:int,1:string,2:?int} */
    private function lingkupPengesahan(): array
    {
        $role  = session()->get('role');
        $opdId = session()->get('opd_id');
        $mode  = $role === 'admin_kab' ? 'kabupaten' : 'opd';
        $tahun = (int) ($this->request->getPost('tahun') ?: $this->request->getGet('tahun')
                        ?: (date('Y') - 1));

        return [$tahun, $mode, $mode === 'kabupaten' ? null : (int) $opdId];
    }

    public function pengesahanSahkan()
    {
        if (! user_can('lakip_opd.finalisasi')) {
            return redirect()->back()->with('error', 'Anda tidak berwenang mengesahkan LAKIP.');
        }

        [$tahun, $mode, $opdId] = $this->lingkupPengesahan();

        if ($mode === 'opd' && ! $opdId) {
            return redirect()->to('/login')->with('error', 'Session tidak valid');
        }

        try {
            $hasil = $this->pengesahanModel->sahkan($tahun, $mode, $opdId, session()->get('user_id'), [
                'nomor'   => $this->request->getPost('nomor') ?: null,
                'catatan' => $this->request->getPost('catatan') ?: null,
            ]);
        } catch (\Throwable $e) {
            return redirect()->to(base_url('adminopd/lakip?tahun=' . $tahun))
                ->with('error', $e->getMessage());
        }

        return redirect()->to(base_url('adminopd/lakip?tahun=' . $tahun))->with(
            'success',
            ($hasil['sahkan_ulang'] ? 'LAKIP ' . $tahun . ' disahkan ulang' : 'LAKIP ' . $tahun . ' disahkan')
            . ' dan dikunci — ' . $hasil['jumlah_realisasi'] . ' realisasi tercakup. '
            . 'Bila kemudian ada yang keliru, ajukan Permintaan Perbaikan.'
        );
    }

    public function pengesahanAjukan()
    {
        if (! user_can('lakip_opd.finalisasi')) {
            return redirect()->back()->with('error', 'Anda tidak berwenang mengajukan perbaikan LAKIP.');
        }

        [$tahun, $mode, $opdId] = $this->lingkupPengesahan();

        try {
            $this->pengesahanModel->ajukanPembukaan(
                $tahun, $mode, $opdId,
                (string) $this->request->getPost('alasan'),
                session()->get('user_id')
            );
        } catch (\Throwable $e) {
            return redirect()->to(base_url('adminopd/lakip?tahun=' . $tahun))
                ->with('error', $e->getMessage());
        }

        return redirect()->to(base_url('adminopd/lakip?tahun=' . $tahun))
            ->with('success', 'Permintaan perbaikan dikirim ke Admin Kabupaten. '
                . 'LAKIP ' . $tahun . ' tetap terkunci sampai permintaannya disetujui.');
    }

    public function pengesahanTarik($permintaanId)
    {
        [$tahun, $mode, $opdId] = $this->lingkupPengesahan();

        // Hanya pemilik lingkupnya yang boleh menarik — id permintaan dari URL
        // tidak dipercaya begitu saja.
        $keadaan = $this->pengesahanModel->keadaan($tahun, $mode, $opdId);
        $menunggu = $keadaan ? $this->pengesahanModel->permintaanMenunggu((int) $keadaan['id']) : null;

        if ($menunggu === null || (int) $menunggu['id'] !== (int) $permintaanId) {
            return redirect()->to(base_url('adminopd/lakip?tahun=' . $tahun))
                ->with('error', 'Permintaan itu bukan milik lingkup Anda atau sudah diputuskan.');
        }

        try {
            $this->pengesahanModel->tarik((int) $permintaanId);
        } catch (\Throwable $e) {
            return redirect()->to(base_url('adminopd/lakip?tahun=' . $tahun))->with('error', $e->getMessage());
        }

        return redirect()->to(base_url('adminopd/lakip?tahun=' . $tahun))
            ->with('success', 'Permintaan perbaikan ditarik kembali.');
    }

    /**
     * Penjaga tunggal: tolak setiap penulisan pada tahun yang SUDAH DISAHKAN.
     *
     * Ditegakkan di controller, bukan hanya disembunyikan di layar — tombol
     * yang hilang tidak menghentikan URL yang diketik langsung. Semua jalur
     * tulis LAKIP (save, update, status, delete) lewat sini.
     *
     * Mengembalikan null bila boleh menulis, atau tanggapan penolakan.
     */
    private function tolakBilaDisahkan(int $tahun, string $mode, ?int $opdId, ?string $kembaliKe = null)
    {
        if (! $this->pengesahanModel->siap()) {
            return null; // basis data belum dimigrasi: perilaku lama
        }

        if (! $this->pengesahanModel->terkunci($tahun, $mode, $opdId)) {
            return null;
        }

        $pesan = 'LAKIP tahun ' . $tahun . ' sudah disahkan dan terkunci. '
            . 'Bila ada yang keliru, ajukan Permintaan Perbaikan kepada Admin Kabupaten '
            . 'lewat panel Pengesahan di halaman LAKIP.';

        if ($this->request->isAJAX()) {
            return $this->response->setStatusCode(423)
                ->setJSON(['success' => false, 'status' => 'error', 'message' => $pesan]);
        }

        return redirect()->to($kembaliKe ?? base_url('adminopd/lakip?tahun=' . $tahun))
            ->with('error', $pesan);
    }

    public function save()
    {
        $session = session();
        $role = $session->get('role');
        $opdId = $session->get('opd_id');

        if (in_array($role, ['admin_opd', 'admin_kecamatan'], true) && !$opdId) {
            return redirect()->to('/login')->with('error', 'Session tidak valid');
        }
        $rx = $this->xssRule();

        // ============================
        // VALIDASI (ANTI XSS/SCRIPT)
        // ============================
        $rules = [
            'status' => 'permit_empty|string|max_length[50]|' . $rx,
            'target_lalu' => 'permit_empty|string|max_length[255]|' . $rx,
            'capaian_lalu' => 'permit_empty|string|max_length[255]|' . $rx,
            'capaian_tahun_ini' => 'permit_empty|string|max_length[255]|' . $rx,
            'target_hitung' => 'permit_empty|numeric',
            'capaian_hitung' => 'permit_empty|numeric',
        ];

        // Tahun yang SUDAH DISAHKAN tidak boleh ditambahi baris baru — sama
        // seperti tidak boleh disunting. Diperiksa sedini mungkin, sebelum
        // pekerjaan penyusunan data dimulai.
        $tahunTulis = (int) ($this->request->getPost('tahun') ?: (date('Y') - 1));
        $modeTulis  = $role === 'admin_kab' ? 'kabupaten' : 'opd';

        if ($tolak = $this->tolakBilaDisahkan(
            $tahunTulis, $modeTulis, $modeTulis === 'kabupaten' ? null : (int) $opdId
        )) {
            return $tolak;
        }

        // Sumber IKU memakai kunci tersendiri; target Renstra/RPJMD tidak ada.
        $sumberForm = trim((string) $this->request->getPost('sumber_lakip'));

        if ($sumberForm === 'iku') {
            $rules['source_entity_id']  = 'required|integer';
            $rules['source_version_id'] = 'required|integer';
        } elseif ($role === 'admin_kab') {
            $rules['rpjmd_target_id'] = 'required|integer';
        } else {
            $rules['renstra_target_id'] = 'required|integer';
        }

        $messages = [
            'status' => ['regex_match' => 'Status mengandung script / input berbahaya.'],
            'target_lalu' => ['regex_match' => 'Target lalu mengandung script / input berbahaya.'],
            'capaian_lalu' => ['regex_match' => 'Capaian lalu mengandung script / input berbahaya.'],
            'capaian_tahun_ini' => ['regex_match' => 'Capaian tahun ini mengandung script / input berbahaya.'],
            'target_hitung' => ['numeric' => 'Target hitung harus berupa angka.'],
            'capaian_hitung' => ['numeric' => 'Capaian hitung harus berupa angka.'],
        ];

        if (!$this->validate($rules, $messages)) {
            return redirect()->back()->withInput()
                ->with('error', implode(' ', $this->validator->getErrors()));
        }
        $targetPrev = $this->request->getPost('target_lalu');
        $capaianPrev = $this->request->getPost('capaian_lalu');
        $capaianNow = $this->request->getPost('capaian_tahun_ini');
        $status = $this->request->getPost('status') ?: 'draft';

        if ($sumberForm === 'iku') {
            $opdId     = (int) session()->get('opd_id');
            $indikator = (int) $this->request->getPost('source_entity_id');
            $revisiId  = (int) $this->request->getPost('source_version_id');
            $tahunForm = (int) ($this->request->getPost('tahun') ?: (date('Y') - 1));

            // Diperiksa ulang di server: id indikator dari form tidak pernah
            // dipercaya sebagai bukti kepemilikan.
            $cek = $this->lakipModel->getIkuTargetDetail($revisiId, $indikator, $tahunForm);

            if ($cek === null || (int) ($cek['opd_id'] ?? 0) !== $opdId) {
                return redirect()->back()->withInput()
                    ->with('error', 'Indikator IKU tidak sah untuk OPD Anda.');
            }

            $data = [
                'renstra_target_id' => null,
                'rpjmd_target_id'   => null,
                'tahun'             => $tahunForm,
                'opd_id'            => $opdId,
                'mode'              => 'opd',

                // Jejak "dinilai terhadap apa" — dibekukan saat baris lahir,
                // bukan dibaca ulang tiap kali halaman dibuka.
                'source_type'       => 'iku',
                'source_version_id' => $revisiId,
                'source_entity_id'  => $indikator,

                'target_lalu'       => $targetPrev ?? '',
                'capaian_lalu'      => $capaianPrev ?? '',
                'capaian_tahun_ini' => $capaianNow ?? '',
                // Target ikut dibekukan: tanpa ini, persentase capaian dihitung
                // ulang dari dokumen yang bisa saja sudah berubah.
                'target_hitung'     => $this->request->getPost('target_hitung') !== ''
                    ? $this->request->getPost('target_hitung')
                    : ($cek['target'] ?? null),
                'capaian_hitung'    => $this->request->getPost('capaian_hitung') !== ''
                    ? $this->request->getPost('capaian_hitung') : null,
                'status'            => $status,
            ];
        } elseif ($role === 'admin_kab') {
            $targetId = $this->request->getPost('rpjmd_target_id');
            if (empty($targetId)) {
                return redirect()->back()->with('error', 'Target RPJMD tidak valid.')->withInput();
            }

            // Lingkup DIBEKUKAN saat baris lahir — dulu hanya jangkar id-nya
            // yang disimpan, dan begitu jangkar itu putus (target dihapus di
            // dokumen sumber) barisnya tidak bisa dikenali milik siapa/tahun
            // berapa lagi. Itulah asal 123 baris realisasi yatim.
            $rpjmdTarget = $this->db->table('rpjmd_target')
                ->select('tahun')->where('id', (int) $targetId)
                ->get()->getRowArray();

            $data = [
                'renstra_target_id' => null,
                'rpjmd_target_id' => (int) $targetId,
                'tahun' => isset($rpjmdTarget['tahun']) ? (int) $rpjmdTarget['tahun'] : null,
                'opd_id' => 0,
                'mode' => 'kabupaten',
                'source_type' => 'rpjmd',
                'source_entity_id' => (int) $targetId,
                'target_lalu' => $targetPrev ?? '',
                'capaian_lalu' => $capaianPrev ?? '',
                'capaian_tahun_ini' => $capaianNow ?? '',
                'target_hitung' => $this->request->getPost('target_hitung') !== '' ? $this->request->getPost('target_hitung') : null,
                'capaian_hitung' => $this->request->getPost('capaian_hitung') !== '' ? $this->request->getPost('capaian_hitung') : null,
                'status' => $status,
            ];
        } else {
            $targetId = $this->request->getPost('renstra_target_id');
            if (empty($targetId)) {
                return redirect()->back()->with('error', 'Target RENSTRA tidak valid.')->withInput();
            }

            // Lingkup dibekukan saat lahir — lihat catatan pada cabang RPJMD.
            // opd_id diambil dari DOKUMEN, bukan sesi: peran lintas OPD (admin)
            // menyimpan untuk OPD pemilik target, bukan untuk dirinya.
            $ikatan = $this->db->table('renstra_target rt')
                ->select('rt.tahun, rs.opd_id')
                ->join('renstra_indikator_sasaran ris', 'ris.id = rt.renstra_indikator_id')
                ->join('renstra_sasaran rs', 'rs.id = ris.renstra_sasaran_id')
                ->where('rt.id', (int) $targetId)
                ->get()->getRowArray();

            $data = [
                'renstra_target_id' => (int) $targetId,
                'rpjmd_target_id' => null,
                'tahun' => isset($ikatan['tahun']) ? (int) $ikatan['tahun'] : null,
                'opd_id' => isset($ikatan['opd_id']) ? (int) $ikatan['opd_id'] : null,
                'mode' => 'opd',
                'source_type' => 'renstra',
                'source_entity_id' => (int) $targetId,
                'target_lalu' => $targetPrev ?? '',
                'capaian_lalu' => $capaianPrev ?? '',
                'capaian_tahun_ini' => $capaianNow ?? '',
                'target_hitung' => $this->request->getPost('target_hitung') !== '' ? $this->request->getPost('target_hitung') : null,
                'capaian_hitung' => $this->request->getPost('capaian_hitung') !== '' ? $this->request->getPost('capaian_hitung') : null,
                'status' => $status,
            ];
        }

        $this->lakipModel->insert($data);

        return redirect()->to(base_url('adminopd/lakip'))
            ->with('success', 'Data LAKIP berhasil disimpan.');
    }

    /* =========================================================
     * FORM EDIT (FIX: wajib kirim tahun ke model)
     * =======================================================*/
    /**
     * Form sunting realisasi untuk baris bersumber IKU.
     *
     * Indikator dan targetnya dibaca dari ARSIP revisi yang dipakai baris itu,
     * bukan dari IKU berjalan — supaya yang tampil di form sama persis dengan
     * yang tampil di tabel, dan sama dengan yang dulu dipakai menilai.
     */
    private function editDariIku(int $indikatorId, int $revisiId, int $tahun, int $opdId, string $role)
    {
        $qsBack  = $this->buildQs((string) $tahun, $this->request->getGet('status'));
        $kembali = base_url('adminopd/lakip') . $qsBack;

        $lakip = $this->lakipModel->getLakipByIku($indikatorId, $tahun, $opdId);

        if ($lakip === null) {
            return redirect()->to(base_url('adminopd/lakip/tambah/' . $indikatorId) . $qsBack)
                ->with('info', 'Realisasi belum pernah diisi. Silakan tambah dulu.');
        }

        // Versi yang dipakai baris itu, bukan yang kebetulan dipilih di layar:
        // menyuntingnya lewat versi lain berarti menilai ulang dengan target
        // yang berbeda dari saat capaiannya diisi.
        $revisiBaris = (int) ($lakip['source_version_id'] ?? 0) ?: $revisiId;

        $target = $this->lakipModel->getIkuTargetDetail($revisiBaris, $indikatorId, $tahun);

        if ($target === null || (int) ($target['opd_id'] ?? 0) !== $opdId) {
            return redirect()->to($kembali)
                ->with('error', 'Indikator IKU tidak sah untuk OPD Anda.');
        }

        return view('adminOpd/lakip/edit_lakip', [
            'title'      => 'Edit LAKIP',
            'role'       => $role,
            'indikator'  => [
                'id'                => $indikatorId,
                'indikator_sasaran' => $target['indikator_sasaran'] ?? '',
                'satuan'            => $target['satuan'] ?? '',
                'jenis_indikator'   => $target['jenis_indikator'] ?? 'positif',
                'sasaran'           => $target['sasaran'] ?? '',
            ],
            'lakip'      => $lakip,
            'target'     => $target,
            'tahun'      => (string) $tahun,
            'validation' => \Config\Services::validation(),
        ]);
    }

    public function edit($indikatorId)
    {
        $session = session();
        $role = $session->get('role');
        $opdId = $session->get('opd_id');

        if (in_array($role, ['admin_opd', 'admin_kecamatan'], true) && !$opdId) {
            return redirect()->to('/login')->with('error', 'Session tidak valid');
        }

        // Bawaan = TAHUN LALU, sama dengan index()/cetak(): LAKIP menilai
        // tahun yang sudah selesai. Sebelumnya di sini bawaannya tahun
        // BERJALAN, sehingga membuka form dari layar 2025 tanpa parameter
        // diam-diam berpindah ke 2026 dan menolak dengan alasan
        // "target belum diisi".
        $tahun = $this->request->getGet('tahun') ?: (date('Y') - 1);

        if (in_array($role, ['admin_opd', 'admin_kecamatan'], true)) {
            [$sumberAktif, $revisiIku] = $this->sumberDariQuery('opd', (int) $opdId, (int) $tahun);

            if ($sumberAktif === 'iku') {
                return $this->editDariIku((int) $indikatorId, $revisiIku, (int) $tahun, (int) $opdId, $role);
            }
        }

        // indikator
        $tableIndikator = ($role === 'admin_kab')
            ? 'rpjmd_indikator_sasaran'
            : 'renstra_indikator_sasaran';

        $indikator = $this->db->table($tableIndikator)
            ->where('id', $indikatorId)
            ->get()
            ->getRowArray();

        if (!$indikator) {
            return redirect()->back()->with('error', 'Indikator tidak ditemukan.');
        }

        // Kolom `satuan` pada indikator menyimpan id -> resolve ke nama satuan.
        $indikator['satuan'] = $this->lakipModel->resolveSatuanName($indikator['satuan'] ?? null);

        // target tahun berjalan
        $tableTarget = ($role === 'admin_kab') ? 'rpjmd_target' : 'renstra_target';
        $byColumn = ($role === 'admin_kab') ? 'indikator_sasaran_id' : 'renstra_indikator_id';

        $target = $this->db->table($tableTarget)
            ->where($byColumn, $indikatorId)
            ->where('tahun', $tahun)
            ->orderBy('tahun', 'ASC')
            ->get()
            ->getRowArray();

        // ✅ FIX: kirim $tahun ke model
        $lakip = $this->lakipModel->getLakipDetail((int) $indikatorId, $role, (string) $tahun);

        return view('adminOpd/lakip/edit_lakip', [
            'title' => 'Edit LAKIP',
            'role' => $role,
            'indikator' => $indikator,
            'lakip' => $lakip,
            'target' => $target,
            'tahun' => (string) $tahun,
            'validation' => \Config\Services::validation(),
        ]);
    }
    /* =========================================================
     * UPDATE
     * =======================================================*/
    /**
     * Bolehkah pengguna ini menyunting satu baris LAKIP.
     *
     * Tiga sumber, tiga jalur kepemilikan yang berbeda — dan itulah sebabnya
     * pemeriksaannya dikumpulkan di satu tempat, bukan disebar di tiap aksi:
     *
     *   iku      -> `lakip.opd_id` langsung
     *   renstra  -> ditelusuri lewat target > indikator > sasaran
     *   rpjmd    -> milik Kabupaten, bukan OPD
     */
    private function bolehSuntingLakip(array $baris, string $role, ?int $opdId): bool
    {
        if ($role === 'admin_kab' || $role === 'admin') {
            return true;
        }

        if ($opdId === null || $opdId <= 0) {
            return false;
        }

        if (($baris['source_type'] ?? null) === 'iku') {
            return (int) ($baris['opd_id'] ?? 0) === $opdId;
        }

        if (! empty($baris['renstra_target_id'])) {
            $pemilik = $this->db->table('renstra_target rt')
                ->select('rs.opd_id')
                ->join('renstra_indikator_sasaran ris', 'ris.id = rt.renstra_indikator_id', 'left')
                ->join('renstra_sasaran rs', 'rs.id = ris.renstra_sasaran_id', 'left')
                ->where('rt.id', (int) $baris['renstra_target_id'])
                ->get()->getRowArray();

            return (int) ($pemilik['opd_id'] ?? 0) === $opdId;
        }

        // Baris RPJMD atau baris yatim tanpa tautan apa pun: bukan milik OPD.
        return false;
    }

    public function update()
    {
        $session = session();
        $role = $session->get('role');
        $opdId = $session->get('opd_id');

        if (in_array($role, ['admin_opd', 'admin_kecamatan'], true) && !$opdId) {
            return redirect()->to('/login')->with('error', 'Session tidak valid');
        }

        $rx = $this->xssRule();

        // ============================
        // VALIDASI (ANTI XSS/SCRIPT)
        // ============================
        $rules = [
            'lakip_id' => 'required|integer',
            'status' => 'permit_empty|string|max_length[50]|' . $rx,
            'target_lalu' => 'permit_empty|string|max_length[255]|' . $rx,
            'capaian_lalu' => 'permit_empty|string|max_length[255]|' . $rx,
            'capaian_tahun_ini' => 'permit_empty|string|max_length[255]|' . $rx,
            'target_hitung' => 'permit_empty|numeric',
            'capaian_hitung' => 'permit_empty|numeric',
        ];

        $messages = [
            'status' => ['regex_match' => 'Status mengandung script / input berbahaya.'],
            'target_lalu' => ['regex_match' => 'Target lalu mengandung script / input berbahaya.'],
            'capaian_lalu' => ['regex_match' => 'Capaian lalu mengandung script / input berbahaya.'],
            'capaian_tahun_ini' => ['regex_match' => 'Capaian tahun ini mengandung script / input berbahaya.'],
        ];

        if (!$this->validate($rules, $messages)) {
            return redirect()->back()->withInput()
                ->with('error', implode(' ', $this->validator->getErrors()));
        }
        $data = $this->request->getPost();
        $lakipId = $data['lakip_id'] ?? null;

        if (!$lakipId) {
            session()->setFlashdata('error', 'ID LAKIP tidak ditemukan');
            return redirect()->back()->withInput();
        }

        // Kepemilikan diperiksa SEBELUM menulis.
        //
        // Sebelumnya update() hanya bermodal `lakip_id` dari form — id apa pun
        // yang dikarang akan tertulis, termasuk milik OPD lain. Tidak ada galat
        // yang muncul; capaian OPD lain sekadar berubah.
        $barisLakip = $this->lakipModel->find((int) $lakipId);

        if ($barisLakip === null) {
            return redirect()->back()->withInput()->with('error', 'Baris LAKIP tidak ditemukan.');
        }

        if (! $this->bolehSuntingLakip($barisLakip, $role, $opdId === null ? null : (int) $opdId)) {
            return redirect()->to(base_url('adminopd/lakip'))
                ->with('error', 'Akses ditolak: baris LAKIP itu bukan milik OPD Anda.');
        }

        if ($tolak = $this->tolakBilaDisahkan(
            (int) ($barisLakip['tahun'] ?? 0),
            (string) ($barisLakip['mode'] ?? 'opd'),
            isset($barisLakip['opd_id']) ? (int) $barisLakip['opd_id'] : null
        )) {
            return $tolak;
        }

        try {
            $updateData = [
                // kolom NOT NULL: pakai '' bukan null agar konsisten dgn save() & tak melanggar constraint
                'target_lalu' => $data['target_lalu'] ?? '',
                'capaian_lalu' => $data['capaian_lalu'] ?? '',
                'capaian_tahun_ini' => $data['capaian_tahun_ini'] ?? '',
                'target_hitung' => ($data['target_hitung'] ?? '') !== '' ? $data['target_hitung'] : null,
                'capaian_hitung' => ($data['capaian_hitung'] ?? '') !== '' ? $data['capaian_hitung'] : null,
            ];

            if (!empty($data['status'])) {
                $updateData['status'] = $data['status'];
            }

            $this->lakipModel->updateLakip((int) $lakipId, $updateData);

            session()->setFlashdata('success', 'Data LAKIP berhasil diperbarui');
        } catch (\Throwable $e) {
            session()->setFlashdata('error', 'Gagal mengupdate data LAKIP: ' . $e->getMessage());
        }

        return redirect()->to(base_url('adminopd/lakip'));
    }

    /* =========================================================
     * DELETE
     * =======================================================*/
    public function delete($id)
    {
        $session = session();
        $role = $session->get('role');
        $opdId = $session->get('opd_id');

        if (!$role) {
            return redirect()->to('/login')->with('error', 'Session tidak valid');
        }

        if (in_array($role, ['admin_opd', 'admin_kecamatan'], true) && !$opdId) {
            return redirect()->to('/login')->with('error', 'Session tidak valid');
        }

        $lakip = $this->lakipModel->find($id);

        if (!$lakip) {
            return redirect()->back()
                ->with('error', 'Data LAKIP tidak ditemukan');
        }

        // Kepemilikan diperiksa SEBELUM menghapus.
        //
        // Sebelumnya method ini hanya memastikan ADA sesi — id apa pun yang
        // diketik di URL akan terhapus, termasuk realisasi milik OPD lain.
        // Terbukti lewat uji: akun BPBD berhasil menghapus capaian Dinkes
        // hanya dengan membuka /adminopd/lakip/delete/<id>. Menghapus lebih
        // berat daripada menyunting, jadi penjaganya justru wajib di sini.
        if (! $this->bolehSuntingLakip($lakip, $role, $opdId === null ? null : (int) $opdId)) {
            return redirect()->to(base_url('adminopd/lakip'))
                ->with('error', 'Akses ditolak: baris LAKIP itu bukan milik OPD Anda.');
        }

        if ($tolak = $this->tolakBilaDisahkan(
            (int) ($lakip['tahun'] ?? 0),
            (string) ($lakip['mode'] ?? 'opd'),
            isset($lakip['opd_id']) ? (int) $lakip['opd_id'] : null
        )) {
            return $tolak;
        }

        if ($this->lakipModel->deleteLakip((int) $id)) {
            return redirect()->back()
                ->with('success', 'LAKIP berhasil dihapus');
        }

        return redirect()->back()
            ->with('error', 'Gagal menghapus LAKIP');
    }

    /* =========================================================
     * UBAH STATUS
     * =======================================================*/
    public function status($id, $to)
    {
        $session = session();
        $role = $session->get('role');
        $opdId = $session->get('opd_id');

        if (in_array($role, ['admin_opd', 'admin_kecamatan'], true) && !$opdId) {
            return redirect()->to('/login')->with('error', 'Session tidak valid');
        }

        $allowedStatus = ['draft', 'selesai'];
        if (!in_array($to, $allowedStatus)) {
            return redirect()->to(base_url('adminopd/lakip'))
                ->with('error', 'Status tidak valid.');
        }

        $lakip = $this->lakipModel->find($id);
        if (!$lakip) {
            return redirect()->to(base_url('adminopd/lakip'))
                ->with('error', 'Data LAKIP tidak ditemukan.');
        }

        // Kepemilikan diperiksa SEBELUM menulis — kembaran penjaga di update().
        // Tanpa ini, status baris milik OPD lain bisa diubah hanya dengan
        // mengetik URL /lakip/status/<id>/selesai (terbukti lewat uji).
        if (! $this->bolehSuntingLakip($lakip, $role, $opdId === null ? null : (int) $opdId)) {
            return redirect()->to(base_url('adminopd/lakip'))
                ->with('error', 'Akses ditolak: baris LAKIP itu bukan milik OPD Anda.');
        }

        if ($tolak = $this->tolakBilaDisahkan(
            (int) ($lakip['tahun'] ?? 0),
            (string) ($lakip['mode'] ?? 'opd'),
            isset($lakip['opd_id']) ? (int) $lakip['opd_id'] : null
        )) {
            return $tolak;
        }

        try {
            $this->lakipModel->updateLakip((int) $id, ['status' => $to]);
            return redirect()->to(base_url('adminopd/lakip'))
                ->with('success', 'Status LAKIP berhasil diubah menjadi: ' . ucfirst($to));
        } catch (\Throwable $e) {
            return redirect()->to(base_url('adminopd/lakip'))
                ->with('error', 'Gagal mengubah status: ' . $e->getMessage());
        }
    }
}
