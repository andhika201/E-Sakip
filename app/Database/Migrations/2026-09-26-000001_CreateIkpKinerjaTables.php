<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * AKSARA+ — tabel Indikator Kinerja Prioritas (IKP) dan SAKIP sampai pelaksana:
 * ikp_program_unggulan, ikp_sasaran_pembangunan, ikp_buku_saku, ikp,
 * ikp_target_tahunan, ikp_bulanan, ikp_inovasi, cascading_pemilik,
 * cascading_indikator_target.
 *
 * Kembaran SQL langsungnya: db/update_2026-09-26_ikp_kinerja.sql.
 *
 * MENGAPA migrasi ini mengeksekusi pernyataan CREATE dari berkas kembarnya,
 * bukan menulis ulang lewat Forge: server produksi menjalankan berkas SQL itu
 * lewat phpMyAdmin (tanpa CLI), sedangkan lingkungan pengembang memakai
 * `spark migrate`. Satu sumber DDL mencegah dua jalur itu menghasilkan skema
 * yang berbeda (nama FK, tipe kolom, collation). Hanya pernyataan
 * `CREATE TABLE IF NOT EXISTS` yang diambil — izin dipasang migrasi 000002
 * lewat IkpPermissionSeeder.
 *
 * Sifat: IDEMPOTEN (IF NOT EXISTS) & ADDITIF (tidak mengubah tabel lama).
 */
class CreateIkpKinerjaTables extends Migration
{
    private const BERKAS = 'db/update_2026-09-26_ikp_kinerja.sql';

    /** Urutan hapus: anak dulu (FK). */
    private const TABEL = [
        'cascading_indikator_target', 'cascading_pemilik',
        'ikp_inovasi', 'ikp_bulanan', 'ikp_target_tahunan', 'ikp',
        'ikp_buku_saku', 'ikp_sasaran_pembangunan', 'ikp_program_unggulan',
    ];

    public function up()
    {
        $sql = file_get_contents(ROOTPATH . self::BERKAS);
        if ($sql === false) {
            throw new \RuntimeException('Berkas kembar SQL tidak ditemukan: ' . self::BERKAS);
        }
        // Buang komentar baris, lalu pecah per pernyataan.
        $bersih = preg_replace('/^\s*--.*$/m', '', $sql);
        foreach (preg_split('/;\s*\n/', $bersih) as $pernyataan) {
            $pernyataan = trim($pernyataan);
            if (stripos($pernyataan, 'CREATE TABLE IF NOT EXISTS') === 0) {
                $this->db->query($pernyataan);
            }
        }
    }

    public function down()
    {
        foreach (self::TABEL as $t) {
            $this->forge->dropTable($t, true, true);
        }
    }
}
