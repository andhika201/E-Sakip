<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        // Run seeders in the correct order (considering foreign key dependencies)
        $this->call('Opd');
        $this->call('Pangkat');
        $this->call('Jabatan');
        $this->call('UserAdminOpdSeeder');
        // Add other seeders here as needed
    }
}
