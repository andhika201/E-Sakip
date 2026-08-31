<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * KOLOM `iku_revisi_sasaran.renstra_tujuan_id`.
 *
 * Kembaran SQL langsungnya (dipakai di server yang skemanya sudah menyimpang
 * dari migration): db/update_2026-08-31_sasaran_mandiri_dalam_revisi.sql —
 * berkas itu memuat alasan lengkap tiap keputusan.
 *
 * RINGKASNYA: sasaran mandiri kini lahir DI DALAM draft revisi, bukan lewat
 * pintu tersendiri yang menulis langsung ke tabel live di luar alur revisi.
 * `iku_sasaran` (live) sudah punya kolom ini sejak 2026-08-29; arsipnya belum.
 * Tanpa padanan di arsip, tujuan Renstra sasaran mandiri hilang begitu revisi
 * disahkan dan arsipnya diterapkan ke live — dan hilangnya tidak bergejala
 * sampai ada yang membuka Cascading.
 *
 * SENGAJA tanpa foreign key, mengikuti kolom live-nya: arsip revisi adalah
 * catatan sejarah. Bila tujuan Renstra-nya kelak dihapus, arsip tahun lampau
 * tidak boleh ikut berubah bunyinya — dan ON DELETE SET NULL justru akan
 * mengubahnya.
 *
 * Idempoten: memeriksa kolom & indeks dulu.
 */
class AddTujuanToIkuRevisiSasaran extends Migration
{
    public function up()
    {
        // Basis data yang belum memasang modul revisi IKU dilewati; migration
        // ini tidak boleh menjadi alasan `spark migrate` gagal.
        if (! $this->db->tableExists('iku_revisi_sasaran')) {
            return;
        }

        if (! $this->db->fieldExists('renstra_tujuan_id', 'iku_revisi_sasaran')) {
            $this->forge->addColumn('iku_revisi_sasaran', [
                'renstra_tujuan_id' => [
                    'type'       => 'INT',
                    'unsigned'   => true,
                    'null'       => true,
                    'comment'    => 'tujuan Renstra bagi sasaran yang LAHIR di IKU; NULL = ikut source_ref_id',
                    'after'      => 'source_ref_id',
                ],
            ]);
        }

        $indeks = $this->db->getIndexData('iku_revisi_sasaran');

        if (! isset($indeks['idx_iku_revisi_sasaran_tujuan'])) {
            $this->db->query(
                'CREATE INDEX `idx_iku_revisi_sasaran_tujuan` ON `iku_revisi_sasaran` (`renstra_tujuan_id`)'
            );
        }

        // Arsip yang dibekukan sebelum kolom ini ada tidak menyimpan tujuannya.
        // Yang masih bisa dipulihkan diambil dari baris live yang dirujuknya;
        // yang live-nya pun kosong dibiarkan NULL — tidak ada yang bisa ditebak.
        if ($this->db->fieldExists('renstra_tujuan_id', 'iku_sasaran')) {
            $this->db->query(
                'UPDATE `iku_revisi_sasaran` ars
                   JOIN `iku_sasaran` liv ON liv.id = ars.sumber_sasaran_id
                    SET ars.renstra_tujuan_id = liv.renstra_tujuan_id
                  WHERE ars.renstra_tujuan_id IS NULL
                    AND liv.renstra_tujuan_id IS NOT NULL'
            );
        }
    }

    public function down()
    {
        if ($this->db->tableExists('iku_revisi_sasaran')
            && $this->db->fieldExists('renstra_tujuan_id', 'iku_revisi_sasaran')) {
            $this->forge->dropColumn('iku_revisi_sasaran', 'renstra_tujuan_id');
        }
    }
}
