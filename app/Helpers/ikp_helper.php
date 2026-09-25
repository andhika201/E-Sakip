<?php

/**
 * =====================================================================
 * IKP — Indikator Kinerja Prioritas: rumus murni (tanpa DB)
 * =====================================================================
 *
 * Dipakai bersama oleh AdminOpd\IkpController, IkpRekapService, lampiran PK,
 * dasbor Kabupaten/Bupati, dan API eKin. Karena itu SEMUA fungsi di sini
 * murni: menerima angka, mengembalikan angka/array, tidak membaca sesi atau
 * basis data — sehingga bisa dikunci dengan tests/unit/IkpRekapTest.php.
 *
 * MENGAPA ADA PARSER ANGKA SENDIRI (ikp_angka_baca), BUKAN capaianToFloat()
 *   capaianToFloat("302.663") = 302,663 (titik = desimal), sedangkan target
 *   IKP warisan prototipe Prioritas menulis "302.663" untuk 302.663 orang
 *   (titik = ribuan; 48 dari 346 target berbentuk begitu). Dua tafsir yang
 *   berbeda atas teks yang sama tidak boleh hidup berdampingan, maka:
 *     - teks isian IKP dibaca SEKALI saat disimpan, dengan aturan Angka::baca
 *       Prioritas, lalu disimpan sebagai DECIMAL;
 *     - setelah itu semua perhitungan bekerja pada float. Teks IKP tidak
 *       pernah dilewatkan ke capaianToFloat().
 *
 * MENGAPA calculateCapaianTotalPercentage() DIBUNGKUS, BUKAN DIUBAH
 *   Fungsi itu hanya mengenal triwulan [1..4] dan dikunci 33 kasus di
 *   tests/unit/CapaianTotalTest.php. IKP berbasis BULAN, jadi ikp_capaian()
 *   meringkas bulan-bulan menjadi satu "periode" lalu menyerahkan
 *   persentase serta kasus tepinya (target 0 -> not_evaluable, trend_turun
 *   dengan realisasi 0, metode kosong) ke fungsi lama. Rumusnya tetap satu.
 *
 * Metode (kosakata sama dengan monev & capaian_helper):
 *   sum          nilai bulanan = TAMBAHAN; triwulan = jumlah bulan terisi;
 *                tahunan = jumlah 12 bulan.
 *   trend_naik   nilai bulanan = POSISI (makin tinggi makin baik); triwulan =
 *                bulan terisi terakhir dalam triwulan; tahunan = Desember.
 *   trend_turun  seperti trend_naik, makin rendah makin baik.
 *   trend_flat   posisi dipertahankan; rumus capaiannya = trend_naik.
 */

if (! function_exists('ikp_metode_valid')) {
    /** Metode yang dikenal (sum|trend_naik|trend_turun|trend_flat). */
    function ikp_metode_valid(?string $metode): bool
    {
        return in_array($metode, ['sum', 'trend_naik', 'trend_turun', 'trend_flat'], true);
    }
}

if (! function_exists('ikp_rapikan_teks')) {
    /** Spasi tak-putus -> spasi biasa, spasi berlebih dirapatkan, ujung dipangkas. */
    function ikp_rapikan_teks(?string $teks): string
    {
        $teks = str_replace(["\u{00A0}", "\u{2007}", "\u{202F}", "\t"], ' ', (string) $teks);

        return trim((string) preg_replace('/\s+/u', ' ', $teks));
    }
}

if (! function_exists('ikp_angka_kosong')) {
    /** Isian yang bermakna "belum diisi": kosong atau tanda strip. */
    function ikp_angka_kosong(?string $teks): bool
    {
        return in_array(ikp_rapikan_teks($teks), ['', '-', '–', '—'], true);
    }
}

