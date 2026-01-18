/**
 * pk-pengawas-form.js
 * FINAL – ARSITEKTUR IDENTIK DENGAN pk-form.js
 * Struktur:
 * Sasaran → Indikator → Program(hidden) → Kegiatan → Subkegiatan
 * Anggaran diambil dari SUBKEGIATAN
 */

document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("pk-form");
  const sasaranContainer = document.querySelector(".sasaran-container");
  if (!sasaranContainer) return;

  /* =========================================================
     UTILITIES (IDENTIK pk-form.js)
  ========================================================= */
  const qs = (s, c = document) => c.querySelector(s);
  const qsa = (s, c = document) => Array.from(c.querySelectorAll(s));

  function initSelect2(ctx = document) {
    if (!window.jQuery || !jQuery.fn.select2) return;

    jQuery(ctx)
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
      if (el.tagName === "SELECT") el.selectedIndex = 0;
      else if (el.type !== "hidden") el.value = "";
    });
  }

  function formatRupiahNumber(val) {
    if (!val) return "";

    let n = parseInt(val, 10);
    if (isNaN(n)) return '';

    return 'Rp ' + n.toLocaleString('id-ID');
  }

  $(document).on(
    'select2:select',
    'select[name*="[subkegiatan_id]"]',
    function (e) {
      const select = this;
      const selected = select.options[select.selectedIndex];
      if (!selected) return;

      const subkegItem = select.closest('.subkeg-item');
      if (!subkegItem) return;

      const anggaranInput = subkegItem.querySelector(
        'input[name*="[anggaran]"]'
      );
      if (!anggaranInput) return;

      const anggaran = selected.getAttribute('data-anggaran') || '';
      anggaranInput.value = anggaran
        ? 'Rp ' + parseInt(anggaran, 10).toLocaleString('id-ID')
        : '';
    }
  );



  /* =========================================================
     TEMPLATE (SAMA DENGAN pk-form.js)
  ========================================================= */
  const tplSasaran = qs(".sasaran-item")?.cloneNode(true);
  const tplIndikator = qs(".indikator-item")?.cloneNode(true);
  const tplKegiatan = qs(".kegiatan-item")?.cloneNode(true);
  const tplSubkeg = qs(".subkeg-item")?.cloneNode(true);

  if (tplSasaran) clearFormControls(tplSasaran);
  if (tplIndikator) clearFormControls(tplIndikator);
  if (tplKegiatan) clearFormControls(tplKegiatan);
  if (tplSubkeg) clearFormControls(tplSubkeg);

  /* =========================================================
     UPDATE NAME (PENGAWAS)
  ========================================================= */
  function updateFormNames() {
    qsa(".sasaran-item", sasaranContainer).forEach((sasaran, si) => {
      const sasaranTxt = qs("textarea", sasaran);
      if (sasaranTxt) sasaranTxt.name = `sasaran_pk[${si}][sasaran]`;

      qsa(".indikator-item", sasaran).forEach((indikator, ii) => {
        setName(
          indikator,
          ".indikator-input",
          `sasaran_pk[${si}][indikator][${ii}][indikator]`
        );
        setName(
          indikator,
          ".indikator-target",
          `sasaran_pk[${si}][indikator][${ii}][target]`
        );
        setName(
          indikator,
          ".satuan-select",
          `sasaran_pk[${si}][indikator][${ii}][id_satuan]`
        );
        setName(
          indikator,
          ".jenis-indikator-select",
          `sasaran_pk[${si}][indikator][${ii}][jenis_indikator]`
        );

        qsa(".kegiatan-item", indikator).forEach((keg, ki) => {
          const progHidden = qs(".program-id-hidden", keg);
          if (progHidden) {
            progHidden.name = `sasaran_pk[${si}][indikator][${ii}][program][${ki}][program_id]`;
          }

          const kegSelect = qs(".kegiatan-select", keg);
          if (kegSelect) {
            kegSelect.name = `sasaran_pk[${si}][indikator][${ii}][program][${ki}][kegiatan][0][kegiatan_id]`;
          }

          qsa(".subkeg-item", keg).forEach((sub, sk) => {
            const subSel = qs(".subkeg-select", sub);
            const subAng = qs(".anggaran-input", sub);

            if (subSel) {
              subSel.name = `sasaran_pk[${si}][indikator][${ii}][program][${ki}][kegiatan][0][subkegiatan][${sk}][subkegiatan_id]`;
            }
            if (subAng) {
              subAng.name = `sasaran_pk[${si}][indikator][${ii}][program][${ki}][kegiatan][0][subkegiatan][${sk}][anggaran]`;
            }
          });
        });
      });
    });
  }

  function setName(scope, sel, name) {
    const el = qs(sel, scope);
    if (el) el.name = name;
  }

  updateFormNames();
  /* =========================================================
     ADD / REMOVE (IDENTIK POLA pk-form.js)
  ========================================================= */
  document.body.addEventListener("click", (ev) => {
    const t = ev.target;

    // ADD SASARAN
    if (t.closest(".add-sasaran")) {
      ev.preventDefault();
      const clone = tplSasaran.cloneNode(true);
      clearFormControls(clone);
      sasaranContainer.appendChild(clone);
      updateFormNames();
      initSelect2(clone);
      return;
    }

    // ADD INDIKATOR
    if (t.closest(".add-indikator")) {
      ev.preventDefault();
      const container = t
        .closest(".indikator-section")
        ?.querySelector(".indikator-container");
      if (!container) return;
      const clone = tplIndikator.cloneNode(true);
      clearFormControls(clone);
      container.appendChild(clone);
      updateFormNames();
      initSelect2(clone);
      return;
    }

    // ADD KEGIATAN
    if (t.closest(".add-kegiatan")) {
      ev.preventDefault();
      const indikator = t.closest(".indikator-item");
      const container = indikator?.querySelector(".kegiatan-container");
      if (!container) return;
      const clone = tplKegiatan.cloneNode(true);
      clearFormControls(clone);
      container.appendChild(clone);
      updateFormNames();
      initSelect2(clone);
      return;
    }

    // ADD SUBKEG
    if (t.closest(".add-subkeg")) {
      ev.preventDefault();
      const keg = t.closest(".kegiatan-item");
      const container = keg?.querySelector(".subkeg-container");
      if (!container) return;
      const clone = tplSubkeg.cloneNode(true);
      clearFormControls(clone);
      container.appendChild(clone);
      updateFormNames();
      initSelect2(clone);
      return;
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

  /* =========================================================
     AUTOFILL (SUBKEGIATAN → ANGGARAN)
  ========================================================= */

  updateFormNames();
  initSelect2();

  document.body.addEventListener("change", (ev) => {
    const tgt = ev.target;
    if (tgt.matches('select.subkeg-select, select[name*="[subkegiatan_id]"]')) {
      const subkegItem = tgt.closest(".subkeg-item") || tgt.closest(".row");
      if (!subkegItem) return;
      const anggaranField = subkegItem.querySelector(
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
  });

  // initialize anggaran values for existing selects on load
  qsa('.subkeg-select').forEach((sel) => {
    const selected = sel.options[sel.selectedIndex];
    if (selected && selected.dataset && selected.dataset.anggaran) {
      const angInput = sel
        .closest(".subkeg-item")
        ?.querySelector('input[name*="[anggaran]"]');
      if (angInput)
        angInput.value = formatRupiahNumber(selected.dataset.anggaran);
    }
  });

  window.addEventListener("load", () => {
    initSelect2(document);
  });

  if (form) {
    form.addEventListener("submit", updateFormNames);
  }
});
