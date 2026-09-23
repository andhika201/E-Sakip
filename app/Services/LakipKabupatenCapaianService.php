<?php

namespace App\Services;

use App\Models\LakipModel;
use App\Models\LakipPengesahanModel;
use App\Models\LakipSnapshotModel;
use App\Services\Version\LakipSourceService;

/**
 * Capaian LAKIP KABUPATEN satu tahun laporan, dibaca PERSIS seperti layar
 * LAKIP Kabupaten membacanya — tetapi tanpa bergantung pada request.
 *
 * =====================================================================
 * MENGAPA ADA
 *
 * Kartu "Capaian PK Bupati" di dashboard kabupaten semula dihitung dari
 * indikator PK Bupati + MONEV triwulanan. Selama dokumen PK Bupati tahun itu
 * belum ada (atau MONEV-nya tidak diisi), kartunya kosong padahal capaian
 * kinerja Bupati sudah dilaporkan resmi lewat LAKIP tahun sebelumnya.
 * Kartu itu kini membaca LAKIP — dan supaya angkanya SAMA dengan yang dilihat
 * operator di menu LAKIP, aturan berikut ditiru dari AdminKab\LakipController
 * (LakipSumberTrait + LakipSnapshotTrait):
 *
 *   1. Sumber dokumen: IKU Kabupaten bila ada revisi resmi yang memuat tahun
 *      laporan (versi rekomendasi = yang berlaku pada tahun itu), selain itu
 *      cadangan RPJMD. Ini aturan "paksa IKU" pada pilihanSumberLakip().
 *   2. Tahun yang sudah DIFINALKAN dibaca dari arsip beku (lakip_snapshot),
 *      bukan tabel hidup — snapshot draft SENGAJA tidak dipakai, sama seperti
 *      layar.
 *   3. Capaian % per indikator = rumus layar (positif/negatif, apa adanya
 *      tanpa batas), kolom perhitungan (target_hitung/capaian_hitung)
 *      didahulukan bila diisi.
 *
 * Rumusnya DITULIS ULANG di sini, bukan memuat helper('lakip'), semata supaya
 * service ini tidak bergantung pada helper yang dimuat per-request. Sejak
 * 23 Sep 2026 keduanya memberi hasil yang SAMA PERSIS: batas 200% yang dulu
 * membedakan layar dari cetak sudah dicabut dari semua tampilan.
 * =====================================================================
 */
class LakipKabupatenCapaianService
{
    private LakipModel $lakip;

    public function __construct()
    {
        $this->lakip = new LakipModel();
    }

