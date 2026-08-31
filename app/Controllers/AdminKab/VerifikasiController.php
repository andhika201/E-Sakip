<?php

namespace App\Controllers\AdminKab;

use App\Controllers\BaseController;
use App\Models\DokumenVersiModel;
use App\Services\Version\ArsipRegistry;
use App\Services\Version\VersionApprovalService;
use App\Services\Version\VersionAuditService;
use App\Services\Version\VersionCompareService;
use App\Models\Opd\IkuRevisiModel;
use App\Services\Version\IzinSuntingService;
use App\Services\Version\VersionCorrectionService;
use App\Services\Version\VersionResolver;
use App\Services\Version\VersionScope;
use Throwable;

/**
 * Antrean verifikasi versi dokumen untuk Admin Kabupaten (§17, §47).
 *
 * =====================================================================
 * MENGAPA CONTROLLER TERSENDIRI, BUKAN MENUMPANG DokumenVersiTrait
 *
 * Trait itu mengurung setiap aksi pada lingkup pemakainya — `versiMilikSaya()`
 * menolak versi milik OPD lain, dan itu memang yang benar untuk Admin OPD.
 *
 * Verifikator justru harus melihat pengajuan LINTAS OPD. Kalau kemampuan itu
 * ditambahkan ke trait, satu kekeliruan kecil di sana akan membocorkan versi
 * OPD lain ke Admin OPD. Dipisah, batas keduanya jelas dan tidak bisa tertukar.
 *
 * Sebagai gantinya, wewenang di sini dijaga permission `<modul>.version.verify`
 * yang hanya diberikan ke admin_kab — diperiksa per modul, bukan sekali di awal.
 * =====================================================================
 */
class VerifikasiController extends BaseController
{
    /** Modul yang bisa masuk antrean verifikasi. */
    private const MODUL = [
        VersionScope::MODUL_RPJMD,
        VersionScope::MODUL_RENSTRA,
        VersionScope::MODUL_IKU,
        VersionScope::MODUL_LAKIP,
    ];

    private ?DokumenVersiModel $versiModel = null;

    private function versi(): DokumenVersiModel
    {
        return $this->versiModel ??= new DokumenVersiModel();
    }

    /** True bila pemakai boleh memverifikasi modul ini. */
    private function bolehVerifikasi(string $modul): bool
    {
        return function_exists('user_can') && user_can($modul . '.version.verify');
    }

    /** @return string[] modul yang boleh diverifikasi pemakai ini */
    private function modulBoleh(): array
    {
        return array_values(array_filter(self::MODUL, fn ($m) => $this->bolehVerifikasi($m)));
    }

    /* =========================================================
     * ANTREAN
     * =======================================================*/

