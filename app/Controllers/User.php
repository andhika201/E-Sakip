<?php

namespace App\Controllers;

use App\Models\RpjmdModel;
use App\Models\RktModel;
use App\Models\RenjaModel;
use App\Models\RenstraModel;
use App\Models\IkuModel;

class User extends BaseController
{
    protected $rpjmdModel;
    protected $rktModel;
    protected $renstraModel;
    protected $renjaModel;
    protected $ikuModel;

    public function __construct()
    {
        $this->rpjmdModel = new RpjmdModel();
        $this->rktModel = new RktModel();
        $this->renstraModel = new RenstraModel();
        $this->renjaModel = new RenjaModel();
        $this->ikuModel = new IkuModel();
    }

    public function index()
    {
        return view('user/dashboard');
    }

    public function rpjmd()
    {
        $completedRpjmd = $this->rpjmdModel->getCompleteRpjmdStructure();

        if (empty($completedRpjmd)) {
            return view('user/rpjmd', [
                'rpjmdGrouped' => [],
                'message' => 'Belum ada data RPJMD yang telah selesai.'
            ]);
        }

        $groupedData = [];
        foreach ($completedRpjmd as $misi) {
            $periodKey = $misi['tahun_mulai'] . '-' . $misi['tahun_akhir'];

            if (!isset($groupedData[$periodKey])) {
                $groupedData[$periodKey] = [
                    'period' => $periodKey,
                    'tahun_mulai' => $misi['tahun_mulai'],
                    'tahun_akhir' => $misi['tahun_akhir'],
                    'years' => range($misi['tahun_mulai'], $misi['tahun_akhir']),
                    'misi_data' => []
                ];
            }

            $groupedData[$periodKey]['misi_data'][] = $misi;
        }

        ksort($groupedData);

        return view('user/rpjmd', [
            'rpjmdGrouped' => $groupedData
        ]);
    }

    public function rkt()
    {
        $rktData = $this->rktModel->getAllRkt();

        $groupedRkt = [];
        foreach ($rktData as $row) {
            $sasaranId = $row['sasaran_id'];

            if (!isset($groupedRkt[$sasaranId])) {
                $groupedRkt[$sasaranId] = [
                    'sasaran' => $row['sasaran'],
                    'indikator' => []
                ];
            }

            $groupedRkt[$sasaranId]['indikator'][] = [
                'indikator_sasaran' => $row['indikator_sasaran'],
                'tahun' => $row['tahun'],
                'target' => $row['target']
            ];
        }

        return view('user/rkt', ['grouped_rkt' => $groupedRkt]);
    }

    public function renja()
    {
        $renjaModel = new RenjaModel();
        $renjaData = $renjaModel->getAllRenja();

        return view('user/renja', [
            'renjaData' => $renjaData
        ]);
    }

    public function renstra()
    {
        $data = $this->renstraModel->getRenstraData();

        $renstraData = [];
        $tahunSet = [];
        $opdSet = [];

        foreach ($data as $row) {
            $key = $row['indikator_id'];
            $tahunSet[$row['tahun']] = true;
            $opdSet[$row['opd_id']] = true;

            if (!isset($renstraData[$key])) {
                $renstraData[$key] = [
                    'opd' => $row['opd_id'],
                    'sasaran' => $row['sasaran'],
                    'indikator' => $row['indikator_sasaran'],
                    'target_capaian' => []
                ];
            }

            $renstraData[$key]['target_capaian'][$row['tahun']] = $row['target'];
        }

        $tahunList = array_keys($tahunSet);
        sort($tahunList);

        return view('user/renstra', [
            'renstraData' => $renstraData,
            'tahunList' => $tahunList,
            'opdList' => array_keys($opdSet)
        ]);
    }

    public function iku_opd()
    {
        $ikuModel = new \App\Models\IkuModel();
        $ikuResult = $ikuModel->getAllIku();

        return view('user/iku_opd', [
            'ikuOpdData' => $ikuResult['iku_opd_data'],
            'tahunList' => $ikuResult['tahun_list']
        ]);
    }

    public function pk_bupati()
    {
        $data['pkBupatiData'] = []; // Ganti jika sudah tersedia
        return view('user/pk_bupati', $data);
    }

    public function pk_administrator()
    {
        $data['pkAdministratorData'] = []; // Ganti jika sudah tersedia
        return view('user/pk_administrator', $data);
    }

    public function pk_pengawas()
    {
        $data['pkPengawasData'] = []; // Ganti jika sudah tersedia
        return view('user/pk_pengawas', $data);
    }

    public function pk_pimpinan()
    {
        $data['pkPimpinanData'] = []; // Ganti jika sudah tersedia
        return view('user/pk_pimpinan', $data);
    }

    public function lakip_kabupaten()
    {
        $data['lakipKabupatenData'] = []; // Ganti jika sudah tersedia
        return view('user/lakip_kabupaten', $data);
    }

    public function lakip_opd()
    {
        $data['lakipOpdData'] = []; // Ganti jika sudah tersedia
        return view('user/lakip_opd', $data);
    }

    public function tentang_kami()
    {
        return view('user/tentang_kami');
    }
}
