<?php

namespace App\Controllers\Concerns;

use App\Models\LakipAnalisisModel;
use App\Models\LakipEfisiensiModel;

/**
 * Dua tabel tambahan di bawah tabel utama LAKIP, dipakai bersama oleh
 * AdminKab\LakipController dan AdminOpd\LakipOpdController:
 *
 *   1. Analisis Faktor Pencapaian Kinerja (lakip_analisis_faktor)
 *   2. Efisiensi Program dan Anggaran     (lakip_efisiensi_program)
 *
 * ---------------------------------------------------------------------
 * LINGKUP (mode + OPD) — dipakai untuk filter DATA sekaligus OTORISASI
 *
 *   mode 'kabupaten' (RPJMD) -> opdScope = 0, penambat rpjmd_target_id
 *   mode 'opd'       (RENSTRA)-> opdScope = id OPD, penambat renstra_target_id
 *
 * Hak tulis:
 *   admin_opd / admin_kecamatan : hanya OPD-nya sendiri (dari SESSION, bukan
 *                                 dari request), selalu mode 'opd'
 *   admin_kab / admin           : mode kabupaten, atau mode OPD dengan OPD
 *                                 yang dipilih secara eksplisit
 *   admin_inspektorat           : baca saja (pola lama project)
 *
 * opdScope TIDAK PERNAH diambil mentah dari request untuk role tingkat OPD —
 * lihat lakipScope().
 * ---------------------------------------------------------------------
 */
trait LakipAddendumTrait
{
    protected ?LakipAnalisisModel $analisisModel = null;
    protected ?LakipEfisiensiModel $efisiensiModel = null;

    protected function analisis(): LakipAnalisisModel
    {
        return $this->analisisModel ??= new LakipAnalisisModel();
    }

    protected function efisiensi(): LakipEfisiensiModel
    {
        return $this->efisiensiModel ??= new LakipEfisiensiModel();
    }

    /* =========================================================
     * LINGKUP & OTORISASI
     * =======================================================*/

    /**
     * Tentukan lingkup halaman dari role + request.
     *
     * @return array{
     *     mode: string, tahun: string, opdScope: int|null,
     *     canWrite: bool, role: string, alasan: string|null
     * }
     *     opdScope null = lintas OPD (admin_kab tanpa memilih OPD) -> baca saja
     */
    protected function lakipScope(string $tahun, string $mode): array
    {
        $session = session();
        $role    = (string) $session->get('role');
        $mode    = ($mode === 'kabupaten') ? 'kabupaten' : 'opd';

        $hasil = [
            'mode'     => $mode,
            'tahun'    => (string) $tahun,
            'opdScope' => null,
            'canWrite' => false,
            'role'     => $role,
            'alasan'   => null,
        ];

        if (in_array($role, ['admin_opd', 'admin_kecamatan'], true)) {
            $opdId = (int) $session->get('opd_id');
            if ($opdId <= 0) {
                $hasil['alasan'] = 'Session OPD tidak valid.';

                return $hasil;
            }

            // Role tingkat OPD selalu terkunci pada RENSTRA OPD-nya sendiri.
            $hasil['mode']     = 'opd';
            $hasil['opdScope'] = $opdId;
            $hasil['canWrite'] = true;

            return $hasil;
        }

        if (in_array($role, ['admin_kab', 'admin'], true)) {
            if ($mode === 'kabupaten') {
                $hasil['opdScope'] = 0; // 0 = tingkat kabupaten
                $hasil['canWrite'] = true;

                return $hasil;
            }

            $opdRaw = $this->request->getGet('opd_id');
            $opdId  = ($opdRaw === null || $opdRaw === '') ? null : (int) $opdRaw;
            $hasil['opdScope'] = $opdId;
            $hasil['canWrite'] = !empty($opdId);
            if (empty($opdId)) {
                $hasil['alasan'] = 'Pilih satu OPD dulu untuk bisa menambah data.';
            }

            return $hasil;
        }

        // admin_inspektorat & lainnya: baca saja
        $hasil['alasan'] = 'Peran Anda hanya dapat melihat data ini.';
        if ($mode === 'kabupaten') {
            $hasil['opdScope'] = 0;
        } else {
            $opdRaw = $this->request->getGet('opd_id');
            $hasil['opdScope'] = ($opdRaw === null || $opdRaw === '') ? null : (int) $opdRaw;
        }

        return $hasil;
    }

