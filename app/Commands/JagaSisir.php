<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Sisir SELURUH halaman yang bisa dibuka satu peran, lalu laporkan yang rusak.
 *
 *   php spark jaga:sisir --base http://localhost:8080 --user admin_kab --pass ...
 *   php spark jaga:sisir --base ... --user ... --pass ... --maks 400
 *
 * =====================================================================
 * MENGAPA ADA, PADAHAL SUDAH ADA `jaga:asap`
 *
 * `jaga:asap` menjaga LAPISAN: filter rute, CSRF, sesi, metode HTTP. Halaman
 * yang dibukanya sengaja sedikit — delapan halaman utama tiap peran — karena
 * yang diuji memang bukan halamannya, melainkan penjagaannya.
 *
 * Akibatnya seluruh halaman DALAM tidak pernah tersentuh. Pada 8 September
 * 2026 empat halaman terbukti rusak total sementara `jaga:asap` tetap lulus
 * 23/23: verifikasi revisi IKU (Array to string conversion), tentang_kami/edit
 * (view-nya memang tidak pernah ada), serta dua jalur cetak PDF yang melampaui
 * `pcre.backtrack_limit`. Tidak satu pun ada di daftar delapan halaman itu.
 *
 * Perintah ini melengkapi, bukan menggantikan: `jaga:asap` menjaga pagar,
 * `jaga:sisir` menyapu isi halamannya.
 *
 * =====================================================================
 * DUA LAPIS, KARENA MASING-MASING BUTA PADA SATU HAL
 *
 *   1. DAFTAR RUTE — mengambil seluruh rute GET dari Router. Cakupannya
 *      lengkap, tetapi ruas ber-placeholder harus ditebak id-nya, sehingga
 *      banyak yang berhenti di "tidak ditemukan" dan isinya tak teruji.
 *
 *   2. JELAJAH TAUTAN — berangkat dari beranda peran itu lalu mengikuti
 *      `href` yang benar-benar dirender. Id-nya asli, jadi halaman detail
 *      betul-betul terbuka; tetapi hanya menjangkau yang ada tautannya.
 *
 * Yang lolos dari lapis pertama sering tertangkap lapis kedua, dan sebaliknya.
 *
 * =====================================================================
 * HANYA MEMBACA
 *
 * Cuma GET, dan URL yang polanya menghapus/menyetujui/mengesahkan dilewati —
 * lihat POLA_MUTASI. Rute mutasi memang tidak melayani GET (`jaga:asap` yang
 * menjaga itu), tetapi menekannya tetap akan tercatat di log aktivitas dan
 * mengotori jejak audit, jadi lebih baik tidak disentuh sama sekali.
 */
class JagaSisir extends BaseCommand
{
    protected $group       = 'Jaga';
    protected $name        = 'jaga:sisir';
    protected $description = 'Sisir seluruh halaman satu peran; laporkan yang 500 atau memuat jejak galat.';
    protected $usage       = 'jaga:sisir --base <url> --user <nama> --pass <sandi> [--maks 300]';
    protected $arguments   = [];
    protected $options     = [
        '--base' => 'alamat dasar aplikasi, mis. http://localhost:8080',
        '--user' => 'nama pengguna untuk masuk',
        '--pass' => 'kata sandi',
        '--maks' => 'batas halaman yang dijelajah pada lapis 2 (bawaan 300)',
    ];

    /** URL yang MENGUBAH data — tidak pernah disentuh. */
    private const POLA_MUTASI = '#/(logout|delete|hapus|setujui|tolak|kembalikan|sahkan|tetapkan|cabut'
        . '|tarik|ajukan|simpan|save|update|clear|reset|sync/run|import/process|selesai)(/|$)#i';

    /** Berkas statis — tidak perlu disisir. */
    private const POLA_ASET = '#\.(css|js|png|jpe?g|gif|svg|ico|woff2?|ttf|map)($|\?)#i';

