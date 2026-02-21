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
            ->select('
            MIN(pp.id) as pk_program_id,
            pr.program_kegiatan,
            pr.anggaran,
            pp.program_id
        ')
            ->join('program_pk pr', 'pr.id = pp.program_id', 'left')
            ->join('pk_indikator pi', 'pi.id = pp.pk_indikator_id', 'left')
            ->join('pk_sasaran ps', 'ps.id = pi.pk_sasaran_id', 'left')
            ->where('ps.pk_id', $pkId)
            ->groupBy('pp.program_id, pr.anggaran')
            ->orderBy('pk_program_id', 'ASC')
            ->get()
            ->getResultArray();
    }


    public function getKegiatanByPkId($pkId)
    {
        return $this->db->table('pk_kegiatan kk')
            ->select('
            kk.id as pk_kegiatan_id,
            kp.kegiatan,
            kp.anggaran,
            kk.kegiatan_id,
            kk.pk_program_id,
            pr.program_kegiatan
        ')
            ->join('kegiatan_pk kp', 'kp.id = kk.kegiatan_id', 'left')
            ->join('pk_program pp', 'pp.id = kk.pk_program_id', 'left')
            ->join('program_pk pr', 'pr.id = pp.program_id', 'left')
            ->join('pk_indikator pi', 'pi.id = pp.pk_indikator_id', 'left')
            ->join('pk_sasaran ps', 'ps.id = pi.pk_sasaran_id', 'left')
            ->where('ps.pk_id', $pkId)
            ->orderBy('pr.program_kegiatan', 'ASC')
            ->orderBy('kk.id', 'ASC')
            ->get()
            ->getResultArray();
    }


    public function getSubKegiatanByPkId($pkId)
    {
        return $this->db->table('pk_subkegiatan psk')
            ->select('
            kp.kegiatan,
            skp.sub_kegiatan,
            skp.anggaran
        ')
            ->join('sub_kegiatan_pk skp', 'skp.id = psk.subkegiatan_id', 'left')
            ->join('pk_kegiatan kk', 'kk.id = psk.pk_kegiatan_id', 'left')
            ->join('kegiatan_pk kp', 'kp.id = kk.kegiatan_id', 'left')
            ->join('pk_program pp', 'pp.id = kk.pk_program_id', 'left')
            ->join('pk_indikator pi', 'pi.id = pp.pk_indikator_id', 'left')
            ->join('pk_sasaran ps', 'ps.id = pi.pk_sasaran_id', 'left')
            ->where('ps.pk_id', $pkId)
            ->groupBy([
                'kp.kegiatan',
                'skp.sub_kegiatan',
                'skp.anggaran'
            ])
            ->orderBy('kp.kegiatan', 'ASC')
            ->orderBy('skp.sub_kegiatan', 'ASC')
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

            // ======================
            // INDIKATOR
            // ======================
            $indikatorList = $this->db->table('pk_indikator pi')
                ->select('pi.*, satuan.satuan as satuan_nama')
                ->join('satuan', 'satuan.id = pi.id_satuan', 'left')
                ->where('pi.pk_sasaran_id', $sasaran['id'])
                ->orderBy('pi.id', 'ASC')
                ->get()
                ->getResultArray();

            foreach ($indikatorList as &$indikator) {

                // ======================
                // PROGRAM (PER INDIKATOR)
                // ======================
                $programList = $this->db->table('pk_program pp')
                    ->select('
                    pp.id as pk_program_id,
                    pr.program_kegiatan,
                    pr.anggaran,
                    pp.program_id
                ')
                    ->join('program_pk pr', 'pr.id = pp.program_id', 'left')
                    ->where('pp.pk_indikator_id', $indikator['id'])
                    ->orderBy('pp.id', 'ASC')
                    ->get()
                    ->getResultArray();

                foreach ($programList as &$program) {

                    // ======================
                    // KEGIATAN (PER PROGRAM)
                    // ======================
                    $kegiatanList = $this->db->table('pk_kegiatan pkeg')
                        ->select('
                        pkeg.id as pk_kegiatan_id,
                        keg.kegiatan,
                        keg.anggaran,
                        pkeg.kegiatan_id
                    ')
                        ->join('kegiatan_pk keg', 'keg.id = pkeg.kegiatan_id', 'left')
                        ->where('pkeg.pk_program_id', $program['pk_program_id'])
                        ->orderBy('pkeg.id', 'ASC')
                        ->get()
                        ->getResultArray();

                    // ======================
                    // SUBKEGIATAN (PER KEGIATAN)
                    // ======================
                    foreach ($kegiatanList as &$kegiatan) {

                        $subkegiatanList = $this->db->table('pk_subkegiatan pksub')
                            ->select('
                            pksub.id as pk_subkegiatan_id,
                            sub.sub_kegiatan,
                            sub.anggaran,
                            pksub.subkegiatan_id
                        ')
                            ->join('sub_kegiatan_pk sub', 'sub.id = pksub.subkegiatan_id', 'left')
                            ->where('pksub.pk_kegiatan_id', $kegiatan['pk_kegiatan_id'])
                            ->orderBy('pksub.id', 'ASC')
                            ->get()
                            ->getResultArray();

                        // 🔑 masukkan subkegiatan ke kegiatan
                        $kegiatan['subkegiatan'] = $subkegiatanList;
                    }

                    // masukkan kegiatan (lengkap dengan subkegiatan) ke program
                    $program['kegiatan'] = $kegiatanList;
                }

                // masukkan program ke indikator
                $indikator['program'] = $programList;
            }

            // masukkan indikator ke sasaran
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
                'opd_id' => $data['opd_id'],
                'tahun' => $data['tahun'],
                'jenis' => $data['jenis'],
                'pihak_1' => $data['pihak_1'],
                'pihak_2' => $data['pihak_2'],
                'tanggal' => $data['tanggal'],
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
                        'pk_id' => $pkId,
                        'referensi_pk_id' => $ref['referensi_pk_id'] ?? null,
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
                        'pk_id' => $pkId,
                        'jenis' => $data['jenis'],
                        'sasaran' => $sasaran['sasaran'],
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
                                'pk_sasaran_id' => $pkSasaranId,
                                'jenis' => $data['jenis'],
                                'indikator' => $indikator['indikator'] ?? '',
                                'target' => $indikator['target'] ?? '',
                                'id_satuan' => $idSatuan,
                                'jenis_indikator' => $indikator['jenis_indikator'],
                                'created_at' => $now,
                                'updated_at' => $now
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
                                        'program_id' => $programItem['program_id'] ?? null,
                                        'pk_indikator_id' => $pkIndikatorId,
                                        'created_at' => $now,
                                        'updated_at' => $now
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
                                                'kegiatan_id' => $kegiatanItem['kegiatan_id'] ?? null,
                                                'created_at' => $now,
                                                'updated_at' => $now
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
                                                        'created_at' => $now,
                                                        'updated_at' => $now
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
                        'pk_id' => $pkId,
                        'rpjmd_misi_id' => $misiId,
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
    public function updateCompletePk($pkId, $data)
    {
        $db = \Config\Database::connect();
        $db->transStart();
        $now = date('Y-m-d H:i:s');

        try {

            // =====================================================
            // 1. UPDATE PK
            // =====================================================
            $db->table('pk')->where('id', $pkId)->update([
                'pihak_1' => $data['pihak_1'],
                'pihak_2' => $data['pihak_2'],
                'tahun' => $data['tahun'],
                'tanggal' => $data['tanggal'],
                'updated_at' => $now
            ]);

            // =====================================================
            // 2. SYNC SASARAN
            // =====================================================
            $existingSasaran = $db->table('pk_sasaran')
                ->where('pk_id', $pkId)->get()->getResultArray();
            $existingSasaranIds = array_column($existingSasaran, 'id');
            $usedSasaranIds = [];

            foreach ($data['sasaran_pk'] as $s) {

                // ---------- SASARAN ----------
                if (!empty($s['pk_sasaran_id'])) {
                    $db->table('pk_sasaran')
                        ->where('id', $s['pk_sasaran_id'])
                        ->update([
                            'sasaran' => $s['sasaran'],
                            'updated_at' => $now
                        ]);
                    $pkSasaranId = $s['pk_sasaran_id'];
                } else {
                    $db->table('pk_sasaran')->insert([
                        'pk_id' => $pkId,
                        'jenis' => $data['jenis'],
                        'sasaran' => $s['sasaran'],
                        'created_at' => $now,
                        'updated_at' => $now
                    ]);
                    $pkSasaranId = $db->insertID();
                }
                $usedSasaranIds[] = $pkSasaranId;

                // =====================================================
                // 3. SYNC INDIKATOR
                // =====================================================
                $existingInd = $db->table('pk_indikator')
                    ->where('pk_sasaran_id', $pkSasaranId)->get()->getResultArray();
                $existingIndIds = array_column($existingInd, 'id');
                $usedIndIds = [];

                foreach ($s['indikator'] as $ind) {

                    if (!empty($ind['pk_indikator_id'])) {
                        $db->table('pk_indikator')->where('id', $ind['pk_indikator_id'])->update([
                            'indikator' => $ind['indikator'],
                            'target' => $ind['target'],
                            'id_satuan' => $ind['id_satuan'],
                            'jenis_indikator' => $ind['jenis_indikator'],
                            'updated_at' => $now
                        ]);
                        $pkIndId = $ind['pk_indikator_id'];
                    } else {
                        $db->table('pk_indikator')->insert([
                            'pk_sasaran_id' => $pkSasaranId,
                            'jenis' => $data['jenis'],
                            'indikator' => $ind['indikator'],
                            'target' => $ind['target'],
                            'id_satuan' => $ind['id_satuan'],
                            'jenis_indikator' => $ind['jenis_indikator'],
                            'created_at' => $now,
                            'updated_at' => $now
                        ]);
                        $pkIndId = $db->insertID();
                    }
                    $usedIndIds[] = $pkIndId;

                    // =====================================================
                    // 4. SYNC PROGRAM
                    // =====================================================
                    if (empty($ind['program']) || !is_array($ind['program'])) {
                        continue;
                    }

                    foreach ($ind['program'] as $p) {

                        if (!empty($p['pk_program_id'])) {
                            $db->table('pk_program')->where('id', $p['pk_program_id'])->update([
                                'program_id' => $p['program_id'],
                                'updated_at' => $now
                            ]);
                            $pkProgramId = $p['pk_program_id'];
                        } else {
                            $db->table('pk_program')->insert([
                                'pk_indikator_id' => $pkIndId,
                                'program_id' => $p['program_id'],
                                'created_at' => $now,
                                'updated_at' => $now
                            ]);
                            $pkProgramId = $db->insertID();
                        }

                        // 🔥 JPT STOP DI PROGRAM
                        if ($data['jenis'] === 'jpt') {
                            continue;
                        }

                        if (empty($p['kegiatan']) || !is_array($p['kegiatan'])) {
                            continue;
                        }

                        // =====================================================
                        // 5. SYNC KEGIATAN
                        // =====================================================
                        foreach ($p['kegiatan'] as $k) {

                            if (!empty($k['pk_kegiatan_id'])) {
                                $db->table('pk_kegiatan')->where('id', $k['pk_kegiatan_id'])->update([
                                    'kegiatan_id' => $k['kegiatan_id'],
                                    'updated_at' => $now
                                ]);
                                $pkKegId = $k['pk_kegiatan_id'];
                            } else {
                                $db->table('pk_kegiatan')->insert([
                                    'pk_program_id' => $pkProgramId,
                                    'kegiatan_id' => $k['kegiatan_id'],
                                    'created_at' => $now,
                                    'updated_at' => $now
                                ]);
                                $pkKegId = $db->insertID();
                            }

                            // =====================================================
                            // 6. SYNC SUBKEGIATAN
                            // =====================================================
                            if (
                                $data['jenis'] === 'pengawas' &&
                                !empty($k['subkegiatan']) &&
                                is_array($k['subkegiatan'])
                            ) {

                                $existingSub = $db->table('pk_subkegiatan')
                                    ->where('pk_kegiatan_id', $pkKegId)
                                    ->get()
                                    ->getResultArray();

                                $existingSubIds = array_column($existingSub, 'id');
                                $usedSubIds = [];

                                foreach ($k['subkegiatan'] as $sk) {

                                    if (!empty($sk['pk_subkegiatan_id'])) {

                                        // UPDATE
                                        $db->table('pk_subkegiatan')
                                            ->where('id', $sk['pk_subkegiatan_id'])
                                            ->update([
                                                'subkegiatan_id' => $sk['subkegiatan_id'],
                                                'updated_at' => $now
                                            ]);

                                        $pkSubId = $sk['pk_subkegiatan_id'];
                                    } else {

                                        // INSERT
                                        $db->table('pk_subkegiatan')->insert([
                                            'pk_kegiatan_id' => $pkKegId,
                                            'subkegiatan_id' => $sk['subkegiatan_id'],
                                            'created_at' => $now,
                                            'updated_at' => $now
                                        ]);

                                        $pkSubId = $db->insertID();
                                    }

                                    $usedSubIds[] = $pkSubId;
                                }

                                // DELETE subkegiatan yang dihapus user
                                $deleteSub = array_diff($existingSubIds, $usedSubIds);
                                if (!empty($deleteSub)) {
                                    $db->table('pk_subkegiatan')->whereIn('id', $deleteSub)->delete();
                                }
                            }
                        }
                    }
                }
            }

            // DELETE sasaran yang dihapus
            $deleteSasaran = array_diff($existingSasaranIds, $usedSasaranIds);
            if ($deleteSasaran) {
                $db->table('pk_sasaran')->whereIn('id', $deleteSasaran)->delete();
            }

            $db->transComplete();
            return true;
        } catch (\Exception $e) {
            $db->transRollback();
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
        return $this->db->table('program_pk')->orderBy('program_kegiatan', 'ASC')->get()->getResultArray();
    }

    public function getProgramsForBupatiFromPkJpt(int $tahun)
    {
        return $this->db->table('pk')
            ->select([
                'pk.id AS pk_id',
                'pk.opd_id',
                'opd.nama_opd',
                'pr.id AS program_id',
                'pr.program_kegiatan',
                'pr.anggaran'
            ])
            ->join('opd', 'opd.id = pk.opd_id', 'left')
            ->join('pegawai', 'pegawai.id = pk.pihak_1', 'left') // 🔥 JOIN PEGAWAI
            ->join('pk_sasaran ps', 'ps.pk_id = pk.id', 'inner')
            ->join('pk_indikator pi', 'pi.pk_sasaran_id = ps.id', 'inner')
            ->join('pk_program pp', 'pp.pk_indikator_id = pi.id', 'inner')
            ->join('program_pk pr', 'pr.id = pp.program_id', 'inner')
            ->where('pk.jenis', 'jpt')
            ->where('pi.jenis', 'jpt')
            ->where('pk.tahun', $tahun)

            // 🔥 KONDISI KHUSUS OPD 2
            ->groupStart()
            ->where('pk.opd_id !=', 2)
            ->orGroupStart()
            ->where('pk.opd_id', 2)
            ->where('pegawai.jabatan_id', 170)
            ->groupEnd()
            ->groupEnd()

            ->groupBy([
                'pk.id',
                'pk.opd_id',
                'opd.nama_opd',
                'pr.id',
                'pr.anggaran'
            ])
            ->orderBy('pk.opd_id', 'ASC')
            ->orderBy('pr.program_kegiatan', 'ASC')
            ->get()
            ->getResultArray();
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
            ->select('program_pk.program_kegiatan, program_pk.id, program_pk.anggaran')
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
        return $this->db->table('pk_kegiatan pkeg')
            ->select([
                'keg.id',
                'keg.kegiatan',
                'keg.anggaran',
                'pp.program_id AS program_id'
            ])
            ->join('kegiatan_pk keg', 'keg.id = pkeg.kegiatan_id')
            ->join('pk_program pp', 'pp.id = pkeg.pk_program_id')
            ->join('pk_indikator pi', 'pi.id = pp.pk_indikator_id')
            ->join('pk_sasaran ps', 'ps.id = pi.pk_sasaran_id')
            ->join('pk', 'pk.id = ps.pk_id')
            ->where('pi.jenis', 'administrator')
            ->where('pk.opd_id', $opdId)
            ->groupBy('keg.id, pp.program_id')
            ->orderBy('keg.kegiatan', 'ASC')
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
        return 'Rp ' . number_format((float) $anggaran, 2, ',', '.');
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
    public function getCompletePkByOpdIdAndJenis($opdId, $jenis, $tahun)
    {
        $builder = $this->db->table('pk');
        $builder->select('pk.*, opd.nama_opd');
        $builder->join('opd', 'opd.id = pk.opd_id', 'left');
        $builder->where('pk.opd_id', $opdId);
        $builder->where('pk.jenis', $jenis);
        $builder->where('pk.tahun', $tahun);
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

    public function getCompletePkById($id)
    {
        $builder = $this->db->table('pk');
        $builder->select('pk.*, opd.nama_opd');
        $builder->join('opd', 'opd.id = pk.opd_id', 'left');
        $builder->where('pk.id', $id);
        $query = $builder->get();

        $results = $query->getResultArray();
        if (empty($results)) {
            return [];
        }

        foreach ($results as &$pk) {
            $pk['sasaran'] = $this->getSasaranByPkId($pk['id']);
            // $pk['program'] = $this->getProgramByPkId($pk['id']);
            // $pk['kegiatan'] = $this->getKegiatanByPkId($pk['id']);
            // $pk['subkegiatan'] = $this->getSubKegiatanByPkId($pk['id']);
        }

        return $results;
    }

    public function getProgramByJenis($pkId, $jenis)
    {
        switch (strtolower($jenis)) {
            case 'bupati':
                return $this->getProgramsForBupatiFromPkJpt(intval());

            case 'jpt':
                return $this->getProgramByPkId($pkId);

            case 'administrator':
                return $this->getKegiatanByPkId($pkId);

            case 'pengawas':
                return $this->getSubKegiatanByPkId($pkId);

            default:
                return [];
        }
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

    public function getPkRelasiByOpdJenisTahun($opdId, $jenis, $tahun)
    {
        return $this->db->table('pk p')
            ->select("
            p.id,
            CASE 
                WHEN p.jenis = 'bupati' THEN 
                    CONCAT(peg1.nama_pegawai, ' (', jab1.nama_jabatan, ')')
                ELSE
                    CONCAT(
                        peg1.nama_pegawai, ' (', jab1.nama_jabatan, ')',
                        ' ↔ ',
                        peg2.nama_pegawai, ' (', jab2.nama_jabatan, ')'
                    )
            END AS relasi
        ")
            ->join('pegawai peg1', 'peg1.id = p.pihak_1', 'left')
            ->join('jabatan jab1', 'jab1.id = peg1.jabatan_id', 'left')
            ->join('pegawai peg2', 'peg2.id = p.pihak_2', 'left')
            ->join('jabatan jab2', 'jab2.id = peg2.jabatan_id', 'left')
            ->where('p.opd_id', $opdId)
            ->where('p.jenis', $jenis)
            ->where('p.tahun', $tahun)
            ->orderBy('peg1.nama_pegawai', 'ASC')
            ->get()
            ->getResultArray();
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
            p.tahun,
            p.pihak_1,
            p.pihak_2,
            p.opd_id,
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

        // =======================
        // AMBIL SASARAN PK
        // =======================
        $pk['sasaran_pk'] = $this->db->table('pk_sasaran')
            ->where('pk_id', $id)
            ->orderBy('id', 'ASC')
            ->get()
            ->getResultArray();

        foreach ($pk['sasaran_pk'] as &$s) {

            // =======================
            // AMBIL INDIKATOR
            // =======================
            $indikatorList = $this->db->table('pk_indikator')
                ->select('pk_indikator.*, satuan.satuan as satuan_nama')
                ->join('satuan', 'satuan.id = pk_indikator.id_satuan', 'left')
                ->where('pk_sasaran_id', $s['id'])
                ->orderBy('id', 'ASC')
                ->get()
                ->getResultArray();

            foreach ($indikatorList as &$indikator) {

                // format satuan
                $indikator['satuan'] = $indikator['satuan_nama'] ?? ($indikator['id_satuan'] ?? '-');

                // =======================
                // AMBIL PROGRAM
                // =======================
                $programList = $this->db->table('pk_program pp')
                    ->select('
                    pp.id as pk_program_id,
                    pr.program_kegiatan,
                    pr.anggaran,
                    pp.program_id,
                    pp.pk_indikator_id
                ')
                    ->join('program_pk pr', 'pr.id = pp.program_id', 'left')
                    ->where('pp.pk_indikator_id', $indikator['id'])
                    ->orderBy('pp.id', 'ASC')
                    ->get()
                    ->getResultArray();

                // =======================
                // AMBIL KEGIATAN DALAM PROGRAM
                // =======================
                foreach ($programList as &$program) {

                    // 🔥 ambil kegiatan berdasarkan program
                    $kegiatanList = $this->db->table('pk_kegiatan pkeg')
                        ->select('pkeg.id AS pk_kegiatan_id,
                                pkeg.kegiatan_id AS kegiatan_id,
                                keg.kegiatan
                                ')
                        ->join('kegiatan_pk keg', 'keg.id = pkeg.kegiatan_id', 'left')
                        ->where('pkeg.pk_program_id', $program['pk_program_id'])
                        ->orderBy('pkeg.id', 'ASC')
                        ->get()
                        ->getResultArray();

                    // looping kegiatan → ambil subkegiatan
                    foreach ($kegiatanList as &$keg) {

                        $subList = $this->db->table('pk_subkegiatan psub')
                            ->select('psub.id as sub_id,
                                    sub.sub_kegiatan,
                                    psub.pk_kegiatan_id,
                                    psub.subkegiatan_id
                                ')
                            ->join('sub_kegiatan_pk sub', 'sub.id = psub.subkegiatan_id', 'left')
                            ->where('psub.pk_kegiatan_id', $keg['pk_kegiatan_id'])
                            ->orderBy('psub.id', 'ASC')
                            ->get()
                            ->getResultArray();

                        // masukkan subkegiatan ke dalam kegiatan
                        $keg['subkegiatan'] = $subList;
                    }

                    // masukkan kegiatan ke dalam program
                    $program['kegiatan'] = $kegiatanList;
                }

                // simpan program lengkap ke indikator
                $indikator['program'] = $programList;
            }

            // masukkan indikator ke sasaran
            $s['indikator'] = $indikatorList;
        }

        return $pk;
    }
}
