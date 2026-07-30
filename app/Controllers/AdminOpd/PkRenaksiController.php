<?php

namespace App\Controllers\AdminOpd;

use App\Controllers\BaseController;
use App\Models\Opd\TargetModel;
use App\Models\Opd\MonevModel;
use App\Models\SatuanModel;
use Config\Database;

/**
 * Monitoring realisasi PK lewat Rencana Aksi (renaksi) + MONEV.
 *
 * Modul TERISOLASI dari TargetController/MonevController Renstra/RPJMD agar
 * alur kadis (eselon II) yang sudah jalan tidak terganggu. Mereuse:
 *   - target_rencana.pk_indikator_id  (jangkar baru ke pk_indikator)
 *   - TargetModel::getTargetListByPk* / existsForPkIndikator
 *   - MonevModel::getIndexDataPk* / upsertForTarget
 *
 * $jenis:
 *   'bupati' -> PK Bupati (pk.jenis='bupati'),  input oleh admin_kab,  monev.opd_id = NULL
 *   'es3'    -> PK OPD/Kecamatan (jpt, camat, administrator, pengawas),
 *               input oleh admin_opd (per OPD), monev.opd_id = target_rencana.opd_id
 */
class PkRenaksiController extends BaseController
{
    /** `capaian` menyediakan rumus Capaian Total (persentase) untuk MONEV. */
    protected $helpers = ['cascading_label', 'capaian'];

    protected TargetModel $targets;
    protected MonevModel $monev;
    protected SatuanModel $satuan;
    protected $db;

    public function __construct()
    {
        $this->targets = new TargetModel();
        $this->monev   = new MonevModel();
        $this->satuan  = new SatuanModel();
        $this->db      = Database::connect();
    }

    /**
     * Skala predikat satuan sebuah indikator (kosong = satuan angka biasa).
     *
     * Sengaja diambil terpisah, bukan lewat JOIN di query besar: instalasi yang
     * belum menjalankan db/update_2026-07-27_satuan_predikat.sql tetap jalan
     * (SatuanModel mengembalikan [] kalau tabelnya belum ada), tinggal input
     * target/capaiannya kembali berupa angka bebas.
     *
     * @return array<int, array<string, mixed>>
     */
    private function skalaSatuan(?array $ctx): array
    {
        return $this->satuan->skalaBySatuan((int) ($ctx['satuan_id'] ?? 0));
    }

    /* ===================== HELPER KONTEKS ===================== */

    /** Normalisasi segmen jenis URL -> 'bupati' | 'es3'. 'kabupaten' = alias URL bersih utk bupati. */
    private function normJenis(string $jenis): ?string
    {
        $j = strtolower(trim($jenis));
        if ($j === 'kabupaten') {
            return 'bupati';
        }
        if (in_array($j, ['es3', 'camat', 'kecamatan', 'administrator', 'pengawas', 'jpt'], true)) {
            return 'es3';
        }
        return $j === 'bupati' ? 'bupati' : null;
    }

    /**
     * Path dasar route Rencana Aksi (URL bersih, tanpa kata "bupati"/"renaksi_pk"):
     * - bupati             -> adminkab/target_renaksi
     * - es3 (admin_opd)    -> adminopd/target_renaksi
     * - es3 (admin_kab)    -> adminkab/renaksi_pk/es3 (pantau lintas OPD, tetap)
     */
    private function renaksiUrl(string $jenis): string
    {
        $norm = $this->normJenis($jenis) ?? 'es3';
        if ($norm === 'bupati') {
            return 'adminkab/target_renaksi';
        }
        return $this->base($norm) === 'adminopd'
            ? 'adminopd/target_renaksi'
            : ($this->base($norm) . '/renaksi_pk/es3');
    }

    /** Path dasar route MONEV. bupati -> adminkab/monev; es3 admin_opd -> adminopd/monev; es3 admin_kab -> renaksi_pk style. */
    private function monevUrl(string $jenis): string
    {
        $norm = $this->normJenis($jenis) ?? 'es3';
        if ($norm === 'bupati') {
            return 'adminkab/monev';
        }
        return $this->base($norm) === 'adminopd'
            ? 'adminopd/monev'
            : ($this->base($norm) . '/monev_pk/es3');
    }

    /** Nilai filter eselon pada modul OPD/Kecamatan. */
    private const OPD_JENIS = ['jpt', 'administrator', 'pengawas'];

    /** Scope data PK OPD/Kecamatan. */
    private const OPD_SCOPE_JENIS = ['jpt', 'camat', 'administrator', 'pengawas'];

    /** Label eselon manusiawi dari pk.jenis/filter. */
    private function eselonLabel(string $pkJenis): string
    {
        $map = [
            'bupati'        => 'Bupati',
            'jpt'           => 'Eselon II',
            'camat'         => 'Eselon III',
            'kecamatan'     => 'Eselon III',
            'administrator' => 'Eselon III',
            'pengawas'      => 'Eselon IV',
        ];
        return $map[$pkJenis] ?? '-';
    }

    /** Normalisasi filter eselon -> 'jpt'|'administrator'|'pengawas' | null (semua). */
    private function normEselon($e): ?string
    {
        $e = strtolower(trim((string) $e));
        if (in_array($e, ['camat', 'kecamatan'], true)) {
            return 'administrator';
        }
        return in_array($e, self::OPD_JENIS, true) ? $e : null;
    }

    private function jenisScopeForEselon(?string $eselon): array
    {
        return match ($eselon) {
            'jpt'           => ['jpt'],
            'administrator' => ['administrator', 'camat'],
            'pengawas'      => ['pengawas'],
            default         => self::OPD_SCOPE_JENIS,
        };
    }

    private function resolvePkFilter(string $role, string $origJenis): ?string
    {
        $raw = $this->request->getGet('eselon');
        if ($raw !== null) {
            return $this->normEselon($raw);
        }

        $origJenis = strtolower(trim($origJenis));
        if (in_array($origJenis, ['camat', 'kecamatan'], true)) {
            return 'administrator';
        }
        if (in_array($origJenis, self::OPD_JENIS, true)) {
            return $origJenis;
        }
        if ($role === 'admin_kecamatan') {
            return 'administrator';
        }
        if ($role === 'admin_opd') {
            return 'jpt';
        }

        return null;
    }

    /**
     * Batasi builder pada jenis PK sesuai modul:
     * - bupati -> hanya 'bupati'
     * - es3    -> Eselon II/III/IV (Eselon III mencakup administrator + camat)
     */
    private function applyJenisScope($builder, string $jenis, ?string $eselon = null): void
    {
        if ($jenis === 'bupati') {
            $builder->where('pk.jenis', 'bupati');
        } else {
            $builder->whereIn('pk.jenis', $this->jenisScopeForEselon($eselon));
        }
    }

    /** Opsi dropdown pejabat (pk.pihak_1) untuk filter nama, di-scope per OPD. */
    private function pejabatOptions(?int $opdId, ?string $eselon = null): array
    {
        if (empty($opdId)) {
            return [];
        }
        $b = $this->db->table('pk')
            ->select('pk.pihak_1 AS id, peg.nama_pegawai AS nama, jab.nama_jabatan AS jabatan')
            ->join('pegawai peg', 'peg.id = pk.pihak_1', 'left')
            ->join('jabatan jab', 'jab.id = peg.jabatan_id', 'left')
            ->where('pk.opd_id', (int) $opdId)
            ->where('pk.pihak_1 IS NOT NULL', null, false);
        $b->where("(COALESCE(LOWER(jab.nama_jabatan), '') NOT LIKE '%bupati%' AND COALESCE(LOWER(peg.nama_pegawai), '') NOT LIKE '%bupati%')", null, false);
        $b->whereIn('pk.jenis', $this->jenisScopeForEselon($eselon));
        return $b->groupBy('pk.pihak_1, peg.nama_pegawai, jab.nama_jabatan')
            ->orderBy('jab.nama_jabatan', 'ASC')
            ->get()->getResultArray();
    }

    /** Daftar Perangkat Daerah (OPD) untuk dropdown penanggung jawab PK Bupati. */
    private function opdOptions(): array
    {
        return $this->db->table('opd')->select('id, nama_opd')
            ->whereNotIn('id', \App\Models\OpdModel::EXCLUDED_OPD_IDS)
            ->orderBy('nama_opd', 'ASC')
            ->get()->getResultArray();
    }

