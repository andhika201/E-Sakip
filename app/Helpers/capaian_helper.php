<?php

/**
 * Perhitungan CAPAIAN TOTAL (persentase) untuk MONEV Rencana Aksi PK.
 *
 * Ini SATU-SATUNYA sumber rumus di sisi server: dipakai saat menyimpan capaian
 * (PkRenaksiController::monevSave) maupun saat menampilkannya kembali.
 * Fungsi JavaScript calculateAchievementPercentage() di
 * app/Views/adminOpd/pk_renaksi/monev_form.php adalah CERMINAN fungsi ini —
 * kalau rumus di sini berubah, ubah juga di sana (JS hanya untuk pratinjau,
 * nilai yang tersimpan selalu hasil hitungan server).
 *
 * Catatan kolom: `monev.capaian_triwulan_*`, `target_sub_rencana.target_triwulan_*`
 * dan `target_rencana.target_triwulan_*` bertipe VARCHAR, jadi isinya bisa saja
 * bukan angka. Semua konversi lewat capaianToFloat() dan ketidaksesuaian
 * dilaporkan lewat kunci `error`, bukan dibiarkan jadi NaN/Infinity.
 */

if (!function_exists('capaianMetodeList')) {
    /**
     * Metode perhitungan: kode yang disimpan di DB => label untuk dropdown.
     *
     * @return array<string, string>
     */
    function capaianMetodeList(): array
    {
        return [
            'sum'         => 'Akumulasi / Jumlah',
            'trend_naik'  => 'Trend Naik — semakin tinggi semakin baik',
            'trend_turun' => 'Trend Turun — semakin rendah semakin baik',
            'trend_flat'  => 'Trend Flat — target tetap',
        ];
    }
}

if (!function_exists('capaianMetodeValid')) {
    function capaianMetodeValid($metode): bool
    {
        return is_string($metode) && array_key_exists($metode, capaianMetodeList());
    }
}

if (!function_exists('capaianMetodeNama')) {
    /** Nama pendek metode, untuk kalimat keterangan & label tabel. */
    function capaianMetodeNama(?string $metode): string
    {
        $nama = [
            'sum'         => 'Akumulasi / Jumlah',
            'trend_naik'  => 'Trend Naik',
            'trend_turun' => 'Trend Turun',
            'trend_flat'  => 'Trend Flat',
        ];

        return $nama[(string) $metode] ?? '-';
    }
}

if (!function_exists('capaianMetodeRingkas')) {
    /**
     * Ringkasan metode perhitungan sebuah indikator dari SELURUH baris ukurnya.
     *
     * =================================================================
     * MENGAPA TIDAK CUKUP `rows[0]`
     *
     * Sebelumnya layar mengambil `rows[0]['metode']` — metode baris pertama —
     * lalu menampilkannya sebagai metode indikator. Itu benar hanya ketika
     * indikator punya tepat satu baris ukur. Begitu sebuah rencana aksi
     * dipecah menjadi beberapa sub dengan metode berbeda, layar menampilkan
     * metode salah satu sub seolah berlaku untuk semuanya, dan pembacanya
     * tidak punya cara tahu bahwa sub lain dihitung dengan cara lain.
     *
     * Lebih buruk pada urutan sebaliknya: bila sub PERTAMA kebetulan sudah
     * bermetode sementara sub kedua belum, layar menampilkan nama metode yang
     * meyakinkan padahal agregatnya justru tidak bisa dihitung.
     *
     * @param array<int, array<string, mixed>> $rows baris ukur CURRENT
     */
    function capaianMetodeRingkas(array $rows): string
    {
        if ($rows === []) {
            return '-';
        }

        $metode = [];

        foreach ($rows as $baris) {
            $m = trim((string) ($baris['metode'] ?? ''));

            // Satu saja yang belum bermetode sudah membuat agregat tidak bisa
            // dihitung, jadi itulah yang perlu dibaca lebih dulu — bukan nama
            // metode baris yang kebetulan sudah terisi.
            if ($m === '') {
                return 'Metode capaian belum ditentukan';
            }

            $metode[$m] = true;
        }

        if (count($metode) > 1) {
            return 'Beragam (per Sub Rencana Aksi)';
        }

        return capaianMetodeNama((string) array_key_first($metode));
    }
}

