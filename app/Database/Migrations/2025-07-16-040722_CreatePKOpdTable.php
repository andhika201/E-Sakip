<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePKOpdTable extends Migration
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
            'opd_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => false,
            ],
            'jenis' => [
                'type' => 'ENUM',
                'constraint' => ['JPT', 'Administrator', 'Pengawas'],
                'null' => false,
            ],
            'pihak_1' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => false,
            ],
            'pihak_2' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => false,
            ],
            'tanggal' => [
                'type' => 'DATE',
                'null' => false,
            ],

            'rpjmd_misi_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
            ],
            'parent_jpt_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
            ],
            'parent_admin_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'default' => new \CodeIgniter\Database\RawSql('CURRENT_TIMESTAMP'),
            ],
            'updated_at' => [
                'type' => 'DATETIME', 
                'null' => true,
                'default' => new \CodeIgniter\Database\RawSql('CURRENT_TIMESTAMP'),
                'on_update' => new \CodeIgniter\Database\RawSql('CURRENT_TIMESTAMP'),
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('opd_id', 'opd', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('pihak_1', 'pegawai', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('pihak_2', 'pegawai', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('rpjmd_misi_id', 'rpjmd_misi', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('parent_jpt_id', 'pk_opd', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('parent_admin_id', 'pk_opd', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('pk_opd');
    }

    public function down()
    {
        $this->forge->dropTable('pk_opd', true);
    }
}
