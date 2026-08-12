<?php

namespace App\Controllers\Concerns;

use App\Services\Lampiran8Parser;
use App\Services\OpdResolver;
use App\Services\ProgramPkWriter;

/**
 * Cakupan import "Seluruh OPD": staging batch, resolusi OPD per unit, dan
 * pemetaan manual tanpa unggah ulang Excel.
 *
 * Alur:
 *   1. File di-parse menjadi daftar unit (Lampiran8Parser).
 *   2. Tiap unit dicarikan OPD-nya (OpdResolver) dengan urutan
 *      exact -> alias -> parent_rule -> (fuzzy hanya SARAN).
 *   3. Seluruh hasil parsing disimpan ke staging (import_batch*).
 *   4. Unit yang OPD-nya sudah pasti langsung difinalisasi ke tabel produksi.
 *   5. Unit yang belum pasti berstatus `pending_mapping` dan MENUNGGU di
 *      staging — tidak satu pun barisnya masuk ke tabel produksi.
 *   6. Pengguna memetakan unit pending di halaman mapping, lalu unit tersebut
 *      difinalisasi. Excel tidak perlu diunggah ulang.
 */
trait ImportMultiOpdTrait
{
    /** Tabel staging tersedia? Instalasi lama boleh belum menjalankan migrasinya. */
    protected function stagingSiap($db): bool
    {
        return $db->tableExists('import_batch')
            && $db->tableExists('import_batch_unit')
            && $db->tableExists('import_batch_row');
    }

    /**
     * Jalankan import cakupan "Seluruh OPD".
     *
     * @param array<int, array<string, mixed>> $units hasil Lampiran8Parser
     *
     * @return array<string, mixed> ringkasan untuk pesan/halaman hasil
     */
    protected function importSeluruhOpd(
        $db,
        array $units,
        int $tahun,
        string $jenisAnggaran,
        string $namaFile,
        bool $dryRun
    ): array {
        $resolver = new OpdResolver($db);
        $writer   = new ProgramPkWriter($db);
        $userId   = (int) session()->get('user_id') ?: null;

        $stat = ProgramPkWriter::statKosong();
        $ring = [
            'unit_total' => count($units),
            'exact' => 0, 'alias' => 0, 'parent_rule' => 0, 'pending' => 0,
            'program' => 0, 'kegiatan' => 0, 'sub' => 0, 'total_anggaran' => 0.0,
            'batch_id' => 0, 'pending_units' => [],
        ];

        // Batch dibuat lebih dulu supaya unit pending punya tempat menunggu.
        $batchId = 0;
        if (!$dryRun && $this->stagingSiap($db)) {
            $db->table('import_batch')->insert([
                'tahun'          => $tahun,
                'jenis_anggaran' => $jenisAnggaran,
                'mode_import'    => 'seluruh',
                'nama_file'      => mb_substr($namaFile, 0, 255),
                'status'         => 'pending_mapping',
                'jumlah_unit'    => count($units),
                'jumlah_pending' => 0,
                'created_by'     => $userId,
                'created_at'     => date('Y-m-d H:i:s'),
                'updated_at'     => date('Y-m-d H:i:s'),
            ]);
            $batchId = (int) $db->insertID();
        }
        $ring['batch_id'] = $batchId;

        foreach ($units as $urutan => $unit) {
            $res = $resolver->resolve($unit['nama_unit'], $unit['kode_unit']);

            $ring['program']        += $unit['jumlah_program'];
            $ring['kegiatan']       += $unit['jumlah_kegiatan'];
            $ring['sub']            += $unit['jumlah_sub'];
            $ring['total_anggaran'] += (float) $unit['total_anggaran'];

            $terpetakan = ($res['status'] === 'resolved' && !empty($res['opd_id']));
            if ($terpetakan) {
                $ring[$res['method']] = ($ring[$res['method']] ?? 0) + 1;
            } else {
                $ring['pending']++;
                $ring['pending_units'][] = [
                    'kode' => $unit['kode_unit'],
                    'nama' => $unit['nama_unit'],
                    'alasan' => $res['alasan'],
                ];
            }

            // Simulasi: cukup dihitung, tidak menulis apa pun.
            if ($dryRun) {
                continue;
            }

            $unitId = 0;
            if ($batchId > 0) {
                $unitId = $this->simpanUnitStaging($db, $batchId, $unit, $res, $urutan);
            }

            if ($terpetakan) {
                $writer->tulisUnit($unit, (int) $res['opd_id'], $tahun, $jenisAnggaran, $stat);
                if ($unitId > 0) {
                    $db->table('import_batch_unit')->where('id', $unitId)
                        ->update(['mapping_status' => 'imported', 'updated_at' => date('Y-m-d H:i:s')]);
                }
            }
        }

        if (!$dryRun && $batchId > 0) {
            $db->table('import_batch')->where('id', $batchId)->update([
                'jumlah_pending' => $ring['pending'],
                'status'         => $ring['pending'] > 0 ? 'pending_mapping' : 'selesai',
                'updated_at'     => date('Y-m-d H:i:s'),
            ]);
        }

        $ring['stat'] = $stat;

        return $ring;
    }

