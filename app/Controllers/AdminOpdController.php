<?php

namespace App\Controllers;

use App\Controllers\BaseController;

class AdminOpdController extends BaseController
{
    protected $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }

    public function index()
    {
        /**
         * ================= RENSTRA (tabel renstra_sasaran) =================
         * Kolom status: Draft/Draf/Selesai
         */
        $renstraDraft = $this->db->table('renstra_sasaran')
            ->groupStart()
            ->where('status', 'Draft')
            ->orWhere('status', 'Draf')
            ->groupEnd()
            ->countAllResults();

        $renstraSelesai = $this->db->table('renstra_sasaran')
            ->where('status', 'Selesai')
            ->countAllResults();

        $renstraStats = [
            'draft' => $renstraDraft,
            'selesai' => $renstraSelesai,
        ];

        /**
         * ================= RENJA / RKT (tabel rkt) =================
         */
        $renjaDraft = $this->db->table('rkt')
            ->groupStart()
            ->where('status', 'Draft')
            ->orWhere('status', 'Draf')
            ->groupEnd()
            ->countAllResults();

        $renjaSelesai = $this->db->table('rkt')
            ->where('status', 'Selesai')
            ->countAllResults();

        $renjaStats = [
            'draft' => $renjaDraft,
            'selesai' => $renjaSelesai,
        ];

        /**
         * ================= IKU (tabel iku) =================
         * Asumsi kolom status berisi 'Tercapai' / 'Belum'
         */
        $ikuTercapai = $this->db->table('iku')
            ->where('status', 'Tercapai')
            ->countAllResults();

        $ikuBelum = $this->db->table('iku')
            ->where('status', 'Belum')
            ->countAllResults();

        $ikuStats = [
            'tercapai' => $ikuTercapai,
            'belum' => $ikuBelum,
        ];

        /**
         * ================= LAKIP (tabel lakip) =================
         * Tidak pakai filter tahun (langsung total per status)
         */
        $lakipSiap = $this->db->table('lakip')
            ->where('status', 'Siap')
            ->countAllResults();

        $lakipProses = $this->db->table('lakip')
            ->where('status', 'Proses')
            ->countAllResults();

        $lakipStats = [
            'siap' => $lakipSiap,
            'proses' => $lakipProses,
        ];

        /**
         * ================= PK (sementara 0 dulu) =================
         * Nanti kalau sudah jelas tabel PK-nya, baru kita sambungkan.
         */
        $pkStats = [
            'ditandatangani' => 0,
            'belum' => 0,
        ];

        return view('adminOpd/dashboard', [
            'title' => 'Dashboard e-SAKIP - Admin OPD',
            'renstraStats' => $renstraStats,
            'renjaStats' => $renjaStats,
            'ikuStats' => $ikuStats,
            'lakipStats' => $lakipStats,
            'pkStats' => $pkStats,
        ]);
    }
}
