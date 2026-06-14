<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Enforcement permission granular berbasis URL.
 *
 * Menurunkan (modul, aksi) dari URI lalu mengecek user_can('<modul>.<aksi>').
 * - Super admin (role 'admin') selalu lolos.
 * - Belum login: dilewati (biar AuthFilter yang menangani redirect ke login).
 * - Rute yang tidak terpetakan: dibiarkan lolos (mis. helper AJAX, master panel
 *   yang sudah dijaga auth:admin).
 *
 * Dipasang via Config\Filters::$filters untuk pola 'adminkab/*' & 'adminopd/*'.
 */
class ModulePermissionFilter implements FilterInterface
{
    /** segmen pertama setelah 'adminkab' => modul permission (konteks Kabupaten) */
    private const KAB_MAP = [
        'dashboard'       => 'dashboard',
        'rpjmd'           => 'rpjmd',
        'rkpd'            => 'rkpd',
        'iku'             => 'iku_kab',
        'cascading'       => 'cascading_kab',
        'rkt'             => 'rkt_kab',
        'target'          => 'target_kab',
        'monev'           => 'monev_kab',
        'lakip'           => 'lakip_kab',
        'lakip_kabupaten' => 'lakip_kab',
        'program_pk'      => 'program_pk',
        'pk'              => 'pk_bupati',
        'capaian_pk'      => 'pk_bupati',
        'pk_bupati'       => 'pk_bupati',
        'tentang_kami'    => 'tentang_kami',
    ];

    /** segmen pertama setelah 'adminopd' => modul permission (konteks OPD) */
    private const OPD_MAP = [
        'dashboard'    => 'dashboard',
        'renstra'      => 'renstra',
        'rkt'          => 'rkt_opd',
        'iku'          => 'iku_opd',
        'cascading'    => 'cascading_opd',
        'target'       => 'target_opd',
        'monev'        => 'monev_opd',
        'lakip'        => 'lakip_opd',
        'pk'           => 'pk_opd',
        'capaian_pk'   => 'pk_opd',
        'tentang_kami' => 'tentang_kami',
    ];

    public function before(RequestInterface $request, $arguments = null)
    {
        helper('rbac');

        // belum login -> biarkan AuthFilter yang urus
        if (!session()->get('isLoggedIn')) {
            return;
        }
        // super admin lolos semua
        if (session()->get('role') === 'admin') {
            return;
        }

        $path = trim((string) $request->getUri()->getPath(), '/');
        $segs = array_values(array_filter(explode('/', $path), static fn($s) => $s !== ''));
        if (count($segs) < 2) {
            return; // mis. /adminkab saja
        }

        $area  = $segs[0];
        $first = $segs[1];

        if ($area === 'adminkab') {
            $module = self::KAB_MAP[$first] ?? null;
        } elseif ($area === 'adminopd') {
            $module = self::OPD_MAP[$first] ?? null;
        } else {
            return;
        }

        if ($module === null) {
            return; // rute tak terpetakan -> lolos (dijaga filter lain)
        }

        // dashboard hanya punya .view
        if ($module === 'dashboard') {
            return user_can('dashboard.view') ? null : $this->deny();
        }

        $actions = $this->actionsFor($path);
        foreach ($actions as $a) {
            if (user_can($module . '.' . $a)) {
                return; // punya salah satu izin -> lolos
            }
        }

        return $this->deny();
    }

    /** @return string[] daftar aksi yang relevan (any-of) */
    private function actionsFor(string $path): array
    {
        $p = strtolower($path);
        if (strpos($p, 'delete') !== false)      { return ['delete']; }
        if (strpos($p, 'import') !== false)      { return ['create']; }
        if (strpos($p, 'save') !== false)        { return ['create', 'update']; }
        if (strpos($p, 'tambah') !== false)      { return ['create']; }
        if (strpos($p, 'setcapaian') !== false)  { return ['update']; }
        if (strpos($p, 'update') !== false || strpos($p, 'edit') !== false || strpos($p, 'status') !== false) {
            return ['update'];
        }
        return ['view']; // index, cetak, capaian (listing), dll.
    }

    private function deny()
    {
        return redirect()->to('/unauthorized')
            ->with('error', 'Anda tidak memiliki izin untuk aksi/halaman tersebut.');
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        //
    }
}
