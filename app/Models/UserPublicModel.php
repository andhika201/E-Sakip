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
            ->select('rs.id as sasaran_id, o.id as opd_id_val, o.nama_opd, rs.sasaran, ris.indikator_sasaran, ris.id as indikator_id, ris.satuan')
            ->join('opd o', 'o.id = rs.opd_id')
            ->join('renstra_indikator_sasaran ris', 'ris.renstra_sasaran_id = rs.id', 'left')
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

    public function getIkuOpdData($opd_id)
    {
        $query = $this->db->table('iku')
            ->select('iku.id as iku_id, o.id as opd_id_val, o.nama_opd, iku.definisi, rs.sasaran, ris.indikator_sasaran as indikator, ris.satuan, iku.renstra_id')
            ->join('renstra_indikator_sasaran ris', 'ris.id = iku.renstra_id')
            ->join('renstra_sasaran rs', 'rs.id = ris.renstra_sasaran_id')
            ->join('opd o', 'o.id = rs.opd_id')
            ->where('iku.status', 'selesai')
            ->where('iku.renstra_id IS NOT NULL');

        if ($opd_id !== 'all') {
            $query->where('rs.opd_id', (int) $opd_id);
        }

        $ikuOpdDataRaw = $query->get()->getResultArray();

        $targets = $this->db->table('renstra_target')->get()->getResultArray();
        $targetMap = [];
        $tahun_set = [];
        foreach ($targets as $t) {
            $targetMap[$t['renstra_indikator_id']][$t['tahun']] = $t['target'];
            $tahun_set[$t['tahun']] = true;
        }
        $tahunList = array_keys($tahun_set);
        sort($tahunList);

        $ikuOpdData = [];
        foreach ($ikuOpdDataRaw as $row) {
            $renstra_id = $row['renstra_id'];
            $row['target_capaian'] = $targetMap[$renstra_id] ?? [];
            $ikuOpdData[] = $row;
        }

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
