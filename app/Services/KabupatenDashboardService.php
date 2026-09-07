<?php

namespace App\Services;

use App\Models\OpdModel;
use Config\Database;

/**
 * Sumber data Dashboard Pengendalian Kinerja tingkat KABUPATEN.
 *
 * Dua mode:
 *   - MODE KABUPATEN  : filter OPD = semua. Menyorot PK Bupati, status seluruh
 *                       Perangkat Daerah, OPD yang belum update, prioritas
 *                       pimpinan, dan kontribusi terhadap Misi Bupati.
 *   - MODE FOKUS OPD  : satu OPD dipilih. Seluruh kartu & grafik berganti ke
 *                       konteks OPD tersebut, TETAP di area /adminkab.
 *
 * Perhitungan indikator (validitas, persentase, status, anggaran) memakai
 * OpdDashboardService yang sama dengan dashboard OPD supaya angka di dua
 * dashboard tidak pernah berbeda. Pemuatan lintas OPD dilakukan sekali
 * secara batch (loadIndicatorsForOpds), bukan per OPD.
 *
 * BATAS AKSES: dashboard ini hanya untuk role lintas OPD
 * (admin_kab, admin_inspektorat, admin). opd_id dari request selalu
 * divalidasi ke daftar OPD yang sah sebelum dipakai.
 */
class KabupatenDashboardService
{
    /** Batas hari sejak pembaruan terakhir sebelum sebuah OPD disebut terlambat. */
    public const HARI_TERLAMBAT = 14;

    /** Porsi indikator belum valid yang membuat status OPD dinyatakan Belum Valid. */
    private const AMBANG_BELUM_VALID = 0.5;

    /**
     * Kegentingan panel prioritas - makin kecil makin atas.
     *
     * Urutannya SENGAJA menaruh isu KINERJA di atas gap KELENGKAPAN DATA.
     * Sebelumnya kebalikannya (gap PK Bupati = 25, OPD kritis = 30), sehingga
     * lima belas indikator PK Bupati yang belum punya Rencana Aksi mendorong
     * seluruh Perangkat Daerah bermasalah keluar dari lima besar.
     */
    private const SEV_PK_BUPATI_KRITIS  = 10;
    private const SEV_OPD_KRITIS        = 20;
    private const SEV_SERAP_TINGGI      = 30;
    private const SEV_PK_BUPATI_FORMULA = 40;
    private const SEV_PK_BUPATI_DATA    = 45;
    private const SEV_OPD_BELUM_UPDATE  = 50;
    private const SEV_VERIFIKASI        = 60;

    /** Penyerapan anggaran (%) yang dianggap tinggi pada rule "serap tinggi, capaian rendah". */
    private const PENYERAPAN_TINGGI = 60.0;

    /**
     * Role yang boleh membuka dashboard ini.
     * `bupati` memakai data yang sama, tetapi dilayani lewat area rute /bupati
     * (lihat $linkArea) sehingga tidak pernah menyentuh fitur administratif.
     */
    private const ROLE_DIIZINKAN = ['admin_kab', 'admin_inspektorat', 'admin', 'bupati'];

    /** Role yang hanya boleh membaca (tanpa tombol ubah). */
    private const ROLE_READONLY = ['admin_inspektorat', 'bupati'];

    private $db;
    private OpdDashboardService $opd;

    /**
     * Area rute tujuan seluruh tautan tindak lanjut: 'adminkab' (admin_kab /
     * inspektorat / super admin) atau 'bupati' (halaman monitoring read-only).
     */
    private string $linkArea = 'adminkab';

    /** @var array<string, array<int, array<int, array<string, mixed>>>> memoisasi indikator per OPD */
    private array $cacheIndikator = [];

    public function __construct()
    {
        $this->db  = Database::connect();
        $this->opd = new OpdDashboardService();

        // Seluruh tombol tindak lanjut mengarah ke rute pemantauan lintas OPD —
        // /bupati untuk role bupati, /adminkab untuk role kabupaten lainnya.
        // Tidak pernah ke /adminopd yang terikat sesi OPD.
        $this->linkArea = ((string) (session()->get('role') ?? '') === 'bupati') ? 'bupati' : 'adminkab';
        $this->opd->setLinkArea($this->linkArea);

        helper(['capaian', 'dashboard_status', 'format']);
    }

    /** Area rute tautan yang sedang dipakai ('adminkab' | 'bupati'). */
    public function linkArea(): string
    {
        return $this->linkArea;
    }

    /* =====================================================================
     * KONTEKS & FILTER
     * ===================================================================*/

    /**
     * Validasi hak akses + filter OPD.
     *
     * @return array{role: string, allowed: bool, can_write: bool,
     *               opd_id: int|null, opd_nama: string|null, mode: string,
     *               opd_list: array<int, array<string, mixed>>}
     */
    public function resolveScope(?int $opdDiminta): array
    {
        $role    = (string) (session()->get('role') ?? '');
        $allowed = in_array($role, self::ROLE_DIIZINKAN, true);
        $list    = $allowed ? $this->opdOptions() : [];

        // opd_id dari request TIDAK pernah dipercaya begitu saja: hanya diterima
        // bila benar-benar ada pada daftar OPD yang sah.
        $sahId = array_map('intval', array_column($list, 'id'));
        $opdId = ($opdDiminta !== null && in_array($opdDiminta, $sahId, true)) ? $opdDiminta : null;

        return [
            'role'      => $role,
            'allowed'   => $allowed,
            'can_write' => $allowed && !in_array($role, self::ROLE_READONLY, true),
            'opd_id'    => $opdId,
            'opd_nama'  => $opdId ? $this->namaOpd($opdId) : null,
            'mode'      => $opdId ? 'fokus_opd' : 'kabupaten',
            'opd_list'  => $list,
        ];
    }

    /**
     * Perangkat Daerah yang masuk agregat dashboard eksekutif.
     *
     * Kecamatan, kelurahan, dan UPT DIKELUARKAN: jalur pembinaan dan dokumen
     * PK-nya berbeda (PK Camat, jenjang Pelaksana), sehingga bila dicampur ke
     * agregat lintas Perangkat Daerah angka "Capaian Perangkat Daerah",
     * "OPD Belum Update", dan Prioritas Pimpinan menjadi tidak sebanding.
     * Klasifikasinya dibaca dari kolom `opd.jenis` — DATA yang bisa dikoreksi
     * Super Admin lewat Master OPD, bukan tebakan dari `pk.jenis` atau pola
     * nama (lihat OpdModel::EXCLUDED_EXECUTIVE_JENIS).
     *
     * Bila kolom `jenis` belum ada (basis data yang belum dimigrasi), daftar
     * dikembalikan apa adanya — dashboard tetap jalan, hanya belum tersaring.
     *
     * @return array<int, array<string, mixed>>
     */
    public function opdOptions(): array
    {
        $b = $this->db->table('opd')
            ->select('id, nama_opd')
            ->whereNotIn('id', OpdModel::EXCLUDED_OPD_IDS);

        if ($this->db->fieldExists('jenis', 'opd')) {
            $b->whereNotIn('jenis', OpdModel::EXCLUDED_EXECUTIVE_JENIS);
        }

        return $b->orderBy('nama_opd', 'ASC')->get()->getResultArray();
    }

    private function namaOpd(int $opdId): ?string
    {
        $row = $this->db->table('opd')->select('nama_opd')->where('id', $opdId)->get()->getRowArray();

        return $row['nama_opd'] ?? null;
    }

    /**
     * Tahun yang tersedia (dari seluruh dokumen PK). Tahun berjalan selalu ikut.
     *
     * @return int[]
     */
    public function getAvailableYears(): array
    {
        $rows  = $this->db->table('pk')->select('tahun')->distinct()->orderBy('tahun', 'DESC')->get()->getResultArray();
        $tahun = array_map('intval', array_column($rows, 'tahun'));
        $tahun[] = (int) date('Y');
        $tahun = array_values(array_unique(array_filter($tahun)));
        rsort($tahun);

        return $tahun;
    }

    /**
     * Misi RPJMD yang berlaku pada tahun tersebut.
     *
     * @return array<int, array<string, mixed>>
     */
    public function misiOptions(int $tahun): array
    {
        return $this->db->table('rpjmd_misi')
            ->select('id, misi, tahun_mulai, tahun_akhir')
            ->where('tahun_mulai <=', $tahun)
            ->where('tahun_akhir >=', $tahun)
            ->orderBy('id', 'ASC')
            ->get()->getResultArray();
    }

