<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Rapikan SASARAN KEMBAR di dalam revisi IKU.
 *
 * =====================================================================
 * DARI MANA KEMBARNYA
 *
 * Sampai perbaikan 2026-09-01, `imporKandidat()` mencocokkan sasaran yang
 * sudah ada di draft HANYA lewat teksnya. Begitu redaksi sasaran di Renstra
 * disunting — satu kata ditambah, tanda baca dirapikan — teksnya tidak lagi
 * sama, sync tidak menemukan padanannya, lalu menyisipkan baris KEDUA untuk
 * sasaran Renstra yang sama.
 *
 * Cirinya pasti: dua baris `iku_revisi_sasaran` dalam satu revisi dengan
 * `source_ref_id` yang sama.
 *
 * =====================================================================
 * MENGAPA DIGABUNG, BUKAN DIHAPUS
 *
 * Baris kembar itu TIDAK kosong. Ia lahir dari sync, jadi ia justru memuat
 * indikator BARU yang belum ada di baris aslinya — pada kasus yang memicu
 * perintah ini, satu indikator yang memang baru ditambahkan di Renstra.
 * Menghapusnya begitu saja berarti membuang indikator itu diam-diam.
 *
 * Karena itu urutannya:
 *
 *   1. Pilih baris yang DIPERTAHANKAN: yang bertaut ke IKU berjalan
 *      (`sumber_sasaran_id` terisi). Ia yang dikenali seluruh modul lain.
 *   2. Pindahkan indikator dari baris kembar ke baris itu — kecuali yang
 *      sudah ada padanannya (silsilah atau teks sama), yang memang salinan
 *      dan boleh dibuang.
 *   3. Baris kembar yang sudah kosong barulah dihapus.
 *
 * Targetnya ikut karena `iku_revisi_target.revisi_indikator_id` menunjuk
 * baris indikator, dan baris itu hanya BERPINDAH induk, tidak dibuat ulang.
 *
 * =====================================================================
 * ARSIP TIDAK DISENTUH TANPA DIMINTA
 *
 * Revisi berstatus `berlaku`/`superseded` adalah dokumen yang dibaca LAKIP
 * tahun-tahun yang dipayunginya. Mengubahnya berarti mengubah bacaan laporan
 * yang sudah jadi. Perintah ini melewatinya kecuali diminta tegas dengan
 * --termasuk-arsip.
 */
class IkuRapikanKembar extends BaseCommand
{
    protected $group       = 'IKU';
    protected $name        = 'iku:rapikan-kembar';
    protected $description = 'Gabungkan sasaran kembar di revisi IKU (pratinjau; --fix untuk menulis).';
    protected $usage       = 'iku:rapikan-kembar [--fix] [--termasuk-arsip] [--db <nama>]';
    protected $options     = [
        '--fix'            => 'benar-benar menulis; tanpa ini hanya pratinjau',
        '--termasuk-arsip' => 'ikut merapikan revisi berlaku/superseded (hati-hati)',
        '--db'             => 'kerjakan pada basis data lain (mis. salinan uji)',
    ];

