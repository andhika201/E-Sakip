<?php

namespace App\Libraries\Pegawai;

use Config\Database;

/**
 * Sinkronisasi master + pegawai dari SIMPEG (Presensi Sync API) ke tabel lokal.
 * Spec API: api_dokumentasi_presensi.md
 *
 * Bisa dijalankan per-entitas (syncOpd/syncPangkat/syncJabatan/syncPegawai) atau
 * sekaligus (apply). Disarankan urut: OPD -> Pangkat -> Jabatan -> Pegawai,
 * karena Pegawai menautkan FK ke master yang sudah ada (via simpeg_id).
 *
 * Idempoten: pakai kolom `simpeg_id` (opd/pangkat/jabatan) + NIP (pegawai); pada
 * sync pertama master lama tanpa simpeg_id "diadopsi" via pencocokan nama.
 */
class PegawaiSyncService
{
    /** @var \CodeIgniter\Database\BaseConnection */
    protected $db;
    protected SimpegClient $client;

    public function __construct(?SimpegClient $client = null)
    {
        $this->db     = Database::connect();
        $this->client = $client ?? new SimpegClient();
    }

    public function isConfigured(): bool
    {
        return $this->client->isConfigured();
    }

    /* ===================== PREVIEW ===================== */

    /**
     * Pratinjau ringan: hitung master baru + total pegawai + sampel beberapa baris.
     *
     * @return array{summary: array<string,int>, rows: array<int,array<string,mixed>>}
     */
    public function preview(): array
    {
        $opd     = $this->client->opd();
        $pangkat = $this->client->pangkat();
        $jabatan = $this->client->jabatan();

        [$opdBySimpeg, $opdByName]         = $this->localMaps('opd', 'nama_opd');
        [$pangkatBySimpeg, $pangkatByName] = $this->localMaps('pangkat', 'nama_pangkat');
        [$jabatanBySimpeg, $jabatanByName] = $this->localMaps('jabatan', 'nama_jabatan');

        $opdBaru = 0;
        foreach ($opd as $o) {
            if (!$this->resolveLocalId((string) ($o['id_opd'] ?? ''), $o['nama_opd'] ?? '', $opdBySimpeg, $opdByName)) {
                $opdBaru++;
            }
        }
        $pangkatBaru = 0;
        foreach ($pangkat as $p) {
            $nama = $p['nama_pangkat'] ?: ($p['golongan'] ?? '');
            if (!$this->resolveLocalId((string) ($p['id_pangkat'] ?? ''), $nama, $pangkatBySimpeg, $pangkatByName)) {
                $pangkatBaru++;
            }
        }
        $jabatanBaru = 0;
        foreach ($jabatan as $j) {
            if (!$this->resolveLocalId((string) ($j['id_jabatan'] ?? ''), $j['nama_jabatan'] ?? '', $jabatanBySimpeg, $jabatanByName)) {
                $jabatanBaru++;
            }
        }

        $pegRemote = $this->client->pegawaiTotal();
        $pegLokal  = (int) $this->db->table('pegawai')->countAllResults();

        $opdNameByRid = [];
        foreach ($opd as $o) { $opdNameByRid[(string) ($o['id_opd'] ?? '')] = $o['nama_opd'] ?? '-'; }

        $sample = $this->client->pegawaiPage(1, 25);
        $rows   = [];
        foreach ($sample['data'] as $p) {
            $rows[] = [
                'nip'     => $p['nip_pegawai'] ?? '',
                'nama'    => $p['nama_pegawai'] ?? '',
                'opd'     => $opdNameByRid[(string) ($p['id_opd'] ?? '')] ?? '-',
                'pangkat' => $p['nama_pangkat'] ?? '-',
                'jabatan' => $p['nama_jabatan'] ?? '-',
            ];
        }

        return [
            'summary' => [
                'opd_remote'     => count($opd),
                'opd_baru'       => $opdBaru,
                'pangkat_remote' => count($pangkat),
                'pangkat_baru'   => $pangkatBaru,
                'jabatan_remote' => count($jabatan),
                'jabatan_baru'   => $jabatanBaru,
                'pegawai_remote' => $pegRemote,
                'pegawai_lokal'  => $pegLokal,
            ],
            'rows' => $rows,
        ];
    }

    /* ===================== SYNC PER-ENTITAS ===================== */

    /** Sinkron master OPD. @return array{opd_baru:int,opd_update:int} */
    public function syncOpd(): array
    {
        @set_time_limit(0);
        $now  = date('Y-m-d H:i:s');
        $opd  = $this->client->opd();
        [$bySimpeg, $byName] = $this->localMaps('opd', 'nama_opd');
        $baru = 0; $update = 0;

        $this->db->transStart();
        foreach ($opd as $o) {
            $rid  = (string) ($o['id_opd'] ?? '');
            $nama = trim((string) ($o['nama_opd'] ?? ''));
            if ($rid === '' || $nama === '') { continue; }
            $local = $this->resolveLocalId($rid, $nama, $bySimpeg, $byName);
            if ($local) {
                $this->db->table('opd')->where('id', $local)->update(['simpeg_id' => $rid, 'nama_opd' => $nama, 'updated_at' => $now]);
                $update++;
            } else {
                $this->db->table('opd')->insert(['simpeg_id' => $rid, 'nama_opd' => $nama, 'created_at' => $now, 'updated_at' => $now]);
                $local = (int) $this->db->insertID();
                $baru++;
            }
            $bySimpeg[$rid] = $local;
            $byName[$this->key($nama)] = $local;
        }
        $this->db->transComplete();

        return ['opd_baru' => $baru, 'opd_update' => $update];
    }

