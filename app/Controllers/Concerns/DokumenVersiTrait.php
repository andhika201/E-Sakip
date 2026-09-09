<?php

namespace App\Controllers\Concerns;

use App\Models\DokumenVersiModel;
use App\Services\Version\ArsipRegistry;
use App\Services\Version\IzinSuntingService;
use App\Services\Version\VersionApprovalService;
use App\Services\Version\VersionAuditService;
use App\Services\Version\VersionCompareService;
use App\Services\Version\VersionCorrectionService;
use App\Services\Version\VersionDeepCopyService;
use App\Services\Version\VersionResolver;
use App\Services\Version\VersionScope;
use App\Services\Version\VersionTimelineService;
use Throwable;

/**
 * Aksi versi dokumen, dipakai bersama RpjmdController dan RenstraController.
 *
 * Mengikuti pola IkuRevisiTrait & LakipSnapshotTrait yang sudah ada: satu
 * berkas aksi, dua controller pemakai, perbedaan lingkup diserahkan ke empat
 * method abstrak di bawah.
 *
 * =====================================================================
 * OTORISASI DIPERIKSA DI SINI, BUKAN DISERAHKAN KE modperm
 *
 * `ModulePermissionFilter::actionsFor()` menyimpulkan aksi dari SUBSTRING path.
 * Path `rpjmd/versi/tetapkan/5` tidak mengandung save/update/edit/delete/tambah,
 * sehingga filter itu hanya akan menuntut `rpjmd.view` — jauh lebih longgar
 * daripada yang seharusnya.
 *
 * Karena itu setiap aksi di bawah memeriksa permission-nya sendiri secara
 * eksplisit (§54, §55). Filter tetap dipasang sebagai lapis pertama, tetapi
 * bukan satu-satunya penjaga.
 * =====================================================================
 */
trait DokumenVersiTrait
{
    protected ?DokumenVersiModel $versiModel = null;

    /** 'rpjmd' | 'renstra' — sekaligus prefix permission `<modul>.version.*`. */
    abstract protected function versiModul(): string;

    /** NULL untuk RPJMD (tingkat kabupaten); id OPD dari SESI untuk Renstra. */
    abstract protected function versiOpdId(): ?int;

    /** Basis URL modul, mis. 'adminkab/rpjmd' atau 'adminopd/renstra'. */
    abstract protected function versiBaseUrl(): string;

    /** Judul yang ditampilkan, mis. 'RPJMD' atau 'Renstra'. */
    abstract protected function versiNamaDokumen(): string;

    /* =========================================================
     * INFRASTRUKTUR
     * =======================================================*/

    protected function versi(): DokumenVersiModel
    {
        return $this->versiModel ??= new DokumenVersiModel();
    }

    protected function versiBoleh(string $aksi): bool
    {
        if (! function_exists('user_can')) {
            return false;
        }

        return user_can($this->versiModul() . '.version.' . $aksi);
    }

    /**
     * Lingkup versi untuk sebuah periode.
     *
     * @param string $periode format "2025-2029"
     */
    protected function versiScope(string $periode): ?VersionScope
    {
        if (! preg_match('/^(\d{4})-(\d{4})$/', trim($periode), $m)) {
            return null;
        }

        try {
            return $this->versiModul() === VersionScope::MODUL_RPJMD
                ? VersionScope::rpjmd((int) $m[1], (int) $m[2])
                : VersionScope::renstra((int) $this->versiOpdId(), (int) $m[1], (int) $m[2]);
        } catch (Throwable $e) {
            return null;
        }
    }

    /**
     * Periode yang tersedia: dari data live DAN dari registri versi.
     *
     * Keduanya digabung supaya periode yang baru punya draft (belum menyentuh
     * tabel live) tetap muncul, dan periode lama yang belum pernah diversikan
     * juga tetap terlihat.
     *
     * @return array<int,array{periode:string,tahun_mulai:int,tahun_akhir:int}>
     */
    protected function versiPeriodeTersedia(): array
    {
        $db    = \Config\Database::connect();
        $out   = [];
        $modul = $this->versiModul();

        try {
            if ($modul === VersionScope::MODUL_RPJMD) {
                $rows = $db->table('rpjmd_misi')
                    ->select('tahun_mulai, tahun_akhir')->distinct()
                    ->where('tahun_mulai IS NOT NULL', null, false)
                    ->get()->getResultArray();
            } else {
                $rows = $db->table('renstra_sasaran')
                    ->select('tahun_mulai, tahun_akhir')->distinct()
                    ->where('opd_id', $this->versiOpdId())
                    ->where('tahun_mulai IS NOT NULL', null, false)
                    ->get()->getResultArray();
            }
        } catch (Throwable $e) {
            $rows = [];
        }

        foreach ($rows as $r) {
            $key       = (int) $r['tahun_mulai'] . '-' . (int) $r['tahun_akhir'];
            $out[$key] = [
                'periode'     => $key,
                'tahun_mulai' => (int) $r['tahun_mulai'],
                'tahun_akhir' => (int) $r['tahun_akhir'],
            ];
        }

        if ($this->versi()->siap()) {
            foreach ($this->versi()->daftarLingkup($modul) as $r) {
                if ($modul !== VersionScope::MODUL_RPJMD
                    && (int) $r['opd_key'] !== (int) $this->versiOpdId()) {
                    continue;
                }

                $key       = (int) $r['periode_mulai'] . '-' . (int) $r['periode_akhir'];
                $out[$key] = [
                    'periode'     => $key,
                    'tahun_mulai' => (int) $r['periode_mulai'],
                    'tahun_akhir' => (int) $r['periode_akhir'],
                ];
            }
        }

        krsort($out);

        return array_values($out);
    }

    /** Tolak dengan pesan yang menjelaskan, bukan halaman kosong. */
    protected function versiTolak(string $pesan)
    {
        return redirect()->to(base_url($this->versiBaseUrl() . '/versi'))->with('error', $pesan);
    }

    protected function versiBelumSiap()
    {
        return redirect()->to(base_url($this->versiBaseUrl()))->with(
            'error',
            'Fitur versi dokumen belum aktif: tabel registri versi belum terpasang. '
            . 'Jalankan db/update_2026-08-20_versioning_dokumen.sql lebih dulu.'
        );
    }

    /* =========================================================
     * DAFTAR VERSI (§48)
     * =======================================================*/

