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

  if (templateSasaran) clearFormControls(templateSasaran);
  if (templateIndikator) clearFormControls(templateIndikator);
  if (templateProgramItem) clearFormControls(templateProgramItem);

  // --------- Naming helper ----------
  function updateFormNames() {
    qsa(".sasaran-item", sasaranContainer).forEach((sasaranItem, si) => {
      // SASARAN
      const sasaranTxt = sasaranItem.querySelector("textarea");
      if (sasaranTxt) {
        sasaranTxt.name = `sasaran_pk[${si}][sasaran]`;
      }

      // INDIKATOR
      qsa(".indikator-item", sasaranItem).forEach((indikatorItem, ii) => {
        indikatorItem
          .querySelector(".indikator-input")
          ?.setAttribute(
            "name",
            `sasaran_pk[${si}][indikator][${ii}][indikator]`,
          );

        indikatorItem
          .querySelector(".indikator-target")
          ?.setAttribute("name", `sasaran_pk[${si}][indikator][${ii}][target]`);

        indikatorItem
          .querySelector(".satuan-select")
          ?.setAttribute(
            "name",
            `sasaran_pk[${si}][indikator][${ii}][id_satuan]`,
          );

        indikatorItem
          .querySelector(".jenis-indikator-select")
          ?.setAttribute(
            "name",
            `sasaran_pk[${si}][indikator][${ii}][jenis_indikator]`,
          );

        // PROGRAM (JPT: 1 indikator → banyak program)
        qsa(".program-item", indikatorItem).forEach((programItem, pi) => {
          const programSelect = programItem.querySelector(".program-select");
          const anggaranInput = programItem.querySelector(".anggaran-input");

          if (programSelect) {
            programSelect.name = `sasaran_pk[${si}][indikator][${ii}][program][${pi}][program_id]`;
          }

          if (anggaranInput) {
            anggaranInput.name = `sasaran_pk[${si}][indikator][${ii}][program][${pi}][anggaran]`;
          }
        });
      });
    });
  }

  // Ensure names correct on load
  updateFormNames();

  // --------- Add handlers (delegated) ----------
  document.body.addEventListener("click", (ev) => {
    const t = ev.target;

    // ADD SASARAN
    if (t.closest(".add-sasaran")) {
      ev.preventDefault();

      const clone = templateSasaran.cloneNode(true);
      clearFormControls(clone);

      // paksa hanya 1 indikator awal
      const indikatorContainer = clone.querySelector(".indikator-container");
      indikatorContainer.innerHTML = "";

      const indikatorClone = templateIndikator.cloneNode(true);
      clearFormControls(indikatorClone);

      // paksa 1 program awal
      const programContainer =
        indikatorClone.querySelector(".program-container");
      programContainer.innerHTML = "";

      const programClone = templateProgramItem.cloneNode(true);
      clearFormControls(programClone);
      programContainer.appendChild(programClone);

      indikatorContainer.appendChild(indikatorClone);
      sasaranContainer.appendChild(clone);

      updateFormNames();
      initSelect2(clone);
    }

    // ADD INDIKATOR
    if (t.closest(".add-indikator")) {
      ev.preventDefault();

      const sasaranItem = t.closest(".sasaran-item");
      const container = sasaranItem.querySelector(".indikator-container");

      const indikatorClone = templateIndikator.cloneNode(true);
      clearFormControls(indikatorClone);

      const programContainer =
        indikatorClone.querySelector(".program-container");
      programContainer.innerHTML = "";

      const programClone = templateProgramItem.cloneNode(true);
      clearFormControls(programClone);
      programContainer.appendChild(programClone);

      container.appendChild(indikatorClone);

      updateFormNames();
      initSelect2(indikatorClone);
    }

    if (t.closest(".add-program")) {
      ev.preventDefault();

      const indikatorItem = t.closest(".indikator-item");
      const container = indikatorItem.querySelector(".program-container");

      const clone = templateProgramItem.cloneNode(true);
      clearFormControls(clone);

      container.appendChild(clone);

      updateFormNames();
      initSelect2(clone);
    }

    // REMOVE GENERIC
    ["sasaran", "indikator", "kegiatan", "subkeg"].forEach((type) => {
      if (t.closest(`.remove-${type}`)) {
        ev.preventDefault();
        const item = t.closest(`.${type}-item`);
        const parent = item?.parentElement;
        if (!item || !parent) return;
        if (parent.children.length > 1) item.remove();
        else clearFormControls(item);
        updateFormNames();
      }
    });
  });

  updateFormNames();
  initSelect2();
  // --------- Delegated change handlers ----------
  // program-select change -> update anggaran

  qsa(".kegiatan-select").forEach((select) => {
    const selected = select.options[select.selectedIndex];
    if (!selected) return;

    const programId = selected.dataset.program || "";
    const wrapper = select.closest(".kegiatan-item");
    const hidden = wrapper?.querySelector(".program-id-hidden");

    if (hidden && programId) {
      hidden.value = programId;
      console.debug("[PK] program_id init:", {
        kegiatan_id: select.value,
        program_id: programId,
      });
    }
  });

  document.body.addEventListener("change", (ev) => {
    const tgt = ev.target;
    if (tgt.matches('select.program-select, select[name*="[program_id]"]')) {
      const programItem = tgt.closest(".program-item") || tgt.closest(".row");
      if (!programItem) return;
      const anggaranField = programItem.querySelector(
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

    // kegiatan select change may not need extra handling except naming n
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

  window.addEventListener("load", () => {
    initSelect2(document);
  });
  // Ensure names are updated before submit
  if (form) {
    form.addEventListener("submit", (e) => {
      updateFormNames();
      // no preventDefault -> let submit proceed
    });
  }
});