if (! function_exists('ikp_angka_sah')) {
    /**
     * Benar bila isian boleh diterima sebagai angka IKP (atau kosong/strip).
     *
     * Diterima: "400", "1.234" (ribuan), "1.234,5", "24,65", "2.5", "90%", "90 %".
     * Ditolak : huruf, kalimat, "1.2.3", angka negatif, "1 234".
     */
    function ikp_angka_sah(?string $teks): bool
    {
        if ($teks === null || ikp_angka_kosong($teks)) {
            return true;
        }
        $bersih = ikp_rapikan_teks($teks);

        return preg_match('/^\d{1,3}(\.\d{3})*(,\d+)?\s*%?$/u', $bersih) === 1
            || preg_match('/^\d+([.,]\d+)?\s*%?$/u', $bersih) === 1;
    }
}

if (! function_exists('ikp_angka_baca')) {
    /**
     * Teks isian -> float, dengan aturan Angka::baca prototipe Prioritas.
     *
     *   "302.663"  -> 302663   (titik tanpa koma, pola ribuan = pemisah ribuan)
     *   "1.234,5"  -> 1234.5   (koma hadir: titik = ribuan, koma = desimal)
     *   "24,65"    -> 24.65
     *   "2.5"      -> 2.5      (bukan pola ribuan -> titik desimal)
     *   "90%"      -> 90       (tanda persen dibuang; satuannya di kolom satuan)
     *   "" / "-"   -> null
     *   "0"        -> null bila $nolKosong (TARGET: nol = belum ada target),
     *                 0.0 bila ! $nolKosong (REALISASI: nol adalah nilai sah)
     *
     * Mengembalikan null juga untuk teks yang tidak terbaca sebagai angka —
     * pemanggil yang perlu membedakan "kosong" dari "salah ketik" memeriksa
     * ikp_angka_sah() lebih dulu.
     */
    function ikp_angka_baca(?string $teks, bool $nolKosong = true): ?float
    {
        if ($teks === null || ikp_angka_kosong($teks)) {
            return null;
        }

        $bersih = trim(str_replace('%', '', ikp_rapikan_teks($teks)));

        if (str_contains($bersih, ',')) {
            $bersih = str_replace(',', '.', str_replace('.', '', $bersih));
        } elseif (preg_match('/^-?\d{1,3}(\.\d{3})+$/', $bersih)) {
            $bersih = str_replace('.', '', $bersih);
        }

        if (! is_numeric($bersih)) {
            return null;
        }

        $angka = (float) $bersih;
        if (abs($angka) < 1e-12) {
            return $nolKosong ? null : 0.0;
        }

        return $angka;
    }
}

if (! function_exists('ikp_fmt')) {
    /**
     * Float -> teks gaya Indonesia tanpa nol berlebih; "-" untuk null.
     *   302663 -> "302.663", 1234.5 -> "1.234,5", 98.37 -> "98,37", 1.0 -> "1".
     *
     * Hasilnya aman dibaca ulang oleh ikp_angka_baca() (bolak-balik tetap sama),
     * jadi boleh dipakai sebagai nilai awal kotak isian.
     */
    function ikp_fmt(?float $v, int $maxDesimal = 2): string
    {
        if ($v === null || ! is_finite($v)) {
            return '-';
        }
        $maxDesimal = max(0, min(6, $maxDesimal));
        $v          = round($v, $maxDesimal);
        if ($v == 0.0) {
            $v = 0.0; // -0.0 -> 0.0, hindari tampilan "-0"
        }
        $teks = number_format($v, $maxDesimal, ',', '.');
        if ($maxDesimal > 0) {
            $teks = rtrim(rtrim($teks, '0'), ',');
        }

        return $teks;
    }
}