    public function isReadOnly(string $role): bool
    {
        return in_array($role, self::ROLE_READONLY, true);
    }

    /* =====================================================================
     * MODE KABUPATEN
     * ===================================================================*/

    /**
     * Seluruh bahan Mode Kabupaten.
     *
     * @return array<string, mixed>
     */
    public function getKabupatenSummary(int $tahun, int $triwulan, ?int $misiId = null): array
    {
        $bupati   = $this->opd->loadBupatiIndicators($tahun, $triwulan);
        $pkBupati = $this->getBupatiPkAchievement($bupati, $tahun);

        $statuses = $this->getOpdStatuses($tahun, $triwulan, $misiId);
        $ringkas  = $this->getOpdStatusSummary($statuses);
        $telat    = $this->getUnupdatedOpds($statuses);
        $misi     = $this->getMissionContributions($tahun, $triwulan, $statuses, $bupati);
        $prioritas = $this->getLeadershipPriorities($pkBupati, $statuses, $tahun, $triwulan);

        return [
            'mode'    => 'kabupaten',
            'context' => [
                'tahun'       => $tahun,
                'triwulan'    => $triwulan,
                'misi_id'     => $misiId,
                'last_update' => $this->getLastUpdate($tahun),
                'verifikasi'  => $this->opd->verificationInfo(),
                'batas_hari'  => self::HARI_TERLAMBAT,
            ],
            'pk_bupati'  => $pkBupati,
            'opd'        => $ringkas,
            'opd_list'   => array_values($statuses),
            'belum_update' => $telat,
            'prioritas'  => $prioritas,
            'distribusi' => $this->getOpdStatusDistribution($statuses),
            'tren'       => $this->getBupatiIndicatorTrend($bupati),
            'misi'       => $misi,
        ];
    }

    /**
     * Kartu 1 — Capaian PK Bupati.
     *
     * Rumus sama dengan capaian OPD: rata-rata persentase SELURUH indikator PK
     * Bupati yang wajib dihitung, dan hanya ditampilkan bila semuanya valid.
     * Tidak pernah menampilkan rata-rata parsial.
     *
     * @param array<int, array<string, mixed>> $indikator
     *
     * @return array<string, mixed>
     */
    public function getBupatiPkAchievement(array $indikator, int $tahun): array
    {
        $wajib  = count($indikator);
        $valid  = 0;
        $jumlah = 0.0;
        $kritis = 0;
        $formulaGap = 0;

        $pengampu = $this->opdPengampuSasaran(array_column($indikator, 'sasaran_id'));

        $daftar = [];
        foreach ($indikator as $i) {
            if ($i['validity']['is_valid']) {
                $valid++;
                $jumlah += (float) $i['percentage'];
                if ($i['status']['code'] === 'critical') {
                    $kritis++;
                }
            } elseif (in_array((string) $i['validity']['reason_code'], ['missing_method', 'missing_formula', 'missing_predicate_scale'], true)) {
                $formulaGap++;
            }

            $daftar[] = [
                'indikator_id'    => $i['indikator_id'],
                'indikator'       => $i['indikator'],
                'sasaran'         => $i['sasaran'],
                'sasaran_id'      => $i['sasaran_id'],
                'satuan'          => $i['satuan'],
                'target_tahunan'  => $i['target_tahunan'],
                'percentage'      => $i['percentage'],
                'percentage_teks' => $i['percentage'] !== null ? capaianFormatPersen($i['percentage']) : null,
                'status'          => $i['status'],
                'is_valid'        => $i['validity']['is_valid'],
                'reason_code'     => $i['validity']['reason_code'],
                'reason'          => $i['validity']['reason'],
                'metode_nama'     => capaianMetodeRingkas($i['rows'] ?? []),
                'verification'    => $i['verification'],
                'renaksi_count'   => $i['renaksi_count'],
                'pengampu'        => $pengampu[(int) $i['sasaran_id']] ?? [],
                'updated_at'      => $i['updated_at'],
            ];
        }

        $bisa       = $wajib > 0 && $valid === $wajib;
        $total      = $bisa ? round($jumlah / $wajib, 2) : null;
        $verifikasi = $this->opd->verificationInfo();
        $belumVerif = $verifikasi['available'] ? 0 : $valid;

        return [
            'ada'         => $wajib > 0,
            'total'       => $total,
            'valid'       => $valid,
            'wajib'       => $wajib,
            'belum_valid' => $wajib - $valid,
            'kritis'      => $kritis,
            'formula_gap' => $formulaGap,
            'can_compute' => $bisa,
            'status'      => $bisa ? getAchievementStatus((float) $total) : dash_status_nonnumeric('belum_valid'),
            'label'       => ($verifikasi['available'] && $belumVerif === 0) ? 'Terverifikasi' : 'Sementara',
            'verified_all' => $verifikasi['available'] && $belumVerif === 0,
            'belum_verifikasi' => $belumVerif,
            'verifikasi'  => $verifikasi,
            'indikator'   => $daftar,
            'tahun'       => $tahun,
        ];
    }

    /**
     * OPD pengampu tiap sasaran PK Bupati.
     *
     * SATU-SATUNYA relasi yang dipakai adalah `pk_sasaran_opd` (penetapan
     * manual lewat menu "Kelola Perangkat Daerah" pada Rencana Aksi PK Bupati).
     * Pencocokan berdasarkan kemiripan teks sasaran SENGAJA TIDAK dipakai —
     * sasaran tanpa baris di tabel ini dilaporkan apa adanya sebagai gap.
     *
     * @param int[] $sasaranIds
     *
     * @return array<int, array<int, array<string, mixed>>> [pk_sasaran_id => opd[]]
     */
    private function opdPengampuSasaran(array $sasaranIds): array
    {
        $sasaranIds = array_values(array_unique(array_filter(array_map('intval', $sasaranIds))));
        if ($sasaranIds === [] || !$this->db->tableExists('pk_sasaran_opd')) {
            return [];
        }

        $rows = $this->db->table('pk_sasaran_opd pso')
            ->select('pso.pk_sasaran_id, pso.opd_id, o.nama_opd')
            ->join('opd o', 'o.id = pso.opd_id', 'inner')
            ->whereIn('pso.pk_sasaran_id', $sasaranIds)
            ->orderBy('o.nama_opd', 'ASC')
            ->get()->getResultArray();

        $map = [];
        foreach ($rows as $r) {
            $map[(int) $r['pk_sasaran_id']][] = [
                'opd_id' => (int) $r['opd_id'],
                'nama'   => (string) $r['nama_opd'],
            ];
        }

        return $map;
    }

    /**
     * Status pengendalian seluruh Perangkat Daerah.
     *
     * @return array<int, array<string, mixed>> [opd_id => ringkasan]
     */
    public function getOpdStatuses(int $tahun, int $triwulan, ?int $misiId = null): array
    {
        $opdList = $this->opdOptions();
        $opdIds  = array_map('intval', array_column($opdList, 'id'));
        $perOpd  = $this->indikatorSeluruhOpd($opdIds, $tahun, $triwulan);

        $hasil = [];
        foreach ($opdList as $o) {
            $opdId     = (int) $o['id'];
            $indikator = $perOpd[$opdId] ?? [];

            if ($misiId !== null) {
                $indikator = array_values(array_filter($indikator, static function ($i) use ($misiId) {
                    foreach ($i['misi'] as $m) {
                        if ((int) $m['misi_id'] === $misiId) {
                            return true;
                        }
                    }
                    return false;
                }));
                // Dengan filter Misi aktif, OPD yang tidak mengampu misi itu
                // memang tidak relevan untuk ditampilkan.
                if ($indikator === []) {
                    continue;
                }
            }

            $hasil[$opdId] = $this->ringkasOpd($opdId, (string) $o['nama_opd'], $indikator, $triwulan, $tahun);
        }

        return $hasil;
    }

