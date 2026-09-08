<?php

namespace App\Controllers\Concerns;

use App\Models\DokumenVersiModel;
use App\Models\Versi\RenstraVersiModel;
use App\Services\Version\VersionScope;
use Throwable;

/**
 * Mengisi dan membaca ISI sebuah versi Renstra.
 *
 * =====================================================================
 * MENGAPA FORMNYA SAMA PERSIS DENGAN "TAMBAH RENSTRA"
 *
 * Menyusun versi baru adalah pekerjaan yang sama dengan menyusun Renstra:
 * memilih sasaran RPJMD, menulis tujuan, indikator, sasaran, dan targetnya.
 * Yang berbeda hanya ke mana hasilnya ditulis. Karena itu berkas view-nya
 * DIPAKAI ULANG, bukan disalin — bila kelak ada medan baru di Tambah Renstra,
 * medan itu otomatis ikut ada di sini, dan tidak ada kemungkinan keduanya
 * menyimpang diam-diam.
 *
 * =====================================================================
 * SATU PENGIRIMAN = SATU TUJUAN
 *
 * Bentuk POST form itu memang satu tujuan beserta seluruh anaknya. Jadi versi
 * disusun bertahap: tambah tujuan, tambah tujuan lagi, sunting yang keliru.
 * Ini juga yang membuat penyusunan versi terasa sama seperti mengisi Renstra
 * pertama kali, bukan seperti mengisi formulir asing.
 *
 * =====================================================================
 * YANG DIJAGA
 *
 * - Hanya DRAFT milik OPD sendiri yang bisa diisi. Keduanya diperiksa
 *   `versiDraftMilikSaya()`, yang mengambil opd dari SESI.
 * - `opd_id` dan periode sasaran diambil dari LINGKUP versi, tidak pernah dari
 *   form — form tidak boleh memindahkan kepemilikan atau menggeser periode.
 * - Seluruh penulisan berjalan di dalam satu transaksi; arsip setengah jadi
 *   lebih berbahaya daripada penyimpanan yang gagal terang-terangan.
 */
trait RenstraVersiIsiTrait
{
    /** Form kosong: menambah satu tujuan ke arsip versi. */
    public function versiTujuanTambah($id = null)
    {
        return $this->versiTujuanForm((int) $id, null);
    }

    /** Form terisi: menyunting satu tujuan yang sudah ada di arsip versi. */
    public function versiTujuanSunting($id = null, $tujuanId = null)
    {
        return $this->versiTujuanForm((int) $id, (int) $tujuanId);
    }

    public function versiTujuanSimpan($id = null)
    {
        if (! $this->versiBoleh('update_draft')) {
            return $this->versiTolakIzin();
        }

        $versi = $this->versiDraftMilikSaya((int) $id);

        if (! is_array($versi)) {
            return $versi;
        }

        $scope    = VersionScope::dariBaris($versi);
        $tujuanId = (int) ($this->request->getPost('arsip_tujuan_id') ?? 0);
        $kembali  = base_url('adminopd/renstra/versi/lihat/' . (int) $id);

        $db = \Config\Database::connect();

        try {
            $db->transBegin();
            $db->resetTransStatus();

            $arsip = new RenstraVersiModel($db);

            $arsip->simpanTujuanDariForm(
                (int) $id,
                $scope,
                $this->request->getPost() ?? [],
                $tujuanId > 0 ? $tujuanId : null
            );

            if ($db->transStatus() === false) {
                $db->transRollback();

                return redirect()->to($kembali)->with('error', 'Penyimpanan ditolak basis data.');
            }

            $db->transCommit();
        } catch (Throwable $e) {
            if ($db->transDepth > 0) {
                $db->transRollback();
            }

            // Kembali ke formnya, bukan ke daftar: yang salah adalah isian, dan
            // memulangkan pengguna ke daftar berarti ketikannya hilang percuma.
            return redirect()->back()->withInput()
                ->with('error', pesanGalatBerawalan($e, 'Gagal menyimpan tujuan', 'umum.renstraVersiIsi'));
        }

        return redirect()->to($kembali)->with('success', $tujuanId > 0
            ? 'Tujuan pada draft versi diperbarui.'
            : 'Tujuan ditambahkan ke draft versi.');
    }

    public function versiTujuanHapus($id = null, $tujuanId = null)
    {
        if (! $this->versiBoleh('update_draft')) {
            return $this->versiTolakIzin();
        }

        $versi = $this->versiDraftMilikSaya((int) $id);

        if (! is_array($versi)) {
            return $versi;
        }

        $kembali = base_url('adminopd/renstra/versi/lihat/' . (int) $id);
        $db      = \Config\Database::connect();

        // Anak-anaknya ikut terbuang lewat ON DELETE CASCADE.
        $db->table('renstra_versi_tujuan')
            ->where('id', (int) $tujuanId)->where('version_id', (int) $id)->delete();

        return $db->affectedRows() > 0
            ? redirect()->to($kembali)->with('success', 'Tujuan dihapus dari draft versi.')
            : redirect()->to($kembali)->with('error', 'Tujuan tidak ditemukan pada versi ini.');
    }

