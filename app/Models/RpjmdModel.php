<?php

namespace App\Models;

use CodeIgniter\Model;

class RpjmdModel extends Model
{
    protected $db;

    public function __construct()
    {
        parent::__construct();
        $this->db = \Config\Database::connect();
    }

    // ==================== HELPER JENIS INDIKATOR ====================

    /**
     * Normalisasi nilai jenis_indikator supaya konsisten:
     * - 'indikator negatif' atau 'negatif' => 'indikator negatif'
     * - selain itu => 'indikator positif'
     */
    private function normalizeJenisIndikator(?string $raw): string
    {
        $raw = strtolower(trim($raw ?? ''));

        if ($raw === 'indikator negatif' || $raw === 'negatif') {
            return 'indikator negatif';
        }

        // default ke positif
        return 'indikator positif';
    }

    // ==================== RPJMD MISI ====================

    public function getAllMisi()
    {
        return $this->db->table('rpjmd_misi')
            ->orderBy('tahun_mulai', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function getMisiById($id)
    {
        return $this->db->table('rpjmd_misi')
            ->where('id', $id)
            ->get()
            ->getRowArray();
    }

    public function getMisiByYear($tahun)
    {
        return $this->db->table('rpjmd_misi')
            ->where('tahun_mulai <=', $tahun)
            ->where('tahun_akhir >=', $tahun)
            ->get()
            ->getResultArray();
    }

    public function getAllMisiByStatus($status = null)
    {
        $qb = $this->db->table('rpjmd_misi');
        if ($status !== null) {
            $qb->where('status', $status);
        }
        return $qb->orderBy('tahun_mulai', 'ASC')->get()->getResultArray();
    }

    public function getCompletedMisi()
    {
        return $this->db->table('rpjmd_misi')
            ->where('status', 'selesai')
            ->orderBy('tahun_mulai', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function updateMisiStatus($id, $status)
    {
        if (!in_array($status, ['draft', 'selesai'])) {
            throw new \InvalidArgumentException("Status harus 'draft' atau 'selesai'");
        }
        return $this->db->table('rpjmd_misi')
            ->where('id', $id)
            ->update([
                'status' => $status,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
    }

    // ==================== RPJMD TUJUAN ====================

    public function getAllTujuan()
    {
        return $this->db->table('rpjmd_tujuan t')
            ->select('t.*, m.misi, m.tahun_mulai, m.tahun_akhir')
            ->join('rpjmd_misi m', 'm.id = t.misi_id')
            ->orderBy('t.misi_id', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function getTujuanByMisiId($misiId)
    {
        return $this->db->table('rpjmd_tujuan')
            ->where('misi_id', $misiId)
            ->get()
            ->getResultArray();
    }

    public function getTujuanById($id)
    {
        return $this->db->table('rpjmd_tujuan t')
            ->select('t.*, m.misi, m.tahun_mulai, m.tahun_akhir')
            ->join('rpjmd_misi m', 'm.id = t.misi_id')
            ->where('t.id', $id)
            ->get()
            ->getRowArray();
    }

    // ==================== RPJMD INDIKATOR TUJUAN ====================

    public function getIndikatorTujuanByTujuanId($tujuanId)
    {
        return $this->db->table('rpjmd_indikator_tujuan')
            ->where('tujuan_id', $tujuanId)
            ->get()
            ->getResultArray();
    }

    // Target Tahunan untuk Indikator Tujuan (rpjmd_target_tujuan)
    public function getTargetTahunanTujuanByIndikatorId($indikatorTujuanId)
    {
        return $this->db->table('rpjmd_target_tujuan')
            ->where('indikator_tujuan_id', $indikatorTujuanId)
            ->orderBy('tahun', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function createTargetTahunanTujuan($data)
    {
        // required: indikator_tujuan_id, tahun; target_tahunan opsional
        if (empty($data['indikator_tujuan_id']) || empty($data['tahun'])) {
            throw new \InvalidArgumentException("indikator_tujuan_id dan tahun harus diisi");
        }
        $insert = [
            'indikator_tujuan_id' => (int) $data['indikator_tujuan_id'],
            'tahun' => (int) $data['tahun'],
            'target_tahunan' => (string) ($data['target_tahunan'] ?? ''),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        $ok = $this->db->table('rpjmd_target_tujuan')->insert($insert);
        if (!$ok) {
            $err = $this->db->error();
            throw new \Exception("Gagal insert target_tujuan: " . ($err['message'] ?? 'unknown'));
        }
        return (int) $this->db->insertID();
    }

    public function updateTargetTahunanTujuan($id, $data)
    {
        return $this->db->table('rpjmd_target_tujuan')
            ->where('id', (int) $id)
            ->update([
                'tahun' => isset($data['tahun']) ? (int) $data['tahun'] : null,
                'target_tahunan' => $data['target_tahunan'] ?? null,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
    }

    public function deleteTargetTahunanTujuan($id)
    {
        return $this->db->table('rpjmd_target_tujuan')->delete(['id' => (int) $id]);
    }

    public function deleteTargetTahunanTujuanByIndikatorId($indikatorTujuanId)
    {
        return $this->db->table('rpjmd_target_tujuan')
            ->where('indikator_tujuan_id', (int) $indikatorTujuanId)
            ->delete();
    }

    // ==================== RPJMD SASARAN ====================

    public function getAllSasaran()
    {
        return $this->db->table('rpjmd_sasaran s')
            ->select('s.*, t.tujuan_rpjmd, m.misi')
            ->join('rpjmd_tujuan t', 't.id = s.tujuan_id')
            ->join('rpjmd_misi m', 'm.id = t.misi_id')
            ->orderBy('s.tujuan_id', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function getAllSasaranFromCompletedMisi()
    {
        return $this->db->table('rpjmd_sasaran s')
            ->select('s.*, t.tujuan_rpjmd, m.misi, m.tahun_mulai, m.tahun_akhir')
            ->join('rpjmd_tujuan t', 't.id = s.tujuan_id')
            ->join('rpjmd_misi m', 'm.id = t.misi_id')
            ->where('m.status', 'selesai')
            ->orderBy('s.tujuan_id', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function getSasaranByTujuanId($tujuanId)
    {
        return $this->db->table('rpjmd_sasaran')
            ->where('tujuan_id', $tujuanId)
            ->get()
            ->getResultArray();
    }

    public function getSasaranById($id)
    {
        return $this->db->table('rpjmd_sasaran s')
            ->select('s.*, t.tujuan_rpjmd, m.misi')
            ->join('rpjmd_tujuan t', 't.id = s.tujuan_id')
            ->join('rpjmd_misi m', 'm.id = t.misi_id')
            ->where('s.id', $id)
            ->get()
            ->getRowArray();
    }

    public function getAllSasaranWithPeriode()
    {
        return $this->db->table('rpjmd_sasaran s')
            ->select('s.id, s.sasaran_rpjmd, m.tahun_mulai, m.tahun_akhir')
            ->join('rpjmd_tujuan t', 't.id = s.tujuan_id')
            ->join('rpjmd_misi m', 'm.id = t.misi_id')
            ->orderBy('s.id', 'ASC')
            ->get()
            ->getResultArray();
    }

    // ==================== RPJMD INDIKATOR SASARAN ====================

    public function getAllIndikatorSasaran()
    {
        return $this->db->table('rpjmd_indikator_sasaran iss')
            ->select('iss.*, s.sasaran_rpjmd, t.tujuan_rpjmd, m.misi')
            ->join('rpjmd_sasaran s', 's.id = iss.sasaran_id')
            ->join('rpjmd_tujuan t', 't.id = s.tujuan_id')
            ->join('rpjmd_misi m', 'm.id = t.misi_id')
            ->orderBy('iss.sasaran_id', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function getIndikatorSasaranBySasaranId($sasaranId)
    {
        return $this->db->table('rpjmd_indikator_sasaran')
            ->where('sasaran_id', $sasaranId)
            ->get()
            ->getResultArray();
    }

    public function getIndikatorSasaranById($id)
    {
        return $this->db->table('rpjmd_indikator_sasaran iss')
            ->select('iss.*, s.sasaran_rpjmd, t.tujuan_rpjmd, m.misi')
            ->join('rpjmd_sasaran s', 's.id = iss.sasaran_id')
            ->join('rpjmd_tujuan t', 't.id = s.tujuan_id')
            ->join('rpjmd_misi m', 'm.id = t.misi_id')
            ->where('iss.id', $id)
            ->get()
            ->getRowArray();
    }

    // ==================== RPJMD TARGET TAHUNAN (Indikator Sasaran) ====================

    public function getTargetTahunanByIndikatorId($indikatorId)
    {
        return $this->db->table('rpjmd_target')
            ->where('indikator_sasaran_id', $indikatorId)
            ->orderBy('tahun', 'ASC')
            ->get()
            ->getResultArray();
    }

    // ==================== COMPREHENSIVE DATA LOADING ====================

    public function getCompleteRpjmdStructure()
    {
        $misiList = $this->getAllMisi();

        foreach ($misiList as &$misi) {
            $misi['tujuan'] = $this->getTujuanByMisiId($misi['id']);

            if (!empty($misi['tujuan'])) {
                foreach ($misi['tujuan'] as &$tujuan) {
                    // indikator_tujuan + target_tahunan_tujuan
                    $tujuan['indikator_tujuan'] = $this->getIndikatorTujuanByTujuanId($tujuan['id']) ?? [];
                    if (!empty($tujuan['indikator_tujuan'])) {
                        foreach ($tujuan['indikator_tujuan'] as &$it) {
                            $it['target_tahunan_tujuan'] = $this->getTargetTahunanTujuanByIndikatorId($it['id']) ?? [];
                        }
                    }

                    // sasaran -> indikator_sasaran -> target_tahunan (sasaran)
                    $tujuan['sasaran'] = $this->getSasaranByTujuanId($tujuan['id']);
                    if (!empty($tujuan['sasaran'])) {
                        foreach ($tujuan['sasaran'] as &$sasaran) {
                            $sasaran['indikator_sasaran'] = $this->getIndikatorSasaranBySasaranId($sasaran['id']);
                            if (!empty($sasaran['indikator_sasaran'])) {
                                foreach ($sasaran['indikator_sasaran'] as &$indikator) {
                                    $indikator['target_tahunan'] = $this->getTargetTahunanByIndikatorId($indikator['id']);
                                }
                            }
                        }
                    }
                }
            }
        }

        return $misiList;
    }

    public function getRpjmdByYear($tahun)
    {
        $misiList = $this->getMisiByYear($tahun);

        foreach ($misiList as &$misi) {
            $misi['tujuan'] = $this->getTujuanByMisiId($misi['id']);

            if (!empty($misi['tujuan'])) {
                foreach ($misi['tujuan'] as &$tujuan) {
                    // indikator_tujuan: filter target tahun spesifik
                    $tujuan['indikator_tujuan'] = $this->getIndikatorTujuanByTujuanId($tujuan['id']) ?? [];
                    if (!empty($tujuan['indikator_tujuan'])) {
                        foreach ($tujuan['indikator_tujuan'] as &$it) {
                            $it['target_tahunan_tujuan'] = $this->db->table('rpjmd_target_tujuan')
                                ->where('indikator_tujuan_id', $it['id'])
                                ->where('tahun', $tahun)
                                ->get()->getResultArray();
                        }
                    }

                    // sasaran -> indikator_sasaran: filter target tahun spesifik
                    $tujuan['sasaran'] = $this->getSasaranByTujuanId($tujuan['id']);
                    if (!empty($tujuan['sasaran'])) {
                        foreach ($tujuan['sasaran'] as &$sasaran) {
                            $sasaran['indikator_sasaran'] = $this->getIndikatorSasaranBySasaranId($sasaran['id']);
                            if (!empty($sasaran['indikator_sasaran'])) {
                                foreach ($sasaran['indikator_sasaran'] as &$indikator) {
                                    $indikator['target_tahunan'] = $this->db->table('rpjmd_target')
                                        ->where('indikator_sasaran_id', $indikator['id'])
                                        ->where('tahun', $tahun)
                                        ->get()->getResultArray();
                                }
                            }
                        }
                    }
                }
            }
        }

        return $misiList;
    }

    public function getRpjmdSummary()
    {
        // ambil tahun dari target sasaran
        $yearsSasaran = $this->db->table('rpjmd_target')
            ->distinct()
            ->select('tahun')
            ->orderBy('tahun', 'ASC')
            ->get()
            ->getResultArray();

        // ambil tahun dari target tujuan
        $yearsTujuan = $this->db->table('rpjmd_target_tujuan')
            ->distinct()
            ->select('tahun')
            ->orderBy('tahun', 'ASC')
            ->get()
            ->getResultArray();

        $years = [];
        foreach ($yearsSasaran as $y) {
            $years[$y['tahun']] = ['tahun' => $y['tahun']];
        }
        foreach ($yearsTujuan as $y) {
            $years[$y['tahun']] = ['tahun' => $y['tahun']];
        }
        ksort($years);

        return [
            'total_misi' => $this->db->table('rpjmd_misi')->countAllResults(),
            'total_tujuan' => $this->db->table('rpjmd_tujuan')->countAllResults(),
            'total_sasaran' => $this->db->table('rpjmd_sasaran')->countAllResults(),
            'total_indikator_sasaran' => $this->db->table('rpjmd_indikator_sasaran')->countAllResults(),
            'total_indikator_tujuan' => $this->db->table('rpjmd_indikator_tujuan')->countAllResults(),
            'total_target_tahunan' => $this->db->table('rpjmd_target')->countAllResults(),
            'total_target_tahunan_tujuan' => $this->db->table('rpjmd_target_tujuan')->countAllResults(),
            'years_available' => array_values($years),
        ];
    }

    // ==================== CRUD MISI ====================

    public function createMisi($data)
    {
        foreach (['misi', 'tahun_mulai', 'tahun_akhir'] as $f) {
            if (empty($data[$f])) {
                throw new \InvalidArgumentException("Field {$f} harus diisi");
            }
        }

        $insert = [
            'misi' => $data['misi'],
            'tahun_mulai' => (int) $data['tahun_mulai'],
            'tahun_akhir' => (int) $data['tahun_akhir'],
            'status' => $data['status'] ?? 'draft',
        ];

        $ok = $this->db->table('rpjmd_misi')->insert($insert);
        if (!$ok) {
            $err = $this->db->error();
            throw new \Exception("Failed to insert misi: " . $err['message']);
        }
        return (int) $this->db->insertID();
    }

    public function updateMisi($id, $data)
    {
        return $this->db->table('rpjmd_misi')->where('id', (int) $id)->update($data);
    }

    /* =====================================================
      |  AMBIL MISI ID DARI SEMUA LEVEL
      ===================================================== */
    public function findMisiIdForAnyEntity(int $id): ?int
    {
        $checks = [
            ['rpjmd_misi', 'id', 'id'],
            ['rpjmd_tujuan', 'id', 'misi_id'],
        ];

        foreach ($checks as [$table, $col, $ret]) {
            $q = $this->db->table($table)->where($col, $id)->get();
            if ($q !== false && $q->getNumRows() > 0) {
                return (int) ($ret === 'id' ? $id : $q->getRow()->$ret);
            }
        }

        // sasaran
        $q = $this->db->query(
            "SELECT t.misi_id FROM rpjmd_sasaran s
             JOIN rpjmd_tujuan t ON t.id = s.tujuan_id
             WHERE s.id = ?",
            [$id]
        );
        if ($q && ($r = $q->getRowArray())) {
            return (int) $r['misi_id'];
        }

        // indikator sasaran
        $q = $this->db->query(
            "SELECT t.misi_id FROM rpjmd_indikator_sasaran i
             JOIN rpjmd_sasaran s ON s.id = i.sasaran_id
             JOIN rpjmd_tujuan t ON t.id = s.tujuan_id
             WHERE i.id = ?",
            [$id]
        );
        if ($q && ($r = $q->getRowArray())) {
            return (int) $r['misi_id'];
        }

        return null;
    }

    /* =====================================================
     |  DELETE RPJMD - SUPER AMAN
     ===================================================== */
    public function deleteMisi(int $misiId): bool
    {
        $this->db->transBegin();

        try {
            // ================= TUJUAN =================
            $tujuanList = $this->db->table('rpjmd_tujuan')
                ->where('misi_id', $misiId)
                ->get()->getResultArray();

            foreach ($tujuanList as $tujuan) {
                $tujuanId = (int) $tujuan['id'];

                // ========== SASARAN ==========
                $sasaranList = $this->db->table('rpjmd_sasaran')
                    ->where('tujuan_id', $tujuanId)
                    ->get()->getResultArray();

                foreach ($sasaranList as $sasaran) {
                    $sasaranId = (int) $sasaran['id'];

                    // ---- INDIKATOR SASARAN ----
                    $indikatorSasaran = $this->db->table('rpjmd_indikator_sasaran')
                        ->where('sasaran_id', $sasaranId)
                        ->get()->getResultArray();

                    foreach ($indikatorSasaran as $is) {
                        // target indikator sasaran
                        $this->db->table('rpjmd_target')
                            ->where('indikator_sasaran_id', $is['id'])
                            ->delete();
                    }

                    $this->db->table('rpjmd_indikator_sasaran')
                        ->where('sasaran_id', $sasaranId)
                        ->delete();
                }

                $this->db->table('rpjmd_sasaran')
                    ->where('tujuan_id', $tujuanId)
                    ->delete();

                // ---- INDIKATOR TUJUAN ----
                $indikatorTujuan = $this->db->table('rpjmd_indikator_tujuan')
                    ->where('tujuan_id', $tujuanId)
                    ->get()->getResultArray();

                foreach ($indikatorTujuan as $it) {
                    $this->db->table('rpjmd_target_tujuan')
                        ->where('indikator_tujuan_id', $it['id'])
                        ->delete();
                }

                $this->db->table('rpjmd_indikator_tujuan')
                    ->where('tujuan_id', $tujuanId)
                    ->delete();
            }

            $this->db->table('rpjmd_tujuan')
                ->where('misi_id', $misiId)
                ->delete();

            // ================= MISI =================
            $this->db->table('rpjmd_misi')
                ->where('id', $misiId)
                ->delete();

            if ($this->db->transStatus() === false) {
                $this->db->transRollback();
                return false;
            }

            $this->db->transCommit();
            return true;

        } catch (\Throwable $e) {
            $this->db->transRollback();
            log_message('error', 'DELETE RPJMD FAILED: ' . $e->getMessage());
            return false;
        }
    }

    // ==================== CRUD TUJUAN ====================

    public function createTujuan($data)
    {
        foreach (['misi_id', 'tujuan_rpjmd'] as $f) {
            if (empty($data[$f])) {
                throw new \InvalidArgumentException("Field {$f} harus diisi");
            }
        }

        $ok = $this->db->table('rpjmd_tujuan')->insert([
            'misi_id' => (int) $data['misi_id'],
            'tujuan_rpjmd' => $data['tujuan_rpjmd'],
        ]);
        if (!$ok) {
            $err = $this->db->error();
            throw new \Exception("Failed to insert tujuan: " . $err['message']);
        }
        return (int) $this->db->insertID();
    }

    public function updateTujuan($id, $data)
    {
        return $this->db->table('rpjmd_tujuan')->where('id', (int) $id)->update($data);
    }

    public function deleteTujuan($id, $internal = false)
    {
        if (!$internal) {
            $this->db->transStart();
        }

        try {
            // hapus indikator tujuan dan targetnya
            $indikatorList = $this->getIndikatorTujuanByTujuanId($id);
            foreach ($indikatorList as $it) {
                $this->deleteTargetTahunanTujuanByIndikatorId($it['id']);
            }
            $this->db->table('rpjmd_indikator_tujuan')->delete(['tujuan_id' => (int) $id]);

            // hapus sasaran dan turunannya
            $sasaranList = $this->getSasaranByTujuanId($id);
            foreach ($sasaranList as $sas) {
                $this->deleteSasaran($sas['id'], true);
            }

            $res = $this->db->table('rpjmd_tujuan')->delete(['id' => (int) $id]);

            if (!$internal) {
                $this->db->transComplete();
                if ($this->db->transStatus() === false) {
                    throw new \Exception("Transaction failed during tujuan deletion");
                }
            }
            return $res;

        } catch (\Exception $e) {
            if (!$internal) {
                $this->db->transRollback();
            }
            throw $e;
        }
    }

    // ==================== CRUD INDIKATOR TUJUAN ====================

    public function createIndikatorTujuan($data)
    {
        foreach (['tujuan_id', 'indikator_tujuan'] as $f) {
            if (empty($data[$f])) {
                throw new \InvalidArgumentException("Field {$f} harus diisi");
            }
        }

        $ok = $this->db->table('rpjmd_indikator_tujuan')->insert([
            'tujuan_id' => (int) $data['tujuan_id'],
            'indikator_tujuan' => $data['indikator_tujuan'],
        ]);
        if (!$ok) {
            $err = $this->db->error();
            throw new \Exception("Failed to insert indikator tujuan: " . $err['message']);
        }
        return (int) $this->db->insertID();
    }

    public function updateIndikatorTujuan($id, $data)
    {
        return $this->db->table('rpjmd_indikator_tujuan')->where('id', (int) $id)->update($data);
    }

    public function deleteIndikatorTujuan($id)
    {
        // hapus target tahunan tujuan dahulu
        $this->deleteTargetTahunanTujuanByIndikatorId((int) $id);
        return $this->db->table('rpjmd_indikator_tujuan')->delete(['id' => (int) $id]);
    }

    // ==================== CRUD SASARAN ====================

    public function createSasaran($data)
    {
        foreach (['tujuan_id', 'sasaran_rpjmd'] as $f) {
            if (empty($data[$f])) {
                throw new \InvalidArgumentException("Field {$f} harus diisi");
            }
        }

        $ok = $this->db->table('rpjmd_sasaran')->insert([
            'tujuan_id' => (int) $data['tujuan_id'],
            'sasaran_rpjmd' => $data['sasaran_rpjmd'],
        ]);
        if (!$ok) {
            $err = $this->db->error();
            throw new \Exception("Failed to insert sasaran: " . $err['message']);
        }
        return (int) $this->db->insertID();
    }

    public function updateSasaran($id, $data)
    {
        return $this->db->table('rpjmd_sasaran')->where('id', (int) $id)->update($data);
    }

    public function deleteSasaran($id, $internal = false)
    {
        if (!$internal) {
            $this->db->transStart();
        }

        try {
            // hapus indikator_sasaran + target
            $indikatorList = $this->getIndikatorSasaranBySasaranId($id);
            foreach ($indikatorList as $ind) {
                $this->deleteIndikatorSasaran($ind['id'], true);
            }

            // Hapus RENSTRA & RENJA & RKPD (jika ada pada skema)
            $renstraSasaran = $this->db->table('renstra_sasaran')->where('rpjmd_sasaran_id', $id)->get()->getResultArray();
            foreach ($renstraSasaran as $rs) {
                $renstraSasaranId = $rs['id'];

                $renjaSasaran = $this->db->table('renja_sasaran')->where('renstra_sasaran_id', $renstraSasaranId)->get()->getResultArray();
                foreach ($renjaSasaran as $rj) {
                    $this->db->table('renja_indikator_sasaran')->where('renja_sasaran_id', $rj['id'])->delete();
                }
                $this->db->table('renja_sasaran')->where('renstra_sasaran_id', $renstraSasaranId)->delete();

                $renstraIndikator = $this->db->table('renstra_indikator_sasaran')->where('renstra_sasaran_id', $renstraSasaranId)->get()->getResultArray();
                foreach ($renstraIndikator as $ri) {
                    $this->db->table('renstra_target')->where('renstra_indikator_id', $ri['id'])->delete();
                }
                $this->db->table('renstra_indikator_sasaran')->where('renstra_sasaran_id', $renstraSasaranId)->delete();
            }
            $this->db->table('renstra_sasaran')->delete(['rpjmd_sasaran_id' => $id]);

            $rkpdSasaran = $this->db->table('rkpd_sasaran')->where('rpjmd_sasaran_id', $id)->get()->getResultArray();
            foreach ($rkpdSasaran as $rk) {
                $this->db->table('rkpd_indikator_sasaran')->delete(['rkpd_sasaran_id' => $rk['id']]);
            }
            $this->db->table('rkpd_sasaran')->delete(['rpjmd_sasaran_id' => $id]);

            // hapus sasaran
            $res = $this->db->table('rpjmd_sasaran')->delete(['id' => (int) $id]);

            if (!$internal) {
                $this->db->transComplete();
                if ($this->db->transStatus() === false) {
                    throw new \Exception("Transaction failed during sasaran deletion");
                }
            }
            return $res;

        } catch (\Exception $e) {
            if (!$internal) {
                $this->db->transRollback();
            }
            throw $e;
        }
    }

    // ==================== CRUD INDIKATOR SASARAN ====================

    public function createIndikatorSasaran($data)
    {
        $debugFile = WRITEPATH . 'debug_rpjmd_model.txt';
        file_put_contents($debugFile, "=== CREATE INDIKATOR SASARAN - " . date('Y-m-d H:i:s') . " ===\n", FILE_APPEND);

        foreach (['sasaran_id', 'indikator_sasaran', 'definisi_op', 'satuan'] as $f) {
            if (empty($data[$f])) {
                $err = "Field {$f} harus diisi";
                file_put_contents($debugFile, "VALIDATION ERROR: {$err}\n", FILE_APPEND);
                throw new \InvalidArgumentException($err);
            }
        }

        // normalisasi jenis_indikator
        $jenis = $this->normalizeJenisIndikator($data['jenis_indikator'] ?? '');

        $insert = [
            'sasaran_id' => (int) $data['sasaran_id'],
            'indikator_sasaran' => $data['indikator_sasaran'],
            'definisi_op' => $data['definisi_op'],
            'satuan' => $data['satuan'],
            'jenis_indikator' => $jenis,
        ];

        $ok = $this->db->table('rpjmd_indikator_sasaran')->insert($insert);
        $id = (int) $this->db->insertID();

        file_put_contents($debugFile, "Insert result: " . ($ok ? 'TRUE' : 'FALSE') . " | ID: {$id}\n", FILE_APPEND);

        if (!$ok) {
            $err = $this->db->error();
            throw new \Exception("Failed to insert indikator sasaran: " . $err['message']);
        }
        return $id;
    }

    public function updateIndikatorSasaran($id, $data)
    {
        $debugFile = WRITEPATH . 'debug_rpjmd_model.txt';
        file_put_contents($debugFile, "=== UPDATE INDIKATOR SASARAN - " . date('Y-m-d H:i:s') . " ===\n", FILE_APPEND);

        // normalisasi jenis_indikator jika ada di data
        if (array_key_exists('jenis_indikator', $data)) {
            $data['jenis_indikator'] = $this->normalizeJenisIndikator($data['jenis_indikator']);
        }

        $ok = $this->db->table('rpjmd_indikator_sasaran')
            ->where('id', (int) $id)
            ->update($data);

        file_put_contents(
            $debugFile,
            "Update result: " . ($ok ? 'TRUE' : 'FALSE') . " | Affected: " . $this->db->affectedRows() . "\n",
            FILE_APPEND
        );
        return $ok;
    }

    public function deleteIndikatorSasaran($id, $internal = false)
    {
        if (!$internal) {
            $this->db->transStart();
        }

        try {
            $this->db->table('rpjmd_target')->delete(['indikator_sasaran_id' => (int) $id]);
            $res = $this->db->table('rpjmd_indikator_sasaran')->delete(['id' => (int) $id]);

            if (!$internal) {
                $this->db->transComplete();
                if ($this->db->transStatus() === false) {
                    throw new \Exception("Transaction failed during indikator sasaran deletion");
                }
            }
            return $res;

        } catch (\Exception $e) {
            if (!$internal) {
                $this->db->transRollback();
            }
            throw $e;
        }
    }

    // ==================== CRUD TARGET TAHUNAN (Indikator Sasaran) ====================

    public function createTargetTahunan($data)
    {
        $debugFile = WRITEPATH . 'debug_rpjmd_model.txt';
        file_put_contents($debugFile, "=== CREATE TARGET TAHUNAN - " . date('Y-m-d H:i:s') . " ===\n", FILE_APPEND);

        foreach (['indikator_sasaran_id', 'tahun'] as $f) {
            if (empty($data[$f])) {
                $err = "Field {$f} harus diisi";
                file_put_contents($debugFile, "VALIDATION ERROR: {$err}\n", FILE_APPEND);
                throw new \InvalidArgumentException($err);
            }
        }

        $insert = [
            'indikator_sasaran_id' => (int) $data['indikator_sasaran_id'],
            'tahun' => (int) $data['tahun'],
            'target_tahunan' => (string) ($data['target_tahunan'] ?? ''),
        ];

        $ok = $this->db->table('rpjmd_target')->insert($insert);
        $id = (int) $this->db->insertID();

        file_put_contents($debugFile, "Insert result: " . ($ok ? 'TRUE' : 'FALSE') . " | ID: {$id}\n", FILE_APPEND);

        if (!$ok) {
            $err = $this->db->error();
            throw new \Exception("Failed to insert target tahunan: " . $err['message']);
        }
        return $id;
    }

    public function updateTargetTahunan($id, $data)
    {
        return $this->db->table('rpjmd_target')->where('id', (int) $id)->update($data);
    }

    public function deleteTargetTahunan($id)
    {
        return $this->db->table('rpjmd_target')->delete(['id' => (int) $id]);
    }

    public function deleteTargetTahunanByIndikatorId($indikatorSasaranId)
    {
        $debugFile = WRITEPATH . 'debug_rpjmd_model.txt';
        file_put_contents($debugFile, "=== DELETE TARGET BY INDIKATOR (SASARAN) - " . date('Y-m-d H:i:s') . " ===\n", FILE_APPEND);

        $res = $this->db->table('rpjmd_target')->delete(['indikator_sasaran_id' => (int) $indikatorSasaranId]);
        file_put_contents($debugFile, "Delete result: " . ($res ? 'TRUE' : 'FALSE') . " | Affected: " . $this->db->affectedRows() . "\n", FILE_APPEND);
        return $res;
    }

    // ==================== BATCH OPERATIONS ====================

    public function createCompleteRpjmdTransaction(array $data): ?int
    {
        $db = $this->db;
        $db->transStart();

        // ================== INSERT MISI ==================
        $builderMisi = $db->table('rpjmd_misi');
        $builderMisi->insert([
            'misi' => $data['misi']['misi'] ?? '',
            'tahun_mulai' => $data['misi']['tahun_mulai'] ?? null,
            'tahun_akhir' => $data['misi']['tahun_akhir'] ?? null,
            'status' => $data['misi']['status'] ?? 'draft',
        ]);
        $misiId = $db->insertID();

        // ================== LOOP TUJUAN ==================
        $builderTujuan = $db->table('rpjmd_tujuan');
        $builderIndTujuan = $db->table('rpjmd_indikator_tujuan');
        $builderTargetTujuan = $db->table('rpjmd_target_tujuan');

        $builderSasaran = $db->table('rpjmd_sasaran');
        $builderIndSasaran = $db->table('rpjmd_indikator_sasaran');
        $builderTargetSas = $db->table('rpjmd_target');

        foreach ($data['tujuan'] ?? [] as $tujuan) {

            // ---------- TUJUAN ----------
            $builderTujuan->insert([
                'misi_id' => $misiId,
                'tujuan_rpjmd' => $tujuan['tujuan_rpjmd'] ?? '',
            ]);
            $tujuanId = $db->insertID();

            // ---------- INDIKATOR TUJUAN ----------
            foreach ($tujuan['indikator_tujuan'] ?? [] as $indikatorTujuan) {
                $builderIndTujuan->insert([
                    'tujuan_id' => $tujuanId,
                    'indikator_tujuan' => $indikatorTujuan['indikator_tujuan'] ?? '',
                ]);
                $indTujuanId = $db->insertID();

                foreach ($indikatorTujuan['target_tahunan_tujuan'] ?? [] as $t) {
                    $builderTargetTujuan->insert([
                        'indikator_tujuan_id' => $indTujuanId,
                        'tahun' => $t['tahun'] ?? null,
                        'target_tahunan' => $t['target_tahunan'] ?? null,
                        'baseline' => $t['baseline'] ?? null,
                    ]);
                }
            }

            // ---------- SASARAN ----------
            foreach ($tujuan['sasaran'] ?? [] as $sasaran) {
                $builderSasaran->insert([
                    'tujuan_id' => $tujuanId,
                    'sasaran_rpjmd' => $sasaran['sasaran_rpjmd'] ?? '',
                ]);
                // ✅ ID sasaran hasil insert
                $sasaranId = $db->insertID();

                // ---------- INDIKATOR SASARAN ----------
                foreach ($sasaran['indikator_sasaran'] ?? [] as $indikatorSasaran) {

                    // normalisasi jenis indikator (opsional)
                    $jenisRaw = strtolower(trim($indikatorSasaran['jenis_indikator'] ?? ''));
                    $jenis = ($jenisRaw === 'indikator negatif' || $jenisRaw === 'negatif')
                        ? 'indikator negatif'
                        : 'indikator positif';

                    $builderIndSasaran->insert([
                        // ✅ PAKAI $sasaranId, BUKAN dari POST
                        'sasaran_id' => $sasaranId,
                        'indikator_sasaran' => $indikatorSasaran['indikator_sasaran'] ?? '',
                        'satuan' => $indikatorSasaran['satuan'] ?? '',
                        'jenis_indikator' => $jenis,
                        'baseline' => $indikatorSasaran['baseline'] ?? null,
                        'definisi_op' => $indikatorSasaran['definisi_op'] ?? '',
                    ]);
                    $indSasaranId = $db->insertID();

                    // ---------- TARGET TAHUNAN INDIKATOR SASARAN ----------
                    foreach ($indikatorSasaran['target_tahunan'] ?? [] as $t) {
                        $builderTargetSas->insert([
                            // ✅ selalu pakai $indSasaranId
                            'indikator_sasaran_id' => $indSasaranId,
                            'tahun' => $t['tahun'] ?? null,
                            'target_tahunan' => $t['target_tahunan'] ?? null,
                        ]);
                    }
                }
            }
        }

        $db->transComplete();

        if (!$db->transStatus()) {
            return null;
        }

        return $misiId;
    }


    // helper untuk sinkronisasi target_tujuan saat UPDATE
    private function syncTargetTujuanForIndikator(int $indikatorId, array $targets): void
    {
        $tbl = $this->db->table('rpjmd_target_tujuan');

        $existing = $tbl->select('id, tahun')
            ->where('indikator_tujuan_id', $indikatorId)->get()->getResultArray();

        $byId = [];
        $byYear = [];
        foreach ($existing as $row) {
            $byId[(int) $row['id']] = $row;
            $byYear[(int) $row['tahun']] = $row;
        }

        $seenIds = [];

        foreach ($targets as $tt) {
            $id = isset($tt['id']) && $tt['id'] !== '' ? (int) $tt['id'] : null;
            $tahun = (int) ($tt['tahun'] ?? 0);
            $target = (string) ($tt['target_tahunan'] ?? '');

            if ($id && isset($byId[$id])) {
                $tbl->where('id', $id)->update([
                    'tahun' => $tahun,
                    'target_tahunan' => $target,
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
                $seenIds[] = $id;

            } elseif ($tahun) {
                if (isset($byYear[$tahun])) {
                    $tbl->where('id', (int) $byYear[$tahun]['id'])->update([
                        'target_tahunan' => $target,
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
                    $seenIds[] = (int) $byYear[$tahun]['id'];
                } else {
                    $tbl->insert([
                        'indikator_tujuan_id' => $indikatorId,
                        'tahun' => $tahun,
                        'target_tahunan' => $target,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
                    $seenIds[] = (int) $this->db->insertID();
                }
            }
        }

        // delete yang tidak ada lagi di form
        if (!empty($byId)) {
            $idsToDelete = array_diff(array_keys($byId), $seenIds);
            if (!empty($idsToDelete)) {
                // 🔥 DIUBAH: NONAKTIFKAN target lama, BUKAN delete
                $tbl->whereIn('id', $idsToDelete)
                    ->update(['is_active' => 0]);
            }
        }
    }

    public function updateCompleteRpjmdTransaction($misiId, $data)
    {
        $debugFile = WRITEPATH . 'debug_rpjmd_model.txt';
        file_put_contents($debugFile, "\n=== UPDATE COMPLETE RPJMD TRANSACTION - " . date('Y-m-d H:i:s') . " ===\n", FILE_APPEND);

        try {
            $this->db->transStart();

            /* ======================================================
             |  🔥 FIX 1: NORMALISASI INDEX ARRAY (WAJIB)
             ====================================================== */
            $data['tujuan'] = array_values($data['tujuan'] ?? []);
            foreach ($data['tujuan'] as &$t) {

                if (isset($t['indikator_tujuan'])) {
                    $t['indikator_tujuan'] = array_values($t['indikator_tujuan']);
                }

                if (isset($t['sasaran'])) {
                    $t['sasaran'] = array_values($t['sasaran']);

                    foreach ($t['sasaran'] as &$s) {
                        if (isset($s['indikator_sasaran'])) {
                            $s['indikator_sasaran'] = array_values($s['indikator_sasaran']);
                        }
                    }
                    unset($s);
                }
            }
            unset($t);

            /* =======================
             |  UPDATE MISI
             ======================= */
            if (!empty($data['misi'])) {
                $this->updateMisi($misiId, $data['misi']);
            }

            /* =======================
             |  TUJUAN
             ======================= */
            $existingTujuanIds = array_column($this->getTujuanByMisiId($misiId), 'id');
            $processedTujuanIds = [];

            foreach ($data['tujuan'] as $tujuanData) {

                if (empty($tujuanData['tujuan_rpjmd'])) {
                    continue;
                }

                // upsert tujuan
                if (!empty($tujuanData['id'])) {
                    $tujuanId = (int) $tujuanData['id'];
                    $this->updateTujuan($tujuanId, [
                        'misi_id' => $misiId,
                        'tujuan_rpjmd' => $tujuanData['tujuan_rpjmd'],
                    ]);
                } else {
                    $tujuanId = $this->createTujuan([
                        'misi_id' => $misiId,
                        'tujuan_rpjmd' => $tujuanData['tujuan_rpjmd'],
                    ]);
                }
                $processedTujuanIds[] = $tujuanId;

                /* =======================
                 |  INDIKATOR TUJUAN
                 ======================= */
                $existingIndTujuan = array_column(
                    $this->getIndikatorTujuanByTujuanId($tujuanId),
                    'id'
                );
                $processedIndTujuan = [];

                foreach (($tujuanData['indikator_tujuan'] ?? []) as $it) {

                    if (empty($it['indikator_tujuan'])) {
                        continue;
                    }

                    if (!empty($it['id'])) {
                        $indikatorId = (int) $it['id'];
                        $this->updateIndikatorTujuan($indikatorId, [
                            'tujuan_id' => $tujuanId,
                            'indikator_tujuan' => $it['indikator_tujuan'],
                            'baseline' => $it['baseline'] ?? null,
                        ]);
                    } else {
                        $indikatorId = $this->createIndikatorTujuan([
                            'tujuan_id' => $tujuanId,
                            'indikator_tujuan' => $it['indikator_tujuan'],
                            'baseline' => $it['baseline'] ?? null,
                        ]);
                    }
                    $processedIndTujuan[] = $indikatorId;

                    /* ---- TARGET TUJUAN (CEGAH DOBEL TAHUN) ---- */
                    $targets = [];
                    foreach (($it['target_tahunan_tujuan'] ?? []) as $tt) {
                        if (isset($tt['tahun'])) {
                            $targets[(int) $tt['tahun']] = $tt;
                        }
                    }

                    $this->syncTargetTujuanForIndikator($indikatorId, array_values($targets));
                }

                // hapus indikator tujuan yang tidak dipakai
                foreach (array_diff($existingIndTujuan, $processedIndTujuan) as $delId) {
                    $this->deleteIndikatorTujuan((int) $delId);
                }

                /* =======================
                 |  SASARAN
                 ======================= */
                $existingSasaranIds = array_column(
                    $this->getSasaranByTujuanId($tujuanId),
                    'id'
                );
                $processedSasaranIds = [];

                foreach (($tujuanData['sasaran'] ?? []) as $sasData) {

                    if (empty($sasData['sasaran_rpjmd'])) {
                        continue;
                    }

                    if (!empty($sasData['id'])) {
                        $sasaranId = (int) $sasData['id'];
                        $this->updateSasaran($sasaranId, [
                            'tujuan_id' => $tujuanId,
                            'sasaran_rpjmd' => $sasData['sasaran_rpjmd'],
                        ]);
                    } else {
                        $sasaranId = $this->createSasaran([
                            'tujuan_id' => $tujuanId,
                            'sasaran_rpjmd' => $sasData['sasaran_rpjmd'],
                        ]);
                    }
                    $processedSasaranIds[] = $sasaranId;

                    /* =======================
                     |  INDIKATOR SASARAN
                     ======================= */
                    $existingIndSasIds = array_column(
                        $this->getIndikatorSasaranBySasaranId($sasaranId),
                        'id'
                    );
                    $processedIndSasIds = [];

                    foreach (($sasData['indikator_sasaran'] ?? []) as $is) {

                        if (empty($is['indikator_sasaran'])) {
                            continue;
                        }

                        if (!empty($is['id'])) {
                            $indId = (int) $is['id'];
                            $this->updateIndikatorSasaran($indId, [
                                'sasaran_id' => $sasaranId,
                                'indikator_sasaran' => $is['indikator_sasaran'],
                                'definisi_op' => $is['definisi_op'] ?? '',
                                'satuan' => $is['satuan'] ?? '',
                                'baseline' => $is['baseline'] ?? null,
                                'jenis_indikator' => $is['jenis_indikator'] ?? '',
                            ]);
                        } else {
                            $indId = $this->createIndikatorSasaran([
                                'sasaran_id' => $sasaranId,
                                'indikator_sasaran' => $is['indikator_sasaran'],
                                'definisi_op' => $is['definisi_op'] ?? '',
                                'satuan' => $is['satuan'] ?? '',
                                'baseline' => $is['baseline'] ?? null,
                                'jenis_indikator' => $is['jenis_indikator'] ?? '',
                            ]);
                        }
                        $processedIndSasIds[] = $indId;

                        /* ---- TARGET SASARAN (CEGAH DOBEL TAHUN) ---- */
                        $this->deleteTargetTahunanByIndikatorId($indId);

                        $targets = [];
                        foreach (($is['target_tahunan'] ?? []) as $t) {
                            if (isset($t['tahun'])) {
                                $targets[(int) $t['tahun']] = $t;
                            }
                        }

                        foreach ($targets as $t) {
                            $this->createTargetTahunan([
                                'indikator_sasaran_id' => $indId,
                                'tahun' => (int) $t['tahun'],
                                'target_tahunan' => (string) ($t['target_tahunan'] ?? ''),
                            ]);
                        }
                    }

                    // hapus indikator sasaran tidak terpakai
                    foreach (array_diff($existingIndSasIds, $processedIndSasIds) as $delId) {
                        $this->deleteIndikatorSasaran((int) $delId, true);
                    }
                }

                // hapus sasaran tidak terpakai
                foreach (array_diff($existingSasaranIds, $processedSasaranIds) as $delId) {
                    $this->deleteSasaran((int) $delId, true);
                }
            }

            // hapus tujuan tidak terpakai
            foreach (array_diff($existingTujuanIds, $processedTujuanIds) as $delId) {
                $this->deleteTujuan((int) $delId, true);
            }

            // cleanup orphan
            $this->cleanupOrphanedRecords($misiId);

            $this->db->transComplete();
            if ($this->db->transStatus() === false) {
                file_put_contents($debugFile, "ERROR: DB transaction failed\n", FILE_APPEND);
                throw new \Exception("Database transaction failed");
            }

            file_put_contents($debugFile, "SUCCESS: Update completed\n", FILE_APPEND);
            return true;

        } catch (\Exception $e) {
            file_put_contents($debugFile, "EXCEPTION: " . $e->getMessage() . "\n", FILE_APPEND);
            $this->db->transRollback();
            throw $e;
        }
    }


    // ==================== HELPER METHODS ====================

    public function misiExists(int $id): bool
    {
        return $this->db
            ->table('rpjmd_misi')
            ->where('id', $id)
            ->countAllResults() > 0;
    }

    public function tujuanExists($id)
    {
        return $this->db->table('rpjmd_tujuan')->where('id', (int) $id)->countAllResults() > 0;
    }

    public function sasaranExists($id)
    {
        return $this->db->table('rpjmd_sasaran')->where('id', (int) $id)->countAllResults() > 0;
    }

    public function indikatorSasaranExists($id)
    {
        return $this->db->table('rpjmd_indikator_sasaran')->where('id', (int) $id)->countAllResults() > 0;
    }

    public function cleanupOrphanedRecords($misiId)
    {
        // indikator_tujuan yatim
        $this->db->query("
            DELETE it FROM rpjmd_indikator_tujuan it
            LEFT JOIN rpjmd_tujuan t ON t.id = it.tujuan_id
            LEFT JOIN rpjmd_misi m ON m.id = t.misi_id
            WHERE m.id = ? AND t.id IS NULL
        ", [$misiId]);

        // sasaran yatim
        $this->db->query("
            DELETE s FROM rpjmd_sasaran s
            LEFT JOIN rpjmd_tujuan t ON t.id = s.tujuan_id
            LEFT JOIN rpjmd_misi m ON m.id = t.misi_id
            WHERE m.id = ? AND t.id IS NULL
        ", [$misiId]);

        // indikator_sasaran yatim
        $this->db->query("
            DELETE iss FROM rpjmd_indikator_sasaran iss
            LEFT JOIN rpjmd_sasaran s ON s.id = iss.sasaran_id
            LEFT JOIN rpjmd_tujuan t ON t.id = s.tujuan_id
            LEFT JOIN rpjmd_misi m ON m.id = t.misi_id
            WHERE m.id = ? AND s.id IS NULL
        ", [$misiId]);

        // target (sasaran) yatim
        $this->db->query("
            DELETE tt FROM rpjmd_target tt
            LEFT JOIN rpjmd_indikator_sasaran iss ON iss.id = tt.indikator_sasaran_id
            LEFT JOIN rpjmd_sasaran s ON s.id = iss.sasaran_id
            LEFT JOIN rpjmd_tujuan t ON t.id = s.tujuan_id
            LEFT JOIN rpjmd_misi m ON m.id = t.misi_id
            WHERE m.id = ? AND iss.id IS NULL
        ", [$misiId]);

        // target_tujuan yatim
        $this->db->query("
            DELETE ttt FROM rpjmd_target_tujuan ttt
            LEFT JOIN rpjmd_indikator_tujuan it ON it.id = ttt.indikator_tujuan_id
            LEFT JOIN rpjmd_tujuan t ON t.id = it.tujuan_id
            LEFT JOIN rpjmd_misi m ON m.id = t.misi_id
            WHERE m.id = ? AND it.id IS NULL
        ", [$misiId]);
    }

    public function findMisiIdByTujuanId($tujuanId)
    {
        $row = $this->db->table('rpjmd_tujuan')->where('id', (int) $tujuanId)->get()->getRowArray();
        return $row['misi_id'] ?? null;
    }

    public function findMisiIdBySasaranId($sasaranId)
    {
        $row = $this->db->table('rpjmd_sasaran s')
            ->join('rpjmd_tujuan t', 't.id = s.tujuan_id')
            ->select('t.misi_id')
            ->where('s.id', (int) $sasaranId)
            ->get()->getRowArray();
        return $row['misi_id'] ?? null;
    }

    public function findMisiIdByIndikatorSasaranId($indikatorId)
    {
        $row = $this->db->table('rpjmd_indikator_sasaran iss')
            ->join('rpjmd_sasaran s', 's.id = iss.sasaran_id')
            ->join('rpjmd_tujuan t', 't.id = s.tujuan_id')
            ->select('t.misi_id')
            ->where('iss.id', (int) $indikatorId)
            ->get()->getRowArray();
        return $row['misi_id'] ?? null;
    }

    public function findMisiIdByTargetId($targetId)
    {
        $row = $this->db->table('rpjmd_target tt')
            ->join('rpjmd_indikator_sasaran iss', 'iss.id = tt.indikator_sasaran_id')
            ->join('rpjmd_sasaran s', 's.id = iss.sasaran_id')
            ->join('rpjmd_tujuan t', 't.id = s.tujuan_id')
            ->select('t.misi_id')
            ->where('tt.id', (int) $targetId)
            ->get()->getRowArray();
        return $row['misi_id'] ?? null;
    }

    // public function findMisiIdForAnyEntity(int $id): ?int
    // {
    //     // 1. Cek langsung misi
    //     $m = $this->db->table('rpjmd_misi')->where('id', $id)->get();
    //     if ($m !== false && $m->getNumRows() > 0) {
    //         return (int) $id;
    //     }

    //     // 2. Dari tujuan
    //     $q = $this->db->query(
    //         "SELECT misi_id FROM rpjmd_tujuan WHERE id = ? LIMIT 1",
    //         [$id]
    //     );
    //     if ($q !== false) {
    //         $r = $q->getRowArray();
    //         if (!empty($r['misi_id']))
    //             return (int) $r['misi_id'];
    //     }

    //     // 3. Dari sasaran
    //     $q = $this->db->query(
    //         "SELECT t.misi_id
    //      FROM rpjmd_sasaran s
    //      JOIN rpjmd_tujuan t ON t.id = s.tujuan_id
    //      WHERE s.id = ? LIMIT 1",
    //         [$id]
    //     );
    //     if ($q !== false) {
    //         $r = $q->getRowArray();
    //         if (!empty($r['misi_id']))
    //             return (int) $r['misi_id'];
    //     }

    //     // 4. Dari indikator sasaran
    //     $q = $this->db->query(
    //         "SELECT t.misi_id
    //      FROM rpjmd_indikator_sasaran i
    //      JOIN rpjmd_sasaran s ON s.id = i.sasaran_id
    //      JOIN rpjmd_tujuan t ON t.id = s.tujuan_id
    //      WHERE i.id = ? LIMIT 1",
    //         [$id]
    //     );
    //     if ($q !== false) {
    //         $r = $q->getRowArray();
    //         if (!empty($r['misi_id']))
    //             return (int) $r['misi_id'];
    //     }

    //     return null;
    // }


    public function getCompletedRpjmdStructure()
    {
        $misiList = $this->getCompletedMisi();

        foreach ($misiList as &$misi) {
            $misi['tujuan'] = $this->getTujuanByMisiId($misi['id']);

            if (!empty($misi['tujuan'])) {
                foreach ($misi['tujuan'] as &$tujuan) {
                    // indikator tujuan + target tujuan
                    $tujuan['indikator_tujuan'] = $this->getIndikatorTujuanByTujuanId($tujuan['id']) ?? [];
                    if (!empty($tujuan['indikator_tujuan'])) {
                        foreach ($tujuan['indikator_tujuan'] as &$it) {
                            $it['target_tahunan_tujuan'] = $this->getTargetTahunanTujuanByIndikatorId($it['id']) ?? [];
                        }
                    }

                    // sasaran -> indikator -> target
                    $tujuan['sasaran'] = $this->getSasaranByTujuanId($tujuan['id']);
                    if (!empty($tujuan['sasaran'])) {
                        foreach ($tujuan['sasaran'] as &$sasaran) {
                            $sasaran['indikator_sasaran'] = $this->getIndikatorSasaranBySasaranId($sasaran['id']);
                            if (!empty($sasaran['indikator_sasaran'])) {
                                foreach ($sasaran['indikator_sasaran'] as &$ind) {
                                    $ind['target_tahunan'] = $this->getTargetTahunanByIndikatorId($ind['id']);
                                }
                            }
                        }
                    }
                }
            }
        }

        return $misiList;
    }

    public function getSasaranWithIndikatorAndTarget()
    {
        $sasaranList = $this->db->table('rpjmd_sasaran s')
            ->select('s.id, s.sasaran_rpjmd')
            ->orderBy('s.id', 'ASC')
            ->get()
            ->getResultArray();

        foreach ($sasaranList as &$sasaran) {
            $indikatorList = $this->db->table('rpjmd_indikator_sasaran i')
                ->select('i.id, i.indikator_sasaran, i.satuan')
                ->where('i.sasaran_id', $sasaran['id'])
                ->get()
                ->getResultArray();

            foreach ($indikatorList as &$indikator) {
                $indikator['target_tahunan'] = $this->db->table('rpjmd_target t')
                    ->select('t.tahun, t.target_tahunan')
                    ->where('t.indikator_sasaran_id', $indikator['id'])
                    ->orderBy('t.tahun', 'ASC')
                    ->get()
                    ->getResultArray();
            }

            $sasaran['indikator_sasaran'] = $indikatorList;
        }

        return $sasaranList;
    }
    // =====================================================
// 🔥 TAMBAHAN BARU: NONAKTIFKAN TARGET SASARAN (AMAN)
// =====================================================
    private function syncTargetSasaranSafe(int $indikatorId, array $targets): void
    {
        $tbl = $this->db->table('rpjmd_target');

        // 🔥 NONAKTIFKAN semua target lama (mis. tahun 2030)
        $tbl->where('indikator_sasaran_id', $indikatorId)
            ->update(['is_active' => 0]);

        foreach ($targets as $t) {

            if (!empty($t['id'])) {
                // 🔥 UPDATE target lama → AKTIF
                $tbl->where('id', (int) $t['id'])->update([
                    'tahun' => (int) $t['tahun'],
                    'target_tahunan' => (string) ($t['target_tahunan'] ?? ''),
                    'is_active' => 1,
                ]);
            } else {
                // 🔥 INSERT target baru
                $tbl->insert([
                    'indikator_sasaran_id' => $indikatorId,
                    'tahun' => (int) $t['tahun'],
                    'target_tahunan' => (string) ($t['target_tahunan'] ?? ''),
                    'is_active' => 1,
                ]);
            }
        }
    }

}
