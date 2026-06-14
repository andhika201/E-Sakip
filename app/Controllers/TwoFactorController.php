<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Libraries\Totp;

/**
 * Autentikasi Dua Faktor (TOTP authenticator).
 * - setup/enable/disable : kelola 2FA (butuh login).
 * - verify/verifyPost    : langkah kedua saat login (sebelum sesi penuh).
 */
class TwoFactorController extends BaseController
{
    private const ISSUER = 'e-SAKIP Pringsewu';

    private function db()
    {
        return \Config\Database::connect();
    }

    private function tplPrefix(?string $role): string
    {
        return $role === 'admin_opd' ? 'adminOpd' : 'adminKabupaten';
    }

    /* ===================== KELOLA 2FA ===================== */

    public function setup()
    {
        $role   = session()->get('role');
        $userId = session()->get('user_id');
        $user   = $this->db()->table('users')->where('user_id', $userId)->get()->getRowArray();

        if (!$user) {
            return redirect()->to('/login');
        }
        if (!empty($user['two_factor_enabled'])) {
            return redirect()->to('change-password')->with('error', '2FA sudah aktif.');
        }

        // secret sementara disimpan di sesi sampai dikonfirmasi
        $secret = session()->get('twofa_pending') ?: Totp::generateSecret();
        session()->set('twofa_pending', $secret);

        $label   = $user['username'] ?? 'user';
        $otpauth = Totp::otpauthUri($secret, $label, self::ISSUER);

        return view('two_factor_setup', [
            'role'    => $role,
            'tpl'     => $this->tplPrefix($role),
            'secret'  => $secret,
            'otpauth' => $otpauth,
        ]);
    }

    public function enable()
    {
        $userId = session()->get('user_id');
        $secret = session()->get('twofa_pending');
        $code   = (string) $this->request->getPost('code');

        if (!$secret) {
            return redirect()->to('2fa/setup')->with('error', 'Sesi setup kedaluwarsa, silakan ulangi.');
        }
        if (!Totp::verify($secret, $code)) {
            return redirect()->to('2fa/setup')->with('error', 'Kode verifikasi salah. Pastikan jam perangkat tepat lalu coba lagi.');
        }

        $this->db()->table('users')->where('user_id', $userId)->update([
            'two_factor_secret'  => $secret,
            'two_factor_enabled' => 1,
        ]);
        session()->remove('twofa_pending');

        helper('activity');
        log_activity('2fa_aktif', 'auth', 'Mengaktifkan 2FA');

        return redirect()->to('change-password')->with('success', '2FA berhasil diaktifkan.');
    }

    public function disable()
    {
        $userId   = session()->get('user_id');
        $password = (string) $this->request->getPost('password');
        $user     = $this->db()->table('users')->where('user_id', $userId)->get()->getRowArray();

        if (!$user || !password_verify($password, $user['password'])) {
            return redirect()->to('change-password')->with('error', 'Password salah. 2FA tidak dinonaktifkan.');
        }

        $this->db()->table('users')->where('user_id', $userId)->update([
            'two_factor_enabled' => 0,
            'two_factor_secret'  => null,
        ]);

        helper('activity');
        log_activity('2fa_nonaktif', 'auth', 'Menonaktifkan 2FA');

        return redirect()->to('change-password')->with('success', '2FA dinonaktifkan.');
    }

    /* ===================== LANGKAH LOGIN ===================== */

    public function verify()
    {
        if (!session()->get('twofa_user_id')) {
            return redirect()->to('/login');
        }
        return view('two_factor_verify');
    }

    public function verifyPost()
    {
        $pendingId = session()->get('twofa_user_id');
        if (!$pendingId) {
            return redirect()->to('/login');
        }

        $user = $this->db()->table('users')->where('user_id', $pendingId)->get()->getRowArray();
        $code = (string) $this->request->getPost('code');

        if (!$user || empty($user['two_factor_secret']) || !Totp::verify($user['two_factor_secret'], $code)) {
            helper('activity');
            log_activity('login_gagal', 'auth', 'Kode 2FA salah', [
                'user_id'  => null,
                'username' => $user['username'] ?? null,
                'role'     => null,
            ]);
            return redirect()->to('2fa/verify')->with('error', 'Kode 2FA salah.');
        }

        // selesaikan login
        session()->remove('twofa_user_id');
        session()->set([
            'user_id'    => $user['user_id'],
            'username'   => $user['username'],
            'role'       => $user['role'],
            'opd_id'     => $user['opd_id'] ?? null,
            'isLoggedIn' => true,
        ]);

        helper('activity');
        log_activity('login', 'auth', 'Login berhasil (2FA)');

        return $user['role'] === 'admin_opd'
            ? redirect()->to('/adminopd/dashboard')
            : redirect()->to('/adminkab/dashboard');
    }
}
