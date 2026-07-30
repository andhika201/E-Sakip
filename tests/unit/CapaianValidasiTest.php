<?php

use CodeIgniter\Test\CIUnitTestCase;

/**
 * Aturan validasi form Input Capaian.
 *
 * Aturannya CERMINAN dari PkRenaksiController::monevSave() — kalau di sana
 * berubah, sesuaikan di sini. Yang dijaga terutama: angka 0 harus LOLOS
 * (permit_empty di CI4 hanya melewatkan ''/null, bukan '0') dan teks predikat
 * ditolak karena field ini dipakai untuk menghitung persentase.
 *
 * @internal
 */
final class CapaianValidasiTest extends CIUnitTestCase
{
    private function rules(): array
    {
        helper('capaian');
        $rxAngka = 'regex_match[/^-?\d+([.,]\d+)?$/]';

        return [
            'target_rencana_id'  => 'required|integer',
            'metode_perhitungan' => 'required|in_list[' . implode(',', array_keys(capaianMetodeList())) . ']',
            'capaian_triwulan_1' => 'permit_empty|max_length[255]|' . $rxAngka,
            'capaian_triwulan_2' => 'permit_empty|max_length[255]|' . $rxAngka,
        ];
    }

    private function cek(array $data): array
    {
        $v = service('validation');
        $v->reset();
        $v->setRules($this->rules());

        return [$v->run($data), $v->getErrors()];
    }

    public function testNolLolosValidasiDanKosongDilewatkan(): void
    {
        [$lolos, $err] = $this->cek([
            'target_rencana_id'  => 1,
            'metode_perhitungan' => 'sum',
            'capaian_triwulan_1' => '0',
            'capaian_triwulan_2' => '',
        ]);

        $this->assertTrue($lolos, json_encode($err));
    }

    public function testDesimalTitikDanKomaLolos(): void
    {
        [$lolos, $err] = $this->cek([
            'target_rencana_id'  => 1,
            'metode_perhitungan' => 'trend_naik',
            'capaian_triwulan_1' => '4.25',
            'capaian_triwulan_2' => '1,5',
        ]);

        $this->assertTrue($lolos, json_encode($err));
    }

    public function testTeksPredikatDitolak(): void
    {
        [$lolos, $err] = $this->cek([
            'target_rencana_id'  => 1,
            'metode_perhitungan' => 'trend_naik',
            'capaian_triwulan_1' => 'WTP',
        ]);

        $this->assertFalse($lolos);
        $this->assertArrayHasKey('capaian_triwulan_1', $err);
    }

    public function testMetodeWajibDipilihDanHarusDikenal(): void
    {
        [$kosong] = $this->cek(['target_rencana_id' => 1, 'metode_perhitungan' => '']);
        $this->assertFalse($kosong);

        [$asing] = $this->cek(['target_rencana_id' => 1, 'metode_perhitungan' => 'rata_rata']);
        $this->assertFalse($asing);
    }
}
