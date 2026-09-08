<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Cacah data inti, simpan, dan bandingkan (§50).
 *
 *   php spark data:cacah                 tampilkan cacahan sekarang
 *   php spark data:cacah --simpan        simpan sebagai patokan
 *   php spark data:cacah --banding       bandingkan dengan patokan terakhir
 *   php spark data:cacah --db <nama>     cacah basis data lain
 *
 * =====================================================================
 * UNTUK APA
 *
 * §50 menuntut satu hal yang sederhana tetapi mudah terlewat: jumlah baris
 * pada tabel inti TIDAK BOLEH TURUN tanpa persetujuan. Selama ini
 * pemeriksaannya dilakukan tangan, satu SELECT COUNT setiap kali — dan
 * pemeriksaan yang dilakukan tangan adalah pemeriksaan yang suatu saat
 * dilewatkan, biasanya justru pada perubahan yang paling terburu-buru.
 *
 * Perintah ini menyimpan patokan ke berkas dan membandingkannya. Yang NAIK
 * dilaporkan sebagai keterangan; yang TURUN dilaporkan sebagai KEGAGALAN,
 * dengan status keluar bukan-nol supaya bisa menghentikan skrip rilis.
 *
 * =====================================================================
 * HANYA MEMBACA
 *
 * Tidak ada satu pun penulisan ke basis data. Patokannya disimpan sebagai
 * berkas JSON di writable/, bukan sebagai tabel baru — menambah tabel demi
 * mengawasi tabel hanya menambah yang harus diawasi.
 */
class DataCacah extends BaseCommand
{
    protected $group       = 'SAKIP';
    protected $name        = 'data:cacah';
    protected $description = 'Cacah tabel inti, simpan patokan, dan bandingkan (§50 data safety).';
    protected $usage       = 'data:cacah [--simpan] [--banding] [--db <nama>]';
    protected $options     = [
        '--simpan'  => 'simpan cacahan sekarang sebagai patokan',
        '--banding' => 'bandingkan dengan patokan tersimpan',
        '--db'      => 'cacah basis data lain',
    ];

    /**
     * Tabel yang dijaga, beserta sebutannya.
     *
     * Daftarnya mengikuti §50 apa adanya. Ditambah dua yang tidak disebut di
     * sana tetapi sama menyakitkannya bila hilang diam-diam: arsip versi IKU
     * dan registri versi dokumen — keduanya jejak yang tidak bisa disusun
     * ulang dari apa pun bila terhapus.
     */
    private const TABEL = [
        'renstra_sasaran'       => 'Sasaran Renstra',
        'renstra_indikator_sasaran' => 'Indikator Renstra',
        'iku_sasaran'           => 'Sasaran IKU',
        'iku_indikator'         => 'Indikator IKU',
        'cascading_sasaran_opd' => 'Cascading OPD',
        'pk_indikator'          => 'Indikator PK',
        'target_rencana'        => 'Rencana aksi',
        'target_sub_rencana'    => 'Sub rencana aksi',
        'monev'                 => 'MONEV capaian',
        'monev_anggaran'        => 'MONEV anggaran',
        'lakip'                 => 'Baris LAKIP',
        'iku_revisi'            => 'Versi IKU',
        'iku_revisi_indikator'  => 'Indikator terarsip IKU',
        'dokumen_versi'         => 'Versi dokumen',
    ];

    private function berkasPatokan(string $namaDb): string
    {
        return WRITEPATH . 'cacah-' . preg_replace('/[^A-Za-z0-9_\-]/', '_', $namaDb) . '.json';
    }

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

        $simpan  = (bool) CLI::getOption('simpan');
        $banding = (bool) CLI::getOption('banding');

        CLI::write('Basis data: ' . $db->getDatabase(), 'yellow');
        CLI::newLine();

        $cacah = [];

