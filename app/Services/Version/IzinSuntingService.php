<?php

namespace App\Services\Version;

use App\Models\DokumenVersiModel;
use CodeIgniter\Database\ConnectionInterface;
use RuntimeException;

/**
 * Izin menyunting dokumen yang sudah ditetapkan.
 *
 * =====================================================================
 * APA YANG DIBUKA, DAN APA YANG TIDAK
 *
 * Izin ini membuka kunci TABEL BERJALAN — supaya penyuntingan berjalan lewat
 * form dan tombol yang sudah dikenal, bukan lewat menu koreksi tersendiri.
 *
 * Arsip versi yang sudah ditetapkan TIDAK ikut terbuka dan tidak pernah
 * disentuh. Itu bukan kehati-hatian berlebihan: tujuh layanan memperlakukan
 * `published` sebagai "beku", dan tiga di antaranya (snapshot LAKIP, jejak
 * audit, Bandingkan) kehilangan artinya bila arsip bisa berubah diam-diam.
 *
 * Hasil penyuntingan menjadi VERSI BERIKUTNYA saat diajukan ulang. Jadi izin
 * ini memperpendek jalannya, bukan melubangi arsipnya.
 *
 * =====================================================================
 * MENGAPA MEMINTA DAN MEMUTUSKAN DIPISAH
 *
 * Kalau OPD boleh membuka kuncinya sendiri, penguncian tinggal hiasan.
 * `renstra.izin_sunting.request` ada di OPD, `.verify` di Kabupaten —
 * pembagian yang sama dengan mengajukan versi dan menetapkannya (§17).
 *
 * =====================================================================
 * KAPAN IZIN BERAKHIR
 *
 * Izin yang tidak pernah berakhir sama saja dengan kunci yang dicabut
 * selamanya. Karena itu ia ditutup sendiri (`selesai`) begitu versi berikutnya
 * diajukan — saat itu penyuntingannya memang sudah rampung. Admin Kabupaten
 * juga bisa mencabutnya kapan saja.
 */
class IzinSuntingService
{
    public const STATUS_PENDING   = 'pending';
    public const STATUS_DISETUJUI = 'disetujui';
    public const STATUS_DITOLAK   = 'ditolak';
    public const STATUS_SELESAI   = 'selesai';
    public const STATUS_DICABUT   = 'dicabut';

    /** Status yang masih menggantung; hanya satu boleh ada per dokumen. */
    public const STATUS_BERJALAN = [self::STATUS_PENDING, self::STATUS_DISETUJUI];

    /**
     * =====================================================================
     * DUA JENIS PERMOHONAN, SATU ANTREAN
     *
     * 'sunting' membuka kunci supaya dokumen bisa diperbaiki. 'hapus' meminta
     * dokumen itu DITIADAKAN. Keduanya berbagi tabel, antrean, dan aturan
     * kewenangan yang sama — yang berbeda hanya apa yang terjadi saat
     * disetujui.
     *
     * Baris lama tidak punya kolom ini; DEFAULT 'sunting' pada skema membuat
     * seluruhnya terbaca sebagai permohonan sunting, persis seperti sebelum
     * kolomnya ada.
     * =====================================================================
     */
    public const JENIS_SUNTING = 'sunting';
    public const JENIS_HAPUS   = 'hapus';

    private const TABEL = 'dokumen_izin_sunting';

    private ConnectionInterface $db;
    private ?bool $siap = null;

    public function __construct(?ConnectionInterface $db = null)
    {
        $this->db = $db ?? \Config\Database::connect();
    }

    public function siap(): bool
    {
        if ($this->siap !== null) {
            return $this->siap;
        }

        try {
            return $this->siap = $this->db->tableExists(self::TABEL);
        } catch (\Throwable $e) {
            return $this->siap = false;
        }
    }

    /* =========================================================
     * PEMBACAAN
     * =======================================================*/

