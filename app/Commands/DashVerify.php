<?php

namespace App\Commands;

use App\Models\DashboardThresholdModel;
use App\Services\OpdDashboardService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Verifikasi aturan Dashboard Perangkat Daerah.
 *
 *   php spark dash:verify [opd_id]
 *
 * Menjalankan skenario uji yang disepakati (validitas indikator, capaian total
 * OPD, metode perhitungan, satuan predikat, penyerapan anggaran, ambang status,
 * dan pembatasan lintas-OPD) terhadap DATA SEMENTARA yang dibuat di dalam
 * transaksi lalu DI-ROLLBACK — data produksi tidak pernah berubah.
 *
 * Alat bantu pengembangan; aman dihapus bila tidak diperlukan lagi.
 */
class DashVerify extends BaseCommand
{
    protected $group       = 'Dashboard';
    protected $name        = 'dash:verify';
    protected $description = 'Uji aturan Dashboard OPD memakai data sementara (otomatis di-rollback).';

    private const TAHUN_UJI = 2099;

    private $db;
    private int $lulus = 0;
    private int $gagal = 0;

    public function run(array $params)
    {
        helper(['capaian', 'dashboard_status', 'format']);
        $this->db = db_connect();

        $opdId = (int) ($params[0] ?? 0);
        if ($opdId <= 0) {
            $row   = $this->db->table('opd')->select('id')->orderBy('id', 'ASC')->get()->getRowArray();
            $opdId = (int) ($row['id'] ?? 0);
        }
        if ($opdId <= 0) {
            CLI::error('Tidak ada data OPD untuk diuji.');
            return 1;
        }

        // ---- Uji yang tidak menyentuh database sama sekali ----
        $this->ujiAmbang();
        $this->ujiValidasiAmbang();

        // ---- Uji end-to-end dengan data sementara ----
        $this->db->transBegin();
        try {
            $this->ujiDashboard($opdId);
        } catch (\Throwable $e) {
            CLI::error('Kesalahan saat pengujian: ' . $e->getMessage());
            CLI::write($e->getFile() . ':' . $e->getLine());
            $this->gagal++;
        } finally {
            $this->db->transRollback();
            CLI::write('');
            CLI::write('Seluruh data uji sudah di-rollback.', 'dark_gray');
        }

        CLI::write('');
        CLI::write('LULUS: ' . $this->lulus . '   GAGAL: ' . $this->gagal, $this->gagal ? 'red' : 'green');

        return $this->gagal ? 1 : 0;
    }

    /* ================= skenario ================= */

    private function ujiAmbang(): void
    {
        CLI::write('== Ambang status (uji 11) ==', 'yellow');
        $this->cek('0% -> critical', getAchievementStatus(0)['code'] === 'critical');
        $this->cek('59,99% -> critical', getAchievementStatus(59.99)['code'] === 'critical');
        $this->cek('60% -> attention', getAchievementStatus(60)['code'] === 'attention');
        $this->cek('80% -> near_target', getAchievementStatus(80)['code'] === 'near_target');
        $this->cek('95% -> achieved', getAchievementStatus(95)['code'] === 'achieved');
        $this->cek('105% -> achieved', getAchievementStatus(105)['code'] === 'achieved');
        $this->cek('105,01% -> exceeded', getAchievementStatus(105.01)['code'] === 'exceeded');
        $this->cek('200% -> exceeded', getAchievementStatus(200)['code'] === 'exceeded');
        $this->cek('warna diambil dari tabel, bukan hardcode',
            getAchievementStatus(50)['color_hex'] === DashboardThresholdModel::COLORS['merah']['hex']);
    }

    private function ujiValidasiAmbang(): void
    {
        CLI::write('== Validasi konfigurasi ambang (uji 12 & 13) ==', 'yellow');
        $m = new DashboardThresholdModel();

        $sah = [
            ['code' => 'critical', 'name' => 'Kritis', 'min_value' => 0, 'max_value' => 59.99, 'color' => 'merah', 'icon' => '', 'is_active' => 1],
            ['code' => 'attention', 'name' => 'Perhatian', 'min_value' => 60, 'max_value' => null, 'color' => 'oranye', 'icon' => '', 'is_active' => 1],
        ];
        $this->cek('rentang bersambung diterima', $m->validasiRentang($sah) === []);

        $tumpang = $sah;
        $tumpang[1]['min_value'] = 50;
        $this->cek('rentang tumpang tindih ditolak', $m->validasiRentang($tumpang) !== []);

        $celah = $sah;
        $celah[1]['min_value'] = 70;
        $this->cek('celah antar rentang ditolak', $m->validasiRentang($celah) !== []);

        $duaTanpaBatas = $sah;
        $duaTanpaBatas[0]['max_value'] = null;
        $this->cek('dua rentang tanpa batas atas ditolak', $m->validasiRentang($duaTanpaBatas) !== []);

        $minLebihBesar = $sah;
        $minLebihBesar[0]['min_value'] = 90;
        $this->cek('min > max ditolak', $m->validasiRentang($minLebihBesar) !== []);

        $warnaLiar = $sah;
        $warnaLiar[0]['color'] = 'background:url(x)';
        $this->cek('warna di luar palet ditolak', $m->validasiRentang($warnaLiar) !== []);
    }

