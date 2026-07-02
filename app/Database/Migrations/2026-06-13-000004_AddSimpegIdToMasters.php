<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Menambah kolom `simpeg_id` pada opd, pangkat, jabatan untuk menyimpan ID dari
 * SIMPEG (mis. opd: "1", pangkat: "pns-7", jabatan: "struktural-STR-001").
 *
 * Dipakai sebagai kunci pemetaan saat sinkron agar idempoten & tahan rename
 * (tidak menduplikasi master). Pegawai dipetakan via NIP (sudah ada).
 *
 * Idempoten: cek kolom sebelum menambah.
 */
class AddSimpegIdToMasters extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();

        foreach (['opd', 'pangkat', 'jabatan'] as $table) {
            $fields = array_column($db->getFieldData($table), 'name');
            if (!in_array('simpeg_id', $fields, true)) {
                $this->forge->addColumn($table, [
                    'simpeg_id' => [
                        'type'       => 'VARCHAR',
                        'constraint' => 50,
                        'null'       => true,
                        'after'      => 'id',
                    ],
                ]);
                // index untuk lookup cepat saat sync
                $db->query("CREATE INDEX `idx_{$table}_simpeg_id` ON `{$table}` (`simpeg_id`)");
            }
        }
    }

    public function down()
    {
        $db = \Config\Database::connect();
        foreach (['opd', 'pangkat', 'jabatan'] as $table) {
            $fields = array_column($db->getFieldData($table), 'name');
            if (in_array('simpeg_id', $fields, true)) {
                $this->forge->dropColumn($table, 'simpeg_id');
            }
        }
    }
}
