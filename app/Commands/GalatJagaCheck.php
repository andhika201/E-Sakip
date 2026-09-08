<?php

namespace App\Commands;

use App\Exceptions\AturanBisnis;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use CodeIgniter\Database\Exceptions\DatabaseException;
use CodeIgniter\Database\Exceptions\DataException;
use DomainException;
use Exception;
use InvalidArgumentException;
use LogicException;
use PDOException;
use RuntimeException;
use Throwable;
use TypeError;

/**
 * Uji penanganan galat terpusat (§8).
 *
 *   php spark galat:jaga-check
 *
 * =====================================================================
 * APA YANG DIJAGA DI SINI
 *
 * Aplikasi ini melempar ratusan exception yang pesannya MEMANG untuk dibaca
 * pengguna — "Tahun 2028 sudah dipakai revisi lain", "Hanya draft yang bisa
 * ditambahi hasil sync". Kalimat seperti itu harus lewat apa adanya.
 *
 * Yang tidak boleh lewat adalah galat teknis, dan di sinilah jebakannya:
 *
 *   CodeIgniter\Database\Exceptions\DatabaseException
 *     -> CodeIgniter\Exceptions\RuntimeException
 *       -> RuntimeException
 *
 * DatabaseException ADALAH RuntimeException. Penggolongan yang paling wajar —
 * "RuntimeException berarti aturan bisnis" — karena itu justru meloloskan
 * setiap galat SQL ke layar, lengkap dengan nama tabel, nama indeks unik, dan
 * nilai kolomnya:
 *
 *   Duplicate entry '1-2091-2095-2091-1' for key 'iku_revisi.uq_iku_revisi_efektif'
 *
 * Uji di bawah memastikan pembedanya adalah KELAS PERSIS, bukan turunan, dan
 * bahwa yang tidak dikenali diperlakukan sebagai teknis (gagal-tertutup).
 *
 * Perintah ini TIDAK menyentuh basis data sama sekali.
 */
class GalatJagaCheck extends BaseCommand
{
    protected $group       = 'SAKIP';
    protected $name        = 'galat:jaga-check';
    protected $description = 'Uji penggolongan pesan galat: aturan bisnis lewat, galat teknis disembunyikan.';
    protected $usage       = 'galat:jaga-check';

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
        helper('galat');

        $this->ujiHirarki();
        $this->ujiAturanBisnisLewat();
        $this->ujiTeknisDisembunyikan();
        $this->ujiKodeRujukan();
        $this->ujiAwalan();
        $this->ujiTidakAdaSisaBocor();

        CLI::newLine();
        CLI::write('LULUS ' . $this->lulus . '   GAGAL ' . $this->gagal,
            $this->gagal === 0 ? 'green' : 'red');

