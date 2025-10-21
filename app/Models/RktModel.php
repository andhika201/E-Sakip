<?php

namespace App\Models;

use CodeIgniter\Model;

class RktModel extends Model
{
    protected $table = 'rkt';
    protected $primaryKey = 'id';
    protected $allowedFields = ['opd_id', 'indikator_id', 'program_id'];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    /**
     * Simpan seluruh data RENJA (RKT + Kegiatan + Subkegiatan)
     */
    public function saveRkt($data)
    {
        $db = \Config\Database::connect();
        $db->transStart();

        $kegiatanModel = $db->table('rkt_kegiatan');
        $subModel = $db->table('rkt_subkegiatan');

        if (!empty($data['program'])) {
            foreach ($data['program'] as $prog) {
                // 1️⃣ Simpan program ke tabel rkt
                $rktData = [
                    'opd_id' => $data['opd_id'],
                    'indikator_id' => $data['indikator_id'],
                    'program_id' => $prog['program_id'],
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ];

                $this->insert($rktData);
                $rktId = $this->getInsertID();

                // 2️⃣ Simpan kegiatan
                if (!empty($prog['kegiatan'])) {
                    foreach ($prog['kegiatan'] as $keg) {
                        $kegiatanData = [
                            'rkt_id' => $rktId,
                            'nama_kegiatan' => $keg['nama_kegiatan'],
                            'program_id' => $prog['program_id'],
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s'),
                        ];
                        $kegiatanModel->insert($kegiatanData);
                        $kegiatanId = $db->insertID();

                        // 3️⃣ Simpan subkegiatan
                        if (!empty($keg['subkegiatan'])) {
                            foreach ($keg['subkegiatan'] as $sub) {
                                $subModel->insert([
                                    'kegiatan_id' => $kegiatanId,
                                    'nama_subkegiatan' => $sub['nama_subkegiatan'],
                                    'target_anggaran' => $sub['target_anggaran'],
                                    'created_at' => date('Y-m-d H:i:s'),
                                    'updated_at' => date('Y-m-d H:i:s'),
                                ]);
                            }
                        }
                    }
                }
            }
        }

        $db->transComplete();
        return $db->transStatus();
    }

    /**
     * Ambil data lengkap RENJA (RKT + Kegiatan + Subkegiatan)
     */
    public function getRktByOpd($opdId)
    {
        $db = \Config\Database::connect();

        // Ambil data RKT utama
        $rktData = $db->table('rkt r')
            ->select('r.*, s.sasaran, i.indikator_sasaran, i.satuan, t.target, p.program_kegiatan AS program_nama')
            ->join('renstra_indikator_sasaran i', 'i.id = r.indikator_id', 'left')
            ->join('renstra_sasaran s', 's.id = i.renstra_sasaran_id', 'left')

            // SUBQUERY: ambil target per indikator per tahun (tanpa alias i/r dulu)
            ->join(
                '(SELECT t2.renstra_indikator_id, t2.tahun, t2.target
      FROM renstra_target t2
      ORDER BY t2.tahun ASC
     ) t',
                't.renstra_indikator_id = i.id AND t.tahun = r.tahun',
                'left'
            )


            ->join('program_pk p', 'p.id = r.program_id', 'left')
            ->where('r.opd_id', $opdId)
            ->orderBy('r.id', 'ASC')
            ->get()
            ->getResultArray();

        // Ambil kegiatan & subkegiatan
        foreach ($rktData as &$rkt) {
            $kegiatans = $db->table('rkt_kegiatan')
                ->where('rkt_id', $rkt['id'])
                ->orderBy('id', 'ASC')
                ->get()
                ->getResultArray();

            foreach ($kegiatans as &$kegiatan) {
                $subkegiatan = $db->table('rkt_subkegiatan')
                    ->where('kegiatan_id', $kegiatan['id'])
                    ->orderBy('id', 'ASC')
                    ->get()
                    ->getResultArray();

                $kegiatan['subkegiatan'] = $subkegiatan;
            }

            $rkt['kegiatan'] = $kegiatans;
        }

