<?php

/**
 * Dropdown master pada form Perjanjian Kinerja (Program / Kegiatan / Sub Kegiatan).
 *
 * =====================================================================
 * KENAPA ADA HELPER INI
 *
 * Dulu setiap <select> di form PK memuat SELURUH master sebagai <option>:
 * dropdown Sub Kegiatan menyalin 3.185 baris `sub_kegiatan_pk` (semua OPD,
 * semua tahun), dan form PK pengawas punya satu dropdown per baris sub
 * kegiatan. Edit PK dengan 11 sub kegiatan = 35.000 <option>, 31 MB HTML —
 * peramban operator menyerah ("Page Unresponsive"), lihat kasus PK #410
 * Kesbangpol 2026-09-16.
 *
 * Sekarang <select> hanya memuat SATU option: yang terpilih. Daftar lengkap
 * dikirim SEKALI sebagai JSON (`window.pkMaster`) dan disaring di peramban
 * oleh public/assets/js/adminopd/pk/pk-master-select.js lewat Select2.
 * Bentuk {id, text, anggaran, program_id} di sini harus tetap sama dengan
 * yang dibaca skrip itu.
 * =====================================================================
 */

if (! function_exists('pk_master_teks')) {
    /**
     * Teks satu pilihan: "Nama — Rp 1.000.000" (tanpa bagian Rp bila anggaran null).
     * Dipakai untuk <option> terpilih DAN daftar JSON supaya keduanya seragam.
     */
    function pk_master_teks(string $nama, ?float $anggaran): string
    {
        $nama = trim($nama);

        return $anggaran === null ? $nama : $nama . ' — Rp ' . number_format($anggaran, 0, ',', '.');
    }
}

if (! function_exists('pk_master_daftar')) {
    /**
     * Normalisasi baris master jadi daftar pilihan Select2.
     *
     * Baris ber-id ganda hanya diambil sekali — getJptPrograms() memang bisa
     * mengembalikan program yang sama lebih dari satu kali (satu baris per
     * tautan pk_program).
     *
     * @param list<array<string,mixed>> $rows      baris dari model (id, <kolomNama>, anggaran, program_id?)
     * @param string                    $kolomNama kolom nama: program_kegiatan | kegiatan | sub_kegiatan
     *
     * @return list<array{id:int, text:string, anggaran?:string, program_id?:int}>
     */
    function pk_master_daftar(array $rows, string $kolomNama): array
    {
        $hasil = [];
        $ada   = [];

        foreach ($rows as $r) {
            $id = (int) ($r['id'] ?? 0);

            if ($id <= 0 || isset($ada[$id])) {
                continue;
            }

            $ada[$id] = true;

            $anggaranMentah = $r['anggaran'] ?? null;
            $adaAnggaran    = $anggaranMentah !== null && $anggaranMentah !== '';

            $item = [
                'id'   => $id,
                'text' => pk_master_teks((string) ($r[$kolomNama] ?? ''), $adaAnggaran ? (float) $anggaranMentah : null),
            ];

            if ($adaAnggaran) {
                // Apa adanya dari DB (skrip form memakai parseInt) — sama dengan
                // isi atribut data-anggaran yang dulu dicetak langsung.
                $item['anggaran'] = (string) $anggaranMentah;
            }

            if (isset($r['program_id']) && $r['program_id'] !== null && $r['program_id'] !== '') {
                $item['program_id'] = (int) $r['program_id'];
            }

            $hasil[] = $item;
        }

        return $hasil;
    }
}

if (! function_exists('pk_master_opsi')) {
    /**
     * HTML <option> untuk pilihan yang sedang terpilih saja (atau '' bila belum ada).
     *
     * Atribut data-anggaran / data-program ikut dicetak karena skrip form
     * membacanya dari option terpilih saat halaman dimuat.
     *
     * Id yang tidak ada lagi di master (tautan yatim) tetap dicetak dengan
     * label yang jujur, bukan dibuang diam-diam: kalau dibuang, dropdown
     * `required` yang disembunyikan Select2 menghalangi simpan tanpa petunjuk,
     * dan operator tidak tahu baris mana yang bermasalah.
     *
     * @param list<array{id:int, text:string, anggaran?:string, program_id?:int}> $daftar hasil pk_master_daftar()
     * @param int|string|null                                                     $terpilih
     */
    function pk_master_opsi(array $daftar, $terpilih): string
    {
        $id = (int) $terpilih;

        if ($id <= 0) {
            return '';
        }

        foreach ($daftar as $item) {
            if ((int) $item['id'] !== $id) {
                continue;
            }

            $attr = '';

            if (isset($item['anggaran'])) {
                $attr .= ' data-anggaran="' . esc($item['anggaran'], 'attr') . '"';
            }

            if (isset($item['program_id'])) {
                $attr .= ' data-program="' . (int) $item['program_id'] . '"';
            }

            return '<option value="' . $id . '"' . $attr . ' selected>' . esc($item['text']) . '</option>';
        }

        return '<option value="' . $id . '" selected>#' . $id . ' (tidak ada di master)</option>';
    }
}
