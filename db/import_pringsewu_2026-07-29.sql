-- =====================================================================
-- Impor data produksi `esakippringsewu_e-sakip` ke database aplikasi
-- Mode  : GANTI TOTAL (truncate 43 tabel SAKIP, lalu load dari staging)
-- Sumber: database staging `stage_pringsewu` (hasil impor dump mentah)
-- Tanggal: 2026-07-29
--
-- Cara pakai (skrip ini membaca dari staging, jadi siapkan dulu):
--   mysql -uroot -e "DROP DATABASE IF EXISTS stage_pringsewu; CREATE DATABASE stage_pringsewu CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;"
--   mysql -uroot stage_pringsewu < esakippringsewu_e-sakip.sql
--   mysql -uroot test_sakip     < db/import_pringsewu_2026-07-29.sql
-- Skrip ini idempoten: boleh dijalankan ulang kapan saja.
--
-- Tabel lokal yang TIDAK disentuh:
--   users, roles, role_permissions, app_settings, activity_logs,
--   migrations, dashboard_status_thresholds, satuan_skala,
--   iku_sasaran, iku_indikator, iku_target, iku_program,
--   lakip_analisis_faktor, lakip_efisiensi_program
--
-- Penyesuaian skema (dump -> aplikasi):
--   monev.total          : varchar -> decimal(10,2) (tanda % dibuang,
--                          nilai non-numerik spt 'BB'/'-' jadi NULL)
--   satuan.tipe          : kolom lokal, nilai 'predikat' dipulihkan
--   monev.metode_perhitungan, monev.target_sub_rencana_id : kolom lokal,
--                          pakai default (NULL / 0)
--   target_rencana.capaian: kolom dump yang sudah dihapus di aplikasi,
--                          diarsipkan ke _bak_target_rencana_capaian_pringsewu
-- =====================================================================

USE test_sakip;

SET NAMES utf8mb4;
SET SESSION sql_mode = 'NO_AUTO_VALUE_ON_ZERO';
SET FOREIGN_KEY_CHECKS = 0;
SET UNIQUE_CHECKS = 0;

-- --- 1. Simpan nilai kolom lokal yang harus bertahan ------------------
DROP TEMPORARY TABLE IF EXISTS _tmp_satuan_tipe;
CREATE TEMPORARY TABLE _tmp_satuan_tipe AS
  SELECT id, tipe FROM test_sakip.satuan WHERE tipe <> 'angka';

-- --- 2. Arsipkan kolom `capaian` milik dump (tidak ada di aplikasi) ---
DROP TABLE IF EXISTS test_sakip._bak_target_rencana_capaian_pringsewu;
CREATE TABLE test_sakip._bak_target_rencana_capaian_pringsewu AS
  SELECT id, capaian FROM stage_pringsewu.target_rencana WHERE capaian IS NOT NULL;

-- --- 3. Bersihkan baris anak lokal yang induknya akan berganti arti ---
--     (target_rencana id 108/110/111 di dump = record berbeda)
DELETE FROM test_sakip.target_sub_rencana;
DELETE FROM test_sakip.monev_anggaran;
--     baris analisis/efisiensi LAKIP lokal juga menunjuk renstra_target lama
DELETE FROM test_sakip.lakip_analisis_faktor;
DELETE FROM test_sakip.lakip_efisiensi_program;

