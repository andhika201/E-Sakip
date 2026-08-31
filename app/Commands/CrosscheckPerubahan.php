<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use ReflectionMethod;

/**
 * CROSSCHECK seluruh perubahan LAKIP + Revisi IKU.
 *
 * Menguji jalur nyata (model & service, bukan tiruan) pada SALINAN basis data,
 * lalu memeriksa keadaan tabelnya sesudah tiap langkah. Yang dicari bukan
 * hanya "tidak melempar galat", melainkan:
 *
 *   * data yang DIKIRIM tetapi tidak sampai ke tabel (penyaring allowedFields,
 *     nama medan form yang tidak cocok, kolom yang belum ada);
 *   * jalur tulis yang lolos tanpa penjaga;
 *   * keadaan yang tertinggal tidak konsisten sesudah operasi gagal.
 *
 * Wajib --db <salinan>: perintah ini MENULIS.
 */
class CrosscheckPerubahan extends BaseCommand
{
    protected $group       = 'Pemeriksaan';
    protected $name        = 'cek:menyeluruh';
    protected $description = 'Crosscheck seluruh perubahan LAKIP & Revisi IKU pada salinan basis data.';
    protected $usage       = 'cek:menyeluruh --db <salinan>';
    protected $options     = ['--db' => 'basis data salinan (wajib, beda dari yang dipakai aplikasi)'];

    private $db;
    private int $lulus = 0;
    private int $gagal = 0;
    private array $temuan = [];

    private function cek(string $nama, bool $ok, string $ket = ''): void
    {
        CLI::write('  ' . ($ok ? CLI::color('LULUS', 'green') : CLI::color('GAGAL', 'red'))
            . '  ' . $nama . ($ket !== '' ? CLI::color('  [' . $ket . ']', 'dark_gray') : ''));

        if ($ok) {
            $this->lulus++;
        } else {
            $this->gagal++;
            $this->temuan[] = $nama . ($ket !== '' ? ' — ' . $ket : '');
        }
    }

    private function galat(callable $fn): string
    {
        try {
            $fn();

            return '';
        } catch (\Throwable $e) {
            return $e->getMessage();
        }
    }

    /** Isi berkas sebuah method, untuk memeriksa penjaga yang wajib ada. */
    private function badan(string $kelas, string $method): string
    {
        $r = new ReflectionMethod($kelas, $method);

        return implode('', array_slice(
            file($r->getFileName()),
            $r->getStartLine() - 1,
            $r->getEndLine() - $r->getStartLine() + 1
        ));
    }

    public function run(array $params)
    {
        $namaDb = trim((string) ($params['db'] ?? CLI::getOption('db') ?: ''));
        $cfg    = config('Database')->default;

        if ($namaDb === '' || $namaDb === '1' || $namaDb === $cfg['database']) {
            CLI::error('Wajib --db <salinan> yang berbeda dari basis data aplikasi.');

            return EXIT_ERROR;
        }

        $cfg['database'] = $namaDb;
        $this->db        = db_connect($cfg, false);

        CLI::write('Basis data: ' . CLI::color($this->db->getDatabase(), 'yellow'));

        $this->bersihkanJejak();
        $this->bagianSkema();
        $this->bagianLakipSumber();
        $this->bagianKunciPengesahan();
        $this->bagianTahunBerlaku();
        $this->bagianSasaranBaru();
        $this->bagianHapusVersi();
        $this->bagianPintuTertutup();

        CLI::newLine();
        CLI::write(str_repeat('=', 62));
        CLI::write(sprintf('TOTAL  LULUS: %d   GAGAL: %d', $this->lulus, $this->gagal),
            $this->gagal === 0 ? 'green' : 'red');

        if ($this->temuan !== []) {
            CLI::newLine();
            CLI::write('TEMUAN:', 'red');

            foreach ($this->temuan as $t) {
                CLI::write('  - ' . $t);
            }
        }

        return $this->gagal === 0 ? EXIT_SUCCESS : EXIT_ERROR;
    }

