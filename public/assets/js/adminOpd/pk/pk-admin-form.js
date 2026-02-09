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

      const kegItem = select.closest(".kegiatan-item");
      if (!kegItem) return;

      const anggaranInput = kegItem.querySelector(
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

  if (templateSasaran) {
    templateSasaran.querySelectorAll("textarea, input").forEach(el => el.value = "");
  }
  if (templateIndikator) {
    templateIndikator.querySelectorAll("input, select").forEach(el => {
      if (el.tagName === "SELECT") el.selectedIndex = 0;
      else el.value = "";
    });
  }
  if (templateKegiatanItem) {
    templateKegiatanItem.querySelectorAll("input, select").forEach(el => {
      if (el.tagName === "SELECT") el.selectedIndex = 0;
      else el.value = "";
    });
  }
  if (templateProgramItem) {
    templateProgramItem.querySelectorAll("input, select").forEach(el => {
      if (el.tagName === "SELECT") el.selectedIndex = 0;
      else el.value = "";
    });
  }

  if (templateSasaran) clearFormControls(templateSasaran);
  if (templateIndikator) clearFormControls(templateIndikator);
  if (templateKegiatanItem) clearFormControls(templateKegiatanItem);
  if (templateProgramItem) clearFormControls(templateProgramItem);

  /* =========================================================
     UPDATE FORM NAMES (ADMIN)
  ========================================================= */
  function updateFormNames() {
    qsa(".sasaran-item", sasaranContainer).forEach((sasaran, si) => {
      // ===== SASARAN =====
      sasaran
        .querySelector("textarea")
        ?.setAttribute("name", `sasaran_pk[${si}][sasaran]`);

      // ===== INDIKATOR =====
      qsa(".indikator-item", sasaran).forEach((indikator, ii) => {
        indikator
          .querySelector(".indikator-input")
          ?.setAttribute(
            "name",
            `sasaran_pk[${si}][indikator][${ii}][indikator]`
          );

        indikator
          .querySelector(".indikator-target")
          ?.setAttribute(
            "name",
            `sasaran_pk[${si}][indikator][${ii}][target]`
          );

        indikator
          .querySelector(".satuan-select")
          ?.setAttribute(
            "name",
            `sasaran_pk[${si}][indikator][${ii}][id_satuan]`
          );

        indikator
          .querySelector(".jenis-indikator-select")
          ?.setAttribute(
            "name",
            `sasaran_pk[${si}][indikator][${ii}][jenis_indikator]`
          );

        // ===== PROGRAM =====
        qsa(".program-item", indikator).forEach((program, pi) => {
          program
            .querySelector(".program-select")
            ?.setAttribute(
              "name",
              `sasaran_pk[${si}][indikator][${ii}][program][${pi}][program_id]`
            );

          // ===== KEGIATAN =====
          qsa(".kegiatan-item", program).forEach((kegiatan, ki) => {
            kegiatan
              .querySelector(".kegiatan-select")
              ?.setAttribute(
                "name",
                `sasaran_pk[${si}][indikator][${ii}][program][${pi}][kegiatan][${ki}][kegiatan_id]`
              );

            kegiatan
              .querySelector(".anggaran-input")
              ?.setAttribute(
                "name",
                `sasaran_pk[${si}][indikator][${ii}][program][${pi}][kegiatan][${ki}][anggaran]`
              );
          });
        });
      });
    });
  }


  updateFormNames();
  /* =========================================================
     ADD / REMOVE HANDLER
  ========================================================= */
  document.body.addEventListener("click", (e) => {
    const t = e.target;

    /* =======================
       ADD SASARAN
    ======================= */
    if (t.closest(".add-sasaran")) {
      e.preventDefault();

      const sasaranClone = templateSasaran.cloneNode(true);
      const indikatorContainer =
        sasaranClone.querySelector(".indikator-container");

      indikatorContainer.innerHTML = "";

      const indikatorClone = templateIndikator.cloneNode(true);
      const programContainer =
        indikatorClone.querySelector(".program-container");

      programContainer.innerHTML = "";

      const programClone = templateProgramItem.cloneNode(true);
      const kegiatanContainer =
        programClone.querySelector(".kegiatan-container");

      kegiatanContainer.innerHTML = "";

      const kegiatanClone = templateKegiatanItem.cloneNode(true);
      kegiatanContainer.appendChild(kegiatanClone);

      programContainer.appendChild(programClone);
      indikatorContainer.appendChild(indikatorClone);
      sasaranContainer.appendChild(sasaranClone);

      updateFormNames();
      initSelect2(sasaranClone);
    }

    /* =======================
       ADD INDIKATOR
    ======================= */
    if (t.closest(".add-indikator")) {
      e.preventDefault();

      const indikatorContainer = t
        .closest(".indikator-section")
        ?.querySelector(".indikator-container");
      if (!indikatorContainer) return;

      const indikatorClone = templateIndikator.cloneNode(true);
      const programContainer =
        indikatorClone.querySelector(".program-container");

      programContainer.innerHTML = "";

      const programClone = templateProgramItem.cloneNode(true);
      const kegiatanContainer =
        programClone.querySelector(".kegiatan-container");

      kegiatanContainer.innerHTML = "";

      const kegiatanClone = templateKegiatanItem.cloneNode(true);
      kegiatanContainer.appendChild(kegiatanClone);

      programContainer.appendChild(programClone);
      indikatorContainer.appendChild(indikatorClone);

      updateFormNames();
      initSelect2(indikatorClone);
    }

    /* =======================
       ADD PROGRAM
    ======================= */
    if (t.closest(".add-program")) {
      e.preventDefault();

      const indikator = t.closest(".indikator-item");
      const container = indikator?.querySelector(".program-container");
      if (!container) return;

      const programClone = templateProgramItem.cloneNode(true);
      const kegiatanContainer =
        programClone.querySelector(".kegiatan-container");

      kegiatanContainer.innerHTML = "";

      const kegiatanClone = templateKegiatanItem.cloneNode(true);
      kegiatanContainer.appendChild(kegiatanClone);

      container.appendChild(programClone);

      updateFormNames();
      initSelect2(programClone);
    }

    /* =======================
       ADD KEGIATAN
    ======================= */
    if (t.closest(".add-kegiatan")) {
      e.preventDefault();

      const program = t.closest(".program-item");
      const container = program?.querySelector(".kegiatan-container");
      if (!container) return;

      const kegiatanClone = templateKegiatanItem.cloneNode(true);
      container.appendChild(kegiatanClone);

      updateFormNames();
      initSelect2(kegiatanClone);
    }

    /* =======================
       REMOVE GENERIC
    ======================= */
    ["sasaran", "indikator", "program", "kegiatan"].forEach((type) => {
      if (t.closest(`.remove-${type}`)) {
        e.preventDefault();

        const item = t.closest(`.${type}-item`);
        const parent = item?.parentElement;
        if (!item || !parent) return;

        if (parent.children.length > 1) {
          item.remove();
        } else {
          clearFormControls(item);
        }

        updateFormNames();
      }
    });
  });


  updateFormNames();
  initSelect2();
  /* =========================================================
     INIT
  ========================================================= */
  document.body.addEventListener("change", (ev) => {
    const tgt = ev.target;


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
