document.addEventListener('DOMContentLoaded', function () {
  // Elements + templates
  const programContainer = document.getElementById('program-container');
  const addProgramBtn = document.getElementById('add-program');
  const tplProgram = document.getElementById('tpl-program');
  const tplKegiatan = document.getElementById('tpl-kegiatan');
  const tplSub = document.getElementById('tpl-subkegiatan');
  const deletedContainer = document.getElementById('deleted-ids-container');
  const form = document.getElementById('edit-rkt-form');

  // ---------- helpers ----------
  function cloneTemplate(tpl) {
    return tpl.content.firstElementChild.cloneNode(true);
  }

  function safeText(s) {
    return (s === null || s === undefined) ? '' : String(s);
  }

  function fillProgramOptions(selectEl, selectedId) {
    if (!selectEl) return;
    selectEl.innerHTML = '<option value="">-- Pilih Program --</option>';
    if (typeof daftarProgram !== 'undefined' && Array.isArray(daftarProgram)) {
      daftarProgram.forEach(p => {
        const opt = document.createElement('option');
        opt.value = p.id;
        opt.textContent = safeText(p.program_kegiatan);
        if (selectedId && parseInt(selectedId, 10) === parseInt(p.id, 10)) opt.selected = true;
        selectEl.appendChild(opt);
      });
    }
  }

  // format helpers for rupiah display
  function digitsOnly(str) {
    if (!str && str !== 0) return '';
    return String(str).replace(/[^\d]/g, '');
  }
  function formatRupiah(numString) {
    const s = digitsOnly(numString);
    if (!s) return '';
    return s.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
  }

  // ---------- index / naming ----------
  function updateAllNames() {
    const programs = Array.from(programContainer.querySelectorAll('.program-item'));
    programs.forEach((pEl, pIdx) => {
      // label
      const lbl = pEl.querySelector('label.fw-medium, label.fw-bold');
      if (lbl) lbl.textContent = `Program ${pIdx + 1}`;

      // ensure hidden program id exists and set name
      let hidProg = pEl.querySelector('input[type="hidden"][name^="program["][name$="[id]"]');
      if (!hidProg) {
        hidProg = document.createElement('input');
        hidProg.type = 'hidden';
        pEl.prepend(hidProg);
      }
      hidProg.name = `program[${pIdx}][id]`;

      // select program
      const select = pEl.querySelector('.select-program, select');
      if (select) {
        select.name = `program[${pIdx}][program_id]`;
        // ensure options exist
        if (!select.querySelector('option[value]') || select.options.length === 1) {
          fillProgramOptions(select, select.getAttribute('data-selected'));
        }
      }

      // kegiatan
      const kegiatanEls = Array.from(pEl.querySelectorAll('.kegiatan-item'));
      kegiatanEls.forEach((kEl, kIdx) => {
        const kLbl = kEl.querySelector('label.fw-medium');
        if (kLbl) kLbl.textContent = `Kegiatan ${pIdx + 1}.${kIdx + 1}`;

        // hidden kegiatan id
        let hidK = kEl.querySelector('input[type="hidden"][name*="[kegiatan]"][name$="[id]"]');
        if (!hidK) {
          hidK = document.createElement('input');
          hidK.type = 'hidden';
          kEl.prepend(hidK);
        }
        hidK.name = `program[${pIdx}][kegiatan][${kIdx}][id]`;

        // nama kegiatan textarea
        const namaK = kEl.querySelector('.nama-kegiatan, textarea');
        if (namaK) namaK.name = `program[${pIdx}][kegiatan][${kIdx}][nama_kegiatan]`;

        // subkegiatan
        const subEls = Array.from(kEl.querySelectorAll('.subkegiatan-item'));
        subEls.forEach((sEl, sIdx) => {
          const sLbl = sEl.querySelector('label.fw-medium');
          if (sLbl) sLbl.textContent = `Sub ${pIdx + 1}.${kIdx + 1}.${sIdx + 1}`;

          // hidden sub id
          let hidS = sEl.querySelector('input[type="hidden"][name*="[subkegiatan]"][name$="[id]"]');
          if (!hidS) {
            hidS = document.createElement('input');
            hidS.type = 'hidden';
            sEl.prepend(hidS);
          }
          hidS.name = `program[${pIdx}][kegiatan][${kIdx}][subkegiatan][${sIdx}][id]`;

          // nama sub
          const namaS = sEl.querySelector('.nama-subkegiatan, textarea');
          if (namaS) namaS.name = `program[${pIdx}][kegiatan][${kIdx}][subkegiatan][${sIdx}][nama_subkegiatan]`;

          // target hidden
          const hiddenTarget = sEl.querySelector('.target-hidden');
          if (hiddenTarget) hiddenTarget.name = `program[${pIdx}][kegiatan][${kIdx}][subkegiatan][${sIdx}][target_anggaran]`;

          // display input - ensure data-index for debugging if needed
          const display = sEl.querySelector('.target-display');
          if (display) display.setAttribute('data-index', `${pIdx}-${kIdx}-${sIdx}`);
        });
      });
    });

    // show/hide remove-program if only 1
    const progCount = programContainer.querySelectorAll('.program-item').length;
    programContainer.querySelectorAll('.program-item').forEach(p => {
      const rem = p.querySelector('.remove-program');
      if (rem) rem.style.display = progCount > 1 ? '' : 'none';
    });

    // for each kegiatan block, show/hide remove-kegiatan based on count
    programContainer.querySelectorAll('.program-item').forEach(p => {
      const kCount = p.querySelectorAll('.kegiatan-item').length;
      p.querySelectorAll('.kegiatan-item').forEach(k => {
        const remK = k.querySelector('.remove-kegiatan');
        if (remK) remK.style.display = kCount > 1 ? '' : 'none';
      });
    });

    // for each sub block, show/hide remove-sub based on count
    programContainer.querySelectorAll('.kegiatan-item').forEach(k => {
      const sCount = k.querySelectorAll('.subkegiatan-item').length;
      k.querySelectorAll('.subkegiatan-item').forEach(s => {
        const remS = s.querySelector('.remove-subkegiatan');
        if (remS) remS.style.display = sCount > 1 ? '' : 'none';
      });
    });
  }

  // ---------- add functions ----------
  function addProgram(prefill = null) {
    const node = cloneTemplate(tplProgram);
    const select = node.querySelector('select');
    fillProgramOptions(select, prefill ? prefill.program_id : null);

    // set hidden id if prefill
    if (prefill && prefill.id) {
      const hid = node.querySelector('input[type="hidden"][name^="program["][name$="[id]"]') || document.createElement('input');
      hid.type = 'hidden';
      hid.value = prefill.id || '';
      node.prepend(hid);
      if (select) select.setAttribute('data-selected', prefill.program_id || '');
    }

    // append to DOM
    programContainer.appendChild(node);

    // Ensure at least one kegiatan + one sub for this program
    const kegiatanContainer = node.querySelector('.kegiatan-container');
    // if prefill has kegiatan, add them
    if (prefill && Array.isArray(prefill.kegiatan) && prefill.kegiatan.length) {
      kegiatanContainer.innerHTML = '';
      prefill.kegiatan.forEach(k => addKegiatanTo(kegiatanContainer, k));
    } else {
      // add one empty kegiatan and one sub inside it
      const kNode = addKegiatanTo(kegiatanContainer, null);
      const subContainer = kNode.querySelector('.subkegiatan-container');
      addSubTo(subContainer, null);
    }

    updateAllAfterMutate();
    return node;
  }

  function addKegiatanTo(container, prefill = null) {
    const node = cloneTemplate(tplKegiatan);
    container.appendChild(node);

    if (prefill && prefill.id) {
      const hid = node.querySelector('input[type="hidden"][name*="[kegiatan]"][name$="[id]"]') || document.createElement('input');
      hid.type = 'hidden';
      hid.value = prefill.id || '';
      node.prepend(hid);

      const nama = node.querySelector('.nama-kegiatan, textarea');
      if (nama) nama.value = prefill.nama_kegiatan || '';

      // sub list
      const subCont = node.querySelector('.subkegiatan-container');
      subCont.innerHTML = '';
      if (Array.isArray(prefill.subkegiatan) && prefill.subkegiatan.length) {
        prefill.subkegiatan.forEach(s => addSubTo(subCont, s));
      } else {
        addSubTo(subCont, null);
      }
    } else {
      // new kegiatan: ensure at least one sub
      const subCont = node.querySelector('.subkegiatan-container');
      subCont.innerHTML = '';
      addSubTo(subCont, null);
    }

    updateAllAfterMutate();
    return node;
  }

  function addSubTo(container, prefill = null) {
    const node = cloneTemplate(tplSub);
    container.appendChild(node);

    if (prefill && prefill.id) {
      const hid = node.querySelector('input[type="hidden"][name*="[subkegiatan]"][name$="[id]"]') || document.createElement('input');
      hid.type = 'hidden';
      hid.value = prefill.id || '';
      node.prepend(hid);

      const nama = node.querySelector('.nama-subkegiatan, textarea');
      if (nama) nama.value = prefill.nama_subkegiatan || '';

      const hiddenTarget = node.querySelector('.target-hidden');
      if (hiddenTarget) hiddenTarget.value = prefill.target_anggaran || '';

      const display = node.querySelector('.target-display');
      if (display) display.value = prefill.target_anggaran ? ('Rp ' + formatRupiah(String(prefill.target_anggaran))) : '';
    } else {
      // ensure hidden exists (empty) for new sub
      const hiddenTarget = node.querySelector('.target-hidden');
      if (hiddenTarget) hiddenTarget.value = '';
    }

    updateAllAfterMutate();
    return node;
  }

  function updateAllAfterMutate() {
    updateAllNames();
  }

  // ---------- initial hydrate (if existing markup exists) ----------
  (function hydrateExisting() {
    // If there are no program-items, create one default (with 1 kegiatan & 1 sub)
    if (!programContainer.querySelector('.program-item')) {
      addProgram(null);
      return;
    }

    // For every existing program-item, ensure select options filled
    const existingPrograms = Array.from(programContainer.querySelectorAll('.program-item'));
    existingPrograms.forEach((pEl) => {
      const select = pEl.querySelector('select');
      const selValue = select ? select.value : null;
      fillProgramOptions(select, selValue);

      // Ensure every kegiatan has at least one sub; if none, create one
      const kegiatanEls = Array.from(pEl.querySelectorAll('.kegiatan-item'));
      if (kegiatanEls.length === 0) {
        const kc = pEl.querySelector('.kegiatan-container');
        const kNode = addKegiatanTo(kc, null);
        addSubTo(kNode.querySelector('.subkegiatan-container'), null);
      } else {
        kegiatanEls.forEach(kEl => {
          const subEls = kEl.querySelectorAll('.subkegiatan-item');
          if (!subEls.length) {
            const sc = kEl.querySelector('.subkegiatan-container');
            addSubTo(sc, null);
          }
        });
      }
    });

    updateAllNames();

    // format existing target-display values from hidden values
    programContainer.querySelectorAll('.subkegiatan-item').forEach(sEl => {
      const display = sEl.querySelector('.target-display');
      const hidden = sEl.querySelector('.target-hidden');
      if (display && hidden) {
        const raw = hidden.value || '';
        display.value = raw ? ('Rp ' + formatRupiah(String(raw))) : display.value;
      }
    });
  })();

  // ---------- delegation: click handlers ----------
  document.addEventListener('click', function (e) {
    // add program
    if (e.target.closest('#add-program')) {
      e.preventDefault();
      addProgram(null);
      return;
    }

    // add kegiatan inside a program
    const addK = e.target.closest('.add-kegiatan');
    if (addK) {
      e.preventDefault();
      const pItem = addK.closest('.program-item');
      const container = pItem.querySelector('.kegiatan-container');
      addKegiatanTo(container, null);
      return;
    }

    // add sub inside a kegiatan
    const addS = e.target.closest('.add-subkegiatan');
    if (addS) {
      e.preventDefault();
      const kItem = addS.closest('.kegiatan-item');
      const subContainer = kItem.querySelector('.subkegiatan-container');
      addSubTo(subContainer, null);
      return;
    }

    // remove program
    const remP = e.target.closest('.remove-program');
    if (remP) {
      e.preventDefault();
      // Prevent deletion if this is the only program
      const progCount = programContainer.querySelectorAll('.program-item').length;
      if (progCount <= 1) {
        alert('Minimal harus ada 1 program pada RKT.');
        return;
      }

      const pItem = remP.closest('.program-item');
      const existing = pItem.getAttribute('data-existing-id');
      if (existing) {
        const inp = document.createElement('input');
        inp.type = 'hidden';
        inp.name = 'deleted_program_ids[]';
        inp.value = existing;
        deletedContainer.appendChild(inp);
      }
      pItem.remove();
      updateAllAfterMutate();
      return;
    }

    // remove kegiatan
    const remK = e.target.closest('.remove-kegiatan');
    if (remK) {
      e.preventDefault();
      const kItem = remK.closest('.kegiatan-item');
      const pItem = remK.closest('.program-item');
      const kCount = pItem.querySelectorAll('.kegiatan-item').length;
      if (kCount <= 1) {
        alert('Minimal harus ada 1 kegiatan dalam setiap program.');
        return;
      }

      const existing = kItem.getAttribute('data-existing-id');
      if (existing) {
        const inp = document.createElement('input');
        inp.type = 'hidden';
        inp.name = 'deleted_kegiatan_ids[]';
        inp.value = existing;
        deletedContainer.appendChild(inp);
      }
      kItem.remove();
      updateAllAfterMutate();
      return;
    }

    // remove sub
    const remS = e.target.closest('.remove-subkegiatan');
    if (remS) {
      e.preventDefault();
      const sItem = remS.closest('.subkegiatan-item');
      const kItem = remS.closest('.kegiatan-item');
      const sCount = kItem.querySelectorAll('.subkegiatan-item').length;
      if (sCount <= 1) {
        alert('Minimal harus ada 1 subkegiatan dalam setiap kegiatan.');
        return;
      }

      const existing = sItem.getAttribute('data-existing-id');
      if (existing) {
        const inp = document.createElement('input');
        inp.type = 'hidden';
        inp.name = 'deleted_subkegiatan_ids[]';
        inp.value = existing;
        deletedContainer.appendChild(inp);
      }
      sItem.remove();
      updateAllAfterMutate();
      return;
    }
  });

  // ---------- input handlers: format rupiah display & sync hidden ----------
  document.addEventListener('input', function (e) {
    const t = e.target;
    if (!t.classList) return;
    if (t.classList.contains('target-display')) {
      // capture raw digits
      const raw = digitsOnly(t.value);
      // update hidden in same sub
      const sub = t.closest('.subkegiatan-item');
      if (sub) {
        const hidden = sub.querySelector('.target-hidden');
        if (hidden) hidden.value = raw || '';
      }
      // update display formatting
      t.value = raw ? ('Rp ' + formatRupiah(raw)) : '';
    }
  });

  // focusin: show raw digits for easier edit
  document.addEventListener('focusin', function (e) {
    const t = e.target;
    if (t.classList && t.classList.contains('target-display')) {
      const sub = t.closest('.subkegiatan-item');
      if (sub) {
        const hidden = sub.querySelector('.target-hidden');
        const raw = hidden ? (hidden.value || '') : '';
        t.value = raw ? formatRupiah(raw) : '';
      }
    }
  });

  // focusout: reformat to Rp ...
  document.addEventListener('focusout', function (e) {
    const t = e.target;
    if (t.classList && t.classList.contains('target-display')) {
      const sub = t.closest('.subkegiatan-item');
      if (sub) {
        const hidden = sub.querySelector('.target-hidden');
        const raw = hidden ? (hidden.value || '') : '';
        t.value = raw ? ('Rp ' + formatRupiah(raw)) : '';
      }
    }
  });

  // ---------- before submit ----------
  if (form) {
    form.addEventListener('submit', function () {
      // ensure names & hidden sync
      updateAllNames();
      // make sure every sub has its hidden set
      programContainer.querySelectorAll('.subkegiatan-item').forEach(sEl => {
        const display = sEl.querySelector('.target-display');
        const hidden = sEl.querySelector('.target-hidden');
        if (display && hidden) {
          hidden.value = digitsOnly(display.value) || hidden.value || '';
        }
      });
    });
  }
});
