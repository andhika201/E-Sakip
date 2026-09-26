-- =====================================================================
-- BERSIHKAN BARIS LAKIP KABUPATEN YANG TIDAK SESUAI IKATAN DOKUMEN
-- Tanggal : 2026-09-25
-- Sifat   : Baris DIARSIPKAN UTUH lebih dulu ke `lakip_yatim_arsip`
--           (salinan JSON seluruh kolom), baru dihapus dari `lakip`.
--           TIDAK ADA nilai yang lenyap. Idempoten: dijalankan dua kali
--           tidak mengarsipkan apa pun untuk kedua kalinya.
--
-- =====================================================================
-- MASALAHNYA
--
-- Pengesahan LAKIP Kabupaten 2025 ditolak dengan pesan:
--
--     "LAKIP Kabupaten memiliki source type atau source version campuran."
--
-- Sebabnya tabel `lakip` memuat sisa dari sumber-sumber lama. Berpindah
-- sumber dokumen TIDAK menghapus baris yang realisasinya sudah terisi,
-- sehingga tahun 2025 menumpuk tiga kelompok sekaligus:
--
--   15 baris  rpjmd, tanpa versi, opd_id = 0    <- paling lama
--   11 baris  iku v116, opd_id = 0              <- SESUAI ikatan aktif
--   10 baris  iku v118, opd_id = NULL           <- yatim, tak terlihat
--
-- `lakip_dokumen` tahun 2025 mengikat dokumen ke IKU v116 ("IKU yang
-- disetujui Kemenpan"), jadi hanya 11 baris itu yang sah.
--
-- Sepuluh indikator bahkan kembar TIGA dengan realisasi identik (Angka
-- Kemiskinan 7,60 di ketiganya, dan seterusnya): nilainya memang dibawa
-- terus setiap kali sumber berpindah, jadi kelompok lama tidak memuat
-- angka unik yang akan hilang.
--
-- =====================================================================
-- SISI KODE SUDAH DIPERBAIKI TERPISAH
--
-- LakipPengesahanModel::validasiKabupaten() kini menyaring baris sesuai
-- ikatan `lakip_dokumen`, sama seperti yang dilakukan layar lewat
-- LakipModel::getLakipMapIku(). Dengan itu saja pengesahan sudah lolos —
-- skrip ini membereskan sisanya supaya tidak menggantung dan tidak
-- membingungkan pembaca tabel di kemudian hari.
--
-- =====================================================================
-- MENGAPA AMAN DIHAPUS
--
--  1. Tidak ada FOREIGN KEY mana pun yang menunjuk `lakip`.
--  2. Satu-satunya perujuk adalah `lakip_snapshot_baris.lakip_id` — 15
--     baris RPJMD dirujuk snapshot#1 (tahun 2025, status DRAFT, sumber
--     rpjmd, dibuat 26 Agu 2026, belum pernah disinkronkan/difinalkan).
--     Snapshot itu MENYALIN TEKS (indikator, sasaran, satuan, target,
--     realisasi) ke barisnya sendiri, dan rehidrasi() TIDAK pernah
--     menyambung `lakip_id` kembali ke tabel `lakip` — ia hanya membawa
--     nilainya. Jadi isinya tetap utuh; yang tersisa hanya id yang tidak
--     pernah ditelusuri lagi.
--  3. Layar LAKIP Kabupaten hanya membaca snapshot berstatus FINAL, dan
--     snapshot#1 masih draft — ia tidak dipakai menampilkan apa pun.
--
-- Snapshot#1 itu sendiri SENGAJA TIDAK disentuh skrip ini: menghapus
-- snapshot adalah keputusan tersendiri milik pemakainya, bukan efek
-- samping pembersihan baris.
-- =====================================================================

SELECT '========== SEBELUM ==========' AS `laporan`;

SELECT l.tahun, l.opd_id, l.source_type, l.source_version_id, COUNT(*) AS baris
  FROM lakip l
 WHERE l.mode = 'kabupaten' AND l.tahun = 2025
 GROUP BY l.tahun, l.opd_id, l.source_type, l.source_version_id
 ORDER BY l.source_type, l.source_version_id;

-- Baris yang akan diarsipkan: TIDAK cocok dengan ikatan dokumen aktif.
SELECT l.id, l.source_type, l.source_version_id, l.opd_id, l.status, l.capaian_tahun_ini
  FROM lakip l
  JOIN lakip_dokumen d
    ON d.tahun = l.tahun AND d.mode = 'kabupaten'
 WHERE l.mode = 'kabupaten'
   AND l.tahun = 2025
   AND (l.opd_id = 0 OR l.opd_id IS NULL)
   AND NOT (l.source_type = d.source_type
            AND (d.source_version_id IS NULL
                 OR l.source_version_id <=> d.source_version_id))
 ORDER BY l.id;

