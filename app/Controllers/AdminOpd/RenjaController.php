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

class RenjaController extends BaseController
{
    protected $renstraModel;
    protected $renjaModel;
    protected $rpjmdModel;
    protected $opdModel;
    protected $pkModel;
    protected $rktModel;
    protected $programModel;

    public function __construct()
    {
        $this->renstraModel = new RenstraModel();
        $this->renjaModel = new RenjaModel();
        $this->rpjmdModel = new RpjmdModel();
        $this->opdModel = new OpdModel();
        $this->pkModel = new PkModel();
        $this->rktModel = new RktModel();

    }

    public function index()
    {
        $session = session();
        $opdId = $session->get('opd_id');
        $tahun = (int) ($this->request->getGet('tahun') ?? date('Y'));

        if (!$opdId) {
            throw new \Exception("OPD id required");
        }

        $currentOpd = $this->opdModel->getOpdById($opdId);
        $indicators = $this->rktModel->getIndicatorsWithRkt($opdId, $tahun);

        // dd($indicators);
        // kirim ke view
        return view('adminOpd/rkt/rkt', [
            'currentOpd' => $currentOpd,
            'rktdata' => $indicators,
            'tahun' => $tahun,
            'opdId' => $opdId,
        ]);
    }

    public function tambah($indikatorId = null)
    {
        // Get OPD ID from session (logged in user's OPD)
        $session = session();
        $opdId = $session->get('opd_id');
        $role = $session->get('role');

        $status = 'selesai';

        // If no OPD ID in session, redirect to login or show error
        if (!$opdId) {
            return redirect()->to('/login')->with('error', 'Silakan login terlebih dahulu');
        }

        $indikator = null;
        if ($indikatorId) {
            $db = \Config\Database::connect();
            if ($role == 'admin_kab') {
                // Ambil dari tabel RPJMD
                $indikator = $db->table('rpjmd_indikator_sasaran')
                    ->where('id', $indikatorId)
                    ->get()
                    ->getRowArray();
            } else {
                // Default admin_opd ambil dari tabel Renstra
                $indikator = $db->table('renstra_indikator_sasaran')
                    ->where('id', $indikatorId)
                    ->get()
                    ->getRowArray();
            }
        }

        $program = $this->pkModel->getAllPrograms();


        // Get current OPD info
        $currentOpd = $this->opdModel->find($opdId);
        if (!$currentOpd) {
            return redirect()->to('/login')->with('error', 'Data OPD tidak ditemukan');
        }

        // Get RENSTRA Sasaran from completed Renstra only
        $renstraSasaran = $this->renstraModel->getAllRenstraByStatus($status, $opdId);

        // dd($renstraSasaran);
        $data = [
            'renstra_sasaran' => $renstraSasaran,
            'title' => 'Tambah Rencana Kerja Tahunan',
            'currentOpd' => $currentOpd,
            'indikator' => $indikator,
            'program' => $program,
            'role' => $role,
            'validation' => \Config\Services::validation()
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

    public function edit($indikatorId)
    {
        $db = \Config\Database::connect();
        $role = session()->get('role');

        // Models
        $programModel = new \App\Models\ProgramPkModel();

        // Ambil indikator (renstra_indikator_sasaran) + nama sasaran
        $indicator = $db->table('renstra_indikator_sasaran i')
            ->select('i.*, s.sasaran')
            ->join('renstra_sasaran s', 's.id = i.renstra_sasaran_id', 'left')
            ->where('i.id', $indikatorId)
            ->get()
            ->getRowArray();

        if (!$indicator) {
            // jika indikator tidak ditemukan, redirect atau tampilkan error sesuai alur aplikasi
            return redirect()->back()->with('error', 'Indikator tidak ditemukan.');
        }

        // Ambil semua RKT (program) untuk indikator ini — MULTI PROGRAM
        $rkts = $db->table('rkt r')
            ->select('r.*, p.program_kegiatan AS program_nama')
            ->join('program_pk p', 'p.id = r.program_id', 'left')
            ->where('r.indikator_id', $indikatorId)
            ->orderBy('r.id', 'ASC')
            ->get()
            ->getResultArray();

        // Untuk tiap rkt (program) ambil kegiatan -> subkegiatan, dan attach sebagai nested
        foreach ($rkts as &$rkt) {
            // ambil kegiatan milik rkt ini
            $kegiatans = $db->table('rkt_kegiatan rk')
                ->select('rk.*')
                ->where('rk.rkt_id', $rkt['id'])
                ->orderBy('rk.id', 'ASC')
                ->get()
                ->getResultArray();

            // untuk tiap kegiatan ambil subkegiatan
            foreach ($kegiatans as &$keg) {
                $subs = $db->table('rkt_subkegiatan rs')
                    ->select('rs.*')
                    ->where('rs.kegiatan_id', $keg['id'])
                    ->orderBy('rs.id', 'ASC')
                    ->get()
                    ->getResultArray();

                $keg['subkegiatan'] = $subs ?: [];
            }

            // attach kegiatan ke rkt
            $rkt['kegiatan'] = $kegiatans ?: [];
        }

        // attach rkts ke indikator -> sesuai struktur view yang kamu gunakan
        $indicator['rkts'] = $rkts ?: [];

        // daftar program untuk dropdown
        $programs = $programModel->findAll();

        // kirim data ke view (view memakai $indicator dan $programs)
        return view('adminOpd/rkt/edit_rkt', [
            'indicator' => $indicator,
            'programs' => $programs,
            'role' => $role,
        ]);
    }


    // app/Controllers/Adminopd/RenjaController.php (method update)
    public function update()
    {
        $db = \Config\Database::connect();
        $builderRkt = $db->table('rkt');
        $builderKeg = $db->table('rkt_kegiatan');
        $builderSub = $db->table('rkt_subkegiatan');

        // ambil post
        $postPrograms = $this->request->getPost('program') ?? [];
        $deletedSubs = $this->request->getPost('deleted_subkegiatan_ids') ?? [];
        $deletedKegs = $this->request->getPost('deleted_kegiatan_ids') ?? [];
        $deletedRkts = $this->request->getPost('deleted_program_ids') ?? [];

        // ambil indikator_id & opd_id & tahun (tahun bisa dikirimkan dari form; fallback ke tahun sekarang)
        $indikatorId = $this->request->getPost('indikator_id');
        $opdId = session()->get('opd_id') ?? $this->request->getPost('opd_id') ?? null;
        $tahun = $this->request->getPost('tahun') ?? date('Y');

        // helper kecil untuk bersihkan angka (target_anggaran)
        $cleanNumber = function ($v) {
            if ($v === null || $v === '')
                return null;
            // hapus semua non-digit
            $digits = preg_replace('/[^\d]/', '', (string) $v);
            return $digits === '' ? null : (int) $digits;
        };

        $db->transStart();

        try {
            // 1) DELETE (dari daftar deleted_ids) — lakukan sub -> kegiatan -> rkt (cascade)
            if (!empty($deletedSubs)) {
                // pastikan array integer
                $deletedSubs = array_filter(array_map('intval', (array) $deletedSubs));
                if (!empty($deletedSubs)) {
                    $builderSub->whereIn('id', $deletedSubs)->delete();
                }
            }

            if (!empty($deletedKegs)) {
                $deletedKegs = array_filter(array_map('intval', (array) $deletedKegs));
                if (!empty($deletedKegs)) {
                    // hapus semua sub yang berhubungan dulu
                    $builderSub->whereIn('kegiatan_id', $deletedKegs)->delete();
                    // hapus kegiatan
                    $builderKeg->whereIn('id', $deletedKegs)->delete();
                }
            }

            if (!empty($deletedRkts)) {
                $deletedRkts = array_filter(array_map('intval', (array) $deletedRkts));
                if (!empty($deletedRkts)) {
                    // cari kegiatan id yang berhubungan
                    $kegs = $db->table('rkt_kegiatan')->select('id')->whereIn('rkt_id', $deletedRkts)->get()->getResultArray();
                    $kegIds = array_column($kegs, 'id');
                    if (!empty($kegIds)) {
                        $builderSub->whereIn('kegiatan_id', $kegIds)->delete();
                        $builderKeg->whereIn('id', $kegIds)->delete();
                    }
                    // hapus rkt (program) sendiri
                    $builderRkt->whereIn('id', $deletedRkts)->delete();
                }
            }

            // 2) Iterate posted programs (insert / update)
            // Each $p is: ['id' => rkt_id or '', 'program_id' => pk_id, 'kegiatan' => [...]]
            foreach ($postPrograms as $pIndex => $p) {
                // normalize
                $pId = isset($p['id']) && $p['id'] !== '' ? (int) $p['id'] : null;
                $programIdSelected = isset($p['program_id']) ? (int) $p['program_id'] : null;

                // if existing rkt row -> update program_id (and tahun/opd/indikator if needed)
                if ($pId) {
                    // safety: ensure the row exists and belongs to this indikator (optional)
                    $exists = $builderRkt->where('id', $pId)->where('indikator_id', $indikatorId)->get()->getRowArray();
                    if ($exists) {
                        $builderRkt->where('id', $pId)->update([
                            'program_id' => $programIdSelected,
                            'updated_at' => date('Y-m-d H:i:s'),
                        ]);
                        $rktId = $pId;
                    } else {
                        // jika tidak ditemukan (safety), treat as insert
                        $builderRkt->insert([
                            'opd_id' => $opdId,
                            'tahun' => $tahun,
                            'indikator_id' => $indikatorId,
                            'program_id' => $programIdSelected,
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s'),
                        ]);
                        $rktId = $db->insertID();
                    }
                } else {
                    // insert new rkt
                    $builderRkt->insert([
                        'opd_id' => $opdId,
                        'tahun' => $tahun,
                        'indikator_id' => $indikatorId,
                        'program_id' => $programIdSelected,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
                    $rktId = $db->insertID();
                }

                // proses kegiatan (jika ada)
                $kegList = $p['kegiatan'] ?? [];
                foreach ($kegList as $kIndex => $k) {
                    $kegId = isset($k['id']) && $k['id'] !== '' ? (int) $k['id'] : null;
                    $namaKeg = isset($k['nama_kegiatan']) ? trim($k['nama_kegiatan']) : null;

                    if ($kegId) {
                        // update existing kegiatan
                        $builderKeg->where('id', $kegId)->update([
                            'rkt_id' => $rktId,
                            'program_id' => $programIdSelected,
                            'nama_kegiatan' => $namaKeg,
                            'updated_at' => date('Y-m-d H:i:s'),
                        ]);
                        $kegiatanId = $kegId;
                    } else {
                        // insert new kegiatan
                        $builderKeg->insert([
                            'rkt_id' => $rktId,
                            'program_id' => $programIdSelected,
                            'nama_kegiatan' => $namaKeg,
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s'),
                        ]);
                        $kegiatanId = $db->insertID();
                    }

                    // proses subkegiatan
                    $subs = $k['subkegiatan'] ?? [];
                    foreach ($subs as $sIndex => $s) {
                        $subId = isset($s['id']) && $s['id'] !== '' ? (int) $s['id'] : null;
                        $namaSub = isset($s['nama_subkegiatan']) ? trim($s['nama_subkegiatan']) : null;
                        $targetRaw = $s['target_anggaran'] ?? null;
                        $target = $cleanNumber($targetRaw);

                        if ($subId) {
                            $builderSub->where('id', $subId)->update([
                                'kegiatan_id' => $kegiatanId,
                                'nama_subkegiatan' => $namaSub,
                                'target_anggaran' => $target,
                                'updated_at' => date('Y-m-d H:i:s'),
                            ]);
                        } else {
                            $builderSub->insert([
                                'kegiatan_id' => $kegiatanId,
                                'nama_subkegiatan' => $namaSub,
                                'target_anggaran' => $target,
                                'created_at' => date('Y-m-d H:i:s'),
                                'updated_at' => date('Y-m-d H:i:s'),
                            ]);
                        }
                    } // end subs loop
                } // end kegiatan loop
            } // end programs loop

            $db->transComplete();

            if ($db->transStatus() === false) {
                // rollback happened
                return redirect()->back()->with('error', 'Gagal menyimpan perubahan (transaksi gagal).');
            }

            return redirect()->to(base_url('adminopd/rkt'))->with('success', 'Perubahan RKT disimpan.');
        } catch (\Throwable $th) {
            $db->transRollback();
            // log error bila perlu
            // log_message('error', $th->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $th->getMessage());
        }
    }


    public function updateStatus()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setJSON(['success' => false, 'message' => 'Invalid request']);
        }

        // Get JSON input
        $json = $this->request->getJSON(true);
        $id = $json['id'] ?? null;

        if (!$id) {
            return $this->response->setJSON(['success' => false, 'message' => 'ID harus diisi']);
        }

        try {
            // Get current status
            $currentRenja = $this->renjaModel->getRenjaSasaranById($id);
            if (!$currentRenja) {
                return $this->response->setJSON(['success' => false, 'message' => 'Data tidak ditemukan']);
            }

            // Toggle status
            $currentStatus = $currentRenja['status'] ?? 'draft';
            $newStatus = $currentStatus === 'draft' ? 'selesai' : 'draft';

            $result = $this->renjaModel->updateRenjaStatus($id, $newStatus);

            if ($result) {
                return $this->response->setJSON([
                    'success' => true,
                    'message' => 'Status berhasil diupdate',
                    'oldStatus' => $currentStatus,
                    'newStatus' => $newStatus
                ]);
            } else {
                return $this->response->setJSON(['success' => false, 'message' => 'Gagal mengupdate status']);
            }
        } catch (\Exception $e) {
            return $this->response->setJSON(['success' => false, 'message' => $e->getMessage()]);
        }
    }

}