    /**
     * Ringkasan capaian LAKIP Kabupaten tahun $tahun.
     *
     * @return array{
     *     tahun: int, ada: bool, sumber: string|null, sumber_label: string|null,
     *     versi_label: string|null, dari_snapshot: bool, terkunci: bool,
     *     disahkan: bool, final: bool, wajib: int, valid: int, belum_valid: int,
     *     belum_input: int, total: float|null, can_compute: bool,
     *     indikator: list<array<string,mixed>>
     * }
     */
    public function ringkasan(int $tahun): array
    {
        $bahan = $this->bahan($tahun);
        $rows  = $bahan['rows'];
        $map   = $bahan['lakipMap'];

        $daftar     = [];
        $valid      = 0;
        $belumInput = 0;
        $jumlah     = 0.0;
        $semuaFinal = $rows !== [];

        foreach ($rows as $r) {
            $targetId = (int) ($r['target_id'] ?? 0);
            $item     = $map[$targetId] ?? null;

            $target    = $r['target_tahun_ini'] ?? null;
            $realisasi = $item['capaian_tahun_ini'] ?? null;

            // Kolom perhitungan didahulukan bila operator mengisinya (pola view).
            if (isset($item['target_hitung']) && $item['target_hitung'] !== '') {
                $target = $item['target_hitung'];
            }
            if (isset($item['capaian_hitung']) && $item['capaian_hitung'] !== '') {
                $realisasi = $item['capaian_hitung'];
            }

            // Jenis kosong TIDAK dianggap positif. capaianPersen() memulangkan
            // null dan alasan() menandainya 'missing_method' — indikator itu
            // masuk hitungan belum_valid sehingga rata-rata tahunan tidak
            // dipublikasikan di atas angka yang arahnya ditebak.
            $jenis  = trim((string) ($r['jenis_indikator'] ?? ''));
            $persen = $item === null ? null : self::capaianPersen($target, $realisasi, $jenis);

            [$reasonCode, $reason] = $this->alasan($item, $target, $realisasi, $jenis, $persen);

            $statusLakip = strtolower(trim((string) ($item['status'] ?? '')));
            if (! in_array($statusLakip, ['selesai', 'siap'], true)) {
                $semuaFinal = false;
            }

            if ($item === null) {
                $belumInput++;
            }

            if ($persen !== null) {
                $valid++;
                // Per indikator ditampilkan apa adanya (boleh minus, boleh di
                // atas 200%), tetapi ke RATA-RATA kontribusinya dibatasi
                // 0–200%. Tanpa batas bawah, satu indikator -773% menyeret
                // total 11 indikator dari 84% ke 14% dan menutupi sepuluh
                // lainnya; tanpa batas atas, satu indikator 183.907% membuat
                // rata-ratanya 16.700% — dua-duanya angka yang tidak berarti
                // apa-apa.
                //
                // KEDUA batas dipasang DI SINI. Sebelumnya batas atas menumpang
                // pada capaianPersen(), sehingga mencabut batas di tampilan
                // ikut menjebol rata-rata tanpa ada yang menyadarinya.
                $jumlah += min(200.0, max(0.0, $persen));
            }

            $daftar[] = [
                'indikator_id'    => $r['indikator_id'] !== null ? (int) $r['indikator_id'] : null,
                'target_id'       => $targetId,
                'indikator'       => (string) ($r['indikator_sasaran'] ?? '-'),
                'sasaran'         => (string) ($r['sasaran'] ?? '-'),
                'sasaran_id'      => $r['sasaran_id'] !== null ? (int) $r['sasaran_id'] : null,
                'satuan'          => (string) ($r['satuan'] ?? ''),
                'jenis'           => $jenis,
                'target'          => $target !== null ? (string) $target : null,
                'realisasi'       => $realisasi !== null ? (string) $realisasi : null,
                'target_lalu'     => $item['target_lalu'] ?? null,
                'capaian_lalu'    => $item['capaian_lalu'] ?? null,
                'percentage'      => $persen !== null ? round($persen, 2) : null,
                'percentage_teks' => $persen !== null ? capaianFormatPersen($persen) : null,
                'status'          => $persen !== null
                    ? getAchievementStatus($persen)
                    : dash_status_nonnumeric($item === null ? 'belum_ada_data' : 'belum_valid'),
                'is_valid'        => $persen !== null,
                'reason_code'     => $reasonCode,
                'reason'          => $reason,
                'status_lakip'    => $statusLakip !== '' ? $statusLakip : null,
                'updated_at'      => $item['updated_at'] ?? null,
            ];
        }

        $wajib = count($rows);
        // Sama dengan kartu PK: rata-rata hanya ditampilkan bila SELURUH
        // indikator terhitung — tidak pernah rata-rata parsial.
        $bisa  = $wajib > 0 && $valid === $wajib;
        $official = $bisa && $bahan['disahkan'];

        return [
            'tahun'         => $tahun,
            'ada'           => $wajib > 0,
            'sumber'        => $bahan['sumber'],
            'sumber_label'  => $bahan['sumber_label'],
            'versi_label'   => $bahan['versi_label'],
            'dari_snapshot' => $bahan['dari_snapshot'],
            'terkunci'      => $bahan['terkunci'],
            'disahkan'      => $bahan['disahkan'],
            // Hanya pengesahan formal yang menjadikan hasil tahunan resmi.
            // Kelengkapan input dan snapshot dapat membantu pembacaan, tetapi
            // tidak boleh diberi label final/official.
            'final'         => $wajib > 0 && $bahan['disahkan'],
            'wajib'         => $wajib,
            'valid'         => $valid,
            'belum_valid'   => $wajib - $valid,
            'belum_input'   => $belumInput,
            // Perhitungan internal boleh lengkap, tetapi dashboard tidak
            // memublikasikan angka itu sebagai hasil tahunan sebelum sah.
            'total'         => $official ? round($jumlah / $wajib, 2) : null,
            'can_compute'   => $official,
            'indikator'     => $daftar,
        ];
    }

