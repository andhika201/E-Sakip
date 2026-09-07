<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Cari sasaran IKU yang MELAYANG di Pohon Kinerja.
 *
 * =====================================================================
 * GEJALANYA DI LAYAR
 *
 * Pohon Kinerja menampilkan cabang terpisah berjudul "(Tanpa Tujuan RPJMD)",
 * "(Tanpa Sasaran RPJMD)", dan "(Tanpa Tujuan Renstra)" — berdiri sendiri di
 * samping tulang punggung yang benar.
 *
 * =====================================================================
 * SEBABNYA
 *
 * CascadingModel menjangkau Renstra & RPJMD dari IKU lewat DUA jalan, dan
 * cabang melayang muncul hanya bila KEDUANYA buntu:
 *
 *   1. silsilah indikator  `iku_indikator.source_indikator_id`
 *      -> renstra_indikator_sasaran -> renstra_sasaran -> renstra_tujuan
 *   2. silsilah sasaran    `iku_sasaran.source_sasaran_id` -> renstra_sasaran
 *   3. tujuan mandiri      `iku_sasaran.renstra_tujuan_id` (jalan pintas untuk
 *                          sasaran yang memang LAHIR di IKU)
 *
 * Jadi yang melayang adalah sasaran yang tidak bersilsilah ke Renstra DAN
 * tidak menyebut tujuan Renstra-nya sendiri. Biasanya sasaran mandiri yang
 * dibuat sebelum pemilihan tujuan diwajibkan.
 *
 * =====================================================================
 * MENGAPA HANYA MELAPOR, TIDAK MEMPERBAIKI
 *
 * "Tujuan Renstra mana yang menaungi sasaran ini" adalah keputusan
 * perencanaan, bukan sesuatu yang bisa disimpulkan dari data. Menebaknya —
 * misalnya karena OPD itu kebetulan cuma punya satu tujuan — berarti
 * memasang jawaban yang tampak pasti padahal tidak pernah diputuskan
 * siapa pun.
 */
class CascadingYatimCheck extends BaseCommand
{
    protected $group       = 'Pemeriksaan';
    protected $name        = 'casc:yatim';
    protected $description = 'Daftar sasaran IKU yang melayang di Pohon Kinerja, per OPD.';
    protected $usage       = 'casc:yatim [--opd <id>] [--db <nama>]';
    protected $options     = [
        '--opd' => 'batasi ke satu OPD',
        '--db'  => 'periksa basis data lain (mis. salinan server)',
    ];

