<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Database;
use Throwable;

/**
 * Temukan (dan rapikan) capaian MONEV warisan di tingkat rencana aksi.
 *
 *   php spark monev:warisan            laporan saja
 *   php spark monev:warisan --opd 11   batasi satu OPD
 *   php spark monev:warisan --fix      kosongkan capaian warisannya
 *
 * =====================================================================
 * PERANNYA SUDAH BERUBAH — BACA INI DULU
 *
 * Satu indikator diukur lewat BARIS UKUR: tiap sub rencana aksi punya target,
 * capaian, dan metode perhitungannya sendiri. Bila sub belum ada, rencana
 * aksinya sendiri yang menjadi baris ukur.
 *
 * Banyak OPD mengisi capaian di tingkat rencana aksi LEBIH DULU, baru kemudian
 * memecahnya menjadi sub. Capaian lama itu tetap tinggal.
 *
 * DULU baris tinggalan itu MERUSAK dashboard: OpdDashboardService ikut
 * menghitung baris tingkat rencana aksi selama masih berkapaian, sehingga satu
 * baris tanpa metode menjatuhkan seluruh indikator menjadi "belum dapat
 * dihitung" walau setiap sub di bawahnya sudah lengkap. Perintah ini lahir
 * sebagai penawarnya, dan menjalankannya terasa WAJIB.
 *
 * SEJAK 2 September 2026 tidak lagi. Dashboard kini memilih baris ukurnya
 * dengan tegas — sub bila ada, parent bila tidak — sehingga baris tinggalan
 * TIDAK PERNAH ikut mengukur apa pun. Pada basis data ini perubahan itu
 * menurunkan laporan "metode belum dipilih" dari 291 menjadi 65; ke-226
 * sisanya memang palsu.
 *
 * Maka perintah ini sekarang ALAT KEBERSIHAN DATA, bukan syarat supaya
 * dashboard benar. Menjalankannya merapikan residu; TIDAK menjalankannya
 * tidak lagi membuat angka mana pun salah. Karena itu jangan pernah
 * menjadikannya prasyarat rilis, dan jangan menjalankan --fix hanya untuk
 * "menghilangkan peringatan" — peringatannya sudah tidak ada.
 *
 * =====================================================================
 * YANG DIANGGAP WARISAN
 *
 * Baris monev tingkat rencana aksi (target_sub_rencana_id 0/NULL) yang:
 *   1. punya capaian, DAN
 *   2. rencana aksinya SUDAH dipecah menjadi sub, DAN
 *   3. seluruh sub itu sudah punya baris monev sendiri.
 *
 * Syarat ke-3 penting: tanpa itu, mengosongkan baris induk bisa membuang
 * satu-satunya capaian yang pernah diisi.
 *
 * --fix hanya MENGOSONGKAN keempat kolom capaian; barisnya sendiri, metode,
 * dan totalnya tidak dihapus, sehingga bisa diisi ulang bila ternyata keliru.
 */
class MonevWarisanCheck extends BaseCommand
{
    protected $group       = 'SAKIP';
    protected $name        = 'monev:warisan';
    protected $description = 'Temukan capaian MONEV warisan di tingkat rencana aksi yang menjatuhkan indikator.';
    protected $usage       = 'monev:warisan [--opd <id>] [--fix]';
    protected $options     = [
        '--opd' => 'Batasi pada satu OPD.',
        '--fix' => 'Kosongkan capaian warisannya. Tanpa ini hanya melaporkan.',
    ];