    /**
     * Ringkasan satu OPD dari daftar indikatornya.
     *
     * @param array<int, array<string, mixed>> $indikator
     *
     * @return array<string, mixed>
     */
    private function ringkasOpd(int $opdId, string $nama, array $indikator, int $triwulan, int $tahun): array
    {
        $total = count($indikator);
        $valid = 0;
        $kritis = 0;
        $perhatian = 0;
        $jumlah = 0.0;
        $anggaran = 0.0;
        $realisasi = 0.0;
        $adaRealisasi = false;
        $monevAda = 0;
        $tanpaCapaianPeriode = 0;
        $tanpaRenaksi = 0;
        $waktu = [];
        $programTerhitung = [];

        foreach ($indikator as $i) {
            if ($i['validity']['is_valid']) {
                $valid++;
                $jumlah += (float) $i['percentage'];
                if ($i['status']['code'] === 'critical') {
                    $kritis++;
                } elseif ($i['status']['code'] === 'attention') {
                    $perhatian++;
                }
            }
            if ($i['renaksi_count'] === 0) {
                $tanpaRenaksi++;
            }

            $punyaMonev = false;
            $punyaPeriode = false;
            foreach ($i['rows'] as $baris) {
                if ($baris['monev_id'] !== null) {
                    $punyaMonev = true;
                }
                if (capaianTerisi($baris['capaian'][$triwulan] ?? null)) {
                    $punyaPeriode = true;
                }
            }
            if ($punyaMonev) {
                $monevAda++;
            }
            if (!$punyaPeriode) {
                $tanpaCapaianPeriode++;
            }

            foreach ($i['programs'] as $p) {
                if ($p['milik_opd_lain'] || isset($programTerhitung[$p['program_id']])) {
                    continue;
                }
                $programTerhitung[$p['program_id']] = true;
                $anggaran += (float) $p['anggaran'];
            }
            if ($i['realisasi'] !== null) {
                $realisasi += (float) $i['realisasi'];
                $adaRealisasi = true;
            }
            if (!empty($i['updated_at'])) {
                $waktu[] = (string) $i['updated_at'];
            }
        }

        $lastUpdate = $waktu === [] ? null : max($waktu);
        $hari       = $lastUpdate !== null ? (int) floor((time() - strtotime($lastUpdate)) / 86400) : null;

        $update = $this->statusUpdate(
            $total,
            $monevAda,
            $tanpaCapaianPeriode,
            $hari,
            $triwulan,
            dash_triwulan_selesai($tahun, $triwulan)
        );

        $ringkas = [
            'opd_id'          => $opdId,
            'nama_opd'        => $nama,
            'tahun'           => $tahun,
            'indikator'       => $total,
            'valid'           => $valid,
            'belum_valid'     => $total - $valid,
            'kritis'          => $kritis,
            'perhatian'       => $perhatian,
            'tanpa_renaksi'   => $tanpaRenaksi,
            'indikator_belum_input' => $tanpaCapaianPeriode,
            'percentage'      => ($total > 0 && $valid === $total) ? round($jumlah / $total, 2) : null,
            'can_compute'     => $total > 0 && $valid === $total,
            'anggaran'        => $anggaran,
            'realisasi'       => $adaRealisasi ? $realisasi : null,
            'penyerapan'      => ($anggaran > 0 && $adaRealisasi) ? round($realisasi / $anggaran * 100, 2) : null,
            'last_update'     => $lastUpdate,
            'hari_sejak_update' => $hari,
            'update'          => $update,
        ];

        $ringkas['status'] = $this->getOpdControlStatus($ringkas);

        return $ringkas;
    }

    /**
     * Status pembaruan data sebuah OPD pada periode terpilih.
     *
     * Dibedakan dengan jelas: belum pernah input, belum mengisi periode ini,
     * baru sebagian, atau sudah lengkap namun sudah lama tidak diperbarui.
     *
     * @return array{code: string, label: string, keterangan: string, belum_update: bool}
     */
    private function statusUpdate(
        int $total,
        int $monevAda,
        int $tanpaPeriode,
        ?int $hari,
        int $triwulan,
        bool $periodeSelesai = false
    ): array {
        $tw = capaianRomawi($triwulan);

        if ($total === 0) {
            return ['code' => 'belum_ada_pk', 'label' => 'Belum ada PK', 'keterangan' => 'Belum ada Perjanjian Kinerja pimpinan pada tahun ini.', 'belum_update' => false];
        }
        if ($monevAda === 0) {
            return ['code' => 'belum_pernah', 'label' => 'Belum pernah input', 'keterangan' => 'Belum ada satu pun MONEV yang diinput.', 'belum_update' => true];
        }
        if ($tanpaPeriode >= $total) {
            return ['code' => 'belum_periode', 'label' => 'Belum isi TW ' . $tw, 'keterangan' => 'Belum mengisi capaian Triwulan ' . $tw . '.', 'belum_update' => true];
        }
        if ($tanpaPeriode > 0) {
            return ['code' => 'belum_lengkap', 'label' => 'Belum lengkap', 'keterangan' => $tanpaPeriode . ' indikator belum diisi pada Triwulan ' . $tw . '.', 'belum_update' => true];
        }
        // Sampai di sini seluruh indikator periode ini SUDAH terisi.
        //
        // Ambang "lama tidak diperbarui" hanya bermakna selama triwulannya
        // masih BERJALAN — di situ masih mungkin ada capaian baru yang belum
        // dilaporkan. Untuk triwulan yang sudah tutup dan datanya lengkap,
        // tidak ada apa pun yang perlu diperbarui; menandainya "belum update"
        // membuat 20 Perangkat Daerah menyala oranye selamanya hanya karena
        // berkasnya tidak disentuh lagi setelah pelaporan selesai.
        if (!$periodeSelesai && $hari !== null && $hari > self::HARI_TERLAMBAT) {
            return ['code' => 'terlambat', 'label' => 'Lama tidak diperbarui', 'keterangan' => 'Triwulan ' . $tw . ' masih berjalan dan pembaruan terakhir ' . $hari . ' hari lalu.', 'belum_update' => true];
        }

        return [
            'code'         => 'terkini',
            'label'        => $periodeSelesai ? 'Lengkap' : 'Terkini',
            'keterangan'   => $periodeSelesai
                ? 'Capaian Triwulan ' . $tw . ' sudah lengkap dan periodenya telah ditutup.'
                : 'Capaian Triwulan ' . $tw . ' sudah terisi.',
            'belum_update' => false,
        ];
    }

