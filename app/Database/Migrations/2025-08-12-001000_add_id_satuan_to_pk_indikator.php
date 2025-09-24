<?php
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddIdSatuanToPkIndikator extends Migration
{
    public function up()
    {
        $fields = [
            'id_satuan' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
                'after'      => 'indikator', // place after 'indikator' column
            ],
        ];
        $this->forge->addColumn('pk_indikator', $fields);
    }

    public function down()
    {
        $this->forge->dropColumn('pk_indikator', 'id_satuan');
    }
}
