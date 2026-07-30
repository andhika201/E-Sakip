<?php

namespace App\Database\Seeds;

use App\Models\DashboardThresholdModel;
use CodeIgniter\Database\Seeder;

/**
 * Ambang status capaian bawaan.
 *
 * Idempoten: baris yang `code`-nya sudah ada TIDAK ditimpa, supaya konfigurasi
 * yang sudah disesuaikan Super Admin tidak hilang saat seeder dijalankan ulang.
 * Untuk mengembalikan ke bawaan, pakai tombol "Reset ke Default" di
 * adminkab/dashboard-thresholds (atau DashboardThresholdModel::resetToDefault()).
 *
 *   php spark db:seed DashboardStatusThresholdSeeder
 */
class DashboardStatusThresholdSeeder extends Seeder
{
    public function run()
    {
        $tabel = 'dashboard_status_thresholds';
        if (!$this->db->tableExists($tabel)) {
            echo "Tabel {$tabel} belum ada — jalankan migrasinya lebih dulu.\n";
            return;
        }

        $sudah = array_column(
            $this->db->table($tabel)->select('code')->get()->getResultArray(),
            'code'
        );

        $baru = [];
        foreach (DashboardThresholdModel::DEFAULTS as $row) {
            if (in_array($row['code'], $sudah, true)) {
                continue;
            }
            $baru[] = $row + [
                'is_active'  => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ];
        }

        if ($baru === []) {
            echo "Ambang status capaian sudah lengkap — tidak ada yang ditambahkan.\n";
            return;
        }

        $this->db->table($tabel)->insertBatch($baru);
        echo count($baru) . " ambang status capaian ditambahkan.\n";
    }
}
