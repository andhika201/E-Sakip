<?php

namespace App\Controllers\AdminOpd;

use App\Controllers\BaseController;
use App\Controllers\Concerns\IkuFormTrait;
use App\Controllers\Concerns\IkuRevisiTrait;
use App\Models\Opd\IkuModel;
use App\Models\Opd\IkuRevisiModel;
use App\Models\OpdModel;

/**
 * IKU tingkat OPD / Kecamatan — STANDALONE.
 *
 * Sejak 2026-07-27 IKU tidak lagi menempel ke RENSTRA: sasaran, indikator,
 * satuan, dan target per tahun diinput langsung di modul ini dan disimpan di
 * `iku_sasaran` / `iku_indikator` / `iku_target` dengan `iku_sasaran.opd_id`
 * sebagai penanda pemilik.
 *
 * Dipakai role admin_opd dan admin_kecamatan (keduanya punya `opd_id` di sesi).
 * Super admin (role `admin`, tanpa opd_id) melihat data seluruh OPD.
 */
class IkuController extends BaseController
{
    use IkuFormTrait;

    /** Revisi IKU: versi dokumen yang bernomor & bertanggal berlaku. */
    use IkuRevisiTrait;

    /* =========================================================
     * LINGKUP REVISI (dipakai IkuRevisiTrait)
     * =======================================================*/

    protected function revisiPermPrefix(): string
    {
        return 'iku_opd';
    }

    /**
     * OPD diambil dari SESSION, tidak pernah dari request — pola yang sama
     * dengan LakipAddendumTrait::lakipScope(). Ini yang mencegah satu OPD
     * merevisi IKU OPD lain lewat parameter yang ditebak.
     */
    protected function revisiOpdId(): ?int
    {
        $opdId = (int) session()->get('opd_id');

        return $opdId > 0 ? $opdId : null;
    }

    protected function revisiBaseUrl(): string
    {
        return 'adminopd/iku';
    }

    protected IkuModel $ikuModel;
    protected OpdModel $opdModel;

    public function __construct()
    {
        $this->ikuModel = new IkuModel();
        $this->opdModel = new OpdModel();
    }

    /* =========================================================
     * LIST
     * =======================================================*/
    public function index()
    {
        $opdId = $this->opdIdSesi();
        if ($opdId === false) {
            return redirect()->to('/login')->with('error', 'Silakan login terlebih dahulu');
        }

        [$groupedData, $periode] = $this->resolvePeriode($opdId);

        $ikuData = $this->ikuModel->getMatrix([
            'level'       => 'opd',
            'opd_id'      => $opdId,
            'tahun_mulai' => $periode['tahun_mulai'] ?? null,
            'tahun_akhir' => $periode['tahun_akhir'] ?? null,
        ]);

        $rev = new IkuRevisiModel();

        return view('adminOpd/iku/iku', [
            // Keadaan pengesahan: yang menunggu keputusan, dan yang sedang berlaku.
            'revisiMenunggu' => $rev->siap() && ! empty($periode['tahun_mulai'])
                ? $rev->berjalanMenunggu($opdId, (int) $periode['tahun_mulai'], (int) $periode['tahun_akhir'])
                : null,
            'revisiBerlaku'  => $rev->siap() && ! empty($periode['tahun_mulai'])
                ? $rev->revisiBerlaku($opdId, (int) $periode['tahun_mulai'], (int) $periode['tahun_akhir'])
                : null,
            'title'            => 'Indikator Kinerja Utama',
            'iku_data'         => $ikuData,
            'grouped_data'     => $groupedData,
            'selected_periode' => $periode['key'] ?? null,
            'years'            => $periode['years'] ?? [],
            'role'             => session()->get('role'),
            'is_lintas_opd'    => $opdId === null,
        ]);
    }

