<?php

namespace App\Controllers\AdminOpd;

use App\Controllers\BaseController;
use App\Models\Opd\TargetModel;
use App\Models\Opd\MonevModel;
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
 *   'es3'    -> PK Administrator/Eselon III (pk.jenis='administrator'),
 *               input oleh admin_opd (per OPD), monev.opd_id = target_rencana.opd_id
 */
class PkRenaksiController extends BaseController
{
    protected TargetModel $targets;
    protected MonevModel $monev;
    protected $db;

    public function __construct()
    {
        $this->targets = new TargetModel();
        $this->monev   = new MonevModel();
        $this->db      = Database::connect();
    }

    /* ===================== HELPER KONTEKS ===================== */

    /** Normalisasi segmen jenis URL -> 'bupati' | 'es3'. 'kabupaten' = alias URL bersih utk bupati. */
    private function normJenis(string $jenis): ?string
    {
        $j = strtolower(trim($jenis));
        if ($j === 'kabupaten') {
            return 'bupati';
        }
        return in_array($j, ['bupati', 'es3'], true) ? $j : null;
    }

    /**
     * Path dasar route Rencana Aksi (URL bersih, tanpa kata "bupati"/"renaksi_pk"):
     * - bupati             -> adminkab/target_renaksi
     * - es3 (admin_opd)    -> adminopd/target_renaksi
     * - es3 (admin_kab)    -> adminkab/renaksi_pk/es3 (pantau lintas OPD, tetap)
     */
    private function renaksiUrl(string $jenis): string
    {
        if ($jenis === 'bupati') {
            return 'adminkab/target_renaksi';
        }
        return $this->base($jenis) === 'adminopd'
            ? 'adminopd/target_renaksi'
            : ($this->base($jenis) . '/renaksi_pk/' . $jenis);
    }

    /** Path dasar route MONEV. bupati -> adminkab/monev; es3 admin_opd -> adminopd/monev; es3 admin_kab -> renaksi_pk style. */
    private function monevUrl(string $jenis): string
    {
        if ($jenis === 'bupati') {
            return 'adminkab/monev';
        }
        return $this->base($jenis) === 'adminopd'
            ? 'adminopd/monev'
            : ($this->base($jenis) . '/monev_pk/' . $jenis);
    }

    /** Eselon level pada modul OPD (Eselon II/III/IV + Camat=Eselon III kecamatan). */
    private const OPD_JENIS = ['jpt', 'camat', 'administrator', 'pengawas'];

    /** Label eselon manusiawi dari pk.jenis. */
    private function eselonLabel(string $pkJenis): string
    {
        $map = [
            'bupati'        => 'Bupati',
            'jpt'           => 'Eselon II',
            'camat'         => 'Camat (Eselon III)',
            'administrator' => 'Eselon III',
            'pengawas'      => 'Eselon IV',
        ];
        return $map[$pkJenis] ?? '-';
    }

    /** Normalisasi filter eselon -> 'jpt'|'administrator'|'pengawas' | null (semua). */
    private function normEselon($e): ?string
    {
        $e = strtolower(trim((string) $e));
        return in_array($e, self::OPD_JENIS, true) ? $e : null;
    }

