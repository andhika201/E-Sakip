/**
 * rkt.js
 * Pola PK-style, disesuaikan tambah_rkt.php
 */
document.addEventListener('DOMContentLoaded', () => {

    const qs = (s, c = document) => c.querySelector(s);
    const qsa = (s, c = document) => Array.from(c.querySelectorAll(s));


    function formatRupiahNumber(val) {
        if (!val) return "";

        let n = parseInt(val, 10);
        if (isNaN(n)) return "";

        return "Rp " + n.toLocaleString("id-ID");
    }

    function initSelect2(context = document) {
        if (!window.jQuery || !jQuery.fn.select2) return;

        jQuery(context)
            .find("select.select2")
            .each(function () {
                if (jQuery(this).hasClass("select2-hidden-accessible")) {
                    jQuery(this).select2("destroy");
                }

                jQuery(this).select2({
                    width: "100%",
                    dropdownParent: jQuery(this).parent(),
                });
            });
    }

    function clearFormControls(root) {
        qsa("input, textarea, select", root).forEach((el) => {
            const t = el.tagName.toLowerCase();
            if (t === "select") {
                el.selectedIndex = 0;
            } else if (el.type === "hidden") {
                // biarkan hidden (id) tetap, kecuali ingin direset
                // jika mau reset: el.value = '';
            } else {
                el.value = "";
            }
        });
    }

    $(document).on(
        "select2:select",
        ".subkeg-select",
        function () {
            const selected = this.options[this.selectedIndex];
            if (!selected) return;

            const subkegItem = this.closest(".subkeg-item");
            if (!subkegItem) return;

            const anggaranInput = subkegItem.querySelector(".anggaran-input");
            if (!anggaranInput) return;

            const anggaran = selected.dataset.anggaran || "";
            anggaranInput.value = anggaran
                ? "Rp " + parseInt(anggaran).toLocaleString("id-ID")
                : "";
        }
    );


    /* =======================
     * BASIC GUARD
     * ======================= */
    const templateProgram = document
        .querySelector(".program-item")
        .cloneNode(true);

    const templateKegiatan = document
        .querySelector(".kegiatan-item")
        .cloneNode(true);

    const templateSubkeg = document
        .querySelector(".subkeg-item")
        .cloneNode(true);

    // bersihkan isi dinamis
    templateProgram.querySelectorAll(".kegiatan-item").forEach((e, i) => {
        if (i > 0) e.remove();
    });
    templateProgram.querySelectorAll(".subkeg-item").forEach((e, i) => {
        if (i > 0) e.remove();
    });

    templateKegiatan.querySelectorAll(".subkeg-item").forEach((e, i) => {
        if (i > 0) e.remove();
    });

    clearFormControls(templateProgram);
    clearFormControls(templateKegiatan);
    clearFormControls(templateSubkeg);

    /* =======================
     * UPDATE NAME (PENTING!)
     * ======================= */
    function updateNameRkt() {
        document.querySelectorAll(".program-item").forEach((programItem, pi) => {

            const programSelect = programItem.querySelector(".program-select");
            if (programSelect) {
                programSelect.name = `program[${pi}][program_id]`;
            }

            programItem.querySelectorAll(".kegiatan-item").forEach((kegiatanItem, ki) => {

                const kegiatanSelect = kegiatanItem.querySelector(".kegiatan-select");
                if (kegiatanSelect) {
                    kegiatanSelect.name = `program[${pi}][kegiatan][${ki}][kegiatan_id]`;
                }

                const anggaranKegiatan = kegiatanItem.querySelector(".anggaran-input");
                if (anggaranKegiatan) {
                    anggaranKegiatan.name = `program[${pi}][kegiatan][${ki}][anggaran]`;
                }

                kegiatanItem.querySelectorAll(".subkeg-item").forEach((subItem, si) => {

                    const subSelect = subItem.querySelector(".subkeg-select");
                    if (subSelect) {
                        subSelect.name =
                            `program[${pi}][kegiatan][${ki}][subkegiatan][${si}][subkegiatan_id]`;
                    }

                    const anggaranSub = subItem.querySelector(".anggaran-input");
                    if (anggaranSub) {
                        anggaranSub.name =
                            `program[${pi}][kegiatan][${ki}][subkegiatan][${si}][anggaran]`;
                    }
                });
            });
        });
    }


    updateNameRkt();

    /* =======================
     * DELEGATED CLICK
     * ======================= */
    document.body.addEventListener('click', e => {

        /* ================= ADD PROGRAM ================= */
        if (e.target.closest(".add-program")) {
            e.preventDefault();

            const container = document.querySelector(".program-container");
            const clone = templateProgram.cloneNode(true);

            container.appendChild(clone);
            initSelect2(clone);
            updateNameRkt();
        }


        /* ================= ADD KEGIATAN ================= */
        if (e.target.closest(".add-kegiatan")) {
            e.preventDefault();

            const programItem = e.target.closest(".program-item");
            const container = programItem.querySelector(".kegiatan-container");

            const clone = templateKegiatan.cloneNode(true);
            container.appendChild(clone);

            initSelect2(clone);
            updateNameRkt();
        }

        /* ================= ADD SUBKEGIATAN ================= */
        if (e.target.closest(".add-subkeg")) {
            e.preventDefault();

            const kegiatanItem = e.target.closest(".kegiatan-item");
            const container = kegiatanItem.querySelector(".subkeg-container");

            const clone = templateSubkeg.cloneNode(true);
            container.appendChild(clone);

            initSelect2(clone);
            updateNameRkt();
        }


        /* REMOVE ITEM */
        ['program', 'kegiatan', 'subkegiatan'].forEach(type => {
            if (e.target.closest(`.remove-${type}`)) {
                e.preventDefault();
                const item = e.target.closest(`.${type}-item`);
                const parent = item.parentElement;

                if (parent.children.length > 1) item.remove();
                else clearControls(item);

                updateNameRkt();
            }
        });

    });
    updateNameRkt();
    initSelect2();

    /* =======================
 * CHANGE HANDLER
 * ======================= */


    qsa(".kegiatan-select").forEach((sel) => {
        const selected = sel.options[sel.selectedIndex];
        if (selected && selected.dataset && selected.dataset.anggaran) {
            const angInput = sel
                .closest(".kegiatan-item")
                ?.querySelector('input[name*="[anggaran]"]');
            if (angInput)
                angInput.value = formatRupiahNumber(selected.dataset.anggaran);
        }
    });

    window.addEventListener("load", () => {
        initSelect2(document);
        updateNameRkt();
    });

    if (form) {
        form.addEventListener("submit", (e) => {
            updateNameRkt();
        });
    }
});