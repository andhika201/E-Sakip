<?php

use CodeIgniter\Test\CIUnitTestCase;

/**
 * Rumus IKP (Indikator Kinerja Prioritas): parser angka, rekap triwulan,
 * capaian bulanan, Bagi Rata, dan pemeriksa konsistensi.
 *
 * Angka harapan DLH diambil dari seed demo (ikp_bulanan_2026.csv) dengan
 * aturan AKSARA: hanya bulan yang realisasinya terisi yang ikut dihitung
 * (lihat riset kritik.md §2 A3 / C16).
 *
 * @see app/Helpers/ikp_helper.php
 *
 * @internal
 */
final class IkpRekapTest extends CIUnitTestCase
{
    /** DLH-01 "Jumlah nasabah aktif bank sampah" (trend_naik, posisi kumulatif). */
    private const DLH01_TARGET = [1 => 215, 230, 245, 260, 275, 290, 310, 330, 350, 365, 385, 400];
    private const DLH01_REAL   = [1 => 212, 226, 241, 262, 270, 281, 305, 318, null, null, null, null];

    /** DLH-02 "Berat sampah yang dikelola bank sampah" (sum, ton per bulan). */
    private const DLH02_TARGET = [1 => 1.0, 1.0, 1.1, 1.2, 1.2, 1.2, 1.3, 1.3, 1.3, 1.4, 1.4, 1.6];
    private const DLH02_REAL   = [1 => 0.9, 1.1, 1.0, 1.3, 1.1, 1.2, 1.4, 1.2, null, null, null, null];

    protected function setUp(): void
    {
        parent::setUp();
        helper('ikp');
    }

    /* =========================== ikp_angka_baca / sah =========================== */

    public function testAngkaBacaTitikRibuan(): void
    {
        $this->assertSame(302663.0, ikp_angka_baca('302.663'));
        $this->assertSame(1234567.0, ikp_angka_baca('1.234.567'));
    }

    public function testAngkaBacaKomaDesimal(): void
    {
        $this->assertSame(24.65, ikp_angka_baca('24,65'));
        $this->assertSame(1234.5, ikp_angka_baca('1.234,5'));
        $this->assertSame(0.9, ikp_angka_baca('0,9'));
    }

    public function testAngkaBacaTitikDesimalBukanPolaRibuan(): void
    {
        $this->assertSame(2.5, ikp_angka_baca('2.5'));
        $this->assertSame(1.25, ikp_angka_baca('1.25'));
    }

    public function testAngkaBacaPersenDanSpasi(): void
    {
        $this->assertSame(90.0, ikp_angka_baca('90%'));
        $this->assertSame(90.5, ikp_angka_baca(" 90,5 %\u{00A0}"));
    }

    public function testAngkaBacaKosongDanStrip(): void
    {
        $this->assertNull(ikp_angka_baca('-'));
        $this->assertNull(ikp_angka_baca(''));
        $this->assertNull(ikp_angka_baca('  '));
        $this->assertNull(ikp_angka_baca(null));
        $this->assertNull(ikp_angka_baca('—'));
        $this->assertNull(ikp_angka_baca('-', false));
    }

    public function testAngkaBacaNolDuaModus(): void
    {
        // Target: nol = belum ada target.
        $this->assertNull(ikp_angka_baca('0'));
        $this->assertNull(ikp_angka_baca('0,00'));
        // Realisasi: nol adalah nilai sah.
        $this->assertSame(0.0, ikp_angka_baca('0', false));
        $this->assertSame(0.0, ikp_angka_baca('0,0', false));
    }

    public function testAngkaBacaBukanAngka(): void
    {
        $this->assertNull(ikp_angka_baca('Nindya'));
        $this->assertNull(ikp_angka_baca('1.2.3'));
    }

    public function testAngkaSah(): void
    {
        foreach (['400', '302.663', '1.234,5', '24,65', '2.5', '90%', '90 %', '-', '', '0'] as $ok) {
            $this->assertTrue(ikp_angka_sah($ok), "Harus sah: '{$ok}'");
        }
        foreach (['Nindya', '1.2.3', 'abc12', '1 234', '-5', '12,5,3'] as $salah) {
            $this->assertFalse(ikp_angka_sah($salah), "Harus ditolak: '{$salah}'");
        }
    }

