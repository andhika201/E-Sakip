<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class ApiTokenFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        // Argumen filter memilih token TERPISAH per konsumen, mis. 'api-token:ekin'
        // → env('EKIN_API_TOKEN'). Tanpa argumen perilakunya persis seperti dulu
        // (API_TOKEN bersama), jadi konsumen lama (Prioritas, korpus) tidak terdampak.
        // MENGAPA terpisah: endpoint eKin memuat nama & NIP pegawai; tokennya harus
        // bisa dicabut tanpa memutus konsumen lain.
        $konsumen = is_array($arguments) && $arguments !== [] ? strtolower((string) $arguments[0]) : '';
        if ($konsumen === 'ekin') {
            $configuredToken = trim((string) (env('EKIN_API_TOKEN') ?: env('api.ekinToken')));
        } else {
            $configuredToken = trim((string) (env('API_TOKEN') ?: env('api.token')));
        }

        if ($configuredToken === '') {
            return service('response')
                ->setStatusCode(500)
                ->setJSON([
                    'status' => 'error',
                    'message' => 'API token belum dikonfigurasi.',
                ]);
        }

        $providedToken = $this->getProvidedToken($request);

        if ($providedToken === '' || !hash_equals($configuredToken, $providedToken)) {
            return service('response')
                ->setStatusCode(401)
                ->setJSON([
                    'status' => 'error',
                    'message' => 'API token tidak valid.',
                ]);
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        //
    }

    private function getProvidedToken(RequestInterface $request): string
    {
        $token = $request->getHeaderLine('api-token');

        if ($token === '') {
            $token = $request->getHeaderLine('X-API-Token');
        }

        if ($token !== '') {
            return trim($token);
        }

        $authorization = trim($request->getHeaderLine('Authorization'));

        if (stripos($authorization, 'Bearer ') === 0) {
            return trim(substr($authorization, 7));
        }

        return '';
    }
}