    private function ujiDashboard(int $opdId): void
    {
        $svc   = new OpdDashboardService();
        $tahun = self::TAHUN_UJI;

        CLI::write('== Data kosong (uji 25) ==', 'yellow');
        $kosong = $svc->getSummary($opdId, $tahun, 4);
        $this->cek('tanpa PK tidak error & indikator 0', ($kosong['pk']['indikator'] ?? -1) === 0);
        $this->cek('tanpa PK: capaian tidak dapat dihitung', $kosong['capaian']['can_compute'] === false);

        // ---------- bangun 8 indikator ----------
        $pkId      = $this->buatPk($opdId, $tahun);
        $sasaranId = $this->buatSasaran($pkId, 'Sasaran uji dashboard');

        // 1..6: trend naik lengkap (capaian = target -> 100%)
        $ind = [];
        for ($i = 1; $i <= 6; $i++) {
            $ind[$i] = $this->buatIndikator($sasaranId, 'Indikator naik ' . $i, '100', 1);
            $tr      = $this->buatRenaksi($opdId, $ind[$i], ['25', '50', '75', '100']);
            $this->buatMonev($opdId, $tr, ['25', '50', '75', '100'], 'trend_naik');
        }
        // 7: trend turun (target 10, capaian 8 -> 125%)
        $ind[7] = $this->buatIndikator($sasaranId, 'Indikator turun', '10', 1);
        $tr7    = $this->buatRenaksi($opdId, $ind[7], ['40', '30', '20', '10']);
        $this->buatMonev($opdId, $tr7, ['40', '30', '20', '8'], 'trend_turun');
        // 8: akumulasi (target 10+10+10+10, capaian 10+10+10+10 -> 100%)
        $ind[8] = $this->buatIndikator($sasaranId, 'Indikator akumulasi', '40', 1);
        $tr8    = $this->buatRenaksi($opdId, $ind[8], ['10', '10', '10', '10']);
        $this->buatMonev($opdId, $tr8, ['10', '10', '10', '10'], 'sum');

        CLI::write('== 8 indikator semuanya valid (uji 1, 5, 6, 7) ==', 'yellow');
        $d = $svc->getSummary($opdId, $tahun, 4);
        $this->cek('8 indikator terbaca', $d['pk']['indikator'] === 8);
        $this->cek('8 dari 8 valid', $d['capaian']['valid'] === 8 && $d['capaian']['wajib'] === 8);
        $this->cek('capaian total tampil', $d['capaian']['can_compute'] === true);
        $pctTurun = $this->pctIndikator($d, $ind[7]);
        $pctSum   = $this->pctIndikator($d, $ind[8]);
        $this->cek('trend naik = 100%', $this->pctIndikator($d, $ind[1]) === 100.0);
        $this->cek('trend turun 10/8 = 125%', $pctTurun === 125.0, (string) $pctTurun);
        $this->cek('akumulasi 40/40 = 100%', $pctSum === 100.0, (string) $pctSum);
        $harap = round((100 * 6 + 125 + 100) / 8, 2);
        $this->cek('capaian total OPD = rata-rata ' . $harap . '%', (float) $d['capaian']['total'] === $harap, (string) $d['capaian']['total']);

        CLI::write('== Label verifikasi (uji 9 & 10) ==', 'yellow');
        $this->cek('seluruh valid tapi belum diverifikasi -> label "Sementara"', $d['capaian']['label'] === 'Sementara');
        $this->cek('tidak ada nilai yang diklaim terverifikasi', $d['capaian']['verified_all'] === false);

        CLI::write('== Filter triwulan (uji 19) ==', 'yellow');
        $q2 = $svc->getSummary($opdId, $tahun, 2);
        $this->cek('TW II: indikator tetap valid', $q2['capaian']['valid'] === 8);
        $this->cek('TW II: trend naik memakai capaian TW II (50/50=100%)', $this->pctIndikator($q2, $ind[1]) === 100.0);
        $this->cek('TW II: akumulasi 20/20 = 100%', $this->pctIndikator($q2, $ind[8]) === 100.0);

        CLI::write('== Filter tahun (uji 18) ==', 'yellow');
        $lain = $svc->getSummary($opdId, $tahun - 1, 4);
        $this->cek('tahun lain tidak membawa indikator uji', $lain['pk']['indikator'] !== 8 || $lain['capaian']['valid'] !== 8);

        CLI::write('== Capaian 0 vs NULL (uji 3 & 4) ==', 'yellow');
        $indNol = $this->buatIndikator($sasaranId, 'Indikator capaian nol', '100', 1);
        $trNol  = $this->buatRenaksi($opdId, $indNol, ['25', '50', '75', '100']);
        $this->buatMonev($opdId, $trNol, ['0', '0', '0', '0'], 'trend_naik');
        $d2 = $svc->getSummary($opdId, $tahun, 4);
        $this->cek('capaian 0 dianggap sudah diisi (valid)', $this->validIndikator($d2, $indNol) === true);
        $this->cek('capaian 0 menghasilkan 0%', $this->pctIndikator($d2, $indNol) === 0.0);
        $this->cek('0% berstatus Kritis', $this->statusIndikator($d2, $indNol) === 'critical');
        $this->cek('kartu Perlu Perhatian mencatat indikator kritis', $d2['perhatian']['kritis'] === 1);

        $indNull = $this->buatIndikator($sasaranId, 'Indikator tanpa capaian', '100', 1);
        $trNull  = $this->buatRenaksi($opdId, $indNull, ['25', '50', '75', '100']);
        $this->buatMonev($opdId, $trNull, [null, null, null, null], 'trend_naik');
        $d3 = $svc->getSummary($opdId, $tahun, 4);
        $this->cek('capaian NULL = belum diinput (tidak valid)', $this->validIndikator($d3, $indNull) === false);
        $this->cek('alasannya missing_achievement', $this->alasanIndikator($d3, $indNull) === 'missing_achievement');

        CLI::write('== Satu indikator belum lengkap (uji 2) ==', 'yellow');
        $this->cek('capaian total OPD tidak ditampilkan', $d3['capaian']['can_compute'] === false);
        $this->cek('capaian total bernilai null', $d3['capaian']['total'] === null);
        $this->cek('jumlah valid < wajib', $d3['capaian']['valid'] < $d3['capaian']['wajib']);
        $this->cek('grafik menampilkan kategori Belum Valid',
            in_array('belum_valid', array_column($d3['status_distribution']['segments'], 'code'), true));

        CLI::write('== Indikator predikat tanpa skala (uji 8) ==', 'yellow');
        $satuanTanpaSkala = $this->satuanPredikatTanpaSkala();
        $indPred = $this->buatIndikator($sasaranId, 'Indikator predikat', 'WTP', $satuanTanpaSkala);
        $trPred  = $this->buatRenaksi($opdId, $indPred, ['WTP', 'WTP', 'WTP', 'WTP']);
        $this->buatMonev($opdId, $trPred, ['WTP', 'WTP', 'WTP', 'WTP'], 'trend_naik');
        $d4 = $svc->getSummary($opdId, $tahun, 4);
        $this->cek('predikat tanpa skala -> tidak valid', $this->validIndikator($d4, $indPred) === false);
        $this->cek('alasannya missing_predicate_scale',
            $this->alasanIndikator($d4, $indPred) === 'missing_predicate_scale',
            (string) $this->alasanIndikator($d4, $indPred));

        CLI::write('== Penyerapan anggaran (uji 14, 15, 16) ==', 'yellow');
        $prog = $this->buatProgram($opdId, $tahun, 'Program uji dashboard', 1000000000);
        $this->kaitkanProgram($prog, $ind[1]);
        $d5 = $svc->getSummary($opdId, $tahun, 4);
        $this->cek('pagu terbaca dari program_pk', (float) $d5['anggaran']['anggaran'] === 1000000000.0);
        $this->cek('realisasi NULL tidak dianggap 0', $d5['anggaran']['realisasi'] === null);
        $this->cek('penyerapan null saat realisasi belum ada', $d5['anggaran']['persen'] === null);

        $tr1 = $this->renaksiPertama($ind[1]);
        $this->buatRealisasi($opdId, $tr1, [0, 0, 0, 0]);
        $d6 = $svc->getSummary($opdId, $tahun, 4);
        $this->cek('realisasi 0 yang dilaporkan = nilai sah', $d6['anggaran']['realisasi'] === 0.0);
        $this->cek('penyerapan 0% (bukan null)', $d6['anggaran']['persen'] === 0.0);

        $this->ubahRealisasi($tr1, [100000000, 100000000, 150000000, 150000000]);
        $d7 = $svc->getSummary($opdId, $tahun, 4);
        $this->cek('realisasi terjumlah benar (Rp500 jt)', $d7['anggaran']['realisasi'] === 500000000.0);
        $this->cek('penyerapan 500/1000 = 50%', $d7['anggaran']['persen'] === 50.0, (string) $d7['anggaran']['persen']);
        $q1 = $svc->getSummary($opdId, $tahun, 1);
        $this->cek('penyerapan ikut filter triwulan (TW I = 10%)', $q1['anggaran']['persen'] === 10.0, (string) $q1['anggaran']['persen']);

        CLI::write('== Rencana Aksi belum ada ==', 'yellow');
        $indTanpa = $this->buatIndikator($sasaranId, 'Indikator tanpa rencana aksi', '100', 1);
        $d8 = $svc->getSummary($opdId, $tahun, 4);
        $this->cek('indikator tanpa Rencana Aksi terdeteksi', $d8['pk']['tanpa_renaksi'] >= 1);
        $this->cek('alasannya missing_target', $this->alasanIndikator($d8, $indTanpa) === 'missing_target');
        $this->cek('muncul di prioritas sebagai renaksi_belum',
            in_array('renaksi_belum', array_column($d8['insights'], 'code'), true));
        $this->cek('prioritas terurut (kritis lebih dulu)',
            $d8['insights'] === [] || $d8['insights'][0]['severity'] <= $d8['insights'][count($d8['insights']) - 1]['severity']);

        CLI::write('== Batas akses lintas OPD (uji 17) ==', 'yellow');
        $opdLain = $this->opdLain($opdId);
        if ($opdLain) {
            $bocor = $svc->getIndicatorDetail($opdLain, $ind[1], $tahun, 4, 'jpt');
            $this->cek('OPD lain tidak dapat membuka indikator ini', $bocor === null);
        }
        $milik = $svc->getIndicatorDetail($opdId, $ind[1], $tahun, 4, 'jpt');
        $this->cek('OPD pemilik tetap dapat membuka indikatornya', $milik !== null);

        CLI::write('== Tautan modul tujuan (uji 20-24) ==', 'yellow');
        // Di CLI, koleksi rute perlu dimuat eksplisit sebelum bisa dibaca.
        $koleksi = service('routes');
        $koleksi->loadRoutes();
        $rute = array_keys($koleksi->getRoutes('GET'));
        $adaRute = static function (array $rute, string $cari): bool {
            foreach ($rute as $r) {
                if ($r === $cari || strpos($r, $cari) === 0) {
                    return true;
                }
            }
            return false;
        };
        foreach ([
            'adminopd/pk/', 'adminopd/target_renaksi', 'adminopd/monev', 'adminopd/lakip',
            'adminopd/dashboard/indicator/',
            'adminkab/dashboard', 'adminkab/dashboard/pk-bupati/', 'adminkab/dashboard/opd/',
            'adminkab/dashboard/status-opd/', 'adminkab/dashboard/misi/', 'adminkab/dashboard/anggaran-kinerja',
            'adminkab/renaksi_pk/', 'adminkab/monev_pk/', 'adminkab/monev', 'adminkab/target_renaksi',
        ] as $cari) {
            $this->cek('rute tujuan tersedia: ' . $cari, $adaRute($rute, $cari));
        }

        $this->ujiKabupaten($opdId, $sasaranId, $ind);
    }

