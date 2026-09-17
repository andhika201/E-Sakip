<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class AuthFilter implements FilterInterface
{
    /**
     * Do whatever processing this filter needs to do.
     * By default it should not return anything during
     * normal execution. However, when an abnormal state
     * is found, it should return an instance of
     * CodeIgniter\HTTP\Response. If it does, script
     * execution will end and that Response will be
     * sent back to the client, allowing for error pages,
     * redirects, etc.
     *
     * @param RequestInterface $request
     * @param array|null       $arguments
     *
     * @return RequestInterface|ResponseInterface|string|void
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        $session = session();

        if (!$session->get('isLoggedIn')) {
            // User is not logged in, redirect to login page
            return redirect()->to('/login')->with('error', 'You must be logged in to access this page.');
        }

        // =============================================================
        // AKUN DIPERIKSA ULANG TIAP REQUEST
        //
        // Sesi hanya memotret baris users saat login. Tanpa pemeriksaan ini,
        // akun yang DINONAKTIFKAN atau DIHAPUS admin tetap bisa bekerja
        // sampai sesinya kedaluwarsa (2 jam), dan perubahan role/OPD baru
        // terasa sesudah login ulang. Satu SELECT berkunci primer per
        // request — murah dibanding risikonya.
        // =============================================================
        $userId    = (int) $session->get('user_id');
        $akun      = null;
        $diperiksa = false;

        try {
            $akun = \Config\Database::connect()->table('users')
                ->select('is_active, role, opd_id')
                ->where('user_id', $userId)
                ->get()->getRowArray();
            $diperiksa = true;
        } catch (\Throwable $e) {
            // Basis data bermasalah: jangan mengunci semua orang karena itu;
            // biarkan pemeriksaan berikutnya (permission, controller) yang jalan.
            log_message('error', 'AuthFilter: gagal memeriksa ulang akun #' . $userId . ': ' . $e->getMessage());
        }

        if (is_array($akun)) {
            if (empty($akun['is_active'])) {
                log_message('warning', 'AuthFilter: sesi akun nonaktif #' . $userId . ' dihentikan.');
                $session->destroy();

                return redirect()->to('/login')->with('error', 'Akun Anda dinonaktifkan. Hubungi administrator.');
            }

            // Sinkronkan role/OPD bila admin mengubahnya sesudah login.
            $opdBaru = $akun['opd_id'] === null ? null : (int) $akun['opd_id'];
            $opdLama = $session->get('opd_id') === null ? null : (int) $session->get('opd_id');

            if ((string) $akun['role'] !== (string) $session->get('role') || $opdBaru !== $opdLama) {
                $session->set(['role' => $akun['role'], 'opd_id' => $opdBaru]);
            }
        } elseif ($diperiksa) {
            // Query sukses tetapi barisnya tidak ada: akun sudah dihapus.
            log_message('warning', 'AuthFilter: sesi akun terhapus #' . $userId . ' dihentikan.');
            $session->destroy();

            return redirect()->to('/login')->with('error', 'Akun Anda sudah tidak terdaftar. Hubungi administrator.');
        }
        

        if($arguments !== null && is_array($arguments)) {
            // Check if user has the required role
            $userRole = $session->get('role');
            if (!in_array($userRole, $arguments)) {
                // User does not have the required role, redirect to unauthorized page
                return redirect()->to('/unauthorized')->with('error', 'You do not have permission to access this page.');
            }
        }
    }

    /**
     * Allows After filters to inspect and modify the response
     * object as needed. This method does not allow any way
     * to stop execution of other after filters, short of
     * throwing an Exception or Error.
     *
     * @param RequestInterface  $request
     * @param ResponseInterface $response
     * @param array|null        $arguments
     *
     * @return ResponseInterface|void
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        //
    }
}
