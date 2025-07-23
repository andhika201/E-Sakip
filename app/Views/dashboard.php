<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Dashboard - e-SAKIP Kabupaten Pringsewu</title>
  <?= $this->include('/templates/style.php'); ?>
</head>
<body>
  
  <?= $this->include('/templates/header'); ?>
  
  <main class="container main-content">
    <h4 class="fw-bold text-success">Selamat Datang di Dashboard e-SAKIP!</h4>
    <p class="text-muted">Gunakan menu navigasi di atas untuk mengakses berbagai fitur dan informasi terkait kinerja pemerintahan daerah.</p>
  </main>
  
  <?= $this->include('/user/templates/footer'); ?>
</body>
</html>