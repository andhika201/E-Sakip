<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;

class LoginController extends BaseController
{
    public function index()
{
    // Cek apakah user sudah login
    if (session()->get('isLoggedIn')) {
        $role = session()->get('role');

        switch ($role) {
            case 'admin_kab':
                return redirect()->to('/adminkab/dashboard')
                    ->with('message', 'Anda sudah login sebagai Admin Kabupaten');
            case 'admin_opd':
                return redirect()->to('/adminopd/dashboard')
                    ->with('message', 'Anda sudah login sebagai Admin OPD');
            case 'admin':
                return redirect()->to('/adminkab/dashboard')
                    ->with('message', 'Anda sudah login sebagai Admin');
            default:
                return redirect()->to('/dashboard')
                    ->with('message', 'Anda sudah login');
        }
    }

    return view('login');
}

    // private function verifyRecaptcha($response)
    // {
    //     $secretKey = env('RECAPTCHA_SECRET_KEY');

    //     if (empty($secretKey)) {
    //         log_message('error', 'RECAPTCHA_SECRET_KEY not found in environment variables');
    //         return false;
    //     }

    //     $url = 'https://www.google.com/recaptcha/api/siteverify';

    //     $data = [
    //         'secret' => $secretKey,
    //         'response' => $response,
    //         'remoteip' => $this->request->getIPAddress()
    //     ];

    //     try {
    //         $client = \Config\Services::curlrequest();
    //         $response = $client->post($url, ['form_params' => $data]);
    //         $result = json_decode($response->getBody(), true);

    //         return isset($result['success']) && $result['success'] === true;
    //     } catch (\Exception $e) {
    //         log_message('error', 'reCAPTCHA verification failed: ' . $e->getMessage());
    //         return false;
    //     }
    // }

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
        $recaptchaResponse = $this->request->getPost('g-recaptcha-response');

        // Validasi CAPTCHA
        // if (empty($recaptchaResponse)) {
        //     return redirect()->back()
        //         ->withInput()
        //         ->with('error', 'Silakan verifikasi CAPTCHA terlebih dahulu');
        // }

        // if (!$this->verifyRecaptcha($recaptchaResponse)) {
        //     return redirect()->back()
        //         ->withInput()
        //         ->with('error', 'Verifikasi CAPTCHA gagal. Silakan coba lagi');
        // }

        // Load model untuk cek user di database
        $userModel = new \App\Models\UserModel();
        $user = $userModel->where('username', $username)->first();

        // Debug: cek struktur data user
        if ($user) {
            log_message('debug', 'User data structure: ' . print_r($user, true));
        }

        // Cek apakah user ditemukan dan password benar
        if ($user && password_verify($password, $user['password'])) {
            // Set session data dengan field yang benar
            $sessionData = [
                'user_id' => $user['user_id'], // Menggunakan user_id sesuai migration
                'username' => $user['username'],
                'role' => $user['role'],
                'opd_id' => isset($user['opd_id']) ? $user['opd_id'] : null,
                'isLoggedIn' => true
            ];

            session()->set($sessionData);

            // Redirect berdasarkan role
            switch ($user['role']) {
                case 'admin_kabupaten':
                    return redirect()->to('/adminkab/dashboard');
                case 'admin_opd':
                    return redirect()->to('/adminopd/dashboard');
                case 'admin':
                    return redirect()->to('/adminkab/dashboard'); // misalnya halaman utama admin
                default:
                    return redirect()->to('/login')->with('error', 'Role tidak dikenali');
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
