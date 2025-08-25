<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateRkpdIndikatorSasaranTable extends Migration
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
            'rkpd_sasaran_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => false,
            ],
            'indikator_sasaran' => [
                'type' => 'TEXT',
                'null' => false,
            ],
            'satuan' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
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
        $this->forge->addForeignKey('rkpd_sasaran_id', 'rkpd_sasaran', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('rkpd_indikator_sasaran');
    }

    public function down()
    {
        $this->forge->dropTable('rkpd_indikator_sasaran', true, true);
    }
}