    public function versiIndex()
    {
        if (! $this->versiBoleh('view')) {
            return $this->versiTolakIzin();
        }

        if (! $this->versi()->siap()) {
            return $this->versiBelumSiap();
        }

        $resolver = new VersionResolver();
        $periode  = $this->versiPeriodeTersedia();
        $blok     = [];

        foreach ($periode as $p) {
            $scope = $this->versiScope($p['periode']);

            if ($scope === null) {
                continue;
            }

            $daftar  = $this->versi()->daftar($scope);
            $sekarang = null;
            $konflik  = null;

            try {
                $sekarang = $resolver->getEffectiveVersion($scope);
            } catch (Throwable $e) {
                // Konflik timeline ditampilkan apa adanya; sistem TIDAK memilih
                // salah satu diam-diam (§26, §81).
                $konflik = $e->getMessage();
            }

            foreach ($daftar as &$d) {
                $d['badge']  = $resolver->badge($d);
                $d['rentang'] = $resolver->rentangTeks($d);
            }
            unset($d);

            $blok[] = [
                'periode'  => $p['periode'],
                'scope'    => $scope,
                'daftar'   => $daftar,
                'sekarang' => $sekarang,
                'konflik'  => $konflik,
            ];
        }

        return view('versi/index', [
            'title'         => 'Versi ' . $this->versiNamaDokumen(),
            'judulHalaman'  => 'Versi Dokumen ' . $this->versiNamaDokumen(),
            'namaDokumen'   => $this->versiNamaDokumen(),
            'baseUrl'       => $this->versiBaseUrl(),
            'blok'          => $blok,
            'bolehBuat'     => $this->versiBoleh('create'),
            'bolehAjukan'   => $this->versiBoleh('submit'),
            'bolehTetapkan' => $this->versiBoleh('publish'),
        ]);
    }

    /* =========================================================
     * BUAT VERSI (§9)
     * =======================================================*/

    public function versiBuat()
    {
        if (! $this->versiBoleh('create')) {
            return $this->versiTolakIzin();
        }

        if (! $this->versi()->siap()) {
            return $this->versiBelumSiap();
        }

        $periode = (string) ($this->request->getGet('periode') ?? '');
        $scope   = $this->versiScope($periode);

        if ($scope === null) {
            return $this->versiTolak('Periode dokumen belum dipilih atau tidak sah.');
        }

        // Hanya versi PUBLISHED yang boleh jadi sumber salinan (§10).
        $sumberSalin = $this->versi()->daftar($scope, [DokumenVersiModel::STATUS_PUBLISHED]);

        return view('versi/form', [
            'title'        => 'Buat Versi ' . $this->versiNamaDokumen(),
            'judulHalaman' => 'Buat Versi Baru — ' . $this->versiNamaDokumen(),
            'namaDokumen'  => $this->versiNamaDokumen(),
            'baseUrl'      => $this->versiBaseUrl(),
            'scope'        => $scope,
            'periode'      => $periode,
            'nomorBerikut' => $this->versi()->nomorBerikutnya($scope),
            'sumberSalin'  => $sumberSalin,
        ]);
    }

    public function versiSimpan()
    {
        if (! $this->versiBoleh('create')) {
            return $this->versiTolakIzin();
        }

        $periode = (string) ($this->request->getPost('periode') ?? '');
        $scope   = $this->versiScope($periode);

        if ($scope === null) {
            return $this->versiTolak('Periode dokumen tidak sah.');
        }

        $sumber = (string) ($this->request->getPost('sumber') ?? VersionDeepCopyService::SUMBER_LIVE);

        try {
            $hasil = (new VersionDeepCopyService())->buatVersi($scope, [
                'label'                  => (string) $this->request->getPost('label'),
                'effective_from'         => (string) $this->request->getPost('effective_from'),
                'sumber'                 => $sumber,
                'copied_from_version_id' => (int) $this->request->getPost('copied_from_version_id'),
                'alasan_perubahan'       => $this->kosong($this->request->getPost('alasan_perubahan')),
                'dasar_perubahan'        => $this->kosong($this->request->getPost('dasar_perubahan')),
                'nomor_dasar'            => $this->kosong($this->request->getPost('nomor_dasar')),
                'tanggal_dasar'          => $this->kosong($this->request->getPost('tanggal_dasar')),
                'catatan'                => $this->kosong($this->request->getPost('catatan')),
                'created_by'             => session()->get('user_id') ?? session()->get('id'),
            ]);
        } catch (Throwable $e) {
            return redirect()->back()->withInput()->with('error', pesanGalat($e, 'umum.dokumenVersi'));
        }

        return redirect()
            ->to(base_url($this->versiBaseUrl() . '/versi/lihat/' . $hasil['version_id']))
            ->with('success', 'Versi baru dibuat sebagai draft. Periksa isinya sebelum diajukan.');
    }

    /* =========================================================
     * LIHAT ISI VERSI
     * =======================================================*/

    public function versiLihat($id = null)
    {
        if (! $this->versiBoleh('view')) {
            return $this->versiTolakIzin();
        }

        $baris = $this->versiMilikSaya((int) $id);

        if ($baris === null) {
            return $this->versiTolak('Versi tidak ditemukan pada lingkup Anda.');
        }

        $scope    = VersionScope::dariBaris($baris);
        $arsip    = (new ArsipRegistry())->untuk($scope->modul());
        $resolver = new VersionResolver();
        $approval = new VersionApprovalService();
        $timeline = new VersionTimelineService();

        return view('versi/lihat', [
            'title'         => $baris['label'],
            'judulHalaman'  => $baris['label'],
            'namaDokumen'   => $this->versiNamaDokumen(),
            'baseUrl'       => $this->versiBaseUrl(),
            'versi'         => $baris,
            'scope'         => $scope,
            'badge'         => $resolver->badge($baris),
            'rentang'       => $resolver->rentangTeks($baris),
            'isi'           => $arsip !== null && $arsip->siap() ? $arsip->isi((int) $id) : [],
            'ringkas'       => $arsip !== null && $arsip->siap() ? $arsip->ringkas((int) $id) : [],
            'riwayat'       => (new VersionAuditService())->riwayat((int) $id),
            'praTinjau'     => $baris['status'] === DokumenVersiModel::STATUS_DRAFT
                ? $timeline->praTinjau($scope, (string) $baris['effective_from'], (int) $id)
                : null,
            'galatValidasi' => $baris['status'] === DokumenVersiModel::STATUS_DRAFT
                ? $timeline->validasi((int) $id)
                : [],
            'bolehSunting'  => $approval->bolehSunting($baris) && $this->versiBoleh('update_draft'),
            'bolehKeterangan' => $approval->bolehUbahKeterangan($baris) && $this->versiBoleh('update_draft'),
            'bolehTanggalBaseline' => ($this->versiBoleh('create')
                    && $approval->bolehPerbaikiTanggalBaseline($baris))
                || $this->versiBolehGeserTanggal($baris, $scope),
            'dampakKosong'  => $this->versiDampakKosong($baris),
            'bolehKoreksi'  => $approval->bolehKoreksi($baris)
                && function_exists('user_can') && user_can('version_correction.request'),
            'koreksiTertunda' => array_values(array_filter(
                (new VersionCorrectionService())->daftar((int) $id),
                static fn ($k) => $k['status'] === VersionCorrectionService::STATUS_PENDING
            )),
            'bolehIsiBaseline' => $this->versiBoleh('create')
                && $approval->bolehIsiBaseline($baris, $this->versiArsipKosong($baris)),
            // Menunjuk hanya masuk akal untuk versi yang sudah resmi DAN berisi.
            'bolehTunjuk'   => $this->versiBoleh('pin')
                && $this->versi()->siapTampilan()
                && $baris['status'] === DokumenVersiModel::STATUS_PUBLISHED
                && ! $this->versiArsipKosong($baris),
            'sudahDitunjuk' => (int) ($baris['tampilan_utama'] ?? 0) === 1,
            // Versi yang berlaku menurut TANGGAL. Dibawa ke tampilan supaya
            // selisihnya dengan tunjukan bisa dinyatakan, bukan didiamkan.
            'versiMenurutTanggal' => $resolver->getEffectiveVersion($scope, date('Y-m-d')),
            'bolehAjukan'   => $approval->bolehAjukan($baris) && $this->versiBoleh('submit'),
            'bolehTetapkan' => $this->versiBoleh('publish')
                && ($approval->bolehVerifikasi($baris) || $approval->bolehAjukan($baris)),
            'bolehBatalkan' => $approval->bolehBatalkan($baris) && $this->versiBoleh('update_draft'),
            'keadaanHapus'  => $this->versiKeadaanHapus($baris),
            'daftarBanding' => $this->versi()->daftar($scope, [DokumenVersiModel::STATUS_PUBLISHED]),
        ]);
    }

