<?php

namespace App\Controllers\Concerns;

/**
 * Bagian bersama form IKU standalone (dipakai AdminKab\IkuController dan
 * AdminOpd\IkuController): pembacaan input, validasi, dan filter anti-script.
 *
 * Bentuk POST yang diharapkan:
 *   sasaran, tahun_mulai, tahun_akhir
 *   indikator[i][id]                  (hanya pada form edit)
 *   indikator[i][indikator]
 *   indikator[i][definisi]
 *   indikator[i][rumusan_perhitungan]
 *   indikator[i][satuan]
 *   indikator[i][sumber_data]
 *   indikator[i][penanggung_jawab]
 *   indikator[i][jenis_indikator]
 *   indikator[i][baseline]
 *   indikator[i][status]
 *   indikator[i][target][<tahun>]
 *   indikator[i][program][]
 */
trait IkuFormTrait
{
    /** Rentang periode yang masuk akal untuk dokumen perencanaan. */
    private const TAHUN_MIN     = 1900;
    private const TAHUN_MAX     = 2999;
    private const PERIODE_MAKS  = 20;

    /* =========================================================
     * SYNC DARI RPJMD / RENSTRA
     * =======================================================*/

    /**
     * Tentukan periode sumber yang sedang dilihat di halaman sync.
     *
     * @return array{0: array<string, mixed>, 1: array<string, mixed>} [daftar periode, periode terpilih]
     */
    private function resolvePeriodeSumber(string $sumber, ?int $opdId): array
    {
        $daftar = $this->ikuModel->getPeriodeSumber($sumber, $opdId);

        if (empty($daftar)) {
            return [[], []];
        }

        $dipilih = $this->request->getGet('periode');

        if (empty($dipilih) || !isset($daftar[$dipilih])) {
            $dipilih       = null;
            $tahunSekarang = (int) date('Y');

            foreach ($daftar as $key => $p) {
                if (in_array($tahunSekarang, $p['years'], true)) {
                    $dipilih = $key;
                    break;
                }
            }

            $dipilih ??= array_key_first($daftar);
        }

        return [$daftar, $daftar[$dipilih] + ['key' => $dipilih]];
    }

    /**
     * Baca centang pratinjau sync.
     *
     * Yang diambil dari form HANYA id sumber (integer) — isi sasaran/indikator
     * selalu dibaca ulang dari DB oleh model, jadi tidak ada data dari browser
     * yang langsung masuk ke tabel IKU.
     *
     * @return array<int, int[]> [id_sasaran_sumber => [id_indikator_sumber, ...]]
     */
    private function bacaPilihanSync(array $post): array
    {
        $pilihan = [];

        foreach ((array) ($post['pilih'] ?? []) as $sasaranId => $daftarIndikator) {
            $sasaranId = (int) $sasaranId;
            if ($sasaranId <= 0 || !is_array($daftarIndikator)) {
                continue;
            }

            foreach ($daftarIndikator as $indikatorId => $ditandai) {
                $indikatorId = (int) $indikatorId;
                if ($indikatorId > 0 && !empty($ditandai)) {
                    $pilihan[$sasaranId][] = $indikatorId;
                }
            }
        }

        return $pilihan;
    }

    /** Susun pesan hasil sync untuk flashdata. */
    private function pesanHasilSync(array $stat): string
    {
        $bagian = [];

        if ($stat['sasaran_baru'] > 0) {
            $bagian[] = $stat['sasaran_baru'] . ' sasaran baru';
        }
        if ($stat['indikator_baru'] > 0) {
            $bagian[] = $stat['indikator_baru'] . ' indikator';
        }
        if ($stat['target'] > 0) {
            $bagian[] = $stat['target'] . ' target tahunan';
        }

        if (empty($bagian)) {
            return $stat['dilewati'] > 0
                ? 'Tidak ada data baru — ' . $stat['dilewati'] . ' indikator yang dipilih sudah ada di IKU.'
                : 'Tidak ada data yang dipilih untuk disalin.';
        }

        $pesan = 'Sync berhasil: ' . implode(', ', $bagian) . ' ditambahkan.';

        if ($stat['dilewati'] > 0) {
            $pesan .= ' ' . $stat['dilewati'] . ' indikator dilewati karena sudah ada.';
        }

        return $pesan;
    }

