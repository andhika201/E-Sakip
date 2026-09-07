<?php

namespace App\Commands;

use App\Controllers\AdminOpd\PkRenaksiController;
use App\Models\Opd\MonevModel;
use App\Models\Opd\TargetModel;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\SiteURI;
use CodeIgniter\HTTP\UserAgent;
use ReflectionMethod;
use ReflectionProperty;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Throwable;

/**
 * Uji transaksi SUB RENCANA AKSI: simpan, sunting, hapus.
 *
 *   php spark renaksi:sub-check [--db <salinan>]
 *
 * =====================================================================
 * APA YANG DIJAGA DI SINI
 *
 * `saveSubRencana()` bukan "hapus semua lalu tulis ulang". Ia MEREKONSILIASI:
 * baris yang id-nya dikirim balik diperbarui DI TEMPAT, yang tidak dikirim
 * dihapus, yang tanpa id disisipkan baru. Itu disengaja, dan taruhannya besar:
 *
 *   * capaian MONEV menempel pada `target_sub_rencana_id`, dan kolom itu TIDAK
 *     punya foreign key. Kalau id sub berubah setiap kali rencana aksi
 *     disunting, seluruh capaian yang sudah diisi akan menggantung tanpa
 *     induk — tidak hilang dari tabel, tetapi tidak pernah tampil lagi.
 *   * karena tanpa foreign key pula, menghapus sub TIDAK otomatis membersihkan
 *     capaiannya. Itu tugas `hapusCapaianSubYatim()`, dan kalau ia lupa
 *     dipanggil, capaian sub yang sudah dihapus akan hidup terus dan bisa
 *     terhitung ulang saat id-nya kelak dipakai baris lain.
 *
 * Ditambah `satuan`, yang baru masuk 31 Agustus 2026 dan melewati query builder
 * langsung — bukan `allowedFields` — sehingga tidak ada jaring pengaman yang
 * menangkapnya bila salah nama kolom.
 *
 * =====================================================================
 * FIXTURE
 *
 * Seluruh baris uji ditandai `UJI-SUB-RENAKSI` dan dibuang lagi di akhir,
 * sukses maupun gagal. Baris `target_rencana` induknya dibuat tanpa
 * `pk_indikator_id`, jadi tidak menyentuh data PK mana pun.
 */
class RenaksiSubCheck extends BaseCommand
{
    protected $group       = 'SAKIP';
    protected $name        = 'renaksi:sub-check';
    protected $description = 'Uji simpan/sunting/hapus sub rencana aksi beserta satuannya.';
    protected $usage       = 'renaksi:sub-check [--db <nama>]';
    protected $options     = ['--db' => 'kerjakan pada basis data lain (mis. salinan uji)'];

    private const TANDA = 'UJI-SUB-RENAKSI';

    private int $lulus = 0;
    private int $gagal = 0;

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

        if (! $db->fieldExists('satuan', 'target_sub_rencana')) {
            CLI::error('Kolom target_sub_rencana.satuan belum ada. '
                . 'Jalankan db/update_2026-08-31_satuan_sub_rencana.sql lebih dulu.');

            return EXIT_ERROR;
        }

        $targets = new TargetModel($db);
        $monev   = new MonevModel($db);
        $targetId = 0;

        try {
            $db->table('target_rencana')->insert([
                'rencana_aksi' => self::TANDA . ' induk',
                'created_at'   => date('Y-m-d H:i:s'),
                'updated_at'   => date('Y-m-d H:i:s'),
            ]);
            $targetId = (int) $db->insertID();

            CLI::write('Target uji  : #' . $targetId);
            CLI::newLine();

            $this->ujiSimpan($db, $targets, $targetId);
            $this->ujiBacaUlang($db, $targets, $targetId);
            $this->ujiSunting($db, $targets, $targetId);
            $this->ujiHapus($db, $targets, $monev, $targetId);
            $this->ujiTepi($db, $targets, $targetId);
            $this->ujiBacaPost();
        } catch (Throwable $e) {
            $this->gagal++;
            CLI::error('Galat tak terduga: ' . $e->getMessage());
            CLI::write('  ' . $e->getFile() . ':' . $e->getLine());
        } finally {
            if ($targetId > 0) {
                // target_sub_rencana & monev ikut terhapus lewat ON DELETE CASCADE.
                $db->table('target_rencana')->where('id', $targetId)->delete();
            }

            $db->table('target_rencana')->like('rencana_aksi', self::TANDA)->delete();
        }