    /**
     * Buang sisa jalannya sendiri, supaya bisa diulang pada salinan yang sama.
     *
     * Tanpa ini, jalan kedua gagal bukan karena ada yang rusak melainkan
     * karena fixture jalan pertama masih memakai tahun berlakunya — kegagalan
     * palsu yang justru menutupi kegagalan sungguhan.
     *
     * Yang dibuang hanya baris bertanda "CEK " yang dilahirkan suite ini.
     * Arsip isinya ikut terbawa lewat foreign key ON DELETE CASCADE.
     */
    private function bersihkanJejak(): void
    {
        $revisi = $this->db->table('iku_revisi')->select('id')
            ->like('nama', 'CEK ', 'after')->get()->getResultArray();

        foreach ($revisi as $r) {
            $this->db->table('dokumen_izin_sunting')->where('version_id', (int) $r['id'])->delete();
            $this->db->table('iku_revisi')->where('id', (int) $r['id'])->delete();
        }

        // Sasaran live yang lahir dari uji sebelumnya, beserta indikatornya
        // (CASCADE) — kalau tertinggal, ia ikut terhitung pada uji berikutnya.
        $this->db->table('iku_sasaran')->like('sasaran', 'CEK sasaran mandiri', 'after')->delete();

        if ($revisi !== []) {
            CLI::write('  (membuang ' . count($revisi) . ' sisa fixture jalan sebelumnya)', 'dark_gray');
        }
    }

    /* ============================================================ */

    private function bagianSkema(): void
    {
        CLI::newLine();
        CLI::write('== 1. SKEMA: kolom baru benar-benar terpasang ==', 'cyan');

        $this->cek('iku_revisi_sasaran.renstra_tujuan_id ada',
            $this->db->fieldExists('renstra_tujuan_id', 'iku_revisi_sasaran'));
        $this->cek('dokumen_izin_sunting.jenis ada',
            $this->db->fieldExists('jenis', 'dokumen_izin_sunting'));
        $this->cek('lakip_pengesahan ada (penjaga kunci)',
            $this->db->tableExists('lakip_pengesahan'));

        // Kolom lingkup LAKIP wajib lolos allowedFields — inilah penyaring yang
        // dulu membuang 134 baris realisasi tanpa gejala apa pun.
        $lm     = new \App\Models\LakipModel();
        $medan  = (new \ReflectionClass($lm))->getProperty('allowedFields');
        $medan->setAccessible(true);
        $izin   = $medan->getValue($lm);
        $wajib  = ['tahun', 'opd_id', 'mode', 'source_type', 'source_version_id', 'source_entity_id'];
        $hilang = array_diff($wajib, $izin);

        $this->cek('LakipModel::allowedFields memuat enam kolom lingkup',
            $hilang === [], $hilang === [] ? '' : 'hilang: ' . implode(',', $hilang));
    }

    /* ============================================================ */