    /**
     * Peta OTOMATIS Penanggung Jawab Perangkat Daerah untuk PK Bupati (best-effort):
     * teks sasaran RPJMD (dinormalisasi) => [ ['id'=>opd_id,'nama'=>nama_opd], ... ],
     * ditarik dari rantai Renstra (renstra_tujuan.rpjmd_sasaran_id -> rpjmd_sasaran;
     * OPD dari renstra_sasaran.opd_id). Dicocokkan dgn teks sasaran PK Bupati di view.
     */
    private function autoPdBySasaran(): array
    {
        $norm = static fn($s) => strtolower(trim(preg_replace('/\s+/', ' ', (string) $s)));

        $rows = $this->db->table('renstra_tujuan rt')
            ->select('rsp.id AS sasaran_id, rsp.sasaran_rpjmd, ro.opd_id, o.nama_opd')
            ->join('renstra_sasaran ro', 'ro.renstra_tujuan_id = rt.id', 'inner')
            ->join('rpjmd_sasaran rsp', 'rsp.id = rt.rpjmd_sasaran_id', 'inner')
            ->join('opd o', 'o.id = ro.opd_id', 'inner')
            ->where('rt.rpjmd_sasaran_id IS NOT NULL')
            ->groupBy('rsp.id, rsp.sasaran_rpjmd, ro.opd_id, o.nama_opd')
            ->orderBy('o.nama_opd', 'ASC')
            ->get()->getResultArray();

        $bySasId = []; // sasaran_id => [ ['id','nama'], ... ]
        $map     = []; // norm(teks) => [ ['id','nama'], ... ]  (kunci: teks SASARAN)
        foreach ($rows as $r) {
            $opd = ['id' => (int) $r['opd_id'], 'nama' => $r['nama_opd']];
            $bySasId[(int) $r['sasaran_id']][] = $opd;
            $map[$norm($r['sasaran_rpjmd'])][]  = $opd;
        }

        // Fallback: kunci juga per-INDIKATOR RPJMD -> OPD sasaran induknya
        // (mengatasi teks sasaran PK Bupati yang beda/typo, mis. "pemerintaha").
        foreach ($this->db->table('rpjmd_indikator_sasaran')
            ->select('sasaran_id, indikator_sasaran')->get()->getResultArray() as $ir) {
            $sid = (int) $ir['sasaran_id'];
            if (empty($bySasId[$sid])) { continue; }
            $key = $norm($ir['indikator_sasaran']);
            if ($key !== '' && !isset($map[$key])) { $map[$key] = $bySasId[$sid]; }
        }

        return $map;
    }

    /** Mapping MANUAL Perangkat Daerah pendukung per Sasaran PK: pk_sasaran_id => [ ['id','nama'], ... ]. */
    private function manualPdBySasaran(): array
    {
        if (!$this->db->tableExists('pk_sasaran_opd')) {
            return [];
        }
        $rows = $this->db->table('pk_sasaran_opd pso')
            ->select('pso.pk_sasaran_id, pso.opd_id, o.nama_opd')
            ->join('opd o', 'o.id = pso.opd_id', 'inner')
            ->orderBy('o.nama_opd', 'ASC')
            ->get()->getResultArray();
        $map = [];
        foreach ($rows as $r) {
            $map[(int) $r['pk_sasaran_id']][] = ['id' => (int) $r['opd_id'], 'nama' => $r['nama_opd']];
        }
        return $map;
    }

    /**
     * Saran OTOMATIS OPD untuk sebuah Sasaran PK (dipakai sbg prefill form kelola PD).
     * Meniru logika pencocokan di tampilan: cocokkan teks sasaran ke mapping cascading,
     * bila kosong fallback lewat teks indikator sasaran tsb.
     */
    private function autoOpdsForSasaran(int $pkSasaranId, string $sasaranText): array
    {
        $map  = $this->autoPdBySasaran();
        $norm = static fn($s) => strtolower(trim(preg_replace('/\s+/', ' ', (string) $s)));
        $opds = $map[$norm($sasaranText)] ?? [];
        if (empty($opds)) {
            $inds = $this->db->table('pk_indikator')->select('indikator')
                ->where('pk_sasaran_id', $pkSasaranId)->get()->getResultArray();
            foreach ($inds as $ir) {
                $k = $norm($ir['indikator']);
                if ($k !== '' && !empty($map[$k])) { $opds = $map[$k]; break; }
            }
        }
        return $opds;
    }

    /**
     * Prefix route. Bupati -> adminkab. Untuk es3 tergantung peran:
     * admin_kab memantau lintas OPD (read-only) tetap di rute /adminkab,
     * admin_opd mengelola PK OPD-nya sendiri di /adminopd.
     */
    private function base(string $jenis): string
    {
        $role = (string) session()->get('role');

        // Role bupati SELALU dilayani di area /bupati (read-only), termasuk
        // untuk jenis 'bupati' maupun 'es3', supaya tautan pada view tidak
        // pernah mengarah ke area administratif /adminkab.
        if ($role === 'bupati') {
            return 'bupati';
        }
        if ($jenis === 'bupati') {
            return 'adminkab';
        }
        // admin_kab & admin_inspektorat memantau lintas OPD lewat rute /adminkab (read-only);
        // admin_opd & admin_kecamatan mengelola PK-nya sendiri lewat /adminopd.
        return in_array($role, ['admin_kab', 'admin_inspektorat'], true) ? 'adminkab' : 'adminopd';
    }

    /** Teks bebas tag. */
    private function rxNumber(): string { return 'regex_match[/^[^<>]*$/]'; }
    private function rxText(): string   { return 'regex_match[/^[^<>]*$/]'; }

    /**
     * Pastikan role berhak untuk modul ini.
     * - bupati -> write: admin_kab; read: admin_kab + admin_inspektorat (evaluasi, read-only).
     * - es3    -> write: admin_opd + admin_kecamatan; read: + admin_kab + admin_inspektorat (lintas OPD).
     */
    private function ensureRole(string $jenis, bool $write): bool
    {
        $role = (string) session()->get('role');

        // Role bupati: BACA saja, untuk semua jenis. Tidak pernah lolos ke $write.
        if ($role === 'bupati') {
            return !$write;
        }

        if ($jenis === 'bupati') {
            if ($write) {
                return $role === 'admin_kab';
            }
            return in_array($role, ['admin_kab', 'admin_inspektorat'], true);
        }
        // es3
        if ($write) {
            return in_array($role, ['admin_opd', 'admin_kecamatan'], true);
        }
        return in_array($role, ['admin_opd', 'admin_kecamatan', 'admin_kab', 'admin_inspektorat'], true);
    }

