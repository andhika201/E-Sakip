<?php

namespace App\Database\Migrations;

use App\Database\Seeds\RoleBupatiSeeder;
use CodeIgniter\Database\Migration;

/**
 * Role BUPATI — akses read-only ke Dashboard Eksekutif Kabupaten beserta
 * halaman monitoring pendukungnya (PK, Target & Rencana Aksi, MONEV, LAKIP).
 *
 * Definisinya (role, permission, pemetaan) ada di App\Database\Seeds\RoleBupatiSeeder
 * agar `php spark migrate` dan `php spark db:seed RoleBupatiSeeder` menghasilkan
 * keadaan yang sama persis.
 *
 * Sifat: IDEMPOTEN & ADDITIVE — tidak menghapus role existing, tidak menyentuh
 * tabel `users` (tidak membuat akun Bupati, tidak mengubah password).
 * `users`.`role` bertipe VARCHAR(50) pada DB berjalan, jadi TIDAK ada enum yang
 * perlu diubah.
 */
class SeedRoleBupati extends Migration
{
    public function up()
    {
        RoleBupatiSeeder::terapkan();
    }

    public function down()
    {
        RoleBupatiSeeder::lepas();
    }
}
