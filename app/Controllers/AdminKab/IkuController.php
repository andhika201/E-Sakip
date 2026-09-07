<?php

namespace App\Controllers\AdminKab;

use App\Controllers\BaseController;
use App\Controllers\Concerns\IkuFormTrait;
use App\Controllers\Concerns\IkuRevisiTrait;
use App\Models\Opd\IkuModel;
use App\Models\Opd\IkuRevisiModel;
use App\Models\OpdModel;

/**
 * IKU tingkat Kabupaten — STANDALONE.
 *
 * Sejak 2026-07-27 IKU tidak lagi menempel ke RPJMD/RENSTRA: sasaran, indikator,
 * satuan, dan target per tahun diinput langsung di modul ini dan disimpan di
 * `iku_sasaran` / `iku_indikator` / `iku_target`.
 *
 * Dua mode tampilan:
 *   * mode=kabupaten -> IKU milik Pemerintah Kabupaten (iku_sasaran.opd_id NULL),
 *                       bisa ditambah/diubah/dihapus di sini.
 *   * mode=opd       -> rekap IKU seluruh OPD/Kecamatan (opd_id terisi), untuk
 *                       pemantauan; penyuntingannya tetap di modul IKU OPD.
 */
class IkuController extends BaseController
{
    use IkuFormTrait;

    /** Revisi IKU: versi dokumen yang bernomor & bertanggal berlaku. */
    use IkuRevisiTrait;

    protected IkuModel $ikuModel;
    protected OpdModel $opdModel;

    public function __construct()
    {
        $this->ikuModel = new IkuModel();
        $this->opdModel = new OpdModel();
    }

    /* =========================================================
     * LINGKUP REVISI (dipakai IkuRevisiTrait)
     * =======================================================*/

    protected function revisiPermPrefix(): string
    {
        return 'iku_kab';
    }

    /**
     * Revisi yang dikelola di sini SELALU tingkat kabupaten (opd_id NULL).
     *
     * Mode 'opd' pada halaman ini hanyalah rekap pemantauan lintas OPD;
     * revisi IKU milik OPD dikelola di modul IKU OPD oleh OPD-nya sendiri.
     */
    protected function revisiOpdId(): ?int
    {
        return null;
    }

    protected function revisiBaseUrl(): string
    {
        return 'adminkab/iku';
    }

    /* =========================================================
     * LIST
     * =======================================================*/
    public function index()
    {
        $mode      = $this->modeAktif();
        $opdFilter = $this->opdFilter();

        [$groupedData, $periode] = $this->resolvePeriode($mode, $opdFilter);

        $ikuData = $this->ikuModel->getMatrix([
            'level'       => $mode,
            'opd_id'      => $mode === 'opd' ? $opdFilter : null,
            'tahun_mulai' => $periode['tahun_mulai'] ?? null,
            'tahun_akhir' => $periode['tahun_akhir'] ?? null,
        ]);

        // Keadaan pengesahan IKU Kabupaten (lingkup opd NULL) — panel yang
        // sama dengan layar IKU OPD, supaya konsep kedua layar tidak berbeda.
        $rev = new IkuRevisiModel();
        $adaPeriode = $mode === 'kabupaten' && $rev->siap() && ! empty($periode['tahun_mulai']);

        return view('adminKabupaten/iku/iku', [
            'revisiMenunggu' => $adaPeriode
                ? $rev->berjalanMenunggu(null, (int) $periode['tahun_mulai'], (int) $periode['tahun_akhir'])
                : null,
            'revisiBerlaku'  => $adaPeriode
                ? $rev->revisiBerlaku(null, (int) $periode['tahun_mulai'], (int) $periode['tahun_akhir'])
                : null,
            'title'            => 'IKU - e-SAKIP',
            'mode'             => $mode,
            'opdFilter'        => $opdFilter,
            'opdList'          => $this->opdModel->orderBy('nama_opd', 'ASC')->findAll(),
            'iku_data'         => $ikuData,
            'grouped_data'     => $groupedData,
            'selected_periode' => $periode['key'] ?? null,
            'years'            => $periode['years'] ?? [],
        ]);
    }

