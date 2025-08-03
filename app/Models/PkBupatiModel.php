<?php

namespace App\Models;

use CodeIgniter\Model;

class PkBupatiModel extends Model
{
    protected $table            = 'pk_bupati';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
       'nama',
       'tanggal',
       'rpjmd_misi_id'
    ];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected array $casts = [];
    protected array $castHandlers = [];

    // Dates - Aktifkan auto timestamps
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    // Validation
    protected $validationRules = [
        'rpjmd_misi_id' => 'required|integer',
        'nama' => 'required|min_length[3]|max_length[255]',
        'tanggal' => 'required|valid_date'
    ];
    
    protected $validationMessages = [
        'rpjmd_misi_id' => [
            'required' => 'Misi RPJMD harus dipilih',
            'integer' => 'ID Misi RPJMD harus berupa angka'
        ],
        'nama' => [
            'required' => 'Nama PK harus diisi',
            'min_length' => 'Nama PK minimal 3 karakter',
            'max_length' => 'Nama PK maksimal 255 karakter'
        ],
        'tanggal' => [
            'required' => 'Tanggal harus diisi',
            'valid_date' => 'Format tanggal tidak valid'
        ]
    ];
    
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert   = [];
    protected $afterInsert    = [];
    protected $beforeUpdate   = [];
    protected $afterUpdate    = [];
    protected $beforeFind     = [];
    protected $afterFind      = [];
    protected $beforeDelete   = [];
    protected $afterDelete    = [];


    public function getCompletePk()
    {
        $query = $this->db->table('pk_bupati pb')
        ->select('
            pb.id as pk_id,
            pb.nama,
            pb.tanggal,
            pb.rpjmd_misi_id,
            rm.misi as misi_rpjmd,

            ps.id as sasaran_id,
            ps.sasaran,
            pi.id as indikator_id,
            pi.indikator,
            pi.target,

            pp.id as pk_program_id,
            pr.program_kegiatan,
            pr.anggaran
        ')

        ->join('rpjmd_misi rm', 'rm.id = pb.rpjmd_misi_id', 'left')
        ->join('pk_bupati_sasaran ps', 'ps.pk_bup_id = pb.id', 'left')
        ->join('pk_bupati_indikator pi', 'pi.pk_bup_sasaran_id = ps.id', 'left')
        ->join('pk_bupati_program pp', 'pp.pk_bup_id = pb.id', 'left')
        ->join('program_pk pr', 'pr.id = pp.program_id', 'left')
        ->orderBy('pb.id', 'ASC')
        ->orderBy('ps.id', 'ASC')
        ->orderBy('pi.id', 'ASC')
        ->orderBy('pp.id', 'ASC')
        ->get()
        ->getResultArray();

        // Group data berdasarkan PK dengan struktur hierarkis
        $result = [];
        foreach ($query as $row) {
            $pkId = $row['pk_id'];
            
            if (!isset($result[$pkId])) {
                $result[$pkId] = [
                    'pk_id' => $pkId,
                    'nama' => $row['nama'],
                    'tanggal' => $row['tanggal'],
                    'rpjmd_misi_id' => $row['rpjmd_misi_id'],
                    'misi_rpjmd' => $row['misi_rpjmd'],
                    'sasaran' => [],
                    'program' => []
                ];
            }
            
            // Group sasaran dan indikator
            if ($row['sasaran_id']) {
                $sasaranId = $row['sasaran_id'];
                if (!isset($result[$pkId]['sasaran'][$sasaranId])) {
                    $result[$pkId]['sasaran'][$sasaranId] = [
                        'sasaran_id' => $sasaranId,
                        'sasaran' => $row['sasaran'],
                        'indikator' => []
                    ];
                }
                
                if ($row['indikator_id']) {
                    $indikatorId = $row['indikator_id'];
                    if (!isset($result[$pkId]['sasaran'][$sasaranId]['indikator'][$indikatorId])) {
                        $result[$pkId]['sasaran'][$sasaranId]['indikator'][$indikatorId] = [
                            'indikator_id' => $row['indikator_id'],
                            'indikator' => $row['indikator'],
                            'target' => $row['target']
                        ];
                    }
                }
            }
            
            // Group program
            if ($row['pk_program_id']) {
                $programId = $row['pk_program_id'];
                if (!isset($result[$pkId]['program'][$programId])) {
                    $result[$pkId]['program'][$programId] = [
                        'pk_program_id' => $row['pk_program_id'],
                        'program_kegiatan' => $row['program_kegiatan'],
                        'anggaran' => $row['anggaran']
                    ];
                }
            }
        }
        
        // Convert ke array numerik dan format data
        foreach ($result as &$pk) {
            $pk['sasaran'] = array_values($pk['sasaran']);
            foreach ($pk['sasaran'] as &$sasaran) {
                $sasaran['indikator'] = array_values($sasaran['indikator']);
            }
            $pk['program'] = array_values($pk['program']);
        }
        
        return array_values($result);
    }

    public function getCompletePkByYear($tahun)
    {
        $query = $this->db->table('pk_bupati pb')
        ->select('
            pb.id as pk_id,
            pb.nama,
            pb.tanggal,
            pb.rpjmd_misi_id,
            rm.misi as misi_rpjmd,

            ps.id as sasaran_id,
            ps.sasaran,
            pi.id as indikator_id,
            pi.indikator,
            pi.target,

            pp.id as pk_program_id,
            pr.program_kegiatan,
            pr.anggaran
        ')

        ->join('rpjmd_misi rm', 'rm.id = pb.rpjmd_misi_id', 'left')
        ->join('pk_bupati_sasaran ps', 'ps.pk_bup_id = pb.id', 'left')
        ->join('pk_bupati_indikator pi', 'pi.pk_bup_sasaran_id = ps.id', 'left')
        ->join('pk_bupati_program pp', 'pp.pk_bup_id = pb.id', 'left')
        ->join('program_pk pr', 'pr.id = pp.program_id', 'left')
        ->where('YEAR(pb.tanggal)', $tahun)
        ->orderBy('pb.id', 'ASC')
        ->orderBy('ps.id', 'ASC')
        ->orderBy('pi.id', 'ASC')
        ->orderBy('pp.id', 'ASC')
        ->get()
        ->getResultArray();

        // Group data berdasarkan PK dengan struktur hierarkis
        $result = [];
        foreach ($query as $row) {
            $pkId = $row['pk_id'];
            
            if (!isset($result[$pkId])) {
                $result[$pkId] = [
                    'pk_id' => $pkId,
                    'nama' => $row['nama'],
                    'tanggal' => $row['tanggal'],
                    'rpjmd_misi_id' => $row['rpjmd_misi_id'],
                    'misi_rpjmd' => $row['misi_rpjmd'],
                    'sasaran' => [],
                    'program' => []
                ];
            }
            
            // Group sasaran dan indikator
            if ($row['sasaran_id']) {
                $sasaranId = $row['sasaran_id'];
                if (!isset($result[$pkId]['sasaran'][$sasaranId])) {
                    $result[$pkId]['sasaran'][$sasaranId] = [
                        'sasaran_id' => $sasaranId,
                        'sasaran' => $row['sasaran'],
                        'indikator' => []
                    ];
                }
                
                if ($row['indikator_id']) {
                    $indikatorId = $row['indikator_id'];
                    if (!isset($result[$pkId]['sasaran'][$sasaranId]['indikator'][$indikatorId])) {
                        $result[$pkId]['sasaran'][$sasaranId]['indikator'][$indikatorId] = [
                            'indikator_id' => $row['indikator_id'],
                            'indikator' => $row['indikator'],
                            'target' => $row['target']
                        ];
                    }
                }
            }
            
            // Group program
            if ($row['pk_program_id']) {
                $programId = $row['pk_program_id'];
                if (!isset($result[$pkId]['program'][$programId])) {
                    $result[$pkId]['program'][$programId] = [
                        'pk_program_id' => $row['pk_program_id'],
                        'program_kegiatan' => $row['program_kegiatan'],
                        'anggaran' => $row['anggaran']
                    ];
                }
            }
        }
        
        // Convert ke array numerik dan format data
        foreach ($result as &$pk) {
            $pk['sasaran'] = array_values($pk['sasaran']);
            foreach ($pk['sasaran'] as &$sasaran) {
                $sasaran['indikator'] = array_values($sasaran['indikator']);
            }
            $pk['program'] = array_values($pk['program']);
        }
        
        return array_values($result);
    }

    public function getAvailableYears()
    {
        $query = $this->db->table('pk_bupati')
            ->select('YEAR(tanggal) as tahun')
            ->distinct()
            ->where('tanggal IS NOT NULL')
            ->orderBy('tahun', 'DESC')
            ->get()
            ->getResultArray();
        
        $years = [];
        foreach ($query as $row) {
            if (!empty($row['tahun'])) {
                $years[] = $row['tahun'];
            }
        }
        
        return $years;
    }


    public function getCompletePkByOpdId($opdId)
    {
        // Method ini tidak sesuai dengan ERD PK Bupati yang tidak memiliki opd_id
        // Mengembalikan data PK Bupati tanpa filter OPD
        return $this->getCompletePk();
    }

    public function getPkById($id)
    {
        $builder = $this->db->table('pk_bupati pb')
            ->select('
                pb.id as pk_id,
                pb.nama,
                pb.tanggal,
                pb.rpjmd_misi_id
            ')
            ->where('pb.id', $id)
            ->get();

        $pk = $builder->getRowArray();

        if (!$pk) return null;

        // Ambil sasaran & indikator
        $pk['sasaran_pk'] = $this->db->table('pk_bupati_sasaran')
            ->where('pk_bup_id', $id)
            ->get()
            ->getResultArray();

        foreach ($pk['sasaran_pk'] as &$s) {
            $s['indikator'] = $this->db->table('pk_bupati_indikator')
                ->where('pk_bup_sasaran_id', $s['id'])
                ->get()
                ->getResultArray();
        }

        // Ambil program
        $pk['program_pk'] = $this->db->table('pk_bupati_program pp')
            ->select('pp.program_id, pr.program_kegiatan, pr.anggaran')
            ->join('program_pk pr', 'pr.id = pp.program_id')
            ->where('pp.pk_bup_id', $id)
            ->get()
            ->getResultArray();

        return $pk;
    }


    public function saveCompletePk($data)
    {
        $db = \Config\Database::connect();
        $db->transStart();

        try {
            // Simpan ke tabel pk_bupati
            $pkData = [
                'nama'    => $data['nama'],
                'tanggal' => $data['tanggal'],
                'rpjmd_misi_id' => $data['rpjmd_misi_id'] ?? null,
            ];

            $this->insert($pkData);
            $pkId = $this->getInsertID();
            
            // Simpan ke pk_bupati_sasaran dan pk_bupati_indikator
            foreach ($data['sasaran_pk'] as $sasaran) {
                $db->table('pk_bupati_sasaran')->insert([
                    'pk_bup_id' => $pkId,
                    'sasaran' => $sasaran['sasaran']
                ]);

                $pkSasaranId = $db->insertID();

                if (!empty($sasaran['indikator'])) {
                    foreach ($sasaran['indikator'] as $indikator) {
                        $db->table('pk_bupati_indikator')->insert([
                            'pk_bup_sasaran_id' => $pkSasaranId,
                            'indikator' => $indikator['indikator'],
                            'target' => $indikator['target']
                        ]);
                    }
                }
            }

            // Proses Program dan Anggaran
            if (isset($data['program']) && is_array($data['program'])) {
                foreach ($data['program'] as $program) {
                    $programId = $program['program_id'];

                    $db->table('pk_bupati_program')->insert([
                        'pk_bup_id' => $pkId,
                        'program_id' => $programId
                    ]);
                }
            }

            $db->transComplete();

            return $db->transStatus() ? $pkId : false;

        } catch (\Exception $e) {
            $db->transRollback();
            throw $e;
        }
    }

    public function updateCompletePk($id, $data)
    {
        $db = \Config\Database::connect();
        $db->transStart();

        try {
            // Update tabel pk_bupati
            $pkData = [
                'nama'    => $data['nama'],
                'tanggal' => $data['tanggal'],
                'rpjmd_misi_id' => $data['rpjmd_misi_id'] ?? null,
            ];
            
            $this->update($id, $pkData);
            
            // Hapus data lama
            $db->table('pk_bupati_sasaran')->where('pk_bup_id', $id)->delete();
            $db->table('pk_bupati_program')->where('pk_bup_id', $id)->delete();
            
            // Simpan data baru
            foreach ($data['sasaran_pk'] as $sasaran) {
                $db->table('pk_bupati_sasaran')->insert([
                    'pk_bup_id' => $id,
                    'sasaran' => $sasaran['sasaran']
                ]);

                $pkSasaranId = $db->insertID();

                if (!empty($sasaran['indikator'])) {
                    foreach ($sasaran['indikator'] as $indikator) {
                        $db->table('pk_bupati_indikator')->insert([
                            'pk_bup_sasaran_id' => $pkSasaranId,
                            'indikator' => $indikator['indikator'],
                            'target' => $indikator['target']
                        ]);
                    }
                }
            }

            if (isset($data['program']) && is_array($data['program'])) {
                foreach ($data['program'] as $program) {
                    $db->table('pk_bupati_program')->insert([
                        'pk_bup_id' => $id,
                        'program_id' => $program['program_id']
                    ]);
                }
            }

            $db->transComplete();

            return $db->transStatus();

        } catch (\Exception $e) {
            $db->transRollback();
            throw $e;
        }
    }

    public function deleteCompletePk($id)
    {
        $db = \Config\Database::connect();
        $db->transStart();

        try {
            // Hapus indikator terlebih dahulu
            $sasaranIds = $db->table('pk_bupati_sasaran')
                ->select('id')
                ->where('pk_bup_id', $id)
                ->get()
                ->getResultArray();
            
            foreach ($sasaranIds as $sasaran) {
                $db->table('pk_bupati_indikator')
                    ->where('pk_bup_sasaran_id', $sasaran['id'])
                    ->delete();
            }
            
            // Hapus sasaran
            $db->table('pk_bupati_sasaran')->where('pk_bup_id', $id)->delete();
            
            // Hapus program
            $db->table('pk_bupati_program')->where('pk_bup_id', $id)->delete();
            
            // Hapus PK utama
            $this->delete($id);

            $db->transComplete();

            return $db->transStatus();

        } catch (\Exception $e) {
            $db->transRollback();
            throw $e;
        }
    }

}
