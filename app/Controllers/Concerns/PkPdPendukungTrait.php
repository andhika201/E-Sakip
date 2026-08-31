<?php

namespace App\Controllers\Concerns;

/**
 * Penanggung Jawab / Perangkat Daerah PENDUKUNG sebuah Sasaran PK Bupati.
 *
 * Dipakai bersama oleh:
 *   - AdminOpd\PkRenaksiController  (layar & cetak Target dan Rencana Aksi)
 *   - Api\TargetRenaksiController   (endpoint /api/target-renaksi/bupati)
 *
 * Sumber datanya dua lapis, persis seperti yang tampil di web:
 *   1. OTOMATIS — teks Sasaran PK Bupati dicocokkan ke Sasaran RPJMD lewat
 *      rantai Renstra (best-effort, karena teksnya diketik ulang di PK).
 *   2. MANUAL   — override yang disimpan admin di `pk_sasaran_opd`; bila ada,
 *      ia MENGGANTIKAN hasil otomatis (bukan menambah).
 *
 * Pemakainya wajib menyediakan `$this->db` (BaseConnection).
 */
trait PkPdPendukungTrait
{
    /** Normalisasi teks untuk pencocokan: huruf kecil, spasi tunggal, tanpa spasi tepi. */
    private function normTeksSasaran($teks): string
    {
        return strtolower(trim(preg_replace('/\s+/', ' ', (string) $teks)));
    }

    /**
     * Peta OTOMATIS Penanggung Jawab Perangkat Daerah untuk PK Bupati (best-effort):
     * teks sasaran RPJMD (dinormalisasi) => [ ['id'=>opd_id,'nama'=>nama_opd], ... ],
     * ditarik dari rantai Renstra (renstra_tujuan.rpjmd_sasaran_id -> rpjmd_sasaran;
     * OPD dari renstra_sasaran.opd_id). Dicocokkan dgn teks sasaran PK Bupati di view.
     */
    private function autoPdBySasaran(): array
    {
        $norm = fn($s) => $this->normTeksSasaran($s);

        $rows = $this->db->table('renstra_tujuan rt')
            ->select('rsp.id AS sasaran_id, rsp.sasaran_rpjmd, ro.opd_id, o.nama_opd')
            ->join('renstra_sasaran ro', 'ro.renstra_tujuan_id = rt.id', 'inner')
            ->join('rpjmd_sasaran rsp', 'rsp.id = rt.rpjmd_sasaran_id', 'inner')
            ->join('opd o', 'o.id = ro.opd_id', 'inner')
            ->where('rt.rpjmd_sasaran_id IS NOT NULL')
            ->groupBy('rsp.id, rsp.sasaran_rpjmd, ro.opd_id, o.nama_opd')
            ->orderBy('o.nama_opd', 'ASC')
            ->get()->getResultArray();

        $bySasId = []; // sasaran_id => [ ['id','nama'], ... ]
        $map     = []; // norm(teks) => [ ['id','nama'], ... ]  (kunci: teks SASARAN)
        foreach ($rows as $r) {
            $opd = ['id' => (int) $r['opd_id'], 'nama' => $r['nama_opd']];
            $bySasId[(int) $r['sasaran_id']][] = $opd;
            $map[$norm($r['sasaran_rpjmd'])][]  = $opd;
        }

        // Fallback: kunci juga per-INDIKATOR RPJMD -> OPD sasaran induknya
        // (mengatasi teks sasaran PK Bupati yang beda/typo, mis. "pemerintaha").
        foreach ($this->db->table('rpjmd_indikator_sasaran')
            ->select('sasaran_id, indikator_sasaran')->get()->getResultArray() as $ir) {
            $sid = (int) $ir['sasaran_id'];
            if (empty($bySasId[$sid])) { continue; }
            $key = $norm($ir['indikator_sasaran']);
            if ($key !== '' && !isset($map[$key])) { $map[$key] = $bySasId[$sid]; }
        }

        return $map;
    }

    /** Mapping MANUAL Perangkat Daerah pendukung per Sasaran PK: pk_sasaran_id => [ ['id','nama'], ... ]. */
    private function manualPdBySasaran(): array
    {
        if (!$this->db->tableExists('pk_sasaran_opd')) {
            return [];
        }
        $rows = $this->db->table('pk_sasaran_opd pso')
            ->select('pso.pk_sasaran_id, pso.opd_id, o.nama_opd')
            ->join('opd o', 'o.id = pso.opd_id', 'inner')
            ->orderBy('o.nama_opd', 'ASC')
            ->get()->getResultArray();
        $map = [];
        foreach ($rows as $r) {
            $map[(int) $r['pk_sasaran_id']][] = ['id' => (int) $r['opd_id'], 'nama' => $r['nama_opd']];
        }
        return $map;
    }

    /**
     * Saran OTOMATIS OPD untuk sebuah Sasaran PK (dipakai sbg prefill form kelola PD).
     * Meniru logika pencocokan di tampilan: cocokkan teks sasaran ke mapping cascading,
     * bila kosong fallback lewat teks indikator sasaran tsb.
     */
    private function autoOpdsForSasaran(int $pkSasaranId, string $sasaranText): array
    {
        $map  = $this->autoPdBySasaran();
        $norm = fn($s) => $this->normTeksSasaran($s);
        $opds = $map[$norm($sasaranText)] ?? [];
        if (empty($opds)) {
            $inds = $this->db->table('pk_indikator')->select('indikator')
                ->where('pk_sasaran_id', $pkSasaranId)->get()->getResultArray();
            foreach ($inds as $ir) {
                $k = $norm($ir['indikator']);
                if ($k !== '' && !empty($map[$k])) { $opds = $map[$k]; break; }
            }
        }
        return $opds;
    }

    /**
     * Perangkat Daerah pendukung yang BENAR-BENAR tampil untuk satu Sasaran PK,
     * memakai peta yang sudah ditarik sekali (hindari query per sasaran).
     *
     * Urutan persis seperti di layar: manual mengalahkan otomatis; kalau
     * otomatis tidak ketemu lewat teks sasaran, dicoba lewat teks indikatornya.
     *
     * @param array<int, string> $indikatorTexts teks indikator pada sasaran tsb (untuk fallback)
     * @param array              $autoPd         hasil autoPdBySasaran()
     * @param array              $manualPd       hasil manualPdBySasaran()
     *
     * @return array{sumber: string|null, opd: array<int, array{id: int, nama: string}>}
     */
    private function pdPendukungSasaran(
        int $pkSasaranId,
        string $sasaranText,
        array $indikatorTexts,
        array $autoPd,
        array $manualPd
    ): array {
        $manual = $manualPd[$pkSasaranId] ?? [];
        if (!empty($manual)) {
            return ['sumber' => 'manual', 'opd' => $manual];
        }

        $opds = $autoPd[$this->normTeksSasaran($sasaranText)] ?? [];
        if (empty($opds)) {
            foreach ($indikatorTexts as $teks) {
                $k = $this->normTeksSasaran($teks);
                if ($k !== '' && !empty($autoPd[$k])) {
                    $opds = $autoPd[$k];
                    break;
                }
            }
        }

        return ['sumber' => empty($opds) ? null : 'otomatis', 'opd' => $opds];
    }
}
