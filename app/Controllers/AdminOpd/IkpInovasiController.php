<?php

namespace App\Controllers\AdminOpd;

use App\Controllers\BaseController;
use App\Models\Ikp\IkpInovasiModel;
use App\Models\OpdModel;
use App\Models\PegawaiModel;
use App\Models\PkModel;
use App\Services\IkpRekapService;
use CodeIgniter\Exceptions\PageNotFoundException;
use Throwable;

/**
 * Rencana Inovasi Perangkat Daerah (Lampiran III PK) + cetak Lampiran PK Eselon II.
 *
 * Rute (Config\Routes, blok AKSARA+ di ujung berkas):
 *   GET  adminopd/ikp/inovasi                  index   daftar per tahun + form
 *   POST adminopd/ikp/inovasi/save             save
 *   POST adminopd/ikp/inovasi/update/(:num)    update
 *   POST adminopd/ikp/inovasi/delete/(:num)    delete
 *   GET  adminopd/ikp/lampiran-pk?tahun=       lampiranPk  (SATU PDF: perjanjian +
 *        Lampiran I IKU & anggaran + II IKP + III inovasi + IV tahunan + V bulanan)
 *
 * =====================================================================
 * LINGKUP OPD
 *
 * admin_opd / admin_kecamatan: OPD SELALU dari sesi — opd_id kiriman
 * diabaikan sepenuhnya. admin (super admin) boleh memilih OPD lewat
 * ?opd_id= / opd_id POST, tetapi hanya dari daftar OPD sah (bukan
 * OpdModel::EXCLUDED_OPD_IDS). Kepemilikan baris selalu dicek terhadap baris
 * di basis data, tidak pernah terhadap nilai kiriman.
 *
 * =====================================================================
 * MENGAPA LAMPIRAN PK DI SINI, BUKAN DI PkController::cetak
 *
 * Mengubah PkController::cetak berarti mengubah SEMUA cetak PK (Bupati, JPT,
 * administrator, pengawas). Di sini halaman perjanjian dan Lampiran I dibangun
 * ulang dengan data yang PERSIS sama (PkModel::getPkById + getProgramByJenis)
 * dan dirender dengan view yang sama (adminOpd/pk/cetak & cetak-L), lalu IKP
 * ditempelkan sebagai Lampiran II–V. Berbeda dengan rute cetak lama — yang
 * mencetak PK mana pun asal id-nya diketahui (kritik A7) — PK di sini WAJIB
 * milik OPD sesi.
 */
class IkpInovasiController extends BaseController
{
    protected $helpers = ['cascading_label', 'ikp', 'format'];

    /** Batas panjang isian (kolom nama VARCHAR 255; strictOn=false memotong diam-diam). */
    private const MAKS_NAMA      = 255;
    private const MAKS_DESKRIPSI = 5000;

    private IkpInovasiModel $inovasi;
    private IkpRekapService $rekap;
    private PkModel $pkModel;
    private PegawaiModel $pegawaiModel;
    private $db;

    /** @var array<string, mixed>|null lingkup OPD (dihitung sekali per request) */
    private ?array $lingkupCache = null;

    public function __construct()
    {
        $this->inovasi      = new IkpInovasiModel();
        $this->rekap        = new IkpRekapService();
        $this->pkModel      = new PkModel();
        $this->pegawaiModel = new PegawaiModel();
        $this->db           = \Config\Database::connect();
    }