    public function run(array $params)
    {
        $db  = Database::connect();
        $fix = array_key_exists('fix', $params) || CLI::getOption('fix') !== null;
        $opd = CLI::getOption('opd');

        CLI::write('Basis data: ' . $db->getDatabase(), 'yellow');
        CLI::newLine();

        $b = $db->table('monev m')
            ->select("m.id, m.opd_id, m.target_rencana_id, m.metode_perhitungan,
                      m.capaian_triwulan_1, m.capaian_triwulan_2,
                      m.capaian_triwulan_3, m.capaian_triwulan_4,
                      o.nama_opd, tr.rencana_aksi,
                      (SELECT COUNT(*) FROM target_sub_rencana s
                        WHERE s.target_rencana_id = m.target_rencana_id) AS jml_sub,
                      (SELECT COUNT(*) FROM target_sub_rencana s
                         JOIN monev ms ON ms.target_sub_rencana_id = s.id
                        WHERE s.target_rencana_id = m.target_rencana_id) AS jml_sub_bermonev", false)
            ->join('target_rencana tr', 'tr.id = m.target_rencana_id', 'left')
            ->join('opd o', 'o.id = m.opd_id', 'left')
            ->groupStart()
                ->where('m.target_sub_rencana_id', 0)
                ->orWhere('m.target_sub_rencana_id IS NULL', null, false)
            ->groupEnd();

        if ($opd !== null && $opd !== true) {
            $b->where('m.opd_id', (int) $opd);
        }

        $warisan = [];

        foreach ($b->get()->getResultArray() as $r) {
            $adaCapaian = false;

            foreach ([1, 2, 3, 4] as $q) {
                $v = $r['capaian_triwulan_' . $q];
                if ($v !== null && trim((string) $v) !== '') {
                    $adaCapaian = true;
                    break;
                }
            }

            // Ketiga syarat harus terpenuhi bersamaan.
            if (! $adaCapaian
                || (int) $r['jml_sub'] === 0
                || (int) $r['jml_sub_bermonev'] < (int) $r['jml_sub']) {
                continue;
            }

            $warisan[] = $r;
        }

        if ($warisan === []) {
            CLI::write('Tidak ada capaian warisan yang perlu dirapikan.', 'green');

            return 0;
        }

        // Ringkas per OPD.
        $perOpd = [];
        foreach ($warisan as $w) {
            $nama = $w['nama_opd'] ?? ('OPD #' . $w['opd_id']);
            $perOpd[$nama] = ($perOpd[$nama] ?? 0) + 1;
        }

        arsort($perOpd);

        CLI::write(count($warisan) . ' baris capaian warisan ditemukan:', 'red');

        foreach ($perOpd as $nama => $n) {
            CLI::write(sprintf('  %-58s %3d baris', mb_substr($nama, 0, 58), $n));
        }

        // Yang paling merugikan: warisan TANPA metode — inilah yang membuat
        // indikatornya dilaporkan "belum dapat dihitung".
        $tanpaMetode = array_values(array_filter(
            $warisan,
            static fn ($w) => $w['metode_perhitungan'] === null || $w['metode_perhitungan'] === ''
        ));

        CLI::newLine();
        CLI::write(count($tanpaMetode) . ' di antaranya TANPA metode perhitungan '
            . '— inilah yang menjatuhkan indikatornya.', 'yellow');

        if (! $fix) {
            CLI::newLine();
            CLI::write('Contoh 5 teratas:', 'cyan');

            foreach (array_slice($warisan, 0, 5) as $w) {
                CLI::write('  monev #' . $w['id'] . '  ' . mb_substr((string) $w['nama_opd'], 0, 28)
                    . '  |  ' . mb_substr(trim((string) $w['rencana_aksi']), 0, 42)
                    . '  |  ' . $w['jml_sub'] . ' sub');
            }

            CLI::newLine();
            CLI::write('Tidak ada yang diubah. Jalankan ulang dengan --fix untuk mengosongkan '
                . 'capaian warisannya (metode & barisnya tetap).', 'yellow');

            return 1;
        }

        $db->transStart();

        try {
            foreach ($warisan as $w) {
                $db->table('monev')->where('id', (int) $w['id'])->update([
                    'capaian_triwulan_1' => null,
                    'capaian_triwulan_2' => null,
                    'capaian_triwulan_3' => null,
                    'capaian_triwulan_4' => null,
                    'updated_at'         => date('Y-m-d H:i:s'),
                ]);
            }

            $db->transComplete();

            if ($db->transStatus() === false) {
                CLI::error('Transaksi gagal, tidak ada yang diubah.');

                return 1;
            }
        } catch (Throwable $e) {
            $db->transRollback();
            CLI::error('Gagal: ' . $e->getMessage());

            return 1;
        }

        CLI::newLine();
        CLI::write(count($warisan) . ' baris dikosongkan capaiannya.', 'green');
        CLI::write('Pengukuran kini sepenuhnya mengikuti sub rencana aksi.', 'green');

        return 0;
    }
}
