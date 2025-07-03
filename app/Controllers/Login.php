<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;

class Login extends BaseController
{
    public function index()
    {
        // Cek apakah user sudah login
        if (session()->get('isLoggedIn')) {
            return redirect()->to('/dashboard');
        }

        return view('login');
    }

    public function authenticate()
    {
        // Validasi input
        $rules = [
            'username' => 'required|min_length[4]',
            'password' => 'required|min_length[6]'
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Username minimal 4 karakter dan password minimal 6 karakter');
        }

        $username = $this->request->getPost('username');
        $password = $this->request->getPost('password');

        // Load model untuk cek user di database
        $userModel = new \App\Models\UserModel();
        $user = $userModel->where('username', $username)->first();

        // Cek apakah user ditemukan dan password benar
        if ($user && password_verify($password, $user['password'])) {
            // Set session data
            $sessionData = [
                'user_id' => $user['id'],
                'username' => $user['username'],
                'role' => $user['role'],
                'isLoggedIn' => true
            ];
            
            session()->set($sessionData);

            // Redirect berdasarkan role
            switch ($user['role']) {
                case 'admin_kabupaten':
                    return redirect()->to('/adminkab/dashboard');
                    break;
                    
                case 'admin_opd':
                    return redirect()->to('/adminopd/dashboard');
                    break;
                    
                default:
                    return redirect()->to('/login')
                        ->with('error', 'Role tidak dikenali');
                    break;
            }
        } else {
            // Jika gagal, kembali ke halaman login dengan pesan error
            return redirect()->back()
                ->withInput()
                ->with('error', 'Username atau password salah');
        }
    }

    public function logout()
    {
        // Hapus semua session data
        session()->destroy();
        return redirect()->to('/login')->with('message', 'Anda telah berhasil logout');
    }
}