    public function testFmtBolakBalik(): void
    {
        $this->assertSame('302.663', ikp_fmt(302663.0));
        $this->assertSame('1.234,5', ikp_fmt(1234.5));
        $this->assertSame('98,37', ikp_fmt(98.3673));
        $this->assertSame('1', ikp_fmt(1.0));
        $this->assertSame('0', ikp_fmt(0.0));
        $this->assertSame('-', ikp_fmt(null));
        $this->assertSame('1,2346', ikp_fmt(1.23456, 4));
        foreach ([302663.0, 1234.5, 24.65, 0.9, 1.25, 1000.0] as $v) {
            $this->assertSame($v, ikp_angka_baca(ikp_fmt($v, 4)), 'Bolak-balik ' . $v);
        }
    }

    /* =========================== ikp_nilai_triwulan =========================== */

    public function testNilaiTriwulanTrendAmbilBulanTerakhirTerisi(): void
    {
        $tw = ikp_nilai_triwulan(self::DLH01_REAL, 'trend_naik');
        $this->assertSame([1 => 241.0, 2 => 281.0, 3 => 318.0, 4 => null], $tw);

        $twT = ikp_nilai_triwulan(self::DLH01_TARGET, 'trend_naik');
        $this->assertSame([1 => 245.0, 2 => 290.0, 3 => 350.0, 4 => 400.0], $twT);
    }

    public function testNilaiTriwulanSumMenjumlahBulanTerisi(): void
    {
        $tw = ikp_nilai_triwulan(self::DLH02_REAL, 'sum');
        $this->assertEqualsWithDelta(3.0, $tw[1], 1e-9);
        $this->assertEqualsWithDelta(3.6, $tw[2], 1e-9);
        $this->assertEqualsWithDelta(2.6, $tw[3], 1e-9);
        $this->assertNull($tw[4]);

        $twT = ikp_nilai_triwulan(self::DLH02_TARGET, 'sum');
        $this->assertEqualsWithDelta(3.1, $twT[1], 1e-9);
        $this->assertEqualsWithDelta(15.0, array_sum($twT), 1e-9);
    }

    public function testNilaiTriwulanNolTerisiDanKunciHilang(): void
    {
        $tw = ikp_nilai_triwulan([2 => 0.0, 5 => 4], 'sum');
        $this->assertSame([1 => 0.0, 2 => 4.0, 3 => null, 4 => null], $tw);
    }

    public function testNilaiTriwulanTanpaMetodeKosong(): void
    {
        $this->assertSame([1 => null, 2 => null, 3 => null, 4 => null], ikp_nilai_triwulan(self::DLH02_REAL, ''));
    }

    /* =========================== ikp_capaian (DLH) =========================== */

    public function testCapaianDlh01PerTriwulan(): void
    {
        $tw1 = ikp_capaian('trend_naik', self::DLH01_TARGET, self::DLH01_REAL, 1, 3);
        $this->assertSame('calculated', $tw1['status']);
        $this->assertSame(98.37, $tw1['percentage']); // 241/245

        $tw2 = ikp_capaian('trend_naik', self::DLH01_TARGET, self::DLH01_REAL, 4, 6);
        $this->assertSame(96.9, $tw2['percentage']); // 281/290

        // TW III berjalan: Juli & Agustus terisi -> posisi Agustus 318/330.
        $tw3 = ikp_capaian('trend_naik', self::DLH01_TARGET, self::DLH01_REAL, 7, 9);
        $this->assertSame(96.36, $tw3['percentage']);
        $this->assertSame(8, $tw3['bulan_terakhir']);

        $tw4 = ikp_capaian('trend_naik', self::DLH01_TARGET, self::DLH01_REAL, 10, 12);
        $this->assertNull($tw4['percentage']);
        $this->assertNull($tw4['error']);
        $this->assertNull($tw4['bulan_terakhir']);

        $ytd = ikp_capaian('trend_naik', self::DLH01_TARGET, self::DLH01_REAL);
        $this->assertSame(96.36, $ytd['percentage']);
    }

    public function testCapaianDlh02PerTriwulanDanTahunBerjalan(): void
    {
        $tw1 = ikp_capaian('sum', self::DLH02_TARGET, self::DLH02_REAL, 1, 3);
        $this->assertSame(96.77, $tw1['percentage']); // 3,0 / 3,1

        $tw2 = ikp_capaian('sum', self::DLH02_TARGET, self::DLH02_REAL, 4, 6);
        $this->assertSame(100.0, $tw2['percentage']); // 3,6 / 3,6

        // TW III berjalan: hanya target Juli & Agustus (2,6) yang dipakai, bukan 3,9.
        $tw3 = ikp_capaian('sum', self::DLH02_TARGET, self::DLH02_REAL, 7, 9);
        $this->assertSame(100.0, $tw3['percentage']);

        $ytd = ikp_capaian('sum', self::DLH02_TARGET, self::DLH02_REAL);
        $this->assertSame(98.92, $ytd['percentage']); // 9,2 / 9,3
        $this->assertSame(8, $ytd['bulan_terisi']);
    }

