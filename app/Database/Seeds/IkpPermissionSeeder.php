<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Izin modul Kinerja Prioritas (IKP) dan Pemilik Kinerja (SAKIP sampai pelaksana).
 *
 *   php spark db:seed IkpPermissionSeeder
 *
 * SUMBER TUNGGAL definisi izin; migrasi 2026-09-26-000002_SeedIkpPermissions
 * memanggil terapkan()/lepas() di sini. Kembaran SQL: db/update_2026-09-26_ikp_kinerja.sql.
 *
 * Sifat: IDEMPOTEN & ADDITIF — hanya menyisipkan yang belum ada; tidak mencabut
 * izin lain; tidak membuat role baru (role yang tidak ada di instalasi ini dilewati).
 */
class IkpPermissionSeeder extends Seeder
{
    /** @return array<int, array{0:string,1:string,2:string}> [grup, name, label] */
    public static function catalog(): array
    {
        $crud = static fn (string $m, string $label, string $grup) => [
            [$grup, "$m.view",   "$label - Lihat"],
            [$grup, "$m.create", "$label - Tambah"],
            [$grup, "$m.update", "$label - Ubah"],
            [$grup, "$m.delete", "$label - Hapus"],
        ];

        return array_merge(
            $crud('ikp_opd', 'Kinerja Prioritas (IKP) OPD', 'OPD'),
            [
                ['OPD', 'pemilik_kinerja.view',   'Pemilik Kinerja (sampai Pelaksana) - Lihat'],
                ['OPD', 'pemilik_kinerja.update', 'Pemilik Kinerja (sampai Pelaksana) - Ubah'],
            ],
            $crud('ikp_kab', 'Kinerja Prioritas (IKP) Kabupaten', 'Kabupaten'),
            [['Bupati', 'ikp_bupati_monitoring.view', 'Monitoring Kinerja Prioritas (IKP) - Lihat']]
        );
    }

    /** @return array<string, string[]> role => izin (additif) */
    public static function roleDefaults(): array
    {
        $opd = ['ikp_opd.view', 'ikp_opd.create', 'ikp_opd.update', 'ikp_opd.delete',
                'pemilik_kinerja.view', 'pemilik_kinerja.update'];

        return [
            'admin_opd'         => $opd,
            'admin_kecamatan'   => $opd,
            'admin_kab'         => ['ikp_kab.view', 'ikp_kab.create', 'ikp_kab.update', 'ikp_kab.delete',
                                    'ikp_opd.view', 'pemilik_kinerja.view'],
            'admin_inspektorat' => ['ikp_kab.view', 'ikp_opd.view', 'pemilik_kinerja.view'],
            'bupati'            => ['ikp_bupati_monitoring.view'],
            // 'admin' tidak perlu: user_can() selalu true untuk super admin.
        ];
    }

    private static function siap($db): bool
    {
        foreach (['roles', 'permissions', 'role_permissions'] as $t) {
            if (! $db->tableExists($t)) {
                return false;
            }
        }

        return true;
    }

    public static function terapkan(): bool
    {
        $db  = \Config\Database::connect();
        $now = date('Y-m-d H:i:s');
        if (! self::siap($db)) {
            return false;
        }

        foreach (self::catalog() as [$grup, $name, $label]) {
            if ($db->table('permissions')->where('name', $name)->countAllResults() === 0) {
                $db->table('permissions')->insert([
                    'name' => $name, 'label' => $label, 'grup' => $grup,
                    'created_at' => $now, 'updated_at' => $now,
                ]);
            }
        }

        foreach (self::roleDefaults() as $role => $names) {
            $r = $db->table('roles')->select('id')->where('name', $role)->get()->getRowArray();
            if (! $r) {
                continue; // role tidak ada di instalasi ini: lewati, jangan dibuat
            }
            foreach ($names as $n) {
                $p = $db->table('permissions')->select('id')->where('name', $n)->get()->getRowArray();
                if (! $p) {
                    continue;
                }
                $ada = $db->table('role_permissions')
                    ->where(['role_id' => (int) $r['id'], 'permission_id' => (int) $p['id']])
                    ->countAllResults();
                if ($ada === 0) {
                    $db->table('role_permissions')->insert([
                        'role_id' => (int) $r['id'], 'permission_id' => (int) $p['id'],
                    ]);
                }
            }
        }

        return true;
    }

    /** Lepas hanya yang dipasang terapkan(). */
    public static function lepas(): void
    {
        $db = \Config\Database::connect();
        if (! self::siap($db)) {
            return;
        }
        $names = array_column(self::catalog(), 1);
        $ids   = array_column($db->table('permissions')->select('id')->whereIn('name', $names)->get()->getResultArray(), 'id');
        if ($ids !== []) {
            $db->table('role_permissions')->whereIn('permission_id', $ids)->delete();
            $db->table('permissions')->whereIn('id', $ids)->delete();
        }
    }

    public function run()
    {
        echo self::terapkan() ? "Izin IKP & Pemilik Kinerja siap.\n" : "Tabel RBAC belum ada.\n";
    }
}
