<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Cascading Esselon III</title>

    <?= $this->include('adminOpd/templates/style.php'); ?>

    <style>
        .cascading-container {
            max-width: 900px;
            margin: auto;
        }

        /* GROUP SASARAN */

        .es3-group {
            border: 1px solid #e3e6ea;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 18px;
            background: #fafafa;
        }

        /* TITLE */

        .level-title {
            font-weight: 600;
            font-size: 14px;
            color: #495057;
            margin-bottom: 6px;
        }

        /* INDIKATOR AREA */

        .indikator-container {
            margin-top: 10px;
            padding-left: 18px;
            border-left: 3px solid #dfe3e7;
        }

        /* INDIKATOR ITEM */

        .indikator-es3 {
            background: #ffffff;
            border: 1px solid #e5e7ea;
            border-radius: 6px;
            padding: 10px 12px;
            margin-top: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* INPUT */

        .indikator-es3 input {
            flex: 1;
        }

        /* DELETE BUTTON */

        .btn-delete {
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            padding: 0;
        }

        /* DELETE INDIKATOR */

        .btn-delete-indikator {
            background: #fff5f5;
            border: 1px solid #ffc9c9;
            color: #e03131;
        }

        .btn-delete-indikator:hover {
            background: #ffe3e3;
        }

        /* DELETE SASARAN */

        .btn-delete-sasaran {
            background: #fff4e6;
            border: 1px solid #ffd8a8;
            color: #d9480f;
        }

        .btn-delete-sasaran:hover {
            background: #ffe8cc;
        }
    </style>

</head>

<body class="bg-light d-flex flex-column min-vh-100">
    <div id="main-content" class="content-wrapper d-flex flex-column" style="transition: margin-left .3s ease;">

    <?= $this->include('adminOpd/templates/header.php'); ?>
    <?= $this->include('adminOpd/templates/sidebar.php'); ?>

    <main class="p-4 flex-grow-1">

        <div class="bg-white rounded shadow-sm p-4 mx-auto cascading-container">

            <h4 class="text-success mb-4">
                Edit Cascading Esselon III
            </h4>
            <?= $this->include('adminOpd/cascading/_form_es3') ?>

        </div>

    </main>

    <?= $this->include('adminOpd/templates/footer.php'); ?>

    <script>
        let es3EditNewIdx = 0;
        function addIndikatorEs3Edit() {
            const key = 'new_' + (es3EditNewIdx++);
            const html = `
                <div class="indikator-es3">
                    <input type="text" name="indikator[${key}][nama]" class="form-control"
                        placeholder="<?= casc_relabel('Masukkan indikator ESS III') ?>">
                    <button type="button" class="btn btn-delete btn-delete-indikator"
                        data-es4-count="0" onclick="hapusIndikatorEs3(this)">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>`;
            document.getElementById('indikator-container').insertAdjacentHTML('beforeend', html);
        }

        function hapusIndikatorEs3(btn) {
            const baris = btn.closest('.indikator-es3');
            const cnt = parseInt(btn.getAttribute('data-es4-count') || '0', 10);
            if (cnt === 0) {
                // Tanpa anak: cukup aturan blok biasa (kosong dibuang langsung).
                Konfirmasi.buangBlok(btn, '.indikator-es3', '<?= esc(casc_relabel('Indikator ESS III'), 'js') ?>');
                return;
            }
            // Punya anak Es4 -> dampaknya berantai, jadi dirinci lewat dialog bersama.
            Konfirmasi.hapus({
                judul: 'Hapus <?= esc(casc_relabel('Indikator ESS III'), 'js') ?>',
                pesan: 'Indikator ini dikeluarkan dari formulir. Perubahan baru berlaku setelah Anda menekan Update.',
                nama: (baris.querySelector('input[type="text"], textarea') || {}).value || '',
                rincian: [cnt + ' <?= esc(casc_relabel('Sasaran Eselon IV'), 'js') ?> di bawahnya ikut terhapus saat Update ditekan']
            }).then(function (ya) {
                if (ya) { baris.remove(); }
            });
        }

        let sasaranBaruIndex = 0;
        function addSasaranBaruEs3() {
            let html = `
                <div class="es3-group mb-3 p-3 border rounded bg-light">
                    <label class="fw-bold mb-2"><?= casc_relabel('Sasaran ESS III') ?> (Baru)</label>
                    <input type="text" name="sasaran_baru[${sasaranBaruIndex}][nama]" class="form-control mb-2" placeholder="<?= casc_relabel('Masukkan Sasaran ESS III') ?>" required>
                    
                    <div class="indikator-container" id="indikator-baru-container-${sasaranBaruIndex}">
                    </div>
                    
                    <div class="mt-2 d-flex gap-2">
                        <button type="button" class="btn btn-sm btn-outline-success" onclick="addIndikatorBaruEs3(${sasaranBaruIndex})">
                            + <?= casc_relabel('Tambah Indikator ESS III') ?>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="Konfirmasi.buangBlok(this, '.es3-group', '<?= esc(casc_relabel('Sasaran ESS III'), 'js') ?>')">
                            <i class="fas fa-trash"></i> Hapus
                        </button>
                    </div>
                </div>
            `;
            document.getElementById('sasaran-baru-container').insertAdjacentHTML('beforeend', html);
            sasaranBaruIndex++;
        }

        function addIndikatorBaruEs3(idx) {
            let indIdx = Date.now();
            let html = `
                <div class="indikator-es3 d-flex gap-2 mt-2">
                    <input type="text" name="sasaran_baru[${idx}][indikator][${indIdx}][nama]" class="form-control" placeholder="<?= casc_relabel('Masukkan indikator ESS III') ?>">
                    <button type="button" class="btn btn-delete btn-delete-indikator" onclick="Konfirmasi.buangBlok(this, '', '<?= esc(casc_relabel('Indikator ESS III'), 'js') ?>')">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            `;
            document.getElementById(`indikator-baru-container-${idx}`).insertAdjacentHTML('beforeend', html);
        }
    </script>
    </div>
</body>

</html>