    /* =========================================================
     * FORM TAMBAH
     * =======================================================*/
    /** Pintu SASARAN MANDIRI lama — ditutup. Lihat tolakPintuMandiri(). */
    public function tambah()
    {
        // =================================================================
        // PINTU INI DITUTUP — SASARAN MANDIRI KINI LAHIR DI DALAM REVISI
        //
        // Jalur lama menulis LANGSUNG ke tabel live lewat createComplete(),
        // di luar alur revisi. Akibatnya IKU berjalan berubah tanpa revisi dan
        // tanpa pengesahan, dan sasarannya tidak pernah masuk arsip versi mana
        // pun — sehingga ia tampil di layar IKU tetapi TIDAK ADA di dokumen
        // penilaian tahun mana pun, dan LAKIP tidak bisa menilainya.
        //
        // Tombolnya sudah dicabut dari layar, tetapi RUTE-nya masih bisa
        // diketik langsung — dan pintu belakang yang menulis ke tabel live
        // sama berbahayanya dengan tombol. Karena itu ditutup di controller,
        // bukan sekadar disembunyikan di view.
        //
        // Method save() di bawah ikut ditutup dengan alasan yang sama.
        // =================================================================
        return $this->tolakPintuMandiri();
    }

    /**
     * Penolakan bersama untuk pintu sasaran mandiri lama.
     *
     * Diarahkan ke Versi IKU, bukan sekadar ditolak: pemakai yang sampai ke
     * sini memang sedang hendak menambah sasaran, dan menyebut tempat barunya
     * jauh lebih berguna daripada pesan "akses ditolak".
     */
    private function tolakPintuMandiri()
    {
        return redirect()->to(base_url('adminopd/iku/revisi'))->with(
            'error',
            'Menambah sasaran mandiri kini dilakukan di dalam Versi IKU: buat revisi '
            . '(atau buka draft yang sudah ada), lalu tekan "Tambah Sasaran" di layar '
            . 'suntingnya. Dengan begitu sasaran barunya ikut diperiksa Admin Kabupaten '
            . 'dan tercatat pada dokumen penilaian tahun berjalan.'
        );
    }

    /**
     * Penyimpanan sasaran mandiri lama — ditutup bersama tambah().
     *
     * Ditutup di sini, bukan sekadar dengan menghapus formnya: POST bisa
     * dikirim tanpa membuka form mana pun, dan jalur ini menulis LANGSUNG ke
     * `iku_sasaran`/`iku_indikator` di luar alur revisi.
     */
    public function save()
    {
        return $this->tolakPintuMandiri();
    }

    /* =========================================================
     * SYNC DARI RENSTRA — PRATINJAU
     * GET adminopd/iku/sync?periode=2025-2029
     * =======================================================*/

    /* =========================================================
     * AJUKAN PENGESAHAN IKU (dari menu IKU, bukan menu Revisi)
     *
     * Kembaran "Ajukan Validasi" pada menu Renstra. Sebelum ini, satu-satunya
     * jalan mengesahkan IKU adalah lewat menu Revisi — dan menu itu kosong
     * sampai seseorang membuat revisi, sehingga IKU hasil sync tidak punya
     * pintu apa pun menuju pengesahan.
     * =======================================================*/

    public function ajukanPengesahan()
    {
        $opdId = $this->opdIdSesi();

        if ($opdId === false) {
            return redirect()->to('/login')->with('error', 'Silakan login terlebih dahulu');
        }

        if (! user_can('iku_opd.revisi')) {
            return redirect()->to(base_url('adminopd/iku'))
                ->with('error', 'Anda tidak berwenang mengajukan pengesahan IKU.');
        }

        $tm = (int) $this->request->getPost('tahun_mulai');
        $ta = (int) $this->request->getPost('tahun_akhir');

        if ($tm <= 0 || $ta < $tm) {
            return redirect()->back()->with('error', 'Periode IKU tidak sah.');
        }

        $kembali = base_url('adminopd/iku?periode=' . $tm . '-' . $ta);

        try {
            (new IkuRevisiModel())->bekukanDanAjukan($opdId, $tm, $ta, $this->penggunaIku(), [
                'catatan' => $this->request->getPost('catatan') ?: null,
            ]);
        } catch (\Throwable $e) {
            return redirect()->to($kembali)->with('error', $e->getMessage());
        }

        return redirect()->to($kembali)->with('success',
            'IKU periode ' . $tm . '-' . $ta . ' diajukan untuk disahkan Admin Kabupaten. '
            . 'Isinya dibekukan apa adanya saat ini — perubahan sesudah ini tidak ikut terkirim.');
    }

