<?php

/**
 * Helper cetak PDF (mPDF): sel "gabungan visual" pengganti rowspan besar.
 *
 * LATAR: mPDF memperlakukan seluruh baris yang dilintasi satu rowspan sebagai
 * satu blok yang tidak boleh dipotong halaman. Akibatnya:
 *   - blok yang tidak muat di sisa halaman dipindah utuh ke halaman berikut
 *     (menyisakan halaman setengah kosong);
 *   - blok yang lebih tinggi dari SATU halaman memaksa mPDF menyusutkan
 *     SELURUH tabel — tanpa batas, sampai bloknya muat — sehingga cetakan
 *     MONEV/Rencana Aksi OPD besar keluar dengan huruf 1-3 pt yang tak terbaca
 *     (shrink_tables_to_fit=false pun tidak menolongnya, lihat
 *     Mpdf\Tag\Table: $forcerecalc).
 *
 * SOLUSI: kolom induk (No, Pejabat, Sasaran, Indikator, Unit, ...) dicetak di
 * SETIAP baris grup, tanpa rowspan. Isinya hanya ditulis di baris tengah grup
 * (tampak seperti vertical-align: middle), garis atas/bawah di dalam grup
 * dihilangkan lewat kelas .vm, dan garis pembuka/penutup grup dipasang lewat
 * .vm-awal / .vm-akhir (CSS di templates/pdf_style.php). Hasilnya tampak
 * menyatu seperti rowspan, tetapi mPDF bebas memotong halaman di baris mana
 * pun dan tidak pernah menyusutkan tabel.
 *
 * Pola yang sama sudah dipakai cascading_cetak_kabupaten (kelas casc-parent).
 */

if (!function_exists('pdf_td_gabung')) {
    /**
     * Satu sel kolom induk untuk baris ke-$ke dari grup setinggi $jumlah baris.
     *
     * Isi ditulis di baris tengah grup. Grup yang sangat tinggi (lebih dari
     * $ulangTiap baris) dibagi menjadi beberapa penggal yang hampir sama tinggi
     * dan isinya diulang di tengah tiap penggal, supaya grup yang membentang
     * beberapa halaman tetap punya label di (hampir) setiap halaman — tanpa
     * itu, halaman lanjutan hanya menampilkan kolom Pejabat/Sasaran kosong.
     *
     * @param int    $ke        posisi baris ini di dalam grup, mulai 0
     * @param int    $jumlah    jumlah baris grup (>= 1)
     * @param string $isiHtml   isi sel yang SUDAH aman (esc)
     * @param string $kelas     kelas CSS tambahan, mis. "c nowrap" / "text-start"
     * @param string $attr      atribut tambahan mentah, mis. 'style="..."'
     * @param int    $ulangTiap batas tinggi penggal sebelum isi diulang; 0 = tidak pernah diulang
     */
    function pdf_td_gabung(int $ke, int $jumlah, string $isiHtml, string $kelas = '', string $attr = '', int $ulangTiap = 20): string
    {
        $jumlah = max(1, $jumlah);
        $ke     = max(0, min($ke, $jumlah - 1));

        $kelasSel = ['vm'];
        if ($ke === 0) {
            $kelasSel[] = 'vm-awal';
        }
        if ($ke === $jumlah - 1) {
            $kelasSel[] = 'vm-akhir';
        }
        if ($kelas !== '') {
            $kelasSel[] = $kelas;
        }

        $tampil = in_array($ke, pdf_baris_label($jumlah, $ulangTiap), true);

        return '<td class="' . implode(' ', $kelasSel) . '"' . ($attr !== '' ? ' ' . $attr : '') . '>'
            . ($tampil ? $isiHtml : '')
            . '</td>';
    }
}

