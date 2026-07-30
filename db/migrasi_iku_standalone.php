<?php

/**
 * =====================================================================
 * MIGRASI DATA: IKU lama (nempel renstra/rpjmd) -> IKU standalone
 * Tanggal : 2026-07-27
 * =====================================================================
 *
 * Prasyarat : db/update_2026-07-27_iku_standalone.sql sudah dijalankan
 *             (tabel iku_sasaran / iku_indikator / iku_target / iku_program ada).
 *
 * Yang disalin — hanya indikator yang MEMANG sudah jadi IKU, yaitu yang punya
 * baris di tabel `iku`:
 *   * IKU OPD  (iku.renstra_id) : sasaran + periode + opd_id dari renstra_sasaran,
 *                                 indikator + satuan dari renstra_indikator_sasaran,
 *                                 target per tahun dari renstra_target.
 *   * IKU Kab  (iku.rpjmd_id)   : sasaran dari rpjmd_sasaran, periode dari rpjmd_misi,
 *                                 indikator + satuan dari rpjmd_indikator_sasaran,
 *                                 target per tahun dari rpjmd_target, opd_id = NULL.
 * Definisi / rumusan perhitungan / sumber data / penanggung jawab / status
 * diambil dari tabel `iku`, program pendukung dari `iku_program_pendukung`.
 *
 * Baris `iku` yang indikator sumbernya sudah terhapus (yatim) dilewati dan
 * dilaporkan — baris itu memang sudah tidak tampil di halaman IKU yang lama.
 *
 * Sifat : IDEMPOTEN — kalau tabel tujuan sudah terisi, script berhenti kecuali
 *         dijalankan dengan --force (yang MENGOSONGKAN tabel IKU standalone
 *         lebih dulu). Tabel `iku` & `iku_program_pendukung` tidak pernah
 *         disentuh, jadi migrasi ini selalu bisa diulang.
 *
 * Jalankan : php db/migrasi_iku_standalone.php
 *            php db/migrasi_iku_standalone.php --force
 */

// ---------------------------------------------------------------------
// Koneksi — dibaca dari .env supaya tidak perlu hardcode kredensial.
// ---------------------------------------------------------------------
$root = dirname(__DIR__);
$cfg  = ['hostname' => 'localhost', 'username' => 'root', 'password' => '', 'database' => 'test_sakip', 'port' => 3306];

if (is_file($root . '/.env')) {
    foreach (file($root . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#' || strpos($line, 'database.default.') !== 0) {
            continue;
        }
        [$key, $val] = array_map('trim', explode('=', $line, 2) + [1 => '']);
        $key = substr($key, strlen('database.default.'));
        if (array_key_exists($key, $cfg)) {
            $cfg[$key] = $val;
        }
    }
}

$db = @new mysqli($cfg['hostname'], $cfg['username'], $cfg['password'], $cfg['database'], (int) $cfg['port']);
if ($db->connect_error) {
    exit("Koneksi DB gagal: {$db->connect_error}\n");
}
$db->set_charset('utf8mb4');

$force = in_array('--force', $argv ?? [], true);

echo "=== MIGRASI IKU -> STANDALONE ({$cfg['database']}) ===\n";

// ---------------------------------------------------------------------
// Cek prasyarat & guard idempoten
// ---------------------------------------------------------------------
foreach (['iku_sasaran', 'iku_indikator', 'iku_target', 'iku_program'] as $t) {
    if ($db->query("SHOW TABLES LIKE '{$t}'")->num_rows === 0) {
        exit("Tabel `{$t}` belum ada. Jalankan dulu:\n  mysql -u root {$cfg['database']} < db/update_2026-07-27_iku_standalone.sql\n");
    }
}

$sudahAda = (int) $db->query('SELECT COUNT(*) c FROM iku_sasaran')->fetch_assoc()['c'];
if ($sudahAda > 0) {
    if (!$force) {
        exit("Tabel iku_sasaran sudah berisi {$sudahAda} baris. Migrasi dilewati.\n"
            . "Jalankan dengan --force kalau mau isi ulang dari nol (data IKU standalone yang ada akan dihapus).\n");
    }
    echo "--force: mengosongkan tabel IKU standalone lebih dulu...\n";
    $db->query('SET FOREIGN_KEY_CHECKS = 0');
    foreach (['iku_program', 'iku_target', 'iku_indikator', 'iku_sasaran'] as $t) {
        $db->query("TRUNCATE TABLE `{$t}`");
    }
    $db->query('SET FOREIGN_KEY_CHECKS = 1');
}

