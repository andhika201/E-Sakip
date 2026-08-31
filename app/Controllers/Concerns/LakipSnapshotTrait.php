<?php

namespace App\Controllers\Concerns;

use App\Models\LakipPenyesuaianModel;
use App\Models\LakipSnapshotModel;
use App\Models\Opd\IkuRevisiModel;
use Throwable;

/**
 * Snapshot tahunan LAKIP + penyesuaian kebijakan, dipakai bersama oleh
 * AdminKab\LakipController dan AdminOpd\LakipOpdController.
 *
 * Melengkapi LakipAddendumTrait yang sudah ada, dan memakai ulang
 * lakipScope() / lakipScopeFromPost() dari trait itu sebagai satu-satunya
 * sumber otorisasi lingkup.
 *
 * ---------------------------------------------------------------------
 * KAPAN HALAMAN MEMBACA ARSIP, KAPAN MEMBACA DATA HIDUP
 *
 *   ?snapshot=<id>  -> versi itu yang dibaca (untuk melihat versi lama)
 *   tahun TERKUNCI  -> snapshot final yang dibaca
 *   selain itu      -> data hidup, persis seperti sebelum fitur ini ada
 *
 * Snapshot berstatus draft SENGAJA tidak membajak tampilan. Kalau ia ikut
 * dipakai begitu dibuat, operator akan bingung mengapa angka berhenti mengikuti
 * perubahan yang baru saja ia simpan. Yang mengunci tampilan adalah FINALISASI,
 * dan itu tindakan sadar.
 *
 * Karena rehidrasi() mengembalikan bentuk yang sama persis dengan
 * LakipModel::getLakipByMode(), seluruh view, view cetak, dan kedua fungsi
 * Excel tidak perlu tahu sedang membaca arsip atau bukan.
 *
 * ---------------------------------------------------------------------
 * HAK TULIS
 *
 * Selain lakipScope()->canWrite, aksi di sini menuntut permission tersendiri:
 *
 *   <prefix>.snapshot     siapkan & sinkronkan
 *   <prefix>.finalisasi   kunci tahun
 *   <prefix>.penyesuaian  penyesuaian kebijakan
 *
 * dengan prefix 'lakip_kab' atau 'lakip_opd'. Role read-only (bupati,
 * admin_inspektorat) tidak diberi satu pun, sehingga tombolnya tidak pernah
 * muncul dan aksinya tetap ditolak walau URL-nya ditebak.
 */
trait LakipSnapshotTrait
{
    protected ?LakipSnapshotModel $snapshotModel      = null;
    protected ?LakipPenyesuaianModel $penyesuaianModel = null;

    protected function snapshot(): LakipSnapshotModel
    {
        return $this->snapshotModel ??= new LakipSnapshotModel();
    }

    protected function penyesuaian(): LakipPenyesuaianModel
    {
        return $this->penyesuaianModel ??= new LakipPenyesuaianModel();
    }

    /**
     * Prefix permission modul ini: 'lakip_kab' atau 'lakip_opd'.
     * Wajib disediakan controller pemakai.
     */
    abstract protected function lakipPermPrefix(): string;

    private function bolehSnapshot(): bool
    {
        return function_exists('user_can') && user_can($this->lakipPermPrefix() . '.snapshot');
    }

    private function bolehFinalisasi(): bool
    {
        return function_exists('user_can') && user_can($this->lakipPermPrefix() . '.finalisasi');
    }

    private function bolehPenyesuaian(): bool
    {
        return function_exists('user_can') && user_can($this->lakipPermPrefix() . '.penyesuaian');
    }

    /* =========================================================
     * SUMBER DATA HALAMAN
     * =======================================================*/

