<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Ambang (threshold) status capaian kinerja — dikelola Super Admin.
 *
 * Satu-satunya sumber rentang & warna status capaian yang dipakai dashboard.
 * JANGAN hardcode rentang (0-59,99 dst.) di controller/model/view/JavaScript;
 * pakai getAchievementStatus() di app/Helpers/dashboard_status_helper.php.
 *
 * Kembaran SQL langsungnya (berikut seed default):
 * db/update_2026-07-28_dashboard_thresholds.sql
 *
 * Bersifat idempoten — aman dijalankan ulang pada database yang skemanya
 * sudah lebih dulu diperbarui lewat SQL.
 */
class CreateDashboardStatusThresholds extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('dashboard_status_thresholds')) {
            return;
        }

        $this->forge->addField([
            'id'   => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'code' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'comment'    => 'kunci tetap: critical|attention|near_target|achieved|exceeded',
            ],
            'name'      => ['type' => 'VARCHAR', 'constraint' => 100],
            'min_value' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'null'       => true,
                'comment'    => 'batas bawah persentase (inklusif); NULL = tak terbatas ke bawah',
            ],
            'max_value' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'null'       => true,
                'comment'    => 'batas atas persentase (inklusif); NULL = tak terbatas ke atas',
            ],
            'color' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'default'    => 'abu',
                'comment'    => 'slug warna terbatas: merah|oranye|kuning|hijau|biru|abu',
            ],
            'icon'           => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'sort_order'     => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'is_active'      => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'effective_from' => ['type' => 'DATE', 'null' => true],
            'created_by'     => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'updated_by'     => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'created_at'     => ['type' => 'DATETIME', 'null' => true],
            'updated_at'     => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('code', 'uq_dashboard_threshold_code');
        $this->forge->addKey(['is_active', 'sort_order'], false, false, 'idx_dashboard_threshold_aktif');
        $this->forge->createTable('dashboard_status_thresholds', true);
    }

    public function down()
    {
        $this->forge->dropTable('dashboard_status_thresholds', true);
    }
}