    /**
     * Jejak galat pada BADAN jawaban.
     *
     * Perlu diperiksa terpisah dari kode HTTP: sebagian galat dirender di
     * dalam halaman yang tetap berkode 200 — peringatan PHP yang tercetak di
     * tengah tabel, misalnya — dan itu justru yang paling lama tidak
     * ketahuan karena layarnya "terbuka".
     */
    private const POLA_GALAT = '/(Fatal error|Uncaught|Call to a member function'
        . '|Undefined (array key|variable|property)|Trying to access array offset'
        . '|ErrorException|DivisionByZeroError|TypeError|MpdfException|ViewException'
        . '|Array to string conversion)/';

    private string $base   = '';
    private string $cookie = '';
    private int $lulus     = 0;
    private int $gagal     = 0;

    /** @var list<array{jalur:string, kode:int, jejak:string, pesan:string}> */
    private array $temuan = [];

    public function run(array $params)
    {
        $this->base = rtrim((string) (CLI::getOption('base') ?: 'http://localhost:8080'), '/');
        $user       = (string) (CLI::getOption('user') ?: '');
        $pass       = (string) (CLI::getOption('pass') ?: '');
        $maks       = max(1, (int) (CLI::getOption('maks') ?: 300));

        if ($user === '' || $pass === '') {
            CLI::error('Wajib --user dan --pass. Perintah ini masuk sebagai pengguna sungguhan.');

            return EXIT_ERROR;
        }

        if (! function_exists('curl_init')) {
            CLI::error('Ekstensi cURL tidak aktif.');

            return EXIT_ERROR;
        }

        $this->cookie = tempnam(sys_get_temp_dir(), 'sisir');

        CLI::write('Sasaran: ' . $this->base . '  sebagai=' . $user, 'yellow');
        CLI::newLine();

        try {
            if (! $this->masuk($user, $pass)) {
                CLI::error('Gagal masuk sebagai ' . $user . '. Periksa --user/--pass.');

                return EXIT_ERROR;
            }

            $this->sisirDaftarRute();
            $this->jelajahTautan($maks);
        } finally {
            if ($this->cookie !== '' && is_file($this->cookie)) {
                unlink($this->cookie);
            }
        }

        $this->laporkan();

        return $this->gagal === 0 ? EXIT_SUCCESS : EXIT_ERROR;
    }

    /* =========================================================
     * LAPIS 1 — DAFTAR RUTE
     * =======================================================*/

    private function sisirDaftarRute(): void
    {
        CLI::write('== Lapis 1: daftar rute GET ==', 'yellow');

        $rute = $this->ruteGet();
        if ($rute === []) {
            CLI::write('  (tidak ada rute GET terdaftar)', 'dark_gray');

            return;
        }

        $diperiksa = 0;
        foreach ($rute as $pola) {
            $jalur = $this->isiPlaceholder($pola);
            if ($jalur === null || preg_match(self::POLA_MUTASI, $jalur)) {
                continue;
            }

            $this->periksa($jalur);
            $diperiksa++;
        }

        CLI::write('  ' . $diperiksa . ' rute diperiksa.', 'dark_gray');
        CLI::newLine();
    }

    /**
     * Seluruh rute GET dari Router, sebagai pola mentah ('adminkab/iku/(.*)').
     *
     * @return list<string>
     */
    private function ruteGet(): array
    {
        $koleksi = service('routes');
        $koleksi->loadRoutes();

        // Kuncinya HURUF BESAR di CodeIgniter 4.6 — `getRoutes('get')`
        // mengembalikan larik kosong tanpa galat apa pun, sehingga lapis ini
        // diam-diam tidak menguji apa-apa kalau ditulis huruf kecil.
        return array_keys($koleksi->getRoutes('GET'));
    }

