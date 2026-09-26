<?php

namespace App\Controllers;

use App\Services\MasukSebagaiService;

/**
 * AKSARA+ — "Masuk sebagai" (App\Services\MasukSebagaiService): cari akun, masuk sebagai akun itu, kembali ke akun asli.
 *
 * Hak diperiksa di sini, bukan lewat filter peran rute: yang dinilai adalah akun ASLI, sedangkan filter membaca akun
 * yang sedang ditiru.
 */
class MasukSebagaiController extends BaseController
{
    private const PERAN_SARING = ['admin_opd', 'admin_kecamatan', 'bupati', 'admin_inspektorat'];

    private const JENIS_SARING = ['opd' => 'Dinas/Badan', 'kecamatan' => 'Kecamatan', 'kelurahan' => 'Kelurahan', 'upt' => 'UPT', 'non_opd' => 'Lainnya'];

    public function index()
    {
        if (! MasukSebagaiService::bolehDipakai()) {
            return redirect()->to('/unauthorized')->with('error', 'Fitur "Masuk sebagai" hanya untuk Admin Kabupaten dan Super Admin.');
        }

        $peran  = (string) $this->request->getGet('peran');
        $jenis  = (string) $this->request->getGet('jenis');
        $saring = [
            'q'     => mb_substr(trim((string) $this->request->getGet('q')), 0, 100),
            'peran' => in_array($peran, self::PERAN_SARING, true) ? $peran : '',
            'jenis' => array_key_exists($jenis, self::JENIS_SARING) ? $jenis : '',
        ];
        $layanan = new MasukSebagaiService();

        return view('masuk_sebagai/index', [
            'title'       => 'Masuk sebagai · AKSARA+',
            'saring'      => $saring,
            'akun'        => $layanan->cari($saring),
            'terakhir'    => $layanan->terakhir(),
            'asli'        => MasukSebagaiService::akunAsli(),
            'peranSaring' => self::PERAN_SARING,
            'jenisSaring' => self::JENIS_SARING,
        ]);
    }

    public function mulai(int $id)
    {
        if (! MasukSebagaiService::bolehDipakai()) {
            return redirect()->to('/unauthorized')->with('error', 'Fitur "Masuk sebagai" hanya untuk Admin Kabupaten dan Super Admin.');
        }

        $sasaran = (new MasukSebagaiService())->mulai($id);

        if ($sasaran === null) {
            return redirect()->to('/masuk-sebagai')
                ->with('error', 'Akun itu tidak bisa dimasuki (nonaktif, akun admin kabupaten/super admin, atau akun Anda sendiri).');
        }

        return redirect()->to(dashboard_path_by_role($sasaran['role']) ?? '/')
            ->with('success', 'Anda kini masuk sebagai ' . $sasaran['username'] . '.');
    }

    public function kembali()
    {
        if (! (new MasukSebagaiService())->kembali()) {
            return redirect()->to('/login');
        }

        return redirect()->to('/masuk-sebagai')->with('success', 'Anda kembali sebagai ' . session()->get('username') . '.');
    }
}
