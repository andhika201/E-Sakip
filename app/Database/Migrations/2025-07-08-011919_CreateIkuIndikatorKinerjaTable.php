<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateIkuIndikatorKinerjaTable extends Migration
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
                'iku_sasaran_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'null' => false,
                ],
                'indikator_kinerja' => [
                    'type' => 'TEXT',
                    'null' => false,
                ],
                'definisi_formulasi' => [
                    'type' => 'TEXT',
                    'null' => false,
                ],
                'satuan' => [
                    'type' => 'VARCHAR',
                    'constraint' => 50,
                    'null' => false,
                ],
                'program_pendukung' => [
                    'type' => 'TEXT',
                    'null' => true,
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
            $this->forge->addForeignKey('iku_sasaran_id', 'iku_sasaran', 'id', 'CASCADE', 'CASCADE');
            $this->forge->createTable('iku_indikator_kinerja');
    }

    public function down()
    {
        $this->forge->dropTable('iku_indikator_kinerja', true, true);
    }
}