    /**
     * Penjaga izin satu pintu (pola CascadingIzinTrait): setiap metode publik
     * WAJIB disebut di peta; yang tidak disebut ditolak, bukan diloloskan.
     */
    public function _remap(string $method, ...$params)
    {
        if (str_starts_with($method, '_') || ! method_exists($this, $method)
            || ! (new \ReflectionMethod($this, $method))->isPublic()) {
            throw PageNotFoundException::forPageNotFound();
        }

        $peta = [
            'index'      => 'ikp_opd.view',
            'lampiranPk' => 'ikp_opd.view',
            'save'       => 'ikp_opd.create',
            'update'     => 'ikp_opd.update',
            'delete'     => 'ikp_opd.delete',
        ];
        if (! isset($peta[$method])) {
            throw PageNotFoundException::forPageNotFound();
        }

        if (! user_can($peta[$method])) {
            $pesan = str_ends_with($peta[$method], '.view')
                ? 'Anda tidak memiliki akses ke Kinerja Prioritas (IKP).'
                : 'Anda hanya dapat melihat Rencana Inovasi, tidak mengubahnya.';

            return redirect()->back()->with('error', $pesan);
        }

        if (! $this->inovasi->siap() || ! $this->rekap->siap()) {
            return redirect()->to(base_url('adminopd/dashboard'))
                ->with('error', 'Tabel Kinerja Prioritas (IKP) belum tersedia. Hubungi administrator untuk menjalankan pembaruan basis data.');
        }

        return $this->$method(...$params);
    }

    /* =====================================================================
     * RENCANA INOVASI
     * ===================================================================*/

    /** GET adminopd/ikp/inovasi?tahun=[&opd_id= untuk super admin] */
    public function index()
    {
        $lingkup = $this->lingkup();
        $opdId   = $lingkup['opd_id'];
        $tahunan = $this->tahunPilihan($opdId);
        $tahun   = $this->tahunDiminta($this->request->getGet('tahun'), $tahunan);

        $daftar = [];
        $ikp    = [];
        $pk     = [];
        if ($opdId !== null) {
            $daftar = $this->db->table('ikp_inovasi n')
                ->select('n.*, i.output_prioritas AS ikp_nama, i.dihapus_pada AS ikp_dihapus')
                ->join('ikp i', 'i.id = n.ikp_id', 'left')
                ->where('n.opd_id', $opdId)
                ->where('n.tahun', $tahun)
                ->orderBy('n.urutan', 'ASC')->orderBy('n.id', 'ASC')
                ->get()->getResultArray();
            $ikp = $this->rekap->daftar($opdId);
            $pk  = $this->pkKandidat($opdId, $tahun);
        }

        return view('ikp/inovasi', [
            'title'        => 'Rencana Inovasi Perangkat Daerah',
            'lingkup'      => $lingkup,
            'opdId'        => $opdId,
            'tahun'        => $tahun,
            'tahunList'    => $tahunan,
            'daftar'       => $daftar,
            'ikpList'      => $ikp,
            'pkList'       => $pk,
            'pkPilihan'    => $pk === [] ? null : $this->pkBawaan($pk, $opdId),
            'bolehTambah'  => user_can('ikp_opd.create'),
            'bolehUbah'    => user_can('ikp_opd.update'),
            'bolehHapus'   => user_can('ikp_opd.delete'),
            'qsOpd'        => $lingkup['boleh_pilih'] && $opdId ? '&opd_id=' . $opdId : '',
        ]);
    }

    /** POST adminopd/ikp/inovasi/save */
    public function save()
    {
        $lingkup = $this->lingkup();
        $opdId   = $lingkup['opd_id'];
        if ($opdId === null) {
            return redirect()->to(base_url('adminopd/ikp/inovasi'))->with('error', 'Pilih perangkat daerah terlebih dahulu.');
        }

        $tahunan = $this->tahunPilihan($opdId);
        $tahun   = (int) $this->request->getPost('tahun');
        $balik   = $this->urlDaftar($tahun, $lingkup);

        if (! in_array($tahun, $tahunan, true)) {
            return redirect()->to($balik)->withInput()->with('error', 'Tahun rencana inovasi tidak valid.');
        }

        [$data, $galat] = $this->bacaIsian($opdId);
        if ($galat !== null) {
            return redirect()->to($balik)->withInput()->with('error', $galat);
        }

        try {
            if ($data['urutan'] === null) {
                $maks           = $this->db->table('ikp_inovasi')->selectMax('urutan', 'm')
                    ->where(['opd_id' => $opdId, 'tahun' => $tahun])->get()->getRowArray();
                $data['urutan'] = (int) ($maks['m'] ?? 0) + 1;
            }
            $ok = $this->inovasi->insert($data + ['opd_id' => $opdId, 'tahun' => $tahun]);
            if ($ok === false) {
                throw new \RuntimeException('Rencana inovasi gagal disimpan.');
            }
        } catch (Throwable $e) {
            return redirect()->to($balik)->withInput()->with('error', pesanGalatBerawalan($e, 'Rencana inovasi gagal disimpan', 'opd.ikp_inovasi'));
        }

        return redirect()->to($balik)->with('success', 'Rencana inovasi "' . $data['nama'] . '" berhasil ditambahkan.');
    }

