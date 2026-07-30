<?php

namespace App\Services;

use App\Models\OpdModel;
use Config\Database;

/**
 * Sumber data tunggal Dashboard Pengendalian Kinerja Perangkat Daerah.
 *
 * Dipakai oleh AdminOpdController (halaman + endpoint JSON). Seluruh query
 * diambil per-batch (tidak ada N+1): daftar indikator, rencana aksi, sub
 * rencana aksi, MONEV, realisasi anggaran, program, skala satuan, dan misi
 * masing-masing satu query, lalu dirangkai di PHP.
 *
 * RANTAI DATA (semuanya relasi yang memang ada di database):
 *   pk (opd_id, tahun, jenis) -> pk_sasaran -> pk_indikator
 *     -> target_rencana (pk_indikator_id, opd_id)         = Rencana Aksi
 *        -> target_sub_rencana                            = Sub Rencana Aksi
 *        -> monev (target_rencana_id, target_sub_rencana_id, opd_id)
 *        -> monev_anggaran (target_rencana_id)             = realisasi anggaran
 *     -> pk_program -> program_pk                          = program & pagu
 *   pk -> pk_misi -> rpjmd_misi                            = dukungan Misi Bupati
 *
 * PEMBATASAN AKSES: opd_id SELALU berasal dari sesi untuk role tingkat OPD.
 * Hanya role 'admin' (super admin, tidak terikat satu OPD) yang boleh memilih
 * OPD lewat parameter, dan pilihannya divalidasi ke daftar OPD yang sah.
 */
class OpdDashboardService
{
    /** Jenis PK yang dianggap "indikator utama OPD" per role (urutan = prioritas). */
    private const PREFERENSI_JENIS = [
        'admin_kecamatan' => ['camat', 'jpt'],
        'default'         => ['jpt', 'camat'],
    ];

    /** Jenis PK pendukung — hanya untuk drill-down, tidak masuk capaian total OPD. */
    private const JENIS_PENDUKUNG = ['administrator', 'pengawas'];

    private $db;

    /** @var array<int, array<int, array<string, mixed>>> cache skala satuan per satuan_id */
    private array $skalaCache = [];

    /**
     * Area rute tujuan tombol tindak lanjut:
     *   'adminopd' — pemilik data (admin_opd / admin_kecamatan)
     *   'adminkab' — pemantauan lintas OPD (admin_kab / inspektorat / super admin)
     *   'bupati'   — pemantauan read-only role Bupati
     */
    private string $linkArea = 'adminopd';

    /** Area yang memantau lintas OPD (bukan pemilik data). */
    private const AREA_LINTAS_OPD = ['adminkab', 'bupati'];

    public function __construct()
    {
        $this->db = Database::connect();
        helper(['capaian', 'dashboard_status', 'format']);
    }

    /** Pilih area rute tujuan; dipanggil dashboard kabupaten/bupati saat Mode Fokus OPD. */
    public function setLinkArea(string $area): void
    {
        $this->linkArea = in_array($area, self::AREA_LINTAS_OPD, true) ? $area : 'adminopd';
    }

    /**
     * Tautan modul tujuan sesuai area, dengan tahun (dan OPD bila lintas OPD)
     * dipertahankan.
     *
     * Catatan penting untuk area lintas OPD (`adminkab` / `bupati`): halaman
     * dokumen PK (AdminOpd\PkController) menentukan OPD dari SESI, bukan
     * parameter, sehingga tidak bisa dipakai membuka PK milik OPD lain.
     * Karena itu di area kabupaten tautan `pk` diarahkan ke daftar PK
     * OPD/Kecamatan (renaksi_pk) yang memang bisa difilter per OPD; untuk
     * area `bupati` disediakan halaman monitoring PK read-only tersendiri.
     *
     * @return array{pk: string|null, renaksi: string, monev: string, lakip: string}
     */
    public function moduleLinks(int $tahun, string $jenis, ?int $opdId = null): array
    {
        if (in_array($this->linkArea, self::AREA_LINTAS_OPD, true)) {
            $area = $this->linkArea;
            $qs   = '?tahun=' . $tahun . ($opdId ? '&opd_id=' . $opdId : '');

            return [
                // Area bupati punya halaman "Perjanjian Kinerja" read-only
                // lintas OPD; area adminkab tetap null (pola existing).
                'pk'      => $area === 'bupati' ? base_url('bupati/pk/es3' . $qs) : null,
                'renaksi' => base_url($area . '/renaksi_pk/es3' . $qs),
                'monev'   => base_url($area . '/monev_pk/es3' . $qs),
                'lakip'   => base_url($area . '/lakip?mode=opd&tahun=' . $tahun . ($opdId ? '&opd_id=' . $opdId : '')),
            ];
        }

        $qs = '?tahun=' . $tahun;

        return [
            'pk'      => base_url('adminopd/pk/' . $this->pkSegment($jenis) . $qs),
            'renaksi' => base_url('adminopd/target_renaksi' . $qs),
            'monev'   => base_url('adminopd/monev' . $qs),
            'lakip'   => base_url('adminopd/lakip' . $qs),
        ];
    }

    /* =====================================================================
     * KONTEKS & FILTER
     * ===================================================================*/

    /**
     * Tentukan OPD yang boleh dilihat pengguna ini.
     *
     * @return array{role: string, opd_id: int|null, opd_nama: string|null,
     *               can_pick: bool, opd_list: array<int, array<string, mixed>>}
     */
    public function resolveScope(?int $opdDiminta = null): array
    {
        $session = session();
        $role    = (string) $session->get('role');

        // Role tingkat OPD: opd_id HANYA dari sesi, parameter diabaikan total.
        if (in_array($role, ['admin_opd', 'admin_kecamatan'], true)) {
            $opdId = (int) ($session->get('opd_id') ?? 0) ?: null;

            return [
                'role'     => $role,
                'opd_id'   => $opdId,
                'opd_nama' => $opdId ? $this->namaOpd($opdId) : null,
                'can_pick' => false,
                'opd_list' => [],
            ];
        }

        // Super admin membuka area OPD: boleh memilih, tapi hanya dari daftar sah.
        if ($role === 'admin') {
            $list  = $this->opdOptions();
            $sahId = array_map('intval', array_column($list, 'id'));
            $opdId = ($opdDiminta !== null && in_array($opdDiminta, $sahId, true)) ? $opdDiminta : null;

            return [
                'role'     => $role,
                'opd_id'   => $opdId,
                'opd_nama' => $opdId ? $this->namaOpd($opdId) : null,
                'can_pick' => true,
                'opd_list' => $list,
            ];
        }

        return ['role' => $role, 'opd_id' => null, 'opd_nama' => null, 'can_pick' => false, 'opd_list' => []];
    }

    /** @return array<int, array<string, mixed>> */
    public function opdOptions(): array
    {
        return $this->db->table('opd')
            ->select('id, nama_opd')
            ->whereNotIn('id', OpdModel::EXCLUDED_OPD_IDS)
            ->orderBy('nama_opd', 'ASC')
            ->get()->getResultArray();
    }

    private function namaOpd(int $opdId): ?string
    {
        $row = $this->db->table('opd')->select('nama_opd')->where('id', $opdId)->get()->getRowArray();

        return $row['nama_opd'] ?? null;
    }

    /**
     * Tahun PK yang tersedia untuk OPD ini (menurun). Tahun berjalan selalu ikut
     * agar OPD yang belum punya PK tetap bisa membuka tahun aktif.
     *
     * @return int[]
     */
    public function getAvailableYears(?int $opdId): array
    {
        $tahun = [];
        if ($opdId) {
            $rows = $this->db->table('pk')
                ->select('tahun')
                ->where('opd_id', $opdId)
                ->distinct()
                ->orderBy('tahun', 'DESC')
                ->get()->getResultArray();
            $tahun = array_map('intval', array_column($rows, 'tahun'));
        }

        $tahun[] = (int) date('Y');
        $tahun = array_values(array_unique(array_filter($tahun)));
        rsort($tahun);

        return $tahun;
    }

    /**
     * Jenis PK pimpinan OPD: PK JPT (Eselon II) untuk OPD, PK Camat untuk
     * kecamatan. Ditentukan dari role + data yang benar-benar ada, bukan
     * di-hardcode: super admin yang membuka OPD kecamatan tetap dapat PK Camat.
     */
    public function primaryJenis(?int $opdId, int $tahun, string $role): string
    {
        $preferensi = self::PREFERENSI_JENIS[$role] ?? self::PREFERENSI_JENIS['default'];
        if (!$opdId) {
            return $preferensi[0];
        }

        foreach ($preferensi as $jenis) {
            $ada = $this->db->table('pk')
                ->where('opd_id', $opdId)
                ->where('tahun', $tahun)
                ->where('jenis', $jenis)
                ->countAllResults();
            if ($ada > 0) {
                return $jenis;
            }
        }

        return $preferensi[0];
    }