-- ---------------------------------------------------------------------
-- 1. ARSIPKAN UTUH
--
-- `nasib` = 'digantikan': baris ini sah pada masanya, lalu dokumen
-- acuannya berpindah. Istilah yang sama sudah dipakai pembersihan
-- 2026-08-29, jadi pembacaan arsipnya tetap seragam.
-- ---------------------------------------------------------------------
INSERT INTO `lakip_yatim_arsip` (`nasib`, `catatan`, `lakip_id`, `isi`, `diarsipkan_pada`)
SELECT 'digantikan',
       CONCAT('LAKIP Kabupaten ', l.tahun, ': sumber ', UPPER(l.source_type),
              IFNULL(CONCAT(' v', l.source_version_id), ' (tanpa versi)'),
              ' tidak sesuai ikatan dokumen aktif ', UPPER(d.source_type),
              IFNULL(CONCAT(' v', d.source_version_id), ''),
              '. Diarsipkan 2026-09-25 agar pengesahan tidak lagi melihat sumber campuran.'),
       l.id,
       JSON_OBJECT(
           'id',                l.id,
           'tahun',             l.tahun,
           'mode',              l.mode,
           'opd_id',            l.opd_id,
           'source_type',       l.source_type,
           'source_version_id', l.source_version_id,
           'source_entity_id',  l.source_entity_id,
           'renstra_target_id', l.renstra_target_id,
           'rpjmd_target_id',   l.rpjmd_target_id,
           'target_hitung',     l.target_hitung,
           'target_lalu',       l.target_lalu,
           'capaian_lalu',      l.capaian_lalu,
           'capaian_tahun_ini', l.capaian_tahun_ini,
           'capaian_hitung',    l.capaian_hitung,
           'status',            l.status,
           'lakip_version_id',  l.lakip_version_id,
           'lakip_dokumen_id',  l.lakip_dokumen_id,
           'created_at',        l.created_at,
           'updated_at',        l.updated_at
       ),
       NOW()
  FROM lakip l
  JOIN lakip_dokumen d
    ON d.tahun = l.tahun AND d.mode = 'kabupaten'
 WHERE l.mode = 'kabupaten'
   AND l.tahun = 2025
   AND (l.opd_id = 0 OR l.opd_id IS NULL)
   AND NOT (l.source_type = d.source_type
            AND (d.source_version_id IS NULL
                 OR l.source_version_id <=> d.source_version_id))
   -- Idempoten: jangan mengarsipkan baris yang sudah pernah diarsipkan.
   AND NOT EXISTS (SELECT 1 FROM lakip_yatim_arsip a WHERE a.lakip_id = l.id);

-- ---------------------------------------------------------------------
-- 2. HAPUS DARI `lakip`
--
-- Hanya baris yang salinannya SUDAH ada di arsip. Syarat itu membuat
-- penghapusan mustahil mendahului pengarsipan, walau skrip ini dijalankan
-- sebagian.
-- ---------------------------------------------------------------------
DELETE l
  FROM lakip l
  JOIN lakip_dokumen d
    ON d.tahun = l.tahun AND d.mode = 'kabupaten'
 WHERE l.mode = 'kabupaten'
   AND l.tahun = 2025
   AND (l.opd_id = 0 OR l.opd_id IS NULL)
   AND NOT (l.source_type = d.source_type
            AND (d.source_version_id IS NULL
                 OR l.source_version_id <=> d.source_version_id))
   AND EXISTS (SELECT 1 FROM lakip_yatim_arsip a WHERE a.lakip_id = l.id);

SELECT '========== SESUDAH ==========' AS `laporan`;

SELECT l.tahun, l.opd_id, l.source_type, l.source_version_id, COUNT(*) AS baris
  FROM lakip l
 WHERE l.mode = 'kabupaten' AND l.tahun = 2025
 GROUP BY l.tahun, l.opd_id, l.source_type, l.source_version_id;

SELECT COUNT(*) AS baris_terarsip_hari_ini
  FROM lakip_yatim_arsip
 WHERE nasib = 'digantikan' AND DATE(diarsipkan_pada) = CURDATE();

-- Harus TEPAT 1 kombinasi sumber agar pengesahan lolos.
SELECT COUNT(DISTINCT CONCAT(source_type, ':', IFNULL(source_version_id, 0))) AS kombinasi_sumber
  FROM lakip
 WHERE mode = 'kabupaten' AND tahun = 2025 AND opd_id = 0;

-- =====================================================================
-- ROLLBACK
--
-- Seluruh baris tersimpan utuh sebagai JSON di `lakip_yatim_arsip`.
-- Pemulangannya:
--
--   INSERT INTO lakip (id, tahun, mode, opd_id, source_type,
--                      source_version_id, source_entity_id,
--                      renstra_target_id, rpjmd_target_id,
--                      target_hitung, target_lalu, capaian_lalu,
--                      capaian_tahun_ini, capaian_hitung, status,
--                      lakip_version_id, lakip_dokumen_id,
--                      created_at, updated_at)
--   SELECT isi->>'$.id', isi->>'$.tahun', isi->>'$.mode', isi->>'$.opd_id',
--          isi->>'$.source_type', isi->>'$.source_version_id',
--          isi->>'$.source_entity_id', isi->>'$.renstra_target_id',
--          isi->>'$.rpjmd_target_id', isi->>'$.target_hitung',
--          isi->>'$.target_lalu', isi->>'$.capaian_lalu',
--          isi->>'$.capaian_tahun_ini', isi->>'$.capaian_hitung',
--          isi->>'$.status', isi->>'$.lakip_version_id',
--          isi->>'$.lakip_dokumen_id', isi->>'$.created_at', isi->>'$.updated_at'
--     FROM lakip_yatim_arsip
--    WHERE nasib = 'digantikan' AND DATE(diarsipkan_pada) = '2026-09-25';
--
-- Perhatikan: `isi->>'$.x'` memulangkan string "null" untuk nilai NULL;
-- bungkus dengan NULLIF(..., 'null') bila memulangkannya.
-- =====================================================================