    private function penggunaIku(): ?int
    {
        $id = session()->get('user_id') ?? session()->get('id');

        return $id === null ? null : (int) $id;
    }

    public function sync()
    {
        $opdId = $this->opdIdSesi();
        if ($opdId === false) {
            return redirect()->to('/login')->with('error', 'Silakan login terlebih dahulu');
        }

        if (!user_can('iku_opd.create')) {
            return redirect()->to(base_url('adminopd/iku'))
                ->with('error', 'Anda tidak memiliki akses untuk menyalin data ke IKU.');
        }

        // Sync selalu terikat satu OPD — super admin lintas OPD harus memilih dulu.
        $opdId = $this->opdSyncTerpilih($opdId);
        if ($opdId === null) {
            return redirect()->to(base_url('adminopd/iku'))
                ->with('error', 'Akun ini tidak terikat ke satu OPD, jadi sync Renstra tidak bisa dijalankan dari sini.');
        }

        [$daftarPeriode, $periode] = $this->resolvePeriodeSumber('renstra', $opdId);

        if (empty($periode)) {
            return redirect()->to(base_url('adminopd/iku'))
                ->with('error', 'Belum ada periode Renstra pada OPD ini yang bisa disalin. Isi Renstra terlebih dahulu.');
        }

        // Versi Renstra yang jadi titik tolak. Tanpa parameter ini, sumbernya
        // kondisi berjalan — perilaku lama, apa adanya.
        $versiTersedia = $this->ikuModel->versiRenstraTersedia(
            $opdId,
            (int) $periode['tahun_mulai'],
            (int) $periode['tahun_akhir']
        );

        $versiDipilih = $this->versiRenstraDipilih(
            $this->request->getGet('renstra_versi'),
            $versiTersedia
        );

        $kandidat = $this->ikuModel->getKandidatSync(
            'renstra',
            $opdId,
            $periode['tahun_mulai'],
            $periode['tahun_akhir'],
            $versiDipilih !== null ? (int) $versiDipilih['id'] : null
        );

        $opd = $this->opdModel->find($opdId);

        return view('adminOpd/iku/sync_iku', [
            'title'          => 'Sync IKU dari Renstra',
            'kandidat'       => $kandidat,
            'daftar_periode' => $daftarPeriode,
            'periode'        => $periode,
            'years'          => $periode['years'],
            'nama_opd'       => $opd['nama_opd'] ?? '',
            'role'           => session()->get('role'),
            'versi_tersedia' => $versiTersedia,
            'versi_dipilih'  => $versiDipilih,
            'tanpa_padanan'  => $this->ikuModel->ikuTanpaPadananSumber(),
            // Pratinjau MODE GANTI. Dihitung di sini, bukan saat menyimpan,
            // supaya operator melihat lebih dulu apa yang akan dibuang DAN apa
            // yang dipertahankan karena sudah dipakai di tempat lain.
            'ganti_pratinjau' => $this->ikuModel->buangTanpaPadanan(
                $opdId,
                (int) $periode['tahun_mulai'],
                (int) $periode['tahun_akhir'],
                false
            ),
        ] + $this->muaraSync($opdId, $periode));
    }

