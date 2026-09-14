<?php

/**
 * Penanda SERAPAN ANGGARAN terhadap pagu.
 *
 * =====================================================================
 * MENGAPA BUKAN HEATMAP, DAN BUKAN PEWARNAAN BARIS
 *
 * Seluruh warna pada palet dashboard SUDAH punya arti sebagai status
 * capaian kinerja (dashboard_status_thresholds):
 *
 *     merah  = Kritis            hijau = Tercapai
 *     oranye = Perlu Perhatian   biru  = Melampaui Target
 *     kuning = Mendekati Target
 *
 * Dua akibatnya bila serapan ikut memakai warna:
 *
 *   1. `biru` sudah berarti "MELAMPAUI TARGET" — sesuatu yang BAIK. Memakai
 *      warna untuk menandai "melampaui pagu" — sesuatu yang BURUK — membuat
 *      kata yang sama berarti terbalik di dua kolom bersebelahan.
 *
 *   2. `merah` sudah berarti kinerja Kritis. Mewarnai baris merah karena
 *      anggarannya lebih akan mencampur dua persoalan yang pemiliknya dan
 *      cara memperbaikinya berbeda — persis yang dilarang §38.
 *
 * =====================================================================
 * YANG DIPAKAI: PANJANG & POSISI, BUKAN RONA
 *
 * Batas yang bermakna di sini bukan gradasi melainkan SATU GARIS: 100% pagu.
 * Heatmap menyatakan "makin gelap makin buruk" dan menuntut legenda; bar
 * dengan garis 100% menyatakan "lewat garis ini berarti keliru" tanpa perlu
 * dijelaskan. 104% dan 400% sama-sama keliru — yang perlu terbaca adalah
 * seberapa jauh lewatnya, dan itu urusan panjang, bukan rona.
 *
 * Warna tetap dipakai, tetapi hanya sebagai PENEGAS di atas bentuk dan teks —
 * tidak pernah sebagai satu-satunya pembawa arti. Itu juga yang membuatnya
 * tetap terbaca saat dicetak hitam-putih (layar ini punya Cetak PDF) dan bagi
 * pembaca yang sulit membedakan warna.
 */

if (! function_exists('serapan_hitung')) {
    /**
     * Angka-angka serapan sebuah unit.
     *
     * @return array{pagu:float, realisasi:float, persen:float|null, lebih:float,
     *               melebihi:bool, terukur:bool}
     */
    function serapan_hitung($pagu, $realisasi): array
    {
        $pagu      = (float) $pagu;
        $realisasi = (float) $realisasi;

        // Pagu 0 bukan "serapan tak hingga": persentasenya memang tidak
        // bermakna, dan memaksanya jadi angka hanya melahirkan 999% palsu.
        $terukur = $pagu > 0;

        return [
            'pagu'      => $pagu,
            'realisasi' => $realisasi,
            'persen'    => $terukur ? $realisasi / $pagu * 100 : null,
            'lebih'     => max(0.0, $realisasi - $pagu),
            'melebihi'  => $realisasi > $pagu,
            'terukur'   => $terukur,
        ];
    }
}

