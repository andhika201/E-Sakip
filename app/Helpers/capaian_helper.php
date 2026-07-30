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
        $hasil = [
            'percentage'              => null,
            'last_quarter'            => null,
            'filled_quarters_count'   => 0,
            'calculation_description' => 'Isi minimal satu capaian triwulan untuk menghitung capaian total.',
            'error'                   => null,
        ];

        $gagal = static function (array $hasil, string $pesan): array {
            $hasil['error']                   = $pesan;
            $hasil['calculation_description'] = $pesan;
            $hasil['percentage']              = null;

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

            if (abs($totalTarget) < 1e-9) {
                return $gagal($hasil, 'Total target triwulan bernilai 0, Capaian Total tidak dapat dihitung.');
            }

            $hasil['percentage']              = round($totalCapaian / $totalTarget * 100, 2);
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
                $hasil['percentage'] = 100.0;

                return $hasil;
            }

            $hasil['percentage'] = round($target / $capaian * 100, 2);

            return $hasil;
        }

        // trend_naik & trend_flat: semakin tinggi capaian semakin baik.
        if (abs($target) < 1e-9) {
            return $gagal(
                $hasil,
                'Target Triwulan ' . capaianRomawi($akhir['quarter'])
                    . ' bernilai 0, Capaian Total tidak dapat dihitung.'
            );
        }

        $hasil['percentage'] = round($capaian / $target * 100, 2);

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