    /**
     * Permohonan yang masih berjalan pada satu dokumen, bila ada.
     *
     * `$jenis` null = apa pun jenisnya (dipakai penjaga "satu permohonan
     * menggantung per dokumen"). Bawaannya SUNTING supaya seluruh pemanggil
     * lama — Renstra, RPJMD, dan penjaga kunci IKU — tidak mendadak membaca
     * permohonan HAPUS sebagai izin sunting, yang berarti membuka kunci
     * dokumen atas keputusan yang bukan tentang itu.
     */
    public function berjalan(VersionScope $scope, ?string $jenis = self::JENIS_SUNTING): ?array
    {
        if (! $this->siap()) {
            return null;
        }

        $b = $this->db->table(self::TABEL)
            ->where($this->kondisi($scope))
            ->whereIn('status', self::STATUS_BERJALAN);

        if ($jenis !== null && $this->punyaKolomJenis()) {
            $b->where('jenis', $jenis);
        }

        $baris = $b->get()->getRowArray();

        return $baris ?: null;
    }

    /** Apakah dokumen ini sedang boleh disunting meski versinya sudah ditetapkan. */
    public function bolehSunting(VersionScope $scope): bool
    {
        $izin = $this->berjalan($scope, self::JENIS_SUNTING);

        return $izin !== null && $izin['status'] === self::STATUS_DISETUJUI;
    }

    /** Riwayat permohonan satu dokumen, terbaru di atas. */
    public function riwayat(VersionScope $scope): array
    {
        if (! $this->siap()) {
            return [];
        }

        return $this->db->table(self::TABEL)
            ->where($this->kondisi($scope))
            ->orderBy('diminta_pada', 'DESC')->orderBy('id', 'DESC')
            ->get()->getResultArray();
    }

    /**
     * Antrean permohonan yang menunggu keputusan, lintas OPD.
     *
     * Nama OPD ikut diambil di sini, bukan di tampilan: antrean verifikasi
     * memuat banyak OPD sekaligus, dan mencarinya satu per satu dari layar
     * berarti satu query per baris.
     */
    public function antrean(?string $modul = null): array
    {
        if (! $this->siap()) {
            return [];
        }

        $b = $this->db->table(self::TABEL . ' i')
            ->select('i.*, o.nama_opd')
            ->join('opd o', 'o.id = i.opd_id', 'left')
            ->where('i.status', self::STATUS_PENDING);

        if ($modul !== null) {
            $b->where('i.modul', $modul);
        }

        return $b->orderBy('i.diminta_pada', 'ASC')->get()->getResultArray();
    }

    /**
     * Izin yang SUDAH DISETUJUI dan masih terbuka, lintas dokumen.
     *
     * =====================================================================
     * MENGAPA PERLU DAFTAR TERSENDIRI
     *
     * `antrean()` hanya memuat yang berstatus `pending`. Begitu sebuah izin
     * disetujui, ia LENYAP dari layar Admin Kabupaten — padahal `cabut()`
     * dan rutenya sudah ada sejak awal, hanya tidak pernah punya tempat untuk
     * ditekan.
     *
     * Akibatnya bukan sekadar tidak rapi. Satu dokumen hanya boleh punya satu
     * permohonan menggantung; izin yang terlanjur disetujui lalu tidak pernah
     * ditutup akan MEMBLOKIR seluruh lingkup itu dari permohonan baru, tanpa
     * seorang pun bisa melihat penyebabnya atau mencabutnya.
     *
     * @return array<int,array<string,mixed>>
     */
    public function berjalanSemua(?string $modul = null): array
    {
        if (! $this->siap()) {
            return [];
        }

        $b = $this->db->table(self::TABEL . ' i')
            ->select('i.*, o.nama_opd')
            ->join('opd o', 'o.id = i.opd_id', 'left')
            ->where('i.status', self::STATUS_DISETUJUI);

        if ($modul !== null) {
            $b->where('i.modul', $modul);
        }

        return $b->orderBy('i.diputus_pada', 'ASC')->get()->getResultArray();
    }

    public function ambil(int $id): ?array
    {
        if (! $this->siap() || $id <= 0) {
            return null;
        }

        $baris = $this->db->table(self::TABEL)->where('id', $id)->get()->getRowArray();

        return $baris ?: null;
    }

    /* =========================================================
     * PERUBAHAN KEADAAN
     * =======================================================*/

