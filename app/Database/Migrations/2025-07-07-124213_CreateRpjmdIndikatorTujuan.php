<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateRpjmdIndikatorTujuan extends Migration
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
            'tujuan_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => false,
            ],
            'indikator_tujuan' => [
                'type' => 'TEXT',
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
        $this->forge->addForeignKey('tujuan_id', 'rpjmd_tujuan', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('rpjmd_indikator_tujuan');
    }

    public function down()
    {
        $this->forge->dropTable('rpjmd_indikator_tujuan', true, true);
    }
}
