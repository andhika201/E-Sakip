<?php

/**
 * Form IKU standalone — dipakai bersama form tambah & edit, admin kabupaten & OPD.
 *
 * Variabel yang diharapkan:
 * @var array|null $iku            data sasaran + indikator (null = form tambah)
 * @var array      $satuan_options daftar satuan untuk dropdown
 * @var array      $opd_list       daftar OPD (hanya dipakai kalau $is_lintas_opd)
 * @var bool       $is_lintas_opd  user boleh memilih OPD pemilik IKU
 * @var string     $action_url     URL tujuan submit
 * @var string     $back_url       URL tombol kembali
 */
$iku            = $iku ?? null;
$satuan_options = $satuan_options ?? [];
$opd_list       = $opd_list ?? [];
$is_lintas_opd  = $is_lintas_opd ?? false;
$action_url     = $action_url ?? '';
$back_url       = $back_url ?? '';

$isEdit = !empty($iku['id']);

// Nilai lama (setelah validasi gagal) diprioritaskan supaya isian user tidak hilang.
$tahunMulai = old('tahun_mulai', $iku['tahun_mulai'] ?? '');
$tahunAkhir = old('tahun_akhir', $iku['tahun_akhir'] ?? '');
$sasaranTxt = old('sasaran', $iku['sasaran'] ?? '');
$opdTerpilih = old('opd_id', $iku['opd_id'] ?? '');

/**
 * Susun daftar indikator yang dirender awal:
 * old input > data tersimpan > satu baris kosong.
 */
$indikatorAwal = old('indikator');
if (!is_array($indikatorAwal) || empty($indikatorAwal)) {
    $indikatorAwal = [];

    foreach ($iku['indikator'] ?? [] as $ind) {
        $target = [];
        foreach ($ind['target'] ?? [] as $tahun => $nilai) {
            $target[(int) $tahun] = $nilai;
        }

        $indikatorAwal[] = [
            'id'                  => $ind['id'] ?? '',
            'indikator'           => $ind['indikator'] ?? '',
            'definisi'            => $ind['definisi'] ?? '',
            'rumusan_perhitungan' => $ind['rumusan_perhitungan'] ?? '',
            'satuan'              => $ind['satuan'] ?? '',
            'sumber_data'         => $ind['sumber_data'] ?? '',
            'penanggung_jawab'    => $ind['penanggung_jawab'] ?? '',
            'jenis_indikator'     => $ind['jenis_indikator'] ?? '',
            'baseline'            => $ind['baseline'] ?? '',
            'status'              => $ind['status'] ?? 'draft',
            'target'              => $target,
        ];
    }

    if (empty($indikatorAwal)) {
        $indikatorAwal[] = [
            'id' => '', 'indikator' => '', 'definisi' => '', 'rumusan_perhitungan' => '',
            'satuan' => '', 'sumber_data' => '', 'penanggung_jawab' => '',
            'jenis_indikator' => '', 'baseline' => '', 'status' => 'draft', 'target' => [],
        ];
    }
}

// Tahun-tahun yang dirender di baris target saat halaman pertama dibuka.
$tahunAwal = [];
if ($tahunMulai !== '' && $tahunAkhir !== '' && (int) $tahunAkhir >= (int) $tahunMulai) {
    $tahunAwal = range((int) $tahunMulai, (int) $tahunAkhir);
}
?>

