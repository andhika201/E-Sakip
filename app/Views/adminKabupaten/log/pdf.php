<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <?= $this->include('templates/pdf_style') ?>
    <style>
        .log-table { width: 100%; border-collapse: collapse; }
        .log-table th, .log-table td { border: 0.5px solid #999; padding: 3px 4px; font-size: 8px; vertical-align: top; }
        .log-table thead th { background: #00743e; color: #fff; text-align: center; }
        .meta { font-size: 9px; color: #555; margin-bottom: 8px; }
        .nowrap { white-space: nowrap; }
    </style>
</head>

<body>
    <?= $this->include('templates/pdf_kop', [
        'judul'    => 'Log Aktivitas Pengguna',
        'namaUnit' => 'Kabupaten Pringsewu',
    ]) ?>
    <div class="meta">
        Dicetak: <?= esc($dicetak) ?> oleh <?= esc($oleh ?? '-') ?>.
        <?php
        $aktif = array_filter([
            'Kata kunci' => $filters['q'] ?? '',
            'Aksi'       => $filters['action'] ?? '',
            'Modul'      => $filters['module'] ?? '',
            'User'       => $filters['user'] ?? '',
            'Dari'       => $filters['from'] ?? '',
            'Sampai'     => $filters['to'] ?? '',
        ], static fn($v) => $v !== '');
        ?>
        <?php if (!empty($aktif)): ?>
            Filter:
            <?php $i = 0; foreach ($aktif as $k => $v): ?>
                <?= $i++ ? ', ' : '' ?><strong><?= esc($k) ?></strong>: <?= esc($v) ?>
            <?php endforeach; ?>.
        <?php endif; ?>
        Total baris: <?= count($logs) ?><?= !empty($truncated) ? ' (dibatasi ' . (int) $maxRows . ')' : '' ?>.
    </div>

    <table class="log-table">
        <thead>
            <tr>
                <th style="width:18px;">No</th>
                <th class="nowrap">Waktu</th>
                <th>User</th>
                <th>Role</th>
                <th>Aksi</th>
                <th>Modul</th>
                <th>Deskripsi</th>
                <th>IP</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($logs)): ?>
                <tr><td colspan="8" style="text-align:center;">Tidak ada data.</td></tr>
            <?php else: ?>
                <?php $no = 1; foreach ($logs as $l): ?>
                    <tr>
                        <td style="text-align:center;"><?= $no++ ?></td>
                        <td class="nowrap"><?= esc($l['created_at'] ?? '-') ?></td>
                        <td><?= esc($l['username'] ?? '-') ?></td>
                        <td><?= esc($l['role'] ?? '-') ?></td>
                        <td><?= esc($l['action'] ?? '-') ?></td>
                        <td><?= esc($l['module'] ?? '-') ?></td>
                        <td><?= esc($l['description'] ?? '-') ?></td>
                        <td class="nowrap"><?= esc($l['ip_address'] ?? '-') ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</body>

</html>