        CLI::newLine();
        CLI::write(sprintf('LULUS: %d   GAGAL: %d', $this->lulus, $this->gagal),
            $this->gagal === 0 ? 'green' : 'red');

        return $this->gagal === 0 ? EXIT_SUCCESS : EXIT_ERROR;
    }

    /** Baris sub milik target uji, urut seperti yang dibaca form. */
    private function baris($db, int $targetId): array
    {
        return $db->table('target_sub_rencana')
            ->where('target_rencana_id', $targetId)
            ->orderBy('baris_rencana')->orderBy('urutan')->orderBy('id')
            ->get()->getResultArray();
    }

    /* ================================================================= */

    private function ujiSimpan($db, TargetModel $targets, int $targetId): void
    {
        CLI::write('== 1. SIMPAN BARU ==', 'yellow');

        $jumlah = $targets->saveSubRencana($targetId, [
            0 => [
                ['id' => 0, 'teks' => 'Sub A', 'satuan' => 'Dokumen',
                 'tw' => [1 => '10', 2 => '20', 3 => '30', 4 => '40']],
                ['id' => 0, 'teks' => 'Sub B', 'satuan' => '',
                 'tw' => [1 => '1', 2 => '', 3 => null, 4 => '4']],
            ],
            1 => [
                ['id' => 0, 'teks' => 'Sub C', 'satuan' => 'Persentase',
                 'tw' => [1 => '', 2 => '', 3 => '', 4 => '']],
            ],
        ]);

        $rows = $this->baris($db, $targetId);

        $this->cek('jumlah yang dilaporkan sesuai', $jumlah === 3, 'dapat ' . $jumlah);
        $this->cek('3 baris benar-benar masuk basis data', count($rows) === 3, 'dapat ' . count($rows));

        $a = $rows[0] ?? [];
        $b = $rows[1] ?? [];
        $c = $rows[2] ?? [];

        $this->cek('teks tersimpan apa adanya', ($a['sub_rencana_aksi'] ?? '') === 'Sub A');
        $this->cek('satuan tersimpan', ($a['satuan'] ?? null) === 'Dokumen', var_export($a['satuan'] ?? null, true));
        // Sengaja TANPA `??`: operator itu menyamakan NULL dengan "tidak ada",
        // sehingga justru menyembunyikan yang hendak diperiksa di sini.
        $this->cek('satuan kosong disimpan NULL, bukan string kosong',
            array_key_exists('satuan', $b) && $b['satuan'] === null,
            var_export($b['satuan'] ?? '(kolom tidak ada)', true));
        $this->cek('target triwulan 1-4 masuk ke kolom yang tepat',
            ($a['target_triwulan_1'] ?? '') === '10' && ($a['target_triwulan_2'] ?? '') === '20'
            && ($a['target_triwulan_3'] ?? '') === '30' && ($a['target_triwulan_4'] ?? '') === '40');
        $this->cek('triwulan kosong jadi NULL',
            array_key_exists('target_triwulan_2', $b) && $b['target_triwulan_2'] === null
            && array_key_exists('target_triwulan_3', $b) && $b['target_triwulan_3'] === null,
            var_export([$b['target_triwulan_2'] ?? '(tidak ada)', $b['target_triwulan_3'] ?? '(tidak ada)'], true));
        $this->cek('baris_rencana memisahkan butir rencana aksi',
            (int) ($a['baris_rencana'] ?? -1) === 0 && (int) ($c['baris_rencana'] ?? -1) === 1);
        $this->cek('urutan dihitung ulang per butir',
            (int) ($a['urutan'] ?? -1) === 0 && (int) ($b['urutan'] ?? -1) === 1
            && (int) ($c['urutan'] ?? -1) === 0);

        CLI::newLine();
    }

    private function ujiBacaUlang($db, TargetModel $targets, int $targetId): void
    {
        CLI::write('== 2. BACA ULANG UNTUK FORM EDIT ==', 'yellow');

        $peta = $targets->getSubRencanaByTarget($targetId);

        $this->cek('dikelompokkan per butir rencana aksi',
            isset($peta[0], $peta[1]) && count($peta[0]) === 2 && count($peta[1]) === 1);

        $a = $peta[0][0] ?? [];

        $this->cek('membawa id sub (kunci penempelan capaian MONEV)',
            (int) ($a['id'] ?? 0) > 0);
        $this->cek('membawa teks', ($a['teks'] ?? '') === 'Sub A');
        // Inilah yang dulu terbuang di form: modelnya mengirim, tetapi
        // penginisialisasi JS membuangnya sehingga dropdown terbuka kosong.
        $this->cek('membawa SATUAN', ($a['satuan'] ?? '') === 'Dokumen',
            var_export($a['satuan'] ?? null, true));
        $this->cek('triwulan berindeks 1..4 seperti yang diharapkan form',
            ($a['tw'][1] ?? '') === '10' && ($a['tw'][4] ?? '') === '40');

        CLI::newLine();
    }

    private function ujiSunting($db, TargetModel $targets, int $targetId): void
    {
        CLI::write('== 3. SUNTING (id harus BERTAHAN) ==', 'yellow');

        $sebelum = $this->baris($db, $targetId);
        $idA = (int) $sebelum[0]['id'];
        $idB = (int) $sebelum[1]['id'];
        $idC = (int) $sebelum[2]['id'];

        $targets->saveSubRencana($targetId, [
            0 => [
                ['id' => $idA, 'teks' => 'Sub A diubah', 'satuan' => 'Orang',
                 'tw' => [1 => '11', 2 => '22', 3 => '33', 4 => '44']],
                ['id' => $idB, 'teks' => 'Sub B', 'satuan' => 'Kegiatan',
                 'tw' => [1 => '1', 2 => '2', 3 => '3', 4 => '4']],
            ],
            1 => [
                ['id' => $idC, 'teks' => 'Sub C', 'satuan' => '',
                 'tw' => [1 => '', 2 => '', 3 => '', 4 => '']],
            ],
        ]);

        $sesudah = $this->baris($db, $targetId);

        $this->cek('jumlah baris tetap 3', count($sesudah) === 3, 'dapat ' . count($sesudah));
        $this->cek('id TIDAK berubah — capaian MONEV tetap menempel',
            array_map('intval', array_column($sesudah, 'id')) === [$idA, $idB, $idC],
            implode(',', array_column($sesudah, 'id')) . ' vs ' . implode(',', [$idA, $idB, $idC]));
        $this->cek('teks diperbarui', ($sesudah[0]['sub_rencana_aksi'] ?? '') === 'Sub A diubah');
        $this->cek('satuan diperbarui', ($sesudah[0]['satuan'] ?? '') === 'Orang');
        $this->cek('satuan yang semula kosong bisa DIISI',
            ($sesudah[1]['satuan'] ?? '') === 'Kegiatan');
        $this->cek('satuan yang semula terisi bisa DIKOSONGKAN kembali',
            array_key_exists('satuan', $sesudah[2]) && $sesudah[2]['satuan'] === null,
            var_export($sesudah[2]['satuan'] ?? '(kolom tidak ada)', true));
        $this->cek('triwulan diperbarui', ($sesudah[0]['target_triwulan_2'] ?? '') === '22');

        CLI::newLine();
    }

    private function ujiHapus($db, TargetModel $targets, MonevModel $monev, int $targetId): void
    {
        CLI::write('== 4. HAPUS SATU SUB (tombol x pada form) ==', 'yellow');

        $sebelum = $this->baris($db, $targetId);
        $idA = (int) $sebelum[0]['id'];
        $idB = (int) $sebelum[1]['id'];
        $idC = (int) $sebelum[2]['id'];

        // Capaian MONEV menempel pada B (yang akan dihapus) dan A (yang bertahan).
        foreach ([$idA, $idB] as $sub) {
            $db->table('monev')->insert([
                'target_rencana_id'      => $targetId,
                'target_sub_rencana_id'  => $sub,
                'created_at'             => date('Y-m-d H:i:s'),
                'updated_at'             => date('Y-m-d H:i:s'),
            ]);
        }

        $adaMonev = static fn (int $sub): int => (int) $db->table('monev')
            ->where('target_rencana_id', $targetId)
            ->where('target_sub_rencana_id', $sub)
            ->countAllResults();

        $this->cek('capaian uji terpasang lebih dulu', $adaMonev($idA) === 1 && $adaMonev($idB) === 1);

        // B dihilangkan dari kiriman — persis seperti menekan tombol x lalu simpan.
        $targets->saveSubRencana($targetId, [
            0 => [['id' => $idA, 'teks' => 'Sub A diubah', 'satuan' => 'Orang',
                   'tw' => [1 => '11', 2 => '22', 3 => '33', 4 => '44']]],
            1 => [['id' => $idC, 'teks' => 'Sub C', 'satuan' => '',
                   'tw' => [1 => '', 2 => '', 3 => '', 4 => '']]],
        ]);

        $sesudah = $this->baris($db, $targetId);
        $idSisa  = array_map('intval', array_column($sesudah, 'id'));

        $this->cek('sub yang dibuang benar-benar hilang', ! in_array($idB, $idSisa, true));
        $this->cek('sub lain BERTAHAN dengan id yang sama', $idSisa === [$idA, $idC],
            implode(',', $idSisa));

        // Inilah yang dikerjakan controller sesudah menyimpan.
        $monev->hapusCapaianSubYatim($targetId);

        $this->cek('capaian milik sub yang dihapus ikut dibersihkan', $adaMonev($idB) === 0);
        $this->cek('capaian milik sub yang bertahan TIDAK ikut terbawa', $adaMonev($idA) === 1);

        CLI::newLine();
    }

    private function ujiTepi($db, TargetModel $targets, int $targetId): void
    {
        CLI::write('== 5. KASUS TEPI ==', 'yellow');

        $sebelum = $this->baris($db, $targetId);
        $idA     = (int) $sebelum[0]['id'];

        // Sub milik TARGET LAIN. Id asing tidak boleh menimpa baris orang lain.
        $db->table('target_rencana')->insert([
            'rencana_aksi' => self::TANDA . ' tetangga',
            'created_at'   => date('Y-m-d H:i:s'),
            'updated_at'   => date('Y-m-d H:i:s'),
        ]);
        $lainId = (int) $db->insertID();
        $targets->saveSubRencana($lainId, [
            0 => [['id' => 0, 'teks' => 'Milik tetangga', 'satuan' => 'Dokumen', 'tw' => []]],
        ]);
        $idTetangga = (int) ($db->table('target_sub_rencana')
            ->where('target_rencana_id', $lainId)->get()->getRowArray()['id'] ?? 0);

        $targets->saveSubRencana($targetId, [
            0 => [
                ['id' => $idA, 'teks' => 'Sub A diubah', 'satuan' => 'Orang', 'tw' => []],
                // id asing: harus jadi baris BARU di target ini, bukan menimpa tetangga
                ['id' => $idTetangga, 'teks' => 'Curian', 'satuan' => 'Dokumen', 'tw' => []],
                // teks kosong: dilewati, tidak menghasilkan baris hampa
                ['id' => 0, 'teks' => '   ', 'satuan' => 'Dokumen', 'tw' => []],
                // satuan kelewat panjang: dipotong 50, bukan menggagalkan simpan
                ['id' => 0, 'teks' => 'Sub panjang', 'satuan' => str_repeat('X', 80), 'tw' => []],
            ],
        ]);

        $rows      = $this->baris($db, $targetId);
        $tetangga  = $db->table('target_sub_rencana')->where('id', $idTetangga)->get()->getRowArray();
        $teksSemua = array_column($rows, 'sub_rencana_aksi');

        $this->cek('baris tetangga TIDAK tertimpa oleh id asing',
            ($tetangga['sub_rencana_aksi'] ?? '') === 'Milik tetangga'
            && (int) ($tetangga['target_rencana_id'] ?? 0) === $lainId);
        $this->cek('id asing diperlakukan sebagai baris baru',
            in_array('Curian', $teksSemua, true));
        $this->cek('teks kosong tidak menghasilkan baris hampa',
            ! in_array('', array_map('trim', $teksSemua), true));

        $panjang = null;

        foreach ($rows as $r) {
            if ($r['sub_rencana_aksi'] === 'Sub panjang') {
                $panjang = $r;
            }
        }

        $this->cek('satuan kelewat panjang dipotong 50 huruf, bukan menggagalkan simpan',
            $panjang !== null && mb_strlen((string) $panjang['satuan']) === 50,
            $panjang === null ? 'barisnya tidak ada' : mb_strlen((string) $panjang['satuan']) . ' huruf');

        $db->table('target_rencana')->where('id', $lainId)->delete();

        CLI::newLine();
    }

    /**
     * Jalur POST -> array, yaitu `PkRenaksiController::bacaSubRencana()`.
     *
     * Diuji lewat refleksi, bukan lewat HTTP: yang perlu dipastikan di sini
     * adalah penafsiran kiriman form, bukan rute maupun hak aksesnya. Method
     * aslinya yang dipanggil, bukan salinan — kalau penyaringnya kelak diubah,
     * uji ini ikut berubah artinya.
     */
    private function ujiBacaPost(): void
    {
        CLI::write('== 6. BACA KIRIMAN FORM (POST -> array) ==', 'yellow');

        $ctrl = new PkRenaksiController();

        $prop = new ReflectionProperty($ctrl, 'request');
        $prop->setAccessible(true);

        $baca = new ReflectionMethod($ctrl, 'bacaSubRencana');
        $baca->setAccessible(true);

        // Sejak CI 4.7, IncomingRequest tidak membaca $_POST langsung melainkan
        // lewat layanan `superglobals`, yang memotret isinya sekali. Menyetel
        // $_POST saja tidak berpengaruh — permintaan akan selalu terbaca kosong
        // dan seluruh uji di bawah "lulus" karena alasan yang salah. Jadi
        // kirimannya dipasang lewat layanan itu, dan permintaannya dibangun
        // ULANG tiap kali karena isinya ikut disimpan di objeknya.
        $jalankan = static function (?string $json) use ($baca, $ctrl, $prop) {
            service('superglobals')->setPostArray($json === null ? [] : ['sub_rencana_json' => $json]);

            $prop->setValue($ctrl, new IncomingRequest(
                config('App'),
                new SiteURI(config('App')),
                null,
                new UserAgent()
            ));

            return $baca->invoke($ctrl);
        };

        // Bentuk persis seperti yang disusun sync() di form.
        $hasil = $jalankan(json_encode([
            '0' => [
                ['id' => 12, 'teks' => 'Sub A', 'satuan' => 'Dokumen', 'tw' => ['10', '20', '', '40']],
            ],
            '1' => [
                ['id' => 0, 'teks' => '  Sub B  ', 'satuan' => '', 'tw' => ['', '', '', '']],
            ],
        ], JSON_UNESCAPED_UNICODE));

        $this->cek('kiriman sah terbaca sebagai dua butir',
            is_array($hasil) && isset($hasil[0], $hasil[1]));
        $this->cek('id sub ikut terbawa dari form',
            (int) ($hasil[0][0]['id'] ?? 0) === 12);
        $this->cek('SATUAN ikut terbawa dari form',
            ($hasil[0][0]['satuan'] ?? null) === 'Dokumen',
            var_export($hasil[0][0]['satuan'] ?? null, true));
        // Form mengirim tw berindeks 0..3; basis data memakai 1..4.
        $this->cek('triwulan digeser dari indeks 0..3 ke 1..4',
            ($hasil[0][0]['tw'][1] ?? null) === '10'
            && ($hasil[0][0]['tw'][2] ?? null) === '20'
            && ($hasil[0][0]['tw'][4] ?? null) === '40');
        $this->cek('triwulan kosong jadi NULL, bukan string kosong',
            array_key_exists(3, $hasil[0][0]['tw'] ?? []) && $hasil[0][0]['tw'][3] === null);
        $this->cek('spasi di ujung teks dipangkas',
            ($hasil[1][0]['teks'] ?? '') === 'Sub B');

        $this->cek('kiriman kosong bukan galat, hanya tidak ada sub',
            $jalankan(null) === []);
        $this->cek('JSON rusak ditolak dengan aman, bukan fatal',
            $jalankan('{bukan json') === []);

        // Penyaring: yang berbahaya HARUS ditolak, bukan dibersihkan diam-diam.
        $skrip = json_encode(['0' => [['id' => 0, 'teks' => '<script>alert(1)</script>', 'satuan' => '', 'tw' => []]]]);
        $this->cek('teks mengandung <script> DITOLAK', $jalankan($skrip) === false);

        $satuanNakal = json_encode(['0' => [['id' => 0, 'teks' => 'Sub', 'satuan' => 'javascript:x', 'tw' => []]]]);
        $this->cek('satuan berisi javascript: DITOLAK', $jalankan($satuanNakal) === false);

        $satuanPanjang = json_encode(['0' => [['id' => 0, 'teks' => 'Sub', 'satuan' => str_repeat('X', 51), 'tw' => []]]]);
        $this->cek('satuan lebih dari 50 huruf DITOLAK di controller',
            $jalankan($satuanPanjang) === false);

        service('superglobals')->setPostArray([]);

        CLI::newLine();
    }
}