    /** Lingkup dari POST (untuk aksi simpan/hapus) — tahun & mode ikut dikirim form. */
    protected function lakipScopeFromPost(): array
    {
        $tahun = (string) ($this->request->getPost('tahun') ?? '');
        $mode  = (string) ($this->request->getPost('mode') ?? 'opd');

        $session = session();
        $role    = (string) $session->get('role');
        $mode    = ($mode === 'kabupaten') ? 'kabupaten' : 'opd';

        $hasil = [
            'mode'     => $mode,
            'tahun'    => $tahun,
            'opdScope' => null,
            'canWrite' => false,
            'role'     => $role,
            'alasan'   => null,
        ];

        if (in_array($role, ['admin_opd', 'admin_kecamatan'], true)) {
            $opdId = (int) $session->get('opd_id');
            if ($opdId > 0) {
                $hasil['mode']     = 'opd';
                $hasil['opdScope'] = $opdId;
                $hasil['canWrite'] = true;
            }

            return $hasil;
        }

        if (in_array($role, ['admin_kab', 'admin'], true)) {
            if ($mode === 'kabupaten') {
                $hasil['opdScope'] = 0;
                $hasil['canWrite'] = true;

                return $hasil;
            }

            $opdId = (int) ($this->request->getPost('opd_id') ?? 0);
            $hasil['opdScope'] = $opdId ?: null;
            $hasil['canWrite'] = $opdId > 0;
        }

        return $hasil;
    }

    /**
     * Pastikan sebuah target LAKIP memang ada pada tahun & lingkup tersebut.
     * Inilah pencegah IDOR: id target dari request tidak pernah dipercaya.
     *
     * @return array<string, mixed>|null baris target + indikatornya
     */
    /**
     * Sumber dokumen yang sedang dipilih di layar ('iku'|'renstra'|'rpjmd'),
     * atau null bila layar ini tidak punya pemilih sumber.
     *
     * SEAM. Ditimpa LakipOpdController. Dipakai untuk menyaring penyesuaian
     * kebijakan: id baris IKU dan id baris Renstra hidup di ruang angka yang
     * sama, jadi tanpa penyaring ini penyesuaian milik sumber lain bisa
     * menempel pada baris yang kebetulan ber-id sama.
     */
    protected function sumberDokumenLakip(string $mode): ?string
    {
        return $mode === 'kabupaten' ? 'rpjmd' : null;
    }

