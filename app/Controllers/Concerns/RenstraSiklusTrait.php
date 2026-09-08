<?php

namespace App\Controllers\Concerns;

use App\Models\DokumenVersiModel;
use App\Services\Version\ArsipRegistry;
use App\Services\Version\IzinSuntingService;
use App\Services\Version\VersionApprovalService;
use App\Services\Version\VersionScope;
use Throwable;

/**
 * Siklus hidup Renstra berjalan: susun → ajukan validasi → ditetapkan.
 *
 * =====================================================================
 * BAGAIMANA MENU "RENSTRA" DAN "VERSI RENSTRA" TERHUBUNG
 *
 * Renstra yang disusun lewat menu Renstra ADALAH Versi 1. Bukan dokumen
 * terpisah yang kebetulan mirip: saat diajukan, isinya dibekukan ke dalam arsip
 * V1, dan yang diverifikasi Admin Kabupaten adalah arsip itu.
 *
 *   draft            → menu Renstra bebas disunting (typo, sasaran kurang)
 *   pending_approval → TERKUNCI; tarik permohonan dulu bila ingin menyunting
 *   published        → TERKUNCI; salah ketik lewat Ajukan Koreksi,
 *                      perubahan kebijakan lewat versi baru (V2, V3, ...)
 *
 * =====================================================================
 * MENGAPA PENGUNCIAN DIPASANG DI CONTROLLER, BUKAN DI TOMBOL
 *
 * Menyembunyikan tombol hanya menyembunyikan pintu; rutenya tetap bisa
 * di-POST langsung. Karena itu setiap jalur tulis Renstra memanggil
 * `renstraPastikanBoleh()` lebih dulu — save, update, delete, updateStatus,
 * updateTujuan, semuanya.
 *
 * =====================================================================
 * BASELINE OTOMATIS TIDAK MENGUNCI
 *
 * Migrasi membuat V1 berstatus published dengan arsip KOSONG untuk setiap OPD.
 * Baris itu tidak pernah disusun maupun diverifikasi siapa pun — memperlakukannya
 * sebagai "sudah ditetapkan" akan mengunci Renstra 40 OPD sekaligus tanpa ada
 * yang pernah menyetujuinya. Selama arsipnya masih kosong, ia dianggap belum
 * divalidasi dan Renstra tetap bebas disunting.
 */
trait RenstraSiklusTrait
{
    /** Lingkup versi Renstra untuk satu periode milik OPD yang sedang login. */
    protected function renstraScope(int $tahunMulai, int $tahunAkhir): ?VersionScope
    {
        $opd = session()->get('opd_id');

        if (empty($opd) || $tahunMulai <= 0 || $tahunAkhir < $tahunMulai) {
            return null;
        }

        try {
            return VersionScope::renstra((int) $opd, $tahunMulai, $tahunAkhir);
        } catch (Throwable $e) {
            return null;
        }
    }