        return $this->gagal === 0 ? EXIT_SUCCESS : EXIT_ERROR;
    }

    /** Sebab musababnya dinyatakan sebagai uji, bukan hanya sebagai komentar. */
    private function ujiHirarki(): void
    {
        CLI::write('== Jebakan hirarki ==', 'yellow');

        $db = new DatabaseException('uji');

        $this->cek('DatabaseException MEMANG turunan RuntimeException',
            $db instanceof RuntimeException);
        $this->cek('...sehingga instanceof TIDAK bisa dipakai membedakannya',
            $db instanceof RuntimeException && ! galatAturanBisnis($db));

        CLI::newLine();
    }

    private function ujiAturanBisnisLewat(): void
    {
        CLI::write('== Aturan bisnis: pesannya LEWAT apa adanya ==', 'yellow');

        $pesan = 'Tahun 2028 sudah dipakai revisi lain.';

        $aman = [
            'RuntimeException polos'        => new RuntimeException($pesan),
            'InvalidArgumentException polos' => new InvalidArgumentException($pesan),
            'DomainException polos'          => new DomainException($pesan),
            'LogicException polos'           => new LogicException($pesan),
            'Exception polos'                => new Exception($pesan),
            'App\Exceptions\AturanBisnis'    => new AturanBisnis($pesan),
        ];

        foreach ($aman as $nama => $e) {
            $this->cek($nama . ' digolongkan aturan bisnis', galatAturanBisnis($e));
            $this->cek($nama . ' pesannya utuh sampai layar',
                pesanGalat($e, 'uji') === $pesan, pesanGalat($e, 'uji'));
        }

        CLI::newLine();
    }

    private function ujiTeknisDisembunyikan(): void
    {
        CLI::write('== Galat teknis: pesannya DISEMBUNYIKAN ==', 'yellow');

        // Pesan sungguhan dari MySQL: memuat nama tabel, nama indeks unik,
        // dan nilai kolomnya sekaligus.
        $bocor = "Duplicate entry '1-2091-2095-2091-1' for key 'iku_revisi.uq_iku_revisi_efektif'";

        $teknis = [
            'DatabaseException' => new DatabaseException($bocor),
            'DataException'     => new DataException($bocor),
            'PDOException'      => new PDOException($bocor),
            'TypeError'         => new TypeError($bocor),
        ];

        foreach ($teknis as $nama => $e) {
            $this->cek($nama . ' TIDAK digolongkan aturan bisnis',
                ! galatAturanBisnis($e));

            $hasil = pesanGalat($e, 'uji');

            $this->cek($nama . ' pesan aslinya tidak muncul di layar',
                ! str_contains($hasil, 'Duplicate entry'), $hasil);
            $this->cek($nama . ' nama tabel/indeks tidak bocor',
                ! str_contains($hasil, 'iku_revisi')
                && ! str_contains($hasil, 'uq_iku_revisi_efektif'), $hasil);
        }

        // Turunan yang tidak dikenal sama sekali — harus gagal-tertutup.
        $asing = new class ('rahasia skema') extends RuntimeException {};

        $this->cek('turunan RuntimeException yang tak dikenal diperlakukan TEKNIS',
            ! galatAturanBisnis($asing));
        $this->cek('...dan pesannya tidak bocor',
            ! str_contains(pesanGalat($asing, 'uji'), 'rahasia skema'));

        CLI::newLine();
    }

    private function ujiKodeRujukan(): void
    {
        CLI::write('== Kode rujukan ==', 'yellow');

        $e = new DatabaseException('galat teknis');

        $a = pesanGalat($e, 'uji');
        $b = pesanGalat($e, 'uji');

        $this->cek('pesan generik memuat kode ERR-',
            (bool) preg_match('/ERR-[0-9A-F]{6}/', $a), $a);
        $this->cek('kode berbeda tiap kejadian, sehingga bisa ditelusuri satu per satu',
            $a !== $b);
        $this->cek('kodeGalat() berbentuk benar',
            (bool) preg_match('/^ERR-[0-9A-F]{6}$/', kodeGalat()));
        $this->cek('pesan generik menyebut apa yang harus dilakukan pengguna',
            str_contains($a, 'hubungi administrator'), $a);

        CLI::newLine();
    }

    private function ujiAwalan(): void
    {
        CLI::write('== Awalan konteks ==', 'yellow');

        $bisnis = new RuntimeException('Sub rencana aksi belum diisi.');
        $teknis = new DatabaseException('Unknown column x in field list');

        $a = pesanGalatBerawalan($bisnis, 'Rencana aksi gagal disimpan', 'uji');
        $b = pesanGalatBerawalan($teknis, 'Rencana aksi gagal disimpan', 'uji');

        $this->cek('awalan dipertahankan untuk aturan bisnis',
            $a === 'Rencana aksi gagal disimpan: Sub rencana aksi belum diisi.', $a);
        $this->cek('awalan dipertahankan untuk galat teknis', str_starts_with($b,
            'Rencana aksi gagal disimpan: '), $b);
        $this->cek('isi teknisnya tetap tidak bocor',
            ! str_contains($b, 'Unknown column'), $b);

        // Awalan yang sudah berakhiran titik dua tidak boleh jadi "::".
        $c = pesanGalatBerawalan($bisnis, 'Gagal menyimpan:', 'uji');

        $this->cek('titik dua ganda tidak terjadi', ! str_contains($c, '::'), $c);

        CLI::newLine();
    }

    /**
     * Penjaga regresi: tidak ada lagi controller yang mencetak getMessage().
     *
     * Diuji sebagai BERKAS, bukan sebagai perilaku, karena inilah bentuk
     * kekeliruan yang paling mudah kembali — satu blok catch baru yang ditulis
     * mengikuti pola lama di sekitarnya.
     */
    private function ujiTidakAdaSisaBocor(): void
    {
        CLI::write('== Tidak ada sisa pola lama di controller ==', 'yellow');

        $dir = APPPATH . 'Controllers';
        $sisa = [];

        $iter = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir));

        foreach ($iter as $berkas) {
            if (! $berkas->isFile() || $berkas->getExtension() !== 'php') {
                continue;
            }

            // Diperiksa atas SELURUH ISI berkas, bukan baris per baris.
            //
            // Pemeriksaan per-baris meleset pada pemanggilan yang dipecah:
            //
            //     return redirect()->back()->with(
            //         'error',
            //         'Gagal: ' . $th->getMessage()
            //     );
            //
            // Bentuk seperti itu memang ada di proyek ini dan sempat lolos
            // dari versi pertama pemeriksaan ini — penjaga yang buta pada
            // satu bentuk penulisan lebih buruk daripada tidak ada, karena ia
            // memberi rasa aman yang keliru.
            $isi = (string) file_get_contents($berkas->getPathname());

            // Petiknya dicocokkan sebagai "satu karakter apa pun" agar polanya
            // tidak perlu memuat petik tunggal MAUPUN ganda sekaligus.
            $pola = '/with\(\s*.error.\s*,(?:[^;]{0,400}?)getMessage\(\)/s';

            if (preg_match_all($pola, $isi, $cocok, PREG_OFFSET_CAPTURE)) {
                foreach ($cocok[0] as $m) {
                    $nomor = substr_count(substr($isi, 0, $m[1]), "\n") + 1;
                    $sisa[] = str_replace(APPPATH, '', $berkas->getPathname()) . ':' . $nomor;
                }
            }
        }

        $this->cek('nol controller mencetak $e->getMessage() ke layar',
            $sisa === [], implode(', ', $sisa));

        CLI::newLine();
    }
}
