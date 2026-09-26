<?php

namespace App\Controllers\AdminKab;

use App\Controllers\BaseController;
use App\Models\CapaianPkBupatiHistorisModel;
use App\Services\KabupatenDashboardService;

/**
 * Dashboard -> Capaian PK Bupati tahun sebelumnya (input MANUAL).
 *
 * Mengisi titik grafik "Tren Capaian PK Bupati" untuk tahun sebelum
 * KabupatenDashboardService::TAHUN_MULAI_ENGINE; tahun sesudahnya dihitung
 * mesin dari LAKIP Kabupaten dan tidak bisa diinput di sini.
 *
 * Rute: adminkab/dashboard/capaian-historis (grup admin_kab/admin/inspektorat;
 * inspektorat hanya boleh melihat).
 */
class CapaianHistorisController extends BaseController
{
    private CapaianPkBupatiHistorisModel $model;

    public function __construct()
    {
        $this->model = new CapaianPkBupatiHistorisModel();
    }

    /** @return int[] tahun yang boleh diinput, terbaru dulu */
    private function daftarTahun(): array
    {
        $akhir = KabupatenDashboardService::TAHUN_MULAI_ENGINE - 1;
        // Cukup untuk mengisi grafik 5 tahun beberapa tahun ke depan
        // sekaligus tahun-tahun yang masih tampil sekarang.
        return range($akhir, $akhir - 9);
    }

    private function bolehUbah(): bool
    {
        return (new KabupatenDashboardService())->isReadOnly((string) session()->get('role')) === false;
    }

    public function index()
    {
        $tahun = $this->daftarTahun();

        return view('adminKabupaten/capaian_historis/index', [
            'title'       => 'Capaian PK Bupati Tahun Sebelumnya',
            'tahunList'   => $tahun,
            'peta'        => $this->model->petaTahun(min($tahun), max($tahun)),
            'tabelAda'    => $this->model->tersedia(),
            'bolehUbah'   => $this->bolehUbah(),
            'mulaiEngine' => KabupatenDashboardService::TAHUN_MULAI_ENGINE,
        ]);
    }

    public function save()
    {
        if (!$this->bolehUbah()) {
            return redirect()->to(base_url('unauthorized'))
                ->with('error', 'Anda hanya dapat melihat data ini.');
        }
        if (!$this->model->tersedia()) {
            return redirect()->back()
                ->with('error', 'Tabel capaian_pk_bupati_historis belum dibuat. Jalankan db/update_2026-09-26_capaian_pk_bupati_historis.sql.');
        }

        $post = $this->request->getPost('rows');
        if (!is_array($post)) {
            return redirect()->back()->with('error', 'Tidak ada data yang dikirim.');
        }

        $sah = $this->daftarTahun();
        $isi = [];
        foreach ($post as $tahun => $r) {
            $tahun = (int) $tahun;
            // Tahun mesin (>= TAHUN_MULAI_ENGINE) atau karangan ditolak.
            if (!in_array($tahun, $sah, true) || !is_array($r)) {
                return redirect()->back()->withInput()
                    ->with('error', 'Tahun ' . $tahun . ' tidak dapat diinput manual.');
            }

            $teks = trim(str_replace(['%', ' '], '', (string) ($r['capaian'] ?? '')));
            $ket  = trim((string) ($r['keterangan'] ?? ''));

            if ($teks === '') {
                $isi[$tahun] = ['capaian' => null, 'keterangan' => null];
                continue;
            }

            $angka = str_replace(',', '.', $teks);
            if (!is_numeric($angka) || (float) $angka < -999999 || (float) $angka > 999999) {
                return redirect()->back()->withInput()
                    ->with('error', 'Capaian tahun ' . $tahun . ' harus berupa angka persen (mis. 87,5).');
            }
            if (preg_match('/[<>]/', $ket)) {
                return redirect()->back()->withInput()
                    ->with('error', 'Keterangan tahun ' . $tahun . ' tidak boleh mengandung karakter < >.');
            }

            $isi[$tahun] = [
                'capaian'    => round((float) $angka, 2),
                'keterangan' => $ket === '' ? null : mb_substr($ket, 0, 255),
            ];
        }

        try {
            $this->model->simpanSemua($isi, (int) session()->get('user_id') ?: null);
        } catch (\Throwable $e) {
            return redirect()->back()->withInput()
                ->with('error', pesanGalatBerawalan($e, 'Capaian tahun sebelumnya gagal disimpan', 'kab.capaianHistoris'));
        }

        log_activity(
            'update',
            'capaian_pk_bupati_historis',
            'Capaian PK Bupati tahun sebelumnya diperbarui: ' . implode(', ', array_map(
                static fn ($th, $r) => $th . '=' . ($r['capaian'] === null ? 'kosong' : $r['capaian'] . '%'),
                array_keys($isi),
                $isi
            ))
        );

        return redirect()->to(base_url('adminkab/dashboard/capaian-historis'))
            ->with('success', 'Capaian PK Bupati tahun sebelumnya berhasil disimpan.');
    }
}
