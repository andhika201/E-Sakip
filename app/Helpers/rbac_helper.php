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
