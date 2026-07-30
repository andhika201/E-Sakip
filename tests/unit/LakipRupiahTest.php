<?php

use CodeIgniter\Test\CIUnitTestCase;

/**
 * Konversi nominal Rupiah pada Efisiensi Program LAKIP.
 *
 * @see app/Helpers/format_helper.php (parseRupiah / formatRupiah)
 *
 * @internal
 */
final class LakipRupiahTest extends CIUnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        helper('format');
    }

    /* ---------- Test case 13 & 14: tampilan Rupiah -> angka murni ---------- */
    public function testTeksRupiahJadiAngkaMurni(): void
    {
        $this->assertSame(150000000.0, parseRupiah('Rp 150.000.000'));
        $this->assertSame(10000000.0, parseRupiah('Rp 10.000.000'));
        $this->assertSame(160176881557.0, parseRupiah('Rp 160.176.881.557'));
    }

    public function testAngkaTanpaFormatTetapDiterima(): void
    {
        $this->assertSame(150000000.0, parseRupiah('150000000'));
        $this->assertSame(150000000.0, parseRupiah(150000000));
    }

    public function testKomaDibacaSebagaiDesimal(): void
    {
        $this->assertSame(150000000.5, parseRupiah('150.000.000,50'));
    }

    /* ---------- Test case 19: nilai 0 valid ---------- */
    public function testNolValidDanBukanKosong(): void
    {
        $this->assertSame(0.0, parseRupiah('0'));
        $this->assertSame(0.0, parseRupiah('Rp 0'));
        $this->assertNotFalse(parseRupiah('0'));
    }

    public function testKosongJadiNullBukanNol(): void
    {
        $this->assertNull(parseRupiah(''));
        $this->assertNull(parseRupiah('   '));
        $this->assertNull(parseRupiah(null));
    }

    public function testNegatifDanNonAngkaDitolak(): void
    {
        $this->assertFalse(parseRupiah('-5000'));
        $this->assertFalse(parseRupiah('Rp -5.000'));
        $this->assertFalse(parseRupiah('seratus juta'));
        $this->assertFalse(parseRupiah('<script>'));
    }

    /* ---------- Bolak-balik: format -> parse kembali utuh ---------- */
    public function testFormatLaluParseKembaliUtuh(): void
    {
        foreach ([0, 1500, 150000000, 160176881557] as $angka) {
            $teks = formatRupiah($angka);
            $this->assertSame((float) $angka, parseRupiah($teks), "gagal pada {$angka} ({$teks})");
        }
    }

    public function testFormatRupiahNullJadiStrip(): void
    {
        $this->assertSame('-', formatRupiah(null));
        $this->assertSame('-', formatRupiah(''));
        $this->assertSame('Rp 150.000.000', formatRupiah(150000000));
    }
}
