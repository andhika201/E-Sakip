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
 *   3. Capaian % per indikator = rumus layar (positif/negatif, batas atas 200%,
 *      negatif dibiarkan), kolom perhitungan (target_hitung/capaian_hitung)
 *      didahulukan bila diisi.
 *
 * Rumusnya DITULIS ULANG di sini, bukan memuat helper('lakip'): helper itu
 * versi TANPA batas atas (untuk cetak/Excel), sedangkan yang dilihat pemakai di
 * layar dibatasi 200%. Dashboard harus cocok dengan layar.
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

            $jenis  = (string) ($r['jenis_indikator'] ?? 'indikator positif');
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
                // Per indikator ditampilkan apa adanya (boleh minus), tetapi
                // ke RATA-RATA kontribusinya dibatasi 0–200%: satu indikator
                // -773% akan menyeret total 11 indikator dari 84% ke 14% dan
                // menutupi sepuluh lainnya. Batas atas 200% sudah dipasang
                // capaianPersen(); batas bawah 0% adalah pasangannya.
                $jumlah += max(0.0, $persen);
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

        return [
            'tahun'         => $tahun,
            'ada'           => $wajib > 0,
            'sumber'        => $bahan['sumber'],
            'sumber_label'  => $bahan['sumber_label'],
            'versi_label'   => $bahan['versi_label'],
            'dari_snapshot' => $bahan['dari_snapshot'],
            'terkunci'      => $bahan['terkunci'],
            'disahkan'      => $bahan['disahkan'],
            // "Final" = tahunnya dikunci snapshot ATAU disahkan ATAU seluruh
            // barisnya berstatus selesai/siap. Selain itu Sementara.
            'final'         => $wajib > 0 && ($bahan['terkunci'] || $bahan['disahkan'] || $semuaFinal),
            'wajib'         => $wajib,
            'valid'         => $valid,
            'belum_valid'   => $wajib - $valid,
            'belum_input'   => $belumInput,
            'total'         => $bisa ? round($jumlah / $wajib, 2) : null,
            'can_compute'   => $bisa,
            'indikator'     => $daftar,
        ];
    }

    /**
     * Rumus layar LAKIP. Lihat catatan kelas.
     *
     * Hanya batas ATAS (200%) yang dipasang. Nilai negatif dibiarkan apa
     * adanya: realisasi -38,67 terhadap target 5 memang berarti -773% —
     * memotongnya ke 0% menyembunyikan seberapa jauh melesetnya, dan cetak/
     * Excel (helper lakip) sejak awal tidak pernah memotongnya.
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

        return min($hasil, 200);
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
            $out['lakipMap']     = $this->lakip->getLakipMapIku($tahun, null, null);
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
