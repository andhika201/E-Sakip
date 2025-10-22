<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\RktModel;
use App\Models\ProgramPkModel;
use App\Models\OpdModel;
use App\Models\Opd\LakipOpdModel;

class RkpdController extends BaseController
{
    protected $rktModel;
    protected $programModel;
    protected $opdModel;
    protected $db;
    protected $lakipModel;



    public function __construct()
    {
        $this->rktModel = new RktModel();
        $this->programModel = new ProgramPkModel();
        $this->opdModel = new OpdModel();
        $this->lakipModel = new LakipOpdModel();
        $this->db = \Config\Database::connect();

    }

    /**
     * Index: show RKPD list. If opd_id provided via GET => filter per OPD.
     * We'll also accept tahun via GET (default current year).
     */
    public function index()
    {
        $session = session();
        $curropd = $session->get('opd_id');


        $opdId = $this->request->getGet('opd_id') ?? 'all';

        // dd($opdId);
        $tahun = $this->request->getGet('tahun') ?? 'all';

        if ($opdId !== 'all') {
            $currentOpd = $this->rktModel->getOpdById((int) $opdId);
        }
        $currentOpd = $this->opdModel->getAllOpd();

        dd($currentOpd[8]['nama_opd']);
        $allOPd = $this->opdModel->getAllOpd();

        $indicators = $this->rktModel->getIndicatorsWithRkt($curropd, $tahun);
        $availableYears = $this->lakipModel->getAvailableYears();
        // Ambil data RKPD berdasarkan filter
        $rkpdData = $this->rktModel->getIndicatorsForRkpd($opdId, $tahun);

        // dd($indicators);
        // kirim ke view
        return view('adminkabupaten/rkpd/rkpd', [
            'currentOpd' => $currentOpd,
            'allOpd' => $allOPd,
            'rktdata' => $indicators,
            'rkpdData' => $rkpdData,
            'available_years' => $availableYears,
            'tahun' => $tahun,
            'opdId' => $opdId,
        ]);
    }

    public function tambah()
    {
        $db = \Config\Database::connect();

        // load programs and indikator list
        $programs = $this->programModel->findAll();

        // get all indikator (for all OPD) — you may refine to only indikator for selected OPD later
        $indikators = $db->table('renstra_indikator_sasaran i')
            ->select('i.*, s.sasaran, s.opd_id')
            ->join('renstra_sasaran s', 's.id = i.renstra_sasaran_id', 'left')
            ->orderBy('s.id', 'ASC')
            ->get()
            ->getResultArray();

        // OPD list so admin_kab can pick target OPD when creating RKPD entries
        $opds = $this->opdModel->findAll();

        return view('adminKab/rkpd/tambah', [
            'title' => 'Tambah RKPD',
            'program' => $programs,
            'indikators' => $indikators,
            'opds' => $opds,
            'role' => session()->get('role'),
        ]);
    }

    /**
     * Store RKPD (multiple program->kegiatan->sub) for selected opd and indikator.
     * We reuse RktModel->saveRkt expects data array with opd_id, indikator_id, program[] structure.
     */
    public function store()
    {
        $request = service('request');

        $post = $request->getPost();

        // basic required fields
        $opdId = $post['opd_id'] ?? null;
        $indikatorId = $post['indikator_id'] ?? null;
        $tahun = $post['tahun'] ?? date('Y');

        if (!$opdId || !$indikatorId) {
            session()->setFlashdata('error', 'OPD dan Indikator harus dipilih.');
            return redirect()->back()->withInput();
        }

        // Prepare payload for RktModel::saveRkt
        $payload = [
            'opd_id' => (int) $opdId,
            'indikator_id' => (int) $indikatorId,
            'tahun' => $tahun, // note: saveRkt currently doesn't include tahun — we'll include it in each insert below
            'program' => $post['program'] ?? []
        ];

        // Since existing saveRkt in your model doesn't accept tahun in insert,
        // we will add tahun into each inserted rkt inside the model; but to be safe,
        // we can temporarily extend save here: loop through programs and insert.
        $db = \Config\Database::connect();
        $db->transStart();

        $rktTable = $db->table('rkt');
        $kegiatanTable = $db->table('rkt_kegiatan');
        $subTable = $db->table('rkt_subkegiatan');

        if (!empty($payload['program'])) {
            foreach ($payload['program'] as $prog) {
                // insert rkt
                $rktData = [
                    'opd_id' => $payload['opd_id'],
                    'tahun' => $payload['tahun'],
                    'indikator_id' => $payload['indikator_id'],
                    'program_id' => $prog['program_id'],
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ];
                $rktTable->insert($rktData);
                $rktId = $db->insertID();

                // kegiatan
                if (!empty($prog['kegiatan'])) {
                    foreach ($prog['kegiatan'] as $keg) {
                        $kegiatanTable->insert([
                            'rkt_id' => $rktId,
                            'program_id' => $prog['program_id'],
                            'nama_kegiatan' => $keg['nama_kegiatan'],
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s'),
                        ]);
                        $kegiatanId = $db->insertID();

                        if (!empty($keg['subkegiatan'])) {
                            foreach ($keg['subkegiatan'] as $sub) {
                                $subTable->insert([
                                    'kegiatan_id' => $kegiatanId,
                                    'nama_subkegiatan' => $sub['nama_subkegiatan'],
                                    'target_anggaran' => $sub['target_anggaran'] ?? 0,
                                    'created_at' => date('Y-m-d H:i:s'),
                                    'updated_at' => date('Y-m-d H:i:s'),
                                ]);
                            }
                        }
                    }
                }
            }
        }

        $db->transComplete();

        if ($db->transStatus()) {
            session()->setFlashdata('success', 'Data RKPD berhasil disimpan.');
            return redirect()->to(base_url('adminkab/rkpd'));
        } else {
            session()->setFlashdata('error', 'Gagal menyimpan data RKPD.');
            return redirect()->back()->withInput();
        }
    }

    /**
     * Delete a single rkt record (and cascade kegiatan/sub via DB foreign keys if present),
     * or we do manual deletes for safety.
     */
    public function delete($rktId = null)
    {
        if (!$rktId) {
            session()->setFlashdata('error', 'ID RKT tidak valid.');
            return redirect()->back();
        }
        $db = \Config\Database::connect();
        $db->transStart();

        // delete subkegiatan -> kegiatan -> rkt
        $keg = $db->table('rkt_kegiatan')->where('rkt_id', $rktId)->get()->getResultArray();
        foreach ($keg as $k) {
            $db->table('rkt_subkegiatan')->where('kegiatan_id', $k['id'])->delete();
        }
        $db->table('rkt_kegiatan')->where('rkt_id', $rktId)->delete();
        $db->table('rkt')->where('id', $rktId)->delete();

        $db->transComplete();

        if ($db->transStatus()) {
            session()->setFlashdata('success', 'RKT berhasil dihapus.');
        } else {
            session()->setFlashdata('error', 'Gagal menghapus RKT.');
        }
        return redirect()->to(base_url('adminkab/rkpd'));
    }


    /**
     * (Optional) edit() / update() stubs: if you want full edit/hard-sync like in OPD,
     * I can implement update() that handles deleted_* arrays and inserts/updates accordingly.
     */
    public function edit($id = null)
    {
        // Simple redirect for now — implement if you want full edit experience.
        return redirect()->to(base_url('adminkab/rkpd'));
    }

    public function update($id = null)
    {
        // TODO: implement hard-sync multi-program update (delete/insert/update)
        return redirect()->to(base_url('adminkab/rkpd'));
    }
}
