<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class UserAdminOpdSeeder extends Seeder
{
    public function run()
    {
        // Data admin OPD untuk berbagai dinas
        $userData = [
            // Super Admin
            [
                'username' => 'superadmin',
                'password' => password_hash('superadmin', PASSWORD_DEFAULT),
                'email' => 'admin@admin',
                'role' => 'admin',
                'opd_id' => 0, // Admin
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],

            // Admin Kabupaten
            [
                'username' => 'admin_kab',
                'password' => password_hash('admin_kab', PASSWORD_DEFAULT),
                'email' => 'adminkabupaten@kabupaten.go.id',
                'role' => 'admin_kab',
                'opd_id' => 75, 
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            
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
            
            // DINAS PENDIDIKAN (contoh jika ada ID 5)
            [
                'username' => 'admin_diknas',
                'password' => password_hash('diknas2024', PASSWORD_DEFAULT),
                'email' => 'admin@diknas.kabupaten.go.id',
                'role' => 'admin_opd',
                'opd_id' => 5, // Sesuaikan dengan ID Dinas Pendidikan
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            
            // DINAS KESEHATAN (contoh jika ada ID 6)
            [
                'username' => 'admin_dinkes',
                'password' => password_hash('dinkes2024', PASSWORD_DEFAULT),
                'email' => 'admin@dinkes.kabupaten.go.id',
                'role' => 'admin_opd',
                'opd_id' => 6, // Sesuaikan dengan ID Dinas Kesehatan
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            
            // BAPPEDA (contoh jika ada ID 2)
            [
                'username' => 'admin_bappeda',
                'password' => password_hash('bappeda2024', PASSWORD_DEFAULT),
                'email' => 'admin@bappeda.kabupaten.go.id',
                'role' => 'admin_opd',
                'opd_id' => 2, // Sesuaikan dengan ID BAPPEDA
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]
        ];

        echo "=== MENAMBAHKAN USER ADMIN OPD ===\n";
        
        foreach ($userData as $user) {
            // Cek apakah user sudah ada
            $existingUser = $this->db->table('users')
                ->where('username', $user['username'])
                ->get()
                ->getRow();

            if (!$existingUser) {
                $this->db->table('users')->insert($user);
                echo "✓ User {$user['username']} berhasil ditambahkan\n";
            } else {
                echo "- User {$user['username']} sudah ada, skip\n";
            }
        }

        echo "\n=== DAFTAR LOGIN ADMIN OPD ===\n";
        echo "1. DISKOMINFO:\n";
        echo "   Username: admin_diskominfo\n";
        echo "   Password: diskominfo2024\n";
        echo "   URL: /adminopd/dashboard\n\n";
        
        echo "2. DINAS PENDIDIKAN:\n";
        echo "   Username: admin_diknas\n";
        echo "   Password: diknas2024\n";
        echo "   URL: /adminopd/dashboard\n\n";
        
        echo "3. DINAS KESEHATAN:\n";
        echo "   Username: admin_dinkes\n";
        echo "   Password: dinkes2024\n";
        echo "   URL: /adminopd/dashboard\n\n";
        
        echo "4. BAPPEDA:\n";
        echo "   Username: admin_bappeda\n";
        echo "   Password: bappeda2024\n";
        echo "   URL: /adminopd/dashboard\n";
        echo "==============================\n";
    }
}
