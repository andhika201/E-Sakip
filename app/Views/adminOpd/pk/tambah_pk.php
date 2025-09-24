<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Tambah PK <?= ucfirst($jenis) ?> - e-SAKIP</title>
    <?= $this->include('adminOpd/templates/style.php'); ?>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .misi-container,
        .indikator-acuan-container {
            margin-bottom: 20px;
        }

        .btn-check:checked+.btn-outline-primary {
            background-color: #0d6efd;
            color: white;
        }

        .btn-check:checked+.btn-outline-info {
            background-color: #0dcaf0;
            color: black;
        }

        #selected-misi-container,
        #selected-indikator-container {
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 5px;
            margin-top: 15px;
        }

        .misi-label {
            margin-bottom: 10px;
            font-weight: bold;
        }

        .content-wrapper {
            transition: margin-left 0.3s ease;
        }

        .bg-custom {
            background-color: #00743e;
        }
    </style>
</head>

<body class="bg-light min-vh-100 d-flex flex-column position-relative">
    <div id="main-content" class="content-wrapper d-flex flex-column">
        <?= $this->include(($jenis === 'bupati' ? 'adminKabupaten/templates/header.php' : 'adminOpd/templates/header.php')); ?>

        <?= $this->include(($jenis === 'bupati' ? 'adminKabupaten/templates/sidebar.php' : 'adminOpd/templates/sidebar.php')); ?>

        <main class="flex-fill d-flex justify-content-center p-4 mt-4">
            <div class="bg-white rounded shadow-sm p-4" style="width: 100%; max-width: 1200px;">
                <h2 class="h3 fw-bold text-center mb-4" style="color: #00743e;">Tambah PK <?= strtoupper($jenis) ?></h2>
                <form id="pk-form" method="POST"
                    action="<?= base_url(($jenis === 'bupati' ? 'adminkab/pk/' : 'adminopd/pk/') . $jenis . '/save') ?>">
                    <?= csrf_field() ?>
                    <section class="mb-4">
                        <h2 class="h5 fw-semibold mb-3">Informasi Umum PK</h2>
                        <div class="col">
                            <label class="form-label fw-bold">Jenis PK</label>
                            <select name="jenis" id="jenis-pk" class="form-select mb-3 border-secondary" disabled>
                                <option value="<?= esc($jenis) ?>" selected>PK <?= ucfirst($jenis) ?></option>
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
                                            <?php if (isset($pegawaiOpd) && !empty($pegawaiOpd)): ?>
                                                <?php foreach ($pegawaiOpd as $pegawai): ?>
                                                    <option value="<?= $pegawai['id'] ?>"
                                                        data-nip="<?= $pegawai['nip_pegawai'] ?>">
                                                        <?= esc($pegawai['nama_pegawai']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <option value="" disabled>Tidak Pegawai yang tersedia</option>
                                            <?php endif; ?>
                                        </select>
                                    </div>
                                    <?php if ($jenis !== 'bupati'): ?>
                                        <div class="col-md-4">
                                            <label class="form-label">NIP Pegawai</label>
                                            <input type="text" name="nip-p1" class="form-control mb-3 border-secondary"
                                                value="" placeholder="NIP" required readonly>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <?php if ($jenis !== 'bupati'): ?>
                                    <div class="row">
                                        <div class="col-md-8">
                                            <label class="form-label fw-bold">Pihak Kedua</label>
                                            <select name="pegawai_2_id" id="p2_select"
                                                class="form-select mb-3 border-secondary pegawai-select"
                                                data-target="nip-p2" required>
                                                <option value="">Pilih Pihak Kedua</option>
                                                <?php if (isset($pegawaiOpd) && !empty($pegawaiOpd)): ?>
                                                    <?php foreach ($pegawaiOpd as $pegawai): ?>
                                                        <option value="<?= $pegawai['id'] ?>"
                                                            data-nip="<?= $pegawai['nip_pegawai'] ?>">
                                                            <?= esc($pegawai['nama_pegawai']) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <option value="" disabled>Tidak Pegawai yang tersedia</option>
                                                <?php endif; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">NIP Pegawai</label>
                                            <input type="text" name="nip-p2" class="form-control mb-3 border-secondary"
                                                value="" placeholder="NIP" required readonly>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                <?php if ($jenis === 'jpt'): ?>
                                    <div class="mb-3">
                                        <div class="misi-container">
                                            <label class="form-label misi-label">Misi Bupati</label>
                                            <div class="btn-group-vertical w-100" role="group" aria-label="Misi Bupati">
                                                <?php $misiList = model('App\\Models\\RpjmdModel')->getAllMisi(); ?>
                                                <?php if (!empty($misiList)):
                                                    foreach ($misiList as $misi): ?>
                                                        <input type="checkbox" class="btn-check" id="misi<?= $misi['id'] ?>"
                                                            autocomplete="off" name="misi_bupati_id[]" value="<?= $misi['id'] ?>"
                                                            data-misi="<?= esc($misi['misi']) ?>">
                                                        <label class="btn btn-outline-primary text-start mb-2"
                                                            for="misi<?= $misi['id'] ?>"><?= esc($misi['misi']) ?></label>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <span class="text-muted">Data misi bupati belum tersedia</span>
                                                <?php endif; ?>
                                            </div>
                                            <div id="selected-misi-container" class="mt-2"></div>
                                        </div>
                                    </div>
                                <?php elseif ($jenis !== 'bupati' && $jenis !== 'jpt'): ?>
                                    <div class="mb-3">
                                        <div class="indikator-acuan-container">
                                            <label class="form-label misi-label">Indikator Acuan (Referensi)</label>
                                            <div class="btn-group-vertical w-100" role="group" aria-label="Indikator Acuan">
                                                <?php
                                                if (!empty($pkPimpinan)):
                                                    $hasIndikator = false;
                                                    foreach ($pkPimpinan as $refPk):
                                                        if (isset($refPk['id'])) {
                                                            $indikatorList = model('App\\Models\\PkModel')->getSasaranByPkId($refPk['id']);
                                                            foreach ($indikatorList as $sasaran) {
                                                                foreach ($sasaran['indikator'] as $indikator) {
                                                                    $hasIndikator = true;
                                                                    $label = esc($indikator['indikator']);
                                                                    $inputId = 'indikatorAcuan' . $refPk['id'] . '_' . $indikator['id'];
                                                                    echo '<input type="checkbox" class="btn-check" id="' . $inputId . '" autocomplete="off" name="referensi_indikator_id[]" value="' . $refPk['id'] . '-' . $indikator['id'] . '" data-indikator="' . $label . '">';
                                                                    echo '<label class="btn btn-outline-info text-start mb-2" for="' . $inputId . '">' . $label . '</label>';
                                                                }
                                                            }
                                                        }
                                                    endforeach;
                                                    if (!$hasIndikator): ?>
                                                        <span>pk pimpinan masih kosong</span>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span>pk pimpinan masih kosong</span>
                                                <?php endif; ?>
                                            </div>
                                            <div id="selected-indikator-container" class="mt-2"></div>
                                        </div>
                                    </div>
                                <?php endif; ?>
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
                                <!-- Sasaran 1.1 -->
                                <div class="sasaran-item border border-secondary rounded p-3 bg-white mb-3">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <label class="fw-medium h5">Sasaran</label>
                                        <button type="button" class="remove-sasaran btn btn-outline-danger btn-sm"><i
                                                class="fas fa-trash"></i></button>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Sasaran PK</label>
                                        <textarea name="sasaran_pk[0][sasaran]" class="form-control border-secondary"
                                            rows="2"
                                            placeholder="Contoh: Terwujudnya pelayanan publik yang prima dan memuaskan masyarakat"
                                            required></textarea>
                                    </div>
                                    <!-- Indikator -->
                                    <div class="indikator-section">
                                        <div class="indikator-container">
                                            <!-- Indikator-->
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
                                                        <input type="text" name="sasaran_pk[0][indikator][0][indikator]"
                                                            class="form-control mb-3 border-secondary" value=""
                                                            placeholder="Contoh: Persentase tingkat kepuasan masyarakat terhadap pelayanan"
                                                            required>
                                                        <input type="hidden" name="id_pk_indikator" value="">
                                                    </div>
                                                    <div class="col-md-3">
                                                        <label class="form-label">Target</label>
                                                        <input type="text" name="sasaran_pk[0][indikator][0][target]"
                                                            class="form-control mb-3 border-secondary" value=""
                                                            placeholder="Nilai target" required>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="form-label">Satuan</label>
                                                        <select name="sasaran_pk[0][indikator][0][id_satuan]"
                                                            class="form-select mb-3 border-secondary" required>
                                                            <option value="">Pilih Satuan</option>
                                                            <?php if (isset($satuan) && !empty($satuan)): ?>
                                                                <?php foreach ($satuan as $s): ?>
                                                                    <option value="<?= $s['id'] ?>"><?= esc($s['satuan']) ?>
                                                                    </option>
                                                                <?php endforeach; ?>
                                                            <?php endif; ?>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="form-label">Jenis Indikator</label>
                                                        <select name="sasaran_pk[0][indikator][0][jenis_indikator]"
                                                            class="form-select mb-3 border-secondary" required>
                                                            <option value="">Pilih Jenis Indikator</option>
                                                            <option value="Indikator Positif">Indikator Positif</option>
                                                            <option value="Indikator Negatif">Indikator Negatif</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <!-- Program Container Dinamis per Indikator -->
                                                <div class="program-container">
                                                    <div class="row program-item">
                                                        <div class="col-md-6">
                                                            <label class="form-label">Program</label>
                                                            <select
                                                                name="sasaran_pk[0][indikator][0][program][0][program_id]"
                                                                class="form-select program-select mb-3 border-secondary"
                                                                required>
                                                                <option value="">Pilih Program</option>
                                                                <?php if (isset($program) && !empty($program)): ?>
                                                                    <?php foreach ($program as $programItem): ?>
                                                                        <option value="<?= $programItem['id'] ?>"
                                                                            data-anggaran="<?= $programItem['anggaran'] ?>">
                                                                            <?= esc($programItem['program_kegiatan']) ?>
                                                                        </option>
                                                                    <?php endforeach; ?>
                                                                <?php endif; ?>
                                                            </select>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <label class="form-label">Anggaran</label>
                                                            <input type="text" name="sasaran_pk[0][indikator][0][program][0][anggaran]"
                                                                class="form-control mb-3 border-secondary"
                                                                value="" placeholder="Anggaran" <?= ($jenis !== 'bupati') ? 'required' : '' ?> readonly />
                                                            <input type="hidden" name="program[0][id_indikator]" value="">
                                                        </div>
                                                        <div class="col-md-3">
                                                            <button type="button" class="remove-program btn btn-outline-danger btn-sm">
                                                            <i class="fas fa-trash"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="d-flex justify-content-end mt-2">
                                                    <button type="button" class="add-program btn btn-success btn-sm">
                                                        <i class="fas fa-plus me-1"></i> Tambah Program
                                                    </button>
                                                </div>
                                            </div> <!-- End Indikator Item -->
                                        </div> <!-- End Indikator Container -->
                                        <div class="d-flex justify-content-end mt-2">
                                            <button type="button" class="add-indikator btn btn-success btn-sm">
                                                <i class="fas fa-plus me-1"></i> Tambah Indikator
                                            </button>
                                        </div>
                                    </div> <!-- End Indikator Section -->
                                </div> <!-- End Sasaran Item -->
                            </div> <!-- End Sasaran Container -->
                            <div class="d-flex justify-content-end mt-2">
                                <button type="button" class="add-sasaran btn btn-success btn-sm">
                                    <i class="fas fa-plus me-1"></i> Tambah Sasaran
                                </button>
                            </div>
                        </div> <!-- End Sasaran Section -->
                    </section>
                    <div class="d-flex justify-content-between mt-4">
                        <a href="<?= base_url(($jenis === 'bupati' ? 'adminkab/pk/' : 'adminopd/pk/') . $jenis) ?>"
                            class="btn btn-secondary">
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

    <!-- JavaScript Libraries -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

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
        window.programDropdownTemplate = `<?php
        if (isset($program) && !empty($program)) {
            foreach ($program as $programItem) {
                echo '<option value="' . $programItem['id'] . '" data-anggaran="' . $programItem['anggaran'] . '">' . esc($programItem['program_kegiatan']) . '</option>';
            }
        } else {
            echo '<option value="" disabled>Tidak ada program</option>';
        }
        ?>`;
    </script>

    <!-- Script untuk interaksi form -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Fungsi untuk mengupdate NIP berdasarkan pilihan pegawai
            document.querySelectorAll('.pegawai-select').forEach(select => {
                select.addEventListener('change', function () {
                    const targetField = document.querySelector(`input[name="${this.dataset.target}"]`);
                    if (targetField && this.selectedOptions[0]) {
                        targetField.value = this.selectedOptions[0].dataset.nip || '';
                    }
                });
            });

            // Fungsi untuk program anggaran
            document.querySelectorAll('.program-select').forEach(select => {
                select.addEventListener('change', function () {
                    const anggaranField = this.closest('.row').querySelector('input[name$="[anggaran]"]');
                    if (anggaranField && this.selectedOptions[0]) {
                        anggaranField.value = this.selectedOptions[0].dataset.anggaran || '';
                    }
                });
            });

            // Fungsi untuk misi bupati
            const misiCheckboxes = document.querySelectorAll('input[name="misi_bupati_id[]"]');
            const misiContainer = document.getElementById('selected-misi-container');

            misiCheckboxes.forEach(cb => {
                cb.addEventListener('change', updateSelectedMisi);
            });

            function updateSelectedMisi() {
                const selected = Array.from(misiCheckboxes)
                    .filter(c => c.checked)
                    .map(c => c.getAttribute('data-misi'));

                misiContainer.innerHTML = selected.length ?
                    '<strong>Misi Bupati Terpilih:</strong><br>' + selected.join('<br>') :
                    '';
            }

            // Fungsi untuk indikator acuan
            const indikatorCheckboxes = document.querySelectorAll('input[name="referensi_indikator_id[]"]');
            const indikatorContainer = document.getElementById('selected-indikator-container');

            indikatorCheckboxes.forEach(cb => {
                cb.addEventListener('change', updateSelectedIndikator);
            });

            function updateSelectedIndikator() {
                const selected = Array.from(indikatorCheckboxes)
                    .filter(c => c.checked)
                    .map(c => c.getAttribute('data-indikator'));

                indikatorContainer.innerHTML = selected.length ?
                    '<strong>Indikator Acuan Terpilih:</strong><br>' + selected.join('<br>') :
                    '';
            }

            // Inisialisasi awal
            updateSelectedMisi();
            updateSelectedIndikator();
        });
    </script>
    <script src="<?= base_url('assets/js/adminOpd/pk/pk-form.js') ?>"></script>
</body>

</html>