        return $rktData;
    }

    public function getIndicatorsWithRkt(int $opdId, $tahun)
    {
         $db = \Config\Database::connect();

        // 1) Ambil semua indikator RENSTRA untuk OPD tersebut (master list)
        //    dan ambil target RENSTRA untuk $tahun via correlated subquery.
        $sql = "
            SELECT 
                i.*,
                s.sasaran,
                s.opd_id,
                (SELECT target 
                   FROM renstra_target t2 
                   WHERE t2.renstra_indikator_id = i.id 
                     AND t2.tahun = ? 
                   LIMIT 1
                ) AS target
            FROM renstra_indikator_sasaran i
            JOIN renstra_sasaran s ON s.id = i.renstra_sasaran_id
            WHERE s.opd_id = ?
            ORDER BY s.id ASC, i.id ASC
        ";

        $indicators = $db->query($sql, [$tahun, $opdId])->getResultArray();

        // 2) Untuk tiap indikator, ambil semua RKT (program) untuk OPD & tahun.
        foreach ($indicators as &$ind) {

            $rkts = $db->table('rkt r')
                ->select('r.*, p.program_kegiatan AS program_nama, p.anggaran AS program_anggaran')
                ->join('program_pk p', 'p.id = r.program_id', 'left')
                ->where('r.opd_id', $opdId)
                ->where('r.tahun', $tahun)
                ->where('r.indikator_id', $ind['id'])
                ->orderBy('r.id', 'ASC')
                ->get()
                ->getResultArray();

            // Untuk setiap RKT (program) ambil kegiatan & subkegiatan
            foreach ($rkts as &$rkt) {
                $kegiatans = $db->table('rkt_kegiatan rk')
                    ->select('rk.*')
                    ->where('rk.rkt_id', $rkt['id'])
                    ->orderBy('rk.id', 'ASC')
                    ->get()
                    ->getResultArray();

                foreach ($kegiatans as &$kegiatan) {
                    $subkegiatan = $db->table('rkt_subkegiatan rs')
                        ->select('rs.*')
                        ->where('rs.kegiatan_id', $kegiatan['id'])
                        ->orderBy('rs.id', 'ASC')
                        ->get()
                        ->getResultArray();

                    $kegiatan['subkegiatan'] = $subkegiatan;
                }

                $rkt['kegiatan'] = $kegiatans;
            }

            // attach rkts (may be empty array)
            $ind['rkts'] = $rkts;
        }

        return $indicators;
    }


    public function getRktbyIndicator(int $opdId, $tahun, $indicatorId)
    {
         $db = \Config\Database::connect();

        // 1) Ambil semua indikator RENSTRA untuk OPD tersebut (master list)
        //    dan ambil target RENSTRA untuk $tahun via correlated subquery.
        $sql = "
            SELECT 
                i.*,
                s.sasaran,
                s.opd_id,
                (SELECT target 
                   FROM renstra_target t2 
                   WHERE t2.renstra_indikator_id = i.id 
                     AND t2.tahun = ? 
                   LIMIT 1
                ) AS target
            FROM renstra_indikator_sasaran i
            JOIN renstra_sasaran s ON s.id = i.renstra_sasaran_id
            WHERE s.opd_id = ?
            ORDER BY s.id ASC, i.id ASC
        ";

        $indicators = $db->query($sql, [$tahun, $opdId])->getResultArray();

        // 2) Untuk tiap indikator, ambil semua RKT (program) untuk OPD & tahun.
        foreach ($indicators as &$ind) {

            $rkts = $db->table('rkt r')
                ->select('r.*, p.program_kegiatan AS program_nama, p.anggaran AS program_anggaran')
                ->join('program_pk p', 'p.id = r.program_id', 'left')
                ->where('r.opd_id', $opdId)
                ->where('r.tahun', $tahun)
                ->where('r.indikator_id', $ind['id'])
                ->orderBy('r.id', 'ASC')
                ->get()
                ->getResultArray();

            // Untuk setiap RKT (program) ambil kegiatan & subkegiatan
            foreach ($rkts as &$rkt) {
                $kegiatans = $db->table('rkt_kegiatan rk')
                    ->select('rk.*')
                    ->where('rk.rkt_id', $rkt['id'])
                    ->orderBy('rk.id', 'ASC')
                    ->get()
                    ->getResultArray();

                foreach ($kegiatans as &$kegiatan) {
                    $subkegiatan = $db->table('rkt_subkegiatan rs')
                        ->select('rs.*')
                        ->where('rs.kegiatan_id', $kegiatan['id'])
                        ->orderBy('rs.id', 'ASC')
                        ->get()
                        ->getResultArray();

                    $kegiatan['subkegiatan'] = $subkegiatan;
                }

                $rkt['kegiatan'] = $kegiatans;
            }

            // attach rkts (may be empty array)
            $ind['rkts'] = $rkts;
        }

        return $indicators;
    }
    /**
     * Ambil data OPD (nama_opd) by id
     */
    public function getOpdById(int $opdId)
    {
        $db = \Config\Database::connect();
        return $db->table('opd')->where('id', $opdId)->get()->getRowArray();
    }

}