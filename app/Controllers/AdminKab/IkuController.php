<?php

namespace App\Controllers\AdminKab;

use App\Controllers\BaseController;
use App\Controllers\Concerns\IkuFormTrait;
use App\Models\Opd\IkuModel;
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
        $mode      = $this->modeAktif();
        $opdFilter = $this->opdFilter();

        [$groupedData, $periode] = $this->resolvePeriode($mode, $opdFilter);

        $ikuData = $this->ikuModel->getMatrix([
            'level'       => $mode,
            'opd_id'      => $mode === 'opd' ? $opdFilter : null,
            'tahun_mulai' => $periode['tahun_mulai'] ?? null,
            'tahun_akhir' => $periode['tahun_akhir'] ?? null,
        ]);

        return view('adminKabupaten/iku/iku', [
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
        if (!user_can('iku_kab.create')) {
            return redirect()->to(base_url('adminkab/iku'))
                ->with('error', 'Anda tidak memiliki akses untuk menambah IKU.');
        }

        return view('adminKabupaten/iku/tambah_iku', [
            'title'          => 'Tambah IKU Kabupaten',
            'satuan_options' => $this->ikuModel->getSatuanOptions(),
        ]);
    }

    /* =========================================================
     * SIMPAN BARU
     * =======================================================*/
    public function save()
    {
        if (!user_can('iku_kab.create')) {
            return redirect()->to(base_url('adminkab/iku'))
                ->with('error', 'Anda tidak memiliki akses untuk menambah IKU.');
        }

        $data = $this->bacaFormIku($this->request->getPost() ?? []);

        if ($error = $this->validasiFormIku($data)) {
            return redirect()->back()->withInput()->with('error', $error);
        }

        // IKU yang dibuat di sini selalu tingkat kabupaten.
        $data['opd_id'] = null;

        try {
            $this->ikuModel->createComplete($data);
        } catch (\Throwable $e) {
            log_message('error', '[IKU SAVE KAB] ' . $e->getMessage());

            return redirect()->back()->withInput()->with('error', 'Gagal menyimpan IKU: ' . $e->getMessage());
        }

        return redirect()->to(base_url('adminkab/iku?mode=kabupaten'))
            ->with('success', 'IKU berhasil disimpan.');
    }

    /* =========================================================
     * SYNC DARI RPJMD — PRATINJAU
     * GET adminkab/iku/sync?periode=2025-2029
     * =======================================================*/
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

        $kandidat = $this->ikuModel->getKandidatSync(
            'rpjmd',
            null,
            $periode['tahun_mulai'],
            $periode['tahun_akhir']
        );

        return view('adminKabupaten/iku/sync_iku', [
            'title'            => 'Sync IKU dari RPJMD',
            'kandidat'         => $kandidat,
            'daftar_periode'   => $daftarPeriode,
            'periode'          => $periode,
            'years'            => $periode['years'],
        ]);
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

        $pilihan = $this->bacaPilihanSync($post);
        if (empty($pilihan)) {
            return redirect()->to(base_url('adminkab/iku/sync?periode=' . $periode))
                ->with('error', 'Pilih minimal satu indikator untuk disalin.');
        }

        try {
            $stat = $this->ikuModel->importSync(
                'rpjmd',
                null,
                $pilihan,
                $daftarPeriode[$periode]['tahun_mulai'],
                $daftarPeriode[$periode]['tahun_akhir']
            );
        } catch (\Throwable $e) {
            log_message('error', '[IKU SYNC KAB] ' . $e->getMessage());

            return redirect()->to(base_url('adminkab/iku/sync?periode=' . $periode))
                ->with('error', 'Gagal menyalin data RPJMD: ' . $e->getMessage());
        }

        return redirect()->to(base_url('adminkab/iku?mode=kabupaten&periode=' . $periode))
            ->with('success', $this->pesanHasilSync($stat));
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

        return view('adminKabupaten/iku/edit_iku', [
            'title'          => 'Edit IKU Kabupaten',
            'iku'            => $sasaran,
            'satuan_options' => $this->ikuModel->getSatuanOptions(),
        ]);
    }

    /* =========================================================
     * UPDATE
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

        $data = $this->bacaFormIku($this->request->getPost() ?? []);

        if ($error = $this->validasiFormIku($data)) {
            return redirect()->back()->withInput()->with('error', $error);
        }

        try {
            $this->ikuModel->updateComplete($sasaranId, $data);
        } catch (\Throwable $e) {
            log_message('error', '[IKU UPDATE KAB] ' . $e->getMessage());

            return redirect()->back()->withInput()->with('error', 'Gagal mengubah IKU: ' . $e->getMessage());
        }

        return redirect()->to(base_url('adminkab/iku?mode=kabupaten'))
            ->with('success', 'IKU berhasil diperbarui.');
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
