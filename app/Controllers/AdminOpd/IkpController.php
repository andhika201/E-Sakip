<?php

namespace App\Controllers\AdminOpd;

use App\Controllers\BaseController;
use App\Models\Concerns\TransaksiAman;
use App\Models\Ikp\IkpBulananModel;
use App\Models\Ikp\IkpModel;
use App\Models\Ikp\IkpTargetTahunanModel;
use App\Models\OpdModel;
use App\Services\IkpRekapService;
use CodeIgniter\Exceptions\PageNotFoundException;

/**
 * =====================================================================
 * AKSARA+ — Indikator Kinerja Prioritas (IKP) untuk admin OPD
 * =====================================================================
 *
 * Layar: daftar IKP, form tambah/ubah, target satu IKP (5 th -> tahunan ->
 * 12 bulan), grid breakdown massal, grid realisasi bulanan, rekap triwulan,
 * dan cetak PDF rekap. Angka & rumus: IkpRekapService + ikp_helper (satu
 * sumber yang juga dipakai lampiran PK, Kabupaten, Bupati, dan API eKin).
 *
 * SCOPE OPD
 *   admin_opd / admin_kecamatan : OPD SELALU dari session('opd_id'); opd_id
 *                                 dari permintaan diabaikan total.
 *   admin (super admin)         : boleh ?opd_id= (GET atau POST), DIVALIDASI
 *                                 terhadap daftar OPD sah (tanpa
 *                                 OpdModel::EXCLUDED_OPD_IDS). Tanpa pilihan
 *                                 sah -> layar "pilih OPD".
 *   Kepemilikan baris IKP selalu dicek terhadap BARIS DB (ikp.opd_id), tidak
 *   pernah terhadap nilai kiriman.
 *
 * IZIN: _remap() + peta metode -> izin ikp_opd.*. ModulePermissionFilter
 * sudah memetakan `ikp` -> `ikp_opd`, tetapi penjagaan di controller tetap
 * wajib (aturan rumah §19.3): filter itu menebak aksi dari kata di URL.
 *
 * HAPUS = SOFT DELETE (ikp.dihapus_pada). MENGAPA: RHK SKP di eKin merujuk
 * ikp.id; menghapus fisik akan memutus rantai kaskade SKP pegawai.
 */
class IkpController extends BaseController
{
    use TransaksiAman;

    protected $helpers = ['cascading_label', 'ikp', 'capaian', 'dashboard_status'];

    /** @var \CodeIgniter\Database\BaseConnection */
    protected $db;

    private ?IkpRekapService $rekapSvc = null;

    /**
     * Alias OPD untuk daftar pegawai. MENGAPA: sebagian pegawai tercatat pada
     * OPD kembar (BKPSDM 8 <-> 210, Kec. Gadingrejo 32 <-> 213, DP3AP2KB
     * 211 <-> 13); akun, PK, dan pohon kinerja memakai id pertama. Tanpa alias
     * pemilih pegawai BKPSDM kosong melompong (riset kritik 0.10).
     */
    public const OPD_ALIAS = [8 => [8, 210], 32 => [32, 213], 211 => [211, 13]];

    /** Panjang maksimal isian teks (kolom TEXT dibatasi agar layar & cetak tetap waras). */
    private const MAKS_TEKS = 2000;

    /** Tampilan kategori: ikon, warna, dan penjelasan singkat untuk operator. */
    public const KATEGORI_META = [
        'program_unggulan' => [
            'ikon'  => 'fa-star', 'warna' => '#00743e', 'singkat' => 'Program Unggulan',
            'jelas' => 'Output prioritas yang mendukung salah satu dari 9 Program Unggulan Bupati (Buku Saku).',
        ],
        'program_prioritas' => [
            'ikon'  => 'fa-flag', 'warna' => '#1971c2', 'singkat' => 'Program Prioritas',
            'jelas' => 'Program prioritas perangkat daerah dalam RPJMD/Renstra yang dipantau capaiannya setiap bulan.',
        ],
        'penugasan_khusus' => [
            'ikon'  => 'fa-user-tie', 'warna' => '#e8590c', 'singkat' => 'Penugasan Khusus',
            'jelas' => 'Tugas khusus langsung dari Bupati di luar program rutin. Sebutkan dasar arahan atau suratnya.',
        ],
        'penugasan_tambahan' => [
            'ikon'  => 'fa-circle-plus', 'warna' => '#7048e8', 'singkat' => 'Penugasan Tambahan',
            'jelas' => 'Tugas tambahan yang diberikan di tengah tahun berjalan. Sebutkan dasar penugasannya.',
        ],
    ];