    /**
     * Ambil konteks 1 indikator PK (untuk tambah renaksi):
     * pi -> pk_sasaran -> pk (jenis, opd_id, tahun) + indikator/satuan.
     */
    private function getIndikatorContext(int $pkIndikatorId, string $jenis): ?array
    {
        $b = $this->db->table('pk_indikator pi')
            ->select('
                pi.id        AS pk_indikator_id,
                pi.indikator AS indikator_sasaran,
                pi.target    AS indikator_target,
                pi.id_satuan AS satuan_id,
                s.satuan     AS satuan,
                pk.id        AS pk_id,
                pk.tahun     AS tahun,
                pk.opd_id    AS opd_id,
                pk.jenis     AS pk_jenis,
                pj.nama_pegawai AS pejabat_nama,
                jb.nama_jabatan AS pejabat_jabatan,
                jb.eselon AS pejabat_eselon,
                ps.sasaran   AS sasaran_renstra
            ')
            ->join('pk_sasaran ps', 'ps.id = pi.pk_sasaran_id', 'left')
            ->join('pk', 'pk.id = ps.pk_id', 'left')
            ->join('pegawai pj', 'pj.id = pk.pihak_1', 'left')
            ->join('jabatan jb', 'jb.id = pj.jabatan_id', 'left')
            ->join('satuan s', 's.id = pi.id_satuan', 'left')
            ->where('pi.id', $pkIndikatorId);
        $this->applyJenisScope($b, $jenis);
        if ($jenis !== 'bupati') {
            $b->where("(COALESCE(LOWER(jb.nama_jabatan), '') NOT LIKE '%bupati%' AND COALESCE(LOWER(pj.nama_pegawai), '') NOT LIKE '%bupati%')", null, false);
        }
        return $b->get()->getRowArray();
    }

    /* ===================== RENCANA AKSI: LIST ===================== */
    public function index($jenis)
    {
        $origJenis = strtolower(trim((string) $jenis));
        $jenis = $this->normJenis((string) $jenis);
        if (!$jenis) {
            return redirect()->to(base_url('/'))->with('error', 'Modul tidak dikenal.');
        }
        if (!$this->ensureRole($jenis, false)) {
            return redirect()->to(base_url('/'))->with('error', 'Tidak berhak mengakses halaman ini.');
        }

        $session = session();
        $role    = (string) $session->get('role');

        $tahun = trim((string) ($this->request->getGet('tahun') ?? ''));
        $tahun = ($tahun === '' || strtolower($tahun) === 'all') ? null : (string) (int) $tahun;

        $opdList     = [];
        $opdFilter   = null;
        $eselon      = null;            // filter eselon (jpt|administrator|pengawas) — modul es3
        $pejabatId   = null;           // filter nama pejabat (pk.pihak_1)
        $pejabatList = [];
        $tahunList   = $this->targets->getAvailableYearsPk('bupati');

        $opdMap = []; // nama_opd => id : untuk tautan "Perangkat Daerah" -> PK OPD/Kecamatan tsb
        $autoPd = []; // norm(sasaran) => [ ['id','nama'], ... ] : PJ Perangkat Daerah OTOMATIS via cascading
        $manualPd = []; // pk_sasaran_id => [ ['id','nama'], ... ] : override MANUAL (kolom Aksi)
        if ($jenis === 'bupati') {
            $rows     = $this->targets->getTargetListByPkBupati($tahun);
            $opdMap   = array_column($this->opdOptions(), 'id', 'nama_opd');
            $autoPd   = $this->autoPdBySasaran();
            $manualPd = $this->manualPdBySasaran();
        } else {
            // es3 -> PK OPD/Kecamatan: filter jenis PK & pejabat
            $eselon    = $this->resolvePkFilter($role, $origJenis);
            $pejabatId = (int) ($this->request->getGet('pejabat_id') ?? 0) ?: null;

            if (in_array($role, ['admin_opd', 'admin_kecamatan'], true)) {
                $opdFilter = (int) $session->get('opd_id');
            } else {
                // admin_kab / admin_inspektorat: bisa filter OPD, default semua (lintas OPD)
                $opdRaw    = $this->request->getGet('opd_id');
                $opdFilter = ($opdRaw === null || $opdRaw === '') ? null : (int) $opdRaw;
                $opdList = $this->db->table('opd')->select('id, nama_opd')
                    ->whereNotIn('id', \App\Models\OpdModel::EXCLUDED_OPD_IDS)->orderBy('nama_opd', 'ASC')
                    ->get()->getResultArray();
            }

            $rows        = $this->targets->getTargetListByPkOpd($tahun, $opdFilter, $eselon, $pejabatId);
            $pejabatList = $this->pejabatOptions($opdFilter, $eselon);
            $tahunList   = $this->targets->getAvailableYearsPkOpd($opdFilter);
        }

        // Program & anggaran diambil dari PK (pk_program -> program_pk), dan sub
        // rencana aksi dari target_sub_rencana. Keduanya relasi 1-ke-banyak, jadi
        // diambil terpisah supaya baris indikator tidak berlipat karena join.
        $programMap = $this->targets->getProgramPkByIndikator(array_column($rows, 'pk_indikator_id'));
        $subMap     = $this->targets->getSubRencanaByTargets(array_column($rows, 'target_id'));

        // Group per sasaran PK (pakai pk_sasaran_id agar sasaran milik pejabat
        // berbeda tidak tergabung walau teksnya kebetulan sama)
        $grouped = [];
        $withRenaksi = 0;
        foreach ($rows as $row) {
            $grouped[$row['pk_sasaran_id'] ?? '-'][] = $row;
            if (!empty($row['target_id'])) {
                $withRenaksi++;
            }
        }
        $summary = [
            'indikator'    => count($rows),
            'with_renaksi' => $withRenaksi,
            'belum'        => count($rows) - $withRenaksi,
        ];

        return view('adminOpd/pk_renaksi/index', [
            'programMap'   => $programMap,
            'subMap'       => $subMap,
            'opdMap'       => $opdMap,
            'autoPd'       => $autoPd,
            'manualPd'     => $manualPd,
            'jenis'        => $jenis,
            'base'        => $this->base($jenis),
            'role'        => $role,
            'canWrite'    => $this->ensureRole($jenis, true),
            'tahun'       => $tahun ?? 'all',
            'tahunList'   => $tahunList,
            'opdList'     => $opdList,
            'opdFilter'   => $opdFilter,
            'eselon'      => $eselon,
            'pejabatId'   => $pejabatId,
            'pejabatList' => $pejabatList,
            'grouped'     => $grouped,
            'summary'     => $summary,
        ]);
    }

    /* ===================== RENCANA AKSI: FORM TAMBAH ===================== */
    public function tambah($jenis)
    {
        $jenis = $this->normJenis((string) $jenis);
        if (!$jenis || !$this->ensureRole($jenis, true)) {
            return redirect()->to(base_url('/'))->with('error', 'Tidak berhak.');
        }

        $pi = (int) $this->request->getGet('pi'); // pk_indikator_id
        if ($pi <= 0) {
            return redirect()->to(base_url($this->renaksiUrl($jenis)))
                ->with('error', 'Parameter indikator tidak valid.');
        }

        $ctx = $this->getIndikatorContext($pi, $jenis);
        if (!$ctx) {
            return redirect()->to(base_url($this->renaksiUrl($jenis)))
                ->with('error', 'Indikator PK tidak ditemukan.');
        }

        // es3: indikator harus milik OPD sendiri
        if ($jenis === 'es3' && (int) $ctx['opd_id'] !== (int) session()->get('opd_id')) {
            return redirect()->to(base_url($this->renaksiUrl($jenis)))
                ->with('error', 'Indikator bukan milik OPD Anda.');
        }

        // Anti duplikat (1 renaksi per indikator per OPD)
        $existing = $this->targets->existsForPkIndikator($pi, (int) $ctx['opd_id']);
        if ($existing) {
            return redirect()->to(base_url($this->renaksiUrl($jenis) . '/edit/' . (int) $existing['id']))
                ->with('success', 'Rencana aksi sudah ada. Silakan edit.');
        }

        return view('adminOpd/pk_renaksi/form', [
            'jenis'      => $jenis,
            'base'       => $this->base($jenis),
            'mode'       => 'tambah',
            'ctx'        => $ctx,
            'detail'     => null,
            'opdList'    => ($jenis === 'bupati') ? $this->opdOptions() : [],
            'subRencana' => [],
            'skala'      => $this->skalaSatuan($ctx),
            'programPk'  => $this->targets->getProgramPkByIndikator([$pi])[$pi] ?? [],
        ]);
    }

    /* ===================== RENCANA AKSI: SIMPAN ===================== */
    public function save($jenis)
    {
        $jenis = $this->normJenis((string) $jenis);
        if (!$jenis || !$this->ensureRole($jenis, true)) {
            return redirect()->to(base_url('/'))->with('error', 'Tidak berhak.');
        }

        $rxT = $this->rxText();
        $rules = [
            'pk_indikator_id'   => 'required|integer',
            'rencana_aksi'      => 'required|string|max_length[10000]|' . $rxT,
            'penanggung_jawab'  => 'permit_empty|string|max_length[255]|' . $rxT,
        ];
        if (!$this->validate($rules, $this->triwulanMessages())) {
            return redirect()->back()->withInput()
                ->with('error', implode(' ', $this->validator->getErrors()));
        }

        $pi  = (int) $this->request->getPost('pk_indikator_id');
        $ctx = $this->getIndikatorContext($pi, $jenis);
        if (!$ctx) {
            return redirect()->to(base_url($this->renaksiUrl($jenis)))
                ->with('error', 'Indikator PK tidak valid.');
        }
        if ($jenis === 'es3' && (int) $ctx['opd_id'] !== (int) session()->get('opd_id')) {
            return redirect()->to(base_url($this->renaksiUrl($jenis)))
                ->with('error', 'Indikator bukan milik OPD Anda.');
        }
        if ($this->targets->existsForPkIndikator($pi, (int) $ctx['opd_id'])) {
            return redirect()->to(base_url($this->renaksiUrl($jenis)))
                ->with('error', 'Rencana aksi sudah ada untuk indikator ini.');
        }

        $subRencana = $this->bacaSubRencana();
        if ($subRencana === false) {
            return redirect()->back()->withInput()
                ->with('error', 'Sub rencana aksi mengandung karakter yang tidak diizinkan.');
        }

        $newId = $this->targets->insert([
            'opd_id'            => (int) $ctx['opd_id'],
            'pk_indikator_id'   => $pi,
            'rencana_aksi'      => $this->request->getPost('rencana_aksi'),
            // target_triwulan_* tingkat indikator sengaja TIDAK ditulis lagi:
            // targetnya kini per Sub Rencana Aksi (target_sub_rencana). Kolomnya
            // dibiarkan apa adanya supaya nilai lama tidak tertimpa null.
            'penanggung_jawab'  => $this->request->getPost('penanggung_jawab'),
        ], true);

        if ($newId) {
            $this->targets->saveSubRencana((int) $newId, $subRencana);
        }

        return redirect()->to(base_url($this->renaksiUrl($jenis)))
            ->with('success', 'Rencana aksi berhasil ditambahkan.');
    }

    /* ===================== RENCANA AKSI: FORM EDIT ===================== */
    public function edit($jenis, $id)
    {
        $jenis = $this->normJenis((string) $jenis);
        if (!$jenis || !$this->ensureRole($jenis, true)) {
            return redirect()->to(base_url('/'))->with('error', 'Tidak berhak.');
        }

        $detail = $this->getRenaksiDetail((int) $id, $jenis);
        if (!$detail) {
            return redirect()->to(base_url($this->renaksiUrl($jenis)))
                ->with('error', 'Data tidak ditemukan.');
        }
        if ($jenis === 'es3' && (int) $detail['opd_id'] !== (int) session()->get('opd_id')) {
            return redirect()->to(base_url($this->renaksiUrl($jenis)))
                ->with('error', 'Data bukan milik OPD Anda.');
        }

        $pkIndikatorId = (int) ($detail['pk_indikator_id'] ?? 0);

        return view('adminOpd/pk_renaksi/form', [
            'jenis'      => $jenis,
            'base'       => $this->base($jenis),
            'mode'       => 'edit',
            'ctx'        => $detail,
            'detail'     => $detail,
            'opdList'    => ($jenis === 'bupati') ? $this->opdOptions() : [],
            'subRencana' => $this->targets->getSubRencanaByTarget((int) $id),
            'skala'      => $this->skalaSatuan($detail),
            'programPk'  => $pkIndikatorId > 0
                ? ($this->targets->getProgramPkByIndikator([$pkIndikatorId])[$pkIndikatorId] ?? [])
                : [],
        ]);
    }

    /* ===================== RENCANA AKSI: UPDATE ===================== */
    public function update($jenis, $id)
    {
        $jenis = $this->normJenis((string) $jenis);
        if (!$jenis || !$this->ensureRole($jenis, true)) {
            return redirect()->to(base_url('/'))->with('error', 'Tidak berhak.');
        }

        $id  = (int) $id;
        $row = $this->targets->find($id);
        if (!$row || empty($row['pk_indikator_id'])) {
            return redirect()->to(base_url($this->renaksiUrl($jenis)))
                ->with('error', 'Data tidak ditemukan.');
        }
        if ($jenis === 'es3' && (int) $row['opd_id'] !== (int) session()->get('opd_id')) {
            return redirect()->to(base_url($this->renaksiUrl($jenis)))
                ->with('error', 'Data bukan milik OPD Anda.');
        }

        $rxT = $this->rxText();
        $rules = [
            'rencana_aksi'      => 'required|string|max_length[10000]|' . $rxT,
            'penanggung_jawab'  => 'permit_empty|string|max_length[255]|' . $rxT,
        ];
        if (!$this->validate($rules, $this->triwulanMessages())) {
            return redirect()->back()->withInput()
                ->with('error', implode(' ', $this->validator->getErrors()));
        }

        $subRencana = $this->bacaSubRencana();
        if ($subRencana === false) {
            return redirect()->back()->withInput()
                ->with('error', 'Sub rencana aksi mengandung karakter yang tidak diizinkan.');
        }

        $this->targets->update($id, [
            'rencana_aksi'      => $this->request->getPost('rencana_aksi'),
            // target_triwulan_* tingkat indikator sengaja TIDAK ditulis lagi:
            // targetnya kini per Sub Rencana Aksi (target_sub_rencana). Kolomnya
            // dibiarkan apa adanya supaya nilai lama tidak tertimpa null.
            'penanggung_jawab'  => $this->request->getPost('penanggung_jawab'),
        ]);

        $this->targets->saveSubRencana($id, $subRencana);
        // Capaian MONEV milik sub yang dihapus ikut dibersihkan (kolomnya tanpa FK).
        $this->monev->hapusCapaianSubYatim($id);

        return redirect()->to(base_url($this->renaksiUrl($jenis)))
            ->with('success', 'Rencana aksi berhasil diperbarui.');
    }

    /**
     * Baca sub rencana aksi dari POST.
     *
     * Form mengirimnya sebagai JSON pada field `sub_rencana_json`, berbentuk
     * { "<indeks butir rencana aksi>": [ {"teks": "...", "tw": ["I","II","III","IV"]}, ... ] }.
     * JSON dipakai (bukan input array bernama) supaya penomoran butir tetap benar
     * walau ada butir yang dihapus/disisipkan di tengah.
     *
     * Target triwulan sengaja TIDAK dipaksa cocok dengan skala predikat: untuk
     * satuan berpredikat, form sudah membatasinya lewat dropdown, sedangkan
     * data lama yang di luar skala tetap boleh tersimpan apa adanya. Kalau
     * targetnya tidak bisa dinilai, Capaian Total-nya yang melaporkan
     * (lihat calculateCapaianTotalPercentage()), bukan simpanan yang ditolak.
     *
     * @return array<int, array<int, array{teks: string, tw: array<int, string|null>}>>|false
     *         false bila ada teks yang tidak lolos filter
     */
    private function bacaSubRencana()
    {
        $raw = (string) ($this->request->getPost('sub_rencana_json') ?? '');
        if (trim($raw) === '') {
            return [];
        }

        $data = json_decode($raw, true);
        if (!is_array($data)) {
            return [];
        }

        // Pola yang sama dengan rxText() dipakai field rencana aksi.
        $aman = static fn(string $teks): bool => (bool) preg_match(
            '/^(?!.*<\s*script\b)(?!.*<\/\s*script\s*>)(?!.*javascript\s*:)(?!.*data\s*:\s*text\/html)(?!.*(?<!\w)on\w+\s*=)(?!.*<\?php)(?!.*<\?).*$/is',
            $teks
        );

        $hasil = [];
        foreach ($data as $indeksBaris => $daftar) {
            $indeksBaris = (int) $indeksBaris;
            if ($indeksBaris < 0 || !is_array($daftar)) {
                continue;
            }

            foreach ($daftar as $item) {
                // Terima bentuk ringkas (string) maupun lengkap (objek: id + teks + triwulan).
                $teks = is_array($item) ? ($item['teks'] ?? '') : $item;
                if (!is_scalar($teks)) {
                    continue;
                }

                $teks = trim((string) $teks);
                if ($teks === '') {
                    continue;
                }
                if (mb_strlen($teks) > 2000 || !$aman($teks)) {
                    return false;
                }

                $tw = [];
                $twInput = is_array($item) ? (array) ($item['tw'] ?? []) : [];
                foreach ([1, 2, 3, 4] as $q) {
                    // JSON dari form berindeks 0..3; terima juga kunci 1..4.
                    $nilai = $twInput[$q - 1] ?? ($twInput[$q] ?? null);
                    if (!is_scalar($nilai)) {
                        $tw[$q] = null;
                        continue;
                    }

                    $nilai = trim((string) $nilai);
                    if ($nilai === '') {
                        $tw[$q] = null;
                        continue;
                    }
                    if (mb_strlen($nilai) > 255 || !$aman($nilai)) {
                        return false;
                    }

                    $tw[$q] = $nilai;
                }

                // id sub dipertahankan supaya capaian MONEV yang menempel padanya
                // tidak putus setiap kali rencana aksi disunting.
                $idSub = is_array($item) ? (int) ($item['id'] ?? 0) : 0;

                $hasil[$indeksBaris][] = ['id' => max(0, $idSub), 'teks' => $teks, 'tw' => $tw];
            }
        }

        return $hasil;
    }

    /* =========== PERANGKAT DAERAH PENDUKUNG PK BUPATI: FORM KELOLA =========== */
    /**
     * Form pilih Perangkat Daerah pendukung untuk sebuah Sasaran PK Bupati.
     * Bila belum pernah diatur manual, checkbox di-prefill dgn saran otomatis (cascading).
     */
    public function kelolaPd($jenis, $pkSasaranId)
    {
        $jenis = $this->normJenis((string) $jenis);
        if ($jenis !== 'bupati' || !$this->ensureRole($jenis, true)) {
            return redirect()->to(base_url('/'))->with('error', 'Tidak berhak.');
        }
        $pkSasaranId = (int) $pkSasaranId;

        $sasaran = $this->db->table('pk_sasaran ps')
            ->select('ps.id, ps.sasaran, pk.tahun')
            ->join('pk', 'pk.id = ps.pk_id', 'left')
            ->where('ps.id', $pkSasaranId)
            ->where('pk.jenis', 'bupati')
            ->get()->getRowArray();
        if (!$sasaran) {
            return redirect()->to(base_url($this->renaksiUrl($jenis)))
                ->with('error', 'Sasaran PK Bupati tidak ditemukan.');
        }

        $manual   = $this->manualPdBySasaran()[$pkSasaranId] ?? [];
        $isManual = !empty($manual);
        $selectedIds = array_map(static fn($o) => (int) $o['id'], $manual);
        if (empty($selectedIds)) { // prefill saran otomatis (cascading) saat pertama kali
            $selectedIds = array_map(static fn($o) => (int) $o['id'],
                $this->autoOpdsForSasaran($pkSasaranId, (string) $sasaran['sasaran']));
        }

        return view('adminOpd/pk_renaksi/pd_form', [
            'jenis'       => $jenis,
            'base'        => $this->base($jenis),
            'sasaran'     => $sasaran,
            'opdList'     => $this->opdOptions(),
            'selectedIds' => $selectedIds,
            'isManual'    => $isManual,
        ]);
    }

    /* ========== PERANGKAT DAERAH PENDUKUNG PK BUPATI: SIMPAN ========== */
    public function savePd($jenis)
    {
        $jenis = $this->normJenis((string) $jenis);
        if ($jenis !== 'bupati' || !$this->ensureRole($jenis, true)) {
            return redirect()->to(base_url('/'))->with('error', 'Tidak berhak.');
        }
        if (!$this->db->tableExists('pk_sasaran_opd')) {
            return redirect()->to(base_url($this->renaksiUrl($jenis)))
                ->with('error', 'Tabel pk_sasaran_opd belum tersedia. Jalankan migrasi db/update_2026-07-02_pk_sasaran_opd.sql.');
        }

        $pkSasaranId = (int) $this->request->getPost('pk_sasaran_id');
        $ok = $this->db->table('pk_sasaran ps')->join('pk', 'pk.id = ps.pk_id', 'left')
            ->where('ps.id', $pkSasaranId)->where('pk.jenis', 'bupati')->countAllResults();
        if ($pkSasaranId <= 0 || !$ok) {
            return redirect()->to(base_url($this->renaksiUrl($jenis)))
                ->with('error', 'Sasaran PK Bupati tidak valid.');
        }

        $opdIds = $this->request->getPost('opd_ids');
        $opdIds = is_array($opdIds) ? $opdIds : [];
        $opdIds = array_values(array_unique(array_filter(array_map('intval', $opdIds), static fn($v) => $v > 0)));

        $tbl = $this->db->table('pk_sasaran_opd');
        $tbl->where('pk_sasaran_id', $pkSasaranId)->delete();
        if (!empty($opdIds)) {
            $tbl->insertBatch(array_map(static fn($id) => [
                'pk_sasaran_id' => $pkSasaranId,
                'opd_id'        => $id,
            ], $opdIds));
        }

        return redirect()->to(base_url($this->renaksiUrl($jenis)))
            ->with('success', 'Perangkat Daerah pendukung PK Bupati berhasil disimpan.');
    }

    /* ===================== MONEV: LIST (pantau realisasi) ===================== */
    public function monev($jenis)
    {
        $origJenis = strtolower(trim((string) $jenis));
        $jenis = $this->normJenis((string) $jenis);
        if (!$jenis) {
            return redirect()->to(base_url('/'))->with('error', 'Modul tidak dikenal.');
        }
        if (!$this->ensureRole($jenis, false)) {
            return redirect()->to(base_url('/'))->with('error', 'Tidak berhak.');
        }

        $session = session();
        $role    = (string) $session->get('role');

        $tahun = trim((string) ($this->request->getGet('tahun') ?? ''));
        $tahun = ($tahun === '' || strtolower($tahun) === 'all') ? null : (string) (int) $tahun;

        $opdList     = [];
        $opdFilter   = null;
        $eselon      = null;
        $pejabatId   = null;
        $pejabatList = [];
        $tahunList   = $this->monev->getAvailableYearsPk('bupati');

        $autoPd = []; // norm(sasaran) => [ ['id','nama'], ... ] : PJ Perangkat Daerah OTOMATIS (bupati)
        if ($jenis === 'bupati') {
            $rows   = $this->monev->getIndexDataPkBupati($tahun);
            $autoPd = $this->autoPdBySasaran();
        } else {
            $eselon    = $this->resolvePkFilter($role, $origJenis);
            $pejabatId = (int) ($this->request->getGet('pejabat_id') ?? 0) ?: null;

            if (in_array($role, ['admin_opd', 'admin_kecamatan'], true)) {
                $opdFilter = (int) $session->get('opd_id');
            } else {
                $opdRaw    = $this->request->getGet('opd_id');
                $opdFilter = ($opdRaw === null || $opdRaw === '') ? null : (int) $opdRaw;
                $opdList = $this->db->table('opd')->select('id, nama_opd')
                    ->whereNotIn('id', \App\Models\OpdModel::EXCLUDED_OPD_IDS)->orderBy('nama_opd', 'ASC')
                    ->get()->getResultArray();
            }

            $rows        = $this->monev->getIndexDataPkOpd($tahun, $opdFilter, $eselon, $pejabatId);
            $pejabatList = $this->pejabatOptions($opdFilter, $eselon);
            $tahunList   = $this->monev->getAvailableYearsPkOpd($opdFilter);
        }

        $grouped = [];
        foreach ($rows as $row) {
            $grouped[$row['pk_sasaran_id'] ?? '-'][] = $row;
        }

        // Capaian disimpan per SUB rencana aksi, jadi rekapnya dihitung dari
        // sana (bukan dari kolom hasil join yang hanya memuat capaian sub 0).
        $monevSub = $this->monev->getBySubForTargets(array_column($rows, 'target_id'));
        $summary  = $this->ringkasCapaian(count($rows), $monevSub);

        return view('adminOpd/pk_renaksi/monev', [
            // Target triwulan kini per SUB rencana aksi, dan capaiannya pun
            // disimpan per sub — keduanya diambil terpisah lalu dipetakan.
            'subMap'      => $this->targets->getSubRencanaByTargets(array_column($rows, 'target_id')),
            'monevSub'    => $monevSub,
            // Program & pagu anggaran ikut PK (sama dengan Target & Rencana Aksi),
            // realisasi anggarannya diinput sendiri di MONEV.
            'programMap'  => $this->targets->getProgramPkByIndikator(array_column($rows, 'pk_indikator_id')),
            'anggaranMap' => $this->monev->getAnggaranForTargets(array_column($rows, 'target_id')),
            'jenis'       => $jenis,
            'autoPd'      => $autoPd,
            'base'        => $this->base($jenis),
            'role'        => $role,
            'canWrite'    => $this->ensureRole($jenis, true),
            'tahun'       => $tahun ?? 'all',
            'tahunList'   => $tahunList,
            'opdList'     => $opdList,
            'opdFilter'   => $opdFilter,
            'eselon'      => $eselon,
            'pejabatId'   => $pejabatId,
            'pejabatList' => $pejabatList,
            'grouped'     => $grouped,
            'summary'     => $summary,
        ]);
    }

    /* ===================== MONEV: CETAK PDF ===================== */
    public function cetak($jenis)
    {
        ob_clean();
        ob_start();

        $origJenis = strtolower(trim((string) $jenis));
        $jenis = $this->normJenis((string) $jenis);
        if (!$jenis || !$this->ensureRole($jenis, false)) {
            return redirect()->to(base_url('/'))->with('error', 'Tidak berhak.');
        }

        $session = session();
        $role    = (string) $session->get('role');

        $tahun = trim((string) ($this->request->getGet('tahun') ?? ''));
        $tahun = ($tahun === '' || strtolower($tahun) === 'all') ? null : (string) (int) $tahun;

        $opdList     = [];
        $opdFilter   = null;
        $eselon      = null;
        $pejabatId   = null;
        $pejabatList = [];
        $tahunList   = $this->monev->getAvailableYearsPk('bupati');
        $autoPd      = [];
        $namaUnit    = 'Kabupaten Pringsewu';

        if ($jenis === 'bupati') {
            $rows = $this->monev->getIndexDataPkBupati($tahun);
            $autoPd = $this->autoPdBySasaran();
        } else {
            $eselon    = $this->resolvePkFilter($role, $origJenis);
            $pejabatId = (int) ($this->request->getGet('pejabat_id') ?? 0) ?: null;

            if (in_array($role, ['admin_opd', 'admin_kecamatan'], true)) {
                $opdFilter = (int) $session->get('opd_id');
            } else {
                $opdRaw = $this->request->getGet('opd_id');
                $opdFilter = ($opdRaw === null || $opdRaw === '') ? null : (int) $opdRaw;
                $opdList = $this->db->table('opd')->select('id, nama_opd')
                    ->whereNotIn('id', \App\Models\OpdModel::EXCLUDED_OPD_IDS)->orderBy('nama_opd', 'ASC')
                    ->get()->getResultArray();
            }

            $rows = $this->monev->getIndexDataPkOpd($tahun, $opdFilter, $eselon, $pejabatId);
            $pejabatList = $this->pejabatOptions($opdFilter, $eselon);
            $tahunList   = $this->monev->getAvailableYearsPkOpd($opdFilter);

            if ($opdFilter) {
                $opd = $this->db->table('opd')->select('nama_opd')->where('id', $opdFilter)->get()->getRowArray();
                $namaUnit = $opd['nama_opd'] ?? $namaUnit;
            } elseif (in_array($role, ['admin_opd', 'admin_kecamatan'], true)) {
                $opd = $this->db->table('opd')->select('nama_opd')->where('id', (int) $session->get('opd_id'))->get()->getRowArray();
                $namaUnit = $opd['nama_opd'] ?? $namaUnit;
            } else {
                $namaUnit = 'Seluruh OPD';
            }
        }

        $grouped = [];
        foreach ($rows as $row) {
            $grouped[$row['pk_sasaran_id'] ?? '-'][] = $row;
        }

        $monevSub = $this->monev->getBySubForTargets(array_column($rows, 'target_id'));
        $summary  = $this->ringkasCapaian(count($rows), $monevSub);

        $html = view('adminOpd/pk_renaksi/cetak', [
            // Target triwulan per SUB rencana aksi + capaiannya per sub
            'subMap'      => $this->targets->getSubRencanaByTargets(array_column($rows, 'target_id')),
            'monevSub'    => $monevSub,
            'programMap'  => $this->targets->getProgramPkByIndikator(array_column($rows, 'pk_indikator_id')),
            'anggaranMap' => $this->monev->getAnggaranForTargets(array_column($rows, 'target_id')),
            'jenis'       => $jenis,
            'grouped'     => $grouped,
            'tahun'       => $tahun ?? 'all',
            'tahunList'   => $tahunList,
            'eselon'      => $eselon,
            'opdFilter'   => $opdFilter,
            'opdList'     => $opdList,
            'pejabatId'   => $pejabatId,
            'pejabatList' => $pejabatList,
            'role'        => $role,
            'base'        => $this->base($jenis),
            'autoPd'      => $autoPd,
            'summary'     => $summary,
            'nama_opd'    => $namaUnit,
        ]);

        $mpdf = new \Mpdf\Mpdf([
            'mode'          => 'utf-8',
            'format'        => 'A4-L',
            'margin_left'   => 10,
            'margin_right'  => 10,
            'margin_top'    => 12,
            'margin_bottom' => 10,
            'margin_header' => 0,
            'margin_footer' => 0,
            'tempDir'       => sys_get_temp_dir(),
        ]);
        helper('setting');
        $mpdf->SetHTMLFooter(pdf_footer_aksara());
        pdf_watermark_aksara($mpdf);
        $mpdf->SetDisplayMode('fullpage');
        $mpdf->WriteHTML($html);
        $this->response->setHeader('Content-Type', 'application/pdf');
        $mpdf->Output('MONEV-PK-' . $jenis . '-' . ($tahun ?? 'semua') . '.pdf', 'I');
        exit;
    }

    public function cetakRenaksi($jenis)
    {
        ob_clean();
        ob_start();

        $origJenis = strtolower(trim((string) $jenis));
        $jenis = $this->normJenis((string) $jenis);
        if (!$jenis) {
            return redirect()->to(base_url('/'))->with('error', 'Modul tidak dikenal.');
        }
        if (!$this->ensureRole($jenis, false)) {
            return redirect()->to(base_url('/'))->with('error', 'Tidak berhak mengakses halaman ini.');
        }

        $session = session();
        $role    = (string) $session->get('role');

        $tahun = trim((string) ($this->request->getGet('tahun') ?? ''));
        $tahun = ($tahun === '' || strtolower($tahun) === 'all') ? null : (string) (int) $tahun;

        $opdList     = [];
        $opdFilter   = null;
        $eselon      = null;
        $pejabatId   = null;
        $pejabatList = [];
        $tahunList   = $this->targets->getAvailableYearsPk('bupati');

        $opdMap = [];
        $autoPd = [];
        $manualPd = [];
        $namaUnit = 'Kabupaten Pringsewu';

        if ($jenis === 'bupati') {
            $rows     = $this->targets->getTargetListByPkBupati($tahun);
            $opdMap   = array_column($this->opdOptions(), 'id', 'nama_opd');
            $autoPd   = $this->autoPdBySasaran();
            $manualPd = $this->manualPdBySasaran();
        } else {
            $eselon    = $this->resolvePkFilter($role, $origJenis);
            $pejabatId = (int) ($this->request->getGet('pejabat_id') ?? 0) ?: null;

            if (in_array($role, ['admin_opd', 'admin_kecamatan'], true)) {
                $opdFilter = (int) $session->get('opd_id');
            } else {
                $opdRaw    = $this->request->getGet('opd_id');
                $opdFilter = ($opdRaw === null || $opdRaw === '') ? null : (int) $opdRaw;
                $opdList = $this->db->table('opd')->select('id, nama_opd')
                    ->whereNotIn('id', \App\Models\OpdModel::EXCLUDED_OPD_IDS)->orderBy('nama_opd', 'ASC')
                    ->get()->getResultArray();
            }

            $rows        = $this->targets->getTargetListByPkOpd($tahun, $opdFilter, $eselon, $pejabatId);
            $pejabatList = $this->pejabatOptions($opdFilter, $eselon);
            $tahunList   = $this->targets->getAvailableYearsPkOpd($opdFilter);

            if ($opdFilter) {
                $opd = $this->db->table('opd')->select('nama_opd')->where('id', $opdFilter)->get()->getRowArray();
                $namaUnit = $opd['nama_opd'] ?? $namaUnit;
            } elseif (in_array($role, ['admin_opd', 'admin_kecamatan'], true)) {
                $opd = $this->db->table('opd')->select('nama_opd')->where('id', (int) $session->get('opd_id'))->get()->getRowArray();
                $namaUnit = $opd['nama_opd'] ?? $namaUnit;
            } else {
                $namaUnit = 'Seluruh OPD';
            }
        }

        $grouped = [];
        $withRenaksi = 0;
        foreach ($rows as $row) {
            $grouped[$row['pk_sasaran_id'] ?? '-'][] = $row;
            if (!empty($row['target_id'])) {
                $withRenaksi++;
            }
        }
        $summary = [
            'indikator'    => count($rows),
            'with_renaksi' => $withRenaksi,
            'belum'        => count($rows) - $withRenaksi,
        ];

        $html = view('adminOpd/pk_renaksi/target_rencana_aksi_cetak', [
            'programMap'   => $this->targets->getProgramPkByIndikator(array_column($rows, 'pk_indikator_id')),
            'subMap'       => $this->targets->getSubRencanaByTargets(array_column($rows, 'target_id')),
            'opdMap'       => $opdMap,
            'autoPd'       => $autoPd,
            'manualPd'     => $manualPd,
            'jenis'        => $jenis,
            'base'         => $this->base($jenis),
            'role'         => $role,
            'tahun'        => $tahun ?? 'all',
            'tahunList'    => $tahunList,
            'opdList'      => $opdList,
            'opdFilter'    => $opdFilter,
            'eselon'       => $eselon,
            'pejabatId'    => $pejabatId,
            'pejabatList'  => $pejabatList,
            'grouped'      => $grouped,
            'summary'      => $summary,
            'nama_opd'     => $namaUnit,
        ]);

        $mpdf = new \Mpdf\Mpdf([
            'mode'          => 'utf-8',
            'format'        => 'A4-L',
            'margin_left'   => 10,
            'margin_right'  => 10,
            'margin_top'    => 12,
            'margin_bottom' => 10,
            'margin_header' => 0,
            'margin_footer' => 0,
            'tempDir'       => sys_get_temp_dir(),
        ]);
        helper('setting');
        $mpdf->SetHTMLFooter(pdf_footer_aksara());
        pdf_watermark_aksara($mpdf);
        $mpdf->SetDisplayMode('fullpage');
        $mpdf->WriteHTML($html);

        $this->response->setHeader('Content-Type', 'application/pdf');
        $safeName = trim((string) $namaUnit) !== '' ? preg_replace('/[^A-Za-z0-9]+/', '-', $namaUnit) . '-' : '';
        $mpdf->Output('Target-Rencana-Aksi-' . $safeName . ($tahun ?? 'semua') . '.pdf', 'I');
        exit;
    }

    /**
     * Kartu ringkasan MONEV.
     *
     * `monev.total` sudah berupa PERSENTASE hasil hitungan server, jadi
     * rata-ratanya cukup dirata-rata langsung — tidak dibagi target lagi.
     *
     * @param int                                              $jumlahRenaksi baris indikator/renaksi yang tampil
     * @param array<int, array<int, array<string, mixed>>>     $monevSub      [target_id => [sub_id => baris monev]]
     *
     * @return array{renaksi: int, with_capaian: int, avg_pct: float|null}
     */
    private function ringkasCapaian(int $jumlahRenaksi, array $monevSub): array
    {
        $terisi = 0;
        $jumlah = 0.0;
        $n      = 0;

        foreach ($monevSub as $perSub) {
            foreach ($perSub as $cap) {
                $terisi++;
                $pct = capaianToFloat($cap['total'] ?? null);
                if ($pct !== null) {
                    $jumlah += $pct;
                    $n++;
                }
            }
        }

        return [
            'renaksi'      => $jumlahRenaksi,
            'with_capaian' => $terisi,
            'avg_pct'      => $n > 0 ? round($jumlah / $n, 1) : null,
        ];
    }

    /* ===================== MONEV: FORM INPUT CAPAIAN ===================== */
    public function monevForm($jenis, $targetId)
    {
        $jenis = $this->normJenis((string) $jenis);
        if (!$jenis || !$this->ensureRole($jenis, true)) {
            return redirect()->to(base_url('/'))->with('error', 'Tidak berhak.');
        }

        $detail = $this->getRenaksiDetail((int) $targetId, $jenis);
        if (!$detail) {
            return redirect()->to(base_url($this->monevUrl($jenis)))
                ->with('error', 'Rencana aksi tidak ditemukan.');
        }
        if ($jenis === 'es3' && (int) $detail['opd_id'] !== (int) session()->get('opd_id')) {
            return redirect()->to(base_url($this->monevUrl($jenis)))
                ->with('error', 'Data bukan milik OPD Anda.');
        }

        // monev.opd_id: bupati = NULL, es3 = target_rencana.opd_id
        $monevOpdId = ($jenis === 'bupati') ? null : (int) $detail['opd_id'];

        // Capaian diinput per SUB rencana aksi (?sub=<id>). Tanpa ?sub, form
        // jatuh ke capaian tingkat rencana aksi (bentuk lama, sub_id = 0).
        $subId = (int) ($this->request->getGet('sub') ?? 0);
        $sub   = $subId > 0 ? $this->cariSubRencana((int) $targetId, $subId) : null;
        if ($subId > 0 && $sub === null) {
            return redirect()->to(base_url($this->monevUrl($jenis)))
                ->with('error', 'Sub rencana aksi tidak ditemukan pada rencana aksi ini.');
        }

        $monevRow = $this->monev->findByTargetAndOpd((int) $targetId, $monevOpdId, $subId);
        $targets  = $this->targetTriwulan($detail, $sub);
        $skala    = $this->skalaSatuan($detail);

        // Pratinjau awal dihitung di server juga, supaya form edit menampilkan
        // angka yang benar bahkan sebelum/ tanpa JavaScript berjalan.
        $preview = calculateCapaianTotalPercentage(
            $monevRow['metode_perhitungan'] ?? null,
            $targets,
            [
                1 => $monevRow['capaian_triwulan_1'] ?? null,
                2 => $monevRow['capaian_triwulan_2'] ?? null,
                3 => $monevRow['capaian_triwulan_3'] ?? null,
                4 => $monevRow['capaian_triwulan_4'] ?? null,
            ],
            $skala
        );

        // Predikat tidak bisa diakumulasi (menjumlah opini BPK tak bermakna).
        $metodeList = capaianMetodeList();
        if ($skala !== []) {
            unset($metodeList['sum']);
        }

        return view('adminOpd/pk_renaksi/monev_form', [
            'jenis'      => $jenis,
            'base'       => $this->base($jenis),
            'detail'     => $detail,
            'monev'      => $monevRow,
            'sub'        => $sub,
            'targets'    => $targets,
            'skala'      => $skala,
            'metodeList' => $metodeList,
            'preview'    => $preview,
        ]);
    }

    /**
     * Target triwulan acuan sebuah baris capaian: dari SUB rencana aksi bila
     * capaiannya per sub, kalau tidak dari tingkat rencana aksi (bentuk lama).
     *
     * Selalu dibaca dari DB — target yang dikirim browser tidak pernah dipercaya.
     *
     * @return array<int, string|null> [1..4 => target]
     */
    private function targetTriwulan(array $detail, ?array $sub): array
    {
        $sumber = $sub ?? $detail;

        return [
            1 => $sumber['target_triwulan_1'] ?? null,
            2 => $sumber['target_triwulan_2'] ?? null,
            3 => $sumber['target_triwulan_3'] ?? null,
            4 => $sumber['target_triwulan_4'] ?? null,
        ];
    }

    /**
     * Cek bentuk nilai capaian menurut satuannya.
     *
     * - satuan angka   : wajib angka (boleh desimal titik/koma)
     * - satuan predikat: wajib salah satu kode skala
     *
     * $lama = baris monev tersimpan. Nilai yang SUDAH tersimpan tapi di luar
     * skala (data sebelum satuannya dijadikan predikat) tetap diterima apa
     * adanya — supaya membuka lalu menyimpan form tidak menghapus data lama;
     * Capaian Total-nya yang akan dilaporkan belum bisa dihitung.
     *
     * @param array<int, string|null>          $capaian
     * @param array<int, array<string, mixed>> $skala
     * @param array<string, mixed>             $lama
     *
     * @return string|null pesan kesalahan, null bila semua sah
     */
    private function cekBentukCapaian(array $capaian, array $skala, array $lama = []): ?string
    {
        $peta = capaianSkalaMap($skala);

        foreach ([1, 2, 3, 4] as $q) {
            $nilai = $capaian[$q] ?? null;
            if (!capaianTerisi($nilai)) {
                continue;
            }

            $tersimpan = $lama['capaian_triwulan_' . $q] ?? null;
            if (capaianTerisi($tersimpan) && (string) $tersimpan === (string) $nilai) {
                continue; // nilai lama, tidak diubah
            }

            if ($peta === []) {
                if (capaianToFloat($nilai) === null) {
                    return 'Capaian Triwulan ' . capaianRomawi($q) . ' harus berupa angka.';
                }
                continue;
            }

            if (capaianNilaiSkala($nilai, $peta) === null) {
                return 'Capaian Triwulan ' . capaianRomawi($q) . ' harus salah satu dari: '
                    . implode(', ', array_column($skala, 'kode')) . '.';
            }
        }

        return null;
    }

    /* ===================== MONEV: SIMPAN CAPAIAN ===================== */
    public function monevSave($jenis)
    {
        $jenis = $this->normJenis((string) $jenis);
        if (!$jenis || !$this->ensureRole($jenis, true)) {
            return redirect()->to(base_url('/'))->with('error', 'Tidak berhak.');
        }

        // Tahap 1: yang bisa divalidasi tanpa tahu indikatornya. Bentuk nilai
        // capaian baru bisa dicek setelah satuannya diketahui (angka vs predikat).
        // `total` sengaja TIDAK divalidasi/disimpan dari POST — selalu dihitung
        // ulang di server (lihat di bawah).
        $rules = [
            'target_rencana_id'  => 'required|integer',
            'metode_perhitungan' => 'required|in_list[' . implode(',', array_keys(capaianMetodeList())) . ']',
            'capaian_triwulan_1' => 'permit_empty|max_length[255]|' . $this->rxText(),
            'capaian_triwulan_2' => 'permit_empty|max_length[255]|' . $this->rxText(),
            'capaian_triwulan_3' => 'permit_empty|max_length[255]|' . $this->rxText(),
            'capaian_triwulan_4' => 'permit_empty|max_length[255]|' . $this->rxText(),
        ];
        $messages = [
            'metode_perhitungan' => [
                'required' => 'Metode perhitungan wajib dipilih.',
                'in_list'  => 'Metode perhitungan tidak dikenal.',
            ],
            'capaian_triwulan_1' => ['regex_match' => 'Capaian Triwulan I mengandung karakter yang tidak diizinkan.'],
            'capaian_triwulan_2' => ['regex_match' => 'Capaian Triwulan II mengandung karakter yang tidak diizinkan.'],
            'capaian_triwulan_3' => ['regex_match' => 'Capaian Triwulan III mengandung karakter yang tidak diizinkan.'],
            'capaian_triwulan_4' => ['regex_match' => 'Capaian Triwulan IV mengandung karakter yang tidak diizinkan.'],
        ];
        if (!$this->validate($rules, $messages)) {
            return redirect()->back()->withInput()
                ->with('error', implode(' ', $this->validator->getErrors()));
        }

        $targetId = (int) $this->request->getPost('target_rencana_id');
        $detail   = $this->getRenaksiDetail($targetId, $jenis);
        if (!$detail) {
            return redirect()->to(base_url($this->monevUrl($jenis)))
                ->with('error', 'Rencana aksi tidak ditemukan.');
        }
        if ($jenis === 'es3' && (int) $detail['opd_id'] !== (int) session()->get('opd_id')) {
            return redirect()->to(base_url($this->monevUrl($jenis)))
                ->with('error', 'Data bukan milik OPD Anda.');
        }

        $monevOpdId = ($jenis === 'bupati') ? null : (int) $detail['opd_id'];

        // Sub harus benar-benar milik rencana aksi ini (cegah tempel capaian
        // ke sub milik renaksi/OPD lain).
        $subId = (int) ($this->request->getPost('target_sub_rencana_id') ?? 0);
        $sub   = null;
        if ($subId > 0) {
            $sub = $this->cariSubRencana($targetId, $subId);
            if ($sub === null) {
                return redirect()->to(base_url($this->monevUrl($jenis)))
                    ->with('error', 'Sub rencana aksi tidak ditemukan pada rencana aksi ini.');
            }
        }

        $txt = fn ($v) => ($v === null || $v === '') ? null : trim((string) $v);
        $metode  = (string) $this->request->getPost('metode_perhitungan');
        $capaian = [
            1 => $txt($this->request->getPost('capaian_triwulan_1')),
            2 => $txt($this->request->getPost('capaian_triwulan_2')),
            3 => $txt($this->request->getPost('capaian_triwulan_3')),
            4 => $txt($this->request->getPost('capaian_triwulan_4')),
        ];

        // Tahap 2: bentuk nilai capaian, sekarang satuannya sudah diketahui.
        $skala = $this->skalaSatuan($detail);
        $lama  = $this->monev->findByTargetAndOpd($targetId, $monevOpdId, $subId) ?? [];
        if ($skala !== [] && $metode === 'sum') {
            return redirect()->back()->withInput()
                ->with('error', 'Metode Akumulasi / Jumlah tidak berlaku untuk satuan berpredikat.');
        }
        if (($salah = $this->cekBentukCapaian($capaian, $skala, $lama)) !== null) {
            return redirect()->back()->withInput()->with('error', $salah);
        }

        // Capaian Total dihitung ULANG di server dari target yang tersimpan di
        // DB. Nilai `total` yang dikirim browser sengaja diabaikan — form-nya
        // readonly, tapi POST-nya tetap bisa dimanipulasi.
        $hitung = calculateCapaianTotalPercentage($metode, $this->targetTriwulan($detail, $sub), $capaian, $skala);

        $payload = [
            'capaian_triwulan_1' => $capaian[1],
            'capaian_triwulan_2' => $capaian[2],
            'capaian_triwulan_3' => $capaian[3],
            'capaian_triwulan_4' => $capaian[4],
            'metode_perhitungan' => $metode,
            'total'              => $hitung['percentage'],
        ];

        $this->monev->upsertForTarget($targetId, $monevOpdId, $payload, $subId);

        // Capaian tetap tersimpan walau persentasenya belum bisa dihitung
        // (mis. target triwulannya masih berupa teks) — pengguna diberi tahu
        // apa yang harus dibereskan, bukan kehilangan data yang sudah diketik.
        if ($hitung['error'] !== null && $hitung['filled_quarters_count'] > 0) {
            return redirect()->to(base_url($this->monevUrl($jenis)))
                ->with('error', 'Capaian tersimpan, tetapi Capaian Total belum dapat dihitung: ' . $hitung['error']);
        }

        return redirect()->to(base_url($this->monevUrl($jenis)))
            ->with('success', 'Capaian berhasil disimpan.');
    }

    /* ============ MONEV: REALISASI ANGGARAN (form & simpan) ============ */

    /**
     * Form realisasi anggaran per triwulan untuk satu rencana aksi.
     * Pagu anggarannya read-only, ikut Perjanjian Kinerja.
     */
    public function monevAnggaranForm($jenis, $targetId)
    {
        $jenis = $this->normJenis((string) $jenis);
        if (!$jenis || !$this->ensureRole($jenis, true)) {
            return redirect()->to(base_url('/'))->with('error', 'Tidak berhak.');
        }

        $detail = $this->getRenaksiDetail((int) $targetId, $jenis);
        if (!$detail) {
            return redirect()->to(base_url($this->monevUrl($jenis)))
                ->with('error', 'Rencana aksi tidak ditemukan.');
        }
        if ($jenis === 'es3' && (int) $detail['opd_id'] !== (int) session()->get('opd_id')) {
            return redirect()->to(base_url($this->monevUrl($jenis)))
                ->with('error', 'Data bukan milik OPD Anda.');
        }

        $pkIndikatorId = (int) ($detail['pk_indikator_id'] ?? 0);

        return view('adminOpd/pk_renaksi/monev_anggaran_form', [
            'jenis'     => $jenis,
            'base'      => $this->base($jenis),
            'detail'    => $detail,
            'anggaran'  => $this->monev->findAnggaran((int) $targetId),
            'programPk' => $pkIndikatorId > 0
                ? ($this->targets->getProgramPkByIndikator([$pkIndikatorId])[$pkIndikatorId] ?? [])
                : [],
        ]);
    }

    /** Simpan realisasi anggaran per triwulan. */
    public function monevAnggaranSave($jenis)
    {
        $jenis = $this->normJenis((string) $jenis);
        if (!$jenis || !$this->ensureRole($jenis, true)) {
            return redirect()->to(base_url('/'))->with('error', 'Tidak berhak.');
        }

        $targetId = (int) ($this->request->getPost('target_rencana_id') ?? 0);
        $detail   = $this->getRenaksiDetail($targetId, $jenis);
        if (!$detail) {
            return redirect()->to(base_url($this->monevUrl($jenis)))
                ->with('error', 'Rencana aksi tidak ditemukan.');
        }
        if ($jenis === 'es3' && (int) $detail['opd_id'] !== (int) session()->get('opd_id')) {
            return redirect()->to(base_url($this->monevUrl($jenis)))
                ->with('error', 'Data bukan milik OPD Anda.');
        }

        $realisasi = [];
        foreach ([1, 2, 3, 4] as $q) {
            $nilai = $this->rupiahKeAngka($this->request->getPost('realisasi_triwulan_' . $q));
            if ($nilai === false) {
                return redirect()->back()->withInput()
                    ->with('error', 'Realisasi Triwulan ' . $q . ' harus berupa angka rupiah.');
            }
            $realisasi[$q] = $nilai;
        }

        $monevOpdId = ($jenis === 'bupati') ? null : (int) $detail['opd_id'];
        $this->monev->upsertAnggaran($targetId, $monevOpdId, $realisasi);

        return redirect()->to(base_url($this->monevUrl($jenis)))
            ->with('success', 'Realisasi anggaran berhasil disimpan.');
    }

    /**
     * Ubah input rupiah jadi angka.
     *
     * Menerima "1.500.000", "1500000", atau "1.500.000,00". Kosong -> null
     * (belum diisi, dibedakan dari 0). Mengembalikan false bila bukan angka.
     *
     * @return float|null|false
     */
    private function rupiahKeAngka($nilai)
    {
        if ($nilai === null) {
            return null;
        }

        $teks = trim((string) $nilai);
        if ($teks === '') {
            return null;
        }

        // Buang pemisah ribuan & simbol; koma desimal jadi titik.
        $teks = str_replace(['Rp', 'rp', ' ', '.'], '', $teks);
        $teks = str_replace(',', '.', $teks);

        if (!is_numeric($teks)) {
            return false;
        }

        $angka = (float) $teks;

        return $angka < 0 ? false : $angka;
    }

    /**
     * Ambil satu sub rencana aksi, dipastikan milik rencana aksi yang dimaksud.
     */
    private function cariSubRencana(int $targetRencanaId, int $subId): ?array
    {
        return $this->db->table('target_sub_rencana')
            ->where('id', $subId)
            ->where('target_rencana_id', $targetRencanaId)
            ->get()
            ->getRowArray() ?: null;
    }

    /* ===================== UTIL ===================== */

    /**
     * Detail 1 baris target_rencana berbasis PK (join indikator/sasaran/pk).
     * Mengembalikan null bila baris bukan renaksi PK dengan jenis yang sesuai.
     */
    private function getRenaksiDetail(int $id, string $jenis): ?array
    {
        $b = $this->db->table('target_rencana tr')
            ->select('
                tr.*,
                pi.indikator AS indikator_sasaran,
                pi.target    AS indikator_target,
                pi.id_satuan AS satuan_id,
                s.satuan     AS satuan,
                pk.tahun     AS indikator_tahun,
                pk.opd_id    AS pk_opd_id,
                pk.jenis     AS pk_jenis,
                ps.sasaran   AS sasaran_renstra,
                pj.nama_pegawai AS pejabat_nama,
                jb.nama_jabatan AS pejabat_jabatan,
                jb.eselon AS pejabat_eselon,
                o.nama_opd   AS nama_opd
            ')
            ->join('pk_indikator pi', 'pi.id = tr.pk_indikator_id', 'left')
            ->join('pk_sasaran ps', 'ps.id = pi.pk_sasaran_id', 'left')
            ->join('pk', 'pk.id = ps.pk_id', 'left')
            ->join('satuan s', 's.id = pi.id_satuan', 'left')
            ->join('pegawai pj', 'pj.id = pk.pihak_1', 'left')
            ->join('jabatan jb', 'jb.id = pj.jabatan_id', 'left')
            ->join('opd o', 'o.id = tr.opd_id', 'left')
            ->where('tr.id', $id)
            ->where('tr.pk_indikator_id IS NOT NULL', null, false);
        $this->applyJenisScope($b, $jenis);
        if ($jenis !== 'bupati') {
            $b->where("(COALESCE(LOWER(jb.nama_jabatan), '') NOT LIKE '%bupati%' AND COALESCE(LOWER(pj.nama_pegawai), '') NOT LIKE '%bupati%')", null, false);
        }
        return $b->get()->getRowArray();
    }

    private function triwulanMessages(): array
    {
        return [
            'rencana_aksi'      => ['regex_match' => 'Rencana aksi mengandung karakter yang tidak diizinkan.'],
            'penanggung_jawab'  => ['regex_match' => 'Penanggung jawab mengandung karakter yang tidak diizinkan.'],
            'target_triwulan_1' => ['regex_match' => 'Target Triwulan I mengandung karakter yang tidak diizinkan.'],
            'target_triwulan_2' => ['regex_match' => 'Target Triwulan II mengandung karakter yang tidak diizinkan.'],
            'target_triwulan_3' => ['regex_match' => 'Target Triwulan III mengandung karakter yang tidak diizinkan.'],
            'target_triwulan_4' => ['regex_match' => 'Target Triwulan IV mengandung karakter yang tidak diizinkan.'],
        ];
    }
}