    /**
     * Status pengendalian OPD — BUKAN sekadar rata-rata capaian.
     *
     * Urutan aturan (yang pertama cocok dipakai):
     *   1. belum ada indikator PK                        -> Belum Ada Data
     *   2. ada indikator kritis DAN OPD belum update     -> Kritis
     *   3. ada indikator kritis                          -> minimal Perlu Perhatian
     *   4. ada indikator belum valid                     -> Belum Valid
     *   5. seluruh indikator valid                       -> status ambang sesuai persentase
     *
     * Seluruh rentang & warna berasal dari tabel `dashboard_status_thresholds`.
     *
     * @param array<string, mixed> $ringkas
     *
     * @return array<string, mixed>
     */
    public function getOpdControlStatus(array $ringkas): array
    {
        $total   = (int) $ringkas['indikator'];
        $valid   = (int) $ringkas['valid'];
        $invalid = $total - $valid;
        $kritis  = (int) $ringkas['kritis'];
        // Sengaja TIDAK ikut menentukan status KINERJA — lihat catatan di
        // bawah. Tetap dipakai untuk melengkapi kalimat alasan.
        $belumUpdate = (bool) ($ringkas['update']['belum_update'] ?? false);

        $bungkus = static function (array $status, string $alasan, bool $karenaData = false) use ($kritis, $invalid, $ringkas): array {
            return $status + [
                'reason'                   => $alasan,
                // Membedakan "kritis karena kinerja" (persentase memang rendah)
                // dari "kritis karena data" (ada indikator kritis TAPI datanya
                // belum diperbarui, jadi persentase OPD-nya belum ada).
                // Sejak "Kritis" tidak lagi boleh lahir dari data yang belum
                // lengkap, nilainya selalu false. Kuncinya dipertahankan agar
                // pembacanya (rekap & view) tidak perlu ikut diubah.
                'critical_by_data'         => $karenaData,
                'critical_indicator_count' => $kritis,
                'invalid_indicator_count'  => $invalid,
                'late_update_count'        => (int) ($ringkas['indikator_belum_input'] ?? 0),
            ];
        };

        if ($total === 0) {
            return $bungkus(dash_status_nonnumeric('belum_ada_data'), 'Belum ada indikator Perjanjian Kinerja pimpinan.');
        }

        // =============================================================
        // "KRITIS" HANYA UNTUK KINERJA, BUKAN UNTUK DATA YANG BELUM LENGKAP
        //
        // Di sini dulu ada satu cabang lagi: bila OPD punya indikator kritis
        // DAN datanya belum diperbarui, statusnya dipaksa menjadi Kritis dan
        // ditandai `critical_by_data`.
        //
        // Penandanya benar, tetapi labelnya terlanjur terbaca. Yang sampai ke
        // pimpinan adalah kata "Kritis" — dan sebuah OPD yang sebenarnya belum
        // sempat mengisi MONEV tampil sederajat dengan OPD yang capaiannya
        // memang buruk. Flag internal tidak pernah bisa menahan makna sebuah
        // label yang sudah tercetak di layar.
        //
        // Cabang itu dihapus, dan penilaiannya jatuh ke aturan yang memang
        // sudah ada di bawah:
        //
        //   ada indikator belum dapat dihitung -> Belum Valid
        //   seluruhnya valid & ada yang kritis -> Perlu Perhatian
        //   seluruhnya valid                   -> status ambang capaian
        //
        // Sinyal kritisnya TIDAK hilang: `critical_indicator_count` tetap
        // dibawa, indikator kritis tetap muncul di Prioritas Pimpinan, dan
        // keterlambatan pembaruan tetap dilaporkan lewat kartu tersendiri
        // beserta `late_update_count`.
        // =============================================================

        if ($kritis > 0) {
            // "Minimal Perlu Perhatian": indikator kritis tidak boleh tertutup
            // oleh rata-rata yang terlihat baik.
            return $bungkus(
                $this->statusAmbang('attention'),
                $kritis . ' dari ' . $total . ' indikator berstatus kritis.'
            );
        }

        if ($invalid > 0) {
            // "Sebagian besar" hanya masuk akal bila memang ada sebagian yang
            // lain. Saat SELURUH indikator tidak valid — kasus paling umum —
            // kalimatnya harus menyebut seluruhnya, bukan "sebagian besar
            // (1 dari 1)".
            if ($invalid === $total) {
                $alasan = $total === 1
                    ? 'Satu-satunya indikator belum dapat dihitung.'
                    : 'Seluruh ' . $total . ' indikator belum dapat dihitung.';
            } elseif ($invalid / $total >= self::AMBANG_BELUM_VALID) {
                $alasan = 'Sebagian besar indikator (' . $invalid . ' dari ' . $total . ') belum dapat dihitung.';
            } else {
                $alasan = $invalid . ' dari ' . $total . ' indikator belum dapat dihitung.';
            }

            // Keterangan keterlambatan yang dulu dibawa cabang "kritis karena
            // data" tidak ikut hilang — ia menempel di sini, pada status yang
            // memang jujur menyatakan capaiannya belum bisa dinilai.
            if ($belumUpdate) {
                $alasan .= ' Data belum diperbarui (' . $ringkas['update']['keterangan'] . ').';
            }

            if ($kritis > 0) {
                $alasan .= ' ' . $kritis . ' indikator berstatus kritis.';
            }

            return $bungkus(dash_status_nonnumeric('belum_valid'), $alasan);
        }

        return $bungkus(
            getAchievementStatus((float) $ringkas['percentage']),
            'Seluruh indikator valid, capaian ' . capaianFormatPersen($ringkas['percentage']) . '.'
        );
    }

    /**
     * Status ambang bernama, dibaca dari tabel `dashboard_status_thresholds`
     * (bukan hardcode, dan bukan hasil memalsukan persentase).
     *
     * Bila Super Admin menonaktifkan ambang yang diminta, jatuh ke ambang
     * terendah yang masih aktif agar tetap ada penanda kewaspadaan.
     */
    private function statusAmbang(string $code): array
    {
        $aktif = dash_threshold_rows();

        foreach ($aktif as $t) {
            if ($t['code'] === $code) {
                return dash_status_from_row($t);
            }
        }

        if ($aktif === []) {
            return dash_status_nonnumeric('belum_valid');
        }

        return dash_status_from_row($code === 'critical'
            ? $aktif[0]
            : ($aktif[min(1, count($aktif) - 1)] ?? $aktif[0]));
    }

    /**
     * Kartu 2 — ringkasan status seluruh Perangkat Daerah.
     *
     * @param array<int, array<string, mixed>> $statuses
     *
     * @return array<string, mixed>
     */
    public function getOpdStatusSummary(array $statuses): array
    {
        $out = [
            'total'        => count($statuses),
            'dapat_dinilai' => 0,
            'belum_lengkap' => 0,
            'per_status'   => [],
        ];

        $out['kritis_data'] = 0;

        foreach ($statuses as $s) {
            if ($s['can_compute']) {
                $out['dapat_dinilai']++;
            } else {
                $out['belum_lengkap']++;
            }
            $code = $s['status']['code'];
            $out['per_status'][$code] = ($out['per_status'][$code] ?? 0) + 1;
            // OPD yang berstatus kritis PADAHAL capaiannya belum bisa dihitung
            // (kritis karena data belum diperbarui) dilaporkan terpisah supaya
            // tidak terbaca sebagai kinerja yang benar-benar rendah.
            if ($code === 'critical' && !empty($s['status']['critical_by_data'])) {
                $out['kritis_data']++;
            }
        }

        $out['kritis']         = $out['per_status']['critical'] ?? 0;
        $out['kritis_kinerja'] = $out['kritis'] - $out['kritis_data'];
        $out['perhatian']  = $out['per_status']['attention'] ?? 0;
        $out['terkendali'] = ($out['per_status']['near_target'] ?? 0)
            + ($out['per_status']['achieved'] ?? 0)
            + ($out['per_status']['exceeded'] ?? 0);
        $out['belum_valid'] = ($out['per_status']['belum_valid'] ?? 0) + ($out['per_status']['belum_ada_data'] ?? 0);

        return $out;
    }

    /**
     * Kartu 3 — OPD yang belum memperbarui data.
     *
     * @param array<int, array<string, mixed>> $statuses
     *
     * @return array<string, mixed>
     */
    public function getUnupdatedOpds(array $statuses): array
    {
        $daftar = [];
        $belumPeriode = 0;
        $belumLengkap = 0;
        $terlambat = 0;
        $belumPernah = 0;
        $indikatorBelum = 0;

        foreach ($statuses as $s) {
            $indikatorBelum += (int) $s['indikator_belum_input'];
            if (!$s['update']['belum_update']) {
                continue;
            }
            $daftar[] = $s;
            // Empat kondisi ini butuh tindak lanjut yang BERBEDA, jadi tidak
            // boleh dilebur ke satu angka: belum pernah input sama sekali,
            // belum menyentuh periode ini, baru sebagian, atau sudah lengkap
            // tapi lama tidak disentuh.
            switch ($s['update']['code']) {
                case 'belum_pernah':  $belumPernah++;  break;
                case 'belum_periode': $belumPeriode++; break;
                case 'belum_lengkap': $belumLengkap++; break;
                case 'terlambat':     $terlambat++;    break;
            }
        }

        usort($daftar, static fn ($a, $b) => ($b['indikator_belum_input'] <=> $a['indikator_belum_input'])
            ?: strcmp((string) $a['nama_opd'], (string) $b['nama_opd']));

        return [
            'total'             => count($daftar),
            'belum_pernah'      => $belumPernah,
            'belum_periode'     => $belumPeriode,
            'belum_lengkap'     => $belumLengkap,
            'terlambat'         => $terlambat,
            'indikator_belum'   => $indikatorBelum,
            'batas_hari'        => self::HARI_TERLAMBAT,
            'daftar'            => $daftar,
        ];
    }

