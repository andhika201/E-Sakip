<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class Jabatan extends Seeder
{
    public function run()
    {
        $data = [
            // Sekretariat Daerah (OPD ID: 1)
            ['opd_id' => 1, 'nama_jabatan' => 'Staf Ahli Bupati Bidang Pemerintahan Hukum dan Politik'],
            ['opd_id' => 1, 'nama_jabatan' => 'Staf Ahli Bupati Bidang Kemasyarakatan dan Sumberdaya Manusia'],
            ['opd_id' => 1, 'nama_jabatan' => 'Staf Ahli Bupati Bidang Ekonomi Pembangunan dan Keuangan Kabupaten'],
            ['opd_id' => 1, 'nama_jabatan' => 'Asisten Pemerintahan dan Kesejahteraan Rakyat'],
            ['opd_id' => 1, 'nama_jabatan' => 'Asisten Perekonomian dan Pembangunan'],
            ['opd_id' => 1, 'nama_jabatan' => 'Asisten Administrasi Umum'],
            ['opd_id' => 1, 'nama_jabatan' => 'Kepala Bagian Pemerintahan'],
            ['opd_id' => 1, 'nama_jabatan' => 'Kepala Bagian Kesejahteraan Rakyat'],
            ['opd_id' => 1, 'nama_jabatan' => 'Kepala Bagian Hukum'],
            ['opd_id' => 1, 'nama_jabatan' => 'Kepala Bagian Perekonomian dan Sumber Daya Alam'],
            ['opd_id' => 1, 'nama_jabatan' => 'Kepala Bagian Administrasi Pembangunan'],
            ['opd_id' => 1, 'nama_jabatan' => 'Kepala Bagian Pengadaan Barang dan Jasa'],
            ['opd_id' => 1, 'nama_jabatan' => 'Kepala Bagian Organisasi'],
            ['opd_id' => 1, 'nama_jabatan' => 'Kepala Bagian Umum'],
            ['opd_id' => 1, 'nama_jabatan' => 'Kepala Bagian Protokol dan Komunikasi Pimpinan'],

            // Sekretariat DPRD (OPD ID: 2)
            ['opd_id' => 2, 'nama_jabatan' => 'Sekretaris DPRD'],
            ['opd_id' => 2, 'nama_jabatan' => 'Kepala Bagian Umum dan Keuangan'],
            ['opd_id' => 2, 'nama_jabatan' => 'Kepala Bagian Persidangan Dan Perundang-Undangan'],
            ['opd_id' => 2, 'nama_jabatan' => 'Kepala Bagian Fasilitasi Penganggaran dan Pengawasan'],

            // Inspektorat (OPD ID: 3)
            ['opd_id' => 3, 'nama_jabatan' => 'Inspektur'],
            ['opd_id' => 3, 'nama_jabatan' => 'Sekretaris Inspektorat'],
            ['opd_id' => 3, 'nama_jabatan' => 'Inspektur Pembantu Wilayah II'],
            ['opd_id' => 3, 'nama_jabatan' => 'Inspektur Pembantu Wilayah III'],
            ['opd_id' => 3, 'nama_jabatan' => 'Inspektur Pembantu Bidang Investigasi'],

            // Badan Pengelolaan Keuangan dan Aset Daerah (OPD ID: 4)
            ['opd_id' => 4, 'nama_jabatan' => 'Kepala Badan Pengelolaan Keuangan dan Aset Daerah'],
            ['opd_id' => 4, 'nama_jabatan' => 'Sekretaris Badan Pengelolaan Keuangan dan Aset Daerah'],
            ['opd_id' => 4, 'nama_jabatan' => 'Kepala Bidang Anggaran'],
            ['opd_id' => 4, 'nama_jabatan' => 'Kepala Bidang Akuntansi'],
            ['opd_id' => 4, 'nama_jabatan' => 'Kepala Bidang Perbendaharaan'],
            ['opd_id' => 4, 'nama_jabatan' => 'Kepala Bidang Aset Daerah'],

            // Badan Pendapatan Daerah (OPD ID: 5)
            ['opd_id' => 5, 'nama_jabatan' => 'Kepala Badan Pendapatan Daerah'],
            ['opd_id' => 5, 'nama_jabatan' => 'Sekretaris Badan Pendapatan Daerah'],
            ['opd_id' => 5, 'nama_jabatan' => 'Kepala Bidang Pendapatan'],
            ['opd_id' => 5, 'nama_jabatan' => 'Kepala Bidang Pengendalian dan Pelaporan'],

            // Badan Perencanaan Pembangunan Daerah (OPD ID: 6)
            ['opd_id' => 6, 'nama_jabatan' => 'Kepala Badan Perencanaan Pembangunan Daerah'],
            ['opd_id' => 6, 'nama_jabatan' => 'Sekretaris Badan Perencanaan Pembangunan Daerah'],
            ['opd_id' => 6, 'nama_jabatan' => 'Kepala Bidang Perekonomian dan Sumberdaya Alam'],
            ['opd_id' => 6, 'nama_jabatan' => 'Kepala Bidang Pemerintahan dan Pembangunan Manusia'],
            ['opd_id' => 6, 'nama_jabatan' => 'Kepala Bidang Infrastruktur dan Pengembangan Wilayah'],
            ['opd_id' => 6, 'nama_jabatan' => 'Kepala Bidang Perencanaan, Pengendalian dan Evaluasi'],
            ['opd_id' => 6, 'nama_jabatan' => 'Kepala Bidang Penelitian dan Pengembangan'],

            // Badan Kepegawaian dan Pengembangan Sumber Daya Manusia (OPD ID: 7)
            ['opd_id' => 7, 'nama_jabatan' => 'Kepala Badan Kepegawaian dan Pengembangan Sumber Daya Manusia'],
            ['opd_id' => 7, 'nama_jabatan' => 'Sekretaris Badan Kepegawaian dan Pengembangan Sumber Daya Manusia'],
            ['opd_id' => 7, 'nama_jabatan' => 'Kepala Bidang Pengadaan, Pembinaan dan Informasi ASN'],
            ['opd_id' => 7, 'nama_jabatan' => 'Kepala Bidang Mutasi, Promosi dan Pengembangan Kompetensi'],

            // Badan Satuan Polisi Pamong Praja (OPD ID: 8)
            ['opd_id' => 8, 'nama_jabatan' => 'Kepala Badan Satuan Polisi Pamong Praja'],
            ['opd_id' => 8, 'nama_jabatan' => 'Sekretaris Badan Satuan Polisi Pamong Praja'],
            ['opd_id' => 8, 'nama_jabatan' => 'Kepala Bidang Penegak Perundang-undangan Daerah'],
            ['opd_id' => 8, 'nama_jabatan' => 'Kepala Bidang Ketertiban Umum dan Ketentraman Masyarakat'],
            ['opd_id' => 8, 'nama_jabatan' => 'Kepala Bidang Sumberdaya Aparatur dan Perlindungan Masyarakat'],

            // Dinas Pendidikan dan Kebudayaan (OPD ID: 9)
            ['opd_id' => 9, 'nama_jabatan' => 'Kepala Dinas Pendidikan dan Kebudayaan'],
            ['opd_id' => 9, 'nama_jabatan' => 'Sekretaris Dinas Pendidikan dan Kebudayaan'],
            ['opd_id' => 9, 'nama_jabatan' => 'Kepala Bidang Pendidikan Dasar'],
            ['opd_id' => 9, 'nama_jabatan' => 'Kepala Bidang Pendidikan Anak Usia Dini dan Dikmas'],
            ['opd_id' => 9, 'nama_jabatan' => 'Kepala Bidang Guru dan Tenaga Kependidikan'],
            ['opd_id' => 9, 'nama_jabatan' => 'Kepala Bidang Kebudayaan'],

            // Dinas Kesehatan (OPD ID: 10)
            ['opd_id' => 10, 'nama_jabatan' => 'Kepala Dinas Kesehatan'],
            ['opd_id' => 10, 'nama_jabatan' => 'Sekretaris Dinas Kesehatan'],
            ['opd_id' => 10, 'nama_jabatan' => 'Kepala Bidang Sumber Daya Kesehatan'],
            ['opd_id' => 10, 'nama_jabatan' => 'Kepala Bidang Pelayanan Kesehatan'],
            ['opd_id' => 10, 'nama_jabatan' => 'Kepala Bidang Pencegahan dan Pengendalian Penyakit'],
            ['opd_id' => 10, 'nama_jabatan' => 'Kepala Bidang Kesehatan Masyarakat'],

            // Dinas Sosial (OPD ID: 11)
            ['opd_id' => 11, 'nama_jabatan' => 'Kepala Dinas Sosial'],
            ['opd_id' => 11, 'nama_jabatan' => 'Sekretaris Dinas Sosial'],
            ['opd_id' => 11, 'nama_jabatan' => 'Kepala Bidang Rehabilitasi dan Perlindungan Jaminan Sosial'],
            ['opd_id' => 11, 'nama_jabatan' => 'Kepala Bidang Pemberdayaan Sosial dan Penanganan Fakir Miskin'],

            // Dinas Pemberdayaan Perempuan, Perlindungan Anak, Pengendalian Penduduk dan Keluarga Berencana (OPD ID: 12)
            ['opd_id' => 12, 'nama_jabatan' => 'Kepala Dinas Pemberdayaan Perempuan, Perlindungan Anak, Pengendalian Penduduk dan Keluarga Berencana'],
            ['opd_id' => 12, 'nama_jabatan' => 'Sekretaris Dinas Pemberdayaan Perempuan, Perlindungan Anak, Pengendalian Penduduk dan Keluarga Berencana'],
            ['opd_id' => 12, 'nama_jabatan' => 'Kepala Bidang Pemberdayaan Perempuan dan Perlindungan Anak'],
            ['opd_id' => 12, 'nama_jabatan' => 'Kepala Bidang Keluarga Berencana, Ketahanan dan Kesejahteraan Keluarga'],
            ['opd_id' => 12, 'nama_jabatan' => 'Kepala Bidang Pengendalian Penduduk, Penyuluh dan Penggerakan'],

            // Dinas Kependudukan dan Pencatatan Sipil (OPD ID: 13)
            ['opd_id' => 13, 'nama_jabatan' => 'Kepala Dinas Kependudukan dan Pencatatan Sipil'],
            ['opd_id' => 13, 'nama_jabatan' => 'Sekretaris Dinas Kependudukan dan Pencatatan Sipil'],
            ['opd_id' => 13, 'nama_jabatan' => 'Kepala Bidang Pelayanan Pendaftaran Penduduk'],
            ['opd_id' => 13, 'nama_jabatan' => 'Kepala Bidang Pelayanan Pencatatan Sipil'],
            ['opd_id' => 13, 'nama_jabatan' => 'Kepala Bidang Pengelolaan Informasi Administrasi Kependudukan dan Pemanfaatan Data'],

            // Dinas Kepemudaan, Olahraga dan Pariwisata (OPD ID: 14)
            ['opd_id' => 14, 'nama_jabatan' => 'Kepala Dinas Kepemudaan, Olahraga dan Pariwisata'],
            ['opd_id' => 14, 'nama_jabatan' => 'Sekretaris Dinas Kepemudaan, Olahraga dan Pariwisata'],
            ['opd_id' => 14, 'nama_jabatan' => 'Kepala Bidang Kepemudaan'],
            ['opd_id' => 14, 'nama_jabatan' => 'Kepala Bidang Keolahragaan'],
            ['opd_id' => 14, 'nama_jabatan' => 'Kepala Bidang Kepariwisataan'],

            // Dinas Koperasi, Usaha Kecil dan Menengah, Perindustrian dan Perdagangan (OPD ID: 15)
            ['opd_id' => 15, 'nama_jabatan' => 'Kepala Dinas Koperasi, Usaha Kecil dan Menengah, Perindustrian dan Perdagangan'],
            ['opd_id' => 15, 'nama_jabatan' => 'Sekretaris Dinas Koperasi, Usaha Kecil dan Menengah, Perindustrian dan Perdagangan'],
            ['opd_id' => 15, 'nama_jabatan' => 'Kepala Bidang Koperasi dan UKM'],
            ['opd_id' => 15, 'nama_jabatan' => 'Kepala Bidang Perindustrian'],
            ['opd_id' => 15, 'nama_jabatan' => 'Kepala Bidang Perdagangan'],

            // Dinas Perhubungan (OPD ID: 16)
            ['opd_id' => 16, 'nama_jabatan' => 'Kepala Dinas Perhubungan'],
            ['opd_id' => 16, 'nama_jabatan' => 'Sekretaris Dinas Perhubungan'],
            ['opd_id' => 16, 'nama_jabatan' => 'Kepala Bidang Lalu Lintas'],
            ['opd_id' => 16, 'nama_jabatan' => 'Kepala Bidang Angkutan Jalan dan Teknik Sarana'],

            // Dinas Pekerjaan Umum dan Perumahan Rakyat (OPD ID: 17)
            ['opd_id' => 17, 'nama_jabatan' => 'Kepala Dinas Pekerjaan Umum dan Perumahan Rakyat'],
            ['opd_id' => 17, 'nama_jabatan' => 'Sekretaris Dinas Pekerjaan Umum dan Perumahan Rakyat'],
            ['opd_id' => 17, 'nama_jabatan' => 'Kepala Bidang Bina Marga'],
            ['opd_id' => 17, 'nama_jabatan' => 'Kepala Bidang Sumber Daya Air'],
            ['opd_id' => 17, 'nama_jabatan' => 'Kepala Bidang Cipta Karya'],
            ['opd_id' => 17, 'nama_jabatan' => 'Kepala Bidang Penataan Ruang'],
            ['opd_id' => 17, 'nama_jabatan' => 'Kepala Bidang Perumahan dan Kawasan Pemukiman'],
            ['opd_id' => 17, 'nama_jabatan' => 'Kepala Bidang Bina Konstruksi'],

            // Dinas Perikanan (OPD ID: 18)
            ['opd_id' => 18, 'nama_jabatan' => 'Kepala Dinas Perikanan'],
            ['opd_id' => 18, 'nama_jabatan' => 'Sekretaris Dinas Perikanan'],
            ['opd_id' => 18, 'nama_jabatan' => 'Kepala Bidang Pasca Panen dan Pengawasan'],
            ['opd_id' => 18, 'nama_jabatan' => 'Kepala Bidang Perikanan Budidaya dan Tangkap'],

            // Dinas Komunikasi dan Informatika (OPD ID: 19)
            ['opd_id' => 19, 'nama_jabatan' => 'Kepala Dinas Komunikasi dan Informatika'],
            ['opd_id' => 19, 'nama_jabatan' => 'Sekretaris Dinas Komunikasi dan Informatika'],
            ['opd_id' => 19, 'nama_jabatan' => 'Kepala Bidang Tata Kelola SPBE, Persandian, dan Keamanan Informasi'],
            ['opd_id' => 19, 'nama_jabatan' => 'Kepala Bidang Informasi, Komunikasi Publik dan Statistik Sektoral'],

            // Dinas Pertanian (OPD ID: 20)
            ['opd_id' => 20, 'nama_jabatan' => 'Kepala Dinas Pertanian'],
            ['opd_id' => 20, 'nama_jabatan' => 'Sekretaris Dinas Pertanian'],
            ['opd_id' => 20, 'nama_jabatan' => 'Kepala Bidang Peternakan dan Kesehatan Hewan'],
            ['opd_id' => 20, 'nama_jabatan' => 'Kepala Bidang Perkebunan'],
            ['opd_id' => 20, 'nama_jabatan' => 'Kepala Bidang Tanaman Pangan dan Hortikultura'],
            ['opd_id' => 20, 'nama_jabatan' => 'Kepala Bidang Sarana dan Prasarana'],
            ['opd_id' => 20, 'nama_jabatan' => 'Kepala Bidang Penyuluhan'],

            // Dinas Pemberdayaan Masyarakat dan Pekon (OPD ID: 21)
            ['opd_id' => 21, 'nama_jabatan' => 'Kepala Dinas Pemberdayaan Masyarakat dan Pekon'],
            ['opd_id' => 21, 'nama_jabatan' => 'Sekretaris Dinas Pemberdayaan Masyarakat dan Pekon'],
            ['opd_id' => 21, 'nama_jabatan' => 'Kepala Bidang Pemberdayaan'],
            ['opd_id' => 21, 'nama_jabatan' => 'Kepala Bidang Pemerintahan, Pembangunan, Keuangan dan Aset Pekon'],

            // Dinas Lingkungan Hidup (OPD ID: 22)
            ['opd_id' => 22, 'nama_jabatan' => 'Kepala Dinas Lingkungan Hidup'],
            ['opd_id' => 22, 'nama_jabatan' => 'Sekretaris Dinas Lingkungan Hidup'],
            ['opd_id' => 22, 'nama_jabatan' => 'Kepala Bidang Pengelolaan Sampah, Limbah B3 dan Pengendalian Pencemaran'],
            ['opd_id' => 22, 'nama_jabatan' => 'Kepala Bidang Penataan dan Peningkatan Kapasitas'],

            // Dinas Ketahanan Pangan (OPD ID: 23)
            ['opd_id' => 23, 'nama_jabatan' => 'Kepala Dinas Ketahanan Pangan'],
            ['opd_id' => 23, 'nama_jabatan' => 'Sekretaris Dinas Ketahanan Pangan'],
            ['opd_id' => 23, 'nama_jabatan' => 'Kepala Bidang Ketersediaan dan Kerawanan Pangan'],
            ['opd_id' => 23, 'nama_jabatan' => 'Kepala Bidang Konsumsi dan Keamanan Pangan'],

            // Dinas Penanaman Modal dan Pelayanan Terpadu Satu Pintu (OPD ID: 24)
            ['opd_id' => 24, 'nama_jabatan' => 'Kepala Dinas Penanaman Modal dan Pelayanan Terpadu Satu Pintu'],
            ['opd_id' => 24, 'nama_jabatan' => 'Sekretaris Dinas Penanaman Modal dan Pelayanan Terpadu Satu Pintu'],

            // Dinas Perpustakaan dan Kearsipan (OPD ID: 25)
            ['opd_id' => 25, 'nama_jabatan' => 'Kepala Dinas Perpustakaan dan Kearsipan'],
            ['opd_id' => 25, 'nama_jabatan' => 'Sekretaris Dinas Perpustakaan dan Kearsipan'],
            ['opd_id' => 25, 'nama_jabatan' => 'Kepala Bidang Perpustakaan'],
            ['opd_id' => 25, 'nama_jabatan' => 'Kepala Bidang Kearsipan'],

            // Dinas Tenaga Kerja dan Transmigrasi (OPD ID: 26)
            ['opd_id' => 26, 'nama_jabatan' => 'Kepala Dinas Tenaga Kerja dan Transmigrasi'],
            ['opd_id' => 26, 'nama_jabatan' => 'Sekretaris Dinas Tenaga Kerja dan Transmigrasi'],
            ['opd_id' => 26, 'nama_jabatan' => 'Kepala Bidang Tenaga Kerja'],
            ['opd_id' => 26, 'nama_jabatan' => 'Kepala Bidang Transmigrasi'],

            // Badan Kesatuan Bangsa dan Politik (OPD ID: 27)
            ['opd_id' => 27, 'nama_jabatan' => 'Kepala Badan Kesatuan Bangsa dan Politik'],
            ['opd_id' => 27, 'nama_jabatan' => 'Sekretaris Badan Kesatuan Bangsa dan Politik'],
            ['opd_id' => 27, 'nama_jabatan' => 'Kepala Bidang Ideologi Pancasila, Wawasan Kebangsaan dan Ketahanan Ekonomi, Sosial Budaya dan Agama'],
            ['opd_id' => 27, 'nama_jabatan' => 'Kepala Bidang Politik Dalam Negeri dan Organisasi Kemasyarakatan'],
            ['opd_id' => 27, 'nama_jabatan' => 'Kepala Bidang Kewaspadaan Nasional dan Penanganan Konflik'],

            // Badan Penanggulangan Bencana Daerah (OPD ID: 28)
            ['opd_id' => 28, 'nama_jabatan' => 'Kepala Badan Penanggulangan Bencana Daerah'],
            ['opd_id' => 28, 'nama_jabatan' => 'Sekretaris Badan Penanggulangan Bencana Daerah'],
            ['opd_id' => 28, 'nama_jabatan' => 'Kepala Bidang Pencegahan dan Kesiapsiagaan'],
            ['opd_id' => 28, 'nama_jabatan' => 'Kepala Bidang Kedaruratan dan Logistik'],
            ['opd_id' => 28, 'nama_jabatan' => 'Kepala Bidang Rehabilitasi dan Rekonstruksi'],
        ];

        // Insert data using insertBatch for better performance
        $this->db->table('jabatan')->insertBatch($data);
    }
}
