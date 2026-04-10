-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Apr 10, 2026 at 05:47 PM
-- Server version: 8.0.45
-- PHP Version: 8.4.19

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `esakippringsewu_e-sakip`
--

-- --------------------------------------------------------

--
-- Table structure for table `cascading_indikator_opd`
--

CREATE TABLE `cascading_indikator_opd` (
  `id` int UNSIGNED NOT NULL,
  `cascading_sasaran_id` int UNSIGNED NOT NULL,
  `indikator` text COLLATE utf8mb4_general_ci NOT NULL,
  `satuan` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table structure for table `cascading_sasaran_opd`
--

CREATE TABLE `cascading_sasaran_opd` (
  `id` int UNSIGNED NOT NULL,
  `opd_id` int UNSIGNED NOT NULL,
  `renstra_indikator_sasaran_id` int UNSIGNED NOT NULL,
  `parent_id` int UNSIGNED DEFAULT NULL,
  `es3_indikator_id` int UNSIGNED DEFAULT NULL,
  `level` enum('es2','es3','es4') COLLATE utf8mb4_general_ci NOT NULL,
  `nama_sasaran` text COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
-- Table structure for table `kegiatan_pk`
--

CREATE TABLE `kegiatan_pk` (
  `id` int UNSIGNED NOT NULL,
  `program_id` int UNSIGNED NOT NULL,
  `kode_kegiatan` int NOT NULL,
  `kegiatan` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `tahun_anggaran` year NOT NULL,
  `anggaran` decimal(15,2) DEFAULT '0.00',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
  `id` int NOT NULL,
  `nama_pegawai` varchar(255) NOT NULL,
  `nip_pegawai` varchar(20) NOT NULL,
  `tanggal_lahir` date DEFAULT NULL,
  `opd_id` int NOT NULL,
  `jabatan_id` int NOT NULL,
  `pangkat_id` int NOT NULL,
  `atasan_id` int DEFAULT NULL,
  `password` text NOT NULL,
  `level` enum('ADMIN','PERMITOR','VERIFIKATOR','USER') NOT NULL DEFAULT 'USER',
  `url_foto_pegawai` varchar(255) DEFAULT NULL,
  `tukin` bigint NOT NULL,
  `edited_by` int DEFAULT NULL,
  `first_time` int NOT NULL DEFAULT '0',
  `created_at` text,
  `updated_at` text,
  `no_whatsapp` varchar(50) DEFAULT NULL,
  `device_id` varchar(255) DEFAULT NULL,
  `device_type` enum('WEB','MOBILE') DEFAULT NULL,
  `kategori` varchar(255) DEFAULT NULL,
  `status` varchar(255) DEFAULT NULL,
  `is_banned` tinyint(1) NOT NULL DEFAULT '0',
  `pelanggaran_lokasi` int NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='SELECT *\r\nFROM pegawai\r\nWHERE nip_pegawai = ''1987654321'';\r\n';

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
-- Table structure for table `pk_misi`
--

CREATE TABLE `pk_misi` (
  `id` int UNSIGNED NOT NULL,
  `pk_id` int UNSIGNED NOT NULL,
  `rpjmd_misi_id` int UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
-- Table structure for table `program_pk`
--

CREATE TABLE `program_pk` (
  `id` int UNSIGNED NOT NULL,
  `opd_id` int DEFAULT NULL,
  `kode_program` int NOT NULL,
  `program_kegiatan` text COLLATE utf8mb4_general_ci NOT NULL,
  `tahun_anggaran` year NOT NULL,
  `anggaran` decimal(15,2) NOT NULL DEFAULT '0.00',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


--
-- Table structure for table `renstra_indikator_sasaran`
--

CREATE TABLE `renstra_indikator_sasaran` (
  `id` int UNSIGNED NOT NULL,
  `renstra_sasaran_id` int UNSIGNED NOT NULL,
  `indikator_sasaran` text COLLATE utf8mb4_general_ci NOT NULL,
  `satuan` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `baseline` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `jenis_indikator` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table structure for table `renstra_indikator_tujuan`
--

CREATE TABLE `renstra_indikator_tujuan` (
  `id` int UNSIGNED NOT NULL,
  `tujuan_id` int UNSIGNED NOT NULL,
  `indikator_tujuan` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


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
-- Table structure for table `rpjmd_cascading`
--

CREATE TABLE `rpjmd_cascading` (
  `id` int UNSIGNED NOT NULL,
  `indikator_sasaran_id` int UNSIGNED NOT NULL,
  `opd_id` int UNSIGNED NOT NULL,
  `pk_program_id` int UNSIGNED NOT NULL,
  `tahun` year NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `baseline` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table structure for table `rpjmd_indikator_tujuan`
--

CREATE TABLE `rpjmd_indikator_tujuan` (
  `id` int UNSIGNED NOT NULL,
  `tujuan_id` int UNSIGNED NOT NULL,
  `indikator_tujuan` text COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;



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
-- Table structure for table `satuan`
--

CREATE TABLE `satuan` (
  `id` int UNSIGNED NOT NULL,
  `satuan` varchar(100) COLLATE utf8mb4_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


--
-- Table structure for table `sub_kegiatan_pk`
--

CREATE TABLE `sub_kegiatan_pk` (
  `id` int UNSIGNED NOT NULL,
  `kegiatan_id` int UNSIGNED NOT NULL,
  `kode_sub_kegiatan` int NOT NULL,
  `sub_kegiatan` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `tahun_anggaran` year NOT NULL,
  `anggaran` decimal(15,2) DEFAULT '0.00',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


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


-- Indexes for table `cascading_indikator_opd`
--
ALTER TABLE `cascading_indikator_opd`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_indikator_cascading` (`cascading_sasaran_id`);

--
-- Indexes for table `cascading_sasaran_opd`
--
ALTER TABLE `cascading_sasaran_opd`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_cascading_opd` (`opd_id`),
  ADD KEY `idx_cascading_parent` (`parent_id`),
  ADD KEY `idx_cascading_renstra_indikator` (`renstra_indikator_sasaran_id`);

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
  ADD KEY `id_jabatan` (`jabatan_id`),
  ADD KEY `id_opd` (`opd_id`),
  ADD KEY `id_pangkat` (`pangkat_id`);

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
-- Indexes for table `rpjmd_cascading`
--
ALTER TABLE `rpjmd_cascading`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_map` (`indikator_sasaran_id`,`opd_id`,`pk_program_id`,`tahun`),
  ADD KEY `fk_cascade_program` (`pk_program_id`),
  ADD KEY `fk_cascade_opd` (`opd_id`);

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
-- AUTO_INCREMENT for table `cascading_indikator_opd`
--
ALTER TABLE `cascading_indikator_opd`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;

--
-- AUTO_INCREMENT for table `cascading_sasaran_opd`
--
ALTER TABLE `cascading_sasaran_opd`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT for table `iku`
--
ALTER TABLE `iku`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=155;

--
-- AUTO_INCREMENT for table `iku_program_pendukung`
--
ALTER TABLE `iku_program_pendukung`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=292;

--
-- AUTO_INCREMENT for table `jabatan`
--
ALTER TABLE `jabatan`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10007;

--
-- AUTO_INCREMENT for table `kegiatan_pk`
--
ALTER TABLE `kegiatan_pk`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=523;

--
-- AUTO_INCREMENT for table `lakip`
--
ALTER TABLE `lakip`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=69;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT for table `monev`
--
ALTER TABLE `monev`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

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
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=100209;

--
-- AUTO_INCREMENT for table `pk`
--
ALTER TABLE `pk`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=402;

--
-- AUTO_INCREMENT for table `pk_indikator`
--
ALTER TABLE `pk_indikator`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1846;

--
-- AUTO_INCREMENT for table `pk_kegiatan`
--
ALTER TABLE `pk_kegiatan`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1731;

--
-- AUTO_INCREMENT for table `pk_misi`
--
ALTER TABLE `pk_misi`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=105;

--
-- AUTO_INCREMENT for table `pk_program`
--
ALTER TABLE `pk_program`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2418;

--
-- AUTO_INCREMENT for table `pk_referensi`
--
ALTER TABLE `pk_referensi`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=298;

--
-- AUTO_INCREMENT for table `pk_sasaran`
--
ALTER TABLE `pk_sasaran`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1543;

--
-- AUTO_INCREMENT for table `pk_subkegiatan`
--
ALTER TABLE `pk_subkegiatan`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=829;

--
-- AUTO_INCREMENT for table `program_pk`
--
ALTER TABLE `program_pk`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=211;

--
-- AUTO_INCREMENT for table `renstra_indikator_sasaran`
--
ALTER TABLE `renstra_indikator_sasaran`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=437;

--
-- AUTO_INCREMENT for table `renstra_indikator_tujuan`
--
ALTER TABLE `renstra_indikator_tujuan`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=380;

--
-- AUTO_INCREMENT for table `renstra_sasaran`
--
ALTER TABLE `renstra_sasaran`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=152;

--
-- AUTO_INCREMENT for table `renstra_target`
--
ALTER TABLE `renstra_target`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2397;

--
-- AUTO_INCREMENT for table `renstra_target_tujuan`
--
ALTER TABLE `renstra_target_tujuan`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1920;

--
-- AUTO_INCREMENT for table `renstra_tujuan`
--
ALTER TABLE `renstra_tujuan`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=94;

--
-- AUTO_INCREMENT for table `rkt`
--
ALTER TABLE `rkt`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=456;

--
-- AUTO_INCREMENT for table `rkt_kegiatan`
--
ALTER TABLE `rkt_kegiatan`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=823;

--
-- AUTO_INCREMENT for table `rkt_subkegiatan`
--
ALTER TABLE `rkt_subkegiatan`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2379;

--
-- AUTO_INCREMENT for table `rpjmd_cascading`
--
ALTER TABLE `rpjmd_cascading`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `rpjmd_indikator_sasaran`
--
ALTER TABLE `rpjmd_indikator_sasaran`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=116;

--
-- AUTO_INCREMENT for table `rpjmd_indikator_tujuan`
--
ALTER TABLE `rpjmd_indikator_tujuan`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT for table `rpjmd_misi`
--
ALTER TABLE `rpjmd_misi`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `rpjmd_sasaran`
--
ALTER TABLE `rpjmd_sasaran`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=96;

--
-- AUTO_INCREMENT for table `rpjmd_target`
--
ALTER TABLE `rpjmd_target`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=574;

--
-- AUTO_INCREMENT for table `rpjmd_target_tujuan`
--
ALTER TABLE `rpjmd_target_tujuan`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=214;

--
-- AUTO_INCREMENT for table `rpjmd_tujuan`
--
ALTER TABLE `rpjmd_tujuan`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT for table `satuan`
--
ALTER TABLE `satuan`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT for table `sub_kegiatan_pk`
--
ALTER TABLE `sub_kegiatan_pk`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1634;

--
-- AUTO_INCREMENT for table `target_rencana`
--
ALTER TABLE `target_rencana`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=89;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=57;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `cascading_indikator_opd`
--
ALTER TABLE `cascading_indikator_opd`
  ADD CONSTRAINT `fk_indikator_cascading` FOREIGN KEY (`cascading_sasaran_id`) REFERENCES `cascading_sasaran_opd` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `cascading_sasaran_opd`
--
ALTER TABLE `cascading_sasaran_opd`
  ADD CONSTRAINT `fk_cascading_parent` FOREIGN KEY (`parent_id`) REFERENCES `cascading_sasaran_opd` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_cascading_renstra_indikator` FOREIGN KEY (`renstra_indikator_sasaran_id`) REFERENCES `renstra_indikator_sasaran` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

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

--
-- Constraints for table `lakip`
--
ALTER TABLE `lakip`
  ADD CONSTRAINT `fk_lakip_renstra_target` FOREIGN KEY (`renstra_target_id`) REFERENCES `renstra_target` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_lakip_rpjmd_target` FOREIGN KEY (`rpjmd_target_id`) REFERENCES `rpjmd_target` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `monev`
--
ALTER TABLE `monev`
  ADD CONSTRAINT `fk_monev_opd` FOREIGN KEY (`opd_id`) REFERENCES `opd` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_monev_target` FOREIGN KEY (`target_rencana_id`) REFERENCES `target_rencana` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `pk`
--
ALTER TABLE `pk`
  ADD CONSTRAINT `fk_pk_parent` FOREIGN KEY (`parent_pk_id`) REFERENCES `pk` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `pk_indikator`
--
ALTER TABLE `pk_indikator`
  ADD CONSTRAINT `fk_pk_indikator_id_satuan` FOREIGN KEY (`id_satuan`) REFERENCES `satuan` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pk_indikator_sasaran` FOREIGN KEY (`pk_sasaran_id`) REFERENCES `pk_sasaran` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `pk_kegiatan`
--
ALTER TABLE `pk_kegiatan`
  ADD CONSTRAINT `fk_pk_kegiatan_program` FOREIGN KEY (`pk_program_id`) REFERENCES `pk_program` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `pk_misi`
--
ALTER TABLE `pk_misi`
  ADD CONSTRAINT `fk_pk_misi_pk` FOREIGN KEY (`pk_id`) REFERENCES `pk` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `pk_program`
--
ALTER TABLE `pk_program`
  ADD CONSTRAINT `fk_pk_program_indikator` FOREIGN KEY (`pk_indikator_id`) REFERENCES `pk_indikator` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `pk_referensi`
--
ALTER TABLE `pk_referensi`
  ADD CONSTRAINT `fk_pk_referensi_pk` FOREIGN KEY (`pk_id`) REFERENCES `pk` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pk_referensi_ref_indikator` FOREIGN KEY (`referensi_indikator_id`) REFERENCES `pk_indikator` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pk_referensi_ref_pk` FOREIGN KEY (`referensi_pk_id`) REFERENCES `pk` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `pk_sasaran`
--
ALTER TABLE `pk_sasaran`
  ADD CONSTRAINT `fk_pk_sasaran_pk` FOREIGN KEY (`pk_id`) REFERENCES `pk` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `pk_subkegiatan`
--
ALTER TABLE `pk_subkegiatan`
  ADD CONSTRAINT `fk_pk_subkegiatan_kegiatan` FOREIGN KEY (`pk_kegiatan_id`) REFERENCES `pk_kegiatan` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `renstra_indikator_tujuan`
--
ALTER TABLE `renstra_indikator_tujuan`
  ADD CONSTRAINT `fk_renstra_indikator_tujuan_tujuan` FOREIGN KEY (`tujuan_id`) REFERENCES `renstra_tujuan` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `renstra_sasaran`
--
ALTER TABLE `renstra_sasaran`
  ADD CONSTRAINT `fk_renstra_sasaran_renstra_tujuan` FOREIGN KEY (`renstra_tujuan_id`) REFERENCES `renstra_tujuan` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `renstra_target_tujuan`
--
ALTER TABLE `renstra_target_tujuan`
  ADD CONSTRAINT `fk_renstra_target_tujuan_indikator` FOREIGN KEY (`indikator_tujuan_id`) REFERENCES `renstra_indikator_tujuan` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `rkt`
--
ALTER TABLE `rkt`
  ADD CONSTRAINT `fk_rkt_program_pk` FOREIGN KEY (`program_id`) REFERENCES `program_pk` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `rkt_kegiatan`
--
ALTER TABLE `rkt_kegiatan`
  ADD CONSTRAINT `fk_rkt_kegiatan_kegiatan_pk` FOREIGN KEY (`kegiatan_id`) REFERENCES `kegiatan_pk` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_rkt_kegiatan_rkt` FOREIGN KEY (`rkt_id`) REFERENCES `rkt` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `rkt_subkegiatan`
--
ALTER TABLE `rkt_subkegiatan`
  ADD CONSTRAINT `fk_rkt_subkegiatan_rkt_kegiatan` FOREIGN KEY (`rkt_kegiatan_id`) REFERENCES `rkt_kegiatan` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_rkt_subkegiatan_sub_kegiatan_pk` FOREIGN KEY (`sub_kegiatan_id`) REFERENCES `sub_kegiatan_pk` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `rpjmd_cascading`
--
ALTER TABLE `rpjmd_cascading`
  ADD CONSTRAINT `fk_cascade_indikator` FOREIGN KEY (`indikator_sasaran_id`) REFERENCES `rpjmd_indikator_sasaran` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_cascade_opd` FOREIGN KEY (`opd_id`) REFERENCES `opd` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_cascade_program` FOREIGN KEY (`pk_program_id`) REFERENCES `pk_program` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `sub_kegiatan_pk`
--
ALTER TABLE `sub_kegiatan_pk`
  ADD CONSTRAINT `fk_subkegiatan_kegiatan` FOREIGN KEY (`kegiatan_id`) REFERENCES `kegiatan_pk` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `target_rencana`
--
ALTER TABLE `target_rencana`
  ADD CONSTRAINT `fk_target_rencana_opd` FOREIGN KEY (`opd_id`) REFERENCES `opd` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_target_rencana_renstra_target` FOREIGN KEY (`renstra_target_id`) REFERENCES `renstra_target` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_target_rencana_rpjmd_target` FOREIGN KEY (`rpjmd_target_id`) REFERENCES `rpjmd_target` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