    /**
     * Keadaan siklus hidup satu periode Renstra.
     *
     * =====================================================================
     * YANG MENGUNCI ADALAH VERSI RESMI, BUKAN VERSI TERBARU
     *
     * Sebelumnya keadaan ditentukan versi ber-`version_no` TERTINGGI apa pun
     * statusnya. Akibatnya membuat draft baru di menu "Versi Renstra" membuat
     * status periode berubah menjadi draft — dan kunci Renstra berjalan
     * terbuka sendiri, tanpa permohonan dan tanpa persetujuan siapa pun.
     * Itu pintu belakang yang membatalkan seluruh alur izin sunting.
     *
     * Sekarang penentunya dipisah menurut peran, bukan menurut nomor:
     *
     *   pending_approval  -> sedang di tangan verifikator; terkunci
     *   published + berisi-> dokumen resmi; terkunci kecuali ada izin sunting
     *   published + kosong-> baseline pemasangan; belum pernah divalidasi
     *   draft / cancelled -> TIDAK PERNAH menentukan apa pun
     *
     * Draft hidup di arsipnya sendiri dan tidak menyentuh tabel berjalan, jadi
     * keberadaannya memang tidak punya alasan mengubah keadaan apa pun.
     *
     * @return array{
     *     versi:?array, versi_resmi:?array, versi_dasar:?array,
     *     status:string, terkunci:bool, alasan:?string,
     *     boleh_ajukan:bool, boleh_tarik:bool, draft_berisi:?array,
     *     izin:?array, boleh_minta_izin:bool, sedang_disunting:bool
     * }
     */
    protected function renstraKeadaan(int $tahunMulai, int $tahunAkhir): array
    {
        $kosong = [
            'versi' => null, 'versi_resmi' => null, 'versi_dasar' => null,
            'status' => 'belum', 'terkunci' => false, 'alasan' => null,
            'boleh_ajukan' => true, 'boleh_tarik' => false, 'draft_berisi' => null,
            'izin' => null, 'boleh_minta_izin' => false, 'sedang_disunting' => false,
        ];

        $scope = $this->renstraScope($tahunMulai, $tahunAkhir);
        $model = new DokumenVersiModel();

        // Periode tidak sah = TOLAK, bukan "bebas".
        //
        // Ini bukan kehati-hatian berlebihan. renstraKeadaanDariTujuan() gagal
        // menemukan barisnya ketika tujuannya milik OPD lain, lalu memanggil
        // renstraKeadaan(0, 0). Selama cabang ini mengembalikan "tidak
        // terkunci", penjaga justru MELOLOSKAN tujuan asing — dan lebih mudah
        // menulisi milik orang lain daripada milik sendiri.
        if ($scope === null) {
            return array_merge($kosong, [
                'status'       => 'tidak_sah',
                'terkunci'     => true,
                'alasan'       => 'Periode Renstra tidak sah, atau data itu bukan milik OPD Anda.',
                'boleh_ajukan' => false,
            ]);
        }

        if (! $model->siap()) {
            return $kosong;
        }

        $daftar = $model->daftar($scope);

        if ($daftar === []) {
            return $kosong;
        }

        // Kelompokkan menurut PERAN, bukan menurut nomor versi.
        $pending      = null;
        $resmi        = null;   // published DAN berisi -> inilah yang mengunci
        $baseline     = null;   // published tapi arsipnya kosong -> bawaan pemasangan
        $draftBerisi  = null;   // draft yang sudah disusun tangan di menu Versi

        $lebihBaru = static fn (?array $lama, array $baru): bool => $lama === null
            || (int) $baru['version_no'] > (int) $lama['version_no'];

        foreach ($daftar as $d) {
            $arsipKosong = $this->renstraArsipKosong($d);

            if ($d['status'] === DokumenVersiModel::STATUS_PENDING) {
                if ($lebihBaru($pending, $d)) {
                    $pending = $d;
                }
            } elseif ($d['status'] === DokumenVersiModel::STATUS_PUBLISHED) {
                if ($arsipKosong) {
                    if ($lebihBaru($baseline, $d)) {
                        $baseline = $d;
                    }
                } elseif ($lebihBaru($resmi, $d)) {
                    $resmi = $d;
                }
            } elseif ($d['status'] === DokumenVersiModel::STATUS_DRAFT && ! $arsipKosong) {
                if ($lebihBaru($draftBerisi, $d)) {
                    $draftBerisi = $d;
                }
            }
        }

        $izin = (new IzinSuntingService())->berjalan($scope);

        // Draft yang sudah berisi TIDAK boleh diajukan lewat tombol di menu
        // Renstra: pengajuan dari sini membekukan ulang dari kondisi berjalan,
        // dan itu akan MENIMPA isi draft yang disusun tangan di menu Versi.
        $halangDraft = $draftBerisi !== null
            ? 'Ada draft V' . (int) $draftBerisi['version_no'] . ' yang isinya Anda susun sendiri '
              . 'di menu Versi Renstra. Ajukan dari sana — mengajukan dari sini akan menimpanya '
              . 'dengan kondisi berjalan.'
            : null;

        $dasar = [
            'versi_resmi'  => $resmi,
            'versi_dasar'  => $baseline,
            'draft_berisi' => $draftBerisi,
            'izin'         => $izin,
        ];

        // 1. Menunggu verifikator — sinyal terkuat, apa pun yang lain.
        if ($pending !== null) {
            return array_merge($kosong, $dasar, [
                'versi'        => $pending,
                'status'       => DokumenVersiModel::STATUS_PENDING,
                'terkunci'     => true,
                'alasan'       => 'Renstra sedang menunggu verifikasi Admin Kabupaten, sehingga '
                    . 'tidak bisa disunting. Tarik permohonan lebih dulu bila ingin memperbaikinya.',
                'boleh_ajukan' => false,
                'boleh_tarik'  => true,
            ]);
        }

        // 2. Ada dokumen resmi berisi -> terkunci, kecuali izin sunting berlaku.
        if ($resmi !== null) {
            if ($izin !== null && $izin['status'] === IzinSuntingService::STATUS_DISETUJUI) {
                return array_merge($kosong, $dasar, [
                    'versi'            => $resmi,
                    'status'           => DokumenVersiModel::STATUS_PUBLISHED,
                    'terkunci'         => false,
                    'sedang_disunting' => true,
                    'boleh_ajukan'     => $draftBerisi === null,
                    'alasan'           => $halangDraft,
                ]);
            }

            $menunggu = $izin !== null && $izin['status'] === IzinSuntingService::STATUS_PENDING;

            return array_merge($kosong, $dasar, [
                'versi'    => $resmi,
                'status'   => DokumenVersiModel::STATUS_PUBLISHED,
                'terkunci' => true,
                'alasan'   => $menunggu
                    ? 'Permohonan izin sunting Anda sedang menunggu keputusan Admin Kabupaten. '
                      . 'Selama itu Renstra masih terkunci.'
                    : 'Renstra sudah ditetapkan berlaku, sehingga terkunci. Ajukan izin sunting '
                      . 'bila ada yang perlu diperbaiki; setelah disetujui Admin Kabupaten, '
                      . 'Renstra bisa disunting seperti biasa.',
                'boleh_ajukan'     => false,
                'boleh_minta_izin' => ! $menunggu,
            ]);
        }

        // 3. Belum ada dokumen resmi: bebas disunting. Baseline pemasangan yang
        //    arsipnya masih kosong tidak mengunci apa pun — ia memang belum
        //    pernah disusun maupun diverifikasi siapa pun.
        return array_merge($kosong, $dasar, [
            'versi'        => $baseline ?? $draftBerisi,
            'status'       => $baseline !== null ? 'baseline_kosong' : DokumenVersiModel::STATUS_DRAFT,
            'boleh_ajukan' => $draftBerisi === null,
            'alasan'       => $halangDraft,
        ]);
    }

