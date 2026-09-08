<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Uji asap & matriks keamanan lewat HTTP sungguhan (§47, §49).
 *
 *   php spark jaga:asap --base http://localhost:8080 --user admin_kab --pass ...
 *   php spark jaga:asap --base ... --user ... --pass ... --peran admin_opd
 *
 * =====================================================================
 * MENGAPA LEWAT HTTP, BUKAN MEMANGGIL CONTROLLER
 *
 * Yang dijaga di sini justru hal-hal yang HANYA ada di lapisan HTTP: filter
 * rute, CSRF, sesi, dan pembedaan metode. Memanggil method controller
 * langsung akan melewati semuanya — dan uji yang melewati penjagaannya
 * sendiri akan selalu lulus, termasuk saat penjagaannya sudah copot.
 *
 * Bug IDOR RKT yang ditemukan 6 September 2026 adalah contohnya: seluruh
 * pemeriksa yang ada waktu itu lulus, karena tak satu pun benar-benar
 * mengirim permintaan sebagai pengguna lain.
 *
 * =====================================================================
 * HANYA MEMBACA — DAN ITU DISENGAJA
 *
 * Perintah ini TIDAK menulis apa pun. Yang diuji:
 *
 *   * halaman utama tiap modul terbuka (§49 regression smoke);
 *   * permintaan tanpa sesi ditolak;
 *   * POST tanpa CSRF ditolak;
 *   * POST dengan CSRF karangan ditolak;
 *   * mutasi lewat GET tidak dikenali sebagai rute.
 *
 * Ketiga uji CSRF di atas semuanya berakhir DITOLAK bila penjagaannya benar,
 * jadi tidak ada data yang berubah walau dijalankan pada basis data sungguhan.
 * Uji lintas-OPD yang benar-benar MENULIS sengaja tidak ada di sini: bila
 * penjagaannya sedang copot, ujinya sendiri yang akan merusak data. Untuk itu
 * pakai salinan dan uji tangan.
 */
class JagaAsap extends BaseCommand
{
    protected $group       = 'SAKIP';
    protected $name        = 'jaga:asap';
    protected $description = 'Uji asap halaman & matriks keamanan HTTP (CSRF, sesi, mutasi GET).';
    protected $usage       = 'jaga:asap --base <url> --user <nama> --pass <sandi> [--peran opd|kab]';
    protected $options     = [
        '--base'  => 'alamat dasar aplikasi, mis. http://localhost:8080',
        '--user'  => 'nama pengguna untuk masuk',
        '--pass'  => 'kata sandi',
        '--peran' => 'opd | kab (menentukan halaman mana yang diasapi)',
    ];

    private int $lulus = 0;
    private int $gagal = 0;
    private string $base = '';
    private string $cookie = '';

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

