<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateProgramPkTable extends Migration
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
            'program_kegiatan'        => [
                'type'       => 'TEXT',
                'null'       => false,
            ],
            'anggaran'         => [
                'type'       => 'DECIMAL',
                'constraint' => '15,2',
                'null'       => false,
                'default'    => 0,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME', 
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->createTable('program_pk');
    }

    public function down()
    {
        $this->forge->dropTable('program_pk');
    }
}
