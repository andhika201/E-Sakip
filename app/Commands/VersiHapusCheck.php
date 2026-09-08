<?php

namespace App\Commands;

use App\Controllers\AdminOpd\RenstraController;
use App\Controllers\RpjmdController;
use App\Models\DokumenVersiModel;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use ReflectionMethod;
use Throwable;

/**
 * Uji penghapusan VERSI DOKUMEN: penghalang, status, dan lingkup.
 *
 *   php spark versi:hapus-check [--db <salinan>]
 *
 * =====================================================================
 * APA YANG DIJAGA DI SINI
 *
 * Menghapus sebuah versi dokumen berbeda sifat dari menghapus baris biasa:
 * `dokumen_versi.id` dirujuk dari belasan tempat, dan sebagian besar rujukan
 * itu TANPA foreign key. Basis data tidak akan menahan apa pun, dan tidak ada
 * satu pun layar yang melaporkannya sebagai galat — yang terjadi hanyalah
 * isi yang masih tampil berhenti menemukan versinya.
 *
 * Tiga hal yang paling mudah salah, dan karena itu diuji di sini:
 *
 *   1. ISI LIVE. `rpjmd_misi/tujuan/sasaran/indikator_*` menyandang
 *      `version_id` tanpa foreign key. Menghapus versinya membuat isi RPJMD
 *      yang masih berjalan menunjuk ketiadaan.
 *
 *   2. `source_version_id` ITU POLIMORFIK. Satu kolom dipakai bersama sumber
 *      'rpjmd', 'renstra', dan 'iku', masing-masing dengan penomorannya
 *      sendiri. Menghitung tanpa `source_type` membuat versi RPJMD #1 tampak
 *      dirujuk oleh apa pun yang bersumber dari revisi IKU #1 — pada basis
 *      data kerja ada 4 baris LAKIP semacam itu, dan semuanya milik IKU.
 *      Penolakan karena alasan yang keliru sama merusaknya dengan izin yang
 *      keliru: orang akan mengira datanya terkunci padahal tidak.
 *
 *   3. LINGKUP. DokumenVersiTrait dipakai bersama RenstraController, dan
 *      `RenstraController::versiOpdId()` membaca session('opd_id') — bernilai
 *      NULL begitu sesinya kosong. Kalau kewenangan hapus hanya diuji dengan
 *      "opdId === null", sesi tanpa opd_id lolos sebagai lingkup kabupaten.
 *
 * =====================================================================
 * FIXTURE
 *
 * Seluruh versi uji berlabel berawalan UJI-HAPUS-VERSI pada periode 2090-2094
 * — periode yang tidak dipakai data mana pun — dan dibuang lagi di akhir,
 * sukses maupun gagal.
 */
class VersiHapusCheck extends BaseCommand
{
    protected $group       = 'SAKIP';
    protected $name        = 'versi:hapus-check';
    protected $description = 'Uji penghalang, status, dan lingkup penghapusan versi dokumen.';
    protected $usage       = 'versi:hapus-check [--db <nama>] [--lihat <id versi>]';
    protected $options     = [
        '--db'    => 'kerjakan pada basis data lain (mis. salinan uji)',
        '--lihat' => 'HANYA laporkan penghalang sebuah versi nyata; tidak menulis apa pun',
    ];

    private const TANDA  = 'UJI-HAPUS-VERSI';
    private const MULAI  = 2090;
    private const AKHIR  = 2094;

    private int $lulus = 0;
    private int $gagal = 0;

    /** @var list<int> */
    private array $dibuat = [];

    private function cek(string $nama, bool $ok, string $detail = ''): void
    {
        if ($ok) {
            $this->lulus++;
            CLI::write('  ' . CLI::color('LULUS', 'green') . '  ' . $nama);
        } else {
            $this->gagal++;
            CLI::write('  ' . CLI::color('GAGAL', 'red') . '  ' . $nama
                . ($detail !== '' ? ' -> ' . $detail : ''));
        }
    }