    /**
     * Pola anti-script yang dipakai konsisten di seluruh modul: menolak tag
     * script, skema javascript:, data:text/html, atribut on*=, dan tag PHP.
     */
    private function isSafeText($val): bool
    {
        if ($val === null || $val === '') {
            return true;
        }

        return (bool) preg_match(
            '/^(?!.*<\s*script\b)(?!.*<\/\s*script\s*>)(?!.*javascript\s*:)(?!.*data\s*:\s*text\/html)(?!.*(?<!\w)on\w+\s*=)(?!.*<\?php)(?!.*<\?).*$/is',
            (string) $val
        );
    }

    /**
     * @param array<string, mixed> $fields [label => nilai]
     *
     * @return string|null pesan error, atau null bila semua aman
     */
    private function safeTextError(array $fields): ?string
    {
        foreach ($fields as $label => $value) {
            if (!$this->isSafeText($value)) {
                return $label . ' terdeteksi mengandung script / input berbahaya.';
            }
        }

        return null;
    }

    /**
     * Ubah POST mentah jadi struktur siap simpan untuk IkuModel.
     *
     * @return array{
     *     sasaran: string, tahun_mulai: int, tahun_akhir: int,
     *     indikator: array<int, array<string, mixed>>
     * }
     */
    private function bacaFormIku(array $post): array
    {
        $tahunMulai = (int) ($post['tahun_mulai'] ?? 0);
        $tahunAkhir = (int) ($post['tahun_akhir'] ?? 0);

        $indikatorInput = $post['indikator'] ?? [];
        if (!is_array($indikatorInput)) {
            $indikatorInput = [];
        }

        $indikator = [];

        foreach ($indikatorInput as $row) {
            if (!is_array($row)) {
                continue;
            }

            // Baris yang teks indikatornya kosong dianggap baris kosong -> dilewati.
            $teks = trim((string) ($row['indikator'] ?? ''));
            if ($teks === '') {
                continue;
            }

            $target = [];
            foreach ((array) ($row['target'] ?? []) as $tahun => $nilai) {
                $tahun = (int) $tahun;
                if ($tahun < self::TAHUN_MIN || $tahun > self::TAHUN_MAX) {
                    continue;
                }
                // Hanya tahun dalam periode yang disimpan, supaya sisa input
                // dari periode lama (kalau periode diperpendek) ikut terbuang.
                if ($tahunMulai > 0 && $tahunAkhir > 0 && ($tahun < $tahunMulai || $tahun > $tahunAkhir)) {
                    continue;
                }
                $target[$tahun] = is_scalar($nilai) ? trim((string) $nilai) : null;
            }

            $program = [];
            foreach ((array) ($row['program'] ?? []) as $nama) {
                if (!is_scalar($nama)) {
                    continue;
                }
                $nama = trim((string) $nama);
                if ($nama !== '') {
                    $program[] = $nama;
                }
            }

            $indikator[] = [
                'id'                  => isset($row['id']) ? (int) $row['id'] : 0,
                'indikator'           => $teks,
                'definisi'            => trim((string) ($row['definisi'] ?? '')),
                'rumusan_perhitungan' => trim((string) ($row['rumusan_perhitungan'] ?? '')),
                'satuan'              => trim((string) ($row['satuan'] ?? '')),
                'sumber_data'         => trim((string) ($row['sumber_data'] ?? '')),
                'penanggung_jawab'    => trim((string) ($row['penanggung_jawab'] ?? '')),
                'jenis_indikator'     => trim((string) ($row['jenis_indikator'] ?? '')),
                'baseline'            => trim((string) ($row['baseline'] ?? '')),
                'status'              => trim((string) ($row['status'] ?? 'draft')),
                'target'              => $target,
                'program'             => $program,
            ];
        }

        return [
            'sasaran'     => trim((string) ($post['sasaran'] ?? '')),
            'tahun_mulai' => $tahunMulai,
            'tahun_akhir' => $tahunAkhir,
            'indikator'   => $indikator,
        ];
    }

