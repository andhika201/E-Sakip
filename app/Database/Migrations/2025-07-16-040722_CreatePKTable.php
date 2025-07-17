<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePKTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'          => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'opd_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => false,
            ],
            'jenis' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => false,
            ],
            'pihak_1' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => false,
            ],
            'pihak_2' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => false,
            ],
            'tanggal' => [
                'type' => 'DATE',
                'null' => false,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME', 
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('opd_id', 'opd', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('pihak_1', 'pegawai', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('pihak_2', 'pegawai', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('pk');
    }

    public function down()
    {
        $this->forge->dropTable('pk');
    }
}