// ---------------------------------------------------------------------
// Statement yang dipakai berulang
// ---------------------------------------------------------------------
$insSasaran = $db->prepare(
    'INSERT INTO iku_sasaran (opd_id, sasaran, tahun_mulai, tahun_akhir, urutan) VALUES (?, ?, ?, ?, ?)'
);
$insIndikator = $db->prepare(
    'INSERT INTO iku_indikator
        (iku_sasaran_id, indikator, definisi, rumusan_perhitungan, satuan, sumber_data,
         penanggung_jawab, jenis_indikator, baseline, urutan, status)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
);
$insTarget = $db->prepare(
    'INSERT INTO iku_target (iku_indikator_id, tahun, target) VALUES (?, ?, ?)
     ON DUPLICATE KEY UPDATE target = VALUES(target)'
);
$insProgram = $db->prepare(
    'INSERT INTO iku_program (iku_indikator_id, program, urutan) VALUES (?, ?, ?)'
);

$stat = ['sasaran' => 0, 'indikator' => 0, 'target' => 0, 'program' => 0];

/**
 * Menyalin satu kelompok (sasaran + indikator-indikatornya) ke tabel standalone.
 *
 * @param array{opd_id: ?int, sasaran: string, tahun_mulai: int, tahun_akhir: int, urutan: int} $sasaran
 * @param array<int, array<string, mixed>> $indikators
 */
$simpanKelompok = static function (array $sasaran, array $indikators) use (
    $db, $insSasaran, $insIndikator, $insTarget, $insProgram, &$stat
): void {
    $insSasaran->bind_param(
        'isiii',
        $sasaran['opd_id'],
        $sasaran['sasaran'],
        $sasaran['tahun_mulai'],
        $sasaran['tahun_akhir'],
        $sasaran['urutan']
    );
    $insSasaran->execute();
    $sasaranId = $db->insert_id;
    $stat['sasaran']++;

    foreach (array_values($indikators) as $urut => $ind) {
        $insIndikator->bind_param(
            'issssssssis',
            $sasaranId,
            $ind['indikator'],
            $ind['definisi'],
            $ind['rumusan_perhitungan'],
            $ind['satuan'],
            $ind['sumber_data'],
            $ind['penanggung_jawab'],
            $ind['jenis_indikator'],
            $ind['baseline'],
            $urut,
            $ind['status']
        );
        $insIndikator->execute();
        $indikatorId = $db->insert_id;
        $stat['indikator']++;

        foreach ($ind['target'] as $tahun => $nilai) {
            $tahun = (int) $tahun;
            $insTarget->bind_param('iis', $indikatorId, $tahun, $nilai);
            $insTarget->execute();
            $stat['target']++;
        }

        foreach (array_values($ind['program']) as $urutProgram => $program) {
            $insProgram->bind_param('isi', $indikatorId, $program, $urutProgram);
            $insProgram->execute();
            $stat['program']++;
        }
    }
};

/** Normalkan status lama ('', 'belum', 'tercapai', dst) ke draft|selesai. */
$normalStatus = static function (?string $status): string {
    return strtolower(trim((string) $status)) === 'selesai' ? 'selesai' : 'draft';
};

/** Ambil program pendukung milik satu baris `iku`. */
$ambilProgram = static function (int $ikuId) use ($db): array {
    $res  = $db->query("SELECT program FROM iku_program_pendukung WHERE iku_id = {$ikuId} ORDER BY id ASC");
    $list = [];
    while ($row = $res->fetch_assoc()) {
        $program = trim((string) $row['program']);
        if ($program !== '') {
            $list[] = $program;
        }
    }
    return $list;
};

$db->begin_transaction();

