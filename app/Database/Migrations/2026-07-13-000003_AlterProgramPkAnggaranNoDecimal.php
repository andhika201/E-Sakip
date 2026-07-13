<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AlterProgramPkAnggaranNoDecimal extends Migration
{
    public function up()
    {
        foreach (['program_pk', 'kegiatan_pk', 'sub_kegiatan_pk'] as $table) {
            $this->db->query("UPDATE `{$table}` SET `created_at` = NOW() WHERE CAST(`created_at` AS CHAR) = '0000-00-00 00:00:00'");
            $this->db->query("UPDATE `{$table}` SET `updated_at` = NOW() WHERE CAST(`updated_at` AS CHAR) = '0000-00-00 00:00:00'");
        }

        $this->forge->modifyColumn('program_pk', [
            'anggaran' => [
                'name'       => 'anggaran',
                'type'       => 'DECIMAL',
                'constraint' => '15,0',
                'null'       => false,
                'default'    => '0',
            ],
        ]);

        foreach (['kegiatan_pk', 'sub_kegiatan_pk'] as $table) {
            $this->forge->modifyColumn($table, [
                'anggaran' => [
                    'name'       => 'anggaran',
                    'type'       => 'DECIMAL',
                    'constraint' => '15,0',
                    'null'       => true,
                    'default'    => '0',
                ],
            ]);
        }
    }

    public function down()
    {
        $this->forge->modifyColumn('program_pk', [
            'anggaran' => [
                'name'       => 'anggaran',
                'type'       => 'DECIMAL',
                'constraint' => '15,2',
                'null'       => false,
                'default'    => '0.00',
            ],
        ]);

        foreach (['kegiatan_pk', 'sub_kegiatan_pk'] as $table) {
            $this->forge->modifyColumn($table, [
                'anggaran' => [
                    'name'       => 'anggaran',
                    'type'       => 'DECIMAL',
                    'constraint' => '15,2',
                    'null'       => true,
                    'default'    => '0.00',
                ],
            ]);
        }
    }
}
