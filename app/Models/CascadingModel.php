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
                o.nama_opd,

                IF(map.id IS NULL, 0, 1) as is_mapped,
                map.indikator_sasaran_id as mapped_indikator
            ", false)

            ->join('rpjmd_sasaran s', 's.tujuan_id = t.id', 'left')
            ->join('rpjmd_indikator_sasaran i', 'i.sasaran_id = s.id', 'left')

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
            ->select('MIN(pp.id) as id, p.program_kegiatan')
            ->join('pk_indikator pi', 'pi.id = pp.pk_indikator_id')
            ->join('pk_sasaran ps', 'ps.id = pi.pk_sasaran_id')
            ->join('pk pk', 'pk.id = ps.pk_id')
            ->join('program_pk p', 'p.id = pp.program_id')
            ->where('pk.opd_id', $opdId)
            ->where('pk.tahun', $tahun)
            ->where('pi.jenis', 'jpt')
            ->groupBy('pp.program_id')
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

    public function getExistingMapping($indikatorId, $tahun)
    {
        return $this->db->table('rpjmd_cascading c')
            ->select('c.opd_id, c.pk_program_id')
            ->where('c.indikator_sasaran_id', $indikatorId)
            ->where('c.tahun', $tahun)
            ->get()
            ->getResultArray();
    }

    public function deleteByIndikatorAndYear($indikatorId, $tahun)
    {
        return $this->db->table($this->table)
            ->where('indikator_sasaran_id', $indikatorId)
            ->where('tahun', $tahun)
            ->delete();
    }

    public function getPdfMatrix($start, $end)
    {
        $rows = $this->db->table('rpjmd_indikator_sasaran i')
            ->select("
            i.id as indikator_id,
            i.indikator_sasaran,
            i.satuan,
            i.baseline,

            map.opd_id,
            o.nama_opd,

            p.program_kegiatan
        ")
            ->join('rpjmd_cascading map', 'map.indikator_sasaran_id = i.id', 'left')
            ->join('pk_program pp', 'pp.id = map.pk_program_id', 'left')
            ->join('program_pk p', 'p.id = pp.program_id', 'left')
            ->join('opd o', 'o.id = map.opd_id', 'left')
            ->where("map.tahun BETWEEN {$start} AND {$end}")
            ->orderBy('i.id')
            ->orderBy('o.nama_opd')
            ->get()
            ->getResultArray();

        $grouped = [];

        foreach ($rows as $r) {

            $indikator = $r['indikator_id'];
            $opd = $r['opd_id'];

            if (!isset($grouped[$indikator])) {
                $grouped[$indikator] = [
                    'indikator' => $r['indikator_sasaran'],
                    'satuan' => $r['satuan'],
                    'baseline' => $r['baseline'],
                    'opd' => []
                ];
            }

            if (!isset($grouped[$indikator]['opd'][$opd])) {
                $grouped[$indikator]['opd'][$opd] = [
                    'nama_opd' => $r['nama_opd'],
                    'program' => []
                ];
            }

            if ($r['program_kegiatan']) {
                $grouped[$indikator]['opd'][$opd]['program'][] =
                    $r['program_kegiatan'];
            }
        }

        return $grouped;
    }

    // adminopd
    public function getCascadingMatrixByOpd($opdId)
    {
        return $this->db->table('rpjmd_tujuan t')

            ->select("
            t.id as tujuan_id,
            t.tujuan_rpjmd,

            s.id as sasaran_id,
            s.sasaran_rpjmd,

            rt.id as renstra_tujuan_id,
            rt.tujuan as renstra_tujuan,

            rs.id as renstra_sasaran_id,
            rs.sasaran as renstra_sasaran,

            ris.id as indikator_id,
            ris.indikator_sasaran,
            ris.satuan,

            es3.id as es3_id,
            es3.nama_sasaran as es3_sasaran,

            i3.id as es3_indikator_id,
            i3.indikator as es3_indikator,

            es4.id as es4_id,
            es4.nama_sasaran as es4_sasaran,

            i4.id as es4_indikator_id,
            i4.indikator as es4_indikator
        ")

            ->join('rpjmd_sasaran s', 's.tujuan_id=t.id', 'left')

            ->join('renstra_tujuan rt', 'rt.rpjmd_sasaran_id=s.id', 'left')

            ->join('renstra_sasaran rs', 'rs.renstra_tujuan_id=rt.id', 'left')

            ->join('renstra_indikator_sasaran ris', 'ris.renstra_sasaran_id=rs.id', 'left')

            ->join(
                'cascading_sasaran_opd es3',
                'es3.renstra_indikator_sasaran_id = ris.id 
            AND es3.level="es3" 
            AND es3.opd_id=' . $opdId,
                'left'
            )

            ->join(
                'cascading_indikator_opd i3',
                'i3.cascading_sasaran_id = es3.id',
                'left'
            )

            ->join(
                'cascading_sasaran_opd es4',
                'es4.es3_indikator_id = i3.id AND es4.level="es4"',
                'left'
            )

            ->join(
                'cascading_indikator_opd i4',
                'i4.cascading_sasaran_id = es4.id',
                'left'
            )

            ->where('rs.opd_id', $opdId)

            ->orderBy('t.id', 'ASC')
            ->orderBy('s.id', 'ASC')
            ->orderBy('rt.id', 'ASC')
            ->orderBy('rs.id', 'ASC')
            ->orderBy('ris.id', 'ASC')

            ->orderBy('es3.id', 'ASC')
            ->orderBy('i3.id', 'ASC')
            ->orderBy('es4.id', 'ASC')
            ->orderBy('i4.id', 'ASC')

            ->get()
            ->getResultArray();
    }
    public function getCascadingTree($renstraIndikatorId, $opdId)
    {
        return $this->db->table('cascading_sasaran_opd')
            ->where('renstra_indikator_sasaran_id', $renstraIndikatorId)
            ->where('opd_id', $opdId)
            ->orderBy('level', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function insertSasaran($data)
    {
        $this->db->table('cascading_sasaran_opd')
            ->insert($data);

        return $this->db->insertID();
    }
    public function insertIndikator($data)
    {
        return $this->db->table('cascading_indikator_opd')
            ->insert($data);
    }
    public function getIndikatorBySasaran($sasaranId)
    {
        return $this->db->table('cascading_indikator_opd')
            ->where('cascading_sasaran_id', $sasaranId)
            ->get()
            ->getResultArray();
    }
    public function getRenstraHierarchyByOpd($opdId)
    {
        return $this->db->table('rpjmd_tujuan t')
            ->select("
            t.id as rpjmd_tujuan_id,
            t.tujuan_rpjmd,

            s.id as rpjmd_sasaran_id,
            s.sasaran_rpjmd,

            rt.id as renstra_tujuan_id,
            rt.tujuan as renstra_tujuan,

            rs.id as renstra_sasaran_id,
            rs.sasaran as renstra_sasaran,

            ris.id as indikator_id,
            ris.indikator_sasaran,
            ris.satuan
        ")

            ->join('rpjmd_sasaran s', 's.tujuan_id = t.id', 'left')

            ->join(
                'renstra_tujuan rt',
                'rt.rpjmd_sasaran_id = s.id',
                'left'
            )

            ->join(
                'renstra_sasaran rs',
                'rs.renstra_tujuan_id = rt.id',
                'left'
            )

            ->join(
                'renstra_indikator_sasaran ris',
                'ris.renstra_sasaran_id = rs.id',
                'left'
            )

            ->where('rs.opd_id', $opdId)

            ->orderBy('t.id', 'ASC')
            ->orderBy('s.id', 'ASC')
            ->orderBy('rt.id', 'ASC')
            ->orderBy('rs.id', 'ASC')
            ->orderBy('ris.id', 'ASC')

            ->get()
            ->getResultArray();
    }


    public function getRenstraByOpd($opdId)
    {
        return $this->db->table('rpjmd_tujuan t')
            ->select("
            t.id as tujuan_id,
            t.tujuan_rpjmd,

            s.id as sasaran_id,
            s.sasaran_rpjmd,

            rt.id as renstra_tujuan_id,
            rt.tujuan as renstra_tujuan,

            rs.id as renstra_sasaran_id,
            rs.sasaran as renstra_sasaran,

            ris.id as indikator_id,
            ris.indikator_sasaran,
            ris.satuan
        ")

            ->join('rpjmd_sasaran s', 's.tujuan_id = t.id', 'left')

            ->join(
                'renstra_tujuan rt',
                'rt.rpjmd_sasaran_id = s.id',
                'left'
            )

            ->join(
                'renstra_sasaran rs',
                'rs.renstra_tujuan_id = rt.id',
                'left'
            )

            ->join(
                'renstra_indikator_sasaran ris',
                'ris.renstra_sasaran_id = rs.id',
                'left'
            )

            ->where('rs.opd_id', $opdId)

            ->orderBy('t.id', 'ASC')
            ->orderBy('s.id', 'ASC')
            ->orderBy('rt.id', 'ASC')
            ->orderBy('rs.id', 'ASC')
            ->orderBy('ris.id', 'ASC')

            ->get()
            ->getResultArray();
    }


}