    /**
     * Grafik 1 — distribusi status seluruh OPD.
     *
     * Satu OPD masuk tepat satu segmen. Kategori "Belum Update" diletakkan
     * paling akhir dan hanya dipakai untuk OPD yang datanya belum bisa dinilai
     * KARENA belum diperbarui (bukan menimpa status kritis yang sudah pasti).
     *
     * @param array<int, array<string, mixed>> $statuses
     *
     * @return array<string, mixed>
     */
    public function getOpdStatusDistribution(array $statuses): array
    {
        $segmen = [];
        foreach (dash_threshold_rows() as $t) {
            $s = dash_status_from_row($t);
            $segmen[$s['code']] = ['code' => $s['code'], 'name' => $s['name'], 'color' => $s['color_hex'], 'count' => 0];
        }
        foreach (['belum_valid', 'belum_ada_data'] as $kode) {
            $ns = dash_status_nonnumeric($kode === 'belum_ada_data' ? 'belum_ada_data' : 'belum_valid');
            $segmen[$kode] = ['code' => $kode, 'name' => $ns['name'], 'color' => $ns['color_hex'], 'count' => 0];
        }
        $segmen['belum_update'] = ['code' => 'belum_update', 'name' => 'Belum Update', 'color' => dash_color('oranye')['hex'], 'count' => 0];

        foreach ($statuses as $s) {
            $code = $s['status']['code'];
            if (in_array($code, ['belum_valid', 'belum_ada_data'], true) && $s['update']['belum_update']) {
                $segmen['belum_update']['count']++;
                continue;
            }
            if (!isset($segmen[$code])) {
                $segmen[$code] = ['code' => $code, 'name' => $s['status']['name'], 'color' => $s['status']['color_hex'], 'count' => 0];
            }
            $segmen[$code]['count']++;
        }

        // "Dinilai" hanya sah untuk OPD yang punya indikator PK; yang tidak
        // punya dokumen PK sama sekali dilaporkan terpisah, bukan diklaim
        // ikut dinilai.
        $tanpaPk = 0;
        foreach ($statuses as $s) {
            if ((int) $s['indikator'] === 0) {
                $tanpaPk++;
            }
        }
        $dinilai = count($statuses) - $tanpaPk;

        $caption = $dinilai . ' dari ' . count($statuses) . ' Perangkat Daerah punya indikator PK pada periode ini';
        $caption .= $tanpaPk > 0
            ? ' — ' . $tanpaPk . ' belum memiliki dokumen PK.'
            : '.';

        return [
            'segments'  => array_values(array_filter($segmen, static fn ($s) => $s['count'] > 0)),
            'total'     => count($statuses),
            'dinilai'   => $dinilai,
            'tanpa_pk'  => $tanpaPk,
            'caption'   => $caption,
        ];
    }

    /**
     * Grafik 2 — tren indikator PK Bupati (target vs realisasi triwulanan).
     *
     * Realisasi PK diambil dari MONEV rencana aksi PK Bupati. Bila indikator
     * belum punya rencana aksi/MONEV, serinya tetap dikirim dengan penanda
     * `tersedia = false` supaya tampilan menyatakan "belum tersedia", bukan
     * menampilkan angka karangan.
     *
     * @param array<int, array<string, mixed>> $indikator
     *
     * @return array<int, array<string, mixed>>
     */
    public function getBupatiIndicatorTrend(array $indikator): array
    {
        $seri = [];
        foreach ($this->opd->getQuarterlyOptions($indikator) as $s) {
            $s['tersedia'] = true;
            $seri[] = $s;
        }

        // Indikator tanpa baris ukur sama sekali tetap muncul di dropdown,
        // ditandai belum tersedia.
        $sudah = array_column($seri, 'indikator_id');
        foreach ($indikator as $i) {
            if (in_array($i['indikator_id'], $sudah, true)) {
                continue;
            }
            $seri[] = [
                'key'          => $i['indikator_id'] . '-0-0',
                'indikator_id' => $i['indikator_id'],
                'indikator'    => $i['indikator'],
                'label'        => $i['indikator'],
                'satuan'       => $i['satuan'],
                'status'       => $i['status']['code'],
                'is_valid'     => false,
                'misi'         => $i['misi'] !== [],
                'tersedia'     => false,
                'alasan'       => (string) ($i['validity']['reason'] ?? 'Realisasi PK belum tersedia.'),
                'series'       => [
                    'target'        => [null, null, null, null],
                    'capaian'       => [null, null, null, null],
                    'label_target'  => [null, null, null, null],
                    'label_capaian' => [null, null, null, null],
                    'predikat'      => false,
                    'metode'        => null,
                    'metode_nama'   => '-',
                ],
            ];
        }

        return $seri;
    }

    /**
     * Panel prioritas pimpinan — rule-based, diurutkan dari risiko tertinggi.
     *
     * @param array<string, mixed>             $pkBupati
     * @param array<int, array<string, mixed>> $statuses
     *
     * @return array<int, array<string, mixed>>
     */
    public function getLeadershipPriorities(array $pkBupati, array $statuses, int $tahun, int $triwulan): array
    {
        $out = [];
        // Tautan mengikuti area rute aktif ('adminkab' atau 'bupati') supaya
        // role bupati tidak pernah diarahkan ke halaman administratif.
        $urlBupatiMonev = base_url($this->linkArea . '/monev?tahun=' . $tahun);
        $urlBupatiRen   = base_url($this->linkArea . '/target_renaksi?tahun=' . $tahun);
        $tw             = capaianRomawi($triwulan);

        // 1. Indikator PK Bupati kritis (isu KINERJA - paling atas)
        foreach ($pkBupati['indikator'] as $i) {
            if (!$i['is_valid'] || $i['status']['code'] !== 'critical') {
                continue;
            }
            $out[] = $this->insight(self::SEV_PK_BUPATI_KRITIS, 'pk_bupati_kritis', $i['indikator'],
                'Realisasi PK berada di bawah target periode (' . $i['percentage_teks'] . ').',
                $i['status']['name'], $i['status']['color'],
                $i['pengampu'] === [] ? 'OPD pengampu belum ditetapkan' : count($i['pengampu']) . ' OPD pengampu',
                $urlBupatiMonev, 'Lihat indikator', ['indikator_id' => $i['indikator_id']]);
        }

        // 2. OPD kritis (isu KINERJA)
        foreach ($statuses as $s) {
            if ($s['status']['code'] !== 'critical') {
                continue;
            }
            $out[] = $this->insight(self::SEV_OPD_KRITIS, 'opd_kritis', $s['nama_opd'],
                (string) $s['status']['reason'], $s['status']['name'], $s['status']['color'],
                $s['kritis'] . ' indikator kritis dari ' . $s['indikator'] . ' indikator.',
                $this->urlFokus($s['opd_id'], $tahun, $triwulan), 'Fokus OPD', ['opd_id' => $s['opd_id']]);
        }

        // 3. Penyerapan tinggi tetapi capaian rendah (KINERJA vs ANGGARAN)
        foreach ($statuses as $s) {
            if (!$s['can_compute'] || $s['penyerapan'] === null) {
                continue;
            }
            if ((float) $s['penyerapan'] < self::PENYERAPAN_TINGGI) {
                continue;
            }
            if (!in_array($s['status']['code'], ['critical', 'attention'], true)) {
                continue;
            }
            $out[] = $this->insight(self::SEV_SERAP_TINGGI, 'serap_tinggi_capaian_rendah', $s['nama_opd'],
                'Penyerapan anggaran ' . capaianFormatPersen($s['penyerapan'])
                    . ' sementara capaian kinerja ' . capaianFormatPersen($s['percentage']) . '.',
                'Perlu ditinjau', 'biru',
                'Bandingkan pada analisis anggaran dan kinerja.',
                $this->urlFokus($s['opd_id'], $tahun, $triwulan), 'Fokus OPD', ['opd_id' => $s['opd_id']]);
        }

        // 4 & 5. Kelengkapan dokumen PK Bupati - DIRINGKAS.
        //
        // Satu sebab sistemik (mis. seluruh indikator PK Bupati belum punya
        // Rencana Aksi) dulu melahirkan satu kartu per indikator, sehingga
        // panel "Lima Prioritas" terisi lima baris berkalimat persis sama dan
        // tidak menyisakan ruang untuk isu OPD. Sekarang digabung menjadi SATU
        // baris bila lebih dari satu indikator mengalaminya.
        $gapFormula = [];
        $gapData    = [];
        foreach ($pkBupati['indikator'] as $i) {
            if ($i['is_valid']) {
                continue;
            }
            if (in_array((string) $i['reason_code'], ['missing_method', 'missing_formula', 'missing_predicate_scale'], true)) {
                $gapFormula[] = $i;
            } else {
                $gapData[] = $i;
            }
        }

        foreach ($this->insightGapPkBupati($gapFormula, 'pk_bupati_formula', self::SEV_PK_BUPATI_FORMULA,
            'Formula belum tersedia', 'metode/rumus perhitungan belum tersedia',
            $urlBupatiMonev, 'Lihat formula') as $ins) {
            $out[] = $ins;
        }

        foreach ($this->insightGapPkBupati($gapData, 'pk_bupati_belum_valid', self::SEV_PK_BUPATI_DATA,
            'Belum dapat dihitung', 'Rencana Aksi / capaian belum lengkap',
            $urlBupatiRen, $this->linkArea === 'bupati' ? 'Lihat Rencana Aksi' : 'Kelola Rencana Aksi') as $ins) {
            $out[] = $ins;
        }

        // 6. OPD belum update - keterangannya mengikuti KONDISI SEBENARNYA.
        foreach ($statuses as $s) {
            if (!$s['update']['belum_update'] || $s['status']['code'] === 'critical') {
                continue;
            }
            $out[] = $this->insight(self::SEV_OPD_BELUM_UPDATE, 'opd_belum_update', $s['nama_opd'],
                (string) $s['update']['keterangan'], $s['update']['label'], 'oranye',
                $this->objekBelumUpdate($s, $tw),
                $this->urlFokus($s['opd_id'], $tahun, $triwulan), 'Fokus OPD', ['opd_id' => $s['opd_id']]);
        }

        // 7. Status verifikasi.
        //
        // Dulu butir ini justru muncul ketika mekanisme verifikasi BELUM ada —
        // artinya ia selalu nongol, menyebut seluruh nilai PK Bupati "sementara",
        // padahal tidak ada satu pun tindakan yang bisa dikerjakan pimpinan untuk
        // menutupnya. Di daftar Prioritas Tindak Lanjut itu terbaca sebagai
        // tunggakan palsu. Sekarang butir ini hanya terbit bila mekanismenya ada
        // dan memang ada yang belum diverifikasi.
        // Lihat OpdDashboardService::verificationInfo().
        $verifikasi = $this->opd->verificationInfo();
        if ($verifikasi['available'] && ($pkBupati['belum_verifikasi'] ?? 0) > 0) {
            $out[] = $this->insight(self::SEV_VERIFIKASI, 'verifikasi', 'Status verifikasi capaian',
                $verifikasi['note'], $verifikasi['label'], 'abu',
                (int) $pkBupati['belum_verifikasi'] . ' nilai PK Bupati belum diverifikasi.',
                $urlBupatiMonev, 'Buka MONEV', []);
        }

        usort($out, static fn ($a, $b) => $a['severity'] <=> $b['severity'] ?: strcmp($a['judul'], $b['judul']));

        return $out;
    }