    private function bagianLakipSumber(): void
    {
        CLI::newLine();
        CLI::write('== 2. LAKIP: sumber & tahun ==', 'cyan');

        $kelas = \App\Controllers\AdminKab\LakipController::class;

        $r = new ReflectionMethod($kelas, 'tahunLaporanBawaan');
        $r->setAccessible(true);
        $kab = $r->invoke(new $kelas());

        $this->cek('AdminKab: tahun laporan bawaan = tahun lalu',
            $kab === (int) date('Y') - 1, (string) $kab);

        $opd = $this->badan(\App\Controllers\AdminOpd\LakipOpdController::class, 'index');
        $this->cek('AdminOpd: tahun bawaan juga tahun lalu (dua layar sepakat)',
            str_contains($opd, "date('Y') - 1"));

        // index/cetak/cetakExcel WAJIB lewat pemilih sumber, bukan dipatok.
        foreach (['index', 'cetak', 'cetakExcel'] as $m) {
            $isi = $this->badan($kelas, $m);
            $this->cek($m . '() memakai barisLakipSumber()', str_contains($isi, 'barisLakipSumber'));
            $this->cek($m . '() tidak lagi memanggil getIndexRenstraTargets langsung',
                ! str_contains($isi, 'getIndexRenstraTargets'));
        }

        // Peta realisasi harus berkunci nilai yang SAMA dengan target_id baris.
        $svc   = new \App\Services\Version\LakipSourceService($this->db);
        $lakip = new \App\Models\LakipModel($this->db);

        $opdId = (int) $this->db->table('iku_revisi')->select('opd_key')
            ->where('opd_key >', 0)->orderBy('opd_key')->get()->getRowArray()['opd_key'];

        $versi = $svc->pilihanVersiIku('opd', $opdId, 2025);
        $this->cek('versi IKU OPD tersedia untuk 2025', $versi !== []);

        if ($versi !== []) {
            $rows = $lakip->getIndexIkuTargets((int) $versi[0]['id'], 2025, $opdId);
            $peta = $lakip->getLakipMapIku(2025, null, $opdId);

            $samaKunci = true;

            foreach ($rows as $r2) {
                if ($r2['indikator_id'] !== null
                    && (int) $r2['target_id'] !== (int) $r2['indikator_id']) {
                    $samaKunci = false;
                }
            }

            $this->cek('sumber IKU: target_id == indikator_id (kunci peta cocok)', $samaKunci);
            $this->cek('peta realisasi IKU terisi', $peta !== [], count($peta) . ' baris');
        }

        // Kabupaten harus punya versi IKU sesudah baseline massal diperbaiki.
        $this->cek('IKU Kabupaten punya versi untuk 2025',
            $svc->pilihanVersiIku('kabupaten', null, 2025) !== []);

        // Rekap lintas OPD TIDAK boleh menarik IKU Kabupaten.
        $rk = new ReflectionMethod($kelas, 'barisLakipSumber');
        $rk->setAccessible(true);
        $isiRk = $this->badan($kelas, 'barisLakipSumber');
        $this->cek('penjaga rekap lintas OPD ada (empty($opdId))',
            str_contains($isiRk, "\$mode === 'opd' && empty(\$opdId)"));
    }

    /* ============================================================ */

    private function bagianKunciPengesahan(): void
    {
        CLI::newLine();
        CLI::write('== 3. KUNCI PENGESAHAN LAKIP ditegakkan di server ==', 'cyan');

        foreach ([
            \App\Controllers\AdminKab\LakipController::class    => ['save', 'update', 'status', 'delete'],
            \App\Controllers\AdminOpd\LakipOpdController::class => ['save', 'update', 'status', 'delete'],
        ] as $kelas => $metode) {
            $pendek = substr(strrchr($kelas, '\\'), 1);

            foreach ($metode as $m) {
                $this->cek($pendek . '::' . $m . '() memanggil tolakBilaDisahkan()',
                    str_contains($this->badan($kelas, $m), 'tolakBilaDisahkan'));
            }
        }

        // Penjaga benar-benar mengunci, bukan sekadar ada.
        $pm = new \App\Models\LakipPengesahanModel($this->db);

        if (! $pm->siap()) {
            $this->cek('model pengesahan siap', false, 'tabel belum ada');

            return;
        }

        $opd = (int) $this->db->table('lakip')->select('opd_id')
            ->where('mode', 'opd')->where('opd_id >', 0)->get()->getRowArray()['opd_id'];

        $this->db->table('lakip_pengesahan')->where('tahun', 2025)->where('mode', 'opd')
            ->where('opd_id', $opd)->delete();

        $this->cek('sebelum disahkan: tidak terkunci', ! $pm->terkunci(2025, 'opd', $opd));

        $pm->sahkan(2025, 'opd', $opd, null, []);
        $this->cek('sesudah disahkan: terkunci', $pm->terkunci(2025, 'opd', $opd));

        $pm->ajukanPembukaan(2025, 'opd', $opd, 'uji crosscheck', null);
        $this->cek('masih terkunci selama permintaan menggantung', $pm->terkunci(2025, 'opd', $opd));

        $keadaan  = $pm->keadaan(2025, 'opd', $opd);
        $menunggu = $pm->permintaanMenunggu((int) $keadaan['id']);
        $pm->setujui((int) $menunggu['id'], null, 'disetujui uji');

        $this->cek('sesudah disetujui: terbuka', ! $pm->terkunci(2025, 'opd', $opd));
    }

    /* ============================================================ */

