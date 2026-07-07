<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= esc($title ?? 'Master Data') ?></title>
    <?= $this->include('adminKabupaten/templates/style.php'); ?>
    <style>
        .master-table thead th { position: sticky; top: 0; z-index: 2; white-space: nowrap; }
        .master-table td, .master-table th { vertical-align: middle; }
        .tab-table-wrap { max-height: 62vh; overflow: auto; }
        .nav-tabs .nav-link.active { font-weight: 600; }
        .matrix-table th, .matrix-table td { text-align: center; vertical-align: middle; }
        .matrix-table td:first-child, .matrix-table th:first-child { text-align: left; }
        /* DataTables: rapikan kontrol search/length */
        .dataTables_wrapper .row { margin: 0; }
        .dataTables_wrapper .dataTables_filter input { border-radius: .375rem; }
        div.dt-buttons + .dataTables_filter { float: right; }
        .master-table { width: 100% !important; }
    </style>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
</head>

<body class="bg-light min-vh-100 d-flex flex-column position-relative">
    <div id="main-content" class="content-wrapper d-flex flex-column" style="transition: margin-left .3s ease;">

        <?= $this->include('adminKabupaten/templates/header.php'); ?>
        <?= $this->include('adminKabupaten/templates/sidebar.php'); ?>

        <main class="flex-fill p-4 mt-2">
            <div class="bg-white rounded shadow p-4">
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
                    <div>
                        <h2 class="h4 fw-bold text-success mb-1">⚙️ Master Data — Super Admin</h2>
                        <p class="text-muted small mb-0">Kelola pegawai, pangkat, jabatan, OPD, user, role &amp; permission, dan satuan dalam satu tempat.</p>
                    </div>
                    <a href="<?= base_url('adminkab/pegawai/sync') ?>" class="btn btn-success">
                        <i class="fas fa-sync-alt me-1"></i> Sinkron dari SIMPEG
                    </a>
                </div>
                <hr class="mt-2">
                <p class="text-muted small mb-3">
                    <i class="fas fa-cloud-download-alt me-1"></i>
                    Tombol <strong>Sinkron dari SIMPEG</strong> menarik data <strong>OPD, Pangkat, Jabatan &amp; Pegawai</strong> dari SIMPEG Pringsewu.
                </p>

                <?php if (session()->getFlashdata('success')): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <?= esc(session()->getFlashdata('success')) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                <?php if (session()->getFlashdata('error')): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <?= esc(session()->getFlashdata('error')) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php
                $tab = $activeTab ?? 'pegawai';
                $isActive = fn($n) => $n === $tab ? 'active' : '';
                $isPane   = fn($n) => $n === $tab ? 'show active' : '';
                // json aman untuk atribut (tombol edit)
                $j = fn($row) => esc(json_encode($row, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE), 'attr');
                $delConfirm = "onclick=\"return confirm('Yakin hapus data ini?')\"";

                // Tombol "Sync dari SIMPEG" per-tab (hanya bila SIMPEG terkonfigurasi)
                $syncBtn = function (string $entity, string $label) use ($simpegConfigured) {
                    if (empty($simpegConfigured)) {
                        return '';
                    }
                    return '<form action="' . base_url('adminkab/pegawai/sync/run') . '" method="post" class="d-inline"'
                        . ' onsubmit="return confirm(\'Sinkron ' . $label . ' dari SIMPEG?\');">'
                        . csrf_field()
                        . '<input type="hidden" name="entity" value="' . $entity . '">'
                        . '<input type="hidden" name="back" value="' . $entity . '">'
                        . '<button type="submit" class="btn btn-outline-success btn-sm"><i class="fas fa-sync-alt me-1"></i> Sync dari SIMPEG</button>'
                        . '</form>';
                };
                ?>

                <ul class="nav nav-tabs mb-3" role="tablist">
                    <li class="nav-item"><button class="nav-link <?= $isActive('pegawai') ?>" data-bs-toggle="tab" data-bs-target="#pane-pegawai" type="button">Pegawai</button></li>
                    <li class="nav-item"><button class="nav-link <?= $isActive('pangkat') ?>" data-bs-toggle="tab" data-bs-target="#pane-pangkat" type="button">Pangkat</button></li>
                    <li class="nav-item"><button class="nav-link <?= $isActive('jabatan') ?>" data-bs-toggle="tab" data-bs-target="#pane-jabatan" type="button">Jabatan</button></li>
                    <li class="nav-item"><button class="nav-link <?= $isActive('opd') ?>" data-bs-toggle="tab" data-bs-target="#pane-opd" type="button">OPD</button></li>
                    <li class="nav-item"><button class="nav-link <?= $isActive('user') ?>" data-bs-toggle="tab" data-bs-target="#pane-user" type="button">User</button></li>
                    <li class="nav-item"><button class="nav-link <?= $isActive('role') ?>" data-bs-toggle="tab" data-bs-target="#pane-role" type="button">Role &amp; Permission</button></li>
                    <li class="nav-item"><button class="nav-link <?= $isActive('satuan') ?>" data-bs-toggle="tab" data-bs-target="#pane-satuan" type="button">Satuan</button></li>
                </ul>

                <div class="tab-content">

                    <!-- ================= PEGAWAI ================= -->
                    <div class="tab-pane fade <?= $isPane('pegawai') ?>" id="pane-pegawai">
                        <div class="d-flex justify-content-end mb-2 gap-2">
                            <?= $syncBtn('pegawai', 'Pegawai') ?>
                            <button class="btn btn-success btn-sm" data-add="modal-pegawai"><i class="fas fa-plus me-1"></i> Tambah Pegawai</button>
                        </div>
                        <div class="table-responsive tab-table-wrap">
                            <!-- Pegawai: DataTables server-side (data ribuan baris) -->
                            <table id="tbl-pegawai" class="table table-bordered table-striped small master-table" style="width:100%">
                                <thead class="table-success text-center"><tr>
                                    <th>No</th><th>Nama</th><th>NIP</th><th>OPD</th><th>Jabatan</th><th>Pangkat</th><th>Level</th><th>Aksi</th>
                                </tr></thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>

                    <!-- ================= PANGKAT ================= -->
                    <div class="tab-pane fade <?= $isPane('pangkat') ?>" id="pane-pangkat">
                        <div class="d-flex justify-content-end mb-2 gap-2">
                            <?= $syncBtn('pangkat', 'Pangkat') ?>
                            <button class="btn btn-success btn-sm" data-add="modal-pangkat"><i class="fas fa-plus me-1"></i> Tambah Pangkat</button>
                        </div>
                        <div class="table-responsive tab-table-wrap">
                            <table class="table table-bordered table-striped small master-table">
                                <thead class="table-success text-center"><tr><th>No</th><th>Nama Pangkat</th><th>Golongan</th><th>Aksi</th></tr></thead>
                                <tbody>
                                <?php $n = 1; foreach ($pangkat as $p): ?>
                                    <tr>
                                        <td class="text-center"><?= $n++ ?></td>
                                        <td><?= esc($p['nama_pangkat']) ?></td>
                                        <td><?= esc($p['golongan'] ?? '-') ?></td>
                                        <td class="text-center text-nowrap">
                                            <button class="btn btn-warning btn-sm" data-edit="modal-pangkat" data-json='<?= $j(['id'=>$p['id'],'nama_pangkat'=>$p['nama_pangkat'],'golongan'=>$p['golongan']]) ?>'><i class="fas fa-edit"></i></button>
                                            <a href="<?= base_url('adminkab/master/pangkat/delete/' . (int)$p['id']) ?>" <?= $delConfirm ?> class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($pangkat)): ?><tr><td colspan="4" class="text-center text-muted py-3">Belum ada data.</td></tr><?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- ================= JABATAN ================= -->
                    <div class="tab-pane fade <?= $isPane('jabatan') ?>" id="pane-jabatan">
                        <div class="d-flex justify-content-end mb-2 gap-2">
                            <?= $syncBtn('jabatan', 'Jabatan') ?>
                            <button class="btn btn-success btn-sm" data-add="modal-jabatan"><i class="fas fa-plus me-1"></i> Tambah Jabatan</button>
                        </div>
                        <div class="table-responsive tab-table-wrap">
                            <table class="table table-bordered table-striped small master-table">
                                <thead class="table-success text-center"><tr><th>No</th><th>Nama Jabatan</th><th>OPD</th><th>Eselon</th><th>Aksi</th></tr></thead>
                                <tbody>
                                <?php $n = 1; foreach ($jabatan as $jb): ?>
                                    <tr>
                                        <td class="text-center"><?= $n++ ?></td>
                                        <td><?= esc($jb['nama_jabatan']) ?></td>
                                        <td><?= esc($jb['nama_opd'] ?? '-') ?></td>
                                        <td class="text-center"><?= esc($jb['eselon'] ?? '-') ?></td>
                                        <td class="text-center text-nowrap">
                                            <button class="btn btn-warning btn-sm" data-edit="modal-jabatan" data-json='<?= $j(['id'=>$jb['id'],'nama_jabatan'=>$jb['nama_jabatan'],'opd_id'=>$jb['opd_id'],'eselon'=>$jb['eselon']]) ?>'><i class="fas fa-edit"></i></button>
                                            <a href="<?= base_url('adminkab/master/jabatan/delete/' . (int)$jb['id']) ?>" <?= $delConfirm ?> class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($jabatan)): ?><tr><td colspan="5" class="text-center text-muted py-3">Belum ada data.</td></tr><?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- ================= OPD ================= -->
                    <div class="tab-pane fade <?= $isPane('opd') ?>" id="pane-opd">
                        <div class="d-flex justify-content-end mb-2 gap-2">
                            <?= $syncBtn('opd', 'OPD') ?>
                            <button class="btn btn-success btn-sm" data-add="modal-opd"><i class="fas fa-plus me-1"></i> Tambah OPD</button>
                        </div>
                        <div class="table-responsive tab-table-wrap">
                            <table class="table table-bordered table-striped small master-table">
                                <thead class="table-success text-center"><tr><th>No</th><th>Nama OPD</th><th>Singkatan</th><th>Alamat</th><th>Aksi</th></tr></thead>
                                <tbody>
                                <?php $n = 1; foreach ($opd as $o): ?>
                                    <tr>
                                        <td class="text-center"><?= $n++ ?></td>
                                        <td><?= esc($o['nama_opd']) ?></td>
                                        <td><?= esc($o['singkatan'] ?? '-') ?></td>
                                        <td><?= esc($o['alamat_opd'] ?? '-') ?></td>
                                        <td class="text-center text-nowrap">
                                            <button class="btn btn-warning btn-sm" data-edit="modal-opd" data-json='<?= $j(['id'=>$o['id'],'nama_opd'=>$o['nama_opd'],'singkatan'=>$o['singkatan'],'alamat_opd'=>$o['alamat_opd']]) ?>'><i class="fas fa-edit"></i></button>
                                            <a href="<?= base_url('adminkab/master/opd/delete/' . (int)$o['id']) ?>" <?= $delConfirm ?> class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($opd)): ?><tr><td colspan="5" class="text-center text-muted py-3">Belum ada data.</td></tr><?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- ================= USER ================= -->
                    <div class="tab-pane fade <?= $isPane('user') ?>" id="pane-user">
                        <div class="d-flex justify-content-end mb-2">
                            <button class="btn btn-success btn-sm" data-add="modal-user"><i class="fas fa-plus me-1"></i> Tambah User</button>
                        </div>
                        <div class="table-responsive tab-table-wrap">
                            <table class="table table-bordered table-striped small master-table">
                                <thead class="table-success text-center"><tr><th>No</th><th>Username</th><th>Email</th><th>Role</th><th>OPD</th><th>Aktif</th><th>Aksi</th></tr></thead>
                                <tbody>
                                <?php $n = 1; foreach ($users as $u): ?>
                                    <tr>
                                        <td class="text-center"><?= $n++ ?></td>
                                        <td><?= esc($u['username']) ?></td>
                                        <td><?= esc($u['email']) ?></td>
                                        <td><?= esc($u['role_label'] ?? $u['role']) ?></td>
                                        <td><?= esc($u['nama_opd'] ?? '-') ?></td>
                                        <td class="text-center">
                                            <?php if ((int)$u['is_active'] === 1): ?><span class="badge bg-success">Aktif</span><?php else: ?><span class="badge bg-secondary">Nonaktif</span><?php endif; ?>
                                        </td>
                                        <td class="text-center text-nowrap">
                                            <button class="btn btn-warning btn-sm" data-edit="modal-user" data-json='<?= $j(['id'=>$u['user_id'],'username'=>$u['username'],'email'=>$u['email'],'role'=>$u['role'],'opd_id'=>$u['opd_id'],'is_active'=>$u['is_active']]) ?>'><i class="fas fa-edit"></i></button>
                                            <a href="<?= base_url('adminkab/master/user/delete/' . (int)$u['user_id']) ?>" <?= $delConfirm ?> class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($users)): ?><tr><td colspan="7" class="text-center text-muted py-3">Belum ada data.</td></tr><?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- ================= ROLE & PERMISSION ================= -->
                    <div class="tab-pane fade <?= $isPane('role') ?>" id="pane-role">
                        <div class="d-flex justify-content-end mb-2">
                            <button class="btn btn-success btn-sm" data-add="modal-role"><i class="fas fa-plus me-1"></i> Tambah Role</button>
                        </div>
                        <div class="table-responsive mb-4">
                            <table class="table table-bordered table-striped small master-table">
                                <thead class="table-success text-center"><tr><th>No</th><th>Slug</th><th>Label</th><th>Sistem</th><th>#Permission</th><th>#User</th><th>Aksi</th></tr></thead>
                                <tbody>
                                <?php $n = 1; foreach ($roles as $r): ?>
                                    <tr>
                                        <td class="text-center"><?= $n++ ?></td>
                                        <td><code><?= esc($r['name']) ?></code></td>
                                        <td><?= esc($r['label'] ?? '-') ?></td>
                                        <td class="text-center"><?= ((int)$r['is_system'] === 1) ? '<span class="badge bg-info text-dark">Sistem</span>' : '-' ?></td>
                                        <td class="text-center"><?= (int)($r['perm_count'] ?? 0) ?></td>
                                        <td class="text-center"><?= (int)($r['user_count'] ?? 0) ?></td>
                                        <td class="text-center text-nowrap">
                                            <button class="btn btn-warning btn-sm" data-edit="modal-role" data-json='<?= $j(['id'=>$r['id'],'name'=>$r['name'],'label'=>$r['label']]) ?>'><i class="fas fa-edit"></i></button>
                                            <?php if ((int)$r['is_system'] !== 1): ?>
                                                <a href="<?= base_url('adminkab/master/role/delete/' . (int)$r['id']) ?>" <?= $delConfirm ?> class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <h6 class="fw-bold text-success">Matriks Permission</h6>
                        <form action="<?= base_url('adminkab/master/role/permissions') ?>" method="post">
                            <?= csrf_field() ?>
                            <div class="table-responsive tab-table-wrap">
                                <table class="table table-bordered small matrix-table">
                                    <thead class="table-success">
                                        <tr>
                                            <th style="min-width:240px;">Permission</th>
                                            <?php foreach ($roles as $r): ?>
                                                <th><?= esc($r['label'] ?? $r['name']) ?></th>
                                            <?php endforeach; ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php $curGroup = null; ?>
                                        <?php foreach ($permissions as $perm): ?>
                                            <?php if (($perm['grup'] ?? '') !== $curGroup): ?>
                                                <?php $curGroup = $perm['grup'] ?? ''; ?>
                                                <tr class="table-light">
                                                    <td colspan="<?= count($roles) + 1 ?>" class="fw-bold text-success">
                                                        <?= esc($curGroup !== '' ? $curGroup : 'Lainnya') ?>
                                                    </td>
                                                </tr>
                                            <?php endif; ?>
                                            <tr>
                                                <td>
                                                    <strong><?= esc($perm['label'] ?? $perm['name']) ?></strong>
                                                    <div class="text-muted" style="font-size:.75rem;"><code><?= esc($perm['name']) ?></code></div>
                                                </td>
                                                <?php foreach ($roles as $r): ?>
                                                    <?php
                                                    $rid = (int)$r['id']; $pid = (int)$perm['id'];
                                                    $checked = in_array($pid, $rolePermMap[$rid] ?? [], true);
                                                    $isAdmin = ($r['name'] === 'admin');
                                                    ?>
                                                    <td>
                                                        <input type="checkbox" class="form-check-input"
                                                            name="perm[<?= $rid ?>][]" value="<?= $pid ?>"
                                                            <?= ($checked || $isAdmin) ? 'checked' : '' ?>
                                                            <?= $isAdmin ? 'disabled title="Super admin selalu punya semua izin"' : '' ?>>
                                                    </td>
                                                <?php endforeach; ?>
                                            </tr>
                                        <?php endforeach; ?>
                                        <?php if (empty($permissions)): ?>
                                            <tr><td colspan="<?= count($roles) + 1 ?>" class="text-center text-muted py-3">Belum ada permission.</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                            <button type="submit" class="btn btn-success"><i class="fas fa-save me-1"></i> Simpan Matriks Permission</button>
                            <small class="text-muted ms-2">Centang izin per role. Super admin tidak dapat diubah (selalu penuh).</small>
                        </form>
                    </div>

                    <!-- ================= SATUAN ================= -->
                    <div class="tab-pane fade <?= $isPane('satuan') ?>" id="pane-satuan">
                        <div class="d-flex justify-content-end mb-2">
                            <button class="btn btn-success btn-sm" data-add="modal-satuan"><i class="fas fa-plus me-1"></i> Tambah Satuan</button>
                        </div>
                        <div class="table-responsive tab-table-wrap">
                            <table class="table table-bordered table-striped small master-table">
                                <thead class="table-success text-center"><tr><th>No</th><th>Satuan</th><th>Aksi</th></tr></thead>
                                <tbody>
                                <?php $n = 1; foreach ($satuan as $s): ?>
                                    <tr>
                                        <td class="text-center"><?= $n++ ?></td>
                                        <td><?= esc($s['satuan']) ?></td>
                                        <td class="text-center text-nowrap">
                                            <button class="btn btn-warning btn-sm" data-edit="modal-satuan" data-json='<?= $j(['id'=>$s['id'],'satuan'=>$s['satuan']]) ?>'><i class="fas fa-edit"></i></button>
                                            <a href="<?= base_url('adminkab/master/satuan/delete/' . (int)$s['id']) ?>" <?= $delConfirm ?> class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($satuan)): ?><tr><td colspan="3" class="text-center text-muted py-3">Belum ada data.</td></tr><?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div><!-- /tab-content -->
            </div>
        </main>

        <?= $this->include('adminKabupaten/templates/footer.php'); ?>
    </div>

    <?php
    // helper render <option> OPD/jabatan/pangkat/role
    $optOpd = function () use ($opdOptions) { $h=''; foreach ($opdOptions as $o) { $h.='<option value="'.(int)$o['id'].'">'.esc($o['nama_opd']).'</option>'; } return $h; };
    $optJab = function () use ($jabatanOptions) { $h=''; foreach ($jabatanOptions as $o) { $lbl=esc($o['nama_jabatan']).(!empty($o['nama_opd'])?' — '.esc($o['nama_opd']):''); $h.='<option value="'.(int)$o['id'].'">'.$lbl.'</option>'; } return $h; };
    $optPangkat = function () use ($pangkatOptions) { $h=''; foreach ($pangkatOptions as $o) { $lbl=esc($o['nama_pangkat']).(!empty($o['golongan'])?' ('.esc($o['golongan']).')':''); $h.='<option value="'.(int)$o['id'].'">'.$lbl.'</option>'; } return $h; };
    $optRole = function () use ($roleOptions) { $h=''; foreach ($roleOptions as $o) { $h.='<option value="'.esc($o['name']).'">'.esc($o['label'] ?? $o['name']).'</option>'; } return $h; };
    ?>

    <!-- ================= MODALS ================= -->

    <!-- Pegawai -->
    <div class="modal fade" id="modal-pegawai" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
        <form method="post" action="<?= base_url('adminkab/master/pegawai/save') ?>">
            <?= csrf_field() ?><input type="hidden" name="id">
            <div class="modal-header"><h5 class="modal-title">Data Pegawai</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="mb-2"><label class="form-label">Nama</label><input type="text" name="nama_pegawai" class="form-control" required></div>
                <div class="mb-2"><label class="form-label">NIP</label><input type="text" name="nip_pegawai" class="form-control" required></div>
                <div class="mb-2"><label class="form-label">OPD</label><select name="opd_id" class="form-select"><option value="">- Pilih -</option><?= $optOpd() ?></select></div>
                <div class="mb-2"><label class="form-label">Jabatan</label><select name="jabatan_id" class="form-select"><option value="">- Pilih -</option><?= $optJab() ?></select></div>
                <div class="mb-2 form-check">
                    <input type="checkbox" name="is_plt" id="pegawai-is-plt" class="form-check-input" value="1">
                    <label class="form-check-label" for="pegawai-is-plt">Jabatan Plt (Pelaksana Tugas)</label>
                </div>
                <div class="mb-2"><label class="form-label">Pangkat</label><select name="pangkat_id" class="form-select"><option value="">- Pilih -</option><?= $optPangkat() ?></select></div>
                <div class="mb-2"><label class="form-label">Level</label><select name="level" class="form-select"><option value="USER">USER</option><option value="ADMIN">ADMIN</option><option value="PERMITOR">PERMITOR</option><option value="VERIFIKATOR">VERIFIKATOR</option></select></div>
                <small class="text-muted">Pegawai baru: password awal = NIP.</small>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-success">Simpan</button></div>
        </form>
    </div></div></div>

    <!-- Pangkat -->
    <div class="modal fade" id="modal-pangkat" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
        <form method="post" action="<?= base_url('adminkab/master/pangkat/save') ?>">
            <?= csrf_field() ?><input type="hidden" name="id">
            <div class="modal-header"><h5 class="modal-title">Data Pangkat</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="mb-2"><label class="form-label">Nama Pangkat</label><input type="text" name="nama_pangkat" class="form-control" required></div>
                <div class="mb-2"><label class="form-label">Golongan</label><input type="text" name="golongan" class="form-control"></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-success">Simpan</button></div>
        </form>
    </div></div></div>

    <!-- Jabatan -->
    <div class="modal fade" id="modal-jabatan" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
        <form method="post" action="<?= base_url('adminkab/master/jabatan/save') ?>">
            <?= csrf_field() ?><input type="hidden" name="id">
            <div class="modal-header"><h5 class="modal-title">Data Jabatan</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="mb-2"><label class="form-label">Nama Jabatan</label><input type="text" name="nama_jabatan" class="form-control" required></div>
                <div class="mb-2"><label class="form-label">OPD</label><select name="opd_id" class="form-select"><option value="">- Tanpa OPD -</option><?= $optOpd() ?></select></div>
                <div class="mb-2"><label class="form-label">Eselon</label><input type="number" name="eselon" class="form-control" min="0"></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-success">Simpan</button></div>
        </form>
    </div></div></div>

    <!-- OPD -->
    <div class="modal fade" id="modal-opd" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
        <form method="post" action="<?= base_url('adminkab/master/opd/save') ?>">
            <?= csrf_field() ?><input type="hidden" name="id">
            <div class="modal-header"><h5 class="modal-title">Data OPD</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="mb-2"><label class="form-label">Nama OPD</label><input type="text" name="nama_opd" class="form-control" required></div>
                <div class="mb-2"><label class="form-label">Singkatan</label><input type="text" name="singkatan" class="form-control"></div>
                <div class="mb-2"><label class="form-label">Alamat</label><input type="text" name="alamat_opd" class="form-control" maxlength="50"></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-success">Simpan</button></div>
        </form>
    </div></div></div>

    <!-- User -->
    <div class="modal fade" id="modal-user" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
        <form method="post" action="<?= base_url('adminkab/master/user/save') ?>">
            <?= csrf_field() ?><input type="hidden" name="id">
            <div class="modal-header"><h5 class="modal-title">Data User</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="mb-2"><label class="form-label">Username</label><input type="text" name="username" class="form-control" required></div>
                <div class="mb-2"><label class="form-label">Email</label><input type="email" name="email" class="form-control" required></div>
                <div class="mb-2"><label class="form-label">Role</label><select name="role" class="form-select" required><?= $optRole() ?></select></div>
                <div class="mb-2"><label class="form-label">OPD</label><select name="opd_id" class="form-select"><option value="">- Tanpa OPD (kabupaten) -</option><?= $optOpd() ?></select></div>
                <div class="mb-2"><label class="form-label">Password</label><input type="password" name="password" class="form-control" autocomplete="new-password"><small class="text-muted">Kosongkan bila tidak ingin mengubah (saat edit). Minimal 6 karakter.</small></div>
                <div class="form-check"><input type="checkbox" class="form-check-input" name="is_active" id="user-active" value="1" checked><label class="form-check-label" for="user-active">Aktif</label></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-success">Simpan</button></div>
        </form>
    </div></div></div>

    <!-- Role -->
    <div class="modal fade" id="modal-role" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
        <form method="post" action="<?= base_url('adminkab/master/role/save') ?>">
            <?= csrf_field() ?><input type="hidden" name="id">
            <div class="modal-header"><h5 class="modal-title">Data Role</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="mb-2"><label class="form-label">Slug (kode role)</label><input type="text" name="name" class="form-control" pattern="[A-Za-z0-9_-]+" required><small class="text-muted">Hanya huruf, angka, - dan _. Role sistem tidak dapat diganti slug-nya.</small></div>
                <div class="mb-2"><label class="form-label">Label</label><input type="text" name="label" class="form-control"></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-success">Simpan</button></div>
        </form>
    </div></div></div>

    <!-- Satuan -->
    <div class="modal fade" id="modal-satuan" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
        <form method="post" action="<?= base_url('adminkab/master/satuan/save') ?>">
            <?= csrf_field() ?><input type="hidden" name="id">
            <div class="modal-header"><h5 class="modal-title">Data Satuan</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="mb-2"><label class="form-label">Nama Satuan</label><input type="text" name="satuan" class="form-control" required></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-success">Simpan</button></div>
        </form>
    </div></div></div>

    <script>
        (function () {
            function fillForm(modal, json) {
                const form = modal.querySelector('form');
                if (!form) return;
                form.reset();
                const idEl = form.querySelector('[name="id"]');
                if (idEl) idEl.value = '';
                if (!json) return;
                let data;
                try { data = JSON.parse(json); } catch (e) { return; }
                Object.keys(data).forEach(function (k) {
                    const el = form.querySelector('[name="' + k + '"]');
                    if (!el) return;
                    if (el.type === 'checkbox') {
                        el.checked = Number(data[k]) === 1;
                    } else {
                        el.value = (data[k] === null || data[k] === undefined) ? '' : data[k];
                    }
                });
            }

            function showModal(id) {
                const el = document.getElementById(id);
                if (!el || typeof bootstrap === 'undefined') return;
                bootstrap.Modal.getOrCreateInstance(el).show();
            }

            document.addEventListener('click', function (e) {
                const add = e.target.closest('[data-add]');
                if (add) {
                    const modal = document.getElementById(add.dataset.add);
                    if (modal) { fillForm(modal, null); showModal(add.dataset.add); }
                    return;
                }
                const edit = e.target.closest('[data-edit]');
                if (edit) {
                    const modal = document.getElementById(edit.dataset.edit);
                    if (modal) { fillForm(modal, edit.dataset.json); showModal(edit.dataset.edit); }
                }
            });

            // Pertahankan tab aktif di URL saat berpindah tab
            document.querySelectorAll('[data-bs-toggle="tab"]').forEach(function (btn) {
                btn.addEventListener('shown.bs.tab', function (ev) {
                    const target = ev.target.getAttribute('data-bs-target') || '';
                    const tab = target.replace('#pane-', '');
                    if (tab && history.replaceState) {
                        const url = new URL(window.location);
                        url.searchParams.set('tab', tab);
                        history.replaceState(null, '', url);
                    }
                });
            });
        })();
    </script>

    <!-- DataTables (search, sort, pagination per tabel) -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
    <script>
        (function () {
            if (typeof jQuery === 'undefined' || !jQuery.fn || !jQuery.fn.DataTable) return;

            var langID = {
                search: "Cari:",
                lengthMenu: "Tampil _MENU_ data",
                info: "Menampilkan _START_–_END_ dari _TOTAL_ data",
                infoEmpty: "Tidak ada data",
                infoFiltered: "(disaring dari _MAX_ data)",
                zeroRecords: "Data tidak ditemukan",
                emptyTable: "Belum ada data",
                paginate: { first: "Awal", last: "Akhir", next: "›", previous: "‹" }
            };

            // Tabel kecil: DataTables client-side
            jQuery('table.master-table').each(function () {
                if (this.id === 'tbl-pegawai') return; // pegawai = server-side (di bawah)
                if (jQuery.fn.dataTable.isDataTable(this)) return;
                var cols = jQuery(this).find('thead th').length;
                jQuery(this).DataTable({
                    pageLength: 10,
                    lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'Semua']],
                    order: [],
                    language: langID,
                    columnDefs: [{ orderable: false, targets: [0, cols - 1] }]
                });
            });

            // Pegawai: DataTables server-side (search/sort/paging diproses di server)
            if (jQuery('#tbl-pegawai').length && !jQuery.fn.dataTable.isDataTable('#tbl-pegawai')) {
                jQuery('#tbl-pegawai').DataTable({
                    serverSide: true,
                    processing: true,
                    ajax: '<?= base_url('adminkab/master/pegawai-data') ?>',
                    pageLength: 10,
                    lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
                    order: [[1, 'asc']],
                    language: langID,
                    columns: [
                        { data: 'no', orderable: false, className: 'text-center' },
                        { data: 'nama' },
                        { data: 'nip' },
                        { data: 'opd' },
                        { data: 'jabatan' },
                        { data: 'pangkat' },
                        { data: 'level' },
                        { data: 'aksi', orderable: false, className: 'text-center text-nowrap' }
                    ]
                });
            }

            // Perbaiki lebar kolom saat tab ditampilkan (DataTables di tab tersembunyi)
            document.querySelectorAll('[data-bs-toggle="tab"]').forEach(function (btn) {
                btn.addEventListener('shown.bs.tab', function () {
                    jQuery.fn.dataTable.tables({ visible: true, api: true }).columns.adjust();
                });
            });
        })();
    </script>

</body>

</html>
