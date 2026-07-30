<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

/**
 * SUB RENCANA AKSI pada modul Target & Rencana Aksi.
 *
 * `target_rencana.rencana_aksi` menyimpan beberapa butir rencana aksi sebagai
 * teks multi-baris (1 baris = 1 butir) — bentuk itu sengaja dipertahankan karena
 * dipakai halaman cetak & MONEV. Sub rencana aksi karena itu ditautkan ke NOMOR
 * BARIS butirnya lewat kolom `baris_rencana` (0 = butir ke-1).
 *
 * Idempoten: dilewati kalau tabelnya sudah dibuat lewat
 * db/update_2026-07-27_sub_rencana_aksi.sql.
 */
class CreateTargetSubRencanaTable extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('target_sub_rencana')) {
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
            'baris_rencana' => [
                'type'    => 'INT',
                'null'    => false,
                'default' => 0,
                'comment' => 'indeks butir rencana aksi (0 = butir ke-1)',
            ],
            'sub_rencana_aksi' => [
                'type' => 'TEXT',
                'null' => false,
            ],
            // Target triwulan diisi per sub rencana aksi. Kolom sejenis di
            // `target_rencana` tetap ada — MONEV memakainya sebagai target
            // tingkat indikator pembanding capaian.
            'target_triwulan_1' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'target_triwulan_2' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'target_triwulan_3' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'target_triwulan_4' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'urutan' => [
                'type'    => 'INT',
                'null'    => false,
                'default' => 0,
            ],
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
        $this->forge->addKey(['target_rencana_id', 'baris_rencana', 'urutan']);
        $this->forge->addForeignKey('target_rencana_id', 'target_rencana', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('target_sub_rencana');
    }

    public function down()
    {
        $this->forge->dropTable('target_sub_rencana', true, true);
    }
}
