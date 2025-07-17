<?php

namespace App\Models;

use CodeIgniter\Model;

class PkModel extends Model
{
    protected $table            = 'pk';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
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
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    // Validation
    protected $validationRules = [
        'jenis' => 'required|in_list[jpt,administrator,pengawas]',
        'pihak_1' => 'required|integer',
        'pihak_2' => 'required|integer',
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

    public function getPkForTable($opdId = null)
    {
        $query = $this->db->table('pk p')
        ->select('
            p.id as pk_id,
            p.jenis,
            p.tanggal,
            o.nama_opd,
            ps.id as sasaran_id,
            ps.sasaran,
            pi.id as indikator_id,
            pi.indikator,
            pi.target,
            pp.id as pk_program_id,
            pr.program_kegiatan,
            pr.anggaran
        ')
        ->join('opd o', 'o.id = p.opd_id') // Join OPD
        ->join('pk_sasaran ps', 'ps.pk_id = p.id', 'left')
        ->join('pk_indikator pi', 'pi.pk_sasaran_id = ps.id', 'left')
        ->join('pk_program pp', 'pp.pk_id = p.id', 'left')
        ->join('program_pk pr', 'pr.id = pp.program_id', 'left');

        if ($opdId !== null) {
            $query->where('p.opd_id', $opdId);
        }

        $data = $query
            ->orderBy('p.id', 'ASC')
            ->orderBy('ps.id', 'ASC')
            ->orderBy('pi.id', 'ASC')
            ->orderBy('pp.id', 'ASC')
            ->get()
            ->getResultArray();

        return $data;
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
                'jenis'   => $data['jenis'],
                'pihak_1' => $data['pihak_1'],
                'pihak_2' => $data['pihak_2'],
                'tanggal' => $data['tanggal'],
            ];
            
            $this->db->table('pk')->insert($pkData);
            $pkId = $this->db->insertID();
            
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
                            'target' => $indikator['target']
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
            throw $e;
        }
    }

}
