<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateKegiatanOpdTable extends Migration
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
            'opd_id'      => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'kegiatan' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'anggaran' => [
                'type'       => 'DECIMAL',
                'constraint' => '15,2',
                'null'      => true,
            ],
            'created_at'  => [
                'type'       => 'DATETIME',
                'null'      => true,
                'default'   => new \CodeIgniter\Database\RawSql('CURRENT_TIMESTAMP'),
            ],
            'updated_at'  => [
                'type'       => 'DATETIME',
                'null'      => true,
                'default'   => new \CodeIgniter\Database\RawSql('CURRENT_TIMESTAMP'),
                'on_update' => new \CodeIgniter\Database\RawSql('CURRENT_TIMESTAMP'),
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('opd_id', 'opd', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('kegiatan_opd');
    }

    public function down()
    {
        $this->forge->dropTable('kegiatan_opd', true);
    }
}