    /**
     * Simpan satu unit + seluruh baris hasil parsing ke staging.
     *
     * @return int id import_batch_unit
     */
    protected function simpanUnitStaging($db, int $batchId, array $unit, array $res, int $urutan): int
    {
        $terpetakan = ($res['status'] === 'resolved' && !empty($res['opd_id']));

        $db->table('import_batch_unit')->insert([
            'batch_id'        => $batchId,
            'kode_unit_excel' => mb_substr((string) $unit['kode_unit'], 0, 50),
            'nama_unit_excel' => mb_substr((string) $unit['nama_unit'], 0, 255),
            'opd_id'          => $terpetakan ? (int) $res['opd_id'] : null,
            'mapping_status'  => $terpetakan ? 'resolved' : 'pending_mapping',
            'mapping_method'  => $res['method'],
            'saran_opd_id'    => $res['saran_opd_id'],
            'saran_skor'      => $res['saran_skor'],
            'jumlah_program'  => (int) $unit['jumlah_program'],
            'jumlah_kegiatan' => (int) $unit['jumlah_kegiatan'],
            'jumlah_sub'      => (int) $unit['jumlah_sub'],
            'total_anggaran'  => (float) $unit['total_anggaran'],
            'urutan'          => $urutan,
            'created_at'      => date('Y-m-d H:i:s'),
            'updated_at'      => date('Y-m-d H:i:s'),
        ]);
        $unitId = (int) $db->insertID();

        $batch = [];
        foreach ($unit['entities'] as $i => $e) {
            $batch[] = [
                'batch_unit_id' => $unitId,
                'level'         => $e['level'],
                'kode_program'  => $e['kode_program'],
                'kode_kegiatan' => $e['kode_kegiatan'],
                'kode_sub'      => $e['kode_sub'],
                'nomenklatur'   => $e['nomenklatur'],
                'uraian'        => $e['uraian'],
                'anggaran'      => $e['anggaran'],
                'punya_baris'   => $e['punya_baris'] ? 1 : 0,
                'baris_excel'   => $e['baris_excel'],
                'urutan'        => $i,
            ];
            // insertBatch bertahap supaya paket query tidak terlalu besar.
            if (count($batch) >= 500) {
                $db->table('import_batch_row')->insertBatch($batch);
                $batch = [];
            }
        }
        if ($batch) {
            $db->table('import_batch_row')->insertBatch($batch);
        }

        return $unitId;
    }

    /**
     * Susun ulang satu unit staging menjadi bentuk yang dimengerti
     * ProgramPkWriter — dipakai saat finalisasi mapping manual.
     *
     * @return array<string, mixed>|null
     */
    protected function unitDariStaging($db, int $unitId): ?array
    {
        $unit = $db->table('import_batch_unit')->where('id', $unitId)->get()->getRowArray();
        if (!$unit) {
            return null;
        }

        $rows = $db->table('import_batch_row')
            ->where('batch_unit_id', $unitId)
            ->orderBy('urutan', 'ASC')
            ->get()->getResultArray();

        $entities = [];
        foreach ($rows as $r) {
            $entities[] = [
                'level'         => $r['level'],
                'kode_program'  => $r['kode_program'],
                'kode_kegiatan' => $r['kode_kegiatan'],
                'kode_sub'      => $r['kode_sub'],
                'nomenklatur'   => $r['nomenklatur'],
                'uraian'        => $r['uraian'],
                // NULL harus tetap NULL (sel kolom K kosong), bukan 0.
                'anggaran'      => $r['anggaran'] === null ? null : (int) round((float) $r['anggaran']),
                'punya_baris'   => (int) $r['punya_baris'] === 1,
                'baris_excel'   => $r['baris_excel'] === null ? null : (int) $r['baris_excel'],
            ];
        }

        return [
            'id'              => (int) $unit['id'],
            'batch_id'        => (int) $unit['batch_id'],
            'kode_unit'       => (string) $unit['kode_unit_excel'],
            'nama_unit'       => (string) $unit['nama_unit_excel'],
            'mapping_status'  => (string) $unit['mapping_status'],
            'mapping_method'  => $unit['mapping_method'],
            'saran_opd_id'    => $unit['saran_opd_id'] === null ? null : (int) $unit['saran_opd_id'],
            'saran_skor'      => $unit['saran_skor'],
            'jumlah_program'  => (int) $unit['jumlah_program'],
            'jumlah_kegiatan' => (int) $unit['jumlah_kegiatan'],
            'jumlah_sub'      => (int) $unit['jumlah_sub'],
            'total_anggaran'  => (float) $unit['total_anggaran'],
            'entities'        => $entities,
        ];
    }

