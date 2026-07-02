<?php

namespace App\Controllers\AdminKab;

use App\Controllers\BaseController;
use App\Models\PegawaiModel;
use App\Models\OpdModel;
use App\Libraries\Pegawai\PegawaiSyncService;
use App\Libraries\Pegawai\ProviderNotConfiguredException;

/**
 * Manajemen data pegawai (edit manual jabatan & OPD) + halaman sinkronisasi
 * SIMPEG/SIKASN. Edit manual selalu tersedia; sinkron otomatis aktif begitu
 * provider dikonfigurasi di .env.
 */
class PegawaiController extends BaseController
{
    protected PegawaiModel $pegawaiModel;
    protected OpdModel $opdModel;

    public function __construct()
    {
        $this->pegawaiModel = new PegawaiModel();
        $this->opdModel     = new OpdModel();
    }

    private function isSafeText($val): bool
    {
        if ($val === null || $val === '') {
            return true;
        }
        return (bool) preg_match(
            '/^(?!.*<\s*script\b)(?!.*<\/\s*script\s*>)(?!.*javascript\s*:)(?!.*data\s*:\s*text\/html)(?!.*on\w+\s*=)(?!.*<\?php)(?!.*<\?).*$/is',
            (string) $val
        );
    }

    /* ===================== LIST PEGAWAI ===================== */
    public function index()
    {
        $opdId  = $this->request->getGet('opd_id');
        $opdId  = ($opdId === '' || $opdId === null) ? null : (int) $opdId;
        $search = trim((string) $this->request->getGet('q'));

        $data = [
            'title'    => 'Manajemen Pegawai',
            'pegawai'  => $this->pegawaiModel->getPegawaiList($opdId, $search ?: null),
            'opdList'  => $this->opdModel->orderBy('nama_opd', 'ASC')->findAll(),
            'filters'  => ['opd_id' => $opdId, 'q' => $search],
        ];

        return view('adminKabupaten/pegawai/index', $data);
    }

    /* ===================== EDIT PEGAWAI ===================== */
    public function edit($id = null)
    {
        $pegawai = $this->pegawaiModel->getPegawaiDetail((int) $id);
        if (!$pegawai) {
            return redirect()->to('adminkab/pegawai')->with('error', 'Data pegawai tidak ditemukan.');
        }

        $data = [
            'title'          => 'Edit Pegawai',
            'pegawai'        => $pegawai,
            'opdList'        => $this->opdModel->orderBy('nama_opd', 'ASC')->findAll(),
            'jabatanList'    => $this->pegawaiModel->getJabatanList(),
            'pangkatOptions' => $this->pegawaiModel->getPangkatOptions(),
        ];

        return view('adminKabupaten/pegawai/edit', $data);
    }

    public function update($id = null)
    {
        $id = (int) $id;
        $pegawai = $this->pegawaiModel->getPegawaiDetail($id);
        if (!$pegawai) {
            return redirect()->to('adminkab/pegawai')->with('error', 'Data pegawai tidak ditemukan.');
        }

        $rules = [
            'opd_id'     => 'required|integer',
            'jabatan_id' => 'required|integer',
            'pangkat_id' => 'permit_empty|integer',
        ];
        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()
                ->with('error', implode(' | ', $this->validator->getErrors()));
        }

        $set = [
            'opd_id'     => (int) $this->request->getPost('opd_id'),
            'jabatan_id' => (int) $this->request->getPost('jabatan_id'),
        ];
        $pangkatId = $this->request->getPost('pangkat_id');
        if ($pangkatId !== null && $pangkatId !== '') {
            $set['pangkat_id'] = (int) $pangkatId;
        }
        $set['edited_by'] = session()->get('user_id') ?? session()->get('id_pegawai');

        $this->pegawaiModel->update($id, $set);