    /**
     * Tentukan sumber baris LAKIP: arsip beku atau data hidup.
     *
     * @param array  $scope     hasil lakipScope()
     * @param string $status    filter status pada halaman
     * @param array  $hidup     ['rows' => ..., 'lakipMap' => ...] dari LakipModel
     *
     * @return array{
     *     rows: array, lakipMap: array, snapshot: array|null,
     *     dariSnapshot: bool, terkunci: bool, pesan: string|null
     * }
     */
    protected function sumberLakip(array $scope, string $status, array $hidup): array
    {
        $hasil = [
            'rows'         => $hidup['rows'] ?? [],
            'lakipMap'     => $hidup['lakipMap'] ?? [],
            'snapshot'     => null,
            'dariSnapshot' => false,
            'terkunci'     => false,
            'pesan'        => null,
        ];

        $model = $this->snapshot();

        if (! $model->siap()) {
            return $hasil;
        }

        $tahun = (string) $scope['tahun'];
        $mode  = (string) $scope['mode'];
        $opd   = $scope['opdScope'];

        // Lintas OPD (admin_kab belum memilih OPD) tidak punya snapshot:
        // dokumen LAKIP selalu milik satu unit kerja.
        if ($mode === 'opd' && empty($opd)) {
            return $hasil;
        }

        $aktif = $model->aktif($tahun, $mode, $opd);
        $hasil['snapshot'] = $aktif;
        $hasil['terkunci'] = $aktif !== null && $aktif['status'] === LakipSnapshotModel::STATUS_FINAL;

        $diminta = (int) ($this->request->getGet('snapshot') ?? 0);
        $dipakai = null;

        if ($diminta > 0) {
            $kandidat = $model->ambil($diminta);

            // Cegah IDOR: snapshot dari lingkup lain tidak boleh dibuka lewat
            // tebakan id.
            if ($kandidat
                && (string) $kandidat['tahun'] === $tahun
                && $kandidat['mode'] === $mode
                && (int) $kandidat['opd_id'] === (int) ($mode === 'kabupaten' ? 0 : $opd)) {
                $dipakai = $kandidat;
            } else {
                $hasil['pesan'] = 'Snapshot yang diminta tidak tersedia untuk tahun & lingkup ini.';
            }
        } elseif ($hasil['terkunci']) {
            $dipakai = $aktif;
        }

        if ($dipakai === null) {
            return $hasil;
        }

        $isi = $model->rehidrasi((int) $dipakai['id']);

        $hasil['rows']         = $isi['rows'];
        $hasil['lakipMap']     = $isi['lakipMap'];
        $hasil['snapshot']     = $dipakai;
        $hasil['dariSnapshot'] = true;

        return $hasil;
    }

    /**
     * Bahan panel snapshot untuk view (daftar versi, penyesuaian, izin tombol).
     *
     * @return array<string, mixed>
     */
    protected function dataSnapshot(array $scope, array $sumber): array
    {
        $model = $this->snapshot();
        $tahun = (string) $scope['tahun'];
        $mode  = (string) $scope['mode'];
        $opd   = $scope['opdScope'];

        $lintasOpd = ($mode === 'opd' && empty($opd));

        return [
            'snapshotSiap'        => $model->siap(),
            'snapshotAktif'       => $sumber['snapshot'] ?? null,
            'snapshotDaftar'      => $model->siap() && ! $lintasOpd ? $model->daftar($tahun, $mode, $opd) : [],
            'snapshotDipakai'     => ! empty($sumber['dariSnapshot']),
            'snapshotTerkunci'    => ! empty($sumber['terkunci']),
            'snapshotLintasOpd'   => $lintasOpd,
            'snapshotPesan'       => $sumber['pesan'] ?? null,
            'penyesuaianPeta'     => $this->penyesuaian()->petaAktif(
                $tahun,
                $mode,
                $opd,
                $this->sumberDokumenLakip($mode)
            ),
            'penyesuaianRiwayat'  => $lintasOpd ? [] : $this->penyesuaian()->riwayat($tahun, $mode, $opd),
            'bolehSnapshot'       => $this->bolehSnapshot(),
            'bolehFinalisasi'     => $this->bolehFinalisasi(),
            'bolehPenyesuaian'    => $this->bolehPenyesuaian(),
            'snapshotBase'        => $this->lakipBaseUrl(),
        ];
    }

