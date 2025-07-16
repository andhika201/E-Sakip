<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePkProgramTable extends Migration
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
            'pk_id'       => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => false,
            ],
            'program_id'  => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => false,
            ],
            'created_at' => [
                'type'    => 'DATETIME',
                'null'    => true,
            ],
            'updated_at' => [
                'type'    => 'DATETIME', 
                'null'    => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('pk_id', 'pk', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('program_id', 'program_pk', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('pk_program');
    }

    public function down()
    {
        $this->forge->dropTable('pk_program');
    }
}
