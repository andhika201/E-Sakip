<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class Pangkat extends Seeder
{
    public function run()
    {
        $data = [
            ['nama_pangkat' => 'Juru Muda', 'golongan' => 'I/a'],
            ['nama_pangkat' => 'Juru Muda Tingkat I', 'golongan' => 'I/b'],
            ['nama_pangkat' => 'Juru', 'golongan' => 'I/c'],
            ['nama_pangkat' => 'Juru Tingkat I', 'golongan' => 'I/d'],
            ['nama_pangkat' => 'Pengatur Muda', 'golongan' => 'II/a'],
            ['nama_pangkat' => 'Pengatur Muda Tingkat I', 'golongan' => 'II/b'],
            ['nama_pangkat' => 'Pengatur', 'golongan' => 'II/c'],
            ['nama_pangkat' => 'Pengatur Tingkat I', 'golongan' => 'II/d'],
            ['nama_pangkat' => 'Penata Muda', 'golongan' => 'III/a'],
            ['nama_pangkat' => 'Penata Muda Tingkat I', 'golongan' => 'III/b'],
            ['nama_pangkat' => 'Penata', 'golongan' => 'III/c'],
            ['nama_pangkat' => 'Penata Tingkat I', 'golongan' => 'III/d'],
            ['nama_pangkat' => 'Pembina', 'golongan' => 'IV/a'],
            ['nama_pangkat' => 'Pembina Tingkat I', 'golongan' => 'IV/b'],
            ['nama_pangkat' => 'Pembina Utama Muda', 'golongan' => 'IV/c'],
            ['nama_pangkat' => 'Pembina Utama Madya', 'golongan' => 'IV/d'],
            ['nama_pangkat' => 'Pembina Utama', 'golongan' => 'IV/e'],
        ];

        $this->db->table('pangkat')->insertBatch($data);
    }
}