    /**
     * Dua tabel tambahan (Analisis Faktor & Efisiensi Program): versi beku bila
     * halaman sedang membaca snapshot, versi hidup bila tidak.
     *
     * Dipakai menggantikan pemanggilan lakipAddendumData() langsung. Kalau tidak
     * ditukar, tabel utama akan beku sementara dua tabel di bawahnya tetap
     * mengikuti data hidup — dokumen yang setengah beku justru lebih menyesatkan
     * daripada tidak beku sama sekali.
     *
     * @return array<string, mixed>
     */
    protected function addendumLakip(array $scope, array $sumber): array
    {
        $data = $this->lakipAddendumData($scope);

        if (! empty($sumber['dariSnapshot']) && ! empty($sumber['snapshot'])) {
            $beku = $this->snapshot()->rehidrasiAddendum((int) $sumber['snapshot']['id']);

            $data['analisisMap']   = $beku['analisisMap'];
            $data['efisiensiRows'] = $beku['efisiensiRows'];

            // Pilihan program tetap dari data hidup: itu daftar untuk MENAMBAH
            // baris baru, bukan isi dokumen. Pada tahun terkunci daftar ini
            // memang tidak terpakai karena tombol tambahnya disembunyikan.
        }

        return $data;
    }

    /* =========================================================
     * PINTU SNAPSHOT & PENYESUAIAN DITUTUP
     *
     * Penguncian tahun LAKIP kini dilayani mekanisme PENGESAHAN
     * (LakipPengesahanModel): sahkan -> terkunci -> ajukan perbaikan ->
     * admin_kab memutuskan -> sahkan ulang.
     *
     * Endpoint di bawah TIDAK CUKUP disembunyikan tombolnya. Selama ia hidup,
     * URL yang diketik langsung masih bisa memfinalkan tahun lewat jalur LAMA
     * — dan layar akan terkunci oleh kunci yang tidak dijelaskan panel
     * Pengesahan mana pun. Dua kunci pada satu tahun adalah keadaan yang
     * mustahil dipahami operator.
     *
     * Kode & tabelnya sengaja TIDAK dihapus: dua snapshot draft milik 2025
     * masih tersimpan utuh, dan mekanismenya bisa dihidupkan kembali bila
     * kelak dibutuhkan.
     * =======================================================*/

    /** Jawaban seragam bagi seluruh pintu yang ditutup. */
    private function snapshotDitutup(array $scope)
    {
        return redirect()->to($this->kembaliLakip($scope))->with(
            'error',
            'Snapshot & Penyesuaian Kebijakan tidak lagi dipakai. Penguncian LAKIP '
            . 'kini lewat panel Pengesahan: tekan Sahkan untuk mengunci, dan bila ada '
            . 'yang keliru ajukan Permintaan Perbaikan ke Admin Kabupaten.'
        );
    }

    /* =========================================================
     * AKSI — SNAPSHOT
     * =======================================================*/

    /** POST: siapkan snapshot pertama untuk tahun & lingkup pada form. */
    public function snapshotSiapkan()
    {
        $scope = $this->lakipScopeFromPost();

        return $this->snapshotDitutup($scope);
        // @phpstan-ignore-next-line — kode lama sengaja dipertahankan di bawah.

        if (! $scope['canWrite'] || ! $this->bolehSnapshot()) {
            return redirect()->to($this->kembaliLakip($scope))
                ->with('error', 'Anda tidak berwenang menyiapkan snapshot LAKIP pada lingkup ini.');
        }

        try {
            $id = $this->snapshot()->siapkan($scope, $this->bahanSnapshot($scope), $this->penggunaSaatIni());

            return redirect()->to($this->kembaliLakip($scope) . (str_contains($this->kembaliLakip($scope), '?') ? '&' : '?') . 'snapshot=' . $id)
                ->with('success', 'Snapshot LAKIP tahun ' . $scope['tahun'] . ' berhasil disiapkan.');
        } catch (Throwable $e) {
            return redirect()->to($this->kembaliLakip($scope))->with('error', $e->getMessage());
        }
    }