    protected function targetLakipSah(
        int $targetId,
        string $mode,
        string $tahun,
        ?int $opdScope,
        ?string $sumber = null
    ): ?array {
        if ($targetId <= 0 || $tahun === '') {
            return null;
        }

        // Sumber IKU: id yang dikirim adalah id INDIKATOR IKU BERJALAN, bukan
        // id target Renstra. Tanpa cabang ini, penyesuaian pada baris IKU
        // ditolak sebagai "tidak ada pada tahun & lingkup ini" — atau, lebih
        // buruk, DITERIMA karena kebetulan ada renstra_target ber-id sama.
        if ($sumber === 'iku') {
            $b = $this->db->table('iku_indikator ii')
                ->select('ii.id AS target_id, ii.indikator AS indikator_sasaran, ii.id AS indikator_id, isa.opd_id')
                ->join('iku_sasaran isa', 'isa.id = ii.iku_sasaran_id', 'left')
                ->where('ii.id', $targetId);

            // Indikator yang SUDAH DIHENTIKAN tetap sah untuk LAKIP tahun
            // pelaporan yang masih dalam masa berlakunya. LAKIP selalu
            // melaporkan tahun yang sudah lewat: begitu revisi IKU disahkan,
            // indikator lama dicap `dihentikan_pada`, padahal tabel utama —
            // yang mengikuti versi dokumen terpilih — masih menampilkannya.
            // Menuntut `dihentikan_pada IS NULL` membuat SELURUH tombol pada
            // tahun itu mati: layar menawarkan baris yang server selalu tolak.
            // `berlaku_sampai` adalah batas resminya; bila kosong, tahun saat
            // dihentikan dipakai sebagai penggantinya. Saringan lingkup
            // (opd_id) di bawah TIDAK dilonggarkan.
            if ($this->db->fieldExists('dihentikan_pada', 'iku_indikator')) {
                $kolomBatas = $this->db->fieldExists('berlaku_sampai', 'iku_indikator')
                    ? 'COALESCE(ii.berlaku_sampai, YEAR(ii.dihentikan_pada))'
                    : 'YEAR(ii.dihentikan_pada)';

                $b->where(
                    '(ii.dihentikan_pada IS NULL OR ' . $kolomBatas . ' >= ' . (int) $tahun . ')',
                    null,
                    false
                );
            }

            // IKU Kabupaten hidup di iku_sasaran.opd_id NULL. Tanpa saringan
            // ini, id indikator IKU OPD mana pun lolos sebagai "milik
            // kabupaten" — lingkupnya bocor lintas pemilik.
            if ($mode === 'kabupaten') {
                $b->where('isa.opd_id IS NULL', null, false);
            } elseif (! empty($opdScope)) {
                $b->where('isa.opd_id', (int) $opdScope);
            }

            $baris = $b->get()->getRowArray();

            if ($baris) {
                $baris['tahun'] = $tahun;
            }

            return $baris ?: null;
        }

        if ($mode === 'kabupaten') {
            return $this->db->table('rpjmd_target rpj')
                ->select('rpj.id AS target_id, rpj.tahun, ris.id AS indikator_id, ris.indikator_sasaran')
                ->join('rpjmd_indikator_sasaran ris', 'ris.id = rpj.indikator_sasaran_id', 'left')
                ->where('rpj.id', $targetId)
                ->where('rpj.tahun', $tahun)
                ->get()->getRowArray() ?: null;
        }

        $b = $this->db->table('renstra_target rt')
            ->select('rt.id AS target_id, rt.tahun, ris.id AS indikator_id, ris.indikator_sasaran, rs.opd_id')
            ->join('renstra_indikator_sasaran ris', 'ris.id = rt.renstra_indikator_id', 'left')
            ->join('renstra_sasaran rs', 'rs.id = ris.renstra_sasaran_id', 'left')
            ->where('rt.id', $targetId)
            ->where('rt.tahun', $tahun);

        if (!empty($opdScope)) {
            $b->where('rs.opd_id', (int) $opdScope);
        }

        return $b->get()->getRowArray() ?: null;
    }

    /* =========================================================
     * DATA UNTUK VIEW
     * =======================================================*/

    /**
     * Semua bahan dua tabel tambahan, dalam beberapa query saja (bukan per baris).
     *
     * @param array $scope hasil lakipScope()
     *
     * @return array<string, mixed>
     */
    protected function lakipAddendumData(array $scope): array
    {
        $tahun = (string) $scope['tahun'];
        $mode  = (string) $scope['mode'];
        $opd   = $scope['opdScope'];

        // Efisiensi & program: mode kabupaten memakai opd_id 0; mode OPD
        // memakai OPD terpilih (null = lintas OPD, tidak difilter).
        $opdEfisiensi = ($mode === 'kabupaten') ? 0 : $opd;

        return [
            // Sumber ikut disaring: analisis milik Renstra tidak boleh muncul
            // pada baris IKU yang kebetulan ber-id sama.
            'analisisMap'    => $this->analisis()->getByTahunGrouped(
                $tahun, $mode, $opd, $this->sumberDokumenLakip($mode)
            ),
            'efisiensiRows'  => $this->efisiensi()->getByTahun($tahun, $opdEfisiensi),
            'programOptions' => $tahun !== '' ? $this->efisiensi()->programOptions($tahun, $opd) : [],
            'addendumScope'  => $scope,
        ];
    }

