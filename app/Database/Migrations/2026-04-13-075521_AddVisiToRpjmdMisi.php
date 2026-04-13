<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddVisiToRpjmdMisi extends Migration
{
    public function up()
    {
        // Tambah kolom visi ke rpjmd_misi (setelah id, sebelum misi)
        $this->forge->addColumn('rpjmd_misi', [
            'visi' => [
                'type'       => 'TEXT',
                'null'       => true,
                'after'      => 'id',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('rpjmd_misi', 'visi');
    }
}
