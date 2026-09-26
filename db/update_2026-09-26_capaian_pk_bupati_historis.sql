-- 2026-09-26 — Capaian PK Bupati tahunan yang DIINPUT MANUAL (tahun sebelum 2025).
--
-- Grafik "Tren Capaian PK Bupati" di dashboard kabupaten & bupati kini per
-- TAHUN (5 tahun terakhir), bukan per triwulan. Tahun >= 2025 dihitung mesin
-- (LAKIP Kabupaten); tahun sebelumnya indikatornya berbeda (RPJMD/RPD lama),
-- jadi persentasenya diketik langsung oleh admin kabupaten lewat
-- adminkab/dashboard/capaian-historis. Satu baris per tahun.
--
-- Aman dijalankan berulang.

CREATE TABLE IF NOT EXISTS capaian_pk_bupati_historis (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    tahun       SMALLINT UNSIGNED NOT NULL,
    capaian     DECIMAL(8,2) NOT NULL COMMENT 'persentase capaian PK Bupati tahun itu',
    keterangan  VARCHAR(255) NULL COMMENT 'mis. sumber: LKjIP 2023',
    updated_by  INT UNSIGNED NULL,
    created_at  DATETIME NULL,
    updated_at  DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_capaian_pk_bupati_historis_tahun (tahun)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
