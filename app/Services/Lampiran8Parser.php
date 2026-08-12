<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Pembaca Lampiran 8 APBD (format SIPD) — MURNI BACA, tidak menyentuh DB.
 *
 * Peta kolom: A = kode perangkat daerah/unit, B = urusan, C = bidang urusan,
 * D = program, E = kegiatan, F = sub kegiatan, G = uraian,
 * K = anggaran Rancangan APBD.
 *
 * Level baris ditentukan dari kolom D/E/F pada baris itu sendiri:
 *   D terisi, E & F kosong  -> Program
 *   D & E terisi, F kosong  -> Kegiatan
 *   D, E & F terisi         -> Sub Kegiatan
 *   D kosong                -> baris OPD/urusan/bidang urusan/total (dilewati)
 *
 * Kode hirarki dibentuk dari jalur SIPD lengkap (A+B+C+D, +E, +F). Tanpa B
 * dan C, "1.1.2" (Pengelolaan Pendidikan) dan "2.22.2" (Pengembangan
 * Kebudayaan) menghasilkan kode yang sama sehingga anggaran program saling
 * menimpa dan kegiatan masuk ke program yang salah.
 *
 * Hasilnya dikelompokkan PER UNIT supaya pemanggil bisa memilih: seluruh unit
 * masuk ke satu OPD (mode per OPD), atau tiap unit dipetakan ke OPD-nya
 * sendiri (mode seluruh OPD).
 */
class Lampiran8Parser
{
    /**
     * Lebar baku tiap ruas kode SIPD.
     *
     * SIPD mencetak sebagian sel sebagai angka (1, 2, 3) dan sebagian lagi
     * sebagai teks (01, 02, 0003) untuk kode yang sama persis. Tanpa
     * normalisasi, "02" dan "2" menghasilkan kode berbeda sehingga re-import
     * file yang sama membuat data ganda.
     *
     * @var array<string, int[]> per segmen kode (dipisah titik)
     */
    public const KODE_WIDTH = [
        'B' => [1],     // urusan
        'C' => [2],     // bidang urusan
        'D' => [2],     // program
        'E' => [1, 2],  // kegiatan, mis. "2.01"
        'F' => [4],     // sub kegiatan
    ];

    /**
     * Uraian yang TIDAK boleh dianggap nama unit walau pola barisnya mirip
     * (kolom A kode, B-F kosong, G terisi).
     */
    private const BUKAN_UNIT = [
        'URAIAN', 'KODE', 'TOTAL', 'JUMLAH', 'JUMLAH TOTAL',
        'SURPLUS', 'DEFISIT', 'PEMBIAYAAN',
    ];

