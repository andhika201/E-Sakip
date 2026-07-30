<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Pagar terakhir untuk role yang MURNI baca-saja.
 *
 * Dipasang GLOBAL (before) supaya tidak bergantung pada satu grup rute: apa pun
 * URL-nya, permintaan yang mengubah data (POST / PUT / PATCH / DELETE) dari role
 * baca-saja langsung ditolak di lapis filter — sebelum controller apa pun
 * berjalan. Menyembunyikan tombol di view saja tidak cukup: request bisa
 * dikirim manual (curl/Postman/DevTools).
 *
 * Pengecualian sengaja dibuat sempit dan hanya untuk hal yang tidak menyentuh
 * data SAKIP: logout, ganti password sendiri, dan pengaturan 2FA akun sendiri.
 *
 * Role admin_inspektorat TIDAK dimasukkan di sini karena pola read-only-nya
 * sudah dijaga RBAC per modul (permissions *.view saja) dan pemeriksaan role di
 * controller — mengubahnya berisiko pada perilaku existing. Filter ini khusus
 * untuk role baru `bupati` yang memang tidak punya satu pun izin tulis.
 */
class ReadOnlyRoleFilter implements FilterInterface
{
    /** Role yang tidak boleh melakukan operasi pengubah data sama sekali. */
    private const ROLE_BACA_SAJA = ['bupati'];

    /** Metode HTTP yang dianggap mengubah data. */
    private const METODE_UBAH = ['post', 'put', 'patch', 'delete'];

    /**
     * Jalur yang tetap boleh POST walau role baca-saja (akun sendiri, bukan
     * data kinerja). Dicocokkan sebagai awalan path tanpa slash pembuka.
     */
    private const JALUR_DIIZINKAN = [
        'logout',
        'login',
        'change-password',
        '2fa/',
    ];

    public function before(RequestInterface $request, $arguments = null)
    {
        $role = (string) (session()->get('role') ?? '');
        if (!in_array($role, self::ROLE_BACA_SAJA, true)) {
            return;
        }

        $metode = strtolower($request->getMethod());
        if (!in_array($metode, self::METODE_UBAH, true)) {
            return;
        }

        $path = ltrim((string) $request->getUri()->getPath(), '/');
        foreach (self::JALUR_DIIZINKAN as $izin) {
            if ($path === rtrim($izin, '/') || strpos($path, $izin) === 0) {
                return;
            }
        }

        // Endpoint JSON dashboard (mis. dashboard/data) dipanggil via POST oleh
        // grafik/drawer. Itu murni pembacaan, jadi tetap diizinkan — daftarnya
        // dibatasi ke area /bupati saja.
        if (preg_match('#^bupati/dashboard(/|$)#', $path)) {
            return;
        }

        log_message('warning', 'ReadOnlyRoleFilter: ' . strtoupper($metode) . ' ' . $path
            . ' ditolak untuk role ' . $role . ' (user_id=' . (string) session()->get('user_id') . ').');

        if ($request instanceof \CodeIgniter\HTTP\IncomingRequest && $request->isAJAX()) {
            return service('response')->setStatusCode(403)->setJSON([
                'status'  => 'error',
                'message' => 'Peran Anda hanya dapat melihat data (read-only).',
            ]);
        }

        return redirect()->to('/unauthorized')
            ->with('error', 'Peran Anda hanya dapat melihat data (read-only).');
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        //
    }
}