    /**
     * Ganti placeholder rute dengan contoh yang masuk akal.
     *
     * Angka diisi 1: kalaupun barisnya tidak ada, yang diuji tetap berguna —
     * halaman WAJIB menjawab "tidak ditemukan" dengan rapi, bukan 500. Ruas
     * bebas diisi menurut segmen sebelumnya karena `pk/(:any)` memang
     * mengharap jenis PK, bukan sembarang kata.
     */
    private function isiPlaceholder(string $pola): ?string
    {
        $jalur = '/' . ltrim($pola, '/');

        $contohAny = str_contains($jalur, '/pk/') || str_contains($jalur, 'renaksi_pk')
            || str_contains($jalur, 'monev_pk') ? 'jpt' : 'x';

        // Penggantian DULU, baru pemeriksaan sisa. Router menyimpan rute dalam
        // bentuk yang SUDAH dikompilasi — `(:num)` menjadi `([0-9]+)` — jadi
        // menolak pola ber-`[`/`]`/`+` lebih dulu akan membuang setiap rute
        // ber-id sekaligus. Ketika urutannya masih terbalik, seluruh halaman
        // detail lolos tanpa diuji dan perintah ini tetap melaporkan "GAGAL 0".
        $jalur = str_replace(['(:num)', '([0-9]+)'], '1', $jalur);
        $jalur = str_replace(['(:segment)', '(:alpha)', '(:alphanum)'], $contohAny, $jalur);
        $jalur = str_replace(['(:any)', '(.*)'], $contohAny, $jalur);

        // Sisa regex apa pun berarti polanya di luar bentuk baku; ditebak
        // sembarangan hanya melahirkan temuan palsu, jadi dilewati.
        return preg_match('/[()\[\]\{\}\|\?\+\*]/', $jalur) ? null : $jalur;
    }

    /* =========================================================
     * LAPIS 2 — JELAJAH TAUTAN
     * =======================================================*/

    private function jelajahTautan(int $maks): void
    {
        CLI::write('== Lapis 2: jelajah tautan nyata ==', 'yellow');

        // Benih sengaja beberapa, bukan hanya '/'. Beranda menjawab pengalihan
        // (302) tanpa badan, jadi berangkat dari sana saja membuat penjelajahan
        // berhenti di satu halaman tanpa satu pun tautan ditemukan. Beranda
        // tiap peran dimasukkan semua; yang bukan haknya akan ditolak dengan
        // rapi dan tidak dihitung sebagai temuan.
        $antre = [
            '/', '/profile',
            '/adminkab/dashboard', '/adminopd/dashboard',
            '/bupati/dashboard', '/superadmin/master/user',
        ];
        $pernah = [];
        $jumlah = 0;

        while ($antre !== [] && $jumlah < $maks) {
            $jalur = array_shift($antre);
            $jalur = strtok($jalur, '#');

            if ($jalur === false || isset($pernah[$jalur]) || preg_match(self::POLA_MUTASI, $jalur)) {
                continue;
            }
            $pernah[$jalur] = true;
            $jumlah++;

            $r = $this->periksa($jalur);

            if (! str_contains($r['tipe'], 'html')) {
                continue;
            }

            foreach ($this->tautan($r['isi']) as $t) {
                if (! isset($pernah[$t])) {
                    $antre[] = $t;
                }
            }
        }

        CLI::write('  ' . $jumlah . ' halaman dijelajah.', 'dark_gray');
        CLI::newLine();
    }

    /**
     * Tautan internal dari sebuah halaman, sudah jadi jalur relatif.
     *
     * Pembatas regex sengaja `~`, bukan `#`: `#` juga dipakai di dalam kelas
     * karakter untuk membuang jangkar, dan memakainya sebagai pembatas
     * membuat pola gagal dikompilasi ("Unknown modifier").
     *
     * @return list<string>
     */
    private function tautan(string $html): array
    {
        if (! preg_match_all('~href="([^"#]+)"~', $html, $m)) {
            return [];
        }

        $hasil = [];
        foreach ($m[1] as $h) {
            $h = html_entity_decode($h, ENT_QUOTES, 'UTF-8');

            if (preg_match(self::POLA_ASET, $h)) {
                continue;
            }

            // base_url() merender alamat PRODUKSI walau server yang diuji
            // lokal; yang dipakai hanya bagian jalurnya.
            if (preg_match('~^https?://~', $h)) {
                $bagian = parse_url($h);
                $tuan   = parse_url($this->base);
                if (($bagian['host'] ?? '') !== ($tuan['host'] ?? '')
                    && ! str_contains((string) ($bagian['host'] ?? ''), 'esakip')) {
                    continue;                       // tautan ke luar aplikasi
                }
                $h = ($bagian['path'] ?? '/') . (isset($bagian['query']) ? '?' . $bagian['query'] : '');
            }

            if (! str_starts_with($h, '/')) {
                continue;
            }

            $hasil[] = $h;
        }

        return $hasil;
    }