    private function bagianTahunBerlaku(): void
    {
        CLI::newLine();
        CLI::write('== 4. TAHUN BERLAKU: bebas, bentrok ditolak, garis waktu rapat ==', 'cyan');

        $rev = new \App\Models\Opd\IkuRevisiModel($this->db);
        $TM  = 2025;
        $TA  = 2029;

        $opd = (int) $this->db->table('iku_revisi')->select('opd_key')
            ->where('opd_key >', 0)->where('nomor', 0)->orderBy('opd_key')
            ->get()->getRowArray()['opd_key'];

        $awal = (int) $this->db->table('iku_revisi')->where('opd_key', $opd)->where('nomor', 0)
            ->get()->getRowArray()['id'];

        // Seluruh tahun periode ditawarkan; yang terpakai disebut pemakainya.
        $terpakai = $rev->tahunBerlakuTerpakai($opd, $TM, $TA);
        $this->cek('tahun pertama periode dilaporkan terpakai, bukan disembunyikan',
            isset($terpakai[$TM]));

        $bebasSendiri = $rev->tahunBerlakuBebas($opd, $TM, $TA, $awal);
        $this->cek('bagi pemakainya sendiri, tahun itu jadi pilihan',
            in_array($TM, $bebasSendiri, true));

        // Tahun bentrok ditolak SAAT AJUKAN, bukan menunggu pengesahan.
        $d1 = $rev->buatDraft(['opd_id' => $opd, 'tahun_mulai' => $TM, 'tahun_akhir' => $TA,
            'nama' => 'CEK d1', 'berlaku_mulai_tahun' => 2026]);
        $d2 = $rev->buatDraft(['opd_id' => $opd, 'tahun_mulai' => $TM, 'tahun_akhir' => $TA,
            'nama' => 'CEK d2', 'berlaku_mulai_tahun' => 2027]);

        $rev->ajukan($d1, null);
        $this->db->table('iku_revisi')->where('id', $d2)->update(['berlaku_mulai_tahun' => 2026]);

        $g = $this->galat(fn () => $rev->ajukan($d2, null));
        $this->cek('ajukan() menolak tahun yang sudah dipakai',
            str_contains($g, 'sudah dipakai revisi lain'));

        $st = $this->db->table('iku_revisi')->select('status')->where('id', $d2)
            ->get()->getRowArray()['status'];
        $this->cek('draft bentrok TIDAK berubah status (tidak masuk antrean)',
            $st === 'draft', $st);

        // Kondisi Awal boleh digeser, dengan peringatan.
        $this->db->table('iku_revisi')->where('id', $d2)->update(['berlaku_mulai_tahun' => 2027]);
        $h = $rev->ubahTahunBerlaku($awal, 2028);
        $this->cek('Kondisi Awal boleh digeser', (int) $h['ke'] === 2028);
        $this->cek('peringatan awal periode tanpa payung dikembalikan', ! empty($h['peringatan']));
        $rev->ubahTahunBerlaku($awal, $TM);

        // Garis waktu tetap rapat sesudah pengesahan berurutan + lompatan.
        $rev->sahkan($d1, null);
        $rev->ajukan($d2, null);
        $rev->sahkan($d2, null);

        $rev->ubahTahunBerlaku($d1, 2029); // melompati d2
        $kosong = [];

        foreach (range($TM, $TA) as $th) {
            $r = $rev->resolveEfektif($opd, $th);

            if ($r['revisi'] === null) {
                $kosong[] = $th;
            }

            if ($r['konflik'] !== []) {
                $kosong[] = $th . '(konflik)';
            }
        }

        $this->cek('sesudah lompatan, tiap tahun dipayungi tepat satu revisi',
            $kosong === [], implode(',', $kosong));

        // =============================================================
        // PENGESAHAN DI LUAR URUTAN TAHUN
        //
        // sahkan() dulu hanya melihat baris ber-status 'berlaku' dan hanya
        // menurunkan yang mulai berlakunya LEBIH AWAL — benar selama revisi
        // disahkan berurutan, runtuh sejak tahun berlaku boleh dipilih bebas.
        // Mengesahkan revisi yang mulai LEBIH AWAL dari yang sudah berlaku
        // meninggalkan DUA baris 'berlaku', dan jendela warisan yang tidak
        // dihitung ulang membuat tahunnya tumpang tindih.
        //
        // Terlihat di layar sebagai "Konflik masa berlaku revisi".
        // =============================================================
        $bebasLuar = $rev->tahunBerlakuBebas($opd, $TM, $TA);

        if ($bebasLuar !== []) {
            $lebihAwal = min($bebasLuar);

            $x = $rev->buatDraft(['opd_id' => $opd, 'tahun_mulai' => $TM, 'tahun_akhir' => $TA,
                'nama' => 'CEK luar urutan', 'berlaku_mulai_tahun' => $lebihAwal]);
            $rev->ajukan($x, null);
            $rev->sahkan($x, null);

            $this->cek('sesudah sahkan di luar urutan: tepat SATU revisi berlaku',
                $this->db->table('iku_revisi')->where('opd_key', $opd)
                    ->where('tahun_mulai', $TM)->where('tahun_akhir', $TA)
                    ->where('status', 'berlaku')->countAllResults() === 1);

            $bentrok = [];

            foreach (range($TM, $TA) as $th) {
                if ($rev->resolveEfektif($opd, $th)['konflik'] !== []) {
                    $bentrok[] = $th;
                }
            }

            $this->cek('  tidak ada tahun yang tumpang tindih',
                $bentrok === [], implode(',', $bentrok));

            // Yang terkini harus yang mulai berlakunya PALING AKHIR.
            $terkini = $this->db->table('iku_revisi')->where('opd_key', $opd)
                ->where('tahun_mulai', $TM)->where('tahun_akhir', $TA)
                ->where('status', 'berlaku')->get()->getRowArray();

            $paling = $this->db->table('iku_revisi')->where('opd_key', $opd)
                ->where('tahun_mulai', $TM)->where('tahun_akhir', $TA)
                ->whereIn('status', ['berlaku', 'superseded'])
                ->orderBy('berlaku_mulai_tahun', 'DESC')->get()->getRowArray();

            $this->cek('  yang berlaku adalah yang mulai paling akhir',
                (int) $terkini['id'] === (int) $paling['id']);
        }

        $this->ujiHapus = ['opd' => $opd, 'd1' => $d1, 'd2' => $d2, 'awal' => $awal];
    }

