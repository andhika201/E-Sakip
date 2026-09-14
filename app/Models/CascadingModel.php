<?php

namespace App\Models;

use CodeIgniter\Model;

class CascadingModel extends Model
{
    protected $db;
    protected $table = 'rpjmd_cascading';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'iku_indikator_id',
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

    // =====================================================================
    // TULANG PUNGGUNG CASCADING KABUPATEN: IKU KABUPATEN
    //
    // Sampai 14 Sep 2026 tulang punggungnya RPJMD (misi -> tujuan -> sasaran
    // -> indikator RPJMD) dan IKU hanya "ditempelkan" bila silsilahnya ketemu.
    // Asumsinya: IKU adalah PILIHAN indikator RPJMD. Asumsi itu bocor begitu
    // IKU menyusun ulang: lima indikator "Produksi Pangan" yang IKU buang
    // tetap tampil dari RPJMD, sedangkan "Indeks Ketahanan Pangan" yang IKU
    // tambahkan tidak pernah tampil — dokumen yang dinilai LAKIP tidak sama
    // dengan yang dicascading.
    //
    // Kini barisnya adalah SASARAN & INDIKATOR IKU KABUPATEN. RPJMD tinggal
    // menyumbang Misi dan Tujuan lewat JANGKAR sasaran, dengan urutan:
    //   1. silsilah sasaran   (iku_sasaran.source_sasaran_id, hasil sync)
    //   2. silsilah indikator (iku_indikator.source_indikator_id -> sasaran RPJMD)
    //   3. jangkar manual     (iku_sasaran.rpjmd_tujuan_id, diisi di form revisi)
    // Sasaran tanpa ketiganya TETAP TAMPIL — kolom Misi/Tujuan-nya kosong,
    // bukan disembunyikan (keputusan pemilik sistem, 14 Sep 2026): yang
    // kosong terlihat, dan ada layar untuk mengisinya.
    //
    // Nama kolom hasil TIDAK berubah (`misi`, `tujuan_rpjmd`, `sasaran_rpjmd`,
    // `indikator_sasaran`, `satuan`, `baseline`, `targets`, ...): view
    // kabupaten, ekspor Excel, cetak, halaman publik, dan pohon kinerja
    // membacanya dengan nama itu. `sasaran_id` dan `indikator_id` kini id IKU.
    // =====================================================================

    /**
     * Sasaran IKU Kabupaten satu periode beserta JANGKAR RPJMD-nya.
     *
     * @return array<int, array<string,mixed>> dikunci id sasaran IKU, terurut
     *         urutan dokumen; tiap baris memuat: id, sasaran, source_type,
     *         source_sasaran_id, rpjmd_tujuan_id, rpjmd_sasaran_id (jangkar
     *         terpilih), tujuan_id, misi_id, jangkar ('silsilah'|'indikator'|
     *         'tujuan'|''), es2_versi_status
     */
    private function sasaranKabIku(int $start, int $end, ?int $ikuRevisiId = null): array
    {
        $db = $this->db;

        if (! $db->tableExists('iku_sasaran')) {
            return [];
        }

        $adaJangkar = $db->fieldExists('rpjmd_tujuan_id', 'iku_sasaran');
        $dariVersi  = $ikuRevisiId !== null && $ikuRevisiId > 0 && $db->tableExists('iku_revisi_sasaran');

        $q = $db->table('iku_sasaran iks')
            ->select('iks.id, iks.source_type, iks.source_sasaran_id, iks.urutan'
                . ($adaJangkar ? ', iks.rpjmd_tujuan_id' : ', NULL AS rpjmd_tujuan_id')
                . ($dariVersi
                    ? ", COALESCE(NULLIF(rvs.sasaran, ''), iks.sasaran) AS sasaran"
                    : ', iks.sasaran'), false)
            ->where('iks.opd_id IS NULL', null, false)
            ->where('iks.tahun_mulai', $start)
            ->where('iks.tahun_akhir', $end)
            ->where('iks.dihentikan_pada IS NULL', null, false)
            ->orderBy('iks.urutan', 'ASC')
            ->orderBy('iks.id', 'ASC');

        if ($dariVersi) {
            // Teks versi terpilih menimpa teks berjalan — persis perlakuan
            // lama pada tempelan IKU.
            $q->join(
                'iku_revisi_sasaran rvs',
                'rvs.sumber_sasaran_id = iks.id AND rvs.revisi_id = ' . (int) $ikuRevisiId,
                'left',
                false
            );
        }

        $sasaran = [];

        foreach ($q->get()->getResultArray() as $r) {
            $sasaran[(int) $r['id']] = $r + [
                'rpjmd_sasaran_id' => null,
                'tujuan_id'        => null,
                'misi_id'          => null,
                'jangkar'          => '',
            ];
        }

        if ($sasaran === []) {
            return [];
        }

        // --- jangkar 1: silsilah sasaran -------------------------------
        foreach ($sasaran as &$s) {
            if (($s['source_type'] ?? '') === 'rpjmd' && ! empty($s['source_sasaran_id'])) {
                $s['rpjmd_sasaran_id'] = (int) $s['source_sasaran_id'];
                $s['jangkar']          = 'silsilah';
            }
        }
        unset($s);

        // --- jangkar 2: silsilah indikator ------------------------------
        // Sasaran yang lahir di IKU bisa saja menampung indikator hasil sync
        // (kasus nyata: "Lingkungan Hidup" tanpa silsilah sasaran, tetapi
        // IKLH & IRB-nya bersilsilah). Sasaran RPJMD indikator itu menjadi
        // jangkarnya. Yang pertama menang: satu sasaran satu jangkar.
        $tanpaJangkar = array_keys(array_filter($sasaran, static fn ($s) => $s['rpjmd_sasaran_id'] === null));

        if ($tanpaJangkar !== [] && $db->fieldExists('source_indikator_id', 'iku_indikator')) {
            $rows = $db->table('iku_indikator iki')
                ->select('iki.iku_sasaran_id, ris.sasaran_id AS rpjmd_sasaran_id')
                ->join('rpjmd_indikator_sasaran ris', 'ris.id = iki.source_indikator_id')
                ->whereIn('iki.iku_sasaran_id', $tanpaJangkar)
                ->where('iki.source_type', 'rpjmd')
                ->where('iki.dihentikan_pada IS NULL', null, false)
                ->orderBy('iki.urutan', 'ASC')
                ->orderBy('iki.id', 'ASC')
                ->get()->getResultArray();

            foreach ($rows as $r) {
                $sid = (int) $r['iku_sasaran_id'];

                if (isset($sasaran[$sid]) && $sasaran[$sid]['rpjmd_sasaran_id'] === null) {
                    $sasaran[$sid]['rpjmd_sasaran_id'] = (int) $r['rpjmd_sasaran_id'];
                    $sasaran[$sid]['jangkar']          = 'indikator';
                }
            }
        }

        // --- turunkan tujuan & misi dari sasaran RPJMD ------------------
        $idSasaranRpjmd = array_values(array_unique(array_filter(array_column($sasaran, 'rpjmd_sasaran_id'))));
        $tujuanDariSasaran = [];

        if ($idSasaranRpjmd !== []) {
            foreach ($db->table('rpjmd_sasaran')->select('id, tujuan_id')
                ->whereIn('id', $idSasaranRpjmd)->get()->getResultArray() as $r) {
                $tujuanDariSasaran[(int) $r['id']] = (int) $r['tujuan_id'];
            }
        }

        foreach ($sasaran as &$s) {
            if ($s['rpjmd_sasaran_id'] !== null && isset($tujuanDariSasaran[$s['rpjmd_sasaran_id']])) {
                $s['tujuan_id'] = $tujuanDariSasaran[$s['rpjmd_sasaran_id']];
            } elseif (! empty($s['rpjmd_tujuan_id'])) {
                // --- jangkar 3: manual, tujuan saja ---------------------
                $s['rpjmd_sasaran_id'] = null;
                $s['tujuan_id']        = (int) $s['rpjmd_tujuan_id'];
                $s['jangkar']          = 'tujuan';
            }
        }
        unset($s);

        $idTujuan = array_values(array_unique(array_filter(array_column($sasaran, 'tujuan_id'))));

        if ($idTujuan !== []) {
            $misiDariTujuan = [];

            foreach ($db->table('rpjmd_tujuan')->select('id, misi_id')
                ->whereIn('id', $idTujuan)->get()->getResultArray() as $r) {
                $misiDariTujuan[(int) $r['id']] = (int) $r['misi_id'];
            }

            foreach ($sasaran as &$s) {
                if ($s['tujuan_id'] !== null) {
                    $s['misi_id'] = $misiDariTujuan[$s['tujuan_id']] ?? null;

                    // Tujuan yang tidak ada (terhapus) = tidak berjangkar.
                    if ($s['misi_id'] === null) {
                        $s['tujuan_id'] = null;
                        $s['jangkar']   = '';
                    }
                }
            }
            unset($s);
        }

        return $sasaran;
    }