    /** POST adminopd/ikp/inovasi/update/(:num) */
    public function update($id = null)
    {
        $baris = $this->barisMilik((int) $id);
        if (is_string($baris)) {
            return redirect()->to(base_url('adminopd/ikp/inovasi'))->with('error', $baris);
        }
        $lingkup = $this->lingkup();
        $balik   = $this->urlDaftar((int) $baris['tahun'], $lingkup, (int) $baris['opd_id']);

        [$data, $galat] = $this->bacaIsian((int) $baris['opd_id']);
        if ($galat !== null) {
            return redirect()->to($balik)->with('error', $galat);
        }
        if ($data['urutan'] === null) {
            unset($data['urutan']);
        }

        try {
            if ($this->inovasi->update((int) $baris['id'], $data) === false) {
                throw new \RuntimeException('Rencana inovasi gagal diperbarui.');
            }
        } catch (Throwable $e) {
            return redirect()->to($balik)->with('error', pesanGalatBerawalan($e, 'Rencana inovasi gagal diperbarui', 'opd.ikp_inovasi'));
        }

        return redirect()->to($balik)->with('success', 'Rencana inovasi "' . $data['nama'] . '" berhasil diperbarui.');
    }

    /** POST adminopd/ikp/inovasi/delete/(:num) */
    public function delete($id = null)
    {
        $baris = $this->barisMilik((int) $id);
        if (is_string($baris)) {
            return redirect()->to(base_url('adminopd/ikp/inovasi'))->with('error', $baris);
        }
        $balik = $this->urlDaftar((int) $baris['tahun'], $this->lingkup(), (int) $baris['opd_id']);

        try {
            // Hapus fisik aman: tidak ada tabel lain yang merujuk ikp_inovasi.
            $this->db->table('ikp_inovasi')->where('id', (int) $baris['id'])
                ->where('opd_id', (int) $baris['opd_id'])->delete();
        } catch (Throwable $e) {
            return redirect()->to($balik)->with('error', pesanGalatBerawalan($e, 'Rencana inovasi gagal dihapus', 'opd.ikp_inovasi'));
        }

        return redirect()->to($balik)->with('success', 'Rencana inovasi "' . $baris['nama'] . '" telah dihapus.');
    }

    /* =====================================================================
     * LAMPIRAN PK ESELON II (PDF)
     * ===================================================================*/

