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
                ->withInput()
                ->with('error', 'Username minimal 4 karakter dan password minimal 6 karakter.');
        }

        $username = $this->request->getPost('username');
        $password = $this->request->getPost('password');

        // Cari user di database
        $userModel = model(UserModel::class);
        $user      = $userModel->where('username', $username)->first();

        // Verifikasi password
        if ($user && password_verify($password, $user['password'])) {
            // Simpan data sesi — JANGAN simpan password
            session()->set([
                'user_id'   => $user['user_id'],
                'username'  => $user['username'],
                'role'      => $user['role'],
                'opd_id'    => $user['opd_id'] ?? null,
                'isLoggedIn' => true,
            ]);

            return $this->redirectByRole($user['role']);
        }

        return redirect()->back()
            ->withInput()
            ->with('error', 'Username atau password salah.');
    }

    /**
     * Logout: hancurkan sesi dan redirect ke login.
     */
    public function logout()
    {
        session()->destroy();
        return redirect()->to('/login')->with('message', 'Anda telah berhasil logout.');
    }

    /**
     * Helper: redirect berdasarkan role user.
     * Satu-satunya tempat mapping role → URL.
     */
    private function redirectByRole(string $role)
    {
        switch ($role) {
            case 'admin_kab':
            case 'admin':           // superadmin juga ke dashboard kab
                return redirect()->to('/adminkab/dashboard');
            case 'admin_opd':
                return redirect()->to('/adminopd/dashboard');
            default:
                session()->destroy();
                return redirect()->to('/login')->with('error', 'Role tidak dikenali. Hubungi administrator.');
        }
    }
}