    /* =========================================================
     * RUPIAH
     * =======================================================*/

    /**
     * "Rp 150.000.000" / "150000000" / "150.000.000,50" -> float.
     * Mengembalikan null bila kosong, false bila bukan angka atau negatif.
     *
     * Pembungkus tipis parseRupiah() di app/Helpers/format_helper.php —
     * helper itu tidak ikut autoload, jadi dimuat di sini.
     *
     * @return float|null|false
     */
    protected function rupiahLakip($nilai)
    {
        helper('format');

        return parseRupiah($nilai);
    }

    /** Teks bebas tag/script — pola yang sama dengan xssRule() controller LAKIP. */
    protected function teksAmanLakip($nilai): bool
    {
        if ($nilai === null || $nilai === '') {
            return true;
        }

        return (bool) preg_match(
            '/^(?!.*<\s*script\b)(?!.*<\/\s*script\s*>)(?!.*javascript\s*:)(?!.*data\s*:\s*text\/html)(?!.*(?<!\w)on\w+\s*=)(?!.*<\?php)(?!.*<\?).*$/is',
            (string) $nilai
        );
    }

    /** URL kembali ke halaman LAKIP dengan filter yang sedang aktif dipertahankan. */
    protected function kembaliLakip(array $scope): string
    {
        $params = [];
        if (($scope['role'] ?? '') === 'admin_kab' || ($scope['role'] ?? '') === 'admin' || ($scope['role'] ?? '') === 'admin_inspektorat') {
            $params['mode'] = $scope['mode'];
            if (!empty($scope['opdScope'])) {
                $params['opd_id'] = (int) $scope['opdScope'];
            }
        }
        if (!empty($scope['tahun'])) {
            $params['tahun'] = $scope['tahun'];
        }

        // Pilihan sumber dokumen (IKU/Renstra + versinya) ikut pulang. Tanpa
        // ini, menekan tombol snapshot melempar operator kembali ke sumber
        // BAWAAN — ia menyiapkan snapshot dari versi IKU yang dipilihnya, lalu
        // mendarat di halaman yang menampilkan versi lain, dan menyimpulkan
        // snapshotnya salah. Layar AdminKab tidak pernah mengirim keduanya,
        // jadi tidak ada yang berubah di sana.
        foreach (['sumber', 'sumber_versi'] as $kunciSumber) {
            $nilai = trim((string) ($this->request->getPost($kunciSumber) ?? ''));

            if ($nilai !== '') {
                $params[$kunciSumber] = $nilai;
            }
        }

        $qs = $params ? ('?' . http_build_query($params)) : '';

        return base_url($this->lakipBaseUrl()) . $qs;
    }

    /* =========================================================
     * ANALISIS FAKTOR — SIMPAN & HAPUS
     * =======================================================*/