if (! function_exists('ikp_nilai_triwulan')) {
    /**
     * Nilai bulanan [1..12 => ?float] -> nilai triwulan [1..4 => ?float].
     *
     *   sum     -> jumlah bulan yang terisi dalam triwulan
     *   trend_* -> nilai bulan TERISI terakhir dalam triwulan (posisi)
     *   lainnya -> null semua. MENGAPA: tanpa metode tidak ada cara jujur untuk
     *              tahu apakah bulan-bulannya dijumlah atau diambil posisinya
     *              (pelajaran data Prioritas: IKP 454/455 kumulatif tanpa
     *              penanda). Lebih baik kosong daripada angka yang salah.
     *
     * Triwulan tanpa satu pun bulan terisi = null (bukan 0).
     *
     * @param array<int, float|int|string|null> $bulan
     *
     * @return array<int, float|null>
     */
    function ikp_nilai_triwulan(array $bulan, string $metode): array
    {
        $tw = [];
        for ($q = 1; $q <= 4; $q++) {
            if (! ikp_metode_valid($metode)) {
                $tw[$q] = null;

                continue;
            }
            $isi = [];
            // Loop per nomor bulan: kunci bisa hilang/berurutan acak.
            for ($m = 3 * $q - 2; $m <= 3 * $q; $m++) {
                if (array_key_exists($m, $bulan) && $bulan[$m] !== null && $bulan[$m] !== '') {
                    $isi[$m] = (float) $bulan[$m];
                }
            }
            if ($isi === []) {
                $tw[$q] = null;
            } elseif ($metode === 'sum') {
                $tw[$q] = array_sum($isi);
            } else {
                $tw[$q] = end($isi);
            }
        }

        return $tw;
    }
}

if (! function_exists('ikp_nilai_tahunan')) {
    /**
     * Nilai setahun dari bulanan: sum -> jumlah bulan terisi; trend_* -> bulan
     * terisi terakhir. null bila tak satu pun terisi / metode tak dikenal.
     *
     * @param array<int, float|int|string|null> $bulan
     */
    function ikp_nilai_tahunan(array $bulan, string $metode): ?float
    {
        if (! ikp_metode_valid($metode)) {
            return null;
        }
        $isi = [];
        for ($m = 1; $m <= 12; $m++) {
            if (array_key_exists($m, $bulan) && $bulan[$m] !== null && $bulan[$m] !== '') {
                $isi[$m] = (float) $bulan[$m];
            }
        }
        if ($isi === []) {
            return null;
        }

        return $metode === 'sum' ? array_sum($isi) : end($isi);
    }
}