    public function testCapaianRealisasiNolTetapDihitung(): void
    {
        $h = ikp_capaian('sum', [1 => 5, 2 => 5], [1 => 0.0, 2 => null], 1, 3);
        $this->assertSame('calculated', $h['status']);
        $this->assertSame(0.0, $h['percentage']);
    }

    public function testCapaianTrendTurunDanTargetKosong(): void
    {
        // Makin rendah makin baik: target 42 / realisasi 40 = 105%.
        $h = ikp_capaian('trend_turun', [1 => 42], [1 => 40], 1, 3);
        $this->assertSame(105.0, $h['percentage']);

        // Realisasi ada, target bulan itu kosong -> incomplete dengan pesan bulan.
        $g = ikp_capaian('sum', [1 => null], [1 => 3], 1, 3);
        $this->assertSame('incomplete', $g['status']);
        $this->assertStringContainsString('Januari', (string) $g['error']);

        $m = ikp_capaian('', [1 => 1], [1 => 1], 1, 3);
        $this->assertNotNull($m['error']);
    }

    public function testCapaianTargetNolBelumDapatDinilai(): void
    {
        $h = ikp_capaian('trend_naik', [1 => 0.0], [1 => 3], 1, 3);
        $this->assertSame('not_evaluable', $h['status']);
    }

    /* =========================== ikp_bagi_rata =========================== */

    public function testBagiRataSumBulat(): void
    {
        $this->assertSame([1 => 80.0, 2 => 80.0, 3 => 80.0, 4 => 80.0, 5 => 80.0], ikp_bagi_rata(400, 5, 'sum', null, true));

        // Sisa digeser ke slot awal, tidak ada pecahan orang.
        $r = ikp_bagi_rata(346, 5, 'sum', null, true);
        $this->assertSame([1 => 70.0, 2 => 69.0, 3 => 69.0, 4 => 69.0, 5 => 69.0], $r);
        $this->assertSame(346.0, array_sum($r));

        $r = ikp_bagi_rata(8, 12, 'sum', null, true);
        $this->assertSame(8.0, array_sum($r));
        $this->assertSame(1.0, $r[1]);
        $this->assertSame(0.0, $r[12]);
    }

    public function testBagiRataSumDesimal(): void
    {
        $r = ikp_bagi_rata(15, 12, 'sum');
        $this->assertCount(12, $r);
        foreach ($r as $v) {
            $this->assertSame(1.25, $v);
        }

        $r = ikp_bagi_rata(10, 3, 'sum');
        $this->assertSame([1 => 3.34, 2 => 3.33, 3 => 3.33], $r);
        $this->assertEqualsWithDelta(10.0, array_sum($r), 1e-9);

        $r = ikp_bagi_rata(0.1, 12, 'sum');
        $this->assertEqualsWithDelta(0.1, array_sum($r), 1e-9);
        $this->assertGreaterThan(0.0, $r[12]);
    }

    public function testBagiRataTrend(): void
    {
        $r = ikp_bagi_rata(1000, 5, 'trend_naik', 0.0, true);
        $this->assertSame([1 => 200.0, 2 => 400.0, 3 => 600.0, 4 => 800.0, 5 => 1000.0], $r);

        $r = ikp_bagi_rata(400, 12, 'trend_naik', 200.0, true);
        $this->assertSame(400.0, $r[12]);
        $this->assertSame(217.0, $r[1]);
        $this->assertTrue(ikp_cek('trend_naik', 400, $r)['ok']);

        $r = ikp_bagi_rata(42, 4, 'trend_turun', 50.0);
        $this->assertSame([1 => 48.0, 2 => 46.0, 3 => 44.0, 4 => 42.0], $r);

        // Tanpa titik awal: tidak mengarang, semua = target.
        $this->assertSame([1 => 90.0, 2 => 90.0, 3 => 90.0], ikp_bagi_rata(90, 3, 'trend_naik'));
    }

    public function testBagiRataFlatDanTanpaMetode(): void
    {
        $this->assertSame(array_fill(1, 12, 1.0), ikp_bagi_rata(1, 12, 'trend_flat'));
        $this->assertSame([], ikp_bagi_rata(10, 3, ''));
        $this->assertSame([], ikp_bagi_rata(10, 0, 'sum'));
    }

    /* =========================== ikp_cek =========================== */

