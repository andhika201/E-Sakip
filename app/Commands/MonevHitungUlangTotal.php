<?php

namespace App\Commands;

use App\Models\SatuanModel;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Throwable;

/**
 * Hitung ulang `monev.total` memakai aturan Capaian Total yang berlaku.
 *
 *   php spark monev:hitung-ulang            laporan saja (dry-run)
 *   php spark monev:hitung-ulang --fix      tulis hasilnya
 *   php spark monev:hitung-ulang --db <db>  kerjakan pada salinan
 *
 * =====================================================================
 * MENGAPA PERLU
 *
 * `monev.total` adalah kolom TERSIMPAN: nilainya ditulis sekali saat form
 * MONEV disimpan, memakai aturan yang berlaku SAAT ITU. Kartu "Rata-rata
 * Capaian", ekspor, dan API membacanya apa adanya.
 *
 * Akibatnya setiap kali aturan Capaian Total berubah, baris lama tetap
 * membawa angka lama sampai seseorang membuka dan menyimpannya kembali satu
 * per satu. Pada basis data ini tersimpan juga nilai yang jelas bukan
 * persentase — mis. `4091` (jumlah capaian mentah 2183 + 1908) dan `"58%"`
 * lengkap dengan tanda persennya.
 *
 * Perintah ini menghitung ulang seluruhnya lewat satu-satunya sumber
 * kebenaran, `calculateCapaianTotalPercentage()`, sehingga yang tersimpan
 * selalu sama dengan yang dihitung layar.
 *
 * =====================================================================
 * YANG TIDAK DILAKUKAN
 *
 * Capaian triwulan, metode, dan target TIDAK disentuh sama sekali — hanya
 * kolom `total` yang ditulis ulang. Baris yang hasilnya "belum dapat dinilai"
 * disimpan NULL, bukan 0 dan bukan 100.
 */
class MonevHitungUlangTotal extends BaseCommand
{
    protected $group       = 'SAKIP';
    protected $name        = 'monev:hitung-ulang';
    protected $description = 'Hitung ulang monev.total dengan aturan Capaian Total yang berlaku.';
    protected $usage       = 'monev:hitung-ulang [--fix] [--db <nama>]';
    protected $options     = [
        '--fix' => 'benar-benar menulis; tanpa ini hanya laporan',
        '--db'  => 'kerjakan pada basis data lain (mis. salinan uji)',
    ];

    public function run(array $params)
    {
        helper(['capaian']);

        $kerjakan = array_key_exists('fix', $params) || CLI::getOption('fix');
        $namaDb   = trim((string) (CLI::getOption('db') ?: ''));

        if ($namaDb !== '' && $namaDb !== '1') {
            $cfg             = config('Database')->default;
            $cfg['database'] = $namaDb;
            $db              = db_connect($cfg, false);
        } else {
            $db = db_connect();
        }

        CLI::write('Basis data : ' . $db->getDatabase());
        CLI::write('Mode       : ' . ($kerjakan ? CLI::color('KERJAKAN', 'red') : CLI::color('laporan saja', 'yellow')));
        CLI::newLine();

        $satuan = new SatuanModel($db);
        $skala  = [];

        // Baris MONEV beserta target yang berlaku untuknya: target sub bila
        // barisnya milik sub, target rencana aksi bila bukan.
        $rows = $db->query(
            'SELECT m.id, m.total, m.metode_perhitungan,
                    m.capaian_triwulan_1 c1, m.capaian_triwulan_2 c2,
                    m.capaian_triwulan_3 c3, m.capaian_triwulan_4 c4,
                    CASE WHEN s.id IS NOT NULL THEN s.target_triwulan_1 ELSE tr.target_triwulan_1 END t1,
                    CASE WHEN s.id IS NOT NULL THEN s.target_triwulan_2 ELSE tr.target_triwulan_2 END t2,
                    CASE WHEN s.id IS NOT NULL THEN s.target_triwulan_3 ELSE tr.target_triwulan_3 END t3,
                    CASE WHEN s.id IS NOT NULL THEN s.target_triwulan_4 ELSE tr.target_triwulan_4 END t4,
                    pi.id_satuan
               FROM monev m
               JOIN target_rencana tr ON tr.id = m.target_rencana_id
               LEFT JOIN target_sub_rencana s ON s.id = m.target_sub_rencana_id
               LEFT JOIN pk_indikator pi ON pi.id = tr.pk_indikator_id
              WHERE (m.target_sub_rencana_id > 0 AND s.id IS NOT NULL)
                 OR (COALESCE(m.target_sub_rencana_id, 0) = 0
                     AND NOT EXISTS (SELECT 1 FROM target_sub_rencana x
                                      WHERE x.target_rencana_id = m.target_rencana_id))'
        )->getResultArray();

        $ubah = 0;
        $sama = 0;
        $jadiNull = 0;
        $contoh   = [];

        foreach ($rows as $r) {
            $idSatuan = (int) ($r['id_satuan'] ?? 0);

            if ($idSatuan > 0 && ! array_key_exists($idSatuan, $skala)) {
                try {
                    $skala[$idSatuan] = $satuan->skalaBySatuan($idSatuan);
                } catch (Throwable $e) {
                    $skala[$idSatuan] = [];
                }
            }

            $hasil = calculateCapaianTotalPercentage(
                $r['metode_perhitungan'],
                [1 => $r['t1'], 2 => $r['t2'], 3 => $r['t3'], 4 => $r['t4']],
                [1 => $r['c1'], 2 => $r['c2'], 3 => $r['c3'], 4 => $r['c4']],
                $skala[$idSatuan] ?? []
            );

            $baru = $hasil['percentage'];
            $lama = $r['total'];

            // Perbandingan dilakukan atas ANGKA, bukan teks: kolomnya VARCHAR
            // dan sempat menampung "58%", sehingga membandingkan string akan
            // melaporkan perubahan palsu setiap kali dijalankan.
            $lamaAngka = $lama === null || trim((string) $lama) === '' ? null : capaianToFloat($lama);
            $samaAngka = ($lamaAngka === null && $baru === null)
                || ($lamaAngka !== null && $baru !== null && abs($lamaAngka - (float) $baru) < 1e-9);

            if ($samaAngka) {
                $sama++;

                continue;
            }

            $ubah++;

            if ($baru === null) {
                $jadiNull++;
            }

            if (count($contoh) < 12) {
                $contoh[] = sprintf('  monev #%-6d %-12s -> %-12s  %s',
                    $r['id'],
                    $lama === null ? 'NULL' : (string) $lama,
                    $baru === null ? 'NULL' : (string) $baru,
                    (string) ($hasil['status'] ?? '?'));
            }

            if ($kerjakan) {
                $db->table('monev')->where('id', $r['id'])->update(['total' => $baru]);
            }
        }

        if ($contoh !== []) {
            CLI::write('Contoh perubahan:', 'yellow');
            foreach ($contoh as $c) {
                CLI::write($c);
            }
            CLI::newLine();
        }

        CLI::write(sprintf('Diperiksa: %d   tetap: %d   berubah: %d   (di antaranya jadi NULL: %d)',
            count($rows), $sama, $ubah, $jadiNull));

        if (! $kerjakan && $ubah > 0) {
            CLI::newLine();
            CLI::write('Belum ada yang ditulis. Ulangi dengan --fix untuk menyimpannya.', 'yellow');
        }

        return EXIT_SUCCESS;
    }
}
