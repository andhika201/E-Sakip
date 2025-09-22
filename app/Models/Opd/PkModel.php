<?php

namespace App\Models\Opd;

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

    public function getAllPkData($opdId = null)
    {
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
            ->orderBy('p.id')
            ->orderBy('ps.id')
            ->orderBy('pi.id')
            ->orderBy('pp.id')
            ->get()
            ->getResultArray();

        if (!$query) {
            return null;
        }

        $result = [];

        foreach ($query as $row) {
            $pkId = $row['pk_id'];

            // Jika PK belum ada, buat struktur dasar
            if (!isset($result[$pkId])) {
                $result[$pkId] = [
                    'pk_id' => $row['pk_id'],
                    'opd_id' => $row['opd_id'],
                    'nama_opd' => $row['nama_opd'],
                    'jenis' => $row['jenis'],
                    'tanggal' => $row['tanggal'],
                    'pihak_1' => [
                        'id' => $row['pihak_1_id'],
                        'nama' => $row['pihak_1_nama'],
                        'nip' => $row['pihak_1_nip'],
                        'jabatan' => $row['pihak_1_jabatan'],
                        'pangkat' => $row['pihak_1_pangkat'],
                        'golongan' => $row['pihak_1_golongan'],
                    ],
                    'pihak_2' => [
                        'id' => $row['pihak_2_id'],
                        'nama' => $row['pihak_2_nama'],
                        'nip' => $row['pihak_2_nip'],
                        'jabatan' => $row['pihak_2_jabatan'],
                        'pangkat' => $row['pihak_2_pangkat'],
                        'golongan' => $row['pihak_2_golongan'],
                    ],
                    'sasaran' => [],
                    'program' => [],
                ];
            }

            // Tambahkan sasaran & indikator
            if ($row['sasaran_id']) {
                $sid = $row['sasaran_id'];
                if (!isset($result[$pkId]['sasaran'][$sid])) {
                    $result[$pkId]['sasaran'][$sid] = [
                        'sasaran_id' => $sid,
                        'sasaran' => $row['sasaran'],
                        'indikator' => []
                    ];
                }

                if ($row['indikator_id']) {
                    $indikatorId = $row['indikator_id'];
                    $indikatorExists = false;
                    
                    // Cek apakah indikator sudah ada
                    foreach ($result[$pkId]['sasaran'][$sid]['indikator'] as $existingIndikator) {
                        if ($existingIndikator['indikator_id'] == $indikatorId) {
                            $indikatorExists = true;
                            break;
                        }
                    }
                    
                    // Tambahkan indikator hanya jika belum ada
                    if (!$indikatorExists) {
                        $result[$pkId]['sasaran'][$sid]['indikator'][] = [
                            'indikator_id' => $row['indikator_id'],
                            'indikator' => $row['indikator'],
                            'target' => $row['target']
                        ];
                    }
                }
            }

            // Tambahkan program (cegah duplikasi)
            if ($row['pk_program_id']) {
                $programId = $row['pk_program_id'];
                $programExists = false;
                
                // Cek apakah program sudah ada
                foreach ($result[$pkId]['program'] as $existingProgram) {
                    if ($existingProgram['pk_program_id'] == $programId) {
                        $programExists = true;
                        break;
                    }
                }
                
                // Tambahkan program hanya jika belum ada
                if (!$programExists) {
                    $result[$pkId]['program'][] = [
                        'pk_program_id' => $row['pk_program_id'],
                        'program_kegiatan' => $row['program_kegiatan'],
                        'anggaran' => $row['anggaran']
                    ];
                }
            }
        }

        // Format ulang agar array numerik
        foreach ($result as &$pk) {
            $pk['sasaran'] = array_values($pk['sasaran']);
        }

        return array_values($result);
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

        if (!$pk) return null;

        // Ambil sasaran & indikator
        $pk['sasaran_pk'] = $this->db->table('pk_sasaran')
            ->where('pk_id', $id)
            ->get()
            ->getResultArray();

        foreach ($pk['sasaran_pk'] as &$s) {
            $s['indikator'] = $this->db->table('pk_indikator')
                ->where('pk_sasaran_id', $s['id'])
                ->get()
                ->getResultArray();
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

    public function getCompletePkById($id)
    {
        $builder = $this->db->table('pk p')
            ->select('
                p.id as pk_id,
                p.opd_id,
                p.jenis,
                p.pihak_1,
                p.pihak_2,
                p.tanggal,
                o.nama_opd
            ')
            ->join('opd o', 'o.id = p.opd_id')
            ->where('p.id', $id)
            ->get();

        $pk = $builder->getRowArray();

        if (!$pk) return null;

        // Ambil sasaran & indikator dengan struktur yang konsisten
        $pk['sasaran_pk'] = $this->db->table('pk_sasaran')
            ->where('pk_id', $id)
            ->get()
            ->getResultArray();

        foreach ($pk['sasaran_pk'] as &$s) {
            $s['indikator'] = $this->db->table('pk_indikator')
                ->where('pk_sasaran_id', $s['id'])
                ->get()
                ->getResultArray();
        }

        // Ambil program dengan ID untuk editing
        $pk['program'] = $this->db->table('pk_program pp')
            ->select('pp.id as pk_program_id, pp.program_id, pr.program_kegiatan, pr.anggaran')
            ->join('program_pk pr', 'pr.id = pp.program_id')
            ->where('pp.pk_id', $id)
            ->get()
            ->getResultArray();

        return $pk;
    }

    public function updateCompletePk($id, $data)
    {
        $db = \Config\Database::connect();
        $db->transStart();

        try {
            log_message('debug', 'Starting PK update for ID: ' . $id);
            
            // Update data utama PK
            $pkData = [
                'jenis'   => $data['jenis'],
                'pihak_1' => $data['pihak_1'],
                'pihak_2' => $data['pihak_2'],
                'tanggal' => $data['tanggal'],
            ];
            
            log_message('debug', 'Updating main PK data: ' . json_encode($pkData));
            $db->table('pk')->where('id', $id)->update($pkData);

            // SMART UPDATE PATTERN (seperti Renja)
            $processedSasaranIds = [];
            $processedIndikatorIds = [];
            $processedProgramIds = [];

            // Process Sasaran & Indikator dengan smart update
            if (isset($data['sasaran_pk']) && is_array($data['sasaran_pk'])) {
                foreach ($data['sasaran_pk'] as $sasaranItem) {
                    $sasaranId = null;
                    
                    if (isset($sasaranItem['id']) && !empty($sasaranItem['id'])) {
                        // UPDATE existing sasaran
                        $sasaranId = $sasaranItem['id'];
                        $sasaranUpdateData = [
                            'sasaran' => $sasaranItem['sasaran']
                        ];
                        
                        log_message('debug', 'Updating existing sasaran ID: ' . $sasaranId);
                        $db->table('pk_sasaran')->where('id', $sasaranId)->update($sasaranUpdateData);
                        $processedSasaranIds[] = $sasaranId;
                    } else {
                        // INSERT new sasaran
                        $sasaranInsertData = [
                            'pk_id' => $id,
                            'sasaran' => $sasaranItem['sasaran']
                        ];
                        
                        log_message('debug', 'Inserting new sasaran: ' . json_encode($sasaranInsertData));
                        $db->table('pk_sasaran')->insert($sasaranInsertData);
                        $sasaranId = $db->insertID();
                        $processedSasaranIds[] = $sasaranId;
                    }

                    // Process indikator untuk sasaran ini
                    if (isset($sasaranItem['indikator']) && is_array($sasaranItem['indikator'])) {
                        foreach ($sasaranItem['indikator'] as $indikatorItem) {
                            if (isset($indikatorItem['id']) && !empty($indikatorItem['id'])) {
                                // UPDATE existing indikator
                                $indikatorId = $indikatorItem['id'];
                                $indikatorUpdateData = [
                                    'indikator' => $indikatorItem['indikator'],
                                    'target' => $indikatorItem['target']
                                ];
                                
                                log_message('debug', 'Updating existing indikator ID: ' . $indikatorId);
                                $db->table('pk_indikator')->where('id', $indikatorId)->update($indikatorUpdateData);
                                $processedIndikatorIds[] = $indikatorId;
                            } else {
                                // INSERT new indikator
                                if (!empty($indikatorItem['indikator']) && !empty($indikatorItem['target'])) {
                                    $indikatorInsertData = [
                                        'pk_sasaran_id' => $sasaranId,
                                        'indikator' => $indikatorItem['indikator'],
                                        'target' => $indikatorItem['target']
                                    ];
                                    
                                    log_message('debug', 'Inserting new indikator: ' . json_encode($indikatorInsertData));
                                    $db->table('pk_indikator')->insert($indikatorInsertData);
                                    $processedIndikatorIds[] = $db->insertID();
                                }
                            }
                        }
                    }
                }
            }

            // Process Program dengan smart update
            if (isset($data['program']) && is_array($data['program'])) {
                foreach ($data['program'] as $programItem) {
                    if (isset($programItem['id']) && !empty($programItem['id'])) {
                        // UPDATE existing program
                        $programId = $programItem['id'];
                        $programUpdateData = [
                            'program_id' => $programItem['program_id']
                        ];
                        
                        log_message('debug', 'Updating existing program ID: ' . $programId);
                        $db->table('pk_program')->where('id', $programId)->update($programUpdateData);
                        $processedProgramIds[] = $programId;
                    } else {
                        // INSERT new program
                        if (!empty($programItem['program_id'])) {
                            $programInsertData = [
                                'pk_id' => $id,
                                'program_id' => $programItem['program_id']
                            ];
                            
                            log_message('debug', 'Inserting new program: ' . json_encode($programInsertData));
                            $db->table('pk_program')->insert($programInsertData);
                            $processedProgramIds[] = $db->insertID();
                        }
                    }
                }
            }

            // DELETE unused records (yang tidak ada di processed IDs)
            // Delete unused indikator (harus dilakukan dulu karena foreign key)
            if (!empty($processedIndikatorIds)) {
                $db->query("DELETE pi FROM pk_indikator pi 
                           JOIN pk_sasaran ps ON ps.id = pi.pk_sasaran_id 
                           WHERE ps.pk_id = ? AND pi.id NOT IN (" . implode(',', array_fill(0, count($processedIndikatorIds), '?')) . ")", 
                           array_merge([$id], $processedIndikatorIds));
            } else {
                // Delete all indikator jika tidak ada yang diproses
                $db->query("DELETE pi FROM pk_indikator pi 
                           JOIN pk_sasaran ps ON ps.id = pi.pk_sasaran_id 
                           WHERE ps.pk_id = ?", [$id]);
            }

            // Delete unused sasaran
            if (!empty($processedSasaranIds)) {
                $db->table('pk_sasaran')
                    ->where('pk_id', $id)
                    ->whereNotIn('id', $processedSasaranIds)
                    ->delete();
            } else {
                $db->table('pk_sasaran')->where('pk_id', $id)->delete();
            }

            // Delete unused program
            if (!empty($processedProgramIds)) {
                $db->table('pk_program')
                    ->where('pk_id', $id)
                    ->whereNotIn('id', $processedProgramIds)
                    ->delete();
            } else {
                $db->table('pk_program')->where('pk_id', $id)->delete();
            }

            $db->transComplete();

            if ($db->transStatus() === false) {
                throw new \Exception('Transaction failed');
            }

            log_message('debug', 'PK update completed successfully');
            return true;

        } catch (\Exception $e) {
            $db->transRollback();
            log_message('error', 'Update PK Model Error - ID: ' . $id . ', Message: ' . $e->getMessage());
            log_message('debug', 'Update PK Data: ' . json_encode($data));
            throw new \Exception('Database error: ' . $e->getMessage());
        }
    }


    public function saveCompletePk($data)
    {
        $db = \Config\Database::connect();
        $db->transStart();

        try {
            // Simpan ke tabel pk
            $pkData = [
                'opd_id'  => $data['opd_id'] ?? null,
                'jenis'   => $data['jenis'] ?? null,
                'pihak_1' => $data['pihak_1'] ?? null,
                'pihak_2' => $data['pihak_2'] ?? null,
                'tanggal' => $data['tanggal'] ?? date('Y-m-d'),
            ];

            $db->table('pk')->insert($pkData);
            $pkId = $db->insertID();

            // Simpan ke pk_sasaran & indikator
            if (!empty($data['sasaran_pk']) && is_array($data['sasaran_pk'])) {
                foreach ($data['sasaran_pk'] as $sasaran) {
                    $db->table('pk_sasaran')->insert([
                        'pk_id'   => $pkId,
                        'sasaran' => $sasaran['sasaran'] ?? ''
                    ]);

                    $pkSasaranId = $db->insertID();

                    if (!empty($sasaran['indikator']) && is_array($sasaran['indikator'])) {
                        $indikatorBatch = [];
                        foreach ($sasaran['indikator'] as $indikator) {
                            $indikatorBatch[] = [
                                'pk_sasaran_id' => $pkSasaranId,
                                'indikator'     => $indikator['indikator'] ?? '',
                                'target'        => $indikator['target'] ?? '',
                            ];
                        }
                        if (!empty($indikatorBatch)) {
                            $db->table('pk_indikator')->insertBatch($indikatorBatch);
                        }
                    }
                }
            }

            // Simpan ke pk_program
            if (!empty($data['program']) && is_array($data['program'])) {
                $programBatch = [];
                foreach ($data['program'] as $program) {
                    if (!empty($program['program_id'])) {
                        $programBatch[] = [
                            'pk_id'      => $pkId,
                            'program_id' => $program['program_id']
                        ];
                    }
                }
                if (!empty($programBatch)) {
                    $db->table('pk_program')->insertBatch($programBatch);
                }
            }

            $db->transComplete();
            return $db->transStatus() ? $pkId : false;

        } catch (\Exception $e) {
            $db->transRollback();
            throw $e;
        }
    }


}