    /** Keadaan berdasarkan sebuah baris renstra_sasaran (untuk update/delete). */
    protected function renstraKeadaanDariSasaran(int $sasaranId): array
    {
        // Disaring opd_id dari SESI. Tanpa itu, id sasaran milik OPD lain akan
        // dinilai memakai penguncian periode OPD ini — penjaga yang memeriksa
        // dokumen yang salah.
        $row = \Config\Database::connect()->table('renstra_sasaran')
            ->select('tahun_mulai, tahun_akhir')
            ->where('id', $sasaranId)
            ->where('opd_id', session()->get('opd_id'))
            ->get()->getRowArray();

        if ($row === null) {
            return $this->renstraKeadaan(0, 0);
        }

        return $this->renstraKeadaan((int) $row['tahun_mulai'], (int) $row['tahun_akhir']);
    }

    /** Keadaan berdasarkan sebuah baris renstra_tujuan (untuk updateTujuan). */
    protected function renstraKeadaanDariTujuan(int $tujuanId): array
    {
        $row = \Config\Database::connect()->table('renstra_sasaran')
            ->select('tahun_mulai, tahun_akhir')
            ->where('renstra_tujuan_id', $tujuanId)
            ->where('opd_id', session()->get('opd_id'))
            ->orderBy('id', 'ASC')->get()->getRowArray();

        if ($row === null) {
            return $this->renstraKeadaan(0, 0);
        }

        return $this->renstraKeadaan((int) $row['tahun_mulai'], (int) $row['tahun_akhir']);
    }