    /**
     * Rumus layar LAKIP. Lihat catatan kelas.
     *
     * TANPA batas atas maupun bawah — persentasenya apa adanya, persis sama
     * dengan layar Kabupaten, layar OPD, cetak, dan Excel.
     *
     * Batas 200% dulu dipasang di sini. Akibatnya satu indikator punya DUA
     * angka: Persentase Daerah Rawan Pangan (target 9, realisasi 0,8) tampil
     * 200% di layar tetapi 1.125% di PDF — prestasi sesungguhnya tersembunyi
     * justru di layar yang paling sering dibuka. Nilai negatif juga dibiarkan:
     * realisasi -38,67 terhadap target 5 memang berarti -773%, dan memotongnya
     * ke 0% menyembunyikan seberapa jauh melesetnya.
     *
     * Pembatasan 0-200% TIDAK hilang, hanya pindah ke tempat yang memang
     * membutuhkannya: kontribusi per indikator ke RATA-RATA di ringkasan().
     */
    public static function capaianPersen($target, $realisasi, string $jenisIndikator): ?float
    {
        $target    = toFloatComma($target);
        $realisasi = toFloatComma($realisasi);

        if ($target === null || $target <= 0 || $realisasi === null) {
            return null;
        }

        $jenis = strtolower(trim($jenisIndikator));

        if ($jenis === 'indikator positif' || $jenis === 'positif') {
            $hasil = ($realisasi / $target) * 100;
        } elseif ($jenis === 'indikator negatif' || $jenis === 'negatif') {
            // Semakin rendah semakin baik: target / realisasi — rumus yang
            // sama dengan metode "Trend Turun" MONEV. Realisasi 0 = 100%.
            $hasil = $realisasi <= 0 ? 100.0 : ($target / $realisasi) * 100;
        } else {
            return null;
        }

        return $hasil;
    }

