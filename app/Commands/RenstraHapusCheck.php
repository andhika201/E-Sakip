<?php

namespace App\Commands;

use App\Models\Opd\RenstraModel;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Throwable;

/**
 * Uji penghapusan RENSTRA satu periode.
 *
 *   php spark renstra:hapus-check --db <salinan>
 *
 * =====================================================================
 * MENGAPA INI YANG PALING PERLU DIJAGA
 *
 * Menghapus isi Renstra bukan sekadar meninggalkan rujukan menggantung.
 * Rantai foreign key di bawahnya CASCADE, dan panjang:
 *
 *   renstra_target
 *     -> target_rencana        (CASCADE)
 *          -> target_sub_rencana   (CASCADE)
 *          -> monev                (CASCADE)
 *          -> monev_anggaran       (CASCADE)
 *
 * Satu baris target Renstra yang terhapus bisa membawa serta seluruh Rencana
 * Aksi beserta sub, capaian MONEV, dan realisasi anggarannya. Basis data
 * mengerjakannya tanpa galat; tidak ada layar yang melaporkannya.
 *
 * Karena itu yang diuji di sini bukan "apakah penghapusannya berhasil",
 * melainkan "apakah penghapusannya DITOLAK saat seharusnya ditolak" — dan
 * apakah yang di luar periode itu benar-benar tidak tersentuh.
 *
 * =====================================================================
 * FIXTURE
 *
 * Seluruh baris uji memakai OPD pertama pada periode 2087-2091 — periode yang
 * tidak dipakai data mana pun — dan dibuang lagi di akhir, sukses maupun
 * gagal.
 */
class RenstraHapusCheck extends BaseCommand
{
    protected $group       = 'SAKIP';
    protected $name        = 'renstra:hapus-check';
    protected $description = 'Uji penghalang & penghapusan Renstra satu periode.';
    protected $usage       = 'renstra:hapus-check --db <nama>';
    protected $options     = ['--db' => 'basis data salinan (WAJIB, perintah ini menulis)'];

    private const TANDA = 'UJI-HAPUS-RENSTRA';
    private const TM    = 2087;
    private const TA    = 2091;

    private int $lulus = 0;
    private int $gagal = 0;

    private function cek(string $nama, bool $ok, string $detail = ''): void
    {
        if ($ok) {
            $this->lulus++;
            CLI::write('  ' . CLI::color('LULUS', 'green') . '  ' . $nama);
        } else {
            $this->gagal++;
            CLI::write('  ' . CLI::color('GAGAL', 'red') . '  ' . $nama
                . ($detail !== '' ? ' -> ' . $detail : ''));
        }
    }

    public function run(array $params)
    {
        $namaDb = trim((string) (CLI::getOption('db') ?: ''));

        if ($namaDb === '' || $namaDb === '1' || $namaDb === config('Database')->default['database']) {
            CLI::error('Wajib --db <salinan> yang BERBEDA dari basis data aplikasi.');

            return EXIT_ERROR;
        }

        $cfg             = config('Database')->default;
        $cfg['database'] = $namaDb;
        $db              = db_connect($cfg, false);

        CLI::write('Basis data: ' . $db->getDatabase(), 'yellow');

        $opdId = (int) ($db->table('opd')->select('id')->orderBy('id', 'ASC')
            ->get()->getRowArray()['id'] ?? 0);

        if ($opdId <= 0) {
            CLI::error('Tidak ada OPD untuk dijadikan fixture.');

            return EXIT_ERROR;
        }

        CLI::write('OPD uji   : #' . $opdId);
        CLI::newLine();

        $this->bersihkan($db, $opdId);

        try {
            $this->jalankan($db, $opdId);
        } catch (Throwable $e) {
            $this->gagal++;
            CLI::error('Galat tak terduga: ' . $e->getMessage());
            CLI::write('  ' . $e->getFile() . ':' . $e->getLine());
        } finally {
            $this->bersihkan($db, $opdId);
        }

        CLI::newLine();
        CLI::write('LULUS ' . $this->lulus . '   GAGAL ' . $this->gagal,
            $this->gagal === 0 ? 'green' : 'red');

        return $this->gagal === 0 ? EXIT_SUCCESS : EXIT_ERROR;
    }

    /** @return array{0:int,1:int,2:int} [tujuanId, sasaranId, indikatorId] */
    private function buatPeriode($db, int $opdId): array
    {
        $rpjmd = $db->table('rpjmd_sasaran')->select('id')->orderBy('id', 'ASC')
            ->get()->getRowArray();

        $db->table('renstra_tujuan')->insert([
            'tujuan'           => self::TANDA . ' Tujuan',
            'rpjmd_sasaran_id' => $rpjmd['id'] ?? null,
        ]);
        $tujuanId = (int) $db->insertID();

        $db->table('renstra_sasaran')->insert([
            'renstra_tujuan_id' => $tujuanId, 'opd_id' => $opdId,
            'sasaran'           => self::TANDA . ' Sasaran',
            'tahun_mulai'       => self::TM, 'tahun_akhir' => self::TA,
        ]);
        $sasaranId = (int) $db->insertID();

        $db->table('renstra_indikator_sasaran')->insert([
            'renstra_sasaran_id' => $sasaranId,
            'indikator_sasaran'  => self::TANDA . ' Indikator',
            'satuan'             => '%',
        ]);
        $indikatorId = (int) $db->insertID();

        $db->table('renstra_target')->insert([
            'renstra_indikator_id' => $indikatorId, 'tahun' => self::TM, 'target' => '10',
        ]);

        return [$tujuanId, $sasaranId, $indikatorId];
    }