    /* =========================================================
     * FORM TAMBAH (selalu IKU tingkat kabupaten)
     * =======================================================*/
    public function tambah()
    {
        // Pintu tambah manual DITUTUP (bukan sekadar tombolnya disembunyikan):
        // selama endpoint-nya hidup, tautan lama atau URL yang diketik langsung
        // tetap bisa melahirkan sasaran kembar di sebelah hasil sync.
        //
        // Rutenya sengaja dibiarkan terdaftar supaya tautan lama tidak berujung
        // 404 tanpa penjelasan — yang datang ke sini diberi tahu jalan yang benar.
        return redirect()->to(base_url('adminkab/iku'))
            ->with('error', 'IKU tidak lagi ditambah manual. Sasaran & indikatornya diambil dari RPJMD lewat tombol Sync — supaya tidak lahir sasaran kembar dan setiap baris punya jejak asal.');
    }

    /* =========================================================
     * SIMPAN BARU
     * =======================================================*/
    public function save()
    {
        // Pintu tambah manual DITUTUP (bukan sekadar tombolnya disembunyikan):
        // selama endpoint-nya hidup, tautan lama atau URL yang diketik langsung
        // tetap bisa melahirkan sasaran kembar di sebelah hasil sync.
        //
        // Rutenya sengaja dibiarkan terdaftar supaya tautan lama tidak berujung
        // 404 tanpa penjelasan — yang datang ke sini diberi tahu jalan yang benar.
        return redirect()->to(base_url('adminkab/iku'))
            ->with('error', 'IKU tidak lagi ditambah manual. Sasaran & indikatornya diambil dari RPJMD lewat tombol Sync — supaya tidak lahir sasaran kembar dan setiap baris punya jejak asal.');
    }

    /* =========================================================
     * SYNC DARI RPJMD — PRATINJAU
     * GET adminkab/iku/sync?periode=2025-2029
     * =======================================================*/
    /**
     * Bekukan IKU Kabupaten periode ini menjadi revisi, siap disahkan.
     *
     * Kembaran AdminOpd\IkuController::ajukanPengesahan() untuk lingkup
     * kabupaten (opd NULL). Bedanya satu: tidak ada pihak luar yang menunggu —
     * dokumen kabupaten disahkan penyusunnya sendiri lewat tombol Sahkan.
     */
    public function ajukanPengesahan()
    {
        if (! user_can('iku_kab.revisi')) {
            return redirect()->to(base_url('adminkab/iku'))
                ->with('error', 'Anda tidak berwenang mengajukan pengesahan IKU.');
        }

        $tm = (int) $this->request->getPost('tahun_mulai');
        $ta = (int) $this->request->getPost('tahun_akhir');

        if ($tm <= 0 || $ta < $tm) {
            return redirect()->back()->with('error', 'Periode IKU tidak sah.');
        }

        $kembali = base_url('adminkab/iku?mode=kabupaten&periode=' . $tm . '-' . $ta);

        try {
            (new IkuRevisiModel())->bekukanDanAjukan(null, $tm, $ta, $this->penggunaIkuKab(), [
                'catatan' => $this->request->getPost('catatan') ?: null,
            ]);
        } catch (\Throwable $e) {
            return redirect()->to($kembali)->with('error', $e->getMessage());
        }

        return redirect()->to($kembali)->with('success',
            'IKU Kabupaten periode ' . $tm . '-' . $ta . ' dibekukan dan siap disahkan. '
            . 'Tekan "Sahkan Sekarang" bila isinya sudah final.');
    }

    private function penggunaIkuKab(): ?int
    {
        $id = session()->get('user_id') ?? session()->get('id');

        return $id === null ? null : (int) $id;
    }

