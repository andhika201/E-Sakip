<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Role & permission read-only untuk BUPATI.
 *
 *   php spark db:seed RoleBupatiSeeder
 *
 * Kelas ini adalah SUMBER TUNGGAL definisi role bupati; migrasi
 * 2026-07-30-000001_SeedRoleBupati memanggil terapkan()/lepas() di sini
 * (berkas migrasi CI4 tidak bisa di-autoload, sedangkan seeder bisa).
 * Dengan begitu hasilnya identik lewat jalur `migrate` maupun `db:seed`.
 *
 * Sifat: IDEMPOTEN & ADDITIVE.
 *   - tidak menghapus / mengubah role existing;
 *   - TIDAK menyentuh tabel `users`: tidak membuat akun Bupati, tidak mengubah
 *     password siapa pun. Akun dibuat Super Admin lewat Master Data > User
 *     (opsi role "Bupati" muncul otomatis dari tabel roles), sehingga tidak ada
 *     kredensial bawaan yang tertanam di kode.
 *
 * Catatan struktur (hasil pemeriksaan DB berjalan, bukan asumsi):
 *   - `users`.`role` = VARCHAR(50) -> TIDAK ada enum yang perlu diubah;
 *   - master role di `roles` (name, label, is_system);
 *   - permission granular di `permissions` (name, label, grup);
 *   - pemetaan di `role_permissions` (role_id, permission_id) UNIQUE.
 */
class RoleBupatiSeeder extends Seeder
{
    public const ROLE  = 'bupati';
    public const LABEL = 'Bupati';

    /**
     * Permission khusus role Bupati — semuanya view-only.
     *
     * @return array<int, array{0:string,1:string,2:string}> [grup, name, label]
     */
    public static function catalog(): array
    {
        return [
            ['Bupati', 'dashboard_bupati.view',         'Dashboard Eksekutif Bupati - Lihat'],
            ['Bupati', 'pk_bupati_monitoring.view',     'Monitoring Perjanjian Kinerja - Lihat'],
            ['Bupati', 'target_bupati_monitoring.view', 'Monitoring Target & Rencana Aksi - Lihat'],
            ['Bupati', 'monev_bupati_monitoring.view',  'Monitoring MONEV - Lihat'],
            ['Bupati', 'lakip_bupati_monitoring.view',  'Monitoring LAKIP - Lihat'],
        ];
    }

    /**
     * Izin yang diberikan ke role bupati.
     *
     * `dashboard.view` ikut diberikan supaya entri Dashboard pada sidebar
     * terpadu (templates/admin_menu.php) muncul — BUKAN untuk membuka
     * /adminkab/dashboard, sebab grup rute itu dijaga AuthFilter tersendiri
     * (auth:admin_kab,admin,admin_inspektorat).
     *
     * @return string[]
     */
    public static function roleDefaults(): array
    {
        return array_merge(['dashboard.view'], array_column(self::catalog(), 1));
    }

    /** Tabel RBAC tersedia? (instalasi lama bisa belum punya). */
    private static function siap($db): bool
    {
        foreach (['roles', 'permissions', 'role_permissions'] as $t) {
            if (!$db->tableExists($t)) {
                return false;
            }
        }

        return true;
    }

    /** Pasang role + permission + pemetaannya. Aman dijalankan berulang. */
    public static function terapkan(): bool
    {
        $db  = \Config\Database::connect();
        $now = date('Y-m-d H:i:s');

        if (!self::siap($db)) {
            return false;
        }

        // 1) master role
        $role = $db->table('roles')->where('name', self::ROLE)->get()->getRowArray();
        if (!$role) {
            $db->table('roles')->insert([
                'name'       => self::ROLE,
                'label'      => self::LABEL,
                'is_system'  => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $roleId = (int) $db->insertID();
        } else {
            $roleId = (int) $role['id'];
            $db->table('roles')->where('id', $roleId)->update([
                'label'      => ($role['label'] === null || $role['label'] === '') ? self::LABEL : $role['label'],
                'is_system'  => 1,
                'updated_at' => $now,
            ]);
        }

        // 2) katalog permission read-only
        foreach (self::catalog() as [$grup, $name, $label]) {
            if ($db->table('permissions')->where('name', $name)->countAllResults() === 0) {
                $db->table('permissions')->insert([
                    'name'       => $name,
                    'label'      => $label,
                    'grup'       => $grup,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        // 3) pemetaan role -> permission (insert-if-not-exists)
        foreach (self::roleDefaults() as $name) {
            $perm = $db->table('permissions')->select('id')->where('name', $name)->get()->getRowArray();
            if (!$perm) {
                continue;
            }
            $sudah = $db->table('role_permissions')
                ->where('role_id', $roleId)
                ->where('permission_id', (int) $perm['id'])
                ->countAllResults();
            if ($sudah === 0) {
                $db->table('role_permissions')->insert([
                    'role_id'       => $roleId,
                    'permission_id' => (int) $perm['id'],
                ]);
            }
        }

        return true;
    }

    /**
     * Rollback: lepas HANYA apa yang dipasang terapkan().
     * Role tidak dihapus bila masih dipakai user — rollback tidak boleh
     * membuat akun kehilangan role-nya.
     */
    public static function lepas(): void
    {
        $db = \Config\Database::connect();
        if (!self::siap($db)) {
            return;
        }

        $role = $db->table('roles')->where('name', self::ROLE)->get()->getRowArray();
        if (!$role) {
            return;
        }
        $roleId = (int) $role['id'];

        $ids = array_column(
            $db->table('permissions')->select('id')->whereIn('name', self::roleDefaults())->get()->getResultArray(),
            'id'
        );
        if ($ids !== []) {
            $db->table('role_permissions')->where('role_id', $roleId)->whereIn('permission_id', $ids)->delete();
        }

        // Permission khusus Bupati dihapus hanya bila tidak lagi dipakai role lain.
        foreach (array_column(self::catalog(), 1) as $name) {
            $perm = $db->table('permissions')->select('id')->where('name', $name)->get()->getRowArray();
            if (!$perm) {
                continue;
            }
            if ($db->table('role_permissions')->where('permission_id', (int) $perm['id'])->countAllResults() === 0) {
                $db->table('permissions')->where('id', (int) $perm['id'])->delete();
            }
        }

        $terpakai = $db->tableExists('users')
            ? $db->table('users')->where('role', self::ROLE)->countAllResults()
            : 0;
        if ($terpakai === 0) {
            $db->table('roles')->where('id', $roleId)->delete();
        }
    }

    public function run()
    {
        if (!self::terapkan()) {
            echo "Tabel RBAC (roles/permissions/role_permissions) belum ada — jalankan migrasi RBAC lebih dulu.\n";

            return;
        }

        $jumlah = $this->db->table('role_permissions rp')
            ->join('roles r', 'r.id = rp.role_id')
            ->where('r.name', self::ROLE)
            ->countAllResults();

        echo "Role '" . self::ROLE . "' siap dengan {$jumlah} permission (read-only).\n";
        echo "Buat akun Bupati lewat Master Data > User (Super Admin), pilih role \"" . self::LABEL . "\".\n";
    }
}
