<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * DINONAKTIFKAN (2026-07-27).
 *
 * Tabel `iku_target_tahunan` bentuk lama tidak dipakai lagi dan tidak pernah
 * terbentuk di DB. Penggantinya adalah `iku_target` yang dibuat oleh:
 *   2026-07-27-000001_CreateIkuStandaloneTables
 *
 * Lihat catatan lengkap di 2025-07-08-011656_CreateIkuSasaranTable.
 */
class CreateIkuTargetTahunanTable extends Migration
{
    public function up()
    {
        // no-op — lihat 2026-07-27-000001_CreateIkuStandaloneTables
    }

    public function down()
    {
        // no-op
    }
}
