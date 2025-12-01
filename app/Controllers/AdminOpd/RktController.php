<?php

namespace App\Controllers\AdminOpd;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;
use App\Models\Opd\RenstraModel;
use App\Models\Opd\RenjaModel;
use App\Models\RpjmdModel;
use App\Models\OpdModel;
use App\Models\PkModel;
use App\Models\RktModel;
use App\Models\ProgramPkModel;
use Config\Database;

class RktController extends BaseController
{
    protected $renstraModel;
    protected $opdModel;
    protected $rktModel;
    protected $programPkModel;
    protected $db;

    public function __construct()
    {
        $this->renstraModel = new RenstraModel();
        $this->opdModel = new OpdModel();
        $this->rktModel = new RktModel();
        $this->programPkModel = new ProgramPkModel();
        $this->db = \Config\Database::connect();

    }

    /**
     * INDEX RKT
     * route: GET adminopd/rkt
     */
    public function index()
    {
        $db = Database::connect();
        $role = session()->get('role');
        $opdId = session()->get('opd_id');

        // ------------ FILTER ------------
        $filterSasaran = $this->request->getGet('sasaran') ?? 'all';  // indikator_id
        $filterTahun = $this->request->getGet('tahun') ?? 'all';    // <-- bisa 'all'
        $filterStatus = $this->request->getGet('status') ?? 'all';

        // OPD aktif
        $currentOpd = $db->table('opd')
            ->where('id', $opdId)
            ->get()
            ->getRowArray();

        // ------------ AMBIL INDIKATOR (RENSTRA) UNTUK OPD INI ------------
        $indikators = $db->table('renstra_indikator_sasaran i')
            ->select('i.*, s.sasaran, s.opd_id')
            ->join('renstra_sasaran s', 's.id = i.renstra_sasaran_id', 'left')
            ->where('s.opd_id', $opdId)
            ->orderBy('i.id', 'ASC')
            ->get()
            ->getResultArray();

        $rktdata = [];

        foreach ($indikators as $ind) {
            $indikatorId = $ind['id'];

            // filter indikator
            if ($filterSasaran !== 'all' && (string) $filterSasaran !== (string) $indikatorId) {
                continue;
            }

            // ------------ AMBIL RKT (PROGRAM) ------------
            $builderRkt = $db->table('rkt r')
                ->select('r.*, p.program_kegiatan AS program_nama')
                ->join('program_pk p', 'p.id = r.program_id', 'left')
                ->where('r.indikator_id', $indikatorId)
                ->where('r.opd_id', $opdId);

            // kalau TAHUN bukan 'all' baru difilter
            if (!empty($filterTahun) && $filterTahun !== 'all') {
                $builderRkt->where('r.tahun', $filterTahun);
            }

            if ($filterStatus !== 'all') {
                $builderRkt->where('r.status', $filterStatus);
            }

            $rkts = $builderRkt
                ->orderBy('r.id', 'ASC')
                ->get()
                ->getResultArray();

            // ... (bagian susun nested kegiatan/sub tetap seperti punyamu)
            // KEGIATAN + SUB KEGIATAN
            foreach ($rkts as &$rkt) {
                $kegiatans = $db->table('rkt_kegiatan rk')
                    ->select('rk.id, rk.kegiatan_id, k.kegiatan')
                    ->join('kegiatan_pk k', 'k.id = rk.kegiatan_id', 'left')
                    ->where('rk.rkt_id', $rkt['id'])
                    ->orderBy('rk.id', 'ASC')
                    ->get()
                    ->getResultArray();

                foreach ($kegiatans as &$keg) {
                    $subs = $db->table('rkt_subkegiatan rs')
                        ->select('rs.id, rs.sub_kegiatan_id, sk.sub_kegiatan, sk.anggaran')
                        ->join('sub_kegiatan_pk sk', 'sk.id = rs.sub_kegiatan_id', 'left')
                        ->where('rs.rkt_kegiatan_id', $keg['id'])
                        ->orderBy('rs.id', 'ASC')
                        ->get()
                        ->getResultArray();

                    $keg['subkegiatan'] = $subs ?: [];
                }
                unset($keg);

                $rkt['kegiatan'] = $kegiatans ?: [];
            }
            unset($rkt);

            $ind['rkts'] = $rkts;
            $rktdata[] = $ind;
        }

        // ------------ DATA UNTUK FILTER TAHUN (dari RENSTRA_TARGET) ------------
        $yearRows = $db->table('renstra_target rt')
            ->select('DISTINCT rt.tahun', false)
            ->join('renstra_indikator_sasaran i', 'i.id = rt.renstra_indikator_id', 'left')
            ->join('renstra_sasaran s', 's.id = i.renstra_sasaran_id', 'left')
            ->where('s.opd_id', $opdId)
            ->orderBy('rt.tahun', 'ASC')
            ->get()
            ->getResultArray();

        $availableYears = array_column($yearRows, 'tahun');

        return view('adminOpd/rkt/rkt', [
            'title' => 'RENJA (RKT)',
            'role' => $role,
            'rktdata' => $rktdata,
            'sasaranList' => $sasaranList ?? [],
            'available_years' => $availableYears,
            'filter_sasaran' => $filterSasaran,
            'filter_tahun' => $filterTahun,   // bisa 'all'
            'filter_status' => $filterStatus,
            'currentOpd' => $currentOpd,
        ]);
    }



