<?php

/**
 * Penanganan galat terpusat (§8).
 *
 * =====================================================================
 * MASALAH YANG DIPECAHKAN
 *
 * Di 17 controller ada 79 tempat yang menuliskan `$e->getMessage()` langsung
 * ke layar, hampir semuanya dari blok `catch (Throwable $e)`. Menyapunya rata
 * menjadi pesan generik akan MERUSAK: sebagian besar `throw` di proyek ini
 * membawa kalimat Indonesia yang memang ditulis untuk pengguna, dan justru
 * itulah yang menolong mereka memperbaiki kesalahannya.
 *
 * Yang berbahaya adalah galat TEKNIS yang lewat di jalur yang sama.
 *
 * =====================================================================
 * JEBAKANNYA: DatabaseException ITU RuntimeException
 *
 *   CodeIgniter\Database\Exceptions\DatabaseException
 *     -> CodeIgniter\Exceptions\RuntimeException
 *       -> RuntimeException
 *
 * Jadi penggolongan yang paling wajar — "RuntimeException berarti aturan
 * bisnis, aman ditampilkan" — justru meloloskan SETIAP galat SQL. Pesannya
 * berbentuk seperti ini:
 *
 *   Duplicate entry '1-2091-2095-2091-1' for key 'iku_revisi.uq_iku_revisi_efektif'
 *
 * yaitu nama tabel, nama indeks unik, dan nilai kolom sekaligus — peta skema
 * yang diberikan cuma-cuma kepada siapa pun yang bisa memicu galatnya.
 *
 * =====================================================================
 * CARA MENGGOLONGKAN: KELAS PERSIS, BUKAN TURUNAN
 *
 * Kode aplikasi ini melempar kelas SPL POLOS — `new RuntimeException(...)`,
 * `new InvalidArgumentException(...)`. Kerangka kerja melempar TURUNANnya.
 * Karena itu yang diperiksa adalah kelas persisnya (`get_class() === ...`),
 * bukan `instanceof`. Turunan apa pun — dari CodeIgniter, PDO, atau PHP
 * sendiri — otomatis dianggap teknis.
 *
 * Ditambah satu izin eksplisit: apa pun di namespace `App\Exceptions\`, yang
 * memang ditulis proyek ini untuk dibaca pengguna.
 *
 * Gagal-tertutup: kelas yang tidak dikenali diperlakukan sebagai teknis.
 */

use App\Exceptions\AturanBisnis;

if (! function_exists('galatAturanBisnis')) {
    /**
     * Apakah galat ini memang ditulis untuk dibaca pengguna?
     *
     * @see galat_helper.php — catatan "KELAS PERSIS, BUKAN TURUNAN"
     */
    function galatAturanBisnis(Throwable $e): bool
    {
        $kelas = $e::class;

        // Ditulis proyek ini sendiri, memang untuk layar.
        if (str_starts_with($kelas, 'App\\Exceptions\\')) {
            return true;
        }

        // Kelas SPL POLOS — bukan turunannya. Turunan berarti kerangka kerja.
        return in_array($kelas, [
            AturanBisnis::class,
            RuntimeException::class,
            InvalidArgumentException::class,
            DomainException::class,
            LogicException::class,
            OutOfRangeException::class,
            UnexpectedValueException::class,
            Exception::class,
        ], true);
    }
}

if (! function_exists('kodeGalat')) {
    /**
     * Kode rujukan pendek yang menautkan layar pengguna dengan baris log.
     *
     * Sengaja pendek dan mudah didikte lewat telepon — pengguna akan
     * membacakannya, bukan menyalin-tempelnya.
     */
    function kodeGalat(): string
    {
        return 'ERR-' . strtoupper(bin2hex(random_bytes(3)));
    }
}

if (! function_exists('catatGalat')) {
    /**
     * Catat galat teknis LENGKAP ke log server, kembalikan kode rujukannya.
     *
     * Yang dicatat mengikuti §36: siapa, berperan apa, dari OPD mana, di rute
     * mana, sedang mengerjakan apa, beserta pesan dan jejak tumpukannya.
     *
     * Yang TIDAK pernah dicatat: kata sandi, token, dan isi $_POST mentah —
     * form di aplikasi ini memuat data yang tidak perlu ikut tersalin ke
     * berkas log yang dibaca banyak orang.
     *
     * @param array<string,mixed> $konteks keterangan aman, mis. ['revisi_id' => 12]
     */
    function catatGalat(Throwable $e, string $operasi, array $konteks = []): string
    {
        $kode = kodeGalat();

        $sesi = function_exists('session') ? session() : null;
        $req  = function_exists('service') ? service('request') : null;

        $baris = [
            'kode'    => $kode,
            'operasi' => $operasi,
            'user_id' => $sesi?->get('user_id'),
            'role'    => $sesi?->get('role'),
            'opd_id'  => $sesi?->get('opd_id'),
            'rute'    => $req && method_exists($req, 'getUri') ? (string) $req->getUri() : null,
            'metode'  => $req && method_exists($req, 'getMethod') ? $req->getMethod() : null,
            'kelas'   => $e::class,
            'pesan'   => $e->getMessage(),
            'berkas'  => $e->getFile() . ':' . $e->getLine(),
        ];

        foreach ($konteks as $k => $v) {
            // Hanya nilai sederhana; apa pun yang lain bisa membawa serta
            // objek besar atau data yang tidak dimaksudkan masuk log.
            if (is_scalar($v) || $v === null) {
                $baris['ctx_' . $k] = $v;
            }
        }

        $ringkas = [];

        foreach ($baris as $k => $v) {
            $ringkas[] = $k . '=' . ($v === null ? '-' : (string) $v);
        }

        log_message('critical', '[GALAT] ' . implode(' | ', $ringkas));
        log_message('critical', '[GALAT ' . $kode . '] jejak: ' . $e->getTraceAsString());

        return $kode;
    }
}

if (! function_exists('pesanGalat')) {
    /**
     * Pesan yang AMAN ditampilkan untuk sebuah galat.
     *
     * Aturan bisnis diteruskan apa adanya — itu memang kalimat untuk pengguna.
     * Galat teknis dicatat lengkap ke log, lalu diganti pesan generik yang
     * membawa kode rujukan supaya keluhan pengguna bisa ditelusuri ke baris
     * log yang tepat tanpa menebak.
     *
     * @param string              $operasi apa yang sedang dikerjakan, untuk log
     * @param array<string,mixed> $konteks keterangan aman tambahan
     */
    function pesanGalat(Throwable $e, string $operasi = 'operasi', array $konteks = []): string
    {
        if (galatAturanBisnis($e)) {
            return $e->getMessage();
        }

        $kode = catatGalat($e, $operasi, $konteks);

        return 'Data gagal diproses. Silakan coba kembali atau hubungi administrator. '
            . 'Kode referensi: ' . $kode;
    }
}

if (! function_exists('pesanGalatBerawalan')) {
    /**
     * Seperti pesanGalat(), dengan awalan yang menyebut konteksnya.
     *
     * Dipakai menggantikan pola lama `'Gagal menyimpan: ' . $e->getMessage()`.
     * Awalannya dipertahankan karena ia menerangkan APA yang gagal — bagian
     * yang tetap berguna dan tidak pernah membocorkan apa pun.
     */
    function pesanGalatBerawalan(
        Throwable $e,
        string $awalan,
        string $operasi = 'operasi',
        array $konteks = []
    ): string {
        $awalan = rtrim(trim($awalan), ':');

        return $awalan . ': ' . pesanGalat($e, $operasi, $konteks);
    }
}
