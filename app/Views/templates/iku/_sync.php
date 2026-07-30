<?php

/**
 * Pratinjau sync IKU dari RPJMD / Renstra — dipakai bersama admin kabupaten & OPD.
 *
 * Yang dikirim ke server hanya ID sumber lewat `pilih[sasaran_id][indikator_id]`;
 * isi sasaran/indikator dibaca ulang dari DB oleh model.
 *
 * @var array  $kandidat       sasaran sumber + indikator + target
 * @var array  $daftar_periode opsi periode sumber
 * @var array  $periode        periode terpilih (key, period, years, tahun_mulai, tahun_akhir)
 * @var array  $years          tahun periode terpilih
 * @var string $sumber_label   'RPJMD' | 'Renstra'
 * @var string $action_url     URL submit
 * @var string $back_url       URL kembali
 * @var string $filter_url     URL untuk ganti periode
 */
$kandidat       = $kandidat ?? [];
$daftar_periode = $daftar_periode ?? [];
$periode        = $periode ?? [];
$years          = !empty($years) ? $years : [];
$sumber_label   = $sumber_label ?? 'RPJMD';
$action_url     = $action_url ?? '';
$back_url       = $back_url ?? '';
$filter_url     = $filter_url ?? '';

$totalIndikator = 0;
$totalBaru      = 0;
foreach ($kandidat as $s) {
    $totalIndikator += count($s['indikator'] ?? []);
    $totalBaru      += (int) ($s['jumlah_baru'] ?? 0);
}
?>

