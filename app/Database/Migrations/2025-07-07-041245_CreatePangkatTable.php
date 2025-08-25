<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePangkatTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'nama_pangkat' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => false,
            ],
            'golongan' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => false,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'default' => new \CodeIgniter\Database\RawSql('CURRENT_TIMESTAMP'),
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'default' => new \CodeIgniter\Database\RawSql('CURRENT_TIMESTAMP'),
                'on_update' => new \CodeIgniter\Database\RawSql('CURRENT_TIMESTAMP'),
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->createTable('pangkat');
    }

    public function down()
    {
        $this->forge->dropTable('pangkat', true, true);
    }
}
