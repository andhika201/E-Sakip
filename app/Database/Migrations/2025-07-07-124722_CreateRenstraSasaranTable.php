<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class CreateRenstraSasaranTable extends Migration
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
            'sasaran' => [
                'type' => 'TEXT',
                'null' => false,
            ],
            'tahun_mulai' => [
                'type' => 'INT',
                'constraint' => 4,
                'null' => false,
            ],
            'tahun_akhir' => [
                'type' => 'INT',
                'constraint' => 4,
                'null' => false,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'default' => new RawSql('CURRENT_TIMESTAMP'),
            ],
            'updated_at' => [
                'type' => new RawSql("DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP"),
            ],
        ]);


        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('opd_id', 'opd', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('rpjmd_sasaran_id', 'rpjmd_sasaran', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('renstra_sasaran');
    }

    public function down()
    {
        $this->forge->dropTable('renstra_sasaran', true, true);
    }
}
