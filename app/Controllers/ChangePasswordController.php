<?php

namespace App\Controllers;

use App\Controllers\BaseController;

class ChangePasswordController extends BaseController
{
    public function index()
    {
        $role = session()->get('role');
        $prefix = $this->getPrefix($role);
        return view($prefix . '/change_password', ['role' => $role]);
    }

    public function update()
    {
        $role     = session()->get('role');
        $userId   = session()->get('user_id');
        $prefix   = $this->getPrefix($role);

        $current  = $this->request->getPost('current_password');
        $new      = $this->request->getPost('new_password');
        $confirm  = $this->request->getPost('confirm_password');

        // Validasi
        if (empty($current) || empty($new) || empty($confirm)) {
            return redirect()->back()->with('error', 'Semua field wajib diisi.');
        }
        if (strlen($new) < 6) {
            return redirect()->back()->with('error', 'Password baru minimal 6 karakter.');
        }
        if ($new !== $confirm) {
            return redirect()->back()->with('error', 'Konfirmasi password tidak cocok.');
        }

        $db   = \Config\Database::connect();
        $user = $db->table('users')->where('user_id', $userId)->get()->getRowArray();

        if (!$user || !password_verify($current, $user['password'])) {
            return redirect()->back()->with('error', 'Password lama tidak sesuai.');
        }

        $db->table('users')->where('user_id', $userId)->update([
            'password' => password_hash($new, PASSWORD_DEFAULT)
        ]);

        return redirect()->back()->with('success', 'Password berhasil diubah.');
    }

    private function getPrefix(string $role): string
    {
        return match ($role) {
            'admin_kabupaten', 'admin' => 'adminKabupaten',
            'admin_opd'               => 'adminOpd',
            default                   => 'adminKabupaten',
        };
    }
}
