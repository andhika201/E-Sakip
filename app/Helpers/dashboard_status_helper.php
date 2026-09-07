<?php

/**
 * Status & validitas capaian untuk Dashboard Perangkat Daerah.
 *
 * Dua tanggung jawab:
 *  1. STATUS WARNA — getAchievementStatus() menerjemahkan persentase menjadi
 *     status (Kritis … Melampaui Target) memakai ambang dari tabel
 *     `dashboard_status_thresholds` yang dikelola Super Admin. Rentangnya
 *     TIDAK BOLEH di-hardcode di controller/model/view/JavaScript.
 *  2. VALIDITAS — dash_row_validity() memutuskan apakah sebuah baris ukur
 *     (rencana aksi / sub rencana aksi) menghasilkan persentase yang sah,
 *     lengkap dengan ALASAN yang bisa ditampilkan ke pengguna.
 *
 * Rumus persentasenya sendiri tetap milik app/Helpers/capaian_helper.php —
 * helper ini hanya membungkusnya agar kegagalan punya kode alasan.
 */

helper('capaian');

if (!function_exists('dash_threshold_rows')) {
    /**
     * Ambang aktif (terurut naik), di-cache per request.
     *
     * @return array<int, array<string, mixed>>
     */
    function dash_threshold_rows(bool $segarkan = false): array
    {
        static $cache = null;
        if ($segarkan) {
            $cache = null;
        }
        if ($cache !== null) {
            return $cache;
        }

        try {
            return $cache = (new \App\Models\DashboardThresholdModel())->aktif();
        } catch (\Throwable $e) {
            log_message('error', 'dash_threshold_rows gagal: ' . $e->getMessage());

            $out = [];
            foreach (\App\Models\DashboardThresholdModel::DEFAULTS as $d) {
                $out[] = $d + ['is_active' => 1];
            }
            return $cache = $out;
        }
    }
}

if (!function_exists('dash_color')) {
    /**
     * Slug warna -> daftar nilai siap pakai (hex untuk chart, kelas Bootstrap
     * untuk badge). Slug tak dikenal jatuh ke abu-abu — tidak pernah menerima
     * CSS bebas dari database.
     *
     * @return array{slug: string, label: string, hex: string, soft: string, bs: string}
     */
    function dash_color(?string $slug): array
    {
        $palet = \App\Models\DashboardThresholdModel::COLORS;
        $slug  = (string) $slug;
        $warna = $palet[$slug] ?? $palet['abu'];

        return ['slug' => isset($palet[$slug]) ? $slug : 'abu'] + $warna;
    }
}

if (!function_exists('getAchievementStatus')) {
    /**
     * Status capaian dari sebuah persentase.
     *
     * @return array{code: string, name: string, color: string, color_hex: string,
     *               color_soft: string, bs: string, icon: string|null, numeric: bool}
     */
    function getAchievementStatus(float $percentage): array
    {
        if (!is_finite($percentage)) {
            return dash_status_nonnumeric('belum_valid');
        }

        $baris = dash_threshold_rows();

        foreach ($baris as $t) {
            $min = $t['min_value'] === null ? -INF : (float) $t['min_value'];
            $max = $t['max_value'] === null ? INF  : (float) $t['max_value'];
            if ($percentage >= $min && $percentage <= $max) {
                return dash_status_from_row($t);
            }
        }

        // Di luar semua rentang (mis. persentase negatif saat rentang terendah
        // dimulai dari 0): pakai rentang terdekat, jangan mengarang status baru.
        if ($baris !== []) {
            $pertama = $baris[0];
            $terakhir = $baris[count($baris) - 1];
            $minPertama = $pertama['min_value'] === null ? -INF : (float) $pertama['min_value'];

            return dash_status_from_row($percentage < $minPertama ? $pertama : $terakhir);
        }

        return dash_status_nonnumeric('belum_valid');
    }
}

if (!function_exists('dash_status_from_row')) {
    /**
     * @param array<string, mixed> $t
     *
     * @return array<string, mixed>
     */
    function dash_status_from_row(array $t): array
    {
        $warna = dash_color($t['color'] ?? null);

        return [
            'code'       => (string) $t['code'],
            'name'       => (string) $t['name'],
            'color'      => $warna['slug'],
            'color_hex'  => $warna['hex'],
            'color_soft' => $warna['soft'],
            'bs'         => $warna['bs'],
            'icon'       => ($t['icon'] ?? '') !== '' ? (string) $t['icon'] : null,
            'numeric'    => true,
        ];
    }
}