    /**
     * Kerangka bersama kedua form. Dipisah supaya "tambah" dan "sunting" tidak
     * bisa berbeda perlakuannya tanpa sengaja.
     */
    private function versiTujuanForm(int $id, ?int $tujuanId)
    {
        if (! $this->versiBoleh('update_draft')) {
            return $this->versiTolakIzin();
        }

        $versi = $this->versiDraftMilikSaya($id);

        if (! is_array($versi)) {
            return $versi;
        }

        $scope   = VersionScope::dariBaris($versi);
        $db      = \Config\Database::connect();
        $arsip   = new RenstraVersiModel($db);
        $isiAwal = null;

        if ($tujuanId !== null && $tujuanId > 0) {
            $isiAwal = $arsip->tujuanUntukForm($id, $tujuanId);

            if ($isiAwal === null) {
                return redirect()->to(base_url('adminopd/renstra/versi/lihat/' . $id))
                    ->with('error', 'Tujuan tidak ditemukan pada versi ini.');
            }
        }

        $opdId      = (int) session()->get('opd_id');
        $currentOpd = $this->opdModel->find($opdId);

        return view('adminOpd/renstra/tambah_renstra', [
            'title'          => ($tujuanId ? 'Sunting' : 'Tambah') . ' Tujuan — ' . $versi['label'],
            'rpjmd_sasaran'  => $this->rpjmdModel->getAllSasaran(),
            'satuan_options' => $this->pkModel->getAllSatuan(),
            'current_opd'    => $currentOpd,

            // --- parameter form bersama ---
            'judulForm'    => ($tujuanId ? 'Sunting Tujuan' : 'Tambah Tujuan') . ' — ' . $versi['label'],
            'formAction'   => base_url('adminopd/renstra/versi/tujuan/simpan/' . $id),
            'kembaliUrl'   => base_url('adminopd/renstra/versi/lihat/' . $id),
            'hiddenExtra'  => ['arsip_tujuan_id' => $tujuanId ?? ''],
            'periodeKunci' => ['mulai' => $scope->periodeMulai(), 'akhir' => $scope->periodeAkhir()],
            'isiAwal'      => $isiAwal,
            'catatanForm'  => 'Isian ini masuk ke <strong>draft ' . esc($versi['label'])
                . '</strong>, bukan ke Renstra yang sedang berjalan. Renstra berjalan baru berubah '
                . 'setelah versi ini diajukan dan ditetapkan Admin Kabupaten.',
        ]);
    }

    /* =========================================================
     * MEMILIH VERSI UNTUK DILIHAT & DICETAK
     * =======================================================*/

    /**
     * Versi yang pantas ditawarkan pada pemilih: sudah ditetapkan DAN berisi.
     *
     * Baseline otomatis hasil migrasi sengaja disaring keluar. Ia published,
     * tetapi arsipnya kosong — menawarkannya berarti menawarkan tabel kosong
     * yang tampak seperti data hilang.
     *
     * @return array<int,array<string,mixed>>
     */
    protected function renstraVersiPilihan(string $periode): array
    {
        $scope = $this->versiScope($periode);

        if ($scope === null || ! $this->versi()->siap()) {
            return [];
        }

        $db    = \Config\Database::connect();
        $keluar = [];

        foreach ($this->versi()->daftar($scope, [DokumenVersiModel::STATUS_PUBLISHED]) as $v) {
            $isi = (int) $db->table('renstra_versi_tujuan')
                ->where('version_id', (int) $v['id'])->countAllResults();

            if ($isi > 0) {
                $keluar[] = $v;
            }
        }

        return $keluar;
    }

    /**
     * Versi yang sedang dipilih pemakai, atau null bila ia melihat kondisi
     * berjalan.
     *
     * Nilai dari query string TIDAK dipercaya: versinya harus benar-benar ada
     * pada daftar pilihan, yang sudah dibatasi lingkup OPD dari sesi. Dengan
     * begitu tidak ada jalan mengintip versi OPD lain lewat mengarang id.
     *
     * Tiga nilai yang berbeda artinya, dan perbedaannya penting:
     *   tidak ada parameter  -> pemanggil boleh jatuh ke tunjukan tampilan utama
     *   'berjalan' (atau apa pun yang bukan id sah) -> kondisi berjalan, TEGAS
     *   angka id sah -> versi itu
     *
     * Nilai 'berjalan' harus bisa dibedakan dari "tidak ada parameter", sebab
     * memilih kondisi berjalan sementara ada tunjukan yang terpasang adalah
     * permintaan yang sah dan tidak boleh ditimpa balik oleh tunjukan itu.
     */
    protected function renstraVersiDipilih($param, string $periode): ?array
    {
        $id = (int) $param;

        if ($id <= 0 || $periode === '') {
            return null;
        }

        foreach ($this->renstraVersiPilihan($periode) as $v) {
            if ((int) $v['id'] === $id) {
                return $v;
            }
        }

        return null;
    }

    /**
     * Versi yang ditunjuk sebagai tampilan utama periode ini, bila ada dan
     * masih layak.
     *
     * Kelayakannya diperiksa ulang lewat daftar pilihan, bukan dipercaya
     * begitu saja dari kolomnya. Sebuah versi bisa saja ditunjuk lalu
     * arsipnya dikosongkan oleh jalur lain; menampilkan tabel kosong sebagai
     * "tampilan utama" jauh lebih membingungkan daripada diam-diam kembali ke
     * kondisi berjalan.
     */
    protected function renstraVersiTunjukan(string $periode): ?array
    {
        $scope = $this->versiScope($periode);

        if ($scope === null || ! $this->versi()->siapTampilan()) {
            return null;
        }

        $tunjuk = $this->versi()->tampilanUtama($scope);

        if ($tunjuk === null) {
            return null;
        }

        foreach ($this->renstraVersiPilihan($periode) as $v) {
            if ((int) $v['id'] === (int) $tunjuk['id']) {
                return $v;
            }
        }

        return null;
    }

    /**
     * Isi Renstra sebuah versi dalam bentuk yang sama dengan data berjalan.
     *
     * @return array<int,array<string,mixed>>
     */
    protected function renstraIsiVersi(array $versi, ?string $cariRpjmd, ?string $cariTujuan): array
    {
        return (new RenstraVersiModel())
            ->bacaSepertiLive((int) $versi['id'], $cariRpjmd, $cariTujuan);
    }
}
