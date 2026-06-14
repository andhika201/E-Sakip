<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Mengganti permission berbasis `.manage` menjadi CRUD per modul
 * (<modul>.view / .create / .update / .delete) untuk SEMUA modul
 * (Master Data, Kabupaten, OPD) + permission khusus (dashboard.view,
 * master.access, tentang_kami.view/update). Default role diisi ulang.
 *
 * Idempoten.
 */
class ExpandPermissionsCrud extends Migration
{
    /** @return array<int,array{0:string,1:string,2:string}> [key,label,grup] */
    public static function modules(): array
    {
        return [
            ['pegawai', 'Pegawai', 'Master Data'],
            ['pangkat', 'Pangkat', 'Master Data'],
            ['jabatan', 'Jabatan', 'Master Data'],
            ['opd', 'OPD', 'Master Data'],
            ['user', 'User', 'Master Data'],
            ['role', 'Role', 'Master Data'],
            ['satuan', 'Satuan', 'Master Data'],
            ['rpjmd', 'RPJMD', 'Kabupaten'],
            ['rkpd', 'RKPD', 'Kabupaten'],
            ['iku_kab', 'IKU Kabupaten', 'Kabupaten'],
            ['cascading_kab', 'Cascading Kabupaten', 'Kabupaten'],
            ['rkt_kab', 'RKT Kabupaten', 'Kabupaten'],
            ['target_kab', 'Target Kabupaten', 'Kabupaten'],
            ['monev_kab', 'Monev Kabupaten', 'Kabupaten'],
            ['lakip_kab', 'LAKIP Kabupaten', 'Kabupaten'],
            ['program_pk', 'Program PK', 'Kabupaten'],
            ['pk_bupati', 'PK Bupati', 'Kabupaten'],
            ['renstra', 'Renstra', 'OPD'],
            ['rkt_opd', 'RKT OPD', 'OPD'],
            ['iku_opd', 'IKU OPD', 'OPD'],
            ['cascading_opd', 'Cascading OPD', 'OPD'],
            ['target_opd', 'Target OPD', 'OPD'],
            ['monev_opd', 'Monev OPD', 'OPD'],
            ['lakip_opd', 'LAKIP OPD', 'OPD'],
            ['pk_opd', 'PK OPD', 'OPD'],
        ];
    }

    /** @return array<string,string> action_key => label */
    public static function actions(): array
    {
        return ['view' => 'Lihat', 'create' => 'Tambah', 'update' => 'Ubah', 'delete' => 'Hapus'];
    }

    public function up()
    {
        $db  = \Config\Database::connect();
        $now = date('Y-m-d H:i:s');

        // 1) buang permission .manage lama (diganti CRUD)
        $db->query("DELETE rp FROM role_permissions rp JOIN permissions p ON p.id = rp.permission_id WHERE p.name LIKE '%.manage'");
        $db->query("DELETE FROM permissions WHERE name LIKE '%.manage'");

        // 2) permission khusus
        $special = [
            ['dashboard.view', 'Lihat Dashboard', 'Umum'],
            ['master.access', 'Akses Panel Master Data', 'Master Data'],
            ['tentang_kami.view', 'Tentang Kami - Lihat', 'Umum'],
            ['tentang_kami.update', 'Tentang Kami - Ubah', 'Umum'],
        ];
        foreach ($special as [$name, $label, $grup]) {
            $this->insertPerm($db, $name, $label, $grup, $now);
        }

        // 3) CRUD untuk semua modul
        foreach (self::modules() as [$mk, $ml, $mg]) {
            foreach (self::actions() as $ak => $al) {
                $this->insertPerm($db, "{$mk}.{$ak}", "{$ml} - {$al}", $mg, $now);
            }
        }

        // 4) default role_permissions
        $this->grantAdmin($db);
        $this->grantByGroup($db, 'admin_kab', 'Kabupaten');
        $this->grantByGroup($db, 'admin_opd', 'OPD');
    }

    private function insertPerm($db, string $name, string $label, string $grup, string $now): void
    {
        if (!$db->table('permissions')->where('name', $name)->countAllResults()) {
            $db->table('permissions')->insert([
                'name' => $name, 'label' => $label, 'grup' => $grup,
                'created_at' => $now, 'updated_at' => $now,
            ]);
        }
    }

    private function grantAdmin($db): void
    {
        $rid = (int) ($db->table('roles')->where('name', 'admin')->get()->getRowArray()['id'] ?? 0);
        if (!$rid) { return; }
        foreach ($db->table('permissions')->select('id')->get()->getResultArray() as $p) {
            $this->link($db, $rid, (int) $p['id']);
        }
    }

    private function grantByGroup($db, string $roleName, string $grup): void
    {
        $rid = (int) ($db->table('roles')->where('name', $roleName)->get()->getRowArray()['id'] ?? 0);
        if (!$rid) { return; }
        $perms = $db->table('permissions')
            ->select('id')
            ->groupStart()
                ->where('grup', $grup)
                ->orWhereIn('name', ['dashboard.view', 'tentang_kami.view', 'tentang_kami.update'])
            ->groupEnd()
            ->get()->getResultArray();
        foreach ($perms as $p) {
            $this->link($db, $rid, (int) $p['id']);
        }
    }

    private function link($db, int $roleId, int $permId): void
    {
        $exists = $db->table('role_permissions')->where('role_id', $roleId)->where('permission_id', $permId)->countAllResults();
        if (!$exists) {
            $db->table('role_permissions')->insert(['role_id' => $roleId, 'permission_id' => $permId]);
        }
    }

    public function down()
    {
        // tidak diturunkan otomatis (non-destruktif).
    }
}
