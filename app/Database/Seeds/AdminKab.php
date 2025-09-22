<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class AdminKab extends Seeder
{
    public function run()
    {
        // Data admin kabupaten
        $userData = [
            'username' => 'admin_kabupaten',
            'password' => password_hash('admin2025', PASSWORD_DEFAULT),
            'email' => 'admin_kabupaten@example.com',
            'role' => 'admin_kabupaten',
            'opd_id' => null, // Admin kabupaten tidak terkait dengan OPD tertentu
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        // Insert data admin kabupaten
        $this->db->table('users')->insert($userData);   
    }
}
