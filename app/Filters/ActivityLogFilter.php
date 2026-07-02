<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Mencatat setiap aksi pengubah data (POST/PUT/PATCH/DELETE) oleh user yang login.
 * Dipasang sebagai filter global "after". Login/logout dicatat terpisah di
 * LoginController agar tidak ganda.
 */
class ActivityLogFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        // tidak ada aksi di before
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        try {
            if (!session()->get('isLoggedIn')) {
                return;
            }

            $method = strtoupper(method_exists($request, 'getMethod') ? $request->getMethod() : 'GET');
            if (!in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
                return; // hanya aksi pengubah data
            }

            $path = trim((string) $request->getUri()->getPath(), '/');

            // lewati rute auth (dicatat manual) & rute log itu sendiri
            foreach (['login', 'logout', 'log-aktivitas'] as $skip) {
                if (strpos($path, $skip) !== false) {
                    return;
                }
            }

            helper('activity');
            [$action, $module] = $this->derive($path);
            log_activity($action, $module, $method . ' /' . $path);
        } catch (\Throwable $e) {
            log_message('error', 'ActivityLogFilter: ' . $e->getMessage());
        }
    }

    /** @return array{0:string,1:?string} [action, module] */
    private function derive(string $path): array
    {
        $p = strtolower($path);

        $action = 'aksi';
        if (strpos($p, 'delete') !== false)            { $action = 'hapus'; }
        elseif (strpos($p, 'update-status') !== false) { $action = 'ubah status'; }
        elseif (strpos($p, 'status') !== false)        { $action = 'ubah status'; }
        elseif (strpos($p, 'update') !== false)        { $action = 'ubah'; }
        elseif (strpos($p, 'save') !== false)          { $action = 'simpan'; }
        elseif (strpos($p, 'sync') !== false)          { $action = 'sinkron'; }
        elseif (strpos($p, 'import') !== false)        { $action = 'import'; }
        elseif (strpos($p, 'permission') !== false)    { $action = 'ubah'; }

        $skipSeg = ['adminkab', 'adminopd', 'master', 'api'];
        $segs    = array_values(array_filter(explode('/', $path), static fn($s) => $s !== ''));
        $module  = null;
        foreach ($segs as $s) {
            if (!in_array($s, $skipSeg, true) && !is_numeric($s)) {
                $module = $s;
                break;
            }
        }

        return [$action, $module];
    }
}
