<?php

use CodeIgniter\Test\CIUnitTestCase;

/**
 * Rumus Capaian Total (persentase) MONEV Rencana Aksi PK.
 *
 * @see app/Helpers/capaian_helper.php
 *
 * @internal
 */
final class CapaianTotalTest extends CIUnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        helper('capaian');
    }

    /**
     * @param array<int, mixed> $targets
     * @param array<int, mixed> $capaian
     */
    private function hitung(?string $metode, array $targets, array $capaian): array
    {
        return calculateCapaianTotalPercentage($metode, $targets, $capaian);
    }

    /* ---------- 1. semua capaian kosong ---------- */
    public function testSemuaCapaianKosongMengembalikanNull(): void
    {
        $hasil = $this->hitung('trend_naik', [1 => 25, 2 => 50, 3 => 75, 4 => 100], [1 => null, 2 => '', 3 => null, 4 => null]);

        $this->assertNull($hasil['percentage']);
        $this->assertSame(0, $hasil['filled_quarters_count']);
        $this->assertNull($hasil['error']);
        $this->assertSame('-', capaianFormatPersen($hasil['percentage']));
    }

    /* ---------- 2-4. trend naik: hanya triwulan terakhir yang dipakai ---------- */
    public function testTrendNaikHanyaQ1(): void
    {
        $hasil = $this->hitung('trend_naik', [1 => 25, 2 => 50, 3 => 75, 4 => 100], [1 => 20]);

        $this->assertSame(80.0, $hasil['percentage']);
        $this->assertSame(1, $hasil['last_quarter']);
    }

    public function testTrendNaikQ1DanQ2MemakaiQ2(): void
    {
        $hasil = $this->hitung('trend_naik', [1 => 25, 2 => 50, 3 => 75, 4 => 100], [1 => 20, 2 => 45]);

        $this->assertSame(90.0, $hasil['percentage']);
        $this->assertSame(2, $hasil['last_quarter']);
        $this->assertSame('Dihitung dari Capaian Triwulan II menggunakan metode Trend Naik.', $hasil['calculation_description']);
    }

    public function testTrendNaikSemuaTerisiMemakaiQ4(): void
    {
        $hasil = $this->hitung('trend_naik', [1 => 25, 2 => 50, 3 => 75, 4 => 100], [1 => 20, 2 => 45, 3 => 70, 4 => 110]);

        $this->assertSame(110.0, $hasil['percentage']);
        $this->assertSame(4, $hasil['last_quarter']);
    }

    /* ---------- 5-6. trend turun ---------- */
    public function testTrendTurunSemakinRendahSemakinBaik(): void
    {
        $hasil = $this->hitung('trend_turun', [1 => 12, 2 => 11], [1 => 13, 2 => 10]);

        $this->assertSame(110.0, $hasil['percentage']);
    }

    public function testTrendTurunQ1Saja(): void
    {
        $hasil = $this->hitung('trend_turun', [1 => 12], [1 => 13]);

        $this->assertSame(92.31, $hasil['percentage']);
    }

    public function testTrendTurunCapaianNolJadi100PersenBukanInfinity(): void
    {
        $hasil = $this->hitung('trend_turun', [1 => 12], [1 => 0]);

        $this->assertSame(100.0, $hasil['percentage']);
        $this->assertTrue(is_finite($hasil['percentage']));
    }

    public function testTrendTurunTargetDanCapaianSamaSamaNol(): void
    {
        $hasil = $this->hitung('trend_turun', [1 => 0], [1 => 0]);

        $this->assertSame(100.0, $hasil['percentage']);
    }

    /* ---------- 7. trend flat ---------- */
    public function testTrendFlatMemakaiTriwulanTerakhir(): void
    {
        $hasil = $this->hitung('trend_flat', [1 => 80, 2 => 80, 3 => 80, 4 => 80], [1 => 70, 2 => 76, 3 => 76]);

        $this->assertSame(95.0, $hasil['percentage']);
        $this->assertSame(3, $hasil['last_quarter']);
    }

    /* ---------- 8-9. akumulasi ---------- */
    public function testSumHanyaMenjumlahTargetTriwulanYangCapaiannyaTerisi(): void
    {
        $hasil = $this->hitung('sum', [1 => 10, 2 => 20, 3 => 30, 4 => 40], [1 => 8, 2 => 18]);

        // (8 + 18) / (10 + 20) x 100
        $this->assertSame(86.67, $hasil['percentage']);
        $this->assertSame(2, $hasil['filled_quarters_count']);
        $this->assertSame('Dihitung dari akumulasi 2 triwulan yang telah diisi.', $hasil['calculation_description']);
    }

    public function testSumSeluruhTriwulan(): void
    {
        $hasil = $this->hitung('sum', [1 => 10, 2 => 20, 3 => 30, 4 => 40], [1 => 10, 2 => 20, 3 => 30, 4 => 40]);

        $this->assertSame(100.0, $hasil['percentage']);
        $this->assertSame(4, $hasil['filled_quarters_count']);
    }

    public function testSumMelewatiTriwulanKosongDiTengah(): void
    {
        // Q2 kosong -> target Q2 TIDAK ikut dibagi
        $hasil = $this->hitung('sum', [1 => 10, 2 => 20, 3 => 30, 4 => 40], [1 => 10, 3 => 15]);

        // (10 + 15) / (10 + 30) x 100
        $this->assertSame(62.5, $hasil['percentage']);
    }

    public function testSumTotalTargetNolTidakMenghasilkanInfinity(): void
    {
        $hasil = $this->hitung('sum', [1 => 0, 2 => 0], [1 => 5, 2 => 5]);

        $this->assertNull($hasil['percentage']);
        $this->assertNotNull($hasil['error']);
        $this->assertSame('-', capaianFormatPersen($hasil['percentage']));
    }

    /* ---------- 10. angka 0 dianggap sudah diisi ---------- */
    public function testNolPadaQ1DianggapSudahDiisi(): void
    {
        $hasil = $this->hitung('trend_naik', [1 => 25, 2 => 50], [1 => 0]);

        $this->assertSame(1, $hasil['filled_quarters_count']);
        $this->assertSame(1, $hasil['last_quarter']);
        $this->assertSame(0.0, $hasil['percentage']);
    }

    public function testNolBertipeStringJugaDianggapSudahDiisi(): void
    {
        $hasil = $this->hitung('sum', [1 => 10], [1 => '0']);

        $this->assertSame(1, $hasil['filled_quarters_count']);
        $this->assertSame(0.0, $hasil['percentage']);
    }

    /* ---------- 11. Q1 terisi, Q2 kosong, Q3 terisi -> pakai Q3 ---------- */
    public function testTrendMemakaiQ3SaatQ2Kosong(): void
    {
        $hasil = $this->hitung('trend_naik', [1 => 25, 2 => 50, 3 => 75, 4 => 100], [1 => 20, 2 => null, 3 => 60]);

        $this->assertSame(3, $hasil['last_quarter']);
        $this->assertSame(80.0, $hasil['percentage']); // 60/75
    }

    /* ---------- Metode wajib dipilih ---------- */
    public function testMetodeKosongTidakMenghitung(): void
    {
        $hasil = $this->hitung(null, [1 => 10], [1 => 5]);

        $this->assertNull($hasil['percentage']);
        $this->assertSame('Metode perhitungan belum dipilih.', $hasil['error']);
    }

    public function testMetodeTidakDikenalDitolak(): void
    {
        $this->assertFalse(capaianMetodeValid('rata_rata'));
        $this->assertNull($this->hitung('rata_rata', [1 => 10], [1 => 5])['percentage']);
    }

    /* ---------- Target/capaian non-numerik ---------- */
    public function testTargetBerupaTeksMemunculkanValidasi(): void
    {
        $hasil = $this->hitung('trend_naik', [1 => 'tw 1', 2 => 'WTP'], [1 => 5]);

        $this->assertNull($hasil['percentage']);
        $this->assertStringContainsString('Target Triwulan I harus berupa angka', (string) $hasil['error']);
    }

    public function testCapaianBerupaTeksMemunculkanValidasi(): void
    {
        $hasil = $this->hitung('trend_naik', [1 => 10], [1 => 'Baik']);

        $this->assertNull($hasil['percentage']);
        $this->assertStringContainsString('Capaian Triwulan I harus berupa angka', (string) $hasil['error']);
    }

    public function testTargetNolPadaTrendNaikTidakMenghasilkanInfinity(): void
    {
        $hasil = $this->hitung('trend_naik', [1 => 0], [1 => 5]);

        $this->assertNull($hasil['percentage']);
        $this->assertNotNull($hasil['error']);
    }

    /* ---------- Parsing angka & format ---------- */
    public function testDesimalKomaDanTitikDibacaSama(): void
    {
        $this->assertSame(1.5, capaianToFloat('1,5'));
        $this->assertSame(1.5, capaianToFloat('1.5'));
        $this->assertSame(1234.5, capaianToFloat('1.234,5'));
        $this->assertSame(0.0, capaianToFloat('0'));
        $this->assertNull(capaianToFloat(''));
        $this->assertNull(capaianToFloat(null));
        $this->assertNull(capaianToFloat('WTP'));
    }

    public function testFormatPersenGayaIndonesia(): void
    {
        $this->assertSame('93,75%', capaianFormatPersen(93.75));
        $this->assertSame('100%', capaianFormatPersen(100));
        $this->assertSame('112,50%', capaianFormatPersen(112.5));
        $this->assertSame('86,67%', capaianFormatPersen('86.67')); // apa adanya dari DECIMAL
        $this->assertSame('-', capaianFormatPersen(null));
        $this->assertSame('-', capaianFormatPersen(''));
    }

    /* ---------- Satuan berpredikat (skala kode -> skor) ---------- */

    /** @return array<int, array<string, mixed>> skala Opini BPK */
    private function skalaOpiniBpk(): array
    {
        return [
            ['kode' => 'TMP', 'label' => 'Tidak Menyatakan Pendapat', 'nilai' => 1],
            ['kode' => 'TW',  'label' => 'Tidak Wajar',               'nilai' => 2],
            ['kode' => 'WDP', 'label' => 'Wajar Dengan Pengecualian', 'nilai' => 3],
            ['kode' => 'WTP', 'label' => 'Wajar Tanpa Pengecualian',  'nilai' => 4],
        ];
    }

    public function testPredikatDiterjemahkanKeSkor(): void
    {
        $hasil = calculateCapaianTotalPercentage(
            'trend_naik',
            [4 => 'WTP'],
            [4 => 'WDP'],
            $this->skalaOpiniBpk()
        );

        // WDP(3) / WTP(4) x 100
        $this->assertSame(75.0, $hasil['percentage']);
        $this->assertSame('75%', capaianFormatPersen($hasil['percentage']));
    }

    public function testPredikatSamaDenganTargetJadi100Persen(): void
    {
        $hasil = calculateCapaianTotalPercentage('trend_flat', [1 => 'WTP'], [1 => 'WTP'], $this->skalaOpiniBpk());

        $this->assertSame(100.0, $hasil['percentage']);
    }

    public function testPredikatTidakPedulikanBesarKecilHuruf(): void
    {
        $hasil = calculateCapaianTotalPercentage('trend_naik', [1 => 'wtp'], [1 => ' WdP '], $this->skalaOpiniBpk());

        $this->assertSame(75.0, $hasil['percentage']);
    }

    public function testPredikatMemakaiTriwulanTerakhirYangTerisi(): void
    {
        $hasil = calculateCapaianTotalPercentage(
            'trend_naik',
            [1 => 'WTP', 2 => 'WTP', 3 => 'WTP', 4 => 'WTP'],
            [1 => 'TW', 2 => null, 3 => 'WDP'],
            $this->skalaOpiniBpk()
        );

        $this->assertSame(3, $hasil['last_quarter']);
        $this->assertSame(75.0, $hasil['percentage']); // WDP(3)/WTP(4)
    }

    public function testKodeDiLuarSkalaDilaporkanBukanDihitung(): void
    {
        $hasil = calculateCapaianTotalPercentage('trend_naik', [1 => 'WTP'], [1 => 'ABC'], $this->skalaOpiniBpk());

        $this->assertNull($hasil['percentage']);
        $this->assertStringContainsString('tidak ada pada skala predikat', (string) $hasil['error']);
    }

    public function testTargetDiLuarSkalaDilaporkan(): void
    {
        $hasil = calculateCapaianTotalPercentage('trend_naik', [1 => 'sangat baik'], [1 => 'WTP'], $this->skalaOpiniBpk());

        $this->assertNull($hasil['percentage']);
        $this->assertStringContainsString('Target Triwulan I', (string) $hasil['error']);
    }

    public function testAngkaTetapDiterimaWalauSatuannyaBerpredikat(): void
    {
        // Data campuran: sebagian indikator lama menyimpan skor langsung.
        $hasil = calculateCapaianTotalPercentage('trend_naik', [1 => 4], [1 => 'WDP'], $this->skalaOpiniBpk());

        $this->assertSame(75.0, $hasil['percentage']);
    }

    public function testSkalaKosongTidakMengubahPerilakuLama(): void
    {
        $tanpa = calculateCapaianTotalPercentage('trend_naik', [1 => 25], [1 => 20]);
        $kosong = calculateCapaianTotalPercentage('trend_naik', [1 => 25], [1 => 20], []);

        $this->assertSame(80.0, $tanpa['percentage']);
        $this->assertSame($tanpa, $kosong);
    }

    public function testPetaSkalaMengabaikanKodeKosong(): void
    {
        $peta = capaianSkalaMap([
            ['kode' => 'WTP', 'nilai' => 4],
            ['kode' => '',    'nilai' => 9],
        ]);

        $this->assertSame(['wtp' => 4.0], $peta);
        $this->assertSame(4.0, capaianNilaiSkala('WTP', $peta));
        $this->assertNull(capaianNilaiSkala('WDP', $peta));
        $this->assertSame(12.0, capaianNilaiSkala('12', $peta), 'angka tetap dibaca sebagai angka');
    }

    /* ---------- Hasil sama: input periodik vs sekaligus ---------- */
    public function testInputPeriodikDanSekaligusMenghasilkanAngkaSama(): void
    {
        $targets = [1 => 25, 2 => 50, 3 => 75, 4 => 100];

        $sekaligus = $this->hitung('trend_naik', $targets, [1 => 20, 2 => 45, 3 => 70, 4 => 95]);

        // periodik: nilai yang sama diisi bertahap, hasil akhirnya identik
        $this->hitung('trend_naik', $targets, [1 => 20]);
        $this->hitung('trend_naik', $targets, [1 => 20, 2 => 45]);
        $this->hitung('trend_naik', $targets, [1 => 20, 2 => 45, 3 => 70]);
        $periodik = $this->hitung('trend_naik', $targets, [1 => 20, 2 => 45, 3 => 70, 4 => 95]);

        $this->assertSame($sekaligus['percentage'], $periodik['percentage']);
        $this->assertSame(95.0, $periodik['percentage']);
    }
}