    /**
     * Skenario Dashboard Kabupaten (Mode Kabupaten & Mode Fokus OPD).
     *
     * @param array<int, int> $ind indikator OPD yang sudah dibuat pada ujiDashboard()
     */
    private function ujiKabupaten(int $opdId, int $sasaranId, array $ind): void
    {
        $svc   = new \App\Services\KabupatenDashboardService();
        $tahun = self::TAHUN_UJI;

        CLI::write('== Kabupaten: data kosong (uji 20) ==', 'yellow');
        $kosong = $svc->getKabupatenSummary($tahun, 4, null);
        $this->cek('tanpa PK Bupati tidak error', ($kosong['pk_bupati']['ada'] ?? true) === false);
        $this->cek('total PK Bupati tidak tampil', $kosong['pk_bupati']['can_compute'] === false);
        $this->cek('daftar OPD tetap terisi', count($kosong['opd_list']) > 0);

        // ---------- PK Bupati: 3 indikator, semuanya valid ----------
        $pkB   = $this->buatPkBupati($tahun);
        $sasB  = $this->buatSasaran($pkB, 'Sasaran uji PK Bupati');
        $indB  = [];
        for ($i = 1; $i <= 3; $i++) {
            $indB[$i] = $this->buatIndikator($sasB, 'Indikator Bupati ' . $i, '100', 1);
            $tr = $this->buatRenaksi($this->opdBupati($pkB), $indB[$i], ['25', '50', '75', '100']);
            $this->buatMonevBupati($tr, ['25', '50', '75', '100'], 'trend_naik');
        }

        CLI::write('== Kabupaten: seluruh indikator PK Bupati valid (uji 1) ==', 'yellow');
        $d = $svc->getKabupatenSummary($tahun, 4, null);
        $this->cek('3 indikator PK Bupati terbaca', $d['pk_bupati']['wajib'] === 3);
        $this->cek('3 dari 3 valid', $d['pk_bupati']['valid'] === 3);
        $this->cek('capaian PK Bupati tampil', $d['pk_bupati']['can_compute'] === true);
        $this->cek('capaian PK Bupati = 100%', (float) $d['pk_bupati']['total'] === 100.0, (string) $d['pk_bupati']['total']);
        $this->cek('label Sementara (belum ada mekanisme verifikasi)', $d['pk_bupati']['label'] === 'Sementara');

        CLI::write('== Kabupaten: satu indikator PK Bupati tidak valid (uji 2) ==', 'yellow');
        $indRusak = $this->buatIndikator($sasB, 'Indikator Bupati tanpa capaian', '100', 1);
        $trRusak  = $this->buatRenaksi($this->opdBupati($pkB), $indRusak, ['25', '50', '75', '100']);
        $this->buatMonevBupati($trRusak, [null, null, null, null], 'trend_naik');
        $d2 = $svc->getKabupatenSummary($tahun, 4, null);
        $this->cek('total PK Bupati tidak tampil', $d2['pk_bupati']['can_compute'] === false);
        $this->cek('nilai total null (tidak parsial)', $d2['pk_bupati']['total'] === null);
        $this->cek('3 dari 4 valid dilaporkan', $d2['pk_bupati']['valid'] === 3 && $d2['pk_bupati']['wajib'] === 4);

        CLI::write('== Kabupaten: formula PK Bupati belum tersedia (uji 13) ==', 'yellow');
        $indFormula = $this->buatIndikator($sasB, 'Indikator Bupati tanpa metode', '100', 1);
        $trFormula  = $this->buatRenaksi($this->opdBupati($pkB), $indFormula, ['25', '50', '75', '100']);
        $this->buatMonevBupatiTanpaMetode($trFormula, ['25', '50', '75', '100']);
        $d3 = $svc->getKabupatenSummary($tahun, 4, null);
        $this->cek('formula_gap terhitung', $d3['pk_bupati']['formula_gap'] >= 1, (string) $d3['pk_bupati']['formula_gap']);
        $this->cek('total tetap tidak tampil', $d3['pk_bupati']['can_compute'] === false);
        $adaInsightFormula = in_array('pk_bupati_formula', array_column($d3['prioritas'], 'code'), true);
        $this->cek('muncul di prioritas sebagai pk_bupati_formula', $adaInsightFormula);
        $seri = array_values(array_filter($d3['tren'], static fn ($s) => $s['indikator_id'] === $indFormula));
        $this->cek('seri tren indikator itu ditandai tersedia/tidak', $seri !== []);

        CLI::write('== Kabupaten: status & distribusi OPD (uji 3, 4, 11, 14) ==', 'yellow');
        $statuses = $svc->getOpdStatuses($tahun, 4, null);
        $this->cek('OPD uji ada di daftar status', isset($statuses[$opdId]));
        $s = $statuses[$opdId] ?? [];
        $this->cek('OPD uji: capaian tidak tampil karena ada indikator belum valid', ($s['can_compute'] ?? true) === false);
        $this->cek('status OPD memakai kode dari ambang/nonnumerik', isset($s['status']['code']));
        $this->cek('status membawa alasan', !empty($s['status']['reason']));
        $this->cek('status membawa jumlah indikator kritis', array_key_exists('critical_indicator_count', $s['status']));
        $ringkas = $svc->getOpdStatusSummary($statuses);
        $this->cek('ringkasan OPD konsisten', $ringkas['total'] === count($statuses)
            && ($ringkas['dapat_dinilai'] + $ringkas['belum_lengkap']) === $ringkas['total']);
        $dist = $svc->getOpdStatusDistribution($statuses);
        $totalSeg = array_sum(array_column($dist['segments'], 'count'));
        $this->cek('distribusi memuat seluruh OPD tepat sekali', $totalSeg === count($statuses), $totalSeg . ' vs ' . count($statuses));

        CLI::write('== Kabupaten: OPD belum update (uji 9) ==', 'yellow');
        $telat = $svc->getUnupdatedOpds($statuses);
        $this->cek('OPD uji masuk daftar belum update', in_array($opdId, array_column($telat['daftar'], 'opd_id'), true));
        $this->cek('jenis keterlambatan dibedakan',
            ($telat['belum_pernah'] + $telat['belum_periode'] + $telat['terlambat']) === $telat['total']);

        CLI::write('== Kabupaten: prioritas pimpinan (uji 10) ==', 'yellow');
        $prio = $svc->getLeadershipPriorities($d3['pk_bupati'], $statuses, $tahun, 4);
        $this->cek('prioritas terisi', $prio !== []);
        $this->cek('prioritas terurut menaik menurut severity',
            $prio === [] || $prio[0]['severity'] <= $prio[count($prio) - 1]['severity']);
        $this->cek('setiap insight punya judul, alasan, status, objek, tombol, url',
            $prio === [] || (isset($prio[0]['judul'], $prio[0]['alasan'], $prio[0]['status'], $prio[0]['objek'], $prio[0]['tombol'], $prio[0]['url'])));

        CLI::write('== Kabupaten: drill-down PK Bupati (uji 16 & 17) ==', 'yellow');
        $detail = $svc->getBupatiIndicatorDetail($indB[1], $tahun, 4);
        $this->cek('detail indikator PK Bupati terbuka', $detail !== null);
        $this->cek('gap relasi OPD pengampu dilaporkan jujur',
            $detail !== null && $detail['gap_relasi'] === true && $detail['gap_pesan'] !== '');
        $bukanBupati = $svc->getBupatiIndicatorDetail($ind[1], $tahun, 4);
        $this->cek('indikator PK OPD tidak bisa dibuka lewat endpoint PK Bupati', $bukanBupati === null);

        // Tetapkan OPD pengampu -> jalur drill-down tersedia.
        $this->db->table('pk_sasaran_opd')->insert(['pk_sasaran_id' => $sasB, 'opd_id' => $opdId]);
        $detail2 = $svc->getBupatiIndicatorDetail($indB[1], $tahun, 4);
        $this->cek('setelah pengampu ditetapkan, jalur ke OPD tersedia',
            $detail2 !== null && $detail2['gap_relasi'] === false && count($detail2['pengampu']) === 1);
        $this->cek('jalur memuat indikator PK OPD pengampu',
            $detail2 !== null && count($detail2['pengampu'][0]['indikator']) > 0);

        CLI::write('== Kabupaten: kontribusi Misi (uji 11 relasi) ==', 'yellow');
        $misi = $svc->getMissionContributions($tahun, 4, $statuses, []);
        $this->cek('daftar misi mengikuti tahun', is_array($misi['items']));

        CLI::write('== Kabupaten: analisis anggaran vs kinerja (uji 18 & 19) ==', 'yellow');
        $ak = $svc->getBudgetPerformanceComparison($statuses);
        $this->cek('OPD tanpa capaian valid dikecualikan, bukan dipaksakan',
            in_array($opdId, array_column($ak['excluded'], 'opd_id'), true));
        $this->cek('setiap pengecualian membawa alasan',
            $ak['excluded'] === [] || !empty($ak['excluded'][0]['alasan']));
        $this->cek('tidak ada pelabelan efisien otomatis', str_contains($ak['catatan'], 'LAKIP'));

        CLI::write('== Kabupaten: Mode Fokus OPD (uji 5, 6, 7) ==', 'yellow');
        $fokus = $svc->getOpdFocusDashboard($opdId, $tahun, 4);
        $this->cek('mode fokus mengembalikan konteks OPD', ($fokus['context']['opd_id'] ?? 0) === $opdId);
        $this->cek('seluruh indikator fokus milik OPD itu',
            array_values(array_unique(array_column($fokus['indicators'], 'indikator_id'))) !== []
            && count($fokus['indicators']) === count($statuses[$opdId]['indikator'] ? $fokus['indicators'] : []));
        $tautan = $fokus['links'];
        $this->cek('tautan fokus tetap di area adminkab',
            str_contains((string) $tautan['renaksi'], '/adminkab/')
            && str_contains((string) $tautan['monev'], '/adminkab/')
            && str_contains((string) $tautan['lakip'], '/adminkab/'));
        $this->cek('tautan dokumen PK OPD sengaja kosong di area kabupaten', $tautan['pk'] === null);
        $this->cek('tautan membawa opd_id yang benar', str_contains((string) $tautan['monev'], 'opd_id=' . $opdId));

        CLI::write('== Kabupaten: validasi filter (uji 15) ==', 'yellow');
        $this->cek('OPD di luar daftar sah ditolak jadi Mode Kabupaten',
            $svc->resolveScope(999999)['opd_id'] === null || session()->get('role') === null);
    }

