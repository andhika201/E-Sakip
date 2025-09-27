<?php

namespace App\Controllers;

class UserController extends BaseController
{
    public function index()
    {
        // session()->destroy(); // hapus semua session
        // dd(session()->get('role'));
        return view('dashboard');
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

    public function rkpd()
    {
        // Simulasi data dari database 
        $rkpd_data = [
            [
                'id' => 1,
                'rpjmd_sasaran_id' => 1,
                'rpjmd_sasaran' => 'Meningkatkan kualitas tata kelola pemerintahan',
                'sasaran' => 'Indeks Keterbukaan Informasi Publik',
                'status' => 'draft',
                'indikator' => [
                    [
                        'indikator_sasaran' => 'Nilai indeks dari Komisi Informasi',
                        'satuan' => 'Skor',
                        'tahun' => '2025',
                        'target' => '85'
                    ],
                    [
                        'indikator_sasaran' => 'Persentase keterbukaan dokumen publik',
                        'satuan' => '%',
                        'tahun' => '2025',
                        'target' => '90'
                    ]
                ]
            ],
            [
                'id' => 2,
                'rpjmd_sasaran_id' => 2,
                'rpjmd_sasaran' => 'Meningkatkan kualitas pendidikan',
                'sasaran' => 'Indeks Partisipasi Sekolah',
                'status' => 'selesai',
                'indikator' => [
                    [
                        'indikator_sasaran' => 'Angka Partisipasi Murni SMP',
                        'satuan' => '%',
                        'tahun' => '2025',
                        'target' => '98'
                    ]
                ]
            ],
        ];

        return view('user/rkpd', [
            'rkpd_data' => $rkpd_data,
            'available_years' => ['2023', '2024', '2025']
        ]);
    }

    public function lakip_kabupaten()
    {   
        $lakipKabupatenData = [
            [
                'sasaran' => 'Meningkatkan Kerukunana dan Toleransi Antar Umat Beragama',
                'indikator' => 'Indeks Kerukunan Umat Beraga',
                'capaian_sebelumnya' => '78',
                'target_tahun_ini' => '79',
                'capaian_tahun_ini' => '78',
            ],
            [
                'sasaran' => 'Meningkatkan Kerukunana dan Toleransi Antar Umat Beragama',
                'indikator' => 'Indeks Kerukunan Umat Beraga',
                'capaian_sebelumnya' => '78',
                'target_tahun_ini' => '79',
                'capaian_tahun_ini' => '78',
            ]
        ];

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
        $renjaData = [
            [
                'sasaran' => "Indeks Keterbukaan Informaasi Publik",
                'indikator_sasaran' => "Nilai indeks didapat dari hasil penilaian indeks keterbukaan informasi publik oleh komisi informasi",
                'target_capaian_per_tahun' => "2025"
            ],
            [
                'sasaran' => "Indeks Keterbukaan Informaasi Publik",
                'indikator_sasaran' => "Nilai indeks didapat dari hasil penilaian indeks keterbukaan informasi publik oleh komisi informasi",
                'target_capaian_per_tahun' => "2025"
            ]
            ];

        return view('user/renja', [
            'renjaData' => $renjaData
        ]);
    }

    public function renstra()
    {
        $tahunList = ['2025', '2026', '2027', '2028', '2029', '2030'];
        $opdList = ['Unit Kerja', 'Dinas Pendidikan', 'Dinas Kesehatan'];
        $renstraData = [
            [
                'opd' => 'Unit Kerja',
                'sasaran' => 'Indeks Keterbukaan Informasi Publik',
                'indikator' => 'Nilai Indeks didapat dari hasil penilaian Indeks Keterbukaan Informasi Publik oleh Komisi Informasi',
                'target_capaian' => [
                    '2025' => '70',
                    '2026' => '80',
                    '2027' => '85',
                    '2028' => '90',
                    '2029' => '90',
                    '2030' => '95',
                ]
            ],
        ];

        return view('user/renstra', [
            'tahunList' => $tahunList,
            'opdList' => $opdList,
            'renstraData' => $renstraData
        ]);
    }


    public function lakip_opd()
    {
         $lakipOpdData = [
            [
                'sasaran' => 'Meningkatkan Kerukunana dan Toleransi Antar Umat Beragama',
                'indikator' => 'Indeks Kerukunan Umat Beraga',
                'capaian_sebelumnya' => '78',
                'target_tahun_ini' => '79',
                'capaian_tahun_ini' => '78',
            ],
            [
                'sasaran' => 'Meningkatkan Kerukunana dan Toleransi Antar Umat Beragama',
                'indikator' => 'Indeks Kerukunan Umat Beraga',
                'capaian_sebelumnya' => '78',
                'target_tahun_ini' => '79',
                'capaian_tahun_ini' => '78',
            ]
        ];

        return view('user/lakip_opd', [
            'lakipOpdData' => $lakipOpdData
        ]);
    }

    public function iku_opd()
    {
        $tahunList = ['2025', '2026', '2027', '2028'];
        
        $ikuOpdData = [
            [
                'sasaran' => 'Pendidikan',
                'indikator' => 'Meningkatkan Mutu Pendidikan',
                'definisi' => 'Angka Partisipasi Sekolah',
                'satuan' => '%',
                'target_capaian' => [
                    '2025' => '95',
                    '2026' => '95',
                    '2027' => '95',
                    '2028' => '95',
                ]
                ],
            [
                'sasaran' => 'Pendidikan',
                'indikator' => 'Meningkatkan Mutu Pendidikan',
                'definisi' => 'Angka Partisipasi Sekolah',
                'satuan' => '%',
                'target_capaian' => [
                    '2025' => '95',
                    '2026' => '95',
                    '2027' => '95',
                    '2028' => '95',
                ]
            ]
        ];

        return view('user/iku_opd',
        [
            'tahunList' => $tahunList,
            'ikuOpdData' => $ikuOpdData
        ]);
    }

    public function pk_pimpinan()
    {
        $pkPimpinanData = [
            [
                'tahun' => '2023',
                'misi' => 'Meningkatkan kualitas sumber daya manusia yang berdaya saing',
                'sasaran' => 'Meningkatkan kualitas pendidikan',
                'indikator' => 'Angkat Partisipsi Sekolah',
                'target' => '98%',
            ],
            [
                'tahun' => '2023',
                'misi' => 'Meningkatkan kualitas sumber daya manusia yang berdaya saing',
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
