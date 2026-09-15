<?php

namespace App\Commands;

use App\Models\Opd\MonevModel;
use App\Services\AnggaranUnitService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Throwable;

/**
 * Uji unit anggaran yang dipakai bersama beberapa indikator (§56–§72).
 *
 *   php spark monev:shared-check --db <salinan>
 *
 * =====================================================================
 * ATURAN YANG DIJAGA
 *
 * Satu Program/Kegiatan/Sub Kegiatan boleh mendukung beberapa indikator PK.
 * Tiap indikator mengisi BAGIANnya sendiri, dan jumlah seluruh bagian pada
 * satu unit tidak boleh melampaui pagu unit itu.
 *
 * Empat hal yang paling mudah salah:
 *
 *   1. VALIDASI HANYA MELIHAT ANGKA YANG DIKIRIM. Bagian indikator lain pada
 *      unit yang sama harus ikut dihitung; tanpa itu, A=300jt lolos padahal
 *      B sudah mengisi 600jt dari pagu 800jt (§28).
 *
 *   2. BATASNYA DIGESER SATU RUPIAH. Total yang PERSIS sama dengan pagu itu
 *      sah; yang lebih satu rupiah tidak (§61, §62). Membandingkan float
 *      mentah bisa menolak yang sebenarnya tepat.
 *
 *   3. REALISASI DI-DEDUPE. Sesudah aturan ini berlaku, dua indikator pada
 *      satu program memang menjumlah. Men-DISTINCT-kannya justru salah —
 *      yang dihitung sekali adalah PAGUnya, bukan realisasinya (§32, §33).
 *
 *   4. NULL BERUBAH JADI 0. "Belum diisi" dan "diisi nol" berbeda arti dan
 *      tidak boleh disamakan hanya demi validasi pagu (§26, §64).
 *
 * =====================================================================
 * FIXTURE
 *
 * Memakai OPD pertama yang ada, seluruh baris bertanda UJI-SHARED pada tahun
 * 2093, dan dibuang lagi di akhir baik sukses maupun gagal.
 */
class MonevSharedCheck extends BaseCommand
{
    protected $group       = 'SAKIP';
    protected $name        = 'monev:shared-check';
    protected $description = 'Uji unit anggaran dipakai bersama: plafon pagu, sibling, dan agregasi.';
    protected $usage       = 'monev:shared-check --db <nama>';
    protected $options     = ['--db' => 'basis data salinan (WAJIB, perintah ini menulis)'];

    private const TANDA = 'UJI-SHARED';
    private const TAHUN = 2093;
    private const PAGU  = 1000000.0;

    private int $lulus = 0;
    private int $gagal = 0;

    /** @var array<string, list<int>> id fixture untuk dibersihkan */
    private array $jejak = [];

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

        if ($namaDb === '' || $namaDb === '1') {
            CLI::error('Wajib --db <salinan>. Perintah ini menulis ke basis data.');

            return EXIT_ERROR;
        }

        if ($namaDb === config('Database')->default['database']) {
            CLI::error('Salinan harus BERBEDA dari basis data aplikasi.');

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

        try {
            $ctx = $this->siapkan($db, $opdId);

            $this->ujiPetaShared($db, $ctx);
            $this->ujiPlafon($db, $ctx);
            $this->ujiSibling($db, $ctx);
            $this->ujiNullVsNol($db, $ctx);
            $this->ujiAgregasi($db, $ctx);
            $this->ujiAuditLebihPagu($db, $ctx);
            $this->ujiAtomik($db, $ctx);
        } catch (Throwable $e) {
            $this->gagal++;
            CLI::error('Galat tak terduga: ' . $e->getMessage());
            CLI::write('  ' . $e->getFile() . ':' . $e->getLine());
        } finally {
            $this->bersihkan($db);
        }

        CLI::newLine();
        CLI::write('LULUS ' . $this->lulus . '   GAGAL ' . $this->gagal,
            $this->gagal === 0 ? 'green' : 'red');

        return $this->gagal === 0 ? EXIT_SUCCESS : EXIT_ERROR;
    }