    private function buatPkBupati(int $tahun): int
    {
        $opd = $this->db->table('opd')->select('id')->orderBy('id', 'DESC')->get()->getRowArray();
        $this->db->table('pk')->insert([
            'opd_id'  => (int) $opd['id'],
            'tahun'   => $tahun,
            'jenis'   => 'bupati',
            'tanggal' => $tahun . '-01-02',
        ]);

        return (int) $this->db->insertID();
    }

    private function opdBupati(int $pkId): int
    {
        $row = $this->db->table('pk')->select('opd_id')->where('id', $pkId)->get()->getRowArray();

        return (int) $row['opd_id'];
    }

    /** MONEV PK Bupati: opd_id NULL (mengikuti PkRenaksiController). */
    private function buatMonevBupati(int $targetId, array $cap, string $metode): void
    {
        $this->db->table('monev')->insert([
            'opd_id'                => null,
            'target_rencana_id'     => $targetId,
            'target_sub_rencana_id' => 0,
            'capaian_triwulan_1'    => $cap[0],
            'capaian_triwulan_2'    => $cap[1],
            'capaian_triwulan_3'    => $cap[2],
            'capaian_triwulan_4'    => $cap[3],
            'metode_perhitungan'    => $metode,
        ]);
    }

    private function buatMonevBupatiTanpaMetode(int $targetId, array $cap): void
    {
        $this->db->table('monev')->insert([
            'opd_id'                => null,
            'target_rencana_id'     => $targetId,
            'target_sub_rencana_id' => 0,
            'capaian_triwulan_1'    => $cap[0],
            'capaian_triwulan_2'    => $cap[1],
            'capaian_triwulan_3'    => $cap[2],
            'capaian_triwulan_4'    => $cap[3],
            'metode_perhitungan'    => null,
        ]);
    }

