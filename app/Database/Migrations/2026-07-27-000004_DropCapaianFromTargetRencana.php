<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Hapus kolom "Baseline" (`target_rencana.capaian`).
 *
 * Baseline dibuang dari modul Target & Rencana Aksi maupun PK Renaksi/MONEV;
 * targetnya kini dipegang `target_sub_rencana.target_triwulan_1..4`.
 *
 * JANGAN tertukar dengan kolom lain yang namanya mirip dan TETAP dipakai:
 *   monev.capaian_triwulan_1..4, monev.total, monev_anggaran.realisasi_*,
 *   serta `baseline` milik renstra/rpjmd_indikator_sasaran (dipakai Cascading).
 *
 * Idempoten: dilewati kalau kolomnya sudah tidak ada (mis. sudah dijalankan
 * lewat db/update_2026-07-27_drop_baseline_target_rencana.sql, yang juga
 * mencadangkan isinya ke `_bak_target_rencana_capaian_20260727`).
 */
class DropCapaianFromTargetRencana extends Migration
{
    public function up()
    {
        if (! $this->db->fieldExists('capaian', 'target_rencana')) {
            return;
        }

        $this->forge->dropColumn('target_rencana', 'capaian');
    }

    public function down()
    {
        if ($this->db->fieldExists('capaian', 'target_rencana')) {
            return;
        }

        $this->forge->addColumn('target_rencana', [
            'capaian' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'after'      => 'rencana_aksi',
            ],
        ]);
    }
}
