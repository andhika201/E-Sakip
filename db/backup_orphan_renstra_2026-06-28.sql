-- Backup baris orphan renstra (opd_id=1 tidak ada di tabel opd) sebelum cleanup FK 2026-06-28
-- Restore: mysql -u root test_sakip < db/backup_orphan_renstra_2026-06-28.sql

-- MySQL dump 10.13  Distrib 8.4.3, for Win64 (x86_64)
--
-- Host: localhost    Database: test_sakip
-- ------------------------------------------------------
-- Server version	8.4.3

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Dumping data for table `renstra_sasaran`
--
-- WHERE:  id IN (35,58)

LOCK TABLES `renstra_sasaran` WRITE;
/*!40000 ALTER TABLE `renstra_sasaran` DISABLE KEYS */;
INSERT INTO `renstra_sasaran` (`id`, `opd_id`, `renstra_tujuan_id`, `csf`, `sasaran`, `status`, `tahun_mulai`, `tahun_akhir`, `created_at`, `updated_at`) VALUES (35,1,28,NULL,'sasaran','draft',2025,2029,'2026-01-15 02:11:38','2026-01-21 08:29:53');
INSERT INTO `renstra_sasaran` (`id`, `opd_id`, `renstra_tujuan_id`, `csf`, `sasaran`, `status`, `tahun_mulai`, `tahun_akhir`, `created_at`, `updated_at`) VALUES (58,1,42,NULL,'sasaran2','draft',2025,2029,'2026-01-15 03:54:03','2026-01-15 03:54:03');
/*!40000 ALTER TABLE `renstra_sasaran` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-06-28 11:51:31
-- MySQL dump 10.13  Distrib 8.4.3, for Win64 (x86_64)
--
-- Host: localhost    Database: test_sakip
-- ------------------------------------------------------
-- Server version	8.4.3

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Dumping data for table `renstra_indikator_sasaran`
--
-- WHERE:  id IN (77,112)

LOCK TABLES `renstra_indikator_sasaran` WRITE;
/*!40000 ALTER TABLE `renstra_indikator_sasaran` DISABLE KEYS */;
INSERT INTO `renstra_indikator_sasaran` (`id`, `renstra_sasaran_id`, `indikator_sasaran`, `satuan`, `baseline`, `jenis_indikator`, `created_at`, `updated_at`) VALUES (77,35,'indikator','7','','positif','2026-01-15 09:14:55','2026-01-15 09:14:55');
INSERT INTO `renstra_indikator_sasaran` (`id`, `renstra_sasaran_id`, `indikator_sasaran`, `satuan`, `baseline`, `jenis_indikator`, `created_at`, `updated_at`) VALUES (112,58,'sasaran 2','20','','negatif','2026-01-15 03:54:03','2026-01-15 03:54:03');
/*!40000 ALTER TABLE `renstra_indikator_sasaran` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-06-28 11:51:31
-- MySQL dump 10.13  Distrib 8.4.3, for Win64 (x86_64)
--
-- Host: localhost    Database: test_sakip
-- ------------------------------------------------------
-- Server version	8.4.3

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Dumping data for table `renstra_target`
--
-- WHERE:  id IN (440,441,442,443,444,617,618,619,620,621)

LOCK TABLES `renstra_target` WRITE;
/*!40000 ALTER TABLE `renstra_target` DISABLE KEYS */;
INSERT INTO `renstra_target` (`id`, `renstra_indikator_id`, `tahun`, `target`, `created_at`, `updated_at`) VALUES (440,77,2025,'5','2026-01-15 09:14:55','2026-01-15 09:14:55');
INSERT INTO `renstra_target` (`id`, `renstra_indikator_id`, `tahun`, `target`, `created_at`, `updated_at`) VALUES (441,77,2026,'5','2026-01-15 09:14:55','2026-01-15 09:14:55');
INSERT INTO `renstra_target` (`id`, `renstra_indikator_id`, `tahun`, `target`, `created_at`, `updated_at`) VALUES (442,77,2027,'5','2026-01-15 09:14:55','2026-01-15 09:14:55');
INSERT INTO `renstra_target` (`id`, `renstra_indikator_id`, `tahun`, `target`, `created_at`, `updated_at`) VALUES (443,77,2028,'5','2026-01-15 09:14:55','2026-01-15 09:14:55');
INSERT INTO `renstra_target` (`id`, `renstra_indikator_id`, `tahun`, `target`, `created_at`, `updated_at`) VALUES (444,77,2029,'5','2026-01-15 09:14:55','2026-01-15 09:14:55');
INSERT INTO `renstra_target` (`id`, `renstra_indikator_id`, `tahun`, `target`, `created_at`, `updated_at`) VALUES (617,112,2025,'8','2026-01-15 03:54:03','2026-01-15 03:54:03');
INSERT INTO `renstra_target` (`id`, `renstra_indikator_id`, `tahun`, `target`, `created_at`, `updated_at`) VALUES (618,112,2026,'8','2026-01-15 03:54:03','2026-01-15 03:54:03');
INSERT INTO `renstra_target` (`id`, `renstra_indikator_id`, `tahun`, `target`, `created_at`, `updated_at`) VALUES (619,112,2027,'8','2026-01-15 03:54:03','2026-01-15 03:54:03');
INSERT INTO `renstra_target` (`id`, `renstra_indikator_id`, `tahun`, `target`, `created_at`, `updated_at`) VALUES (620,112,2028,'8','2026-01-15 03:54:03','2026-01-15 03:54:03');
INSERT INTO `renstra_target` (`id`, `renstra_indikator_id`, `tahun`, `target`, `created_at`, `updated_at`) VALUES (621,112,2029,'8','2026-01-15 03:54:03','2026-01-15 03:54:03');
/*!40000 ALTER TABLE `renstra_target` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-06-28 11:51:32
