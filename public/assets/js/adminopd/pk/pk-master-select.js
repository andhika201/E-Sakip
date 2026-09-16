/**
 * Select2 RINGAN untuk dropdown master pada form Perjanjian Kinerja.
 *
 * =====================================================================
 * MASALAH YANG DISELESAIKAN
 *
 * Dulu tiap <select> Program/Kegiatan/Sub Kegiatan memuat SELURUH master
 * sebagai <option> — 3.185 sub kegiatan × satu dropdown per baris. Edit PK
 * pengawas dengan 11 sub kegiatan = 35.000 <option>, 31 MB HTML, dan
 * peramban operator membeku ("Page Unresponsive").
 *
 * Sekarang <select data-master="subkegiatan"> hanya berisi option terpilih.
 * Daftar lengkapnya ada SEKALI di window.pkMaster (JSON dari
 * app/Helpers/pk_form_helper.php) dan disaring DI PERAMBAN lewat
 * ajax.transport Select2 — tidak ada permintaan ke server, dan hanya 50
 * hasil per halaman yang dirender.
 *
 * =====================================================================
 * KONTRAK DENGAN SKRIP FORM (pk-form.js, pk-admin-form.js, pk-pengawas-*.js)
 *
 *  - Skrip form memanggil PkMasterSelect.init(el) SEBELUM inisialisasi
 *    Select2 biasa; bila true, select itu sudah ditangani di sini.
 *  - Skrip form membaca data-anggaran / data-program dari <option> terpilih.
 *    Select2 membuat <option> baru untuk pilihan dari ajax TANPA atribut itu,
 *    jadi disalin di sinkronAtribut() pada `change` — handler langsung pada
 *    elemen berjalan sebelum handler delegasi di document/body.
 *  - PkMasterSelect.bersihkan(template) membuang option sisa dari baris
 *    yang di-clone jadi template, supaya baris baru mulai dari "Pilih ...".
 * =====================================================================
 */
(function (global) {
  'use strict';

  var PER_HALAMAN = 50;

  function norm(s) {
    return String(s || '').toLowerCase();
  }

  /** Saring daftar dengan semua kata pada `term`, lalu potong per halaman. */
  function cari(daftar, term, page) {
    var kata = norm(term).split(/\s+/).filter(Boolean);
    var cocok = kata.length
      ? daftar.filter(function (it) {
          var t = norm(it.text);
          return kata.every(function (k) { return t.indexOf(k) !== -1; });
        })
      : daftar;

    var mulai = (page - 1) * PER_HALAMAN;

    return {
      results: cocok.slice(mulai, mulai + PER_HALAMAN),
      pagination: { more: mulai + PER_HALAMAN < cocok.length }
    };
  }

  function sinkronAtribut(el) {
    var $ = global.jQuery;
    var data = $(el).select2('data')[0];
    var opt = el.options[el.selectedIndex];

    if (!data || !opt || !opt.value) return;

    if (data.anggaran !== undefined) opt.setAttribute('data-anggaran', data.anggaran);
    if (data.program_id !== undefined) opt.setAttribute('data-program', data.program_id);
  }

  /**
   * Inisialisasi satu <select data-master="...">.
   * @returns {boolean} true bila ditangani; false bila bukan dropdown master
   *                    (biarkan skrip form memakai Select2 biasa).
   */
  function init(el) {
    var $ = global.jQuery;
    if (!$ || !$.fn || !$.fn.select2) return false;

    var kunci = el.getAttribute('data-master');
    var daftar = kunci && global.pkMaster ? global.pkMaster[kunci] : null;
    if (!daftar) return false;

    var $el = $(el);
    if ($el.hasClass('select2-hidden-accessible')) $el.select2('destroy');

    var placeholder = (el.options[0] && !el.options[0].value) ? el.options[0].text.trim() : 'Pilih...';

    $el.select2({
      width: '100%',
      dropdownParent: $el.parent(),
      placeholder: placeholder,
      minimumInputLength: 0,
      ajax: {
        delay: 0,
        data: function (params) {
          return { term: params.term || '', page: params.page || 1 };
        },
        // Tidak ada jaringan: hasil disusun dari daftar di memori.
        transport: function (params, success) {
          success(cari(daftar, params.data.term, params.data.page));
        },
        processResults: function (data) { return data; },
        cache: false
      }
    });

    $el.off('.pkMaster')
      .on('change.pkMaster select2:select.pkMaster', function () { sinkronAtribut(this); });

    return true;
  }

  /** Buang option sisa pada select master di dalam template baris baru. */
  function bersihkan(root) {
    if (!root) return;
    Array.prototype.forEach.call(root.querySelectorAll('select[data-master]'), function (sel) {
      Array.prototype.slice.call(sel.options).forEach(function (o) {
        if (o.value) o.remove();
        else o.removeAttribute('selected');
      });
      sel.selectedIndex = 0;
    });
  }

  global.PkMasterSelect = { init: init, bersihkan: bersihkan, cari: cari };
})(window);