    /* ================= fixture ================= */

    private function catat(string $tabel, int $id): int
    {
        $this->jejak[$tabel][] = $id;

        return $id;
    }

    /**
     * Satu PK dengan 3 indikator: A & B berbagi satu program, C berbagi juga
     * tetapi TANPA rencana aksi (§13).
     *
     * @return array<string,mixed>
     */
    private function siapkan($db, int $opdId): array
    {
        $now = date('Y-m-d H:i:s');

        $db->table('program_pk')->insert([
            'kode_program'     => self::TANDA . '-P1',
            'program_kegiatan' => self::TANDA . ' Program Bersama',
            'anggaran'         => self::PAGU,
            'tahun_anggaran'   => self::TAHUN,
            'opd_id'           => $opdId,
            'created_at'       => $now,
        ]);
        $programId = $this->catat('program_pk', (int) $db->insertID());

        $db->table('pk')->insert([
            'opd_id' => $opdId, 'tahun' => self::TAHUN, 'jenis' => 'jpt',
            'tanggal' => self::TAHUN . '-01-02', 'created_at' => $now,
        ]);
        $pkId = $this->catat('pk', (int) $db->insertID());

        $db->table('pk_sasaran')->insert([
            'pk_id' => $pkId, 'sasaran' => self::TANDA . ' Sasaran', 'created_at' => $now,
        ]);
        $sasaranId = $this->catat('pk_sasaran', (int) $db->insertID());

        $ind = [];

        foreach (['A', 'B', 'C'] as $n) {
            $db->table('pk_indikator')->insert([
                'pk_sasaran_id' => $sasaranId,
                'indikator'     => self::TANDA . ' Indikator ' . $n,
                'target'        => '100',
                'created_at'    => $now,
            ]);
            $indId = $this->catat('pk_indikator', (int) $db->insertID());

            $db->table('pk_program')->insert([
                'program_id' => $programId, 'pk_indikator_id' => $indId, 'created_at' => $now,
            ]);
            $this->catat('pk_program', (int) $db->insertID());

            $trId = null;

            // Indikator C sengaja TIDAK diberi rencana aksi (§13).
            if ($n !== 'C') {
                $db->table('target_rencana')->insert([
                    'pk_indikator_id' => $indId, 'opd_id' => $opdId,
                    'rencana_aksi'    => self::TANDA . ' Renaksi ' . $n,
                    'created_at'      => $now, 'updated_at' => $now,
                ]);
                $trId = $this->catat('target_rencana', (int) $db->insertID());
            }

            $ind[$n] = ['pk_indikator_id' => $indId, 'target_rencana_id' => $trId];
        }

        return [
            'opd_id'     => $opdId,
            'program_id' => $programId,
            'ref_key'    => 'program:' . $programId,
            'ind'        => $ind,
        ];
    }

    private function bersihkan($db): void
    {
        $urut = ['monev_anggaran', 'target_rencana', 'pk_program', 'pk_indikator',
            'pk_sasaran', 'pk', 'program_pk'];

        // monev_anggaran tidak dicatat satu per satu; dibuang lewat renaksinya.
        if (! empty($this->jejak['target_rencana'])) {
            $db->table('monev_anggaran')
                ->whereIn('target_rencana_id', $this->jejak['target_rencana'])->delete();
        }

        foreach ($urut as $tabel) {
            if (empty($this->jejak[$tabel])) {
                continue;
            }

            $db->table($tabel)->whereIn('id', array_reverse($this->jejak[$tabel]))->delete();
        }
    }

    /** Simpan lewat jalur sungguhan; kembalikan pesan galat atau '' bila sukses. */
    private function simpan($db, array $ctx, string $indikator, array $tw): string
    {
        $target = $ctx['ind'][$indikator]['target_rencana_id'];

        try {
            (new MonevModel($db))->simpanAnggaranTervalidasi(
                [$target => [$ctx['ref_key'] => $tw]],
                (int) $ctx['opd_id'],
                self::TAHUN
            );

            return '';
        } catch (Throwable $e) {
            return $e->getMessage();
        }
    }

