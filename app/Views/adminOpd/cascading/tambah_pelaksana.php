<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc(casc_pelaksana_label('Tambah Cascading ')) ?></title>

    <?= $this->include('adminOpd/templates/style.php'); ?>

    <style>
        .cascading-container { max-width: 900px; margin: auto; }
        .pel-group {
            border: 1px solid #e3e6ea;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 18px;
            background: #fafafa;
        }
        .level-title { font-weight: 600; font-size: 14px; color: #495057; margin-bottom: 6px; }
        .indikator-container { margin-top: 10px; padding-left: 18px; border-left: 3px solid #dfe3e7; }
        .indikator-pel {
            background: #fff;
            border: 1px solid #e5e7ea;
            border-radius: 6px;
            padding: 10px 12px;
            margin-top: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .indikator-pel input { flex: 1; }
        .btn-delete {
            width: 32px; height: 32px;
            display: flex; align-items: center; justify-content: center;
            border-radius: 6px; padding: 0;
        }
        .btn-delete-indikator { background: #fff5f5; border: 1px solid #ffc9c9; color: #e03131; }
        .btn-delete-indikator:hover { background: #ffe3e3; }
        .btn-delete-sasaran { background: #fff4e6; border: 1px solid #ffd8a8; color: #d9480f; }
        .btn-delete-sasaran:hover { background: #ffe8cc; }
    </style>
</head>

<body class="bg-light d-flex flex-column min-vh-100">
    <div id="main-content" class="content-wrapper d-flex flex-column" style="transition: margin-left .3s ease;">

        <?= $this->include('adminOpd/templates/header.php'); ?>
        <?= $this->include('adminOpd/templates/sidebar.php'); ?>

        <main class="p-4 flex-grow-1">
            <div class="bg-white rounded shadow-sm p-4 mx-auto cascading-container">

                <h4 class="text-success mb-4">
                    <?= esc(casc_pelaksana_label('Tambah Cascading ')) ?>
                </h4>

                <form action="<?= base_url('adminopd/cascading/save-pelaksana') ?>" method="post" id="form-pelaksana">
                    <?= csrf_field() ?>

                    <?php // Hanya id indikator ES IV yang dikirim; OPD, parent, dan
                          // renstra indikator diturunkan server dari baris induknya. ?>
                    <input type="hidden" name="es4_indikator_id" value="<?= (int) $indikator['es4_indikator_id'] ?>">

                    <div class="mb-3">
                        <label><?= casc_relabel('Sasaran ESS IV / JF') ?></label>
                        <input type="text" class="form-control bg-light" value="<?= esc($indikator['sasaran_es4']) ?>" readonly>
                    </div>

                    <div class="mb-3">
                        <label><?= casc_relabel('Indikator ESS IV') ?></label>
                        <input type="text" class="form-control bg-light" value="<?= esc($indikator['indikator_es4']) ?>" readonly>
                    </div>

                    <hr>

                    <div class="mb-3">
                        <label class="fw-bold"><?= esc(casc_pelaksana_label('Sasaran ')) ?></label>
                        <div id="pel-container"></div>
                        <button type="button" class="btn btn-sm btn-success mt-2" onclick="addPelaksana()">
                            + <?= esc(casc_pelaksana_label('Tambah Sasaran ')) ?>
                        </button>
                        <div class="form-text">Minimal satu Sasaran <?= esc(casc_pelaksana_label()) ?> harus diisi.</div>
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-success">Simpan</button>
                        <a href="<?= base_url('adminopd/cascading?periode=' . urlencode((string) $periode)) ?>"
                            class="btn btn-secondary">Kembali</a>
                    </div>
                </form>
            </div>
        </main>

        <?= $this->include('adminOpd/templates/footer.php'); ?>

        <script>
            let pelIndex = 0;

            function addPelaksana() {
                const html = `
                <div class="pel-group">
                    <div class="level-title"><?= esc(casc_pelaksana_label('Sasaran ')) ?></div>
                    <input type="text"
                        name="sasaran[${pelIndex}][nama]"
                        class="form-control mb-2 pel-nama"
                        placeholder="<?= esc(casc_pelaksana_label('Masukkan Sasaran '), 'attr') ?>"
                        required>
                    <div class="indikator-container" id="indikator-pel-${pelIndex}"></div>
                    <div class="mt-2 d-flex gap-2">
                        <button type="button" class="btn btn-sm btn-outline-success"
                            onclick="addIndikatorPelaksana(${pelIndex})">
                            + <?= esc(casc_pelaksana_label('Tambah Indikator ')) ?>
                        </button>
                        <button type="button" class="btn btn-delete btn-delete-sasaran"
                            onclick="this.closest('.pel-group').remove()">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>`;
                document.getElementById('pel-container').insertAdjacentHTML('beforeend', html);
                pelIndex++;
            }

            function addIndikatorPelaksana(pel) {
                const idx = Date.now();
                const html = `
                <div class="indikator-pel">
                    <input type="text"
                        name="sasaran[${pel}][indikator][${idx}][nama]"
                        class="form-control"
                        placeholder="<?= esc(casc_pelaksana_label('Masukkan indikator '), 'attr') ?>">
                    <button type="button" class="btn btn-delete btn-delete-indikator"
                        onclick="this.parentElement.remove()">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>`;
                document.getElementById(`indikator-pel-${pel}`).insertAdjacentHTML('beforeend', html);
            }

            // Satu blok sasaran langsung disiapkan agar form tidak tampak kosong.
            addPelaksana();

            // Validasi sisi depan (server tetap memvalidasi ulang).
            document.getElementById('form-pelaksana').addEventListener('submit', function (e) {
                const terisi = Array.from(document.querySelectorAll('.pel-nama'))
                    .filter(el => el.value.trim() !== '');
                if (terisi.length === 0) {
                    e.preventDefault();
                    alert('Minimal satu Sasaran <?= esc(casc_pelaksana_label()) ?> harus diisi.');
                }
            });
        </script>
    </div>
</body>

</html>