if (! function_exists('serapan_bar')) {
    /**
     * Bar serapan siap tempel.
     *
     * @param array<string,mixed> $opsi
     *      'lebar'   => lebar bar (bawaan '100%')
     *      'teks'    => tampilkan baris keterangan di bawah bar (bawaan true)
     *      'judul'   => tooltip tambahan
     */
    function serapan_bar($pagu, $realisasi, array $opsi = []): string
    {
        $s = serapan_hitung($pagu, $realisasi);

        $rp = static fn (float $n): string => 'Rp' . number_format($n, 0, ',', '.');

        if (! $s['terukur']) {
            $ket = $s['realisasi'] > 0
                ? '<span class="text-danger fw-semibold">'
                    . '<i class="fas fa-triangle-exclamation me-1"></i>pagu belum diisi, realisasi '
                    . esc($rp($s['realisasi'])) . '</span>'
                : '<span class="text-muted">pagu belum diisi</span>';

            return '<div class="serapan small">' . $ket . '</div>';
        }

        // =============================================================
        // SKALA JALUR BERUBAH SAAT MELEBIHI PAGU
        //
        // Bila masih di dalam pagu, jalur mewakili PAGU: isian sepanjang
        // persen serapannya, dan garis 100% berimpit dengan ujung jalur —
        // tidak perlu digambar.
        //
        // Bila melebihi, jalur mewakili TOTAL REALISASI. Bagian yang masih
        // di dalam pagu menempati pagu/realisasi dari jalur, sisanya luberan.
        // Garisnya jatuh tepat di pertemuan keduanya — jadi posisinya ikut
        // proporsi, bukan dipatok di tengah.
        //
        // Versi pertama mematoknya di 50%, yang hanya benar bila serapannya
        // kebetulan tepat 200%.
        // =============================================================
        if ($s['melebihi']) {
            $dalam = $s['pagu'] / $s['realisasi'] * 100;
            $luar  = 100 - $dalam;
        } else {
            $dalam = max(0.0, $s['persen']);
            $luar  = 0.0;
        }

        $judul = $opsi['judul'] ?? ($rp($s['realisasi']) . ' dari pagu ' . $rp($s['pagu']));
        $lebar = $opsi['lebar'] ?? '100%';

        $html = '<div class="serapan" style="width:' . esc($lebar, 'attr') . '" title="' . esc($judul, 'attr') . '">'
            . '<div class="serapan-jalur' . ($s['melebihi'] ? ' serapan-lebih' : '') . '">'
            . '<div class="serapan-isi" style="width:' . round($dalam, 2) . '%"></div>';

        if ($luar > 0) {
            $html .= '<div class="serapan-luber" style="width:' . round($luar, 2) . '%"></div>';
        }

        $html .= '</div>';

        if ($opsi['teks'] ?? true) {
            $persen = number_format($s['persen'], 1, ',', '.') . '%';

            $html .= '<div class="serapan-ket small ' . ($s['melebihi'] ? 'text-danger fw-semibold' : 'text-muted') . '">';

            if ($s['melebihi']) {
                // Ikon + kata, bukan warna saja — supaya tetap terbaca saat
                // dicetak hitam-putih dan bagi yang sulit membedakan warna.
                $html .= '<i class="fas fa-triangle-exclamation me-1"></i>'
                    . '<span class="serapan-nilai">' . esc($persen) . '</span>'
                    . ' &mdash; lebih <span class="serapan-nilai">' . esc($rp($s['lebih'])) . '</span>';
            } else {
                $html .= esc($persen) . ' terserap';
            }

            $html .= '</div>';
        }

        return $html . '</div>';
    }
}

if (! function_exists('serapan_gaya')) {
    /**
     * Gaya bar serapan — ditempel sekali per halaman yang memakainya.
     *
     * Ditaruh di helper, bukan disalin ke tiap view, supaya bentuk penandanya
     * tidak menyimpang antara layar MONEV dan layar input anggaran.
     */
    function serapan_gaya(): string
    {
        static $sudah = false;

        if ($sudah) {
            return '';
        }

        $sudah = true;

        return <<<'HTML'
<style>
    .serapan-jalur {
        position: relative;
        display: flex;
        height: 6px;
        background: #e9ecef;
        border-radius: 3px;
        overflow: hidden;
    }
    .serapan-isi   { background: #0a8f50; }
    .serapan-luber { background: #d64545; }

    /* Garis batas pagu — digambar di UJUNG bagian yang masih di dalam pagu,
       sehingga posisinya selalu tepat berapa pun serapannya. */
    .serapan-lebih .serapan-isi {
        border-right: 2px solid #212529;
        box-sizing: border-box;
    }
    /* Keterangan BOLEH turun baris.
       Versi pertama memakai white-space:nowrap, sehingga pada kolom sempit
       (layar MONEV memberinya 120px) kalimat "156,1% - lebih Rp235.808.936"
       meluber keluar kolom dan menabrak kolom sebelahnya. Yang perlu tidak
       terpenggal hanya angkanya, bukan seluruh kalimat. */
    .serapan-ket { margin-top: .15rem; line-height: 1.25; }
    .serapan-ket .serapan-nilai { white-space: nowrap; }
</style>
HTML;
    }
}