    /**
     * OPD meminta izin menyunting.
     *
     * @throws RuntimeException bila alasan kosong atau sudah ada permohonan berjalan
     */
    public function ajukan(
        VersionScope $scope,
        string $alasan,
        ?int $oleh,
        ?int $versiId = null,
        string $jenis = self::JENIS_SUNTING
    ): int {
        $this->pastikanSiap();

        $alasan = trim($alasan);
        $jenis  = $jenis === self::JENIS_HAPUS ? self::JENIS_HAPUS : self::JENIS_SUNTING;

        // Alasan diwajibkan, bukan basa-basi: yang membaca permohonan ini adalah
        // orang lain di instansi lain, dan tanpa alasan ia hanya bisa menebak.
        if ($alasan === '') {
            throw new RuntimeException($jenis === self::JENIS_HAPUS
                ? 'Sebutkan alasan mengapa versi ini perlu dihapus.'
                : 'Sebutkan alasan mengapa dokumen ini perlu disunting.');
        }

        // Satu dokumen hanya boleh punya SATU permohonan menggantung, apa pun
        // jenisnya: memohon hapus sementara permohonan sunting masih
        // menggantung berarti dua keputusan yang saling meniadakan atas
        // dokumen yang sama.
        if (($berjalan = $this->berjalan($scope, null)) !== null) {
            throw new RuntimeException(
                ($berjalan['jenis'] ?? self::JENIS_SUNTING) === self::JENIS_HAPUS
                    ? 'Sudah ada permohonan PENGHAPUSAN yang berjalan untuk periode ini. '
                      . 'Selesaikan dulu permohonan itu.'
                    : 'Sudah ada permohonan izin sunting yang berjalan untuk periode ini.'
            );
        }

        $baris = [
            'modul'         => $scope->modul(),
            'scope'         => $scope->scope(),
            'opd_id'        => $scope->opdId(),
            'periode_mulai' => $scope->periodeMulai(),
            'periode_akhir' => $scope->periodeAkhir(),
            'version_id'    => $versiId,
            'status'        => self::STATUS_PENDING,
            'alasan'        => mb_substr($alasan, 0, 5000),
            'diminta_oleh'  => $oleh,
            'diminta_nama'  => $this->namaPengguna(),
            'diminta_pada'  => date('Y-m-d H:i:s'),
        ];

        // Dijaga fieldExists supaya basis data yang belum menjalankan
        // db/update_2026-08-31_izin_hapus_versi_iku.sql tetap melayani jalur
        // sunting seperti biasa, alih-alih gagal menyisipkan.
        if ($this->punyaKolomJenis()) {
            $baris['jenis'] = $jenis;
        }

        $this->db->table(self::TABEL)->insert($baris);

        return (int) $this->db->insertID();
    }

