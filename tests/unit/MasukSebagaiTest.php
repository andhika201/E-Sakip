<?php

use App\Services\MasukSebagaiService;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * AKSARA+ — aturan murni "Masuk sebagai": siapa boleh memakai, akun mana yang boleh dimasuki.
 *
 * @internal
 */
final class MasukSebagaiTest extends CIUnitTestCase
{
    public function testHanyaAdminKabDanSuperAdminBolehMemulai(): void
    {
        $this->assertTrue(MasukSebagaiService::bolehMemulai('admin_kab'));
        $this->assertTrue(MasukSebagaiService::bolehMemulai('admin'));

        foreach (['admin_opd', 'admin_kecamatan', 'bupati', 'admin_inspektorat', '', null] as $peran) {
            $this->assertFalse(MasukSebagaiService::bolehMemulai($peran), 'peran ' . var_export($peran, true));
        }
    }

    public function testAkunYangBolehDimasuki(): void
    {
        $akun = static fn (int $id, string $role, int $aktif = 1) => ['user_id' => $id, 'role' => $role, 'is_active' => $aktif];

        foreach (['admin_opd', 'admin_kecamatan', 'bupati', 'admin_inspektorat'] as $i => $role) {
            $this->assertTrue(MasukSebagaiService::bolehDitiru($akun(10 + $i, $role), 2), $role);
        }

        $this->assertFalse(MasukSebagaiService::bolehDitiru($akun(2, 'admin_opd'), 2), 'akun sendiri');
        $this->assertFalse(MasukSebagaiService::bolehDitiru($akun(20, 'admin_opd', 0), 2), 'akun nonaktif');
        $this->assertFalse(MasukSebagaiService::bolehDitiru($akun(1, 'admin'), 2), 'tidak boleh naik ke Super Admin');
        $this->assertFalse(MasukSebagaiService::bolehDitiru($akun(6, 'admin_kab'), 2), 'sesama Admin Kabupaten');
    }
}
