<?php

/**
 * Helper Pengaturan Aplikasi (app_settings).
 * Membaca konfigurasi web (nama, instansi, logo, favicon, SEO, dll) dari DB
 * dengan cache per-request. Defensif: jika tabel belum ada, kembalikan default.
 */

if (!function_exists('settings_all')) {
    function settings_all(): array
    {
        static $cache = null;
        if ($cache !== null) {
            return $cache;
        }
        $cache = [];
        try {
            $db = \Config\Database::connect();
            if ($db->tableExists('app_settings')) {
                foreach ($db->table('app_settings')->get()->getResultArray() as $row) {
                    $cache[$row['skey']] = $row['svalue'];
                }
            }
        } catch (\Throwable $e) {
            $cache = [];
        }
        return $cache;
    }
}

if (!function_exists('setting')) {
    /**
     * Ambil satu nilai pengaturan.
     */
    function setting(string $key, string $default = ''): string
    {
        $all = settings_all();
        $val = $all[$key] ?? null;
        return ($val === null || $val === '') ? $default : $val;
    }
}

if (!function_exists('setting_asset')) {
    /**
     * URL aset dari pengaturan (logo/favicon). Kembalikan base_url(path) bila ada.
     */
    function setting_asset(string $key, string $default = ''): string
    {
        $path = setting($key, $default);
        return $path ? base_url($path) : '';
    }
}
