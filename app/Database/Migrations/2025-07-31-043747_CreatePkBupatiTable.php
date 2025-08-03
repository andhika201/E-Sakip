<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePkBupatiTable extends Migration
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
            'rpjmd_misi_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'nama'       => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'tanggal'   => [
                'type'       => 'DATE',
                'null'       => true,
            ],
            'created_at' => [
                'type'    => 'DATETIME',
                'null'    => true,
                'default' => new \CodeIgniter\Database\RawSql('CURRENT_TIMESTAMP'),
            ],
            'updated_at' => [
                'type'    => 'DATETIME',
                'null'    => true,
                'default' => new \CodeIgniter\Database\RawSql('CURRENT_TIMESTAMP'),
                'on_update' => new \CodeIgniter\Database\RawSql('CURRENT_TIMESTAMP'),
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('misi_rpjmd_id', 'rpjmd_misi', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('pk_bupati');
    }

    public function down()
    {
        $this->forge->dropTable('pk_bupati', true);
    }
}