    /* =========================================================
     * AJUKAN KOREKSI (§20, §21)
     * =======================================================*/

    public function versiKoreksi($id = null)
    {
        if (! function_exists('user_can') || ! user_can('version_correction.request')) {
            return $this->versiTolakIzin();
        }

        $baris = $this->versiMilikSaya((int) $id);

        if ($baris === null) {
            return $this->versiTolak('Versi tidak ditemukan pada lingkup Anda.');
        }

        if ($baris['status'] !== DokumenVersiModel::STATUS_PUBLISHED) {
            return redirect()
                ->to(base_url($this->versiBaseUrl() . '/versi/lihat/' . (int) $id))
                ->with('error', 'Koreksi hanya untuk versi yang sudah ditetapkan. '
                    . 'Versi ini masih ' . $baris['status'] . ' — sunting saja langsung.');
        }

        $scope    = VersionScope::dariBaris($baris);
        $arsip    = (new ArsipRegistry())->untuk($scope->modul());
        $koreksi  = new VersionCorrectionService();

        return view('versi/koreksi', [
            'title'        => 'Ajukan Koreksi — ' . $baris['label'],
            'judulHalaman' => 'Ajukan Koreksi',
            'namaDokumen'  => $this->versiNamaDokumen(),
            'baseUrl'      => $this->versiBaseUrl(),
            'versi'        => $baris,
            'isi'          => $arsip !== null && $arsip->siap() ? $arsip->isi((int) $id) : [],
            'daftarPutih'  => $koreksi->daftarPutih(),
            'dipakaiLakip' => $koreksi->dipakaiLakip((int) $id),
            'riwayat'      => $koreksi->daftar((int) $id),
        ]);
    }

    public function versiKoreksiSimpan($id = null)
    {
        if (! function_exists('user_can') || ! user_can('version_correction.request')) {
            return $this->versiTolakIzin();
        }

        if ($this->versiMilikSaya((int) $id) === null) {
            return $this->versiTolak('Versi tidak ditemukan pada lingkup Anda.');
        }

        try {
            (new VersionCorrectionService())->ajukan((int) $id, [
                'entity_type'     => $this->request->getPost('entity_type'),
                'entity_id'       => $this->request->getPost('entity_id'),
                'field'           => $this->request->getPost('field'),
                'requested_value' => $this->request->getPost('requested_value'),
                'reason'          => $this->request->getPost('reason'),
                'dasar'           => $this->request->getPost('dasar'),
            ], session()->get('user_id') ?? session()->get('id'));
        } catch (Throwable $e) {
            return redirect()->back()->withInput()->with('error', pesanGalat($e, 'umum.dokumenVersi'));
        }

        return redirect()
            ->to(base_url($this->versiBaseUrl() . '/versi/koreksi/' . (int) $id))
            ->with('success', 'Permintaan koreksi diajukan. Perubahan baru berlaku '
                . 'setelah disetujui Admin Kabupaten.');
    }

    public function versiKoreksiBatal($id = null, $koreksiId = null)
    {
        // Gerbang yang sama dengan mengajukan/menyimpan koreksi — membatalkan
        // adalah bagian dari alur yang sama, bukan aksi bebas izin.
        if (! function_exists('user_can') || ! user_can('version_correction.request')) {
            return $this->versiTolakIzin();
        }

        if ($this->versiMilikSaya((int) $id) === null) {
            return $this->versiTolak('Versi tidak ditemukan pada lingkup Anda.');
        }

        $svc     = new VersionCorrectionService();
        $koreksi = $svc->ambil((int) $koreksiId);

        // Anti-IDOR: $koreksiId datang dari URL dan harus benar-benar milik
        // versi yang lolos pemeriksaan lingkup di atas. Tanpa ikatan ini,
        // koreksi pending milik OPD lain (atau modul lain) bisa dibatalkan
        // hanya dengan mengarang angkanya.
        if ($koreksi === null || (int) $koreksi['version_id'] !== (int) $id) {
            return $this->versiTolak('Permintaan koreksi tidak ditemukan pada versi ini.');
        }

        try {
            $svc->batalkan(
                (int) $koreksiId,
                session()->get('user_id') ?? session()->get('id')
            );
        } catch (Throwable $e) {
            return redirect()->back()->with('error', pesanGalat($e, 'umum.dokumenVersi'));
        }

        return redirect()->back()->with('success', 'Permintaan koreksi dibatalkan.');
    }

    /* =========================================================
     * UBAH KETERANGAN VERSI (label, tanggal berlaku, dasar, alasan)
     * =======================================================*/

