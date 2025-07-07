<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateRenstraTable extends Migration
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
            'target_akhir_tahun_pertama' => [
                'type' => 'TEXT',
                'null' => false,
            ],
            'target_akhir_tahun_kedua' => [
                'type' => 'TEXT',
                'null' => false,
            ],
            'target_akhir_tahun_ketiga' => [
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
        $this->forge->addForeignKey('opd_id', 'opd', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('rpjmd_sasaran_id', 'rpjmd_sasaran', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('renstra');
    }

    public function down()
    {
        $this->forge->dropTable('renstra', true);
        // Optionally, you can also drop the foreign keys if they exist
        // $this->forge->dropForeignKey('renstra', 'opd_id_foreign');
        // $this->forge->dropForeignKey('renstra', 'rpjmd_sasaran_id_foreign');
    }
}
