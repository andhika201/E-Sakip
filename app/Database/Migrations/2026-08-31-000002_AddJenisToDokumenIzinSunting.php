<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * KOLOM `dokumen_izin_sunting.jenis`.
 *
 * Kembaran SQL langsungnya: db/update_2026-08-31_izin_hapus_versi_iku.sql —
 * berkas itu memuat alasan lengkap tiap keputusan.
 *
 * RINGKASNYA: permohonan MENGHAPUS versi IKU menumpang antrean izin sunting
 * yang sudah ada, karena tabel itu sudah menyimpan lingkup, sasaran versinya,
 * alasan, dan jejak siapa meminta/memutuskan. Yang membedakan cukup satu
 * kolom. Membuat tabel kedua berarti dua antrean dan dua tempat aturan
 * kewenangan bisa menyimpang.
 *
 * Baris lama seluruhnya 'sunting' — DEFAULT-nya menjamin itu, sehingga
 * pemasangan kolom ini tidak mengubah perilaku apa pun yang sudah berjalan.
 *
 * Idempoten: memeriksa kolom & indeks dulu.
 */
class AddJenisToDokumenIzinSunting extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('dokumen_izin_sunting')) {
            return;
        }

        if (! $this->db->fieldExists('jenis', 'dokumen_izin_sunting')) {
            $this->forge->addColumn('dokumen_izin_sunting', [
                'jenis' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 12,
                    'null'       => false,
                    'default'    => 'sunting',
                    'comment'    => 'sunting = buka kunci; hapus = permohonan menghapus versi',
                    'after'      => 'version_id',
                ],
            ]);
        }

        $indeks = $this->db->getIndexData('dokumen_izin_sunting');

        if (! isset($indeks['idx_izin_jenis'])) {
            $this->db->query('CREATE INDEX `idx_izin_jenis` ON `dokumen_izin_sunting` (`jenis`)');
        }

        $this->db->query(
            "UPDATE `dokumen_izin_sunting` SET `jenis` = 'sunting' WHERE `jenis` IS NULL OR `jenis` = ''"
        );
    }

    public function down()
    {
        if ($this->db->tableExists('dokumen_izin_sunting')
            && $this->db->fieldExists('jenis', 'dokumen_izin_sunting')) {
            $this->forge->dropColumn('dokumen_izin_sunting', 'jenis');
        }
    }
}
