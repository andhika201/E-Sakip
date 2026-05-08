<?php

namespace App\Models;

use CodeIgniter\Model;

class DashboardOpdModel extends Model
{
    protected $db;

    public function __construct()
    {
        parent::__construct();
        $this->db = \Config\Database::connect();
    }

    public function getRenstraStats()
    {
        $draft = $this->db->table('renstra_sasaran')
            ->groupStart()
            ->where('status', 'Draft')
            ->orWhere('status', 'Draf')
            ->groupEnd()
            ->countAllResults();

        $selesai = $this->db->table('renstra_sasaran')
            ->where('status', 'Selesai')
            ->countAllResults();

        return [
            'draft' => $draft,
            'selesai' => $selesai,
        ];
    }

    public function getRenjaStats()
    {
        $draft = $this->db->table('rkt')
            ->groupStart()
            ->where('status', 'Draft')
            ->orWhere('status', 'Draf')
            ->groupEnd()
            ->countAllResults();

        $selesai = $this->db->table('rkt')
            ->where('status', 'Selesai')
            ->countAllResults();

        return [
            'draft' => $draft,
            'selesai' => $selesai,
        ];
    }

    public function getIkuStats()
    {
        $selesai = $this->db->table('iku')
            ->where('status', 'selesai')
            ->countAllResults();

        $draft = $this->db->table('iku')
            ->groupStart()
                ->where('status', 'draft')
                ->orWhere('status IS NULL OR TRIM(status) = ', "''", false)
            ->groupEnd()
            ->countAllResults();

        return [
            'selesai' => $selesai,
            'draft'   => $draft,
        ];
    }

    public function getLakipStats()
    {
        $selesai = $this->db->table('lakip')
            ->where('status', 'selesai')
            ->countAllResults();

        $draft = $this->db->table('lakip')
            ->groupStart()
                ->where('status', 'draft')
                ->orWhere('status IS NULL OR TRIM(status) = ', "''", false)
            ->groupEnd()
            ->countAllResults();

        return [
            'selesai' => $selesai,
            'draft'   => $draft,
        ];
    }

    public function getPkStats()
    {
        // TODO: Sesuaikan query tabel PK jika sudah fix
        return [
            'ditandatangani' => 0,
            'belum' => 0,
        ];
    }

    public function getAllStats()
    {
        return [
            'renstraStats' => $this->getRenstraStats(),
            'renjaStats'   => $this->getRenjaStats(),
            'ikuStats'     => $this->getIkuStats(),
            'lakipStats'   => $this->getLakipStats(),
            'pkStats'      => $this->getPkStats(),
        ];
    }
}
