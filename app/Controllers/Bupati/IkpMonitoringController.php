<?php

namespace App\Controllers\Bupati;

use App\Controllers\AdminKab\IkpController as IkpKabupatenController;

/**
 * Monitoring BULANAN Kinerja Prioritas (IKP) — role Bupati (dan admin), HANYA BACA.
 *
 * Rute (blok AKSARA+ di ujung Config\Routes, grup bupati auth:bupati,admin):
 *   GET bupati/ikp              index  kartu utama, kisi panas OPD × bulan,
 *                                      5 IKP perlu perhatian, ringkas per Program Unggulan
 *   GET bupati/ikp/opd/(:num)   opd    rincian satu OPD: grafik target vs realisasi per IKP + triwulan
 *
 * MENGAPA mewarisi AdminKab\IkpController: lingkup OPD, pilihan tahun/bulan,
 * dan rangkuman per OPD HARUS identik dengan rekap Kabupaten — Bupati dan
 * Bappeda/admin kabupaten melihat angka yang sama. Yang berbeda hanya sudut
 * pandang (capaian BULAN terpilih, bukan kumulatif), izin, dan tampilan.
 *
 * Tidak ada aksi tulis. ReadOnlyRoleFilter (global) menolak POST apa pun dari
 * role bupati; halaman ini pun hanya GET.
 */
class IkpMonitoringController extends IkpKabupatenController
{
    /** Hanya dua halaman; metode induk lain (programUnggulan, cetak) = 404 di area ini. */
    protected function petaIzin(): array
    {
        return [
            'index' => 'ikp_bupati_monitoring.view',
            'opd'   => 'ikp_bupati_monitoring.view',
        ];
    }

    /** GET bupati/ikp?tahun=&bulan=&jenis= */
    public function index()
    {
        [$tahun, $bulan] = $this->tahunBulan();
        $jenis   = $this->jenisDiminta();
        $baris   = $this->rekapLintas($tahun, $bulan, $this->daftarOpd($jenis));

        // ---------- kartu utama ----------
        $opdBerIkp = 0;
        $opdLapor  = 0;
        $persenBln = [];
        $merah     = 0;
        $kuning    = 0;
        $jumlahIkp = 0;
        $belumNilai = 0;
        $semuaIkp  = [];
        foreach ($baris as $b) {
            if ($b['agregat']['jumlah'] > 0) {
                $opdBerIkp++;
            }
            if ($b['agregat']['lapor_bulan'] > 0) {
                $opdLapor++;
            }
            foreach ($b['ikp'] as $i) {
                $jumlahIkp++;
                if ($i['bln']['persen'] !== null) {
                    $persenBln[] = $i['bln']['persen'];
                } else {
                    $belumNilai++;
                }
                $k = $i['bln']['status']['kelompok'];
                $merah  += $k === 'merah' ? 1 : 0;
                $kuning += $k === 'kuning' ? 1 : 0;
                $semuaIkp[] = ['opd' => $b['opd']] + $i;
            }
        }
        $rataBln = $persenBln === [] ? null : round(array_sum($persenBln) / count($persenBln), 2);

        // ---------- 5 IKP perlu perhatian ----------
        // Urutan kegentingan: capaian bulan ini terendah (merah lalu kuning),
        // baru kemudian IKP yang punya target bulan ini tetapi belum melapor.
        $terukur = array_values(array_filter($semuaIkp, static fn ($x) => $x['bln']['persen'] !== null
            && in_array($x['bln']['status']['kelompok'], ['merah', 'kuning'], true)));
        usort($terukur, static fn ($a, $b) => $a['bln']['persen'] <=> $b['bln']['persen']);
        $belumLapor = array_values(array_filter($semuaIkp, static fn ($x) => ! $x['lapor']
            && ($x['rekap']['bulan'][$bulan]['target'] ?? null) !== null));
        $perhatian = array_slice(array_merge($terukur, $belumLapor), 0, 5);

        // ---------- ringkas per Program Unggulan ----------
        $puList = $this->db->table('ikp_program_unggulan')->orderBy('urutan', 'ASC')->orderBy('id', 'ASC')
            ->get()->getResultArray();
        $perPu = [];
        foreach ($puList as $pu) {
            $isi = array_values(array_filter($semuaIkp, static fn ($x) => (int) ($x['rekap']['ikp']['program_unggulan_id'] ?? 0) === (int) $pu['id']));
            $p   = array_values(array_filter(array_map(static fn ($x) => $x['bln']['persen'], $isi), static fn ($v) => $v !== null));
            $r   = $p === [] ? null : round(array_sum($p) / count($p), 2);
            $perPu[] = [
                'pu'     => $pu,
                'jumlah' => count($isi),
                'opd'    => count(array_unique(array_map(static fn ($x) => (int) $x['opd']['id'], $isi))),
                'rata'   => $r,
                'status' => $this->statusDariPersen($r),
                'merah'  => count(array_filter($isi, static fn ($x) => $x['bln']['status']['kelompok'] === 'merah')),
            ];
        }

        return view('ikp/bupati_index', [
            'title'     => 'Monitoring Kinerja Prioritas (IKP)',
            'tahun'     => $tahun,
            'bulan'     => $bulan,
            'jenis'     => $jenis,
            'tahunList' => $this->rekap->tahunPeriode(),
            'baris'     => $baris,
            'kartu'     => [
                'opd_ber_ikp' => $opdBerIkp,
                'opd_lapor'   => $opdLapor,
                'ikp'         => $jumlahIkp,
                'rata_bln'    => $rataBln,
                'status_bln'  => $this->statusDariPersen($rataBln),
                'terukur'     => count($persenBln),
                'belum_nilai' => $belumNilai,
                'merah'       => $merah,
                'kuning'      => $kuning,
            ],
            'perhatian' => $perhatian,
            'perPu'     => $perPu,
        ]);
    }

    /** GET bupati/ikp/opd/(:num)?tahun=&bulan= */
    public function opd($id = null)
    {
        $opd = $this->opdSah((int) $id);
        [$tahun, $bulan] = $this->tahunBulan();
        $data = $this->rekapLintas($tahun, $bulan, [$opd])[0];

        return view('ikp/bupati_opd', [
            'title'     => 'Monitoring IKP ' . $opd['nama_opd'],
            'tahun'     => $tahun,
            'bulan'     => $bulan,
            'tahunList' => $this->rekap->tahunPeriode(),
            'data'      => $data,
        ]);
    }
}