    public function sync()
    {
        if (!user_can('iku_kab.create')) {
            return redirect()->to(base_url('adminkab/iku'))
                ->with('error', 'Anda tidak memiliki akses untuk menyalin data ke IKU.');
        }

        [$daftarPeriode, $periode] = $this->resolvePeriodeSumber('rpjmd', null);

        if (empty($periode)) {
            return redirect()->to(base_url('adminkab/iku?mode=kabupaten'))
                ->with('error', 'Belum ada periode RPJMD yang bisa disalin. Isi RPJMD terlebih dahulu.');
        }

        // Versi RPJMD yang jadi titik tolak. Tanpa parameter ini, sumbernya
        // kondisi berjalan — perilaku lama, apa adanya.
        $versiTersedia = $this->ikuModel->versiRpjmdTersedia(
            (int) $periode['tahun_mulai'],
            (int) $periode['tahun_akhir']
        );

        $versiDipilih = $this->versiSumberDipilih(
            $this->request->getGet('renstra_versi'),
            $versiTersedia
        );

        $kandidat = $this->ikuModel->getKandidatSync(
            'rpjmd',
            null,
            $periode['tahun_mulai'],
            $periode['tahun_akhir'],
            $versiDipilih !== null ? (int) $versiDipilih['id'] : null
        );

        return view('adminKabupaten/iku/sync_iku', [
            'title'            => 'Sync IKU dari RPJMD',
            'kandidat'         => $kandidat,
            'daftar_periode'   => $daftarPeriode,
            'periode'          => $periode,
            'years'            => $periode['years'],
            'versi_tersedia'   => $versiTersedia,
            'versi_dipilih'    => $versiDipilih,
            'tanpa_padanan'    => $this->ikuModel->ikuTanpaPadananSumber(),
            // Pratinjau MODE GANTI — kembaran sisi OPD. Dihitung di sini, bukan
            // saat menyimpan, supaya operator melihat lebih dulu apa yang akan
            // dibuang DAN apa yang dipertahankan karena sudah dipakai di
            // cascading / LAKIP / arsip revisi.
            'ganti_pratinjau'  => $this->ikuModel->buangTanpaPadanan(
                null,
                (int) $periode['tahun_mulai'],
                (int) $periode['tahun_akhir'],
                false
            ),
        ] + $this->muaraSync(null, $periode));
    }

