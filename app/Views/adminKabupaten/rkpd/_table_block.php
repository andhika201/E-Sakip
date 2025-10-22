<?php
// expects $rktdata (array of indicators) and $currentOpd (opd array)
helper('format_helper');
?>

<div class="table-responsive">
  <table class="table table-bordered">
    <thead>
      <tr>
        <th>No</th>
        <th>OPD</th>
        <th>Sasaran</th>
        <th>Indikator</th>
        <th>Satuan</th>
        <th>Target</th>
        <th>Program</th>
        <th>Kegiatan</th>
        <th>Subkegiatan</th>
        <th>Anggaran</th>
        <th>Aksi</th>
      </tr>
    </thead>
    <tbody>
      <?php
      $no = 1;
      if (empty($rktdata)):
        ?>
        <tr>
          <td colspan="11" class="text-center">Tidak ada data</td>
        </tr>
      <?php else: ?>
        <?php foreach ($rktdata as $ind): ?>
          <?php
          // compute total rows for indicator (like sebelumnya)
          $totalSub = 0;
          if (!empty($ind['rkts'])) {
            foreach ($ind['rkts'] as $rkt) {
              if (!empty($rkt['kegiatan'])) {
                foreach ($rkt['kegiatan'] as $keg) {
                  $subCount = count($keg['subkegiatan'] ?? []);
                  $totalSub += ($subCount > 0 ? $subCount : 1);
                }
              } else {
                $totalSub += 1;
              }
            }
          } else {
            $totalSub = 1;
          }
          $firstIndicatorRow = true;
          ?>
          <?php if (empty($ind['rkts'])): ?>
            <tr>
              <td><?= $no++ ?></td>
              <td><?= esc($currentOpd['nama_opd'] ?? '-') ?></td>
              <td><?= esc($ind['sasaran'] ?? '-') ?></td>
              <td><?= esc($ind['indikator_sasaran'] ?? '-') ?></td>
              <td><?= esc($ind['satuan'] ?? '-') ?></td>
              <td><?= esc($ind['target'] ?? '-') ?></td>
              <td>-</td>
              <td>-</td>
              <td>-</td>
              <td>-</td>
              <td><a href="<?= base_url('adminkab/rkpd/tambah') ?>" class="btn btn-sm btn-primary">Tambah</a></td>
            </tr>
          <?php else: ?>
            <?php foreach ($ind['rkts'] as $rkt): ?>
              <?php $firstRktRow = true; ?>
              <?php if (!empty($rkt['kegiatan'])): ?>
                <?php foreach ($rkt['kegiatan'] as $keg): ?>
                  <?php if (!empty($keg['subkegiatan'])): ?>
                    <?php foreach ($keg['subkegiatan'] as $sub): ?>
                      <tr>
                        <?php if ($firstIndicatorRow): ?>
                          <td rowspan="<?= $totalSub ?>"><?= $no++ ?></td>
                          <td rowspan="<?= $totalSub ?>"><?= esc($currentOpd['nama_opd'] ?? '-') ?></td>
                          <td rowspan="<?= $totalSub ?>"><?= esc($ind['sasaran'] ?? '-') ?></td>
                          <td rowspan="<?= $totalSub ?>"><?= esc($ind['indikator_sasaran'] ?? '-') ?></td>
                          <td rowspan="<?= $totalSub ?>"><?= esc($ind['satuan'] ?? '-') ?></td>
                          <td rowspan="<?= $totalSub ?>"><?= esc($ind['target'] ?? '-') ?></td>
                          <?php $firstIndicatorRow = false; ?>
                        <?php endif; ?>

                        <?php if ($firstRktRow): ?>
                          <td rowspan="<?= $totalSub ?>"><?= esc($rkt['program_nama'] ?? '-') ?></td>
                          <?php $firstRktRow = false; ?>
                        <?php endif; ?>

                        <td><?= esc($keg['nama_kegiatan'] ?? '-') ?></td>
                        <td><?= esc($sub['nama_subkegiatan'] ?? '-') ?></td>
                        <td class="text-end"><?= formatRupiah($sub['target_anggaran'] ?? null) ?></td>
                        <td>
                          <a href="<?= base_url('adminkab/rkpd/delete/' . $rkt['id']) ?>" class="btn btn-sm btn-danger"
                            onclick="return confirm('Hapus RKT ini?')">Hapus RKT</a>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  <?php else: ?>
                    <tr>
                      <?php if ($firstIndicatorRow): ?>
                        <td rowspan="<?= $totalSub ?>"><?= $no++ ?></td>
                        <td rowspan="<?= $totalSub ?>"><?= esc($currentOpd['nama_opd'] ?? '-') ?></td>
                        <td rowspan="<?= $totalSub ?>"><?= esc($ind['sasaran'] ?? '-') ?></td>
                        <td rowspan="<?= $totalSub ?>"><?= esc($ind['indikator_sasaran'] ?? '-') ?></td>
                        <td rowspan="<?= $totalSub ?>"><?= esc($ind['satuan'] ?? '-') ?></td>
                        <td rowspan="<?= $totalSub ?>"><?= esc($ind['target'] ?? '-') ?></td>
                        <?php $firstIndicatorRow = false; ?>
                      <?php endif; ?>

                      <?php if ($firstRktRow): ?>
                        <td rowspan="<?= $totalSub ?>"><?= esc($rkt['program_nama'] ?? '-') ?></td>
                        <?php $firstRktRow = false; ?>
                      <?php endif; ?>

                      <td><?= esc($keg['nama_kegiatan'] ?? '-') ?></td>
                      <td>-</td>
                      <td>-</td>
                      <td>
                        <a href="<?= base_url('adminkab/rkpd/delete/' . $rkt['id']) ?>" class="btn btn-sm btn-danger"
                          onclick="return confirm('Hapus RKT ini?')">Hapus RKT</a>
                      </td>
                    </tr>
                  <?php endif; ?>
                <?php endforeach; ?>
              <?php else: ?>
                <tr>
                  <?php if ($firstIndicatorRow): ?>
                    <td rowspan="<?= $totalSub ?>"><?= $no++ ?></td>
                    <td rowspan="<?= $totalSub ?>"><?= esc($currentOpd['nama_opd'] ?? '-') ?></td>
                    <td rowspan="<?= $totalSub ?>"><?= esc($ind['sasaran'] ?? '-') ?></td>
                    <td rowspan="<?= $totalSub ?>"><?= esc($ind['indikator_sasaran'] ?? '-') ?></td>
                    <td rowspan="<?= $totalSub ?>"><?= esc($ind['satuan'] ?? '-') ?></td>
                    <td rowspan="<?= $totalSub ?>"><?= esc($ind['target'] ?? '-') ?></td>
                    <?php $firstIndicatorRow = false; ?>
                  <?php endif; ?>

                  <td><?= esc($rkt['program_nama'] ?? '-') ?></td>
                  <td>-</td>
                  <td class="text-end"><?= formatRupiah($rkt['program_anggaran'] ?? null) ?></td>
                  <td>
                    <a href="<?= base_url('adminkab/rkpd/delete/' . $rkt['id']) ?>" class="btn btn-sm btn-danger"
                      onclick="return confirm('Hapus RKT ini?')">Hapus RKT</a>
                  </td>
                </tr>
              <?php endif; ?>
            <?php endforeach; ?>
          <?php endif; ?>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>