    /**
     * Baris target + peta realisasi + keterangan sumber untuk satu tahun.
     *
     * Kunci peta SELALU sama dengan `target_id` barisnya (IKU: id indikator
     * berjalan; RPJMD: id rpjmd_target) — baik dari tabel hidup maupun arsip.
     *
     * @return array{rows: array, lakipMap: array, sumber: ?string, sumber_label: ?string,
     *               versi_label: ?string, dari_snapshot: bool, terkunci: bool, disahkan: bool}
     */
    private function bahan(int $tahun): array
    {
        $out = [
            'rows'          => [],
            'lakipMap'      => [],
            'sumber'        => null,
            'sumber_label'  => null,
            'versi_label'   => null,
            'dari_snapshot' => false,
            'terkunci'      => false,
            'disahkan'      => $this->disahkan($tahun),
        ];

        // 2. Tahun terkunci -> arsip beku.
        $snap = new LakipSnapshotModel();
        if ($snap->siap()) {
            $aktif = $snap->aktif((string) $tahun, 'kabupaten', null);
            if ($aktif !== null && $aktif['status'] === LakipSnapshotModel::STATUS_FINAL) {
                $isi    = $snap->rehidrasi((int) $aktif['id']);
                $sumber = (string) ($aktif['source_type'] ?? '');
                $sumber = $sumber !== '' ? $sumber : LakipSourceService::SUMBER_RPJMD;

                $out['rows']          = $isi['rows'];
                $out['lakipMap']      = $isi['lakipMap'];
                $out['sumber']        = $sumber;
                $out['sumber_label']  = $this->labelSumber($sumber);
                $out['versi_label']   = 'Arsip final v' . (int) ($aktif['versi'] ?? 0);
                $out['dari_snapshot'] = true;
                $out['terkunci']      = true;

                return $out;
            }
        }

        // 1.5. Cek binding dokumen yang sedang aktif (single source of truth).
        $dokumenKab = (new \App\Models\LakipDokumenModel())->kabupaten($tahun);
        if ($dokumenKab !== null) {
            if (($dokumenKab['source_type'] ?? '') === 'iku') {
                $versiId = (int) ($dokumenKab['source_version_id'] ?? 0);
                $out['rows']         = $this->lakip->getIndexIkuTargets($versiId, $tahun, null);
                $out['lakipMap']     = $this->lakip->getLakipMapIku($tahun, null, null, $versiId);
                $out['sumber']       = LakipSourceService::SUMBER_IKU;
                $out['sumber_label'] = $this->labelSumber(LakipSourceService::SUMBER_IKU);
                // Kita tidak tahu nama versi pastinya dari tabel dokumen, jadi kita sebut saja Terikat v[id]
                $out['versi_label']  = 'Terikat v' . $versiId;

                return $out;
            } elseif (($dokumenKab['source_type'] ?? '') === 'rpjmd') {
                $out['rows']         = $this->lakip->getIndexRpjmdTargets((string) $tahun);
                $out['lakipMap']     = $this->lakip->getLakipMapRpjmd((string) $tahun, null);
                $out['sumber']       = LakipSourceService::SUMBER_RPJMD;
                $out['sumber_label'] = $this->labelSumber(LakipSourceService::SUMBER_RPJMD);
                $out['versi_label']  = 'Terikat (Fallback)';

                return $out;
            }
        }

        // 1. Sumber: IKU Kabupaten bila ada revisi resmi, cadangan RPJMD.
        $svc    = new LakipSourceService();
        $daftar = $svc->pilihanVersi(LakipSourceService::SUMBER_IKU, 'kabupaten', null, $tahun);

        if ($daftar !== []) {
            $versi = null;
            foreach ($daftar as $v) {
                if (! empty($v['rekomendasi'])) {
                    $versi = $v;
                    break;
                }
            }
            $versi ??= $daftar[0];

            $out['rows']         = $this->lakip->getIndexIkuTargets((int) $versi['id'], $tahun, null);
            $out['lakipMap']     = $this->lakip->getLakipMapIku(
                $tahun,
                null,
                null,
                (int) $versi['id']
            );
            $out['sumber']       = LakipSourceService::SUMBER_IKU;
            $out['sumber_label'] = $this->labelSumber(LakipSourceService::SUMBER_IKU);
            $out['versi_label']  = (string) ($versi['label'] ?? '');

            return $out;
        }

        $out['rows']         = $this->lakip->getIndexRpjmdTargets((string) $tahun);
        $out['lakipMap']     = $this->lakip->getLakipMapRpjmd((string) $tahun, null);
        $out['sumber']       = LakipSourceService::SUMBER_RPJMD;
        $out['sumber_label'] = $this->labelSumber(LakipSourceService::SUMBER_RPJMD);

        return $out;
    }

    private function disahkan(int $tahun): bool
    {
        $m = new LakipPengesahanModel();

        return $m->siap() && $m->terkunci($tahun, 'kabupaten', null);
    }

    private function labelSumber(string $sumber): string
    {
        return $sumber === LakipSourceService::SUMBER_IKU ? 'IKU Kabupaten' : 'RPJMD';
    }

    /**
     * Kode + kalimat alasan bila persentase tidak terhitung.
     *
     * @return array{0: ?string, 1: ?string}
     */
    private function alasan(?array $item, $target, $realisasi, string $jenis, ?float $persen): array
    {
        if ($persen !== null) {
            return [null, null];
        }

        if ($item === null) {
            return ['missing_achievement', 'Realisasi belum diinput pada LAKIP.'];
        }

        $t = toFloatComma($target);
        $r = toFloatComma($realisasi);

        if ($t === null) {
            return ['missing_target', 'Target tahunan belum tersedia atau bukan angka.'];
        }
        if ($t <= 0) {
            return ['division_by_zero', 'Target bernilai 0 sehingga persentase tidak dapat dihitung.'];
        }
        if ($r === null) {
            return ['invalid_number', 'Realisasi pada LAKIP bukan angka yang dapat dihitung.'];
        }

        $j = strtolower(trim($jenis));
        if (! in_array($j, ['indikator positif', 'positif', 'indikator negatif', 'negatif'], true)) {
            return ['missing_method', 'Jenis indikator (positif/negatif) belum ditentukan.'];
        }

        return ['not_calculable', dash_reason_label('not_calculable')];
    }
}
