<?php
/**
 * Gaya bersama Dashboard Pengendalian Kinerja (OPD & Kabupaten).
 *
 * Satu sumber supaya kedua dashboard benar-benar terlihat sebagai satu sistem:
 * kartu putih berbingkai lembut, bayangan tipis, badge status, panel, drawer,
 * skeleton, dan empty state. Warna mengikuti tema hijau aplikasi.
 *
 * Dipakai dengan: <?= $this->include('templates/dashboard_kit') ?>
 */
?>
<style>
  /* ===== Kepala halaman ===== */
  .dash-hero {
    background: linear-gradient(120deg, #00803f 0%, #00642f 100%);
    color: #fff; border-radius: 18px; padding: 22px 26px;
    display: flex; align-items: center; gap: 18px; flex-wrap: wrap;
    position: relative; overflow: hidden;
    box-shadow: 0 14px 34px rgba(0, 116, 62, .18);
  }
  .dash-hero::after {
    content: ''; position: absolute; right: -30px; top: -40px;
    width: 170px; height: 170px; border-radius: 50%; background: rgba(255, 255, 255, .08);
  }
  .dash-hero .dh-ic {
    width: 54px; height: 54px; border-radius: 16px; background: rgba(255, 255, 255, .16);
    display: grid; place-items: center; font-size: 23px; flex: 0 0 auto;
  }
  .dash-hero h2 { font-weight: 800; margin: 0 0 3px; font-size: clamp(1.05rem, 2.2vw, 1.35rem); }
  .dash-hero p { margin: 0; opacity: .88; font-size: .85rem; }
  .dash-mode {
    display: inline-flex; align-items: center; gap: 6px; font-size: .72rem; font-weight: 700;
    background: rgba(255, 255, 255, .18); padding: .3em .7em; border-radius: 999px; letter-spacing: .3px;
  }

  /* ===== Bilah filter ===== */
  .dash-filter {
    background: #fff; border: 1px solid #e9efeb; border-radius: 14px;
    padding: 14px 16px; box-shadow: 0 6px 18px rgba(16, 40, 24, .05);
  }
  .dash-filter label { font-size: .74rem; font-weight: 700; color: #6b7a70; text-transform: uppercase; letter-spacing: .3px; }

  /* ===== Kartu ringkasan ===== */
  .kpi {
    background: #fff; border: 1px solid #e9efeb; border-radius: 16px;
    padding: 16px 18px; height: 100%; width: 100%; text-align: left;
    box-shadow: 0 8px 22px rgba(16, 40, 24, .05);
    transition: transform .16s ease, box-shadow .16s ease, border-color .16s ease;
    display: flex; flex-direction: column; gap: 10px;
  }
  button.kpi { cursor: pointer; }
  button.kpi:hover { transform: translateY(-3px); box-shadow: 0 14px 28px rgba(16, 40, 24, .11); border-color: #d7e3da; }
  .kpi-head { display: flex; align-items: center; gap: 10px; }
  .kpi-ic { width: 38px; height: 38px; border-radius: 11px; display: grid; place-items: center; color: #fff; font-size: 16px; flex: 0 0 auto; }
  .kpi-title { font-size: .78rem; font-weight: 700; color: #6b7a70; text-transform: uppercase; letter-spacing: .3px; }
  .kpi-num { font-size: 1.75rem; font-weight: 800; line-height: 1.05; color: #15311f; }
  .kpi-num.sm { font-size: 1.15rem; }
  .kpi-sub { font-size: .8rem; color: #5d6b62; line-height: 1.45; }
  .kpi-sub .dot { display: inline-block; width: 7px; height: 7px; border-radius: 50%; margin-right: 6px; vertical-align: middle; }
  .kpi-foot { margin-top: auto; font-size: .76rem; font-weight: 700; color: #00743e; }

  /* ===== Panel ===== */
  .panel { background: #fff; border: 1px solid #e9efeb; border-radius: 16px; padding: 18px 20px; height: 100%; box-shadow: 0 8px 22px rgba(16, 40, 24, .05); }
  .panel-head { display: flex; align-items: center; justify-content: space-between; gap: 10px; margin-bottom: 14px; flex-wrap: wrap; }
  .panel-head h3 { margin: 0; font-size: .98rem; font-weight: 700; color: #16321f; }
  .panel-head p { margin: 0; font-size: .76rem; color: #6b7a70; }

  /* ===== Daftar insight & indikator ===== */
  .ins { display: flex; gap: 12px; align-items: flex-start; padding: 11px 12px; border: 1px solid #eef2ef; border-radius: 12px; background: #fcfdfc; }
  .ins + .ins { margin-top: 8px; }
  .ins-bar { width: 4px; align-self: stretch; border-radius: 3px; flex: 0 0 auto; }
  .ins-body { flex: 1 1 auto; min-width: 0; }
  .ins-title { font-weight: 700; font-size: .87rem; color: #1d2b23; line-height: 1.35; }
  .ins-why { font-size: .79rem; color: #6b7a70; margin-top: 2px; }
  .ins-act { flex: 0 0 auto; }

  .ind-card { border: 1px solid #eef2ef; border-radius: 12px; padding: 12px 14px; background: #fff; }
  .ind-card + .ind-card { margin-top: 10px; }
  .ind-meta { display: flex; flex-wrap: wrap; gap: 6px 14px; font-size: .77rem; color: #6b7a70; margin-top: 6px; }
  .ind-pct { font-size: 1.05rem; font-weight: 800; color: #15311f; }

  .badge-soft { font-size: .7rem; font-weight: 700; padding: .3em .6em; border-radius: 7px; display: inline-flex; align-items: center; gap: 5px; }

  /* ===== Accordion misi ===== */
  .misi-item { border: 1px solid #eef2ef; border-radius: 12px; overflow: hidden; }
  .misi-item + .misi-item { margin-top: 8px; }
  .misi-btn { width: 100%; text-align: left; background: #fcfdfc; border: 0; padding: 12px 14px; display: flex; gap: 12px; align-items: flex-start; cursor: pointer; }
  .misi-btn:hover { background: #f3f8f4; }
  .misi-no { flex: 0 0 auto; width: 30px; height: 30px; border-radius: 9px; background: #e8f2ec; color: #00743e; font-weight: 800; font-size: .78rem; display: grid; place-items: center; }

  /* ===== Grafik ===== */
  .chart-box { position: relative; height: 260px; }
  @media (max-width: 575px) { .chart-box { height: 220px; } }

  /* ===== Drawer ===== */
  .offcanvas.dash-drawer { width: min(600px, 100%); }
  .dash-drawer .offcanvas-header { background: linear-gradient(120deg, #00803f, #00642f); color: #fff; }
  .dash-drawer .offcanvas-header .btn-close { filter: invert(1) grayscale(1) brightness(2); }
  .dash-drawer .offcanvas-body { background: #f7faf8; }
  .drawer-section { background: #fff; border: 1px solid #eef2ef; border-radius: 12px; padding: 14px; margin-bottom: 12px; }
  .drawer-dl { display: grid; grid-template-columns: minmax(120px, 40%) 1fr; gap: 6px 12px; font-size: .82rem; }
  .drawer-dl dt { color: #6b7a70; font-weight: 600; }
  .drawer-dl dd { margin: 0; color: #1d2b23; }

  /* ===== Skeleton & empty state ===== */
  .skel { background: linear-gradient(90deg, #eef2ef 25%, #f7faf8 37%, #eef2ef 63%); background-size: 400% 100%; animation: skel 1.3s ease infinite; border-radius: 8px; height: 14px; }
  .skel + .skel { margin-top: 8px; }
  @keyframes skel { 0% { background-position: 100% 50%; } 100% { background-position: 0 50%; } }
  .empty { text-align: center; padding: 26px 18px; color: #6b7a70; }
  .empty .ic { width: 54px; height: 54px; border-radius: 16px; background: #eef4f0; color: #00743e; display: grid; place-items: center; font-size: 21px; margin: 0 auto 12px; }
</style>
