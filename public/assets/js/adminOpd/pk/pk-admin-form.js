/**
 * pk-admin-form.js (FINAL)
 * Role : ADMIN
 * Struktur:
 * Sasaran → Indikator → Program → Kegiatan
 * Anggaran diambil dari KEGIATAN (data-anggaran)
 * Select2 SAFE + dynamic clone
 */

document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("pk-form");
  const sasaranContainer = document.querySelector(".sasaran-container");
  if (!sasaranContainer) return;

  /* =========================================================
     UTILITIES (SAMA DENGAN JPT)
  ========================================================= */
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
    // kosongkan input/textareas/select (kecuali hidden id fields jika perlu)
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
    'select[name*="[kegiatan_id]"]',
    function (e) {
      const select = this;
      const selected = select.options[select.selectedIndex];
      if (!selected) return;

      const kegiatanItem = select.closest(".kegiatan-item");
      if (!kegiatanItem) return;

      const anggaranInput = kegiatanItem.querySelector(
        'input[name*="[anggaran]"]',
      );
      if (!anggaranInput) return;

      const anggaran = selected.getAttribute("data-anggaran") || "";
      anggaranInput.value = anggaran
        ? "Rp " + parseInt(anggaran, 10).toLocaleString("id-ID")
        : "";
    },
  );

  // Ambil single templates berdasarkan item pertama di DOM (safe cloning)
  const templateSasaran = qs(".sasaran-item")?.cloneNode(true);
  const templateIndikator = qs(".indikator-item")?.cloneNode(true);
  const templateProgramItem = qs(".program-item")?.cloneNode(true);
  const templateKegiatanItem = qs(".kegiatan-item")?.cloneNode(true);
  const templateSubkegItem = qs(".subkeg-item")?.cloneNode(true);

  // Jika templates ada, bersihkan nilainya supaya jadi "kosong"
  if (templateProgramItem && window.jQuery) {
    qsa("select.select2", templateProgramItem).forEach((sel) => {
      if (jQuery(sel).hasClass("select2-hidden-accessible")) {
        jQuery(sel).select2("destroy");
      }
    });
  }

  if (templateIndikator) {
    const programs = qsa(".program-item", templateIndikator);
    programs.forEach((el, idx) => {
      if (idx > 0) el.remove(); // sisakan 1 program saja
    });
  }
  if (templateSasaran) clearFormControls(templateSasaran);
  if (templateIndikator) clearFormControls(templateIndikator);
  if (templateKegiatanItem) clearFormControls(templateKegiatanItem);
  if (templateSubkegItem) clearFormControls(templateSubkegItem);

  /* =========================================================
     UPDATE FORM NAMES (ADMIN)
  ========================================================= */
  function updateFormNames() {
    qsa(".sasaran-item", sasaranContainer).forEach((sasaran, si) => {
      // ===== SASARAN =====
      const sasaranTxt = sasaran.querySelector("textarea");
      if (sasaranTxt) {
        sasaranTxt.name = `sasaran_pk[${si}][sasaran]`;
      }

      // ===== INDIKATOR =====
      qsa(".indikator-item", sasaran).forEach((indikator, ii) => {
        const inputIndikator = indikator.querySelector(
          'input[type="text"]:not([name*="target"])',
        );
        if (inputIndikator) {
          inputIndikator.name = `sasaran_pk[${si}][indikator][${ii}][indikator]`;
        }

        const inputTarget = indikator.querySelector('input[name*="[target]"]');
        if (inputTarget) {
          inputTarget.name = `sasaran_pk[${si}][indikator][${ii}][target]`;
        }

        const selectSatuan = indikator.querySelector(
          'select[name*="[id_satuan]"]',
        );
        if (selectSatuan) {
          selectSatuan.name = `sasaran_pk[${si}][indikator][${ii}][id_satuan]`;
        }

        const selectJenis = indikator.querySelector(
          'select[name*="[jenis_indikator]"]',
        );
        if (selectJenis) {
          selectJenis.name = `sasaran_pk[${si}][indikator][${ii}][jenis_indikator]`;
        }

        // ===== PROGRAM =====
        qsa(".program-item", indikator).forEach((program, pi) => {
          const programSelect = program.querySelector(
            'select[name*="[program_id]"]',
          );
          if (programSelect) {
            programSelect.name = `sasaran_pk[${si}][indikator][${ii}][program][${pi}][program_id]`;
          }

          // ===== KEGIATAN =====
          qsa(".kegiatan-item", program).forEach((keg, ki) => {
            const kegSelect = keg.querySelector(
              'select[name*="[kegiatan_id]"]',
            );
            if (kegSelect) {
              kegSelect.name = `sasaran_pk[${si}][indikator][${ii}][program][${pi}][kegiatan][${ki}][kegiatan_id]`;
            }

            const anggaranInput = keg.querySelector(
              'input[name*="[anggaran]"]',
            );
            if (anggaranInput) {
              anggaranInput.name = `sasaran_pk[${si}][indikator][${ii}][program][${pi}][kegiatan][${ki}][anggaran]`;
            }
          });
        });
      });
    });
  }

  /* =========================================================
     ADD / REMOVE HANDLER
  ========================================================= */
  document.body.addEventListener("click", (e) => {
    const target = e.target;

    //   ------- ADD SASARAN ---------- */
    if (target.closest(".add-sasaran")) {
      e.preventDefault();
      if (!templateSasaran) return;
      const clone = templateSasaran.cloneNode(true);
      // clean inner lists: keep exactly 1 indikator, each indikator keep 1 program/kegiatan/subkeg
      // remove extra indikator/program if any (template should already be simple)
      clearFormControls(clone);
      // append and update names
      sasaranContainer.appendChild(clone);
      updateFormNames();
      initSelect2(clone);

      return;
    }

    // add-indikator
    if (target.closest(".add-indikator")) {
      e.preventDefault();
      const indikatorSection = target.closest(".indikator-section");
      if (!indikatorSection) return;
      const indikatorContainer = qs(".indikator-container", indikatorSection);
      if (!indikatorContainer) return;
      // choose template: if there's at least one existing indikator, clone that first; else fallback to global template
      const prototype = templateIndikator
        ? templateIndikator.cloneNode(true)
        : null;
      if (!prototype) return;
      clearFormControls(prototype);
      // ensure program/kegiatan/subkeg inside prototype reduced to single item
      // remove extra program-items
      const programItems = qsa(".program-item", prototype);
      programItems.forEach((el, idx) => {
        if (idx > 0) el.remove();
      });
      qsa(".program-item", prototype).forEach((el, idx) => {
        if (idx > 0) el.remove();
      });
      qsa(".kegiatan-item", prototype).forEach((el, idx) => {
        if (idx > 0) el.remove();
      });
      qsa(".subkeg-item", prototype).forEach((el, idx) => {
        if (idx > 0) el.remove();
      });

      indikatorContainer.appendChild(prototype);
      updateFormNames();
      initSelect2(prototype);

      return;
    }

    /* ---------- ADD PROGRAM ---------- */
    if (target.closest(".add-program")) {
      e.preventDefault();

      const indikatorItem = target.closest(".indikator-item");
      if (!indikatorItem) return;

      const programContainer = qs(".program-container", indikatorItem);
      if (!programContainer) return;

      const proto = templateProgramItem
        ? templateProgramItem.cloneNode(true)
        : null;
      if (!proto) return;

      clearFormControls(proto);

      // pastikan hanya 1 kegiatan
      qsa(".kegiatan-item", proto).forEach((el, i) => {
        if (i > 0) el.remove();
      });

      programContainer.appendChild(proto);

      updateFormNames();
      initSelect2(proto);
      return;
    }

    /* ---------- ADD KEGIATAN ---------- */
    if (target.closest(".add-kegiatan")) {
      e.preventDefault();

      const programitem = target.closest(".program-item");
      if (!programitem) return;

      const kegiatancontainer = qs(".kegiatan-container", programitem);
      if (!kegiatancontainer) return;

      const proto = templateKegiatanItem
        ? templateKegiatanItem.cloneNode(true)
        : null;
      if (!proto) return;

      clearFormControls(proto);

      kegiatancontainer.appendChild(proto);

      updateFormNames();
      initSelect2(proto);
      return;
    }

    /* ---------- REMOVE PROGRAM ---------- */
    if (target.closest(".remove-program")) {
      e.preventDefault();

      const program = target.closest(".program-item");
      const container = program?.parentElement;
      if (!container) return;

      if (qsa(".program-item", container).length > 1) {
        program.remove();
        updateFormNames();
      } else {
        clearFormControls(program);
      }
      return;
    }

    /* ---------- REMOVE KEGIATAN ---------- */
    if (target.closest(".remove-kegiatan")) {
      e.preventDefault();

      const kegiatan = target.closest(".kegiatan-item");
      const container = kegiatan?.parentElement;
      if (!container) return;

      if (qsa(".kegiatan-item", container).length > 1) {
        kegiatan.remove();
        updateFormNames();
      } else {
        clearFormControls(kegiatan);
      }
      return;
    }

    // remove-sasaran
    if (target.closest(".remove-sasaran")) {
      e.preventDefault();
      const item = target.closest(".sasaran-item");
      if (!item) return;
      const total = qsa(".sasaran-item", sasaranContainer).length;
      if (total <= 1) {
        alert("Minimal harus ada 1 sasaran!");
        return;
      }
      if (!confirm("Yakin ingin menghapus sasaran ini?")) return;
      item.remove();
      updateFormNames();
      return;
    }

    // remove-indikator
    if (target.closest(".remove-indikator")) {
      e.preventDefault();
      const container = target.closest(".indikator-container");
      const item = target.closest(".indikator-item");
      if (!container || !item) return;
      const total = qsa(".indikator-item", container).length;
      if (total <= 1) {
        alert("Minimal harus ada 1 indikator!");
        return;
      }
      if (!confirm("Yakin ingin menghapus indikator ini?")) return;
      item.remove();
      updateFormNames();
      return;
    }
  });

  /* =========================================================
     INIT
  ========================================================= */
  document.body.addEventListener("change", (ev) => {
    const tgt = ev.target;
    if (tgt.matches('select.kegiatan-select, select[name*="[kegiatan_id]"]')) {
      const kegiatanItem = tgt.closest(".kegiatan-item") || tgt.closest(".row");
      if (!kegiatanItem) return;
      const anggaranField = kegiatanItem.querySelector(
        'input[name*="[anggaran]"]',
      );
      const selected = tgt.options[tgt.selectedIndex];
      if (anggaranField) {
        const ang = selected
          ? selected.getAttribute("data-anggaran") || ""
          : "";
        anggaranField.value = ang ? formatRupiahNumber(ang) : "";
      }
      updateFormNames();
      return;
    }

    // pegawai select -> fill nip
    if (tgt.matches(".pegawai-select")) {
      const targetName = tgt.dataset.target;
      if (!targetName) return;
      const nipInput = document.querySelector(`input[name="${targetName}"]`);
      if (!nipInput) return;
      const selected = tgt.options[tgt.selectedIndex];
      nipInput.value = selected ? selected.dataset.nip || "" : "";
    }

    // kegiatan select change may not need extra handling except naming
    if (tgt.matches(".kegiatan-select, .subkeg-select")) {
      updateFormNames();
    }
  });

  // initialize anggaran values for existing selects on load
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
    updateFormNames();
  });

 if (form) {
  form.addEventListener("submit", (e) => {
    updateFormNames();
  });
}
});
