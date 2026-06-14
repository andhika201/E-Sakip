<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * - Menambah kolom `penanggung_jawab` pada tabel `iku` (format IKU Permenpan).
 * - Menormalkan kolom `iku.status` agar konsisten memakai nilai 'draft' / 'selesai'.
 *   Pada DB lama kolom ini pernah berupa enum('belum','tercapai') sehingga banyak
 *   baris tersimpan kosong ('') atau memakai nilai lama. Migrasi ini memetakan:
 *     'tercapai'                  -> 'selesai'
 *     '' / NULL / 'belum' / lain  -> 'draft'
 *   lalu menjadikan tipe kolom VARCHAR(20) NOT NULL DEFAULT 'draft'.
 */
class AddPenanggungJawabAndNormalizeIkuStatus extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();

        // 1) Tambah kolom penanggung_jawab kalau belum ada
        $fields     = $db->getFieldData('iku');
        $fieldNames = array_column($fields, 'name');

        if (!in_array('penanggung_jawab', $fieldNames, true)) {
            $this->forge->addColumn('iku', [
                'penanggung_jawab' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 255,
                    'null'       => true,
                    'after'      => 'definisi',
                ],
            ]);
        }

        // 2) Normalisasi nilai status SEBELUM mengubah tipe kolom
        $db->query("UPDATE `iku` SET `status` = 'selesai' WHERE LOWER(TRIM(`status`)) = 'tercapai'");
        $db->query("UPDATE `iku` SET `status` = 'draft'   WHERE `status` IS NULL OR LOWER(TRIM(`status`)) NOT IN ('draft','selesai')");

        // 3) Samakan tipe kolom menjadi VARCHAR(20) NOT NULL DEFAULT 'draft'
        //    (idempotent: aman dijalankan ulang; juga memperbaiki skema fresh-install
        //     yang sebelumnya enum('belum','tercapai')).
        $this->forge->modifyColumn('iku', [
            'status' => [
                'name'       => 'status',
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => false,
                'default'    => 'draft',
            ],
        ]);
    }

    public function down()
    {
        $db         = \Config\Database::connect();
        $fields     = $db->getFieldData('iku');
        $fieldNames = array_column($fields, 'name');

        if (in_array('penanggung_jawab', $fieldNames, true)) {
            $this->forge->dropColumn('iku', 'penanggung_jawab');
        }

        // Kembalikan status ke enum lama (best-effort).
        $this->forge->modifyColumn('iku', [
            'status' => [
                'name'       => 'status',
                'type'       => 'ENUM',
                'constraint' => ['belum', 'tercapai'],
                'null'       => false,
                'default'    => 'belum',
            ],
        ]);
    }
}
