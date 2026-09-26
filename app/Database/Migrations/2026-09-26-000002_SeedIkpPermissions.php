<?php

namespace App\Database\Migrations;

use App\Database\Seeds\IkpPermissionSeeder;
use CodeIgniter\Database\Migration;

/**
 * Izin modul Kinerja Prioritas (IKP) & Pemilik Kinerja.
 * Definisi tunggalnya ada di App\Database\Seeds\IkpPermissionSeeder (pola
 * SeedRoleBupati): `spark migrate` dan `spark db:seed IkpPermissionSeeder`
 * menghasilkan keadaan yang sama. IDEMPOTEN & ADDITIF.
 */
class SeedIkpPermissions extends Migration
{
    public function up()
    {
        IkpPermissionSeeder::terapkan();
    }

    public function down()
    {
        IkpPermissionSeeder::lepas();
    }
}
