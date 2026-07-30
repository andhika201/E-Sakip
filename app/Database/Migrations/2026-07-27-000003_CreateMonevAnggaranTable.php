<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

/**
 * REALISASI ANGGARAN per triwulan pada MONEV.
 *
 * Pagu anggarannya sendiri tidak disimpan di sini — itu ikut Perjanjian Kinerja
 * (pk_program -> program_pk), sama seperti yang ditampilkan di Target & Rencana
 * Aksi. Tabel ini hanya menampung REALISASI-nya, satu baris per `target_rencana`
 * (yaitu per indikator PK milik satu OPD).
 *
 * Idempoten: dilewati kalau tabelnya sudah dibuat lewat
 * db/update_2026-07-27_monev_anggaran.sql.
 */
class CreateMonevAnggaranTable extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('monev_anggaran')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            // target_rencana.id bertipe INT (signed) — tipe FK harus sama persis.
            'target_rencana_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => false,
            ],
            'opd_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
                'comment'    => 'NULL = PK Bupati (tingkat kabupaten)',
            ],
            'realisasi_triwulan_1' => ['type' => 'DECIMAL', 'constraint' => '15,0', 'null' => true],
            'realisasi_triwulan_2' => ['type' => 'DECIMAL', 'constraint' => '15,0', 'null' => true],
            'realisasi_triwulan_3' => ['type' => 'DECIMAL', 'constraint' => '15,0', 'null' => true],
            'realisasi_triwulan_4' => ['type' => 'DECIMAL', 'constraint' => '15,0', 'null' => true],
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
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('target_rencana_id');
        $this->forge->addKey('opd_id');
        $this->forge->addForeignKey('target_rencana_id', 'target_rencana', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('opd_id', 'opd', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('monev_anggaran');
    }

    public function down()
    {
        $this->forge->dropTable('monev_anggaran', true, true);
    }
}