    private array $ujiHapus = [];

    /* ============================================================ */

    private function bagianSasaranBaru(): void
    {
        CLI::newLine();
        CLI::write('== 5. SASARAN MANDIRI di dalam revisi: sampai ke tabel? ==', 'cyan');

        $rev = new \App\Models\Opd\IkuRevisiModel($this->db);
        $iku = new \App\Models\Opd\IkuModel($this->db);
        $opd = (int) ($this->ujiHapus['opd'] ?? 0);

        $draft = $rev->buatDraft(['opd_id' => $opd, 'tahun_mulai' => 2025, 'tahun_akhir' => 2029,
            'nama' => 'CEK sasaran baru', 'berlaku_mulai_tahun' => 2028]);

        $tujuan = $iku->tujuanRenstraOpd($opd, 2025, 2029);

        if ($tujuan === []) {
            $this->cek('ada tujuan Renstra untuk sasaran mandiri', false, 'tidak ada');

            return;
        }

        $tid = (int) $tujuan[0]['id'];

        $rev->simpanSuntinganDraft($draft, [], [], false, [
            's_1' => [
                'sasaran'           => 'CEK sasaran mandiri',
                'renstra_tujuan_id' => $tid,
                'indikator'         => [
                    'i_1' => [
                        'indikator'           => 'CEK indikator mandiri',
                        'definisi'            => 'definisi uji',
                        'rumusan_perhitungan' => 'rumusan uji',
                        'sumber_data'         => 'sumber uji',
                        'penanggung_jawab'    => 'pj uji',
                        'jenis_indikator'     => 'positif',
                        'baseline'            => '7',
                        'satuan'              => 'Persen',
                        'target'              => [2025 => '10', 2026 => '20', 2027 => '30',
                                                  2028 => '40', 2029 => '50'],
                    ],
                ],
            ],
        ]);

        $arsip = $this->db->table('iku_revisi_sasaran')
            ->where('revisi_id', $draft)->like('sasaran', 'CEK sasaran mandiri', 'after')
            ->get()->getRowArray();

        $this->cek('sasaran baru tersimpan di arsip', $arsip !== null);
        $this->cek('tujuan Renstra ikut tersimpan (kolom baru terpakai)',
            (int) ($arsip['renstra_tujuan_id'] ?? 0) === $tid);
        $this->cek('ditandai lahir di IKU', ($arsip['source_type'] ?? '') === 'iku');

        $ind = $this->db->table('iku_revisi_indikator')
            ->where('revisi_sasaran_id', (int) $arsip['id'])->get()->getRowArray();

        $this->cek('indikatornya tersimpan', $ind !== null);

        // SELURUH medan indikator harus sampai — inilah kelas bug "data tidak
        // terkirim": satu medan hilang dari form/model tidak bergejala.
        foreach ([
            'definisi'            => 'definisi uji',
            'rumusan_perhitungan' => 'rumusan uji',
            'sumber_data'         => 'sumber uji',
            'penanggung_jawab'    => 'pj uji',
            'jenis_indikator'     => 'positif',
            'baseline'            => '7',
        ] as $kolom => $nilai) {
            $this->cek('  medan "' . $kolom . '" sampai ke tabel',
                (string) ($ind[$kolom] ?? '') === $nilai, (string) ($ind[$kolom] ?? 'NULL'));
        }

        $this->cek('  satuan diterjemahkan ke nama', ($ind['satuan_nama'] ?? '') !== '');

        $tgt = $this->db->table('iku_revisi_target')
            ->where('revisi_indikator_id', (int) $ind['id'])->get()->getResultArray();

        $this->cek('  kelima target tahunan tersimpan', count($tgt) === 5, count($tgt) . ' baris');

        $petaTgt = [];

        foreach ($tgt as $t) {
            $petaTgt[(int) $t['tahun']] = (string) $t['target'];
        }

        $this->cek('  target per tahun benar nilainya',
            ($petaTgt[2025] ?? '') === '10' && ($petaTgt[2029] ?? '') === '50');

        // Pengesahan: arsip -> live.
        $rev->ajukan($draft, null);
        $rev->sahkan($draft, null);

        $live = $this->db->table('iku_sasaran')->where('opd_id', $opd)
            ->like('sasaran', 'CEK sasaran mandiri', 'after')->get()->getRowArray();

        $this->cek('sasaran live lahir dari arsip', $live !== null);
        $this->cek('tujuan Renstra turun ke live',
            (int) ($live['renstra_tujuan_id'] ?? 0) === $tid,
            'live=' . ($live['renstra_tujuan_id'] ?? 'NULL'));

        $indLive = $this->db->table('iku_indikator')
            ->where('iku_sasaran_id', (int) $live['id'])->get()->getRowArray();

        $this->cek('indikator live lahir', $indLive !== null);

        $tgtLive = $this->db->table('iku_target')
            ->where('iku_indikator_id', (int) $indLive['id'])->countAllResults();

        $this->cek('target live ikut turun (5 tahun)', $tgtLive === 5, $tgtLive . ' baris');

        $this->ujiHapus['draft_sahkan'] = $draft;
        $this->ujiHapus['sasaran_live'] = (int) $live['id'];
    }

