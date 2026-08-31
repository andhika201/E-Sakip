-- =====================================================================
-- EKSEKUSI NASIB 123 BARIS LAKIP YATIM
-- Tanggal : 2026-08-29
-- Sifat   : Semua baris DIARSIPKAN dulu (tabel lakip_yatim_arsip, salinan
--           utuh), baru dipulangkan/dihapus. TIDAK ADA nilai yang lenyap.
--
-- JANGAN DIJALANKAN sebelum daftar pemulangannya disetujui.
--
-- =====================================================================
-- DASAR KEPUTUSANNYA (ringkas)
--
-- Tiga sinyal diuji silang: (1) sidik jari `target_lalu` terhadap
-- renstra_target, (2) rekonstruksi sesi dari tetangga waktu, (3) status slot
-- hari ini. Sinyal (2) runtuh saat banyak OPD mengisi bersamaan; sinyal (1)
-- rontok pada nilai degenerate ('-', 'N/A'). Yang dipakai memutus adalah
-- irisan (1) khas + (3):
--
--   * slot KOSONG  -> SEMPAT diusulkan dipulangkan lewat sidik jari —
--                     lalu DIBATALKAN: arkeologi cadangan lama menemukan
--                     entri 26 Agustus (baris 266-271, berlingkup penuh,
--                     jangkar putus) untuk slot-slot yang sama. Entri
--                     terbaru menang; pemulangannya lewat
--                     perbaiki_2026-08-29_lakip_jangkar_ulang.sql yang
--                     deterministik, bukan lewat sidik jari;
--   * slot BERISI nilai sama     -> kembar, arsip;
--   * slot BERISI nilai berbeda  -> kata terakhir operator menang, arsip;
--   * sisanya                    -> arsip (nilai terjaga, bisa ditelusuri).
--
-- Seluruh 123 karena itu DIARSIPKAN; tidak ada pemulangan dari kelompok ini.
-- =====================================================================

-- ---- 0. arsip: salinan utuh + alasan ---------------------------------
CREATE TABLE IF NOT EXISTS lakip_yatim_arsip (
    arsip_id   INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    nasib      VARCHAR(20) NOT NULL COMMENT 'dipulangkan | kembar | bentrok | kosong | tak-tercocokkan',
    catatan    VARCHAR(255) NULL,
    diarsipkan_pada DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    lakip_id   INT UNSIGNED NOT NULL,
    isi        JSON NOT NULL COMMENT 'seluruh kolom baris lakip aslinya'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO lakip_yatim_arsip (nasib, catatan, lakip_id, isi)
SELECT
    CASE
        WHEN y.id IN (146,43,45,102,25) THEN 'digantikan'
        WHEN y.id IN (84,87,224,13,101,103,133,137,138,192,193,194,222) THEN 'kembar'
        WHEN y.id IN (12,17,55,65,66,225) THEN 'bentrok'
        WHEN y.capaian_tahun_ini IS NULL OR y.capaian_tahun_ini='' THEN 'kosong'
        ELSE 'tak-tercocokkan'
    END,
    CASE WHEN y.id IN (146,43,45,102,25)
         THEN 'digantikan entri 26 Agustus (lakip 266-271) untuk slot Dinkes yang sama'
         ELSE NULL END,
    y.id,
    JSON_OBJECT('id',y.id,'target_lalu',y.target_lalu,'capaian_lalu',y.capaian_lalu,
                'capaian_tahun_ini',y.capaian_tahun_ini,'target_hitung',y.target_hitung,
                'capaian_hitung',y.capaian_hitung,'status',y.status,
                'created_at',y.created_at,'updated_at',y.updated_at)
FROM lakip y
WHERE y.mode IS NULL AND y.renstra_target_id IS NULL AND y.rpjmd_target_id IS NULL;

-- ---- 1. HAPUS seluruh yatim dari tabel kerja (arsipnya sudah utuh) ---
DELETE FROM lakip
WHERE mode IS NULL AND renstra_target_id IS NULL AND rpjmd_target_id IS NULL;

-- ---- 2. PERIKSA ------------------------------------------------------
SELECT 'sisa yatim (harus 0)' k, COUNT(*) n FROM lakip WHERE mode IS NULL;
SELECT 'terarsip' k, nasib, COUNT(*) n FROM lakip_yatim_arsip GROUP BY nasib;
-- (pengisian slot Dinkes terjadi di perbaiki_2026-08-29_lakip_jangkar_ulang.sql)
