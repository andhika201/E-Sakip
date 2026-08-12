<?php

namespace App\Services;

/**
 * Menulis hasil Lampiran8Parser ke tabel produksi
 * (program_pk / kegiatan_pk / sub_kegiatan_pk).
 *
 * Aturan yang WAJIB dipertahankan (sudah lolos verifikasi sebelumnya):
 *   - Program  dicari dengan opd_id + tahun + jenis_anggaran + kode_program
 *   - Kegiatan dicari dengan program_id  + kode_kegiatan + tahun + jenis
 *   - Sub      dicari dengan kegiatan_id + kode_sub      + tahun + jenis
 *   - Anggaran selalu dari kolom K baris entitas itu sendiri; null (sel
 *     kosong) tidak menimpa nilai lama menjadi 0
 *   - Induk yang tidak punya barisnya sendiri di Excel dibuat otomatis lalu
 *     anggarannya dihitung dari total anak di bawahnya
 *   - Idempoten: import ulang file yang sama tidak menambah baris
 *
 * Pemanggil bertanggung jawab membuka transaksi.
 */
class ProgramPkWriter
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    /** @return array<string, int> ringkasan kosong */
    public static function statKosong(): array
    {
        return [
            'program_baru' => 0, 'program_update' => 0, 'program_auto' => 0,
            'kegiatan_baru' => 0, 'kegiatan_update' => 0, 'kegiatan_auto' => 0,
            'sub_baru' => 0, 'sub_update' => 0, 'lewat' => 0,
        ];
    }

    /**
     * Tulis satu unit ke tabel produksi.
     *
     * @param array<string, mixed> $unit hasil Lampiran8Parser
     * @param array<string, int>   $stat akumulator ringkasan (by reference)
     */
    public function tulisUnit(array $unit, int $opdId, int $tahun, string $jenisAnggaran, array &$stat): void
    {
        $tbProgram  = $this->db->table('program_pk');
        $tbKegiatan = $this->db->table('kegiatan_pk');
        $tbSub      = $this->db->table('sub_kegiatan_pk');
        $now        = date('Y-m-d H:i:s');

        $programIdByKode  = [];
        $kegiatanIdByKode = [];
        // Entitas yang tidak punya barisnya sendiri -> anggarannya dijumlah
        // dari anaknya setelah semua baris ditulis.
        $programTanpaBaris  = [];
        $kegiatanTanpaBaris = [];

        foreach ($unit['entities'] as $e) {
            $anggaran = $e['anggaran'];

            if ($e['level'] === 'program') {
                $data = [
                    'opd_id'           => $opdId,
                    'kode_program'     => $e['kode_program'],
                    'program_kegiatan' => $e['uraian'],
                    'tahun_anggaran'   => $tahun,
                    'jenis_anggaran'   => $jenisAnggaran,
                    'updated_at'       => $now,
                ];
                if ($anggaran !== null) {
                    $data['anggaran'] = $anggaran;
                }

                $id = $this->cariProgram($tbProgram, $e['kode_program'], $opdId, $tahun, $jenisAnggaran);
                if ($id !== null) {
                    $tbProgram->where('id', $id)->update($data);
                    $stat['program_update']++;
                } else {
                    $data['created_at'] = $now;
                    if (!isset($data['anggaran'])) {
                        $data['anggaran'] = 0;
                    }
                    $tbProgram->insert($data);
                    $id = (int) $this->db->insertID();
                    $e['punya_baris'] ? $stat['program_baru']++ : $stat['program_auto']++;
                }

                $programIdByKode[$e['kode_program']] = $id;
                if (!$e['punya_baris']) {
                    $programTanpaBaris[$e['kode_program']] = $id;
                }
                continue;
            }

            // Induk program WAJIB sudah ada — parser selalu memancarkan program
            // lebih dulu, termasuk yang dibuat otomatis.
            $programId = $programIdByKode[$e['kode_program']]
                ?? $this->cariProgram($tbProgram, $e['kode_program'], $opdId, $tahun, $jenisAnggaran);

            if ($programId === null) {
                $stat['lewat']++;
                log_message('warning', 'Import Program PK: baris ' . ($e['baris_excel'] ?? '?')
                    . ' dilewati, program ' . $e['kode_program'] . ' tidak ditemukan');
                continue;
            }
            $programIdByKode[$e['kode_program']] = $programId;

            if ($e['level'] === 'kegiatan') {
                $data = [
                    'program_id'     => $programId,
                    'kode_kegiatan'  => $e['kode_kegiatan'],
                    'kegiatan'       => $e['uraian'],
                    'tahun_anggaran' => $tahun,
                    'jenis_anggaran' => $jenisAnggaran,
                    'updated_at'     => $now,
                ];
                if ($anggaran !== null) {
                    $data['anggaran'] = $anggaran;
                }

                $id = $this->cariKegiatan($tbKegiatan, $e['kode_kegiatan'], $programId, $tahun, $jenisAnggaran);
                if ($id !== null) {
                    $tbKegiatan->where('id', $id)->update($data);
                    $stat['kegiatan_update']++;
                } else {
                    $data['created_at'] = $now;
                    if (!isset($data['anggaran'])) {
                        $data['anggaran'] = 0;
                    }
                    $tbKegiatan->insert($data);
                    $id = (int) $this->db->insertID();
                    $e['punya_baris'] ? $stat['kegiatan_baru']++ : $stat['kegiatan_auto']++;
                }

                $kegiatanIdByKode[$e['kode_kegiatan']] = $id;
                if (!$e['punya_baris']) {
                    $kegiatanTanpaBaris[$e['kode_kegiatan']] = $id;
                }
                continue;
            }

            // SUB KEGIATAN
            $kegiatanId = $kegiatanIdByKode[$e['kode_kegiatan']]
                ?? $this->cariKegiatan($tbKegiatan, $e['kode_kegiatan'], $programId, $tahun, $jenisAnggaran);

            if ($kegiatanId === null) {
                $stat['lewat']++;
                log_message('warning', 'Import Program PK: baris ' . ($e['baris_excel'] ?? '?')
                    . ' dilewati, kegiatan ' . $e['kode_kegiatan'] . ' tidak ditemukan');
                continue;
            }
            $kegiatanIdByKode[$e['kode_kegiatan']] = $kegiatanId;

            $data = [
                'kegiatan_id'       => $kegiatanId,
                'kode_sub_kegiatan' => $e['kode_sub'],
                'sub_kegiatan'      => $e['uraian'],
                'tahun_anggaran'    => $tahun,
                'jenis_anggaran'    => $jenisAnggaran,
                'updated_at'        => $now,
            ];
            if ($anggaran !== null) {
                $data['anggaran'] = $anggaran;
            }

            $subId = $this->cariSub($tbSub, $e['kode_sub'], $kegiatanId, $tahun, $jenisAnggaran);
            if ($subId !== null) {
                $tbSub->where('id', $subId)->update($data);
                $stat['sub_update']++;
            } else {
                $data['created_at'] = $now;
                if (!isset($data['anggaran'])) {
                    $data['anggaran'] = 0;
                }
                $tbSub->insert($data);
                $stat['sub_baru']++;
            }
        }

        // Kegiatan dulu (totalnya menjadi sumber total program), lalu program.
        foreach ($kegiatanTanpaBaris as $id) {
            $this->db->table('kegiatan_pk')->where('id', $id)->update([
                'anggaran'   => $this->totalAnak('sub_kegiatan_pk', 'kegiatan_id', $id),
                'updated_at' => $now,
            ]);
        }
        foreach ($programTanpaBaris as $id) {
            $this->db->table('program_pk')->where('id', $id)->update([
                'anggaran'   => $this->totalAnak('kegiatan_pk', 'program_id', $id),
                'updated_at' => $now,
            ]);
        }
    }

    private function cariProgram($tb, string $kode, int $opdId, int $tahun, string $jenis): ?int
    {
        $row = $tb->where('opd_id', $opdId)
            ->where('tahun_anggaran', $tahun)
            ->where('jenis_anggaran', $jenis)
            ->where('kode_program', $kode)
            ->get()->getRow();

        return $row ? (int) $row->id : null;
    }

    private function cariKegiatan($tb, string $kode, int $programId, int $tahun, string $jenis): ?int
    {
        $row = $tb->where('program_id', $programId)
            ->where('kode_kegiatan', $kode)
            ->where('tahun_anggaran', $tahun)
            ->where('jenis_anggaran', $jenis)
            ->get()->getRow();

        return $row ? (int) $row->id : null;
    }

    private function cariSub($tb, string $kode, int $kegiatanId, int $tahun, string $jenis): ?int
    {
        $row = $tb->where('kegiatan_id', $kegiatanId)
            ->where('kode_sub_kegiatan', $kode)
            ->where('tahun_anggaran', $tahun)
            ->where('jenis_anggaran', $jenis)
            ->get()->getRow();

        return $row ? (int) $row->id : null;
    }

    private function totalAnak(string $tabel, string $kolomInduk, int $indukId): int
    {
        $row = $this->db->table($tabel)
            ->selectSum('anggaran', 'total')
            ->where($kolomInduk, $indukId)
            ->get()->getRow();

        return (int) ($row->total ?? 0);
    }
}
