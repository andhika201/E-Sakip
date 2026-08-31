<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Controllers\Concerns\PkPdPendukungTrait;
use App\Models\Opd\TargetModel;
use App\Models\OpdModel;

/**
 * API read-only Target & Rencana Aksi (turunan Perjanjian Kinerja).
 *
 * Isinya PERSIS data yang tampil di layar web:
 *
 *   - PK OPD/Kecamatan (Eselon II/III/IV) -> adminopd/target_renaksi
 *     Sasaran > Indikator > (Unit anggaran) > Rencana Aksi > Sub Rencana Aksi
 *     > Target Triwulan I-IV > Penanggung Jawab.
 *
 *   - PK Bupati -> adminkab/target_renaksi
 *     Tabelnya memang hanya 8 kolom: Sasaran, Indikator, Tahun, Satuan, Target,
 *     dan Perangkat Daerah Pendukung. Jadi endpoint bupati pun berhenti di situ,
 *     tidak mengarang kolom yang tidak ada di layar.
 *
 * Struktur JSON-nya DIKELOMPOKKAN per Sasaran PK, meniru rowspan tabel di web:
 * satu elemen `data` = satu blok bernomor pada kolom "No".
 */
class TargetRenaksiController extends BaseController
{
    // Perangkat Daerah pendukung PK Bupati (otomatis via cascading + override
    // manual), sumbernya sama dengan layar Target & Rencana Aksi.
    use PkPdPendukungTrait;

    protected $helpers = ['pk_unit', 'format'];

    private const EXCLUDED_OPD_IDS = OpdModel::EXCLUDED_OPD_IDS;

    /** Eselon yang boleh dipakai sebagai filter; nilai = jenis PK ternormalisasi. */
    private const ESELON_ALIAS = [
        'jpt'           => 'jpt',
        'administrator' => 'administrator',
        'camat'         => 'administrator',
        'kecamatan'     => 'administrator',
        'pengawas'      => 'pengawas',
    ];

    protected $db;
    protected TargetModel $targets;

    public function __construct()
    {
        $this->db      = \Config\Database::connect();
        $this->targets = new TargetModel();
    }

    /* ===================== ENDPOINT ===================== */

    /**
     * Target & Rencana Aksi PK OPD/Kecamatan (Eselon II/III/IV).
     *
     * GET /api/target-renaksi
     * GET /api/perangkat-daerah/{opd_id}/target-renaksi
     */
    public function index($opdId = null)
    {
        $opd = null;
        if ($opdId !== null || trim((string) ($this->request->getGet('opd_id') ?? '')) !== '') {
            $opdId = $this->resolveOpdId($opdId);
            if (!$opdId) {
                return $this->respondError('Parameter opd_id tidak valid.', 400);
            }
            $opd = $this->findOpd($opdId);
            if (!$opd) {
                return $this->respondError('Perangkat daerah tidak ditemukan.', 404);
            }
        } else {
            $opdId = null; // lintas OPD, seperti tampilan admin_kab
        }

        [$tahun, $tahunError] = $this->resolveTahun();
        if ($tahunError) {
            return $this->respondError($tahunError, 400);
        }

        $eselonRaw = strtolower(trim((string) ($this->request->getGet('eselon') ?? '')));
        if ($eselonRaw !== '' && !isset(self::ESELON_ALIAS[$eselonRaw])) {
            return $this->respondError('Eselon tidak valid. Gunakan jpt, administrator (alias camat/kecamatan), atau pengawas.', 400);
        }
        $eselon = $eselonRaw === '' ? null : self::ESELON_ALIAS[$eselonRaw];

        $pejabatRaw = $this->request->getGet('pejabat_id');
        if ($pejabatRaw !== null && $pejabatRaw !== '' && !ctype_digit((string) $pejabatRaw)) {
            return $this->respondError('Parameter pejabat_id tidak valid.', 400);
        }
        $pejabatId = ((int) $pejabatRaw) ?: null;

        $rows = $this->targets->getTargetListByPkOpd($tahun, $opdId, $eselon, $pejabatId);

        return $this->respondSuccess($this->kelompokkanOpd($rows), [
            'jenis'           => 'es3',
            'opd'             => $opd,
            'tahun'           => $tahun ?? 'all',
            'available_years' => $this->daftarTahun($this->targets->getAvailableYearsPkOpd($opdId)),
            'filter'          => [
                'opd_id'     => $opdId,
                'eselon'     => $eselon,
                'pejabat_id' => $pejabatId,
            ],
            // Judul kolom unit anggaran, sama dengan yang dipakai tabel di web.
            'label_kolom_unit' => pk_unit_header($eselon),
            'summary'          => $this->ringkasan($rows),
            'count'            => count($rows),
        ]);
    }

