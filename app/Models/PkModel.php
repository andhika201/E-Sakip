<?php

namespace App\Models;

use CodeIgniter\Model;

class PkModel extends Model
{
    /**
     * Get all program data for a given PK ID
     */
    public function getProgramByPkId($pkId)
    {
        return $this->db->table('pk_program pp')
            ->select('pp.id as pk_program_id, pr.program_kegiatan, pr.anggaran')
            ->join('program_pk pr', 'pr.id = pp.program_id')
            ->where('pp.pk_id', $pkId)
            ->get()
            ->getResultArray();
    }
    /**
     * Get all sasaran and their indikator for a given PK ID
     */
    public function getSasaranByPkId($pkId)
    {
        $sasaranList = $this->db->table('pk_sasaran')
            ->where('pk_id', $pkId)
            ->get()
            ->getResultArray();

        foreach ($sasaranList as &$sasaran) {
            $indikatorList = $this->db->table('pk_indikator')
                ->select('pk_indikator.*, satuan.satuan as satuan_nama')
                ->join('satuan', 'satuan.id = pk_indikator.id_satuan', 'left')
                ->where('pk_sasaran_id', $sasaran['id'])
                ->get()
                ->getResultArray();
            // Normalisasi key agar view tetap pakai $indikator['satuan']
            foreach ($indikatorList as &$indikator) {
                $indikator['satuan'] = $indikator['satuan_nama'] ?? '-';
            }
            $sasaran['indikator'] = $indikatorList;
        }
        return $sasaranList;
    }
    /**
     * Get indikator acuan (referensi) untuk PK tertentu
     */
    public function getIndikatorAcuanByPkId($pkId)
    {
        $result = $this->db->table('pk_referensi pr')
            ->select('pr.*, pi.indikator as nama_indikator')
            ->join('pk_indikator pi', 'pi.id = pr.referensi_indikator_id', 'left')
            ->where('pr.pk_id', $pkId)
            ->get()
            ->getResultArray();
        return $result;
    }
    /**
     * Update PK and related data (sasaran, indikator, program)
     */
    public function updateCompletePk($id, $data)
    {
        $db = \Config\Database::connect();
        $db->transStart();
        try {
            // Update main PK table
            $db->table('pk')->where('id', $id)->update([
                'opd_id' => $data['opd_id'],
                'jenis' => $data['jenis'],
                'pihak_1' => $data['pihak_1'],
                'pihak_2' => $data['pihak_2'],
                'tanggal' => $data['tanggal'],
            ]);

            // Delete old sasaran, indikator, and program
            $sasaranIds = $db->table('pk_sasaran')->select('id')->where('pk_id', $id)->get()->getResultArray();
            $sasaranIdArr = array_column($sasaranIds, 'id');
            if (!empty($sasaranIdArr)) {
                $db->table('pk_indikator')->whereIn('pk_sasaran_id', $sasaranIdArr)->delete();
            }
            $db->table('pk_sasaran')->where('pk_id', $id)->delete();
            $db->table('pk_program')->where('pk_id', $id)->delete();

            // Insert new sasaran and indikator
            if (isset($data['sasaran_pk']) && is_array($data['sasaran_pk'])) {
                foreach ($data['sasaran_pk'] as $sasaran) {
                    $db->table('pk_sasaran')->insert([
                        'pk_id' => $id,
                        'sasaran' => $sasaran['sasaran']
                    ]);
                    $sasaranId = $db->insertID();
                    if (isset($sasaran['indikator']) && is_array($sasaran['indikator'])) {
                        foreach ($sasaran['indikator'] as $indikator) {
                            $db->table('pk_indikator')->insert([
                                'pk_sasaran_id' => $sasaranId,
                                'indikator' => $indikator['indikator'],
                                'target' => $indikator['target'],
                                'id_satuan' => $indikator['id_satuan'] ?? null,
                                'jenis_indikator' => $indikator['jenis_indikator'] ?? null
                            ]);
                        }
                    }
                }
            }

            // Insert new program
            if (isset($data['program']) && is_array($data['program'])) {
                foreach ($data['program'] as $program) {
                    $db->table('pk_program')->insert([
                        'pk_id' => $id,
                        'program_id' => $program['program_id']
                    ]);
                }
            }

            $db->transComplete();
            return $db->transStatus();
        } catch (\Exception $e) {
            $db->transRollback();
            return false;
        }
    }
    protected $table = 'pk';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'opd_id',
        'jenis',
        'pihak_1',
        'pihak_2',
        'tanggal'
    ];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected array $casts = [];
    protected array $castHandlers = [];

    // Dates - Aktifkan auto timestamps
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = 'deleted_at';

    // Validation
    protected $validationRules = [
        'jenis' => 'required|in_list[jpt,administrator,pengawas,bupati]',
        'pihak_1' => 'permit_empty|integer',
        'pihak_2' => 'permit_empty|integer',
        'tanggal' => 'required|valid_date'
    ];

    protected $validationMessages = [
        'jenis' => [
            'required' => 'Jenis PK harus dipilih',
            'in_list' => 'Jenis PK tidak valid'
        ],
        'pihak_1' => [
            'required' => 'Pihak 1 harus dipilih',
            'integer' => 'Pihak 1 harus berupa angka'
        ],
        'pihak_2' => [
            'required' => 'Pihak 2 harus dipilih',
            'integer' => 'Pihak 2 harus berupa angka'
        ],
        'tanggal' => [
            'required' => 'Tanggal harus diisi',
            'valid_date' => 'Format tanggal tidak valid'
        ]
    ];

    protected $skipValidation = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert = [];
    protected $afterInsert = [];
    protected $beforeUpdate = [];
    protected $afterUpdate = [];
    protected $beforeFind = [];
    protected $afterFind = [];
    protected $beforeDelete = [];
    protected $afterDelete = [];

    /**
     * Get PK with pegawai relationships
     */
    public function getPkWithPegawai($id = null)
    {
        $builder = $this->db->table($this->table . ' pk')
            ->select('pk.*, p1.nama as nama_pihak_1, p2.nama as nama_pihak_2')
            ->join('pegawai p1', 'p1.id = pk.pihak_1', 'left')
            ->join('pegawai p2', 'p2.id = pk.pihak_2', 'left');

        if ($id !== null) {
            return $builder->where('pk.id', $id)->get()->getRowArray();
        }

        return $builder->get()->getResultArray();
    }

    public function getAllPkData($opdId = null)
    {
        // Ambil data PK utama + data pihak
        $query = $this->db->table('pk p')
            ->select('
            p.id as pk_id,
            p.jenis,
            p.tanggal,
            o.nama_opd,

            peg1.id as pihak_1_id,
            peg1.nama_pegawai as pihak_1_nama,
            peg1.nip_pegawai  as pihak_1_nip,
            j1.nama_jabatan as pihak_1_jabatan,
            pang1.nama_pangkat as pihak_1_pangkat,
            pang1.golongan as pihak_1_golongan,

            peg2.id as pihak_2_id,
            peg2.nama_pegawai  as pihak_2_nama,
            peg2.nip_pegawai  as pihak_2_nip,
            j2.nama_jabatan as pihak_2_jabatan,
            pang2.nama_pangkat as pihak_2_pangkat,
            pang2.golongan as pihak_2_golongan
        ')
            ->join('opd o', 'o.id = p.opd_id')
            ->join('pegawai peg1', 'peg1.id = p.pihak_1', 'left')
            ->join('jabatan j1', 'j1.id = peg1.jabatan_id', 'left')
            ->join('pangkat pang1', 'pang1.id = peg1.pangkat_id', 'left')
            ->join('pegawai peg2', 'peg2.id = p.pihak_2', 'left')
            ->join('jabatan j2', 'j2.id = peg2.jabatan_id', 'left')
            ->join('pangkat pang2', 'pang2.id = peg2.pangkat_id', 'left');

        if ($opdId !== null) {
            $query->where('p.opd_id', $opdId);
        }

        $result = $query
            ->orderBy('p.id', 'ASC')
            ->get()
            ->getResultArray();

        // Loop setiap PK untuk ambil sasaran, indikator, dan program
        foreach ($result as &$pk) {
            $pkId = $pk['pk_id'];

            // Ambil sasaran
            $sasaranList = $this->db->table('pk_sasaran')
                ->select('id, pk_id, sasaran')
                ->where('pk_id', $pkId)
                ->orderBy('id', 'ASC')
                ->get()
                ->getResultArray();

            // Untuk setiap sasaran, ambil indikator
            foreach ($sasaranList as &$sasaran) {
                $indikatorList = $this->db->table('pk_indikator')
                    ->select('id, pk_sasaran_id, indikator, target')
                    ->where('pk_sasaran_id', $sasaran['id'])
                    ->orderBy('id', 'ASC')
                    ->get()
                    ->getResultArray();

                $sasaran['indikator'] = $indikatorList;
            }

            $pk['sasaran'] = $sasaranList;

            // Ambil program PK untuk PK ini
            $programList = $this->db->table('pk_program pp')
                ->select('pp.id, pp.pk_id, pr.program_kegiatan, pr.anggaran')
                ->join('program_pk pr', 'pr.id = pp.program_id', 'left')
                ->where('pp.pk_id', $pkId)
                ->orderBy('pp.id', 'ASC')
                ->get()
                ->getResultArray();

            $pk['program'] = $programList;
        }

        return $result;
    }

    public function getCompletePkByOpdIdAndJenis($opdId, $jenis)
    {
        $builder = $this->db->table('pk');
        $builder->select('pk.*, opd.nama_opd');
        $builder->join('opd', 'opd.id = pk.opd_id');
        $builder->where('pk.opd_id', $opdId);
        $builder->where('pk.jenis', $jenis); // Filter by jenis
        $query = $builder->get();

        $results = $query->getResultArray();

        // Jika tidak ada hasil, return array kosong
        if (empty($results)) {
            return [];
        }

        // Dapatkan sasaran dan program untuk setiap PK
        foreach ($results as &$pk) {
            $pk['sasaran'] = $this->getSasaranByPkId($pk['id']);
            $pk['program'] = $this->getProgramByPkId($pk['id']);
        }

        return $results;
    }

    public function getCompletePkByOpdId($opdId)
    {
        $query = $this->db->table('pk p')
            ->select('
                p.id as pk_id,
                p.opd_id,
                p.jenis,
                p.tanggal,
                o.nama_opd,

                peg1.id as pihak_1_id,
                peg1.nama_pegawai as pihak_1_nama,
                peg1.nip_pegawai as pihak_1_nip,
                j1.nama_jabatan as pihak_1_jabatan,
                pang1.nama_pangkat as pihak_1_pangkat,
                pang1.golongan as pihak_1_golongan,

                peg2.id as pihak_2_id,
                peg2.nama_pegawai as pihak_2_nama,
                peg2.nip_pegawai as pihak_2_nip,
                j2.nama_jabatan as pihak_2_jabatan,
                pang2.nama_pangkat as pihak_2_pangkat,
                pang2.golongan as pihak_2_golongan,

                ps.id as sasaran_id,
                ps.sasaran,

                pi.id as indikator_id,
                pi.indikator,
                pi.target,

                pp.id as pk_program_id,
                pr.program_kegiatan,
                pr.anggaran
            ')
            ->join('opd o', 'o.id = p.opd_id')
            ->join('pegawai peg1', 'peg1.id = p.pihak_1')
            ->join('jabatan j1', 'j1.id = peg1.jabatan_id', 'left')
            ->join('pangkat pang1', 'pang1.id = peg1.pangkat_id', 'left')
            ->join('pegawai peg2', 'peg2.id = p.pihak_2')
            ->join('jabatan j2', 'j2.id = peg2.jabatan_id', 'left')
            ->join('pangkat pang2', 'pang2.id = peg2.pangkat_id', 'left')
            ->join('pk_sasaran ps', 'ps.pk_id = p.id', 'left')
            ->join('pk_indikator pi', 'pi.pk_sasaran_id = ps.id', 'left')
            ->join('pk_program pp', 'pp.pk_id = p.id', 'left')
            ->join('program_pk pr', 'pr.id = pp.program_id', 'left')
            ->where('p.opd_id', $opdId)
            ->orderBy('ps.id')
            ->orderBy('pi.id')
            ->orderBy('pp.id')
            ->get()
            ->getResultArray();

        if (!$query) {
            return null;
        }

        $first = $query[0];
        $result = [
            'pk_id' => $first['pk_id'],
            'opd_id' => $first['opd_id'],
            'nama_opd' => $first['nama_opd'],
            'jenis' => $first['jenis'],
            'tanggal' => $first['tanggal'],
            'pihak_1' => [
                'id' => $first['pihak_1_id'],
                'nama' => $first['pihak_1_nama'],
                'nip' => $first['pihak_1_nip'],
                'jabatan' => $first['pihak_1_jabatan'],
                'pangkat' => $first['pihak_1_pangkat'],
                'golongan' => $first['pihak_1_golongan'],
            ],
            'pihak_2' => [
                'id' => $first['pihak_2_id'],
                'nama' => $first['pihak_2_nama'],
                'nip' => $first['pihak_2_nip'],
                'jabatan' => $first['pihak_2_jabatan'],
                'pangkat' => $first['pihak_2_pangkat'],
                'golongan' => $first['pihak_2_golongan'],
            ],
            'sasaran' => [],
            'program' => [],
        ];

        foreach ($query as $row) {
            // Sasaran & Indikator
            if ($row['sasaran_id']) {
                $sid = $row['sasaran_id'];
                if (!isset($result['sasaran'][$sid])) {
                    $result['sasaran'][$sid] = [
                        'sasaran_id' => $sid,
                        'sasaran' => $row['sasaran'],
                        'indikator' => []
                    ];
                }

                if ($row['indikator_id']) {
                    // Ambil semua indikator_id yang sudah dimasukkan untuk sasaran ini
                    $existingIndikatorIds = array_column($result['sasaran'][$sid]['indikator'], 'indikator_id');

                    // Cek apakah indikator_id ini belum ada
                    if (!in_array($row['indikator_id'], $existingIndikatorIds)) {
                        $result['sasaran'][$sid]['indikator'][] = [
                            'indikator_id' => $row['indikator_id'],
                            'indikator' => $row['indikator'],
                            'target' => $row['target']
                        ];
                    }
                }

            }

            // Program
            if ($row['pk_program_id']) {
                $existingProgramIds = array_column($result['program'], 'pk_program_id');
                if (!in_array($row['pk_program_id'], $existingProgramIds)) {
                    $result['program'][] = [
                        'pk_program_id' => $row['pk_program_id'],
                        'program_kegiatan' => $row['program_kegiatan'],
                        'anggaran' => $row['anggaran']
                    ];
                }
            }
        }

        // Format ulang agar array numerik
        $result['sasaran'] = array_values($result['sasaran']);

        return $result;
    }

    public function getPkById($id)
    {
        $builder = $this->db->table('pk p')
            ->select('
                p.id as pk_id,
                p.jenis,
                p.tanggal,
                o.nama_opd,
                o.singkatan,

                peg1.nama_pegawai as nama_pihak_1,
                peg1.nip_pegawai as nip_pihak_1,
                jab1.nama_jabatan as jabatan_pihak_1,
                pang1.nama_pangkat as pangkat_pihak_1,
                pang1.golongan as golongan_pihak_1,

                peg2.nama_pegawai as nama_pihak_2,
                peg2.nip_pegawai as nip_pihak_2,
                jab2.nama_jabatan as jabatan_pihak_2,
                pang2.nama_pangkat as pangkat_pihak_2,
                pang2.golongan as golongan_pihak_2
            ')
            ->join('opd o', 'o.id = p.opd_id')
            ->join('pegawai peg1', 'peg1.id = p.pihak_1', 'left')
            ->join('jabatan jab1', 'jab1.id = peg1.jabatan_id', 'left')
            ->join('pangkat pang1', 'pang1.id = peg1.pangkat_id', 'left')

            ->join('pegawai peg2', 'peg2.id = p.pihak_2', 'left')
            ->join('jabatan jab2', 'jab2.id = peg2.jabatan_id', 'left')
            ->join('pangkat pang2', 'pang2.id = peg2.pangkat_id', 'left')
            ->where('p.id', $id)
            ->get();

        $pk = $builder->getRowArray();

        if (!$pk)
            return null;

        // Ambil sasaran & indikator
        $pk['sasaran_pk'] = $this->db->table('pk_sasaran')
            ->where('pk_id', $id)
            ->get()
            ->getResultArray();

        foreach ($pk['sasaran_pk'] as &$s) {
            $indikatorList = $this->db->table('pk_indikator')
                ->select('pk_indikator.*, satuan.satuan as satuan_nama')
                ->join('satuan', 'satuan.id = pk_indikator.id_satuan', 'left')
                ->where('pk_sasaran_id', $s['id'])
                ->get()
                ->getResultArray();
            // Normalisasi agar view bisa pakai $indikator['satuan']
            foreach ($indikatorList as &$indikator) {
                $indikator['satuan'] = $indikator['satuan_nama'] ?? '-';
            }
            $s['indikator'] = $indikatorList;
        }

        // Ambil program
        $pk['program_pk'] = $this->db->table('pk_program pp')
            ->select('pr.program_kegiatan, pr.anggaran')
            ->join('program_pk pr', 'pr.id = pp.program_id')
            ->where('pp.pk_id', $id)
            ->get()
            ->getResultArray();

        return $pk;
    }


    public function saveCompletePk($data)
    {
        // Prepare data for saving
        $db = \Config\Database::connect();
        $db->transStart();

        try {

            // Simpan ke tabel pk
            $pkData = [
                'opd_id' => $data['opd_id'],
                'jenis' => $data['jenis'],
                'pihak_1' => $data['pihak_1'],
                'pihak_2' => $data['pihak_2'],
                'tanggal' => $data['tanggal'],
            ];

            $this->db->table('pk')->insert($pkData);
            $pkId = $this->db->insertID();

            // Simpan referensi indikator acuan jika ada
            if (!empty($data['referensi_acuan']) && is_array($data['referensi_acuan'])) {
                foreach ($data['referensi_acuan'] as $ref) {
                    $this->db->table('pk_referensi')->insert([
                        'pk_id' => $pkId,
                        'referensi_pk_id' => $ref['referensi_pk_id'],
                        'referensi_indikator_id' => $ref['referensi_indikator_id']
                    ]);
                }
            }

            // Simpan ke pk_sasaran dan pk_indikator (langsung dengan query builder)
            foreach ($data['sasaran_pk'] as $sasaran) {
                $db->table('pk_sasaran')->insert([
                    'pk_id' => $pkId,
                    'sasaran' => $sasaran['sasaran']
                ]);

                $pkSasaranId = $db->insertID();

                if (!empty($sasaran['indikator'])) {
                    foreach ($sasaran['indikator'] as $indikator) {
                        $db->table('pk_indikator')->insert([
                            'pk_sasaran_id' => $pkSasaranId,
                            'indikator' => $indikator['indikator'],
                            'target' => $indikator['target'],
                            'id_satuan' => $indikator['id_satuan'] ?? null,
                            'jenis_indikator' => $indikator['jenis_indikator'] ?? null
                        ]);
                    }
                }
            }

            // Proses Program dan Anggaran
            if (isset($data['program']) && is_array($data['program'])) {
                foreach ($data['program'] as $program) {
                    $programId = $program['program_id'];

                    $db->table('pk_program')->insert([
                        'pk_id' => $pkId,
                        'program_id' => $programId
                    ]);
                }
            }

            $db->transComplete();

            return $db->transStatus() ? $pkId : false;

        } catch (\Exception $e) {
            $this->db->transRollback();
            dd($e->getMessage());
            throw $e;
        }
    }

    /**
     * Get all satuan
     */
    public function getAllSatuan()
    {
        return $this->db->table('satuan')->get()->getResultArray();
    }

    /**
     * Get all programs
     */
    public function getAllPrograms()
    {
        return $this->db->table('program_pk')->orderBy('created_at', 'DESC')->get()->getResultArray();
    }

    /**
     * Get program by ID
     */
    public function getProgramById($id)
    {
        return $this->db->table('program_pk')->where('id', $id)->get()->getRowArray();
    }

    /**
     * Format anggaran as currency
     */
    public function formatAnggaran($anggaran)
    {
        return 'Rp ' . number_format($anggaran, 2, ',', '.');
    }

    /**
     * Search programs
     */
    public function searchPrograms($keyword)
    {
        return $this->db->table('program_pk')
            ->like('program_kegiatan', $keyword)
            ->orderBy('created_at', 'DESC')
            ->get()->getResultArray();
    }

    /**
     * Get PK Pimpinan (referensi PK) by OPD and jenis
     */
    public function getPkPimpinan($opdId, $referensiJenis)
    {
        return $this->where('opd_id', $opdId)
                    ->where('jenis', $referensiJenis)
                    ->findAll();
    }

    /**
     * Get jenis_indikator for PK Bupati by PK ID
     */
    public function getJenisIndikatorByPkId($pkId)
    {
        $rows = $this->db->table('pk_indikator')
            ->select('id, jenis_indikator')
            ->where('pk_id', $pkId)
            ->get()->getResultArray();
        $result = [];
        foreach ($rows as $row) {
            $result[$row['id']] = $row['jenis_indikator'];
        }
        return $result;
    }

    /**
     * Update capaian indikator
     */
    public function updateCapaianIndikator($capaianArr)
    {
        $db = \Config\Database::connect();
        foreach ($capaianArr as $indikatorId => $nilaiCapaian) {
            $db->table('pk_indikator')->where('id', $indikatorId)->update([
                'capaian' => $nilaiCapaian
            ]);
        }
    }

}