if (! function_exists('ikp_nama_bulan')) {
    /** 1 -> "Januari"; $pendek: 1 -> "Jan". */
    function ikp_nama_bulan(int $bulan, bool $pendek = false): string
    {
        $panjang = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        $singkat = [1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

        return ($pendek ? $singkat : $panjang)[$bulan] ?? (string) $bulan;
    }
}

if (! function_exists('ikp_capaian')) {
    /**
     * Capaian (%) atas bulan $dari..$sampai.
     *
     * Aturan (sama dengan capaian_helper untuk triwulan):
     *  - hanya bulan yang REALISASINYA terisi yang dihitung (0 = terisi);
     *  - sum     : Σ realisasi bulan terisi / Σ target bulan yang sama;
     *  - trend_* : realisasi bulan terisi terakhir vs target bulan itu
     *              (trend_turun: target / realisasi).
     *
     * Contoh DLH-01 (trend_naik) Jul 305/310, Agu 318/330, Sep kosong ->
     * TW III berjalan = 318 / 330 = 96,36 %.
     *
     * Mengembalikan array hasil calculateCapaianTotalPercentage() (kunci
     * percentage, status calculated|not_evaluable|incomplete, reason_code,
     * target_total, actual_total, error, calculation_description, ...) dengan
     * `calculation_description`/`error` ditulis ulang dalam bahasa BULAN, plus:
     *   bulan_terakhir  bulan terisi terakhir (null bila belum ada realisasi)
     *   bulan_terisi    jumlah bulan terisi dalam rentang
     *
     * PENTING bagi pemanggil yang merata-rata: pakai hanya status 'calculated'
     * (not_evaluable menulis percentage 0 yang BUKAN hasil pengukuran).
     *
     * @param array<int, float|int|string|null> $target [bulan => nilai]
     * @param array<int, float|int|string|null> $real   [bulan => nilai]
     * @param array<int, array<string, mixed>>  $skala  baris satuan_skala (satuan predikat)
     */
    function ikp_capaian(string $metode, array $target, array $real, int $dari = 1, int $sampai = 12, array $skala = []): array
    {
        helper('capaian');
        $dari   = max(1, $dari);
        $sampai = min(12, $sampai);

        $terisi = [];
        for ($m = $dari; $m <= $sampai; $m++) {
            if (array_key_exists($m, $real) && $real[$m] !== null && $real[$m] !== '') {
                $terisi[] = $m;
            }
        }
        $akhir = $terisi === [] ? null : end($terisi);

        $rentang = $dari === $sampai
            ? ikp_nama_bulan($dari)
            : ikp_nama_bulan($dari) . '–' . ikp_nama_bulan($sampai);

        $tempel = static function (array $hasil, ?string $deskripsi, ?string $galat = null) use ($akhir, $terisi): array {
            if ($deskripsi !== null) {
                $hasil['calculation_description'] = $deskripsi;
            }
            if ($hasil['error'] !== null) {
                $hasil['error']                   = $galat ?? $hasil['error'];
                $hasil['calculation_description'] = $hasil['error'];
            }
            $hasil['bulan_terakhir'] = $akhir;
            $hasil['bulan_terisi']   = count($terisi);

            return $hasil;
        };

        if ($akhir === null) {
            // Belum ada realisasi: status incomplete TANPA error (bukan kesalahan).
            $hasil = calculateCapaianTotalPercentage($metode, [], []);

            return $tempel($hasil, 'Belum ada realisasi pada ' . $rentang . '.');
        }

        if (! ikp_metode_valid($metode)) {
            $hasil = calculateCapaianTotalPercentage($metode, [1 => 1], [1 => 1]);

            return $tempel($hasil, null, 'Metode perhitungan IKP belum dipilih.');
        }

        if ($metode === 'sum') {
            $t = 0.0;
            $r = 0.0;
            foreach ($terisi as $m) {
                $tm = $target[$m] ?? null;
                if ($tm === null || $tm === '') {
                    $hasil = calculateCapaianTotalPercentage('sum', [1 => null], [1 => $real[$m]], $skala);

                    return $tempel($hasil, null, 'Target ' . ikp_nama_bulan($m) . ' belum diisi, padahal realisasinya sudah ada.');
                }
                $t += (float) $tm;
                $r += (float) $real[$m];
            }
            $hasil = calculateCapaianTotalPercentage('sum', [1 => $t], [1 => $r], $skala);
            if ($hasil['status'] === 'calculated') {
                $awalIsi = $terisi[0];
                $jangka  = $awalIsi === $akhir ? ikp_nama_bulan($akhir) : ikp_nama_bulan($awalIsi) . '–' . ikp_nama_bulan($akhir);

                return $tempel($hasil, 'Akumulasi ' . count($terisi) . ' bulan terisi (' . $jangka . ').');
            }
            if ($hasil['status'] === 'not_evaluable') {
                return $tempel($hasil, 'Target kumulatif bulan terisi masih 0; capaian belum dapat dinilai.');
            }

            return $tempel($hasil, null);
        }

        $tAkhir = $target[$akhir] ?? null;
        if ($tAkhir === '') {
            $tAkhir = null;
        }
        $hasil = calculateCapaianTotalPercentage($metode, [1 => $tAkhir], [1 => $real[$akhir]], $skala);
        if ($hasil['error'] !== null && $tAkhir === null) {
            return $tempel($hasil, null, 'Target ' . ikp_nama_bulan($akhir) . ' belum diisi, padahal realisasinya sudah ada.');
        }
        if ($hasil['status'] === 'calculated') {
            return $tempel($hasil, 'Posisi ' . ikp_nama_bulan($akhir) . ' (bulan terisi terakhir) dengan metode '
                . capaianMetodeNama($metode) . '.');
        }
        if ($hasil['status'] === 'not_evaluable') {
            return $tempel($hasil, 'Target ' . ikp_nama_bulan($akhir) . ' masih 0; capaian belum dapat dinilai.');
        }

        return $tempel($hasil, null);
    }
}

if (! function_exists('ikp_status')) {
    /**
     * Hasil ikp_capaian() -> status warna memakai ambang dashboard_status_helper
     * (tabel dashboard_status_thresholds). Rentang TIDAK di-hardcode di sini.
     *
     * @return array{code:string, name:string, color:string, color_hex:string,
     *               color_soft:string, bs:string, icon:?string, numeric:bool, kelompok:string}
     */
    function ikp_status(array $hasil): array
    {
        helper('dashboard_status');

        if (($hasil['status'] ?? null) === 'calculated' && $hasil['percentage'] !== null) {
            $s = getAchievementStatus((float) $hasil['percentage']);
        } elseif (($hasil['status'] ?? null) === 'not_evaluable') {
            $s = dash_status_nonnumeric('belum_dinilai');
        } elseif (($hasil['error'] ?? null) !== null) {
            $s = dash_status_nonnumeric('belum_valid');
        } else {
            $s = dash_status_nonnumeric('belum_ada_data');
        }
        $s['kelompok'] = ikp_kelompok_warna($s['color'] ?? null);

        return $s;
    }
}

if (! function_exists('ikp_kelompok_warna')) {
    /**
     * Slug warna ambang -> 4 kelompok ringkas untuk kartu ringkasan:
     *   hijau (hijau, biru = tercapai/melampaui), kuning (kuning, oranye),
     *   merah, abu (belum ada data / belum dapat dinilai).
     * Yang dipetakan adalah WARNA pilihan Super Admin, bukan rentang angka.
     */
    function ikp_kelompok_warna(?string $slug): string
    {
        return match ((string) $slug) {
            'hijau', 'biru'    => 'hijau',
            'kuning', 'oranye' => 'kuning',
            'merah'            => 'merah',
            default            => 'abu',
        };
    }
}

if (! function_exists('ikp_bagi_rata')) {
    /**
     * Bagi Rata deterministik: $total dipecah ke $n slot. Kunci hasil 1..$n.
     *
     *   sum          dibagi rata; sisa pembagian digeser ke slot-slot AWAL.
     *                $bulat (satuan tak terbagi: orang/unit/dokumen) & total
     *                bulat -> bilangan bulat (400/5 = 80×5; 346/5 = 70,69,69,69,69).
     *                Selain itu per 0,01 (atau 0,0001 bila 0,01 terlalu kasar):
     *                15/12 = 1,25×12; 10/3 = 3,34; 3,33; 3,33. Σ selalu = $total.
     *   trend_naik   interpolasi linear dari $awal (baseline / posisi periode
     *   trend_turun  sebelumnya) ke $total; slot terakhir = $total persis.
     *                Tanpa $awal -> semua slot = $total (tidak mengarang titik awal).
     *   trend_flat   semua slot = $total.
     *   lainnya      [] (metode belum dipilih: tidak ada pembagian yang jujur).
     *
     * @return array<int, float>
     */
    function ikp_bagi_rata(float $total, int $n, string $metode, ?float $awal = null, bool $bulat = false): array
    {
        if ($n < 1 || ! ikp_metode_valid($metode) || ! is_finite($total)) {
            return [];
        }

        if ($metode === 'trend_flat') {
            return array_fill(1, $n, $total);
        }

        if ($metode === 'sum') {
            $tanda   = $total < 0 ? -1 : 1;
            $mutlak  = abs($total);
            $desimal = ($bulat && abs($mutlak - round($mutlak)) < 1e-9) ? 0 : 2;
            if ($desimal === 2 && intdiv((int) round($mutlak * 100), $n) === 0 && $mutlak > 0) {
                $desimal = 4; // 0,1 dibagi 12: per 0,01 hampir semua slot jadi 0
            }
            $skala = 10 ** $desimal;
            $unit  = (int) round($mutlak * $skala);
            $dasar = intdiv($unit, $n);
            $sisa  = $unit - $dasar * $n;
            $out   = [];
            for ($k = 1; $k <= $n; $k++) {
                $u       = $dasar + ($k <= $sisa ? 1 : 0);
                $out[$k] = (float) ($tanda * $u / $skala);
            }

            return $out;
        }

        // trend_naik / trend_turun
        if ($awal === null || ! is_finite($awal)) {
            return array_fill(1, $n, $total);
        }
        $out = [];
        for ($k = 1; $k <= $n; $k++) {
            $v = $awal + ($total - $awal) * $k / $n;
            if ($k === $n) {
                $v = $total;
            } elseif ($bulat) {
                $v = round($v);
            } else {
                $v = round($v, 2);
            }
            $out[$k] = (float) $v;
        }

        return $out;
    }
}

if (! function_exists('ikp_cek')) {
    /**
     * Pemeriksa konsistensi pecahan target terhadap induknya (tidak memblokir
     * penyimpanan — hasilnya peringatan untuk operator).
     *
     *   sum          Σ anak terisi = induk (toleransi 0,005)
     *   trend_naik   anak slot TERAKHIR = induk, dan tidak pernah turun
     *   trend_turun  anak slot TERAKHIR = induk, dan tidak pernah naik
     *   trend_flat   semua anak terisi = induk, slot terakhir terisi
     *
     * Slot kosong (null) dilewati — mis. kolom 2025 yang "sudah lewat".
     *
     * @param array<int, float|int|null> $anak [urutan => nilai], diurutkan menurut kunci
     *
     * @return array{ok:bool, pesan:string, selisih:?float, status:string}
     *         status: cocok|selisih|kosong|tanpa_induk|tanpa_metode
     */
    function ikp_cek(string $metode, ?float $induk, array $anak): array
    {
        $tol = 0.005;
        ksort($anak);
        $isi = [];
        foreach ($anak as $k => $v) {
            if ($v !== null && $v !== '') {
                $isi[$k] = (float) $v;
            }
        }
        $hasil = static fn (bool $ok, string $pesan, ?float $selisih, string $status): array
            => ['ok' => $ok, 'pesan' => $pesan, 'selisih' => $selisih, 'status' => $status];

        if (! ikp_metode_valid($metode)) {
            return $hasil(false, 'Metode perhitungan belum dipilih, konsistensi tidak dapat diperiksa.', null, 'tanpa_metode');
        }
        if ($induk === null) {
            return $hasil(false, 'Target induk belum berupa angka, konsistensi tidak dapat diperiksa.', null, 'tanpa_induk');
        }
        if ($isi === []) {
            return $hasil(false, 'Belum ada rincian yang diisi.', null, 'kosong');
        }

        if ($metode === 'sum') {
            $jumlah  = array_sum($isi);
            $selisih = $jumlah - $induk;
            if (abs($selisih) <= $tol) {
                return $hasil(true, 'Jumlah ' . ikp_fmt($jumlah, 4) . ' sesuai target ' . ikp_fmt($induk, 4) . '.', 0.0, 'cocok');
            }

            return $hasil(false, 'Jumlah ' . ikp_fmt($jumlah, 4) . ($selisih < 0 ? ' kurang ' : ' lebih ')
                . ikp_fmt(abs($selisih), 4) . ' dari target ' . ikp_fmt($induk, 4) . '.', $selisih, 'selisih');
        }

        $kunciAkhir = array_key_last($anak);
        $akhir      = $anak[$kunciAkhir] ?? null;

        if ($metode === 'trend_flat') {
            foreach ($isi as $k => $v) {
                if (abs($v - $induk) > $tol) {
                    return $hasil(false, 'Nilai ' . ikp_fmt($v, 4) . ' pada periode ke-' . $k . ' berbeda dari target '
                        . ikp_fmt($induk, 4) . ' (metode dipertahankan: semua periode sama).', $v - $induk, 'selisih');
                }
            }
            if ($akhir === null || $akhir === '') {
                return $hasil(false, 'Periode terakhir belum diisi.', null, 'selisih');
            }

            return $hasil(true, 'Semua periode terisi sama dengan target ' . ikp_fmt($induk, 4) . '.', 0.0, 'cocok');
        }

        // trend_naik / trend_turun
        $naik    = $metode === 'trend_naik';
        $sebelum = null;
        foreach ($isi as $k => $v) {
            if ($sebelum !== null && ($naik ? $v < $sebelum - $tol : $v > $sebelum + $tol)) {
                return $hasil(false, 'Nilai ' . ikp_fmt($v, 4) . ' pada periode ke-' . $k . ($naik
                    ? ' turun dari periode sebelumnya (metode makin tinggi makin baik: tidak boleh turun).'
                    : ' naik dari periode sebelumnya (metode makin rendah makin baik: tidak boleh naik).'), null, 'selisih');
            }
            $sebelum = $v;
        }
        if ($akhir === null || $akhir === '') {
            return $hasil(false, 'Periode terakhir belum diisi; nilainya harus sama dengan target ' . ikp_fmt($induk, 4) . '.', null, 'selisih');
        }
        $selisih = (float) $akhir - $induk;
        if (abs($selisih) > $tol) {
            return $hasil(false, 'Nilai periode terakhir ' . ikp_fmt((float) $akhir, 4) . ' belum sama dengan target '
                . ikp_fmt($induk, 4) . '.', $selisih, 'selisih');
        }

        return $hasil(true, 'Periode terakhir sama dengan target ' . ikp_fmt($induk, 4) . ($naik ? ', naik bertahap.' : ', turun bertahap.'), 0.0, 'cocok');
    }
}

if (! function_exists('ikp_satuan_bulat')) {
    /**
     * Satuan yang tak terbagi (orang, unit, dokumen, …) -> Bagi Rata bilangan bulat.
     * Heuristik kata; operator tetap bisa menyunting hasilnya.
     *
     * MENGAPA satuan terbagi dicocokkan per KATA UTUH (\b): dengan cocok
     * potongan, "ha" menangkap perUSAHAan, pelaku usaHA, keluraHAn dan "rp"
     * menangkap peRPustakaan — sehingga 20 Perusahaan dibagi 1,67 per bulan.
     */
    function ikp_satuan_bulat(?string $satuan): bool
    {
        $s = mb_strtolower(ikp_rapikan_teks($satuan));
        if ($s === '' || str_contains($s, '%')
            || preg_match('/\b(persen|persentase|indeks|nilai|skor|rasio|predikat|kategori|ton|kg|kilogram|km|kilometer|ha|hektar|hektare|meter|m2|m3|liter|rp|rupiah|jam|hari|menit|ppm|level)\b/u', $s)) {
            return false;
        }

        return (bool) preg_match('/(orang|jiwa|unit|dokumen|laporan|paket|kegiatan|sekolah|lembaga|aplikasi|kali|buah|desa|pekon|kelurahan|kecamatan|perusahaan|usaha|pedagang|kelompok|titik|lokasi|perangkat daerah|opd|pd\b|berita acara|inovasi|layanan|kasus|keluarga|kk|rumah|peserta|siswa|anak|nasabah|bank sampah|tps|ruang|komunitas|media|konten|eksemplar|arsip|berkas|sampel|usulan|sertifikat|objek|satuan pendidikan|koperasi|ikm|ukm|umkm|pasar|trayek|event|festival)/u', $s);
    }
}

if (! function_exists('ikp_lencana_status')) {
    /**
     * HTML lencana status capaian. Warnanya diambil dari palet ambang
     * (dash_color) berdasar slug warna — tidak menerima CSS bebas.
     */
    function ikp_lencana_status(?string $warna, string $label, ?string $judul = null): string
    {
        helper('dashboard_status');
        $w     = dash_color($warna);
        $ikon  = match ($w['slug']) {
            'hijau'  => 'fa-circle-check',
            'biru'   => 'fa-arrow-trend-up',
            'kuning' => 'fa-circle-half-stroke',
            'oranye' => 'fa-triangle-exclamation',
            'merah'  => 'fa-circle-exclamation',
            default  => 'fa-minus',
        };
        $title = $judul !== null && $judul !== '' ? ' title="' . esc($judul, 'attr') . '"' : '';

        return '<span class="ikp-status" style="background:' . esc($w['soft'], 'attr') . ';color:' . esc($w['hex'], 'attr') . '"' . $title . '>'
            . '<i class="fas ' . $ikon . '"></i>' . esc($label) . '</span>';
    }
}
