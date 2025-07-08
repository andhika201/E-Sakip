<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateIkuTargetTahunanTable extends Migration
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
            'iku_indikator_id' => [
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
        $this->forge->addForeignKey('iku_indikator_id', 'iku_indikator_kinerja', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('iku_target_tahunan');
    }

    public function down()
    {
        $this->forge->dropTable('iku_target_tahunan');
    }
}