    /**
     * Satu permintaan HTTP dengan toples cookie yang sama.
     *
     * @param array<string,string>|null $post null = GET
     *
     * @return array{kode:int, isi:string}
     */
    private function minta(string $jalur, ?array $post = null): array
    {
        $ch = curl_init($this->base . $jalur);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_COOKIEJAR      => $this->cookie,
            CURLOPT_COOKIEFILE     => $this->cookie,
            CURLOPT_TIMEOUT        => 20,
        ]);

        if ($post !== null) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
        }

        $isi  = (string) curl_exec($ch);
        $kode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return ['kode' => $kode, 'isi' => $isi];
    }

    /** Token CSRF dari sebuah halaman, atau '' bila tidak ada. */
    private function token(string $jalur): string
    {
        $h = $this->minta($jalur);

        if (preg_match('/name="csrf_test_name"[^>]*value="([^"]+)"/', $h['isi'], $m)) {
            return $m[1];
        }

        return '';
    }

    public function run(array $params)
    {
        $this->base = rtrim((string) (CLI::getOption('base') ?: 'http://localhost:8080'), '/');
        $user       = (string) (CLI::getOption('user') ?: '');
        $pass       = (string) (CLI::getOption('pass') ?: '');
        $peran      = (string) (CLI::getOption('peran') ?: 'kab');

        if ($user === '' || $pass === '') {
            CLI::error('Wajib --user dan --pass. Perintah ini masuk sebagai pengguna sungguhan.');

            return EXIT_ERROR;
        }

        if (! function_exists('curl_init')) {
            CLI::error('Ekstensi cURL tidak aktif.');

            return EXIT_ERROR;
        }

        $this->cookie = tempnam(sys_get_temp_dir(), 'asap');

        CLI::write('Sasaran: ' . $this->base . '  peran=' . $peran, 'yellow');
        CLI::newLine();

        try {
            $this->ujiTanpaSesi();

            if (! $this->masuk($user, $pass)) {
                CLI::error('Gagal masuk sebagai ' . $user . '. Periksa --user/--pass.');

                return EXIT_ERROR;
            }

            $this->ujiHalaman($peran);
            $this->ujiCsrf($peran);
            $this->ujiMutasiGet($peran);
        } finally {
            if ($this->cookie !== '' && is_file($this->cookie)) {
                unlink($this->cookie);
            }
        }

        CLI::newLine();
        CLI::write('LULUS ' . $this->lulus . '   GAGAL ' . $this->gagal,
            $this->gagal === 0 ? 'green' : 'red');

        return $this->gagal === 0 ? EXIT_SUCCESS : EXIT_ERROR;
    }

    /** Tanpa sesi, halaman dalam tidak boleh terbuka. */
    private function ujiTanpaSesi(): void
    {
        CLI::write('== Tanpa sesi ==', 'yellow');

        foreach (['/adminkab/dashboard', '/adminopd/dashboard', '/adminkab/rpjmd/versi'] as $jalur) {
            $r = $this->minta($jalur);

            // 3xx (dialihkan ke login) maupun 401/403 sama-sama benar; yang
            // tidak boleh adalah 200 berisi halamannya.
            $this->cek('tanpa sesi ditolak: ' . $jalur,
                $r['kode'] !== 200, 'http ' . $r['kode']);
        }

        CLI::newLine();
    }

    private function masuk(string $user, string $pass): bool
    {
        $t = $this->token('/login');

        if ($t === '') {
            return false;
        }

        $r = $this->minta('/login/authenticate', [
            'csrf_test_name' => $t,
            'username'       => $user,
            'password'       => $pass,
        ]);

        return $r['kode'] >= 300 && $r['kode'] < 400;
    }

    /** @return list<string> */
    private function halaman(string $peran): array
    {
        if ($peran === 'opd') {
            return [
                '/adminopd/dashboard', '/adminopd/renstra', '/adminopd/rkt',
                '/adminopd/iku', '/adminopd/iku/revisi', '/adminopd/cascading',
                // PK selalu berjenjang: `adminopd/pk` tanpa segmen memang
                // tidak punya rute. Diasapi dua jenjang yang paling banyak
                // dipakai, bukan satu, karena tampilannya memang berbeda.
                '/adminopd/pk/jpt', '/adminopd/pk/administrator',
                '/adminopd/target_renaksi', '/adminopd/monev',
                '/adminopd/lakip',
            ];
        }

        return [
            '/adminkab/dashboard', '/adminkab/rpjmd', '/adminkab/rpjmd/versi',
            '/adminkab/iku', '/adminkab/iku/revisi', '/adminkab/cascading',
            '/adminkab/lakip', '/adminkab/verifikasi',
        ];
    }

    /**
     * §49: halaman utama tiap modul terbuka dan tidak memuat jejak galat.
     *
     * Yang dicari bukan hanya kode 200. Halaman bisa membalas 200 sambil
     * memuat pesan galat PHP di badannya — dan itu justru bentuk kerusakan
     * yang paling mudah lolos dari pemeriksaan berbasis status.
     */
    private function ujiHalaman(string $peran): void
    {
        CLI::write('== Asap halaman (§49) ==', 'yellow');

        foreach ($this->halaman($peran) as $jalur) {
            $r = $this->minta($jalur);
            $ok = $r['kode'] === 200;

            $this->cek('terbuka: ' . $jalur, $ok, 'http ' . $r['kode']);

            if (! $ok) {
                continue;
            }

            $jejak = false;

            foreach (['Fatal error', 'Parse error', 'Uncaught ', 'SQLSTATE',
                'Call to a member function', 'Undefined variable'] as $tanda) {
                if (str_contains($r['isi'], $tanda)) {
                    $jejak = true;
                }
            }

            $this->cek('tanpa jejak galat: ' . $jalur, ! $jejak);
        }

        CLI::newLine();
    }

    /** Rute POST yang aman dipakai menguji CSRF: ditolak sebelum menyentuh data. */
    private function ruteUji(string $peran): string
    {
        return $peran === 'opd'
            ? '/adminopd/rkt/update-status'
            : '/adminkab/rpjmd/versi/hapus/999999';
    }

    /**
     * §47: POST tanpa CSRF dan dengan CSRF karangan sama-sama ditolak.
     *
     * Dipakai id yang PASTI tidak ada (999999) supaya walau CSRF-nya kelak
     * lolos, tidak ada baris nyata yang bisa tersentuh oleh uji ini.
     */
    private function ujiCsrf(string $peran): void
    {
        CLI::write('== CSRF (§47) ==', 'yellow');

        $rute = $this->ruteUji($peran);

        $tanpa = $this->minta($rute, ['indikator_id' => '999999', 'tahun' => '2026']);

        $this->cek('POST tanpa token CSRF ditolak (403)',
            $tanpa['kode'] === 403, 'http ' . $tanpa['kode']);

        $palsu = $this->minta($rute, [
            'csrf_test_name' => str_repeat('a', 32),
            'indikator_id'   => '999999',
            'tahun'          => '2026',
        ]);

        $this->cek('POST dengan token CSRF karangan ditolak (403)',
            $palsu['kode'] === 403, 'http ' . $palsu['kode']);

        // =============================================================
        // KONTROL: BUKTIKAN 403 DI ATAS MEMANG KARENA CSRF
        //
        // Tanpa kontrol ini kedua uji di atas bisa LULUS TANPA MENGUJI APA
        // PUN: rute yang membalas 403 kepada semua orang — karena izinnya
        // berubah, filternya salah pasang, atau sesinya tidak terbentuk —
        // akan tampak seperti CSRF yang bekerja sempurna.
        //
        // Itu bukan kemungkinan teoretis. Saat perintah ini disusun, kontrol
        // pertamanya justru gagal: tokennya diambil dari halaman yang tidak
        // merender form sama sekali, sehingga "token sah" yang dikirim
        // sebenarnya kosong.
        //
        // Token diambil dari halaman yang MEMANG punya form, dan sasarannya
        // memakai id yang pasti tidak ada, sehingga yang sah pun berakhir
        // sebagai "tidak ditemukan" — tidak ada baris yang tersentuh.
        // =============================================================
        $halamanForm = $peran === 'opd'
            ? '/adminopd/iku/revisi/buat'
            : '/adminkab/iku/revisi/buat';

        $sah = $this->token($halamanForm);

        if ($sah === '') {
            $this->cek('kontrol: halaman form menyediakan token', false,
                'tidak ada csrf_test_name di ' . $halamanForm);
        } else {
            $benar = $this->minta($rute, [
                'csrf_test_name' => $sah,
                'indikator_id'   => '999999',
                'tahun'          => '2026',
            ]);

            $this->cek('kontrol: token SAH tidak ditolak 403 — jadi 403 di atas memang CSRF',
                $benar['kode'] !== 403, 'http ' . $benar['kode']);
        }

        CLI::newLine();
    }

    /**
     * §47: mutasi tidak boleh bisa dipicu lewat GET.
     *
     * Sekadar membuka alamat tidak boleh mengubah apa pun — itu sebabnya
     * seluruh rute mutasi dipindah ke POST/DELETE pada Phase 0. Di sini
     * dibuktikan rutenya memang TIDAK dikenali sebagai GET.
     */
    private function ujiMutasiGet(string $peran): void
    {
        CLI::write('== Mutasi lewat GET (§47) ==', 'yellow');

        $rute = $this->ruteUji($peran);
        $r    = $this->minta($rute);

        $this->cek('rute mutasi tidak menerima GET',
            in_array($r['kode'], [404, 405], true), 'http ' . $r['kode']);

        CLI::newLine();
    }
}