    /** Sinkron master Pangkat. @return array{pangkat_baru:int,pangkat_update:int} */
    public function syncPangkat(): array
    {
        @set_time_limit(0);
        $now     = date('Y-m-d H:i:s');
        $pangkat = $this->client->pangkat();
        [$bySimpeg, $byName] = $this->localMaps('pangkat', 'nama_pangkat');
        $baru = 0; $update = 0;

        $this->db->transStart();
        foreach ($pangkat as $p) {
            $rid  = (string) ($p['id_pangkat'] ?? '');
            $nama = trim((string) ($p['nama_pangkat'] ?: ($p['golongan'] ?? '')));
            $gol  = trim((string) ($p['golongan'] ?? ''));
            if ($rid === '' || $nama === '') { continue; }
            $local = $this->resolveLocalId($rid, $nama, $bySimpeg, $byName);
            if ($local) {
                $this->db->table('pangkat')->where('id', $local)->update(['simpeg_id' => $rid, 'nama_pangkat' => $nama, 'golongan' => $gol, 'updated_at' => $now]);
                $update++;
            } else {
                $this->db->table('pangkat')->insert(['simpeg_id' => $rid, 'nama_pangkat' => $nama, 'golongan' => $gol, 'created_at' => $now, 'updated_at' => $now]);
                $local = (int) $this->db->insertID();
                $baru++;
            }
            $bySimpeg[$rid] = $local;
            $byName[$this->key($nama)] = $local;
        }
        $this->db->transComplete();

        return ['pangkat_baru' => $baru, 'pangkat_update' => $update];
    }

    /** Sinkron master Jabatan. @return array{jabatan_baru:int,jabatan_update:int} */
    public function syncJabatan(): array
    {
        @set_time_limit(0);
        $now     = date('Y-m-d H:i:s');
        $jabatan = $this->client->jabatan();
        [$bySimpeg, $byName] = $this->localMaps('jabatan', 'nama_jabatan');
        $baru = 0; $update = 0;

        $this->db->transStart();
        foreach ($jabatan as $j) {
            $rid  = (string) ($j['id_jabatan'] ?? '');
            $nama = trim((string) ($j['nama_jabatan'] ?? ''));
            if ($rid === '' || $nama === '') { continue; }
            $namaEselon = trim((string) ($j['nama_eselon'] ?? ''));
            $eselon = $namaEselon !== '' ? $namaEselon : null;
            $payload = ['simpeg_id' => $rid, 'nama_jabatan' => $nama, 'eselon' => $eselon, 'updated_at' => $now];
            $local  = $this->resolveLocalId($rid, $nama, $bySimpeg, $byName);
            if ($local) {
                $this->db->table('jabatan')->where('id', $local)->update($payload);
                $update++;
            } else {
                $this->db->table('jabatan')->insert($payload + ['created_at' => $now]);
                $local = (int) $this->db->insertID();
                $baru++;
            }
            $bySimpeg[$rid] = $local;
            $byName[$this->key($nama)] = $local;
        }
        $this->db->transComplete();

        return ['jabatan_baru' => $baru, 'jabatan_update' => $update];
    }

