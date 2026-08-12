<?php

namespace App\Controllers\Concerns;

use App\Models\LakipBenchmarkModel;

/**
 * Benchmark Provinsi Lampung & Nasional pada halaman LAKIP.
 *
 * Dipakai bersama AdminKab\LakipController dan AdminOpd\LakipOpdController,
 * dan MENUMPANG lingkup (mode/tahun/opdScope) milik LakipAddendumTrait —
 * jadi chart selalu mengikuti filter LAKIP yang sedang aktif.
 *
 * ---------------------------------------------------------------------
 * HAK AKSES — sengaja TIDAK memakai izin lakip_kab / lakip_opd
 *
 * Role OPD boleh menulis LAKIP unitnya sendiri, tetapi TIDAK boleh mengubah
 * angka pembanding Provinsi/Nasional. Karena itu benchmark memakai modul izin
 * tersendiri:
 *
 *   lakip_benchmark.view   -> semua role pembaca LAKIP
 *   lakip_benchmark.manage -> admin_kab (role 'admin' otomatis lolos user_can)
 *
 * Pemeriksaan dilakukan di SERVER pada setiap aksi tulis, bukan sekadar
 * menyembunyikan tombol di view.
 * ---------------------------------------------------------------------
 */
trait LakipBenchmarkTrait
{
    protected ?LakipBenchmarkModel $benchmarkModel = null;

    protected function benchmark(): LakipBenchmarkModel
    {
        return $this->benchmarkModel ??= new LakipBenchmarkModel();
    }

    /** Boleh input/ubah benchmark? Dipakai view untuk gate tombol + server untuk tolak. */
    protected function benchmarkCanManage(): bool
    {
        helper('rbac');

        return user_can('lakip_benchmark.manage');
    }

    /** Boleh melihat chart benchmark? */
    protected function benchmarkCanView(): bool
    {
        helper('rbac');

        // Sudah lolos gate halaman LAKIP; izin manage otomatis mencakup lihat.
        return user_can('lakip_benchmark.view') || user_can('lakip_benchmark.manage');
    }

    /**
     * Bahan chart perbandingan untuk view.
     *
     * Nilai Kabupaten/OPD diambil dari LAKIP existing ($lakipMap, kunci
     * target_id) — sumber datanya tidak diubah sama sekali. Benchmark hanya
     * menempelkan nilai Provinsi & Nasional per indikator.
     *
     * @param array $scope    hasil LakipAddendumTrait::lakipScope()
     * @param array $rows     baris target/indikator tahun aktif
     * @param array $lakipMap peta baris LAKIP; boleh dikunci target_id
     *                        (AdminKab) maupun indikator_id (AdminOpd)
     *
     * @return array<string, mixed>
     */
    protected function lakipBenchmarkData(array $scope, array $rows, array $lakipMap): array
    {
        // Kedua controller LAKIP mengunci petanya dengan cara berbeda; nilainya
        // sama-sama memuat indikator_id, jadi dikunci ulang di sini.
        $lakipByIndikator = [];
        foreach ($lakipMap as $baris) {
            if (is_array($baris) && !empty($baris['indikator_id'])) {
                $lakipByIndikator[(int) $baris['indikator_id']] = $baris;
            }
        }

        $mode  = (string) ($scope['mode'] ?? 'opd');
        $tahun = (string) ($scope['tahun'] ?? '');
        $opd   = $scope['opdScope'];

        // Mode OPD tanpa memilih OPD: indikator antar-OPD berbeda-beda sehingga
        // tidak boleh diagregasi. View menampilkan pesan "pilih satu OPD".
        $perluPilihOpd = ($mode === 'opd') && empty($opd);

        $benchmarkMap = $perluPilihOpd
            ? []
            : $this->benchmark()->getByTahunKeyedByIndikator($tahun, $mode, $opd ? (int) $opd : null);

        $daftar = [];
        if (!$perluPilihOpd) {
            foreach ($rows as $r) {
                $indikatorId = (int) ($r['indikator_id'] ?? 0);
                $targetId    = (int) ($r['target_id'] ?? 0);
                if ($indikatorId <= 0) {
                    continue;
                }
                // Satu indikator cukup sekali walau punya beberapa baris target.
                if (isset($daftar[$indikatorId])) {
                    continue;
                }

                $lakipBaris = $lakipByIndikator[$indikatorId] ?? null;
                $bm         = $benchmarkMap[$indikatorId] ?? null;

                $daftar[$indikatorId] = [
                    'indikator_id' => $indikatorId,
                    'target_id'    => $targetId,
                    'nama'         => (string) ($r['indikator_sasaran'] ?? '-'),
                    'satuan'       => (string) ($r['satuan'] ?? ''),
                    'sasaran'      => (string) ($r['sasaran'] ?? ''),
                    'nama_opd'     => (string) ($r['nama_opd'] ?? ''),
                    // Nilai indikator (bukan persentase capaian).
                    'nilai_daerah' => $this->benchmarkAngka($lakipBaris['capaian_tahun_ini'] ?? null),
                    'nilai_provinsi'  => isset($bm['nilai_provinsi']) && $bm['nilai_provinsi'] !== null ? (float) $bm['nilai_provinsi'] : null,
                    'nilai_nasional'  => isset($bm['nilai_nasional']) && $bm['nilai_nasional'] !== null ? (float) $bm['nilai_nasional'] : null,
                    'sumber_provinsi' => (string) ($bm['sumber_provinsi'] ?? ''),
                    'sumber_nasional' => (string) ($bm['sumber_nasional'] ?? ''),
                    'catatan'         => (string) ($bm['catatan'] ?? ''),
                    'updated_at'      => (string) ($bm['updated_at'] ?? ''),
                    'benchmark_id'    => (int) ($bm['id'] ?? 0),
                    'ada_benchmark'   => $bm !== null,
                ];
            }
        }

        return [
            'benchmarkList'          => array_values($daftar),
            'benchmarkCanManage'     => $this->benchmarkCanManage(),
            'benchmarkCanView'       => $this->benchmarkCanView(),
            'benchmarkPerluPilihOpd' => $perluPilihOpd,
            'benchmarkSiap'          => $this->benchmark()->siap(),
        ];
    }

