<?php

use App\Models\ActivityLogModel;

/**
 * Catat satu baris log aktivitas. Data user/diambil dari session & request.
 * Dipakai untuk login/logout/login gagal dan bisa dipanggil manual di mana saja.
 */
if (!function_exists('log_activity')) {
    /**
     * @param array<string,mixed> $overrides override field tertentu (mis. username/user_id)
     */
    function log_activity(string $action, ?string $module = null, ?string $description = null, array $overrides = []): void
    {
        try {
            $req = service('request');
            $ses = session();

            $data = [
                'user_id'     => $ses->get('user_id'),
                'username'    => $ses->get('username'),
                'role'        => $ses->get('role'),
                'action'      => $action,
                'module'      => $module,
                'description' => $description !== null ? mb_substr($description, 0, 255) : null,
                'method'      => method_exists($req, 'getMethod') ? strtoupper($req->getMethod()) : null,
                'url'         => mb_substr((string) $req->getUri()->getPath(), 0, 255),
                'ip_address'  => $req->getIPAddress(),
                'user_agent'  => mb_substr((string) $req->getUserAgent(), 0, 255),
                'created_at'  => date('Y-m-d H:i:s'),
            ];

            foreach ($overrides as $k => $v) {
                $data[$k] = $v;
            }

            (new ActivityLogModel())->insert($data);
        } catch (\Throwable $e) {
            // Jangan pernah memblok request hanya karena logging gagal.
            log_message('error', 'log_activity gagal: ' . $e->getMessage());
        }
    }
}
