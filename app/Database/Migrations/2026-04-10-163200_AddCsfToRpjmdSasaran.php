<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddCsfToRpjmdSasaran extends Migration
{
    public function up()
    {
        $this->forge->addColumn('rpjmd_sasaran', [
            'csf' => [
                'type' => 'TEXT',
                'null' => true,
                'after' => 'sasaran_rpjmd',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('rpjmd_sasaran', 'csf');
    }
}
