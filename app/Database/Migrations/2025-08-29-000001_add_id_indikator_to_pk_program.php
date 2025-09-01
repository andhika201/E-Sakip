<?php
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class add_id_indikator_to_pk_program extends Migration
{
    public function up()
    {
        $this->forge->addColumn('pk_program', [
            'id_indikator' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
                'after' => 'program_id',
            ],
        ]);
        $this->forge->addForeignKey('id_indikator', 'pk_indikator', 'id', 'CASCADE', 'CASCADE');
    }

    public function down()
    {
        $this->forge->dropForeignKey('pk_program', 'pk_program_id_indikator_foreign');
        $this->forge->dropColumn('pk_program', 'id_indikator');
    }
}