    /** POST: buat versi baru dari data terkini (versi lama dinonaktifkan). */
    public function snapshotSinkronkan()
    {
        $scope = $this->lakipScopeFromPost();

        return $this->snapshotDitutup($scope);
        // @phpstan-ignore-next-line — kode lama sengaja dipertahankan di bawah.

        if (! $scope['canWrite'] || ! $this->bolehSnapshot()) {
            return redirect()->to($this->kembaliLakip($scope))
                ->with('error', 'Anda tidak berwenang menyinkronkan snapshot LAKIP pada lingkup ini.');
        }

        try {
            $this->snapshot()->sinkronkan($scope, $this->bahanSnapshot($scope), $this->penggunaSaatIni());

            return redirect()->to($this->kembaliLakip($scope))
                ->with('success', 'Snapshot LAKIP tahun ' . $scope['tahun'] . ' disinkronkan dengan data terbaru.');
        } catch (Throwable $e) {
            return redirect()->to($this->kembaliLakip($scope))->with('error', $e->getMessage());
        }
    }

    /** POST: kunci tahun. Tidak ada pasangan "buka kunci" — lihat invariant 6. */
    public function snapshotFinalkan()
    {
        $scope = $this->lakipScopeFromPost();

        return $this->snapshotDitutup($scope);
        // @phpstan-ignore-next-line — kode lama sengaja dipertahankan di bawah.

        if (! $scope['canWrite'] || ! $this->bolehFinalisasi()) {
            return redirect()->to($this->kembaliLakip($scope))
                ->with('error', 'Anda tidak berwenang memfinalkan LAKIP pada lingkup ini.');
        }

        try {
            $aktif = $this->snapshot()->aktif((string) $scope['tahun'], (string) $scope['mode'], $scope['opdScope']);

            if (! $aktif) {
                throw new \RuntimeException('Belum ada snapshot untuk difinalkan. Siapkan snapshot lebih dulu.');
            }

            $this->snapshot()->finalkan((int) $aktif['id'], $this->penggunaSaatIni());

            return redirect()->to($this->kembaliLakip($scope))->with(
                'success',
                'LAKIP tahun ' . $scope['tahun'] . ' difinalkan. Angkanya tidak akan berubah lagi walau '
                . 'RPJMD/Renstra/IKU disunting. Koreksi selanjutnya hanya lewat Penyesuaian Kebijakan.'
            );
        } catch (Throwable $e) {
            return redirect()->to($this->kembaliLakip($scope))->with('error', $e->getMessage());
        }
    }

    /** GET: bandingkan snapshot aktif dengan data hidup. */
    public function snapshotBandingkan()
    {
        $session = session();
        $tahun   = (string) ($this->request->getGet('tahun') ?: date('Y'));
        $mode    = (string) ($this->request->getGet('mode') ?: 'opd');
        $scope   = $this->lakipScope($tahun, $mode);

        $aktif = $this->snapshot()->siap()
            ? $this->snapshot()->aktif($tahun, (string) $scope['mode'], $scope['opdScope'])
            : null;

        if (! $aktif) {
            return redirect()->to($this->kembaliLakip($scope))
                ->with('error', 'Belum ada snapshot untuk dibandingkan pada tahun & lingkup ini.');
        }

        $beda = $this->snapshot()->bandingkan((int) $aktif['id'], $this->bahanSnapshot($scope));

        return view('lakip/snapshot_banding', [
            'title'      => 'Bandingkan Snapshot LAKIP ' . $tahun,
            'role'       => $session->get('role'),
            'scope'      => $scope,
            'snapshot'   => $aktif,
            'beda'       => $beda,
            'kembaliUrl' => $this->kembaliLakip($scope),
        ]);
    }

    /* =========================================================
     * AKSI — PENYESUAIAN KEBIJAKAN
     * =======================================================*/