    /* ================= skenario ================= */

    private function ujiPetaShared($db, array $ctx): void
    {
        CLI::write('== Peta unit -> indikator (§8, §12, §13) ==', 'yellow');

        $svc  = new AnggaranUnitService($db);
        $peta = $svc->petaUnitIndikator([$ctx['ind']['A']['pk_indikator_id']]);
        $unit = $peta[$ctx['ref_key']] ?? null;

        $this->cek('unit ditemukan lewat indikatornya', $unit !== null, implode(',', array_keys($peta)));

        if ($unit === null) {
            return;
        }

        $this->cek('tercatat dipakai 3 indikator', (int) $unit['indikator_count'] === 3,
            (string) $unit['indikator_count']);
        $this->cek('ditandai shared', $unit['shared'] === true);
        $this->cek('pagunya terbaca dari master', (float) $unit['pagu'] === self::PAGU,
            (string) $unit['pagu']);

        $tanpaRenaksi = 0;

        foreach ($unit['indikator'] as $i) {
            if ($i['target_rencana_id'] === null) {
                $tanpaRenaksi++;
            }
        }

        $this->cek('indikator tanpa rencana aksi tetap terhitung sebagai pemakai',
            $tanpaRenaksi === 1, (string) $tanpaRenaksi);

        CLI::newLine();
    }

    private function ujiPlafon($db, array $ctx): void
    {
        CLI::write('== Plafon pagu (§24, §61, §62) ==', 'yellow');

        // Tepat pagu -> sah.
        $galat = $this->simpan($db, $ctx, 'A', [1 => '400000', 2 => '600000', 3 => null, 4 => null]);
        $this->cek('total TEPAT sama dengan pagu diterima', $galat === '', $galat);

        // Lebih 1 rupiah -> ditolak.
        $galat = $this->simpan($db, $ctx, 'A', [1 => '400000', 2 => '600001', 3 => null, 4 => null]);
        $this->cek('lebih 1 rupiah DITOLAK', $galat !== '');
        $this->cek('pesannya menyebut pagu & totalnya',
            str_contains($galat, 'melebihi pagunya') && str_contains($galat, 'Rp1.000.000'), $galat);

        // Nilai lama tidak boleh ikut tertimpa oleh percobaan yang gagal.
        $baris = $db->table('monev_anggaran')
            ->where('target_rencana_id', $ctx['ind']['A']['target_rencana_id'])
            ->where('ref_key', $ctx['ref_key'])->get()->getRowArray();

        $this->cek('percobaan yang ditolak tidak mengubah nilai tersimpan',
            (float) ($baris['realisasi_triwulan_2'] ?? -1) === 600000.0,
            (string) ($baris['realisasi_triwulan_2'] ?? '-'));

        CLI::newLine();
    }