    public function run(array $params)
    {
        $namaDb = trim((string) (CLI::getOption('db') ?: ''));

        if ($namaDb !== '' && $namaDb !== '1') {
            $cfg             = config('Database')->default;
            $cfg['database'] = $namaDb;
            $db              = db_connect($cfg, false);
        } else {
            $db = db_connect();
        }

        CLI::write('Basis data: ' . $db->getDatabase(), 'yellow');
        CLI::newLine();

        $model = new DokumenVersiModel($db);

        if (! $model->siap()) {
            CLI::error('Tabel dokumen_versi belum terpasang.');

            return EXIT_ERROR;
        }

        // Mode diagnosa: menjawab "kenapa versi ini tidak bisa dihapus" tanpa
        // menyentuh apa pun. Layar hanya sanggup memuat satu kalimat; di sini
        // penghalangnya terurai satu per satu.
        $lihat = (int) (CLI::getOption('lihat') ?: 0);

        if ($lihat > 0) {
            return $this->laporkan($model, $lihat);
        }

        try {
            $this->ujiStatus($db, $model);
            $this->ujiPenghalangIsiLive($db, $model);
            $this->ujiPolimorfik($db, $model);
            $this->ujiSilsilah($db, $model);
            $this->ujiHapusBersih($db, $model);
            $this->ujiArsipIkut($db, $model);
            $this->ujiLingkup();
        } catch (Throwable $e) {
            $this->gagal++;
            CLI::error('Galat tak terduga: ' . $e->getMessage());
            CLI::write('  ' . $e->getFile() . ':' . $e->getLine());
        } finally {
            $this->bersihkan($db);
        }

        CLI::newLine();
        CLI::write('LULUS ' . $this->lulus . '   GAGAL ' . $this->gagal,
            $this->gagal === 0 ? 'green' : 'red');

        return $this->gagal === 0 ? EXIT_SUCCESS : EXIT_ERROR;
    }

    /** Laporan penghalang sebuah versi nyata — hanya membaca. */
    private function laporkan(DokumenVersiModel $model, int $id): int
    {
        $versi = $model->ambil($id);

        if ($versi === null) {
            CLI::error('Versi #' . $id . ' tidak ada.');

            return EXIT_ERROR;
        }

        CLI::write('Versi #' . $id . '  ' . $versi['label']
            . '  [' . $versi['status'] . ']  modul=' . $versi['modul']);
        CLI::newLine();

        $tolak = $model->alasanTolakHapus($versi);

        CLI::write('Menurut status : ' . ($tolak ?? 'boleh dihapus'),
            $tolak === null ? 'green' : 'red');

        $halang = $model->penghalangHapus($id, (string) $versi['modul']);

        if ($halang === []) {
            CLI::write('Penghalang     : tidak ada', 'green');
        } else {
            CLI::write('Penghalang     : ' . count($halang) . ' jenis', 'red');

            foreach ($halang as $apa => $n) {
                CLI::write(sprintf('  %6d  %s', $n, $apa));
            }
        }

        CLI::newLine();
        CLI::write('Tidak ada yang ditulis.', 'yellow');

        return EXIT_SUCCESS;
    }

    /* ================= fixture ================= */

    private function buatVersi($db, string $status, string $sebutan, int $no): int
    {
        $db->table('dokumen_versi')->insert([
            'modul'          => 'rpjmd',
            'scope'          => 'kabupaten',
            'opd_id'         => null,
            'periode_mulai'  => self::MULAI,
            'periode_akhir'  => self::AKHIR,
            'version_no'     => $no,
            'label'          => self::TANDA . ' ' . $sebutan,
            'effective_from' => self::MULAI . '-01-01',
            'status'         => $status,
            'created_at'     => date('Y-m-d H:i:s'),
        ]);

        $id = (int) $db->insertID();
        $this->dibuat[] = $id;

        return $id;
    }

