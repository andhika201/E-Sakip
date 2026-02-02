<?php

namespace App\Controllers\AdminOpd;

use App\Controllers\BaseController;
use App\Models\Opd\RenstraModel;
use App\Models\RpjmdModel;
use App\Models\OpdModel;
use App\Models\PkModel;


class CascadingController extends BaseController
{
    protected $renstraModel;
    protected $rpjmdModel;
    protected $opdModel;
    protected $pkModel;
    public function __construct()
    {
        $this->renstraModel = new RenstraModel();
        $this->rpjmdModel = new RpjmdModel();
        $this->opdModel = new OpdModel();
        $this->pkModel = new PkModel();
    }

    public function index()
    {
        $session = session();
        $opdId = $session->get('opd_id');

        if (!$opdId) {
            return redirect()->to('/login')->with('error', 'Silakan login terlebih dahulu');
        }

        // ambil filter dari query string
        $misi = trim($this->request->getGet('misi') ?? '');
        $tujuan = trim($this->request->getGet('tujuan') ?? '');
        $rpjmd = trim($this->request->getGet('rpjmd') ?? '');
        $periode = trim($this->request->getGet('periode') ?? '');
        $status = trim($this->request->getGet('status') ?? '');

        // ambil data renstra (flatten) + target sasaran + target tujuan
        $renstraData = $this->renstraModel->getFilteredRenstra(
            $opdId,
            $misi ?: null,
            $tujuan ?: null,
            $rpjmd ?: null,
            $status ?: null,
            $periode ?: null
        );

        $currentOpd = $this->opdModel->find($opdId);

        $data = [
            'title' => 'Cascading - ' . ($currentOpd['nama_opd'] ?? ''),
            'current_opd' => $currentOpd,
            'renstra_data' => $renstraData,
            'filters' => [
                'misi' => $misi,
                'tujuan' => $tujuan,
                'rpjmd' => $rpjmd,
                'periode' => $periode,
                'status' => $status,
            ],
        ];
        return view('adminOpd/cascading/cascading', $data);
    }
}
