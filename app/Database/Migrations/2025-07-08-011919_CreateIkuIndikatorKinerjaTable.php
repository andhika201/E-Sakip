<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * DINONAKTIFKAN (2026-07-27).
 *
 * Tabel `iku_indikator_kinerja` bentuk lama tidak dipakai lagi dan tidak pernah
 * terbentuk di DB. Penggantinya adalah `iku_indikator` yang dibuat oleh:
 *   2026-07-27-000001_CreateIkuStandaloneTables
 *
 * Lihat catatan lengkap di 2025-07-08-011656_CreateIkuSasaranTable.
 */
class CreateIkuIndikatorKinerjaTable extends Migration
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