    /* =========================================================
     * SYNC DARI RPJMD — SIMPAN
     * POST adminkab/iku/sync/simpan
     * =======================================================*/
    public function syncSimpan()
    {
        if (!user_can('iku_kab.create')) {
            return redirect()->to(base_url('adminkab/iku'))
                ->with('error', 'Anda tidak memiliki akses untuk menyalin data ke IKU.');
        }

        $post    = $this->request->getPost() ?? [];
        $periode = (string) ($post['periode'] ?? '');

        $daftarPeriode = $this->ikuModel->getPeriodeSumber('rpjmd', null);
        if (!isset($daftarPeriode[$periode])) {
            return redirect()->to(base_url('adminkab/iku/sync'))
                ->with('error', 'Periode RPJMD tidak valid.');
        }

        // Seluruh isi RPJMD periode terpilih disalin — pemakai memilih SUMBER,
        // bukan baris per baris. Keranjang dibangun di server dari kandidat
        // yang sama dengan yang dipratinjau.
        // Versi diperiksa ulang terhadap daftar yang sah, bukan dipercaya
        // dari form.
        $versiTersedia = $this->ikuModel->versiRpjmdTersedia(
            (int) $daftarPeriode[$periode]['tahun_mulai'],
            (int) $daftarPeriode[$periode]['tahun_akhir']
        );

        // Sama seperti sisi OPD: id yang dikirim tetapi tidak sah DITOLAK,
        // bukan dibiarkan jatuh ke RPJMD berjalan.
        if ($tolak = $this->tolakVersiSumberTakSah($post['renstra_versi'] ?? null, $versiTersedia)) {
            return $tolak;
        }

        $versiDipilih = $this->versiSumberDipilih($post['renstra_versi'] ?? null, $versiTersedia);
        $versiId      = $versiDipilih !== null ? (int) $versiDipilih['id'] : null;

        $kandidat = $this->ikuModel->getKandidatSync(
            'rpjmd',
            null,
            (int) $daftarPeriode[$periode]['tahun_mulai'],
            (int) $daftarPeriode[$periode]['tahun_akhir'],
            $versiId
        );

        [$pilihan, $perbarui] = $this->keranjangSyncPenuh($kandidat);

        if (empty($pilihan) && empty($perbarui)) {
            // MODE GANTI tetap dikerjakan walau tidak ada yang perlu disalin —
            // justru itu keadaan yang paling lazim baginya: isi IKU sudah sama
            // dengan RPJMD, yang tersisa hanya kelebihan yang tidak ada di sana.
            // Tanpa cabang ini, mencentang Ganti melapor "tidak ada yang perlu
            // disalin" lalu tidak mengerjakan apa pun.
            //
            // Tidak berlaku bila hasil sync bermuara ke draft revisi: di sana
            // IKU berjalan memang tidak disentuh.
            if ($this->request->getPost('ganti')
                && ! $this->muaraSync(null, $daftarPeriode[$periode])['ke_revisi']) {
                return redirect()->to(base_url('adminkab/iku?mode=kabupaten&periode=' . $periode))
                    ->with('success', $this->pesanGanti($daftarPeriode[$periode]));
            }

            return redirect()->to(base_url('adminkab/iku/sync?periode=' . $periode))
                ->with('info', 'IKU Kabupaten sudah sama dengan RPJMD periode ini — tidak ada yang perlu disalin.');
        }

        // Ke mana hasilnya bermuara. Sesudah IKU Kabupaten punya revisi yang
        // berlaku, menyalin langsung ke tabel berjalan berarti mengubah
        // dokumen resmi tanpa jejak revisi — sama persis dengan alasan di
        // sisi OPD.
        $muara = $this->muaraSync(null, $daftarPeriode[$periode]);

        try {
            if ($muara['ke_revisi']) {
                $stat = $this->syncKeDraft(
                    $muara,
                    $post,
                    null,
                    $daftarPeriode[$periode],
                    $versiDipilih,
                    $pilihan,
                    $perbarui,
                    'rpjmd',
                    base_url('adminkab/iku/sync?periode=' . $periode)
                );

                if (! is_array($stat)) {
                    return $stat;
                }
            } else {
                $stat = $this->ikuModel->importSync(
                    'rpjmd',
                    null,
                    $pilihan,
                    $daftarPeriode[$periode]['tahun_mulai'],
                    $daftarPeriode[$periode]['tahun_akhir'],
                    $versiId,
                    $perbarui
                );
            }
        } catch (\Throwable $e) {
            log_message('error', '[IKU SYNC KAB] ' . $e->getMessage());

            return redirect()->to(base_url('adminkab/iku/sync?periode=' . $periode))
                ->with('error', 'Gagal menyalin data RPJMD: ' . $e->getMessage());
        }

        if ($muara['ke_revisi']) {
            return redirect()->to(base_url('adminkab/iku/revisi'))
                ->with('success', $this->pesanHasilSync($stat)
                    . ' Semuanya masuk ke DRAFT revisi — IKU Kabupaten berjalan belum berubah, '
                    . 'dan baru berubah setelah revisi itu disahkan.');
        }

        $pesan = $this->pesanHasilSync($stat);

        // Dijalankan SESUDAH penyalinan supaya baris yang baru saja tertaut
        // lewat tautkanSilsilah() tidak ikut terbuang hanya karena tadi belum
        // bersilsilah.
        if ($this->request->getPost('ganti')) {
            $pesan .= ' ' . $this->pesanGanti($daftarPeriode[$periode]);
        }

        return redirect()->to(base_url('adminkab/iku?mode=kabupaten&periode=' . $periode))
            ->with('success', $pesan);
    }

    /**
     * Kerjakan MODE GANTI untuk lingkup kabupaten, lalu susun laporannya.
     *
     * Dipakai dua kali di `syncSimpan()` — jalur normal dan jalur "tidak ada
     * yang perlu disalin" — jadi teks laporannya tidak perlu ditulis dua kali
     * dan tidak bisa menyimpang satu sama lain.
     */
    private function pesanGanti(array $periode): string
    {
        $buang = $this->ikuModel->buangTanpaPadanan(
            null,
            (int) $periode['tahun_mulai'],
            (int) $periode['tahun_akhir'],
            true
        );

        $pesan = 'Mode Ganti: ' . $buang['dibuang_indikator'] . ' indikator';

        if ($buang['dibuang_sasaran'] > 0) {
            $pesan .= ' & ' . $buang['dibuang_sasaran'] . ' sasaran kosong';
        }

        $pesan .= ' dibuang karena tidak ada di RPJMD.';

        if ($buang['dipertahankan'] !== []) {
            $pesan .= ' ' . count($buang['dipertahankan'])
                . ' dipertahankan karena sudah dipakai (cascading / LAKIP / arsip revisi).';
        }

        return $pesan;
    }