    /** POST tambah/edit analisis faktor. `id` kosong = tambah. */
    public function analisisSave()
    {
        $scope = $this->lakipScopeFromPost();
        $back  = $this->kembaliLakip($scope);

        if (!$this->analisis()->siap()) {
            return redirect()->to($back)->with('error', 'Tabel lakip_analisis_faktor belum tersedia. Jalankan db/update_2026-07-27_lakip_analisis_efisiensi.sql.');
        }
        if (!$scope['canWrite']) {
            return redirect()->to($back)->with('error', $scope['alasan'] ?? 'Tidak berhak menyimpan data ini.');
        }

        // Sumber dokumen ikut dikirim form (lihat `$bidangLingkup` pada partial
        // addendum). Ia menentukan di TABEL MANA id itu harus ada — id
        // indikator IKU dan id target Renstra hidup di ruang angka yang sama,
        // jadi memverifikasinya ke tabel yang salah bukan sekadar gagal, tapi
        // bisa lolos untuk baris yang keliru.
        $sumberDok = $this->sumberDokumenLakip((string) $scope['mode']);

        $targetId = (int) ($this->request->getPost('target_id') ?? 0);
        $target   = $this->targetLakipSah($targetId, $scope['mode'], $scope['tahun'], $scope['opdScope'], $sumberDok);
        if (!$target) {
            return redirect()->to($back)->with('error', 'Indikator tidak ditemukan pada tahun & unit yang dipilih.');
        }

        $isi = [
            'faktor_pendukung'  => trim((string) ($this->request->getPost('faktor_pendukung') ?? '')),
            'faktor_penghambat' => trim((string) ($this->request->getPost('faktor_penghambat') ?? '')),
            'upaya_peningkatan' => trim((string) ($this->request->getPost('upaya_peningkatan') ?? '')),
        ];

        foreach ($isi as $kunci => $nilai) {
            if (mb_strlen($nilai) > 5000) {
                return redirect()->to($back)->withInput()
                    ->with('error', 'Isian analisis terlalu panjang (maksimal 5000 karakter).');
            }
            if (!$this->teksAmanLakip($nilai)) {
                return redirect()->to($back)->withInput()
                    ->with('error', 'Isian analisis mengandung script / input berbahaya.');
            }
            $isi[$kunci] = ($nilai === '') ? null : $nilai;
        }

        if ($isi['faktor_pendukung'] === null && $isi['faktor_penghambat'] === null && $isi['upaya_peningkatan'] === null) {
            return redirect()->to($back)->withInput()
                ->with('error', 'Minimal salah satu dari Faktor Pendukung, Faktor Penghambat, atau Upaya Peningkatan harus diisi.');
        }

        $userId = (int) session()->get('user_id') ?: null;
        $id     = (int) ($this->request->getPost('id') ?? 0);

        if ($id > 0) {
            // Edit: baris harus milik lingkup yang sama (cegah IDOR).
            $lama = $this->analisis()->ambil($id);
            if (!$lama || !$this->analisisMilikLingkup($lama, $scope, $targetId, $sumberDok)) {
                return redirect()->to($back)->with('error', 'Data analisis tidak ditemukan pada unit Anda.');
            }

            $this->analisis()->update($id, $isi + ['updated_by' => $userId]);

            return redirect()->to($back)->with('success', 'Analisis faktor berhasil diperbarui.');
        }

        $model  = $this->analisis();
        $sumber = $model->sumberSah((string) $scope['mode'], $sumberDok);
        $kunci  = $model->kolomKunci($sumber);

        // Hanya kolom kunci milik sumbernya yang diisi; dua lainnya sengaja
        // NULL supaya tidak ada id yang tampak sah di kolom yang artinya lain.
        $barisBaru = [
            'renstra_target_id' => $kunci === 'renstra_target_id' ? $targetId : null,
            'rpjmd_target_id'   => $kunci === 'rpjmd_target_id' ? $targetId : null,
            'opd_id'            => (int) ($scope['opdScope'] ?? 0),
            'tahun'             => $scope['tahun'],
            'created_by'        => $userId,
            'updated_by'        => $userId,
        ];

        if ($model->punyaKolomSumber()) {
            $barisBaru['iku_indikator_id'] = $kunci === 'iku_indikator_id' ? $targetId : null;
            $barisBaru['source_type']      = $sumber;
        } elseif ($kunci === 'iku_indikator_id') {
            return redirect()->to($back)->with('error',
                'Analisis untuk LAKIP bersumber IKU membutuhkan migrasi '
                . 'db/update_2026-08-25_izin_sunting_iku_dan_analisis_iku.sql. Jalankan dulu di server ini.');
        }

        $model->insert($isi + $barisBaru);

        return redirect()->to($back)->with('success', 'Analisis faktor berhasil ditambahkan.');
    }

