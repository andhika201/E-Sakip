<?php

namespace App\Controllers\AdminKab;

use App\Controllers\BaseController;
use App\Models\DashboardThresholdModel;

/**
 * Pengaturan Dashboard -> Ambang Status Capaian (KHUSUS Super Admin).
 *
 * Mengatur rentang persentase, nama, warna, dan ikon status capaian yang
 * dipakai seluruh dashboard. Nilai `code` sengaja TIDAK dapat diubah lewat
 * form karena dirujuk langsung oleh kode program (mis. `critical` untuk
 * insight "indikator kritis"); yang bebas diganti adalah namanya.
 *
 * Rute: adminkab/dashboard-thresholds (grup auth:admin di app/Config/Routes.php).
 */
class DashboardThresholdController extends BaseController
{
    private DashboardThresholdModel $model;

    public function __construct()
    {
        $this->model = new DashboardThresholdModel();
    }

    public function index()
    {
        return view('adminKabupaten/dashboard_threshold/index', [
            'rows'    => $this->model->semua(),
            'colors'  => DashboardThresholdModel::COLORS,
            'icons'   => DashboardThresholdModel::ICONS,
        ]);
    }

    public function save()
    {
        $post = $this->request->getPost('rows');
        if (!is_array($post) || $post === []) {
            return redirect()->back()->with('error', 'Tidak ada data yang dikirim.');
        }

        // `code` hanya boleh salah satu yang memang sudah terdaftar — kiriman
        // dengan kode karangan ditolak, bukan diam-diam dibuatkan barisnya.
        $kodeSah = array_column($this->model->semua(), 'code');
        if ($kodeSah === []) {
            $kodeSah = DashboardThresholdModel::KNOWN_CODES;
        }

        $rows = [];
        foreach ($post as $code => $r) {
            $code = (string) $code;
            if (!in_array($code, $kodeSah, true)) {
                return redirect()->back()->withInput()
                    ->with('error', 'Kode status "' . esc($code) . '" tidak dikenal.');
            }

            $nama = trim((string) ($r['name'] ?? ''));
            if ($nama === '' || preg_match('/[<>]/', $nama)) {
                return redirect()->back()->withInput()
                    ->with('error', 'Nama status tidak boleh kosong atau mengandung karakter < >.');
            }

            $min = $this->angka($r['min_value'] ?? null);
            $max = $this->angka($r['max_value'] ?? null);
            if ($min === false || $max === false) {
                return redirect()->back()->withInput()
                    ->with('error', 'Nilai minimum/maksimum harus berupa angka (kosongkan bila tanpa batas).');
            }

            $rows[] = [
                'code'       => $code,
                'name'       => mb_substr($nama, 0, 100),
                'min_value'  => $min,
                'max_value'  => $max,
                'color'      => (string) ($r['color'] ?? 'abu'),
                'icon'       => (string) ($r['icon'] ?? ''),
                'sort_order' => (int) ($r['sort_order'] ?? 0),
                'is_active'  => !empty($r['is_active']) ? 1 : 0,
            ];
        }

        $errors = $this->model->validasiRentang($rows);
        if ($errors !== []) {
            return redirect()->back()->withInput()->with('error', implode(' ', $errors));
        }

        $this->model->simpanSemua($rows, (int) session()->get('user_id') ?: null);

        log_activity(
            'update',
            'dashboard_threshold',
            'Ambang status capaian diperbarui: ' . implode(', ', array_map(
                static fn ($r) => $r['code'] . ' [' . ($r['min_value'] ?? '-') . '..' . ($r['max_value'] ?? '∞') . ']'
                    . ($r['is_active'] ? '' : ' (nonaktif)'),
                $rows
            ))
        );

        return redirect()->to(base_url('adminkab/dashboard-thresholds'))
            ->with('success', 'Ambang status capaian berhasil disimpan.');
    }

    public function reset()
    {
        $this->model->resetToDefault((int) session()->get('user_id') ?: null);

        log_activity('reset', 'dashboard_threshold', 'Ambang status capaian dikembalikan ke konfigurasi bawaan.');

        return redirect()->to(base_url('adminkab/dashboard-thresholds'))
            ->with('success', 'Ambang status capaian dikembalikan ke bawaan.');
    }

    /**
     * Teks -> desimal. Kosong = null (tanpa batas), bukan angka = false.
     *
     * @return float|null|false
     */
    private function angka($nilai)
    {
        if ($nilai === null) {
            return null;
        }
        $teks = trim((string) $nilai);
        if ($teks === '') {
            return null;
        }
        $teks = str_replace(',', '.', $teks);

        return is_numeric($teks) ? round((float) $teks, 2) : false;
    }
}