    private function ujiSibling($db, array $ctx): void
    {
        CLI::write('== Bagian indikator lain ikut dihitung (§28) ==', 'yellow');

        // A sudah memakai 1.000.000 (seluruh pagu). B tidak boleh menambah.
        $galat = $this->simpan($db, $ctx, 'B', [1 => '1', 2 => null, 3 => null, 4 => null]);
        $this->cek('B ditolak karena A sudah memakai seluruh pagu', $galat !== '');
        $this->cek('pesannya menyebut bagian indikator lain',
            str_contains($galat, 'indikator lain'), $galat);

        // Turunkan A, lalu B boleh mengisi sisanya.
        $this->simpan($db, $ctx, 'A', [1 => '400000', 2 => null, 3 => null, 4 => null]);

        $galat = $this->simpan($db, $ctx, 'B', [1 => '600000', 2 => null, 3 => null, 4 => null]);
        $this->cek('sesudah A diturunkan, B boleh mengisi sisanya', $galat === '', $galat);

        $svc = new AnggaranUnitService($db);
        $total = $svc->totalTerpakai('program', (int) $ctx['program_id'], (int) $ctx['opd_id'], self::TAHUN);

        $this->cek('total lintas indikator = 1.000.000 (dijumlah, bukan di-dedupe)',
            (float) $total === 1000000.0, (string) $total);

        // §60: nilainya memang boleh berbeda antar indikator.
        $a = $db->table('monev_anggaran')->where('target_rencana_id', $ctx['ind']['A']['target_rencana_id'])
            ->where('ref_key', $ctx['ref_key'])->get()->getRowArray();
        $b = $db->table('monev_anggaran')->where('target_rencana_id', $ctx['ind']['B']['target_rencana_id'])
            ->where('ref_key', $ctx['ref_key'])->get()->getRowArray();

        $this->cek('nilai A & B tersimpan berbeda, tidak saling menimpa',
            (float) $a['realisasi_triwulan_1'] === 400000.0
            && (float) $b['realisasi_triwulan_1'] === 600000.0,
            ($a['realisasi_triwulan_1'] ?? '-') . ' vs ' . ($b['realisasi_triwulan_1'] ?? '-'));

        CLI::newLine();
    }

    private function ujiNullVsNol($db, array $ctx): void
    {
        CLI::write('== NULL vs 0 (§26, §64) ==', 'yellow');

        $this->simpan($db, $ctx, 'A', [1 => '0', 2 => null, 3 => null, 4 => null]);

        $baris = $db->table('monev_anggaran')
            ->where('target_rencana_id', $ctx['ind']['A']['target_rencana_id'])
            ->where('ref_key', $ctx['ref_key'])->get()->getRowArray();

        $this->cek('0 tersimpan sebagai 0', $baris['realisasi_triwulan_1'] !== null
            && (float) $baris['realisasi_triwulan_1'] === 0.0,
            var_export($baris['realisasi_triwulan_1'] ?? null, true));
        $this->cek('yang tidak diisi tetap NULL, bukan berubah jadi 0',
            $baris['realisasi_triwulan_2'] === null,
            var_export($baris['realisasi_triwulan_2'] ?? 'tidak ada', true));

        CLI::newLine();
    }

    private function ujiAgregasi($db, array $ctx): void
    {
        CLI::write('== Agregasi: pagu sekali, realisasi dijumlah (§32, §33) ==', 'yellow');

        $this->simpan($db, $ctx, 'A', [1 => '150000', 2 => null, 3 => null, 4 => null]);
        $this->simpan($db, $ctx, 'B', [1 => '75000', 2 => null, 3 => null, 4 => null]);

        $svc   = new AnggaranUnitService($db);
        $total = $svc->totalTerpakai('program', (int) $ctx['program_id'], (int) $ctx['opd_id'], self::TAHUN);

        $this->cek('realisasi 150rb + 75rb = 225rb', (float) $total === 225000.0, (string) $total);
        $this->cek('pagu tetap satu kali, bukan dikali jumlah indikator',
            (float) $svc->pagu('program', (int) $ctx['program_id']) === self::PAGU);

        CLI::newLine();
    }