    /* =========================================================
     * FORM EDIT
     * =======================================================*/
    public function edit($sasaranId = null)
    {
        if (!user_can('iku_kab.update')) {
            return redirect()->to(base_url('adminkab/iku'))
                ->with('error', 'Anda tidak memiliki akses untuk mengubah IKU.');
        }

        $sasaran = $this->ikuModel->getSasaranDetail((int) $sasaranId);
        if (!$sasaran) {
            return redirect()->to(base_url('adminkab/iku'))->with('error', 'Data IKU tidak ditemukan.');
        }

        if ($sasaran['opd_id'] !== null) {
            return redirect()->to(base_url('adminkab/iku?mode=opd'))
                ->with('error', 'IKU milik OPD diubah lewat menu IKU pada akun OPD yang bersangkutan.');
        }

        // Redaksi RPJMD ditampilkan berdampingan supaya operator tahu ia
        // sedang menyimpang dari sumbernya — menyimpang itu sah, tidak
        // menyadarinya yang tidak.
        $sasaranRpjmd = null;

        if (! empty($sasaran['source_sasaran_id']) && ($sasaran['source_type'] ?? '') === 'rpjmd') {
            $sasaranRpjmd = \Config\Database::connect()->table('rpjmd_sasaran')
                ->select('sasaran_rpjmd')->where('id', (int) $sasaran['source_sasaran_id'])
                ->get()->getRowArray()['sasaran_rpjmd'] ?? null;
        }

        return view('adminKabupaten/iku/edit_iku', [
            'title'          => 'Edit IKU Kabupaten',
            'iku'            => $sasaran,
            'renameBoleh'    => ! $this->renameSasaranDikunci((int) $sasaranId),
            'sasaranSumber'  => $sasaranRpjmd,
            'satuan_options' => $this->ikuModel->getSatuanOptions(),
        ]);
    }

    /* =========================================================
     * UPDATE
     *
     * Sengaja dibuat sesempit sisi OPD: yang ditulis hanya KETERANGAN tiap
     * indikator, ditambah redaksi sasaran lewat satu medan tersendiri.
     *
     * Sebelumnya method ini memakai bacaFormIku() + updateComplete(), yang
     * menulis ulang sasaran, indikator, satuan, dan SELURUH target sekaligus.
     * Di layar kabupaten jalan itu justru paling berbahaya: satu POST yang
     * dikarang bisa menghapus indikator yang sudah dijangkar Cascading dan
     * dipakai LAKIP. Perubahan substantif berjalan lewat Versi IKU, tempat ia
     * tercatat dan disahkan.
     * =======================================================*/
    public function update()
    {
        if (!user_can('iku_kab.update')) {
            return redirect()->to(base_url('adminkab/iku'))
                ->with('error', 'Anda tidak memiliki akses untuk mengubah IKU.');
        }

        $sasaranId = (int) ($this->request->getPost('iku_sasaran_id') ?? 0);
        if ($sasaranId <= 0) {
            return redirect()->to(base_url('adminkab/iku'))->with('error', 'ID IKU tidak ditemukan.');
        }

        $owner = $this->ikuModel->getSasaranOwner($sasaranId);
        if (!$owner['found']) {
            return redirect()->to(base_url('adminkab/iku'))->with('error', 'Data IKU tidak ditemukan.');
        }
        if ($owner['opd_id'] !== null) {
            return redirect()->to(base_url('adminkab/iku?mode=opd'))
                ->with('error', 'IKU milik OPD diubah lewat menu IKU pada akun OPD yang bersangkutan.');
        }

        $keterangan = (array) ($this->request->getPost('keterangan') ?? []);

        if ($keterangan === []) {
            return redirect()->back()->with('error', 'Tidak ada keterangan yang dikirim.');
        }

        try {
            $jumlah = $this->ikuModel->perbaruiKeterangan($sasaranId, $keterangan);
        } catch (\Throwable $e) {
            log_message('error', '[IKU KETERANGAN KAB] ' . $e->getMessage());

            return redirect()->back()->withInput()
                ->with('error', 'Gagal menyimpan keterangan: ' . $e->getMessage());
        }

        $pesanSasaran = '';
        $teksSasaran  = $this->request->getPost('sasaran_baru');

        if ($teksSasaran !== null && trim((string) $teksSasaran) !== '') {
            if ($this->renameSasaranDikunci($sasaranId)) {
                $pesanSasaran = ' Redaksi sasaran TIDAK diubah: IKU periode ini sudah punya revisi'
                    . ' yang berlaku, jadi perubahannya harus lewat draft revisi agar tercatat.';
            } else {
                $hasil = $this->ikuModel->renameSasaran($sasaranId, (string) $teksSasaran);

                if ($hasil['pesan'] !== '') {
                    $pesanSasaran = ' ' . $hasil['pesan'];
                }

                if (! $hasil['ok'] && $hasil['pesan'] !== '') {
                    return redirect()->back()->withInput()->with('error', $hasil['pesan']);
                }
            }
        }

        return redirect()->to(base_url('adminkab/iku?mode=kabupaten'))
            ->with('success', 'Keterangan ' . $jumlah . ' indikator disimpan. '
                . 'Indikator, satuan, dan target tidak tersentuh.' . $pesanSasaran);
    }

