<?php

namespace App\Controllers\AdminOpd;

use App\Controllers\BaseController;
use App\Controllers\Concerns\IkuFormTrait;
use App\Models\Opd\IkuModel;
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

        return view('adminOpd/iku/iku', [
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
    public function tambah()
    {
        $opdId = $this->opdIdSesi();
        if ($opdId === false) {
            return redirect()->to('/login')->with('error', 'Silakan login terlebih dahulu');
        }

        if (!user_can('iku_opd.create')) {
            return redirect()->to(base_url('adminopd/iku'))
                ->with('error', 'Anda tidak memiliki akses untuk menambah IKU.');
        }

        return view('adminOpd/iku/tambah_iku', [
            'title'          => 'Tambah IKU',
            'satuan_options' => $this->ikuModel->getSatuanOptions(),
            'opd_list'       => $opdId === null ? $this->opdModel->orderBy('nama_opd', 'ASC')->findAll() : [],
            'is_lintas_opd'  => $opdId === null,
            'role'           => session()->get('role'),
        ]);
    }

    /* =========================================================
     * SIMPAN BARU
     * =======================================================*/
    public function save()
    {
        $opdId = $this->opdIdSesi();
        if ($opdId === false) {
            return redirect()->to('/login')->with('error', 'Silakan login terlebih dahulu');
        }

        if (!user_can('iku_opd.create')) {
            return redirect()->to(base_url('adminopd/iku'))
                ->with('error', 'Anda tidak memiliki akses untuk menambah IKU.');
        }

        $data = $this->bacaFormIku($this->request->getPost() ?? []);

        if ($error = $this->validasiFormIku($data)) {
            return redirect()->back()->withInput()->with('error', $error);
        }

        // Super admin lintas-OPD wajib memilih OPD; admin_opd/kecamatan terkunci
        // ke OPD-nya sendiri (nilai dari form diabaikan — cegah IDOR).
        $data['opd_id'] = $opdId;
        if ($opdId === null) {
            $pilihanOpd = (int) ($this->request->getPost('opd_id') ?? 0);
            if ($pilihanOpd <= 0) {
                return redirect()->back()->withInput()->with('error', 'OPD pemilik IKU wajib dipilih.');
            }
            $data['opd_id'] = $pilihanOpd;
        }

        try {
            $this->ikuModel->createComplete($data);
        } catch (\Throwable $e) {
            log_message('error', '[IKU SAVE OPD] ' . $e->getMessage());

            return redirect()->back()->withInput()->with('error', 'Gagal menyimpan IKU: ' . $e->getMessage());
        }

        return redirect()->to(base_url('adminopd/iku'))->with('success', 'IKU berhasil ditambahkan.');
    }

    /* =========================================================
     * SYNC DARI RENSTRA — PRATINJAU
     * GET adminopd/iku/sync?periode=2025-2029
     * =======================================================*/
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

        $kandidat = $this->ikuModel->getKandidatSync(
            'renstra',
            $opdId,
            $periode['tahun_mulai'],
            $periode['tahun_akhir']
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
        ]);
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

        $pilihan = $this->bacaPilihanSync($post);
        if (empty($pilihan)) {
            return redirect()->to(base_url('adminopd/iku/sync?periode=' . $periode))
                ->with('error', 'Pilih minimal satu indikator untuk disalin.');
        }

        try {
            $stat = $this->ikuModel->importSync(
                'renstra',
                $opdId,
                $pilihan,
                $daftarPeriode[$periode]['tahun_mulai'],
                $daftarPeriode[$periode]['tahun_akhir']
            );
        } catch (\Throwable $e) {
            log_message('error', '[IKU SYNC OPD] ' . $e->getMessage());

            return redirect()->to(base_url('adminopd/iku/sync?periode=' . $periode))
                ->with('error', 'Gagal menyalin data Renstra: ' . $e->getMessage());
        }

        return redirect()->to(base_url('adminopd/iku?periode=' . $periode))
            ->with('success', $this->pesanHasilSync($stat));
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

        return view('adminOpd/iku/edit_iku', [
            'title'          => 'Edit IKU',
            'iku'            => $sasaran,
            'satuan_options' => $this->ikuModel->getSatuanOptions(),
            'opd_list'       => $opdId === null ? $this->opdModel->orderBy('nama_opd', 'ASC')->findAll() : [],
            'is_lintas_opd'  => $opdId === null,
            'role'           => session()->get('role'),
        ]);
    }

    /* =========================================================
     * UPDATE
     * =======================================================*/
    public function update()
    {
        $opdId = $this->opdIdSesi();
        if ($opdId === false) {
            return redirect()->to('/login')->with('error', 'Silakan login terlebih dahulu');
        }

        if (!user_can('iku_opd.update')) {
            return redirect()->to(base_url('adminopd/iku'))
                ->with('error', 'Anda tidak memiliki akses untuk mengubah IKU.');
        }

        $sasaranId = (int) ($this->request->getPost('iku_sasaran_id') ?? 0);
        if ($sasaranId <= 0) {
            return redirect()->to(base_url('adminopd/iku'))->with('error', 'ID IKU tidak ditemukan.');
        }

        if (!$this->bolehAksesSasaran($sasaranId)) {
            return redirect()->to(base_url('adminopd/iku'))
                ->with('error', 'Anda tidak memiliki akses ke IKU OPD lain.');
        }

        $data = $this->bacaFormIku($this->request->getPost() ?? []);

        if ($error = $this->validasiFormIku($data)) {
            return redirect()->back()->withInput()->with('error', $error);
        }

        // Pemindahan IKU antar-OPD hanya untuk super admin lintas-OPD.
        if ($opdId === null) {
            $pilihanOpd = (int) ($this->request->getPost('opd_id') ?? 0);
            if ($pilihanOpd > 0) {
                $data['opd_id'] = $pilihanOpd;
            }
        }

        try {
            $this->ikuModel->updateComplete($sasaranId, $data);
        } catch (\Throwable $e) {
            log_message('error', '[IKU UPDATE OPD] ' . $e->getMessage());

            return redirect()->back()->withInput()->with('error', 'Gagal mengubah IKU: ' . $e->getMessage());
        }

        return redirect()->to(base_url('adminopd/iku'))->with('success', 'Data IKU berhasil diperbarui.');
    }

    /* =========================================================
     * HAPUS
     * =======================================================*/
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