    /**
     * Baris prioritas untuk gap kelengkapan PK Bupati.
     *
     * Satu indikator -> satu baris seperti biasa. Lebih dari satu -> SATU baris
     * ringkas, karena lima baris berkalimat identik tidak menambah informasi
     * apa pun bagi pimpinan dan menutupi isu Perangkat Daerah.
     *
     * @param array<int, array<string, mixed>> $daftar
     *
     * @return array<int, array<string, mixed>>
     */
    private function insightGapPkBupati(
        array $daftar,
        string $code,
        int $severity,
        string $status,
        string $ringkasan,
        string $url,
        string $tombol
    ): array {
        if ($daftar === []) {
            return [];
        }

        if (count($daftar) === 1) {
            $i = $daftar[0];

            return [$this->insight($severity, $code, $i['indikator'],
                (string) $i['reason'], $status, 'abu',
                $i['renaksi_count'] === 0
                    ? 'Rencana Aksi PK Bupati belum disusun.'
                    : 'Rencana Aksi sudah ada, capaian belum lengkap.',
                $url, $tombol, ['indikator_id' => $i['indikator_id']])];
        }

        // Alasan yang paling sering muncul dipakai sebagai kalimat utama;
        // sisanya cukup disebut jumlahnya, bukan diulang satu per satu.
        $alasan       = [];
        $tanpaRenaksi = 0;
        foreach ($daftar as $i) {
            $teks          = (string) ($i['reason'] ?? '');
            $alasan[$teks] = ($alasan[$teks] ?? 0) + 1;
            if ($i['renaksi_count'] === 0) {
                $tanpaRenaksi++;
            }
        }
        arsort($alasan);
        $utama = (string) array_key_first($alasan);
        if (count($alasan) > 1) {
            $utama .= ' (' . (count($alasan) - 1) . ' alasan lain pada indikator sisanya)';
        }

        $objek = $tanpaRenaksi === count($daftar)
            ? 'Seluruhnya belum memiliki Rencana Aksi PK Bupati.'
            : $tanpaRenaksi . ' dari ' . count($daftar) . ' belum memiliki Rencana Aksi PK Bupati.';

        return [$this->insight($severity, $code,
            count($daftar) . ' indikator PK Bupati: ' . $ringkasan,
            $utama, $status, 'abu', $objek, $url, $tombol,
            ['indikator_id' => array_column($daftar, 'indikator_id')])];
    }

    /**
     * Kalimat objek untuk prioritas "OPD belum update".
     *
     * DULU selalu memakai `indikator_belum_input`, padahal untuk status
     * `terlambat` angka itu SELALU 0 - justru artinya periode ini sudah
     * terisi lengkap, hanya sudah lama tidak disentuh. Akibatnya panel
     * menulis "0 indikator belum diperbarui".
     *
     * @param array<string, mixed> $s
     */
    private function objekBelumUpdate(array $s, string $tw): string
    {
        $belum = (int) $s['indikator_belum_input'];
        $total = (int) $s['indikator'];

        switch ((string) $s['update']['code']) {
            case 'belum_pernah':
                return $total . ' indikator belum pernah diinput MONEV.';

            case 'belum_periode':
                return 'Seluruh ' . $total . ' indikator belum diisi pada Triwulan ' . $tw . '.';

            case 'belum_lengkap':
                return $belum . ' dari ' . $total . ' indikator belum diisi pada Triwulan ' . $tw . '.';

            case 'terlambat':
                $hari = $s['hari_sejak_update'];

                return 'Triwulan ' . $tw . ' sudah terisi lengkap, tetapi tidak ada pembaruan '
                    . ($hari === null ? 'sejak lama' : 'selama ' . (int) $hari . ' hari') . '.';
        }

        return $belum . ' indikator belum diperbarui.';
    }

    /** @return array<string, mixed> */
    private function insight(
        int $severity,
        string $code,
        string $judul,
        string $alasan,
        string $status,
        string $warna,
        string $objek,
        string $url,
        string $tombol,
        array $ref
    ): array {
        return [
            'severity' => $severity,
            'code'     => $code,
            'judul'    => $judul,
            'alasan'   => $alasan,
            'status'   => $status,
            'objek'    => $objek,
            'color'    => dash_color($warna),
            'url'      => $url,
            'tombol'   => $tombol,
            'ref'      => $ref,
        ];
    }

    /** URL dashboard ini dengan Mode Fokus OPD aktif (tetap di area rute aktif). */
    public function urlFokus(int $opdId, int $tahun, int $triwulan): string
    {
        return base_url($this->linkArea . '/dashboard?opd_id=' . $opdId . '&tahun=' . $tahun . '&triwulan=' . $triwulan);
    }

