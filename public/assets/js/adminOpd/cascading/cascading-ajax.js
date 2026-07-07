/**
 * Orkestrasi AJAX Cascading — Update & Delete Es3/Es4 TANPA reload halaman.
 *
 * - Klik Edit (.casc-edit)  -> muat form (partial) ke modal via AJAX.
 * - Submit form di modal     -> POST via AJAX -> tutup modal -> refresh tabel.
 * - Klik Hapus (.casc-del)   -> konfirmasi -> GET via AJAX -> refresh tabel.
 * - Refresh tabel            -> ambil ulang partial _table & ganti #cascTableWrap.
 *
 * Butuh jQuery + Bootstrap 5 (dimuat via footer.php).
 */
(function () {
    "use strict";

    document.addEventListener("DOMContentLoaded", function () {
        if (!window.jQuery || !window.bootstrap) {
            console.warn("[cascading-ajax] jQuery / Bootstrap tidak tersedia.");
            return;
        }

        var $ = window.jQuery;
        var modalEl = document.getElementById("cascEditModal");
        var wrap = document.getElementById("cascTableWrap");
        if (!modalEl || !wrap) return;

        var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        var $body = $("#cascEditBody");
        var $title = $("#cascEditTitle");

        function toast(message, ok) {
            var el = document.getElementById("cascToast");
            if (!el) {
                alert(message);
                return;
            }
            el.classList.remove("text-bg-success", "text-bg-danger");
            el.classList.add(ok === false ? "text-bg-danger" : "text-bg-success");
            document.getElementById("cascToastBody").textContent = message;
            bootstrap.Toast.getOrCreateInstance(el, { delay: 3000 }).show();
        }

        function refreshTable() {
            var url = wrap.getAttribute("data-table-url");
            var periode = wrap.getAttribute("data-periode") || "";
            return $.get(url, { periode: periode })
                .done(function (html) {
                    wrap.innerHTML = html;
                })
                .fail(function () {
                    toast("Gagal memuat ulang tabel. Silakan refresh halaman.", false);
                });
        }

        // ---------- BUKA MODAL EDIT ----------
        $(document).on("click", ".casc-edit", function (e) {
            e.preventDefault();
            var url = this.getAttribute("data-url") || this.getAttribute("href");
            var title = this.getAttribute("data-title") || "Edit Cascading";
            $title.text(title);
            $body.html('<div class="text-center py-4 text-muted"><i class="fas fa-spinner fa-spin me-1"></i> Memuat…</div>');
            modal.show();
            $.get(url)
                .done(function (html) {
                    $body.html(html);
                })
                .fail(function () {
                    $body.html('<div class="alert alert-danger mb-0">Gagal memuat form. Coba lagi.</div>');
                });
        });

        // ---------- BATAL DI MODAL ----------
        $(document).on("click", "#cascEditBody .casc-cancel", function (e) {
            e.preventDefault();
            modal.hide();
        });

        // ---------- SUBMIT UPDATE (AJAX) ----------
        $(document).on("submit", "#cascEditBody form.casc-form", function (e) {
            e.preventDefault();
            var form = this;
            var $submit = $(form).find('button[type="submit"]');
            $submit.prop("disabled", true).attr("data-orig", $submit.html()).html('<i class="fas fa-spinner fa-spin"></i> Menyimpan…');

            $.ajax({
                url: form.action,
                method: "POST",
                data: $(form).serialize(),
                dataType: "json",
            })
                .done(function (res) {
                    if (res && res.success) {
                        modal.hide();
                        refreshTable();
                        toast((res.message) || "Perubahan berhasil disimpan.", true);
                    } else {
                        toast((res && res.message) || "Gagal menyimpan.", false);
                        $submit.prop("disabled", false).html($submit.attr("data-orig") || "Update");
                    }
                })
                .fail(function () {
                    toast("Gagal menyimpan (kesalahan server).", false);
                    $submit.prop("disabled", false).html($submit.attr("data-orig") || "Update");
                });
        });

        // ---------- DELETE (AJAX) ----------
        $(document).on("click", ".casc-del", function (e) {
            e.preventDefault();
            var url = this.getAttribute("data-url") || this.getAttribute("href");
            var msg = this.getAttribute("data-confirm") || "Yakin menghapus data ini?";
            if (!confirm(msg)) return;

            $.ajax({ url: url, method: "GET", dataType: "json" })
                .done(function (res) {
                    if (res && res.success) {
                        refreshTable();
                        toast((res.message) || "Data berhasil dihapus.", true);
                    } else {
                        toast((res && res.error) || "Gagal menghapus.", false);
                    }
                })
                .fail(function () {
                    toast("Gagal menghapus (kesalahan server).", false);
                });
        });
    });
})();
