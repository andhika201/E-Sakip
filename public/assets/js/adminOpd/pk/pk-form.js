/**
 * pk-form.js
 * Versi yang stabil — sesuai HTML yang kamu kirim.
 */
document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("pk-form");
  const sasaranContainer = document.querySelector(".sasaran-container");
  if (!sasaranContainer) return; // tidak ada form, hentikan

  // --------- Utilities ----------
  const qs = (sel, ctx = document) => ctx.querySelector(sel);
  const qsa = (sel, ctx = document) => Array.from(ctx.querySelectorAll(sel));

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
          dropdownParent: jQuery("body"),
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

  function formatRupiahNumber(val) {
    if (!val) return "";

    let n = parseInt(val, 10);
    if (isNaN(n)) return "";

    return "Rp " + n.toLocaleString("id-ID");
  }
  // === JPT + Select2 handler (TAMBAHAN, BUKAN PENGGANTI) ===
  $(document).on(
    "select2:select",
    'select[name*="[program_id]"]',
    function (e) {
      const select = this;
      const selected = select.options[select.selectedIndex];
      if (!selected) return;

      const programItem = select.closest(".program-item");
      if (!programItem) return;

      const anggaranInput = programItem.querySelector(
        'input[name*="[anggaran]"]'
      );
      if (!anggaranInput) return;

      const anggaran = selected.getAttribute("data-anggaran") || "";
      anggaranInput.value = anggaran
        ? "Rp " + parseInt(anggaran, 10).toLocaleString("id-ID")
        : "";
    }
  );

  // Ambil single templates berdasarkan item pertama di DOM (safe cloning)
  const templateSasaran = document
    .querySelector(".sasaran-item")
    ?.cloneNode(true);
  const templateIndikator = document
    .querySelector(".indikator-item")
    ?.cloneNode(true);
  const templateProgramItem = document
    .querySelector(".program-item")
    ?.cloneNode(true);
  const templateKegiatanItem = document
    .querySelector(".kegiatan-item")
    ?.cloneNode(true);
  const templateSubkegItem = document
    .querySelector(".subkeg-item")
    ?.cloneNode(true);

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

  // --------- Naming helper ----------
  function updateFormNames() {
    // untuk tiap sasaran
    qsa(".sasaran-item", sasaranContainer).forEach((sasaranItem, si) => {
      const textarea = qs("textarea", sasaranItem);
      if (textarea) textarea.name = `sasaran_pk[${si}][sasaran]`;

      // indikator
      qsa(".indikator-item", sasaranItem).forEach((indikatorItem, ii) => {
        const inputInd = indikatorItem.querySelector(
          'input[type="text"][name*="[indikator]"], input[name*="[indikator]"]'
        );
        const inputTarget = indikatorItem.querySelector(
          'input[name*="[target]"]'
        );
        const selectSatuan = indikatorItem.querySelector(
          'select[name*="[id_satuan]"]'
        );
        const selectJenis = indikatorItem.querySelector(
          'select[name*="[jenis_indikator]"]'
        );

        if (inputInd)
          inputInd.name = `sasaran_pk[${si}][indikator][${ii}][indikator]`;
        if (inputTarget)
          inputTarget.name = `sasaran_pk[${si}][indikator][${ii}][target]`;
        if (selectSatuan)
          selectSatuan.name = `sasaran_pk[${si}][indikator][${ii}][id_satuan]`;
        if (selectJenis)
          selectJenis.name = `sasaran_pk[${si}][indikator][${ii}][jenis_indikator]`;

        // program (could be program container or kegiatan depending jenis)
        const programItems = qsa(".program-item", indikatorItem);
        programItems.forEach((prog, pi) => {
          // program select (if exists)
          const progSelect = prog.querySelector(
            'select.program-select, select[name*="[program]"], select[name*="[program_id]"]'
          );
          const anggaranInput = prog.querySelector('input[name*="[anggaran]"]');

          if (progSelect) {
            progSelect.name = `sasaran_pk[${si}][indikator][${ii}][program][${pi}][program_id]`;
          }
          if (anggaranInput) {
            anggaranInput.name = `sasaran_pk[${si}][indikator][${ii}][program][${pi}][anggaran]`;
          }

          // kegiatan under this program (administrator) or for pengawas structure
          const kegiatanItems = qsa(".kegiatan-item", prog);
          kegiatanItems.forEach((keg, ki) => {
            const kegSelect = keg.querySelector(
              'select.kegiatan-select, select[name*="[kegiatan_id]"]'
            );
            if (kegSelect) {
              kegSelect.name = `sasaran_pk[${si}][indikator][${ii}][program][${pi}][kegiatan][${ki}][kegiatan_id]`;
            }

            // subkeg under this kegiatan
            const subkegs = qsa(".subkeg-item", keg);
            subkegs.forEach((sub, sk) => {
              const subSel = sub.querySelector(
                'select.subkeg-select, select[name*="[subkegiatan_id]"]'
              );
              if (subSel) {
                subSel.name = `sasaran_pk[${si}][indikator][${ii}][program][${pi}][kegiatan][${ki}][subkegiatan][${sk}][subkegiatan_id]`;
              }
            });
          });

          // handle case pengawas where kegiatan are on indikator level (some HTML uses program->kegiatan placeholder)
          const kegiatanItemsOnIndicator = qsa(
            ".kegiatan-item",
            indikatorItem
          ).filter((node) => node.closest(".program-item") === null);
          kegiatanItemsOnIndicator.forEach((keg, ki) => {
            const kegSelect = keg.querySelector(
              'select.kegiatan-select, select[name*="[kegiatan_id]"]'
            );
            if (kegSelect) {
              // For pengawas, use program index 0
              kegSelect.name = `sasaran_pk[${si}][indikator][${ii}][program][0][kegiatan][${ki}][kegiatan_id]`;
            }
            qsa(".subkeg-item", keg).forEach((sub, sk) => {
              const subSel = sub.querySelector(
                'select.subkeg-select, select[name*="[subkegiatan_id]"]'
              );
              if (subSel) {
                subSel.name = `sasaran_pk[${si}][indikator][${ii}][program][0][kegiatan][${ki}][subkegiatan][${sk}][subkegiatan_id]`;
              }
            });
          });
        });
      });
    });
  }

  // Ensure names correct on load
  updateFormNames();

  // --------- Add handlers (delegated) ----------
  document.body.addEventListener("click", (ev) => {
    const target = ev.target;
    // add-sasaran
    if (target.closest(".add-sasaran")) {
      ev.preventDefault();
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
      ev.preventDefault();
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

    // add-program (works for jpt & admin): add program-item to closest indikator
    if (target.closest(".add-program")) {
      ev.preventDefault();
      const indikatorItem = target.closest(".indikator-item");
      if (!indikatorItem) return;
      const programContainer =
        qs(".program-container", indikatorItem) ||
        qs(".program-container", indikatorItem.parentElement);
      if (!programContainer) return;
      // choose first existing program-item as prototype or global templateProgramItem
      const proto = templateProgramItem
        ? templateProgramItem.cloneNode(true)
        : null;
      if (!proto) return;
      clearFormControls(proto);

      // If program contains nested kelompok kegiatan, ensure one kegiatan-item present
      // remove extra kegiatan/subkeg inside prototype
      qsa(".kegiatan-item", proto).forEach((el, idx) => {
        if (idx > 0) el.remove();
      });
      qsa(".subkeg-item", proto).forEach((el, idx) => {
        if (idx > 0) el.remove();
      });

      programContainer.appendChild(proto);
      updateFormNames();
      initSelect2(proto);
      return;
    }

    // add-kegiatan (admin/pengawas)
    if (target.closest(".add-kegiatan")) {
      ev.preventDefault();
      // add-kegiatan button may be global or inside program. find the proper container
      const wrapper =
        target.closest(".program-item") ||
        target.closest(".sasaran-item") ||
        target.closest(".indikator-item");
      let kegiatanContainer = wrapper
        ? wrapper.querySelector(".kegiatan-container")
        : null;
      if (!kegiatanContainer) {
        // fallback: find nearest indikator-item then its kegiatan-container
        const indik = target.closest(".indikator-item");
        kegiatanContainer = indik
          ? indik.querySelector(".kegiatan-container")
          : null;
      }
      if (!kegiatanContainer) return;

      const proto = kegiatanContainer.querySelector(".kegiatan-item")
        ? kegiatanContainer.querySelector(".kegiatan-item").cloneNode(true)
        : templateKegiatanItem
        ? templateKegiatanItem.cloneNode(true)
        : null;
      if (!proto) return;
      clearFormControls(proto);
      // remove extra subkeg beyond one
      qsa(".subkeg-item", proto).forEach((el, idx) => {
        if (idx > 0) el.remove();
      });

      kegiatanContainer.appendChild(proto);
      updateFormNames();
      return;
    }

    // add-subkeg
    if (target.closest(".add-subkeg")) {
      ev.preventDefault();
      const kegiatanItem = target.closest(".kegiatan-item");
      if (!kegiatanItem) return;
      const subContainer =
        kegiatanItem.querySelector(".subkeg-container") ||
        kegiatanItem.querySelector(".subkeg-container") ||
        kegiatanItem;
      if (!subContainer) return;
      const proto = subContainer.querySelector(".subkeg-item")
        ? subContainer.querySelector(".subkeg-item").cloneNode(true)
        : templateSubkegItem
        ? templateSubkegItem.cloneNode(true)
        : null;
      if (!proto) return;
      clearFormControls(proto);
      subContainer.appendChild(proto);
      updateFormNames();
      return;
    }

    // remove-sasaran
    if (target.closest(".remove-sasaran")) {
      ev.preventDefault();
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
      ev.preventDefault();
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

    // remove-program
    if (target.closest(".remove-program")) {
      ev.preventDefault();
      const programItem = target.closest(".program-item");
      if (!programItem) return;
      const container = programItem.closest(".program-container");
      if (!container) return;
      const total = qsa(".program-item", container).length;
      if (total > 1) {
        if (!confirm("Yakin ingin menghapus program ini?")) return;
        programItem.remove();
      } else {
        // kosongkan saja nilai
        clearFormControls(programItem);
      }
      updateFormNames();
      return;
    }

    // remove-kegiatan
    if (target.closest(".remove-kegiatan")) {
      ev.preventDefault();
      const keg = target.closest(".kegiatan-item");
      if (!keg) return;
      const container = keg.closest(".kegiatan-container");
      if (!container) return;
      const total = qsa(".kegiatan-item", container).length;
      if (total > 1) {
        if (!confirm("Yakin ingin menghapus kegiatan ini?")) return;
        keg.remove();
      } else {
        clearFormControls(keg);
      }
      updateFormNames();
      return;
    }

    // remove-subkeg
    if (target.closest(".remove-subkeg")) {
      ev.preventDefault();
      const sub = target.closest(".subkeg-item");
      if (!sub) return;
      const container = sub.closest(".subkeg-container") || sub.parentElement;
      if (!container) return;
      const total = qsa(".subkeg-item", container).length;
      if (total > 1) {
        if (!confirm("Yakin ingin menghapus sub kegiatan ini?")) return;
        sub.remove();
      } else {
        clearFormControls(sub);
      }
      updateFormNames();
      return;
    }
  }); // end body click

  // --------- Delegated change handlers ----------
  // program-select change -> update anggaran
  document.body.addEventListener("change", (ev) => {
    const tgt = ev.target;
    if (tgt.matches('select.program-select, select[name*="[program_id]"]')) {
      const programItem = tgt.closest(".program-item") || tgt.closest(".row");
      if (!programItem) return;
      const anggaranField = programItem.querySelector(
        'input[name*="[anggaran]"]'
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
  qsa(".program-select").forEach((sel) => {
    const selected = sel.options[sel.selectedIndex];
    if (selected && selected.dataset && selected.dataset.anggaran) {
      const angInput = sel
        .closest(".program-item")
        ?.querySelector('input[name*="[anggaran]"]');
      if (angInput)
        angInput.value = formatRupiahNumber(selected.dataset.anggaran);
    }
  });

  // Ensure names are updated before submit
  if (form) {
    form.addEventListener("submit", (e) => {
      updateFormNames();
      // no preventDefault -> let submit proceed
    });
  }
});
