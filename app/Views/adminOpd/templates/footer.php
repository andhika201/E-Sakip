  <!-- app/views/adminOpd/templates/footer.php -->
  <?= $this->include('layout/footer', ['sn' => 'ESAKIP-OPD-2025-001', 'full' => false]); ?>

  <!-- jQuery (required for Select2) -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <!-- Select2 JS -->
  <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

  <!-- Pagination tabel (rowspan-aware, otomatis) -->
  <style>
    .js-pager { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 12px; margin-top: 16px; }
    .js-pager .pg-info { font-size: .82rem; color: #6b7a70; }
    .js-pager .pg-nav { display: inline-flex; gap: 4px; align-items: center; flex-wrap: wrap; }
    .js-pager .pg-btn { min-width: 34px; height: 34px; padding: 0 8px; border: 1px solid #dbe3dd; background: #fff; color: #2f3d35; border-radius: 9px; font-size: .82rem; font-weight: 600; cursor: pointer; transition: all .12s ease; }
    .js-pager .pg-btn:hover:not(:disabled):not(.active) { border-color: #6eab11; color: #00743e; }
    .js-pager .pg-btn.active { background: #00743e; border-color: #00743e; color: #fff; }
    .js-pager .pg-btn:disabled { opacity: .45; cursor: default; }
    .js-pager .pg-dots { padding: 0 4px; color: #6b7a70; }
    .js-pager .pg-size-wrap { display: inline-flex; align-items: center; gap: 6px; font-size: .82rem; color: #6b7a70; }
    .js-pager .pg-size { width: auto; border-radius: 8px; }
  </style>
  <script>
    (function () {
      document.addEventListener('DOMContentLoaded', function () {
        // Auto-wrap tabel agar responsif (scroll horizontal) — selalu jalan
        document.querySelectorAll('main table.table').forEach(function (t) {
          if (t.closest('.table-responsive') || t.closest('.table-responsive-wrapper') || t.closest('.casc-table-wrap')) return;
          var w = document.createElement('div');
          w.className = 'table-responsive';
          t.parentNode.insertBefore(w, t);
          w.appendChild(t);
        });

        if (document.body.hasAttribute('data-no-paginate')) return;
        var DEFAULT_SIZE = 10;
        var SIZES = [10, 25, 50, 0]; // 0 = Semua

        document.querySelectorAll('main table.table').forEach(function (table) {
          if (table.hasAttribute('data-no-paginate')) return;
          if (table.classList.contains('casc-table') || table.closest('.casc-table-wrap')) return; // cascading dikecualikan
          var tbody = table.tBodies[0];
          if (!tbody) return;
          var rows = Array.prototype.slice.call(tbody.rows);
          if (rows.length < 2) return;

          var hasRowspan = rows.some(function (r) {
            return Array.prototype.some.call(r.cells, function (c) { return c.rowSpan > 1; });
          });
          var units = [];
          if (!hasRowspan) {
            units = rows.map(function (r) { return [r]; });
          } else {
            var remaining = 0, current = null;
            rows.forEach(function (r) {
              if (remaining <= 0) {
                current = [r];
                units.push(current);
                var first = r.cells[0];
                remaining = first ? first.rowSpan : 1;
              } else {
                current.push(r);
              }
              remaining--;
            });
          }

          if (units.length <= DEFAULT_SIZE) return;

          var pageSize = DEFAULT_SIZE;
          var page = 1;
          var anchor = table.closest('.casc-table-wrap') || table.closest('.table-responsive') || table.closest('.table-responsive-wrapper') || table;
          var bar = document.createElement('div');
          bar.className = 'js-pager';
          anchor.insertAdjacentElement('afterend', bar);

          function totalPages() {
            var size = pageSize === 0 ? units.length : pageSize;
            return Math.max(1, Math.ceil(units.length / size));
          }

          function render() {
            var size = pageSize === 0 ? units.length : pageSize;
            var tp = totalPages();
            if (page > tp) page = tp;
            var startU = (page - 1) * size;
            var endU = Math.min(startU + size, units.length);

            units.forEach(function (u, i) {
              var vis = (i >= startU && i < endU);
              u.forEach(function (r) { r.style.display = vis ? '' : 'none'; });
            });

            var info = 'Menampilkan ' + (units.length ? (startU + 1) : 0) + '–' + endU + ' dari ' + units.length;
            var btns = '';
            btns += '<button type="button" class="pg-btn" data-pg="prev"' + (page === 1 ? ' disabled' : '') + '>&laquo;</button>';
            var win = 2;
            var from = Math.max(1, page - win), to = Math.min(tp, page + win);
            if (from > 1) { btns += '<button type="button" class="pg-btn" data-pg="1">1</button>'; if (from > 2) btns += '<span class="pg-dots">…</span>'; }
            for (var p = from; p <= to; p++) {
              btns += '<button type="button" class="pg-btn' + (p === page ? ' active' : '') + '" data-pg="' + p + '">' + p + '</button>';
            }
            if (to < tp) { if (to < tp - 1) btns += '<span class="pg-dots">…</span>'; btns += '<button type="button" class="pg-btn" data-pg="' + tp + '">' + tp + '</button>'; }
            btns += '<button type="button" class="pg-btn" data-pg="next"' + (page === tp ? ' disabled' : '') + '>&raquo;</button>';

            var sizeSel = '<select class="pg-size form-select form-select-sm">' +
              SIZES.map(function (s) { var label = s === 0 ? 'Semua' : s; return '<option value="' + s + '"' + (s === pageSize ? ' selected' : '') + '>' + label + '</option>'; }).join('') + '</select>';

            bar.innerHTML =
              '<div class="pg-info">' + info + '</div>' +
              '<div class="pg-nav">' + btns + '</div>' +
              '<div class="pg-size-wrap"><span>Baris:</span>' + sizeSel + '</div>';

            bar.querySelectorAll('.pg-btn').forEach(function (b) {
              b.addEventListener('click', function () {
                var v = b.getAttribute('data-pg');
                if (v === 'prev') page = Math.max(1, page - 1);
                else if (v === 'next') page = Math.min(tp, page + 1);
                else page = parseInt(v, 10);
                render();
              });
            });
            bar.querySelector('.pg-size').addEventListener('change', function () { pageSize = parseInt(this.value, 10); page = 1; render(); });
          }

          render();
        });
      });
    })();
  </script>

  <!-- Global: Select2 untuk SEMUA dropdown FILTER (di form GET / navigasi) -->
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      if (!window.jQuery || !jQuery.fn || !jQuery.fn.select2) return;
      var $ = jQuery;
      $('select.form-select').each(function () {
        var $s = $(this);
        if ($s.hasClass('select2-hidden-accessible')) return;  // sudah select2
        if ($s.hasClass('pg-size')) return;                    // pemilih baris pagination
        if ($s.is('[data-no-select2]')) return;                // opt-out manual
        if ($s.closest('.modal').length) return;               // skip di dalam modal
        var f = $s.closest('form');
        if (f.length && (f.attr('method') || 'get').toLowerCase() === 'post') return; // form input (POST) -> lewati (hindari select AJAX dependen)
        $s.select2({ width: '100%', theme: 'bootstrap-5', dropdownParent: $(document.body) });
      });
    });
  </script>