    /**
     * Hentikan aksi tulis bila periode terkunci.
     *
     * @return \CodeIgniter\HTTP\RedirectResponse|null null = boleh lanjut
     */
    /**
     * Tanggal mulai berlaku yang belum terpakai pada lingkup ini.
     *
     * §6 melarang dua versi published mulai pada tanggal yang sama, dan
     * UNIQUE `uq_dokver_mulai` menegakkannya. Menebak "1 Januari" untuk versi
     * kedua karena itu hampir pasti ditolak — jadi dicari tanggal kosong
     * pertama mulai hari ini, tetap di dalam periode dokumennya.
     */
    protected function renstraTanggalBerikutnya(VersionScope $scope, int $tm, int $ta): string
    {
        $model  = new DokumenVersiModel();
        $pakai  = [];

        foreach ($model->daftar($scope) as $v) {
            $pakai[(string) $v['effective_from']] = true;
        }

        $awal  = max(strtotime($tm . '-01-01'), strtotime(date('Y-m-d')));
        $batas = strtotime($ta . '-12-31');

        for ($t = $awal; $t <= $batas; $t = strtotime('+1 day', $t)) {
            $tanggal = date('Y-m-d', $t);

            if (! isset($pakai[$tanggal])) {
                return $tanggal;
            }
        }

        // Periode penuh sesak — biarkan lapisan timeline yang menolak dengan
        // pesannya sendiri, ketimbang menebak tanggal di luar periode.
        return date('Y-m-d', $batas);
    }

    /**
     * Apakah sebuah `renstra_tujuan` benar-benar milik OPD yang sedang login.
     *
     * =====================================================================
     * MENGAPA INI ADA
     *
     * `renstra_tujuan` tidak punya kolom `opd_id` sama sekali (temuan T4);
     * kepemilikannya hanya tersirat lewat `renstra_sasaran` yang menggantung
     * padanya. Karena itu tidak ada satu pun query yang otomatis aman, dan
     * tiap jalur tulis harus memeriksanya sendiri.
     *
     * `editTujuan` memeriksanya lewat getCompleteTujuan($id, $opdId),
     * `update` dan `delete` memeriksanya masing-masing — `updateTujuan` tidak,
     * dan meneruskan id mentah ke updateCompleteTujuan(). Method ini menutup
     * celah itu dengan pemeriksaan yang sama untuk semua jalur.
     */
    protected function renstraTujuanMilikSaya(int $tujuanId): bool
    {
        $opd = session()->get('opd_id');

        if (empty($opd) || $tujuanId <= 0) {
            return false;
        }

        return \Config\Database::connect()->table('renstra_sasaran')
            ->where('renstra_tujuan_id', $tujuanId)
            ->where('opd_id', (int) $opd)
            ->countAllResults() > 0;
    }

    protected function renstraPastikanBoleh(array $keadaan)
    {
        if (! $keadaan['terkunci']) {
            return null;
        }

        return redirect()->to(base_url('adminopd/renstra'))->with('error', $keadaan['alasan']);
    }

    protected function renstraArsipKosong(array $versi): bool
    {
        $arsip = (new ArsipRegistry())->untuk((string) $versi['modul']);

        if ($arsip === null || ! $arsip->siap()) {
            return true;
        }

        foreach ($arsip->ringkas((int) $versi['id']) as $jml) {
            if ((int) $jml > 0) {
                return false;
            }
        }

        return true;
    }