    public function versiKeterangan($id = null)
    {
        if (! $this->versiBoleh('update_draft') && ! $this->versiBoleh('create')) {
            return $this->versiTolakIzin();
        }

        $baris = $this->versiMilikSaya((int) $id);

        if ($baris === null) {
            return $this->versiTolak('Versi tidak ditemukan pada lingkup Anda.');
        }

        $approval = new VersionApprovalService();
        $scope    = VersionScope::dariBaris($baris);

        $bolehPenuh   = $approval->bolehUbahKeterangan($baris) && $this->versiBoleh('update_draft');

        // Memperbaiki tebakan tanggal sistem pada dokumen SENDIRI cukup dengan
        // izin membuat versi. Menggerbanginya dengan `publish` (yang sengaja
        // hanya milik Kabupaten) membuat jalan buntu: rute adminopd/* juga
        // tidak menerima role admin_kab, sehingga tidak ada satu pun pihak yang
        // bisa memperbaikinya.
        $bolehTanggal = ($approval->bolehPerbaikiTanggalBaseline($baris) && $this->versiBoleh('create'))
            || $this->versiBolehGeserTanggal($baris, $scope);

        if (! $bolehPenuh && ! $bolehTanggal) {
            return redirect()
                ->to(base_url($this->versiBaseUrl() . '/versi/lihat/' . (int) $id))
                ->with('error', 'Tanggal berlaku versi resmi hanya bisa digeser oleh Admin Kabupaten, '
                    . 'atau oleh penyusunnya selama ada izin sunting yang berlaku pada periode ini.');
        }

        return view('versi/keterangan', [
            'title'         => 'Ubah Keterangan — ' . $baris['label'],
            'judulHalaman'  => 'Ubah Keterangan Versi',
            'namaDokumen'   => $this->versiNamaDokumen(),
            'baseUrl'       => $this->versiBaseUrl(),
            'versi'         => $baris,
            'scope'         => $scope,
            'bolehPenuh'    => $bolehPenuh,
            'bolehTanggal'  => $bolehTanggal,
            'timeline'      => $this->versi()->publishedUrutMaju($scope),
        ]);
    }

    public function versiKeteranganSimpan($id = null)
    {
        $baris = $this->versiMilikSaya((int) $id);

        if ($baris === null) {
            return $this->versiTolak('Versi tidak ditemukan pada lingkup Anda.');
        }

        $approval     = new VersionApprovalService();
        $scope        = VersionScope::dariBaris($baris);
        $bolehPenuh   = $approval->bolehUbahKeterangan($baris) && $this->versiBoleh('update_draft');
        $bolehTanggal = ($approval->bolehPerbaikiTanggalBaseline($baris) && $this->versiBoleh('create'))
            || $this->versiBolehGeserTanggal($baris, $scope);

        if (! $bolehPenuh && ! $bolehTanggal) {
            return $this->versiTolakIzin();
        }

        $tanggal = trim((string) $this->request->getPost('effective_from'));
        $alasan  = trim((string) $this->request->getPost('alasan_ubah'));

        // Menggeser tanggal baseline yang sudah published wajib beralasan:
        // itu satu-satunya perubahan timeline yang tidak lewat penerbitan versi.
        if ($bolehTanggal && ! $bolehPenuh && $alasan === '') {
            return redirect()->back()->withInput()
                ->with('error', 'Alasan perubahan tanggal berlaku wajib diisi.');
        }

        $db = \Config\Database::connect();

        try {
            $db->transBegin();
            $db->resetTransStatus();

            if ($bolehPenuh) {
                $this->versi()->perbarui((int) $id, [
                    'label'            => $this->kosong($this->request->getPost('label'))
                        ?? $baris['label'],
                    'dasar_perubahan'  => $this->kosong($this->request->getPost('dasar_perubahan')),
                    'nomor_dasar'      => $this->kosong($this->request->getPost('nomor_dasar')),
                    'tanggal_dasar'    => $this->kosong($this->request->getPost('tanggal_dasar')),
                    'alasan_perubahan' => $this->kosong($this->request->getPost('alasan_perubahan')),
                    'catatan'          => $this->kosong($this->request->getPost('catatan')),
                ]);
            }

            if ($tanggal !== '') {
                (new VersionTimelineService($db))->ubahTanggalBerlaku(
                    (int) $id,
                    $tanggal,
                    $alasan !== '' ? $alasan : null
                );
            }

            if ($db->transStatus() === false) {
                $db->transRollback();

                return redirect()->back()->withInput()->with('error', 'Penyimpanan gagal.');
            }

            $db->transCommit();
        } catch (Throwable $e) {
            if ($db->transDepth > 0) {
                $db->transRollback();
            }

            return redirect()->back()->withInput()->with('error', pesanGalat($e, 'umum.dokumenVersi'));
        }

        return redirect()
            ->to(base_url($this->versiBaseUrl() . '/versi/lihat/' . (int) $id))
            ->with('success', 'Keterangan versi disimpan.');
    }

    /**
     * Isi baseline kosong dengan salinan kondisi berjalan (§43).
     *
     * Baseline lahir kosong karena SQL murni tidak bisa membekukan isi dengan
     * benar — pembekuan menuntut terjemahan satuan dan penomoran urut yang sama
     * persis dengan aplikasi. Aksi inilah yang mewujudkan maksud §43: "data
     * existing sebagai Version 1 Published".
     *
     * TIDAK menyentuh tabel live sama sekali. Yang ditulis hanya arsip, dan
     * isinya disalin DARI live — jadi keduanya memang sudah sama.
     */
    public function versiIsiBaseline($id = null)
    {
        if (! $this->versiBoleh('create')) {
            return $this->versiTolakIzin();
        }

        $baris = $this->versiMilikSaya((int) $id);

        if ($baris === null) {
            return $this->versiTolak('Versi tidak ditemukan pada lingkup Anda.');
        }

        $approval = new VersionApprovalService();

        if (! $approval->bolehIsiBaseline($baris, $this->versiArsipKosong($baris))) {
            return redirect()->back()->with(
                'error',
                'Hanya baseline otomatis yang masih kosong yang bisa diisi dari kondisi berjalan.'
            );
        }

        $scope = VersionScope::dariBaris($baris);
        $arsip = (new ArsipRegistry())->untuk($scope->modul());

        if ($arsip === null || ! $arsip->siap()) {
            return $this->versiTolak('Arsip modul ini tidak tersedia.');
        }

        $db = \Config\Database::connect();

        try {
            $db->transBegin();
            $db->resetTransStatus();

            $n = $arsip->bekukanDariLive((int) $id, $scope);

            if ($db->transStatus() === false) {
                $db->transRollback();

                return redirect()->back()->with('error', 'Pembekuan gagal pada salah satu query.');
            }

            $db->transCommit();

            (new VersionAuditService())->catat((int) $id, VersionAuditService::AKSI_APPLIED, [
                'ringkasan' => 'Baseline diisi dari kondisi berjalan: ' . implode(', ', array_map(
                    static fn ($k, $v) => $k . '=' . $v,
                    array_keys($n),
                    $n
                )),
                'sesudah'   => $n,
                'oleh'      => session()->get('user_id') ?? session()->get('id'),
            ]);
        } catch (Throwable $e) {
            if ($db->transDepth > 0) {
                $db->transRollback();
            }

            return redirect()->back()->with('error', pesanGalat($e, 'umum.dokumenVersi'));
        }

        return redirect()->back()->with(
            'success',
            'Baseline diisi dari kondisi berjalan. Data berjalan tidak berubah — '
            . 'yang ditulis hanya arsipnya, dan isinya memang disalin dari sana.'
        );
    }

