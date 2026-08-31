<?php

namespace App\Models;

use CodeIgniter\Model;
use RuntimeException;

/**
 * Pengesahan LAKIP per tahun + permintaan pembukaan bila ada typo.
 *
 * =====================================================================
 * MENGAPA TANPA VERSI/SNAPSHOT
 *
 * LAKIP di sini bukan dokumen berjenjang seperti IKU — ia laporan tahunan
 * yang angkanya tunggal. Yang dibutuhkan bukan salinan beku, melainkan
 * KEADAAN: sudah disahkan atau belum, dan bila perlu diperbaiki, siapa yang
 * meminta serta siapa yang mengizinkan.
 *
 * Karena itu tidak ada tabel salinan. `lakip` tetap satu-satunya tempat
 * angka. Yang ditambahkan hanya kunci dan riwayat izin membukanya.
 *
 * =====================================================================
 * ALURNYA
 *
 *   OPD  : sahkan()          -> tahun terkunci
 *   OPD  : ajukanPembukaan() -> permintaan menunggu (alasan WAJIB)
 *   Kab  : setujui()         -> tahun dibuka, OPD boleh menyunting
 *   Kab  : tolak()           -> tetap terkunci, alasannya tercatat
 *   OPD  : sahkan()          -> mengunci lagi sesudah diperbaiki
 *
 * Riwayat permintaan TIDAK PERNAH dihapus — termasuk yang ditolak. Itulah
 * yang membuat alur ini bisa dipertanggungjawabkan, bukan sekadar tombol
 * buka-kunci.
 */
class LakipPengesahanModel extends Model
{
    protected $table         = 'lakip_pengesahan';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;

    public const STATUS_DISAHKAN = 'disahkan';
    public const STATUS_DIBUKA   = 'dibuka';

    public const MINTA_MENUNGGU  = 'menunggu';
    public const MINTA_DISETUJUI = 'disetujui';
    public const MINTA_DITOLAK   = 'ditolak';
    public const MINTA_DITARIK   = 'ditarik';

    /** Fitur ini mati dengan sendirinya bila migrasinya belum dijalankan. */
    public function siap(): bool
    {
        return $this->db->tableExists('lakip_pengesahan')
            && $this->db->tableExists('lakip_buka_permintaan');
    }

    /**
     * Lingkup kabupaten disimpan sebagai opd_id = 0, mengikuti tabel `lakip`.
     *
     * @return array{mode: string, opd_id: int}
     */
    private function kunciLingkup(string $mode, ?int $opdId): array
    {
        return [
            'mode'   => $mode === 'kabupaten' ? 'kabupaten' : 'opd',
            'opd_id' => $mode === 'kabupaten' ? 0 : (int) $opdId,
        ];
    }

    /** @return array<string,mixed>|null keadaan pengesahan, null bila belum pernah disahkan */
    public function keadaan(int $tahun, string $mode, ?int $opdId): ?array
    {
        if (! $this->siap()) {
            return null;
        }

        $k = $this->kunciLingkup($mode, $opdId);

        return $this->db->table('lakip_pengesahan')
            ->where('tahun', $tahun)
            ->where('mode', $k['mode'])
            ->where('opd_id', $k['opd_id'])
            ->get()->getRowArray();
    }

    /**
     * Inti penjaga: bolehkah angka LAKIP lingkup+tahun ini disunting?
     *
     * Dipanggil SEBELUM setiap tulis di controller. Sengaja mengembalikan
     * "tidak terkunci" saat fitur belum terpasang, supaya basis data lama
     * tidak mendadak membeku.
     */
    public function terkunci(int $tahun, string $mode, ?int $opdId): bool
    {
        $keadaan = $this->keadaan($tahun, $mode, $opdId);

        return $keadaan !== null && $keadaan['status'] === self::STATUS_DISAHKAN;
    }

