<?php
namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class SatuanSeeder extends Seeder
{
    public function run()
    {
        $data = [
            ['satuan' => 'persen'],
            ['satuan' => 'orang'],
            ['satuan' => 'paket'],
            ['satuan' => 'dokumen'],
            ['satuan' => 'akreditasi rumah sakit'],
            ['satuan' => 'opini BPK'],
            ['satuan' => 'unit'],
            ['satuan' => 'laporan'],
            ['satuan' => 'bulan'],
            ['satuan' => 'unit kerja'],
            ['satuan' => 'nilai sakip'],
            ['satuan' => 'ton'],
        ];
        $this->db->table('satuan')->insertBatch($data);
    }
}