    /**
     * Target & Rencana Aksi PK Bupati.
     *
     * GET /api/target-renaksi/bupati
     */
    public function bupati()
    {
        [$tahun, $tahunError] = $this->resolveTahun();
        if ($tahunError) {
            return $this->respondError($tahunError, 400);
        }

        $rows = $this->targets->getTargetListByPkBupati($tahun);

        return $this->respondSuccess($this->kelompokkanBupati($rows), [
            'jenis'           => 'bupati',
            'tahun'           => $tahun ?? 'all',
            'available_years' => $this->daftarTahun($this->targets->getAvailableYearsPk('bupati')),
            'summary'         => $this->ringkasan($rows),
            'count'           => count($rows),
        ]);
    }

    /* ===================== PENYUSUN DATA ===================== */

    /**
     * Kelompokkan baris PK OPD/Kecamatan per Sasaran PK.
     *
     * Pengelompokan memakai `pk_sasaran_id` (bukan teks sasaran) supaya sasaran
     * milik pejabat berbeda tidak tergabung walau teksnya kebetulan sama —
     * aturan yang sama dipakai tabel di web.
     */
    private function kelompokkanOpd(array $rows): array
    {
        $unitMap = $this->targets->getUnitPkByIndikator(array_column($rows, 'pk_indikator_id'));
        $subMap  = $this->targets->getSubRencanaByTargets(array_column($rows, 'target_id'));

        $data = [];
        $no   = 1;
        foreach ($this->groupPerSasaran($rows) as $isiSasaran) {
            $awal = $isiSasaran[0];

            $indikator = [];
            foreach ($isiSasaran as $row) {
                $indikator[] = $this->formatIndikator($row, $unitMap, $subMap);
            }

            $data[] = [
                'no'            => $no++,
                'pk_sasaran_id' => (int) ($awal['pk_sasaran_id'] ?? 0),
                'pk_id'         => isset($awal['pk_id']) ? (int) $awal['pk_id'] : null,
                'sasaran'       => $awal['sasaran_renstra'] ?? null,
                'opd'           => [
                    'id'       => isset($awal['opd_id']) ? (int) $awal['opd_id'] : null,
                    'nama_opd' => $awal['nama_opd'] ?? null,
                ],
                'pejabat'       => [
                    'id'      => isset($awal['pejabat_id']) ? (int) $awal['pejabat_id'] : null,
                    'nama'    => $awal['pejabat_nama'] ?? null,
                    'jabatan' => $awal['pejabat_jabatan'] ?? null,
                    'eselon'  => $this->eselonLabel((string) ($awal['pk_jenis'] ?? '')),
                    'jenis'   => $awal['pk_jenis'] ?? null,
                ],
                'indikator'     => $indikator,
            ];
        }

        return $data;
    }