    /* =========================================================
     * PEMERIKSAAN SATU HALAMAN
     * =======================================================*/

    /** @return array{kode:int, isi:string, tipe:string} */
    private function periksa(string $jalur): array
    {
        $r = $this->minta($jalur);

        $jejak = '';
        if (str_contains($r['tipe'], 'html') || str_contains($r['tipe'], 'json')) {
            if (preg_match(self::POLA_GALAT, $r['isi'], $m)) {
                $jejak = $m[0];
            }
        }

        // 404 yang MEMANG dijawab rapi bukan kegagalan: id contoh pada lapis 1
        // hampir selalu tidak ada, dan menolaknya dengan sopan justru benar.
        $rusak = $r['kode'] >= 500 || ($jejak !== '' && $r['kode'] !== 404);

        if ($rusak) {
            $this->gagal++;
            $this->temuan[] = [
                'jalur' => $jalur,
                'kode'  => $r['kode'],
                'jejak' => $jejak,
                'pesan' => $this->pesanRingkas($r['isi']),
            ];
            CLI::write('  GAGAL  ' . $r['kode'] . '  ' . $jalur, 'red');
        } else {
            $this->lulus++;
        }

        return $r;
    }

    /** Pesan galat dari badan JSON debug CodeIgniter, bila ada. */
    private function pesanRingkas(string $isi): string
    {
        $j = json_decode($isi, true);
        if (is_array($j) && isset($j['message'])) {
            return (string) $j['message']
                . (isset($j['file']) ? '  @ ' . basename((string) $j['file']) . ':' . ($j['line'] ?? '?') : '');
        }

        return trim(preg_replace('/\s+/', ' ', strip_tags(mb_substr($isi, 0, 400))) ?? '');
    }

    private function laporkan(): void
    {
        CLI::newLine();

        if ($this->temuan === []) {
            CLI::write('LULUS ' . $this->lulus . '   GAGAL 0', 'green');

            return;
        }

        CLI::write('== Temuan ==', 'red');
        foreach ($this->temuan as $t) {
            CLI::write('  HTTP ' . $t['kode'] . '  ' . $t['jalur'], 'red');
            if ($t['jejak'] !== '') {
                CLI::write('        jejak : ' . $t['jejak'], 'dark_gray');
            }
            if ($t['pesan'] !== '') {
                CLI::write('        pesan : ' . mb_substr($t['pesan'], 0, 160), 'dark_gray');
            }
        }

        CLI::newLine();
        CLI::write('LULUS ' . $this->lulus . '   GAGAL ' . $this->gagal, 'red');
    }

    /* =========================================================
     * HTTP
     * =======================================================*/

    /** @return array{kode:int, isi:string, tipe:string} */
    private function minta(string $jalur, ?array $post = null): array
    {
        $ch = curl_init($this->base . $jalur);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_COOKIEJAR      => $this->cookie,
            CURLOPT_COOKIEFILE     => $this->cookie,
            CURLOPT_TIMEOUT        => 120,
        ]);

        if ($post !== null) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
        }

        $isi  = (string) curl_exec($ch);
        $kode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $tipe = (string) curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        curl_close($ch);

        return ['kode' => $kode, 'isi' => $isi, 'tipe' => $tipe];
    }

    private function masuk(string $user, string $pass): bool
    {
        $h = $this->minta('/login');

        if (! preg_match('/name="csrf_test_name"[^>]*value="([^"]+)"/', $h['isi'], $m)) {
            return false;
        }

        $this->minta('/login/authenticate', [
            'csrf_test_name' => $m[1],
            'username'       => $user,
            'password'       => $pass,
        ]);

        // Beranda peran mana pun akan menolak bila sesi tidak terbentuk.
        $cek = $this->minta('/profile');

        return $cek['kode'] === 200;
    }
}