try {
    // -----------------------------------------------------------------
    // 1. IKU TINGKAT OPD  (iku.renstra_id -> renstra_*)
    // -----------------------------------------------------------------
    $sql = "
        SELECT i.id AS iku_id, i.definisi, i.rumusan_perhitungan, i.sumber_data,
               i.penanggung_jawab, i.status,
               rs.id AS sasaran_id, rs.opd_id, rs.sasaran, rs.tahun_mulai, rs.tahun_akhir,
               ris.id AS indikator_id, ris.indikator_sasaran, ris.satuan,
               ris.jenis_indikator, ris.baseline
        FROM iku i
        JOIN renstra_indikator_sasaran ris ON ris.id = i.renstra_id
        JOIN renstra_sasaran rs            ON rs.id = ris.renstra_sasaran_id
        WHERE i.renstra_id IS NOT NULL
        ORDER BY rs.opd_id ASC, rs.id ASC, ris.id ASC, i.id ASC
    ";

    $kelompok = [];
    $res      = $db->query($sql);
    while ($row = $res->fetch_assoc()) {
        $key = 'renstra-' . $row['sasaran_id'];

        if (!isset($kelompok[$key])) {
            $kelompok[$key] = [
                'sasaran' => [
                    'opd_id'      => (int) $row['opd_id'],
                    'sasaran'     => (string) $row['sasaran'],
                    'tahun_mulai' => (int) $row['tahun_mulai'],
                    'tahun_akhir' => (int) $row['tahun_akhir'],
                    'urutan'      => count($kelompok),
                ],
                'indikator' => [],
            ];
        }

        // satu indikator cukup sekali walau punya lebih dari satu baris `iku`
        if (isset($kelompok[$key]['indikator'][$row['indikator_id']])) {
            continue;
        }

        $target = [];
        $resT   = $db->query(
            'SELECT tahun, target FROM renstra_target
             WHERE renstra_indikator_id = ' . (int) $row['indikator_id'] . ' ORDER BY tahun ASC'
        );
        while ($t = $resT->fetch_assoc()) {
            $target[(int) $t['tahun']] = $t['target'];
        }

        $kelompok[$key]['indikator'][$row['indikator_id']] = [
            'indikator'           => (string) $row['indikator_sasaran'],
            'definisi'            => $row['definisi'],
            'rumusan_perhitungan' => $row['rumusan_perhitungan'],
            'satuan'              => $row['satuan'],
            'sumber_data'         => $row['sumber_data'],
            'penanggung_jawab'    => $row['penanggung_jawab'],
            'jenis_indikator'     => $row['jenis_indikator'],
            'baseline'            => $row['baseline'],
            'status'              => $normalStatus($row['status']),
            'target'              => $target,
            'program'             => $ambilProgram((int) $row['iku_id']),
        ];
    }

    foreach ($kelompok as $k) {
        $simpanKelompok($k['sasaran'], $k['indikator']);
    }
    echo 'IKU OPD      : ' . count($kelompok) . " sasaran disalin\n";

    // -----------------------------------------------------------------
    // 2. IKU TINGKAT KABUPATEN  (iku.rpjmd_id -> rpjmd_*), opd_id = NULL
    // -----------------------------------------------------------------
    $sql = "
        SELECT i.id AS iku_id, i.definisi, i.rumusan_perhitungan, i.sumber_data,
               i.penanggung_jawab, i.status,
               rs.id AS sasaran_id, rs.sasaran_rpjmd AS sasaran,
               rm.tahun_mulai, rm.tahun_akhir,
               ris.id AS indikator_id, ris.indikator_sasaran, ris.satuan,
               ris.definisi_op, ris.jenis_indikator, ris.baseline
        FROM iku i
        JOIN rpjmd_indikator_sasaran ris ON ris.id = i.rpjmd_id
        JOIN rpjmd_sasaran rs            ON rs.id = ris.sasaran_id
        JOIN rpjmd_tujuan rt             ON rt.id = rs.tujuan_id
        JOIN rpjmd_misi rm               ON rm.id = rt.misi_id
        WHERE i.rpjmd_id IS NOT NULL
        ORDER BY rs.id ASC, ris.id ASC, i.id ASC
    ";

    $kelompokKab = [];
    $res         = $db->query($sql);
    while ($row = $res->fetch_assoc()) {
        $key = 'rpjmd-' . $row['sasaran_id'];

        if (!isset($kelompokKab[$key])) {
            $kelompokKab[$key] = [
                'sasaran' => [
                    'opd_id'      => null, // IKU tingkat kabupaten
                    'sasaran'     => (string) $row['sasaran'],
                    'tahun_mulai' => (int) $row['tahun_mulai'],
                    'tahun_akhir' => (int) $row['tahun_akhir'],
                    'urutan'      => count($kelompokKab),
                ],
                'indikator' => [],
            ];
        }

        if (isset($kelompokKab[$key]['indikator'][$row['indikator_id']])) {
            continue;
        }

        $target = [];
        $resT   = $db->query(
            'SELECT tahun, target_tahunan FROM rpjmd_target
             WHERE indikator_sasaran_id = ' . (int) $row['indikator_id'] . ' ORDER BY tahun ASC'
        );
        while ($t = $resT->fetch_assoc()) {
            $target[(int) $t['tahun']] = $t['target_tahunan'];
        }

        // definisi IKU diprioritaskan; kalau kosong pakai definisi_op RPJMD
        $definisi = trim((string) ($row['definisi'] ?? ''));
        if ($definisi === '') {
            $definisi = $row['definisi_op'];
        }

        $kelompokKab[$key]['indikator'][$row['indikator_id']] = [
            'indikator'           => (string) $row['indikator_sasaran'],
            'definisi'            => $definisi,
            'rumusan_perhitungan' => $row['rumusan_perhitungan'],
            'satuan'              => $row['satuan'],
            'sumber_data'         => $row['sumber_data'],
            'penanggung_jawab'    => $row['penanggung_jawab'],
            'jenis_indikator'     => $row['jenis_indikator'],
            'baseline'            => $row['baseline'],
            'status'              => $normalStatus($row['status']),
            'target'              => $target,
            'program'             => $ambilProgram((int) $row['iku_id']),
        ];
    }

    foreach ($kelompokKab as $k) {
        $simpanKelompok($k['sasaran'], $k['indikator']);
    }
    echo 'IKU Kabupaten: ' . count($kelompokKab) . " sasaran disalin\n";

    $db->commit();
} catch (Throwable $e) {
    $db->rollback();
    exit("\nGAGAL, semua perubahan dibatalkan: {$e->getMessage()}\n");
}

