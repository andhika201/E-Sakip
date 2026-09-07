<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Security extends BaseConfig
{
    /**
     * --------------------------------------------------------------------------
     * CSRF Protection Method
     * --------------------------------------------------------------------------
     *
     * Protection Method for Cross Site Request Forgery protection.
     *
     * @var string 'cookie' or 'session'
     */
    public string $csrfProtection = 'cookie';

    /**
     * --------------------------------------------------------------------------
     * CSRF Token Randomization
     * --------------------------------------------------------------------------
     *
     * Randomize the CSRF Token for added security.
     */
    public bool $tokenRandomize = false;

    /**
     * --------------------------------------------------------------------------
     * CSRF Token Name
     * --------------------------------------------------------------------------
     *
     * Token name for Cross Site Request Forgery protection.
     */
    public string $tokenName = 'csrf_test_name';

    /**
     * --------------------------------------------------------------------------
     * CSRF Header Name
     * --------------------------------------------------------------------------
     *
     * Header name for Cross Site Request Forgery protection.
     */
    public string $headerName = 'X-CSRF-TOKEN';

    /**
     * --------------------------------------------------------------------------
     * CSRF Cookie Name
     * --------------------------------------------------------------------------
     *
     * Cookie name for Cross Site Request Forgery protection.
     */
    public string $cookieName = 'csrf_cookie_name';

    /**
     * --------------------------------------------------------------------------
     * CSRF Expires
     * --------------------------------------------------------------------------
     *
     * Expiration time for Cross Site Request Forgery protection cookie.
     *
     * Defaults to two hours (in seconds).
     */
    public int $expires = 7200;

    /**
     * --------------------------------------------------------------------------
     * CSRF Regenerate
     * --------------------------------------------------------------------------
     *
     * Regenerate CSRF Token on every submission.
     *
     * =====================================================================
     * SENGAJA false — DAN INI KEPUTUSAN, BUKAN KELALAIAN
     *
     * Dengan true, token berganti pada SETIAP request. Halaman yang mengirim
     * lebih dari satu AJAX tanpa dimuat ulang lalu memakai token basi pada
     * kiriman kedua, dan tombolnya mati tanpa pesan yang berarti.
     *
     * Di project ini pola penyegaran token itu TIDAK seragam: ai/index.php
     * memperbarui meta dari `json.csrf` pada respons, tetapi
     * public/assets/js/adminopd/cascading/cascading-ajax.js (2 titik POST)
     * hanya membaca meta sekali dan tidak pernah memperbaruinya. Mengaktifkan
     * CSRF dengan regenerate menyala berarti mematikan tombol-tombol itu.
     *
     * Yang HILANG dengan false hanyalah perlindungan replay dalam satu sesi;
     * perlindungan terhadap permintaan lintas-situs — alasan CSRF ada — tetap
     * penuh, karena penyerang tetap tidak bisa membaca token korban.
     *
     * Untuk mengembalikannya ke true kelak, syaratnya satu: SETIAP pemanggil
     * AJAX harus menyegarkan token dari respons, seperti ai/index.php.
     * =====================================================================
     */
    public bool $regenerate = false;

    /**
     * --------------------------------------------------------------------------
     * CSRF Redirect
     * --------------------------------------------------------------------------
     *
     * Redirect to previous page with error on failure.
     *
     * @see https://codeigniter4.github.io/userguide/libraries/security.html#redirection-on-failure
     */
    public bool $redirect = (ENVIRONMENT === 'production');
}
