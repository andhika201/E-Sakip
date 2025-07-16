<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePkSasaranTable extends Migration
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
            'sasaran'     => [
                'type'       => 'TEXT',
                'null'       => false,
            ],
            'created_at'  => [
                'type'    => 'DATETIME',
                'null'    => true,
            ],
            'updated_at'  => [
                'type'    => 'DATETIME',
                'null'    => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('pk_id', 'pk', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('pk_sasaran');
    }

    public function down()
    {
        $this->forge->dropTable('pk_sasaran');
    }
}