    /**
     * Ringkasan dampak bila versi KOSONG ini ditetapkan, atau null bila berisi.
     *
     * Mengembalikan teks siap tampil, mis. "3 sasaran dan 7 indikator".
     */
    protected function versiDampakKosong(array $baris): ?string
    {
        if (! $this->versiArsipKosong($baris)) {
            return null;
        }

        $arsip = (new ArsipRegistry())->untuk((string) $baris['modul']);

        if ($arsip === null || ! $arsip->siap()) {
            return null;
        }

        $n = $arsip->hitungLiveAktif(VersionScope::dariBaris($baris));
        $bagian = [];

        foreach ($n as $nama => $jml) {
            if ((int) $jml > 0) {
                $bagian[] = $jml . ' ' . str_replace('_', ' ', $nama);
            }
        }

        return $bagian === [] ? 'data (saat ini tidak ada baris aktif)' : implode(' dan ', $bagian);
    }

    /** True bila versi ini belum punya isi arsip sama sekali. */
    protected function versiArsipKosong(array $baris): bool
    {
        $arsip = (new ArsipRegistry())->untuk((string) $baris['modul']);

        if ($arsip === null || ! $arsip->siap()) {
            return true;
        }

        foreach ($arsip->ringkas((int) $baris['id']) as $jml) {
            if ((int) $jml > 0) {
                return false;
            }
        }

        return true;
    }

    /* =========================================================
     * SUNTING ISI DRAFT (§9)
     * =======================================================*/

    public function versiSunting($id = null)
    {
        if (! $this->versiBoleh('update_draft')) {
            return $this->versiTolakIzin();
        }

        $baris = $this->versiDraftMilikSaya((int) $id);

        if (! is_array($baris)) {
            return $baris;
        }

        $scope = VersionScope::dariBaris($baris);
        $arsip = (new ArsipRegistry())->untuk($scope->modul());

        return view('versi/sunting', [
            'title'        => 'Sunting ' . $baris['label'],
            'judulHalaman' => 'Sunting Draft: ' . $baris['label'],
            'namaDokumen'  => $this->versiNamaDokumen(),
            'baseUrl'      => $this->versiBaseUrl(),
            'versi'        => $baris,
            'scope'        => $scope,
            'modul'        => $scope->modul(),
            'isi'          => $arsip !== null && $arsip->siap() ? $arsip->isi((int) $id) : [],
            'tahun'        => range($scope->periodeMulai(), $scope->periodeAkhir()),
            'satuanOpsi'   => $this->versiSatuanOpsi(),
            'indikatorAsal' => $this->versiIndikatorUntukLineage($scope),
        ]);
    }

    public function versiSuntingSimpan($id = null)
    {
        if (! $this->versiBoleh('update_draft')) {
            return $this->versiTolakIzin();
        }

        $baris = $this->versiDraftMilikSaya((int) $id);

        if (! is_array($baris)) {
            return $baris;
        }

        $scope = VersionScope::dariBaris($baris);
        $arsip = (new ArsipRegistry())->untuk($scope->modul());

        if ($arsip === null || ! $arsip->siap()) {
            return $this->versiTolak('Arsip modul ini tidak tersedia.');
        }

        $data = [
            'misi'      => (array) ($this->request->getPost('misi') ?? []),
            'tujuan'    => (array) ($this->request->getPost('tujuan') ?? []),
            'sasaran'   => (array) ($this->request->getPost('sasaran') ?? []),
            'indikator' => (array) ($this->request->getPost('indikator') ?? []),
            'hapus'     => (array) ($this->request->getPost('hapus') ?? []),
            'baru'      => (array) ($this->request->getPost('baru') ?? []),
        ];

        try {
            // Transaksi dibuka di sini, bukan di model: satu penyimpanan
            // menyentuh empat tabel arsip sekaligus, dan setengah jadi berarti
            // draft yang isinya tidak masuk akal.
            $db = \Config\Database::connect();
            $db->transBegin();
            $db->resetTransStatus();

            $n = $arsip->simpanSuntingan((int) $id, $data);

            if ($db->transStatus() === false) {
                $db->transRollback();

                return redirect()->back()->with('error', 'Penyimpanan gagal pada salah satu query.');
            }

            $db->transCommit();

            (new VersionAuditService())->catat((int) $id, VersionAuditService::AKSI_EDITED_DRAFT, [
                'ringkasan' => 'Draft disunting: ' . implode(', ', array_map(
                    static fn ($k, $v) => $k . '=' . $v,
                    array_keys($n),
                    $n
                )),
                'sesudah'   => $n,
                'oleh'      => session()->get('user_id') ?? session()->get('id'),
            ]);
        } catch (Throwable $e) {
            if (isset($db) && $db->transDepth > 0) {
                $db->transRollback();
            }

            return redirect()->back()->withInput()->with('error', pesanGalat($e, 'umum.dokumenVersi'));
        }

        return redirect()
            ->to(base_url($this->versiBaseUrl() . '/versi/sunting/' . (int) $id))
            ->with('success', 'Draft disimpan. Data berjalan belum berubah — perubahan baru '
                . 'diterapkan setelah versi ini ditetapkan berlaku.');
    }

    /**
     * Ambil versi HANYA bila draft milik lingkup pemakai.
     *
     * Versi published sengaja ditolak dengan pesan yang mengarahkan, bukan
     * sekadar "tidak ditemukan": operator perlu tahu bahwa jalannya adalah
     * versi baru atau pengajuan koreksi (§16).
     *
     * @return array|\CodeIgniter\HTTP\RedirectResponse
     */
    protected function versiDraftMilikSaya(int $id)
    {
        $baris = $this->versiMilikSaya($id);

        if ($baris === null) {
            return $this->versiTolak('Versi tidak ditemukan pada lingkup Anda.');
        }

        if ($baris['status'] !== DokumenVersiModel::STATUS_DRAFT) {
            return redirect()
                ->to(base_url($this->versiBaseUrl() . '/versi/lihat/' . $id))
                ->with('error', $baris['status'] === DokumenVersiModel::STATUS_PENDING
                    ? 'Versi sedang menunggu verifikasi, jadi tidak bisa disunting.'
                    : 'Versi yang sudah ditetapkan bersifat tetap. Untuk perubahan substantif buat '
                      . 'versi baru; untuk salah ketik gunakan pengajuan koreksi.');
        }

        return $baris;
    }