    /**
     * Pratinjau hierarki unit staging: Program -> Kegiatan -> Sub beserta
     * rekonsiliasi anggaran tiap tingkat.
     *
     * @return array<string, mixed>
     */
    protected function pratinjauUnit(array $unit): array
    {
        $program = [];

        foreach ($unit['entities'] as $e) {
            $kp = $e['kode_program'];
            if (!isset($program[$kp])) {
                $program[$kp] = [
                    'kode' => $kp, 'uraian' => '', 'anggaran' => null,
                    'punya_baris' => true, 'kegiatan' => [],
                ];
            }

            if ($e['level'] === 'program') {
                $program[$kp]['uraian']      = $e['uraian'];
                $program[$kp]['anggaran']    = $e['anggaran'];
                $program[$kp]['punya_baris'] = $e['punya_baris'];
                continue;
            }

            $kk = $e['kode_kegiatan'];
            if (!isset($program[$kp]['kegiatan'][$kk])) {
                $program[$kp]['kegiatan'][$kk] = [
                    'kode' => $kk, 'uraian' => '', 'anggaran' => null,
                    'punya_baris' => true, 'sub' => [],
                ];
            }

            if ($e['level'] === 'kegiatan') {
                $program[$kp]['kegiatan'][$kk]['uraian']      = $e['uraian'];
                $program[$kp]['kegiatan'][$kk]['anggaran']    = $e['anggaran'];
                $program[$kp]['kegiatan'][$kk]['punya_baris'] = $e['punya_baris'];
                continue;
            }

            $program[$kp]['kegiatan'][$kk]['sub'][] = [
                'kode' => $e['kode_sub'], 'uraian' => $e['uraian'], 'anggaran' => $e['anggaran'],
            ];
        }

        // Rekonsiliasi: Program vs total Kegiatan, Kegiatan vs total Sub.
        $selisihProgram  = 0;
        $selisihKegiatan = 0;
        $totalProgram    = 0.0;

        foreach ($program as $kp => $p) {
            $totalKegiatan = 0.0;
            foreach ($p['kegiatan'] as $kk => $k) {
                $totalSub = 0.0;
                foreach ($k['sub'] as $s) {
                    $totalSub += (float) ($s['anggaran'] ?? 0);
                }
                $program[$kp]['kegiatan'][$kk]['total_sub'] = $totalSub;
                $program[$kp]['kegiatan'][$kk]['selisih']   = $k['sub']
                    ? ((float) ($k['anggaran'] ?? 0) - $totalSub)
                    : 0.0;
                if (abs($program[$kp]['kegiatan'][$kk]['selisih']) >= 1) {
                    $selisihKegiatan++;
                }
                $totalKegiatan += (float) ($k['anggaran'] ?? 0);
            }

            $program[$kp]['total_kegiatan'] = $totalKegiatan;
            $program[$kp]['selisih'] = $p['kegiatan'] ? ((float) ($p['anggaran'] ?? 0) - $totalKegiatan) : 0.0;
            if (abs($program[$kp]['selisih']) >= 1) {
                $selisihProgram++;
            }
            $totalProgram += (float) ($p['anggaran'] ?? 0);
        }

        return [
            'program'          => array_values(array_map(static function ($p) {
                $p['kegiatan'] = array_values($p['kegiatan']);

                return $p;
            }, $program)),
            'total_program'    => $totalProgram,
            'selisih_program'  => $selisihProgram,
            'selisih_kegiatan' => $selisihKegiatan,
        ];
    }
}