    /**
     * Baca satu sheet menjadi daftar unit + entitasnya.
     *
     * @return array{units: array<int, array<string, mixed>>, peringatan: string[]}
     */
    public function parse(Worksheet $sheet): array
    {
        $rows = $sheet->toArray(null, true, true, true);

        $units       = [];
        $unitAktif   = null;   // indeks unit yang sedang berjalan pada $units
        $peringatan  = [];

        // Konteks yang boleh diwarisi baris di bawahnya. Hanya A/B/C, karena
        // ketiganya murni penanda posisi; D/E/F justru penentu level baris
        // (mewarisi D membuat baris urusan ikut terbaca sebagai program).
        $ctxUnit   = '';
        $ctxUrusan = '';
        $ctxBidang = '';

        // Parent aktif, selalu diturunkan dari kode baris yang diproses.
        $currentProgramKode  = null;
        $currentKegiatanKode = null;
        $currentKegiatanE    = '';

        foreach ($rows as $rowNo => $r) {
            if (!is_int($rowNo)) {
                continue;
            }

            $A = $this->cleanUraian($r['A'] ?? '');
            $G = $this->cleanUraian($r['G'] ?? '');

            $rawB = $this->cleanUraian($r['B'] ?? '');
            $rawC = $this->cleanUraian($r['C'] ?? '');
            $rawD = $this->cleanUraian($r['D'] ?? '');
            $rawE = $this->cleanUraian($r['E'] ?? '');
            $rawF = $this->cleanUraian($r['F'] ?? '');

            // ---------------------------------------------------------
            // HEADER UNIT: kolom A kode SIPD, B-F kosong, G nama unit.
            // Tidak cukup mengandalkan "B-F kosong" saja — pola kode dan
            // uraiannya ikut divalidasi supaya baris judul, TOTAL, dan blok
            // tanda tangan tidak salah dikenali sebagai unit.
            // ---------------------------------------------------------
            $bfKosong = ($rawB === '' && $rawC === '' && $rawD === '' && $rawE === '' && $rawF === '');
            if ($bfKosong && $this->kodeUnitValid($A) && $this->namaUnitValid($G)) {
                $units[] = [
                    'kode_unit'       => $A,
                    'nama_unit'       => $G,
                    'baris_excel'     => $rowNo,
                    'entities'        => [],
                    'jumlah_program'  => 0,
                    'jumlah_kegiatan' => 0,
                    'jumlah_sub'      => 0,
                    'total_anggaran'  => 0.0,
                ];
                $unitAktif = array_key_last($units);

                // Ganti unit -> seluruh konteks di bawahnya tidak berlaku lagi.
                $ctxUnit             = $A;
                $ctxUrusan           = '';
                $ctxBidang           = '';
                $currentProgramKode  = null;
                $currentKegiatanKode = null;
                $currentKegiatanE    = '';
                continue;
            }

            // Kolom A tetap dipakai sebagai konteks kode (fill-down) untuk
            // file yang tidak mencantumkan baris header unit sama sekali.
            if ($A !== '' && $this->kodeUnitValid($A) && $A !== $ctxUnit) {
                $ctxUnit             = $A;
                $ctxUrusan           = '';
                $ctxBidang           = '';
                $currentProgramKode  = null;
                $currentKegiatanKode = null;
                $currentKegiatanE    = '';
            }

            $nB = $this->normalizeKodeRuas($rawB, self::KODE_WIDTH['B']);
            $nC = $this->normalizeKodeRuas($rawC, self::KODE_WIDTH['C']);
            $nD = $this->normalizeKodeRuas($rawD, self::KODE_WIDTH['D']);
            $nE = $this->normalizeKodeRuas($rawE, self::KODE_WIDTH['E']);
            $nF = $this->normalizeKodeRuas($rawF, self::KODE_WIDTH['F']);

            // Urusan berganti -> bidang urusan di bawahnya tidak berlaku lagi.
            if ($nB !== '') {
                if ($nB !== $ctxUrusan) {
                    $ctxUrusan = $nB;
                    $ctxBidang = '';
                }
            } else {
                $nB = $ctxUrusan;
            }

            if ($nC !== '') {
                $ctxBidang = $nC;
            } else {
                $nC = $ctxBidang;
            }

            // Baris urusan / bidang urusan / TOTAL / tanda tangan.
            if ($nD === '' || $nD === '00') {
                $currentProgramKode  = null;
                $currentKegiatanKode = null;
                $currentKegiatanE    = '';
                continue;
            }

            if ($G === '') {
                continue;
            }

            if ($unitAktif === null) {
                // Entitas sebelum header unit mana pun: tidak boleh ditebak
                // pemiliknya, jadi dicatat sebagai peringatan dan dilewati.
                $peringatan[] = 'Baris ' . $rowNo . ' dilewati: belum ada header unit di atasnya.';
                continue;
            }

            $kodeProgram = ($ctxUnit !== '' ? $ctxUnit : '0')
                . '.' . ($nB !== '' ? $nB : '0')
                . '.' . ($nC !== '' ? $nC : '00')
                . '.' . $nD;

            // Sub kegiatan yang kolom E-nya dikosongkan (gaya fill-down)
            // mewarisi kegiatan terakhir pada program yang sama.
            if ($nE === '' && $nF !== '' && $currentKegiatanE !== '' && $currentProgramKode === $kodeProgram) {
                $nE = $currentKegiatanE;
            }

            // Anggaran wajib dari kolom K baris entitas ini sendiri.
            // null = sel benar-benar kosong (bukan 0).
            $anggaran    = $this->parseAnggaranCell($sheet, $rowNo);
            $nomenklatur = $nB . '.' . $nC . '.' . $nD;

            if ($nE === '') {
                $units[$unitAktif]['entities'][] = [
                    'level'        => 'program',
                    'kode_program' => $kodeProgram,
                    'kode_kegiatan' => null,
                    'kode_sub'     => null,
                    'nomenklatur'  => $nomenklatur,
                    'uraian'       => $G,
                    'anggaran'     => $anggaran,
                    'punya_baris'  => true,
                    'baris_excel'  => $rowNo,
                ];
                $currentProgramKode  = $kodeProgram;
                $currentKegiatanKode = null;
                $currentKegiatanE    = '';
                continue;
            }

            $kodeKegiatan = $kodeProgram . '.' . $nE;

            if ($nF === '') {
                $units[$unitAktif]['entities'][] = [
                    'level'         => 'kegiatan',
                    'kode_program'  => $kodeProgram,
                    'kode_kegiatan' => $kodeKegiatan,
                    'kode_sub'      => null,
                    'nomenklatur'   => $nomenklatur . '.' . $nE,
                    'uraian'        => $G,
                    'anggaran'      => $anggaran,
                    'punya_baris'   => true,
                    'baris_excel'   => $rowNo,
                ];
                $currentProgramKode  = $kodeProgram;
                $currentKegiatanKode = $kodeKegiatan;
                $currentKegiatanE    = $nE;
                continue;
            }

            $units[$unitAktif]['entities'][] = [
                'level'         => 'sub',
                'kode_program'  => $kodeProgram,
                'kode_kegiatan' => $kodeKegiatan,
                'kode_sub'      => $kodeKegiatan . '.' . $nF,
                'nomenklatur'   => $nomenklatur . '.' . $nE,
                'uraian'        => $G,
                'anggaran'      => $anggaran,
                'punya_baris'   => true,
                'baris_excel'   => $rowNo,
            ];
            $currentProgramKode  = $kodeProgram;
            $currentKegiatanKode = $kodeKegiatan;
        }

        // Ringkasan per unit + induk yang tidak punya barisnya sendiri.
        foreach ($units as $i => $unit) {
            $units[$i] = $this->lengkapiUnit($unit);
        }

        // Unit tanpa satu pun entitas (mis. blok kosong) dibuang.
        $units = array_values(array_filter($units, static fn ($u) => !empty($u['entities'])));

        return ['units' => $units, 'peringatan' => $peringatan];
    }

