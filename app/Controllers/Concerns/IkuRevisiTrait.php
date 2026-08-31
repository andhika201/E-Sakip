<?php

namespace App\Controllers\Concerns;

use App\Models\Opd\IkuRevisiModel;
use App\Services\Version\IzinSuntingService;
use App\Services\Version\VersionScope;
use Throwable;

/**
 * Aksi Revisi IKU, dipakai bersama AdminKab\IkuController dan
 * AdminOpd\IkuController.
 *
 * ---------------------------------------------------------------------
 * ALUR DI LAYAR
 *
 *   Daftar Revisi  -> semua versi satu periode, beserta masa berlakunya
 *   Buat Revisi    -> membuat DRAFT berisi salinan IKU yang berlaku sekarang
 *   Sunting Draft  -> mengubah target, menandai indikator dihentikan/pengganti,
 *                     menambah indikator baru — SEMUANYA di dalam draft
 *   Sahkan         -> draft diterapkan ke IKU berjalan, versi lama jadi arsip
 *   Batalkan       -> draft dibuang (barisnya tetap, statusnya 'batal')
 *   Lihat          -> isi salah satu versi, apa adanya saat versi itu berlaku
 *
 * Selama masih draft, IKU yang dipakai LAKIP dan API publik TIDAK berubah
 * sedikit pun (invariant 1 & 5).
 *
 * ---------------------------------------------------------------------
 * OTORISASI
 *
 * Controller pemakai menyediakan lingkupnya lewat tiga method:
 *
 *   revisiPermPrefix()  'iku_kab' | 'iku_opd'
 *   revisiOpdId()       NULL untuk kabupaten, id OPD untuk OPD/kecamatan
 *   revisiBaseUrl()     'adminkab/iku' | 'adminopd/iku'
 *
 * revisiOpdId() WAJIB mengambil OPD dari SESSION untuk role tingkat OPD, tidak
 * pernah dari request — pola yang sama dengan LakipAddendumTrait::lakipScope().
 */
trait IkuRevisiTrait
{
    protected ?IkuRevisiModel $revisiModel = null;

    private ?IzinSuntingService $izinService = null;

    protected function revisi(): IkuRevisiModel
    {
        return $this->revisiModel ??= new IkuRevisiModel();
    }

    abstract protected function revisiPermPrefix(): string;

    /** NULL = lingkup kabupaten. */
    abstract protected function revisiOpdId(): ?int;

    abstract protected function revisiBaseUrl(): string;

    private function bolehRevisi(): bool
    {
        return function_exists('user_can') && user_can($this->revisiPermPrefix() . '.revisi');
    }

    private function bolehSahkanRevisi(): bool
    {
        return function_exists('user_can') && user_can($this->revisiPermPrefix() . '.revisi_sahkan');
    }

    private function urlRevisi(string $tambahan = ''): string
    {
        return base_url($this->revisiBaseUrl() . '/revisi' . $tambahan);
    }

    /**
     * Pastikan sebuah revisi memang milik lingkup pengguna ini.
     * Pencegah IDOR: id revisi dari URL tidak pernah dipercaya.
     */
    /* =========================================================
     * IZIN SUNTING REVISI YANG SUDAH BERLAKU
     *
     * Alurnya sengaja SAMA PERSIS dengan Renstra, dan memakai mesin yang sama
     * (`dokumen_izin_sunting` + IzinSuntingService) — tabelnya memang sudah
     * generik lewat kolom `modul`, dan VersionScope sudah mengenal MODUL_IKU.
     * Tidak ada tabel, service, atau antrean baru; yang ditambahkan hanya
     * pemetaan dari sebuah revisi IKU ke lingkupnya.
     *
     * MENGAPA REVISI BERLAKU TERKUNCI
     *
     * Revisi yang sudah disahkan adalah arsip: isinya sudah diterapkan ke IKU
     * berjalan, dan LAKIP tahun berjalan menilai dengan angka itu. Membukanya
     * tanpa jejak berarti angka penilaian bisa berubah setelah dinilai.
     * Karena itu kuncinya hanya boleh dibuka lewat keputusan tercatat —
     * siapa meminta, alasannya apa, siapa yang menyetujui.
     *
     * ARSIP (`superseded`) IKUT BISA DIPERBAIKI
     *
     * Ia sempat dikunci permanen dengan alasan "perbaikannya di revisi yang
     * berlaku". Itu keliru: arsip-lah yang dibaca LAKIP tahun-tahun lampau,
     * sehingga salah ketik di sana tetap tercetak pada laporan tahun itu dan
     * membetulkannya di revisi terkini TIDAK mengubah apa pun.
     *
     * Yang membedakannya dari revisi berlaku bukan boleh-tidaknya disunting,
     * melainkan apa yang terjadi sesudahnya: hasil suntingan revisi berlaku
     * diterapkan ke IKU berjalan, sedangkan arsip berhenti di arsip.
     *
     * Laporan LAKIP yang sudah difinalkan tetap tidak ikut berubah — angkanya
     * sudah disalin ke snapshot, bukan dibaca ulang dari sini.
     * =======================================================*/