<form method="get" action="<?= esc($filter_url, 'attr') ?>" class="row g-2 mb-3 align-items-end">
    <div class="col-md-5">
        <label class="form-label fw-semibold text-secondary mb-1">Periode <?= esc($sumber_label) ?></label>
        <select name="periode" class="form-select" onchange="this.form.submit()">
            <?php foreach ($daftar_periode as $key => $p): ?>
                <option value="<?= esc($key) ?>" <?= (($periode['key'] ?? '') === $key) ? 'selected' : '' ?>>
                    <?= esc($p['period']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
</form>

<div class="alert alert-info d-flex flex-wrap gap-3 align-items-center">
    <div>
        <i class="fas fa-info-circle me-1"></i>
        Data di bawah diambil dari <strong><?= esc($sumber_label) ?></strong> periode
        <strong><?= esc($periode['period'] ?? '-') ?></strong>.
    </div>
    <div class="ms-auto small">
        <span class="badge bg-secondary"><?= $totalIndikator ?> indikator tersedia</span>
        <span class="badge bg-success"><?= $totalBaru ?> belum ada di IKU</span>
    </div>
</div>

<?php if (empty($kandidat)): ?>

    <div class="text-center py-5 my-4">
        <i class="bi bi-inbox text-secondary" style="font-size: 3rem;"></i>
        <h5 class="mt-3 text-secondary">
            Tidak ada sasaran <?= esc($sumber_label) ?> pada periode ini.
        </h5>
        <a href="<?= esc($back_url, 'attr') ?>" class="btn btn-secondary mt-3">
            <i class="fas fa-arrow-left me-1"></i> Kembali
        </a>
    </div>

<?php else: ?>

    <form method="post" action="<?= esc($action_url, 'attr') ?>" id="sync-form">
        <?= csrf_field() ?>
        <input type="hidden" name="periode" value="<?= esc($periode['key'] ?? '', 'attr') ?>">

        <div class="d-flex gap-2 mb-2 flex-wrap">
            <button type="button" class="btn btn-outline-success btn-sm" id="pilih-semua">
                <i class="fas fa-check-double me-1"></i> Pilih semua yang belum ada
            </button>
            <button type="button" class="btn btn-outline-secondary btn-sm" id="kosongkan">
                <i class="fas fa-times me-1"></i> Kosongkan pilihan
            </button>
            <span class="ms-auto align-self-center small text-muted">
                Terpilih: <strong id="jumlah-terpilih">0</strong> indikator
            </span>
        </div>

        <div class="table-responsive table-wrap">
            <table class="table table-bordered table-striped align-middle small iku-table" data-no-paginate>
                <thead class="table-success text-dark">
                    <tr class="text-center">
                        <th style="width:38px;">
                            <input type="checkbox" class="form-check-input" id="centang-induk" title="Pilih semua yang belum ada">
                        </th>
                        <th>Sasaran <?= esc($sumber_label) ?></th>
                        <th>Indikator</th>
                        <th>Satuan</th>
                        <th colspan="<?= max(1, count($years)) ?>">Target per Tahun</th>
                        <th>Status di IKU</th>
                    </tr>
                    <tr class="text-center">
                        <th colspan="4"></th>
                        <?php if (empty($years)): ?>
                            <th>-</th>
                        <?php else: ?>
                            <?php foreach ($years as $tahun): ?>
                                <th><?= esc($tahun) ?></th>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        <th></th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($kandidat as $sasaran): ?>
                        <?php
                        $indikators   = $sasaran['indikator'] ?? [];
                        $barisSasaran = max(1, count($indikators));
                        $daftarBaris  = !empty($indikators) ? $indikators : [null];
                        $barisPertama = true;
                        ?>

                        <?php foreach ($daftarBaris as $ind): ?>
                            <tr>
                                <?php if ($ind === null): ?>
                                    <td class="text-center">-</td>
                                <?php else: ?>
                                    <td class="text-center">
                                        <input type="checkbox"
                                               class="form-check-input centang-indikator"
                                               name="pilih[<?= (int) $sasaran['sumber_id'] ?>][<?= (int) $ind['sumber_id'] ?>]"
                                               value="1"
                                               data-baru="<?= $ind['sudah_ada'] ? '0' : '1' ?>"
                                               <?= $ind['sudah_ada'] ? '' : 'checked' ?>>
                                    </td>
                                <?php endif; ?>

                                <?php if ($barisPertama): ?>
                                    <td rowspan="<?= $barisSasaran ?>" class="text-start align-middle">
                                        <?= esc($sasaran['sasaran'] ?? '-') ?>
                                        <?php if (!empty($sasaran['induk'])): ?>
                                            <div class="text-muted small mt-1">
                                                <i class="fas fa-level-up-alt fa-rotate-90 me-1"></i>
                                                <?= esc($sasaran['induk']) ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <?php $barisPertama = false; ?>
                                <?php endif; ?>

                                <?php if ($ind === null): ?>
                                    <td colspan="<?= 3 + max(1, count($years)) ?>" class="text-center text-muted">
                                        Sasaran ini belum punya indikator di <?= esc($sumber_label) ?>.
                                    </td>
                                <?php else: ?>
                                    <td class="text-start"><?= esc($ind['indikator'] ?? '-') ?></td>
                                    <td class="text-center"><?= esc(($ind['satuan_nama'] ?? '') !== '' ? $ind['satuan_nama'] : '-') ?></td>

                                    <?php if (empty($years)): ?>
                                        <td class="text-center">-</td>
                                    <?php else: ?>
                                        <?php foreach ($years as $tahun): ?>
                                            <?php $nilai = $ind['target'][(int) $tahun] ?? null; ?>
                                            <td class="text-center"><?= esc(($nilai === null || $nilai === '') ? '-' : $nilai) ?></td>
                                        <?php endforeach; ?>
                                    <?php endif; ?>

                                    <td class="text-center">
                                        <?php if ($ind['sudah_ada']): ?>
                                            <span class="badge bg-secondary">Sudah ada</span>
                                        <?php else: ?>
                                            <span class="badge bg-success">Baru</span>
                                        <?php endif; ?>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="d-flex justify-content-between mt-4">
            <a href="<?= esc($back_url, 'attr') ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i> Kembali
            </a>
            <button type="submit" class="btn btn-success">
                <i class="fas fa-download me-1"></i> Salin ke IKU
            </button>
        </div>
    </form>

    <script>
        (function () {
            const form     = document.getElementById('sync-form');
            const induk    = document.getElementById('centang-induk');
            const tombolAll = document.getElementById('pilih-semua');
            const tombolNol = document.getElementById('kosongkan');
            const jumlahEl  = document.getElementById('jumlah-terpilih');

            const semua = () => Array.from(form.querySelectorAll('.centang-indikator'));

            function hitung() {
                jumlahEl.textContent = semua().filter(c => c.checked).length;
            }

            /** Indikator yang sudah ada di IKU sengaja tidak ikut "pilih semua". */
            function setSemua(nilai, hanyaBaru) {
                semua().forEach(c => {
                    if (hanyaBaru && c.dataset.baru !== '1') return;
                    c.checked = nilai;
                });
                hitung();
            }

            induk.checked = true;
            induk.addEventListener('change', () => setSemua(induk.checked, induk.checked));
            tombolAll.addEventListener('click', () => setSemua(true, true));
            tombolNol.addEventListener('click', () => { setSemua(false, false); induk.checked = false; });
            form.addEventListener('change', e => {
                if (e.target.classList.contains('centang-indikator')) hitung();
            });

            form.addEventListener('submit', e => {
                if (semua().filter(c => c.checked).length === 0) {
                    e.preventDefault();
                    alert('Pilih minimal satu indikator untuk disalin.');
                }
            });

            hitung();
        })();
    </script>

<?php endif; ?>
