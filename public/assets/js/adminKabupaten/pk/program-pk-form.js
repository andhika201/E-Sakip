document.addEventListener("DOMContentLoaded", () => {
  const programContainer = document.getElementById("program-container");
  const btnAddProgram = document.getElementById("add-program");
  const form = document.querySelector("form");

  if (!programContainer || !btnAddProgram || !form) return;

  function directItems(container, className) {
    if (!container) return [];
    return Array.from(container.children).filter((el) => el.classList.contains(className));
  }

  function formatRupiah(angka) {
    angka = String(angka || "")
      .trim()
      .replace(/[,.]\d{1,2}$/, "")
      .replace(/[^\d]/g, "");

    const sisa = angka.length % 3;
    let rupiah = angka.substr(0, sisa);
    const ribuan = angka.substr(sisa).match(/\d{3}/g);

    if (ribuan) {
      const separator = sisa ? "." : "";
      rupiah += separator + ribuan.join(".");
    }

    return rupiah;
  }

  function programTemplate(p) {
    return `
    <div class="card border-success mb-4 program-item item">
      <div class="card-header bg-success bg-opacity-10 d-flex justify-content-between">
        <strong class="text-success">Program</strong>
        <button type="button" class="btn btn-sm btn-outline-danger remove">Hapus</button>
      </div>
      <div class="card-body">
        <input type="hidden" name="program[${p}][id]" class="program-id">
        <textarea name="program[${p}][nama]" class="form-control mb-2" placeholder="Nama Program" required></textarea>
        <input type="text" name="program[${p}][anggaran]" class="form-control mb-3 rupiah" placeholder="Anggaran Program (contoh: 1.500.000)" required>

        <div class="kegiatan-container"></div>

        <button type="button" class="btn btn-sm btn-outline-primary add-kegiatan" data-p="${p}">
          + Kegiatan
        </button>
      </div>
    </div>`;
  }

  function kegiatanTemplate(p, k) {
    return `
    <div class="card border-primary mb-3 kegiatan-item item">
      <div class="card-header bg-primary bg-opacity-10 d-flex justify-content-between">
        <span class="fw-semibold text-primary">Kegiatan</span>
        <button type="button" class="btn btn-sm btn-outline-danger remove">Hapus</button>
      </div>
      <div class="card-body">
        <input type="hidden" name="program[${p}][kegiatan][${k}][id]" class="kegiatan-id">
        <textarea name="program[${p}][kegiatan][${k}][nama]" class="form-control mb-2" placeholder="Nama Kegiatan" required></textarea>
        <input type="text" name="program[${p}][kegiatan][${k}][anggaran]" class="form-control mb-2 rupiah" placeholder="Anggaran Kegiatan (contoh: 1.500.000)" required>

        <div class="sub-container"></div>

        <button type="button" class="btn btn-sm btn-outline-secondary add-sub" data-p="${p}" data-k="${k}">
          + Sub Kegiatan
        </button>
      </div>
    </div>`;
  }

  function subTemplate(p, k, s) {
    return `
    <div class="card border-secondary mb-2 sub-item item">
      <div class="card-header bg-secondary bg-opacity-10 d-flex justify-content-between py-1">
        <span class="fw-semibold text-secondary">Sub Kegiatan</span>
        <button type="button" class="btn btn-sm btn-outline-danger remove">Hapus</button>
      </div>

      <div class="card-body py-2">
        <input type="hidden" name="program[${p}][kegiatan][${k}][sub][${s}][id]" class="sub-id">
        <div class="row g-2">
          <div class="col-md-7">
            <input type="text"
              name="program[${p}][kegiatan][${k}][sub][${s}][nama]"
              class="form-control form-control-sm"
              placeholder="Nama Sub Kegiatan"
              required>
          </div>

          <div class="col-md-5">
            <input type="text"
              name="program[${p}][kegiatan][${k}][sub][${s}][anggaran]"
              class="form-control form-control-sm rupiah"
              placeholder="Anggaran Sub Kegiatan (contoh: 1.500.000)"
              required>
          </div>
        </div>
      </div>
    </div>`;
  }

  function syncAddProgramButton() {
    const hasProgram = directItems(programContainer, "program-item").length >= 1;
    btnAddProgram.classList.toggle("d-none", hasProgram);
    btnAddProgram.disabled = hasProgram;
  }

  function renumberForm() {
    directItems(programContainer, "program-item").forEach((programItem, p) => {
      const programBody = programItem.querySelector(":scope > .card-body");
      const programId = programBody?.querySelector(":scope > input.program-id");
      const programName = programBody?.querySelector(":scope > textarea");
      const programAnggaran = programBody?.querySelector(":scope > input.rupiah");
      const kegiatanContainer = programBody?.querySelector(":scope > .kegiatan-container");
      const addKegiatan = programBody?.querySelector(":scope > .add-kegiatan");

      if (programId) programId.name = `program[${p}][id]`;
      if (programName) programName.name = `program[${p}][nama]`;
      if (programAnggaran) programAnggaran.name = `program[${p}][anggaran]`;
      if (addKegiatan) addKegiatan.dataset.p = String(p);

      directItems(kegiatanContainer, "kegiatan-item").forEach((kegiatanItem, k) => {
        const kegiatanBody = kegiatanItem.querySelector(":scope > .card-body");
        const kegiatanId = kegiatanBody?.querySelector(":scope > input.kegiatan-id");
        const kegiatanName = kegiatanBody?.querySelector(":scope > textarea");
        const kegiatanAnggaran = kegiatanBody?.querySelector(":scope > input.rupiah");
        const subContainer = kegiatanBody?.querySelector(":scope > .sub-container");
        const addSub = kegiatanBody?.querySelector(":scope > .add-sub");

        if (kegiatanId) kegiatanId.name = `program[${p}][kegiatan][${k}][id]`;
        if (kegiatanName) kegiatanName.name = `program[${p}][kegiatan][${k}][nama]`;
        if (kegiatanAnggaran) kegiatanAnggaran.name = `program[${p}][kegiatan][${k}][anggaran]`;
        if (addSub) {
          addSub.dataset.p = String(p);
          addSub.dataset.k = String(k);
        }

        directItems(subContainer, "sub-item").forEach((subItem, s) => {
          const subId = subItem.querySelector("input.sub-id");
          const subName = subItem.querySelector('input[name*="[nama]"]');
          const subAnggaran = subItem.querySelector('input.rupiah[name*="[anggaran]"]');

          if (subId) subId.name = `program[${p}][kegiatan][${k}][sub][${s}][id]`;
          if (subName) subName.name = `program[${p}][kegiatan][${k}][sub][${s}][nama]`;
          if (subAnggaran) subAnggaran.name = `program[${p}][kegiatan][${k}][sub][${s}][anggaran]`;
        });
      });
    });

    syncAddProgramButton();
  }

  function appendProgram() {
    if (directItems(programContainer, "program-item").length >= 1) return;
    programContainer.insertAdjacentHTML("beforeend", programTemplate(0));
    renumberForm();
  }

  btnAddProgram.addEventListener("click", () => {
    appendProgram();
  });

  programContainer.addEventListener("click", (e) => {
    const addKegiatanBtn = e.target.closest(".add-kegiatan");
    const addSubBtn = e.target.closest(".add-sub");
    const removeBtn = e.target.closest(".remove");

    if (addKegiatanBtn) {
      const programItem = addKegiatanBtn.closest(".program-item");
      const kegiatanWrap = programItem?.querySelector(".kegiatan-container");
      if (!programItem || !kegiatanWrap) return;

      const p = directItems(programContainer, "program-item").indexOf(programItem);
      const k = directItems(kegiatanWrap, "kegiatan-item").length;
      kegiatanWrap.insertAdjacentHTML("beforeend", kegiatanTemplate(p, k));
      renumberForm();
      return;
    }

    if (addSubBtn) {
      const kegiatanItem = addSubBtn.closest(".kegiatan-item");
      const programItem = addSubBtn.closest(".program-item");
      const subWrap = kegiatanItem?.querySelector(".sub-container");
      const kegiatanWrap = programItem?.querySelector(".kegiatan-container");
      if (!programItem || !kegiatanItem || !kegiatanWrap || !subWrap) return;

      const p = directItems(programContainer, "program-item").indexOf(programItem);
      const k = directItems(kegiatanWrap, "kegiatan-item").indexOf(kegiatanItem);
      const s = directItems(subWrap, "sub-item").length;
      subWrap.insertAdjacentHTML("beforeend", subTemplate(p, k, s));
      renumberForm();
      return;
    }

    if (removeBtn) {
      if (confirm("Yakin ingin menghapus data ini?")) {
        removeBtn.closest(".item")?.remove();
        renumberForm();
      }
    }
  });

  document.addEventListener("input", (e) => {
    if (!e.target.classList.contains("rupiah")) return;

    const input = e.target;
    const oldLength = input.value.length;
    let cursorPos = input.selectionStart;

    input.value = formatRupiah(input.value);

    const newLength = input.value.length;
    cursorPos = cursorPos + (newLength - oldLength);
    input.setSelectionRange(cursorPos, cursorPos);
  });

  form.addEventListener("submit", (e) => {
    renumberForm();

    const programs = directItems(programContainer, "program-item");
    if (programs.length === 0) {
      alert("Minimal harus ada 1 program");
      e.preventDefault();
      return;
    }

    document.querySelectorAll(".rupiah").forEach((input) => {
      input.value = input.value.replace(/\./g, "");
    });
  });

  function firstProgram(data) {
    if (Array.isArray(data)) return data[0] || {};
    if (data && Array.isArray(data.program)) return data.program[0] || {};
    return data || {};
  }

  function loadProgramData(data) {
    const program = firstProgram(data);
    programContainer.innerHTML = "";
    programContainer.insertAdjacentHTML("beforeend", programTemplate(0));

    const programItem = programContainer.querySelector(".program-item");
    const programBody = programItem.querySelector(":scope > .card-body");

    const programId = programBody.querySelector(":scope > input.program-id");
    if (programId) programId.value = program.id || "";
    programBody.querySelector(":scope > textarea").value = program.program_kegiatan || program.nama || "";
    programBody.querySelector(":scope > input.rupiah").value = formatRupiah(program.anggaran || "");

    const kegiatanWrap = programBody.querySelector(":scope > .kegiatan-container");
    const kegiatanList = Array.isArray(program.kegiatan) ? program.kegiatan : [];

    kegiatanList.forEach((kegiatan, k) => {
      kegiatanWrap.insertAdjacentHTML("beforeend", kegiatanTemplate(0, k));

      const kegiatanItem = directItems(kegiatanWrap, "kegiatan-item")[k];
      const kegiatanBody = kegiatanItem.querySelector(":scope > .card-body");

      const kegiatanId = kegiatanBody.querySelector(":scope > input.kegiatan-id");
      if (kegiatanId) kegiatanId.value = kegiatan.id || "";
      kegiatanBody.querySelector(":scope > textarea").value = kegiatan.kegiatan || kegiatan.nama || "";
      kegiatanBody.querySelector(":scope > input.rupiah").value = formatRupiah(kegiatan.anggaran || "");

      const subWrap = kegiatanBody.querySelector(":scope > .sub-container");
      const subList = Array.isArray(kegiatan.sub_kegiatan)
        ? kegiatan.sub_kegiatan
        : (Array.isArray(kegiatan.sub) ? kegiatan.sub : []);

      subList.forEach((sub, s) => {
        subWrap.insertAdjacentHTML("beforeend", subTemplate(0, k, s));

        const subItem = directItems(subWrap, "sub-item")[s];
        const subId = subItem.querySelector("input.sub-id");
        const subInputs = subItem.querySelectorAll("input[type='text']");
        if (subId) subId.value = sub.id || "";
        if (subInputs[0]) subInputs[0].value = sub.sub_kegiatan || sub.nama || "";
        if (subInputs[1]) subInputs[1].value = formatRupiah(sub.anggaran || "");
      });
    });

    if (directItems(kegiatanWrap, "kegiatan-item").length === 0) {
      kegiatanWrap.insertAdjacentHTML("beforeend", kegiatanTemplate(0, 0));
    }

    renumberForm();
  }

  if (typeof programData !== "undefined") {
    loadProgramData(programData);
  } else {
    appendProgram();
  }

  renumberForm();
});
