<?php

namespace App\Controllers\Concerns;

use App\Models\DokumenVersiModel;
use App\Services\Version\ArsipRegistry;
use App\Services\Version\IzinSuntingService;
use App\Services\Version\VersionScope;
use Throwable;

/**
 * Kunci RPJMD berjalan + izin sunting — sejajar dengan RenstraSiklusTrait,
 * disesuaikan untuk tingkat KABUPATEN.
 *
 * =====================================================================
 * MASALAH YANG DIPECAHKAN
 *
 * Sebelumnya RPJMD berjalan (rpjmd_misi/tujuan/sasaran/indikator/target) bisa
 * disunting kapan saja, tanpa memandang apakah sudah ada VERSI RPJMD yang
 * ditetapkan. Akibatnya tabel berjalan dapat menyimpang DIAM-DIAM dari versi
 * yang katanya "dibekukan" — janji utama modul versi jadi bocor. Renstra sudah
 * ditutup begini sejak awal (RenstraSiklusTrait); RPJMD belum.
 *
 * =====================================================================
 * MENGAPA BEDA DARI RENSTRA: TIDAK ADA "AJUKAN VALIDASI KE ATAS"
 *
 * Renstra milik OPD, jadi izin suntingnya diminta OPD dan diputuskan Admin
 * Kabupaten (pemisahan wewenang). RPJMD milik Admin Kabupaten sendiri — tidak
 * ada pihak di atasnya. Karena itu izin suntingnya SWALAYAN: Admin Kabupaten
 * membuka kuncinya sendiri, dengan alasan yang WAJIB diisi dan tercatat di
 * jejak (dokumen_izin_sunting + activity_logs). Yang dicegah kunci ini bukan
 * "siapa boleh menyunting" (itu tetap Admin Kabupaten), melainkan penyimpangan
 * yang tidak disengaja dan tidak tercatat. Ini konsisten dengan cara Admin
 * Kabupaten menetapkan versinya sendiri langsung dari draft (§18) — pembuat
 * dan pemutusnya memang sama.
 *
 * =====================================================================
 * PENGUNCIAN DI CONTROLLER, BUKAN DI TOMBOL
 *
 * Menyembunyikan tombol hanya menutup pintu depan; rutenya tetap bisa di-POST.
 * Karena itu save(), update(), delete(), dan updateStatus() semua memanggil
 * penjaganya lebih dulu. Tombol di layar hanya cerminan keadaan yang sama.
 *
 * Kontrak pemakai: kelas punya $this->request dan $this->rpjmdModel.
 */
trait RpjmdSiklusTrait
{
    protected function rpjmdScopePeriode(int $tahunMulai, int $tahunAkhir): ?VersionScope
    {
        if ($tahunMulai <= 0 || $tahunAkhir < $tahunMulai) {
            return null;
        }

        try {
            return VersionScope::rpjmd($tahunMulai, $tahunAkhir);
        } catch (Throwable $e) {
            return null;
        }
    }

