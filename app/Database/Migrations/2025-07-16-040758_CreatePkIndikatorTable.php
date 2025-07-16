<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePkIndikatorTable extends Migration
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
            'pk_sasaran_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => false,
            ],
            'indikator'    => [
                'type'       => 'TEXT',
                'null'       => false,
            ],
            'target'       => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
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
        $this->forge->addForeignKey('pk_sasaran_id', 'pk_sasaran', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('pk_indikator');
    }

    public function down()
    {
        $this->forge->dropTable('pk_indikator');
    }
}
