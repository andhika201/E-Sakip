<?php

namespace App\Controllers;
use App\Controllers\BaseController;
use App\Models\RpjmdModel;
use App\Models\RkpdModel;
use App\Models\LakipKabupatenModel;
use App\Models\PkBupatiModel;
use App\Models\ProgramPkModel;
use App\Models\OpdModel;
use App\Models\Opd\RenstraModel;
use App\Models\Opd\RenjaModel;
use App\Models\Opd\IkuOpdModel;
use App\Models\Opd\PkModel;
use App\Models\Opd\LakipOpdModel;


class UserController extends BaseController
{
    protected $rpjmdModel;
    protected $rkpdModel;
    protected $pkBupatiModel;
    protected $programPkModel;
    protected $lakipModel;
    protected $OpdModel;
    protected $renstraModel;
    protected $renjaModel;
    protected $ikuOpdModel;
    protected $pkOpdModel;
    protected $lakipOpdModel;

    public function __construct()
    {
        $this->rpjmdModel = new RpjmdModel();
        $this->rkpdModel = new RkpdModel();
        $this->lakipModel = new LakipKabupatenModel();
        $this->pkBupatiModel = new PkBupatiModel();
        $this->programPkModel = new ProgramPkModel();
        $this->renstraModel = new RenstraModel();
        $this->renjaModel = new RenjaModel();
        $this->ikuOpdModel = new IkuOpdModel();
        $this->pkOpdModel = new PkModel();
        $this->lakipOpdModel = new LakipOpdModel();
        $this->OpdModel = new OpdModel();

        helper(['form', 'url']);
    }
    public function index()
    {
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
        $status = 'selesai';
        // Get all RKPD data (no server-side filtering)
        $rkpdData = $this->rkpdModel->getAllRkpdByStatus($status);
        
        // Get unique years for filter dropdown
        $availableYears = [];
        foreach ($rkpdData as $rkpd) {
            foreach ($rkpd['indikator'] as $indikator) {
                if (!empty($indikator['tahun']) && !in_array($indikator['tahun'], $availableYears)) {
                    $availableYears[] = $indikator['tahun'];
                }
            }
        }
        sort($availableYears);

        $data = [
            'title' => 'Rencana Kerja Tahunan',
            'rkpd_data' => $rkpdData,
            'available_years' => $availableYears
        ];
        
        return view('user/rkpd', $data);
    }

    public function lakipKabupaten()
    {
        // Get filter parameters
        $tahun = $this->request->getVar('tahun');
        $status = 'selesai'; 

        // Build query with filters
        $builder = $this->lakipModel->orderBy('created_at', 'DESC')->where('status', $status);
        
        if ($tahun) {
            $builder = $builder->where('YEAR(tanggal_laporan)', $tahun)->where('status', $status);
        }
          
        // Get all data
        $lakips = $builder->findAll();

        $data = [
            'lakips' => $lakips,
            'availableYears' => $this->lakipModel->getAvailableYears(),
            'selected_year' => $tahun,
            'filters' => [
                'tahun' => $tahun,
            ]
        ];
        
        return view('user/lakip_kabupaten', $data);
    }

    /**
     * Download file LAKIP
     */
    public function downloadLakip($id)
    {
        $lakip = $this->lakipModel->find($id);
        
        if (!$lakip || empty($lakip['file'])) {
            return redirect()->to('/user/lakip_kabupaten')
                           ->with('error', 'File tidak ditemukan');
        }

        $filePath = WRITEPATH . 'uploads/lakip/kabupaten/' . $lakip['file'];
        
        if (!file_exists($filePath)) {
            return redirect()->to('/user/lakip_kabupaten')
                           ->with('error', 'File tidak ditemukan di server');
        }

        // Get file extension from stored file
        $fileExtension = pathinfo($lakip['file'], PATHINFO_EXTENSION);
        
        // Clean judul for filename (remove special characters)
        $cleanJudul = preg_replace('/[^a-zA-Z0-9\s\-_]/', '', $lakip['judul']);
        $cleanJudul = preg_replace('/\s+/', '_', trim($cleanJudul));
        
        // Ensure filename doesn't exceed limits
        if (strlen($cleanJudul) > 100) {
            $cleanJudul = substr($cleanJudul, 0, 100);
        }
        
        // Create download filename with judul + extension
        $downloadName = $cleanJudul . '.' . $fileExtension;

        // Force download with custom filename
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $downloadName . '"');
        header('Content-Length: ' . filesize($filePath));
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: no-cache');
        