    /** GET adminopd/ikp/lampiran-pk?tahun=[&pk_id=][&opd_id= untuk super admin] */
    public function lampiranPk()
    {
        $lingkup = $this->lingkup();
        $opdId   = $lingkup['opd_id'];
        if ($opdId === null) {
            return redirect()->to(base_url('adminopd/ikp/inovasi'))
                ->with('error', 'Pilih perangkat daerah terlebih dahulu, lalu cetak Lampiran PK dari halaman ini.');
        }
        $tahunan = $this->tahunPilihan($opdId);
        $tahun   = $this->tahunDiminta($this->request->getGet('tahun'), $tahunan);
        $opd     = $lingkup['opd'];

        // ---- PK Eselon II OPD ini (jpt; camat untuk kecamatan) ----
        $kandidat = $this->pkKandidat($opdId, $tahun);
        $pkIdReq  = (int) ($this->request->getGet('pk_id') ?? 0);
        $pkPilih  = null;
        if ($pkIdReq > 0) {
            foreach ($kandidat as $k) {
                if ((int) $k['id'] === $pkIdReq) {
                    $pkPilih = $k;
                }
            }
            if ($pkPilih === null) {
                // MENGAPA ditolak, bukan dicetak: rute cetak PK lama mencetak PK
                // OPD mana pun asal id-nya ditebak (kritik A7). Di sini tidak.
                return redirect()->to($this->urlDaftar($tahun, $lingkup))
                    ->with('error', 'Perjanjian Kinerja yang diminta tidak ditemukan untuk perangkat daerah Anda pada tahun ' . $tahun . '.');
            }
        } elseif ($kandidat !== []) {
            $pkPilih = $this->pkBawaan($kandidat, $opdId);
        }

        try {
            $pdf = $this->susunLampiran($opd, $tahun, $pkPilih);
        } catch (Throwable $e) {
            return redirect()->to($this->urlDaftar($tahun, $lingkup))
                ->with('error', pesanGalatBerawalan($e, 'Lampiran PK gagal dicetak', 'opd.ikp_lampiran_pk'));
        }

        $nama = 'Lampiran-PK-Eselon-II-' . preg_replace('/[^A-Za-z0-9]+/', '-', (string) ($opd['singkatan'] ?: $opd['nama_opd'])) . '-' . $tahun . '.pdf';
        $this->response->setHeader('Content-Type', 'application/pdf');

        return $pdf->Output(trim($nama, '-'), 'I');
    }

