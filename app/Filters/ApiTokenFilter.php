<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class ApiTokenFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $configuredToken = trim((string) (env('API_TOKEN') ?: env('api.token')));

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
