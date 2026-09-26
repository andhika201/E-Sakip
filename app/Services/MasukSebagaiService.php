<?php

namespace App\Services;

use Config\Database;

/**
 * AKSARA+ — "Masuk sebagai": Admin Kabupaten / Super Admin memakai akun lain (Admin OPD, Admin Kecamatan, Bupati,
 * Inspektorat) untuk menelusuri AKSARA persis seperti pemiliknya, lalu kembali ke akunnya sendiri. Pasangan fitur yang
 * sama di eKin (App\Services\MasukSebagaiService di repo eKin).
 *
 * SESI
 *   user_id, username, role, opd_id, isLoggedIn   akun yang DITIRU — seluruh kode lama membaca kunci ini, jadi otomatis ikut
 *   masuk_sebagai_asli_id, _asli_username         akun admin yang sebenarnya
 *   masuk_sebagai_terakhir                        id akun yang baru saja ditiru (≤ 6)
 *
 * PENJAGA
 *   - Hanya bila .env `demo.masukSebagai = true` (bawaan mati; lingkungan prototipe/peragaan).
 *   - Hak dinilai dari akun ASLI: admin boleh berganti langsung dari satu akun ke akun lain, tetapi akun yang ditiru tidak
 *     pernah bisa memulai "Masuk sebagai" sendiri.
 *   - Akun Super Admin dan Admin Kabupaten tidak bisa ditiru — pintu ini tidak boleh menjadi jalan naik ke Super Admin.
 *   - AuthFilter memeriksa ulang akun asli setiap permintaan dan menolak ganti kata sandi / 2FA akun yang ditiru.
 *   - Mulai, ganti, dan kembali dicatat di activity_logs atas nama akun asli; setiap log lain selama meniru diberi
 *     catatan pelaku asli (log_activity).
 */
final class MasukSebagaiService
{
    /** Peran akun ASLI yang boleh memakai fitur ini. */
    public const PERAN_PELAKU = ['admin_kab', 'admin'];

    /** Peran akun yang tidak bisa ditiru. */
    public const PERAN_TAK_BISA_DITIRU = ['admin_kab', 'admin'];

    private const BATAS_TERAKHIR = 6;

    public static function nyala(): bool
    {
        return filter_var(env('demo.masukSebagai', false), FILTER_VALIDATE_BOOLEAN);
    }

    public static function bolehMemulai(?string $peranAsli): bool
    {
        return in_array((string) $peranAsli, self::PERAN_PELAKU, true);
    }

    /**
     * @param array<string,mixed> $sasaran baris users
     */
    public static function bolehDitiru(array $sasaran, int $idAsli): bool
    {
        return ! empty($sasaran['is_active'])
            && (int) ($sasaran['user_id'] ?? 0) !== $idAsli
            && ! in_array((string) ($sasaran['role'] ?? ''), self::PERAN_TAK_BISA_DITIRU, true);
    }

    public static function sedangMeniru(): bool
    {
        return (int) (session()->get('masuk_sebagai_asli_id') ?? 0) > 0;
    }

    /** Id akun asli sesi ini (akun yang masuk bila sedang tidak meniru). */
    public static function idAsli(): int
    {
        return (int) (session()->get('masuk_sebagai_asli_id') ?? session()->get('user_id') ?? 0);
    }

    /**
     * @return array<string,mixed>|null baris users akun asli (tanpa password & rahasia 2FA)
     */
    public static function akunAsli(): ?array
    {
        $id = self::idAsli();

        return $id > 0 ? self::akun($id) : null;
    }

    /** Saklar menyala dan akun ASLI sesi ini aktif serta berperan pelaku. */
    public static function bolehDipakai(): bool
    {
        if (! self::nyala()) {
            return false;
        }

        $asli = self::akunAsli();

        return $asli !== null && ! empty($asli['is_active']) && self::bolehMemulai($asli['role'] ?? null);
    }