    /**
     * Keadaan siklus hidup satu periode RPJMD.
     *
     * Aturan pengunci sama dengan Renstra: yang mengunci adalah versi RESMI
     * (published + arsip berisi), bukan versi bernomor tertinggi. Draft dan
     * baseline-kosong tidak pernah mengunci.
     *
     * @return array{
     *   versi:?array, status:string, terkunci:bool, alasan:?string,
     *   izin:?array, boleh_minta_izin:bool, sedang_disunting:bool
     * }
     */
    protected function rpjmdKeadaan(int $tahunMulai, int $tahunAkhir): array
    {
        $kosong = [
            'versi' => null, 'status' => 'belum', 'terkunci' => false, 'alasan' => null,
            'izin' => null, 'boleh_minta_izin' => false, 'sedang_disunting' => false,
        ];

        $scope = $this->rpjmdScopePeriode($tahunMulai, $tahunAkhir);

        if ($scope === null) {
            return $kosong;
        }

        $model = new DokumenVersiModel();

        if (! $model->siap()) {
            return $kosong;
        }

        $daftar = $model->daftar($scope);

        if ($daftar === []) {
            return $kosong;
        }

        $pending  = null;
        $resmi    = null;   // published + berisi -> mengunci
        $baseline = null;   // published + arsip kosong -> bawaan pemasangan

        $lebihBaru = static fn (?array $lama, array $baru): bool => $lama === null
            || (int) $baru['version_no'] > (int) $lama['version_no'];

        foreach ($daftar as $d) {
            if ($d['status'] === DokumenVersiModel::STATUS_PENDING) {
                if ($lebihBaru($pending, $d)) {
                    $pending = $d;
                }
            } elseif ($d['status'] === DokumenVersiModel::STATUS_PUBLISHED) {
                if ($this->rpjmdArsipKosong($d)) {
                    if ($lebihBaru($baseline, $d)) {
                        $baseline = $d;
                    }
                } elseif ($lebihBaru($resmi, $d)) {
                    $resmi = $d;
                }
            }
        }

        $izin = (new IzinSuntingService())->berjalan($scope);

        // 1. Menunggu verifikasi (jarang untuk RPJMD, tapi mungkin bila
        //    diajukan lewat alur versi) — terkunci.
        if ($pending !== null) {
            return array_merge($kosong, [
                'versi'    => $pending,
                'status'   => DokumenVersiModel::STATUS_PENDING,
                'terkunci' => true,
                'izin'     => $izin,
                'alasan'   => 'Versi RPJMD periode ini sedang menunggu penetapan, sehingga RPJMD '
                    . 'berjalan dikunci sampai versinya diputuskan.',
            ]);
        }

        // 2. Ada versi resmi berisi -> terkunci, kecuali izin sunting berlaku.
        if ($resmi !== null) {
            if ($izin !== null && $izin['status'] === IzinSuntingService::STATUS_DISETUJUI) {
                return array_merge($kosong, [
                    'versi'            => $resmi,
                    'status'           => DokumenVersiModel::STATUS_PUBLISHED,
                    'terkunci'         => false,
                    'sedang_disunting' => true,
                    'izin'             => $izin,
                ]);
            }

            return array_merge($kosong, [
                'versi'            => $resmi,
                'status'           => DokumenVersiModel::STATUS_PUBLISHED,
                'terkunci'         => true,
                'izin'             => $izin,
                'boleh_minta_izin' => true,
                'alasan'           => 'RPJMD periode ini sudah punya versi yang ditetapkan (V'
                    . (int) $resmi['version_no'] . '), sehingga tabel berjalan dikunci agar tidak '
                    . 'menyimpang dari versi itu. Buka kunci untuk menyunting bila memang ada yang '
                    . 'perlu diperbaiki — pembukaannya dicatat.',
            ]);
        }

        // 3. Belum ada versi resmi: bebas disunting.
        return array_merge($kosong, [
            'versi'  => $baseline,
            'status' => $baseline !== null ? 'baseline_kosong' : DokumenVersiModel::STATUS_DRAFT,
        ]);
    }

    /** Keadaan dari sebuah baris rpjmd_misi (periode menempel di misi). */
    protected function rpjmdKeadaanDariMisi(int $misiId): array
    {
        $row = \Config\Database::connect()->table('rpjmd_misi')
            ->select('tahun_mulai, tahun_akhir')
            ->where('id', $misiId)
            ->get()->getRowArray();

        if ($row === null) {
            return $this->rpjmdKeadaan(0, 0);
        }

        return $this->rpjmdKeadaan((int) $row['tahun_mulai'], (int) $row['tahun_akhir']);
    }

    /**
     * Keadaan dari sebuah id entitas apa pun (misi/tujuan/sasaran/indikator) —
     * dipakai delete(), yang menerima id turunan lalu memetakannya ke misi.
     */
    protected function rpjmdKeadaanDariEntitas(int $id): array
    {
        $misiId = (int) $this->rpjmdModel->findMisiIdForAnyEntity($id);

        return $misiId > 0 ? $this->rpjmdKeadaanDariMisi($misiId) : $this->rpjmdKeadaan(0, 0);
    }

    /**
     * Hentikan aksi tulis bila periode terkunci.
     *
     * @return \CodeIgniter\HTTP\RedirectResponse|null null = boleh lanjut
     */
    protected function rpjmdPastikanBoleh(array $keadaan)
    {
        if (empty($keadaan['terkunci'])) {
            return null;
        }

        return redirect()->to(base_url('adminkab/rpjmd'))->with('error', $keadaan['alasan']);
    }

    protected function rpjmdArsipKosong(array $versi): bool
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

    /**
     * Keadaan kunci untuk SETIAP periode RPJMD — bahan tampilan index.
     *
     * @return array<string,array> periode "2025-2029" => keadaan
     */
    protected function rpjmdKunciPerPeriode(): array
    {
        $out = [];

        try {
            $rows = \Config\Database::connect()->table('rpjmd_misi')
                ->select('tahun_mulai, tahun_akhir')->distinct()
                ->where('tahun_mulai IS NOT NULL', null, false)
                ->get()->getResultArray();
        } catch (Throwable $e) {
            return [];
        }

        foreach ($rows as $r) {
            $tm = (int) $r['tahun_mulai'];
            $ta = (int) $r['tahun_akhir'];

            if ($tm <= 0 || $ta <= 0) {
                continue;
            }

            $out[$tm . '-' . $ta] = $this->rpjmdKeadaan($tm, $ta);
        }

        return $out;
    }