if (!function_exists('capaianAngkaRingkas')) {
    /** Angka untuk kalimat penjelas: 7.0 -> "7", 7.5 -> "7,5". */
    function capaianAngkaRingkas(float $nilai): string
    {
        return rtrim(rtrim(number_format($nilai, 2, ',', '.'), '0'), ',');
    }
}

if (!function_exists('capaianRomawi')) {
    function capaianRomawi(int $triwulan): string
    {
        return ['I', 'II', 'III', 'IV'][$triwulan - 1] ?? (string) $triwulan;
    }
}

if (!function_exists('capaianTerisi')) {
    /**
     * Apakah sebuah nilai capaian dianggap SUDAH diisi.
     *
     * Angka 0 (dan teks "0") termasuk SUDAH diisi — hanya null dan string
     * kosong yang dianggap belum. Jangan pakai empty()/! di tempat lain.
     */
    function capaianTerisi($nilai): bool
    {
        return $nilai !== null && trim((string) $nilai) !== '';
    }
}

if (!function_exists('capaianToFloat')) {
    /**
     * Teks angka -> float; null bila kosong ATAU bukan angka.
     *
     * Menerima "1.5" & "1,5" (desimal) serta "1.234,5" (titik = pemisah ribuan
     * kalau koma juga hadir). Simbol persen dan spasi diabaikan.
     */
    function capaianToFloat($nilai): ?float
    {
        if (!capaianTerisi($nilai)) {
            return null;
        }

        $teks = str_replace(['%', ' ', "\u{00A0}"], '', trim((string) $nilai));

        // Hanya bila keduanya hadir, titik diperlakukan sebagai pemisah ribuan;
        // "4.25" (tanpa koma) tetap dibaca 4,25 seperti data yang sudah ada.
        if (str_contains($teks, ',') && str_contains($teks, '.')) {
            $teks = str_replace('.', '', $teks);
        }
        $teks = str_replace(',', '.', $teks);

        return is_numeric($teks) ? (float) $teks : null;
    }
}

if (!function_exists('capaianSkalaMap')) {
    /**
     * Daftar skala predikat -> peta pencarian [kode huruf kecil => nilai].
     *
     * Dipakai untuk satuan bertipe `predikat` (mis. Opini BPK: WTP=4, WDP=3).
     * Baris skala berbentuk ['kode' => 'WTP', 'nilai' => 4, ...].
     *
     * @param array<int, array<string, mixed>> $skala
     *
     * @return array<string, float>
     */
    function capaianSkalaMap(array $skala): array
    {
        $peta = [];
        foreach ($skala as $baris) {
            $kode = strtolower(trim((string) ($baris['kode'] ?? '')));
            if ($kode === '') {
                continue;
            }
            $peta[$kode] = (float) ($baris['nilai'] ?? 0);
        }

        return $peta;
    }
}

if (!function_exists('capaianNilaiSkala')) {
    /**
     * Nilai sebuah target/capaian: angka apa adanya, atau skor predikatnya.
     * null bila bukan angka DAN kodenya tidak ada di skala.
     *
     * @param array<string, float> $peta hasil capaianSkalaMap()
     */
    function capaianNilaiSkala($nilai, array $peta = []): ?float
    {
        $angka = capaianToFloat($nilai);
        if ($angka !== null) {
            return $angka;
        }

        if ($peta === [] || !capaianTerisi($nilai)) {
            return null;
        }

        return $peta[strtolower(trim((string) $nilai))] ?? null;
    }
}

