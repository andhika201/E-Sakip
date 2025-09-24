<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class UbahKolomRpjmdIndikatorSasaran extends Migration
{
    public function up()
    {
        // Ubah kolom 'strategi' menjadi 'definisi_op' pada tabel 'rpjmd_indikator_sasaran'
        $fields = [
            'strategi' => [
                'name' => 'definisi_op',
                'type' => 'TEXT',
                'null' => true,
            ],
        ];
        $this->forge->modifyColumn('rpjmd_indikator_sasaran', $fields);
    }

    public function down()
    {
        // Kembalikan kolom 'definisi_op' menjadi 'strategi'
        $fields = [
            'definisi_op' => [
                'name' => 'strategi',
                'type' => 'TEXT',
                'null' => true,
            ],
        ];
        $this->forge->modifyColumn('rpjmd_indikator_sasaran', $fields);
    }
}
