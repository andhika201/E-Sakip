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
                'updated_at' => date('Y-m-d H:i:s'), // FIX: gunakan updated_at
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
            'updated_at' => date('Y-m-d H:i:s'), // FIX: ganti update_at -> updated_at
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
                'updated_at' => date('Y-m-d H:i:s'), // FIX: ganti update_at -> updated_at
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
        $years = $this->db->table('rpjmd_target')
            ->distinct()
            ->select('tahun')
            ->orderBy('tahun', 'ASC')
            ->get()
            ->getResultArray();

        return [
            'total_misi' => $this->db->table('rpjmd_misi')->countAllResults(),
            'total_tujuan' => $this->db->table('rpjmd_tujuan')->countAllResults(),
            'total_sasaran' => $this->db->table('rpjmd_sasaran')->countAllResults(),
            'total_indikator_sasaran' => $this->db->table('rpjmd_indikator_sasaran')->countAllResults(),
            'total_indikator_tujuan' => $this->db->table('rpjmd_indikator_tujuan')->countAllResults(),
            'total_target_tahunan' => $this->db->table('rpjmd_target')->countAllResults(),
            'total_target_tahunan_tujuan' => $this->db->table('rpjmd_target_tujuan')->countAllResults(),
            'years_available' => $years,
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

    public function deleteMisi($id, $internal = false)
    {
        if (!$internal)
            $this->db->transStart();

        try {
            $tujuanList = $this->getTujuanByMisiId($id);
            foreach ($tujuanList as $tujuan) {
                $this->deleteTujuan($tujuan['id'], true);
            }
            $res = $this->db->table('rpjmd_misi')->delete(['id' => (int) $id]);

            if (!$internal)
                $this->db->transComplete();
            return $res;

        } catch (\Exception $e) {
            if (!$internal)
                $this->db->transRollback();
            throw $e;
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
        if (!$internal)
            $this->db->transStart();

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
            if (!$internal)
                $this->db->transRollback();
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
        if (!$internal)
            $this->db->transStart();

        try {
            // hapus indikator_sasaran + target
            $indikatorList = $this->getIndikatorSasaranBySasaranId($id);
            foreach ($indikatorList as $ind) {
                $this->deleteIndikatorSasaran($ind['id'], true);
            }

            // Hapus RENSTRA & RENJA & RKPD (jika ada pada skema kamu)
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
            if (!$internal)
                $this->db->transRollback();
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

        $insert = [
            'sasaran_id' => (int) $data['sasaran_id'],
            'indikator_sasaran' => $data['indikator_sasaran'],
            'definisi_op' => $data['definisi_op'],
            'satuan' => $data['satuan'],
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

        $ok = $this->db->table('rpjmd_indikator_sasaran')->where('id', (int) $id)->update($data);
        file_put_contents($debugFile, "Update result: " . ($ok ? 'TRUE' : 'FALSE') . " | Affected: " . $this->db->affectedRows() . "\n", FILE_APPEND);
        return $ok;
    }

    public function deleteIndikatorSasaran($id, $internal = false)
    {
        if (!$internal)
            $this->db->transStart();

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
            if (!$internal)
                $this->db->transRollback();
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

    public function createCompleteRpjmdTransaction($data)
    {
        try {
            $this->db->transStart();

            // MISI
            $misiId = $this->createMisi($data['misi']);
            // TUJUAN
            if (!empty($data['tujuan']) && is_array($data['tujuan'])) {
                foreach ($data['tujuan'] as $tujuanData) {
                    $tujuanData['misi_id'] = $misiId;
                    $tujuanId = $this->createTujuan($tujuanData);

                    // INDIKATOR TUJUAN
                    if (!empty($tujuanData['indikator_tujuan']) && is_array($tujuanData['indikator_tujuan'])) {
                        foreach ($tujuanData['indikator_tujuan'] as $it) {
                            $indikatorId = $this->createIndikatorTujuan([
                                'tujuan_id' => $tujuanId,
                                'indikator_tujuan' => $it['indikator_tujuan'] ?? '',
                            ]);

                            // TARGET TUJUAN
                            if (!empty($it['target_tahunan_tujuan']) && is_array($it['target_tahunan_tujuan'])) {
                                foreach ($it['target_tahunan_tujuan'] as $tt) {
                                    $this->createTargetTahunanTujuan([
                                        'indikator_tujuan_id' => $indikatorId,
                                        'tahun' => (int) ($tt['tahun'] ?? 0),
                                        'target_tahunan' => (string) ($tt['target_tahunan'] ?? ''),
                                    ]);
                                }
                            }
                        }
                    }

                    // SASARAN
                    if (!empty($tujuanData['sasaran']) && is_array($tujuanData['sasaran'])) {
                        foreach ($tujuanData['sasaran'] as $sasaranData) {
                            $sasaranData['tujuan_id'] = $tujuanId;
                            $sasaranId = $this->createSasaran($sasaranData);

                            // INDIKATOR SASARAN
                            if (!empty($sasaranData['indikator_sasaran']) && is_array($sasaranData['indikator_sasaran'])) {
                                foreach ($sasaranData['indikator_sasaran'] as $indSas) {
                                    $indikatorId = $this->createIndikatorSasaran([
                                        'sasaran_id' => $sasaranId,
                                        'indikator_sasaran' => $indSas['indikator_sasaran'] ?? '',
                                        'definisi_op' => $indSas['definisi_op'] ?? '',
                                        'satuan' => $indSas['satuan'] ?? '',
                                    ]);

                                    // TARGET (SASARAN)
                                    if (!empty($indSas['target_tahunan']) && is_array($indSas['target_tahunan'])) {
                                        foreach ($indSas['target_tahunan'] as $t) {
                                            $this->createTargetTahunan([
                                                'indikator_sasaran_id' => $indikatorId,
                                                'tahun' => (int) ($t['tahun'] ?? 0),
                                                'target_tahunan' => (string) ($t['target_tahunan'] ?? ''),
                                            ]);
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }

            $this->db->transComplete();
            if ($this->db->transStatus() === false) {
                throw new \Exception("Database transaction failed. All changes have been rolled back.");
            }

            return $misiId;

        } catch (\Exception $e) {
            $this->db->transRollback();
            throw $e;
        }
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
                    'updated_at' => date('Y-m-d H:i:s'), // FIX
                ]);
                $seenIds[] = $id;

            } elseif ($tahun) {
                if (isset($byYear[$tahun])) {
                    $tbl->where('id', (int) $byYear[$tahun]['id'])->update([
                        'target_tahunan' => $target,
                        'updated_at' => date('Y-m-d H:i:s'), // FIX
                    ]);
                    $seenIds[] = (int) $byYear[$tahun]['id'];
                } else {
                    $tbl->insert([
                        'indikator_tujuan_id' => $indikatorId,
                        'tahun' => $tahun,
                        'target_tahunan' => $target,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'), // FIX
                    ]);
                    $seenIds[] = (int) $this->db->insertID();
                }
            }
        }

        // delete yang tidak ada lagi di form
        if (!empty($byId)) {
            $idsToDelete = array_diff(array_keys($byId), $seenIds);
            if (!empty($idsToDelete)) {
                $tbl->whereIn('id', $idsToDelete)->delete();
            }
        }
    }

    public function updateCompleteRpjmdTransaction($misiId, $data)
    {
        $debugFile = WRITEPATH . 'debug_rpjmd_model.txt';
        file_put_contents($debugFile, "\n=== UPDATE COMPLETE RPJMD TRANSACTION - " . date('Y-m-d H:i:s') . " ===\n", FILE_APPEND);

        try {
            $this->db->transStart();

            // Update misi
            if (!empty($data['misi'])) {
                $this->updateMisi($misiId, $data['misi']);
            }

            // Track tujuan existing vs processed
            $existingTujuanIds = array_column($this->getTujuanByMisiId($misiId), 'id');
            $processedTujuanIds = [];

            if (!empty($data['tujuan']) && is_array($data['tujuan'])) {
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

                    // ============ INDIKATOR TUJUAN + TARGET TUJUAN ============
                    $existingIndTujuan = array_column($this->getIndikatorTujuanByTujuanId($tujuanId), 'id');
                    $processedIndTujuan = [];

                    if (!empty($tujuanData['indikator_tujuan']) && is_array($tujuanData['indikator_tujuan'])) {
                        foreach ($tujuanData['indikator_tujuan'] as $it) {
                            if (empty($it['indikator_tujuan']))
                                continue;

                            if (!empty($it['id'])) {
                                $indikatorId = (int) $it['id'];
                                $this->updateIndikatorTujuan($indikatorId, [
                                    'tujuan_id' => $tujuanId,
                                    'indikator_tujuan' => $it['indikator_tujuan'],
                                ]);
                            } else {
                                $indikatorId = $this->createIndikatorTujuan([
                                    'tujuan_id' => $tujuanId,
                                    'indikator_tujuan' => $it['indikator_tujuan'],
                                ]);
                            }
                            $processedIndTujuan[] = $indikatorId;

                            // sinkronisasi target_tahunan_tujuan
                            $targets = $it['target_tahunan_tujuan'] ?? [];
                            $this->syncTargetTujuanForIndikator($indikatorId, is_array($targets) ? $targets : []);
                        }
                    }

                    // hapus indikator_tujuan yang tidak diproses lagi
                    $toDeleteIndTujuan = array_diff($existingIndTujuan, $processedIndTujuan);
                    foreach ($toDeleteIndTujuan as $delId) {
                        $this->deleteIndikatorTujuan((int) $delId);
                    }

                    // ============ SASARAN + INDIKATOR SASARAN + TARGET ============
                    $existingSasaranIds = array_column($this->getSasaranByTujuanId($tujuanId), 'id');
                    $processedSasaranIds = [];

                    if (!empty($tujuanData['sasaran']) && is_array($tujuanData['sasaran'])) {
                        foreach ($tujuanData['sasaran'] as $sasData) {
                            if (empty($sasData['sasaran_rpjmd']))
                                continue;

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

                            $existingIndSasIds = array_column($this->getIndikatorSasaranBySasaranId($sasaranId), 'id');
                            $processedIndSasIds = [];

                            if (!empty($sasData['indikator_sasaran']) && is_array($sasData['indikator_sasaran'])) {
                                foreach ($sasData['indikator_sasaran'] as $is) {
                                    if (empty($is['indikator_sasaran']))
                                        continue;

                                    if (!empty($is['id'])) {
                                        $indId = (int) $is['id'];
                                        $this->updateIndikatorSasaran($indId, [
                                            'sasaran_id' => $sasaranId,
                                            'indikator_sasaran' => $is['indikator_sasaran'],
                                            'definisi_op' => $is['definisi_op'] ?? '',
                                            'satuan' => $is['satuan'] ?? '',
                                        ]);
                                    } else {
                                        $indId = $this->createIndikatorSasaran([
                                            'sasaran_id' => $sasaranId,
                                            'indikator_sasaran' => $is['indikator_sasaran'],
                                            'definisi_op' => $is['definisi_op'] ?? '',
                                            'satuan' => $is['satuan'] ?? '',
                                        ]);
                                    }
                                    $processedIndSasIds[] = $indId;

                                    // refresh target (hapus dulu biar tidak duplikat)
                                    $this->deleteTargetTahunanByIndikatorId($indId);
                                    if (!empty($is['target_tahunan']) && is_array($is['target_tahunan'])) {
                                        foreach ($is['target_tahunan'] as $t) {
                                            if (!empty($t['tahun'])) {
                                                $this->createTargetTahunan([
                                                    'indikator_sasaran_id' => $indId,
                                                    'tahun' => (int) $t['tahun'],
                                                    'target_tahunan' => (string) ($t['target_tahunan'] ?? ''),
                                                ]);
                                            }
                                        }
                                    }
                                }
                            }

                            // hapus indikator_sasaran yang tidak dipakai lagi
                            $toDeleteIndSas = array_diff($existingIndSasIds, $processedIndSasIds);
                            foreach ($toDeleteIndSas as $delId) {
                                $this->deleteIndikatorSasaran((int) $delId, true);
                            }
                        }
                    }

                    // hapus sasaran yang tidak dipakai lagi
                    $toDeleteSasaran = array_diff($existingSasaranIds, $processedSasaranIds);
                    foreach ($toDeleteSasaran as $delId) {
                        $this->deleteSasaran((int) $delId, true);
                    }
                }
            }

            // hapus tujuan yang tidak dipakai lagi
            $toDeleteTujuan = array_diff($existingTujuanIds, $processedTujuanIds);
            foreach ($toDeleteTujuan as $delId) {
                $this->deleteTujuan((int) $delId, true);
            }

            // cleanup orphan
            $this->cleanupOrphanedRecords($misiId);

            $this->db->transComplete();
            if ($this->db->transStatus() === false) {
                file_put_contents($debugFile, "ERROR: DB transaction failed\n", FILE_APPEND);
                throw new \Exception("Database transaction failed. All changes have been rolled back.");
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

    public function misiExists($id)
    {
        return $this->db->table('rpjmd_misi')->where('id', (int) $id)->countAllResults() > 0;
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

    public function findMisiIdForAnyEntity($id)
    {
        // misi?
        $misi = $this->getMisiById($id);
        if ($misi)
            return (int) $id;

        // tujuan?
        $mid = $this->findMisiIdByTujuanId($id);
        if ($mid)
            return (int) $mid;

        // sasaran?
        $mid = $this->findMisiIdBySasaranId($id);
        if ($mid)
            return (int) $mid;

        // indikator sasaran?
        $mid = $this->findMisiIdByIndikatorSasaranId($id);
        if ($mid)
            return (int) $mid;

        // target (sasaran)?
        $mid = $this->findMisiIdByTargetId($id);
        if ($mid)
            return (int) $mid;

        // indikator tujuan?
        $row = $this->db->table('rpjmd_indikator_tujuan it')
            ->join('rpjmd_tujuan t', 't.id = it.tujuan_id')
            ->select('t.misi_id')
            ->where('it.id', (int) $id)
            ->get()->getRowArray();
        if ($row && !empty($row['misi_id']))
            return (int) $row['misi_id'];

        // target_tujuan?
        $row = $this->db->table('rpjmd_target_tujuan ttt')
            ->join('rpjmd_indikator_tujuan it', 'it.id = ttt.indikator_tujuan_id')
            ->join('rpjmd_tujuan t', 't.id = it.tujuan_id')
            ->select('t.misi_id')
            ->where('ttt.id', (int) $id)
            ->get()->getRowArray();
        if ($row && !empty($row['misi_id']))
            return (int) $row['misi_id'];

        return null;
    }

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
}
