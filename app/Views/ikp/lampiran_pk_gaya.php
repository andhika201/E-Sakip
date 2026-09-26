<?php
/**
 * Gaya bersama Lampiran II–V PK Eselon II (mPDF).
 *
 * MENGAPA tidak memakai templates/pdf_style: berkas ini dicetak SEDOKUMEN
 * dengan halaman perjanjian & Lampiran I (adminOpd/pk/cetak*.php) yang
 * bertipografi Times. pdf_style memaksa `* { DejaVu Sans }` sehingga lampiran
 * akan tampil beda huruf dari halaman perjanjiannya.
 *
 * MENGAPA semua aturan berawalan kelas `lp-`: CSS mPDF MENETAP antar
 * WriteHTML(). Aturan tag milik view PK lama (mis. `td, th { font-size: 11pt;
 * padding: 6px 8px }`) ikut berlaku di halaman lampiran; aturan berkelas di
 * sini menimpanya (mPDF: kelas menang atas tag). Kelas `vm*` sengaja memakai
 * nama yang sama dengan app/Helpers/pdf_helper.php::pdf_td_gabung().
 */
?>
<style>
  .lp-wrap { font-family: "Times New Roman", Times, serif; color: #000; }
  .lp-label { font-family: "Times New Roman", Times, serif; font-size: 11pt; font-weight: bold; margin: 0; }
  .lp-sub   { font-family: "Times New Roman", Times, serif; font-size: 11pt; font-weight: bold; margin: 0; }
  .lp-meta  { font-family: "Times New Roman", Times, serif; font-size: 10pt; margin: 2px 0 10px 0; }
  .lp-catatan { font-family: "Times New Roman", Times, serif; font-size: 9.5pt; border: 0.6px solid #b58900; background-color: #fff8e1;
                padding: 6px 8px; margin: 0 0 10px 0; }
  .lp-legenda { font-family: "Times New Roman", Times, serif; font-size: 8.5pt; margin-top: 6px; color: #333; }

  table.lp-t { width: 100%; border-collapse: collapse; margin-top: 4px; }
  table.lp-t th { font-family: "Times New Roman", Times, serif; font-size: 8.5pt; font-weight: bold; text-align: center; vertical-align: middle;
                  background-color: #d9d9d9; border: 0.6px solid #000; padding: 4px 3px; }
  table.lp-t td { font-family: "Times New Roman", Times, serif; font-size: 8.5pt; vertical-align: top;
                  border: 0.6px solid #000; padding: 3px 4px; }
  table.lp-t td.lp-c { text-align: center; }
  table.lp-t td.lp-r { text-align: right; }
  table.lp-t td.lp-nomor { text-align: center; font-style: italic; font-size: 7.5pt; background-color: #f2f2f2; padding: 1px 2px; }
  table.lp-t td.lp-banner { font-weight: bold; background-color: #eeeeee; }
  table.lp-t td.lp-visi { font-weight: bold; background-color: #e2efe7; }
  table.lp-t td.lp-kosong { text-align: center; font-style: italic; color: #555; padding: 10px; }
  table.lp-t td.lp-sorot, table.lp-t th.lp-sorot { background-color: #e8f3ec; font-weight: bold; }
  table.lp-t td.lp-tw { background-color: #f5f5f5; }
  table.lp-t td.lp-mini { font-size: 7.5pt; color: #444; }

  table.lp-t.lp-kecil th { font-size: 7.4pt; padding: 3px 2px; }
  table.lp-t.lp-kecil td { font-size: 7.4pt; padding: 2px 2px; }

  /* Gabungan visual (pengganti rowspan besar — lihat pdf_helper.php). */
  table.lp-t td.vm { border-top: none; border-bottom: none; }
  table.lp-t td.vm-awal { border-top: 0.6px solid #000; }
  table.lp-t td.vm-akhir { border-bottom: 0.6px solid #000; }

  table.lp-ttd { width: 100%; border-collapse: collapse; margin-top: 18px; page-break-inside: avoid; }
  table.lp-ttd td { font-family: "Times New Roman", Times, serif; font-size: 10.5pt; text-align: center; vertical-align: top;
                    border: none; padding: 1px 4px; }
  table.lp-ttd td.lp-ttd-ruang { height: 62px; }
  table.lp-ttd td.lp-ttd-nama { font-weight: bold; text-decoration: underline; text-transform: uppercase; }
</style>