    /**
     * Masuk sebagai akun `$idSasaran`. Null bila tidak boleh.
     *
     * @return array<string,mixed>|null baris users sasaran
     */
    public function mulai(int $idSasaran): ?array
    {
        if (! self::bolehDipakai()) {
            return null;
        }

        $asli    = self::akunAsli();
        $sasaran = self::akun($idSasaran);

        if ($sasaran === null || ! self::bolehDitiru($sasaran, (int) $asli['user_id'])) {
            return null;
        }

        $sesi     = session();
        $terakhir = array_values(array_filter(
            (array) ($sesi->get('masuk_sebagai_terakhir') ?? []),
            static fn ($id) => (int) $id !== $idSasaran
        ));
        array_unshift($terakhir, $idSasaran);

        $this->catat($asli, $sasaran, self::sedangMeniru() ? 'ganti_akun_tiruan' : 'masuk_sebagai');

        // MENGAPA regenerate: identitas sesi berubah, jadi id sesinya juga.
        $sesi->regenerate(true);
        $sesi->set([
            'user_id'                     => (int) $sasaran['user_id'],
            'username'                    => $sasaran['username'],
            'role'                        => $sasaran['role'],
            'opd_id'                      => $sasaran['opd_id'] === null ? null : (int) $sasaran['opd_id'],
            'isLoggedIn'                  => true,
            'masuk_sebagai_asli_id'       => (int) $asli['user_id'],
            'masuk_sebagai_asli_username' => $asli['username'],
            'masuk_sebagai_terakhir'      => array_slice($terakhir, 0, self::BATAS_TERAKHIR),
        ]);

        return $sasaran;
    }

    /** Kembali ke akun asli. False bila sesi ini sedang tidak meniru. */
    public function kembali(): bool
    {
        if (! self::sedangMeniru()) {
            return false;
        }

        $sesi    = session();
        $asli    = self::akun((int) $sesi->get('masuk_sebagai_asli_id'));
        $sasaran = self::akun((int) $sesi->get('user_id'));

        if ($asli === null || empty($asli['is_active'])) {
            $sesi->destroy();

            return false;
        }

        if ($sasaran !== null) {
            $this->catat($asli, $sasaran, 'kembali_ke_akun_asli');
        }

        $terakhir = $sesi->get('masuk_sebagai_terakhir') ?? [];
        $sesi->regenerate(true);
        $sesi->remove(['masuk_sebagai_asli_id', 'masuk_sebagai_asli_username']);
        $sesi->set([
            'user_id'                => (int) $asli['user_id'],
            'username'               => $asli['username'],
            'role'                   => $asli['role'],
            'opd_id'                 => $asli['opd_id'] === null ? null : (int) $asli['opd_id'],
            'isLoggedIn'             => true,
            'masuk_sebagai_terakhir' => $terakhir,
        ]);

        return true;
    }

