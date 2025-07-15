<?php

namespace App\Controllers;

use App\Models\RpjmdModel;
use App\Models\RktModel;
use App\Models\RenjaModel;
use App\Models\RenstraModel;

class User extends BaseController
{
    protected $rpjmdModel;
    protected $rktModel;
    protected $renstraModel;

    public function __construct()
    {
        $this->rpjmdModel = new RpjmdModel();
        $this->rktModel = new RktModel();
        $this->renstraModel = new RenstraModel();
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
            $renjaData = $renjaModel->getAllRenja(); // Ambil data dari model

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

        // Sorting tahun
        $tahunList = array_keys($tahunSet);
        sort($tahunList);

        return view('user/renstra', [
            'renstraData' => $renstraData,
            'tahunList' => $tahunList,
            'opdList' => array_keys($opdSet)
        ]);
    }


    public function pk_bupati()
    {
        $data['pkBupatiData'] = []; // Ganti dengan data asli jika tersedia
        return view('user/pk_bupati', $data);
    }

    public function pk_administrator()
    {
        $data['pkAdministratorData'] = []; // Ganti dengan data asli jika tersedia
        return view('user/pk_administrator', $data);
    }

    public function pk_pengawas()
    {
        $data['pkPengawasData'] = []; // Ganti dengan data asli jika tersedia
        return view('user/pk_pengawas', $data);
    }

    public function pk_pimpinan()
    {
        $data['pkPimpinanData'] = []; // Ganti dengan data asli jika tersedia
        return view('user/pk_pimpinan', $data);
    }

    public function iku_opd()
    {
        // Kirim data kosong agar tidak error
        $data = [
            'ikuOpdData' => [],
            'tahunList' => [2021, 2022, 2023, 2024] // contoh tahun dummy (boleh diubah)
        ];

        return view('user/iku_opd', $data);
    }

    public function lakip_kabupaten()
    {
        $data['lakipKabupatenData'] = []; // Ganti dengan query data asli jika ada
        return view('user/lakip_kabupaten', $data);
    }

    public function lakip_opd()
    {
        $data['lakipOpdData'] = []; // Ganti dengan query data asli jika ada
        return view('user/lakip_opd', $data);
    }

    public function tentang_kami()
    {
        return view('user/tentang_kami');
    }
}
