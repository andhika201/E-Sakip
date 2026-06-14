<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Filter izin granular berbasis RBAC.
 * Pemakaian: ['filter' => 'permission:master.access'] atau beberapa
 * (any-of): 'permission:user.manage,role.manage'.
 *
 * Selalu pasang SETELAH 'auth' agar status login sudah dipastikan.
 */
class PermissionFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        helper('rbac');

        if (!session()->get('isLoggedIn')) {
            return redirect()->to('/login')->with('error', 'Silakan login terlebih dahulu.');
        }

        // tanpa argumen: cukup login
        if (empty($arguments)) {
            return;
        }

        // any-of: lolos bila punya salah satu permission
        foreach ($arguments as $perm) {
            if (user_can(trim($perm))) {
                return;
            }
        }

        return redirect()->to('/unauthorized')
            ->with('error', 'Anda tidak memiliki izin untuk mengakses halaman ini.');
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        //
    }
}