    /**
     * Sahkan (atau sahkan ulang sesudah perbaikan).
     *
     * Menolak mengesahkan tahun yang belum punya satu pun angka: mengunci
     * halaman kosong hanya menyusahkan, tanpa melindungi apa pun.
     *
     * @return array{id: int, jumlah_realisasi: int, sahkan_ulang: bool}
     */
    public function sahkan(int $tahun, string $mode, ?int $opdId, ?int $userId, array $opsi = []): array
    {
        if (! $this->siap()) {
            throw new RuntimeException('Fitur pengesahan LAKIP belum terpasang di basis data ini.');
        }

        $k = $this->kunciLingkup($mode, $opdId);

        $isi = $this->db->table('lakip')
            ->where('tahun', $tahun)
            ->where('mode', $k['mode'])
            ->where('opd_id', $k['opd_id'])
            ->where('capaian_tahun_ini IS NOT NULL', null, false)
            ->where("capaian_tahun_ini <> ''", null, false)
            ->countAllResults();

        if ($isi < 1) {
            throw new RuntimeException(
                'Belum ada satu pun realisasi yang terisi untuk tahun ' . $tahun
                . ', jadi belum ada yang perlu disahkan.'
            );
        }

        $sekarang = date('Y-m-d H:i:s');
        $ada      = $this->keadaan($tahun, $mode, $opdId);

        $data = [
            'status'        => self::STATUS_DISAHKAN,
            'nomor'         => $opsi['nomor']   ?? ($ada['nomor']   ?? null),
            'catatan'       => $opsi['catatan'] ?? ($ada['catatan'] ?? null),
            'disahkan_oleh' => $userId,
            'disahkan_pada' => $sekarang,
            // Jejak pembukaan sebelumnya dibersihkan: kunci baru, keadaan baru.
            'dibuka_oleh'   => null,
            'dibuka_pada'   => null,
        ];

        if ($ada) {
            $this->db->table('lakip_pengesahan')->where('id', $ada['id'])->update($data);
            $id = (int) $ada['id'];
        } else {
            $this->db->table('lakip_pengesahan')->insert($data + [
                'tahun'  => $tahun,
                'mode'   => $k['mode'],
                'opd_id' => $k['opd_id'],
            ]);
            $id = (int) $this->db->insertID();
        }

        return ['id' => $id, 'jumlah_realisasi' => $isi, 'sahkan_ulang' => $ada !== null];
    }

    /** Permintaan pembukaan oleh OPD. Alasan WAJIB — itu inti pertanggungjawabannya. */
    public function ajukanPembukaan(int $tahun, string $mode, ?int $opdId, string $alasan, ?int $userId): int
    {
        $alasan  = trim($alasan);
        $keadaan = $this->keadaan($tahun, $mode, $opdId);

        if ($keadaan === null) {
            throw new RuntimeException('LAKIP tahun ' . $tahun . ' belum disahkan, jadi belum perlu dibuka.');
        }

        if ($keadaan['status'] !== self::STATUS_DISAHKAN) {
            throw new RuntimeException(
                'LAKIP tahun ' . $tahun . ' sedang dalam keadaan terbuka — silakan langsung perbaiki.'
            );
        }

        if ($alasan === '') {
            throw new RuntimeException('Alasan wajib diisi: sebutkan apa yang keliru dan mengapa perlu dibuka.');
        }

        if ($this->permintaanMenunggu((int) $keadaan['id']) !== null) {
            throw new RuntimeException('Sudah ada permintaan yang menunggu keputusan admin kabupaten.');
        }

        $this->db->table('lakip_buka_permintaan')->insert([
            'pengesahan_id' => (int) $keadaan['id'],
            'alasan'        => $alasan,
            'status'        => self::MINTA_MENUNGGU,
            'diminta_oleh'  => $userId,
            'diminta_pada'  => date('Y-m-d H:i:s'),
        ]);

        return (int) $this->db->insertID();
    }

    /** @return array<string,mixed>|null permintaan yang masih menunggu keputusan */
    public function permintaanMenunggu(int $pengesahanId): ?array
    {
        if (! $this->siap()) {
            return null;
        }

        return $this->db->table('lakip_buka_permintaan')
            ->where('pengesahan_id', $pengesahanId)
            ->where('status', self::MINTA_MENUNGGU)
            ->orderBy('id', 'DESC')
            ->get()->getRowArray();
    }

