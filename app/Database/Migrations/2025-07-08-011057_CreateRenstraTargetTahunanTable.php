<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateRenstraTargetTahunanTable extends Migration
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
            'renstra_indikator_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => false,
            ],
            'tahun' => [
                'type' => 'YEAR',
                'null' => false,
            ],
            'target' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
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
        $this->forge->addForeignKey('renstra_indikator_id', 'renstra_indikator_sasaran', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('renstra_target');
    }

    public function down()
    {
        $this->forge->dropTable('renstra_target', true, true);
    }
}