    /* =========================================================
     * SYNC DARI RENSTRA — SIMPAN
     * POST adminopd/iku/sync/simpan
     * =======================================================*/
    public function syncSimpan()
    {
        $opdId = $this->opdIdSesi();
        if ($opdId === false) {
            return redirect()->to('/login')->with('error', 'Silakan login terlebih dahulu');
        }

        if (!user_can('iku_opd.create')) {
            return redirect()->to(base_url('adminopd/iku'))
                ->with('error', 'Anda tidak memiliki akses untuk menyalin data ke IKU.');
        }

        $opdId = $this->opdSyncTerpilih($opdId);
        if ($opdId === null) {
            return redirect()->to(base_url('adminopd/iku'))
                ->with('error', 'Akun ini tidak terikat ke satu OPD, jadi sync Renstra tidak bisa dijalankan dari sini.');
        }

        $post    = $this->request->getPost() ?? [];
        $periode = (string) ($post['periode'] ?? '');

        $daftarPeriode = $this->ikuModel->getPeriodeSumber('renstra', $opdId);
        if (!isset($daftarPeriode[$periode])) {
            return redirect()->to(base_url('adminopd/iku/sync'))
                ->with('error', 'Periode Renstra tidak valid.');
        }

        // Versi diperiksa ulang terhadap daftar yang sah, bukan dipercaya dari
        // form: id karangan tidak boleh membuka arsip OPD lain.
        $versiTersedia = $this->ikuModel->versiRenstraTersedia(
            $opdId,
            (int) $daftarPeriode[$periode]['tahun_mulai'],
            (int) $daftarPeriode[$periode]['tahun_akhir']
        );

        $versiDipilih = $this->versiRenstraDipilih($post['renstra_versi'] ?? null, $versiTersedia);

        // Seluruh isi sumber terpilih disalin — pemakai memilih SUMBER, bukan
        // baris per baris. Keranjangnya dibangun dari kandidat yang sama
        // dengan yang dipratinjau, di server.
        $kandidat = $this->ikuModel->getKandidatSync(
            'renstra',
            $opdId,
            (int) $daftarPeriode[$periode]['tahun_mulai'],
            (int) $daftarPeriode[$periode]['tahun_akhir'],
            $versiDipilih !== null ? (int) $versiDipilih['id'] : null
        );

        [$pilihan, $perbarui] = $this->keranjangSyncPenuh($kandidat);

        if (empty($pilihan) && empty($perbarui)) {
            // "Tidak ada yang perlu DISALIN" bukan berarti tidak ada yang perlu
            // DIBUANG. Mode Ganti justru paling sering dipakai persis di keadaan
            // ini: isi IKU sudah sama dengan Renstra, yang tersisa hanya
            // kelebihan yang tidak ada di sana. Tanpa cabang ini, mencentang
            // Ganti melapor "tidak ada yang perlu disalin" lalu tidak
            // mengerjakan apa pun.
            // `$muara` baru dihitung di bawah; di cabang ini ia ditanyakan
            // langsung. Mode Ganti tidak berlaku bila hasil sync bermuara ke
            // draft revisi — di sana IKU berjalan memang tidak disentuh.
            if ($this->request->getPost('ganti')
                && ! $this->muaraSync($opdId, $daftarPeriode[$periode])['ke_revisi']) {
                $buang = $this->ikuModel->buangTanpaPadanan(
                    $opdId,
                    (int) $daftarPeriode[$periode]['tahun_mulai'],
                    (int) $daftarPeriode[$periode]['tahun_akhir'],
                    true
                );

                $pesan = 'Mode Ganti: ' . $buang['dibuang_indikator'] . ' indikator';

                if ($buang['dibuang_sasaran'] > 0) {
                    $pesan .= ' & ' . $buang['dibuang_sasaran'] . ' sasaran kosong';
                }

                $pesan .= ' dibuang karena tidak ada di Renstra.';

                if ($buang['dipertahankan'] !== []) {
                    $pesan .= ' ' . count($buang['dipertahankan'])
                        . ' dipertahankan karena sudah dipakai (cascading / LAKIP / arsip revisi).';
                }

                return redirect()->to(base_url('adminopd/iku?periode=' . $periode))->with('success', $pesan);
            }

            return redirect()->to(base_url('adminopd/iku/sync?periode=' . $periode))
                ->with('info', 'IKU sudah sama dengan sumber ini — tidak ada yang perlu disalin.');
        }

        $muara = $this->muaraSync($opdId, $daftarPeriode[$periode]);

        try {
            if ($muara['ke_revisi']) {
                $stat = $this->syncKeDraft($muara, $post, $opdId, $daftarPeriode[$periode],
                    $versiDipilih, $pilihan, $perbarui, 'renstra',
                    base_url('adminopd/iku/sync?periode=' . $periode));

                if (! is_array($stat)) {
                    return $stat;
                }
            } else {
                $stat = $this->ikuModel->importSync(
                    'renstra',
                    $opdId,
                    $pilihan,
                    $daftarPeriode[$periode]['tahun_mulai'],
                    $daftarPeriode[$periode]['tahun_akhir'],
                    $versiDipilih !== null ? (int) $versiDipilih['id'] : null,
                    $perbarui
                );
            }
        } catch (\Throwable $e) {
            log_message('error', '[IKU SYNC OPD] ' . $e->getMessage());

            return redirect()->to(base_url('adminopd/iku/sync?periode=' . $periode))
                ->with('error', 'Gagal menyalin data Renstra: ' . $e->getMessage());
        }

        if ($muara['ke_revisi']) {
            return redirect()->to(base_url('adminopd/iku/revisi'))
                ->with('success', $this->pesanHasilSync($stat)
                    . ' Semuanya masuk ke DRAFT revisi — IKU berjalan belum berubah, '
                    . 'dan baru berubah setelah revisi itu diajukan dan disahkan.');
        }

        $pesan = $this->pesanHasilSync($stat);

        // MODE GANTI. Dijalankan SESUDAH penyalinan supaya baris yang baru saja
        // tertaut lewat tautkanSilsilah() tidak ikut terbuang hanya karena tadi
        // belum bersilsilah. Baris yang sudah dipakai di tempat lain tidak
        // pernah dibuang — penjaganya ada di buangTanpaPadanan().
        if ($this->request->getPost('ganti')) {
            $buang = $this->ikuModel->buangTanpaPadanan(
                $opdId,
                (int) $daftarPeriode[$periode]['tahun_mulai'],
                (int) $daftarPeriode[$periode]['tahun_akhir'],
                true
            );

            $pesan .= ' Mode Ganti: ' . $buang['dibuang_indikator'] . ' indikator';

            if ($buang['dibuang_sasaran'] > 0) {
                $pesan .= ' & ' . $buang['dibuang_sasaran'] . ' sasaran kosong';
            }

            $pesan .= ' dibuang karena tidak ada di Renstra.';

            if ($buang['dipertahankan'] !== []) {
                $pesan .= ' ' . count($buang['dipertahankan'])
                    . ' dipertahankan karena sudah dipakai (cascading / LAKIP / arsip revisi).';
            }
        }

        return redirect()->to(base_url('adminopd/iku?periode=' . $periode))
            ->with('success', $pesan);
    }

    

