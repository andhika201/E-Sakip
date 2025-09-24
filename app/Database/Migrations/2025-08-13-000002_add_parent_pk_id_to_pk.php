<?php
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddParentPkIdToPk extends Migration
{
    public function up()
    {
        $this->forge->addColumn('pk', [
            'parent_pk_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
                'after' => 'id',
            ],
        ]);
        $this->forge->addForeignKey('parent_pk_id', 'pk', 'id', 'CASCADE', 'CASCADE');
    }

    public function down()
    {
        $this->forge->dropForeignKey('pk', 'pk_parent_pk_id_foreign');
        $this->forge->dropColumn('pk', 'parent_pk_id');
    }
}
