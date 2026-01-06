<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Import Lampiran 8 APBD' ?></title>
    <?= $this->include('adminKabupaten/templates/style.php'); ?>
</head>

<body class="bg-light min-vh-100 d-flex flex-column position-relative">

    <!-- Navbar/Header -->
    <?= $this->include('adminKabupaten/templates/header.php'); ?>

    <!-- Sidebar -->
    <?= $this->include('adminKabupaten/templates/sidebar.php'); ?>

    <!-- Konten Utama -->
    <main class="flex-fill d-flex justify-content-center p-4 mt-4">
        <div class="bg-white rounded shadow-sm p-4" style="width: 100%; max-width: 1200px;">
            <h2 class="h3 fw-bold text-center mb-4" style="color: #00743e;">Import Program/Kegiatan/Sub Kegiatan PK</h2>

            <form id="import-lampiran8-form" method="POST"
                action="<?= base_url('adminkab/program_pk/import/process') ?>" enctype="multipart/form-data">
                <?= csrf_field() ?>

                <!-- Flash Messages -->
                <?php if (session()->getFlashdata('success')): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i>
                        <?= session()->getFlashdata('success') ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (session()->getFlashdata('error')): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <?= session()->getFlashdata('error') ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (session()->getFlashdata('validation')): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <ul class="mb-0">
                            <?php foreach (session()->getFlashdata('validation') as $error): ?>
                                <li><?= $error ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Kartu Import -->
                <section>
                    <!-- Tahun Anggaran -->
                    <div class="bg-light border rounded p-3 mb-3">
                        <label class="fw-medium h5 d-block mb-3">Tahun Anggaran</label>

                        <div class="row">
                            <div class="col-md-4">
                                <select name="tahun_anggaran" id="tahun_anggaran"
                                    class="form-control border-secondary" required>
                                    <option value="">-- Pilih Tahun Anggaran --</option>
                                    <?php
                                    $tahunSekarang = date('Y');
                                    for ($t = $tahunSekarang - 2; $t <= $tahunSekarang + 1; $t++):
                                    ?>
                                        <option value="<?= $t ?>"><?= $t ?></option>
                                    <?php endfor; ?>
                                </select>
                                <small class="text-muted">
                                    Pilih tahun APBD sesuai file yang akan diimpor
                                </small>
                            </div>
                        </div>
                    </div>

                    <!-- Upload File -->
                    <div class="bg-light border rounded p-3 mb-3">
                        <label class="fw-medium h5 d-block mb-3">Upload File Excel</label>

                        <div class="row g-3">
                            <div class="col-lg-6">
                                <label class="form-label">Pilih File (.xlsx / .xls)</label>
                                <input
                                    type="file"
                                    name="file"
                                    id="excel-file"
                                    class="form-control border-secondary"
                                    accept=".xlsx,.xls"
                                    required>
                                <small class="text-muted d-block mt-1">
                                    Maksimal 20 MB. File Lampiran APBD (format SIPD).
                                </small>
                            </div>

                            <div class="col-lg-3">
                                <label class="form-label">Sheet (opsional)</label>
                                <input
                                    type="text"
                                    name="sheet"
                                    class="form-control border-secondary"
                                    placeholder="Kosongkan untuk sheet aktif">
                            </div>

                            <div class="col-lg-3">
                                <label class="form-label">Header di Baris</label>
                                <input
                                    type="number"
                                    name="header_row"
                                    class="form-control border-secondary"
                                    value="1"
                                    min="1">
                                <small class="text-muted">Umumnya: 1</small>
                            </div>

                            <div class="col-12">
                                <div class="form-check mt-2">
                                    <input
                                        class="form-check-input"
                                        type="checkbox"
                                        value="1"
                                        id="filldown"
                                        name="filldown"
                                        checked>
                                    <label class="form-check-label" for="filldown">
                                        Gunakan <em>fill-down</em> otomatis untuk sel kosong
                                        (mengikuti baris sebelumnya)
                                    </label>
                                </div>

                                <div class="form-check mt-2">
                                    <input
                                        class="form-check-input"
                                        type="checkbox"
                                        value="1"
                                        id="dryrun"
                                        name="dryrun">
                                    <label class="form-check-label" for="dryrun">
                                        <strong>Simulasi saja</strong> (tanpa menyimpan ke database)
                                    </label>
                                </div>
                            </div>
                        </div>

                        <hr class="my-4">

                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="p-3 border rounded">
                                    <div class="fw-semibold mb-2">Aturan Mapping Kolom</div>
                                    <ul class="small text-muted mb-0">
                                        <li><strong>D ≠ 0, E & F kosong</strong> → Program</li>
                                        <li><strong>D ≠ 0, E ada, F kosong</strong> → Kegiatan</li>
                                        <li><strong>D ≠ 0, E & F ada</strong> → Sub Kegiatan</li>
                                        <li>Kolom <strong>J</strong> → Anggaran (Rp)</li>
                                    </ul>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="p-3 border rounded">
                                    <div class="fw-semibold mb-2">Tips</div>
                                    <ul class="small text-muted mb-0">
                                        <li>Pastikan tahun anggaran sesuai dengan file.</li>
                                        <li>Gunakan <strong>Simulasi</strong> untuk cek hasil sebelum commit.</li>
                                        <li>Re-import di tahun yang sama akan <strong>update</strong>, bukan duplikasi.</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Tombol Aksi -->
                <div class="d-flex justify-content-between mt-4">
                    <a href="<?= base_url('adminkab/program_pk') ?>" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Kembali
                    </a>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-upload me-1"></i> Import
                    </button>
                </div>
            </form>
        </div>
    </main>

    <?= $this->include('adminKabupaten/templates/footer.php'); ?>

    <script>
        // Alert ke pengguna saat menekan tombol kembali
        document.querySelector('a.btn-secondary').addEventListener('click', function(e) {
            if (!confirm('Yakin ingin kembali? Proses import dibatalkan.')) {
                e.preventDefault();
            }
        });

        // Validasi front-end file Excel
        (function() {
            const form = document.getElementById('import-lampiran8-form');
            const input = document.getElementById('excel-file');
            const MAX_SIZE = 20 * 1024 * 1024; // 20MB

            form.addEventListener('submit', function(e) {
                if (!input.files || input.files.length === 0) {
                    alert('Silakan pilih file Excel (.xlsx / .xls)');
                    e.preventDefault();
                    return;
                }

                const file = input.files[0];
                const name = file.name.toLowerCase();
                if (!name.endsWith('.xlsx') && !name.endsWith('.xls')) {
                    alert('Format file harus .xlsx atau .xls');
                    e.preventDefault();
                    return;
                }

                if (file.size > MAX_SIZE) {
                    alert('Ukuran file melebihi 20 MB');
                    e.preventDefault();
                    return;
                }

                if (!confirm('Yakin ingin memproses import dari file ini?')) {
                    e.preventDefault();
                }
            });
        })();

        document.getElementById('import-lampiran8-form').addEventListener('submit', function(e) {
            const tahun = document.getElementById('tahun_anggaran').value;
            if (!tahun) {
                alert('Silakan pilih Tahun Anggaran terlebih dahulu');
                e.preventDefault();
            }
        });
    </script>
</body>

</html>