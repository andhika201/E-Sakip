<?php

namespace App\Controllers\Concerns;

/**
 * Pemilih DOKUMEN SUMBER penilaian LAKIP (IKU / Renstra / RPJMD + versinya).
 *
 * Dipindah dari LakipOpdController supaya layar LAKIP Kabupaten
 * (AdminKab\LakipController) memakai mesin yang SAMA — dua layar yang
 * menampilkan LAKIP lingkup yang sama tidak boleh me-resolve sumber dengan
 * dua salinan logika yang bisa menyimpang.
 *
 * Kontrak pemakai: kelas pemakai punya $this->request dan $this->lakipModel.
 */
trait LakipSumberTrait
{
    /**
     * =====================================================================
     * PAKSA IKU — CERMIN AKAR CASCADING
     *
     * Aturannya sama dengan CascadingModel::akarAktif(): begitu ada versi IKU
     * yang BERLAKU untuk tahun ini, IKU-lah satu-satunya rujukan penilaian.
     * Sumber Renstra/RPJMD tidak lagi dipilih manual — ia tinggal cadangan
     * untuk lingkup yang IKU-nya belum disahkan, dan kepindahannya selalu
     * disebutkan terang-terangan di layar, tidak pernah diam-diam.
     *
     * Kenapa ditegakkan DI SINI, bukan hanya disembunyikan di dropdown:
     * pemilih sumber juga datang lewat query string dan POST (panel Analisis,
     * Benchmark, Penyesuaian). URL yang diketik langsung harus tunduk pada
     * aturan yang sama dengan tombolnya.
     * =====================================================================
     *
     * @return array{0: string, 1: bool} [sumber terpaksa, apakah dipaksa]
     */
    protected function paksaIkuBilaAda(string $sumber, string $mode, ?int $opdId, int $tahun): array
    {
        if ($sumber === \App\Services\Version\LakipSourceService::SUMBER_IKU) {
            return [$sumber, false];
        }

        $adaIku = (new \App\Services\Version\LakipSourceService())
            ->pilihanVersi('iku', $mode, $opdId, $tahun) !== [];

        return $adaIku
            ? [\App\Services\Version\LakipSourceService::SUMBER_IKU, true]
            : [$sumber, false];
    }

protected function sumberDariPermintaan(string $mode, ?int $opdId, int $tahun): array
    {
        $sumberPost = trim((string) ($this->request->getPost('sumber') ?? ''));

        if ($sumberPost === '') {
            return $this->sumberDariQuery($mode, $opdId, $tahun);
        }

        [$sumberPost] = $this->paksaIkuBilaAda($sumberPost, $mode, $opdId, $tahun);

        if ($sumberPost !== \App\Services\Version\LakipSourceService::SUMBER_IKU) {
            return [$sumberPost, 0];
        }

        // Versi dari POST sama tidak dipercayanya dengan versi dari GET.
        $versi  = (int) ($this->request->getPost('sumber_versi') ?? 0);
        $daftar = (new \App\Services\Version\LakipSourceService())
            ->pilihanVersi('iku', $mode, $opdId, $tahun);

        foreach ($daftar as $v) {
            if ((int) $v['id'] === $versi) {
                return ['iku', $versi];
            }
        }

        return ['iku', 0];
    }

protected function sumberDariQuery(string $mode, ?int $opdId, int $tahun): array
    {
        $sumber = trim((string) ($this->request->getGet('sumber') ?? ''));
        $versi  = (int) ($this->request->getGet('sumber_versi') ?? 0);

        // Tanpa parameter, ikuti resolusi yang SAMA dengan tabel utama
        // (pilihanSumberLakip): bawaan IKU bila ada versinya, jatuh ke
        // cadangan bila tidak. Dulu default-nya dipatok 'renstra' sehingga
        // pada muatan default tabel merender IKU sementara panel Analisis/
        // Benchmark/Penyesuaian menyaring dengan Renstra — dua bagian halaman
        // yang sama membaca sumber berbeda.
        if ($sumber === '') {
            $svc    = new \App\Services\Version\LakipSourceService();
            $bawaan = $svc->sumberBawaan();
            $daftar = $svc->pilihanVersi($bawaan, $mode, $opdId, $tahun);

            if ($daftar === []) {
                return [$svc->sumberAlternatif($mode), 0];
            }

            $sumber = $bawaan;
            // versi tetap dari query (0 bila tak ada) — dipilihkan di bawah.
        }

        [$sumber] = $this->paksaIkuBilaAda($sumber, $mode, $opdId, $tahun);

        if ($sumber !== \App\Services\Version\LakipSourceService::SUMBER_IKU) {
            return [$sumber, 0];
        }

        // Versi dari query string TIDAK dipercaya: harus ada pada daftar yang
        // sah untuk lingkup & tahun ini.
        $daftar = (new \App\Services\Version\LakipSourceService())
            ->pilihanVersi('iku', $mode, $opdId, $tahun);

        foreach ($daftar as $v) {
            if ((int) $v['id'] === $versi) {
                return ['iku', $versi];
            }
        }

        foreach ($daftar as $v) {
            if (! empty($v['rekomendasi'])) {
                return ['iku', (int) $v['id']];
            }
        }

        return ['iku', isset($daftar[0]) ? (int) $daftar[0]['id'] : 0];
    }

/**
     * CATATAN NAMA: JANGAN dinamai sumberLakip().
     *
     * LakipSnapshotTrait sudah punya sumberLakip(array, string, array) yang
     * menukar tabel hidup dengan snapshot beku. Metode kelas MEMBAYANGI metode
     * trait tanpa peringatan apa pun — php -l tetap bersih, dan yang meledak
     * adalah jalur cetak saat dipanggil dengan argumen milik trait.
     *
     * Keduanya sah dan memang berbeda urusan: yang ini memilih DOKUMEN SUMBER
     * (IKU/Renstra beserta versinya), yang itu memilih antara data hidup dan
     * arsip laporan.
     */
    protected function pilihanSumberLakip(string $mode, ?int $opdId, int $tahun): array
    {
        $svc = new \App\Services\Version\LakipSourceService();

        $diminta = trim((string) ($this->request->getGet('sumber') ?? ''));
        $versiId = (int) ($this->request->getGet('sumber_versi') ?? 0);

        $pilihanSumber = $svc->pilihanSumber($mode, $opdId, $tahun);
        $sumber        = $diminta !== '' ? $diminta : $svc->sumberBawaan();

        try {
            $sumber = $svc->sumberSah($sumber, $mode);
        } catch (\Throwable $e) {
            $sumber = $svc->sumberBawaan();
        }

        // PAKSA IKU (lihat paksaIkuBilaAda). Permintaan sumber lain lewat
        // dropdown, query string, maupun URL yang diketik langsung sama-sama
        // dibelokkan ke IKU begitu ada versinya yang berlaku.
        [$sumber, $dipaksa] = $this->paksaIkuBilaAda($sumber, $mode, $opdId, $tahun);

        $daftar = $svc->pilihanVersi($sumber, $mode, $opdId, $tahun);

        // Sumber cadangan dikunci di daftar pilihan begitu IKU tersedia,
        // supaya dropdown-nya jujur terhadap aturan di atas.
        if ($daftar !== [] && $sumber === \App\Services\Version\LakipSourceService::SUMBER_IKU) {
            foreach ($pilihanSumber as &$p) {
                if ($p['nilai'] !== \App\Services\Version\LakipSourceService::SUMBER_IKU) {
                    $p['terkunci'] = true;
                }
            }
            unset($p);
        }

        // Tidak ada versi IKU yang memayungi tahun ini -> jatuh ke cadangan,
        // dan katakan alasannya. Diam-diam berpindah sumber jauh lebih buruk
        // daripada tabel kosong: pemakai akan mengira angkanya berasal dari
        // dokumen yang sebenarnya tidak dipakai.
        $catatan = null;

        if (! empty($dipaksa)) {
            $catatan = 'LAKIP dinilai terhadap IKU yang sudah disahkan — sumber lain '
                . 'tidak lagi dipilih manual, supaya dokumen penilaiannya satu.';
        }

        if ($daftar === [] && $diminta === '') {
            $cadangan = $svc->sumberAlternatif($mode);
            $daftarCadangan = $svc->pilihanVersi($cadangan, $mode, $opdId, $tahun);

            if ($daftarCadangan !== []) {
                $catatan = 'Belum ada versi IKU yang berlaku untuk tahun ' . $tahun
                    . ', jadi sumbernya sementara memakai ' . strtoupper($cadangan) . '. '
                    . 'Supaya LAKIP menilai IKU: buka menu IKU lalu tekan '
                    . '"Ajukan Pengesahan" — begitu revisinya berlaku, layar ini '
                    . 'berpindah sendiri.';
                $sumber  = $cadangan;
                $daftar  = $daftarCadangan;
            }
        }

        // Versi terpilih: yang diminta bila sah, selain itu yang direkomendasikan.
        $versi = null;

        foreach ($daftar as $v) {
            if ($versiId > 0 && (int) $v['id'] === $versiId) {
                $versi = $v;
            }
        }

        if ($versi === null) {
            foreach ($daftar as $v) {
                if (! empty($v['rekomendasi'])) {
                    $versi = $v;
                    break;
                }
            }

            $versi ??= ($daftar[0] ?? null);
        }

        return [
            'sumber'         => $sumber,
            'versi'          => $versi,
            'daftar_versi'   => $daftar,
            'pilihan_sumber' => $pilihanSumber,
            // §27: memilih selain rekomendasi wajib beralasan. Ditegakkan saat
            // menyimpan, bukan hanya di form.
            'alasan_wajib'   => $versi !== null && empty($versi['rekomendasi']),
            'catatan'        => $catatan,
        ];
    }

protected function baganLakip(array $pilihan, string $mode, ?int $opdId, int $tahun, ?string $status): array
    {
        $svc = \App\Services\Version\LakipSourceService::class;

        if ($pilihan['sumber'] === $svc::SUMBER_IKU && ! empty($pilihan['versi'])) {
            $rows     = $this->lakipModel->getIndexIkuTargets((int) $pilihan['versi']['id'], $tahun, $opdId);
            $lakipMap = $this->lakipModel->getLakipMapIku($tahun, $status ?: null, $opdId);

            return [$this->lakipModel->groupIndexRowsBySasaran($rows, $mode), $lakipMap, $rows];
        }

        if ($mode === 'kabupaten') {
            $rows = $this->lakipModel->getIndexRpjmdTargets((string) $tahun);
            $peta = $this->lakipModel->getLakipMapRpjmd((string) $tahun, $status ?: null);
        } else {
            $rows = $this->lakipModel->getIndexRenstraTargets((string) $tahun, $opdId);
            $peta = $this->lakipModel->getLakipMapRenstra((string) $tahun, $status ?: null, $opdId);
        }

        $lakipMap = [];

        foreach ($peta as $l) {
            if (! empty($l['indikator_id'])) {
                $lakipMap[(int) $l['indikator_id']] = $l;
            }
        }

        return [$this->lakipModel->groupIndexRowsBySasaran($rows, $mode), $lakipMap, $rows];
    }
}
