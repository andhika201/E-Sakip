<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Tabel audit aktivitas pengguna (login, logout, dan semua aksi ubah data).
 * Idempoten: cek keberadaan tabel sebelum membuat.
 */
class CreateActivityLogs extends Migration
{
    public function up()
    {
        if (\Config\Database::connect()->tableExists('activity_logs')) {
            return;
        }

        $this->forge->addField([
            'id'          => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'user_id'     => ['type' => 'INT', 'null' => true],
            'username'    => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'role'        => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'action'      => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'module'      => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'description' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'method'      => ['type' => 'VARCHAR', 'constraint' => 10, 'null' => true],
            'url'         => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'ip_address'  => ['type' => 'VARCHAR', 'constraint' => 45, 'null' => true],
            'user_agent'  => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'created_at'  => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('user_id');
        $this->forge->addKey('action');
        $this->forge->addKey('module');
        $this->forge->addKey('created_at');
        $this->forge->createTable('activity_logs');
    }

    public function down()
    {
        $this->forge->dropTable('activity_logs', true);
    }
}