    // /**
    //  * Toggle status draft <-> selesai untuk semua RKT
    //  * dari satu indikator + tahun.
    //  * route: POST adminopd/rkt/update-status
    //  */
    // public function updateStatus()
    // {
    //     $db = Database::connect();

    //     $indikatorId = $this->request->getPost('indikator_id');
    //     $tahun       = $this->request->getPost('tahun');

    //     if (!$indikatorId || !$tahun) {
    //         return redirect()->back()
    //             ->with('error', 'Data indikator atau tahun tidak lengkap.');
    //     }

    //     // Ambil semua RKT untuk indikator + tahun itu
    //     $rkts = $db->table('rkt')
    //         ->where('indikator_id', $indikatorId)
    //         ->where('tahun', $tahun)
    //         ->get()
    //         ->getResultArray();

    //     if (empty($rkts)) {
    //         return redirect()->back()
    //             ->with('error', 'Belum ada RKT untuk indikator & tahun ini.');
    //     }

    //     // Jika semua sudah selesai => ubah jadi draft
    //     // Jika ada yang masih draft => ubah semua jadi selesai
    //     $allSelesai = true;
    //     foreach ($rkts as $r) {
    //         if ($r['status'] !== 'selesai') {
    //             $allSelesai = false;
    //             break;
    //         }
    //     }

    //     $newStatus = $allSelesai ? 'draft' : 'selesai';

    //     $db->table('rkt')
    //         ->where('indikator_id', $indikatorId)
    //         ->where('tahun', $tahun)
    //         ->update([
    //             'status'     => $newStatus,
    //             'updated_at' => date('Y-m-d H:i:s'),
    //         ]);

    //     return redirect()->back()
    //         ->with('success', 'Status RKT berhasil diubah.');
    // }

    /**
     * FORM TAMBAH RKT
     * $indikatorId = id dari tabel renstra_indikator_sasaran
     */
    public function tambah($indikatorId)
    {
        $indikator = $this->db->table('renstra_indikator_sasaran')
            ->where('id', $indikatorId)
            ->get()
            ->getRowArray();

        if (!$indikator) {
            return redirect()->back()->with('error', 'Indikator tidak ditemukan');
        }

        // tahun bisa dari query string ?tahun=2025 atau default tahun sekarang
        $tahun = $this->request->getGet('tahun') ?? date('Y');

        // DATA MASTER
        $programPk = $this->programPkModel->findAll();          // tabel program_pk
        $kegiatanPk = $this->programPkModel->getAllKegiatan();   // tabel kegiatan_pk
        $subKegiatanPk = $this->programPkModel->getAllSubKegiatan();// tabel sub_kegiatan_pk

        $data = [
            'title' => 'Tambah RENJA (RKT)',
            'role' => 'admin_opd',
            'indikator' => $indikator,
            'tahun' => $tahun,
            'program' => $programPk,
            'kegiatanPk' => $kegiatanPk,
            'subKegiatanPk' => $subKegiatanPk,
        ];

        return view('adminOpd/rkt/tambah_rkt', $data);
    }

    public function save()
    {
        $data = $this->request->getPost();

        // dd($data);
        $data['opd_id'] = session()->get('opd_id'); // atau sesuai field sesi kamu
        $rktModel = new \App\Models\RktModel();
        try {
            if ($rktModel->saveRkt($data)) {
                return redirect()->to('/adminopd/rkt')->with('success', 'Data berhasil disimpan');
            } else {
                log_message('error', 'Gagal menyimpan data RKT: ' . print_r($data, true));

                return redirect()->back()->with('error', 'Terjadi kesalahan saat menyimpan data');
            }

        } catch (\Exception $e) {
            log_message('error', 'Error saving PK: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error: ' . $e->getMessage());
        }

    }