    /* =========================================================
     * AKSI
     * =======================================================*/

    /**
     * Ajukan Renstra berjalan untuk divalidasi Admin Kabupaten.
     *
     * Urutannya penting: isi Renstra DIBEKUKAN ke arsip versi LEBIH DULU, baru
     * permohonannya diajukan. Yang diverifikasi Admin Kabupaten harus berupa
     * salinan beku — kalau ia menilai tabel berjalan, isinya bisa berubah di
     * tengah proses verifikasi tanpa siapa pun menyadari.
     */
    public function renstraAjukanValidasi()
    {
        $tm = (int) $this->request->getPost('tahun_mulai');
        $ta = (int) $this->request->getPost('tahun_akhir');

        $scope = $this->renstraScope($tm, $ta);

        if ($scope === null) {
            return redirect()->back()->with('error', 'Periode Renstra tidak sah.');
        }

        $keadaan = $this->renstraKeadaan($tm, $ta);

        if (! $keadaan['boleh_ajukan']) {
            return redirect()->back()->with('error',
                $keadaan['alasan'] ?? 'Renstra periode ini tidak dalam keadaan bisa diajukan.');
        }

        $model = new DokumenVersiModel();
        $arsip = (new ArsipRegistry())->untuk(VersionScope::MODUL_RENSTRA);

        if ($arsip === null || ! $arsip->siap()) {
            return redirect()->back()->with('error',
                'Fitur versi belum aktif: jalankan db/update_2026-08-20_versioning_dokumen.sql lebih dulu.');
        }

        $pengguna = session()->get('user_id') ?? session()->get('id');
        $db       = \Config\Database::connect();

        try {
            $db->transBegin();
            $db->resetTransStatus();

            // Sumber keputusan dipisah tegas, sebab ketiganya berakhir berbeda:
            //   versi_resmi  -> sudah ada dokumen resmi berisi  -> LAHIR versi baru
            //   versi_dasar  -> baseline pemasangan yang kosong -> DIPAKAI ULANG jadi V1
            //   keduanya null-> belum ada apa-apa               -> BUAT V1
            //
            // Memakai "versi terbaru" begitu saja pernah membuat baseline kosong
            // ikut terbaca sebagai dokumen resmi, sehingga Renstra pertama
            // sebuah OPD lahir sebagai V2 — padahal V1 miliknya sendiri.
            $resmi = $keadaan['versi_resmi'] ?? null;
            $versi = $resmi ?? ($keadaan['versi_dasar'] ?? null);

            // Belum ada versi sama sekali -> Renstra ini menjadi Versi 1.
            if ($versi === null) {
                $versiId = $model->sisipkan(array_merge($scope->kolomBaru(), [
                    'version_no'     => 1,
                    'label'          => 'V1 — Renstra ' . $tm . '-' . $ta,
                    'effective_from' => $tm . '-01-01',
                    'status'         => DokumenVersiModel::STATUS_DRAFT,
                    'created_by'     => $pengguna,
                ]));
            } elseif ($resmi !== null) {
                // Menyunting lewat izin, lalu diajukan ulang -> LAHIR VERSI BARU.
                //
                // Cabang ini wajib ada. Tanpanya, alurnya jatuh ke cabang di
                // bawah yang mengembalikan versi itu ke status draft — dan versi
                // yang SUDAH DITETAPKAN akan hilang keresmiannya beserta seluruh
                // arti jejak auditnya. Justru itu yang dijaga izin sunting.
                $nomor = $model->nomorBerikutnya($scope);

                $versiId = $model->sisipkan(array_merge($scope->kolomBaru(), [
                    'version_no' => $nomor,
                    'label'      => 'V' . $nomor . ' — Renstra ' . $tm . '-' . $ta . ' (hasil penyuntingan)',
                    // Sengaja TIDAK 1 Januari: tanggal itu hampir selalu sudah
                    // dipakai versi sebelumnya, dan §6 melarang dua published
                    // mulai di tanggal yang sama. Tanggalnya bisa diperbaiki
                    // lewat "Ubah Tanggal & Keterangan" sebelum ditetapkan.
                    'effective_from'   => $this->renstraTanggalBerikutnya($scope, $tm, $ta),
                    'status'           => DokumenVersiModel::STATUS_DRAFT,
                    'created_by'       => $pengguna,
                    'alasan_perubahan' => $keadaan['izin']['alasan'] ?? null,
                ]));
            } else {
                $versiId = (int) $versi['id'];

                // Baseline otomatis diambil alih menjadi milik penyusun, supaya
                // ia berhenti dianggap placeholder migrasi.
                $model->perbarui($versiId, [
                    'status'     => DokumenVersiModel::STATUS_DRAFT,
                    'created_by' => $versi['created_by'] ?? $pengguna,
                    'label'      => $versi['label'] ?: ('V1 — Renstra ' . $tm . '-' . $ta),
                ]);
            }

            // Bekukan ulang dari nol: arsip harus mencerminkan Renstra APA ADANYA
            // saat diajukan, bukan gabungan pembekuan lama dan baru.
            $arsip->kosongkan($versiId);
            $ringkas = $arsip->bekukanDariLive($versiId, $scope);

            if ($db->transStatus() === false) {
                $db->transRollback();

                return redirect()->back()->with('error', 'Pembekuan Renstra gagal.');
            }

            $db->transCommit();

            if (($ringkas['sasaran'] ?? 0) < 1) {
                return redirect()->back()->with('error',
                    'Renstra periode ini belum berisi sasaran apa pun, jadi belum ada yang bisa divalidasi.');
            }

            (new VersionApprovalService())->ajukan($versiId, $pengguna === null ? null : (int) $pengguna);

            // Izin sunting sudah terpakai: penyuntingannya rampung dan hasilnya
            // ada di tangan verifikator. Izin yang dibiarkan terbuka sama saja
            // dengan kunci yang dicabut selamanya.
            (new IzinSuntingService())->selesaikan($scope);
        } catch (Throwable $e) {
            if ($db->transDepth > 0) {
                $db->transRollback();
            }

            return redirect()->back()->with('error', pesanGalat($e, 'umum.renstraSiklus'));
        }

        return redirect()->to(base_url('adminopd/renstra'))->with('success',
            'Renstra periode ' . $tm . '-' . $ta . ' diajukan untuk divalidasi. '
            . 'Selama menunggu, isinya tidak bisa disunting — tarik permohonan bila perlu diperbaiki.');
    }

