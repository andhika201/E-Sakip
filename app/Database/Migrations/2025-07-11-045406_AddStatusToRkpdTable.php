<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddStatusToRkpdTable extends Migration
{
    public function up()
    {
        $this->forge->addColumn('rkpd_sasaran', [
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
        $this->forge->dropColumn('rkpd_sasaran', 'status');
    }
}