    /**
     * Susun PDF lengkap. Tanpa PK: hanya Lampiran II–V + catatan jelas.
     *
     * @param array<string, mixed>      $opd
     * @param array<string, mixed>|null $pkRow baris ringkas dari pkKandidat()
     */
    private function susunLampiran(array $opd, int $tahun, ?array $pkRow): \App\Libraries\PdfMpdf
    {
        helper(['format', 'setting', 'pdf']);
        $opdId = (int) $opd['id'];

        // ---------- Data PK (sama persis dengan AdminOpd\PkController::cetak) ----------
        $pk        = null;
        $htmlPk    = null;
        $htmlLampI = null;
        if ($pkRow !== null) {
            $pk = $this->pkModel->getPkById((int) $pkRow['id']);
            if (! $pk || (int) $pk['opd_id'] !== $opdId) {
                $pk = null; // penjaga kedua: tidak pernah mencetak PK OPD lain
            }
        }
        if ($pk !== null) {
            $jenis              = (string) $pk['jenis'];
            $pk['logo_url']     = FCPATH . 'assets/images/logo.png';
            $pk['isKecamatan']  = $jenis === 'camat';
            $pk['program_pk']   = $this->pkModel->getProgramByJenis((int) $pk['pk_id'], $jenis);
            $level              = $this->pegawaiModel->getLevelByPegawaiId($pk['pihak_1']);
            $tampilkanProgram   = ! ((int) $pk['opd_id'] === 2 && ($level['level'] ?? null) === 'VERIFIKATOR');

            $htmlPk    = view('adminOpd/pk/cetak', $pk);
            $htmlLampI = view('adminOpd/pk/cetak-L', array_merge($pk, ['tampilkanProgram' => $tampilkanProgram]));

            // MENGAPA teks view lama ditambal di sini, bukan view-nya diubah:
            // view yang sama dipakai semua cetak PK. Tambalan hanya MENAMBAH
            // label; bila pola tidak ditemukan (view berubah), cetakan tetap
            // jalan tanpa label — tidak pernah rusak.
            //  * "Lampiran I" — agar urutan lampiran dokumen gabungan jelas.
            //  * "APBD Murni" — AKSARA hanya menyimpan anggaran murni
            //    (program_pk.jenis_anggaran; kritik C15); label ini mencegah
            //    angka murni terbaca sebagai anggaran perubahan.
            $htmlLampI = preg_replace(
                '~<h4 style="text-align: center;">~',
                '<div style="font-family: \'Times New Roman\', serif; font-size: 11pt; font-weight: bold; margin: 0 0 6px 0;">Lampiran I :'
                . '<br><span style="font-weight: normal;">Sasaran Strategis, Indikator Kinerja, Target, serta Program dan Anggaran (APBD Murni)</span></div>'
                . '<h4 style="text-align: center;">',
                $htmlLampI,
                1
            );
            $htmlLampI = str_replace('ANGGARAN (Rp)', 'ANGGARAN APBD MURNI (Rp)', $htmlLampI);
        }

        // ---------- Data IKP ----------
        $daftar   = $this->rekap->daftar($opdId);
        $ids      = array_map(static fn ($r) => (int) $r['id'], $daftar);
        $tahunan  = $this->rekap->targetTahunan($ids);
        $bulanan  = $this->rekap->bulanan($ids, $tahun);
        $periode  = $this->rekap->tahunPeriode();
        $misi     = $this->misiPeriode();
        $inovasi  = $this->db->table('ikp_inovasi n')
            ->select('n.*, i.output_prioritas AS ikp_nama')
            ->join('ikp i', 'i.id = n.ikp_id AND i.dihapus_pada IS NULL', 'left')
            ->where('n.opd_id', $opdId)->where('n.tahun', $tahun)
            ->orderBy('n.urutan', 'ASC')->orderBy('n.id', 'ASC')
            ->get()->getResultArray();

        // Urutkan IKP per misi (urutan misi RPJMD), lalu urutan IKP — seperti draf Bapperida.
        $urutMisi = array_flip(array_keys($misi));
        usort($daftar, static function ($a, $b) use ($urutMisi) {
            $ma = $urutMisi[(int) ($a['rpjmd_misi_id'] ?? 0)] ?? 999;
            $mb = $urutMisi[(int) ($b['rpjmd_misi_id'] ?? 0)] ?? 999;

            return [$ma, (int) $a['urutan'], (int) $a['id']] <=> [$mb, (int) $b['urutan'], (int) $b['id']];
        });

        // Target bulanan & triwulan tahun PK (Lampiran V) — triwulan dihitung
        // rumus yang sama dengan halaman OPD (ikp_nilai_triwulan).
        $barisBulan = [];
        foreach ($daftar as $r) {
            $id  = (int) $r['id'];
            $t   = [];
            for ($m = 1; $m <= 12; $m++) {
                $t[$m] = $bulanan[$id][$m]['target'] ?? null;
            }
            $barisBulan[$id] = [
                'bulan'    => $t,
                'triwulan' => ikp_nilai_triwulan($t, (string) ($r['metode'] ?? '')),
            ];
        }

        $namaKepala = $pk['nama_pihak_1'] ?? null;
        $dataLamp = [
            'lampOpd'       => $opd,
            'lampTahun'     => $tahun,
            'lampPk'        => $pk,
            'lampIkp'       => $daftar,
            'lampTahunan'   => $tahunan,
            'lampBulan'     => $barisBulan,
            'lampPeriode'   => $periode,
            'lampMisi'      => $misi,
            'lampVisi'      => $this->visiPeriode(),
            'lampInovasi'   => $inovasi,
            'lampMetode'    => \App\Models\Ikp\IkpModel::METODE,
            'lampKategori'  => \App\Models\Ikp\IkpModel::KATEGORI,
            'lampAdaTtd'    => $pk !== null && $namaKepala !== null,
            'lampNamaDok'   => $pk !== null
                ? 'Perjanjian Kinerja Tahun ' . $tahun
                : 'Draf Lampiran Perjanjian Kinerja Tahun ' . $tahun,
        ];

        // ---------- PDF ----------
        $mpdf = new \App\Libraries\PdfMpdf([
            'mode'              => 'utf-8',
            'format'            => 'FOLIO',
            'default_font_size' => 12,
            'mirrorMargins'     => true,
            'tempDir'           => sys_get_temp_dir(),
        ]);
        $footer = pdf_footer_aksara();
        pdf_watermark_aksara($mpdf);
        if ($pk === null) {
            // Tanpa PK tidak ada tanda tangan: tandai tegas sebagai draf.
            $mpdf->SetWatermarkText('DRAF', 0.07);
            $mpdf->showWatermarkText = true;
        }
        $mpdf->WriteHTML('img { width: 70px; height: auto; }', \Mpdf\HTMLParserMode::HEADER_CSS);
        $mpdf->SetHTMLFooter($footer, 'O');
        $mpdf->SetHTMLFooter($footer, 'E');

        if ($htmlPk !== null) {
            $mpdf->WriteHTML($htmlPk);
            $mpdf->AddPage('P');
            $mpdf->WriteHTML($htmlLampI);
        }

        $mpdf->AddPage('L');
        $mpdf->WriteHTML(view('ikp/lampiran_pk', $dataLamp));
        $mpdf->AddPage('P');
        $mpdf->WriteHTML(view('ikp/lampiran_pk_inovasi', $dataLamp));
        $mpdf->AddPage('L');
        $mpdf->WriteHTML(view('ikp/lampiran_pk_tahunan', $dataLamp));
        $mpdf->AddPage('L');
        $mpdf->WriteHTML(view('ikp/lampiran_pk_bulanan', $dataLamp));

        // Setelah semua WriteHTML: <title> view PK lama menimpa judul yang dipasang lebih awal.
        $mpdf->SetTitle('Lampiran Perjanjian Kinerja Eselon II ' . $opd['nama_opd'] . ' ' . $tahun);
        $mpdf->SetAuthor('AKSARA');

        return $mpdf;
    }

