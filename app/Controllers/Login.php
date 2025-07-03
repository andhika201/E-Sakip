<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;

class Login extends BaseController
{
    public function index()
    {
        return view('login');
    }

    public function authenticate()
    {
        // Logika untuk memproses login
        // Misalnya, validasi username dan password
        $username = $this->request->getPost('username');
        $password = $this->request->getPost('password');

        // Cek apakah username dan password valid (contoh sederhana)
        if ($username === 'admin' && $password === 'password') {
            // Set session atau redirect ke halaman dashboard
            return redirect()->to('/dashboard');
        } else {
            // Jika gagal, kembali ke halaman login dengan pesan error
            return redirect()->back()->with('error', 'Username atau password salah');
        }
    }
}