    /**
     * Kelompokkan baris PK Bupati per Sasaran PK, lengkap dengan Perangkat
     * Daerah Pendukung (manual mengalahkan otomatis, persis seperti di layar).
     */
    private function kelompokkanBupati(array $rows): array
    {
        $autoPd   = $this->autoPdBySasaran();
        $manualPd = $this->manualPdBySasaran();

        $data = [];
        $no   = 1;
        foreach ($this->groupPerSasaran($rows) as $isiSasaran) {
            $awal        = $isiSasaran[0];
            $pkSasaranId = (int) ($awal['pk_sasaran_id'] ?? 0);
            $pd          = $this->pdPendukungSasaran(
                $pkSasaranId,
                (string) ($awal['sasaran_renstra'] ?? ''),
                array_column($isiSasaran, 'indikator_sasaran'),
                $autoPd,
                $manualPd
            );

            $indikator = [];
            foreach ($isiSasaran as $row) {
                $indikator[] = [
                    'pk_indikator_id' => (int) ($row['pk_indikator_id'] ?? 0),
                    'indikator'       => $row['indikator_sasaran'] ?? null,
                    'tahun'           => $row['indikator_tahun'] ?? null,
                    'satuan'          => $row['satuan'] ?? null,
                    'target'          => $row['indikator_target'] ?? null,
                ];
            }

            $data[] = [
                'no'            => $no++,
                'pk_sasaran_id' => $pkSasaranId,
                'pk_id'         => isset($awal['pk_id']) ? (int) $awal['pk_id'] : null,
                'sasaran'       => $awal['sasaran_renstra'] ?? null,
                'indikator'     => $indikator,
                'perangkat_daerah_pendukung' => [
                    // 'manual' = ditetapkan admin lewat tombol Aksi; 'otomatis' =
                    // hasil pencocokan sasaran PK ke rantai cascading; null = belum ada.
                    'sumber' => $pd['sumber'],
                    'opd'    => array_map(static fn($o) => [
                        'id'   => (int) $o['id'],
                        'nama' => $o['nama'],
                    ], $pd['opd']),
                ],
            ];
        }

        return $data;
    }

    /**
     * Kelompokkan baris per Sasaran PK dengan urutan aslinya dipertahankan.
     *
     * @return array<int, array<int, array<string, mixed>>>
     */
    private function groupPerSasaran(array $rows): array
    {
        $grouped = [];
        foreach ($rows as $row) {
            $grouped[$row['pk_sasaran_id'] ?? '-'][] = $row;
        }

        return array_values($grouped);
    }

    /** Satu baris indikator PK OPD beserta unit anggaran & rencana aksinya. */
    private function formatIndikator(array $row, array $unitMap, array $subMap): array
    {
        $pkIndikatorId = (int) ($row['pk_indikator_id'] ?? 0);
        $targetId      = (int) ($row['target_id'] ?? 0);

        $unit = array_map(static fn($u) => [
            'level'           => $u['level'],
            'level_label'     => $u['level_label'],
            'ref_key'         => $u['ref_key'],
            'kode'            => $u['kode'],
            'nama'            => $u['nama'],
            'anggaran'        => (float) $u['anggaran'],
            'anggaran_format' => formatRupiah($u['anggaran']),
            'tahun_anggaran'  => $u['tahun'],
            // true = tingkat aslinya kosong, isinya diambil dari tingkat di atasnya
            'fallback'        => (bool) $u['fallback'],
        ], array_values($unitMap[$pkIndikatorId] ?? []));

        $totalAnggaran = array_sum(array_column($unit, 'anggaran'));

        return [
            'pk_indikator_id'       => $pkIndikatorId,
            'indikator'             => $row['indikator_sasaran'] ?? null,
            'tahun'                 => $row['indikator_tahun'] ?? null,
            'satuan'                => $row['satuan'] ?? null,
            'target'                => $row['indikator_target'] ?? null,
            'unit'                  => $unit,
            'total_anggaran'        => $totalAnggaran,
            'total_anggaran_format' => formatRupiah($totalAnggaran),
            'target_id'             => $targetId ?: null,
            'punya_renaksi'         => $targetId > 0,
            'rencana_aksi'          => $this->formatRencanaAksi($row['rencana_aksi'] ?? '', $subMap[$targetId] ?? []),
            // Target triwulan pada level rencana aksi (target_rencana). Kolom
            // Triwulan I-IV di layar menampilkan target milik SUB rencana aksi;
            // nilai di sini disertakan apa adanya sebagai isi rekamannya.
            'target_triwulan'       => [
                1 => $row['target_triwulan_1'] ?? null,
                2 => $row['target_triwulan_2'] ?? null,
                3 => $row['target_triwulan_3'] ?? null,
                4 => $row['target_triwulan_4'] ?? null,
            ],
            'penanggung_jawab'      => $row['penanggung_jawab'] ?? null,
        ];
    }

