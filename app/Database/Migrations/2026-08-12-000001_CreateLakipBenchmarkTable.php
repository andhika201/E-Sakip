<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Benchmark Provinsi Lampung & Nasional untuk indikator LAKIP.
 *
 * Berbeda dengan `lakip_analisis_faktor` yang menempel ke TARGET, benchmark
 * menempel ke INDIKATOR (rpjmd_indikator_sasaran / renstra_indikator_sasaran)
 * + tahun. Alasannya: benchmark adalah lapisan analisis tambahan yang tidak
 * boleh melahirkan indikator baru, dan satu indikator cukup punya satu
 * pembanding per tahun.
 *
 * Nilai Kabupaten/OPD TIDAK disimpan di sini — tetap dibaca dari
 * `lakip.capaian_tahun_ini` supaya sumber data LAKIP existing tidak berubah.
 *
 * Kembaran SQL langsungnya: db/update_2026-08-12_lakip_benchmark.sql
 */
class CreateLakipBenchmarkTable extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('lakip_benchmark')) {
            return;
        }

        $this->forge->addField([
            'id'                   => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'rpjmd_indikator_id'   => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true, 'comment' => 'mode Kabupaten (RPJMD)'],
            'renstra_indikator_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true, 'comment' => 'mode OPD (Renstra)'],
            'opd_id'               => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'default' => 0, 'comment' => '0 = tingkat kabupaten'],
            'tahun'                => ['type' => 'YEAR'],
            'nilai_provinsi'       => ['type' => 'DECIMAL', 'constraint' => '20,4', 'null' => true, 'comment' => 'NULL = belum ada data, bukan 0'],
            'nilai_nasional'       => ['type' => 'DECIMAL', 'constraint' => '20,4', 'null' => true, 'comment' => 'NULL = belum ada data, bukan 0'],
            'sumber_provinsi'      => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'sumber_nasional'      => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'catatan'              => ['type' => 'TEXT', 'null' => true],
            'created_by'           => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'updated_by'           => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'created_at'           => ['type' => 'DATETIME', 'null' => true],
            'updated_at'           => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        // MySQL mengizinkan banyak NULL pada UNIQUE, jadi kunci rpjmd tidak
        // mengganggu baris renstra (dan sebaliknya).
        $this->forge->addUniqueKey(['rpjmd_indikator_id', 'tahun'], 'uq_benchmark_rpjmd');
        $this->forge->addUniqueKey(['renstra_indikator_id', 'tahun'], 'uq_benchmark_renstra');
        $this->forge->addKey(['opd_id', 'tahun'], false, false, 'idx_benchmark_opd_tahun');
        $this->forge->addForeignKey('rpjmd_indikator_id', 'rpjmd_indikator_sasaran', 'id', 'CASCADE', 'CASCADE', 'fk_benchmark_rpjmd_indikator');
        $this->forge->addForeignKey('renstra_indikator_id', 'renstra_indikator_sasaran', 'id', 'CASCADE', 'CASCADE', 'fk_benchmark_renstra_indikator');
        $this->forge->createTable('lakip_benchmark');
    }

    public function down()
    {
        $this->forge->dropTable('lakip_benchmark', true, true);
    }
}
