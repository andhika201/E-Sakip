<?php

namespace App\Models;

use CodeIgniter\Model;

class IkuModel extends Model
{
    protected $table = 'iku_indikator_kinerja';

    public function getAllIku()
    {
        $builder = $this->db->table('iku_indikator_kinerja i');
        $builder->select('
            i.id AS indikator_id,
            s.sasaran,
            i.indikator_kinerja AS indikator,
            i.definisi_formulasi AS definisi,
            i.satuan,
            t.tahun,
            t.target
        ');
        $builder->join('iku_sasaran s', 's.id = i.iku_sasaran_id');
        $builder->join('iku_target_tahunan t', 't.iku_indikator_id = i.id');
        $builder->orderBy('i.id');
        $builder->orderBy('t.tahun');

        $result = $builder->get()->getResultArray();

        $ikuData = [];
        $tahunList = [];

        foreach ($result as $row) {
            $id = $row['indikator_id'];

            if (!isset($ikuData[$id])) {
                $ikuData[$id] = [
                    'sasaran' => $row['sasaran'],
                    'indikator' => $row['indikator'],
                    'definisi' => $row['definisi'],
                    'satuan' => $row['satuan'],
                    'target_capaian' => []
                ];
            }

            $ikuData[$id]['target_capaian'][$row['tahun']] = $row['target'];
            $tahunList[$row['tahun']] = true;
        }

        ksort($tahunList);

        return [
            'iku_opd_data' => $ikuData,
            'tahun_list' => array_keys($tahunList)
        ];
    }
}