    // app/Controllers/AdminOpd/RktController.php

    public function edit($indikatorId)
    {
        $db = \Config\Database::connect();
        $role = session()->get('role');

        $programModel = new \App\Models\ProgramPkModel();

        // ---------- Ambil indikator + sasaran ----------
        $indikator = $db->table('renstra_indikator_sasaran i')
            ->select('i.*, s.sasaran')
            ->join('renstra_sasaran s', 's.id = i.renstra_sasaran_id', 'left')
            ->where('i.id', $indikatorId)
            ->get()
            ->getRowArray();

        if (!$indikator) {
            return redirect()->back()->with('error', 'Indikator tidak ditemukan.');
        }

        // ---------- Ambil semua RKT (program) utk indikator ini ----------
        $rkts = $db->table('rkt r')
            ->select('r.id, r.program_id, r.tahun, r.status')
            ->where('r.indikator_id', $indikatorId)
            ->orderBy('r.id', 'ASC')
            ->get()
            ->getResultArray();

        // kalau belum ada RKT, pakai tahun sekarang
        $tahun = !empty($rkts) ? ($rkts[0]['tahun'] ?? date('Y')) : date('Y');

        // ---------- Susun nested: program -> kegiatan -> subkegiatan ----------
        $rktPrograms = [];   // ini yang dipakai view utk pre-fill

        foreach ($rkts as $rktRow) {
            $rktId = $rktRow['id'];

            // kegiatan untuk rkt ini
            $kegiatans = $db->table('rkt_kegiatan rk')
                ->select('rk.id, rk.kegiatan_id')
                ->where('rk.rkt_id', $rktId)
                ->orderBy('rk.id', 'ASC')
                ->get()
                ->getResultArray();

            $kegiatanNested = [];

            foreach ($kegiatans as $kegRow) {
                $rkKegId = $kegRow['id'];
                $kegiatanId = $kegRow['kegiatan_id'];

                // subkegiatan utk rkt_kegiatan ini
                $subs = $db->table('rkt_subkegiatan rs')
                    ->select('rs.id, rs.sub_kegiatan_id')
                    ->where('rs.rkt_kegiatan_id', $rkKegId)
                    ->orderBy('rs.id', 'ASC')
                    ->get()
                    ->getResultArray();

                $subNested = [];
                foreach ($subs as $sRow) {
                    $subNested[] = [
                        'sub_kegiatan_id' => $sRow['sub_kegiatan_id'],
                    ];
                }

                $kegiatanNested[] = [
                    'kegiatan_id' => $kegiatanId,
                    'subkegiatan' => $subNested,
                ];
            }

            $rktPrograms[] = [
                'program_id' => $rktRow['program_id'],
                'kegiatan' => $kegiatanNested,
            ];
        }

        // ---------- master program / kegiatan / sub ----------
        $programs = $programModel->findAll();              // table program_pk
        $kegiatanPk = $programModel->getAllKegiatan();       // table kegiatan_pk
        $subKegiatanPk = $programModel->getAllSubKegiatan();    // table sub_kegiatan_pk

        // ---------- kirim ke view ----------
        return view('adminOpd/rkt/edit_rkt', [
            'title' => 'Edit RENJA (RKT)',
            'role' => $role,
            'indikator' => $indikator,
            'tahun' => $tahun,
            'program' => $programs,
            'kegiatanPk' => $kegiatanPk,
            'subKegiatanPk' => $subKegiatanPk,
            'rktPrograms' => $rktPrograms,   // <- prefill
        ]);
    }

