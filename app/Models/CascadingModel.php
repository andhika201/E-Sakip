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
        // ==========================================================
        // 1. BACKBONE RPJMD: Misi -> Tujuan -> Sasaran -> Indikator
        //    Selalu tampil walau OPD belum di-mapping.
        //    (Tidak lagi memakai WHERE pada tabel LEFT JOIN yang
        //     dulu menyebabkan baris RPJMD tanpa mapping menghilang.)
        // ==========================================================
        $backbone = $this->db->table('rpjmd_misi m')
            ->select("
                t.id as tujuan_id,
                t.tujuan_rpjmd,

                s.id as sasaran_id,
                s.sasaran_rpjmd,
                s.csf,

                i.id as indikator_id,
                i.indikator_sasaran,
                i.satuan,
                i.baseline
            ", false)
            ->join('rpjmd_tujuan t', 't.misi_id = m.id', 'left')
            ->join('rpjmd_sasaran s', 's.tujuan_id = t.id', 'left')
            ->join('rpjmd_indikator_sasaran i', 'i.sasaran_id = s.id', 'left')
            ->where('m.tahun_mulai', (int) $start)
            ->where('m.tahun_akhir', (int) $end)
            ->orderBy('t.id', 'ASC')
            ->orderBy('s.id', 'ASC')
            ->orderBy('i.id', 'ASC')
            ->get()
            ->getResultArray();

        if (empty($backbone)) {
            return [];
        }

        // ==========================================================
        // 2-3b. Sumber data OPD & Program (helper bersama, dipakai juga
        //       oleh getPohonKinerja agar pohon SELARAS dengan cascading).
        // ==========================================================
        $opdBySasaran      = $this->opdBySasaranMap();              // sasaran_id => [opd_id => nama_opd]
        $manualByIndikator = $this->manualMappingMap($start, $end); // indikator_id => [opd_id => ['nama_opd','programs']]
        $programByOpd      = $this->programByOpdMap();              // opd_id => [program_kegiatan,...]

        // ==========================================================
        // 4. RAKIT BARIS FLAT untuk view
        //    Gabung OPD otomatis (renstra) + OPD mapping manual.
        //    Program: utamakan mapping manual, fallback ke PK otomatis.
        // ==========================================================
        $rows = [];

        foreach ($backbone as $b) {
            $sasaranId = $b['sasaran_id'];
            $indikatorId = $b['indikator_id'];

            // Gabungan OPD: dari renstra (otomatis) + dari mapping manual
            $opdSet = $opdBySasaran[$sasaranId] ?? [];
            foreach ($manualByIndikator[$indikatorId] ?? [] as $opdId => $info) {
                $opdSet[$opdId] = $info['nama_opd'];
            }

            // Indikator dianggap "mapped" bila ada mapping manual apa pun
            $isMapped = !empty($manualByIndikator[$indikatorId]) ? 1 : 0;

            asort($opdSet); // urutkan OPD berdasarkan nama

            if (empty($opdSet)) {
                // Tidak ada OPD sama sekali -> baris tetap tampil (kolom OPD kosong)
                $rows[] = $b + [
                    'nama_opd' => null,
                    'program_kegiatan' => null,
                    'is_mapped' => $isMapped,
                ];
                continue;
            }

            foreach ($opdSet as $opdId => $namaOpd) {
                // Program: utamakan mapping manual; jika tidak ada, pakai
                // program PK otomatis milik OPD tsb (hybrid).
                $manualPrograms = $manualByIndikator[$indikatorId][$opdId]['programs'] ?? [];
                $programs = !empty($manualPrograms)
                    ? $manualPrograms
                    : ($programByOpd[$opdId] ?? []);

                if (empty($programs)) {
                    // OPD muncul otomatis tapi tidak punya program (manual maupun PK)
                    $rows[] = $b + [
                        'nama_opd' => $namaOpd,
                        'program_kegiatan' => null,
                        'is_mapped' => $isMapped,
                    ];
                } else {
                    foreach ($programs as $prog) {
                        $rows[] = $b + [
                            'nama_opd' => $namaOpd,
                            'program_kegiatan' => $prog,
                            'is_mapped' => $isMapped,
                        ];
                    }
                }
            }
        }

        // ==========================================================
        // 5. AMBIL & ATTACH TARGET per indikator
        // ==========================================================
        $indikatorIds = array_values(array_unique(array_filter(array_column($rows, 'indikator_id'))));

        if (empty($indikatorIds)) {
            return $rows;
        }

        $targets = $this->db->table('rpjmd_target')
            ->select('indikator_sasaran_id, tahun, target_tahunan')
            ->whereIn('indikator_sasaran_id', $indikatorIds)
            ->get()
            ->getResultArray();

        $targetMap = [];
        foreach ($targets as $t) {
            $targetMap[$t['indikator_sasaran_id']][$t['tahun']] = $t['target_tahunan'];
        }

        foreach ($rows as &$r) {
            $r['targets'] = $targetMap[$r['indikator_id']] ?? [];
        }
        unset($r);

        return $rows;
    }

    /**
     * OPD per sasaran RPJMD, ditarik otomatis dari rantai Renstra.
     * renstra_tujuan.rpjmd_sasaran_id -> rpjmd_sasaran.id ;
     * OPD diambil dari renstra_sasaran.opd_id.
     * @return array sasaran_id => [ opd_id => nama_opd ]
     */
    private function opdBySasaranMap(): array
    {
        $rows = $this->db->table('renstra_tujuan rt')
            ->select('rt.rpjmd_sasaran_id as sasaran_id, rs.opd_id, o.nama_opd')
            ->join('renstra_sasaran rs', 'rs.renstra_tujuan_id = rt.id', 'inner')
            ->join('opd o', 'o.id = rs.opd_id', 'inner')
            ->where('rt.rpjmd_sasaran_id IS NOT NULL')
            ->groupBy('rt.rpjmd_sasaran_id, rs.opd_id, o.nama_opd')
            ->orderBy('o.nama_opd', 'ASC')
            ->get()
            ->getResultArray();

        $map = [];
        foreach ($rows as $row) {
            $map[$row['sasaran_id']][$row['opd_id']] = $row['nama_opd'];
        }
        return $map;
    }

    /**
     * Program JPT per OPD, ditarik otomatis dari rantai PK (Perjanjian Kinerja).
     * program_pk.opd_id NULL, jadi OPD hanya bisa dijangkau lewat tabel pk.
     * Diambil program tahun PK TERBARU per OPD.
     * @return array opd_id => [ program_kegiatan, ... ]
     */
    private function programByOpdMap(): array
    {
        $rows = $this->db->table('pk')
            ->select('pk.opd_id, pk.tahun, p.program_kegiatan')
            ->join('pk_sasaran ps', 'ps.pk_id = pk.id', 'inner')
            ->join('pk_indikator pi', 'pi.pk_sasaran_id = ps.id', 'inner')
            ->join('pk_program pp', 'pp.pk_indikator_id = pi.id', 'inner')
            ->join('program_pk p', 'p.id = pp.program_id', 'inner')
            ->where('pi.jenis', 'jpt')
            ->orderBy('pk.opd_id', 'ASC')
            ->orderBy('pk.tahun', 'DESC')
            ->get()
            ->getResultArray();

        $byOpd  = [];
        $latest = [];
        foreach ($rows as $row) {
            $opd = $row['opd_id'];
            $th  = (int) $row['tahun'];
            if (!isset($latest[$opd])) {
                $latest[$opd] = $th; // baris terurut tahun DESC -> pertama = terbaru
            }
            if ($th !== $latest[$opd]) {
                continue;
            }
            $prog = $row['program_kegiatan'];
            if ($prog !== null && $prog !== '' && !in_array($prog, $byOpd[$opd] ?? [], true)) {
                $byOpd[$opd][] = $prog;
            }
        }
        return $byOpd;
    }

    /**
     * Mapping manual cascading (rpjmd_cascading) untuk satu periode.
     * @return array indikator_id => [ opd_id => ['nama_opd' => ..., 'programs' => [...] ] ]
     */
    private function manualMappingMap($start, $end): array
    {
        $rows = $this->db->table('rpjmd_cascading map')
            ->select('map.indikator_sasaran_id, map.opd_id, o.nama_opd, p.program_kegiatan')
            ->join('pk_program pp', 'pp.id = map.pk_program_id', 'left')
            ->join('program_pk p', 'p.id = pp.program_id', 'left')
            ->join('opd o', 'o.id = map.opd_id', 'left')
            ->where('map.tahun >=', (int) $start)
            ->where('map.tahun <=', (int) $end)
            ->get()
            ->getResultArray();

        $map = [];
        foreach ($rows as $row) {
            $ind = $row['indikator_sasaran_id'];
            $opd = $row['opd_id'];
            if (!isset($map[$ind][$opd])) {
                $map[$ind][$opd] = ['nama_opd' => $row['nama_opd'], 'programs' => []];
            }
            if (!empty($row['program_kegiatan'])) {
                $map[$ind][$opd]['programs'][] = $row['program_kegiatan'];
            }
        }
        return $map;
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
            ->where('map.tahun >=', (int) $start)
            ->where('map.tahun <=', (int) $end)
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
    public function getCascadingMatrixByOpd($opdId, $startYear = null, $endYear = null)
    {
        // Hindari query rusak (ON clause "opd_id = NULL") bila OPD tidak diketahui,
        // mis. akun super admin yang tidak terikat OPD.
        if (empty($opdId)) {
            return [];
        }

        $builder = $this->db->table('renstra_sasaran rs')
            ->select("
            t.id as tujuan_id,
            t.tujuan_rpjmd,

            s.id as sasaran_id,
            s.sasaran_rpjmd,

            rt.id as renstra_tujuan_id,
            rt.tujuan as renstra_tujuan,

            rs.csf as csf_es2,
            rs.id as renstra_sasaran_id,
            rs.sasaran as renstra_sasaran,

            ris.id as indikator_id,
            ris.indikator_sasaran,
            ris.satuan,

            es3.csf as csf_es3,
            es3.id as es3_id,
            es3.nama_sasaran as es3_sasaran,

            i3.id as es3_indikator_id,
            i3.indikator as es3_indikator,

            es4.csf as csf_es4,
            es4.id as es4_id,
            es4.nama_sasaran as es4_sasaran,

            i4.id as es4_indikator_id,
            i4.indikator as es4_indikator
        ")
            ->join('renstra_tujuan rt', 'rt.id=rs.renstra_tujuan_id', 'left')
            ->join('rpjmd_sasaran s', 's.id=rt.rpjmd_sasaran_id', 'left')
            ->join('rpjmd_tujuan t', 't.id=s.tujuan_id', 'left')
            ->join('renstra_indikator_sasaran ris', 'ris.renstra_sasaran_id=rs.id', 'left')
            ->join(
                'cascading_sasaran_opd es3',
                'es3.renstra_indikator_sasaran_id = ris.id 
            AND es3.level="es3" 
            AND es3.opd_id=' . $this->db->escape($opdId),
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
            ->where('rs.opd_id', $opdId);

        if ($startYear && $endYear) {
            $builder->where('rs.tahun_mulai', $startYear);
            $builder->where('rs.tahun_akhir', $endYear);
        }

        return $builder
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


    /**
     * Get hierarchical tree data for Pohon Kinerja PDF
     * Misi → Tujuan → Indikator Tujuan + CSF → Sasaran → Indikator Sasaran
     */
    public function getPohonKinerja($tahunMulai, $tahunAkhir)
    {
        // 1. Get all Misi for the period
        $misiList = $this->db->table('rpjmd_misi')
            ->where('tahun_mulai', $tahunMulai)
            ->where('tahun_akhir', $tahunAkhir)
            ->orderBy('id', 'ASC')
            ->get()
            ->getResultArray();

        if (empty($misiList)) {
            return [];
        }

        $misiIds = array_column($misiList, 'id');

        // 2. Get all Tujuan for these Misi
        $tujuanList = $this->db->table('rpjmd_tujuan')
            ->whereIn('misi_id', $misiIds)
            ->orderBy('id', 'ASC')
            ->get()
            ->getResultArray();

        $tujuanIds = array_column($tujuanList, 'id');

        // 3. Get all Indikator Tujuan and Sasaran for these Tujuan
        $indikatorTujuanList = [];
        $sasaranList = [];
        $sasaranIds = [];
        
        if (!empty($tujuanIds)) {
            $indikatorTujuanList = $this->db->table('rpjmd_indikator_tujuan')
                ->whereIn('tujuan_id', $tujuanIds)
                ->orderBy('id', 'ASC')
                ->get()
                ->getResultArray();

            $sasaranList = $this->db->table('rpjmd_sasaran')
                ->whereIn('tujuan_id', $tujuanIds)
                ->orderBy('id', 'ASC')
                ->get()
                ->getResultArray();

            $sasaranIds = array_column($sasaranList, 'id');
        }

        // 4. Get all Indikator Sasaran for these Sasaran
        $indikatorSasaranList = [];
        if (!empty($sasaranIds)) {
            $indikatorSasaranList = $this->db->table('rpjmd_indikator_sasaran')
                ->whereIn('sasaran_id', $sasaranIds)
                ->orderBy('id', 'ASC')
                ->get()
                ->getResultArray();
        }

        // --- SUMBER OPD & PROGRAM (logika identik dengan cascading getMatrix) ---
        $opdBySasaran      = $this->opdBySasaranMap();
        $manualByIndikator = $this->manualMappingMap($tahunMulai, $tahunAkhir);
        $programByOpd      = $this->programByOpdMap();

        // --- GROUPING IN MEMORY ---

        // Group Indikator Sasaran by sasaran_id
        $groupedIndikatorSasaran = [];
        foreach ($indikatorSasaranList as $indSas) {
            $groupedIndikatorSasaran[$indSas['sasaran_id']][] = $indSas;
        }

        // Group Sasaran by tujuan_id + lampirkan Indikator Sasaran & cabang OPD/Program
        $groupedSasaran = [];
        foreach ($sasaranList as $sasaran) {
            $sid = $sasaran['id'];
            $indikatorSasaran = $groupedIndikatorSasaran[$sid] ?? [];
            $indIds = array_column($indikatorSasaran, 'id');

            // OPD: otomatis dari Renstra + union OPD dari mapping manual indikator sasaran ini
            $opdSet = $opdBySasaran[$sid] ?? [];
            foreach ($indIds as $ind) {
                foreach ($manualByIndikator[$ind] ?? [] as $opdId => $info) {
                    $opdSet[$opdId] = $info['nama_opd'];
                }
            }
            asort($opdSet);

            // Program per OPD: utamakan mapping manual (gabungan indikator sasaran ini),
            // jika tidak ada pakai program PK otomatis (hybrid, sama seperti cascading).
            $opdNodes = [];
            foreach ($opdSet as $opdId => $namaOpd) {
                $manualProgs = [];
                foreach ($indIds as $ind) {
                    foreach ($manualByIndikator[$ind][$opdId]['programs'] ?? [] as $pg) {
                        if (!in_array($pg, $manualProgs, true)) {
                            $manualProgs[] = $pg;
                        }
                    }
                }
                $opdNodes[] = [
                    'nama_opd' => $namaOpd,
                    'programs' => !empty($manualProgs) ? $manualProgs : ($programByOpd[$opdId] ?? []),
                ];
            }

            $groupedSasaran[$sasaran['tujuan_id']][] = [
                'id' => $sid,
                'sasaran_rpjmd' => $sasaran['sasaran_rpjmd'],
                'csf' => $sasaran['csf'] ?? '',
                'indikator_sasaran' => $indikatorSasaran,
                'opd' => $opdNodes,
            ];
        }

        // Group Indikator Tujuan by tujuan_id
        $groupedIndikatorTujuan = [];
        foreach ($indikatorTujuanList as $indTuj) {
            $groupedIndikatorTujuan[$indTuj['tujuan_id']][] = $indTuj;
        }

        // Group Tujuan by misi_id and attach Sasaran & Indikator Tujuan
        $groupedTujuan = [];
        foreach ($tujuanList as $tujuan) {
            $tujuanNode = [
                'id' => $tujuan['id'],
                'tujuan_rpjmd' => $tujuan['tujuan_rpjmd'],
                'indikator_tujuan' => $groupedIndikatorTujuan[$tujuan['id']] ?? [],
                'sasaran' => $groupedSasaran[$tujuan['id']] ?? []
            ];
            $groupedTujuan[$tujuan['misi_id']][] = $tujuanNode;
        }

        // Assemble final tree
        $tree = [];
        foreach ($misiList as $misi) {
            $misiNode = [
                'id' => $misi['id'],
                'misi' => $misi['misi'],
                'tujuan' => $groupedTujuan[$misi['id']] ?? []
            ];
            $tree[] = $misiNode;
        }

        return $tree;
    }

}