    /**
     * "3,52" / "3.52" / "85 %" -> float. Kolom capaian LAKIP bertipe VARCHAR
     * sehingga isinya bisa memakai koma desimal atau membawa satuan.
     */
    protected function benchmarkAngka($nilai): ?float
    {
        if ($nilai === null) {
            return null;
        }
        $teks = trim((string) $nilai);
        if ($teks === '') {
            return null;
        }

        // Buang semua kecuali digit, koma, titik, dan minus.
        $bersih = preg_replace('/[^0-9,.\-]/', '', $teks);
        if ($bersih === '' || $bersih === '-') {
            return null;
        }

        $titik = strrpos($bersih, '.');
        $koma  = strrpos($bersih, ',');

        if ($koma !== false && ($titik === false || $koma > $titik)) {
            // Format Indonesia: titik = ribuan, koma = desimal.
            $bersih = str_replace('.', '', $bersih);
            $bersih = str_replace(',', '.', $bersih);
        } else {
            $bersih = str_replace(',', '', $bersih);
        }

        return is_numeric($bersih) ? (float) $bersih : null;
    }

    /**
     * "3,52" -> float untuk input form. Mengembalikan null bila kosong,
     * false bila bukan angka.
     *
     * @return float|null|false
     */
    protected function benchmarkNilaiInput($nilai)
    {
        if ($nilai === null || trim((string) $nilai) === '') {
            return null;
        }

        $angka = $this->benchmarkAngka($nilai);

        return $angka === null ? false : $angka;
    }

    /* =========================================================
     * SIMPAN & HAPUS
     * =======================================================*/

