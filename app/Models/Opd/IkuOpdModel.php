<?php

namespace App\Models\Opd;

use CodeIgniter\Model;

class IkuOpdModel extends Model
{
    protected $table = 'iku_opd';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'opd_id', 
        'sasaran', 
        'indikator', 
        'definisi', 
        'satuan', 
        'created_at', 
        'updated_at'
    ];

    // Ambil data IKU beserta target tahunannya
    public function getIkuOpdWithTarget()
    {
        // Ambil semua data IKU
        $ikuData = $this->findAll();

        // Ambil data target tahunan dari tabel terkait
        $db = \Config\Database::connect();
        $builder = $db->table('iku_opd_target');

        $targetData = $builder->select('iku_opd_id, tahun, target')
                              ->get()
                              ->getResultArray();

        // Susun data target berdasarkan IKU dan tahun
        $targets = [];
        foreach ($targetData as $target) {
            $targets[$target['iku_opd_id']][$target['tahun']] = $target['target'];
        }

        // Gabungkan target dengan data IKU
        foreach ($ikuData as &$iku) {
            $iku['target_capaian'] = $targets[$iku['id']] ?? [];
        }

        return $ikuData;
    }

    public function getTahunList()
    {
        $db = \Config\Database::connect();
        return $db->table('iku_opd_target')
                  ->select('tahun')
                  ->distinct()
                  ->orderBy('tahun', 'asc')
                  ->get()
                  ->getResultArray();
    }
}
