<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\RpjmdModel;

class RpjmdController extends BaseController
{
    protected $rpjmdModel;

    public function __construct()
    {
        $this->rpjmdModel = new RpjmdModel();
    }
    private function xssPattern(): string
    {
        return '/^(?!.*<\s*script\b)(?!.*<\/\s*script\s*>)(?!.*javascript\s*:)(?!.*data\s*:\s*text\/html)(?!.*on\w+\s*=)(?!.*<\?php)(?!.*<\?).*$/is';
    }

    private function xssRule(): string
    {
        return 'regex_match[/^(?!.*<\s*script\b)(?!.*<\/\s*script\s*>)(?!.*javascript\s*:)(?!.*data\s*:\s*text\/html)(?!.*on\w+\s*=)(?!.*<\?php)(?!.*<\?).*$/is]';
    }

    /**
     * Validasi rekursif semua value string di array (tujuan nested) agar tidak ada script.
     * Return: [bool $ok, string $pathError]
     */
    private function validateNestedNoScript($data, string $path = 'tujuan'): array
    {
        if (!is_array($data))
            return [true, ''];

        foreach ($data as $k => $v) {
            $p = $path . '[' . $k . ']';

            if (is_array($v)) {
                [$ok, $err] = $this->validateNestedNoScript($v, $p);
                if (!$ok)
                    return [$ok, $err];
                continue;
            }

            // cek hanya untuk string
            if (is_string($v) && $v !== '') {
                if (!preg_match($this->xssPattern(), $v)) {
                    return [false, $p];
                }
            }
        }

        return [true, ''];
    }


    // ==================== MAIN RPJMD VIEWS ====================

    public function index()
    {
        // Ambil semua data lengkap (misi->tujuan->indikator_tujuan+target_tujuan, sasaran->indikator_sasaran+target)
        $allMisi = $this->rpjmdModel->getCompleteRpjmdStructure();

        // Kelompokkan per periode: tahun_mulai - tahun_akhir
        $groupedData = [];
        foreach ($allMisi as $misi) {
            $periodKey = $misi['tahun_mulai'] . '-' . $misi['tahun_akhir'];

            if (!isset($groupedData[$periodKey])) {
                $groupedData[$periodKey] = [
                    'period' => $periodKey,
                    'tahun_mulai' => (int) $misi['tahun_mulai'],
                    'tahun_akhir' => (int) $misi['tahun_akhir'],
                    'years' => range((int) $misi['tahun_mulai'], (int) $misi['tahun_akhir']),
                    'misi_data' => [],
                ];
            }
            $groupedData[$periodKey]['misi_data'][] = $misi;
        }

        // Urutkan key periode
        ksort($groupedData);

        $data['rpjmd_grouped'] = $groupedData;
        $data['rpjmd_data'] = $allMisi;

        // Ringkasan
        $summary = $this->rpjmdModel->getRpjmdSummary();
        $data['rpjmd_summary'] = $summary;

        // Tahun yang tersedia (fallback kalau kosong)
        $availableYears = $summary['years_available'] ?? [];
        $data['available_years'] = array_map(static function ($row) {
            return (int) $row['tahun'];
        }, $availableYears);
        sort($data['available_years']);

        if (empty($data['available_years'])) {
            // fallback 5 tahun umum (bukan patokan header, hanya untuk kompatibilitas lama)
            $data['available_years'] = [2025, 2026, 2027, 2028, 2029];
        }
        // dd($data['rpjmd_data'][0]['tujuan'][0]);

        return view('adminKabupaten/rpjmd/rpjmd', $data);
    }

    public function tambah()
    {
        // (Opsional) data dropdown; form sudah dinamis
        $data['misi_list'] = $this->rpjmdModel->getAllMisi();
        $data['tujuan_list'] = $this->rpjmdModel->getAllTujuan();
        $data['sasaran_list'] = $this->rpjmdModel->getAllSasaran();
        $data['indikator_sasaran_list'] = $this->rpjmdModel->getAllIndikatorSasaran();

        return view('adminKabupaten/rpjmd/tambah_rpjmd', $data);
    }