    /**
     * Akun yang bisa ditiru, disaring. AKSARA memakai akun per perangkat daerah, jadi pencarian nama/jabatan
     * mencocokkan KEPALA perangkat daerahnya.
     *
     * MENGAPA kepala dari PK tahun berjalan (jpt/camat, pihak pertama terbaru), bukan opd.id_kepala_opd: kolom itu
     * banyak yang basi (telaah 26-09-2026, simulasi/01b_pk.php), sedangkan PK perubahan memuat pejabat terkini —
     * termasuk Plt. Sama dengan kepala yang dikirim API eKin (api/ekin/opd).
     *
     * @param array{q?:string, peran?:string, jenis?:string} $saring
     *
     * @return list<array<string,mixed>>
     */
    public function cari(array $saring): array
    {
        $db = Database::connect();
        $q  = $db->table('users u')
            ->select('u.user_id, u.username, u.role, u.opd_id, o.nama_opd, o.singkatan, o.jenis AS jenis_opd, r.label AS peran_label')
            ->join('opd o', 'o.id = u.opd_id', 'left')
            ->join('roles r', 'r.name = u.role', 'left')
            ->where('u.is_active', 1)
            ->where('u.user_id !=', self::idAsli())
            ->whereNotIn('u.role', self::PERAN_TAK_BISA_DITIRU);

        if (($saring['peran'] ?? '') !== '') {
            $q->where('u.role', $saring['peran']);
        }

        if (($saring['jenis'] ?? '') !== '') {
            $q->where('o.jenis', $saring['jenis']);
        }

        $akun   = $q->orderBy("FIELD(u.role, 'bupati', 'admin_inspektorat', 'admin_opd', 'admin_kecamatan')", '', false)
            ->orderBy('o.nama_opd')->orderBy('u.username')
            ->get()->getResultArray();
        $kepala = $this->kepalaPerOpd((int) date('Y'));
        $kata   = preg_split('/\s+/u', mb_strtolower(trim((string) ($saring['q'] ?? ''))), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $hasil  = [];

        foreach ($akun as $a) {
            $k = $kepala[(int) $a['opd_id']] ?? null;
            $a['kepala_nama']    = $k['nama'] ?? null;
            $a['kepala_jabatan'] = $k['jabatan'] ?? null;

            // Setiap kata harus cocok di salah satu kolom: "dinas kesehatan" → admin_dinkes.
            $teks = mb_strtolower(implode(' ', [$a['username'], $a['nama_opd'], $a['singkatan'], $a['peran_label'], $a['kepala_nama'], $a['kepala_jabatan']]));

            foreach ($kata as $k1) {
                if (! str_contains($teks, $k1)) {
                    continue 2;
                }
            }

            $hasil[] = $a;
        }

        return $hasil;
    }

    /**
     * Kepala tiap perangkat daerah menurut PK jpt/camat tahun `$tahun` (pihak pertama PK terbaru).
     *
     * @return array<int,array{nama:string, jabatan:string}>
     */
    private function kepalaPerOpd(int $tahun): array
    {
        $baris = Database::connect()->table('pk k')
            ->select('k.opd_id, k.is_plt_pihak_1, k.jabatan_pihak_1_manual, p.nama_pegawai, j.nama_jabatan')
            ->join('pegawai p', 'p.id = k.pihak_1', 'left')
            ->join('jabatan j', 'j.id = p.jabatan_id', 'left')
            ->where('k.tahun', $tahun)
            ->whereIn('k.jenis', ['jpt', 'camat'])
            ->where('k.pihak_1 >', 0)
            ->orderBy('k.tanggal')->orderBy('k.created_at')->orderBy('k.id')
            ->get()->getResultArray();

        $hasil = [];

        foreach ($baris as $r) {
            if (($r['nama_pegawai'] ?? '') === '') {
                continue;
            }

            $jabatan = trim((string) ($r['jabatan_pihak_1_manual'] ?: $r['nama_jabatan']));
            $hasil[(int) $r['opd_id']] = [
                'nama'    => (string) $r['nama_pegawai'],
                'jabatan' => (! empty($r['is_plt_pihak_1']) ? 'Plt. ' : '') . ucwords(strtolower($jabatan)),
            ];
        }

        return $hasil;
    }

    /**
     * Akun yang baru saja ditiru di sesi ini (terbaru dulu), masih bisa ditiru.
     *
     * @return list<array<string,mixed>>
     */
    public function terakhir(): array
    {
        $ids = array_map('intval', (array) (session()->get('masuk_sebagai_terakhir') ?? []));

        if ($ids === []) {
            return [];
        }

        $baris = Database::connect()->table('users u')
            ->select('u.user_id, u.username, u.role, u.is_active, o.nama_opd, o.singkatan')
            ->join('opd o', 'o.id = u.opd_id', 'left')
            ->whereIn('u.user_id', $ids)
            ->get()->getResultArray();

        $perId = array_column($baris, null, 'user_id');
        $hasil = [];

        foreach ($ids as $id) {
            if (isset($perId[$id]) && self::bolehDitiru($perId[$id], self::idAsli()) && $id !== (int) session()->get('user_id')) {
                $hasil[] = $perId[$id];
            }
        }

        return $hasil;
    }

    /**
     * @return array<string,mixed>|null
     */
    private static function akun(int $id): ?array
    {
        $baris = Database::connect()->table('users')
            ->select('user_id, username, role, opd_id, is_active')
            ->where('user_id', $id)
            ->get()->getRowArray();

        return $baris ?: null;
    }

    /**
     * @param array<string,mixed> $asli
     * @param array<string,mixed> $sasaran
     */
    private function catat(array $asli, array $sasaran, string $aksi): void
    {
        // Pelaku ditetapkan eksplisit = akun ASLI, apa pun isi sesi pada saat itu.
        log_activity($aksi, 'masuk_sebagai', sprintf('%s → akun %s (%s)', $asli['username'], $sasaran['username'], $sasaran['role']), [
            'user_id'  => (int) $asli['user_id'],
            'username' => $asli['username'],
            'role'     => $asli['role'],
        ]);
    }
}
