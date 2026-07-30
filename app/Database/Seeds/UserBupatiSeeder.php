<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * AKUN pengguna untuk role `bupati`.
 *
 *   php spark db:seed UserBupatiSeeder
 *
 * Password TIDAK PERNAH ditulis di dalam kode:
 *   1. bila variabel environment BUPATI_PASSWORD diisi -> nilai itu yang dipakai;
 *   2. bila tidak -> digenerate acak (kriptografis) lalu DICETAK SEKALI ke layar.
 * Kata sandi hanya disimpan sebagai hash `password_hash()` di kolom `users.password`.
 *
 * Contoh menentukan sendiri kata sandinya (Git Bash):
 *   BUPATI_PASSWORD='KataSandiPilihanAnda' php spark db:seed UserBupatiSeeder
 *
 * IDEMPOTEN & TIDAK MERUSAK:
 *   - bila akun sudah ada, seeder HANYA melaporkannya dan TIDAK menimpa
 *     password / email / status aktifnya. Untuk mengganti kata sandi akun yang
 *     sudah ada, jalankan dengan RESET_BUPATI_PASSWORD=1.
 *
 * Catatan penting: `opd_id` HARUS NULL. Bupati adalah peran lintas Perangkat
 * Daerah; bila diisi salah satu OPD, pemeriksaan lintas-OPD
 * (BaseController::canAccessOpd) akan menguncinya ke OPD tersebut.
 *
 * Jalankan RoleBupatiSeeder lebih dulu supaya role & permission-nya ada.
 */
class UserBupatiSeeder extends Seeder
{
    private const USERNAME = 'bupati';
    private const EMAIL    = 'bupati@pringsewukab.go.id';

    /** Kata sandi acak yang mudah dibaca ulang (tanpa karakter ambigu). */
    private static function passwordAcak(int $panjang = 16): string
    {
        $abjad = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789';
        $simbol = '!@#$%&*?';
        $hasil = '';
        for ($i = 0; $i < $panjang - 2; $i++) {
            $hasil .= $abjad[random_int(0, strlen($abjad) - 1)];
        }
        // Sisipkan satu simbol & satu angka agar cukup kuat.
        $hasil .= $simbol[random_int(0, strlen($simbol) - 1)];
        $hasil .= (string) random_int(0, 9);

        return $hasil;
    }

    public function run()
    {
        if (!$this->db->tableExists('users')) {
            echo "Tabel users belum ada.\n";

            return;
        }

        // Role harus sudah terdaftar (AuthFilter & Master Data memakai tabel roles).
        $adaRole = $this->db->tableExists('roles')
            && $this->db->table('roles')->where('name', RoleBupatiSeeder::ROLE)->countAllResults() > 0;
        if (!$adaRole) {
            echo "Role '" . RoleBupatiSeeder::ROLE . "' belum ada. Jalankan dulu:\n";
            echo "  php spark db:seed RoleBupatiSeeder\n";

            return;
        }

        $reset = (string) (getenv('RESET_BUPATI_PASSWORD') ?: '') !== '';
        $lama  = $this->db->table('users')
            ->where('username', self::USERNAME)
            ->orWhere('email', self::EMAIL)
            ->get()->getRowArray();

        // --- Akun sudah ada ---
        if ($lama && !$reset) {
            echo "Akun '" . self::USERNAME . "' SUDAH ADA (user_id={$lama['user_id']}, role={$lama['role']}"
                . ", aktif=" . ((int) $lama['is_active']) . "). Password TIDAK diubah.\n";
            if ($lama['role'] !== RoleBupatiSeeder::ROLE) {
                echo "PERHATIAN: role akun ini '{$lama['role']}', bukan '" . RoleBupatiSeeder::ROLE . "'.\n";
                echo "Ubah lewat Master Data > User bila memang akun ini yang dipakai Bupati.\n";
            }
            echo "Untuk mengatur ulang kata sandinya: RESET_BUPATI_PASSWORD=1 php spark db:seed UserBupatiSeeder\n";

            return;
        }

        $dariEnv  = (string) (getenv('BUPATI_PASSWORD') ?: '');
        $password = $dariEnv !== '' ? $dariEnv : self::passwordAcak();

        if (strlen($password) < 6) {
            echo "BUPATI_PASSWORD minimal 6 karakter (aturan validasi login).\n";

            return;
        }

        $isi = [
            'password'   => password_hash($password, PASSWORD_DEFAULT),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        if ($lama) {
            // Reset kata sandi akun yang sudah ada — kolom lain tidak disentuh.
            $this->db->table('users')->where('user_id', (int) $lama['user_id'])->update($isi);
            echo "Kata sandi akun '" . self::USERNAME . "' (user_id={$lama['user_id']}) telah diatur ulang.\n";
        } else {
            $this->db->table('users')->insert($isi + [
                'username'   => self::USERNAME,
                'email'      => self::EMAIL,
                'role'       => RoleBupatiSeeder::ROLE,
                // WAJIB NULL: peran lintas Perangkat Daerah.
                'opd_id'     => null,
                'is_active'  => 1,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            echo "Akun Bupati dibuat (user_id=" . $this->db->insertID() . ").\n";
        }

        echo str_repeat('=', 62) . "\n";
        echo "  Username : " . self::USERNAME . "\n";
        echo "  Email    : " . self::EMAIL . "\n";
        if ($dariEnv !== '') {
            echo "  Password : (sesuai BUPATI_PASSWORD yang Anda tentukan)\n";
        } else {
            echo "  Password : {$password}\n";
            echo "  ^ CATAT SEKARANG. Tidak dapat ditampilkan lagi (disimpan sebagai hash).\n";
        }
        echo str_repeat('=', 62) . "\n";
        echo "Langkah berikutnya: login di /login lalu ganti kata sandi di menu\n";
        echo "Profil Saya, dan aktifkan 2FA bila diperlukan.\n";
    }
}
