<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePkBupatiIndikatorSasaranTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'          => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'pk_bup_sasaran_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'indikator' => [
                'type'       => 'TEXT',
                'null'       => false,
            ],
            'target'   => [
                'type'       => 'TEXT',
                'null'       => false,
            ],
            'created_at'   => [
                'type'    => 'DATETIME',
                'null'    => true,
                'default' => new \CodeIgniter\Database\RawSql('CURRENT_TIMESTAMP'),
            ],
            'updated_at'   => [
                'type'    => 'DATETIME',
                'null'    => true,
                'default' => new \CodeIgniter\Database\RawSql('CURRENT_TIMESTAMP'),
                'on_update' => new \CodeIgniter\Database\RawSql('CURRENT_TIMESTAMP'),
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('pk_bup_sasaran_id', 'pk_bupati_sasaran', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('pk_bupati_indikator');
    }

    public function down()
    {
        $this->forge->dropTable('pk_bupati_indikator', true);
    }
}