    /**
     * Batasi builder pada jenis PK sesuai modul:
     * - bupati -> hanya 'bupati'
     * - es3    -> Eselon II/III/IV (opsional dipersempit ke 1 eselon)
     */
    private function applyJenisScope($builder, string $jenis, ?string $eselon = null): void
    {
        if ($jenis === 'bupati') {
            $builder->where('pk.jenis', 'bupati');
        } elseif (!empty($eselon) && in_array($eselon, self::OPD_JENIS, true)) {
            $builder->where('pk.jenis', $eselon);
        } else {
            $builder->whereIn('pk.jenis', self::OPD_JENIS);
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
        if (!empty($eselon)) {
            $b->where('pk.jenis', $eselon);
        } else {
            $b->whereIn('pk.jenis', self::OPD_JENIS);
        }
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
        if ($jenis === 'bupati') {
            return 'adminkab';
        }
        // admin_kab & admin_inspektorat memantau lintas OPD lewat rute /adminkab (read-only);
        // admin_opd & admin_kecamatan mengelola PK-nya sendiri lewat /adminopd.
        $role = (string) session()->get('role');
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
                s.satuan     AS satuan,
                pk.id        AS pk_id,
                pk.tahun     AS tahun,
                pk.opd_id    AS opd_id,
                pk.jenis     AS pk_jenis,
                pj.nama_pegawai AS pejabat_nama,
                ps.sasaran   AS sasaran_renstra
            ')
            ->join('pk_sasaran ps', 'ps.id = pi.pk_sasaran_id', 'left')
            ->join('pk', 'pk.id = ps.pk_id', 'left')
            ->join('pegawai pj', 'pj.id = pk.pihak_1', 'left')
            ->join('satuan s', 's.id = pi.id_satuan', 'left')
            ->where('pi.id', $pkIndikatorId);
        $this->applyJenisScope($b, $jenis);
        return $b->get()->getRowArray();
    }

    /* ===================== RENCANA AKSI: LIST ===================== */
    public function index($jenis)
    {
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

        $opdMap = []; // nama_opd => id : untuk tautan "Perangkat Daerah" -> PK Eselon OPD tsb
        $autoPd = []; // norm(sasaran) => [ ['id','nama'], ... ] : PJ Perangkat Daerah OTOMATIS via cascading
        $manualPd = []; // pk_sasaran_id => [ ['id','nama'], ... ] : override MANUAL (kolom Aksi)
        if ($jenis === 'bupati') {
            $rows     = $this->targets->getTargetListByPkBupati($tahun);
            $opdMap   = array_column($this->opdOptions(), 'id', 'nama_opd');
            $autoPd   = $this->autoPdBySasaran();
            $manualPd = $this->manualPdBySasaran();
        } else {
            // es3 -> Eselon II/III/IV: filter eselon & pejabat
            $eselon    = $this->normEselon($this->request->getGet('eselon'));
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
            'jenis'   => $jenis,
            'base'    => $this->base($jenis),
            'mode'    => 'tambah',
            'ctx'     => $ctx,
            'detail'  => null,
            'opdList' => ($jenis === 'bupati') ? $this->opdOptions() : [],
        ]);
    }

