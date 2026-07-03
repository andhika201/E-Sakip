<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Cascading Esselon IV</title>

    <?= $this->include('adminOpd/templates/style.php'); ?>

    <style>
        .cascading-container {
            max-width: 900px;
            margin: auto;
        }

        /* GROUP SASARAN */

        .es4-group {
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

        .indikator-es4 {
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
                Edit Cascading Esselon IV
            </h4>
            <?= $this->include('adminOpd/cascading/_form_es4') ?>

        </div>

    </main>

    <?= $this->include('adminOpd/templates/footer.php'); ?>

    <script src="<?= base_url('assets/js/adminOpd/cascading/cascading-es4.js') ?>"></script>
    </div>
</body>

</html>