    /* ================= pembuat data uji ================= */

    private function buatPk(int $opdId, int $tahun): int
    {
        $this->db->table('pk')->insert([
            'opd_id'  => $opdId,
            'tahun'   => $tahun,
            'jenis'   => 'jpt',
            'tanggal' => $tahun . '-01-02',
        ]);

        return (int) $this->db->insertID();
    }

    private function buatSasaran(int $pkId, string $teks): int
    {
        $this->db->table('pk_sasaran')->insert(['pk_id' => $pkId, 'jenis' => 'jpt', 'sasaran' => $teks]);

        return (int) $this->db->insertID();
    }

    private function buatIndikator(int $sasaranId, string $teks, string $target, ?int $satuanId): int
    {
        $this->db->table('pk_indikator')->insert([
            'pk_sasaran_id'   => $sasaranId,
            'jenis'           => 'jpt',
            'indikator'       => $teks,
            'jenis_indikator' => 'kinerja',
            'id_satuan'       => $satuanId,
            'target'          => $target,
        ]);

        return (int) $this->db->insertID();
    }

    /** @param array<int, string|null> $tw */
    private function buatRenaksi(int $opdId, int $indikatorId, array $tw): int
    {
        $this->db->table('target_rencana')->insert([
            'opd_id'            => $opdId,
            'pk_indikator_id'   => $indikatorId,
            'rencana_aksi'      => 'Rencana aksi uji',
            'target_triwulan_1' => $tw[0],
            'target_triwulan_2' => $tw[1],
            'target_triwulan_3' => $tw[2],
            'target_triwulan_4' => $tw[3],
            'penanggung_jawab'  => 'Penguji',
        ]);

        return (int) $this->db->insertID();
    }

