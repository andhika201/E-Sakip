<?php

namespace App\Controllers\AdminOpd;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;
use App\Models\Opd\RenstraModel;
use App\Models\Opd\RenjaModel;
use App\Models\RpjmdModel;
use App\Models\OpdModel;
use App\Models\PkModel;
use App\Models\RktModel;
use App\Models\ProgramPkModel;
use Config\Database;

class RktController extends BaseController
{
    protected $renstraModel;
    protected $opdModel;
    protected $rktModel;
    protected $programPkModel;
    protected $pkModel;
    protected $db;

    public function __construct()
    {
        $this->renstraModel = new RenstraModel();
        $this->opdModel = new OpdModel();
        $this->rktModel = new RktModel();
        $this->programPkModel = new ProgramPkModel();
        $this->pkModel = new PkModel();
        $this->db = \Config\Database::connect();
    }

    /**
     * INDEX RKT
     * route: GET adminopd/rkt
     */
    public function index()
    {
        $db = Database::connect();
        $role = session()->get('role');
        $opdId = session()->get('opd_id');

        // ------------ FILTER ------------
        $filterSasaran = $this->request->getGet('sasaran') ?? 'all';   // indikator_id
        $filterTahun = $this->request->getGet('tahun') ?? 'all';   // 'all' | tahun
        $filterStatus = $this->request->getGet('status') ?? 'all';   // 'all' | draft | selesai

        // OPD aktif
        $currentOpd = $db->table('opd')
            ->where('id', $opdId)
            ->get()
            ->getRowArray();

        // ------------ SUMBER RENSTRA: BERJALAN ATAU SEBUAH VERSI ------------
        //
        // Mengikuti pola LAKIP: operator memilih versi Renstra mana yang
        // dibaca, dan seluruh halaman — tabel maupun filter indikator —
        // mengikuti pilihan itu. Tanpa parameter, yang dibaca Renstra
        // berjalan, persis perilaku sebelumnya.
        $versiTersedia = $this->versiRenstraUntukRkt($db, (int) $opdId);
        $versiDiminta  = $this->request->getGet('renstra_versi');
        $versiDipilih  = $this->versiRenstraDipilih($versiDiminta, $versiTersedia);

        // Id yang DIKIRIM tetapi tidak sah tidak boleh diam-diam berubah
        // menjadi "berjalan": operator akan mengira ia sedang melihat versi
        // yang ia pilih.
        $versiDiabaikan = $versiDipilih === null
            && $versiDiminta !== null
            && trim((string) $versiDiminta) !== ''
            && (int) $versiDiminta > 0;

        [$indikators, $targetYearsByInd] = $this->sumberRenstraRkt($db, (int) $opdId, $versiDipilih);

        // ------------ TAHUN YANG TERSEDIA UNTUK DROPDOWN ------------
        $availableYears = [];

        foreach ($targetYearsByInd as $id => $years) {
            $years = array_values(array_unique($years));
            sort($years);
            $targetYearsByInd[$id] = $years;
            $availableYears = array_merge($availableYears, $years);
        }

        $availableYears = array_values(array_unique($availableYears));
        sort($availableYears);

        // kalau query tahun kosong, pakai 'all'
        if ($filterTahun === '' || $filterTahun === null) {
            $filterTahun = 'all';
        }

        // ------------ SUSUN DATA RKT NESTED ------------
        $rktdata = [];

        foreach ($indikators as $ind) {
            $indikatorId = $ind['id'];

            // filter indikator (dropdown "Indikator Sasaran Renstra")
            if ($filterSasaran !== 'all' && (string) $filterSasaran !== (string) $indikatorId) {
                continue;
            }

            // ------------ AMBIL RKT (PROGRAM) UNTUK INDIKATOR INI ------------
            $builderRkt = $db->table('rkt r')
                ->select('r.*, p.program_kegiatan AS program_nama')
                ->join('program_pk p', 'p.id = r.program_id', 'left')
                ->where('r.indikator_id', $indikatorId)
                ->where('r.opd_id', $opdId);

            // filter tahun hanya kalau bukan "SEMUA TAHUN"
            if ($filterTahun !== 'all') {
                $builderRkt->where('r.tahun', $filterTahun);
            }

            // filter status kalau dipilih
            if ($filterStatus !== 'all') {
                $builderRkt->where('r.status', $filterStatus);
            }

            $rkts = $builderRkt
                ->orderBy('r.id', 'ASC')
                ->get()
                ->getResultArray();

            // ------------ NESTED: PROGRAM -> KEGIATAN -> SUB KEGIATAN ------------
            foreach ($rkts as &$rkt) {
                // KEGIATAN
                $kegiatans = $db->table('rkt_kegiatan rk')
                    ->select('rk.id, rk.kegiatan_id, k.kegiatan')
                    ->join('kegiatan_pk k', 'k.id = rk.kegiatan_id', 'left')
                    ->where('rk.rkt_id', $rkt['id'])
                    ->orderBy('rk.id', 'ASC')
                    ->get()
                    ->getResultArray();

                foreach ($kegiatans as &$keg) {
                    // SUB KEGIATAN (anggaran diambil dari sub_kegiatan_pk)
                    $subs = $db->table('rkt_subkegiatan rs')
                        ->select('rs.id, rs.sub_kegiatan_id, rs.indikator_sasaran_sub_kegiatan, rs.target, sk.sub_kegiatan, sk.anggaran')
                        ->join('sub_kegiatan_pk sk', 'sk.id = rs.sub_kegiatan_id', 'left')
                        ->where('rs.rkt_kegiatan_id', $keg['id'])
                        ->orderBy('rs.id', 'ASC')
                        ->get()
                        ->getResultArray();

                    $keg['subkegiatan'] = $subs ?: [];
                }
                unset($keg);

                $rkt['kegiatan'] = $kegiatans ?: [];
            }
            unset($rkt);

            // simpan ke array utama
            $ind['rkts'] = $rkts;
            $ind['target_years'] = $targetYearsByInd[$indikatorId] ?? [];

            $rktdata[] = $ind;
        }

        // ------------ DATA UNTUK FILTER DROPDOWN ------------

        // Indikator (untuk filter) — DIAMBIL DARI SUMBER YANG SAMA.
        //
        // =========================================================
        // DUA CACAT SEKALIGUS DIPERBAIKI DI SINI
        //
        // 1. Daftar ini semula membaca `renstra_indikator_sasaran` TANPA
        //    saringan OPD sama sekali. Pada basis data ini dropdown-nya
        //    memuat 128 indikator milik 38 OPD, sementara tabel di bawahnya
        //    hanya menampilkan milik OPD sendiri — Dishub, misalnya, punya 1.
        //    Selain membingungkan, itu memperlihatkan redaksi indikator OPD
        //    lain kepada siapa pun yang membuka halaman RKT.
        //
        // 2. Daftarnya tidak mengikuti versi yang dipilih. Menyusunnya dari
        //    $indikators membuat filter dan tabel dijamin bicara tentang
        //    himpunan yang sama — kalau disusun terpisah, keduanya bisa
        //    menyimpang tanpa ada yang menyadarinya.
        // =========================================================
        $sasaranList = [];
        $sudah       = [];

        foreach ($indikators as $ind) {
            $id = (int) $ind['id'];

            // Baris arsip tanpa silsilah tidak bisa dipakai memfilter: tidak
            // ada baris RKT yang bisa ditemukannya.
            if ($id <= 0 || isset($sudah[$id])) {
                continue;
            }

            $sudah[$id]    = true;
            $sasaranList[] = ['id' => $id, 'indikator_sasaran' => $ind['indikator_sasaran']];
        }

        usort($sasaranList, static fn ($a, $b) => strcasecmp(
            (string) $a['indikator_sasaran'],
            (string) $b['indikator_sasaran']
        ));

        return view('adminOpd/rkt/rkt', [
            'title' => 'RENJA (RKT)',
            'role' => $role,
            'rktdata' => $rktdata,
            'sasaranList' => $sasaranList,
            'available_years' => $availableYears,  // tahun dari renstra_target
            'filter_sasaran' => $filterSasaran,
            'filter_tahun' => $filterTahun,     // 'all' atau tahun
            'filter_status' => $filterStatus,
            'currentOpd' => $currentOpd,
            'versiRenstraList' => $versiTersedia,
            'versiRenstraDipilih' => $versiDipilih,
            'versiRenstraDiabaikan' => $versiDiabaikan,
        ]);
    }


