<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class Opd extends Seeder
{
    public function run()
    {
        $data = [
            [
                'nama_opd' => 'Sekretariat Daerah',
                'singkatan' => 'Setda',
            ],
            [
                'nama_opd' => 'Sekretariat DPRD',
                'singkatan' => 'Setwan',
            ],
            [
                'nama_opd' => 'Inspektorat',
                'singkatan' => 'Inspektorat',
            ],
            [
                'nama_opd' => 'Badan Pengelolaan Keuangan dan Aset Daerah',
                'singkatan' => 'BPKAD',
            ],
            [
                'nama_opd' => 'Badan Pendapatan Daerah',
                'singkatan' => 'Bapenda',
            ],
            [
                'nama_opd' => 'Badan Perencanaan Pembangunan Daerah',
                'singkatan' => 'Bappeda',
            ],
            [
                'nama_opd' => 'Badan Kepegawaian dan Pengembangan Sumber Daya Manusia',
                'singkatan' => 'BKPSDM',
            ],
            [
                'nama_opd' => 'Badan Satuan Polisi Pamong Praja',
                'singkatan' => 'Satpol PP',
            ],
            [
                'nama_opd' => 'Dinas Pendidikan dan Kebudayaan',
                'singkatan' => 'Disdikbud',
            ],
            [
                'nama_opd' => 'Dinas Kesehatan',
                'singkatan' => 'Dinkes',
            ],
            [
                'nama_opd' => 'Dinas Sosial',
                'singkatan' => 'Dinsos',
            ],
            [
                'nama_opd' => 'Dinas Pemberdayaan Perempuan, Perlindungan Anak, Pengendalian Penduduk dan Keluarga Bencana',
                'singkatan' => 'DP3APKB',
            ],
            [
                'nama_opd' => 'Dinas Kependudukan dan Pencatatan Sipil',
                'singkatan' => 'Disdukcapil',
            ],
            [
                'nama_opd' => 'Dinas Kepemudaan, Olahraga dan Pariwisata',
                'singkatan' => 'Disporapar',
            ],
            [
                'nama_opd' => 'Dinas Koperasi, Usaha Kecil dan Menengah, Perdagangan dan Perindustrian',
                'singkatan' => 'Diskopindagri',
            ],
            [
                'nama_opd' => 'Dinas Perhubungan',
                'singkatan' => 'Dishub',
            ],
            [
                'nama_opd' => 'Dinas Pekerjaan Umum dan Perumahan Rakyat',
                'singkatan' => 'DPUPR',
            ],
            [
                'nama_opd' => 'Dinas Perikanan',
                'singkatan' => 'Diskan',
            ],
            [
                'nama_opd' => 'Dinas Komunikasi dan Informatika',
                'singkatan' => 'Diskominfo',
            ],
            [
                'nama_opd' => 'Dinas Pertanian',
                'singkatan' => 'Distan',
            ],
            [
                'nama_opd' => 'Badan Pemberdayaan Masyarakat dan Pekon',
                'singkatan' => 'BPMP',
            ],
            [
                'nama_opd' => 'Dinas Lingkungan Hidup',
                'singkatan' => 'DLH',
            ],
            [
                'nama_opd' => 'Dinas Ketahanan Pangan',
                'singkatan' => 'DKP',
            ],
            [
                'nama_opd' => 'Dinas Penanaman Modal dan Pelayanan Terpadu Satu Pintu',
                'singkatan' => 'DPMPTSP',
            ],
            [
                'nama_opd' => 'Dinas Perpustakaan dan Kearsipan',
                'singkatan' => 'Dispusip',
            ],
            [
                'nama_opd' => 'Dinas Tenaga Kerja dan Transmigrasi',
                'singkatan' => 'Disnakertrans',
            ],
            [
                'nama_opd' => 'Badan Kesatuan Bangsa dan Politik',
                'singkatan' => 'Kesbangpol',
            ],
            [
                'nama_opd' => 'Badan Penanggulangan Bencana Daerah',
                'singkatan' => 'BPBD',
            ],
        ];

        // Insert data using insertBatch for better performance
        $this->db->table('opd')->insertBatch($data);
    }
}
