<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\UserModel;

class LoginController extends BaseController
{
    /**
     * Tampilkan halaman login.
     * Jika sudah login, redirect ke dashboard sesuai role.
     */
    public function index()
    {
        if (session()->get('isLoggedIn')) {
            return $this->redirectByRole(session()->get('role'));
        }

        return view('login');
    }

    /**
     * Proses autentikasi login.
     */
    public function authenticate()
    {
        // Validasi input
        $rules = [
            'username' => 'required|min_length[4]',
            'password' => 'required|min_length[6]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()
                ->with('_ci_old_input', $this->isianLamaTanpaPassword())
                ->with('error', 'Username minimal 4 karakter dan password minimal 6 karakter.');
        }

        $username = $this->request->getPost('username');
        $password = $this->request->getPost('password');

        // =============================================================
        // PEMBATASAN PERCOBAAN (brute force)
        //
        // Per pasangan IP + username: 5 percobaan per menit. Tanpa ini,
        // tebakan kata sandi bisa diulang tanpa batas — dan kode TOTP 2FA
        // (6 digit) bisa dihabiskan ruang pencariannya.
        // =============================================================
        $throttler = service('throttler');
        $kunci     = 'login_' . md5($this->request->getIPAddress() . '|' . strtolower((string) $username));

        if ($throttler->check($kunci, 5, MINUTE) === false) {
            log_activity('login_gagal', 'auth', 'Login dibatasi (terlalu banyak percobaan): ' . $username, [
                'user_id'  => null,
                'username' => $username,
                'role'     => null,
            ]);

            return redirect()->back()
                ->with('_ci_old_input', $this->isianLamaTanpaPassword())
                ->with('error', 'Terlalu banyak percobaan login. Coba lagi dalam satu menit.');
        }

        // Cari user di database
        $userModel = model(UserModel::class);
        $user      = $userModel->where('username', $username)->first();

        // Verifikasi password
        if ($user && password_verify($password, $user['password'])) {
            // Tolak akun yang dinonaktifkan (cek SEBELUM alur 2FA agar tidak bisa dilanjutkan)
            if (empty($user['is_active'])) {
                log_activity('login_gagal', 'auth', 'Login ditolak (akun nonaktif): ' . $username, [
                    'user_id'  => $user['user_id'],
                    'username' => $username,
                    'role'     => $user['role'] ?? null,
                ]);
                return redirect()->back()
                    ->with('_ci_old_input', $this->isianLamaTanpaPassword())
                    ->with('error', 'Akun Anda dinonaktifkan. Hubungi administrator.');
            }

            // Bila 2FA aktif: tunda login, minta kode authenticator dulu
            if (!empty($user['two_factor_enabled'])) {
                session()->set('twofa_user_id', $user['user_id']);
                return redirect()->to('2fa/verify');
            }

            // Simpan data sesi — JANGAN simpan password. ID sesi diganti
            // (lihat mulai_sesi_login()).
            mulai_sesi_login($user);
            $throttler->remove($kunci);

            log_activity('login', 'auth', 'Login berhasil');

            return $this->redirectByRole($user['role']);
        }

        log_activity('login_gagal', 'auth', 'Login gagal untuk username: ' . $username, [
            'user_id'  => null,
            'username' => $username,
            'role'     => null,
        ]);

        return redirect()->back()
            ->with('_ci_old_input', $this->isianLamaTanpaPassword())
            ->with('error', 'Username atau password salah.');
    }

    /**
     * Logout: hancurkan sesi dan redirect ke login.
     */
    public function logout()
    {
        log_activity('logout', 'auth', 'Logout');
        session()->destroy();
        return redirect()->to('/login')->with('message', 'Anda telah berhasil logout.');
    }

    /**
     * Helper: redirect berdasarkan role user.
     * Satu-satunya tempat mapping role → URL.
     */
    private function redirectByRole(string $role)
    {
        $tujuan = dashboard_path_by_role($role); // pemetaan tunggal di rbac_helper

        if ($tujuan === null) {
            session()->destroy();
            return redirect()->to('/login')->with('error', 'Role tidak dikenali. Hubungi administrator.');
        }

        return redirect()->to($tujuan);
    }

    /**
     * Isian lama untuk form login: HANYA username.
     * MENGAPA bukan withInput(): withInput() menyimpan seluruh POST — termasuk
     * password dalam teks biasa — ke berkas sesi di server (writable/session).
     */
    private function isianLamaTanpaPassword(): array
    {
        return ['get' => [], 'post' => ['username' => (string) $this->request->getPost('username')]];
    }
}
