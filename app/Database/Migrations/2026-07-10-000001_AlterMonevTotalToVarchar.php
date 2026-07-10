<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AlterMonevTotalToVarchar extends Migration
{
    public function up()
    {
        if (!$this->db->tableExists('monev') || !$this->db->fieldExists('total', 'monev')) {
            return;
        }

        $this->forge->modifyColumn('monev', [
            'total' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
        ]);
    }

    public function down()
    {
        if (!$this->db->tableExists('monev') || !$this->db->fieldExists('total', 'monev')) {
            return;
        }

        $this->forge->modifyColumn('monev', [
            'total' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => false,
            ],
        ]);
    }
}