    private function bersihkan($db): void
    {
        if ($this->dibuat !== []) {
            // Rujukan lunak dibersihkan lebih dulu supaya FK RESTRICT tidak
            // menahan pembersihan fixture-nya sendiri.
            foreach (['rpjmd_misi', 'rpjmd_tujuan', 'rpjmd_sasaran',
                'rpjmd_indikator_tujuan', 'rpjmd_indikator_sasaran'] as $t) {
                if ($db->tableExists($t) && $db->fieldExists('version_id', $t)) {
                    $db->table($t)->whereIn('version_id', $this->dibuat)->delete();
                }
            }

            foreach (['lakip', 'iku_sasaran'] as $t) {
                if ($db->tableExists($t) && $db->fieldExists('source_version_id', $t)) {
                    $db->table($t)->whereIn('source_version_id', $this->dibuat)->delete();
                }
            }

            $db->table('dokumen_versi')
                ->whereIn('source_version_id', $this->dibuat)
                ->update(['source_version_id' => null]);

            $db->table('dokumen_versi')->whereIn('id', $this->dibuat)->delete();
        }

        // Jaring pengaman: apa pun yang lolos tetap terbawa labelnya.
        $db->table('dokumen_versi')->like('label', self::TANDA, 'after')->delete();
    }

    /* ================= skenario ================= */

    /** Status yang menutup pintu: published (§16) dan pending (di meja verifikator). */
    private function ujiStatus($db, DokumenVersiModel $model): void
    {
        CLI::write('== Status yang menolak penghapusan ==', 'yellow');

        $terbit  = $this->buatVersi($db, DokumenVersiModel::STATUS_PUBLISHED, 'terbit', 91);
        $tunggu  = $this->buatVersi($db, DokumenVersiModel::STATUS_PENDING, 'menunggu', 92);
        $draft   = $this->buatVersi($db, DokumenVersiModel::STATUS_DRAFT, 'draft', 93);
        $batal   = $this->buatVersi($db, DokumenVersiModel::STATUS_CANCELLED, 'batal', 94);

        $this->cek('versi published DITOLAK',
            $model->alasanTolakHapus($model->ambil($terbit)) !== null);
        $this->cek('versi pending_approval DITOLAK',
            $model->alasanTolakHapus($model->ambil($tunggu)) !== null);
        $this->cek('versi draft DIIZINKAN menurut status',
            $model->alasanTolakHapus($model->ambil($draft)) === null);
        $this->cek('versi cancelled DIIZINKAN menurut status',
            $model->alasanTolakHapus($model->ambil($batal)) === null);

        // Penolakan status bukan hanya soal tampilan — hapusVersi() sendiri
        // harus menolaknya walau dipanggil langsung.
        $lolos = true;

        try {
            $model->hapusVersi($terbit);
        } catch (Throwable $e) {
            $lolos = false;
        }

        $this->cek('hapusVersi() menolak published walau dipanggil langsung', ! $lolos);
        $this->cek('versi published masih ada sesudah percobaan itu',
            $model->ambil($terbit) !== null);

        CLI::newLine();
    }

    /** Isi RPJMD yang masih berjalan menahan penghapusan versinya. */
    private function ujiPenghalangIsiLive($db, DokumenVersiModel $model): void
    {
        CLI::write('== Isi live menahan penghapusan ==', 'yellow');

        if (! $db->tableExists('rpjmd_misi') || ! $db->fieldExists('version_id', 'rpjmd_misi')) {
            CLI::write('  (dilewati: rpjmd_misi.version_id tidak ada)');
            CLI::newLine();

            return;
        }

        $v = $this->buatVersi($db, DokumenVersiModel::STATUS_DRAFT, 'berisi-live', 95);

        $this->cek('sebelum diisi: tidak ada penghalang',
            $model->penghalangHapus($v, 'rpjmd') === []);

        $db->table('rpjmd_misi')->insert([
            'misi'        => self::TANDA . ' misi',
            'tahun_mulai' => self::MULAI,
            'tahun_akhir' => self::AKHIR,
            'version_id'  => $v,
        ]);

        $halang = $model->penghalangHapus($v, 'rpjmd');

        $this->cek('misi RPJMD berjalan tercatat sebagai penghalang',
            ($halang['misi RPJMD berjalan'] ?? 0) === 1,
            json_encode($halang));

        $ditolak = false;

        try {
            $model->hapusVersi($v);
        } catch (Throwable $e) {
            $ditolak = str_contains($e->getMessage(), 'Masih dirujuk');
        }

        $this->cek('hapusVersi() menolak dengan sebutan "Masih dirujuk"', $ditolak);
        $this->cek('versinya masih ada', $model->ambil($v) !== null);
        $this->cek('isi live-nya tidak ikut terhapus',
            $db->table('rpjmd_misi')->where('version_id', $v)->countAllResults() === 1);

        CLI::newLine();
    }

