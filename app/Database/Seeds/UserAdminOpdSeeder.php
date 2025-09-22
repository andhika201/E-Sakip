<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class UserAdminOpdSeeder extends Seeder
{
    public function run()
    {
        // Data admin OPD untuk berbagai dinas
        $userData = [
            // DISKOMINFO
            [
                'username' => 'admin_diskominfo',
                'password' => password_hash('diskominfo2024', PASSWORD_DEFAULT),
                'email' => 'admin@diskominfo.kabupaten.go.id',
                'role' => 'admin_opd',
                'opd_id' => 20, // DINAS KOMUNIKASI DAN INFORMATIKA
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],
        ];
        
        foreach ($userData as $user) {
            // Cek apakah user sudah ada
            $existingUser = $this->db->table('users')
                ->where('username', $user['username'])
                ->get()
                ->getRow();

            if (!$existingUser) {
                $this->db->table('users')->insert($user);
                echo "User {$user['username']} berhasil ditambahkan\n";
            } else {
                echo "User {$user['username']} sudah ada, skip\n";
            }
        }
    }
}