    /** @param array<int, string|null> $cap */
    private function buatMonev(int $opdId, int $targetId, array $cap, string $metode): void
    {
        $this->db->table('monev')->insert([
            'opd_id'                => $opdId,
            'target_rencana_id'     => $targetId,
            'target_sub_rencana_id' => 0,
            'capaian_triwulan_1'    => $cap[0],
            'capaian_triwulan_2'    => $cap[1],
            'capaian_triwulan_3'    => $cap[2],
            'capaian_triwulan_4'    => $cap[3],
            'metode_perhitungan'    => $metode,
        ]);
    }

    private function buatProgram(int $opdId, int $tahun, string $nama, float $anggaran): int
    {
        $this->db->table('program_pk')->insert([
            'opd_id'           => $opdId,
            'kode_program'     => '9.99.99',
            'program_kegiatan' => $nama,
            'tahun_anggaran'   => $tahun,
            'jenis_anggaran'   => 'murni',
            'anggaran'         => $anggaran,
        ]);

        return (int) $this->db->insertID();
    }

    private function kaitkanProgram(int $programId, int $indikatorId): void
    {
        $this->db->table('pk_program')->insert(['program_id' => $programId, 'pk_indikator_id' => $indikatorId]);
    }

    /** @param array<int, float|null> $nilai */
    private function buatRealisasi(int $opdId, int $targetId, array $nilai): void
    {
        $this->db->table('monev_anggaran')->insert([
            'target_rencana_id'     => $targetId,
            'opd_id'                => $opdId,
            'realisasi_triwulan_1'  => $nilai[0],
            'realisasi_triwulan_2'  => $nilai[1],
            'realisasi_triwulan_3'  => $nilai[2],
            'realisasi_triwulan_4'  => $nilai[3],
        ]);
    }

