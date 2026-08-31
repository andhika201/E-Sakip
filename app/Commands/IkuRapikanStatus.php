<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Rapikan STATUS revisi IKU agar sejalan dengan garis waktunya.
 *
 * =====================================================================
 * MASALAH YANG DIPERBAIKI
 *
 * Sejak tahun berlaku boleh dipilih bebas, sebuah revisi bisa digeser
 * melewati tetangganya. Penjahit garis waktu membetulkan jendela tahunnya,
 * tetapi pada versi pertamanya TIDAK ikut membetulkan kolom `status` —
 * sehingga muncul keadaan yang saling bertentangan:
 *
 *   revisi yang memayungi tahun TERAKHIR  -> status 'superseded'
 *   revisi yang memayungi tahun AWAL      -> status 'berlaku'
 *
 * Akibatnya berantai:
 *
 *   * `revisiBerlaku()` mengurutkan `berlaku_mulai_tahun DESC`, jadi ia dan
 *     kolom status menunjuk revisi yang berbeda;
 *   * layar revisi menutup PERMANEN apa pun yang berstatus 'superseded',
 *     sehingga izin sunting yang menempel padanya tidak bisa diselesaikan
 *     maupun ditarik — dan karena satu lingkup hanya boleh punya satu
 *     permohonan menggantung, SELURUH lingkup itu terkunci dari permohonan
 *     baru untuk selamanya.
 *
 * Perintah ini menjalankan penjahit yang sudah diperbaiki pada SETIAP
 * lingkup, sehingga statusnya kembali sejalan dan izin yang telanjur
 * menggantung ikut ditutup.
 *
 * Aman diulang: lingkup yang sudah benar tidak disentuh.
 */
class IkuRapikanStatus extends BaseCommand
{
    protected $group       = 'IKU';
    protected $name        = 'iku:rapikan-status';
    protected $description = 'Samakan status revisi IKU dengan garis waktunya (pratinjau; --fix untuk menulis).';
    protected $usage       = 'iku:rapikan-status [--fix]';
    protected $options     = ['--fix' => 'benar-benar menulis; tanpa ini hanya pratinjau'];

    public function run(array $params)
    {
        $kerjakan = array_key_exists('fix', $params) || CLI::getOption('fix');
        $db       = db_connect();
        $rev      = new \App\Models\Opd\IkuRevisiModel();

        if (! $rev->siap()) {
            CLI::error('Tabel revisi IKU belum tersedia.');

            return EXIT_ERROR;
        }

        CLI::write('Basis data : ' . $db->getDatabase());
        CLI::write('Mode       : ' . ($kerjakan ? CLI::color('KERJAKAN', 'red') : CLI::color('pratinjau', 'yellow')));
        CLI::newLine();

        $lingkup = $db->table('iku_revisi r')
            ->select('r.opd_key, r.opd_id, r.tahun_mulai, r.tahun_akhir, o.nama_opd', false)
            ->join('opd o', 'o.id = r.opd_key', 'left')
            ->groupBy(['r.opd_key', 'r.opd_id', 'r.tahun_mulai', 'r.tahun_akhir', 'o.nama_opd'])
            ->orderBy('o.nama_opd', 'ASC')
            ->get()->getResultArray();

        $perlu = 0;
        $utuh  = 0;

        foreach ($lingkup as $l) {
            $opdId = $l['opd_id'] !== null ? (int) $l['opd_id'] : null;
            $tm    = (int) $l['tahun_mulai'];
            $ta    = (int) $l['tahun_akhir'];
            $nama  = mb_substr($l['nama_opd'] ?? 'IKU Kabupaten', 0, 40);

            // Siapa SEHARUSNYA berlaku: yang mulai berlakunya paling akhir.
            $harusnya = $db->table('iku_revisi')
                ->where('opd_key', (int) $l['opd_key'])
                ->where('tahun_mulai', $tm)->where('tahun_akhir', $ta)
                ->whereIn('status', ['berlaku', 'superseded'])
                ->orderBy('berlaku_mulai_tahun', 'DESC')->orderBy('nomor', 'DESC')
                ->get()->getRowArray();

            if ($harusnya === null) {
                continue;
            }

            $salah = $db->table('iku_revisi')
                ->where('opd_key', (int) $l['opd_key'])
                ->where('tahun_mulai', $tm)->where('tahun_akhir', $ta)
                ->where('status', 'berlaku')
                ->where('id !=', (int) $harusnya['id'])
                ->countAllResults();

            $terkiniSalahStatus = $harusnya['status'] !== 'berlaku';

            // Jendela yang tumpang tindih ikut dicari, bukan hanya statusnya.
            // Keduanya lahir dari sebab yang sama — sahkan() yang dulu tidak
            // menjahit ulang — tetapi bisa muncul sendiri-sendiri: sebuah
            // `berlaku_sampai_tahun` warisan bisa menabrak tahun mulai revisi
            // berikutnya tanpa membuat status mana pun keliru. Tahun yang
            // bertabrakan itulah yang membuat resolveEfektif() menolak
            // melayani, dan layar menampilkan "Konflik masa berlaku revisi".
            $konflik = [];

            foreach (range($tm, $ta) as $th) {
                if ($rev->resolveEfektif($opdId, $th)['konflik'] !== []) {
                    $konflik[] = $th;
                }
            }

            if ($salah === 0 && ! $terkiniSalahStatus && $konflik === []) {
                $utuh++;

                continue;
            }

            $perlu++;

            $sebab = [];

            if ($terkiniSalahStatus || $salah > 0) {
                $sebab[] = 'revisi #' . (int) $harusnya['id'] . ' (mulai '
                    . (int) $harusnya['berlaku_mulai_tahun'] . ') seharusnya BERLAKU, kini '
                    . $harusnya['status'];
            }

            if ($salah > 0) {
                $sebab[] = $salah . ' revisi lain juga berstatus berlaku';
            }

            if ($konflik !== []) {
                $sebab[] = 'tahun bertabrakan: ' . implode(', ', $konflik);
            }

            CLI::write(sprintf('  %-42s %s', $nama, implode('; ', $sebab)));

            if (! $kerjakan) {
                continue;
            }

            try {
                $r = new \ReflectionMethod($rev, 'jahitUlangTimeline');
                $r->setAccessible(true);
                $r->invoke($rev, $opdId, $tm, $ta);

                CLI::write('      ' . CLI::color('dirapikan', 'green'));
            } catch (\Throwable $e) {
                CLI::write('      ' . CLI::color('GAGAL: ' . $e->getMessage(), 'red'));
            }
        }

        CLI::newLine();
        CLI::write(sprintf('Lingkup perlu dirapikan: %d   sudah benar: %d', $perlu, $utuh));

        if ($perlu > 0 && ! $kerjakan) {
            CLI::write('Belum ada yang ditulis. Ulangi dengan --fix untuk mengerjakan.', 'yellow');
        }

        return EXIT_SUCCESS;
    }
}
