<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddStatusColumn extends Migration
{
    public function up()
    {

        $this->forge->addColumn('renja_sasaran', [
            'status' => [
                'type' => 'ENUM',
                'constraint' => ['draft', 'selesai'],
                'default' => 'draft',
                'null' => false,
                'after' => 'sasaran_renja'
            ]
        ]);

        $this->forge->addColumn('iku_sasaran', [
            'status' => [
                'type' => 'ENUM',
                'constraint' => ['draft', 'selesai'],
                'default' => 'draft',
                'null' => false,
                'after' => 'sasaran'
            ]
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('renja_sasaran', 'status');
        $this->forge->dropColumn('iku_sasaran', 'status');
    }
}
