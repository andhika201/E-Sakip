<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateRenstraSasaranTable extends Migration
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
            'opd_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => false,
            ],
            'rpjmd_sasaran_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => false,
            ],
            'indikator_sasaran' => [
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
        $this->forge->addForeignKey('opd_id', 'opd', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('rpjmd_sasaran_id', 'rpjmd_sasaran', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('renstra_sasaran');
    }

    public function down()
    {
        $this->forge->dropTable('renstra_sasaran');
    }
}