    /**
     * Ke mana hasil sync bermuara: tabel berjalan atau draft revisi.
     *
     * Selama IKU belum punya revisi yang berlaku, ia memang masih disusun dan
     * menyalin langsung ke tabel berjalan sama wajarnya dengan mengetik
     * manual. Sesudah ada revisi yang disahkan, menambah indikator langsung ke
     * tabel berjalan berarti mengubah dokumen resmi tanpa sepengetahuan
     * siapa pun — dan tambahan itu tidak akan muncul di arsip revisi mana pun.
     *
     * @return array{ke_revisi:bool, revisi_berlaku:?array, draft_tersedia:array}
     */


    /**
     * Versi Renstra yang dipilih sebagai sumber, atau null bila memakai
     * kondisi berjalan.
     *
     * Nilainya TIDAK dipercaya begitu saja: harus ada pada daftar versi yang
     * sudah dibatasi OPD dan periodenya. Dengan begitu tidak ada jalan membaca
     * arsip OPD lain lewat id yang dikarang.
     *
     * @param array<int,array<string,mixed>> $tersedia
     */
    private function versiRenstraDipilih($nilai, array $tersedia): ?array
    {
        $id = (int) $nilai;

        if ($id <= 0) {
            return null;
        }

        foreach ($tersedia as $v) {
            if ((int) $v['id'] === $id) {
                return $v;
            }
        }

        return null;
    }