    /**
     * `target_rencana.rencana_aksi` menyimpan beberapa butir sebagai teks
     * multi-baris (1 baris = 1 butir); sub rencana aksi ditautkan ke nomor
     * baris butirnya. Pemecahannya sama dengan yang dilakukan tabel di web.
     *
     * @param array<int, array<int, array{id: int, teks: string, tw: array}>> $subs
     */
    private function formatRencanaAksi($teks, array $subs): array
    {
        $teks = trim((string) $teks);
        if ($teks === '') {
            return [];
        }

        $butir = array_values(array_filter(
            array_map('trim', preg_split('/\r\n|\r|\n/', $teks)),
            static fn($b) => $b !== ''
        ));

        $hasil = [];
        foreach ($butir as $i => $uraian) {
            $sub = [];
            foreach ($subs[$i] ?? [] as $j => $s) {
                $sub[] = [
                    'id'              => (int) $s['id'],
                    'no'              => $j + 1,
                    'uraian'          => $s['teks'],
                    'target_triwulan' => [
                        1 => $s['tw'][1] ?? null,
                        2 => $s['tw'][2] ?? null,
                        3 => $s['tw'][3] ?? null,
                        4 => $s['tw'][4] ?? null,
                    ],
                ];
            }

            $hasil[] = [
                'no'               => $i + 1,
                'uraian'           => $uraian,
                'sub_rencana_aksi' => $sub,
            ];
        }

        return $hasil;
    }

    /* ===================== HELPER ===================== */

    /** Label eselon dari `pk.jenis` mentah (query sudah memagari nilainya). */
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

        return $map[strtolower(trim($pkJenis))] ?? '-';
    }

    /**
     * Ringkasan yang sama dengan kartu di atas tabel web.
     *
     * @return array{sasaran: int, indikator: int, sudah_renaksi: int, belum_renaksi: int}
     */
    private function ringkasan(array $rows): array
    {
        $sudah = 0;
        foreach ($rows as $row) {
            if (!empty($row['target_id'])) {
                $sudah++;
            }
        }

        return [
            'sasaran'       => count(array_unique(array_column($rows, 'pk_sasaran_id'))),
            'indikator'     => count($rows),
            'sudah_renaksi' => $sudah,
            'belum_renaksi' => count($rows) - $sudah,
        ];
    }

    /** @return array{0: string|null, 1: string|null} [tahun, pesan error] */
    private function resolveTahun(): array
    {
        $tahun = trim((string) ($this->request->getGet('tahun') ?? ''));

        if ($tahun === '' || strtolower($tahun) === 'all') {
            return [null, null];
        }

        if (!preg_match('/^\d{4}$/', $tahun)) {
            return [null, 'Format tahun tidak valid. Gunakan 4 digit, contoh 2026, atau all.'];
        }

        return [$tahun, null];
    }

    /** @param array<int, array<string, mixed>> $rows baris hasil getAvailableYearsPk* */
    private function daftarTahun(array $rows): array
    {
        $tahun = [];
        foreach ($rows as $row) {
            $nilai = trim((string) ($row['tahun'] ?? ''));
            if ($nilai !== '') {
                $tahun[] = $nilai;
            }
        }

        return $tahun;
    }

    private function resolveOpdId($opdId): ?int
    {
        $opdId = $opdId ?? $this->request->getGet('opd_id');

        if ($opdId === null || $opdId === '' || !ctype_digit((string) $opdId)) {
            return null;
        }

        return (int) $opdId > 0 ? (int) $opdId : null;
    }

    private function findOpd($opdId): ?array
    {
        $opd = $this->db->table('opd')
            ->select('id, nama_opd, singkatan')
            ->where('id', (int) $opdId)
            ->whereNotIn('id', self::EXCLUDED_OPD_IDS)
            ->get()
            ->getRowArray();

        return $opd ? [
            'id'        => (int) $opd['id'],
            'nama_opd'  => $opd['nama_opd'],
            'singkatan' => $opd['singkatan'] ?? null,
        ] : null;
    }

    private function respondSuccess($data, array $meta = [], int $statusCode = 200)
    {
        return $this->response
            ->setStatusCode($statusCode)
            ->setJSON([
                'status' => 'success',
                'meta' => $meta,
                'data' => $data,
            ]);
    }

    private function respondError(string $message, int $statusCode = 400, array $errors = [])
    {
        $payload = [
            'status' => 'error',
            'message' => $message,
        ];

        if (!empty($errors)) {
            $payload['errors'] = $errors;
        }

        return $this->response
            ->setStatusCode($statusCode)
            ->setJSON($payload);
    }
}