        readfile($filePath);
        exit;
    }

    public function pkBupati()
    {
        // Get filter parameter
        $tahun = $this->request->getVar('tahun');
        
        // Get available years from database
        $availableYears = $this->pkBupatiModel->getAvailableYears();
        
        // Get PK data dengan filter tahun jika ada
        if ($tahun) {
            $pkData = $this->pkBupatiModel->getCompletePkByYear($tahun);
        } else {
            $pkData = $this->pkBupatiModel->getCompletePk();
        }
        
        // Load the view for PK Bupati
        $data = [
            'pk_data' => $pkData,
            'available_years' => $availableYears,
            'selected_year' => $tahun,
            'title' => 'Perjanjian Kinerja - Bupati'
        ];

        return view('user/pk_bupati', $data);
    }

    public function renstra()
    {
        // Get all OPD for filter dropdown
        $opdData = $this->OpdModel->getAllOpd();

        // Get all Renstra data (no server-side filtering)
        $renstraData = $this->renstraModel->getAllCompletedRenstra(null);

        // Get all available periods for filter dropdown
        $availablePeriods = [];
        foreach ($renstraData as $data) {
            $periodKey = $data['tahun_mulai'] . '-' . $data['tahun_akhir'];
            if (!in_array($periodKey, $availablePeriods)) {
                $availablePeriods[] = $periodKey;
            }
        }
        sort($availablePeriods);
        
        $data = [
            'renstra_data' => $renstraData,
            'opd_data' => $opdData,
            'available_periods' => $availablePeriods,
            'title' => 'Rencana Strategis'
        ];

        return view('user/renstra', $data);
    }

    public function renja()
    {
        // Get all OPD for filter dropdown
        $opdData = $this->OpdModel->getAllOpd();

        // Get all RENJA data (no server-side filtering - pass null for all data)
        $renjaData = $this->renjaModel->getAllCompletedRenja(null);
        
        // Get unique years for filter dropdown
        $availableYears = [];
        foreach ($renjaData as $renja) {
            foreach ($renja['indikator'] as $indikator) {
                if (!empty($indikator['tahun']) && !in_array($indikator['tahun'], $availableYears)) {
                    $availableYears[] = $indikator['tahun'];
                }
            }
        }
        sort($availableYears);

        $data = [
            'title' => 'Rencana Kerja Tahunan',
            'renja_data' => $renjaData,
            'opd_data' => $opdData,
            'available_years' => $availableYears
        ];
        
        return view('user/renja', $data);
    }    

    public function ikuOpd()
    {
        // Get all OPD for filter dropdown
        $opdData = $this->OpdModel->getAllOpd();

        // Get IKU data filtered by user's OPD
        $ikuData = $this->ikuOpdModel->getCompletedIkuOpd(null);

        $groupedData = [];
        foreach ($ikuData as $data) {
            $periodKey = $data['tahun_mulai'] . '-' . $data['tahun_akhir'];
            
            if (!isset($groupedData[$periodKey])) {
                $groupedData[$periodKey] = [
                    'period' => $periodKey,
                    'tahun_mulai' => $data['tahun_mulai'],
                    'tahun_akhir' => $data['tahun_akhir'],
                    'years' => range($data['tahun_mulai'], $data['tahun_akhir']),
                    'iku_data' => []
                ];
            }

            $groupedData[$periodKey]['iku_data'][] = $data;
        }
        
        // Sort periods
        ksort($groupedData);
        
        // Extract available periods for filter
        $availablePeriods = [];
        foreach ($groupedData as $periodData) {
            $availablePeriods[] = $periodData['period'];
        }
        $availablePeriods = array_unique($availablePeriods);
        sort($availablePeriods);
        
        $data = [
            'iku_data' => $ikuData,
            'grouped_data' => $groupedData,
            'opd_data' => $opdData,
            'available_periods' => $availablePeriods,
            'title' => 'IKU OPD'
        ];

        return view('user/iku_opd', $data);
    }

    public function lakipOpd()
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


    public function pkJpt()
    {
        // Get all OPD for filter dropdown
        $opdData = $this->OpdModel->getAllOpd();

        // Get PK JPT data 
        $pkJptData = $this->pkOpdModel->getCompletePkByRole('jpt');

        // Get available years for filter dropdown
        $availableYears = [];
        foreach ($pkJptData as $pk) {
            $year = date('Y', strtotime($pk['tanggal']));
            if (!in_array($year, $availableYears)) {
                $availableYears[] = $year;
            }
        }
        sort($availableYears);

        return view('user/pk_jpt', [
            'pkJptData' => $pkJptData,
            'opd_data' => $opdData,
            'available_years' => $availableYears
        ]);
    }


    public function pkAdministrator()
    {
        // Get all OPD for filter dropdown
        $opdData = $this->OpdModel->getAllOpd();

        // Get PK Administrator data 
        $pkAdminData = $this->pkOpdModel->getCompletePkByRole('administrator');

        // Get available years for filter dropdown
        $availableYears = [];
        foreach ($pkAdminData as $pk) {
            $year = date('Y', strtotime($pk['tanggal']));
            if (!in_array($year, $availableYears)) {
                $availableYears[] = $year;
            }
        }
        sort($availableYears);

        return view('user/pk_administrator', [
            'pkAdminData' => $pkAdminData,
            'opd_data' => $opdData,
            'available_years' => $availableYears
        ]);
    }

    public function pkPengawas()
    {
        // Get all OPD for filter dropdown
        $opdData = $this->OpdModel->getAllOpd();

        // Get PK Pengawas data 
        $pkPengawasData = $this->pkOpdModel->getCompletePkByRole('pengawas');

        // Get available years for filter dropdown
        $availableYears = [];
        foreach ($pkPengawasData as $pk) {
            $year = date('Y', strtotime($pk['tanggal']));
            if (!in_array($year, $availableYears)) {
                $availableYears[] = $year;
            }
        }
        sort($availableYears);

        return view('user/pk_pengawas', [
            'pkPengawasData' => $pkPengawasData,
            'opd_data' => $opdData,
            'available_years' => $availableYears
        ]);
    }

    public function tentang_kami()
    {
        return view('user/tentang_kami');
    }

}
