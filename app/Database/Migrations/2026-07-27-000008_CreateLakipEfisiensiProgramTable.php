<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Efisiensi Program dan Anggaran pada halaman LAKIP.
 *
 * Satu program hanya boleh punya SATU baris efisiensi per tahun per OPD —
 * dijaga UNIQUE (program_id, tahun, opd_id). `opd_id` NOT NULL DEFAULT 0
 * (0 = tingkat kabupaten) supaya UNIQUE-nya benar-benar mengikat; MySQL
 * menganggap dua NULL selalu berbeda.
 *
 * `anggaran` adalah SNAPSHOT dari program_pk.anggaran saat baris disimpan,
 * supaya laporan tahun berjalan tidak ikut berubah bila pagu di PK direvisi.
 * `realisasi` & `efisiensi` diinput manual (bukan hasil hitung sistem).
 *
 * Kembaran SQL langsungnya: db/update_2026-07-27_lakip_analisis_efisiensi.sql
 */
class CreateLakipEfisiensiProgramTable extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('lakip_efisiensi_program')) {
            return;
        }

        $this->forge->addField([
            'id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'program_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'comment' => '-> program_pk.id'],
            'opd_id'     => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'default' => 0, 'comment' => '0 = tingkat kabupaten'],
            'tahun'      => ['type' => 'YEAR'],
            'anggaran'   => ['type' => 'DECIMAL', 'constraint' => '20,2', 'default' => 0, 'comment' => 'snapshot pagu dari program_pk saat disimpan'],
            'realisasi'  => ['type' => 'DECIMAL', 'constraint' => '20,2', 'default' => 0],
            'efisiensi'  => ['type' => 'DECIMAL', 'constraint' => '20,2', 'default' => 0, 'comment' => 'diinput manual, bukan hasil hitung sistem'],
            'created_by' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'updated_by' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['program_id', 'tahun', 'opd_id'], 'uq_efisiensi_program_tahun_opd');
        $this->forge->addKey(['opd_id', 'tahun'], false, false, 'idx_efisiensi_opd_tahun');
        $this->forge->addForeignKey('program_id', 'program_pk', 'id', 'CASCADE', 'CASCADE', 'fk_efisiensi_program');
        $this->forge->createTable('lakip_efisiensi_program', true);
    }

    public function down()
    {
        $this->forge->dropTable('lakip_efisiensi_program', true);
    }
}
