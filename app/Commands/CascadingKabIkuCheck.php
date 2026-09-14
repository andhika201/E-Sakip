<?php

namespace App\Commands;

use App\Models\CascadingModel;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Database;

/**
 * Periksa Cascading Kabupaten bertulang punggung IKU Kabupaten.
 *
 *   php spark casc:kab-verify
 *
 * Kembaran casc:verify untuk sisi kabupaten. Yang diuji bukan skemanya —
 * itu urusan migrasi — melainkan yang dilihat pemakai:
 *
 *   1. barisnya adalah indikator IKU Kabupaten berjalan: tidak ada yang
 *      hilang, tidak ada yang ganda, dan tidak ada indikator RPJMD tanpa
 *      padanan IKU yang menyelinap masuk;
 *   2. revisi teks IKU langsung terbaca, tanpa RPJMD ikut tersentuh, dan
 *      target berasal dari IKU;
 *   3. jangkar RPJMD: sasaran bersilsilah punya Misi/Tujuan; sasaran tanpa
 *      jangkar TETAP TAMPIL dengan Misi/Tujuan kosong (bukan hilang); jangkar
 *      manual (`rpjmd_tujuan_id`) memindahkannya ke tempatnya;
 *   4. nama kolom lama tidak berubah — view, cetak, Excel, dan halaman publik
 *      bergantung padanya.
 *
 * Menulis SEMENTARA ke `iku_indikator` (butir 2) dan `iku_sasaran` (butir 3),
 * lalu mengembalikannya. Jalankan pada salinan bila ragu.
 *
 * Sebelum 14 Sep 2026 perintah ini menuntut seluruh indikator RPJMD tampil —
 * itu kontrak lama (RPJMD sebagai tulang punggung) dan sengaja dibalik.
 */
class CascadingKabIkuCheck extends BaseCommand
{
    protected $group       = 'SAKIP';
    protected $name        = 'casc:kab-verify';
    protected $description = 'Uji Cascading Kabupaten bertulang punggung IKU Kabupaten (jangkar RPJMD).';

    private int $lulus = 0;
    private int $gagal = 0;

    private function cek(string $nama, bool $ok, string $detail = ''): void
    {
        if ($ok) {
            $this->lulus++;
            CLI::write('  ' . CLI::color('LULUS', 'green') . "  {$nama}" . ($detail !== '' ? "  ({$detail})" : ''));
        } else {
            $this->gagal++;
            CLI::write('  ' . CLI::color('GAGAL', 'red') . "  {$nama}" . ($detail !== '' ? "  -> {$detail}" : ''));
        }
    }

