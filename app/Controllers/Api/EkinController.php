<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\OpdModel;
use App\Services\IkpRekapService;
use Throwable;

/**
 * =====================================================================
 * API untuk eKin Internal Pringsewu (token TERPISAH: filter api-token:ekin,
 * env EKIN_API_TOKEN). Hanya GET. Kontrak: RANCANGAN §2.4 dan
 * API_DOCUMENTATION.md bagian "API eKin (token terpisah)".
 *
 *   GET api/ekin/opd?tahun=                         OPD aktif + kepala (PK jpt|camat pihak_1)
 *   GET api/ekin/pegawai?opd_id=|q=|ids=&tahun=      roster (maks 500), TANPA data rahasia
 *   GET api/ekin/pegawai/{id}/kinerja?tahun=         sumber RHK seorang pegawai
 *   GET api/ekin/opd/{id}/ikp?tahun=                 IKP OPD: bulanan, triwulan, capaian
 *
 * Amplop rumah: {status:'success', meta:{…}, data:…}; galat
 * {status:'error', message:'…'} dengan kode HTTP 400/404/500.
 *
 * MENGAPA daftar kolom pegawai ditulis TANGAN (bukan p.*): tabel pegawai
 * produksi adalah salinan tabel presensi — memuat password, tanggal_lahir,
 * tukin, device_id, no_whatsapp. Endpoint ini hanya boleh mengirim yang
 * dibutuhkan eKin (identitas, jabatan, OPD, pangkat, status).
 *
 * MENGAPA ada peta alias OPD: pegawai BKPSDM tercatat di opd 210 (tidak ada
 * di tabel opd), Kec. Gadingrejo di 213, sebagian DP3AP2KB di 13 — sedangkan
 * akun, pohon kinerja, PK dan IKP memakai 8, 32, 211 (kritik 0.10).
 * =====================================================================
 */
class EkinController extends BaseController
{
    /** id OPD pegawai → id OPD efektif (yang dipakai pohon/PK/IKP). */
    private const ALIAS_KE_EFEKTIF = [210 => 8, 213 => 32, 13 => 211];

    /** id OPD efektif → semua id OPD tempat pegawainya tercatat. */
    private const ALIAS_OPD = [8 => [8, 210], 32 => [32, 213], 211 => [211, 13]];

    private const MAKS_PEGAWAI = 500;

    private const PESAN_TAHUN = 'Parameter tahun tidak valid: gunakan tahun 4 digit antara 2000 dan 2100, mis. tahun=2026.';

    /** Urutan alasan IKP bila satu IKP muncul karena beberapa sebab (paling spesifik dulu). */
    private const PRIORITAS_ALASAN = ['pj' => 1, 'simpul' => 2, 'kepala_opd' => 3];