    /* ===================== RENCANA AKSI: SIMPAN ===================== */
    public function save($jenis)
    {
        $jenis = $this->normJenis((string) $jenis);
        if (!$jenis || !$this->ensureRole($jenis, true)) {
            return redirect()->to(base_url('/'))->with('error', 'Tidak berhak.');
        }

        $rxN = $this->rxNumber();
        $rxT = $this->rxText();
        $rules = [
            'pk_indikator_id'   => 'required|integer',
            'rencana_aksi'      => 'required|string|max_length[10000]|' . $rxT,
            'penanggung_jawab'  => 'permit_empty|string|max_length[255]|' . $rxT,
            'capaian'           => 'permit_empty|' . $rxN,
            'target_triwulan_1' => 'permit_empty|' . $rxN,
            'target_triwulan_2' => 'permit_empty|' . $rxN,
            'target_triwulan_3' => 'permit_empty|' . $rxN,
            'target_triwulan_4' => 'permit_empty|' . $rxN,
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

        $this->targets->insert([
            'opd_id'            => (int) $ctx['opd_id'],
            'pk_indikator_id'   => $pi,
            'rencana_aksi'      => $this->request->getPost('rencana_aksi'),
            'capaian'           => $this->request->getPost('capaian'),
            'target_triwulan_1' => $this->request->getPost('target_triwulan_1'),
            'target_triwulan_2' => $this->request->getPost('target_triwulan_2'),
            'target_triwulan_3' => $this->request->getPost('target_triwulan_3'),
            'target_triwulan_4' => $this->request->getPost('target_triwulan_4'),
            'penanggung_jawab'  => $this->request->getPost('penanggung_jawab'),
        ]);

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

        return view('adminOpd/pk_renaksi/form', [
            'jenis'   => $jenis,
            'base'    => $this->base($jenis),
            'mode'    => 'edit',
            'ctx'     => $detail,
            'detail'  => $detail,
            'opdList' => ($jenis === 'bupati') ? $this->opdOptions() : [],
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

        $rxN = $this->rxNumber();
        $rxT = $this->rxText();
        $rules = [
            'rencana_aksi'      => 'required|string|max_length[10000]|' . $rxT,
            'penanggung_jawab'  => 'permit_empty|string|max_length[255]|' . $rxT,
            'capaian'           => 'permit_empty|' . $rxN,
            'target_triwulan_1' => 'permit_empty|' . $rxN,
            'target_triwulan_2' => 'permit_empty|' . $rxN,
            'target_triwulan_3' => 'permit_empty|' . $rxN,
            'target_triwulan_4' => 'permit_empty|' . $rxN,
        ];
        if (!$this->validate($rules, $this->triwulanMessages())) {
            return redirect()->back()->withInput()
                ->with('error', implode(' ', $this->validator->getErrors()));
        }

        $this->targets->update($id, [
            'rencana_aksi'      => $this->request->getPost('rencana_aksi'),
            'capaian'           => $this->request->getPost('capaian'),
            'target_triwulan_1' => $this->request->getPost('target_triwulan_1'),
            'target_triwulan_2' => $this->request->getPost('target_triwulan_2'),
            'target_triwulan_3' => $this->request->getPost('target_triwulan_3'),
            'target_triwulan_4' => $this->request->getPost('target_triwulan_4'),
            'penanggung_jawab'  => $this->request->getPost('penanggung_jawab'),
        ]);

        return redirect()->to(base_url($this->renaksiUrl($jenis)))
            ->with('success', 'Rencana aksi berhasil diperbarui.');
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
            $eselon    = $this->normEselon($this->request->getGet('eselon'));
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
        $withCapaian = 0;
        $pctSum = 0.0;
        $pctN   = 0;
        foreach ($rows as $row) {
            $grouped[$row['pk_sasaran_id'] ?? '-'][] = $row;
            if (!empty($row['monev_id'])) {
                $withCapaian++;
            }
            $target = $this->pkNum($row['indikator_target'] ?? null);
            $total  = $this->pkNum($row['monev_total'] ?? null);
            if ($target && $total !== null) {
                $pctSum += ($total / $target * 100);
                $pctN++;
            }
        }
        $summary = [
            'renaksi'      => count($rows),
            'with_capaian' => $withCapaian,
            'avg_pct'      => $pctN > 0 ? round($pctSum / $pctN, 1) : null,
        ];

        return view('adminOpd/pk_renaksi/monev', [
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
        $jenis = $this->normJenis((string) $jenis);
        if (!$jenis || !$this->ensureRole($jenis, false)) {
            return redirect()->to(base_url('/'))->with('error', 'Tidak berhak.');
        }

        $session = session();
        $role    = (string) $session->get('role');

        $tahun = trim((string) ($this->request->getGet('tahun') ?? ''));
        $tahun = ($tahun === '' || strtolower($tahun) === 'all') ? null : (string) (int) $tahun;

        $namaUnit = 'Kabupaten Pringsewu';
        $eselon   = null;
        if ($jenis === 'bupati') {
            $rows = $this->monev->getIndexDataPkBupati($tahun);
        } else {
            $eselon    = $this->normEselon($this->request->getGet('eselon'));
            $pejabatId = (int) ($this->request->getGet('pejabat_id') ?? 0) ?: null;
            if (in_array($role, ['admin_opd', 'admin_kecamatan'], true)) {
                $opdId = (int) $session->get('opd_id');
            } else {
                $opdRaw = $this->request->getGet('opd_id');
                $opdId  = ($opdRaw === null || $opdRaw === '') ? null : (int) $opdRaw;
            }
            $rows = $this->monev->getIndexDataPkOpd($tahun, $opdId, $eselon, $pejabatId);
            if ($opdId) {
                $opd = $this->db->table('opd')->select('nama_opd')->where('id', $opdId)->get()->getRowArray();
                $namaUnit = $opd['nama_opd'] ?? $namaUnit;
            } else {
                $namaUnit = 'Seluruh OPD';
            }
        }

        $grouped = [];
        foreach ($rows as $row) {
            $grouped[$row['pk_sasaran_id'] ?? '-'][] = $row;
        }

        $html = view('adminOpd/pk_renaksi/cetak', [
            'jenis'    => $jenis,
            'grouped'  => $grouped,
            'tahun'    => $tahun,
            'eselon'   => $eselon,
            'namaUnit' => $namaUnit,
            'logo_url' => FCPATH . 'assets/images/logo.png',
        ]);

        $mpdf = new \Mpdf\Mpdf([
            'mode'              => 'utf-8',
            'format'            => 'FOLIO-L',
            'default_font_size' => 10,
            'tempDir'           => sys_get_temp_dir(),
        ]);
        helper('setting');
        $mpdf->SetHTMLFooter(pdf_footer_aksara());
        pdf_watermark_aksara($mpdf); // watermark AKSARA halus di latar
        $mpdf->WriteHTML($html);
        $this->response->setHeader('Content-Type', 'application/pdf');
        $mpdf->Output('MONEV-PK-' . $jenis . '-' . ($tahun ?? 'semua') . '.pdf', 'I');
        exit;
    }

    /** Angka Indonesia -> float (null bila kosong/non-numerik). */
    private function pkNum($v): ?float
    {
        if ($v === null || $v === '') {
            return null;
        }
        $v = str_replace(',', '.', (string) $v);
        return is_numeric($v) ? (float) $v : null;
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
        $monevRow   = $this->monev->findByTargetAndOpd((int) $targetId, $monevOpdId);

        return view('adminOpd/pk_renaksi/monev_form', [
            'jenis'  => $jenis,
            'base'   => $this->base($jenis),
            'detail' => $detail,
            'monev'  => $monevRow,
        ]);
    }

    /* ===================== MONEV: SIMPAN CAPAIAN ===================== */
    public function monevSave($jenis)
    {
        $jenis = $this->normJenis((string) $jenis);
        if (!$jenis || !$this->ensureRole($jenis, true)) {
            return redirect()->to(base_url('/'))->with('error', 'Tidak berhak.');
        }

        $rxN = $this->rxText();
        $rules = [
            'target_rencana_id'  => 'required|integer',
            'capaian_triwulan_1' => 'permit_empty|' . $rxN,
            'capaian_triwulan_2' => 'permit_empty|' . $rxN,
            'capaian_triwulan_3' => 'permit_empty|' . $rxN,
            'capaian_triwulan_4' => 'permit_empty|' . $rxN,
            'total'              => 'permit_empty|' . $rxN,
        ];
        if (!$this->validate($rules)) {
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

        $cleanStr = fn ($v) => ($v === null || $v === '') ? null : trim((string) $v);
        $payload = [
            'capaian_triwulan_1' => $cleanStr($this->request->getPost('capaian_triwulan_1')),
            'capaian_triwulan_2' => $cleanStr($this->request->getPost('capaian_triwulan_2')),
            'capaian_triwulan_3' => $cleanStr($this->request->getPost('capaian_triwulan_3')),
            'capaian_triwulan_4' => $cleanStr($this->request->getPost('capaian_triwulan_4')),
            'total'              => $cleanStr($this->request->getPost('total')),
        ];

        $this->monev->upsertForTarget($targetId, $monevOpdId, $payload);

        return redirect()->to(base_url($this->monevUrl($jenis)))
            ->with('success', 'Capaian berhasil disimpan.');
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
                s.satuan     AS satuan,
                pk.tahun     AS indikator_tahun,
                pk.opd_id    AS pk_opd_id,
                pk.jenis     AS pk_jenis,
                ps.sasaran   AS sasaran_renstra,
                pj.nama_pegawai AS pejabat_nama,
                o.nama_opd   AS nama_opd
            ')
            ->join('pk_indikator pi', 'pi.id = tr.pk_indikator_id', 'left')
            ->join('pk_sasaran ps', 'ps.id = pi.pk_sasaran_id', 'left')
            ->join('pk', 'pk.id = ps.pk_id', 'left')
            ->join('satuan s', 's.id = pi.id_satuan', 'left')
            ->join('pegawai pj', 'pj.id = pk.pihak_1', 'left')
            ->join('opd o', 'o.id = tr.opd_id', 'left')
            ->where('tr.id', $id)
            ->where('tr.pk_indikator_id IS NOT NULL', null, false);
        $this->applyJenisScope($b, $jenis);
        return $b->get()->getRowArray();
    }

    private function triwulanMessages(): array
    {
        return [
            'rencana_aksi'      => ['regex_match' => 'Rencana aksi mengandung karakter yang tidak diizinkan.'],
            'penanggung_jawab'  => ['regex_match' => 'Penanggung jawab mengandung karakter yang tidak diizinkan.'],
            'capaian'           => ['regex_match' => 'Baseline mengandung karakter yang tidak diizinkan.'],
            'target_triwulan_1' => ['regex_match' => 'Target Triwulan I mengandung karakter yang tidak diizinkan.'],
            'target_triwulan_2' => ['regex_match' => 'Target Triwulan II mengandung karakter yang tidak diizinkan.'],
            'target_triwulan_3' => ['regex_match' => 'Target Triwulan III mengandung karakter yang tidak diizinkan.'],
            'target_triwulan_4' => ['regex_match' => 'Target Triwulan IV mengandung karakter yang tidak diizinkan.'],
        ];
    }
}
