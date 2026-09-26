<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Referensi modul Kinerja Prioritas (IKP), dari Buku Saku Program Unggulan
 * Bupati Pringsewu (dibawa dari prototipe Prioritas):
 *   - 9 Program Unggulan (nama, warna, ikon)
 *   - 10 Sasaran Pembangunan
 *   - 319 baris Buku Saku (katalog output prioritas se-kabupaten, untuk autolengkap)
 *
 *   php spark db:seed IkpReferensiSeeder
 *
 * Data: app/Database/Seeds/data/ikp_referensi.json & ikp_buku_saku.json
 * (hanya teks kelembagaan — tidak ada nama/NIP orang).
 *
 * IDEMPOTEN: Program Unggulan di-upsert per slug; Sasaran per nama; Buku Saku
 * hanya diisi bila tabelnya masih kosong (id-nya dirujuk ikp.buku_saku_id,
 * jadi tidak boleh dihapus-buat-ulang).
 *
 * `misi_nomor` (1..5) dipetakan ke rpjmd_misi.id menurut urutan id misi periode
 * RPJMD berjalan — bukan angka tetap, karena id misi berbeda antar instalasi.
 */
class IkpReferensiSeeder extends Seeder
{
    public function run()
    {
        $db  = \Config\Database::connect();
        $now = date('Y-m-d H:i:s');
        $dir = __DIR__ . '/data/';
        foreach (['ikp_program_unggulan', 'ikp_sasaran_pembangunan', 'ikp_buku_saku'] as $t) {
            if (! $db->tableExists($t)) {
                echo "Tabel $t belum ada — jalankan migrasi dulu.\n";
                return;
            }
        }
        $ref = json_decode((string) file_get_contents($dir . 'ikp_referensi.json'), true);
        $bs  = json_decode((string) file_get_contents($dir . 'ikp_buku_saku.json'), true);

        // 1) Program Unggulan
        $puId = [];
        foreach ($ref['program_unggulan'] as $pu) {
            $ada = $db->table('ikp_program_unggulan')->where('slug', $pu['slug'])->get()->getRowArray();
            $baris = ['nama' => $pu['nama'], 'warna' => $pu['warna'], 'ikon' => $pu['ikon'], 'urutan' => (int) $pu['urutan'], 'updated_at' => $now];
            if ($ada) {
                $db->table('ikp_program_unggulan')->where('id', $ada['id'])->update($baris);
                $puId[$pu['slug']] = (int) $ada['id'];
            } else {
                $db->table('ikp_program_unggulan')->insert($baris + ['slug' => $pu['slug'], 'created_at' => $now]);
                $puId[$pu['slug']] = (int) $db->insertID();
            }
        }

        // 2) Sasaran Pembangunan
        $spId = [];
        foreach ($ref['sasaran_pembangunan'] as $sp) {
            $ada = $db->table('ikp_sasaran_pembangunan')->where('nama', $sp['nama'])->get()->getRowArray();
            if ($ada) {
                $spId[(int) $sp['urutan']] = (int) $ada['id'];
            } else {
                $db->table('ikp_sasaran_pembangunan')->insert(['nama' => $sp['nama'], 'urutan' => (int) $sp['urutan'], 'created_at' => $now, 'updated_at' => $now]);
                $spId[(int) $sp['urutan']] = (int) $db->insertID();
            }
        }

        // 3) Misi nomor -> rpjmd_misi.id (periode terbaru)
        $misi = $db->table('rpjmd_misi')->select('id')->orderBy('tahun_mulai', 'DESC')->orderBy('id', 'ASC')->get()->getResultArray();
        $misiId = [];
        foreach (array_slice($misi, 0, 5) as $i => $m) {
            $misiId[$i + 1] = (int) $m['id'];
        }

        // 4) Buku Saku (sekali saja)
        if ($db->table('ikp_buku_saku')->countAllResults() === 0) {
            $batch = [];
            foreach ($bs as $b) {
                $batch[] = [
                    'rpjmd_misi_id'            => $misiId[(int) ($b['misi_nomor'] ?? 0)] ?? null,
                    'program_unggulan_id'      => $puId[$b['program_unggulan_slug'] ?? ''] ?? null,
                    'program_unggulan_teks'    => $b['program_unggulan_teks'] ?? null,
                    'sasaran_pembangunan_id'   => $spId[(int) ($b['sasaran_pembangunan_urutan'] ?? 0)] ?? null,
                    'sasaran_pembangunan_teks' => $b['sasaran_pembangunan_teks'] ?? null,
                    'outcome'                  => $b['outcome'] ?? null,
                    'indikator'                => $b['indikator'] ?? null,
                    'program_opd'              => $b['program_opd'] ?? null,
                    'output_prioritas'         => $b['output_prioritas'] ?? null,
                    'satuan'                   => $b['satuan'] ?? null,
                    'target_5_tahun'           => $b['target_5_tahun'] ?? null,
                    'bidang_urusan'            => $b['bidang_urusan'] ?? null,
                    'created_at'               => $now,
                    'updated_at'               => $now,
                ];
            }
            foreach (array_chunk($batch, 100) as $potong) {
                $db->table('ikp_buku_saku')->insertBatch($potong);
            }
        }

        echo 'Referensi IKP siap: ' . count($puId) . ' PU, ' . count($spId) . ' sasaran, '
            . $db->table('ikp_buku_saku')->countAllResults() . " buku saku.\n";
    }
}