    public function penyesuaianSave()
    {
        $scope = $this->lakipScopeFromPost();

        return $this->snapshotDitutup($scope);
        // @phpstan-ignore-next-line — kode lama sengaja dipertahankan di bawah.

        if (! $scope['canWrite'] || ! $this->bolehPenyesuaian()) {
            return redirect()->to($this->kembaliLakip($scope))
                ->with('error', 'Anda tidak berwenang membuat penyesuaian LAKIP pada lingkup ini.');
        }

        $post = $this->request->getPost();

        // Pola anti-script yang sama dengan LakipAddendumTrait.
        foreach (['dasar_kebijakan', 'nomor_dasar', 'alasan', 'nilai_disesuaikan', 'nilai_asli'] as $k) {
            if (! $this->teksAmanLakip($post[$k] ?? null)) {
                return redirect()->to($this->kembaliLakip($scope))
                    ->with('error', 'Isian ' . $k . ' terdeteksi mengandung script / input berbahaya.');
            }
        }

        // Pencegah IDOR: id target dari request diverifikasi ulang ke tahun &
        // lingkup yang sedang aktif, sama seperti analisisSave()/efisiensiSave().
        $targetId = (int) ($post['target_id'] ?? 0);

        // Sumber ikut dikirim form panel snapshot. Ia menentukan DI TABEL MANA
        // id itu harus ada — id indikator IKU dan id target Renstra hidup di
        // ruang angka yang sama, jadi memverifikasinya ke tabel yang salah
        // bukan sekadar gagal, tapi bisa lolos untuk baris yang keliru.
        $sumberDok = $this->sumberDokumenLakip((string) $scope['mode']);
        $target    = $this->targetLakipSah(
            $targetId,
            (string) $scope['mode'],
            (string) $scope['tahun'],
            $scope['opdScope'],
            $sumberDok
        );

        if (! $target) {
            return redirect()->to($this->kembaliLakip($scope))
                ->with('error', 'Indikator yang dipilih tidak ada pada tahun & lingkup ini.');
        }

        try {
            $id = $this->penyesuaian()->simpan($scope, [
                'sumber'            => $sumberDok,
                'target_id'         => $targetId,
                'jenis'             => (string) ($post['jenis'] ?? ''),
                'nilai_asli'        => $post['nilai_asli'] ?? null,
                'nilai_disesuaikan' => $post['nilai_disesuaikan'] ?? null,
                'dasar_kebijakan'   => $post['dasar_kebijakan'] ?? null,
                'nomor_dasar'       => $post['nomor_dasar'] ?? null,
                'tanggal_dasar'     => $post['tanggal_dasar'] ?? null,
                'alasan'            => $post['alasan'] ?? null,
            ], $this->penggunaSaatIni());

            $baris  = $this->penyesuaian()->ambil($id);
            $catatan = ! empty($baris['setelah_final'])
                ? ' Dicatat sebagai penyesuaian SETELAH finalisasi.'
                : '';

            return redirect()->to($this->kembaliLakip($scope))
                ->with('success', 'Penyesuaian kebijakan tersimpan.' . $catatan);
        } catch (Throwable $e) {
            return redirect()->to($this->kembaliLakip($scope))->with('error', $e->getMessage());
        }
    }

    /**
     * POST: "Usulkan sebagai Perubahan IKU" (Case 15).
     *
     * Hanya membuat DRAFT revisi IKU. Tidak ada jalur di sini yang menyentuh
     * IKU yang sedang berlaku.
     */
    public function penyesuaianUsulRevisi($id = null)
    {
        $scope = $this->lakipScopeFromPost();

        if (! $scope['canWrite'] || ! $this->bolehPenyesuaian()) {
            return redirect()->to($this->kembaliLakip($scope))
                ->with('error', 'Anda tidak berwenang mengusulkan perubahan IKU.');
        }

        $baris = $this->penyesuaian()->ambil((int) $id);

        if (! $baris
            || (string) $baris['tahun'] !== (string) $scope['tahun']
            || $baris['mode'] !== $scope['mode']) {
            return redirect()->to($this->kembaliLakip($scope))
                ->with('error', 'Penyesuaian tidak ditemukan pada lingkup ini.');
        }

        // Lingkup IKU: mode kabupaten -> IKU kabupaten (opd_id NULL);
        // mode OPD -> IKU milik OPD tersebut.
        $opdIku = $scope['mode'] === 'kabupaten' ? null : (int) $scope['opdScope'];
        $tahun  = (int) $scope['tahun'];

        $periode = $this->periodeIkuUntukTahun($opdIku, $tahun);

        if (! $periode) {
            return redirect()->to($this->kembaliLakip($scope))->with(
                'error',
                'Belum ada periode IKU yang memuat tahun ' . $tahun . ' pada lingkup ini, '
                . 'sehingga usulan revisi tidak bisa dibuat.'
            );
        }

        // Usulan berlaku mulai TAHUN BERIKUTNYA: itulah yang membedakannya dari
        // penyesuaian, yang hanya berlaku untuk tahun LAKIP ini saja.
        $berlaku = min($tahun + 1, (int) $periode['tahun_akhir']);

        try {
            $revisiId = $this->penyesuaian()->usulkanRevisiIku(
                (int) $id,
                $opdIku,
                (int) $periode['tahun_mulai'],
                (int) $periode['tahun_akhir'],
                $berlaku,
                $this->penggunaSaatIni()
            );

            return redirect()->to($this->kembaliLakip($scope))->with(
                'success',
                'Draft revisi IKU #' . $revisiId . ' dibuat (berlaku mulai ' . $berlaku . '). '
                . 'IKU yang sedang berlaku BELUM berubah — buka modul IKU untuk menyunting lalu mengesahkannya.'
            );
        } catch (Throwable $e) {
            return redirect()->to($this->kembaliLakip($scope))->with('error', $e->getMessage());
        }
    }

