
document.addEventListener("DOMContentLoaded", () => {

  const form = document.getElementById("pk-form");

  const sasaranContainer = document.querySelector(".sasaran-container");
  if (!sasaranContainer) return;

  const isEdit =
    sasaranContainer.querySelectorAll(".sasaran-item").length > 0 &&
    sasaranContainer.querySelector("textarea")?.value?.trim() !== "";

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
    if (isNaN(n)) return "";

    return "Rp " + n.toLocaleString("id-ID");
  }

  $(document).on(
    "select2:select",
    'select[name*="[subkegiatan_id]"]',
    function (e) {
      const select = this;
      const selected = select.options[select.selectedIndex];
      if (!selected) return;

      const subkegItem = select.closest(".subkeg-item");
      if (!subkegItem) return;

      const anggaranInput = subkegItem.querySelector(
        'input[name*="[anggaran]"]',
      );
      if (!anggaranInput) return;

      const anggaran = selected.getAttribute("data-anggaran") || "";
      anggaranInput.value = anggaran
        ? "Rp " + parseInt(anggaran, 10).toLocaleString("id-ID")
        : "";
    },
  );

  /* =========================================================
     TEMPLATE (SAMA DENGAN pk-form.js)
  ========================================================= */
  const tplSasaran = qs(".sasaran-item")?.cloneNode(true);
  const tplIndikator = qs(".indikator-item")?.cloneNode(true);
  const tplKegiatan = qs(".kegiatan-item")?.cloneNode(true);
  const tplSubkeg = qs(".subkeg-item")?.cloneNode(true);

  // 🔥 PENTING: template harus BERSIH
  if (tplSasaran) {
    tplSasaran.querySelectorAll("textarea, input").forEach(el => el.value = "");
  }
  if (tplIndikator) {
    tplIndikator.querySelectorAll("input, select").forEach(el => {
      if (el.tagName === "SELECT") el.selectedIndex = 0;
      else el.value = "";
    });
  }
  if (tplKegiatan) {
    tplKegiatan.querySelectorAll("input, select").forEach(el => {
      if (el.tagName === "SELECT") el.selectedIndex = 0;
      else el.value = "";
    });
  }
  if (tplSubkeg) {
    tplSubkeg.querySelectorAll("input, select").forEach(el => {
      if (el.tagName === "SELECT") el.selectedIndex = 0;
      else el.value = "";
    });
  }


  if (!isEdit) {
    if (tplSasaran) clearFormControls(tplSasaran);
    if (tplIndikator) clearFormControls(tplIndikator);
    if (tplKegiatan) clearFormControls(tplKegiatan);
    if (tplSubkeg) clearFormControls(tplSubkeg);
  }

  /* =========================================================
     UPDATE NAME (PENGAWAS)
  ========================================================= */
  function updateFormNames() {
    qsa(".sasaran-item", sasaranContainer).forEach((sasaran, si) => {
      // SASARAN
      const sasaranTxt = sasaran.querySelector("textarea");
      if (sasaranTxt) {
        sasaranTxt.name = `sasaran_pk[${si}][sasaran]`;
      }

      qsa(".indikator-item", sasaran).forEach((indikator, ii) => {
        // INDIKATOR
        
        indikator
          .querySelector(".indikator-input")
          ?.setAttribute(
            "name",
            `sasaran_pk[${si}][indikator][${ii}][indikator]`,
          );

        indikator
          .querySelector(".indikator-target")
          ?.setAttribute("name", `sasaran_pk[${si}][indikator][${ii}][target]`);

        indikator
          .querySelector(".satuan-select")
          ?.setAttribute(
            "name",
            `sasaran_pk[${si}][indikator][${ii}][id_satuan]`,
          );

        indikator
          .querySelector(".jenis-indikator-select")
          ?.setAttribute(
            "name",
            `sasaran_pk[${si}][indikator][${ii}][jenis_indikator]`,
          );
          

        // PROGRAM (IMPLICIT, DARI KEGIATAN)
        qsa(".kegiatan-item", indikator).forEach((kegiatan, ki) => {
          const programHidden = kegiatan.querySelector(".program-id-hidden");
          const kegiatanSelect = kegiatan.querySelector(".kegiatan-select");

          if (programHidden) {
            programHidden.name = `sasaran_pk[${si}][indikator][${ii}][program][${ki}][program_id]`;
          }

          if (kegiatanSelect) {
            kegiatanSelect.name = `sasaran_pk[${si}][indikator][${ii}][program][${ki}][kegiatan][0][kegiatan_id]`;
          }

          // SUBKEGIATAN
          qsa(".subkeg-item", kegiatan).forEach((sub, sk) => {
            sub
              .querySelector(".subkeg-select")
              ?.setAttribute(
                "name",
                `sasaran_pk[${si}][indikator][${ii}][program][${ki}][kegiatan][0][subkegiatan][${sk}][subkegiatan_id]`,
              );

            sub
              .querySelector(".anggaran-input")
              ?.setAttribute(
                "name",
                `sasaran_pk[${si}][indikator][${ii}][program][${ki}][kegiatan][0][subkegiatan][${sk}][anggaran]`,
              );
          });
        });
      });
    });
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

      const sasaranClone = tplSasaran.cloneNode(true);
      const indikatorContainer = sasaranClone.querySelector(".indikator-container");
      if (indikatorContainer && tplIndikator) {
        indikatorContainer.innerHTML = "";

        const indikatorClone = tplIndikator.cloneNode(true);

        const kegiatanContainer = indikatorClone.querySelector(
          ".kegiatan-container",
        );
        if (kegiatanContainer && tplKegiatan) {
          kegiatanContainer.innerHTML = "";

          const kegClone = tplKegiatan.cloneNode(true);

          const subContainer = kegClone.querySelector(".subkeg-container");
          if (subContainer && tplSubkeg) {
            subContainer.innerHTML = "";
            const subClone = tplSubkeg.cloneNode(true);
            subContainer.appendChild(subClone);
          }

          kegiatanContainer.appendChild(kegClone);
        }

        indikatorContainer.appendChild(indikatorClone);
      }

      sasaranContainer.appendChild(sasaranClone);
      updateFormNames();
      initSelect2(sasaranClone);
    }

    // ADD INDIKATOR
    if (t.closest(".add-indikator")) {
      ev.preventDefault();

      const indikatorContainer = t
        .closest(".indikator-section")
        ?.querySelector(".indikator-container");
      if (!indikatorContainer) return;

      // ===== CLONE INDIKATOR =====
      const indikatorClone = tplIndikator.cloneNode(true);

      // ===== PAKSA ADA 1 KEGIATAN =====
      const kegiatanContainer = indikatorClone.querySelector(
        ".kegiatan-container",
      );
      if (kegiatanContainer && tplKegiatan) {
        kegiatanContainer.innerHTML = "";

        const kegClone = tplKegiatan.cloneNode(true);

        // ===== PAKSA ADA 1 SUBKEGIATAN =====
        const subContainer = kegClone.querySelector(".subkeg-container");
        if (subContainer && tplSubkeg) {
          subContainer.innerHTML = "";
          const subClone = tplSubkeg.cloneNode(true);
          subContainer.appendChild(subClone);
        }

        kegiatanContainer.appendChild(kegClone);
      }

      indikatorContainer.appendChild(indikatorClone);
      updateFormNames();
      initSelect2(indikatorClone);
    }

    // ADD KEGIATAN
    if (t.closest(".add-kegiatan")) {
      ev.preventDefault();
      const indikator = t.closest(".indikator-item");
      const container = indikator?.querySelector(".kegiatan-container");
      if (!container) return;
      const clone = tplKegiatan.cloneNode(true);
      container.appendChild(clone);
      updateFormNames();
      initSelect2(clone);
    }

    // ADD SUBKEG
    if (t.closest(".add-subkeg")) {
      ev.preventDefault();
      const keg = t.closest(".kegiatan-item");
      const container = keg?.querySelector(".subkeg-container");
      if (!container) return;
      const clone = tplSubkeg.cloneNode(true);
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

  /* =========================================================
     AUTOFILL (SUBKEGIATAN → ANGGARAN)
  ========================================================= */

  updateFormNames();
  initSelect2();

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

    if (tgt.matches(".kegiatan-select")) {
      const selected = tgt.options[tgt.selectedIndex];
      const programId = selected?.dataset.program || "";

      const wrapper = tgt.closest(".kegiatan-item");
      const hidden = wrapper?.querySelector(".program-id-hidden");

      if (hidden) {
        hidden.value = programId;
        console.debug("[PK] program_id di-set (change):", {
          kegiatan_id: tgt.value,
          program_id: programId,
        });
      }
      return;
    }

    if (tgt.matches('select.subkeg-select, select[name*="[subkegiatan_id]"]')) {
      const subkegItem = tgt.closest(".subkeg-item") || tgt.closest(".row");
      if (!subkegItem) return;
      const anggaranField = subkegItem.querySelector(
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
  });

  // initialize anggaran values for existing selects on load
  qsa(".subkeg-select").forEach((sel) => {
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
    form.addEventListener("submit", (e) => {
      // 1️⃣ UPDATE name=""
      updateFormNames();

      // 2️⃣ 🔥 PAKSA SET program_id SEBELUM FormData
      document.querySelectorAll(".kegiatan-item").forEach((item) => {
        const keg = item.querySelector(".kegiatan-select");
        const hidden = item.querySelector(".program-id-hidden");
        const selected = keg?.options[keg.selectedIndex];

        const programId = selected?.dataset?.program || "";

        if (hidden) {
          hidden.value = programId;
        }
      });

      // 3️⃣ BARU DEBUG FormData
      console.log("===== FORMDATA FIXED =====");
      const fd = new FormData(form);
      for (const [k, v] of fd.entries()) {
        if (
          k.includes("program") ||
          k.includes("kegiatan") ||
          k.includes("subkegiatan")
        ) {
          console.log(k, "=", v);
        }
      }
      console.log("===== END =====");

      // 4️⃣ VALIDASI OPTIONAL
      let invalid = false;
      document.querySelectorAll(".program-id-hidden").forEach((h) => {
        if (!h.value) invalid = true;
      });

      if (invalid) {
        e.preventDefault();
        alert("Masih ada kegiatan tanpa program.");
        return;
      }
    });
  }
});
