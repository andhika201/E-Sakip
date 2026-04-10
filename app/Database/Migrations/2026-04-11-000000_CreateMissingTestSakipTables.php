<?php
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateMissingTestSakipTables extends Migration
{
    public function up()
    {
        // 1. cascading_indikator_opd
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'cascading_sasaran_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'indikator' => ['type' => 'TEXT'],
            'satuan' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('cascading_indikator_opd', true);

        // 2. cascading_sasaran_opd
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'opd_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'renstra_indikator_sasaran_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'parent_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'es3_indikator_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'level' => ['type' => 'ENUM', 'constraint' => ['es2', 'es3', 'es4']],
            'nama_sasaran' => ['type' => 'TEXT'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('cascading_sasaran_opd', true);

        // 3. iku
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
            'rpjmd_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'renstra_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'definisi' => ['type' => 'TEXT', 'null' => true],
            'status' => ['type' => 'ENUM', 'constraint' => ['belum', 'tercapai'], 'default' => 'belum'],
            'created_at' => ['type' => 'TIMESTAMP', 'null' => true],
            'updated_at' => ['type' => 'TIMESTAMP', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('iku', true);

        // 4. iku_program_pendukung
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
            'iku_id' => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'program' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'created_at' => ['type' => 'TIMESTAMP', 'null' => true],
            'updated_at' => ['type' => 'TIMESTAMP', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('iku_program_pendukung', true);

        // 5. kegiatan_pk
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'program_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'kode_kegiatan' => ['type' => 'INT', 'constraint' => 11],
            'kegiatan' => ['type' => 'TEXT'],
            'tahun_anggaran' => ['type' => 'YEAR'],
            'anggaran' => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => '0.00'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('kegiatan_pk', true);

        // 6. lakip
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
            'renstra_target_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'rpjmd_target_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'target_lalu' => ['type' => 'VARCHAR', 'constraint' => 50],
            'capaian_lalu' => ['type' => 'VARCHAR', 'constraint' => 50],
            'capaian_tahun_ini' => ['type' => 'VARCHAR', 'constraint' => 50],
            'status' => ['type' => 'ENUM', 'constraint' => ['proses', 'siap'], 'default' => 'proses'],
            'created_at' => ['type' => 'TIMESTAMP', 'null' => true],
            'updated_at' => ['type' => 'TIMESTAMP', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('lakip', true);

        // 7. monev
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
            'opd_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'target_rencana_id' => ['type' => 'INT', 'constraint' => 11],
            'capaian_triwulan_1' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'capaian_triwulan_2' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'capaian_triwulan_3' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'capaian_triwulan_4' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'total' => ['type' => 'INT', 'constraint' => 11],
            'created_at' => ['type' => 'TIMESTAMP', 'null' => true],
            'updated_at' => ['type' => 'TIMESTAMP', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('monev', true);

        // 8. pk_kegiatan
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'pk_program_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'kegiatan_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('pk_kegiatan', true);

        // 9. pk_subkegiatan
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'pk_kegiatan_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'subkegiatan_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('pk_subkegiatan', true);

        // 10. renstra_indikator_tujuan
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'tujuan_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'indikator_tujuan' => ['type' => 'TEXT'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('renstra_indikator_tujuan', true);

        // 11. renstra_target_tujuan
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'indikator_tujuan_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'tahun' => ['type' => 'YEAR'],
            'target_tahunan' => ['type' => 'VARCHAR', 'constraint' => 100],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('renstra_target_tujuan', true);

        // 12. renstra_tujuan
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'rpjmd_sasaran_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'tujuan' => ['type' => 'TEXT'],
            'created_at' => ['type' => 'TIMESTAMP', 'null' => true],
            'updated_at' => ['type' => 'TIMESTAMP', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('renstra_tujuan', true);

        // 13. rkt
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'opd_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'tahun' => ['type' => 'YEAR'],
            'indikator_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'program_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'status' => ['type' => 'ENUM', 'constraint' => ['draft', 'selesai'], 'default' => 'draft'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('rkt', true);

        // 14. rkt_kegiatan
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'rkt_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'kegiatan_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('rkt_kegiatan', true);

        // 15. rkt_subkegiatan
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'rkt_kegiatan_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'sub_kegiatan_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('rkt_subkegiatan', true);

        // 16. rpjmd_cascading
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'indikator_sasaran_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'opd_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'pk_program_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'tahun' => ['type' => 'YEAR'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('rpjmd_cascading', true);

        // 17. rpjmd_target_tujuan
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'indikator_tujuan_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'tahun' => ['type' => 'YEAR'],
            'target_tahunan' => ['type' => 'VARCHAR', 'constraint' => 255],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('rpjmd_target_tujuan', true);

        // 18. sub_kegiatan_pk
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'kegiatan_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'kode_sub_kegiatan' => ['type' => 'INT', 'constraint' => 11],
            'sub_kegiatan' => ['type' => 'TEXT'],
            'tahun_anggaran' => ['type' => 'YEAR'],
            'anggaran' => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => '0.00'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('sub_kegiatan_pk', true);

        // 19. target_rencana
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
            'opd_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'renstra_target_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'rpjmd_target_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'rencana_aksi' => ['type' => 'TEXT', 'null' => true],
            'capaian' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'target_triwulan_1' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'target_triwulan_2' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'target_triwulan_3' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'target_triwulan_4' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'penanggung_jawab' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'created_at' => ['type' => 'TIMESTAMP', 'null' => true],
            'updated_at' => ['type' => 'TIMESTAMP', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('target_rencana', true);
    }

    public function down()
    {
        $this->forge->dropTable('cascading_indikator_opd', true);
        $this->forge->dropTable('cascading_sasaran_opd', true);
        $this->forge->dropTable('iku', true);
        $this->forge->dropTable('iku_program_pendukung', true);
        $this->forge->dropTable('kegiatan_pk', true);
        $this->forge->dropTable('lakip', true);
        $this->forge->dropTable('monev', true);
        $this->forge->dropTable('pk_kegiatan', true);
        $this->forge->dropTable('pk_subkegiatan', true);
        $this->forge->dropTable('renstra_indikator_tujuan', true);
        $this->forge->dropTable('renstra_target_tujuan', true);
        $this->forge->dropTable('renstra_tujuan', true);
        $this->forge->dropTable('rkt', true);
        $this->forge->dropTable('rkt_kegiatan', true);
        $this->forge->dropTable('rkt_subkegiatan', true);
        $this->forge->dropTable('rpjmd_cascading', true);
        $this->forge->dropTable('rpjmd_target_tujuan', true);
        $this->forge->dropTable('sub_kegiatan_pk', true);
        $this->forge->dropTable('target_rencana', true);
    }
}
