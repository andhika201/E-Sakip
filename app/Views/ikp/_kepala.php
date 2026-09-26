<?php
/**
 * Kepala bersama halaman IKP (admin OPD): cangkang shell_atas + judul +
 * pemilih tahun + navigasi antarhalaman IKP.
 *
 * Dipanggil: <?= $this->include('ikp/_kepala') ?> — partial ini hanya melihat
 * DATA view (dari controller / setVar), bukan variabel lokal berkas pemanggil.
 *
 * Data yang dibaca: $title, $scope, $tahun, $tahunList, $periode, $aktif, $u,
 * opsional $tanpaTahun (form), $subJudul.
 */
$this->setVar('shellCss', (string) @file_get_contents(FCPATH . 'assets/css/ikp.css'));

$req  = service('request');
$qs   = $req->getGet();
$nav  = [
    'index'     => ['adminopd/ikp', 'fa-list-check', 'Indikator & Target'],
    'breakdown' => ['adminopd/ikp/breakdown', 'fa-table-cells', 'Breakdown Target'],
    'realisasi' => ['adminopd/ikp/realisasi', 'fa-pen-to-square', 'Realisasi Bulanan'],
    'rekap'     => ['adminopd/ikp/rekap', 'fa-chart-column', 'Rekap Triwulan'],
    'inovasi'   => ['adminopd/ikp/inovasi', 'fa-lightbulb', 'Rencana Inovasi'],
];
$periodeTeks = 'Periode RPJMD ' . (int) $periode['awal'] . '–' . (int) $periode['akhir'];
?>
<?= $this->include('templates/shell_atas') ?>

<div class="ikp-kepala">
    <div class="ic"><i class="fas fa-bullseye"></i></div>
    <div class="isi">
        <h2><?= esc($title ?? 'Kinerja Prioritas (IKP)') ?></h2>
        <p>
            <i class="fas fa-building me-1"></i><?= esc($scope['opd_nama'] ?? '-') ?>
            <span class="mx-1">·</span><?= esc($periodeTeks) ?>
            <?php if (! empty($scope['can_pick'])): ?>
                <span class="mx-1">·</span><a href="<?= base_url('adminopd/ikp') ?>" class="small">ganti OPD</a>
            <?php endif; ?>
        </p>
        <?php if (! empty($subJudul)): ?><p class="mt-1"><?= esc($subJudul) ?></p><?php endif; ?>
    </div>
    <?php if (empty($tanpaTahun)): ?>
        <nav class="ikp-tahun" aria-label="Pilih tahun">
            <span class="lbl">Tahun</span>
            <?php foreach ($tahunList as $th): ?>
                <?php $q = $qs; $q['tahun'] = $th; ?>
                <a href="<?= esc(current_url() . '?' . http_build_query($q), 'attr') ?>"
                   class="<?= (int) $th === (int) $tahun ? 'aktif' : '' ?>"
                   <?= (int) $th === (int) $tahun ? 'aria-current="true"' : '' ?>><?= (int) $th ?></a>
            <?php endforeach; ?>
        </nav>
    <?php endif; ?>
</div>

<nav class="ikp-nav" aria-label="Menu Kinerja Prioritas">
    <?php foreach ($nav as $kunci => [$path, $ikon, $label]): ?>
        <a href="<?= esc($u($path, ['tahun' => $tahun]), 'attr') ?>" class="<?= ($aktif ?? '') === $kunci ? 'aktif' : '' ?>">
            <i class="fas <?= $ikon ?>"></i><?= esc($label) ?>
        </a>
    <?php endforeach; ?>
    <a href="<?= esc($u('adminopd/ikp/lampiran-pk', ['tahun' => $tahun]), 'attr') ?>" target="_blank" rel="noopener">
        <i class="fas fa-file-pdf"></i>Lampiran PK
    </a>
</nav>
<script>
    // Di ponsel menu IKP menggulir mendatar: pastikan halaman aktif terlihat.
    (function () {
        var a = document.querySelector('.ikp-nav a.aktif');
        if (a && a.parentNode.scrollWidth > a.parentNode.clientWidth) {
            a.parentNode.scrollLeft = Math.max(0, a.offsetLeft - a.parentNode.offsetLeft - 16);
        }
    })();
</script>
