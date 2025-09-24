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

                <form id="rpjmd-form" method="POST"
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
                                                    echo "<option value=\"{$pegawai['id']}\" data-nip=\"{$pegawai['nip_pegawai']}\"$selected>" . esc($pegawai['nama_pegawai']) . "</option>";
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
                                                        echo "<option value=\"{$pegawai['id']}\" data-nip=\"{$pegawai['nip_pegawai']}\"$selected>" . esc($pegawai['nama_pegawai']) . "</option>";
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
                                                                    class="form-control mb-3 border-secondary"
                                                                    value="<?= esc($indikator['indikator']) ?>" required>
                                                            </div>
                                                            <div class="col-md-3">
                                                                <label class="form-label">Target</label>
                                                                <input type="text"
                                                                    name="sasaran_pk[<?= $si ?>][indikator][<?= $ii ?>][target]"
                                                                    class="form-control mb-3 border-secondary"
                                                                    value="<?= esc($indikator['target']) ?>" required>
                                                            </div>
                                                            <div class="col-md-4">
                                                                <label class="form-label">Satuan</label>
                                                                <select
                                                                    name="sasaran_pk[<?= $si ?>][indikator][<?= $ii ?>][id_satuan]"
                                                                    class="form-select mb-3 border-secondary" required>
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
                                                                <select name="sasaran_pk[<?= $si ?>][indikator][<?= $ii ?>][jenis_indikator]"
                                                                    class="form-select mb-3 border-secondary" required>
                                                                    <option value="">Pilih Jenis Indikator</option>
                                                                    <option value="Indikator Positif" <?= (isset($indikator['jenis_indikator']) && $indikator['jenis_indikator'] == 'Indikator Positif') ? 'selected' : '' ?>>Indikator Positif</option>
                                                                    <option value="Indikator Negatif" <?= (isset($indikator['jenis_indikator']) && $indikator['jenis_indikator'] == 'Indikator Negatif') ? 'selected' : '' ?>>Indikator Negatif</option>
                                                                </select>
                                                            </div>
                                                        </div>
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

                        <!-- Program PK -->
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h3 class="fw-medium">Program PK</h3>
                        </div>

                        <div class="program-container">
                            <?php
                            $programList = isset($pk['program_pk']) && is_array($pk['program_pk']) && count($pk['program_pk']) > 0 ? $pk['program_pk'] : [['program_id' => '', 'anggaran' => '', 'program_kegiatan' => '']];
                            foreach ($programList as $pi => $prog) {
                                ?>
                                <div class="program-item border border-secondary rounded p-3 bg-white mb-3">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <label class="fw-medium">Program <?= $pi + 1 ?></label>
                                        <button type="button" class="remove-program btn btn-outline-danger btn-sm"><i
                                                class="fas fa-trash"></i></button>
                                    </div>
                                    <div class="mb-3">
                                        <div class="row">
                                            <div class="col-md-8">
                                                <label class="form-label">Program</label>
                                                <select name="program[<?= $pi ?>][program_id]"
                                                    class="form-select program-select mb-3" <?= ($jenis !== 'bupati') ? 'required' : '' ?>>
                                                    <option value="">Pilih Program</option>
                                                    <?php
                                                    if (!empty($program)) {
                                                        foreach ($program as $programItem) {
                                                            $selected = '';
                                                            if (isset($prog['program_id']) && $prog['program_id'] == $programItem['id']) {
                                                                $selected = ' selected';
                                                            } elseif (isset($prog['program_kegiatan']) && $prog['program_kegiatan'] == $programItem['program_kegiatan']) {
                                                                $selected = ' selected';
                                                            }
                                                            echo "<option value=\"{$programItem['id']}\" data-anggaran=\"{$programItem['anggaran']}\"$selected>" . esc($programItem['program_kegiatan']) . "</option>";
                                                        }
                                                    } else {
                                                        echo '<option value="" disabled>Tidak ada Program yang tersedia</option>';
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label">Anggaran</label>
                                                <input type="text" name="program[<?= $pi ?>][anggaran]"
                                                    class="form-control mb-3 border-secondary"
                                                    value="<?= isset($prog['anggaran']) ? esc($prog['anggaran']) : '' ?>"
                                                    placeholder="Anggaran" <?= ($jenis !== 'bupati') ? 'required' : '' ?>
                                                    readonly>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php } ?>
                        </div>
                        <div class="d-flex justify-content-end mt-2">
                            <button type="button" class="add-program btn btn-success btn-sm">
                                <i class="fas fa-plus me-1"></i> Tambah Program
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

    <!-- JavaScript Function For Handling RPJMD Form-->
    <script>
        window.satuanDropdownTemplate = `<?php
        if (isset($satuan) && !empty($satuan)) {
            foreach ($satuan as $s) {
                echo '<option value="' . $s['id'] . '">' . esc($s['satuan']) . '</option>';
            }
        } else {
            echo '<option value="" disabled>Tidak ada satuan</option>';
        }
        ?>`;
    </script>
    <script src="<?= base_url('assets/js/adminOpd/pk/pk-form.js') ?>"></script>

</body>

</html>