    private function penggunaRpjmd(): ?int
    {
        $id = session()->get('user_id') ?? session()->get('id');

        return $id === null ? null : (int) $id;
    }

    /* =========================================================
     * IZIN SUNTING — SWALAYAN (buka & tutup oleh Admin Kabupaten)
     * =======================================================*/

    /** Buka kunci sebuah periode untuk disunting (buka + setujui sekaligus). */
    public function izinSuntingAjukan()
    {
        if (! function_exists('user_can') || ! user_can('rpjmd.update')) {
            return redirect()->to(base_url('unauthorized'))
                ->with('error', 'Anda tidak berwenang membuka kunci RPJMD.');
        }

        $tm = (int) $this->request->getPost('tahun_mulai');
        $ta = (int) $this->request->getPost('tahun_akhir');

        $scope   = $this->rpjmdScopePeriode($tm, $ta);
        $kembali = base_url('adminkab/rpjmd');

        if ($scope === null) {
            return redirect()->to($kembali)->with('error', 'Periode RPJMD tidak sah.');
        }

        $keadaan = $this->rpjmdKeadaan($tm, $ta);

        if (empty($keadaan['terkunci']) || empty($keadaan['boleh_minta_izin'])) {
            return redirect()->to($kembali)
                ->with('error', 'RPJMD periode ini tidak sedang terkunci, jadi tidak perlu dibuka.');
        }

        $alasan = trim((string) $this->request->getPost('alasan'));

        if ($alasan === '') {
            return redirect()->to($kembali)
                ->with('error', 'Sebutkan alasan mengapa RPJMD periode ini perlu disunting.');
        }

        $svc     = new IzinSuntingService();
        $oleh    = $this->penggunaRpjmd();
        $versiId = isset($keadaan['versi']['id']) ? (int) $keadaan['versi']['id'] : null;

        try {
            $id = $svc->ajukan($scope, $alasan, $oleh, $versiId);
            // Otoritas RPJMD ada pada Admin Kabupaten sendiri — permohonan
            // langsung disetujui, tetapi TETAP tercatat sebagai keputusan.
            $svc->setujui($id, $oleh, 'Dibuka sendiri oleh Admin Kabupaten (otoritas RPJMD).');
        } catch (Throwable $e) {
            return redirect()->to($kembali)->with('error', pesanGalat($e, 'umum.rpjmdSiklus'));
        }

        if (function_exists('log_activity')) {
            log_activity('buka_kunci_rpjmd', 'rpjmd',
                'Buka kunci RPJMD ' . $tm . '-' . $ta . ' (V' . (int) ($keadaan['versi']['version_no'] ?? 0)
                . '). Alasan: ' . $alasan);
        }

        return redirect()->to($kembali)->with('success',
            'RPJMD periode ' . $tm . '-' . $ta . ' dibuka untuk disunting. Setelah selesai, tekan '
            . '"Selesai Menyunting" — dan buatlah versi baru bila perubahannya perlu dibekukan.');
    }

    /** Tutup kembali kunci setelah selesai menyunting. */
    public function izinSuntingSelesai()
    {
        if (! function_exists('user_can') || ! user_can('rpjmd.update')) {
            return redirect()->to(base_url('unauthorized'))
                ->with('error', 'Anda tidak berwenang menutup kunci RPJMD.');
        }

        $tm = (int) $this->request->getPost('tahun_mulai');
        $ta = (int) $this->request->getPost('tahun_akhir');

        $scope   = $this->rpjmdScopePeriode($tm, $ta);
        $kembali = base_url('adminkab/rpjmd');

        if ($scope === null) {
            return redirect()->to($kembali)->with('error', 'Periode RPJMD tidak sah.');
        }

        try {
            (new IzinSuntingService())->selesaikan($scope);
        } catch (Throwable $e) {
            return redirect()->to($kembali)->with('error', pesanGalat($e, 'umum.rpjmdSiklus'));
        }

        if (function_exists('log_activity')) {
            log_activity('tutup_kunci_rpjmd', 'rpjmd', 'Tutup kunci RPJMD ' . $tm . '-' . $ta . '.');
        }

        return redirect()->to($kembali)->with('success',
            'RPJMD periode ' . $tm . '-' . $ta . ' dikunci kembali.');
    }
}
