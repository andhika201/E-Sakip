<?php
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePkMisiTable extends Migration
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
            'pk_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => false,
            ],
            'rpjmd_misi_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => false,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('pk_id', 'pk', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('rpjmd_misi_id', 'rpjmd_misi', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('pk_misi');
    }

    public function down()
    {
        $this->forge->dropTable('pk_misi', true, true);
    }
}