    /** Label eselon manusiawi, mengikuti penamaan modul PK/MONEV yang sudah ada. */
    public function jenisLabel(string $jenis): string
    {
        return [
            'bupati'        => 'Bupati',
            'jpt'           => 'Eselon II (JPT)',
            'camat'         => 'Eselon III (Camat)',
            'administrator' => 'Eselon III (Administrator)',
            'pengawas'      => 'Eselon IV (Pengawas)',
        ][$jenis] ?? $jenis;
    }

    /** Segmen URL modul PK sesuai jenis (lihat AdminOpd\PkController::index). */
    public function pkSegment(string $jenis): string
    {
        return $jenis === 'camat' ? 'kecamatan' : $jenis;
    }

    /**
     * Daftar pejabat penanda tangan PK (pk.pihak_1) untuk filter unit.
     *
     * @return array<int, array<string, mixed>>
     */
    public function pejabatOptions(?int $opdId, int $tahun, string $jenis): array
    {
        if (!$opdId) {
            return [];
        }

        return $this->db->table('pk')
            ->select('pk.pihak_1 AS id, peg.nama_pegawai AS nama, jab.nama_jabatan AS jabatan')
            ->join('pegawai peg', 'peg.id = pk.pihak_1', 'left')
            ->join('jabatan jab', 'jab.id = peg.jabatan_id', 'left')
            ->where('pk.opd_id', $opdId)
            ->where('pk.tahun', $tahun)
            ->where('pk.jenis', $jenis)
            ->where('pk.pihak_1 IS NOT NULL', null, false)
            ->groupBy('pk.pihak_1, peg.nama_pegawai, jab.nama_jabatan')
            ->orderBy('jab.nama_jabatan', 'ASC')
            ->get()->getResultArray();
    }

    /* =====================================================================
     * RINGKASAN UTAMA
     * ===================================================================*/

    /**
     * Seluruh bahan dashboard dalam satu panggilan.
     *
     * @return array<string, mixed>
     */
    public function getSummary(?int $opdId, int $tahun, ?int $triwulan = null, array $opsi = []): array
    {
        $role      = (string) (session()->get('role') ?? '');
        $triwulan  = $this->normalisasiTriwulan($triwulan, $tahun);
        $jenis     = $opsi['jenis'] ?? $this->primaryJenis($opdId, $tahun, $role);
        $pejabatId = isset($opsi['pejabat_id']) ? ((int) $opsi['pejabat_id'] ?: null) : null;

        $indikator = $opdId ? $this->loadIndicators($opdId, $tahun, $jenis, $triwulan, $pejabatId) : [];

        $pk        = $this->getPkSummary($indikator, $opdId, $tahun, $jenis);
        $capaian   = $this->getOpdAchievement($indikator);
        $anggaran  = $this->getBudgetAbsorption($indikator, $triwulan);
        $distribusi = $this->getStatusDistribution($indikator);
        $misi      = $this->getMissionContributions($indikator, $opdId, $tahun);
        $insight   = $this->getPriorityInsights($indikator, $anggaran, $tahun, $triwulan, $jenis, $opdId);

        return [
            'context' => [
                'opd_id'        => $opdId,
                'opd_nama'      => $opdId ? $this->namaOpd($opdId) : null,
                'role'          => $role,
                'tahun'         => $tahun,
                'triwulan'      => $triwulan,
                'jenis'         => $jenis,
                'jenis_label'   => $this->jenisLabel($jenis),
                'pk_segment'    => $this->pkSegment($jenis),
                'pejabat_id'    => $pejabatId,
                'last_update'   => $this->getLastUpdate($opdId, $tahun),
                'verifikasi'    => $this->verificationInfo(),
            ],
            'pk'                  => $pk,
            'capaian'             => $capaian,
            'anggaran'            => $anggaran,
            'perhatian'           => $this->ringkasPerhatian($indikator, $insight, $anggaran),
            'status_distribution' => $distribusi,
            'insights'            => $insight,
            'misi'                => $misi,
            'indicators'          => array_map([$this, 'ringkasIndikator'], $indikator),
            'chart_series'        => $this->getQuarterlyOptions($indikator),
        ];
    }

    private function normalisasiTriwulan(?int $triwulan, int $tahun): int
    {
        if ($triwulan !== null && $triwulan >= 1 && $triwulan <= 4) {
            return $triwulan;
        }

        return dash_triwulan_berjalan($tahun);
    }

    /* =====================================================================
     * PEMUATAN INDIKATOR (semua query batch di sini)
     * ===================================================================*/

    /**
     * Indikator PK pimpinan OPD beserta rencana aksi, capaian, dan anggarannya.
     *
     * @return array<int, array<string, mixed>>
     */
    public function loadIndicators(
        int $opdId,
        int $tahun,
        string $jenis,
        int $triwulan,
        ?int $pejabatId = null,
        ?int $onlyIndikatorId = null
    ): array {
        $rows = $this->queryIndikator(
            [$opdId],
            $tahun,
            [$jenis],
            $pejabatId,
            $onlyIndikatorId
        );

        return $this->assembleIndicators($rows, $triwulan);
    }

    /**
     * Indikator PK pimpinan BANYAK OPD sekaligus (dipakai dashboard kabupaten).
     *
     * Jenis PK pimpinan ditentukan per OPD dari data yang ada: 'jpt' bila
     * dokumennya ada, kalau tidak 'camat' (kecamatan). Seluruhnya diambil
     * dalam satu rangkaian query batch — tidak ada query per OPD.
     *
     * @param int[] $opdIds
     *
     * @return array<int, array<int, array<string, mixed>>> [opd_id => indikator[]]
     */
    public function loadIndicatorsForOpds(array $opdIds, int $tahun, int $triwulan): array
    {
        $opdIds = $this->bersihkanIds($opdIds);
        if ($opdIds === []) {
            return [];
        }

        $rows = $this->queryIndikator($opdIds, $tahun, ['jpt', 'camat']);

        // Per OPD hanya satu jenis pimpinan yang dipakai supaya capaian OPD
        // tidak mencampur dua dokumen PK puncak.
        $punyaJpt = [];
        foreach ($rows as $r) {
            if ($r['pk_jenis'] === 'jpt') {
                $punyaJpt[(int) $r['opd_id']] = true;
            }
        }
        $rows = array_values(array_filter($rows, static function ($r) use ($punyaJpt) {
            $opd = (int) $r['opd_id'];
            return isset($punyaJpt[$opd]) ? $r['pk_jenis'] === 'jpt' : true;
        }));

        $hasil = [];
        foreach ($this->assembleIndicators($rows, $triwulan) as $ind) {
            $hasil[(int) $ind['opd_id']][] = $ind;
        }

        return $hasil;
    }

    /**
     * Indikator PK Bupati (pk.jenis = 'bupati').
     *
     * Rantai datanya identik dengan PK OPD; bedanya hanya lingkup pemilik:
     * rencana aksi & realisasinya melekat pada OPD milik dokumen PK Bupati.
     *
     * @return array<int, array<string, mixed>>
     */
    public function loadBupatiIndicators(int $tahun, int $triwulan, ?int $onlyIndikatorId = null): array
    {
        $rows = $this->queryIndikator([], $tahun, ['bupati'], null, $onlyIndikatorId);

        return $this->assembleIndicators($rows, $triwulan);
    }