    /** Apakah skema sudah punya kolom pembeda jenis permohonan. */
    public function punyaKolomJenis(): bool
    {
        try {
            return $this->siap() && $this->db->fieldExists('jenis', self::TABEL);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /** OPD menarik permohonannya sendiri selagi belum diputuskan. */
    public function tarik(int $id, ?int $oleh): void
    {
        $izin = $this->wajibAda($id);

        if ($izin['status'] !== self::STATUS_PENDING) {
            throw new RuntimeException('Permohonan ini sudah diputuskan, jadi tidak bisa ditarik.');
        }

        $this->tutup($id, self::STATUS_DICABUT, $oleh, 'Ditarik oleh pemohon.');
    }

    public function setujui(int $id, ?int $oleh, ?string $catatan = null): void
    {
        $izin = $this->wajibAda($id);

        if ($izin['status'] !== self::STATUS_PENDING) {
            throw new RuntimeException('Permohonan ini sudah diputuskan.');
        }

        $this->db->table(self::TABEL)->where('id', $id)->update([
            'status'            => self::STATUS_DISETUJUI,
            'catatan_keputusan' => $this->potong($catatan),
            'diputus_oleh'      => $oleh,
            'diputus_nama'      => $this->namaPengguna(),
            'diputus_pada'      => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Menolak WAJIB disertai catatan.
     *
     * Penolakan tanpa alasan memaksa pemohon menebak apa yang harus diperbaiki,
     * dan biasanya berakhir dengan permohonan yang sama diajukan ulang.
     */
    public function tolak(int $id, ?int $oleh, string $catatan): void
    {
        $izin    = $this->wajibAda($id);
        $catatan = trim($catatan);

        if ($izin['status'] !== self::STATUS_PENDING) {
            throw new RuntimeException('Permohonan ini sudah diputuskan.');
        }

        if ($catatan === '') {
            throw new RuntimeException('Sebutkan alasan penolakan.');
        }

        $this->tutup($id, self::STATUS_DITOLAK, $oleh, $catatan);
    }

    /** Admin Kabupaten mencabut izin yang sudah terlanjur diberikan. */
    public function cabut(int $id, ?int $oleh, ?string $catatan = null): void
    {
        $izin = $this->wajibAda($id);

        if ($izin['status'] !== self::STATUS_DISETUJUI) {
            throw new RuntimeException('Hanya izin yang sedang berlaku yang bisa dicabut.');
        }

        $this->tutup($id, self::STATUS_DICABUT, $oleh, $catatan ?? 'Dicabut Admin Kabupaten.');
    }

    /**
     * Tutup izin karena penyuntingannya sudah rampung.
     *
     * Dipanggil saat versi berikutnya diajukan. Sengaja TIDAK melempar bila
     * tidak ada izin berjalan: pengajuan versi tetap sah tanpa izin apa pun
     * (misalnya pengajuan pertama kali), dan menggagalkannya karena itu akan
     * memblokir alur yang normal.
     */
    public function selesaikan(VersionScope $scope): void
    {
        if (! $this->siap()) {
            return;
        }

        $this->db->table(self::TABEL)
            ->where($this->kondisi($scope))
            ->whereIn('status', self::STATUS_BERJALAN)
            ->update([
                'status'       => self::STATUS_SELESAI,
                'selesai_pada' => date('Y-m-d H:i:s'),
            ]);
    }

    /* =========================================================
     * BANTU
     * =======================================================*/

    private function tutup(int $id, string $status, ?int $oleh, ?string $catatan): void
    {
        $this->db->table(self::TABEL)->where('id', $id)->update([
            'status'            => $status,
            'catatan_keputusan' => $this->potong($catatan),
            'diputus_oleh'      => $oleh,
            'diputus_nama'      => $this->namaPengguna(),
            'diputus_pada'      => date('Y-m-d H:i:s'),
        ]);
    }

    private function wajibAda(int $id): array
    {
        $this->pastikanSiap();

        $izin = $this->ambil($id);

        if ($izin === null) {
            throw new RuntimeException('Permohonan izin sunting tidak ditemukan.');
        }

        return $izin;
    }

    private function pastikanSiap(): void
    {
        if (! $this->siap()) {
            throw new RuntimeException(
                'Fitur izin sunting belum aktif: jalankan db/update_2026-08-24_izin_sunting.sql lebih dulu.'
            );
        }
    }

    /** @return array<string,mixed> */
    private function kondisi(VersionScope $scope): array
    {
        return [
            'modul'         => $scope->modul(),
            'scope_key'     => $scope->scope(),
            'opd_key'       => $scope->opdKey(),
            'periode_mulai' => $scope->periodeMulai(),
            'periode_akhir' => $scope->periodeAkhir(),
        ];
    }

    /**
     * Nama pelaku DIBEKUKAN saat peristiwanya terjadi.
     *
     * Menyimpan id saja berarti nama yang tampil ikut berubah bila penggunanya
     * berganti nama, dan jejak keputusan lama jadi menunjuk orang yang salah.
     */
    private function namaPengguna(): ?string
    {
        $s = session();

        if ($s === null) {
            return null;
        }

        $nama = $s->get('nama') ?? $s->get('username') ?? $s->get('name');

        return $nama === null ? null : mb_substr((string) $nama, 0, 150);
    }

    private function potong(?string $teks): ?string
    {
        $teks = trim((string) $teks);

        return $teks === '' ? null : mb_substr($teks, 0, 5000);
    }
}