    /* =========================================================
     * SUMBER RENSTRA UNTUK RKT — BERJALAN ATAU SEBUAH VERSI
     * =======================================================*/

    /**
     * Versi Renstra yang boleh dipilih RKT, untuk sebuah OPD.
     *
     * =====================================================================
     * MEMAKAI MEKANISME YANG SAMA DENGAN LAKIP
     *
     * `VersionResolver::pilihanSumber()` adalah fasilitas yang sudah dipakai
     * LAKIP untuk memilih versi sumbernya. Menyalin logikanya ke sini akan
     * melahirkan dua aturan "versi mana yang boleh dipilih" yang diam-diam
     * bisa menyimpang.
     *
     * =====================================================================
     * YANG BERARSIP KOSONG DIBUANG
     *
     * Versi yang sudah ditetapkan belum tentu MEMBEKUKAN isinya: pada basis
     * data ini hanya 1 dari 39 versi Renstra terbit yang punya baris arsip.
     * Menawarkan 38 sisanya berarti menyodorkan pilihan yang begitu dipilih
     * menampilkan tabel kosong — dan operator akan mengira Renstra-nya yang
     * hilang, bukan arsipnya yang memang tidak pernah diisi.
     *
     * @return array<int,array<string,mixed>>
     */
    private function versiRenstraUntukRkt($db, int $opdId): array
    {
        if (! $db->tableExists('dokumen_versi') || ! $db->tableExists('renstra_versi_sasaran')) {
            return [];
        }

        return $db->table('dokumen_versi d')
            ->select('d.id, d.version_no, d.label, d.periode_mulai, d.periode_akhir,
                      COUNT(rvs.id) AS jumlah_sasaran')
            ->join('renstra_versi_sasaran rvs', 'rvs.version_id = d.id AND rvs.opd_id = ' . (int) $opdId, 'inner', false)
            ->where('d.modul', 'renstra')
            ->where('d.opd_key', $opdId)
            ->where('d.status', 'published')
            ->groupBy('d.id')
            ->orderBy('d.version_no', 'DESC')
            ->get()->getResultArray();
    }

    /**
     * Indikator sasaran Renstra untuk RKT — dari versi terpilih atau berjalan.
     *
     * =====================================================================
     * `id` SELALU id INDIKATOR BERJALAN, BUKAN id ARSIP
     *
     * Seluruh RKT menempel pada `rkt.indikator_id`, yang menunjuk
     * `renstra_indikator_sasaran.id` — baris BERJALAN. Kalau membaca arsip
     * lalu memakai id arsipnya, tidak ada satu pun baris RKT yang akan
     * ketemu: tabelnya tampil lengkap tetapi kosong melompong di bawah
     * setiap indikator, tanpa satu pun galat.
     *
     * Karena itu `source_indikator_id` yang dipakai sebagai identitas, dan
     * arsipnya hanya menyumbang TEKS dan TARGET — persis pola yang dipakai
     * Cascading saat menjembatani RPJMD ke IKU.
     *
     * Baris arsip yang tidak bersilsilah (`source_indikator_id` NULL) memang
     * tidak bisa ditautkan ke RKT mana pun. Ia tetap ditampilkan — dengan
     * penanda — supaya keberadaannya terbaca, bukan hilang diam-diam.
     *
     * @return array{0: array<int,array<string,mixed>>, 1: array<int,array<int,int>>}
     *         [indikator, tahun target per indikator]
     */
    private function sumberRenstraRkt($db, int $opdId, ?int $versiId): array
    {
        // ---- KONDISI BERJALAN ------------------------------------------
        if ($versiId === null) {
            $indikators = $db->table('renstra_indikator_sasaran i')
                ->select('i.*, s.sasaran, s.opd_id')
                ->join('renstra_sasaran s', 's.id = i.renstra_sasaran_id', 'left')
                ->where('s.opd_id', $opdId)
                ->orderBy('i.id', 'ASC')
                ->get()->getResultArray();

            foreach ($indikators as &$ind) {
                $ind['dari_versi']   = false;
                $ind['tanpa_taut']   = false;
            }
            unset($ind);

            $tahun = [];

            if ($indikators !== []) {
                $rows = $db->table('renstra_target t')
                    ->select('t.renstra_indikator_id AS indikator_id, t.tahun', false)
                    ->whereIn('t.renstra_indikator_id', array_column($indikators, 'id'))
                    ->orderBy('t.tahun', 'ASC')
                    ->get()->getResultArray();

                foreach ($rows as $r) {
                    $tahun[(int) $r['indikator_id']][] = (int) $r['tahun'];
                }
            }

            return [$indikators, $tahun];
        }

        // ---- DARI ARSIP SEBUAH VERSI -----------------------------------
        $rows = $db->table('renstra_versi_indikator_sasaran ri')
            ->select('ri.id AS arsip_id, ri.source_indikator_id, ri.indikator_sasaran,
                      ri.satuan, ri.satuan_nama, ri.baseline, ri.jenis_indikator,
                      rs.sasaran, rs.opd_id')
            ->join('renstra_versi_sasaran rs', 'rs.id = ri.versi_sasaran_id')
            ->where('rs.version_id', $versiId)
            ->where('rs.opd_id', $opdId)
            ->orderBy('rs.urutan', 'ASC')->orderBy('ri.urutan', 'ASC')->orderBy('ri.id', 'ASC')
            ->get()->getResultArray();

        $indikators = [];
        $arsipIds   = [];

        foreach ($rows as $r) {
            $live = $r['source_indikator_id'] !== null ? (int) $r['source_indikator_id'] : 0;

            $indikators[] = [
                // Identitas untuk penautan RKT: id BERJALAN. Nol berarti baris
                // arsip ini tidak bersilsilah dan tidak akan menemukan RKT.
                'id'                => $live,
                'indikator_sasaran' => $r['indikator_sasaran'],
                'satuan'            => $r['satuan'],
                'satuan_nama'       => $r['satuan_nama'] ?? null,
                'baseline'          => $r['baseline'],
                'jenis_indikator'   => $r['jenis_indikator'],
                'sasaran'           => $r['sasaran'],
                'opd_id'            => (int) $r['opd_id'],
                'dari_versi'        => true,
                'tanpa_taut'        => $live === 0,
                'arsip_id'          => (int) $r['arsip_id'],
            ];

            $arsipIds[] = (int) $r['arsip_id'];
        }

        // Target diambil dari ARSIP versi itu, bukan dari renstra_target
        // berjalan: memilih sebuah versi lalu menampilkan target terkini
        // berarti menampilkan campuran yang tidak pernah ada sebagai dokumen.
        $tahun = [];

        if ($arsipIds !== []) {
            $rowsT = $db->table('renstra_versi_target')
                ->select('versi_indikator_id, tahun')
                ->whereIn('versi_indikator_id', $arsipIds)
                ->orderBy('tahun', 'ASC')
                ->get()->getResultArray();

            $petaArsip = [];

            foreach ($indikators as $ind) {
                $petaArsip[$ind['arsip_id']] = (int) $ind['id'];
            }

            foreach ($rowsT as $r) {
                $liveId = $petaArsip[(int) $r['versi_indikator_id']] ?? 0;

                if ($liveId > 0) {
                    $tahun[$liveId][] = (int) $r['tahun'];
                }
            }
        }

        return [$indikators, $tahun];
    }

    /** Versi terpilih, DIVALIDASI terhadap daftar yang sah untuk OPD ini. */
    private function versiRenstraDipilih($nilai, array $tersedia): ?int
    {
        $id = (int) $nilai;

        if ($id <= 0) {
            return null;
        }

        foreach ($tersedia as $v) {
            if ((int) $v['id'] === $id) {
                return $id;
            }
        }

        // Id yang dikirim tetapi tidak sah TIDAK diam-diam jatuh ke "berjalan":
        // itu akan menampilkan sumber yang tidak pernah diminta siapa pun.
        // Dikembalikan null, dan pemanggil memberi tahu bahwa pilihannya
        // diabaikan.
        return null;
    }

    public function cetak()
    {
        ob_clean();
        ob_start();

        $db = Database::connect();
        $role = session()->get('role');
        $opdId = session()->get('opd_id');

        $filterSasaran = $this->request->getGet('sasaran') ?? 'all';
        $filterTahun = $this->request->getGet('tahun') ?? 'all';
        $filterStatus = $this->request->getGet('status') ?? 'all';

        $currentOpd = $db->table('opd')
            ->where('id', $opdId)
            ->get()
            ->getRowArray();

        // Cetak MENGIKUTI versi yang sedang dilihat di layar.
        //
        // Kalau cetak selalu membaca Renstra berjalan, operator memilih sebuah
        // versi lalu menerima cetakan yang disusun dari sumber lain — tanpa
        // satu pun tanda bahwa isinya berbeda. Kekeliruan yang sama pernah
        // ada pada ekspor Cascading dan sudah diperbaiki di sana.
        $versiCetak = $this->versiRenstraDipilih(
            $this->request->getGet('renstra_versi'),
            $this->versiRenstraUntukRkt($db, (int) $opdId)
        );

        [$indikators, $targetCetak] = $this->sumberRenstraRkt($db, (int) $opdId, $versiCetak);

        // Target ikut dari sumber yang SAMA dengan indikatornya. Membaca
        // renstra_target berjalan di sini akan mencampur teks arsip dengan
        // target terkini — cetakan yang tidak pernah ada sebagai dokumen.
        $targetYearsByInd = $targetCetak;
        $availableYears = [];

        if (!empty($indikators)) {
            foreach ($targetYearsByInd as $years) {
                $availableYears = array_merge($availableYears, $years);
            }

            foreach ($targetYearsByInd as $id => $years) {
                $years = array_values(array_unique($years));
                sort($years);
                $targetYearsByInd[$id] = $years;
            }

            $availableYears = array_values(array_unique($availableYears));
            sort($availableYears);
        }

        if ($filterTahun === '' || $filterTahun === null) {
            $filterTahun = 'all';
        }

        $rktdata = [];

        foreach ($indikators as $ind) {
            $indikatorId = $ind['id'];

            if ($filterSasaran !== 'all' && (string) $filterSasaran !== (string) $indikatorId) {
                continue;
            }

            $builderRkt = $db->table('rkt r')
                ->select('r.*, p.program_kegiatan AS program_nama')
                ->join('program_pk p', 'p.id = r.program_id', 'left')
                ->where('r.indikator_id', $indikatorId)
                ->where('r.opd_id', $opdId);

            if ($filterTahun !== 'all') {
                $builderRkt->where('r.tahun', $filterTahun);
            }

            if ($filterStatus !== 'all') {
                $builderRkt->where('r.status', $filterStatus);
            }

            $rkts = $builderRkt
                ->orderBy('r.id', 'ASC')
                ->get()
                ->getResultArray();

            foreach ($rkts as &$rkt) {
                $kegiatans = $db->table('rkt_kegiatan rk')
                    ->select('rk.id, rk.kegiatan_id, k.kegiatan')
                    ->join('kegiatan_pk k', 'k.id = rk.kegiatan_id', 'left')
                    ->where('rk.rkt_id', $rkt['id'])
                    ->orderBy('rk.id', 'ASC')
                    ->get()
                    ->getResultArray();

                foreach ($kegiatans as &$keg) {
                    $subs = $db->table('rkt_subkegiatan rs')
                        ->select('rs.id, rs.sub_kegiatan_id, rs.indikator_sasaran_sub_kegiatan, rs.target, sk.sub_kegiatan, sk.anggaran')
                        ->join('sub_kegiatan_pk sk', 'sk.id = rs.sub_kegiatan_id', 'left')
                        ->where('rs.rkt_kegiatan_id', $keg['id'])
                        ->orderBy('rs.id', 'ASC')
                        ->get()
                        ->getResultArray();

                    $keg['subkegiatan'] = $subs ?: [];
                }
                unset($keg);

                $rkt['kegiatan'] = $kegiatans ?: [];
            }
            unset($rkt);

            $ind['rkts'] = $rkts;
            $ind['target_years'] = $targetYearsByInd[$indikatorId] ?? [];
            $rktdata[] = $ind;
        }

        // Disusun dari $indikators, sama seperti di index(): disaring OPD dan
        // mengikuti versi yang dipilih. Query lamanya membaca seluruh
        // renstra_indikator_sasaran tanpa saringan apa pun.
        $sasaranList = [];
        $sudahCetak  = [];

        foreach ($indikators as $ind) {
            $id = (int) $ind['id'];

            if ($id <= 0 || isset($sudahCetak[$id])) {
                continue;
            }

            $sudahCetak[$id] = true;
            $sasaranList[]   = ['id' => $id, 'indikator_sasaran' => $ind['indikator_sasaran']];
        }

        usort($sasaranList, static fn ($a, $b) => strcasecmp(
            (string) $a['indikator_sasaran'],
            (string) $b['indikator_sasaran']
        ));

        $html = view('adminOpd/rkt/rkt_cetak', [
            'title' => 'RENJA (RKT)',
            'role' => $role,
            'rktdata' => $rktdata,
            'sasaranList' => $sasaranList,
            'available_years' => $availableYears,
            'filter_sasaran' => $filterSasaran,
            'filter_tahun' => $filterTahun,
            'filter_status' => $filterStatus,
            'currentOpd' => $currentOpd,
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
        $namaOpd = trim((string) ($currentOpd['nama_opd'] ?? ''));
        $namaFile = $namaOpd !== '' ? preg_replace('/[^A-Za-z0-9]+/', '-', $namaOpd) . '-' : '';
        $filterLabel = ($filterTahun !== 'all' ? $filterTahun : 'semua-tahun');
        $mpdf->Output('RKT-OPD-' . $namaFile . $filterLabel . '.pdf', 'I');
        exit;
    }


    // /**
    //  * Toggle status draft <-> selesai untuk semua RKT
    //  * dari satu indikator + tahun.
    //  * route: POST adminopd/rkt/update-status
    //  */
    // public function updateStatus()
    // {
    //     $db = Database::connect();

    //     $indikatorId = $this->request->getPost('indikator_id');
    //     $tahun       = $this->request->getPost('tahun');

    //     if (!$indikatorId || !$tahun) {
    //         return redirect()->back()
    //             ->with('error', 'Data indikator atau tahun tidak lengkap.');
    //     }

    //     // Ambil semua RKT untuk indikator + tahun itu
    //     $rkts = $db->table('rkt')
    //         ->where('indikator_id', $indikatorId)
    //         ->where('tahun', $tahun)
    //         ->get()
    //         ->getResultArray();

    //     if (empty($rkts)) {
    //         return redirect()->back()
    //             ->with('error', 'Belum ada RKT untuk indikator & tahun ini.');
    //     }

    //     // Jika semua sudah selesai => ubah jadi draft
    //     // Jika ada yang masih draft => ubah semua jadi selesai
    //     $allSelesai = true;
    //     foreach ($rkts as $r) {
    //         if ($r['status'] !== 'selesai') {
    //             $allSelesai = false;
    //             break;
    //         }
    //     }

    //     $newStatus = $allSelesai ? 'draft' : 'selesai';

    //     $db->table('rkt')
    //         ->where('indikator_id', $indikatorId)
    //         ->where('tahun', $tahun)
    //         ->update([
    //             'status'     => $newStatus,
    //             'updated_at' => date('Y-m-d H:i:s'),
    //         ]);

    //     return redirect()->back()
    //         ->with('success', 'Status RKT berhasil diubah.');
    // }

    /**
     * FORM TAMBAH RKT
     * $indikatorId = id dari tabel renstra_indikator_sasaran
     */
    public function tambah($indikatorId)
    {
        $indikator = $this->db->table('renstra_indikator_sasaran')
            ->where('id', $indikatorId)
            ->get()
            ->getRowArray();

        if (!$indikator) {
            return redirect()->back()->with('error', 'Indikator tidak ditemukan');
        }

        // tahun bisa dari query string ?tahun=2025 atau default tahun sekarang
        $tahun = $this->request->getGet('tahun') ?? date('Y');

        // DATA MASTER
        $programPk = $this->pkModel->getAllPrograms();          // tabel program_pk
        $kegiatanPk = $this->pkModel->getKegiatan();  // tabel kegiatan_pk
        $subKegiatanPk = $this->pkModel->getSubKegiatan(); // tabel sub_kegiatan_pk filtered by tahun

        $data = [
            'title' => 'Tambah RENJA (RKT)',
            'role' => session()->get('role'),
            'indikator' => $indikator,
            'tahun' => $tahun,
            'program' => $programPk,
            'kegiatanPk' => $kegiatanPk,
            'subKegiatanPk' => $subKegiatanPk,
        ];

        return view('adminOpd/rkt/tambah_rkt', $data);
    }

    public function save()
    {
        $data = $this->request->getPost();

        // dd($data['program'][0]);
        $data['opd_id'] = session()->get('opd_id'); // atau sesuai field sesi kamu

        // Status TIDAK boleh datang dari form (§34).
        //
        // saveRkt() membaca `$payload['status']` langsung dari POST mentah,
        // sehingga menambahkan satu field tersembunyi sudah cukup untuk
        // membuat RKT lahir dalam keadaan 'selesai' tanpa pernah melewati
        // tombol penyelesaiannya. Status baru hanya boleh berpindah lewat
        // updateStatus(), yang memang memeriksa keadaan seluruh barisnya.
        unset($data['status']);
        $rktModel = new \App\Models\RktModel();
        try {
            if ($rktModel->saveRkt($data)) {
                return redirect()->to('/adminopd/rkt')->with('success', 'Data berhasil disimpan');
            } else {
                log_message('error', 'Gagal menyimpan data RKT: ' . print_r($data, true));

                return redirect()->back()->with('error', 'Terjadi kesalahan saat menyimpan data');
            }

        } catch (\Exception $e) {
            log_message('error', 'Error saving PK: ' . $e->getMessage());
            return redirect()->back()->with('error', pesanGalatBerawalan($e, 'RKT gagal diproses', 'opd.rkt'));
        }

    }

    // app/Controllers/AdminOpd/RktController.php

    public function edit($indikatorId)
    {
        $db = \Config\Database::connect();
        $role = session()->get('role');

        // ---------- Ambil indikator + sasaran ----------
        $indikator = $db->table('renstra_indikator_sasaran i')
            ->select('i.*, s.sasaran')
            ->join('renstra_sasaran s', 's.id = i.renstra_sasaran_id', 'left')
            ->where('i.id', $indikatorId)
            ->get()
            ->getRowArray();

        if (!$indikator) {
            return redirect()->back()->with('error', 'Indikator tidak ditemukan.');
        }

        // ---------- Ambil semua RKT (program) utk indikator ini ----------
        $rkts = $db->table('rkt r')
            ->select('r.id, r.program_id, r.tahun, r.status')
            ->where('r.indikator_id', $indikatorId)
            ->orderBy('r.id', 'ASC')
            ->get()
            ->getResultArray();

        // kalau belum ada RKT, pakai tahun sekarang
        $tahun = !empty($rkts) ? ($rkts[0]['tahun'] ?? date('Y')) : date('Y');

        // ---------- Susun nested: program -> kegiatan -> subkegiatan ----------
        $rktPrograms = [];   // ini yang dipakai view utk pre-fill

        foreach ($rkts as $rktRow) {
            $rktId = $rktRow['id'];

            // kegiatan untuk rkt ini
            $kegiatans = $db->table('rkt_kegiatan rk')
                ->select('rk.id, rk.kegiatan_id')
                ->where('rk.rkt_id', $rktId)
                ->orderBy('rk.id', 'ASC')
                ->get()
                ->getResultArray();

            $kegiatanNested = [];

            foreach ($kegiatans as $kegRow) {
                $rkKegId = $kegRow['id'];
                $kegiatanId = $kegRow['kegiatan_id'];

                // subkegiatan utk rkt_kegiatan ini
                $subs = $db->table('rkt_subkegiatan rs')
                    ->select('rs.sub_kegiatan_id, rs.indikator_sasaran_sub_kegiatan, rs.target, sk.anggaran')
                    ->join(
                        'sub_kegiatan_pk sk',
                        'sk.id = rs.sub_kegiatan_id',
                        'left'
                    )
                    ->where('rs.rkt_kegiatan_id', $rkKegId)
                    ->orderBy('rs.id', 'ASC')
                    ->get()
                    ->getResultArray();


                $subNested = [];
                foreach ($subs as $sRow) {
                    $subNested[] = [
                        'sub_kegiatan_id' => $sRow['sub_kegiatan_id'],
                        'anggaran' => $sRow['anggaran'],
                        'indikator_sasaran_sub_kegiatan' => $sRow['indikator_sasaran_sub_kegiatan'] ?? null,
                        'target' => $sRow['target'] ?? null,
                    ];
                }

                $kegiatanNested[] = [
                    'kegiatan_id' => $kegiatanId,
                    'subkegiatan' => $subNested,
                ];
            }

            $rktPrograms[] = [
                'program_id' => $rktRow['program_id'],
                'kegiatan' => $kegiatanNested,
            ];
        }

        // dd($rktPrograms);

        // ---------- master program / kegiatan / sub ----------
        $programPk = $this->pkModel->getAllPrograms();          // tabel program_pk
        $kegiatanPk = $this->pkModel->getKegiatan();  // tabel kegiatan_pk
        $subKegiatanPk = $this->pkModel->getSubKegiatan(); // tabel sub_kegiatan_pk filtered by tahun

        // ---------- kirim ke view ----------
        return view('adminOpd/rkt/edit_rkt', [
            'title' => 'Edit RENJA (RKT)',
            'role' => $role,
            'indikator' => $indikator,
            'tahun' => $tahun,
            'program' => $programPk,
            'kegiatanPk' => $kegiatanPk,
            'subKegiatanPk' => $subKegiatanPk,
            'rktPrograms' => $rktPrograms,   // <- prefill
        ]);
    }

    // app/Controllers/Adminopd/RenjaController.php (method update)
    public function update()
    {
        $db = \Config\Database::connect();

        // data dasar
        $indikatorId = $this->request->getPost('indikator_id');
        $opdId = session()->get('opd_id') ?? $this->request->getPost('opd_id');
        $tahun = $this->request->getPost('tahun') ?? date('Y');

        if (!$indikatorId || !$opdId) {
            return redirect()->back()->with('error', 'Data dasar (indikator / OPD) tidak lengkap.');
        }

        $postPrograms = $this->request->getPost('program') ?? [];

        // dd($postPrograms);
        $db->transStart();

        try {
            // ========== UPSERT (UPDATE / INSERT) berdasarkan POST ==========
            $processedRktIds = [];
            $processedKegIds = [];
            $processedSubIds = [];

            foreach ($postPrograms as $p) {
                $programId = isset($p['program_id']) ? (int) $p['program_id'] : 0;
                if (!$programId) {
                    continue;
                }

                // 2.1 UPSERT RKT
                $existRkt = $db->table('rkt')
                    ->where('indikator_id', $indikatorId)
                    ->where('program_id', $programId)
                    ->where('tahun', $tahun)
                    ->where('opd_id', $opdId)
                    ->get()->getRowArray();
                
                if ($existRkt) {
                    $rktId = $existRkt['id'];
                    $db->table('rkt')->where('id', $rktId)->update([
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
                } else {
                    $db->table('rkt')->insert([
                        'opd_id' => $opdId,
                        'tahun' => $tahun,
                        'indikator_id' => $indikatorId,
                        'program_id' => $programId,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
                    $rktId = $db->insertID();
                }
                $processedRktIds[] = $rktId;

                // 2.2 UPSERT KEGIATAN di dalam program ini
                $kegList = $p['kegiatan'] ?? [];
                foreach ($kegList as $k) {
                    $kegiatanId = isset($k['kegiatan_id']) ? (int) $k['kegiatan_id'] : 0;
                    if (!$kegiatanId) {
                        continue;
                    }

                    $existKeg = $db->table('rkt_kegiatan')
                        ->where('rkt_id', $rktId)
                        ->where('kegiatan_id', $kegiatanId)
                        ->get()->getRowArray();

                    if ($existKeg) {
                        $rktKegId = $existKeg['id'];
                        $db->table('rkt_kegiatan')->where('id', $rktKegId)->update([
                            'updated_at' => date('Y-m-d H:i:s')
                        ]);
                    } else {
                        $db->table('rkt_kegiatan')->insert([
                            'rkt_id' => $rktId,
                            'kegiatan_id' => $kegiatanId,
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s'),
                        ]);
                        $rktKegId = $db->insertID();
                    }
                    $processedKegIds[] = $rktKegId;

                    // 2.3 UPSERT SUBKEGIATAN di dalam kegiatan ini
                    $subs = $k['subkegiatan'] ?? [];
                    foreach ($subs as $s) {
                        $subId = isset($s['subkegiatan_id']) ? (int) $s['subkegiatan_id'] : 0;
                        if (!$subId) {
                            continue;
                        }

                        $existSub = $db->table('rkt_subkegiatan')
                            ->where('rkt_kegiatan_id', $rktKegId)
                            ->where('sub_kegiatan_id', $subId)
                            ->get()->getRowArray();
                            
                        if ($existSub) {
                            $rktSubId = $existSub['id'];
                            $db->table('rkt_subkegiatan')->where('id', $rktSubId)->update([
                                'indikator_sasaran_sub_kegiatan' => $s['indikator_sasaran_sub_kegiatan'] ?? null,
                                'target' => $s['target'] ?? null,
                                'updated_at' => date('Y-m-d H:i:s')
                            ]);
                        } else {
                            $db->table('rkt_subkegiatan')->insert([
                                'rkt_kegiatan_id' => $rktKegId,
                                'sub_kegiatan_id' => $subId,
                                'indikator_sasaran_sub_kegiatan' => $s['indikator_sasaran_sub_kegiatan'] ?? null,
                                'target' => $s['target'] ?? null,
                                'created_at' => date('Y-m-d H:i:s'),
                                'updated_at' => date('Y-m-d H:i:s'),
                            ]);
                            $rktSubId = $db->insertID();
                        }
                        $processedSubIds[] = $rktSubId;
                    }
                }
            }

            // ========== DELETE ORPHANS (Hapus sisa-sisa ID lama yang tak lagi dipilih di form) ==========
            $allDbRkts = $db->table('rkt')
                ->where('indikator_id', $indikatorId)
                ->where('tahun', $tahun)
                ->where('opd_id', $opdId)
                ->get()->getResultArray();
            
            $dbRktIds = array_column($allDbRkts, 'id');

            if (!empty($dbRktIds)) {
                $allDbKegs = $db->table('rkt_kegiatan')
                    ->whereIn('rkt_id', $dbRktIds)
                    ->get()->getResultArray();
                $dbKegIds = array_column($allDbKegs, 'id');

                if (!empty($dbKegIds)) {
                    // hapus subkegiatan
                    $qSub = $db->table('rkt_subkegiatan')->whereIn('rkt_kegiatan_id', $dbKegIds);
                    if (!empty($processedSubIds)) {
                        $qSub->whereNotIn('id', $processedSubIds);
                    }
                    $qSub->delete();

                    // hapus kegiatan
                    $qKeg = $db->table('rkt_kegiatan')->whereIn('id', $dbKegIds);
                    if (!empty($processedKegIds)) {
                        $qKeg->whereNotIn('id', $processedKegIds);
                    }
                    $qKeg->delete();
                }

                // hapus rkt program
                $qRkt = $db->table('rkt')->whereIn('id', $dbRktIds);
                if (!empty($processedRktIds)) {
                    $qRkt->whereNotIn('id', $processedRktIds);
                }
                $qRkt->delete();
            }

            $db->transComplete();

            if ($db->transStatus() === false) {
                return redirect()->back()->with('error', 'Gagal menyimpan perubahan (transaksi gagal).');
            }

            return redirect()->to(base_url('adminopd/rkt'))
                ->with('success', 'Perubahan RKT berhasil disimpan.');
        } catch (\Throwable $th) {
            $db->transRollback();
            return redirect()->back()
                ->with('error', pesanGalatBerawalan($th, 'Terjadi kesalahan', 'opd.rkt'));
        }
    }

    public function updateStatus()
    {
        $db = \Config\Database::connect();

        $indikatorId = (int) $this->request->getPost('indikator_id');
        $tahun = $this->request->getPost('tahun') ?: date('Y');

        if (!$indikatorId) {
            return redirect()->back()->with('error', 'Indikator tidak valid.');
        }

        // =============================================================
        // LINGKUP OPD DIAMBIL DARI SESI, DAN IKUT MENYARING
        //
        // Sebelumnya baca maupun tulis di bawah hanya menyaring
        // `indikator_id` + `tahun`. Karena indikator milik SEMUA OPD hidup di
        // tabel yang sama, satu POST dengan indikator_id milik OPD lain sudah
        // cukup untuk membalik status RKT mereka — tanpa galat apa pun, dan
        // tanpa jejak bahwa yang mengubahnya bukan pemiliknya.
        //
        // `deleteByIndicator()` tepat di bawah method ini SUDAH menyaring
        // opd_id sejak semula; yang satu ini terlewat.
        // =============================================================
        $opdId = session()->get('opd_id');

        if ($opdId === null || $opdId === '') {
            return redirect()->back()->with('error', 'Lingkup OPD tidak dikenali.');
        }

        $opdId = (int) $opdId;

        try {
            // Ambil semua RKT untuk indikator + tahun ini, MILIK OPD INI.
            $rows = $db->table('rkt')
                ->where('indikator_id', $indikatorId)
                ->where('tahun', $tahun)
                ->where('opd_id', $opdId)
                ->get()
                ->getResultArray();

            if (empty($rows)) {
                return redirect()->back()->with(
                    'error',
                    'Data RKT untuk indikator dan tahun tersebut tidak ditemukan.'
                );
            }

            // Cek apakah semua sudah selesai
            $allSelesai = true;
            foreach ($rows as $row) {
                if (($row['status'] ?? '') !== 'selesai') {
                    $allSelesai = false;
                    break;
                }
            }

            // Kalau semua selesai → jadi draft
            // Kalau ada yang belum selesai → jadikan selesai
            $newStatus = $allSelesai ? 'draft' : 'selesai';

            $db->table('rkt')
                ->where('indikator_id', $indikatorId)
                ->where('tahun', $tahun)
                ->where('opd_id', $opdId)
                ->update([
                    'status' => $newStatus,
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);

            return redirect()->back()->with(
                'success',
                'Status RKT berhasil diubah menjadi: ' . ucfirst($newStatus)
            );
        } catch (\Throwable $th) {
            return redirect()->back()->with(
                'error',
                pesanGalatBerawalan($th, 'Gagal mengubah status RKT', 'opd.rkt')
            );
        }
    }
    public function deleteByIndicator()
    {
        $db = \Config\Database::connect();

        $indikatorId = $this->request->getPost('indikator_id');
        $opdId = session()->get('opd_id');

        if (!$indikatorId) {
            return redirect()->back()->with('error', 'Indikator tidak valid.');
        }

        $db->transStart();

        try {
            // Ambil semua ID RKT untuk indikator dan OPD ini
            $rkts = $db->table('rkt')
                ->select('id')
                ->where('indikator_id', $indikatorId)
                ->where('opd_id', $opdId)
                ->get()
                ->getResultArray();
            $rktIds = array_column($rkts, 'id');

            if (!empty($rktIds)) {
                // Ambil semua ID kegiatan untuk RKT ini
                $kegs = $db->table('rkt_kegiatan')
                    ->select('id')
                    ->whereIn('rkt_id', $rktIds)
                    ->get()
                    ->getResultArray();
                $kegIds = array_column($kegs, 'id');

                if (!empty($kegIds)) {
                    // Hapus subkegiatan terlebih dahulu
                    $db->table('rkt_subkegiatan')->whereIn('rkt_kegiatan_id', $kegIds)->delete();
                    // Hapus kegiatan
                    $db->table('rkt_kegiatan')->whereIn('id', $kegIds)->delete();
                }

                // Hapus RKT utama
                $db->table('rkt')->whereIn('id', $rktIds)->delete();
            }

            $db->transComplete();

            if ($db->transStatus() === false) {
                throw new \Exception('Transaksi database gagal.');
            }

            return redirect()->back()->with('success', 'Seluruh RKT indikator berhasil dihapus.');

        } catch (\Throwable $e) {
            $db->transRollback();
            return redirect()->back()->with('error', pesanGalatBerawalan($e, 'Gagal menghapus data', 'opd.rkt'));
        }
    }
}
