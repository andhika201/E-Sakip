-- =====================================================================
-- Penguatan Integritas Referensial SAKIP (RPJMD, Renstra, Cascading)
-- Tanggal   : 2026-06-28
-- Sifat     : IDEMPOTEN — aman dijalankan ulang.
-- Engine    : InnoDB (sudah terverifikasi).
-- Konvensi  : ON UPDATE CASCADE; ON DELETE CASCADE untuk relasi struktural
--             (induk-anak), ON DELETE SET NULL untuk soft-pointer nullable.
--             Mengikuti konvensi FK yang sudah ada di database.
-- Jalankan  : mysql -u root test_sakip < db/update_2026-06-28_fk.sql
-- Catatan   : Membersihkan 2 baris orphan renstra (opd_id=1 yang tidak ada
--             di tabel opd) beserta turunannya. Backup baris tsb ada di
--             db/backup_orphan_renstra_2026-06-28.sql (bisa di-restore).
-- =====================================================================

-- ---------------------------------------------------------------------
-- 1) BERSIHKAN DATA ORPHAN (renstra_sasaran.opd_id menunjuk OPD tak ada)
--    Urutan: target -> indikator -> sasaran (anak dulu, baru induk).
--    Hanya menghapus baris yang opd-nya benar-benar tidak ada.
-- ---------------------------------------------------------------------
DELETE t FROM renstra_target t
  JOIN renstra_indikator_sasaran i ON i.id = t.renstra_indikator_id
  JOIN renstra_sasaran s ON s.id = i.renstra_sasaran_id
  LEFT JOIN opd o ON o.id = s.opd_id
  WHERE o.id IS NULL;

DELETE i FROM renstra_indikator_sasaran i
  JOIN renstra_sasaran s ON s.id = i.renstra_sasaran_id
  LEFT JOIN opd o ON o.id = s.opd_id
  WHERE o.id IS NULL;

DELETE s FROM renstra_sasaran s
  LEFT JOIN opd o ON o.id = s.opd_id
  WHERE o.id IS NULL;

-- ---------------------------------------------------------------------
-- 2) HELPER: tambah FK hanya bila belum ada (idempoten)
-- ---------------------------------------------------------------------
DROP PROCEDURE IF EXISTS _add_fk_if_absent;
DELIMITER $$
CREATE PROCEDURE _add_fk_if_absent(IN p_table VARCHAR(64), IN p_name VARCHAR(64), IN p_ddl TEXT)
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE()
      AND TABLE_NAME       = p_table
      AND CONSTRAINT_NAME  = p_name
      AND CONSTRAINT_TYPE  = 'FOREIGN KEY'
  ) THEN
    SET @sql = p_ddl;
    PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;
  END IF;
END$$
DELIMITER ;

-- ---------------------------------------------------------------------
-- 3) FOREIGN KEY — RANTAI RPJMD
-- ---------------------------------------------------------------------
CALL _add_fk_if_absent('rpjmd_misi','fk_rpjmd_misi_visi',
  'ALTER TABLE rpjmd_misi ADD CONSTRAINT fk_rpjmd_misi_visi FOREIGN KEY (rpjmd_visi_id) REFERENCES rpjmd_visi(id) ON UPDATE CASCADE ON DELETE CASCADE');

CALL _add_fk_if_absent('rpjmd_tujuan','fk_rpjmd_tujuan_misi',
  'ALTER TABLE rpjmd_tujuan ADD CONSTRAINT fk_rpjmd_tujuan_misi FOREIGN KEY (misi_id) REFERENCES rpjmd_misi(id) ON UPDATE CASCADE ON DELETE CASCADE');

CALL _add_fk_if_absent('rpjmd_indikator_tujuan','fk_rpjmd_indtujuan_tujuan',
  'ALTER TABLE rpjmd_indikator_tujuan ADD CONSTRAINT fk_rpjmd_indtujuan_tujuan FOREIGN KEY (tujuan_id) REFERENCES rpjmd_tujuan(id) ON UPDATE CASCADE ON DELETE CASCADE');

CALL _add_fk_if_absent('rpjmd_target_tujuan','fk_rpjmd_targettujuan_ind',
  'ALTER TABLE rpjmd_target_tujuan ADD CONSTRAINT fk_rpjmd_targettujuan_ind FOREIGN KEY (indikator_tujuan_id) REFERENCES rpjmd_indikator_tujuan(id) ON UPDATE CASCADE ON DELETE CASCADE');

