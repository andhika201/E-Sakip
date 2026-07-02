<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Analisis AI - <?= esc(setting('app_name', 'e-SAKIP')) ?></title>
  <?= $this->include('adminKabupaten/templates/style.php'); ?>
  <style>
    .ai-result {
      border: 1px solid #e6ece8;
      border-radius: 14px;
      padding: 22px 26px;
      background: #fbfdfc;
      line-height: 1.7;
      color: #344039;
    }
    .ai-result h1 { font-size: 1.3rem; }
    .ai-result h2 { font-size: 1.12rem; font-weight: 800; color: #00743e; margin: 20px 0 8px; padding-bottom: 6px; border-bottom: 1px solid #e6ece8; }
    .ai-result h2:first-child { margin-top: 0; }
    .ai-result h3 { font-size: 1rem; font-weight: 700; color: #16321f; margin-top: 12px; }
    .ai-result ul, .ai-result ol { padding-left: 1.3rem; }
    .ai-result li { margin-bottom: .25rem; }
    .ai-result p { margin-bottom: .6rem; }
    .ai-result strong { color: #15311f; }
    .ai-result code { background: #eef3ef; padding: 1px 5px; border-radius: 5px; }
    .ai-result table { width: 100%; border-collapse: collapse; margin: 10px 0; font-size: .92rem; }
    .ai-result th, .ai-result td { border: 1px solid #e3e8e4; padding: 7px 10px; text-align: left; vertical-align: top; word-break: break-word; }
    .ai-result thead th { background: #eef6f0; color: #15311f; }
    .ai-empty { text-align: center; color: #8a958d; padding: 48px 20px; }
    .ai-empty i { opacity: .4; }
  </style>
</head>

<body class="bg-light min-vh-100 d-flex flex-column position-relative">

  <?= $this->include('adminKabupaten/templates/sidebar.php'); ?>

  <div id="main-content" class="content-wrapper d-flex flex-column" style="transition: margin-left 0.3s ease;">

    <?= $this->include('adminKabupaten/templates/header.php'); ?>

    <main class="flex-fill p-4 mt-2">
      <div class="bg-white rounded shadow p-4">
        <h2 class="h3 fw-bold text-success text-center mb-4">Analisis AI</h2>
        <p class="text-muted text-center mb-4" style="margin-top:-12px;">
          Analisis otomatis Pohon Kinerja &amp; Cascading menggunakan Google Gemini.
        </p>

        <?php if (!$hasKey): ?>
          <div class="alert alert-warning">
            <i class="fas fa-triangle-exclamation me-1"></i>
            API key Gemini belum diatur.
            <?php if (session('role') === 'admin'): ?>
              <a href="<?= base_url('adminkab/pengaturan') ?>" class="fw-semibold">Atur di Pengaturan Aplikasi → Integrasi AI</a>.
            <?php else: ?>
              Silakan hubungi Super Admin untuk mengaktifkannya.
            <?php endif; ?>
          </div>
        <?php endif; ?>

        <div class="row g-2 mb-3 align-items-end">
          <div class="col-12 col-md-4">
            <label class="form-label fw-semibold">Periode RPJMD</label>
            <select id="aiPeriode" class="form-select">
              <option value="">-- Pilih Periode --</option>
              <?php foreach (($periode_master ?? []) as $p): ?>
                <?php $key = $p['tahun_mulai'] . '-' . $p['tahun_akhir']; ?>
                <option value="<?= $key ?>"><?= $p['tahun_mulai'] . ' - ' . $p['tahun_akhir'] ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-12 col-md-6">
            <label class="form-label fw-semibold">Pertanyaan khusus <span class="text-muted">(opsional)</span></label>
            <input type="text" id="aiQuestion" class="form-control" placeholder="mis. fokuskan pada indikator tanpa OPD / program">
          </div>
          <div class="col-12 col-md-2 d-grid">
            <button id="aiRun" class="btn btn-success" <?= $hasKey ? '' : 'disabled' ?>>
              <i class="fas fa-wand-magic-sparkles me-1"></i> Generate
            </button>
          </div>
        </div>

        <div id="aiResult" class="ai-result" hidden></div>
        <div id="aiEmpty" class="ai-empty">
          <i class="fas fa-robot fa-2x d-block mb-2"></i>
          Pilih periode lalu klik <strong>Generate</strong> untuk membuat analisis.
        </div>
      </div>
    </main>

    <?= $this->include('adminKabupaten/templates/footer.php'); ?>

  </div>

  <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
  <script>
    (function () {
      const btn = document.getElementById('aiRun');
      const out = document.getElementById('aiResult');
      const empty = document.getElementById('aiEmpty');

      if (window.marked && marked.setOptions) marked.setOptions({ gfm: true, breaks: true });

      // Perbaiki tabel Markdown dari AI agar selalu valid (baris pemisah & jumlah kolom)
      function normalizeMd(md) {
        if (!md) return '';
        const lines = md.replace(/\r\n?/g, '\n').split('\n');
        const cells = s => { let t = s.trim(); if (t.startsWith('|')) t = t.slice(1); if (t.endsWith('|')) t = t.slice(0, -1); return t.split('|'); };
        const isRow = s => s.trim().startsWith('|') && (s.match(/\|/g) || []).length >= 2;
        const isDelim = s => /-/.test(s) && /^\s*\|?\s*:?-+:?\s*(\|\s*:?-+:?\s*)*\|?\s*$/.test(s);
        const res = [];
        for (let i = 0; i < lines.length; i++) {
          const line = lines[i], next = lines[i + 1];
          if (isRow(line) && next !== undefined && isDelim(next)) {
            if (res.length && res[res.length - 1].trim() !== '') res.push('');
            const cols = cells(line).length;
            const aligns = cells(next).map(c => { c = c.trim(); const l = c.startsWith(':'), r = c.endsWith(':'); return (l && r) ? ':-:' : (r ? '--:' : (l ? ':--' : '---')); });
            const fixed = []; for (let k = 0; k < cols; k++) fixed.push(aligns[k] || '---');
            res.push(line, '| ' + fixed.join(' | ') + ' |');
            i++; continue;
          }
          res.push(line);
        }
        return res.join('\n');
      }
      function render(text) {
        return window.marked ? marked.parse(normalizeMd(text)) : '<pre style="white-space:pre-wrap">' + text + '</pre>';
      }

      btn.addEventListener('click', async function () {
        const periode = document.getElementById('aiPeriode').value;
        const question = document.getElementById('aiQuestion').value;
        if (!periode) { alert('Silakan pilih periode terlebih dahulu.'); return; }

        const csrfName = document.querySelector('meta[name="csrf-name"]')?.content;
        const csrfHash = document.querySelector('meta[name="csrf-hash"]')?.content;
        const fd = new FormData();
        fd.append('periode', periode);
        fd.append('question', question);
        if (csrfName) fd.append(csrfName, csrfHash);

        const oldHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Menganalisis...';
        empty.hidden = true;
        out.hidden = false;
        out.innerHTML = '<div class="text-muted d-flex align-items-center gap-2"><span class="spinner-border spinner-border-sm"></span> AI sedang menganalisis data…</div>';

        try {
          const res = await fetch('<?= base_url('analisis-ai/run') ?>', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: fd
          });
          const json = await res.json();
          if (json.csrf) {
            const m = document.querySelector('meta[name="csrf-hash"]');
            if (m) m.setAttribute('content', json.csrf);
          }
          if (json.ok) {
            out.innerHTML = render(json.text);
          } else {
            out.innerHTML = '<div class="alert alert-danger mb-0">' + (json.message || 'Gagal membuat analisis.') + '</div>';
          }
        } catch (e) {
          out.innerHTML = '<div class="alert alert-danger mb-0">Gagal menghubungi server.</div>';
        } finally {
          btn.disabled = false;
          btn.innerHTML = oldHtml;
        }
      });
    })();
  </script>
</body>

</html>
