<?php

namespace App\Services;

use CodeIgniter\Database\BaseConnection;

/**
 * Unit anggaran yang DIPAKAI BERSAMA beberapa indikator PK.
 *
 * =====================================================================
 * ATURAN BISNIS YANG DILAYANI
 *
 * Satu Program/Kegiatan/Sub Kegiatan boleh mendukung dua indikator PK atau
 * lebih. Realisasi TW I–IV tetap diinput TERPISAH pada tiap indikator, dan
 * angkanya berarti BAGIAN realisasi yang dipakai untuk mendukung indikator
 * itu — bukan salinan realisasi unit secara keseluruhan.
 *
 * Konsekuensinya dua, dan keduanya mudah tertukar:
 *
 *   * REALISASI dijumlahkan lintas indikator. Program X dengan A=150jt dan
 *     B=75jt memang terserap 225jt. Jangan pernah men-DISTINCT-kannya (§32).
 *   * PAGU dihitung SEKALI. Program X yang mendukung 5 indikator tetap
 *     berpagu satu, bukan lima kali (§33).
 *
 * Dan satu batas keras: jumlah seluruh bagian pada satu unit TIDAK BOLEH
 * melebihi pagu unit itu (§24).
 *
 * =====================================================================
 * LINGKUP SEBUAH "UNIT" — EMPAT BAGIAN, BUKAN SATU
 *
 *   opd_id + tahun PK + ref_level + ref_id
 *
 * `ref_id` saja tidak pernah cukup. Program #10 dan Kegiatan #10 adalah dua
 * benda berbeda yang kebetulan bernomor sama (§8), dan master unit dipakai
 * bersama antar-OPD sehingga menjumlahkan lintas OPD akan mencampur anggaran
 * yang bukan miliknya (§9). Tahun juga memisah: satu unit tidak boleh
 * menjumlahkan realisasi dua tahun anggaran (§10, §31).
 */
class AnggaranUnitService
{
    private BaseConnection $db;

