<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateRpjmdTargetTable extends Migration
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
            'indikator_sasaran_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => false,
            ],
            'tahun' => [
                'type' => 'YEAR',
                'null' => false,
            ],
            'target_tahunan' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
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
        $this->forge->addForeignKey('indikator_sasaran_id', 'rpjmd_indikator_sasaran', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('rpjmd_target');
    }

    public function down()
    {
        $this->forge->dropTable('rpjmd_target');
    }
}
