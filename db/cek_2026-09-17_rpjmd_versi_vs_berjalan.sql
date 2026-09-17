-- =====================================================================
-- CEK (HANYA BACA): kenapa versi RPJMD yang dibuat dari kondisi berjalan
-- memuat lebih sedikit indikator daripada menu RPJMD?
-- Tanggal : 2026-09-17
-- Sifat   : SELECT saja. Tidak mengubah apa pun. Aman dijalankan di server.
--
-- Menu RPJMD menampilkan SEMUA baris, termasuk yang sudah dihentikan
-- (`dihentikan_pada` terisi). Pembekuan versi hanya mengambil baris yang
-- masih hidup pada periode versi. Query di bawah menyebut baris mana yang
-- tidak ikut, dan mengapa.
-- =====================================================================

SELECT '========== 1. Versi RPJMD yang ada ==========' AS laporan;
SELECT id, version_no, label, status, effective_from, copied_from_version_id AS salinan_dari,
       mulai_dari_kosong, created_at
FROM dokumen_versi WHERE modul = 'rpjmd' ORDER BY id;

SELECT '========== 2. Jumlah indikator sasaran per versi (arsip) ==========' AS laporan;
SELECT v.id AS version_id, v.version_no, v.status,
       (SELECT COUNT(*) FROM rpjmd_versi_indikator_sasaran a WHERE a.version_id = v.id) AS indikator_di_versi
FROM dokumen_versi v WHERE v.modul = 'rpjmd' ORDER BY v.id;

SELECT '========== 3. Indikator sasaran BERJALAN: hidup vs dihentikan ==========' AS laporan;
SELECT CONCAT(m.tahun_mulai, '-', m.tahun_akhir) AS periode_misi,
       SUM(i.dihentikan_pada IS NULL AND s.dihentikan_pada IS NULL AND t.dihentikan_pada IS NULL AND m.dihentikan_pada IS NULL) AS hidup,
       SUM(NOT (i.dihentikan_pada IS NULL AND s.dihentikan_pada IS NULL AND t.dihentikan_pada IS NULL AND m.dihentikan_pada IS NULL)) AS dihentikan,
       COUNT(*) AS tampil_di_menu
FROM rpjmd_indikator_sasaran i
LEFT JOIN rpjmd_sasaran s ON s.id = i.sasaran_id
LEFT JOIN rpjmd_tujuan  t ON t.id = s.tujuan_id
LEFT JOIN rpjmd_misi    m ON m.id = t.misi_id
GROUP BY periode_misi;

SELECT '========== 4. Baris yang TIDAK ikut dibekukan, dan mengapa ==========' AS laporan;
SELECT i.id, i.indikator_sasaran,
       CASE
         WHEN m.id IS NULL THEN 'tidak tersambung ke misi'
         WHEN i.dihentikan_pada IS NOT NULL THEN CONCAT('indikator dihentikan ', DATE(i.dihentikan_pada),
                                                        IFNULL(CONCAT(' s.d. ', i.berlaku_sampai), ''),
                                                        IFNULL(CONCAT(' — ', i.alasan_dihentikan), ''))
         WHEN s.dihentikan_pada IS NOT NULL THEN CONCAT('sasaran dihentikan ', DATE(s.dihentikan_pada))
         WHEN t.dihentikan_pada IS NOT NULL THEN CONCAT('tujuan dihentikan ', DATE(t.dihentikan_pada))
         WHEN m.dihentikan_pada IS NOT NULL THEN CONCAT('misi dihentikan ', DATE(m.dihentikan_pada))
         ELSE CONCAT('misi berperiode ', m.tahun_mulai, '-', m.tahun_akhir)
       END AS alasan,
       s.sasaran_rpjmd, m.misi
FROM rpjmd_indikator_sasaran i
LEFT JOIN rpjmd_sasaran s ON s.id = i.sasaran_id
LEFT JOIN rpjmd_tujuan  t ON t.id = s.tujuan_id
LEFT JOIN rpjmd_misi    m ON m.id = t.misi_id
WHERE m.id IS NULL
   OR i.dihentikan_pada IS NOT NULL OR s.dihentikan_pada IS NOT NULL
   OR t.dihentikan_pada IS NOT NULL OR m.dihentikan_pada IS NOT NULL
ORDER BY m.id, t.id, s.id, i.id;

SELECT '========== 5. Siapa yang menghentikannya (riwayat versi) ==========' AS laporan;
SELECT h.version_id, v.version_no, v.label, h.aksi, h.ringkasan, h.oleh_nama, h.pada
FROM version_submission_history h
JOIN dokumen_versi v ON v.id = h.version_id
WHERE v.modul = 'rpjmd' AND h.aksi IN ('published', 'applied', 'retired')
ORDER BY h.id;