    /**
     * Indikator IKU Kabupaten milik sasaran-sasaran di atas.
     *
     * @param int[] $sasaranIds
     *
     * @return array<int, array<string,mixed>> dikunci id indikator IKU; memuat
     *         id, iku_sasaran_id, indikator, satuan, baseline, source_type,
     *         source_indikator_id (rpjmd), es2_versi_status
     */
    private function indikatorKabIku(array $sasaranIds, ?int $ikuRevisiId = null): array
    {
        $db = $this->db;

        if ($sasaranIds === [] || ! $db->tableExists('iku_indikator')) {
            return [];
        }

        $dariVersi  = $ikuRevisiId !== null && $ikuRevisiId > 0 && $db->tableExists('iku_revisi_indikator');
        $adaSumber  = $db->fieldExists('source_indikator_id', 'iku_indikator');

        $q = $db->table('iku_indikator iki')
            ->select('iki.id, iki.iku_sasaran_id, iki.urutan, iki.source_type'
                . ($adaSumber ? ', iki.source_indikator_id' : ', NULL AS source_indikator_id')
                . ($dariVersi
                    ? ", COALESCE(NULLIF(rvi.indikator, ''), iki.indikator) AS indikator
                       , COALESCE(rvi.satuan_nama, NULLIF(rvi.satuan, ''), satiku.satuan, NULLIF(iki.satuan, '')) AS satuan
                       , COALESCE(NULLIF(rvi.baseline, ''), iki.baseline) AS baseline
                       , CASE WHEN rvi.id IS NULL THEN 'tidak_ada'
                              ELSE COALESCE(NULLIF(rvi.jenis_perubahan, ''), 'tetap') END AS es2_versi_status"
                    : ", iki.indikator
                       , COALESCE(satiku.satuan, NULLIF(iki.satuan, '')) AS satuan
                       , iki.baseline
                       , '' AS es2_versi_status"), false)
            ->join('satuan satiku', "satiku.id = iki.satuan AND iki.satuan REGEXP '^[0-9]+$'", 'left', false)
            ->whereIn('iki.iku_sasaran_id', $sasaranIds)
            ->where('iki.dihentikan_pada IS NULL', null, false)
            ->orderBy('iki.iku_sasaran_id', 'ASC')
            ->orderBy('iki.urutan', 'ASC')
            ->orderBy('iki.id', 'ASC');

        if ($dariVersi) {
            $q->join(
                'iku_revisi_indikator rvi',
                'rvi.sumber_indikator_id = iki.id AND rvi.revisi_id = ' . (int) $ikuRevisiId,
                'left',
                false
            );
        }

        $indikator = [];

        foreach ($q->get()->getResultArray() as $r) {
            $indikator[(int) $r['id']] = $r;
        }

        return $indikator;
    }

    /**
     * Teks Misi & Tujuan RPJMD satu periode, untuk dipasangkan ke jangkar.
     *
     * @return array{misi: array<int,string>, tujuan: array<int,array{teks:string, misi_id:int}>}
     */
    private function misiTujuanRpjmd(int $start, int $end): array
    {
        $misi   = [];
        $tujuan = [];

        $rows = $this->db->table('rpjmd_misi m')
            ->select('m.id AS misi_id, m.misi, t.id AS tujuan_id, t.tujuan_rpjmd')
            ->join('rpjmd_tujuan t', 't.misi_id = m.id', 'left')
            ->where('m.tahun_mulai', $start)
            ->where('m.tahun_akhir', $end)
            ->orderBy('m.id', 'ASC')
            ->orderBy('t.id', 'ASC')
            ->get()->getResultArray();

        foreach ($rows as $r) {
            $misi[(int) $r['misi_id']] = (string) $r['misi'];

            if ($r['tujuan_id'] !== null) {
                $tujuan[(int) $r['tujuan_id']] = ['teks' => (string) $r['tujuan_rpjmd'], 'misi_id' => (int) $r['misi_id']];
            }
        }

        return ['misi' => $misi, 'tujuan' => $tujuan];
    }

    /**
     * Target per indikator IKU: iku_target dulu; bila kosong, target RPJMD
     * indikator sumbernya (silsilah) — supaya baris hasil sync yang targetnya
     * belum disalin tidak tampil kosong.
     *
     * @param array<int, array<string,mixed>> $indikator hasil indikatorKabIku()
     *
     * @return array<int, array<int|string, mixed>> [id indikator IKU => [tahun => target]]
     */
    private function targetKabIku(array $indikator): array
    {
        $db  = $this->db;
        $ids = array_keys($indikator);
        $map = [];

        if ($ids === []) {
            return [];
        }

        if ($db->tableExists('iku_target')) {
            foreach ($db->table('iku_target')->select('iku_indikator_id, tahun, target')
                ->whereIn('iku_indikator_id', $ids)->get()->getResultArray() as $t) {
                $map[(int) $t['iku_indikator_id']][$t['tahun']] = $t['target'];
            }
        }

        $butuhRpjmd = [];

        foreach ($indikator as $id => $i) {
            if (empty($map[$id]) && ! empty($i['source_indikator_id'])) {
                $butuhRpjmd[(int) $i['source_indikator_id']][] = $id;
            }
        }

        if ($butuhRpjmd !== []) {
            foreach ($db->table('rpjmd_target')->select('indikator_sasaran_id, tahun, target_tahunan')
                ->whereIn('indikator_sasaran_id', array_keys($butuhRpjmd))->get()->getResultArray() as $t) {
                foreach ($butuhRpjmd[(int) $t['indikator_sasaran_id']] as $id) {
                    $map[$id][$t['tahun']] = $t['target_tahunan'];
                }
            }
        }

        return $map;
    }

    /**
     * Matriks Cascading Kabupaten: satu baris per (indikator IKU, OPD, program).
     *
     * Bentuk barisnya sama dengan sebelum 14 Sep 2026 — lihat catatan di atas.
     * Baris sasaran yang belum berjangkar membawa `misi_id`, `tujuan_id`,
     * `misi`, `tujuan_rpjmd` = NULL dan `jangkar` = ''.
     *
     * @return list<array<string,mixed>>
     */
    public function getMatrix($start, $end, ?int $ikuRevisiId = null)
    {
        $start = (int) $start;
        $end   = (int) $end;

        $sasaran = $this->sasaranKabIku($start, $end, $ikuRevisiId);

        if ($sasaran === []) {
            return [];
        }

        $indikator = $this->indikatorKabIku(array_keys($sasaran), $ikuRevisiId);
        $rpjmd     = $this->misiTujuanRpjmd($start, $end);
        $targets   = $this->targetKabIku($indikator);

        // CSF tersimpan pada sasaran RPJMD (rpjmd_sasaran.csf); ikut tampil
        // untuk sasaran yang berjangkar ke sana.
        $csf = [];
        $idSasaranRpjmd = array_values(array_unique(array_filter(array_column($sasaran, 'rpjmd_sasaran_id'))));

        if ($idSasaranRpjmd !== []) {
            foreach ($this->db->table('rpjmd_sasaran')->select('id, csf')
                ->whereIn('id', $idSasaranRpjmd)->get()->getResultArray() as $r) {
                $csf[(int) $r['id']] = $r['csf'];
            }
        }

        // Sumber OPD & Program — helper bersama dengan pohon kinerja.
        $opdBySasaran      = $this->opdBySasaranMap();              // sasaran RPJMD => [opd_id => nama]
        $manualByIndikator = $this->manualMappingMap($start, $end); // indikator IKU => [opd_id => [...]]
        $programByOpd      = $this->programByOpdMap();              // opd_id => [program,...]

        // Indikator per sasaran, urutan dokumen.
        $indPerSasaran = [];

        foreach ($indikator as $id => $i) {
            $indPerSasaran[(int) $i['iku_sasaran_id']][] = $id;
        }

        // Urutan baris: Misi -> Tujuan -> urutan sasaran IKU; yang belum
        // berjangkar di EKOR, supaya tidak menyela dokumen.
        $urut = array_values($sasaran);
        usort($urut, static function ($a, $b) {
            $ka = [$a['misi_id'] === null ? 1 : 0, (int) $a['misi_id'], (int) $a['tujuan_id'], (int) $a['urutan'], (int) $a['id']];
            $kb = [$b['misi_id'] === null ? 1 : 0, (int) $b['misi_id'], (int) $b['tujuan_id'], (int) $b['urutan'], (int) $b['id']];

            return $ka <=> $kb;
        });

        $rows = [];

        foreach ($urut as $s) {
            $sid    = (int) $s['id'];
            $tujuan = $s['tujuan_id'] !== null ? ($rpjmd['tujuan'][$s['tujuan_id']] ?? null) : null;

            $dasar = [
                'misi_id'          => $s['misi_id'],
                'misi'             => $s['misi_id'] !== null ? ($rpjmd['misi'][$s['misi_id']] ?? null) : null,
                'tujuan_id'        => $s['tujuan_id'],
                'tujuan_rpjmd'     => $tujuan['teks'] ?? null,
                'sasaran_id'       => $sid,
                'sasaran_rpjmd'    => $s['sasaran'],
                'csf'              => $s['rpjmd_sasaran_id'] !== null ? ($csf[$s['rpjmd_sasaran_id']] ?? null) : null,
                'rpjmd_sasaran_id' => $s['rpjmd_sasaran_id'],
                'jangkar'          => $s['jangkar'],
            ];

            $daftarInd = $indPerSasaran[$sid] ?? [];

            if ($daftarInd === []) {
                // Sasaran tanpa indikator tetap tampil satu baris.
                $rows[] = $dasar + [
                    'indikator_id'       => null,
                    'indikator_sasaran'  => null,
                    'satuan'             => null,
                    'baseline'           => null,
                    'iku_indikator_id'   => null,
                    'rpjmd_indikator_id' => null,
                    'es2_versi_status'   => '',
                    'nama_opd'           => null,
                    'program_kegiatan'   => null,
                    'is_mapped'          => 0,
                    'targets'            => [],
                ];
                continue;
            }

            foreach ($daftarInd as $iid) {
                $i = $indikator[$iid];

                $b = $dasar + [
                    'indikator_id'       => $iid,
                    'indikator_sasaran'  => $i['indikator'],
                    'satuan'             => $i['satuan'],
                    'baseline'           => $i['baseline'],
                    // Dipertahankan: view lama menandai "dari IKU" lewat kolom ini.
                    'iku_indikator_id'   => $iid,
                    'rpjmd_indikator_id' => ! empty($i['source_indikator_id']) ? (int) $i['source_indikator_id'] : null,
                    'es2_versi_status'   => $i['es2_versi_status'] ?? '',
                    'targets'            => $targets[$iid] ?? [],
                ];

                // =====================================================
                // OPD & PROGRAM: MANUAL MENGGANTIKAN OTOMATIS (sejak 14 Sep 2026)
                //
                // Tanpa mapping manual, kolom Perangkat Daerah/Program diisi
                // PENURUNAN OTOMATIS: OPD dari rantai Renstra yang berjangkar
                // ke sasaran RPJMD ini, programnya seluruh program PK JPT tiap
                // OPD. Begitu pemakai menyimpan mapping manual untuk indikator
                // ini, mapping itulah satu-satunya yang tampil.
                //
                // Dulu keduanya DIGABUNG (OPD otomatis ∪ manual). Akibatnya
                // tombol "Edit" tidak bisa membuang OPD yang tidak relevan —
                // apa pun yang dihapus di form muncul lagi dari penurunan
                // otomatis, dan layar terbaca "sudah ada data" padahal form
                // editnya kosong. Form edit kini terisi dari penurunan
                // otomatis (lihat CascadingController::tambah()), jadi yang
                // disimpan adalah keadaan utuh, bukan tambalan.
                // =====================================================
                $manual   = $manualByIndikator[$iid] ?? [];
                $isMapped = $manual !== [] ? 1 : 0;

                if ($manual !== []) {
                    $opdSet = [];
                    foreach ($manual as $opdId => $info) {
                        $opdSet[$opdId] = $info['nama_opd'];
                    }
                } else {
                    $opdSet = $s['rpjmd_sasaran_id'] !== null ? ($opdBySasaran[$s['rpjmd_sasaran_id']] ?? []) : [];
                }

                asort($opdSet);

                if ($opdSet === []) {
                    $rows[] = $b + ['nama_opd' => null, 'program_kegiatan' => null, 'is_mapped' => $isMapped];
                    continue;
                }

                foreach ($opdSet as $opdId => $namaOpd) {
                    // Program: manual bila ada mapping; selain itu seluruh
                    // program PK JPT milik OPD itu (penurunan otomatis).
                    $manualPrograms = $manual[$opdId]['programs'] ?? [];
                    $programs = $manual !== [] ? $manualPrograms : ($programByOpd[$opdId] ?? []);

                    if ($programs === []) {
                        $rows[] = $b + ['nama_opd' => $namaOpd, 'program_kegiatan' => null, 'is_mapped' => $isMapped];
                        continue;
                    }

                    foreach ($programs as $prog) {
                        $rows[] = $b + ['nama_opd' => $namaOpd, 'program_kegiatan' => $prog, 'is_mapped' => $isMapped];
                    }
                }
            }
        }

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
            // OPD sistem (BAGIAN ADMIN dkk.) tidak pernah menjadi penanggung
            // jawab; ia sempat muncul di kolom Perangkat Daerah karena punya
            // baris Renstra uji yang berjangkar ke sasaran RPJMD.
            ->whereNotIn('rs.opd_id', \App\Models\OpdModel::EXCLUDED_OPD_IDS)
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
     * Kolom kunci mapping manual: `iku_indikator_id` sejak
     * db/update_2026-09-14_jangkar_rpjmd_iku_kabupaten.sql; basis data yang
     * belum dimigrasi masih berkunci indikator RPJMD (`indikator_sasaran_id`)
     * — di sana mapping hanya bisa dibuat untuk indikator IKU yang punya
     * silsilah RPJMD, dan kuncinya diterjemahkan lewat silsilah itu.
     */
    private function kolomKunciMapping(): string
    {
        return $this->db->fieldExists('iku_indikator_id', 'rpjmd_cascading')
            ? 'iku_indikator_id'
            : 'indikator_sasaran_id';
    }

    /**
     * Peta id indikator RPJMD -> id indikator IKU Kabupaten (silsilah), untuk
     * membaca mapping lama pada basis data yang belum dimigrasi.
     *
     * @return array<int,int>
     */
    private function ikuDariRpjmdIndikator(): array
    {
        if (! $this->db->fieldExists('source_indikator_id', 'iku_indikator')) {
            return [];
        }

        $peta = [];

        foreach ($this->db->table('iku_indikator iki')
            ->select('iki.id, iki.source_indikator_id')
            ->join('iku_sasaran iks', 'iks.id = iki.iku_sasaran_id')
            ->where('iks.opd_id IS NULL', null, false)
            ->where('iki.source_type', 'rpjmd')
            ->where('iki.source_indikator_id IS NOT NULL', null, false)
            ->get()->getResultArray() as $r) {
            $peta[(int) $r['source_indikator_id']] = (int) $r['id'];
        }

        return $peta;
    }

    /**
     * Mapping manual cascading (rpjmd_cascading) untuk satu periode.
     * @return array indikator IKU => [ opd_id => ['nama_opd' => ..., 'programs' => [...] ] ]
     */
    private function manualMappingMap($start, $end): array
    {
        $kunci = $this->kolomKunciMapping();

        $rows = $this->db->table('rpjmd_cascading map')
            ->select("map.{$kunci} AS kunci, map.opd_id, o.nama_opd, p.program_kegiatan", false)
            ->join('pk_program pp', 'pp.id = map.pk_program_id', 'left')
            ->join('program_pk p', 'p.id = pp.program_id', 'left')
            ->join('opd o', 'o.id = map.opd_id', 'left')
            ->where('map.tahun >=', (int) $start)
            ->where('map.tahun <=', (int) $end)
            ->get()
            ->getResultArray();

        // DB lama: kunci RPJMD diterjemahkan ke indikator IKU-nya.
        $terjemah = $kunci === 'iku_indikator_id' ? null : $this->ikuDariRpjmdIndikator();

        $map = [];
        foreach ($rows as $row) {
            $ind = (int) $row['kunci'];

            if ($terjemah !== null) {
                if (! isset($terjemah[$ind])) {
                    continue;
                }
                $ind = $terjemah[$ind];
            }

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

    /**
     * Indikator IKU Kabupaten untuk layar mapping (tambah/edit cascading).
     *
     * @return array{id:int, indikator_sasaran:string, satuan:?string, sasaran:string,
     *               iku_sasaran_id:int, rpjmd_indikator_id:?int}|null
     */
    public function indikatorIkuKab(int $ikuIndikatorId): ?array
    {
        if ($ikuIndikatorId <= 0 || ! $this->db->tableExists('iku_indikator')) {
            return null;
        }

        $adaSumber = $this->db->fieldExists('source_indikator_id', 'iku_indikator');

        $r = $this->db->table('iku_indikator iki')
            ->select('iki.id, iki.indikator AS indikator_sasaran, iki.iku_sasaran_id, iks.sasaran'
                . ", COALESCE(satiku.satuan, NULLIF(iki.satuan, '')) AS satuan"
                . ($adaSumber ? ', iki.source_indikator_id AS rpjmd_indikator_id' : ', NULL AS rpjmd_indikator_id'), false)
            ->join('iku_sasaran iks', 'iks.id = iki.iku_sasaran_id')
            ->join('satuan satiku', "satiku.id = iki.satuan AND iki.satuan REGEXP '^[0-9]+$'", 'left', false)
            ->where('iki.id', $ikuIndikatorId)
            ->where('iks.opd_id IS NULL', null, false)
            ->get()->getRowArray();

        if (! $r) {
            return null;
        }

        $r['id']                 = (int) $r['id'];
        $r['iku_sasaran_id']     = (int) $r['iku_sasaran_id'];
        $r['rpjmd_indikator_id'] = ! empty($r['rpjmd_indikator_id']) ? (int) $r['rpjmd_indikator_id'] : null;

        return $r;
    }

    /**
     * Penurunan OTOMATIS untuk satu indikator IKU Kabupaten — OPD dari rantai
     * Renstra yang berjangkar ke sasaran RPJMD-nya, beserta program PK JPT
     * tiap OPD pada tahun yang diminta. Inilah yang tampil di Cascading
     * selama indikator itu belum punya mapping manual, dan inilah isi awal
     * form mapping supaya "Edit" berangkat dari keadaan yang terlihat.
     *
     * @return array<int, list<int>> [opd_id => [pk_program.id, ...]] (program bisa kosong)
     */
    public function penurunanOtomatis(int $ikuIndikatorId, int $start, int $end, int $tahun): array
    {
        $ind = $this->indikatorIkuKab($ikuIndikatorId);

        if ($ind === null) {
            return [];
        }

        $sasaran = $this->sasaranKabIku($start, $end)[$ind['iku_sasaran_id']] ?? null;

        if ($sasaran === null || $sasaran['rpjmd_sasaran_id'] === null) {
            return [];
        }

        $hasil = [];

        foreach (array_keys($this->opdBySasaranMap()[$sasaran['rpjmd_sasaran_id']] ?? []) as $opdId) {
            $hasil[(int) $opdId] = array_map(
                'intval',
                array_column($this->getPkProgramByOpd((int) $opdId, $tahun), 'id')
            );
        }

        return $hasil;
    }

    /**
     * Bolehkah indikator IKU ini dipetakan pada basis data ini?
     *
     * Selalu boleh setelah migrasi. Sebelum migrasi, hanya indikator yang punya
     * silsilah RPJMD — kuncinya masih `indikator_sasaran_id` NOT NULL.
     */
    public function bolehDipetakan(array $indikatorIku): bool
    {
        return $this->kolomKunciMapping() === 'iku_indikator_id'
            || ! empty($indikatorIku['rpjmd_indikator_id']);
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

    /**
     * @param list<array{iku_indikator_id:int, indikator_sasaran_id:?int, opd_id:int, pk_program_id:int, tahun:int}> $data
     */
    public function saveBatchMapping(array $data)
    {
        if (empty($data))
            return false;

        if ($this->kolomKunciMapping() !== 'iku_indikator_id') {
            // DB lama: kolom iku_indikator_id belum ada; baris tanpa silsilah
            // RPJMD tidak bisa disimpan (kuncinya NOT NULL).
            $data = array_values(array_filter(array_map(static function ($d) {
                unset($d['iku_indikator_id']);
                return $d;
            }, $data), static fn ($d) => ! empty($d['indikator_sasaran_id'])));

            if ($data === []) {
                return false;
            }
        }

        // Tanpa INSERT IGNORE: kembar sudah dirapikan pemanggil, dan galat
        // (FK, kolom) harus sampai ke pemanggil supaya transaksinya dibatalkan
        // — bukan dibungkam lalu dilaporkan "berhasil".
        return $this->db->table($this->table)->insertBatch($data);
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

    /**
     * @param int      $ikuIndikatorId   indikator IKU Kabupaten
     * @param int|null $rpjmdIndikatorId silsilahnya (kunci pada DB yang belum dimigrasi)
     */
    public function getExistingMapping($ikuIndikatorId, $tahun, ?int $rpjmdIndikatorId = null)
    {
        $kunci = $this->kolomKunciMapping();
        $nilai = $kunci === 'iku_indikator_id' ? (int) $ikuIndikatorId : (int) $rpjmdIndikatorId;

        if ($nilai <= 0) {
            return [];
        }

        return $this->db->table('rpjmd_cascading c')
            ->select('c.opd_id, c.pk_program_id')
            ->where('c.' . $kunci, $nilai)
            ->where('c.tahun', $tahun)
            ->get()
            ->getResultArray();
    }

    public function deleteByIndikatorAndYear($ikuIndikatorId, $tahun, ?int $rpjmdIndikatorId = null)
    {
        $kunci = $this->kolomKunciMapping();
        $nilai = $kunci === 'iku_indikator_id' ? (int) $ikuIndikatorId : (int) $rpjmdIndikatorId;

        if ($nilai <= 0) {
            return false;
        }

        return $this->db->table($this->table)
            ->where($kunci, $nilai)
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
    // =====================================================================
    // SUMBER TAMPILAN ESELON II: IKU dulu, Renstra sebagai jaring pengaman.
    //
    // Sejak db/update_2026-08-27_cascading_sumber_iku.sql, baris cascading
    // boleh berjangkar ke IKU lewat `iku_indikator_id`. Bila jangkar itu
    // terisi, teks yang DITAMPILKAN diambil dari IKU — sehingga revisi IKU
    // langsung terbaca di cascading tanpa menunggu Renstra ikut diubah.
    // Bila kosong (belum dipetakan, atau OPD-nya memang belum selaras),
    // COALESCE jatuh ke Renstra dan tampilannya persis seperti sebelumnya.
    //
    // Nama kolom hasil sengaja TIDAK diubah (`renstra_sasaran`,
    // `indikator_sasaran`, `satuan`): 20+ pemanggil — view OPD, view
    // Kabupaten, ekspor Excel, cetak, API, dan analisis AI — membacanya
    // dengan nama itu.
    // =====================================================================

    /** `iku_indikator.satuan` menyimpan id numerik ke `satuan`, atau teks bebas. */
    private const SATUAN_JOIN_IKU = "siku.id = iki.satuan AND iki.satuan REGEXP '^[0-9]+$'";

    private const SASARAN_ES2_SELECT    = "COALESCE(NULLIF(iks.sasaran, ''), rs.sasaran)";
    private const INDIKATOR_ES2_SELECT  = "COALESCE(NULLIF(iki.indikator, ''), ris.indikator_sasaran)";
    private const SATUAN_ES2_SELECT     = "COALESCE(siku.satuan, NULLIF(iki.satuan, ''), ris.satuan)";

    /**
     * Apakah basis data ini sudah punya jangkar IKU pada cascading?
     *
     * Dipakai TAMPILAN untuk memutuskan apakah penanda "masih dari Renstra"
     * layak dicetak. Pada server yang belum menjalankan migrasi 2026-08-27,
     * SELURUH baris memang membaca Renstra — menandai semuanya hanya jadi
     * dinding lencana yang tidak menyuruh siapa pun berbuat apa-apa. Penanda
     * baru bermakna ketika sebagian baris sudah bisa berjangkar IKU dan
     * sebagian belum.
     */
    public function jangkarIkuTersedia(): bool
    {
        return $this->db->fieldExists('iku_indikator_id', 'cascading_sasaran_opd');
    }

    /**
     * SAKLAR AKAR CASCADING — satu-satunya tempat memutuskannya.
     *
     * 'renstra' : daftar baris Eselon II ditentukan Renstra, teks IKU
     *             dilapiskan di atasnya. Perilaku sejak awal.
     * 'iku'     : daftar barisnya ditentukan IKU, Renstra & RPJMD dijangkau
     *             balik lewat silsilah.
     *
     * Membalik konstanta ini membalik SISI BACA (query matriks) sekaligus
     * SISI TULIS (jangkar baris cascading baru) sekali jalan — keduanya
     * bertanya ke akarAktif(), tidak ada yang memutuskan sendiri-sendiri.
     *
     * Sebelum membaliknya, buktikan dulu tidak ada yang hilang:
     *
     *     php spark casc:akar-check
     */
    public const AKAR_BAWAAN = 'iku';

    /**
     * Akar yang benar-benar dipakai. Basis data yang belum punya kolom
     * jangkar IKU selalu jatuh ke 'renstra' — perilaku lamanya masih sah.
     */
    public function akarAktif(): string
    {
        return (self::AKAR_BAWAAN === 'iku' && $this->jangkarIkuTersedia()) ? 'iku' : 'renstra';
    }

    /**
     * Terjemahkan identitas Eselon II menjadi KEDUA jangkar baris cascading.
     *
     * Baris cascading selalu menyimpan dua jangkar: `renstra_indikator_sasaran_id`
     * dan `iku_indikator_id`. Yang berubah menurut akar hanyalah id MANA yang
     * dipegang tampilan — dan itulah yang diterjemahkan di sini, supaya alur
     * penyimpanan tidak perlu tahu akar mana yang sedang aktif.
     *
     * @return array{renstra_indikator_sasaran_id: int|null, iku_indikator_id: int|null}
     */
    public function jangkarDariEs2(int $identitas, ?string $akar = null): array
    {
        $akar = $akar ?? $this->akarAktif();

        if ($identitas <= 0) {
            return ['renstra_indikator_sasaran_id' => null, 'iku_indikator_id' => null];
        }

        if ($akar === 'iku') {
            // Arah balik: silsilah indikator IKU yang menyebut asal Renstra-nya.
            $baris = $this->db->table('iku_indikator')
                ->select('source_indikator_id')
                ->where('id', $identitas)
                ->get()->getRowArray();

            return [
                'renstra_indikator_sasaran_id' => isset($baris['source_indikator_id'])
                    ? (int) $baris['source_indikator_id'] : null,
                'iku_indikator_id' => $identitas,
            ];
        }

        return [
            'renstra_indikator_sasaran_id' => $identitas,
            'iku_indikator_id'             => $this->padananIkuIndikator($identitas),
        ];
    }

    /**
     * Pasangan Kabupaten dari jangkarIkuTersedia().
     *
     * Cascading Kabupaten tidak punya tabel jembatan; ia menyambung RPJMD ke
     * IKU Kabupaten lewat silsilah `iku_indikator.source_indikator_id` yang
     * diisi db/update_2026-08-28_silsilah_iku_kabupaten.sql.
     */
    public function silsilahIkuTersedia(): bool
    {
        return $this->db->fieldExists('source_indikator_id', 'iku_indikator');
    }

    /**
     * Padanan indikator IKU untuk sebuah indikator sasaran Renstra.
     *
     * Dipakai saat baris cascading BARU dibuat, supaya baris itu langsung
     * berjangkar ganda seperti hasil backfill migrasi — bukan lahir hanya
     * berjangkar Renstra lalu ikut mati bila indikator Renstra-nya dihapus.
     *
     * Silsilah didahulukan, teks belakangan: `source_indikator_id` ditulis
     * oleh Sync IKU dan menunjuk id, jadi ia tetap benar walau redaksinya
     * kemudian dirapikan. Pencocokan teks hanya jaring terakhir untuk baris
     * IKU yang diketik manual dan belum punya silsilah.
     *
     * @return int|null id `iku_indikator`, atau null bila padanannya tidak
     *                  tunggal — menebak lebih buruk daripada membiarkan
     *                  baris itu tetap membaca Renstra.
     */
    public function padananIkuIndikator($renstraIndikatorId): ?int
    {
        $renstraIndikatorId = (int) $renstraIndikatorId;

        if ($renstraIndikatorId <= 0
            || ! $this->db->fieldExists('source_indikator_id', 'iku_indikator')) {
            return null;
        }

        $punyaPensiun = $this->db->fieldExists('dihentikan_pada', 'iku_indikator');

        // 1. Lewat silsilah.
        $b = $this->db->table('iku_indikator')
            ->select('id')
            ->where('source_indikator_id', $renstraIndikatorId);

        if ($punyaPensiun) {
            $b->where('dihentikan_pada IS NULL', null, false);
        }

        $baris = $b->limit(2)->get()->getResultArray();

        if (count($baris) === 1) {
            return (int) $baris[0]['id'];
        }

        if ($baris !== []) {
            return null; // silsilah ganda: jangan menebak
        }

        // 2. Jaring terakhir: cocokkan teks, aturan sama persis dengan
        //    IkuModel::normalkanTeks() dan migrasi 2026-08-27.
        $rapikan = static fn (string $kolom): string
            => "TRIM(REGEXP_REPLACE({$kolom}, '[[:space:]]+', ' '))";

        $b = $this->db->table('renstra_indikator_sasaran ris')
            ->select('iki.id', false)
            ->join('renstra_sasaran rs', 'rs.id = ris.renstra_sasaran_id')
            ->join(
                'iku_sasaran iks',
                'iks.opd_id = rs.opd_id
                 AND iks.tahun_mulai = rs.tahun_mulai
                 AND iks.tahun_akhir = rs.tahun_akhir
                 AND ' . $rapikan('iks.sasaran') . ' = ' . $rapikan('rs.sasaran'),
                'inner',
                false
            )
            ->join(
                'iku_indikator iki',
                'iki.iku_sasaran_id = iks.id
                 AND ' . $rapikan('iki.indikator') . ' = ' . $rapikan('ris.indikator_sasaran'),
                'inner',
                false
            )
            ->where('ris.id', $renstraIndikatorId);

        if ($punyaPensiun) {
            $b->where('iki.dihentikan_pada IS NULL', null, false)
                ->where('iks.dihentikan_pada IS NULL', null, false);
        }

        $baris = $b->limit(2)->get()->getResultArray();

        return count($baris) === 1 ? (int) $baris[0]['id'] : null;
    }

    /**
     * @param int|null $ikuRevisiId Versi IKU yang dibaca. null = IKU BERJALAN.
     *                              Bila diisi, teks & target Eselon II diambil
     *                              dari ARSIP revisi itu, dan tiap baris diberi
     *                              penanda apakah induknya berubah/hilang pada
     *                              versi tersebut.
     * @param string   $akar        'renstra' (bawaan) | 'iku'
     *
     * =====================================================================
     * DUA AKAR, SATU DAFTAR KOLOM
     *
     * Matriks ini sejak awal dibangun `FROM renstra_sasaran`: daftar baris
     * Eselon II ditentukan Renstra, dan teks IKU hanya dilapiskan di atasnya
     * lewat COALESCE. Akar 'iku' membalik itu — daftar barisnya ditentukan
     * IKU, dan Renstra/RPJMD dijangkau balik lewat silsilah
     * (`iku_indikator.source_indikator_id`, `iku_sasaran.source_sasaran_id`).
     *
     * Keduanya memakai DAFTAR KOLOM YANG SAMA PERSIS; yang berbeda hanya
     * tabel pangkal, syarat sambungan baris cascading, dan penyaring
     * periodenya. Disatukan dalam satu metode dengan sengaja: dua salinan
     * query sepanjang ini pasti menyimpang cepat atau lambat.
     *
     * Bedanya bisa diukur, bukan dikira-kira:
     *
     *     php spark casc:akar-check
     *
     * Selama bawaannya masih 'renstra', akar 'iku' hanya dipakai perintah
     * itu — tampilan belum tersentuh.
     */
    public function getCascadingMatrixByOpd(
        $opdId,
        $startYear = null,
        $endYear = null,
        ?int $ikuRevisiId = null,
        ?string $akar = null
    ) {
        // null = ikuti saklar tunggal. Nilai tegas hanya dipakai casc:akar-check
        // untuk menjalankan kedua akar berdampingan.
        $akar = $akar ?? $this->akarAktif();

        // Hindari query rusak (ON clause "opd_id = NULL") bila OPD tidak diketahui,
        // mis. akun super admin yang tidak terikat OPD.
        if (empty($opdId)) {
            return [];
        }

        $dariVersi = $ikuRevisiId !== null && $ikuRevisiId > 0
            && $this->db->tableExists('iku_revisi_indikator');

        // Server yang belum menjalankan migrasi 2026-08-27 tidak punya kolom
        // jangkar IKU. Tanpa penjaga ini, seluruh menu Cascading mati dengan
        // "Unknown column" — padahal perilaku lamanya masih sah sepenuhnya.
        $adaJangkarIku = $this->db->fieldExists('iku_indikator_id', 'cascading_sasaran_opd');

        // Akar 'iku' MUSTAHIL tanpa kolom jangkar — tanpa itu tidak ada cara
        // menyambungkan baris cascading ke indikator IKU. Diam-diam kembali ke
        // akar Renstra, bukan melempar galat: perilaku lama tetap sah.
        $akarIku = $akar === 'iku' && $adaJangkarIku;

        // Membaca VERSI berarti membaca arsip revisi, bukan tabel berjalan.
        // COALESCE tetap berlapis ke Renstra supaya baris yang tidak punya
        // jangkar IKU sama sekali tidak berubah perilakunya.
        // Identitas Eselon II. Pada akar Renstra ia id indikator Renstra; pada
        // akar IKU ia id indikator IKU. Kunci inilah yang dipakai tampilan
        // untuk rowspan, pengelompokan, dan tautan "Tambah ESS III" — sehingga
        // membaliknya berarti membalik makna kolom `indikator_id`.
        $identitasEs2      = $akarIku ? 'iki.id'  : 'ris.id';
        // Sasaran Eselon II sebagai kunci pengelompokan kolom sebelahnya.
        $identitasSasaran  = $akarIku ? 'iks.id'  : 'rs.id';

        $sasaranEs2   = $adaJangkarIku
            ? ($dariVersi ? "COALESCE(NULLIF(rvs.sasaran, ''), " . self::SASARAN_ES2_SELECT . ")" : self::SASARAN_ES2_SELECT)
            : 'rs.sasaran';
        $indikatorEs2 = $adaJangkarIku
            ? ($dariVersi ? "COALESCE(NULLIF(rvi.indikator, ''), " . self::INDIKATOR_ES2_SELECT . ")" : self::INDIKATOR_ES2_SELECT)
            : 'ris.indikator_sasaran';
        $satuanEs2    = $adaJangkarIku
            ? ($dariVersi ? "COALESCE(rvi.satuan_nama, NULLIF(rvi.satuan, ''), " . self::SATUAN_ES2_SELECT . ")" : self::SATUAN_ES2_SELECT)
            : 'ris.satuan';
        $lineageEs2   = $adaJangkarIku
            ? 'es3.source_type as es2_source_type, es3.iku_indikator_id as es2_iku_indikator_id,'
            : "'renstra' as es2_source_type, NULL as es2_iku_indikator_id,";

        // Penanda per baris: apa yang terjadi pada indikator induk di versi ini.
        //   tetap|revisi|baru|pengganti|dihentikan  -> ada di versi, jenisnya apa
        //   tidak_ada                               -> jangkarnya tidak dibawa versi ini
        //   ''                                      -> tidak sedang membaca versi
        $statusVersi = $dariVersi
            ? "CASE WHEN es3.iku_indikator_id IS NULL THEN ''
                    WHEN rvi.id IS NULL THEN 'tidak_ada'
                    ELSE COALESCE(NULLIF(rvi.jenis_perubahan, ''), 'tetap') END"
            : "''";

        $builder = $this->db->table($akarIku ? 'iku_sasaran iks' : 'renstra_sasaran rs')
            ->select("
            t.id as tujuan_id,
            t.tujuan_rpjmd,

            s.id as sasaran_id,
            s.sasaran_rpjmd,

            rt.id as renstra_tujuan_id,
            rt.tujuan as renstra_tujuan,

            rit.id as indikator_tujuan_id,
            rit.indikator_tujuan,

            rs.csf as csf_es2,
            {$identitasSasaran} as renstra_sasaran_id,
            {$sasaranEs2} as renstra_sasaran,

            {$identitasEs2} as indikator_id,
            {$indikatorEs2} as indikator_sasaran,
            {$satuanEs2} as satuan,

            {$lineageEs2}
            {$statusVersi} as es2_versi_status,

            es3.csf as csf_es3,
            es3.id as es3_id,
            es3.nama_sasaran as es3_sasaran,

            i3.id as es3_indikator_id,
            i3.indikator as es3_indikator,

            es4.csf as csf_es4,
            es4.id as es4_id,
            es4.nama_sasaran as es4_sasaran,

            i4.id as es4_indikator_id,
            i4.indikator as es4_indikator,

            pel.csf as csf_pelaksana,
            pel.id as pelaksana_id,
            pel.nama_sasaran as pelaksana_sasaran,

            ipel.id as pelaksana_indikator_id,
            ipel.indikator as pelaksana_indikator
        ");

        // DB yang belum dimigrasi tetap dilayani dengan perilaku lama.
        $tujuanIku = $this->db->fieldExists('renstra_tujuan_id', 'iku_sasaran')
            ? 'rt.id = COALESCE(rs.renstra_tujuan_id, iks.renstra_tujuan_id)'
            : 'rt.id = rs.renstra_tujuan_id';

        if ($akarIku) {
            // Pangkalnya IKU. Renstra dan RPJMD dijangkau BALIK lewat silsilah:
            // indikator dulu (`source_indikator_id`), dan bila indikatornya
            // belum bersilsilah, lewat sasarannya (`source_sasaran_id`) —
            // supaya tulang punggung RPJMD tetap terangkai sejauh mungkin.
            $builder
                ->join('iku_indikator iki', 'iki.iku_sasaran_id = iks.id AND iki.dihentikan_pada IS NULL', 'inner', false)
                ->join('renstra_indikator_sasaran ris', 'ris.id = iki.source_indikator_id', 'left')
                ->join('renstra_sasaran rs', 'rs.id = COALESCE(ris.renstra_sasaran_id, iks.source_sasaran_id)', 'left', false)
                // TUJUAN SASARAN MANDIRI.
                //
                // Sasaran yang LAHIR di IKU tidak punya `source_sasaran_id`,
                // sehingga `rs` kosong dan tujuannya tak terjangkau — barisnya
                // tampil dengan empat kolom kosong dan berdiri sebagai pulau
                // sendiri di luar blok Tujuan mana pun.
                //
                // Ia menunjuk tujuannya SENDIRI lewat `renstra_tujuan_id`.
                // Urutannya penting: `rs` didahulukan, jadi sasaran hasil sync
                // tetap menurunkan tujuan lewat silsilahnya seperti semula dan
                // kolom ini tidak pernah ikut bicara untuk mereka.
                ->join('renstra_tujuan rt', $tujuanIku, 'left', false)
                ->join('renstra_indikator_tujuan rit', 'rit.tujuan_id=rt.id', 'left')
                ->join('rpjmd_sasaran s', 's.id=rt.rpjmd_sasaran_id', 'left')
                ->join('rpjmd_tujuan t', 't.id=s.tujuan_id', 'left');
        } else {
            // Urutan join jalur Renstra sengaja dibiarkan persis seperti semula.
            $builder
                ->join('renstra_tujuan rt', 'rt.id=rs.renstra_tujuan_id', 'left')
                ->join('renstra_indikator_tujuan rit', 'rit.tujuan_id=rt.id', 'left')
                ->join('rpjmd_sasaran s', 's.id=rt.rpjmd_sasaran_id', 'left')
                ->join('rpjmd_tujuan t', 't.id=s.tujuan_id', 'left')
                ->join('renstra_indikator_sasaran ris', 'ris.renstra_sasaran_id=rs.id', 'left');
        }

        $builder
            ->join(
                'cascading_sasaran_opd es3',
                ($akarIku ? 'es3.iku_indikator_id = iki.id' : 'es3.renstra_indikator_sasaran_id = ris.id')
            . ' AND es3.level="es3"
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
            // Jenjang PELAKSANA: pola yang sama persis dengan es4, satu tingkat
            // lebih dalam. `es3_indikator_id` di sini berisi id indikator ES IV
            // (kolomnya memang bermakna "indikator induk", lihat migrasi
            // 2026-07-27-000009).
            ->join(
                'cascading_sasaran_opd pel',
                'pel.es3_indikator_id = i4.id AND pel.level="pelaksana"',
                'left'
            )
            ->join(
                'cascading_indikator_opd ipel',
                'ipel.cascading_sasaran_id = pel.id',
                'left'
            )
            ->where($akarIku ? 'iks.opd_id' : 'rs.opd_id', $opdId);

        // Jangkar IKU baris ES III — menentukan teks Eselon II yang tampil.
        // Ditambahkan belakangan dengan sengaja: alias `es3` sudah terpasang
        // di atas, dan MySQL hanya menuntut tabel yang diacu ON sudah lebih
        // dulu ada di urutan FROM, bukan tepat sebelumnya.
        if ($adaJangkarIku) {
            // Pada akar IKU, `iki` dan `iks` SUDAH menjadi pangkal query —
            // menyambungnya lagi di sini akan menabrak alias yang sama.
            if (! $akarIku) {
                $builder->join('iku_indikator iki', 'iki.id = es3.iku_indikator_id', 'left')
                    ->join('iku_sasaran iks', 'iks.id = iki.iku_sasaran_id', 'left');
            }

            $builder->join('satuan siku', self::SATUAN_JOIN_IKU, 'left', false);

            // Arsip versi terpilih. Jembatannya `sumber_indikator_id`, yang
            // menunjuk id indikator IKU BERJALAN — kunci yang sama dengan
            // jangkar cascading, sehingga baris tetap ketemu walau teksnya
            // sudah berubah di versi itu.
            if ($dariVersi) {
                $builder
                    ->join(
                        'iku_revisi_indikator rvi',
                        // Pada akar IKU, jembatannya indikator IKU itu sendiri:
                        // baris Eselon II tetap punya arsip versi walau belum
                        // ada satu pun baris cascading di bawahnya.
                        ($akarIku ? 'rvi.sumber_indikator_id = iki.id' : 'rvi.sumber_indikator_id = es3.iku_indikator_id')
                        . ' AND rvi.revisi_id = ' . (int) $ikuRevisiId,
                        'left',
                        false
                    )
                    ->join('iku_revisi_sasaran rvs', 'rvs.id = rvi.revisi_sasaran_id', 'left');
            }
        }

        if ($startYear && $endYear) {
            // Periode disaring pada tabel PANGKAL. Menyaring lewat Renstra saat
            // akar IKU akan membuang baris IKU yang belum bersilsilah — padahal
            // justru baris itulah yang hanya ada di IKU.
            $builder->where($akarIku ? 'iks.tahun_mulai' : 'rs.tahun_mulai', $startYear);
            $builder->where($akarIku ? 'iks.tahun_akhir' : 'rs.tahun_akhir', $endYear);
        }

        // Pada akar IKU, urutan pangkalnya ikut IKU: sasaran & indikator IKU
        // punya `urutan` sendiri yang menentukan bagaimana dokumen itu dibaca.
        if ($akarIku) {
            $builder->orderBy('iks.urutan', 'ASC')->orderBy('iks.id', 'ASC')
                ->orderBy('iki.urutan', 'ASC')->orderBy('iki.id', 'ASC');
        }

        $rows = $builder
            ->orderBy('t.id', 'ASC')
            ->orderBy('s.id', 'ASC')
            ->orderBy('rt.id', 'ASC')
            ->orderBy('rs.id', 'ASC')
            ->orderBy('ris.id', 'ASC')
            ->orderBy('es3.id', 'ASC')
            ->orderBy('i3.id', 'ASC')
            ->orderBy('es4.id', 'ASC')
            ->orderBy('i4.id', 'ASC')
            ->orderBy('pel.id', 'ASC')
            ->orderBy('ipel.id', 'ASC')
            ->orderBy('rit.id', 'ASC')
            ->get()
            ->getResultArray();

        return $this->alignIndikatorTujuanRows($rows);
    }

    private function alignIndikatorTujuanRows(array $rows): array
    {
        if (empty($rows)) {
            return [];
        }

        $groups = [];
        $groupOrder = [];

        foreach ($rows as $row) {
            $groupKey = !empty($row['renstra_tujuan_id'])
                ? 'rt_' . $row['renstra_tujuan_id']
                : 'row_' . $this->cascadeMatrixRowKey($row);

            if (!isset($groups[$groupKey])) {
                $groups[$groupKey] = [
                    'base' => $row,
                    'indikator_tujuan' => [],
                    'cascade_rows' => [],
                ];
                $groupOrder[] = $groupKey;
            }

            if (!empty($row['indikator_tujuan_id'])) {
                $indikatorKey = (string) $row['indikator_tujuan_id'];
                $groups[$groupKey]['indikator_tujuan'][$indikatorKey] = [
                    'indikator_tujuan_id' => $row['indikator_tujuan_id'],
                    'indikator_tujuan' => $row['indikator_tujuan'],
                ];
            }

            $cascadeKey = $this->cascadeMatrixRowKey($row);
            if (!isset($groups[$groupKey]['cascade_rows'][$cascadeKey])) {
                $cascadeRow = $row;
                $cascadeRow['indikator_tujuan_id'] = null;
                $cascadeRow['indikator_tujuan'] = null;
                $groups[$groupKey]['cascade_rows'][$cascadeKey] = $cascadeRow;
            }
        }

        $aligned = [];

        foreach ($groupOrder as $groupKey) {
            $group = $groups[$groupKey];
            $indikatorTujuan = array_values($group['indikator_tujuan']);
            $cascadeRows = array_values($group['cascade_rows']);
            $totalRows = max(count($indikatorTujuan), count($cascadeRows), 1);
            $indikatorTujuanCount = count($indikatorTujuan);

            for ($i = 0; $i < $totalRows; $i++) {
                $row = $cascadeRows[$i] ?? $this->blankCascadeMatrixRow($group['base']);

                if ($indikatorTujuanCount > 0) {
                    $indikatorIndex = min(
                        $indikatorTujuanCount - 1,
                        (int) floor($i * $indikatorTujuanCount / $totalRows)
                    );

                    $row['indikator_tujuan_id'] = $indikatorTujuan[$indikatorIndex]['indikator_tujuan_id'];
                    $row['indikator_tujuan'] = $indikatorTujuan[$indikatorIndex]['indikator_tujuan'];
                } else {
                    $row['indikator_tujuan_id'] = null;
                    $row['indikator_tujuan'] = null;
                }

                $aligned[] = $row;
            }
        }

        return $aligned;
    }

    private function cascadeMatrixRowKey(array $row): string
    {
        $fields = [
            'renstra_sasaran_id',
            'indikator_id',
            'es3_id',
            'es3_indikator_id',
            'es4_id',
            'es4_indikator_id',
            'pelaksana_id',
            'pelaksana_indikator_id',
        ];

        $parts = [];
        foreach ($fields as $field) {
            $parts[] = (string) ($row[$field] ?? '');
        }

        return implode('|', $parts);
    }

    private function blankCascadeMatrixRow(array $base): array
    {
        foreach ([
            'csf_es2',
            'renstra_sasaran_id',
            'renstra_sasaran',
            'indikator_id',
            'indikator_sasaran',
            'satuan',
            'csf_es3',
            'es3_id',
            'es3_sasaran',
            'es3_indikator_id',
            'es3_indikator',
            'csf_es4',
            'es4_id',
            'es4_sasaran',
            'es4_indikator_id',
            'es4_indikator',
            'csf_pelaksana',
            'pelaksana_id',
            'pelaksana_sasaran',
            'pelaksana_indikator_id',
            'pelaksana_indikator',
        ] as $field) {
            $base[$field] = null;
        }

        return $base;
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
     * Pohon Kinerja Kabupaten: Misi -> Tujuan (+ indikator tujuan RPJMD)
     *  -> Sasaran IKU -> Indikator IKU -> OPD -> Program.
     *
     * Misi, tujuan, dan indikator tujuan tetap dari RPJMD — IKU tidak punya
     * tingkat tujuan. Sasaran & indikatornya dari IKU Kabupaten, dipasangkan
     * ke tujuan lewat jangkar yang sama dengan getMatrix() (lihat catatan di
     * sana). Sasaran yang belum berjangkar dikumpulkan dalam satu simpul
     * Misi semu di EKOR pohon: tampak, dan jelas kenapa ia di sana.
     *
     * Bentuk simpulnya tidak berubah dari sebelum 14 Sep 2026 — view pohon dan
     * cetaknya membaca kunci yang sama (`misi`, `tujuan_rpjmd`,
     * `indikator_tujuan`, `sasaran_rpjmd`, `csf`, `indikator_sasaran`, `opd`).
     */
    public function getPohonKinerja($tahunMulai, $tahunAkhir)
    {
        $tahunMulai = (int) $tahunMulai;
        $tahunAkhir = (int) $tahunAkhir;

        // 1. Misi & Tujuan RPJMD (+ indikator tujuan) — kerangka pohon.
        $misiList = $this->db->table('rpjmd_misi')
            ->where('tahun_mulai', $tahunMulai)
            ->where('tahun_akhir', $tahunAkhir)
            ->orderBy('id', 'ASC')
            ->get()
            ->getResultArray();

        if (empty($misiList)) {
            return [];
        }

        $misiIds    = array_column($misiList, 'id');
        $tujuanList = $this->db->table('rpjmd_tujuan')
            ->whereIn('misi_id', $misiIds)
            ->orderBy('id', 'ASC')
            ->get()
            ->getResultArray();

        $groupedIndikatorTujuan = [];

        if (! empty($tujuanList)) {
            foreach ($this->db->table('rpjmd_indikator_tujuan')
                ->whereIn('tujuan_id', array_column($tujuanList, 'id'))
                ->orderBy('id', 'ASC')->get()->getResultArray() as $indTuj) {
                $groupedIndikatorTujuan[$indTuj['tujuan_id']][] = $indTuj;
            }
        }

        // 2. Sasaran & indikator IKU Kabupaten, berjangkar.
        $sasaran   = $this->sasaranKabIku($tahunMulai, $tahunAkhir);
        $indikator = $this->indikatorKabIku(array_keys($sasaran));

        $csf = [];
        $idSasaranRpjmd = array_values(array_unique(array_filter(array_column($sasaran, 'rpjmd_sasaran_id'))));

        if ($idSasaranRpjmd !== []) {
            foreach ($this->db->table('rpjmd_sasaran')->select('id, csf')
                ->whereIn('id', $idSasaranRpjmd)->get()->getResultArray() as $r) {
                $csf[(int) $r['id']] = $r['csf'];
            }
        }

        // --- SUMBER OPD & PROGRAM (identik dengan getMatrix) ---
        $opdBySasaran      = $this->opdBySasaranMap();
        $manualByIndikator = $this->manualMappingMap($tahunMulai, $tahunAkhir);
        $programByOpd      = $this->programByOpdMap();

        $indPerSasaran = [];

        foreach ($indikator as $id => $i) {
            $indPerSasaran[(int) $i['iku_sasaran_id']][] = [
                'id'                 => $id,
                'sasaran_id'         => (int) $i['iku_sasaran_id'],
                'indikator_sasaran'  => $i['indikator'],
                'satuan'             => $i['satuan'],
                'baseline'           => $i['baseline'],
                'rpjmd_indikator_id' => ! empty($i['source_indikator_id']) ? (int) $i['source_indikator_id'] : null,
            ];
        }

        $groupedSasaran = [];  // tujuan_id => [simpul sasaran]
        $tanpaJangkar   = [];  // simpul sasaran yang belum berjangkar

        foreach ($sasaran as $sid => $s) {
            $indikatorSasaran = $indPerSasaran[$sid] ?? [];
            $indIds           = array_column($indikatorSasaran, 'id');

            // OPD per sasaran = gabungan per indikatornya, dengan aturan yang
            // sama seperti getMatrix(): indikator yang punya mapping manual
            // menyumbang mapping itu SAJA; indikator tanpa mapping menyumbang
            // penurunan otomatis (OPD Renstra + program PK JPT).
            $otomatis = $s['rpjmd_sasaran_id'] !== null ? ($opdBySasaran[$s['rpjmd_sasaran_id']] ?? []) : [];
            $opdSet   = [];
            $progManual = [];   // opd_id => [program manual...]
            $pakaiOtomatis = []; // opd_id => true bila ada indikator tanpa manual

            foreach ($indIds as $ind) {
                $manual = $manualByIndikator[$ind] ?? [];

                if ($manual === []) {
                    foreach ($otomatis as $opdId => $nama) {
                        $opdSet[$opdId]        = $nama;
                        $pakaiOtomatis[$opdId] = true;
                    }
                    continue;
                }

                foreach ($manual as $opdId => $info) {
                    $opdSet[$opdId] = $info['nama_opd'];
                    foreach ($info['programs'] as $pg) {
                        if (! in_array($pg, $progManual[$opdId] ?? [], true)) {
                            $progManual[$opdId][] = $pg;
                        }
                    }
                }
            }

            if ($indIds === []) {
                $opdSet = $otomatis;
                foreach ($otomatis as $opdId => $nama) {
                    $pakaiOtomatis[$opdId] = true;
                }
            }
            asort($opdSet);

            $opdNodes = [];

            foreach ($opdSet as $opdId => $namaOpd) {
                $programs = $progManual[$opdId] ?? [];

                if (isset($pakaiOtomatis[$opdId])) {
                    foreach ($programByOpd[$opdId] ?? [] as $pg) {
                        if (! in_array($pg, $programs, true)) {
                            $programs[] = $pg;
                        }
                    }
                }

                $opdNodes[] = ['nama_opd' => $namaOpd, 'programs' => $programs];
            }

            $simpul = [
                'id'                => $sid,
                'sasaran_rpjmd'     => $s['sasaran'],
                'csf'               => $s['rpjmd_sasaran_id'] !== null ? ($csf[$s['rpjmd_sasaran_id']] ?? '') : '',
                'jangkar'           => $s['jangkar'],
                'rpjmd_sasaran_id'  => $s['rpjmd_sasaran_id'],
                'indikator_sasaran' => $indikatorSasaran,
                'opd'               => $opdNodes,
            ];

            if ($s['tujuan_id'] !== null) {
                $groupedSasaran[$s['tujuan_id']][] = $simpul;
            } else {
                $tanpaJangkar[] = $simpul;
            }
        }

        // 3. Rakit pohon.
        $groupedTujuan = [];

        foreach ($tujuanList as $tujuan) {
            $groupedTujuan[$tujuan['misi_id']][] = [
                'id'               => $tujuan['id'],
                'tujuan_rpjmd'     => $tujuan['tujuan_rpjmd'],
                'indikator_tujuan' => $groupedIndikatorTujuan[$tujuan['id']] ?? [],
                'sasaran'          => $groupedSasaran[$tujuan['id']] ?? [],
            ];
        }

        $tree = [];

        foreach ($misiList as $misi) {
            $tree[] = [
                'id'     => $misi['id'],
                'misi'   => $misi['misi'],
                'tujuan' => $groupedTujuan[$misi['id']] ?? [],
            ];
        }

        if ($tanpaJangkar !== []) {
            // Misi semu: id 0 tidak pernah dipakai RPJMD. Teksnya menjelaskan
            // keadaannya sekaligus jalan keluarnya.
            $tree[] = [
                'id'     => 0,
                'misi'   => 'Sasaran IKU yang belum dijangkarkan ke RPJMD',
                'jangkar_kosong' => true,
                'tujuan' => [[
                    'id'               => 0,
                    'tujuan_rpjmd'     => 'Belum ada Tujuan RPJMD — jangkarkan lewat IKU Kabupaten › Revisi',
                    'indikator_tujuan' => [],
                    'sasaran'          => $tanpaJangkar,
                ]],
            ];
        }

        return $tree;
    }

    // =====================================================================
    // MODE 1 — KABUPATEN: backbone RPJMD sampai Indikator Sasaran (tanpa OPD)
    // Satu baris per indikator (atau per sasaran bila belum ada indikator).
    // =====================================================================
    public function getRpjmdMatrix($start, $end)
    {
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

        // Tandai indikator yang sudah punya mapping manual (untuk tombol Aksi)
        $manual = $this->manualMappingMap($start, $end);

        $rows = [];
        foreach ($backbone as $b) {
            $rows[] = $b + [
                'is_mapped' => !empty($manual[$b['indikator_id']]) ? 1 : 0,
            ];
        }

        // Target per tahun
        $indikatorIds = array_values(array_unique(array_filter(array_column($rows, 'indikator_id'))));
        $targetMap = [];
        if (!empty($indikatorIds)) {
            $targets = $this->db->table('rpjmd_target')
                ->select('indikator_sasaran_id, tahun, target_tahunan')
                ->whereIn('indikator_sasaran_id', $indikatorIds)
                ->get()
                ->getResultArray();
            foreach ($targets as $t) {
                $targetMap[$t['indikator_sasaran_id']][$t['tahun']] = $t['target_tahunan'];
            }
        }
        foreach ($rows as &$r) {
            $r['targets'] = $targetMap[$r['indikator_id']] ?? [];
        }
        unset($r);

        return $rows;
    }

    // =====================================================================
    // MODE 3 — KESELURUHAN: RPJMD (Tujuan→Sasaran) turun ke Renstra tiap OPD,
    // lalu diteruskan ke cascade internal OPD: Eselon III → Eselon IV/JF →
    // PELAKSANA (tabel rekursif cascading_sasaran_opd + cascading_indikator_opd).
    //
    // Relasi: renstra_tujuan.rpjmd_sasaran_id = rpjmd_sasaran.id
    // LEFT JOIN agar sasaran RPJMD tanpa renstra tetap tampil (kolom '-').
    //
    // Catatan penting: setiap join ke cascading_sasaran_opd DIKUNCI ke
    // rs.opd_id sehingga cascade satu OPD tidak pernah menempel pada indikator
    // Renstra OPD lain. Semua dilakukan dalam SATU query (bukan N+1).
    // =====================================================================
    public function getKeseluruhanMatrix($start, $end)
    {
        $start = (int) $start;
        $end   = (int) $end;

        $rows = $this->db->table('rpjmd_misi m')
            ->select("
                t.id as tujuan_id,
                t.tujuan_rpjmd,

                s.id as sasaran_id,
                s.sasaran_rpjmd,

                o.id as opd_id,
                o.nama_opd,

                rt.id as renstra_tujuan_id,
                rt.tujuan as renstra_tujuan,

                rs.id as renstra_sasaran_id,
                rs.sasaran as renstra_sasaran,

                ris.id as renstra_indikator_id,
                ris.indikator_sasaran as renstra_indikator,

                es3.id as es3_id,
                es3.nama_sasaran as es3_sasaran,
                i3.id as es3_indikator_id,
                i3.indikator as es3_indikator,

                es4.id as es4_id,
                es4.nama_sasaran as es4_sasaran,
                i4.id as es4_indikator_id,
                i4.indikator as es4_indikator,

                pel.id as pelaksana_id,
                pel.nama_sasaran as pelaksana_sasaran,
                ipel.id as pelaksana_indikator_id,
                ipel.indikator as pelaksana_indikator
            ", false)
            ->join('rpjmd_tujuan t', 't.misi_id = m.id', 'left')
            ->join('rpjmd_sasaran s', 's.tujuan_id = t.id', 'left')
            ->join('renstra_tujuan rt', 'rt.rpjmd_sasaran_id = s.id', 'left')
            ->join(
                'renstra_sasaran rs',
                "rs.renstra_tujuan_id = rt.id AND rs.tahun_mulai = {$start} AND rs.tahun_akhir = {$end}",
                'left',
                false
            )
            ->join('opd o', 'o.id = rs.opd_id', 'left')
            ->join('renstra_indikator_sasaran ris', 'ris.renstra_sasaran_id = rs.id', 'left')
            // ESELON III — milik OPD pemilik sasaran renstra tsb
            ->join(
                'cascading_sasaran_opd es3',
                "es3.renstra_indikator_sasaran_id = ris.id AND es3.level = 'es3' AND es3.opd_id = rs.opd_id",
                'left',
                false
            )
            ->join('cascading_indikator_opd i3', 'i3.cascading_sasaran_id = es3.id', 'left')
            // ESELON IV / JF
            ->join(
                'cascading_sasaran_opd es4',
                "es4.es3_indikator_id = i3.id AND es4.level = 'es4'",
                'left',
                false
            )
            ->join('cascading_indikator_opd i4', 'i4.cascading_sasaran_id = es4.id', 'left')
            // PELAKSANA — `es3_indikator_id` di sini berisi id indikator ES IV
            // (kolomnya bermakna "indikator induk", lihat migrasi 2026-07-27-000009)
            ->join(
                'cascading_sasaran_opd pel',
                "pel.es3_indikator_id = i4.id AND pel.level = 'pelaksana'",
                'left',
                false
            )
            ->join('cascading_indikator_opd ipel', 'ipel.cascading_sasaran_id = pel.id', 'left')
            ->where('m.tahun_mulai', $start)
            ->where('m.tahun_akhir', $end)
            ->orderBy('t.id', 'ASC')
            ->orderBy('s.id', 'ASC')
            ->orderBy('o.nama_opd', 'ASC')
            ->orderBy('rt.id', 'ASC')
            ->orderBy('rs.id', 'ASC')
            ->orderBy('ris.id', 'ASC')
            ->orderBy('es3.id', 'ASC')
            ->orderBy('i3.id', 'ASC')
            ->orderBy('es4.id', 'ASC')
            ->orderBy('i4.id', 'ASC')
            ->orderBy('pel.id', 'ASC')
            ->orderBy('ipel.id', 'ASC')
            ->get()
            ->getResultArray();

        return $rows;
    }

    /**
     * Renstra (per OPD) yang terhubung ke tiap sasaran RPJMD, untuk Pohon Keseluruhan.
     * @return array sasaran_id => [ opd_id => ['nama_opd', 'tujuan' => [ rt_id => ['nama','sasaran'=>[ rs_id => ['nama','indikators'=>[]] ]] ]] ]
     */
    private function renstraBySasaranMap($start, $end): array
    {
        $rows = $this->db->table('renstra_tujuan rt')
            ->select('
                rt.rpjmd_sasaran_id as sasaran_id,
                rt.id as rt_id,
                rt.tujuan as renstra_tujuan,
                rs.id as rs_id,
                rs.sasaran as renstra_sasaran,
                rs.opd_id,
                o.nama_opd,
                ris.id as ris_id,
                ris.indikator_sasaran as renstra_indikator
            ')
            ->join('renstra_sasaran rs', 'rs.renstra_tujuan_id = rt.id', 'inner')
            ->join('opd o', 'o.id = rs.opd_id', 'inner')
            ->join('renstra_indikator_sasaran ris', 'ris.renstra_sasaran_id = rs.id', 'left')
            ->where('rt.rpjmd_sasaran_id IS NOT NULL')
            ->where('rs.tahun_mulai', (int) $start)
            ->where('rs.tahun_akhir', (int) $end)
            ->orderBy('o.nama_opd', 'ASC')
            ->orderBy('rt.id', 'ASC')
            ->orderBy('rs.id', 'ASC')
            ->orderBy('ris.id', 'ASC')
            ->get()
            ->getResultArray();

        $map = [];
        foreach ($rows as $r) {
            $sid = $r['sasaran_id'];
            $opd = $r['opd_id'];
            if (!isset($map[$sid][$opd])) {
                $map[$sid][$opd] = ['nama_opd' => $r['nama_opd'], 'tujuan' => []];
            }
            $rt = $r['rt_id'];
            if (!isset($map[$sid][$opd]['tujuan'][$rt])) {
                $map[$sid][$opd]['tujuan'][$rt] = ['nama' => $r['renstra_tujuan'], 'sasaran' => []];
            }
            $rs = $r['rs_id'];
            if (!isset($map[$sid][$opd]['tujuan'][$rt]['sasaran'][$rs])) {
                $map[$sid][$opd]['tujuan'][$rt]['sasaran'][$rs] = ['nama' => $r['renstra_sasaran'], 'indikators' => []];
            }
            if (!empty($r['ris_id'])) {
                $map[$sid][$opd]['tujuan'][$rt]['sasaran'][$rs]['indikators'][$r['ris_id']] = $r['renstra_indikator'];
            }
        }
        return $map;
    }

    /**
     * Pohon Kinerja Keseluruhan: Visi → Misi → Tujuan RPJMD → Sasaran RPJMD
     *  → (per OPD) Tujuan Renstra → Sasaran Renstra → Indikator Renstra.
     */
    public function getKeseluruhanTree($tahunMulai, $tahunAkhir)
    {
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

        $tujuanList = $this->db->table('rpjmd_tujuan')
            ->whereIn('misi_id', $misiIds)
            ->orderBy('id', 'ASC')
            ->get()
            ->getResultArray();

        $tujuanIds = array_column($tujuanList, 'id');

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

        $indikatorSasaranList = [];
        if (!empty($sasaranIds)) {
            $indikatorSasaranList = $this->db->table('rpjmd_indikator_sasaran')
                ->whereIn('sasaran_id', $sasaranIds)
                ->orderBy('id', 'ASC')
                ->get()
                ->getResultArray();
        }

        $renstraBySasaran = $this->renstraBySasaranMap($tahunMulai, $tahunAkhir);

        $groupedIndikatorSasaran = [];
        foreach ($indikatorSasaranList as $is) {
            $groupedIndikatorSasaran[$is['sasaran_id']][] = $is;
        }

        $groupedSasaran = [];
        foreach ($sasaranList as $sasaran) {
            $sid = $sasaran['id'];

            // Ubah map renstra OPD menjadi list bersih untuk view
            $opdNodes = [];
            foreach ($renstraBySasaran[$sid] ?? [] as $opd) {
                $tujuan = [];
                foreach ($opd['tujuan'] as $t) {
                    $sasaranArr = [];
                    foreach ($t['sasaran'] as $s) {
                        $sasaranArr[] = [
                            'nama' => $s['nama'],
                            'indikators' => array_values($s['indikators']),
                        ];
                    }
                    $tujuan[] = ['nama' => $t['nama'], 'sasaran' => $sasaranArr];
                }
                $opdNodes[] = ['nama_opd' => $opd['nama_opd'], 'tujuan' => $tujuan];
            }

            $groupedSasaran[$sasaran['tujuan_id']][] = [
                'id' => $sid,
                'sasaran_rpjmd' => $sasaran['sasaran_rpjmd'],
                'indikator_sasaran' => $groupedIndikatorSasaran[$sid] ?? [],
                'opd' => $opdNodes,
            ];
        }

        $groupedIndikatorTujuan = [];
        foreach ($indikatorTujuanList as $it) {
            $groupedIndikatorTujuan[$it['tujuan_id']][] = $it;
        }

        $groupedTujuan = [];
        foreach ($tujuanList as $tujuan) {
            $groupedTujuan[$tujuan['misi_id']][] = [
                'id' => $tujuan['id'],
                'tujuan_rpjmd' => $tujuan['tujuan_rpjmd'],
                'indikator_tujuan' => $groupedIndikatorTujuan[$tujuan['id']] ?? [],
                'sasaran' => $groupedSasaran[$tujuan['id']] ?? [],
            ];
        }

        $tree = [];
        foreach ($misiList as $misi) {
            $tree[] = [
                'id' => $misi['id'],
                'misi' => $misi['misi'],
                'tujuan' => $groupedTujuan[$misi['id']] ?? [],
            ];
        }

        return $tree;
    }

    /**
     * Pohon Keseluruhan versi ringkas (tanpa Visi/Misi/Tujuan RPJMD/Sasaran RPJMD):
     * Perangkat Daerah → Tujuan Renstra → Sasaran Renstra (ES II) → Indikator,
     * DITERUSKAN ke cascade internal OPD: Eselon III → Eselon IV/JF → PELAKSANA.
     *
     * Satu query saja (semua jenjang di-LEFT JOIN) supaya tidak N+1.
     *
     * @return array list [
     *   ['nama_opd', 'tujuan' => [
     *      ['nama', 'sasaran' => [
     *         ['nama', 'indikators' => [...], 'es3s' => [
     *            ['nama', 'indikators' => [...], 'es4s' => [
     *               ['nama', 'indikators' => [...], 'pelaksanas' => [
     *                  ['nama', 'indikators' => [...]]
     *               ]]
     *            ]]
     *         ]]
     *      ]]
     *   ]]
     * ]
     */
    public function getKeseluruhanByOpd($start, $end): array
    {
        $rows = $this->db->table('renstra_tujuan rt')
            ->select('
                rs.opd_id,
                o.nama_opd,
                rt.id as rt_id,
                rt.tujuan as renstra_tujuan,
                rs.id as rs_id,
                rs.sasaran as renstra_sasaran,
                ris.id as ris_id,
                ris.indikator_sasaran as renstra_indikator,
                es3.id as es3_id,
                es3.nama_sasaran as es3_sasaran,
                i3.id as i3_id,
                i3.indikator as es3_indikator,
                es4.id as es4_id,
                es4.nama_sasaran as es4_sasaran,
                i4.id as i4_id,
                i4.indikator as es4_indikator,
                pel.id as pel_id,
                pel.nama_sasaran as pelaksana_sasaran,
                ipel.id as ipel_id,
                ipel.indikator as pelaksana_indikator
            ')
            ->join('renstra_sasaran rs', 'rs.renstra_tujuan_id = rt.id', 'inner')
            ->join('opd o', 'o.id = rs.opd_id', 'inner')
            ->join('renstra_indikator_sasaran ris', 'ris.renstra_sasaran_id = rs.id', 'left')
            ->join(
                'cascading_sasaran_opd es3',
                "es3.renstra_indikator_sasaran_id = ris.id AND es3.level = 'es3' AND es3.opd_id = rs.opd_id",
                'left',
                false
            )
            ->join('cascading_indikator_opd i3', 'i3.cascading_sasaran_id = es3.id', 'left')
            ->join(
                'cascading_sasaran_opd es4',
                "es4.es3_indikator_id = i3.id AND es4.level = 'es4'",
                'left',
                false
            )
            ->join('cascading_indikator_opd i4', 'i4.cascading_sasaran_id = es4.id', 'left')
            ->join(
                'cascading_sasaran_opd pel',
                "pel.es3_indikator_id = i4.id AND pel.level = 'pelaksana'",
                'left',
                false
            )
            ->join('cascading_indikator_opd ipel', 'ipel.cascading_sasaran_id = pel.id', 'left')
            ->where('rt.rpjmd_sasaran_id IS NOT NULL')
            ->where('rs.tahun_mulai', (int) $start)
            ->where('rs.tahun_akhir', (int) $end)
            ->orderBy('o.nama_opd', 'ASC')
            ->orderBy('rt.id', 'ASC')
            ->orderBy('rs.id', 'ASC')
            ->orderBy('ris.id', 'ASC')
            ->orderBy('es3.id', 'ASC')
            ->orderBy('i3.id', 'ASC')
            ->orderBy('es4.id', 'ASC')
            ->orderBy('i4.id', 'ASC')
            ->orderBy('pel.id', 'ASC')
            ->orderBy('ipel.id', 'ASC')
            ->get()
            ->getResultArray();

        $map = [];
        foreach ($rows as $r) {
            $opd = $r['opd_id'];
            if (!isset($map[$opd])) {
                $map[$opd] = ['nama_opd' => $r['nama_opd'], 'tujuan' => []];
            }
            $rt = $r['rt_id'];
            if (!isset($map[$opd]['tujuan'][$rt])) {
                $map[$opd]['tujuan'][$rt] = ['nama' => $r['renstra_tujuan'], 'sasaran' => []];
            }
            $rs = $r['rs_id'];
            if (!isset($map[$opd]['tujuan'][$rt]['sasaran'][$rs])) {
                $map[$opd]['tujuan'][$rt]['sasaran'][$rs] = [
                    'nama'       => $r['renstra_sasaran'],
                    'indikators' => [],
                    'es3s'       => [],
                ];
            }
            $sasNode = &$map[$opd]['tujuan'][$rt]['sasaran'][$rs];

            if (!empty($r['ris_id'])) {
                $sasNode['indikators'][$r['ris_id']] = $r['renstra_indikator'];
            }

            if (empty($r['es3_id'])) {
                unset($sasNode);
                continue;
            }
            if (!isset($sasNode['es3s'][$r['es3_id']])) {
                $sasNode['es3s'][$r['es3_id']] = ['nama' => $r['es3_sasaran'], 'indikators' => [], 'es4s' => []];
            }
            $es3Node = &$sasNode['es3s'][$r['es3_id']];
            if (!empty($r['i3_id'])) {
                $es3Node['indikators'][$r['i3_id']] = $r['es3_indikator'];
            }

            if (empty($r['es4_id'])) {
                unset($es3Node, $sasNode);
                continue;
            }
            if (!isset($es3Node['es4s'][$r['es4_id']])) {
                $es3Node['es4s'][$r['es4_id']] = ['nama' => $r['es4_sasaran'], 'indikators' => [], 'pelaksanas' => []];
            }
            $es4Node = &$es3Node['es4s'][$r['es4_id']];
            if (!empty($r['i4_id'])) {
                $es4Node['indikators'][$r['i4_id']] = $r['es4_indikator'];
            }

            if (!empty($r['pel_id'])) {
                if (!isset($es4Node['pelaksanas'][$r['pel_id']])) {
                    $es4Node['pelaksanas'][$r['pel_id']] = ['nama' => $r['pelaksana_sasaran'], 'indikators' => []];
                }
                if (!empty($r['ipel_id'])) {
                    $es4Node['pelaksanas'][$r['pel_id']]['indikators'][$r['ipel_id']] = $r['pelaksana_indikator'];
                }
            }

            unset($es4Node, $es3Node, $sasNode);
        }

        // Bersihkan jadi list (buang key id)
        $tree = [];
        foreach ($map as $opd) {
            $tujuanList = [];
            foreach ($opd['tujuan'] as $t) {
                $sasaranList = [];
                foreach ($t['sasaran'] as $s) {
                    $es3List = [];
                    foreach ($s['es3s'] as $e3) {
                        $es4List = [];
                        foreach ($e3['es4s'] as $e4) {
                            $pelList = [];
                            foreach ($e4['pelaksanas'] as $p) {
                                $pelList[] = ['nama' => $p['nama'], 'indikators' => array_values($p['indikators'])];
                            }
                            $es4List[] = [
                                'nama'       => $e4['nama'],
                                'indikators' => array_values($e4['indikators']),
                                'pelaksanas' => $pelList,
                            ];
                        }
                        $es3List[] = [
                            'nama'       => $e3['nama'],
                            'indikators' => array_values($e3['indikators']),
                            'es4s'       => $es4List,
                        ];
                    }
                    $sasaranList[] = [
                        'nama'       => $s['nama'],
                        'indikators' => array_values($s['indikators']),
                        'es3s'       => $es3List,
                    ];
                }
                $tujuanList[] = ['nama' => $t['nama'], 'sasaran' => $sasaranList];
            }
            $tree[] = ['nama_opd' => $opd['nama_opd'], 'tujuan' => $tujuanList];
        }

        return $tree;
    }

    /**
     * Program & kegiatan PK yang menempel pada tiap node Eselon III (kabid)
     * pada Pohon Kinerja / Cascading OPD.
     *
     * Tidak ada kunci struktural apa pun dari cascading_sasaran_opd ke
     * pk_indikator, jadi jembatannya PENCOCOKAN TEKS ternormalisasi - pola yang
     * sudah dipakai PkRenaksiController::autoOpdsForSasaran. Urutan prioritas:
     * teks INDIKATOR dulu (lebih presisi), baru teks SASARAN sebagai cadangan,
     * dan hanya untuk node yang belum dapat lewat indikator. Tanpa pemilihan
     * prioritas itu, node yang sudah presisi ikut menyerap kegiatan milik
     * saudara sekandung yang kebetulan seteks sasarannya.
     *
     * Program DITURUNKAN DARI kegiatan (kegiatan_pk.program_id), BUKAN dari
     * pk_program.program_id. Sebabnya nyata: 452 dari 2051 tautan (22%) punya
     * program induk berbeda antara kedua jalur itu, sehingga menyandingkan
     * keduanya akan memperlihatkan kegiatan yang menempel pada program salah.
     *
     * Jenis PK jenjang es3 tidak selalu 'administrator': pada OPD kecamatan,
     * teks es3 cascading justru sepadan dengan pk.jenis 'pengawas' (konsisten
     * dengan relabel eselon kecamatan). Dideteksi dari DATA - ada tidaknya PK
     * berjenis 'camat' di OPD itu - bukan dari nama OPD. Tanpa aturan ini
     * seluruh kecamatan tampil kosong (cakupan 50% vs 59%).
     *
     * Node yang teksnya tidak cocok sengaja dibiarkan KOSONG, bukan diisi
     * seluruh program OPD, supaya program tidak menempel pada kabid yang salah.
     *
     * @return array<int, array<int, array<string, mixed>>> [es3_id => daftar program]
     */
    public function programPkByEs3(int $opdId, $periodeStart = null, $periodeEnd = null): array
    {
        if ($opdId <= 0) {
            return [];
        }

        $db = $this->db;

        // Jenjang es3 kecamatan memakai PK 'pengawas'; OPD biasa 'administrator'.
        $adaCamat = (int) $db->table('pk')
            ->where('opd_id', $opdId)->where('jenis', 'camat')
            ->countAllResults();
        $jenisEs3 = $adaCamat > 0 ? 'pengawas' : 'administrator';

        // Tahun PK: terbaru di dalam periode; kalau kosong, terbaru apa pun.
        $bt = $db->table('pk')->selectMax('tahun', 'tahun')
            ->where('opd_id', $opdId)->where('jenis', $jenisEs3);
        if ($periodeStart !== null && $periodeEnd !== null) {
            $bt->where('tahun >=', (int) $periodeStart)->where('tahun <=', (int) $periodeEnd);
        }
        $tahun = $bt->get()->getRowArray()['tahun'] ?? null;

        if ($tahun === null && $periodeStart !== null) {
            $tahun = $db->table('pk')->selectMax('tahun', 'tahun')
                ->where('opd_id', $opdId)->where('jenis', $jenisEs3)
                ->get()->getRowArray()['tahun'] ?? null;
        }
        if ($tahun === null) {
            return [];
        }

        $norm = static function (string $kol): string {
            return "LOWER(TRIM(REGEXP_REPLACE({$kol}, '[[:space:]]+', ' ')))";
        };

        // Sisi PK: indikator + teks sasaran & indikator yang sudah dinormalkan.
        $pkRows = $db->table('pk')
            ->select('pi.id AS pk_indikator_id')
            ->select($norm('ps.sasaran') . ' AS k_sas', false)
            ->select($norm('pi.indikator') . ' AS k_ind', false)
            ->join('pk_sasaran ps', 'ps.pk_id = pk.id AND ps.jenis = pk.jenis', 'inner')
            ->join('pk_indikator pi', 'pi.pk_sasaran_id = ps.id', 'inner')
            ->where('pk.opd_id', $opdId)
            ->where('pk.jenis', $jenisEs3)
            ->where('pk.tahun', $tahun)
            ->get()->getResultArray();

        if (empty($pkRows)) {
            return [];
        }

        // Sisi cascading: node es3 + teks sasaran & indikatornya.
        $nodeRows = $db->table('cascading_sasaran_opd cs')
            ->select('cs.id AS node_id')
            ->select($norm('cs.nama_sasaran') . ' AS k_sas', false)
            ->select($norm('ci.indikator') . ' AS k_ind', false)
            ->join('cascading_indikator_opd ci', 'ci.cascading_sasaran_id = cs.id', 'left')
            ->where('cs.opd_id', $opdId)
            ->where('cs.level', 'es3')
            ->get()->getResultArray();

        if (empty($nodeRows)) {
            return [];
        }

        $pkByInd = [];
        $pkBySas = [];
        foreach ($pkRows as $r) {
            $id = (int) $r['pk_indikator_id'];
            if (($r['k_ind'] ?? '') !== '') {
                $pkByInd[$r['k_ind']][$id] = $id;
            }
            if (($r['k_sas'] ?? '') !== '') {
                $pkBySas[$r['k_sas']][$id] = $id;
            }
        }

        $cocokInd = [];
        $cocokSas = [];
        foreach ($nodeRows as $r) {
            $node = (int) $r['node_id'];
            if (($r['k_ind'] ?? '') !== '' && isset($pkByInd[$r['k_ind']])) {
                foreach ($pkByInd[$r['k_ind']] as $id) {
                    $cocokInd[$node][$id] = $id;
                }
            }
            if (($r['k_sas'] ?? '') !== '' && isset($pkBySas[$r['k_sas']])) {
                foreach ($pkBySas[$r['k_sas']] as $id) {
                    $cocokSas[$node][$id] = $id;
                }
            }
        }

        $indikatorPerNode = [];
        $semuaNode = array_unique(array_merge(array_keys($cocokInd), array_keys($cocokSas)));
        foreach ($semuaNode as $node) {
            // Cocok indikator menang; sasaran hanya cadangan bila indikator nihil.
            $indikatorPerNode[$node] = !empty($cocokInd[$node])
                ? array_values($cocokInd[$node])
                : array_values($cocokSas[$node] ?? []);
        }
        if (empty($indikatorPerNode)) {
            return [];
        }

        $semuaIndikator = array_values(array_unique(array_merge(...array_values($indikatorPerNode))));

        // Satu query batch: indikator -> kegiatan -> program INDUK kegiatan itu.
        $unitRows = $db->table('pk_program pp')
            ->select('pp.pk_indikator_id,
                      pr.id AS program_id, pr.kode_program, pr.program_kegiatan,
                      kg.id AS kegiatan_id, kg.kode_kegiatan, kg.kegiatan')
            ->join('pk_kegiatan pkg', 'pkg.pk_program_id = pp.id', 'inner')
            ->join('kegiatan_pk kg', 'kg.id = pkg.kegiatan_id', 'inner')
            ->join('program_pk pr', 'pr.id = kg.program_id', 'inner')
            ->whereIn('pp.pk_indikator_id', $semuaIndikator)
            ->orderBy('pr.kode_program', 'ASC')
            ->orderBy('pr.id', 'ASC')
            ->orderBy('kg.kode_kegiatan', 'ASC')
            ->get()->getResultArray();

        $unitByIndikator = [];
        foreach ($unitRows as $r) {
            $unitByIndikator[(int) $r['pk_indikator_id']][] = $r;
        }

        $hasil = [];
        foreach ($indikatorPerNode as $node => $ids) {
            $programs = [];
            foreach ($ids as $id) {
                foreach ($unitByIndikator[$id] ?? [] as $r) {
                    $pid = (int) $r['program_id'];
                    if (!isset($programs[$pid])) {
                        $programs[$pid] = [
                            'program_id' => $pid,
                            'kode'       => ($r['kode_program'] ?? '') !== '' ? $r['kode_program'] : null,
                            'nama'       => (string) $r['program_kegiatan'],
                            'kegiatan'   => [],
                        ];
                    }
                    $kid = (int) $r['kegiatan_id'];
                    $programs[$pid]['kegiatan'][$kid] = [
                        'kegiatan_id' => $kid,
                        'kode'        => ($r['kode_kegiatan'] ?? '') !== '' ? $r['kode_kegiatan'] : null,
                        'nama'        => (string) $r['kegiatan'],
                    ];
                }
            }
            if (empty($programs)) {
                continue; // cocok teks tapi rantainya putus -> tetap kosong, jujur
            }
            foreach ($programs as &$p) {
                $p['kegiatan'] = array_values($p['kegiatan']);
            }
            unset($p);
            $hasil[(int) $node] = array_values($programs);
        }

        return $hasil;
    }
}
