<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Cascading</title>
    <?= $this->include('adminKabupaten/templates/style.php'); ?>
</head>

<body class="bg-light">

    <?= $this->include('adminKabupaten/templates/header.php'); ?>
    <?= $this->include('adminKabupaten/templates/sidebar.php'); ?>

    <main class="p-4">

        <div class="bg-white rounded shadow-sm p-4" style="max-width:800px;margin:auto">

            <h4 class="text-success mb-4">Tambah Mapping Cascading</h4>

            <form action="<?= base_url('adminkab/cascading/save') ?>" method="post">
                <?= csrf_field() ?>

                <input type="hidden" name="indikator_id" value="<?= $indikator['id'] ?>">

                <div class="mb-3">
                    <label>Indikator</label>
                    <input type="text" class="form-control" value="<?= esc($indikator['indikator_sasaran']) ?>"
                        readonly>
                </div>

                <div class="mb-3">
                    <label>Tahun</label>
                    <?php $currentYear = date('Y'); ?>

                    <select name="tahun" id="tahun" class="form-select">
                        <?php foreach ($years as $y): ?>
                            <option value="<?= $y ?>" <?= ($y == $currentYear ? 'selected' : '') ?>>
                                <?= $y ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>


                <div class="mb-3">
                    <label>OPD & Program Pendukung</label>

                    <div id="opd-container"></div>

                    <button type="button" class="btn btn-sm btn-success mt-2" onclick="addOpdGroup()">

                        + Tambah OPD

                    </button>

                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-success">Simpan</button>
                    <a href="<?= base_url('adminkab/cascading?periode=' . $periode) ?>"
                        class="btn btn-secondary">Kembali</a>
                </div>

            </form>

        </div>

    </main>

    <?= $this->include('adminKabupaten/templates/footer.php'); ?>


    <script>
        let opdOptions = `
        <?php foreach ($opd_list as $o): ?>
        <option value="<?= $o['id'] ?>">
        <?= esc($o['nama_opd']) ?>
        </option>
        <?php endforeach; ?>
        `;
    </script>

    <script>
        const BASE_URL = "<?= base_url() ?>";
    </script>

    <script src="<?= base_url('assets/js/adminKabupaten/cascading/cascading.js') ?>"></script>

</body>

</html>