    public function penyesuaianCabut($id = null)
    {
        $scope = $this->lakipScopeFromPost();

        if (! $scope['canWrite'] || ! $this->bolehPenyesuaian()) {
            return redirect()->to($this->kembaliLakip($scope))
                ->with('error', 'Anda tidak berwenang mencabut penyesuaian.');
        }

        $baris = $this->penyesuaian()->ambil((int) $id);

        if (! $baris
            || (string) $baris['tahun'] !== (string) $scope['tahun']
            || $baris['mode'] !== $scope['mode']) {
            return redirect()->to($this->kembaliLakip($scope))
                ->with('error', 'Penyesuaian tidak ditemukan pada lingkup ini.');
        }

        try {
            $this->penyesuaian()->cabut((int) $id);

            return redirect()->to($this->kembaliLakip($scope))
                ->with('success', 'Penyesuaian dicabut. Riwayatnya tetap tersimpan.');
        } catch (Throwable $e) {
            return redirect()->to($this->kembaliLakip($scope))->with('error', $e->getMessage());
        }
    }

    /* =========================================================
     * HELPER
     * =======================================================*/

    /**
     * Rakit bahan pembekuan dari data HIDUP.
     *
     * Sengaja memakai LakipModel & model addendum yang sama dengan halaman
     * index, supaya isi snapshot persis sama dengan yang dilihat operator saat
     * ia menekan tombolnya.
     */
    protected function bahanSnapshot(array $scope): array
    {
        $tahun  = (string) $scope['tahun'];
        $mode   = (string) $scope['mode'];
        $opd    = $scope['opdScope'];
        $status = (string) ($this->request->getPost('status') ?? $this->request->getGet('status') ?? '');

        $hidup = $this->barisHidupUntukSnapshot($mode, $tahun, $status, $opd);

        $opdEfisiensi = ($mode === 'kabupaten') ? 0 : $opd;

        // Revisi IKU yang efektif ikut dicatat supaya angka LAKIP selalu bisa
        // ditelusuri ke versi dokumen IKU-nya. Konflik revisi TIDAK diselesaikan
        // diam-diam: bila ada, id-nya dibiarkan kosong dan pemakai melihat
        // peringatan di halaman IKU (invariant 2).
        $revisiId = null;

        try {
            $revisi = new IkuRevisiModel();

            if ($revisi->siap()) {
                $efektif  = $revisi->resolveEfektif($mode === 'kabupaten' ? null : (int) $opd, (int) $tahun);
                $revisiId = $efektif['revisi']['id'] ?? null;
            }
        } catch (Throwable $e) {
            $revisiId = null;
        }

        // Bila sumbernya memang IKU, versi YANG DIPILIH operator-lah yang
        // dibekukan — bukan revisi yang kebetulan efektif hari ini. Dua kolom
        // yang menyebut revisi berbeda pada satu snapshot hanya akan membuat
        // penelusuran berikutnya bertengkar dengan dirinya sendiri.
        if (($hidup['sumber_type'] ?? null) === 'iku' && ! empty($hidup['sumber_versi_id'])) {
            $revisiId = (int) $hidup['sumber_versi_id'];
        }

        return [
            'rows'            => $hidup['rows'],
            'lakipMap'        => $hidup['lakipMap'],
            // Sumbernya WAJIB sama dengan sumber baris yang dibekukan di
            // atas — bukan sumber yang kebetulan dipilih di layar. Kalau
            // berbeda, snapshot membekukan baris IKU tetapi analisis Renstra,
            // dan keduanya tidak akan pernah bertemu saat dibaca ulang.
            'analisisMap'     => $this->analisis()->getByTahunGrouped(
                $tahun,
                $mode,
                $opd,
                $hidup['sumber_type'] ?? $this->sumberDokumenLakip($mode)
            ),
            'efisiensiRows'   => $this->efisiensi()->getByTahun($tahun, $opdEfisiensi),
            'filter_status'   => $status,
            'iku_revisi_id'   => $revisiId,
            'sumber_type'     => $hidup['sumber_type'] ?? null,
            'sumber_versi_id' => $hidup['sumber_versi_id'] ?? null,
        ];
    }