    public function run(array $params)
    {
        $kerjakan = array_key_exists('fix', $params) || CLI::getOption('fix');
        $arsip    = array_key_exists('termasuk-arsip', $params) || CLI::getOption('termasuk-arsip');
        $namaDb   = trim((string) (CLI::getOption('db') ?: ''));

        if ($namaDb !== '' && $namaDb !== '1') {
            $cfg             = config('Database')->default;
            $cfg['database'] = $namaDb;
            $db              = db_connect($cfg, false);
        } else {
            $db = db_connect();
        }

        if (! $db->tableExists('iku_revisi_sasaran')) {
            CLI::error('Tabel revisi IKU belum tersedia.');

            return EXIT_ERROR;
        }

        CLI::write('Basis data : ' . $db->getDatabase());
        CLI::write('Mode       : ' . ($kerjakan ? CLI::color('KERJAKAN', 'red') : CLI::color('pratinjau', 'yellow')));
        CLI::write('Arsip      : ' . ($arsip ? 'ikut dirapikan' : 'dilewati'));
        CLI::newLine();

        $status = $arsip
            ? ['draft', 'menunggu', 'berlaku', 'superseded']
            : ['draft', 'menunggu'];

        // Kelompok kembar: satu revisi, satu silsilah, lebih dari satu baris.
        $grup = $db->table('iku_revisi_sasaran rs')
            ->select('rs.revisi_id, rs.source_ref_id, COUNT(*) AS n', false)
            ->join('iku_revisi r', 'r.id = rs.revisi_id')
            ->where('rs.source_ref_id IS NOT NULL', null, false)
            ->whereIn('r.status', $status)
            ->groupBy(['rs.revisi_id', 'rs.source_ref_id'])
            ->having('n >', 1)
            ->get()->getResultArray();

        if ($grup === []) {
            CLI::write('  Tidak ada sasaran kembar. Tidak ada yang perlu dirapikan.', 'green');

            return EXIT_SUCCESS;
        }

        $totalPindah = 0;
        $totalBuang  = 0;
        $totalHapus  = 0;

        foreach ($grup as $g) {
            $revisiId = (int) $g['revisi_id'];
            $ref      = (int) $g['source_ref_id'];

            $rev = $db->table('iku_revisi r')
                ->select('r.id, r.nomor, r.status, r.opd_key, o.nama_opd')
                ->join('opd o', 'o.id = r.opd_key', 'left')
                ->where('r.id', $revisiId)->get()->getRowArray();

            $baris = $db->table('iku_revisi_sasaran')
                ->where('revisi_id', $revisiId)->where('source_ref_id', $ref)
                ->orderBy('sumber_sasaran_id IS NULL', 'ASC', false)
                ->orderBy('id', 'ASC')
                ->get()->getResultArray();

            // Yang dipertahankan: bertaut ke IKU berjalan. Urutan di atas sudah
            // menaruhnya di depan; kalau tak satu pun bertaut, id terkecil.
            $simpan = array_shift($baris);
            $idKeep = (int) $simpan['id'];

            CLI::write(sprintf('  %s — revisi #%d (ke-%s, %s), silsilah #%d',
                mb_substr((string) ($rev['nama_opd'] ?? 'OPD ' . $rev['opd_key']), 0, 40),
                $revisiId, $rev['nomor'], $rev['status'], $ref));
            CLI::write(sprintf('    dipertahankan : #%-5d %s', $idKeep,
                mb_substr((string) $simpan['sasaran'], 0, 52)));

            // Padanan yang sudah ada di baris yang dipertahankan.
            $adaRef  = [];
            $adaTeks = [];

            foreach ($db->table('iku_revisi_indikator')->where('revisi_sasaran_id', $idKeep)
                ->get()->getResultArray() as $i) {
                if (! empty($i['source_ref_id'])) {
                    $adaRef[(int) $i['source_ref_id']] = true;
                }

                $adaTeks[$this->kunci((string) $i['indikator'])] = true;
            }

            $urutan = (int) $db->table('iku_revisi_indikator')
                ->where('revisi_sasaran_id', $idKeep)->countAllResults();

            foreach ($baris as $kembar) {
                $idKembar = (int) $kembar['id'];

                CLI::write(sprintf('    kembar        : #%-5d %s', $idKembar,
                    mb_substr((string) $kembar['sasaran'], 0, 52)));

                foreach ($db->table('iku_revisi_indikator')->where('revisi_sasaran_id', $idKembar)
                    ->orderBy('urutan')->orderBy('id')->get()->getResultArray() as $i) {
                    $idInd = (int) $i['id'];
                    $refI  = (int) ($i['source_ref_id'] ?? 0);
                    $kunci = $this->kunci((string) $i['indikator']);

                    $sudahAda = ($refI > 0 && isset($adaRef[$refI])) || isset($adaTeks[$kunci]);

                    if ($sudahAda) {
                        CLI::write(sprintf('        - buang  #%-5d %s %s', $idInd,
                            mb_substr((string) $i['indikator'], 0, 44),
                            CLI::color('(sudah ada padanannya)', 'dark_gray')));
                        $totalBuang++;

                        if ($kerjakan) {
                            $db->table('iku_revisi_indikator')->where('id', $idInd)->delete();
                        }

                        continue;
                    }

                    CLI::write(sprintf('        - PINDAH #%-5d %s %s', $idInd,
                        mb_substr((string) $i['indikator'], 0, 44),
                        CLI::color('-> sasaran #' . $idKeep, 'green')));
                    $totalPindah++;

                    if ($kerjakan) {
                        $db->table('iku_revisi_indikator')->where('id', $idInd)->update([
                            'revisi_sasaran_id' => $idKeep,
                            'urutan'            => $urutan++,
                            'updated_at'        => date('Y-m-d H:i:s'),
                        ]);
                    }

                    if ($refI > 0) {
                        $adaRef[$refI] = true;
                    }

                    $adaTeks[$kunci] = true;
                }

                // Barulah baris kembarnya dibuang — sesudah isinya diselamatkan.
                CLI::write(sprintf('        - HAPUS  sasaran #%d (sudah kosong)', $idKembar));
                $totalHapus++;

                if ($kerjakan) {
                    $sisa = $db->table('iku_revisi_indikator')
                        ->where('revisi_sasaran_id', $idKembar)->countAllResults();

                    if ($sisa > 0) {
                        CLI::write(CLI::color('        GAGAL: masih ada ' . $sisa
                            . ' indikator; baris tidak dihapus.', 'red'));

                        continue;
                    }

                    $db->table('iku_revisi_sasaran')->where('id', $idKembar)->delete();
                }
            }

            CLI::newLine();
        }

        CLI::write(sprintf('Indikator dipindahkan: %d   dibuang (salinan): %d   sasaran kembar dihapus: %d',
            $totalPindah, $totalBuang, $totalHapus));

        if (! $kerjakan) {
            CLI::newLine();
            CLI::write('Belum ada yang ditulis. Ulangi dengan --fix untuk mengerjakan.', 'yellow');
        }

        return EXIT_SUCCESS;
    }

    /** Kunci pembanding teks; sama aturannya dengan IkuRevisiModel::kunciTeks(). */
    private function kunci(?string $teks): string
    {
        return mb_strtolower(trim(preg_replace('/\s+/u', ' ', (string) $teks)));
    }
}