-- --- 4. Kosongkan tabel tujuan -----------------------------------------
TRUNCATE TABLE test_sakip.`opd`;
TRUNCATE TABLE test_sakip.`pangkat`;
TRUNCATE TABLE test_sakip.`jabatan`;
TRUNCATE TABLE test_sakip.`pegawai`;
TRUNCATE TABLE test_sakip.`permissions`;
TRUNCATE TABLE test_sakip.`satuan`;
TRUNCATE TABLE test_sakip.`rpjmd_visi`;
TRUNCATE TABLE test_sakip.`rpjmd_misi`;
TRUNCATE TABLE test_sakip.`rpjmd_tujuan`;
TRUNCATE TABLE test_sakip.`rpjmd_sasaran`;
TRUNCATE TABLE test_sakip.`rpjmd_indikator_tujuan`;
TRUNCATE TABLE test_sakip.`rpjmd_indikator_sasaran`;
TRUNCATE TABLE test_sakip.`rpjmd_target`;
TRUNCATE TABLE test_sakip.`rpjmd_target_tujuan`;
TRUNCATE TABLE test_sakip.`renstra_tujuan`;
TRUNCATE TABLE test_sakip.`renstra_sasaran`;
TRUNCATE TABLE test_sakip.`renstra_indikator_tujuan`;
TRUNCATE TABLE test_sakip.`renstra_indikator_sasaran`;
TRUNCATE TABLE test_sakip.`renstra_target`;
TRUNCATE TABLE test_sakip.`renstra_target_tujuan`;
TRUNCATE TABLE test_sakip.`pk`;
TRUNCATE TABLE test_sakip.`pk_sasaran`;
TRUNCATE TABLE test_sakip.`pk_indikator`;
TRUNCATE TABLE test_sakip.`pk_misi`;
TRUNCATE TABLE test_sakip.`pk_referensi`;
TRUNCATE TABLE test_sakip.`pk_sasaran_opd`;
TRUNCATE TABLE test_sakip.`pk_program`;
TRUNCATE TABLE test_sakip.`pk_kegiatan`;
TRUNCATE TABLE test_sakip.`pk_subkegiatan`;
TRUNCATE TABLE test_sakip.`program_pk`;
TRUNCATE TABLE test_sakip.`kegiatan_pk`;
TRUNCATE TABLE test_sakip.`sub_kegiatan_pk`;
TRUNCATE TABLE test_sakip.`rkt`;
TRUNCATE TABLE test_sakip.`rkt_kegiatan`;
TRUNCATE TABLE test_sakip.`rkt_subkegiatan`;
TRUNCATE TABLE test_sakip.`cascading_sasaran_opd`;
TRUNCATE TABLE test_sakip.`cascading_indikator_opd`;
TRUNCATE TABLE test_sakip.`rpjmd_cascading`;
TRUNCATE TABLE test_sakip.`target_rencana`;
TRUNCATE TABLE test_sakip.`monev`;
TRUNCATE TABLE test_sakip.`lakip`;
TRUNCATE TABLE test_sakip.`iku`;
TRUNCATE TABLE test_sakip.`iku_program_pendukung`;

-- --- 5. Muat data dari staging ----------------------------------------
INSERT INTO test_sakip.`opd` (`id`, `simpeg_id`, `nama_opd`, `singkatan`, `alamat_opd`, `id_kepala_opd`, `lat_opd`, `long_opd`, `edited_by`, `created_at`, `updated_at`)
  SELECT `id`, `simpeg_id`, `nama_opd`, `singkatan`, `alamat_opd`, `id_kepala_opd`, `lat_opd`, `long_opd`, `edited_by`, `created_at`, `updated_at` FROM stage_pringsewu.`opd`;
INSERT INTO test_sakip.`pangkat` (`id`, `simpeg_id`, `nama_pangkat`, `golongan`, `edited_by`, `created_at`, `updated_at`)
  SELECT `id`, `simpeg_id`, `nama_pangkat`, `golongan`, `edited_by`, `created_at`, `updated_at` FROM stage_pringsewu.`pangkat`;
INSERT INTO test_sakip.`jabatan` (`id`, `simpeg_id`, `opd_id`, `nama_jabatan`, `tupoksi`, `edited_by`, `created_at`, `updated_at`, `eselon`)
  SELECT `id`, `simpeg_id`, `opd_id`, `nama_jabatan`, `tupoksi`, `edited_by`, `created_at`, `updated_at`, `eselon` FROM stage_pringsewu.`jabatan`;
INSERT INTO test_sakip.`pegawai` (`id`, `nama_pegawai`, `nip_pegawai`, `tanggal_lahir`, `opd_id`, `jabatan_id`, `pangkat_id`, `atasan_id`, `password`, `level`, `is_plt`, `url_foto_pegawai`, `tukin`, `edited_by`, `first_time`, `created_at`, `updated_at`, `no_whatsapp`, `device_id`, `device_type`, `kategori`, `status`, `is_banned`, `pelanggaran_lokasi`)
  SELECT `id`, `nama_pegawai`, `nip_pegawai`, `tanggal_lahir`, `opd_id`, `jabatan_id`, `pangkat_id`, `atasan_id`, `password`, `level`, `is_plt`, `url_foto_pegawai`, `tukin`, `edited_by`, `first_time`, `created_at`, `updated_at`, `no_whatsapp`, `device_id`, `device_type`, `kategori`, `status`, `is_banned`, `pelanggaran_lokasi` FROM stage_pringsewu.`pegawai`;
