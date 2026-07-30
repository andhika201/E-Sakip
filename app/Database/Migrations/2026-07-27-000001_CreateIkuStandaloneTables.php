<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

/**
 * IKU STANDALONE — IKU berdiri sendiri, tidak lagi menempel ke RENSTRA/RPJMD.
 *
 * Sebelumnya modul IKU cuma punya tabel pelengkap `iku` (kolom rpjmd_id /
 * renstra_id + definisi) sehingga Sasaran, Indikator, Satuan, dan Target per
 * tahun harus diambil dari renstra/rpjmd dan tidak bisa diinput di modul IKU.
 *
 * Struktur baru:
 *   iku_sasaran (opd_id NULL = tingkat kabupaten)
 *     └─ iku_indikator
 *          ├─ iku_target   (target per tahun)
 *          └─ iku_program  (program pendukung)
 *
 * Satu-satunya relasi keluar adalah `iku_sasaran.opd_id` -> `opd.id`.
 *
 * Idempoten: setiap tabel dilewati kalau sudah ada, supaya aman dijalankan di
 * DB yang skemanya sudah disamakan lewat db/update_2026-07-27_iku_standalone.sql.
 *
 * Tabel lama `iku` dan `iku_program_pendukung` sengaja TIDAK dihapus supaya
 * data lama tetap bisa dirujuk / di-rollback.
 */
class CreateIkuStandaloneTables extends Migration
{
    public function up()
    {
        $this->createSasaran();
        $this->createIndikator();
        $this->createTarget();
        $this->createProgram();
    }

    public function down()
    {
        // urutan terbalik supaya FK tidak menghalangi
        $this->forge->dropTable('iku_program', true, true);
        $this->forge->dropTable('iku_target', true, true);
        $this->forge->dropTable('iku_indikator', true, true);
        $this->forge->dropTable('iku_sasaran', true, true);
    }

    private function timestamps(): array
    {
        return [
            'created_at' => [
                'type'    => 'DATETIME',
                'null'    => true,
                'default' => new RawSql('CURRENT_TIMESTAMP'),
            ],
            'updated_at' => [
                'type'      => 'DATETIME',
                'null'      => true,
                'default'   => new RawSql('CURRENT_TIMESTAMP'),
                'on_update' => new RawSql('CURRENT_TIMESTAMP'),
            ],
        ];
    }

    private function createSasaran(): void
    {
        if ($this->db->tableExists('iku_sasaran')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'opd_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
                'comment'    => 'NULL = IKU tingkat kabupaten; terisi = IKU OPD/Kecamatan',
            ],
            'sasaran' => [
                'type' => 'TEXT',
                'null' => false,
            ],
            'tahun_mulai' => [
                'type' => 'INT',
                'null' => false,
            ],
            'tahun_akhir' => [
                'type' => 'INT',
                'null' => false,
            ],
            'urutan' => [
                'type'    => 'INT',
                'null'    => false,
                'default' => 0,
            ],
        ] + $this->timestamps());

        $this->forge->addKey('id', true);
        $this->forge->addKey('opd_id');
        $this->forge->addKey(['tahun_mulai', 'tahun_akhir']);
        $this->forge->addForeignKey('opd_id', 'opd', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('iku_sasaran');
    }

    private function createIndikator(): void
    {
        if ($this->db->tableExists('iku_indikator')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'iku_sasaran_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => false,
            ],
            'indikator' => [
                'type' => 'TEXT',
                'null' => false,
            ],
            'definisi' => [
                'type'    => 'TEXT',
                'null'    => true,
                'comment' => 'Definisi Operasional',
            ],
            'rumusan_perhitungan' => [
                'type'    => 'TEXT',
                'null'    => true,
                'comment' => 'Formula / Rumusan Perhitungan',
            ],
            'satuan' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
                'comment'    => 'id satuan (numerik) atau teks bebas',
            ],
            'sumber_data' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'penanggung_jawab' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'jenis_indikator' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'baseline' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
            ],
            'urutan' => [
                'type'    => 'INT',
                'null'    => false,
                'default' => 0,
            ],
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => false,
                'default'    => 'draft',
            ],
        ] + $this->timestamps());

        $this->forge->addKey('id', true);
        $this->forge->addKey('iku_sasaran_id');
        $this->forge->addKey('status');
        $this->forge->addForeignKey('iku_sasaran_id', 'iku_sasaran', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('iku_indikator');
    }

    private function createTarget(): void
    {
        if ($this->db->tableExists('iku_target')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'iku_indikator_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => false,
            ],
            'tahun' => [
                'type' => 'INT',
                'null' => false,
            ],
            'target' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
        ] + $this->timestamps());

        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['iku_indikator_id', 'tahun']);
        $this->forge->addForeignKey('iku_indikator_id', 'iku_indikator', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('iku_target');
    }

    private function createProgram(): void
    {
        if ($this->db->tableExists('iku_program')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'iku_indikator_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => false,
            ],
            'program' => [
                'type' => 'TEXT',
                'null' => false,
            ],
            'urutan' => [
                'type'    => 'INT',
                'null'    => false,
                'default' => 0,
            ],
        ] + $this->timestamps());

        $this->forge->addKey('id', true);
        $this->forge->addKey('iku_indikator_id');
        $this->forge->addForeignKey('iku_indikator_id', 'iku_indikator', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('iku_program');
    }
}
