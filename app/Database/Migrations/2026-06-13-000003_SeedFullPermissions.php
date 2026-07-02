<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Melengkapi katalog permission agar mencakup seluruh modul aplikasi
 * (Umum, Master Data, Kabupaten, OPD) dan memberi default permission untuk
 * role admin_kab & admin_opd. Role 'admin' (super admin) tetap mendapat semua.
 *
 * Idempoten: insert-if-not-exists; aman dijalankan berulang.
 */
class SeedFullPermissions extends Migration
{
    /** @return array<int, array{0:string,1:string,2:string}> [grup, name, label] */
    public static function catalog(): array
    {
        return [
            // grup, name, label
            ['Umum', 'dashboard.view', 'Lihat Dashboard'],
            ['Umum', 'tentang_kami.manage', 'Kelola Tentang Kami'],

            ['Master Data', 'master.access', 'Akses Panel Master Data'],
            ['Master Data', 'pegawai.manage', 'Kelola Pegawai'],
            ['Master Data', 'pangkat.manage', 'Kelola Pangkat'],
            ['Master Data', 'jabatan.manage', 'Kelola Jabatan'],
            ['Master Data', 'opd.manage', 'Kelola OPD'],
            ['Master Data', 'user.manage', 'Kelola User'],
            ['Master Data', 'role.manage', 'Kelola Role & Permission'],
            ['Master Data', 'satuan.manage', 'Kelola Satuan'],

            ['Kabupaten', 'rpjmd.manage', 'Kelola RPJMD'],
            ['Kabupaten', 'rkpd.manage', 'Kelola RKPD'],
            ['Kabupaten', 'iku_kab.manage', 'Kelola IKU Kabupaten'],
            ['Kabupaten', 'cascading_kab.manage', 'Kelola Cascading/Pohon Kinerja Kabupaten'],
            ['Kabupaten', 'rkt_kab.manage', 'Kelola RKT Kabupaten'],
            ['Kabupaten', 'target_kab.manage', 'Kelola Target & Rencana Aksi Kabupaten'],
            ['Kabupaten', 'monev_kab.manage', 'Kelola Monev Kabupaten'],
            ['Kabupaten', 'lakip_kab.manage', 'Kelola LAKIP Kabupaten'],
            ['Kabupaten', 'program_pk.manage', 'Kelola Program PK'],
            ['Kabupaten', 'pk_bupati.manage', 'Kelola PK Bupati'],

            ['OPD', 'renstra.manage', 'Kelola Renstra'],
            ['OPD', 'rkt_opd.manage', 'Kelola RKT OPD'],
            ['OPD', 'iku_opd.manage', 'Kelola IKU OPD'],
            ['OPD', 'cascading_opd.manage', 'Kelola Cascading OPD'],
            ['OPD', 'target_opd.manage', 'Kelola Target OPD'],
            ['OPD', 'monev_opd.manage', 'Kelola Monev OPD'],
            ['OPD', 'lakip_opd.manage', 'Kelola LAKIP OPD'],
            ['OPD', 'pk_opd.manage', 'Kelola PK OPD (Admin/JPT/Pengawas)'],
        ];
    }

    public static function adminKabDefaults(): array
    {
        return [
            'dashboard.view', 'tentang_kami.manage',
            'rpjmd.manage', 'rkpd.manage', 'iku_kab.manage', 'cascading_kab.manage',
            'rkt_kab.manage', 'target_kab.manage', 'monev_kab.manage', 'lakip_kab.manage',
            'program_pk.manage', 'pk_bupati.manage',
        ];
    }

    public static function adminOpdDefaults(): array
    {
        return [
            'dashboard.view', 'tentang_kami.manage',
            'renstra.manage', 'rkt_opd.manage', 'iku_opd.manage', 'cascading_opd.manage',
            'target_opd.manage', 'monev_opd.manage', 'lakip_opd.manage', 'pk_opd.manage',
        ];
    }

    public function up()
    {
        $db  = \Config\Database::connect();
        $now = date('Y-m-d H:i:s');

        // 1) insert permission yang belum ada
        foreach (self::catalog() as [$grup, $name, $label]) {
            if (!$db->table('permissions')->where('name', $name)->countAllResults()) {
                $db->table('permissions')->insert([
                    'name' => $name, 'label' => $label, 'grup' => $grup,
                    'created_at' => $now, 'updated_at' => $now,
                ]);
            }
        }

        // peta name -> id
        $permId = [];
        foreach ($db->table('permissions')->get()->getResultArray() as $p) {
            $permId[$p['name']] = (int) $p['id'];
        }
        // peta role name -> id
        $roleId = [];
        foreach ($db->table('roles')->get()->getResultArray() as $r) {
            $roleId[$r['name']] = (int) $r['id'];
        }

        $grant = function (int $rid, array $permNames) use ($db, $permId) {
            foreach ($permNames as $pn) {
                $pid = $permId[$pn] ?? null;
                if ($pid && !$db->table('role_permissions')->where('role_id', $rid)->where('permission_id', $pid)->countAllResults()) {
                    $db->table('role_permissions')->insert(['role_id' => $rid, 'permission_id' => $pid]);
                }
            }
        };

        // 2) admin -> SEMUA
        if (isset($roleId['admin'])) {
            $grant($roleId['admin'], array_keys($permId));
        }
        // 3) admin_kab & admin_opd -> default (hanya tambah, tidak menghapus yang sudah ada)
        if (isset($roleId['admin_kab'])) {
            $grant($roleId['admin_kab'], self::adminKabDefaults());
        }
        if (isset($roleId['admin_opd'])) {
            $grant($roleId['admin_opd'], self::adminOpdDefaults());
        }
    }

    public function down()
    {
        // tidak menghapus permission inti master.* (dipakai panel). Sisanya dibiarkan
        // agar tidak merusak penugasan yang mungkin sudah diubah manual.
    }
}