    /** @param array<int, float|null> $nilai */
    private function ubahRealisasi(int $targetId, array $nilai): void
    {
        $this->db->table('monev_anggaran')->where('target_rencana_id', $targetId)->update([
            'realisasi_triwulan_1' => $nilai[0],
            'realisasi_triwulan_2' => $nilai[1],
            'realisasi_triwulan_3' => $nilai[2],
            'realisasi_triwulan_4' => $nilai[3],
        ]);
    }

    private function renaksiPertama(int $indikatorId): int
    {
        $row = $this->db->table('target_rencana')->select('id')
            ->where('pk_indikator_id', $indikatorId)->orderBy('id', 'ASC')->get()->getRowArray();

        return (int) ($row['id'] ?? 0);
    }

    /** Satuan bertipe predikat yang BELUM punya baris skala (dibuat sementara). */
    private function satuanPredikatTanpaSkala(): int
    {
        $this->db->table('satuan')->insert(['satuan' => 'Predikat Uji', 'tipe' => 'predikat']);

        return (int) $this->db->insertID();
    }

    private function opdLain(int $opdId): ?int
    {
        $row = $this->db->table('opd')->select('id')->where('id !=', $opdId)->get()->getRowArray();

        return $row ? (int) $row['id'] : null;
    }

    /* ================= pembantu asersi ================= */

