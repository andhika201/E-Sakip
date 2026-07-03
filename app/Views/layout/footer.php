<?php
// Nilai dari Pengaturan Aplikasi (fallback ke default lama)
$sn_code   = setting('serial_number', $sn ?? 'ESAKIP-2025-001');
$show_full = $full ?? false;

$f_app      = setting('app_name', 'e-SAKIP');
$f_long     = setting('app_long_name', 'Sistem Akuntabilitas Kinerja Instansi Pemerintah');
$f_instansi = setting('instansi', 'Pemerintah Kabupaten Pringsewu');
$f_addr     = setting('instansi_address', '');
$f_phone    = setting('instansi_phone', '');
$f_email    = setting('instansi_email', '');
$f_logo     = base_url(setting('app_logo', 'assets/images/LogoTentang.png'));
$f_devlogo  = base_url(setting('dev_logo', 'assets/images/devtech.png'));
$f_devname  = setting('dev_name', 'DevTech');
?>
<?php if ($show_full): ?>

<style>
    .site-footer {
        background: linear-gradient(180deg, #016f3c 0%, #014c29 100%);
        color: #fff;
        margin-top: auto;
        padding: 0;
    }
    .site-footer .sf-top { padding: 40px 0 28px; }
    .site-footer .sf-brand { display: flex; align-items: center; gap: 12px; margin-bottom: 14px; }
    .site-footer .sf-brand img { height: 64px; width: auto; object-fit: contain; }
    .site-footer .sf-brand-title { font-weight: 800; font-size: 1.3rem; letter-spacing: .5px; line-height: 1.1; }
    .site-footer .sf-brand-sub { font-size: .82rem; opacity: .82; }
    .site-footer .sf-desc { font-size: .85rem; opacity: .82; line-height: 1.65; max-width: 360px; margin-bottom: 16px; }
    .site-footer .sf-social a {
        display: inline-grid;
        place-items: center;
        width: 38px; height: 38px;
        border-radius: 11px;
        background: rgba(255, 255, 255, .12);
        color: #fff;
        margin-right: 8px;
        text-decoration: none;
        transition: transform .15s ease, background .15s ease;
    }
    .site-footer .sf-social a:hover { background: #6eab11; transform: translateY(-3px); }
    .site-footer .sf-head {
        font-weight: 700;
        font-size: .98rem;
        margin-bottom: 16px;
        position: relative;
        padding-bottom: 9px;
    }
    .site-footer .sf-head::after {
        content: '';
        position: absolute;
        left: 0; bottom: 0;
        width: 36px; height: 3px;
        border-radius: 3px;
        background: #9bd34a;
    }
    .site-footer .sf-links { display: flex; flex-direction: column; gap: 10px; }
    .site-footer .sf-links a {
        color: rgba(255, 255, 255, .85);
        text-decoration: none;
        font-size: .88rem;
        width: fit-content;
        transition: color .15s ease, padding .15s ease;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    .site-footer .sf-links a i { font-size: .7rem; color: #9bd34a; }
    .site-footer .sf-links a:hover { color: #fff; padding-left: 4px; }
    .site-footer .sf-contact { list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 12px; }
    .site-footer .sf-contact li { display: flex; gap: 11px; font-size: .86rem; opacity: .9; line-height: 1.4; }
    .site-footer .sf-contact li i { color: #9bd34a; margin-top: 3px; flex: 0 0 auto; }
    .site-footer .sf-bottom { border-top: 1px solid rgba(255, 255, 255, .15); padding: 15px 0; }
    .site-footer .sf-copy { margin: 0; font-size: .82rem; opacity: .82; }
    .site-footer .sf-powered { display: flex; align-items: center; gap: 12px; }
    .site-footer .sf-powered img { height: 62px; width: auto; object-fit: contain; filter: brightness(0) invert(1); opacity: .95; }
    .site-footer .sf-powered .pw-label { font-size: .8rem; opacity: .85; line-height: 1.3; }
    .site-footer .sf-powered .pw-sn { font-size: .85rem; font-weight: 700; letter-spacing: .04em; opacity: .92; line-height: 1.3; }
</style>

<footer class="site-footer">
    <div class="sf-top">
        <div class="container-fluid px-4">
            <div class="row g-4">

                <!-- Brand -->
                <div class="col-lg-4">
                    <div class="sf-brand">
                        <img src="<?= $f_logo ?>" alt="<?= esc($f_app) ?>" />
                        <div>
                            <div class="sf-brand-title"><?= esc($f_app) ?></div>
                            <div class="sf-brand-sub"><?= esc($f_instansi) ?></div>
                        </div>
                    </div>
                    <p class="sf-desc">
                        <?= esc($f_long) ?> <?= esc($f_instansi) ?> &mdash; transparan, terukur, dan akuntabel.
                    </p>
                    <div class="sf-social">
                        <a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                        <a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                        <a href="#" aria-label="YouTube"><i class="fab fa-youtube"></i></a>
                    </div>
                </div>

                <!-- Tautan -->
                <div class="col-lg-4 col-md-6">
                    <div class="sf-head">Tautan</div>
                    <div class="sf-links">
                        <a href="<?= base_url('dashboard') ?>"><i class="fas fa-chevron-right"></i> Beranda</a>
                        <a href="<?= base_url('rpjmd') ?>"><i class="fas fa-chevron-right"></i> RPJMD</a>
                        <a href="<?= base_url('cascading_kabupaten') ?>"><i class="fas fa-chevron-right"></i> Cascading Kabupaten</a>
                        <a href="<?= base_url('pohon_kinerja_kabupaten') ?>"><i class="fas fa-chevron-right"></i> Pohon Kinerja Kabupaten</a>
                        <a href="<?= base_url('lakip_kabupaten') ?>"><i class="fas fa-chevron-right"></i> LAKIP</a>
                        <a href="<?= base_url('tentang_kami') ?>"><i class="fas fa-chevron-right"></i> Tentang Kami</a>
                    </div>
                </div>

                <!-- Kontak -->
                <div class="col-lg-4 col-md-6">
                    <div class="sf-head">Kontak</div>
                    <ul class="sf-contact">
                        <?php if ($f_addr): ?><li><i class="fas fa-location-dot"></i> <?= esc($f_addr) ?></li><?php endif; ?>
                        <?php if ($f_phone): ?><li><i class="fas fa-phone"></i> <?= esc($f_phone) ?></li><?php endif; ?>
                        <?php if ($f_email): ?><li><i class="fas fa-envelope"></i> <?= esc($f_email) ?></li><?php endif; ?>
                    </ul>
                </div>

            </div>
        </div>
    </div>

    <div class="sf-bottom">
        <div class="container-fluid px-4 d-flex flex-column flex-sm-row align-items-center justify-content-between gap-2">
            <p class="sf-copy">&copy; <?= date('Y') ?> <?= esc($f_instansi) ?>. All rights reserved.</p>
            <div class="sf-powered">
                <img src="<?= $f_devlogo ?>" alt="<?= esc($f_devname) ?>" />
                <div class="text-end">
                    <div class="pw-label">Powered by</div>
                    <div class="pw-sn">SN: <?= esc($sn_code) ?></div>
                </div>
            </div>
        </div>
    </div>
</footer>

<?php else: ?>

<footer class="bg-success text-white py-2 mt-auto" style="background-color: #00743e !important;">
    <div class="container-fluid px-4">
        <div class="d-flex flex-column flex-sm-row align-items-center justify-content-between gap-2">
            <p class="small mb-0 opacity-75">
                &copy; <?= date('Y') ?> <?= esc($f_instansi) ?>. All rights reserved.
            </p>
            <div class="d-flex align-items-center gap-3">
                <img src="<?= $f_devlogo ?>" alt="<?= esc($f_devname) ?>"
                    style="height: 60px; width: auto; object-fit: contain; filter: brightness(0) invert(1); opacity: .95;">
                <div class="text-end">
                    <div style="font-size: 0.8rem; opacity: 0.85; line-height: 1.3;">Powered by</div>
                    <div style="font-size: 0.85rem; opacity: 0.9; line-height: 1.3; letter-spacing: 0.05em; font-weight: 700;">SN: <?= esc($sn_code) ?></div>
                </div>
            </div>
        </div>
    </div>
</footer>

<?php endif; ?>
