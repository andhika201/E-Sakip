<?php

namespace App\Controllers;

class UserController extends BaseController
{
    public function index()
    {
        return view('user/dashboard');
    }
    
    public function rpjmd()
    {
        $rpjmdModel = new \App\Models\RpjmdModel();
        
        // Ambil data RPJMD yang sudah selesai dengan struktur lengkap
        $completedRpjmd = $rpjmdModel->getCompletedRpjmdStructure();
        
        // Jika tidak ada data selesai, tampilkan pesan
        if (empty($completedRpjmd)) {
            return view('user/rpjmd', [
                'rpjmdGrouped' => [],
                'message' => 'Belum ada data RPJMD yang telah selesai.'
            ]);
        }
        
        // Group data by period (tahun_mulai - tahun_akhir) seperti di admin kabupaten
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
        
        // Sort periods by tahun_mulai
        ksort($groupedData);
        
        return view('user/rpjmd', [
            'rpjmdGrouped' => $groupedData
        ]);
    }

    public function rkt()
    {
        $rktModel = new \App\Models\RktModel();
        $rktData = $rktModel->getRktData(); 

        return view('user/rkt', ['rktData' => $rktData]);
    }


    public function lakip_kabupaten()
    {
        $lakipKabupatenModel = new \App\Models\LakipKabupatenModel();

        // Ambil semua data dari tabel lakip_kabupaten
        $lakipKabupatenData = $lakipKabupatenModel->getAllLakipKabupaten();

        // Kirim data ke view
        return view('user/lakip_kabupaten', [
            'lakipKabupatenData' => $lakipKabupatenData
        ]);
    }


    public function pk_bupati()
    {
        $pkBupatiData = [
            [
                'tahun' => '2023',
                'sasaran' => 'Meningkatkan kualitas pendidikan',
                'indikator' => 'Angkat Partisipsi Sekolah',
                'target' => '98%',
            ],
            [
                'tahun' => '2023',
                'sasaran' => 'Meningkatkan kualitas pendidikan',
                'indikator' => 'Angkat Partisipsi Sekolah',
                'target' => '98%',
            ]
        ];

        return view('user/pk_bupati', [
        'pkBupatiData' => $pkBupatiData
        ]);
    }

    public function renja()
{
    $renjaModel = new \App\Models\Opd\RenjaModel();

    // Ambil data dari model
    $renjaData = $renjaModel->getRenjaData();

    // Gabungkan data berdasarkan sasaran dan indikator
    $mergedData = [];
    foreach ($renjaData as $item) {
        $key = $item['sasaran_renja'] . '|' . $item['indikator_sasaran'];

        if (!isset($mergedData[$key])) {
            $mergedData[$key] = [
                'sasaran' => $item['sasaran_renja'],
                'indikator_sasaran' => $item['indikator_sasaran'],
                'target_capaian_per_tahun' => []
            ];
        }

        $mergedData[$key]['target_capaian_per_tahun'][$item['tahun']] = $item['target'] . ' ' . $item['satuan'];
    }

    // Format ulang agar target tahun jadi satu string
        $formattedData = [];
        foreach ($mergedData as $row) {
            $targets = [];
            foreach ($row['target_capaian_per_tahun'] as $tahun => $target) {
                $targets[] = "$tahun: $target";
                }
            $formattedData[] = [
                'sasaran' => $row['sasaran'],
                'indikator_sasaran' => $row['indikator_sasaran'],
                'target_capaian_per_tahun' => implode('<br>', $targets)
            ];
        }

        // Kirim ke view
        return view('user/renja', [
            'renjaData' => $formattedData
        ]);
    }

    public function renstra()
    {
        $renstraModel = new \App\Models\Opd\RenstraModel();
        $opdModel = new \App\Models\OpdModel();

        // Ambil filter OPD dari query
        $opdId = $this->request->getGet('opd_id');

        $opdList = $opdModel->findAll();
        $renstraData = [];
        $tahunList = [];

        if ($opdId) {
            $renstraData = $renstraModel->getRenstraWithTarget($opdId);

            // Ambil daftar tahun dari salah satu data renstra (asumsi semua punya tahun sama)
            foreach ($renstraData as $data) {
                if (!empty($data['target'])) {
                    $tahunList = array_keys($data['target']);
                    break;
                }
            }
        }

        return view('user/renstra', [
            'renstraData' => $renstraData,
            'opdList' => $opdList,
            'tahunList' => $tahunList,
            'selectedOpdId' => $opdId
        ]);
    }


    public function lakip_opd()
    {
        $lakipModel = new \App\Models\Opd\LakipOpdModel();

        // Ambil semua data LAKIP OPD dari database
        $lakipOpdData = $lakipModel->getAllLakipOpd();

        return view('user/lakip_opd', [
            'lakipOpdData' => $lakipOpdData
        ]);
    }


    public function iku_opd()
    {
        $ikuOpdModel = new \App\Models\Opd\IkuOpdModel();

        $ikuData = $ikuOpdModel->getIkuOpdWithTarget();

        // Ambil list tahun
        $tahunListResult = $ikuOpdModel->getTahunList();
        $tahunList = array_column($tahunListResult, 'tahun'); // Ambil kolom 'tahun' saja

        $data = [
            'title' => 'IKU OPD',
            'ikuOpdData' => $ikuData,
            'tahunList' => $tahunList
        ];

        return view('user/iku_opd', $data);
}



    public function pk_pimpinan()
    {
        $pkPimpinanData = [
            [
                'tahun' => '2023',
                'sasaran' => 'Meningkatkan kualitas pendidikan',
                'indikator' => 'Angkat Partisipsi Sekolah',
                'target' => '98%',
            ],
            [
                'tahun' => '2023',
                'sasaran' => 'Meningkatkan kualitas pendidikan',
                'indikator' => 'Angkat Partisipsi Sekolah',
                'target' => '98%',
            ]
        ];

        return view('user/pk_pimpinan', [
        'pkPimpinanData' => $pkPimpinanData
        ]);
    }

    public function pk_administrator()
    {
        $pkAdministratorData = [
            [
                'tahun' => '2023',
                'sasaran' => 'Meningkatkan kualitas pendidikan',
                'indikator' => 'Angkat Partisipsi Sekolah',
                'target' => '98%',
            ],
            [
                'tahun' => '2023',
                'sasaran' => 'Meningkatkan kualitas pendidikan',
                'indikator' => 'Angkat Partisipsi Sekolah',
                'target' => '98%',
            ]
            ];
        return view('user/pk_administrator',[
            'pkAdministratorData' => $pkAdministratorData
        ]);
    }

    public function pk_pengawas()
    {
        $pkPengawasData = [
            [
                'tahun' => '2023',
                'sasaran' => 'Meningkatkan kualitas pendidikan',
                'indikator' => 'Angkat Partisipsi Sekolah',
                'target' => '98%',
            ],
            [
                'tahun' => '2023',
                'sasaran' => 'Meningkatkan kualitas pendidikan',
                'indikator' => 'Angkat Partisipsi Sekolah',
                'target' => '98%',
            ]
        ];

        return view('user/pk_pengawas',[
            'pkPengawasData' => $pkPengawasData
        ]);
    }

    public function tentang_kami()
    {
        return view('user/tentang_kami');
    }

}