CALL _add_fk_if_absent('rpjmd_sasaran','fk_rpjmd_sasaran_tujuan',
  'ALTER TABLE rpjmd_sasaran ADD CONSTRAINT fk_rpjmd_sasaran_tujuan FOREIGN KEY (tujuan_id) REFERENCES rpjmd_tujuan(id) ON UPDATE CASCADE ON DELETE CASCADE');

CALL _add_fk_if_absent('rpjmd_indikator_sasaran','fk_rpjmd_indsasaran_sasaran',
  'ALTER TABLE rpjmd_indikator_sasaran ADD CONSTRAINT fk_rpjmd_indsasaran_sasaran FOREIGN KEY (sasaran_id) REFERENCES rpjmd_sasaran(id) ON UPDATE CASCADE ON DELETE CASCADE');

CALL _add_fk_if_absent('rpjmd_target','fk_rpjmd_target_indsasaran',
  'ALTER TABLE rpjmd_target ADD CONSTRAINT fk_rpjmd_target_indsasaran FOREIGN KEY (indikator_sasaran_id) REFERENCES rpjmd_indikator_sasaran(id) ON UPDATE CASCADE ON DELETE CASCADE');

-- ---------------------------------------------------------------------
-- 4) FOREIGN KEY — RANTAI RENSTRA (+ jembatan keselarasan ke RPJMD)
-- ---------------------------------------------------------------------
CALL _add_fk_if_absent('renstra_tujuan','fk_renstra_tujuan_rpjmd_sasaran',
  'ALTER TABLE renstra_tujuan ADD CONSTRAINT fk_renstra_tujuan_rpjmd_sasaran FOREIGN KEY (rpjmd_sasaran_id) REFERENCES rpjmd_sasaran(id) ON UPDATE CASCADE ON DELETE CASCADE');

CALL _add_fk_if_absent('renstra_sasaran','fk_renstra_sasaran_opd',
  'ALTER TABLE renstra_sasaran ADD CONSTRAINT fk_renstra_sasaran_opd FOREIGN KEY (opd_id) REFERENCES opd(id) ON UPDATE CASCADE ON DELETE CASCADE');

CALL _add_fk_if_absent('renstra_indikator_sasaran','fk_renstra_indsasaran_sasaran',
  'ALTER TABLE renstra_indikator_sasaran ADD CONSTRAINT fk_renstra_indsasaran_sasaran FOREIGN KEY (renstra_sasaran_id) REFERENCES renstra_sasaran(id) ON UPDATE CASCADE ON DELETE CASCADE');

CALL _add_fk_if_absent('renstra_target','fk_renstra_target_indikator',
  'ALTER TABLE renstra_target ADD CONSTRAINT fk_renstra_target_indikator FOREIGN KEY (renstra_indikator_id) REFERENCES renstra_indikator_sasaran(id) ON UPDATE CASCADE ON DELETE CASCADE');

-- ---------------------------------------------------------------------
-- 5) FOREIGN KEY — CASCADING (Pohon Kinerja OPD)
--    es3_indikator_id = soft-pointer nullable -> SET NULL (hindari siklus cascade)
-- ---------------------------------------------------------------------
CALL _add_fk_if_absent('cascading_sasaran_opd','fk_cascading_sasaran_opd',
  'ALTER TABLE cascading_sasaran_opd ADD CONSTRAINT fk_cascading_sasaran_opd FOREIGN KEY (opd_id) REFERENCES opd(id) ON UPDATE CASCADE ON DELETE CASCADE');

CALL _add_fk_if_absent('cascading_sasaran_opd','fk_cascading_es3_indikator',
  'ALTER TABLE cascading_sasaran_opd ADD CONSTRAINT fk_cascading_es3_indikator FOREIGN KEY (es3_indikator_id) REFERENCES cascading_indikator_opd(id) ON UPDATE CASCADE ON DELETE SET NULL');

-- ---------------------------------------------------------------------
-- 6) Bersihkan helper
-- ---------------------------------------------------------------------
DROP PROCEDURE IF EXISTS _add_fk_if_absent;

-- Selesai. IKU (iku.rpjmd_id / iku.renstra_id) sengaja DILEWATI sampai
-- makna kolomnya diverifikasi (lihat db/RELASI_SAKIP.md bagian 6).
