<?php

namespace App\Controllers;

class User extends BaseController
{
    public function index()
    {
        return view('user/dashboard');
    }

    public function rkt()
    {
    // Simulasi data dari database
        $rktData = [
            [
                'sasaran' => 'indeks Keterbukaan Informasi Publik',
                'indikator' => 'Nilai Indeks didapat dari hasil penilaian indeks Keterbukaan Informasi Publik oleh Komisi Informasi',
                'target' => '2025'
            ],
            [
                'sasaran' => 'indeks Keterbukaan Informasi Publik',
                'indikator' => 'Nilai Indeks didapat dari hasil penilaian indeks Keterbukaan Informasi Publik oleh Komisi Informasi',
                'target' => '2025'
            ],
            [
                'sasaran' => 'indeks Keterbukaan Informasi Publik',
                'indikator' => 'Nilai Indeks didapat dari hasil penilaian indeks Keterbukaan Informasi Publik oleh Komisi Informasi',
                'target' => '2025'
            ],
        ];

        return view('user/rkt', [
            'rktData' => $rktData
        ]);
    }

    public function rpjmd()
    {
        $tahunList = ['2019', '2020', '2021', '2022', '2023', '2024'];
        $rpjmdData = [
            [
                'misi' => 'Meningkatkan ketahanan nasional',
                'tujuan' => 'Terwujudnya masyarakat yang berdaya',
                'indikator' => 'Indeks Demokrasi Indonesia (IDI)',
                'target' => '76',
                'sasaran' => 'Meningkatkan kualitas demokrasi di daerah',
                'strategi' => 'Isi strategi',
                'target_capaian' => [
                    '2019' => '0.00',
                    '2020' => '72.00',
                    '2021' => '72.00',
                    '2022' => '75.00',
                    '2023' => '75.00',
                    '2024' => '78.6'
                ]
            ], 
        ];

        return view('user/rpjmd', [
            'tahunList' => $tahunList,
            'rpjmdData' => $rpjmdData
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
