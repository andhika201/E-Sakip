<?php

namespace App\Models\Opd;

use CodeIgniter\Model;

class RenstraModel extends Model
{
    protected $db;
    public function __construct()
    {
        parent::__construct();
        $this->db = \Config\Database::connect();
    }

    /**
     * Get all RENSTRA Sasaran dengan filter status dan OPD
     */
    public function getAllRenstraByStatus($status = null, $opdId = null)
    {
        $query = $this->db->table('renstra_sasaran rs');

        if ($status !== null) {
            $query->where('rs.status', $status);
        }

        if ($opdId !== null) {
            $query->where('rs.opd_id', $opdId);
        }

        return $query
            ->orderBy('rs.tahun_mulai', 'ASC')
            ->orderBy('rs.id', 'ASC')
            ->get()
            ->getResultArray();
    }


    /**
     * Get all Renstra Sasaran
     */
    public function getAllSasaran()
    {
        return $this->db->table('renstra_sasaran rs')
            ->select('rs.*, rs.sasaran, o.nama_opd, rt.tujuan as renstra_tujuan, rps.rpjmd_sasaran')
            ->join('opd o', 'o.id = rs.opd_id')
            ->join('renstra_tujuan rt', 'rt.id = rs.renstra_tujuan_id')
            ->join('rpjmd_sasaran rps', 'rps.id = rt.rpjmd_sasaran_id', 'left') // tambahkan left join
            ->orderBy('rs.tahun_mulai', 'ASC')
            ->orderBy('rs.id', 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * Get Renstra Sasaran by ID
     */
    public function getSasaranById($id)
    {
        return $this->db->table('renstra_sasaran rs')
            ->select('rs.*, o.nama_opd, rt.tujuan as renstra_tujuan')
            ->join('opd o', 'o.id = rs.opd_id')
            ->join('renstra_tujuan rt', 'rt.id = rs.renstra_tujuan_id')
            ->where('rs.id', $id)
            ->get()
            ->getRowArray();
    }

    public function getRenstraById($id)
    {
        return $this->db->table('renstra_sasaran')
            ->where('id', $id)
            ->get()
            ->getRowArray();
    }

    /**
     * Get Renstra Sasaran by OPD ID
     */
    public function getSasaranByOpdId($opdId)
    {
        return $this->db->table('renstra_sasaran rs')
            ->select('rs.*, o.nama_opd, rt.tujuan as renstra_tujuan')
            ->join('opd o', 'o.id = rs.opd_id')
            ->join('renstra_tujuan rt', 'rt.id = rs.renstra_tujuan_id')
            ->where('rs.opd_id', $opdId)
            ->orderBy('rs.tahun_mulai', 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * Get Renstra Sasaran by year range
     */
    public function getSasaranByYear($tahun)
    {
        return $this->db->table('renstra_sasaran')
            ->where('tahun_mulai <=', $tahun)
            ->where('tahun_akhir >=', $tahun)
            ->get()
            ->getResultArray();
    }

    /**
     * Get all RENSTRA Sasaran dengan filter status
     */
    // public function getAllRenstraByStatus($status = null, $opdId = null)
    // {
    //     $query = $this->db->table('renstra_sasaran rs');

    //     if ($status !== null) {
    //         $query->where('rs.status', $status);
    //     }

    //     if ($opdId !== null) {
    //         $query->where('rs.opd_id', $opdId);
    //     }

    //     return $query->orderBy('rs.tahun_mulai', 'ASC')
    //         ->get()
    //         ->getResultArray();
    // }

    // ==================== RENSTRA INDIKATOR SASARAN ====================

    /**
     * Get all Indikator Sasaran
     */
    public function getAllIndikatorSasaran()
    {
        return $this->db->table('renstra_indikator_sasaran ris')
            ->select('ris.*, rs.sasaran as sasaran_nama, rs.tahun_mulai, rs.tahun_akhir')
            ->join('renstra_sasaran rs', 'rs.id = ris.renstra_sasaran_id')
            ->orderBy('ris.renstra_sasaran_id', 'ASC')
            ->orderBy('ris.id', 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * Get Indikator Sasaran by Sasaran ID
     */
    public function getIndikatorSasaranBySasaranId($sasaranId)
    {
        return $this->db->table('renstra_indikator_sasaran')
            ->where('renstra_sasaran_id', $sasaranId)
            ->get()
            ->getResultArray();
    }

    /**
     * Get Indikator Sasaran by ID
     */
    public function getIndikatorSasaranById($id)
    {
        return $this->db->table('renstra_indikator_sasaran ris')
            ->select('ris.*, rs.sasaran as sasaran_nama, rs.tahun_mulai, rs.tahun_akhir')
            ->join('renstra_sasaran rs', 'rs.id = ris.renstra_sasaran_id')
            ->where('ris.id', $id)
            ->get()
            ->getRowArray();
    }


    // ==================== RENSTRA TARGET TAHUNAN ====================

    /**
     * Get Target Tahunan by Indikator ID
     */
    public function getTargetTahunanByIndikatorId($indikatorId)
    {
        return $this->db->table('renstra_target')
            ->where('renstra_indikator_id', $indikatorId)
            ->orderBy('tahun', 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * Get Target Tahunan by Indikator ID and Year
     */
    public function getTargetTahunanByIndikatorAndYear($indikatorId, $tahun)
    {
        return $this->db->table('renstra_target')
            ->where('renstra_indikator_id', $indikatorId)
            ->where('tahun', $tahun)
            ->get()
            ->getRowArray();
    }


    // ==================== COMPLETE RENSTRA STRUCTURE ====================

    /**
     * Get complete Renstra structure with all related data
     */
    public function getCompleteRenstraStructure($opdId = null)
    {
        $query = $this->db->table('renstra_sasaran rs')
            ->select('
                rs.id as sasaran_id,
                rs.opd_id,
                rs.renstra_tujuan_id,
                rs.sasaran,
                rs.tahun_mulai,
                rs.tahun_akhir,
                o.nama_opd,
                
                ris.id as indikator_id,
                ris.indikator_sasaran,
                ris.satuan,
                rt.id as target_id,
                rt.tahun,
                rt.target
            ')
            // rps.rpjmd_sasaran,
            ->join('opd o', 'o.id = rs.opd_id')
            ->join('renstra_tujuan rtj', 'rtj.id = rs.renstra_tujuan_id')
            // ->join('rpjmd_sasaran rps', 'rps.id = rs.rpjmd_sasaran_id')
            ->join('renstra_indikator_sasaran ris', 'ris.renstra_sasaran_id = rs.id', 'left')
            ->join('renstra_target rt', 'rt.renstra_indikator_id = ris.id', 'left');

        if ($opdId !== null) {
            $query->where('rs.opd_id', $opdId);
        }

        $results = $query->orderBy('rs.id', 'ASC')
            ->orderBy('ris.id', 'ASC')
            ->orderBy('rt.tahun', 'ASC')
            ->get()
            ->getResultArray();

        // Group results
        $grouped = [];
        foreach ($results as $row) {
            $sasaranKey = $row['sasaran_id'];

            if (!isset($grouped[$sasaranKey])) {
                $grouped[$sasaranKey] = [
                    'sasaran_id' => $row['sasaran_id'],
                    'opd_id' => $row['opd_id'],
                    'nama_opd' => $row['nama_opd'],
                    'renstra_tujuan_id' => $row['renstra_tujuan_id'],
                    'renstra_tujuan' => $row['renstra_tujuan'],
                    'rpjmd_sasaran' => $row['rpjmd_sasaran'],
                    // test
                    'sasaran' => $row['sasaran'],
                    'tahun_mulai' => $row['tahun_mulai'],
                    'tahun_akhir' => $row['tahun_akhir'],
                    'indikator_sasaran' => []
                ];
            }

            if ($row['indikator_id']) {
                $indikatorKey = $row['indikator_id'];

                if (!isset($grouped[$sasaranKey]['indikator_sasaran'][$indikatorKey])) {
                    $grouped[$sasaranKey]['indikator_sasaran'][$indikatorKey] = [
                        'indikator_id' => $row['indikator_id'],
                        'indikator_sasaran' => $row['indikator_sasaran'],
                        'satuan' => $row['satuan'],
                        'target_tahunan' => []
                    ];
                }

                if ($row['target_id']) {
                    $grouped[$sasaranKey]['indikator_sasaran'][$indikatorKey]['target_tahunan'][] = [
                        'target_id' => $row['target_id'],
                        'tahun' => $row['tahun'],
                        'target' => $row['target']
                    ];
                }
            }
        }

        // Convert to indexed array
        return array_values($grouped);
    }

    public function getCompleteRenstraById($id, $opdId)
    {
        // Ambil semua sasaran + indikator + target berdasarkan id tujuan
        $query = $this->db->table('renstra_sasaran rs')
            ->select('
        rs.id as sasaran_id,
        rs.opd_id,
        rs.renstra_tujuan_id,
        rs.sasaran,
        rs.tahun_mulai,
        rs.tahun_akhir,
        o.nama_opd,
        ris.id as indikator_id,
        ris.indikator_sasaran,
        ris.satuan,
        rt.id as target_id,
        rt.tahun,
        rt.target
    ')
            ->join('opd o', 'o.id = rs.opd_id')
            ->join('renstra_tujuan rtj', 'rtj.id = rs.renstra_tujuan_id')
            ->join('renstra_indikator_sasaran ris', 'ris.renstra_sasaran_id = rs.id', 'left')
            ->join('renstra_target rt', 'rt.renstra_indikator_id = ris.id', 'left')
            ->where('rs.id', $id)
            ->where('rs.opd_id', $opdId)
            ->orderBy('rs.id', 'ASC')
            ->orderBy('ris.id', 'ASC')
            ->orderBy('rt.tahun', 'ASC')
            ->get()
            ->getResultArray();


        if (!$query) {
            return null;
        }

        // Ambil info tujuan dari baris pertama
        $result = [
            'renstra_tujuan_id' => $query[0]['renstra_tujuan_id'],
            // 'rpjmd_sasaran_id' => $query[0]['rpjmd_sasaran_id'],
            // 'tujuan_renstra' => $query[0]['tujuan'],
            'tahun_mulai' => $query[0]['tahun_mulai'],
            'tahun_akhir' => $query[0]['tahun_akhir'],
            'sasaran_renstra' => []
        ];

        // Grouping per sasaran -> indikator -> target
        foreach ($query as $row) {
            $sasaranId = $row['sasaran_id'];
            if (!isset($result['sasaran_renstra'][$sasaranId])) {
                $result['sasaran_renstra'][$sasaranId] = [
                    'id' => $sasaranId,
                    'sasaran' => $row['sasaran'],
                    'indikator_sasaran' => []
                ];
            }

            if ($row['indikator_id']) {
                $indikatorId = $row['indikator_id'];
                if (!isset($result['indikator_sasaran'][$indikatorId])) {
                    $result['indikator_sasaran'][$indikatorId] = [
                        'indikator_id' => $indikatorId,
                        'indikator_sasaran' => $row['indikator_sasaran'],
                        'satuan' => $row['satuan'],
                        'target' => []
                    ];
                }

                if ($row['target_id']) {
                    $result['sasaran_renstra'][$sasaranId]['indikator_sasaran'][$indikatorId]['target'][] = [
                        'id' => $row['target_id'],
                        'tahun' => $row['tahun'],
                        'target' => $row['target']
                    ];
                }
            }
        }

        // Ubah ke array numerik agar mudah di-loop di view
        $result['sasaran_renstra'] = array_map(function ($sasaran) {
            $sasaran['indikator_sasaran'] = array_values($sasaran['indikator_sasaran']);
            return $sasaran;
        }, array_values($result['sasaran_renstra']));

        return $result;
    }
    public function getFilteredRenstra($opdId = null, $misi = null, $tujuan = null, $rpjmd = null, $status = null, $periode = null)
    {
        $query = $this->db->table('renstra_sasaran rs')
            ->select('
            rs.id as sasaran_id,
            rs.sasaran,
            rs.status,
            ris.id as indikator_id,
            ris.indikator_sasaran,
            ris.satuan,
            rs.tahun_mulai,
            rs.tahun_akhir,
            o.nama_opd,
            o.singkatan,
            rps.sasaran_rpjmd as rpjmd_sasaran,
            rtj.tujuan_rpjmd as rpjmd_tujuan,
            rm.misi as rpjmd_misi
        ')
            ->join('opd o', 'o.id = rs.opd_id')
            ->join('rpjmd_sasaran rps', 'rps.id = rs.rpjmd_sasaran_id', 'left')
            ->join('rpjmd_tujuan rtj', 'rtj.id = rps.tujuan_id', 'left')
            ->join('rpjmd_misi rm', 'rm.id = rtj.misi_id', 'left')
            ->join('renstra_indikator_sasaran ris', 'ris.renstra_sasaran_id = rs.id');

        // 🏢 Filter OPD
        if ($opdId !== null) {
            $query->where('rs.opd_id', $opdId);
        }

        // 🧭 Filter Misi
        if (!empty($misi)) {
            $query->like('rm.misi', $misi);
        }

        // 🎯 Filter Tujuan
        if (!empty($tujuan)) {
            $query->like('rtj.tujuan_rpjmd', $tujuan);
        }

        // 🥅 Filter Sasaran RPJMD
        if (!empty($rpjmd)) {
            $query->like('rps.sasaran_rpjmd', $rpjmd);
        }

        // 📊 Filter Status
        if (!empty($status)) {
            $query->where('rs.status', $status);
        }

        // 📆 Filter Periode (format: "2021-2026")
        if (!empty($periode)) {
            [$start, $end] = explode('-', $periode);
            $query->where('rs.tahun_mulai >=', (int) $start);
            $query->where('rs.tahun_akhir <=', (int) $end);
        }

        $indikatorData = $query
            ->orderBy('rs.id', 'ASC')
            ->orderBy('ris.id', 'ASC')
            ->get()
            ->getResultArray();

        // Ambil target tahunan
        foreach ($indikatorData as &$indikator) {
            if ($indikator['indikator_id']) {
                $targets = $this->getTargetTahunanByIndikatorId($indikator['indikator_id']);
                $indikator['targets'] = [];
                foreach ($targets as $target) {
                    $indikator['targets'][$target['tahun']] = $target['target'];
                }
            }
        }

        return $indikatorData;
    }

    /**
     * Get Renstra data for display table (flattened structure)
     */
    public function getAllRenstra($opdId = null, $rpjmdFilter = null, $periodeFilter = null, $statusFilter = null)
    {
        $query = $this->db->table('renstra_sasaran rs')
            ->select('
            rs.id as sasaran_id,
            rs.sasaran,
            rs.status,
            ris.id as indikator_id,
            ris.indikator_sasaran,
            ris.satuan,
            rs.tahun_mulai,
            rs.tahun_akhir,
            o.nama_opd,
            o.singkatan,
            rps.sasaran_rpjmd as rpjmd_sasaran,
            rtj.tujuan_rpjmd as rpjmd_tujuan,
            rm.misi as rpjmd_misi
        ')
            ->join('opd o', 'o.id = rs.opd_id')
            ->join('rpjmd_sasaran rps', 'rps.id = rs.rpjmd_sasaran_id', 'left')
            ->join('rpjmd_tujuan rtj', 'rtj.id = rps.tujuan_id', 'left')
            ->join('rpjmd_misi rm', 'rm.id = rtj.misi_id', 'left') // ✅ JOIN MISI

            ->join('renstra_indikator_sasaran ris', 'ris.renstra_sasaran_id = rs.id');

        if ($opdId !== null) {
            $query->where('rs.opd_id', $opdId);
        }
        if ($rpjmdFilter !== null && $rpjmdFilter !== '') {
            $query->where('rps.sasaran_rpjmd', $rpjmdFilter);
        }
        if ($periodeFilter !== null && $periodeFilter !== '') {
            $periodeArr = explode('-', $periodeFilter);
            if (count($periodeArr) == 2) {
                $query->where('rs.tahun_mulai', $periodeArr[0]);
                $query->where('rs.tahun_akhir', $periodeArr[1]);
            }
        }
        if ($statusFilter !== null && $statusFilter !== '') {
            $query->where('rs.status', $statusFilter);
        }
        $indikatorData = $query->orderBy('rs.id', 'ASC')
            ->orderBy('ris.id', 'ASC')
            ->get()
            ->getResultArray();

        foreach ($indikatorData as &$indikator) {
            if ($indikator['indikator_id']) {
                $targets = $this->getTargetTahunanByIndikatorId($indikator['indikator_id']);
                $indikator['targets'] = [];
                foreach ($targets as $target) {
                    $indikator['targets'][$target['tahun']] = $target['target'];
                }
            }
        }

        return $indikatorData;
    }


    // ==================== CRUD OPERATIONS FOR RENSTRA Tujuan ====================
    // public function createTujuan($data)
    // {
    //     $required = ['rpjmd_sasaran_id', 'tujuan'];
    //     foreach ($required as $field) {
    //         if (!isset($data[$field]) || empty($data[$field])) {
    //             throw new \InvalidArgumentException("Field {$field} harus diisi");
    //         }
    //     }
    //     $now = date('Y-m-d H:i:s');
    //     $insertData = [
    //         'rpjmd_sasaran_id' => $data['rpjmd_sasaran_id'],
    //         'tujuan' => $data['tujuan'],
    //         'created_at' => $now,
    //         'updated_at' => $now
    //     ];
    //     $result = $this->db->table('renstra_tujuan')->insert($insertData);
    //     $insertId = $this->db->insertID();
    //     if (!$result) {
    //         $error = $this->db->error();
    //         throw new \Exception("Failed to insert sasaran: " . $error['message']);
    //     }
    //     return $insertId;
    // }

    // public function updateTujuan($id, $data)
    // {
    //     return $this->db->table('renstra_tujuan')->where('id', $id)->update($data);
    // }

    // ==================== CRUD OPERATIONS FOR RENSTRA SASARAN ====================

    /* *
     * Create new RENSTRA Sasaran
     */
    public function createSasaran($data)
    {
        // Validation
        $required = ['renstra_tujuan_id', 'opd_id', 'sasaran', 'tahun_mulai', 'tahun_akhir'];
        foreach ($required as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                throw new \InvalidArgumentException("Field {$field} harus diisi");
            }
        }

        $insertData = [
            'opd_id' => $data['opd_id'],
            'renstra_tujuan_id' => $data['renstra_tujuan_id'],
            'sasaran' => $data['sasaran'],
            'status' => $data['status'] ?? 'draft',
            'tahun_mulai' => $data['tahun_mulai'],
            'tahun_akhir' => $data['tahun_akhir']
        ];

        $result = $this->db->table('renstra_sasaran')->insert($insertData);
        $insertId = $this->db->insertID();

        if (!$result) {
            $error = $this->db->error();
            throw new \Exception("Failed to insert sasaran: " . $error['message']);
        }

        return $insertId;
    }

    /**
     * Update RENSTRA Sasaran
     */
    public function updateSasaran($id, $data)
    {
        return $this->db->table('renstra_sasaran')->where('id', $id)->update($data);
    }

    /**
     * Delete RENSTRA Sasaran (with cascade delete)
     */
    public function deleteSasaran($id)
    {
        $this->db->transStart();

        try {
            // Get and delete related indikator sasaran
            $indikatorList = $this->getIndikatorSasaranBySasaranId($id);
            foreach ($indikatorList as $indikator) {
                $this->deleteIndikatorSasaran($indikator['id']);
            }

            // Delete the sasaran
            $result = $this->db->table('renstra_sasaran')->delete(['id' => $id]);

            $this->db->transComplete();
            return $result;

        } catch (\Exception $e) {
            $this->db->transRollback();
            throw $e;
        }
    }


    // ==================== CRUD OPERATIONS FOR RENSTRA INDIAKTOR SASARAN ====================
    /**
     * Create new RENSTRA Indikator Sasaran
     */
    public function createIndikatorSasaran($data)
    {
        // Validation
        $required = ['renstra_sasaran_id', 'indikator_sasaran', 'satuan'];
        foreach ($required as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                throw new \InvalidArgumentException("Field {$field} harus diisi");
            }
        }

        $insertData = [
            'renstra_sasaran_id' => $data['renstra_sasaran_id'],
            'indikator_sasaran' => $data['indikator_sasaran'],
            'satuan' => $data['satuan'],
        ];


        $result = $this->db->table('renstra_indikator_sasaran')->insert($insertData);
        $insertId = $this->db->insertID();


        if (!$result) {
            $error = $this->db->error();
            throw new \Exception("Failed to insert indikator sasaran: " . $error['message']);
        }

        return $insertId;
    }

    /**
     * Update Renstra Indikator Sasaran
     */
    public function updateIndikatorSasaran($id, $data)
    {
        return $this->db->table('renstra_indikator_sasaran')->where('id', $id)->update($data);
    }

    /**
     * Delete RENSTRA Indikator Sasaran (with cascade delete)
     */
    public function deleteIndikatorSasaran($id)
    {
        $this->db->transStart();

        try {
            // Delete related target tahunan
            $this->db->table('renstra_target')->delete(['renstra_indikator_id' => $id]);

            // Delete the indikator sasaran
            $result = $this->db->table('renstra_indikator_sasaran')->delete(['id' => $id]);

            $this->db->transComplete();
            return $result;

        } catch (\Exception $e) {
            $this->db->transRollback();
            throw $e;
        }
    }


    // ==================== CRUD OPERATIONS FOR RENSTRA TARGET TAHUNAN ====================

    /**
     * Create new RENSTRA Target Tahunan
     */
    public function createTargetTahunan($data)
    {

        // Validation
        $required = ['renstra_indikator_id', 'tahun'];
        foreach ($required as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                throw new \InvalidArgumentException("Field {$field} harus diisi");
            }
        }

        // target_tahunan is optional and can be empty
        $insertData = [
            'renstra_indikator_id' => $data['renstra_indikator_id'],
            'tahun' => $data['tahun'],
            'target' => $data['target'] ?? ''
        ];

        $result = $this->db->table('renstra_target')->insert($insertData);
        $insertId = $this->db->insertID();

        if (!$result) {
            $error = $this->db->error();
            throw new \Exception("Failed to insert target tahunan: " . $error['message']);
        }

        return $insertId;
    }

    /**
     * Update RENSTRA Target Tahunan
     */
    public function updateTargetTahunan($id, $data)
    {
        return $this->db->table('renstra_target')->where('id', $id)->update($data);
    }

    /**
     * Delete RENSTRA Target Tahunan
     */
    public function deleteTargetTahunan($id)
    {
        return $this->db->table('renstra_target')->where('id', $id)->delete();
    }

    /**
     * Delete all RENSTRA Target Tahunan by Indikator Sasaran ID
     */
    public function deleteTargetTahunanByIndikatorId($indikatorId)
    {
        return $this->db->table('renstra_target')
            ->where('renstra_indikator_id', $indikatorId)
            ->delete();
    }


    // ==================== COMPLETE RENSTRA OPERATIONS ====================

    /**
     * Save complete Renstra data (Sasaran + Indikator + Target)
     */
    public function createCompleteRenstra($data)
    {
        // Validasi awal
        if (!isset($data['sasaran_renstra']) || !is_array($data['sasaran_renstra'])) {
            throw new \Exception('Data sasaran_renstra tidak valid atau kosong');
        }

        $this->db->transBegin();

        try {
            $sasaranIds = []; // inisialisasi array penampung id sasaran

            foreach ($data['sasaran_renstra'] as $index => $sasaranItem) {


                // Validasi sasaran item
                if (empty($sasaranItem['sasaran'])) {
                    throw new \Exception("Sasaran pada index {$index} tidak boleh kosong");
                }


                $sasaranData = [
                    'opd_id' => $data['opd_id'],
                    'renstra_tujuan_id' => $sasaranItem['renstra_tujuan_id'] ?? null,
                    'sasaran' => trim($sasaranItem['sasaran'] ?? ''),
                    'status' => $data['status'] ?? 'draft',
                    'tahun_mulai' => $data['tahun_mulai'],
                    'tahun_akhir' => $data['tahun_akhir'],
                ];

                // Insert sasaran ke database
                $sasaranId = $this->createSasaran($sasaranData);


                if (!$sasaranId) {
                    throw new \Exception("Gagal menyimpan sasaran pada index {$index}");
                }

                $sasaranIds[] = $sasaranId;

                // Process Indikator Sasaran untuk sasaran ini
                if (isset($sasaranItem['indikator_sasaran']) && is_array($sasaranItem['indikator_sasaran'])) {

                    foreach ($sasaranItem['indikator_sasaran'] as $indikatorIndex => $indikator) {

 
                        if (empty($indikator['indikator_sasaran'])) {
                            throw new \Exception("Indikator sasaran pada sasaran {$index}, indikator {$indikatorIndex} tidak boleh kosong");
                        }

                        $indikatorData = [
                            'renstra_sasaran_id' => $sasaranId,
                            'indikator_sasaran' => trim($indikator['indikator_sasaran']),
                            'satuan' => trim($indikator['satuan'] ?? ''),
                        ];

                        // Insert indikator ke database
                        $indikatorId = $this->createIndikatorSasaran($indikatorData);


                        if (!$indikatorId) {
                            throw new \Exception("Gagal menyimpan indikator pada sasaran {$index}, indikator {$indikatorIndex}");
                        }

                        // Process Target Tahunan untuk indikator ini
                        if (isset($indikator['target_tahunan']) && is_array($indikator['target_tahunan'])) {

                            foreach ($indikator['target_tahunan'] as $targetIndex => $target) {


                                if (empty($target['tahun']) || empty($target['target'])) {
                                    log_message('warning', "Target pada sasaran {$index}, indikator {$indikatorIndex}, target {$targetIndex} tidak valid, akan diabaikan");
                                    continue;
                                }

                                $targetData = [
                                    'renstra_indikator_id' => $indikatorId,
                                    'tahun' => $target['tahun'],
                                    'target' => trim($target['target']),
                                ];
                                // Insert target ke database
                                $targetId = $this->createTargetTahunan($targetData);

                                if (!$targetId) {
                                    throw new \Exception("Gagal menyimpan target pada sasaran {$index}, indikator {$indikatorIndex}, target {$targetIndex}");
                                }
                            }
                        }
                    }
                }
            }

            if ($this->db->transStatus() === false) {
                $this->db->transRollback();
                throw new \Exception('Transaksi gagal, data tidak tersimpan');
            } else {
                $this->db->transCommit();
            }

            return true;

        } catch (\Exception $e) {
            $this->db->transRollback();
            throw $e;
        }
    }

    /**
     * Update complete Renstra data
     */
    public function updateCompleteRenstra($sasaranId, $data)
    {
        $this->db->transStart();

        try {
            // --- 1. Validasi ---
            if (empty($data['tujuan_renstra'])) {
                throw new \Exception("Tujuan Renstra tidak boleh kosong");
            }

            $this->db->table('renstra_indikator_sasaran')
                ->where('renstra_sasaran_id', $sasaranId)
                ->delete();

            // Insert new indikator and targets
            if (isset($data['indikator_sasaran']) && is_array($data['indikator_sasaran'])) {
                foreach ($data['indikator_sasaran'] as $indikator) {
                    // Skip if indikator is not an array or is empty
                    if (!is_array($indikator) || empty($indikator)) {
                        continue;
                    }

                    // Skip if required fields are missing
                    if (
                        !isset($indikator['indikator_sasaran']) || empty($indikator['indikator_sasaran']) ||
                        !isset($indikator['satuan']) || empty($indikator['satuan'])
                    ) {
                        continue;
                    }

                    $indikatorData = [
                        'renstra_sasaran_id' => $sasaranId,
                        'indikator_sasaran' => $indikator['indikator_sasaran'],
                        'satuan' => $indikator['satuan']

                    ];

                    $this->updateSasaran($sasaranItemId, $sasaranData);

                    // Insert Target Tahunan
                    if (isset($indikator['target_tahunan']) && is_array($indikator['target_tahunan'])) {
                        foreach ($indikator['target_tahunan'] as $target) {
                            // Skip if target data is incomplete
                            if (
                                !is_array($target) || !isset($target['tahun']) || !isset($target['target']) ||
                                empty($target['tahun']) || empty($target['target'])
                            ) {
                                continue;
                            }

                            $targetData = [
                                'renstra_indikator_id' => $indikatorId,
                                'tahun' => $target['tahun'],
                                'target' => $target['target']
                            ];


                            $indikatorData = [
                                'renstra_sasaran_id' => $sasaranItemId,
                                'indikator_sasaran' => $indikator['indikator_sasaran'],
                                'satuan' => $indikator['satuan'],
                            ];
                            $this->db->table('renstra_indikator_sasaran')->insert($indikatorData);
                            $indikatorId = $this->db->insertID();

                            // Target tahunan
                            if (!empty($indikator['target_tahunan']) && is_array($indikator['target_tahunan'])) {
                                foreach ($indikator['target_tahunan'] as $target) {
                                    if (empty($target['tahun']) || empty($target['target'])) {
                                        continue;
                                    }
                                    $targetData = [
                                        'renstra_indikator_id' => $indikatorId,
                                        'tahun' => $target['tahun'],
                                        'target' => $target['target'],
                                    ];
                                    $this->db->table('renstra_target')->insert($targetData);
                                }
                            }
                        }
                    }
                }
            }

            $this->db->transComplete();
            return $this->db->transStatus() !== false;

        } catch (\Exception $e) {
            $this->db->transRollback();
            throw $e;
        }
    }

    /**
     * Create or get Renstra Tujuan
     */
    public function createOrGetTujuan($rpjmdSasaranId, $tujuan)
    {
        $existing = $this->db->table('renstra_tujuan')
            ->where('rpjmd_sasaran_id', $rpjmdSasaranId)
            ->where('tujuan', $tujuan)
            ->get()->getRowArray();

        try {
            // First, delete all RENJA data that references this RENSTRA sasaran
            $renjaSasaranList = $this->db->table('renja_sasaran')
                ->where('renstra_sasaran_id', $sasaranId)
                ->get()
                ->getResultArray();

            foreach ($renjaSasaranList as $renjaSasaran) {
                // Delete RENJA indikator sasaran first
                $this->db->table('renja_indikator_sasaran')
                    ->where('renja_sasaran_id', $renjaSasaran['id'])
                    ->delete();
            }

            // Delete all RENJA sasaran that reference this RENSTRA sasaran
            $this->db->table('renja_sasaran')
                ->where('renstra_sasaran_id', $sasaranId)
                ->delete();

            // Get indikator IDs first
            $indikatorList = $this->getIndikatorSasaranBySasaranId($sasaranId);

            // Delete targets for each indikator
            foreach ($indikatorList as $indikator) {
                $this->deleteTargetTahunanByIndikatorId($indikator['id']);
            }

            // Delete indikator sasaran
            $this->db->table('renstra_indikator_sasaran')
                ->where('renstra_sasaran_id', $sasaranId)
                ->delete();

            // Delete sasaran
            $this->db->table('renstra_sasaran')
                ->where('id', $sasaranId)
                ->delete();

            $this->db->transComplete();

    /**
     * Update Renstra Tujuan
     */
    public function updateTujuan($id, $data)
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        return $this->db->table('renstra_tujuan')->where('id', $id)->update($data);
    }

    /**
     * Delete Renstra Tujuan jika tidak ada sasaran yang mengacu
     */
    public function deleteTujuanIfUnused($tujuanId)
    {
        $count = $this->db->table('renstra_sasaran')->where('renstra_tujuan_id', $tujuanId)->countAllResults();
        if ($count == 0) {
            $this->db->table('renstra_tujuan')->where('id', $tujuanId)->delete();
        }
    }
    public function updateRenstraStatus($id, $status)
    {
        if (!in_array($status, ['draft', 'selesai'])) {
            throw new \InvalidArgumentException("Status harus 'draft' atau 'selesai'");
        }

        return $this->db->table('renstra_sasaran')
            ->where('id', $id)
            ->update(['status' => $status]);
    }


}