    /**
     * Panel kontribusi OPD terhadap Misi Bupati.
     *
     * Relasi yang dipakai (keduanya relasi database, bukan pencocokan teks):
     *   - pk -> pk_misi -> rpjmd_misi                       (pemetaan langsung)
     *   - renstra_sasaran(opd) -> renstra_tujuan.rpjmd_sasaran_id
     *     -> rpjmd_sasaran -> rpjmd_tujuan -> rpjmd_misi     (cadangan, ditandai)
     *
     * @param array<int, array<string, mixed>> $statuses
     * @param array<int, array<string, mixed>> $indikatorBupati
     *
     * @return array<string, mixed>
     */
    public function getMissionContributions(int $tahun, int $triwulan, array $statuses, array $indikatorBupati): array
    {
        $misiRows = $this->misiOptions($tahun);
        $items    = [];
        foreach ($misiRows as $idx => $m) {
            $items[(int) $m['id']] = [
                'misi_id'          => (int) $m['id'],
                'nomor'            => $idx + 1,
                'misi'             => (string) $m['misi'],
                'indikator_bupati' => 0,
                'opd'              => [],
                'indikator_opd'    => 0,
                'kritis'           => 0,
                'belum_update'     => 0,
                // Kontribusi yang keterkaitannya hanya bisa DITAKSIR (Renstra
                // OPD menyentuh beberapa misi sekaligus). Dipisah supaya angka
                // utama tidak pernah melebihi jumlah indikator yang nyata ada.
                'opd_taksir'       => 0,
                'indikator_taksir' => 0,
                'sumber'           => [],
            ];
        }

        // Indikator PK Bupati per misi (lewat pk_misi dokumen PK Bupati).
        // `misi` bisa absen bila pemanggil mengirim bentuk indikator yang sudah
        // diringkas untuk kartu; itu bukan alasan untuk melempar error.
        foreach ($indikatorBupati as $i) {
            foreach ($i['misi'] ?? [] as $m) {
                $id = (int) $m['misi_id'];
                if (!isset($items[$id])) {
                    continue;
                }
                $items[$id]['indikator_bupati']++;
                $items[$id]['sumber'][$m['sumber']] = true;
            }
        }

        // OPD pengampu per misi (lewat pk_misi PK pimpinan OPD / rantai Renstra).
        $indikatorPerOpd = $this->indikatorMisiPerOpd($statuses, $tahun, $triwulan);
        foreach ($indikatorPerOpd as $opdId => $perMisi) {
            $s = $statuses[$opdId] ?? null;
            if ($s === null) {
                continue;
            }
            foreach ($perMisi as $misiId => $data) {
                if (!isset($items[$misiId])) {
                    continue;
                }
                $items[$misiId]['opd'][] = [
                    'opd_id'    => $opdId,
                    'nama_opd'  => $s['nama_opd'],
                    'indikator' => $data['jumlah'],
                    'kritis'    => $data['kritis'],
                    'valid'     => $data['valid'],
                    'taksiran'  => $data['ambigu'],
                    'status'    => $s['status'],
                    'update'    => $s['update'],
                ];
                // OPD-nya tetap dicatat sebagai pengampu misi ini (itu memang
                // benar), tetapi JUMLAH indikator & indikator kritisnya hanya
                // masuk angka utama bila pemetaannya tegas. Bila tidak, satu
                // indikator yang sama akan terhitung di beberapa misi sekaligus
                // - dulu itulah sebabnya total per misi (156) melampaui jumlah
                // indikator yang sebenarnya ada (140).
                if ($data['ambigu']) {
                    $items[$misiId]['opd_taksir']++;
                    $items[$misiId]['indikator_taksir'] += $data['jumlah'];
                } else {
                    $items[$misiId]['indikator_opd'] += $data['jumlah'];
                    $items[$misiId]['kritis']        += $data['kritis'];
                }
                $items[$misiId]['sumber'][$data['sumber']] = true;
                if ($s['update']['belum_update']) {
                    $items[$misiId]['belum_update']++;
                }
            }
        }

        foreach ($items as $id => $it) {
            $items[$id]['opd_count'] = count($it['opd']);
            $items[$id]['sumber']    = array_keys($it['sumber']);
        }

        $taksir = array_sum(array_column($items, 'indikator_taksir'));

        return [
            'items' => array_values($items),
            // Gap yang tidak disembunyikan: dokumen PK Bupati belum dipetakan
            // ke Misi RPJMD lewat pk_misi.
            'gap_pk_bupati' => array_sum(array_column($items, 'indikator_bupati')) === 0 && $indikatorBupati !== [],
            // Gap kedua: PK OPD yang keterkaitan misinya hanya bisa ditaksir.
            'indikator_taksir' => $taksir,
            'catatan_taksir'   => $taksir === 0 ? '' : $taksir . ' indikator berasal dari Perangkat Daerah yang '
                . 'Renstra-nya menyentuh lebih dari satu Misi, sehingga keterkaitannya belum dapat ditetapkan '
                . 'ke satu misi tertentu. Angka itu tidak ikut dijumlahkan agar total per misi tidak melebihi '
                . 'jumlah indikator yang sebenarnya ada. Lengkapi relasi pk_misi pada dokumen PK untuk memastikannya.',
        ];
    }

    /**
     * Memoisasi pemuatan indikator seluruh OPD: satu kombinasi (OPD, tahun,
     * triwulan) hanya diambil sekali per request meski dipakai beberapa panel.
     *
     * @param int[] $opdIds
     *
     * @return array<int, array<int, array<string, mixed>>>
     */
    private function indikatorSeluruhOpd(array $opdIds, int $tahun, int $triwulan): array
    {
        sort($opdIds);
        $kunci = $tahun . ':' . $triwulan . ':' . md5(implode(',', $opdIds));

        return $this->cacheIndikator[$kunci]
            ??= $this->opd->loadIndicatorsForOpds($opdIds, $tahun, $triwulan);
    }

    /**
     * @param array<int, array<string, mixed>> $statuses
     *
     * @return array<int, array<int, array<string, mixed>>> [opd_id => [misi_id => data]]
     */
    private function indikatorMisiPerOpd(array $statuses, int $tahun, int $triwulan): array
    {
        if ($statuses === []) {
            return [];
        }

        // Sengaja memakai daftar OPD penuh agar memoisasinya sama dengan
        // getOpdStatuses (satu pemuatan untuk seluruh panel).
        $perOpd = $this->indikatorSeluruhOpd(
            array_map('intval', array_column($this->opdOptions(), 'id')),
            $tahun,
            $triwulan
        );

        $out = [];
        foreach ($perOpd as $opdId => $daftar) {
            if (!isset($statuses[$opdId])) {
                continue;
            }
            foreach ($daftar as $i) {
                foreach ($i['misi'] as $m) {
                    $id = (int) $m['misi_id'];
                    if (!isset($out[$opdId][$id])) {
                        $out[$opdId][$id] = [
                            'jumlah' => 0,
                            'kritis' => 0,
                            'valid'  => 0,
                            'sumber' => $m['sumber'],
                            'ambigu' => !empty($m['ambigu']),
                        ];
                    }
                    if (empty($m['ambigu'])) {
                        $out[$opdId][$id]['ambigu'] = false;
                    }
                    $out[$opdId][$id]['jumlah']++;
                    if ($i['validity']['is_valid']) {
                        $out[$opdId][$id]['valid']++;
                        if ($i['status']['code'] === 'critical') {
                            $out[$opdId][$id]['kritis']++;
                        }
                    }
                }
            }
        }

        return $out;
    }

    /* =====================================================================
     * DRILL-DOWN
     * ===================================================================*/