INSERT INTO test_sakip.`permissions` (`id`, `name`, `label`, `grup`, `created_at`, `updated_at`)
  SELECT `id`, `name`, `label`, `grup`, `created_at`, `updated_at` FROM stage_pringsewu.`permissions`;
INSERT INTO test_sakip.`satuan` (`id`, `satuan`)
  SELECT `id`, `satuan` FROM stage_pringsewu.`satuan`;
INSERT INTO test_sakip.`rpjmd_visi` (`id`, `visi`, `created_at`, `updated_at`)
  SELECT `id`, `visi`, `created_at`, `updated_at` FROM stage_pringsewu.`rpjmd_visi`;
INSERT INTO test_sakip.`rpjmd_misi` (`id`, `rpjmd_visi_id`, `misi`, `status`, `tahun_mulai`, `tahun_akhir`, `created_at`, `updated_at`)
  SELECT `id`, `rpjmd_visi_id`, `misi`, `status`, `tahun_mulai`, `tahun_akhir`, `created_at`, `updated_at` FROM stage_pringsewu.`rpjmd_misi`;
INSERT INTO test_sakip.`rpjmd_tujuan` (`id`, `misi_id`, `tujuan_rpjmd`, `created_at`, `updated_at`)
  SELECT `id`, `misi_id`, `tujuan_rpjmd`, `created_at`, `updated_at` FROM stage_pringsewu.`rpjmd_tujuan`;
INSERT INTO test_sakip.`rpjmd_sasaran` (`id`, `tujuan_id`, `status`, `sasaran_rpjmd`, `csf`, `created_at`, `updated_at`)
  SELECT `id`, `tujuan_id`, `status`, `sasaran_rpjmd`, `csf`, `created_at`, `updated_at` FROM stage_pringsewu.`rpjmd_sasaran`;
INSERT INTO test_sakip.`rpjmd_indikator_tujuan` (`id`, `tujuan_id`, `indikator_tujuan`, `created_at`, `updated_at`)
  SELECT `id`, `tujuan_id`, `indikator_tujuan`, `created_at`, `updated_at` FROM stage_pringsewu.`rpjmd_indikator_tujuan`;
INSERT INTO test_sakip.`rpjmd_indikator_sasaran` (`id`, `sasaran_id`, `indikator_sasaran`, `definisi_op`, `satuan`, `jenis_indikator`, `created_at`, `updated_at`, `baseline`)
  SELECT `id`, `sasaran_id`, `indikator_sasaran`, `definisi_op`, `satuan`, `jenis_indikator`, `created_at`, `updated_at`, `baseline` FROM stage_pringsewu.`rpjmd_indikator_sasaran`;
INSERT INTO test_sakip.`rpjmd_target` (`id`, `indikator_sasaran_id`, `tahun`, `target_tahunan`, `created_at`, `updated_at`)
  SELECT `id`, `indikator_sasaran_id`, `tahun`, `target_tahunan`, `created_at`, `updated_at` FROM stage_pringsewu.`rpjmd_target`;
INSERT INTO test_sakip.`rpjmd_target_tujuan` (`id`, `indikator_tujuan_id`, `tahun`, `target_tahunan`, `created_at`, `updated_at`)
  SELECT `id`, `indikator_tujuan_id`, `tahun`, `target_tahunan`, `created_at`, `updated_at` FROM stage_pringsewu.`rpjmd_target_tujuan`;
INSERT INTO test_sakip.`renstra_tujuan` (`id`, `rpjmd_sasaran_id`, `tujuan`, `created_at`, `updated_at`)
  SELECT `id`, `rpjmd_sasaran_id`, `tujuan`, `created_at`, `updated_at` FROM stage_pringsewu.`renstra_tujuan`;
INSERT INTO test_sakip.`renstra_sasaran` (`id`, `opd_id`, `renstra_tujuan_id`, `csf`, `sasaran`, `status`, `tahun_mulai`, `tahun_akhir`, `created_at`, `updated_at`)
  SELECT `id`, `opd_id`, `renstra_tujuan_id`, `csf`, `sasaran`, `status`, `tahun_mulai`, `tahun_akhir`, `created_at`, `updated_at` FROM stage_pringsewu.`renstra_sasaran`;