    /** Tarik permohonan sendiri agar Renstra bisa disunting lagi. */
    public function renstraTarikPermohonan()
    {
        $tm = (int) $this->request->getPost('tahun_mulai');
        $ta = (int) $this->request->getPost('tahun_akhir');

        $keadaan = $this->renstraKeadaan($tm, $ta);

        if (! $keadaan['boleh_tarik'] || $keadaan['versi'] === null) {
            return redirect()->back()->with('error',
                'Tidak ada permohonan yang sedang menunggu verifikasi untuk periode ini.');
        }

        try {
            (new VersionApprovalService())->tarikPengajuan(
                (int) $keadaan['versi']['id'],
                session()->get('user_id') ?? session()->get('id')
            );
        } catch (Throwable $e) {
            return redirect()->back()->with('error', pesanGalat($e, 'umum.renstraSiklus'));
        }

        return redirect()->to(base_url('adminopd/renstra'))->with('success',
            'Permohonan ditarik. Renstra bisa disunting kembali; ajukan ulang bila sudah selesai.');
    }

    /* =========================================================
     * IZIN SUNTING (sisi OPD)
     *
     * Yang diminta di sini adalah membuka KUNCI menu Renstra, bukan mengubah
     * arsip versi. Setelah disetujui, penyuntingan berjalan lewat form dan
     * tombol yang sudah dikenal; hasilnya menjadi versi berikutnya saat
     * diajukan ulang.
     * =======================================================*/