    public function run(array $params)
    {
        $namaDb = trim((string) (CLI::getOption('db') ?: ''));

        if ($namaDb !== '' && $namaDb !== '1') {
            $cfg             = config('Database')->default;
            $cfg['database'] = $namaDb;
            $db              = db_connect($cfg, false);
        } else {
            $db = db_connect();
        }

        $opd = (int) (CLI::getOption('opd') ?: 0);

        CLI::write('Basis data: ' . $db->getDatabase());

        if (! $db->fieldExists('renstra_tujuan_id', 'iku_sasaran')) {
            CLI::error('Kolom iku_sasaran.renstra_tujuan_id belum ada. Jalankan '
                . 'db/update_2026-08-29_sasaran_iku_mandiri.sql lebih dulu.');

            return EXIT_ERROR;
        }

        // Cerminan jalur yang dipakai CascadingModel::getCascadingMatrixByOpd().
        // Sasaran dihitung MELAYANG bila tak satu pun dari tiga jalan itu
        // sampai ke sebuah renstra_tujuan.
        $sql = "
            SELECT s.opd_id, o.nama_opd, s.id, s.sasaran, s.source_type,
                   s.source_sasaran_id,
                   CASE
                     WHEN s.source_sasaran_id IS NULL THEN 'tak bersilsilah'
                     WHEN NOT EXISTS (SELECT 1 FROM renstra_sasaran x
                                       WHERE x.id = s.source_sasaran_id)
                          THEN 'rujukan menggantung'
                     ELSE 'sasaran Renstra tanpa tujuan'
                   END AS sebab,
                   (SELECT COUNT(*) FROM iku_indikator i
                     WHERE i.iku_sasaran_id = s.id AND i.dihentikan_pada IS NULL) AS jml_indikator
              FROM iku_sasaran s
              LEFT JOIN opd o ON o.id = s.opd_id
             WHERE s.dihentikan_pada IS NULL
               AND s.opd_id IS NOT NULL
               AND s.renstra_tujuan_id IS NULL
               AND NOT EXISTS (
                     SELECT 1 FROM renstra_sasaran rs
                      JOIN renstra_tujuan rt ON rt.id = rs.renstra_tujuan_id
                     WHERE rs.id = s.source_sasaran_id)
               AND NOT EXISTS (
                     SELECT 1 FROM iku_indikator i
                      JOIN renstra_indikator_sasaran ris ON ris.id = i.source_indikator_id
                      JOIN renstra_sasaran rs2 ON rs2.id = ris.renstra_sasaran_id
                      JOIN renstra_tujuan rt2 ON rt2.id = rs2.renstra_tujuan_id
                     WHERE i.iku_sasaran_id = s.id AND i.dihentikan_pada IS NULL)
        " . ($opd > 0 ? ' AND s.opd_id = ' . $opd : '') . "
             ORDER BY o.nama_opd, s.urutan, s.id";

        $rows = $db->query($sql)->getResultArray();

        if ($rows === []) {
            CLI::newLine();
            CLI::write('  Tidak ada sasaran IKU yang melayang. Pohon Kinerja utuh.', 'green');

            return EXIT_SUCCESS;
        }

        $perOpd = [];

        foreach ($rows as $r) {
            $perOpd[(string) ($r['nama_opd'] ?? ('OPD #' . $r['opd_id']))][] = $r;
        }

        CLI::newLine();
        CLI::write('SASARAN IKU YANG MELAYANG (muncul sebagai cabang "Tanpa Tujuan ...")', 'red');

        foreach ($perOpd as $nama => $daftar) {
            CLI::newLine();
            CLI::write('  ' . $nama . '  (' . count($daftar) . ' sasaran)', 'yellow');

            foreach ($daftar as $r) {
                CLI::write(sprintf('    #%-5d %-46s %-22s %d indikator',
                    $r['id'], mb_substr((string) $r['sasaran'], 0, 44),
                    $r['sebab'] . ($r['source_sasaran_id'] !== null
                        ? ' (#' . $r['source_sasaran_id'] . ')' : ''),
                    (int) $r['jml_indikator']));
            }

            // Tujuan yang tersedia untuk OPD itu — bahan keputusan operator.
            $tujuan = $db->query(
                "SELECT DISTINCT rt.id, rt.tujuan
                   FROM renstra_tujuan rt
                   JOIN renstra_sasaran rs ON rs.renstra_tujuan_id = rt.id
                  WHERE rs.opd_id = ?
                  ORDER BY rt.id",
                [(int) $daftar[0]['opd_id']]
            )->getResultArray();

            if ($tujuan === []) {
                CLI::write('      ' . CLI::color(
                    'OPD ini belum punya Tujuan Renstra sama sekali — lengkapi Renstra dulu.',
                    'red'
                ));

                continue;
            }

            CLI::write('      Tujuan Renstra yang bisa dipilih:');

            foreach ($tujuan as $t) {
                CLI::write(sprintf('        id=%-5d %s', $t['id'], mb_substr((string) $t['tujuan'], 0, 62)));
            }
        }

        CLI::newLine();
        CLI::write('Total: ' . count($rows) . ' sasaran melayang pada '
            . count($perOpd) . ' OPD.');
        CLI::newLine();
        CLI::write('Cara memperbaiki: tetapkan Tujuan Renstra bagi tiap sasaran di atas.');
        CLI::write('Perintah ini SENGAJA tidak menebaknya — "tujuan mana yang menaungi');
        CLI::write('sasaran ini" adalah keputusan perencanaan, bukan simpulan dari data.');

        return EXIT_SUCCESS;
    }
}
