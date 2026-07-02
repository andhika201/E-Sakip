<?php

namespace App\Controllers;

use App\Controllers\BaseController;

class ProfileController extends BaseController
{
    public function index()
    {
        $db     = \Config\Database::connect();
        $userId = session()->get('user_id');

        $user = $db->table('users')->where('user_id', $userId)->get()->getRowArray();
        if (!$user) {
            return redirect()->to('/login')->with('error', 'Sesi tidak valid, silakan login ulang.');
        }

        $role   = $user['role'] ?? session()->get('role');
        $prefix = ($role === 'admin_opd') ? 'adminOpd' : 'adminKabupaten';

        $roleLabel = match ($role) {
            'admin'                        => 'Super Admin',
            'admin_opd'                    => 'Admin OPD',
            'admin_kab', 'admin_kabupaten' => 'Admin Kabupaten',
            default                        => ucwords(str_replace('_', ' ', (string) $role)),
        };

        $namaOpd = 'Pemerintah Kabupaten Pringsewu';
        if (!empty($user['opd_id'])) {
            $opd = $db->table('opd')->select('nama_opd')->where('id', $user['opd_id'])->get()->getRowArray();
            if ($opd) {
                $namaOpd = $opd['nama_opd'];
            }
        }

        return view('profile', [
            'prefix'       => $prefix,
            'user'         => $user,
            'roleLabel'    => $roleLabel,
            'namaOpd'      => $namaOpd,
            'twofaEnabled' => !empty($user['two_factor_enabled']),
        ]);
    }
}
