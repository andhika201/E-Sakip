<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddStatusToRktTable extends Migration
{
    public function up()
    {
        $this->forge->addColumn('rkt_sasaran', [
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
        $this->forge->dropColumn('rkt_sasaran', 'status');
    }
}