<form method="post" action="<?= esc($action_url, 'attr') ?>" id="iku-form">
    <?= csrf_field() ?>
    <?php if ($isEdit): ?>
        <input type="hidden" name="iku_sasaran_id" value="<?= (int) $iku['id'] ?>">
    <?php endif; ?>

    <!-- ================= SASARAN ================= -->
    <section class="mb-4">
        <h2 class="h5 fw-semibold mb-3">Sasaran IKU</h2>

        <div class="row g-3">
            <?php if ($is_lintas_opd): ?>
                <div class="col-md-12">
                    <label class="form-label">Perangkat Daerah Pemilik IKU <span class="text-danger">*</span></label>
                    <select name="opd_id" class="form-select select2" required>
                        <option value="">Pilih Perangkat Daerah</option>
                        <?php foreach ($opd_list as $opd): ?>
                            <option value="<?= (int) $opd['id'] ?>"
                                <?= (string) $opdTerpilih === (string) $opd['id'] ? 'selected' : '' ?>>
                                <?= esc($opd['nama_opd']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>

            <div class="col-md-12">
                <label class="form-label">Sasaran <span class="text-danger">*</span></label>
                <textarea name="sasaran" class="form-control" rows="2"
                          placeholder="Contoh: Meningkatnya kualitas layanan aplikasi informatika pemerintah daerah"
                          required><?= esc($sasaranTxt) ?></textarea>
                <small class="text-muted">
                    Sasaran diisi langsung di sini — tidak lagi mengambil dari Renstra/RPJMD.
                </small>
            </div>

            <div class="col-md-3">
                <label class="form-label">Tahun Mulai <span class="text-danger">*</span></label>
                <input type="number" name="tahun_mulai" id="tahun_mulai" class="form-control"
                       value="<?= esc($tahunMulai) ?>" placeholder="Contoh: 2025" min="1900" max="2999" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Tahun Akhir <span class="text-danger">*</span></label>
                <input type="number" name="tahun_akhir" id="tahun_akhir" class="form-control"
                       value="<?= esc($tahunAkhir) ?>" placeholder="Contoh: 2029" min="1900" max="2999" required>
                <small class="text-muted">Terisi otomatis 5 tahun, masih bisa diubah manual.</small>
            </div>
        </div>
    </section>

    <!-- ================= INDIKATOR ================= -->
    <section>
        <h2 class="h5 fw-semibold mb-3">Indikator Kinerja Utama</h2>

        <div id="indikator-container">
            <?php foreach (array_values($indikatorAwal) as $i => $ind): ?>
                <div class="indikator-item border rounded p-3 bg-light mb-3" data-indeks="<?= $i ?>">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <label class="fw-medium indikator-title">Indikator <?= $i + 1 ?></label>
                        <button type="button" class="btn btn-outline-danger btn-sm remove-indikator" title="Hapus indikator">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>

                    <?php if (!empty($ind['id'])): ?>
                        <input type="hidden" name="indikator[<?= $i ?>][id]" value="<?= (int) $ind['id'] ?>">
                    <?php endif; ?>

                    <div class="mb-3">
                        <label class="form-label">Indikator <span class="text-danger">*</span></label>
                        <textarea name="indikator[<?= $i ?>][indikator]" class="form-control" rows="2"
                                  placeholder="Contoh: Indeks SPBE" required><?= esc($ind['indikator'] ?? '') ?></textarea>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Definisi Operasional</label>
                            <textarea name="indikator[<?= $i ?>][definisi]" class="form-control" rows="3"
                                      placeholder="Penjelasan makna indikator"><?= esc($ind['definisi'] ?? '') ?></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Formula / Rumusan Perhitungan</label>
                            <textarea name="indikator[<?= $i ?>][rumusan_perhitungan]" class="form-control" rows="3"
                                      placeholder="Cara menghitung capaian indikator"><?= esc($ind['rumusan_perhitungan'] ?? '') ?></textarea>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Satuan</label>
                            <select name="indikator[<?= $i ?>][satuan]" class="form-select select2 satuan-select">
                                <option value="">Pilih Satuan</option>
                                <?php foreach ($satuan_options as $s): ?>
                                    <option value="<?= (int) $s['id'] ?>"
                                        <?= (string) ($ind['satuan'] ?? '') === (string) $s['id'] ? 'selected' : '' ?>>
                                        <?= esc($s['satuan']) ?>
                                    </option>
                                <?php endforeach; ?>
                                <?php
                                // Satuan lama yang tersimpan sebagai teks bebas (bukan id) tetap dipertahankan.
                                $satuanTersimpan = (string) ($ind['satuan'] ?? '');
                                if ($satuanTersimpan !== '' && !ctype_digit($satuanTersimpan)):
                                    ?>
                                    <option value="<?= esc($satuanTersimpan, 'attr') ?>" selected>
                                        <?= esc($satuanTersimpan) ?>
                                    </option>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Jenis Indikator</label>
                            <select name="indikator[<?= $i ?>][jenis_indikator]" class="form-select select2">
                                <option value="">Pilih Jenis Indikator</option>
                                <option value="positif" <?= ($ind['jenis_indikator'] ?? '') === 'positif' ? 'selected' : '' ?>>
                                    Indikator Positif (naik = baik)
                                </option>
                                <option value="negatif" <?= ($ind['jenis_indikator'] ?? '') === 'negatif' ? 'selected' : '' ?>>
                                    Indikator Negatif (turun = baik)
                                </option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Kondisi Awal (Baseline)</label>
                            <input type="text" name="indikator[<?= $i ?>][baseline]" class="form-control"
                                   value="<?= esc($ind['baseline'] ?? '') ?>" maxlength="50" placeholder="Opsional">
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Sumber Data</label>
                            <textarea name="indikator[<?= $i ?>][sumber_data]" class="form-control" rows="2"
                                      placeholder="Contoh: Hasil evaluasi KemenPAN-RB"><?= esc($ind['sumber_data'] ?? '') ?></textarea>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Penanggung Jawab</label>
                            <input type="text" name="indikator[<?= $i ?>][penanggung_jawab]" class="form-control"
                                   value="<?= esc($ind['penanggung_jawab'] ?? '') ?>" maxlength="255"
                                   placeholder="Contoh: Bidang E-Government">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Status</label>
                            <select name="indikator[<?= $i ?>][status]" class="form-select">
                                <option value="draft" <?= strtolower((string) ($ind['status'] ?? 'draft')) !== 'selesai' ? 'selected' : '' ?>>Draft</option>
                                <option value="selesai" <?= strtolower((string) ($ind['status'] ?? '')) === 'selesai' ? 'selected' : '' ?>>Selesai</option>
                            </select>
                        </div>
                    </div>

                    <div class="target-section">
                        <label class="form-label fw-medium">Target Capaian per Tahun</label>
                        <div class="target-container row g-2">
                            <?php if (empty($tahunAwal)): ?>
                                <div class="col-12">
                                    <small class="text-muted target-kosong">Isi Tahun Mulai dan Tahun Akhir dulu untuk menampilkan kolom target.</small>
                                </div>
                            <?php else: ?>
                                <?php foreach ($tahunAwal as $tahun): ?>
                                    <div class="col-6 col-md-2 target-item">
                                        <label class="form-label small mb-1"><?= esc($tahun) ?></label>
                                        <input type="text" name="indikator[<?= $i ?>][target][<?= (int) $tahun ?>]"
                                               class="form-control form-control-sm" maxlength="100" placeholder="Target"
                                               value="<?= esc($ind['target'][$tahun] ?? '') ?>">
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="text-end mt-2">
            <button type="button" id="add-indikator" class="btn btn-info btn-sm">
                <i class="fas fa-plus me-1"></i> Tambah Indikator
            </button>
        </div>
    </section>

    <div class="d-flex justify-content-between mt-4">
        <a href="<?= esc($back_url, 'attr') ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i> Kembali
        </a>
        <button type="submit" class="btn btn-success">
            <i class="fas fa-save me-1"></i> Simpan
        </button>
    </div>
</form>

<script>
    (function () {
        const container   = document.getElementById('indikator-container');
        const addBtn      = document.getElementById('add-indikator');
        const tahunMulaiEl = document.getElementById('tahun_mulai');
        const tahunAkhirEl = document.getElementById('tahun_akhir');

        // Opsi satuan dirender sekali dari PHP lalu dipakai ulang tiap indikator baru.
        const OPSI_SATUAN = <?= json_encode(
            array_map(static fn($s) => ['id' => (int) $s['id'], 'nama' => $s['satuan']], $satuan_options),
            JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
        ) ?>;

        function opsiSatuanHtml() {
            return '<option value="">Pilih Satuan</option>' +
                OPSI_SATUAN.map(s => `<option value="${s.id}">${s.nama}</option>`).join('');
        }

        function daftarTahun() {
            const mulai = parseInt(tahunMulaiEl.value, 10);
            const akhir = parseInt(tahunAkhirEl.value, 10);
            if (isNaN(mulai) || isNaN(akhir) || akhir < mulai || (akhir - mulai) > 30) return [];
            const tahun = [];
            for (let t = mulai; t <= akhir; t++) tahun.push(t);
            return tahun;
        }

        /** Bangun ulang kolom target mengikuti periode, nilai yang sudah diketik dipertahankan. */
        function segarkanTarget() {
            const tahun = daftarTahun();

            container.querySelectorAll('.indikator-item').forEach(item => {
                const indeks = item.getAttribute('data-indeks');
                const wrap   = item.querySelector('.target-container');
                if (!wrap) return;

                const nilaiLama = {};
                wrap.querySelectorAll('input[type="text"]').forEach(inp => {
                    const cocok = inp.name.match(/\[target\]\[(\d+)\]$/);
                    if (cocok) nilaiLama[cocok[1]] = inp.value;
                });

                if (tahun.length === 0) {
                    wrap.innerHTML = '<div class="col-12"><small class="text-muted target-kosong">' +
                        'Isi Tahun Mulai dan Tahun Akhir dulu untuk menampilkan kolom target.</small></div>';
                    return;
                }

                wrap.innerHTML = tahun.map(t => `
                    <div class="col-6 col-md-2 target-item">
                        <label class="form-label small mb-1">${t}</label>
                        <input type="text" name="indikator[${indeks}][target][${t}]"
                               class="form-control form-control-sm" maxlength="100" placeholder="Target"
                               value="${(nilaiLama[t] || '').replace(/"/g, '&quot;')}">
                    </div>
                `).join('');
            });
        }

        function templateIndikator(indeks) {
            return `
            <div class="indikator-item border rounded p-3 bg-light mb-3" data-indeks="${indeks}">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <label class="fw-medium indikator-title">Indikator ${indeks + 1}</label>
                    <button type="button" class="btn btn-outline-danger btn-sm remove-indikator" title="Hapus indikator">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>

                <div class="mb-3">
                    <label class="form-label">Indikator <span class="text-danger">*</span></label>
                    <textarea name="indikator[${indeks}][indikator]" class="form-control" rows="2"
                              placeholder="Contoh: Indeks SPBE" required></textarea>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Definisi Operasional</label>
                        <textarea name="indikator[${indeks}][definisi]" class="form-control" rows="3"
                                  placeholder="Penjelasan makna indikator"></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Formula / Rumusan Perhitungan</label>
                        <textarea name="indikator[${indeks}][rumusan_perhitungan]" class="form-control" rows="3"
                                  placeholder="Cara menghitung capaian indikator"></textarea>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Satuan</label>
                        <select name="indikator[${indeks}][satuan]" class="form-select select2 satuan-select">
                            ${opsiSatuanHtml()}
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Jenis Indikator</label>
                        <select name="indikator[${indeks}][jenis_indikator]" class="form-select select2">
                            <option value="">Pilih Jenis Indikator</option>
                            <option value="positif">Indikator Positif (naik = baik)</option>
                            <option value="negatif">Indikator Negatif (turun = baik)</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Kondisi Awal (Baseline)</label>
                        <input type="text" name="indikator[${indeks}][baseline]" class="form-control"
                               maxlength="50" placeholder="Opsional">
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Sumber Data</label>
                        <textarea name="indikator[${indeks}][sumber_data]" class="form-control" rows="2"
                                  placeholder="Contoh: Hasil evaluasi KemenPAN-RB"></textarea>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Penanggung Jawab</label>
                        <input type="text" name="indikator[${indeks}][penanggung_jawab]" class="form-control"
                               maxlength="255" placeholder="Contoh: Bidang E-Government">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Status</label>
                        <select name="indikator[${indeks}][status]" class="form-select">
                            <option value="draft" selected>Draft</option>
                            <option value="selesai">Selesai</option>
                        </select>
                    </div>
                </div>

                <div class="target-section">
                    <label class="form-label fw-medium">Target Capaian per Tahun</label>
                    <div class="target-container row g-2"></div>
                </div>
            </div>`;
        }

        function nomoriUlang() {
            container.querySelectorAll('.indikator-item').forEach((item, urut) => {
                const judul = item.querySelector('.indikator-title');
                if (judul) judul.textContent = 'Indikator ' + (urut + 1);
            });
        }

        // Tahun akhir default 5 tahun dari tahun mulai, tapi tetap bisa diubah manual.
        tahunMulaiEl.addEventListener('input', function () {
            const mulai = parseInt(this.value, 10);
            const akhir = parseInt(tahunAkhirEl.value, 10);
            if (!isNaN(mulai) && (isNaN(akhir) || akhir < mulai)) {
                tahunAkhirEl.value = mulai + 4;
            }
            segarkanTarget();
        });
        tahunAkhirEl.addEventListener('input', segarkanTarget);

        addBtn.addEventListener('click', function () {
            let indeksBerikut = 0;
            container.querySelectorAll('.indikator-item').forEach(item => {
                const idx = parseInt(item.getAttribute('data-indeks'), 10);
                if (!isNaN(idx) && idx >= indeksBerikut) indeksBerikut = idx + 1;
            });

            container.insertAdjacentHTML('beforeend', templateIndikator(indeksBerikut));
            nomoriUlang();
            segarkanTarget();

            if (window.jQuery && jQuery.fn.select2) {
                jQuery(container).find('.select2').not('.select2-hidden-accessible')
                    .select2({ width: '100%', dropdownParent: jQuery('body') });
            }
        });

        container.addEventListener('click', function (e) {
            if (!e.target.closest('.remove-indikator')) return;

            const item = e.target.closest('.indikator-item');
            if (!item) return;

            if (container.querySelectorAll('.indikator-item').length === 1) {
                alert('Minimal satu indikator IKU harus ada.');
                return;
            }
            if (!confirm('Hapus indikator ini beserta targetnya?')) return;

            item.remove();
            nomoriUlang();
        });
    })();
</script>