    public function testCekSum(): void
    {
        $ok = ikp_cek('sum', 15.0, self::DLH02_TARGET);
        $this->assertTrue($ok['ok']);
        $this->assertSame('cocok', $ok['status']);

        $kurang = ikp_cek('sum', 400.0, [1 => 80, 2 => 80, 3 => 80, 4 => 80, 5 => 70]);
        $this->assertFalse($kurang['ok']);
        $this->assertEqualsWithDelta(-10.0, $kurang['selisih'], 1e-9);
        $this->assertStringContainsString('kurang', $kurang['pesan']);

        // Toleransi 0,005.
        $this->assertTrue(ikp_cek('sum', 10.0, [1 => 3.333, 2 => 3.333, 3 => 3.336])['ok']);
    }

    public function testCekTrendNaik(): void
    {
        $this->assertTrue(ikp_cek('trend_naik', 400.0, self::DLH01_TARGET)['ok']);
        $this->assertTrue(ikp_cek('trend_naik', 1000.0, [2025 => 200, 2026 => 400, 2027 => 600, 2028 => 800, 2029 => 1000])['ok']);

        $turun = ikp_cek('trend_naik', 400.0, [1 => 215, 2 => 210, 3 => 400]);
        $this->assertFalse($turun['ok']);

        $akhirBeda = ikp_cek('trend_naik', 400.0, [1 => 215, 2 => 230, 3 => 390]);
        $this->assertFalse($akhirBeda['ok']);
        $this->assertEqualsWithDelta(-10.0, $akhirBeda['selisih'], 1e-9);

        $akhirKosong = ikp_cek('trend_naik', 400.0, [1 => 215, 2 => 230, 3 => null]);
        $this->assertFalse($akhirKosong['ok']);
    }

    public function testCekTrendTurunDanFlat(): void
    {
        $this->assertTrue(ikp_cek('trend_turun', 42.0, [1 => 50, 2 => 46, 3 => 42])['ok']);
        $this->assertFalse(ikp_cek('trend_turun', 42.0, [1 => 44, 2 => 46, 3 => 42])['ok']);

        // Kolom 2025 kosong ("sudah lewat") tidak menggagalkan flat.
        $this->assertTrue(ikp_cek('trend_flat', 1.0, [2025 => null, 2026 => 1, 2027 => 1, 2028 => 1, 2029 => 1])['ok']);
        $this->assertFalse(ikp_cek('trend_flat', 90.0, [2026 => 80, 2027 => 85, 2028 => 90, 2029 => 90])['ok']);
    }

    public function testCekKeadaanTakTerperiksa(): void
    {
        $this->assertSame('tanpa_induk', ikp_cek('sum', null, [1 => 5])['status']);
        $this->assertSame('kosong', ikp_cek('sum', 5.0, [1 => null, 2 => null])['status']);
        $this->assertSame('tanpa_metode', ikp_cek('', 5.0, [1 => 5])['status']);
    }

    /* =========================== lain-lain =========================== */

    public function testSatuanBulat(): void
    {
        $this->assertTrue(ikp_satuan_bulat('Orang'));
        $this->assertTrue(ikp_satuan_bulat('Unit Sekolah'));
        $this->assertTrue(ikp_satuan_bulat('Dokumen'));
        $this->assertFalse(ikp_satuan_bulat('Ton'));
        $this->assertFalse(ikp_satuan_bulat('Persen'));
        $this->assertFalse(ikp_satuan_bulat('%'));
        // "ha"/"rp" hanya sebagai kata utuh: bukan perusahaan, usaha, kelurahan, perpustakaan.
        $this->assertTrue(ikp_satuan_bulat('Perusahaan'));
        $this->assertTrue(ikp_satuan_bulat('Pelaku Usaha'));
        $this->assertTrue(ikp_satuan_bulat('Pekon/kelurahan'));
        $this->assertFalse(ikp_satuan_bulat('ha'));
        $this->assertFalse(ikp_satuan_bulat('Hektar'));
        $this->assertFalse(ikp_satuan_bulat('Rp'));
    }

    public function testStatusMemakaiAmbangDashboard(): void
    {
        $st = ikp_status(ikp_capaian('sum', self::DLH02_TARGET, self::DLH02_REAL));
        $this->assertTrue($st['numeric']);
        $this->assertSame('hijau', $st['kelompok']); // 98,92% ada di rentang "Tercapai" bawaan

        $abu = ikp_status(ikp_capaian('sum', self::DLH02_TARGET, [], 1, 3));
        $this->assertSame('belum_ada_data', $abu['code']);
        $this->assertSame('abu', $abu['kelompok']);
    }
}