    public function index()
    {
        $modulBoleh = $this->modulBoleh();

        if ($modulBoleh === []) {
            return redirect()->to(base_url('unauthorized'))
                ->with('error', 'Anda tidak memiliki izin memverifikasi versi dokumen.');
        }

        if (! $this->versi()->siap()) {
            return redirect()->to(base_url('adminkab/dashboard'))->with(
                'error',
                'Fitur versi dokumen belum aktif: tabel registri versi belum terpasang. '
                . 'Jalankan db/update_2026-08-20_versioning_dokumen.sql lebih dulu.'
            );
        }

        $resolver = new VersionResolver();
        $antrean  = [];

        foreach ($this->versi()->menungguVerifikasi() as $v) {
            // Saring per modul: seseorang bisa saja hanya berwenang atas
            // sebagian dokumen.
            if (! in_array($v['modul'], $modulBoleh, true)) {
                continue;
            }

            $v['rentang'] = $resolver->rentangTeks($v);
            $v['lama_hari'] = ! empty($v['submitted_at'])
                ? (int) floor((time() - strtotime($v['submitted_at'])) / 86400)
                : null;

            $antrean[] = $v;
        }

        // Antrean koreksi ikut di halaman yang sama: keduanya keputusan Admin
        // Kabupaten atas dokumen yang sama, dan memisahkannya ke dua menu hanya
        // membuat salah satunya terlupakan.
        $koreksi = [];

        if (function_exists('user_can') && user_can('version_correction.verify')) {
            $koreksi = (new VersionCorrectionService())->menunggu($modulBoleh);
        }

        // Permohonan izin sunting ikut di halaman yang sama, dengan alasan yang
        // sama seperti koreksi: keduanya keputusan Admin Kabupaten atas dokumen
        // yang sama, dan memisahkannya ke menu lain membuat salah satu terlupa.
        $izin = [];

        // Hanya modul yang benar-benar boleh diputuskan pengguna ini yang
        // muncul. Menampilkan permohonan yang tombolnya pasti ditolak hanya
        // membuat antrean tampak macet tanpa sebab yang terlihat.
        if ($this->bolehIzin()) {
            foreach (['renstra', 'iku'] as $modulIzin) {
                if ($this->bolehIzin($modulIzin)) {
                    $izin = array_merge($izin, (new IzinSuntingService())->antrean($modulIzin));
                }
            }
        }

        // Revisi IKU OPD ikut di halaman yang sama. Sebelumnya alurnya buntu:
        // OPD bisa menyusun revisi tapi tidak ada satu peran pun yang bisa
        // mengesahkannya.
        $ikuRevisi = [];

        if (function_exists('user_can') && user_can('iku.version.verify')) {
            $ikuRevisi = (new IkuRevisiModel())->menungguVerifikasi();
        }

        return view('versi/verifikasi_index', [
            'title'        => 'Verifikasi Pengajuan Versi',
            'judulHalaman' => 'Verifikasi Pengajuan Versi Dokumen',
            'antrean'      => $antrean,
            'koreksi'      => $koreksi,
            'izinSunting'  => $izin,
            // Izin yang SUDAH disetujui dan masih terbuka. Tanpa daftar
            // ini ia lenyap dari layar begitu diputus, dan izin yang tak
            // pernah ditutup memblokir lingkupnya dari permohonan baru
            // tanpa seorang pun bisa melihat penyebabnya.
            'izinBerjalan' => (new IzinSuntingService())->berjalanSemua(),
            'ikuRevisi'    => $ikuRevisi,
            'modulBoleh'   => $modulBoleh,
        ]);
    }

    /* =========================================================
     * DETAIL PENGAJUAN
     * =======================================================*/

    public function lihat($id = null)
    {
        $baris = $this->pengajuanSah((int) $id, false);

        if (! is_array($baris)) {
            return $baris; // sudah berupa redirect
        }

        $scope    = VersionScope::dariBaris($baris);
        $arsip    = (new ArsipRegistry())->untuk($scope->modul());
        $resolver = new VersionResolver();

        // §17 menuntut "summary diff": bandingkan pengajuan ini dengan versi
        // yang SEDANG berlaku, supaya verifikator tahu apa persisnya yang
        // berubah — bukan cuma melihat isinya utuh.
        $pembanding = null;
        $diff       = null;

        try {
            $pembanding = $resolver->getEffectiveVersion($scope);

            if ($pembanding !== null && (int) $pembanding['id'] !== (int) $baris['id']) {
                $diff = (new VersionCompareService())->banding((int) $pembanding['id'], (int) $baris['id']);
            }
        } catch (Throwable $e) {
            // Timeline yang sedang konflik tidak boleh menghalangi verifikator
            // membaca pengajuannya; ringkasan perubahan saja yang absen.
            $diff = null;
        }

        return view('versi/verifikasi_lihat', [
            'title'        => 'Verifikasi — ' . $baris['label'],
            'judulHalaman' => 'Verifikasi Pengajuan',
            'versi'        => $baris,
            'scope'        => $scope,
            'namaOpd'      => $this->namaOpd($baris),
            'badge'        => $resolver->badge($baris),
            'rentang'      => $resolver->rentangTeks($baris),
            'isi'          => $arsip !== null && $arsip->siap() ? $arsip->isi((int) $baris['id']) : [],
            'ringkas'      => $arsip !== null && $arsip->siap() ? $arsip->ringkas((int) $baris['id']) : [],
            'riwayat'      => (new VersionAuditService())->riwayat((int) $baris['id']),
            'pembanding'   => $pembanding,
            'diff'         => $diff,
            'asal'         => ! empty($baris['copied_from_version_id'])
                ? $this->versi()->ambil((int) $baris['copied_from_version_id'])
                : null,
        ]);
    }