if (!function_exists('dash_status_nonnumeric')) {
    /**
     * Status yang BUKAN rentang angka. Sengaja tidak disimpan di tabel ambang
     * (tabel itu khusus rentang persentase), tapi tetap didefinisikan di satu
     * tempat supaya konsisten dipakai kartu, grafik, dan drawer.
     *
     * @return array<string, mixed>
     */
    function dash_status_nonnumeric(string $code): array
    {
        $peta = [
            'belum_valid'         => ['name' => 'Belum Valid',         'color' => 'abu',    'icon' => 'fa-circle-question'],
            // Data LENGKAP, tetapi belum ada pembagi untuk menilainya. Sengaja
            // abu-abu, bukan merah: ini bukan kinerja buruk dan bukan kelalaian.
            'belum_dinilai'       => ['name' => 'Belum Dapat Dinilai', 'color' => 'abu',    'icon' => 'fa-hourglass-half'],
            'belum_ada_data'      => ['name' => 'Belum Ada Data',      'color' => 'abu',    'icon' => 'fa-minus'],
            'belum_terverifikasi' => ['name' => 'Belum Terverifikasi', 'color' => 'oranye', 'icon' => 'fa-hourglass-half'],
        ];
        $row   = $peta[$code] ?? $peta['belum_valid'];
        $warna = dash_color($row['color']);

        return [
            'code'       => array_key_exists($code, $peta) ? $code : 'belum_valid',
            'name'       => $row['name'],
            'color'      => $warna['slug'],
            'color_hex'  => $warna['hex'],
            'color_soft' => $warna['soft'],
            'bs'         => $warna['bs'],
            'icon'       => $row['icon'],
            'numeric'    => false,
        ];
    }
}

if (!function_exists('dash_reason_label')) {
    /** Kode alasan tidak valid -> kalimat bawaan (boleh ditimpa alasan spesifik). */
    function dash_reason_label(string $code): string
    {
        return [
            'missing_target'          => 'Target triwulan belum tersedia.',
            'missing_achievement'     => 'Capaian triwulan belum diinput.',
            'missing_method'          => 'Metode perhitungan belum dipilih.',
            'invalid_number'          => 'Target atau capaian bukan angka yang dapat dihitung.',
            'missing_predicate_scale' => 'Satuan predikat belum memiliki skala nilai.',
            'missing_formula'         => 'Rumus untuk kombinasi satuan & metode ini belum tersedia.',
            'incomplete_period'       => 'Capaian pada periode yang dipilih belum lengkap.',
            'division_by_zero'        => 'Target bernilai 0 sehingga persentase tidak dapat dihitung.',
            'not_calculable'          => 'Persentase capaian belum dapat dihitung.',
        ][$code] ?? 'Persentase capaian belum dapat dihitung.';
    }
}

if (!function_exists('dash_reason_priority')) {
    /**
     * Urutan kegentingan alasan — dipakai saat satu indikator punya beberapa
     * baris ukur bermasalah (yang paling genting yang ditampilkan) dan untuk
     * mengurutkan panel prioritas tindak lanjut.
     */
    function dash_reason_priority(?string $code): int
    {
        $urutan = [
            'missing_target'          => 1,
            'missing_achievement'     => 2,
            'incomplete_period'       => 3,
            'missing_method'          => 4,
            'missing_predicate_scale' => 5,
            'missing_formula'         => 6,
            'invalid_number'          => 7,
            'division_by_zero'        => 8,
            'not_calculable'          => 9,
        ];

        return $urutan[(string) $code] ?? 99;
    }
}

if (!function_exists('dash_mask_quarters')) {
    /**
     * Kosongkan triwulan di atas periode terpilih.
     *
     * Inilah yang membuat filter Triwulan bermakna: capaian & target triwulan
     * setelah periode yang dipilih tidak ikut dihitung.
     *
     * @param array<int, mixed> $nilai [1..4 => nilai]
     *
     * @return array<int, mixed>
     */
    function dash_mask_quarters(array $nilai, int $sampaiTriwulan): array
    {
        $out = [];
        foreach ([1, 2, 3, 4] as $q) {
            $out[$q] = ($q <= $sampaiTriwulan) ? ($nilai[$q] ?? null) : null;
        }
        return $out;
    }
}