    /**
     * Detail satu indikator PK Bupati + jalur turunannya ke OPD.
     *
     * Jalur yang dipakai: PK Bupati -> sasaran -> pk_sasaran_opd (OPD pengampu)
     * -> PK pimpinan OPD -> indikator OPD. Relasi `pk_referensi` dipakai untuk
     * menunjukkan turunan di DALAM OPD (Eselon II -> III -> IV); pada data ini
     * pk_referensi TIDAK pernah menunjuk ke PK Bupati, sehingga jalur
     * PK Bupati -> PK OPD hanya tersedia lewat pk_sasaran_opd. Bila belum
     * ditetapkan, gap-nya dilaporkan apa adanya.
     *
     * @return array<string, mixed>|null
     */
    public function getBupatiIndicatorDetail(int $indikatorId, int $tahun, int $triwulan): ?array
    {
        $rows = $this->opd->loadBupatiIndicators($tahun, $triwulan, $indikatorId);
        if ($rows === []) {
            return null;
        }
        $i = $rows[0];

        $pengampu = $this->opdPengampuSasaran([$i['sasaran_id']])[(int) $i['sasaran_id']] ?? [];
        $opdIds   = array_map('intval', array_column($pengampu, 'opd_id'));
        $perOpd   = $opdIds === [] ? [] : $this->indikatorSeluruhOpd($opdIds, $tahun, $triwulan);

        $rantai = [];
        foreach ($pengampu as $p) {
            $daftar = $perOpd[(int) $p['opd_id']] ?? [];
            $rantai[] = [
                'opd_id'   => (int) $p['opd_id'],
                'nama_opd' => $p['nama'],
                'tersedia' => $daftar !== [],
                'indikator' => array_map(function ($x) use ($tahun, $triwulan) {
                    return [
                        'indikator_id' => $x['indikator_id'],
                        'indikator'    => $x['indikator'],
                        'percentage_teks' => $x['percentage'] !== null ? capaianFormatPersen($x['percentage']) : null,
                        'status'       => $x['status'],
                        'is_valid'     => $x['validity']['is_valid'],
                        'reason'       => $x['validity']['reason'],
                        'renaksi_count' => $x['renaksi_count'],
                        'turunan'      => 0,
                        'url_fokus'    => $this->urlFokus((int) $x['opd_id'], $tahun, $triwulan),
                    ];
                }, $daftar),
                'url_fokus' => $this->urlFokus((int) $p['opd_id'], $tahun, $triwulan),
            ];
        }

        // Jumlah indikator turunan (Eselon III/IV) tiap indikator OPD.
        $semuaIndikatorOpd = [];
        foreach ($rantai as $r) {
            foreach ($r['indikator'] as $x) {
                $semuaIndikatorOpd[] = $x['indikator_id'];
            }
        }
        $turunan = $this->hitungTurunanPk($semuaIndikatorOpd);
        foreach ($rantai as $ri => $r) {
            foreach ($r['indikator'] as $xi => $x) {
                $rantai[$ri]['indikator'][$xi]['turunan'] = $turunan[$x['indikator_id']] ?? 0;
            }
        }

        return [
            'indikator_id'   => $i['indikator_id'],
            'indikator'      => $i['indikator'],
            'sasaran'        => $i['sasaran'],
            'satuan'         => $i['satuan'],
            'target_tahunan' => $i['target_tahunan'],
            'metode_nama'    => capaianMetodeRingkas($i['rows'] ?? []),
            'percentage'     => $i['percentage'],
            'percentage_teks' => $i['percentage'] !== null ? capaianFormatPersen($i['percentage']) : null,
            'status'         => $i['status'],
            'validity'       => $i['validity'],
            'verification'   => $i['verification'],
            'misi'           => $i['misi'],
            'rows'           => $i['rows'],
            'programs'       => $i['programs'],
            'anggaran_teks'  => formatRupiah($i['anggaran']),
            'realisasi_teks' => $i['realisasi'] !== null ? formatRupiah($i['realisasi']) : null,
            'updated_at'     => $i['updated_at'],
            'pengampu'       => $rantai,
            'gap_relasi'     => $pengampu === [],
            'gap_pesan'      => 'Relasi PK OPD belum tersedia — OPD pengampu sasaran PK Bupati ini belum ditetapkan.',
            'links'          => [
                'renaksi' => base_url('adminkab/target_renaksi?tahun=' . $tahun),
                'monev'   => base_url('adminkab/monev?tahun=' . $tahun),
                'pd'      => base_url('adminkab/target_renaksi/pd/' . (int) $i['sasaran_id']),
                'lakip'   => base_url('adminkab/lakip?tahun=' . $tahun),
            ],
        ];
    }

    /**
     * Jumlah indikator PK bawahan yang merujuk sebuah indikator PK atasan.
     *
     * @param int[] $indikatorIds
     *
     * @return array<int, int>
     */
    private function hitungTurunanPk(array $indikatorIds): array
    {
        $indikatorIds = array_values(array_unique(array_filter(array_map('intval', $indikatorIds))));
        if ($indikatorIds === [] || !$this->db->tableExists('pk_referensi')) {
            return [];
        }

        $rows = $this->db->table('pk_referensi')
            ->select('referensi_indikator_id, COUNT(*) AS jumlah')
            ->whereIn('referensi_indikator_id', $indikatorIds)
            ->groupBy('referensi_indikator_id')
            ->get()->getResultArray();

        $map = [];
        foreach ($rows as $r) {
            $map[(int) $r['referensi_indikator_id']] = (int) $r['jumlah'];
        }

        return $map;
    }

    /**
     * Mode Fokus OPD — memakai perhitungan yang sama persis dengan dashboard
     * OPD, hanya tautan tindak lanjutnya diarahkan ke area /adminkab.
     *
     * @return array<string, mixed>
     */
    public function getOpdFocusDashboard(int $opdId, int $tahun, int $triwulan): array
    {
        $ringkas = $this->opd->getSummary($opdId, $tahun, $triwulan);
        $ringkas['mode']  = 'fokus_opd';
        $ringkas['links'] = $this->opd->moduleLinks($tahun, (string) $ringkas['context']['jenis'], $opdId);

        return $ringkas;
    }

    /**
     * Analisis anggaran vs kinerja (scatter). Hanya OPD yang capaian, anggaran,
     * dan realisasinya sah yang menjadi titik; sisanya dilaporkan terpisah
     * beserta alasannya. Tidak ada pelabelan "efisien" otomatis.
     *
     * @param array<int, array<string, mixed>> $statuses
     *
     * @return array<string, mixed>
     */
    public function getBudgetPerformanceComparison(array $statuses): array
    {
        $titik = [];
        $dikecualikan = [];

        foreach ($statuses as $s) {
            $alasan = null;
            if (!$s['can_compute']) {
                $alasan = 'Capaian OPD belum dapat dihitung (' . $s['belum_valid'] . ' indikator belum valid).';
            } elseif ((float) $s['anggaran'] <= 0) {
                $alasan = 'Pagu anggaran pada Perjanjian Kinerja belum tersedia.';
            } elseif ($s['realisasi'] === null) {
                $alasan = 'Realisasi anggaran belum dilaporkan.';
            }

            if ($alasan !== null) {
                $dikecualikan[] = [
                    'opd_id'   => $s['opd_id'],
                    'nama_opd' => $s['nama_opd'],
                    'alasan'   => $alasan,
                    'status'   => $s['status'],
                ];
                continue;
            }

            $titik[] = [
                'opd_id'     => $s['opd_id'],
                'nama_opd'   => $s['nama_opd'],
                'x'          => (float) $s['penyerapan'],
                'y'          => (float) $s['percentage'],
                'color'      => $s['status']['color_hex'],
                'status'     => $s['status']['name'],
                'anggaran'   => formatRupiah($s['anggaran']),
                'realisasi'  => formatRupiah($s['realisasi']),
            ];
        }

        return [
            'points'       => $titik,
            'excluded'     => $dikecualikan,
            'catatan'      => 'Kuadran hanya menggambarkan posisi relatif penyerapan dan capaian. '
                . 'Penilaian efisiensi tetap mengikuti input manual pada modul LAKIP.',
        ];
    }

    /** Perubahan data terakhir yang relevan (seluruh OPD, tahun terpilih). */
    public function getLastUpdate(int $tahun): ?string
    {
        $waktu = [];

        $mv = $this->db->table('monev m')
            ->select('MAX(m.updated_at) AS w')
            ->join('target_rencana tr', 'tr.id = m.target_rencana_id', 'inner')
            ->join('pk_indikator pi', 'pi.id = tr.pk_indikator_id', 'inner')
            ->join('pk_sasaran ps', 'ps.id = pi.pk_sasaran_id', 'inner')
            ->join('pk', 'pk.id = ps.pk_id', 'inner')
            ->where('pk.tahun', $tahun)
            ->get()->getRowArray();
        $waktu[] = $mv['w'] ?? null;

        // Disamakan dengan OpdDashboardService::getLastUpdate(): "Pembaruan
        // data terakhir" harus berarti hal yang sama di layar OPD, Kabupaten,
        // dan Bupati. Sebelumnya layar Kabupaten hanya melihat `monev`,
        // sehingga pembaruan realisasi anggaran tidak pernah tercermin.
        if ($this->db->tableExists('monev_anggaran')) {
            $ma = $this->db->table('monev_anggaran ma')
                ->select('MAX(ma.updated_at) AS w')
                ->join('target_rencana tr', 'tr.id = ma.target_rencana_id', 'inner')
                ->join('pk_indikator pi', 'pi.id = tr.pk_indikator_id', 'inner')
                ->join('pk_sasaran ps', 'ps.id = pi.pk_sasaran_id', 'inner')
                ->join('pk', 'pk.id = ps.pk_id', 'inner')
                ->where('pk.tahun', $tahun)
                ->get()->getRowArray();
            $waktu[] = $ma['w'] ?? null;
        }

        $waktu = array_values(array_filter($waktu));

        return $waktu === [] ? null : max($waktu);
    }
}