    /**
     * Tambahkan induk yang tidak punya baris sendiri di Excel (mis. Puskesmas
     * dan Kelurahan yang langsung mencantumkan kegiatan/sub kegiatan) lalu
     * hitung ringkasannya.
     *
     * @param array<string, mixed> $unit
     *
     * @return array<string, mixed>
     */
    private function lengkapiUnit(array $unit): array
    {
        $programAda   = [];
        $kegiatanAda  = [];
        $namaProgram  = [];
        $namaKegiatan = [];

        foreach ($unit['entities'] as $e) {
            if ($e['level'] === 'program') {
                $programAda[$e['kode_program']] = true;
                $namaProgram[$e['nomenklatur']] = $e['uraian'];
            } elseif ($e['level'] === 'kegiatan') {
                $kegiatanAda[$e['kode_kegiatan']] = true;
                $namaKegiatan[$e['nomenklatur']]  = $e['uraian'];
            }
        }

        $tambahan = [];
        $lihatP   = [];
        $lihatK   = [];

        foreach ($unit['entities'] as $e) {
            $kp = $e['kode_program'];
            if (!isset($programAda[$kp]) && !isset($lihatP[$kp])) {
                $lihatP[$kp] = true;
                $nom = $this->nomenklaturProgram($e['nomenklatur']);
                $tambahan[] = [
                    'level'         => 'program',
                    'kode_program'  => $kp,
                    'kode_kegiatan' => null,
                    'kode_sub'      => null,
                    'nomenklatur'   => $nom,
                    // Nama program identik di semua unit; diambil dari unit lain
                    // saat finalisasi kalau di unit ini tidak ada barisnya.
                    'uraian'        => $namaProgram[$nom] ?? ('PROGRAM ' . $nom),
                    'anggaran'      => null,
                    'punya_baris'   => false,
                    'baris_excel'   => $e['baris_excel'],
                ];
            }

            $kk = $e['kode_kegiatan'];
            if ($kk !== null && !isset($kegiatanAda[$kk]) && !isset($lihatK[$kk])) {
                $lihatK[$kk] = true;
                $tambahan[] = [
                    'level'         => 'kegiatan',
                    'kode_program'  => $kp,
                    'kode_kegiatan' => $kk,
                    'kode_sub'      => null,
                    'nomenklatur'   => $e['nomenklatur'],
                    'uraian'        => $namaKegiatan[$e['nomenklatur']] ?? ('Kegiatan ' . $e['nomenklatur']),
                    'anggaran'      => null,
                    'punya_baris'   => false,
                    'baris_excel'   => $e['baris_excel'],
                ];
            }
        }

        if ($tambahan) {
            $unit['entities'] = array_merge($tambahan, $unit['entities']);
        }

        // Urutkan supaya induk SELALU mendahului anaknya saat ditulis ke DB.
        //
        // Dibandingkan sebagai tuple, bukan string gabungan: kalau ruasnya
        // disambung jadi satu string, Program (kode_kegiatan kosong) justru
        // jatuh SESUDAH kegiatannya karena pemisah "|" bernilai lebih besar
        // dari digit — akibatnya seluruh kegiatan tertolak karena induknya
        // belum ada.
        $kunci = static fn (array $e): array => [
            $e['kode_program'],
            $e['level'] === 'program' ? 0 : 1,
            (string) ($e['kode_kegiatan'] ?? ''),
            $e['level'] === 'sub' ? 1 : 0,
            (string) ($e['kode_sub'] ?? ''),
        ];
        usort($unit['entities'], static fn ($a, $b) => $kunci($a) <=> $kunci($b));

        $jml = ['program' => 0, 'kegiatan' => 0, 'sub' => 0];
        $totalProgram = 0.0;
        foreach ($unit['entities'] as $e) {
            $jml[$e['level']]++;
            if ($e['level'] === 'program' && $e['anggaran'] !== null) {
                $totalProgram += (float) $e['anggaran'];
            }
        }

        $unit['jumlah_program']  = $jml['program'];
        $unit['jumlah_kegiatan'] = $jml['kegiatan'];
        $unit['jumlah_sub']      = $jml['sub'];
        // Total unit = jumlah pagu seluruh Program-nya (level teratas).
        $unit['total_anggaran']  = $totalProgram;

        return $unit;
    }