    /**
     * Rujukan milik SUMBER LAIN tidak boleh menghalangi.
     *
     * Inilah kesalahan yang paling mudah lolos: `source_version_id` sama-sama
     * bernilai 1 untuk RPJMD #1, Renstra #1, dan revisi IKU #1.
     */
    private function ujiPolimorfik($db, DokumenVersiModel $model): void
    {
        CLI::write('== source_version_id polimorfik disaring source_type ==', 'yellow');

        if (! $db->tableExists('lakip') || ! $db->fieldExists('source_type', 'lakip')) {
            CLI::write('  (dilewati: lakip.source_type tidak ada)');
            CLI::newLine();

            return;
        }

        $v = $this->buatVersi($db, DokumenVersiModel::STATUS_DRAFT, 'polimorfik', 96);

        // Baris LAKIP dengan NOMOR versi yang sama tetapi sumber BERBEDA.
        $db->table('lakip')->insert([
            'target_lalu'       => '0',
            'capaian_lalu'      => '0',
            'capaian_tahun_ini' => '0',
            'status'            => 'draft',
            'tahun'             => self::MULAI,
            'source_type'       => 'iku',
            'source_version_id' => $v,
        ]);

        $this->cek('rujukan bersumber "iku" TIDAK menghalangi versi rpjmd',
            $model->penghalangHapus($v, 'rpjmd') === [],
            json_encode($model->penghalangHapus($v, 'rpjmd')));

        // Sekarang yang benar-benar bersumber RPJMD.
        $db->table('lakip')->insert([
            'target_lalu'       => '0',
            'capaian_lalu'      => '0',
            'capaian_tahun_ini' => '0',
            'status'            => 'draft',
            'tahun'             => self::MULAI,
            'source_type'       => 'rpjmd',
            'source_version_id' => $v,
        ]);

        $halang = $model->penghalangHapus($v, 'rpjmd');

        $this->cek('rujukan bersumber "rpjmd" MEMANG menghalangi',
            ($halang['baris LAKIP yang dinilai terhadap versi ini'] ?? 0) === 1,
            json_encode($halang));

        CLI::newLine();
    }

    /** Silsilah antarversi ber-FK SET NULL — hilang tanpa suara bila tak dijaga. */
    private function ujiSilsilah($db, DokumenVersiModel $model): void
    {
        CLI::write('== Silsilah antarversi ==', 'yellow');

        $induk = $this->buatVersi($db, DokumenVersiModel::STATUS_DRAFT, 'induk-silsilah', 97);
        $anak  = $this->buatVersi($db, DokumenVersiModel::STATUS_DRAFT, 'anak-silsilah', 98);

        $db->table('dokumen_versi')->where('id', $anak)
            ->update(['source_version_id' => $induk]);

        $halang = $model->penghalangHapus($induk, 'rpjmd');

        $this->cek('versi yang jadi sumber versi lain tertahan',
            ($halang['versi lain yang bersumber dari versi ini'] ?? 0) === 1,
            json_encode($halang));

        $this->cek('anaknya sendiri tetap bebas dihapus',
            $model->penghalangHapus($anak, 'rpjmd') === []);

        CLI::newLine();
    }

