<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateRpjmdMisiTable extends Migration
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
            'misi' => [
                'type' => 'TEXT',
                'null' => false,
            ],
            'tahun_mulai' => [
                'type' => 'YEAR',
                'null' => false,
            ],
            'tahun_akhir' => [
                'type' => 'YEAR',
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
        $this->forge->createTable('rpjmd_misi');
    }

    public function down()
    {
        $this->forge->dropTable('rpjmd_misi');
    }
}