    /** @var array<string, array<string,mixed>> cache pagu per ref_key */
    private array $paguCache = [];

    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?? db_connect();
    }

    /** Kunci unit yang dipakai di seluruh modul: "level:id". */
    public static function kunci(?string $level, ?int $refId): string
    {
        return (string) $level . ':' . (int) $refId;
    }

    /* =========================================================
     * PETA BALIK: UNIT -> INDIKATOR (§12)
     * =======================================================*/

    /**
     * Untuk setiap unit yang dipakai indikator-indikator ini, siapa saja
     * indikator lain yang memakai unit yang sama.
     *
     * Dipakai form MONEV Anggaran untuk memutuskan apakah tampilan "shared"
     * perlu muncul, dan untuk menampilkan bagian indikator lain.
     *
     * =====================================================================
     * TIGA QUERY, BUKAN N+1
     *
     * Seluruh indikator diresolusi sekaligus per tingkat. Bentuk N+1 di sini
     * bukan sekadar lambat: form dibuka per rencana aksi, dan satu program
     * pada basis data ini bisa dipakai 54 indikator.
     *
     * @param list<int> $pkIndikatorIds indikator yang sedang jadi acuan
     *
     * @return array<string, array<string,mixed>> dikunci "level:id"
     */
    public function petaUnitIndikator(array $pkIndikatorIds): array
    {
        $pkIndikatorIds = array_values(array_unique(array_filter(array_map('intval', $pkIndikatorIds))));

        if ($pkIndikatorIds === []) {
            return [];
        }

        // (a) Unit apa saja yang dipakai indikator acuan — beserta lingkupnya.
        $acuan = $this->unitDariIndikator($pkIndikatorIds);

        if ($acuan === []) {
            return [];
        }

        // (b) Untuk tiap unit itu, SEMUA indikator pemakainya dalam lingkup
        //     yang sama (opd + tahun + level + id).
        $peta = [];

        foreach ($acuan as $u) {
            $kunci = self::kunci($u['level'], (int) $u['ref_id']);

            $peta[$kunci] ??= [
                'level'     => $u['level'],
                'ref_id'    => (int) $u['ref_id'],
                'opd_id'    => $u['opd_id'] === null ? null : (int) $u['opd_id'],
                'tahun'     => (int) $u['tahun'],
                'kode'      => $u['kode'],
                'nama'      => $u['nama'],
                'pagu'      => (float) $u['pagu'],
                'indikator' => [],
            ];
        }

        foreach ($this->indikatorPemakai(array_values($peta)) as $baris) {
            $kunci = self::kunci($baris['level'], (int) $baris['ref_id']);

            if (! isset($peta[$kunci])) {
                continue;
            }

            $peta[$kunci]['indikator'][] = [
                'pk_indikator_id'   => (int) $baris['pk_indikator_id'],
                'target_rencana_id' => $baris['target_rencana_id'] === null
                    ? null : (int) $baris['target_rencana_id'],
                'nama'              => $baris['indikator'],
                'pk_jenis'          => $baris['pk_jenis'],
                'sasaran'           => $baris['sasaran'] ?? null,
            ];
        }

        foreach ($peta as &$u) {
            $u['indikator_count'] = count($u['indikator']);
            // Tampilan "shared" HANYA untuk unit yang benar-benar dipakai
            // dua indikator atau lebih (§8).
            $u['shared'] = $u['indikator_count'] >= 2;
        }
        unset($u);

        return $peta;
    }

    /**
     * Unit anggaran beserta lingkup & pagunya, untuk sekumpulan indikator.
     *
     * @param list<int> $pkIndikatorIds
     *
     * @return list<array<string,mixed>>
     */
    private function unitDariIndikator(array $pkIndikatorIds): array
    {
        // =============================================================
        // TIGA QUERY TERPISAH, BUKAN SATU UNION
        //
        // Versi pertama menggabungkannya dengan UNION ALL dan gagal:
        // "Illegal mix of collations for operation 'UNION'". Literal
        // 'program'/'kegiatan' memakai kolasi koneksi, sementara kolom yang
        // disandingkan memakai kolasi tabelnya sendiri.
        //
        // Menambal dengan COLLATE eksplisit berarti mematok satu kolasi yang
        // belum tentu sama di server produksi. Tiga query yang digabung di
        // PHP menghindari seluruh kelas persoalan itu — dan tetap bukan N+1,
        // karena jumlahnya tetap tiga berapa pun indikatornya.
        // =============================================================
        $daftar = $this->daftar($pkIndikatorIds);
        $hasil  = [];

        $q = [
            'program' => "SELECT pp.program_id AS ref_id, pk.opd_id, pk.tahun,
                                 p.kode_program AS kode, p.program_kegiatan AS nama,
                                 COALESCE(p.anggaran,0) AS pagu
                            FROM pk_program pp
                            JOIN pk_indikator pi ON pi.id = pp.pk_indikator_id
                            JOIN pk_sasaran ps ON ps.id = pi.pk_sasaran_id
                            JOIN pk ON pk.id = ps.pk_id
                            JOIN program_pk p ON p.id = pp.program_id
                           WHERE pi.id IN {$daftar}",

            'kegiatan' => "SELECT k.kegiatan_id AS ref_id, pk.opd_id, pk.tahun,
                                  kg.kode_kegiatan AS kode, kg.kegiatan AS nama,
                                  COALESCE(kg.anggaran,0) AS pagu
                             FROM pk_kegiatan k
                             JOIN pk_program pp ON pp.id = k.pk_program_id
                             JOIN pk_indikator pi ON pi.id = pp.pk_indikator_id
                             JOIN pk_sasaran ps ON ps.id = pi.pk_sasaran_id
                             JOIN pk ON pk.id = ps.pk_id
                             JOIN kegiatan_pk kg ON kg.id = k.kegiatan_id
                            WHERE pi.id IN {$daftar}",

            'subkegiatan' => "SELECT sk.subkegiatan_id AS ref_id, pk.opd_id, pk.tahun,
                                     sg.kode_sub_kegiatan AS kode, sg.sub_kegiatan AS nama,
                                     COALESCE(sg.anggaran,0) AS pagu
                                FROM pk_subkegiatan sk
                                JOIN pk_kegiatan k ON k.id = sk.pk_kegiatan_id
                                JOIN pk_program pp ON pp.id = k.pk_program_id
                                JOIN pk_indikator pi ON pi.id = pp.pk_indikator_id
                                JOIN pk_sasaran ps ON ps.id = pi.pk_sasaran_id
                                JOIN pk ON pk.id = ps.pk_id
                                JOIN sub_kegiatan_pk sg ON sg.id = sk.subkegiatan_id
                               WHERE pi.id IN {$daftar}",
        ];

        foreach ($q as $level => $sql) {
            foreach ($this->db->query($sql)->getResultArray() as $r) {
                $r['level'] = $level;
                $hasil[]    = $r;
            }
        }

        return $hasil;
    }

    /**
     * Semua indikator pemakai untuk sekumpulan unit, dalam lingkupnya.
     *
     * `target_rencana_id` boleh NULL: indikator yang belum punya rencana aksi
     * tetap dihitung sebagai pemakai unit (§13). Ia ditampilkan supaya jumlah
     * "mendukung N indikator" jujur, tetapi tidak diberi kolom input.
     *
     * @param list<array<string,mixed>> $unit
     *
     * @return list<array<string,mixed>>
     */
    private function indikatorPemakai(array $unit): array
    {
        if ($unit === []) {
            return [];
        }

        $perLevel = ['program' => [], 'kegiatan' => [], 'subkegiatan' => []];

        foreach ($unit as $u) {
            $perLevel[$u['level']][] = (int) $u['ref_id'];
        }

        // Tiga query terpisah — alasannya sama dengan unitDariIndikator().
        $inti = "pi.id AS pk_indikator_id, pi.indikator, tr.id AS target_rencana_id,
                 COALESCE(pk.jenis, pi.jenis) AS pk_jenis, ps.sasaran, pk.opd_id, pk.tahun";

        $ekor = "JOIN pk_indikator pi ON pi.id = pp.pk_indikator_id
                 JOIN pk_sasaran ps ON ps.id = pi.pk_sasaran_id
                 JOIN pk ON pk.id = ps.pk_id
                 LEFT JOIN target_rencana tr ON tr.pk_indikator_id = pi.id";

        $sql = [];

        if ($perLevel['program'] !== []) {
            $sql['program'] = "SELECT pp.program_id AS ref_id, {$inti}
                                 FROM pk_program pp {$ekor}
                                WHERE pp.program_id IN " . $this->daftar($perLevel['program']);
        }

        if ($perLevel['kegiatan'] !== []) {
            $sql['kegiatan'] = "SELECT k.kegiatan_id AS ref_id, {$inti}
                                  FROM pk_kegiatan k
                                  JOIN pk_program pp ON pp.id = k.pk_program_id {$ekor}
                                 WHERE k.kegiatan_id IN " . $this->daftar($perLevel['kegiatan']);
        }

        if ($perLevel['subkegiatan'] !== []) {
            $sql['subkegiatan'] = "SELECT sk.subkegiatan_id AS ref_id, {$inti}
                                     FROM pk_subkegiatan sk
                                     JOIN pk_kegiatan k ON k.id = sk.pk_kegiatan_id
                                     JOIN pk_program pp ON pp.id = k.pk_program_id {$ekor}
                                    WHERE sk.subkegiatan_id IN " . $this->daftar($perLevel['subkegiatan']);
        }

        if ($sql === []) {
            return [];
        }

        $rows = [];

        foreach ($sql as $level => $q) {
            foreach ($this->db->query($q)->getResultArray() as $r) {
                $r['level'] = $level;
                $rows[]     = $r;
            }
        }

        // Saring ke lingkup masing-masing unit: OPD & tahun harus cocok.
        // Tanpa ini, indikator OPD lain yang kebetulan memakai master unit
        // yang sama akan ikut terhitung (§9).
        $sah = [];

        foreach ($unit as $u) {
            $sah[self::kunci($u['level'], (int) $u['ref_id'])] = [
                'opd'   => $u['opd_id'] === null ? null : (int) $u['opd_id'],
                'tahun' => (int) $u['tahun'],
            ];
        }

        $hasil = [];
        $unik  = [];

        foreach ($rows as $r) {
            $kunci = self::kunci($r['level'], (int) $r['ref_id']);
            $batas = $sah[$kunci] ?? null;

            if ($batas === null) {
                continue;
            }

            $opdBaris = $r['opd_id'] === null ? null : (int) $r['opd_id'];

            if ($opdBaris !== $batas['opd'] || (int) $r['tahun'] !== $batas['tahun']) {
                continue;
            }

            // Relasi PK bisa terduplikasi (§14): indikator yang sama tertaut
            // dua kali ke unit yang sama hanya boleh dihitung sekali.
            $sidik = $kunci . '#' . (int) $r['pk_indikator_id'] . '#' . (int) ($r['target_rencana_id'] ?? 0);

            if (isset($unik[$sidik])) {
                continue;
            }

            $unik[$sidik] = true;
            $hasil[]      = $r;
        }

        return $hasil;
    }

    /** Daftar id aman untuk disisipkan ke SQL mentah. */
    private function daftar(array $ids): string
    {
        $bersih = array_map('intval', $ids);

        return '(' . ($bersih === [] ? '0' : implode(',', $bersih)) . ')';
    }

    /* =========================================================
     * PAGU & PEMAKAIANNYA
     * =======================================================*/

    /**
     * Pagu sebuah unit, dari tabel masternya.
     *
     * Unit yang masternya sudah terhapus mengembalikan NULL — dibedakan dari
     * pagu 0, karena keduanya berbeda arti dan berbeda penanganannya (§25).
     */
    public function pagu(string $level, int $refId): ?float
    {
        $kunci = self::kunci($level, $refId);

        if (array_key_exists($kunci, $this->paguCache)) {
            return $this->paguCache[$kunci];
        }

        [$tabel, $kolom] = match ($level) {
            'kegiatan'    => ['kegiatan_pk', 'anggaran'],
            'subkegiatan' => ['sub_kegiatan_pk', 'anggaran'],
            default       => ['program_pk', 'anggaran'],
        };

        $baris = $this->db->table($tabel)->select($kolom)->where('id', $refId)->get()->getRowArray();

        return $this->paguCache[$kunci] = $baris === null ? null : (float) ($baris[$kolom] ?? 0);
    }

    /**
     * Total realisasi TW I–IV pada satu unit, dijumlah lintas indikator.
     *
     * @param list<int> $kecualiTarget rencana aksi yang nilainya sedang diubah
     *                                 dan karena itu tidak boleh ikut dihitung
     *                                 dari isi lama (§28)
     */
    public function totalTerpakai(
        string $level,
        int $refId,
        ?int $opdId,
        int $tahun,
        array $kecualiTarget = []
    ): float {
        $q = $this->db->table('monev_anggaran ma')
            ->select('SUM(COALESCE(ma.realisasi_triwulan_1,0)+COALESCE(ma.realisasi_triwulan_2,0)
                      +COALESCE(ma.realisasi_triwulan_3,0)+COALESCE(ma.realisasi_triwulan_4,0)) AS total', false)
            ->join('target_rencana tr', 'tr.id = ma.target_rencana_id')
            ->join('pk_indikator pi', 'pi.id = tr.pk_indikator_id')
            ->join('pk_sasaran ps', 'ps.id = pi.pk_sasaran_id')
            ->join('pk', 'pk.id = ps.pk_id')
            ->where('ma.ref_level', $level)
            ->where('ma.ref_id', $refId)
            ->where('pk.tahun', $tahun);

        if ($opdId === null) {
            $q->where('pk.opd_id IS NULL', null, false);
        } else {
            $q->where('pk.opd_id', $opdId);
        }

        $kecualiTarget = array_values(array_unique(array_filter(array_map('intval', $kecualiTarget))));

        if ($kecualiTarget !== []) {
            $q->whereNotIn('ma.target_rencana_id', $kecualiTarget);
        }

        return (float) ($q->get()->getRowArray()['total'] ?? 0);
    }

    /* =========================================================
     * AUDIT UNIT YANG MELEBIHI PAGU (§36)
     * =======================================================*/

    /**
     * Unit yang total realisasinya melampaui pagunya.
     *
     * Dipakai form MONEV, Dashboard OPD/Kabupaten, dan perintah audit —
     * satu sumber kebenaran, supaya jumlah yang tampil di dashboard tidak
     * pernah berbeda dengan yang tampil di form.
     *
     * Data seperti ini SENGAJA tidak diperbaiki otomatis (§0): ia ditampilkan
     * agar operator yang tahu duduk perkaranya yang membetulkan.
     *
     * @return list<array<string,mixed>>
     */
    public function unitLebihPagu(?int $opdId = null, ?int $tahun = null): array
    {
        if (! $this->db->tableExists('monev_anggaran')) {
            return [];
        }

        $q = $this->db->table('monev_anggaran ma')
            ->select("ma.ref_level, ma.ref_id, pk.opd_id, pk.tahun,
                      COALESCE(p.kode_program, kg.kode_kegiatan, sg.kode_sub_kegiatan) AS kode,
                      COALESCE(p.program_kegiatan, kg.kegiatan, sg.sub_kegiatan) AS nama,
                      COALESCE(MAX(CASE ma.ref_level
                          WHEN 'program' THEN p.anggaran
                          WHEN 'kegiatan' THEN kg.anggaran
                          ELSE sg.anggaran END), 0) AS pagu,
                      SUM(COALESCE(ma.realisasi_triwulan_1,0)+COALESCE(ma.realisasi_triwulan_2,0)
                         +COALESCE(ma.realisasi_triwulan_3,0)+COALESCE(ma.realisasi_triwulan_4,0)) AS total,
                      COUNT(DISTINCT pi.id) AS jumlah_indikator,
                      GROUP_CONCAT(DISTINCT ma.target_rencana_id) AS target_ids,
                      GROUP_CONCAT(DISTINCT pi.indikator SEPARATOR ' || ') AS indikator_nama", false)
            ->join('target_rencana tr', 'tr.id = ma.target_rencana_id')
            ->join('pk_indikator pi', 'pi.id = tr.pk_indikator_id')
            ->join('pk_sasaran ps', 'ps.id = pi.pk_sasaran_id')
            ->join('pk', 'pk.id = ps.pk_id')
            ->join('program_pk p', "ma.ref_level = 'program' AND p.id = ma.ref_id", 'left', false)
            ->join('kegiatan_pk kg', "ma.ref_level = 'kegiatan' AND kg.id = ma.ref_id", 'left', false)
            ->join('sub_kegiatan_pk sg', "ma.ref_level = 'subkegiatan' AND sg.id = ma.ref_id", 'left', false)
            ->where('ma.ref_level IS NOT NULL', null, false)
            ->where('ma.ref_id IS NOT NULL', null, false)
            ->groupBy('ma.ref_level, ma.ref_id, pk.opd_id, pk.tahun');

        if ($opdId !== null) {
            $q->where('pk.opd_id', $opdId);
        }

        if ($tahun !== null) {
            $q->where('pk.tahun', $tahun);
        }

        $hasil = [];

        foreach ($q->get()->getResultArray() as $r) {
            $pagu  = (float) $r['pagu'];
            $total = (float) $r['total'];

            if ($total <= $pagu) {
                continue;
            }

            $hasil[] = [
                'ref_level'        => $r['ref_level'],
                'ref_id'           => (int) $r['ref_id'],
                'ref_key'          => self::kunci($r['ref_level'], (int) $r['ref_id']),
                'opd_id'           => $r['opd_id'] === null ? null : (int) $r['opd_id'],
                'tahun'            => (int) $r['tahun'],
                'kode'             => $r['kode'],
                'nama'             => $r['nama'] ?? '(unit tidak ditemukan)',
                'pagu'             => $pagu,
                'total_realisasi'  => $total,
                'selisih'          => $total - $pagu,
                // Pagu 0 tidak bisa dijadikan pembagi; persentasenya
                // dinyatakan NULL, bukan dipaksa menjadi angka karangan (§25).
                'persentase'       => $pagu > 0 ? round($total / $pagu * 100, 1) : null,
                'jumlah_indikator' => (int) $r['jumlah_indikator'],
                'target_rencana_id' => array_values(array_filter(array_map(
                    'intval',
                    explode(',', (string) $r['target_ids'])
                ))),
                'indikator'        => array_values(array_filter(
                    explode(' || ', (string) $r['indikator_nama'])
                )),
            ];
        }

        // Yang paling jauh melampaui pagu diletakkan di depan.
        usort($hasil, static fn ($a, $b) => $b['selisih'] <=> $a['selisih']);

        return $hasil;
    }
}
