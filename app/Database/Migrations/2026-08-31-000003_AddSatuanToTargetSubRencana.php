<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * KOLOM `target_sub_rencana.satuan`.
 *
 * Kembaran SQL langsungnya: db/update_2026-08-31_satuan_sub_rencana.sql —
 * berkas itu memuat alasan lengkap tiap keputusan.
 *
 * RINGKASNYA: target triwulan tiap sub rencana aksi selama ini berupa angka
 * telanjang tanpa keterangan satuan. Satuan tingkat indikator sudah ada, tapi
 * itu satuan indikatornya — satu indikator bersatuan "Persen" lazim dirinci
 * jadi sub-sub yang dihitung dalam "Dokumen" atau "Kegiatan".
 *
 * Disimpan sebagai NAMA, bukan foreign key ke `satuan`: baris ini catatan
 * rencana yang dibaca bertahun kemudian, dan tidak boleh ikut berubah bunyinya
 * bila master satuan kelak diubah — pola yang sama dengan
 * `iku_revisi_indikator.satuan_nama`. Konsistensinya dijaga di sisi isian,
 * lewat dropdown yang menyodorkan daftar dari master.
 *
 * Idempoten: memeriksa kolomnya dulu.
 */
class AddSatuanToTargetSubRencana extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('target_sub_rencana')) {
            return;
        }

        if (! $this->db->fieldExists('satuan', 'target_sub_rencana')) {
            $this->forge->addColumn('target_sub_rencana', [
                'satuan' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 50,
                    'null'       => true,
                    'comment'    => 'nama satuan target triwulan sub ini; NULL = mengikuti satuan indikator',
                    'after'      => 'sub_rencana_aksi',
                ],
            ]);
        }
    }

    public function down()
    {
        if ($this->db->tableExists('target_sub_rencana')
            && $this->db->fieldExists('satuan', 'target_sub_rencana')) {
            $this->forge->dropColumn('target_sub_rencana', 'satuan');
        }
    }
}
