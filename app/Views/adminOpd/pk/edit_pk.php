<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Edit PK e-SAKIP</title>
    <!-- Style -->
    <?= $this->include('adminOpd/templates/style.php'); ?>
</head>

<body class="bg-light min-vh-100 d-flex flex-column position-relative">
    <div id="main-content" class="content-wrapper d-flex flex-column" style="transition: margin-left 0.3s ease;">

        <!-- Navbar/Header -->
        <?= $this->include(($jenis === 'bupati' ? 'adminKabupaten/templates/header.php' : 'adminOpd/templates/header.php')); ?>

        <!-- Sidebar -->
        <?= $this->include(($jenis === 'bupati' ? 'adminKabupaten/templates/sidebar.php' : 'adminOpd/templates/sidebar.php')); ?>

        <!-- Konten Utama -->
        <main class="flex-fill d-flex justify-content-center p-4 mt-4">
            <div class="bg-white rounded shadow-sm p-4" style="width: 100%; max-width: 1200px;">
                <h2 class="h3 fw-bold text-center mb-4" style="color: #00743e;">Edit PK</h2>

                <form id="pk-form" method="POST"
                    action="<?= base_url('adminopd/pk/' . $pk['jenis'] . '/update/' . $pk['pk_id']) ?>">
                    <?= csrf_field() ?>

                    <!-- Informasi Umum PK -->
                    <section class="mb-4">
                        <h2 class="h5 fw-semibold mb-3">Informasi Umum PK</h2>
                        <div class="col">
                            <?php
                            // Ambil indikator acuan yang sudah dipilih
                            $indikatorAcuanChecked = [];
                            if (!empty($pk['pk_id'])) {
                                $acuanRows = model('App\\Models\\PkModel')->getIndikatorAcuanByPkId($pk['pk_id']);
                                foreach ($acuanRows as $row) {
                                    $indikatorAcuanChecked[] = $row['referensi_pk_id'] . '-' . $row['referensi_indikator_id'];
                                }
                            }
                            ?>
                            <?php if ($jenis !== 'jpt' && strtolower($jenis) !== 'bupati'): ?>
                                <div class="mb-3">
                                    <label class="form-label">Indikator Acuan (Referensi)</label><br>
                                    <?php if (!empty($pkPimpinan)):
                                        $hasIndikator = false;
                                        foreach ($pkPimpinan as $refPk):
                                            if (isset($refPk['id'])) {
                                                $indikatorList = model('App\\Models\\PkModel')->getSasaranByPkId($refPk['id']);
                                                foreach ($indikatorList as $sasaran) {
                                                    foreach ($sasaran['indikator'] as $indikator) {
                                                        $hasIndikator = true;
                                                        $label = esc($indikator['indikator']);
                                                        $val = $refPk['id'] . '-' . $indikator['id'];
                                                        $checked = in_array($val, $indikatorAcuanChecked) ? 'checked' : '';
                                                        echo '<label style="margin-right:10px;"><input type="checkbox" name="referensi_indikator_id[]" value="' . $val . '" ' . $checked . '> ' . $label . '</label>';
                                                    }
                                                }
                                            }
                                        endforeach;
                                        if (!$hasIndikator): ?>
                                            <span>pk pimpinan masih kosong</span>
                                        <?php endif;
                                    else: ?>
                                        <span>pk pimpinan masih kosong</span>
                                    <?php endif; ?>
                                    <div id="selected-indikator-container" class="mt-2"></div>
                                    <script>
                                        // JS untuk menampilkan indikator acuan yang dipilih dari checkbox
                                        document.addEventListener('DOMContentLoaded', function () {
                                            const checkboxes = document.querySelectorAll('input[name="referensi_indikator_id[]"]');
                                            const container = document.getElementById('selected-indikator-container');
                                            checkboxes.forEach(cb => {
                                                cb.addEventListener('change', () => {
                                                    const selected = Array.from(checkboxes)
                                                        .filter(c => c.checked)
                                                        .map(c => c.parentNode.textContent.trim());
                                                    container.innerHTML = selected.length ? '<b>Indikator Acuan Terpilih:</b><br>' + selected.join('<br>') : '';
                                                });
                                            });
                                        });
                                    </script>
                                </div>
                            <?php endif; ?>
                            <label class="form-label fw-bold">Jenis PK</label>
                            <select name="jenis" id="jenis-pk" class="form-select mb-3 border-secondary" disabled>
                                <option value="<?= esc($jenis) ?>" selected><?= 'PK ' . ucfirst($jenis) ?></option>
                            </select>
                            <input type="hidden" name="jenis" value="<?= esc($jenis) ?>">
                            <div class="col-md-12">
                                <div class="row">
                                    <div class="col-md-8">
                                        <label class="form-label fw-bold">Pihak Kesatu</label>
                                        <select name="pegawai_1_id" id="p1_select"
                                            class="form-select mb-3 border-secondary pegawai-select"
                                            data-target="nip-p1" required>
                                            <option value="">Pilih Pihak Kesatu</option>
                                            <?php
                                            if (!empty($pegawaiOpd)) {
                                                foreach ($pegawaiOpd as $pegawai) {
                                                    $selected = (isset($pk['pihak_1']) && $pk['pihak_1'] == $pegawai['id']) ? ' selected' : '';
                                                    echo "<option value=\"{$pegawai['id']}\" data-nip=\"{$pegawai['nip_pegawai']}\"$selected>" . esc(strtoupper($pegawai['nama_pegawai'])) . "</option>";
                                                }
                                            } else {
                                                echo '<option value="" disabled>Tidak Pegawai yang tersedia</option>';
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">NIP Pegawai</label>
                                        <input type="text" name="nip-p1" class="form-control mb-3 border-secondary"
                                            value="<?= isset($pk['nip_pihak_1']) ? esc($pk['nip_pihak_1']) : '' ?>"
                                            placeholder="NIP" required readonly>
                                    </div>
                                </div>
                            </div>

                            <?php if (strtolower($jenis) !== 'bupati'): ?>
                                <div class="col-md-12">
                                    <div class="row">
                                        <div class="col-md-8">
                                            <label class="form-label fw-bold">Pihak Kedua</label>
                                            <select name="pegawai_2_id" id="p2_select"
                                                class="form-select mb-3 border-secondary pegawai-select"
                                                data-target="nip-p2" required>
                                                <option value="">Pilih Pihak Kedua</option>
                                                <?php
                                                if (!empty($pegawaiOpd)) {
                                                    foreach ($pegawaiOpd as $pegawai) {
                                                        $selected = (isset($pk['pihak_2']) && $pk['pihak_2'] == $pegawai['id']) ? ' selected' : '';
                                                        echo "<option value=\"{$pegawai['id']}\" data-nip=\"{$pegawai['nip_pegawai']}\"$selected>" . esc(strtoupper($pegawai['nama_pegawai'])) . "</option>";
                                                    }
                                                } else {
                                                    echo '<option value="" disabled>Tidak Pegawai yang tersedia</option>';
                                                }
                                                ?>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">NIP Pegawai</label>
                                            <input type="text" name="nip-p2" class="form-control mb-3 border-secondary"
                                                value="<?= isset($pk['nip_pihak_2']) ? esc($pk['nip_pihak_2']) : '' ?>"
                                                placeholder="NIP" required readonly>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-bold">Tahun PK</label>
                                <input type="number" name="tahun" class="form-control border-secondary"
                                    value="<?= isset($pk['tahun']) ? esc($pk['tahun']) : '' ?>"
                                    placeholder="Contoh: 2025" min="2020" max="2050" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-bold">Tanggal Penandatanganan PK</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-secondary">
                                        <i class="bi bi-calendar-event"></i>
                                    </span>
                                    <input type="date" name="tanggal_pk" class="form-control border-secondary"
                                        value="<?= esc($pk['tanggal']) ?>" required>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section>
                        <!-- Sasaran -->
                        <div class="sasaran-section">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h3 class="fw-medium h4">Sasaran Terkait PK Ini</h3>
                            </div>
                            <div class="sasaran-container">
                                <?php
                                $sasaranList = isset($pk['sasaran_pk']) && is_array($pk['sasaran_pk']) && count($pk['sasaran_pk']) > 0 ? $pk['sasaran_pk'] : [['sasaran' => '', 'indikator' => [['indikator' => '', 'target' => '']]]];
                                foreach ($sasaranList as $si => $sasaran) {
                                    ?>
                                    <div class="sasaran-item border border-secondary rounded p-3 bg-white mb-3"
                                        data-sasaran-index="<?= $si ?>">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <label class="fw-medium h5">Sasaran</label>
                                            <button type="button" class="remove-sasaran btn btn-outline-danger btn-sm"><i
                                                    class="fas fa-trash"></i></button>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Sasaran PK</label>
                                            <textarea name="sasaran_pk[<?= $si ?>][sasaran]"
                                                class="form-control border-secondary" rows="2"
                                                required><?= esc($sasaran['sasaran']) ?></textarea>
                                        </div>
                                        <div class="indikator-section">
                                            <div class="indikator-container">
                                                <?php
                                                $indikatorList = isset($sasaran['indikator']) && is_array($sasaran['indikator']) && count($sasaran['indikator']) > 0 ? $sasaran['indikator'] : [['indikator' => '', 'target' => '']];
                                                foreach ($indikatorList as $ii => $indikator) {
                                                    ?>
                                                    <div class="indikator-item border rounded p-3 bg-light mb-3">
                                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                                            <label class="fw-medium">Indikator</label>
                                                            <button type="button"
                                                                class="remove-indikator btn btn-outline-danger btn-sm"><i
                                                                    class="fas fa-trash"></i></button>
                                                        </div>
                                                        <div class="row">
                                                            <div class="col-md-5">
                                                                <label class="form-label">Indikator</label>
                                                                <input type="text"
                                                                    name="sasaran_pk[<?= $si ?>][indikator][<?= $ii ?>][indikator]"
                                                                    class="form-control mb-3 border-secondary indikator-input"
                                                                    value="<?= esc($indikator['indikator']) ?>" required>
                                                            </div>
                                                            <div class="col-md-3">
                                                                <label class="form-label">Target</label>
                                                                <input type="text"
                                                                    name="sasaran_pk[<?= $si ?>][indikator][<?= $ii ?>][target]"
                                                                    class="form-control mb-3 border-secondary indikator-target"
                                                                    value="<?= esc($indikator['target']) ?>" required>
                                                            </div>
                                                            <div class="col-md-4">
                                                                <label class="form-label">Satuan</label>
                                                                <select
                                                                    name="sasaran_pk[<?= $si ?>][indikator][<?= $ii ?>][id_satuan]"
                                                                    class="form-select mb-3 border-secondary satuan-select" required>
                                                                    <option value="">Pilih Satuan</option>
                                                                    <?php if (isset($satuan) && !empty($satuan)): ?>
                                                                        <?php foreach ($satuan as $s): ?>
                                                                            <option value="<?= $s['id'] ?>"
                                                                                <?= (isset($indikator['id_satuan']) && $indikator['id_satuan'] == $s['id']) ? 'selected' : '' ?>>
                                                                                <?= esc($s['satuan']) ?>
                                                                            </option>
                                                                        <?php endforeach; ?>
                                                                    <?php else: ?>
                                                                        <option value="" disabled>Tidak ada satuan</option>
                                                                    <?php endif; ?>
                                                                </select>
                                                            </div>
                                                            <div class="col-md-4">
                                                                <label class="form-label">Jenis Indikator</label>
                                                                <select
                                                                    name="sasaran_pk[<?= $si ?>][indikator][<?= $ii ?>][jenis_indikator]"
                                                                    class="form-select mb-3 border-secondary jenis-indikator-select" required>
                                                                    <option value="">Pilih Jenis Indikator</option>
                                                                    <option value="Indikator Positif"
                                                                        <?= (isset($indikator['jenis_indikator']) && $indikator['jenis_indikator'] == 'Indikator Positif') ? 'selected' : '' ?>>Indikator Positif</option>
                                                                    <option value="Indikator Negatif"
                                                                        <?= (isset($indikator['jenis_indikator']) && $indikator['jenis_indikator'] == 'Indikator Negatif') ? 'selected' : '' ?>>Indikator Negatif</option>
                                                                </select>
                                                            </div>
                                                        </div>

                                                        <!-- Program Container Dinamis per Indikator -->
                                                        <?php if ($jenis === 'jpt'): ?>
                                                            <div class="program-container">
                                                                <?php
                                                                $programList = !empty($indikator['program'])
                                                                    ? $indikator['program']
                                                                    : [['program_id' => '', 'anggaran' => '']];
                                                                ?>

                                                                <?php foreach ($programList as $pi => $prog): ?>

                                                                <div class="row program-item border rounded p-3 bg-light mb-3">
                                                                    <!-- PROGRAM -->
                                                                    <div class="col-md-6">
                                                                        <label class="form-label">Program</label>
                                                                        <select
                                                                            name="sasaran_pk[<?= $si ?>][indikator][<?= $ii ?>][program][<?= $pi ?>][program_id]"
                                                                            class="form-select select2 program-select border-secondary"
                                                                            required>

                                                                            <option value="">Pilih Program</option>

                                                                            <?php foreach ($program as $programItem): ?>
                                                                                <option value="<?= $programItem['id'] ?>"
                                                                                    data-anggaran="<?= $programItem['anggaran'] ?>"
                                                                                    <?= (!empty($prog['program_id']) && $prog['program_id'] == $programItem['id']) ? 'selected' : '' ?>>
                                                                                    <?= esc($programItem['program_kegiatan']) ?> — Rp
                                                                                    <?= number_format($programItem['anggaran'], 0, ',', '.') ?>
                                                                                </option>
                                                                            <?php endforeach; ?>

                                                                        </select>
                                                                    </div>

                                                                    <!-- ANGGARAN (LEVEL PROGRAM) -->
                                                                    <div class="col-md-3">
                                                                        <label class="form-label">Anggaran</label>
                                                                        <input type="text"
                                                                            name="sasaran_pk[<?= $si ?>][indikator][<?= $ii ?>][program][<?= $pi ?>][anggaran]"
                                                                            class="form-control anggaran-input border-secondary"
                                                                            value="<?= esc($prog['anggaran'] ?? '') ?>"
                                                                            placeholder="Anggaran"
                                                                            readonly>
                                                                    </div>

                                                                    <!-- ACTION -->
                                                                    <div class="col-md-3 d-flex align-items-end">
                                                                        <button type="button"
                                                                            class="remove-program btn btn-outline-danger btn-sm">
                                                                            <i class="fas fa-trash"></i>
                                                                        </button>
                                                                    </div>
                                                                </div>

                                                                <?php endforeach; ?>

                                                                </div>

                                                                <div class="d-flex justify-content-end mt-2">
                                                                    <button type="button"
                                                                        class="add-program btn btn-success btn-sm">
                                                                        <i class="fas fa-plus me-1"></i> Tambah Program
                                                                    </button>
                                                                </div>

                                                        <?php elseif ($jenis === 'administrator'): ?>
                                                            <div class="program-container">

                                                                <?php
                                                                $programList = !empty($indikator['program'])
                                                                    ? $indikator['program']
                                                                    : [['program_id' => '', 'kegiatan' => []]];
                                                                ?>

                                                                <?php foreach ($programList as $pi => $prog): ?>

                                                                    <div class="program-item border rounded p-3 bg-white mb-4">

                                                                        <!-- PROGRAM -->
                                                                        <div class="row mb-3">
                                                                            <div class="col-md-6">
                                                                                <label class="form-label">Program</label>
                                                                                <select
                                                                                    name="sasaran_pk[<?= $si ?>][indikator][<?= $ii ?>][program][<?= $pi ?>][program_id]"
                                                                                    class="form-select select2 program-select border-secondary"
                                                                                    required>

                                                                                    <option value="">Pilih Program</option>

                                                                                    <?php foreach ($jptProgram as $programItem): ?>
                                                                                        <option value="<?= $programItem['id'] ?>"
                                                                                            <?= (!empty($prog['program_id']) && $prog['program_id'] == $programItem['id']) ? 'selected' : '' ?>>
                                                                                            <?= esc($programItem['program_kegiatan']) ?> - Rp
                                                                                            <?= number_format($programItem['anggaran'], 0, ',', '.') ?>
                                                                                        </option>
                                                                                    <?php endforeach; ?>

                                                                                </select>
                                                                            </div>

                                                                            <div class="col-md-3 d-flex align-items-end">
                                                                                <button type="button"
                                                                                    class="remove-program btn btn-outline-danger btn-sm">
                                                                                    <i class="fas fa-trash"></i>
                                                                                </button>
                                                                            </div>
                                                                        </div>

                                                                        <!-- KEGIATAN -->
                                                                        <div class="kegiatan-container">

                                                                            <?php
                                                                            $kegiatanList = !empty($prog['kegiatan'])
                                                                                ? $prog['kegiatan']
                                                                                : [['kegiatan_id' => '', 'anggaran' => '']];
                                                                            ?>

                                                                            <?php foreach ($kegiatanList as $ke => $keg): ?>

                                                                                <div class="kegiatan-item border rounded bg-light p-3 mb-3">
                                                                                    <div class="row">
                                                                                        <!-- DROPDOWN KEGIATAN -->
                                                                                        <div class="col-md-5">
                                                                                            <label class="form-label">Kegiatan</label>
                                                                                            <select
                                                                                                name="sasaran_pk[<?= $si ?>][indikator][<?= $ii ?>][program][<?= $pi ?>][kegiatan][<?= $ke ?>][kegiatan_id]"
                                                                                                class="form-select select2 kegiatan-select border-secondary"
                                                                                                required>

                                                                                                <option value="">Pilih Kegiatan</option>

                                                                                                <?php foreach ($kegiatan as $kegiatanItem): ?>
                                                                                                    <option value="<?= $kegiatanItem['id'] ?>"
                                                                                                        data-anggaran="<?= $kegiatanItem['anggaran'] ?>"
                                                                                                        <?= (!empty($keg['kegiatan_id']) && $keg['kegiatan_id'] == $kegiatanItem['id']) ? 'selected' : '' ?>>
                                                                                                        <?= esc($kegiatanItem['kegiatan']) ?> —
                                                                                                        Rp
                                                                                                        <?= number_format($kegiatanItem['anggaran'], 0, ',', '.') ?>
                                                                                                    </option>
                                                                                                <?php endforeach; ?>

                                                                                            </select>
                                                                                        </div>

                                                                                        <!-- ANGGARAN (LEVEL KEGIATAN) -->
                                                                                        <div class="col-md-3">
                                                                                            <label class="form-label">Anggaran</label>
                                                                                            <input type="text"
                                                                                                name="sasaran_pk[<?= $si ?>][indikator][<?= $ii ?>][program][<?= $pi ?>][kegiatan][<?= $ke ?>][anggaran]"
                                                                                                class="form-control anggaran-input border-secondary"
                                                                                                value="<?= esc($keg['anggaran'] ?? '') ?>"
                                                                                                placeholder="Anggaran" readonly>
                                                                                        </div>

                                                                                        <!-- ACTION -->
                                                                                        <div class="col-md-3 d-flex align-items-end">
                                                                                            <button type="button"
                                                                                                class="remove-kegiatan btn btn-outline-danger btn-sm">
                                                                                                <i class="fas fa-trash"></i>
                                                                                            </button>
                                                                                        </div>
                                                                                    </div>
                                                                                </div>

                                                                            <?php endforeach; ?>

                                                                        </div>

                                                                        <div class="d-flex justify-content-end">
                                                                            <button type="button"
                                                                                class="add-kegiatan btn btn-success btn-sm">
                                                                                <i class="fas fa-plus me-1"></i> Tambah Kegiatan
                                                                            </button>
                                                                        </div>

                                                                    </div>

                                                                <?php endforeach; ?>

                                                            </div>

                                                            <div class="d-flex justify-content-end mt-2">
                                                                <button type="button" class="add-program btn btn-primary btn-sm">
                                                                    <i class="fas fa-plus me-1"></i> Tambah Program
                                                                </button>
                                                            </div>

                                                        <?php elseif ($jenis === 'pengawas'): ?>
                                                            <!-- PENGAWAS: Kegiatan (dari admin) -> Sub Kegiatan -->
                                                            <div class="kegiatan-container">

                                                            <?php
                                                            // Struktur default
                                                            $programList = !empty($indikator['program'])
                                                                ? $indikator['program']
                                                                : [['program_id' => '', 'kegiatan' => []]];
                                                            ?>

                                                            <?php foreach ($programList as $pi => $prog): ?>

                                                            <div class="kegiatan-item border rounded p-3 bg-white mb-4">

                                                                <!-- KEGIATAN -->
                                                                <?php
                                                                $kegiatanList = !empty($prog['kegiatan'])
                                                                    ? $prog['kegiatan']
                                                                    : [['kegiatan_id' => '', 'subkegiatan' => []]];
                                                                ?>

                                                                <?php foreach ($kegiatanList as $ke => $keg): ?>

                                                                <div class="row mb-3">
                                                                    <div class="col-md-6">
                                                                        <label class="form-label">Kegiatan</label>

                                                                        <!-- PROGRAM_ID diisi via JS dari data-program -->
                                                                        <input type="hidden"
                                                                            name="sasaran_pk[<?= $si ?>][indikator][<?= $ii ?>][program][<?= $pi ?>][program_id]"
                                                                            class="program-id-hidden"
                                                                            value="<?= esc($prog['program_id'] ?? '') ?>">

                                                                        <select
                                                                            name="sasaran_pk[<?= $si ?>][indikator][<?= $ii ?>][program][<?= $pi ?>][kegiatan][<?= $ke ?>][kegiatan_id]"
                                                                            class="form-select select2 kegiatan-select border-secondary kegiatan-dropdown"
                                                                            required>

                                                                            <option value="">Pilih Kegiatan</option>

                                                                            <?php foreach ($kegiatanAdmin as $kegiatanItem): ?>
                                                                                <option value="<?= $kegiatanItem['id'] ?>"
                                                                                    data-program="<?= $kegiatanItem['program_id'] ?>"
                                                                                    <?= (!empty($keg['kegiatan_id']) && $keg['kegiatan_id'] == $kegiatanItem['id']) ? 'selected' : '' ?>>
                                                                                    <?= esc($kegiatanItem['kegiatan']) ?> — Rp
                                                                                    <?= number_format($kegiatanItem['anggaran'], 0, ',', '.') ?>
                                                                                </option>
                                                                            <?php endforeach; ?>

                                                                        </select>
                                                                    </div>

                                                                    <div class="col-md-3 d-flex align-items-end">
                                                                        <button type="button"
                                                                            class="remove-kegiatan btn btn-outline-danger btn-sm">
                                                                            <i class="fas fa-trash"></i>
                                                                        </button>
                                                                    </div>
                                                                </div>

                                                                <!-- SUB KEGIATAN -->
                                                                <div class="subkeg-container">

                                                                    <?php
                                                                    $subList = !empty($keg['subkegiatan'])
                                                                        ? $keg['subkegiatan']
                                                                        : [['subkegiatan_id' => '', 'anggaran' => '']];
                                                                    ?>

                                                                    <?php foreach ($subList as $sk => $sub): ?>

                                                                    <div class="subkeg-item border rounded bg-light p-3 mb-3">
                                                                        <div class="row">
                                                                            <!-- DROPDOWN SUB KEGIATAN -->
                                                                            <div class="col-md-5">
                                                                                <label class="form-label">Sub Kegiatan</label>
                                                                                <select
                                                                                    name="sasaran_pk[<?= $si ?>][indikator][<?= $ii ?>][program][<?= $pi ?>][kegiatan][<?= $ke ?>][subkegiatan][<?= $sk ?>][subkegiatan_id]"
                                                                                    class="form-select select2 subkeg-select border-secondary"
                                                                                    required>

                                                                                    <option value="">Pilih Sub Kegiatan</option>

                                                                                    <?php foreach ($subkegiatan as $skItem): ?>
                                                                                        <option value="<?= $skItem['id'] ?>"
                                                                                            data-anggaran="<?= $skItem['anggaran'] ?>"
                                                                                            <?= (!empty($sub['subkegiatan_id']) && $sub['subkegiatan_id'] == $skItem['id']) ? 'selected' : '' ?>>
                                                                                            <?= esc($skItem['sub_kegiatan']) ?> — Rp
                                                                                            <?= number_format($skItem['anggaran'], 0, ',', '.') ?>
                                                                                        </option>
                                                                                    <?php endforeach; ?>

                                                                                </select>
                                                                            </div>

                                                                            <!-- ANGGARAN (LEVEL SUB KEGIATAN) -->
                                                                            <div class="col-md-3">
                                                                                <label class="form-label">Anggaran</label>
                                                                                <input type="text"
                                                                                    name="sasaran_pk[<?= $si ?>][indikator][<?= $ii ?>][program][<?= $pi ?>][kegiatan][<?= $ke ?>][subkegiatan][<?= $sk ?>][anggaran]"
                                                                                    class="form-control anggaran-input border-secondary"
                                                                                    value="<?= esc($sub['anggaran'] ?? '') ?>"
                                                                                    placeholder="Anggaran"
                                                                                    readonly>
                                                                            </div>

                                                                            <!-- ACTION -->
                                                                            <div class="col-md-3 d-flex align-items-end">
                                                                                <button type="button"
                                                                                    class="remove-subkeg btn btn-outline-danger btn-sm">
                                                                                    <i class="fas fa-trash"></i>
                                                                                </button>
                                                                            </div>
                                                                        </div>
                                                                    </div>

                                                                    <?php endforeach; ?>

                                                                </div>

                                                                <div class="d-flex justify-content-end">
                                                                    <button type="button"
                                                                        class="add-subkeg btn btn-success btn-sm">
                                                                        <i class="fas fa-plus me-1"></i> Tambah Sub Kegiatan
                                                                    </button>
                                                                </div>

                                                                <?php endforeach; ?>

                                                            </div>
                                                            <?php endforeach; ?>
                                                            </div>

                                                            <div class="d-flex justify-content-end mt-2">
                                                                <button type="button"
                                                                    class="add-kegiatan btn btn-primary btn-sm">
                                                                    <i class="fas fa-plus me-1"></i> Tambah Kegiatan
                                                                </button>
                                                            </div>

                                                        <?php endif; ?>

                                                    </div>
                                                <?php } ?>
                                            </div>
                                            <div class="d-flex justify-content-end mt-2">
                                                <button type="button" class="add-indikator btn btn-info btn-sm">
                                                    <i class="fas fa-plus me-1"></i> Tambah Indikator
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <?php
                                } // end foreach sasaranList
                                ?>

                            </div>
                        </div>
                        <div class="d-flex justify-content-end mt-2">
                            <button type="button" class="add-sasaran btn btn-success btn-sm">
                                <i class="fas fa-plus me-1"></i> Tambah Sasaran
                            </button>
                        </div>
                    </section>

                    <!-- Tombol Aksi -->
                    <div class="d-flex justify-content-between mt-4">
                        <a href="<?= base_url('adminopd/pk/' . $jenis) ?>" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-1"></i> Kembali
                        </a>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save me-1"></i> Simpan
                        </button>
                    </div>
                </form>
            </div>
        </main>

        <?= $this->include('adminOpd/templates/footer.php'); ?>
    </div>

    <script>
        // satuan dropdown
        window.satuanDropdownTemplate = `<?php
        if (isset($satuan) && !empty($satuan)) {
            foreach ($satuan as $s) {
                echo '<option value="' . $s['id'] . '">' . esc($s['satuan']) . '</option>';
            }
        } else {
            echo '<option value="" disabled>Tidak ada satuan</option>';
        }
        ?>`;

        // program dropdown
        window.programDropdownTemplate = `<?php
        if (isset($program) && !empty($program)) {
            foreach ($program as $programItem) {
                echo '<option value="' . $programItem['id'] . '" data-anggaran="' . $programItem['anggaran'] . '">' . esc($programItem['program_kegiatan']) . '</option>';
            }
        } else {
            echo '<option value="" disabled>Tidak ada program</option>';
        }
        ?>`;

        //program jpt dropdown
        window.jptProgramDropdownTemplate = `<?php
        if (isset($jptProgram) && !empty($jptProgram)) {
            foreach ($jptProgram as $programItem) {
                echo '<option value="' . $programItem['id'] . '">' . esc($programItem['program_kegiatan']) . '</option>';
            }
        } else {
            echo '<option value="" disabled>Tidak ada program</option>';
        }
        ?>`;

        // kegiatan dropdown
        window.kegiatanDropdownTemplate = `<?php
        if (isset($kegiatan) && !empty($kegiatan)) {
            foreach ($kegiatan as $kegiatanItem) {
                echo '<option value="' . $kegiatanItem['id'] . '">' . esc($kegiatanItem['kegiatan']) . '</option>';
            }
        } else {
            echo '<option value="" disabled>Tidak ada kegiatan</option>';
        }
        ?>`;

        // kegiatan admin dropdown
        window.kegiatanAdminDropdownTemplate = `<?php
        if (isset($kegiatanAdmin) && !empty($kegiatanAdmin)) {
            foreach ($kegiatanAdmin as $kegiatanItem) {
                echo '<option value="' . $kegiatanItem['id'] . '">' . esc($kegiatanItem['kegiatan']) . '</option>';
            }
        } else {
            echo '<option value="" disabled>Tidak ada kegiatan</option>';
        }
        ?>`;

        // subkegiatan dropdown
        window.subkegiatanDropdownTemplate = `<?php
        if (isset($subkegiatan) && !empty($subkegiatan)) {
            foreach ($subkegiatan as $sk) {
                echo '<option value="' . $sk['id'] . '">' . esc($sk['sub_kegiatan']) . '</option>';
            }
        } else {
            echo '<option value="" disabled>Tidak ada sub kegiatan</option>';
        }
        ?>`;
    </script>

    <?php if ($jenis === 'jpt'): ?>
        <script src="<?= base_url('assets/js/adminOpd/pk/pk-form.js') ?>"></script>
    <?php elseif ($jenis === 'administrator'): ?>
        <script src="<?= base_url('assets/js/adminOpd/pk/pk-admin-form.js') ?>"></script>
    <?php elseif ($jenis === 'pengawas'): ?>
        <script src="<?= base_url('assets/js/adminOpd/pk/pk-pengawas-form.js') ?>"></script>
    <?php elseif ($jenis === 'bupati'): ?>
        <script src="<?= base_url('assets/js/adminKabupaten/pk/pk-bupati-form.js') ?>"></script>
    <?php endif; ?>
</body>

</html>