<?php
/**
 * CSS standar untuk semua dokumen cetak PDF (Mpdf).
 * Include di <head> view cetak: <?= $this->include('templates/pdf_style') ?>
 * Tema hijau e-SAKIP, tipografi rapi, tabel bersih, blok tanda tangan.
 */
?>
<style>
    * { font-family: "DejaVu Sans", sans-serif; }
    body { color: #222; font-size: 10px; line-height: 1.4; }

    /* ===== KOP SURAT ===== */
    .pdf-kop { width: 100%; border-collapse: collapse; }
    .pdf-kop td { vertical-align: middle; padding: 0; }
    .pdf-kop .kop-logo { height: 64px; width: auto; }
    .pdf-kop .inst-name { font-size: 16.5px; font-weight: bold; letter-spacing: .6px; text-transform: uppercase; color: #15311f; }
    .pdf-kop .inst-unit { font-size: 13px; font-weight: bold; letter-spacing: .3px; text-transform: uppercase; color: #15311f; }
    .pdf-kop .inst-addr { font-size: 8.5px; color: #555; margin-top: 3px; }
    .pdf-kop-rule { border: 0; border-top: 2.5px solid #00743e; margin: 4px 0 0; }
    .pdf-kop-rule.thin { border-top: 0.8px solid #00743e; margin-top: 1.5px; }

    /* ===== JUDUL ===== */
    .pdf-title { text-align: center; margin: 12px 0 2px; font-size: 13px; font-weight: bold; text-transform: uppercase; color: #15311f; }
    .pdf-subtitle { text-align: center; font-size: 10px; color: #555; margin-bottom: 12px; }

    /* ===== TABEL DATA ===== */
    table.pdf-table { width: 100%; border-collapse: collapse; font-size: 9px; }
    table.pdf-table th, table.pdf-table td { border: 0.5px solid #6b7a70; padding: 4px 6px; vertical-align: top; }
    table.pdf-table thead th { background: #00743e; color: #fff; text-align: center; font-weight: bold; font-size: 8.6px; text-transform: uppercase; letter-spacing: .3px; }
    table.pdf-table tbody tr:nth-child(even) td { background: #f4f8f5; }
    table.pdf-table td.c, table.pdf-table th.c { text-align: center; }
    table.pdf-table td.r { text-align: right; }
    .pdf-muted { color: #8a958d; }

    /* ===== TANDA TANGAN ===== */
    .pdf-ttd { width: 100%; margin-top: 26px; border-collapse: collapse; }
    .pdf-ttd td { font-size: 10px; vertical-align: top; text-align: center; }
    .pdf-ttd .sp { height: 62px; }
    .pdf-ttd .nm { font-weight: bold; text-decoration: underline; }
    .pdf-ttd .nip { font-size: 9px; }
</style>