    /**
     * OPD yang jadi sasaran sync.
     *
     * admin_opd/admin_kecamatan terkunci ke OPD-nya sendiri. Super admin lintas
     * OPD boleh menyebut ?opd_id= karena sync wajib terikat tepat satu OPD.
     */
    private function opdSyncTerpilih(?int $opdSesi): ?int
    {
        if ($opdSesi !== null) {
            return $opdSesi;
        }

        $dari = $this->request->getGet('opd_id') ?? $this->request->getPost('opd_id');
        $dari = (int) $dari;

        return $dari > 0 ? $dari : null;
    }

    /* =========================================================
     * FORM EDIT
     * =======================================================*/
    public function edit($sasaranId = null)
    {
        $opdId = $this->opdIdSesi();
        if ($opdId === false) {
            return redirect()->to('/login')->with('error', 'Silakan login terlebih dahulu');
        }

        if (!user_can('iku_opd.update')) {
            return redirect()->to(base_url('adminopd/iku'))
                ->with('error', 'Anda tidak memiliki akses untuk mengubah IKU.');
        }

        $sasaran = $this->ikuModel->getSasaranDetail((int) $sasaranId);
        if (!$sasaran) {
            return redirect()->to(base_url('adminopd/iku'))->with('error', 'Data IKU tidak ditemukan.');
        }

        if (!$this->bolehAksesSasaran((int) $sasaranId)) {
            return redirect()->to(base_url('adminopd/iku'))
                ->with('error', 'Anda tidak memiliki akses ke IKU OPD lain.');
        }

        // Redaksi Renstra ditampilkan berdampingan supaya operator tahu ia
        // sedang menyimpang dari sumbernya — menyimpang itu sah, tidak
        // menyadarinya yang tidak.
        $sasaranRenstra = null;

        if (! empty($sasaran['source_sasaran_id']) && ($sasaran['source_type'] ?? '') === 'renstra') {
            $sasaranRenstra = \Config\Database::connect()->table('renstra_sasaran')
                ->select('sasaran')->where('id', (int) $sasaran['source_sasaran_id'])
                ->get()->getRowArray()['sasaran'] ?? null;
        }

        return view('adminOpd/iku/edit_iku', [
            'title'          => 'Edit IKU',
            'iku'            => $sasaran,
            'renameBoleh'    => ! $this->renameSasaranDikunci((int) $sasaranId),
            'sasaranRenstra' => $sasaranRenstra,
            'satuan_options' => $this->ikuModel->getSatuanOptions(),
            'opd_list'       => $opdId === null ? $this->opdModel->orderBy('nama_opd', 'ASC')->findAll() : [],
            'is_lintas_opd'  => $opdId === null,
            'role'           => session()->get('role'),
        ]);
    }