if (!function_exists('dash_row_validity')) {
    /**
     * Validitas SATU baris ukur (rencana aksi atau sub rencana aksi).
     *
     * Tidak pernah mengembalikan sekadar true/false — alasannya ikut, supaya
     * dashboard bisa memberi tahu apa yang harus dilengkapi.
     *
     * @param string|null                      $metode      sum|trend_naik|trend_turun|trend_flat
     * @param array<int, mixed>                $targets     [1..4]
     * @param array<int, mixed>                $capaian     [1..4]
     * @param array<int, array<string, mixed>> $skala       baris satuan_skala (kosong = satuan angka)
     * @param int                              $triwulan    periode yang diwajibkan (1..4)
     * @param bool                             $adaBarisMonev apakah baris MONEV-nya sudah pernah dibuat
     * @param bool                             $satuanPredikat satuan bertipe predikat
     *
     * @return array{is_valid: bool, reason_code: string|null, reason: string|null,
     *               percentage: float|null, filled_quarters: int, last_quarter: int|null}
     */
    function dash_row_validity(
        ?string $metode,
        array $targets,
        array $capaian,
        array $skala,
        int $triwulan,
        bool $adaBarisMonev,
        bool $satuanPredikat = false
    ): array {
        $triwulan = max(1, min(4, $triwulan));
        $targets  = dash_mask_quarters($targets, $triwulan);
        $capaian  = dash_mask_quarters($capaian, $triwulan);

        $gagal = static fn (string $code, ?string $pesan = null): array => [
            'is_valid'        => false,
            // Data wajibnya memang belum lengkap / tidak terbaca. Berbeda dari
            // `not_evaluable` di bawah, yang datanya justru sudah lengkap.
            'not_evaluable'   => false,
            'reason_code'     => $code,
            'reason'          => $pesan ?? dash_reason_label($code),
            'percentage'      => null,
            'filled_quarters' => 0,
            'last_quarter'    => null,
        ];

        $adaTarget = false;
        foreach ($targets as $t) {
            if (capaianTerisi($t)) {
                $adaTarget = true;
                break;
            }
        }
        if (!$adaTarget) {
            return $gagal('missing_target', 'Target Triwulan I–' . capaianRomawi($triwulan) . ' belum diisi.');
        }

        if (!$adaBarisMonev) {
            return $gagal('missing_achievement', 'MONEV belum diinput sama sekali.');
        }

        // Satuan predikat tanpa skala: kodenya tidak bisa diterjemahkan ke angka.
        if ($satuanPredikat && $skala === []) {
            return $gagal('missing_predicate_scale');
        }
        // Akumulasi tidak bermakna untuk predikat (menjumlah opini BPK tak masuk akal).
        if ($skala !== [] && $metode === 'sum') {
            return $gagal('missing_formula', 'Metode Akumulasi tidak berlaku untuk satuan predikat.');
        }

        if (!capaianTerisi($capaian[$triwulan] ?? null)) {
            return $gagal(
                'missing_achievement',
                'Capaian Triwulan ' . capaianRomawi($triwulan) . ' belum diinput.'
            );
        }

        $hasil = calculateCapaianTotalPercentage($metode, $targets, $capaian, $skala);

        if ($hasil['error'] !== null) {
            return $gagal(dash_map_error_code((string) $hasil['error']), (string) $hasil['error']);
        }

        // =========================================================
        // BELUM DAPAT DINILAI — DIBEDAKAN DARI BELUM LENGKAP
        //
        // Barisnya sah dan datanya sudah diisi; yang belum ada hanyalah
        // pembagi. Kalau ini dijadikan `$gagal`, dashboard akan
        // memperlakukannya sebagai pekerjaan yang belum beres: masuk hitungan
        // "belum valid", menyumbang butir Perlu Tindak Lanjut, dan menahan
        // agregat OPD — padahal tidak ada yang bisa dikerjakan operator.
        //
        // `is_valid` tetap false karena tidak ada persentase yang sah untuk
        // dirata-rata, TETAPI `not_evaluable` menandainya agar pemanggil bisa
        // MENGELUARKANNYA dari perhitungan alih-alih menghitungnya gagal.
        // =========================================================
        if (($hasil['status'] ?? '') === 'not_evaluable') {
            return [
                'is_valid'        => false,
                'not_evaluable'   => true,
                'reason_code'     => (string) ($hasil['reason_code'] ?? 'zero_target'),
                'reason'          => (string) $hasil['calculation_description'],
                'percentage'      => null,
                'filled_quarters' => (int) $hasil['filled_quarters_count'],
                'last_quarter'    => $hasil['last_quarter'],
            ];
        }

        if ($hasil['percentage'] === null) {
            return $gagal('not_calculable');
        }
        if (!is_finite((float) $hasil['percentage'])) {
            return $gagal('invalid_number', 'Perhitungan menghasilkan nilai yang tidak sah.');
        }

        // Periode sebelum triwulan terpilih yang bolong: nilainya tetap terhitung,
        // tapi kelengkapannya dilaporkan supaya tidak terkesan sudah paripurna.
        for ($q = 1; $q < $triwulan; $q++) {
            if (!capaianTerisi($capaian[$q] ?? null) && capaianTerisi($targets[$q] ?? null)) {
                return [
                    'is_valid'        => false,
                    'not_evaluable'   => false,
                    'reason_code'     => 'incomplete_period',
                    'reason'          => 'Capaian Triwulan ' . capaianRomawi($q) . ' belum diinput.',
                    'percentage'      => (float) $hasil['percentage'],
                    'filled_quarters' => (int) $hasil['filled_quarters_count'],
                    'last_quarter'    => $hasil['last_quarter'],
                ];
            }
        }

        return [
            'is_valid'        => true,
            'not_evaluable'   => false,
            'reason_code'     => null,
            'reason'          => null,
            'percentage'      => round((float) $hasil['percentage'], 2),
            'filled_quarters' => (int) $hasil['filled_quarters_count'],
            'last_quarter'    => $hasil['last_quarter'],
        ];
    }
}

