<?php

use Config\Database;

/**
 * Helper RBAC sederhana berbasis tabel roles/permissions/role_permissions.
 * Role pengguna diambil dari session('role') (slug yang sama dgn roles.name).
 */

if (!function_exists('user_permissions')) {
    /**
     * Daftar nama permission milik role user yang login (di-cache per request).
     *
     * @return string[]
     */
    function user_permissions(): array
    {
        static $cache = null;
        if ($cache !== null) {
            return $cache;
        }

        $role = session()->get('role');
        if (!$role) {
            return $cache = [];
        }

        try {
            $db   = Database::connect();
            $rows = $db->table('role_permissions rp')
                ->select('p.name')
                ->join('roles r', 'r.id = rp.role_id')
                ->join('permissions p', 'p.id = rp.permission_id')
                ->where('r.name', $role)
                ->get()
                ->getResultArray();

            return $cache = array_column($rows, 'name');
        } catch (\Throwable $e) {
            // Tabel RBAC belum ada / error koneksi -> jangan memblok aplikasi
            return $cache = [];
        }
    }
}

if (!function_exists('user_can')) {
    /**
     * True bila user punya permission tertentu.
     * Role 'admin' (super admin) selalu diizinkan.
     */
    function user_can(string $permission): bool
    {
        if (session()->get('role') === 'admin') {
            return true;
        }
        return in_array($permission, user_permissions(), true);
    }
}

if (!function_exists('dashboard_path_by_role')) {
    /**
     * Jalur dashboard untuk sebuah role; null bila role tidak dikenali.
     *
     * Satu-satunya tempat pemetaan role -> URL sesudah login. Dipakai
     * LoginController maupun TwoFactorController — sebelumnya 2FA punya
     * pemetaan sendiri yang tidak mengenal `bupati` & `admin_inspektorat`,
     * sehingga keduanya diarahkan ke halaman yang menolaknya.
     */
    function dashboard_path_by_role(?string $role): ?string
    {
        switch ((string) $role) {
            case 'admin_kab':
            case 'admin':               // superadmin juga ke dashboard kab
            case 'admin_inspektorat':   // inspektorat: view read-only level kabupaten
                return '/adminkab/dashboard';
            case 'admin_opd':
            case 'admin_kecamatan':     // kecamatan pakai modul & dashboard OPD
                return '/adminopd/dashboard';
            case 'bupati':              // dashboard eksekutif read-only, area rute sendiri
                return '/bupati/dashboard';
            default:
                return null;
        }
    }
}

if (!function_exists('mulai_sesi_login')) {
    /**
     * Isi sesi sesudah kredensial (dan 2FA bila ada) terbukti sah.
     *
     * ID sesi DIGANTI (regenerate) supaya id yang sudah ada sebelum login —
     * yang bisa saja ditanam pihak lain lewat session fixation — tidak
     * menjadi sesi terautentikasi. Datanya dipertahankan; flashdata login
     * tetap sampai.
     *
     * @param array<string,mixed> $user baris tabel users
     */
    function mulai_sesi_login(array $user): void
    {
        $session = session();
        $session->regenerate();
        $session->set([
            'user_id'    => $user['user_id'],
            'username'   => $user['username'],
            'role'       => $user['role'],
            'opd_id'     => $user['opd_id'] ?? null,
            'isLoggedIn' => true,
        ]);
    }
}