    /**
     * Query dasar daftar indikator PK.
     *
     * @param int[]    $opdIds daftar OPD (kosong = tanpa batas OPD, dipakai PK Bupati)
     * @param string[] $jenis
     *
     * @return array<int, array<string, mixed>>
     */
    private function queryIndikator(
        array $opdIds,
        int $tahun,
        array $jenis,
        ?int $pejabatId = null,
        ?int $onlyIndikatorId = null
    ): array {
        $adaTipeSatuan = $this->db->fieldExists('tipe', 'satuan');

        $b = $this->db->table('pk_indikator pi')
            ->select('
                pi.id            AS indikator_id,
                pi.indikator,
                pi.jenis_indikator,
                pi.target        AS target_tahunan,
                pi.id_satuan     AS satuan_id,
                s.satuan,
                ps.id            AS sasaran_id,
                ps.sasaran,
                pk.id            AS pk_id,
                pk.opd_id        AS opd_id,
                pk.jenis         AS pk_jenis,
                pk.tahun         AS pk_tahun,
                pk.tanggal       AS pk_tanggal,
                pk.pihak_1       AS pejabat_id,
                pj.nama_pegawai  AS pejabat_nama,
                jb.nama_jabatan  AS pejabat_jabatan,
                o.nama_opd
            ')
            ->join('pk_sasaran ps', 'ps.id = pi.pk_sasaran_id', 'inner')
            ->join('pk', 'pk.id = ps.pk_id', 'inner')
            ->join('opd o', 'o.id = pk.opd_id', 'left')
            ->join('satuan s', 's.id = pi.id_satuan', 'left')
            ->join('pegawai pj', 'pj.id = pk.pihak_1', 'left')
            ->join('jabatan jb', 'jb.id = pj.jabatan_id', 'left')
            ->where('pk.tahun', $tahun)
            ->whereIn('pk.jenis', $jenis);

        if ($opdIds !== []) {
            $b->whereIn('pk.opd_id', $opdIds);
        }
        if ($adaTipeSatuan) {
            $b->select('s.tipe AS satuan_tipe');
        }
        if ($pejabatId) {
            $b->where('pk.pihak_1', $pejabatId);
        }
        if ($onlyIndikatorId) {
            $b->where('pi.id', $onlyIndikatorId);
        }

        return $b->orderBy('pk.opd_id', 'ASC')
            ->orderBy('ps.id', 'ASC')
            ->orderBy('pi.id', 'ASC')
            ->get()->getResultArray();
    }

    /**
     * Rangkai baris indikator mentah menjadi indikator lengkap.
     *
     * Seluruh data pendukung (rencana aksi, sub, MONEV, realisasi anggaran,
     * program, skala satuan, misi) diambil per-batch untuk SELURUH baris —
     * berapa pun jumlah OPD-nya — lalu dirangkai di PHP. Tidak ada query
     * di dalam perulangan.
     *
     * Kepemilikan rencana aksi memakai aturan yang sama di semua modul:
     * `target_rencana.pk_indikator_id = pk_indikator.id AND target_rencana.opd_id = pk.opd_id`.
     *
     * @param array<int, array<string, mixed>> $rows
     *
     * @return array<int, array<string, mixed>>
     */
    private function assembleIndicators(array $rows, int $triwulan): array
    {
        if ($rows === []) {
            return [];
        }

        $indikatorIds = array_map('intval', array_column($rows, 'indikator_id'));
        $opdIds       = $this->bersihkanIds(array_column($rows, 'opd_id'));
        $pkOpd        = [];
        foreach ($rows as $r) {
            $pkOpd[(int) $r['pk_id']] = (int) $r['opd_id'];
        }

        $renaksiMap  = $this->loadRenaksi($indikatorIds, $opdIds);
        $targetIds   = [];
        foreach ($renaksiMap as $daftar) {
            foreach ($daftar as $tr) {
                $targetIds[] = (int) $tr['id'];
            }
        }
        $subMap      = $this->loadSubRencana($targetIds);
        $monevMap    = $this->loadMonev($targetIds, $opdIds);
        $anggaranMap = $this->loadRealisasiAnggaran($targetIds);
        $programMap  = $this->loadProgram($indikatorIds);
        $misiMap     = $this->loadMisi($pkOpd);
        $this->preloadSkala(array_column($rows, 'satuan_id'));

        $out = [];
        foreach ($rows as $r) {
            $indikatorId = (int) $r['indikator_id'];
            $opdId       = (int) $r['opd_id'];
            $satuanTipe  = strtolower((string) ($r['satuan_tipe'] ?? 'angka'));
            $skala       = $this->skalaSatuan((int) ($r['satuan_id'] ?? 0));
            $predikat    = ($satuanTipe === 'predikat') || $skala !== [];

            $renaksi   = $renaksiMap[$indikatorId . ':' . $opdId] ?? [];
            $barisUkur = [];
            $subTotal  = 0;

            foreach ($renaksi as $tr) {
                $targetId = (int) $tr['id'];
                $subs     = $subMap[$targetId] ?? [];
                $subTotal += count($subs);

                foreach ($subs as $sub) {
                    $barisUkur[] = $this->bangunBarisUkur(
                        $tr,
                        $sub,
                        $monevMap[$targetId][(int) $sub['id']] ?? null,
                        $skala,
                        $triwulan,
                        $predikat
                    );
                }

                // Baris capaian tingkat rencana aksi (sub_id = 0). Dipakai bila
                // belum ada sub rencana aksi, atau bila data lama terlanjur
                // tersimpan di sana meski sub-nya sudah dibuat.
                $monev0 = $monevMap[$targetId][0] ?? null;
                if ($subs === [] || ($monev0 !== null && $this->adaCapaian($monev0))) {
                    $barisUkur[] = $this->bangunBarisUkur($tr, null, $monev0, $skala, $triwulan, $predikat);
                }
            }

            $agregat  = $this->agregatIndikator($barisUkur, $renaksi === []);
            $program  = [];
            $anggaran = 0.0;
            foreach ($programMap[$indikatorId] ?? [] as $p) {
                // Pagu program milik Perangkat Daerah LAIN tidak ikut dihitung —
                // lihat catatan pada loadProgram().
                $p['milik_opd_lain'] = $p['program_opd_id'] !== null && $p['program_opd_id'] !== $opdId;
                if (!$p['milik_opd_lain']) {
                    $anggaran += (float) $p['anggaran'];
                }
                $program[] = $p;
            }
            $realisasi = $this->realisasiIndikator($renaksi, $anggaranMap, $triwulan);

            $out[] = [
                'indikator_id'     => $indikatorId,
                'opd_id'           => $opdId,
                'nama_opd'         => (string) ($r['nama_opd'] ?? ''),
                'indikator'        => (string) $r['indikator'],
                'jenis_indikator'  => (string) ($r['jenis_indikator'] ?? ''),
                'satuan'           => (string) ($r['satuan'] ?? ''),
                'satuan_id'        => (int) ($r['satuan_id'] ?? 0),
                'satuan_predikat'  => $predikat,
                'skala'            => $skala,
                'target_tahunan'   => (string) ($r['target_tahunan'] ?? ''),
                'sasaran_id'       => (int) $r['sasaran_id'],
                'sasaran'          => (string) $r['sasaran'],
                'pk_id'            => (int) $r['pk_id'],
                'pk_jenis'         => (string) $r['pk_jenis'],
                'pk_tahun'         => (int) $r['pk_tahun'],
                'pk_tanggal'       => $r['pk_tanggal'],
                'pejabat_id'       => (int) ($r['pejabat_id'] ?? 0),
                'pejabat_nama'     => (string) ($r['pejabat_nama'] ?? ''),
                'pejabat_jabatan'  => (string) ($r['pejabat_jabatan'] ?? ''),
                'renaksi_count'    => count($renaksi),
                'sub_count'        => $subTotal,
                'penanggung_jawab' => $this->penanggungJawab($renaksi),
                'rows'             => $barisUkur,
                'percentage'       => $agregat['percentage'],
                'validity'         => $agregat['validity'],
                'status'           => $agregat['validity']['is_valid']
                    ? getAchievementStatus((float) $agregat['percentage'])
                    : dash_status_nonnumeric($barisUkur === [] ? 'belum_ada_data' : 'belum_valid'),
                'verification'     => $this->verificationInfo(),
                'programs'         => $program,
                'anggaran'         => $anggaran,
                'realisasi'        => $realisasi['nilai'],
                'realisasi_status' => $realisasi['status'],
                'misi'             => $misiMap[(int) $r['pk_id']] ?? [],
                'updated_at'       => $this->updatedAtIndikator($renaksi, $monevMap, $anggaranMap),
            ];
        }

        return $out;
    }

    /**
     * @param int[] $opdIds
     *
     * @return array<string, array<int, array<string, mixed>>> ["indikatorId:opdId" => target_rencana[]]
     */
    private function loadRenaksi(array $indikatorIds, array $opdIds): array
    {
        $indikatorIds = $this->bersihkanIds($indikatorIds);
        $opdIds       = $this->bersihkanIds($opdIds);
        if ($indikatorIds === [] || $opdIds === []) {
            return [];
        }

        $rows = $this->db->table('target_rencana')
            ->select('id, pk_indikator_id, opd_id, rencana_aksi, penanggung_jawab, updated_at,
                      target_triwulan_1, target_triwulan_2, target_triwulan_3, target_triwulan_4')
            ->whereIn('pk_indikator_id', $indikatorIds)
            ->whereIn('opd_id', $opdIds)
            ->orderBy('id', 'ASC')
            ->get()->getResultArray();

        $map = [];
        foreach ($rows as $r) {
            $map[$r['pk_indikator_id'] . ':' . $r['opd_id']][] = $r;
        }

        return $map;
    }

    /** @return array<int, array<int, array<string, mixed>>> [target_rencana_id => sub[]] */
    private function loadSubRencana(array $targetIds): array
    {
        $targetIds = $this->bersihkanIds($targetIds);
        if ($targetIds === []) {
            return [];
        }

        $rows = $this->db->table('target_sub_rencana')
            ->select('id, target_rencana_id, baris_rencana, sub_rencana_aksi, urutan,
                      target_triwulan_1, target_triwulan_2, target_triwulan_3, target_triwulan_4')
            ->whereIn('target_rencana_id', $targetIds)
            ->orderBy('target_rencana_id', 'ASC')
            ->orderBy('baris_rencana', 'ASC')
            ->orderBy('urutan', 'ASC')
            ->orderBy('id', 'ASC')
            ->get()->getResultArray();

        $map = [];
        foreach ($rows as $r) {
            $map[(int) $r['target_rencana_id']][] = $r;
        }

        return $map;
    }

    /**
     * MONEV milik rencana aksi terkait.
     *
     * `monev.opd_id` bernilai NULL untuk MONEV PK Bupati dan terisi untuk PK
     * OPD — keduanya diterima di sini karena lingkupnya sudah dibatasi lewat
     * daftar target_rencana yang ikut.
     *
     * @param int[] $opdIds
     *
     * @return array<int, array<int, array<string, mixed>>> [target_rencana_id => [sub_id => monev]]
     */
    private function loadMonev(array $targetIds, array $opdIds): array
    {
        $targetIds = $this->bersihkanIds($targetIds);
        $opdIds    = $this->bersihkanIds($opdIds);
        if ($targetIds === []) {
            return [];
        }

        $b = $this->db->table('monev')
            ->select('id, target_rencana_id, target_sub_rencana_id, opd_id, metode_perhitungan, total, updated_at,
                      capaian_triwulan_1, capaian_triwulan_2, capaian_triwulan_3, capaian_triwulan_4')
            ->whereIn('target_rencana_id', $targetIds);

        if ($opdIds !== []) {
            $b->groupStart()
                ->whereIn('opd_id', $opdIds)
                ->orWhere('opd_id IS NULL', null, false)
              ->groupEnd();
        }

        $rows = $b->get()->getResultArray();

        $map = [];
        foreach ($rows as $r) {
            $map[(int) $r['target_rencana_id']][(int) $r['target_sub_rencana_id']] = $r;
        }

        return $map;
    }

    /** @return array<int, array<string, mixed>> [target_rencana_id => monev_anggaran] */
    private function loadRealisasiAnggaran(array $targetIds): array
    {
        $targetIds = $this->bersihkanIds($targetIds);
        if ($targetIds === [] || !$this->db->tableExists('monev_anggaran')) {
            return [];
        }

        $rows = $this->db->table('monev_anggaran')
            ->select('id, target_rencana_id, updated_at,
                      realisasi_triwulan_1, realisasi_triwulan_2, realisasi_triwulan_3, realisasi_triwulan_4')
            ->whereIn('target_rencana_id', $targetIds)
            ->get()->getResultArray();

        $map = [];
        foreach ($rows as $r) {
            $map[(int) $r['target_rencana_id']] = $r;
        }

        return $map;
    }

    /**
     * Program & pagu dari rantai PK (pk_program -> program_pk).
     *
     * Program yang sama bisa tercatat lebih dari sekali untuk satu indikator —
     * cukup sekali supaya pagunya tidak berlipat.
     *
     * Program master kabupaten (`program_pk.opd_id` NULL) dipakai apa adanya.
     * Program yang jelas MILIK OPD LAIN (opd_id terisi & berbeda dari pemilik
     * indikator) ditandai `milik_opd_lain` saat perangkaian dan pagunya TIDAK
     * ikut dijumlahkan — biasanya salah pilih saat menyusun PK, dan bila
     * dijumlahkan angkanya menyesatkan. Program itu tetap ditampilkan di
     * drawer sebagai temuan yang perlu diperbaiki, bukan dibuang diam-diam.
     *
     * @return array<int, array<int, array<string, mixed>>>
     */
    private function loadProgram(array $indikatorIds): array
    {
        $indikatorIds = $this->bersihkanIds($indikatorIds);
        if ($indikatorIds === []) {
            return [];
        }

        $rows = $this->db->table('pk_program pp')
            ->select('pp.pk_indikator_id, pr.id AS program_id, pr.kode_program, pr.program_kegiatan,
                      pr.anggaran, pr.tahun_anggaran, pr.jenis_anggaran, pr.opd_id AS program_opd_id')
            ->join('program_pk pr', 'pr.id = pp.program_id', 'inner')
            ->whereIn('pp.pk_indikator_id', $indikatorIds)
            ->orderBy('pr.kode_program', 'ASC')
            ->orderBy('pr.id', 'ASC')
            ->get()->getResultArray();

        $map   = [];
        $sudah = [];
        foreach ($rows as $r) {
            $indikatorId = (int) $r['pk_indikator_id'];
            $programId   = (int) $r['program_id'];
            $kunci       = $indikatorId . ':' . $programId;
            if (isset($sudah[$kunci])) {
                continue;
            }
            $sudah[$kunci] = true;

            $map[$indikatorId][] = [
                'program_id'     => $programId,
                'kode'           => (string) ($r['kode_program'] ?? ''),
                'nama'           => (string) $r['program_kegiatan'],
                'anggaran'       => (float) $r['anggaran'],
                'tahun_anggaran' => $r['tahun_anggaran'],
                'jenis_anggaran' => (string) ($r['jenis_anggaran'] ?? ''),
                'program_opd_id' => $r['program_opd_id'] === null ? null : (int) $r['program_opd_id'],
            ];
        }

        return $map;
    }

    /**
     * Misi Bupati yang didukung tiap PK.
     *
     * Sumber UTAMA: relasi eksplisit pk -> pk_misi -> rpjmd_misi.
     * CADANGAN: bila PK belum dipetakan ke misi, ditelusuri lewat rantai
     * Renstra (renstra_sasaran.opd_id -> renstra_tujuan.rpjmd_sasaran_id ->
     * rpjmd_sasaran -> rpjmd_tujuan -> rpjmd_misi) dan ditandai sumbernya
     * supaya tidak tampak seperti pemetaan resmi.
     * Tidak pernah mencocokkan kemiripan teks indikator.
     *
     * @param array<int, int> $pkOpd [pk_id => opd_id]
     *
     * @return array<int, array<int, array<string, mixed>>> [pk_id => misi[]]
     */
    private function loadMisi(array $pkOpd): array
    {
        $pkIds = $this->bersihkanIds(array_keys($pkOpd));
        if ($pkIds === []) {
            return [];
        }

        $rows = $this->db->table('pk_misi pm')
            ->select('pm.pk_id, rm.id AS misi_id, rm.misi, rm.tahun_mulai, rm.tahun_akhir')
            ->join('rpjmd_misi rm', 'rm.id = pm.rpjmd_misi_id', 'inner')
            ->whereIn('pm.pk_id', $pkIds)
            ->orderBy('rm.id', 'ASC')
            ->get()->getResultArray();

        $map = [];
        foreach ($rows as $r) {
            $map[(int) $r['pk_id']][(int) $r['misi_id']] = [
                'misi_id' => (int) $r['misi_id'],
                'misi'    => (string) $r['misi'],
                'sumber'  => 'pk_misi',
            ];
        }

        $tanpaMisi = array_values(array_diff($pkIds, array_keys($map)));
        foreach ($tanpaMisi as $pkId) {
            $opdId = (int) ($pkOpd[$pkId] ?? 0);
            if ($opdId <= 0) {
                continue;
            }
            foreach ($this->misiViaRenstra($opdId) as $m) {
                $map[$pkId][$m['misi_id']] = $m;
            }
        }

        return array_map('array_values', $map);
    }

    /** @return array<int, array<string, mixed>> */
    private function misiViaRenstra(int $opdId): array
    {
        static $cache = [];
        if (isset($cache[$opdId])) {
            return $cache[$opdId];
        }

        $rows = $this->db->table('renstra_sasaran rs')
            ->select('rm.id AS misi_id, rm.misi')
            ->join('renstra_tujuan rt', 'rt.id = rs.renstra_tujuan_id', 'inner')
            ->join('rpjmd_sasaran rps', 'rps.id = rt.rpjmd_sasaran_id', 'inner')
            ->join('rpjmd_tujuan rpt', 'rpt.id = rps.tujuan_id', 'inner')
            ->join('rpjmd_misi rm', 'rm.id = rpt.misi_id', 'inner')
            ->where('rs.opd_id', $opdId)
            ->groupBy('rm.id, rm.misi')
            ->orderBy('rm.id', 'ASC')
            ->get()->getResultArray();

        $out = [];
        foreach ($rows as $r) {
            $out[] = [
                'misi_id' => (int) $r['misi_id'],
                'misi'    => (string) $r['misi'],
                'sumber'  => 'renstra',
            ];
        }

        return $cache[$opdId] = $out;
    }

    /** Skala predikat seluruh satuan yang dipakai, satu query. */
    private function preloadSkala(array $satuanIds): void
    {
        $satuanIds = $this->bersihkanIds($satuanIds);
        $baru      = array_values(array_diff($satuanIds, array_keys($this->skalaCache)));
        if ($baru === [] || !$this->db->tableExists('satuan_skala')) {
            foreach ($baru as $id) {
                $this->skalaCache[$id] = [];
            }
            return;
        }

        $rows = $this->db->table('satuan_skala')
            ->select('satuan_id, kode, label, nilai, urutan')
            ->whereIn('satuan_id', $baru)
            ->orderBy('urutan', 'ASC')
            ->orderBy('id', 'ASC')
            ->get()->getResultArray();

        foreach ($baru as $id) {
            $this->skalaCache[$id] = [];
        }
        foreach ($rows as $r) {
            $this->skalaCache[(int) $r['satuan_id']][] = $r;
        }
    }

    /** @return array<int, array<string, mixed>> */
    private function skalaSatuan(int $satuanId): array
    {
        if ($satuanId <= 0) {
            return [];
        }
        if (!array_key_exists($satuanId, $this->skalaCache)) {
            $this->preloadSkala([$satuanId]);
        }

        return $this->skalaCache[$satuanId] ?? [];
    }

    /* =====================================================================
     * PERHITUNGAN PER INDIKATOR
     * ===================================================================*/

    /**
     * Satu baris ukur = satu satuan pengukuran yang punya target & capaian
     * triwulanan sendiri: sebuah SUB rencana aksi, atau rencana aksi itu
     * sendiri bila belum dipecah menjadi sub.
     *
     * @param array<string, mixed>      $tr
     * @param array<string, mixed>|null $sub
     * @param array<string, mixed>|null $monev
     *
     * @return array<string, mixed>
     */
    private function bangunBarisUkur(array $tr, ?array $sub, ?array $monev, array $skala, int $triwulan, bool $predikat): array
    {
        $sumber  = $sub ?? $tr;
        $targets = [
            1 => $sumber['target_triwulan_1'] ?? null,
            2 => $sumber['target_triwulan_2'] ?? null,
            3 => $sumber['target_triwulan_3'] ?? null,
            4 => $sumber['target_triwulan_4'] ?? null,
        ];
        $capaian = [
            1 => $monev['capaian_triwulan_1'] ?? null,
            2 => $monev['capaian_triwulan_2'] ?? null,
            3 => $monev['capaian_triwulan_3'] ?? null,
            4 => $monev['capaian_triwulan_4'] ?? null,
        ];

        $validity = dash_row_validity(
            $monev['metode_perhitungan'] ?? null,
            $targets,
            $capaian,
            $skala,
            $triwulan,
            $monev !== null,
            $predikat
        );

        return [
            'target_id' => (int) $tr['id'],
            'sub_id'    => $sub !== null ? (int) $sub['id'] : 0,
            'label'     => $sub !== null
                ? (string) $sub['sub_rencana_aksi']
                : $this->barisPertama((string) ($tr['rencana_aksi'] ?? '')),
            'monev_id'  => $monev !== null ? (int) $monev['id'] : null,
            'metode'    => $monev['metode_perhitungan'] ?? null,
            'targets'   => $targets,
            'capaian'   => $capaian,
            'validity'  => $validity,
        ];
    }

    /**
     * Capaian & validitas satu indikator dari seluruh baris ukurnya.
     *
     * Indikator dinyatakan VALID hanya bila SEMUA baris ukurnya menghasilkan
     * persentase yang sah pada periode yang dipilih. Persentase indikator =
     * rata-rata persentase baris ukurnya (sejalan dengan rekap MONEV existing).
     *
     * @param array<int, array<string, mixed>> $barisUkur
     *
     * @return array{percentage: float|null, validity: array<string, mixed>}
     */
    private function agregatIndikator(array $barisUkur, bool $tanpaRenaksi): array
    {
        if ($tanpaRenaksi || $barisUkur === []) {
            return [
                'percentage' => null,
                'validity'   => [
                    'is_valid'    => false,
                    'reason_code' => 'missing_target',
                    'reason'      => 'Belum memiliki Rencana Aksi, target triwulan belum tersedia.',
                ],
            ];
        }

        $bermasalah = null;
        $jumlah     = 0.0;
        $n          = 0;

        foreach ($barisUkur as $baris) {
            $v = $baris['validity'];
            if (!$v['is_valid']) {
                if ($bermasalah === null
                    || dash_reason_priority($v['reason_code']) < dash_reason_priority($bermasalah['reason_code'])) {
                    $bermasalah = $v;
                }
                continue;
            }
            $jumlah += (float) $v['percentage'];
            $n++;
        }

        if ($bermasalah !== null) {
            return [
                'percentage' => null,
                'validity'   => [
                    'is_valid'    => false,
                    'reason_code' => $bermasalah['reason_code'],
                    'reason'      => $bermasalah['reason'],
                ],
            ];
        }

        return [
            'percentage' => $n > 0 ? round($jumlah / $n, 2) : null,
            'validity'   => $n > 0
                ? ['is_valid' => true, 'reason_code' => null, 'reason' => null]
                : ['is_valid' => false, 'reason_code' => 'not_calculable', 'reason' => dash_reason_label('not_calculable')],
        ];
    }

    /**
     * Realisasi anggaran satu indikator sampai triwulan terpilih.
     *
     * Membedakan realisasi 0 yang SUDAH dilaporkan (nilai sah) dari NULL yang
     * BELUM dilaporkan — NULL tidak pernah dianggap 0.
     *
     * @param array<int, array<string, mixed>> $renaksi
     * @param array<int, array<string, mixed>> $anggaranMap
     *
     * @return array{nilai: float|null, status: string}
     */
    private function realisasiIndikator(array $renaksi, array $anggaranMap, int $triwulan): array
    {
        if ($renaksi === []) {
            return ['nilai' => null, 'status' => 'belum_dilaporkan'];
        }

        $total     = 0.0;
        $adaNilai  = false;
        $adaKosong = false;

        foreach ($renaksi as $tr) {
            $row = $anggaranMap[(int) $tr['id']] ?? null;
            if ($row === null) {
                $adaKosong = true;
                continue;
            }
            for ($q = 1; $q <= $triwulan; $q++) {
                $nilai = $row['realisasi_triwulan_' . $q];
                if ($nilai === null || $nilai === '') {
                    $adaKosong = true;
                    continue;
                }
                $total += (float) $nilai;
                $adaNilai = true;
            }
        }

        if (!$adaNilai) {
            return ['nilai' => null, 'status' => 'belum_dilaporkan'];
        }

        return ['nilai' => $total, 'status' => $adaKosong ? 'sebagian' : 'lengkap'];
    }

    /** @param array<int, array<string, mixed>> $renaksi */
    private function penanggungJawab(array $renaksi): string
    {
        $nama = [];
        foreach ($renaksi as $tr) {
            $pj = trim((string) ($tr['penanggung_jawab'] ?? ''));
            if ($pj !== '' && !in_array($pj, $nama, true)) {
                $nama[] = $pj;
            }
        }

        return implode(', ', $nama);
    }

    /** @param array<int, array<string, mixed>> $renaksi */
    private function updatedAtIndikator(array $renaksi, array $monevMap, array $anggaranMap): ?string
    {
        $waktu = [];
        foreach ($renaksi as $tr) {
            $targetId = (int) $tr['id'];
            if (!empty($tr['updated_at'])) {
                $waktu[] = (string) $tr['updated_at'];
            }
            foreach ($monevMap[$targetId] ?? [] as $m) {
                if (!empty($m['updated_at'])) {
                    $waktu[] = (string) $m['updated_at'];
                }
            }
            if (!empty($anggaranMap[$targetId]['updated_at'])) {
                $waktu[] = (string) $anggaranMap[$targetId]['updated_at'];
            }
        }

        return $waktu === [] ? null : max($waktu);
    }

    /* =====================================================================
     * KARTU & GRAFIK
     * ===================================================================*/

    /**
     * Kartu 1 — PK & kontribusi.
     *
     * @param array<int, array<string, mixed>> $indikator
     *
     * @return array<string, mixed>
     */
    public function getPkSummary(array $indikator, ?int $opdId, int $tahun, string $jenis): array
    {
        $pkIds        = [];
        $mendukungMisi = 0;
        $tanpaRenaksi = 0;
        $misiResmi    = 0;

        foreach ($indikator as $i) {
            $pkIds[$i['pk_id']] = true;
            if ($i['misi'] !== []) {
                $mendukungMisi++;
                foreach ($i['misi'] as $m) {
                    if ($m['sumber'] === 'pk_misi') {
                        $misiResmi++;
                        break;
                    }
                }
            }
            if ($i['renaksi_count'] === 0) {
                $tanpaRenaksi++;
            }
        }

        return [
            'pk_count'            => count($pkIds),
            'indikator'           => count($indikator),
            'mendukung_misi'      => $mendukungMisi,
            'mendukung_misi_resmi' => $misiResmi,
            'tanpa_renaksi'       => $tanpaRenaksi,
            'jenis_label'         => $this->jenisLabel($jenis),
            'pendukung'           => $this->hitungPendukung($opdId, $tahun),
        ];
    }

    /**
     * Jumlah indikator PK pendukung (Administrator & Pengawas) — hanya untuk
     * drill-down, TIDAK ikut dihitung ke capaian total OPD.
     *
     * @return array<string, int>
     */
    private function hitungPendukung(?int $opdId, int $tahun): array
    {
        if (!$opdId) {
            return ['indikator' => 0, 'pk' => 0];
        }

        $row = $this->db->table('pk_indikator pi')
            ->select('COUNT(DISTINCT pi.id) AS indikator, COUNT(DISTINCT pk.id) AS pk')
            ->join('pk_sasaran ps', 'ps.id = pi.pk_sasaran_id', 'inner')
            ->join('pk', 'pk.id = ps.pk_id', 'inner')
            ->where('pk.opd_id', $opdId)
            ->where('pk.tahun', $tahun)
            ->whereIn('pk.jenis', self::JENIS_PENDUKUNG)
            ->get()->getRowArray();

        return ['indikator' => (int) ($row['indikator'] ?? 0), 'pk' => (int) ($row['pk'] ?? 0)];
    }

    /**
     * Kartu 2 — Capaian Kinerja OPD.
     *
     * Capaian Total OPD = jumlah persentase seluruh indikator VALID dibagi
     * jumlah indikator yang WAJIB dihitung, dan HANYA ditampilkan ketika
     * seluruh indikator wajib itu valid.
     *
     * @param array<int, array<string, mixed>> $indikator
     *
     * @return array<string, mixed>
     */
    public function getOpdAchievement(array $indikator): array
    {
        $wajib = count($indikator);
        $valid = 0;
        $jumlah = 0.0;

        foreach ($indikator as $i) {
            if ($i['validity']['is_valid']) {
                $valid++;
                $jumlah += (float) $i['percentage'];
            }
        }

        $bisaDihitung = $wajib > 0 && $valid === $wajib;
        $total        = $bisaDihitung ? round($jumlah / $wajib, 2) : null;
        $verifikasi   = $this->verificationInfo();
        $belumVerif   = $verifikasi['available'] ? 0 : $valid;

        return [
            'total'          => $total,
            'valid'          => $valid,
            'wajib'          => $wajib,
            'belum_valid'    => $wajib - $valid,
            'can_compute'    => $bisaDihitung,
            'status'         => $bisaDihitung ? getAchievementStatus((float) $total) : dash_status_nonnumeric('belum_valid'),
            'verified_all'   => $verifikasi['available'] && $belumVerif === 0,
            'label'          => ($verifikasi['available'] && $belumVerif === 0) ? 'Terverifikasi' : 'Sementara',
            'belum_verifikasi' => $belumVerif,
            'verifikasi'     => $verifikasi,
        ];
    }

    /**
     * Kartu 3 — Penyerapan Anggaran.
     *
     * Pagu dari program_pk lewat rantai PK; realisasi dari monev_anggaran.
     * Pagu program yang menopang beberapa indikator dihitung SEKALI.
     * Efisiensi TIDAK dihitung otomatis di sini — efisiensi tetap input manual
     * pada modul LAKIP (tabel lakip_efisiensi_program).
     *
     * @param array<int, array<string, mixed>> $indikator
     *
     * @return array<string, mixed>
     */
    public function getBudgetAbsorption(array $indikator, int $triwulan): array
    {
        $program   = [];   // program_id => data + indikator pendukung
        $realisasi = 0.0;
        $adaNilai  = false;
        $belumLapor = 0;

        // Realisasi dijumlahkan per target_rencana (bukan per program) agar tidak
        // berlipat saat satu rencana aksi menopang beberapa program.
        $targetTerhitung = [];

        foreach ($indikator as $i) {
            foreach ($i['programs'] as $p) {
                $pid = (int) $p['program_id'];
                if (!isset($program[$pid])) {
                    $program[$pid] = $p + ['indikator' => [], 'realisasi' => null, 'realisasi_status' => 'belum_dilaporkan'];
                }
                $program[$pid]['indikator'][] = [
                    'id'   => $i['indikator_id'],
                    'nama' => $i['indikator'],
                ];
                // Realisasi rencana aksi indikator ini diatributkan ke program yang
                // didukungnya. Bila satu rencana aksi menopang >1 program, angka per
                // program bisa muncul ganda — totalnya tetap dihitung sekali di bawah.
                if ($i['realisasi'] !== null) {
                    $program[$pid]['realisasi'] = (float) ($program[$pid]['realisasi'] ?? 0) + (float) $i['realisasi'];
                    $program[$pid]['realisasi_status'] = $i['realisasi_status'];
                }
            }

            if ($i['realisasi'] !== null && !isset($targetTerhitung[$i['indikator_id']])) {
                $targetTerhitung[$i['indikator_id']] = true;
                $realisasi += (float) $i['realisasi'];
                $adaNilai = true;
            }
            // Sama persis dengan aturan insight "realisasi anggaran belum
            // diperbarui": pagunya ada DAN rencana aksinya sudah dibuat.
            if ($i['realisasi_status'] !== 'lengkap' && $i['anggaran'] > 0 && $i['renaksi_count'] > 0) {
                $belumLapor++;
            }
        }

        $anggaran     = 0.0;
        $anggaranLain = 0.0;
        $programLain  = [];
        $programSah   = [];
        foreach ($program as $p) {
            if ($p['milik_opd_lain']) {
                $anggaranLain += (float) $p['anggaran'];
                $programLain[] = $p;
                continue;
            }
            $anggaran += (float) $p['anggaran'];
            $programSah[] = $p;
        }

        $persen = ($anggaran > 0 && $adaNilai) ? round($realisasi / $anggaran * 100, 2) : null;

        return [
            'anggaran'       => $anggaran,
            'realisasi'      => $adaNilai ? $realisasi : null,
            'persen'         => $persen,
            'program_count'  => count($programSah),
            'belum_lapor'    => $belumLapor,
            'programs'       => $programSah,
            // Program yang tercatat milik Perangkat Daerah lain: tidak dijumlahkan
            // ke pagu, tapi tetap dilaporkan agar bisa dibetulkan lewat modul PK.
            'program_lain'       => $programLain,
            'anggaran_lain'      => $anggaranLain,
            'program_lain_count' => count($programLain),
            'triwulan'       => $triwulan,
        ];
    }

    /**
     * Grafik 1 — distribusi status indikator.
     *
     * Satu indikator masuk TEPAT satu kategori: indikator tidak valid masuk
     * "Belum Valid", sisanya masuk kategori ambang sesuai persentasenya.
     * Kategori "Belum Terverifikasi" ikut disertakan hanya bila mekanisme
     * verifikasi memang tersedia (lihat verificationInfo()).
     *
     * @param array<int, array<string, mixed>> $indikator
     *
     * @return array<string, mixed>
     */
    public function getStatusDistribution(array $indikator): array
    {
        $segmen = [];
        foreach (dash_threshold_rows() as $t) {
            $s = dash_status_from_row($t);
            $segmen[$s['code']] = ['code' => $s['code'], 'name' => $s['name'], 'color' => $s['color_hex'], 'count' => 0];
        }

        $belumValid  = dash_status_nonnumeric('belum_valid');
        $belumVerif  = dash_status_nonnumeric('belum_terverifikasi');
        $verifikasi  = $this->verificationInfo();
        $segmen['belum_valid'] = ['code' => 'belum_valid', 'name' => $belumValid['name'], 'color' => $belumValid['color_hex'], 'count' => 0];
        if ($verifikasi['available']) {
            $segmen['belum_terverifikasi'] = ['code' => 'belum_terverifikasi', 'name' => $belumVerif['name'], 'color' => $belumVerif['color_hex'], 'count' => 0];
        }

        $valid = 0;
        foreach ($indikator as $i) {
            if (!$i['validity']['is_valid']) {
                $segmen['belum_valid']['count']++;
                continue;
            }
            $valid++;
            if ($verifikasi['available'] && ($i['verification']['code'] ?? '') !== 'verified') {
                $segmen['belum_terverifikasi']['count']++;
                continue;
            }
            $code = $i['status']['code'];
            if (!isset($segmen[$code])) {
                $segmen[$code] = ['code' => $code, 'name' => $i['status']['name'], 'color' => $i['status']['color_hex'], 'count' => 0];
            }
            $segmen[$code]['count']++;
        }

        return [
            'segments' => array_values(array_filter($segmen, static fn ($s) => $s['count'] > 0)),
            'valid'    => $valid,
            'total'    => count($indikator),
            'caption'  => count($indikator) > 0
                ? 'Status ' . $valid . ' dari ' . count($indikator) . ' indikator yang dapat dihitung.'
                : 'Belum ada indikator PK untuk periode ini.',
        ];
    }

    /**
     * Grafik 2 — pilihan seri Target vs Capaian triwulanan.
     *
     * Satu seri = satu baris ukur (indikator/sub rencana aksi) karena satuan &
     * metode perhitungan antarindikator berbeda dan tidak boleh digabung.
     *
     * @param array<int, array<string, mixed>> $indikator
     *
     * @return array<int, array<string, mixed>>
     */
    public function getQuarterlyOptions(array $indikator): array
    {
        $out = [];
        foreach ($indikator as $i) {
            foreach ($i['rows'] as $idx => $baris) {
                $out[] = [
                    'key'        => $i['indikator_id'] . '-' . $baris['target_id'] . '-' . $baris['sub_id'],
                    'indikator_id' => $i['indikator_id'],
                    'indikator'  => $i['indikator'],
                    'label'      => $baris['label'] !== '' ? $baris['label'] : ('Rencana Aksi #' . ($idx + 1)),
                    'satuan'     => $i['satuan'],
                    'status'     => $i['status']['code'],
                    'is_valid'   => $baris['validity']['is_valid'],
                    'misi'       => $i['misi'] !== [],
                    'series'     => $this->seriTriwulan($baris, $i['skala']),
                ];
            }
        }

        return $out;
    }

    /**
     * Titik grafik triwulanan sebuah baris ukur.
     *
     * Nilai predikat (mis. WTP) dipetakan ke skor skalanya untuk posisi grafik,
     * sementara label aslinya tetap dibawa untuk tooltip.
     *
     * @param array<string, mixed>             $baris
     * @param array<int, array<string, mixed>> $skala
     *
     * @return array<string, mixed>
     */
    private function seriTriwulan(array $baris, array $skala): array
    {
        $peta   = capaianSkalaMap($skala);
        $target = [];
        $capaian = [];
        $labelTarget = [];
        $labelCapaian = [];

        foreach ([1, 2, 3, 4] as $q) {
            $t = $baris['targets'][$q] ?? null;
            $c = $baris['capaian'][$q] ?? null;
            $target[]  = capaianTerisi($t) ? capaianNilaiSkala($t, $peta) : null;
            $capaian[] = capaianTerisi($c) ? capaianNilaiSkala($c, $peta) : null;
            $labelTarget[]  = capaianTerisi($t) ? (string) $t : null;
            $labelCapaian[] = capaianTerisi($c) ? (string) $c : null;
        }

        return [
            'target'        => $target,
            'capaian'       => $capaian,
            'label_target'  => $labelTarget,
            'label_capaian' => $labelCapaian,
            'predikat'      => $peta !== [],
            'metode'        => $baris['metode'],
            'metode_nama'   => capaianMetodeNama($baris['metode']),
        ];
    }

    /**
     * Panel prioritas tindak lanjut — sepenuhnya BERBASIS ATURAN.
     *
     * Urutan: indikator kritis -> perlu perhatian -> indikator belum valid ->
     * MONEV terlambat -> Rencana Aksi belum ada -> realisasi anggaran belum
     * diperbarui -> status verifikasi.
     *
     * @param array<int, array<string, mixed>> $indikator
     * @param array<string, mixed>             $anggaran
     *
     * @return array<int, array<string, mixed>>
     */
    public function getPriorityInsights(
        array $indikator,
        array $anggaran,
        int $tahun,
        int $triwulan,
        string $jenis,
        ?int $opdId
    ): array {
        $tautan  = $this->moduleLinks($tahun, $jenis, $opdId);
        $urlRen  = $tautan['renaksi'];
        $urlMon  = $tautan['monev'];
        $urlLkp  = $tautan['lakip'];

        $out = [];

        foreach ($indikator as $i) {
            $judul = $i['indikator'];

            if ($i['validity']['is_valid']) {
                $kode = $i['status']['code'];
                if ($kode === 'critical') {
                    $out[] = $this->insight(10, 'indikator_kritis', $judul,
                        'Capaian ' . capaianFormatPersen($i['percentage']) . ' berstatus ' . $i['status']['name'] . '.',
                        $i['status']['name'], $i['status']['color'], $urlMon, 'Buka MONEV', $i['indikator_id']);
                } elseif ($kode === 'attention') {
                    $out[] = $this->insight(20, 'indikator_perhatian', $judul,
                        'Capaian ' . capaianFormatPersen($i['percentage']) . ' berstatus ' . $i['status']['name'] . '.',
                        $i['status']['name'], $i['status']['color'], $urlMon, 'Buka MONEV', $i['indikator_id']);
                }
                continue;
            }

            $alasan = (string) ($i['validity']['reason'] ?? dash_reason_label((string) $i['validity']['reason_code']));
            $kode   = (string) $i['validity']['reason_code'];

            if ($kode === 'missing_target') {
                // Dibedakan: rencana aksinya memang belum dibuat, atau sudah ada
                // tapi target triwulanannya masih kosong. Tindak lanjutnya sama.
                $out[] = $this->insight(50, 'renaksi_belum', $judul, $alasan,
                    $i['renaksi_count'] === 0 ? 'Rencana Aksi belum ada' : 'Target triwulan belum diisi',
                    'oranye', $urlRen, 'Kelola Rencana Aksi', $i['indikator_id']);
                continue;
            }
            if (in_array($kode, ['missing_achievement', 'incomplete_period'], true)) {
                $out[] = $this->insight(40, 'monev_belum', $judul, $alasan,
                    'MONEV belum lengkap', 'oranye', $urlMon, 'Buka MONEV', $i['indikator_id']);
                continue;
            }

            $out[] = $this->insight(30, 'indikator_belum_valid', $judul, $alasan,
                'Belum Valid', 'abu', $urlMon, 'Lengkapi MONEV', $i['indikator_id']);
        }

        // Realisasi anggaran belum diperbarui (pagu ada, laporan realisasi belum lengkap).
        foreach ($indikator as $i) {
            if ($i['anggaran'] <= 0 || $i['realisasi_status'] === 'lengkap' || $i['renaksi_count'] === 0) {
                continue;
            }
            $out[] = $this->insight(60, 'anggaran_belum', $i['indikator'],
                $i['realisasi_status'] === 'belum_dilaporkan'
                    ? 'Realisasi anggaran belum dilaporkan sama sekali.'
                    : 'Realisasi anggaran s.d. Triwulan ' . capaianRomawi($triwulan) . ' belum lengkap.',
                'Realisasi anggaran', 'biru', $urlMon, 'Perbarui Realisasi', $i['indikator_id']);
        }

        // Status verifikasi/pelaporan — memakai status LAKIP yang memang ada,
        // bukan status verifikasi karangan (lihat verificationInfo()).
        $lakip = $this->statusLakip($opdId, $tahun);
        if ($lakip['perlu_tindak_lanjut']) {
            $out[] = $this->insight(70, 'verifikasi', 'Laporan Kinerja (LAKIP) ' . $tahun,
                $lakip['pesan'], 'Belum final', 'abu', $urlLkp, 'Buka LAKIP', null);
        }

        usort($out, static fn ($a, $b) => $a['severity'] <=> $b['severity'] ?: strcmp($a['judul'], $b['judul']));

        return $out;
    }

    /** @return array<string, mixed> */
    private function insight(
        int $severity,
        string $code,
        string $judul,
        string $alasan,
        string $status,
        string $warna,
        string $url,
        string $tombol,
        ?int $indikatorId
    ): array {
        return [
            'severity'     => $severity,
            'code'         => $code,
            'judul'        => $judul,
            'alasan'       => $alasan,
            'status'       => $status,
            'color'        => dash_color($warna),
            'url'          => $url,
            'tombol'       => $tombol,
            'indikator_id' => $indikatorId,
        ];
    }

    /**
     * Ringkasan kartu 4 — Perlu Perhatian.
     *
     * @param array<int, array<string, mixed>> $indikator
     * @param array<int, array<string, mixed>> $insights
     *
     * @return array<string, mixed>
     */
    private function ringkasPerhatian(array $indikator, array $insights, array $anggaran): array
    {
        $hitung = static function (array $insights, string $code): int {
            return count(array_filter($insights, static fn ($i) => $i['code'] === $code));
        };

        return [
            'total'           => count($insights),
            'kritis'          => $hitung($insights, 'indikator_kritis'),
            'perlu_perhatian' => $hitung($insights, 'indikator_perhatian'),
            'belum_valid'     => $hitung($insights, 'indikator_belum_valid'),
            'monev_belum'     => $hitung($insights, 'monev_belum'),
            'renaksi_belum'   => $hitung($insights, 'renaksi_belum'),
            'anggaran_belum'  => $hitung($insights, 'anggaran_belum'),
            'verifikasi'      => $hitung($insights, 'verifikasi'),
        ];
    }

    /**
     * Panel kontribusi terhadap Misi Bupati.
     *
     * @param array<int, array<string, mixed>> $indikator
     *
     * @return array<string, mixed>
     */
    public function getMissionContributions(array $indikator, ?int $opdId, int $tahun): array
    {
        $misi       = [];
        $tanpaMisi  = [];

        foreach ($indikator as $i) {
            if ($i['misi'] === []) {
                $tanpaMisi[] = $this->ringkasIndikator($i);
                continue;
            }
            foreach ($i['misi'] as $m) {
                $id = (int) $m['misi_id'];
                if (!isset($misi[$id])) {
                    $misi[$id] = [
                        'misi_id'    => $id,
                        'misi'       => $m['misi'],
                        'sumber'     => $m['sumber'],
                        'indikator'  => 0,
                        'valid'      => 0,
                        'belum'      => 0,
                        'daftar'     => [],
                    ];
                }
                $misi[$id]['indikator']++;
                $misi[$id][$i['validity']['is_valid'] ? 'valid' : 'belum']++;
                $misi[$id]['daftar'][] = $this->ringkasIndikator($i);
                if ($m['sumber'] === 'pk_misi') {
                    $misi[$id]['sumber'] = 'pk_misi';
                }
            }
        }

        ksort($misi);
        $urut = 1;
        foreach ($misi as $id => $m) {
            $misi[$id]['nomor'] = $urut++;
        }

        return [
            'items'      => array_values($misi),
            'tanpa_misi' => $tanpaMisi,
        ];
    }

    /**
     * Detail satu indikator untuk drawer.
     *
     * @return array<string, mixed>|null
     */
    public function getIndicatorDetail(int $opdId, int $indikatorId, int $tahun, int $triwulan, string $jenis): ?array
    {
        // Dicari dalam lingkup jenis pimpinan lebih dulu, lalu ke seluruh jenis
        // PK milik OPD ini (drill-down indikator pendukung). Selalu di-scope
        // pk.opd_id = OPD pengguna -> indikator OPD lain tidak pernah terbaca.
        foreach (array_unique([$jenis, 'jpt', 'camat', 'administrator', 'pengawas']) as $j) {
            $rows = $this->loadIndicators($opdId, $tahun, $j, $triwulan, null, $indikatorId);
            if ($rows !== []) {
                $detail = $rows[0];
                $detail['status_verifikasi'] = $this->verificationInfo();
                $detail['series']            = $this->getQuarterlyOptions([$detail]);
                $detail['jenis_label']       = $this->jenisLabel($j);
                $detail['pk_segment']        = $this->pkSegment($j);

                return $detail;
            }
        }

        return null;
    }

    /**
     * Bentuk ringkas indikator untuk daftar/drawer (tanpa data mentah besar).
     *
     * @param array<string, mixed> $i
     *
     * @return array<string, mixed>
     */
    private function ringkasIndikator(array $i): array
    {
        return [
            'indikator_id'    => $i['indikator_id'],
            'indikator'       => $i['indikator'],
            'sasaran'         => $i['sasaran'],
            'satuan'          => $i['satuan'],
            'target_tahunan'  => $i['target_tahunan'],
            'percentage'      => $i['percentage'],
            'percentage_teks' => $i['percentage'] !== null ? capaianFormatPersen($i['percentage']) : null,
            'status'          => $i['status'],
            'is_valid'        => $i['validity']['is_valid'],
            'reason_code'     => $i['validity']['reason_code'],
            'reason'          => $i['validity']['reason'],
            'verification'    => $i['verification'],
            'renaksi_count'   => $i['renaksi_count'],
            'sub_count'       => $i['sub_count'],
            'misi'            => $i['misi'],
            'anggaran'        => $i['anggaran'],
            'realisasi'       => $i['realisasi'],
            'realisasi_status' => $i['realisasi_status'],
            'metode'          => $i['rows'][0]['metode'] ?? null,
            'metode_nama'     => capaianMetodeNama($i['rows'][0]['metode'] ?? null),
            'capaian_terakhir' => $this->capaianTerakhir($i),
            'penanggung_jawab' => $i['penanggung_jawab'],
            'updated_at'      => $i['updated_at'],
        ];
    }

    /** Nilai capaian terisi dengan nomor triwulan terbesar (apa adanya, termasuk predikat). */
    private function capaianTerakhir(array $i): ?array
    {
        $hasil = null;
        foreach ($i['rows'] as $baris) {
            for ($q = 4; $q >= 1; $q--) {
                if (capaianTerisi($baris['capaian'][$q] ?? null)) {
                    if ($hasil === null || $q > $hasil['triwulan']) {
                        $hasil = ['triwulan' => $q, 'nilai' => (string) $baris['capaian'][$q]];
                    }
                    break;
                }
            }
        }

        return $hasil;
    }

    /**
     * Detail satu program untuk drawer anggaran.
     *
     * @return array<string, mixed>|null
     */
    public function getProgramDetail(int $opdId, int $programId, int $tahun, int $triwulan, string $jenis): ?array
    {
        $indikator = $this->loadIndicators($opdId, $tahun, $jenis, $triwulan);
        $anggaran  = $this->getBudgetAbsorption($indikator, $triwulan);

        foreach ($anggaran['programs'] as $p) {
            if ((int) $p['program_id'] === $programId) {
                return $p;
            }
        }

        return null;
    }

    /**
     * Waktu perubahan data terakhir yang relevan dengan dashboard ini.
     */
    public function getLastUpdate(?int $opdId, int $tahun): ?string
    {
        if (!$opdId) {
            return null;
        }

        $waktu = [];

        $tr = $this->db->table('target_rencana tr')
            ->select('MAX(tr.updated_at) AS w')
            ->join('pk_indikator pi', 'pi.id = tr.pk_indikator_id', 'inner')
            ->join('pk_sasaran ps', 'ps.id = pi.pk_sasaran_id', 'inner')
            ->join('pk', 'pk.id = ps.pk_id', 'inner')
            ->where('tr.opd_id', $opdId)
            ->where('pk.tahun', $tahun)
            ->get()->getRowArray();
        $waktu[] = $tr['w'] ?? null;

        $mv = $this->db->table('monev m')
            ->select('MAX(m.updated_at) AS w')
            ->join('target_rencana tr', 'tr.id = m.target_rencana_id', 'inner')
            ->join('pk_indikator pi', 'pi.id = tr.pk_indikator_id', 'inner')
            ->join('pk_sasaran ps', 'ps.id = pi.pk_sasaran_id', 'inner')
            ->join('pk', 'pk.id = ps.pk_id', 'inner')
            ->where('tr.opd_id', $opdId)
            ->where('pk.tahun', $tahun)
            ->get()->getRowArray();
        $waktu[] = $mv['w'] ?? null;

        $waktu = array_values(array_filter($waktu));

        return $waktu === [] ? null : max($waktu);
    }

    /* =====================================================================
     * STATUS VERIFIKASI
     * ===================================================================*/

    /**
     * Status verifikasi capaian indikator PK.
     *
     * TEMUAN: tidak ada satu pun kolom/tabel di database yang menyimpan
     * verifikasi capaian per indikator PK — `monev`, `target_rencana`,
     * `pk_indikator` tidak punya kolom status; status yang ada hanya milik
     * dokumen lain (lakip.status, renstra/rpjmd status draft|selesai,
     * iku_indikator.status).
     *
     * Maka seluruh capaian dilaporkan apa adanya sebagai "Sementara" dan
     * TIDAK ada nilai yang diklaim terverifikasi. Struktur datanya sudah siap
     * menampung label "Terverifikasi": begitu mekanisme verifikasi dibuat
     * (mis. kolom `monev.status_verifikasi` + siapa/kapan), cukup ubah fungsi
     * ini menjadi membaca kolom tersebut — kartu, grafik, dan drawer otomatis
     * ikut. Penambahan kolom itu SENGAJA belum dilakukan karena berada di luar
     * lingkup dashboard dan berdampak ke modul MONEV (form, simpan, cetak).
     *
     * @return array{code: string, label: string, available: bool, note: string}
     */
    public function verificationInfo(): array
    {
        return [
            'code'      => 'unverified',
            'label'     => 'Sementara',
            'available' => false,
            'note'      => 'Belum ada mekanisme verifikasi capaian pada sistem — seluruh nilai berstatus Sementara.',
        ];
    }

    /**
     * Status LAKIP OPD tahun berjalan — satu-satunya status "kefinalan"
     * pelaporan yang benar-benar ada datanya.
     *
     * @return array{status: string|null, pesan: string, perlu_tindak_lanjut: bool}
     */
    private function statusLakip(?int $opdId, int $tahun): array
    {
        if (!$opdId) {
            return ['status' => null, 'pesan' => '', 'perlu_tindak_lanjut' => false];
        }

        $rows = $this->db->table('lakip l')
            ->select('l.status, COUNT(*) AS jumlah')
            ->join('renstra_target rt', 'rt.id = l.renstra_target_id', 'inner')
            ->join('renstra_indikator_sasaran ris', 'ris.id = rt.renstra_indikator_id', 'inner')
            ->join('renstra_sasaran rs', 'rs.id = ris.renstra_sasaran_id', 'inner')
            ->where('rs.opd_id', $opdId)
            ->where('rt.tahun', $tahun)
            ->groupBy('l.status')
            ->get()->getResultArray();

        if ($rows === []) {
            return [
                'status'              => null,
                'pesan'               => 'Belum ada data LAKIP tahun ' . $tahun . ' sehingga capaian belum difinalkan.',
                'perlu_tindak_lanjut' => true,
            ];
        }

        $belumFinal = 0;
        foreach ($rows as $r) {
            if (!in_array(strtolower((string) $r['status']), ['selesai', 'siap'], true)) {
                $belumFinal += (int) $r['jumlah'];
            }
        }

        if ($belumFinal > 0) {
            return [
                'status'              => 'proses',
                'pesan'               => $belumFinal . ' baris LAKIP ' . $tahun . ' masih berstatus draft/proses.',
                'perlu_tindak_lanjut' => true,
            ];
        }

        return ['status' => 'siap', 'pesan' => 'LAKIP ' . $tahun . ' sudah final.', 'perlu_tindak_lanjut' => false];
    }

    /* =====================================================================
     * UTILITAS
     * ===================================================================*/

    /** @return int[] */
    private function bersihkanIds(array $ids): array
    {
        return array_values(array_unique(array_filter(array_map('intval', $ids))));
    }

    private function adaCapaian(array $monev): bool
    {
        foreach ([1, 2, 3, 4] as $q) {
            if (capaianTerisi($monev['capaian_triwulan_' . $q] ?? null)) {
                return true;
            }
        }

        return false;
    }

    private function barisPertama(string $teks): string
    {
        $baris = preg_split('/\r\n|\r|\n/', trim($teks)) ?: [];

        return trim((string) ($baris[0] ?? ''));
    }
}