    /** Baris analisis benar-benar milik lingkup (mode, tahun, OPD, target) ini? */
    private function analisisMilikLingkup(
        array $baris,
        array $scope,
        ?int $targetId = null,
        ?string $sumber = null
    ): bool {
        if ((string) $baris['tahun'] !== (string) $scope['tahun']) {
            return false;
        }
        if ((int) $baris['opd_id'] !== (int) ($scope['opdScope'] ?? 0)) {
            return false;
        }

        $model = $this->analisis();

        // Sumber baris dibaca dari BARISNYA, lalu dicocokkan dengan sumber yang
        // sedang aktif di layar. Tanpa pencocokan ini, baris analisis milik
        // Renstra bisa disunting lewat layar yang sedang menampilkan IKU hanya
        // karena id-nya kebetulan sama.
        $sumberBaris = $model->sumberBaris($baris);
        $sumberAktif = $model->sumberSah((string) $scope['mode'], $sumber);

        if ($sumberBaris !== $sumberAktif) {
            return false;
        }

        $kolom = $model->kolomKunci($sumberBaris);

        if (empty($baris[$kolom])) {
            return false;
        }

        return $targetId === null || (int) $baris[$kolom] === $targetId;
    }

    /** Hapus satu baris analisis faktor. */
    public function analisisDelete($id = null)
    {
        $scope = $this->lakipScopeFromPost();
        $back  = $this->kembaliLakip($scope);

        if (!$scope['canWrite']) {
            return redirect()->to($back)->with('error', $scope['alasan'] ?? 'Tidak berhak menghapus data ini.');
        }

        // Sumber dokumen yang sedang aktif WAJIB ikut, sama seperti pada
        // analisisSave(). Tanpa argumen ini analisisMilikLingkup() memakai
        // cadangan mode ('renstra' untuk mode OPD), sehingga baris bersumber
        // IKU selalu dianggap milik lingkup lain dan tombol Hapus-nya mati.
        $sumberDok = $this->sumberDokumenLakip((string) $scope['mode']);

        $baris = $this->analisis()->ambil((int) $id);
        if (!$baris || !$this->analisisMilikLingkup($baris, $scope, null, $sumberDok)) {
            return redirect()->to($back)->with('error', 'Data analisis tidak ditemukan pada unit Anda.');
        }

        $this->analisis()->delete((int) $id);

        return redirect()->to($back)->with('success', 'Analisis faktor berhasil dihapus.');
    }

    /* =========================================================
     * EFISIENSI PROGRAM — SIMPAN & HAPUS
     * =======================================================*/