    public function run(array $params)
    {
        $db    = Database::connect();
        $model = new CascadingModel();

        CLI::write('Basis data: ' . $db->getDatabase(), 'yellow');
        CLI::newLine();

        $periode = $db->table('rpjmd_misi')
            ->select('tahun_mulai, tahun_akhir, COUNT(*) AS n', false)
            ->groupBy(['tahun_mulai', 'tahun_akhir'])
            ->orderBy('n', 'DESC')->limit(1)
            ->get()->getRowArray();

        if ($periode === null) {
            CLI::error('Belum ada periode RPJMD.');

            return 1;
        }

        $tm = (int) $periode['tahun_mulai'];
        $ta = (int) $periode['tahun_akhir'];

        $rows = $model->getMatrix($tm, $ta);

        // ---------------------------------------------------------------
        CLI::write('== 1. Tulang punggung IKU Kabupaten ==', 'cyan');
        // ---------------------------------------------------------------
        $this->cek('matriks terbaca', $rows !== [], 'baris: ' . count($rows));

        if ($rows === []) {
            return $this->tutup();
        }

        foreach (['misi', 'misi_id', 'tujuan_rpjmd', 'tujuan_id', 'sasaran_rpjmd', 'sasaran_id',
                  'indikator_sasaran', 'indikator_id', 'iku_indikator_id', 'rpjmd_indikator_id',
                  'satuan', 'baseline', 'targets', 'jangkar', 'nama_opd', 'program_kegiatan', 'is_mapped'] as $kolom) {
            $this->cek("kolom `{$kolom}` ada", array_key_exists($kolom, $rows[0]));
        }

        $indikatorTampil = array_values(array_unique(array_filter(array_column($rows, 'indikator_id'))));

        $ikuBerjalan = array_map('intval', array_column($db->table('iku_indikator iki')
            ->select('iki.id')
            ->join('iku_sasaran iks', 'iks.id = iki.iku_sasaran_id')
            ->where('iks.opd_id IS NULL', null, false)
            ->where('iks.tahun_mulai', $tm)->where('iks.tahun_akhir', $ta)
            ->where('iks.dihentikan_pada IS NULL', null, false)
            ->where('iki.dihentikan_pada IS NULL', null, false)
            ->get()->getResultArray(), 'id'));

        sort($indikatorTampil);
        sort($ikuBerjalan);

        $this->cek('yang tampil = seluruh indikator IKU Kabupaten berjalan (tidak hilang, tidak ganda)',
            $indikatorTampil === $ikuBerjalan,
            'tampil: ' . count($indikatorTampil) . ', IKU berjalan: ' . count($ikuBerjalan));

        $semuaIku = true;
        foreach ($rows as $r) {
            if (! empty($r['indikator_id']) && (int) $r['indikator_id'] !== (int) ($r['iku_indikator_id'] ?? 0)) {
                $semuaIku = false;
                break;
            }
        }
        $this->cek('setiap baris berindikator adalah indikator IKU (bukan RPJMD)', $semuaIku);

        // Indikator RPJMD yang TIDAK punya padanan IKU tidak boleh tampil —
        // itulah perbedaan kontrak baru dengan yang lama.
        $rpjmdTanpaIku = 0;
        if ($db->fieldExists('source_indikator_id', 'iku_indikator')) {
            $rpjmdTanpaIku = (int) $db->table('rpjmd_indikator_sasaran ris')
                ->join('rpjmd_sasaran rs', 'rs.id = ris.sasaran_id')
                ->join('rpjmd_tujuan rt', 'rt.id = rs.tujuan_id')
                ->join('rpjmd_misi rm', 'rm.id = rt.misi_id')
                ->where('rm.tahun_mulai', $tm)->where('rm.tahun_akhir', $ta)
                ->where("NOT EXISTS (SELECT 1 FROM iku_indikator x WHERE x.source_type = 'rpjmd'
                         AND x.source_indikator_id = ris.id AND x.dihentikan_pada IS NULL)", null, false)
                ->countAllResults();
        }

        $teksTampil = array_map(static fn ($r) => trim((string) ($r['indikator_sasaran'] ?? '')), $rows);
        $rpjmdMenyelinap = 0;

        if ($rpjmdTanpaIku > 0) {
            $namaRpjmd = $db->table('rpjmd_indikator_sasaran ris')
                ->select('ris.indikator_sasaran')
                ->join('rpjmd_sasaran rs', 'rs.id = ris.sasaran_id')
                ->join('rpjmd_tujuan rt', 'rt.id = rs.tujuan_id')
                ->join('rpjmd_misi rm', 'rm.id = rt.misi_id')
                ->where('rm.tahun_mulai', $tm)->where('rm.tahun_akhir', $ta)
                ->where("NOT EXISTS (SELECT 1 FROM iku_indikator x WHERE x.source_type = 'rpjmd'
                         AND x.source_indikator_id = ris.id AND x.dihentikan_pada IS NULL)", null, false)
                ->get()->getResultArray();

            foreach ($namaRpjmd as $n) {
                if (in_array(trim((string) $n['indikator_sasaran']), $teksTampil, true)) {
                    $rpjmdMenyelinap++;
                }
            }
        }

        $this->cek('indikator RPJMD tanpa padanan IKU TIDAK menyelinap masuk',
            $rpjmdMenyelinap === 0,
            "RPJMD tanpa padanan IKU: {$rpjmdTanpaIku}, yang tampil: {$rpjmdMenyelinap}");

        // ---------------------------------------------------------------
        CLI::newLine();
        CLI::write('== 2. Teks & target dari IKU ==', 'cyan');
        // ---------------------------------------------------------------
        $contoh = null;
        foreach ($rows as $r) {
            if (! empty($r['iku_indikator_id']) && ! empty($r['rpjmd_indikator_id'])) {
                $contoh = $r;
                break;
            }
        }

        if ($contoh === null) {
            CLI::write('  (dilewati — belum ada indikator IKU bersilsilah RPJMD)', 'dark_gray');
        } else {
            $ikuId = (int) $contoh['iku_indikator_id'];
            $risId = (int) $contoh['rpjmd_indikator_id'];

            $asli = $db->table('iku_indikator')->select('indikator')->where('id', $ikuId)->get()->getRowArray();
            $teksBaru = 'UJI CASCADING KAB ' . $ikuId;
            $db->table('iku_indikator')->where('id', $ikuId)->update(['indikator' => $teksBaru]);

            try {
                $sesudah = $model->getMatrix($tm, $ta);
                $terbaca = false;

                foreach ($sesudah as $r) {
                    if ((int) ($r['iku_indikator_id'] ?? 0) === $ikuId && $r['indikator_sasaran'] === $teksBaru) {
                        $terbaca = true;
                        break;
                    }
                }

                $this->cek('teks IKU yang diubah langsung terbaca di cascading', $terbaca);

                $ris = $db->table('rpjmd_indikator_sasaran')->select('indikator_sasaran')
                    ->where('id', $risId)->get()->getRowArray();
                $this->cek('teks RPJMD TIDAK ikut tersentuh', ($ris['indikator_sasaran'] ?? '') !== $teksBaru);
            } finally {
                $db->table('iku_indikator')->where('id', $ikuId)->update(['indikator' => $asli['indikator']]);
            }

            $pulih = $db->table('iku_indikator')->select('indikator')->where('id', $ikuId)->get()->getRowArray();
            $this->cek('teks IKU dikembalikan seperti semula', $pulih['indikator'] === $asli['indikator']);

            $tahunIku = $db->table('iku_target')->select('tahun, target')
                ->where('iku_indikator_id', $ikuId)->orderBy('tahun', 'ASC')->get()->getRowArray();

            if ($tahunIku) {
                $this->cek('target yang tampil berasal dari IKU',
                    (string) ($contoh['targets'][$tahunIku['tahun']] ?? '') === (string) $tahunIku['target']);
            } else {
                CLI::write('  (uji target dilewati — indikator itu belum punya target IKU)', 'dark_gray');
            }
        }

        // ---------------------------------------------------------------
        CLI::newLine();
        CLI::write('== 3. Jangkar RPJMD ==', 'cyan');
        // ---------------------------------------------------------------
        $perSasaran = [];
        foreach ($rows as $r) {
            $perSasaran[(int) $r['sasaran_id']] = $r;
        }

        $bersilsilah = array_filter($perSasaran, static fn ($r) => ($r['jangkar'] ?? '') === 'silsilah');
        $misiKosongPadahalSilsilah = 0;
        foreach ($bersilsilah as $r) {
            if ($r['misi_id'] === null || $r['tujuan_id'] === null || empty($r['misi']) || empty($r['tujuan_rpjmd'])) {
                $misiKosongPadahalSilsilah++;
            }
        }
        $this->cek('sasaran bersilsilah RPJMD punya Misi & Tujuan',
            $misiKosongPadahalSilsilah === 0, count($bersilsilah) . ' sasaran');

        $tanpaJangkar = array_filter($perSasaran, static fn ($r) => ($r['jangkar'] ?? '') === '');
        $tampakKosong = true;
        foreach ($tanpaJangkar as $r) {
            if ($r['misi_id'] !== null || $r['tujuan_id'] !== null || $r['misi'] !== null || $r['tujuan_rpjmd'] !== null) {
                $tampakKosong = false;
            }
        }
        $this->cek('sasaran tanpa jangkar TETAP TAMPIL dengan Misi/Tujuan kosong',
            $tampakKosong, count($tanpaJangkar) . ' sasaran');

        // Jangkar manual: pinjam satu sasaran live tanpa jangkar (atau, bila
        // semuanya sudah berjangkar, sasaran mana pun) — set rpjmd_tujuan_id
        // sementara, lihat barisnya pindah, lalu kembalikan.
        if (! $db->fieldExists('rpjmd_tujuan_id', 'iku_sasaran')) {
            CLI::write('  (uji jangkar manual dilewati — jalankan db/update_2026-09-14_jangkar_rpjmd_iku_kabupaten.sql)', 'dark_gray');
        } else {
            $kandidat = $tanpaJangkar !== [] ? array_key_first($tanpaJangkar) : null;

            if ($kandidat === null) {
                // Semua berjangkar: pakai sasaran yang jangkarnya 'indikator'
                // (jangkar manual tidak menimpa silsilah sasaran, tetapi
                // menimpa penurunan dari indikator? tidak — silsilah indikator
                // lebih dulu). Maka uji hanya bermakna pada sasaran tanpa jangkar.
                CLI::write('  (uji jangkar manual dilewati — tidak ada sasaran tanpa jangkar)', 'dark_gray');
            } else {
                $tujuanUji = $db->table('rpjmd_tujuan t')->select('t.id')
                    ->join('rpjmd_misi m', 'm.id = t.misi_id')
                    ->where('m.tahun_mulai', $tm)->where('m.tahun_akhir', $ta)
                    ->orderBy('t.id', 'ASC')->get()->getRowArray();

                $live = $db->table('iku_sasaran')->select('rpjmd_tujuan_id')->where('id', $kandidat)->get()->getRowArray();

                $db->table('iku_sasaran')->where('id', $kandidat)->update(['rpjmd_tujuan_id' => (int) $tujuanUji['id']]);

                try {
                    $sesudah = $model->getMatrix($tm, $ta);
                    $pindah  = false;

                    foreach ($sesudah as $r) {
                        if ((int) $r['sasaran_id'] === $kandidat) {
                            $pindah = (int) ($r['tujuan_id'] ?? 0) === (int) $tujuanUji['id']
                                && ($r['jangkar'] ?? '') === 'tujuan' && ! empty($r['misi']);
                            break;
                        }
                    }

                    $this->cek('jangkar manual (rpjmd_tujuan_id) memindahkan sasaran ke tujuannya', $pindah);
                } finally {
                    $db->table('iku_sasaran')->where('id', $kandidat)
                        ->update(['rpjmd_tujuan_id' => $live['rpjmd_tujuan_id'] ?? null]);
                }

                $pulih = $db->table('iku_sasaran')->select('rpjmd_tujuan_id')->where('id', $kandidat)->get()->getRowArray();
                $this->cek('jangkar dikembalikan seperti semula',
                    (string) ($pulih['rpjmd_tujuan_id'] ?? '') === (string) ($live['rpjmd_tujuan_id'] ?? ''));
            }
        }

        // ---------------------------------------------------------------
        CLI::newLine();
        CLI::write('== 4. Pohon kinerja selaras ==', 'cyan');
        // ---------------------------------------------------------------
        $tree = $model->getPohonKinerja($tm, $ta);
        $idDiPohon = [];

        foreach ($tree as $m) {
            foreach ($m['tujuan'] as $t) {
                foreach ($t['sasaran'] as $s) {
                    foreach ($s['indikator_sasaran'] as $i) {
                        $idDiPohon[] = (int) $i['id'];
                    }
                }
            }
        }
        sort($idDiPohon);
        $idDiPohon = array_values(array_unique($idDiPohon));

        $this->cek('indikator di pohon = indikator di tabel', $idDiPohon === $indikatorTampil,
            'pohon: ' . count($idDiPohon) . ', tabel: ' . count($indikatorTampil));

        $adaSimpulKosong = false;
        foreach ($tree as $m) {
            if (! empty($m['jangkar_kosong'])) {
                $adaSimpulKosong = true;
            }
        }
        $this->cek('simpul "belum dijangkarkan" muncul hanya bila memang ada',
            $adaSimpulKosong === ($tanpaJangkar !== []));

        return $this->tutup();
    }

    private function tutup(): int
    {
        CLI::newLine();
        CLI::write('LULUS: ' . CLI::color((string) $this->lulus, 'green')
            . '   GAGAL: ' . CLI::color((string) $this->gagal, $this->gagal > 0 ? 'red' : 'green'));

        return $this->gagal > 0 ? 1 : 0;
    }
}