    /** "1.01.02.2.01" -> "1.01.02" (nomenklatur tingkat program). */
    private function nomenklaturProgram(string $nomenklatur): string
    {
        $bagian = explode('.', $nomenklatur);

        return implode('.', array_slice($bagian, 0, 3));
    }

    /**
     * Kode unit SIPD, mis. "1.01.2.22.0.00.01.0000".
     * Minimal 4 ruas angka supaya "1", "2.01", atau teks bebas tidak lolos.
     */
    public function kodeUnitValid(string $kode): bool
    {
        return preg_match('/^\d{1,3}(\.\d{1,4}){3,}$/', $kode) === 1;
    }

    /** Uraian layak jadi nama unit (bukan judul kolom / TOTAL / tanda tangan). */
    public function namaUnitValid(string $nama): bool
    {
        $nama = trim($nama);
        if (mb_strlen($nama) < 4 || mb_strlen($nama) > 200) {
            return false;
        }
        if (in_array(mb_strtoupper($nama), self::BUKAN_UNIT, true)) {
            return false;
        }
        // Blok tanda tangan / catatan cetak SIPD.
        if (preg_match('/(SIPD-RI|dicetak pada|Halaman\s+\d+|^\d{1,2}\s)/i', $nama) === 1) {
            return false;
        }

        // Harus memuat huruf — bukan sekadar angka/tanda baca.
        return preg_match('/\p{L}/u', $nama) === 1;
    }