// ---------------------------------------------------------------------
// Laporan
// ---------------------------------------------------------------------
echo "\n--- HASIL ---\n";
echo "iku_sasaran   : {$stat['sasaran']} baris\n";
echo "iku_indikator : {$stat['indikator']} baris\n";
echo "iku_target    : {$stat['target']} baris\n";
echo "iku_program   : {$stat['program']} baris\n";

$yatim = $db->query("
    SELECT
      (SELECT COUNT(*) FROM iku i
        LEFT JOIN renstra_indikator_sasaran ris ON ris.id = i.renstra_id
       WHERE i.renstra_id IS NOT NULL AND ris.id IS NULL) AS opd,
      (SELECT COUNT(*) FROM iku i
        LEFT JOIN rpjmd_indikator_sasaran ris ON ris.id = i.rpjmd_id
       WHERE i.rpjmd_id IS NOT NULL AND ris.id IS NULL) AS kab
")->fetch_assoc();

$totalYatim = (int) $yatim['opd'] + (int) $yatim['kab'];
if ($totalYatim > 0) {
    echo "\nDILEWATI: {$totalYatim} baris `iku` yatim ({$yatim['opd']} OPD, {$yatim['kab']} kabupaten) —\n";
    echo "indikator renstra/rpjmd sumbernya sudah terhapus, jadi sasaran/indikator/targetnya\n";
    echo "tidak bisa direkonstruksi. Baris itu juga sudah tidak tampil di halaman IKU lama.\n";
    echo "Datanya tetap utuh di tabel `iku` kalau sewaktu-waktu perlu dicek manual.\n";
}

echo "\nSelesai. Tabel `iku` & `iku_program_pendukung` lama tidak diubah.\n";

$db->close();