    /* ============================================================ */

    private function bagianHapusVersi(): void
    {
        CLI::newLine();
        CLI::write('== 6. HAPUS VERSI: izin, penghalang, penerus ==', 'cyan');

        $rev  = new \App\Models\Opd\IkuRevisiModel($this->db);
        $izin = new \App\Services\Version\IzinSuntingService($this->db);
        $opd  = (int) ($this->ujiHapus['opd'] ?? 0);

        $this->cek('layanan izin mengenal kolom jenis', $izin->punyaKolomJenis());

        // Versi yang dirujuk LAKIP TIDAK boleh dihapus.
        $dirujuk = $this->db->table('lakip')->select('source_version_id')
            ->where('source_type', 'iku')->where('source_version_id IS NOT NULL', null, false)
            ->get()->getRowArray();

        if ($dirujuk !== null) {
            $vid  = (int) $dirujuk['source_version_id'];
            $peng = $rev->penghalangHapus($vid);
            $this->cek('versi yang dirujuk LAKIP terdeteksi sebagai penghalang',
                $peng !== [], implode('; ', array_keys($peng)));

            $g = $this->galat(fn () => $rev->hapusRevisi($vid));
            $this->cek('penghapusannya DITOLAK', str_contains($g, 'masih dirujuk data lain'));

            $masihAda = $this->db->table('iku_revisi')->where('id', $vid)->countAllResults() === 1;
            $this->cek('versinya masih utuh sesudah penolakan', $masihAda);
        }

        // Versi bebas rujukan: boleh dihapus, arsipnya ikut terhapus.
        $target = (int) ($this->ujiHapus['d2'] ?? 0);

        if ($target > 0 && $rev->penghalangHapus($target) === []) {
            $nSas = $this->db->table('iku_revisi_sasaran')->where('revisi_id', $target)->countAllResults();
            $nInd = $this->db->table('iku_revisi_indikator')->where('revisi_id', $target)->countAllResults();
            $this->cek('sebelum hapus: arsipnya berisi', $nSas > 0 && $nInd > 0,
                $nSas . ' sasaran / ' . $nInd . ' indikator');

            $hasil = $rev->hapusRevisi($target);

            $this->cek('versinya terhapus',
                $this->db->table('iku_revisi')->where('id', $target)->countAllResults() === 0);
            $this->cek('arsip sasaran ikut terhapus (CASCADE)',
                $this->db->table('iku_revisi_sasaran')->where('revisi_id', $target)->countAllResults() === 0);
            $this->cek('arsip indikator ikut terhapus (CASCADE)',
                $this->db->table('iku_revisi_indikator')->where('revisi_id', $target)->countAllResults() === 0);

            // Baris LIVE tidak boleh ikut hilang.
            $sasLive = (int) ($this->ujiHapus['sasaran_live'] ?? 0);

            if ($sasLive > 0) {
                $this->cek('baris IKU berjalan TIDAK ikut terhapus',
                    $this->db->table('iku_sasaran')->where('id', $sasLive)->countAllResults() === 1);
            }

            // Garis waktu tetap rapat sesudah penghapusan.
            $kosong = [];

            foreach (range(2025, 2029) as $th) {
                $r = $rev->resolveEfektif($opd, $th);

                if ($r['revisi'] === null || $r['konflik'] !== []) {
                    $kosong[] = $th;
                }
            }

            $this->cek('garis waktu tetap rapat sesudah penghapusan',
                $kosong === [], implode(',', $kosong));

            $this->cek('tepat satu revisi berstatus berlaku pada lingkup ini',
                $this->db->table('iku_revisi')->where('opd_key', $opd)
                    ->where('tahun_mulai', 2025)->where('tahun_akhir', 2029)
                    ->where('status', 'berlaku')->countAllResults() === 1);
        }

        // =============================================================
        // MENGHAPUS VERSI YANG SEDANG BERLAKU
        //
        // Jalur ini sempat lolos dari uji: fixture sebelumnya kebetulan selalu
        // menghapus revisi 'superseded', sehingga cabang yang menerapkan
        // penerus ke tabel live TIDAK PERNAH dijalankan — dan cabang itu
        // membuka transaksi bersarang yang ditolak TransaksiAman.
        //
        // Sekarang dipaksa: bangun revisi, sahkan sampai BERLAKU, lalu hapus.
        // =============================================================
        // Sasarannya adalah revisi yang MEMANG sedang berlaku — bukan yang
        // baru dibuat lalu diandaikan berlaku. Sejak garis waktu dijahit ulang
        // sesudah pengesahan, revisi baru belum tentu jadi yang terkini: yang
        // terkini adalah yang mulai berlakunya paling akhir, siapa pun itu.
        $berlakuKini = $this->db->table('iku_revisi')->where('opd_key', $opd)
            ->where('tahun_mulai', 2025)->where('tahun_akhir', 2029)
            ->where('status', 'berlaku')->get()->getRowArray();

        if ($berlakuKini !== null && $rev->penghalangHapus((int) $berlakuKini['id']) === []) {
            $b = (int) $berlakuKini['id'];

            $this->cek('revisi uji benar-benar BERLAKU sebelum dihapus',
                $berlakuKini['status'] === 'berlaku', $berlakuKini['status']);

            $g = $this->galat(fn () => $rev->hapusRevisi($b));
            $this->cek('menghapus revisi BERLAKU tidak melempar galat', $g === '', mb_substr($g, 0, 60));
            $this->cek('  versinya benar-benar terhapus',
                $this->db->table('iku_revisi')->where('id', $b)->countAllResults() === 0);
            $this->cek('  penerusnya diangkat jadi berlaku',
                $this->db->table('iku_revisi')->where('opd_key', $opd)
                    ->where('tahun_mulai', 2025)->where('tahun_akhir', 2029)
                    ->where('status', 'berlaku')->countAllResults() === 1);

            // IKU berjalan harus tetap punya isi — penerapan penerus ke live
            // berjalan, bukan gagal diam-diam.
            $this->cek('  IKU berjalan tetap berisi sesudah penghapusan',
                $this->db->table('iku_sasaran')->where('opd_id', $opd)
                    ->where('tahun_mulai', 2025)->countAllResults() > 0);
        }

        // Revisi 'menunggu' ditolak.
        $m = $rev->buatDraft(['opd_id' => $opd, 'tahun_mulai' => 2025, 'tahun_akhir' => 2029,
            'nama' => 'CEK menunggu', 'berlaku_mulai_tahun' => 2027]);
        $rev->ajukan($m, null);

        $g = $this->galat(fn () => $rev->hapusRevisi($m));
        $this->cek('revisi yang menunggu keputusan ditolak dihapus',
            str_contains($g, 'menunggu keputusan'));

        // Penjaga di controller & alur keputusan.
        $isi = $this->badan(\App\Controllers\AdminKab\VerifikasiController::class, 'izinSetujui');
        $this->cek('izinSetujui() mencabang ke hapusRevisi() untuk jenis hapus',
            str_contains($isi, 'JENIS_HAPUS') && str_contains($isi, 'hapusRevisi'));
        $this->cek('urutannya hapus DULU baru tutup permohonan',
            strpos($isi, 'hapusRevisi') < strpos($isi, '$svc->setujui'));

        $isiTrait = $this->badan(\App\Controllers\AdminOpd\IkuController::class, 'revisiMintaHapus');
        $this->cek('permohonan hapus memakai JENIS_HAPUS',
            str_contains($isiTrait, 'JENIS_HAPUS'));

        // berjalan() tidak boleh membaca permohonan hapus sebagai izin sunting.
        $isiSvc = $this->badan(\App\Services\Version\IzinSuntingService::class, 'bolehSunting');
        $this->cek('bolehSunting() menyaring jenis SUNTING',
            str_contains($isiSvc, 'JENIS_SUNTING'));
    }

    /* ============================================================ */

    private function bagianPintuTertutup(): void
    {
        CLI::newLine();
        CLI::write('== 7. PINTU LAMA sasaran mandiri tertutup ==', 'cyan');

        foreach (['tambah', 'save'] as $m) {
            $isi = $this->badan(\App\Controllers\AdminOpd\IkuController::class, $m);
            $this->cek('IkuController::' . $m . '() menolak & mengarahkan ke Versi IKU',
                str_contains($isi, 'tolakPintuMandiri'));
            // Dicari PANGGILANNYA (`->createComplete(`), bukan sekadar
            // katanya: kedua method itu menyebut nama lama di komentar
            // penjelas, dan mencocokkan kata telanjang akan menandai
            // penjelasan sebagai pelanggaran.
            $this->cek('  ' . $m . '() tidak lagi memanggil createComplete()',
                ! str_contains($isi, '->createComplete('));
        }

        $view = file_get_contents(APPPATH . 'Views/adminOpd/iku/iku.php');
        $this->cek('tombol "Sasaran Mandiri" tidak lagi dirender',
            ! str_contains($view, 'adminopd/iku/tambah'));
        $this->cek('tombol Sync tetap ada', str_contains($view, 'adminopd/iku/sync'));
    }
}
