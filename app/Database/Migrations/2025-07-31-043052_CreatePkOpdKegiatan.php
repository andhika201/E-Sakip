<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePkOpdKegiatan extends Migration
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
            'pk_opd_id'       => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => false,
            ],
            'kegiatan_id'  => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => false,
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
        $this->forge->addForeignKey('pk_opd_id', 'pk_opd', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('kegiatan_id', 'kegiatan_opd', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('pk_kegiatan');
    }

    public function down()
    {
        $this->forge->dropTable('pk_kegiatan', true);
    }
}