    /* =====================================================================
     * PEMBANTU
     * ===================================================================*/

    /**
     * Lingkup OPD pengguna ini.
     *
     * @return array{opd_id: ?int, opd: ?array, boleh_pilih: bool, opd_list: array}
     */
    private function lingkup(): array
    {
        if ($this->lingkupCache !== null) {
            return $this->lingkupCache;
        }
        $role  = (string) session('role');
        $opdId = null;
        $list  = [];
        $pilih = false;

        if (in_array($role, ['admin_opd', 'admin_kecamatan'], true)) {
            // MENGAPA opd_id kiriman diabaikan: OPD operator SELALU dari sesi.
            $opdId = (int) (session('opd_id') ?? 0) ?: null;
        } elseif ($role === 'admin') {
            $pilih = true;
            $list  = $this->db->table('opd')->select('id, nama_opd, jenis')
                ->whereNotIn('id', OpdModel::EXCLUDED_OPD_IDS)
                ->whereIn('jenis', [OpdModel::JENIS_OPD, OpdModel::JENIS_KECAMATAN])
                ->orderBy('nama_opd', 'ASC')->get()->getResultArray();
            $sah   = array_map('intval', array_column($list, 'id'));
            $minta = (int) ($this->request->getPost('opd_id') ?? $this->request->getGet('opd_id') ?? 0);
            if (in_array($minta, $sah, true)) {
                $opdId = $minta;
            } elseif (in_array((int) session('opd_id'), $sah, true)) {
                $opdId = (int) session('opd_id');
            }
        }

        $opd = null;
        if ($opdId !== null) {
            $opd = $this->db->table('opd')->select('id, nama_opd, singkatan, jenis, id_kepala_opd')
                ->where('id', $opdId)->get()->getRowArray();
            if ($opd === null) {
                $opdId = null;
            }
        }

        return $this->lingkupCache = ['opd_id' => $opdId, 'opd' => $opd, 'boleh_pilih' => $pilih, 'opd_list' => $list];
    }