    /** Opsi satuan dari master, untuk dropdown indikator. */
    protected function versiSatuanOpsi(): array
    {
        try {
            return \Config\Database::connect()->table('satuan')
                ->select('id, satuan')->orderBy('satuan', 'ASC')
                ->get()->getResultArray();
        } catch (Throwable $e) {
            return [];
        }
    }

    /**
     * Indikator berjalan pada periode ini — untuk memilih "indikator mana yang
     * digantikan" saat menandai sebuah baris sebagai `pengganti` (§11).
     */
    protected function versiIndikatorUntukLineage(VersionScope $scope): array
    {
        try {
            $db = \Config\Database::connect();

            if ($scope->modul() === VersionScope::MODUL_RPJMD) {
                return $db->table('rpjmd_indikator_sasaran i')
                    ->select('i.id, i.indikator_sasaran AS indikator, s.sasaran_rpjmd AS sasaran')
                    ->join('rpjmd_sasaran s', 's.id = i.sasaran_id', 'left')
                    ->join('rpjmd_tujuan t', 't.id = s.tujuan_id', 'left')
                    ->join('rpjmd_misi m', 'm.id = t.misi_id', 'left')
                    ->where('m.tahun_mulai', $scope->periodeMulai())
                    ->where('m.tahun_akhir', $scope->periodeAkhir())
                    ->where('i.dihentikan_pada IS NULL', null, false)
                    ->orderBy('s.id', 'ASC')->orderBy('i.id', 'ASC')
                    ->get()->getResultArray();
            }

            return $db->table('renstra_indikator_sasaran i')
                ->select('i.id, i.indikator_sasaran AS indikator, s.sasaran AS sasaran')
                ->join('renstra_sasaran s', 's.id = i.renstra_sasaran_id', 'left')
                ->where('s.opd_id', $scope->opdId())
                ->where('s.tahun_mulai', $scope->periodeMulai())
                ->where('s.tahun_akhir', $scope->periodeAkhir())
                ->where('i.dihentikan_pada IS NULL', null, false)
                ->orderBy('s.id', 'ASC')->orderBy('i.id', 'ASC')
                ->get()->getResultArray();
        } catch (Throwable $e) {
            return [];
        }
    }

    /* =========================================================
     * TRANSISI STATUS
     * =======================================================*/

    public function versiAjukan($id = null)
    {
        if (! $this->versiBoleh('submit')) {
            return $this->versiTolakIzin();
        }

        if ($this->versiMilikSaya((int) $id) === null) {
            return $this->versiTolak('Versi tidak ditemukan pada lingkup Anda.');
        }

        try {
            (new VersionApprovalService())->ajukan((int) $id, $this->penggunaSaatIni());
        } catch (Throwable $e) {
            return redirect()->back()->with('error', pesanGalat($e, 'umum.dokumenVersi'));
        }

        return redirect()->back()->with(
            'success',
            'Versi diajukan untuk ditetapkan. Selama menunggu verifikasi, isinya tidak bisa disunting.'
        );
    }

    /**
     * Tetapkan berlaku.
     *
     * Dokumen tingkat Kabupaten boleh langsung dari draft (§18); dokumen OPD
     * hanya lewat antrean verifikasi, dan itu dijaga permission `publish` yang
     * memang tidak diberikan ke role OPD.
     */
    public function versiTetapkan($id = null)
    {
        if (! $this->versiBoleh('publish')) {
            return $this->versiTolakIzin();
        }

        $baris = $this->versiMilikSaya((int) $id);

        if ($baris === null) {
            return $this->versiTolak('Versi tidak ditemukan pada lingkup Anda.');
        }

        // PENJAGAAN VERSI KOSONG.
        //
        // Menetapkan versi tanpa isi berarti SELURUH baris periode ini
        // dipensiunkan — akibat yang benar menurut §9 ("mulai dari kosong"),
        // tetapi terlalu besar untuk dilewatkan konfirmasi generik. Karena itu
        // dituntut centang tersendiri yang menyebut angkanya.
        $dampak = $this->versiDampakKosong($baris);

        if ($dampak !== null && ! $this->request->getPost('konfirmasi_kosong')) {
            return redirect()->back()->with('error',
                'Versi ini TIDAK BERISI APA PUN. Menetapkannya akan memensiunkan '
                . $dampak . ' pada periode ini. Bila memang itu yang dimaksud, '
                . 'centang kotak konfirmasi lebih dulu. Bila tidak, isi dulu versinya.');
        }

        try {
            (new VersionApprovalService())->setujui((int) $id, $this->penggunaSaatIni(), true);
        } catch (Throwable $e) {
            return redirect()->back()->with('error', pesanGalat($e, 'umum.dokumenVersi'));
        }

        return redirect()->back()->with(
            'success',
            'Versi ditetapkan berlaku. Isinya sudah diterapkan ke data berjalan, '
            . 'dan baris yang tidak lagi tercantum dipensiunkan — bukan dihapus.'
        );
    }

    public function versiBatalkan($id = null)
    {
        if (! $this->versiBoleh('update_draft')) {
            return $this->versiTolakIzin();
        }

        if ($this->versiMilikSaya((int) $id) === null) {
            return $this->versiTolak('Versi tidak ditemukan pada lingkup Anda.');
        }

        try {
            (new VersionApprovalService())->batalkan(
                (int) $id,
                (string) $this->request->getPost('alasan'),
                $this->penggunaSaatIni()
            );
        } catch (Throwable $e) {
            return redirect()->back()->with('error', pesanGalat($e, 'umum.dokumenVersi'));
        }

        return redirect()
            ->to(base_url($this->versiBaseUrl() . '/versi'))
            ->with('success', 'Versi dibatalkan. Barisnya tetap tersimpan sebagai jejak.');
    }


    /* =========================================================
     * HAPUS VERSI
     * =======================================================*/

