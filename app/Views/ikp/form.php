<?php
/**
 * Form tambah / ubah IKP — AdminOpd\IkpController::tambah|edit.
 *
 * Nilai awal: old() (setelah gagal validasi) lalu baris IKP. Semua pilihan
 * ber-id divalidasi ulang di server (IkpController::bacaForm) — daftar di
 * sini hanya kemudahan.
 *
 * @var string $mode      tambah|edit
 * @var array  $ikp
 * @var array  $daftarPu, $daftarSp, $daftarMisi, $daftarSat
 * @var array  $kategoriList, $kategoriMeta, $metodeList, $metodeJelas
 * @var string $pjTeks    label awal Select2 penanggung jawab
 * @var ?array $bukuSaku  rujukan Buku Saku yang tersimpan
 */
$js  = static fn ($v) => json_encode($v, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
$v   = static fn (string $k) => (string) old($k, isset($ikp[$k]) && $ikp[$k] !== null ? (string) $ikp[$k] : '');
$num = static function (string $k) use ($ikp): string {
    $lama = old($k);
    if ($lama !== null) {
        return (string) $lama;
    }

    return isset($ikp[$k]) && $ikp[$k] !== null && $ikp[$k] !== '' ? ikp_fmt((float) $ikp[$k], 4) : '';
};
$kategori = $v('kategori') !== '' ? $v('kategori') : 'program_unggulan';
$puDipilih = $v('program_unggulan_id');
$aksi = $mode === 'edit'
    ? $u('adminopd/ikp/update/' . (int) $ikp['id'])
    : $u('adminopd/ikp/save');
$satuanTeks = $v('satuan_teks');
$satuanId   = $v('satuan_id');
?>
<?= $this->include('ikp/_kepala') ?>

<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
    <div>
        <div class="small text-secondary">Kolom bertanda <span class="text-danger">*</span> wajib diisi. Target tahunan & bulanan diisi setelah IKP tersimpan.</div>
    </div>
    <a href="<?= esc($u('adminopd/ikp'), 'attr') ?>" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i>Kembali ke daftar</a>
</div>

<form method="post" action="<?= esc($aksi, 'attr') ?>" id="form-ikp" novalidate
      data-url-buku-saku="<?= esc($u('adminopd/ikp/buku-saku'), 'attr') ?>"
      data-url-pegawai="<?= esc($u('adminopd/ikp/pegawai'), 'attr') ?>"
      data-url-node="<?= esc($u('adminopd/ikp/node'), 'attr') ?>"
      data-simpul="<?= esc($v('cascading_sasaran_id'), 'attr') ?>"
      data-indikator="<?= esc($v('cascading_indikator_id'), 'attr') ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="buku_saku_id" id="buku_saku_id" value="<?= esc($v('buku_saku_id'), 'attr') ?>">

    <!-- 1. Kategori -->
    <section class="ikp-bagian">
        <h3><span class="no">1</span>Kategori IKP</h3>
        <div class="ket">Pilih asal indikator ini. Kategori menentukan pengelompokan di rekap dan di Lampiran PK.</div>
        <div class="ikp-katpilih" role="radiogroup" aria-label="Kategori IKP">
            <?php foreach ($kategoriList as $k => $label): $m = $kategoriMeta[$k]; ?>
                <label>
                    <input type="radio" name="kategori" value="<?= $k ?>" <?= $kategori === $k ? 'checked' : '' ?> required>
                    <span class="kic" style="background: <?= esc($m['warna'], 'attr') ?>; color: <?= esc($m['warna'], 'attr') ?>"><i class="fas <?= $m['ikon'] ?>" style="color:#fff"></i></span>
                    <span class="ktx">
                        <span class="knm d-block"><?= esc($label) ?></span>
                        <span class="kjl d-block"><?= esc($m['jelas']) ?></span>
                    </span>
                </label>
            <?php endforeach; ?>
        </div>
        <div class="mb-3" id="blok-dasar" <?= str_starts_with($kategori, 'penugasan_') ? '' : 'hidden' ?>>
            <label class="form-label" for="dasar_penugasan">Dasar penugasan</label>
            <input type="text" class="form-control" name="dasar_penugasan" id="dasar_penugasan" maxlength="255"
                   value="<?= esc($v('dasar_penugasan'), 'attr') ?>"
                   placeholder="mis. Arahan Bupati pada Rapat Pimpinan 12 Mei 2026 / Surat Bupati Nomor …">
            <div class="form-text">Sebutkan arahan atau surat yang menjadi dasar penugasan ini.</div>
        </div>
    </section>

    <!-- 2. Indikator -->
    <section class="ikp-bagian">
        <h3><span class="no">2</span>Indikator yang diukur</h3>
        <div class="ket">Rumuskan hasil yang bisa dihitung setiap bulan. Untuk Program Unggulan, ketik minimal 3 huruf untuk mencari rumusan resmi di Buku Saku.</div>

        <div class="mb-3 position-relative">
            <label class="form-label wajib" for="output_prioritas">Indikator IKP (output prioritas)</label>
            <textarea class="form-control" name="output_prioritas" id="output_prioritas" rows="2" maxlength="1000" required
                      autocomplete="off" aria-autocomplete="list" aria-controls="saran-bs"
                      placeholder="mis. Jumlah nasabah aktif bank sampah"><?= esc($v('output_prioritas')) ?></textarea>
            <div class="ikp-saran" id="saran-bs" role="listbox" hidden></div>
            <div class="d-flex flex-wrap gap-2 align-items-center mt-1">
                <span class="form-text m-0" id="bs-petunjuk"><i class="fas fa-book-open me-1"></i>Saran Buku Saku muncul saat Anda mengetik. Abaikan bila rumusan Anda berbeda.</span>
                <span class="ikp-bs-tanda" id="bs-tanda" <?= $bukuSaku ? '' : 'hidden' ?>>
                    <i class="fas fa-link"></i><span id="bs-tanda-teks"><?= $bukuSaku ? 'Terhubung ke Buku Saku #' . (int) $bukuSaku['id'] : '' ?></span>
                    <button type="button" class="btn btn-link btn-sm p-0 ms-1 text-danger" id="bs-lepas" title="Lepaskan tautan Buku Saku"><i class="fas fa-xmark"></i></button>
                </span>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label wajib" for="satuan_id">Satuan</label>
                <select class="form-select" name="satuan_id" id="satuan_id" data-no-select2>
                    <option value="">— pilih satuan —</option>
                    <?php foreach ($daftarSat as $s): ?>
                        <option value="<?= (int) $s['id'] ?>" <?= $satuanId === (string) $s['id'] ? 'selected' : '' ?>><?= esc($s['satuan']) ?><?= $s['tipe'] === 'predikat' ? ' (predikat)' : '' ?></option>
                    <?php endforeach; ?>
                </select>
                <div class="mt-2" id="blok-satuan-teks" <?= ($satuanTeks !== '' && $satuanId === '') ? '' : 'hidden' ?>>
                    <input type="text" class="form-control" name="satuan_teks" id="satuan_teks" maxlength="100"
                           value="<?= esc($satuanTeks, 'attr') ?>" placeholder="Tulis satuan, mis. Unit Sekolah">
                </div>
                <button type="button" class="btn btn-link btn-sm px-0" id="tombol-satuan-teks">
                    <?= ($satuanTeks !== '' && $satuanId === '') ? 'Pilih dari daftar satuan' : 'Satuan tidak ada di daftar? Tulis sendiri' ?>
                </button>
            </div>
            <div class="col-md-6">
                <label class="form-label wajib" for="metode">Metode perhitungan</label>
                <select class="form-select" name="metode" id="metode" data-no-select2 required>
                    <option value="">— pilih metode —</option>
                    <?php foreach ($metodeList as $k => $label): ?>
                        <option value="<?= $k ?>" <?= $v('metode') === $k ? 'selected' : '' ?>><?= esc($metodeJelas[$k]['judul'] ?? $label) ?></option>
                    <?php endforeach; ?>
                </select>
                <div class="ikp-metode-ket" id="metode-ket"></div>
            </div>
            <div class="col-6 col-md-4">
                <label class="form-label" for="baseline">Baseline (kondisi awal)</label>
                <input type="text" class="form-control isian w-100 text-start" data-nol="sah" name="baseline" id="baseline" value="<?= esc($num('baseline'), 'attr') ?>" placeholder="mis. 200">
                <div class="form-text">Dipakai Bagi Rata metode posisi.</div>
            </div>
            <div class="col-6 col-md-4">
                <label class="form-label" for="target_5_tahun">Target 5 tahun (angka)</label>
                <input type="text" class="form-control isian w-100 text-start" name="target_5_tahun" id="target_5_tahun" value="<?= esc($num('target_5_tahun'), 'attr') ?>" placeholder="mis. 1.000">
                <div class="form-text" id="t5-ket">Titik = ribuan, koma = desimal.</div>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="target_5_tahun_teks">Target 5 tahun (uraian)</label>
                <input type="text" class="form-control" name="target_5_tahun_teks" id="target_5_tahun_teks" maxlength="255" value="<?= esc($v('target_5_tahun_teks'), 'attr') ?>" placeholder="bila target berupa kalimat">
                <div class="form-text">Isi salah satu: angka atau uraian.</div>
            </div>
        </div>
    </section>

    <!-- 3. Keterkaitan RPJMD -->
    <section class="ikp-bagian">
        <h3><span class="no">3</span>Keterkaitan dengan RPJMD &amp; Program Unggulan</h3>
        <div class="ket">Mengisi kolom Lampiran II Perjanjian Kinerja (Misi, 9 Program Unggulan, 10 Sasaran Pembangunan, outcome, program OPD).</div>

        <label class="form-label <?= $kategori === 'program_unggulan' ? 'wajib' : '' ?>" id="label-pu">Program Unggulan Bupati</label>
        <div class="ikp-katpilih mb-3" style="grid-template-columns: repeat(auto-fill, minmax(190px, 1fr));" role="radiogroup" aria-labelledby="label-pu">
            <?php foreach ($daftarPu as $pu): ?>
                <label class="py-2">
                    <input type="radio" name="program_unggulan_id" value="<?= (int) $pu['id'] ?>" <?= $puDipilih === (string) $pu['id'] ? 'checked' : '' ?>>
                    <span class="kic" style="width:30px;height:30px;background: <?= esc($pu['warna'] ?: '#00743e', 'attr') ?>; color: <?= esc($pu['warna'] ?: '#00743e', 'attr') ?>"><i class="fas <?= esc($pu['ikon'] ?: 'fa-star', 'attr') ?>" style="color:#fff;font-size:.8rem"></i></span>
                    <span class="ktx knm align-self-center" style="font-size:.84rem"><?= esc($pu['nama']) ?></span>
                </label>
            <?php endforeach; ?>
            <label class="py-2">
                <input type="radio" name="program_unggulan_id" value="" <?= $puDipilih === '' ? 'checked' : '' ?>>
                <span class="kic" style="width:30px;height:30px;background:#8a968f;color:#8a968f"><i class="fas fa-minus" style="color:#fff;font-size:.8rem"></i></span>
                <span class="ktx knm align-self-center" style="font-size:.84rem">Tidak terkait</span>
            </label>
        </div>

        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label" for="sasaran_pembangunan_id">Sasaran Pembangunan (10 Sasaran)</label>
                <select class="form-select" name="sasaran_pembangunan_id" id="sasaran_pembangunan_id" data-no-select2>
                    <option value="">— tidak dipilih —</option>
                    <?php foreach ($daftarSp as $sp): ?>
                        <option value="<?= (int) $sp['id'] ?>" <?= $v('sasaran_pembangunan_id') === (string) $sp['id'] ? 'selected' : '' ?>><?= (int) $sp['urutan'] ?>. <?= esc($sp['nama']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="rpjmd_misi_id">Misi RPJMD</label>
                <select class="form-select" name="rpjmd_misi_id" id="rpjmd_misi_id" data-no-select2>
                    <option value="">— tidak dipilih —</option>
                    <?php foreach ($daftarMisi as $i => $m): ?>
                        <option value="<?= (int) $m['id'] ?>" <?= $v('rpjmd_misi_id') === (string) $m['id'] ? 'selected' : '' ?>>Misi <?= $i + 1 ?>: <?= esc(mb_strimwidth((string) $m['misi'], 0, 110, '…')) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="outcome">Outcome</label>
                <textarea class="form-control" name="outcome" id="outcome" rows="2" maxlength="2000"><?= esc($v('outcome')) ?></textarea>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="indikator_outcome">Indikator outcome</label>
                <textarea class="form-control" name="indikator_outcome" id="indikator_outcome" rows="2" maxlength="2000"><?= esc($v('indikator_outcome')) ?></textarea>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="program_opd">Program OPD</label>
                <textarea class="form-control" name="program_opd" id="program_opd" rows="2" maxlength="2000" placeholder="mis. Program Pengelolaan Persampahan"><?= esc($v('program_opd')) ?></textarea>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="bidang_urusan">Bidang urusan</label>
                <input type="text" class="form-control" name="bidang_urusan" id="bidang_urusan" maxlength="255" value="<?= esc($v('bidang_urusan'), 'attr') ?>" placeholder="mis. Lingkungan Hidup">
            </div>
        </div>
        <div class="mb-3"></div>
    </section>

    <!-- 4. Tautan pohon kinerja & penanggung jawab -->
    <section class="ikp-bagian">
        <h3><span class="no">4</span>Tautan pohon kinerja &amp; penanggung jawab</h3>
        <div class="ket">Hubungkan IKP dengan simpul pohon kinerja (Cascading) perangkat daerah ini dan tetapkan pegawai penanggung jawabnya. Data ini dipakai eKin untuk menurunkan SKP.</div>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label" for="cascading_sasaran_id">Simpul pohon kinerja</label>
                <select class="form-select" name="cascading_sasaran_id" id="cascading_sasaran_id" data-no-select2>
                    <option value="">Memuat simpul…</option>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="cascading_indikator_id">Indikator simpul</label>
                <select class="form-select" name="cascading_indikator_id" id="cascading_indikator_id" data-no-select2 disabled>
                    <option value="">— pilih simpul dulu —</option>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="pj_pegawai_id">Penanggung jawab (pegawai)</label>
                <select class="form-select" name="pj_pegawai_id" id="pj_pegawai_id" data-no-select2>
                    <?php if ($v('pj_pegawai_id') !== ''): ?>
                        <option value="<?= (int) $v('pj_pegawai_id') ?>" selected><?= esc($pjTeks !== '' ? $pjTeks : 'Pegawai #' . $v('pj_pegawai_id')) ?></option>
                    <?php endif; ?>
                </select>
                <div class="form-text">Cari nama atau jabatan pegawai perangkat daerah ini.</div>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="pj_jabatan_teks">Jabatan penanggung jawab</label>
                <input type="text" class="form-control" name="pj_jabatan_teks" id="pj_jabatan_teks" maxlength="255" value="<?= esc($v('pj_jabatan_teks'), 'attr') ?>" placeholder="terisi otomatis dari jabatan pegawai">
            </div>
        </div>
        <div class="mb-3"></div>
    </section>

    <div class="ikp-tombol-bawah">
        <button type="submit" class="btn btn-success"><i class="fas fa-floppy-disk me-1"></i><?= $mode === 'edit' ? 'Simpan perubahan' : 'Simpan & lanjut isi target' ?></button>
        <a href="<?= esc($u('adminopd/ikp'), 'attr') ?>" class="btn btn-outline-secondary">Batal</a>
        <?php if ($mode === 'edit'): ?>
            <a href="<?= esc($u('adminopd/ikp/target/' . (int) $ikp['id']), 'attr') ?>" class="btn btn-link ms-auto"><i class="fas fa-sliders me-1"></i>Ke target &amp; breakdown</a>
        <?php endif; ?>
    </div>
</form>

<script>
    window.IKP_FORM = {
        metodeJelas: <?= $js($metodeJelas) ?>,
        satuan: <?= $js(array_map(static fn ($s) => ['id' => (int) $s['id'], 'nama' => (string) $s['satuan']], $daftarSat)) ?>
    };
</script>

<?php /* defer: berjalan setelah jQuery/Select2 di footer dimuat, sebelum DOMContentLoaded. */ ?>
<script defer src="<?= base_url('assets/js/adminopd/ikp/ikp-angka.js') ?>?v=<?= @filemtime(FCPATH . 'assets/js/adminopd/ikp/ikp-angka.js') ?>"></script>
<script defer src="<?= base_url('assets/js/adminopd/ikp/ikp-form.js') ?>?v=<?= @filemtime(FCPATH . 'assets/js/adminopd/ikp/ikp-form.js') ?>"></script>

<?= $this->include('templates/shell_bawah') ?>
