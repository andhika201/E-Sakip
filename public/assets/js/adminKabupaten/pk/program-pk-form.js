document.addEventListener("DOMContentLoaded", () => {
  const programContainer = document.getElementById("program-container");
  const btnAddProgram = document.getElementById("add-program");

  let pIndex = 0;
  let kIndex = 0;
  let sIndex = 0;

  btnAddProgram.addEventListener("click", () => {
    programContainer.insertAdjacentHTML("beforeend", programTemplate(pIndex));
    pIndex++;
  });

  programContainer.addEventListener("click", (e) => {
    if (e.target.classList.contains("add-kegiatan")) {
      const wrap = e.target
        .closest(".program-item")
        .querySelector(".kegiatan-container");
      const p = e.target.dataset.p;
      wrap.insertAdjacentHTML(
        "beforeend",
        kegiatanTemplate(p, kIndex++),
      );
    }

    if (e.target.classList.contains("add-sub")) {
      const wrap = e.target
        .closest(".kegiatan-item")
        .querySelector(".sub-container");
      const { p, k } = e.target.dataset;
      wrap.insertAdjacentHTML(
        "beforeend",
        subTemplate(p, k, sIndex++),
      );
    }

    if (e.target.classList.contains("remove")) {

      if (confirm("Yakin ingin menghapus data ini?")) {
        e.target.closest(".item").remove();
      }

    }
  });

  function programTemplate(p) {
    return `
    <div class="card border-success mb-4 program-item item">
      <div class="card-header bg-success bg-opacity-10 d-flex justify-content-between">
        <strong class="text-success">Program</strong>
        <button type="button" class="btn btn-sm btn-outline-danger remove">Hapus</button>
      </div>
      <div class="card-body">
        <textarea name="program[${p}][nama]" class="form-control mb-2" placeholder="Nama Program" required></textarea>
        <input type="text" name="program[${p}][anggaran]" class="form-control mb-3 rupiah" placeholder="Anggaran Program" required>

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
        <textarea name="program[${p}][kegiatan][${k}][nama]" class="form-control mb-2" placeholder="Nama Kegiatan" required></textarea>
        <input type="text" name="program[${p}][kegiatan][${k}][anggaran]" class="form-control mb-2 rupiah" placeholder="Anggaran Kegiatan" required>

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
      <button type="button" class="btn btn-sm btn-outline-danger remove">✕</button>
    </div>

    <div class="card-body py-2">
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
            placeholder="Anggaran Sub Kegiatan"
            required>
        </div>

      </div>
    </div>

  </div>`;
}

  function formatRupiah(angka) {

    angka = angka.replace(/[^\d]/g, '');

    let number_string = angka.toString();
    let sisa = number_string.length % 3;
    let rupiah = number_string.substr(0, sisa);
    let ribuan = number_string.substr(sisa).match(/\d{3}/g);

    if (ribuan) {
      let separator = sisa ? '.' : '';
      rupiah += separator + ribuan.join('.');
    }

    return rupiah;
  }

  document.addEventListener("input", function (e) {

    if (e.target.classList.contains("rupiah")) {

      let input = e.target;

      let oldLength = input.value.length;
      let cursorPos = input.selectionStart;

      input.value = formatRupiah(input.value);

      let newLength = input.value.length;

      cursorPos = cursorPos + (newLength - oldLength);

      input.setSelectionRange(cursorPos, cursorPos);

    }

  });
  document.querySelector("form").addEventListener("submit", function (e) {

    const programs = document.querySelectorAll(".program-item");

    if (programs.length === 0) {
      alert("Minimal harus ada 1 program");
      e.preventDefault();
      return;
    }

  });
  document.querySelector("form").addEventListener("submit", function () {

    document.querySelectorAll(".rupiah").forEach(function (input) {

      input.value = input.value.replace(/\./g, '');

    });

  });

  if (typeof programData !== "undefined") {

    loadProgramData(programData);

  }

  function loadProgramData(data) {

    let p = 0;

    programContainer.insertAdjacentHTML(
      "beforeend",
      programTemplate(p)
    );

    const programItem = programContainer.querySelector(".program-item");

    programItem.querySelector("textarea").value = data.program_kegiatan;
    programItem.querySelector(".rupiah").value = data.anggaran;

    if (data.kegiatan) {

      data.kegiatan.forEach((kegiatan, k) => {

        const kegiatanWrap = programItem.querySelector(".kegiatan-container");

        kegiatanWrap.insertAdjacentHTML(
          "beforeend",
          kegiatanTemplate(p, k)
        );

        const kegiatanItem = kegiatanWrap.querySelectorAll(".kegiatan-item")[k];

        kegiatanItem.querySelector("textarea").value = kegiatan.kegiatan;
        kegiatanItem.querySelector(".rupiah").value = kegiatan.anggaran;

        if (kegiatan.sub_kegiatan) {

          kegiatan.sub_kegiatan.forEach((sub, s) => {

            const subWrap = kegiatanItem.querySelector(".sub-container");

            subWrap.insertAdjacentHTML(
              "beforeend",
              subTemplate(p, k, s)
            );

            const subItem = subWrap.querySelectorAll(".sub-item")[s];

            subItem.querySelector("input[type='text']").value = sub.sub_kegiatan;
            subItem.querySelector(".rupiah").value = sub.anggaran;

          });

        }

      });

    }

  }
});

