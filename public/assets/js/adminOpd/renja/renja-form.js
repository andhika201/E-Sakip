// assets/js/adminOpd/renja/renja-form.js
document.addEventListener('DOMContentLoaded', function () {
  const programContainer = document.getElementById('program-container');
  const addProgramBtn = document.getElementById('add-program');
  const tplProgram = document.getElementById('tpl-program');
  const tplKegiatan = document.getElementById('tpl-kegiatan');
  const tplSub = document.getElementById('tpl-subkegiatan');
  const form = document.querySelector('form');

  // ---------- helpers ----------
  function cloneTemplate(tpl) {
    return tpl.content.firstElementChild.cloneNode(true);
  }

  function fillProgramOptions(selectEl) {
    if (!selectEl) return;
    selectEl.innerHTML = '<option value="">-- Pilih Program --</option>';
    if (typeof daftarProgram !== 'undefined' && Array.isArray(daftarProgram)) {
      daftarProgram.forEach(p => {
        const opt = document.createElement('option');
        opt.value = p.id;
        opt.textContent = p.program_kegiatan || ('Program ' + p.id);
        selectEl.appendChild(opt);
      });
    }
  }

  // rupiah helpers
  function digitsOnly(v) {
    if (v === null || v === undefined) return '';
    return String(v).replace(/[^\d]/g, '');
  }
  function formatRupiah(num) {
    const s = digitsOnly(num);
    if (!s) return '';
    return s.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
  }

  // ---------- naming & labels ----------
  function updateNamesAndLabels() {
    const programEls = Array.from(programContainer.querySelectorAll('.program-item'));
    programEls.forEach((pEl, pIdx) => {
      // program label
      const lblP = pEl.querySelector('label.fw-medium');
      if (lblP) lblP.textContent = `Program ${pIdx + 1}`;

      // ensure hidden program id field exists (for new ones left empty)
      let hidProg = pEl.querySelector('input[name^="program["][name$="[id]"]');
      if (!hidProg) {
        hidProg = document.createElement('input');
        hidProg.type = 'hidden';
        pEl.prepend(hidProg);
      }
      hidProg.name = `program[${pIdx}][id]`;

      // select program
      const select = pEl.querySelector('.select-program, select');
      if (select) select.name = `program[${pIdx}][program_id]`;

      // kegiatan
      const kegiatanEls = Array.from(pEl.querySelectorAll('.kegiatan-item'));
      kegiatanEls.forEach((kEl, kIdx) => {
        const lblK = kEl.querySelector('label.fw-medium');
        if (lblK) lblK.textContent = `Kegiatan ${pIdx + 1}.${kIdx + 1}`;

        // hidden kegiatan id
        let hidK = kEl.querySelector('input[name*="[kegiatan]"][name$="[id]"]');
        if (!hidK) {
          hidK = document.createElement('input');
          hidK.type = 'hidden';
          kEl.prepend(hidK);
        }
        hidK.name = `program[${pIdx}][kegiatan][${kIdx}][id]`;

        // nama kegiatan
        const namaK = kEl.querySelector('.nama-kegiatan, textarea');
        if (namaK) namaK.name = `program[${pIdx}][kegiatan][${kIdx}][nama_kegiatan]`;

        // subkegiatan
        const subEls = Array.from(kEl.querySelectorAll('.subkegiatan-item'));
        subEls.forEach((sEl, sIdx) => {
          const lblS = sEl.querySelector('label.fw-medium');
          if (lblS) lblS.textContent = `Sub ${pIdx + 1}.${kIdx + 1}.${sIdx + 1}`;

          // hidden sub id
          let hidS = sEl.querySelector('input[name*="[subkegiatan]"][name$="[id]"]');
          if (!hidS) {
            hidS = document.createElement('input');
            hidS.type = 'hidden';
            sEl.prepend(hidS);
          }
          hidS.name = `program[${pIdx}][kegiatan][${kIdx}][subkegiatan][${sIdx}][id]`;

          // nama sub
          const namaS = sEl.querySelector('.nama-subkegiatan, textarea');
          if (namaS) namaS.name = `program[${pIdx}][kegiatan][${kIdx}][subkegiatan][${sIdx}][nama_subkegiatan]`;

          // hidden target
          const hiddenT = sEl.querySelector('.target-hidden');
          if (hiddenT) hiddenT.name = `program[${pIdx}][kegiatan][${kIdx}][subkegiatan][${sIdx}][target_anggaran]`;

          // target display data-index
          const disp = sEl.querySelector('.target-display');
          if (disp) disp.setAttribute('data-index', `${pIdx}-${kIdx}-${sIdx}`);
        });
      });
    });

    // show/hide remove buttons based on counts
    const progCount = programContainer.querySelectorAll('.program-item').length;
    programContainer.querySelectorAll('.program-item').forEach(p => {
      const remP = p.querySelector('.remove-program');
      if (remP) remP.style.display = progCount > 1 ? '' : 'none';

      const kCount = p.querySelectorAll('.kegiatan-item').length;
      p.querySelectorAll('.kegiatan-item').forEach(k => {
        const remK = k.querySelector('.remove-kegiatan');
        if (remK) remK.style.display = kCount > 1 ? '' : 'none';
      });
    });

    programContainer.querySelectorAll('.kegiatan-item').forEach(k => {
      const sCount = k.querySelectorAll('.subkegiatan-item').length;
      k.querySelectorAll('.subkegiatan-item').forEach(s => {
        const remS = s.querySelector('.remove-subkegiatan');
        if (remS) remS.style.display = sCount > 1 ? '' : 'none';
      });
    });
  }

  // ---------- node creators ----------
  function createProgramNode() {
    const node = cloneTemplate(tplProgram);
    const select = node.querySelector('select');
    fillProgramOptions(select);

    // ensure it has at least 1 kegiatan + 1 sub
    const kegiatanContainer = node.querySelector('.kegiatan-container');
    kegiatanContainer.innerHTML = '';
    const kNode = createKegiatanNode();
    kegiatanContainer.appendChild(kNode);

    return node;
  }

  function createKegiatanNode() {
    const node = cloneTemplate(tplKegiatan);
    // ensure one sub inside
    const subContainer = node.querySelector('.subkegiatan-container');
    subContainer.innerHTML = '';
    const sNode = createSubNode();
    subContainer.appendChild(sNode);
    return node;
  }

  function createSubNode() {
    const node = cloneTemplate(tplSub);
    // clear values
    const disp = node.querySelector('.target-display');
    const hid = node.querySelector('.target-hidden');
    if (disp) disp.value = '';
    if (hid) hid.value = '';
    return node;
  }

  // ---------- add functions ----------
  function addProgram() {
    const n = createProgramNode();
    programContainer.appendChild(n);
    updateNamesAndLabels();
    return n;
  }

  function addKegiatanTo(container) {
    const node = createKegiatanNode();
    container.appendChild(node);
    updateNamesAndLabels();
    return node;
  }

  function addSubTo(container) {
    const node = createSubNode();
    container.appendChild(node);
    updateNamesAndLabels();
    return node;
  }

  // ---------- hydrate existing (server-rendered) ----------
  (function hydrate() {
    const existingPrograms = programContainer.querySelectorAll('.program-item');
    if (!existingPrograms.length) {
      // ensure at least 1 program on empty form
      addProgram();
      return;
    }
    // fill selects and ensure min children
    existingPrograms.forEach(pEl => {
      const select = pEl.querySelector('select');
      if (select) fillProgramOptions(select);

      const kc = pEl.querySelector('.kegiatan-container');
      const kEls = kc ? kc.querySelectorAll('.kegiatan-item') : [];
      if (!kEls.length) {
        addKegiatanTo(kc);
      } else {
        kEls.forEach(kEl => {
          const sc = kEl.querySelector('.subkegiatan-container');
          const sEls = sc ? sc.querySelectorAll('.subkegiatan-item') : [];
          if (!sEls.length) addSubTo(sc);
        });
      }
    });

    updateNamesAndLabels();
    // format existing display from hidden values if present
    programContainer.querySelectorAll('.subkegiatan-item').forEach(sEl => {
      const display = sEl.querySelector('.target-display');
      const hidden = sEl.querySelector('.target-hidden');
      if (display && hidden && hidden.value) {
        display.value = 'Rp ' + formatRupiah(hidden.value);
      }
    });
  })();

  // ---------- event delegation ----------
  document.addEventListener('click', function (e) {
    // add program
    if (e.target.closest('#add-program')) {
      e.preventDefault();
      addProgram();
      return;
    }

    // add kegiatan in program
    const addK = e.target.closest('.add-kegiatan');
    if (addK) {
      e.preventDefault();
      const pItem = addK.closest('.program-item');
      const kc = pItem.querySelector('.kegiatan-container');
      addKegiatanTo(kc);
      return;
    }

    // add sub in kegiatan
    const addS = e.target.closest('.add-subkegiatan');
    if (addS) {
      e.preventDefault();
      const kItem = addS.closest('.kegiatan-item');
      const sc = kItem.querySelector('.subkegiatan-container');
      addSubTo(sc);
      return;
    }

    // remove program
    const remP = e.target.closest('.remove-program');
    if (remP) {
      e.preventDefault();
      const progCount = programContainer.querySelectorAll('.program-item').length;
      if (progCount > 1) {
        const pItem = remP.closest('.program-item');
        pItem.remove();
        updateNamesAndLabels();
      }
      return;
    }

    // remove kegiatan
    const remK = e.target.closest('.remove-kegiatan');
    if (remK) {
      e.preventDefault();
      const pItem = remK.closest('.program-item');
      const kCount = pItem.querySelectorAll('.kegiatan-item').length;
      if (kCount > 1) {
        const kItem = remK.closest('.kegiatan-item');
        kItem.remove();
        updateNamesAndLabels();
      }
      return;
    }

    // remove sub
    const remS = e.target.closest('.remove-subkegiatan');
    if (remS) {
      e.preventDefault();
      const kItem = remS.closest('.kegiatan-item');
      const sCount = kItem.querySelectorAll('.subkegiatan-item').length;
      if (sCount > 1) {
        const sItem = remS.closest('.subkegiatan-item');
        sItem.remove();
        updateNamesAndLabels();
      }
      return;
    }
  });

  // ---------- input handlers for rupiah ----------
  document.addEventListener('input', function (e) {
    const t = e.target;
    if (!t.classList) return;
    if (t.classList.contains('target-display')) {
      const raw = digitsOnly(t.value);
      const sItem = t.closest('.subkegiatan-item');
      if (sItem) {
        const hidden = sItem.querySelector('.target-hidden');
        if (hidden) hidden.value = raw || '';
      }
      t.value = raw ? 'Rp ' + formatRupiah(raw) : '';
    }
  });

  // focusin: show raw digits (no Rp)
  document.addEventListener('focusin', function (e) {
    const t = e.target;
    if (t.classList && t.classList.contains('target-display')) {
      const sItem = t.closest('.subkegiatan-item');
      const hidden = sItem ? sItem.querySelector('.target-hidden') : null;
      const raw = hidden ? (hidden.value || '') : '';
      t.value = raw ? formatRupiah(raw) : '';
    }
  });

  // focusout: format to Rp ...
  document.addEventListener('focusout', function (e) {
    const t = e.target;
    if (t.classList && t.classList.contains('target-display')) {
      const sItem = t.closest('.subkegiatan-item');
      const hidden = sItem ? sItem.querySelector('.target-hidden') : null;
      const raw = hidden ? (hidden.value || '') : '';
      t.value = raw ? 'Rp ' + formatRupiah(raw) : '';
    }
  });

  // ---------- before submit: sync hidden values & names (optional simple validation) ----------
  if (form) {
    form.addEventListener('submit', function (ev) {
      // sync names & labels
      updateNamesAndLabels();

      // sync hidden target values from display
      programContainer.querySelectorAll('.subkegiatan-item').forEach(sEl => {
        const display = sEl.querySelector('.target-display');
        const hidden = sEl.querySelector('.target-hidden');
        if (display && hidden) hidden.value = digitsOnly(display.value) || hidden.value || '';
      });

      // quick validation: ensure at least 1 program/kegiatan/sub exist (should be ok by UI rules)
      const programEls = programContainer.querySelectorAll('.program-item');
      if (!programEls.length) {
        ev.preventDefault();
        alert('Minimal harus ada 1 program.');
        return false;
      }

      // allow submit
    });
  }

  // initial
  updateNamesAndLabels();
});
