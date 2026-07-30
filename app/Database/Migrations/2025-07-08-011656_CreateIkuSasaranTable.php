<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * DINONAKTIFKAN (2026-07-27).
 *
 * Dulu migration ini membuat `iku_sasaran` dengan FK WAJIB ke `renstra_sasaran`
 * — bentuk lama sewaktu IKU masih menempel ke RENSTRA/RPJMD. Tabelnya sendiri
 * tidak pernah benar-benar terbentuk di DB (hanya tercatat di tabel `migrations`).
 *
 * Sejak IKU dijadikan mandiri, `iku_sasaran` dibuat ulang dengan bentuk baru
 * (kolom `opd_id`, tanpa relasi ke renstra/rpjmd) oleh:
 *   2026-07-27-000001_CreateIkuStandaloneTables
 *
 * up()/down() sengaja dikosongkan supaya instalasi baru tidak membuat tabel
 * berbentuk lama yang bentrok dengan migration penggantinya. File tetap
 * dipertahankan agar riwayat `migrations` di DB lama tidak pincang.
 */
class CreateIkuSasaranTable extends Migration
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
