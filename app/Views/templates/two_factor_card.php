<?php /** Kartu status 2FA untuk halaman ganti password. Butuh $twofaEnabled. */ ?>
<div class="col-12">
  <div class="bg-light p-4 rounded border">
    <h3 class="h5 fw-semibold text-success mb-2">
      <i class="fas fa-shield-alt me-2"></i>Autentikasi Dua Faktor (2FA)
    </h3>
    <p class="text-muted mb-3">
      Lapisan keamanan tambahan. Saat login, selain password Anda perlu memasukkan kode 6 digit
      dari aplikasi authenticator (Google Authenticator, Authy, dll).
    </p>

    <?php if (!empty($twofaEnabled)): ?>
      <p><span class="badge bg-success"><i class="fas fa-check me-1"></i> 2FA Aktif</span></p>
      <form method="post" action="<?= base_url('2fa/disable') ?>"
        onsubmit="return confirm('Nonaktifkan 2FA? Akun jadi kurang aman.');"
        class="row g-2 align-items-end">
        <?= csrf_field() ?>
        <div class="col-md-6">
          <label class="form-label small fw-semibold">Konfirmasi password untuk menonaktifkan</label>
          <input type="password" name="password" class="form-control" placeholder="Password akun" required>
        </div>
        <div class="col-auto">
          <button type="submit" class="btn btn-outline-danger">
            <i class="fas fa-times-circle me-1"></i> Nonaktifkan 2FA
          </button>
        </div>
      </form>
    <?php else: ?>
      <p><span class="badge bg-secondary">2FA Nonaktif</span></p>
      <a href="<?= base_url('2fa/setup') ?>" class="btn btn-success">
        <i class="fas fa-shield-alt me-1"></i> Aktifkan 2FA
      </a>
    <?php endif; ?>
  </div>
</div>