INSERT INTO test_sakip.`renstra_indikator_tujuan` (`id`, `tujuan_id`, `indikator_tujuan`, `created_at`, `updated_at`)
  SELECT `id`, `tujuan_id`, `indikator_tujuan`, `created_at`, `updated_at` FROM stage_pringsewu.`renstra_indikator_tujuan`;
INSERT INTO test_sakip.`renstra_indikator_sasaran` (`id`, `renstra_sasaran_id`, `indikator_sasaran`, `satuan`, `baseline`, `jenis_indikator`, `created_at`, `updated_at`)
  SELECT `id`, `renstra_sasaran_id`, `indikator_sasaran`, `satuan`, `baseline`, `jenis_indikator`, `created_at`, `updated_at` FROM stage_pringsewu.`renstra_indikator_sasaran`;
INSERT INTO test_sakip.`renstra_target` (`id`, `renstra_indikator_id`, `tahun`, `target`, `created_at`, `updated_at`)
  SELECT `id`, `renstra_indikator_id`, `tahun`, `target`, `created_at`, `updated_at` FROM stage_pringsewu.`renstra_target`;
INSERT INTO test_sakip.`renstra_target_tujuan` (`id`, `indikator_tujuan_id`, `tahun`, `target_tahunan`, `created_at`, `updated_at`)
  SELECT `id`, `indikator_tujuan_id`, `tahun`, `target_tahunan`, `created_at`, `updated_at` FROM stage_pringsewu.`renstra_target_tujuan`;
INSERT INTO test_sakip.`pk` (`id`, `parent_pk_id`, `opd_id`, `tahun`, `jenis`, `pihak_1`, `is_plt_pihak_1`, `is_plh_pihak_1`, `jabatan_pihak_1_manual`, `pihak_2`, `is_plt_pihak_2`, `is_plh_pihak_2`, `jabatan_pihak_2_manual`, `tanggal`, `created_at`, `updated_at`)
  SELECT `id`, `parent_pk_id`, `opd_id`, `tahun`, `jenis`, `pihak_1`, `is_plt_pihak_1`, `is_plh_pihak_1`, `jabatan_pihak_1_manual`, `pihak_2`, `is_plt_pihak_2`, `is_plh_pihak_2`, `jabatan_pihak_2_manual`, `tanggal`, `created_at`, `updated_at` FROM stage_pringsewu.`pk`;
INSERT INTO test_sakip.`pk_sasaran` (`id`, `pk_id`, `jenis`, `sasaran`, `created_at`, `updated_at`)
  SELECT `id`, `pk_id`, `jenis`, `sasaran`, `created_at`, `updated_at` FROM stage_pringsewu.`pk_sasaran`;
INSERT INTO test_sakip.`pk_indikator` (`id`, `pk_sasaran_id`, `jenis`, `indikator`, `jenis_indikator`, `id_satuan`, `target`, `created_at`, `updated_at`)
  SELECT `id`, `pk_sasaran_id`, `jenis`, `indikator`, `jenis_indikator`, `id_satuan`, `target`, `created_at`, `updated_at` FROM stage_pringsewu.`pk_indikator`;
INSERT INTO test_sakip.`pk_misi` (`id`, `pk_id`, `rpjmd_misi_id`, `created_at`, `updated_at`)
  SELECT `id`, `pk_id`, `rpjmd_misi_id`, `created_at`, `updated_at` FROM stage_pringsewu.`pk_misi`;
INSERT INTO test_sakip.`pk_referensi` (`id`, `pk_id`, `referensi_pk_id`, `referensi_indikator_id`, `created_at`, `updated_at`)
  SELECT `id`, `pk_id`, `referensi_pk_id`, `referensi_indikator_id`, `created_at`, `updated_at` FROM stage_pringsewu.`pk_referensi`;
INSERT INTO test_sakip.`pk_sasaran_opd` (`id`, `pk_sasaran_id`, `opd_id`, `created_at`)
  SELECT `id`, `pk_sasaran_id`, `opd_id`, `created_at` FROM stage_pringsewu.`pk_sasaran_opd`;