    /**
     * Bolehkah redaksi sasaran diubah langsung?
     *
     * Aturannya SAMA dengan sisi OPD (lihat AdminOpd\IkuController) supaya
     * satu dokumen tidak punya dua aturan yang berbeda:
     *
     *   * belum ada revisi berlaku -> IKU masih disusun, sunting langsung wajar;
     *   * sudah ada revisi berlaku -> dokumennya sudah disahkan, perubahannya
     *     harus lewat draft revisi supaya tercatat dan bisa ditelusuri.
     */
    private function renameSasaranDikunci(int $sasaranId): bool
    {
        $s = \Config\Database::connect()->table('iku_sasaran')
            ->select('opd_id, tahun_mulai, tahun_akhir')
            ->where('id', $sasaranId)->get()->getRowArray();

        if ($s === null) {
            return true;
        }

        $rev = new \App\Models\Opd\IkuRevisiModel();

        return $rev->siap() && $rev->revisiBerlaku(
            $s['opd_id'] !== null ? (int) $s['opd_id'] : null,
            (int) $s['tahun_mulai'],
            (int) $s['tahun_akhir']
        ) !== null;
    }

    /* =========================================================
     * HAPUS
     * =======================================================*/
    public function delete($sasaranId = null)
    {
        if (!user_can('iku_kab.delete')) {
            return redirect()->to(base_url('adminkab/iku'))
                ->with('error', 'Anda tidak memiliki akses untuk menghapus IKU.');
        }

        $owner = $this->ikuModel->getSasaranOwner((int) $sasaranId);

        if (!$owner['found']) {
            return redirect()->to(base_url('adminkab/iku'))->with('error', 'Data IKU tidak ditemukan.');
        }
        if ($owner['opd_id'] !== null) {
            return redirect()->to(base_url('adminkab/iku?mode=opd'))
                ->with('error', 'IKU milik OPD dihapus lewat menu IKU pada akun OPD yang bersangkutan.');
        }

        // Lingkup yang sudah punya revisi disahkan tidak boleh diubah langsung.
        $lingkup = $this->lingkupSasaranIku((int) $sasaranId);

        if ($lingkup !== null
            && ($tolak = $this->tolakBilaIkuSudahResmi($lingkup['opd_id'], $lingkup['tahun_mulai'], $lingkup['tahun_akhir']))) {
            return $tolak;
        }

        try {
            $this->ikuModel->deleteComplete((int) $sasaranId);
            session()->setFlashdata('success', 'IKU berhasil dihapus.');
        } catch (\Throwable $e) {
            session()->setFlashdata('error', 'Gagal menghapus IKU: ' . $e->getMessage());
        }

        return redirect()->to(base_url('adminkab/iku?mode=kabupaten'));
    }

    /* =========================================================
     * UBAH STATUS SATU INDIKATOR (draft <-> selesai)
     * =======================================================*/
    public function change_status($indikatorId = null)
    {
        if (!user_can('iku_kab.update')) {
            return redirect()->back()->with('error', 'Anda tidak memiliki akses untuk mengubah status IKU.');
        }

        $owner = $this->ikuModel->getIndikatorOwner((int) $indikatorId);

        if (!$owner['found']) {
            return redirect()->back()->with('error', 'Indikator IKU tidak ditemukan.');
        }
        if ($owner['opd_id'] !== null) {
            return redirect()->back()
                ->with('error', 'Status IKU milik OPD diubah lewat menu IKU pada akun OPD yang bersangkutan.');
        }

        $lingkup = $this->lingkupSasaranIku((int) ($owner['iku_sasaran_id'] ?? 0));

        if ($lingkup !== null
            && ($tolak = $this->tolakBilaIkuSudahResmi($lingkup['opd_id'], $lingkup['tahun_mulai'], $lingkup['tahun_akhir']))) {
            return $tolak;
        }

        $statusBaru = $this->ikuModel->toggleStatusIndikator((int) $indikatorId);

        return redirect()->back()
            ->with('success', 'Status IKU berhasil diubah menjadi ' . ucfirst((string) $statusBaru) . '.');
    }

