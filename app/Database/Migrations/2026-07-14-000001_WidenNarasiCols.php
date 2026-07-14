<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Menyamakan lebar kolom naratif dengan validasi controller (max_length[255])
 * agar input normal tidak memicu error 1406 "Data too long" pada STRICT mode.
 *   - lakip.target_lalu / capaian_lalu / capaian_tahun_ini : varchar(50) -> varchar(255)
 *   - opd.alamat_opd : varchar(50) -> varchar(255)
 * Idempoten: modifyColumn ke tipe yang sama aman diulang.
 */
class WidenNarasiCols extends Migration
{
    public function up()
    {
        $this->forge->modifyColumn('lakip', [
            'target_lalu'       => ['name' => 'target_lalu', 'type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
            'capaian_lalu'      => ['name' => 'capaian_lalu', 'type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
            'capaian_tahun_ini' => ['name' => 'capaian_tahun_ini', 'type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
        ]);

        $this->forge->modifyColumn('opd', [
            'alamat_opd' => ['name' => 'alamat_opd', 'type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
        ]);
    }

    public function down()
    {
        $this->forge->modifyColumn('lakip', [
            'target_lalu'       => ['name' => 'target_lalu', 'type' => 'VARCHAR', 'constraint' => 50, 'null' => false],
            'capaian_lalu'      => ['name' => 'capaian_lalu', 'type' => 'VARCHAR', 'constraint' => 50, 'null' => false],
            'capaian_tahun_ini' => ['name' => 'capaian_tahun_ini', 'type' => 'VARCHAR', 'constraint' => 50, 'null' => false],
        ]);

        $this->forge->modifyColumn('opd', [
            'alamat_opd' => ['name' => 'alamat_opd', 'type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
        ]);
    }
}
