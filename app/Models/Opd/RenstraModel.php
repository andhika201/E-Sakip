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

    // =========================================================
    // RENSTRA SASARAN (LEVEL OPD)
    // =========================================================

    /**
     * Get all RENSTRA Sasaran dengan filter status & OPD
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
     * Get all Renstra Sasaran (dengan OPD, Renstra Tujuan & RPJMD Sasaran)
     */
    public function getAllSasaran()
    {
        return $this->db->table('renstra_sasaran rs')
            ->select('
                rs.*,
                rs.sasaran,
                o.nama_opd,
                rtj.tujuan AS renstra_tujuan,
                rps.sasaran_rpjmd AS rpjmd_sasaran
            ')
            ->join('opd o', 'o.id = rs.opd_id')
            ->join('renstra_tujuan rtj', 'rtj.id = rs.renstra_tujuan_id')
            ->join('rpjmd_sasaran rps', 'rps.id = rtj.rpjmd_sasaran_id')
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
            ->select('
                rs.*,
                o.nama_opd,
                rtj.tujuan AS renstra_tujuan,
                rps.sasaran_rpjmd AS rpjmd_sasaran
            ')
            ->join('opd o', 'o.id = rs.opd_id')
            ->join('renstra_tujuan rtj', 'rtj.id = rs.renstra_tujuan_id')
            ->join('rpjmd_sasaran rps', 'rps.id = rtj.rpjmd_sasaran_id')
            ->where('rs.id', $id)
            ->get()
            ->getRowArray();
    }

    /**
     * Get Renstra Sasaran row mentah
     */
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
            ->select('
                rs.*,
                o.nama_opd,
                rtj.tujuan AS renstra_tujuan,
                rps.sasaran_rpjmd AS rpjmd_sasaran
            ')
            ->join('opd o', 'o.id = rs.opd_id')
            ->join('renstra_tujuan rtj', 'rtj.id = rs.renstra_tujuan_id')
            ->join('rpjmd_sasaran rps', 'rps.id = rtj.rpjmd_sasaran_id')
            ->where('rs.opd_id', $opdId)
            ->orderBy('rs.tahun_mulai', 'ASC')
            ->orderBy('rs.id', 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * Get Sasaran by tahun di dalam periode
     */
    public function getSasaranByYear($tahun)
    {
        return $this->db->table('renstra_sasaran')
            ->where('tahun_mulai <=', $tahun)
            ->where('tahun_akhir >=', $tahun)
            ->get()
            ->getResultArray();
    }

    // =========================================================
    // RENSTRA INDIKATOR SASARAN
    // =========================================================

    public function getAllIndikatorSasaran()
    {
        return $this->db->table('renstra_indikator_sasaran ris')
            ->select('
                ris.*,
                rs.sasaran AS sasaran_nama,
                rs.tahun_mulai,
                rs.tahun_akhir
            ')
            ->join('renstra_sasaran rs', 'rs.id = ris.renstra_sasaran_id')
            ->orderBy('ris.renstra_sasaran_id', 'ASC')
            ->orderBy('ris.id', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function getIndikatorSasaranBySasaranId($sasaranId)
    {
        return $this->db->table('renstra_indikator_sasaran')
            ->where('renstra_sasaran_id', $sasaranId)
            ->orderBy('id', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function getIndikatorSasaranById($id)
    {
        return $this->db->table('renstra_indikator_sasaran ris')
            ->select('
                ris.*,
                rs.sasaran AS sasaran_nama,
                rs.tahun_mulai,
                rs.tahun_akhir
            ')
            ->join('renstra_sasaran rs', 'rs.id = ris.renstra_sasaran_id')
            ->where('ris.id', $id)
            ->get()
            ->getRowArray();
    }

    // =========================================================
    // RENSTRA TARGET (INDIKATOR SASARAN)
    // =========================================================

    public function getTargetTahunanByIndikatorId($indikatorId)
    {
        return $this->db->table('renstra_target')
            ->where('renstra_indikator_id', $indikatorId)
            ->orderBy('tahun', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function getTargetTahunanByIndikatorAndYear($indikatorId, $tahun)
    {
        return $this->db->table('renstra_target')
            ->where('renstra_indikator_id', $indikatorId)
            ->where('tahun', $tahun)
            ->get()
            ->getRowArray();
    }

    // =========================================================
    // RENSTRA INDIKATOR TUJUAN
    // =========================================================

    /**
     * Get all Indikator Tujuan
     */
    public function getAllIndikatorTujuan()
    {
        return $this->db->table('renstra_indikator_tujuan rit')
            ->select('
                rit.*,
                rtj.tujuan AS nama_tujuan
            ')
            ->join('renstra_tujuan rtj', 'rtj.id = rit.tujuan_id')
            ->orderBy('rit.tujuan_id', 'ASC')
            ->orderBy('rit.id', 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * Get Indikator Tujuan by Tujuan ID
     */
    public function getIndikatorTujuanByTujuanId($tujuanId)
    {
        return $this->db->table('renstra_indikator_tujuan rit')
            ->where('rit.tujuan_id', $tujuanId)
            ->get()
            ->getResultArray();
    }

    /**
     * Get Indikator Tujuan by ID
     */
    public function getIndikatorTujuanById($id)
    {
        return $this->db->table('renstra_indikator_tujuan rit')
            ->select('
                rit.*,
                rtj.tujuan AS nama_tujuan
            ')
            ->join('renstra_tujuan rtj', 'rtj.id = rit.tujuan_id')
            ->where('rit.id', $id)
            ->get()
            ->getRowArray();
    }

    /**
     * Create Indikator Tujuan
     */
    public function createIndikatorTujuan($data)
    {
        $required = ['tujuan_id', 'indikator_tujuan'];
        foreach ($required as $field) {
            if (!isset($data[$field]) || trim($data[$field]) === '') {
                throw new \InvalidArgumentException("Field {$field} harus diisi");
            }
        }

        $insertData = [
            'tujuan_id' => $data['tujuan_id'],
            'indikator_tujuan' => trim($data['indikator_tujuan']),
        ];

        $result = $this->db->table('renstra_indikator_tujuan')->insert($insertData);
        $insertId = $this->db->insertID();

        if (!$result) {
            $error = $this->db->error();
            throw new \Exception("Failed to insert indikator tujuan: " . $error['message']);
        }

        return $insertId;
    }

    public function updateIndikatorTujuan($id, $data)
    {
        return $this->db->table('renstra_indikator_tujuan')
            ->where('id', $id)
            ->update($data);
    }

    public function deleteIndikatorTujuan($id)
    {
        $this->db->transStart();
        try {
            $this->db->table('renstra_target_tujuan')
                ->where('indikator_tujuan_id', $id)
                ->delete();

            $result = $this->db->table('renstra_indikator_tujuan')
                ->where('id', $id)
                ->delete();

            $this->db->transComplete();
            return $result;
        } catch (\Exception $e) {
            $this->db->transRollback();
            throw $e;
        }
    }

    // =========================================================
    // RENSTRA TARGET TUJUAN
    // =========================================================

    public function getTargetTujuanByIndikatorId($indikatorTujuanId)
    {
        return $this->db->table('renstra_target_tujuan')
            ->where('indikator_tujuan_id', $indikatorTujuanId)
            ->orderBy('tahun', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function getTargetTujuanByIndikatorAndYear($indikatorTujuanId, $tahun)
    {
        return $this->db->table('renstra_target_tujuan')
            ->where('indikator_tujuan_id', $indikatorTujuanId)
            ->where('tahun', $tahun)
            ->get()
            ->getRowArray();
    }

    public function createTargetTujuan($data)
    {
        $required = ['indikator_tujuan_id', 'tahun'];
        foreach ($required as $field) {
            if (!isset($data[$field]) || trim($data[$field]) === '') {
                throw new \InvalidArgumentException("Field {$field} harus diisi");
            }
        }

        $insertData = [
            'indikator_tujuan_id' => $data['indikator_tujuan_id'],
            'tahun' => $data['tahun'],
            'target_tahunan' => $data['target_tahunan'] ?? '',
        ];

        $result = $this->db->table('renstra_target_tujuan')->insert($insertData);
        $insertId = $this->db->insertID();

        if (!$result) {
            $error = $this->db->error();
            throw new \Exception("Failed to insert target tujuan: " . $error['message']);
        }

        return $insertId;
    }

    public function updateTargetTujuan($id, $data)
    {
        return $this->db->table('renstra_target_tujuan')
            ->where('id', $id)
            ->update($data);
    }

    public function deleteTargetTujuan($id)
    {
        return $this->db->table('renstra_target_tujuan')
            ->where('id', $id)
            ->delete();
    }

    public function deleteTargetTujuanByIndikatorId($indikatorTujuanId)
    {
        return $this->db->table('renstra_target_tujuan')
            ->where('indikator_tujuan_id', $indikatorTujuanId)
            ->delete();
    }
    public function getRenstraEditData(int $sasaranId, int $opdId)
    {
        // Kepala sasaran + tujuan renstra + RPJMD
        $row = $this->db->table('renstra_sasaran rs')
            ->select('
                rs.*,
                rtj.tujuan AS tujuan_renstra,
                rtj.rpjmd_sasaran_id,
                rps.sasaran_rpjmd
            ')
            ->join('renstra_tujuan rtj', 'rtj.id = rs.renstra_tujuan_id')
            ->join('rpjmd_sasaran rps', 'rps.id = rtj.rpjmd_sasaran_id', 'left')
            ->where('rs.id', $sasaranId)
            ->where('rs.opd_id', $opdId)
            ->get()
            ->getRowArray();

        if (!$row) {
            return null;
        }

        $tujuanId = (int) $row['renstra_tujuan_id'];

        // Indikator TUJUAN + target tujuan
        $indikatorTujuan = $this->getIndikatorTujuanByTujuanId($tujuanId);
        foreach ($indikatorTujuan as &$it) {
            $it['target_tahunan'] = $this->getTargetTujuanByIndikatorId($it['id']);
        }
        unset($it);

        // Indikator SASARAN + target sasaran
        $indikatorSasaran = $this->getIndikatorSasaranBySasaranId($sasaranId);
        foreach ($indikatorSasaran as &$is) {
            $is['target_tahunan'] = $this->getTargetTahunanByIndikatorId($is['id']);
        }
        unset($is);

        $row['indikator_tujuan'] = $indikatorTujuan;
        $row['indikator_sasaran'] = $indikatorSasaran;

        return $row;
    }
    public function updateRenstraFull(int $sasaranId, int $opdId, array $data)
    {
        $this->db->transStart();

        try {
            // 1. Ambil sasaran untuk dapat renstra_tujuan_id
            $sasaranRow = $this->db->table('renstra_sasaran')
                ->where('id', $sasaranId)
                ->where('opd_id', $opdId)
                ->get()
                ->getRowArray();

            if (!$sasaranRow) {
                throw new \RuntimeException('Data sasaran Renstra tidak ditemukan');
            }

            $tujuanId = (int) $sasaranRow['renstra_tujuan_id'];

            // ----------------------------------------------------
            // 2. UPDATE TUJUAN RENSTRA
            // ----------------------------------------------------
            $tujuanUpdate = [
                'rpjmd_sasaran_id' => $data['rpjmd_sasaran_id'] ?? null,
                'tujuan' => $data['tujuan_renstra'] ?? '',
            ];

            $this->db->table('renstra_tujuan')
                ->where('id', $tujuanId)
                ->update($tujuanUpdate);

            // ----------------------------------------------------
// 3. UPDATE / INSERT indikator_tujuan + target_tujuan
// ----------------------------------------------------

            if (!empty($data['indikator_tujuan']) && is_array($data['indikator_tujuan'])) {

                foreach ($data['indikator_tujuan'] as $ind) {

                    $namaIndikator = trim(
                        $ind['indikator_tujuan']
                        ?? ($ind['indikator'] ?? '')
                    );

                    if ($namaIndikator === '')
                        continue;

                    // ======================================
                    // ADA ID → UPDATE
                    // ======================================
                    if (!empty($ind['id'])) {

                        $indikatorTujuanId = (int) $ind['id'];

                        // ==========================
                        // UPDATE INDIKATOR TUJUAN
                        // ==========================
                        $this->db->table('renstra_indikator_tujuan')
                            ->where('id', $indikatorTujuanId)
                            ->update([
                                'indikator_tujuan' => $namaIndikator
                            ]);

                        // ==========================
                        // 🔥 WAJIB: DELETE TARGET LAMA LANGSUNG
                        // ==========================
                        $this->db->table('renstra_target_tujuan')
                            ->where('indikator_tujuan_id', $indikatorTujuanId)
                            ->delete();
                    }
                    // ======================================
                    // TIDAK ADA ID → INSERT BARU
                    // ======================================
                    else {

                        // CEK APAKAH SUDAH ADA DENGAN NAMA SAMA
                        $existing = $this->db->table('renstra_indikator_tujuan')
                            ->where('tujuan_id', $tujuanId)
                            ->where('indikator_tujuan', $namaIndikator)
                            ->get()
                            ->getRowArray();

                        if ($existing) {

                            $indikatorTujuanId = $existing['id'];

                            // HAPUS TARGET LAMA
                            $this->deleteTargetTujuanByIndikatorId($indikatorTujuanId);

                        } else {

                            $indikatorTujuanId = $this->createIndikatorTujuan([
                                'tujuan_id' => $tujuanId,
                                'indikator_tujuan' => $namaIndikator,
                            ]);
                        }
                    }

                    // INSERT TARGET BARU
                    if (!empty($ind['target_tahunan']) && is_array($ind['target_tahunan'])) {

                        foreach ($ind['target_tahunan'] as $t) {

                            $tahun = $t['tahun'] ?? null;
                            $nilaiTarget = $t['target']
                                ?? ($t['target_tahunan'] ?? null);

                            if (empty($tahun) || $nilaiTarget === '' || $nilaiTarget === null)
                                continue;

                            $this->createTargetTujuan([
                                'indikator_tujuan_id' => $indikatorTujuanId,
                                'tahun' => $tahun,
                                'target_tahunan' => trim($nilaiTarget),
                            ]);
                        }
                    }
                }
            }

            // Insert ulang indikator_tujuan + target_tujuan
            if (!empty($data['indikator_tujuan']) && is_array($data['indikator_tujuan'])) {
                foreach ($data['indikator_tujuan'] as $ind) {

                    // antisipasi kalau key berbeda
                    $namaIndikator = $ind['indikator_tujuan']
                        ?? ($ind['indikator'] ?? '');

                    if (trim($namaIndikator) === '') {
                        continue;
                    }

                    $indikatorTujuanId = $this->createIndikatorTujuan([
                        'tujuan_id' => $tujuanId,
                        'indikator_tujuan' => trim($namaIndikator),
                    ]);

                    if (!empty($ind['satuan'])) {
                        $this->db->table('renstra_indikator_tujuan')
                            ->where('id', $indikatorTujuanId)
                            ->update(['satuan' => $ind['satuan']]);
                    }

                    if (!empty($ind['target_tahunan']) && is_array($ind['target_tahunan'])) {
                        foreach ($ind['target_tahunan'] as $t) {

                            $tahun = $t['tahun'] ?? null;
                            // terima kedua nama: target / target_tahunan
                            $nilaiTarget = $t['target'] ?? ($t['target_tahunan'] ?? null);

                            if (empty($tahun) || $nilaiTarget === '' || $nilaiTarget === null) {
                                continue;
                            }

                            $this->createTargetTujuan([
                                'indikator_tujuan_id' => $indikatorTujuanId,
                                'tahun' => $tahun,
                                'target_tahunan' => trim($nilaiTarget),
                            ]);
                        }
                    }
                }
            }

            // ----------------------------------------------------
            // 4. UPDATE renstra_sasaran (1 baris)
            // ----------------------------------------------------

            // antisipasi nama key sasaran berbeda
            $sasaranText = $data['sasaran']
                ?? ($data['sasaran_renstra'] ?? '');

            $sasaranUpdate = [
                'opd_id' => $opdId,
                'renstra_tujuan_id' => $tujuanId,
                'sasaran' => $sasaranText,
                'status' => $data['status'] ?? 'draft',
                'tahun_mulai' => $data['tahun_mulai'],
                'tahun_akhir' => $data['tahun_akhir'],
            ];

            $this->db->table('renstra_sasaran')
                ->where('id', $sasaranId)
                ->update($sasaranUpdate);

            // ----------------------------------------------------
            // 5. RESET indikator_sasaran + target
            // ----------------------------------------------------
            $oldIndS = $this->getIndikatorSasaranBySasaranId($sasaranId);
            foreach ($oldIndS as $is) {
                $this->deleteTargetTahunanByIndikatorId($is['id']);
            }

            $this->db->table('renstra_indikator_sasaran')
                ->where('renstra_sasaran_id', $sasaranId)
                ->delete();

            // Insert ulang indikator_sasaran + target
            if (!empty($data['indikator_sasaran']) && is_array($data['indikator_sasaran'])) {
                foreach ($data['indikator_sasaran'] as $ind) {

                    // antisipasi variasi nama key
                    $namaIndikatorSasaran = $ind['indikator_sasaran']
                        ?? ($ind['indikator'] ?? null);
                    $satuan = $ind['satuan'] ?? ($ind['satuan_id'] ?? null);
                    $jenis = $ind['jenis_indikator'] ?? ($ind['jenis'] ?? '');

                    if (empty($namaIndikatorSasaran) || empty($satuan)) {
                        continue;
                    }

                    $indikatorId = $this->createIndikatorSasaran([
                        'renstra_sasaran_id' => $sasaranId,
                        'indikator_sasaran' => trim($namaIndikatorSasaran),
                        'satuan' => trim($satuan),
                        'jenis_indikator' => trim($jenis),
                    ]);

                    if (!empty($ind['target_tahunan']) && is_array($ind['target_tahunan'])) {
                        foreach ($ind['target_tahunan'] as $t) {

                            $tahun = $t['tahun'] ?? null;
                            $nilaiTarget = $t['target'] ?? ($t['target_tahunan'] ?? null);

                            if (empty($tahun) || $nilaiTarget === '' || $nilaiTarget === null) {
                                continue;
                            }

                            $this->createTargetTahunan([
                                'renstra_indikator_id' => $indikatorId,
                                'tahun' => $tahun,
                                'target' => trim($nilaiTarget),
                            ]);
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

    // =========================================================
    // COMPLETE RENSTRA STRUCTURE (SASARAN + INDIKATOR + TARGET)
    // =========================================================

    public function getCompleteRenstraStructure($opdId = null)
    {
        $query = $this->db->table('renstra_sasaran rs')
            ->select('
                rs.id AS sasaran_id,
                rs.opd_id,
                rs.renstra_tujuan_id,
                rs.sasaran,
                rs.status,
                rs.tahun_mulai,
                rs.tahun_akhir,
                o.nama_opd,
                rtj.tujuan AS renstra_tujuan,
                rps.id AS rpjmd_sasaran_id,
                rps.sasaran_rpjmd,
                ris.id AS indikator_id,
                ris.indikator_sasaran,
                ris.satuan,
                ris.jenis_indikator,
                rt.id AS target_id,
                rt.tahun,
                rt.target
            ')
            ->join('opd o', 'o.id = rs.opd_id')
            ->join('renstra_tujuan rtj', 'rtj.id = rs.renstra_tujuan_id')
            ->join('rpjmd_sasaran rps', 'rps.id = rtj.rpjmd_sasaran_id')
            ->join('renstra_indikator_sasaran ris', 'ris.renstra_sasaran_id = rs.id', 'left')
            ->join('renstra_target rt', 'rt.renstra_indikator_id = ris.id', 'left');

        if ($opdId !== null) {
            $query->where('rs.opd_id', $opdId);
        }

        $rows = $query
            ->orderBy('rs.id', 'ASC')
            ->orderBy('ris.id', 'ASC')
            ->orderBy('rt.tahun', 'ASC')
            ->get()
            ->getResultArray();

        $grouped = [];
        foreach ($rows as $row) {
            $sasaranKey = $row['sasaran_id'];

            if (!isset($grouped[$sasaranKey])) {
                $grouped[$sasaranKey] = [
                    'sasaran_id' => $row['sasaran_id'],
                    'opd_id' => $row['opd_id'],
                    'nama_opd' => $row['nama_opd'],
                    'renstra_tujuan_id' => $row['renstra_tujuan_id'],
                    'renstra_tujuan' => $row['renstra_tujuan'],
                    'rpjmd_sasaran_id' => $row['rpjmd_sasaran_id'],
                    'rpjmd_sasaran' => $row['sasaran_rpjmd'],
                    'sasaran' => $row['sasaran'],
                    'status' => $row['status'],
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
                        'jenis_indikator' => $row['jenis_indikator'],
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

        // Convert nested indikator to indexed array
        foreach ($grouped as &$g) {
            $g['indikator_sasaran'] = array_values($g['indikator_sasaran']);
        }

        return array_values($grouped);
    }

    public function getCompleteRenstraById($id, $opdId)
    {
        $rows = $this->db->table('renstra_sasaran rs')
            ->select('
                rs.id AS sasaran_id,
                rs.opd_id,
                rs.renstra_tujuan_id,
                rs.sasaran,
                rs.status,
                rs.tahun_mulai,
                rs.tahun_akhir,
                o.nama_opd,
                rtj.tujuan AS renstra_tujuan,
                rps.id AS rpjmd_sasaran_id,
                rps.sasaran_rpjmd,
                ris.id AS indikator_id,
                ris.indikator_sasaran,
                ris.satuan,
                ris.jenis_indikator,
                rt.id AS target_id,
                rt.tahun,
                rt.target
            ')
            ->join('opd o', 'o.id = rs.opd_id')
            ->join('renstra_tujuan rtj', 'rtj.id = rs.renstra_tujuan_id')
            ->join('rpjmd_sasaran rps', 'rps.id = rtj.rpjmd_sasaran_id')
            ->join('renstra_indikator_sasaran ris', 'ris.renstra_sasaran_id = rs.id', 'left')
            ->join('renstra_target rt', 'rt.renstra_indikator_id = ris.id', 'left')
            ->where('rs.id', $id)
            ->where('rs.opd_id', $opdId)
            ->orderBy('rs.id', 'ASC')
            ->orderBy('ris.id', 'ASC')
            ->orderBy('rt.tahun', 'ASC')
            ->get()
            ->getResultArray();

        if (!$rows) {
            return null;
        }

        $result = [
            'sasaran_id' => $rows[0]['sasaran_id'],
            'opd_id' => $rows[0]['opd_id'],
            'nama_opd' => $rows[0]['nama_opd'],
            'renstra_tujuan_id' => $rows[0]['renstra_tujuan_id'],
            'renstra_tujuan' => $rows[0]['renstra_tujuan'],
            'rpjmd_sasaran_id' => $rows[0]['rpjmd_sasaran_id'],
            'rpjmd_sasaran' => $rows[0]['sasaran_rpjmd'],
            'sasaran' => $rows[0]['sasaran'],
            'status' => $rows[0]['status'],
            'tahun_mulai' => $rows[0]['tahun_mulai'],
            'tahun_akhir' => $rows[0]['tahun_akhir'],
            'indikator_sasaran' => []
        ];

        foreach ($rows as $row) {
            if ($row['indikator_id']) {
                $indikatorId = $row['indikator_id'];

                if (!isset($result['indikator_sasaran'][$indikatorId])) {
                    $result['indikator_sasaran'][$indikatorId] = [
                        'indikator_id' => $indikatorId,
                        'indikator_sasaran' => $row['indikator_sasaran'],
                        'satuan' => $row['satuan'],
                        'jenis_indikator' => $row['jenis_indikator'],
                        'target_tahunan' => []
                    ];
                }

                if ($row['target_id']) {
                    $result['indikator_sasaran'][$indikatorId]['target_tahunan'][] = [
                        'target_id' => $row['target_id'],
                        'tahun' => $row['tahun'],
                        'target' => $row['target']
                    ];
                }
            }
        }

        $result['indikator_sasaran'] = array_values($result['indikator_sasaran']);

        return $result;
    }

    // =========================================================
    // FILTERED (UNTUK TABEL / INDEX)
    // =========================================================
    public function getFilteredRenstra(
        $opdId = null,
        $misi = null,
        $tujuan = null,
        $rpjmd = null,
        $status = null,
        $periode = null
    ) {
        $query = $this->db->table('renstra_sasaran rs')
            ->select('
            rs.id as sasaran_id,
            rs.sasaran,
            rs.status,
            rs.tahun_mulai,
            rs.tahun_akhir,
            o.nama_opd,
            o.singkatan,
            rps.sasaran_rpjmd,
            rm.misi as rpjmd_misi,
            rt.id  as tujuan_renstra_id,
            rt.tujuan as tujuan_renstra,
            ris.id as indikator_id,
            ris.indikator_sasaran,
            s.satuan as satuan 
            ')
            ->join('opd o', 'o.id = rs.opd_id')

            // Tujuan Renstra
            ->join('renstra_tujuan rt', 'rt.id = rs.renstra_tujuan_id', 'left')

            // Sasaran RPJMD (via renstra_tujuan)
            ->join('rpjmd_sasaran rps', 'rps.id = rt.rpjmd_sasaran_id', 'left')

            // Tujuan & Misi RPJMD (untuk filter misi)
            ->join('rpjmd_tujuan rtj', 'rtj.id = rps.tujuan_id', 'left')
            ->join('rpjmd_misi rm', 'rm.id = rtj.misi_id', 'left')

            // Indikator Sasaran Renstra
            ->join('renstra_indikator_sasaran ris', 'ris.renstra_sasaran_id = rs.id', 'left')

            ->join('satuan s', 's.id = ris.satuan', 'left');

        // Filter OPD
        if ($opdId !== null) {
            $query->where('rs.opd_id', $opdId);
        }

        // 🔹 Filter Misi RPJMD
        if (!empty($misi)) {
            $query->like('rm.misi', $misi);
        }

        // Filter Tujuan Renstra
        if (!empty($tujuan)) {
            $query->like('rt.tujuan', $tujuan);
        }

        // Filter Sasaran RPJMD
        if (!empty($rpjmd)) {
            $query->like('rps.sasaran_rpjmd', $rpjmd);
        }

        // Filter Status
        if (!empty($status)) {
            $query->where('rs.status', $status);
        }

        // Filter Periode "2025-2029"
        if (!empty($periode)) {
            [$start, $end] = explode('-', $periode);
            $query->where('rs.tahun_mulai >=', (int) $start);
            $query->where('rs.tahun_akhir <=', (int) $end);
        }

        $indikatorData = $query
            ->orderBy('rs.id', 'ASC')
            ->orderBy('ris.id', 'ASC') // indikator sasaran
            ->get()
            ->getResultArray();

        foreach ($indikatorData as &$row) {
            if (!empty($row['indikator_id'])) {
                $targets = $this->getTargetTahunanByIndikatorId($row['indikator_id']);
                $row['targets'] = [];
                foreach ($targets as $t) {
                    $row['targets'][$t['tahun']] = $t['target'];
                }
            } else {
                $row['targets'] = [];
            }
        }
        unset($row);

        $tujuanTargetsCache = [];

        foreach ($indikatorData as &$row) {

            $tid = $row['tujuan_renstra_id'] ?? null;

            if (!isset($tujuanTargetsCache[$tid])) {

                $its = $this->getIndikatorTujuanByTujuanId($tid);

                foreach ($its as &$it) {
                    $targetsT = $this->getTargetTujuanByIndikatorId($it['id']);
                    $it['targets'] = [];

                    foreach ($targetsT as $t) {
                        $it['targets'][$t['tahun']] = $t['target_tahunan'];
                    }
                }

                $tujuanTargetsCache[$tid] = $its;
            }

            $row['indikator_tujuan_list'] = $tujuanTargetsCache[$tid];
        }
        unset($row);

        $tree = [];

        foreach ($indikatorData as $row) {

            $tid = $row['tujuan_renstra_id'];
            $sid = $row['sasaran_id'];
            $iid = $row['indikator_id'];

            // LEVEL TUJUAN
            if (!isset($tree[$tid])) {
                $tree[$tid] = [
                    'tujuan_renstra_id' => $tid,
                    'sasaran_rpjmd' => $row['sasaran_rpjmd'],
                    'tujuan' => $row['tujuan_renstra'],
                    'indikator_tujuan' => $row['indikator_tujuan_list'],
                    'sasaran' => []
                ];
            }

            // LEVEL SASARAN
            if (!isset($tree[$tid]['sasaran'][$sid])) {
                $tree[$tid]['sasaran'][$sid] = [
                    'sasaran_id' => $sid,
                    'sasaran' => $row['sasaran'],
                    'status' => $row['status'],
                    'indikator' => []
                ];
            }

            // LEVEL INDIKATOR SASARAN
            if (!empty($iid)) {
                $tree[$tid]['sasaran'][$sid]['indikator'][$iid] = [
                    'indikator' => $row['indikator_sasaran'],
                    'satuan' => $row['satuan'],
                    'targets' => $row['targets']
                ];
            }
        }
        return array_values($tree);
    }


    public function getAllRenstra($opdId = null)
    {
        $query = $this->db->table('renstra_sasaran rs')
            ->select('
                rs.id AS sasaran_id,
                rs.sasaran,
                rs.status,
                rs.tahun_mulai,
                rs.tahun_akhir,
                o.nama_opd,
                o.singkatan,
                rtj_r.tujuan AS renstra_tujuan,
                rps.sasaran_rpjmd AS rpjmd_sasaran,
                rtj.tujuan_rpjmd AS rpjmd_tujuan,
                rm.misi AS rpjmd_misi,
                ris.id AS indikator_id,
                ris.indikator_sasaran,
                ris.satuan,
                ris.jenis_indikator
            ')
            ->join('opd o', 'o.id = rs.opd_id')
            ->join('renstra_tujuan rtj_r', 'rtj_r.id = rs.renstra_tujuan_id')
            ->join('rpjmd_sasaran rps', 'rps.id = rtj_r.rpjmd_sasaran_id', 'left')
            ->join('rpjmd_tujuan rtj', 'rtj.id = rps.tujuan_id', 'left')
            ->join('rpjmd_misi rm', 'rm.id = rtj.misi_id', 'left')
            ->join('renstra_indikator_sasaran ris', 'ris.renstra_sasaran_id = rs.id');

        if ($opdId !== null) {
            $query->where('rs.opd_id', $opdId);
        }

        $indikatorData = $query
            ->orderBy('rs.id', 'ASC')
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

    // =========================================================
    // CRUD SASARAN
    // =========================================================

    public function createSasaran($data)
    {
        // renstra_tujuan_id sekarang wajib, bukan rpjmd_sasaran_id
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

    public function updateSasaran($id, $data)
    {
        return $this->db->table('renstra_sasaran')
            ->where('id', $id)
            ->update($data);
    }

    public function deleteSasaran($id)
    {
        $this->db->transStart();
        try {
            $indikatorList = $this->getIndikatorSasaranBySasaranId($id);
            foreach ($indikatorList as $indikator) {
                $this->deleteIndikatorSasaran($indikator['id']);
            }

            $result = $this->db->table('renstra_sasaran')->delete(['id' => $id]);

            $this->db->transComplete();
            return $result;
        } catch (\Exception $e) {
            $this->db->transRollback();
            throw $e;
        }
    }

    // =========================================================
    // CRUD INDIKATOR SASARAN
    // =========================================================

    public function createIndikatorSasaran($data)
    {
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
            'jenis_indikator' => $data['jenis_indikator'] ?? '',
        ];

        $result = $this->db->table('renstra_indikator_sasaran')->insert($insertData);
        $insertId = $this->db->insertID();

        if (!$result) {
            $error = $this->db->error();
            throw new \Exception("Failed to insert indikator sasaran: " . $error['message']);
        }

        return $insertId;
    }

    public function updateIndikatorSasaran($id, $data)
    {
        return $this->db->table('renstra_indikator_sasaran')
            ->where('id', $id)
            ->update($data);
    }

    public function deleteIndikatorSasaran($id)
    {
        $this->db->transStart();
        try {
            $this->db->table('renstra_target')
                ->delete(['renstra_indikator_id' => $id]);

            $result = $this->db->table('renstra_indikator_sasaran')
                ->delete(['id' => $id]);

            $this->db->transComplete();
            return $result;
        } catch (\Exception $e) {
            $this->db->transRollback();
            throw $e;
        }
    }

    // =========================================================
    // CRUD TARGET SASARAN
    // =========================================================

    public function createTargetTahunan($data)
    {
        $required = ['renstra_indikator_id', 'tahun'];
        foreach ($required as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                throw new \InvalidArgumentException("Field {$field} harus diisi");
            }
        }

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

    public function updateTargetTahunan($id, $data)
    {
        return $this->db->table('renstra_target')
            ->where('id', $id)
            ->update($data);
    }

    public function deleteTargetTahunan($id)
    {
        return $this->db->table('renstra_target')
            ->where('id', $id)
            ->delete();
    }

    public function deleteTargetTahunanByIndikatorId($indikatorId)
    {
        return $this->db->table('renstra_target')
            ->where('renstra_indikator_id', $indikatorId)
            ->delete();
    }

    // =========================================================
    // COMPLETE CREATE / UPDATE / DELETE RENSTRA
    // =========================================================

    public function createCompleteRenstra($data)
    {
        $this->db->transStart();
        try {
            $sasaranIds = [];

            if (empty($data['sasaran_renstra']) || !is_array($data['sasaran_renstra'])) {
                throw new \Exception('Data sasaran_renstra tidak valid atau kosong');
            }

            foreach ($data['sasaran_renstra'] as $index => $sasaranItem) {

                if (empty($sasaranItem['sasaran'])) {
                    throw new \Exception("Sasaran pada index {$index} tidak boleh kosong");
                }

                $sasaranData = [
                    'opd_id' => $data['opd_id'],
                    'renstra_tujuan_id' => $data['renstra_tujuan_id'],
                    'sasaran' => trim($sasaranItem['sasaran']),
                    'status' => $data['status'] ?? 'draft',
                    'tahun_mulai' => $data['tahun_mulai'],
                    'tahun_akhir' => $data['tahun_akhir'],
                ];

                $sasaranId = $this->createSasaran($sasaranData);
                if (!$sasaranId) {
                    throw new \Exception("Gagal menyimpan sasaran pada index {$index}");
                }

                $sasaranIds[] = $sasaranId;

                if (isset($sasaranItem['indikator_sasaran']) && is_array($sasaranItem['indikator_sasaran'])) {
                    foreach ($sasaranItem['indikator_sasaran'] as $indikatorIndex => $indikator) {

                        if (empty($indikator['indikator_sasaran'])) {
                            throw new \Exception("Indikator sasaran pada sasaran {$index}, indikator {$indikatorIndex} tidak boleh kosong");
                        }

                        $indikatorData = [
                            'renstra_sasaran_id' => $sasaranId,
                            'indikator_sasaran' => trim($indikator['indikator_sasaran']),
                            'satuan' => trim($indikator['satuan'] ?? ''),
                            'basis_target' => trim($indikator['basis_target'] ?? ''),
                            'jenis_indikator' => trim($indikator['jenis_indikator'] ?? ''),
                        ];

                        $indikatorId = $this->createIndikatorSasaran($indikatorData);
                        if (!$indikatorId) {
                            throw new \Exception("Gagal menyimpan indikator pada sasaran {$index}, indikator {$indikatorIndex}");
                        }

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

                                $targetId = $this->createTargetTahunan($targetData);
                                if (!$targetId) {
                                    throw new \Exception("Gagal menyimpan target pada sasaran {$index}, indikator {$indikatorIndex}, target {$targetIndex}");
                                }
                            }
                        }
                    }
                }
            }

            $this->db->transComplete();

            if ($this->db->transStatus() === false) {
                throw new \Exception('Transaction gagal, data tidak tersimpan');
            }

            return $sasaranIds;
        } catch (\Exception $e) {
            $this->db->transRollback();
            log_message('error', 'Error in createCompleteRenstra: ' . $e->getMessage());
            throw $e;
        }
    }

    public function updateCompleteRenstra($sasaranId, $data)
    {
        $this->db->transStart();
        try {
            $sasaranData = [
                'opd_id' => $data['opd_id'],
                'renstra_tujuan_id' => $data['renstra_tujuan_id'],
                'sasaran' => $data['sasaran'],
                'tahun_mulai' => $data['tahun_mulai'],
                'tahun_akhir' => $data['tahun_akhir']
            ];

            $this->db->table('renstra_sasaran')
                ->where('id', $sasaranId)
                ->update($sasaranData);

            $existingIndikator = $this->getIndikatorSasaranBySasaranId($sasaranId);
            foreach ($existingIndikator as $indikator) {
                $this->deleteTargetTahunanByIndikatorId($indikator['id']);
            }

            $this->db->table('renstra_indikator_sasaran')
                ->where('renstra_sasaran_id', $sasaranId)
                ->delete();

            if (isset($data['indikator_sasaran']) && is_array($data['indikator_sasaran'])) {
                foreach ($data['indikator_sasaran'] as $indikator) {
                    if (!is_array($indikator) || empty($indikator)) {
                        continue;
                    }

                    if (
                        !isset($indikator['indikator_sasaran']) || empty($indikator['indikator_sasaran']) ||
                        !isset($indikator['satuan']) || empty($indikator['satuan'])
                    ) {
                        continue;
                    }

                    $indikatorData = [
                        'renstra_sasaran_id' => $sasaranId,
                        'indikator_sasaran' => $indikator['indikator_sasaran'],
                        'satuan' => $indikator['satuan'],
                        'baseline' => $indikator['baseline'] ?? '',
                        'jenis_indikator' => $indikator['jenis_indikator'] ?? '',
                    ];

                    $this->db->table('renstra_indikator_sasaran')->insert($indikatorData);
                    $indikatorId = $this->db->insertID();

                    if (isset($indikator['target_tahunan']) && is_array($indikator['target_tahunan'])) {
                        foreach ($indikator['target_tahunan'] as $target) {
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

                            $this->db->table('renstra_target')->insert($targetData);
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

    public function deleteCompleteRenstra($sasaranId)
    {
        $this->db->transStart();

        try {
            // ==========================
            // 1. Hapus RENJA (kalau ada)
            // ==========================
            if ($this->db->tableExists('renja_sasaran')) {

                $queryRenja = $this->db->table('renja_sasaran')
                    ->where('renstra_sasaran_id', $sasaranId)
                    ->get();

                // Kalau query sukses baru ambil result
                if ($queryRenja !== false) {
                    $renjaSasaranList = $queryRenja->getResultArray();

                    // Hapus indikator RENJA kalau tabelnya juga ada
                    if (!empty($renjaSasaranList) && $this->db->tableExists('renja_indikator_sasaran')) {
                        foreach ($renjaSasaranList as $renjaSasaran) {
                            $this->db->table('renja_indikator_sasaran')
                                ->where('renja_sasaran_id', $renjaSasaran['id'])
                                ->delete();
                        }
                    }

                    // Hapus renja_sasaran yang mengacu ke renstra ini
                    $this->db->table('renja_sasaran')
                        ->where('renstra_sasaran_id', $sasaranId)
                        ->delete();
                }
            }

            // ==================================
            // 2. Hapus indikator & target RENSTRA
            // ==================================
            $indikatorList = $this->getIndikatorSasaranBySasaranId($sasaranId);

            foreach ($indikatorList as $indikator) {
                // hapus semua target di renstra_target
                $this->deleteTargetTahunanByIndikatorId($indikator['id']);
            }

            // hapus indikator sasaran renstra
            $this->db->table('renstra_indikator_sasaran')
                ->where('renstra_sasaran_id', $sasaranId)
                ->delete();

            // hapus sasaran renstra
            $this->db->table('renstra_sasaran')
                ->where('id', $sasaranId)
                ->delete();

            $this->db->transComplete();
            return $this->db->transStatus() !== false;
        } catch (\Exception $e) {
            $this->db->transRollback();
            throw $e;
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

    // =========================================================
    // UNTUK TABEL SASARAN + INDIKATOR + TARGET (VIEW)
    // =========================================================

    public function getAllSasaranWithIndikatorAndTarget($opdId = null, $tahun = null)
    {
        $builder = $this->db->table('renstra_sasaran rs')
            ->select('
                rs.*,
                rs.sasaran,
                o.nama_opd,
                rtj.tujuan AS renstra_tujuan,
                rps.sasaran_rpjmd AS sasaran_rpjmd
            ')
            ->join('opd o', 'o.id = rs.opd_id')
            ->join('renstra_tujuan rtj', 'rtj.id = rs.renstra_tujuan_id')
            ->join('rpjmd_sasaran rps', 'rps.id = rtj.rpjmd_sasaran_id', 'left')
            ->orderBy('rs.tahun_mulai', 'ASC')
            ->orderBy('rs.id', 'ASC');

        if ($opdId !== null) {
            $builder->where('rs.opd_id', $opdId);
        }

        $sasaranList = $builder->get()->getResultArray();

        foreach ($sasaranList as &$sasaran) {
            $indikatorList = $this->db->table('renstra_indikator_sasaran ri')
                ->select('ri.id, ri.indikator_sasaran, ri.satuan, ri.jenis_indikator')
                ->where('ri.renstra_sasaran_id', $sasaran['id'])
                ->orderBy('ri.id', 'ASC')
                ->get()
                ->getResultArray();

            foreach ($indikatorList as &$indikator) {
                $targetQuery = $this->db->table('renstra_target rt')
                    ->select('rt.tahun, rt.target')
                    ->where('rt.renstra_indikator_id', $indikator['id']);

                if ($tahun) {
                    $targetQuery->where('rt.tahun', $tahun);
                }

                $targetData = $targetQuery->get()->getResultArray();

                $indikator['target_tahunan'] = (!empty($targetData))
                    ? $targetData
                    : [['tahun' => $tahun, 'target' => null]];
            }

            $sasaran['indikator_sasaran'] = $indikatorList;
        }

        return $sasaranList;
    }
    public function getCompleteTujuan($tujuanId, $opdId)
    {
        $tujuan = $this->db->table('renstra_tujuan')
            ->where('id', $tujuanId)
            ->get()
            ->getRowArray();

        if (!$tujuan)
            return null;

        // ==============================
        // INDIKATOR TUJUAN + TARGET
        // ==============================

        $indikatorTujuan = $this->db->table('renstra_indikator_tujuan')
            ->where('tujuan_id', $tujuanId)
            ->get()
            ->getResultArray();

        foreach ($indikatorTujuan as &$it) {

            $targets = $this->db->table('renstra_target_tujuan')
                ->where('indikator_tujuan_id', $it['id'])
                ->orderBy('tahun', 'ASC')
                ->get()
                ->getResultArray();

            $it['targets'] = $targets;
        }

        // ==============================
        // SASARAN
        // ==============================

        $sasaranList = $this->db->table('renstra_sasaran')
            ->where('renstra_tujuan_id', $tujuanId)
            ->where('opd_id', $opdId)
            ->get()
            ->getResultArray();

        foreach ($sasaranList as &$s) {

            $indikatorS = $this->db->table('renstra_indikator_sasaran')
                ->where('renstra_sasaran_id', $s['id'])
                ->get()
                ->getResultArray();

            foreach ($indikatorS as &$is) {

                $targets = $this->db->table('renstra_target')
                    ->where('renstra_indikator_id', $is['id'])
                    ->orderBy('tahun', 'ASC')
                    ->get()
                    ->getResultArray();

                $is['targets'] = $targets;
            }

            $s['indikator'] = $indikatorS;
        }

        return [
            'tujuan' => $tujuan,
            'indikator_tujuan' => $indikatorTujuan,
            'sasaran' => $sasaranList
        ];
    }
    public function patchTargetTujuan($indikatorId, $targets)
    {
        $old = $this->db->table('renstra_target_tujuan')
            ->where('indikator_tujuan_id', $indikatorId)
            ->get()
            ->getResultArray();

        $oldYears = array_column($old, 'tahun');

        $formYears = [];

        foreach ($targets as $t) {

            $tahun = $t['tahun'] ?? null;
            $target = $t['target'] ?? null;

            if (!$tahun || $target === null || $target === '')
                continue;

            $formYears[] = $tahun;

            $exists = $this->db->table('renstra_target_tujuan')
                ->where('indikator_tujuan_id', $indikatorId)
                ->where('tahun', $tahun)
                ->get()
                ->getRow();

            if ($exists) {
                $this->db->table('renstra_target_tujuan')
                    ->where('indikator_tujuan_id', $indikatorId)
                    ->where('tahun', $tahun)
                    ->update([
                        'target_tahunan' => $target
                    ]);
            } else {
                $this->db->table('renstra_target_tujuan')
                    ->insert([
                        'indikator_tujuan_id' => $indikatorId,
                        'tahun' => $tahun,
                        'target_tahunan' => $target
                    ]);
            }
        }

        // DELETE removed
        $toDelete = array_diff($oldYears, $formYears);

        if (!empty($toDelete)) {
            $this->db->table('renstra_target_tujuan')
                ->where('indikator_tujuan_id', $indikatorId)
                ->whereIn('tahun', $toDelete)
                ->delete();
        }
    }

    public function updateCompleteTujuan(int $tujuanId, array $post)
    {
        $db = $this->db;
        $db->transStart();

        try {

            $tahunMulai = (int) $post['tahun_mulai'];
            $tahunAkhir = (int) $post['tahun_akhir'];

            // =========================================================
            // 1. UPDATE TUJUAN
            // =========================================================
            $db->table('renstra_tujuan')
                ->where('id', $tujuanId)
                ->update([
                    'rpjmd_sasaran_id' => $post['rpjmd_sasaran_id'],
                    'tujuan' => $post['tujuan_renstra']
                ]);

            // =========================================================
            // 2. INDIKATOR TUJUAN (SYNC)
            // =========================================================

            $postedIndIds = [];

            if (!empty($post['indikator_tujuan'])) {

                foreach ($post['indikator_tujuan'] as $it) {

                    if (empty($it['indikator_tujuan']))
                        continue;

                    // ======================
                    // UPDATE
                    // ======================
                    if (!empty($it['id'])) {

                        $indikatorId = (int) $it['id'];

                        $db->table('renstra_indikator_tujuan')
                            ->where('id', $indikatorId)
                            ->update([
                                'indikator_tujuan' => $it['indikator_tujuan']
                            ]);
                    }
                    // ======================
                    // INSERT
                    // ======================
                    else {

                        $db->table('renstra_indikator_tujuan')
                            ->insert([
                                'tujuan_id' => $tujuanId,
                                'indikator_tujuan' => $it['indikator_tujuan']
                            ]);

                        $indikatorId = $db->insertID();
                    }

                    $postedIndIds[] = $indikatorId;

                    // ======================
                    // DELETE TARGET LAMA
                    // ======================
                    $db->table('renstra_target_tujuan')
                        ->where('indikator_tujuan_id', $indikatorId)
                        ->delete();

                    // ======================
                    // INSERT TARGET BARU (FILTER PERIODE)
                    // ======================
                    if (!empty($it['target_tahunan'])) {

                        foreach ($it['target_tahunan'] as $t) {

                            $tahun = (int) ($t['tahun'] ?? 0);

                            if ($tahun < $tahunMulai || $tahun > $tahunAkhir)
                                continue;

                            $target = $t['target'] ?? $t['target_tahunan'] ?? null;

                            if ($target === '' || $target === null)
                                continue;

                            $db->table('renstra_target_tujuan')
                                ->insert([
                                    'indikator_tujuan_id' => $indikatorId,
                                    'tahun' => $tahun,
                                    'target_tahunan' => $target
                                ]);
                        }
                    }
                }
            }

            // ======================
            // DELETE INDIKATOR YANG DIHAPUS DI FORM
            // ======================
            if (!empty($postedIndIds)) {

                $db->table('renstra_indikator_tujuan')
                    ->where('tujuan_id', $tujuanId)
                    ->whereNotIn('id', $postedIndIds)
                    ->delete();
            }

            // =========================================================
            // 3. SASARAN (SYNC)
            // =========================================================

            $postedSasaranIds = [];

            if (!empty($post['sasaran_renstra'])) {
                $sasaranList = array_values($post['sasaran_renstra']);

                foreach ($sasaranList as $sr) {

                    $sasaranText = $sr['sasaran'] ?? null;
                    if (empty($sr['id']) && empty($sasaranText)) {
                        continue;
                    }

                    if (!empty($sr['id'])) {
                        $sasaranId = (int) $sr['id'];

                        $db->table('renstra_sasaran')
                            ->where('id', $sasaranId)
                            ->update([
                                'sasaran' => $sr['sasaran'],
                                'tahun_mulai' => $tahunMulai,
                                'tahun_akhir' => $tahunAkhir
                            ]);

                        $postedSasaranIds[] = $sasaranId;

                    } else {
                        $db->table('renstra_sasaran')->insert([
                            'renstra_tujuan_id' => $tujuanId,
                            'opd_id' => session()->get('opd_id'),
                            'sasaran' => $sr['sasaran'],
                            'tahun_mulai' => $tahunMulai,
                            'tahun_akhir' => $tahunAkhir,
                            'status' => 'selesai'
                        ]);

                        $sasaranId = $db->insertID();

                        $postedSasaranIds[] = $sasaranId;
                    }

                    $postedIndSIds = [];

                    // =========================
                    // LOOP UPDATE / INSERT
                    // =========================
                    if (!empty($sr['indikator_sasaran'])) {
                        $indikatorList = array_values($sr['indikator_sasaran']);


                        foreach ($indikatorList as $ind) {

                            $idInd = $ind['id'] ?? null;
                            $nama = $ind['indikator_sasaran'] ?? null;
                            $satuan = $ind['satuan'] ?? null;
                            $jenis = $ind['jenis_indikator'] ?? null;


                            if (!empty($idInd)) {

                                $indSasaranId = (int) $idInd;

                                $db->table('renstra_indikator_sasaran')
                                    ->where('id', $indSasaranId)
                                    ->update([
                                        'indikator_sasaran' => $nama,
                                        'satuan' => $satuan,
                                        'jenis_indikator' => $jenis
                                    ]);
                            } else {

                                $db->table('renstra_indikator_sasaran')
                                    ->insert([
                                        'renstra_sasaran_id' => $sasaranId,
                                        'indikator_sasaran' => $nama,
                                        'satuan' => $satuan,
                                        'jenis_indikator' => $jenis
                                    ]);

                                $indSasaranId = $db->insertID();
                            }

                            $postedIndSIds[] = $indSasaranId;
                            // ======================
// DELETE TARGET LAMA
// ======================
                            $db->table('renstra_target')
                                ->where('renstra_indikator_id', $indSasaranId)
                                ->delete();

                            // ======================
// INSERT TARGET BARU
// ======================
                            if (!empty($ind['target_tahunan'])) {

                                $targets = array_values($ind['target_tahunan']);

                                foreach ($targets as $t) {

                                    $tahun = (int) ($t['tahun'] ?? 0);

                                    if ($tahun < $tahunMulai || $tahun > $tahunAkhir)
                                        continue;

                                    $target = $t['target'] ?? null;

                                    if ($target === '' || $target === null)
                                        continue;

                                    $db->table('renstra_target')
                                        ->insert([
                                            'renstra_indikator_id' => $indSasaranId,
                                            'tahun' => $tahun,
                                            'target' => $target
                                        ]);
                                }
                            }
                        }
                    }

                    // =========================
                    // 🔥 DELETE SETELAH LOOP
                    // =========================
                    if (!empty($postedIndSIds)) {

                        $db->table('renstra_indikator_sasaran')
                            ->where('renstra_sasaran_id', $sasaranId)
                            ->whereNotIn('id', $postedIndSIds)
                            ->delete();
                    }
                }
            }

            // ======================
            // DELETE SASARAN YANG DIHAPUS DI FORM
            // ======================
            if (!empty($postedSasaranIds)) {

                $db->table('renstra_sasaran')
                    ->where('renstra_tujuan_id', $tujuanId)
                    ->whereNotIn('id', $postedSasaranIds)
                    ->delete();
            }


            $db->transComplete();
            return $db->transStatus();

        } catch (\Exception $e) {

            $db->transRollback();
            throw $e;
        }
    }
}