    /**
     * Bolehkah pengguna ini menghapus versi pada lingkup yang sedang dibuka?
     *
     * =====================================================================
     * HANYA LINGKUP KABUPATEN — DAN INI GAGAL-TERTUTUP, BUKAN KELALAIAN
     *
     * Trait ini dipakai bersama RpjmdController (lingkup kabupaten) dan
     * RenstraController (lingkup OPD). Untuk IKU, penghapusan versi milik OPD
     * ditempuh lewat permohonan izin yang disetujui Admin Kabupaten lebih dulu
     * — versi dokumen BELUM punya alur setara itu.
     *
     * Selama alurnya belum ada, lingkup OPD ditolak di sini. Membiarkannya
     * lewat berarti sebuah OPD bisa memusnahkan versi Renstra-nya sendiri
     * tanpa seorang pun menyetujui, dan itu justru penjagaan yang dibongkar,
     * bukan fitur yang ditambah.
     *
     * =====================================================================
     * MENGAPA `<modul>.delete`, BUKAN `<modul>.version.delete`
     *
     * `rpjmd.version.delete` TIDAK ADA di tabel permissions — tujuh
     * `rpjmd.version.*` yang tersedia berhenti di create/pin/publish/submit/
     * update_draft/verify/view. Menuntut izin yang tidak pernah diberikan
     * kepada siapa pun berarti tombolnya tidak akan pernah muncul, dan
     * penyebabnya tidak terlihat dari layar mana pun.
     *
     * `rpjmd.delete` sudah ada dan sudah dipegang admin_kab. Ini juga pilihan
     * yang sama dengan penghapusan versi IKU Kabupaten, yang memakai
     * `iku_kab.delete` dengan alasan persis sama.
     */
    protected function versiBolehHapus(): bool
    {
        // =============================================================
        // RPJMD (kabupaten) DAN RENSTRA (OPD) SAMA-SAMA BOLEH
        //
        // Semula hanya RPJMD yang diizinkan, sebagai sikap gagal-tertutup:
        // versi Renstra milik OPD tidak boleh dimusnahkan tanpa persetujuan,
        // sementara alur persetujuannya belum ada.
        //
        // Yang membuat pembatasan itu tidak lagi diperlukan adalah aturan
        // STATUS pada alasanTolakHapus(): `published` dan `pending_approval`
        // sudah ditolak mentah-mentah. Yang tersisa hanyalah `draft` dan
        // `cancelled` — keduanya belum menjadi dokumen resmi, dan draft
        // memang milik penyusunnya sendiri. Tidak ada persetujuan yang
        // dilangkahi, karena tidak ada yang pernah menyetujuinya.
        //
        // Lingkupnya tetap dijaga versiMilikSaya(), yang menolak versi milik
        // OPD lain — jadi satu OPD tidak bisa membuang draft OPD lain.
        // =============================================================
        $modul = $this->versiModul();

        if (! in_array($modul, [VersionScope::MODUL_RPJMD, VersionScope::MODUL_RENSTRA], true)) {
            return false;
        }

        // RPJMD tidak berpemilik OPD; sesi yang membawa opd_id pada lingkup
        // kabupaten berarti ada yang salah pasang, dan itu ditolak.
        if ($modul === VersionScope::MODUL_RPJMD && $this->versiOpdId() !== null) {
            return false;
        }

        return function_exists('user_can') && user_can($modul . '.delete');
    }

    /**
     * Keadaan penghapusan sebuah versi, untuk dipakai tampilan DAN aksi.
     *
     * Dihitung satu kali di satu tempat supaya tombol dan penyimpanan tidak
     * pernah berbeda pendapat: kalau aturannya tersalin dua kali, yang terjadi
     * adalah tombol muncul tapi aksinya menolak — atau sebaliknya.
     *
     * @return array{boleh:bool, alasan:string, penghalang:array<string,int>}
     */
    protected function versiKeadaanHapus(array $versi): array
    {
        $kosong = ['boleh' => false, 'alasan' => '', 'penghalang' => []];

        if (! $this->versiBolehHapus()) {
            return array_merge($kosong, [
                'alasan' => $this->versiOpdId() !== null
                    ? 'Penghapusan versi ' . $this->versiNamaDokumen()
                        . ' hanya dapat dilakukan Admin Kabupaten.'
                    : 'Anda tidak berwenang menghapus versi ' . $this->versiNamaDokumen() . '.',
            ]);
        }

        $tolak = $this->versi()->alasanTolakHapus($versi);

        if ($tolak !== null) {
            return array_merge($kosong, ['alasan' => $tolak]);
        }

        $penghalang = $this->versi()->penghalangHapus(
            (int) $versi['id'],
            (string) $versi['modul']
        );

        if ($penghalang !== []) {
            $rinci = [];

            foreach ($penghalang as $apa => $n) {
                $rinci[] = $n . ' ' . $apa;
            }

            return array_merge($kosong, [
                'penghalang' => $penghalang,
                'alasan'     => 'Belum bisa dihapus — masih dirujuk: ' . implode('; ', $rinci) . '.',
            ]);
        }

        return [
            'boleh'      => true,
            'alasan'     => 'Penghapusan versi tidak bisa dibatalkan.',
            'penghalang' => [],
        ];
    }

    /** POST: hapus sebuah versi dokumen beserta arsip isinya. */
    public function versiHapus($id = null)
    {
        if (! $this->versiBolehHapus()) {
            return $this->versiTolakIzin();
        }

        $baris = $this->versiMilikSaya((int) $id);

        if ($baris === null) {
            return $this->versiTolak('Versi tidak ditemukan pada lingkup Anda.');
        }

        $keadaan = $this->versiKeadaanHapus($baris);

        if (! $keadaan['boleh']) {
            return redirect()
                ->to(base_url($this->versiBaseUrl() . '/versi/lihat/' . (int) $id))
                ->with('error', $keadaan['alasan']);
        }

        try {
            $ringkas = $this->versi()->hapusVersi((int) $id);
        } catch (Throwable $e) {
            return redirect()
                ->to(base_url($this->versiBaseUrl() . '/versi/lihat/' . (int) $id))
                ->with('error', pesanGalatBerawalan($e, 'Versi tidak bisa dihapus', 'umum.dokumenVersi'));
        }

        $jejak = (int) $ringkas['arsip'] > 0
            ? ' Beserta ' . (int) $ringkas['arsip'] . ' baris arsip isinya.'
            : '';

        return redirect()
            ->to(base_url($this->versiBaseUrl() . '/versi'))
            ->with('success', 'Versi "' . $ringkas['nama'] . '" dihapus.' . $jejak);
    }

    /* =========================================================
     * BANDINGKAN (§23)
     * =======================================================*/

    public function versiBanding()
    {
        if (! $this->versiBoleh('view')) {
            return $this->versiTolakIzin();
        }

        $aId = (int) ($this->request->getGet('a') ?? 0);
        $bId = (int) ($this->request->getGet('b') ?? 0);

        // Anti-IDOR: keduanya harus milik lingkup pemakai, diperiksa sebelum
        // isinya dibaca sama sekali.
        if ($this->versiMilikSaya($aId) === null || $this->versiMilikSaya($bId) === null) {
            return $this->versiTolak('Salah satu versi tidak ditemukan pada lingkup Anda.');
        }

        try {
            $hasil = (new VersionCompareService())->banding($aId, $bId);
        } catch (Throwable $e) {
            return $this->versiTolak($e->getMessage());
        }

        return view('versi/banding', [
            'title'        => 'Bandingkan Versi',
            'judulHalaman' => 'Bandingkan Versi ' . $this->versiNamaDokumen(),
            'namaDokumen'  => $this->versiNamaDokumen(),
            'baseUrl'      => $this->versiBaseUrl(),
            'hasil'        => $hasil,
        ]);
    }