    /* =========================================================
     * KEPUTUSAN
     * =======================================================*/

    public function setujui($id = null)
    {
        $baris = $this->pengajuanSah((int) $id, true);

        if (! is_array($baris)) {
            return $baris;
        }

        // Penjagaan yang sama seperti di sisi OPD: menyetujui versi TANPA ISI
        // berarti memensiunkan seluruh baris periode itu. Verifikator justru
        // paling perlu tahu angkanya, karena ia memutuskan untuk unit lain.
        $arsip = (new ArsipRegistry())->untuk((string) $baris['modul']);

        if ($arsip !== null && $arsip->siap()) {
            $kosong = true;

            foreach ($arsip->ringkas((int) $baris['id']) as $jml) {
                if ((int) $jml > 0) {
                    $kosong = false;
                }
            }

            if ($kosong && ! $this->request->getPost('konfirmasi_kosong')) {
                $n      = $arsip->hitungLiveAktif(VersionScope::dariBaris($baris));
                $bagian = [];

                foreach ($n as $nama => $jml) {
                    if ((int) $jml > 0) {
                        $bagian[] = $jml . ' ' . str_replace('_', ' ', $nama);
                    }
                }

                return redirect()->back()->with('error',
                    'Pengajuan ini TIDAK BERISI APA PUN. Menyetujuinya akan memensiunkan '
                    . ($bagian === [] ? 'seluruh isi' : implode(' dan ', $bagian))
                    . ' pada periode tersebut. Centang konfirmasi bila memang itu yang dimaksud, '
                    . 'atau kembalikan pengajuannya agar diisi lebih dulu.');
            }
        }

        try {
            (new VersionApprovalService())->setujui((int) $baris['id'], $this->pengguna());
        } catch (Throwable $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->to(base_url('adminkab/verifikasi'))->with(
            'success',
            'Versi "' . $baris['label'] . '" ditetapkan berlaku. Isinya sudah diterapkan ke data '
            . 'berjalan, dan baris yang tidak lagi tercantum dipensiunkan — bukan dihapus.'
        );
    }

    public function kembalikan($id = null)
    {
        $baris = $this->pengajuanSah((int) $id, true);

        if (! is_array($baris)) {
            return $baris;
        }

        $catatan = trim((string) $this->request->getPost('catatan'));

        // §17 — pengembalian WAJIB bercatatan. Diperiksa di sini juga, bukan
        // hanya di form: pengaju yang menerima pengembalian tanpa alasan akan
        // mengajukan hal yang sama lagi.
        if ($catatan === '') {
            return redirect()->back()->with('error', 'Catatan pengembalian wajib diisi.');
        }

        try {
            (new VersionApprovalService())->kembalikan((int) $baris['id'], $catatan, $this->pengguna());
        } catch (Throwable $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->to(base_url('adminkab/verifikasi'))->with(
            'success',
            'Versi "' . $baris['label'] . '" dikembalikan ke penyusun beserta catatannya.'
        );
    }

    /* =========================================================
     * KEPUTUSAN KOREKSI (§21)
     * =======================================================*/

    public function koreksiSetujui($id = null)
    {
        $k = $this->koreksiSah((int) $id);

        if (! is_array($k)) {
            return $k;
        }

        try {
            (new VersionCorrectionService())->setujui((int) $id, $this->pengguna());
        } catch (Throwable $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->to(base_url('adminkab/verifikasi'))->with(
            'success',
            'Koreksi disetujui dan diterapkan pada kolom "' . $k['field'] . '". '
            . 'Nilai lama & baru tercatat pada jejak audit.'
        );
    }

    public function koreksiKembalikan($id = null)
    {
        $k = $this->koreksiSah((int) $id);

        if (! is_array($k)) {
            return $k;
        }

        $catatan = trim((string) $this->request->getPost('catatan'));

        if ($catatan === '') {
            return redirect()->back()->with('error', 'Catatan pengembalian wajib diisi.');
        }

        try {
            (new VersionCorrectionService())->kembalikan((int) $id, $catatan, $this->pengguna());
        } catch (Throwable $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->to(base_url('adminkab/verifikasi'))
            ->with('success', 'Permintaan koreksi dikembalikan beserta catatannya.');
    }

    /**
     * Ambil permintaan koreksi bila pemakai berwenang atas MODUL dokumennya.
     *
     * Wewenang diperiksa lewat dokumen induknya, bukan lewat permintaan itu
     * sendiri: yang menentukan boleh-tidaknya memutuskan adalah jenis dokumen
     * yang dikoreksi.
     *
     * @return array|\CodeIgniter\HTTP\RedirectResponse
     */
    private function koreksiSah(int $id)
    {
        if (! function_exists('user_can') || ! user_can('version_correction.verify')) {
            return redirect()->to(base_url('unauthorized'))
                ->with('error', 'Anda tidak berwenang memutuskan permintaan koreksi.');
        }

        $k = (new VersionCorrectionService())->ambil($id);

        if ($k === null) {
            return redirect()->to(base_url('adminkab/verifikasi'))
                ->with('error', 'Permintaan koreksi tidak ditemukan.');
        }

        $versi = $this->versi()->ambil((int) $k['version_id']);

        if ($versi === null || ! $this->bolehVerifikasi((string) $versi['modul'])) {
            return redirect()->to(base_url('unauthorized'))
                ->with('error', 'Anda tidak berwenang atas dokumen tersebut.');
        }

        if ($k['status'] !== VersionCorrectionService::STATUS_PENDING) {
            return redirect()->to(base_url('adminkab/verifikasi'))->with(
                'error',
                'Permintaan ini sudah tidak menunggu keputusan (status: ' . $k['status'] . ').'
            );
        }

        return $k;
    }

    /* =========================================================
     * INTERNAL
     * =======================================================*/

    /**
     * Ambil pengajuan bila pemakai berwenang atasnya.
     *
     * @param bool $wajibPending true untuk aksi keputusan — mencegah dua
     *                           verifikator menyetujui pengajuan yang sama
     *                           dua kali (§53)
     *
     * @return array|\CodeIgniter\HTTP\RedirectResponse
     */
    private function pengajuanSah(int $id, bool $wajibPending)
    {
        if (! $this->versi()->siap()) {
            return redirect()->to(base_url('adminkab/dashboard'))
                ->with('error', 'Registri versi belum terpasang.');
        }

        $baris = $this->versi()->ambil($id);

        if ($baris === null) {
            return redirect()->to(base_url('adminkab/verifikasi'))
                ->with('error', 'Pengajuan tidak ditemukan.');
        }

        if (! $this->bolehVerifikasi((string) $baris['modul'])) {
            return redirect()->to(base_url('unauthorized'))
                ->with('error', 'Anda tidak berwenang memverifikasi dokumen ' . strtoupper($baris['modul']) . '.');
        }

        if ($wajibPending && $baris['status'] !== DokumenVersiModel::STATUS_PENDING) {
            return redirect()->to(base_url('adminkab/verifikasi'))->with(
                'error',
                'Pengajuan ini sudah tidak menunggu verifikasi (status sekarang: '
                . $baris['status'] . '). Mungkin sudah diputuskan verifikator lain.'
            );
        }

        return $baris;
    }

    private function namaOpd(array $baris): string
    {
        if (empty($baris['opd_id'])) {
            return 'Tingkat Kabupaten';
        }

        $row = \Config\Database::connect()->table('opd')->select('nama_opd')
            ->where('id', (int) $baris['opd_id'])->get()->getRowArray();

        return $row['nama_opd'] ?? ('OPD #' . (int) $baris['opd_id']);
    }

    private function pengguna(): ?int
    {
        $id = session()->get('user_id') ?? session()->get('id');

        return $id === null ? null : (int) $id;
    }

    /* =========================================================
     * KEPUTUSAN IZIN SUNTING
     *
     * Yang diputuskan di sini bukan isi dokumen, melainkan boleh-tidaknya
     * kunci dibuka. Karena itu tidak ada halaman detail tersendiri: alasan
     * pemohon sudah tampil utuh di antrean, dan menambah satu klik hanya
     * memperlambat keputusan yang isinya satu kalimat.
     * =======================================================*/

    public function izinSetujui($id = null)
    {
        if (! $this->bolehIzin($this->modulIzin($id))) {
            return $this->tolakIzinSunting();
        }

        $svc  = new IzinSuntingService();
        $izin = $svc->ambil((int) $id);

        // =================================================================
        // PERMOHONAN HAPUS: MENYETUJUI = MENGHAPUS, SAAT ITU JUGA
        //
        // Sengaja tidak dipecah jadi "disetujui lalu OPD menekan hapus".
        // Keadaan "sudah disetujui tapi belum dihapus" hanya menambah satu
        // pintu yang bisa basi, dan yang memikul akibat penghapusan sebaiknya
        // adalah orang yang memutuskannya.
        //
        // Urutannya: HAPUS DULU, tutup permohonan belakangan. Kalau terbalik
        // dan penghapusan gagal, permohonannya sudah tertutup 'disetujui'
        // sementara versinya masih ada — dan tidak ada lagi jalan
        // menyelesaikannya tanpa permohonan baru.
        // =================================================================
        if ($izin !== null
            && ($izin['jenis'] ?? IzinSuntingService::JENIS_SUNTING) === IzinSuntingService::JENIS_HAPUS) {
            if ($izin['modul'] !== \App\Services\Version\VersionScope::MODUL_IKU) {
                return redirect()->back()->with('error',
                    'Permohonan penghapusan baru tersedia untuk versi IKU.');
            }

            try {
                $hasil = (new \App\Models\Opd\IkuRevisiModel())
                    ->hapusRevisi((int) ($izin['version_id'] ?? 0));

                $svc->setujui((int) $id, $this->pengguna(), $this->request->getPost('catatan'));
            } catch (Throwable $e) {
                return redirect()->back()->with('error', $e->getMessage());
            }

            $pesan = 'Versi "' . $hasil['nama'] . '" (revisi ke-' . $hasil['nomor'] . ') dihapus'
                . ($hasil['penerus'] !== null
                    ? ', dan versi penerusnya diberlakukan kembali ke IKU berjalan.'
                    : '.');

            $keluar = redirect()->to(base_url('adminkab/verifikasi'))->with('success', $pesan);

            return empty($hasil['peringatan'])
                ? $keluar
                : $keluar->with('warning', $hasil['peringatan']);
        }

        try {
            $svc->setujui(
                (int) $id,
                $this->pengguna(),
                $this->request->getPost('catatan')
            );
        } catch (Throwable $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->to(base_url('adminkab/verifikasi'))->with('success',
            'Izin sunting diberikan. OPD bisa memperbaiki Renstra-nya, dan hasilnya '
            . 'akan kembali ke antrean ini sebagai versi berikutnya.');
    }

    public function izinTolak($id = null)
    {
        if (! $this->bolehIzin($this->modulIzin($id))) {
            return $this->tolakIzinSunting();
        }

        try {
            (new IzinSuntingService())->tolak(
                (int) $id,
                $this->pengguna(),
                (string) $this->request->getPost('catatan')
            );
        } catch (Throwable $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->to(base_url('adminkab/verifikasi'))
            ->with('success', 'Permohonan izin sunting ditolak beserta catatannya.');
    }

    public function izinCabut($id = null)
    {
        if (! $this->bolehIzin($this->modulIzin($id))) {
            return $this->tolakIzinSunting();
        }

        try {
            (new IzinSuntingService())->cabut(
                (int) $id,
                $this->pengguna(),
                $this->request->getPost('catatan')
            );
        } catch (Throwable $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->to(base_url('adminkab/verifikasi'))
            ->with('success', 'Izin sunting dicabut. Renstra OPD tersebut terkunci kembali.');
    }

    /**
     * Izin memutuskan permohonan, PER MODUL.
     *
     * Antreannya satu, tetapi kewenangannya belum tentu: pemegang
     * `renstra.izin_sunting.verify` tidak otomatis berhak membuka kunci IKU.
     * Karena itu modul permohonan yang diputuskan ikut diperiksa, bukan hanya
     * "boleh memutuskan izin" secara umum.
     */
    private function bolehIzin(?string $modul = null): bool
    {
        if (! function_exists('user_can')) {
            return false;
        }

        if ($modul !== null && $modul !== '') {
            return user_can($modul . '.izin_sunting.verify');
        }

        return user_can('renstra.izin_sunting.verify') || user_can('iku.izin_sunting.verify');
    }

    /** Modul permohonan, dibaca dari permohonannya sendiri. */
    private function modulIzin($id): ?string
    {
        $izin = (new IzinSuntingService())->ambil((int) $id);

        return $izin['modul'] ?? null;
    }

    private function tolakIzinSunting()
    {
        return redirect()->to(base_url('unauthorized'))
            ->with('error', 'Anda tidak memiliki izin untuk memutuskan permohonan sunting.');
    }


    /* =========================================================
     * KEPUTUSAN REVISI IKU OPD (§17)
     *
     * Sebelum ini alurnya buntu: `iku_opd.revisi_sahkan` dicabut dari OPD agar
     * pengesahan berpindah ke Kabupaten, tetapi jalur Kabupatennya tidak pernah
     * dibuat — dan rute `adminopd/iku/*` memang tidak menerima role admin_kab.
     * Akibatnya OPD bisa menyusun revisi lalu mentok selamanya.
     *
     * Wewenangnya memakai `iku.version.verify` yang sudah ada, bukan izin baru:
     * artinya persis sama ("memverifikasi versi IKU"), dan menambah izin kembar
     * hanya membuat daftar izin makin sulit dibaca.
     * =======================================================*/


    /**
     * Halaman keputusan revisi IKU: isi lengkap + apa yang akan berubah.
     *
     * Antrean saja tidak cukup. Menyetujui dokumen yang tidak bisa dibaca
     * bukan verifikasi, dan yang paling perlu terbaca justru bagian yang tidak
     * tertulis di dokumen itu: indikator mana yang akan DIPENSIUNKAN karena
     * tidak lagi tercantum.
     */
    public function ikuRevisiLihat($id = null)
    {
        if (! $this->bolehIku()) {
            return $this->tolakIku();
        }

        $model  = new IkuRevisiModel();
        $revisi = $model->ambil((int) $id);

        if ($revisi === null || (int) $revisi['opd_key'] === 0) {
            return redirect()->to(base_url('adminkab/verifikasi'))
                ->with('error', 'Revisi IKU OPD tidak ditemukan.');
        }

        $opd = $revisi['opd_id'] !== null
            ? \Config\Database::connect()->table('opd')->select('nama_opd')
                ->where('id', (int) $revisi['opd_id'])->get()->getRowArray()
            : null;

        return view('iku/verifikasi_revisi', [
            'title'        => 'Verifikasi Revisi IKU',
            'judulHalaman' => 'Verifikasi Revisi IKU — ' . ($opd['nama_opd'] ?? 'OPD'),
            'revisi'       => $revisi,
            'namaOpd'      => $opd['nama_opd'] ?? ('OPD #' . (int) $revisi['opd_key']),
            'isi'          => $model->isiRevisi((int) $id, true),
            'praTinjau'    => $model->praTinjauPengesahan((int) $id),
            'years'        => range((int) $revisi['tahun_mulai'], (int) $revisi['tahun_akhir']),
            'bolehPutus'   => $revisi['status'] === IkuRevisiModel::STATUS_MENUNGGU,
        ]);
    }

    public function ikuRevisiSahkan($id = null)
    {
        if (! $this->bolehIku()) {
            return $this->tolakIku();
        }

        $revisi = $this->ikuRevisiSah((int) $id);

        if (! is_array($revisi)) {
            return $revisi;
        }

        try {
            $hasil = (new IkuRevisiModel())->sahkan((int) $id, $this->pengguna());
        } catch (Throwable $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        $pesan = 'Revisi IKU disahkan dan berlaku mulai tahun ' . (int) $revisi['berlaku_mulai_tahun'] . '.';

        if (($hasil['indikator_dipensiunkan'] ?? 0) > 0) {
            $pesan .= ' ' . (int) $hasil['indikator_dipensiunkan'] . ' indikator dipensiunkan '
                . '(tidak dihapus), sehingga LAKIP tahun-tahun sebelumnya tetap utuh.';
        }

        if (! empty($hasil['digeser'])) {
            $pesan .= ' ' . count($hasil['digeser']) . ' revisi sebelumnya menjadi arsip.';
        }

        return redirect()->to(base_url('adminkab/verifikasi'))->with('success', $pesan);
    }

    public function ikuRevisiKembalikan($id = null)
    {
        if (! $this->bolehIku()) {
            return $this->tolakIku();
        }

        $revisi = $this->ikuRevisiSah((int) $id);

        if (! is_array($revisi)) {
            return $revisi;
        }

        try {
            (new IkuRevisiModel())->kembalikan(
                (int) $id,
                (string) $this->request->getPost('catatan'),
                $this->pengguna()
            );
        } catch (Throwable $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->to(base_url('adminkab/verifikasi'))
            ->with('success', 'Revisi IKU dikembalikan ke penyusunnya beserta catatannya.');
    }

    /**
     * Revisi IKU yang sah untuk diputuskan di sini.
     *
     * Hanya revisi milik OPD yang boleh; revisi tingkat Kabupaten disahkan
     * lewat menunya sendiri, dan memutuskannya dari antrean ini akan membuat
     * Kabupaten memverifikasi dokumennya sendiri.
     */
    private function ikuRevisiSah(int $id)
    {
        $revisi = (new IkuRevisiModel())->ambil($id);

        if ($revisi === null || (int) $revisi['opd_key'] === 0) {
            return redirect()->to(base_url('adminkab/verifikasi'))
                ->with('error', 'Revisi IKU OPD tidak ditemukan.');
        }

        if ($revisi['status'] !== IkuRevisiModel::STATUS_MENUNGGU) {
            return redirect()->to(base_url('adminkab/verifikasi'))
                ->with('error', 'Revisi ini tidak sedang menunggu keputusan.');
        }

        return $revisi;
    }

    private function bolehIku(): bool
    {
        return function_exists('user_can') && user_can('iku.version.verify');
    }

    private function tolakIku()
    {
        return redirect()->to(base_url('unauthorized'))
            ->with('error', 'Anda tidak memiliki izin untuk memutuskan revisi IKU.');
    }

}