    public function save()
    {
        $db = \Config\Database::connect();
        $db->transBegin();

        try {
            $post = $this->request->getPost();

            /* =======================
             |  SIMPAN MISI
             ======================= */
            $db->table('rpjmd_misi')->insert([
                'misi' => $post['misi'],
                'tahun_mulai' => (int) $post['tahun_mulai'],
                'tahun_akhir' => (int) $post['tahun_akhir'],
                'status' => 'draft'
            ]);

            $misiId = $db->insertID();

            /* =======================
             |  TUJUAN
             ======================= */
            foreach (($post['tujuan'] ?? []) as $tujuan) {

                $db->table('rpjmd_tujuan')->insert([
                    'misi_id' => $misiId,
                    'tujuan_rpjmd' => $tujuan['tujuan_rpjmd']
                ]);
                $tujuanId = $db->insertID();

                /* =======================
                 |  INDIKATOR TUJUAN
                 ======================= */
                foreach (($tujuan['indikator_tujuan'] ?? []) as $it) {

                    $db->table('rpjmd_indikator_tujuan')->insert([
                        'tujuan_id' => $tujuanId,
                        'indikator_tujuan' => $it['indikator_tujuan']
                    ]);
                    $itId = $db->insertID();

                    foreach (($it['target_tahunan_tujuan'] ?? []) as $tt) {
                        $db->table('rpjmd_target_tujuan')->insert([
                            'indikator_tujuan_id' => $itId,
                            'tahun' => (int) $tt['tahun'],
                            'target_tahunan' => trim((string) $tt['target_tahunan']),
                        ]);
                    }
                }

                /* =======================
                 |  SASARAN
                 ======================= */
                foreach (($tujuan['sasaran'] ?? []) as $sas) {

                    $db->table('rpjmd_sasaran')->insert([
                        'tujuan_id' => $tujuanId,
                        'sasaran_rpjmd' => $sas['sasaran_rpjmd']
                    ]);
                    $sasaranId = $db->insertID();

                    foreach (($sas['indikator_sasaran'] ?? []) as $is) {

                        $db->table('rpjmd_indikator_sasaran')->insert([
                            'sasaran_id' => $sasaranId,
                            'indikator_sasaran' => $is['indikator_sasaran'],
                            'definisi_op' => $is['definisi_op'],
                            'satuan' => $is['satuan'],
                            'jenis_indikator' => $is['jenis_indikator']
                        ]);
                        $isId = $db->insertID();

                        foreach (($is['target_tahunan'] ?? []) as $tt) {
                            $db->table('rpjmd_target')->insert([
                                'indikator_sasaran_id' => $isId,
                                'tahun' => (int) $tt['tahun'],
                                'target_tahunan' => trim((string) $tt['target_tahunan']),
                            ]);
                        }
                    }
                }
            }

            if ($db->transStatus() === false) {
                $db->transRollback();
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'Gagal menambahkan data RPJMD');
            }

            $db->transCommit();

            return redirect()->to(base_url('adminkab/rpjmd'))
                ->with('success', 'RPJMD berhasil ditambahkan');
        } catch (\Throwable $e) {
            $db->transRollback();
            log_message('error', 'SAVE RPJMD ERROR: ' . $e->getMessage());

            return redirect()->back()
                ->withInput()
                ->with('error', 'Terjadi kesalahan saat menyimpan RPJMD');
        }
    }

    /* =======================
     |  HELPER NORMALISASI ANGKA
     ======================= */
    private function normalizeNumber($value)
    {
        if ($value === null || $value === '')
            return null;

        // ganti koma ke titik
        $value = str_replace(',', '.', $value);

        return is_numeric($value) ? (float) $value : null;
    }


    public function edit($id = null)
    {
        if ($id === null) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('ID tidak ditemukan');
        }

        // Terima ID apa pun (misi/tujuan/sasaran/indikator/target), cari misi parent-nya
        $misiId = $this->rpjmdModel->findMisiIdForAnyEntity((int) $id);
        if (!$misiId) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('Data RPJMD tidak ditemukan');
        }

        $data['misi'] = $this->rpjmdModel->getMisiById($misiId);
        if (!$data['misi']) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('Data RPJMD tidak ditemukan');
        }

        // Ambil struktur lengkap & saring untuk misi ini
        $complete = $this->rpjmdModel->getCompleteRpjmdStructure();
        $data['rpjmd_complete'] = null;
        foreach ($complete as $misiData) {
            if ((int) $misiData['id'] === (int) $misiId) {
                $data['rpjmd_complete'] = $misiData;
                break;
            }
        }

        // (Opsional) data dropdown
        $data['misi_list'] = $this->rpjmdModel->getAllMisi();
        $data['tujuan_list'] = $this->rpjmdModel->getAllTujuan();
        $data['sasaran_list'] = $this->rpjmdModel->getAllSasaran();
        $data['indikator_sasaran_list'] = $this->rpjmdModel->getAllIndikatorSasaran();

        return view('adminKabupaten/rpjmd/edit_rpjmd', $data);
    }

    public function update()
    {
        $db = \Config\Database::connect();
        $db->transBegin();

        try {
            $post = $this->request->getPost();
            $misiId = (int) ($post['id'] ?? 0);

            if (!$misiId) {
                throw new \Exception('ID RPJMD tidak valid');
            }

            if (empty($post['tujuan']) || !is_array($post['tujuan'])) {
                throw new \Exception('Data tujuan tidak valid');
            }

            /* =======================
             |  HITUNG PERIODE DI SERVER
             ======================= */
            $tahunMulai = (int) ($post['tahun_mulai'] ?? 0);
            if ($tahunMulai <= 0) {
                throw new \Exception('Tahun mulai tidak valid');
            }
            $tahunAkhir = (int) ($post['tahun_akhir'] ?? 0);

            if ($tahunAkhir <= $tahunMulai) {
                throw new \Exception('Tahun akhir tidak valid');
            }

            /* =======================
             |  UPDATE MISI
             ======================= */
            $db->table('rpjmd_misi')
                ->where('id', $misiId)
                ->update([
                    'misi' => $post['misi'],
                    'tahun_mulai' => $tahunMulai,
                    'tahun_akhir' => $tahunAkhir,
                ]);

            /* ======================================================
             |  HAPUS TOTAL DATA LAMA (URUTAN ANAK → INDUK)
             ====================================================== */

            $tujuanList = $db->table('rpjmd_tujuan')
                ->where('misi_id', $misiId)
                ->get()->getResultArray();

            foreach ($tujuanList as $tujuan) {
                $tujuanId = (int) $tujuan['id'];

                // ===== SASARAN =====
                $sasaranList = $db->table('rpjmd_sasaran')
                    ->where('tujuan_id', $tujuanId)
                    ->get()->getResultArray();

                foreach ($sasaranList as $sas) {
                    $sasaranId = (int) $sas['id'];

                    // INDIKATOR SASARAN
                    $indikatorSasaran = $db->table('rpjmd_indikator_sasaran')
                        ->where('sasaran_id', $sasaranId)
                        ->get()->getResultArray();

                    foreach ($indikatorSasaran as $is) {
                        $db->table('rpjmd_target')
                            ->where('indikator_sasaran_id', $is['id'])
                            ->delete();
                    }

                    $db->table('rpjmd_indikator_sasaran')
                        ->where('sasaran_id', $sasaranId)
                        ->delete();
                }

                $db->table('rpjmd_sasaran')
                    ->where('tujuan_id', $tujuanId)
                    ->delete();

                // ===== INDIKATOR TUJUAN =====
                $indikatorTujuan = $db->table('rpjmd_indikator_tujuan')
                    ->where('tujuan_id', $tujuanId)
                    ->get()->getResultArray();

                foreach ($indikatorTujuan as $it) {
                    $db->table('rpjmd_target_tujuan')
                        ->where('indikator_tujuan_id', $it['id'])
                        ->delete();
                }

                $db->table('rpjmd_indikator_tujuan')
                    ->where('tujuan_id', $tujuanId)
                    ->delete();
            }

            $db->table('rpjmd_tujuan')
                ->where('misi_id', $misiId)
                ->delete();

            /* ======================================================
             |  INSERT DATA BARU
             ====================================================== */

            // 🔥 NORMALISASI INDEX TUJUAN
            $post['tujuan'] = array_values($post['tujuan']);

            foreach ($post['tujuan'] as $tujuan) {

                $db->table('rpjmd_tujuan')->insert([
                    'misi_id' => $misiId,
                    'tujuan_rpjmd' => $tujuan['tujuan_rpjmd'],
                ]);
                $tujuanId = $db->insertID();

                /* ===== INDIKATOR TUJUAN ===== */
                $indikatorTujuan = array_values($tujuan['indikator_tujuan'] ?? []);
                foreach ($indikatorTujuan as $it) {

                    $db->table('rpjmd_indikator_tujuan')->insert([
                        'tujuan_id' => $tujuanId,
                        'indikator_tujuan' => $it['indikator_tujuan'],
                    ]);
                    $itId = $db->insertID();

                    // cegah target dobel tahun
                    $targets = [];
                    foreach (($it['target_tahunan_tujuan'] ?? []) as $tt) {
                        if (isset($tt['tahun'])) {
                            $targets[(int) $tt['tahun']] = $tt;
                        }
                    }

                    foreach ($targets as $tt) {
                        log_message('debug', 'TARGET TUJUAN: ' . json_encode($tt));
                        if (!isset($tt['target_tahunan']) || trim((string)$tt['target_tahunan']) === '') {
                            continue;
                        }

                        $db->table('rpjmd_target_tujuan')->insert([
                            'indikator_tujuan_id' => $itId,
                            'tahun' => (int) $tt['tahun'],
                            'target_tahunan' => trim((string) $tt['target_tahunan']),
                        ]);
                    }
                }

                /* ===== SASARAN ===== */
                $sasaranList = array_values($tujuan['sasaran'] ?? []);
                foreach ($sasaranList as $sas) {

                    $db->table('rpjmd_sasaran')->insert([
                        'tujuan_id' => $tujuanId,
                        'sasaran_rpjmd' => $sas['sasaran_rpjmd'],
                    ]);
                    $sasaranId = $db->insertID();

                    $indikatorSasaran = array_values($sas['indikator_sasaran'] ?? []);
                    foreach ($indikatorSasaran as $is) {

                        $db->table('rpjmd_indikator_sasaran')->insert([
                            'sasaran_id' => $sasaranId,
                            'indikator_sasaran' => $is['indikator_sasaran'],
                            'definisi_op' => $is['definisi_op'],
                            'satuan' => $is['satuan'],
                            'jenis_indikator' => $is['jenis_indikator'],
                        ]);
                        $isId = $db->insertID();

                        // cegah target dobel tahun
                        $targets = [];
                        foreach (($is['target_tahunan'] ?? []) as $tt) {
                            if (isset($tt['tahun'])) {
                                $targets[(int) $tt['tahun']] = $tt;
                            }
                        }

                        foreach ($targets as $tt) {
                            log_message('debug', 'TARGET TUJUAN: ' . json_encode($tt));
                            if (!isset($tt['target_tahunan']) || trim((string)$tt['target_tahunan']) === '') {
                                continue;
                            }

                            $db->table('rpjmd_target')->insert([
                                'indikator_sasaran_id' => $isId,
                                'tahun' => (int) $tt['tahun'],
                                'target_tahunan' => trim((string) $tt['target_tahunan']),
                            ]);
                        }
                    }
                }
            }


            if ($db->transStatus() === false) {
                $db->transRollback();
                throw new \Exception('Transaction failed');
            }

            $db->transCommit();

            return redirect()->to(base_url('adminkab/rpjmd'))
                ->with('success', 'RPJMD berhasil diperbarui');
        } catch (\Throwable $e) {
            $db->transRollback();
            log_message('error', 'UPDATE RPJMD ERROR: ' . $e->getMessage());

            return redirect()->back()
                ->withInput()
                ->with('error', 'Gagal memperbarui RPJMD');
        }
    }

    public function delete($id)
    {
        $id = (int) $id;

        $misiId = $this->rpjmdModel->findMisiIdForAnyEntity($id);

        if (!$misiId) {
            return redirect()->back()
                ->with('error', 'Data RPJMD tidak ditemukan');
        }

        if (!$this->rpjmdModel->deleteMisi($misiId)) {
            return redirect()->back()
                ->with('error', 'Gagal menghapus RPJMD. Data relasi bermasalah.');
        }

        return redirect()->to(base_url('adminkab/rpjmd'))
            ->with('success', 'RPJMD berhasil dihapus');
    }

    // ==================== STATUS (AJAX) ====================

    public function updateStatus()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Invalid request',
            ]);
        }

        $json = $this->request->getJSON(true);
        $id = isset($json['id']) ? (int) $json['id'] : 0;

        if (!$id) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'ID harus diisi',
            ]);
        }

        try {
            $currentMisi = $this->rpjmdModel->getMisiById($id);
            if (!$currentMisi) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Data tidak ditemukan',
                ]);
            }

            $currentStatus = $currentMisi['status'] ?? 'draft';
            $newStatus = $currentStatus === 'draft' ? 'selesai' : 'draft';

            $res = $this->rpjmdModel->updateMisiStatus($id, $newStatus);
            if ($res) {
                return $this->response->setJSON([
                    'success' => true,
                    'message' => 'Status berhasil diupdate',
                    'oldStatus' => $currentStatus,
                    'newStatus' => $newStatus,
                ]);
            }

            return $this->response->setJSON([
                'success' => false,
                'message' => 'Gagal mengupdate status',
            ]);
        } catch (\Throwable $e) {
            return $this->response->setJSON([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