if (!function_exists('calculateCapaianTotalPercentage')) {
    /**
     * Hitung Capaian Total (persentase) dari target & capaian triwulanan.
     *
     * Rumus per metode:
     *   sum         -> SUM(capaian terisi) / SUM(target pada triwulan yang sama) x 100
     *   trend_naik  -> capaian triwulan terakhir / target triwulan itu x 100
     *   trend_flat  -> sama dengan trend_naik (target antar triwulan biasanya tetap)
     *   trend_turun -> target triwulan terakhir / capaian triwulan itu x 100
     *
     * "Triwulan terakhir" = triwulan terisi dengan nomor terbesar (Q4 > Q3 > Q2 > Q1),
     * jadi Q1 & Q3 terisi sementara Q2 kosong tetap memakai Q3.
     *
     * Untuk satuan bertipe PREDIKAT (mis. Opini BPK), oper daftar skalanya lewat
     * $skala: kode seperti "WTP" diterjemahkan ke skornya (4) lalu dihitung
     * dengan rumus yang sama persis. Metode `sum` tidak masuk akal untuk
     * predikat — pemilihannya dibatasi di form, bukan di sini.
     *
     * @param string|null                      $method       sum|trend_naik|trend_turun|trend_flat
     * @param array<int, mixed>                $targets      [1..4 => target triwulan (boleh teks/predikat)]
     * @param array<int, mixed>                $achievements [1..4 => capaian triwulan (boleh kosong)]
     * @param array<int, array<string, mixed>> $skala        baris satuan_skala bila satuannya predikat
     *
     * @return array{
     *     percentage: float|null,
     *     last_quarter: int|null,
     *     filled_quarters_count: int,
     *     calculation_description: string,
     *     error: string|null
     * }
     */
    function calculateCapaianTotalPercentage(?string $method, array $targets, array $achievements, array $skala = []): array
    {
        $peta = capaianSkalaMap($skala);
        $nilai = static fn ($v): ?float => capaianNilaiSkala($v, $peta);
        $satuanPredikat = $peta !== [];
        // =============================================================
        // TIGA KEADAAN, BUKAN DUA
        //
        // Sebelumnya hasil fungsi ini hanya punya dua keadaan: ada angka, atau
        // `error`. Itu memaksa keadaan ketiga — data SUDAH lengkap tetapi
        // pembaginya nol — ikut memilih salah satu dari keduanya, dan dua-duanya
        // berbohong:
        //
        //   dijadikan 0%   -> indikatornya masuk pita Kritis (0-59,99) padahal
        //                     pekerjaannya belum jatuh tempo;
        //   dijadikan 100% -> 7 dibagi 0 disebut "tercapai penuh";
        //   dijadikan error-> operator disuruh membetulkan data yang sudah benar.
        //
        // Karena itu ditambahkan `status`:
        //
        //   calculated    persentasenya sah
        //   not_evaluable data lengkap, tetapi belum ada pembagi yang bisa
        //                 dipakai — BUKAN kesalahan, BUKAN data kurang
        //   incomplete    data wajib belum lengkap / tidak terbaca
        //
        // `percentage` dan `error` dipertahankan apa adanya supaya pemanggil
        // lama tetap jalan: not_evaluable memberi percentage null TANPA error,
        // sehingga tidak ada layar lama yang mendadak menampilkan pesan merah.
        // =============================================================
        $hasil = [
            'percentage'              => null,
            'status'                  => 'incomplete',
            'reason_code'             => null,
            'target_total'            => null,
            'actual_total'            => null,
            'last_quarter'            => null,
            'filled_quarters_count'   => 0,
            'calculation_description' => 'Isi minimal satu capaian triwulan untuk menghitung capaian total.',
            'error'                   => null,
        ];

        $gagal = static function (array $hasil, string $pesan, string $kode = 'invalid_input'): array {
            $hasil['error']                   = $pesan;
            $hasil['calculation_description'] = $pesan;
            $hasil['percentage']              = null;
            $hasil['status']                  = 'incomplete';
            $hasil['reason_code']             = $kode;

            return $hasil;
        };

        /** Data lengkap, tetapi tidak ada pembagi yang sah. Bukan kegagalan. */
        $takTerukur = static function (array $hasil, string $kode, string $pesan): array {
            $hasil['percentage']              = null;
            $hasil['error']                   = null;
            $hasil['status']                  = 'not_evaluable';
            $hasil['reason_code']             = $kode;
            $hasil['calculation_description'] = $pesan;

            return $hasil;
        };

        // 1) Kumpulkan triwulan yang capaiannya SUDAH diisi (0 termasuk terisi).
        $terisi = [];
        foreach ([1, 2, 3, 4] as $q) {
            if (!capaianTerisi($achievements[$q] ?? null)) {
                continue;
            }

            $angka = $nilai($achievements[$q]);
            if ($angka === null) {
                return $gagal($hasil, 'Capaian Triwulan ' . capaianRomawi($q) . ($satuanPredikat
                    ? ' tidak ada pada skala predikat satuan ini.'
                    : ' harus berupa angka.'));
            }

            $terisi[] = ['quarter' => $q, 'target' => $targets[$q] ?? null, 'achievement' => $angka];
        }

        $hasil['filled_quarters_count'] = count($terisi);
        if ($terisi === []) {
            return $hasil; // belum ada capaian -> total dikosongkan, bukan error
        }

        if (!capaianMetodeValid($method)) {
            return $gagal($hasil, 'Metode perhitungan belum dipilih.');
        }

        // 2) Target yang dipakai wajib bernilai (kolomnya VARCHAR, jadi bisa saja
        //    teks bebas yang tidak ada di skala).
        $akhir  = $terisi[count($terisi) - 1];
        $dipakai = ($method === 'sum') ? $terisi : [$akhir];
        foreach ($dipakai as $baris) {
            if ($nilai($baris['target']) === null) {
                return $gagal(
                    $hasil,
                    'Target Triwulan ' . capaianRomawi($baris['quarter']) . ($satuanPredikat
                        ? ' tidak ada pada skala predikat satuan ini, Capaian Total tidak dapat dihitung.'
                        : ' harus berupa angka agar Capaian Total dapat dihitung.')
                );
            }
        }

        $hasil['last_quarter'] = $akhir['quarter'];

        // 3) Akumulasi: hanya target dari triwulan yang capaiannya terisi yang dijumlahkan.
        if ($method === 'sum') {
            $totalCapaian = 0.0;
            $totalTarget  = 0.0;
            foreach ($terisi as $baris) {
                $totalCapaian += $baris['achievement'];
                $totalTarget  += (float) $nilai($baris['target']);
            }

            // =====================================================
            // TARGET 0 DAN CAPAIAN 0 -> 0%  (keputusan klien, 3 Sep 2026)
            //
            // Pembagi 0 secara matematis tidak terdefinisi, dan sebelumnya
            // Capaian Total ditolak dengan "Total target triwulan bernilai 0".
            // Kalimat itu benar untuk baris yang seluruh targetnya memang
            // kosong, tetapi menyesatkan untuk kasus yang jauh lebih sering:
            // target ADA namun jatuh tempo di triwulan yang belum dilaporkan
            // (mis. target 0-0-1-0, baru TW1-TW2 yang diisi). Operator lalu
            // mencari kesalahan yang tidak ada.
            //
            // Klien memutuskan: bila targetnya 0 DAN capaiannya juga 0,
            // Capaian Total ditulis 0%, bukan ditolak.
            //
            // KONSEKUENSI YANG PERLU DIINGAT saat membaca dashboard: 0% masuk
            // pita "Kritis" (0-59,99). Jadi sub yang pekerjaannya baru jatuh
            // tempo di TW3/TW4 akan menyumbang 0% ke rata-rata indikatornya
            // sepanjang tahun berjalan. Itu memang yang diminta; bila kelak
            // dirasa terlalu keras, yang perlu diubah adalah pita statusnya
            // atau perlakuan "belum jatuh tempo" pada agregat indikator —
            // bukan rumus ini.
            //
            // =====================================================
            // PEMBAGI 0 PADA AKUMULASI -> BELUM DAPAT DINILAI
            //
            // "Akumulasi / Jumlah" menjawab bagaimana nilai antar-triwulan
            // digabung, BUKAN apakah semakin besar semakin baik. Karena arahnya
            // tidak diketahui, target kumulatif 0 tidak boleh diterjemahkan
            // menjadi angka apa pun:
            //
            //   0 dari 0  bukan 0% (tidak ada yang gagal dikerjakan)
            //             bukan 100% (tidak ada yang dituntut)
            //   7 dari 0  bukan 100% — 7 dibagi 0 tidak menghasilkan persen
            //
            // Keduanya keadaan yang SAH: targetnya memang baru jatuh tempo di
            // triwulan berikutnya. Begitu triwulan bertarget ikut dinilai,
            // pembaginya muncul dan persentasenya dihitung seperti biasa —
            // termasuk bila hasilnya di atas 100%.
            //
            // trend_turun TIDAK lewat sini: ia memang "semakin rendah semakin
            // baik", sehingga target 0 punya arti yang jelas dan rumusnya
            // sendiri sudah menanganinya di bawah.
            // =====================================================
            if (abs($totalTarget) < 1e-9) {
                $nol = abs($totalCapaian) < 1e-9;

                return $takTerukur(
                    $hasil,
                    $nol ? 'zero_target' : 'actual_without_target',
                    $nol
                        ? 'Belum dapat dinilai. Target kumulatif sampai Triwulan '
                            . capaianRomawi($akhir['quarter']) . ' masih 0.'
                        : 'Belum dapat dinilai. Realisasi ' . capaianAngkaRingkas($totalCapaian)
                            . ' sudah tercatat, namun target kumulatif sampai Triwulan '
                            . capaianRomawi($akhir['quarter']) . ' masih 0.'
                );
            }

            $hasil['percentage']              = round($totalCapaian / $totalTarget * 100, 2);
            $hasil['status']                  = 'calculated';
            $hasil['target_total']            = $totalTarget;
            $hasil['actual_total']            = $totalCapaian;
            $hasil['calculation_description'] = 'Dihitung dari akumulasi ' . count($terisi)
                . ' triwulan yang telah diisi.';

            return $hasil;
        }

        // 4) Trend: cukup triwulan terakhir yang terisi.
        $target  = (float) $nilai($akhir['target']);
        $capaian = $akhir['achievement'];
        $hasil['calculation_description'] = 'Dihitung dari Capaian Triwulan ' . capaianRomawi($akhir['quarter'])
            . ' menggunakan metode ' . capaianMetodeNama($method) . '.';

        if ($method === 'trend_turun') {
            // Capaian 0 pada indikator "semakin rendah semakin baik" = target
            // tercapai sempurna. Dipatok 100% supaya tidak jadi Infinity.
            // KEBIJAKAN: ubah angka 100 di bawah bila nanti ada batas maksimal
            // persentase yang disepakati (mis. dibatasi 100% atau 200%).
            if (abs($capaian) < 1e-9) {
                $hasil['percentage']   = 100.0;
                $hasil['status']       = 'calculated';
                $hasil['target_total'] = $target;
                $hasil['actual_total'] = $capaian;

                return $hasil;
            }

            $hasil['percentage']   = round($target / $capaian * 100, 2);
            $hasil['status']       = 'calculated';
            $hasil['target_total'] = $target;
            $hasil['actual_total'] = $capaian;

            return $hasil;
        }

        // trend_naik & trend_flat: semakin tinggi capaian semakin baik.
        //
        // trend_flat berlabel "target tetap", tetapi RUMUSNYA di sini memang
        // sama dengan trend_naik — bukan "harus persis sama dengan target".
        // Karena itu ia tidak diperlakukan sebagai exact-target; memperlakukannya
        // begitu berarti mengarang makna yang tidak pernah ada di kode ini.
        if (abs($target) < 1e-9) {
            $nol = abs($capaian) < 1e-9;

            return $takTerukur(
                $hasil,
                $nol ? 'zero_target' : 'actual_without_target',
                $nol
                    ? 'Belum dapat dinilai. Target Triwulan ' . capaianRomawi($akhir['quarter'])
                        . ' masih 0.'
                    : 'Belum dapat dinilai. Realisasi ' . capaianAngkaRingkas($capaian)
                        . ' sudah tercatat, namun target Triwulan '
                        . capaianRomawi($akhir['quarter']) . ' masih 0.'
            );
        }

        $hasil['percentage']   = round($capaian / $target * 100, 2);
        $hasil['status']       = 'calculated';
        $hasil['target_total'] = $target;
        $hasil['actual_total'] = $capaian;

        return $hasil;
    }
}

if (!function_exists('capaianFormatPersen')) {
    /**
     * Angka desimal dari DB -> teks persentase gaya Indonesia.
     * 93.75 -> "93,75%", 100 -> "100%", 112.5 -> "112,50%", null -> $kosong.
     */
    function capaianFormatPersen($nilai, string $kosong = '-'): string
    {
        if (!capaianTerisi($nilai) || !is_numeric($nilai)) {
            return $kosong;
        }

        $teks = number_format((float) $nilai, 2, ',', '.');

        // Bulat tanpa desimal ditulis ringkas: "100%" bukan "100,00%".
        return preg_replace('/,00$/', '', $teks) . '%';
    }
}
