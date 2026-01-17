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

  function formatRupiah(val) {
    const n = parseInt(val || 0, 10);
    return n ? "Rp " + n.toLocaleString("id-ID") : "";
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

  /* =========================================================
     SELECT2 HANDLER (WAJIB)
  ========================================================= */
  function initSelect2(ctx = document) {
    if (!window.jQuery) return;
    $(ctx)
      .find("select.select2")
      .each(function () {
        if ($(this).hasClass("select2-hidden-accessible")) return;
        $(this).select2({
          width: "100%",
          dropdownParent: $("body"),
        });
      });
  }

  /* =========================================================
     AUTOFILL ANGGARAN (SELECT2 ONLY)
  ========================================================= */
  function bindAnggaranSelect2(ctx = document) {
    if (!window.jQuery) return;

    $(ctx)
      .find(".kegiatan-select")
      .each(function () {
        const $select = $(this);
        $select.off("select2:select");

        $select.on("select2:select", function (e) {
          const opt = e.params.data.element;
          if (!opt) return;

          const anggaran = opt.getAttribute("data-anggaran") || "";
          const kegiatanItem = this.closest(".kegiatan-item");
          if (!kegiatanItem) return;

          const input = kegiatanItem.querySelector(".anggaran-input");
          if (input) input.value = formatRupiah(anggaran);
        });
      });
  }

  /* =========================================================
     UPDATE FORM NAMES (ADMIN)
  ========================================================= */
  function updateFormNames() {
    qsa(".sasaran-item", sasaranContainer).forEach((sasaran, si) => {
      const sasaranText = qs("textarea", sasaran);
      if (sasaranText) {
        sasaranText.name = `sasaran_pk[${si}][sasaran]`;
      }

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

        qsa(".program-item", indikator).forEach((program, pi) => {
          const progSel = qs(".program-select", program);
          if (progSel) {
            progSel.name = `sasaran_pk[${si}][indikator][${ii}][program][${pi}][program_id]`;
          }

          qsa(".kegiatan-item", program).forEach((kegiatan, ki) => {
            const kegSel = qs(".kegiatan-select", kegiatan);
            const kegAng = qs(".anggaran-input", kegiatan);

            if (kegSel) {
              kegSel.name = `sasaran_pk[${si}][indikator][${ii}][program][${pi}][kegiatan][${ki}][kegiatan_id]`;
            }
            if (kegAng) {
              kegAng.name = `sasaran_pk[${si}][indikator][${ii}][program][${pi}][kegiatan][${ki}][anggaran]`;
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
      bindAnggaranSelect2(clone);

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
      bindAnggaranSelect2(prototype);

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
      bindAnggaranSelect2(proto);
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
      bindAnggaranSelect2(proto);
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
  updateFormNames();
  initSelect2();
  bindAnggaranSelect2();

  if (form) {
    form.addEventListener("submit", updateFormNames);
  }
});