    /** @var \CodeIgniter\Database\BaseConnection */
    protected $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }

    // =================================================================
    // GET api/ekin/opd
    // =================================================================

    public function opd()
    {
        try {
            $tahun = $this->tahun();
            if ($tahun === null) {
                return $this->galat(self::PESAN_TAHUN, 400);
            }

            $rows = $this->db->table('opd')->select('id, nama_opd, singkatan, jenis')
                ->whereNotIn('id', $this->opdTidakTampil())
                ->orderBy('nama_opd', 'ASC')->get()->getResultArray();

            $kepala = $this->kepalaOpd(array_map('intval', array_column($rows, 'id')), $tahun);

            $data = [];
            foreach ($rows as $r) {
                $id     = (int) $r['id'];
                $data[] = [
                    'id'              => $id,
                    'nama_opd'        => (string) $r['nama_opd'],
                    'singkatan'       => $r['singkatan'] ?? null,
                    'jenis'           => $r['jenis'] ?? null,
                    'pegawai_opd_ids' => self::ALIAS_OPD[$id] ?? [$id],
                    'kepala'          => $kepala[$id] ?? null,
                ];
            }

            return $this->sukses($data, ['tahun' => $tahun, 'count' => count($data)]);
        } catch (Throwable $e) {
            return $this->galatServer($e, 'api.ekin.opd');
        }
    }

    // =================================================================
    // GET api/ekin/pegawai?opd_id=&q=&ids=
    // =================================================================

    public function pegawai()
    {
        try {
            $tahun = $this->tahun();
            if ($tahun === null) {
                return $this->galat(self::PESAN_TAHUN, 400);
            }

            $opdMinta = $this->request->getGet('opd_id');
            $q        = $this->request->getGet('q');
            $idsMinta = $this->request->getGet('ids');

            $opdId = null;
            if ($opdMinta !== null && $opdMinta !== '') {
                if (! is_string($opdMinta) || ! ctype_digit($opdMinta) || (int) $opdMinta < 1) {
                    return $this->galat('opd_id harus berupa angka.', 400);
                }
                $opdId = $this->efektif((int) $opdMinta);
                if (! $this->opdAda($opdId)) {
                    return $this->galat('Perangkat daerah tidak ditemukan.', 404);
                }
            }

            $cari = null;
            if ($q !== null && $q !== '') {
                if (! is_string($q) || mb_strlen(trim($q)) < 3) {
                    return $this->galat('Parameter q minimal 3 karakter.', 400);
                }
                // MENGAPA daftar karakter dibatasi: % dan _ adalah wildcard LIKE;
                // "q=%%%" akan menjadi "semua pegawai" dan melangkahi syarat
                // minimal 3 huruf. Nama & NIP cukup dengan huruf, angka, spasi, . , ' -
                if (! preg_match("/^[\\p{L}\\p{N} .,'\\-]+$/u", trim($q))) {
                    return $this->galat("Parameter q hanya boleh berisi huruf, angka, spasi, titik, koma, petik, atau tanda hubung.", 400);
                }
                $cari = mb_substr(trim($q), 0, 100);
            }

            $ids = null;
            if ($idsMinta !== null && $idsMinta !== '') {
                $ids = $this->daftarId($idsMinta);
                if ($ids === []) {
                    return $this->galat('Parameter ids harus berisi angka dipisah koma, mis. ids=44,57.', 400);
                }
                if (count($ids) > self::MAKS_PEGAWAI) {
                    return $this->galat('Parameter ids maksimal ' . self::MAKS_PEGAWAI . ' id.', 400);
                }
            }

            if ($opdId === null && $cari === null && $ids === null) {
                return $this->galat('Wajib salah satu parameter: opd_id, q, atau ids.', 400);
            }

            $b = $this->queryPegawai();
            if ($opdId !== null) {
                $b->whereIn('p.opd_id', self::ALIAS_OPD[$opdId] ?? [$opdId]);
            }
            if ($cari !== null) {
                $b->groupStart()->like('p.nama_pegawai', $cari)->orLike('p.nip_pegawai', $cari)->groupEnd();
            }
            if ($ids !== null) {
                $b->whereIn('p.id', $ids);
            }
            $rows = $b->orderBy('p.nama_pegawai', 'ASC')->orderBy('p.id', 'ASC')
                ->limit(self::MAKS_PEGAWAI + 1)->get()->getResultArray();

            $terpotong = count($rows) > self::MAKS_PEGAWAI;
            $rows      = array_slice($rows, 0, self::MAKS_PEGAWAI);

            $namaOpd = $this->namaOpd($rows);
            $pkJenis = $this->pkJenisPerPegawai(array_map('intval', array_column($rows, 'id')), $tahun);

            $data = [];
            foreach ($rows as $r) {
                $data[] = $this->bentukPegawai($r, $namaOpd) + ['pk_jenis' => $pkJenis[(int) $r['id']] ?? []];
            }

            return $this->sukses($data, [
                'tahun'     => $tahun,
                'count'     => count($data),
                'maks'      => self::MAKS_PEGAWAI,
                'terpotong' => $terpotong,
                'filter'    => array_filter(['opd_id' => $opdId, 'q' => $cari, 'ids' => $ids], static fn ($v) => $v !== null),
            ]);
        } catch (Throwable $e) {
            return $this->galatServer($e, 'api.ekin.pegawai');
        }
    }

    // =================================================================
    // GET api/ekin/pegawai/{id}/kinerja?tahun=
    // =================================================================

    public function kinerja($pegawaiId = null)
    {
        try {
            $tahun = $this->tahun();
            if ($tahun === null) {
                return $this->galat(self::PESAN_TAHUN, 400);
            }
            $pid = ctype_digit((string) $pegawaiId) ? (int) $pegawaiId : 0;
            $peg = $pid > 0 ? $this->queryPegawai()->where('p.id', $pid)->get()->getRowArray() : null;
            if (! $peg) {
                return $this->galat('Pegawai tidak ditemukan.', 404);
            }

            $pegawai = $this->bentukPegawai($peg, $this->namaOpd([$peg]));

            // ---------- PK yang ia tanda tangani sebagai pihak_1
            $pkRows = $this->db->table('pk')
                ->select('id, opd_id, tahun, jenis, pihak_2, is_plt_pihak_1, is_plh_pihak_1')
                ->where('pihak_1', $pid)->where('tahun', $tahun)
                ->orderBy('id', 'ASC')->get()->getResultArray();
            $peranPk = array_map(static fn (array $r): array => [
                'pk_id'              => (int) $r['id'],
                'jenis'              => (string) $r['jenis'],
                'tahun'              => (int) $r['tahun'],
                'opd_id'             => (int) $r['opd_id'],
                'pihak_2_pegawai_id' => $r['pihak_2'] !== null ? (int) $r['pihak_2'] : null,
                'plt'                => (int) $r['is_plt_pihak_1'] === 1,
                'plh'                => (int) $r['is_plh_pihak_1'] === 1,
            ], $pkRows);

            // ---------- Simpul pohon kinerja miliknya
            [$cascading, $tersembunyi, $indikatorMilik, $simpulMilik] = $this->simpulMilik($pid, $tahun);

            // ---------- IKP
            $opdKepala = [];
            foreach ($pkRows as $r) {
                if (in_array($r['jenis'], ['jpt', 'camat'], true)) {
                    $opdKepala[] = (int) $r['opd_id'];
                }
            }
            $ikp = $this->ikpPegawai($pid, $tahun, array_values(array_unique($opdKepala)), $simpulMilik, $indikatorMilik);

            // ---------- Indikator PK tahun itu dengan pihak_1 = dia
            $pkIndikator = $this->pkIndikator($pid, $tahun);

            return $this->sukses([
                'pegawai'      => $pegawai,
                'peran_pk'     => $peranPk,
                'cascading'    => $cascading,
                'ikp'          => $ikp,
                'pk_indikator' => $pkIndikator,
            ], [
                'tahun'              => $tahun,
                'pegawai_id'         => $pid,
                'simpul_tersembunyi' => $tersembunyi,
            ]);
        } catch (Throwable $e) {
            return $this->galatServer($e, 'api.ekin.kinerja');
        }
    }

    // =================================================================
    // GET api/ekin/opd/{id}/ikp?tahun=
    // =================================================================

    public function ikpOpd($opdId = null)
    {
        try {
            $tahun = $this->tahun();
            if ($tahun === null) {
                return $this->galat(self::PESAN_TAHUN, 400);
            }
            $id = ctype_digit((string) $opdId) ? $this->efektif((int) $opdId) : 0;
            $opd = $id > 0 && $this->opdAda($id)
                ? $this->db->table('opd')->select('id, nama_opd, singkatan, jenis')->where('id', $id)->get()->getRowArray()
                : null;
            if (! $opd) {
                return $this->galat('Perangkat daerah tidak ditemukan.', 404);
            }

            $svc   = new IkpRekapService($this->db);
            $rekap = $svc->siap() ? $svc->rekapOpd($id, $tahun) : [];

            $data = [];
            foreach ($rekap as $r) {
                $i     = $r['ikp'];
                $bulan = [];
                for ($m = 1; $m <= 12; $m++) {
                    $bulan[$m] = [
                        'target'    => $r['bulan'][$m]['target'] ?? null,
                        'realisasi' => $r['bulan'][$m]['realisasi'] ?? null,
                    ];
                }
                $tw = [];
                for ($q = 1; $q <= 4; $q++) {
                    $t = $r['triwulan'][$q] ?? [];
                    $tw[$q] = [
                        'target'       => $t['target'] ?? null,
                        'realisasi'    => $t['realisasi'] ?? null,
                        'capaian'      => $t['capaian'] ?? null,
                        'status'       => $t['status'] ?? null,
                        'status_label' => $t['status_label'] ?? null,
                        'warna'        => $t['warna'] ?? null,
                        // true = triwulan belum lengkap; capaian dihitung dari bulan yang sudah terisi.
                        'berjalan'     => (bool) ($t['berjalan'] ?? false),
                    ];
                }
                $tb = $r['tahun_berjalan'];
                $data[] = [
                    'ikp_id'                 => (int) $i['id'],
                    'kategori'               => (string) $i['kategori'],
                    'program_unggulan'       => $i['pu_nama'] ?? null,
                    'indikator'              => (string) $i['output_prioritas'],
                    'satuan'                 => (string) ($i['satuan_label'] ?? ''),
                    'metode'                 => $i['metode'] ?? null,
                    'target_5_tahun'         => $i['target_5_tahun'] ?? null,
                    'target_tahunan'         => $r['target_tahunan'],
                    'target_tahunan_teks'    => $r['target_tahunan_teks'],
                    'bulan'                  => $bulan,
                    'triwulan'               => $tw,
                    'capaian_tahun_berjalan' => [
                        'persen'       => $tb['persen'],
                        'status'       => $tb['status'],
                        'status_label' => $tb['status_label'] ?? null,
                        'warna'        => $tb['warna'] ?? null,
                        'sampai_bulan' => $tb['sampai_bulan'],
                    ],
                    'pj_pegawai_id'          => $i['pj_pegawai_id'] !== null ? (int) $i['pj_pegawai_id'] : null,
                    'cascading_sasaran_id'   => $i['cascading_sasaran_id'] !== null ? (int) $i['cascading_sasaran_id'] : null,
                    'cascading_indikator_id' => $i['cascading_indikator_id'] !== null ? (int) $i['cascading_indikator_id'] : null,
                ];
            }

            $kepala = $this->kepalaOpd([$id], $tahun)[$id] ?? null;

            return $this->sukses($data, [
                'opd'               => ['id' => (int) $opd['id'], 'nama_opd' => (string) $opd['nama_opd'],
                                        'singkatan' => $opd['singkatan'] ?? null, 'jenis' => $opd['jenis'] ?? null],
                'tahun'             => $tahun,
                'kepala_pegawai_id' => $kepala['pegawai_id'] ?? null,
                'count'             => count($data),
            ]);
        } catch (Throwable $e) {
            return $this->galatServer($e, 'api.ekin.ikp_opd');
        }
    }

    // =================================================================
    // SIMPUL MILIK PEGAWAI
    // =================================================================

    /**
     * Simpul cascading yang dimiliki pegawai tahun itu, lengkap dengan
     * indikator (satuan, target tahunan, metode, tautan IKP, target bulanan
     * IKP) dan INDUK-nya (RHK atasan yang diintervensi, sebagai usulan).
     *
     * Simpul yang tidak tampil di pohon (berjangkar ke indikator IKU yang
     * dihentikan, atau IKU di luar periode tahun itu — aturan yang sama
     * dengan CascadingModel::getCascadingMatrixByOpd) TIDAK dikirim; jumlahnya
     * dilaporkan di meta.simpul_tersembunyi.
     *
     * @return array{0: array, 1: int, 2: int[], 3: int[]}
     *               [daftar simpul, jumlah tersembunyi, id indikator milik, id simpul milik]
     */
    private function simpulMilik(int $pid, int $tahun): array
    {
        $milik = $this->db->table('cascading_pemilik cp')
            ->select('cp.cascading_sasaran_id AS node_id, cp.peran, cp.jabatan_teks, cp.is_plt, cp.sumber')
            ->where('cp.pegawai_id', $pid)->where('cp.tahun', $tahun)
            ->orderBy('cp.cascading_sasaran_id', 'ASC')->get()->getResultArray();
        if ($milik === []) {
            return [[], 0, [], []];
        }

        // Muat simpul milik + seluruh leluhurnya (maks. 3 putaran: pelaksana → es4 → es3).
        $simpul = [];
        $ind    = [];
        $cari   = array_map('intval', array_column($milik, 'node_id'));
        for ($putaran = 0; $putaran < 4 && $cari !== []; $putaran++) {
            $baru = $this->db->table('cascading_sasaran_opd')
                ->select('id, opd_id, level, nama_sasaran, es3_indikator_id, iku_indikator_id')
                ->whereIn('id', $cari)->get()->getResultArray();
            $indukInd = [];
            foreach ($baru as $s) {
                $simpul[(int) $s['id']] = $s;
                if ($s['level'] !== 'es3' && $s['es3_indikator_id'] !== null && ! isset($ind[(int) $s['es3_indikator_id']])) {
                    $indukInd[] = (int) $s['es3_indikator_id'];
                }
            }
            $cari = [];
            if ($indukInd !== []) {
                foreach ($this->db->table('cascading_indikator_opd')->select('id, cascading_sasaran_id, indikator, satuan')
                    ->whereIn('id', array_values(array_unique($indukInd)))->get()->getResultArray() as $i) {
                    $ind[(int) $i['id']] = $i;
                    if (! isset($simpul[(int) $i['cascading_sasaran_id']])) {
                        $cari[] = (int) $i['cascading_sasaran_id'];
                    }
                }
            }
            $cari = array_values(array_unique($cari));
        }

        // Jangkar IKU simpul Eselon III (aktif + periode memuat tahun).
        $ikuIds = [];
        foreach ($simpul as $s) {
            if ($s['level'] === 'es3' && $s['iku_indikator_id'] !== null) {
                $ikuIds[] = (int) $s['iku_indikator_id'];
            }
        }
        $iku = [];
        if ($ikuIds !== []) {
            foreach ($this->db->table('iku_indikator iki')
                ->select('iki.id, iki.indikator, iki.dihentikan_pada, iks.id AS sasaran_id, iks.sasaran, iks.opd_id, iks.tahun_mulai, iks.tahun_akhir')
                ->join('iku_sasaran iks', 'iks.id = iki.iku_sasaran_id', 'inner')
                ->whereIn('iki.id', array_values(array_unique($ikuIds)))->get()->getResultArray() as $r) {
                $iku[(int) $r['id']] = $r;
            }
        }

        // Aktif? Telusuri ke atas sampai Eselon III lalu cek IKU-nya.
        $aktif = function (int $id) use (&$aktif, $simpul, $ind, $iku, $tahun): bool {
            $s = $simpul[$id] ?? null;
            if ($s === null) {
                return false;
            }
            if ($s['level'] === 'es3') {
                $k = $iku[(int) $s['iku_indikator_id']] ?? null;

                return $k !== null && $k['dihentikan_pada'] === null
                    && (int) $k['opd_id'] === (int) $s['opd_id']
                    && (int) $k['tahun_mulai'] <= $tahun && (int) $k['tahun_akhir'] >= $tahun;
            }
            $harap = $s['level'] === 'pelaksana' ? 'es4' : 'es3';
            $i     = $ind[(int) $s['es3_indikator_id']] ?? null;
            $induk = $i !== null ? ($simpul[(int) $i['cascading_sasaran_id']] ?? null) : null;

            return $induk !== null && $induk['level'] === $harap && $aktif((int) $induk['id']);
        };

        $milikAktif  = [];
        $tersembunyi = 0;
        foreach ($milik as $m) {
            if ($aktif((int) $m['node_id'])) {
                $milikAktif[] = $m;
            } else {
                $tersembunyi++;
            }
        }
        if ($milikAktif === []) {
            return [[], $tersembunyi, [], []];
        }
        $nodeIds = array_map('intval', array_column($milikAktif, 'node_id'));

        // Indikator simpul milik + target tahun itu + IKP tertaut.
        $indMilik = $this->db->table('cascading_indikator_opd ci')
            ->select('ci.id, ci.cascading_sasaran_id, ci.indikator, ci.satuan, cit.target, cit.target_teks, cit.metode, cit.ikp_id,
                      ikp.dihapus_pada AS ikp_dihapus')
            ->join('cascading_indikator_target cit', 'cit.cascading_indikator_id = ci.id AND cit.tahun = ' . (int) $tahun, 'left', false)
            ->join('ikp', 'ikp.id = cit.ikp_id', 'left')
            ->whereIn('ci.cascading_sasaran_id', $nodeIds)
            ->orderBy('ci.id', 'ASC')->get()->getResultArray();

        $ikpTaut = [];
        foreach ($indMilik as $r) {
            if ($r['ikp_id'] !== null && $r['ikp_dihapus'] === null) {
                $ikpTaut[] = (int) $r['ikp_id'];
            }
        }
        $bulananIkp = $this->targetBulananIkp(array_values(array_unique($ikpTaut)), $tahun);

        $perSimpul = [];
        foreach ($indMilik as $r) {
            $ikpId = $r['ikp_id'] !== null && $r['ikp_dihapus'] === null ? (int) $r['ikp_id'] : null;
            $perSimpul[(int) $r['cascading_sasaran_id']][] = [
                'id'             => (int) $r['id'],
                'nama'           => (string) $r['indikator'],
                'satuan'         => ($r['satuan'] ?? '') !== '' ? (string) $r['satuan'] : null,
                'target_tahunan' => $r['target'] !== null ? (float) $r['target'] : null,
                'target_teks'    => $r['target_teks'] ?? null,
                'metode'         => $r['metode'] ?? null,
                'ikp_id'         => $ikpId,
                'target_bulanan' => $ikpId !== null ? ($bulananIkp[$ikpId] ?? null) : null,
            ];
        }

        // Pemilik simpul induk (usulan "RHK atasan yang diintervensi").
        $indukNodeIds = [];
        foreach ($nodeIds as $id) {
            $s = $simpul[$id];
            if ($s['level'] !== 'es3') {
                $indukNodeIds[] = (int) $ind[(int) $s['es3_indikator_id']]['cascading_sasaran_id'];
            }
        }
        $pemilikInduk = [];
        if ($indukNodeIds !== []) {
            foreach ($this->db->table('cascading_pemilik')->select('cascading_sasaran_id, pegawai_id')
                ->whereIn('cascading_sasaran_id', array_values(array_unique($indukNodeIds)))
                ->where('tahun', $tahun)->orderBy("FIELD(peran, 'penanggung_jawab', 'anggota')", '', false)
                ->orderBy('id', 'ASC')->get()->getResultArray() as $r) {
                $pemilikInduk[(int) $r['cascading_sasaran_id']][] = (int) $r['pegawai_id'];
            }
        }
        $opdIds = array_values(array_unique(array_map(static fn ($id) => (int) $simpul[$id]['opd_id'], $nodeIds)));
        $kepala = $this->kepalaOpd($opdIds, $tahun);
        $label  = [];
        foreach ($opdIds as $o) {
            $label[$o] = $this->labelLevel($o);
        }

        $hasil = [];
        foreach ($milikAktif as $m) {
            $id  = (int) $m['node_id'];
            $s   = $simpul[$id];
            $opd = (int) $s['opd_id'];
            if ($s['level'] === 'es3') {
                $k     = $iku[(int) $s['iku_indikator_id']];
                $induk = [
                    'jenis'               => 'iku',
                    'level'               => 'es2',
                    'level_label'         => $label[$opd]['es2'],
                    'node_id'             => null,
                    'sasaran_id'          => (int) $k['sasaran_id'],
                    'sasaran'             => (string) $k['sasaran'],
                    'indikator_id'        => (int) $k['id'],
                    'indikator'           => (string) $k['indikator'],
                    'pemilik_pegawai_ids' => isset($kepala[$opd]) ? [$kepala[$opd]['pegawai_id']] : [],
                ];
            } else {
                $i     = $ind[(int) $s['es3_indikator_id']];
                $p     = $simpul[(int) $i['cascading_sasaran_id']];
                $induk = [
                    'jenis'               => 'cascading',
                    'level'               => (string) $p['level'],
                    'level_label'         => $label[$opd][$p['level']],
                    'node_id'             => (int) $p['id'],
                    'sasaran'             => (string) $p['nama_sasaran'],
                    'indikator_id'        => (int) $i['id'],
                    'indikator'           => (string) $i['indikator'],
                    'pemilik_pegawai_ids' => $pemilikInduk[(int) $p['id']] ?? [],
                ];
            }
            $hasil[] = [
                'node_id'      => $id,
                'opd_id'       => $opd,
                'level'        => (string) $s['level'],
                'level_label'  => $label[$opd][$s['level']],
                'sasaran'      => (string) $s['nama_sasaran'],
                'peran'        => (string) $m['peran'],
                'jabatan_teks' => $m['jabatan_teks'] ?? null,
                'plt'          => (int) $m['is_plt'] === 1,
                'induk'        => $induk,
                'indikator'    => $perSimpul[$id] ?? [],
            ];
        }

        $indikatorIds = array_map('intval', array_column($indMilik, 'id'));

        return [$hasil, $tersembunyi, $indikatorIds, $nodeIds];
    }

    /**
     * Label jenjang per OPD; kecamatan bergeser satu tingkat (Camat = Eselon
     * III). Aturan sama dengan halaman Pemilik Kinerja.
     *
     * @return array<string, string>
     */
    private function labelLevel(int $opdId): array
    {
        static $memo = [];
        if (isset($memo[$opdId])) {
            return $memo[$opdId];
        }
        $jenis = $this->db->table('opd')->select('jenis')->where('id', $opdId)->get()->getRowArray()['jenis'] ?? '';
        $kec   = $jenis === OpdModel::JENIS_KECAMATAN
            || $this->db->table('pk')->where('opd_id', $opdId)->where('jenis', 'camat')->countAllResults() > 0;

        return $memo[$opdId] = $kec
            ? ['es2' => 'Eselon III (Camat)', 'es3' => 'Eselon IV', 'es4' => 'Pelaksana / JF', 'pelaksana' => 'Staf Pelaksana']
            : ['es2' => 'Eselon II', 'es3' => 'Eselon III', 'es4' => 'Eselon IV / JF', 'pelaksana' => 'Pelaksana'];
    }

    // =================================================================
    // IKP PEGAWAI
    // =================================================================

    /**
     * IKP yang relevan bagi seorang pegawai, dengan alasan:
     *   pj          ikp.pj_pegawai_id = dia
     *   simpul      IKP tertaut ke simpul yang ia miliki (ikp.cascading_sasaran_id,
     *               ikp.cascading_indikator_id, atau cascading_indikator_target.ikp_id)
     *   kepala_opd  ia pihak_1 PK JPT/Camat OPD itu tahun itu → SEMUA IKP aktif OPD
     *
     * MENGAPA "simpul": Ketua Tim yang ikut memiliki simpul bank sampah
     * (anggota) bukan PJ IKP mana pun, tetapi RHK-nya jelas mengintervensi IKP
     * yang tertaut ke simpul itu. Tanpa aturan ini eKin tidak menawarkannya.
     */
    private function ikpPegawai(int $pid, int $tahun, array $opdKepala, array $simpulMilik, array $indikatorMilik): array
    {
        $svc = new IkpRekapService($this->db);
        if (! $svc->siap()) {
            return [];
        }

        /** @var array<int, array{opd:int, alasan:string, node_ids:int[]}> $kandidat */
        $kandidat = [];
        $catat = function (int $ikpId, int $opd, string $alasan, ?int $node = null) use (&$kandidat): void {
            if (! isset($kandidat[$ikpId])) {
                $kandidat[$ikpId] = ['opd' => $opd, 'alasan' => $alasan, 'node_ids' => []];
            } elseif (self::PRIORITAS_ALASAN[$alasan] < self::PRIORITAS_ALASAN[$kandidat[$ikpId]['alasan']]) {
                $kandidat[$ikpId]['alasan'] = $alasan;
            }
            if ($node !== null && ! in_array($node, $kandidat[$ikpId]['node_ids'], true)) {
                $kandidat[$ikpId]['node_ids'][] = $node;
            }
        };

        $dasar = fn () => $this->db->table('ikp i')->select('i.id, i.opd_id, i.cascading_sasaran_id, i.cascading_indikator_id')
            ->where('i.dihapus_pada', null);

        foreach ($dasar()->where('i.pj_pegawai_id', $pid)->get()->getResultArray() as $r) {
            $catat((int) $r['id'], (int) $r['opd_id'], 'pj');
        }

        if ($simpulMilik !== [] || $indikatorMilik !== []) {
            $b = $dasar()->groupStart();
            if ($simpulMilik !== []) {
                $b->whereIn('i.cascading_sasaran_id', $simpulMilik);
            }
            if ($indikatorMilik !== []) {
                $b->orWhereIn('i.cascading_indikator_id', $indikatorMilik);
            }
            $b->groupEnd();
            $indKeSimpul = [];
            if ($indikatorMilik !== []) {
                foreach ($this->db->table('cascading_indikator_opd')->select('id, cascading_sasaran_id')
                    ->whereIn('id', $indikatorMilik)->get()->getResultArray() as $x) {
                    $indKeSimpul[(int) $x['id']] = (int) $x['cascading_sasaran_id'];
                }
            }
            foreach ($b->get()->getResultArray() as $r) {
                $node = in_array((int) $r['cascading_sasaran_id'], $simpulMilik, true)
                    ? (int) $r['cascading_sasaran_id']
                    : ($indKeSimpul[(int) $r['cascading_indikator_id']] ?? null);
                $catat((int) $r['id'], (int) $r['opd_id'], 'simpul', $node);
            }
            if ($indikatorMilik !== []) {
                foreach ($this->db->table('cascading_indikator_target cit')
                    ->select('cit.ikp_id, cit.cascading_indikator_id, i.opd_id')
                    ->join('ikp i', 'i.id = cit.ikp_id AND i.dihapus_pada IS NULL', 'inner', false)
                    ->whereIn('cit.cascading_indikator_id', $indikatorMilik)->where('cit.tahun', $tahun)
                    ->get()->getResultArray() as $r) {
                    $catat((int) $r['ikp_id'], (int) $r['opd_id'], 'simpul', $indKeSimpul[(int) $r['cascading_indikator_id']] ?? null);
                }
            }
        }

        foreach ($opdKepala as $opd) {
            foreach ($dasar()->where('i.opd_id', $opd)->get()->getResultArray() as $r) {
                $catat((int) $r['id'], (int) $r['opd_id'], 'kepala_opd');
            }
        }

        if ($kandidat === []) {
            return [];
        }

        // Rekap per OPD — angka yang sama persis dengan layar IKP & monitoring.
        $perOpd = [];
        foreach ($kandidat as $ikpId => $k) {
            $perOpd[$k['opd']][] = $ikpId;
        }
        $hasil = [];
        foreach ($perOpd as $opd => $ids) {
            foreach ($svc->rekapOpd((int) $opd, $tahun, ['ids' => $ids]) as $r) {
                $i  = $r['ikp'];
                $id = (int) $i['id'];
                $tb = [];
                $rb = [];
                for ($m = 1; $m <= 12; $m++) {
                    $tb[$m] = $r['bulan'][$m]['target'] ?? null;
                    $rb[$m] = $r['bulan'][$m]['realisasi'] ?? null;
                }
                $hasil[] = [
                    'ikp_id'                 => $id,
                    'opd_id'                 => (int) $i['opd_id'],
                    'kategori'               => (string) $i['kategori'],
                    'program_unggulan'       => $i['pu_nama'] ?? null,
                    'indikator'              => (string) $i['output_prioritas'],
                    'satuan'                 => (string) ($i['satuan_label'] ?? ''),
                    'metode'                 => $i['metode'] ?? null,
                    'target_5_tahun'         => $i['target_5_tahun'] ?? null,
                    'target_tahunan'         => $r['target_tahunan'],
                    'target_tahunan_teks'    => $r['target_tahunan_teks'],
                    'target_bulanan'         => $tb,
                    'realisasi_bulanan'      => $rb,
                    'pj_pegawai_id'          => $i['pj_pegawai_id'] !== null ? (int) $i['pj_pegawai_id'] : null,
                    'cascading_sasaran_id'   => $i['cascading_sasaran_id'] !== null ? (int) $i['cascading_sasaran_id'] : null,
                    'cascading_indikator_id' => $i['cascading_indikator_id'] !== null ? (int) $i['cascading_indikator_id'] : null,
                    'alasan'                 => $kandidat[$id]['alasan'],
                    'node_ids'               => $kandidat[$id]['node_ids'],
                ];
            }
        }
        usort($hasil, static fn ($a, $b) => [self::PRIORITAS_ALASAN[$a['alasan']], $a['ikp_id']] <=> [self::PRIORITAS_ALASAN[$b['alasan']], $b['ikp_id']]);

        return $hasil;
    }

    /**
     * Target bulanan IKP tahun itu: [ikp_id => [1..12 => ?float]].
     *
     * @param int[] $ikpIds
     */
    private function targetBulananIkp(array $ikpIds, int $tahun): array
    {
        if ($ikpIds === []) {
            return [];
        }
        // Dibaca lewat IkpRekapService::bulanan() — sumber yang sama dengan
        // layar IKP, rekap Kabupaten/Bupati, dan endpoint opd/{id}/ikp.
        $hasil = [];
        foreach ((new IkpRekapService($this->db))->bulanan($ikpIds, $tahun) as $id => $bulan) {
            foreach ($bulan as $m => $b) {
                $hasil[$id][$m] = $b['target'];
            }
        }

        return $hasil;
    }

    // =================================================================
    // INDIKATOR PK
    // =================================================================

    private function pkIndikator(int $pid, int $tahun): array
    {
        $rows = $this->db->table('pk')
            ->select('pk.id AS pk_id, pk.jenis, pk.opd_id, pi.id AS pk_indikator_id, ps.sasaran, pi.indikator,
                      pi.jenis_indikator, pi.target, s.satuan, tr.id AS target_rencana_id, tr.rencana_aksi,
                      tr.target_triwulan_1, tr.target_triwulan_2, tr.target_triwulan_3, tr.target_triwulan_4')
            ->join('pk_sasaran ps', 'ps.pk_id = pk.id', 'inner')
            ->join('pk_indikator pi', 'pi.pk_sasaran_id = ps.id', 'inner')
            ->join('satuan s', 's.id = pi.id_satuan', 'left')
            // target_rencana unik per (pk_indikator_id, opd_id): sambung dengan OPD PK-nya.
            ->join('target_rencana tr', 'tr.pk_indikator_id = pi.id AND tr.opd_id = pk.opd_id', 'left')
            ->where('pk.pihak_1', $pid)->where('pk.tahun', $tahun)
            ->orderBy('pk.id', 'ASC')->orderBy('ps.id', 'ASC')->orderBy('pi.id', 'ASC')
            ->get()->getResultArray();

        $hasil = [];
        foreach ($rows as $r) {
            $tw = null;
            if ($r['target_rencana_id'] !== null) {
                $tw = [];
                for ($q = 1; $q <= 4; $q++) {
                    $v      = $r['target_triwulan_' . $q];
                    $tw[$q] = $v !== null && trim((string) $v) !== '' ? (string) $v : null;
                }
            }
            $aksi = [];
            foreach (preg_split('/\R/u', (string) ($r['rencana_aksi'] ?? '')) as $baris) {
                $baris = trim($baris);
                if ($baris !== '') {
                    $aksi[] = $baris;
                }
            }
            $hasil[] = [
                'pk_id'           => (int) $r['pk_id'],
                'jenis_pk'        => (string) $r['jenis'],
                'opd_id'          => (int) $r['opd_id'],
                'pk_indikator_id' => (int) $r['pk_indikator_id'],
                'sasaran'         => (string) $r['sasaran'],
                'indikator'       => (string) $r['indikator'],
                'jenis_indikator' => $r['jenis_indikator'] ?? null,
                'satuan'          => $r['satuan'] ?? null,
                'target'          => $r['target'] !== null ? (string) $r['target'] : null,
                'target_triwulan' => $tw,
                'rencana_aksi'    => $aksi,
            ];
        }

        return $hasil;
    }

    // =================================================================
    // PEGAWAI & OPD
    // =================================================================

    /** Kolom pegawai yang BOLEH keluar — lihat catatan di kepala kelas. */
    private function queryPegawai()
    {
        return $this->db->table('pegawai p')
            ->select("p.id, p.nip_pegawai, p.nama_pegawai, p.opd_id, p.jabatan_id, p.status, p.is_plt,
                      j.nama_jabatan, j.simpeg_id AS jabatan_simpeg, pg.nama_pangkat, pg.golongan", false)
            ->join('jabatan j', 'j.id = p.jabatan_id', 'left')
            ->join('pangkat pg', 'pg.id = p.pangkat_id', 'left');
    }

    private function bentukPegawai(array $r, array $namaOpd): array
    {
        $asal    = (int) $r['opd_id'];
        $efektif = $this->efektif($asal);
        $pangkat = trim((string) ($r['nama_pangkat'] ?? ''));
        $gol     = trim((string) ($r['golongan'] ?? ''));
        $pangkat = in_array($pangkat, ['', '-'], true) ? '' : $pangkat;
        $gol     = in_array($gol, ['', '-'], true) ? '' : $gol;

        return [
            'id'               => (int) $r['id'],
            'nip'              => $r['nip_pegawai'] ?? null,
            'nama'             => (string) $r['nama_pegawai'],
            'opd_id'           => $asal,
            'opd_id_efektif'   => $efektif > 0 ? $efektif : null,
            'opd'              => $namaOpd[$efektif] ?? null,
            'jabatan_id'       => $r['jabatan_id'] !== null && (int) $r['jabatan_id'] > 0 ? (int) $r['jabatan_id'] : null,
            'jabatan'          => $r['nama_jabatan'] ?? null,
            'jabatan_kategori' => $this->kategoriJabatan($r['jabatan_simpeg'] ?? null),
            'status'           => $r['status'] ?? null,
            'pangkat'          => $pangkat !== '' ? ($gol !== '' ? $pangkat . ' (' . $gol . ')' : $pangkat) : ($gol !== '' ? $gol : null),
            'plt'              => (int) ($r['is_plt'] ?? 0) === 1,
        ];
    }

    /** struktural|fungsional|pelaksana dari awalan jabatan.simpeg_id; selain itu null. */
    private function kategoriJabatan(?string $simpegId): ?string
    {
        foreach (['struktural', 'fungsional', 'pelaksana'] as $k) {
            if (str_starts_with((string) $simpegId, $k . '-')) {
                return $k;
            }
        }

        return null;
    }

    /** @return array<int, string> [opd_id efektif => nama] untuk baris pegawai */
    private function namaOpd(array $rows): array
    {
        $ids = [];
        foreach ($rows as $r) {
            $e = $this->efektif((int) $r['opd_id']);
            if ($e > 0) {
                $ids[$e] = $e;
            }
        }
        if ($ids === []) {
            return [];
        }
        $hasil = [];
        foreach ($this->db->table('opd')->select('id, nama_opd')->whereIn('id', array_values($ids))->get()->getResultArray() as $o) {
            $hasil[(int) $o['id']] = (string) $o['nama_opd'];
        }

        return $hasil;
    }

    /** @return array<int, string[]> [pegawai_id => jenis PK tahun itu sebagai pihak_1] */
    private function pkJenisPerPegawai(array $pegawaiIds, int $tahun): array
    {
        if ($pegawaiIds === []) {
            return [];
        }
        $hasil = [];
        foreach ($this->db->table('pk')->select('pihak_1, jenis')->whereIn('pihak_1', $pegawaiIds)
            ->where('tahun', $tahun)->groupBy(['pihak_1', 'jenis'])->get()->getResultArray() as $r) {
            $hasil[(int) $r['pihak_1']][] = (string) $r['jenis'];
        }

        return $hasil;
    }

    /**
     * Kepala OPD tahun itu = pihak_1 PK jpt/camat (PK terbaru bila lebih dari satu).
     *
     * @param int[] $opdIds
     * @return array<int, array> [opd_id => kepala]
     */
    private function kepalaOpd(array $opdIds, int $tahun): array
    {
        if ($opdIds === []) {
            return [];
        }
        $rows = $this->db->table('pk')
            ->select('pk.id, pk.opd_id, pk.jenis, pk.pihak_1, pk.is_plt_pihak_1, pk.is_plh_pihak_1, pk.jabatan_pihak_1_manual,
                      p.nama_pegawai, p.nip_pegawai, j.nama_jabatan')
            ->join('pegawai p', 'p.id = pk.pihak_1', 'left')
            ->join('jabatan j', 'j.id = p.jabatan_id', 'left')
            ->whereIn('pk.opd_id', $opdIds)->where('pk.tahun', $tahun)
            ->whereIn('pk.jenis', ['jpt', 'camat'])->where('pk.pihak_1 >', 0)
            ->orderBy('pk.id', 'ASC')->get()->getResultArray();

        $hasil = [];
        foreach ($rows as $r) {
            $jab = ($r['jabatan_pihak_1_manual'] ?? '') !== '' ? $r['jabatan_pihak_1_manual'] : ($r['nama_jabatan'] ?? null);
            // PK terakhir menimpa: pergantian kepala di tengah tahun dibuatkan PK baru.
            $hasil[(int) $r['opd_id']] = [
                'pegawai_id' => (int) $r['pihak_1'],
                'nama'       => $r['nama_pegawai'] ?? null,
                'nip'        => $r['nip_pegawai'] ?? null,
                'jabatan'    => $jab,
                'plt'        => (int) $r['is_plt_pihak_1'] === 1,
                'plh'        => (int) $r['is_plh_pihak_1'] === 1,
                'pk_id'      => (int) $r['id'],
                'jenis_pk'   => (string) $r['jenis'],
            ];
        }

        return $hasil;
    }

    private function efektif(int $opdId): int
    {
        return self::ALIAS_KE_EFEKTIF[$opdId] ?? $opdId;
    }

    /** OPD yang tidak dikirim: dikecualikan rumah + id alias (duplikat). */
    private function opdTidakTampil(): array
    {
        return array_values(array_unique(array_merge(OpdModel::EXCLUDED_OPD_IDS, array_keys(self::ALIAS_KE_EFEKTIF))));
    }

    private function opdAda(int $opdId): bool
    {
        return $opdId > 0 && ! in_array($opdId, $this->opdTidakTampil(), true)
            && $this->db->table('opd')->where('id', $opdId)->countAllResults() > 0;
    }

    // =================================================================
    // MASUKAN & AMPLOP
    // =================================================================

    /** ?tahun= (bawaan tahun berjalan WIB — AKSARA sendiri berjalan UTC). null = tidak sah. */
    private function tahun(): ?int
    {
        $t = $this->request->getGet('tahun');
        if ($t === null || $t === '') {
            return (int) (new \DateTimeImmutable('now', new \DateTimeZone('Asia/Jakarta')))->format('Y');
        }
        if (! is_string($t) || ! preg_match('/^\d{4}$/', $t) || (int) $t < 2000 || (int) $t > 2100) {
            return null;
        }

        return (int) $t;
    }

    /** "1,2,3" atau ids[]=1&ids[]=2 → int[] unik positif; [] bila ada yang bukan angka. */
    private function daftarId($nilai): array
    {
        $bagian = is_array($nilai) ? $nilai : explode(',', (string) $nilai);
        $ids    = [];
        foreach ($bagian as $v) {
            $v = is_string($v) || is_int($v) ? trim((string) $v) : '';
            if ($v === '') {
                continue;
            }
            if (! ctype_digit($v) || (int) $v < 1) {
                return [];
            }
            $ids[(int) $v] = (int) $v;
        }

        return array_values($ids);
    }

    private function sukses($data, array $meta = [])
    {
        return $this->response->setStatusCode(200)->setJSON([
            'status' => 'success',
            'meta'   => $meta,
            'data'   => $data,
        ]);
    }

    private function galat(string $pesan, int $kode)
    {
        return $this->response->setStatusCode($kode)->setJSON([
            'status'  => 'error',
            'message' => $pesan,
        ]);
    }

    /** Galat teknis: dicatat dengan kode rujukan, pesan umum ke klien. */
    private function galatServer(Throwable $e, string $operasi)
    {
        return $this->galat(pesanGalat($e, $operasi), 500);
    }
}