    /** Lingkup versi untuk satu revisi IKU; null bila revisinya tidak sah. */
    private function revisiScope(?array $revisi): ?VersionScope
    {
        if ($revisi === null) {
            return null;
        }

        $opdId = $revisi['opd_id'] === null ? null : (int) $revisi['opd_id'];

        try {
            return new VersionScope(
                VersionScope::MODUL_IKU,
                $opdId === null ? VersionScope::SCOPE_KABUPATEN : VersionScope::SCOPE_OPD,
                $opdId,
                (int) $revisi['tahun_mulai'],
                (int) $revisi['tahun_akhir']
            );
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function izin(): IzinSuntingService
    {
        return $this->izinService ??= new IzinSuntingService();
    }

    private function bolehMintaIzin(): bool
    {
        return function_exists('user_can') && user_can('iku.izin_sunting.request');
    }

    /**
     * Keadaan kunci satu revisi: terkunci atau tidak, dan apa langkah
     * berikutnya yang masuk akal.
     *
     * @return array{terkunci:bool, izin:?array, boleh_minta:bool,
     *               boleh_tarik:bool, sedang_disunting:bool, alasan:?string}
     */
    private function revisiKeadaanIzin(?array $revisi): array
    {
        $kosong = [
            'terkunci'         => false,
            'izin'             => null,
            'boleh_minta'      => false,
            'boleh_tarik'      => false,
            'sedang_disunting' => false,
            'alasan'           => null,
        ];

        if ($revisi === null) {
            return $kosong;
        }

        $status = (string) $revisi['status'];

        // Draft & menunggu punya jalurnya sendiri (sunting langsung / tarik
        // pengajuan); izin sunting tidak berlaku di sana.
        if ($status !== IkuRevisiModel::STATUS_BERLAKU
            && $status !== IkuRevisiModel::STATUS_SUPERSEDED) {
            return $kosong;
        }

        // =============================================================
        // ARSIP TIDAK LAGI TERKUNCI PERMANEN
        //
        // Dulu 'superseded' berhenti di sini dengan alasan "perbaikan
        // dilakukan pada revisi yang sedang berlaku". Itu keliru: arsip-lah
        // yang dibaca LAKIP tahun-tahun lampau. Salah ketik pada versi yang
        // sudah digantikan tetap tercetak di laporan tahun itu, dan
        // membetulkannya di revisi terkini TIDAK mengubah apa pun di sana.
        //
        // Yang tetap berbeda dari revisi berlaku: hasil suntingan arsip
        // BERHENTI di arsip — ia tidak pernah diterapkan ke IKU berjalan.
        // Penanda `arsip` di bawah yang membedakannya.
        // =============================================================
        $arsip = $status === IkuRevisiModel::STATUS_SUPERSEDED;

        $scope = $this->revisiScope($revisi);

        if ($scope === null || ! $this->izin()->siap()) {
            return array_merge($kosong, [
                'terkunci' => true,
                'alasan'   => 'Revisi yang sudah berlaku adalah arsip dan tidak bisa disunting.',
            ]);
        }

        $izin = $this->izin()->berjalan($scope);

        // Izin melekat pada REVISI tertentu, bukan sekadar pada periodenya.
        // Tanpa pemeriksaan ini, satu izin untuk revisi 2 ikut membuka revisi
        // 1 yang kebetulan seperiode — dua dokumen berbeda, satu keputusan.
        $untukRevisiIni = $izin !== null
            && (int) ($izin['version_id'] ?? 0) === (int) $revisi['id'];

        if ($untukRevisiIni && $izin['status'] === IzinSuntingService::STATUS_DISETUJUI) {
            return array_merge($kosong, [
                'terkunci'         => false,
                'izin'             => $izin,
                'sedang_disunting' => true,
                // Dipakai layar untuk memilih kalimat DAN tombolnya: arsip
                // tidak punya langkah "terapkan ke IKU berjalan".
                'arsip'            => $arsip,
                'alasan'           => $arsip
                    ? 'Izin perbaikan arsip sudah disetujui Admin Kabupaten. Perbaikan '
                      . 'tersimpan pada arsip versi ini dan mengubah bacaan LAKIP '
                      . 'tahun-tahun yang dipayunginya. IKU berjalan TIDAK ikut berubah.'
                    : 'Izin sunting sudah disetujui Admin Kabupaten. Perbaiki '
                      . 'seperlunya, lalu sahkan ulang agar IKU berjalan ikut diperbarui.',
            ]);
        }

        if ($untukRevisiIni && $izin['status'] === IzinSuntingService::STATUS_PENDING) {
            return array_merge($kosong, [
                'terkunci'    => true,
                'izin'        => $izin,
                'boleh_tarik' => $this->bolehMintaIzin(),
                'alasan'      => 'Permohonan izin sunting sedang menunggu keputusan Admin '
                    . 'Kabupaten. Selama itu revisi ini masih terkunci.',
            ]);
        }

        return array_merge($kosong, [
            'terkunci'    => true,
            'izin'        => $izin !== null && ! $untukRevisiIni ? null : $izin,
            'boleh_minta' => $this->bolehMintaIzin() && $izin === null,
            'arsip'       => $arsip,
            'alasan'      => $izin !== null && ! $untukRevisiIni
                ? 'Sudah ada permohonan izin sunting berjalan untuk periode ini pada revisi '
                  . 'yang lain. Selesaikan dulu permohonan itu.'
                : ($arsip
                ? 'Versi ini sudah digantikan versi yang lebih baru, sehingga terkunci. '
                  . 'Isinya tetap dibaca LAKIP tahun-tahun yang dipayunginya — ajukan izin '
                  . 'bila ada yang perlu dibetulkan di sana.'
                : 'Revisi ini sudah berlaku, sehingga terkunci. Ajukan izin sunting bila ada '
                  . 'yang perlu diperbaiki; setelah disetujui Admin Kabupaten, revisi ini bisa '
                  . 'disunting seperti biasa.'),
        ]);
    }

    /** Bolehkah isi revisi ini diubah sekarang. */
    private function revisiBolehDisunting(array $revisi): bool
    {
        if ($revisi['status'] === IkuRevisiModel::STATUS_DRAFT) {
            return true;
        }

        return ! empty($this->revisiKeadaanIzin($revisi)['sedang_disunting']);
    }

    /** POST: OPD memohon kunci revisi berlaku dibuka. */
    public function revisiMintaIzin($id = null)
    {
        if (! $this->bolehMintaIzin()) {
            return redirect()->to($this->urlRevisi())
                ->with('error', 'Anda tidak berwenang mengajukan izin sunting IKU.');
        }

        $revisi = $this->revisi()->ambil((int) $id);

        if (! $this->revisiMilikLingkup($revisi)) {
            return redirect()->to($this->urlRevisi())->with('error', 'Revisi tidak ditemukan.');
        }

        $keadaan = $this->revisiKeadaanIzin($revisi);

        // Memohon izin atas revisi yang memang sudah bisa disunting hanya
        // melahirkan permohonan yang tak pernah dipakai, dan mengotori antrean
        // Admin Kabupaten.
        if (empty($keadaan['boleh_minta'])) {
            return redirect()->to($this->urlRevisi('/lihat/' . (int) $id))
                ->with('error', $keadaan['alasan']
                    ?? 'Revisi ini tidak dalam keadaan memerlukan izin sunting.');
        }

        $alasan = trim((string) $this->request->getPost('alasan'));

        try {
            $this->izin()->ajukan(
                $this->revisiScope($revisi),
                $alasan,
                $this->penggunaRevisi(),
                (int) $revisi['id']
            );
        } catch (Throwable $e) {
            return redirect()->to($this->urlRevisi('/lihat/' . (int) $id))->with('error', $e->getMessage());
        }

        return redirect()->to($this->urlRevisi('/lihat/' . (int) $id))->with('success',
            'Permohonan izin sunting dikirim. Setelah disetujui Admin Kabupaten, revisi ini '
            . 'bisa disunting seperti biasa.');
    }

    /**
     * Keadaan permohonan HAPUS pada sebuah revisi.
     *
     * Dipisah dari revisiKeadaanIzin() dengan sengaja: yang itu menjawab
     * "boleh disunting atau tidak", yang ini "boleh dihapus atau tidak".
     * Menggabungkannya berarti satu keputusan menjawab dua pertanyaan.
     *
     * @return array{boleh_minta:bool, permohonan:?array, penghalang:array, alasan:?string}
     */
    private function revisiKeadaanHapus(?array $revisi): array
    {
        $kosong = ['boleh_minta' => false, 'permohonan' => null, 'penghalang' => [], 'alasan' => null];

        if ($revisi === null || ! $this->izin()->siap()) {
            return $kosong;
        }

        // Draft milik penyusunnya sendiri dan belum pernah mengikat siapa pun;
        // membuangnya sudah dilayani tombol "Batalkan" tanpa perlu izin.
        if ($revisi['status'] === IkuRevisiModel::STATUS_DRAFT) {
            return array_merge($kosong, [
                'alasan' => 'Draft dibatalkan lewat tombol Batalkan — tidak perlu izin Admin Kabupaten.',
            ]);
        }

        if ($revisi['status'] === IkuRevisiModel::STATUS_MENUNGGU) {
            return array_merge($kosong, [
                'alasan' => 'Revisi ini sedang menunggu keputusan Admin Kabupaten. '
                    . 'Tarik pengajuannya dulu.',
            ]);
        }

        $scope = $this->revisiScope($revisi);

        if ($scope === null) {
            return $kosong;
        }

        $hapus = $this->izin()->berjalan($scope, IzinSuntingService::JENIS_HAPUS);

        $untukRevisiIni = $hapus !== null
            && (int) ($hapus['version_id'] ?? 0) === (int) $revisi['id'];

        if ($untukRevisiIni) {
            return array_merge($kosong, [
                'permohonan' => $hapus,
                // Yang boleh menarik adalah pihak yang boleh mengajukan —
                // bukan disandarkan pada kewenangan lain yang kebetulan ada
                // di layar yang sama.
                'boleh_tarik' => $this->bolehMintaIzin()
                    && $hapus['status'] === IzinSuntingService::STATUS_PENDING,
                'alasan'      => 'Permohonan penghapusan sedang menunggu keputusan Admin Kabupaten.',
            ]);
        }

        // Penghalang dihitung SEKARANG, bukan nanti saat disetujui: memohon
        // penghapusan yang sudah pasti ditolak hanya membuang waktu dua orang.
        $penghalang = $this->revisi()->penghalangHapus((int) $revisi['id']);

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

        // Permohonan lain (mis. izin sunting) yang masih menggantung menutup
        // pintu ini: satu dokumen, satu keputusan pada satu waktu.
        $lain = $this->izin()->berjalan($scope, null);

        if ($lain !== null) {
            return array_merge($kosong, [
                'alasan' => 'Masih ada permohonan lain yang berjalan pada periode ini. '
                    . 'Selesaikan dulu permohonan itu.',
            ]);
        }

        return array_merge($kosong, [
            'boleh_minta' => $this->bolehMintaIzin(),
            'alasan'      => 'Penghapusan versi tidak bisa dibatalkan, jadi harus disetujui '
                . 'Admin Kabupaten lebih dulu.',
        ]);
    }

    /** POST: OPD memohon sebuah versi IKU dihapus. */
    public function revisiMintaHapus($id = null)
    {
        if (! $this->bolehMintaIzin()) {
            return redirect()->to($this->urlRevisi())
                ->with('error', 'Anda tidak berwenang mengajukan penghapusan versi IKU.');
        }

        $revisi = $this->revisi()->ambil((int) $id);

        if (! $this->revisiMilikLingkup($revisi)) {
            return redirect()->to($this->urlRevisi())->with('error', 'Revisi tidak ditemukan.');
        }

        $kembali = $this->urlRevisi('/lihat/' . (int) $id);
        $keadaan = $this->revisiKeadaanHapus($revisi);

        if (empty($keadaan['boleh_minta'])) {
            return redirect()->to($kembali)->with('error',
                $keadaan['alasan'] ?? 'Versi ini tidak dalam keadaan bisa dimohonkan penghapusan.');
        }

        try {
            $this->izin()->ajukan(
                $this->revisiScope($revisi),
                trim((string) $this->request->getPost('alasan')),
                $this->penggunaRevisi(),
                (int) $revisi['id'],
                IzinSuntingService::JENIS_HAPUS
            );
        } catch (Throwable $e) {
            return redirect()->to($kembali)->with('error', $e->getMessage());
        }

        return redirect()->to($kembali)->with('success',
            'Permohonan penghapusan dikirim. Versi ini baru akan dihapus setelah '
            . 'disetujui Admin Kabupaten.');
    }

    /** POST: menarik kembali permohonan yang belum diputus. */
    public function revisiTarikIzin($id = null)
    {
        if (! $this->bolehMintaIzin()) {
            return redirect()->to($this->urlRevisi())
                ->with('error', 'Anda tidak berwenang menarik permohonan izin sunting.');
        }

        $izin = $this->izin()->ambil((int) $id);

        // Lingkup diperiksa dari SESI, bukan dari permintaan: tanpa ini, id
        // permohonan OPD lain bisa ditarik hanya dengan mengarang angkanya.
        $opdSesi = $this->revisiOpdId();

        if ($izin === null
            || $izin['modul'] !== VersionScope::MODUL_IKU
            || (int) $izin['opd_key'] !== (int) $opdSesi) {
            return redirect()->to($this->urlRevisi())
                ->with('error', 'Permohonan tidak ditemukan pada lingkup Anda.');
        }

        $kembali = $this->urlRevisi('/lihat/' . (int) ($izin['version_id'] ?? 0));

        try {
            $this->izin()->tarik((int) $id, $this->penggunaRevisi());
        } catch (Throwable $e) {
            return redirect()->to($kembali)->with('error', $e->getMessage());
        }

        return redirect()->to($kembali)->with('success', 'Permohonan izin sunting ditarik.');
    }

    /** POST: terapkan hasil penyuntingan ke IKU berjalan, lalu tutup izinnya. */
    public function revisiSelesaikanIzin($id = null)
    {
        if (! $this->bolehMintaIzin()) {
            return redirect()->to($this->urlRevisi())
                ->with('error', 'Anda tidak berwenang menyelesaikan izin sunting.');
        }

        $revisi = $this->revisi()->ambil((int) $id);

        if (! $this->revisiMilikLingkup($revisi)) {
            return redirect()->to($this->urlRevisi())->with('error', 'Revisi tidak ditemukan.');
        }

        $kembali = $this->urlRevisi('/lihat/' . (int) $id);

        if (empty($this->revisiKeadaanIzin($revisi)['sedang_disunting'])) {
            return redirect()->to($kembali)
                ->with('error', 'Tidak ada izin sunting berjalan untuk revisi ini.');
        }

        // ARSIP BERHENTI DI ARSIP. Menerapkan isi versi yang sudah digantikan
        // ke IKU berjalan berarti memundurkan dokumen yang sedang dipakai ke
        // keadaan lama — persis kebalikan dari yang dikehendaki. Yang berubah
        // dari perbaikan arsip hanyalah bacaan LAKIP tahun-tahun yang
        // dipayunginya, dan itu memang sudah terjadi saat disimpan.
        $arsip = $revisi['status'] === IkuRevisiModel::STATUS_SUPERSEDED;

        try {
            // Urutannya penting: terapkan DULU, tutup izin belakangan. Kalau
            // terbalik dan penerapan gagal, izinnya sudah tertutup sementara
            // arsip dan IKU berjalan berbeda isi — dan tidak ada lagi jalan
            // membetulkannya tanpa memohon izin baru.
            if (! $arsip) {
                $this->revisi()->terapkanUlang((int) $id);
            }

            $this->izin()->selesaikan($this->revisiScope($revisi));
        } catch (Throwable $e) {
            return redirect()->to($kembali)->with('error', $e->getMessage());
        }

        return redirect()->to($kembali)->with('success', $arsip
            ? 'Perbaikan arsip disimpan dan izin ditutup. IKU berjalan TIDAK berubah — '
              . 'yang menyesuaikan hanyalah bacaan LAKIP tahun-tahun yang dipayungi versi ini. '
              . 'Laporan LAKIP yang sudah difinalkan tetap memakai angka snapshot-nya.'
            : 'Perbaikan diterapkan ke IKU berjalan dan izin sunting ditutup. Laporan LAKIP '
              . 'yang sudah difinalkan tidak ikut berubah.');
    }

    private function penggunaRevisi(): ?int
    {
        $id = session()->get('user_id') ?? session()->get('id');

        return $id === null ? null : (int) $id;
    }

    private function revisiMilikLingkup(?array $revisi): bool
    {
        if (! $revisi) {
            return false;
        }

        $opd = $this->revisiOpdId();

        return (int) $revisi['opd_key'] === ($opd === null ? 0 : $opd);
    }

    /* =========================================================
     * DAFTAR
     * =======================================================*/

    public function revisiIndex()
    {
        if (! user_can($this->revisiPermPrefix() . '.view')) {
            return redirect()->to(base_url($this->revisiBaseUrl()))
                ->with('error', 'Anda tidak memiliki akses ke Revisi IKU.');
        }

        $model = $this->revisi();

        if (! $model->siap()) {
            return redirect()->to(base_url($this->revisiBaseUrl()))->with(
                'error',
                'Modul Revisi IKU belum aktif di basis data ini. Jalankan '
                . 'db/update_2026-08-18_iku_revisi_lakip_snapshot.sql lebih dulu.'
            );
        }

        $opdId  = $this->revisiOpdId();
        $daftar = $model->daftar($opdId);

        // Peringatan konflik ditampilkan apa adanya, tidak diselesaikan
        // diam-diam (invariant 2 / Case 11).
        $konflik = [];
        foreach ($this->tahunPeriode($daftar) as $tahun) {
            $hasil = $model->resolveEfektif($opdId, $tahun);

            if (! empty($hasil['konflik'])) {
                $konflik[$tahun] = $hasil['pesan'];
            }
        }

        // =============================================================
        // KEADAAN IZIN IKUT KE DAFTAR
        //
        // Panel "Ajukan Izin Sunting" selama ini HANYA ada di halaman detail.
        // Dari daftar ini, baris berstatus 'berlaku' tidak menawarkan apa pun
        // selain "Lihat" — sehingga jalan untuk meminta izin tidak pernah
        // terlihat, dan tampak seolah menu ini memang tidak menyediakannya.
        //
        // Dihitung per baris di sini, bukan di view: view tidak boleh
        // memanggil service, dan aturan "siapa boleh meminta" harus tinggal
        // di satu tempat yang sama dengan halaman detail.
        //
        // Jumlah revisi per lingkup hanya segelintir (satu periode, beberapa
        // nomor), jadi perulangan ini tidak menimbulkan beban berarti.
        // =============================================================
        foreach ($daftar as &$baris) {
            $baris['izin_keadaan']  = $this->revisiKeadaanIzin($baris);
            $baris['hapus_keadaan'] = $this->revisiKeadaanHapus($baris);
        }
        unset($baris);

        return view('iku/revisi_index', [
            'title'          => 'Revisi IKU',
            'role'           => session()->get('role'),
            'daftar'         => $daftar,
            'periodeOpsi'    => $this->ikuModel->getPeriodeOptions($opdId === null ? 'kabupaten' : 'opd', $opdId),
            'konflik'        => $konflik,
            'bolehRevisi'    => $this->bolehRevisi(),
            'bolehSahkan'    => $this->bolehSahkanRevisi(),
            'perluVerifikasi' => $this->revisiPerluVerifikasi(),
            'baseUrl'        => $this->revisiBaseUrl(),
            'tahunSekarang'  => (int) date('Y'),
        ]);
    }

    /** Tahun-tahun unik yang dicakup daftar revisi, untuk pemeriksaan konflik. */
    private function tahunPeriode(array $daftar): array
    {
        $tahun = [];

        foreach ($daftar as $r) {
            for ($t = (int) $r['tahun_mulai']; $t <= (int) $r['tahun_akhir']; $t++) {
                $tahun[$t] = true;
            }
        }

        return array_keys($tahun);
    }

    /* =========================================================
     * BUAT DRAFT
     * =======================================================*/

    public function revisiBuat()
    {
        if (! $this->bolehRevisi()) {
            return redirect()->to($this->urlRevisi())
                ->with('error', 'Anda tidak berwenang membuat revisi IKU.');
        }

        $opdId  = $this->revisiOpdId();
        $opsi   = $this->ikuModel->getPeriodeOptions($opdId === null ? 'kabupaten' : 'opd', $opdId);

        // =============================================================
        // TAHUN YANG SUDAH TERPAKAI IKUT DIKIRIM KE LAYAR
        //
        // Model sudah lama menyediakan tahunBerlakuTerpakai() justru untuk
        // ini — "menyodorkan tahun yang masih kosong, dan menyebut revisi mana
        // yang menghalangi" — tetapi layarnya tidak pernah memanggilnya.
        // Akibatnya daftar tahun tampak bebas seluruhnya, pemakai memilih
        // tahun yang ternyata sudah dipakai, dan baru ditolak SESUDAH seluruh
        // form diisi.
        //
        // Tahun PERTAMA periode selalu jatah "Kondisi Awal" dan tidak pernah
        // bisa dipakai revisi bernomor; itu pun ditandai di layar, bukan
        // dibiarkan jadi pilihan yang pasti gagal.
        // =============================================================
        foreach ($opsi as $kunci => $p) {
            $tm = (int) $p['tahun_mulai'];
            $ta = (int) $p['tahun_akhir'];

            $opsi[$kunci]['terpakai'] = $this->revisi()->tahunBerlakuTerpakai($opdId, $tm, $ta);
            $opsi[$kunci]['bebas']    = $this->revisi()->tahunBerlakuBebas($opdId, $tm, $ta);
        }

        // =============================================================
        // PILIHAN SYNC LANGSUNG DI FORM PEMBUATAN
        //
        // Alur lamanya memutar: buka layar Sync -> ditolak "belum ada draft
        // yang bisa menampungnya" -> buat revisi -> kembali ke Sync -> pilih
        // draft tujuan. Empat langkah untuk satu niat, dan langkah pertamanya
        // adalah penolakan.
        //
        // Versi Renstra disodorkan per periode, karena periodenya baru
        // dipilih di layar (JS yang menyaringnya). Daftarnya hanya versi
        // `published` — arsip yang memang boleh jadi sumber.
        //
        // Lingkup KABUPATEN tidak punya Renstra, jadi tidak ada yang
        // ditawarkan di sana; layar menyembunyikan bloknya sendiri.
        // =============================================================
        $versiRenstra = [];

        if ($opdId !== null) {
            foreach ($opsi as $kunci => $p) {
                $versiRenstra[$kunci] = $this->ikuModel->versiRenstraTersedia(
                    $opdId, (int) $p['tahun_mulai'], (int) $p['tahun_akhir']
                );
            }
        }

        return view('iku/revisi_form', [
            'title'        => 'Buat Revisi IKU',
            'role'         => session()->get('role'),
            'periodeOpsi'  => $opsi,
            'versiRenstra' => $versiRenstra,
            'baseUrl'      => $this->revisiBaseUrl(),
        ]);
    }

    public function revisiSimpan()
    {
        if (! $this->bolehRevisi()) {
            return redirect()->to($this->urlRevisi())
                ->with('error', 'Anda tidak berwenang membuat revisi IKU.');
        }

        $post = $this->request->getPost();

        $error = $this->safeTextError([
            'Nama revisi'    => $post['nama'] ?? null,
            'Dasar hukum'    => $post['dasar_hukum'] ?? null,
            'Nomor dasar'    => $post['nomor_dasar'] ?? null,
            'Catatan'        => $post['catatan'] ?? null,
        ]);

        if ($error) {
            return redirect()->back()->withInput()->with('error', $error);
        }

        // Periode dikirim sebagai "2025-2029" — bentuk yang sama dengan
        // getPeriodeOptions().
        $periode = explode('-', (string) ($post['periode'] ?? ''));

        if (count($periode) !== 2) {
            return redirect()->back()->withInput()->with('error', 'Periode IKU belum dipilih.');
        }

        try {
            $id = $this->revisi()->buatDraft([
                'opd_id'              => $this->revisiOpdId(),
                'tahun_mulai'         => (int) $periode[0],
                'tahun_akhir'         => (int) $periode[1],
                'nama'                => (string) ($post['nama'] ?? ''),
                'dasar_hukum'         => $post['dasar_hukum'] ?? null,
                'nomor_dasar'         => $post['nomor_dasar'] ?? null,
                'tanggal_dasar'       => $post['tanggal_dasar'] ?? null,
                'berlaku_mulai_tahun' => (int) ($post['berlaku_mulai_tahun'] ?? 0),
                'catatan'             => $post['catatan'] ?? null,
                'dibuat_oleh'         => (int) session()->get('user_id') ?: null,
            ]);

            $pesan = 'Draft revisi dibuat berisi salinan IKU yang berlaku sekarang. '
                . 'IKU berjalan BELUM berubah — silakan sunting lalu sahkan.';

            // Sync menyusul DI DALAM permintaan yang sama bila diminta, supaya
            // pemakai tidak perlu berpindah layar hanya untuk melakukan hal
            // yang sudah ia nyatakan niatnya di sini.
            //
            // Kegagalannya SENGAJA tidak membatalkan draft: draft-nya sendiri
            // sudah sah dan berguna. Yang gagal hanya penyalinannya, dan itu
            // dikatakan apa adanya supaya pemakai tahu harus mengulang lewat
            // menu Sync — bukan mengira isinya sudah masuk.
            if ($this->request->getPost('sync_renstra')) {
                try {
                    $stat   = $this->syncRenstraKeDraft($id, (int) $periode[0], (int) $periode[1]);
                    $pesan .= ' ' . $this->pesanHasilSync($stat);
                } catch (Throwable $e) {
                    return redirect()->to($this->urlRevisi('/sunting/' . $id))
                        ->with('success', $pesan)
                        ->with('warning', 'Draft dibuat, TETAPI penyalinan dari Renstra gagal: '
                            . $e->getMessage() . ' Ulangi lewat menu Sync dari Renstra.');
                }
            }

            return redirect()->to($this->urlRevisi('/sunting/' . $id))->with('success', $pesan);
        } catch (Throwable $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    /**
     * Salin isi Renstra ke sebuah draft revisi yang baru saja dibuat.
     *
     * Memakai mesin yang SAMA dengan layar Sync — `getKandidatSync()` untuk
     * membandingkan, `keranjangSyncPenuh()` untuk memilah baru/berubah, dan
     * `imporKandidat()` untuk menuliskannya. Menyalin logikanya ke sini akan
     * melahirkan dua aturan sync yang diam-diam bisa menyimpang.
     *
     * Versi Renstra sumber DIVALIDASI terhadap daftar yang sah, bukan
     * dipercaya dari form: id karangan tidak boleh membuka arsip OPD lain.
     *
     * @return array<string,int> statistik untuk pesan hasil
     */
    private function syncRenstraKeDraft(int $draftId, int $tahunMulai, int $tahunAkhir): array
    {
        $opdId = $this->revisiOpdId();

        if ($opdId === null) {
            throw new \RuntimeException(
                'Lingkup kabupaten tidak bersumber dari Renstra, jadi tidak ada yang bisa disalin.'
            );
        }

        $tersedia = $this->ikuModel->versiRenstraTersedia($opdId, $tahunMulai, $tahunAkhir);

        if ($tersedia === []) {
            throw new \RuntimeException(
                'Belum ada versi Renstra yang ditetapkan pada periode ' . $tahunMulai . '-' . $tahunAkhir . '.'
            );
        }

        $diminta = (int) ($this->request->getPost('renstra_versi') ?? 0);
        $versi   = null;

        foreach ($tersedia as $v) {
            if ((int) $v['id'] === $diminta) {
                $versi = $v;
            }
        }

        // Tanpa pilihan yang sah, dipakai versi TERBARU — daftarnya sudah
        // diurutkan version_no DESC. Menolak di sini hanya memaksa pemakai
        // mengulang form demi memilih yang toh jadi bawaannya.
        $versi ??= $tersedia[0];

        $kandidat = $this->ikuModel->getKandidatSync(
            'renstra', $opdId, $tahunMulai, $tahunAkhir, (int) $versi['id']
        );

        [$pilihan, $perbarui] = $this->keranjangSyncPenuh($kandidat);

        return $this->revisi()->imporKandidat(
            $draftId, $kandidat, $pilihan, 'renstra', (int) $versi['id'], $perbarui
        );
    }

    /* =========================================================
     * LIHAT & SUNTING
     * =======================================================*/

    public function revisiLihat($id = null)
    {
        if (! user_can($this->revisiPermPrefix() . '.view')) {
            return redirect()->to(base_url($this->revisiBaseUrl()))->with('error', 'Akses ditolak.');
        }

        $revisi = $this->revisi()->ambil((int) $id);

        if (! $this->revisiMilikLingkup($revisi)) {
            return redirect()->to($this->urlRevisi())->with('error', 'Revisi tidak ditemukan.');
        }

        return view('iku/revisi_lihat', [
            'title'   => $revisi['nama'],
            'role'    => session()->get('role'),
            'revisi'  => $revisi,
            // Nisan 'dihentikan' ikut ditampilkan di sini supaya terbaca APA
            // yang berubah pada revisi ini — berbeda dari isi yang dicetak.
            'isi'     => $this->revisi()->isiRevisi((int) $id, true),
            'years'   => range((int) $revisi['tahun_mulai'], (int) $revisi['tahun_akhir']),
            'baseUrl' => $this->revisiBaseUrl(),
            'keadaanIzin'    => $this->revisiKeadaanIzin($revisi),
            'keadaanHapus'   => $this->revisiKeadaanHapus($revisi),
            // Tahun berlaku bisa diubah pada revisi mana pun yang masih hidup
            // — draft maupun yang sedang berlaku, Kondisi Awal termasuk.
            //
            // SENGAJA tidak memakai revisiBolehDisunting(): izin sunting
            // menjaga ISI arsip, dan tahun berlaku bukan isi. Ia keterangan
            // "dokumen ini menilai tahun yang mana" — pernyataan administratif
            // yang justru sering perlu dibetulkan tanpa membongkar arsipnya.
            // Salah tahun tetap tertahan di satu tempat: pemeriksaan bentrok
            // saat disimpan.
            'bolehUbahBerlaku' => $this->bolehRevisi()
                && in_array($revisi['status'], [
                    IkuRevisiModel::STATUS_DRAFT,
                    IkuRevisiModel::STATUS_BERLAKU,
                    // Versi lama tetap dokumen resmi bagi tahun yang
                    // dipayunginya; masa berlakunya yang telanjur salah harus
                    // bisa dibetulkan. Lihat ubahTahunBerlaku().
                    IkuRevisiModel::STATUS_SUPERSEDED,
                ], true),

            // Tahun yang masih kosong pada lingkup ini + siapa memakai yang
            // lain. Dropdown hanya menawarkan yang bebas, jadi bentrok
            // dicegah sebelum tombol ditekan — bukan ditolak sesudahnya.
            'tahunBebas' => $this->revisi()->tahunBerlakuBebas(
                $revisi['opd_id'] !== null ? (int) $revisi['opd_id'] : null,
                (int) $revisi['tahun_mulai'],
                (int) $revisi['tahun_akhir'],
                (int) $revisi['id']
            ),
            'tahunTerpakai' => $this->revisi()->tahunBerlakuTerpakai(
                $revisi['opd_id'] !== null ? (int) $revisi['opd_id'] : null,
                (int) $revisi['tahun_mulai'],
                (int) $revisi['tahun_akhir'],
                (int) $revisi['id']
            ),
        ]);
    }

    public function revisiSunting($id = null)
    {
        if (! $this->bolehRevisi()) {
            return redirect()->to($this->urlRevisi())->with('error', 'Anda tidak berwenang menyunting revisi.');
        }

        $revisi = $this->revisi()->ambil((int) $id);

        if (! $this->revisiMilikLingkup($revisi)) {
            return redirect()->to($this->urlRevisi())->with('error', 'Revisi tidak ditemukan.');
        }

        $keadaanIzin = $this->revisiKeadaanIzin($revisi);

        if (! $this->revisiBolehDisunting($revisi)) {
            return redirect()->to($this->urlRevisi('/lihat/' . (int) $id))->with(
                'error',
                $keadaanIzin['alasan']
                    ?? 'Hanya draft yang bisa disunting. Revisi yang sudah berlaku adalah arsip '
                       . 'dan tidak boleh diubah.'
            );
        }

        return view('iku/revisi_sunting', [
            'title'          => 'Sunting ' . $revisi['nama'],
            'role'           => session()->get('role'),
            'revisi'         => $revisi,
            'isi'            => $this->revisi()->isiRevisi((int) $id, true),
            'years'          => range((int) $revisi['tahun_mulai'], (int) $revisi['tahun_akhir']),
            'satuan_options' => $this->ikuModel->getSatuanOptions(),
            'indikatorLive'  => $this->indikatorLiveUntukLineage($revisi),
            // Untuk kartu "Sasaran baru": sasaran mandiri WAJIB menyebut
            // tujuan Renstra penaungnya. Lingkup kabupaten tidak punya Renstra
            // sehingga daftarnya kosong — dan kartu sasaran baru pun tidak
            // ditawarkan di sana.
            'tujuanOptions'  => $this->tujuanUntukSasaranBaru($revisi),
            'bolehSahkan'    => $this->bolehSahkanRevisi(),
            'perluVerifikasi' => $this->revisiPerluVerifikasi(),
            'baseUrl'        => $this->revisiBaseUrl(),
            'keadaanIzin'    => $keadaanIzin,
        ]);
    }

    /**
     * Tujuan Renstra penaung sasaran mandiri yang lahir di dalam revisi ini.
     *
     * Kosong untuk lingkup KABUPATEN: IKU Kabupaten bersandar pada RPJMD, dan
     * `tujuanRenstraOpd()` mencapai kepemilikan lewat `renstra_sasaran.opd_id`
     * yang di sana memang tidak ada. Layar membaca daftar kosong ini sebagai
     * "kartu sasaran baru tidak ditawarkan", bukan sebagai galat.
     *
     * @return array<int, array{id:int, tujuan:string}>
     */
    private function tujuanUntukSasaranBaru(array $revisi): array
    {
        $opd = $this->revisiOpdId();

        if ($opd === null) {
            return [];
        }

        return $this->ikuModel->tujuanRenstraOpd(
            $opd,
            (int) $revisi['tahun_mulai'],
            (int) $revisi['tahun_akhir']
        );
    }

    /**
     * Daftar indikator IKU berjalan pada periode revisi ini — untuk memilih
     * "indikator mana yang digantikan" (lineage, invariant 4 / Case 14).
     */
    private function indikatorLiveUntukLineage(array $revisi): array
    {
        // db_connect() dipakai langsung: kedua controller IKU tidak punya
        // properti $db, dan menambahkannya hanya demi satu query di sini
        // akan mengubah konstruktor yang sudah mapan.
        $b = db_connect()->table('iku_indikator ind')
            ->select('ind.id, ind.indikator, sas.sasaran')
            ->join('iku_sasaran sas', 'sas.id = ind.iku_sasaran_id', 'left')
            ->where('sas.tahun_mulai', (int) $revisi['tahun_mulai'])
            ->where('sas.tahun_akhir', (int) $revisi['tahun_akhir'])
            ->orderBy('sas.urutan', 'ASC')
            ->orderBy('ind.urutan', 'ASC');

        $opd = $this->revisiOpdId();
        $opd === null ? $b->where('sas.opd_id IS NULL', null, false) : $b->where('sas.opd_id', $opd);

        return $b->get()->getResultArray();
    }

    /**
     * Simpan suntingan draft.
     *
     * Yang boleh diubah pada tiap baris arsip: target per tahun, jenis
     * perubahan, indikator yang digantikan, dan penanda perubahan substansial.
     * Teks indikatornya sendiri juga boleh disunting — itulah gunanya revisi.
     */
    public function revisiSuntingSimpan($id = null)
    {
        if (! $this->bolehRevisi()) {
            return redirect()->to($this->urlRevisi())->with('error', 'Anda tidak berwenang menyunting revisi.');
        }

        $revisiId = (int) $id;
        $revisi   = $this->revisi()->ambil($revisiId);

        if (! $this->revisiMilikLingkup($revisi)) {
            return redirect()->to($this->urlRevisi())->with('error', 'Revisi tidak ditemukan.');
        }

        $keadaanIzin = $this->revisiKeadaanIzin($revisi);
        $bawahIzin   = ! empty($keadaanIzin['sedang_disunting']);

        if (! $this->revisiBolehDisunting($revisi)) {
            return redirect()->to($this->urlRevisi('/lihat/' . $revisiId))
                ->with('error', $keadaanIzin['alasan'] ?? 'Hanya draft yang bisa disunting.');
        }

        $baris = (array) ($this->request->getPost('baris') ?? []);

        try {
            $sasaranBaru = (array) ($this->request->getPost('sasaran_baru') ?? []);

            // Sasaran yang teksnya sudah ada di RENSTRA periode ini ditolak di
            // sini, bukan di model: penjaganya milik IkuModel, dan alasannya
            // sama dengan pintu mandiri lama — kalau sasarannya memang ada di
            // Renstra, jalan yang benar adalah Sync, sebab Sync-lah yang
            // menyimpan silsilahnya. Mengetiknya di sini melahirkan sasaran
            // kembar tanpa jejak asal.
            if ($sasaranBaru !== [] && ($opd = $this->revisiOpdId()) !== null) {
                foreach ($sasaranBaru as $satu) {
                    $teks = trim((string) ($satu['sasaran'] ?? ''));

                    if ($teks !== '' && $this->ikuModel->sasaranAdaDiRenstra(
                        $opd, (int) $revisi['tahun_mulai'], (int) $revisi['tahun_akhir'], $teks
                    )) {
                        return redirect()->back()->withInput()->with(
                            'error',
                            'Sasaran "' . mb_substr($teks, 0, 60) . '" SUDAH ADA di Renstra periode ini. '
                            . 'Ambil lewat tombol Sync supaya silsilahnya ikut tersimpan — '
                            . 'mengetiknya di sini akan melahirkan sasaran kembar.'
                        );
                    }
                }
            }

            $this->revisi()->simpanSuntinganDraft(
                $revisiId,
                $baris,
                $this->request->getPost('baru') ?? [],
                $bawahIzin,
                $sasaranBaru
            );

            // Menyunting di bawah izin TIDAK langsung mengubah IKU berjalan.
            // Penerapannya dijadikan langkah tersendiri supaya penyuntingan
            // bisa dilakukan beberapa kali, dan supaya saat yang tepat untuk
            // menutup izin ditentukan pemakai — bukan oleh klik "simpan"
            // pertama yang kebetulan terjadi.
            if ($bawahIzin) {
                return redirect()->to($this->urlRevisi('/lihat/' . $revisiId))->with(
                    'success',
                    'Perbaikan tersimpan pada arsip revisi. IKU berjalan BELUM berubah — '
                    . 'tekan "Selesai & Terapkan" bila perbaikannya sudah lengkap.'
                );
            }

            // Pulang ke DAFTAR revisi, bukan kembali ke form. Dari daftar,
            // langkah berikutnya (Ajukan / Pratinjau / sunting lagi) terlihat
            // semua — kembali ke form hanya menyisakan pertanyaan "lalu apa".
            return redirect()->to($this->urlRevisi())
                ->with('success', 'Draft revisi disimpan. IKU berjalan belum berubah — '
                    . 'ajukan revisinya dari daftar bila sudah selesai.');
        } catch (Throwable $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    /**
     * POST: ubah tahun mulai berlaku revisi.
     *
     * Gerbangnya sama persis dengan menyunting isi: draft bebas diubah
     * penyusunnya; revisi berlaku hanya saat izin sunting sedang terbuka.
     * Masa berlaku menentukan revisi mana yang melayani LAKIP tiap tahun,
     * jadi ia bagian dari isi revisi — bukan atribut kosmetik.
     */
    public function revisiUbahBerlaku($id = null)
    {
        if (! $this->bolehRevisi()) {
            return redirect()->to($this->urlRevisi())
                ->with('error', 'Anda tidak berwenang mengubah revisi IKU.');
        }

        $revisiId = (int) $id;
        $revisi   = $this->revisi()->ambil($revisiId);

        if (! $this->revisiMilikLingkup($revisi)) {
            return redirect()->to($this->urlRevisi())->with('error', 'Revisi tidak ditemukan.');
        }

        // Izin sunting SENGAJA tidak diminta di sini — lihat catatan pada
        // 'bolehUbahBerlaku' di revisiLihat(). Yang dijaga izin adalah isi
        // arsip; tahun berlaku bukan isi. Revisi yang sudah tidak hidup
        // (superseded/batal/menunggu) tetap ditolak oleh model.
        $tahunBaru = (int) $this->request->getPost('berlaku_mulai_tahun');

        try {
            $hasil = $this->revisi()->ubahTahunBerlaku($revisiId, $tahunBaru);

            $pesan = $hasil['dari'] === $hasil['ke']
                ? 'Tahun berlaku tidak berubah.'
                : 'Tahun mulai berlaku diubah dari ' . $hasil['dari'] . ' menjadi ' . $hasil['ke'] . '.'
                    . ($hasil['digeser'] !== []
                        ? ' Masa berlaku ' . count($hasil['digeser']) . ' revisi lain ikut dijahit ulang.'
                        : '');

            $keluar = redirect()->to($this->urlRevisi('/lihat/' . $revisiId));

            // Awal periode yang jadi tanpa payung bukan galat — perubahannya
            // tetap tersimpan — tetapi harus dikatakan, bukan ditemukan sendiri
            // berbulan kemudian saat LAKIP disusun.
            return empty($hasil['peringatan'])
                ? $keluar->with('success', $pesan)
                : $keluar->with('success', $pesan)->with('warning', $hasil['peringatan']);
        } catch (Throwable $e) {
            return redirect()->to($this->urlRevisi('/lihat/' . $revisiId))->with('error', $e->getMessage());
        }
    }

    /* =========================================================
     * SAHKAN & BATALKAN
     * =======================================================*/


    /* =========================================================
     * PENGAJUAN REVISI (sisi penyusun)
     *
     * IKU tingkat Kabupaten tetap disahkan penyusunnya sendiri — dokumennya
     * memang milik mereka. IKU OPD melewati Admin Kabupaten, sebagaimana
     * Renstra: dokumen yang mengikat sebuah OPD sebaiknya diperiksa pihak di
     * luarnya.
     * =======================================================*/

    /** Apakah lingkup ini wajib lewat verifikasi Admin Kabupaten. */
    protected function revisiPerluVerifikasi(): bool
    {
        return $this->revisiPermPrefix() === 'iku_opd';
    }

    public function revisiAjukan($id = null)
    {
        if (! $this->bolehRevisi()) {
            return redirect()->to($this->urlRevisi())
                ->with('error', 'Anda tidak berwenang mengajukan revisi IKU.');
        }

        $revisi = $this->revisi()->ambil((int) $id);

        if (! $this->revisiMilikLingkup($revisi)) {
            return redirect()->to($this->urlRevisi())->with('error', 'Revisi tidak ditemukan.');
        }

        if (! $this->revisiPerluVerifikasi()) {
            return redirect()->to($this->urlRevisi())
                ->with('error', 'Revisi pada lingkup ini disahkan langsung, tidak lewat pengajuan.');
        }

        try {
            $this->revisi()->ajukan((int) $id, (int) session()->get('user_id') ?: null);
        } catch (Throwable $e) {
            return redirect()->to($this->urlRevisi())->with('error', $e->getMessage());
        }

        return redirect()->to($this->urlRevisi())->with('success',
            'Revisi diajukan untuk disahkan Admin Kabupaten. Selama menunggu, isinya '
            . 'tidak bisa disunting — tarik pengajuan bila perlu diperbaiki.');
    }

    public function revisiTarik($id = null)
    {
        if (! $this->bolehRevisi()) {
            return redirect()->to($this->urlRevisi())
                ->with('error', 'Anda tidak berwenang menarik pengajuan revisi.');
        }

        $revisi = $this->revisi()->ambil((int) $id);

        if (! $this->revisiMilikLingkup($revisi)) {
            return redirect()->to($this->urlRevisi())->with('error', 'Revisi tidak ditemukan.');
        }

        try {
            $this->revisi()->tarikPengajuan((int) $id);
        } catch (Throwable $e) {
            return redirect()->to($this->urlRevisi())->with('error', $e->getMessage());
        }

        return redirect()->to($this->urlRevisi())
            ->with('success', 'Pengajuan ditarik. Revisi kembali menjadi draft dan bisa disunting.');
    }

    public function revisiSahkan($id = null)
    {
        // Lingkup yang wajib diverifikasi tidak boleh mengesahkan sendiri,
        // sekalipun izinnya kelak diberikan kembali. Menyandarkan penguncian
        // ini semata pada pemberian izin berarti satu baris di tabel
        // role_permissions bisa membatalkan seluruh alur verifikasi.
        if ($this->revisiPerluVerifikasi()) {
            return redirect()->to($this->urlRevisi())->with('error',
                'Revisi IKU OPD disahkan Admin Kabupaten. Ajukan revisinya, '
                . 'lalu tunggu keputusan.');
        }

        if (! $this->bolehSahkanRevisi()) {
            return redirect()->to($this->urlRevisi())
                ->with('error', 'Anda tidak berwenang mengesahkan revisi IKU.');
        }

        $revisi = $this->revisi()->ambil((int) $id);

        if (! $this->revisiMilikLingkup($revisi)) {
            return redirect()->to($this->urlRevisi())->with('error', 'Revisi tidak ditemukan.');
        }

        try {
            $hasil = $this->revisi()->sahkan((int) $id, (int) session()->get('user_id') ?: null);

            $pesan = 'Revisi disahkan dan berlaku mulai tahun ' . $revisi['berlaku_mulai_tahun'] . '.';

            if ($hasil['indikator_dipensiunkan'] > 0) {
                $pesan .= ' ' . $hasil['indikator_dipensiunkan'] . ' indikator dipensiunkan (tidak dihapus) '
                    . 'sehingga LAKIP tahun-tahun sebelumnya tetap utuh.';
            }

            if (! empty($hasil['digeser'])) {
                $pesan .= ' ' . count($hasil['digeser']) . ' revisi sebelumnya menjadi arsip.';
            }

            return redirect()->to($this->urlRevisi())->with('success', $pesan);
        } catch (Throwable $e) {
            return redirect()->to($this->urlRevisi())->with('error', $e->getMessage());
        }
    }

    public function revisiBatalkan($id = null)
    {
        if (! $this->bolehRevisi()) {
            return redirect()->to($this->urlRevisi())->with('error', 'Anda tidak berwenang membatalkan revisi.');
        }

        $revisi = $this->revisi()->ambil((int) $id);

        if (! $this->revisiMilikLingkup($revisi)) {
            return redirect()->to($this->urlRevisi())->with('error', 'Revisi tidak ditemukan.');
        }

        try {
            $this->revisi()->batalkan((int) $id, (int) session()->get('user_id') ?: null);

            return redirect()->to($this->urlRevisi())
                ->with('success', 'Draft dibatalkan. Jejaknya tetap tersimpan.');
        } catch (Throwable $e) {
            return redirect()->to($this->urlRevisi())->with('error', $e->getMessage());
        }
    }
}