        foreach (self::TABEL as $tabel => $sebutan) {
            // Tabel yang belum terpasang dicatat NULL, bukan 0. Keduanya
            // berbeda arti: "belum ada tabelnya" bukan "tabelnya kosong", dan
            // memperlakukannya sama akan melaporkan penurunan palsu di
            // lingkungan yang migrasinya belum lengkap.
            $cacah[$tabel] = $db->tableExists($tabel)
                ? $db->table($tabel)->countAllResults()
                : null;
        }

        $berkas  = $this->berkasPatokan($db->getDatabase());
        $patokan = null;

        if (is_file($berkas)) {
            $isi     = json_decode((string) file_get_contents($berkas), true);
            $patokan = is_array($isi) ? $isi : null;
        }

        $this->tampilkan($cacah, $banding ? $patokan : null);

        $keluar = EXIT_SUCCESS;

        if ($banding) {
            if ($patokan === null) {
                CLI::error('Belum ada patokan untuk basis data ini. Jalankan --simpan lebih dulu.');

                return EXIT_ERROR;
            }

            $keluar = $this->laporkanSelisih($cacah, $patokan);
        }

        if ($simpan) {
            file_put_contents($berkas, json_encode([
                'basis'  => $db->getDatabase(),
                'waktu'  => date('Y-m-d H:i:s'),
                'cacah'  => $cacah,
            ], JSON_PRETTY_PRINT));

            CLI::newLine();
            CLI::write('Patokan disimpan: ' . $berkas, 'green');
        }

        return $keluar;
    }

    /** @param array<string,int|null> $cacah */
    private function tampilkan(array $cacah, ?array $patokan): void
    {
        $lama = $patokan['cacah'] ?? [];

        $baris = [];

        foreach (self::TABEL as $tabel => $sebutan) {
            $n = $cacah[$tabel];
            $b = [
                $sebutan,
                $tabel,
                $n === null ? '(tak ada)' : (string) $n,
            ];

            if ($patokan !== null) {
                $l = $lama[$tabel] ?? null;
                $b[] = $l === null ? '-' : (string) $l;
                $b[] = ($l === null || $n === null)
                    ? '-'
                    : ($n === $l ? 'sama' : ($n > $l ? '+' . ($n - $l) : (string) ($n - $l)));
            }

            $baris[] = $b;
        }

        $kepala = $patokan === null
            ? ['Sebutan', 'Tabel', 'Jumlah']
            : ['Sebutan', 'Tabel', 'Sekarang', 'Patokan', 'Selisih'];

        CLI::table($baris, $kepala);

        if ($patokan !== null) {
            CLI::write('Patokan dibuat: ' . ($patokan['waktu'] ?? '-'));
        }
    }

    /**
     * @param array<string,int|null> $cacah
     *
     * @return int status keluar — bukan nol bila ada yang TURUN
     */
    private function laporkanSelisih(array $cacah, array $patokan): int
    {
        $lama  = $patokan['cacah'] ?? [];
        $turun = [];
        $naik  = 0;

        foreach ($cacah as $tabel => $n) {
            $l = $lama[$tabel] ?? null;

            if ($l === null || $n === null) {
                continue;
            }

            if ($n < $l) {
                $turun[$tabel] = $l - $n;
            } elseif ($n > $l) {
                $naik++;
            }
        }

        CLI::newLine();

        if ($turun === []) {
            CLI::write('Tidak ada tabel yang berkurang.'
                . ($naik > 0 ? ' (' . $naik . ' tabel bertambah — wajar bila memang ada penambahan data.)' : ''),
                'green');

            return EXIT_SUCCESS;
        }

        CLI::write('BERKURANG — §50 menuntut ini disetujui lebih dulu:', 'red');

        foreach ($turun as $tabel => $selisih) {
            CLI::write(sprintf('  %-28s -%d baris', $tabel, $selisih));
        }

        CLI::newLine();
        CLI::write('Jangan lanjutkan rilis sebelum penurunan ini dijelaskan.', 'red');

        return EXIT_ERROR;
    }
}