    /**
     * Baris inovasi milik lingkup ini, atau pesan galat (string).
     *
     * @return array<string, mixed>|string
     */
    private function barisMilik(int $id)
    {
        $baris = $id > 0 ? $this->inovasi->find($id) : null;
        if (! $baris) {
            return 'Rencana inovasi tidak ditemukan.';
        }
        $lingkup = $this->lingkup();
        // Kepemilikan dicek terhadap baris DB. Super admin tanpa OPD terpilih
        // boleh menyunting baris OPD mana pun (ia memang pemegang semua izin).
        if ($lingkup['opd_id'] !== null && (int) $baris['opd_id'] !== $lingkup['opd_id']) {
            return 'Rencana inovasi tidak ditemukan.';
        }
        if ($lingkup['opd_id'] === null && ! $lingkup['boleh_pilih']) {
            return 'Akun Anda belum terhubung ke perangkat daerah.';
        }

        return $baris;
    }

    /**
     * Baca & validasi isian form (nama, deskripsi, ikp_id, urutan).
     *
     * @return array{0: array<string, mixed>, 1: ?string}
     */
    private function bacaIsian(int $opdId): array
    {
        $nama      = ikp_rapikan_teks((string) $this->request->getPost('nama'));
        $deskripsi = trim(str_replace("\r\n", "\n", (string) $this->request->getPost('deskripsi')));
        $ikpId     = (int) ($this->request->getPost('ikp_id') ?? 0);
        $urutanRaw = trim((string) $this->request->getPost('urutan'));

        if ($nama === '') {
            return [[], 'Nama rencana inovasi wajib diisi.'];
        }
        if (mb_strlen($nama) > self::MAKS_NAMA) {
            return [[], 'Nama rencana inovasi maksimal ' . self::MAKS_NAMA . ' karakter.'];
        }
        if (mb_strlen($deskripsi) > self::MAKS_DESKRIPSI) {
            return [[], 'Deskripsi maksimal ' . number_format(self::MAKS_DESKRIPSI, 0, ',', '.') . ' karakter.'];
        }
        // Pola rumah (PkRenaksiController::rxText): tanda < dan > ditolak.
        if (preg_match('/[<>]/', $nama . $deskripsi)) {
            return [[], 'Nama dan deskripsi tidak boleh memuat tanda < atau >.'];
        }
        if ($ikpId > 0 && $this->rekap->satu($opdId, $ikpId) === null) {
            return [[], 'IKP yang dipilih tidak ditemukan pada perangkat daerah ini.'];
        }
        $urutan = null;
        if ($urutanRaw !== '') {
            if (! ctype_digit($urutanRaw) || (int) $urutanRaw < 1 || (int) $urutanRaw > 999) {
                return [[], 'Nomor urut harus angka 1 sampai 999.'];
            }
            $urutan = (int) $urutanRaw;
        }

        return [[
            'nama'      => $nama,
            'deskripsi' => $deskripsi === '' ? null : $deskripsi,
            'ikp_id'    => $ikpId > 0 ? $ikpId : null,
            'urutan'    => $urutan,
        ], null];
    }

    /** Tahun yang boleh dipilih: tahun periode RPJMD aktif + tahun yang sudah punya inovasi/PK. */
    private function tahunPilihan(?int $opdId): array
    {
        $tahun = $this->rekap->tahunPeriode();
        if ($opdId !== null) {
            foreach (['ikp_inovasi', 'pk'] as $t) {
                $b = $this->db->table($t)->select('tahun')->distinct()->where('opd_id', $opdId);
                if ($t === 'pk') {
                    $b->whereIn('jenis', ['jpt', 'camat']);
                }
                foreach ($b->get()->getResultArray() as $r) {
                    $tahun[] = (int) $r['tahun'];
                }
            }
        }
        $tahun = array_values(array_unique(array_filter($tahun, static fn ($t) => $t >= 2000 && $t <= 2100)));
        sort($tahun);

        return $tahun;
    }

    /** Tahun dari query string bila sah; bawaan: tahun berjalan (WIB), lalu tahun awal periode. */
    private function tahunDiminta($minta, array $pilihan): int
    {
        $minta = (int) $minta;
        if (in_array($minta, $pilihan, true)) {
            return $minta;
        }
        // MENGAPA WIB eksplisit: AKSARA berjalan UTC; tahun berganti 7 jam
        // lebih lambat. Default tahun mengikuti kalender pengguna.
        $ini = (int) (new \DateTime('now', new \DateTimeZone('Asia/Jakarta')))->format('Y');

        return in_array($ini, $pilihan, true) ? $ini : (int) ($pilihan[0] ?? $ini);
    }