    private function jalankan($db, int $opdId): void
    {
        $m = new RenstraModel($db);

        /* ---------- 1. periode bersih boleh dihapus ---------- */
        CLI::write('== Periode bersih ==', 'yellow');

        [$tujuanId, $sasaranId, $indikatorId] = $this->buatPeriode($db, $opdId);

        $isi = $m->isiPeriode($opdId, self::TM, self::TA);

        $this->cek('isi periode terhitung benar',
            $isi['sasaran'] === 1 && $isi['indikator'] === 1 && $isi['target'] === 1,
            json_encode($isi));
        $this->cek('tanpa perujuk: tidak ada penghalang',
            $m->penghalangHapusPeriode($opdId, self::TM, self::TA) === []);

        /* ---------- 2. Rencana Aksi menahan — rantai CASCADE ---------- */
        CLI::newLine();
        CLI::write('== Rencana Aksi menahan (rantai CASCADE) ==', 'yellow');

        $targetId = (int) ($db->table('renstra_target')->select('id')
            ->where('renstra_indikator_id', $indikatorId)->get()->getRowArray()['id'] ?? 0);

        $db->table('target_rencana')->insert([
            'rencana_aksi'      => self::TANDA . ' renaksi',
            'renstra_target_id' => $targetId,
            'created_at'        => date('Y-m-d H:i:s'),
            'updated_at'        => date('Y-m-d H:i:s'),
        ]);
        $renaksiId = (int) $db->insertID();

        $halang = $m->penghalangHapusPeriode($opdId, self::TM, self::TA);

        $this->cek('Rencana Aksi tercatat sebagai penghalang',
            isset($halang['Rencana Aksi (beserta sub, MONEV, & realisasi anggarannya)']),
            json_encode($halang, JSON_UNESCAPED_UNICODE));

        $ditolak = false;

        try {
            $m->hapusPeriode($opdId, self::TM, self::TA);
        } catch (Throwable $e) {
            $ditolak = str_contains($e->getMessage(), 'Masih dirujuk');
        }

        $this->cek('penghapusan DITOLAK', $ditolak);
        $this->cek('Rencana Aksi selamat',
            $db->table('target_rencana')->where('id', $renaksiId)->countAllResults() === 1);
        $this->cek('isi Renstra-nya juga selamat',
            $db->table('renstra_sasaran')->where('id', $sasaranId)->countAllResults() === 1);

        $db->table('target_rencana')->where('id', $renaksiId)->delete();

        /* ---------- 3. RKT menahan (tanpa foreign key) ---------- */
        CLI::newLine();
        CLI::write('== RKT menahan (tanpa foreign key) ==', 'yellow');

        // `rkt.program_id` ber-foreign key ke program_pk — dipinjam satu yang
        // ada, sama seperti fixture lain di suite ini.
        $programId = $db->table('program_pk')->select('id')->orderBy('id', 'ASC')
            ->get()->getRowArray()['id'] ?? null;

        $db->table('rkt')->insert([
            'opd_id' => $opdId, 'tahun' => self::TM, 'indikator_id' => $indikatorId,
            'program_id' => $programId,
            'status' => 'draft', 'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $rktId = (int) $db->insertID();

        $halang = $m->penghalangHapusPeriode($opdId, self::TM, self::TA);

        $this->cek('RKT tercatat sebagai penghalang',
            ($halang['baris RKT'] ?? 0) === 1, json_encode($halang, JSON_UNESCAPED_UNICODE));

        $db->table('rkt')->where('id', $rktId)->delete();

        /* ---------- 4. versi terbit BERISI menahan, yang kosong tidak ---------- */
        CLI::newLine();
        CLI::write('== Versi terbit: berisi menahan, kosong tidak ==', 'yellow');

        $db->table('dokumen_versi')->insert([
            'modul' => 'renstra', 'scope' => 'opd', 'opd_id' => $opdId,
            'periode_mulai' => self::TM, 'periode_akhir' => self::TA,
            'version_no' => 1, 'label' => self::TANDA . ' versi kosong',
            'effective_from' => self::TM . '-01-01', 'status' => 'published',
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        $versiKosong = (int) $db->insertID();

        $this->cek('versi terbit BERARSIP KOSONG tidak menahan',
            $m->penghalangHapusPeriode($opdId, self::TM, self::TA) === [],
            json_encode($m->penghalangHapusPeriode($opdId, self::TM, self::TA), JSON_UNESCAPED_UNICODE));

        $db->table('renstra_versi_tujuan')->insert([
            'version_id' => $versiKosong, 'tujuan' => self::TANDA . ' arsip',
        ]);

        $halang = $m->penghalangHapusPeriode($opdId, self::TM, self::TA);

        $this->cek('versi terbit BERISI arsip MENAHAN',
            isset($halang['versi Renstra yang sudah ditetapkan DAN berisi arsip']),
            json_encode($halang, JSON_UNESCAPED_UNICODE));

        $db->table('renstra_versi_tujuan')->where('version_id', $versiKosong)->delete();

        /* ---------- 5. penghapusan sungguhan ---------- */
        CLI::newLine();
        CLI::write('== Penghapusan sungguhan ==', 'yellow');

        $sasaranLain = $db->table('renstra_sasaran')->where('opd_id !=', $opdId)->countAllResults();
        $renaksiLain = $db->table('target_rencana')->countAllResults();

        $ringkas = $m->hapusPeriode($opdId, self::TM, self::TA);

        $this->cek('ringkasan menyebut yang terhapus',
            (int) $ringkas['sasaran'] === 1 && (int) $ringkas['indikator'] === 1,
            json_encode($ringkas));
        $this->cek('sasaran periode itu hilang',
            $db->table('renstra_sasaran')->where('id', $sasaranId)->countAllResults() === 0);
        $this->cek('indikatornya ikut hilang',
            $db->table('renstra_indikator_sasaran')->where('id', $indikatorId)->countAllResults() === 0);
        $this->cek('tujuannya ikut hilang (tidak dipakai periode lain)',
            $db->table('renstra_tujuan')->where('id', $tujuanId)->countAllResults() === 0);
        $this->cek('versi dokumennya ikut hilang',
            $db->table('dokumen_versi')->where('id', $versiKosong)->countAllResults() === 0);

        // Yang TIDAK boleh ikut tersentuh.
        $this->cek('Renstra OPD LAIN tidak tersentuh',
            $db->table('renstra_sasaran')->where('opd_id !=', $opdId)->countAllResults() === $sasaranLain,
            (string) $sasaranLain);
        $this->cek('Rencana Aksi lain tidak tersentuh',
            $db->table('target_rencana')->countAllResults() === $renaksiLain,
            (string) $renaksiLain);

        /* ---------- 6. tujuan yang MASIH dipakai periode lain ---------- */
        CLI::newLine();
        CLI::write('== Tujuan bersama tidak ikut terbuang ==', 'yellow');

        [$tujuanB, $sasaranB, ] = $this->buatPeriode($db, $opdId);

        // Sasaran kedua pada periode BERBEDA, menumpang tujuan yang sama.
        $db->table('renstra_sasaran')->insert([
            'renstra_tujuan_id' => $tujuanB, 'opd_id' => $opdId,
            'sasaran'           => self::TANDA . ' Sasaran periode lain',
            'tahun_mulai'       => 2092, 'tahun_akhir' => 2096,
        ]);
        $sasaranLainPeriode = (int) $db->insertID();

        $m->hapusPeriode($opdId, self::TM, self::TA);

        $this->cek('tujuan TETAP ADA karena masih dipakai periode lain',
            $db->table('renstra_tujuan')->where('id', $tujuanB)->countAllResults() === 1);
        $this->cek('sasaran periode lain tetap ada',
            $db->table('renstra_sasaran')->where('id', $sasaranLainPeriode)->countAllResults() === 1);
    }

    private function bersihkan($db, int $opdId): void
    {
        $sasaran = array_column($db->table('renstra_sasaran')->select('id')
            ->like('sasaran', self::TANDA, 'after')->get()->getResultArray(), 'id');

        if ($sasaran !== []) {
            $ind = array_column($db->table('renstra_indikator_sasaran')->select('id')
                ->whereIn('renstra_sasaran_id', $sasaran)->get()->getResultArray(), 'id');

            if ($ind !== []) {
                $tgt = array_column($db->table('renstra_target')->select('id')
                    ->whereIn('renstra_indikator_id', $ind)->get()->getResultArray(), 'id');

                if ($tgt !== []) {
                    $db->table('target_rencana')->whereIn('renstra_target_id', $tgt)->delete();
                }

                $db->table('rkt')->whereIn('indikator_id', $ind)->delete();
                $db->table('renstra_target')->whereIn('renstra_indikator_id', $ind)->delete();
                $db->table('renstra_indikator_sasaran')->whereIn('id', $ind)->delete();
            }

            $db->table('renstra_sasaran')->whereIn('id', $sasaran)->delete();
        }

        $versi = array_column($db->table('dokumen_versi')->select('id')
            ->like('label', self::TANDA, 'after')->get()->getResultArray(), 'id');

        if ($versi !== []) {
            $db->table('renstra_versi_tujuan')->whereIn('version_id', $versi)->delete();
            $db->table('renstra_versi_sasaran')->whereIn('version_id', $versi)->delete();
            $db->table('version_submission_history')->whereIn('version_id', $versi)->delete();
            $db->table('dokumen_versi')->whereIn('id', $versi)->delete();
        }

        $db->table('target_rencana')->like('rencana_aksi', self::TANDA, 'after')->delete();
        $db->table('renstra_tujuan')->like('tujuan', self::TANDA, 'after')->delete();
    }
}