    /** Penjelasan metode dalam bahasa operator (kunci = IkpModel::METODE). */
    public const METODE_PENJELASAN = [
        'sum' => [
            'judul'  => 'Akumulasi (dijumlah)',
            'isi'    => 'Angka tiap bulan adalah TAMBAHAN pada bulan itu. Triwulan = jumlah bulannya, target tahunan = jumlah 12 bulan, target 5 tahun = jumlah 5 tahun.',
            'contoh' => 'ton sampah terkelola, jumlah sosialisasi, sekolah yang dibina',
        ],
        'trend_naik' => [
            'judul'  => 'Posisi, makin tinggi makin baik',
            'isi'    => 'Angka tiap bulan adalah POSISI terkini, bukan tambahan. Triwulan = posisi bulan terakhirnya, target tahunan = posisi Desember, target 5 tahun = posisi tahun terakhir. Angkanya tidak boleh turun.',
            'contoh' => 'jumlah nasabah aktif, persentase layanan daring',
        ],
        'trend_turun' => [
            'judul'  => 'Posisi, makin rendah makin baik',
            'isi'    => 'Seperti posisi, tetapi capaian baik bila angkanya turun. Posisi Desember = target tahunan. Angkanya tidak boleh naik.',
            'contoh' => 'jumlah kasus, sampah yang dibuang ke TPA per hari',
        ],
        'trend_flat' => [
            'judul'  => 'Dipertahankan',
            'isi'    => 'Angka yang sama dijaga setiap periode: setiap bulan dan setiap tahun bernilai sama dengan targetnya.',
            'contoh' => '1 Bank Sampah Induk tetap beroperasi',
        ],
    ];

    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }

    // =====================================================================
    // PENJAGA IZIN
    // =====================================================================

    /** @return array<string, string> metode -> izin */
    private function petaIzin(): array
    {
        return [
            'index'         => 'ikp_opd.view',
            'rekap'         => 'ikp_opd.view',
            'cetak'         => 'ikp_opd.view',
            'target'        => 'ikp_opd.view',
            'breakdown'     => 'ikp_opd.view',
            'realisasi'     => 'ikp_opd.view',
            'bukuSaku'      => 'ikp_opd.view',
            'pegawai'       => 'ikp_opd.view',
            'node'          => 'ikp_opd.view',
            'tambah'        => 'ikp_opd.create',
            'save'          => 'ikp_opd.create',
            'edit'          => 'ikp_opd.update',
            'update'        => 'ikp_opd.update',
            'targetSave'    => 'ikp_opd.update',
            'breakdownSave' => 'ikp_opd.update',
            'realisasiSave' => 'ikp_opd.update',
            'delete'        => 'ikp_opd.delete',
        ];
    }

    public function _remap(string $method, ...$params)
    {
        // MENGAPA saringan ini: begitu _remap ada, CI4 menyerahkan SETIAP nama
        // metode ke sini — termasuk yang privat. Tanpa saringan, pembantu
        // seperti simpanBulanan() bisa dipanggil lewat URL.
        if (str_starts_with($method, '_') || ! method_exists($this, $method)
            || ! (new \ReflectionMethod($this, $method))->isPublic()
            || ! array_key_exists($method, $this->petaIzin())) {
            throw PageNotFoundException::forPageNotFound();
        }

        $izin = $this->petaIzin()[$method];
        if (! user_can($izin)) {
            $pesan = str_ends_with($izin, '.view')
                ? 'Anda tidak memiliki akses ke Kinerja Prioritas (IKP).'
                : 'Anda hanya dapat melihat IKP, tidak mengubahnya.';

            return $this->mintaJson()
                ? $this->gagal($pesan, 403)
                : redirect()->back()->with('error', $pesan);
        }

        if (! (new IkpModel())->siap()) {
            $pesan = 'Tabel IKP belum tersedia. Minta administrator menjalankan db/update_2026-09-26_ikp_kinerja.sql.';

            return $this->mintaJson() ? $this->gagal($pesan, 503) : redirect()->to(base_url('adminopd/dashboard'))->with('error', $pesan);
        }

        return $this->$method(...$params);
    }

    // =====================================================================
    // HALAMAN
    // =====================================================================

    /** Daftar IKP + ringkasan + filter kategori/PU/cari/tahun. */
    public function index()
    {
        $scope = $this->scope();
        if ($scope['opd_id'] === null) {
            return $this->halamanPilihOpd($scope, 'adminopd/ikp');
        }
        $opdId  = $scope['opd_id'];
        $tahun  = $this->tahunDipilih();
        $filter = [
            'kategori' => $this->kategoriDiminta(),
            'pu'       => $this->puDiminta(),
            'q'        => mb_substr(ikp_rapikan_teks((string) $this->request->getGet('q')), 0, 100),
        ];

        $svc   = $this->svc();
        $semua = $svc->rekapOpd($opdId, $tahun);
        $baris = $semua;
        $adaFilter = $filter['kategori'] !== '' || $filter['pu'] !== '' || $filter['q'] !== '';
        if ($adaFilter) {
            $ids   = array_map('intval', array_column($svc->daftar($opdId, $filter), 'id'));
            $baris = array_values(array_filter($semua, static fn ($r) => in_array((int) $r['ikp']['id'], $ids, true)));
        }

        return view('ikp/index', $this->dataHalaman($scope, $tahun, [
            'title'     => 'Kinerja Prioritas (IKP)',
            'aktif'     => 'index',
            'baris'     => $baris,
            'ringkas'   => $svc->ringkasOpd($opdId, $tahun, $semua),
            'filter'    => $filter,
            'adaFilter' => $adaFilter,
            'daftarPu'  => $this->daftarPu(),
        ]));
    }

    public function tambah()
    {
        $scope = $this->scope();
        if ($scope['opd_id'] === null) {
            return $this->halamanPilihOpd($scope, 'adminopd/ikp/tambah');
        }
        $kategori = $this->kategoriDiminta();

        return view('ikp/form', $this->dataForm($scope, [
            'mode' => 'tambah',
            'ikp'  => ['kategori' => $kategori !== '' ? $kategori : 'program_unggulan'],
        ]));
    }

    public function edit($id = null)
    {
        $scope = $this->scope();
        $ikp   = $this->ikpMilik($scope, (int) $id);
        if ($ikp === null) {
            return redirect()->to($this->u('adminopd/ikp', [], $scope))->with('error', 'IKP tidak ditemukan atau bukan milik perangkat daerah Anda.');
        }

        return view('ikp/form', $this->dataForm($scope, ['mode' => 'edit', 'ikp' => $ikp]));
    }

    public function save()
    {
        $scope = $this->scope();
        if ($scope['opd_id'] === null) {
            return redirect()->to(base_url('adminopd/ikp'))->with('error', 'Pilih perangkat daerah terlebih dahulu.');
        }
        [$data, $galat] = $this->bacaForm($scope['opd_id']);
        if ($galat !== []) {
            return redirect()->back()->withInput()->with('error', implode(' ', $galat));
        }

        try {
            $p   = $this->svc()->periodeAktif();
            $ikp = new IkpModel();
            $maks = (int) ($this->db->table('ikp')->selectMax('urutan', 'm')->where('opd_id', $scope['opd_id'])->get()->getRow()->m ?? 0);
            $data += [
                'opd_id'        => $scope['opd_id'],
                'periode_awal'  => $p['awal'],
                'periode_akhir' => $p['akhir'],
                'urutan'        => $maks + 1,
                'created_by'    => $this->userId(),
                'updated_by'    => $this->userId(),
            ];
            $id = (int) $ikp->insert($data, true);
            if ($id <= 0) {
                throw new \RuntimeException('Penyimpanan IKP tidak mengembalikan id.');
            }
        } catch (\Throwable $e) {
            return redirect()->back()->withInput()->with('error', pesanGalatBerawalan($e, 'IKP gagal disimpan', 'opd.ikp.simpan'));
        }

        return redirect()->to($this->u('adminopd/ikp/target/' . $id, [], $scope))
            ->with('success', 'IKP tersimpan. Lanjutkan dengan mengisi target tahunan dan bulanan di bawah ini.');
    }

    public function update($id = null)
    {
        $scope = $this->scope();
        $lama  = $this->ikpMilik($scope, (int) $id);
        if ($lama === null) {
            return redirect()->to($this->u('adminopd/ikp', [], $scope))->with('error', 'IKP tidak ditemukan atau bukan milik perangkat daerah Anda.');
        }
        [$data, $galat] = $this->bacaForm($scope['opd_id']);
        if ($galat !== []) {
            return redirect()->back()->withInput()->with('error', implode(' ', $galat));
        }

        try {
            $data['updated_by'] = $this->userId();
            (new IkpModel())->update((int) $lama['id'], $data);
        } catch (\Throwable $e) {
            return redirect()->back()->withInput()->with('error', pesanGalatBerawalan($e, 'IKP gagal diperbarui', 'opd.ikp.ubah'));
        }

        $pesan = 'Perubahan IKP tersimpan.';
        if (($lama['metode'] ?? null) !== $data['metode']) {
            $pesan .= ' Metode perhitungan berubah: periksa kembali target bulanan & rekap triwulannya.';
        }

        return redirect()->to($this->u('adminopd/ikp', [], $scope))->with('success', $pesan);
    }

    /**
     * Hapus = SOFT DELETE. Target & realisasinya tetap tersimpan (tidak tampil),
     * supaya SKP eKin yang merujuk IKP ini tidak kehilangan sumbernya.
     */
    public function delete($id = null)
    {
        $scope = $this->scope();
        $ikp   = $this->ikpMilik($scope, (int) $id);
        if ($ikp === null) {
            return redirect()->to($this->u('adminopd/ikp', [], $scope))->with('error', 'IKP tidak ditemukan atau sudah dihapus.');
        }

        try {
            (new IkpModel())->update((int) $ikp['id'], ['dihapus_pada' => date('Y-m-d H:i:s'), 'updated_by' => $this->userId()]);
            log_activity('hapus', 'ikp', 'IKP #' . (int) $ikp['id'] . ' dihapus (soft delete): ' . mb_substr((string) $ikp['output_prioritas'], 0, 150));
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', pesanGalatBerawalan($e, 'IKP gagal dihapus', 'opd.ikp.hapus'));
        }

        return redirect()->to($this->u('adminopd/ikp', [], $scope))->with('success', 'IKP "' . mb_substr((string) $ikp['output_prioritas'], 0, 80) . '" dihapus dari daftar.');
    }

    /** Target satu IKP: 5 tahun -> tahunan -> 12 bulan tahun terpilih. */
    public function target($id = null)
    {
        $scope = $this->scope();
        $ikp   = $this->ikpMilik($scope, (int) $id);
        if ($ikp === null) {
            return redirect()->to($this->u('adminopd/ikp', [], $scope))->with('error', 'IKP tidak ditemukan atau bukan milik perangkat daerah Anda.');
        }
        $tahun = $this->tahunDipilih();
        $svc   = $this->svc();
        $rekap = $svc->rekapSatu($scope['opd_id'], (int) $ikp['id'], $tahun);

        return view('ikp/target', $this->dataHalaman($scope, $tahun, [
            'title'      => 'Target IKP',
            'aktif'      => 'index',
            'tanpaTahun' => true,   // tahun dipilih di langkah 2
            'ikp'     => $ikp,
            'rekap'   => $rekap,
            'tahunan' => $svc->targetTahunan([(int) $ikp['id']])[(int) $ikp['id']] ?? [],
            'bulat'   => ikp_satuan_bulat($ikp['satuan_label'] ?? ''),
        ]));
    }

    /** POST JSON: simpan target tahunan dan/atau bulanan satu IKP. */
    public function targetSave($id = null)
    {
        $scope = $this->scope();
        $ikp   = $this->ikpMilik($scope, (int) $id);
        if ($ikp === null) {
            return $this->gagal('IKP tidak ditemukan atau bukan milik perangkat daerah Anda.', 404);
        }
        $in = $this->jsonMasuk();

        return $this->simpanTarget($scope, $ikp, $in['tahunan'] ?? null, isset($in['tahun']) ? (int) $in['tahun'] : null, $in['bulanan'] ?? null);
    }

    /** Grid massal: tab Tahunan (5 th) & Bulanan (12 bulan tahun terpilih). */
    public function breakdown()
    {
        $scope = $this->scope();
        if ($scope['opd_id'] === null) {
            return $this->halamanPilihOpd($scope, 'adminopd/ikp/breakdown');
        }
        $tahun = $this->tahunDipilih();
        $tab   = $this->request->getGet('tab') === 'bulanan' ? 'bulanan' : 'tahunan';
        $svc   = $this->svc();
        $rekap = $svc->rekapOpd($scope['opd_id'], $tahun, ['kategori' => $this->kategoriDiminta()]);
        $ids   = array_map(static fn ($r) => (int) $r['ikp']['id'], $rekap);

        return view('ikp/breakdown', $this->dataHalaman($scope, $tahun, [
            'title'    => 'Breakdown Target IKP',
            'aktif'    => 'breakdown',
            'tab'      => $tab,
            'rekap'    => $rekap,
            'tahunan'  => $svc->targetTahunan($ids),
            'kategori' => $this->kategoriDiminta(),
        ]));
    }

    /** POST JSON per baris: {ikp_id, jenis: tahunan|bulanan, tahun, nilai:{kunci: teks}}. */
    public function breakdownSave()
    {
        $scope = $this->scope();
        $in    = $this->jsonMasuk();
        $ikp   = $this->ikpMilik($scope, (int) ($in['ikp_id'] ?? 0));
        if ($ikp === null) {
            return $this->gagal('IKP tidak ditemukan atau bukan milik perangkat daerah Anda.', 404);
        }
        $jenis = (string) ($in['jenis'] ?? '');
        $nilai = is_array($in['nilai'] ?? null) ? $in['nilai'] : null;
        if ($nilai === null || ! in_array($jenis, ['tahunan', 'bulanan'], true)) {
            return $this->gagal('Permintaan tidak lengkap: jenis (tahunan/bulanan) dan nilai wajib dikirim.', 422);
        }

        return $jenis === 'tahunan'
            ? $this->simpanTarget($scope, $ikp, $nilai, null, null)
            : $this->simpanTarget($scope, $ikp, null, (int) ($in['tahun'] ?? 0), $nilai);
    }

    /** Grid realisasi bulanan semua IKP + rekap triwulan & capaian. */
    public function realisasi()
    {
        $scope = $this->scope();
        if ($scope['opd_id'] === null) {
            return $this->halamanPilihOpd($scope, 'adminopd/ikp/realisasi');
        }
        $tahun = $this->tahunDipilih();

        return view('ikp/realisasi', $this->dataHalaman($scope, $tahun, [
            'title'        => 'Realisasi Bulanan IKP',
            'aktif'        => 'realisasi',
            'rekap'        => $this->svc()->rekapOpd($scope['opd_id'], $tahun, ['kategori' => $this->kategoriDiminta()]),
            'bulanTerbuka' => $this->bulanTerbuka($tahun),
            'kategori'     => $this->kategoriDiminta(),
        ]));
    }

    /**
     * POST JSON: {ikp_id, tahun, bulan: {m: {realisasi?, keterangan?, bukti_url?}}}.
     * Hanya bulan yang dikirim yang disentuh. Realisasi 0 = nilai sah.
     */
    public function realisasiSave()
    {
        $scope = $this->scope();
        $in    = $this->jsonMasuk();
        $ikp   = $this->ikpMilik($scope, (int) ($in['ikp_id'] ?? 0));
        if ($ikp === null) {
            return $this->gagal('IKP tidak ditemukan atau bukan milik perangkat daerah Anda.', 404);
        }
        $tahun = (int) ($in['tahun'] ?? 0);
        if (! in_array($tahun, $this->svc()->tahunPeriode(), true)) {
            return $this->gagal('Tahun di luar periode RPJMD.', 422);
        }
        $bulanMasuk = is_array($in['bulan'] ?? null) ? $in['bulan'] : [];
        if ($bulanMasuk === []) {
            return $this->gagal('Tidak ada perubahan yang dikirim.', 422);
        }

        // =============================================================
        // MENGAPA BATAS BULAN DIHITUNG DI SINI DAN DARI WIB
        // AKSARA berjalan di UTC (appTimezone), sehingga date('n') pada
        // 1 Oktober pukul 00.00–06.59 WIB masih menyebut September. Batasnya
        // dihitung eksplisit dalam Asia/Jakarta, dan bulan yang diisi selalu
        // diambil dari kiriman pengguna — bukan ditebak dari jam server.
        // =============================================================
        $terbuka = $this->bulanTerbuka($tahun);
        $siap    = [];
        $galat   = [];
        foreach ($bulanMasuk as $m => $isi) {
            $m = (int) $m;
            if ($m < 1 || $m > 12 || ! is_array($isi)) {
                $galat[] = 'Bulan tidak dikenal.';

                continue;
            }
            if ($m > $terbuka) {
                $galat[] = 'Realisasi ' . ikp_nama_bulan($m) . ' ' . $tahun . ' belum dapat diisi karena bulannya belum berjalan.';

                continue;
            }
            $baris = [];
            if (array_key_exists('realisasi', $isi)) {
                $teks = trim((string) $isi['realisasi']);
                if (! ikp_angka_sah($teks)) {
                    $galat[] = 'Realisasi ' . ikp_nama_bulan($m) . ' harus berupa angka (contoh: 1.250 atau 12,5).';

                    continue;
                }
                $v = ikp_angka_baca($teks, false);   // nol = nilai sah untuk realisasi
                $baris['realisasi']      = $v === null ? null : round($v, 4);
                $baris['realisasi_teks'] = null;
            }
            if (array_key_exists('keterangan', $isi)) {
                $ket = trim((string) $isi['keterangan']);
                if (mb_strlen($ket) > self::MAKS_TEKS || $this->berTag($ket)) {
                    $galat[] = 'Keterangan ' . ikp_nama_bulan($m) . ' terlalu panjang atau memuat tag HTML.';

                    continue;
                }
                $baris['keterangan'] = $ket === '' ? null : $ket;
            }
            if (array_key_exists('bukti_url', $isi)) {
                $url = trim((string) $isi['bukti_url']);
                if ($url !== '' && (mb_strlen($url) > 500 || ! preg_match('~^https?://[^\s<>"]+$~i', $url))) {
                    $galat[] = 'Tautan bukti ' . ikp_nama_bulan($m) . ' harus diawali http:// atau https:// (maks. 500 karakter).';

                    continue;
                }
                $baris['bukti_url'] = $url === '' ? null : $url;
            }
            if ($baris !== []) {
                $siap[$m] = $baris;
            }
        }
        if ($galat !== []) {
            return $this->gagal(implode(' ', array_unique($galat)), 422);
        }
        if ($siap === []) {
            return $this->gagal('Tidak ada perubahan yang dikirim.', 422);
        }

        try {
            $this->dalamTransaksi(function () use ($ikp, $tahun, $siap) {
                $model = new IkpBulananModel();
                foreach ($siap as $m => $baris) {
                    if (array_key_exists('realisasi', $baris)) {
                        $baris['realisasi_oleh'] = $this->userId();
                        $baris['realisasi_pada'] = date('Y-m-d H:i:s');   // UTC, seperti seluruh AKSARA
                    }
                    $this->upsertBulan($model, (int) $ikp['id'], $tahun, $m, $baris);
                }
            }, 'simpan realisasi IKP');
        } catch (\Throwable $e) {
            return $this->gagal(pesanGalatBerawalan($e, 'Realisasi gagal disimpan', 'opd.ikp.realisasi'), 500);
        }

        $rekap = $this->svc()->rekapSatu($scope['opd_id'], (int) $ikp['id'], $tahun);

        return $this->sukses([
            'pesan' => 'Tersimpan ' . $this->jamWib(),
            'baris' => $this->barisJson($rekap),
        ]);
    }

    /** Rekap triwulan (target, realisasi, capaian, status) + tahun berjalan. */
    public function rekap()
    {
        $scope = $this->scope();
        if ($scope['opd_id'] === null) {
            return $this->halamanPilihOpd($scope, 'adminopd/ikp/rekap');
        }
        $tahun = $this->tahunDipilih();
        $svc   = $this->svc();
        $semua = $svc->rekapOpd($scope['opd_id'], $tahun);
        $kat   = $this->kategoriDiminta();
        $baris = $kat === '' ? $semua : array_values(array_filter($semua, static fn ($r) => $r['ikp']['kategori'] === $kat));

        return view('ikp/rekap', $this->dataHalaman($scope, $tahun, [
            'title'    => 'Rekap Triwulan IKP',
            'aktif'    => 'rekap',
            'baris'    => $baris,
            'ringkas'  => $svc->ringkasOpd($scope['opd_id'], $tahun, $semua),
            'kategori' => $kat,
        ]));
    }

    /** PDF rekap triwulan IKP satu tahun (A4 mendatar). */
    public function cetak()
    {
        $scope = $this->scope();
        if ($scope['opd_id'] === null) {
            return redirect()->to(base_url('adminopd/ikp/rekap'))->with('error', 'Pilih perangkat daerah terlebih dahulu.');
        }
        $tahun = $this->tahunDipilih();
        $svc   = $this->svc();
        $semua = $svc->rekapOpd($scope['opd_id'], $tahun);
        $kat   = $this->kategoriDiminta();
        $baris = $kat === '' ? $semua : array_values(array_filter($semua, static fn ($r) => $r['ikp']['kategori'] === $kat));

        helper('setting');
        $html = view('ikp/cetak', [
            'baris'    => $baris,
            'ringkas'  => $svc->ringkasOpd($scope['opd_id'], $tahun, $semua),
            'tahun'    => $tahun,
            'kategori' => $kat,
            'namaUnit' => (string) $scope['opd_nama'],
            'judul'    => 'Rekap Indikator Kinerja Prioritas (IKP)',
            'subjudul' => 'Tahun ' . $tahun . ($kat !== '' ? ' — ' . IkpModel::KATEGORI[$kat] : '') . ' · dicetak ' . $this->tanggalWib(),
            'periode'  => $svc->periodeAktif(),
        ]);

        try {
            $mpdf = new \App\Libraries\PdfMpdf([
                'mode'              => 'utf-8',
                'format'            => 'A4-L',
                'default_font_size' => 8,
                'margin_left'       => 10,
                'margin_right'      => 10,
                'margin_top'        => 10,
                'margin_bottom'     => 14,
                'tempDir'           => sys_get_temp_dir(),
            ]);
            $mpdf->SetTitle('Rekap IKP ' . $tahun . ' - ' . $scope['opd_nama']);
            $mpdf->SetHTMLFooter(pdf_footer_aksara());
            pdf_watermark_aksara($mpdf);
            $mpdf->WriteHTML($html);
        } catch (\Throwable $e) {
            return redirect()->to($this->u('adminopd/ikp/rekap', ['tahun' => $tahun], $scope))
                ->with('error', pesanGalatBerawalan($e, 'PDF gagal dibuat', 'opd.ikp.cetak'));
        }

        $this->response->setHeader('Content-Type', 'application/pdf');
        $mpdf->Output('Rekap-IKP-' . $tahun . '-OPD' . $scope['opd_id'] . '.pdf', 'I');
        exit;
    }

    // =====================================================================
    // JSON PENDUKUNG FORM
    // =====================================================================

    /** GET ?q= (≥ 3 huruf): autolengkap Buku Saku, maks. 15 baris. */
    public function bukuSaku()
    {
        $q = mb_substr(ikp_rapikan_teks((string) $this->request->getGet('q')), 0, 100);
        if (mb_strlen($q) < 3 || ! $this->db->tableExists('ikp_buku_saku')) {
            return $this->sukses(['items' => []]);
        }
        $rows = $this->db->table('ikp_buku_saku b')
            ->select('b.*, pu.nama AS pu_nama, pu.warna AS pu_warna, pu.ikon AS pu_ikon')
            ->join('ikp_program_unggulan pu', 'pu.id = b.program_unggulan_id', 'left')
            ->groupStart()
            ->like('b.output_prioritas', $q)
            ->orLike('b.indikator', $q)
            ->orLike('b.program_opd', $q)
            ->orLike('b.bidang_urusan', $q)
            ->groupEnd()
            ->orderBy('b.program_unggulan_id', 'ASC')->orderBy('b.id', 'ASC')
            ->limit(15)->get()->getResultArray();

        $satuanMaster = $this->petaSatuan();
        $items = [];
        foreach ($rows as $r) {
            $satuan = trim((string) $r['satuan']);
            $target = trim((string) $r['target_5_tahun']);
            // Beberapa baris Buku Saku tertukar kolom (satuan "3", target "Unit").
            if (ikp_angka_baca($satuan) !== null && $target !== '' && ikp_angka_baca($target) === null && ! ikp_angka_sah($target)) {
                [$satuan, $target] = [$target, $satuan];
            }
            $targetAngka = ikp_angka_sah($target) ? ikp_angka_baca($target) : null;
            $items[] = [
                'id'                     => (int) $r['id'],
                'output_prioritas'       => (string) $r['output_prioritas'],
                'indikator'              => (string) $r['indikator'],
                'outcome'                => (string) $r['outcome'],
                'program_opd'            => (string) $r['program_opd'],
                'bidang_urusan'          => (string) $r['bidang_urusan'],
                'program_unggulan_id'    => $r['program_unggulan_id'] === null ? null : (int) $r['program_unggulan_id'],
                'pu_nama'                => $r['pu_nama'],
                'pu_warna'               => $r['pu_warna'],
                'sasaran_pembangunan_id' => $r['sasaran_pembangunan_id'] === null ? null : (int) $r['sasaran_pembangunan_id'],
                'rpjmd_misi_id'          => $r['rpjmd_misi_id'] === null ? null : (int) $r['rpjmd_misi_id'],
                'satuan'                 => $satuan,
                'satuan_id'              => $satuanMaster[mb_strtolower($satuan)] ?? null,
                'target_5_tahun'         => $targetAngka === null ? '' : ikp_fmt($targetAngka, 4),
                'target_5_tahun_teks'    => $targetAngka === null ? $target : '',
            ];
        }

        return $this->sukses(['items' => $items]);
    }

    /** GET ?q=: Select2 pegawai OPD sendiri (+ alias OPD kembar). */
    public function pegawai()
    {
        $scope = $this->scope();
        if ($scope['opd_id'] === null) {
            return $this->response->setJSON(['results' => []]);
        }
        $q = mb_substr(ikp_rapikan_teks((string) $this->request->getGet('q')), 0, 60);
        $b = $this->db->table('pegawai p')
            ->select('p.id, p.nama_pegawai, j.nama_jabatan')
            ->join('jabatan j', 'j.id = p.jabatan_id', 'left')
            ->whereIn('p.opd_id', self::OPD_ALIAS[$scope['opd_id']] ?? [$scope['opd_id']]);
        if ($q !== '') {
            $b->groupStart()->like('p.nama_pegawai', $q)->orLike('j.nama_jabatan', $q)->orLike('p.nip_pegawai', $q)->groupEnd();
        }
        $rows = $b->orderBy('p.nama_pegawai', 'ASC')->limit(30)->get()->getResultArray();

        $hasil = [];
        foreach ($rows as $r) {
            $jab     = trim((string) ($r['nama_jabatan'] ?? ''));
            $hasil[] = [
                'id'      => (int) $r['id'],
                'text'    => trim((string) $r['nama_pegawai']) . ($jab !== '' ? ' — ' . $jab : ''),
                'jabatan' => $jab,
            ];
        }

        return $this->response->setJSON(['results' => $hasil]);
    }

    /** GET: simpul pohon kinerja OPD + indikatornya, untuk tautan IKP <-> pohon kinerja. */
    public function node()
    {
        $scope = $this->scope();
        if ($scope['opd_id'] === null) {
            return $this->gagal('Pilih perangkat daerah terlebih dahulu.', 400);
        }

        return $this->sukses(['nodes' => $this->simpulOpd($scope['opd_id'])]);
    }

    // =====================================================================
    // INTI SIMPAN TARGET (dipakai targetSave & breakdownSave)
    // =====================================================================

    /**
     * @param array<string|int, mixed>|null $tahunan [tahun => teks]
     * @param array<string|int, mixed>|null $bulanan [bulan => teks]
     */
    private function simpanTarget(array $scope, array $ikp, ?array $tahunan, ?int $tahun, ?array $bulanan)
    {
        $svc     = $this->svc();
        $periode = $svc->tahunPeriode();
        $galat   = [];
        $siapThn = [];
        $siapBln = [];

        if ($tahunan !== null) {
            foreach ($tahunan as $th => $teks) {
                $th = (int) $th;
                if (! in_array($th, $periode, true)) {
                    $galat[] = 'Tahun ' . $th . ' di luar periode RPJMD ' . $periode[0] . '–' . end($periode) . '.';

                    continue;
                }
                $teks = trim((string) $teks);
                if (! ikp_angka_sah($teks)) {
                    $galat[] = 'Target ' . $th . ' harus berupa angka (contoh: 400, 1.250, atau 12,5).';

                    continue;
                }
                $v            = ikp_angka_baca($teks);          // target: nol = belum ada target
                $siapThn[$th] = $v === null ? null : round($v, 4);
            }
        }
        if ($bulanan !== null) {
            if ($tahun === null || ! in_array($tahun, $periode, true)) {
                $galat[] = 'Tahun target bulanan tidak sah.';
            } else {
                foreach ($bulanan as $m => $teks) {
                    $m = (int) $m;
                    if ($m < 1 || $m > 12) {
                        $galat[] = 'Bulan tidak dikenal.';

                        continue;
                    }
                    $teks = trim((string) $teks);
                    if (! ikp_angka_sah($teks)) {
                        $galat[] = 'Target ' . ikp_nama_bulan($m) . ' harus berupa angka.';

                        continue;
                    }
                    $v           = ikp_angka_baca($teks);
                    $siapBln[$m] = $v === null ? null : round($v, 4);
                }
            }
        }
        if ($galat !== []) {
            return $this->gagal(implode(' ', array_unique($galat)), 422);
        }
        if ($siapThn === [] && $siapBln === []) {
            return $this->gagal('Tidak ada nilai yang dikirim.', 422);
        }

        try {
            $this->dalamTransaksi(function () use ($ikp, $siapThn, $tahun, $siapBln) {
                $id = (int) $ikp['id'];
                if ($siapThn !== []) {
                    $model = new IkpTargetTahunanModel();
                    foreach ($siapThn as $th => $v) {
                        $ada = $model->where(['ikp_id' => $id, 'tahun' => $th])->first();
                        if ($v === null) {
                            // Kosong = tidak ada target tahun itu. Bulanannya SENGAJA
                            // tidak ikut dihapus (tak ada FK/cascade): jebakan Prioritas.
                            // Target berupa uraian (target_teks, mis. hasil impor) tidak
                            // punya kotak angka; jangan ikut terhapus hanya karena kotaknya kosong.
                            if ($ada && trim((string) ($ada['target_teks'] ?? '')) !== '') {
                                $model->update((int) $ada['id'], ['target' => null]);
                            } elseif ($ada) {
                                $model->delete((int) $ada['id']);
                            }
                        } elseif ($ada) {
                            $model->update((int) $ada['id'], ['target' => $v, 'target_teks' => null]);
                        } else {
                            $model->insert(['ikp_id' => $id, 'tahun' => $th, 'target' => $v, 'target_teks' => null]);
                        }
                    }
                }
                if ($siapBln !== []) {
                    $model = new IkpBulananModel();
                    foreach ($siapBln as $m => $v) {
                        // Uraian (target_teks) hanya diganti bila angka baru diisi.
                        $kolom = $v === null ? ['target' => null] : ['target' => $v, 'target_teks' => null];
                        $this->upsertBulan($model, $id, (int) $tahun, (int) $m, $kolom);
                    }
                }
            }, 'simpan target IKP');
        } catch (\Throwable $e) {
            return $this->gagal(pesanGalatBerawalan($e, 'Target gagal disimpan', 'opd.ikp.target'), 500);
        }

        // Balasan: nilai tersimpan (terformat) + hasil cek server (sumber kebenaran).
        $id      = (int) $ikp['id'];
        $metode  = (string) ($ikp['metode'] ?? '');
        $thnDb   = $svc->targetTahunan([$id])[$id] ?? [];
        $anakThn = [];
        foreach ($periode as $th) {
            $anakThn[$th] = $thnDb[$th]['target'] ?? null;
        }
        $data = [
            'pesan'       => 'Tersimpan ' . $this->jamWib(),
            'tahunan'     => array_map(static fn ($v) => $v === null ? '' : ikp_fmt($v, 4), $anakThn),
            'cek_tahunan' => ikp_cek($metode, $ikp['target_5_tahun'] === null ? null : (float) $ikp['target_5_tahun'], $anakThn),
        ];
        if ($tahun !== null && $siapBln !== []) {
            $rekap           = $svc->rekapSatu($scope['opd_id'], $id, $tahun);
            $data['tahun']   = $tahun;
            $data['baris']   = $this->barisJson($rekap);
        }

        return $this->sukses($data);
    }

    /** Sisipkan / perbarui satu baris ikp_bulanan (kunci unik ikp_id+tahun+bulan). */
    private function upsertBulan(IkpBulananModel $model, int $ikpId, int $tahun, int $bulan, array $kolom): void
    {
        $ada = $model->where(['ikp_id' => $ikpId, 'tahun' => $tahun, 'bulan' => $bulan])->first();
        if ($ada) {
            $model->update((int) $ada['id'], $kolom);

            return;
        }
        // Jangan membuat baris kosong melompong (mis. mengosongkan target yang memang belum ada).
        $bermakna = array_filter(
            array_diff_key($kolom, ['realisasi_oleh' => 1, 'realisasi_pada' => 1]),
            static fn ($v) => $v !== null
        );
        if ($bermakna === []) {
            return;
        }
        $model->insert(['ikp_id' => $ikpId, 'tahun' => $tahun, 'bulan' => $bulan] + $kolom);
    }

    /**
     * Rekap satu IKP -> bentuk ringkas untuk balasan JSON grid (nilai sudah
     * terformat id-ID supaya layar tidak memformat ulang dengan aturan lain).
     */
    private function barisJson(?array $r): ?array
    {
        if ($r === null) {
            return null;
        }
        $metode = (string) ($r['ikp']['metode'] ?? '');
        $tw     = [];
        foreach ($r['triwulan'] as $q => $t) {
            $tw[$q] = [
                'target'       => ikp_fmt($t['target']),
                'realisasi'    => ikp_fmt($t['realisasi']),
                'capaian'      => $t['capaian'] === null ? null : capaianFormatPersen($t['capaian']),
                'status'       => $t['status'],
                'status_label' => $t['status_label'],
                'warna'        => $t['warna'],
                'keterangan'   => $t['keterangan'],
                'berjalan'     => $t['berjalan'],
                'sampai_label' => ($t['sampai_bulan'] ?? null) ? ikp_nama_bulan((int) $t['sampai_bulan'], true) : null,
            ];
        }
        $bulan = [];
        $tgt   = [];
        foreach ($r['bulan'] as $m => $b) {
            $tgt[$m]   = $b['target'];
            $bulan[$m] = [
                'target'     => $b['target'] === null ? '' : ikp_fmt($b['target'], 4),
                'realisasi'  => $b['realisasi'] === null ? '' : ikp_fmt($b['realisasi'], 4),
                'keterangan' => (string) ($b['keterangan'] ?? ''),
                'bukti_url'  => (string) ($b['bukti_url'] ?? ''),
            ];
        }
        $tb = $r['tahun_berjalan'];

        return [
            'ikp_id'         => (int) $r['ikp']['id'],
            'bulan'          => $bulan,
            'triwulan'       => $tw,
            'tahun_berjalan' => [
                'persen'       => $tb['persen'] === null ? null : capaianFormatPersen($tb['persen']),
                'status'       => $tb['status'],
                'status_label' => $tb['status_label'],
                'warna'        => $tb['warna'],
                'sampai_bulan' => $tb['sampai_bulan'],
                'keterangan'   => $tb['keterangan'],
            ],
            'target_tahunan' => $r['target_tahunan'] === null ? '' : ikp_fmt($r['target_tahunan'], 4),
            'cek_bulanan'    => ikp_cek($metode, $r['target_tahunan'], $tgt),
            'kelengkapan'    => $r['kelengkapan'],
        ];
    }

    // =====================================================================
    // FORM IKP
    // =====================================================================

    /** @return array{0: array<string, mixed>, 1: string[]} [data siap simpan, daftar galat] */
    private function bacaForm(int $opdId): array
    {
        $in    = fn (string $k): string => trim((string) $this->request->getPost($k));
        $galat = [];
        $data  = [];

        $kategori = $in('kategori');
        if (! array_key_exists($kategori, IkpModel::KATEGORI)) {
            $galat[] = 'Pilih kategori IKP.';
        }
        $data['kategori'] = $kategori;

        // --- referensi ber-id: harus ada di tabelnya (tidak pernah dibuat diam-diam) ---
        $ref = function (string $kolom, string $tabel, string $label) use ($in, &$galat, &$data): void {
            $v = $in($kolom);
            if ($v === '') {
                $data[$kolom] = null;

                return;
            }
            if (! ctype_digit($v) || ! $this->db->table($tabel)->where('id', (int) $v)->countAllResults()) {
                $galat[] = $label . ' tidak dikenal.';
                $data[$kolom] = null;

                return;
            }
            $data[$kolom] = (int) $v;
        };
        $ref('program_unggulan_id', 'ikp_program_unggulan', 'Program Unggulan');
        $ref('sasaran_pembangunan_id', 'ikp_sasaran_pembangunan', 'Sasaran Pembangunan');
        $ref('rpjmd_misi_id', 'rpjmd_misi', 'Misi');
        $ref('satuan_id', 'satuan', 'Satuan');
        $ref('buku_saku_id', 'ikp_buku_saku', 'Rujukan Buku Saku');

        if ($kategori === 'program_unggulan' && $data['program_unggulan_id'] === null) {
            $galat[] = 'IKP Program Unggulan Bupati wajib memilih salah satu dari 9 Program Unggulan.';
        }

        // --- teks ---
        $teks = function (string $kolom, string $label, int $maks, bool $wajib = false) use ($in, &$galat, &$data): void {
            $v = $in($kolom);
            if ($wajib && $v === '') {
                $galat[] = $label . ' wajib diisi.';
            }
            if (mb_strlen($v) > $maks) {
                $galat[] = $label . ' terlalu panjang (maks. ' . $maks . ' karakter).';
            }
            if ($this->berTag($v)) {
                $galat[] = $label . ' tidak boleh memuat tag HTML.';
            }
            $data[$kolom] = $v === '' ? null : $v;
        };
        $teks('output_prioritas', 'Indikator IKP (output prioritas)', 1000, true);
        $teks('outcome', 'Outcome', self::MAKS_TEKS);
        $teks('indikator_outcome', 'Indikator outcome', self::MAKS_TEKS);
        $teks('program_opd', 'Program OPD', self::MAKS_TEKS);
        $teks('bidang_urusan', 'Bidang urusan', 255);
        $teks('satuan_teks', 'Satuan (teks)', 100);
        $teks('target_5_tahun_teks', 'Target 5 tahun (teks)', 255);
        $teks('dasar_penugasan', 'Dasar penugasan', 255);
        $teks('pj_jabatan_teks', 'Jabatan penanggung jawab', 255);

        if (! str_starts_with($kategori, 'penugasan_')) {
            $data['dasar_penugasan'] = null;
        }
        if ($data['satuan_id'] === null && $data['satuan_teks'] === null) {
            $galat[] = 'Satuan wajib diisi (pilih dari daftar atau tulis sendiri).';
        }
        if ($data['satuan_id'] !== null) {
            $data['satuan_teks'] = null; // master satuan menang; teks bebas hanya cadangan
        }

        // --- metode ---
        $metode = $in('metode');
        if (! array_key_exists($metode, IkpModel::METODE)) {
            $galat[] = 'Pilih metode perhitungan agar rekap triwulan & capaian dapat dihitung.';
        }
        $data['metode'] = $metode === '' ? null : $metode;

        // --- angka ---
        $baseline = $in('baseline');
        if (! ikp_angka_sah($baseline)) {
            $galat[] = 'Baseline harus berupa angka (contoh: 200 atau 12,5).';
            $data['baseline'] = null;
        } else {
            $b = ikp_angka_baca($baseline, false); // baseline 0 bermakna (mis. belum ada nasabah)
            $data['baseline'] = $b === null ? null : round($b, 4);
        }
        $t5 = $in('target_5_tahun');
        if (! ikp_angka_sah($t5)) {
            $galat[] = 'Target 5 tahun (angka) hanya boleh berisi angka; tulis kalimat target di kolom "Target 5 tahun (uraian)".';
            $data['target_5_tahun'] = null;
        } else {
            $v = ikp_angka_baca($t5);
            $data['target_5_tahun'] = $v === null ? null : round($v, 4);
        }
        if ($data['target_5_tahun'] === null && $data['target_5_tahun_teks'] === null) {
            $galat[] = 'Isi target 5 tahun (angka, atau uraian bila targetnya berupa kalimat).';
        }

        // --- tautan pohon kinerja: simpul & indikator HARUS milik OPD ini ---
        $sas = $in('cascading_sasaran_id');
        $ind = $in('cascading_indikator_id');
        $data['cascading_sasaran_id']   = null;
        $data['cascading_indikator_id'] = null;
        if ($ind !== '' && ctype_digit($ind)) {
            $row = $this->db->table('cascading_indikator_opd ci')
                ->select('ci.id, ci.cascading_sasaran_id')
                ->join('cascading_sasaran_opd cs', 'cs.id = ci.cascading_sasaran_id')
                ->where('ci.id', (int) $ind)->where('cs.opd_id', $opdId)
                ->get()->getRowArray();
            if (! $row) {
                $galat[] = 'Indikator pohon kinerja tidak ditemukan pada perangkat daerah ini.';
            } else {
                $data['cascading_indikator_id'] = (int) $row['id'];
                $data['cascading_sasaran_id']   = (int) $row['cascading_sasaran_id'];
            }
        } elseif ($sas !== '' && ctype_digit($sas)) {
            $ok = $this->db->table('cascading_sasaran_opd')->where('id', (int) $sas)->where('opd_id', $opdId)->countAllResults();
            if (! $ok) {
                $galat[] = 'Simpul pohon kinerja tidak ditemukan pada perangkat daerah ini.';
            } else {
                $data['cascading_sasaran_id'] = (int) $sas;
            }
        }

        // --- penanggung jawab: pegawai OPD ini (termasuk alias OPD kembar) ---
        $pj = $in('pj_pegawai_id');
        $data['pj_pegawai_id'] = null;
        if ($pj !== '') {
            $pg = ctype_digit($pj) ? $this->db->table('pegawai p')->select('p.id, j.nama_jabatan')
                ->join('jabatan j', 'j.id = p.jabatan_id', 'left')
                ->where('p.id', (int) $pj)->whereIn('p.opd_id', self::OPD_ALIAS[$opdId] ?? [$opdId])
                ->get()->getRowArray() : null;
            if (! $pg) {
                $galat[] = 'Penanggung jawab harus pegawai perangkat daerah ini.';
            } else {
                $data['pj_pegawai_id'] = (int) $pg['id'];
                if ($data['pj_jabatan_teks'] === null && trim((string) $pg['nama_jabatan']) !== '') {
                    $data['pj_jabatan_teks'] = mb_substr(trim((string) $pg['nama_jabatan']), 0, 255);
                }
            }
        } else {
            $data['pj_jabatan_teks'] = null;
        }

        return [$data, array_values(array_unique($galat))];
    }

    private function dataForm(array $scope, array $tambahan): array
    {
        $ikp = $tambahan['ikp'];
        // Nama pegawai PJ untuk nilai awal Select2 (AJAX tidak memuat nilai awal sendiri).
        $pjTeks = '';
        $pjId   = old('pj_pegawai_id', $ikp['pj_pegawai_id'] ?? '');
        if ($pjId !== '' && $pjId !== null) {
            $pg = $this->db->table('pegawai p')->select('p.nama_pegawai, j.nama_jabatan')
                ->join('jabatan j', 'j.id = p.jabatan_id', 'left')->where('p.id', (int) $pjId)->get()->getRowArray();
            if ($pg) {
                $pjTeks = trim($pg['nama_pegawai'] . (trim((string) $pg['nama_jabatan']) !== '' ? ' — ' . $pg['nama_jabatan'] : ''));
            }
        }
        $bukuSaku = null;
        $bsId     = old('buku_saku_id', $ikp['buku_saku_id'] ?? '');
        if ($bsId !== '' && $bsId !== null && $this->db->tableExists('ikp_buku_saku')) {
            $bukuSaku = $this->db->table('ikp_buku_saku')->select('id, output_prioritas')->where('id', (int) $bsId)->get()->getRowArray();
        }

        return $this->dataHalaman($scope, $this->tahunDipilih(), $tambahan + [
            'title'      => $tambahan['mode'] === 'edit' ? 'Ubah IKP' : 'Tambah IKP',
            'aktif'      => 'index',
            'daftarPu'   => $this->daftarPu(),
            'daftarSp'   => $this->db->tableExists('ikp_sasaran_pembangunan')
                ? $this->db->table('ikp_sasaran_pembangunan')->orderBy('urutan')->orderBy('id')->get()->getResultArray() : [],
            'daftarMisi' => $this->daftarMisi(),
            'daftarSat'  => $this->db->table('satuan')->select('id, satuan, tipe')->orderBy('satuan')->get()->getResultArray(),
            'tanpaTahun' => true,
            'pjTeks'     => $pjTeks,
            'bukuSaku'   => $bukuSaku,
        ]);
    }

    // =====================================================================
    // PEMBANTU SCOPE, DATA HALAMAN, JSON
    // =====================================================================

    /**
     * @return array{role:string, opd_id:?int, opd_nama:?string, can_pick:bool, opd_list:array}
     */
    private function scope(): array
    {
        $role = (string) session()->get('role');
        if (in_array($role, ['admin_opd', 'admin_kecamatan'], true)) {
            $opdId = (int) (session()->get('opd_id') ?? 0) ?: null;

            return ['role' => $role, 'opd_id' => $opdId, 'opd_nama' => $opdId ? $this->namaOpd($opdId) : null, 'can_pick' => false, 'opd_list' => []];
        }
        // Super admin: pilihan OPD hanya dari daftar sah.
        $list  = $this->db->table('opd')->select('id, nama_opd')->whereNotIn('id', OpdModel::EXCLUDED_OPD_IDS)
            ->orderBy('nama_opd', 'ASC')->get()->getResultArray();
        $minta = $this->request->getGet('opd_id') ?? $this->request->getPost('opd_id');
        $minta = ctype_digit((string) $minta) ? (int) $minta : null;
        $sah   = array_map('intval', array_column($list, 'id'));
        $opdId = ($minta !== null && in_array($minta, $sah, true)) ? $minta : null;

        return ['role' => $role, 'opd_id' => $opdId, 'opd_nama' => $opdId ? $this->namaOpd($opdId) : null, 'can_pick' => true, 'opd_list' => $list];
    }

    private function namaOpd(int $opdId): ?string
    {
        $r = $this->db->table('opd')->select('nama_opd')->where('id', $opdId)->get()->getRowArray();

        return $r['nama_opd'] ?? null;
    }

    /** IKP aktif milik OPD dalam scope (dicek terhadap baris DB). */
    private function ikpMilik(array $scope, int $id): ?array
    {
        if ($scope['opd_id'] === null || $id <= 0) {
            return null;
        }

        return $this->svc()->satu($scope['opd_id'], $id);
    }

    /** URL area OPD; untuk super admin opd_id ikut dibawa agar scope tidak hilang. */
    private function u(string $path, array $query = [], ?array $scope = null): string
    {
        $scope ??= $this->scope();
        if ($scope['can_pick'] && $scope['opd_id'] !== null) {
            $query = ['opd_id' => $scope['opd_id']] + $query;
        }
        $query = array_filter($query, static fn ($v) => $v !== null && $v !== '');

        return base_url($path) . ($query === [] ? '' : '?' . http_build_query($query));
    }

    private function dataHalaman(array $scope, int $tahun, array $tambahan): array
    {
        return $tambahan + [
            'scope'        => $scope,
            'tahun'        => $tahun,
            'tahunList'    => $this->svc()->tahunPeriode(),
            'periode'      => $this->svc()->periodeAktif(),
            'kategoriList' => IkpModel::KATEGORI,
            'kategoriMeta' => self::KATEGORI_META,
            'metodeList'   => IkpModel::METODE,
            'metodeJelas'  => self::METODE_PENJELASAN,
            'bolehTambah'  => user_can('ikp_opd.create'),
            'bolehUbah'    => user_can('ikp_opd.update'),
            'bolehHapus'   => user_can('ikp_opd.delete'),
            'u'            => fn (string $path, array $q = []) => $this->u($path, $q, $scope),
            'bulanIni'     => $this->sekarangWib(),
        ];
    }

    private function halamanPilihOpd(array $scope, string $tujuan)
    {
        return view('ikp/_pilih_opd', [
            'title'  => 'Kinerja Prioritas (IKP)',
            'scope'  => $scope,
            'tujuan' => $tujuan,
            'tahun'  => $this->tahunDipilih(),
        ]);
    }

    private function svc(): IkpRekapService
    {
        return $this->rekapSvc ??= new IkpRekapService($this->db);
    }

    private function sekarangWib(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('now', new \DateTimeZone('Asia/Jakarta'));
    }

    private function jamWib(): string
    {
        return $this->sekarangWib()->format('H:i');
    }

    private function tanggalWib(): string
    {
        $n = $this->sekarangWib();

        return (int) $n->format('j') . ' ' . ikp_nama_bulan((int) $n->format('n')) . ' ' . $n->format('Y') . ' pukul ' . $n->format('H:i') . ' WIB';
    }

    /** Tahun dari ?tahun= bila di periode; bawaan = tahun berjalan (WIB), dijepit ke periode. */
    private function tahunDipilih(): int
    {
        $list  = $this->svc()->tahunPeriode();
        $minta = (int) ($this->request->getGet('tahun') ?? 0);
        if (in_array($minta, $list, true)) {
            return $minta;
        }
        $kini = (int) $this->sekarangWib()->format('Y');

        return max($list[0], min(end($list), $kini));
    }

    /**
     * Bulan terakhir yang realisasinya boleh diisi untuk $tahun (0..12),
     * berdasarkan tanggal WIB: tahun lampau 12, tahun depan 0, tahun ini = bulan berjalan.
     */
    private function bulanTerbuka(int $tahun): int
    {
        $kini = $this->sekarangWib();
        $y    = (int) $kini->format('Y');
        if ($tahun < $y) {
            return 12;
        }
        if ($tahun > $y) {
            return 0;
        }

        return (int) $kini->format('n');
    }

    private function kategoriDiminta(): string
    {
        $k = (string) $this->request->getGet('kategori');

        return array_key_exists($k, IkpModel::KATEGORI) ? $k : '';
    }

    private function puDiminta(): string
    {
        $pu = trim((string) $this->request->getGet('pu'));

        return ($pu === 'tanpa' || ctype_digit($pu)) ? $pu : '';
    }

    /** @return array<int, array<string, mixed>> */
    private function daftarPu(): array
    {
        return $this->db->table('ikp_program_unggulan')->orderBy('urutan')->orderBy('id')->get()->getResultArray();
    }

    /** Misi RPJMD periode aktif. */
    private function daftarMisi(): array
    {
        if (! $this->db->tableExists('rpjmd_misi')) {
            return [];
        }
        $p = $this->svc()->periodeAktif();
        $b = $this->db->table('rpjmd_misi')->select('id, misi')->where('tahun_mulai', $p['awal'])->where('tahun_akhir', $p['akhir']);
        if ($this->db->fieldExists('dihentikan_pada', 'rpjmd_misi')) {
            $b->where('dihentikan_pada', null);
        }

        return $b->orderBy('id')->get()->getResultArray();
    }

    /** [nama satuan huruf kecil => id] untuk mencocokkan teks satuan Buku Saku. */
    private function petaSatuan(): array
    {
        $peta = [];
        foreach ($this->db->table('satuan')->select('id, satuan')->orderBy('id')->get()->getResultArray() as $s) {
            $k = mb_strtolower(trim((string) $s['satuan']));
            $peta[$k] ??= (int) $s['id'];
        }

        return $peta;
    }

    /**
     * Simpul pohon kinerja OPD (es2..pelaksana) beserta indikatornya.
     *
     * @return array<int, array<string, mixed>>
     */
    private function simpulOpd(?int $opdId): array
    {
        if ($opdId === null || ! $this->db->tableExists('cascading_sasaran_opd')) {
            return [];
        }
        $label = [
            'es2'       => casc_relabel('Eselon II'),
            'es3'       => casc_relabel('Eselon III'),
            'es4'       => casc_relabel('Eselon IV / JF'),
            'pelaksana' => casc_pelaksana_label(),
        ];
        $urut  = ['es2' => 1, 'es3' => 2, 'es4' => 3, 'pelaksana' => 4];
        $nodes = $this->db->table('cascading_sasaran_opd')->select('id, level, parent_id, nama_sasaran')
            ->where('opd_id', $opdId)->get()->getResultArray();
        if ($nodes === []) {
            return [];
        }
        $ids  = array_map(static fn ($n) => (int) $n['id'], $nodes);
        $inds = [];
        foreach ($this->db->table('cascading_indikator_opd')->select('id, cascading_sasaran_id, indikator, satuan')
            ->whereIn('cascading_sasaran_id', $ids)->orderBy('id')->get()->getResultArray() as $i) {
            $inds[(int) $i['cascading_sasaran_id']][] = [
                'id'        => (int) $i['id'],
                'indikator' => (string) $i['indikator'],
                'satuan'    => (string) ($i['satuan'] ?? ''),
            ];
        }
        $out = [];
        foreach ($nodes as $n) {
            $out[] = [
                'id'          => (int) $n['id'],
                'level'       => (string) $n['level'],
                'level_label' => $label[$n['level']] ?? (string) $n['level'],
                'parent_id'   => $n['parent_id'] === null ? null : (int) $n['parent_id'],
                'sasaran'     => (string) $n['nama_sasaran'],
                'indikator'   => $inds[(int) $n['id']] ?? [],
            ];
        }
        usort($out, static fn ($a, $b) => [($urut[$a['level']] ?? 9), $a['id']] <=> [($urut[$b['level']] ?? 9), $b['id']]);

        return $out;
    }

    /**
     * MENGAPA hanya pola tag yang ditolak, bukan setiap '<' / '>': rumusan
     * indikator sah memakai "< 3 hari" atau "> 90%". Yang dicegah adalah
     * penyusupan markup (<script, <img, </…) — keluaran tetap di-esc().
     */
    private function berTag(string $teks): bool
    {
        return (bool) preg_match('~<\s*[a-z!/?]~i', $teks);
    }

    private function userId(): ?int
    {
        $id = (int) (session()->get('user_id') ?? 0);

        return $id > 0 ? $id : null;
    }

    private function mintaJson(): bool
    {
        return $this->request->isAJAX()
            || str_contains(strtolower($this->request->getHeaderLine('Accept')), 'application/json')
            || str_contains(strtolower($this->request->getHeaderLine('Content-Type')), 'application/json');
    }

    /** @return array<string, mixed> */
    private function jsonMasuk(): array
    {
        try {
            $j = $this->request->getJSON(true);
        } catch (\Throwable $e) {
            $j = null;
        }

        return is_array($j) ? $j : (array) $this->request->getPost();
    }

    private function sukses(array $data)
    {
        return $this->response->setJSON([
            'status'   => 'success',
            'data'     => $data,
            'csrfHash' => csrf_hash(),
        ]);
    }

    private function gagal(string $pesan, int $kode)
    {
        return $this->response->setStatusCode($kode)->setJSON([
            'status'   => 'error',
            'message'  => $pesan,
            'csrfHash' => csrf_hash(),
        ]);
    }
}