    /** POST tambah/edit efisiensi program. `id` kosong = tambah. */
    public function efisiensiSave()
    {
        $scope = $this->lakipScopeFromPost();
        $back  = $this->kembaliLakip($scope);

        if (!$this->efisiensi()->siap()) {
            return redirect()->to($back)->with('error', 'Tabel lakip_efisiensi_program belum tersedia. Jalankan db/update_2026-07-27_lakip_analisis_efisiensi.sql.');
        }
        if (!$scope['canWrite']) {
            return redirect()->to($back)->with('error', $scope['alasan'] ?? 'Tidak berhak menyimpan data ini.');
        }

        $programId = (int) ($this->request->getPost('program_id') ?? 0);
        $opdScope  = (int) ($scope['opdScope'] ?? 0);

        // Pagu SELALU diambil ulang dari DB; nilai `anggaran` kiriman browser
        // (field readonly) sengaja diabaikan. programSah() sekaligus memastikan
        // program itu memang milik OPD & tahun tersebut.
        $program = $this->efisiensi()->programSah($programId, $scope['tahun'], $opdScope ?: null);
        if (!$program) {
            return redirect()->to($back)->withInput()
                ->with('error', 'Program tidak ditemukan pada tahun ' . esc($scope['tahun']) . ' untuk unit Anda.');
        }

        $realisasi = $this->rupiahLakip($this->request->getPost('realisasi'));
        $efisiensiN = $this->rupiahLakip($this->request->getPost('efisiensi'));
        if ($realisasi === false) {
            return redirect()->to($back)->withInput()->with('error', 'Realisasi harus berupa angka rupiah yang tidak negatif.');
        }
        if ($efisiensiN === false) {
            return redirect()->to($back)->withInput()->with('error', 'Efisiensi harus berupa angka rupiah yang tidak negatif.');
        }

        $anggaran = (float) ($program['anggaran'] ?? 0);
        if ($anggaran <= 0) {
            return redirect()->to($back)->withInput()
                ->with('error', 'Program ini belum memiliki pagu anggaran di Perjanjian Kinerja tahun ' . esc($scope['tahun']) . '. Lengkapi dulu anggarannya sebelum mengisi efisiensi.');
        }

        $id     = (int) ($this->request->getPost('id') ?? 0);
        $userId = (int) session()->get('user_id') ?: null;

        $kembar = $this->efisiensi()->cariKembar($programId, $scope['tahun'], $opdScope, $id > 0 ? $id : null);
        if ($kembar) {
            return redirect()->to($back)->withInput()
                ->with('error', 'Program tersebut sudah punya data efisiensi tahun ' . esc($scope['tahun']) . '. Silakan edit data yang sudah ada.');
        }

        $isi = [
            'anggaran'   => $anggaran,           // snapshot pagu terbaru saat disimpan
            'realisasi'  => $realisasi ?? 0,
            'efisiensi'  => $efisiensiN ?? 0,
            'updated_by' => $userId,
        ];

        // Beberapa operasi (validasi program + pagu + tulis baris) dijadikan satu
        // transaksi supaya tidak ada baris setengah jadi kalau salah satunya gagal.
        $this->db->transStart();

        if ($id > 0) {
            $lama = $this->efisiensi()->ambil($id);
            if (!$lama
                || (string) $lama['tahun'] !== (string) $scope['tahun']
                || (int) $lama['opd_id'] !== $opdScope) {
                $this->db->transComplete();

                return redirect()->to($back)->with('error', 'Data efisiensi tidak ditemukan pada unit Anda.');
            }

            // Program boleh diganti saat edit -> pagunya ikut diperbarui.
            $this->efisiensi()->update($id, $isi + ['program_id' => $programId]);
        } else {
            $this->efisiensi()->insert($isi + [
                'program_id' => $programId,
                'opd_id'     => $opdScope,
                'tahun'      => $scope['tahun'],
                'created_by' => $userId,
            ]);
        }

        $this->db->transComplete();

        if ($this->db->transStatus() === false) {
            return redirect()->to($back)->withInput()->with('error', 'Gagal menyimpan data efisiensi program.');
        }

        return redirect()->to($back)->with('success', 'Efisiensi program berhasil disimpan.');
    }

    /** Hapus satu baris efisiensi program. */
    public function efisiensiDelete($id = null)
    {
        $scope = $this->lakipScopeFromPost();
        $back  = $this->kembaliLakip($scope);

        if (!$scope['canWrite']) {
            return redirect()->to($back)->with('error', $scope['alasan'] ?? 'Tidak berhak menghapus data ini.');
        }

        $baris = $this->efisiensi()->ambil((int) $id);
        if (!$baris
            || (string) $baris['tahun'] !== (string) $scope['tahun']
            || (int) $baris['opd_id'] !== (int) ($scope['opdScope'] ?? 0)) {
            return redirect()->to($back)->with('error', 'Data efisiensi tidak ditemukan pada unit Anda.');
        }

        $this->efisiensi()->delete((int) $id);

        return redirect()->to($back)->with('success', 'Efisiensi program berhasil dihapus.');
    }
}