    /**
     * Sinkron Pegawai (paginated). Menautkan FK ke master lokal via simpeg_id.
     * Jalankan SETELAH OPD/Pangkat/Jabatan agar tautan lengkap.
     *
     * @return array{pegawai_baru:int,pegawai_update:int,pegawai_dilewati:int}
     */
    public function syncPegawai(?string $updatedSince = null): array
    {
        @set_time_limit(0);
        @ini_set('memory_limit', '512M');
        $now = date('Y-m-d H:i:s');

        $opdBySimpeg     = $this->localMaps('opd', 'nama_opd')[0];
        $pangkatBySimpeg = $this->localMaps('pangkat', 'nama_pangkat')[0];
        $jabatanBySimpeg = $this->localMaps('jabatan', 'nama_jabatan')[0];
        $pegawaiByNip    = $this->pegawaiIdByNip();

        $rows = $this->client->allPegawai($updatedSince);
        $baru = 0; $update = 0; $skip = 0;

        $this->db->transStart();
        foreach ($rows as $p) {
            $nip = $this->normalizeNip($p['nip_pegawai'] ?? '');
            if ($nip === '') { $skip++; continue; }

            $opdLocal     = $opdBySimpeg[(string) ($p['id_opd'] ?? '')] ?? null;
            $jabatanLocal = $jabatanBySimpeg[(string) ($p['id_jabatan'] ?? '')] ?? null;
            $pangkatLocal = $pangkatBySimpeg[(string) ($p['id_pangkat'] ?? '')] ?? null;

            $existing = $pegawaiByNip[$nip] ?? null;
            if ($existing) {
                $set = ['updated_at' => $now];
                if (!empty($p['nama_pegawai'])) { $set['nama_pegawai'] = trim($p['nama_pegawai']); }
                if ($opdLocal !== null)     { $set['opd_id'] = $opdLocal; }
                if ($jabatanLocal !== null) { $set['jabatan_id'] = $jabatanLocal; }
                if ($pangkatLocal !== null) { $set['pangkat_id'] = $pangkatLocal; }
                if (!empty($p['status_pegawai'])) { $set['status'] = $p['status_pegawai']; }
                if (!empty($p['url_foto_pegawai'])) { $set['url_foto_pegawai'] = $p['url_foto_pegawai']; }
                $this->db->table('pegawai')->where('id', $existing)->update($set);
                $update++;
            } else {
                $this->db->table('pegawai')->insert([
                    'nama_pegawai'     => trim((string) ($p['nama_pegawai'] ?? $nip)),
                    'nip_pegawai'      => $p['nip_pegawai'] ?? $nip,
                    'opd_id'           => (int) ($opdLocal ?? 0),
                    'jabatan_id'       => (int) ($jabatanLocal ?? 0),
                    'pangkat_id'       => (int) ($pangkatLocal ?? 0),
                    'password'         => password_hash($p['nip_pegawai'] ?? $nip, PASSWORD_DEFAULT),
                    'level'            => $p['level'] ?? 'USER',
                    'status'           => $p['status_pegawai'] ?? null,
                    'url_foto_pegawai' => $p['url_foto_pegawai'] ?? null,
                    'tukin'            => 0,
                    'first_time'       => 1,
                    'created_at'       => $now,
                    'updated_at'       => $now,
                ]);
                $pegawaiByNip[$nip] = (int) $this->db->insertID();
                $baru++;
            }
        }
        $this->db->transComplete();

        return ['pegawai_baru' => $baru, 'pegawai_update' => $update, 'pegawai_dilewati' => $skip];
    }

    /**
     * Sinkron penuh berurutan: OPD -> Pangkat -> Jabatan -> Pegawai.
     *
     * @return array<string,int>
     */
    public function apply(?string $updatedSince = null): array
    {
        return array_merge(
            $this->syncOpd(),
            $this->syncPangkat(),
            $this->syncJabatan(),
            $this->syncPegawai($updatedSince)
        );
    }

    /** Dispatch per nama entitas: opd|pangkat|jabatan|pegawai|all. */
    public function syncEntity(string $entity, ?string $updatedSince = null): array
    {
        switch ($entity) {
            case 'opd':     return $this->syncOpd();
            case 'pangkat': return $this->syncPangkat();
            case 'jabatan': return $this->syncJabatan();
            case 'pegawai': return $this->syncPegawai($updatedSince);
            case 'all':     return $this->apply($updatedSince);
            default:        throw new \InvalidArgumentException('Entitas sync tidak dikenal: ' . $entity);
        }
    }

    /* ===================== HELPERS ===================== */

    /**
     * Bangun dua peta lokal: by simpeg_id dan by nama(normalisasi) -> id.
     *
     * @return array{0: array<string,int>, 1: array<string,int>}
     */
    protected function localMaps(string $table, string $namaCol): array
    {
        $rows = $this->db->table($table)->select("id, simpeg_id, {$namaCol}")->get()->getResultArray();
        $bySimpeg = [];
        $byName   = [];
        foreach ($rows as $r) {
            if (!empty($r['simpeg_id'])) { $bySimpeg[(string) $r['simpeg_id']] = (int) $r['id']; }
            $byName[$this->key($r[$namaCol] ?? '')] = (int) $r['id'];
        }
        return [$bySimpeg, $byName];
    }

    /** Cari id lokal: utamakan simpeg_id, fallback adopsi via nama. */
    protected function resolveLocalId(string $rid, string $nama, array $bySimpeg, array $byName): ?int
    {
        if ($rid !== '' && isset($bySimpeg[$rid])) { return $bySimpeg[$rid]; }
        $k = $this->key($nama);
        if ($k !== '' && isset($byName[$k])) { return $byName[$k]; }
        return null;
    }

    /** @return array<string,int> nip => id */
    protected function pegawaiIdByNip(): array
    {
        $rows = $this->db->table('pegawai')->select('id, nip_pegawai')->get()->getResultArray();
        $map  = [];
        foreach ($rows as $r) { $map[$this->normalizeNip($r['nip_pegawai'])] = (int) $r['id']; }
        return $map;
    }

    protected function key(?string $s): string
    {
        return strtolower(trim(preg_replace('/\s+/', ' ', (string) $s)));
    }

    protected function normalizeNip(?string $nip): string
    {
        return preg_replace('/\D+/', '', (string) $nip);
    }
}