    // app/Controllers/Adminopd/RenjaController.php (method update)
    public function update()
    {
        $db = \Config\Database::connect();

        // data dasar
        $indikatorId = $this->request->getPost('indikator_id');
        $opdId = session()->get('opd_id') ?? $this->request->getPost('opd_id');
        $tahun = $this->request->getPost('tahun') ?? date('Y');

        if (!$indikatorId || !$opdId) {
            return redirect()->back()->with('error', 'Data dasar (indikator / OPD) tidak lengkap.');
        }

        $postPrograms = $this->request->getPost('program') ?? [];

        $db->transStart();

        try {
            // ========== 1. Hapus semua RKT lama indikator ini ==========
            // cari semua rkt.id untuk indikator ini
            $oldRkts = $db->table('rkt')
                ->select('id')
                ->where('indikator_id', $indikatorId)
                ->get()
                ->getResultArray();

            $oldRktIds = array_column($oldRkts, 'id');

            if (!empty($oldRktIds)) {
                // cari semua rkt_kegiatan.id
                $oldKegs = $db->table('rkt_kegiatan')
                    ->select('id')
                    ->whereIn('rkt_id', $oldRktIds)
                    ->get()
                    ->getResultArray();

                $oldKegIds = array_column($oldKegs, 'id');

                if (!empty($oldKegIds)) {
                    // hapus subkegiatan
                    $db->table('rkt_subkegiatan')
                        ->whereIn('rkt_kegiatan_id', $oldKegIds)
                        ->delete();

                    // hapus kegiatan
                    $db->table('rkt_kegiatan')
                        ->whereIn('id', $oldKegIds)
                        ->delete();
                }

                // hapus rkt
                $db->table('rkt')
                    ->whereIn('id', $oldRktIds)
                    ->delete();
            }

            // ========== 2. Insert ulang berdasarkan POST ==========
            foreach ($postPrograms as $p) {
                $programId = isset($p['program_id']) ? (int) $p['program_id'] : 0;
                if (!$programId) {
                    // kalau program belum dipilih, skip
                    continue;
                }

                // insert RKT (program)
                $db->table('rkt')->insert([
                    'opd_id' => $opdId,
                    'tahun' => $tahun,
                    'indikator_id' => $indikatorId,
                    'program_id' => $programId,
                    'status' => 'draft',
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
                $rktId = $db->insertID();

                // kegiatan di dalam program ini
                $kegList = $p['kegiatan'] ?? [];
                foreach ($kegList as $k) {
                    $kegiatanId = isset($k['kegiatan_id']) ? (int) $k['kegiatan_id'] : 0;
                    if (!$kegiatanId) {
                        continue;
                    }

                    // insert rkt_kegiatan
                    $db->table('rkt_kegiatan')->insert([
                        'rkt_id' => $rktId,
                        'kegiatan_id' => $kegiatanId,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
                    $rktKegId = $db->insertID();

                    // subkegiatan di dalam kegiatan ini
                    $subs = $k['subkegiatan'] ?? [];
                    foreach ($subs as $s) {
                        $subId = isset($s['sub_kegiatan_id']) ? (int) $s['sub_kegiatan_id'] : 0;
                        if (!$subId) {
                            continue;
                        }

                        $db->table('rkt_subkegiatan')->insert([
                            'rkt_kegiatan_id' => $rktKegId,
                            'sub_kegiatan_id' => $subId,
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s'),
                        ]);
                    }
                }
            }

            $db->transComplete();

            if ($db->transStatus() === false) {
                return redirect()->back()->with('error', 'Gagal menyimpan perubahan (transaksi gagal).');
            }

            return redirect()->to(base_url('adminopd/rkt'))
                ->with('success', 'Perubahan RKT berhasil disimpan.');
        } catch (\Throwable $th) {
            $db->transRollback();
            return redirect()->back()
                ->with('error', 'Terjadi kesalahan: ' . $th->getMessage());
        }
    }

    public function updateStatus()
    {
        $db = \Config\Database::connect();

        $indikatorId = (int) $this->request->getPost('indikator_id');
        $tahun = $this->request->getPost('tahun') ?: date('Y');

        if (!$indikatorId) {
            return redirect()->back()->with('error', 'Indikator tidak valid.');
        }

        try {
            // Ambil semua RKT untuk indikator + tahun ini
            $rows = $db->table('rkt')
                ->where('indikator_id', $indikatorId)
                ->where('tahun', $tahun)
                ->get()
                ->getResultArray();

            if (empty($rows)) {
                return redirect()->back()->with(
                    'error',
                    'Data RKT untuk indikator dan tahun tersebut tidak ditemukan.'
                );
            }

            // Cek apakah semua sudah selesai
            $allSelesai = true;
            foreach ($rows as $row) {
                if (($row['status'] ?? '') !== 'selesai') {
                    $allSelesai = false;
                    break;
                }
            }

            // Kalau semua selesai → jadi draft
            // Kalau ada yang belum selesai → jadikan selesai
            $newStatus = $allSelesai ? 'draft' : 'selesai';

            $db->table('rkt')
                ->where('indikator_id', $indikatorId)
                ->where('tahun', $tahun)
                ->update([
                    'status' => $newStatus,
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);

            return redirect()->back()->with(
                'success',
                'Status RKT berhasil diubah menjadi: ' . ucfirst($newStatus)
            );
        } catch (\Throwable $th) {
            return redirect()->back()->with(
                'error',
                'Gagal mengubah status RKT: ' . $th->getMessage()
            );
        }
    }
}
