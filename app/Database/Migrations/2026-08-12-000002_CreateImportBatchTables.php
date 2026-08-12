<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Staging import Lampiran 8 untuk cakupan "Seluruh OPD".
 *
 * Hasil parsing seluruh unit disimpan sementara di sini supaya unit yang
 * OPD-nya belum bisa ditentukan dapat dipetakan manual TANPA mengunggah ulang
 * Excel. Tabel produksi (program_pk/kegiatan_pk/sub_kegiatan_pk) baru diisi
 * ketika sebuah unit selesai dipetakan.
 *
 * Kembaran SQL langsungnya: db/update_2026-08-12_import_multi_opd.sql
 */
class CreateImportBatchTables extends Migration
{
    public function up()
    {
        if (!$this->db->tableExists('import_batch')) {
            $this->forge->addField([
                'id'             => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'tahun'          => ['type' => 'YEAR'],
                'jenis_anggaran' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'murni'],
                'mode_import'    => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'seluruh', 'comment' => 'per_opd | seluruh'],
                'nama_file'      => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'status'         => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'pending_mapping'],
                'jumlah_unit'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'default' => 0],
                'jumlah_pending' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'default' => 0],
                'created_by'     => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'created_at'     => ['type' => 'DATETIME', 'null' => true],
                'updated_at'     => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addKey(['status', 'tahun'], false, false, 'idx_batch_status');
            $this->forge->addKey(['created_by', 'created_at'], false, false, 'idx_batch_pembuat');
            $this->forge->createTable('import_batch');
        }

        if (!$this->db->tableExists('import_batch_unit')) {
            $this->forge->addField([
                'id'              => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'batch_id'        => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'kode_unit_excel' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
                'nama_unit_excel' => ['type' => 'VARCHAR', 'constraint' => 255],
                // NULL = belum dipetakan. Sengaja tidak diberi default supaya
                // tidak pernah ada OPD "default" untuk unit tak dikenal.
                'opd_id'          => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'mapping_status'  => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'pending_mapping'],
                'mapping_method'  => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true, 'comment' => 'exact | alias | parent_rule | manual'],
                'saran_opd_id'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true, 'comment' => 'fuzzy: saran saja'],
                'saran_skor'      => ['type' => 'DECIMAL', 'constraint' => '5,2', 'null' => true],
                'jumlah_program'  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'default' => 0],
                'jumlah_kegiatan' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'default' => 0],
                'jumlah_sub'      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'default' => 0],
                'total_anggaran'  => ['type' => 'DECIMAL', 'constraint' => '20,2', 'default' => 0],
                'urutan'          => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'default' => 0],
                'created_at'      => ['type' => 'DATETIME', 'null' => true],
                'updated_at'      => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addKey(['batch_id', 'mapping_status'], false, false, 'idx_bu_batch_status');
            $this->forge->addKey('opd_id', false, false, 'idx_bu_opd');
            $this->forge->addForeignKey('batch_id', 'import_batch', 'id', 'CASCADE', 'CASCADE', 'fk_bu_batch');
            $this->forge->createTable('import_batch_unit');
        }

        if (!$this->db->tableExists('import_batch_row')) {
            $this->forge->addField([
                'id'            => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'batch_unit_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'level'         => ['type' => 'VARCHAR', 'constraint' => 10, 'comment' => 'program | kegiatan | sub'],
                // Jalur kode penuh yang sudah dinormalisasi, supaya finalisasi
                // cukup menyalin apa adanya dan hierarki tidak mungkin berubah.
                'kode_program'  => ['type' => 'VARCHAR', 'constraint' => 80],
                'kode_kegiatan' => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
                'kode_sub'      => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
                'nomenklatur'   => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => true],
                'uraian'        => ['type' => 'TEXT'],
                'anggaran'      => ['type' => 'DECIMAL', 'constraint' => '20,2', 'null' => true, 'comment' => 'NULL = sel kolom K kosong, bukan 0'],
                'punya_baris'   => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
                'baris_excel'   => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'urutan'        => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'default' => 0],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addKey(['batch_unit_id', 'urutan'], false, false, 'idx_br_unit_urut');
            $this->forge->addKey(['batch_unit_id', 'level'], false, false, 'idx_br_unit_level');
            $this->forge->addForeignKey('batch_unit_id', 'import_batch_unit', 'id', 'CASCADE', 'CASCADE', 'fk_br_unit');
            $this->forge->createTable('import_batch_row');
        }

        if (!$this->db->tableExists('opd_import_alias')) {
            $this->forge->addField([
                'id'               => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'kode_unit'        => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
                'nama_unit_normal' => ['type' => 'VARCHAR', 'constraint' => 255],
                'nama_unit_asli'   => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'opd_id'           => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'created_by'       => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'created_at'       => ['type' => 'DATETIME', 'null' => true],
                'updated_at'       => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addUniqueKey('nama_unit_normal', 'uq_alias_nama');
            $this->forge->addKey('kode_unit', false, false, 'idx_alias_kode');
            $this->forge->addKey('opd_id', false, false, 'idx_alias_opd');
            $this->forge->addForeignKey('opd_id', 'opd', 'id', 'CASCADE', 'CASCADE', 'fk_alias_opd');
            $this->forge->createTable('opd_import_alias');
        }
    }

    public function down()
    {
        // Urutan terbalik supaya foreign key tidak menghalangi.
        $this->forge->dropTable('import_batch_row', true, true);
        $this->forge->dropTable('import_batch_unit', true, true);
        $this->forge->dropTable('import_batch', true, true);
        $this->forge->dropTable('opd_import_alias', true, true);
    }
}
