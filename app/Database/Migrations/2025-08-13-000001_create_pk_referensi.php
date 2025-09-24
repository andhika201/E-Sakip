<?php
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePkReferensi extends Migration
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
            ],
            'referensi_pk_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'referensi_indikator_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('pk_id', 'pk', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('referensi_pk_id', 'pk', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('referensi_indikator_id', 'pk_indikator', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('pk_referensi');
    }

    public function down()
    {
        $this->forge->dropTable('pk_referensi');
    }
}
