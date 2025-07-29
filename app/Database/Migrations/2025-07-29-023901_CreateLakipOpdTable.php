<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateLakipOpdTable extends Migration
{
    public function up()
    {
        $this->forgeField([
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
            'judul'       => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'tanggal_laporan' => [
                'type'       => 'DATE',
                'null'      => true,
            ],
            'file'        => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'      => true,
            ],
            'status'      => [
                'type'       => 'ENUM',
                'constraint' => ['draft', 'selesai'],
                'default'    => 'draft',
                'null'       => false,
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
        $this->forge->createTable('lakip_opd');
    }

    public function down()
    {
        $this->forge->dropTable('lakip_opd', true);
    }
}
