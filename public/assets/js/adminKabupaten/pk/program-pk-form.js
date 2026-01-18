document.addEventListener("DOMContentLoaded", () => {
  const programContainer = document.getElementById("program-container");
  const btnAddProgram = document.getElementById("add-program");

  let pIndex = 0;

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
        kegiatanTemplate(p, wrap.children.length),
      );
    }

    if (e.target.classList.contains("add-sub")) {
      const wrap = e.target
        .closest(".kegiatan-item")
        .querySelector(".sub-container");
      const { p, k } = e.target.dataset;
      wrap.insertAdjacentHTML(
        "beforeend",
        subTemplate(p, k, wrap.children.length),
      );
    }

    if (e.target.classList.contains("remove")) {
      e.target.closest(".item").remove();
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
        <input type="number" name="program[${p}][anggaran]" class="form-control mb-3" placeholder="Anggaran Program" required>

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
        <input type="number" name="program[${p}][kegiatan][${k}][anggaran]" class="form-control mb-2" placeholder="Anggaran Kegiatan" required>

        <div class="sub-container"></div>

        <button type="button" class="btn btn-sm btn-outline-secondary add-sub" data-p="${p}" data-k="${k}">
          + Sub Kegiatan
        </button>
      </div>
    </div>`;
  }

  function subTemplate(p, k, s) {
    return `
    <div class="sub-item item border rounded p-2 mb-2">
      <div class="row g-2">
        <div class="col-md-7">
          <input type="text" name="program[${p}][kegiatan][${k}][sub][${s}][nama]"
            class="form-control form-control-sm" placeholder="Sub Kegiatan" required>
        </div>
        <div class="col-md-4">
          <input type="number" name="program[${p}][kegiatan][${k}][sub][${s}][anggaran]"
            class="form-control form-control-sm" placeholder="Anggaran" required>
        </div>
        <div class="col-md-1 d-flex align-items-center">
          <button type="button" class="btn btn-sm btn-outline-danger remove">✕</button>
        </div>
      </div>
    </div>`;
  }
});