    /**
     * Validasi isi form beserta cek anti-script.
     *
     * @return string|null pesan error pertama, atau null bila lolos
     */
    private function validasiFormIku(array $data): ?string
    {
        if ($data['sasaran'] === '') {
            return 'Sasaran IKU wajib diisi.';
        }
        if (mb_strlen($data['sasaran']) > 10000) {
            return 'Sasaran IKU terlalu panjang (maksimal 10.000 karakter).';
        }

        if ($data['tahun_mulai'] < self::TAHUN_MIN || $data['tahun_mulai'] > self::TAHUN_MAX) {
            return 'Tahun mulai tidak valid.';
        }
        if ($data['tahun_akhir'] < self::TAHUN_MIN || $data['tahun_akhir'] > self::TAHUN_MAX) {
            return 'Tahun akhir tidak valid.';
        }
        if ($data['tahun_akhir'] < $data['tahun_mulai']) {
            return 'Tahun akhir tidak boleh lebih kecil dari tahun mulai.';
        }
        if (($data['tahun_akhir'] - $data['tahun_mulai']) >= self::PERIODE_MAKS) {
            return 'Rentang periode terlalu panjang (maksimal ' . self::PERIODE_MAKS . ' tahun).';
        }

        if (empty($data['indikator'])) {
            return 'Minimal satu indikator IKU wajib diisi.';
        }

        if ($error = $this->safeTextError(['Sasaran' => $data['sasaran']])) {
            return $error;
        }

        foreach ($data['indikator'] as $no => $ind) {
            $label = 'Indikator ke-' . ($no + 1);

            if (mb_strlen($ind['indikator']) > 10000) {
                return $label . ' terlalu panjang (maksimal 10.000 karakter).';
            }
            if (mb_strlen($ind['penanggung_jawab']) > 255) {
                return 'Penanggung Jawab pada ' . $label . ' terlalu panjang (maksimal 255 karakter).';
            }
            if (mb_strlen($ind['baseline']) > 50) {
                return 'Kondisi Awal pada ' . $label . ' terlalu panjang (maksimal 50 karakter).';
            }
            if (mb_strlen($ind['satuan']) > 50) {
                return 'Satuan pada ' . $label . ' terlalu panjang (maksimal 50 karakter).';
            }

            $error = $this->safeTextError([
                $label                              => $ind['indikator'],
                'Definisi Operasional pada ' . $label => $ind['definisi'],
                'Formula/Rumusan pada ' . $label      => $ind['rumusan_perhitungan'],
                'Satuan pada ' . $label               => $ind['satuan'],
                'Sumber Data pada ' . $label          => $ind['sumber_data'],
                'Penanggung Jawab pada ' . $label     => $ind['penanggung_jawab'],
                'Kondisi Awal pada ' . $label         => $ind['baseline'],
            ]);
            if ($error) {
                return $error;
            }

            foreach ($ind['target'] as $tahun => $nilai) {
                if ($nilai !== null && mb_strlen((string) $nilai) > 100) {
                    return 'Target ' . $tahun . ' pada ' . $label . ' terlalu panjang (maksimal 100 karakter).';
                }
                if ($error = $this->safeTextError(['Target ' . $tahun . ' pada ' . $label => $nilai])) {
                    return $error;
                }
            }

            foreach ($ind['program'] as $program) {
                if ($error = $this->safeTextError(['Program pendukung pada ' . $label => $program])) {
                    return $error;
                }
            }
        }

        return null;
    }
}
