<?php

namespace App\Models;

use CodeIgniter\Model;

class UserPublicModel extends Model
{
    protected $db;

    public function __construct()
    {
        parent::__construct();
        $this->db = \Config\Database::connect();
    }

    public function getRenstraData($opd_id)
    {
        $query = $this->db->table('renstra_sasaran rs')
            ->select('rs.id as sasaran_id, o.id as opd_id_val, o.nama_opd, rs.sasaran, ris.indikator_sasaran, ris.id as indikator_id, s.satuan')
            ->join('opd o', 'o.id = rs.opd_id')
            ->join('renstra_indikator_sasaran ris', 'ris.renstra_sasaran_id = rs.id', 'left')
            ->join('satuan s', 's.id = ris.satuan', 'left')
            ->where('rs.status', 'selesai');

        if ($opd_id !== 'all') {
            $query->where('rs.opd_id', (int) $opd_id);
        }

        $renstraDataRaw = $query->get()->getResultArray();

        $targets = $this->db->table('renstra_target')->get()->getResultArray();
        $targetMap = [];
        $tahun_set = [];
        foreach ($targets as $t) {
            $targetMap[$t['renstra_indikator_id']][$t['tahun']] = $t['target'];
            $tahun_set[$t['tahun']] = true;
        }
        $tahunList = array_keys($tahun_set);
        sort($tahunList);

        $renstraData = [];
        if (!empty($renstraDataRaw)) {
            foreach ($renstraDataRaw as $row) {
                $indikator_id = $row['indikator_id'];
                $tcap = $targetMap[$indikator_id] ?? [];

                $renstraData[] = [
                    'opd' => $row['nama_opd'],
                    'sasaran' => $row['sasaran'],
                    'indikator' => $row['indikator_sasaran'],
                    'satuan' => $row['satuan'],
                    'target_capaian' => $tcap
                ];
            }
        }

        return [
            'renstraData' => $renstraData,
            'tahunList' => $tahunList
        ];
    }

    /**
     * IKU OPD untuk halaman publik.
     *
     * Sejak IKU berdiri sendiri, sumbernya `iku_indikator`/`iku_sasaran`/`iku_target`
     * (difilter lewat `iku_sasaran.opd_id`), bukan lagi tabel `iku` yang menempel
     * ke renstra. Bentuk keluarannya sengaja dipertahankan agar view tidak berubah.
     */
    public function getIkuOpdData($opd_id)
    {
        $query = $this->db->table('iku_indikator ind')
            ->select("
                ind.id AS iku_id,
                o.id AS opd_id_val,
                o.nama_opd,
                ind.definisi,
                sas.sasaran,
                ind.indikator AS indikator,
                COALESCE(s.satuan, NULLIF(ind.satuan, '')) AS satuan
            ", false)
            ->join('iku_sasaran sas', 'sas.id = ind.iku_sasaran_id')
            ->join('opd o', 'o.id = sas.opd_id')
            ->join('satuan s', "ind.satuan REGEXP '^[0-9]+$' AND s.id = ind.satuan", 'left', false)
            ->where('ind.status', 'selesai')
            ->orderBy('o.nama_opd', 'ASC')
            ->orderBy('sas.urutan', 'ASC')
            ->orderBy('sas.id', 'ASC')
            ->orderBy('ind.urutan', 'ASC')
            ->orderBy('ind.id', 'ASC');

        if ($opd_id !== 'all') {
            $query->where('sas.opd_id', (int) $opd_id);
        }

        $ikuOpdData = $query->get()->getResultArray();

        $indikatorIds = array_column($ikuOpdData, 'iku_id');
        $targetMap = [];
        $tahun_set = [];

        if (!empty($indikatorIds)) {
            $targets = $this->db->table('iku_target')
                ->select('iku_indikator_id, tahun, target')
                ->whereIn('iku_indikator_id', $indikatorIds)
                ->orderBy('tahun', 'ASC')
                ->get()
                ->getResultArray();

            foreach ($targets as $t) {
                $targetMap[(int) $t['iku_indikator_id']][(int) $t['tahun']] = $t['target'];
                $tahun_set[(int) $t['tahun']] = true;
            }
        }

        $tahunList = array_keys($tahun_set);
        sort($tahunList);

        foreach ($ikuOpdData as &$row) {
            $row['target_capaian'] = $targetMap[(int) $row['iku_id']] ?? [];
        }
        unset($row);

        return [
            'ikuOpdData' => $ikuOpdData,
            'tahunList' => $tahunList
        ];
    }

    public function getPkDataByJenis($jenis, $tahun, $opd_id)
    {
        $pkData = [];
        if ($tahun) {
            $query = $this->db->table('pk p')
                ->select('
                    p.id as pk_id,
                    o.nama_opd,
                    ps.id as sasaran_id,
                    ps.sasaran,
                    pi.id as indikator_id,
                    pi.indikator,
                    pi.target,
                    s.satuan as satuan_nama
                ')
                ->join('opd o', 'o.id = p.opd_id', 'left')
                ->join('pk_sasaran ps', 'ps.pk_id = p.id', 'inner')
                ->join('pk_indikator pi', 'pi.pk_sasaran_id = ps.id', 'inner')
                ->join('satuan s', 's.id = pi.id_satuan', 'left')
                ->where('p.jenis', $jenis)
                ->where('p.tahun', $tahun);

            if ($opd_id !== 'all') {
                $query->where('p.opd_id', (int) $opd_id);
            }

            $rawData = $query->orderBy('p.opd_id', 'ASC')
                             ->orderBy('ps.id', 'ASC')
                             ->orderBy('pi.id', 'ASC')
                             ->get()->getResultArray();

            foreach ($rawData as $row) {
                 $pkData[] = [
                     'opd' => $row['nama_opd'],
                     'sasaran' => $row['sasaran'],
                     'indikator' => $row['indikator'],
                     'target' => $row['target'],
                     'satuan' => $row['satuan_nama'] ?? '-'
                 ];
            }
        }
        return $pkData;
    }

    public function getPkBupatiData($tahun)
    {
        $rawData = [];
        if ($tahun) {
            $rawData = $this->db->table('pk p')
                ->select('
                    p.id as pk_id,
                    ps.id as sasaran_id,
                    ps.sasaran,
                    pi.id as indikator_id,
                    pi.indikator,
                    pi.target,
                    s.satuan as satuan_nama
                ')
                ->join('pk_sasaran ps', 'ps.pk_id = p.id', 'inner')
                ->join('pk_indikator pi', 'pi.pk_sasaran_id = ps.id', 'inner')
                ->join('satuan s', 's.id = pi.id_satuan', 'left')
                ->where('p.jenis', 'bupati')
                ->where('p.tahun', $tahun)
                ->orderBy('ps.id', 'ASC')
                ->orderBy('pi.id', 'ASC')
                ->get()->getResultArray();
        }

        $sasaranList = [];
        foreach ($rawData as $row) {
            $sid = $row['sasaran_id'];
            if (!isset($sasaranList[$sid])) {
                $sasaranList[$sid] = [
                    'sasaran' => $row['sasaran'],
                    'indikator' => [],
                ];
            }
            if (!empty($row['indikator_id'])) {
                $sasaranList[$sid]['indikator'][] = [
                    'indikator' => $row['indikator'],
                    'target' => $row['target'],
                    'satuan' => $row['satuan_nama'] ?? '-',
                ];
            }
        }
        return array_values($sasaranList);
    }
}