if (!function_exists('pdf_baris_label')) {
    /**
     * Indeks (mulai 0) baris-baris tempat isi grup setinggi $jumlah baris ditulis:
     * tengah tiap penggal, penggal dibuat sesedikit mungkin dengan tinggi <= $ulangTiap.
     *
     * @return int[]
     */
    function pdf_baris_label(int $jumlah, int $ulangTiap = 20): array
    {
        $jumlah = max(1, $jumlah);
        $penggal = ($ulangTiap > 0) ? (int) ceil($jumlah / $ulangTiap) : 1;

        $posisi = [];
        $awal   = 0;
        for ($p = 0; $p < $penggal; $p++) {
            // Pembagian hampir rata: penggal awal mendapat sisa pembagian.
            $tinggi   = intdiv($jumlah, $penggal) + ($p < $jumlah % $penggal ? 1 : 0);
            $posisi[] = $awal + intdiv($tinggi - 1, 2);
            $awal    += $tinggi;
        }

        return $posisi;
    }
}

if (!function_exists('pdf_baris_tengah')) {
    /** Indeks (mulai 0) baris tengah grup setinggi $jumlah baris. */
    function pdf_baris_tengah(int $jumlah): int
    {
        return intdiv(max(1, $jumlah) - 1, 2);
    }
}

if (!function_exists('pdf_grup_per_baris')) {
    /**
     * Ubah hasil pk_bagi_baris() — [span per item, item yang MULAI di baris ke-k]
     * — menjadi peta per baris: [indeks item, posisi di dalam item, tinggi item].
     *
     * Dipakai view cetak yang dulu memasang rowspan di baris awal tiap item
     * dan kini harus menulis sel gabungan di setiap baris.
     *
     * @param array<int,int> $span  tinggi (jumlah baris) tiap item
     * @param array<int,int> $mulai baris awal => indeks item
     * @param int            $n     jumlah baris seluruhnya
     *
     * @return array<int, array{0:int|null,1:int,2:int}> baris ke-k => [item, ke, jumlah]
     */
    function pdf_grup_per_baris(array $span, array $mulai, int $n): array
    {
        $peta  = [];
        $item  = null;
        $ke    = 0;
        $tinggi = 1;
        for ($k = 0; $k < $n; $k++) {
            if (isset($mulai[$k])) {
                $item   = $mulai[$k];
                $ke     = 0;
                $tinggi = max(1, (int) ($span[$item] ?? 1));
            } elseif ($item !== null) {
                $ke++;
            }
            $peta[$k] = [$item, $ke, $tinggi];
        }

        return $peta;
    }
}

if (!function_exists('pdf_teks')) {
    /**
     * esc() untuk sel tabel cetak + titik pemenggalan pada token panjang.
     *
     * mPDF tidak mengenal overflow-wrap/word-break dan <wbr>: kata tanpa spasi
     * yang lebih lebar dari kolomnya menaikkan lebar MINIMUM kolom, dan begitu
     * jumlah lebar minimum melebihi lebar kertas, SELURUH tabel disusutkan.
     * Biang tersering: kode sub kegiatan "[2.11.0.00.0.00.10.0000.2.11.01...]"
     * dan kata bergaris miring "Konsultasi/Pendampingan/Asistensi".
     *
     * Zero-width space (U+200B) DIKENALI mPDF sebagai kesempatan pemenggalan
     * (soft hyphen dan <wbr> tidak), jadi ia disisipkan setelah tanda baca di
     * dalam token yang lebih panjang dari $batas. Token yang murni angka
     * (nominal rupiah, tahun) dibiarkan utuh.
     *
     * @param string|null $teks  teks mentah (belum di-escape)
     * @param int         $batas panjang token minimum yang disisipi
     */
    function pdf_teks(?string $teks, int $batas = 14): string
    {
        $teks = (string) $teks;
        if ($teks === '') {
            return '';
        }

        $teks = preg_replace_callback('/\S{' . ($batas + 1) . ',}/u', static function (array $m): string {
            if (preg_match('/^[\d.,%]+$/', $m[0])) {
                return $m[0];
            }
            return preg_replace('~([/.\-,;:_)\]])(?=\S)~u', "$1\u{200B}", $m[0]);
        }, $teks);

        return esc($teks);
    }
}