    /**
     * Normalisasi satu ruas kode SIPD ke bentuk baku.
     *
     * Tiap segmen (dipisah titik) dibuang nol depannya lalu dikembalikan ke
     * lebar baku, sehingga "1"/"01", "2"/"02", dan "3"/"0003" selalu
     * menghasilkan nilai yang sama. Bentuk berlebar tetap ini juga membuat
     * pengurutan string pada kolom kode tetap urut secara numerik.
     *
     * @param int[] $widths lebar per segmen; segmen berikutnya memakai lebar terakhir
     *
     * @return string '' bila sel kosong atau bukan kode angka
     */
    public function normalizeKodeRuas($value, array $widths): string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }

        $fallbackWidth = $widths ? (int) $widths[array_key_last($widths)] : 1;
        $segments      = [];

        foreach (explode('.', $value) as $index => $segment) {
            $segment = trim($segment);

            // Bukan kode angka — mis. baris header ("PROGRAM"), "TOTAL", atau
            // blok tanda tangan. Dianggap tidak berkode supaya baris itu tidak
            // ikut terbaca sebagai Program/Kegiatan/Sub Kegiatan.
            if ($segment === '' || preg_match('/^\d+$/', $segment) !== 1) {
                return '';
            }

            $digits = ltrim($segment, '0');
            if ($digits === '') {
                $digits = '0';
            }

            $segments[] = str_pad($digits, $widths[$index] ?? $fallbackWidth, '0', STR_PAD_LEFT);
        }

        return implode('.', $segments);
    }

    /**
     * Rapikan uraian dari Excel: sel SIPD kerap memuat baris baru dan spasi
     * ganda hasil pembungkusan teks.
     */
    public function cleanUraian($value): string
    {
        $value = (string) $value;
        $clean = preg_replace('/\s+/u', ' ', $value);

        return trim($clean ?? $value);
    }

    /**
     * Baca anggaran (kolom K) pada satu baris.
     *
     * @return int|null null bila sel kosong, agar nilai lama tidak tertimpa 0
     */
    public function parseAnggaranCell(Worksheet $sheet, int $row): ?int
    {
        $cell = $sheet->getCell('K' . $row);
        $raw  = $cell->getValue();

        if (is_int($raw) || is_float($raw)) {
            return (int) round((float) $raw);
        }

        $text = trim((string) $raw);
        if ($text === '') {
            $text = trim((string) $cell->getFormattedValue());
        }
        if ($text === '' || $text === '-') {
            return null;
        }

        $negatif = str_starts_with($text, '(') || str_starts_with($text, '-');
        $text    = preg_replace('/[^0-9.,]/', '', $text);
        if ($text === '') {
            return null;
        }

        // Buang pecahan. SIPD mencetak "89,920,779,177.000000000" maupun
        // "190,754,000.00"; format Indonesia memakai "190.754.000,00".
        $dot   = strrpos($text, '.');
        $comma = strrpos($text, ',');
        $sep   = max($dot === false ? -1 : $dot, $comma === false ? -1 : $comma);

        if ($sep >= 0) {
            $tail = substr($text, $sep + 1);
            // Ruas terakhir tepat 3 digit = kelompok ribuan, bukan pecahan.
            if (preg_match('/^\d{3}$/', $tail) !== 1) {
                $text = substr($text, 0, $sep);
            }
        }

        $digits = preg_replace('/\D/', '', $text);
        if ($digits === '') {
            return 0;
        }

        return $negatif ? -(int) $digits : (int) $digits;
    }
}
