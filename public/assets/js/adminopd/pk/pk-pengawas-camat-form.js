/**
 * PK PENGAWAS — MODE KECAMATAN (Kasi/Kasubag di bawah Camat).
 *
 * Beda dengan pk-pengawas-form.js (mode Dinas):
 *   - Dinas   : kegiatan diambil dari kegiatan milik Administrator; program_id
 *               IMPLISIT (data-program pada opsi kegiatan) -> disalin ke hidden.
 *   - Kecamatan: PROGRAM dipilih EKSPLISIT dari program milik Camat (.program-select),
 *               lalu kegiatan didefinisikan sendiri dari master (.kegiatan-select).
 *               program_id = value .program-select (tanpa derivasi).
 *
 * Struktur name yang dihasilkan identik dgn parser save/update pengawas:
 *   sasaran_pk[si][indikator][ii][program][ki][program_id]
 *   sasaran_pk[si][indikator][ii][program][ki][kegiatan][0][kegiatan_id]
 *   sasaran_pk[si][indikator][ii][program][ki][kegiatan][0][subkegiatan][sk][subkegiatan_id]
 *   sasaran_pk[si][indikator][ii][program][ki][kegiatan][0][subkegiatan][sk][anggaran]
 */
document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("pk-form");

  const sasaranContainer = document.querySelector(".sasaran-container");
  if (!sasaranContainer) return;

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

  /* ============ AUTOFILL ANGGARAN (via event Select2) ============
     Select2 memicu perubahan lewat jQuery, sehingga listener 'change' native
     di document.body sering tidak tertangkap. Pakai event 'select2:select'
     agar anggaran subkegiatan terisi otomatis (mirror pk-pengawas-form.js). */
  if (window.jQuery) {
    jQuery(document).on(
      "select2:select",
      'select[name*="[subkegiatan_id]"]',
      function () {
        const select = this;
        const selected = select.options[select.selectedIndex];
        if (!selected) return;

        const subkegItem = select.closest(".subkeg-item");
        if (!subkegItem) return;

        const anggaranInput = subkegItem.querySelector('input[name*="[anggaran]"]');
        if (!anggaranInput) return;

        const anggaran = selected.getAttribute("data-anggaran") || "";
        anggaranInput.value = anggaran
          ? "Rp " + parseInt(anggaran, 10).toLocaleString("id-ID")
          : "";
      },
    );
  }

  /* ============ TEMPLATE ============ */
  const tplSasaran = qs(".sasaran-item")?.cloneNode(true);
  const tplIndikator = qs(".indikator-item")?.cloneNode(true);
  const tplKegiatan = qs(".kegiatan-item")?.cloneNode(true);
  const tplSubkeg = qs(".subkeg-item")?.cloneNode(true);

  [tplSasaran, tplIndikator, tplKegiatan, tplSubkeg].forEach((tpl) => {
    if (!tpl) return;
    tpl.querySelectorAll("input, textarea, select").forEach((el) => {
      if (el.tagName === "SELECT") el.selectedIndex = 0;
      else el.value = "";
    });
  });

  /* ============ UPDATE NAME ============ */
  function updateFormNames() {
    qsa(".sasaran-item", sasaranContainer).forEach((sasaran, si) => {
      const sasaranTxt = sasaran.querySelector("textarea");
      if (sasaranTxt) sasaranTxt.name = `sasaran_pk[${si}][sasaran]`;

      qsa(".indikator-item", sasaran).forEach((indikator, ii) => {
        indikator
          .querySelector(".indikator-input")
          ?.setAttribute("name", `sasaran_pk[${si}][indikator][${ii}][indikator]`);
        indikator
          .querySelector(".indikator-target")
          ?.setAttribute("name", `sasaran_pk[${si}][indikator][${ii}][target]`);
        indikator
          .querySelector(".satuan-select")
          ?.setAttribute("name", `sasaran_pk[${si}][indikator][${ii}][id_satuan]`);
        indikator
          .querySelector(".jenis-indikator-select")
          ?.setAttribute("name", `sasaran_pk[${si}][indikator][${ii}][jenis_indikator]`);

        // Tiap .kegiatan-item = 1 pasang (program Camat + kegiatan)
        qsa(".kegiatan-item", indikator).forEach((kegiatan, ki) => {
          const programSelect = kegiatan.querySelector(".program-select");
          const kegiatanSelect = kegiatan.querySelector(".kegiatan-select");

          if (programSelect) {
            programSelect.name = `sasaran_pk[${si}][indikator][${ii}][program][${ki}][program_id]`;
          }
          if (kegiatanSelect) {
            kegiatanSelect.name = `sasaran_pk[${si}][indikator][${ii}][program][${ki}][kegiatan][0][kegiatan_id]`;
          }

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

  /* ============ ADD / REMOVE ============ */
  document.body.addEventListener("click", (ev) => {
    const t = ev.target;

    if (t.closest(".add-sasaran")) {
      ev.preventDefault();
      const sasaranClone = tplSasaran.cloneNode(true);
      const indikatorContainer = sasaranClone.querySelector(".indikator-container");
      if (indikatorContainer && tplIndikator) {
        indikatorContainer.innerHTML = "";
        const indikatorClone = tplIndikator.cloneNode(true);
        const kegiatanContainer = indikatorClone.querySelector(".kegiatan-container");
        if (kegiatanContainer && tplKegiatan) {
          kegiatanContainer.innerHTML = "";
          const kegClone = tplKegiatan.cloneNode(true);
          const subContainer = kegClone.querySelector(".subkeg-container");
          if (subContainer && tplSubkeg) {
            subContainer.innerHTML = "";
            subContainer.appendChild(tplSubkeg.cloneNode(true));
          }
          kegiatanContainer.appendChild(kegClone);
        }
        indikatorContainer.appendChild(indikatorClone);
      }
      sasaranContainer.appendChild(sasaranClone);
      updateFormNames();
      initSelect2(sasaranClone);
    }

    if (t.closest(".add-indikator")) {
      ev.preventDefault();
      const indikatorContainer = t
        .closest(".indikator-section")
        ?.querySelector(".indikator-container");
      if (!indikatorContainer) return;
      const indikatorClone = tplIndikator.cloneNode(true);
      const kegiatanContainer = indikatorClone.querySelector(".kegiatan-container");
      if (kegiatanContainer && tplKegiatan) {
        kegiatanContainer.innerHTML = "";
        const kegClone = tplKegiatan.cloneNode(true);
        const subContainer = kegClone.querySelector(".subkeg-container");
        if (subContainer && tplSubkeg) {
          subContainer.innerHTML = "";
          subContainer.appendChild(tplSubkeg.cloneNode(true));
        }
        kegiatanContainer.appendChild(kegClone);
      }
      indikatorContainer.appendChild(indikatorClone);
      updateFormNames();
      initSelect2(indikatorClone);
    }

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

  /* ============ AUTOFILL SUBKEGIATAN → ANGGARAN ============ */
  updateFormNames();
  initSelect2();

  document.body.addEventListener("change", (ev) => {
    const tgt = ev.target;

    if (tgt.matches('select.subkeg-select, select[name*="[subkegiatan_id]"]')) {
      const subkegItem = tgt.closest(".subkeg-item") || tgt.closest(".row");
      if (!subkegItem) return;
      const anggaranField = subkegItem.querySelector('input[name*="[anggaran]"]');
      const selected = tgt.options[tgt.selectedIndex];
      if (anggaranField) {
        const ang = selected ? selected.getAttribute("data-anggaran") || "" : "";
        anggaranField.value = ang ? formatRupiahNumber(ang) : "";
      }
      updateFormNames();
      return;
    }

    if (tgt.matches(".pegawai-select")) {
      const targetName = tgt.dataset.target;
      if (!targetName) return;
      const nipInput = document.querySelector(`input[name="${targetName}"]`);
      if (!nipInput) return;
      const selected = tgt.options[tgt.selectedIndex];
      nipInput.value = selected ? selected.dataset.nip || "" : "";
    }
  });

  // Inisialisasi anggaran utk subkeg yang sudah terpilih (mode edit)
  qsa(".subkeg-select").forEach((sel) => {
    const selected = sel.options[sel.selectedIndex];
    if (selected && selected.dataset && selected.dataset.anggaran) {
      const angInput = sel
        .closest(".subkeg-item")
        ?.querySelector('input[name*="[anggaran]"]');
      if (angInput) angInput.value = formatRupiahNumber(selected.dataset.anggaran);
    }
  });

  window.addEventListener("load", () => initSelect2(document));

  /* ============ SUBMIT: pastikan tiap kegiatan punya Program ============ */
  if (form) {
    form.addEventListener("submit", (e) => {
      updateFormNames();

      let invalid = false;
      document.querySelectorAll(".kegiatan-item .program-select").forEach((sel) => {
        if (!sel.value) invalid = true;
      });

      if (invalid) {
        e.preventDefault();
        alert("Masih ada kegiatan tanpa Program Camat.");
      }
    });
  }
});