INSERT INTO test_sakip.`pk_program` (`id`, `program_id`, `pk_indikator_id`, `created_at`, `updated_at`)
  SELECT `id`, `program_id`, `pk_indikator_id`, `created_at`, `updated_at` FROM stage_pringsewu.`pk_program`;
INSERT INTO test_sakip.`pk_kegiatan` (`id`, `pk_program_id`, `kegiatan_id`, `created_at`, `updated_at`)
  SELECT `id`, `pk_program_id`, `kegiatan_id`, `created_at`, `updated_at` FROM stage_pringsewu.`pk_kegiatan`;
INSERT INTO test_sakip.`pk_subkegiatan` (`id`, `pk_kegiatan_id`, `subkegiatan_id`, `created_at`, `updated_at`)
  SELECT `id`, `pk_kegiatan_id`, `subkegiatan_id`, `created_at`, `updated_at` FROM stage_pringsewu.`pk_subkegiatan`;
INSERT INTO test_sakip.`program_pk` (`id`, `opd_id`, `kode_program`, `program_kegiatan`, `tahun_anggaran`, `jenis_anggaran`, `anggaran`, `created_at`, `updated_at`)
  SELECT `id`, `opd_id`, `kode_program`, `program_kegiatan`, `tahun_anggaran`, `jenis_anggaran`, `anggaran`, `created_at`, `updated_at` FROM stage_pringsewu.`program_pk`;
INSERT INTO test_sakip.`kegiatan_pk` (`id`, `program_id`, `kode_kegiatan`, `kegiatan`, `tahun_anggaran`, `jenis_anggaran`, `anggaran`, `created_at`, `updated_at`)
  SELECT `id`, `program_id`, `kode_kegiatan`, `kegiatan`, `tahun_anggaran`, `jenis_anggaran`, `anggaran`, `created_at`, `updated_at` FROM stage_pringsewu.`kegiatan_pk`;
INSERT INTO test_sakip.`sub_kegiatan_pk` (`id`, `kegiatan_id`, `kode_sub_kegiatan`, `sub_kegiatan`, `tahun_anggaran`, `jenis_anggaran`, `anggaran`, `created_at`, `updated_at`)
  SELECT `id`, `kegiatan_id`, `kode_sub_kegiatan`, `sub_kegiatan`, `tahun_anggaran`, `jenis_anggaran`, `anggaran`, `created_at`, `updated_at` FROM stage_pringsewu.`sub_kegiatan_pk`;
INSERT INTO test_sakip.`rkt` (`id`, `opd_id`, `tahun`, `indikator_id`, `program_id`, `status`, `created_at`, `updated_at`)
  SELECT `id`, `opd_id`, `tahun`, `indikator_id`, `program_id`, `status`, `created_at`, `updated_at` FROM stage_pringsewu.`rkt`;
INSERT INTO test_sakip.`rkt_kegiatan` (`id`, `rkt_id`, `kegiatan_id`, `created_at`, `updated_at`)
  SELECT `id`, `rkt_id`, `kegiatan_id`, `created_at`, `updated_at` FROM stage_pringsewu.`rkt_kegiatan`;
INSERT INTO test_sakip.`rkt_subkegiatan` (`id`, `rkt_kegiatan_id`, `sub_kegiatan_id`, `indikator_sasaran_sub_kegiatan`, `target`, `created_at`, `updated_at`)
  SELECT `id`, `rkt_kegiatan_id`, `sub_kegiatan_id`, `indikator_sasaran_sub_kegiatan`, `target`, `created_at`, `updated_at` FROM stage_pringsewu.`rkt_subkegiatan`;
INSERT INTO test_sakip.`cascading_sasaran_opd` (`id`, `opd_id`, `renstra_indikator_sasaran_id`, `parent_id`, `es3_indikator_id`, `level`, `nama_sasaran`, `csf`, `created_at`, `updated_at`)
  SELECT `id`, `opd_id`, `renstra_indikator_sasaran_id`, `parent_id`, `es3_indikator_id`, `level`, `nama_sasaran`, `csf`, `created_at`, `updated_at` FROM stage_pringsewu.`cascading_sasaran_opd`;