    /* =========================================================
     * UPDATE
     * =======================================================*/
    /**
     * Simpan KETERANGAN indikator saja.
     *
     * =====================================================================
     * PEMBATASANNYA DI SERVER, BUKAN DI LAYAR
     *
     * Sebelumnya method ini membaca seluruh form lewat `bacaFormIku()` dan
     * memanggil `updateComplete()` — yang menulis ulang sasaran, indikator,
     * satuan, dan SELURUH targetnya dari apa pun yang dikirim. Menyembunyikan
     * medannya di layar tidak menutup jalan itu: POST yang dikarang tetap bisa
     * menghapus target dan indikator, tanpa satu pun galat.
     *
     * Karena itu yang dibaca sekarang hanya `keterangan[<id indikator>][...]`,
     * dan yang ditulis hanya empat kolom. Perubahan substantif berjalan lewat
     * revisi IKU, tempat ia tercatat dan disahkan.
     */
    public function update()
    {
        $opdId = $this->opdIdSesi();

        if ($opdId === false) {
            return redirect()->to('/login')->with('error', 'Silakan login terlebih dahulu');
        }

        if (! user_can('iku_opd.update')) {
            return redirect()->to(base_url('adminopd/iku'))
                ->with('error', 'Anda tidak memiliki akses untuk mengubah IKU.');
        }

        $sasaranId = (int) ($this->request->getPost('iku_sasaran_id') ?? 0);

        if ($sasaranId <= 0) {
            return redirect()->to(base_url('adminopd/iku'))->with('error', 'ID IKU tidak ditemukan.');
        }

        if (! $this->bolehAksesSasaran($sasaranId)) {
            return redirect()->to(base_url('adminopd/iku'))
                ->with('error', 'Anda tidak memiliki akses ke IKU OPD lain.');
        }

        $keterangan = (array) ($this->request->getPost('keterangan') ?? []);

        if ($keterangan === []) {
            return redirect()->back()->with('error', 'Tidak ada keterangan yang dikirim.');
        }

        try {
            $jumlah = $this->ikuModel->perbaruiKeterangan($sasaranId, $keterangan);
        } catch (\Throwable $e) {
            log_message('error', '[IKU KETERANGAN OPD] ' . $e->getMessage());

            return redirect()->back()->withInput()
                ->with('error', 'Gagal menyimpan keterangan: ' . $e->getMessage());
        }

        // ---- REDAKSI SASARAN -------------------------------------------
        //
        // Dibaca sebagai medan tersendiri, bukan lewat pembacaan form
        // menyeluruh — mengikuti sikap method ini: yang ditulis hanya kolom
        // yang disebut namanya di sini, sehingga POST yang dikarang tidak bisa
        // menyentuh indikator, satuan, atau target.
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

        return redirect()->to(base_url('adminopd/iku'))
            ->with('success', 'Keterangan ' . $jumlah . ' indikator disimpan. '
                . 'Indikator, satuan, dan target tidak tersentuh.' . $pesanSasaran);
    }

