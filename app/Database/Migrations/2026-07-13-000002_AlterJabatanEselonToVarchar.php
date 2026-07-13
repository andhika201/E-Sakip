<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AlterJabatanEselonToVarchar extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();
        if (!$db->fieldExists('eselon', 'jabatan')) {
            $this->forge->addColumn('jabatan', [
                'eselon' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 50,
                    'null'       => true,
                    'after'      => 'updated_at',
                ],
            ]);
            return;
        }

        $this->forge->modifyColumn('jabatan', [
            'eselon' => [
                'name'       => 'eselon',
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
            ],
        ]);
    }

    public function down()
    {
        $db = \Config\Database::connect();
        if ($db->fieldExists('eselon', 'jabatan')) {
            $this->forge->modifyColumn('jabatan', [
                'eselon' => [
                    'name' => 'eselon',
                    'type' => 'INT',
                    'null' => true,
                ],
            ]);
        }
    }
}
