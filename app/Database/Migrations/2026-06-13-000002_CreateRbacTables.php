<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * RBAC: tabel roles, permissions, role_permissions.
 *
 * Penting: `roles.name` SENGAJA memakai slug yang sama dengan nilai `users.role`
 * yang sudah ada ('admin', 'admin_kab', 'admin_opd') sehingga AuthFilter berbasis
 * role string tetap berjalan tanpa migrasi data user.
 *
 * Idempoten: aman dijalankan ulang (cek keberadaan tabel & data sebelum aksi).
 */
class CreateRbacTables extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();

        // ---- roles ----
        if (!$db->tableExists('roles')) {
            $this->forge->addField([
                'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
                'name'       => ['type' => 'VARCHAR', 'constraint' => 50],
                'label'      => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
                'is_system'  => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
                'created_at' => ['type' => 'DATETIME', 'null' => true],
                'updated_at' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addUniqueKey('name');
            $this->forge->createTable('roles');
        }

        // ---- permissions ----
        if (!$db->tableExists('permissions')) {
            $this->forge->addField([
                'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
                'name'       => ['type' => 'VARCHAR', 'constraint' => 100],
                'label'      => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
                'grup'       => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
                'created_at' => ['type' => 'DATETIME', 'null' => true],
                'updated_at' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addUniqueKey('name');
            $this->forge->createTable('permissions');
        }

        // ---- role_permissions (pivot) ----
        if (!$db->tableExists('role_permissions')) {
            $this->forge->addField([
                'id'            => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
                'role_id'       => ['type' => 'INT', 'unsigned' => true],
                'permission_id' => ['type' => 'INT', 'unsigned' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addUniqueKey(['role_id', 'permission_id']);
            $this->forge->createTable('role_permissions');
        }

        $this->seed($db);
    }

    protected function seed(\CodeIgniter\Database\BaseConnection $db): void
    {
        $now = date('Y-m-d H:i:s');

        // Roles (name = slug yang dipakai users.role)
        $roles = [
            ['name' => 'admin',     'label' => 'Super Admin',      'is_system' => 1],
            ['name' => 'admin_kab', 'label' => 'Admin Kabupaten',  'is_system' => 1],
            ['name' => 'admin_opd', 'label' => 'Admin OPD',        'is_system' => 1],
        ];
        foreach ($roles as $r) {
            $exists = $db->table('roles')->where('name', $r['name'])->countAllResults();
            if (!$exists) {
                $db->table('roles')->insert($r + ['created_at' => $now, 'updated_at' => $now]);
            }
        }

        // Permissions (master data)
        $perms = [
            ['name' => 'master.access',  'label' => 'Akses Panel Master Data', 'grup' => 'Master Data'],
            ['name' => 'pegawai.manage', 'label' => 'Kelola Pegawai',          'grup' => 'Master Data'],
            ['name' => 'pangkat.manage', 'label' => 'Kelola Pangkat',          'grup' => 'Master Data'],
            ['name' => 'jabatan.manage', 'label' => 'Kelola Jabatan',          'grup' => 'Master Data'],
            ['name' => 'opd.manage',     'label' => 'Kelola OPD',              'grup' => 'Master Data'],
            ['name' => 'user.manage',    'label' => 'Kelola User',             'grup' => 'Master Data'],
            ['name' => 'role.manage',    'label' => 'Kelola Role & Permission', 'grup' => 'Master Data'],
            ['name' => 'satuan.manage',  'label' => 'Kelola Satuan',           'grup' => 'Master Data'],
        ];
        foreach ($perms as $p) {
            $exists = $db->table('permissions')->where('name', $p['name'])->countAllResults();
            if (!$exists) {
                $db->table('permissions')->insert($p + ['created_at' => $now, 'updated_at' => $now]);
            }
        }

        // admin (super admin) -> SEMUA permission
        $adminId = (int) ($db->table('roles')->where('name', 'admin')->get()->getRowArray()['id'] ?? 0);
        if ($adminId) {
            $allPerms = $db->table('permissions')->select('id')->get()->getResultArray();
            foreach ($allPerms as $perm) {
                $pid    = (int) $perm['id'];
                $linked = $db->table('role_permissions')
                    ->where('role_id', $adminId)->where('permission_id', $pid)->countAllResults();
                if (!$linked) {
                    $db->table('role_permissions')->insert(['role_id' => $adminId, 'permission_id' => $pid]);
                }
            }
        }
    }

    public function down()
    {
        $this->forge->dropTable('role_permissions', true);
        $this->forge->dropTable('permissions', true);
        $this->forge->dropTable('roles', true);
    }
}