    /**
     * Bolehkah redaksi sasaran diubah langsung?
     *
     * Aturannya SAMA dengan yang sudah dipakai Sync (lihat
     * IkuFormTrait::muaraSync()), supaya operator tidak perlu menghafal dua
     * aturan berbeda untuk satu dokumen yang sama:
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

    public function delete($sasaranId = null)
    {
        if (!user_can('iku_opd.delete')) {
            return redirect()->to(base_url('adminopd/iku'))
                ->with('error', 'Anda tidak memiliki akses untuk menghapus IKU.');
        }

        $sasaranId = (int) $sasaranId;

        if (!$this->bolehAksesSasaran($sasaranId)) {
            return redirect()->to(base_url('adminopd/iku'))
                ->with('error', 'Anda tidak memiliki akses untuk menghapus IKU OPD lain.');
        }

        try {
            $this->ikuModel->deleteComplete($sasaranId);
            session()->setFlashdata('success', 'Data IKU berhasil dihapus.');
        } catch (\Throwable $e) {
            session()->setFlashdata('error', 'Gagal menghapus IKU: ' . $e->getMessage());
        }

        return redirect()->to(base_url('adminopd/iku'));
    }

    /* =========================================================
     * UBAH STATUS SATU INDIKATOR (draft <-> selesai)
     * =======================================================*/
    public function change_status($indikatorId = null)
    {
        if (!user_can('iku_opd.update')) {
            return redirect()->back()->with('error', 'Anda tidak memiliki akses untuk mengubah status IKU.');
        }

        $owner = $this->ikuModel->getIndikatorOwner((int) $indikatorId);

        if (!$owner['found']) {
            return redirect()->back()->with('error', 'Indikator IKU tidak ditemukan.');
        }

        if (!$this->canAccessOpd($owner['opd_id'])) {
            return redirect()->back()->with('error', 'Anda tidak memiliki akses ke IKU OPD lain.');
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
        $opdId = $this->opdIdSesi();
        if ($opdId === false) {
            return redirect()->to('/login')->with('error', 'Silakan login terlebih dahulu');
        }

        [, $periode] = $this->resolvePeriode($opdId);

        if (empty($periode)) {
            return redirect()->to(base_url('adminopd/iku'))
                ->with('error', 'Belum ada data IKU yang bisa dicetak.');
        }

        $ikuData = $this->ikuModel->getMatrix([
            'level'       => 'opd',
            'opd_id'      => $opdId,
            'tahun_mulai' => $periode['tahun_mulai'],
            'tahun_akhir' => $periode['tahun_akhir'],
        ]);

        $opd = $opdId ? $this->opdModel->find($opdId) : null;

        if (ob_get_level() > 0) {
            @ob_clean();
        }

        $html = view('adminOpd/iku/iku_cetak', [
            'iku_data'    => $ikuData,
            'years'       => $periode['years'],
            'periode_txt' => $periode['label'],
            'nama_opd'    => $opd['nama_opd'] ?? '',
            'lintas_opd'  => $opdId === null,
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
        $namaOpd  = trim((string) ($opd['nama_opd'] ?? ''));
        $namaFile = $namaOpd !== '' ? preg_replace('/[^A-Za-z0-9]+/', '-', $namaOpd) . '-' : '';
        $mpdf->Output('IKU-OPD-' . $namaFile . $periode['key'] . '.pdf', 'I');
        exit;
    }

    /* =========================================================
     * HELPER PRIVAT
     * =======================================================*/

    /**
     * opd_id dari sesi.
     *
     * @return int|null|false int  = admin_opd / admin_kecamatan,
     *                        null = super admin (lintas OPD),
     *                        false = belum login
     */
    private function opdIdSesi()
    {
        $session = session();
        $opdId   = $session->get('opd_id');
        $role    = $session->get('role');

        if (!empty($opdId)) {
            return (int) $opdId;
        }

        // Tanpa opd_id hanya boleh role tingkat kabupaten (super admin).
        return $role ? null : false;
    }

    /** Cek otorisasi lintas-OPD untuk satu sasaran IKU (IDOR). */
    private function bolehAksesSasaran(int $sasaranId): bool
    {
        $owner = $this->ikuModel->getSasaranOwner($sasaranId);

        return $owner['found'] && $this->canAccessOpd($owner['opd_id']);
    }

    /**
     * Tentukan periode aktif: dari query string kalau ada, kalau tidak pilih
     * periode yang memuat tahun berjalan, kalau tetap tidak ada pakai yang pertama.
     *
     * @return array{0: array<string, mixed>, 1: array<string, mixed>}
     */
    private function resolvePeriode(?int $opdId): array
    {
        $groupedData = $this->ikuModel->getPeriodeOptions('opd', $opdId);

        if (empty($groupedData)) {
            return [[], []];
        }

        $dipilih = $this->request->getGet('periode');
        if (empty($dipilih) || !isset($groupedData[$dipilih])) {
            $dipilih     = null;
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