        return redirect()->to('adminkab/pegawai')
            ->with('success', 'Data pegawai berhasil diperbarui.');
    }

    /* ===================== KELOLA JABATAN (RENAME) ===================== */
    public function jabatan()
    {
        $opdId = $this->request->getGet('opd_id');
        $opdId = ($opdId === '' || $opdId === null) ? null : (int) $opdId;

        $data = [
            'title'       => 'Kelola Jabatan',
            'jabatanList' => $this->pegawaiModel->getJabatanList($opdId),
            'opdList'     => $this->opdModel->orderBy('nama_opd', 'ASC')->findAll(),
            'filters'     => ['opd_id' => $opdId],
        ];

        return view('adminKabupaten/pegawai/jabatan', $data);
    }

    public function updateJabatan($id = null)
    {
        $id = (int) $id;
        $jabatan = $this->pegawaiModel->getJabatanById($id);
        if (!$jabatan) {
            return redirect()->to('adminkab/pegawai/jabatan')->with('error', 'Jabatan tidak ditemukan.');
        }

        $namaJabatan = trim((string) $this->request->getPost('nama_jabatan'));
        if ($namaJabatan === '') {
            return redirect()->back()->with('error', 'Nama jabatan wajib diisi.');
        }
        if (!$this->isSafeText($namaJabatan)) {
            return redirect()->back()->with('error', 'Nama jabatan mengandung input berbahaya.');
        }

        $opdId  = $this->request->getPost('opd_id');
        $eselon = $this->request->getPost('eselon');

        $set = [
            'nama_jabatan' => $namaJabatan,
            'opd_id'       => ($opdId === '' || $opdId === null) ? null : (int) $opdId,
            'eselon'       => ($eselon === '' || $eselon === null) ? null : (int) $eselon,
            'edited_by'    => session()->get('user_id') ?? session()->get('id_pegawai'),
        ];

        \Config\Database::connect()->table('jabatan')->where('id', $id)->update($set);

        return redirect()->to('adminkab/pegawai/jabatan')
            ->with('success', 'Nama jabatan berhasil diperbarui.');
    }

    /* ===================== SINKRON SIMPEG ===================== */
    public function sync()
    {
        $service    = new PegawaiSyncService();
        $configured = $service->isConfigured();
        $preview    = null;
        $error      = null;

        // jalankan pratinjau hanya bila diminta (memanggil API)
        if ($configured && $this->request->getGet('preview')) {
            try {
                $preview = $service->preview();
            } catch (ProviderNotConfiguredException $e) {
                $error = $e->getMessage();
            } catch (\Throwable $e) {
                $error = 'Gagal mengambil data SIMPEG: ' . $e->getMessage();
            }
        }

        return view('adminKabupaten/pegawai/sync', [
            'title'      => 'Sinkron Pegawai dari SIMPEG',
            'configured' => $configured,
            'preview'    => $preview,
            'syncError'  => $error,
        ]);
    }

    public function runSync()
    {
        $entity  = (string) ($this->request->getPost('entity') ?: 'all');
        $allowed = ['opd', 'pangkat', 'jabatan', 'pegawai', 'all'];
        if (!in_array($entity, $allowed, true)) {
            return redirect()->to('adminkab/pegawai/sync')->with('error', 'Entitas sync tidak dikenal.');
        }

        // Tujuan redirect: kembali ke tab Master Data bila dipicu dari sana,
        // selain itu ke halaman sinkron lengkap.
        $back = (string) $this->request->getPost('back');
        $dest = 'adminkab/pegawai/sync?preview=1';
        if (in_array($back, ['pegawai', 'pangkat', 'jabatan', 'opd'], true)) {
            $dest = 'adminkab/master?tab=' . $back;
        }

        $service = new PegawaiSyncService();
        try {
            $r   = $service->syncEntity($entity);
            $msg = $this->syncMessage($r);
            return redirect()->to($dest)->with('success', $msg);
        } catch (ProviderNotConfiguredException $e) {
            return redirect()->to($dest)->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            return redirect()->to($dest)->with('error', 'Sinkron gagal: ' . $e->getMessage());
        }
    }

    /** Susun pesan ringkas dari hasil sync (hanya bagian yang ada). */
    private function syncMessage(array $r): string
    {
        $parts = [];
        if (isset($r['opd_baru']))     { $parts[] = "OPD {$r['opd_baru']} baru / {$r['opd_update']} diperbarui"; }
        if (isset($r['pangkat_baru'])) { $parts[] = "Pangkat {$r['pangkat_baru']} baru / {$r['pangkat_update']} diperbarui"; }
        if (isset($r['jabatan_baru'])) { $parts[] = "Jabatan {$r['jabatan_baru']} baru / {$r['jabatan_update']} diperbarui"; }
        if (isset($r['pegawai_baru'])) { $parts[] = "Pegawai {$r['pegawai_baru']} baru / {$r['pegawai_update']} diperbarui"; }
        return 'Sinkron selesai — ' . (empty($parts) ? 'tidak ada perubahan' : implode(' · ', $parts)) . '.';
    }
}