INSERT INTO test_sakip.`cascading_indikator_opd` (`id`, `cascading_sasaran_id`, `indikator`, `satuan`, `created_at`, `updated_at`)
  SELECT `id`, `cascading_sasaran_id`, `indikator`, `satuan`, `created_at`, `updated_at` FROM stage_pringsewu.`cascading_indikator_opd`;
INSERT INTO test_sakip.`rpjmd_cascading` (`id`, `indikator_sasaran_id`, `opd_id`, `pk_program_id`, `tahun`, `created_at`, `updated_at`)
  SELECT `id`, `indikator_sasaran_id`, `opd_id`, `pk_program_id`, `tahun`, `created_at`, `updated_at` FROM stage_pringsewu.`rpjmd_cascading`;
INSERT INTO test_sakip.`target_rencana` (`id`, `opd_id`, `renstra_target_id`, `rpjmd_target_id`, `pk_indikator_id`, `rencana_aksi`, `target_triwulan_1`, `target_triwulan_2`, `target_triwulan_3`, `target_triwulan_4`, `penanggung_jawab`, `created_at`, `updated_at`)
  SELECT `id`, `opd_id`, `renstra_target_id`, `rpjmd_target_id`, `pk_indikator_id`, `rencana_aksi`, `target_triwulan_1`, `target_triwulan_2`, `target_triwulan_3`, `target_triwulan_4`, `penanggung_jawab`, `created_at`, `updated_at` FROM stage_pringsewu.`target_rencana`;
INSERT INTO test_sakip.`monev` (`id`, `opd_id`, `target_rencana_id`, `capaian_triwulan_1`, `capaian_triwulan_2`, `capaian_triwulan_3`, `capaian_triwulan_4`, `total`, `created_at`, `updated_at`)
  SELECT `id`, `opd_id`, `target_rencana_id`, `capaian_triwulan_1`, `capaian_triwulan_2`, `capaian_triwulan_3`, `capaian_triwulan_4`,
         -- buang tanda %, koma desimal gaya Indonesia (34,49) -> titik;
         -- sisanya ('-', 'BB', 'Proses Penilaian', dst) jadi NULL
         CASE
           WHEN `total` IS NULL OR TRIM(`total`) = '' THEN NULL
           WHEN TRIM(REPLACE(REPLACE(`total`,'%',''),',','.')) REGEXP '^[0-9]+([.][0-9]+)?$'
             THEN CAST(TRIM(REPLACE(REPLACE(`total`,'%',''),',','.')) AS DECIMAL(10,2))
           ELSE NULL
         END,
         `created_at`, `updated_at` FROM stage_pringsewu.`monev`;
INSERT INTO test_sakip.`lakip` (`id`, `renstra_target_id`, `rpjmd_target_id`, `target_hitung`, `target_lalu`, `capaian_lalu`, `capaian_tahun_ini`, `capaian_hitung`, `status`, `created_at`, `updated_at`)
  SELECT `id`, `renstra_target_id`, `rpjmd_target_id`, `target_hitung`, `target_lalu`, `capaian_lalu`, `capaian_tahun_ini`, `capaian_hitung`, `status`, `created_at`, `updated_at` FROM stage_pringsewu.`lakip`;
INSERT INTO test_sakip.`iku` (`id`, `rpjmd_id`, `renstra_id`, `definisi`, `rumusan_perhitungan`, `sumber_data`, `penanggung_jawab`, `status`, `created_at`, `updated_at`)
  SELECT `id`, `rpjmd_id`, `renstra_id`, `definisi`, `rumusan_perhitungan`, `sumber_data`, `penanggung_jawab`, `status`, `created_at`, `updated_at` FROM stage_pringsewu.`iku`;
INSERT INTO test_sakip.`iku_program_pendukung` (`id`, `iku_id`, `program`, `created_at`, `updated_at`)
  SELECT `id`, `iku_id`, `program`, `created_at`, `updated_at` FROM stage_pringsewu.`iku_program_pendukung`;

-- --- 6. Pulihkan nilai kolom lokal -------------------------------------
UPDATE test_sakip.satuan s
  JOIN _tmp_satuan_tipe t ON t.id = s.id
  SET s.tipe = t.tipe;
DROP TEMPORARY TABLE IF EXISTS _tmp_satuan_tipe;

SET UNIQUE_CHECKS = 1;
SET FOREIGN_KEY_CHECKS = 1;