    /* =========================================================
     * CETAK PDF
     * =======================================================*/
    public function cetak()
    {
        $mode      = $this->modeAktif();
        $opdFilter = $this->opdFilter();

        [, $periode] = $this->resolvePeriode($mode, $opdFilter);

        if (empty($periode)) {
            return redirect()->to(base_url('adminkab/iku'))
                ->with('error', 'Belum ada data IKU yang bisa dicetak.');
        }

        $ikuData = $this->ikuModel->getMatrix([
            'level'       => $mode,
            'opd_id'      => $mode === 'opd' ? $opdFilter : null,
            'tahun_mulai' => $periode['tahun_mulai'],
            'tahun_akhir' => $periode['tahun_akhir'],
        ]);

        $namaOpd = '';
        if ($mode === 'opd' && $opdFilter !== null) {
            $opd     = $this->opdModel->find($opdFilter);
            $namaOpd = $opd['nama_opd'] ?? '';
        }

        if (ob_get_level() > 0) {
            @ob_clean();
        }

        $html = view('adminKabupaten/iku/iku_cetak', [
            'mode'        => $mode,
            'iku_data'    => $ikuData,
            'years'       => $periode['years'],
            'periode_txt' => $periode['label'],
            'opd_name'    => $namaOpd,
        ]);

        $mpdf = new \Mpdf\Mpdf([
            'mode'          => 'utf-8',
            'format'        => 'A4-L',
            'margin_left'   => 10,
            'margin_right'  => 10,
            'margin_top'    => 12,
            'margin_bottom' => 10,
            'margin_header' => 0,
            'margin_footer' => 0,
            'tempDir'       => sys_get_temp_dir(),
        ]);

        helper('setting');
        $mpdf->SetHTMLFooter(pdf_footer_aksara());
        pdf_watermark_aksara($mpdf);
        $mpdf->SetDisplayMode('fullpage');
        $mpdf->WriteHTML($html);

        $this->response->setHeader('Content-Type', 'application/pdf');
        $mpdf->Output('IKU-' . ($mode === 'kabupaten' ? 'KAB' : 'OPD') . '-' . $periode['key'] . '.pdf', 'I');
        exit;
    }

    /* =========================================================
     * HELPER PRIVAT
     * =======================================================*/

    private function modeAktif(): string
    {
        return $this->request->getGet('mode') === 'opd' ? 'opd' : 'kabupaten';
    }

    private function opdFilter(): ?int
    {
        $opdFilter = $this->request->getGet('opd_id');

        return ($opdFilter === null || $opdFilter === '') ? null : (int) $opdFilter;
    }

    /**
     * Tentukan periode aktif: dari query string kalau ada, kalau tidak pilih
     * periode yang memuat tahun berjalan, kalau tetap tidak ada pakai yang pertama.
     *
     * @return array{0: array<string, mixed>, 1: array<string, mixed>}
     */
    private function resolvePeriode(string $mode, ?int $opdFilter): array
    {
        $groupedData = $this->ikuModel->getPeriodeOptions($mode, $mode === 'opd' ? $opdFilter : null);

        if (empty($groupedData)) {
            return [[], []];
        }

        $dipilih = $this->request->getGet('periode');
        if (empty($dipilih) || !isset($groupedData[$dipilih])) {
            $dipilih       = null;
            $tahunSekarang = (int) date('Y');

            foreach ($groupedData as $key => $p) {
                if (in_array($tahunSekarang, $p['years'], true)) {
                    $dipilih = $key;
                    break;
                }
            }

            $dipilih ??= array_key_first($groupedData);
        }

        $p = $groupedData[$dipilih];

        return [$groupedData, [
            'key'         => $dipilih,
            'label'       => $p['period'],
            'years'       => $p['years'],
            'tahun_mulai' => $p['tahun_mulai'],
            'tahun_akhir' => $p['tahun_akhir'],
        ]];
    }
}