    /** Riwayat lengkap permintaan pada satu lingkup+tahun, terbaru dulu. */
    public function riwayat(int $pengesahanId): array
    {
        if (! $this->siap()) {
            return [];
        }

        return $this->db->table('lakip_buka_permintaan')
            ->where('pengesahan_id', $pengesahanId)
            ->orderBy('id', 'DESC')
            ->get()->getResultArray();
    }

    /** Kotak masuk admin_kab: seluruh permintaan yang menunggu keputusan. */
    public function menungguKeputusan(): array
    {
        if (! $this->siap()) {
            return [];
        }

        return $this->db->table('lakip_buka_permintaan p')
            ->select('p.*, g.tahun, g.mode, g.opd_id, o.nama_opd')
            ->join('lakip_pengesahan g', 'g.id = p.pengesahan_id')
            ->join('opd o', 'o.id = g.opd_id', 'left')
            ->where('p.status', self::MINTA_MENUNGGU)
            ->orderBy('p.diminta_pada', 'ASC')
            ->get()->getResultArray();
    }

    /** admin_kab menyetujui: tahun dibuka, OPD boleh menyunting. */
    public function setujui(int $permintaanId, ?int $userId, ?string $tanggapan = null): array
    {
        $p = $this->wajibMenunggu($permintaanId);

        $this->db->transBegin();

        try {
            $this->db->table('lakip_buka_permintaan')->where('id', $permintaanId)->update([
                'status'          => self::MINTA_DISETUJUI,
                'tanggapan'       => $tanggapan,
                'ditanggapi_oleh' => $userId,
                'ditanggapi_pada' => date('Y-m-d H:i:s'),
            ]);

            $this->db->table('lakip_pengesahan')->where('id', (int) $p['pengesahan_id'])->update([
                'status'      => self::STATUS_DIBUKA,
                'dibuka_oleh' => $userId,
                'dibuka_pada' => date('Y-m-d H:i:s'),
            ]);

            if ($this->db->transStatus() === false) {
                throw new RuntimeException('Transaksi persetujuan gagal.');
            }

            $this->db->transCommit();
        } catch (\Throwable $e) {
            $this->db->transRollback();

            throw $e;
        }

        return $p;
    }

    /** admin_kab menolak: tetap terkunci, alasan penolakan tercatat. */
    public function tolak(int $permintaanId, ?int $userId, ?string $tanggapan = null): array
    {
        $p = $this->wajibMenunggu($permintaanId);

        $this->db->table('lakip_buka_permintaan')->where('id', $permintaanId)->update([
            'status'          => self::MINTA_DITOLAK,
            'tanggapan'       => $tanggapan,
            'ditanggapi_oleh' => $userId,
            'ditanggapi_pada' => date('Y-m-d H:i:s'),
        ]);

        return $p;
    }

    /** OPD menarik permintaannya sendiri selagi belum diputuskan. */
    public function tarik(int $permintaanId): array
    {
        $p = $this->wajibMenunggu($permintaanId);

        $this->db->table('lakip_buka_permintaan')->where('id', $permintaanId)->update([
            'status' => self::MINTA_DITARIK,
        ]);

        return $p;
    }

    /** @return array<string,mixed> permintaan yang dipastikan masih menunggu */
    private function wajibMenunggu(int $permintaanId): array
    {
        $p = $this->db->table('lakip_buka_permintaan')
            ->where('id', $permintaanId)->get()->getRowArray();

        if ($p === null) {
            throw new RuntimeException('Permintaan tidak ditemukan.');
        }

        if ($p['status'] !== self::MINTA_MENUNGGU) {
            throw new RuntimeException(
                'Permintaan ini sudah diputuskan sebelumnya (' . $p['status'] . ').'
            );
        }

        return $p;
    }
}
