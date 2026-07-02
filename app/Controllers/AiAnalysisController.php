<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\CascadingModel;
use App\Libraries\GeminiClient;

class AiAnalysisController extends BaseController
{
    protected $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }

    public function index()
    {
        $periodeList = $this->db->table('rpjmd_misi')
            ->select('tahun_mulai, tahun_akhir')
            ->groupBy(['tahun_mulai', 'tahun_akhir'])
            ->orderBy('tahun_mulai', 'ASC')
            ->get()->getResultArray();

        return view('adminKabupaten/ai/index', [
            'periode_master' => $periodeList,
            'hasKey'         => trim(setting('gemini_api_key', '')) !== '',
        ]);
    }

    public function run()
    {
        $periode  = $this->request->getPost('periode');
        $question = trim((string) $this->request->getPost('question'));

        if (!$periode || strpos($periode, '-') === false) {
            return $this->fail('Pilih periode terlebih dahulu.');
        }

        $gemini = new GeminiClient();
        if (!$gemini->isConfigured()) {
            return $this->fail('API key Gemini belum diatur. Buka menu Pengaturan Aplikasi → Integrasi AI.');
        }

        [$start, $end] = array_map('intval', explode('-', $periode));
        $role  = session()->get('role');
        $model = new CascadingModel();

        if ($role === 'admin_opd') {
            $opdId   = session()->get('opd_id');
            $rows    = $opdId ? $model->getCascadingMatrixByOpd($opdId, $start, $end) : [];
            $summary = $this->summarizeOpd($rows);
            $scope   = 'Cascading Kinerja Perangkat Daerah (Renstra → Eselon II/III/IV)';
        } else {
            $rows    = $model->getMatrix($start, $end);
            $summary = $this->summarizeKab($rows, $start, $end);
            $scope   = 'Pohon Kinerja & Cascading RPJMD Kabupaten';
        }

        if (trim($summary) === '') {
            return $this->fail('Tidak ada data cascading untuk periode ini, sehingga belum bisa dianalisis.');
        }

        try {
            $text = $gemini->generate($this->buildPrompt($scope, $start, $end, $summary, $question));
        } catch (\Throwable $e) {
            $msg = $e->getMessage();
            $low = strtolower($msg);
            if (strpos($low, 'quota') !== false || strpos($low, 'exceeded') !== false
                || strpos($low, 'limit: 0') !== false || strpos($low, 'resource_exhausted') !== false
                || strpos($low, 'not found') !== false || strpos($low, 'not supported') !== false) {
                $msg .= '  —  Tips: model tidak tersedia / kuota habis untuk API key Anda. '
                      . 'Buka Pengaturan Aplikasi → Integrasi AI lalu pilih Model dari daftar yang tersedia '
                      . '(terisi otomatis dari API key Anda). Pastikan key dibuat di Google AI Studio (aistudio.google.com/apikey).';
            }
            return $this->fail($msg);
        }

        return $this->response->setJSON(['ok' => true, 'text' => $text, 'csrf' => csrf_hash()]);
    }

    private function fail(string $msg)
    {
        return $this->response->setJSON(['ok' => false, 'message' => $msg, 'csrf' => csrf_hash()]);
    }

    private function buildPrompt(string $scope, int $start, int $end, string $summary, string $question): string
    {
        $p  = "PERAN: Anda konsultan senior perencanaan pembangunan & akuntabilitas kinerja (SAKIP) "
            . "yang biasa mendampingi Bappeda dan Inspektorat Daerah. Gaya bahasa: Indonesia baku, formal, "
            . "objektif, dan analitis (bukan deskriptif).\n\n"
            . "TUGAS: Susun ANALISIS PROFESIONAL atas data {$scope} Pemerintah Kabupaten Pringsewu "
            . "periode {$start}–{$end} di bawah ini.\n\n"
            . "KETENTUAN:\n"
            . "- Format Markdown rapi: gunakan heading, **tebal**, bullet, dan tabel bila relevan.\n"
            . "- Setiap tabel Markdown WAJIB valid: diawali baris kosong, jumlah kolom konsisten antar baris, "
            . "baris pemisah memakai '---' (mis. | --- | --- |), dan TANPA pergantian baris di dalam sel.\n"
            . "- Rujuk ANGKA & NAMA spesifik dari data (jumlah & persentase indikator yang sudah/belum dipetakan, "
            . "sebutkan nama indikator/sasaran yang bermasalah).\n"
            . "- Berikan PENILAIAN dan interpretasi, jangan sekadar mengulang data.\n"
            . "- Rekomendasi harus konkret, terukur, dan berprioritas.\n"
            . "- DILARANG mengarang data di luar yang diberikan; bila informasi terbatas, nyatakan keterbatasannya.\n\n"
            . "STRUKTUR WAJIB (gunakan heading persis seperti ini):\n"
            . "## Ringkasan Eksekutif\n"
            . "3–5 kalimat inti kondisi dan kesimpulan utama.\n\n"
            . "## Penilaian Keselarasan (Cascading)\n"
            . "Nilai keselarasan Tujuan → Sasaran → Indikator → Program/OPD. Beri level **Baik / Cukup / Perlu Perbaikan** disertai alasan berbasis data.\n\n"
            . "## Kelengkapan Pemetaan\n"
            . "Statistik & persentase: indikator/sasaran yang sudah memiliki OPD & program vs yang belum.\n\n"
            . "## Temuan & Gap\n"
            . "Daftar temuan spesifik: indikator/sasaran tanpa OPD atau program, indikator yang kurang terukur (tidak ada satuan/target), potensi duplikasi/ketidakselarasan.\n\n"
            . "## Rekomendasi Prioritas\n"
            . "Sajikan sebagai tabel Markdown VALID dengan TEPAT 4 kolom, diawali baris kosong. "
            . "Setiap sel ringkas (maks ~15 kata) dan kolom Prioritas hanya berisi Tinggi/Sedang/Rendah. "
            . "Minimal 3 baris, urut dari prioritas tertinggi. Gunakan format persis berikut:\n\n"
            . "| No | Rekomendasi | Alasan/Dampak | Prioritas |\n"
            . "| --- | --- | --- | --- |\n"
            . "| 1 | Petakan OPD untuk indikator X | Menutup gap akuntabilitas kinerja | Tinggi |\n\n"
            . "## Kesimpulan\n"
            . "2–3 kalimat penutup.\n\n";

        if ($question !== '') {
            $p .= "PERMINTAAN KHUSUS PENGGUNA (prioritaskan dalam analisis): \"{$question}\"\n\n";
        }

        return $p . "=== DATA ===\n" . $summary;
    }

    /** Ringkasan cascading kabupaten dari hasil CascadingModel::getMatrix(). */
    private function summarizeKab(array $rows, int $start, int $end): string
    {
        if (empty($rows)) {
            return '';
        }

        $visiRow = $this->db->table('rpjmd_misi m')
            ->select('rv.visi')
            ->join('rpjmd_visi rv', 'rv.id = m.rpjmd_visi_id', 'left')
            ->where('m.tahun_mulai', $start)->where('m.tahun_akhir', $end)
            ->orderBy('m.id', 'ASC')->get()->getRowArray();
        $visi = $visiRow['visi'] ?? '-';

        $misiRows = $this->db->table('rpjmd_misi')->select('misi')
            ->where('tahun_mulai', $start)->where('tahun_akhir', $end)
            ->orderBy('id', 'ASC')->get()->getResultArray();

        // Agregasi per indikator
        $byInd   = [];
        $tujuan  = [];
        $sasaran = [];
        $opds    = [];
        foreach ($rows as $r) {
            $tujuan[$r['tujuan_id']]   = $r['tujuan_rpjmd'];
            $sasaran[$r['sasaran_id']] = $r['sasaran_rpjmd'];
            if (!empty($r['nama_opd'])) {
                $opds[$r['nama_opd']] = true;
            }
            $id = $r['indikator_id'] ?? null;
            if (!$id) {
                continue;
            }
            if (!isset($byInd[$id])) {
                $byInd[$id] = [
                    'tujuan'   => $r['tujuan_rpjmd'],
                    'sasaran'  => $r['sasaran_rpjmd'],
                    'nama'     => $r['indikator_sasaran'],
                    'satuan'   => $r['satuan'] ?? '-',
                    'baseline' => $r['baseline'] ?? '-',
                    'targets'  => $r['targets'] ?? [],
                    'opds'     => [],
                    'progs'    => [],
                ];
            }
            if (!empty($r['nama_opd'])) {
                $byInd[$id]['opds'][$r['nama_opd']] = true;
            }
            if (!empty($r['program_kegiatan'])) {
                $byInd[$id]['progs'][$r['program_kegiatan']] = true;
            }
        }

        $indTanpaOpd = 0;
        foreach ($byInd as $i) {
            if (empty($i['opds'])) {
                $indTanpaOpd++;
            }
        }

        $out  = "VISI: {$visi}\n";
        $out .= "MISI:\n";
        foreach ($misiRows as $k => $m) {
            $out .= '  ' . ($k + 1) . '. ' . $m['misi'] . "\n";
        }
        $out .= sprintf(
            "\nSTATISTIK: %d Tujuan, %d Sasaran, %d Indikator Sasaran, %d OPD terlibat, %d indikator BELUM punya OPD/program.\n\n",
            count($tujuan),
            count($sasaran),
            count($byInd),
            count($opds),
            $indTanpaOpd
        );

        $out .= "RINCIAN INDIKATOR:\n";
        $n = 0;
        foreach ($byInd as $i) {
            if (++$n > 250) {
                $out .= "  ...(dipotong)\n";
                break;
            }
            $tgt = [];
            foreach ($i['targets'] as $th => $v) {
                $tgt[] = "$th=$v";
            }
            $opd  = $i['opds'] ? implode('; ', array_keys($i['opds'])) : 'BELUM ADA OPD';
            $prog = $i['progs'] ? implode('; ', array_keys($i['progs'])) : 'belum ada program';
            $out .= sprintf(
                "- [Tujuan: %s] [Sasaran: %s] Indikator: %s (satuan: %s, baseline: %s; target: %s) | OPD: %s | Program: %s\n",
                $this->cut($i['tujuan'], 80),
                $this->cut($i['sasaran'], 90),
                $this->cut($i['nama'], 110),
                $i['satuan'],
                $i['baseline'],
                $tgt ? implode(', ', $tgt) : '-',
                $this->cut($opd, 120),
                $this->cut($prog, 160)
            );
        }

        return $out;
    }

    /** Ringkasan cascading OPD dari hasil CascadingModel::getCascadingMatrixByOpd(). */
    private function summarizeOpd(array $rows): string
    {
        if (empty($rows)) {
            return '';
        }

        $es2 = [];
        $es3 = [];
        $es4 = [];
        $lines = [];
        $n = 0;
        foreach ($rows as $r) {
            if (!empty($r['renstra_sasaran_id'])) {
                $es2[$r['renstra_sasaran_id']] = true;
            }
            if (!empty($r['es3_id'])) {
                $es3[$r['es3_id']] = true;
            }
            if (!empty($r['es4_id'])) {
                $es4[$r['es4_id']] = true;
            }

            if (++$n <= 250) {
                $lines[] = sprintf(
                    "- Tujuan RPJMD: %s | Sasaran RPJMD: %s | Tujuan Renstra: %s | Sasaran ES II: %s | Indikator ES II: %s | Sasaran ES III: %s | Indikator ES III: %s | Sasaran ES IV: %s | Indikator ES IV: %s",
                    $this->cut($r['tujuan_rpjmd'] ?? '-', 70),
                    $this->cut($r['sasaran_rpjmd'] ?? '-', 70),
                    $this->cut($r['renstra_tujuan'] ?? '-', 70),
                    $this->cut($r['renstra_sasaran'] ?? '-', 70),
                    $this->cut($r['indikator_sasaran'] ?? '-', 80),
                    $this->cut($r['es3_sasaran'] ?? '-', 70),
                    $this->cut($r['es3_indikator'] ?? '-', 80),
                    $this->cut($r['es4_sasaran'] ?? '-', 70),
                    $this->cut($r['es4_indikator'] ?? '-', 80)
                );
            }
        }

        $out  = sprintf(
            "STATISTIK: %d Sasaran ES II, %d Sasaran ES III, %d Sasaran ES IV.\n\nRINCIAN:\n",
            count($es2),
            count($es3),
            count($es4)
        );
        $out .= implode("\n", $lines);
        if ($n > 250) {
            $out .= "\n  ...(dipotong)";
        }

        return $out;
    }

    private function cut(?string $s, int $len): string
    {
        $s = trim((string) $s);
        if ($s === '') {
            return '-';
        }
        return mb_strlen($s) > $len ? mb_substr($s, 0, $len - 1) . '…' : $s;
    }
}
