<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddHitungLAKIP extends Migration
{
    public function up()
    {
        $fields = [
            'target_hitung' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
                'null'       => true,
                'after'      => 'rpjmd_target_id',
            ],
            'capaian_hitung' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
                'null'       => true,
                'after'      => 'capaian_tahun_ini',
            ],
        ];

        $this->forge->addColumn('lakip', $fields);
    }

    public function down()
    {
        $this->forge->dropColumn('lakip', ['target_hitung', 'capaian_hitung']);
    }
}