    /**
     * PK Eselon II OPD pada tahun itu: jenis jpt (camat untuk kecamatan).
     * Filter opd_id di query INILAH pemeriksa kepemilikannya.
     *
     * @return array<int, array<string, mixed>>
     */
    private function pkKandidat(int $opdId, int $tahun): array
    {
        return $this->db->table('pk p')
            ->select('p.id, p.jenis, p.tahun, p.tanggal, p.pihak_1, pg.nama_pegawai AS nama_pihak_1')
            ->join('pegawai pg', 'pg.id = p.pihak_1', 'left')
            ->where('p.opd_id', $opdId)
            ->where('p.tahun', $tahun)
            ->whereIn('p.jenis', ['jpt', 'camat'])
            ->orderBy('p.tanggal', 'DESC')->orderBy('p.id', 'DESC')
            ->get()->getResultArray();
    }

    /**
     * PK bawaan bila ada beberapa (mis. Setda): yang ditandatangani kepala OPD
     * tercatat (opd.id_kepala_opd); selain itu yang tanggalnya terbaru.
     *
     * @param array<int, array<string, mixed>> $kandidat
     */
    private function pkBawaan(array $kandidat, int $opdId): array
    {
        $kepala = (int) ($this->lingkup()['opd']['id_kepala_opd'] ?? 0);
        if ($kepala > 0) {
            foreach ($kandidat as $k) {
                if ((int) $k['pihak_1'] === $kepala) {
                    return $k;
                }
            }
        }

        return $kandidat[0];
    }

    /**
     * Misi RPJMD periode aktif, terurut: [rpjmd_misi.id => ['nomor' => n, 'misi' => teks]].
     *
     * @return array<int, array{nomor:int, misi:string}>
     */
    private function misiPeriode(): array
    {
        $p = $this->rekap->periodeAktif();
        $b = $this->db->table('rpjmd_misi')->select('id, misi')
            ->where('tahun_mulai', $p['awal'])->where('tahun_akhir', $p['akhir']);
        if ($this->db->fieldExists('dihentikan_pada', 'rpjmd_misi')) {
            $b->where('dihentikan_pada', null);
        }
        $out = [];
        $n   = 1;
        foreach ($b->orderBy('id', 'ASC')->get()->getResultArray() as $m) {
            $out[(int) $m['id']] = ['nomor' => $n++, 'misi' => ikp_rapikan_teks($m['misi'])];
        }

        return $out;
    }

    /** Visi RPJMD (teks satu baris) untuk banner Lampiran II. */
    private function visiPeriode(): string
    {
        if (! $this->db->tableExists('rpjmd_visi')) {
            return '';
        }
        $p   = $this->rekap->periodeAktif();
        $row = $this->db->table('rpjmd_misi m')->select('v.visi')
            ->join('rpjmd_visi v', 'v.id = m.rpjmd_visi_id')
            ->where('m.tahun_mulai', $p['awal'])->where('m.tahun_akhir', $p['akhir'])
            ->orderBy('m.id', 'ASC')->limit(1)->get()->getRowArray();
        if (! $row) {
            $row = $this->db->table('rpjmd_visi')->select('visi')->orderBy('id', 'DESC')->limit(1)->get()->getRowArray();
        }

        return ikp_rapikan_teks((string) ($row['visi'] ?? ''));
    }

    /** URL daftar inovasi (membawa opd_id bila super admin). */
    private function urlDaftar(int $tahun, array $lingkup, ?int $opdId = null): string
    {
        $qs = ['tahun' => $tahun];
        if ($lingkup['boleh_pilih']) {
            $opd = $opdId ?? $lingkup['opd_id'];
            if ($opd) {
                $qs['opd_id'] = $opd;
            }
        }

        return base_url('adminopd/ikp/inovasi') . '?' . http_build_query($qs);
    }
}
