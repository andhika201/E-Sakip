<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Jangkar RPJMD untuk sasaran IKU Kabupaten + mapping manual Cascading
 * Kabupaten berkunci indikator IKU.
 *
 * Padanan `spark migrate` dari db/update_2026-09-14_jangkar_rpjmd_iku_kabupaten.sql
 * — alasan lengkapnya ada di berkas SQL itu. Setiap langkah memeriksa
 * keadaan sebelumnya, jadi aman dijalankan pada basis data yang sudah
 * menjalankan berkas SQL-nya lebih dulu.
 */
class AddJangkarRpjmdIkuKabupaten extends Migration
{
    public function up()
    {
        foreach (['iku_sasaran', 'iku_revisi_sasaran'] as $tabel) {
            if (! $this->db->tableExists($tabel)) {
                continue;
            }

            if (! $this->db->fieldExists('rpjmd_tujuan_id', $tabel)) {
                $this->forge->addColumn($tabel, [
                    'rpjmd_tujuan_id' => [
                        'type'     => 'INT',
                        'unsigned' => true,
                        'null'     => true,
                        'comment'  => 'tujuan RPJMD bagi sasaran IKU KABUPATEN yang lahir di IKU; NULL = ikut silsilah / belum dijangkarkan',
                        'after'    => $this->db->fieldExists('renstra_tujuan_id', $tabel) ? 'renstra_tujuan_id' : 'source_type',
                    ],
                ]);
            }

            $namaIndeks = 'idx_' . $tabel . '_rpjmd_tujuan';

            if (! isset($this->db->getIndexData($tabel)[$namaIndeks])) {
                $this->db->query("CREATE INDEX `{$namaIndeks}` ON `{$tabel}` (`rpjmd_tujuan_id`)");
            }
        }

        if ($this->db->tableExists('iku_revisi_sasaran') && $this->db->tableExists('iku_sasaran')
            && $this->db->fieldExists('rpjmd_tujuan_id', 'iku_revisi_sasaran')
            && $this->db->fieldExists('rpjmd_tujuan_id', 'iku_sasaran')) {
            $this->db->query(
                'UPDATE `iku_revisi_sasaran` ars
                   JOIN `iku_sasaran` liv ON liv.id = ars.sumber_sasaran_id
                    SET ars.rpjmd_tujuan_id = liv.rpjmd_tujuan_id
                  WHERE ars.rpjmd_tujuan_id IS NULL
                    AND liv.rpjmd_tujuan_id IS NOT NULL'
            );
        }

        if ($this->db->tableExists('rpjmd_cascading')
            && ! $this->db->fieldExists('iku_indikator_id', 'rpjmd_cascading')) {
            $this->db->query(
                'ALTER TABLE `rpjmd_cascading`
                   MODIFY COLUMN `indikator_sasaran_id` INT UNSIGNED NULL,
                   ADD COLUMN `iku_indikator_id` INT UNSIGNED NULL
                       COMMENT \'indikator IKU Kabupaten yang dipetakan; kunci utama mapping sejak 2026-09-14\'
                       AFTER `indikator_sasaran_id`,
                   ADD KEY `idx_rpjmd_cascading_iku` (`iku_indikator_id`)'
            );
        }

        if ($this->db->tableExists('rpjmd_cascading') && $this->db->fieldExists('iku_indikator_id', 'rpjmd_cascading')
            && $this->db->fieldExists('source_indikator_id', 'iku_indikator')) {
            // Baris mapping lama berkunci RPJMD diberi kunci IKU lewat silsilah.
            $this->db->query(
                "UPDATE `rpjmd_cascading` map
                   JOIN `iku_indikator` iki ON iki.source_indikator_id = map.indikator_sasaran_id
                                           AND iki.source_type = 'rpjmd' AND iki.dihentikan_pada IS NULL
                   JOIN `iku_sasaran` iks ON iks.id = iki.iku_sasaran_id AND iks.opd_id IS NULL
                    SET map.iku_indikator_id = iki.id
                  WHERE map.iku_indikator_id IS NULL"
            );
        }

        if ($this->db->tableExists('rpjmd_cascading')) {
            $indeks = $this->db->getIndexData('rpjmd_cascading');

            // FK fk_cascade_indikator bersandar pada uniq_map (kolom pertamanya
            // indikator_sasaran_id); InnoDB menolak membuang index yang dipakai
            // FK. Index biasa untuk kolom itu dibuat DULU.
            if (! isset($indeks['idx_rpjmd_cascading_rpjmd'])) {
                $this->db->query('ALTER TABLE `rpjmd_cascading` ADD KEY `idx_rpjmd_cascading_rpjmd` (`indikator_sasaran_id`)');
            }

            if (isset($indeks['uniq_map'])) {
                $this->db->query('ALTER TABLE `rpjmd_cascading` DROP INDEX `uniq_map`');
            }

            if (! isset($indeks['uniq_map_iku'])) {
                $this->db->query(
                    'ALTER TABLE `rpjmd_cascading`'
                    . ' ADD UNIQUE KEY `uniq_map_iku` (`iku_indikator_id`, `opd_id`, `pk_program_id`, `tahun`)'
                );
            }
        }
    }

    public function down()
    {
        foreach (['iku_sasaran', 'iku_revisi_sasaran'] as $tabel) {
            if ($this->db->tableExists($tabel) && $this->db->fieldExists('rpjmd_tujuan_id', $tabel)) {
                $this->forge->dropColumn($tabel, 'rpjmd_tujuan_id');
            }
        }

        if ($this->db->tableExists('rpjmd_cascading') && $this->db->fieldExists('iku_indikator_id', 'rpjmd_cascading')) {
            $indeks = $this->db->getIndexData('rpjmd_cascading');
            $this->db->query(
                'ALTER TABLE `rpjmd_cascading`'
                . (isset($indeks['uniq_map_iku']) ? ' DROP INDEX `uniq_map_iku`,' : '')
                . ' DROP COLUMN `iku_indikator_id`'
            );
        }
    }
}
