<?php

// Catatan: toFloatComma() disediakan oleh number_helper (di-autoload di Config\Autoload),
// jadi fungsi tersebut selalu tersedia saat helper ini dipakai.

if (!function_exists('hitungCapaianLakip')) {
    function hitungCapaianLakip($target, $realisasi, $jenisIndikator)
    {
        $target = toFloatComma($target);
        $realisasi = toFloatComma($realisasi);

        // Target <= 0, bukan == 0. Target NEGATIF membuat persentasenya
        // terbalik tandanya dan tidak bermakna (target -5 realisasi 10 ->
        // -200%), jadi ia diperlakukan sama dengan target 0: tidak terhitung.
        // Layar Kabupaten dan LakipKabupatenCapaianService sudah memakai <= 0;
        // baris ini dulu memakai == 0 sehingga cetak/Excel dan layar OPD bisa
        // memunculkan angka untuk baris yang di layar Kabupaten tampil '-'.
        if ($target === null || $target <= 0 || $realisasi === null) {
            return null;
        }

        $jenis = strtolower(trim((string) $jenisIndikator));

        if ($jenis === 'indikator positif' || $jenis === 'positif') {
            return ($realisasi / $target) * 100;
        }

        // ---------------------------------------------------------------
        // INDIKATOR NEGATIF (semakin rendah semakin baik)
        //
        //     capaian = (1 - (realisasi - target) / target) x 100%
        //
        // Dibaca: seberapa jauh realisasi MELESET dari target, lalu
        // dikurangkan dari 100%. Meleset ke ATAS (lebih tinggi dari target)
        // menurunkan capaian; meleset ke BAWAH menaikkannya. Secara aljabar
        // setara (2 x target - realisasi) / target x 100% — rumus baku SAKIP.
        //
        // Ditetapkan pemilik dokumen, 24 Sep 2026, menggantikan
        // target / realisasi.
        //
        // DUA SIFAT YANG PERLU DIINGAT, bukan bug:
        //
        //   1. Hasilnya BOLEH MINUS bila realisasi lebih dari dua kali
        //      target (target 10, realisasi 30 -> -100%). Itu memang
        //      sejauh itu melesetnya, dan tampilan sengaja tidak memotongnya.
        //      Yang dibatasi 0-200% hanyalah kontribusi ke RATA-RATA di
        //      LakipKabupatenCapaianService::ringkasan().
        //
        //   2. Realisasi 0 menghasilkan 200%, bukan 100%. Tidak ada lagi
        //      perlakuan khusus untuk realisasi 0: rumus ini tidak membagi
        //      dengan realisasi, jadi tidak ada pembagian nol yang perlu
        //      dihindari, dan 200% adalah hasil yang konsisten dengan
        //      rumusnya sendiri.
        //
        // BERBEDA DARI MONEV. Metode "Trend Turun" pada
        // calculateCapaianTotalPercentage masih memakai target / realisasi,
        // sehingga satu indikator negatif dapat menampilkan dua angka:
        // Indeks Risiko Bencana (target 88,82 realisasi 135,15) tampil
        // 47,84% di LAKIP tetapi 65,72% di MONEV. Perbedaan ini DISADARI
        // dan ditunda pembahasannya, bukan terlewat.
        // ---------------------------------------------------------------
        if ($jenis === 'indikator negatif' || $jenis === 'negatif') {
            return (1 - ($realisasi - $target) / $target) * 100;
        }

        return null;
    }
}
