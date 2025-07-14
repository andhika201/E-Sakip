<?php

namespace App\Models;

use CodeIgniter\Model;

class RktModel extends Model
{
    protected $db;

    public function __construct()
    {
        parent::__construct();
        $this->db = \Config\Database::connect();
    }

    public function getAllRktSasaran()
    {
        return $this->db->table('rkt_sasaran')
            ->select('rkt_sasaran.*, rpjmd_sasaran.sasaran_rpjmd')
            ->join('rpjmd_sasaran', 'rpjmd_sasaran.id = rkt_sasaran.rpjmd_sasaran_id')
            ->get()
            ->getResultArray();
    }

    public function getRktSasaranById($id) {

        return $this->db->table('rkt_sasaran')
            ->where('id', $id)
            ->get()
            ->getRowArray();
     }

     public function getRktByStatus($status)
     {
         return $this->db->table('rkt_sasaran')
             ->where('status', $status)
             ->get()
             ->getResultArray();
     }

    public function getAllRktByStatus($status = null)
    {
        $query = $this->db->table('rkt_sasaran');
        
        if ($status !== null) {
            $query->where('status', $status);
        }
        
        return $query->orderBy('id', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function getCompletedRkt()
    {
        return $this->db->table('rkt_sasaran')
            ->where('status', 'selesai')
            ->orderBy('id', 'ASC')
            ->get()
            ->getResultArray();
    }

    // Menyimpan data RKT beserta indikator sasaran
    public function saveRktWithIndikator($data)
    {
        $this->db->transStart();

        try {
            // Simpan sasaran RKT
            if (isset($data['sasaran_rkt']) && is_array($data['sasaran_rkt'])) {
                foreach ($data['sasaran_rkt'] as $sasaranData) {
                    // Insert sasaran RKT
                    $sasaranRktData = [
                        'rpjmd_sasaran_id' => $data['rpjmd_sasaran_id'],
                        'sasaran' => $sasaranData['sasaran'],
                        'status' => 'draft'
                    ];
                    
                    $this->db->table('rkt_sasaran')->insert($sasaranRktData);
                    $sasaranRktId = $this->db->insertID();

                    // Insert indikator sasaran
                    if (isset($sasaranData['indikator_sasaran']) && is_array($sasaranData['indikator_sasaran'])) {
                        foreach ($sasaranData['indikator_sasaran'] as $indikatorData) {
                            $indikatorSasaranData = [
                                'rkt_sasaran_id' => $sasaranRktId,
                                'indikator_sasaran' => $indikatorData['indikator_sasaran'],
                                'satuan' => $indikatorData['satuan'],
                                'tahun' => $indikatorData['tahun'],
                                'target' => $indikatorData['target']
                            ];

                            $this->db->table('rkt_indikator_sasaran')->insert($indikatorSasaranData);
                        }
                    }
                }
            }

            $this->db->transComplete();

            if ($this->db->transStatus() === false) {
                throw new \Exception('Transaction failed');
            }

            return true;

        } catch (\Exception $e) {
            $this->db->transRollback();
            log_message('error', 'Error saving RKT: ' . $e->getMessage());
            return false;
        }
    }

    // Mendapatkan sasaran RKT beserta indikatornya
    public function getRktWithIndikator($id = null)
    {
        if ($id) {
            // Get specific RKT sasaran
            $sasaran = $this->db->table('rkt_sasaran')
                ->select('rkt_sasaran.*, rpjmd_sasaran.sasaran_rpjmd')
                ->join('rpjmd_sasaran', 'rpjmd_sasaran.id = rkt_sasaran.rpjmd_sasaran_id')
                ->where('rkt_sasaran.id', $id)
                ->get()
                ->getRowArray();

            if ($sasaran) {
                $sasaran['indikator_sasaran'] = $this->db->table('rkt_indikator_sasaran')
                    ->where('rkt_sasaran_id', $id)
                    ->get()
                    ->getResultArray();
            }

            return $sasaran;
        } else {
            // Get all RKT sasaran
            $sasaranList = $this->db->table('rkt_sasaran')
                ->select('rkt_sasaran.*, rpjmd_sasaran.sasaran_rpjmd')
                ->join('rpjmd_sasaran', 'rpjmd_sasaran.id = rkt_sasaran.rpjmd_sasaran_id')
                ->get()
                ->getResultArray();

            foreach ($sasaranList as &$sasaran) {
                $sasaran['indikator_sasaran'] = $this->db->table('rkt_indikator_sasaran')
                    ->where('rkt_sasaran_id', $sasaran['id'])
                    ->get()
                    ->getResultArray();
            }

            return $sasaranList;
        }
    }

    // Update status RKT
    public function updateStatus($id, $status)
    {
        return $this->db->table('rkt_sasaran')
            ->where('id', $id)
            ->update(['status' => $status]);
    }

}