    private function ujiAuditLebihPagu($db, array $ctx): void
    {
        CLI::write('== Audit unit melebihi pagu (§36, §65) ==', 'yellow');

        $svc = new AnggaranUnitService($db);

        $ada = static function (array $daftar, string $refKey): ?array {
            foreach ($daftar as $u) {
                if ($u['ref_key'] === $refKey) {
                    return $u;
                }
            }

            return null;
        };

        $this->cek('unit yang masih di bawah pagu TIDAK dilaporkan',
            $ada($svc->unitLebihPagu((int) $ctx['opd_id'], self::TAHUN), $ctx['ref_key']) === null);

        // Data existing yang melebihi pagu dibuat LANGSUNG ke basis data —
        // meniru data lama yang masuk sebelum validasi ada (§0, §65).
        $db->table('monev_anggaran')
            ->where('target_rencana_id', $ctx['ind']['A']['target_rencana_id'])
            ->where('ref_key', $ctx['ref_key'])
            ->update(['realisasi_triwulan_1' => 900000, 'realisasi_triwulan_2' => 900000]);

        $u = $ada($svc->unitLebihPagu((int) $ctx['opd_id'], self::TAHUN), $ctx['ref_key']);

        $this->cek('unit yang melebihi pagu terdeteksi', $u !== null);

        if ($u === null) {
            CLI::newLine();

            return;
        }

        $this->cek('total & selisihnya benar',
            (float) $u['total_realisasi'] === 1875000.0 && (float) $u['selisih'] === 875000.0,
            $u['total_realisasi'] . ' / ' . $u['selisih']);
        $this->cek('jumlah indikator pemakainya dilaporkan',
            (int) $u['jumlah_indikator'] === 2, (string) $u['jumlah_indikator']);
        $this->cek('data existing yang melebihi pagu TIDAK dihapus',
            $db->table('monev_anggaran')
                ->where('target_rencana_id', $ctx['ind']['A']['target_rencana_id'])
                ->where('ref_key', $ctx['ref_key'])->countAllResults() === 1);

        // §46: data yang sudah telanjur melebihi tetap bisa DIPERBAIKI lewat
        // form — penyimpanan yang menurunkannya ke bawah pagu harus diterima.
        $galat = $this->simpan($db, $ctx, 'A', [1 => '100000', 2 => null, 3 => null, 4 => null]);

        $this->cek('memperbaiki data over-budget lewat form DITERIMA', $galat === '', $galat);

        CLI::newLine();
    }

    private function ujiAtomik($db, array $ctx): void
    {
        CLI::write('== Atomik: satu gagal, tidak ada yang tersimpan (§29, §71) ==', 'yellow');

        $svc    = new AnggaranUnitService($db);
        $sebelum = $svc->totalTerpakai('program', (int) $ctx['program_id'], (int) $ctx['opd_id'], self::TAHUN);

        // Dua rencana aksi sekaligus: yang kedua membuat totalnya melampaui pagu.
        $galat = '';

        try {
            (new MonevModel($db))->simpanAnggaranTervalidasi([
                $ctx['ind']['A']['target_rencana_id'] => [$ctx['ref_key'] => [1 => '500000', 2 => null, 3 => null, 4 => null]],
                $ctx['ind']['B']['target_rencana_id'] => [$ctx['ref_key'] => [1 => '900000', 2 => null, 3 => null, 4 => null]],
            ], (int) $ctx['opd_id'], self::TAHUN);
        } catch (Throwable $e) {
            $galat = $e->getMessage();
        }

        $this->cek('kiriman dua indikator yang totalnya melebihi pagu DITOLAK', $galat !== '');

        $sesudah = $svc->totalTerpakai('program', (int) $ctx['program_id'], (int) $ctx['opd_id'], self::TAHUN);

        $this->cek('tidak ada satu pun yang tersimpan sebagian',
            (float) $sebelum === (float) $sesudah, $sebelum . ' -> ' . $sesudah);

        // Yang muat harus tetap bisa tersimpan sekaligus.
        $galat = '';

        try {
            (new MonevModel($db))->simpanAnggaranTervalidasi([
                $ctx['ind']['A']['target_rencana_id'] => [$ctx['ref_key'] => [1 => '400000', 2 => null, 3 => null, 4 => null]],
                $ctx['ind']['B']['target_rencana_id'] => [$ctx['ref_key'] => [1 => '600000', 2 => null, 3 => null, 4 => null]],
            ], (int) $ctx['opd_id'], self::TAHUN);
        } catch (Throwable $e) {
            $galat = $e->getMessage();
        }

        $this->cek('dua indikator sekaligus yang totalnya PAS diterima', $galat === '', $galat);
        $this->cek('keduanya benar-benar tersimpan',
            (float) $svc->totalTerpakai('program', (int) $ctx['program_id'],
                (int) $ctx['opd_id'], self::TAHUN) === 1000000.0);

        CLI::newLine();
    }
}