    /** Versi bersih benar-benar terhapus. */
    private function ujiHapusBersih($db, DokumenVersiModel $model): void
    {
        CLI::write('== Versi bersih terhapus ==', 'yellow');

        $v = $this->buatVersi($db, DokumenVersiModel::STATUS_DRAFT, 'bersih', 99);

        $ringkas = $model->hapusVersi($v);

        $this->cek('hapusVersi() mengembalikan nama versinya',
            str_contains((string) ($ringkas['nama'] ?? ''), self::TANDA),
            (string) ($ringkas['nama'] ?? ''));
        $this->cek('barisnya benar-benar hilang', $model->ambil($v) === null);

        CLI::newLine();
    }

    /** Arsip isi ikut terhapus lewat CASCADE, dan jumlahnya dilaporkan. */
    private function ujiArsipIkut($db, DokumenVersiModel $model): void
    {
        CLI::write('== Arsip isi ikut terhapus (CASCADE) ==', 'yellow');

        if (! $db->tableExists('rpjmd_versi_misi')) {
            CLI::write('  (dilewati: rpjmd_versi_misi tidak ada)');
            CLI::newLine();

            return;
        }

        $v = $this->buatVersi($db, DokumenVersiModel::STATUS_DRAFT, 'berarsip', 100);

        $db->table('rpjmd_versi_misi')->insert([
            'version_id'  => $v,
            'misi'        => self::TANDA . ' arsip misi',
            'tahun_mulai' => self::MULAI,
            'tahun_akhir' => self::AKHIR,
            'urutan'      => 1,
        ]);

        $sebelum = $db->table('rpjmd_versi_misi')->where('version_id', $v)->countAllResults();
        $this->cek('arsip misi tersimpan lebih dulu', $sebelum === 1);

        // Arsip TIDAK boleh dianggap penghalang: ia memang ikut terhapus.
        $this->cek('arsip isinya sendiri bukan penghalang',
            $model->penghalangHapus($v, 'rpjmd') === [],
            json_encode($model->penghalangHapus($v, 'rpjmd')));

        $ringkas = $model->hapusVersi($v);

        $this->cek('jumlah arsip yang ikut terhapus dilaporkan',
            (int) ($ringkas['arsip'] ?? 0) === 1, (string) ($ringkas['arsip'] ?? '-'));
        $this->cek('arsipnya benar-benar ikut hilang',
            $db->table('rpjmd_versi_misi')->where('version_id', $v)->countAllResults() === 0);

        CLI::newLine();
    }

    /**
     * Kewenangan hapus terkunci pada modul RPJMD.
     *
     * Diuji lewat RenstraController TANPA sesi: `versiOpdId()`-nya akan
     * mengembalikan NULL, persis keadaan yang dulu bisa menyamar sebagai
     * lingkup kabupaten.
     */
    private function ujiLingkup(): void
    {
        CLI::write('== Lingkup: hanya RPJMD ==', 'yellow');

        $baca = static function (object $c): bool {
            $m = new ReflectionMethod($c, 'versiBolehHapus');
            $m->setAccessible(true);

            return (bool) $m->invoke($c);
        };

        $renstra = new RenstraController();

        $this->cek('RenstraController menolak hapus walau opd_id sesi NULL',
            $baca($renstra) === false);

        // RpjmdController lolos gerbang modul; yang menahannya kini tinggal
        // user_can(), yang di CLI memang tidak terpenuhi. Yang diuji di sini
        // adalah bahwa penolakannya BUKAN karena modul.
        $rpjmd  = new RpjmdController();
        $modul  = new ReflectionMethod($rpjmd, 'versiModul');
        $modul->setAccessible(true);
        $opd    = new ReflectionMethod($rpjmd, 'versiOpdId');
        $opd->setAccessible(true);

        $this->cek('RpjmdController bermodul rpjmd', $modul->invoke($rpjmd) === 'rpjmd');
        $this->cek('RpjmdController berlingkup kabupaten (opdId NULL)',
            $opd->invoke($rpjmd) === null);

        CLI::newLine();
    }
}
