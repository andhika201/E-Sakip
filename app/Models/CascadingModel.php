<?php

namespace App\Models;

use CodeIgniter\Model;

class CascadingModel extends Model
{
    protected $db;
    protected $table = 'rpjmd_cascading';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'indikator_sasaran_id',
        'opd_id',
        'pk_program_id',
        'tahun'
    ];

    protected $useTimestamps = true;

    public function __construct()
    {
        parent::__construct();
        $this->db = \Config\Database::connect();
    }

    public function getMatrix($start, $end)
    {
        // ======================
        // AMBIL MATRIX UTAMA
        // ======================
        $rows = $this->db->table('rpjmd_tujuan t')
            ->select("
            t.id as tujuan_id,
            t.tujuan_rpjmd,

            s.id as sasaran_id,
            s.sasaran_rpjmd,

            i.id as indikator_id,
            i.indikator_sasaran,
            i.satuan,
            i.baseline,

            p.program_kegiatan,
            o.nama_opd
        ")
            ->join('rpjmd_sasaran s', 's.tujuan_id = t.id', 'left')
            ->join('rpjmd_indikator_sasaran i', 'i.sasaran_id = s.id', 'left')

            // CASCADING
            ->join(
                'rpjmd_cascading map',
                "map.indikator_sasaran_id = i.id 
                        AND map.tahun BETWEEN {$start} AND {$end}",
                'left'
            )
            ->join('pk_program pp', 'pp.id = map.pk_program_id', 'left')
            ->join('program_pk p', 'p.id = pp.program_id', 'left')
            ->join('opd o', 'o.id = map.opd_id', 'left')

            ->orderBy('t.id', 'ASC')
            ->orderBy('s.id', 'ASC')
            ->orderBy('i.id', 'ASC')

            ->get()
            ->getResultArray();

        // ======================
        // AMBIL TARGET TERPISAH
        // ======================
        $indikatorIds = array_column($rows, 'indikator_id');

        if (empty($indikatorIds)) {
            return $rows;
        }

        $targets = $this->db->table('rpjmd_target')
            ->select('indikator_sasaran_id, tahun, target_tahunan')
            ->whereIn('indikator_sasaran_id', $indikatorIds)
            ->get()
            ->getResultArray();

        // ======================
        // GROUP TARGET
        // ======================
        $targetMap = [];

        foreach ($targets as $t) {
            $targetMap[$t['indikator_sasaran_id']][$t['tahun']] = $t['target_tahunan'];
        }

        // ======================
        // ATTACH KE ROW
        // ======================
        foreach ($rows as &$r) {
            $r['targets'] = $targetMap[$r['indikator_id']] ?? [];
        }

        return $rows;
    }

    public function getPkProgramByOpd($opdId, $tahun)
    {
        return $this->db->table('pk_program pp')
            ->select("
                pp.id,
                p.program_kegiatan
            ")
            ->join('pk_indikator pi', 'pi.id = pp.pk_indikator_id')
            ->join('pk_sasaran ps', 'ps.id = pi.pk_sasaran_id')
            ->join('pk pk', 'pk.id = ps.pk_id')
            ->join('program_pk p', 'p.id = pp.program_id')
            ->where('pk.opd_id', $opdId)
            ->where('pk.tahun', $tahun)
            ->where('pi.jenis', 'jpt')
            ->groupBy('pp.id')
            ->orderBy('p.program_kegiatan', 'ASC')
            ->get()
            ->getResultArray();
    }
    public function saveBatchMapping(array $data)
    {
        if (empty($data))
            return false;

        return $this->db->table($this->table)
            ->ignore(true)
            ->insertBatch($data);
    }

    public function isProgramBelongsToOpd($programId, $opdId)
    {
        return $this->db->table('pk_program pr')
            ->join('pk_indikator i', 'i.id = pr.pk_indikator_id')
            ->join('pk_sasaran s', 's.id = i.pk_sasaran_id')
            ->join('pk p', 'p.id = s.pk_id')
            ->where('pr.id', $programId)
            ->where('p.opd_id', $opdId)
            ->countAllResults() > 0;
    }
}