    /* =========================================================
     * INTERNAL
     * =======================================================*/

    /**
     * Ambil versi HANYA bila ia milik modul & lingkup pemakai.
     *
     * Inilah penjaga anti-IDOR (§13, §55): id versi datang dari URL dan tidak
     * boleh dipercaya. Admin OPD yang menebak id versi OPD lain mendapat null,
     * bukan datanya.
     */

    /* =========================================================
     * TUNJUKAN TAMPILAN UTAMA
     *
     * Menunjuk tidak mengubah satu angka pun: isi versi, tanggal berlakunya,
     * dan tabel berjalan sama sekali tidak tersentuh. Yang berubah hanya versi
     * mana yang ditampilkan lebih dulu di menu dokumennya.
     *
     * Karena itu ia BUKAN bagian dari alur persetujuan, dan sengaja diberi izin
     * tersendiri (`<modul>.version.pin`): ia mengubah apa yang dilihat seluruh
     * pengguna lingkup itu, jadi terlalu besar untuk menumpang `.view`, tetapi
     * bukan penyuntingan sehingga tidak pantas menuntut `.update_draft`.
     * =======================================================*/

    public function versiJadikanUtama($id = null)
    {
        if (! $this->versiBoleh('pin')) {
            return $this->versiTolakIzin();
        }

        $baris = $this->versiMilikSaya((int) $id);

        if ($baris === null) {
            return $this->versiTolak('Versi tidak ditemukan pada lingkup Anda.');
        }

        $scope   = VersionScope::dariBaris($baris);
        $kembali = base_url($this->versiBaseUrl() . '/versi/lihat/' . (int) $id);

        // Versi kosong ditolak di sini, bukan di model: kekosongan adalah soal
        // arsip per-modul, sedangkan model versi tidak tahu isi modul mana pun.
        if ($this->versiArsipKosong($baris)) {
            return redirect()->to($kembali)->with('error',
                'Versi ini belum berisi apa pun, jadi menjadikannya tampilan utama '
                . 'hanya akan menampilkan tabel kosong.');
        }

        try {
            $this->versi()->tetapkanTampilanUtama((int) $id, $scope, $this->penggunaSaatIni());
        } catch (Throwable $e) {
            return redirect()->to($kembali)->with('error', pesanGalat($e, 'umum.dokumenVersi'));
        }

        (new VersionAuditService())->catat((int) $id, 'tampilan_utama', [
            'ringkasan' => 'Dijadikan tampilan utama ' . $this->versiNamaDokumen() . '.',
        ]);

        return redirect()->to($kembali)->with('success',
            'Versi ini kini dipakai sebagai tampilan utama ' . $this->versiNamaDokumen() . '.');
    }

    public function versiLepasUtama($id = null)
    {
        if (! $this->versiBoleh('pin')) {
            return $this->versiTolakIzin();
        }

        $baris = $this->versiMilikSaya((int) $id);

        if ($baris === null) {
            return $this->versiTolak('Versi tidak ditemukan pada lingkup Anda.');
        }

        $this->versi()->lepasTampilanUtama(VersionScope::dariBaris($baris));

        (new VersionAuditService())->catat((int) $id, 'tampilan_utama', [
            'ringkasan' => 'Tunjukan tampilan utama dilepas; tampilan kembali mengikuti kondisi berjalan.',
        ]);

        return redirect()->to(base_url($this->versiBaseUrl() . '/versi/lihat/' . (int) $id))
            ->with('success', 'Tunjukan dilepas. Menu ' . $this->versiNamaDokumen()
                . ' kembali menampilkan kondisi berjalan.');
    }

    /**
     * Bolehkah tanggal berlaku versi RESMI ini digeser.
     *
     * =====================================================================
     * MENGAPA INI BUKAN "MENYUNTING VERSI PUBLISHED"
     *
     * `effective_from` bukan ISI dokumen — ia keterangan KAPAN isi itu mulai
     * dipakai. Menggesernya tidak mengubah satu pun target, sasaran, atau
     * indikator, jadi arsip tetap beku dan snapshot LAKIP tetap cocok dengan
     * apa yang dirujuknya.
     *
     * Yang berubah adalah pertanyaan "versi mana yang berlaku pada tanggal D",
     * dan itu memang keterangan yang bisa salah ketik sebagaimana keterangan
     * lain. Sampai sekarang satu-satunya jalan memperbaikinya adalah
     * menerbitkan versi baru yang isinya sama persis — dokumen tambahan yang
     * tidak menambah informasi apa pun.
     *
     * =====================================================================
     * DUA WEWENANG YANG DITERIMA
     *
     * 1. `.version.publish` — pemilik garis waktu resmi (Kabupaten).
     * 2. Izin sunting yang sedang berlaku pada periode itu — OPD yang sudah
     *    mendapat persetujuan untuk memperbaiki periodenya.
     *
     * Baseline otomatis TIDAK lewat sini; ia punya jalannya sendiri
     * (`bolehPerbaikiTanggalBaseline`) karena tanggalnya memang tebakan
     * pemasangan, bukan keputusan siapa pun.
     */
    protected function versiBolehGeserTanggal(array $baris, VersionScope $scope): bool
    {
        if (($baris['status'] ?? '') !== DokumenVersiModel::STATUS_PUBLISHED) {
            return false;
        }

        if ($this->versiBoleh('publish')) {
            return true;
        }

        return $this->versiBoleh('update_draft')
            && (new IzinSuntingService())->bolehSunting($scope);
    }

    protected function versiMilikSaya(int $id): ?array
    {
        if ($id <= 0 || ! $this->versi()->siap()) {
            return null;
        }

        $baris = $this->versi()->ambil($id);

        if ($baris === null || $baris['modul'] !== $this->versiModul()) {
            return null;
        }

        if ($this->versiModul() !== VersionScope::MODUL_RPJMD
            && (int) $baris['opd_key'] !== (int) $this->versiOpdId()) {
            return null;
        }

        return $baris;
    }

    protected function versiTolakIzin()
    {
        return redirect()->to(base_url('unauthorized'))
            ->with('error', 'Anda tidak memiliki izin untuk aksi versi dokumen tersebut.');
    }

    private function penggunaSaatIni(): ?int
    {
        $id = session()->get('user_id') ?? session()->get('id');

        return $id === null ? null : (int) $id;
    }

    private function kosong($nilai): ?string
    {
        $nilai = trim((string) $nilai);

        return $nilai === '' ? null : $nilai;
    }
}