    /**
     * Baris & peta LAKIP hidup yang akan dibekukan.
     *
     * SEBUAH SEAM YANG SENGAJA BISA DITIMPA. Jangan diubah jadi private.
     *
     * Default-nya menyimpulkan sumber dari mode saja — benar untuk LAKIP
     * Kabupaten, yang memang hanya punya RPJMD. Tetapi layar LAKIP OPD kini
     * punya PEMILIH sumber (IKU atau Renstra, per versi). Kalau seam ini tidak
     * ditimpa di sana, tombol "Siapkan Snapshot" akan membekukan baris Renstra
     * padahal yang dilihat dan dinilai operator adalah baris IKU: dokumen beku
     * yang isinya tidak pernah tampil di layar mana pun, dan tidak ada galat
     * yang memberitahu.
     *
     * @return array{rows:array, lakipMap:array, sumber_type:?string, sumber_versi_id:?int}
     */
    /**
     * Sumber dokumen yang sedang dipilih di layar.
     *
     * DISEDIAKAN LakipAddendumTrait, yang selalu dipakai bersama trait ini.
     * Dinyatakan abstract di sini supaya ketergantungan itu gagal saat kelas
     * disusun — bukan saat tombol snapshot ditekan di produksi.
     */
    abstract protected function sumberDokumenLakip(string $mode): ?string;

    protected function barisHidupUntukSnapshot(string $mode, string $tahun, string $status, $opd): array
    {
        return $this->lakipModel->getLakipByMode(
            $mode,
            $tahun,
            ($status !== '' ? $status : null),
            $mode === 'kabupaten' ? null : (int) $opd
        ) + [
            'sumber_type'     => $mode === 'kabupaten' ? 'rpjmd' : 'renstra',
            'sumber_versi_id' => null,
        ];
    }

    /**
     * Periode IKU yang memuat satu tahun, untuk lingkup tertentu.
     *
     * @return array{tahun_mulai:int, tahun_akhir:int}|null
     */
    private function periodeIkuUntukTahun(?int $opdId, int $tahun): ?array
    {
        $b = $this->db->table('iku_sasaran')
            ->select('tahun_mulai, tahun_akhir')
            ->where('tahun_mulai <=', $tahun)
            ->where('tahun_akhir >=', $tahun)
            ->orderBy('tahun_mulai', 'DESC')
            ->limit(1);

        $opdId === null ? $b->where('opd_id IS NULL', null, false) : $b->where('opd_id', $opdId);

        $row = $b->get()->getRowArray();

        return $row ? ['tahun_mulai' => (int) $row['tahun_mulai'], 'tahun_akhir' => (int) $row['tahun_akhir']] : null;
    }

    /** Pola yang sama dengan LakipAddendumTrait baris 330/440. */
    private function penggunaSaatIni(): ?int
    {
        return (int) session()->get('user_id') ?: null;
    }
}