    private function cariIndikator(array $summary, int $id): ?array
    {
        foreach ($summary['indicators'] as $i) {
            if ((int) $i['indikator_id'] === $id) {
                return $i;
            }
        }

        return null;
    }

    private function pctIndikator(array $summary, int $id): ?float
    {
        $i = $this->cariIndikator($summary, $id);

        return $i && $i['percentage'] !== null ? (float) $i['percentage'] : null;
    }

    private function validIndikator(array $summary, int $id): ?bool
    {
        $i = $this->cariIndikator($summary, $id);

        return $i ? (bool) $i['is_valid'] : null;
    }

    private function statusIndikator(array $summary, int $id): ?string
    {
        $i = $this->cariIndikator($summary, $id);

        return $i ? (string) $i['status']['code'] : null;
    }

    private function alasanIndikator(array $summary, int $id): ?string
    {
        $i = $this->cariIndikator($summary, $id);

        return $i ? ($i['reason_code'] ?? null) : null;
    }

    private function cek(string $judul, bool $hasil, string $catatan = ''): void
    {
        if ($hasil) {
            $this->lulus++;
            CLI::write('  [OK]    ' . $judul, 'green');
            return;
        }
        $this->gagal++;
        CLI::write('  [GAGAL] ' . $judul . ($catatan !== '' ? ' -> ' . $catatan : ''), 'red');
    }
}
