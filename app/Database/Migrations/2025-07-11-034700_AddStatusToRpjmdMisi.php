<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddStatusToRpjmdMisi extends Migration
{
    public function up()
    {
        $this->forge->addColumn('rpjmd_misi', [
            'status' => [
                'type' => 'ENUM',
                'constraint' => ['draft', 'selesai'],
                'default' => 'draft',
                'null' => false,
                'after' => 'misi'
            ]
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('rpjmd_misi', 'status');
    }
}
