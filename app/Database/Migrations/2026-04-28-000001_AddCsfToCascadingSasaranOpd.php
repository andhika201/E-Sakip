<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddCsfToCascadingSasaranOpd extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();

        // Cek apakah kolom csf sudah ada sebelum menambahkan
        $fields = $db->getFieldData('cascading_sasaran_opd');
        $fieldNames = array_column($fields, 'name');

        if (!in_array('csf', $fieldNames)) {
            $this->forge->addColumn('cascading_sasaran_opd', [
                'csf' => [
                    'type'  => 'TEXT',
                    'null'  => true,
                    'after' => 'nama_sasaran',
                ],
            ]);
        }
    }

    public function down()
    {
        $this->forge->dropColumn('cascading_sasaran_opd', 'csf');
    }
}

