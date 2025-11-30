<?php

namespace App\Models;

use CodeIgniter\Model;

class PkModel extends Model
{

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
     * Get all program entries (pk_program) for a given PK id
     */
    public function getProgramByPkId($pkId)
    {
        return $this->db->table('pk_program pp')
            ->select('pp.id as pk_program_id, pr.program_kegiatan, pr.anggaran, pp.program_id, pp.pk_indikator_id')
            ->join('program_pk pr', 'pr.id = pp.program_id', 'left')
            ->join('pk_indikator pi', 'pi.id = pp.pk_indikator_id', 'left')
            ->join('pk_sasaran ps', 'ps.id = pi.pk_sasaran_id', 'left')
            ->where('ps.pk_id', $pkId)
            ->orderBy('pp.id', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function getKegiatanByPkId($pkId)
    {
        return $this->db->table('pk_kegiatan kk')
            ->select('kk.id as pk_kegiatan_id, kp.kegiatan, kp.anggaran, kk.kegiatan_id, kk.pk_program_id')
            ->join('kegiatan_pk kp', 'kp.id = kk.kegiatan_id', 'left')
            ->join('pk_program pp', 'pp.id = kk.pk_program_id', 'left')
            ->join('pk_indikator pi', 'pi.id = pp.pk_indikator_id', 'left')
            ->join('pk_sasaran ps', 'ps.id = pi.pk_sasaran_id', 'left')
            ->where('ps.pk_id', $pkId)
            ->orderBy('kk.id', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function getSubKegiatanByPkId($pkId)
    {
        return $this->db->table('pk_subkegiatan psk')
            ->select('psk.id as pk_subkegiatan_id, skp.sub_kegiatan, skp.anggaran, psk.subkegiatan_id, psk.pk_kegiatan_id')
            ->join('sub_kegiatan_pk skp', 'skp.id = psk.subkegiatan_id', 'left')
            ->join('pk_kegiatan kk', 'kk.id = psk.pk_kegiatan_id', 'left')
            ->join('pk_program pp', 'pp.id = kk.pk_program_id', 'left')
            ->join('pk_indikator pi', 'pi.id = pp.pk_indikator_id', 'left')
            ->join('pk_sasaran ps', 'ps.id = pi.pk_sasaran_id', 'left')
            ->where('ps.pk_id', $pkId)
            ->orderBy('psk.id', 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * Get all sasaran and their indikator for a given PK id
     */
    public function getSasaranByPkId($pkId)
    {
        $sasaranList = $this->db->table('pk_sasaran')
            ->where('pk_id', $pkId)
            ->orderBy('id', 'ASC')
            ->get()
            ->getResultArray();

        foreach ($sasaranList as &$sasaran) {
            $indikatorList = $this->db->table('pk_indikator')
                ->select('pk_indikator.*, satuan.satuan as satuan_nama')
                ->join('satuan', 'satuan.id = pk_indikator.id_satuan', 'left')
                ->where('pk_sasaran_id', $sasaran['id'])
                ->orderBy('id', 'ASC')
                ->get()
                ->getResultArray();

            foreach ($indikatorList as &$indikator) {
                // Normalisasi key agar view tetap pakai $indikator['satuan']
                $indikator['satuan'] = $indikator['satuan_nama'] ?? ($indikator['id_satuan'] ?? '-');
            }

            $sasaran['indikator'] = $indikatorList;
        }

        return $sasaranList;
    }

    /**
     * Get indikator acuan (referensi) for a given PK
     */
    public function getIndikatorAcuanByPkId($pkId)
    {
        return $this->db->table('pk_referensi pr')
            ->select('pr.*, pi.indikator as nama_indikator, pi.id as indikator_id')
            ->join('pk_indikator pi', 'pi.id = pr.referensi_indikator_id', 'left')
            ->where('pr.pk_id', $pkId)
            ->get()
            ->getResultArray();
    }

    /**
     * Save a complete PK (with sasaran->indikator->program->kegiatan->subkegiatan)
     * (Kept as robust implementation)
     * Returns inserted PK id or false
     */
    public function saveCompletePk($data)
    {
        $db = \Config\Database::connect();
        $db->transStart();

        try {
            $now = date('Y-m-d H:i:s');

            // ---------------------------------------
            // 1. INSERT PK
            // ---------------------------------------
            $pkData = [
                'opd_id'     => $data['opd_id'],
                'jenis'      => $data['jenis'],
                'pihak_1'    => $data['pihak_1'],
                'pihak_2'    => $data['pihak_2'],
                'tanggal'    => $data['tanggal'],
                'created_at' => $now,
                'updated_at' => $now
            ];

            $db->table('pk')->insert($pkData);
            $this->logDbError($db, 'INSERT PK');
            $pkId = $db->insertID();


            // ---------------------------------------
            // 2. REFERENSI ACUAN
            if (!empty($data['referensi_acuan'])) {
                foreach ($data['referensi_acuan'] as $ref) {
                    $db->table('pk_referensi')->insert([
                        'pk_id'                  => $pkId,
                        'referensi_pk_id'        => $ref['referensi_pk_id'] ?? null,
                        'referensi_indikator_id' => $ref['referensi_indikator_id'] ?? null,
                    ]);
                    $this->logDbError($db, 'INSERT PK REFERENSI');
                }
            }


            // ---------------------------------------
            // 3. LOOP: SASARAN → INDIKATOR → PROGRAM → KEGIATAN → SUBKEGIATAN
            // ---------------------------------------
            if (!empty($data['sasaran_pk'])) {

                foreach ($data['sasaran_pk'] as $sasaran) {

                    // Insert Sasaran
                    $db->table('pk_sasaran')->insert([
                        'pk_id'      => $pkId,
                        'jenis'      => $data['jenis'],
                        'sasaran'    => $sasaran['sasaran'],
                        'created_at' => $now,
                        'updated_at' => $now
                    ]);
                    $this->logDbError($db, 'INSERT PK SASARAN');
                    $pkSasaranId = $db->insertID();


                    if (!empty($sasaran['indikator'])) {

                        foreach ($sasaran['indikator'] as $indikator) {

                            $idSatuan = $indikator['id_satuan'] ?? ($indikator['satuan'] ?? null);

                            // Insert Indikator
                            $db->table('pk_indikator')->insert([
                                'pk_sasaran_id'  => $pkSasaranId,
                                'jenis'          => $data['jenis'],
                                'indikator'      => $indikator['indikator'] ?? '',
                                'target'         => $indikator['target'] ?? '',
                                'id_satuan'      => $idSatuan,
                                'jenis_indikator' => $indikator['jenis_indikator'],
                                'created_at'     => $now,
                                'updated_at'     => $now
                            ]);
                            $this->logDbError($db, 'INSERT PK INDIKATOR');
                            $pkIndikatorId = $db->insertID();


                            // ================================
                            // 🔥 INSERT PROGRAM (JPT / ADMIN / PENGAWAS)
                            // ================================
                            if (!empty($indikator['program'])) {

                                foreach ($indikator['program'] as $programItem) {

                                    // Insert program
                                    $db->table('pk_program')->insert([                                        
                                        'program_id'            => $programItem['program_id'] ?? null,
                                        'pk_indikator_id'       => $pkIndikatorId,
                                        'created_at'            => $now,
                                        'updated_at'            => $now
                                    ]);
                                    $this->logDbError($db, 'INSERT PK PROGRAM');
                                    $pkProgramId = $db->insertID();


                                    // ================================
                                    // 🔥 INSERT KEGIATAN (admin + pengawas)
                                    // ================================
                                    if (!empty($programItem['kegiatan'])) {
                                        foreach ($programItem['kegiatan'] as $kegiatanItem) {

                                            $db->table('pk_kegiatan')->insert([
                                                'pk_program_id' => $pkProgramId,
                                                'kegiatan_id'   => $kegiatanItem['kegiatan_id'] ?? null,
                                                'created_at'    => $now,
                                                'updated_at'    => $now
                                            ]);
                                            $this->logDbError($db, 'INSERT PK KEGIATAN');
                                            $pkKegiatanId = $db->insertID();


                                            // ================================
                                            // 🔥 INSERT SUBKEGIATAN (pengawas)
                                            // ================================
                                            if (!empty($kegiatanItem['subkegiatan'])) {

                                                foreach ($kegiatanItem['subkegiatan'] as $subItem) {
                                                    $db->table('pk_subkegiatan')->insert([
                                                        'pk_kegiatan_id' => $pkKegiatanId,
                                                        'subkegiatan_id' => $subItem['subkegiatan_id'] ?? null,
                                                        'created_at'     => $now,
                                                        'updated_at'     => $now
                                                    ]);
                                                    $this->logDbError($db, 'INSERT PK SUBKEGIATAN');
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }


            // ---------------------------------------
            // 4. MISI BUPATI
            // ---------------------------------------
            if (!empty($data['misi_bupati_id'])) {
                foreach ($data['misi_bupati_id'] as $misiId) {
                    $db->table('pk_misi')->insert([
                        'pk_id'          => $pkId,
                        'rpjmd_misi_id'  => $misiId,
                    ]);
                    $this->logDbError($db, 'INSERT PK MISI');
                }
            }

            // commit
            $db->transComplete();
            return $pkId;
        } catch (\Exception $e) {
            $db->transRollback();
            throw $e;
        }
    }


    private function logDbError($db, $label)
    {
        $error = $db->error();
        if (!empty($error['code'])) {
            log_message('error', "DB ERROR on {$label}: " . $error['message']);
        }
    }

    /**
     * Update PK and related data (clean & re-insert approach)
     * - This function removes all related rows for the given pk id and inserts fresh ones according to $data
     * - Returns boolean
     */
    public function updateCompletePk($id, $data)
    {
        $db = \Config\Database::connect();
        $db->transStart();

        try {
            $now = date('Y-m-d H:i:s');

            // 1. update main PK fields
            $db->table('pk')->where('id', $id)->update([
                'pihak_1' => $data['pihak_1'] ?? null,
                'pihak_2' => $data['pihak_2'] ?? null,
                'tanggal' => $data['tanggal'] ?? null,
                'updated_at' => $now
            ]);

            // 2. collect existing sasaran ids (before deletion)
            $sasaranRows = $db->table('pk_sasaran')->select('id')->where('pk_id', $id)->get()->getResultArray();
            $sasaranIds = array_column($sasaranRows, 'id');

            // 3. Delete dependent child records in safe order
            if (!empty($sasaranIds)) {
                // delete pk_indikator by pk_sasaran_id
                $db->table('pk_indikator')->whereIn('pk_sasaran_id', $sasaranIds)->delete();
            }

            // Delete subkegiatan -> kegiatan -> program where pk_program.pk_id = $id
            // First delete pk_subkegiatan via join
            $db->query("
                DELETE sk
                FROM pk_subkegiatan sk
                JOIN pk_kegiatan k ON k.id = sk.pk_kegiatan_id
                JOIN pk_program p ON p.id = k.pk_program_id
                WHERE p.pk_id = ?
            ", [$id]);

            // Then delete pk_kegiatan via join
            $db->query("
                DELETE k
                FROM pk_kegiatan k
                JOIN pk_program p ON p.id = k.pk_program_id
                WHERE p.pk_id = ?
            ", [$id]);

            // Then delete pk_program
            $db->table('pk_program')->where('pk_id', $id)->delete();

            // Finally delete sasaran
            $db->table('pk_sasaran')->where('pk_id', $id)->delete();

            // 4. Insert new structure from provided $data (similar to saveCompletePk but re-using pk id)
            if (!empty($data['sasaran_pk']) && is_array($data['sasaran_pk'])) {
                foreach ($data['sasaran_pk'] as $sasaranItem) {
                    $db->table('pk_sasaran')->insert([
                        'pk_id' => $id,
                        'sasaran' => $sasaranItem['sasaran'] ?? '',
                        'created_at' => $now,
                        'updated_at' => $now
                    ]);
                    $pkSasaranId = $db->insertID();

                    if (!empty($sasaranItem['indikator']) && is_array($sasaranItem['indikator'])) {
                        foreach ($sasaranItem['indikator'] as $indikatorItem) {

                            $idSatuan = $indikatorItem['id_satuan'] ?? ($indikatorItem['satuan'] ?? null);

                            $db->table('pk_indikator')->insert([
                                'pk_sasaran_id' => $pkSasaranId,
                                'indikator' => $indikatorItem['indikator'] ?? '',
                                'target' => $indikatorItem['target'] ?? '',
                                'id_satuan' => $idSatuan,
                                'jenis_indikator' => $indikatorItem['jenis_indikator'] ?? null,
                                'created_at' => $now,
                                'updated_at' => $now
                            ]);
                            $pkIndikatorId = $db->insertID();

                            // Insert program -> kegiatan -> subkegiatan
                            if (!empty($indikatorItem['program']) && is_array($indikatorItem['program'])) {
                                foreach ($indikatorItem['program'] as $programItem) {
                                    $db->table('pk_program')->insert([
                                        'pk_id' => $id,
                                        'program_id' => $programItem['program_id'] ?? null,
                                        'pk_indikator_id' => $pkIndikatorId,
                                        'created_at' => $now,
                                        'updated_at' => $now
                                    ]);
                                    $pkProgramId = $db->insertID();

                                    if (!empty($programItem['kegiatan']) && is_array($programItem['kegiatan'])) {
                                        foreach ($programItem['kegiatan'] as $kegiatanItem) {
                                            $db->table('pk_kegiatan')->insert([
                                                'pk_program_id' => $pkProgramId,
                                                'kegiatan_id' => $kegiatanItem['kegiatan_id'] ?? null,
                                                'created_at' => $now,
                                                'updated_at' => $now
                                            ]);
                                            $pkKegiatanId = $db->insertID();

                                            if (!empty($kegiatanItem['subkegiatan']) && is_array($kegiatanItem['subkegiatan'])) {
                                                foreach ($kegiatanItem['subkegiatan'] as $subItem) {
                                                    $db->table('pk_subkegiatan')->insert([
                                                        'pk_kegiatan_id' => $pkKegiatanId,
                                                        'subkegiatan_id' => $subItem['subkegiatan_id'] ?? null,
                                                        'created_at' => $now,
                                                        'updated_at' => $now
                                                    ]);
                                                }
                                            }
                                        }
                                    }
                                }
                            }

                            // Pengawas path (indikator -> kegiatan -> subkegiatan) if present
                            if (!empty($indikatorItem['kegiatan']) && is_array($indikatorItem['kegiatan'])) {
                                // create helper program row linking to indikator
                                $db->table('pk_program')->insert([
                                    'pk_id' => $id,
                                    'program_id' => $indikatorItem['program_id'] ?? null,
                                    'created_at' => $now,
                                    'updated_at' => $now
                                ]);
                                $pkProgramIdForPengawas = $db->insertID();

                                foreach ($indikatorItem['kegiatan'] as $kegiatanItem) {
                                    $db->table('pk_kegiatan')->insert([
                                        'pk_program_id' => $pkProgramIdForPengawas,
                                        'kegiatan_id' => $kegiatanItem['kegiatan_id'] ?? null,
                                        'created_at' => $now,
                                        'updated_at' => $now
                                    ]);
                                    $pkKegiatanId = $db->insertID();

                                    if (!empty($kegiatanItem['subkegiatan']) && is_array($kegiatanItem['subkegiatan'])) {
                                        foreach ($kegiatanItem['subkegiatan'] as $subItem) {
                                            $db->table('pk_subkegiatan')->insert([
                                                'pk_kegiatan_id' => $pkKegiatanId,
                                                'subkegiatan_id' => $subItem['subkegiatan_id'] ?? null,
                                                'created_at' => $now,
                                                'updated_at' => $now
                                            ]);
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }

            $db->transComplete();
            return $db->transStatus();
        } catch (\Exception $e) {
            $db->transRollback();
            // bubble up exception so controller can log or show error
            throw $e;
        }
    }

    /**
     * Get all satuan
     */
    public function getAllSatuan()
    {
        return $this->db->table('satuan')->orderBy('satuan', 'ASC')->get()->getResultArray();
    }

    /**
     * Get all programs (program_pk)
     */
    public function getAllPrograms()
    {
        return $this->db->table('program_pk')->orderBy('created_at', 'DESC')->get()->getResultArray();
    }

    public function getKegiatan()
    {
        return $this->db->table('kegiatan_pk')
            ->select('id, kegiatan, anggaran')
            ->orderBy('kegiatan', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function getSubKegiatan()
    {
        return $this->db->table('sub_kegiatan_pk')
            ->select('id, sub_kegiatan, anggaran')
            ->orderBy('sub_kegiatan', 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * Get JPT programs from existing pk_program entries in this OPD
     * Returns program_pk rows (program_kegiatan + id)
     */
    public function getJptPrograms($opdId)
    {
        return $this->db->table('pk_program')
            ->select('program_pk.program_kegiatan, program_pk.id')
            ->join('program_pk', 'program_pk.id = pk_program.program_id')
            ->join('pk_indikator', 'pk_indikator.id = pk_program.pk_indikator_id')
            ->join('pk_sasaran', 'pk_sasaran.id = pk_indikator.pk_sasaran_id')
            ->join('pk', 'pk.id = pk_sasaran.pk_id')
            ->where('pk_indikator.jenis', 'jpt')
            ->where('pk.opd_id', $opdId)
            ->orderBy('pk.created_at', 'DESC')
            ->get()
            ->getResultArray();
    }

    public function getKegiatanAdmin($opdId)
    {
        return $this->db->table('pk_kegiatan')
            ->select('kegiatan_pk.kegiatan, kegiatan_pk.id, pk_program_id')
            ->join('kegiatan_pk', 'kegiatan_pk.id = pk_kegiatan.kegiatan_id')
            ->join('pk_program', 'pk_program.id = pk_kegiatan.pk_program_id')
            ->join('pk_indikator', 'pk_indikator.id = pk_program.pk_indikator_id')
            ->join('pk_sasaran', 'pk_sasaran.id = pk_indikator.pk_sasaran_id')
            ->join('pk', 'pk.id = pk_sasaran.pk_id')
            ->where('pk_indikator.jenis', 'administrator')
            ->where('pk.opd_id', $opdId)
            ->orderBy('pk.created_at', 'DESC')
            ->get()
            ->getResultArray();
    }

    /**
     * Get program by id (wrapper)
     */
    public function getProgramById($id)
    {
        return $this->db->table('program_pk')->where('id', $id)->get()->getRowArray();
    }

    /**
     * Format anggaran (helper)
     */
    public function formatAnggaran($anggaran)
    {
        return 'Rp ' . number_format((float)$anggaran, 2, ',', '.');
    }

    /**
     * Search programs by keyword
     */
    public function searchPrograms($keyword)
    {
        return $this->db->table('program_pk')
            ->like('program_kegiatan', $keyword)
            ->orderBy('created_at', 'DESC')
            ->get()
            ->getResultArray();
    }

    /**
     * Get PK Pimpinan by OPD and jenis (wrapper)
     */
    public function getPkPimpinan($opdId, $referensiJenis)
    {
        return $this->where('opd_id', $opdId)
            ->where('jenis', $referensiJenis)
            ->findAll();
    }

    /**
     * Get jenis_indikator by pk id (fixed to use pk_sasaran join)
     */
    public function getJenisIndikatorByPkId($pkId)
    {
        $rows = $this->db->table('pk_indikator')
            ->select('pk_indikator.id, pk_indikator.jenis_indikator')
            ->join('pk_sasaran', 'pk_sasaran.id = pk_indikator.pk_sasaran_id')
            ->where('pk_sasaran.pk_id', $pkId)
            ->get()
            ->getResultArray();

        $result = [];
        foreach ($rows as $row) {
            $result[$row['id']] = $row['jenis_indikator'];
        }
        return $result;
    }

    /**
     * Update capaian indikator (bulk)
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

    /**
     * Get summarized PK data by opd + jenis (used by controller)
     */
    public function getCompletePkByOpdIdAndJenis($opdId, $jenis)
    {
        $builder = $this->db->table('pk');
        $builder->select('pk.*, opd.nama_opd');
        $builder->join('opd', 'opd.id = pk.opd_id', 'left');
        $builder->where('pk.opd_id', $opdId);
        $builder->where('pk.jenis', $jenis);
        $query = $builder->get();

        $results = $query->getResultArray();
        if (empty($results)) {
            return [];
        }

        foreach ($results as &$pk) {
            $pk['sasaran'] = $this->getSasaranByPkId($pk['id']);
            $pk['program'] = $this->getProgramByPkId($pk['id']);
            $pk['kegiatan'] = $this->getKegiatanByPkId($pk['id']);
            $pk['subkegiatan'] = $this->getSubKegiatanByPkId($pk['id']);
        }

        return $results;
    }

    /**
     * getCompletePkByOpdId (detailed join-based summary)
     * Kept for compatibility (returns single merged structure)
     */
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
            ->join('opd o', 'o.id = p.opd_id', 'left')
            ->join('pegawai peg1', 'peg1.id = p.pihak_1', 'left')
            ->join('jabatan j1', 'j1.id = peg1.jabatan_id', 'left')
            ->join('pangkat pang1', 'pang1.id = peg1.pangkat_id', 'left')
            ->join('pegawai peg2', 'peg2.id = p.pihak_2', 'left')
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

        if (empty($query)) {
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
            // Sasaran & indikator
            if ($row['sasaran_id']) {
                $sid = $row['sasaran_id'];
                if (!isset($result['sasaran'][$sid])) {
                    $result['sasaran'][$sid] = [
                        'sasaran_id' => $sid,
                        'sasaran' => $row['sasaran'],
                        'indikator' => []
                    ];
                }

                if (!empty($row['indikator_id'])) {
                    $existingIndikatorIds = array_column($result['sasaran'][$sid]['indikator'], 'indikator_id');
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
            if (!empty($row['pk_program_id'])) {
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

        // convert sasaran map to numeric array
        $result['sasaran'] = array_values($result['sasaran']);

        return $result;
    }

    /**
     * Get PK by id (detailed)
     */
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
            ->join('opd o', 'o.id = p.opd_id', 'left')
            ->join('pegawai peg1', 'peg1.id = p.pihak_1', 'left')
            ->join('jabatan jab1', 'jab1.id = peg1.jabatan_id', 'left')
            ->join('pangkat pang1', 'pang1.id = peg1.pangkat_id', 'left')
            ->join('pegawai peg2', 'peg2.id = p.pihak_2', 'left')
            ->join('jabatan jab2', 'jab2.id = peg2.jabatan_id', 'left')
            ->join('pangkat pang2', 'pang2.id = peg2.pangkat_id', 'left')
            ->where('p.id', $id)
            ->get();

        $pk = $builder->getRowArray();
        if (!$pk) {
            return null;
        }

        // sasaran & indikator
        $pk['sasaran_pk'] = $this->db->table('pk_sasaran')->where('pk_id', $id)->orderBy('id', 'ASC')->get()->getResultArray();
        foreach ($pk['sasaran_pk'] as &$s) {
            $indikatorList = $this->db->table('pk_indikator')
                ->select('pk_indikator.*, satuan.satuan as satuan_nama')
                ->join('satuan', 'satuan.id = pk_indikator.id_satuan', 'left')
                ->where('pk_sasaran_id', $s['id'])
                ->orderBy('id', 'ASC')
                ->get()
                ->getResultArray();

            foreach ($indikatorList as &$indikator) {
                $indikator['satuan'] = $indikator['satuan_nama'] ?? ($indikator['id_satuan'] ?? '-');
            }

            $s['indikator'] = $indikatorList;
        }

        // program pk (top-level)
        $pk['program_pk'] = $this->db->table('pk_program pp')
            ->select('pp.id as pk_program_id, pr.program_kegiatan, pr.anggaran, pp.id_indikator, pp.program_id')
            ->join('program_pk pr', 'pr.id = pp.program_id', 'left')
            ->where('pp.pk_id', $id)
            ->orderBy('pp.id', 'ASC')
            ->get()
            ->getResultArray();

        return $pk;
    }
}
