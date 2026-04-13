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
         * Kolom status: 'selesai' / 'draft'
         */
        $ikuSelesai = $this->db->table('iku')
            ->where('status', 'selesai')
            ->countAllResults();

        $ikuDraft = $this->db->table('iku')
            ->groupStart()
                ->where('status', 'draft')
                ->orWhere('status IS NULL OR TRIM(status) = ', "''", false)
            ->groupEnd()
            ->countAllResults();

        $ikuStats = [
            'selesai' => $ikuSelesai,
            'draft'   => $ikuDraft,
        ];

        /**
         * ================= LAKIP (tabel lakip) =================
         * Kolom status: 'selesai' / 'draft'
         */
        $lakipSelesai = $this->db->table('lakip')
            ->where('status', 'selesai')
            ->countAllResults();

        $lakipDraft = $this->db->table('lakip')
            ->groupStart()
                ->where('status', 'draft')
                ->orWhere('status IS NULL OR TRIM(status) = ', "''", false)
            ->groupEnd()
            ->countAllResults();

        $lakipStats = [
            'selesai' => $lakipSelesai,
            'draft'   => $lakipDraft,
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
