<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Capaian Total MONEV jadi PERSENTASE yang dihitung otomatis.
 *
 * - `monev.metode_perhitungan` : sum | trend_naik | trend_turun | trend_flat
 * - `monev.total`              : VARCHAR -> DECIMAL(10,2), diisi angka persen
 *                                tanpa simbol % (mis. 86.67).
 *
 * Nama kolom `total` sengaja dipertahankan (bukan bikin `capaian_total` baru)
 * supaya tidak ada kolom kembar dan query lama tetap jalan.
 *
 * Kembaran SQL langsungnya: db/update_2026-07-27_monev_metode_perhitungan.sql
 * (dipakai di server yang skemanya sudah menyimpang dari daftar migrasi).
 * Migrasi ini idempoten, jadi aman dijalankan walau SQL itu sudah dieksekusi.
 */
class AddMetodePerhitunganToMonev extends Migration
{
    public function up()
    {
        if (!$this->db->tableExists('monev')) {
            return;
        }

        if (!$this->db->fieldExists('metode_perhitungan', 'monev')) {
            $this->forge->addColumn('monev', [
                'metode_perhitungan' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 20,
                    'null'       => true,
                    'comment'    => 'sum | trend_naik | trend_turun | trend_flat',
                    'after'      => 'capaian_triwulan_4',
                ],
            ]);
        }

        if (!$this->db->fieldExists('total', 'monev')) {
            return;
        }

        // Nilai non-numerik tidak bisa dikonversi ke DECIMAL pada sql_mode STRICT.
        $this->db->query(
            "UPDATE `monev` SET `total` = NULL
             WHERE `total` IS NOT NULL
               AND (`total` = '' OR `total` NOT REGEXP '^-?[0-9]+(\\\\.[0-9]+)?$')"
        );

        $this->forge->modifyColumn('monev', [
            'total' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'null'       => true,
                'comment'    => 'Capaian Total dalam PERSEN, tanpa simbol % (mis. 86.67). Dihitung otomatis.',
            ],
        ]);

        // Total warisan adalah angka mutlak ketikan pengguna, bukan persentase.
        $this->db->query('UPDATE `monev` SET `total` = NULL WHERE `metode_perhitungan` IS NULL');
    }

    public function down()
    {
        if (!$this->db->tableExists('monev')) {
            return;
        }

        if ($this->db->fieldExists('total', 'monev')) {
            $this->forge->modifyColumn('monev', [
                'total' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 255,
                    'null'       => true,
                ],
            ]);
        }

        if ($this->db->fieldExists('metode_perhitungan', 'monev')) {
            $this->forge->dropColumn('monev', 'metode_perhitungan');
        }
    }
}