if (!function_exists('dash_map_error_code')) {
    /** Pesan kesalahan dari capaian_helper -> kode alasan dashboard. */
    function dash_map_error_code(string $pesan): string
    {
        $p = strtolower($pesan);

        if (str_contains($p, 'metode perhitungan belum dipilih')) {
            return 'missing_method';
        }
        if (str_contains($p, 'skala predikat')) {
            return 'missing_predicate_scale';
        }
        if (str_contains($p, 'bernilai 0')) {
            return 'division_by_zero';
        }
        if (str_contains($p, 'harus berupa angka') || str_contains($p, 'harus salah satu')) {
            return 'invalid_number';
        }

        return 'not_calculable';
    }
}

if (!function_exists('dash_triwulan_selesai')) {
    /**
     * Apakah sebuah triwulan SUDAH TUTUP menurut tanggal sistem?
     *
     * Dipakai untuk membedakan dua hal yang tampak mirip tetapi tindak
     * lanjutnya berlawanan:
     *   - triwulan BERJALAN & data lengkap tetapi lama tidak disentuh
     *     -> memang perlu diingatkan (mungkin ada capaian baru yang belum
     *        dilaporkan);
     *   - triwulan SUDAH TUTUP & data lengkap
     *     -> tidak ada apa pun yang perlu diperbarui; menandainya "belum
     *        update" hanya karena berkasnya tidak disentuh 30 hari adalah
     *        peringatan palsu yang akan menyala selamanya.
     */
    function dash_triwulan_selesai(int $tahun, int $triwulan): bool
    {
        $triwulan = max(1, min(4, $triwulan));
        $sekarang = (int) date('Y');

        if ($tahun < $sekarang) {
            return true;
        }
        if ($tahun > $sekarang) {
            return false;
        }

        return $triwulan < (int) ceil((int) date('n') / 3);
    }
}

if (!function_exists('dash_triwulan_berjalan')) {
    /**
     * Triwulan BAWAAN dashboard untuk sebuah tahun:
     *  - tahun lampau    -> Triwulan IV (setahun penuh)
     *  - tahun mendatang -> Triwulan I
     *  - tahun berjalan  -> triwulan TERAKHIR YANG SUDAH SELESAI
     *
     * Yang terakhir itu bukan detail kecil. Selama dashboard memilih triwulan
     * yang masih BERJALAN, dash_row_validity() menuntut capaian pada periode
     * yang memang belum waktunya dilaporkan, sehingga seluruh indikator jatuh
     * ke 'missing_achievement' -> "Belum Valid", capaian kabupaten tidak dapat
     * dihitung, dan puluhan Perangkat Daerah tercap "Belum isi TW ini" di
     * tengah triwulan. Dengan memakai triwulan terakhir yang sudah tutup,
     * angka yang tampil adalah periode yang laporannya memang sudah jatuh
     * tempo. Triwulan berjalan tetap bisa dipilih manual lewat filter.
     *
     * Pada Triwulan I tahun berjalan belum ada triwulan yang selesai; nilainya
     * dikunci ke 1 supaya filter tetap sah (1..4) dan tidak melompat ke tahun
     * sebelumnya yang datanya milik dokumen PK lain.
     */
    function dash_triwulan_berjalan(int $tahun): int
    {
        $sekarang = (int) date('Y');
        if ($tahun < $sekarang) {
            return 4;
        }
        if ($tahun > $sekarang) {
            return 1;
        }

        return max(1, (int) ceil((int) date('n') / 3) - 1);
    }
}