    /** POST tambah/ubah benchmark. Upsert per indikator + tahun. */
    public function benchmarkSave()
    {
        $scope = $this->lakipScopeFromPost();
        $back  = $this->kembaliLakip($scope);

        if (!$this->benchmark()->siap()) {
            return redirect()->to($back)->with('error', 'Tabel lakip_benchmark belum tersedia. Jalankan db/update_2026-08-12_lakip_benchmark.sql.');
        }

        // Otorisasi di server — bukan sekadar tombol yang disembunyikan.
        if (!$this->benchmarkCanManage()) {
            return redirect()->to($back)->with('error', 'Anda tidak berhak mengubah data benchmark Provinsi/Nasional.');
        }

        // Lingkup tetap dihormati: mode OPD wajib menunjuk satu OPD.
        if ($scope['mode'] === 'opd' && empty($scope['opdScope'])) {
            return redirect()->to($back)->with('error', 'Pilih satu OPD dulu sebelum mengisi benchmark.');
        }

        $indikatorId = (int) ($this->request->getPost('indikator_id') ?? 0);
        $indikator   = $this->benchmark()->indikatorSah($indikatorId, $scope['mode'], $scope['tahun'], $scope['opdScope']);
        if (!$indikator) {
            return redirect()->to($back)->with('error', 'Indikator tidak ditemukan pada tahun & unit yang dipilih.');
        }

        $provinsi = $this->benchmarkNilaiInput($this->request->getPost('nilai_provinsi'));
        $nasional = $this->benchmarkNilaiInput($this->request->getPost('nilai_nasional'));
        if ($provinsi === false) {
            return redirect()->to($back)->withInput()->with('error', 'Nilai Provinsi harus berupa angka.');
        }
        if ($nasional === false) {
            return redirect()->to($back)->withInput()->with('error', 'Nilai Nasional harus berupa angka.');
        }
        if ($provinsi === null && $nasional === null) {
            return redirect()->to($back)->withInput()
                ->with('error', 'Isi minimal salah satu dari Nilai Provinsi atau Nilai Nasional.');
        }

        $teks = [
            'sumber_provinsi' => trim((string) ($this->request->getPost('sumber_provinsi') ?? '')),
            'sumber_nasional' => trim((string) ($this->request->getPost('sumber_nasional') ?? '')),
            'catatan'         => trim((string) ($this->request->getPost('catatan') ?? '')),
        ];
        foreach ($teks as $kunci => $nilai) {
            $batas = $kunci === 'catatan' ? 5000 : 255;
            if (mb_strlen($nilai) > $batas) {
                return redirect()->to($back)->withInput()
                    ->with('error', 'Isian ' . str_replace('_', ' ', $kunci) . ' terlalu panjang (maksimal ' . $batas . ' karakter).');
            }
            if (!$this->teksAmanLakip($nilai)) {
                return redirect()->to($back)->withInput()
                    ->with('error', 'Isian benchmark mengandung script / input berbahaya.');
            }
            $teks[$kunci] = ($nilai === '') ? null : $nilai;
        }

        $userId = (int) session()->get('user_id') ?: null;
        $kolom  = LakipBenchmarkModel::kolomIndikator($scope['mode']);
        $opdId  = ($scope['mode'] === 'kabupaten') ? 0 : (int) $scope['opdScope'];

        $isi = $teks + [
            'nilai_provinsi' => $provinsi,
            'nilai_nasional' => $nasional,
            'updated_by'     => $userId,
        ];

        $lama = $this->benchmark()->cariByIndikator($indikatorId, $scope['tahun'], $scope['mode']);

        $this->db->transStart();

        if ($lama) {
            $this->benchmark()->update((int) $lama['id'], $isi);
            $pesan = 'Benchmark Provinsi/Nasional berhasil diperbarui.';
        } else {
            $this->benchmark()->insert($isi + [
                $kolom       => $indikatorId,
                'opd_id'     => $opdId,
                'tahun'      => $scope['tahun'],
                'created_by' => $userId,
            ]);
            $pesan = 'Benchmark Provinsi/Nasional berhasil disimpan.';
        }

        $this->db->transComplete();

        if ($this->db->transStatus() === false) {
            return redirect()->to($back)->withInput()->with('error', 'Gagal menyimpan data benchmark.');
        }

        return redirect()->to($back)->with('success', $pesan);
    }

    /** Hapus satu baris benchmark. */
    public function benchmarkDelete($id = null)
    {
        $scope = $this->lakipScopeFromPost();
        $back  = $this->kembaliLakip($scope);

        if (!$this->benchmarkCanManage()) {
            return redirect()->to($back)->with('error', 'Anda tidak berhak menghapus data benchmark Provinsi/Nasional.');
        }

        $baris = $this->benchmark()->ambil((int) $id);
        if (!$baris
            || (string) $baris['tahun'] !== (string) $scope['tahun']
            || (int) $baris['opd_id'] !== (int) ($scope['opdScope'] ?? 0)) {
            return redirect()->to($back)->with('error', 'Data benchmark tidak ditemukan pada unit Anda.');
        }

        $this->benchmark()->delete((int) $id);

        return redirect()->to($back)->with('success', 'Benchmark Provinsi/Nasional berhasil dihapus.');
    }
}