    public function renstraMintaIzin()
    {
        if (! function_exists('user_can') || ! user_can('renstra.izin_sunting.request')) {
            return redirect()->to(base_url('unauthorized'))
                ->with('error', 'Anda tidak memiliki izin untuk mengajukan permohonan sunting.');
        }

        $tm = (int) $this->request->getPost('tahun_mulai');
        $ta = (int) $this->request->getPost('tahun_akhir');

        $scope   = $this->renstraScope($tm, $ta);
        $kembali = base_url('adminopd/renstra?periode=' . $tm . '-' . $ta);

        if ($scope === null) {
            return redirect()->back()->with('error', 'Periode Renstra tidak sah.');
        }

        $keadaan = $this->renstraKeadaan($tm, $ta);

        // Hanya masuk akal bila memang sedang terkunci. Meminta izin atas
        // Renstra yang sudah bebas disunting hanya melahirkan permohonan yang
        // tidak pernah dipakai, dan mengotori antrean Admin Kabupaten.
        if (! $keadaan['terkunci'] || empty($keadaan['boleh_minta_izin'])) {
            return redirect()->to($kembali)->with('error',
                'Renstra periode ini tidak dalam keadaan memerlukan izin sunting.');
        }

        try {
            (new IzinSuntingService())->ajukan(
                $scope,
                (string) $this->request->getPost('alasan'),
                $this->penggunaRenstra(),
                isset($keadaan['versi']['id']) ? (int) $keadaan['versi']['id'] : null
            );
        } catch (Throwable $e) {
            return redirect()->to($kembali)->with('error', pesanGalat($e, 'umum.renstraSiklus'));
        }

        return redirect()->to($kembali)->with('success',
            'Permohonan izin sunting dikirim. Setelah disetujui Admin Kabupaten, '
            . 'Renstra periode ini bisa disunting seperti biasa.');
    }

    public function renstraTarikIzin($id = null)
    {
        if (! function_exists('user_can') || ! user_can('renstra.izin_sunting.request')) {
            return redirect()->to(base_url('unauthorized'))
                ->with('error', 'Anda tidak memiliki izin untuk aksi tersebut.');
        }

        $izin = (new IzinSuntingService())->ambil((int) $id);

        // Lingkup diperiksa dari SESI, bukan dari permintaan: tanpa ini, id
        // permohonan OPD lain bisa ditarik hanya dengan mengarang angkanya.
        // Modul ikut diperiksa (seperti IkuRevisiTrait::revisiTarikIzin):
        // tabel izinnya lintas modul, jadi tanpa saringan ini pemegang izin
        // renstra bisa menarik permohonan IKU/LAKIP milik OPD-nya sendiri
        // lewat modul yang bukan wewenangnya.
        if ($izin === null
            || ($izin['modul'] ?? '') !== VersionScope::MODUL_RENSTRA
            || (int) $izin['opd_key'] !== (int) session()->get('opd_id')) {
            return redirect()->to(base_url('adminopd/renstra'))
                ->with('error', 'Permohonan tidak ditemukan pada lingkup Anda.');
        }

        $kembali = base_url('adminopd/renstra?periode='
            . $izin['periode_mulai'] . '-' . $izin['periode_akhir']);

        try {
            (new IzinSuntingService())->tarik((int) $id, $this->penggunaRenstra());
        } catch (Throwable $e) {
            return redirect()->to($kembali)->with('error', pesanGalat($e, 'umum.renstraSiklus'));
        }

        return redirect()->to($kembali)->with('success', 'Permohonan izin sunting ditarik.');
    }

    private function penggunaRenstra(): ?int
    {
        $id = session()->get('user_id') ?? session()->get('id');

        return $id === null ? null : (int) $id;
    }

}
