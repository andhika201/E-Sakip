-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Jan 14, 2026 at 01:12 PM
-- Server version: 8.4.3
-- PHP Version: 8.3.26

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `test_sakip`
--

-- --------------------------------------------------------

--
-- Table structure for table `iku`
--

CREATE TABLE `iku` (
  `id` int NOT NULL,
  `rpjmd_id` int UNSIGNED DEFAULT NULL,
  `renstra_id` int UNSIGNED DEFAULT NULL,
  `definisi` text COLLATE utf8mb4_general_ci,
  `status` enum('belum','tercapai') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'belum',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `iku`
--

INSERT INTO `iku` (`id`, `rpjmd_id`, `renstra_id`, `definisi`, `status`, `created_at`, `updated_at`) VALUES
(11, NULL, 9, 'sasa', 'tercapai', '2025-11-18 01:41:23', '2025-11-23 19:25:33'),
(12, NULL, 10, 'qasa', 'tercapai', '2025-11-24 02:04:04', '2025-12-01 05:56:50'),
(13, NULL, 3, 'sas', 'tercapai', '2025-12-08 01:19:02', '2025-12-10 23:03:19'),
(14, 2, NULL, 'sasas', 'belum', '2025-12-08 06:11:51', '2025-12-08 06:11:51');

-- --------------------------------------------------------

--
-- Table structure for table `iku_program_pendukung`
--

CREATE TABLE `iku_program_pendukung` (
  `id` int NOT NULL,
  `iku_id` int DEFAULT NULL,
  `program` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `iku_program_pendukung`
--

INSERT INTO `iku_program_pendukung` (`id`, `iku_id`, `program`, `created_at`, `updated_at`) VALUES
(26, 11, 'asa', '2025-11-18 01:41:23', '2025-11-18 01:41:23'),
(27, 11, 'dasa', '2025-11-18 01:41:23', '2025-11-18 01:41:23'),
(28, 11, 'sasas1', '2025-11-18 01:41:23', '2025-11-18 06:06:35'),
(29, 11, 'sasas12', '2025-11-18 01:41:23', '2025-12-08 06:18:40'),
(30, 12, 'sas', '2025-11-24 02:04:04', '2025-11-24 02:04:04'),
(31, 12, 'sas', '2025-11-24 02:04:04', '2025-11-24 02:04:04'),
(32, 13, 'asaas', '2025-12-08 01:19:02', '2025-12-08 01:19:02'),
(33, 13, 'asas1', '2025-12-08 01:19:02', '2025-12-08 01:19:08'),
(34, 14, 'sasa', '2025-12-08 06:11:51', '2025-12-08 06:11:51'),
(35, 14, 'sasas', '2025-12-08 06:11:51', '2025-12-08 06:11:51'),
(36, 14, 'sasa12', '2025-12-08 06:11:51', '2025-12-08 06:18:50'),
(37, 13, 'asa', '2025-12-11 06:03:16', '2025-12-11 06:03:16');

-- --------------------------------------------------------

--
-- Table structure for table `jabatan`
--

CREATE TABLE `jabatan` (
  `id` int UNSIGNED NOT NULL,
  `opd_id` int UNSIGNED DEFAULT NULL,
  `nama_jabatan` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `tupoksi` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `edited_by` int DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `eselon` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `jabatan`
--

INSERT INTO `jabatan` (`id`, `opd_id`, `nama_jabatan`, `tupoksi`, `edited_by`, `created_at`, `updated_at`, `eselon`) VALUES
(1, 1, 'ADMIN', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(2, 2, 'Staf Ahli Bupati Bidang Pemerintahan Hukum dan Politik', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', 2),
(3, 2, 'Staf Ahli Bupati Bidang Kemasyarakatan dan Sumberdaya Manusia', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', 2),
(4, 2, 'Staf Ahli Bupati Bidang Ekonomi Pembangunan dan Keuangan Kabupaten', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', 2),
(5, 2, 'Asisten Pemerintahan dan Kesejahteraan Rakyat', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', 2),
(6, 2, 'Asisten Perekonomian dan Pembangunan', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', 2),
(7, 2, 'Asisten Administrasi Umum ', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', 2),
(8, 2, 'Kepala Bagian Pemerintahan', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', 0),
(9, 2, 'Kepala Bagian Kesejahteraan Rakyat', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(10, 2, 'Kepala Bagian Hukum ', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(11, 2, 'Kepala Bagian Perekonomian dan Sumber Daya Alam', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(12, 2, 'Kepala Bagian Administrasi Pembangunan', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(13, 2, 'Kepala Bagian Pengadaan Barang dan Jasa', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(14, 2, 'Kepala Bagian Organisasi', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(15, 2, 'Kepala Bagian Umum', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(16, 2, 'Kepala Bagian Protokol dan Komunikasi Pimpinan', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(17, 3, 'Sekretaris DPRD', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', 2),
(18, 3, 'Kepala Bagian Umum dan Keuangan', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(19, 3, 'Kepala Bagian Persidangan Dan Perundang-Undangan', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(20, 3, 'Kepala Bagian Fasilitasi Penganggaran dan Pengawasan', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(21, 4, 'Inspektur', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', 2),
(22, 4, 'Sekretaris Inspektorat', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(23, 4, 'Inspektur Pembantu Wilayah II', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(24, 4, 'Inspektur Pembantu Wilayah III', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(25, 4, 'Inspektur Pembantu Bidang Investigasi', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(26, 5, 'Kepala Badan Pengelolaan Keuangan dan Aset Daerah', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', 2),
(27, 5, 'Sekretaris Badan Pengelolaan Keuangan dan Aset Daerah', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(28, 5, 'Kepala Bidang Anggaran', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(29, 5, 'Kepala Bidang Akuntansi', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(30, 5, 'Kepala Bidang Perbendaharaan', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(31, 5, 'Kepala Bidang Aset Daerah', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(32, 6, 'Kepala Badan Pendapatan Daerah', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', 2),
(33, 6, 'Sekretaris Badan Pendapatan Daerah', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(34, 6, 'Kepala Bidang Pendapatan', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(35, 6, 'Kepala Bidang Pengendalian dan Pelaporan', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(36, 7, 'Kepala Badan Perencanaan Pembangunan Daerah', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', 2),
(37, 7, 'Sekretaris Badan Perencanaan Pembangunan Daerah', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(38, 7, 'Kepala Bidang Perekonomian dan Sumberdaya Alam', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(39, 7, 'Kepala Bidang Pemerintahan dan Pembangunan Manusia', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(40, 7, 'Kepala Bidang Infrastruktur dan Pengembangan Wilayah', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(41, 7, 'Kepala Bidang Perencanaan, Pengendalian dan Evaluasi', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(42, 7, 'Kepala Bidang Penelitian dan Pengembangan', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(43, 8, 'Kepala Badan Kepegawaian dan Pengembangan Sumber Daya Manusia', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', 2),
(44, 8, 'Sekretaris Badan Kepegawaian dan Pengembangan Sumber Daya Manusia', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(45, 8, 'Kepala Bidang Pengadaan, Pembinaan dan Informasi ASN', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(46, 8, 'Kepala Bidang Mutasi, Promosi dan Pengembangan Kompetensi', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(47, 9, 'Kepala Badan Satuan Polisi Pamong Praja ', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', 2),
(48, 9, 'Sekretaris Badan Satuan Polisi Pamong Praja ', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(49, 9, 'Kepala Bidang Penegak Perundang-undangan Daerah', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(50, 9, 'Kepala Bidang Ketertiban Umum dan Ketentraman Masyarakat', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(51, 9, 'Kepala Bidang Sumberdaya Aparatur dan Perlindungan Masyarakat', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(52, 10, 'Kepala Dinas Pendidikan dan Kebudayaan', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', 2),
(53, 10, 'Sekretaris Dinas Pendidikan dan Kebudayaan', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(54, 10, 'Kepala Bidang Pendidikan Dasar', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(55, 10, 'Kepala Bidang Pendidikan Anak Usia Dini dan Dikmas', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(56, 10, 'Kepala Bidang Guru dan Tenaga Kependidikan', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(57, 10, 'Kepala Bidang Kebudayaan', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(58, 11, 'Kepala Dinas Kesehatan', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', 2),
(59, 11, 'Sekretaris Dinas Kesehatan', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(60, 11, 'Kepala Bidang Sumber Daya Kesehatan', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(61, 11, 'Kepala Bidang Pelayanan Kesehatan', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(62, 11, 'Kepala Bidang Pencegahan dan Pengendalian Penyakit', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(63, 11, 'Kepala Bidang Kesehatan Masyarakat', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(64, 12, 'Kepala Dinas Sosial', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', 2),
(65, 12, 'Sekretaris Dinas Sosial', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(66, 12, 'Kepala Bidang Rehabilitasi dan Perlindungan Jaminan Sosial', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(67, 12, 'Kepala Bidang Pemberdayaan Sosial dan Penanganan Fakir Miskin', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(68, 13, 'Kepala Dinas Pemberdayaan Perempuan, Perlindungan Anak, Pengendalian Penduduk dan Keluarga Berencana', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', 2),
(69, 13, 'Sekretaris Dinas Pemberdayaan Perempuan, Perlindungan Anak, Pengendalian Penduduk dan Keluarga Berencana', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(70, 13, 'Kepala Bidang Pemberdayaan Perempuan dan Perlindungan Anak', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(71, 13, 'Kepala Bidang Keluarga Berencana, Ketahanan dan Kesejahteraan Keluarga', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(72, 13, 'Kepala Bidang Pengendalian Penduduk, Penyuluh dan Penggerakan', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(73, 14, 'Kepala Dinas Kependudukan dan Pencatatan Sipil', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', 2),
(74, 14, 'Sekretaris Dinas Kependudukan dan Pencatatan Sipil', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(75, 14, 'Kepala Bidang Pelayanan Pendaftaran Penduduk', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(76, 14, 'Kepala Bidang Pelayanan Pencatatan Sipil', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(77, 14, 'Kepala Bidang Pengelolaan Informasi Administrasi Kependudukan dan Pemanfaatan Data', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(78, 15, 'Kepala Dinas Kepemudaan, Olahraga dan Pariwisata', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', 2),
(79, 15, 'Sekretaris Dinas Kepemudaan, Olahraga dan Pariwisata', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(80, 15, 'Kepala Bidang Kepemudaan', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(81, 15, 'Kepala Bidang Keolahragaan', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(82, 15, 'Kepala Bidang Kepariwisataan', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(83, 16, 'Kepala Dinas Koperasi, Usaha Kecil dan Menengah, Perindustrian dan Perdagangan', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', 2),
(84, 16, 'Sekretaris Dinas Koperasi, Usaha Kecil dan Menengah, Perindustrian dan Perdagangan', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(85, 16, 'Kepala Bidang Koperasi dan UKM', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(86, 16, 'Kepala Bidang Perindustrian', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(87, 16, 'Kepala Bidang Perdagangan', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(88, 17, 'Kepala Dinas Perhubungan', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', 2),
(89, 17, 'Sekretaris Dinas Perhubungan', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(90, 17, 'Kepala Bidang Lalu Lintas', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(91, 17, 'Kepala Bidang Angkutan Jalan dan Teknik Sarana', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(92, 18, 'Kepala Dinas Pekerjaan Umum dan Perumahan Rakyat', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', 2),
(93, 18, 'Sekretaris Dinas Pekerjaan Umum dan Perumahan Rakyat', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(94, 18, 'Kepala Bidang Bina Marga', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(95, 18, 'Kepala Bidang Sumber Daya Air', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(96, 18, 'Kepala Bidang Cipta Karya', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(97, 18, 'Kepala Bidang Penataan Ruang', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(98, 18, 'Kepala Bidang Perumahan dan Kawasan Pemukiman', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(99, 18, 'Kepala Bidang Bina Konstruksi', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(100, 19, 'Kepala Dinas Perikanan', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', 2),
(101, 19, 'Sekretaris Dinas Perikanan', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(102, 19, 'Kepala Bidang Pasca Panen dan Pengawasan', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(103, 19, 'Kepala Bidang Perikanan Budidaya dan Tangkap', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(104, 20, 'Kepala Dinas Komunikasi dan Informatika', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', 2),
(105, 20, 'Sekretaris Dinas Komunikasi dan Informatika', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(106, 20, 'Kepala Bidang Tata Kelola SPBE, Persandian, dan Keamanan Informasi', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(107, 20, 'Kepala Bidang Informasi, Komunikasi Publik dan Statistik Sektoral', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(108, 21, 'Kepala Dinas Pertanian', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', 2),
(109, 21, 'Sekretaris Dinas Pertanian', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(110, 21, 'Kepala Bidang Peternakan dan Kesehatan Hewan', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(111, 21, 'Kepala Bidang Perkebunan', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(112, 21, 'Kepala Bidang Tanaman Pangan dan Hortikultura', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(113, 21, 'Kepala Bidang Sarana dan Prasarana', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(114, 21, 'Kepala Bidang Penyuluhan', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(115, 22, 'Kepala Dinas Pemberdayaan Masyarakat dan Pekon', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', 2),
(116, 22, 'Sekretaris Dinas Pemberdayaan Masyarakat dan Pekon', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(117, 22, 'Kepala Bidang Pemberdayaan', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(118, 22, 'Kepala Bidang Pemerintahan, Pembangunan, Keuangan dan Aset Pekon', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(119, 23, 'Kepala Dinas Lingkungan Hidup', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', 2),
(120, 23, 'Sekretaris Dinas Lingkungan Hidup', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(121, 23, 'Kepala Bidang Pengelolaan Sampah, Limbah B3 dan Pengendalian Pencemaran', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(122, 23, 'Kepala Bidang Penataan dan Peningkatan Kapasitas', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(123, 24, 'Kepala Dinas Ketahanan Pangan', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', 2),
(124, 24, 'Sekretaris Dinas Ketahanan Pangan', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(125, 24, 'Kepala Bidang Ketersediaan dan Kerawanan Pangan', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(126, 24, 'Kepala Bidang Konsumsi dan Keamanan Pangan', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(127, 25, 'Kepala Dinas Penanaman Modal dan Pelayanan Terpadu Satu Pintu', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', 2),
(128, 25, 'Sekretaris Dinas Penanaman Modal dan Pelayanan Terpadu Satu Pintu', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(129, 26, 'Kepala Dinas Perpustakaan dan Kearsipan', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', 2),
(130, 26, 'Sekretaris Dinas Perpustakaan dan Kearsipan', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(131, 26, 'Kepala Bidang Perpustakaan', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(132, 26, 'Kepala Bidang Kearsipan', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(133, 27, 'Kepala Dinas Tenaga Kerja dan Transmigrasi', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', 2),
(134, 27, 'Sekretaris Dinas Tenaga Kerja dan Transmigrasi', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(135, 27, 'Kepala Bidang Tenaga Kerja', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(136, 27, 'Kepala Bidang Transmigrasi', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(137, 28, 'Kepala Badan Kesatuan Bangsa dan Politik', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', 2),
(138, 28, 'Sekretaris Badan Kesatuan Bangsa dan Politik', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(139, 28, 'Kepala Bidang Ideologi Pancasila, Wawasan Kebangsaan dan Ketahanan Ekonomi, Sosial Budaya dan Agama', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(140, 28, 'Kepala Bidang Politik Dalam Negeri dan Organisasi Kemasyarakatan', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(141, 28, 'Kepala Bidang Kewaspadaan Nasional dan Penanganan Konflik', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(142, 29, 'Kepala Badan Penanggulangan Bencana Daerah', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', 2),
(143, 29, 'Sekretaris Badan Penanggulangan Bencana Daerah', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(144, 29, 'Kepala Bidang Pencegahan dan Kesiapsiagaan', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(145, 29, 'Kepala Bidang Kedaruratan dan Logistik', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(146, 29, 'Kepala Bidang Rehabilitasi dan Rekonstruksi', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(147, 30, 'Direktur Rumah Sakit Umum Daerah Pringsewu', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', 0),
(148, 30, 'Kepala Bagian Tata Usaha', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(149, 30, 'Kepala Bidang Perencanaan dan Keuangan', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(150, 30, 'Kepala Bidang Pelayanan Medik', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(151, 30, 'Kepala Bidang Keperawatan', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(152, 31, 'Camat Pringsewu', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', 3),
(153, 31, 'Sekretaris Camat Pringsewu', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(154, 32, 'Camat Gading Rejo', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', 3),
(155, 32, 'Sekretaris Camat Gading Rejo', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(156, 33, 'Camat Ambarawa', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', 3),
(157, 33, 'Sekretaris Camat Ambarawa', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(158, 34, 'Camat Sukoharjo', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', 3),
(159, 34, 'Sekretaris Camat Sukoharjo', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(160, 35, 'Camat Adiluwih', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', 3),
(161, 35, 'Sekretaris Camat Adiluwih', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(162, 36, 'Camat Banyumas', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', 3),
(163, 36, 'Sekretaris Camat Banyumas', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(164, 37, 'Camat Pagelaran', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', 3),
(165, 37, 'Sekretaris Camat Pagelaran', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(166, 38, 'Camat Pardasuka', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', 3),
(167, 38, 'Sekretaris Camat Pardasuka', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(168, 39, 'Camat Pagelaran Utara', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', 3),
(169, 39, 'Sekretaris Camat Pagelaran Utara', '', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(170, 2, 'Sekretaris Daerah', ' ', 0, '0000-00-00 00:00:00', NULL, 2),
(171, 10, 'Kepala Sub Bagian Umum dan Kepegawaian', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(172, 10, 'Kepala Seksi Pembinaan SMP', '  ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(173, 10, 'Kepala Seksi Pembinaan SD', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(174, 10, 'Kepala Seksi Pembinaan PAUD', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(175, 10, 'Kepala Seksi Kesetaraan', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(176, 10, 'Kepala Seksi Guru dan Tenaga Kependidikan SMP', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(177, 10, 'Kepala Seksi Guru dan Tenaga Kependidikan SD', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(178, 10, 'Pranata Komputer Pelaksana', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(179, 10, 'Perencana Ahli Muda', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(180, 10, 'Penilik Ahli Muda', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(181, 10, 'Penilik Ahli Madya', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(182, 10, 'Pengolah Data', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(183, 10, 'Pengembang Teknologi Pembelajaran Ahli Muda', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(184, 10, 'Pengadministrasi Umum', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(185, 10, 'Pengadministrasi Perencanaan dan Program', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(186, 10, 'Pamong Budaya Ahli Muda', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(187, 10, 'Bendahara', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(188, 10, 'Analis Laporan Keuangan', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(189, 10, 'Analis Kursus dan Kesetaraan', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(190, 10, 'Analis Keuangan Pusat dan Daerah Ahli Muda', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(191, 10, 'Analis Kebutuhan Diklat Kepala Sekolah', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(192, 10, 'Analis Budaya', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(193, 11, 'Kepala UPTD Laboratorium Kesehatan Daerah', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(194, 11, 'Kepala Sub Bagian Umum,  Kepegawaian dan Aset Daerah', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(195, 11, 'Kepala Seksi Promosi Dan Pemberdayaan Masyarakat', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(196, 11, 'Kepala Sub Bagian Keuangan', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(197, 11, 'Sanitarian Ahli Muda', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(198, 11, 'Perencana Ahli Muda', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(199, 11, 'Penyuluh Obat dan Makanan', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(200, 11, 'Penyuluh Kesehatan Masyarakat Ahli Muda', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(201, 11, 'Pengelola Sarana dan Prasarana Kantor', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(202, 11, 'Pengelola Program Kesehatan Keluarga', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(203, 11, 'Pengelola Program Imunisasi', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(204, 11, 'Pengelola Kepegawaian', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(205, 11, 'Pengelola Data', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(206, 11, 'Pengelola Bahan Perencanaan', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(207, 11, 'Pengawas Keselamatan dan Kesehatan Kerja dan Lindungan Lingkungan', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(208, 11, 'Pelaksana/Terampil - Asisten Apoteker', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(209, 11, 'Nutrisionis Ahli Muda', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(210, 11, 'Fungsional Umum', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(211, 11, 'Bidan Mahir', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(212, 11, 'Bendahara', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(213, 11, 'Apoteker Ahli Muda', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(214, 11, 'Analis Perencanaan, Evaluasi dan Pelaporan', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(215, 11, 'ANALIS PENYAKIT MENULAR', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(216, 11, 'ANALIS PEMBERDAYAAN MASYARAKAT', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(217, 11, 'ANALIS MONITORING, EVALUASI DAN PELAPORAN', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(218, 11, 'ANALIS LAPORAN KEUANGAN', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(219, 11, 'ANALIS KESEHATAN IBU DAN ANAK', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(220, 11, 'ANALIS KESEHATAN', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(221, 11, 'ANALIS GIZI', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(222, 11, 'AHLI PERTAMA - PENYULUH KESEHATAN MASYARAKAT', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(223, 11, 'AHLI PERTAMA - APOTEKER', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(224, 11, 'Administrator Kesehatan Ahli Muda', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(225, 11, 'Administrator Kesehatan Ahli Madya', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(226, 23, 'Kepala UPTD Laboratorium Lingkungan', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(227, 23, 'Kepala UPT Pengelolaan Sampah', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(228, 23, 'Kepala Sub Bagian Umum dan Kepegawaian', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(229, 23, 'Kepala Sub Bagian Tata Usaha UPTD Laboratorium Lingkungan', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(230, 23, 'Kepala Sub Bagian Tata Usaha', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(231, 23, 'Kepala Sub Bagian Perencanaan dan Keuangan', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(232, 23, 'Penyuluh Lingkungan Hidup Ahli Muda', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(233, 23, 'Pengendali Dampak Lingkungan Ahli Muda', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(234, 23, 'PENGELOLA DOKUMEN', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(235, 23, 'Pengawas Lingkungan Hidup Ahli Muda', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(236, 23, 'ANALIS PENGADUAN MASYARAKAT', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(237, 23, 'ANALIS LINGKUNGAN HIDUP', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(238, 27, 'Kepala Sub Bagian Umum dan Kepegawaian', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(239, 27, 'Perencana Ahli Muda', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(240, 27, 'PENGELOLA DATA', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(241, 27, 'Mediator Hubungan Industrial Ahli Muda', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(242, 27, 'Instruktur Ahli Muda', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(243, 27, 'ANALIS TENAGA KERJA', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(244, 27, 'ANALIS PERENCANAAN EVALUASI DAN PELAPORAN', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(245, 27, 'Analis Kebijakan Ahli Muda', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(246, 34, 'Kepala Seksi Bina Pemberdayaan', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(247, 34, 'KEPALA SEKSI KETENTERAMAN DAN KETERTIBAN', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(248, 34, 'KEPALA SUB BAGIAN UMUM DAN KEPEGAWAIAN', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(249, 34, 'PENGADMINISTRASI UMUM', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(250, 34, 'KEPALA SUB BAGIAN PERENCANAAN DAN KEUANGAN', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(251, 34, 'Kepala Seksi Bina Pelayanan', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(252, 34, 'KEPALA SEKSI BINA PEMERINTAHAN', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(253, 24, 'Kepala Sub Bagian Umum dan Kepegawaian', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(254, 24, 'Perencana Ahli Muda', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(255, 24, 'PENYULUH PANGAN', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(256, 24, 'PENGELOLA KETERSEDIAAN DAN KERAWANAN PANGAN', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(257, 24, 'PENGELOLA DATA', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(258, 24, 'ANALIS PANGAN', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(259, 24, 'Analis Ketahanan Pangan Ahli Muda', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(260, 24, 'ANALIS DATA DAN INFORMASI', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(261, 20, 'Kepala SUB BAGIAN UMUM DAN KEPEGAWAIAN', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(262, 20, 'Kepala Sub Bagian Perencanaan dan Keuangan', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(263, 20, 'Statistisi Ahli Muda', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(264, 20, 'Sandiman Ahli Muda', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(265, 20, 'Pranata Siaran Ahli Muda', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(266, 20, 'Pranata Komputer Ahli Muda', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(267, 20, 'Pranata Hubungan Masyarakat Ahli Muda', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(268, 20, 'PENGELOLA SITUS/ WEB', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(269, 20, 'PENGELOLA SISTEM DAN JARINGAN', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(270, 20, 'PENGADMINISTRASI UMUM', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(271, 20, 'PENGADMINISTRASI SANDI DAN TELEKOMUNIKASI', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(272, 5, 'KEPALA SUB BIDANG PENYUSUNAN ANGGARAN', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(273, 5, 'KEPALA SUB BIDANG PENGELOLAAN BELANJA DAERAH', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(274, 5, 'Kepala SUB BIDANG PENGELOLAAN ADMINISTRASI GAJI', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(275, 5, 'Kepala SUB BIDANG PENATAUSAHAAN ASET DAERAH', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(276, 5, 'Kepala SUB BIDANG PEMANFAATAN DAN PENGENDALIAN ASET DAERAH', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(277, 5, 'KEPALA SUB BIDANG PELAPORAN', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(278, 5, 'Kepala Sub Bidang Kebijakan Anggaran', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(279, 5, 'KEPALA SUB BIDANG AKUNTANSI', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(280, 5, 'Kepala SUB BAGIAN UMUM DAN KEPEGAWAIAN', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(281, 5, 'Perencana Ahli Muda', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(282, 5, 'PENGELOLA KEUANGAN', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(283, 5, 'PENGELOLA AKUNTANSI', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(284, 5, 'PENGADMINISTRASI UMUM', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(285, 5, 'Penata Laksana Barang Penyelia', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(286, 5, 'ANALIS SISTEM INFORMASI PELAKSANAAN ANGGARAN', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(287, 5, 'Analis Perbendaharaan Negara Ahli Muda', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(288, 5, 'ANALIS LAPORAN KEUANGAN', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(289, 5, 'Analis Keuangan Pusat dan Daerah Ahli Muda', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(290, 5, 'Analis Anggaran Ahli Muda', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(291, 16, 'KEPALA UPT METROLOGI', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(292, 16, 'Kepala Sub Bagian Umum dan Kepegawaian', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(293, 16, 'KEPALA SUB BAGIAN TATA USAHA  UPT METROLOGI', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(294, 16, 'Kepala Sub Bagian Perencanaan dan Keuangan', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(295, 16, 'PENYUSUN RENCANA BIMBINGAN TEKNIS USAHA MIKRO, KECIL, DAN MENENGAH', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(296, 16, 'Penyuluh Perindustrian dan Perdagangan Ahli Muda', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(297, 16, 'PENGELOLA KEUANGAN', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(298, 16, 'Pengawas Koperasi Ahli Muda', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(299, 16, 'PENGADMINISTRASI UMUM', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(300, 16, 'BENDAHARA', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(301, 16, 'ANALIS PERDAGANGAN', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(302, 16, 'ANALIS KOPERASI', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(303, 16, 'AHLI PERTAMA - ANALIS PERDAGANGAN', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(304, 18, 'Kepala UPTD Sistem Pengelolaan Air Limbah Domestik', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(305, 18, 'Kepala UPTD Rumah Susun Sederhana Sewa', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(306, 18, 'Kepala UPTD Peralatan dan Perbengkelan', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(307, 18, 'Kepala Sub Bagian Umum dan Kepegawaian', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(308, 18, 'Kepala SUB BAGIAN KEUANGAN', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(309, 18, 'Teknik Tata Bangunan dan Perumahan Ahli Muda', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(310, 18, 'Teknik Penyehatan Lingkungan Ahli Muda', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(311, 18, 'Teknik Pengairan Ahli Muda', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(312, 18, 'Teknik Jalan dan Jembatan Ahli Muda', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(313, 18, 'Perencana Ahli Muda', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(314, 18, 'PENYUSUN KEBUTUHAN BARANG INVENTARIS', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(315, 18, 'PENJAGA PINTU AIR', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(316, 18, 'PENGELOLA PERUMAHAN DAN PERMUKIMAN', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(317, 18, 'PENGELOLA JASA KONSTRUKSI', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(318, 18, 'PENGELOLA IRIGASI', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(319, 18, 'PENGELOLA GAJI', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(320, 18, 'PENGELOLA DATA', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(321, 18, 'PENGAWAS JASA KONSTRUKSI', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(322, 18, 'PENGAWAS IRIGASI', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(323, 18, 'PENGADMINISTRASI UMUM', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(324, 18, 'Penata Ruang Ahli Muda', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(325, 18, 'Pembina Jasa Konstruksi Ahli Muda', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(326, 18, 'PEKERJA SALURAN', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(327, 18, 'BENDAHARA', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(328, 18, 'ANALIS TATA RUANG', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(329, 18, 'ANALIS PERENCANAAN WILAYAH PERUMAHAN', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(330, 18, 'ANALIS PERENCANAAN EVALUASI DAN PELAPORAN', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(331, 18, 'ANALIS JALAN DAN JEMBATAN', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(332, 18, 'ANALIS INFRASTRUKTUR', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(333, 18, 'ANALIS BANGUNAN GEDUNG DAN PEMUKIMAN', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(334, 15, 'Kepala Subbag Umum dan Kepegawaian', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(335, 15, 'Perencana Ahli Muda', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(336, 15, 'PENYUSUN KEBUTUHAN BARANG INVENTARIS', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(337, 15, 'PENYULUH WISATA', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(338, 15, 'PENYULUH OLAHRAGA', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(339, 15, 'PENGELOLA SARANA WISATA', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(340, 15, 'PENGELOLA PROMOSI DAN INFORMASI WISATA', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(341, 15, 'PENGELOLA KEUANGAN', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(342, 15, 'PENGELOLA KEPEGAWAIAN', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(343, 15, 'PENGELOLA DATA', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(344, 15, 'PENGADMINISTRASI UMUM', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(345, 15, 'Pelatih Olahraga Ahli Muda', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(346, 15, 'Pamong Budaya Ahli Muda', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(347, 15, 'ANALIS PENGEMBANGAN SARANA DAN PRASARANA', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(348, 15, 'ANALIS KEPEMUDAAN', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(349, 15, 'ANALIS KEOLAHRAGAAN', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(350, 15, 'Analis Kebijakan Ahli Muda', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(351, 15, 'Adyatama Kepariwisataan dan Ekonomi Kreatif Ahli Muda', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(352, 29, 'Kepala SUB BAGIAN UMUM DAN KEPEGAWAIAN', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(353, 29, 'Perencana Ahli Muda', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(354, 29, 'PENGELOLA KEUANGAN', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(355, 29, 'PENGADMINISTRASI UMUM', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(356, 29, 'Penata Penanggulangan Bencana Ahli Muda', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(357, 29, 'ANALIS MITIGASI BENCANA', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(358, 29, 'Analis Keuangan Pusat dan Daerah Ahli Muda', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(359, 29, 'Analis Kebencanaan Ahli Muda', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(360, 29, 'ANALIS BENCANA', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(361, 2, 'PRANATA BARANG DAN JASA', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(362, 2, 'Perancang Peraturan Perundang-undangan Ahli Pertama', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(363, 2, 'Perancang Peraturan Perundang-undangan Ahli Muda', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(364, 2, 'PENYUSUN KEBUTUHAN SARANA DAN PRASARANA', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(365, 2, 'PENYUSUN BAHAN PENYULUHAN HUKUM', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(366, 2, 'PENGELOLA UNIT LAYANAN PENGADAAN', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(367, 2, 'PENGELOLA PENGENDALIAN, MONITORING DAN EVALUASI PEMBANGUNAN', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(368, 2, 'Pengelola Pengadaan Barang/Jasa Ahli Pertama', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(369, 2, 'Pengelola Pengadaan Barang/Jasa Ahli Muda', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(370, 2, 'PENGELOLA PEMBINAAN KETAHANAN KELUARGA', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(371, 2, 'PENGELOLA INFORMASI PRODUK HUKUM', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(372, 2, 'PENGADMINISTRASI UMUM', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(373, 2, 'Kepala Sub Bagian Protokol', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(374, 2, 'BENDAHARA', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(375, 2, 'ANALIS TATA PRAJA', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(376, 2, 'ANALIS SARANA PRASARANA IBADAH', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(377, 2, 'ANALIS PUBLIKASI', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(378, 2, 'ANALIS PERENCANAAN, EVALUASI DAN PELAPORAN', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(379, 2, 'ANALIS LEGISLASI', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(380, 2, 'ANALIS KONSULTASI DAN BANTUAN HUKUM', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(381, 2, 'ANALIS KINERJA', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(382, 2, 'ANALIS KERJASAMA', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(383, 2, 'Analis Kebijakan Ahli Muda', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(384, 2, 'ANALIS JABATAN', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(385, 2, 'Analis Hukum Ahli Muda', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(386, 2, 'ANALIS BINA KEHIDUPAN AGAMA', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(387, 2, 'ANALIS BIDANG PENGAWASAN', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(388, 21, 'Kepala UPTD PEMBIBITAN TERNAK', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(389, 21, 'Kepala UPTD Rumah Potong Hewan', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(390, 21, 'Kepala UPTD Balai Pelaksana Penyuluhan Pertanian', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(391, 21, 'Kepala UPTD Balai Benih Tanaman Pangan dan Hortikultura', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(392, 21, 'Kepala UPTD Pusat Kesehatan Hewan', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(393, 21, 'Kepala Sub Bagian Umum dan Kepegawaian', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(394, 21, 'Kepala Sub bagian Tata Usaha UPT Rumah Potong Hewan', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(395, 21, 'Kepala Sub Bagian Tata Usaha', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(396, 21, 'Perencana Ahli Muda', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(397, 21, 'Penyuluh Pertanian Terampil', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(398, 21, 'Penyuluh Pertanian Penyelia', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(399, 21, 'Penyuluh Pertanian Mahir', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(400, 21, 'Penyuluh Pertanian Ahli Pertama', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(401, 21, 'Penyuluh Pertanian Ahli Muda', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(402, 21, 'Penyuluh Pertanian Ahli Madya', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(403, 21, 'PENGELOLA TEKNOLOGI PERBENIHAN', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(404, 21, 'PENGELOLA PERLINDUNGAN TANAMAN PANGAN', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(405, 21, 'PENGAWAS MUTU BIBIT TERNAK', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(406, 21, 'PENGAWAS BIBIT TERNAK PELAKSANA', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(407, 21, 'Pengawas Benih Tanaman Ahli Muda', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(408, 21, 'Pengawas Alat Dan Mesin Pertanian Ahli Muda', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(409, 21, 'PENGADMINISTRASI UMUM', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(410, 21, 'PARAMEDIK VETERINER PELAKSANA', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(411, 21, 'Medik Veteriner Ahli Pertama', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(412, 21, 'Medik Veteriner Ahli Muda', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(413, 21, 'CALON PENYULUH PERTANIAN', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(414, 21, 'CALON PENGAWAS BENIH TANAMAN', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(415, 21, 'ANALIS PERENCANAAN, EVALUASI DAN PELAPORAN', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(416, 21, 'ANALIS PENGOLAH HASIL PERTANIAN', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(417, 21, 'ANALIS PEMASARAN HASIL PERTANIAN', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(418, 21, 'Analis Pasar Hasil Pertanian Ahli Muda', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(419, 21, 'Analis Keuangan Pusat dan Daerah Ahli Muda', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(420, 21, 'Analis Kebijakan Ahli Muda', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(421, 21, 'ANALIS ALAT DAN MESIN PERTANIAN', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(422, 21, 'AHLI PERTAMA - MEDIK VETERINER', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(423, 4, 'Kepala Sub Bagian Umum dan Kepegawaian', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(424, 4, 'Perencana Ahli Muda', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(425, 4, 'PENGELOLA KEPEGAWAIAN', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(426, 4, 'PENGELOLA DATA', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(427, 4, 'Pengawas Pemerintahan Pertama / Ahli Pertama', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(428, 4, 'Pengawas Pemerintahan Muda / Ahli Muda', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(429, 4, 'Pengawas Pemerintahan Madya / Ahli Madya', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(430, 4, 'PELAKSANA', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(431, 4, 'FUNGSIONAL UMUM', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(432, 4, 'BENDAHARA', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(433, 4, 'Auditor Ahli Pertama', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(434, 4, 'Auditor Ahli Muda', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(435, 4, 'Auditor Ahli Madya', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(436, 4, 'ANALIS PENGAWASAN', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(437, 4, 'ANALIS KINERJA', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(438, 4, 'ANALIS EVALUASI AUDIT', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(439, 4, 'AHLI PERTAMA - AUDITOR', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(440, 28, 'KEPALA SUB BAGIAN UMUM DAN KEPEGAWAIAN', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(441, 28, 'BENDAHARA', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(442, 28, 'ANALIS WAWASAN KEBANGSAAN', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(443, 28, 'ANALIS TATA USAHA', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(444, 28, 'ANALIS ORGANISASI MASYARAKAT', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(445, 28, 'Analis Keuangan Pusat dan Daerah Ahli Muda', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(446, 28, 'Analis Kebijakan Ahli Muda', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(447, 19, 'Kepala UPTD Perikanan Budidaya Air Tawar Wilayah II', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(448, 19, 'Kepala UPTD Perikanan Budidaya Air Tawar Wilayah I', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(449, 40, 'Kepala UPT Pengembangan Budidaya Ikan', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(450, 40, 'Kepala Sub Bagian Umum dan Kepegawaian', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(451, 19, 'Kepala Sub Bagian Tata Usaha', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(452, 40, 'Kepala Sub Bagian Tata Usaha', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(453, 19, 'Perencana Ahli Muda', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(454, 19, 'PENGELOLA PENGEMBANGAN BUDIDAYA DAN PEMASARAN PERIKANAN', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(455, 19, 'PENGELOLA PENGAWASAN PENGOLAHAN, PENGANGKUTAN DAN PEMASARAN IKAN', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(456, 40, 'PENGELOLA PENGAWASAN PEMBUDIDAYAAN IKAN', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(457, 19, 'Pengawas Perikanan Ahli Muda', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(458, 40, 'Pengawas Perikanan Ahli Muda', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(459, 40, 'PELAKSANA', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(460, 40, 'ANALIS KESEHATAN IKAN DAN LINGKUNGAN', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(461, 40, 'ANALIS BUDIDAYA PERIKANAN', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(462, 22, 'Kepala Subbag Perencanaan dan Keuangan', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(463, 22, 'Kepala SUB BAGIAN UMUM DAN KEPEGAWAIAN', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(464, 22, 'PENYUSUN PEMBINAAN INSTITUSI MASYARAKAT PEDESAAN', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(465, 22, 'PENGGERAK SWADAYA MASYARAKAT PERTAMA', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(466, 22, 'Penggerak Swadaya Masyarakat Ahli Muda', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(467, 22, 'PENGGERAK SUMBER DAYA MASYARAKAT', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(468, 22, 'PENGELOLA DATA', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(469, 22, 'PENGADMINISTRASI UMUM', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(470, 22, 'ANALIS DESA/ KELURAHAN', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(471, 13, 'Kepala Subbag Umum dan Kepegawaian', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(472, 13, 'Kepala Sub Bagian Tata Usaha', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(473, 13, 'Kepala UPTD Pemberdayaan Perempuan dan Perlindungan Anak', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(474, 13, 'Perencana Ahli Muda', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(475, 13, 'PENYUSUN KEBUTUHAN BARANG INVENTARIS', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(476, 13, 'PENGELOLA DATA', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL);
INSERT INTO `jabatan` (`id`, `opd_id`, `nama_jabatan`, `tupoksi`, `edited_by`, `created_at`, `updated_at`, `eselon`) VALUES
(477, 13, 'PENGADMINISTRASI UMUM', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(478, 13, 'Penata Kependudukan dan Keluarga Berencana Ahli Muda', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(479, 13, 'Analis Kebijakan Ahli Muda', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(480, 14, 'Kepala SUBBAG UMUM DAN KEPEGAWAIAN', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(481, 14, 'Kepala Subbag Perencanaan dan Keuangan', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(482, 14, 'PENGELOLA SARANA DAN PRASARANA', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(483, 14, 'PENGELOLA DATA', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(484, 14, 'BENDAHARA', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(485, 14, 'ANALIS PERENCANAAN EVALUASI DAN PELAPORAN', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(486, 14, 'Administrator Database Kependudukan Ahli Muda', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(487, 3, 'KEPALA SUB BAGIAN TATA USAHA DAN KEPEGAWAIAN', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(488, 3, 'PRANATA KOMPUTER', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(489, 3, 'Perancang Peraturan Perundang-undangan Ahli Pertama', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(490, 3, 'Perancang Peraturan Perundang-undangan Ahli Muda', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(491, 3, 'PENGOLAH DATA DUKUNGAN PENGAWASAN', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(492, 3, 'PENGELOLA PERSIDANGAN', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(493, 3, 'PENGELOLA KEUANGAN', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(494, 3, 'PENGELOLA KEPEGAWAIAN', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(495, 3, 'PENGADMINISTRASI UMUM', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(496, 3, 'CALON PRANATA KOMPUTER PERTAMA', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(497, 3, 'Analis Kebijakan Ahli Muda', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(498, 3, 'Analis Hukum Ahli Muda', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(499, 3, 'Analis Anggaran Ahli Muda', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(500, 9, 'KEPALA SUB BAGIAN UMUM KEPEGAWAIAN', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(501, 9, 'KEPALA SUB BAGIAN PERENCANAAN DAN KEUANGAN', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(502, 9, 'Kepala SEKSI SATUAN LINMAS', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(503, 9, 'KEPALA SEKSI PENYELIDIKAN DAN PENYIDIKAN', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(504, 9, 'KEPALA SEKSI PELATIHAN DASAR', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(505, 9, 'Kepala SEKSI OPERASIONAL PENGENDALIAN', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(506, 9, 'KEPALA SEKSI KERJASAMA', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(507, 9, 'Kepala Seksi Kemitraan dan Pembinaan Penyidik Pegawai Negeri Sipil (PPNS)', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(508, 9, 'Polisi Pamong Praja Ahli Muda', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(509, 9, 'PENGELOLA DATA', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(510, 9, 'PENGADMINISTRASI UMUM', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(511, 9, 'PENGADMINISTRASI KEUANGAN', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(512, 9, 'BENDAHARA', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(513, 9, 'Analis Kebijakan Ahli Muda', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(514, 8, 'KEPALA SUB BAGIAN UMUM DAN KEPEGAWAIAN', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(515, 8, 'KEPALA SUB BAGIAN PERENCANAAN DAN KEUANGAN', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(516, 8, 'PENYUSUN RENCANA MUTASI', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(517, 8, 'PENGELOLA SISTEM INFORMASI MANAJEMEN KEPEGAWAIAN', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(518, 8, 'PENGELOLA PENILAIAN KINERJA PEGAWAI', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(519, 8, 'PENGELOLA FORMASI DAN PENGADAAN PEGAWAI', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(520, 8, 'PENGELOLA DATA', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(521, 8, 'PENGADMINISTRASI UMUM', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(522, 8, 'ANALIS TATA USAHA', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(523, 8, 'Analis Sumber Daya Manusia Aparatur Ahli Muda', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(524, 8, 'ANALIS PERENCANAAN EVALUASI DAN PELAPORAN', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(525, 8, 'ANALIS PENGEMBANGAN KOMPETENSI', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(526, 8, 'ANALIS PENGEMBANGAN KARIR', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(527, 8, 'ANALIS KINERJA', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(528, 8, 'PENYUSUN KEBUTUHAN BARANG INVENTARIS', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(529, 12, 'Kepala Sub Bagian Umum dan Kepegawaian', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(530, 12, 'Perencana Ahli Muda', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(531, 12, 'Penyuluh Sosial Ahli Muda', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(532, 12, 'PENGGERAK SWADAYA MASYARAKAT PERTAMA', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(533, 12, 'Penggerak Swadaya Masyarakat Ahli Muda', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(534, 12, 'PENGELOLA DATA', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(535, 12, 'PENGADMINISTRASI UMUM', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(536, 12, 'Pekerja Sosial Ahli Muda', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(537, 12, 'ANALIS REHABILITASI MASALAH SOSIAL', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(538, 12, 'ANALIS PERENCANAAN, EVALUASI DAN PELAPORAN', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(539, 7, 'Kepala SUB BAGIAN UMUM DAN KEPEGAWAIAN', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(540, 7, 'Perencana Ahli Muda', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(541, 7, 'PENYUSUN KEBUTUHAN BARANG INVENTARIS', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(542, 7, 'PENGELOLA DATA', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(543, 7, 'Peneliti Ahli Muda', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(544, 7, 'BENDAHARA', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(545, 7, 'ANALIS PROGRAM PEMBANGUNAN', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(546, 7, 'ANALIS PERENCANAAN, EVALUASI DAN PELAPORAN', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(547, 7, 'Analis Keuangan Pusat dan Daerah Ahli Muda', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(548, 26, 'Kepala SUB BAGIAN UMUM DAN KEPEGAWAIAN', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(549, 26, 'Pustakawan Ahli Muda', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(550, 26, 'PRANATA KEARSIPAN', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(551, 26, 'Perencana Ahli Muda', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(552, 26, 'PENGELOLA SARANA DAN PRASARANA', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(553, 26, 'PENGELOLA PERPUSTAKAAN', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(554, 26, 'PENGELOLA KEPEGAWAIAN', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(555, 26, 'PENGADMINISTRASI PROGRAM DAN KERJASAMA', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(556, 26, 'CALON ARSIPARIS PELAKSANA', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(557, 26, 'CALON ARSIPARIS', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(558, 26, 'Arsiparis Ahli Muda', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(559, 25, 'Kepala Subbag Umum dan Kepegawaian', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(560, 25, 'PRANATA KOMPUTER PELAKSANA', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(561, 25, 'Perencana Ahli Muda', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(562, 25, 'PENGELOLA SARANA DAN PRASARANA', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(563, 25, 'PENGELOLA DOKUMEN PERIZINAN', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(564, 25, 'PENGADMINISTRASI UMUM', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(565, 25, 'ANALIS PEMBINAAN KELEMBAGAAN INVESTASI', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(566, 25, 'ANALIS KERJASAMA DAN PERMODALAN', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(567, 25, 'Analis Kebijakan Ahli Muda', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(568, 25, 'Analis Kebijakan Ahli Madya', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(569, 25, 'ANALIS INVESTASI DAN PERMODALAN USAHA', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(570, 25, 'ANALIS ADAPTASI DAMPAK PERUBAHAN IKLIM', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(571, 17, 'KEPALA UPTD PENGELOLA PRASARANA TEKNIS PERHUBUNGAN', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(572, 17, 'KEPALA UPTD PENGELOLA PRASARANA PERHUBUNGAN', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(573, 17, 'KEPALA SUB BAGIAN UMUM DAN KEPEGAWAIAN', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(574, 17, 'KEPALA SUB BAGIAN TATA USAHA', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(575, 17, 'Kepala Sub Bagian Perencanaan dan Keuangan', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(576, 17, 'Kepala Seksi Pengendalian Operasional', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(577, 17, 'Kepala Seksi Manajemen Rekayasa Lalu Lintas', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(578, 17, 'KEPALA SEKSI ANGKUTAN ORANG', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(579, 17, 'KEPALA SEKSI ANGKUTAN BARANG', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(580, 17, 'PRANATA STANDAR KESELAMATAN', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(581, 17, 'PENGUJI KENDARAAN BERMOTOR PELAKSANA', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(582, 17, 'PENGELOLA TERMINAL', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(583, 17, 'PENGELOLA SISTEM INFORMASI SARANA DAN PRASARANA JALAN', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(584, 17, 'PENGELOLA REKAYASA LALU LINTAS', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(585, 17, 'PENGELOLA PENGAWASAN LLAJ', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(586, 17, 'PELAKSANA/TERAMPIL - PENGUJI KENDARAAN BERMOTOR', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(587, 17, 'ANALIS SARANA DAN PRASARANA', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(588, 17, 'ANALIS PENGEMBANGAN SARANA DAN PRASARANA', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(589, 17, 'Analis Kebijakan Ahli Muda', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(590, 6, 'KEPALA SUB BIDANG PENGOLAHAN DATA DAN DISTRIBUSI', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(591, 6, 'KEPALA SUB BIDANG PENGENDALIAN DAN OPERASIONAL', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(592, 6, 'KEPALA SUB BIDANG PENGEMBANGAN DAN EVALUASI', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(593, 6, 'KEPALA SUB BIDANG PENERIMAAN DAN PENAGIHAN', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(594, 6, 'KEPALA SUB BAGIAN UMUM DAN KEPEGAWAIAN', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(595, 6, 'Perencana Ahli Muda', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(596, 6, 'Penilai Pajak Ahli Muda', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(597, 6, 'PENGELOLA PENDAFTARAN DAN PENDATAAN PAJAK/ RETRIBUSI', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(598, 6, 'PENGADMINISTRASI UMUM', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(599, 6, 'PEMBANTU BENDAHARA PENGELUARAN', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(600, 6, 'BENDAHARA PENGELUARAN', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(601, 6, 'ANALIS PERENCANAAN, EVALUASI DAN PELAPORAN', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(602, 6, 'ANALIS PAJAK DAN RETRIBUSI DAERAH', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(603, 6, 'ANALIS MONITORING DAN EVALUASI KEBIJAKAN PAJAK DAERAH DAN RETRIBUSI DAERAH', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(604, 6, 'ANALIS KEBIJAKAN PAJAK DAN RETRIBUSI DAERAH', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(605, 6, 'Analis Kebijakan Ahli Muda', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(606, 6, 'ANALIS DATA DAN INFORMASI', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(607, 31, 'Kepala Seksi Bina Pelayanan', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(608, 31, 'Kepala Seksi Bina Pemberdayaan', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(609, 31, 'Kepala Seksi Bina Pemerintahan', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(610, 31, 'Kepala SEKSI KETENTERAMAN DAN KETERTIBAN', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(611, 31, 'KEPALA SUB BAGIAN PERENCANAAN DAN KEUANGAN', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(612, 31, 'KEPALA SUB BAGIAN UMUM DAN KEPEGAWAIAN', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(613, 31, 'PENGADMINISTRASI UMUM', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(614, 31, 'PENGELOLA SISTEM INFORMASI KEPENDUDUKAN', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(615, 31, 'PENGOLAH DATA PELAYANAN', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(616, 31, 'PENYULUH KEAMANAN MASYARAKAT', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(617, 33, 'KEPALA SUB BAGIAN PERENCANAAN DAN KEUANGAN', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(618, 33, 'KEPALA SUB BAGIAN UMUM DAN KEPEGAWAIAN', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(619, 33, 'Kepala SEKSI KETENTERAMAN DAN KETERTIBAN', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(620, 33, 'Kepala Seksi Bina Pemerintahan', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(621, 33, 'Kepala Seksi Bina Pelayanan', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(622, 33, 'Kepala Seksi Bina Pemberdayaan', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(623, 33, 'PENGADMINISTRASI UMUM', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(624, 39, 'Kepala SEKSI PEMBERDAYAAN MASYARAKAT', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(625, 39, 'Kepala SUBBAG PERENCANAAN DAN KEUANGAN', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(626, 39, 'Kepala Seksi Bina Pelayanan', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(627, 39, 'Kepala SEKSI KETENTERAMAN DAN KETERTIBAN', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(628, 39, 'Kepala Seksi Bina Pemerintahan', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(629, 39, 'Kepala SUB BAGIAN UMUM DAN KEPEGAWAIAN', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(630, 36, 'Kepala Seksi Bina Pelayanan', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(631, 36, 'Kepala Seksi Bina Pemberdayaan', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(632, 36, 'Kepala Seksi Bina Pemerintahan', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(633, 36, 'Kepala SEKSI KETENTERAMAN DAN KETERTIBAN', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(634, 36, 'KEPALA SUB BAGIAN PERENCANAAN DAN KEUANGAN', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(635, 36, 'KEPALA SUB BAGIAN UMUM DAN KEPEGAWAIAN', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(636, 36, 'ANALIS DESA/ KELURAHAN', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(637, 36, 'PENGADMINISTRASI PEMERINTAHAN', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(638, 36, 'PENGADMINISTRASI UMUM', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(639, 37, 'KEPALA SUB BAGIAN UMUM DAN KEPEGAWAIAN', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(640, 37, 'KEPALA SUB BAGIAN PERENCANAAN DAN KEUANGAN', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(641, 37, 'Kepala SEKSI KETENTERAMAN DAN KETERTIBAN', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(642, 37, 'Kepala Seksi Bina Pemerintahan', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(643, 37, 'Kepala Seksi Bina Pemberdayaan', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(644, 37, 'Kepala Seksi Bina Pelayanan', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(645, 37, 'PENGADMINISTRASI PEMERINTAHAN', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(646, 37, 'PENGADMINISTRASI UMUM', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(647, 35, 'Kepala Seksi Bina Pelayanan', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(648, 35, 'Kepala Seksi Bina Pemberdayaan', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(649, 35, 'Kepala Seksi Bina Pemerintahan', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(650, 35, 'Kepala SEKSI KETENTERAMAN DAN KETERTIBAN', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(651, 35, 'Kepala SUB BAGIAN PERENCANAAN DAN KEUANGAN', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(652, 35, 'KEPALA SUB BAGIAN UMUM DAN KEPEGAWAIAN', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(653, 35, 'BENDAHARA', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(654, 35, 'PENGADMINISTRASI', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(655, 35, 'PENGADMINISTRASI PEMERINTAHAN', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(656, 35, 'PENGADMINISTRASI PERTANAHAN', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(657, 35, 'PENGADMINISTRASI UMUM', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(658, 32, 'KEPALA SEKSI BINA PELAYANAN', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(659, 32, 'KEPALA SEKSI BINA PEMBERDAYAAN', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(660, 32, 'KEPALA SEKSI BINA PEMERINTAHAN', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(661, 32, 'KEPALA SEKSI KETENTERAMAN DAN KETERTIBAN', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(662, 32, 'KEPALA SUB BAGIAN PERENCANAAN DAN KEUANGAN', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(663, 32, 'KEPALA SUB BAGIAN UMUM DAN KEPEGAWAIAN', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(664, 32, 'ANALIS PENYULUHAN DAN LAYANAN INFORMASI', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(665, 32, 'BENDAHARA', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(666, 32, 'PENGADMINISTRASI PEMERINTAHAN', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(667, 32, 'PENGADMINISTRASI UMUM', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(668, 32, 'PENGELOLA KEUANGAN', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(669, 32, 'PENGELOLA SISTEM INFORMASI KEPENDUDUKAN', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(670, 32, 'PENGOLAH DATA PELAYANAN', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(671, 32, 'PENYULUH PEMBERDAYAAN MASYARAKAT DESA/ KELURAHAN', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(672, 38, 'Kepala Seksi Bina Pelayanan', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(673, 38, 'Kepala Seksi Bina Pemberdayaan', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(674, 38, 'Kepala Seksi Bina Pemerintahan', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(675, 38, 'Kepala SEKSI KETENTERAMAN DAN KETERTIBAN', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(676, 38, 'KEPALA SUB BAGIAN PERENCANAAN DAN KEUANGAN', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(677, 38, 'KEPALA SUB BAGIAN UMUM DAN KEPEGAWAIAN', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(678, 38, 'PENGADMINISTRASI PEMERINTAHAN', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(679, 38, 'PENGADMINISTRASI PERTANAHAN', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(680, 38, 'PENGADMINISTRASI UMUM', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(681, 23, 'Pengadministrasi Umum', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(682, 27, 'ANALIS DATA DAN INFORMASI', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(683, 3, 'Analis Laporan Realisasi Anggaran', ' ', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(684, 4, 'Inspektur Pembantu Wilayah I', NULL, 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(685, 24, 'Kepala Bidang Distribusi dan Cadangan Pangan', NULL, NULL, NULL, NULL, NULL),
(686, 41, 'Lurah Pajaresuk', NULL, NULL, NULL, NULL, NULL),
(687, 42, 'Lurah Pringsewu Barat', NULL, NULL, NULL, NULL, NULL),
(688, 43, 'Lurah Pringsewu Selatan', NULL, NULL, NULL, NULL, NULL),
(689, 44, 'Lurah Pringsewu Timur', NULL, NULL, NULL, NULL, NULL),
(690, 45, 'Lurah Pringsewu Utara', NULL, NULL, NULL, NULL, NULL),
(691, 41, 'Sekretaris Lurah Pajaresuk\r', NULL, NULL, NULL, NULL, NULL),
(692, 41, 'Kepala SEKSI KETERTIBAN DAN KETENTERAMAN UMUM\r', NULL, NULL, NULL, NULL, NULL),
(693, 41, 'Kepala Seksi Pemberdayaan\r', NULL, NULL, NULL, NULL, NULL),
(694, 41, 'Kepala Seksi Pemerintahan dan Pendapatan\r', NULL, NULL, NULL, NULL, NULL),
(695, 42, 'Sekretaris Lurah Pringsewu Barat\r', NULL, NULL, NULL, NULL, NULL),
(696, 42, 'KEPALA SEKSI KETENTRAMAN DAN KETERTIBAN UMUM\r', NULL, NULL, NULL, NULL, NULL),
(697, 42, 'KEPALA SEKSI KETENTRAMAN DAN KETERTIBAN UMUM\r', NULL, NULL, NULL, NULL, NULL),
(698, 42, 'Kepala Seksi Pemerintahan dan Pendapatan\r', NULL, NULL, NULL, NULL, NULL),
(699, 42, 'PENGADMINISTRASI UMUM\r', NULL, NULL, NULL, NULL, NULL),
(700, 42, 'PETUGAS KEAMANAN\r', NULL, NULL, NULL, NULL, NULL),
(701, 43, 'Sekretaris Lurah Pringsewu Selatan\r', NULL, NULL, NULL, NULL, NULL),
(702, 43, 'Kepala SEKSI KETENTRAMAN DAN KETERTIBAN UMUM\r', NULL, NULL, NULL, NULL, NULL),
(703, 43, 'Kepala Seksi Pemberdayaan\r', NULL, NULL, NULL, NULL, NULL),
(704, 43, 'Kepala Seksi Pemerintahan dan Pendapatan\r', NULL, NULL, NULL, NULL, NULL),
(705, 43, 'PENGADMINISTRASI PEMERINTAHAN\r', NULL, NULL, NULL, NULL, NULL),
(706, 43, 'PENGADMINISTRASI UMUM\r', NULL, NULL, NULL, NULL, NULL),
(707, 44, 'Sekretaris Lurah Pringsewu Timur\r', NULL, NULL, NULL, NULL, NULL),
(708, 44, 'Kepala SEKSI KETERTIBAN DAN KETENTERAMAN\r', NULL, NULL, NULL, NULL, NULL),
(709, 44, 'Kepala Seksi Pemberdayaan\r', NULL, NULL, NULL, NULL, NULL),
(710, 44, 'Kepala Seksi Pemerintahan dan Pendapatan\r', NULL, NULL, NULL, NULL, NULL),
(711, 45, 'Sekretaris Lurah Pringsewu Utara\r', NULL, NULL, NULL, NULL, NULL),
(712, 45, 'KEPALA SEKSI KETENTRAMAN DAN KETERTIBAN UMUM\r', NULL, NULL, NULL, NULL, NULL),
(713, 45, 'Kepala Seksi Pemberdayaan\r', NULL, NULL, NULL, NULL, NULL),
(714, 45, 'Kepala Seksi Pemerintahan dan Pendapatan\r', NULL, NULL, NULL, NULL, NULL),
(715, 45, 'PENGADMINISTRASI PEMERINTAHAN\r', NULL, NULL, NULL, NULL, NULL),
(716, 45, 'PENGADMINISTRASI UMUM\r', NULL, NULL, NULL, NULL, NULL),
(717, 11, 'Pengelola Pelayanan kesehatan', NULL, NULL, NULL, NULL, NULL),
(718, 2, 'Analis Protokol', NULL, NULL, NULL, NULL, NULL),
(719, 10, 'Petugas Keamanan', NULL, NULL, NULL, NULL, NULL),
(720, 10, 'Pamong Belajar Madya', NULL, NULL, NULL, NULL, NULL),
(721, 10, 'Analis Tata Usaha', NULL, NULL, NULL, NULL, NULL),
(722, 10, 'Pengadministrasi Kesiswaan', NULL, NULL, NULL, NULL, NULL),
(723, 10, 'Pengadministrasi Keuangan', NULL, NULL, NULL, NULL, NULL),
(724, 10, 'Pengadministrasi Sarana dan Prasarana', NULL, NULL, NULL, NULL, NULL),
(725, 39, 'Pengadministrasi Umum', NULL, NULL, NULL, NULL, NULL),
(726, 4, 'Analis Perencanaan', NULL, NULL, NULL, NULL, NULL),
(727, 11, 'Pengelola Rujukan Kesehatan', NULL, NULL, NULL, NULL, NULL),
(728, 11, 'Analis Rencana Program dan Kegiatan', NULL, NULL, NULL, NULL, NULL),
(729, 9, 'Pelaksanaan pada Satpol PP', NULL, NULL, NULL, NULL, NULL),
(730, 10, 'Kepala Sekolah', NULL, NULL, NULL, NULL, NULL),
(731, 10, 'Guru Kelas', NULL, NULL, NULL, NULL, NULL),
(732, 10, 'Guru Penjas', NULL, NULL, NULL, NULL, NULL),
(733, 10, 'Guru PAI', NULL, NULL, NULL, NULL, NULL),
(734, 10, 'Penjaga', NULL, NULL, NULL, NULL, NULL),
(735, 10, 'Guru Bahasa Inggris', NULL, NULL, NULL, NULL, NULL),
(736, 10, 'Guru Matematika', NULL, NULL, NULL, NULL, NULL),
(737, 10, 'Guru Bahasa Indonesia ', NULL, NULL, NULL, NULL, NULL),
(738, 10, 'Guru PPKn', NULL, NULL, NULL, NULL, NULL),
(739, 10, 'Guru Bimbingan Konseling', NULL, NULL, NULL, NULL, NULL),
(740, 10, 'Guru IPA', NULL, NULL, NULL, NULL, NULL),
(741, 10, 'Guru IPS', NULL, NULL, NULL, NULL, NULL),
(742, 10, 'Guru Bahasa Lampung', NULL, NULL, NULL, NULL, NULL),
(743, 10, 'Guru TIK', NULL, NULL, NULL, NULL, NULL),
(744, 10, 'Guru Seni Budaya', NULL, NULL, NULL, NULL, NULL),
(745, 10, 'Guru Agama Katholik', NULL, NULL, NULL, NULL, NULL),
(746, 10, 'Guru (PPPK) Prakarya ', NULL, NULL, NULL, NULL, NULL),
(747, 10, 'Analis Tata Usaha', NULL, NULL, NULL, NULL, NULL),
(748, 10, 'Pengadministrasi Umum', NULL, NULL, NULL, NULL, NULL),
(749, 10, 'Guru Agama Buddha', NULL, NULL, NULL, NULL, NULL),
(750, 10, 'Pendidikan Agama Hindu', NULL, NULL, NULL, NULL, NULL),
(751, 17, 'Pengawas Satuan Pelayanan', NULL, NULL, NULL, NULL, NULL),
(752, 17, 'Penelaah Teknis Kebijakan', NULL, NULL, NULL, NULL, NULL),
(753, 17, 'Petugas Sarana dan Prasarana', NULL, NULL, NULL, NULL, NULL),
(754, 10, 'Fasilitator Promosi', NULL, NULL, NULL, NULL, NULL),
(755, 7, 'Pengelola Bahan Perencanaan', NULL, NULL, NULL, NULL, NULL),
(756, 20, 'Analisis Data dan Informasi', NULL, NULL, NULL, NULL, NULL),
(757, 21, 'Penata Dokumen Hasil Produksi', NULL, NULL, NULL, NULL, NULL),
(800, 2, 'Kelompok Jabatan Fungsional - Sekretariat Daerah ', NULL, NULL, NULL, NULL, NULL),
(801, 3, 'Kelompok Jabatan Fungsional - Sekretariat DPRD ', NULL, NULL, NULL, NULL, NULL),
(802, 5, 'Kelompok Jabatan Fungsional - Badan Pengelolaan Keuangan Dan Aset Daerah ', NULL, NULL, NULL, NULL, NULL),
(803, 6, 'Kelompok Jabatan Fungsional - Badan Pendapatan Daerah ', NULL, NULL, NULL, NULL, NULL),
(804, 7, 'Kelompok Jabatan Fungsional - Badan Perencanaan Pembangunan Daerah ', NULL, NULL, NULL, NULL, NULL),
(805, 8, 'Kelompok Jabatan Fungsional - Badan Kepegawaian Dan Pengembangan Sumberdaya Manusia ', NULL, NULL, NULL, NULL, NULL),
(808, 12, 'Kelompok Jabatan Fungsional - Dinas Sosial ', NULL, NULL, NULL, NULL, NULL),
(809, 14, 'Kelompok Jabatan Fungsional - Dinas Kependudukan Dan Pencatatan Sipil ', NULL, NULL, NULL, NULL, NULL),
(813, 16, 'Kelompok Jabatan Fungsional - Dinas Koperasi, Usaha Kecil Dan Menengah, Perdagangan Dan Perindustrian', NULL, NULL, NULL, NULL, NULL),
(814, 18, 'Kelompok Jabatan Fungsional - Dinas Pekerjaan Umum Dan Perumahan Rakyat ', NULL, NULL, NULL, NULL, NULL),
(817, 20, 'Kelompok Jabatan Fungsional - Dinas Komunikasi Dan Informatika ', NULL, NULL, NULL, NULL, NULL),
(819, 21, 'Kelompok Jabatan Fungsional - Dinas Pertanian ', NULL, NULL, NULL, NULL, NULL),
(821, 23, 'Kelompok Jabatan Fungsional - Dinas Lingkungan Hidup ', NULL, NULL, NULL, NULL, NULL),
(822, 25, 'Kelompok Jabatan Fungsional - Dinas Penanaman Modal Dan Pelayanan Terpadu Satu Pintu ', NULL, NULL, NULL, NULL, NULL),
(824, 26, 'Kelompok Jabatan Fungsional - Dinas Perpustakaan Dan Kearsipan ', NULL, NULL, NULL, NULL, NULL),
(826, 26, 'Kelompok Jabatan Fungsional - Dinas Perpustakaan Dan Kearsipan ', NULL, NULL, NULL, NULL, NULL),
(827, 27, 'Kelompok Jabatan Fungsional - Dinas Tenaga Kerja Dan Transmigrasi ', NULL, NULL, NULL, NULL, NULL),
(828, 28, 'Kelompok Jabatan Fungsional - Badan Kesatuan Bangsa Dan Politik ', NULL, NULL, NULL, NULL, NULL),
(829, 29, 'Kelompok Jabatan Fungsional - Badan Penanggulangan Bencana Daerah ', NULL, NULL, NULL, NULL, NULL),
(830, 9, 'PRANATA TRANTIBUM', NULL, NULL, NULL, NULL, NULL),
(831, 11, 'PENGELOLA LAYANAN OPERASIONAL', NULL, NULL, NULL, NULL, NULL),
(832, 11, 'PENGADMINISTRASI PERKANTORAN', NULL, NULL, NULL, NULL, NULL),
(833, 11, 'PENATA LAYANAN OPERASIONAL', NULL, NULL, NULL, NULL, NULL),
(834, 11, 'PENGELOLA UMUM OPERASIONAL', NULL, NULL, NULL, NULL, NULL),
(835, 18, 'PENATA LAYANAN OPERASIONAL', NULL, NULL, NULL, NULL, NULL),
(836, 18, 'PENGADMINISTRASI PERKANTORAN', NULL, NULL, NULL, NULL, NULL),
(837, 24, 'PENGADMINISTRASI PERKANTORAN', NULL, NULL, NULL, NULL, NULL),
(838, 24, 'PENATA LAYANAN OPERASIONAL', NULL, NULL, NULL, NULL, NULL),
(839, 17, 'PENATA LAYANAN OPERASIONAL', NULL, NULL, NULL, NULL, NULL),
(840, 17, 'PENGADMINISTRASI PERKANTORAN', NULL, NULL, NULL, NULL, NULL),
(841, 17, 'PENGELOLA UMUM OPERASIONAL', NULL, NULL, NULL, NULL, NULL),
(842, 17, 'OPERATOR LAYANAN OPERASIONAL', NULL, NULL, NULL, NULL, NULL),
(843, 17, 'PENGELOLA LAYANAN OPERASIONAL', NULL, NULL, NULL, NULL, NULL),
(844, 16, 'PENATA LAYANAN OPERASIONAL', NULL, NULL, NULL, NULL, NULL),
(845, 16, 'PENGELOLA UMUM OPERASIONAL', NULL, NULL, NULL, NULL, NULL),
(846, 16, 'PENGADMINISTRASI PERKANTORAN', NULL, NULL, NULL, NULL, NULL),
(847, 16, 'PENGELOLA LAYANAN OPERASIONAL', NULL, NULL, NULL, NULL, NULL),
(848, 21, 'Penata Layanan Operasional', NULL, NULL, NULL, NULL, NULL),
(849, 21, 'Operator Layanan Operasional', NULL, NULL, NULL, NULL, NULL),
(850, 21, 'Pengelola Layanan Operasional', NULL, NULL, NULL, NULL, NULL),
(851, 27, 'Pengadministrasi perkantoran', NULL, NULL, NULL, NULL, NULL),
(852, 27, 'Penata layanan operrasional', NULL, NULL, NULL, NULL, NULL),
(853, 17, 'Pengelola layanan Operasional', NULL, NULL, NULL, NULL, NULL),
(854, 20, 'Pranata Komputer Ahli Pertama', NULL, NULL, NULL, NULL, NULL),
(855, 20, 'Pranata Hubungan Masyarakat Ahli Pertama', NULL, NULL, NULL, NULL, NULL),
(1747, 2, 'Analis Hubungan Masyarakat', NULL, NULL, NULL, NULL, NULL),
(1748, 3, 'ANALIS PRODUK HUKUM', NULL, 0, NULL, NULL, NULL),
(1749, 10, 'Analis Sarana Pembinaan Pendidikan Masyarakat', NULL, 0, NULL, NULL, NULL),
(1750, 3, 'Analis Peraturan Perundang-undangan', NULL, NULL, NULL, NULL, NULL),
(1751, 20, 'Analis Statik', NULL, 0, NULL, NULL, NULL),
(1752, 37, 'Analis Pelayanan', NULL, NULL, NULL, NULL, NULL),
(1753, 11, 'Pelayanan Kesehatan', NULL, NULL, NULL, NULL, NULL),
(1754, 28, 'CPNS KEMENTERIAN DALAM NEGERI', NULL, NULL, NULL, NULL, NULL),
(1755, 23, 'Pengelolaan Lingkungan Bidang Pengelolaan Sampah, Limbah B3 dan Pengendalian Pencemaran', NULL, NULL, NULL, NULL, NULL),
(1756, 4, 'Penyusun Kebutuhan Barang Inventaris', NULL, NULL, NULL, NULL, NULL),
(1757, 5, 'Penyusun Kebutuhan Barang Inventaris', NULL, NULL, NULL, NULL, NULL),
(1758, 18, 'Teknik Tata Bangunan dan Perumahan Ahli Muda', NULL, NULL, NULL, NULL, NULL),
(1759, 11, 'Pengelola Data Bidang Pelayanan Kesehatan', NULL, NULL, NULL, NULL, NULL),
(1760, 11, 'Pengelola Program Gizi', NULL, NULL, NULL, NULL, NULL),
(1761, 27, 'Pengantar Kerja Ahli Muda', NULL, NULL, NULL, NULL, NULL),
(1762, 5, 'Penata Lapoaran Keuangan Pada Bidang Akutansi', NULL, NULL, NULL, NULL, NULL),
(1763, 11, 'Pengolola Program dan Kegiatan pada Sub Bagian Perencanaan', NULL, NULL, NULL, NULL, NULL),
(1764, 11, 'Pengelola Data dan Informasi', NULL, NULL, NULL, NULL, NULL),
(1765, 11, 'Pengelola Kefarmasian', NULL, NULL, NULL, NULL, NULL),
(1766, 7, ' Penelaah Teknis Kebijakan', NULL, NULL, NULL, NULL, NULL),
(1767, 17, 'PETUGAS TRANSPORTASI DARAT', NULL, NULL, NULL, NULL, NULL),
(1768, 14, 'Administrator Database Kependudukan Ahli Pertama', NULL, NULL, NULL, NULL, NULL),
(1769, 28, 'Analis Kebijakan Ahli Pertama', NULL, NULL, NULL, NULL, NULL),
(1770, 32, 'Pranata Komputer Terampil', NULL, NULL, NULL, NULL, NULL),
(1771, 4, 'Auditor Ahli Pratama', NULL, NULL, NULL, NULL, NULL),
(1772, 4, 'Pengawas Penyelenggara Urusan', NULL, NULL, NULL, NULL, NULL),
(1773, 2, 'Pengelola Pengadaan Barang/Jasa Ahli Pertama', NULL, NULL, NULL, NULL, NULL),
(1774, 12, 'Punyuluh Sosial Ahli Pertama', NULL, NULL, NULL, NULL, NULL),
(1775, 16, 'Pengawas Koperasi Ahli Pertama', NULL, NULL, NULL, NULL, NULL),
(1776, 16, 'Penyuluh Perindustrian dan Perdagangan Ahli Pertama', NULL, NULL, NULL, NULL, NULL),
(1777, 16, 'Penera Ahli Pertama', NULL, NULL, NULL, NULL, NULL),
(1778, 16, 'Pengawas Perdagangan Ahli Pertama', NULL, NULL, NULL, NULL, NULL),
(1779, 27, 'Pengantar Kerja Ahli Pertama', NULL, NULL, NULL, NULL, NULL),
(1780, 5, 'Penata Laksana Barang Terampil', NULL, NULL, NULL, NULL, NULL),
(1781, 4, 'Auditor Ahli Pertama', NULL, NULL, NULL, NULL, NULL),
(1782, 7, 'Perencana Ahli Pertama', NULL, NULL, NULL, NULL, NULL),
(1783, 37, 'Pranata Komputer Trampil', NULL, NULL, NULL, NULL, NULL),
(1784, 6, 'Pranata Komputer Ahli Pertama', NULL, NULL, NULL, NULL, NULL),
(1785, 18, 'Penata Laksana Barang Terampil', NULL, NULL, NULL, NULL, NULL),
(1786, 18, 'Penata Ruang Ahli Pertama', NULL, NULL, NULL, NULL, NULL),
(1787, 25, 'Penata Kelola Penanaman Modal', NULL, NULL, NULL, NULL, NULL),
(1788, 19, 'Pengelola Kesehatan Ikan Ahli Pertama', NULL, NULL, NULL, NULL, NULL),
(1789, 23, 'Penyuluh Lingkungan Hidup Ahli Pertama', NULL, NULL, NULL, NULL, NULL),
(1790, 13, 'PENGADMINISTRASI PERKANTORAN', NULL, NULL, NULL, NULL, NULL),
(1791, 23, 'PENATA LAYANAN OPERASIONAL', NULL, NULL, NULL, NULL, NULL),
(1792, 23, 'PENGADMINISTRASIAN PERKANTORAN', NULL, NULL, NULL, NULL, NULL),
(1793, 23, 'PENGELOLA UMUM OPERASIONAL', NULL, NULL, NULL, NULL, NULL),
(1794, 29, 'PEMADAM KEBAKARAN\n PEMULA', NULL, NULL, NULL, NULL, NULL),
(1795, 3, 'Penata Layanan Operasional', NULL, NULL, NULL, NULL, NULL),
(1796, 3, 'Pengadministrasi Perkantoran', NULL, NULL, NULL, NULL, NULL),
(1797, 3, 'Pengelola Layanan Operasional ', NULL, NULL, NULL, NULL, NULL),
(1798, 7, 'Penata Layanan Operasional', NULL, NULL, NULL, NULL, NULL),
(1799, 7, 'Pengadministrasi Perkantoran', NULL, NULL, NULL, NULL, NULL),
(1800, 7, 'Pengelola Umum Operasional', NULL, NULL, NULL, NULL, NULL),
(1801, 32, 'Penata Layanan Operasional', NULL, NULL, NULL, NULL, NULL),
(1802, 32, 'Pengadministasi Perkantoran', NULL, NULL, NULL, NULL, NULL),
(1803, 35, 'PENATA LAYANAN OPERASIONAL', NULL, 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(1804, 35, 'PENGADMINISTRASIAN PERKANTORAN', NULL, 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL),
(1805, 37, 'PENGELOLA LAYANAN OPERASIONAL', NULL, NULL, NULL, NULL, NULL),
(1806, 37, 'PENATA LAYANAN OPERASIONAL', NULL, NULL, NULL, NULL, NULL),
(1807, 37, 'PENGADMINISTRASI PERKANTORAN', NULL, NULL, NULL, NULL, NULL),
(1808, 36, 'PENATA LAYANAN OPERASIONAL', NULL, NULL, NULL, NULL, NULL),
(1809, 31, 'PENGADMINISTRASI PERKANTORAN', NULL, NULL, NULL, NULL, NULL),
(1810, 31, 'PENGELOLA LAYANAN OPERASIONAL', NULL, NULL, NULL, NULL, NULL),
(1811, 34, 'PENATA LAYANAN OPERASIONAL', NULL, NULL, NULL, NULL, NULL),
(1812, 34, 'PENGELOLA LAYANAN OPERASIONAL', NULL, NULL, NULL, NULL, NULL),
(1813, 34, 'PENGADMINISTRASIAN PERKANTORAN', NULL, NULL, NULL, NULL, NULL),
(1814, 33, 'PENGADMINISTRASIAN PERKANTORAN', NULL, NULL, NULL, NULL, NULL),
(1815, 33, 'PENATA LAYANAN OPERASIONAL', NULL, NULL, NULL, NULL, NULL),
(1816, 38, 'PENGADMINISTRASIAN PERKANTORAN', NULL, NULL, NULL, NULL, NULL),
(1817, 39, 'PENGADMINITRASI PERKANTORAN', NULL, NULL, NULL, NULL, NULL),
(1818, 39, 'PENATA LAYANAN OPERASIONAL', NULL, NULL, NULL, NULL, NULL),
(1819, 42, 'PENATA LAYANAN OPERASIONAL', NULL, NULL, NULL, NULL, NULL),
(1820, 43, 'PENGADMINISTRASIAN PERKANTORAN', NULL, NULL, NULL, NULL, NULL),
(1821, 43, 'PENATA LAYANAN OPERASIONAL', NULL, NULL, NULL, NULL, NULL),
(1822, 44, 'PENATA LAYANAN OPERASIONAL', NULL, NULL, NULL, NULL, NULL),
(1823, 44, 'PENGELOLA LAYANAN OPERASIONAL', NULL, NULL, NULL, NULL, NULL),
(1824, 44, 'PENGADMINISTRASI PERKANTORAN', NULL, NULL, NULL, NULL, NULL),
(1825, 45, 'PENGADMINISTRASI PERKANTORAN', NULL, NULL, NULL, NULL, NULL),
(1826, 41, 'PENATA LAYANAN OPERASIONAL', NULL, NULL, NULL, NULL, NULL),
(1827, 41, 'PENGADMINISTRASIAN PERKANTORAN', NULL, NULL, NULL, NULL, NULL),
(1828, 15, 'PENGELOLA LAYANAN OPERASIONAL', NULL, NULL, NULL, NULL, NULL),
(1829, 15, 'PENATA LAYANAN OPERASIONAL', NULL, NULL, NULL, NULL, NULL),
(1830, 8, 'PENGADMINISTRASI PERKANTORAN', NULL, NULL, NULL, NULL, NULL),
(1831, 8, 'PENATA LAYANAN OPERASIONAL', NULL, NULL, NULL, NULL, NULL),
(1832, 8, 'PENGELOLA LAYANAN OPERASIONAL', NULL, NULL, NULL, NULL, NULL),
(1833, 26, 'PENATA LAYANAN OPERASIONAL', NULL, NULL, NULL, NULL, NULL),
(1834, 26, 'PENGADMINISTRASI PERKANTORAN', NULL, NULL, NULL, NULL, NULL),
(1835, 22, 'PENATA LAYANAN OPERASIONAL', NULL, NULL, NULL, NULL, NULL),
(1836, 22, 'PENGELOLA LAYANAN OPERASIONAL', NULL, NULL, NULL, NULL, NULL),
(1837, 22, 'PENGADMINISTRASI PERKANTORAN', NULL, NULL, NULL, NULL, NULL),
(1838, 25, 'PENGADMINISTRASI PERKANTORAN', NULL, NULL, NULL, NULL, NULL),
(1839, 25, 'PENGELOLA LAYANAN OPERASIONAL', NULL, NULL, NULL, NULL, NULL),
(1840, 25, 'PENATA LAYANAN OPERASIONAL', NULL, NULL, NULL, NULL, NULL),
(1841, 10, 'Penata Layanan Operasional', NULL, NULL, NULL, NULL, NULL),
(1842, 10, 'Pengelola Layanan Operasional', NULL, NULL, NULL, NULL, NULL),
(1843, 10, 'Pengadministrasian Perkantoran', NULL, NULL, NULL, NULL, NULL),
(1844, 28, 'PENATA LAYANAN OPERASIONAL', NULL, NULL, NULL, NULL, NULL),
(1845, 28, 'PENGELOLA LAYANAN OPERASIONAL ', NULL, NULL, NULL, NULL, NULL),
(1846, 28, 'PENGELOLA UMUM OPERSIONAL', NULL, NULL, NULL, NULL, NULL),
(1847, 6, 'Penata Layanan Operasional', NULL, NULL, NULL, NULL, NULL),
(1848, 6, 'Pengadministrasi Perkantoran', NULL, NULL, NULL, NULL, NULL),
(1849, 5, 'PENATA LAYANAN OPERASIONAL', NULL, NULL, NULL, NULL, NULL),
(1850, 5, 'PENGADMINISTRASI PERKANTORAN', NULL, NULL, NULL, NULL, NULL),
(1851, 5, 'PENGELOLA LAYANAN OPERASIONAL\r\n', NULL, NULL, NULL, NULL, NULL),
(1852, 2, 'Penata Layanan Operasional', NULL, NULL, NULL, NULL, NULL),
(1853, 2, 'Pengadministrasi Perkantoran', NULL, NULL, NULL, NULL, NULL),
(1854, 2, 'Pengelola Layanan Operasional', NULL, NULL, NULL, NULL, NULL),
(1855, 2, 'Penata  Layanan Operasional', NULL, NULL, NULL, NULL, NULL),
(1856, 2, 'Operator Layanan Operasional', NULL, NULL, NULL, NULL, NULL),
(1857, 4, 'PENATA LAYANAN OPERASIONAL', NULL, NULL, NULL, NULL, NULL),
(1858, 4, 'PENGADMINISTRASI PERKANTORAN', NULL, NULL, NULL, NULL, NULL),
(1859, 20, 'PENATA LAYANAN OPERASIONAL', NULL, NULL, NULL, NULL, NULL),
(1860, 20, 'PENGADMITRASI PERKANTORAN', NULL, NULL, NULL, NULL, NULL),
(1861, 20, 'PENGELOLA LAYANAN OPERASIONAL', NULL, NULL, NULL, NULL, NULL),
(1862, 12, 'PENGADMINISTRASI PERKANTORAN', NULL, NULL, NULL, NULL, NULL),
(1863, 12, 'PENATA LAYANAN OPERASIONAL', NULL, NULL, NULL, NULL, NULL),
(1864, 12, 'PENGELOLA LAYANAN OPERASIONAL', NULL, NULL, NULL, NULL, NULL),
(1865, 19, 'PENATA LAYANAN OPERASIONAL ', NULL, NULL, NULL, NULL, NULL),
(1866, 19, 'PENGELOLA LAYANAN OPERASIONAL', NULL, NULL, NULL, NULL, NULL),
(1867, 19, 'PENGADMINISTRASI PERKANTORAN ', NULL, NULL, NULL, NULL, NULL),
(1868, 14, 'PENGADMINISTRASI PERKANTORAN', NULL, NULL, NULL, NULL, NULL),
(1869, 14, 'PENATA LAYANAN OPERASIONAL', NULL, NULL, NULL, NULL, NULL),
(1870, 14, 'PENGELOLA LAYANAN OPERASIONAL', NULL, NULL, NULL, NULL, NULL),
(1871, 37, 'Pengadministrasi Perkantoran', NULL, NULL, NULL, NULL, NULL),
(1872, 32, 'Pengadministrasi Perkantoran', NULL, NULL, NULL, NULL, NULL),
(9999, 46, 'BUPATI', NULL, NULL, '2025-10-23 10:03:46', '2025-10-23 10:03:46', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `kegiatan_pk`
--

CREATE TABLE `kegiatan_pk` (
  `id` int UNSIGNED NOT NULL,
  `program_id` int UNSIGNED NOT NULL,
  `kegiatan` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `anggaran` decimal(15,2) DEFAULT '0.00',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `kegiatan_pk`
--

INSERT INTO `kegiatan_pk` (`id`, `program_id`, `kegiatan`, `anggaran`, `created_at`, `updated_at`) VALUES
(1, 1, 'Perencanaan, Penganggaran, dan Evaluasi Kinerja Perangkat Daerah', 21152732.00, '2026-01-05 13:36:46', '2026-01-05 13:36:46');

-- --------------------------------------------------------

--
-- Table structure for table `lakip`
--

CREATE TABLE `lakip` (
  `id` int NOT NULL,
  `renstra_target_id` int UNSIGNED DEFAULT NULL,
  `rpjmd_target_id` int UNSIGNED DEFAULT NULL,
  `target_lalu` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `capaian_lalu` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `capaian_tahun_ini` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `status` enum('proses','siap') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'proses',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lakip`
--

INSERT INTO `lakip` (`id`, `renstra_target_id`, `rpjmd_target_id`, `target_lalu`, `capaian_lalu`, `capaian_tahun_ini`, `status`, `created_at`, `updated_at`) VALUES
(1, 121, NULL, '100', '100', '100', 'siap', '2025-12-10 19:41:24', '2025-12-14 21:35:01'),
(2, 126, NULL, '1', '1', '1', 'siap', '2025-12-10 21:28:18', '2025-12-10 21:49:21'),
(3, 61, NULL, '1', '1', '1', 'siap', '2025-12-11 23:42:07', '2025-12-12 00:26:51'),
(4, 66, NULL, '1', '1', '1', 'proses', '2025-12-12 00:26:58', '2025-12-12 00:26:58'),
(5, 122, NULL, '22', '22', '20', 'proses', '2025-12-14 21:07:06', '2025-12-14 21:38:22'),
(6, 127, NULL, '1', '1', '1', 'proses', '2025-12-14 21:35:16', '2025-12-14 21:37:22'),
(7, NULL, 146, '1', '1', '1', 'proses', '2025-12-14 21:41:36', '2025-12-14 21:41:36'),
(8, 14, NULL, '2,2', '2,2', '2,2', 'siap', '2026-01-14 00:43:35', '2026-01-14 00:45:27');

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` bigint UNSIGNED NOT NULL,
  `version` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `class` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `group` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `namespace` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `time` int NOT NULL,
  `batch` int UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `version`, `class`, `group`, `namespace`, `time`, `batch`) VALUES
(1, '2025-07-07-005404', 'App\\Database\\Migrations\\CreateOpdTable', 'default', 'App', 1755656248, 1),
(2, '2025-07-07-005702', 'App\\Database\\Migrations\\CreateUsersTable', 'default', 'App', 1755656249, 1),
(3, '2025-07-07-041245', 'App\\Database\\Migrations\\CreatePangkatTable', 'default', 'App', 1755656249, 1),
(4, '2025-07-07-041257', 'App\\Database\\Migrations\\CreateJabatanTable', 'default', 'App', 1755656249, 1),
(5, '2025-07-07-041307', 'App\\Database\\Migrations\\CreatePegawaiTable', 'default', 'App', 1755656249, 1),
(6, '2025-07-07-123221', 'App\\Database\\Migrations\\CreateRpjmdMisiTable', 'default', 'App', 1755656249, 1),
(7, '2025-07-07-124150', 'App\\Database\\Migrations\\CreateRpjmdTujuanTable', 'default', 'App', 1755656249, 1),
(8, '2025-07-07-124213', 'App\\Database\\Migrations\\CreateRpjmdIndikatorTujuan', 'default', 'App', 1755656249, 1),
(9, '2025-07-07-124244', 'App\\Database\\Migrations\\CreateRpjmdSasaranTable', 'default', 'App', 1755656249, 1),
(10, '2025-07-07-124722', 'App\\Database\\Migrations\\CreateRenstraSasaranTable', 'default', 'App', 1755656249, 1),
(11, '2025-07-07-125014', 'App\\Database\\Migrations\\CreateRenjaSasaranTable', 'default', 'App', 1755656249, 1),
(12, '2025-07-07-135038', 'App\\Database\\Migrations\\CreateRenjaIndikatorSasaranTable', 'default', 'App', 1755656249, 1),
(13, '2025-07-08-010348', 'App\\Database\\Migrations\\CreateRenstraIndikatorSasaranTable', 'default', 'App', 1755656249, 1),
(14, '2025-07-08-011057', 'App\\Database\\Migrations\\CreateRenstraTargetTahunanTable', 'default', 'App', 1755656249, 1),
(15, '2025-07-08-011656', 'App\\Database\\Migrations\\CreateIkuSasaranTable', 'default', 'App', 1755656249, 1),
(16, '2025-07-08-011919', 'App\\Database\\Migrations\\CreateIkuIndikatorKinerjaTable', 'default', 'App', 1755656250, 1),
(17, '2025-07-08-011937', 'App\\Database\\Migrations\\CreateIkuTargetTahunanTable', 'default', 'App', 1755656250, 1),
(18, '2025-07-08-012457', 'App\\Database\\Migrations\\CreateRkpdSasaranTable', 'default', 'App', 1755656250, 1),
(19, '2025-07-08-012507', 'App\\Database\\Migrations\\CreateRkpdIndikatorSasaranTable', 'default', 'App', 1755656250, 1),
(20, '2025-07-08-015055', 'App\\Database\\Migrations\\CreateRpjmdIndikatorSasaranTable', 'default', 'App', 1755656250, 1),
(21, '2025-07-08-015120', 'App\\Database\\Migrations\\CreateRpjmdTargetTable', 'default', 'App', 1755656250, 1),
(22, '2025-07-11-034700', 'App\\Database\\Migrations\\AddStatusToRpjmdMisi', 'default', 'App', 1755656250, 1),
(23, '2025-07-11-045406', 'App\\Database\\Migrations\\AddStatusToRkpdTable', 'default', 'App', 1755656250, 1),
(24, '2025-07-12-145716', 'App\\Database\\Migrations\\AddStatusColumn', 'default', 'App', 1755656250, 1),
(25, '2025-07-15-044548', 'App\\Database\\Migrations\\UbahKolomRpjmdIndikatorSasaran', 'default', 'App', 1755656250, 1),
(26, '2025-07-16-035354', 'App\\Database\\Migrations\\CreateProgramPkTable', 'default', 'App', 1755656250, 1),
(27, '2025-07-16-040722', 'App\\Database\\Migrations\\CreatePKTable', 'default', 'App', 1755656250, 1),
(28, '2025-07-16-040748', 'App\\Database\\Migrations\\CreatePkSasaranTable', 'default', 'App', 1755656250, 1),
(29, '2025-07-16-040758', 'App\\Database\\Migrations\\CreatePkIndikatorTable', 'default', 'App', 1755656250, 1),
(30, '2025-07-16-040813', 'App\\Database\\Migrations\\CreatePkProgramTable', 'default', 'App', 1755656251, 1),
(31, '2025-07-29-012619', 'App\\Database\\Migrations\\CreateLakipKabTable', 'default', 'App', 1755656251, 1),
(32, '2025-07-29-023901', 'App\\Database\\Migrations\\CreateLakipOpdTable', 'default', 'App', 1755656251, 1),
(33, '2025-08-12-000000', 'App\\Database\\Migrations\\CreateSatuanTable', 'default', 'App', 1755656251, 1),
(34, '2025-08-12-001000', 'App\\Database\\Migrations\\AddIdSatuanToPkIndikator', 'default', 'App', 1755656251, 1),
(35, '2025-08-13-000001', 'App\\Database\\Migrations\\CreatePkReferensi', 'default', 'App', 1755656251, 1),
(36, '2025-08-13-000002', 'App\\Database\\Migrations\\AddParentPkIdToPk', 'default', 'App', 1755656251, 1),
(37, '2025-08-20-000002', 'App\\Database\\Migrations\\CreatePkMisiTable', 'default', 'App', 1755656251, 1),
(38, '2025-08-29-000001', 'App\\Database\\Migrations\\add_id_indikator_to_pk_program', 'default', 'App', 1756433412, 2);

-- --------------------------------------------------------

--
-- Table structure for table `monev`
--

CREATE TABLE `monev` (
  `id` int NOT NULL,
  `opd_id` int UNSIGNED DEFAULT NULL,
  `target_rencana_id` int NOT NULL,
  `capaian_triwulan_1` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `capaian_triwulan_2` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `capaian_triwulan_3` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `capaian_triwulan_4` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `total` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `monev`
--

INSERT INTO `monev` (`id`, `opd_id`, `target_rencana_id`, `capaian_triwulan_1`, `capaian_triwulan_2`, `capaian_triwulan_3`, `capaian_triwulan_4`, `total`, `created_at`, `updated_at`) VALUES
(10, NULL, 18, '1', '23', '3', NULL, 2, '2025-11-30 19:22:02', '2025-11-30 19:40:26'),
(13, 20, 17, '1', '2', '', '', 2, '2025-12-15 21:47:29', '2025-12-15 21:47:29');

-- --------------------------------------------------------

--
-- Table structure for table `opd`
--

CREATE TABLE `opd` (
  `id` int UNSIGNED NOT NULL,
  `nama_opd` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `singkatan` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `alamat_opd` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `id_kepala_opd` int DEFAULT NULL,
  `lat_opd` decimal(20,6) DEFAULT NULL,
  `long_opd` decimal(20,6) DEFAULT NULL,
  `edited_by` int DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `opd`
--

INSERT INTO `opd` (`id`, `nama_opd`, `singkatan`, `alamat_opd`, `id_kepala_opd`, `lat_opd`, `long_opd`, `edited_by`, `created_at`, `updated_at`) VALUES
(1, 'BAGIAN ADMIN', NULL, 'KOMPLEK PERKANTORAN PEMERINTAH DAERAH KABUPATEN PR', 168, -5.344490, 105.003800, 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(2, 'SEKRETARIAT DAERAH', NULL, 'KOMPLEK PERKANTORAN PEMERINTAH DAERAH KABUPATEN PR', 172, -5.344348, 105.006254, 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(3, 'SEKRETARIAT DPRD', NULL, 'KOMPLEK PERKANTORAN PEMERINTAH DAERAH KABUPATEN PR', 16, -5.345434, 105.010057, 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(4, 'INSPEKTORAT', NULL, 'KOMPLEK PERKANTORAN PEMERINTAH DAERAH KABUPATEN PR', 20, -5.344430, 105.004500, 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(5, 'BADAN PENGELOLAAN KEUANGAN DAN ASET DAERAH', NULL, 'KOMPLEK PERKANTORAN PEMERINTAH DAERAH KABUPATEN PR', 25, -5.345980, 105.008770, 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(6, 'BADAN PENDAPATAN DAERAH', NULL, 'KOMPLEK PERKANTORAN PEMERINTAH DAERAH KABUPATEN PR', 31, -5.345960, 105.008230, 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(7, 'BADAN PERENCANAAN PEMBANGUNAN DAERAH', NULL, 'KOMPLEK PERKANTORAN PEMERINTAH DAERAH KABUPATEN PR', 35, -5.345190, 105.004510, 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(8, 'BADAN KEPEGAWAIAN DAN PENGEMBANGAN SUMBER DAYA MANUSIA', NULL, 'KOMPLEK PERKANTORAN PEMERINTAH DAERAH KABUPATEN PR', 42, -5.344440, 105.002620, 0, '2022-05-19 09:37:54', NULL),
(9, 'BADAN SATUAN POLISI PAMONG PRAJA', NULL, '-', 46, -5.359039, 104.976923, 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(10, 'DINAS PENDIDIKAN DAN KEBUDAYAAN', NULL, 'KOMPLEK PERKANTORAN PEMERINTAH DAERAH KABUPATEN PR', 7, -5.345230, 105.003380, 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(11, 'DINAS KESEHATAN', NULL, 'KOMPLEK PERKANTORAN PEMERINTAH DAERAH KABUPATEN PR', 57, -5.346570, 105.008440, 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(12, 'DINAS SOSIAL', NULL, 'KOMPLEK PERKANTORAN PEMERINTAH DAERAH KABUPATEN PR', 63, -5.345317, 105.008348, 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(13, 'DINAS PEMBERDAYAAN PEREMPUAN, PERLINDUNGAN ANAK, PENGENDALIAN PENDUDUK DAN KELUARGA BENCANA', NULL, 'KOMPLEK PERKANTORAN PEMERINTAH DAERAH KABUPATEN PR', 67, -5.345220, 105.002040, 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(14, 'DINAS KEPENDUDUKAN DAN PENCATATAN SIPIL', NULL, 'KOMPLEK PERKANTORAN PEMERINTAH DAERAH KABUPATEN PR', 72, -5.345220, 105.003930, 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(15, 'DINAS KEPEMUDAAN, OLAHRAGA DAN PARIWISATA', NULL, 'KOMPLEK PERKANTORAN PEMERINTAH DAERAH KABUPATEN PR', 77, -5.345125, 105.008346, 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(16, 'DINAS KOPERASI, USAHA KECIL DAN MENENGAH, PERDAGANGAN DAN PERINDUSTRIAN', NULL, 'KOMPLEK PERKANTORAN PEMERINTAH DAERAH KABUPATEN PR', 82, -5.344430, 105.003370, 0, '2022-05-19 09:37:54', NULL),
(17, 'DINAS PERHUBUNGAN', NULL, 'KOMPLEK PERKANTORAN PEMERINTAH DAERAH KABUPATEN PR', 87, -5.344430, 105.003940, 0, '2022-05-19 09:37:54', NULL),
(18, 'DINAS PEKERJAAN UMUM DAN PERUMAHAN RAKYAT', NULL, 'KOMPLEK PERKANTORAN PEMERINTAH DAERAH KABUPATEN PR', 91, -5.345210, 105.002620, 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(19, 'DINAS PERIKANAN', NULL, ' ', 99, -5.337125, 104.975201, 0, '2022-05-19 09:37:54', NULL),
(20, 'DINAS KOMUNIKASI DAN INFORMATIKA', NULL, '-', 103, -5.344430, 105.003940, 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(21, 'DINAS PERTANIAN', NULL, 'KOMPLEK PERKANTORAN PEMERINTAH DAERAH KABUPATEN PR', 107, -5.373748, 105.013318, 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(22, 'BADAN PEMBERDAYAAN MASYARAKAT DAN PEKON', NULL, 'KOMPLEK PERKANTORAN PEMERINTAH DAERAH KABUPATEN PR', 114, -5.344430, 105.003370, 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(23, 'DINAS LINGKUNGAN HIDUP', NULL, ' ', 118, -5.355240, 105.024290, 0, '2022-05-19 09:37:54', NULL),
(24, 'DINAS KETAHANAN PANGAN', NULL, '-', 122, -5.355061, 104.970820, 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(25, 'DINAS PENANAMAN MODAL DAN PELAYANAN TERPADU SATU PINTU', NULL, '-', 126, -5.355767, 104.971278, 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(26, 'DINAS PERPUSTAKAAN DAN KEARSIPAN', NULL, 'Jl. Ki Hajar Dewantara Rejosari No.589, RT.01, Pri', 128, -5.345269, 105.007550, 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(27, 'DINAS TENAGA KERJA DAN TRANSMIGRASI', NULL, 'KOMPLEK PERKANTORAN PEMERINTAH DAERAH KABUPATEN PR', 132, -5.345220, 105.002040, 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(28, 'BADAN KESATUAN BANGSA DAN POLITIK', NULL, 'GADINGREJO', 136, -5.361269, 104.982982, 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(29, 'BADAN PENANGGULANGAN BENCANA DAERAH', NULL, 'JL. KESEHATAN NO. 1136 ', 141, -5.343595, 105.005792, 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(30, 'RUMAH SAKIT UMUM DAERAH PRINGSEWU', NULL, 'Jl. Kesehatan No.1360, Pajar Agung, Kec. Pringsewu', 146, -5.368013, 104.937494, 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(31, 'KECAMATAN PRINGSEWU', NULL, 'KOMPLEK PERKANTORAN PEMERINTAH DAERAH KABUPATEN PR', 151, -5.354004, 104.974285, 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(32, 'KECAMATAN GADING REJO', NULL, '-', 153, -5.374460, 105.062200, 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(33, 'KECAMATAN AMBARAWA', NULL, '-', 155, -5.406622, 104.967193, 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(34, 'KECAMATAN SUKOHARJO', NULL, 'Jl. Winangun 3, Sukoharjo III, Kec. Sukoharjo, Kab', 157, -5.303058, 104.982735, 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(35, 'KECAMATAN ADILUWIH', NULL, 'ADILUWIH', 159, -5.219470, 105.030282, 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(36, 'KECAMATAN BANYUMAS', NULL, 'Jl. Perintis No.5, Rejosari, Kacamatan Banyumas, K', 161, -5.293127, 104.918087, 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(37, 'KECAMATAN PAGELARAN', NULL, 'Gumuk Rejo, Kec. Pagelaran, Kabupaten Pringsewu, L', 163, -5.371307, 104.929837, 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(38, 'KECAMATAN PARDASUKA', NULL, '-', 8030, -5.473802, 104.930528, 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(39, 'KECAMATAN PAGELARAN UTARA', NULL, '-', 167, -5.292434, 104.870329, 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(40, 'UPT PENGEMBANGAN BUDIDAYA IKAN DINAS PERIKANAN', NULL, 'Jl. Perikanan, Wonodadi, Kec. Gading Rejo, Kabupat', 99, -5.364931, 105.037094, 0, '0000-00-00 00:00:00', NULL),
(41, 'KELURAHAN PAJARESUK', NULL, '-', 1009, -5.355742, 104.952074, NULL, NULL, NULL),
(42, 'KELURAHAN PRINGSEWU BARAT', NULL, '-', 1010, -5.353839, 104.973338, NULL, NULL, NULL),
(43, 'KELURAHAN PRINGSEWU SELATAN', NULL, '-', 1011, -5.355104, 104.972634, NULL, NULL, NULL),
(44, 'KELURAHAN PRINGSEWU TIMUR', NULL, '-', 1012, -5.359825, 104.982368, NULL, NULL, NULL),
(45, 'KELURAHAN PRINGSEWU UTARA', NULL, '-', 1013, -5.350269, 104.976302, NULL, NULL, NULL),
(46, 'BUPATI', NULL, 'KOMPLEK PERKANTORAN PEMERINTAH DAERAH KABUPATEN PR', NULL, NULL, NULL, NULL, '2025-10-23 09:59:13', '2025-10-23 09:59:13'),
(209, 'UPTD PERLINGDUNGAN PEREMPUAN DAN ANAK', NULL, '-', 707, -5.355141, 104.977763, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `pangkat`
--

CREATE TABLE `pangkat` (
  `id` int UNSIGNED NOT NULL,
  `nama_pangkat` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `golongan` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `edited_by` int DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pangkat`
--

INSERT INTO `pangkat` (`id`, `nama_pangkat`, `golongan`, `edited_by`, `created_at`, `updated_at`) VALUES
(0, '-', '-', NULL, '2022-03-21 13:04:21', NULL),
(1, 'Juru Muda', 'I/a\r', NULL, '2022-03-21 13:04:21', NULL),
(2, 'Juru Muda Tingkat I', 'I/b\r', NULL, '2022-03-21 13:04:21', NULL),
(3, 'Juru', 'I/c\r', NULL, '2022-03-21 13:04:21', NULL),
(4, 'Juru Tingkat I', 'I/d\r', NULL, '2022-03-21 13:04:21', NULL),
(5, 'Pengatur Muda', 'II/a', NULL, '2022-03-21 13:04:21', NULL),
(6, 'Pengatur Muda Tingkat I', 'II/b', NULL, '2022-03-21 13:04:21', NULL),
(7, 'Pengatur', 'II/c', NULL, '2022-03-21 13:04:21', NULL),
(8, 'Pengatur Tingkat I', 'II/d', NULL, '2022-03-21 13:04:21', NULL),
(9, 'Penata Muda', 'III/a', NULL, '2022-03-21 13:04:21', NULL),
(10, 'Penata Muda Tingkat I', 'III/b', NULL, '2022-03-21 13:04:21', NULL),
(11, 'Penata', 'III/c', NULL, '2022-03-21 13:04:21', NULL),
(12, 'Penata Tingkat I', 'III/d', NULL, '2022-03-21 13:04:21', NULL),
(13, 'Pembina', 'IV/a', NULL, '2022-03-21 13:04:21', NULL),
(14, 'Pembina Tingkat I', 'IV/b', NULL, '2022-03-21 13:04:21', NULL),
(15, 'Pembina Utama Muda', 'IV/c', NULL, '2022-03-21 13:04:21', NULL),
(16, 'Pembina Utama Madya', 'IV/d', NULL, '2022-03-21 13:04:21', NULL),
(17, 'Pembina Utama', 'IV/e', NULL, '2022-03-21 13:04:21', NULL),
(18, 'Pemula', 'V', NULL, NULL, NULL),
(19, 'Terampil', 'VI', NULL, NULL, NULL),
(20, 'Mahir', 'IX', NULL, NULL, NULL),
(21, 'Penyelia', 'XI', NULL, NULL, NULL),
(22, 'Juru', 'I', NULL, NULL, NULL),
(23, 'Terampil', 'VII', NULL, NULL, NULL),
(24, '-', 'IV', NULL, NULL, NULL),
(25, 'BUPATI', '-', NULL, '2025-10-23 10:03:13', '2025-10-23 10:03:13');

-- --------------------------------------------------------

--
-- Table structure for table `pegawai`
--

CREATE TABLE `pegawai` (
  `id` int UNSIGNED NOT NULL,
  `nama_pegawai` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `nip_pegawai` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `opd_id` int UNSIGNED DEFAULT NULL,
  `jabatan_id` int UNSIGNED DEFAULT NULL,
  `pangkat_id` int UNSIGNED DEFAULT NULL,
  `atasan_id` int DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `level` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `url_foto_pegawai` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `tukin` decimal(20,6) DEFAULT NULL,
  `edited_by` int DEFAULT NULL,
  `first_time` int DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `no_whatsapp` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pegawai`
--

INSERT INTO `pegawai` (`id`, `nama_pegawai`, `nip_pegawai`, `opd_id`, `jabatan_id`, `pangkat_id`, `atasan_id`, `password`, `level`, `url_foto_pegawai`, `tukin`, `edited_by`, `first_time`, `created_at`, `updated_at`, `no_whatsapp`) VALUES
(1, 'HIPNI', '196512061987121001', 2, 2, 15, 0, '$2a$10$0eCg9EERutvhTW2lSdotZ.TJgz8knvwSoxgwnXEiFNR2TwjJib2oq', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(2, 'ANI SUNDARI', '198305242002122001', 19, 459, 14, 100, '$2a$10$s4PcFyz.deTWF3Gb3LkHAu8E0dwJUnBGFYMnwG2NaOt5E0wZGGDBW', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(7, 'SUPRIYANTO', '197309191998021001', 10, 52, 15, 0, '$2a$10$N8d6ZDWC/7BtefB88mVJxup5EcPvnvCusnaXSmd3lv6EvR6655zQW', 'USER', 'https://dev.pringsewukab.go.id/foto/1657786577296.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(8, 'RUSTIYAN', '196904151997021001', 2, 9, 14, 0, '$2b$10$GaY7S39adtbHgQqbwKrGhOIIFDlp32CpdL7MQggWY8wBzT68ZCDBG', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(9, 'PUTRA ADITIA GUMILANG', '198702122010011005', 2, 10, 13, 0, '$2a$10$ZRzHVbj47lOC5xSV9H9ba.wIHhlHh8rxFAHMEl3ajArZo23rj60Qe', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(10, 'IDHAM ALBAZAMI', '197611202002121007', 2, 11, 13, 0, '$2a$10$EmpEdAN3YYnhcuHIIHGcueHUlyKRqlNIdd/u84oP/JRfH89S/sjli', 'USER', 'https://dev.pringsewukab.go.id/foto/1721264818059.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(11, 'ZULYADIN', '196406261986031008', 2, 12, 14, 0, '', 'USER', NULL, 0.000000, 0, 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(12, 'A. HANDRI YUSUF', '197408162010011004', 25, 127, 13, 0, '$2b$10$pv9VOO6fXNsCkJeTgiUdLOmYLiWqyex4URpxpfKronnDbxHVhwlK6', 'USER', 'https://dev.pringsewukab.go.id/foto/1665375125356.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(13, 'ADAM ERKHANSYAH', '197110292005011005', 2, 14, 13, 0, '$2b$10$n6SsywdTNVDh6hSjzq9a1e4d30x9aQFFtJxUOrjOfDXOXBiSZvxaq', 'USER', 'https://dev.pringsewukab.go.id/foto/1665374717064.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(14, 'SIDIK PRIYANTO', '196803221993031003', 17, 89, 13, 0, '$2b$10$OsII7s59u14M6wjS8cUWRuy9V6wBp3fLjrYaqj/UQ1ScZ3ork4D/2', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(15, 'WIWIT SUTRIYONO', '197010051992031010', 4, 25, 13, 20, '$2a$10$//y2beioFIQueTVjd..xi.xLcvSQGdKp8aA0YsJCDqhhuxcOn/j3S', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(17, 'ENDA FAKSI JAYA', '198110282005011006', 3, 18, 13, 16, '$2b$10$nv1bGOjfRZlIfN/6B6DSV.1.VHz6A6ZeBhzmteJ9z./xvMqMrGnJS', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(18, 'YULI SUSAPTO', '197607042003121004', 3, 19, 13, 16, '$2b$10$rS4YnXhW6.zFlLgZNofocutwtMp6vitsBqzmWKIB.GFicPHDScmLS', 'VERIFIKATOR', 'https://dev.pringsewukab.go.id/foto/1668587799081.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(19, 'SUPENDI', '197209072000031002', 3, 20, 14, 16, '$2b$10$NRQ3n4FfrZ.QjoPGi3uUnuMnk20fh.d1rXgIVIc4YBJ6UScJ2w4Ni', 'VERIFIKATOR', 'https://dev.pringsewukab.go.id/foto/1664758061225.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(20, 'M.ANDI PURWANTO', '197002091999021001', 4, 21, 15, 0, '$2a$10$KzsYsflWUdk9qcaCKOjj8etCjQLlkzp58jX4g54/ME5oDP/Z79MQG', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(21, 'YANUAR HARYANTO', '196901211990031008', 4, 22, 14, 20, '$2b$10$gngF8mQVCogED/gFXNDl7OKiFfNdHXq8QkWwBss.zA0N2Bt/iKUBK', 'USER', 'https://dev.pringsewukab.go.id/foto/1664933184414.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(22, 'ACHMAD IWAN KURNIAWAN', '197605302010011004', 4, 23, 13, 20, '$2a$10$QQnQgb.Wac9HuGRmCmP6FO7BxA/V35v4OL5JtagBYgCyXHXRaQzxG', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(23, 'TANJUNG DEWAYANI', '197307181999022001', 4, 24, 14, 20, '$2a$10$9Z//nFE1d/48W967q1YQ4ujXOdH8P3fiKdsalqw8DfnDQ.b6Re9ie', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(24, 'ENCEP ILYAS', '197003041990121001', 4, 684, 14, 20, '$2b$10$M6Nue/ViNdn5IMMPirncLezdzcfpQqGB.RKCCQ0FQ4QZZWqQ3Uu3.', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(25, 'ARIF NUGROHO', '196909131999021001', 2, 7, 15, 0, '$2a$10$.ojqoOEE1q2w958e0AsI1e23NISmfBYMbBZMwRruHej9OxhCf2UMy', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(26, 'TRI ANTARA', '197207012002121003', 5, 27, 13, 25, '$2b$10$YNFhMVlt2QGdbMlGzqgzsuzwVvLbRE7wjUxZSU4VqFvsmx0XD5Qu6', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(27, 'AHMAR ABYADH', '198609252010011008', 24, 685, 13, 103, '$2b$10$td7QCSCNH2/4PwbDCqjpA.4Bi56YTKgXHEINbgGkUpytq29fWgSf6', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(28, 'LILI YULIANA', '198007152007012015', 5, 29, 13, 25, '$2a$10$6Nb1lCd4scviu0FTnB/jTeX3NnI1z5/71JxdMixrNsxil/TahhzS6', 'VERIFIKATOR', 'https://dev.pringsewukab.go.id/foto/1718005481823.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(29, 'WURI HANDAYANI', '197406201998022001', 5, 30, 13, 25, '$2b$10$B6UYbyO22Xc7cknKF40RmesDx5Bzp5JXO//LQ6WDEv3R7f.5YCvXC', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(30, 'ARIFUDIN', '197305041998021003', 27, 134, 13, 25, '$2a$10$fa8O8GklkCJ5pnGfOrneXOH6ya37n6vvs1K5FRlswYLKZ9s/L1/Ka', 'VERIFIKATOR', 'https://dev.pringsewukab.go.id/foto/1664522524999.png', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(32, 'FIRSTIANA YOGI ANUGRAHI', '196705281991032005', 6, 33, 14, 128, '$2b$10$uyehg2XNA1DRphKOi1927ejZOHm7GGLhL4xbZ4JvZacbkJkpXn0Nq', 'VERIFIKATOR', 'https://dev.pringsewukab.go.id/foto/1693961149287.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(33, 'ALI ALHAMIDI', '197502022000031004', 6, 34, 13, 128, '$2b$10$1Bg3vo6UvaXMCKH91pAxp.P8glQ18UGr5bNw1dijUgzQBRUpsHtMq', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(34, 'ILHAM TESA PUTRA', '198504142010011019', 6, 35, 12, 128, '$2b$10$SF2ELRWoDdnulp2lq.8sPeX0CRIjFZEwZZy0skOHG.fNtOfaTd9hS', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(35, 'AKHMAD FADOLI', '197010051997101002', 3, 17, 15, 0, '$2a$10$mImiF4EusOkFu9.XqXFuiOFIg6wqwcqRqXOEDyU2gunUAA5Hnhmoe', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(36, 'CICIH DANIASRI', '197803042002122004', 2, 12, 13, 35, '$2b$10$dakCoWy0XceZ.uGyXK0QH.2F.Krre.LpgMZ/TQALhUi6JqkreLdwO', 'USER', 'https://dev.pringsewukab.go.id/foto/1711076686121.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(37, 'BAYU SETIAWAN', '198009112010011011', 17, 90, 12, 91, '$2b$10$f8BB.s7Ntyi2QCeUwxYlLeUe38TVbxaBjNsh.f5gZk0gyivZ2zHdi', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(38, 'CHRISTIANTO H. SANI', '198710202010011001', 31, 152, 12, 91, '$2b$10$JwKuoAi5sme5LcT10wLPM.OUNRdMCUplbv3KJPU/qwdJxrZA2204y', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(39, 'RARA SUKMA', '198507242010012016', 7, 40, 12, 91, '$2b$10$OfRtap/5Exg169toHCQsaO9bO3YUvbZypZ6/7zggWHbUqbYwpiyeK', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(40, 'SUNARTO', '197603122011011001', 28, 140, 12, 136, '$2b$10$IC7rz6QUgwhW.V5.NgnPTOUBGghyCCS/1Kfu2a8mwN9Izn0BS8A/m', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(41, 'FENNY APRILIA', '198404052010012032', 7, 42, 12, 91, '$2a$10$LCQOH/lkG5.DleNr7UotIu0EYxTBmvJI1thHzQj7PdpqTa4qBhK/y', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(42, 'EKO SUMARMI', '197104091998032005', 13, 68, 15, 0, '$2a$10$6LYb9YsQWq93FNK0c8fqreeQEq1Wy2xej27EO0Ars0vsuTqByhaFi', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(43, 'JUDY MULJANA', '197104262010011003', 8, 44, 13, 42, '$2b$10$czCJR6m16h6JOkGV2cDkzuTVO70PKCg.3705KX00ODmuI5vFymwey', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(44, 'DIMAS SUDRAJAD', '198302242010011016', 8, 45, 12, 42, '$2b$10$s3xdNWnwgbDYmTjvdYhVmOCicxNb/Jbh1zemWw1Htfiata3Vm89tS', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(45, 'FERY AMRIYANTO', '198402142006041003', 7, 41, 12, 91, '$2a$10$XcF1T.xpVIbMrTO3CQ9u7OZ1gJSo8iyy6ekHcACGPeRDXRn3O.52G', 'VERIFIKATOR', NULL, 0.000000, NULL, 1, '0000-00-00 00:00:00', NULL, '6287838807981'),
(46, 'IBNU HARJIYANTO', '196603311989011001', 15, 78, 15, 0, '$2a$10$yxKAvUA4AMbSbDcFSU5ukunCdJxWkG3Yh6ZIX8W5vEIIaaryIr3W.', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(47, 'MAULIDIN ANSORI', '196806062008011017', 9, 48, 13, 46, '$2b$10$S5wGR4h2esEHp2qQqvweIuPC4j6RspQ.vaaDStNL2ZbjSWvnCUv4C', 'VERIFIKATOR', 'https://dev.pringsewukab.go.id/foto/1664422549062.png', 0.000000, NULL, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(48, 'MARWAN', '197312012003121003', 9, 49, 12, 46, '$2b$10$T0IVG4dLhDfH3N7oKrMi4evdVkg41fomdCgA14vsr8Aezr5G.ZtHe', 'VERIFIKATOR', 'https://dev.pringsewukab.go.id/foto/1664759018474.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(49, 'TRINO', '196609112007011034', 9, 50, 12, 46, '$2b$10$Xg5FNEcMkfsQVoCHUTOk1uP/U03bgI2Nl8HVLEgAwTLeAqEjaJKlW', 'VERIFIKATOR', 'https://dev.pringsewukab.go.id/foto/1664438392109.png', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(50, 'SUHERMADI', '197203172005011010', 9, 51, 13, 46, '$2b$10$NMpHRtp5BT.BxDJXAlVUp.dONPtKZeZMx4S/y4.B012I8gCMvlJeW', 'VERIFIKATOR', 'https://dev.pringsewukab.go.id/foto/1664438419809.png', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(52, 'SUNAJI', '197403011998021001', 2, 9, 14, 51, '$2b$10$wvPWg6L7poUSeyoxlu2aD.shu3x3CX.6C0x/8j3qCoF6AigUzYz7a', 'USER', 'https://dev.pringsewukab.go.id/foto/1670375599520.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(55, 'TOMI YAZID BUSTOMI', '197206231999031003', 10, 56, 13, 51, '$2a$10$qUNYowgzubFSrbslsU0ed.Pi97Q2Z0/agtNx6gtK0ETwL6tUuCkQC', 'USER', 'https://dev.pringsewukab.go.id/foto/1664422606408.png', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(56, 'SIGIT BUDIARTO', '197005041997021002', 10, 57, 13, 51, '$2b$10$p..98Hb0B4HjG5U0gCVpZesdhlQQJTigZRHY2uvWYUdtvaRSLmjMK', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(57, 'ULIN NOHA', '197303052006041011', 23, 119, 14, 0, '$2b$10$KLzbPgDhSyhcKniuRWlUcO33AhM3q9KMiEPmT/OH2WRB4evzX86Ym', 'VERIFIKATOR', 'https://dev.pringsewukab.go.id/foto/1665719569620.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(58, 'IMANDA AMIN ABRI', '196810051989031014', 11, 59, 14, 4, '$2b$10$AIdI7X25MROanYnjFik7DOBnnihONHaM0B.OJbU1pZsOx5w.SpFTO', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(59, 'SELAMET KUNTORO', '196603261987031004', 11, 60, 13, 4, '$2a$10$Sr2YEC3fmK81r57xQNi7h.gQfddHhjEQSePtDRSlrPmZndCBMVvtG', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(60, 'DARLI YONHAS', '197804162006041014', 11, 61, 11, 4, '$2b$10$8iMgaOJsRVdZNkaoraWr3e5It/E4Zf9uLg30ti60RO1B7kgqQbDHK', 'VERIFIKATOR', 'https://dev.pringsewukab.go.id/foto/1664756291878.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(61, 'HADI MOCHTAROM', '197706302006041004', 11, 62, 13, 4, '$2a$10$sFDr/nuBLkDw9B/IGqoBhe8EAbGRsV81gr2/ynjp8doYfOO.J65i.', 'VERIFIKATOR', 'https://dev.pringsewukab.go.id/foto/1751882252929.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(62, 'RAHMADI', '197104161991011001', 11, 63, 13, 4, '$2b$10$WSNY01h61oF4SnMKtFb4JuIYAZxS49JGVJ2sAIbk7Sx1vt.6E8UVK', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(63, 'TITIK PUJI LESTARI', '196702151994032005', 2, 3, 14, 0, '$2a$10$1JMF5jPQEL1vsrkUDUy8uOJu38D9EBXf5mZmx7zYGTifJPge/fEsm', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(64, 'TRI KADARMANTO', '196801111988101001', 14, 74, 14, 73, '$2a$10$saVCUrBGOkaC5ulMZx2Djuqun6COiiqRCexywalaMbq2QsgGW7oQ6', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(65, 'MARISON', '197906192005011006', 12, 66, 12, 99, '$2b$10$TrKG6aELmGZMRCArq8MC3.JQDTb1SGF7EON5sFereHNCWp8UQ1S.W', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(66, 'SUGENG PRAMONO', '197108311998031004', 9, 50, 13, 99, '$2b$10$I1iYgmmZmNU1X4/e5Eppvu/brPeRE3qcXyva8JU4TbnGryPnq4C4C', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(67, 'NANG ABIDIN HASAN', '196607291993121002', 29, 142, 15, 0, '$2a$10$adf2A7Q2Ab/HljCMCCH55eERxqk4smKRfJuSf8b3M31T5.b0LPDxm', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(68, 'NURIYANTO', '197808062000121003', 13, 69, 13, 57, '$2b$10$9fmtzMw4WYIx7dV31JRL3OAjZkoTkhvQKvARpMBPlzyaWklNlI9d.', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(69, 'AVI RISDYANTI', '197506102010012009', 13, 70, 12, 57, '$2b$10$A551AFVnTnX1.T7BAW4Q4e4nO.fccue27RIrCKqm.RlkoognFve8G', 'VERIFIKATOR', 'https://dev.pringsewukab.go.id/foto/1665464470139.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(70, 'ELITA YANTI', '197307281999032006', 13, 71, 13, 57, '$2b$10$iVpJKmI5omTmb.40nUcQbu0QMiIFr7lhcC8AkmAtyzicUoSgaOljK', 'VERIFIKATOR', 'https://dev.pringsewukab.go.id/foto/1669249067802.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(71, 'SOFYAN HARNADI', '197705022008041001', 13, 72, 12, 57, '$2b$10$QcMYxNYPhtg3SzWlxjL8d.hJWQskBO30ZQEpc/msJuF25FDgG7mwa', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(73, 'SUDARSIH', '197006071999032001', 14, 73, 14, 72, '$2a$10$bGSccpkq4wXQTmpGRmqxoOp9x/4zwDolDJGZmK3efIGdXWJmE0qB6', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(74, 'TATI ZARSMI', '196505271986032005', 14, 75, 13, 72, '$2b$10$gUTHD3C0.74zkyzumEn67Oaul9Bny/tCyu.PGb0Q3thSXSFGhouAi', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(75, 'HANI LINA NADWAH', '197503032000032001', 14, 76, 13, 72, '$2b$10$y.tMo77Z/SQdDLKqoMr.OOLmpF2GERZNGITucbNzmCfDr/ibUPhQ.', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(76, 'SUYATNO', '197108032006041010', 14, 77, 13, 72, '$2b$10$pjMwhHvKKm7z/Vsv6JS/COusEG3uzuB1kaz.eEFf2GKGZC9Dlf0Lu', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(77, 'JAHRON', '196605071991031008', 9, 47, 15, 0, '$2b$10$oMPZsLezJZqR5YOTBbUT0.BFrxeAmlGsaVwnooXMQPOxY8fuAHC5O', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(78, 'PENI WIDAYATI', '196608201987052001', 15, 79, 14, 46, '$2b$10$19UibPgSdPtXCD5pr3FY9OvRvD7obqYG2.szaoQHq5tsZ2p9tOwou', 'VERIFIKATOR', 'https://dev.pringsewukab.go.id/foto/1664505066421.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(79, 'HERU WIDODO', '196705011990021001', 15, 80, 13, 46, '$2a$10$SiEvOSNrkiFHoZexAvxD1OSfmVYiG2NkEmNrbVtCLwv8fZszCh5zC', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(80, 'DADAN PRASADA', '196605041993031010', 15, 81, 13, 46, '$2a$10$3UqfMG4.JxSkOajNBblapeOqqm7uf7aB0MOgdMJ.WNjEdNqE.tQaK', 'VERIFIKATOR', NULL, 0.000000, NULL, 1, '0000-00-00 00:00:00', NULL, '6287838807981'),
(81, 'SHINTA DEWI DAMAYANTI', '197805022010012011', 15, 79, 11, 46, '$2b$10$JH9V4RnMpN7aA727MAwD7./x6sWW7Kdw6JG/gC7YhJ1BsQR6YGzGG', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(83, 'SULISTIYO NINGSIH', '197601162010012016', 16, 84, 12, 82, '$2b$10$oHsPf94Hy4ZBRmSxOL07F.5nOwdGsoJUjIC5bwlx5Wing8pd41PR2', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(84, 'DEBIT ZULIANSYAH', '198010122010011023', 16, 85, 12, 82, '$2a$10$OW.Yb9vhCBsO2HSuw/Xy7Or5Tz9RlSsjSm7gRB2V7RIj227WIETry', 'VERIFIKATOR', 'https://dev.pringsewukab.go.id/foto/1718003520841.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(85, 'RIKA KARTINI', '198106212010012015', 16, 86, 12, 82, '$2b$10$nQ3DLBug3E/zR2eM5WAU1.RXBV/oV3yaJqbSSNKMBOi//kzb0QBKO', 'VERIFIKATOR', 'https://dev.pringsewukab.go.id/foto/1664428216733.png', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(86, 'REKA PAHLEFI', '198405222010011010', 16, 87, 12, 82, '$2b$10$ZT/gpLkTf7UbkIdmAGPu6uKeb0SIefIWRiLDI936bLV/xFtnsTYD6', 'VERIFIKATOR', 'https://dev.pringsewukab.go.id/foto/1664441576460.png', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(87, 'BAMBANG SUHARMANU', '196410311985031003', 17, 88, 15, 0, '$2b$10$zAwH0LarVaCjUO2T.JrQLOhZ6oNBWKiqjR4rqLkFdKVBml1oHZ7F.', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(88, 'HERIYADI INDERA', '196606131987031009', 17, 89, 14, 87, '$2b$10$QxBtxOy6V9Koim17IaZFDu3BEyAuBB79GKidd9vkgPssueWkSR7tG', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(89, 'HARTADI', '196506051990031010', 0, 90, 13, 0, '$2b$10$KMwyxx.c0LkFZgwULz9MLOOCYWcPwk8eiWiJDP/w1EP7sPEhE7uHe', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(90, 'ISMAIL', '196711162000031004', 17, 91, 13, 87, '$2b$10$JGoOGwjHnqaZ/b0ubARu6.pENe3t6uGflMe5vApCUN4UHxHb4TKy.', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(91, 'IMAM SANTIKO RAHARJO', '196804011998031002', 17, 88, 15, 0, '$2a$10$C26uOdsJf3WHUHCbum26pOW.rB3hOdmBtpabPDo6ke1f5vDlc5CQC', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(92, 'AMAD SYAIFUDIN', '197901162010011007', 18, 92, 13, 91, '$2a$10$1r88RNx4JtPmjgiOuva2VutrhRiVbc.YSaCpSOULZmoSWR1g3eWiy', 'VERIFIKATOR', 'https://dev.pringsewukab.go.id/foto/1659660242221.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(93, 'IKROMI FAHMI', '198112252010011021', 18, 93, 12, 91, '$2b$10$tJv6Zo2XTgDTKv2wSM3w5.0E9BTSSfmRqkGaZ/sdi08KkBDr9yIU6', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(94, 'WIYANTO', '197511162010011007', 18, 95, 12, 91, '$2a$10$mKDqYNd79ta4fKopUL5WMuOlJK5pgsw7UKlF8pa09q5iyUrHkGtLu', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(95, 'ARAINA DWI RUSTIANI', '198506102010012023', 18, 96, 12, 91, '$2b$10$7UVc/ulxbWuTt2d3NV/onezbBJ0Oh/gPug2hcg0Yx67rgCEfwto4a', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(96, 'ANJARWATI SETYANINGRUM', '198501102010012036', 18, 97, 12, 91, '$2a$10$oDqkfm7RIlDpYWsvkiAH6OPgjUjAtUxcKVd52VIKSqltbz654SE0W', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(97, 'OTTEN RINALDI', '198210252010011017', 18, 98, 12, 91, '$2a$10$jwjX0RW4PhdnM4ZwtUUp0uNk2scblUr4evvXkGsgEG3mdTvy9K6gy', 'VERIFIKATOR', 'https://dev.pringsewukab.go.id/foto/1674628430778.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(98, 'ADE SUHERNA ', '198201022010011031', 18, 99, 12, 91, '$2b$10$gXtIs0tPMbOfzy.66Fe4xezZRdtcKZTsWllSJm6LbNiQnNyVWM7y2', 'VERIFIKATOR', 'https://dev.pringsewukab.go.id/foto/1665375578203.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(99, 'DEBI HARDIAN', '196710221998032005', 12, 64, 15, 0, '$2a$10$Mb3f8HKqadvl5op9TuMBmOBiFpdMNxC9PPVUongM0kt9XSbNs08wK', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(100, 'I MADE SUBAGIARTHA', '196601301992031005', 40, 101, 14, 99, '$2b$10$5VxW9F/kNf/1A3iplW6jiezcjp1JRSZqia/vlx9ARYftaiAY9OmRy', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(101, 'MUKHOLIK', '196904062003121006', 19, 102, 13, 99, '$2b$10$/RkpvMrg5uK6hsR99352PeknoS1bMXGVZmepREQ6Se9tiQ7Q26a2W', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(102, 'NOVITA SUSANTI', '197311151998032008', 19, 103, 13, 99, '$2b$10$BXlgvYSNWq36H9iMGa/DcubWuMa4zuSndjsA6YVSyrpRaemUKRFFm', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(103, 'HENDRID', '196712311992031044', 2, 6, 15, 0, '$2b$10$6.xyFaPFBKABtpuLeBI0TOkICEN9im5jbczwaPG2egf4T9Qf1l3uu', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(104, 'EKO KUSMIRAN', '197408172000031007', 10, 53, 14, 103, '$2a$10$yjh5aNNinbRQ/tzIaU2bfOqDv7lNm3M9BtZvmSpdSccw12BoZiTza', 'USER', 'https://dev.pringsewukab.go.id/foto/1658736113280.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(105, 'MAHRIZAN ALRIZKIE', '198312172010011008', 20, 106, 12, 103, '$2a$10$v3QdY9O774DydeuYmT8BGO.VWleCvV6LgCKk0BBGVFmgK9ovzOqBG', 'VERIFIKATOR', 'https://dev.pringsewukab.go.id/foto/1657501903428.png', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(106, 'RUSTADI WIJAYA', '198309292011011004', 2, 15, 11, 103, '$2b$10$iZYmR4fqwyzVaWVTYGv4rO4plQ79s0QG9nutv6m8mSz04bNpgyima', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(107, 'SITI LITAWATI', '196811191998032002', 2, 4, 14, 0, '$2b$10$65JZV4WAC8OUb0lsJEiZlejyjN5QTAf1/MI4CwAnCubnjw.0OKLcm', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(108, 'M. MARYANTO', '197603122002121002', 21, 109, 14, 107, '$2b$10$tLZgwnwNRPIARJXmB4wFweqL3dYzlm3DBGU1i8eEbPa9gi6qAiDoK', 'VERIFIKATOR', 'https://dev.pringsewukab.go.id/foto/1664512199878.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(109, 'BUDI PRAMONO', '198206032010011022', 24, 124, 13, 107, '$2a$10$fuDzoul1u9ThDj0FRoMCeO0qwLJgIwmKDZCzwQzuNR/RZe4Su2aJy', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(110, 'ELIS MARTATIASIH', '197103292005012002', 21, 111, 13, 107, '$2b$10$VXp6WTchMgLo5iuCUTm10ezTa3oCStzpU0SUoiPLjXjMbAepkdZYK', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(111, 'DWIYANTO SULISTIONO', '196702281989031004', 21, 112, 13, 107, '$2b$10$HJCf0E6iwYUz9Dmt4KHTgeMYM9OwCgwL7drIZDtnbwaOAp2IMbDW.', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(112, 'SRI ERMALIA', '197611222010012007', 21, 113, 12, 107, '$2b$10$buBMs1.dMCEqp.kkZSSn8eyArFD8iWirqrOenFR.kXyfsTtDS57ZO', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(113, 'DJOKO PRABOWO', '197107081996031003', 21, 114, 14, 107, '$2b$10$bzAD3K6T9YjsrdCFOnwVcOJEkqJSY5vyz2pBTLCNxG2AGEyuuHWH.', 'VERIFIKATOR', 'https://dev.pringsewukab.go.id/foto/1750669192820.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(114, 'ISKANDAR MUDA', '196510171992021001', 22, 115, 15, 0, '$2b$10$eMM/SAFfjKnlmQo5cCdn8uOO3eEmKvrZQ.CkO81Ih78LTUSTo6Ddi', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(115, 'TRI HARYONO', '197910051999021001', 22, 116, 13, 114, '$2a$10$nYSxi7H9iqsEIzJkjfMSqedBJ9.GZgqhNTzsjY7MvGygyfwuJ88J6', 'VERIFIKATOR', 'https://dev.pringsewukab.go.id/foto/1704165126346.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(116, 'KASMINI', '196810101992032012', 22, 117, 13, 114, '$2b$10$3IOfrOhgzrFJAiLl09BPlelCB2.Xg/QWbhjqQbZX5C5HkzbHhibju', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(117, 'BUDI SANTOSO', '197102142002121005', 22, 118, 13, 114, '$2a$10$5bFYVNPjJvfexz7TkDR5ZuYvP8O1H2oV42Ho41WTscRaX5Vbb8O36', 'VERIFIKATOR', 'https://dev.pringsewukab.go.id/foto/1657586295246.png', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(118, 'NUR PAJRI', '196510101985031004', 19, 100, 14, 0, '$2a$10$BO9YNqcNgMKxJeoB6CJo4uniFwLo.LbxnTOdwuwSjG9af6acrnF3e', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(119, 'RINI ANDALUSIA', '197402271999022001', 25, 128, 14, 35, '$2a$10$vA9KeUZYXB/KlH5DN20T9O/QgAVoAHf/RuDg5cLCiTfNxn3GPtZrO', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(120, 'AHMAD ADAM ALTHUSIUS', '198004222010011009', 23, 121, 12, 57, '$2b$10$ce7pYgAIirwuUr/ZJ0FKW.WJsZ4yRct.bC.yexYr6tv5OZ9NsnEPq', 'VERIFIKATOR', 'https://dev.pringsewukab.go.id/foto/1704103806866.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(121, 'FIDELIS SIGIT SUSANTO', '196904122000031003', 23, 122, 13, 57, '$2a$10$qz.qD0MLIqpQj2/tYA.R2O0fWPBXuq.yP8sBf4eIBETs9OU.VoBza', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(122, 'MOKHAMAD KHOTIM', '196303201985021001', 24, 123, 15, 0, '$2b$10$nIH2eX.Cc.fAUstdpIrLnODszCdfsm4F7XU0bY9w43m8p2bz.u3ri', 'VERIFIKATOR', 'https://dev.pringsewukab.go.id/foto/1665628224626.png', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(123, 'FIRDAUS TARUNA JAYA', '196808281989091001', 24, 124, 14, 103, '$2b$10$CWGnJxDgYM9Pttq2aMsYGOX5Ajxk1Xl2q11k2uSRxZo2F8wc2dphC', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(124, 'NURUL HIKMAH', '197502191998022001', 24, 125, 13, 103, '$2b$10$s9PXHuo2b3mZ.oSm.CZ5l.srb8/EUh4ZyxqsErnWG17C3bq49.x.a', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(125, 'AWALUDIN NUR', '196602271988011002', 24, 126, 13, 103, '$2b$10$JQKpNeLLySt50yzFzVzv8uSZDMFScKzhnNoTKhyZzmEsjzwAXk14i', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(126, 'IHSAN HENDRAWAN', '197012112005011009', 2, 5, 13, 0, '$2a$10$ObNb0F4TRmIWBaHB7kM7Z.5nard0NdElbXlHLPMBXoky.SgxUHF9a', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(127, 'SUKTARI MARGAYANI', '196611031992032004', 25, 128, 13, 126, '$2b$10$4n3.8jGFACr/RecG5kjiYuvTtTIIlyJbVeMdOJwJoDFoRcvBPypNq', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(128, 'YANWIR', '197303141997031004', 6, 32, 14, 0, '$2a$10$ue2PhHAatxHJjmqbnjxBYOmt.avx2GuJFe3EB2UkvbpPyO6Q7UQY.', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(129, 'WIWIN WIDANINGSIH', '196507031987032002', 26, 130, 14, 128, '$2b$10$TKDD5gVeewi8WMw9QdWs3.mr85Z.y1Q145nUOaM91v.CYSgWhJxI2', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(130, 'HASAN PAUZI', '196711031999031003', 26, 131, 13, 128, '$2b$10$woSaHM52YJokD6BZTEZerOCWgfTY7hprp9eIO6jwf0fUGMcyNy21K', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(131, 'NATALINA AMBARSARI', '197212261996032002', 26, 132, 12, 128, '$2b$10$i.W.qVlFPDd/kSRTK.FKquS5QsqgJE46b8Ws9Wg.9myIV3ISMr1E.', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(133, 'NORTIANA SINAGA', '196512121985092001', 27, 134, 13, 141, '$2b$10$Q6ZxN72XzvZTGEGFrFgNgOIDPN1uEMvB9NuBAYcYVwa1A49D4RaS6', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(134, 'LEKAT ATORIP', '197304271997031003', 27, 135, 13, 141, '$2a$10$aFg3BCF2rnVEhKkAmmMcuOGPie7VPSh5Oanw6Phyn/0hgQoorDZTa', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(135, 'MUHAMMAD SUBHAN', '197901112008041001', 34, 159, 12, 141, '$2b$10$PQsQh4JqJLZzH53zWqcuPe1QpowuzKl5DSfU.ZT4XOoVMF8m1qx7e', 'USER', 'https://dev.pringsewukab.go.id/foto/1664935667604.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(137, 'INDRA HERYADI', '198009271999121001', 28, 138, 14, 136, '$2b$10$YB04Gjy22aXbLWbAeg5HqOOSj7VJ/Xb00q8WAUopD6ObAV43ekIMy', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(138, 'AHMAD KHOIDIR', '197004011991031005', 28, 139, 13, 136, '$2b$10$oP77h9Z4Gzl5QXql523uXershcMdIEXAZfwJfb0qp./7Y/KhieAIC', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(139, 'PARYONO', '198409062010011018', 8, 46, 12, 42, '$2b$10$JbbH/sBwDgzhmQRW45FVquUzztVWFjH3XQoGr8xxB9YZ68AZhQeju', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(140, 'EKA MASTUR', '197606161998031002', 28, 141, 13, 136, '$2a$10$4GILpZnBTKJxF7KygC6PJuhDgeKEvVkpIYEUtEkFet2YhuxPuYQgC', 'USER', 'https://dev.pringsewukab.go.id/foto/1668489576888.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(141, 'EDY SUMBER PAMUNGKAS', '196508151986021007', 27, 133, 15, 0, '$2a$10$vdyEcgzBvTAfZKXNahEsy.51Z6r65YIdQN/ecO.zknz8DIZEk3Uc2', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(142, 'TEGUH PRASETYO', '196809191995031003', 29, 143, 13, 67, '$2a$10$/AjQXYObf8fjjx3V18UgXuKaMkUR6MDby8j66eU4IFKYbK7DG2PFq', 'VERIFIKATOR', 'https://dev.pringsewukab.go.id/foto/1664930987191.png', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(143, 'PAHRURROZI', '196804151989031008', 29, 144, 13, 67, '$2b$10$oAlQmQ6v.HeSVDYD4/TkKOqJFQzPyrUs..sabgBr1hGQoYReWTscK', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(144, 'MA\'RUF WAHYUDHI', '197303231998031008', 29, 145, 13, 67, '$2b$10$i8v68A1w42bRTKAOOJnNluOpF2bDNa3fm3Y4YfxxIYLW.WINbz6De', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(145, 'ANANTO PRATIKNO', '196810031994031005', 29, 146, 14, 67, '$2b$10$I69sfNqZbLP9QO2kgbWSXeW/9uVF3Y9oqIh.OczaL7/3ryhVkIN76', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(146, 'ANDI ARMAN', '197808012005011009', 30, 147, 12, 0, '', 'VERIFIKATOR', NULL, 0.000000, 0, 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(147, 'PUJI HARNO', '196710101988021001', 30, 148, 13, 146, '', 'USER', NULL, 0.000000, 0, 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(148, 'ROHMAD', '197702232005011002', 30, 149, 12, 146, '', 'USER', NULL, 0.000000, 0, 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(149, 'TRIYANI ROSITASARI', '198306192011012005', 30, 150, 12, 146, '', 'USER', NULL, 0.000000, 0, 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(150, 'SUS INDAH MARTININGSIH', '196703071986032001', 30, 151, 13, 146, '', 'USER', NULL, 0.000000, 0, 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(151, 'MOUDY ARY NAZOLLA', '198408192002121001', 20, 104, 13, 0, '$2a$10$IvbhK2Au7tOiykLvtxq2H.i09IAcgv8SU5.IEkMYpCYAIo.O9b76u', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(152, 'ERLI YUNARNI', '197407161993032003', 31, 153, 12, 151, '$2a$10$Y3j91mgtOpW4xewSZE9fK.HXxmAOI3beHXdfomTh/eL8X3gKEeiYu', 'USER', NULL, 0.000000, NULL, 1, '0000-00-00 00:00:00', NULL, '6287838807981'),
(153, 'JOKO HERMANTO', '196612221990031005', 32, 664, 14, 0, '$2b$10$dZ.Q38ry3hX3c6Wxpx9iWeWAZn4a6/xLIhvzF9IYeofWwx0DDXI8S', 'VERIFIKATOR', 'https://dev.pringsewukab.go.id/foto/1712106036100.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(154, 'KUNCORO SANCOKO', '196806222008011011', 32, 155, 12, 153, '$2b$10$l/y1Ddo8B6WEPojmk3s.3OwF2BNsR0J.MaS4Zmpc6BjjHP6.JPxcG', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(155, 'EKO PURWANTO', '197502142000031003', 32, 154, 13, 0, '$2b$10$eT0frchtzkiBTzpGdIZos.mxUazKuBaO33q.gaTBY8SedJ0ki/wRG', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(156, 'M. ANDRI DWIHARTO', '198208242010011009', 33, 157, 12, 155, '$2b$10$9L879sG7JPytH7GMhBFtSe.DDFUHRrY64Kx3dG78iHF6BLfbmsqKu', 'VERIFIKATOR', 'https://dev.pringsewukab.go.id/foto/1664420706649.png', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(157, 'YULI SAPTIKAWATI', '197107021998022004', 35, 160, 14, 0, '$2a$10$3h3CX0P4vx9IE.mbzy.DbeqaZMXUN5zk08HE.Vht25NvQ5JWFif4m', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(158, 'FASEH RAHMAN', '197704102003121004', 39, 169, 13, 157, '$2b$10$dHkZJKXAF07o..VlfTqKwOJ8IOzr0aWfPgmMryNlHSUYn8BcmFPdu', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(159, 'PARWANTO', '197206161996031005', 26, 130, 13, 0, '$2a$10$oVU7BBDRJDb5iw/jwH6sEO/mU055kG4kVhol/LGpDaLveah1nGOIC', 'VERIFIKATOR', 'https://dev.pringsewukab.go.id/foto/1665041731505.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(160, 'LASMINI', '197206151992032002', 35, 161, 13, 159, '$2b$10$2CYO0XywlogObA8.rz.5iuTMfuBIFv0UA2ovtRtbCEm5.OFxbHcTG', 'VERIFIKATOR', 'https://dev.pringsewukab.go.id/foto/1665967421326.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(162, 'MOHAMAD NURDIN', '196802261998031005', 36, 163, 13, 161, '$2b$10$mijxxHaPzicVVnezD3PtEOFVMkBeLmc6KzulRo7weatKwdVSfmo7K', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(163, 'MUHAMAD FAOZAN', '196711031992031005', 37, 164, 14, 0, '$2b$10$Dkrf/WyiHPUL4WurMgNJNuTqaUUynv75JXhF4q4GC7y0QZvoCseWK', 'VERIFIKATOR', 'https://dev.pringsewukab.go.id/foto/1712106090710.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(164, 'EKO SUBAGIYO', '197807122002121004', 37, 165, 13, 163, '$2b$10$R/3VeyfrIlkc1mVViWPFsO/Dz1ZJMKmSIgVsv.saQGdDuIEn3DLTe', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(165, 'BUKHORI', '196412041990031010', 38, 166, 14, 166, '$2b$10$on1nhTRojhfsx9WMA/LjKuaEOnTyc0y7Wwfm27INy7OYlcxScEbJe', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(166, 'ANTON DWI WAHYONO', '197710101998031002', 33, 156, 12, 0, '$2b$10$0ZNpFpSLZD45tltvnh6Uheu90jWQ6056DNCB6YKnluwzJySj9z7sO', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(167, 'ZAINAL ABIDIN', '197604042000121002', 36, 162, 13, 0, '$2b$10$fv.RuJSwtQmvR6xkcFLz8uWtEUCvv1JoinLMEHf4RMrzozNKkFoSi', 'VERIFIKATOR', NULL, 0.000000, NULL, 1, '0000-00-00 00:00:00', NULL, '6287838807981'),
(168, 'seribubambu', 'admin', 0, 1, 1, 1, '$2a$10$d7WsfRUDO9.ceAqjp5fJMuoyOo9mCZLn5oV1fJHzpvvKUhrSDDxIG', 'ADMIN', 'https://dev.pringsewukab.go.id/foto/1664423458747.png', 0.000000, 0, 1, '2022-03-02 14:46:08', '0000-00-00 00:00:00', '6287838807981'),
(169, 'Rangkas Andreansyah', '1998', 0, 18, 0, 170, '$2a$10$MsPkbcg86bV2gMpNNXWlC.cHzazpsVMVOoTFDpOVwMmXDMq45kYRq', 'PERMITOR', 'https://dev.pringsewukab.go.id/foto/1710836664002.jpg', 2400000.000000, NULL, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(170, 'Aan D Sanova', '1997', 0, 0, 0, NULL, '$2a$10$IkBMYqA1MGqX/5CtswYOguc6xmZfDEJrAtXDHtCFYVWNxi3jGm9bG', 'VERIFIKATOR', 'https://dev.pringsewukab.go.id/foto/1668955179684.jpg', 0.000000, NULL, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(171, 'Budi', '1234567890', 0, 18, 0, 0, '$2a$10$mdvnrjZGRCYlSN9/rwD/8.kk0JdLiuYY2c6baOrrHpfMBOmkBR8H2', 'USER', 'https://dev.pringsewukab.go.id/foto/1710903564929.jpg', 0.000000, NULL, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(172, 'HERI ISWAHYUDI', '196911011997021007', 2, 170, 17, NULL, '$2a$10$jlNlMvPDAq.r2Z4XCoPvi.70yO9lpDBCBx5hvW8UlhgHzuTL5iR4G', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', NULL, '6287838807981'),
(173, 'NILA SUSILA WARDANI', '197802162000032001', 10, 171, 9, 52, '$2b$10$/DfDnzoUP37x.FOCiqbRmuT2ReCPout.iPkjlgtABvNDkSd3L6vLG', 'PERMITOR', 'https://dev.pringsewukab.go.id/foto/1664979926383.jpeg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(174, 'ISWANTO', '197307231999031007', 10, 54, 9, 53, '$2b$10$cpP9SZErL2OlPQZDu4VVcOqeYLHjSo8aq24cwWdMCgRbdLMzXuyoC', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(175, 'SUBUR SETIYO WIDODO', '197109251998031007', 38, 167, 9, 53, '$2b$10$kml5WMsg360OAf140Ra6KezkzofrgOmYvkTuFhl7kLctCq08N1Qx6', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(178, 'AHMAD HELMI', '198501052015031003', 10, 176, 9, 55, '$2b$10$EdvQMfM.ch6q9jOi97LtJ.SaRiJ6EL9FUlOsG.P2T.jnFSvnNzgNm', 'VERIFIKATOR', 'https://dev.pringsewukab.go.id/foto/1664467209086.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(179, 'HARI SUSANTO', '197603221999031006', 10, 177, 9, 55, '$2b$10$.W64NN9XSRxu079Y1Eryo.6n88tsdg.K6O2sXXgJ9Wk/ImlpzILTa', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(180, 'TRI MULYANI', '198106062015032001', 10, 178, 9, 0, '$2b$10$MrMufJkYM/K90vp3wm1Ciumn98o4Pmr0QbtxMa8pkJzj98temizF.', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(181, 'NORMA YUNITA', '198606282015032005', 10, 178, 9, 173, '$2b$10$pLRy1pn9Ogfj0ZmBbZzQkObD8mSAo8nqcpl1L/nciYSgB6oFLsJJi', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(182, 'JAIM', '198505102006041001', 10, 179, 9, 173, '$2b$10$iG2w7XQ1RXCrW3dMUUJVsOhPX3CqstEW94iIcxm/RiEpelfSulHyi', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(183, 'NETI YUNIDA', '197106301993032003', 10, 180, 9, 51, '$2b$10$EvtKT81PlivZdxo8Zwy0auaUFn6/DMUn96kTk.KqrPhgs969Z452m', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(185, 'BARIYAH', '196607111992032006', 10, 181, 9, 51, '$2b$10$HJ7uZs5p.oSMjng6XmwWF.U4BEftB2fLt8/1oGkQp/nlP3lD63rUi', 'USER', 'https://dev.pringsewukab.go.id/foto/1669352654187.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(186, 'ROKHMAN', '198901192019021002', 10, 182, 9, 173, '$2b$10$/nn5qNRJn8I7EScZ6GaNDOoUaX5CWBdOTUZ0rEcLUIfd.x/Fy7N76', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(187, 'ROSHASTINI', '196804121996032003', 10, 183, 9, 55, '$2a$10$MCEH653VmcNFg2OpppmEsu3iObEj2JAri4lC32P.sE6iE0s0Uk9mW', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(188, 'ANIZA DWI GARDIKA', '198406062006041001', 10, 183, 9, 53, '$2a$10$CGQdX8s8Xll5hklvcIDIeunZBYxTAoXSMcvraKc8Vfrxw/6YZfiv6', 'USER', 'https://dev.pringsewukab.go.id/foto/1678946487699.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(190, 'M.SOFYAN', '197404062014071001', 10, 184, 9, 175, '$2b$10$YsgyAJZaFL1E3YInC6gjue1ZLO1reEuYP4BqLIirHuCGdLo5dS/Ku', 'USER', 'https://dev.pringsewukab.go.id/foto/1711529418237.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(191, 'MUHAMMAD SIDDIK', '197504051998031004', 10, 184, 9, 176, '$2b$10$pGoLRMnfNdUC/K9zFfeh4eatrVTfNaHVqniKOWPrDkGJlHW53Sjz6', 'USER', 'https://dev.pringsewukab.go.id/foto/1664503987271.png', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(192, 'LASMI', '197703112007012022', 10, 185, 9, 182, '$2b$10$ssONqVgT6t0Eit6ZrUrb0een9WEnnuswVLJDst/u2WxFttflJclXC', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(193, 'RULLY FIRMANSYAH', '198204292010011013', 10, 186, 9, 56, '$2a$10$uZCZGqkrz7Xqg/9IayRrxeVYkeInZuyXb/.xm4eu15KjS3.grSoNS', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(194, 'NENY TRI SUSILOWESTRI', '197404081999022001', 10, 186, 9, 56, '$2b$10$mN1W6tht/EaJCaD5FD0K0OhJRQlgr2PWI0CqU.Tj7nH263NhvreSK', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(195, 'MIKE AMARWAN YUCE', '198403222010011008', 10, 187, 9, 198, '$2b$10$vxua8wlfNGxDXB8PM9hoFuq.W.7HD9P9Jd/l5WzWMQaZ7VhViAoiy', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(196, 'JUWARIYAH', '197705261998032002', 10, 188, 9, 198, '$2b$10$QyN20XOT..BdddGH1gClUuhx1fuEf3wAghZH17z9YrXtUVUr6kTTK', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(198, 'MARLIN WINATA', '197903272003121004', 10, 190, 9, 173, '$2a$10$5VBy8YUFJm/TQGF31vRutufd9x.7/fKiykE8WORipEyZghq6ol8TG', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(199, 'DEWI KUSUMA', '198111102010012018', 10, 191, 9, 175, '$2b$10$6QVG6szICzBP0lfxqu4YxuKE6Q0Thtu6nnY9EZ3TLfOkj10LS4iSm', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(201, 'EKO SUTARKO', '197711081996031001', 11, 193, 9, 58, '$2b$10$18CcpcpN82KRMiyPs7glvOZ8UZMAgDmfAnrjft8Og5i/8uhnQGVw.', 'USER', 'https://dev.pringsewukab.go.id/foto/1676351054759.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(202, 'TRI KHASANAH', '197508192010012004', 11, 194, 9, 58, '$2a$10$VjIyNra68lYP/3HNpC0GrO.Hb6V2DWn.XBBsijkr4z7.KcvBtyA6m', 'PERMITOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(203, 'CHUSAIRIL PASA', '198904012011011003', 11, 195, 9, 62, '$2b$10$/uP6O8442t7LhB/lbZm25.nViy/8ymQvWnXbcBX6ImSy/3UtGTrHG', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(204, 'IBNU HARTOYO', '197303251998031004', 11, 196, 9, 58, '$2a$10$dqetVniCr16zvIcFELUKi.LSdK.1qVwIlNZfn2pdykF3LLT5VblSm', 'VERIFIKATOR', 'https://dev.pringsewukab.go.id/foto/1664762637976.png', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(205, 'ERWATY KOMARIYAH', '197709032008042002', 11, 197, 9, 62, '$2b$10$Bncr2olrSdofUCpZgozv0eh6J5GxSq0M6bJAPZiPCqjtJQmhc5zMG', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(206, 'DONI RAMANDOKO', '198605092010011005', 11, 198, 9, 204, '$2b$10$mURJw.4hEl19AHU2QkvIZu9YVeq0r7jTrjEpOm2qi0wd7pOiJ7wDy', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(207, 'HETI SUSILO ASIH', '198512012010012018', 11, 199, 9, 223, '$2b$10$XaP4Hqw1egR76Omc5Y13y.CU5GSHmR0eC8CT6OOjb17fNO2pm82wC', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(208, 'HERLAMBANG SUNENDAR', '197710302000121003', 11, 200, 9, 61, '$2b$10$bXD9T5Cdo1qa0MvWCs16d.wyKNNt6mRL8ryM0CmCJUZEIRemknw1.', 'VERIFIKATOR', 'https://dev.pringsewukab.go.id/foto/1667440560161.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(209, 'DWI RAHMAWATI', '197910102009022004', 11, 200, 9, 61, '$2a$10$/545dXkJGJrJpUoPeyXTeuqZu94JvRVsjUUCbMHsJyzC0xAero7I6', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(210, 'YUDHISTIRA ADI NUGRAHA', '198806052011011004', 11, 200, 9, 61, '$2b$10$J8J4AFt0/dGYx6GBva56Y.qGco14QJgLkx8d5ejl8SJy0Q/Q3I65G', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(211, 'ARI MUNINGGAR RINI', '198201122006042007', 11, 201, 9, 202, '$2b$10$2sjUfZooR0JWYcxrz.g.PuuQfWkMz1mlL.fPptTQ/qZzt.GWd0lxq', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(212, 'EKO HENI PUSPITASARI', '198611232008042002', 2, 202, 9, 219, '$2b$10$qFlwKdRRj8dtV5Kkuv1PB.BG4N8DrJZNnB8e08cy.8ns./3.mXuq6', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(213, 'RIKI SAPUTRA YUSDA', '199206032015031003', 11, 203, 9, 210, '$2a$10$ZfNxlWd6UPtNNtmY0SeNuuwp1oRKfJ1b1bBIpwAW08Tvarvyk8gRS', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(214, 'RENDY BATARA', '198609102015031001', 11, 204, 9, 202, '$2b$10$fwJIh0yjlulPkgCHLLJZ9.EoVUg.HxRaFBAB1Viex5LPOgIY3DYDy', 'USER', 'https://dev.pringsewukab.go.id/foto/1664933201374.png', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(215, 'NOSI KARLINASARI', '198211292012122001', 11, 205, 9, 244, '$2b$10$sA4e8YEFaHM2.RFb95F7fuhRsi.IpvvDxEsWXY5y3E2kgiZic8kxy', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(216, 'ULFI YUNIAR', '199006272014032004', 11, 206, 9, 206, '$2a$10$.WJLlbuZ5KduKswQl6A2lO23EBwD4nZ7oix5/CDnm0nJkg7QDg5nW', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(217, 'RIESEVA FITRIA', '198901202011012012', 11, 207, 9, 205, '$2b$10$sbwL3fyuE9YM6WP9BP47n./iYBNQA8Vi9vNMxjz3nwCy3qkUUVlBW', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(218, 'DODY FERNANDA', '199308252020121013', 11, 208, 9, 223, '$2b$10$BRkv5tbMZ6EVD.ZJkZmyEeKrlvf8l3xv2D6Cg1wnufwq31fTcaulq', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(219, 'TRI NOVA NURHAYATI', '198011132003122002', 11, 209, 9, 62, '$2b$10$ROlk0FNTCJIuvZkn42jJWeWJl2RHfBvGqEBnMDS5UdJ1r.V5W2j9K', 'VERIFIKATOR', 'https://dev.pringsewukab.go.id/foto/1664755733916.png', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(220, 'NOVIA BEKTI SETIAWATI', '198611102011012008', 11, 210, 9, 203, '$2b$10$6kbbYCO2mtuqGyDlX4aDs.dzhYlJzwpSAJ0OYqbJFzoC6iIoXKUv6', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(221, 'SITI INDAH KESUMANINGRUM', '198709212010012008', 37, 644, 9, 60, '$2b$10$lB81Va8uC1SUg6Qqwjo4WOq90kAa7YU6KQSnLJYS8VLbspupwFdTe', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(222, 'LISNA UTAMI', '199105302015032005', 11, 212, 9, 204, '$2a$10$25SDjH3aIyKnqr8qphrv9egQvowF34VJlanNsmp6xta.Oudq0AgBy', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(223, 'ASTERIA WORO INDAH SUSANTI', '198208032009022003', 11, 213, 9, 59, '$2b$10$j1jno9wcWWJqg6IV2YQ56u5xqVSXlQVMZ6EpmeecH.HhM2EdQh3Ni', 'USER', 'https://dev.pringsewukab.go.id/foto/1664513626247.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(224, 'LEONARDUS RIYAN KRISSUDIRO', '199102262020121012', 11, 214, 9, 246, '$2b$10$3uowRafMj89zGKWYAWItRuT8aBLOQSZmPjeRx9K1JwthkHgM7.dRu', 'USER', 'https://dev.pringsewukab.go.id/foto/1664529015750.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(225, 'APRILINA S', '198204042006042010', 11, 215, 9, 208, '$2a$10$YNcLDxm06ZGB0gkmxvcoU.DqFyF7duvxFeFCRL16uhADR.R.v6FA6', 'USER', 'https://dev.pringsewukab.go.id/foto/1670916333462.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(226, 'YULIANA NINA YUANITA', '198606192010012013', 11, 215, 9, 208, '$2b$10$05dpqxBnVPlYZOoaS4W.AOQTuWs3goPUEVBMag.yIhLYMuAmkqjgm', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(227, 'SULISTRI ATMASARI', '198906262011012008', 11, 216, 9, 203, '$2b$10$nTYVTMIJuF0L9zFURfSahuoQ4BYVCq.BqZn938AKOD2uZTZgmWenK', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(228, 'BERLIANA SITUMEANG', '198511172010012026', 11, 217, 9, 209, '$2b$10$UXdGG8LesHCnbjmZHekn2.Pxc4BnGheqgy35/msQh44qDzLmBKs0W', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(229, 'NANANG ASTONI', '198001132006041013', 11, 217, 9, 210, '$2b$10$DvhoG5ExJDRdpq3NWtFCkehybt9djDDXBPy4h32lzVlVnPg.tULSy', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(230, 'RENI KARTIKASARI', '198304232006042006', 11, 217, 9, 60, '$2b$10$vgoJmaRJhshJ3K9k7uuJ/OkaqHNtuZenrJsVT6Woawsw4z1wFcA.S', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(231, 'MYRISTICA DWIJAYANTI', '198701052010012019', 11, 217, 9, 245, '$2b$10$8K2PR/6T28alHULhVs1RZ.DjgC/i1PUzUA4PulELmYvgUmp8v/VAm', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(232, 'INDRIA WARDHANI', '198501032011012007', 11, 218, 9, 204, '$2b$10$B7fBwDKzANgUVHxdanSd6e0yUY5w5SXv3NQso/FSiWna.dvUP4HKe', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981');
INSERT INTO `pegawai` (`id`, `nama_pegawai`, `nip_pegawai`, `opd_id`, `jabatan_id`, `pangkat_id`, `atasan_id`, `password`, `level`, `url_foto_pegawai`, `tukin`, `edited_by`, `first_time`, `created_at`, `updated_at`, `no_whatsapp`) VALUES
(233, 'WAHYUDIN', '197605041997031003', 11, 218, 9, 204, '$2b$10$/2pxdL3Tjbw7iURqIrs/deylRurDyyx9UFF9w/riK8gFi5jRyRsKm', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(234, 'HERA FITRIYASIH', '198207282006042014', 11, 219, 9, 219, '$2a$10$NFoZ.CoaZVYZHEVH9.oN0ehUrzA8XRO8qpmtq/civbd2bOotpUUbK', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(235, 'DIAH OKTAVIANTI', '198710112011012012', 11, 220, 9, 245, '$2a$10$4lWT/5trqiAX3Wc8yxlmEu2SPq8jI29wmYcpwBNQ47upAwcE3X3A6', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(236, 'BAMBANG YUWAN YASMAKASA', '198606062010011012', 11, 220, 9, 210, '$2b$10$/t04FT68ObDMigQ2I1ViVeqjwx.86xprufZn1FwGeH.tGCJy9XTTO', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(237, 'SUCI FUJIYANI', '198808092010012004', 11, 220, 9, 209, '$2b$10$NVw8bBFpy8VW1l.ercc2FO6xA/2dNTGflLJgxoJuZhSYOGKGk5sw.', 'USER', 'https://dev.pringsewukab.go.id/foto/1664430242013.png', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(238, 'TRI AFRINIA', '198904212011012010', 2, 220, 9, 219, '$2a$10$77vwWSmDyaUH9QfMoYRKce7nEHDu59kqbILmvj6dw0xYUeOJGeD0m', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(239, 'DEVIE ADRIANA INDHASYARI', '197907022010012014', 11, 221, 9, 219, '$2b$10$aE0ZWsDqGypZPWothYWD2uZYciZuSTXTJvUoOhG2GA6WHyeKwm7mC', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(240, 'RIMA ZEINNAMIRA', '199005272020122017', 11, 222, 9, 203, '$2b$10$tVAnu7TBmvMsxbPWSq7w6ejCMQNXEGutVEQYHJs1eT7Q343VaM3yC', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(241, 'VINNIE SYLVIANI PURBA', '199608042020122022', 11, 222, 9, 203, '$2a$10$L9VXeErcGRaVA2tuvRH3zurnKrEYWcblHVjfZfxk48rnDWzEywqnS', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(242, 'NIKEN DWI LARASATI', '199404132020122019', 11, 223, 9, 223, 'a', 'USER', NULL, 0.000000, 0, 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(243, 'ADANI ADILARAYANI', '199405202020122019', 11, 223, 9, 223, '$2b$10$cAgO/QBaNnly7Obc3Ve17ubDECIoWC/OMHqWWpLhu9zM1oScnbzki', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(244, 'ROCHMAD APRIYANTO', '197004261994031002', 11, 224, 9, 59, '$2b$10$kO5Y4j9ZeCsRr3EGkNDZreIZXJG4aV1DPgGBufl8QsP6zK9fDDKD6', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(245, 'EKO SULISTIYANTO', '197512081998031003', 11, 224, 9, 60, '$2b$10$diKsQCz.2ozKXyGWxIWp9eAjIN4cc8MnlGYT9sOnHeGMcJWm2AA.C', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(246, 'SIDIK PRIHANTANTO', '198202262010011011', 11, 225, 9, 59, '$2b$10$tnWfIOiRQFmTaH97xhbMl.66c.gHP5UEKWafQgql/NrPEAOPgxiFC', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(247, 'MUHAMMAD TAUFIK', '197305021997031006', 23, 226, 9, 120, '$2b$10$W1GvQYmsklex2zLwrFBPrOk7sV/CLK.POz0/T3XQrfWf/Tx/CEUWm', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(248, 'PURWANTO', '197106122007011008', 23, 227, 9, 120, '$2b$10$NoprYQWsmhVcibGFA6troegfjc2UbNb5buB/ZDDsN9.nVKohn7XTG', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(249, 'YOSE MARINA', '198403212011012007', 23, 228, 9, 119, '$2a$10$ssee5xB5IawC27mteh3rZeJHyINNovU8Ms5jfZQGnF0moXGUEg9ry', 'PERMITOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(250, 'NI MADE YUNTARI WIDYASARI DARMIASIH', '197610132010012012', 23, 229, 9, 247, '$2b$10$3rgG7rz08gCJlFNEsn4vyebrS2ynh2jzA30vLpQ9M38RK.f18ZfMe', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(251, 'AHMAD SYAHRI', '196611031989031013', 23, 230, 9, 248, '$2b$10$.3yfA1scClbbW.azyW9tu.w9vXoozfjbSxYGtm6gq6/7CIuAnC4se', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(252, 'VINA BERNALISA SN', '198609282010012015', 23, 231, 9, 119, '$2a$10$odmTlvnB81QyqWrw8IkR3uNXOuIK2nPswjlzsW5i.s5hiZE0SxG6W', 'USER', 'https://dev.pringsewukab.go.id/foto/1664434215639.png', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(253, 'EMY MASTINI', '197008271998032006', 23, 232, 9, 121, '$2a$10$7y2tCUxmKcBkj8HrAnZbveJqLs54i0ur3PinMOFHDAWPPNmYVI0Va', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(254, 'HENDRA', '198112302002121002', 23, 233, 9, 121, '$2b$10$hWuNiAebairPwTJrdR6tSukqfdAamkpHMPEdcNJLKGJwxuseTltJC', 'VERIFIKATOR', 'https://dev.pringsewukab.go.id/foto/1710895342836.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(255, 'MUHAMMAD IKHWAN', '198005252010011021', 23, 233, 9, 120, '$2b$10$GB4IfHb/4QgYLzjiwY.V9uLRU5cTzURNCjd2cTlpqTo/vTWgANBx6', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(256, 'YULI ZANTINA', '197607152010012009', 23, 233, 9, 120, '$2a$10$OQ.2sh21B85xiywopi2wNOqhI5gDmr9cCZlvLi7Rc7yWRV0OL7fuq', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(257, 'ARI HANDOKO', '197904281999031004', 23, 233, 9, 120, '$2b$10$AduV3LVXltQAXaKfFyUG0OLP1KKAt8FUkKESPmgeGLM/IpmAm9DIa', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(258, 'HENDRI AGUSTA', '197908211998031001', 23, 234, 9, 254, '$2a$10$UXhc9xJ9jhnK1Mfr3MBF1.X6/iKj3wyRFBBI24fvPiS5gmXUc.yHG', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(259, 'FERZA IKA MAHENDRA', '198202012010011021', 23, 235, 9, 121, '$2b$10$1YfA6IagcuMs1HfyGQGLf.rkzOsMS/7eoYY2HliaVJNUU/cTrI00q', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(260, 'SUHARTOMO', '197201042005011010', 23, 236, 9, 259, '$2a$10$KYWxgnN9vO22TXeJBPToX.0mEM5wkZ4/Q0QagXymNRNEj3p3NTF86', 'USER', 'https://dev.pringsewukab.go.id/foto/1711417974122.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(261, 'I GEDE MADE ADI RINATA', '199509232019021002', 23, 237, 9, 257, '$2b$10$hJLIUky5Ex/B8JwIcuLB9ejaCFfcbaX14nMhofqIwfLAbHtDNzv1q', 'USER', 'https://dev.pringsewukab.go.id/foto/1664429712091.png', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(262, 'HAZIRUR ROHMAN', '198907152019021003', 23, 237, 9, 254, '$2b$10$gRL2HrsUQd0nOmJ1sETvtOyViJ6tCjMc9CLo3UtOcwNLjJgdN/98W', 'USER', 'https://dev.pringsewukab.go.id/foto/1712034530683.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(263, 'FERA YULIA AGUSTINA', '199608152020122023', 23, 237, 9, 256, '$2b$10$g5la4gtU27HNhW7bjbdo3.PIrhUxXY6i7G7zWwzoUEx8y4.Gl7/Ym', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(264, 'RESTIANA', '199508152020122027', 23, 237, 9, 256, '$2b$10$5xpLthbmSHwwyvMH2ZMFgeHOzKYBHd8BvS3ZGIVHndqejNmjVqLrW', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(265, 'FENI MUSTIKA', '198701122010012013', 27, 238, 9, 141, '$2a$10$VnM5.pkVUAYzdwmle51XweZMZFc2Qy5ebpRYCJkxZ1U2wDtaPuN9i', 'PERMITOR', 'https://dev.pringsewukab.go.id/foto/1667262707635.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(266, 'RUDY', '197003302009021001', 27, 239, 9, 141, '$2a$10$B3kE6yeYjVd8W2Do9j4bH.eG95Mn42a2xiAwlVhHSmugXEvujEBay', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(267, 'DENNIS SONTANY', '199111232019021007', 27, 240, 9, 0, '$2a$10$8BgRhRvYrh.jXajo8u7lX.qNVj4wqsDbx/R/S.5MoFJGVpex2q6lq', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(268, 'PANCA RETNAWATI', '198303032010012028', 27, 241, 9, 134, '$2a$10$VOONyOzNN/Gg9mw0DIFiduagQqKgiByAzGSd.rK3s5eR1ckeH1bZG', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(269, 'AGUS SUPADMONO', '197608202003121006', 27, 242, 9, 134, '$2b$10$9K05g75aZvdLjZRYo28HquErUzehP252XEZra6a8qEvSSvX3dnXi2', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(270, 'ADELIA DYAH PRATIWI', '199306292020122016', 27, 243, 9, 134, '$2b$10$INBUrdkj6M6gnUOEqXMGsuTLTVe1Q2wiBZUO0.jzdaOA2.g6R3reC', 'USER', 'https://dev.pringsewukab.go.id/foto/1664756496391.png', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(271, 'ARGO SUYOSO', '197611192011011002', 27, 244, 9, 266, '$2a$10$6EmTS31BBNMQSzIXOCuI.OCrKsvLe4ithT7.0SgutK1/IVPHolehe', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(272, 'INTAN BELINDA', '198603272010012018', 27, 245, 9, 135, '$2b$10$GV9SuCKGiHdLVi9Ko1UAzec8XA3NcbJrh0U.i192AupO6DWFjHnam', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(273, 'SUTRISNI', '197812212009022003', 27, 245, 9, 135, '$2b$10$zP1OzGqgol4VM6J6d83qb.Zb0kRtXaGwkpw9PMMbvqc6OK5Bz5HAK', 'USER', 'https://dev.pringsewukab.go.id/foto/1664759920054.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(274, 'DARMAWATI', '197201281992032003', 27, 245, 9, 135, '$2a$10$mwUodE4wqusYiHjvkKjMRe6p3L7V05jOyU6IGOWyZbOnoBfX5C0Su', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(275, 'SEPTI AMPERATNANINGRUM', '196609231988032004', 34, 246, 9, 157, '$2b$10$F9o0pK9UXG/9637keca3cOZ05MHJu4lBRyAdfkngUTR4GjAIF40aq', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(276, 'ARIZON FAHINDRA', '196710031989031012', 34, 247, 9, 157, '$2a$10$zBK.P79XtlNzDESLByrJ/ezzjECgrT3rxFVtt/OKIn8BYJuUzXQD6', 'VERIFIKATOR', 'https://dev.pringsewukab.go.id/foto/1665408010702.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(277, 'LENI SETYANINGSIH', '198202202014032003', 34, 248, 9, 158, '$2b$10$.7aBnpGP9APYIU.LXdNipem1ktE.OBbRWh6aOs6Bqs/OHqze5QRPu', 'PERMITOR', 'https://dev.pringsewukab.go.id/foto/1664422626266.png', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(278, 'IMAM SUWIGNYO', '196912072009061002', 34, 249, 9, 275, '$2b$10$Sm9gn.qIXZKji6E/OSshGubGK/shRL7HbOSRS4Zzgxi0q6o5/3K62', 'USER', 'https://dev.pringsewukab.go.id/foto/1684075741370.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(279, 'TRI RAHARJO', '196605102007011043', 34, 249, 9, 276, '$2b$10$AEYEXOJ5wVp9BC2.iCdzwezfdBvrdma13Dmky2coOWoY1Jt5jVeV6', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(280, 'YAZID', '196510211986031007', 34, 250, 9, 158, '$2b$10$VmarVFv5baRgztAw4ZXLx.eilMD4ARtaKoC1zDY2TzmuVcKr1Pmmy', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(281, 'SUPRIADI', '196707071992031012', 34, 251, 9, 157, '$2b$10$hPfnYF9VfFNeUvFPMmZ02uhCtkmwPkrL6WW6kBVnxA6NKPNpXA6e.', 'USER', 'https://dev.pringsewukab.go.id/foto/1666140936837.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(282, 'JOHANSYAH', '196407121988031008', 34, 252, 9, 0, 'a', 'USER', NULL, 0.000000, 0, 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(283, 'TUKINO', '196806201989031004', 34, 249, 9, 280, '$2a$10$Jhqp1gYJmU//nEUBdcR3aOXyWP/wZGhZ8UmihNPY6JKCNi.d9.6Jq', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(284, 'DINI MARYANI', '198503312010012018', 24, 253, 9, 123, '$2b$10$PUICcnJNSyZrGIMjB8H8f..ClpwUjJTUl3DZwOk4XnqHClefk9TRm', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(285, 'SEPTI EKASARI', '197809192011012003', 24, 254, 9, 123, '$2a$10$eyD34WUb06nhPc7MMy0lzOImsv711F99mBY1WrjnN2Ap06P2htufO', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(286, 'ABANDI', '196502161993031003', 24, 255, 9, 292, '$2a$10$QyuRMQqpzRgoXCL5kyHdvePcL1E6TxJ8WqOax0CzX3G5UUtVpadoW', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(287, 'TIARA ADIKA', '199807252020122009', 24, 256, 9, 292, '$2b$10$kxlqLkWmlD5THCdppGYFlu1z4B3v.PTkSGb9kFyk51nJYiNcscRH.', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(288, 'ELI HERMAWATI', '199206252019022006', 24, 257, 9, 284, '$2b$10$HaOOWo78F2ju0t6eio9Ocu99lVDi1qfOlom4QZzvt4jT/GyRMG3SC', 'USER', 'https://dev.pringsewukab.go.id/foto/1667277329012.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(289, 'ROLIN ADITIA PAHSA', '198805192019021002', 24, 257, 9, 285, '$2b$10$IBW0LPjznBXBVCEI6FmL9.1mVy75tvkwJ0cQtDNT3sjsO4xZVbwt6', 'USER', 'https://dev.pringsewukab.go.id/foto/1664757261509.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(290, 'ANGGUN PRIMASTUTI', '199710112022032010', 24, 258, 9, 292, '$2b$10$YVghiUGenP250Myf6VM7NeKG5m7iBxkp2/V9MovX/jXd7GORwWVTy', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(291, 'LUKMAN', '196905281991031006', 24, 259, 9, 125, '$2b$10$choGZ6JxXWwoOyxjJJUXkeq2cWt51KvWwcOcz8Y9/H2QHXsJyOtzy', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(292, 'ESANING WIGATI', '197205052007012007', 24, 259, 9, 124, '$2b$10$OnXpZsftPcx80qCeM.N79.cIfSnUk.oawEXgWIyQe6eX5EMvD10Oi', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(293, 'YUNITA LESTARI', '198206172009022008', 24, 259, 9, 27, '$2b$10$5/tGGnlT0p1XqhwfDM4UM.AjjFpPFL2yrkKlMm31xS70D2poQ.jA2', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(294, 'DIYAH RATNA KUSUMAWATI', '197909082000122001', 24, 126, 9, 27, '$2a$10$zs8xTwRNzc1YXRsWx.cR.uxg.oCGVZwm/x3MWOMs9JtEHrj1hMwZ6', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(295, 'SYSCA DENIA', '198208032011012001', 24, 259, 9, 125, '$2b$10$f2Wf6Sa53gt/7eYVqNFt/.RcIKAgcW9tt616FmZ3tWitfIFubRPh.', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(296, 'EMILTA', '197303062010012002', 24, 259, 9, 124, '$2b$10$/Rm6MLo/GAJrHQ5j6ZBHtu1tKb4CCglr0FBdsxfen1yFAHEBrRBGu', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(297, 'NORA RISYQIA', '197712032010012004', 24, 259, 9, 124, '$2b$10$Mas1ngFHoINQLQYco.CMteDgHu9tn/HsrP9165fhUtQVZqCDMilQu', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(298, 'HERLIN NUGRAHENIE', '198212272010012014', 24, 259, 9, 27, '$2b$10$UckJNZYmonqRKWrrJ0Rj/uiNT98.xc1Z7T.v9juDpvlgZxsFpE1CK', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(299, 'SURYO NDADARI', '197711112009021003', 24, 260, 9, 27, '$2b$10$j66pturJVyrU8rjEV3b2d.IZutCDyPM2xGkxjxNkOE56EapQs7rCq', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(300, 'ARIES KARTIKARINI HANDAYANI', '197903252005012006', 20, 261, 9, 0, '$2a$10$12QkOYF5.o2ElHRdjyxe8erBQ6luGV8E./aR9oysqX5tK9FUDimby', 'PERMITOR', 'https://dev.pringsewukab.go.id/foto/1659598801732.png', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(301, 'HAKIM ANSORI', '198601072015031003', 20, 262, 9, 0, '$2a$10$Nu0CMM04zOQxb0EiStFPROM9HRF3DSVYbGDD.WX0fIo0UpR8/Lhcm', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(302, 'FIRDAUSI', '197409092006041004', 20, 263, 9, 106, '$2a$10$3QlpddmFdDNY521tlPKGqOWDo2aElC5bPO91MWbQZ7y6advSSEXDC', 'USER', 'https://dev.pringsewukab.go.id/foto/1659058654244.png', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(303, 'TAUFIK SAPUTRA', '198610192010011002', 20, 264, 9, 105, '$2a$10$WxbpwTWvdo8tLFTTh82clu2CFACUC/Le/LQS7Hmd1RzNsszQUtDta', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(304, 'YUNI EFRIZAL', '197306261997031003', 20, 265, 9, 106, '$2a$10$xpP/NaXnAOaDsuRLgX43b.SxLcTqBDN0r5oULNbyH4l0Ut0xf3Ury', 'USER', 'https://dev.pringsewukab.go.id/foto/1664504000740.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(305, 'ARVIANSYAH', '198110022010011020', 20, 266, 9, 105, '$2b$10$IUU72ijWdq3yDFPjUdqgRuBvX1p5YeTTcq5pYUfxtev9QrPTKJc2S', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(306, 'INDRA MARZA', '198410162010011015', 20, 267, 9, 106, '$2b$10$if4xx9BKQzNrSt70xH9PHek7Q0HyAaEPpR18tzNgwcuM6frilar.y', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(307, 'ICHSANUDDIN', '198405112010011020', 20, 267, 9, 105, '$2a$10$kBOvdKCCKiukF557jbHEWu925Kgzj0K0N.4pL.yhfu/lhRUXAfds.', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(308, 'DWI PURNAMA SARI R', '199502142019022010', 20, 268, 9, 0, '$2b$10$S9vp9B/lTCin.yLN9yx18eg8Mz7GS7D5poUqs642wQgX9X5ztPTVS', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(309, 'ASTARITA', '198602022019022006', 20, 268, 9, 105, '$2b$10$q1/m7ty2Ayq7tDzRufkUNeX/MuUwi3lT.QNjc1oIfTzsg0CS3xu72', 'USER', 'https://dev.pringsewukab.go.id/foto/1659598164260.png', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(310, 'TRI LESTARI', '199510182019022009', 20, 268, 9, 105, '$2b$10$PhAqGeg6VYNcKP63Uomqv.swcSm7yVLoG29rOnSeNICU2Pzvz0PuC', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(311, 'AGUS BUDIONO', '198508182011011013', 43, 688, 9, 105, '$2a$10$CL.ng0eyva4uaKoN0hdn6ek8snjoJ1dnL9uoZuAL0GZ4seVHOx7Oe', 'USER', 'https://dev.pringsewukab.go.id/foto/1659058624435.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(312, 'ARYA SAPUTRA', '198312132010011016', 20, 270, 9, 0, '$2a$10$NQNwNSFL.DTOsJ2atS2dz.DXSmBSQNuMh9c/M2NSPixFX3AEYHoqm', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(313, 'DJOKO SUSANTO', '197201041992031003', 20, 271, 9, 105, '$2a$10$6ipP3RjMN9LU3XzLGa3Oi.uVg.UrthJKyjHjwwM1HouJDFJk8OAlG', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(314, 'NOFRIZALDO', '198611012010011009', 5, 28, 9, 25, '$2a$10$ejslIvafMwLlaNfTGj9WC.Dg/Ilr9ah27OkGm3PSB.CVyn9iixdCG', 'VERIFIKATOR', 'https://dev.pringsewukab.go.id/foto/1745896275899.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(315, 'DIAN MEYRIZAL YASHA', '198305192010011010', 5, 273, 9, 29, '$2b$10$Ha21p/vyyK8X3WebafxyyejLOlKjobO4EurfQ5x7K6WTXPaH4ammu', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(316, 'NOVI SISCA', '198511182010012014', 5, 274, 9, 29, '$2b$10$o2QxNdtrQJ9c/q.69Tkyz.4hkmWkMNamRtcdlthJiUTkkd7XA37Dy', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(317, 'YUSUP MUTAKIN', '197701212010011003', 5, 275, 9, 30, '$2b$10$lHm6xJUodbc6gGqIMSorV./dHG.dDIUReutRZZ5aZgWLoCxYzmVzO', 'VERIFIKATOR', 'https://dev.pringsewukab.go.id/foto/1664757393376.png', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(318, 'MUHAMMAD ZUNAEDI', '196806221992031004', 5, 276, 9, 30, '$2b$10$MKqgGgObEjSbPSrg5NXVvuuKVAddSeYvuqGFI/lw6.OQHZE4uj4MS', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(319, 'DIAN ANGGRAINI', '198703222011012008', 5, 277, 9, 28, '$2a$10$ZBSnc4hMFmDuch4RGCMKbeGhXoIJUJIdKnNTb8PPYw1om8TXZAIiu', 'VERIFIKATOR', 'https://dev.pringsewukab.go.id/foto/1721873029629.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(320, 'TOPAN', '198404042011011010', 5, 278, 9, 314, '$2b$10$wgwcrwExvypF3ormFBAdJO5TJ6wmcm7gYWtX8Tuz4niMomAqaA5oa', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(321, 'DESSY ARYANI', '198412112006042006', 5, 279, 9, 28, '$2b$10$dOm9s8o0m0.zyyhIqt.ZfugqfgfZDF3SmQ3PSjnhtwBbF4xH5HA4O', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(322, 'RAMASARI HUSEN', '198107172010012012', 5, 280, 9, 26, '$2a$10$twJxB8lny5YuNtbZ5mnXy.NW.u5EMir/tZrcHOn48P6w0WY8p8uxG', 'PERMITOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(323, 'RIKI YANWAR', '198401022011011006', 5, 281, 9, 26, '$2b$10$e6yYB3/W2eZSzTWkdkU4ouBxR3vaA8VTZ8gzRnBcCa/YzAWODaxru', 'USER', 'https://dev.pringsewukab.go.id/foto/1665362303203.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(324, 'AULIA ANNISA', '199702162020122019', 5, 282, 9, 333, '$2b$10$5hSxIE8M1ZblrhyKi23gku3Aq6MZNt50Pvq4/jpZwl0UFHiOVSxF.', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(325, 'SAHNIYAR', '197910182008012011', 5, 282, 9, 333, '$2b$10$GoO7OufjMqDlo1n.i7VawOZk8rZXspGSuSlJj1DAR//c41i1bwkGe', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(326, 'NANDA MAHARDIKA PUTRI', '199809222020122006', 5, 283, 9, 321, '$2a$10$5LQPQg6HgWDlFFlYDsdA4eOMFgoSxUSxQw/7Y/eYAU3fJxA/M8PkC', 'USER', 'https://dev.pringsewukab.go.id/foto/1672981646872.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(327, 'IWAN APRIYADI', '198404012014071001', 5, 284, 9, 318, '$2a$10$Aje1Mp2NJhfr3fMu0Cvf0e2icf82qB/nKh02MhJF1Bvntxfb4XwmW', 'USER', 'https://dev.pringsewukab.go.id/foto/1683596546400.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(328, 'EDI SULAIMAN', '197910022011011002', 5, 285, 9, 317, '$2b$10$d.ZJJOkAzup1gwSvI/1ENeCl5vYVPZzQ25qBJEhVpkJ265aG3Mb.2', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(329, 'HENNY MEDIA OKTASARI', '198910132020122007', 5, 286, 9, 320, '$2a$10$JR5vazThUIy8U/IlxDngkOJ.HbIwDsBScO67em.dXbnX9lpHl0y6K', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(330, 'SUSMADA LIWANTARI', '198202282008012013', 5, 287, 9, 29, '$2b$10$e7RJFQUNR2Xj43VyBqHQmOQiLKQmC1TYZJr7eZ9xxW9WQ6B2Qmf8q', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(331, 'SANUSI OSKAR', '198906112015031004', 5, 288, 9, 319, '$2b$10$NfuLredv87Eqn1bktP1upegyOlQxAubr19SDcrh17CLD6yhjsny1O', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(332, 'YUWINDA ASTYA PUTRI', '198502162010012021', 5, 289, 9, 28, '$2b$10$3QdcZRgJo5Jp8SrtPe.uO..UXc0p4LG1xfAEOuauvEbzbpDk7DWrC', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(333, 'SETIA NURHAYATI', '198106022011012004', 5, 289, 9, 26, '$2b$10$nbbIB44t33S73psDeRXaqu46kpa2gGLockYKGHaMZ7Pt10wdTjqwS', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(334, 'YUDI YANTO', '198207102010011006', 5, 290, 9, 314, '$2b$10$xuBxxGWJwrSZ46qaezXbjeSBYcdK5VXLAUwOExO/JZppRSx5oZdi2', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(335, 'ROMY HERWANSYAH', '197609201999021001', 16, 291, 9, 85, '$2b$10$yrltI4shsyOE5fl1HuJ7ZOsGBVGyYzpr/4b08pkP62FhPQqJqq9Qy', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(336, 'IRA NOVIANTI', '198611202011012014', 16, 292, 9, 83, '$2a$10$eE6yYqMhcRPAY1uKHeoEtOTDI8QH.N7VIaAimEza32THbCDxGVfqC', 'PERMITOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(337, 'YUHANDI AFRIYANSYAH', '198004222010011017', 16, 293, 9, 335, '$2b$10$Xz.h3QXpPph97Bfydf3s.OFwRE31eh5k23AJGyppOvdXf9kDhNMLC', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(338, 'ENI STIANI', '198710272011012010', 16, 294, 9, 83, '$2a$10$26n9TYvX21aGbmDHVfe7juqpOKeQjmT9osvXVnYKLp2WtM8k2g8F.', 'USER', 'https://dev.pringsewukab.go.id/foto/1669281550397.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(339, 'DWI HARYADI', '197705121999031004', 16, 295, 9, 346, '$2a$10$GGzacgWYxtDOKBidAq2gwenzgSySkjNOymWMuUpBzNTPRHUBHlZYS', 'USER', 'https://dev.pringsewukab.go.id/foto/1665361420590.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(340, 'ARIFIN NOVRIANDI', '198111222010011025', 16, 296, 9, 86, '$2b$10$U22CdOuO0rGZrF5n4skbI.5xFT2kXYhWiAR5M9nH5s9XyGhgEEMKG', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(341, 'MUHAMMAD MIRZA NAWAWI', '198507092010011019', 16, 296, 9, 86, '$2b$10$EWFwYjqZYd.EbH/EsYR.eOC38UbJDoVhu.RNOEE624YOmSJyvC.4.', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(342, 'MUHAMMAD RIDO', '198705092010011010', 16, 296, 9, 85, '$2a$10$UwL1Fc4UtOkgum3dIGd78OhTgzSKy1oxKHqv5OanVTpRAfAVqMa66', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(343, 'YEKTI UTAMI', '197507242010012009', 16, 296, 9, 85, '$2a$10$3n6JHYj9.ydk65RKiCqaG.712/onpLKZjxwmG84fBRolYQUDt7jCO', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(344, 'DETA ZETIA RAHMAWATI', '199212312020122024', 16, 297, 9, 338, '$2b$10$2ZKvewC7Zpv9eNExAgJ3eO5vidx4HhCXOueVT4Ome8Hfc0KmKoUO.', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(345, 'ROHAYATI', '197601102007012006', 16, 298, 9, 84, '$2b$10$vKcThgnLpToCmOBwQ4NKOeK9fCpaxhJycitm9RWrna55z51TIZ1JO', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(346, 'SILVIA HERNI', '198406012011012009', 16, 298, 9, 84, '$2b$10$JKGPjLKQiSvA9KaxUN48T..I7h9w99.6fvCeYywed2jsFcDxwnKXC', 'VERIFIKATOR', 'https://dev.pringsewukab.go.id/foto/1672036407364.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(347, 'RENSUS RENATUS SIMANJORANG', '197810142006041004', 16, 298, 9, 84, '$2a$10$L5Aa3fcc6JjboTv1tIIVQusf0qqsOBR1WnsbFxC9StPsFz0BXHiuq', 'VERIFIKATOR', 'https://dev.pringsewukab.go.id/foto/1707266260319.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(348, 'M. YASIN', '196606052014071004', 16, 299, 9, 341, '$2b$10$uuPkdALhL8nCk6qV4MoLmu3c.apgpUIj/xrgI76IWiow9EG.UAmr6', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(349, 'M.RIZANI', '196611222014071001', 16, 299, 9, 86, '$2b$10$4UdTBIvRJmYaJaNZ8Q9HZ.MDu9flxriWa5P/orhAj57AAa9IT8vCO', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(350, 'LEZAR ADITIA', '197603292009021002', 16, 299, 9, 343, '$2a$10$VpHQolsFl7t1gbhy8sMwU.W.XxMfOp8yf08oBos78Q2Ev/58SWPOu', 'USER', 'https://dev.pringsewukab.go.id/foto/1665016719193.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(351, 'M. KAHFI', '196702062014071002', 16, 300, 9, 338, '$2b$10$zSCN/IGHJHnbgDeS7uZvzucHBVvpV1k/sbjIG6ICVGxLtxMkqkdzO', 'USER', 'https://dev.pringsewukab.go.id/foto/1666574359431.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(352, 'ANDI NURCAHYO', '197904012010011010', 16, 301, 9, 86, '$2a$10$a3dcUgWOHmgtJcGi4IHPJuppRY0n8N3.g1EZq43OYhCZZ0XfH1IuO', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(353, 'ERWIN MAFRI', '198102012010011015', 16, 301, 9, 341, '$2b$10$9wYciN7xpfDuXEFKDVztpuXuyl3MGsls/TMLY4zl/nJ5GNOcV6546', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(354, 'GITA ANGGI OKTAVIA', '199210252022032013', 16, 302, 9, 347, '$2b$10$D5xJAWdMFw6Q8xMlkfRmCO2fQe05TIIhqa3NlAmfg2gSzPGYCEx1O', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(355, 'JIMLY MAJIDI ASAIF', '199610102022031008', 16, 303, 9, 341, '$2a$10$z0uCrdXPoY.g.0qASBsCje.o4MBE0nEvlqW0TxFg6MoGK2V8DY.26', 'USER', 'https://dev.pringsewukab.go.id/foto/1664760460580.png', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(356, 'WIDODO', '197202262008011009', 18, 304, 9, 91, '$2b$10$nRmNgbHwb8onmk8fZ6CHzueR4FkbV7GQgGjiWQsofMiVpi7LSUfk.', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(357, 'JOHANNES KOHERU LUMBAN BATU', '198311262010011016', 18, 305, 9, 91, '$2b$10$8ecOSqPXrQ1c6NXO3/PJfOkcHWkdFaEqx3VG94pIX9mLwwxx2J.t6', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(358, 'SUHONO', '196503091994021001', 18, 306, 9, 91, '$2b$10$DZ5gasAheDlKK/PiBBwJheuWL2BHQ4ZHSuPh/y6LRi8lZKiAqLxom', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(359, 'HERLINA', '197308172007012023', 18, 307, 9, 92, '$2b$10$iyI8yeS9iir0625MlfQ/T.llFcCk9T05yiwmlmQ694RnlytxznGHm', 'PERMITOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(360, 'TRI ARIYANTI', '197802212010012008', 18, 308, 9, 92, '$2b$10$8ZyKARmxXeod/hnFnaxqEelQKWnT0vx2OCwgsI1978RHvlCcvydUG', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(361, 'REVI RAHMADHAN ERGANTARA', '198107162010011011', 18, 309, 9, 95, '$2b$10$.xFjL0uPV5TY.xoOtFa.cuaWTq.pyfarh5o25NrDXx3L1rQjiCRtO', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(362, 'MUHAMMAD ANDRY FEBRIANSYAH', '198202282010011021', 18, 309, 9, 97, '$2b$10$Wu4pqbXgwh1EO9BSt7MOoeCt1kahfMC0bVmgkQBtzc3oMAKBGfn.u', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(363, 'RAHMAT ALI WIBOWO', '197507102010011015', 18, 309, 9, 97, '$2b$10$xhmBT9s1P.eIswa8ZYcgcuDWznuvIS4aMYfa/H.88xYlkPL.zbnxq', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(364, 'YUNITA FITRIANI', '198506302010012019', 18, 309, 9, 97, '$2a$10$GfbzeQfxYD2nR07iYWTi5eTfvxRjX013.LizHGvs3Mu7DqtEK7XJK', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(365, 'ASEF FRAN KURNIAWAN', '198105162010011012', 18, 310, 9, 95, '$2b$10$56GTNMaP/DRcR4t/istbSuR2c97U.AqtMT6rxE3KFj0.kjVX81qwC', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(366, 'YOSAFAT WAWAN HENDARTO', '198503242010011016', 18, 310, 9, 95, '$2b$10$QtswlDOqgKxtrnxTOQZuguW0spXtKeOuNAUYvc2nc8fndVSCYa8.e', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(367, 'AGUS NUR SYAMSI', '197208142007011005', 18, 311, 9, 94, '$2b$10$0iMjn6gjEuZrO94Qfzp.duMvZl8guwI8h1R.Haw0Iis7KjWTbwej.', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(368, 'DEDI SURIZMAN, ST', '197707252006041006', 18, 311, 9, 94, '$2a$10$v6UR33iV6ox1J0xIT78XzelT0TJti1xrdH5KhDxS0PGaufwTAXSSa', 'VERIFIKATOR', 'https://dev.pringsewukab.go.id/foto/1664757988397.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(369, 'EKA SETIAWAN', '198010102007011017', 18, 311, 9, 94, '$2b$10$OrFOI/oi.KXmE7Gfii5yV.uQ75lR0EU6EkJoODlDHEc2rTy8Sclye', 'VERIFIKATOR', 'https://dev.pringsewukab.go.id/foto/1665658588248.png', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(370, 'FITRIYANI', '197510172010012005', 18, 312, 9, 93, '$2a$10$9uez2vOxydGaHjap3zDc4.UfFod9DxCCHO.Gme1.eyaytzXMyVEp6', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(371, 'IWAN SETIAWAN', '197302242006041006', 18, 312, 9, 93, '$2a$10$XrZIgA5vpzQ3kMlGh06fHO/4Wlqq5/XojkkWKdzkKfHvbpLRQ8Deu', 'VERIFIKATOR', 'https://dev.pringsewukab.go.id/foto/1707273087920.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(372, 'SUSI YANTI', '197804102010012008', 18, 312, 9, 93, '$2a$10$oqwuOVie94gok9x3k98jc.Rxl/nIk4D8VgFTH8JaNsZsWTTnKTt9C', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(373, 'ADHIKA PERANGIN-ANGIN', '198306112010011013', 18, 313, 9, 360, '$2b$10$Kfr9eM2T5a6j7Fjif2AsveJPv43xKUaA06NgY8ML8QKV2DULv2.Yy', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(374, 'RITO SUJOKO', '197507302007011005', 18, 314, 9, 359, '$2a$10$olBKqWbFQAgb0mj4AVihN.xdVKrY5b8xQBp/M0Qmm7KBRS5WCCEvm', 'USER', 'https://dev.pringsewukab.go.id/foto/1718931062597.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(375, 'ROSIDIN', '197306082007011025', 18, 315, 9, 369, '$2a$10$e4AyWpQTxoWie69BsPOjwetkEIzBQThQZ/O2QXajO/.gQAyH.cEzq', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(376, 'BAMBANG SUGIARTO', '197712162007011006', 18, 315, 9, 369, '$2b$10$jOO9Dt.sSpxsrlLj.P95UunpMi8/Go.2yvZakL5nAvCD0rZQlm86q', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(377, 'NGADISO', '196508122007011010', 18, 315, 9, 369, '$2b$10$rnKgxsoVYxhQVZqHFeRGDue5mtLhyZmsQJVz67oXAKiiZIBDKJjky', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(378, 'ALFIAN', '197305072007011009', 18, 315, 9, 369, '$2b$10$NtBd3SOn.6EckLUrWKngnu2sjJ0RWZcRcjIGuIXe8Xc.vcdn44lsm', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(379, 'NASRULLAH', '196509272006041002', 18, 315, 9, 369, '$2b$10$Tefwi79vw0NeM2fB7Wg2NePabOKevFIPLJQXJjIi2PUjLAuNXZnaO', 'USER', 'https://dev.pringsewukab.go.id/foto/1667900289484.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(380, 'SUYARNO', '196906162007011011', 18, 315, 9, 369, '$2a$10$hcx8yIpA4byh/EVaGDDvbOZppDSGcPbzABzUS3/rA7uUExnMyfM.6', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(381, 'SUNARTO', '196501102007011004', 18, 315, 9, 369, '$2b$10$oFyl4ipa9/f6tOQbrlbZX.eZF1iSOAJ377w5TA/VSbJZP0P4blY3K', 'USER', 'https://dev.pringsewukab.go.id/foto/1668489843565.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(382, 'SUGENG WIBOWO', '197407132007011005', 18, 315, 9, 369, '$2a$10$RDisbwuZfT1Q0yQGd7Yi9ebztHA6/e.tzf.S60kgatwnLYQoiQs82', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(383, 'SUDIYONO', '196810192006041001', 18, 315, 9, 369, '$2b$10$kXqUbNifXmMSMlK5cVbXLuRFqdR7u5DsK2wuY6bEv7csOx7.5vYY2', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(384, 'SOHIBUN', '197202042007011006', 18, 315, 9, 369, '$2b$10$1GoTzB8w8QEpDoZ0utsnSumiQZhNB1XROZZ.5Sl54m6DvCynaeL1a', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(385, 'NURMANSYAH S. LAMBRA', '198101172011011005', 18, 316, 9, 363, '$2b$10$d63OXq0CGpyqWMl8H3fVwu8xqqHeDaneMwBJueQ.esyVhZ2XoQpIq', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(386, 'YUNIDA PUTRI RAHMANI', '199506272019022005', 18, 316, 9, 362, '$2b$10$FcZMP7FnmChmswg6LOK5WO3lj2DfxFhNyMkGe0.Ee46IMeGmALThy', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(387, 'AHMAD SAFEI', '197007172007011017', 18, 317, 9, 409, '$2b$10$QvOXRT5Z7fU6Ll8/8fWD1uBSYpzsQHe2rW6BC09h9WlqTxOBhkNMu', 'USER', 'https://dev.pringsewukab.go.id/foto/1690266516350.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(388, 'YUDI KUSUMA WIJAYA', '198110292010011011', 18, 317, 9, 408, '$2a$10$Z0TESgL7jxgUSaeWDmhpWumsnS4saWtyuM..5A0bb5hOmTPqvonfK', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(389, 'ERIAN AFRIANZHA', '199804222019021001', 18, 318, 9, 369, '$2b$10$LUw28izTbnjMdKV59c7NXuaIkQe5VDV9sxUCoGwT8s8gqVP/E8IpS', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(390, 'BEKTI WAHYUNI', '198510122019022003', 18, 318, 9, 367, '$2b$10$mBtIaYO4GnX9CV7p/zbi/.xvp.05KWkhd16S1si19ehtjQET0I/56', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(391, 'SIGIT PAMBUDI', '197512052007011019', 18, 319, 9, 360, '$2b$10$O9Xxtqf6i//.BUEocgHRj.ewzsDWBQv6y.UijogbqFeH2uer5uK7.', 'USER', 'https://dev.pringsewukab.go.id/foto/1712297304902.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(392, 'RETNOWATI', '197402062007012010', 18, 320, 9, 371, '$2b$10$UgVxDrLOsWACer0y8KFio.ofj7gcX3m4y4V1OyXnEotDeYxwE/W/6', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(393, 'TEGUH PURNOMO', '196407241985031006', 18, 321, 9, 410, 'a', 'USER', NULL, 0.000000, 0, 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(394, 'RYO KHRISMANIK', '198502032011011008', 18, 322, 9, 94, '$2a$10$zz8hxokvAGcbZIgq0U5QLOh0MANQ.FjNiO.PmCXTHZHZ20H4lSalW', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(395, 'NURSIMAN', '196804051998031009', 18, 323, 9, 357, '$2b$10$a89YahaG.FijLXPFU30F4eaN3OI.geisynaSBNUvufaL/1EOB6A8u', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(396, 'NGATEMIN', '196807012006041011', 18, 323, 9, 367, '$2a$10$CFtRoYrtiYa76EdXAz2Ni.q6ep6jyroRuSiTkPinh8KrzCtEYW1XC', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(397, 'NURIMAN', '198202182007011006', 18, 323, 9, 368, '$2b$10$vzzBt1klbJv/b.kKq9MYBu8neNccxw.LSjgG9hQFRhaULDmyzEM/C', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(398, 'TRI HARJANTO', '196712122006041006', 18, 323, 9, 93, '$2b$10$caR9EbYpBC7EKnm.POkwi.JD4eajvJsyLoZdrZwLZFCAIepBeQMMi', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(399, 'SUISWANTO', '196505121998031007', 18, 323, 9, 357, '$2b$10$dbKN/nUkBNnni99Nxcjtqe5I7ITFcAJ3OXBEZqqqdhdAnBWxCCPhW', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(400, 'WIJANARKO', '196505031993031008', 18, 323, 9, 358, 'a', 'USER', NULL, 0.000000, 0, 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(401, 'YUSMAN', '197205042007011009', 18, 323, 9, 357, '$2b$10$qex0wajRajRbBuJmTW3UrO1dRr9XTYpZrAq7l0PfegtPYLKecnVnO', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(402, 'SUPRIHATIN', '196601012006042008', 18, 323, 9, 356, '$2b$10$eccH57VwckEeLTE1NwdWvOCjx1jdIWVXO1NIUCXiV8SqcWsiEEzDO', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(403, 'GIRI KASIYANTO', '197505102008011018', 18, 323, 9, 371, '$2a$10$b/HrenLSeTJ79YiVnHMfoeZCRtERA/MZsgpZauxhSzzwegpMYDMYC', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(404, 'ANDI YUNIARTO', '198106252008011006', 18, 323, 9, 366, '$2b$10$2m05eXP4ACSmwY3MLUvofeYdoRy8uOarudQqqvtH36xcYDkO/30o6', 'USER', 'https://dev.pringsewukab.go.id/foto/1665453963102.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(405, 'M. DARTO WAHAB', '198212312010011055', 18, 324, 9, 96, '$2b$10$4zQVAWfwU3aM23EAdJh5Beazwz9JGVaKsehV9P2sNSEPEqAvq6Feq', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(406, 'AMINULLAH', '197705172011011001', 18, 324, 9, 96, '$2b$10$NgS6PD0RxvEvJ9Q0bhnqn.tavpFa5sWkSkqnCf6lIEiHh9WPlxleW', 'VERIFIKATOR', 'https://dev.pringsewukab.go.id/foto/1666157295968.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(407, 'DARMIATI', '197506162010012012', 18, 324, 9, 96, '$2a$10$909TozUeacd9U8KzTYChVuDiCp49zqfTb1Is5W41ay5Mmb.X3v/mC', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(408, 'NUZUL WAHYUDI', '197801292008011009', 18, 325, 9, 98, '$2b$10$7hc6YGVqJJ0DtLE0RyGVoeonkxr3PjjGVFd0frn0eXEba8ii0YPVC', 'VERIFIKATOR', 'https://dev.pringsewukab.go.id/foto/1738657107888.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(409, 'HOTMA PARULIAN BATUBARA', '198103222010011008', 18, 325, 9, 98, '$2a$10$SUJXwDdMDjE1kqA76n6WNe/WcYqGOSikEa9x21TiwMNQi5CkqTRBy', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(410, 'LATIVA INDRIATI', '197108292006042005', 18, 325, 9, 98, '$2b$10$gMhs9DbTqU7uTCyZI7UPnuqzry3YIcNr2KUwN1UB7QwShdCJjwo6e', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(411, 'NGADIRUN', '196503152007011008', 18, 326, 9, 358, '$2a$10$of4kcaQffHyhAQuG33z2wunj/gjFGYhBC0YdshXkjg0nQmmmkiShq', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(412, 'FRANSISKUS ASISI JOKO PURNOMO', '197410012007011009', 18, 327, 9, 360, '$2b$10$mNAI2pOWbzxh4R5IrO/OCOetIq/n3NM2MYqIgT/ZR4uAj684Ev/Hi', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(413, 'ANTONI ANGKAT', '199208282019021002', 18, 328, 9, 406, '$2b$10$UyCloxb4G3OSAH2h2Nxwu.AuUsiwWrCCIjoyDw3rv2dnLoUQ8p1K.', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(414, 'DWI FITRIYANTO', '199504052019021002', 18, 328, 9, 407, '$2b$10$xnei4/RyZzUisTiHclx73OOny/tnxO2y2maizSWFZfW/qrZkjeCbG', 'USER', 'https://dev.pringsewukab.go.id/foto/1710410576524.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(415, 'LINTANG DEA AMANDA', '199505262019021004', 18, 328, 9, 405, '$2b$10$zw4rnKb1AbPFs8VL2JlYd.6yxKL7BvuNRxU0X0X414/kpfrYgR00u', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(416, 'DIRGA RANE AGASI', '199508222019022002', 18, 328, 9, 406, '$2a$10$rN60vG9yuJZpfMyAQHNCweUXVtjipD1/Qx5Q7wIpxjYfoaPh9J.7C', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(417, 'IRWANSYAH', '198403052010011015', 18, 329, 9, 363, '$2b$10$GH/9wpguZ4C98wbLqQoa8.fp4ndiZP7nfy0UnqVmm4bVv3JkDY7Zq', 'USER', 'https://dev.pringsewukab.go.id/foto/1720508864017.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(418, 'PONCO SUGIARTO', '199009272019021004', 18, 329, 9, 364, '$2b$10$0Zq95EIaEDPDA/AuKze4YOYvy.aMBD6KQA6SEjsLzAsKN1KNzji2e', 'USER', 'https://dev.pringsewukab.go.id/foto/1664869316993.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(419, 'ABIMAYU NOVANDI', '198411232019021002', 18, 329, 9, 362, '$2b$10$sRbakiwyEb/4IslfrA.wXOHabCP9wdIKBz3YOkMi1.yzNAVOn1Kbe', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(420, 'NOVI RUMANSYAH', '198611112010011012', 18, 330, 9, 373, '$2b$10$IjEa/ZUaAO2wYk1CYvl6ZOv4BuNwr20p3XoyKm4bMczub88C140Eu', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(421, 'NURHAIDIR', '196501012006041009', 18, 331, 9, 372, '$2b$10$c3sQ3b2DBeQ0mK6eRic5VuD5nE/0QpAanTFsFOKuWARfkR1bzeOtW', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(422, 'SELLA ANGGRAINI', '199508082019022008', 18, 331, 9, 370, '$2b$10$mIAo28jmavffkNWMOsmO.uNd5enxSPrECx32U2tTCEF8RyCuvw4Bi', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(423, 'RIO ANGGORO', '198606232019021002', 18, 331, 9, 369, '$2b$10$WPBpgwYRROYreVlOnehS.e5hw3IU0ArqWrAJ9uQUylqWiDrZwIM6m', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(424, 'ADELIA INDAH OKTAVIANI', '199610282019022004', 18, 332, 9, 365, '$2b$10$x4nMP0jlmykXYWVEbc.9meidhcXzW86VCL5Lm18QbbrdCxV.hLcvG', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(425, 'HARI WAHYUDHI', '198509202019021002', 18, 332, 9, 366, '$2b$10$OiagHfeJl4asKGVYjFaaCuGobUUbhoWRy/czwleD7Q9/FQbnEtLbC', 'USER', 'https://dev.pringsewukab.go.id/foto/1664757913615.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(426, 'SUHARDI', '199208262019021008', 18, 333, 9, 361, '$2a$10$9FkI/9S3qPVs6l47g8HnAuEb.21TCZ5LWnNgBMOTeg7nSn7fay5uS', 'USER', 'https://dev.pringsewukab.go.id/foto/1664756474915.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(427, 'IKA LINDA YANTI', '198106032010012010', 15, 334, 9, 78, '$2a$10$iTGW0drTImUJGafOEO2/yuTHYb5qW/zJs2P4u78xYtOIHuvppwFTa', 'PERMITOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(428, 'ROMANUS YOGA CAHYONO', '197111071992031003', 15, 335, 9, 78, '$2b$10$.sTI1J/8hpI5FwDWSUBOxenuDbnMtaqV0VvLItYQv2eiovZMlbBjS', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(429, 'SUGINO', '196908051991031009', 15, 336, 9, 427, '$2a$10$DiiXErAcB2uxd3Y7X8U44eJcKLSrSVnr4TqJpjfR3y4gcBwAv5Rny', 'USER', 'https://dev.pringsewukab.go.id/foto/1664504841430.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(430, 'OUKSA IMMARRDUIE LODY', '198911082011011002', 15, 337, 9, 451, '$2b$10$4D0JsvCklP8vc3X4Wev6hugGbTsQIpSIQmQH46JlOO/qEcf7dIrQ6', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(431, 'INDRA AJI PRANATA', '199407052022031012', 15, 338, 9, 441, '$2a$10$zaHeYCX4BxQ5Pms.32g0X.GSxVYpFvNanGXDRg/alSOf/5uzUfhIC', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(432, 'BAYU ADITYAWAN', '198910182020121007', 15, 339, 9, 443, '$2b$10$bpaZ8eOsq1gL.dvoVpcwjui0nd2LyBBbXErjGYwvC6UW35MyIAkwe', 'USER', 'https://dev.pringsewukab.go.id/foto/1669169406807.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(433, 'STEFI TANYA', '198910072020122014', 15, 340, 9, 451, '$2a$10$assfYXl1TIpWmgt5G.DuB.Xpq1lg.1VGyPGdogIpN2s0fLy/Xdoy2', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(434, 'IDA YANI', '199102122022032003', 15, 341, 9, 428, '$2b$10$iq4zRlMzOT0Fx4KmXLxS4OwuaZx4volcuiGnyhVWz2R4zN3FL7Sxm', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(435, 'FENNY TRI RATNASARI', '198902162011012004', 15, 342, 9, 427, '$2b$10$yz3pnpyobGEieJRgkBTE0.8TXWh5lCN59578xhqiarTn6LgT9lEgK', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(436, 'YEFANDO AL JAYA', '199712192019021002', 15, 343, 9, 427, '$2b$10$Qjtv7hjxzIsNKJJlHXOYk.bZtb2Lz6buWoN./0Gl.aaDqls0qBv8i', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(437, 'MUGIONO', '196812061990031005', 15, 344, 9, 428, '$2b$10$fw/d.98D//NrjsYwrViZ4OwAtelz5Ey89.n7jINrsDycN482ur/d6', 'USER', 'https://dev.pringsewukab.go.id/foto/1664506950833.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(438, 'MARLINA', '197904202010012002', 15, 344, 9, 427, 'a', 'USER', NULL, 0.000000, 0, 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(439, 'M NURMAN SYAHRUDIN', '196608241989031005', 15, 344, 9, 442, '$2a$10$8S/S//4UukihzQ6C5a4Cxev2twEB2CXRqWYqxcFNMLhbdddaJX.pG', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(440, 'HENDRA ISKANDAR', '198306282010011012', 15, 345, 9, 80, '$2a$10$AwM1b45K0B0RwXjB4VQxWOX4W6aBjrcNgvKG8BtO1Hd.DNF0ruMGi', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(441, 'FITRI ISWATUNNISA', '197412202010012004', 15, 345, 9, 80, '$2b$10$M6poRkJ9Ml92LerFBQ2G9eEkjscTKmvJL5sWFk5wzGzzNrXFpkwlu', 'VERIFIKATOR', 'https://dev.pringsewukab.go.id/foto/1665621005792.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(442, 'SRI REJEKI', '196902021993032004', 15, 345, 9, 80, '$2a$10$bQ2jcFTj/UdAJXjP1gPA/uN/WDX0pC7OTq.JFU4iKoMrJ6pY2ydwG', 'VERIFIKATOR', 'https://dev.pringsewukab.go.id/foto/1665101904784.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(443, 'SUBAGIYO', '196804051998021001', 15, 346, 9, 81, '$2b$10$.BpD7myhTwrpV4zit/NqQ.eqKynNwmHbrQ09wYhd5k5Hnn3q5lzQu', 'VERIFIKATOR', 'https://dev.pringsewukab.go.id/foto/1721612035231.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(444, 'TAMRIN ADRIAN', '196606261989011004', 15, 347, 9, 448, '$2b$10$Jur/KWa713pB10SLLpWRBu/kRYla/uVSGjfcwfTdU.360HdRlLPTC', 'USER', 'https://dev.pringsewukab.go.id/foto/1706083347653.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(445, 'SAMSUL GUSTAF', '196504121998021001', 15, 348, 9, 450, '$2b$10$q/3/TZ5IMOqTY5AJ/18E3OeoDkXIfPK6ImAwUjFfSGsXXCSa0YZIu', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(446, 'RISKY RISTANTO', '199304242020121015', 15, 349, 9, 440, '$2b$10$U1qRd5oBHDec2p0VDmUbnODmRdz9CurM0u/dj8EmJ1BfkvzDmUFIa', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981');
INSERT INTO `pegawai` (`id`, `nama_pegawai`, `nip_pegawai`, `opd_id`, `jabatan_id`, `pangkat_id`, `atasan_id`, `password`, `level`, `url_foto_pegawai`, `tukin`, `edited_by`, `first_time`, `created_at`, `updated_at`, `no_whatsapp`) VALUES
(447, 'DEHRRY KHARISMA', '199408072020121013', 15, 349, 9, 441, '$2b$10$kBuoMTawE2mY8lO2Ixh4i.GrxrETXHkVRoQ6sBBe2qzrTv1NiAgyu', 'USER', 'https://dev.pringsewukab.go.id/foto/1753693285244.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(448, 'SUSANTI FEBRIARINI', '197902052002122005', 15, 350, 9, 79, '$2b$10$KbcL8CyQh5PsE9XlaD4rqOFw3K6dTeeYzShDJCHzrkZPLyWOfNvSO', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(449, 'TEZAR ADMIJAYA', '198408272010011011', 15, 350, 9, 79, '$2b$10$/wBTrI5o/aXZ3fczkQCBJ.6NAN76YC9nhIlXwkTAFLqYs.GRGEskK', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(450, 'MARYUNI', '196905071991032007', 15, 350, 9, 79, '$2b$10$NqTS95JpUHOxOZlt5wERyuUxkC12flMXegPSPPwAEz.5o8btZDYFy', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(451, 'RAPINA PITA ULI NAINGGOLAN', '196910061991032006', 15, 351, 9, 81, '$2b$10$VjVZzTbHtGV.Sb0PiddPMenNiAAlpVsKdOb8v0r.8TQvezTCUJ5e6', 'VERIFIKATOR', 'https://dev.pringsewukab.go.id/foto/1664757003787.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(452, 'AGUS HERNADI', '198112252009021007', 29, 352, 9, 142, '$2b$10$Nsz7Eyx.is4McjPBm/1bj.hCCAV79yn7r791.vm7jAeGBZK.jgL/q', 'PERMITOR', 'https://dev.pringsewukab.go.id/foto/1664422763371.png', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(453, 'APRITA MAYA SARI', '198402032010012022', 29, 353, 9, 452, '$2a$10$pMSEXMPnbG73B6F5TooN8.1luhbPCxrRekKeSvZQlerNLuxcgeR/K', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(454, 'MILKY ABRAR', '199304052020121020', 29, 534, 9, 461, '$2b$10$0CRomK2QXOxGgXtMzbxI3.M6rBiT7BdGyj0pYIy2vgta4hE0auVDC', 'USER', 'https://dev.pringsewukab.go.id/foto/1664956808617.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(455, 'TABRANI AHMAD FUDHOLI', '196910301994031004', 29, 355, 9, 0, '$2b$10$BdH9Oj9zqWpSmsZ.6IHdPurqlowdSFteVSj3sgjYGJ3SzmI1x0SZC', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(456, 'SRIWINARSIH', '196802261991032003', 29, 355, 9, 452, '$2a$10$z8aBuc.zyuPVyLMx6DQW5eqi6whsZd6bg3clGXsP1y3sMOx6eSJzK', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(457, 'KUSMARYATI', '196707071991032008', 29, 355, 9, 453, '$2a$10$8/nMDm6c43lDk1aowIj.BeoDVgic6cWyrusrYnx3KeuDBWl68Nupm', 'USER', 'https://dev.pringsewukab.go.id/foto/1665377855540.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(458, 'NURHIDAYAT SURALAGA', '198312122010011026', 29, 356, 9, 145, '$2a$10$vbo51gXhERv/GzVpaLd8xuZAU/b.8f23seM7qt1lB7W1JmA1m8eNa', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(459, 'EDI KUSWORO', '197907212009021003', 29, 356, 9, 145, '$2a$10$ifm3zQUZVaHEzJLU.1SbdOS.Jd0fHNInUzGQOfi3UFt8Y9PeEdiU2', 'VERIFIKATOR', 'https://dev.pringsewukab.go.id/foto/1665016619198.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(460, 'KUKUH PANDU HERLAMBANG', '198711152019021005', 29, 357, 9, 459, '$2a$10$ihCyVOgtEfSz/Wtfbr8tXuGdfpGwHQ1JcaxMM6aeSRJ3A8IAUjbP.', 'USER', 'https://dev.pringsewukab.go.id/foto/1669077171085.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(461, 'TEGUH WIJANARKO', '197104042007011008', 29, 356, 9, 452, '$2a$10$hdPbmgXEsYEbFGVqub7tQOYD290bb05NMIbcdIOuEHgbDXqpOiWMK', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(462, 'ABDUL HARIS', '196604211987111001', 29, 359, 9, 144, '$2b$10$e6qorJJIMqR8MTp3ozsj1eAA0PRMl68Ls9P187IV/zVMn2pwGyliG', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(463, 'FIKA LESTA', '198410082010011012', 29, 359, 9, 144, '$2b$10$/kTvT/vybJ8y/1MwAUU8WuhlHZ5U.a0Af3/35jnR4n.EqOnAL2cuK', 'USER', 'https://dev.pringsewukab.go.id/foto/1665134069074.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(464, 'AGUNG ADITAMA', '197707222010011012', 29, 359, 9, 143, '$2b$10$.NETsfZhqVnimVOvo8AjnOOxCFRC7IZD3tyUrK7QN7eE5z2LTRj7G', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(465, 'AGUS PURNOMO', '197611222011011001', 29, 359, 9, 143, '$2b$10$Mzk4Xj4Jht.LOas.T3g8EetJeyhyDh/JVJB5mhLjs43q2ExoR331.', 'VERIFIKATOR', 'https://dev.pringsewukab.go.id/foto/1665035094805.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(466, 'ANGGA BAGUS SAPUTRA', '199609252022031003', 29, 360, 9, 465, '$2b$10$ALub7fd3irwkk6SkiZyb0uVpTlb3zPYddZwDGiHfID99pITJR0cia', 'USER', 'https://dev.pringsewukab.go.id/foto/1668660145147.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(467, 'DHARMA PANDU WIJAYA', '198508142010011012', 2, 361, 9, 0, '$2a$10$qYR5WaOuGggh4WpmyGbUCumglaACIfnfYTMoENLrfL4wgVP7gaNn6', 'USER', 'https://dev.pringsewukab.go.id/foto/1664760134043.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(468, 'ANITA SARI,SH', '198709292015032004', 2, 362, 9, 0, '$2b$10$cm.EMggbG4Wpvaem/u0qvOvqk6.WfUMxER4Kw2jnmMBOQxBzeZSZW', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(469, 'YUYUN AREI MARGHIHANA', '198001182015032002', 2, 363, 9, 0, '$2a$10$ijJ2XRr5B26balzoa.EeWeGhfUI/1oD.d6iM5sjbQUDQ8nbiyQFbO', 'USER', 'https://dev.pringsewukab.go.id/foto/1694065194013.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(470, 'MUHAMMAD GINTING WARDAYA', '199406022018081001', 2, 373, 9, 0, '$2b$10$uZfJJJ3q7cO3y45ZM34l8uw5GDD3hk45y0Cx0jpYz1hzcJtpSCEz6', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(471, 'FITO ADJI SATRIA ADMAJA', '199505212020121014', 2, 365, 9, 0, '$2a$10$0Rko9gIHPSB.WFN4XgqPDOd/Ap0HIrZ2kr3ygVewrlZyBbOHgkjzK', 'USER', 'https://dev.pringsewukab.go.id/foto/1667917214937.png', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(472, 'EHROM', '196505201988031007', 2, 366, 9, 0, '$2b$10$OUUMBa35ZE6sPbcGwE6Py.QARJQSc9TvHKB0Olutl16/gmZNw.gIG', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(473, 'BAMBANG NUGROHO', '197209082000031004', 2, 367, 9, 0, '$2b$10$J6yw32U90ucf0vJKPDYzu.5y2fjpJoAHKqX9yS.ch5FFicAQSw.Ji', 'USER', 'https://dev.pringsewukab.go.id/foto/1664759348450.png', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(474, 'BROTO NUGROHO', '198007012010011022', 2, 368, 9, 0, '$2b$10$ROdBm3dBbqILF4LR33Yr/uPwgXyojJi71hMELsHdXUp5/hbuAjnXy', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(475, 'MAYDA SARI', '198305102010012026', 2, 369, 9, 0, '$2a$10$vtNgNEuVl7bXLUCc9A0K5.3wltQVjm5nrKQBV2nHG2cIisb5oyuCi', 'USER', 'https://dev.pringsewukab.go.id/foto/1670569529280.jpeg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(476, 'IRWAN SAPUTRO', '198308132006041005', 2, 369, 9, 0, '$2b$10$T9D87YqJOPvbqfPJHySEceXhim5XQFjCjwp53ePTabaaGjb9RCcfa', 'USER', 'https://dev.pringsewukab.go.id/foto/1665117895559.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(477, 'FINA TRIANA MARBUN', '198502032010012016', 2, 369, 9, 0, '$2a$10$AslZuTgoAvYx.WQyI1YPA.gfRldTFBRdYnNYXbw40q95Cdc19Y.Vi', 'USER', 'https://dev.pringsewukab.go.id/foto/1665470678840.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(478, 'NOVAN DWI WIBOWO', '198211252010011010', 2, 369, 9, 0, '$2a$10$mqRzgjyOYZY0WRXxWPUsb.wOEgA3t7jGmqSip1ecsqBM5Y67NYegW', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(479, 'RENDY ANDHIKA', '198608122010011007', 2, 369, 9, 0, '$2b$10$YXgPtNnEMlAa37iVJXMNTOSZfZ0rkWYSHdc6YRsgnZuC7HiYV74pq', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(480, 'DEDI ANWAR SIPAYUNG', '198701072011011009', 2, 369, 9, 0, '$2b$10$qHOyaGD1Kg9w/M8chckv0epKzJeibzltm/B2ajOjNC8O1F1qrODn.', 'USER', 'https://dev.pringsewukab.go.id/foto/1664940953244.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(481, 'IBRAHIM ABDUL MAJID', '198707072011011004', 2, 369, 9, 0, '$2a$10$P6JYxuK/mA2kCQLLJ0ANu.sAJMU6KU8wSJDIhgnVCzZGsJ9vx.kti', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(482, 'ALIM AMAR MA`RUF', '198504192019021005', 2, 370, 9, 0, '$2b$10$TYdUoBmWessqLWVQ7XLgnOz6rFCY8yw99X6bgma3SvN7FmGORElPe', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(483, 'TOHIR', '198905252019021002', 2, 370, 9, 0, '$2b$10$cdmVRUTo2sTwRDdh1lQo2uaeEi14lu08Grq8taiN4VIdjrhHLFVfS', 'USER', 'https://dev.pringsewukab.go.id/foto/1664760727679.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(484, 'THERESIA DIANA NOVIANTI', '197511172010012002', 2, 371, 9, 0, '$2b$10$63DFz38LPpfN2cfLp7d5du7JNQM64dM8XJpGS80PTGq4ndRBignwe', 'USER', 'https://dev.pringsewukab.go.id/foto/1664424993362.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(485, 'SRI PENI HANDAYANI', '197107052014072003', 2, 372, 9, 0, '$2b$10$7HsM6CNI6jWm/lfSPUBPk.QjVzeuW0I/3CUqEP.4MX.Id0F8TCzLK', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(486, 'KOKO JATMIKO', '197701282000031001', 2, 372, 9, 0, '$2a$10$KbxjVmZ7wC4PE7Ux3tapKefQd9HOxRsmsPBxORnjWEzrUltMqrQly', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(487, 'ERVAN SURYADI', '197911222002121007', 2, 372, 9, 0, '$2b$10$9XLOIrN71pMHYlSPAghDDORia2aoO6wCZajyPTGZvuukWTPfOUL5i', 'USER', 'https://dev.pringsewukab.go.id/foto/1665640261020.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(488, 'SUPARMAN S', '197501212007011003', 37, 372, 9, 163, '$2b$10$BVNw/jk9BF2xN2Aju14V9e2q6fYN94iVTxIv1aVXB0WRLZkDKv12q', 'USER', 'https://dev.pringsewukab.go.id/foto/1665994954361.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(489, 'JOKO SUTOPO', '197107051992031008', 2, 372, 9, 0, '$2b$10$9iwvaeQFsC5cBjx1SaXDE.ML6SLUioPdwqW5ydCQgpCHRnYm7AD5m', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(490, 'ARIS SETIAWAN', '197708252010011019', 2, 372, 9, 0, '$2b$10$OJoUce5cDWQlCGc0XdK9NutOSv9WAcok1nmJIZvPsYzAyKMgrxi2a', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(491, 'MUHTADIN', '197604032007011003', 2, 372, 9, 0, '$2b$10$OEiiyPZJTCDDq3uzonRHk.5kWnmd1WogaGFCasXqjU1x3VyjuiXP.', 'USER', 'https://dev.pringsewukab.go.id/foto/1664759767962.png', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(492, 'ALI SYAHPUTRA', '199312252017081002', 2, 373, 9, 0, '$2a$10$0SLlX4DbqFl5.NDJ/b3ZsuBVKcRQYR4W8j8H9PTv7WDfDLO7Hobw6', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(493, 'M. SALEH', '197804092007011006', 2, 374, 9, 0, '$2b$10$l8K5Q5OaoZNJ7TM.vknSIunO2gswhv3R2ASHpEMBNonnZ5ljDviAm', 'USER', 'https://dev.pringsewukab.go.id/foto/1665132439478.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(494, 'RIZKY IRSYAD FAUZAN', '199612182018081002', 2, 375, 9, 0, '$2a$10$eNDWJCnr6SBvS302ir6Q..b80DERgQjuN56F5tbGR6YRmWUYhg2jK', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(495, 'MUHAMMAD SAMSIR', '198610172009021001', 2, 376, 9, 0, '$2b$10$sdOsGjq5P74PRP/fdqn/3OdBaKAhBYDt6gbyjNDXMQozfY7Ah01qy', 'USER', 'https://dev.pringsewukab.go.id/foto/1664874605964.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(496, 'RISKA DWI KARTAPA', '198706142019022003', 2, 377, 9, 0, '$2b$10$QyJTLnaBY702a7GcTk6Yu.DEWZ23lfOSg5gPHkoNsAqXE/ZBAxxHe', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(497, 'KHANIFUDDIN', '196508311993021001', 2, 378, 9, 0, 'a', 'USER', NULL, 0.000000, 0, 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(498, 'ANDRIKA FAROSA', '199004152019021006', 2, 379, 9, 0, '$2b$10$5mhQJ5ca.bf5KnciTrLoq.Gk8Iyxe.9lc53zL8udszBQJl70R/k3e', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(499, 'RATIH ELIYANTI', '198812232019022002', 2, 380, 9, 0, '$2b$10$a5lmW6Lk/DR5IWfeAzZ/qOa3cj6gbEEx9qt5owi/dQ8bT8sEfO3yS', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(500, 'YUHANNA AFTIKA', '199106022019022008', 2, 381, 9, 0, '$2a$10$Mf.VTXKEJsBduXWmFWnBveoDAMqrLzOzL76kf4qrHPy8BYTKB8hpm', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(501, 'SRI MULYAWATI, SE', '198603272015032003', 2, 382, 9, 0, '$2a$10$ljxny84kAmY8FrtOXCEUg.3YEcL8D4wGAKaZchgEt1Q0rrX5.zUGK', 'USER', 'https://dev.pringsewukab.go.id/foto/1666170933679.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(502, 'EDY SUMARKO', '197104111992031002', 2, 383, 9, 0, '$2a$10$M32TXepSOFcTA7ASLnsKs.I14EGmYakSGev0bAZat0r7e7zUc/4mu', 'PERMITOR', 'https://dev.pringsewukab.go.id/foto/1744793717680.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(503, 'MAULIYAH NURMALA SARI', '198108202010012014', 2, 383, 9, 0, '$2b$10$wKdJlyqgUCRhELVRASO5U.geiGD3q8KanxY70KCKA/uYq1vU491gC', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(504, 'YURLIZA', '197907172008012029', 2, 383, 9, 0, '$2a$10$pzUnzPG4qTlVI.CljKn6GOproWoCRgKFsJoTknxCJk9Ka.H0U9QRa', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(505, 'DINA ROSDIANA', '198101012009022012', 34, 252, 9, 0, '$2a$10$yX.3yClhjPJQJzhO7YOXB.W46C04LlGA0yAzvr0nfiE7J02sQ5aV.', 'USER', 'https://dev.pringsewukab.go.id/foto/1731370592575.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(506, 'SISKA HANDAYANI', '197803262010012004', 2, 383, 9, 0, '$2b$10$CbnIjHmzFJZLGf8iBlduTeli2EGQakkrAaCgJ82/9MR5Z8xggZOK.', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(507, 'IVAN FIRDAUS', '197110122003121003', 2, 383, 9, 0, '$2b$10$qr0vaiLhbwHca92gsz.w.ODUIXRpujim5fNQA8ENCrhkq4RcTDNpi', 'USER', 'https://dev.pringsewukab.go.id/foto/1752479511317.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(508, 'EKA YULIA FITRIANA', '197407071998032008', 2, 383, 9, 0, '$2a$10$gJbH/z1fO/zGKa3G3v3g8eDnJWLI24tU73GdSj3./9F56dFiaxC/S', 'USER', 'https://dev.pringsewukab.go.id/foto/1664426262649.png', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(509, 'ZAINAL ARIPIN', '197609142010011012', 2, 383, 9, 0, '$2b$10$yHKuBJs6sWCZlF5XTECA1us6WpopclvzG7uvTF1sBAe.BLsADizty', 'USER', 'https://dev.pringsewukab.go.id/foto/1704328391541.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(510, 'INDRA GOZALI', '198205032010011018', 2, 383, 9, 0, '$2a$10$w0C5K8XUfrLzzXwiHO3qnekBqwT2ga8LW9nh6T62Bvvdn6nFf2/By', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(511, 'IKE DWI KUSUMAWATI', '198209052010012026', 2, 383, 9, 0, '$2b$10$QZfPUXMNCXoykyrXMJw83eO4p9r9VyGsGPOo4rgpksy9OCc27htiG', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(512, 'PERALAPAL WAHID SATRIA, SE', '198611152015031003', 2, 383, 9, 0, '$2b$10$KVvhSxZ5CyTfRfrGJsmJvOtu6tWaIhbXZy1gJw9j3Vy12gsZ36nxq', 'USER', 'https://dev.pringsewukab.go.id/foto/1665364744068.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(513, 'DHANI PRAMAYOGI', '198209022011011001', 2, 383, 9, 0, '$2b$10$uwsN8c/RSpysrbc0qk0a2.MSnlSYNJ0osfiea3Wz.GiH/Rque3oH6', 'USER', 'https://dev.pringsewukab.go.id/foto/1665032107056.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(514, 'ADHIAN RESTYADI', '198305222011011004', 2, 383, 9, 0, '$2a$10$96v6pL0gskE2nRZahleoLe6oRW17uBdq7dU3iC6E64PDrnKR.zfjO', 'USER', 'https://dev.pringsewukab.go.id/foto/1664430873844.png', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(515, 'TRI PRAMESWARI', '198406032010012023', 2, 383, 9, 0, '$2b$10$miP7.vg3FUMwlLxI4TV5zusfvMCU0DH26cIz1KHeE/Lp3VevUlbIS', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(516, 'ADE SYAFUTRA', '197808012006041014', 2, 383, 9, 0, '$2b$10$mWqsyVhiKbUw181xytzNSefEow8cxRKxyDczGW1kbvcY6dmyyQ8RC', 'USER', 'https://dev.pringsewukab.go.id/foto/1724907783108.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(517, 'CIK ANI ROHIMI', '198011082010011012', 2, 383, 9, 0, '$2a$10$vtUFBTGcz8QMaskxpu/iKuoF0rKv4j3aH6hI8iiC6viSzFZIsJzdK', 'USER', 'https://dev.pringsewukab.go.id/foto/1664499412173.png', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(518, 'HERAM CANDRA SETIAWAN', '198211042011011007', 2, 383, 9, 0, '$2b$10$HrFPn3XYKHbIKZ8HsXrumORnX1bn8G0bwh2c2R94em2WDEAx.WMyC', 'USER', 'https://dev.pringsewukab.go.id/foto/1664757048628.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(519, 'YULI YANTI', '197806122011012005', 2, 383, 9, 0, '$2a$10$wvatU8LmKhZiMesgC6yDuemohwRcNQxnWhnwgHI6ofP3.foMdnsn2', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(520, 'ANHAR SALEH', '198512182010011003', 2, 383, 9, 0, '$2b$10$Xr2Fb91qcUvVmny7iGkW0.Hxg/tjIkMWvizIZXE6zIMfoW/XCSvaa', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(521, 'IKARINI WIDAYATI', '198101192010012010', 2, 383, 8, 0, '$2a$10$QxKZud0nyfb2Ai6u1COjjOy.E0.I7LeqrEzjdk.DkYH3JBlhl7rpu', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(522, 'RIFQI NURDIANSYAH', '198709172011011002', 44, 689, 9, 0, '$2b$10$0quPRggslddI1FPM68ZuquhNj0ZZniAUYdMYxoVAfR/iqd0WXJkAe', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(523, 'WATI HANDAYANI', '198411092019022007', 2, 384, 9, 0, '$2b$10$fWKRN02TrL4DZPQ0OEGat.qF3lR.G4SJpi6tbd6UFeUru5Kd9yekC', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(524, 'M. ANDREW FICKRY MAHARDIKHA TAMIN', '199106022019021004', 2, 384, 9, 0, '$2a$10$UWg/h0rOiGl00zfVxaiX4uUP5yxHRZNU4ooY8hY1GTi2BhE.uErde', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(525, 'RATIH MAISAROH', '197509062011012001', 2, 385, 9, 0, '$2a$10$SlDXvHcgdenlNQkOROa5Vu.rQFBuL59Iv3dfpJKpSZU2ONzjSPLJe', 'USER', 'https://dev.pringsewukab.go.id/foto/1693448052504.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(526, 'AGUS TARIKA KURNIATI', '198608172015032006', 2, 386, 9, 0, '$2a$10$YI37SOV6ZZ/6c8mY5M7vReV/ufWDsaVW7O82Y563rVpN4AqC7oXlS', 'USER', 'https://dev.pringsewukab.go.id/foto/1664760802642.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(527, 'ABDUL AZIS JAELANI', '198810132015031005', 2, 386, 9, 0, '$2b$10$g98ZAnk9f0oNmroIh4ssY.Pdeb29j961Qd3eF2rqSF8Sz43OGYX26', 'USER', 'https://dev.pringsewukab.go.id/foto/1710830588509.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(528, 'DEWANTO DWI UTOMO', '196512181995031001', 2, 387, 9, 0, '$2b$10$0BPJ.LGPKfYyPP7J32RSc.bgnkFHqyVTZ1ns5BtlS2n3KWYM3ozgO', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(529, 'MISKAM', '196409241987091001', 21, 388, 9, 0, 'a', 'USER', NULL, 0.000000, 0, 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(530, 'EKO TURYONO', '197102111999021001', 21, 389, 9, 109, '$2b$10$9oAloHVmA76KeZNxmIlojeiEfckibwXS2DSrt2Q1Lb.hhOb4wXL72', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(531, 'NUR KHASANAH', '196408231987092001', 21, 390, 9, 0, 'a', 'USER', NULL, 0.000000, 0, 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(532, 'NUR IRFAN', '198601052010011005', 21, 391, 9, 109, '$2b$10$MTRzchGEUn0xxfqfRZBpx.CHnzLpwbEZUK5cJQ1xgQcl3cLziYODm', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(533, 'MERY FITRIA', '197605032010012010', 21, 392, 9, 109, '$2b$10$/IrGZ.2HYJHreh431ZEaHu0a9wpd90j0/ASQzCbENjhe8D6azJmWm', 'VERIFIKATOR', 'https://dev.pringsewukab.go.id/foto/1733272775763.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(534, 'DWI SUSILOWATI', '198004082007012003', 21, 393, 9, 108, '$2a$10$2bL64Nete4qGUezDwU6sEORB09Z2AWmTlwduEfkx6FSSEp0x0MMXK', 'PERMITOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(535, 'DEWI KURNIA', '197504052010012008', 21, 394, 9, 530, '$2b$10$/sxfYKEULQgmA6T4AR2.e.N9MdpEtQ0liGxNJkrbr7h7snhtKSmKK', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(536, 'RATNANINGSIH', '196604071989032003', 21, 395, 9, 530, '$2a$10$FoOMTSzudZ6uVmp5JPSNDObaCJM.DRVJROHLGQDuLtNUQoOuZgcl6', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(537, 'FURI YUSMIATI', '198502222010012013', 21, 395, 9, 109, '$2b$10$ntPmhcZg5jHY5axHRVLUNebE.jo8ylKZ48ZOUKrrf3SzX/yk4/9lK', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(538, 'DYAH RIANITA SUSANTI', '198512122010012036', 21, 395, 9, 533, '$2b$10$trDZCWxXNUoTPLMPzexM..xIWWchDKsaa6wRrJgW/0WEywxDlo84O', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(539, 'ZARNIATI', '197707152010012018', 21, 395, 9, 532, '$2b$10$dco.TGFLsA6pOiMezoW9Kexmh3T.Y6gcQFctol9oUAtSAe6Rd6FDy', 'VERIFIKATOR', 'https://dev.pringsewukab.go.id/foto/1664928887494.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(540, 'YOPPY KURNIAWAN', '198612102010011008', 21, 396, 9, 108, '$2b$10$FU9D2ZvYX4wORgeUP0MVd.m3PZ/YlNtzsZJKo2Qtt/xDShbbkYuKm', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(541, 'DIANA RATNASARI', '198410162017062001', 21, 397, 9, 537, '$2b$10$ek25h20vCv8.cTxxYGgNUOQqGktPn8B3AHzsS9351GcENOWH0dLQ.', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(542, 'SUMARIANTO', '196511131991031004', 21, 398, 9, 537, '$2b$10$7MCJMqksmv9HdVNJm0Ujz.h7C5lwpUFTGVWOXwHlidPEdjBxFZwdi', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(543, 'HELMI APRIZAL', '198204222010011014', 21, 399, 9, 537, '$2b$10$ar82cqDR9Wq2z2X0wXcDL.ELIGZsHQpVCHM43tUZwrEkt67w.N4j6', 'USER', 'https://dev.pringsewukab.go.id/foto/1664437200896.png', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(544, 'SURYO WIDODO', '198610022011011004', 21, 400, 9, 537, '$2b$10$tx3EK39aQFdYtCLkndBbu.EWCgJWJdTYoFhgeyd9UThexacnyrI3K', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(545, 'ELY RAKHMAWATI', '198205212017062001', 21, 400, 9, 537, '$2b$10$TyeG9070MPIeyI/BU8sWqOzHyMFN5JRji//HYQgSfVwkZV2qbC.y.', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(546, 'WINDA GIOFANNY', '199110132015032003', 21, 400, 9, 109, '$2b$10$K0Wxx4npiEG6QkMIYA41muuIZE/cWb2JW85ffHERAAfQnEHwuYXBa', 'USER', 'https://dev.pringsewukab.go.id/foto/1665537388660.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(547, 'RAHMAWATI NURMALASARI', '198907092015032006', 21, 400, 9, 537, '$2b$10$wbdsqVcP8mqcJ2kTIhIog.MmW9uKlyUEiKiGBQuJ32pCQsgtWz6pC', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(548, 'RONALD WILMAN', '198312202017061001', 21, 400, 9, 537, '$2a$10$uoIni6u/ElW846aMy9uNLunnl8GN8yJFOeCWN8kBMaxlg6B.Z06kO', 'USER', 'https://dev.pringsewukab.go.id/foto/1726656537493.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(549, 'ITA YULIANTI', '198207272017062001', 21, 400, 9, 537, '$2b$10$wJuxMelkXTsi8l4piR17.uFMONCyUfQdO2tVhZVC1pKQYg0orjBz.', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(550, 'NANI LESTARI', '198402062017062001', 21, 400, 9, 537, '$2a$10$YruNOoeJh2V9xfJoTMdlZuG/sk60yEIguUJH5WVqUKGMQdE5YWCYy', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(551, 'DESI ARIYANI', '198112042017062001', 21, 400, 9, 537, '$2b$10$YW2MggDZ6JvhnnvnxeQ21OvevNsCCv6jvJ3.tSF7M3e.ChXNBJcKe', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(552, 'HENING YOGO ASTUTI', '198601092017062001', 21, 400, 9, 537, '$2b$10$4xOTzVxK06AjV9.Oj5aR1uzLQgQ796USjhUsRE6AiCoVLiLYFkTgO', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(553, 'WAHYU UTAMININGSIH', '198408252017062001', 21, 400, 9, 537, '$2a$10$2NonnwzgtqIYCkc41ojxt.7ljHLzBLyTx4zAbRkAfCcmTXwlnlTwi', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(554, 'EPI KURNIAWAN', '199003202015031007', 21, 400, 9, 537, '$2b$10$LdB4qTE0pleFLZKNUpJWo.7UKcEMKZXJ30B6ZcbtgM0RuxjarLLNq', 'USER', 'https://dev.pringsewukab.go.id/foto/1723858896646.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(555, 'SUNDARI EKAWANTI', '198110112010012011', 21, 401, 9, 537, '$2b$10$l6TWWUM5p.YTyJhDmZLBYOhDhsQhG4NjEyEQIZxeoTv9U12Fd6rOC', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(556, 'ENDARYATI', '196810051992032010', 21, 401, 9, 113, '$2b$10$qQyZ20Xw64SaT/C9So/u1.6qOah0ZQ0ik5WlXDCW3FdSIaG8yeqfW', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(557, 'SRI ENI ROMDINI', '196612151988012002', 21, 401, 9, 537, '$2b$10$y8xgMrFCuQxzgdTLCx0Y1OmiTFyCklByLyZHdy.Pe9vguhJgpWK2K', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(558, 'YUSI PUTRI', '198710022010012009', 21, 401, 9, 537, '$2a$10$DdlFpvzFZtOKlAqVLOr.tuQt.cYeXgXyCAWlhtsvSCMy7OZihsLWe', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(559, 'HERU KADARYONO', '198111272010011009', 21, 401, 9, 537, '$2a$10$rcSRdoFnwwe.LAfdSVkzReKlNmQ9KaVLDrWk.RarAQqn4caeVD/O.', 'USER', 'https://dev.pringsewukab.go.id/foto/1664497587502.png', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(560, 'RIO VALENTINO', '198108052010011014', 21, 401, 9, 537, '$2b$10$CDmja0lAEqvxp8Tm.JHJB.WTS5d4rEKDp9X.4nGCK4Z4blc9zSW9y', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(561, 'RHISKA WIDA DHARMA', '198608022010012020', 21, 401, 9, 537, '$2a$10$20wQEET7V21EprFquDoW9eE7/apjclK.wiEHbUAif1ZvG0RWXzUu6', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(563, 'FRANSISCA ANDA CHRISMA DEWI', '197806052002122008', 21, 401, 9, 113, '$2b$10$.MBphYU4vLamFyt8n3HoKOoxhkhEiMpzUKjTpjh.yMonfwLKqfFhK', 'USER', 'https://dev.pringsewukab.go.id/foto/1664986733860.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(564, 'A. MUKHSIN', '198307052010011024', 21, 401, 9, 537, '$2a$10$HhUdI9UZlw0KlMBA.hcI/OqEQK26B43R3sEb5DDQv0c/biJwJR8JG', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(565, 'GUGUN ADITIA PRATAMA', '198904262015031008', 21, 401, 9, 113, '$2a$10$ZyLCZ8YutU9CtwljQwiQO.ed7S9CzlAqybikUB6DFLpwhlactfK.2', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(566, 'AMBAR WIDIATMOKO', '197911122010011012', 21, 401, 9, 537, '$2b$10$OKal7kIh3UTiTQcWBDExZe6YOnuK7bxTuIp0XDeLGy4kT4ur7gMja', 'USER', 'https://dev.pringsewukab.go.id/foto/1664929331303.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(567, 'SAIFURROHMAN', '196608062000031004', 21, 401, 9, 537, '$2b$10$HeK6qTrR.5FqsDRIuRLlZuS.t/wqLHsuvFHUzB6fQITAgJBLN9BB6', 'USER', 'https://dev.pringsewukab.go.id/foto/1664873821322.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(568, 'MIJIYANTO', '196210311987101001', 21, 402, 9, 537, '$2b$10$xf5nlab2UNLjMBI4Y.zkIO.qJZc8mzAS/.KbDd6NOOkpfkG78uo7C', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(569, 'TUGIMIN', '196804201994031007', 21, 402, 9, 537, '$2b$10$jhE2HkyYorQ04jZGFO4L5unzUvquwTr68LbzbHWU3b1jgWzCIuHha', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(570, 'NUNING MUMBIARTI', '196310181993032002', 21, 402, 9, 537, '$2b$10$rCiUJ/ObAPHdY5OstKwc0.RugIkNLx4G9572r4yGCVlCfX.Z6sP4O', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(571, 'SAIMAN', '196312291988011001', 21, 402, 9, 109, '$2a$10$JmBjxeD0QA7W2Rv6rzcUSuRXVtiagj5dzqyPmQmdn3f7FPXNgt5HO', 'USER', 'https://dev.pringsewukab.go.id/foto/1664876248831.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(572, 'URIP', '196306061988031014', 21, 402, 9, 109, '$2b$10$GrsGdxz3I6D8DmzzRaRbvOzEpXEMOoarvWrqbBycpS5hh2MIaz8Xy', 'USER', 'https://dev.pringsewukab.go.id/foto/1664943353165.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(573, 'MOHAMAD YUSUF', '196403101998031004', 21, 402, 9, 109, '$2b$10$VQnzzlHfx5XuOZpYSTEVfut4sTVQ2ePm3RmhyacUjACbdIj6p5SJC', 'USER', 'https://dev.pringsewukab.go.id/foto/1694565053253.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(574, 'SUYATMAN', '196404211987101001', 21, 402, 9, 109, '$2b$10$Lwn.nhNkND//LGi/SGTnduZgy4V1Lv1NAhm4igti4gKoqS92TDxLO', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(575, 'ANGGA SATRIA NUGRAHA', '199306112020121010', 21, 403, 9, 583, '$2a$10$HoDUdKQeyDNU8xDniGdpuudY3iqeX4SOEWqhb0OP7sgMGIbV/K6Vq', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(576, 'ADI KURNIAWAN', '199309052022031008', 21, 403, 9, 583, '$2b$10$0svLy9/7QNxxuD75SRKokuUavbuhbEUN66.uwhBNcSFpTdYYdC12C', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(577, 'EVA GASELA LOVIAN MANIK', '199504292020122021', 21, 404, 9, 583, '$2b$10$xB3EApm6W2WWrfuSwjgziemVqw83Lozgyo.YJroCdUflcCEcvFQgK', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(578, 'RAHARJO', '197306062006041015', 21, 405, 9, 594, '$2b$10$LZowhTEy8HCXcdRiszaqmeWM0LfdsvokXPJx6ddQMzkpxmgkIdJyW', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(579, 'AFICO FERRY ARDIANTO', '199306192015031002', 21, 406, 9, 594, '$2a$10$lBg9thI2oRXJTgBYdQ24o.GMHEL9W1tJ.2s8AvJki.uDDBYDgUOGu', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(580, 'M YUNUS', '196506142002121002', 21, 407, 9, 110, '$2a$10$MpgsE882Ysl9ZGuG2YvEH.arOOXCHB.9FRob6R1wsXztf9GY4muta', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(581, 'OKTIANA EKASARI', '197710172007012006', 21, 407, 9, 111, '$2b$10$Nrh2GTnoIjCcbwYROnbTsepv76kqPF2ElvdqzaU4cPCKJOc1mwsg6', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(582, 'DIAN MEGASARI', '198504072010012019', 21, 407, 9, 110, '$2b$10$NR9dAFrQs.TfXvKNFAX/VOBoin0hKwU6Ja0SMFZUQtuHK.qI.aHz6', 'USER', 'https://dev.pringsewukab.go.id/foto/1664756177103.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(583, 'LUKY ADRIAN', '198709232010011003', 21, 407, 9, 111, '$2a$10$OUoT7ipdG8oS703t10/PQunC764jK9C0EifIG.hZgZUId8qCA1.yq', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(584, 'HARZON', '198305092010011012', 21, 408, 9, 112, '$2b$10$gjHw0okNpeRe08g4PVitauLyGM79jHPba3lqD79uH57ixtjJPpRp.', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(585, 'BINSAR SIMARMATA', '198405092010011011', 21, 408, 9, 112, '$2b$10$Aoq.ESLXdyuYCm6MNLxHaulUlIPmRY3KrjMLcts9N1Sulzm7YqBtO', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(586, 'IKA TRISNAWATI RAMDHANI', '198605212010012021', 21, 408, 9, 112, '$2a$10$Zcpl2VyVfJDWSQgq2ymTvechCuCI3Pl.2ol5HjtNGmzhC9OX/cR8W', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(587, 'SUGIONO', '196407152002121002', 21, 409, 9, 0, 'a', 'USER', NULL, 0.000000, 0, 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(588, 'MUHAMAD  AZWAR SANDI SALIM', '199004032015031003', 21, 410, 9, 594, '$2b$10$O8YpZnndXrSR4T9eeDepyOgy2DKqGNm8WnCWwET23PTkKjKo1bhHS', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(589, 'ENI FEBRIYANTI, AMD', '198902042015032004', 21, 410, 9, 538, '$2a$10$rZ1.PbGMO9OcWoRqSYO6H.jOCJwhfJpwGa9wG5SrOznMt3luXcUDW', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(590, 'LILIK SURYANI', '198502052015032003', 21, 410, 9, 538, '$2b$10$xaqoARqeeLsYZt5tQK0Ize.u.hYFG7OHfcjOgd21jWBiz2w3qZkBm', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(591, 'EVA MEYDINA RAKHMAH', '199005112019022003', 21, 411, 9, 594, '$2a$10$1VPALAaZ9pKpDzJgALk7MuTi7Yuxdk83ieFjzOfJG3XFJLpu5nYFa', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(592, 'YULISTA AMBAR WIDYASARI', '198607162019022007', 21, 411, 9, 594, '$2b$10$9o58cUxz80y9MkKtsrlLs./EGPdMuqewUDp1QQKz0NhKoO6jTks8a', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(593, 'SUSAN FASELLA', '199201272019022005', 21, 411, 9, 594, '$2a$10$RrKTQGvjf1l30VZKY5UkQeq6AsKrDhw.LmAGLiHCfvst2rGLi/xxS', 'USER', 'https://dev.pringsewukab.go.id/foto/1706238384554.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(594, 'PENTI SUSANTI', '198212072010012015', 21, 412, 9, 109, '$2a$10$H4skV24gf.deBeCTuo8ZCenvNfASe/cEYT5XLQW5T8bsOXniprILy', 'VERIFIKATOR', 'https://dev.pringsewukab.go.id/foto/1671592525427.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(595, 'SUHATIAH', '197612212006042001', 21, 412, 9, 538, '$2b$10$msV2BD0/YpaH3TC8ylwaEOumr5aXQSI0GfSZyFIxRALHXGmH9eiym', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(596, 'DWI NURKHALIM', '198405012010012016', 21, 110, 9, 538, '$2a$10$kCtEnJMkYoeM48j2gYPFc.YuN9ycmaN1NcEyhJbar3/MD4fvIW1aC', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(597, 'UMI MULYANTI', '198101232015032001', 21, 412, 9, 538, '$2b$10$XpApgXmnszGcDRuvvWoY2Oj2.OxOYP/iG2iFWurhHqX0TuiOC0tt.', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(598, 'JOHAN ARFIANTO', '198310282010011026', 21, 412, 9, 538, '$2a$10$J3XMo/ZfAC865szuiduycOEZ3rK8sxMX6151TQIm2PJPCP2FCTDja', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(599, 'SUNIYEM', '196907152014072001', 21, 413, 9, 584, '$2b$10$ZyvAeQU808AWT2ufVoyuVeJ/YqBj2kf5cQLvmku2W6r.n.uB/ucOG', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(600, 'NURNANINGSIH', '196708102014072002', 21, 414, 9, 539, '$2b$10$0jwKrY03bK9Mo1YrBH2P/uJZ2prMVugw5Pd/OOIP5nZQxlMvkW2lG', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(601, 'SAHARUDIN', '197311092007011012', 21, 415, 9, 540, '$2b$10$taDHlO4GWICaHX4NbmtTH.P3SwesODc5aSPymQlIwPQBfTLJNNvj2', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(602, 'STELLA AYU ANGGRAENI', '199508092020122022', 21, 416, 9, 581, '$2b$10$a6mH0vou2PawZr/lBRVr5uhLzV5l07sFxr.YpJLeBFEw8/ieV2fzi', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(603, 'MURTOPO', '198507222010011016', 21, 417, 9, 604, '$2a$10$PoE8zYHnZ2oYqo4OwRg5Iu9wz1BwVl8L/phnZdDJptdTCiaKRvX1m', 'USER', 'https://dev.pringsewukab.go.id/foto/1676528126310.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(604, 'MARIYANA ULFAH', '198412092009022005', 21, 418, 9, 111, '$2b$10$cNXJt3C4YoBqMZSOvWMEguNJo4AR19O8yt0dlml0VklgdM4nPEPsa', 'VERIFIKATOR', 'https://dev.pringsewukab.go.id/foto/1665016386544.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(605, 'ANDREO MARZA', '198201252010011013', 21, 418, 9, 110, '$2b$10$43hre9Xfr6xCvIx/mlCVvu2uqrqTl5kThqZa0WzKNgAHhxX8MlomO', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(606, 'YULLI MULIA', '198407222011012005', 21, 419, 9, 108, '$2a$10$RFbVHV9jrAfRNqtxgbrJzOXMkelaKLXiaNwpv7QFEvAWTOedyKvBW', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(607, 'ZULKARNAEN', '197605102010011020', 21, 419, 9, 109, '$2b$10$nlyzGqdUREHScQ7D5vYbAub6uENOR2Iz8O/Zeh/71a/MQtG7gUdOu', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(608, 'WENSI HENDRIYANI', '198205092011012005', 21, 420, 9, 109, '$2a$10$hAWEaHBH0a.CN5Uo5C1R8OjihCwShgugmoPNDG0YtUemUser2w9o2', 'USER', 'https://dev.pringsewukab.go.id/foto/1714090834086.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(609, 'HENGKI FERDIAWAN', '199602262022031007', 21, 421, 9, 586, '$2a$10$KfDGO23xrl547StyF9Dunu.74OfroffTyiP.bPw94o2eboeKTWwMy', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(610, 'BAMBANG WISNU LAKSONO', '199212172020121011', 21, 422, 9, 535, '$2a$10$GB6A0L00USfCjI6KdoHGkuY9v899C0t.LT/ZjGWnMfm19DKllv9Zu', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(611, 'BAGUS SETIAWAN', '198908172020121015', 21, 422, 9, 535, '$2b$10$cV8gey.Bfq5C5DQkGHS/U.Wp4Q8bcCKGdN03FOcIZdpfC6iSsUAV6', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(612, 'TURMIJAH', '198107052007012009', 4, 423, 9, 20, '$2a$10$UMmxmoovMYUYL0MKmYLChOw1sO/1MqXLyQjaGv0ukiag04E.20ATG', 'PERMITOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(613, 'FEDRY GUMILANG', '198705212011011003', 4, 424, 9, 20, '$2b$10$rrvehbn5uHhtQCgVycv7DOTldNxjQqF8gxxt7CtpAA765ByDkuB52', 'VERIFIKATOR', 'https://dev.pringsewukab.go.id/foto/1731661275656.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(614, 'YULI SETIYANI', '198107062003122002', 4, 425, 9, 612, '$2b$10$TURfUBlc2wmPYzp0HvtfYuVtejMtvqFC2ruUupiZEI8xRuT0F9EpO', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(615, 'FERIZAL AZHARI', '198901242019021005', 4, 426, 9, 612, '$2a$10$.lM44NrjnJu7w5Y5g9136.xK5VDSHxheXSUZGAKla1Ql85Dkle65y', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(616, 'RIDWAN NAWAWI', '198911092019021002', 4, 426, 9, 612, '$2b$10$tpCkw6Fb.SbDD71goyHX4eGvQ/fw6RLMCdjMQ4LiIsXkph7eVbti6', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(617, 'ANNISA  DESMASARI', '199112272015032008', 4, 427, 9, 613, '$2b$10$aDrvlnT1IkZTjmuQP6VnHuRhJn4oktDIGoEDSWL9XozYCXBh4qJh2', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(618, 'DHANDI CATUR PUTRANTO', '198508212015031001', 4, 427, 9, 22, '$2a$10$d8Q4MDcdUig8uv2LKh.ZPOckh9rrMJyxpibpG./Q284GpUkkvfPY2', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(619, 'EMI SUSANTI', '198110282007012006', 4, 428, 9, 24, '$2b$10$44GM5QV0apjmehSX6s8S3.tmUQzaehvWPjfB2e5ve22jxlwrlP5Oy', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(620, 'FAHRIAWAN', '197502052009031001', 4, 428, 9, 23, '$2b$10$mBq.MhM3atPLfuWiKFEHh.uZRoN0xk3icmA3MfYO.FkS3kYWXSEdi', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(621, 'NORA ARMINDA', '198706172011012010', 4, 428, 9, 15, '$2b$10$wbJTktcS7JNANPiXCWXK8.yHdPQ2EUezved.xbbFhgMx0HvzQgonC', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(622, 'HERI PURNOMO', '198401182005011001', 4, 428, 9, 15, '$2a$10$69vw8YUIMv0SBG.ohkE9C.5HYViDTDctFlcr/9ZmUoVTQAgGIQ8A.', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(623, 'DEKI ARDIANTO', '197505052011011002', 4, 428, 9, 23, '$2b$10$gswxDihlZPT4b1cbGCyjlekqhvgARfbdY.5IHv9HCSmfpGjlOTrby', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(624, 'RISTIYATI', '197511102007012028', 4, 428, 9, 23, '$2b$10$8pLYdxtFE9isX6Raz/HkiOrM6P.iNl0HXesBDMvouifzcnqwnNAvm', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(625, 'SYARIFUDIN', '197311221994031002', 4, 428, 9, 22, '$2b$10$YPZM4sTB6XTahzDbi5lM7O/HkRHdht2X.WmrweAAcU5GyOxZox2Zy', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(626, 'MUHAMMAD AKMALUDIN', '198610012011011007', 4, 428, 9, 23, '$2a$10$qotX1vhBG47z3bh4h34m/ewBG1SPlDTg4gkfxtgKzBeXrUQKYq0my', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(627, 'YULIA', '198107142010012015', 4, 428, 9, 15, '$2b$10$dsWvmC3NyuaxKVPbvW.GJuHqW03ygcW6Lt9tBPKzGAR8mP3YJdXyK', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(628, 'AGUS SUSILAWATI', '198008012002122003', 4, 428, 9, 612, '$2b$10$c6QRR9uHADRTbx8w9Waqg.9lNU1.rg6k89/5cnCmYuZYQ7RCSlniK', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(629, 'EDWARD MAKMUR', '196211101990031011', 4, 429, 9, 24, '$2b$10$F1ePXEvpQk0UDbjJFUrtOuVbRRC9/3O0XgeMJtVSvCYO/CAGQROXu', 'USER', 'https://dev.pringsewukab.go.id/foto/1665140415211.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(631, 'SRI HASTARINI', '196612301989032004', 4, 429, 9, 22, '$2b$10$oSDatqkjFCiU.zoM9Uy4yuxGqfFwHkRCJrzFiO2Zi6KMiyeI/NzlW', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(632, 'YOSEVA ERYANI', '197705252003122005', 4, 429, 9, 24, '$2b$10$YMlnEHa0ZK2tlef76ZTJOe5Ki5XrcsAL1hdXq6NgztQQVKHdW0MX.', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(633, 'FARIDHA', '196602061994032005', 4, 429, 9, 22, '$2b$10$j5pV6JyOvo4eLNnicYuK9.1gYhjNLX.oQnxvDUwQ0WmpNWj26OrQu', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(634, 'SUSANTI SYARWAZI', '197407191998032002', 4, 429, 9, 24, '$2b$10$tgWEqLPt9OEW9cM8NPCX0OaKD3Wau0Viwh.4aA9xQzIVhGw/jX4hW', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(635, 'EKA ANGKASAWAN', '197904042006041008', 4, 429, 9, 24, '$2b$10$J/z/wYwcrUxsfdycs4EKauTboQoEtqK/HyXI7Nb.Yzw0VZUCJl6e2', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(636, 'DIDI SUSWANTO', '197412191997031003', 4, 429, 9, 23, '$2b$10$aD9pX0o8T3u.2lKwEPwQn.HRh/rkXDEetU8ysx.F/Yiv2v.lg6k2O', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(637, 'RAHMAT MARSUWAN', '198303102010011027', 4, 430, 9, 22, '$2b$10$cnsmC150T1NJGu797kofxuHGXLp3zEKKVl.V3p4phByNaVAmpjSLe', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(638, 'CIK RODYA SARI', '197801052006042009', 4, 431, 9, 22, '$2b$10$9O1NAOKLf0F/nzyInZVyuujjN2EneOY9QXZpGET6n3Jn0QmwgGYp.', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(639, 'NOVIYANTI AGUSTINA', '197208072006042009', 4, 431, 9, 613, '$2b$10$4FzURLxmbv0lSmYXkNbO..NakO3oAqSpIYfJodE9WMgX8jRxitMyu', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(640, 'GANI YULIANTO', '198107222007011008', 4, 432, 9, 613, '$2a$10$EO6f/k5NiW0JtyjnqVJSge4EXn2ATWsUsB2xCeGYPNcUAvggjHPXi', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(641, 'EKO SULISTIYO', '198202112010011026', 4, 433, 9, 24, '$2b$10$8AxdjbnKdYE1bLP1QYpPmukKeR5LRwejeSBR48y58VWsFACEg8fvC', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(642, 'AGUSTIA RINI SOFIANTI', '198508012010012026', 4, 434, 9, 24, '$2a$10$7gGUSDWfy8VwTnh8E6CWYedQJHS22lZnuArMkJWDqQ.R9A7NbjKCu', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(643, 'RAFAEL DITYA UGRASENA', '198101262011011004', 4, 434, 9, 15, '$2b$10$PbrApTiiSHLuh8bftbiiGemZpCdR6Fs1x0jH4z/oP1PwWgfWplgfO', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(644, 'ARI FEBRIZAL', '198502252010011020', 4, 434, 9, 23, '$2b$10$RVrdDkMCzDWfJfGOIOqujOx2sbHdf2xg4GdC0vVj7xBIhzKfrzGNy', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(645, 'RIA AFRIANI', '198304042010012031', 4, 434, 9, 23, '$2b$10$gxLHJcOYNqEMCF0uGaBWsOg/TZwB.8ZHVk8Mb8dORMxYMB4KJhSbW', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(646, 'MERISITIAMIN BR GINTING', '197505232010012006', 4, 434, 9, 24, '$2b$10$BlwuSN2Eu7JeFZe7JZ4Z/.P8bq3ZiMU0QAtx7zh0bGeLfyKSMtsgG', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(647, 'MAITA EFRIYANTI', '198305022005012001', 4, 434, 9, 23, '$2b$10$/ncZb7lSVpEL.zxDm/C8AuM.9uU/hYbo4dbHhRfAgQ8vTIB5y0vOy', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(648, 'WIRA PRATAMA LUBIS', '198312102010011014', 4, 434, 9, 22, '$2b$10$tLXVtAuome1za.l/CxPmcOKgew91B77wFinmwE3W8t7zVawt9brAO', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(649, 'DIDI KARTADI', '196910051992031005', 4, 435, 9, 24, '$2b$10$eNo9xvsxUeoGDWeZ3GyvceFTyIcU7z0/lONa/Kr/QDkZKQH.04tX6', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(650, 'FAUZI LENA', '197609232010012007', 4, 435, 9, 15, '$2b$10$.e/4/dnCkJ/KKoa/9YWdsO32Z3B2ddFcoTcAP7Jnjk5xJsdqu806.', 'USER', 'https://dev.pringsewukab.go.id/foto/1664944706205.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(651, 'AZWAR HASA', '196807051989031009', 4, 435, 9, 15, '$2a$10$whbTQrWeFSaAo1caHS7u6uryv.Cn4GiPqzxTlTOYThRC7/myCSvIC', 'USER', 'https://dev.pringsewukab.go.id/foto/1666860084630.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(652, 'VANIA AYU CAHYANI', '199603142020122024', 4, 436, 9, 23, '$2b$10$IDosGOSB.wC36S2E7tozselLZ3wGuw5j2nagi472UwlC0xt7XyS6C', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(653, 'MUHAMAD ARIFIN', '198708202020121010', 4, 436, 9, 24, '$2b$10$1hXB517ywJh3fZ2hk0uiB./MBXonV9hw8VUr.sN7cN55A/ekqwOHO', 'USER', 'https://dev.pringsewukab.go.id/foto/1667201886337.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(654, 'ANDREAN FAHREZA', '199203022020121015', 4, 436, 9, 22, '$2b$10$GvSFs.hKX3ydRhyaI11jVemwbSfYPapFeJhz65uc8RnQncuV2.uNW', 'USER', 'https://dev.pringsewukab.go.id/foto/1710912504160.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(655, 'RINNELDA PENTAWATI', '198411152010012026', 4, 437, 9, 612, '$2b$10$q80FKkBOPnsCe9hDmmYG9OSPYue.ekqABbtCuzX9XFKUnNn3A1OnK', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(656, 'LIA FARIHUL MUBIN', '199106152020122016', 4, 438, 9, 612, '$2a$10$O9A2Ogq6if62Gv0nmR7w5OEiROr2WSU/YZluEnglpjarKsTqpUt1.', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(657, 'ANGGI TIARA NOVIRA', '199711052022032007', 4, 439, 9, 15, '$2b$10$HRVQx4UJ2b5Vjx42hZ/QmuWj3tMe/tKbb7qEseL3p8H56Q.Mh1Dyu', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(658, 'ANA YULIANI', '199507282022032019', 4, 439, 9, 612, '$2a$10$wxAvpYpJTkEM05CE54G6Pe.F2UQTkoHSJsSGZvhvfPFa4hLlBGDGm', 'USER', 'https://dev.pringsewukab.go.id/foto/1733215275562.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(659, 'HANDAYANI', '198106162010012019', 28, 440, 9, 0, '$2a$10$56UuJ0Qew1yR2Wdibb4d6uDrN.H1a4cBobiMiM7CxBsCwYVh5BOyO', 'PERMITOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(660, 'DIMAS FITRA FAHAR INDRA VIRSAWAN', '198008182010011017', 28, 441, 9, 0, '$2b$10$dAicHFnhstohPOSS1ZPpzexdLX/I6EwKFTHYQvBwRRiCQpAb.Pr1.', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(661, 'MARKUS ADI WARDOYO', '198012092005011010', 28, 442, 9, 0, '$2a$10$pnEVgXnqlA1UA4QblkZry.CAbyrm/yIrgVnZcDZhsCxQMB8Q6gCNW', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981');
INSERT INTO `pegawai` (`id`, `nama_pegawai`, `nip_pegawai`, `opd_id`, `jabatan_id`, `pangkat_id`, `atasan_id`, `password`, `level`, `url_foto_pegawai`, `tukin`, `edited_by`, `first_time`, `created_at`, `updated_at`, `no_whatsapp`) VALUES
(662, 'MUHAMMAD NASRUN', '197612032010011005', 28, 443, 9, 0, '$2b$10$z8E1KwjkraAQMNVtO/vFsOu7rAzAZA.HVHQtmenK2ATIAiQ8PT6Ri', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(663, 'NURJANA PELLU', '198306062006042024', 28, 444, 9, 0, '$2a$10$Nnb2x2M6.50Pu2CneNzNxeykAce.B6qmrKxsmNB6.8wBbiYF8R1Ey', 'USER', 'https://dev.pringsewukab.go.id/foto/1664510200090.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(664, 'VICTORIA PRIMARISKI VIENJAYANTI', '198310292010012013', 28, 445, 9, 0, '$2b$10$pUGvAGQdrHgtPV7hXqg.F.ve9Rqfzgv6o9uPXmPoWqkXVqk3OvUFa', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(665, 'M AJIE SANGJAYA', '198607152011011008', 28, 446, 9, 0, '$2a$10$A30/DlI8JSnzmsrhpetrOOHGAG2KXKPB.xdH1kbNzvEHod44lruM6', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(666, 'DEDI SEPTIAWAN', '198309292010011014', 28, 446, 9, 0, '$2b$10$ySyFmaHXhZENiKFt6c0eHOMjJTp24W4RYqmRJoGd.R6W5l74O7GG.', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(667, 'AHMAD ZIYADI', '199507232017081001', 28, 446, 9, 0, '$2b$10$rF6fW1ZVH/KgpG5Bfd8IU.2dNMeoFpn2VuYSNaP0AqNo3ex.kDB62', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(668, 'ARIS WINARKO', '198009302010011009', 28, 446, 9, 0, '$2b$10$GUGO6190UzxDeGi5zGGwmuSc0IzJm4fhh3Wczd/Y.sA8OFMzuQ2Lu', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(669, 'MEI BETI', '197405102009022004', 28, 446, 9, 0, '$2b$10$yyf0lUMD9/kaAyhFnN7lfe6N/hlnV07t2Aht0T/sBwh7fxu9cTzey', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(670, 'SUHARDIYANTO', '197005041998031005', 28, 446, 9, 0, '$2a$10$m3toyabeZmX1ICOfXPpECuALBvzUBTHOVqbncOpyg/loR0KScwfJ6', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(671, 'ANDAYANI', '196601011986022003', 19, 447, 9, 100, '$2b$10$itbT.AmE3OpNTS/dGzAi5O2g.MSNc149c2Cl4jSXyplm/WITJmatm', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(672, 'SAWARIAH', '196705181993032003', 19, 448, 9, 100, '$2b$10$asAJ7pOrTBpT/1dyk3dzQOVIP/uKa44DbNPgxTwz2M1eQPKpi6Peq', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(673, 'SUNARWOTO', '196511101992031018', 40, 449, 9, 100, '$2b$10$l1xZC4XpBKxnINvQ4lwnH.7tX89zTh53vPoCP7jLvzIe3UMEIsFQC', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(674, 'FAJARUL ISNAN', '198102092005011007', 19, 450, 9, 100, '$2a$10$td.95bMJqQwcKKMpJ8JSnuokWegJhDYV.yGpB0ctl5Ybkr5ghW72u', 'PERMITOR', 'https://dev.pringsewukab.go.id/foto/1667263087589.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(675, 'SARMI', '196705211992032002', 19, 451, 9, 672, '$2b$10$../ky25FhBT3meKIJF18UOcEgoSiz8jpeF8HqkNTWDTk1fxlOXk5C', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(676, 'RATNANINGSIH', '197504052005012007', 40, 452, 9, 673, '$2b$10$9XRH6FGhNxlq27b6/D2XL.vuvvy4AqRqABvC.5otyQV9wdpQfoWGy', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(677, 'EMY PURWANA', '198202022014072005', 19, 451, 9, 671, '$2b$10$x.DJk9jtvHcgojle6DOzdut5CcEChOYWfTK5jFzmZRe6Gujn.Amfe', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(678, 'M. YANUAR MAULID', '198101172010011011', 19, 453, 9, 674, '$2a$10$4Sr9jsEMnZu23pauIaM1i.BSQhQfkRZFDqzQawb2mUFgfs8qSMFNa', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(679, 'NURUL AMRI ANDINI', '199204252019022008', 19, 454, 9, 683, '$2b$10$SR05CI1iWXF1lCzLw1uNje3QtlGG/VnD79fB9FbCC3s/HxEFKTIju', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(680, 'MAULANA IQBAL ABDUL AZIZ', '199605142019021001', 19, 454, 9, 687, '$2b$10$iCAnz8vhP6PAXCDX70XRUOFpaPQPP.lXyhT8Nys35Ykt3x.4svQGi', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(681, 'JANU FERDIANSYAH', '199001302020121008', 19, 455, 9, 684, '$2b$10$MVQE2ZdaSvNO7.Jf/p2ySu59gAphkCU7gd64Qr9TJLGFK3e65Qs/G', 'USER', 'https://dev.pringsewukab.go.id/foto/1664757435563.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(682, 'RIZEKI RAMADANU', '199203102020121010', 19, 456, 9, 686, '$2b$10$0ZOJQofGNwvV7PZfgVFZSu6eJhEZ.HfR2SAjSHTt9eEbuqMHMHAIC', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(683, 'ROLI EKA PUTRA', '197406181998031004', 19, 457, 9, 101, '$2a$10$E1IWFV4vi9TE6az6yTCNlOxQ2C/VdAq8qWvIt4ILKv.Lbhi5Rhd36', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(684, 'YENI SAFITRI', '197805142010012008', 19, 457, 9, 101, '$2b$10$3cUdZ8ql5XldF2kZBrz2BOCRRucEPCwizTt9M/p6JTfZV.utkQuDu', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(685, 'ADI NUGROHO', '198701132015031003', 19, 458, 9, 102, '$2b$10$d0BWmG2BnLmMpJqC2Oc89u2ywr2ir5o.pm/t6ZzQYQVvhLvHflrT6', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(686, 'SABARONO', '196704051991031008', 19, 458, 9, 102, '$2b$10$QI4DqZDkRiP8EZc7iraHAu4fcMAOomsTNxRR2kzTTUAtITT7sMfcG', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(687, 'TRI SEPTINA', '198509292015032004', 19, 458, 9, 102, '$2b$10$gprCx7DaS3NQ/wmRFMT1q.PV6mMGYZt5RyAzaP04rRKmuZ95oK12e', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(688, 'AMINAH', '196902261994022001', 19, 457, 9, 101, '$2b$10$HGTu6FrdusQC/.BVkIF/r.uN1BJ7zS5TelzUauO0DanYWB.9CbmR.', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(689, 'YULI WANTORO', '196907261992031003', 40, 459, 9, 0, '$2b$10$VybrG3kTa4sW77pdFINxzOprC.O3iNJjbGecAOQPM2uj7ybikS3FS', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(690, 'MIKE GUSTIANA', '199108112020122015', 19, 460, 9, 685, '$2b$10$KSNGhe8/mhtbPUWrNn9KVOWhRgh0symun2fdkNWVjHOd7C3wqdlEG', 'USER', 'https://dev.pringsewukab.go.id/foto/1664758268041.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(691, 'HASYIM', '198909052020121010', 19, 461, 9, 687, '$2b$10$aKtHJOQD4/uSa4i9OwEX1eEMJBym1TKGZQhNHKojibvBCeFfRqqPa', 'USER', 'https://dev.pringsewukab.go.id/foto/1666663095437.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(692, 'NOPITASARI', '197908192010012007', 22, 462, 9, 115, '$2a$10$g3Oyd72WY2vxC//NK7RH7ujQEcsS6oLSKXm0feLMcEARCoF.5LAQu', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(693, 'ANY WIJAYANTI', '197107151998032005', 22, 463, 9, 115, '$2b$10$Z2MBf32ZNll8XzD7oC6f2eu8c9W1s8P9hKkQv/JgUpmtOGieSHoF2', 'PERMITOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(694, 'YENI TRI SUSANTI', '198906242020122016', 22, 464, 9, 696, '$2b$10$6Kq/UUTIOq9J9zCUEy9p9.kpk9Mpe4rEyeAETC2aLp1VDmQDKtJ7q', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(695, 'ARDIANSYAH', '198811102015031005', 22, 465, 9, 117, '$2b$10$auZocRDTuTSqdnE2dIvBduSd1siYXdZ1jIlzq8QX9VtVIBSkrYG7C', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(696, 'MELITINA ERIKAWATI', '197409152007012010', 22, 466, 9, 116, '$2b$10$tn7t.A2tZR4ouAt9ccQrd.hSy3rgvwvtee2StNrH2q47Qd9r0Zymy', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(697, 'INDRA KURNIAWAN', '198006212010011016', 22, 466, 9, 116, '$2b$10$19Z8GtanoVsa7dq8dlY8WOE2JRVUEI9XXkIV66T/cjGnxEles10xq', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(698, 'NURJANAH', '197605202014072002', 41, 686, 9, 116, '$2a$10$PoH229b/lKeIzpA.tVnKqeqrB.qvijSNQ45bsXTUMARN22G1BwoFq', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(699, 'SUHARTI', '197105061992032005', 22, 466, 9, 117, '$2b$10$BqqFYrtwq6JfO.9IyQD3/eMDBICOuVtycw2SVcxcNvqndQQyUc3GS', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(700, 'MUTIARA SAFITRI', '198705202011012016', 22, 466, 9, 117, '$2a$10$A14nnKIVE.rNxkb7OoVwVOwWm1TM87InvdTbqlG1ghetKnmsi67Ia', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(701, 'LEGINO', '196809172007011027', 22, 466, 9, 117, '$2a$10$ri7NmKi6dMqD3lY8wv/EPOEWybf4JxnzviBanYYZyUhh81oOaB1Kq', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(702, 'OBED SUSATYA', '199005102015031004', 22, 467, 9, 117, '$2b$10$cjl5EikqyWgEpAe5amNrouvGWhL0l.bOKniL73JI0ACK8EuwP0zCS', 'USER', 'https://dev.pringsewukab.go.id/foto/1676873844959.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(703, 'MISNA HIDAYATI', '198601302019022004', 22, 468, 9, 693, '$2b$10$CNTFor556Q/Oslcb4pVWv.ESPvOFhGZ2FxKNSR6G.JIJYKEqyJFte', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(704, 'HENDI SUTARNO', '197406092009061001', 22, 469, 9, 697, '$2b$10$4dJDrVe6pOZ/x7SIvy5kH.4kTVG7NUiBQ7nLUxUcaPoIrO1UkiLuO', 'USER', 'https://dev.pringsewukab.go.id/foto/1668470487842.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(705, 'ADI PEBRIANSYAH', '198302112010011016', 22, 470, 9, 699, '$2a$10$maQuaG5v68H3/NlIXCYBYeFkxQv2nAow/bgverGrwF0wl6fcfpyuy', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(706, 'SANTI SUPRIATININGSIH', '197510202007012031', 13, 471, 9, 68, '$2b$10$8BcODVyBDBLe7ffOkKwlV.A6UU4oW0IDo84zRNY3zguxDDRGuIzie', 'PERMITOR', 'https://dev.pringsewukab.go.id/foto/1664421992741.png', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(707, 'MELA APRIANI', '198703122011012008', 209, 472, 9, 70, '$2b$10$PgZtwZVo9Io.OoFOnxXYPeDWAuIBjjlbZO8mtimW9ZHbN4Wx8gacC', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(708, 'ASRI DWIJAYANTI', '198508182011012008', 209, 473, 9, 70, '$2b$10$x28oBrRrB07zKANloaE9KeClKItGqS69JwJ4qb8SsVSt6uzdsxka6', 'PERMITOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(709, 'SRI SUKAMTI', '197409071994022002', 13, 474, 9, 706, '$2b$10$Y0Ird6dTfbFY6.ePOy/Q/uC4UC6.udh7n0nEsJUi/re4EhEJFuCOG', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(710, 'ARIZAL AZIZ', '197612142010011009', 13, 475, 9, 706, '$2a$10$jWKS00j3JdZqzzxUrtOYOeXBsX7EeV.rVYmbtCav.zrUbH1xD26BW', 'USER', 'https://dev.pringsewukab.go.id/foto/1713402031096.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(711, 'ANNISA ROYYANI', '199507042019022009', 13, 476, 9, 706, '$2a$10$FsBb1a7ynTB1GfuC1ThaQ.dfDtMXpJDhoccMjDyZH4oVUS.avgEi.', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(712, 'YANA KURNIA DEWI', '198809052019022006', 13, 476, 9, 718, '$2b$10$0khubeUpqiSoTUUDi6F2huKyFL18k.8jIwTtmwUIg4isLAux.pO9m', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(713, 'EDISON', '196602101991031006', 13, 477, 9, 718, '$2b$10$HtNoYHZC.a7NjO0/VsQDX.LXtGI1fLLK.ZEKfdNg0oAxN/pmJ7hsC', 'USER', 'https://dev.pringsewukab.go.id/foto/1664869428503.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(714, 'DHAYS UJANG HAYKAL', '196605071986091001', 13, 477, 9, 715, 'a', 'USER', NULL, 0.000000, 0, 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(715, 'TRI HANDAYANI', '197412052003122003', 13, 478, 9, 70, '$2a$10$V/EXgpT7a5q9pMgqgkci3.iYIV8n5aYcfFdoWhEwjoke1SOwDGxLC', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(716, 'NELLA HELDAYANI', '198509292008042001', 13, 478, 9, 70, '$2a$10$7TOiL1NxtcrsuQ0OUeIs6ukky7x2sVJfS4krKnq9f.JJsEm6rVQ7a', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(717, 'DEVI MARIATY GULTOM', '196507211993032004', 13, 478, 9, 70, '$2b$10$WB8LYYEyvZtrwz3yFcnoAOFEW3Djs7CRaWLLFkVBzXwK9NvW78VsW', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(718, 'DIAN FAJAR PUTRI ARIFIN', '198412142011012003', 13, 478, 9, 71, '$2b$10$zaPsUY5HfONA2g7oJKZcTONMaOkUaQOk4GoWUGOm2U7idhf.O8pFu', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(719, 'SUPARLIN', '196903251990032006', 13, 479, 9, 69, '$2b$10$nnIVWnP7WaNT9MIi0140CunfpVRbjKSK9Ixm2NVXuMjy/UVx/sxby', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(720, 'CARIDAH', '196604121987032005', 13, 479, 9, 69, '$2b$10$3vz2OFPdM5ExvlxqxSEx9eaTTrIrCQbvoxFSfA205TPYwTuXsH1yS', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(721, 'LIZA DESNIATI', '198612032010012015', 13, 479, 9, 69, '$2b$10$WAC3snUejzDGgP35C7B2gObE7CDNV7JsrK0e3FXIverD378EomIqG', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(722, 'YULIA HARDIANTI', '199107312015032002', 14, 480, 9, 73, '$2b$10$JXu8ekaBn/lzoWjqQHPIO.msR7ZeyZIG76jrLhx5yij5a88mQAijC', 'PERMITOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(723, 'ADI SUSANTO', '198501312015031004', 14, 481, 9, 73, '$2b$10$x0cfZaQPOpxLWLlrpiDpU.Dg5sfJSvAFEYEikNSWea99IIBhge5ty', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(724, 'EDWIN HUMAIDI SAID', '198301192011011003', 14, 482, 9, 722, '$2b$10$A7gIv3r/XCOWGMB5HI8C7O.0o904wMNTB28ZpmyzrJJImu6v3M9Sm', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(725, 'AGUS TRI ISNANTO', '198808222019021004', 14, 483, 9, 735, '$2b$10$3fKMBddBZoeTQLRzwEO6fOC9TpeBoEUV6cCh7gs5V4TUS8m.XeV/e', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(726, 'NUR MULATININGSIH', '199103202019022005', 14, 483, 9, 722, '$2a$10$ObZDdF8yx1hCdoi9xES2q.g9RJXnrui6pgVq943uLCkdYClKLXhVS', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(727, 'ANDI IRAWAN', '198101282008011006', 14, 484, 9, 723, '$2b$10$eCCC6aEDsc3EwpK4Z3wOz.Y2NHncJJah8VMfzEWfAgDqDg4KIXut.', 'USER', 'https://dev.pringsewukab.go.id/foto/1664752795170.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(728, 'LAILA PUTRI RIZALIA', '198805292011012008', 14, 485, 9, 723, '$2b$10$KNR6kRbB7AdU/bEofwiLp.KY6aoFfpfYwFRoHpfo3iGQFO9lksxGW', 'USER', 'https://dev.pringsewukab.go.id/foto/1665538659001.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(729, 'ROSFI TRI YANI', '198407182010012039', 14, 486, 9, 76, '$2b$10$Q/1Rsvg.2.dFnXhr.Y1xjuxW/pfIXFidCag.IOzld4HPmhqoZIfb6', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(730, 'KUSLIANA', '198101172009022004', 14, 486, 9, 75, '$2b$10$/wng46BjKCB1VJ6Z1pOni.HOM2lgcZNbw0CoOdswwAo/MGmfrakdu', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(731, 'TEJO TRI WIDODO', '198210292009021006', 14, 486, 9, 76, '$2b$10$UqTRpTBr4xUEMKrv6JHU4.e61chpiOPhc9iP88y7TfYkl2DxvWfh.', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(732, 'ALFIN MASRURI', '198310262010011015', 14, 486, 9, 76, '$2b$10$Dyfjm60sC88tetYTZI7squ4ETUqwJDkphEx2sGxXb53TXLKSubUUm', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(733, 'CHAIRUL STIAWAN', '198408282010011019', 14, 486, 9, 75, '$2b$10$GARAwjufWUdsL4sv3IxHhecOrK5XL6i1i4aNLVnZKGa.YAU3VAvQu', 'USER', 'https://dev.pringsewukab.go.id/foto/1664512318221.png', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(734, 'MARTIN BUDI', '198603252011011004', 14, 486, 9, 74, '$2b$10$oQvC9b63dudIKwAFk4ryMep4biA3UCRnBLtk/5kcRxecburxzD.4q', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(735, 'VIRGIYAN KUSUMA', '198308282005012007', 14, 486, 9, 74, '$2b$10$sqZ7cJGo3neO0vhvrtp8OO9cdXVM4Lzi8GbdkasyUHGaKkjphpRRe', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(736, 'ARINI HIDAYATI', '197704302010012005', 14, 486, 9, 75, '$2b$10$Lc1Mg56e7bhq.PTVaVq.euCC73ns1aJYZzwD9qqNt0W2ui4kZqt3q', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(737, 'SISWOYO', '197004111992031007', 3, 487, 9, 17, '$2b$10$RRiqE4wgnjeyT8eCPzYYT.OUowz2jvxumdCtEIidLhUC6DItV70Yy', 'PERMITOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(738, 'ASEP ROBINSON', '198902102015031002', 3, 488, 9, 743, '$2b$10$6UfqJQQu7m1MX0Oz9Jtib.An8vo8tlbkZLQlqJQ6s1QfL4ds2PJY2', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(739, 'OKTABRIAN ALAMSYAH', '198810052015031004', 3, 488, 9, 752, '$2a$10$kMODKj95An3PkbrxpXweoelYp31GRLEAF3XruuKhaezy2Ej7Obt9a', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(740, 'RAHMAT JULIANTA TARIGAN', '199207082015031006', 3, 489, 9, 756, '$2b$10$BMY1WyEznwBw/UD35A2KBOlUyfBiJDGiMQLkM/.iI3BYH47yjsX.y', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(741, 'HESTIKA DWI NINGRUM', '199412152019022007', 3, 489, 9, 743, '$2b$10$ywUbbm/n0vgHfGMKdvegGeehzguIAAhfq/o8SFL881tG.7OFdDeAi', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(742, 'MAHENDRA RAHADIANSYAH', '199011012015031010', 3, 489, 9, 743, '$2b$10$MueFuKTaJo701YGUg0NEK.QNlgjaunPNnGL8gqD2iH81XMNPppl0a', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(743, 'HANAZIR', '198105062010011015', 3, 490, 9, 18, '$2b$10$vCoXSgY2nsag4ykZBe2EX.7ZGhfbS1BUhURY7Xa8pAZEFoT8EyOQO', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(744, 'RIKA RAHMAYANA', '198708022011012008', 3, 491, 9, 752, '$2b$10$yTduH8ZxCrxYA97fia1WEe2sYh13MO9Hp3L4lV1wTNFApoe2LsU7e', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(745, 'RIZA YURIKA', '198807162022032003', 3, 492, 9, 755, '$2b$10$JpzqKSknxPtDG//XbikOp..tFu8qvwUOEFucc9//COQEL7YrEXciK', 'USER', 'https://dev.pringsewukab.go.id/foto/1675755860168.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(746, 'SASKIA ANISA', '199807122022032019', 3, 493, 9, 752, '$2b$10$c71JuypKtf1iU.yUP4FUveJkA4sW2ysiJHim9Ep3pg2W0dvxs05va', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(747, 'MARIA NURULITA', '197512162010012011', 3, 494, 9, 737, '$2b$10$j5/toVxxtYVYVBhgqdr7L.cdSLh9NZMscUAT5JrQ7Uy6PkUJWCYsO', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(748, 'SUMARJONO', '198103182010011005', 3, 495, 9, 737, '$2b$10$Xe4e3otFaaIzFgSn1cw6d.moLTUW8zAYSEFoKne5LXXou7whYuJvS', 'USER', 'https://dev.pringsewukab.go.id/foto/1755661080962.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(749, 'RAHMAT ZULIYANSYAH', '198607092015031001', 3, 496, 9, 755, '$2b$10$1ocscehoAH4uGMUpTrVsP.JtbYxSjlEgzu1an3w9AWnZCBr8xMqZe', 'USER', 'https://dev.pringsewukab.go.id/foto/1706079908453.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(750, 'SUHERMAN', '196905171995031001', 3, 497, 9, 18, '$2b$10$OlY1UxNzh3vaCh3c/gC63.nmiu232AJdqRtUjhJb78HRJ0Qjvu7LK', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(751, 'MUHAMMAD SYIDAD', '198205182010011015', 3, 497, 9, 19, '$2b$10$rAcWC14KmpBRLEbmBmm/sOEqvKKxoXOefKiPA70YEahNoln1fdKOi', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(752, 'SUNARTI', '198601292010012018', 3, 497, 9, 737, '$2a$10$Nle3QWko5ZZC//mNrmNNf..RQAQi214bUoCPxVVUo9pf1cvYedlG6', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(753, 'DIAN AFFANDI', '198407312011011005', 3, 497, 9, 737, '$2b$10$6ZKFjfU.l4pW6Rb5XqFEAul.bNnyWoQTj/woiW9igHQ3jFahXNEYC', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(754, 'SAKDIYAH', '196805212007012006', 3, 497, 9, 19, '$2b$10$Rj0AvGYvAOjaKAvX6tsptOeVfM7Bv./RFAq8fGXcJejVqll7o0LpK', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(755, 'FATHU ROHMAN RIZQI', '198512112015031005', 3, 498, 9, 18, '$2a$10$TLLHkR/iLPHedudd6W.yy.Pa9E//n2vuoRbPOyrbeEX8h23qFtsom', 'VERIFIKATOR', 'https://dev.pringsewukab.go.id/foto/1717638894316.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(756, 'DADAR SETIAJI', '198410102011011015', 3, 499, 9, 19, '$2b$10$YwXoaOkRUoH1JyC0kk0gguPdQoXjGdSdOss256aW/gTCDyjWXqBtu', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(757, 'MIN HELINA', '197609062010012010', 9, 500, 9, 47, '$2a$10$vhzYSYlB5KeVPG0T2MCctO3YDJYuUrHmK1AgXAG44TFx.mjVZu9Y2', 'PERMITOR', 'https://dev.pringsewukab.go.id/foto/1664423113500.png', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(758, 'YOAN ENRILE ZAMA', '198609192011011006', 9, 501, 9, 47, '$2b$10$WTuvrqz1mznsJ0tnms.eGOS2kCnRx.mWW2YWKwZKpdGIH7jLkdzm2', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(759, 'BUDIONO', '198010102009021006', 9, 502, 9, 46, '$2b$10$H3m37fzwGooWRMVXzSMSBufGxwJ2Rp3XMVoN5q1J7zEHYiSlw47Qa', 'USER', 'https://dev.pringsewukab.go.id/foto/1666599638515.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(760, 'ERDIANSYAH', '197602192010011001', 9, 503, 9, 48, '$2b$10$dsZIxu/hzr.kfSWulYB/dO/mZnVTrenqzCpHBZwIW/WNtOiQWSUS.', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(761, 'RINGGO AMIN GEMAIL ROLI', '198402142010011012', 9, 504, 9, 46, '$2b$10$nez3u9Zn0yQRcvcWPVQ6b.9CsQJh5yH7.S.yHfZO8kcpbTyHWkC1q', 'USER', 'https://dev.pringsewukab.go.id/foto/1752047167492.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(762, 'HARYADI SURYA NUGRAHA', '198506202010011015', 9, 505, 9, 49, '$2a$10$waZV6H054TD3Q8Ah8vqmLebt0tx75YcLcs9XrQ8BodfxiofPzQkMS', 'USER', 'https://dev.pringsewukab.go.id/foto/1723085848311.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(763, 'ELFA YULI', '197907102009021005', 9, 506, 9, 49, '$2b$10$x3sttVe29tskYkpPyrxejOtOxTUKTyr9wozUulcXooTyIZ.EHN8AG', 'VERIFIKATOR', 'https://dev.pringsewukab.go.id/foto/1664759203708.png', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(764, 'HENDRA KENCANA', '197706102010011014', 9, 507, 9, 48, '$2b$10$ZEYhCt7lVfOx9EHtk9A2heln.fl8YcU0w6dPL.j9VkR.gQyA5Sio2', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(765, 'ARIS DARYANTO OKTARIYA', '198310292010011004', 9, 508, 9, 46, '$2b$10$KvOGuD4tmM92xo94EsyFTuqvnE2vVEKN8TYOT/envHTGCE7BN4hOS', 'USER', 'https://dev.pringsewukab.go.id/foto/1731304395063.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(766, 'NINGRUM KARUNIA PUTRI', '199607072019022004', 9, 509, 9, 763, '$2b$10$Lngj4VFmK6DMbUJPaXBMHO522mTweoiLahBd5G1rNABkirjZ/ikYK', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(767, 'NOVI HANDAYANI', '198211152005012005', 9, 510, 9, 758, '$2a$10$mvXJy9quh52gJugnTH4rwu61.bf06S/uUnClJ6PhDcg0E996rqwVq', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(768, 'YUNIZAR', '197610032009021002', 9, 510, 9, 757, '$2b$10$j3E2yt2rYMqjm8cb.QJCEukuGjLk/FhW.DDO6TrUEeQ3TXwgTpl62', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(769, 'MUHAMAD FIRMANSYAH SYUKRI', '197509291999021001', 9, 511, 9, 758, '$2a$10$OFImni74sotQ3K4B5uoEwepfJRCfZxb/PUhghcTPlmqgH0sgonr3.', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(770, 'YUNIZAR PERMATA SAKTI', '198006092005011004', 9, 512, 9, 763, '$2b$10$E5h49Mg.vTqEKJtfqnnkgOvEjPrwodnMFF/uuvzlqoNZG8nj.iqFy', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(771, 'ZULIANTO', '198212042010011014', 9, 513, 9, 49, '$2a$10$bN.2JlSJs4NkQKmBGlELz.DwqRiZwZvuvTYdKsZtO/hxd2Zv1ZjkK', 'USER', 'https://dev.pringsewukab.go.id/foto/1664438184481.png', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(772, 'SULTAN HADI', '196511091985031002', 0, 513, 9, 48, '$2a$10$xNaFHlhycjsm81CVWB8ig.qojYMLitHqn6dzBeFZTkm9CW9owF0ze', 'USER', 'https://dev.pringsewukab.go.id/foto/1664929733444.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(773, 'RIRIN DESTIANA', '198912262014032002', 8, 514, 9, 43, '$2a$10$fI2bRu59SAaiL4aReL6VjOmno4ccQrXienxQTXulsv1uqsgQXSVmC', 'PERMITOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(774, 'DIAN MARISSA', '198501252009022004', 8, 515, 9, 43, '$2a$10$myJdZqACi0KC47cU/f4jNOfBCfhs4cAcbOKx699OMqIrL1UBZIv7y', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(775, 'YUDI RAHMANTO', '197407152007011010', 8, 516, 9, 787, '$2b$10$iyUtcSBWd2LOxXBmOdC7Ge2OgP9n520jS4eYxHAB6dsswdPxcg7Y6', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(776, 'NELY OKTAVIA', '198710262010012014', 8, 516, 9, 787, '$2a$10$cn.7UIKikbr7BVnC75mItuai8B5.XCCDohuLNrbvykdAFi/5z25be', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(777, 'WALTERINDRA GERI PERMANA', '199710052019021001', 8, 517, 9, 791, '$2b$10$oVNUSJizJmvModGQNvUZ2.OkVdtGh4a5Rb716A.as1overkWsl9xK', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(778, 'BETTY MUTIA PUTRI', '199201062019022010', 8, 517, 9, 791, '$2a$10$oOOs0GOvhOnVPiFfST5r1O0S7I44GBWgDFr7m2CoU5IZR35w/nnue', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(779, 'FATIN ERPILIA', '199804132020122008', 8, 518, 9, 788, '$2a$10$WbvnODyplwGPlismm2b2EOqYlR7Je/bOjZ4cWKnkNOrgVAAguQmAi', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(780, 'AYU CANDRA', '198901042020122014', 8, 519, 9, 789, '$2b$10$uXn8wRz6PWzA4x/qABoFAO95rLHm6QHkBSK/K8cCFvV8luCNIOku2', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(781, 'NOVALIA PRASTIWI', '198511272015032001', 8, 520, 9, 789, '$2a$10$1R45PqUIn1rId4j/lZnUleM5iLU/lsbHq9M5ig/YnPG5ITbARYQj.', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(782, 'NIA ANGGELINA', '198404122009022009', 8, 521, 9, 773, '$2b$10$1nbJSnmT56pPYLZsqJaSEu4J6oRZFtwQAE.OIlzkkAbGYWJ4ulpPi', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(783, 'FELIK BERIMUDA ARUM', '197409132006041002', 7, 755, 8, 826, '$2a$10$jd41lQrZLeFNhcrQnFAqPuIHpsC05O6XUtKlrbU0oH4iWLoJYiUEi', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(784, 'SUDARMANTO', '197511062007011012', 8, 521, 9, 789, '$2b$10$TUIWCF7/hTSM71oIPRocCu5WDGNMGjjmtADzfLkMQ8/IbR.590F5m', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(785, 'SARMIYATI', '197808282008012021', 8, 522, 9, 773, '$2a$10$EAlJ74GyMohj17QPiv42EuBbJ42CTVANw7uiY34mBOG.rRfhkrzS2', 'USER', 'https://dev.pringsewukab.go.id/foto/1671065389728.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(786, 'EQUARIS SILFIANI', '198502152010012017', 8, 523, 9, 139, '$2a$10$nWg.8vpxGm.vAMr6LupWaOGBYk08lmNkZJa5Q2Rh19/3RQHWHAV/S', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(787, 'EKWAN DEDY JHONI IRAWANSYAH', '199106142015031005', 8, 523, 9, 139, '$2a$10$SMJ2Rz98MILkkPRXxoNMwuqyATpgGEKzwU245nTZkEM.fHiunLtdu', 'VERIFIKATOR', 'https://dev.pringsewukab.go.id/foto/1665966540959.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(788, 'DWI SANTOSO', '198009202011011003', 8, 523, 9, 44, '$2b$10$buzFj0tJzz.ZGspugUuyz.zP1urTcMn5piW/YGdstw6KyOr.9KM7u', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(789, 'AHMAD ROSID', '198704122011011003', 8, 523, 9, 44, '$2b$10$0yyhy/kI9YEZAHcE/mK9Rez6qYYtfiV7QtVUMIxbXbMz2XxA8Dkcq', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(790, 'NUR ISMAIL HAMID', '198705152014031003', 8, 523, 9, 139, '$2a$10$1YXua0ruYUbln.PiPKqkJOHEIm9TMNOiQCjKq.arz7gUeGXCQVLjG', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(791, 'NURDIANA', '198907282014031002', 8, 523, 9, 44, '$2a$10$5yyRTjZquIGhsYiRvqdK4.GJ5qoGg95A5UVtg3DWHwuIBaSwiUl6C', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(792, 'IPRIZA', '198207092009011004', 8, 524, 9, 774, '$2b$10$.xyM7PD1H9EBbYde9VZpZeiy95.baoUx6vl7BkAlndiBnmA3aB0zy', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(793, 'RINDA ANGGESTARIA', '199603082019082002', 8, 525, 9, 790, '$2a$10$GMitgp/WtLhv.huZx9HqtuLirPz46nmHeTh9YLz5Dc6Ex228s64WW', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(794, 'ANGGA PUTRA PERSADA', '199601262020081001', 2, 718, 9, 139, '$2a$10$3Q67.vSORlL2qNJYOkwa0O91FVcSch3QDC8lckhZKiq68MIUtVoLK', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(795, 'EDI KUSUMANTORO NUGROHO', '198107242005011007', 8, 527, 9, 787, '$2b$10$p0XRSXYs49zmFJwjQoP3t.jVIPl1NcgReSmdj/P3leb3BpVtFyfcm', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(796, 'NISTIA DESTANTIKA', '199012202020122008', 8, 527, 9, 788, '$2b$10$TGybnOBNK9xxiv86qLM8SOblN422r.RpPXvMGtcEum.Hze3c1E4iW', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(797, 'JOHAN SAPUTRA', '199108262019021004', 8, 527, 9, 788, '$2b$10$YrezdLY1UCsnlIrfaCbbyO15je1Wr0I5PiGGxsKsMpI.1IUUosJHu', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(798, 'FEFTIANA ANDRIYANI', '198102162010012010', 8, 527, 9, 789, '$2a$10$j0P97KKo2CeLyConKXy3FuCzuTkl0D41cvOQotkZm06K7eYfY5nkG', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(800, 'DEWI NATARINA', '198406092010012025', 12, 529, 9, 64, '$2a$10$oYOu8i9NPQp24C6IBoHFlOUvVNOJZFdLRJ74BKQ6mCxWmFJ2pKcZa', 'PERMITOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(801, 'DENNY EDWIN', '197908072000031004', 12, 530, 9, 64, '$2b$10$Baez5wRCUq.BDjgDBY4rNOW8NPsbb3T1ie.lYxOUt7m/YMAsRP0T6', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(802, 'IKA HADIYATI', '197403191994022002', 12, 531, 9, 65, '$2a$10$1x2d4r.hmsg5EJUYwm4M3OpyOglPfEaTH89d2ukY1x6F/DUUJ.g46', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(803, 'AFRIANI PUSPITA RINI', '198604172015032003', 12, 532, 9, 64, '$2b$10$7n51nNwxE9aNP4Z3538f/ecO63R0HKp.AL2Y26qtGzT3KTLX6vO8i', 'USER', 'https://dev.pringsewukab.go.id/foto/1665046352150.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(804, 'AHMAD ZAILANI', '197802122010011007', 12, 533, 9, 66, '$2b$10$GBdLOYDr1A0U7DeTVgd1E.HRbJk4fFw0TZw3ZTuq5KW162QxMVfOq', 'USER', 'https://dev.pringsewukab.go.id/foto/1664435604684.png', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(805, 'RUSLI BASTARI', '197404102007011008', 45, 690, 9, 66, '$2a$10$5YZXKFnDqipo37KmAUQfveImtlkFRryHGiUvE/hBuVPFRjw50Ty/W', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(806, 'DEDY AKHMADI', '198108022015031001', 12, 533, 9, 66, '$2b$10$J9jWWwFaETBs29MP6aMjNeJi01r6FkTejM.roR.M14/jUNQ3JIXDq', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(807, 'ALFIAN SAPUTRA', '198712042019021002', 12, 534, 9, 65, '$2b$10$hSyq69OsKTvlshqNa0Ok7OGKBUfS44oA1pyu/AA4HUpDpfCngw.6a', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(808, 'ANDRIAWAN', '198705052019021002', 2, 534, 9, 172, '$2b$10$5Af9izH7od9SQZDmOUnSI.2.j3LPcU54t0PEkGbCLqZM1wJtyBABK', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(809, 'WARIANENGSIH', '197310282007012007', 12, 535, 9, 64, '$2a$10$DTMYKj8aP7EV3VlIHNm2ZeJo1bManudUE7vTJIBZWOWDcOF9Fjkbu', 'USER', 'https://dev.pringsewukab.go.id/foto/1665372894491.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(810, 'INDRIYANI', '198308182010012025', 12, 536, 9, 65, '$2b$10$enObcYJSdVaYuiXLxl4OyO63P9HECHIp5xZsVKoePXmBBGDkdQp1K', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(811, 'HENDRI', '197112142011011001', 12, 536, 9, 65, '$2b$10$Q4b7S0VB4JX/hgZG.1VcneS56PHTa1h2q8lw3jPT7CF8CnT02deCG', 'USER', 'https://dev.pringsewukab.go.id/foto/1754541648423.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(812, 'SETEPANUS BAGUS WICAKSONO', '199506212020121010', 12, 537, 9, 65, '$2b$10$vCNKLFhK7bDo9Uh8OYacX.hkrd5z/88i4EuOvBFbXJd1IA0GBUf3O', 'USER', 'https://dev.pringsewukab.go.id/foto/1664427308501.png', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(813, 'MUHAMMAD BERLIA RIZAL', '197709092007011016', 12, 538, 9, 64, '$2a$10$PgkNSELyj9wD52u25IU2Me.wY1PZVQV.fZuvJai/siIwDIqxo3X8e', 'USER', 'https://dev.pringsewukab.go.id/foto/1670834766331.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(814, 'FITRI FAULA', '198804292010012006', 7, 539, 9, 1006, '$2b$10$lVN4cHDb8Kr2IFNwDul6kOjG7zwmE9xGBqUIQan40TT..4M3J5/mK', 'PERMITOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(815, 'ELYANI RAFIDA', '197512012009022002', 7, 540, 9, 45, '$2b$10$w9pHDA/.pT0qFTIDZipmIO37rIg/g3LNFR1GPuLmkjwwshq43cTPS', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(816, 'MAINY', '197905252010012015', 7, 540, 9, 37, '$2b$10$JmGLuCkHWBOkEEx1S0uCOOLNeHnqVCXhgmsltEqZaPGjF3oYKagIu', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(817, 'BAMBANG ADI PRANATA', '198605092011011003', 7, 540, 9, 38, '$2b$10$vlednCufEepUy4rX65U5HevJd88vHnWA7CsgLVG1l0rnU10JMwTa.', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(818, 'YULIA SETYANINGRUM', '197907182006042015', 7, 540, 9, 38, '$2b$10$NquWIIiJiVq9Q5YAL3wiyOhGf5igG8LKvs/OJGZ5nsf/V8lWqiHRi', 'USER', 'https://dev.pringsewukab.go.id/foto/1665065243229.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(819, 'DWI ANDYSUPRIANTO', '198402172011011016', 7, 540, 9, 45, '$2b$10$rjkZe80pporh760cMzhDCOsW5lfdwFDxKRrKylLTWoxbyyHU06M5K', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(820, 'EKA OKTARIANTI', '198410232010012019', 7, 540, 9, 38, '$2b$10$YKyTuSA1IE74ReVZPMqRtOtZwltPDORiE0EjmTb4hjYYybUH.76A2', 'USER', 'https://dev.pringsewukab.go.id/foto/1672636627231.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(821, 'ERWIN SONI', '198012082010011011', 7, 540, 9, 45, '$2b$10$ckdzF9WuK9gvhCcBZsBBgeGx3SqnG7642HBlfTCAtwZyHilZryLXe', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(822, 'NURUL KHUSNA HADIYATI', '198411122014022001', 7, 540, 9, 37, '$2b$10$Wl3mA.tbCUEXmT3a12c9VesZiQhohHK1jjwn002S9BRNK9bYtkHhG', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(823, 'LIZA WARDANI PULUNGAN', '198010252010012017', 7, 540, 9, 39, '$2b$10$0sCiyoMh8wl2yxQfLUvLZOCEVKBxW/joDDHd6Y7KA6tZf5kh9A2u6', 'USER', 'https://dev.pringsewukab.go.id/foto/1665109243740.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(824, 'FATKUR ROKHMAN', '199009122015031007', 7, 540, 9, 39, '$2b$10$GAetBNrrKHeaoVqDXmE/MOA2t1XVXZBofWzncu46PEaxsga9lHmzu', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(825, 'AMRULLOH KHUSAIN', '198803082015031007', 7, 540, 9, 37, '$2b$10$JEnga17t0JgABsXgOJBRVOL1CQYVWJu3WxhFv34F/dXWfxBUcm5Gi', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(826, 'EVY SURYANI SIMATUPANG', '198303312011012006', 7, 540, 9, 814, '$2b$10$bk0z36.T.VHllVjSyAJ9Ku5sGA.VS4DEBZ.U9W7qUviXa1PdI5nVe', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(827, 'NOVI ARUM ARTATI', '198511042015032002', 7, 541, 9, 814, '$2a$10$EV7w4Uy7j4ikpeEXwU/nk.DnFFzDwSchw1mxQHlcY2y7MJRiXDLru', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(828, 'SURYA TRI SAPUTRA', '199310212019021005', 7, 542, 9, 821, '$2a$10$ilT8xFNuHN3SVtHa71T3EOtP7K2mBVpbPdDgaL.ubhf7tonNoOIxe', 'USER', 'https://dev.pringsewukab.go.id/foto/1664429062061.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(829, 'YASIR ARAFAT', '198502182010011011', 7, 543, 9, 41, '$2a$10$xKs7RzXpXY8OEoVHTc7NLuK99fKThtJjNfi/3OeSnAIb/NPKccr4e', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(830, 'EKO WALUYO', '198712302015031005', 7, 543, 9, 41, '$2a$10$WPqG3BokDDsbeQwnGYGo3.1C0hhihegywuyQtVI0s.eAyWZR6O2iu', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(831, 'NELY YULIANTY', '198207062010012030', 7, 544, 9, 835, '$2b$10$IVQ6366MLpmPwv6oY2Yn.OBawtgXLIHDwaNkUMFk25QpKaW/rs0LO', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(832, 'ERWIN SAIFULLOH', '199702232022031006', 7, 545, 9, 824, '$2b$10$9Ci1g7/mod5hI5vg79xTIOgeAKynUcXhqJPwuo6vtEYRdxkX5.SMG', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(833, 'KEMALA PUTRA ARIFIN', '198109102015021001', 40, 443, 10, 676, '$2a$10$MCXQzdujsC1hXa7ey3CKNuotY1DRA7hoTPDrJ1IKSYSAEw56XE8pW', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(834, 'MUHAMMAD FAISAL ZULFIKRI', '199409152019021004', 7, 546, 9, 826, '$2b$10$Rmru7uMl3eInCduZnhvk4OWWY5lVsvKz.1Ws/JtNWM7Hewuw0MxbG', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(835, 'FEBBY SABEL SUPRIYATNA', '198602042010012021', 7, 547, 9, 814, '$2b$10$6iPZ/x64hHadM00UnajkZeDBbT0xf9akbJ740YxtNsUVG4KB/.9j.', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(836, 'ANIZA PUTRA', '197607252010011010', 26, 548, 9, 129, '$2b$10$i9RNv.thjM5AvD12UbAf5OZx7s3xaNetnEUsOBOclNnk2uHZghjEW', 'PERMITOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(837, 'REGKO OKTALEO', '198610252010011008', 26, 549, 9, 130, '$2b$10$75Www9Fuo6uSEu8reoqD6e1WD2I2FmItN7t7Nineg7sbo8z/lLrBm', 'USER', 'https://dev.pringsewukab.go.id/foto/1706147395535.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(838, 'NIA MARULITA', '198506042010012017', 26, 549, 9, 130, '$2b$10$oPuNNGvCInKAsfjduLaYKOw4qqg04zOHP.aQI4gSD7quMTRVL2wCy', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(839, 'ZURTA NADIYA MUMPUNI', '199201282019022005', 26, 550, 9, 851, '$2a$10$VMue7QSEgzSUiQvQ6blYZOC/mk3ng4mT4DW7fCmZqpQM5qAcQ.pTC', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(840, 'MERRY ARISTA', '199605032019022008', 26, 550, 9, 849, '$2a$10$qpZ8b/K8QR1.sWAY5sPjou1IZjZgUSfKbARcf/QL8BPzIiCFExHxm', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(841, 'ITA MARYA', '198510142019022004', 26, 550, 9, 850, '$2a$10$hTUxXo5M2FxtYV3izAW9Uuq.ef5SRPesz1Cd4J9aZ4ie44eddrhrS', 'USER', 'https://dev.pringsewukab.go.id/foto/1664432877005.png', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(842, 'DESMALIA SUMARDI', '197712192011012003', 26, 551, 9, 129, '$2b$10$yvf1kVjINkanCEJXaCSQwetszqIjKRvrsAbOHhc5zQsNRaBrvxk86', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(843, 'IIS MAITASARI', '198205212005012006', 26, 552, 9, 836, '$2b$10$2lPbhKNzoslLUb8MGbK.kOMfVsN7DYFrPhLN56dV2cHJNQrif.PF6', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(844, 'HANIFFADIBA SYAHFINA', '199612142022032013', 26, 553, 9, 838, '$2b$10$ltgoI0JPItunWTXQqUWlRe/un8yhQuskNV4HECKfAA2PQ9Ooj5KpO', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(845, 'CHANDRA ADITYA UTAMA', '198110022010011017', 26, 554, 9, 836, '$2a$10$7dMtdI4Bw3GeIX/4eHxM4.PxgsCgH05zX3R2oD.nlYU7hS8JQHj3.', 'USER', 'https://dev.pringsewukab.go.id/foto/1664423515105.png', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(846, 'ZEDY SUNARYO', '197306052006041010', 26, 555, 9, 842, '$2b$10$IghKht95051LtC69H6nTS.j/ritELOAcRoWrGv85U17orxkgsmHxK', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(847, 'QORI AHMADI IMANDIRI', '199010082015031005', 26, 556, 9, 850, '$2b$10$KNiS4jBDmPElWpbNanbuzOr4LU5TCxwcwGcANNmEY2VaaPG8vrGua', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(848, 'PRAMILADIYANTI', '198612072015032003', 26, 557, 9, 850, '$2b$10$t/yL7ldbcswta70WE5Yji.aSCJ41VbheZ5pKwPXyLZxsWP7kWjfue', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(849, 'NURRATRI SUSANTI', '196709251994032008', 26, 558, 9, 131, '$2b$10$e50tUgmUCyeyg4AsrsO5/elAUIu.3skguwSjZVrVrl1MxIo2/QW52', 'VERIFIKATOR', 'https://dev.pringsewukab.go.id/foto/1668125689126.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(850, 'FERRI FESDIAN', '196602131989021001', 26, 558, 9, 131, '$2b$10$FpM4cvQ/qyPJPSKRZqFvYuXJoVUDdzKN1fpivl/qtGrjRj7tD8GmG', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(851, 'EMMALIA AFRILIANA DJOHAN', '198704052010012019', 14, 558, 9, 73, '$2a$10$3rmC0KaqssL3yPvJjc.NOu6TqeGCWLMyz9k33GKAEbfpEBJWdP1oK', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(852, 'ANI MARIYANA', '198401072010012010', 25, 559, 9, 127, '$2a$10$efPqz7KY5MixM7GrKXwo2ea/k6M0TOER22712JtftnCyiTqe/UGVO', 'PERMITOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(853, 'YANDRA OKTA WIGUNA', '198810082015031002', 25, 560, 9, 855, '$2b$10$AZ0SUVvWPJ.73D.Xztlg3eTqQzsLJqkahpnOFipFSgc2IPB2DIVoi', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(854, 'MEI MEGAWATI', '198305042015032003', 25, 560, 9, 869, '$2b$10$HY8ceEbkqjPwATHNHesA6ej2J4FVCmAibCusdWqHxDIPOU0AOmQe6', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(855, 'WAHYUNI NURMALIA ARIFIN', '198009192008042001', 25, 561, 9, 127, '$2a$10$aPT60hXXuJKkZYbESuxWAuto.6fungeyMYJ16/6DHdf5jQ6B1UhRi', 'VERIFIKATOR', 'https://dev.pringsewukab.go.id/foto/1709692137881.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(856, 'TRIYANA', '197610042010011011', 25, 562, 9, 852, '$2a$10$JwGq22XWt1rVG4uXerULBOcTMAWAQr8BS0qn1d/tzRv5JEiVYzZRa', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(857, 'JONI IRAWAN', '198801262022031002', 25, 563, 9, 866, '$2a$10$y0MRZpjgNfBlfGM/gbtkRuyftCHLHH4ijkLLvNi2B2q3LlHGxOS0u', 'USER', 'https://dev.pringsewukab.go.id/foto/1664430439933.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(858, 'MEIYANTI', '197605202003122006', 25, 564, 9, 869, '$2b$10$hWrWAkg7pcSUndmYgVgPkeMMuKtCOkksDhUH6KzTOLsBxQYphfIz6', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(859, 'ROHI MANIKA', '198405272002121003', 25, 565, 9, 855, '$2b$10$ZBz25n8tIztcLVfcGTXcOeVUT5zWNLdV7Gfxgwau0r/cPiPpCjvXC', 'USER', 'https://dev.pringsewukab.go.id/foto/1675995074430.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(860, 'DOLA SANTRI', '198602152019022003', 25, 566, 9, 867, '$2b$10$9y8M5GvJ8UYNZ3v2Ws05xe/S2rmy6dNX.KoDnlWjoc6Yd2p0sCexy', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(861, 'RIA MANOPE', '198606242010012017', 25, 567, 9, 855, '$2a$10$VV8WyBvjhjmL9UF9yfQabOjMblvlBRoDSrmEjfd5qnS4pW.uJW4aa', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(862, 'AFRIZAL EKA PUTRA', '198907102015031005', 25, 567, 9, 855, '$2a$10$qzB0dRiFEM/lYXGxw1WKNebIrezO9Pc6km.xz6KpZZ9AtPKldm.fq', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(864, 'YERI AFRIANTO', '197406192009021001', 25, 567, 9, 855, '$2a$10$Hxw7KXcwuGce1kHmHvON6.THBqmOnaB6y/r8TxJXKRMO1kYcois5O', 'USER', 'https://dev.pringsewukab.go.id/foto/1664964369950.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(865, 'YULI AGUNG SAPUTRA', '198107112011011005', 25, 567, 9, 855, '$2a$10$kjV.5VpGdTvPUjnjxZF1euxkYH68/FmRsBxnoezIUOetioUMlAByC', 'USER', 'https://dev.pringsewukab.go.id/foto/1693793251663.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(866, 'RIKI AGUNG YULIANTO', '198407152015031003', 25, 567, 9, 855, '$2b$10$3XCoVqb8mQ.cVowWHZAS6OxMQf56eXEMiczBql804cWAvGP0g4m7u', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(867, 'RENNIA SONE', '198604122015032007', 25, 567, 9, 855, '$2b$10$AsOyXuOgcre80hQvHBCMquE51IKnHHaBbUUlbuKQ/j2If1jRuHwDS', 'VERIFIKATOR', 'https://dev.pringsewukab.go.id/foto/1664508924579.png', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(868, 'ELLYATMI YUNITA', '198406122011012007', 25, 567, 9, 855, '$2b$10$tb7eHzw41id3AtSSpYvOaus0O3l4EokXFziED5vPsh3XKVOaihKSa', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(869, 'FARIDZ RAMADAN', '199004252011011002', 25, 567, 9, 855, '$2b$10$4Pml.P3NQ94sfBIyfAp2S.KQqucpek6zOHV5IFf0yjVkALQ5iT5xq', 'VERIFIKATOR', 'https://dev.pringsewukab.go.id/foto/1717398303669.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(870, 'RIO REZA VAHLEVI', '198610212010011004', 25, 568, 9, 855, '$2a$10$iB6Cb3lJjUotEXujaypfVeLIEqbvIk1/6WXmZzslxy7v8CBi0wU2a', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(871, 'MARDIYANTO', '198603022010011009', 25, 568, 9, 855, '$2a$10$h1ZnTxQ4sbh7F4XCLiMs.uRPOIlO.bl7AiW9vxkVdTSksrcRxnL36', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(872, 'YOPIA ARNAWATI', '197807092010012013', 25, 568, 9, 855, '$2a$10$uKZPDRqTGzA99rJGDTwEI.0PfQwuR5tsJQTRV2LDeLiI2eaWQjZn.', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(873, 'MULYA ADIGUNA PARINGGAN', '198905272020121007', 25, 569, 9, 867, '$2a$10$uIfV/BswDGKP52/oBJZ4s.zaIizNeVAKhoGKtnEEahZQJmqpYIp2a', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(874, 'KHAHARUDIN SANUSI', '199111192019021003', 25, 570, 9, 867, '$2b$10$pudNwezsASNeN4HCCET2q.TQKDHI84lNc2/uzYiq5kEgQ.2n14CUy', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(875, 'SYAMSUDIN', '196806151992031009', 17, 571, 9, 88, '$2a$10$d41zTJYSj1NEZBURp9ZjpuaUyrzdIZoTIc5yjIaMG5adnszr2ym7a', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(876, 'RIDUAN AHAFID', '196703231990111001', 17, 572, 9, 88, '$2b$10$fwFKd6tNhWQuXGON5S0iFe3JVZkrpDTqKOmQBOlY8PBMP/l5wXUHu', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(877, 'RIANA  FAJARWATI', '197904172011012002', 17, 573, 9, 88, '$2a$10$pq0CdS80jJW684DSdnjE/.SPVYY1xg5TY681HwUMdkVqjq5omyIoO', 'PERMITOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(878, 'SHELVI DESMALIA', '198912112015032004', 17, 574, 9, 875, '$2a$10$YAoWeOD1F5LkVkazj65p9OWza5ydpXiu779R2LZfUy9x4zWyRufKy', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981');
INSERT INTO `pegawai` (`id`, `nama_pegawai`, `nip_pegawai`, `opd_id`, `jabatan_id`, `pangkat_id`, `atasan_id`, `password`, `level`, `url_foto_pegawai`, `tukin`, `edited_by`, `first_time`, `created_at`, `updated_at`, `no_whatsapp`) VALUES
(879, 'FIRNANDA WIBOWO', '198512312011012010', 17, 574, 9, 876, '$2b$10$gS0gWFAnsGf7ZoucwVzFM.EXvgOATGh7u4ZwDfDKHsmmwqoOkrW86', 'VERIFIKATOR', 'https://dev.pringsewukab.go.id/foto/1665107023254.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(880, 'DWI DANTARA', '197004212010011002', 17, 575, 9, 88, '$2b$10$lEjVnuh8FqotKJ6zePdEieE85GMrjkR/.BaTXBktOxyMlJfNKTixq', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(881, 'TAHMIADI', '197304161993031004', 17, 576, 9, 89, '$2b$10$rX2X.ugHVU8CZ6MlGNMBzu5Dj2U8vsptgMxsI06wRGRc97kHr9p3W', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(882, 'BENNI HIDAYAT', '197411132006041007', 17, 577, 9, 89, '$2b$10$.fR7dv5hYSsPQYf9VIjBHeZoJLJsdzZYtIQBtxVwlqXzkl4wMY3mq', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(883, 'EDY FIRDIANSYAH', '198202262003121002', 17, 578, 9, 90, '$2a$10$lJKjfz0YLCQbt8OFsfMRxuv.CzTHds8NUS.WzW/w7rKNESWEVxcMe', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(884, 'HAPZI', '196705272007011021', 17, 579, 9, 90, '$2b$10$7l2bcPktnb7VkHDzy/QTlO7MbDF7G6NAXGV4WLpWM5bm0zsoRwUPm', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(885, 'EDI NANDAR SUKODININGRAT', '198702042014031002', 17, 580, 9, 897, '$2a$10$WPy7Sxq6Ioj72S6C8x9Bhe7OSx1tuvvlh66wYlpOS1Z0b4z7jzJU.', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(886, 'ADITYA HAFIZ ZULKARNAEN', '199709222019031003', 17, 581, 9, 878, '$2a$10$TJY5OSgwriW9ARLM.IyXAO4unc2PaRxKxDlFcujBT9MLPHV52dns2', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(887, 'FRANSISKA FIKA DESMANTIKA', '199912152022032012', 17, 582, 9, 879, '$2a$10$G2QvSjCRYRokTliC.3UTv.MIonQ1kBUJ3aAJg0u4lqfBNLKLVTdBK', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(888, 'RIANDARI DEWI KHANIFAH', '199606172020122025', 17, 583, 9, 884, '$2b$10$i6Ysb3Tev6Rq25wmk2vC3.3dDdC/fcMJa2aWpKPEppUGIZ7sVPoli', 'USER', 'https://dev.pringsewukab.go.id/foto/1664431824084.png', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(889, 'GINTO ROHAYANA', '199204012019021005', 17, 584, 9, 882, '$2a$10$.Od3sK3DUfH3rHj725KymeIedDewppVKE8Tguxhn4AnIBECZ0pUbe', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(890, 'DYAH AYU NINGTYAS', '199406202019022009', 17, 584, 9, 882, '$2b$10$vDEDXxw7jtcGdG2u0rJukO3Q/XDGo6E4N7pD0JwWf.Jtn9E5ZVYsW', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(891, 'ADITYA TRINANDA', '199304282016121001', 2, 585, 9, 172, '$2a$10$62U7cSq4QgmKB6Zh231z9OFkyjlTPFcou/JTcNyOzjx82L4xeBm2y', 'USER', 'https://dev.pringsewukab.go.id/foto/1710827318159.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(892, 'PUTRA PAJAR', '199810152020031001', 17, 585, 9, 884, '$2b$10$xaDN.kTm3tyI8drz8Sahp.MrQTpyQrtx4fubuEirrq9v6VqjbtpeK', 'USER', 'https://dev.pringsewukab.go.id/foto/1664430558984.png', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(893, 'DESY RAHMADANI', '199812272022032009', 17, 586, 9, 878, '$2a$10$jZi.eenOrbgo28haHqUUBOUY0k5Ctz2ISnr6rvuqJUh6UzhSlqAoq', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(894, 'AGUS JAUHARI', '196808171993121006', 17, 587, 9, 884, '$2b$10$fVm0MhQ.KzElIlSK3AdVCeyRuJ/MEXPRN.67dyWyWTq1dpFr1.lqS', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(895, 'ARMEN OKTAVIAN', '197410221999021001', 17, 588, 9, 884, '$2a$10$D/.wInW1C2ktUkzVyjty5ewkzxzrbQ5dq2dxZDQv0lcmuRg287aYK', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(896, 'ZAILANI NAHRAWI', '196408101985101001', 0, 589, 9, 0, 'a', 'USER', NULL, 0.000000, 0, 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(897, 'AMRI', '196501041986111002', 0, 589, 9, 89, '$2b$10$R56/.vgdNCrLIsNONPjCBunCYmhIxzSVUqL96iIOL84cn41.jGDa2', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(898, 'YUSUF HABIBI UMAR', '198309242014031001', 6, 590, 9, 33, '$2b$10$rtQ.yVCUbPim2IWpXFOCGuDfok2hz2rUgJ.ClTbEYK.Qi4tvdAmW2', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(899, 'NOVRI WIJAYANTI', '198311302010012014', 6, 591, 9, 34, '$2a$10$YWQjVh8QrmR2UVjYcYB.auI8nEO.AU70Pd/C3IDpUWW8XJIYj987O', 'USER', 'https://dev.pringsewukab.go.id/foto/1668748388548.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(900, 'REKY KURNIAWAN', '199301042015031004', 6, 592, 9, 34, '$2a$10$UPUFaetxFPwsAGZnjKFkjOg8xE1pcRZdz2c8K2KX3IJ32UxSlgNeu', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(901, 'CICILIA LEVANA', '198208172010012028', 6, 593, 9, 33, '$2b$10$.kX6Q6p9efSfIT7TJFkOP.3ufPNaUoAOwHcRw8W7s3j0YOY/nQ5V2', 'PERMITOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(902, 'HANDAYANI', '197604232010012011', 6, 594, 9, 32, '$2b$10$h2aEqGzwlDefD.EM4yQ.YOWWtcchb8Zp.lXq6/US9WeKlLoHT5KC.', 'PERMITOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(903, 'HENDRI AFANTO', '198304262009021003', 6, 595, 9, 902, '$2b$10$B34IAzxPJnf.z98RZTKEf.SpENI00w6JNTYFvsvZ5avdCQEnp.ycO', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(904, 'MUHAMMAD RIZA FAHLEVY PRATAMA', '198411152011011009', 24, 253, 9, 103, '$2a$10$NaxndIFucnLFjaK/inNbmOYTatNgTDl0my5advfz88J7pWxwNsmM2', 'PERMITOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(905, 'LESYA NAWANG AYU', '199606292019022004', 6, 597, 9, 904, '$2b$10$Ud5QL2k3/wSApmNHZiqrzOpOI5ZtiB.KkQ5eHyup2V55zL/ePn6hm', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(906, 'WITANTRA ASMARULLAH', '199112142019021003', 6, 597, 9, 904, '$2a$10$p6vN9hqKGLvcGXzYZFpQZeWedJEcxEOcRfbowkIjIZhpj5uurQH9O', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(907, 'ARIO APRIANUS', '198504172015031004', 6, 597, 9, 904, '$2b$10$lyhWCrPQQZK0hPspkS4KLOXwb.aeiObfGm/QwdW12CybHefEKc5/m', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(908, 'RESTI ALFINA', '199501112019022008', 6, 597, 9, 904, '$2a$10$VJiBW2QNOVUKEDSKlSqD5.rSlypiyn3sZlocRztoh5iFKyAoWDehu', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(909, 'INDRA MARKI ANGGARA SUGIANTO', '198603262010011021', 6, 598, 9, 902, '$2b$10$GzXWElgp6u/cIhW.et0Gcur2CH0mrH2lqCRfOK/cdf.AYVu31Zp/G', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(910, 'OZA KHARIS RUSTANDI', '199209112015031002', 6, 599, 9, 903, '$2b$10$obBl78f6oDYcpnvw/.thpeNmgTJsbtBJ3nx1uv6BQ4ffF2JbEmvlu', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(911, 'ANITA PEBRIYANTI', '198502222015032002', 6, 600, 9, 903, '$2b$10$uxCB4oq6KyOHvW0YXitAM.Tw/DLo4BWVCadzZ/DT6f2CduVQZoRg.', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(912, 'CHISKA SEPTRIANI HUTASUHUT', '198409012010012019', 6, 601, 9, 903, '$2a$10$IgkjuAHqeT.vdWsvJOQu6eWj6hq4Y4/YKPGU1u2X1G2CXf.DoTRf.', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(913, 'DWI ASTUTI', '199406082022032006', 6, 602, 9, 904, '$2b$10$hHZN.zQ9rihU9YySk9Ta4eQqmhm6C6XPldEYbI2KB.R3khlZq7vx2', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(914, 'MASJIDAH', '196507031992022001', 6, 603, 9, 900, '$2a$10$pjf20yi5QqKgbxGclDj4..hEcmQEq4muiBSdDIT9SuWg3CrTMpJYG', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(915, 'HENGKY NOVRIZAL', '198011112010011018', 6, 604, 9, 901, '$2a$10$.4oezoWi6vMtgHoT75WJUeCbM/OXSOzC7KIlAaoj/QRt6QJ8m6/6W', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(916, 'TRI SARI WERDONINGSIH', '198609202015032004', 6, 605, 9, 34, '$2b$10$oArMwwst2s8xNDGl8a9K1O/udv83VLbpB38ZnI/2/kTYAzzgtjWae', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(917, 'FEBRIANA', '199702122020122030', 6, 606, 9, 898, '$2a$10$7rF5.biLdyJZEFI3Wk3tNuYkHfCCly6nEkHee86bim73TQgCo/vCm', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(918, 'JASMIN ADLIN', '199311222015032001', 6, 606, 9, 898, '$2a$10$PRIw6Ppkx6Gu1TlXC1Udsegs6ZfmVhzXiEaeVeSih6YkIIQXmJ7bi', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(919, 'HERI RAHIM', '197805252009021004', 31, 607, 9, 151, '$2b$10$UKbtI32mJuXZ8onC3H0RfetzZK27l9rO1P9tn2a839s2eUk.x1ceW', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(920, 'DWI MURTIANA', '197803101998032005', 31, 608, 9, 151, '$2b$10$O5/GrI0cWK2mUk2EOD6iM.dSPwNJHsmKsBJOX4oNSKfb954k8F0Fi', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(921, 'ATIKA WISTIANI', '198303142011012005', 31, 609, 9, 151, '$2a$10$XTn7hIHM0Je6Agb8mu2j9uSLGlJYMNKHPdYJUe2MsE0Z1TU7MYgcy', 'USER', 'https://dev.pringsewukab.go.id/foto/1664945924891.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(922, 'INDRA GUNAWAN SY', '198111202009021002', 9, 729, 10, 763, '$2b$10$rDhcaIQGiuaS.lCfoIXu.OA3xrfDPiuu2mqxIVYILNcJVxXqbU9Ry', 'VERIFIKATOR', 'https://dev.pringsewukab.go.id/foto/1664757508219.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(923, 'PRAYITNO', '196911052009061007', 31, 611, 9, 152, '$2b$10$9HmIF8i.9aqTlaKsvLLiZ.Rh.ntOtA4GTCkbLT99TX0yJf/1FEwj.', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(924, 'DIDI MARYADHI', '197803262010011001', 31, 612, 9, 152, '$2b$10$jVMEfesCMcjQ6u3w9PDWrOLobeK4gU5z6Hag1B0sbeYCKtZac3aNW', 'PERMITOR', 'https://dev.pringsewukab.go.id/foto/1664420899576.png', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(925, 'ALMUNIR', '196909122007011010', 31, 613, 9, 919, '$2b$10$jKbj.jl.6ihLQ8dSOrC7rO399LZmNQIgG6yCgYOiMHe7QCboo.zxy', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(926, 'TUTI WASITA', '198103182008042002', 44, 614, 9, 1012, '$2b$10$C0AXOIvpg64BDZng5egk0uw79O4sPrcOMNGuqd9xm/PIEUqta2lI6', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(927, 'DEDY MUSNANDAR', '198007032010011010', 31, 615, 9, 919, '$2b$10$e1oH/jyMYeHmx8boIwKcKeq.kYfrVBmrCsL9o0onMJ5vqg21toQH.', 'USER', 'https://dev.pringsewukab.go.id/foto/1729241726405.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(928, 'MIKAIL ENDANG SUSILOWATI', '196809291989032004', 31, 616, 9, 922, '$2a$10$XXZ.lheIh62sS4zMHR7PeuVFM3LCumPAQtVOidGyTENANo6TM.gYu', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(929, 'KAMILATUN DAIMAH', '198403292011012002', 33, 617, 9, 156, '$2b$10$edhjf7IP1lkcONn3/nO/aOb2dJb3pXxmJIv5dbsmBKECGiGE14Dsq', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(930, 'DEWI SRI MAHKOTA', '198203232009022006', 33, 618, 9, 156, '$2b$10$L9IH50IcCbm0.nWWkdOcTuLObj9jGCppdvU523ZY1mbkuMe6EGp/6', 'PERMITOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(931, 'RUSDIAN CAKRAWAN', '196411171986031011', 33, 619, 9, 155, '$2a$10$5GsNV7tMWWy72CxSCrwPZ.NQYtWnQLvQvTPH1ci.Ot7eLMFk1kq6y', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(932, 'ISMALISON FERRY', '197403062010011004', 33, 620, 9, 155, '$2b$10$wz6y6lYRWhbHssuNRflbTONGzxlIgw6fGTKkr49kZSIMwCHtn6V7C', 'VERIFIKATOR', 'https://dev.pringsewukab.go.id/foto/1664761177313.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(933, 'PAULUS BASKORO', '198310282010011024', 33, 621, 9, 155, '$2b$10$ar1Kagbg7pBUq2RsVcpYPuxgZWLnUv8163rKEX1oEvhvlKOYt4YB2', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(934, 'TUMINEM', '197502101998032004', 33, 622, 9, 155, '$2a$10$dP5kTmWnmMff7sU9V2a1D.MO9NH5kDo170vmJIAs5VgDkM9JyCEH.', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(935, 'MISLIA', '198110222008012012', 33, 623, 9, 932, '$2b$10$/QgPj6uWzg8eBnOe2HLVCuBawakdhhY4KpIcgy38TgWHX0Cn4tlje', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(936, 'AMAT SOBIRIN', '197209052007011026', 33, 623, 9, 933, '$2a$10$wkuxMhNnojmUKwEculs5GeO/WJIZ3f6x9xYxotRFkClEpeanwk502', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(937, 'SUPARMAN', '196809011991031009', 39, 624, 9, 167, '$2b$10$x4CBMgeo.IIswreisMDNmOVdnyQXWQgsy5IaQZI3I6ONIWBu823Jm', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(938, 'KOMARIYAH', '197905092009012004', 39, 625, 9, 167, '$2b$10$aOUnk7IgM/6FU5KR7czBH.dJZsmDNHoCbqhprFoSWzh/0EV2iSy9a', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(939, 'MUHAMMAD ISKANDAR', '197301251993031002', 39, 626, 9, 167, '$2b$10$lANbVRIE9ctUZa6.bsMnneofywMneSJOLzgiu9qtN029fpS1ITt6W', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(940, 'MUHAMMAD MORULI YAMIN', '197303122009021001', 39, 627, 9, 167, '$2a$10$YXi0GqGUE7mk2nq/uM8JW.dn6OTAFhw/0ScLBI3rA2oVqvBZlAM56', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(941, 'ANDRI NURMAWAN', '198104172009031006', 39, 628, 9, 167, '$2b$10$YfJv4EtYQKj3NoeknGDh2uHKfr6bAsFFkxVXBtHjhz/tH1W3YLmFG', 'PERMITOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(942, 'EGI HILMAN TOHAR', '197608052010011002', 39, 629, 9, 167, '$2a$10$Of6MhqJNIBRIj/n20sRDje4UqZosynXY..DqysFJ7mdUBigNbgfyW', 'PERMITOR', 'https://dev.pringsewukab.go.id/foto/1753671987710.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(943, 'SUKINO', '196504211986031005', 36, 630, 9, 0, '$2b$10$j4a8qYgJCLNGo0q55AQGFOI5hSvU96.bNeyBEYexQM34uRPgNQqTW', 'USER', 'https://dev.pringsewukab.go.id/foto/1669699766159.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(944, 'ELISABET DIANI', '197705102009022005', 36, 631, 9, 0, '$2b$10$04KqVxXHJiIstZVs5bFplufX2tI7iOd/sUoxw63yfJSWsmPGnb9P.', 'USER', 'https://dev.pringsewukab.go.id/foto/1664759524296.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(945, 'ZAINURI', '196710241991031007', 36, 632, 9, 0, '$2b$10$XZuDBJwflp/k.z8ux0bP/uyf9q4BT6h6THUrvFw.ehW1vrooMGO9W', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(946, 'PURWANTORO', '196407031985021002', 36, 633, 9, 0, 'a', 'USER', NULL, 0.000000, 0, 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(947, 'MASAINI', '198206202007012006', 36, 634, 9, 0, '$2a$10$5LKTlcAEfM894.BL3UZH9OOuSA6nzuYvT5YHl8C0P3m8zfFArIb.u', 'USER', 'https://dev.pringsewukab.go.id/foto/1664531130309.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(948, 'PATWADI AT', '197404262008011004', 36, 635, 9, 0, '$2a$10$DKoEsG6WpJcZwFby7gSnKO19Gebe0t2ya9w5/4M3WRm1xpXGJYwuS', 'PERMITOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(949, 'RIZA WIJAYA', '197502182010011006', 36, 636, 9, 0, '$2a$10$bj.1rvZFrXFvvC6wWhs0Wu.FORL.6Ui9x9m9RaJqOm04uIZVnnsVe', 'USER', 'https://dev.pringsewukab.go.id/foto/1753153656023.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(950, 'SARJUDI', '197205122007011035', 36, 637, 9, 0, '$2b$10$ge4p.Pm/8KPSdcV7wbbI/ut8zDjlbVkNTwJb13YeilMaF/YculsHa', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(951, 'MISDIANA', '197312142007012015', 36, 638, 9, 0, '$2a$10$NWD09nDTGarQGSRWRv5T8.svdqDpUrAsMFXCKOSB3e.746QsyokJi', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(952, 'IRWAN HERI', '197610232008011004', 36, 638, 9, 0, '$2b$10$trstW59gqQAdVMPS7VA9xORC8.zAnB/I/VwhP8YBuk12w7DeE5UbS', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(953, 'HENY SETYAWATI', '197703092011012005', 37, 639, 9, 164, '$2b$10$EViEjUQqx5B4Rx/FONqy8uinBNOAicLX/4RqKflAMI/C9e4T77DcO', 'PERMITOR', 'https://dev.pringsewukab.go.id/foto/1705287261338.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(954, 'ANITA NURBAITI', '198210272011012005', 37, 640, 9, 164, '$2a$10$CaEqBJGEmn.6uEUjIT80y.VHGdNH1GvnsUMkYANeB6T1YAwbTvQMy', 'VERIFIKATOR', 'https://dev.pringsewukab.go.id/foto/1711413190249.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(955, 'SUDODO', '196607141986021002', 37, 641, 9, 163, '$2b$10$hd71RRV8XB7G8M5XCgZYfOeslh9B.pXCRUVUEqpzWej/gOxYzTPg.', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(956, 'UMAR PANCA KRIDA WARDANA', '196810062006041007', 37, 642, 9, 163, '$2a$10$qnHn5SDT26OBQrix7ZNbG.pu24AU37RrmjOEzLe64hcSXfg6o2G6i', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(957, 'WITRIYONO', '196711081993031005', 37, 643, 9, 163, '$2a$10$.uuovUAgX1jESq9RUgUIsOUw48BBUlBf.aO0BJzIWjXULm3rJaDl2', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(958, 'YUSMAN', '196502231991031002', 0, 644, 9, 0, '$2b$10$4/ikz8eWRMmMeSrjbqaPber012LAJbuKdK96/HmQIQtcOGU2wAXdm', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(959, 'PUJIYANTO', '196512122007011015', 37, 645, 9, 954, '$2b$10$cR5zy6xYM0.umBST5t0em.41eQ9UB2ZCnpH286WiMEmXvup0eK9BS', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(960, 'JUNI TRIANA', '197906031998022001', 37, 646, 9, 954, '$2b$10$kdzdZKrL.IJL5qYQjZj5gOrp4l1YUoJVwPrPvPB0bxXmI8bcstYcG', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(961, 'KRISTINA DWI LESTARI', '197003041991032004', 35, 647, 9, 159, '$2b$10$PGln0QGws3D3n1WaMEiOWedDCnzbLUE12ke1EHQmSpqwGzze37Uya', 'VERIFIKATOR', 'https://dev.pringsewukab.go.id/foto/1666221217446.png', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(962, 'KHOZANAH', '197207051992032003', 35, 648, 9, 159, '$2b$10$3.UeCCraM9vChcsFTYtxTO55pB9i.Lg2LNCjzTyOinqPCj4L3SYYO', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(963, 'MUHIDDIN', '196610151990031007', 35, 649, 9, 159, '$2b$10$GCVrRRNCnLXF9t9wsyH8v.2irzQ9XGjUzvc63p6AdeAVH9vWcNF8.', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(964, 'ABDUL AZIS', '197308052006041009', 35, 650, 9, 159, '$2b$10$Yeri.S4uddr1oNAGJMy8herzveVlTOzgLBWTL84KD83Xm7r6Z0b9S', 'VERIFIKATOR', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(965, 'PURWANTO', '197507032008011006', 35, 651, 9, 160, '$2b$10$LYVsVHy2fw50iFf5S.bn/uquoj4aGXGEI34VNlocARJTSGkB5VrKy', 'VERIFIKATOR', 'https://dev.pringsewukab.go.id/foto/1665548528358.jpeg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(966, 'EKO PRIATMOKO', '198105192010011008', 35, 652, 9, 160, '$2b$10$zPqKEluL0lFyTq6wWUPw2eJyi8xC/pA514GTTLrbZNIuHl3Ji6Nxa', 'PERMITOR', 'https://dev.pringsewukab.go.id/foto/1667191263047.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(967, 'ZULKARNA`IN', '197204092000031006', 39, 725, 10, 167, '$2b$10$FslawnaKsTxzCbgJWLxiduz..wU2Rj/.53/Zxx3TfaE4lWr7Mtiny', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(968, 'BURZAN', '197212072009061002', 35, 654, 9, 964, '$2a$10$oLP6cxvpN3wGuuPdZ/x3JO.qsKk0/IRRtiRGyzkjedJp9FBOEpSLC', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(969, 'UDY MARYONO', '197312012009061002', 35, 655, 9, 964, '$2a$10$kk31h5.EEaYv/BMVOyikCeZvVzXfv2mw1AiLhCkcjIa8E0eHeenqu', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(970, 'ROSADI', '197504102010011006', 35, 656, 9, 963, '$2a$10$TQIfYKQ8xICNJTFXnsGseOpfCEUrHwXiBej8AlsbTcZljil9fgV.K', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(971, 'SLAMET BASUKI', '196501032014071001', 35, 657, 9, 961, '$2b$10$/anAWPi86yihMLD9UuBgw.y73CsG7mhO.fn9QKKnNd/R6nZEKhHQK', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(972, 'DASIPO', '196908252009061002', 35, 657, 9, 964, '$2a$10$v4ovC/Pn7QDzZuoDWrBQvuNlqBfh1eZAUTpEZZ6pGZ9oCdCnoj6TW', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(973, 'TRI JATMOKO', '197507292008011011', 35, 657, 9, 960, '$2a$10$NBh705V4Gmj5vhhVdh8tPOV2vQyNABxsVChkEKDgynegqjMtzOuK2', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(974, 'ASMUDI', '197207041997031005', 35, 657, 9, 961, '$2a$10$SB5FGd9oRssolDx9HC7UZe.4w0n0Us6tuGvwkrdsJjEdKzuiiLYge', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(975, 'LILI SAPUTRA', '197907172011011005', 32, 658, 9, 0, '$2a$10$2vv4.TOSY2.81d/GCNKshOY6tSxle0NNJdAGi68V1NHE651h4cFO2', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(976, 'APRILA HASAIMAKA', '198104212006041009', 32, 659, 9, 0, '$2b$10$Fz0EMQv9PRw/jOlCaEhtk.vH2I/vW1FD9VWl8c35At6aIKuggPACy', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(977, 'ALI MUSA', '196708231988031002', 32, 660, 9, 0, '$2a$10$bVMH8MizHzHy4WyBPc9BKuBTCIPIqi0xcGr8x1rQ.qWtPp9r.H0dq', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(978, 'PARMAN', '196901061991031002', 32, 661, 9, 0, '$2b$10$UTETT9gzLyP86JZkOIz2LOlTiGjArmA2jfPKyl3QzdzdMaggeGzFC', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(979, 'YULIANSYAH', '198107262010011013', 32, 662, 9, 0, '$2b$10$NILkbY3MylVikn6.RLfZO.HhgxIi5uoc2wkJ4FGgoKfq17E5MEjHW', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(980, 'ROCHMANSAH', '197207262007011010', 32, 663, 9, 0, '$2b$10$imNoEjZP/BmuA0MJwJ62gOojxviNJLqN.EufUiAhk.jz5ER3vH7Cu', 'PERMITOR', 'https://dev.pringsewukab.go.id/foto/1706263607780.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(981, 'IRWANDI', '198607222011011003', 32, 664, 9, 0, '$2a$10$w2J0idZJjl9EJDTIW.1mUOlQg6X3pSmkOYwCwz4grnF1wYapLx15i', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(982, 'MARWOTO', '197004162007011035', 32, 665, 9, 0, '$2b$10$2UmYd70WhzvCS/dFLaB2v.RURjUCPFEY1SgwxrRU/d82fG/Mq1ELK', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(983, 'IWAN SAPTYA', '196805041987121001', 32, 666, 9, 0, '$2a$10$q9n8Unf6TPdw61ufQyrupOIQQTTbcpS44tjO970EaPIud4oIxGlO6', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(984, 'SUKASILO', '196902231990011002', 32, 667, 9, 0, '$2b$10$Y5NG/0nImOVNdVgSMkNAO.yfJurc4IhPmuZevkycpm9L788MNDGhi', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(985, 'INDRIANI', '197905052010012007', 32, 667, 9, 0, '$2a$10$xI1dPNZaMUyZN456NuMbzeKmVfBKv.FZpPoce1/Tz3LQYHfoWBLu.', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(986, 'ROSNI', '196910271993032004', 32, 667, 9, 0, '$2b$10$ueZuyBSBD8PxlvoBSsCnw.RKXnCZPxTIejs/SY01Eu1XetzgtVF1a', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(987, 'YULIAWATI TRISNANINGRUM', '197807112008042001', 32, 668, 9, 0, '$2b$10$6eRVGSFacybQxQjI4xJcnudZQFMgBk0BTiirf4LYs84bEZcKaq7oe', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(988, 'MEITASARI', '197305201993032004', 32, 669, 9, 0, '$2a$10$ScNcrnnW/1NKdVorkiiRyeSgKjkKACRUvgdl11dhZAb2ekJ2zGLMy', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(989, 'FITRA QORIANSA', '199503302020122021', 32, 670, 9, 0, '$2b$10$aRaWnnFSrM4J.1sQs5N7PeGV1I56SqiotWmXDvwjVMoL9QGbqlCeS', 'USER', 'https://dev.pringsewukab.go.id/foto/1716265859758.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(990, 'SUSILO', '196601151990031013', 32, 671, 9, 0, '$2b$10$lyc6GTjKQNg/dt6tVrXzdeu6Zuc/Jrew5jlhD9IjXNBL7Ykp0baQa', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(991, 'BUDIONO', '196502201986031007', 38, 672, 9, 166, '$2b$10$qLuohdlm630xz6cRhUTsOu8tMerLmazrmEkRltQQRIi4XQ7jinL2y', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(992, 'SUDARMAN', '196903181994031002', 38, 673, 9, 166, '$2b$10$RC6ffqkAVU/bV.4Bx7hUy./iGHPJsupFBvYJpat7iR9V20r/oPxse', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(993, 'NINA KUSTINAH', '196603301989012002', 38, 674, 9, 166, '$2b$10$dN5S.ZuyOZZb8BFpUwZVGuLm.SBiilChxiW3TK9HatiCnyLgDpUZq', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(994, 'DANIAL', '196612281988081002', 38, 675, 9, 166, '$2a$10$D1BJ8urFkFAN25.iS71zM.2Q05tr5D9u2Lra5Vt25IN23BAF4.VYe', 'USER', 'https://dev.pringsewukab.go.id/foto/1665031670899.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(995, 'FIRZAMZI', '197401202007011011', 38, 676, 9, 166, '$2a$10$oQXd6HwOo6dVKAWpMCNNn.t.t3CQgOJ31kAuKuYGY9XyJ7VL0qVC.', 'USER', 'https://dev.pringsewukab.go.id/foto/1715820780465.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(996, 'PHANDY GUSTIAWAN', '198408172010011008', 38, 677, 9, 166, '$2b$10$EVxGJRmYiBGsEvUV4GMbjulwIYJmbz4wlhOJe/A0hBBbCngnQecUi', 'PERMITOR', 'https://dev.pringsewukab.go.id/foto/1754470096722.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(997, 'GUNAWAN SISWOSARJONO', '196602072007011032', 38, 678, 9, 166, '$2b$10$4rAjtUUBDXnjuP7mCWonEe.Ki3CgEXAL3VHc/IHVbpSYGuyNzYUT6', 'USER', 'https://dev.pringsewukab.go.id/foto/1666925965778.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(998, 'SITI PARTIMAH', '197001162014012001', 38, 679, 9, 166, '$2a$10$XYyZjCWKpbsK51piycGixepVhQy4/bCDkDn2/dARLI3v7cMibmHIm', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(999, 'FIKRI', '197205232008011004', 38, 680, 9, 166, '$2b$10$pVhuu2REuc7XjxYIwJZozuCm.PSMY3eouEYgAQ3qceacsdk8ctM0S', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(1000, 'SUGITO', '196608112007011020', 38, 680, 9, 166, '$2b$10$oXPluMInYWu24BKuYzGcCuDuS/Dnw2uKXpoemkhD96Upn/TO3zwK.', 'USER', 'https://dev.pringsewukab.go.id/foto/1665452956451.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(1001, 'TUBAGUS CANDRA', '198409132010011028', 38, 680, 9, 166, '$2a$10$ROwzMjapOqgSUkGHXSWineMGfTx9/dFDIWijc3wJL4/sQMOTrc0Aq', 'USER', 'https://dev.pringsewukab.go.id/foto/1666154061119.jpg', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(1002, 'ROSLINA APRILAWATI', '197404262002122003', 23, 681, 9, 253, '$2b$10$sSXs0TATk1Qrb2PE7IUzm.rztaihONZF6FNHaZcPPomRdZSsEDDjS', 'USER', NULL, 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(1003, 'ROMIE TRIANSYAH', '198106272010011015', 27, 682, 9, 135, '$2a$10$Kep97RM8X5eckGBAJu/KHeDk3TTJTSVxP3xVFzhOzoBM9jcPAIanC', 'USER', 'https://dev.pringsewukab.go.id/foto/1664434278556.png', 0.000000, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(1004, 'SRI WAHYUNI', '198002212009022003', 3, 683, 9, 0, 'a', 'USER', NULL, 0.000000, 0, 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(1005, 'EKO IRAWAN', '198602182004121002', 2, 16, 13, 0, '$2b$10$rQfVA36CWFwtDJii3iv3kuYqIA6pQhnx7Tt7dP84m1V0JIUhkhbnW', 'USER', NULL, 0.000000, NULL, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(1006, 'IVAN KURNIAWAN', '197504072000031004', 7, 37, 14, 91, '$2b$10$M37x90kBCa5mZbXkJuIite52jsuEwpkJGSBrESRci3kiAeMo409km', 'VERIFIKATOR', 'https://dev.pringsewukab.go.id/foto/1710739906508.jpg', 0.000000, NULL, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(1007, 'NOVANDRA PRANATA', '198711192010011003', 5, 272, 12, 314, '$2a$10$mHN7Un9k0d68wXaytkj92.5tAeju/xmnFW7eemsTP9qSyKWdpf.7.', 'USER', NULL, 0.000000, NULL, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(1008, 'NANI KURNIAWATI', '197512222002122005', 11, 201, 11, 202, '$2a$10$8HaVuPlOXSwn6x202/h3/.rL815DW/dMBd9HLn56l5JJ3FUF9HnJm', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(1009, 'BAMBANG SUTRISNO', '196601111989021002', 41, 686, 11, 151, '$2b$10$41fucfNgZftcKgZgcmabIOnKR1sUiKGnriad57ZLePuyLUhnMWB.C', 'VERIFIKATOR', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(1010, 'CECEP IRAWAN', '196701011990101001', 42, 687, 13, 151, '$2a$10$Tb.5lrB2hhVghpRPZFivvO4O/ukdzclcdJCwljbgta.sCv01wGA56', 'VERIFIKATOR', 'https://dev.pringsewukab.go.id/foto/1664759678985.png', 0.000000, NULL, 1, NULL, '0000-00-00 00:00:00', '6287838807981'),
(1011, 'PONIJO', '196508251985031003', 43, 688, 13, 151, '$2b$10$hswLoOqeADOJZDEjpacdLejyhtxO2idVyKKriTyDYjw/KC3MJ1tSm', 'VERIFIKATOR', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(1012, 'SUKRON', '197709102011011004', 20, 107, 12, 151, '$2a$10$f99gDdT0l4n/imr8/RSYFextOGCaaQK1tRR7aizF69bEwLocM8die', 'VERIFIKATOR', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(1013, 'ANSHORUDDIN LIDINILLAH', '196611062000121001', 45, 690, 12, 151, '$2b$10$KiJD.OOR2swJ.CWaIRJgkeNQkpuEha2sGOLGmVzSQYyRqiKkUwIb6', 'VERIFIKATOR', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(1014, 'RIZKI AMALIA', '198810022011012009', 41, 691, 11, 1009, '$2b$10$XU4aPl4nLyEbVISeP7CC3ONC.B2fetfYLN8qW.8GOXqyE6NDj7vIG', 'PERMITOR', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(1015, 'MUKHAIROH.S', '196905101994031010', 41, 692, 11, 1009, '$2b$10$3GtvWIVGzr3z4GKWjwK27ONoj3jHFSZeK51YH8UZCIEvPZuO31.SS', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(1016, 'APRIANITA', '198704292011012014', 41, 693, 10, 1009, '$2a$10$3kG4NukMeuZEqzIetzodYusw84faZZM9753gqEW0sSrjpzgHfrUUK', 'USER', 'https://dev.pringsewukab.go.id/foto/1667532172438.jpg', 0.000000, NULL, 1, NULL, '0000-00-00 00:00:00', '6287838807981'),
(1017, 'SA`BAN', '198502052014021002', 41, 694, 10, 1009, '$2b$10$wXR0LEA8renY8owD0Je5ye90HHPb8FpLEd2pJKM3Oy2.cnoUG73VK', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(1018, 'ALHUTABRI KERTA WIJAYA', '197410052010011002', 42, 695, 10, 1010, '$2b$10$suPpXOuhLlk7NpLPZj2b/.1ZXRVtJ7mdTTLGqr2m3UsN71d4RTiZK', 'PERMITOR', 'https://dev.pringsewukab.go.id/foto/1718072363128.jpg', 0.000000, NULL, 1, NULL, '0000-00-00 00:00:00', '6287838807981'),
(1019, 'ROKHIMANUDIN', '196903232007011029', 42, 696, 9, 1010, '$2a$10$YztYCl/zX/PFMKrSUGRT2eAvQm2Zbk0EvrBocdNPm7asfHqetwRbK', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(1020, 'TRIA VIRGUS YUDIANADA', '196808201991031006', 42, 687, 12, 1010, '$2b$10$G8qfUMhS1yXQGcYAKQvsweLI0KKzQfbJfzSnpMOhYkWCDxk0KZRA2', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(1021, 'BAKTI HIDAYAT', '198012032010011007', 42, 698, 10, 1010, '$2a$10$0c8t9SHayIgknwUcLeYsC.3enu9oQOVM/nK9R232nb9Go4aRQjzoK', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(1022, 'KUSUMARIYADI', '196809132007011023', 42, 699, 7, 1010, '$2a$10$wy3VKuhgbnf2UQQJ7671/eHUX4OTPWsIpnCDM3wd/pQxD9z8IETMa', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(1023, 'ROHMAT', '197608092014071002', 42, 700, 2, 1010, '$2b$10$Ua/AR9hIA9rTkHqdpLCRPeJ8IZcVxomQ5kxiDX4UEi7xUvMHIzEu.', 'USER', 'https://dev.pringsewukab.go.id/foto/1670805655946.jpg', 0.000000, NULL, 1, NULL, '0000-00-00 00:00:00', '6287838807981'),
(1024, 'SUTRISNO', '196801201991021001', 43, 701, 12, 1011, '$2b$10$IV8meFLIIiUffJRlm7n7z.RkgXrSTSY85X5Q84DTozj41rbF.sGhy', 'PERMITOR', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(1025, 'NURDAILA', '196808101992032008', 43, 702, 12, 1011, '$2b$10$NP68tr1zetdw/ENjnOWO3eF4peX37VYqcEXS.p3byKYPhyIX8.rFi', 'USER', 'https://dev.pringsewukab.go.id/foto/1665990466792.jpg', 0.000000, NULL, 1, NULL, '0000-00-00 00:00:00', '6287838807981'),
(1026, 'DEWI ROSITA', '197703252009022004', 43, 703, 11, 1011, '$2a$10$Kh8dMiXxPjWd3VjZGQtmwunVXqtmrR/OiHn07Ci7b8K/sQsvYRigC', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(1027, 'FITRI MARDHALENA', '198507072011012013', 43, 704, 12, 1011, '$2a$10$omgS0i3gnCRwUeg8Vl6BvefZvdUQJ8AErAs8v.6OBCrC2x9uV88ze', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(1028, 'SYAMSUL HAKIM', '196805041990031008', 43, 705, 11, 1011, '$2b$10$PnUIUC6HRFHycKB6n9lsJu7P4x57qgdcuUh2hfEtT3uR7VBZsmWYK', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(1029, 'DASWATI', '197506041998032008', 43, 706, 10, 1011, '$2b$10$onN70ocyD2LWI8oGQUhzpOF8dRHLLcPw/aXFr0OYftiIXxOh9j1Iq', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(1030, 'ANGGA DEWANTARA', '198703072011011003', 44, 707, 11, 1012, '$2b$10$tnqEPA3jD0NFTZM6R6sO6e8XZIjEIX06keRiNAP0441oabsOaTr7q', 'PERMITOR', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(1031, 'SAILENDRA LUBIS', '197706262010011022', 44, 708, 11, 1012, '$2a$10$/gOfFBwcfUB5ySAyd1aTzeHzUHE2fOQ7L5Fzy4T44kgla/BO2HEbC', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(1032, 'RISVANANI', '198012082010012007', 44, 709, 10, 1012, '$2b$10$QSQJoDDd0dgOdQq0hPn39eFT6DBwpOC6QY1BTOEfOk6vE5nyLIJHW', 'USER', 'https://dev.pringsewukab.go.id/foto/1711501575918.jpg', 0.000000, NULL, 1, NULL, '0000-00-00 00:00:00', '6287838807981'),
(1033, 'ROBAITI', '197405021997032003', 44, 710, 12, 1012, '$2b$10$.K.B/hnbCymzJ6R6kvFz8us5p.pENrtJVYZFV6baVYdfbBnjWP/ua', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(1034, 'SALMINAH', '196804201990032010', 45, 711, 12, 1013, '$2a$10$HCqQOOgXRGou2igs1pFHHO4Xp44FI7tlLwEJn5PWOOjvm9hSt8ZjS', 'PERMITOR', 'https://dev.pringsewukab.go.id/foto/1671755114152.jpg', 0.000000, NULL, 1, NULL, '0000-00-00 00:00:00', '6287838807981'),
(1035, 'KARSINAH', '197901102011012001', 45, 712, 11, 1013, '$2b$10$3BZHBXNPqJAQuQc/7xE1xe.PS2GHs5RC8NuiFg/okmuPM9n5uOj/2', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(1036, 'DIKA DWIAJI', '197812312006041058', 45, 713, 10, 1013, '$2a$10$kstQMCUqMDMlnMvGhg5MO.wvYRlGQ697NqJNgLfA/Euxq/a6yQepa', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(1037, 'AMRULLAH', '197503032000031003', 45, 714, 12, 1013, '$2b$10$TU8DyakvVjSJAFcm43XNgOZ72FIOTnLmNYHxv29q/0EUvozL99WPm', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(1038, 'ZALDI', '196512311986031103', 45, 715, 10, 1013, '$2a$10$7KGrlNmH8ZLHaP1qqsvBluJ93HcjK88YfH1sjOqNtLvKFg52t.tYO', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(1039, 'SUPRIATIN', '198401032010012027', 45, 716, 7, 1013, '$2a$10$nygZCIqU0/h3/9MnkBVgDuNnotXxU3LKOgpv9/8K37qKvT6IeaMP.', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(1040, 'SYAHRONI AZANI', '198307232009021003', 9, 511, 8, 0, '$2a$10$9AnfudTgDgdpP.rPSi2nt.yh40VTo1h0ijWjEDGWx4qiBc9EkWYb.', 'USER', 'https://dev.pringsewukab.go.id/foto/1666247238951.jpg', 0.000000, NULL, 1, NULL, '0000-00-00 00:00:00', '6287838807981'),
(1041, 'RAHMAN MAULANA', '198811052011011003', 11, 717, 10, 0, '$2a$10$KJqvCh3mLi98y9R3Qav87uTiJgaGL22As.NYFVxGdv6GnaZOTy2qC', 'USER', 'https://dev.pringsewukab.go.id/foto/1665991366664.jpg', 0.000000, NULL, 1, NULL, '0000-00-00 00:00:00', '6287838807981'),
(1042, 'MUNAWARDI', '197712152014071001', 11, 217, 10, 0, '$2a$10$Wo2Pok6bRpSzkW0EJJykPOgY3hR20qBUzodWjDPNJmUa1geZNs58S', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(1043, 'FRIA APRIS PRATAMA', '198404202010011018', 23, 681, 11, 249, '$2a$10$.48rG45TYaSKTBnC3bEBqOh7i1tNhhcI.Aurz7mdI62HcUpgJdXD6', 'USER', 'https://dev.pringsewukab.go.id/foto/1670215015071.jpg', 0.000000, NULL, 1, NULL, '0000-00-00 00:00:00', '6287838807981'),
(1044, 'SUMARDI', '196711161988071001', 10, 184, 7, 179, '$2a$10$YK9pgLG3TQjpUPkHh88rZ.PDFVur.MD4hpALBC9crls0i8KBUn8W2', 'USER', 'https://dev.pringsewukab.go.id/foto/1705279677200.jpg', 0.000000, NULL, 1, NULL, '0000-00-00 00:00:00', '6287838807981'),
(1045, 'MAHRUR ', '196808011988071001', 10, 719, 4, 179, '$2a$10$8fWU7VA6nyvR/.3mMrEIkeunZM8k9b5AETIYKvgJd8/7e2whTOu4G', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(1047, 'YOGI PARGANA', '196803181988071001', 10, 719, 5, 179, 'a', 'USER', NULL, 0.000000, NULL, 0, NULL, NULL, '6287838807981'),
(1048, 'PONIDI', '196412051984031002', 10, 719, 9, 179, 'a', 'USER', NULL, 0.000000, NULL, 0, NULL, NULL, '6287838807981'),
(1049, 'PARDIYO', '197005121993081002', 10, 719, 7, 179, 'a', 'USER', NULL, 0.000000, NULL, 0, NULL, NULL, '6287838807981'),
(1050, 'SUMARNO', '196704021991031005', 10, 719, 7, 179, 'a', 'USER', NULL, 0.000000, NULL, 0, NULL, NULL, '6287838807981'),
(1051, 'MUSLIHIN', '198303152014071002', 10, 719, 3, 179, '$2a$10$hb8Sg68ErD0T5jksP24L6OtMHNwlOoTUJlf.yh0k0MeqX3AuxpvBS', 'USER', 'https://dev.pringsewukab.go.id/foto/1704155053191.jpg', 0.000000, NULL, 1, NULL, '0000-00-00 00:00:00', '6287838807981'),
(1052, 'SUMARDI', '196812051988071001', 10, 719, 8, 179, '$2a$10$JzEQTvjLwVRxyogt9L3/9.1zy2PeMAym.p0UYbcmwKEEkK83dZJti', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(1053, 'NGADINO', '196512101988071003', 10, 719, 5, 179, 'a', 'USER', NULL, 0.000000, NULL, 0, NULL, NULL, '6287838807981'),
(1054, 'RUSLAN', '196701121987051001', 10, 719, 8, 179, 'a', 'USER', NULL, 0.000000, NULL, 0, NULL, NULL, '6287838807981'),
(1055, 'BAMBANG SUKOCO', '196707061986031003', 10, 719, 5, 179, 'a', 'USER', NULL, 0.000000, NULL, 0, NULL, NULL, '6287838807981'),
(1056, 'EDI SUDARYANTO', '196803271991031003', 10, 719, 8, 179, 'a', 'USER', NULL, 0.000000, NULL, 0, NULL, NULL, '6287838807981'),
(1057, 'HUMAM MURFADLO', '196509191989011001', 10, 720, 13, 51, 'a', 'USER', NULL, 0.000000, NULL, 0, NULL, NULL, '6287838807981'),
(1058, 'MOH. YANI', '196510101989031015', 10, 720, 13, 51, 'a', 'USER', NULL, 0.000000, NULL, 0, NULL, NULL, '6287838807981'),
(1059, 'SITI RAHAYU', '196301302008012001', 10, 720, 11, 51, 'a', 'USER', NULL, 0.000000, NULL, 0, NULL, NULL, '6287838807981'),
(1060, 'MB  INTARTI', '196505111986032010', 10, 721, 12, 178, 'a', 'USER', NULL, 0.000000, NULL, 0, NULL, NULL, '6287838807981'),
(1061, 'IKHWANUL KHUSAINI', '197901012014071003', 10, 721, 10, 178, '$2a$10$9Mt8K4Jo2Fv2T13WNtxXIO/5SkBtlxVYXR1goJQgDz/3EFNRlVfYq', 'USER', 'https://dev.pringsewukab.go.id/foto/1704865492766.jpg', 0.000000, NULL, 1, NULL, '0000-00-00 00:00:00', '6287838807981'),
(1062, 'SIMAMBANG', '197107151998021002', 10, 721, 13, 178, '$2a$10$7VfqmRlzSzRsyMMtnrUR2O438K4omiyX/NkWOoDoM.0pCDHw1mvSq', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(1063, 'SRI RATIH', '197306102014072001', 10, 721, 10, 178, '$2a$10$hgi7cHRHxrRBdwerNonuYORZu6x0M9fUai//KEi9jyoW0K91vjBRe', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(1064, 'SUWANTI', '196504051986022002', 10, 722, 9, 178, 'a', 'USER', NULL, 0.000000, NULL, 0, NULL, NULL, '6287838807981'),
(1065, 'FARIDA ZULFIKAR', '196507271988032006', 10, 722, 10, 178, 'a', 'USER', NULL, 0.000000, NULL, 0, NULL, NULL, '6287838807981'),
(1066, 'SULASTRI', '196602021990032007', 10, 723, 10, 178, '$2a$10$au4A7BjUh6i4EtDzXyjb2Ozb5vCGEGl2VR8hfiOzyJsidfXbd6xUe', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(1067, 'MUGIONO', '196804081991031009', 10, 723, 9, 178, '$2a$10$0rGwuwMA76lF8fjRHgActeu.hGtxMoEVqD3iH7OTSgSCL3A6o0T6e', 'USER', 'https://dev.pringsewukab.go.id/foto/1694660106209.jpg', 0.000000, NULL, 1, NULL, '0000-00-00 00:00:00', '6287838807981'),
(1068, 'TARMIDI', '196611101994121003', 10, 723, 10, 178, 'a', 'USER', NULL, 0.000000, NULL, 0, NULL, NULL, '6287838807981'),
(1069, 'SUPRAYITNO', '196608251989011001', 10, 723, 10, 178, '$2a$10$OjvmVIYijGoJTOK2JuDFXOfMLQ9NJ5KjNBfAs8ZdJbuo3LOg60KNO', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(1070, 'SAIMIN', '0196804031990031006', 10, 723, 9, 178, '$2a$10$OO2jHWc1fu4qLFvsKNR9y.6MbLAbKaeC0wmMd6DrLKJTcVT6A1k7e', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(1071, 'KARMIATUN', '196412291989032005', 10, 723, 10, 178, 'a', 'USER', NULL, 0.000000, NULL, 0, NULL, NULL, '6287838807981'),
(1072, 'A. RAJI MUDA', '196509071989021004', 10, 723, 10, 178, '$2a$10$kYspBycgJaGJhWz4QOHYP.wR0aaf5sbawkhqs6WmphyI.1bxSu9OC', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(1073, 'SRI PURBOWATI', '197209181992032003', 10, 724, 10, 178, '$2a$10$UIkyqSrV7L.NwyC9eGN34.NvHIcfiRLMisqMRx6vADBd3QmnXuVFG', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(1075, 'ENI NUR\'AINI', '196702281989112001', 10, 724, 10, 178, '$2a$10$ft3AnvRoVk5JrclXM9XwY.NrGOdQaE4lJzXsdv3hLKBSsZoniMWtm', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(1076, 'SITI SUBARIYAH', '196607021990102001', 10, 724, 10, 178, '$2a$10$bUCnlpVnlGHoKYRmBM4BHepzB9/V.70eT5tCUfVKLJbrwlyDyUF9i', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(1077, 'NUR FAJAR PRIYANA', '196507271986031008', 10, 184, 10, 178, 'a', 'USER', NULL, 0.000000, NULL, 0, NULL, NULL, '6287838807981'),
(1078, 'SUMARNI', '196808251991022001', 10, 184, 10, 178, '$2a$10$sskBnvxwTBZ3/TB2XQ4FEO.DBsSX0UVTmCusBj09B896uyq0/9m4K', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(1079, 'SETIO EDI', '196903102014071001', 10, 184, 6, 178, '$2a$10$QhswTEHH9swzp4PvVXGLWOWgo8pL.AMPz.PJFQJUvEIY.syZP5GH6', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(1080, 'ENDRO MARTOYO', '197603012014071001', 10, 184, 6, 178, 'a', 'USER', NULL, 0.000000, NULL, 0, NULL, NULL, '6287838807981'),
(1081, 'SUTARMAN', '196610151994031006', 10, 184, 10, 178, '$2a$10$BEGb2/AWxfc9SPmvCPWq0u9D5DyGcbY4xHKgHdcV1S34Pg.MLHzKm', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(1082, 'RUSTI ASIH', '0196605151990102001', 10, 184, 10, 178, '$2a$10$7c9c9.UrZT/Bv9c6NTTp2./v4xwvAHCrtWDkV/M7JdWY9RdWmKE5W', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(1083, 'HASUDUNGAN MANULLANG', '196410201989011003', 10, 184, 10, 178, 'a', 'USER', NULL, 0.000000, NULL, 0, NULL, NULL, '6287838807981'),
(1084, 'SUHERMANTO', '196908011990031002', 10, 184, 6, 178, '$2a$10$plM4M1z5QggNzPajeAuIseMTDzZt3J0sB.HB/uZOg26aIPgYzvPV2', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(1085, 'SAINO', '196606252014071001', 10, 184, 5, 178, '$2a$10$MvkLoHGgn5jB1Xg1DQDhG.GgpWlH9KncLR3vA6xiMebicyEoi7fC2', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(1086, 'JUMADI', '196701012014071003', 10, 184, 6, 178, 'a', 'USER', NULL, 0.000000, NULL, 0, NULL, NULL, '6287838807981'),
(1087, 'ULIYANTO MUADI', '197807282014071001', 10, 184, 6, 178, '$2a$10$i9WrkQLaovCbmg9Wl/Uoguv.4S0jj5mVNB8DquIc31fgY9Mrv3/3O', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(1088, 'LILA HARTATI', '197509051998022001', 10, 182, 10, 178, '$2a$10$IJflLrxLJNeWs3.CZis6Xut80oLQv.VlVMOAmLLjuC7vC/YUowzRC', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(1089, 'LAMIJO', '196803132014071001', 10, 719, 4, 178, '$2a$10$gqMly1m0DkDTNHDLHozbqeD6KjwfMhnKRZbgEqNjp42RBsIJhIa1S', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(1090, 'RIVALDI ARNANDO KURNIAWAN', '199707032021081001', 4, 726, 9, 613, '$2a$10$NuPebxnDOtKA6x77/IqXaevR.9jf/x9ag9nUArbjXWLg6p04w/L/2', 'USER', 'https://dev.pringsewukab.go.id/foto/1704777669106.jpg', 0.000000, NULL, 1, NULL, '0000-00-00 00:00:00', '6287838807981'),
(1091, 'WATI', '198405152017052002', 11, 727, 8, 60, '$2a$10$/xlo1uzPkjLgc4Ci0bSJa.fhA2spP1QdhzEgejn7mPLdKpgC4RIOW', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(1092, 'SUHAENDI, SKM', '198102282011011004', 11, 728, 12, 206, '$2a$10$k.yMX478sScwYab2ogld0ebrABTZPTjkPQgTP9k6gR5.UJBWmemhC', 'USER', 'https://dev.pringsewukab.go.id/foto/1680067990534.jpg', 0.000000, NULL, 1, NULL, '0000-00-00 00:00:00', '6287838807981'),
(1093, 'INDRA GUNAWAN', '198111202009021002', 31, 729, 10, 763, 'a', 'USER', NULL, 0.000000, NULL, 0, NULL, NULL, '6287838807981'),
(1280, 'BAMBANG SUKOCO ', '196707061986031003', 10, 734, 5, 1274, 'a', 'USER', '', 0.000000, 0, 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '6287838807981'),
(1338, 'RIYANTO PAMUNGKAS', '111111', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-08-07 22:25:04', '2025-08-07 22:25:04', NULL),
(1693, 'ADELIA OCTAVIA', '199810142023022001', 17, 752, 9, 882, '$2a$10$vVI4ARCBEPkaqBgiJYHxHe24rTz2Y998bCm8mYxWA38ow23fj3DBW', 'USER', NULL, 0.000000, 0, 1, NULL, NULL, '6287838807981'),
(1694, 'ANBIYA RAMADIKA', '200001142023021002', 17, 751, 9, 883, '$2a$10$WDPal94c3K.QytbArqdaVeiT4YMsZvVAQ0Y4Ue6vXQdd29gKDXgdG', 'USER', NULL, 0.000000, 0, 1, NULL, NULL, '6287838807981'),
(1695, 'BAGAS MUKTI AJI PAMUNGKAS', '200007092023021001', 17, 751, 9, 884, '$2a$10$6F8C0iCLrRm1YmdoOXrulu90dwRpdvuZWbKYolbkXMI7Cgd3CPUK6', 'USER', NULL, 0.000000, 0, 1, NULL, NULL, '6287838807981'),
(1696, 'DYAH AYU RAHMAWATI', '200107242023022001', 17, 753, 7, 879, '$2a$10$hWLvlk5njua0.bdkZKLJSeTswlVFtb9jvHv1/F9nv051raUBiz8.O', 'USER', NULL, 0.000000, 0, 1, NULL, NULL, '6287838807981'),
(1697, 'IBNU WIBISONO', '200102242023021001', 17, 753, 7, 879, '$2a$10$YuD3p5LjqDSs8el31rg/ceCUkI22IyZIGk8pWb2DE92GkYM1XBAVK', 'USER', NULL, 0.000000, 0, 1, NULL, NULL, '6287838807981'),
(1698, 'TIAN HESTIARTO', '198710252011011002', 10, 754, 10, 56, '$2a$10$Y4xINa.dBZgO0JMNqZaLAeDU6BivutEGiWTL0V37VefzGh5xaDUp.', 'USER', NULL, 0.000000, 0, 1, NULL, NULL, '6287838807981'),
(1699, 'JAIRUS MANURUNG', '198809122014031002', 8, 526, 10, 139, '$2a$10$X4YHDHNsi71GZBg13W4uI.9uttdZWuc3CbdxyPH25g/mWahj.a/AK', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(1700, 'MELIA IRLISTINA', '198108172014032001', 20, 730, 12, 300, '$2a$10$lNdYbCbhMgVgzSF3STKFZe/9ngVD979NL0RnWQDTeeUctnGjmyAK2', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, NULL),
(1702, 'BERTA SRI DARMAYANTI', '198309262006042010', 21, 730, 12, 113, '$2a$10$z2T7uo0sP7u6q7k1GcHSbOXwZkBtopn6RPAsVDU1af0IgYQ3Z1GFe', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(6746, 'MAT HAPIPI', '197012312009061021', 37, 671, 7, 954, '$2a$10$w0i80/lqtlYl/UJX7nJqh.xKSb/YtgWjDxS032AM0iOpvQ5Ewvc5.', 'USER', NULL, 0.000000, 0, 1, NULL, NULL, '6287838807981'),
(6747, 'DANA MAHMUDA', '199709112021082001', 2, 1747, 9, NULL, '$2a$10$Gcgw256Hgxd38aQVIQx42O6MUHtoZmMsqPzHiJtSIsFkPtHWDyZB6', 'USER', 'https://dev.pringsewukab.go.id/foto/1683683703937.jpg', 0.000000, NULL, 1, NULL, '0000-00-00 00:00:00', '6287838807981'),
(6748, 'NAWANG WULAN', '197108152002122004', 12, 67, 13, 19, '$2a$10$37yOFbjjdeUxrBNC3aVkcedb0rSbrxe3wKX3YAegLzNH.cGeqS6RW', 'USER', NULL, 0.000000, 0, 1, NULL, NULL, '6287838807981'),
(8010, 'FITRIANA AHYAN', '198008202009022010', 21, 757, 12, 110, '$2a$10$UaM2NX2cAhIj7moXnjSRyu2kXTbsZViSdrgEdiJ.9egCNzycSudsO', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8011, 'AHMAD MIRZA S.SOS', '197005051994021003', 10, 1749, 12, 51, '$2a$10$hFNitB5r27KWdVvKJerDeOScYhD4qEdWQ7uqTzWhoG9TI9F/S9PvS', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8012, 'HIDAYATULLAH', '198007182009031001', 3, 1750, 13, 18, '$2a$10$9YgBxAYlQ562MU.PvmQk.uBcMbemXI7Ej5Xy9hoWIC9oNjt5f3Cqa', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8013, 'ANDY DENI SYAMSURYA', '197612202006041009', 20, 1751, 12, 160, '$2a$10$Q82omQ1xx2qOe8yLC51BI.fYU6.E3UhjB89wn1rJGnQR.z.geBarq', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8014, 'Dra. Titik Puji Lestari', '196702151994032005', 2, 3, 15, 0, 'a', 'USER', NULL, 0.000000, NULL, 0, NULL, NULL, '6287838807981'),
(8016, 'Astri Anggraeni Perwitasari', '199110082014032001', 11, 1753, 9, 60, '$2a$10$oq9amuJ8WOkOo.MtbZWPduJMP9.7NzwfeE57kWVdmDZLUFBIvYiNq', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8017, 'NANI PAHINI', '199209192015052001', 8, 524, 10, 774, '$2a$10$6E53eU9XVuIddHbLezpiGeRDAJ4VZupbw6Ou64z9c4jCKlUIt4I5e', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8018, 'PUTRI SYA\'FA NURARIBAH RUSLI', '200102252023082001', 28, 1754, 9, 138, '$2a$10$1R4PbRxI0Z99umBzoHR1iOAk5LQLjS1rSOZvsCEqWseCsa8Lp6QY2', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8019, 'HAFIZSYAH MUHANNAN NASUTION', '199906262023081001', 28, 1754, 9, 40, '$2a$10$loGTNAKOhq3iEOZVGTxKyeC9xECeD1K97TtBBL4v.XIwV4vUyCNfu', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8020, 'SYAHRENDRA AHMAD FARIS', '200107252023081001', 28, 1754, 9, 140, '$2a$10$q39sQre/KB4VYR/XBtZBce4jKChG.D42EkYDKkGHJ.ic198jKDHSW', 'USER', 'https://dev.pringsewukab.go.id/foto/1708653968457.jpg', 0.000000, NULL, 1, NULL, '0000-00-00 00:00:00', '6287838807981');
INSERT INTO `pegawai` (`id`, `nama_pegawai`, `nip_pegawai`, `opd_id`, `jabatan_id`, `pangkat_id`, `atasan_id`, `password`, `level`, `url_foto_pegawai`, `tukin`, `edited_by`, `first_time`, `created_at`, `updated_at`, `no_whatsapp`) VALUES
(8021, 'LARES CAHYADI', '198505052010011018', 23, 1755, 10, 120, '$2a$10$JV7w85D18RYpLR2XcY/zD.AHdIB5ZC4Csl0oY/DNEVWyKLI5.QhpS', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8022, 'ARDAN C', '197804132005011009', 31, 246, 12, 151, '$2a$10$3HGz8qZqdfxjeKYFZFGirer0N3LHwp4lFvrnjxMrfUnZHdq3IuXJW', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8023, 'HAFYS SANJAYA', '200004142022081001', 4, 1756, 9, 612, '$2a$10$Iqrhqw8TT.Ldib8axw9Nr.qeFHSCIdFg1SldP5nQuT8yKToBRh8k6', 'USER', 'https://dev.pringsewukab.go.id/foto/1709601385220.jpg', 0.000000, NULL, 1, NULL, '0000-00-00 00:00:00', '6287838807981'),
(8024, 'TITIN AMBARWATI', '198408152014032001', 8, 520, 8, 139, '$2a$10$rCM/rdWMBgLRumQxF7StrOSRHO97Vzyz4nNes2XJ73/8mdMEHOzFa', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8025, 'MUHAMMAD FAIZ BAMAZEZA', '200010072022081001', 5, 1757, 9, 322, '$2a$10$DtHysHzCaZFaZihMZKwElu.mK7NcucEI3DRL2doETOrWzScqhVi2C', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8026, 'FERIYANSYAH', '198102242010011008', 38, 616, 12, 166, '$2a$10$6NxGlAx52l2nOUDpQOXCDuS7cejKxbyvcYqW9g3tPC660ibLQ1cZ.', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8027, 'Doni Priyanto', '198212272012121001', 18, 1758, 11, 95, '$2a$10$Y.M4iNHr1Jl5gVAOxhnYo.r.juprL6w3ZY8hxkDsqZfxoMm31O4ha', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8028, 'NELIYANA', '198610102017042003', 11, 1759, 8, 60, '$2a$10$N.5pU2LuGxg5/9K0F79EceJ/skXeiqa8TFG3O2dS46kQePj8diGlK', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8029, 'Agus Prasetio', '19950826281223', 0, 18, 0, 170, '$2a$10$asC0gf3tnuhN/G3YmXgv5eAB/A5Ok3tYTCDL48GKKkNtezfT9lEIa', 'USER', NULL, 0.000000, NULL, 0, NULL, NULL, '6287838807981'),
(8030, 'SUNOTO', '197206252007011017', 38, 166, 14, 8030, '$2a$10$sMWWlTBFHGMMn5jCOqQ4ROXMAGvG5cKIYMUV.Hu6Y.rOBxAwJlZWS', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8131, 'IQBAL AZHAR ROMIDHAN', '199912232024021001', 17, 9, 9, 882, '$2a$10$WTGlFG2yHvVt5gPZdgZYmuib0PGSoQ.lo.mEQVVMWtBTWUxFH/HAO', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8132, 'ZAHRA ROITO HARAHAP', '200201242024022001', 17, 9, 7, 877, '$2a$10$bQhag9miT9zyeUARsChtROmfgBFb9bnEYysELV7ZDcN9qgzPUot9m', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8133, 'ADYATMA IVANDER ROHIM', '200112272024021001', 17, 9, 7, 90, '$2a$10$BAq8b1r23YVl7AJt/T9w5OsLyfkgquy0k3OVGA3UJzBY6w1Qgf2p6', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8134, 'Adib Hutama Putra', '199202042015031001', 11, 1760, 10, 61, '$2a$10$MfuDGylTQTO8UeAz8gCL9.dEt0i/hr0nR39AuZDmlCrDqZv8PpobG', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8135, 'Admin UPT Pengembangan Budidaya Ikan', 'admin_upt_budidaya', 40, 1, 1, 0, 'a', 'PERMITOR', NULL, 0.000000, NULL, 0, NULL, NULL, '6287838807981'),
(8136, 'A HAYUN RIFA\'I', '198506032024211009', 3, 1, 1, 737, '$2a$10$1eS2TM1uTPuZdZ5DaAC7V.swb0cpNrPibSfESPVlQZiPLah.5ToYu', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8137, 'PUJI ANGGRAINI, A.Md.', '198807082024212019', 18, 731, 14, 359, '$2a$10$ocGQ4DkPEScLgvzPmd/OL.FnGuzxtqLrtrWOB/YFKzRkSpvVVIcui', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8138, 'TONI, S.Kom', '198501212024211004', 18, 731, 14, 360, '$2a$10$2TMgrADxCZ.XjJdeHmOL8eFX7e45N9uD5fnb3xB2OBt5esLLhR4Sy', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8139, 'NOVA RIANA, S.T.', '197611022024212001', 18, 731, 14, 96, '$2a$10$dVx8oBsf.VFaX2FFmkaNHO7QnHIcnllurFjY2E01YQuS42vkAZCIe', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8140, 'BRYAN TIO LAZUARDI', '199412232024211009', 27, 1761, 9, 134, '$2a$10$FzMLZRZDMukavu8hlPGAaOXlQncEGNWKeGA7/OmdvFmZTuYhbRryu', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8141, 'NURADI ZULKARNAEN, A.Md.', '198502172024211005', 20, 854, 14, 151, '$2a$10$nvCr7913pdvcqEFNI.A9Uu15//qsXdWfnIU9zA95khbH0Y0DhHPyq', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8142, 'FERLYANDI, S.Kom', '199510052024211014', 20, 854, 14, 151, '$2a$10$uIpd96HNWxEjeYFm2ft8A.XPivsL8.Amaj8IzTuu.DTpzzoV7nLiS', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8143, 'Catur Hendra Susiyanto', '197707222009021006', 37, 731, 14, 163, '$2a$10$PJtF3MDjN/2xRkQo7QcUUujvh0LvuE1EODdAlpiMlJNuLeaIU0dVi', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8144, 'ADI SUTANTO, S.Kom.', '198702152024211007', 5, 802, 0, 28, '$2a$10$Nhj7kx7YoiWitkH.KUQzT.XCr3E2QzVnxXG/rNOMujxOr5jd0oHW2', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8145, 'AGUNG FAHRUDDIN, S.E.', '198808262024211007', 26, 826, 0, NULL, '$2a$10$DNaWVJlW.XqLh8toB70vN.UGF8gRrilcsDnV6XOxee8X3ezsSGDFK', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8146, 'ANDRIA DONY ASTRA', '199112082024211008', 29, 829, 0, NULL, '$2a$10$2cvDJU2asODg4thCRRCfau0JukoU8t3ZxhEcgiYtmx6tUcmA0I.0m', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8147, 'ANGGA WIRAYUDA', '199103122024211007', 29, 829, 0, NULL, '$2a$10$VeMVSwLtcVV2YpiTKO05Cev1gyq84vAhdhd2oZQtD1poml8KwIzwe', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8148, 'ARI HANIATUL EVAYANTI, S.E.', '198302182024212004', 7, 804, 0, NULL, '$2a$10$/UMyNoPZbztT5hDgphYjHulC9B0LO4qhlzd9OCd/YBTzlqE5TkEum', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8149, 'DEBI SAPUTRA, S.H.', '198512302024211006', 28, 828, 0, NULL, '$2a$10$qHGViJsl57UYmUWCFkbrkeGTOxdXju8j6yZiUBz/otM3tiUBAi7w6', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8150, 'DEKI SUMARLY, S.Kom.', '198509272024211004', 14, 809, 0, NULL, '$2a$10$9rMBNCZmKmtPghxEtWI1MOij5PXR3F7QMzYVHy8m1K.73bZhFygzC', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8151, 'DENY RACHMAT HALEX, S.T.', '198805012024211009', 16, 813, 0, NULL, '$2a$10$lUWUT7tTC9awRdUAMg6gAOWonbGC04/kjev479JWJisNDb63bUwf6', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8152, 'DIMAS SUTRISNO, A.Md.', '199707112024211008', 25, 822, 0, NULL, '$2a$10$v57oHlXsvpwl/JAyRm8FGuOT8MSxgkcaYA0iEsiIermeYn.6Lf0ei', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8153, 'EMILIA APRIYANTI, S.Si.', '199304182024212020', 23, 821, 0, NULL, '$2a$10$xIqIB683NSQcejkoDxa6vObPHcl5DxB2k.YjuWEEDOuY.zzgthDbe', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8154, 'FENDI RAHMADI', '198507222024211004', 29, 829, 0, NULL, '$2a$10$ssgLmibCgaLYSjijeFgmwe2AwNIgGYqtxqW3cR/IVtp.b7cAkj3bm', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8155, 'FERLIKA FILIYANTO, A.Md.', '198303122024211004', 29, 829, 0, NULL, '$2a$10$XhaoL2FV3FWIFp/NOdtBMebVxrXoHvJBED9pQGguXg3GMMAmWTOYa', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8156, 'HIDAYATUL MAHFIROH, A.Md.', '198910262024212007', 8, 805, 0, NULL, '$2a$10$VErO8C9AuHDEN5e.tuF3.uMlEUd0mX9nqSZtpqdo3z4NK/1Pj33iO', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8157, 'HIJRAH ELISA, S.E.', '198612272024212009', 8, 805, 0, NULL, '$2a$10$jVnfSKuwsDoEhAfKv4OG/.OE7j7Nc3dfimynIoTOOO9AGCSW51Hrq', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8158, 'INDAH SUKESTI, A.Md.', '199807122024212020', 14, 809, 0, NULL, '$2a$10$zzxdmiAHbsJL38MJqqOlA.h3mC3lI27y8L.sddeHR/ymSkPkOCcJ2', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8159, 'INDRA RIADI SAPUTRA', '199210032024211012', 29, 829, 0, NULL, '$2a$10$LDTn7xCrJ38upei0hvY2uuvXEJyqLfXk8wWmHsJVdV6Poq.Sh7Y2i', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8160, 'MAKMUN ROSYID', '199108052024211006', 29, 829, 0, NULL, '$2a$10$P80h2tFCe4z4uPTXjt.Ilej2.eGu2Q6TnfvecprVrS9VAIzvJAwza', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8161, 'MIFTAHUL HUDA, S.Kom.', '198808172024211016', 2, 800, 0, NULL, '$2a$10$Qb8caph9nrk5G8AV6TIyYu.emnxGfEY.pAUDmyR1ikkYbTxqDfI8C', 'USER', NULL, 0.000000, NULL, 1, NULL, '0000-00-00 00:00:00', '6287838807981'),
(8162, 'NINA OKTARINI, A.Md.', '198410102024212017', 14, 809, 0, NULL, '$2a$10$7dw7GAtsrfgw7/IGdA1Es.9vOTs.tYnpeFa/XTNAFnlfXSl/OY7KW', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8163, 'NOVI HERMANTO, S.Kom.', '199011092024211008', 25, 822, 0, NULL, '$2a$10$Qd1gOYV8Vx2HcnVM9ZSPx.RLtFBATSQ0Ju7jMmTU.6sfjW2xvFaQa', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8164, 'OCTAVIANTO', '198510152024211015', 29, 829, 0, NULL, '$2a$10$skMXeudHQlvTHbYpnTaMuO3oeWF96ftQS1Wm2S1fThvhVhimDbCke', 'USER', 'https://dev.pringsewukab.go.id/foto/1725945359474.jpg', 0.000000, NULL, 1, NULL, '0000-00-00 00:00:00', '6287838807981'),
(8165, 'REFLI JANU EX WANTORO, A.Md.', '198703232024211004', 26, 826, 0, NULL, '$2a$10$TaRYmmT9JZE/1YWVW9G9LODQGnI0vkiQwaaN70fnR1b08H2TXBJoO', 'USER', 'https://dev.pringsewukab.go.id/foto/1726563715824.jpg', 0.000000, NULL, 1, NULL, '0000-00-00 00:00:00', '6287838807981'),
(8166, 'RIDHO ANZIL IRAWAN', '199204282024211009', 29, 829, 0, NULL, '$2a$10$0oVCaDVhDO8BkhCRGYjb3.csHDrTbu8q8YP75co0ff9GUIKqSe1uW', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8167, 'RIZKI PURWANINGRUM, A.Md.', '199208032024212013', 21, 819, 0, NULL, '$2a$10$NKulGIKTpl6XU8vpeQbwRetSWAJwL4aMwAVJIXc78tFR8T3BBjT2O', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8168, 'SARKAWI, S.Kom.', '197902162024211001', 14, 809, 0, NULL, '$2a$10$M2PABxA5IfhN.0sWcXWOmuxBxhl2cUnJeR0RVjN7Xhp3D5LwDqQyW', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8169, 'SRI SUSANTI, S.I.Pust.', '198806012024212013', 26, 826, 0, NULL, '$2a$10$5STkMGSb1U9uj1xCQL77neMiwvlsOdlO4Bwr1MfpiCvwKK1nWbOwK', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8170, 'SUDIRMAN', '199405142024211009', 29, 829, 0, NULL, '$2a$10$mHPTN1H1sxeHPMTqDwH9P.cE6BUiKUZ9pQzlCdprACzZ15kZjAP/y', 'USER', 'https://dev.pringsewukab.go.id/foto/1721188908646.jpg', 0.000000, NULL, 1, NULL, '0000-00-00 00:00:00', '6287838807981'),
(8171, 'TRI SETIO WIDODO, S.P.', '199101172024211009', 21, 819, 0, NULL, '$2a$10$NZfutIgCRVG8XEss52VtQOqHamiYDo7T7b8jnCqhnP3pvdEB9CNbG', 'USER', 'https://dev.pringsewukab.go.id/foto/1718066900854.jpg', 0.000000, NULL, 1, NULL, '0000-00-00 00:00:00', '6287838807981'),
(8172, 'WILDAN BUDI PRAKOSO, S.Kom.', '199411292024211010', 8, 805, 0, NULL, '$2a$10$8BJAx7NJ0/k4qZ5WZUpP9.JATe1jxSpXIcl/4KHcWIjh1crJaiPcq', 'USER', 'https://dev.pringsewukab.go.id/foto/1718333172573.jpg', 0.000000, NULL, 1, NULL, '0000-00-00 00:00:00', '6287838807981'),
(8173, 'YODI SEFTIWAN, S.Kom.', '199209142024211009', 6, 803, 0, NULL, '$2a$10$6AmtMEtukYMbKwrWJbtHDeSuQ0tir/vGVKWVB/TCTnVigsD2xDxyG', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8174, 'YOHANA RETNO VINSANA DEWI, S.A.B.', '198806102024212021', 12, 808, 0, NULL, '$2a$10$muniWTRClaDVDHHM5A3P/uYlZ53XP1m5dOrrqL/la/vM9LDF6nklO', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8175, 'AGUS WASONO,A.Md', '197408142021211001', 21, 731, 14, 107, '$2a$10$CqGO0fP46Vrrfq29QmwFBOyi9aNIPZGqRkFLW9f0gM1Q2Q.qvfdpq', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8176, 'ANDI PRAYOGA , S.P', '198712162021211001', 21, 731, 14, 107, '$2a$10$6iO6Qslpr2Wp0Y52e58rD.AVXmLPZIHTu7jAbj4CO8EXoc0n1IdLS', 'USER', 'https://dev.pringsewukab.go.id/foto/1753060717347.jpg', 0.000000, NULL, 1, NULL, '0000-00-00 00:00:00', '6287838807981'),
(8177, 'ARIA RAMA,S.P', '198108022021211003', 21, 731, 14, 107, '$2a$10$J2jM9bM7HGcGpnXd0CDtKum4y0zAxihzEGbfEn32TmfGiBU4oF6mi', 'USER', NULL, 0.000000, NULL, 1, NULL, '0000-00-00 00:00:00', '6287838807981'),
(8178, 'BASIRAH,S.P', '197105072021212002', 21, 731, 14, 107, 'a', 'USER', NULL, 0.000000, NULL, 0, NULL, NULL, '6287838807981'),
(8179, 'BRIAN ARISTONA PRATANA, S.Pt', '199201052021211001', 21, 731, 14, 107, 'a', 'USER', NULL, 0.000000, NULL, 0, NULL, NULL, '6287838807981'),
(8180, 'BUDI WAHYUNI,S.P', '197502282021212001', 21, 731, 14, 107, '$2a$10$HcmUTe45nn4Z5YN0j4TEeu0MYA3Fsb2ZzrX7.AvwWXTcEk4qj0k.2', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8181, 'FATKHUR ROHIM,S.P', '197706032021211002', 21, 731, 14, 107, '$2a$10$UJOi3uxbXlcFBHDuEk66mez41K6dpDHrTqfzF7gOm2KTrrV4yRRMC', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8182, 'FEMI HENDRIYANTO,A.Md', '198111212021211003', 21, 731, 14, 107, '$2a$10$/9hYOugPN/a9SSHd0kzHUe9kB7Ybz0HuCujUF4yKB3Sk8gmR6ljca', 'USER', 'https://dev.pringsewukab.go.id/foto/1720417857088.jpg', 0.000000, NULL, 1, NULL, '0000-00-00 00:00:00', '6287838807981'),
(8183, 'INDRASTI WIDIANINGRUM,S.P', '198106222021212001', 21, 731, 14, 107, '$2a$10$6kb6U3F29IljD2QJf53IPu.cp5YYRBHTj0SN0Rc5uKbL5cwHW87/q', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8184, 'KASBIYAH, S.P', '197303102021212004', 21, 731, 14, 107, '$2a$10$UDTG8cJz3JF1194cDNte4OHYUm7iJZmSdjhGPiTZ1Hzng/wzHoqJ6', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8185, 'LINA INDRAWASIH,S.Pt', '197902232021212004', 21, 731, 14, 107, '$2a$10$2D8pM9YgJePas/BV051V7OC1gl3SNGgc5XRFX4eYBhB/vynsftY3u', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8186, 'MARTONO.S.P', '198503142021211001', 21, 731, 14, 107, '$2a$10$/WJeTO9yDAfDYEVnUMzXhOHKGqhAOg4dtBV3SZav1SJsaN56OlPhG', 'USER', 'https://dev.pringsewukab.go.id/foto/1720685462679.jpg', 0.000000, NULL, 1, NULL, '0000-00-00 00:00:00', '6287838807981'),
(8187, 'MARYUNI.SP', '197002042021212001', 21, 731, 14, 107, '$2a$10$X049SUxWQEdAW7nWgXnKneohPQbxiI22alkamFREuLPvxUyWFpKHa', 'USER', 'https://dev.pringsewukab.go.id/foto/1720420924634.jpg', 0.000000, NULL, 1, NULL, '0000-00-00 00:00:00', '6287838807981'),
(8188, 'MASRURI,A.Md', '198310132021211001', 21, 731, 14, 107, '$2a$10$rIp9xTChArBNmseujMNuLuQ7Nv/6gmr1VLagcWI79xm.GQPgEdgsC', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8189, 'MUHAMAD TAUFIK ZAINAL, S.Pt', '198606272021211001', 21, 731, 14, 107, '$2a$10$2lYWs1p7eD1y54vcq63rIOU2AA4ml7L.xNqdxn.vt2vZd/JGG/P62', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8190, 'NURPALINA,S.T.P,M.Tr.P', '197511242021212002', 21, 731, 14, 107, '$2a$10$CZvrU2e6xXxi5mmBSbyTOOtq8RddlhX7p/t5QeRFk6fetwm289Qb2', 'USER', NULL, 0.000000, NULL, 1, NULL, '0000-00-00 00:00:00', '6287838807981'),
(8191, 'NURSITO', '197108302021211001', 21, 731, 14, 107, '$2a$10$rhr4CqE.NEizz9RzsNU7ruGxMUaFFclqpWTwQ72ZhkayTHUvr9.nm', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8192, 'PETRUS JOKO PRAYITNO', '196708152021211002', 21, 731, 14, 107, '$2a$10$ny6qZBkweIjEmwRAeItHa.rrJkBk6lD80JkjPA6BDRWAZEcWm0GfS', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8193, 'RIYADI,A.Md', '197312032021211002', 21, 731, 14, 107, '$2a$10$BQhmTnGPCdCLmgmhXSj5v.xrzS5KQVp.g/RRbVkI56d0DCJm6EJIG', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8194, 'SARNINGSIH,S.P', '196902202021212001', 21, 731, 14, 107, '$2a$10$dhOCZNoW.ylax7e39mChJeENk/u1RvyN0rUlgX.meWNhZAbC7a7xS', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8195, 'SITI NURBAYA,S.P', '197606102021212002', 21, 731, 14, 107, '$2a$10$q7I2ka44sA56L1CZQorCrOa6OxCPb9b7cLHhzKrvJunhVE9/cpgKK', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8196, 'SOLIHIN.S.P', '197202082021211001', 21, 731, 14, 107, '$2a$10$tRB510Mr7/qbVoeuEpUe3On.MS4zrL5eFbRupRNJvlnihytTYj48a', 'USER', NULL, 0.000000, NULL, 1, NULL, '0000-00-00 00:00:00', '6287838807981'),
(8197, 'SUGENG RIYADI,.S.P', '197309252021211002', 21, 731, 14, 107, '$2a$10$U2eCr3mguLak9x.SQ5kAwOKC1GokTdsniF4WZjUDXCov5d8ZcR8MC', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8198, 'SUNARDI', '197402152021211001', 21, 731, 14, 107, '$2a$10$s/YNrx4PrwwbcLqObISeW.Mk6vLDhXefUM.5AR00rHd.B16mmdpj2', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8199, 'SUWARTO,A.Md', '196806012021211001', 21, 731, 14, 107, '$2a$10$uUzoNtintDybTfCN.keQT.IFJ0kK5BtmMiiU5CKrJD9GA6sl497Oa', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8200, 'TEGUH SUSANTO,S.P', '198106052021211002', 21, 731, 14, 107, '$2a$10$/T4pAJisaU/dBSIF.tsJKuQyUUed77qwahSwY3tt8LoHSCZTx0DjS', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8201, 'TRI WAHYUNINGSIH,A.Md', '197007052021212005', 21, 731, 14, 107, '$2a$10$3QGRxDECEyLAxYRi5i.m6.R4dqBMsmimGSzxunxjrPcF.eH00Lh2K', 'USER', NULL, 0.000000, NULL, 1, NULL, '0000-00-00 00:00:00', '6287838807981'),
(8202, 'YULI WULANDARI,S.P', '197907102021212005', 21, 731, 14, 107, '$2a$10$eu8LRrHv6lad9qln8e/xrOV3taZPDR4poVX1q7Fm4XC9xDT3kUwq.', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8205, 'Sanuri', '197204212010011004', 39, 314, 7, 167, '$2a$10$BtDI/YNqnddBfFzRu7wQiOAYUFuSiebBwGKcPIEQBI2C8HNRWBYha', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8206, 'FAJAR WAHYUDI', '198212232002121005', 12, 756, 13, 66, '$2a$10$6QABHGk54XgEEAyGOH996e2GHpQdA7u12510i7.q1mtmGpJpBn6X6', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8207, 'ABRIAN RAFSANJANI', '199606062018081002', 5, 1762, 10, 28, '$2a$10$D1tBmq8K0WAPezjXs3jID.gPJAH.W/Ibc.7qSsx.upxIKbOHxwMu.', 'USER', NULL, 0.000000, 0, 1, NULL, NULL, '6287838807981'),
(8208, 'ALONA PRICILIA ERTON', '198401192002122002', 35, 670, 12, 961, '$2a$10$l5b4jeJPos/gpMGPyh.gC.GMER0gs5EN6q8c5MQvDtHVCGQmN.wn6', 'USER', NULL, 0.000000, 0, 1, NULL, NULL, '6287838807981'),
(8209, 'Aji Prasetia Nugroho', '200008042024091001', 4, 428, 9, 21, '$2a$10$LbAcfCK86CND/Oa4QkHb3eE4n8Mj6ig4McLdUgOIKPHDFZLZsMS4i', 'USER', NULL, 0.000000, 0, 1, NULL, NULL, '6287838807981'),
(8210, 'Ismail Nahri Alfajri', '198209282010011016', 7, 305, 305, 35, '$2a$10$h/HUD6X5nBrEnfkE9GZudePl.bhU9CHi8dkx7G65p6Cx9uxi1bVoC', 'USER', 'https://dev.pringsewukab.go.id/foto/1729145259098.jpg', 0.000000, 0, 1, NULL, '0000-00-00 00:00:00', '6287838807981'),
(8211, 'Yoce Shintialina', '198003302008042001', 4, 351, 13, 20, '$2a$10$.zJqwYQu./eU47loHgQXh.EBcGflGQFMfO06r5fNClGdALUKK72RO', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8212, 'ANTONIUS WAHYU TRI HANDOKO, S.K.M.', '197811021998031002', 11, 1763, 12, 59, '$2a$10$mFwyeuPx6Zel7YvlBqGSwejAVef1Aw.VBUIMfkcOpgU.o0iEC5/4G', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8213, 'Irfan Ahmad Ghozali', '200006132023081002', 2, 731, 9, 0, '$2a$10$58z/gkHI8AAUng6jiO1P1uOSXvbM/NB6JwkTQscign7cPNtFEEjfy', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8214, 'YULI KURNIAWATI', '199007292015032008', 11, 1764, 9, 204, '$2a$10$EH0qIV2WzTP1sRihd5V32O2NDjThQZY7pJWSemc/HrzHFuKQIIycy', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8215, 'CATUR SUWEDI', '198606142009021006', 11, 1765, 11, 61, '$2a$10$wR0woK05PZlBO812H/hhreJPareJnfgrkbnVpZKIFPG1qGF8giPEm', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8216, 'Yosi Handayani', '198308092008042001', 13, 1766, 14, 67, '$2a$10$dlM68DI6Qz/8rIe6yHz17OqC3xhR8c48R4b4hi47iUG6cxcn6Ujeq', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8217, 'Dewi Mustika', '198802092017042002', 13, 1767, 14, 67, '$2a$10$h9aQRdPECWoOYV8nJ6MgperKMsHyN2cxVtVmQZEeBlvqBSm.cpN6q', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8218, 'Rahma Novita Angraini', '199011012020122018', 11, 1768, 14, 57, '$2a$10$6d4K06boL2.n3evR6TKHzesnQ7339/lcZqxA3hWUyDWHYol8smfTC', 'USER', NULL, 0.000000, NULL, 1, NULL, '0000-00-00 00:00:00', '6287838807981'),
(8219, 'Firania Debby Prabhasari', '199504132018082002', 7, 1766, 9, 45, '$2a$10$ABeTBQqzprVhpmZRde6bNewUrPnT3m6g51YF/6itXjkH07ak16Ipa', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8220, 'RIZKI PUTRI LESTARI', '200307072025032001', 17, 1767, 7, 877, '$2a$10$VezfLf.1iQOYMIllUvjysu.Tn4G1nVjwRhZOWyN02UG2NyYXRndQi', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8221, 'ADITYA RIZKI', '200206102025031001', 17, 1767, 9, 882, '$2a$10$IEzE9hroz0Cs8k/t/NehMunQzH/XuMoyx9Ks9sU2OJGxikXas4QP.', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8222, 'EZHA ALVIONITA', '199904172025032001', 17, 1767, 9, 880, '$2a$10$yvpY6l5glTcKyVJUhn8w3uH/vpC6EMdZxDmQK/UANYJpFSf1Cw1Ky', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8223, 'DITHA AULIA SYAHARANI', '200305192025032001', 17, 1767, 7, 90, '$2a$10$JDvu4X5jtAa8hcdPQNsZmO4sFQLxuoJe5vcd/mHU2oB5gRSjlpToy', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8234, 'ABDUL HAMID', '199210282025041002', 3, 266, 9, 17, '$2a$10$Ps2KeQFRFH/Pd/nxIm17ZOu9fU0omermTUfxcvV3Ka28YghS6iMBW', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8235, 'MALINDA FIRDA ZULAIHA', '199607082025042002', 3, 266, 9, 17, '$2a$10$bKrsXirT72RnkjIxkSRaKOcY0UtSf9bZ8PP6MfNimAUdtiyIA0X.K', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8236, 'DINA ARIYANI', '199604272025042001', 3, 266, 9, 18, '$2a$10$RSlMw8xkXWtfzo.dNF4VoOqmYQIqsYo3k.ToTS6vqvCZfcoc0u2NO', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8237, 'M. FADLI ROBBI', '199207132025041002', 3, 266, 9, 19, '$2a$10$xwPsbGkvJMD2HKckXf82DeF1L.1fVrpoWeP54otE3LfJIivpH8LHy', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8238, 'Mutiara', '199901162025042003', 20, 855, 9, 0, 'a', 'USER', NULL, 0.000000, NULL, 0, NULL, NULL, '6287838807981'),
(8239, 'Lulu Vania Nariswari', '200201302025042003', 20, 855, 9, 0, '$2a$10$Gd..1phHn4nRKkQ7.j4wyOR5EdAwDolI.fo2Fed500OulW3.qPOzW', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8240, 'Andhika fachri', '200012202025041002', 20, 854, 9, 0, '$2a$10$dN5npr4ffxRvHi00Wrcqb.wpcNDeF5RRqKnbCqkMT.3FZKH2/ipnO', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8241, 'Roki Fauzi', '199708032025041001', 20, 854, 9, 0, '$2a$10$o8y0wT5YhLFkPRzlEJT1m.EzwsNA9k3Vc.wO9USZk1Ld5baKhjmZe', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8242, 'Deden Gusti Laksana', '199608252025041001', 22, 466, 9, 0, '$2a$10$ceTIV3DQHaK.toDkVCK5BO37fwTZ/6CF9GrOcgdXJQuYfebBs49tG', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8243, 'Adelina Damayanti', '200104242025042007', 22, 466, 9, 0, '$2a$10$kAoEB1r1Q6vuzXGqb1yHFu3Z.p1tqMEtDmT9fdg3XOzIzB2RGYoB6', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8244, 'Muhammad Hafidz ', '199910312025041003', 21, 397, 7, 0, '$2a$10$.pUdu82ZAzt1EogrlsY4RuMKga2zTTbNRuSq2XhPAv1YkZeow5Oee', 'USER', 'https://dev.pringsewukab.go.id/foto/1750034322044.jpg', 0.000000, NULL, 1, NULL, '0000-00-00 00:00:00', '6287838807981'),
(8245, 'Lulu Sahar Mabrukah', '200112072025042002', 21, 400, 9, 0, '$2a$10$y7OtmgYqQowTb2WHLLBNK.RpTbbMYe5cSLCldNLzye946p942ODse', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8246, 'Khusnul Habibah', '200210252025042001', 21, 397, 7, 0, '$2a$10$bXg8N.BXghv9WTgCQllx6OnhnO2OA4kdKNVHqTAtee.GJ6pKHIJQy', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8247, 'Wike Umairoh', '200012032025042003', 21, 400, 9, 0, '$2a$10$ob6YMYJnGfEaRzwu14.Vnuak6EWL2ObQBmWT59oHDfRcBfOS9mNvm', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8248, 'Irsyad Akbar', '199811082025041002', 21, 397, 7, 0, '$2a$10$quEWYYqKcNu81PzF9X8XeOINNgnADdV0D1PBDQwRfcs12ikjNvvk.', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8249, 'Ayu Wandira', '199806182025042004', 21, 397, 7, 0, '$2a$10$bb22Js2t.DoqbBw0gBLqBO0fsZOTo9m4LHDi3HbiDxyrMET80XwJW', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8250, 'Rini Anggraini ', '199701272025042001', 21, 397, 7, 0, '$2a$10$Ka5oAzIFQQRhGB0818kk2.M3PqBBVIRu.vaV6Rs6WzCHnD4K0y.eu', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8251, 'Bastyan Dimas Prayoga', '200004232025041004', 28, 266, 9, 0, '$2a$10$z0Ib9BAzHCq3cWrSBFGaxOO1Ulxy1ge/XfW/P9A07QBhvJ.aNi57.', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8252, 'Sutin Saputri', '199605032025042001', 28, 266, 9, 0, '$2a$10$ishDPVnm.WfZFe1ELCdd.OggsCnj/d8BXXdTRZNR6EhKCBpBnABZS', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8253, 'Gadis Prasta Driliandra ', '200104222025042004', 28, 1769, 9, 0, '$2a$10$rMgLB6mdD6ufoejfbJNYzOUa6THvFRnRtQ5s2UkJMl/27Ma24X2O2', 'USER', 'https://dev.pringsewukab.go.id/foto/1750035124918.jpg', 0.000000, NULL, 1, NULL, '0000-00-00 00:00:00', '6287838807981'),
(8254, 'Agil Anita Sari', '200208062025042001', 24, 266, 9, 103, '$2a$10$BrXbFtblYl2imAu5mISHvO7yt6ah8dTB842Od4nUnys9J2f25hjIa', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8255, 'Muhammad Umaruddin Syam', '199912302025041002', 14, 1768, 9, 72, '$2a$10$iFIRqt7iFlxWicCGVcjBr.3jtZR9oYmuklPAR75sXDA5y5EU2Fn3C', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8256, 'Fuad Yudhi Yahya', '199712182025041003', 14, 1768, 9, 72, '$2a$10$fUW9SJ/ccft0.NHimdDi4OklwO8GgC2heUNTIRo.zpSRiyu0b6pYu', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8257, 'Melita Ramadhani', '199602052025042001', 28, 1769, 9, 0, '$2a$10$pBrx8XOBFfEuRno3FrACUO4Pe3AJcnY4oufyOCuwlkn5dbMdFOG1m', 'USER', '', 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8258, 'Dika Filawati', '199502032025042004', 10, 266, 9, 0, '$2a$10$0ENfKlzd49GveapYIbtmsu/bVUp9TSo1KqXcw8Pzw7rfym2fL0wsO', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8259, 'Fadel Fathi Suhada', '200209102025041001', 24, 266, 9, 103, '$2a$10$qKjZyTNxa0xipMF44DCq6uxMB6IIdXAFbVHp7ublU9inteGvy3wiW', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8260, 'Aulia Putri Hapsari', '200003262025042003', 15, 351, 9, 46, '$2a$10$99rKUgE.4iV1DnVFz..U4eOMsSs5pXR1gNM5l6DCSpBeJfakEL9L2', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8261, 'Aprianti Ratisna Firdaus', '200004122025042003', 15, 351, 9, 46, '$2a$10$evuYKoUWcU1qY0THZNSWOuAX9kw4LonTe9CsFqgodcLWwe5M5KhyO', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8262, 'Yonada Syafira', '200105082025042003', 16, 266, 9, 82, '$2a$10$FGIV0J38tPygVm5Z7QFy3eIYRLKgYk4s.SyIRIGY/z19Onx4MG/pK', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8263, 'G. Silviana Kristy', '199805282025042001', 16, 266, 9, 82, '$2a$10$8Xwl7iW4kyzvjclBx/PMpe2wCwyPlrx5bBuPsoEo2au/Au/A4BDSW', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8264, 'Refiyana', '199903262025042005', 2, 378, 9, 13, '$2a$10$hQgGt5NeTRMvJefpZPVOTuteaeoscJme6HbT5kTzzBbhye7Fh7Mt.', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8265, 'Nadya Putri Marseila', '200101092025042006', 2, 378, 9, 13, '$2a$10$elHw8E4cKBVKTQTAzd4i.eRYZvGAVVjYkqLX9hBiQiu5e8nYlhEBy', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8266, 'Sherly Noveliza', '199911252025042007', 2, 378, 9, 13, '$2a$10$GUSLh65VIu7XnnGk2jq5F.dPqzjWyG25dBtG2nOZKwGXWN6lHZWq6', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8267, 'FAIZAFATI MATUS ZAHRO', '200209182025042004', 26, 549, 9, 128, '$2a$10$jqhPVhT1kT7QDQ8kPKO0TeV.Rs7ZmPFaU4pj.K/X3434Bzi5Y9gim', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8268, 'LIDYA DARMAYANTI', '200008022025042008', 26, 549, 9, 128, '$2a$10$Q46u4bugzlu5MuueRwyK2uzM4dXiwCHNACmHiPTMxKtupswhYjozy', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8269, 'SHOFI RIZKI YANI', '200001212025042004', 26, 558, 9, 128, '$2a$10$5Q7nGrkI7D5o5SANpAyySu.On3rl4ZieUi05r0INuX96ECnOhaxz.', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8270, 'Siti Adawiyah', '200105302025042003', 26, 558, 9, 128, '$2a$10$lY9SK99N1RbwpAgYgzaWZu1XmT5RUsYQJsxjKvl7YIRMNJxbwHJCK', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8271, 'WAHYU ARIYA SAPUTRA', '199803062025041002', 32, 1770, 7, 153, '$2a$10$/M7ysD8OY9FZgiGYf2Fwh.fh8iq/3KYxWbolrJqk3u6OQhkVPHckS', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8272, 'FAHRANI AMALIA', '200106302025042003', 4, 1771, 9, 20, '$2a$10$UqqLn6MkPEOi.4mZ3R/v5OZ46l1TenYphO8dXBqB5AOz/W2vv1bBi', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8273, 'DIKY KURNIAWAN', '200102142025041002', 4, 1171, 9, 20, '$2a$10$zu/7FhvuDxcm/xMoWgLDQewc4u4ZkOug42Z2pJym1Zs305De.59aa', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8274, 'SIFA AULIA RAMADHANI', '200111222025042005', 4, 1772, 9, 20, '$2a$10$BUx7dDHVzAl58QZUC2n/kOcK.H6EuIjvNNTESHsLhNGneekANglnq', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8275, 'Sugatra Dwiatmaja', '199902012025041001', 16, 298, 9, 82, '$2a$10$33ncD1UGNocUc6krCGCoL.Dq/2xOF.2tP8iZdLGwrxCmJSgpiyHbu', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8276, 'Zaki Ahmad Fauzi', '200202052025041002', 16, 298, 9, 82, '$2a$10$vQ1aP/awnZrxdJkcIVQU8OsnOupmeFQ.6TYRt3XM3twAJlD2b.MGm', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8277, 'Ikhsan Abdullah', '199609042025041002', 16, 298, 9, 82, '$2a$10$lnoE58OXtvo5Ag4Yui7zbeLxp3AnA6KwFVwz3hVb0mVhAeLwk4Hdq', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8278, 'Fadlan Wibisono', '199802062025041001', 16, 298, 9, 82, '$2a$10$tAgVTW8yV.EmspJR3n37jOTeuQT/jYE2cOsjXAO4OOQIwSZqBnf8e', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8279, 'Faesal Agus Setiyanto', '199308082025041002', 34, 1770, 7, 157, '$2a$10$KGFo4FrY6zfU48gImFUdye3cV5iqIjE5Q7dd9vX9TWXoMV90pl3mW', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8280, 'Maiza Ziqril Iqmi', '199405252025041002', 29, 829, 9, 67, '$2a$10$KQ.4naAFHBlX5ibY2z8Q5e9.aNE7qqtUyzgcDXBQyLH81U70I3ocK', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8281, 'Maryam', '199201042025042002', 29, 829, 9, 67, '$2a$10$C12AUjvwuH3T9lxzLxfEEu9Oahh6mWYMqmR0IuPaFdHJV268TdN8m', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8282, 'Rendy Vidian', '199605132025041001', 8, 805, 9, 42, '$2a$10$bRE0JjGHzXLZUcq15Ehfae1EWnqBJ4FVlo4E6SeVDIgcwY42FA1Ni', 'USER', 'https://dev.pringsewukab.go.id/foto/1750151301151.jpg', 0.000000, NULL, 1, NULL, '0000-00-00 00:00:00', '6287838807981'),
(8283, 'Jerry Susanti', '199205312025042002', 8, 805, 9, 42, '$2a$10$m/UnHHmzmLbjHdWqUWOckuzDH9wfCh6EbVc.7y3fLhQlBfVNv/9G.', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8284, 'Andreiansyah', '199903032025041002', 5, 289, 9, 25, '$2a$10$jxVkWNxESW9V.fummqlPV.gJXRF473u0FSohr26BUjCpOnlxPs/YO', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8285, 'Mutiara Salsabilla Diva', '200206282025042004', 5, 1780, 7, 25, '$2a$10$kDldRrfskaLHkJkRMwiE/.a3lSpHmHgQuBKa6VGdforv9evanJER2', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8286, 'Fidel Wahid Abdillah', '199909262025041002', 5, 1780, 7, 25, '$2a$10$jeI8/.G8UTAfBKCnlaa6h.mL.f4pObwrMWDx.hJEf2ynUWwOjuDS2', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8287, 'Rizki Riandiarto', '199612152025041002', 5, 289, 9, 25, '$2a$10$ZOELYqEWk5VkRJTEjvSBlO0u9JC4ZSxo3R9HmRiS3GLe2d0h6T7di', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8288, 'Lina April Lia', '199904092025042002', 27, 1779, 9, 134, '$2a$10$OSOtLWHnpoauegP.NKGCTe830ch5FFGVa0AQKR735VauIbK8o4k7G', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8289, 'Alvianto Kurniawan', '199504152025041002', 27, 1779, 9, 134, '$2a$10$gyyNXgVcmWFb1c/ckFCH.uGx2XorL.A2bpwA0QmbhCx5eqTwufTni', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8290, 'Ikhsan Abdullah', '199609042025041002', 16, 1775, 9, 82, 'a', 'USER', NULL, 0.000000, NULL, 0, NULL, NULL, '6287838807981'),
(8291, 'Yonada Syafira', '200105082025042003', 16, 1776, 9, 82, '$2a$10$FGIV0J38tPygVm5Z7QFy3eIYRLKgYk4s.SyIRIGY/z19Onx4MG/pK', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8292, 'Sugatra Dwiatmaja', '199902012025041001', 16, 1777, 9, 82, 'a', 'USER', NULL, 0.000000, NULL, 0, NULL, NULL, '6287838807981'),
(8293, 'Zaki Ahmad Fauzi', '200202052025041002', 16, 1777, 9, 82, 'a', 'USER', NULL, 0.000000, NULL, 0, NULL, NULL, '6287838807981'),
(8294, 'G. Sliviana Kristy', '199805282025042001', 16, 1776, 9, 82, 'a', 'USER', NULL, 0.000000, NULL, 0, NULL, NULL, '6287838807981'),
(8295, 'Fadlan Wibisono', '199802062025041001', 16, 1778, 9, 82, 'a', 'USER', NULL, 0.000000, NULL, 0, NULL, NULL, '6287838807981'),
(8296, 'Alka Dinda Shafa Nabila', '200011252025042005', 2, 1773, 9, 0, '$2a$10$uJu9DFtz.GPV6MNSrXsX0.rwNGNXxqgRAKtXd2OQSrspBljHqNHNu', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8297, 'Amrina Rosyada', '200106112025042003', 2, 1773, 9, 0, '$2a$10$wTkh5JBb8BOuPn/mNGKdMuelKTxvG5hnTGW8n4bgENUaGdew27jeq', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8298, 'Agung Darmawan', '199506302025041001', 2, 362, 9, 9, '$2a$10$qbAnW97/Ssi7OLs7Wj13a.B8FfPEDPpHcKSG5Sz6g92U0ro1.TM.G', 'USER', 'https://dev.pringsewukab.go.id/foto/1749516314736.jpg', 0.000000, NULL, 1, NULL, '0000-00-00 00:00:00', '6287838807981'),
(8299, 'Riski Irawan', '199211302025041001', 2, 1773, 9, 0, '$2a$10$reT6/.sffk/fYFNjvXLKwOa/Ypn.wiOs18WGPuR2QTCyGVIWHH05O', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8300, 'Arya Idris Baskoro', '200103092025041002', 2, 1773, 9, 0, '$2a$10$qrgonCl6xUu4Son59WEEX.x7oQKDTsv9undlh6otSCi7MPIR1u7uG', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8301, 'Akbar Dimastiar', '200205262025041002', 2, 1773, 9, 0, '$2a$10$dgZLL7h15xJZUlTx5knov.oFap5dU1AuLJzam2UOYKtVzPNx9xLUW', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8302, 'Ilham Hilal Ramadhan', '200011202025041005', 12, 1774, 9, 99, '$2a$10$nSs4uPBjFiRg24BPnrQ9eeRKydvKybJkWQ63YbPXmxC6gU5ncZHaS', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8303, 'Alifia Daraquthni', '200209012025042001', 12, 1774, 9, 99, '$2a$10$iFBGxqGpwx5GQ0cSO3OiauL/1xUliErrM9jylGMyTaMPtZZbxZhKS', 'USER', 'https://dev.pringsewukab.go.id/foto/1749524656628.jpg', 0.000000, NULL, 1, NULL, '0000-00-00 00:00:00', '6287838807981'),
(8304, 'TARISA ARNELIA SYABILLA', '200101202025042001', 4, 1772, 9, 20, '$2a$10$vPlrYwXhkQum/xmg9NRzpOGmYXzyU4ovN13xuXhAT7czRWbrLuzvO', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8305, 'SEPTIAN NUR RAHMATTULLAH', '199809022025041003', 4, 1781, 9, 20, '$2a$10$qhM9wLp4AD1xzb2fzrBrhOdC6biqu5roWKsn7TyB2h8QYU4tRQHfG', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8306, 'ANGGUN LESTARI', '200011202025042003', 4, 1772, 9, 20, '$2a$10$vk4zLUSGhbIt7lb7/XB4hemBns62ORmO5LnsgucjBwDcM/458JGKW', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8307, 'AAFIINA RAMANDA IRFAN', '200109112025042004', 4, 1781, 9, 20, '$2a$10$Ns.DFaUP/GB/zAx9rujTsOyU.3Xbp6q41rl.Cl0FeaeIvpfxykEVi', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8308, 'PUJI AYU LESTARI', '200106162025042002', 4, 1781, 9, 20, '$2a$10$ybaoFPrN2ieujHiyuj9tkuj3x/wh7S3RWVba6Hvl24CyWwEN.c/.2', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8309, 'WULAN AYU PURNAMA', '199212262025042001', 4, 1772, 9, 20, '$2a$10$YAqOp.e4m.v040MiBWgpDeXfE3ZRUDQODOIHbmktXuLSMfXxheyP.', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8310, 'IQBAL TIGANA RIDHO', '199801152025041001', 4, 1772, 9, 20, '$2a$10$HwSEZ4d9KThiJh6yVhJ8MeA/9XUIOOc1kOSleInSbjKrf.clz9RHq', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8311, 'MULIARTA DIPUTRA', '199108102025041002', 7, 1782, 9, 91, '$2a$10$JiguIqN3GOWP0avS5XGvNuFrWU9r5wawpK6kPLy9PxxEq27jRQlqG', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8312, 'HENIA AMALIA SASMITA', '200009042025042003', 7, 1782, 9, 91, '$2a$10$YCllkyhtvCxlecC8OPp0kuOigilNeqoJZGBGT/mUa6nUoRhlqB8BG', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8313, 'ANGELINA LAKSMIATI RACHMA PURNADITYA', '199909092025042005', 7, 1782, 9, 91, '$2a$10$eHF8QPtXZfwczei1ZL/uCexCCT.oshWj8YUlD.qqPPIBdbyNjjB0W', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8314, 'SITI RAHMAH', '198506092010012014', 7, 1766, 13, 91, '$2a$10$xXc6MAduxcUPIhmoT8ELJ.1/7tlpkYlPVFOSkeTgnzW2jWeX3fMX6', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8315, 'NADYA FATRAH BALQIS', '199702272025042001', 7, 1782, 9, 91, '$2a$10$./q4pPmRA2tEs58vjBm6yeg99NqNDKxPiag1qovgm9pAbEDgtsLWS', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8316, 'Farhan Akbar', '199103112025041004', 13, 478, 9, 57, '$2a$10$q40UGrSBXb6lMkUzq3Kpp.EXRkUgbRWHAq5o4pAIkN7XKGU6ycomy', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8317, 'Titus Purwanto', '199406122025041001', 13, 478, 9, 57, '$2a$10$ane6X/W5yRhiazwA8uFziuS1kWPGyWWGYqbQGxlknzXdCylJsoVQG', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8318, 'Ananda Khoirulnnisa Dewanti Pitaloka', '200103202025042002', 13, 478, 9, 57, '$2a$10$Zblrj2zNSceuEtDvEmIGEOdk10OxKGSeLzkv617YnZqzEeLKmg.Sy', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8319, 'Siti Nur Rohma', '200003222025042001', 13, 478, 9, 57, '$2a$10$/g.yPJtLFeBIH1lwVJrv/eXMOq7RTnOSjlNJEYcUP7M5RH7UjvP1G', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8320, 'Woro Astuti', '199609122025042007', 13, 478, 9, 57, '$2a$10$5QH8MWjgmvfxHtA5pe8JoeF/4m44U0XXoobDAHQtwAbDV8xwFjpU.', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8321, 'Hamdani', '199010152025041002', 6, 1784, 9, 128, '$2a$10$iUNTlYuOr7kEJGj0Lvzlv.jI3fcoL5biz2kNSv8BtitsVBjkpaDTK', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8322, 'Adi Ramadhan', '199712222025041001', 6, 1784, 9, 128, '$2a$10$Mw0lruvz3CLPL6DRpA0zn.zA.R6pNGH8XrhVa3VOBFAwnDaEwu9ua', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8323, 'Bayu Setiawan', '198009112010011001', 17, 0, 9, NULL, 'a', 'USER', NULL, 0.000000, NULL, 0, NULL, NULL, '6287838807981'),
(8324, 'Sidik Priyanto', '196803221993031003', 17, 0, 9, NULL, 'a', 'USER', NULL, 0.000000, NULL, 0, NULL, NULL, '6287838807981'),
(8325, 'Tri Yuni Antika', '199706262025042002', 37, 1783, 7, 163, '$2a$10$yoXroYBPao/AvZqC/QghAeo/9ybstaqSiTLVfrdmecGEHllvEz1gm', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8326, 'LITA DWI SAPUTRI', '200111192025042002', 18, 1785, 7, 93, '$2a$10$jQyeI1BYIP0CS7VVylUaaOa/fEgCeQP.wGCQTmlNj/CfCh3cfw7LK', 'USER', NULL, 0.000000, NULL, 1, NULL, '0000-00-00 00:00:00', '6287838807981'),
(8327, 'MASRIFATUN FADZILAH', '199901142025042001', 18, 1786, 9, 96, '$2a$10$aTNVAbc7EirmvpCicuPfEOnT3zxrFoSZKgRqVY8eqDz74SyALGE5e', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8328, 'INDAH YUNIAR', '199906172025042001', 18, 1786, 9, 96, '$2a$10$FOJNlbJtl3OH2m7Jl53KZOQkBC3NNnhLLhTJk.ILGDqF.VXA0kDNW', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8329, 'Reny Rahman', '199612062025042003', 11, 1785, 7, 57, '$2a$10$4VZySGOm6BBCZXVZqRrkpODq/nVZOXqy3kM56E5J5CVyan9rr.8qi', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8330, 'Mutiara Dwi Firdiana', '199908062025042002', 25, 1787, 9, 126, '$2a$10$Q/UYBMSBidJ0N2PD16j4e.mt9fthQnerPRpsUIT7lchd43h0bkJHu', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8331, 'Vicky Rizky Dafitra', '200007032025041001', 25, 1787, 9, 126, '$2a$10$TLFXgmA1ZgWIeosWCYTyqugw8tYMqOohvyFfarUzIFYt1TH/JsD8e', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8332, 'ADE HARDIANSYAH', '200004172025041006', 19, 1788, 9, 99, '$2a$10$hHH/s.CQdbeURY3hMvI13.ftLORy9Fv0wUJtGC7Q4DFb2OdsNaBwC', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8333, 'EMILDA AGUSTINA', '199808082025042004', 19, 1788, 9, 99, '$2a$10$voIMvfrsW4Nnoxuys4WTLerPUi3JhhEUcCezN6S0OWXmZUk27vWMS', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8334, 'Fatimah Maratus Solehah', '199904292025042005', 23, 1789, 9, 118, '$2a$10$5yESW4Sy8jwjGYyclifITOP7zDquy8zbA4PrF2KHAi61HpkqRLecO', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8335, 'Atatya Nisa Salsabila', '199905142025042004', 23, 1789, 9, 118, '$2a$10$v9ZLRXg3irHi7XVvmHTy5uYa3fB5NVkLk5pi1TvJ6O0GDP/lP.HYO', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8336, 'Sutarto', '197101291991011002', 11, 60, 13, 57, '$2a$10$e5L1UtYrQE1emepV.nUGwe6hWiOJ8db9hNt.8VE1swNlxxdqwdlqu', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8337, 'Wahyu Puji Antono', '199010192015021002', 4, 434, 12, 15, '$2a$10$I.Ffc9U8TX7V0m0x80ZMEeGlg.BSG1Wy55x0McHX9Q7ee0apARU3G', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8338, 'AHMAD DIMYATI', '199312072025211019', 13, 1790, 19, 69, '$2a$10$LtJ8u6QTBwKwztNlbYkaiud51DtugvaYf9JqLOGW5nqesAiDzZqjW', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8339, 'MEI ARDIYANTO', '199105062025211024', 13, 1790, 19, 69, '$2a$10$lsom8pywQ4FweBMtvYQ1reHPXJyVWTEGY2Pxs4XqGMai524Aa.dCC', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8340, 'HENI HENIAWATI', '197208272025212006', 23, 1791, 20, 249, '$2a$10$976c6uH9sXyKpU6Ie.xOnOHAvt.Hqp6YL56imupviKmd9lY51LQMG', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8341, 'HANDIKA PRATAMA', '199205232025211016', 23, 1791, 20, 249, '$2a$10$3iWtbjCpSTvOwqIYl9aAV.1HBxIPVSH1FzSdERJzxWe9NZgz6afge', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8342, 'RYAN BAGUS ARDIYANTO', '199205232025211015', 23, 1791, 20, 252, '$2a$10$mvp4Ts1yOn2tT12029bGu.9dpxP2mtvqPkG4/6dg47Rttkn2FrpSy', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8343, 'TONO', '198501212025211018', 23, 1791, 20, 120, '$2a$10$mODR4ww1P9YmN81jc40O9eRaR5ii9G5TrUkj.2jjDs/9161iG5afe', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8344, 'AFRIANDRA SATRIA DIRGANTARA', '198804172025211021', 23, 1792, 18, 249, '$2a$10$LbmFVVH962/IWYfemeO4OOf2e9aXLpiR0mvhGgiXvKIhFeCHXUJ8O', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8345, 'RIKO RIYANSAH', '199112022025211018', 23, 1792, 18, 249, '$2a$10$mWqNLSpqmXTBnnFkZCR/gezoGnZMaXU.skQ0yZyRFvYbx.M5DpdPa', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8346, 'SYAIRUL RAHMAN', '196810072025211009', 23, 1792, 18, 249, '$2a$10$1ldvqCOZex4PFSSx1Ru/4OFqMnkZgpyAVzPUlwamwpkasXdK5cBsO', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8347, 'RENAL GUSNANDA', '199408092025211016', 23, 1792, 18, 249, '$2a$10$GlkxSKqHB5Uq4LKtvmnxN.P2iYd6aiJnzhgsAAhA5pU0a5ING8AwS', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8348, 'CECEP SOPANDI ', '197509102025211012', 23, 1793, 22, 120, '$2a$10$2as.J08BnF1b3lrhYJIZ/uTXYjlbmbAnWEGUzI4Airy4YSso0sm7O', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8349, 'RODIYATUN', '196805122025212008', 23, 1793, 22, 120, '$2a$10$y7Xx5Syx/lzTYpabHsFlgOHvJMHcKzF78Hi2yk10aAvLGY5OV9nbW', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8350, 'NILA WATI', '197809152025212009', 23, 1793, 22, 120, '$2a$10$SO.gUJ8.8wY1BYBRAqCcnebRmGbWH7LY2jqAV8quaMnOO3z.pa76i', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8351, 'EKA PRIATI ', '198604292025212015', 23, 1793, 22, 120, '$2a$10$VLyI.TH88NmLkTAr39UQzun.MF471v0ORi6u4GLCBXZqsEAy81Xnm', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8352, 'CANDRA KURNIAWAN', '199405282025211018', 23, 1793, 22, 120, '$2a$10$VX9FdS0ILbzCYtQW.BT7wu8H6gVFcYQxjs03p8ghxzj3WhJvvgJR.', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8353, 'MARYATI', '197706042025212011', 23, 1793, 22, 120, '$2a$10$xNynrpzH8L/c350gZ/fvKO25Yqv.hGzRydNKaYLjiytQ9oKBPlMNS', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8354, 'WATINI', '197108062025212003', 23, 1793, 22, 120, '$2a$10$mq70xITPgO5/1tT8RjNJVuTxHxzddweCW3stw7LNejobuiJk4JDXa', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8355, 'NASIYEM', '197310112025212003', 23, 1793, 22, 120, '$2a$10$eA45/m92nwFk2ddeDZbSV.nQH36.Lv73RrZNL9lmcdUeze9hINIji', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8356, 'ARIANTO', '198807052025211029', 23, 1793, 22, 120, '$2a$10$oq.UhgpK69DVEQVW0ErGcuNlu3qeiRLDNgvHIKQF/fEB0p/XTl7u.', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8357, 'SURADI', '198508202025211024', 23, 1793, 22, 120, '$2a$10$Op/Li6lc4Ow0ob85thxMFOlQ4LdpE0AAEZSsAa51848E9aMeGgxvy', 'USER', 'https://dev.pringsewukab.go.id/foto/1753691865968.jpg', 0.000000, NULL, 1, NULL, '0000-00-00 00:00:00', '6287838807981'),
(8358, 'SUHENDRI ', '199002182025211016', 23, 1793, 22, 120, '$2a$10$cYgXaVFJKPDROO2vr10jIeidb7wyy3bD5SbaQjDMcqD9voWyGfEni', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8359, 'WALUYO', '198611092025211013', 23, 1793, 22, 120, '$2a$10$qgqxTWE/tNs91fOfrSCkzu6R5A1TKFGaRh76tS77OFMDP2MTyR.Ti', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8360, 'HERU KISWAHYUDI', '198012252025211017', 23, 1793, 22, 120, '$2a$10$IXiV17zPjjsiLGlv1uwi9uBf1YgUOsP.8IyjDBHPeAMpL337RcMPy', 'USER', 'https://dev.pringsewukab.go.id/foto/1753440114119.jpg', 0.000000, NULL, 1, NULL, '0000-00-00 00:00:00', '6287838807981'),
(8361, 'HERMAWAN', '198205052025211042', 23, 1793, 22, 120, '$2a$10$dDGnWz30piQEK.rS3dDIQ.VIRfpkmh3L8TGYoGP.4LndQAnXLGkkq', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8362, 'SRI HANDAYANI', '198512172025212022', 23, 1793, 22, 120, '$2a$10$RPsIjt05toU6ZCWsx9G3P.JgalvAvhXiq5Yz3szVaMqgcmAtITbqa', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8363, 'HABIB', '198403152025211023', 23, 1793, 22, 120, '$2a$10$jRLsiOsnSa63PnlfkkREpO2g3fc5ZqBhj2rLHLxCjrg.wQy2gLlWu', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8364, 'UJANG EFENDI', '197606252025211008', 23, 1793, 22, 120, '$2a$10$jvNcO3TZc6N8KqrNVTEkouQiZvV8hVO.u83/SLdYMpS5sSpj6qTLO', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8365, 'CANDRA ARI SUSENO', '198809092025211021', 23, 1793, 22, 120, '$2a$10$hSGjfGxMf1wp.lagI0JHz.oanHIWhDjcvZ44M7C0RywmvXgFj7sHW', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8366, 'M. YUNUS', '197409092025211011', 23, 1793, 22, 120, '$2a$10$lyiNKF5mA11.iYn3anDopebnEx.ltuy.qEKLW.wurwq3Wl.r7OifW', 'USER', 'https://dev.pringsewukab.go.id/foto/1752814239196.jpg', 0.000000, NULL, 1, NULL, '0000-00-00 00:00:00', '6287838807981'),
(8367, 'BENI SANJAYA', '198306032025211022', 23, 1793, 22, 120, '$2a$10$FuBVFIa143.DoSOO53QYhuTieDv9iIDE0yVj0t1wdfQkqj0tBQajO', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8368, 'SULYANTI', '197404072025212012', 23, 1793, 22, 120, '$2a$10$4.C4OJ1oJ1SzakYO4a7y7OZLNaXqNMKcCTNI2X20z6QATxg9.j9I.', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8369, 'SUJANGI', '196710162025211003', 23, 1793, 22, 120, '$2a$10$4CdpYKIj0/BvARDxyJJzlOFA3TRrr7OreDoTrirv9jGV86FGarK0C', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8370, 'AHMAD FAUZI BUDI SANTOSO', '198107182025211013', 23, 1793, 22, 120, 'a', 'USER', NULL, 0.000000, NULL, 0, NULL, NULL, '6287838807981'),
(8371, 'BOIMAN', '197307152025211018', 23, 1793, 22, 120, '$2a$10$NtdHNFwmSCMle9MVdpuyCew7tLSatlgLrQ0Ka9JTkVfZQ9mSt3AWG', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8372, 'RENDI AYOGA', '199105092025211026', 23, 1793, 22, 120, '$2a$10$YSlzUuxTyLYTTDamAWEQBuE54z6xm4QJsZU0MmSl.bO/o9zYM92Li', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8373, 'FACHRUL SIDIK', '199402072025211012', 23, 1793, 22, 120, '$2a$10$xA7W8kHPS2YMDRZ38Lbjp.w.xdqXFsmio0UVmfzpNjrVBC5RyxJkG', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8374, 'DODI ISWANTO', '199409052025211016', 23, 1793, 22, 120, '$2a$10$hVa7Qa.34/Be1rHiMqRhY.o42zoNL4TqE.UiNQuNr08fNqrWrmwcq', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8375, 'AHMAD TAUFIQ', '199407112025211016', 23, 1793, 22, 120, '$2a$10$0db3LAv5BZExYezDt1yPZ.yCcMMPOU.lPPOLbvUr/TCcFlGeVpwoK', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8376, 'ARIFIN', '197001012025211026', 23, 1793, 22, 120, 'a', 'USER', NULL, 0.000000, NULL, 0, NULL, NULL, '6287838807981'),
(8377, 'KHAIRUDIN TANJUNG', '197412122025211023', 23, 1793, 22, 120, '$2a$10$yD7cIVV4UCiveh5KzyLEfedBEVVbQjeXO2/XUMJvBs.6kjIlZ.H9y', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8378, 'ANDRIKA MAULIDAYA', '197902102025211025', 23, 1793, 22, 120, '$2a$10$rvTqjmSjWYJn85rx2xYTS.7264AHiBspg2ElXdQ0KgErjqgad8lVK', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8379, 'ASIYAH', '197012122025212006', 23, 1793, 22, 120, '$2a$10$pV8TV6fH1itSuSY1sFtVWOeIKP.I5Jg0rNIzh4BNi7PsvMLOQJory', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8380, 'SUSIANTO', '197201082025211008', 23, 1793, 22, 120, '$2a$10$rB/mxZpxzc8JCIEjZek1reumbOcQYyht91ZMV.5IuWUc.XcFKotUO', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8381, 'CHANDRA ARDIANSAH', '199307242025211016', 23, 1793, 22, 120, '$2a$10$jEeKZIZzEavGfl8OFPzs4.TRxVTG/swDP8r76K5wNZky0TCqSncgm', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8382, 'TAUFIK NUR HASAN', '198310232025211020', 29, 1794, 18, 145, '$2a$10$26yrIIJ71QIN2JfbsHMqfOPqmzYJic3Hj.hGFK7rv9j4lNyaC6.I.', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8383, 'ANWAR RISWANTO', '199203202025211029', 29, 1794, 18, 145, '$2a$10$o8Lxh24Uv3w6yWPkCl2sgOGlAmM3vDjnXs.WoUV5oBP3ZhsmSeAb2', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8384, 'IMAM SANTOSO', '198903142025211018', 29, 1794, 18, 145, '$2a$10$pROa21s.n1jtszJw5F644.6ke30to8pWi2UfsxVWPmH2Y0xJdbvc2', 'USER', 'https://dev.pringsewukab.go.id/foto/1752886893634.jpg', 0.000000, NULL, 1, NULL, '0000-00-00 00:00:00', '6287838807981'),
(8385, 'DONI SETIAWAN', '198410072025211026', 29, 1794, 18, 145, '$2a$10$nVTNvRDOUkz3ixvb5/5RcOVoMEiwDdIKgzNDjDYv2sYqHqmAGvj8S', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8386, 'WAHYU SOFYAN ZAIN', '198611202025211028', 29, 1794, 18, 145, '$2a$10$3IkuPGhr3PAIyyLqQZ5f8uBWsuiWXflkYXdWkRNogExlv43KIhylm', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8387, 'INDRA ARDIYANTO', '198308142025211023', 29, 1794, 18, 145, '$2a$10$zWa9XUG4tjdKp/wsH3Iv8uxDiTOd9hIktArTwJnnOWNnqSgAmpcCe', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8388, 'RUSDI TAMIMI', '198712312025211053', 29, 1794, 18, 145, '$2a$10$t4XukXybrb0MqN25337GT.KhG1f1NgVz7rDJG0nXvGPqMAqSWtTUi', 'USER', 'https://dev.pringsewukab.go.id/foto/1753061909023.jpg', 0.000000, NULL, 1, NULL, '0000-00-00 00:00:00', '6287838807981');
INSERT INTO `pegawai` (`id`, `nama_pegawai`, `nip_pegawai`, `opd_id`, `jabatan_id`, `pangkat_id`, `atasan_id`, `password`, `level`, `url_foto_pegawai`, `tukin`, `edited_by`, `first_time`, `created_at`, `updated_at`, `no_whatsapp`) VALUES
(8389, 'SUPRIYANTO', '197805092025211014', 29, 1794, 18, 145, '$2a$10$EzBamkqK/t8VybuPsNRqjeMh2myeFSqm3huW/9SkBrrfoZZ0UgGcO', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8390, 'WIDI ATMOKO', '199006152025211034', 29, 1794, 18, 145, '$2a$10$IJXK0VOxOGVe2SSYjbV6Ze1hrFKzdNwR/ERace4e6PeFxxs/fogu6', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8391, 'BENI TANJAYA', '198810312025211017', 29, 1794, 18, 145, '$2a$10$WAkX5dVDfTFvl5fjYItrFO4yhf.7pP9PO8KLwMHu/MTrYWZVcDM6e', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8392, 'DWI YULIANTO', '198407182025211014', 29, 1794, 18, 145, '$2a$10$2bXeQ96yErt6Lf4mRISyuuo1YT3VabCEWI8waiefGaSTVyCS9yjJO', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8393, 'MISBAHUDIN', '199003022025211023', 29, 1794, 18, 145, '$2a$10$/MaJzFYIfHYy7ZnF2h.j/OMCffnlz/pm6vlfJGQGNyYkQ40j0E5vq', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8394, 'HILDY MIDJASI', '199412162025211022', 29, 1794, 18, 145, '$2a$10$kQLHBjNpepvLm6yDQzjaKOfLyEdUdQTij.mIclFs47wXAQg7D/kM6', 'USER', 'https://dev.pringsewukab.go.id/foto/1754717746729.jpg', 0.000000, NULL, 1, NULL, '0000-00-00 00:00:00', '6287838807981'),
(8395, 'FRANSISKUS DEDI IRAWAN', '198604072025211021', 29, 1794, 18, 145, 'a', 'USER', NULL, 0.000000, NULL, 0, NULL, NULL, '6287838807981'),
(8396, 'RICO VIARDA ALDOSAN', '199012262025211022', 29, 1794, 18, 145, '$2a$10$HDyGWL7Hg2RSEk2TGJvqUOde27WUoaSoWxarcPsS95DC7fUTgit02', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8397, 'JONI KURNIAWAN', '198806102025211023', 29, 1794, 18, 145, '$2a$10$BkX9qKnTihGOfFCQ5nrcd.nG5KrvbTsQfs58omZVaRugNxTDJ1mnq', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8398, 'MUJIANTO', '198505132025211017', 29, 1794, 18, 145, '$2a$10$Y6gxmdv4Pn2VrTGOhH6HaO4YnzZVZBxTYGF6N/.O.1Jatp4wJoTgO', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8399, 'TRIYANTO', '197906092025211013', 29, 1794, 18, 145, '$2a$10$CsqgiSO96J6t7E7jY07fOOf5m9FuAKW2B1.yvK8O9Sxj.zmka8ClC', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8400, 'FAUZAN AHYADI', '198801052025211018', 29, 1794, 18, 145, '$2a$10$i3d68/Lu5PEeRR2ZMK3ll.nsmQuNzKwYXRP8KBIsdQ/ORNEeNaGLK', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8401, 'TOTO HERIYANTO', '198602022025211032', 29, 1794, 18, 145, '$2a$10$YAW6wjx/wBEKhPFR0PHqmeJVNyDEPoFGG7QvZ.m656a7kpUKCbpCu', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8402, 'NASMAI EKA DINRIZWA', '198212142025211020', 29, 1794, 18, 145, '$2a$10$U8vO9r1CnEFs7O983bllXOvPT017MbaRshrH6GtgzvXr8cGmXB2kO', 'USER', 'https://dev.pringsewukab.go.id/foto/1754526358044.jpg', 0.000000, NULL, 1, NULL, '0000-00-00 00:00:00', '6287838807981'),
(8403, 'RIDHO LAKSONO', '199206082025211015', 29, 1794, 18, 145, '$2a$10$DNOfi1JKum1ErqA4WVu25.EwHQVRImet5y1bpP1cDPDSFH4Z.8XSC', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8404, 'BAMBANG WALUYO', '197807252025211010', 29, 1794, 18, 145, '$2a$10$i2P9DWm17YNy/hu9YedGheojKp5FOyVPJIoB7uUSTLbmsilpGk4C6', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8405, 'RIAN HELMI', '199010122025211038', 29, 1794, 18, 145, '$2a$10$yEYlNvD1nJSm40J5yM.24ejWM1eJlxf8/dtfvP9zUbxX5XJYA1ORi', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8406, 'EKO HAROPI', '198809252025211017', 29, 1794, 18, 145, '$2a$10$81Uws4/rAy2B1J3.p4BKyew3oA4HrdbGdPD9XdIEwpDrpXE474F6q', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8407, 'HENDRA BAYU', '199003202025211024', 29, 1794, 18, 145, '$2a$10$/fAM5tC4YZR5JZtgvy3/gO227eKLeCi0pm9H2HIK2sWQTbB1sT.d.', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8408, 'MUHLISIN', '198508202025211023', 29, 1794, 18, 145, '$2a$10$wDVxi0/lg567pzZiKjijKOSYCe1Q6LjdPI2VmmTbicWZ0OFe4ja5u', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8409, 'ROCHMAD SYAH', '198505112025211018', 29, 1794, 18, 145, '$2a$10$PC1lLBP7ZgfJXuPtenAUguCldXVOmSBQNk1VCWGyEfwwgM0YTTGjy', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8410, 'FEBRIAN DWI PUTRA', '198702022025211035', 29, 1794, 18, 145, '$2a$10$xtBEovtBAoKqueM4yifkQOQVvggjwQjnf0visUCf0biJ.1LZ.khhK', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8411, 'APRILISTIAWAN', '198504122025211021', 29, 1794, 18, 145, '$2a$10$CctovO6psajoFxK4Rc9bU.SM7GHD4tygu0ZEQd8a/1420t60V29VK', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8412, 'JEFRI KRISDIANTO', '199107122025211030', 29, 1794, 18, 145, '$2a$10$8wBnh/vE9VsrLw0MnmFLRuFwEyUIfIb33Ci9BfwcVqH9GSFWWVPy.', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8413, 'ARI WAHYUDI', '199009162025211020', 29, 1794, 18, 145, '$2a$10$HBGefSaJMfzGMcYPo6ndeeYtrxcCYQMObH8/u1w4FADMoD2wd6Cvu', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8414, 'RIDUWAN', '198010182025211017', 29, 1794, 18, 145, '$2a$10$.z0tyqd6AXnvQ6JTR7B9xeK8VykxvfF6ev1Ub5OtQwFqynwuAoaBO', 'USER', 'https://dev.pringsewukab.go.id/foto/1753162970143.jpg', 0.000000, NULL, 1, NULL, '0000-00-00 00:00:00', '6287838807981'),
(8415, 'YUDA MAI LUFI', '198907282025211022', 29, 1794, 18, 145, '$2a$10$zZEnvo7qafl9kWCTgXQbue3.L.PVEna5LOn9/HsxYN5pQ01IsGr4q', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8416, 'ANDI SASTRA', '199101312025211021', 29, 1794, 18, 145, '$2a$10$UHNqpzkI/8o8aB4aBq.RT.uBqbCCw.mKhSZ4eprSxkRTNmeu5gq1G', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8417, 'ROYANI', '198703032025212021', 29, 1794, 18, 145, '$2a$10$nkHRQuGPMcUTe7XTKyxY1ODREwBmh3SpZIHA2NLlDZ5N2BAitDkja', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8418, 'ELIDA SARI', '198702212025212014', 3, 1795, 20, 17, '$2a$10$QE7TRXe90gI/vVP/uxN1luu4J09voad2/lz7nSNs/egWtxv2aYlDu', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8419, 'GERVATIUS DONNY STEVANY', '198506192025211015', 3, 1795, 20, 17, '$2a$10$pPi8xxDraKovYEYOHYKPMe.PJHcgxHJ854DnF3Q4HplQqR1rCMVra', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8420, 'SRI WAHYUNI', '199001232025212021', 3, 1795, 20, 17, '$2a$10$qS6LPz3QNiUoRdo6qjWYJeTqpcLyQDF6/Fgw83Z77GlWcmDYc8N3S', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8421, 'MARIYAM', '198405252025212027', 3, 1796, 18, 17, '$2a$10$enBN96nMrgWVR01kG3b0N.Ificgcvrz4lYhk7UrIjrdYWABRv.DGC', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8422, 'NETI SAPUTRI', '199008282025212027', 3, 1796, 18, 17, '$2a$10$OoVDpVYy77qAPRYP1eI6f.hM6N6KAlMD3Vr8NiHQGTEf1tdCm69/.', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8423, 'DAHLENA', '197208152025212009', 3, 1796, 18, 17, '$2a$10$X25UwTDaQ0vdpHqMPkRO.OsZftCfB9q6znsN7fvPSq60L996aoB4m', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8424, 'NORA PRATIWI', '199011062025212020', 3, 1796, 18, 17, '$2a$10$YD8fc3Y/J2RVMLeCdqbxDe/qyJ/zFUjlb38.H4c/FFMBgfNLiBm/.', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8425, 'YUDO HARYANTO', '198505252025211034', 3, 1796, 18, 737, '$2a$10$RSIAXE8eHzr9utZ45qo7H.D8kMj2k9mfY9euftYvZEqiVfRVjkwr6', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8426, 'EDI SUTARTO', '198510172025211020', 3, 1796, 18, 737, '$2a$10$SYdOvAE7OZWNr38MI48Rk.kUkp0mv6uxYjaIm/rQWF0YYolJOWsPC', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8427, 'ISKANDAR', '198402092025211015', 3, 1796, 18, 737, '$2a$10$9UAEMwOHs2GKrqm6pmqct.GrXZRqj4KPv3jrGclsZrDyafY8R5QF.', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8428, 'EDI ROMAHZAH', '198506272025211029', 3, 1795, 20, 18, '$2a$10$PW603WYBtvte4s25MaAwb.pHJxRjj.NO/sbuParXlO9MTDi2wR8dS', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8429, 'USWATUN NURKHASANAH', '198709192025212015', 3, 1795, 20, 18, '$2a$10$eufCSkuEkOwduAT1hN3xOOJ8bez3VcJmt7kZmX3j4mekZexqMAYNO', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8430, 'SULIHAT', '197506102025211014', 3, 1795, 20, 18, '$2a$10$8l2uUlbfKEitHXU4fB0LwOqzNmNikAT1Ho0F1MLdreQomHHXT.LMW', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8431, 'TRIMIN HASTUTI', '198010262025212010', 3, 1795, 20, 18, '$2a$10$Dngy7yVkFVKx.f/lNBFW2eC9p5n/IvHAm0jpxN/9GJ4yLsNPkf1wG', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8432, 'NIA KURNIATI PUTRI', '198409282025212016', 3, 1795, 20, 18, '$2a$10$sM6dBuq701n8lN52qEaY/OXvbEATkobN58Ehc4x8.Ro6byYDKa0VC', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8433, 'HILMAN ABDILLAH', '199201102025211018', 3, 1795, 20, 18, '$2a$10$mv9mfaAez0mpfcZIHkFxEOWp04o0ItnyJgggU4VCcva.6.qhzPxna', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8434, 'YOPHY IBRAMSYAH', '198905032025211031', 3, 1797, 23, 18, '$2a$10$nQh1vlxjB/mYppkrUMRjT.WQS5hOmagxrlkWOwzIULw3QpvHdqSWO', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8435, 'HANDIKO', '199302022025211017', 3, 1796, 18, 18, '$2a$10$0Sbo.BYHdJbHAbFt5IXNYOLb/aXOzdp0AlYzZae.zwrXjwzXAx6.a', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8436, 'YENI ANDRIANI', '198205162025212019', 3, 1795, 20, 19, '$2a$10$V28QtgQj7HnX6HtEStrP3uKddIHmoflM4SjH0iF0FcZY0oiyLtS52', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8437, 'RAHTIANA SHELANDRI', '199002092025212025', 3, 1797, 23, 19, '$2a$10$Gy33o4rnL3BDJHZjiocUdeS4SnpSgDY/.zIxqvb2.xyBaUZugiwG.', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8438, 'ANDRES PRATAMA', '199601022025211015', 3, 1797, 23, 19, '$2a$10$gMsFegOiY3seX6eeA23MSuFAP.JD1xGaDBkF3jcNv0Zp/LDcv4eLS', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8439, 'BETY ASTRI JAYANTI', '199310212025212014', 7, 1798, 20, 814, '$2a$10$W43gBo21iVVTySNwh9N/g.wVq6iOAM3chXm4Pf5A/gdzrYpC68poq', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8440, 'DICKY RIANTO', '198607082025211014', 7, 1798, 20, 814, '$2a$10$TsYJiiK4aeTd968/mz14QuEoC3qWtrYrS.ZBTq41l785XW2icXjx2', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8441, 'IRA FERIYALNETY', '197712252025212013', 7, 1798, 20, 814, '$2a$10$cSobTQn1qTpgXxmQQ5M5Au8ns6WjqAirKfsk4yiEj3XhHoNMZMtgS', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8442, 'FANI YUDI SETIAWAN', '198405202025211026', 7, 1799, 18, 814, '$2a$10$ApAf4xQBg3gQ9peoVgUDy.eJfistqqp27yb3wRR0gZeNlqITtdtky', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8443, 'MULYANI LESTARI', '198201092025212014', 7, 1799, 18, 814, '$2a$10$1SKUteT.NE8nqvC/Ee9L/er7YKXBtgGWe3uLi.qG/RwlUk6YcV2Mu', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8444, 'ASNAWATI', '198904132025212024', 7, 1800, 22, 814, '$2a$10$mricrwTYGqogviAAUbniP.jjDfbQ8Ge3K5aom6TU5CstWOv15NfFa', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8445, 'M.RIZAL SOBRI', '199103022025211020', 7, 1798, 20, 39, '$2a$10$ZI0pmXOEdeR2kx7eo4uzNOzgiLVfM6GvgDWYRxPT.y442pVlPgy7e', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8446, 'EVA DESI YUNIARTI', '197812212025212006', 7, 1799, 18, 41, '$2a$10$mjpROrpbj73OGjXDMgpvzeYmritF4tHbM9b8huGjxD1jNieE4hJpq', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8447, 'DEWI WULANDARI', '198404182025212013', 32, 1801, 20, 155, '$2a$10$ME5zKqdxXCyU.HxtVmzdhefkVZTxoh2XrMxop58ylzEQEunsHcIE2', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8448, 'AGUSTINA  AYUNINGSIH', '198908182025212033', 32, 1802, 18, 976, '$2a$10$KhEt3wsbWI7.5EtaqNlkQO2Tv392H1KP8bd8u2Dp71UBvGvxCGGSa', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8449, 'SULASTRI', '199104012025212026', 35, 1803, 20, 961, '$2a$10$dpD1JnqfnVMqzRwW2BvTGu2dHAYW3YDyBRRmqpt4mWwqmCs3eWmxO', 'USER', 'https://dev.pringsewukab.go.id/foto/1752478177540.jpg', 0.000000, NULL, 1, NULL, '0000-00-00 00:00:00', '6287838807981'),
(8450, 'SANDRA DEWI PUSPITASARI', '198209282025212013', 35, 1803, 20, 962, 'a', 'USER', NULL, 0.000000, NULL, 0, NULL, NULL, '6287838807981'),
(8451, 'TRIYONO', '197905202025211013', 35, 1803, 20, 966, '$2a$10$GXJhMf0Jf8IZ/q/UlLisQ.xksC/Xdxilf6jn8fHslRMUhakRaw26O', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8452, 'NASAROH', '198609272025211021', 35, 1804, 18, 964, '$2a$10$FcxFiYDgmNSl69Wo4OnxPOLzca6IPUB2s9UJaeoHN1BfiRpHtlDnG', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8453, 'RENI APRIYANI', '199504242025212021', 37, 1805, 23, 953, '$2a$10$nZafgQ2QbXlWe/v7jexMceW7y8Ps9dSL7J/9adW3JP1ZBAg3DSrrC', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8454, 'SISKA RESTIANTO', '198010282025211020', 37, 1805, 23, 164, '$2a$10$lUldPwME1toe1FRnIKvU3uENApXWK8rkVccc3TxPquzgJzWyTHiDe', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8455, 'ARIVITA HAPSARI', '198408222025212017', 37, 1806, 20, 954, '$2a$10$JL0BMLwIPPJrBc5dEQO8nuoa65avudfEwXBwzMmNU9AgddzGZT116', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8456, 'DESI LIPANDRI', '198412272025211017', 37, 1806, 20, 953, '$2a$10$pwZlq7JoAFSPNV9h7M1Tx.8nGZB.hDZDC.cn8xhSwiYiYnGkYEske', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8457, 'PONIRIN', '197404252025211013', 37, 1807, 18, 954, '$2a$10$UdKpBRQ.jItD0pYdvPBUoOUzh2zfYYT88XrfV5ehFOUbT3GaTvYnq', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8458, 'EDI YULIANTO', '197507072025211012', 37, 1805, 23, 221, '$2a$10$TPyXjI6Dh5eGdQYVfBlpOeVc6TtFGnCa6zNwGHlMFatV6DnRwRHgi', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8459, 'RITA WULANDARI', '198902232025212018', 36, 1808, 20, 943, '$2a$10$bXsmGNE5BMf8xtA1w7vzpOGS38sprsLp2ynKrsiIslGU12rG1JzLm', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8460, 'HARYONO', '198411282025211019', 31, 1809, 18, 924, '$2a$10$VhX21FxcFTET1brQP2dNyepBNs097dH7ops.OERmXbMJ2MoP6BAM6', 'USER', 'https://dev.pringsewukab.go.id/foto/1754041980084.jpg', 0.000000, NULL, 1, NULL, '0000-00-00 00:00:00', '6287838807981'),
(8461, 'EFI YULINDA ', '198409122025212015', 31, 1810, 23, 924, '$2a$10$RA3GUDznPM3zHtB9iz9HC.SnLdFFhM1wfjIIdV8HYvno2sax3qrJG', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8462, 'HUSEIN ADHI FEBRIANSYAH', '198202152025211017', 34, 1811, 20, 280, 'a', 'USER', 'https://dev.pringsewukab.go.id/foto/1756459943269.jpg', 0.000000, NULL, 0, NULL, '0000-00-00 00:00:00', '6287838807981'),
(8463, 'SUSANTI', '198703042025212023', 34, 1812, 23, 280, '$2a$10$9XOe0LSZKfHLYSeH9vEa1.A5blnRln/FBZyTtAkns12sGVDnTlImq', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8464, 'GUNAWAN', '198101282025211006', 34, 1813, 18, 277, 'a', 'USER', NULL, 0.000000, NULL, 0, NULL, NULL, '6287838807981'),
(8465, 'NURKHOLIS ZAMIL', '197909142025211024', 33, 1814, 18, 945, '$2a$10$5FIqJs0t51mK4iXClCRF8.iEzmQzSMdzdhMaIwklLKRS7EIjqKfcq', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8466, 'EKA PAWITRI', '198401042025212014', 33, 1815, 20, 930, '$2a$10$./FRu6cb027d884LFPfu.uKxYUgeO4XWV5k71/ET9PfwLL8sQEOJy', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8467, 'YENI PRAWITA', '198906222025212028', 33, 1814, 18, 929, '$2a$10$pkxLgZumPLBCZNENHQ26/eUK8sKFD9IUNcXvpGn9zSpuZrYAUpMqO', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8468, 'SOLIHIN', '198507112025211024', 38, 1816, 18, 995, '$2a$10$tj.T2ihOAi/hHn8jdkC5DehCTeqD6ZH6v7bBvxnRsqavjeLMUe.Me', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8469, 'KUSMA ARIF', '198602062025211013', 38, 1816, 18, 992, '$2a$10$7iYXLKQG9/uJRVyiJmrky.JDJ06V8FHeLB0k/CElQNy4tQUwaixcC', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8470, 'YULI YUSTINA', '197707092025212010', 38, 1816, 18, 996, '$2a$10$JorsoYOtP0GCnd/ZTr4chOftLWSGVA2Qh/87GvSw8r403wYErQC/i', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8471, 'RISKI HARI SASONGKO', '198707132025211025', 39, 1817, 18, 942, '$2a$10$hITfP7WIMbLeYbYhVvfbne6Yivx04Ow.3awhi531fNBJnhWT7U6We', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8472, 'EGI RAMADHAN', '199303122025211016', 39, 1817, 18, 942, '$2a$10$NSRfusFkxFwuAHYFaLU5v.mOIV1n20.Et4kcZwoj6Esyg7o8InDDO', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8473, 'DIANA INDAH PURNAMASARI', '199310222025212017', 39, 1818, 20, 942, 'a', 'USER', NULL, 0.000000, NULL, 0, NULL, NULL, '6287838807981'),
(8474, 'ZAINAL ARIFIN', '197803252025211008', 39, 1818, 20, 942, '$2a$10$Vld7YkP7PHYYi8p7pPnZSeE/w0bQ2zHkq.HbZGgtc8cpwcwDWRqDi', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8475, 'KELVIN PUSMAGA', '199008052025211028', 42, 1819, 20, 1020, '$2a$10$Dyevy0m2y3GG/ho2SGwNSezgClxcO4OgwdiffIJBm7ZkTCoWmShRu', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8476, 'SAMINGAN', '197210082025211014', 43, 1820, 18, 311, '$2a$10$eNrvq3.Hch.Zg8uBQRYm6e/7Hjkp6sdtvI/4t0HBWJbRT5kX1Vt5q', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8477, 'DIANA HERIASANDI', '198808112025212021', 43, 1821, 20, 311, '$2a$10$ZWZ2XNMJtTLyZONgWD/UQOpNIz.y7/9nQMjDmRh1HU.ws05MtUByO', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8478, 'DWI APRIANI', '198104052025212013', 44, 1822, 20, 1030, '$2a$10$E3FQAEnV71yunNEKEYzP4O/Lz6SQ4CziYk4vub0rnyERaD1JaeKrO', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8479, 'TRI WAHYUNINGSIH', '198806072025212023', 44, 1823, 23, 1030, '$2a$10$9Mpje1LegMnTC56bSwKTxepmTjC0foMTrFLIHHE.JOaY.j3zz91x.', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8480, 'ELISABET SUNIK BUDI LISTIYOWATI', '198607222025212021', 44, 1824, 18, 1030, '$2a$10$AVRJUC.EbSZOpMC44RtA1eEcdSIOfZJydnV21w5MDl8ki66T6BUCK', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8481, 'RATNA EVITASARI', '198409062025212014', 45, 1825, 18, 1034, '$2a$10$om4rCjVFX/qtq4DidnyqOeThDBzs.3eRPPe0gY8DRmftgV501RGzW', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8482, 'APRIANA', '198709012025212017', 45, 1825, 18, 1034, '$2a$10$ZKEGQL5PdqJ1NHsc2XTudOP3OPaMLbjxkWcZ.zTT8CuSVIgUktjKy', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8483, 'ELFI CHAIRUMNI. CH ', '197302032025212007', 41, 1826, 20, 698, '$2a$10$kaevWZXAcEz.n0m0l/hEheqmboTRUrc.1d/Si61HPqyLuVwyvRhlu', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8484, 'DEWI RISKA WATI', '198706082025212024', 41, 1827, 18, 698, '$2a$10$wt4cjrMZv3LP7mKFCPAdBuqgQ5ToxSZupnIEiTnsY5cZ50dJUIsYa', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8485, 'FRANKY MURRY PURBAYA', '197705152025211030', 15, 1828, 23, 427, '$2a$10$JjPunxHt/kAVB8geQbJwAOrt2GzlukNV4Heohno4A6a7mu2vaotMS', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8486, 'HENDI GUNAWAN', '199111142025211023', 15, 1829, 20, 427, '$2a$10$.TBeLhqr6GmzYId26bmffOiOOP27gvP64FRmkPpSYtwGReLz82a1y', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8487, 'ELSI RIMA DONA', '199402282025212029', 15, 1829, 20, 448, '$2a$10$eZWoifhlsLCFQDDE4MYcNel7HrO7YJGER3mkMEgSL9BHDRH8iniZK', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8488, 'DWI NANDA', '197612202025212006', 15, 1829, 20, 448, '$2a$10$ww4ExL6I8fxMnCM9sSieGOPnxgnKS62trUKPkJ8sTAuXtxZYPurim', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8489, 'IKE APRIANA', '197904102025212008', 15, 1829, 20, 448, '$2a$10$NG0dUuiglz0CE340OxwAKeK0mYXJE0YhCJsUgtbIaLTnzv2BiDr4O', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8490, 'YUNITA GESILAWATI', '198706242025212014', 15, 1829, 20, 427, '$2a$10$FXVlyOvVWcOrvMnfzlUI9e8nOx/qjk6DP4Fsbg4eq32NTVupPsk.e', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8491, 'LIS RUBIYANTI', '197904092025212008', 15, 1829, 20, 442, '$2a$10$8LhWc/DuVsrvNq/m6bomheKDnTLVHpGGwdYErg61Oe8JzcvVMfxze', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8492, 'SURIATI WERDININGSIH', '197003122025212005', 15, 1829, 20, 442, 'a', 'USER', NULL, 0.000000, NULL, 0, NULL, NULL, '6287838807981'),
(8493, 'ERMAWATI', '198112232025212009', 15, 1829, 23, 427, '$2a$10$bx6HChiSzp8WE2FBYwwWNef30.BPMyvLm0YNEdadv6Hd0vQwGODri', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8494, 'BAMBANG PRIHANTO', '198111262025211008', 8, 1830, 18, 773, '$2a$10$BII0SJo1ddAotXOsUmHCK.xQHm0OjbNsSxnQOOJwKGxLpRjeAHQRi', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8495, 'ALLAN DWI ASMARA PUTRA', '199206042025211024', 8, 1830, 18, 773, '$2a$10$TF4x5Fq7YcQSLzB6O/VJWOptBuW8PpOS5dkXA7fcHCxYnsbFuYcsW', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8496, 'DIKA SAPUTRA', '199403262025211015', 8, 1831, 20, 773, '$2a$10$PAG5nsT9SFPdS2QliuNqqe8gXkU06THXVe9/qdx3LEGD1JGKCCiZa', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8497, 'ANIS ALVIANI', '199808102025212014', 8, 1831, 20, 773, '$2a$10$QrSDMU2LL2yIzBpglEBcouI1YOR2OthjbrLeXG00lcZOUdXKx3wJW', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8498, 'EKO ADRIYANSYAH', '198507282025211017', 8, 1830, 18, 774, '$2a$10$ThdgBK.azJskZ2MRQbpP..Gbrx.wzOl4LVxdFNznqgo28znKXNZjC', 'USER', 'https://dev.pringsewukab.go.id/foto/1755138020112.jpg', 0.000000, NULL, 1, NULL, '0000-00-00 00:00:00', '6287838807981'),
(8499, 'NOVI PANCA ANGGARI', '199311212025212017', 8, 1831, 20, 139, '$2a$10$dts5AoV.UcN7RvaTZPfQ8OXgz1LbtXAfDg.0zOc3f.1I3M4ODo85m', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8500, 'HENDRIANSYAH', '198808142025211028', 8, 1831, 20, 139, '$2a$10$tOvY7/KXDz7I6NCJnI3Si.QHHTb8vIJ/zqG9z4pMI.7YZ/mqNvnCW', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8501, 'FAJRIEN DESTA AGUNG', '199312302025211019', 8, 1831, 20, 139, '$2a$10$9SzandQ.T6wSkcA.8HdWUOJ8D9QWBA92higZ3Xfa7stLppaTYiMf.', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8502, 'SITI AISAH', '198606172025212019', 8, 1831, 20, 44, '$2a$10$oo.uaECai/DtUI7BWtS6Qe0BkpK4xqLQlqaNaf7w9xC0K2DNfXWMa', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8503, 'ERLHY', '198108222025211018', 8, 1832, 23, 44, '$2a$10$Kk/bGy6noSbMPxGIlzNng.Wb02DIjTgRLfx75MtQIzWIlRZYcdLeO', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8504, 'HENI PURNAMA SARI', '198209212025212012', 26, 1833, 20, 836, '$2a$10$DVV2.A/1nVu7Qc7mWLWcoeZ97cQNDQoa3go72ciG81M5lco4K8Rmu', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8505, 'RYAN ADE NOFRIANTO', '198911192025211018', 26, 1833, 20, 836, '$2a$10$K1525GquYj3vNOl4pUf7e.yqSF/Zlao8lK.PCMBfQAiwSzQNkutfy', 'USER', 'https://dev.pringsewukab.go.id/foto/1752460463310.jpg', 0.000000, NULL, 1, NULL, '0000-00-00 00:00:00', '6287838807981'),
(8506, 'BUDI RUSTAMI', '199005152025211030', 26, 1833, 20, 131, '$2a$10$cNcH0ZoVIuHdv1h00Ds/iOyteMVWZWq47JAAz3t9vDZ8e0v/QRVpe', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8507, 'BUNYANA', '197210072025211014', 26, 1834, 18, 130, '$2a$10$uXfZaTEEHmTb9CdVYo53Fe2CS36VZH5BDKP6svOyg2oU9daW.3rEK', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8508, 'RENI MARLIA', '198403032025212021', 26, 1834, 18, 836, 'a', 'USER', NULL, 0.000000, NULL, 0, NULL, NULL, '6287838807981'),
(8509, 'THOMAS ARIYANTO', '199006182025211020', 26, 1834, 18, 836, '$2a$10$4ISJRv2okTZS4EySnuXSv.XBr9ZRSdYLLYMavPCV/lBtQANo9VfpO', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8510, 'H.DIO PRAMUDYA WIRATAMA', '199303012025211019', 22, 1835, 20, 692, '$2a$10$FyKh3TLDsdBzzWTXIuZi9u1I7gLlAK4waIrJuByJNvy0XPK9oGnA.', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8511, 'LINA FATMAWATI', '197611222025212005', 22, 1836, 23, 116, '$2a$10$RSTfZqRZudE511MYQweN6uS6/SdkNObJMAhoBFklispgGFkvVZUOu', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8512, 'TEGUH LEGIANTO', '198507152025211033', 22, 1837, 18, 116, '$2a$10$laYRdgzsAcpUC/E.0xzpWerDWCgSUeXX8ynmTBegTnV.rQ4VH5ghG', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8513, 'HENI MARLINA', '199103062025212023', 22, 1837, 18, 117, '$2a$10$SblswKTIw2SVx5z1uZAiTuSNiN00XrCPZEy2ALZcrntYaexwbAK6S', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8514, 'SAYYIDAH ALMUMTAZAH', '199105192025212014', 22, 1837, 18, 117, '$2a$10$jF3jsEu3QtU/oi0JmRRmsuj8qUWq3xF2I.Vo6.7Kudr84H0URKOuu', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8515, 'APRIANA SUSANTI', '197504302025212007', 22, 1837, 18, 693, '$2a$10$VoCXcbXKM6TeqgUsg0LxgOJ45sKVm5KD8PN2jbP9V9sCGrfFq1EYq', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8516, 'SYUKRI', '197507082025211013', 22, 1837, 18, 693, '$2a$10$s4G3eTldd5Zy4mvTZWOe1exdn10XQAIdV8QRHCYN8lzt.emKwEUYC', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8517, 'SRI DWI ASTUTI HANDAYANI', '197602122025212008', 22, 1837, 18, 693, '$2a$10$iM222F6WLgvJ5iZ2isa.begG6ecedREX/zsrHGPEKwLmoR/k5f29m', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8518, 'HANDOKO', '198105022025211015', 25, 1838, 18, 119, '$2a$10$R5baLZjbOuIgvlnpmH0q8exVA47V8lFNB9pfQiwOesMoLAEFerWyy', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8519, 'APRIYANA', '198604292025212016', 25, 1839, 23, 852, '$2a$10$El9jWaBhp0L02Ytr0kY4CuzhON6Gb8uQnAspkzfr8RMQ3VsBFTJlu', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8520, 'EKO NARWANTO', '198207252025211012', 25, 1838, 18, 119, '$2a$10$fIC5ujj7WpY1KbY41BMjt.dpw7t.qOGr0nb4OSbCAXxfBomdZMepO', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8521, 'MEGA PUSPA', '199212122025212037', 25, 1840, 20, 119, '$2a$10$ke23Lv3NVxofxjorGhfaG.5hBlQl8XKjuhAbA14e9vEK3ZthjsvpW', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8522, 'AGUS RAHMANTO', '197808232025211009', 25, 1838, 18, 119, '$2a$10$P5RTCjDvEXjC.eeWwVvIteSuEOUeRXQFpkdBElSLA4d0H6RcoGMva', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8523, 'DESKA SURYANTIKA', '198612282025212023', 25, 1839, 23, 852, '$2a$10$0qPmqgKVvbSE9ZpuoqFCxemuLn4hE88Ft8pKHKdu1kq/CCOh4fBXK', 'USER', 'https://dev.pringsewukab.go.id/foto/1752461859385.jpg', 0.000000, NULL, 1, NULL, '0000-00-00 00:00:00', '6287838807981'),
(8524, 'MEILIANAN KOMALASARI', '198305292025212013', 10, 1841, 20, 173, 'a', 'USER', NULL, 0.000000, NULL, 0, NULL, NULL, '6287838807981'),
(8525, 'MAYA EDRIANANA', '198708102025212025', 10, 1841, 20, 179, '$2a$10$4QxB5ipk6JsX5zUHMK0ZAeY2QOXrNkplOaY9IwSdCwLR4QBMTkB.O', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8526, 'NURMAN WAHYUDI', '198103302025211008', 10, 1841, 20, 173, 'a', 'USER', NULL, 0.000000, NULL, 0, NULL, NULL, '6287838807981'),
(8527, 'ANDI BARADA', '198311062025211013', 10, 1841, 20, 56, 'a', 'USER', NULL, 0.000000, NULL, 0, NULL, NULL, '6287838807981'),
(8528, 'IRFAN NURFADULLOH', '199711102025211014', 10, 1841, 20, 173, '$2a$10$.LuhcdD9Z3c6L.4vekJCOeNAMHRSrCZTx.YLnq0/d2Oz5JYoT3Ow6', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8529, 'ASTRIYANI', '198306022025212017', 10, 1842, 23, 173, '$2a$10$QjiFysBoElkA7h8sTWCXxu.DoM9Uhii1DHNVr8Xh7WVY2JPMDIf7i', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8530, 'JUNIANTO', '197806272025211009', 10, 1843, 18, 173, '$2a$10$3lEYt1HWA5WbrZETYsSTqeFxe/J3ELFYnXza6k9nsgOlRjzfkQonG', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8531, 'OKTA FINDRA KHAIRIL', '197911142025211017', 10, 1843, 18, 182, '$2a$10$QOw2by7CzVsrxnRhdIkLb.ZzKwPK/BGfWrQL4KfoR7bXrz4PnZata', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8532, 'YULIANTO', '196710162025211002', 9, 830, 18, 762, '$2a$10$zTQ3KzDvQIr1zKIOc5mswe398Zc851BA4yxvaOivgXqWHmiqtsDHS', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8533, 'SRI RAHAYU', '196901012025212008', 9, 830, 18, 762, '$2a$10$nxjX45Y43s2PtYubZnFwl.n0uKg.xVIUT079Pc8tj1h0foJY094n6', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8534, 'M. MUSTANIR', '197010242025211003', 9, 830, 18, 762, '$2a$10$E42H8aYmqdIXW//hJJr8Gek75G3HOV3SsbrIORo2wDSxoQ.88nbQO', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8535, 'SAMSUL PAJRI', '197501092025211008', 9, 830, 18, 762, '$2a$10$jAuEmsukL/stQ6H.wzN60O.cFeVWVgFtd0qYxPkIJg65T8puVwWAi', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8536, 'SYAFRULAH', '197511042025211007', 9, 830, 18, 762, '$2a$10$2v1GbYWA0jgE/UOcACNFK.pXQE.iDqNWEwq9Qps2PxvGT.ScwYi4a', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8537, 'NASRU YASOLI', '197606192025211011', 9, 830, 18, 762, '$2a$10$6T9xKv77ULL8z652vS8heOTsgb/yAAFvMicAtpJtUC0k1baVWuBea', 'USER', 'https://dev.pringsewukab.go.id/foto/1753141988347.jpg', 0.000000, NULL, 1, NULL, '0000-00-00 00:00:00', '6287838807981'),
(8538, 'FEPRIADI', '197702042025211016', 9, 830, 18, 762, '$2a$10$zqo5s5LMRXOUdEAwmKzbn.KKdUiq.jLkYVS6iR5KrTP8N/VQfpds6', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8539, 'SONI ANDRIYANTO', '197705012025211019', 9, 830, 18, 762, '$2a$10$CI7NpGi5csfAoCZ50piALOBcIe0xlIaZNhp7a2u1beJgMPtU0qpRK', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8540, 'HAFILUDDIN', '197707202025211009', 9, 830, 18, 762, '$2a$10$7sIwd0WHXnIB2rX4TY4RIO8K8ljrNkFgTIFd/3Ud.ZWw2CDKjTbYO', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8541, 'NELLY OCTAVIA', '197710302025212003', 9, 830, 18, 762, '$2a$10$74K1veCf4Si7HXbqNbaizOSw/K49bGox6xaNhCVjGR.sxM6Iw.4N.', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8542, 'TAUFIK KURNIAWAN', '197804092025211012', 9, 830, 18, 762, '$2a$10$ceMscmCnTVJ2I6XB/HLqsuJlfqP8Aw5iKQYAGw.GZJFeBXP0x3QYq', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8543, 'SUMARDI', '197807012025211017', 9, 830, 18, 762, '$2a$10$r2Gx.eSjIXsZ1NAhwOIqvuBfElWQIHzGFkpgrmErTMy4oBAOAkD52', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8544, 'SUNARMAN', '197809102025211014', 9, 830, 18, 762, '$2a$10$h5y7fMz7iDxsATp2yT0JNei.2KOxw8bMDpPRVBsrkFKjpnV6ynB1a', 'USER', 'https://dev.pringsewukab.go.id/foto/1755217485853.jpg', 0.000000, NULL, 1, NULL, '0000-00-00 00:00:00', '6287838807981'),
(8545, 'DAFIT SAPUTRO', '197809202025211017', 9, 830, 18, 762, '$2a$10$vWARPNXFRIVRwoLBSbOw2.hJt4upD.po6q3a9WK78eYC0FJj.SnDe', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8546, 'EKA SAYEKTI', '197907112025212014', 9, 830, 18, 762, '$2a$10$YU.XylNETsZT9vg0yl4APuLC8O2PYK89nPrIGuWMp.xyyN0cXc4aO', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8547, 'ANDI', '197908082025211019', 9, 830, 18, 762, '$2a$10$B67OlJ6sVjLx9jBcHw.5t.0vUEt/uJlOQc83vE7F.rOwsFwddkFQq', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8548, 'BAHEROMSYAH', '197911172025211011', 9, 830, 18, 762, '$2a$10$yrfx606SbqnguvuqCCZeS.VDKCmLoW7JnTijrHAGmGyAP2Y1wDNqW', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8549, 'SAIPURROZI', '198004122025211024', 9, 830, 18, 762, '$2a$10$oiGVUHzzkgULaBdtXKfR.uoV8JixZjhVg4w83UKLFpmoZVtjMUpgy', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8550, 'BARUDIN', '198007062025211024', 9, 830, 18, 762, '$2a$10$teBE7bB3EfIzwf9SK845L..Hnk7hPiUaYAkXMqgX05xWHiLX8MVby', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8551, 'SIGIT SUPRIYANTO', '198007272025211023', 9, 830, 18, 762, '$2a$10$0Bq05o4TfQllchz4ljpoUeLx9AInUUa8C57zZkkGLcgegNXSwO3Ay', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8552, 'FITRA ARPIAN', '198008052025211021', 9, 830, 18, 762, '$2a$10$XLNALiZ/HhMLjjjRK2D7yuiTLzZWZEt54krgIbo5dP4DDRzum01vq', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8553, 'RAHMAT KURNIAWAN NASUTION', '198008182025211024', 9, 830, 18, 762, '$2a$10$naV9Y8F/5AcIa8IyrJET2uqZGNLQpqy6ZlUAnri3Q2EaVikzB.Yfm', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8554, 'ISTAHURI', '198012292025211015', 9, 830, 18, 762, '$2a$10$fPpv0HdtUaWJls4apXahAelT2P0/HaidZTR7GtcLIJW3RvBk8hKDG', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8555, 'BAMBANG WAHYU WIBOWO', '198107032025211016', 9, 830, 18, 762, '$2a$10$V8/Znu2uvJFHcy1PRmtg9OBn1YyDFTXiEgbX3Xe9e7zpIq8rufjYa', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8556, 'IKHSAN MUSANIF', '198109172025211015', 9, 830, 18, 762, '$2a$10$sAzn8ICg.Mx4qKfHdgrPSeQWOvDUj0Qy9Eytw2TPfr9fhLnWxnYkK', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8557, 'AMRI AJHARI', '198112252025211013', 9, 830, 18, 762, '$2a$10$pRK1CIDlgUqu/eDv09b1AeIp4R9McRlXkXPcad8u6zoLch8.Q7JY6', 'USER', 'https://dev.pringsewukab.go.id/foto/1754095547597.jpg', 0.000000, NULL, 1, NULL, '0000-00-00 00:00:00', '6287838807981'),
(8558, 'ANGKASTARIA', '198202102025211029', 9, 830, 18, 762, '$2a$10$rpAWJD6AY14QkgZ.7WF8CO0Hl56MIb9LV0reMhBGgUxCQH.w16VIe', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8559, 'DADANG HIDAYAT', '198203272025211015', 9, 830, 18, 762, '$2a$10$tyZOo0Gz1BzodH/JcBpfM.mpPNyMdMg2DEny/KAFWRsdfeNqMvBlO', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8560, 'DWI HADIRI MARMIKA', '198205202025211031', 9, 830, 18, 762, '$2a$10$Y6pkdW9vBX2xrVcIp5.8cO6NbmqUvQKn16sD2v/Pd19XD5bBmVSmu', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8561, 'SIGIT PRAMUDIANTO', '198206132025211020', 9, 830, 18, 762, '$2a$10$Wj5qKLV3DScPql8JVreMbOYlFR7QGjf/xn2LUEh1M64grRFR68u9C', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8562, 'ADHI PRIATAMA', '198206252025211019', 9, 830, 18, 762, '$2a$10$xnzxk88L0dBJdo8YklqVeOePIAU//7fSGmK4LvkEQoy0eSv.U1lHe', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8563, 'ADI CANDRA', '198206262025211039', 9, 830, 18, 762, '$2a$10$Udm5tNrQ3.x.5RmpRxl8Vu08wyTUyUQOTt/AT02Q21fVsbq8urS2q', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8564, 'MOHAMAD YASSIN ASRI', '198207052025211039', 9, 830, 18, 762, '$2a$10$uFALQbn6H7fCc4VLN0sbPuRh4V9W9rZUNN83gCCD9jUc7vuP//Uv.', 'USER', 'https://dev.pringsewukab.go.id/foto/1755738152594.jpg', 0.000000, NULL, 1, NULL, '0000-00-00 00:00:00', '6287838807981'),
(8565, 'ZULKARNAIN', '198207122025211023', 9, 830, 18, 762, '$2a$10$ux0dCphj.C5rqAIcy7MyDOP3SfE7ssgnBcYkUsFt8G3othiH6uFwy', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8566, 'IRSAN MUNTAYIN', '198211192025211010', 9, 830, 18, 762, '$2a$10$4ezSb2V4fEgnXpbJZv2uCezANVDRJ.UAHO0vMskr3hZc2V.K2W3.C', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8567, 'FARIZAL', '198301202025211015', 9, 830, 18, 762, '$2a$10$myaQTGGL9YaRMvCOa5JsQeo7TEMfSmiMdey/7ff02.Ef3OvUfrUuG', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8568, 'SANSIN', '198302052025211024', 9, 830, 18, 762, '$2a$10$4J2/kW2lbg5XFLEnAvcK3ex6BPFLBo5946lvkYYWMOlOoCKG9aqku', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8569, 'TIMUR SUSILO', '198302122025211017', 9, 830, 18, 762, '$2a$10$9LeM1NbnxnvSdmk9ASpP1eMIxR6kFHtp1z0ZaPidesro2Q481qn1a', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8570, 'HELMI', '198305022025211026', 9, 830, 18, 762, '$2a$10$HsYzsu32IBFBccXY4HoXnO.36qhpCQjRNidIdesFelKrptG2aGAsq', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8571, 'AFTA YULANDA SAPUTRA', '198305242025211014', 9, 830, 18, 762, '$2a$10$hp2UQgPd0wK9wljd20k1AOvCCfzax/poZeoxE1AfpsHWJkGJHCSpq', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8572, 'ROBIYANSYAH', '198306112025211038', 9, 830, 18, 762, '$2a$10$JB/p4oh7J61L0crkfJRxOur69V9ThpuuFpti.WWSHRpboTR9dv9.S', 'USER', 'https://dev.pringsewukab.go.id/foto/1752472037947.jpg', 0.000000, NULL, 1, NULL, '0000-00-00 00:00:00', '6287838807981'),
(8573, 'HOBBY KHAFILIN', '198306152025211031', 9, 830, 18, 762, '$2a$10$cRkGAvntvuNY8RYfFF/ozeBmIraJtOEOaJk0fhX4yJN7ZfoqxMHD2', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8574, 'EKO SUWANTO', '198308122025211025', 9, 830, 18, 762, '$2a$10$QcoeUIXTq5UvgW4i6iy1feGuaSk5MzSJsxY0805wEtNabDR6O5Awu', 'USER', 'https://dev.pringsewukab.go.id/foto/1754698721463.jpg', 0.000000, NULL, 1, NULL, '0000-00-00 00:00:00', '6287838807981'),
(8575, 'YESI YANTIKA', '198308172025212023', 9, 830, 18, 762, '$2a$10$ZpRqEk5z86RS4obkWLDdR.MKTW4H7BqwLpxvgqJKMJ9W0cXNHJ8GO', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8576, 'YUDI ERWANTO', '198308252025211017', 9, 830, 18, 762, '$2a$10$ZB/7TS5wKc3TXAnlQshj6eCMcL1.dj7Nw9xJU0r020Eh/thgfu0aa', 'USER', 'https://dev.pringsewukab.go.id/foto/1753143293224.jpg', 0.000000, NULL, 1, NULL, '0000-00-00 00:00:00', '6287838807981'),
(8577, 'BURHANUDDIN AMIN', '198310212025211017', 9, 830, 18, 762, '$2a$10$XKe6q6rmzoiGwzFX9Mo2DeYX/M.BapwsLP6WVm1nvD817hjX0q2lq', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8578, 'ABDURRAHMAN', '198312232025211024', 9, 830, 18, 762, '$2a$10$arCz2pYXabKowOabmgUVHO5EP84BwFB7ODZB35KpgUd/0uj4ZqzLG', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8579, 'FAUZI SALEH', '198403012025211020', 9, 830, 18, 762, '$2a$10$gKQt.WSDAnFDlhjuBtzUne7R2aqAJi4HRNM9btUEwPdHPX0N50Q2.', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8580, 'WIDIYA HARMOKO', '198403142025211026', 9, 830, 18, 762, '$2a$10$seHujeAkG5pzVeamAvV7L.TdOQfo3Tap2J/OHyVPWnmXdwjRUqbfC', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8581, 'RISDIANTORO', '198406202025211028', 9, 830, 18, 762, '$2a$10$XVd15I3.q.86Gbn3HmujxuHe6833xH/cVAw8LByH52vwfuowlh.c6', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8582, 'YUSRAN', '198408052025211022', 9, 830, 18, 762, '$2a$10$3cuK4PN8L91c0u90U.OZiOVZl4lNR.aG7e07LlXt16qUW54ey.tMK', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8583, 'MUHAMMAD IMRON', '198410162025211013', 9, 830, 18, 762, '$2a$10$xhIr.329KmcaaU7se8LwXuzIUJP4xYB.J8tIYsHD2tTriXGQU1OHa', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8584, 'DESI PUSPITASARI', '198410222025212010', 9, 830, 18, 762, '$2a$10$dBlgjyzn2a4bvEVjBJ1rc.5QjFWtsIMdv3/Msz3BSV5IdjoVOFNa2', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8585, 'RAHMANUDDIN WAHAB LUBIS', '198411042025211014', 9, 830, 18, 762, '$2a$10$ZfFhWKDKwkWB38x8d2hwNuo6JO7roFMnn7u4sDVFdeyelKoIofMsu', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8586, 'SLAMET SUTOPO', '198412062025211013', 9, 830, 18, 762, '$2a$10$EkeWZQM3mWmEM2sbFSZvjuWp7WfjBI8081cWdyHurD8h365jasKpm', 'USER', 'https://dev.pringsewukab.go.id/foto/1754126610106.jpg', 0.000000, NULL, 1, NULL, '0000-00-00 00:00:00', '6287838807981'),
(8587, 'ARIS DIYANTO', '198412192025211009', 9, 830, 18, 762, '$2a$10$sK7pKSTSXS63Mx72nHaOxu/mD/8VdW1RDUmwNLs5oG8bwVnhkYSVi', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8588, 'ANDRIYANSYAH', '198501122025211019', 9, 830, 18, 762, '$2a$10$bhS2d533zi6aw9PgJBpIb.PqH4hVKRsxtll1hJUFZXEWphYtHkJ8i', 'USER', 'https://dev.pringsewukab.go.id/foto/1753835508455.jpg', 0.000000, NULL, 1, NULL, '0000-00-00 00:00:00', '6287838807981'),
(8589, 'BAMBANG', '198501212025211019', 9, 830, 18, 762, '$2a$10$RMiIGJ1CdTnihp5Vm2y/1OX9MgiCLhW5Aza9utN7uBZWwZkMEwLxS', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8590, 'HERMAN SUSILO', '198502122025211018', 9, 830, 18, 762, '$2a$10$cZlOlMkawfG0FdE1AamzS.1JdXq5Z6t1zy6.ya3xd5QvSGByRJQCC', 'USER', 'https://dev.pringsewukab.go.id/foto/1753075013377.jpg', 0.000000, NULL, 1, NULL, '0000-00-00 00:00:00', '6287838807981'),
(8591, 'BAMBANG SULISTYO', '198502142025211019', 9, 830, 18, 762, '$2a$10$e5Ks/7vgmHOFleAwYUObFu2OqRmt22QIIZfJsl7NWdvVEuL8V9iie', 'USER', 'https://dev.pringsewukab.go.id/foto/1754102067437.jpg', 0.000000, NULL, 1, NULL, '0000-00-00 00:00:00', '6287838807981'),
(8592, 'JOHANES NURHANSYAH', '198502222025211024', 9, 830, 18, 762, '$2a$10$HAvd/sAL2yBryUnORZkOWuEe0qBpLwQQ3b8awrrSOMRA5t3emAsxO', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8593, 'DWI SULISTIADI', '198503042025211015', 9, 830, 18, 762, '$2a$10$8oohrQ1.45DaM0cQZQQ4VOCDNywq0LXr/6kFfyAco3lYHXi.TQGDe', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8594, 'GUFRON FRAHADIAN RIFAI', '198503292025211010', 9, 830, 18, 762, '$2a$10$11pp3.SFxAQsGTv6NbW1YuZp39YvwI/tjD3sV1GfeN814LDu0P.Aq', 'USER', 'https://dev.pringsewukab.go.id/foto/1754269464433.jpg', 0.000000, NULL, 1, NULL, '0000-00-00 00:00:00', '6287838807981'),
(8595, 'APERIYANTO', '198504102025211017', 9, 830, 18, 762, '$2a$10$/ARoNIGMm84TPkKSaLnGtOoVXvarGJziwwuIMRVZq.JhTV.W67ZFe', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8596, 'SITI WANISAH', '198504102025212018', 9, 830, 18, 762, '$2a$10$D8tkNql6s1QMLhIMBkwYxOzNS/wybyXM96rdpsxdzNtQX6jz9Xw.C', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8597, 'APRIYANTO EFENDI', '198504112025211012', 9, 830, 18, 762, '$2a$10$b2EoIfQPnjG0to.GkUvsnOVUd.nY54cz11AbhMKf25vcMhamYvE5y', 'USER', NULL, 0.000000, NULL, 1, NULL, '0000-00-00 00:00:00', '6287838807981'),
(8598, 'EDI SUSANTO', '198505122025211037', 9, 830, 18, 762, '$2a$10$et4K4bkX7tsMpnYTqGJeeuhyNn5MBqKWH1PLnEqQGSHsEA4MgxxHq', 'USER', 'https://dev.pringsewukab.go.id/foto/1752459123280.jpg', 0.000000, NULL, 1, NULL, '0000-00-00 00:00:00', '6287838807981'),
(8599, 'RIVAI MARZUKI', '198505132025211018', 9, 830, 18, 762, '$2a$10$GY3bDn3d.HP5nWoCd0h9XOE51xByRTJJBiZGE3eYtQHwP3vtlsgTi', 'USER', 'https://dev.pringsewukab.go.id/foto/1754094285099.jpg', 0.000000, NULL, 1, NULL, '0000-00-00 00:00:00', '6287838807981'),
(8600, 'ADIMAN', '198508152025211024', 9, 830, 18, 762, '$2a$10$.FggrVzt.PZS0OnjjjnuAOSs6G4HnE/zwDPR.NGCcdD1KSlZ/WlaS', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8601, 'KHADAPI USMAN', '198508182025211025', 9, 830, 18, 762, '$2a$10$JhWrie5Jl6npSyhC/CptWe4M/klUMsCUjS64rwxillWHhjhgvCOWy', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8602, 'AYU LESTARI', '198508282025212023', 9, 830, 18, 762, '$2a$10$NxZVQ/cHM82P0MVrOVX2EuTh9DT2Ngl2wHNCjM/sEmNhXW49cOp.q', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8603, 'SISWANTO', '198511142025211013', 9, 830, 18, 762, '$2a$10$0CjbdEzdnpp8wq3LTuBuW.MylumTODhMqQz/HcuSquNMKZQLgQpq2', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8604, 'FIRLIYANSYAH', '198511162025211014', 9, 830, 18, 762, '$2a$10$Sya.pNQ4E6FfO.pEd4JKFutvH7OiFOWpJMgUvn0N63J/Wi25gcdJa', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8605, 'HABIBI', '198511272025211012', 9, 830, 18, 762, '$2a$10$3Xixh/cwCmpgUWxO6LBQJuHgUfRsQhzSCEE8UKaXGlbUwPot/x6Ku', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8606, 'EDI SAPUTRA', '198601022025211023', 9, 830, 18, 762, '$2a$10$ave7cmrNE7Fhlepj3y7gOOS5sp9xI/mOuAcz4Q.cgN9gpgoJ0gUMO', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8607, 'MUHAMMAD FEBRIANTO ABDILAH', '198602022025211031', 9, 830, 18, 762, '$2a$10$B7Ghrc.NATw8ST/7QVgS6O4Fuepf7kOC3uq4BtIICe9nnD8l2M8um', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8608, 'SUBANA', '198602052025211025', 9, 830, 18, 762, '$2a$10$H99dXN5k2g4UZP9eXJBFm.3QFyTK6W9TgXCeSwAXbCM6kRILzhmjG', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8609, 'FERI DWI PURWONO', '198602212025211018', 9, 830, 18, 762, '$2a$10$OaO6YHXUEE7sp5H.I4ZsuOMFWzY.yUd4dTK.MQFvyUBAwTbbAKrK2', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8610, 'ANTONI RAHMAT UDIN', '198602212025211019', 9, 830, 18, 762, '$2a$10$AH7yZARvTbcDuvrUde2Or.cw51D.42jC8I9h/6MZ8fT3ajotDpYhu', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8611, 'SUHARTONI', '198602282025211021', 9, 830, 18, 762, '$2a$10$jIzchEz.kSqOaBuIeEXf1OLVVPZ8KjyoDF0QpKZJk1Wi8d4siC9Ki', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8612, 'TRI MARDIANTORO', '198603032025211030', 9, 830, 18, 762, '$2a$10$v4DnbY6HAuJxbuw44nYUy.AyDTt0xFREJ26y/veoAMe7IHkXsWzQ.', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8613, 'WAWAN ISTIAWAN PUTRA', '198605062025211024', 9, 830, 18, 762, '$2a$10$XxpiRxegFFNV6wDoFtCTL.8B05EzmjBUUbnkms2dqG4MBuE0U/KcW', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8614, 'EFENDI', '198606152025211037', 9, 830, 18, 762, '$2a$10$d/eik7nKTzCeMUwJad0HIudrmScbnuo4Hj//oReRgqlhmgTiL/2cy', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8615, 'IRWAN PRASETIYO', '198606192025211019', 9, 830, 18, 762, '$2a$10$4IvowVRPhHZcZs0PUKzNj.TlNBlV5mAToirAm9onq6yzrHsG.owXq', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8616, 'FAKI RELIANTAMA', '198606212025211022', 9, 830, 18, 762, '$2a$10$smOFPlo7HtNropYPjDmUD.RBiZG9vSZDiX9XC8o.rbHShS7k4REMm', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8617, 'ADI PUTRA', '198607072025211041', 9, 830, 18, 762, '$2a$10$InpGbYW7IkMS/cIxMfAj6eDQoFJTodjEQfnnpwjEPaH7t4u33aR42', 'USER', 'https://dev.pringsewukab.go.id/foto/1753146631734.jpg', 0.000000, NULL, 1, NULL, '0000-00-00 00:00:00', '6287838807981'),
(8618, 'DODI GUSDAR ANGGA', '198608172025211035', 9, 830, 18, 762, '$2a$10$0l62ufWVuiD8eVGoS1tulO/EsfZQ2BIluI9dbfrhbUMFhErzuThrm', 'USER', 'https://dev.pringsewukab.go.id/foto/1753324245193.jpg', 0.000000, NULL, 1, NULL, '0000-00-00 00:00:00', '6287838807981'),
(8619, 'NUR SALIM JAZULI', '198609162025211015', 9, 830, 18, 762, '$2a$10$0kyHZU/ahf5c7oCL.0KnpukqWdNEvOt3Ks9aQ881hyzFVTvzmt4yW', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8620, 'DENI SANJAYA', '198610082025211019', 9, 830, 18, 762, '$2a$10$5LdB5MRuu1wpx5HwEZLsb.Z0l7J4R53Tv7t5V6CQ5XweUDiFPTiTy', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8621, 'SYAMSUDDIN', '198610102025211036', 9, 830, 18, 762, '$2a$10$JmvPzYAu6g8erIMhBYSmSOJswKOdEE3gonF8zbFYxRUk3hU0Bc4oy', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8622, 'NOPI ARIANSYAH', '198611202025211027', 9, 830, 18, 762, '$2a$10$LwgRtCz7hgZyED6gAKtewekDCcGFhljYeYq/SM7MHhAhL/rg2A7CC', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8623, 'DEBI ERWANTO', '198612182025211019', 9, 830, 18, 762, '$2a$10$eF6Z74FdvWYkIMpRlCqSd.4Y0Mfr42UYhyKEJBnwT/kc5Ju54b3Bi', 'USER', 'https://dev.pringsewukab.go.id/foto/1754003748712.jpg', 0.000000, NULL, 1, NULL, '0000-00-00 00:00:00', '6287838807981'),
(8624, 'AGUS PRASETIO', '198612242025211029', 9, 830, 18, 762, '$2a$10$y/EG9l5iIBuaV1Xlz6Lr1eA7yGX.wPSOP.qUitu.otmrQ5g5vImGa', 'USER', 'https://dev.pringsewukab.go.id/foto/1754050656142.jpg', 0.000000, NULL, 1, NULL, '0000-00-00 00:00:00', '6287838807981'),
(8625, 'ANTONIUS WIJAYA', '198612252025211027', 9, 830, 18, 762, '$2a$10$lACzsvvwsUrBaavC3R7RqejlFLbYnJnLlwQFjtWFpfYb7is3wpA/.', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8626, 'RIFKI JANUANSYAH', '198701112025211013', 9, 830, 18, 762, '$2a$10$5NKAqr5mNDOG5EiX4EqNFe5jmMKzIyHCnOsrMyVRNvGsAZP0UEFqm', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8627, 'YAHYA ROLIYAN', '198702102025211027', 9, 830, 18, 762, '$2a$10$T.QUHh5rezOjmj1Lv3CYyu8dF52GDnEIMqAgi3jjaXF9epa/0vVFy', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8628, 'HENDRI SETIAWAN', '198702112025211019', 9, 830, 18, 762, '$2a$10$NaA8dL2AW6LkdrVqW0wS6O7yHukbnTYI0kewFpQv3oWGaX9JPsCj2', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8629, 'ANDIKA FERDI WIJAYA', '198702122025211026', 9, 830, 18, 762, '$2a$10$Z3rsWadikr3KFI3M1DF64uNkzhOaOb9VWZ7d7LDIN.4dEQFB9TjHm', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8630, 'HAMZAH WIDIYANTO', '198703032025211019', 9, 830, 18, 762, '$2a$10$g33K5S6uu1arbQ22xeq5peY6cojeDvm.A57G/j2U17j/V9jzzO.LS', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8631, 'DEDI ARIYANTO', '198703082025211024', 9, 830, 18, 762, '$2a$10$jjJNSMYk9d7bPDuiHzZY5OmFFI0R/Kql5V4ENQ8FGjfkbPvLTVDG6', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8632, 'RUDIANSYAH', '198704272025211015', 9, 830, 18, 762, 'a', 'USER', NULL, 0.000000, NULL, 0, NULL, NULL, '6287838807981'),
(8633, 'EDI PURNOMO', '198705092025211030', 9, 830, 18, 762, '$2a$10$58oBsVGGzCFoTAJMAPrlluaOvqJBhHSLiXkDwRF4Y/FRrCAg1NotW', 'USER', 'https://dev.pringsewukab.go.id/foto/1754103288032.jpg', 0.000000, NULL, 1, NULL, '0000-00-00 00:00:00', '6287838807981'),
(8634, 'RIBUT WAHYUDI', '198705112025211021', 9, 830, 18, 762, '$2a$10$zb.ny33T0m/rZeaJ9t2YHuzmt99cITA/64zdKjukw70YYQ/BFKe1W', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8635, 'ARIF ARYANTO', '198705222025211019', 9, 830, 18, 762, '$2a$10$dIBFjj49FoRu.cU0Tp8VruVFtixXeIgX0r/w41FmRw/97RH3VF/EO', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8636, 'DODO ISWANTO', '198706022025211019', 9, 830, 18, 762, '$2a$10$xlhRDR1jO9YCwsL.4stWFu7lEuXhpx2B.GlfurC8EETRe0iIyqh0.', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8637, 'RIZKI YOLANDA PRANATA', '198706292025211019', 9, 830, 18, 762, '$2a$10$YIVIuFo4HzkV7CMkgBwUzuCzhmxrzF3to0UJ1X4nDlNDpii68SUhi', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8638, 'ELI YULIYANTO', '198707062025211021', 9, 830, 18, 762, '$2a$10$Dtk4v0FGc4m7ZLIXudZPrO8wyO3cSj6fPsLmVCqZi8M0pyV1XhzA2', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8639, 'ZULFADLI MASYHUR', '198707272025211038', 9, 830, 18, 762, '$2a$10$OBUmCTSxxHsGquVrpmrOo.9.eaQ97/Q0j7MUEQSIqbi6L0KiqglD2', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8640, 'MUHAMMAD AGUNG NUGROHO', '198708022025211017', 9, 830, 18, 762, '$2a$10$J1DcIT00/K794lZTnpCJSO2fUuOVDhpFiJbUZsE2EHBUdw1A5qXJe', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8641, 'MUHARAM', '198708262025211019', 9, 830, 18, 762, '$2a$10$hg57pxhUlwK9tQshKOHUDueW6UggSibOx4lzFzd2/c4X9l9HteizC', 'USER', 'https://dev.pringsewukab.go.id/foto/1753150232704.jpg', 0.000000, NULL, 1, NULL, '0000-00-00 00:00:00', '6287838807981'),
(8642, 'YOSEP SAPUTRA', '198709092025211027', 9, 830, 18, 762, '$2a$10$c0xu0AVp/pP0EaeKgaUXJ.jG7dDF0MGJwh0Ad28pWJUPi3tOGTqTW', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8643, 'PANCA SAKTI WIRAKUSUMA', '198710142025211011', 9, 830, 18, 762, '$2a$10$EVS60NcQ7TkKxVoOk/QkU.zqljkaK1ZefZLKwXkAR8WgeXI9WgBtG', 'USER', 'https://dev.pringsewukab.go.id/foto/1752653820377.jpg', 0.000000, NULL, 1, NULL, '0000-00-00 00:00:00', '6287838807981');
INSERT INTO `pegawai` (`id`, `nama_pegawai`, `nip_pegawai`, `opd_id`, `jabatan_id`, `pangkat_id`, `atasan_id`, `password`, `level`, `url_foto_pegawai`, `tukin`, `edited_by`, `first_time`, `created_at`, `updated_at`, `no_whatsapp`) VALUES
(8644, 'SANEP RESTANDI', '198711012025211021', 9, 830, 18, 762, '$2a$10$d/REKHDq8OaD8KF44oQwCOB5Xv6FAIteA7XGnK.MyDCB1e96XZnoy', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8645, 'MUHAMMAD YAMIN', '198711112025211024', 9, 830, 18, 762, '$2a$10$b7WfMQtOsVToAqh3BRRPNeOQz0ob6jK9HIKpEXNFm6yXU8FFAon4i', 'USER', 'https://dev.pringsewukab.go.id/foto/1752542655033.jpg', 0.000000, NULL, 1, NULL, '0000-00-00 00:00:00', '6287838807981'),
(8646, 'MUJIONO', '198711162025211014', 9, 830, 18, 762, '$2a$10$ynwlFptcFlrLmVaai8zQr.JHtEXEGjsQPXuK2DpuDKX9FpdZCKZpu', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8647, 'SASTRA EFENDI', '198711202025211020', 9, 830, 18, 762, '$2a$10$9m0EWV.Z8u60BD2mzkrPK./EeLRmziQ3E7LIvsGSPAz30mHDE2sA6', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8648, 'MUHAMMAD RENDY', '198711212025211012', 9, 830, 18, 762, '$2a$10$SHQR0932Kb0yFeguD1g9ouXnTVJBMcpG5klIDxFvFGUWbJ.0a/rF.', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8649, 'PENI SINAGA', '198711262025211014', 9, 830, 18, 762, '$2a$10$5aU97MJY4fUHzsl4gH5HYuQoaE3nQx/ScTXLwHcUGhWGjFDlaYxnG', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8650, 'NI WAYAN PUJI ERAWATI', '198712152025212030', 9, 830, 18, 762, '$2a$10$Vvo.CbM7WhiugYHbvTwy4.0N7eP4vMi2iWaVc1gOBIpeAnlRyBz6q', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8651, 'JOKO SUSILO', '198712192025211018', 9, 830, 18, 762, '$2a$10$NJcNSPxH.QY9qaeNvu3ZVeQ5/OUFUP/32.5cwcZjpIjxxMCoQEIi6', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8652, 'ARI ELIYANTO', '198802062025211023', 9, 830, 18, 762, '$2a$10$8R7ejSY8dFUOETFNmO4X/eojwBtf1F3EhYvRe7nC2n6BfYiFIjf9y', 'USER', 'https://dev.pringsewukab.go.id/foto/1753096099495.jpg', 0.000000, NULL, 1, NULL, '0000-00-00 00:00:00', '6287838807981'),
(8653, 'EKA RIYADI', '198802252025211018', 9, 830, 18, 762, '$2a$10$tLBuYFL4lLyd/ElSHUJ9levW/BnfsGGNxQHOdDRpxtgaIpzwUeuhi', 'USER', 'https://dev.pringsewukab.go.id/foto/1752495225254.jpg', 0.000000, NULL, 1, NULL, '0000-00-00 00:00:00', '6287838807981'),
(8654, 'ARI KUSUMA DEWI', '198802262025212024', 9, 830, 18, 762, '$2a$10$vREyJ744awTqRei1AhhAkew426hAFw108qjcRp8nzEOkiPHeF4KIW', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8655, 'WAHYU SATIA', '198803052025211009', 9, 830, 18, 762, '$2a$10$BkAbKls3Ry0lUVPxMNUnpe1IsEooRUQUzg4vWrIy1lFJhe.uP/gpa', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8656, 'AHMAD MARTIN ICHSANI', '198803132025211021', 9, 830, 18, 762, '$2a$10$bcFZhxEAJrgpShcQ3rBcHevIWyK6Y79pr9BzPjI5OOVlC6gQtpaCS', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8657, 'ALIYANTO', '198803142025211019', 9, 830, 18, 762, '$2a$10$F.FEw7aGFkM5X0mliMoteOceRTFHy5MrJnybu9eLuKXgwjmpmf282', 'USER', 'https://dev.pringsewukab.go.id/foto/1753077747570.jpg', 0.000000, NULL, 1, NULL, '0000-00-00 00:00:00', '6287838807981'),
(8658, 'MARDIANSYAH ADI PUTRA', '198803172025211023', 9, 830, 18, 762, '$2a$10$bn5uQ9cqCcsPJIaShnbAAONsX12f6e8XybskTAmGBIM.ttd6nL2Tm', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8659, 'ANTON SUDARMO', '198804042025211023', 9, 830, 18, 762, '$2a$10$tDJsPWQ001YdC0Kd9OmIUOpOyOWzHTQK1j0UCHx7g.ERVbIUGOuFe', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8660, 'YUSUF SETIAWAN', '198804082025211029', 9, 830, 18, 762, '$2a$10$jlL2Xnb4KvJy6LrnFq99B.6yCqtJQIstXl/Z23JSeGQIgKbqo0wmW', 'USER', 'https://dev.pringsewukab.go.id/foto/1754129467611.jpg', 0.000000, NULL, 1, NULL, '0000-00-00 00:00:00', '6287838807981'),
(8661, 'SUFIRMAN', '198804152025211028', 9, 830, 18, 762, '$2a$10$Y6ignnT3OYcwM0bmDuIm0.qy/KcH82.e9DN/OHjHzcG.m8lCUNY7W', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8662, 'SUBEKTI', '198804162025211024', 9, 830, 18, 762, '$2a$10$B5VBvth4rE9go1HNkxrUs.nyhG4nIC0sI2kYg.IdjuyiCak/tKfAO', 'USER', 'https://dev.pringsewukab.go.id/foto/1752456112698.jpg', 0.000000, NULL, 1, NULL, '0000-00-00 00:00:00', '6287838807981'),
(8663, 'ALI USMAN', '198804212025211023', 9, 830, 18, 762, '$2a$10$Ks9IxKkwaVyjuVTHBJRO9.7Ha.2RVyJbF1ErT.f0WHlE2cw8Zqg4q', 'USER', 'https://dev.pringsewukab.go.id/foto/1753691634743.jpg', 0.000000, NULL, 1, NULL, '0000-00-00 00:00:00', '6287838807981'),
(8664, 'HENDRIYANI', '198804242025211033', 9, 830, 18, 762, '$2a$10$rZitKGSWbtRu47rlfbn3lu12JUMqjmPPoYB4aCQto9XXXnWznsytm', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8665, 'ARIFIN RAMADHAN', '198804292025211024', 9, 830, 18, 762, '$2a$10$b5gNpx/4QF9WK0gbmo5FM.bCdfkPjIgFj1ODBoZ2bbdGQ1CPqVuXG', 'USER', 'https://dev.pringsewukab.go.id/foto/1753158416066.jpg', 0.000000, NULL, 1, NULL, '0000-00-00 00:00:00', '6287838807981'),
(8666, 'WAHID EFENDI', '198805082025211024', 9, 830, 18, 762, '$2a$10$Hg4tCGFTMBy6aOVvy7EPpODtzTg9r1DfHx0aN8MEQ9ernnCYpQLie', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8667, 'HENGKI FIRMANSYAH', '198805162025211020', 9, 830, 18, 762, '$2a$10$0qYSTTsKnpHOEZ4q1TmlJ.U5gwu1EnvSXPG91LGPGw5cK8qn8Fz0C', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8668, 'SYUKRON KAMAL', '198806112025211023', 9, 830, 18, 762, 'a', 'USER', NULL, 0.000000, NULL, 0, NULL, NULL, '6287838807981'),
(8669, 'DEWA MADE EDI SUSANTO', '198806122025211029', 9, 830, 18, 762, '$2a$10$VbcauM4kK.VP0Fnn3BLobemZBeNSLG8fQd48cZaCzFnA7zQcwoqOK', 'USER', 'https://dev.pringsewukab.go.id/foto/1754440324755.jpg', 0.000000, NULL, 1, NULL, '0000-00-00 00:00:00', '6287838807981'),
(8670, 'LILIK SUGIYANTO MAKRUF', '198807102025211034', 9, 830, 18, 762, '$2a$10$w4mMGUTzi8OkIiUN1WEzEu3JxOLBfL0aNRVDuB606JMUSlhIXrmeK', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8671, 'MUHAMMAD JAMALUDDIN', '198807292025211016', 9, 830, 18, 762, '$2a$10$BsBqLTIQoP.csCjYLbIiSev26OTc/jPpbCH3NmRahSIIg8tJlF7qy', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8672, 'RESNU JUFRIAN', '198807302025211016', 9, 830, 18, 762, '$2a$10$uWlZwsBeKrTV2IDfhZuHs..aq6S/fqygGkiRRNMQEeXwr0HoV9F/e', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8673, 'HUBERTUS FERI AGUSTINUS HANDOKO', '198808192025211017', 9, 830, 18, 762, '$2a$10$c5EU/N3LJ1bZi8j86p9JU.gIu7R4dkxkGblT7A6kLNxtXBH6YiC5G', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8674, 'AGUS SETIAWAN', '198808252025211016', 9, 830, 18, 762, '$2a$10$Rb6xlOMM.dqVQ8WGJ8BxFOvlc/lKnc2pXtig2elk2cp446WBQS6Eu', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8675, 'JOHANSYAH', '198808262025211017', 9, 830, 18, 762, 'a', 'USER', NULL, 0.000000, NULL, 0, NULL, NULL, '6287838807981'),
(8676, 'HERMANSYAH', '198809132025211020', 9, 830, 18, 762, '$2a$10$qWjG5ZcLZpCXhZwvjstuUu2OkSEa7EpJrRNWIbC9bCi.Dv.Yp3Hs.', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8677, 'OKTAVIAN ERLANSYAH', '198810192025211014', 9, 830, 18, 762, 'a', 'USER', NULL, 0.000000, NULL, 0, NULL, NULL, '6287838807981'),
(8678, 'FERLIYANTO', '198812042025211021', 9, 830, 18, 762, '$2a$10$.YzyZjI7ERs3jaypCJW0.eO9GdgcgRk0KgTuk9lZcgQMPI6oq8Gi.', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8679, 'DEFRIANTO', '198812202025211028', 9, 830, 18, 762, '$2a$10$5VKTY9UfbuPl4Jw7rdEz5ecsmhSBzkVzDHnsgabipH9ByNsF3BAp.', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8680, 'DENNY PURWANTORO', '198812212025211017', 9, 830, 18, 762, '$2a$10$dtcnv4wYqhCidMphGh2DoO0pwYfhUtzVkRwO4PSNbWk1KMsbn6X46', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8681, 'BUDI UTOMO', '198812302025211024', 9, 830, 18, 762, '$2a$10$obljBJfzI0lWwux22/QowuvfFPTH4giVKY/GwLjFEoZQQf82wzwzu', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8682, 'HENDRI SUSILO', '198812312025211052', 9, 830, 18, 762, 'a', 'USER', NULL, 0.000000, NULL, 0, NULL, NULL, '6287838807981'),
(8683, 'HERU PRADITIYO', '198902092025211012', 9, 830, 18, 762, '$2a$10$ZKGqx5gfcXOhmPelyFboOuWbPmbv6BHV4BuIUl3Xf2Mf2bBJx4ucu', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8684, 'MARIA ULFA', '198902132025212013', 9, 830, 18, 762, '$2a$10$WZI5ZK.Bre7LsrjxQLDnAuPZzvuheJPAMz/pMQilvIVfmpz4Hh4za', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8685, 'HARISEN FREDI YANTO', '198902192025211016', 9, 830, 18, 762, '$2a$10$DNF9HjiD2Y7Jsf48Rxc0IelEi.wv1s2qt6YkzmF9MOnSy3CX.gZ36', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8686, 'ANDI IRAWAN', '198902212025211020', 9, 830, 18, 762, '$2a$10$6X0srRYx0lQD4nTwRwiExetCUXdiPOmk59Tnrw.4UWEFy7w9F0Gdi', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8687, 'REZA PRATAMA', '198902252025211023', 9, 830, 18, 762, '$2a$10$5Y/cev5Syl3bkvXNTiz6mOxaOs8f/qGK7f7PXC95jsrxPN4F5.AXa', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8688, 'SUHADI', '198903152025211024', 9, 830, 18, 762, '$2a$10$ToD2EkBaW2KjJI7mci383.TjKA3Y8ycDSZ6xOWY0lqPmJFRbDsAb6', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8689, 'ANDRIANSYAH', '198904262025211023', 9, 830, 18, 762, '$2a$10$OyVwBTXG0P.qyBob7Gax6e2D4/Urf0XdPyYBZs5K2fyx8MuRM3AYy', 'USER', 'https://dev.pringsewukab.go.id/foto/1753691725994.jpg', 0.000000, NULL, 1, NULL, '0000-00-00 00:00:00', '6287838807981'),
(8690, 'RIZKY PUTRA ABU AKBAR', '198905082025211029', 9, 830, 18, 762, '$2a$10$vGJv7bAcv5tzumSuWotvJeFtS77aEPVahLQKqfsuTz.CM/SFq0EAa', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8691, 'NADIA NUR FITRIA', '198905102025212029', 9, 830, 18, 762, '$2a$10$e7AWoTUHbWaHz2sg0vVC3u4ZllZP5vw5aG9po4R8P9Jxw096M1znu', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8692, 'BAYU PRAYOGA UMAM', '198905122025211027', 9, 830, 18, 762, '$2a$10$IZAoUPPsktBb3OvQDIEp3e0HbNB/Lv.sfyiM18ZUf7XHV/GYMQFvm', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8693, 'HENDRI HARTONO', '198905202025211028', 9, 830, 18, 762, '$2a$10$l4e96/2zV2/E48Hx6Sk6TePZhxn8PQI/PA7kGh/UfODV1pLIuB2iq', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8694, 'YOPI ARIF SETIAWAN', '198905262025211019', 9, 830, 18, 762, '$2a$10$oAfoKBDtIQTCzJCDwXQpve.E3Ifc8i7adBWODLGvGPfjm3UAiHobe', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8695, 'RULI ARDIYANTO', '198905272025211021', 9, 830, 18, 762, '$2a$10$8YuM/zExTbnrvPfUsc9JZeGwxvaAsS04dIssL7IshdV7ZmTOqd6wa', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8696, 'WIMBA PRASETYO BUDI', '198905282025211019', 9, 830, 18, 762, '$2a$10$mHjZ7CBdjFm2R9SRjPWvcOQeTxZXDH6Nj/ZSZAavt7YO6BSZt/Phi', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8697, 'SAIPIUDIN', '198906172025211024', 9, 830, 18, 762, '$2a$10$8T0NiNysRtGXp4aRLnYyV.p6svwg6uCOOLiA2YG6GXUNkF8vZ6jam', 'USER', 'https://dev.pringsewukab.go.id/foto/1753756006888.jpg', 0.000000, NULL, 1, NULL, '0000-00-00 00:00:00', '6287838807981'),
(8698, 'CENDI PUTRA TASTI', '198906242025211018', 9, 830, 18, 762, 'a', 'USER', NULL, 0.000000, NULL, 0, NULL, NULL, '6287838807981'),
(8699, 'AHMAD SUHADA', '198907222025211023', 9, 830, 18, 762, '$2a$10$821m1QNdMD95gIoSjguDbugDAzaFRqio8sAlPUII1oA0cI6II.0l2', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8700, 'MUHAMMAD SYAIFUDDIN', '198908212025211014', 9, 830, 18, 762, '$2a$10$fCmP927zAOd1Zk.CNJcDROv7.IUKJ638UzjcQ5y3N0TO42Isshexi', 'USER', 'https://dev.pringsewukab.go.id/foto/1753696793697.jpg', 0.000000, NULL, 1, NULL, '0000-00-00 00:00:00', '6287838807981'),
(8701, 'SYAHRIR MUSOFA AZIS', '198909102025211024', 9, 830, 18, 762, '$2a$10$I64K.YfnoFbkJlkZjHnEY.uUyzWyim86JcsgotkQCkwnvCusfWP0W', 'USER', 'https://dev.pringsewukab.go.id/foto/1753920593961.jpg', 0.000000, NULL, 1, NULL, '0000-00-00 00:00:00', '6287838807981'),
(8702, 'SEFLIANA WULAN SARI', '198909242025212021', 9, 830, 18, 762, '$2a$10$8rqjq7FoOkD3dKFSCsE9Y./G6wTFydkuQ9ybiWoBUB49R7LMZoq7K', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8703, 'JEVI YOCA FIRNANDO', '198910062025211018', 9, 830, 18, 762, '$2a$10$CT6/Z8BaJR6doslntqOX8OjxgUjq4atixzqFCjBbzv06LNMrN4ywy', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8704, 'RIAN SAPUTRA', '198910302025211024', 9, 830, 18, 762, '$2a$10$VDmq3jhhPBwTYfTvHS8TcO/AygQ0.JzcT2hm4UlLEsf8ToTFnokie', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8705, 'WAHYUNI DEWI MAYASARI', '198911082025212015', 9, 830, 18, 762, '$2a$10$noV6vMmTXQpgYMCnKzfruuqluJMFF9Bha9yVvGtgM5tV72NMPQLzS', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8706, 'PERDI PIRNANDO', '198911122025211021', 9, 830, 18, 762, '$2a$10$IPer0VQyO4JOsmZkgk9F9egcRieFkCaewl8eY21ptV0vsZpS7vOa6', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8707, 'DEDI ARIYANTO', '198912022025211031', 9, 830, 18, 762, '$2a$10$dX2RAOmZAgLW5Rma84FiKeSb3CnTVL8wRTTe6NSdXYFvqQ7KfFGXC', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8708, 'AGUNG SETIAWAN', '198912212025211017', 9, 830, 18, 762, '$2a$10$/KmTP2n3SGqS.iMoo8H0t.EfvfvfJOSm.NaDWvke1ch4oEHzHAuMm', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8709, 'JEPI PERLIYANSAH', '199001072025211019', 9, 830, 18, 762, '$2a$10$p4MEbGQkGqkCqd3R6q8kwek6y6B14k4yg4iZv8fAgdkk0otkp7bY.', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8710, 'DODI JARNOKO', '199001092025211017', 9, 830, 18, 762, '$2a$10$s6wEo5i3x/JLje74LiLtF.0Chgj7dhFXUsLRMBCLUC6r4FUInUaJu', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8711, 'BOWO NURDIYANTO', '199001152025211019', 9, 830, 18, 762, '$2a$10$.Ur01zDKXDeWLH5d0Gz63eMr9eclYhxIV0lLra8wGHbhmTpUk6VVK', 'USER', 'https://dev.pringsewukab.go.id/foto/1753765683565.jpg', 0.000000, NULL, 1, NULL, '0000-00-00 00:00:00', '6287838807981'),
(8712, 'HERIANUDDIN', '199001242025211018', 9, 830, 18, 762, '$2a$10$/XKrjFmTrWXd8zx.DulOKOjSwkROsJk43VuLD1hyf3LBoJpyeeYAi', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8713, 'HAMZAH FANSURI', '199001302025211012', 9, 830, 18, 762, '$2a$10$OaTG2K8id0NjFb9kH3/87OTsvZcF.HgXYC1ed1a3fo3DAzFdnimKO', 'USER', 'https://dev.pringsewukab.go.id/foto/1753153696563.jpg', 0.000000, NULL, 1, NULL, '0000-00-00 00:00:00', '6287838807981'),
(8714, 'FEBRI GANI SEMBIKO', '199002172025211022', 9, 830, 18, 762, '$2a$10$8CDAIsf6aidFFL7p2lFVU.sA5KPCFldfPA9d0Lvo3k8EL3K/A8oEa', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8715, 'BENI IRAWAN', '199002222025211020', 9, 830, 18, 762, '$2a$10$KIOhHDI98wYjb9SFCRzStesYH3t03YZf8INjoocAXcHZlHkoX2ani', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8716, 'NANANG NURDIANSYAH', '199003062025211019', 9, 830, 18, 762, '$2a$10$fQdtx9HnIfyk565s0/sMDu0FimTLEmAjRYCMZ.9Lemk8XYliIwH1G', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8717, 'JOKY MORGANA', '199003222025211018', 9, 830, 18, 762, '$2a$10$KP7nUKi49Imv5cIUzFl19uTD9r/5rKxuS0NLZ9z2o8avkNMs7KFIy', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8718, 'RIAN RIDATAMA', '199003312025211016', 9, 830, 18, 762, '$2a$10$VMOnZUSZ1mmwCjM6bHoiBuy0DuPyL4TLPMPtFo1AJm3IKG2QmlVG.', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8719, 'ARIYANTOSA', '199004102025211028', 9, 830, 18, 762, '$2a$10$LQIZ2p23ZmT0pg0RzoYhUOl8OR/xWY2oAl9wKdXkqT01QQcfn5Z6i', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8720, 'LILIK APRIANTO', '199004172025211023', 9, 830, 18, 762, '$2a$10$yGPUreqtx4cIoO2UvYMR.eBOt1OOD6l3Geo19hGOoB96f4MElEIdq', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8721, 'RIZKY AMELIA', '199005152025212026', 9, 830, 18, 762, '$2a$10$eWCzzdigSkeiI0YYBndocuvHZNWhDuGMy5cCeUAoAadfgQnzWdnKS', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8722, 'DIDIT DWI RAVANI', '199005282025211018', 9, 830, 18, 762, '$2a$10$oDGsnwVJ6HIAbfmsvKI9i.RFY/FA7yIS.yzMgIoSqvOxZ6QfzTsc.', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8723, 'VINA DWI KUSUMA', '199006032025212024', 9, 830, 18, 762, '$2a$10$Wyq.Bp5eQT1pxP4O2ZOcEeSXLVhGTOIsvmEVtwVB16UxFmmaWig6K', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8724, 'HARI AJIZ ZAMANI', '199006042025211029', 9, 830, 18, 762, '$2a$10$nkSuHnYpYAROpJ/v28we7Ot8XIuGcjlFM9ZNgVuSqHoSnK0jQ/feO', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8725, 'JAKA PATRA', '199006272025211013', 9, 830, 18, 762, '$2a$10$ZM9.8GxYXcSfFUD790Cs0e2sTzIw5vJlAXvOLPLjTqot/EM.63BgW', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8726, 'RIO ADI PRATAMA', '199007022025211015', 9, 830, 18, 762, '$2a$10$weE3eFAbLllzZd7oA2zogOIsba68rEK6Vswmpw0vo7nlCA95s/taq', 'USER', 'https://dev.pringsewukab.go.id/foto/1753696843742.jpg', 0.000000, NULL, 1, NULL, '0000-00-00 00:00:00', '6287838807981'),
(8727, 'EKA HARYATI', '199007032025212025', 9, 830, 18, 762, '$2a$10$mzMHc/rXVJUxSJvgCRWOvuG8gYEP5EMkQt.vYcGkW2BKIEFosegDm', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8728, 'AHMAD NAZORI', '199007052025211017', 9, 830, 18, 762, '$2a$10$kGCSWmhlmk9MRgUDN1di6.fxwNz0ykRmC9WKWfHDx.eNbOvfMx9W6', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8729, 'AHMAD FIRDAUS', '199008112025211024', 9, 830, 18, 762, '$2a$10$4lO7f19DPQ3DyYKgFCnlg.75yHLwagYeI0Df8Vu4hhRB2HGjdB9U.', 'USER', 'https://dev.pringsewukab.go.id/foto/1753679638107.jpg', 0.000000, NULL, 1, NULL, '0000-00-00 00:00:00', '6287838807981'),
(8730, 'HARDIAN CANDRA SAPUTRA', '199008292025211023', 9, 830, 18, 762, '$2a$10$/div9QCZf6Lb4J2ieTsIG.4wgjpBGU1axz2BMtSy89DqJEWQS9wjK', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8731, 'SEPTIAWAN BUDIANTO', '199009142025211025', 9, 830, 18, 762, '$2a$10$bdTTua7bRuE7nliqPL2uUeaiNVPypnuPOBYX0hUohreKsSLNdiCQW', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8732, 'ERIK PIRNANDO', '199009232025211021', 9, 830, 18, 762, '$2a$10$oBZNO.Ss02BAZurSrVCV0OgzBgguLc8v6r7iEiiAfRyMwTRNI2aji', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8733, 'HENDRI OKTO RIANSYAH', '199010142025211022', 9, 830, 18, 762, '$2a$10$N1cuRuIOWxy43oOWuVskGex1U.qps4zQFwzxhw5WOvOllWVDDND/C', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8734, 'OKTARINO', '199010202025211030', 9, 830, 18, 762, '$2a$10$seb4xsMu1Z7pcJV04tISNeS7uHZYTJTkkUmAZo33rV4urYshGwlpW', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8735, 'MERISA', '199011102025212037', 9, 830, 18, 762, '$2a$10$Nhn/UGIm42t2elUu8ccAR.ee50kfjPqlr5OlX/0azqcZ6867sI0wi', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8736, 'ANGGA SUPRIANSYAH', '199011122025211016', 9, 830, 18, 762, '$2a$10$8RQXivOCKDfPFw85tEUoj.bIplsoDbXLOGhvfMyx4LUHOorpwoyIi', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8737, 'DELIANTO', '199012092025211018', 9, 830, 18, 762, '$2a$10$LfdHakHhK4xofdkH.q/EhuEhl/ofTKUybDOjnVT.7vgDM2P562Z8u', 'USER', 'https://dev.pringsewukab.go.id/foto/1754275070609.jpg', 0.000000, NULL, 1, NULL, '0000-00-00 00:00:00', '6287838807981'),
(8738, 'HENDARTO', '199012132025211017', 9, 830, 18, 762, '$2a$10$aJcry3BDQPYuokWxMnoqs.hTq/9sniJjI0XMN0XrNbKQ0vlTSFHFe', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8739, 'ARI KURNIAWAN', '199101112025211016', 9, 830, 18, 762, '$2a$10$GMEmgE4vChaubT6FsT390eHqSEHcPgj1XX52gyEKrOZHzqm82StzW', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8740, 'YANDI YANUARDI', '199101162025211017', 9, 830, 18, 762, '$2a$10$6Mdzt7AxvFndIAmETQpoOuWrlgWtn/Rg9AFByl.BbC9soz3eb3WAe', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8741, 'MULYANSYAH', '199101212025211020', 9, 830, 18, 762, 'a', 'USER', NULL, 0.000000, NULL, 0, NULL, NULL, '6287838807981'),
(8742, 'PUTRO GALIH ENGGAL NALARO', '199101312025211020', 9, 830, 18, 762, '$2a$10$1w5eihMDjFQ7P87c8tmfOexCpvbtedd/Qk6ILaAHNdVsLI8tY/L0i', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8743, 'YAYAN FERDIYAN', '199102072025211011', 9, 830, 18, 762, '$2a$10$BmrXmqGZoXF636Y.5K.RUOMVqgXbaH0NN4w0N0Asx0brp1sArQ3Sy', 'USER', 'https://dev.pringsewukab.go.id/foto/1754015756684.jpg', 0.000000, NULL, 1, NULL, '0000-00-00 00:00:00', '6287838807981'),
(8744, 'FEBRI SANTONI', '199102082025211012', 9, 830, 18, 762, '$2a$10$o0vfRWsb66G90x7FMgzBbeIlxKTk3TcQbE61F33VxrqdT1qeQr/FC', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8745, 'AHMAD BUDIYANTO', '199102152025211018', 9, 830, 18, 762, '$2a$10$XUtkorF/cqfGWSl6ZKUzVuUzt3zbspxxbmZ64eEYNeQ2AKKR0TqBq', 'USER', 'https://dev.pringsewukab.go.id/foto/1754269386663.jpg', 0.000000, NULL, 1, NULL, '0000-00-00 00:00:00', '6287838807981'),
(8746, 'RIO AGUNG FIRNALDO', '199102212025211013', 9, 830, 18, 762, '$2a$10$28ClKYZxMymlWNKzRUnpTewo584UJgzoVZfwL8WbqxzdtsNq6371G', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8747, 'EKA ARIYANTO', '199102282025211018', 9, 830, 18, 762, '$2a$10$ikbJnhxB97vh6wUJ.yxk3Ow7VPRCWVsZyEqwMqF4Ej5/6ucFXSHFy', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8748, 'MUHAMMAD MARZUKI', '199104092025211027', 9, 830, 18, 762, '$2a$10$lCWS1ruT/zKizRsSPzkPieg9L9DdSAATO5qqJ9mFJx3KWDz781LEC', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8749, 'SUHERMAN', '199105152025211031', 9, 830, 18, 762, '$2a$10$RG3qtdeIp5Mn7ljS7jutG.JtttbUwzKgip3iUAH3iU7XKvb0NneKy', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8750, 'ARIS MUNANDAR', '199106042025211028', 9, 830, 18, 762, '$2a$10$5XJgm2JxY21sZFhAv6tr2OBQwE5BuGF1HCQ7LAFN0brRMSkegGn2G', 'USER', 'https://dev.pringsewukab.go.id/foto/1754266855312.jpg', 0.000000, NULL, 1, NULL, '0000-00-00 00:00:00', '6287838807981'),
(8751, 'DONI KURNIAWAN', '199106042025211029', 9, 830, 18, 762, '$2a$10$O.z6jbOOTmzQwKsrl.LRA.ZOsfizaDa.ZVVYh6TSmpTJcN25XUo/m', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8752, 'RISDIYANTO', '199107182025211019', 9, 830, 18, 762, '$2a$10$f3I9tkaVE.Sg3BfwTgZ7BePRcOa9Udek3aLUqWEWy4WR5WDXOWKKW', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8753, 'PINDO MANDRAS WIGUNA', '199107272025211033', 9, 830, 18, 762, '$2a$10$Nn9WwUeDJcm7nyYfwH5wqelhcDYI6jLYCjkM1jnKEIa0xiYcqZICa', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8754, 'DEDI KURNIAWAN', '199108062025211019', 9, 830, 18, 762, '$2a$10$ay89k2Bnjoektuca/Cyg8ux1Z74RlS0M468Smng3dVoUizo8p7.lS', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8755, 'AGUNG FERDIANSYAH', '199109132025211019', 9, 830, 18, 762, '$2a$10$qHTTp7KmzKC5do.ZlZ3GyeCGGAw48LG8SFaX9d7DoxuNlDIw1dE1a', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8756, 'VINANY DWI PUTRANTI', '199110052025212025', 9, 830, 18, 762, '$2a$10$wilsXdaBiKA2bob1GUiTreYGBZfKPLcdrxB.BQftlZmxm6/io7skW', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8757, 'BUHORI MUSLIM', '199111162025211015', 9, 830, 18, 762, '$2a$10$tfgdjPvZklVBZpipOnoUweH05DeH3AnGFRvoIGqZb8ZElwQoVQRMi', 'USER', 'https://dev.pringsewukab.go.id/foto/1753663256386.jpg', 0.000000, NULL, 1, NULL, '0000-00-00 00:00:00', '6287838807981'),
(8758, 'ARI MUHTADIN', '199112152025211019', 9, 830, 18, 762, '$2a$10$ZDp5XM7DG8PFOlrl9k2ICu8PqIgCwj2Wyw8qc6AL92IzHsj5Acgym', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8759, 'ARDA NURDYANSAH', '199112242025211020', 9, 830, 18, 762, '$2a$10$AKZxpeeX.Wp9fxn7FYNaQeb6LOfb.sgvNaFVcSdn1E7A8ida1mhbK', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8760, 'ANTON PRAYOGI', '199201092025211024', 9, 830, 18, 762, '$2a$10$FDylN6YJHSZPN6WBFwFREu9NjVU/xZPp45lXQy3kjuaGehKM1POwS', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8761, 'DIMAS SETYA PRAYOGI', '199202062025211023', 9, 830, 18, 762, '$2a$10$1lwuzZ.CDwb0Qjv1mVH9N.gnCqhF66TttSd1zHcN1r9z9mxyEwM5u', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8762, 'FAJRUL ISPANALA', '199202132025211015', 9, 830, 18, 762, '$2a$10$kjs8W6.JWjIAse3wCA26ouPk.gl1FkpqKo9vt1c6ga4sQtZFgFSPq', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8763, 'NELLY FAUZIYAH', '199202152025212027', 9, 830, 18, 762, '$2a$10$T4h1G4MQzorE0JQBo2HNo.MYIbMOUXnoD3kUBxj/lWtSYRtsu/3cS', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8764, 'CHANDRA ERIYANTO', '199202172025211015', 9, 830, 18, 762, '$2a$10$9R.EabnMSbMxQ2AhJcrB6OTYKNY32ljHxlhuA0NglXZSsSKIVtWy.', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8765, 'ANGGI FAZHAR.AS', '199203092025211022', 9, 830, 18, 762, '$2a$10$gbijycgaILMa5.WwijLxf.DO4xnq6wFxiFEMQQAmhGXdi6Lg.DzYi', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8766, 'RIKI ROMADONA', '199204012025211017', 9, 830, 18, 762, '$2a$10$tI.w1gNQ4rGYnSWcv1Fo1uXUc5SDFf.paCf80p8nDJLrRA8q0NAqa', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8767, 'HAFIT NUGROHO', '199204122025211026', 9, 830, 18, 762, '$2a$10$vkwAbgtR/yhtuZtQaPTbWe1VWVIluJKGez9X9rqegGvOvu.k9PbzW', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8768, 'ZAILANI', '199206102025211025', 9, 830, 18, 762, '$2a$10$9hKQbibNPacUrFA5b8nRj.3JyHEdTcikHtJFRGa23gaNgsMh7HgPK', 'USER', 'https://dev.pringsewukab.go.id/foto/1754029114056.jpg', 0.000000, NULL, 1, NULL, '0000-00-00 00:00:00', '6287838807981'),
(8769, 'SUPRAWINATA', '199206172025211026', 9, 830, 18, 762, '$2a$10$nmrRQB1KfTmrcJpDK9IBxu.JQq5WMNDAXpH4hpg62Qr/ZvTxAq7S.', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8770, 'FAJAR SUSILO', '199206212025211014', 9, 830, 18, 762, '$2a$10$OFbl4I41uL/dCnPBypZFaOEsN8DDe91hLLohnuuXUGJywhebHTfmm', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8771, 'ANDI FERIKA', '199206242025211020', 9, 830, 18, 762, '$2a$10$9d/2vbywEBfmDSKbd7OsTeCpcDluJfxM6Ga/W9toKMmpZUDca//Fm', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8772, 'RIZKI SEPTIAWAN', '199209112025211019', 9, 830, 18, 762, '$2a$10$GXUI11RRx9i1NTHwCpbGDe1CUnS80FZU1ZzJ5pcpAx5HfsSAjPIHe', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8773, 'A. EVAN LIBIZA', '199210022025211021', 9, 830, 18, 762, '$2a$10$hrHhmKrUAbiCy75WHJ6s2OJonPYJPqvZZRUDBxq37N8RGW4S5EeA.', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8774, 'ANDIYANI SAPUTRA', '199210092025211018', 9, 830, 18, 762, '$2a$10$FuFAvtaL2CRggG2AXHNVEee7P/QFhYjhORH3mFte0HB/CWSlKOQDK', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8775, 'IMAM WAHYU SAPUTRO', '199211132025211015', 9, 830, 18, 762, '$2a$10$7vLnmKq5goF2gXhYY3a09eNq6043HqyYHIiFtq./2Vhzzfmc1tHNq', 'USER', 'https://dev.pringsewukab.go.id/foto/1753588801014.jpg', 0.000000, NULL, 1, NULL, '0000-00-00 00:00:00', '6287838807981'),
(8776, 'HADI SUYANTO', '199306022025211014', 9, 830, 18, 762, '$2a$10$TUaaCCyej0bQq04sOyrL8.lny1Mp8gjKOMBw4J1dXzZt7AKDR7uuS', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8777, 'JEPRIANTO', '199312112025211017', 9, 830, 18, 762, '$2a$10$ELqJ5i0AZyb3xfHgSaYz4eeDFb2mRDpK0v/HFFIC0EMvZBJ1TgFFG', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8778, 'YOFI WIDYA', '199401162025212010', 9, 830, 18, 762, '$2a$10$vEDImSBvGNU9h3lzxZ2Taeb70RxUJZP.KUR96ujXFduo/zMihHJ/a', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8779, 'ARIF BESTARI', '199404092025211015', 9, 830, 18, 762, '$2a$10$cjQ1qU6kTxj8/aJab8pSTeb50i.rZHea.vcUMzRkCI/kz4zApTFRm', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8780, 'RISKI SETIAWAN', '199511092025211012', 9, 830, 18, 762, '$2a$10$5zj2c28DsOXWfxhjyPofqO3c7uizbcQgVDiX3MpXyIrvwEr2D2JCG', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8781, 'RIAN KOKO KARNO', '199605132025211012', 9, 830, 18, 762, '$2a$10$JRWcSWDWd32Jgdqt9teBmeGANd3RxMR1RPPsiNF.kO7edpOU8Ue7S', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8782, 'SITI MUTMAINAH', '199706232025212020', 9, 830, 18, 762, '$2a$10$Gx/dyyQNviXdsfNQit0sgeknr9SoinS4/x89wZPt3grmAaMJ2F9.6', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8783, 'PURWO SUWITO ADI PURNOMO', '199803282025211012', 9, 830, 18, 762, '$2a$10$kVYgYgYQTzSjH8y5/ZRVsuuLQ9cw5CjXa4PxBFUMOTy6hVMJh8mvq', 'USER', 'https://dev.pringsewukab.go.id/foto/1753835711759.jpg', 0.000000, NULL, 1, NULL, '0000-00-00 00:00:00', '6287838807981'),
(8784, 'MAULANA USMAN', '199806272025211010', 9, 830, 18, 762, '$2a$10$kYwG6fvr.MLJ996visVhsuHPCXFIW3Nv2PJwp5WaRXeES44.xYMwO', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8785, 'MUHAMMAD AGEUNG GUMILANG', '199812042025211007', 9, 830, 18, 762, '$2a$10$v6jbi4nByAUiDZ7auO7KEepTXHZPuyhd7X.awOiqAObjyUn4FW.w.', 'USER', 'https://dev.pringsewukab.go.id/foto/1754039583196.jpg', 0.000000, NULL, 1, NULL, '0000-00-00 00:00:00', '6287838807981'),
(8786, 'GEGI OSAKI', '199912092025212011', 9, 830, 18, 762, '$2a$10$sY7CsMqbkf27sCck4T1L1uI4ZdM8djwnZ3GziOB2Wwj7zkO1FjhoC', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8787, 'EKHA FITRIASIH TD', '200003222025212004', 9, 830, 18, 762, '$2a$10$RixKJS62l41iH2wGHF93gOmxOK2MBRxaF/2KGcN6/kDIVHuJKoowW', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8788, 'RINI FITRIANA', '198307112025212016', 11, 831, 23, 202, '$2a$10$AqJFUVi2XZGJmx2ZrDd.ReD/xar19C0h.3IY8hxKVZQlZNnmrlJ1m', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8789, 'EDI SULISTIYANTO', '198404042025211026', 11, 832, 18, 202, '$2a$10$Mh584P.GCjK0S3IThzbH7uB9iQ0uGlunD5jrXTOqwu83ZsN5CrOE2', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8790, 'ANGGI NOPRIANTO', '198711222025211017', 11, 832, 18, 62, '$2a$10$6aL1a/6pfnIQshcHjp5DzOQYJANOnyuBLUrRe462lCXfGo8pq0s6i', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8791, 'AAN APRIYANTO', '198604222025211022', 11, 833, 20, 8336, '$2a$10$SYQpPS9Ou9asEQrxOEPaJ.ZeV2ZzaFe0Jl5aoTP7i0IC43u10OOaG', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8792, 'TRI MEGAWATI', '198205042025212019', 11, 833, 20, 8336, '$2a$10$8stTcfMGcILDmoIeBc7q7e4glM7b9TwcuwgC2Tsn/.aNQTsRAbeaS', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8793, 'SAPTO NUGROHO', '197310042025211010', 11, 831, 23, 8336, '$2a$10$xeSx1Jfi/UYRUAsT0jDy8.zs3Eg4RQkRCs7EEcfllB2SWnoH6VG1q', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8794, 'NURWAWI', '196907192025211005', 11, 834, 22, 202, '$2a$10$CiZq0Xh5vbXtys4TjIVynu9BUQ4km/FJwPQH5v7YAiiHriDonv8Jm', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8795, 'NOVA AGUNG SUPRAYOGI', '198311242025211011', 18, 835, 20, 359, '$2a$10$Q2/IG525Bk21yuXs8uehTO4N3la5FEbDiLTLRzGCY/.3zMpZ7BAr6', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8796, 'LOLI ANGGITA', '199103272025211018', 18, 835, 20, 359, '$2a$10$y3tjRaMhggf5A34JptclxumFurP/6nBZufKSJ4Y1inmoRE/ScavBW', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8797, 'SUTRIYADI', '197310182025211006', 18, 836, 18, 359, '$2a$10$.cMhxH.lrvAW.GvW9.qGx.rkpwSAnJhxNNr1nlnCG7ZJl9w0z1tfC', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8798, 'JONO', '197201152025211010', 18, 836, 18, 359, '$2a$10$/5F0rQCRI02d6xi7sNMDyOoiYdfdMkR0MbP4b1vQFiBl0wEibnZ9O', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8799, 'WAHID SUHERI', '197703022025211016', 18, 836, 18, 359, '$2a$10$3uX3BM0G0B.xTyuxskIVwebhDRjDXCXfk0Z2GSRZqY2yk8KpfOiCC', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8800, 'JOKO SUSILO', '198612262025211019', 18, 836, 18, 359, '$2a$10$BAYJD7EEtK/aAV4vWDwQ6e0vw7zWmXbYyhybtQ9LYKDS006tspzAK', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8801, 'YANUAR EKA PUTRA', '198606102025211029', 18, 835, 20, 360, 'a', 'USER', NULL, 0.000000, NULL, 0, NULL, NULL, '6287838807981'),
(8802, 'JULIS ADI PRABOWO', '199107282025211023', 18, 836, 18, 360, '$2a$10$8z9.GSK8dSSNUoMUsNi3huSb8sebuwKGtojCWpbKenV6/IhdjZLO.', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8803, 'SEPTIAN ARIANDI', '199109212025211018', 18, 836, 18, 360, '$2a$10$qN.2qJOIDsbMyJDo2OmeVe2DOc.VS.Y0BftbQ4Ma5opRRnvPFB3KO', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8804, 'MEDI HR', '198103122025211021', 18, 836, 18, 360, '$2a$10$twDsX8Go8UFSsPn0j959Me/u.x6RzjNPYPW.CA2rDXxDy82PdyUim', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8805, 'PEBRI HASTONI', '199102202025211014', 18, 836, 18, 93, '$2a$10$B8hhK7EbjboXGCQrSM8Gw.a8E96REU2VuWRGixjq/Rsp50kcmaXIK', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8806, 'ROBY ANGGRIAWAN', '199005152025211029', 18, 835, 20, 95, '$2a$10$pZ8XXqBJeLRMF3atMUhGt.0eudb.VLDsMDtxnY8XMQi5FkbGGlk9e', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8807, 'DAFIT IRIAWAN', '199505282025211023', 18, 835, 20, 95, '$2a$10$wfM/.gaSJmxTIb36wWiQweZrymfeIwylJ/1MkRKZz5wM18qLxPsWi', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8808, 'RENI WIDIYA WATI', '198702232025212019', 18, 835, 20, 95, '$2a$10$a9o6ozKKUGnTFOpUIpr6s.NZmqN9dC7xw2CVLv8KYcTFYInZafJgW', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8809, 'AGUS BUDIYANTO', '197908102025211024', 18, 836, 18, 94, '$2a$10$CAB5hn9aQ3ytGRWuvJuPSOtJ45s6Q4L9rKVcoErPbNKrZlEJ65uma', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8810, 'ROHMAN SIDIK', '198801102025211020', 18, 836, 18, 94, '$2a$10$h/xNoRsUfoe/GSJfRAVPEekubXfcsOyu0K9H8VMU2EAKxq4s55xWS', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8811, 'ZULFIKRI', '197405172025211012', 18, 836, 18, 94, '$2a$10$XsTPgzQ92wmEVeWvNgiGGODCneni3h5Hb185aMegsypdsr7i/VqlG', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8812, 'CANDRA GUSSATMA', '198708222025211022', 18, 836, 18, 94, '$2a$10$9MmnvU3sznSMhZiPh0LUU.P3sb2N1kJXG.xX7m18Dg8nikGc48Xjm', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8813, 'AHMAD RONI', '198111092025211016', 18, 836, 18, 94, '$2a$10$sfLzDgYZNGHJP0Pv0PN66OFi3RhdVETt6zyj4OmeLjMnTq43XTd/e', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8814, 'IMAM BAYU PRASETIYO', '198510152025211026', 18, 836, 18, 94, '$2a$10$qyeRJiXXDZpp4ElIe6QfcOI2FXHGwHmz3E9.m2CsJaB2AOCMkGH2K', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8815, 'SUMOKO', '198110122025211028', 18, 836, 18, 94, '$2a$10$3n4zUKJije.DoOxrXzvXuOl7.pgjzRmdS89XxWxl85u.oEnr30Yc.', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8816, 'ISMANTO', '197902202025211007', 18, 836, 18, 94, '$2a$10$ROiwkCDIWctHmgFVHQ4Uhe96odmygFoFB5FwjlAh9k.jUjK2rkale', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8817, 'AGUS YULIANTO', '197907142025211020', 18, 836, 18, 94, '$2a$10$i26SG9SxTdYNcntC4gLOeu0vK2r77jgtykHUC8fiBFA8mN5WyePzu', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8818, 'ARI PRAMONO', '197803062025211012', 18, 836, 18, 94, '$2a$10$s7wzblDybQJiPhPb89BImupGn01E6cLnqctKmnwW1jrxNabz/3Bhy', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8819, 'EKO APRIYANTO', '199504132025211022', 18, 836, 18, 94, '$2a$10$X6ZNQ6cEsA7FfATBtWIkvuVP4JUTyHAl8c058xgrzeF6tzjZTFOMm', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8820, 'WAHYU IRWANTO', '199005062025211015', 18, 836, 18, 94, '$2a$10$PrHrb6cFmbkB6RRYZYn2xu5cUy/OTwgjTuVyOaKYys7aK6k9AELyK', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8821, 'HARI WAHYUDI', '198603312025211014', 18, 836, 18, 94, '$2a$10$bV85QtoGnij7IKsv8BbefutgVcF9/ppHb5vmIF7RlY/PncaCXNn9a', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8822, 'AGUS PURNOMO', '198508172025211028', 18, 836, 18, 94, '$2a$10$rqgaNEOPb7V5gWBuwtNsGe5sCBZwqczipbdp1Hjf9czccxYyniT7e', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8823, 'CERLY NETI ENTINA', '199106142025212020', 18, 835, 20, 96, '$2a$10$4yAwVq093GBmOxmzHhZZveiQi2XQG.MGbP3QKdNjCiHm9SzdZ/auO', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8824, 'MUHAMAD AQIL PATRIZA', '199410082025211014', 18, 836, 18, 96, 'a', 'USER', NULL, 0.000000, NULL, 0, NULL, NULL, '6287838807981'),
(8825, 'MUHAMMAD SYARIF HIDAYATULLOH', '199105032025211024', 18, 835, 20, 97, '$2a$10$HdoVUyeOMx.vFHFhvO1DROjxB1tZHn15Cj7O73OBfu/Xa4jlBgSFW', 'USER', 'https://dev.pringsewukab.go.id/foto/1754464577400.jpg', 0.000000, NULL, 1, NULL, '0000-00-00 00:00:00', '6287838807981'),
(8826, 'GUNTUR FEBRIAN DEKA WICAKSANA', '199002072025211017', 18, 836, 18, 98, '$2a$10$IMvW1QvG4sosG6ZYHsX5Se5N70xuOMETVeyZj1V9IrkgB7Ys5ZsQe', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8827, 'HUSNAN YAHMIN', '198004242025211025', 18, 836, 18, 357, '$2a$10$aX9XmCarwCcu/qfXj5vXCu2YAUWJ3ERKYtkIxArvHYB9uhNqU6PMS', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8828, 'PRAMANA RAMADHANI', '199104052025211027', 18, 836, 18, 371, '$2a$10$1ZNSazQfpG4UPf.847ZUmuZj96v9Kdj4/ynMK9xStll8fLlMWdfSy', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8829, 'DINO PRASTIO', '199207092025211029', 18, 836, 18, 371, '$2a$10$G4CilDvCR3BKkWcsIdhuqeDpQKbtRLyNdujEK93iWw1K.8oWuoe2a', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8830, 'CAHYONO', '198306092025211022', 18, 836, 18, 371, '$2a$10$wXVlAUix0K6zJXjrGpGxMezz.a9MViCZ3l4w4E0Dt37JG0uou0TI6', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8831, 'SUKANTI', '197408202025212007', 18, 836, 18, 371, '$2a$10$JFjW0Iqe8ycUSv991S0TI./ZcGmnv32OO61AE0vhT8Qa3AKyJCcBm', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8832, 'DENI KRISTIANTO', '198203292025211010', 18, 836, 18, 356, '$2a$10$glm000Yw/HMyI4ie9LHHaeIXGBEddk7oNzsFwEQ/Xu0aGWuWgQa8G', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8833, 'NURUL PUJI RISDIANTO', '198503132025211022', 18, 836, 18, 356, '$2a$10$Jo61f5wxZxbYLc.9bEi9ROkjEysO0G7NuBsznMrwR31G41Fbc1r0u', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8834, 'YOSEF ISKANDAR', '199602262025211013', 18, 836, 18, 356, '$2a$10$atIKUBRZ5A2s9hjl4lI2k.yFTtoGxkp8oEmwX9T6QxEbdGEz3E.BC', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8835, 'SITI SOLEHA', '199010172025212025', 18, 836, 18, 356, '$2a$10$1CoMgXQNM4I5UNvZz1KII.ZH3SJW5gHqUmw.13pWb3cIeu3xgoxm6', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8836, 'ZELANDA BETA SEPTIKA', '198909102025212035', 28, 1844, 20, 659, '$2a$10$lO2Cah3gtysk7b4LORAjoehPmJrb1EJChT67MsyGlOAT.R.TTXLq2', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8837, 'DWI LINAWATI', '198503072025212011', 28, 1844, 20, 659, '$2a$10$9kgDH350kUKQ/zM9EBHgpOpyZbGyooZuLpc/s4uM1nXIroFSyajwi', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8838, 'YULIA SANTI', '198407202025212018', 28, 1845, 22, 659, '$2a$10$3neDyDWZb4u763p1uaHCJucjqQdH5es3iQPwuIr1jFRseD9O.O6ZO', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8839, 'JOHAN BARUDI', '198001012025211051', 28, 1846, 23, 659, '$2a$10$lrZ4tCFFK/ds8/qIA9W8A.VOHF8bhjRcicFc6K53g4PiPPBV0V72q', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8840, 'ADE FERNADI', '199112012025211013', 24, 837, 18, 124, '$2a$10$VGTr4vPLIyt5uZCFcCqyuu2e4n55xa5fV1u4vNSR01gDq6fpy8FYK', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8841, 'SEVI WAHYUNI', '199209302025212020', 24, 837, 18, 124, '$2a$10$Vu4L2liVDrNqAqyxNRio9eYR1tvgr2mJRbrnOpf0H8o88zviqkEOu', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8842, 'BAYU ATMAJAYA PUTRA', '198705282025211022', 24, 838, 20, 27, '$2a$10$E8yPb2dpLBz4fEz5h2z5u.TYZ3omZcLSpLhxwSiINN2eHBuzsXf.C', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8843, 'INDAH SETYANINGSIH', '198108192025212013', 24, 838, 20, 294, '$2a$10$ty5GgQvwX8X2q1VG5KV1h.5neD1TTzIFhVamnBRRqj2G3KtyhHunu', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8844, 'AGUS PERMANA', '198508062025211026', 24, 838, 20, 904, '$2a$10$00xzfcwwihvNkRkTyHSjK.GGuOHYgOuSo/U64sBc7.MlNHd0NB4R6', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8845, 'ELSA ERLIANSYAH', '199309152025212019', 24, 837, 18, 904, '$2a$10$4RTqhMFrAwZBv6cUsR.K6O.Z7L6jUyezdE4umWaK08l4D9Ybhwujm', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8846, 'MAY PRIYADI', '198105152025211025', 24, 837, 18, 904, '$2a$10$/YuipikZc6m1XmYUR4t74uoyKl1iUbTGQVpL09exC5AAC/wlyYH8e', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8847, 'MEI EKAWATI', '198605052025212036', 24, 838, 20, 904, '$2a$10$NyAj/LBCLzx9X.N/nmnNvOscMdEOS/wp2bUL4vvj8ScTFkSPPFt7a', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8848, 'MEI SUSANA', '198205082025212011', 24, 837, 18, 904, '$2a$10$I1aLekAcD4RFuEAuldHSYOGR96ujZfcac7LYFgCAaV.RCvjdaDtYi', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8849, 'REMBA DISTIYANTO', '199512232025211016', 24, 838, 20, 294, '$2a$10$qNLjs0/YZCwT3El9hbZUvuU5ei4.iG/BwetTRM8kkCSraunKVpoxq', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8850, 'DEWI PUJI ASTUTI', '199110282025212028', 24, 838, 20, 294, '$2a$10$rpFUbpTGDSBUO84EuOS7Ze6qrs0rhVmYECL4iCau/J8wHcsRDXY/.', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8851, 'LUCI FATIYONO', '198410052025212024', 17, 839, 20, 877, '$2a$10$lSlDiIFTMlh7LdFd31zcouFeUwW2iNMqwg0u/k4uxWFDLl0Y8SPaC', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8852, 'DESI RIBUANAWATI', '198812312025212042', 17, 839, 20, 880, '$2a$10$atI0pLxCigmCYpvrU4I43e7kz9wVnjZgq5tfHO82sjja/1y3lFRH2', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8853, 'LISTYA DAMAYANTI', '199212082025212028', 17, 839, 20, 880, '$2a$10$vOCjKqWNhEMUOOXDVIJ0q.ERgmQOSCBGUNrmhMJrOYhi.IPeo0/Za', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8854, 'IIS IRMAWATI', '198202022025212030', 17, 839, 20, 880, '$2a$10$RZhm99TqyQgY/mJDRkUfeuxy3.4L2n6oBilzq7tnOxs5DRmHss4o2', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8855, 'MEGA FITRIA RACHMAWATI', '198705312025212012', 17, 839, 20, 37, '$2a$10$ChvVry0qmVl.5G49wqnY2eBGkAXyNa.Wle5rtzomWLBuy8oSjx.iK', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8856, 'ARI PRABOWO', '198410222025211022', 17, 839, 20, 90, '$2a$10$GnBQSJ6yoaWko4eW9N3swugZkmjMRCx8mQ0qxh4z13HlDcx6CXMzK', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8857, 'INDRA WIJAYA', '198012272025211021', 17, 840, 18, 877, '$2a$10$E6xoG3DnHyhHJCA9O2dbNusdDxRIIrrGHwqTFfMeeJY0r3CS.YEZS', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8858, 'RULI SAHADI', '197903262025211012', 17, 840, 18, 37, '$2a$10$zkwGk5naxZ3Y0QBOdRmQQ.nUmOkYT0N8I/0sv8wE7RpDyakQX0Tmu', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8859, 'ITA WAHYUNI', '198304302025212015', 17, 840, 18, 37, '$2a$10$AWxcYHDQFOEecBoafBK55OgLyw7OEZOqFNmz3KjWBQsbDyJo7aGr6', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8860, 'DENI SAPUTRI', '199112072025212024', 17, 840, 18, 90, '$2a$10$vbFbSYryLDK3wbSnukhuj.AApjZDRdw7RIo/XlSSevU8wiyIBWnpS', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8861, 'DEBBIE PUTRI RULLYSCHA', '199212022025212022', 17, 840, 18, 90, '$2a$10$z8RWA4pPzfnbbE.gCx0NDOpzLdzyTUvH.VzDCa2Wf6Wae0KcEMtqm', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8862, 'DWI ANTONI', '199001212025211015', 17, 840, 18, 90, '$2a$10$X3mMbGRt1YGpHyCBkm4qHe4aAw2dgCRP3MsMLEqCdLc9NEiE5G8t.', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8863, 'TURJIANTO', '198608152025211023', 17, 840, 18, 90, '$2a$10$akvMExm8zUSBPX7fhmIHIuXQho8iepmWlV8AUGVzJZ2iKXhuofTGO', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8864, 'DEDI APRIYANTO', '198804122025211021', 17, 840, 18, 90, '$2a$10$0MDxP4i1NTxcNaDyyGzZ.uOVAG8CAaNjl8hhyirhwISlsBc3IzIMm', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8865, 'AGUS SUPRIYO', '197608242025211012', 17, 840, 18, 90, '$2a$10$HbN7x3uSEzbxJUlhrelwf.jG9cJo7A3T7d/bg.K/PZJGPo2ah6iTS', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8866, 'TIMOTIUS TRIANTORO', '198201272025211009', 17, 841, 22, 90, '$2a$10$u3xeWDM5O4ZMbPFIR5BYyOTipGAKkUx0gwSAOkB4p5RshbS6mCKxC', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8867, 'ALFAN ASHARY', '199210222025211015', 17, 839, 20, 37, '$2a$10$r8v99HQCLjHG.n87DNMHJ.dnPTMImBjBLymdfUaTGNiWfmeEsJ9li', 'USER', 'https://dev.pringsewukab.go.id/foto/1752555408338.jpg', 0.000000, NULL, 1, NULL, '0000-00-00 00:00:00', '6287838807981'),
(8868, 'NURWAHID HIDAYATULLOH', '199411092025211022', 17, 839, 20, 37, 'a', 'USER', NULL, 0.000000, NULL, 0, NULL, NULL, '6287838807981'),
(8869, 'WAWAN HARYADI', '197905302025211015', 17, 839, 20, 37, '$2a$10$PP.bSIJgNF7NfLKxR9zrlezslqyIE5SGMxKWEKnmKV4tDkGaYjPhO', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8870, 'DERIS IRAWAN', '198712292025211017', 17, 839, 20, 37, '$2a$10$pIDGUM4WGmAwHUa0EZwR/.eUQ71KZCWW1K32lf1ygtJGzMZYobVsq', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8871, 'PANJI KESUMA WIJAYA', '198404072025211028', 17, 839, 20, 37, '$2a$10$Cw/cu947TJXiek3OS1eLy.VPAT2UTj/rTw8IOqHUI6swKj1blWVKW', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8872, 'DEDI SANTOSO', '199212122025211025', 17, 842, 18, 37, '$2a$10$/LozyM6wWRlmQYu8U8IYZuYHE4X8dKzT9x9nEGeY51JAUdZC7tUt2', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8873, 'HIDAYATNO', '198407312025211010', 17, 842, 18, 37, 'a', 'USER', NULL, 0.000000, NULL, 0, NULL, NULL, '6287838807981'),
(8874, 'FERDIANSYAH', '199003102025211027', 17, 842, 18, 37, '$2a$10$zq1WDdPqTZcnNYIjnE9WKOAGlkapo4YGy9.uGpcflSLmPjCTZyB62', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8875, 'HERMAN EFENDI', '197007172025211009', 17, 842, 18, 37, '$2a$10$iL4LdcA8gNd3o.I9sjr3JOjShI9Cxp.2KqbYpuFv7Bv11zEI3BeZC', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8876, 'DERIS', '197003012025211011', 17, 842, 18, 37, '$2a$10$trIKVv35bCEehqWlDEVt.unKbEMRLV7c5Rkh73cI8NIOhxmPTLa8i', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8877, 'RIZKI KURNIA', '198210202025211024', 17, 842, 18, 37, '$2a$10$lYDKZayXgcfcX4bnaan6M.p32perQRY01ujFfhzt8PLviC9vLDxC.', 'USER', 'https://dev.pringsewukab.go.id/foto/1753066972573.jpg', 0.000000, NULL, 1, NULL, '0000-00-00 00:00:00', '6287838807981'),
(8878, 'SUPARMIN', '198211172025211020', 17, 842, 18, 37, '$2a$10$wmD3oxjV57Y6.t7qrMQ97e0ywwpFrHjswkpacM/ravsR4rWPPnHHi', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8879, 'FERIYANSAH', '198910202025211022', 17, 842, 18, 37, '$2a$10$yHKQ25cdlBemZVZlCWUA8OXb3dIQsW0rxnhYA/RKhOmZTCZFhEPQC', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8880, 'ABRO RISMAN', '198801282025211022', 17, 842, 18, 37, '$2a$10$ViqAC8yeQLG/V2.yIEOzEelCGUu39IcQzIzwJJZmlURng8FDupu3i', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8881, 'YAKUP YANSAH', '199307042025211014', 17, 842, 18, 37, '$2a$10$U9xgl10xGNTAERMhz0RgZ.zdLbjunkPcW3LWJl.dk5xgfCKaCyyy.', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8882, 'HERI ARIA UTAMA', '197605182025211012', 17, 842, 18, 37, '$2a$10$wBqVIuNzuCQLGzEpMQLRS.uGawA9vUXWLInx2BUgFKLxGOiQzE9wK', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8883, 'WAHIDIN', '198206022025211025', 17, 842, 18, 37, '$2a$10$2KvQsiyE2BH3IztRLuOc7ujGN51HoYJPJo/crwbfA7t9ul3up4Wq2', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8884, 'DASRIN', '196905272025211004', 17, 842, 18, 37, '$2a$10$yjd/nk9tVsEN79yjAijLWu1MKrW7ZSgA36YuKlrflE5/i4mJrYvde', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8885, 'ARSUGIANTO', '198201162025211015', 17, 842, 18, 37, '$2a$10$2aGLIWU6eAzm1uzNGc9Gd.DZd0eI3lsSx8GU1oi5GUFWu/G6stN06', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8886, 'EDI SISYANTO', '198603082025211021', 17, 842, 18, 37, '$2a$10$gwwgsWsM80sFaZBlo9Fa/.Zfb5MZ3VfuW4AUNAM/AxY7CDVOWE4m2', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8887, 'RIYADI FITRIYANSYAH ', '199004242025211030', 17, 842, 18, 37, '$2a$10$PDMgwWX09pq43kFvDXi09eBFIIcsgkRjW1gvaX6QLvrd0QZ5ZbFuy', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8888, 'SARING', '197305162025211014', 17, 842, 18, 37, '$2a$10$mbeYZ/6PhQjkZ0e/vvteg.Eud4RBK//Klf4S/8oKKH80bblwxJA6m', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8889, 'ISMED HASAN', '197404042025211018', 17, 842, 18, 37, '$2a$10$sq1hTUJXunF5nmcGBsZzgOe.tNXMOAZQzPT/r4CYbzwoYjZMDBcA6', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8890, 'PONIRIN', '198707152025211027', 17, 842, 18, 37, '$2a$10$bI2Ti1njcW3szXqdDv.tDerR.mXvwyVKobBTZaXWul5jQFRpqh95i', 'USER', 'https://dev.pringsewukab.go.id/foto/1754268332391.jpg', 0.000000, NULL, 1, NULL, '0000-00-00 00:00:00', '6287838807981'),
(8891, 'YASIR KHOERI', '198411052025211016', 17, 842, 18, 37, '$2a$10$118VsFeAEdZ/oAm2inW0yOxjG.KRYI6beFdWWNsLMd6E/.VS8ZYti', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8892, 'EKO SANTOSO', '197709202025211004', 17, 842, 18, 37, '$2a$10$4aEVCh.brTJAr0K1oaFdR.9xr4UnSD1hcegHlRVOSZEEy1RMplK.O', 'USER', 'https://dev.pringsewukab.go.id/foto/1753142781897.jpg', 0.000000, NULL, 1, NULL, '0000-00-00 00:00:00', '6287838807981'),
(8893, 'AFRIDO SAPUTRA', '198510292025211009', 17, 842, 18, 37, '$2a$10$QUHIkxsS5xGYkoTlyv.L4eWJfcSFwuk9jbn/9/mbrQ2K6b1LaG6Z6', 'USER', 'https://dev.pringsewukab.go.id/foto/1753057603706.jpg', 0.000000, NULL, 1, NULL, '0000-00-00 00:00:00', '6287838807981'),
(8894, 'AHMAD RAMADANI', '199303092025211014', 17, 842, 18, 37, '$2a$10$LAPNLSylh4WpLHIUAkpuFeXVjPnYOxdZnHcGgr4NKK2b5mjqNWIbi', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8895, 'WAHYU ANGGARA', '199003182025211022', 17, 842, 18, 37, '$2a$10$JmgdVQWf13PyN3B5kacExe8c9hAvt38ECrANMxfCAMmiw3.bEc.wO', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8896, 'JULI WIDIANTO ', '199207252025211017', 17, 842, 18, 37, '$2a$10$WLLyygcFEicVOvu/Sw05OOhC0Qz5l6LddD5R.Lr8OQJqrO90djbF6', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8897, 'DEDI SETIAWAN', '198808122025211033', 17, 842, 18, 37, '$2a$10$ufSpkTGyxKCwYDj6siHr.OeEsOJ/WJsfAOqXghx9tIFhcEFUrW1FG', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8898, 'DESI WAHENDRA', '198612282025211018', 17, 842, 18, 37, '$2a$10$Vvm.eS1TT2AdgDJnT9PSye4wId3z1.SQHTDP2lowc7X9OiE11Wznm', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981');
INSERT INTO `pegawai` (`id`, `nama_pegawai`, `nip_pegawai`, `opd_id`, `jabatan_id`, `pangkat_id`, `atasan_id`, `password`, `level`, `url_foto_pegawai`, `tukin`, `edited_by`, `first_time`, `created_at`, `updated_at`, `no_whatsapp`) VALUES
(8899, 'OON SRIYONO', '197603122025211017', 17, 842, 18, 37, '$2a$10$f5FLhti0sgOLewzFe/B2B.D.5NGb4vVyUHpOcSNM4rC6hLUxlj5vu', 'USER', 'https://dev.pringsewukab.go.id/foto/1755820970911.jpg', 0.000000, NULL, 1, NULL, '0000-00-00 00:00:00', '6287838807981'),
(8900, 'MAHFUDZ', '199001152025211018', 17, 842, 18, 37, '$2a$10$V0lLRIn/PQi1fFUqXtRK3OXS/vkw8BObk/KC/ObmWu/LCvtu7kw5G', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8901, 'ANAS MUHLISIN', '198908282025211026', 17, 842, 18, 37, '$2a$10$tuSc2ms.zW1G6IoXMn5vnOGKsXOLi.l9/f5bvs.5WZ3wRtcHy211q', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8902, 'ASEP USMAN', '199208202025211025', 17, 842, 18, 37, '$2a$10$72MjEVqSE70z9Fsf3GZiWu1Fu21K0hf13T3.pjtW9Jds92N4QwgvS', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8903, 'SEPRI', '198708012025211025', 17, 842, 18, 37, '$2a$10$1ViDhqGylukPhSxcnQE8dO7RMy/DekC/G.QIs.zHIdqe/z4V7WOK6', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8904, 'EDI SUSANTO', '198209302025211014', 17, 842, 18, 37, 'a', 'USER', NULL, 0.000000, NULL, 0, NULL, NULL, '6287838807981'),
(8905, 'GAGANG GUNTORO', '199102202025211013', 17, 842, 18, 37, '$2a$10$/YNayvT9EH04vD0aqAmpoeAop.Ike1IX/KfdaTT/N/NcJa8SPhJp6', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8906, 'MASRUDIN', '197204042025211011', 17, 842, 18, 37, '$2a$10$WAjfLk8HlwKKtgbQhlFqsesqxEHm3Q3Ar2fI4JVI06ttd3y7UgCbe', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8907, 'NOPRIANDI', '198311252025211011', 17, 842, 18, 37, '$2a$10$.NWwTfquwtofClTDRQ1zrOxyriQ4RBjimf8JOBfg6X3i4pRfhWbT2', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8908, 'ALI ZAINAL ABIDIN', '199212152025211022', 17, 842, 18, 37, '$2a$10$WmkwHZIH/YjQh3VMXXK3pOblnGU6KNSC/Lvm99WqEgntvAbo9cg9e', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8909, 'GALIH INDRA SETIAWAN', '198811232025211022', 17, 842, 18, 37, '$2a$10$AOHgOZTzdDwIJrZFw0asoeilKVRabllNSickbnCX/6/Hdugce6gP.', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8910, 'SUPRIYADI', '197309152025211010', 17, 842, 18, 37, '$2a$10$ZwQeFqHI3Ehs2BYEYQXtN.O9oo/2JMRoFPxr2P09Jewa8NfyF0iXG', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8911, 'YURIZAL', '197401022025211011', 17, 842, 18, 37, '$2a$10$qNh4d5T87Mi4nPDjJ7e8Se9e6z3f1QTrPHS3xS1wjgm5eIJi3RGwa', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8912, 'NIRZAWAN', '198412172025211008', 17, 842, 18, 37, '$2a$10$iWuJz/r.31cXnMmPeEstcOqqqwWuZuoDxgQESlkH8SsbBcNf8.Ej.', 'USER', 'https://dev.pringsewukab.go.id/foto/1754267098875.jpg', 0.000000, NULL, 1, NULL, '0000-00-00 00:00:00', '6287838807981'),
(8913, 'AFRIZON ANDI GUNAWAN', '198804162025211023', 17, 842, 18, 37, '$2a$10$YC9q7VjGbrhr.3ysEZ/hGODNEtcBg983jfbdAGH6dKecOSAJrG8sW', 'USER', 'https://dev.pringsewukab.go.id/foto/1752558610304.jpg', 0.000000, NULL, 1, NULL, '0000-00-00 00:00:00', '6287838807981'),
(8914, 'IMAM MAKHFUDI', '198311282025211017', 17, 842, 18, 37, 'a', 'USER', NULL, 0.000000, NULL, 0, NULL, NULL, '6287838807981'),
(8915, 'DWI ANTONI', '198905092025211019', 17, 842, 18, 37, '$2a$10$A6uf6IMHlkZW/I9JA.foHeRr1xDnRDkqVmlkkQ1UhL2cmlzg.g1CS', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8916, 'BAMBANG TRIBOWO', '198708142025211017', 17, 842, 18, 37, '$2a$10$BzUo1KbOYaUQIxFts99S4OiCFwRWLaq.jOoZEV11LURRg5Lk5Kepi', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8917, 'MUHAMMAD ARIEF ICHSANUDIN', '199210112025211023', 17, 842, 18, 37, '$2a$10$50loRIT6cu6db.WXc72cb.x/u.xUUPScR5ZvdQLy5WdlcxWN22qaG', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8918, 'ARIF FITOYO', '197410122025211007', 17, 839, 20, 885, '$2a$10$65lPcMss6UlF8q43aIoJiuRviV5wgNHuD/md3HUmXv13sc6M0R0du', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8919, 'MAULINA RAHMAWATI', '198412212025212016', 17, 839, 20, 885, '$2a$10$jPy1L.nf5MWBa9SnKzGZ5u2dCY0y4BvNY109REa0kV2bFDfFerRzC', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8920, 'RIKA APRIANTI', '198604142025212023', 17, 843, 23, 885, '$2a$10$yFZHCxrUIRZ8ZBObPeMK0uPUyLRguVQBQtdZDE8/k84kdaUJnTIFq', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8921, 'FENI AGUSTINA', '198408182025212028', 17, 843, 23, 885, '$2a$10$HcEwutT.WUCXqL7NeOrLe.EzmhXo16ubi2vQu6ufY8YYtvzhmLP.O', 'USER', 'https://dev.pringsewukab.go.id/foto/1753240278209.jpg', 0.000000, NULL, 1, NULL, '0000-00-00 00:00:00', '6287838807981'),
(8922, 'ERNAWATI', '198708212025212013', 17, 840, 18, 885, '$2a$10$6.IKi9rl4ZpGZlA6vQKgBOfLhVQLaPUmGNIBBya0TXbe1wLzo5zTG', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8923, 'RHOIMAN STIAWAN', '198406092025211020', 17, 839, 20, 875, '$2a$10$TY5AR9fqZF/nIChRwZmXaut.B.N1ark.L93sfqhusI3yi6kkmlXXC', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8924, 'MARYANAH', '198208062025212018', 17, 843, 23, 875, '$2a$10$/lhdHDxg2hbCJE2t12fGtuTWC41sXu3tByaoN/y0Mnj.p0NzYjjWG', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8925, 'TRI WIJIATMOKO', '197303072025211016', 17, 840, 18, 875, '$2a$10$6eyh.o4JV5K1ZbXBwP5YQ.ZbHmhX0yuq.1HRYv3MIdhPWci.FR49m', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8926, 'RIRIN RATNAWATI', '199302262025212022', 17, 840, 18, 875, '$2a$10$qVrzvEjBMvNCxsFvO1xf8OImwYln/8pjzC6pFnRMtMntEyqnhYdE6', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8927, 'ZAINUDIN', '197105172025211007', 17, 840, 18, 875, '$2a$10$JXq6ZpmDF./xg6UGKYUNw.6ucDUjI7hYd5kSgLsbglPyu9xgWSsWa', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8928, 'JAUHARI YUSRANDA KAMBIRA', '199501012025211037', 17, 840, 18, 875, '$2a$10$kXnZIHsNx2pJzBBDJTCiuOZqHQXwZbW10IvaBvhGKoF0WfwA4P1iW', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8929, 'M. AGUS SUPRIYADI', '199008272025211015', 17, 840, 18, 875, '$2a$10$sFmWUwzytbD5J0aUBSsXJ.zaR5sVsn0TydFPEBXF/EmkAeZ1Tw6oC', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8930, 'YANTI OKTAVIA', '198610292025212022', 17, 840, 18, 875, '$2a$10$12f.T0QIEBpm/Zr60jL44uSFxNT3i15zC1wFi/YqEW0L9h9SCefRy', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8931, 'SARIYONO', '198206042025211021', 17, 840, 18, 875, '$2a$10$dEikDs0Y3WgWJSqS.Ob3j.hgxwPNt0/qCI3B9Cne5Ogmdx.AlmAPW', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8932, 'ARI CANDRA', '198903102025211023', 16, 844, 20, 86, '$2a$10$6w5PHHJZTKw7NrshFCmNwu8fmmLVAKn5JFwaUEfsHNyRVjgFRmDlq', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8933, 'RIAN ARDIYANTO', '199207092025211028', 16, 845, 22, 86, '$2a$10$sXskKT0s/H137zl3m8ChOOpie/UzdgnO8XQo1Y/Jqb2OrSQudXYsa', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8934, 'WAHYU NITASARI', '198801012025212042', 16, 844, 20, 338, '$2a$10$E5w/d4xjBX2ExUsz7ke37.GBKTHoz22NvQZd.iwPCq4LAPWvNphDC', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8935, 'FADLILA', '197702112025212006', 16, 844, 20, 86, 'a', 'USER', NULL, 0.000000, NULL, 0, NULL, NULL, '6287838807981'),
(8936, 'ELISSA AGUSTINA', '198408212025212010', 16, 846, 18, 338, '$2a$10$pjVDf4Sgxr/4tK16sia5zObk3oYp2gnUqMmgja45wAOV07k/iLABu', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8937, 'YUNIAR MINARSIH', '198006242025212017', 16, 847, 23, 336, '$2a$10$fcVFaDALst73jM77E0ili.yGI8aYjSnbNnH/qsFLYqBMe.z32dSqy', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8938, 'LIA ARIYUNITA', '198707072025212044', 16, 844, 20, 337, '$2a$10$Jj0jZMiE4FDPitXHq1GeV.uiwjRi.z7E4NaiTJXxff0no/Njpj9Vy', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8939, 'RIRIN MERTAWATI', '198605122025212026', 16, 844, 20, 337, '$2a$10$RzSkzGNH4LKjAhP0CgnXbuPcNjY6NKkSrwHnDM9cw/M2/Bv8G.yGm', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8940, 'EKO SAPUTRO', '197601012025211022', 16, 846, 18, 336, '$2a$10$AoIcp7YR5SB56BpQs1qq5.IPK.0fRLmQCmqsuXuWoyytBu5nhIu0a', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8941, 'MARISA DENTA', '199111292025212023', 16, 844, 20, 85, '$2a$10$nObH6A3moznH/Qe/SUkGfOk0uqVxx0jfdd1BUr/.9MrHk.bi3x9k6', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8942, 'MEMORIAL', '198309232025211018', 16, 846, 18, 86, '$2a$10$qI2Km94A8uJYnhPpUJPEjeZazQOzDIOAvB4dZ5L2JD70jK0FF.xR6', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8943, 'IMAM SYAFEI', '198603222025211011', 16, 844, 20, 86, '$2a$10$xT9qfE7Tx3PGI7C01suz7.BNx54iwKC7d4cwOLv5cimrXUi4BpRzi', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8944, 'FIRMANSYAH', '198803102025211026', 16, 844, 20, 86, '$2a$10$HSkQzxb3ZSFJBfxChmRYxue9vt6pHbB/CPwurkl3661MxVw5BZ2Q.', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8945, 'ANGGER SAPUTRA', '199304052025211022', 16, 844, 20, 86, '$2a$10$twTn8mU9dXWbB4jiQiEG7eeOg7AaBM/ysgKgRW9UBp7.BhtOEOy7m', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8946, 'THAESA GUNA', '199003122025211022', 16, 844, 20, 86, '$2a$10$3m.Cql.W9H9zqju/OkUBue5g4sTkycN6uxjb5VR3Xw5t6ojJFkWc.', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8947, 'ISMANTO', '197108052025211005', 16, 846, 18, 86, '$2a$10$Vcs43Sk0ZVaXRu/grYN9j.NLKkLpdn9pYRlbIPa6RX9nwHRP11Lya', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8948, 'SUWAHYO', '197201192025211008', 16, 846, 18, 86, '$2a$10$Bh54uyt3LijLFLsxYVvcEew0S2.SH54u9dhO8I1pDh2pZJh0k5/nq', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8949, 'SUNARSIH', '197911022025212005', 16, 844, 20, 86, '$2a$10$ne.PwwkDhIWzhtRJKgw.ee2m96nGwMqSUX/N5tFvIn8ow3/JMsP/S', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8950, 'HERU KUSHARIYANTO', '198505182025211019', 16, 847, 23, 86, '$2a$10$ZphC26suFI5NsrK4.Tmoo.DCQm7DKx3o0MVOJvoNyjvA4VIo.qTn2', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8951, 'PARMANTO', '198012202025211013', 16, 846, 18, 86, '$2a$10$bQM8he.2TDjAzyPOCdc48uqaPtO58NGlBJOK3198LJickDtO7RWii', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8952, 'ARA SUHARA', '199210292025211018', 16, 844, 20, 86, '$2a$10$oryQ005exXLBr72E9fEleO79mTD7p3r9ip8tKXgrPw3qeT7CzECWa', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8953, 'BAMBANG WIDODO', '198506172025211018', 16, 846, 18, 86, '$2a$10$Ty4rSlsDNXyiULdODAznse6qSu1UMRCatP4bEVKTcv7Qwai8mlqme', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8954, 'RAHMAWANSYAH', '198507142025211018', 16, 846, 18, 86, '$2a$10$K3WfP8pgUU/kNd74/jMQv./KGXRiDPg2W2NJCzjY97F.JmlHNTuzK', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8955, 'ROHMAN', '197802142025211011', 16, 846, 18, 86, '$2a$10$eUufpLxVcsUpP61B4knqI.SDOY5WXALufywlqEpiWqUm7El8GFsBO', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8956, 'NURYANI', '196907072025212005', 16, 846, 18, 85, '$2a$10$XdPAuGksDMzxpGFGQ4A6ZecF12nwzscP9GUc2/6hsj0A0KdUvtB3i', 'USER', 'https://dev.pringsewukab.go.id/foto/1753249946752.jpg', 0.000000, NULL, 1, NULL, '0000-00-00 00:00:00', '6287838807981'),
(8957, 'ADENAN PUTRA', '198304262025211009', 6, 1847, 20, 128, '$2a$10$YHvmP0oakxDbfHQq4FebBuChp.Mu6Wjo9nzbs.MOxlYASnGWHjkZ2', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8958, 'EDI SAPUTRA', '198705152025211032', 6, 1847, 20, 128, '$2a$10$B7wh27dzzOwRdap16m4SGOGWJoTt1tnvGfBRhNAfNhCJajp8mQV6i', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8959, 'INTAN FERLINA', '199002042025212017', 6, 1847, 20, 128, '$2a$10$BJUAxaAxFjsF9fEVjHovZOBbhT18iidLuJ3j4m63yofF/CX9Z8FfG', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8960, 'ALFI HAFIZ ALGHAZALI', '199410032025211020', 6, 1847, 20, 128, '$2a$10$NZMsh5dZNmsRF0D5ftunYOw.w9UrCU.V7BxPlez5qBh0WKKro6YJi', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8961, 'MIRA PARTICHA', '199007132025212017', 6, 1847, 20, 128, '$2a$10$GU8x6lqwqeOH6T5UIe7G7ukgpYeV6I8Sle6Nygwgo.IPA1x6IUdEy', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8962, 'MEI LASTIAWATI', '197605222025212005', 6, 1847, 20, 128, '$2a$10$q9BSeLk3Ks3zV.ycZfHC3upb.hrfBgaOokt6ZHrFic/MYImjz.Lu6', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8963, 'RENDY SAPUTRA', '198805282025211019', 6, 1847, 20, 128, '$2a$10$pcXv8KCi/sW7d0/rN.vLEODR8stmUo5D1tViC8iPkTgiLtwxgZLYm', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8964, 'VERA SILVIANA', '198909272025212026', 6, 1847, 20, 128, '$2a$10$1BkHyKzsbKCGteubJpfJoOhRG3OZ/4UOfkSFqQwXQ8l8HvCgSFWzO', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8965, 'AYU DEWANI', '199307242025212032', 6, 1848, 18, 128, '$2a$10$Fbk5fnxrVxnlEAntBuh5bOcWGSJtbVOkPl1uV/d1CQXOBnz9qvdNy', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8966, 'FAUZI', '197011212025211007', 6, 1848, 18, 128, '$2a$10$VeEZdM8kKHMLTHbyMdhxUu9TqwOEXgP2esyDNRQ2l68A74luFOSyy', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8967, 'MULYADI', '198201142025211015', 5, 1849, 20, 317, '$2a$10$joMNz0RGQe8YGVoGfiMlM.YBC4W9XKrm24h6TwnmCRGanQ9vCMhAy', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8968, 'SYAPRI PAHLEVI', '198707222025211019', 5, 1849, 20, 317, '$2a$10$oMTcUnG4aY6UIq.9b.lczONjDPXroH3mVPFjRzpOf6k0N29CsBGBG', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8969, 'DWI RIANA', '198405152025212033', 5, 1849, 20, 317, '$2a$10$12L0fEAVk5KbZXPlxLNBiuKs2G9W/aQ5Oqr37vHNsQtmHotxgPs6e', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8970, 'DIAN ISLAMIATI PUTRI', '199601032025212013', 5, 1849, 20, 317, '$2a$10$b2jvaZ1GUjdiFyTYzWcIIe8le30eix53D4kyyuuaaSX1eUuMP1BFe', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8971, 'DESI IQLINA', '199212032025212022', 5, 1849, 20, 317, '$2a$10$aa4WUqYr8sNb2ppYvU5as.JyXikbZVK3qocnZskfsxjF9P0xw31Le', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8972, 'NUROKHIM', '198106032025211015', 5, 1850, 18, 317, '$2a$10$4BASQ0C9kTJOV//qetOqDuZ64Hrv8kfR0a0ZBr11Vsw49WsXXKk1G', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8973, 'BAGUS NURSIWANSYAH', '198801062025211021', 5, 1850, 18, 317, '$2a$10$Qu2WZhvCM0vN4Kj2bv.DpeFlHN3rnBvlrPomghsItfxJVPaKZo9UW', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8974, 'SELLY FAKHRINA DEWI', '199303032025212034', 5, 1849, 20, 26, '$2a$10$T/2r.yqMC3OBfjw.VulfBOuGytMjp3QdreTYgITYiwZfzumJWNGem', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8975, 'AYU LISNA DELVIANA', '198611072025212016', 5, 1849, 20, 1007, '$2a$10$GrDSCRB3NTKrL9.w8uFyqe3MNiK9eH9VbrPxvyXfcQOWyRxFWqhhi', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8976, 'GALIH SYAHRUDDIN', '199107012025211017', 5, 1851, 23, 321, '$2a$10$C4nrMB/jpL240qVF391azutbiUNv2gTMedsqdmNMNjkLtLiv9JeT.', 'USER', 'https://dev.pringsewukab.go.id/foto/1752743011617.jpg', 0.000000, NULL, 1, NULL, '0000-00-00 00:00:00', '6287838807981'),
(8977, 'AGUS JUNARA', '198507272025211033', 5, 1849, 20, 3161, '$2a$10$.4MYRfFTgJPcdudQXPpHMeq7nJ4Tl6OZN.SQ2VV8Qoc/WnFo1VU5C', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8978, 'NITA HESTIKA', '198205142025212008', 5, 1849, 20, 3161, '$2a$10$RudzU8ZFlhL2VP/QtHHvZOakr9Ju8bSJOBxGAQb.5RsS/XMtzrv.S', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8979, 'MEI FITRIA NINGSIH', '198805092025212018', 5, 1849, 20, 315, '$2a$10$PNffRyLD7O8BTG/8iOs7J.P0cVUcZG5GUJcXQa0yMefEXIrT7ey8K', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8980, 'GUSTI RHAMADANI TAMARA PUTRI', '199502162025212021', 5, 1849, 20, 315, '$2a$10$mSY3pwdXACAPVmCLJFz8dee4/ry.2YCgDaq0cyQw6yQ9W8zeDdl52', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8981, 'EKO SUWARTO', '199107152025211019', 5, 1851, 23, 318, '$2a$10$Uk0/Xigtv3/6f5tZTLo/2.4EF/VAAaDfbw30aHC9GuAksF63/Bo6m', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8982, 'REFKI NASAHERO', '198503282025211013', 5, 1849, 20, 317, '$2a$10$nH3HbfbDlnCGB/KiZuzZSOWv9w2knBe4YMFHzBaGEeY/xF5xz52zS', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8983, 'DENY NOVRIADI', '198611132025211015', 2, 1852, 20, 9, '$2a$10$Gzw/pOenhs9wrKXj6vafxeudGw/aVZvl1fGjlJfRrlnAcsEuYvVL.', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8984, 'ARIF HIDAYAH', '198806252025211022', 2, 1853, 18, 9, '$2a$10$uFHlZzUKqfK03bDI9bLfZ.BRMUC6iSeiQDL9fCpqukuZGsP.lSTDK', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8985, 'TRI SETYARINI SARI', '197704282025212007', 2, 1852, 20, 9, '$2a$10$v3nKVc/TULl92jLNjzORc.pa9zs7jB.fPt0V1JISp3DF65vQovQLe', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8986, 'RUSMIN NURYADIN SIDIK', '198304042025211029', 2, 1853, 18, 9, '$2a$10$OjNEwmRcL.2I7y0d7RlFke8GGGfAPusb0dcKWMS6cmxsakufaITSG', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8987, 'HUDIYANTO', '197003112025211003', 2, 1853, 18, 9, '$2a$10$m/gv4PP6BrFUGQytOH.Aku4DomZ04TAR5D.PUsGBjVDL44APmM.ny', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8988, 'MISBAUL MUNIR', '199104072025211016', 2, 1853, 18, 9, '$2a$10$/iexg/ZfSoZijKlxOpeI5.3cC29L1fuSKGCsu0WxpTvpQOBb.KaRG', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8989, 'AGUNG BAYU SAPUTRA.', '199806192025211008', 2, 1853, 18, 521, '$2a$10$Q39yBuKdVu59mMFUG0PZkOJclpm/47/qQ3Ysh7Vv7GFhS5Dbscv2C', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8990, 'BAKUH ADE WIBOWO, S.Pd', '199603022025211010', 2, 1852, 20, 521, '$2a$10$68p.Mh.fYfb0wuBF/sFtrexQRSKDFKG1uBlbqp/ydjp47hFe0EjEy', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8991, 'YUSAR IRAWAN', '199107272025211032', 2, 1853, 18, 521, '$2a$10$OQpJk4a2xdL6oq3yq2GczOfiac9F0qVtIZLtfVDe.G/hYG5f9dKXS', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8992, 'FENNY FITRIYANTI', '198805232025212023', 2, 1852, 20, 521, '$2a$10$7iUkjji0myvNCi9.y5lLzevScozjEYFRscsMgKW79RW00sEOezQGK', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8993, 'YULIATI, S.Kom', '199407182025212019', 2, 1852, 20, 465, '$2a$10$AMzO/lyYHxqdoRqd4sJKeucu3VfJegt4cCFBh7kZVvKOIb1jDDAxW', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8994, 'DINDA YUNITA SARI', '199806022025212013', 2, 1852, 24, 465, '$2a$10$7WweaYFmeLbZ0aWFQHn0nuzDMZ83wUgrN2ljPrGjAeEdqArqnavem', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8995, 'DEWI SINTANI.R,', '199209252025212031', 2, 1852, 20, 465, '$2a$10$QpE9E4BfEsdAFCbDP.e9G.VFYJVyiuSlH5XvGBtTDnitwseaAyXty', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8996, 'HARTATIK', '198608232025212024', 2, 1853, 18, 465, '$2a$10$ZUp5ko8kMWRaGUTaBc0fAO6giClWMjjm84OhRbyNcVD2m6eLiIVk.', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8997, 'WAHYUNING TYAS HANDAYANI', '199206152025212016', 2, 1854, 23, 465, '$2a$10$GK6RS0mo7wIGEy5EvPOpkewHvkzRF9v3iNc96s5KUF2GsKz8tJiWG', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(8998, 'ISNANTO HAPSARA', '197410212025211004', 2, 1852, 20, 11, '$2a$10$uZzPWhhGsi3mJ/yKx1gywurDjd.TvOkgs2ng8hqozKDl3LWJYBP/i', 'USER', 'https://dev.pringsewukab.go.id/foto/1752539247055.jpg', 0.000000, NULL, 1, NULL, '0000-00-00 00:00:00', '6287838807981'),
(8999, 'CINDY PRATIWI', '198602112025212008', 2, 1852, 20, 11, '$2a$10$JmtO59Gc/Vzmx5Q59wwXTeoHWQwzKnRYyl0Xb/MgF07CTkTvVdCCq', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9000, 'AZHAN ZULMI', '199107042025211017', 2, 1852, 20, 11, '$2a$10$XA7fT8WdRpaLP/Ypuv/POOYyU5LK4.RkiVSi73XrefBALxv0QHW0q', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9001, 'IRVA ALWANIZAR', '198704062025211028', 2, 1852, 20, 11, '$2a$10$9Xp0g3GCfeY4WKkb77QrxuWRCGBGJAGNiaV2sQuhmGT4apWI0XvQa', 'USER', 'https://dev.pringsewukab.go.id/foto/1752801165907.jpg', 0.000000, NULL, 1, NULL, '0000-00-00 00:00:00', '6287838807981'),
(9002, 'SUKAMTI', '197112122025212008', 2, 1852, 20, 11, '$2a$10$CdGqule1QrdRvWI5jN9fPuTXJ9lvsOTWRoRE4i5qVjcDxjo4GMrbu', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9003, 'ZAITUN JAMIS', '198012202025212009', 2, 1854, 23, 11, '$2a$10$OOUEWNcKYyb8jRobX1VBzOwBwYqjlbHOrXmY5QB1qCz0AD26iO/ca', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9004, 'ANA SUPIYANAH', '198304242025212020', 2, 1854, 23, 11, '$2a$10$DAv8lF9q3/X6Cqun8PsRJuP13Qum9NAtg1s432kEv46v0W3yNf/d6', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9005, 'RICO CHANDRA', '198908202025211028', 2, 1852, 20, 11, '$2a$10$1VzKound1Zlpc2Y5E4cXv.gtFEzt4e9K9v5pf.IwuUswI3JQpPTa6', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9006, 'MULYONO', '198202232025211015', 2, 1853, 18, 49, '$2a$10$ccRaYcSuL0Oeu2roJHu80Ov.lHRxh8pswEYftV4GyKc9bU4CXe2em', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9007, 'ERNAWATI', '198909102025212036', 2, 1852, 20, 49, '$2a$10$nAozqSHlcE5DfUPCh8r8oel/wfxVQS40fcVxbaPYaKht8pOt97b6G', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9008, 'SOFFYAN ROFLI', '198605142025211027', 2, 1853, 18, 49, '$2a$10$W6zqNO7W7F4JUuS0injzres.WsMoDbQlPcnl6alnQ6axu916KHRKy', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9009, 'YENI SRI ASTUTI', '198606222025212022', 2, 1852, 20, 49, '$2a$10$eLoc7XgmGdTSELf2ZTbitOsx8OrICTxP.IoI.xxzUyYywvzYo1kaO', 'USER', 'https://dev.pringsewukab.go.id/foto/1752657481794.jpg', 0.000000, NULL, 1, NULL, '0000-00-00 00:00:00', '6287838807981'),
(9010, 'THOHIR RIYADI', '197508032025211012', 2, 1853, 18, 49, '$2a$10$9UVd/YunClrMpa1/h8MnZ.72HwvgD1V6sTI544T43k6axYFg6rgQ2', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9011, 'NURILAH SAYEKTI', '197807252025212005', 2, 1854, 23, 49, '$2a$10$bjzAyMHI.LBmSdZIjvum3.HpFjNUcRXzuq7Rbz5YdiafLbg4/pSaC', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9012, 'LISAI RODIYAH', '198408312025212013', 2, 1852, 18, 49, '$2a$10$SGrRi/xculyaZS1ubbl22ub6d4jf9qQye6yVWoJfrz4RCZMnL7MYi', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9013, 'OKI HERAWAN SAPUTRA', '197510152025211012', 2, 1852, 20, 49, '$2a$10$foR23B4CkvlIE9d5cPVGWuiHO/BhGSSvbkR6RqjbwKpCYnN7Bkqu6', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9014, 'ANGGA SULTONI', '199107202025211017', 2, 1852, 20, 36, '$2a$10$Bc0Z1Hk3..wP1qbl7OzJl.w76kTfuDDHgoYynwYnPIPp7VT//wvES', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9015, 'SRI WAHYUNINGSIH', '198803302025212009', 2, 1852, 20, 36, '$2a$10$NmbOJdfwLhbdUCaCWQuIWue864El9Rli0CcZycttTVog7gwy8xOzm', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9016, 'DESSY LIANA', '198701162025212020', 2, 1853, 18, 36, '$2a$10$zq067CSVH5Bb4OHrklfsjuYS.lTYBArH98i4nJEo4vrhsshYNltO6', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9017, 'SUPARTI', '199001242025212029', 2, 1854, 23, 36, '$2a$10$n0t4uqhgQm0lW5DKCVZHVOWNxRDYhGLee6Qll120ukKB1tp.CLKPq', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9018, 'RUDI PRAMONO', '198104232025211016', 2, 1853, 18, 10, '$2a$10$JTAs6/furGMN6SMMjFtbVecSkkRBx6Tt3zrS1mw1BbaG5uL8u2D9G', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9019, 'APRIANTI', '198702072025212014', 2, 1852, 20, 10, '$2a$10$PxSslhWOb.s4CevrIxZgPOw8wC8bJJ6ao4I9j4HN6X8oFoGdkeQD2', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9020, 'IWAN MUIS', '197806052025211027', 2, 1853, 18, 10, '$2a$10$qQ77sT0rRRwKSzEenNNQbO37huCgPuUVySKa4vLQuR.eVysOhHfWW', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9021, 'RICKA ASTRIANA', '198311122025212015', 2, 1854, 23, 10, '$2a$10$P.NGT9DigBchQnz1JUPmqOFn/h11s9e.tF4qL70Lha1RcOBW13Pzq', 'USER', 'https://dev.pringsewukab.go.id/foto/1752627298706.jpg', 0.000000, NULL, 1, NULL, '0000-00-00 00:00:00', '6287838807981'),
(9022, 'HERLINA SAFITRI', '199309092025212030', 2, 1852, 20, 10, '$2a$10$AJzsazib46Fc4gciXoFdduyqyz0V22u1elriJTt6wBJYN1.xIeYdq', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9023, 'NUR AILIYAWATI', '198404052025212025', 2, 1852, 20, 10, '$2a$10$mnBmXX9ChmwQ3SFMC20PDe/55yvCZxH..J.icYRu2vpDEgUVw3mR.', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9024, 'YENI ERNANI', '199606012025212024', 2, 1852, 20, 478, '$2a$10$6T7pXpZs.1vBVSu0kzzcVercI41MHMDJN.FObnUTx0iaDDynxwU8K', 'USER', NULL, 0.000000, NULL, 1, NULL, '0000-00-00 00:00:00', '6287838807981'),
(9025, 'WALDY YOSANTO', '199103202025211018', 2, 1852, 20, 478, '$2a$10$yKoyG0HkQhFTB82TQH4mLOdY8agN/ZmhDqBF6ed2n3UWCBNz2xcwq', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9026, 'YUDI PURYADI', '199307122025211029', 2, 1852, 20, 478, '$2a$10$yttM66eR2tU1YKN//IJ.Bu2SpihH7NvblpJlySF3MzVEOQrkXuGZG', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9027, 'AGUS HERIYANTO', '197602182025211009', 2, 1853, 18, 478, '$2a$10$TUlASWHwVwnjtfuYdDLP1.6Mp7qWpSj.00yM0Le6jyDeOlqHETvOW', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9028, 'HENDRI OKUDAYONA', '199011072025211011', 2, 1853, 18, 478, '$2a$10$2BV2JMNbGR6fPxJU5WDXBubj5vxHsc5ceRPo2ivO1Dlt.HriKFoM2', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9029, 'SITI SOIMAH', '199502102025212034', 2, 1855, 20, 11, '$2a$10$3yCr8rgPCZuk0MmyaTUOgOplm7j8gNPsg4W0Qm3n4XfYgECGXx74a', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9030, 'DIAN NOVITA', '198011042025212009', 2, 1854, 23, 11, '$2a$10$6rDDkKJrZF7qb6qa9LibmeKPyC.vojNY7lE5LXATkrF2DMwznaAB6', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9031, 'VIVI MAWARNI', '198801102025212023', 2, 1853, 18, 11, '$2a$10$UFVfKYenVytddEClOMhEFuYbhJ.82MEC3H40fPlfCaLOeEI165zoK', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9032, 'RULY AGUSTIAWAN', '198802272025211017', 2, 1853, 18, 11, '$2a$10$mxx6kVfRDkLP8MKM7R3PGOr2IcogWOuuCNXNYzftkHeb.M/7T229y', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9033, 'LINDA BUDIARTI', '199205082025212016', 2, 1853, 18, 11, '$2a$10$QgnxMnuGPDv51eRxouGWj.H526QrnyABnxHCoAKRv00Z8nuhD4lzK', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9034, 'YAâ€™KUB MUNAWAR PUTRA KIYAI SINDA', '198808202025211021', 2, 1856, 18, 102, '$2a$10$l/QRYcouLLEVf8CPwyoB5.J809kMF4FkYrlibFqGyxisDuRqHdJsW', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9035, 'Dra. CATUR PRASETIAWATI', '196911042025212002', 2, 1852, 20, 102, '$2a$10$tDRDz4u71arJialFyZyesuTvW96q7AxHkHQ6jiYiWiYefCrI5NWsW', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9036, 'MUHAMMAD ANSOR', '198509192025211017', 2, 1853, 18, 102, '$2a$10$exC7mqmlWtbYF3M3Qb93aOLYGnE4kXjxNHmMU8iYEcfnEqBYHKmdi', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9037, 'TAUFIK ADITIYA', '199203082025211019', 2, 1852, 20, 102, '$2a$10$lrIEK9fZ.MP/7vKePJzyTunaQb.4SUtQNM4o4R3AUgMm7bFgAdllu', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9038, 'SUGIARTO', '198401122025211018', 2, 1856, 18, 102, '$2a$10$R4/qPvcCVPHOPjiNJ58eIOVAQ6x1sxedWtPvHfnVUO8Brw6.4h9r.', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9039, 'SARWONO', '199112252025211022', 2, 1852, 20, 102, '$2a$10$Mq2vmYDCwytHmdWmjL1z6.dQGFUTVG.OgWIOB4lxyvjdP4bunrWHa', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9040, 'ANISATUL MUNAWAROH', '200002262025212006', 2, 1852, 20, 102, '$2a$10$E7x5AHrBnyu3iEpyS.TsGePnhD/qS2k8f7NPvwrZ/OWK7zBcYQt5e', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9041, 'NUR HOLIS', '198705262025211014', 2, 1853, 18, 102, '$2a$10$cF0tLwt5qNSBWMcJ0lkR2ez3WGwvuebfXXtN843T3jcC/caNHv01K', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9042, 'EDWIN PARADES', '198105062025211017', 2, 1852, 20, 102, '$2a$10$ZARTAZ/YRU/K8gJW2WLaNej7ddOot2IZGQpILBSvrmfhH6JkiCU4W', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9043, 'SISNO', '198404152025211024', 2, 1853, 18, 102, '$2a$10$o7XH4UVEJM8rEakhNyvk7.6GnCj9l7W7Sz9w269LvTlljKdSmYXs2', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9044, 'SUHENDRA', '198508252025211021', 2, 1853, 18, 102, '$2a$10$GXW.QbB3N94dnUXoHc5EnudsvPtFeE7ITGwIUaTHo2eiRBPebHkoG', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9045, 'FRAN ANDI RISTANTO', '198802222025211022', 2, 1856, 18, 102, '$2a$10$S.pdFzNHOg9.NiyPD3pgve6TFHP6KoN/eF.e3FT0wiBn9hunvVCoK', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9046, 'AGUS', '199608242025211016', 2, 1856, 18, 102, '$2a$10$prWxlPBd9KfyCtoQuBQGRewWeWjKIh4/ZtGeiAledZTTcDpeUmTL6', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9047, 'SUTRISNO', '199108152025211020', 2, 1854, 23, 102, '$2a$10$iFxvfjoVYNL7bk5doEZJTOk/9tntuDnxEWdNloJudX857K9eu4nga', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9048, 'ROBI ANTARA', '198110082025211017', 2, 1856, 18, 102, '$2a$10$3Qyyfw3c5ph5cAUFcLFAie.jKxWqgHJt4ODrtLEDgMWIfeX7HEm.y', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9049, 'NOVIANTO', '198211122025211022', 2, 1856, 18, 102, '$2a$10$Lg4ghtbZFtXtrJ22Cv2dsuzfXnk7gARh4.uVPQkYeXo6pl/dcp6Za', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9050, 'UNTUNG SUHARYONO', '198008222025211008', 2, 1852, 20, 102, '$2a$10$q95DlWNWag4noAE7M3NViuMZqfTvx7vh16Qk5H.7LHWOdxGlb7GOK', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9051, 'MIFTAHUL ARIF HIDAYAT', '199904182025211008', 2, 1852, 20, 102, '$2a$10$OFBMBv.tJLTaCD7zgjf0NehjJn0CU6aIHDOxTaboDfDEolgQRYMJe', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9052, 'IMAM IRFAI', '199710302025211006', 2, 1852, 20, 102, '$2a$10$NG.UOVmj03DYrrtUxVzrYeKuY3C/R15egibh.CYeY43K2AUXueC5G', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9053, 'GERAL FERDISON', '199007142025211024', 2, 1853, 18, 102, '$2a$10$CZ3g.VhFk4Q9fej4DKnBj.S3mY2/YLYr2ErUkHO2ZBc8azH.TEhzW', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9054, 'HENNY ARIYANI', '197006262025212004', 2, 1853, 18, 102, '$2a$10$wCQrUxFvyC4rX763PvyXuuhXYxvpjKdcVuAuy7orfV6meEWv444Ly', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9055, 'DEDI ELYUS', '196911302025211009', 2, 1856, 18, 102, '$2a$10$dqzIYR6ueRtcGHIqn5NlyOhOjkibKsJPiOk58kij.jfHqUDJjLLe6', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9056, 'SRIYANI', '199411102025212025', 2, 1852, 20, 102, '$2a$10$UmWXFyKDQccYAA0ZNi1YLupbNHEjwkDs1lPa7pWM8pXjg/0vPbo8G', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9057, 'SITI ASPIAH', '196902022025212002', 2, 1854, 23, 102, '$2a$10$grKWapBCHlNA2Gt1Ft6c0uwyDA8s8nbVdAevI.oNqAhT4ldgi9wdy', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9058, 'MARFUAH', '197307272025212008', 2, 1853, 18, 102, '$2a$10$gu.DJwkPm/3SC1QCHZllH.RkPEfWnImzwux2Cv4EzXpOWxmrc06/a', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9059, 'DIAJENG RIZKI HARISTANTI', '199709202025212016', 2, 1856, 18, 102, '$2a$10$FZDq6f.RHFBdj0XkGPynJe44Y/Wcpvcqb2l/eA3Fh/QtG6FwSBVKK', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9060, 'QONAâ€™ATUL FITRININGSIH DL', '199704072025212017', 2, 1852, 20, 102, '$2a$10$J8ln4sjlH2YIZ/PASTIWDey8n5gwR0Mf2UIKi2fxBXLXF1JXn0pGu', 'USER', 'https://dev.pringsewukab.go.id/foto/1755073084578.jpg', 0.000000, NULL, 1, NULL, '0000-00-00 00:00:00', '6287838807981'),
(9061, 'REKA SETIAWAN', '199703262025211014', 2, 1856, 18, 102, '$2a$10$H8JlfwYaJAqt0rgE1CXZLeHm688/msNQiK8Y9xkCRMcfETUs/hZLO', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9062, 'SIGIT SULISTIYO', '199504262025211014', 2, 1853, 18, 102, '$2a$10$z6KgGlHMK01p4WUK4qyxfuoWFsGofaLqsseOYwSA2sk/MgbZAzBx.', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9063, 'ERWIN SAPUTRA', '198807272025211027', 2, 1856, 18, 102, '$2a$10$U5GnavjCrtqEyoEDxaixKOxpEBRJTu/82MB3oXrUH4ibYsGlzNjni', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9064, 'SULISTIOWATI', '198811122025212027', 2, 1852, 20, 102, '$2a$10$jTFy0R4S0H7LkiB20twcM.kYpzDPHMTHj.zbsbOrKPb42nYezwfPC', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9065, 'RIZKA DWI ASTUTI', '199703112025212015', 2, 1853, 18, 102, '$2a$10$MtcuxetU.UV6/HXsOS3.PesFb.bEooXiZuGipvdBux0ArKrdJWxxG', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9066, 'JUNETA LESTARI', '199606142025212022', 2, 1853, 18, 102, '$2a$10$jkjAbIrczZBb7G2osL89sOOsqbcZEWmI7KV7TBhdSL3Aoj0A.7EVm', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9067, 'NOVA LISTIA', '199211242025212017', 2, 1853, 18, 102, '$2a$10$WXTzb2uk4xsXln8dTXTscO2P3Nbtom8sh9TiIMMQwnnoJONj94MKG', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9068, 'DENNY PUTRA PAMUNGKAS', '198612122025211031', 2, 1852, 20, 102, '$2a$10$cVitut651gqlUi4wTS4jmutSJ3LpOCwnjmZ0cClcpyPkLwZezX0u6', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9069, 'ATAN PRIONO', '198910042025211014', 2, 1854, 23, 102, '$2a$10$.T6EJ1Cl2uNH0QllfQ3syucPUBMoTjvvQSkP4nm2ybICiapxF2F0G', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9070, 'NOVI NURMARIA', '198911072025212029', 2, 1852, 20, 102, '$2a$10$ez0EZgfmbzfKdNVX5bEhleUNPK9s4MUNZbyDcSccPE2OgnuiAHZ76', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9071, 'PONIDI', '198011152025211011', 2, 1856, 18, 102, '$2a$10$PuPqDi.OEQk6W8f7C6pLJujsyOIIvV48KQf9vmXlzKY0GhybXof.m', 'USER', NULL, 0.000000, NULL, 0, NULL, NULL, '6287838807981'),
(9072, 'IWAN YULIANTO', '199007272025211023', 21, 848, 20, 534, '$2a$10$/2laly5gzA00FASP.JQM/uIJ1zpJfpiX1.ozKHcDBEJKhSqEOqAs6', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9073, 'OKA SYAHPUTRA', '198410242025211011', 21, 848, 20, 534, '$2a$10$aV6bAwSpXs/8eBrFrKWOS.uArexj5R/SjxYqehRlBX0Vc/AU8lk9m', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9074, 'AMILA LUTVIA', '199608282025212022', 21, 848, 20, 534, '$2a$10$Ihgavz5rMBpIectMiV.8CO4g2w3bb4UoOGzktjaiIiw6c7UL4e5G6', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9075, 'EKO SULISTIYO', '198704242025211029', 21, 848, 20, 534, '$2a$10$dQcxMNOxf6C6NCFJGqT08ej0me5PfXAwbENr0g4gjnedlxrgiyxiq', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9076, 'TRI NOVIANTARY', '198211282025212013', 21, 848, 20, 534, '$2a$10$ICxFQkojYRsaOaQTbKDa4uthYrt9GQzFDrjxQVJQ.Qgyk/MwyC1Hm', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9077, 'SILVIANA', '199506212025212021', 21, 848, 20, 534, '$2a$10$uOcJtWJ.WiFD428dAFI29eJcgUR03FaJY9Afc0kJgsXKDFMH9lFXC', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9078, 'SRI HANDAYANI', '199107052025212027', 21, 848, 20, 534, '$2a$10$ed/zut/dQAosyRWViUcQK.sCdoLFifS8zA8y1hjz3F.dKxOBG53vS', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9079, 'EKA SUKANI', '198909012025212020', 21, 849, 18, 534, '$2a$10$HQH70/t/VnZIg2Vzi/nOq.lTLUK3yDp3X4O7UQWchahHh9rOOLJOm', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9080, 'HASRONI', '197806032025211014', 21, 849, 18, 534, '$2a$10$1D899tXxBVBMy/TUxb0zL.5fifK6BYiLaCVNzsgDKEUS.iX298X6.', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9081, 'ILHAM', '199301012025211038', 21, 849, 18, 534, '$2a$10$xZs94LdOtbcdwFshaHp.V.Lvf6mVUPK63Xv724r57nMGPmHyXlvk6', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9082, 'LENI YUNI ANDARI', '199006132025212020', 21, 849, 18, 534, '$2a$10$XS2KihUMprezzuj.vRj.ye1j6zPKP2Z0vpALW2UXpCM/bOZP2hJUm', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9083, 'YOUVI HARDIKA WARGATAMA', '199705022025211011', 21, 849, 18, 534, '$2a$10$gXXaFL04YyxWhWlpeWstGep1TkjGaAy/wnsf6U13Sn.NKWyfgabte', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9084, 'AFRIZANIANSYAH', '198104022025211010', 21, 848, 20, 112, '$2a$10$gint4YshpYt3ZrcT5HKKA.ncm8jcU/wvq6s9oQSZQHpfbxoHLPNWK', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9085, 'ENDRIADI RATNANTO SATRIANTONO', '197909092025211017', 21, 848, 20, 112, '$2a$10$88A63.h5Zz7mdE.YONRxQ.bhPEs7TsC1pDaeGt.Ak5v8NhJ5FlMna', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9086, 'AYU ROHENI', '198902252025212022', 21, 848, 20, 113, '$2a$10$nDDEapPUH4c2WAaTGK.Lo.3uqzktfn837dnqT6u/XwZ1JvqnHlMJK', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9087, 'DONNY DESTIAWANSYAH', '199512182025211016', 21, 848, 20, 113, '$2a$10$oypLpgQjRly5/HY1NcYjMu7QYAy6QqvMqd322bQH2kmB/4DfoRDRC', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9088, 'HANA ANINTIA', '199408042025212022', 21, 848, 20, 113, '$2a$10$ZUk5.hJumxngGfgPiqsDvuo2/Vq8V11l6fN6kw2T.YTL72n8mVFai', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9089, 'HUMAIDI', '199002022025211024', 21, 848, 20, 113, '$2a$10$p6vGDVgUMad3/RSPrDKbb.LimbwzSNYvWQNL9qBR3hvh82zDVIG02', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9090, 'RITA NUZIA YUSUF', '198111092025212013', 21, 848, 20, 113, '$2a$10$p5wSIA5bePmUatlLmjvl1OfsL6fNAQ0cLG3DE9qVHeV9pNwiuv45G', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9091, 'ROBIAH IIN LESTARI', '199503032025212032', 21, 848, 20, 113, '$2a$10$5Lg0LXkqRUP3bGUi2Mke5exRutJLlhstfn6b7K4zNvplq5EtIqdfa', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9092, 'MUHAMMAD RINALDO', '199402202025211013', 21, 850, 23, 113, '$2a$10$NvmihQdZuPMvRoCPNtbyfuN1EfAoikb/4d8YN.ZSjjYt3zQ5gCCOO', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9093, 'DIAN SAPUTRA', '199012132025211016', 4, 20, 1857, 612, '$2a$10$WHY9ARWzCSLwen353kfOEOWPepR/OLnv.nW0s2aA0UTxicPtYHFtK', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9094, 'FENDRI YANI', '198911112025212023', 4, 20, 1857, 612, '$2a$10$YHrr2ImV7tIDHAYiS3FP9eHgojxOcu/pFSX01AgzTPd.37a8m6lTm', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9095, 'FEBRIANTO IRAWAN', '199302072025211019', 4, 20, 1857, 612, '$2a$10$rItgdjsK80g.nNulEtH0puY4aUpkxw3RQRUfCCDbNG5tP/4ysKMOO', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9096, 'SUWARSONO', '197512022025211008', 4, 20, 1857, 612, '$2a$10$1/7D194mILsh1C0eaPB7Zeky3yuR/YsmBtlwzzuMjxmvglyaWfyHe', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9097, 'TRI ANDIKA WIDODO', '199206112025211020', 4, 18, 1858, 612, '$2a$10$NzQIpEXLP0m8Y8VsVeCFQedgVl97UzwA0259x4LgFUjmkADVpNEny', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9098, 'ROHAYATI', '199410262025212018', 4, 18, 1858, 612, '$2a$10$FQZpeA5kasKeW33BrG28oOZZel8SrVhn4v3wE6QPcfc6qkc5ciVb2', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9099, 'CHOIRUL AMRI', '198304142025211033', 20, 1859, 20, 1012, '$2a$10$yOvAaN2.jQpeOLsvH5G2POEoEKS0iGike1Ab4UgtPEfd18kxxIxMy', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9100, 'SOLIHIN', '198204132025211018', 20, 1860, 18, 1012, '$2a$10$Cpv0cmYZzduun1o0Rp1VluKb9Cgg2WLwKktsttdLqViIGZxoC54ai', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9101, 'RAKHMAT RAMADHAN ', '198705222025211020', 20, 1859, 20, 1012, '$2a$10$klKZb59LKuEBF5ro5O1hiuFHLySWEcAE1vphMfKYQ.LJLWsJolvb.', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9102, 'DIMAS SATRIA', '198808022025211018', 20, 1860, 18, 300, '$2a$10$Pnf44TzIwSdatdysch2Rhuk9CialOLqKfAjMH2r9diEcuyrj3rZ6q', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9103, 'AJIS AFANDI', '199308292025211019', 20, 1860, 18, 1012, '$2a$10$PuB5eqdiikbZ.PwUyThNbuWrGqUYvp6RJlEn/vnZypNbDCSYYtPgG', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9104, 'ASEP PAHLEVI ISNIEN', '198809302025211029', 20, 1861, 23, 1012, '$2a$10$8eKb6kEbiDDt3OKbAkQi.eLP3jIhe1.KUT0t3q4tKlwHb2ns3.fpC', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9105, 'ERLINDA AYU GUSTARI', '197505312025212005', 20, 1859, 20, 300, '$2a$10$RK1H6NZewwOyHMM2N1W5he82xhrcQWdcwREjDFQxMrhTjwSItg5DW', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9106, 'RANGGA CASMITA', '198807122025211033', 20, 1858, 18, 1012, '$2a$10$vKf6m53kqvLvviwRTf9jL.VJuqCFjHGz56VUMJm2b5OYK3tN.gSsS', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9107, 'Rudi Susanto', '198507172025211040', 12, 18, 1862, 65, '$2a$10$eL55xP2kOPCh4eziFuy/y.1RcUSQ8hUqpxwygN.U0F1R5V5HEnCMS', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9108, 'Heti Noviana', '198511072025212020', 12, 18, 1862, 65, '$2a$10$hFmGwHdD/cmBDcL6MHmYvepzi1P/SnSBhEg.zNC6Y8gnymwsmeYry', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9109, 'Aliyah Mantik', '198705152025212024', 12, 29, 1863, 6748, '$2a$10$1XSXSLOpZrK73I4OTW6O4O5idbVFF3l8Rb7kBMumktS7Iu1daBbF.', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9110, 'Beni Folkland', '198205282025211016', 12, 18, 1862, 800, '$2a$10$ArgOmrJfdyzqsw53t8Zc8OByZGRovHgESLj373rebFo1XuGCPTziq', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9111, 'Sucahyo', '197201195025211009', 12, 18, 1862, 800, '$2a$10$xsgMgp.mFV/OIcQsVmlyDe74q5fCof3i9g4LGxoLust8UmbPwo/wu', 'USER', 'https://dev.pringsewukab.go.id/foto/1755046903226.jpg', 0.000000, NULL, 1, NULL, '0000-00-00 00:00:00', '6287838807981'),
(9112, 'Syaifudin Zuchri', '197211102025211016', 12, 18, 1862, 800, '$2a$10$HWDBIkD2/adsu0R6cLR6GuPYGaX01Yfi9MfAUeHS1xufqZVMNJ6L.', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9113, 'Rosovy Abdurrahman', '199006042025211030', 12, 23, 1864, 800, '$2a$10$OQSP.QGuBzfC/uXmaejo4uAcTYx7tgpwAp7yRJdH94dvW.nNEep9q', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9114, 'Supriono', '198911202025211015', 12, 18, 1862, 6748, 'a', 'USER', NULL, 0.000000, NULL, 0, NULL, NULL, '6287838807981'),
(9115, 'Ruli Puji Presnawan', '198801022025211023', 12, 18, 1862, 6748, '$2a$10$fbZ5FK4G9eJW7Kylzs5YYuJYlULCshdWU8IfiI7m2ywXfQKxb5fay', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9116, 'Toni Oktriono', '197910012025211011', 12, 18, 1862, 800, '$2a$10$xaQ6zLAXNOrEtNz2LmIGKO7UEBT0HGcLF2UJCym3.F/pFRqjpsIie', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9117, 'IRVAN JANUARKO', '199201132025211017', 19, 20, 1865, 674, '$2a$10$.mGcmy6Tl9Tw2OJ9Ia3bEu1lWmglRBD1HzWOdsjkOpc2Wx5D9hPgi', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9118, 'WAHYU PRAYOGI', '199202152025211016', 19, 29, 1865, 674, '$2a$10$lJRy.5vI.RpSqgXWSdM/KuKDtgZAH4bY5LSp4lUHpTXbLIN1piqe.', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9119, 'PRABOWO TRI ATMOJO', '198403062025211025', 19, 23, 1866, 674, '$2a$10$SthRHc1hhh5kHk3c8zmXPeuAY7tLINcGMFpE5IpeJUOx/g14BHnfa', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9120, 'ROSA APRILIA', '198904052025212030', 19, 23, 1866, 102, '$2a$10$iuiH.CFaZ/0QAAmNOLr6Rex.rLHfXP7oJdEVX3usweY.bOvNaBNg6', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9121, 'SITI NURJANAH', '198402222025212014', 19, 18, 1867, 674, '$2a$10$tgDmdNePCDiZYfy.thEDPeWvIdZh9S5.8k66Q2zybCeN6Jd6gJ0hi', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9122, 'JUNNA ', '197907142025211019', 40, 18, 1867, 689, '$2a$10$Y64RYM0A1dcC0UPmYkZGq.l5Mk3at.xMqXRj19A1qD3GibxfmnkbO', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9123, 'PEBRI MAULIDI ', '198002112025211009', 19, 18, 1867, 101, '$2a$10$9RsjBqwpfa5ca2Q6INqW0eZMs.CCJ.aNXVCh4.F3OddBg34zRw/CK', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9124, 'DUDUN FERIADI', '198302202025211016', 14, 18, 1868, 723, '$2a$10$M505HUd3uhv5pZgAjORHdO/qHy2CNzMwPPCZlU7j8Pm6hRnL7rLw.', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9125, 'EDIYANSYAH UNZIR', '198411032025211017', 14, 20, 1869, 851, '$2a$10$niBOhAY/dVVYeWahiY2hce6X8aYedwLmQ4ygVB6YNqSffRvyh6O2O', 'USER', 'https://dev.pringsewukab.go.id/foto/1752461367993.jpg', 0.000000, NULL, 1, NULL, '0000-00-00 00:00:00', '6287838807981'),
(9126, 'DIAH AYU KRISTIANINGSIH', '199704062025212024', 14, 20, 1869, 851, '$2a$10$tFI6KI1.g6//Na4Edczuj.HbJeJmuhti0jkp.AkS1coLFufFJo1l2', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9127, 'NELDA FITRIYANI', '199104152025212022', 14, 20, 1869, 851, '$2a$10$AlbC7lXWLPvOoOSsSR2EEOH6cORXxx4ypfNecVTH5Yq1TvnGQeVjW', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9128, 'SRI EKOWATI', '198705072025212027', 14, 20, 1869, 851, '$2a$10$VHDhUIkucI5Epz609QT4wOu0kpjwglEaOAlNIDXHxk8Aj1gkLqIHa', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9129, 'MARNAH', '198707062025212029', 14, 20, 1869, 851, '$2a$10$oW/s9qfPrIdqJnu50AvDi.GKYz7MQMxB3YGQif9H8V4oPkz9C2Jsu', 'USER', 'https://dev.pringsewukab.go.id/foto/1752462097555.jpg', 0.000000, NULL, 1, NULL, '0000-00-00 00:00:00', '6287838807981'),
(9130, 'HARIYATI', '198710172025212021', 14, 20, 1869, 851, '$2a$10$jCMSowSBPhrxWXHq25gbTusDryIv6IVsPbEulEs/.q39VGmXPtkE.', 'USER', 'https://dev.pringsewukab.go.id/foto/1752647537464.jpg', 0.000000, NULL, 1, NULL, '0000-00-00 00:00:00', '6287838807981'),
(9131, 'HILMAN HIDAYATULLOH', '198406042025211028', 14, 20, 1869, 851, '$2a$10$YUVoTnzTwUhhawbuhxSN6uHE6ps1m2io457pXBFlehzNZXRro.jnS', 'USER', 'https://dev.pringsewukab.go.id/foto/1752538985809.jpg', 0.000000, NULL, 1, NULL, '0000-00-00 00:00:00', '6287838807981'),
(9132, 'TRI ASIH', '197901172025212008', 14, 20, 1869, 851, '$2a$10$onz7XFKHK9.1dOtsoWGALeDMdCOSjHqXTO8qogkkuhUSD1X3/uXQq', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9133, 'WIDODO', '198803092025211012', 14, 20, 1869, 851, '$2a$10$PiRZ.AjZ.0dUqNKwzNHSYe0qXhN8xFXeQ2c2zslxhd8G0EukLab5m', 'USER', 'https://dev.pringsewukab.go.id/foto/1755844542046.jpg', 0.000000, NULL, 1, NULL, '0000-00-00 00:00:00', '6287838807981'),
(9134, 'MUHAMMAD TAUFIQ RAIHAN', '199603062025211013', 14, 20, 1869, 851, '$2a$10$KLcIZg54speV57QZGVms0O/BpW5dJjHCeYhU78goNPhLJh0BGlzUm', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9135, 'FIA EKA AFRIANA', '198804042025212030', 14, 20, 1869, 851, '$2a$10$MPJ5tiEJPsDkQmS5CMWWsuo5DxKj1XBI14yYNV7Di4mgu9DtfeMyi', 'USER', 'https://dev.pringsewukab.go.id/foto/1753692312507.jpg', 0.000000, NULL, 1, NULL, '0000-00-00 00:00:00', '6287838807981'),
(9136, 'HELNA SAFITRI', '199009012025212032', 14, 20, 1869, 851, '$2a$10$EOPhlgMMEj3ZV/Pc9W7Xt.wOr23fqqVar4YbSSzmfFz.C6mDC/6DC', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9137, 'FEKA ROVESA', '198802262025212025', 14, 23, 1870, 851, '$2a$10$y8EUZ8IWzYeb8xJzwbXzOOJQlgSA3sTniJ0DilhnuDcYQ2aEpnq.e', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9138, 'PURNAMA SARI', '198810272025212014', 14, 23, 1870, 851, '$2a$10$sJJQHc18KziNBnp5MMrlo.o/0rLktt8ejX3AhJfWvbC4v0ebmJ2gW', 'USER', 'https://dev.pringsewukab.go.id/foto/1752461467356.jpg', 0.000000, NULL, 1, NULL, '0000-00-00 00:00:00', '6287838807981'),
(9139, 'LAILA FARAMITHA', '199004182025212021', 14, 23, 1870, 851, '$2a$10$iNZEyp8Pm1Js/Te/OUqBSuwEaxzFQJme3Yh4L2hNt93qgem1Fu3lW', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9140, 'DARWIN', '198411182025211017', 14, 23, 1870, 851, '$2a$10$leXpzDgMXj8yX/NFmkwxgOTNNex1ZgsVhKtYQQ1h.9JrfvEcizCA6', 'USER', 'https://dev.pringsewukab.go.id/foto/1752567983015.jpg', 0.000000, NULL, 1, NULL, '0000-00-00 00:00:00', '6287838807981'),
(9141, 'RANI ERINIKA PUTRI', '199406282025212021', 14, 23, 1870, 851, '$2a$10$J6R3XuL6L7en5IskNPjuwOIK6Z8xQbWPpFJiOrZmNoSfXQDgyd9wO', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9142, 'VENTY AFIANI', '199212232025212022', 14, 18, 1868, 851, '$2a$10$MUN/cUPZgjZlBM0xQWNjN.qMDybr/b7xjOIJSLtADsUy4zOj0xOHK', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9143, 'SITI FATIMATUR ROHMAH', '199112172025212022', 14, 18, 1868, 851, '$2a$10$UoI.NVpYqkvFMQC8iD1V3O4yf/KGgX6e3LNixJNNwuRozHfSW6cJe', 'USER', 'https://dev.pringsewukab.go.id/foto/1752461441930.jpg', 0.000000, NULL, 1, NULL, '0000-00-00 00:00:00', '6287838807981'),
(9144, 'RETNO DWI CAHYANI', '199308182025212037', 14, 18, 1868, 851, '$2a$10$qogu8szyMqAdcEN.ey2L1.FGrVz23aDPshZJ1gDM9kBAOf9EDVWkC', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9145, 'SUSANEN', '198505052025211043', 14, 18, 1868, 851, '$2a$10$uzWUyvq6C.bDI5ffEt6bO.dOcAkqumP62k/vjdgNHEH6.5ZBLU.4e', 'USER', 'https://dev.pringsewukab.go.id/foto/1752544744673.jpg', 0.000000, NULL, 1, NULL, '0000-00-00 00:00:00', '6287838807981'),
(9146, 'M. AGUNG INDRA JAYA', '198910092025211023', 14, 20, 1869, 75, '$2a$10$MnpslxzpeEn/wDKZGcYWy.IIgHQulCQzpVTiEH9rjkgj3w7Ql6M1C', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9147, 'BINA LESTARI', '199208052025212034', 14, 20, 1869, 75, '$2a$10$90/JfmEx41w08ZG3FOLK4u8ybSNE7rxCEBGueFbZtO9v4.DHhUYjO', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9148, 'EFRIYANI', '198809252025212026', 14, 20, 1869, 75, '$2a$10$9TqGErLXwfXGb.ILJ2zULuXo/37bw0m8wj3L317YeeoIEn1RvicX2', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9149, 'NINA FARIKHA', '199101292025212023', 14, 20, 1869, 75, '$2a$10$QGNt6J0onfznQdrdpBDpL.eki4sK78rGx.b3WA3Hbc/TudyJaEwJe', 'USER', 'https://dev.pringsewukab.go.id/foto/1752558206709.jpg', 0.000000, NULL, 1, NULL, '0000-00-00 00:00:00', '6287838807981'),
(9150, 'IRVAN MUBAROKAH', '198612292025211014', 14, 23, 1870, 75, '$2a$10$nf7dhkXYC7uNKg0ED7u7v.waujM9qqt4nEO3ix5IAWCIIKjcvCOF2', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9151, 'RISKI BUDIYANTO', '198710092025211017', 14, 23, 1870, 75, '$2a$10$YfvpuB1K5Gl2vr5mon98Y.uY04nFFlRLKGi76gCUcHaj8OQGmGoVW', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9152, 'KAMELIA', '198909272025212027', 14, 23, 1870, 75, '$2a$10$YsU1.9q8QwBbMWnhidiYae7Wl0Y635fdEUo.Vrx2xpnX8tx5gpEKm', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9153, 'SOFIA. R', '198402072025212012', 14, 23, 1870, 75, '$2a$10$Xpc1yrEVUHPeLB6sB2Mts.JC9NU/oh6j7/0hVSlkqZ4WpEpyF.7dS', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9154, 'JUWANDA', '198707072025211034', 14, 18, 1868, 75, '$2a$10$EzeePmSZt.GPiFiVzzMP..FpN6P/iYQ/7rYzl4O.PdlxXiybLn1qu', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981');
INSERT INTO `pegawai` (`id`, `nama_pegawai`, `nip_pegawai`, `opd_id`, `jabatan_id`, `pangkat_id`, `atasan_id`, `password`, `level`, `url_foto_pegawai`, `tukin`, `edited_by`, `first_time`, `created_at`, `updated_at`, `no_whatsapp`) VALUES
(9155, 'HERLI OKDIAN', '199010192025211024', 14, 20, 1869, 731, '$2a$10$xNKknXWNvUK1kiGVz6MDC./od0MNjGw/kNjaR1Grdclo3BJDiZl/S', 'USER', 'https://dev.pringsewukab.go.id/foto/1752461312999.jpg', 0.000000, NULL, 1, NULL, '0000-00-00 00:00:00', '6287838807981'),
(9156, 'AGUS ROSHANDI', '199008262025211015', 14, 20, 1869, 731, '$2a$10$WSMt8QrJvhoKdLjWVEfKMODO5Pw5l0XuxXjDs0IYN72CR64HQ2hs2', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9157, 'BEDI IRAWAN', '198110102025211023', 14, 20, 1869, 731, '$2a$10$LJDsjBVmJbnOKmo0Rk2gjenJEG/4rxSSrDZUGe.uYPcKF6MlzF7pC', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9158, 'ERDIWAN BAHANA PUTRA', '198305032025211020', 14, 20, 1869, 731, '$2a$10$xL1wyMDDAIiHA96RjFzdW..XiSIG4NPINj6x5gn8n.bCFWLSomYbi', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9159, 'YONDRY ISMAIL', '198106092025211015', 14, 20, 1869, 731, '$2a$10$0u06oFWTsyUNEDbAXaVheugDzNYToRejBxGvB5Fdx52.hEmjFG98a', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9160, 'DENI IRAWAN', '199104042025211019', 14, 23, 1870, 731, '$2a$10$TuHR6tAJYcN2yj9T6mtO7.BJbgfEaqWH3JYAw4UIRGOIGY2z75km.', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9161, 'HENDRI BIANTORO', '198206252025211020', 14, 23, 1870, 731, '$2a$10$nqBEBarKlWmw.t.DDbyEJ.QGTxNgRYL3QMezwvT.MCCBddgq7vVSe', 'USER', 'https://dev.pringsewukab.go.id/foto/1752562100258.jpg', 0.000000, NULL, 1, NULL, '0000-00-00 00:00:00', '6287838807981'),
(9162, 'LIA INDAH ARISTAMA PUTRI', '198610202025212023', 14, 20, 1869, 722, '$2a$10$QSFY8rZC9UuSUqFJSeJhYOy5TP9XwisSbBBUHoerpWMCtCXvUzsoq', 'USER', 'https://dev.pringsewukab.go.id/foto/1753318391397.jpg', 0.000000, NULL, 1, NULL, '0000-00-00 00:00:00', '6287838807981'),
(9163, 'CAHYANI ISTIQOMAH', '199306202025212021', 14, 20, 1869, 722, '$2a$10$EmBaP69FHISbuRm4h6JYm.FhdIl3rbw/ClFNtCmF3MfpjKRC5hNKy', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9164, 'RODIANSYAH', '198906212025211019', 14, 23, 1870, 722, '$2a$10$rm.hoCbUmsprPbHBDh7mZ.5w4krVepi890uHq/GfHa6hqNgGrWIC6', 'USER', 'https://dev.pringsewukab.go.id/foto/1752712181775.jpg', 0.000000, NULL, 1, NULL, '0000-00-00 00:00:00', '6287838807981'),
(9165, 'ALHADI ROBBI', '198606272025211014', 14, 23, 1870, 722, '$2a$10$Z5/hp0j1Nvwa6jYxUvOwJO0SYAyf1ah5ULm/3Av/x09MjB2KSo9kC', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9166, 'ENI YASMIRA', '198207082025212017', 14, 18, 1868, 722, '$2a$10$D5ne5c2fRy7T0QYr9uYGI.JeE8mLFGV5DhMPl7mnIaKM5P5OBsy4a', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9167, 'MASUDAH', '198201062025212017', 14, 18, 1868, 722, '$2a$10$aRcDTtDIQ68wBY9memOFMeM9qfWfEvWbY08Mn.rw1qivjvRhPHBTy', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9168, 'AHMAD SYUKUR', '197701242025211008', 14, 18, 1868, 722, '$2a$10$2Qw68k9fAuNQfKJbVU23oeWvIPhuCQwctohIip3wlzqrdrnakta52', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9169, 'RIANSYAH', '199205202025211025', 14, 18, 1868, 722, '$2a$10$tv8UujOQliWFO42zBJlJXuYiKOKnhf.jvhGIXauPbudMfeF3GuNq6', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9170, 'BASYUNI RAHIM', '196808142025211007', 27, 18, 851, 265, '$2a$10$GFzsi9bKYl6467cTIWTrKe.KVTOpUANVQh0nBLIhw.jmeFTpOdd0G', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9171, 'RETNO PUJI ASTUTI', '198910032025212023', 27, 20, 852, 266, '$2a$10$p8YLXMACrorPQHssur1Evu1CAoh0.KT.G9wrwzwJKTqPiK7hlsD5O', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9172, 'DEO GUSTIMEGO', '199205042025211024', 27, 18, 851, 269, '$2a$10$pL.xw8o65zlZ2KMAV4yCju3zcqiF/PMBXZcM/gVu2KYo.GddzwoxK', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9173, 'FENNY ARMIYASIH', '199608312025212015', 27, 18, 851, 269, '$2a$10$9peluwrVVu8UIQMjiYtkTu.KHCAh/zNlIFOd75FgLlT0KvS69ro9W', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9174, 'EMI FITRIYANI', '198107162025212016', 27, 23, 853, 265, '$2a$10$QS6iVtwaCLBKqMCUiA6z5.g.4qPRoUjsEf7A3p/GxlFghFe9FJz82', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9175, 'INDAH WAHYU HARYANTI', '198304222025212013', 27, 18, 851, 266, '$2a$10$ZQZXKVlJHV7QpaI./D9NM.6d.eDNeR1wMydNYCGMjQpbfLadHROG.', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9176, 'ARIN AFRIYADI', '199104082025211025', 27, 18, 851, 265, '$2a$10$maAJfAvfAUHJ7H5V86mVHeAYJr/HsMmkpxXrlJokHqNZ2ecA/I5S2', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9177, 'BELLY IRAWAN', '199504022025211020', 40, 18, 1867, 689, '$2a$10$vIv2Zp38ANMHXRN2yHGUquRaCPHLUndHf7NWI6HRszrAMw6DUD9Ni', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9178, 'EM WINARTO', '197607161995021001', 2, 383, 13, 521, '$2a$10$L1qA8KzuqB9FDNbrzBJQKeIwnfElVNbeQf5aVsUNMsN1X7gToR0YG', 'USER', 'https://dev.pringsewukab.go.id/foto/1753241895756.jpg', 0.000000, NULL, 1, NULL, '0000-00-00 00:00:00', '6287838807981'),
(9179, 'MULYONO', '197503122007011030', 37, 1805, 9, 163, '$2a$10$fHAqFG4A61rzzqQtO.rQOOBGah8pnEGkI5VsOIep5eJIDWoBNYBw.', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9180, 'ANDHIKA WIJAYA', '198709232025211022', 29, 1794, 18, 145, '$2a$10$l75qLahilHz2AXOkuDXOmu/ObQHQZt8gbp0m.1veE0UWlHMiiECQ6', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, '6287838807981'),
(9181, 'NURLIZAWATI', '197711192025212007', 36, 1808, 20, 943, '$2a$10$U7lmxRwQ4pO/rgJ533hEu.wbWFmcrlnKIDM5mVqmHBR3D9HiJW61C', 'USER', 'https://dev.pringsewukab.go.id/foto/1753160767386.jpg', 0.000000, NULL, 1, NULL, '0000-00-00 00:00:00', '6287838807981'),
(9182, 'YOYON DWI PURNOMO', '198606012025211020', 36, 1808, 20, 943, '$2a$10$.TSkvmPRjaEIpsVwYGZNpudyu6n9s5dNUAao16O..GCkoCIwSj15K', 'USER', 'https://dev.pringsewukab.go.id/foto/1753161561861.jpg', 0.000000, NULL, 1, NULL, '0000-00-00 00:00:00', '6287838807981'),
(9183, 'ADI SUTRISNO', '196910272009061001', 37, 1871, 7, 163, '$2a$10$REIq8Ko/65NnbUWI6l33kuEQEzV29sMQw0ySQpuLNvY1qdWtBZn46', 'USER', 'https://dev.pringsewukab.go.id/foto/1754393011401.jpg', 0.000000, NULL, 1, NULL, '0000-00-00 00:00:00', '6287838807981'),
(9184, 'IMAM FATKUROJI', '198411102003121001', 7, 36, 14, 0, '$2a$10$NPf0gFGUaRbFUunFUTMo3./9i1Tvg2XTOjnK5xTU90H1fV9WJkDVK', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, NULL),
(9185, 'CATUR AGUS DEWANTO', '196808111998031005', 28, 137, 15, 0, '$2a$10$nCVr.6riEMCP.fj9EPGVsefQ870uRvj0dio3Qgnhwq2Bjy013k3E2', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, NULL),
(9186, 'OLPIN PUTRA', '198210212010011010', 5, 26, 15, 0, '$2a$10$An/vE3nQDgkUzwCmn6ZKouTJTNxL7vIzeDqqcaO.wg7tj0YQQtnxG', 'USER', 'https://dev.pringsewukab.go.id/foto/1754871627413.jpg', 0.000000, NULL, 1, NULL, '0000-00-00 00:00:00', NULL),
(9187, 'A. Fadil Hadiarto', '199401122025211027', 16, 846, 18, 336, '$2a$10$UavGpLLpbLRnHFI/2ZCLNOuGBks0KvdpWc6IcY434YMaSd9qKvSaG', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, NULL),
(9188, 'Sugianto', '197308112009061001', 32, 1872, 6, 153, '$2a$10$1jilwMljnUbqnwJP.sh/leIV0o.ynwyXi3EoJdbi6E7dDHmtfBtM.', 'USER', NULL, 0.000000, NULL, 1, NULL, NULL, NULL),
(9999, 'RIYANTO PAMUNGKAS', '00000', 46, 9999, 25, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-23 10:00:44', '2025-10-23 10:00:44', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `pk`
--

CREATE TABLE `pk` (
  `id` int UNSIGNED NOT NULL,
  `parent_pk_id` int UNSIGNED DEFAULT NULL,
  `opd_id` int UNSIGNED NOT NULL,
  `tahun` year NOT NULL,
  `jenis` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `pihak_1` int UNSIGNED DEFAULT NULL,
  `pihak_2` int UNSIGNED DEFAULT NULL,
  `tanggal` date NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pk`
--

INSERT INTO `pk` (`id`, `parent_pk_id`, `opd_id`, `tahun`, `jenis`, `pihak_1`, `pihak_2`, `tanggal`, `created_at`, `updated_at`) VALUES
(62, NULL, 46, '2025', 'bupati', 9999, NULL, '2025-12-15', '2025-12-15 06:26:03', '2025-12-15 06:26:03'),
(63, NULL, 20, '2025', 'jpt', 151, 307, '2025-12-16', '2025-12-16 07:37:23', '2025-12-16 07:52:41'),
(64, NULL, 20, '2025', 'administrator', 307, 305, '2025-12-16', '2025-12-16 07:38:22', '2025-12-16 07:54:57'),
(66, NULL, 20, '2025', 'pengawas', 305, 303, '2025-12-16', '2025-12-16 07:50:47', '2025-12-16 07:50:47');

-- --------------------------------------------------------

--
-- Table structure for table `pk_indikator`
--

CREATE TABLE `pk_indikator` (
  `id` int UNSIGNED NOT NULL,
  `pk_sasaran_id` int UNSIGNED NOT NULL,
  `jenis` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `indikator` text COLLATE utf8mb4_general_ci NOT NULL,
  `jenis_indikator` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `id_satuan` int UNSIGNED DEFAULT NULL,
  `target` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pk_indikator`
--

INSERT INTO `pk_indikator` (`id`, `pk_sasaran_id`, `jenis`, `indikator`, `jenis_indikator`, `id_satuan`, `target`, `created_at`, `updated_at`) VALUES
(93, 87, 'bupati', 'wedwe', 'Indikator Positif', 5, '223', '2025-12-15 06:26:03', '2025-12-15 06:26:03'),
(97, 91, 'pengawas', 'sas', 'Indikator Positif', 20, '21', '2025-12-16 07:50:47', '2025-12-16 07:50:47'),
(98, 92, 'jpt', 'xzx', 'Indikator Positif', 13, '2112', '2025-12-16 07:52:41', '2025-12-16 07:52:41'),
(99, 93, 'administrator', 'sa', 'Indikator Positif', 20, '32', '2025-12-16 07:54:57', '2025-12-16 07:54:57');

-- --------------------------------------------------------

--
-- Table structure for table `pk_kegiatan`
--

CREATE TABLE `pk_kegiatan` (
  `id` int UNSIGNED NOT NULL,
  `pk_program_id` int UNSIGNED DEFAULT NULL,
  `kegiatan_id` int UNSIGNED DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pk_kegiatan`
--

INSERT INTO `pk_kegiatan` (`id`, `pk_program_id`, `kegiatan_id`, `created_at`, `updated_at`) VALUES
(28, 75, 8, '2025-12-16 07:38:58', '2025-12-16 07:38:58'),
(29, 76, 8, '2025-12-16 07:50:47', '2025-12-16 07:50:47'),
(30, 80, 8, '2025-12-16 07:54:57', '2025-12-16 07:54:57'),
(31, 80, 11, '2025-12-16 07:54:57', '2025-12-16 07:54:57'),
(32, 81, 18, '2025-12-16 07:54:57', '2025-12-16 07:54:57');

-- --------------------------------------------------------

--
-- Table structure for table `pk_misi`
--

CREATE TABLE `pk_misi` (
  `id` int UNSIGNED NOT NULL,
  `pk_id` int UNSIGNED NOT NULL,
  `rpjmd_misi_id` int UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pk_program`
--

CREATE TABLE `pk_program` (
  `id` int UNSIGNED NOT NULL,
  `program_id` int UNSIGNED DEFAULT NULL,
  `pk_indikator_id` int UNSIGNED DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pk_program`
--

INSERT INTO `pk_program` (`id`, `program_id`, `pk_indikator_id`, `created_at`, `updated_at`) VALUES
(75, 74, NULL, '2025-12-16 07:38:58', '2025-12-16 07:38:58'),
(76, 74, 97, '2025-12-16 07:50:47', '2025-12-16 07:50:47'),
(77, 1, 98, '2025-12-16 07:52:41', '2025-12-16 07:52:41'),
(78, 15, 98, '2025-12-16 07:52:41', '2025-12-16 07:52:41'),
(79, 2, 98, '2025-12-16 07:52:41', '2025-12-16 07:52:41'),
(80, 1, 99, '2025-12-16 07:54:57', '2025-12-16 07:54:57'),
(81, 15, 99, '2025-12-16 07:54:57', '2025-12-16 07:54:57');

-- --------------------------------------------------------

--
-- Table structure for table `pk_referensi`
--

CREATE TABLE `pk_referensi` (
  `id` int UNSIGNED NOT NULL,
  `pk_id` int UNSIGNED NOT NULL,
  `referensi_pk_id` int UNSIGNED NOT NULL,
  `referensi_indikator_id` int UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pk_sasaran`
--

CREATE TABLE `pk_sasaran` (
  `id` int UNSIGNED NOT NULL,
  `pk_id` int UNSIGNED NOT NULL,
  `jenis` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `sasaran` text COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pk_sasaran`
--

INSERT INTO `pk_sasaran` (`id`, `pk_id`, `jenis`, `sasaran`, `created_at`, `updated_at`) VALUES
(87, 62, 'bupati', 'SAA', '2025-12-15 06:26:03', '2025-12-15 06:26:03'),
(91, 66, 'pengawas', 'asa', '2025-12-16 07:50:47', '2025-12-16 07:50:47'),
(92, 63, 'jpt', 'zxz', '2025-12-16 07:52:41', '2025-12-16 07:52:41'),
(93, 64, 'administrator', 'asa', '2025-12-16 07:54:57', '2025-12-16 07:54:57');

-- --------------------------------------------------------

--
-- Table structure for table `pk_subkegiatan`
--

CREATE TABLE `pk_subkegiatan` (
  `id` int UNSIGNED NOT NULL,
  `pk_kegiatan_id` int UNSIGNED DEFAULT NULL,
  `subkegiatan_id` int UNSIGNED DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pk_subkegiatan`
--

INSERT INTO `pk_subkegiatan` (`id`, `pk_kegiatan_id`, `subkegiatan_id`, `created_at`, `updated_at`) VALUES
(10, 28, 5, '2025-12-16 07:38:58', '2025-12-16 07:38:58'),
(11, 29, 29, '2025-12-16 07:50:47', '2025-12-16 07:50:47');

-- --------------------------------------------------------

--
-- Table structure for table `program_pk`
--

CREATE TABLE `program_pk` (
  `id` int UNSIGNED NOT NULL,
  `program_kegiatan` text COLLATE utf8mb4_general_ci NOT NULL,
  `anggaran` decimal(15,2) NOT NULL DEFAULT '0.00',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `program_pk`
--

INSERT INTO `program_pk` (`id`, `program_kegiatan`, `anggaran`, `created_at`, `updated_at`) VALUES
(1, 'PROGRAM PENUNJANG URUSAN PEMERINTAHAN DAERAH KABUPATEN/KOTA', 9999999999999.99, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `renstra_indikator_sasaran`
--

CREATE TABLE `renstra_indikator_sasaran` (
  `id` int UNSIGNED NOT NULL,
  `renstra_sasaran_id` int UNSIGNED NOT NULL,
  `indikator_sasaran` text COLLATE utf8mb4_general_ci NOT NULL,
  `satuan` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `jenis_indikator` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `baseline` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `renstra_indikator_sasaran`
--

INSERT INTO `renstra_indikator_sasaran` (`id`, `renstra_sasaran_id`, `indikator_sasaran`, `satuan`, `jenis_indikator`, `baseline`, `created_at`, `updated_at`) VALUES
(2, 2, 'sasa', 'Persen', 'positif', NULL, '2025-12-22 03:47:44', '2025-12-22 03:47:44'),
(3, 3, 'sds', 'Persen', 'positif', NULL, '2026-01-14 07:43:05', '2026-01-14 07:43:05');

-- --------------------------------------------------------

--
-- Table structure for table `renstra_indikator_tujuan`
--

CREATE TABLE `renstra_indikator_tujuan` (
  `id` int UNSIGNED NOT NULL,
  `tujuan_id` int UNSIGNED NOT NULL,
  `indikator_tujuan` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `baseline` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `renstra_indikator_tujuan`
--

INSERT INTO `renstra_indikator_tujuan` (`id`, `tujuan_id`, `indikator_tujuan`, `baseline`, `created_at`, `updated_at`) VALUES
(2, 2, 'sasas', NULL, '2025-12-22 03:47:44', '2025-12-22 03:47:44'),
(3, 3, 'asas', NULL, '2026-01-14 07:43:05', '2026-01-14 07:43:05');

-- --------------------------------------------------------

--
-- Table structure for table `renstra_sasaran`
--

CREATE TABLE `renstra_sasaran` (
  `id` int UNSIGNED NOT NULL,
  `opd_id` int UNSIGNED NOT NULL,
  `renstra_tujuan_id` int UNSIGNED NOT NULL,
  `sasaran` text COLLATE utf8mb4_general_ci NOT NULL,
  `status` enum('draft','selesai') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'draft',
  `tahun_mulai` int NOT NULL,
  `tahun_akhir` int NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `renstra_sasaran`
--

INSERT INTO `renstra_sasaran` (`id`, `opd_id`, `renstra_tujuan_id`, `sasaran`, `status`, `tahun_mulai`, `tahun_akhir`, `created_at`, `updated_at`) VALUES
(2, 20, 2, 'sasa', 'draft', 2025, 2030, '2025-12-22 03:47:44', '2025-12-22 03:47:44'),
(3, 20, 3, 'xzx', 'draft', 2025, 2029, '2026-01-14 07:43:05', '2026-01-14 07:43:05');

-- --------------------------------------------------------

--
-- Table structure for table `renstra_target`
--

CREATE TABLE `renstra_target` (
  `id` int UNSIGNED NOT NULL,
  `renstra_indikator_id` int UNSIGNED NOT NULL,
  `tahun` year NOT NULL,
  `target` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `renstra_target`
--

INSERT INTO `renstra_target` (`id`, `renstra_indikator_id`, `tahun`, `target`, `created_at`, `updated_at`) VALUES
(7, 2, '2025', '1', '2025-12-22 03:47:44', '2025-12-22 03:47:44'),
(8, 2, '2026', '2', '2025-12-22 03:47:44', '2025-12-22 03:47:44'),
(9, 2, '2027', '3', '2025-12-22 03:47:44', '2025-12-22 03:47:44'),
(10, 2, '2028', '5', '2025-12-22 03:47:44', '2025-12-22 03:47:44'),
(11, 2, '2029', '6', '2025-12-22 03:47:44', '2025-12-22 03:47:44'),
(12, 2, '2030', '7', '2025-12-22 03:47:44', '2025-12-22 03:47:44'),
(13, 3, '2025', '2,1', '2026-01-14 07:43:05', '2026-01-14 07:43:05'),
(14, 3, '2026', '2,2', '2026-01-14 07:43:05', '2026-01-14 07:43:05'),
(15, 3, '2027', '2,3', '2026-01-14 07:43:05', '2026-01-14 07:43:05'),
(16, 3, '2028', '2,4', '2026-01-14 07:43:05', '2026-01-14 07:43:05'),
(17, 3, '2029', '2,5', '2026-01-14 07:43:05', '2026-01-14 07:43:05');

-- --------------------------------------------------------

--
-- Table structure for table `renstra_target_tujuan`
--

CREATE TABLE `renstra_target_tujuan` (
  `id` int UNSIGNED NOT NULL,
  `indikator_tujuan_id` int UNSIGNED NOT NULL,
  `tahun` year NOT NULL,
  `target_tahunan` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `renstra_target_tujuan`
--

INSERT INTO `renstra_target_tujuan` (`id`, `indikator_tujuan_id`, `tahun`, `target_tahunan`, `created_at`, `updated_at`) VALUES
(7, 2, '2025', '1', '2025-12-22 03:47:44', '2025-12-22 03:47:44'),
(8, 2, '2026', '1', '2025-12-22 03:47:44', '2025-12-22 03:47:44'),
(9, 2, '2027', '2', '2025-12-22 03:47:44', '2025-12-22 03:47:44'),
(10, 2, '2028', '3', '2025-12-22 03:47:44', '2025-12-22 03:47:44'),
(11, 2, '2029', '4', '2025-12-22 03:47:44', '2025-12-22 03:47:44'),
(12, 2, '2030', '5', '2025-12-22 03:47:44', '2025-12-22 03:47:44'),
(13, 3, '2025', '1,1', '2026-01-14 07:43:05', '2026-01-14 07:43:05'),
(14, 3, '2026', '1,2', '2026-01-14 07:43:05', '2026-01-14 07:43:05'),
(15, 3, '2027', '1,3', '2026-01-14 07:43:05', '2026-01-14 07:43:05'),
(16, 3, '2028', '1,4', '2026-01-14 07:43:05', '2026-01-14 07:43:05'),
(17, 3, '2029', '1,5', '2026-01-14 07:43:05', '2026-01-14 07:43:05');

-- --------------------------------------------------------

--
-- Table structure for table `renstra_tujuan`
--

CREATE TABLE `renstra_tujuan` (
  `id` int UNSIGNED NOT NULL,
  `rpjmd_sasaran_id` int UNSIGNED NOT NULL,
  `tujuan` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `renstra_tujuan`
--

INSERT INTO `renstra_tujuan` (`id`, `rpjmd_sasaran_id`, `tujuan`, `created_at`, `updated_at`) VALUES
(2, 2, 'asada', '2025-12-21 20:47:44', '2025-12-21 20:47:44'),
(3, 0, 'sasa', '2026-01-14 00:43:05', '2026-01-14 00:43:05');

-- --------------------------------------------------------

--
-- Table structure for table `rkt`
--

CREATE TABLE `rkt` (
  `id` int UNSIGNED NOT NULL,
  `opd_id` int UNSIGNED NOT NULL,
  `tahun` year NOT NULL,
  `indikator_id` int UNSIGNED DEFAULT NULL,
  `program_id` int UNSIGNED NOT NULL,
  `status` enum('draft','selesai') COLLATE utf8mb4_general_ci DEFAULT 'draft',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rkt`
--

INSERT INTO `rkt` (`id`, `opd_id`, `tahun`, `indikator_id`, `program_id`, `status`, `created_at`, `updated_at`) VALUES
(13, 20, '2025', 3, 1, 'selesai', '2025-12-11 07:53:30', '2025-12-11 07:54:49');

-- --------------------------------------------------------

--
-- Table structure for table `rkt_kegiatan`
--

CREATE TABLE `rkt_kegiatan` (
  `id` int UNSIGNED NOT NULL,
  `rkt_id` int UNSIGNED NOT NULL,
  `kegiatan_id` int UNSIGNED NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rkt_kegiatan`
--

INSERT INTO `rkt_kegiatan` (`id`, `rkt_id`, `kegiatan_id`, `created_at`, `updated_at`) VALUES
(14, 13, 1, '2025-12-11 07:53:30', '2025-12-11 07:53:30');

-- --------------------------------------------------------

--
-- Table structure for table `rkt_subkegiatan`
--

CREATE TABLE `rkt_subkegiatan` (
  `id` int UNSIGNED NOT NULL,
  `rkt_kegiatan_id` int UNSIGNED NOT NULL,
  `sub_kegiatan_id` int UNSIGNED NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rkt_subkegiatan`
--

INSERT INTO `rkt_subkegiatan` (`id`, `rkt_kegiatan_id`, `sub_kegiatan_id`, `created_at`, `updated_at`) VALUES
(22, 14, 1, '2025-12-11 07:53:30', '2025-12-11 07:53:30');

-- --------------------------------------------------------

--
-- Table structure for table `rpjmd_indikator_sasaran`
--

CREATE TABLE `rpjmd_indikator_sasaran` (
  `id` int UNSIGNED NOT NULL,
  `sasaran_id` int UNSIGNED NOT NULL,
  `indikator_sasaran` text COLLATE utf8mb4_general_ci NOT NULL,
  `definisi_op` text COLLATE utf8mb4_general_ci,
  `satuan` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `jenis_indikator` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `baseline` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rpjmd_indikator_sasaran`
--

INSERT INTO `rpjmd_indikator_sasaran` (`id`, `sasaran_id`, `indikator_sasaran`, `definisi_op`, `satuan`, `jenis_indikator`, `baseline`, `created_at`, `updated_at`) VALUES
(27, 0, 'kjj', 'asa', 'Nilai', 'indikator positif', NULL, '2026-01-14 10:14:42', '2026-01-14 10:14:42');

-- --------------------------------------------------------

--
-- Table structure for table `rpjmd_indikator_tujuan`
--

CREATE TABLE `rpjmd_indikator_tujuan` (
  `id` int UNSIGNED NOT NULL,
  `tujuan_id` int UNSIGNED NOT NULL,
  `indikator_tujuan` text COLLATE utf8mb4_general_ci NOT NULL,
  `baseline` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rpjmd_indikator_tujuan`
--

INSERT INTO `rpjmd_indikator_tujuan` (`id`, `tujuan_id`, `indikator_tujuan`, `baseline`, `created_at`, `updated_at`) VALUES
(29, 26, 'sas', NULL, '2026-01-14 10:14:42', '2026-01-14 10:14:42');

-- --------------------------------------------------------

--
-- Table structure for table `rpjmd_misi`
--

CREATE TABLE `rpjmd_misi` (
  `id` int UNSIGNED NOT NULL,
  `misi` text COLLATE utf8mb4_general_ci NOT NULL,
  `status` enum('draft','selesai') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'draft',
  `tahun_mulai` year NOT NULL,
  `tahun_akhir` year NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rpjmd_misi`
--

INSERT INTO `rpjmd_misi` (`id`, `misi`, `status`, `tahun_mulai`, `tahun_akhir`, `created_at`, `updated_at`) VALUES
(20, 'sasas', 'selesai', '2025', '2029', '2026-01-14 10:14:42', '2026-01-14 07:41:47');

-- --------------------------------------------------------

--
-- Table structure for table `rpjmd_sasaran`
--

CREATE TABLE `rpjmd_sasaran` (
  `id` int UNSIGNED NOT NULL,
  `tujuan_id` int UNSIGNED NOT NULL,
  `status` enum('draft','selesai') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'draft',
  `sasaran_rpjmd` text COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rpjmd_sasaran`
--

INSERT INTO `rpjmd_sasaran` (`id`, `tujuan_id`, `status`, `sasaran_rpjmd`, `created_at`, `updated_at`) VALUES
(0, 26, 'draft', 'sd', '2026-01-14 10:14:42', '2026-01-14 10:14:42');

-- --------------------------------------------------------

--
-- Table structure for table `rpjmd_target`
--

CREATE TABLE `rpjmd_target` (
  `id` int UNSIGNED NOT NULL,
  `indikator_sasaran_id` int UNSIGNED NOT NULL,
  `tahun` year NOT NULL,
  `target_tahunan` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rpjmd_target`
--

INSERT INTO `rpjmd_target` (`id`, `indikator_sasaran_id`, `tahun`, `target_tahunan`, `created_at`, `updated_at`) VALUES
(233, 27, '2025', '1.1', '2026-01-14 10:14:42', '2026-01-14 10:14:42'),
(234, 27, '2026', '2.1', '2026-01-14 10:14:42', '2026-01-14 10:14:42'),
(235, 27, '2027', '0.1', '2026-01-14 10:14:42', '2026-01-14 10:14:42'),
(236, 27, '2028', '12', '2026-01-14 10:14:42', '2026-01-14 10:14:42'),
(237, 27, '2029', '77', '2026-01-14 10:14:42', '2026-01-14 10:14:42');

-- --------------------------------------------------------

--
-- Table structure for table `rpjmd_target_tujuan`
--

CREATE TABLE `rpjmd_target_tujuan` (
  `id` int UNSIGNED NOT NULL,
  `indikator_tujuan_id` int UNSIGNED NOT NULL,
  `tahun` year NOT NULL,
  `target_tahunan` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rpjmd_target_tujuan`
--

INSERT INTO `rpjmd_target_tujuan` (`id`, `indikator_tujuan_id`, `tahun`, `target_tahunan`, `created_at`, `updated_at`) VALUES
(134, 29, '2025', '0.12', '2026-01-14 10:14:42', '2026-01-14 10:14:42'),
(135, 29, '2026', '65', '2026-01-14 10:14:42', '2026-01-14 10:14:42'),
(136, 29, '2027', '3', '2026-01-14 10:14:42', '2026-01-14 10:14:42'),
(137, 29, '2028', '14', '2026-01-14 10:14:42', '2026-01-14 10:14:42'),
(138, 29, '2029', '0.1', '2026-01-14 10:14:42', '2026-01-14 10:14:42');

-- --------------------------------------------------------

--
-- Table structure for table `rpjmd_tujuan`
--

CREATE TABLE `rpjmd_tujuan` (
  `id` int UNSIGNED NOT NULL,
  `misi_id` int UNSIGNED NOT NULL,
  `tujuan_rpjmd` text COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rpjmd_tujuan`
--

INSERT INTO `rpjmd_tujuan` (`id`, `misi_id`, `tujuan_rpjmd`, `created_at`, `updated_at`) VALUES
(26, 20, 'sas', '2026-01-14 10:14:42', '2026-01-14 10:14:42');

-- --------------------------------------------------------

--
-- Table structure for table `satuan`
--

CREATE TABLE `satuan` (
  `id` int UNSIGNED NOT NULL,
  `satuan` varchar(100) COLLATE utf8mb4_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `satuan`
--

INSERT INTO `satuan` (`id`, `satuan`) VALUES
(1, 'persen'),
(2, 'orang'),
(3, 'paket'),
(4, 'dokumen'),
(5, 'akreditasi rumah sakit'),
(6, 'opini BPK'),
(7, 'unit'),
(8, 'laporan'),
(9, 'bulan'),
(10, 'unit kerja'),
(11, 'nilai sakip'),
(12, 'ton'),
(13, '%'),
(14, 'lembaga'),
(15, 'kegiatan'),
(16, 'km'),
(17, 'ha'),
(18, 'ppm'),
(19, 'indeks'),
(20, 'nilai'),
(21, 'rp');

-- --------------------------------------------------------

--
-- Table structure for table `sub_kegiatan_pk`
--

CREATE TABLE `sub_kegiatan_pk` (
  `id` int UNSIGNED NOT NULL,
  `kegiatan_id` int UNSIGNED NOT NULL,
  `sub_kegiatan` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `anggaran` decimal(15,2) DEFAULT '0.00',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sub_kegiatan_pk`
--

INSERT INTO `sub_kegiatan_pk` (`id`, `kegiatan_id`, `sub_kegiatan`, `anggaran`, `created_at`, `updated_at`) VALUES
(1, 1, 'Koordinasi dan Penyusunan Dokumen RKA-SKPD', 11152732.00, '2026-01-05 13:36:46', '2026-01-05 13:36:46'),
(2, 1, 'Koordinasi dan Penyusunan Laporan Capaian Kinerja dan Ikhtisar Realisasi\nKinerja SKPD', 1000000000.00, '2026-01-05 13:36:46', '2026-01-05 13:36:46'),
(3, 1, 'Evaluasi Kinerja Perangkat Daerah', 0.00, '2026-01-05 13:36:46', '2026-01-05 13:36:46'),
(4, 1, 'Administrasi Keuangan Perangkat Daerah', 322560780603.00, '2026-01-05 13:36:46', '2026-01-05 13:36:46'),
(5, 1, 'Penyediaan Gaji dan Tunjangan ASN', 322228820603.00, '2026-01-05 13:36:46', '2026-01-05 13:36:46'),
(6, 1, 'Pelaksanaan Penatausahaan dan\n\nPengujian/Verifikasi Keuangan SKPD', 32196000000.00, '2026-01-05 13:36:46', '2026-01-05 13:36:46'),
(7, 1, 'Koordinasi dan Penyusunan Laporan Keuangan Akhir Tahun SKPD', 10000000.00, '2026-01-05 13:36:46', '2026-01-05 13:36:46'),
(8, 1, 'Administrasi Kepegawaian Perangkat Daerah', 0.00, '2026-01-05 13:36:46', '2026-01-05 13:36:46'),
(9, 1, 'Pendidikan dan Pelatihan Pegawai Berdasarkan Tugas dan Fungsi', 0.00, '2026-01-05 13:36:46', '2026-01-05 13:36:46'),
(10, 1, 'Administrasi Umum Perangkat Daerah', 69318000.00, '2026-01-05 13:36:46', '2026-01-05 13:36:46'),
(11, 1, 'Penyediaan Komponen Instalasi\n\nListrik/Penerangan Bangunan Kantor', 500000000.00, '2026-01-05 13:36:46', '2026-01-05 13:36:46'),
(12, 1, 'Penyediaan Bahan Logistik Kantor', 34588000.00, '2026-01-05 13:36:46', '2026-01-05 13:36:46'),
(13, 1, 'Penyediaan Barang Cetakan dan Penggandaan', 9990000.00, '2026-01-05 13:36:46', '2026-01-05 13:36:46'),
(14, 1, 'Penyediaan Bahan Bacaan dan Peraturan Perundang-undangan', 19740000.00, '2026-01-05 13:36:46', '2026-01-05 13:36:46'),
(15, 1, 'Penyelenggaraan Rapat Koordinasi dan Konsultasi SKPD', 0.00, '2026-01-05 13:36:46', '2026-01-05 13:36:46'),
(16, 1, 'Pengadaan Barang Milik Daerah Penunjang Urusan Pemerintah Daerah', 0.00, '2026-01-05 13:36:46', '2026-01-05 13:36:46'),
(17, 1, 'Pengadaan Sarana dan Prasarana Gedung Kantor atau Bangunan Lainnya', 0.00, '2026-01-05 13:36:46', '2026-01-05 13:36:46'),
(18, 1, 'Penyediaan Jasa Penunjang Urusan Pemerintahan Daerah', 128950000.00, '2026-01-05 13:36:46', '2026-01-05 13:36:46'),
(19, 1, 'Penyediaan Jasa Komunikasi, Sumber Daya Air dan Listrik', 128950000.00, '2026-01-05 13:36:46', '2026-01-05 13:36:46'),
(20, 1, 'Pemeliharaan Barang Milik Daerah Penunjang Urusan Pemerintahan Daerah', 107530000.00, '2026-01-05 13:36:46', '2026-01-05 13:36:46'),
(21, 1, 'Penyediaan Jasa Pemeliharaan, Biaya Pemeliharaan, dan Pajak Kendaraan\nPerorangan Dinas atau Kendaraan Dinas Jabatan', 10144000000.00, '2026-01-05 13:36:46', '2026-01-05 13:36:46'),
(22, 1, 'Pemeliharaan Peralatan dan Mesin Lainnya', 6090000.00, '2026-01-05 13:36:46', '2026-01-05 13:36:46'),
(23, 1, 'PROGRAM PENGELOLAAN PENDIDIKAN', 67336319268.00, '2026-01-05 13:36:46', '2026-01-05 13:36:46'),
(24, 1, 'Pengelolaan Pendidikan Sekolah Dasar', 37074918550.00, '2026-01-05 13:36:46', '2026-01-05 13:36:46'),
(25, 1, 'Pembangunan Ruang Guru/Kepala Sekolah/TU', 0.00, '2026-01-05 13:36:46', '2026-01-05 13:36:46'),
(26, 1, 'Pembangunan Perpustakaan Sekolah', 189260000.00, '2026-01-05 13:36:46', '2026-01-05 13:36:46'),
(27, 1, 'Pembangunan Sarana, Prasarana dan Utilitas Sekolah', 180232000.00, '2026-01-05 13:36:46', '2026-01-05 13:36:46'),
(28, 1, 'Pengadaan Mebel Sekolah', 14400000.00, '2026-01-05 13:36:46', '2026-01-05 13:36:46'),
(29, 1, 'Pengadaan Perlengkapan Sekolah', 0.00, '2026-01-05 13:36:46', '2026-01-05 13:36:46'),
(30, 1, 'Pembinaan Minat, Bakat dan Kreativitas Siswa', 213000000.00, '2026-01-05 13:36:46', '2026-01-05 13:36:46'),
(31, 1, 'Pengembangan Karir Pendidik dan Tenaga Kependidikan pada Satuan\nPendidikan Sekolah Dasar', 710100000.00, '2026-01-05 13:36:46', '2026-01-05 13:36:46'),
(32, 1, 'Pembinaan Kelembagaan dan Manajemen Sekolah', 48336000.00, '2026-01-05 13:36:46', '2026-01-05 13:36:46'),
(33, 1, 'Pengelolaan Dana BOS Sekolah Dasar', 35610400000.00, '2026-01-05 13:36:46', '2026-01-05 13:36:46'),
(34, 1, 'Peningkatan Kapasitas Pengelolaan Dana BOS Sekolah Dasar', 48490600.00, '2026-01-05 13:36:46', '2026-01-05 13:36:46'),
(35, 1, 'Pembangunan Laboratorium Sekolah Dasar', 0.00, '2026-01-05 13:36:46', '2026-01-05 13:36:46'),
(36, 1, 'Pengembangan konten digital untuk pendidikan', 0.00, '2026-01-05 13:36:46', '2026-01-05 13:36:46'),
(37, 1, 'PROGRAM PENDIDIK DAN TENAGA KEPENDIDIKAN', 204798000.00, '2026-01-05 13:36:46', '2026-01-05 13:36:46'),
(38, 1, 'Pemerataan Kuantitas dan Kualitas Pendidik dan Tenaga Kependidikan bagi\nSatuan Pendidikan Dasar, PAUD, dan Pendidikan Nonformal/Kesetaraan', 20479800000.00, '2026-01-05 13:36:46', '2026-01-05 13:36:46'),
(39, 1, 'Perhitungan dan Pemetaan Pendidik dan Tenaga Kependidikan Satuan\nPendidikan Dasar, PAUD, dan Pendidikan Nonformal/Kesetaraan', 7485500000.00, '2026-01-05 13:36:46', '2026-01-05 13:36:46'),
(40, 1, 'Penataan Pendistribusian Pendidik dan Tenaga Kependidikan bagi Satuan\nPendidikan Dasar, PAUD, dan Pendidikan Nonformal/Kesetaraan', 12994300000.00, '2026-01-05 13:36:46', '2026-01-05 13:36:46'),
(41, 1, 'PROGRAM PENGENDALIAN PERIZINAN PENDIDIKAN', 2206000.00, '2026-01-05 13:36:46', '2026-01-05 13:36:46'),
(42, 1, 'Penerbitan Izin PAUD dan Pendidikan Nonformal yang Diselenggarakan oleh\nMasyarakat', 220600000.00, '2026-01-05 13:36:46', '2026-01-05 13:36:46');

-- --------------------------------------------------------

--
-- Table structure for table `target_rencana`
--

CREATE TABLE `target_rencana` (
  `id` int NOT NULL,
  `opd_id` int UNSIGNED DEFAULT NULL,
  `renstra_target_id` int UNSIGNED DEFAULT NULL,
  `rpjmd_target_id` int UNSIGNED DEFAULT NULL,
  `rencana_aksi` text COLLATE utf8mb4_general_ci,
  `capaian` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `target_triwulan_1` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `target_triwulan_2` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `target_triwulan_3` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `target_triwulan_4` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `penanggung_jawab` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `target_rencana`
--

INSERT INTO `target_rencana` (`id`, `opd_id`, `renstra_target_id`, `rpjmd_target_id`, `rencana_aksi`, `capaian`, `target_triwulan_1`, `target_triwulan_2`, `target_triwulan_3`, `target_triwulan_4`, `penanggung_jawab`, `created_at`, `updated_at`) VALUES
(17, 20, 61, NULL, 'asa', '212', '1', '2', '', '', 'asasa', '2025-11-26 07:43:13', '2025-12-16 04:47:19'),
(18, NULL, NULL, NULL, 'asa', '2', '1', '3', '2', '', 'sdsd', '2025-11-26 20:06:37', '2025-11-26 20:23:39'),
(19, NULL, NULL, NULL, 'sasa', '12', '12', '', '', '', 'qeq', '2025-11-29 02:22:07', '2025-11-29 02:22:07'),
(20, 20, 8, 234, 'asa', '22', '12', '334', '3424', '32423', 'sdadsfsdf', '2026-01-13 21:08:40', '2026-01-13 21:24:01');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int UNSIGNED NOT NULL,
  `username` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `role` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `opd_id` int UNSIGNED DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `password`, `email`, `role`, `opd_id`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'superadmin', '$2y$12$gzePKqh3CNmRuuFavJcR2uNxp6zyDrAZGDGL8wg.bJ.3HIGi/Rdta', 'admin@admin', 'admin', NULL, 0, '2025-08-20 02:26:41', '2025-08-20 02:26:41'),
(2, 'admin_kab', '$2y$12$gzePKqh3CNmRuuFavJcR2uNxp6zyDrAZGDGL8wg.bJ.3HIGi/Rdta', 'adminkabupaten@kabupaten.go.id', 'admin_kab', 46, 0, '2025-08-20 02:26:41', '2025-08-20 02:26:41'),
(3, 'admin_diskominfo', '$2y$12$gzePKqh3CNmRuuFavJcR2uNxp6zyDrAZGDGL8wg.bJ.3HIGi/Rdta', 'admin@diskominfo.kabupaten.go.id', 'admin_opd', 20, 0, '2025-08-20 02:26:41', '2025-08-20 02:26:41'),
(4, 'admin_diknas', '$2y$10$5E8GPKpVRu4Txwfz9O4.L.mK0zLrY2Uyc9.fjK4kEut.T/KBwUHZy', 'admin@diknas.kabupaten.go.id', 'admin_opd', NULL, 0, '2025-08-20 02:26:41', '2025-08-20 02:26:41'),
(5, 'admin_dinkes', '$2y$10$/pBDfgB4gH6GV4RlNrCRSuhQJfpf/OKWJzqzLDPjT9K.U9G1rReQC', 'admin@dinkes.kabupaten.go.id', 'admin_opd', NULL, 0, '2025-08-20 02:26:41', '2025-08-20 02:26:41'),
(6, 'admin_bappeda', '$2y$10$sGlE8SfZgOv10pEjK5nUOu5O/8iPT7QJTEQ16no/kSJCoh4w3iRwG', 'admin@bappeda.kabupaten.go.id', 'admin_opd', NULL, 0, '2025-08-20 02:26:41', '2025-08-20 02:26:41');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `iku`
--
ALTER TABLE `iku`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `iku_program_pendukung`
--
ALTER TABLE `iku_program_pendukung`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_iku_program_pendukung_iku` (`iku_id`);

--
-- Indexes for table `jabatan`
--
ALTER TABLE `jabatan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `jabatan_opd_id_foreign` (`opd_id`);

--
-- Indexes for table `kegiatan_pk`
--
ALTER TABLE `kegiatan_pk`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_kegiatan_program` (`program_id`);

--
-- Indexes for table `lakip`
--
ALTER TABLE `lakip`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_lakip_renstra_target` (`renstra_target_id`),
  ADD KEY `fk_lakip_rpjmd_target` (`rpjmd_target_id`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `monev`
--
ALTER TABLE `monev`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_monev_rencana_tahun` (`target_rencana_id`) USING BTREE,
  ADD KEY `fk_monev_opd` (`opd_id`);

--
-- Indexes for table `opd`
--
ALTER TABLE `opd`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pangkat`
--
ALTER TABLE `pangkat`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pegawai`
--
ALTER TABLE `pegawai`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pegawai_pangkat_id_foreign` (`pangkat_id`),
  ADD KEY `pegawai_jabatan_id_foreign` (`jabatan_id`),
  ADD KEY `pegawai_opd_id_foreign` (`opd_id`);

--
-- Indexes for table `pk`
--
ALTER TABLE `pk`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_pk_parent` (`parent_pk_id`),
  ADD KEY `idx_pk_opd` (`opd_id`),
  ADD KEY `idx_pk_tahun` (`tahun`),
  ADD KEY `idx_pk_jenis` (`jenis`);

--
-- Indexes for table `pk_indikator`
--
ALTER TABLE `pk_indikator`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_pk_ind_sasaran` (`pk_sasaran_id`),
  ADD KEY `idx_pk_ind_satuan` (`id_satuan`);

--
-- Indexes for table `pk_kegiatan`
--
ALTER TABLE `pk_kegiatan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_pk_kegiatan_program` (`pk_program_id`),
  ADD KEY `idx_pk_kegiatan_kegiatan` (`kegiatan_id`);

--
-- Indexes for table `pk_misi`
--
ALTER TABLE `pk_misi`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_pk_misi_pk` (`pk_id`),
  ADD KEY `idx_pk_misi_rpjmd` (`rpjmd_misi_id`);

--
-- Indexes for table `pk_program`
--
ALTER TABLE `pk_program`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_pk_program_program` (`program_id`),
  ADD KEY `idx_pk_program_indikator` (`pk_indikator_id`);

--
-- Indexes for table `pk_referensi`
--
ALTER TABLE `pk_referensi`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_pk_referensi_pk` (`pk_id`),
  ADD KEY `idx_pk_referensi_refpk` (`referensi_pk_id`),
  ADD KEY `idx_pk_referensi_refindikator` (`referensi_indikator_id`);

--
-- Indexes for table `pk_sasaran`
--
ALTER TABLE `pk_sasaran`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_pk_sasaran_pk` (`pk_id`);

--
-- Indexes for table `pk_subkegiatan`
--
ALTER TABLE `pk_subkegiatan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_pk_subkegiatan_kegiatan` (`pk_kegiatan_id`),
  ADD KEY `idx_pk_subkegiatan_subkegiatan` (`subkegiatan_id`);

--
-- Indexes for table `program_pk`
--
ALTER TABLE `program_pk`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `renstra_indikator_sasaran`
--
ALTER TABLE `renstra_indikator_sasaran`
  ADD PRIMARY KEY (`id`),
  ADD KEY `renstra_indikator_sasaran_renstra_sasaran_id_foreign` (`renstra_sasaran_id`);

--
-- Indexes for table `renstra_indikator_tujuan`
--
ALTER TABLE `renstra_indikator_tujuan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_renstra_indikator_tujuan_tujuan` (`tujuan_id`);

--
-- Indexes for table `renstra_sasaran`
--
ALTER TABLE `renstra_sasaran`
  ADD PRIMARY KEY (`id`),
  ADD KEY `renstra_sasaran_opd_id_foreign` (`opd_id`),
  ADD KEY `renstra_sasaran_rpjmd_sasaran_id_foreign` (`renstra_tujuan_id`);

--
-- Indexes for table `renstra_target`
--
ALTER TABLE `renstra_target`
  ADD PRIMARY KEY (`id`),
  ADD KEY `renstra_target_renstra_indikator_id_foreign` (`renstra_indikator_id`);

--
-- Indexes for table `renstra_target_tujuan`
--
ALTER TABLE `renstra_target_tujuan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_renstra_target_tujuan_indikator` (`indikator_tujuan_id`);

--
-- Indexes for table `renstra_tujuan`
--
ALTER TABLE `renstra_tujuan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_renstra_tujuan_rpjmd_sasaran` (`rpjmd_sasaran_id`);

--
-- Indexes for table `rkt`
--
ALTER TABLE `rkt`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_rkt_program_pk` (`program_id`);

--
-- Indexes for table `rkt_kegiatan`
--
ALTER TABLE `rkt_kegiatan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_rkt_kegiatan_rkt` (`rkt_id`),
  ADD KEY `fk_rkt_kegiatan_kegiatan_pk` (`kegiatan_id`);

--
-- Indexes for table `rkt_subkegiatan`
--
ALTER TABLE `rkt_subkegiatan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_rkt_subkegiatan_rkt_kegiatan` (`rkt_kegiatan_id`),
  ADD KEY `fk_rkt_subkegiatan_sub_kegiatan_pk` (`sub_kegiatan_id`);

--
-- Indexes for table `rpjmd_indikator_sasaran`
--
ALTER TABLE `rpjmd_indikator_sasaran`
  ADD PRIMARY KEY (`id`),
  ADD KEY `rpjmd_indikator_sasaran_sasaran_id_foreign` (`sasaran_id`);

--
-- Indexes for table `rpjmd_indikator_tujuan`
--
ALTER TABLE `rpjmd_indikator_tujuan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `rpjmd_indikator_tujuan_tujuan_id_foreign` (`tujuan_id`);

--
-- Indexes for table `rpjmd_misi`
--
ALTER TABLE `rpjmd_misi`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `rpjmd_sasaran`
--
ALTER TABLE `rpjmd_sasaran`
  ADD PRIMARY KEY (`id`),
  ADD KEY `rpjmd_sasaran_tujuan_id_foreign` (`tujuan_id`);

--
-- Indexes for table `rpjmd_target`
--
ALTER TABLE `rpjmd_target`
  ADD PRIMARY KEY (`id`),
  ADD KEY `rpjmd_target_indikator_sasaran_id_foreign` (`indikator_sasaran_id`);

--
-- Indexes for table `rpjmd_target_tujuan`
--
ALTER TABLE `rpjmd_target_tujuan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `rpjmd_target_indikator_tujuan_id_foreign` (`indikator_tujuan_id`);

--
-- Indexes for table `rpjmd_tujuan`
--
ALTER TABLE `rpjmd_tujuan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `rpjmd_tujuan_misi_id_foreign` (`misi_id`);

--
-- Indexes for table `satuan`
--
ALTER TABLE `satuan`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sub_kegiatan_pk`
--
ALTER TABLE `sub_kegiatan_pk`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_subkegiatan_kegiatan` (`kegiatan_id`);

--
-- Indexes for table `target_rencana`
--
ALTER TABLE `target_rencana`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_target_rencana_opd` (`opd_id`),
  ADD KEY `fk_target_rencana_renstra_target` (`renstra_target_id`),
  ADD KEY `fk_target_rencana_rpjmd_target` (`rpjmd_target_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `opd_id` (`opd_id`),
  ADD KEY `role` (`role`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `iku`
--
ALTER TABLE `iku`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `iku_program_pendukung`
--
ALTER TABLE `iku_program_pendukung`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `jabatan`
--
ALTER TABLE `jabatan`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10000;

--
-- AUTO_INCREMENT for table `kegiatan_pk`
--
ALTER TABLE `kegiatan_pk`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `lakip`
--
ALTER TABLE `lakip`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT for table `monev`
--
ALTER TABLE `monev`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `opd`
--
ALTER TABLE `opd`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=210;

--
-- AUTO_INCREMENT for table `pangkat`
--
ALTER TABLE `pangkat`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `pegawai`
--
ALTER TABLE `pegawai`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10000;

--
-- AUTO_INCREMENT for table `pk`
--
ALTER TABLE `pk`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=67;

--
-- AUTO_INCREMENT for table `pk_indikator`
--
ALTER TABLE `pk_indikator`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=100;

--
-- AUTO_INCREMENT for table `pk_kegiatan`
--
ALTER TABLE `pk_kegiatan`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `pk_misi`
--
ALTER TABLE `pk_misi`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `pk_program`
--
ALTER TABLE `pk_program`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=82;

--
-- AUTO_INCREMENT for table `pk_referensi`
--
ALTER TABLE `pk_referensi`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `pk_sasaran`
--
ALTER TABLE `pk_sasaran`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=94;

--
-- AUTO_INCREMENT for table `pk_subkegiatan`
--
ALTER TABLE `pk_subkegiatan`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `program_pk`
--
ALTER TABLE `program_pk`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `renstra_indikator_sasaran`
--
ALTER TABLE `renstra_indikator_sasaran`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `renstra_indikator_tujuan`
--
ALTER TABLE `renstra_indikator_tujuan`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `renstra_sasaran`
--
ALTER TABLE `renstra_sasaran`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `renstra_target`
--
ALTER TABLE `renstra_target`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `renstra_target_tujuan`
--
ALTER TABLE `renstra_target_tujuan`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `renstra_tujuan`
--
ALTER TABLE `renstra_tujuan`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `rkt`
--
ALTER TABLE `rkt`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `rkt_kegiatan`
--
ALTER TABLE `rkt_kegiatan`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `rkt_subkegiatan`
--
ALTER TABLE `rkt_subkegiatan`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `rpjmd_indikator_sasaran`
--
ALTER TABLE `rpjmd_indikator_sasaran`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `rpjmd_indikator_tujuan`
--
ALTER TABLE `rpjmd_indikator_tujuan`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `rpjmd_misi`
--
ALTER TABLE `rpjmd_misi`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `rpjmd_target`
--
ALTER TABLE `rpjmd_target`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=238;

--
-- AUTO_INCREMENT for table `rpjmd_target_tujuan`
--
ALTER TABLE `rpjmd_target_tujuan`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=139;

--
-- AUTO_INCREMENT for table `rpjmd_tujuan`
--
ALTER TABLE `rpjmd_tujuan`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `satuan`
--
ALTER TABLE `satuan`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `sub_kegiatan_pk`
--
ALTER TABLE `sub_kegiatan_pk`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT for table `target_rencana`
--
ALTER TABLE `target_rencana`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `iku_program_pendukung`
--
ALTER TABLE `iku_program_pendukung`
  ADD CONSTRAINT `fk_iku_program_pendukung_iku` FOREIGN KEY (`iku_id`) REFERENCES `iku` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `jabatan`
--
ALTER TABLE `jabatan`
  ADD CONSTRAINT `jabatan_opd_id_foreign` FOREIGN KEY (`opd_id`) REFERENCES `opd` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `kegiatan_pk`
--
ALTER TABLE `kegiatan_pk`
  ADD CONSTRAINT `fk_kegiatan_program` FOREIGN KEY (`program_id`) REFERENCES `program_pk` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
