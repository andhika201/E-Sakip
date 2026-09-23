<?php

namespace App\Services\Version;

use CodeIgniter\Database\ConnectionInterface;
use Throwable;

/**
 * Penulis jejak audit versi ke `version_submission_history` (§17, §22).
 *
 * Melengkapi `activity_logs` yang generik per-request, bukan menggantikannya:
 * activity_logs menjawab "siapa membuka URL apa", tabel ini menjawab "siapa
 * mengubah versi mana, dari status apa ke apa, dengan alasan dan dasar apa".
 *
 * ---------------------------------------------------------------------
 * NAMA PELAKU DIBEKUKAN
 *
 * `oleh_nama` dan `oleh_role` disalin, bukan di-join saat ditampilkan. Pegawai
 * berpindah OPD, akun dinonaktifkan, nama berubah ejaan. Jejak audit yang
 * ikut berubah ketika master datanya berubah bukan jejak audit.
 * ---------------------------------------------------------------------
 */
class VersionAuditService
{
    public const AKSI_CREATED              = 'created';
    public const AKSI_EDITED_DRAFT         = 'edited_draft';
    // Suntingan pada versi yang SUDAH DITETAPKAN. Sengaja dibedakan dari
    // edited_draft: menyunting draft itu pekerjaan biasa, menyunting versi
    // resmi mengubah dokumen yang sudah berlaku dan harus bisa ditelusuri
    // tersendiri di jejak audit.
    public const AKSI_EDITED_PUBLISHED     = 'edited_published';
    public const AKSI_SUBMITTED            = 'submitted';
    public const AKSI_RETURNED             = 'returned';
    public const AKSI_RESUBMITTED          = 'resubmitted';
    public const AKSI_PUBLISHED            = 'published';
    public const AKSI_CANCELLED            = 'cancelled';
    public const AKSI_CORRECTION_REQUESTED = 'correction_requested';
    public const AKSI_CORRECTION_APPROVED  = 'correction_approved';
    public const AKSI_CORRECTION_RETURNED  = 'correction_returned';
    public const AKSI_SYNCED               = 'synced';
    public const AKSI_APPLIED              = 'applied';
    public const AKSI_RETIRED              = 'retired';

    private ConnectionInterface $db;

    private ?bool $siap = null;

    public function __construct(?ConnectionInterface $db = null)
    {
        $this->db = $db ?? db_connect();
    }

    public function siap(): bool
    {
        if ($this->siap !== null) {
            return $this->siap;
        }

        try {
            return $this->siap = $this->db->tableExists('version_submission_history');
        } catch (Throwable $e) {
            return $this->siap = false;
        }
    }

    /**
     * Catat satu peristiwa.
     *
     * @param array{
     *     dari_status?:?string, ke_status?:?string,
     *     entitas?:?string, entitas_id?:?int,
     *     ringkasan?:?string, sebelum?:mixed, sesudah?:mixed,
     *     catatan?:?string, alasan?:?string, dasar?:?string,
     *     effective_from?:?string, source_version_id?:?int,
     *     oleh?:?int, oleh_nama?:?string, oleh_role?:?string
     * } $opt
     */
    public function catat(int $versiId, string $aksi, array $opt = []): void
    {
        if (! $this->siap() || $versiId <= 0) {
            return;
        }

        [$oleh, $nama, $role] = $this->pelaku($opt);

        $this->db->table('version_submission_history')->insert([
            'version_id'  => $versiId,
            'aksi'        => $aksi,
            'dari_status' => $opt['dari_status'] ?? null,
            'ke_status'   => $opt['ke_status'] ?? null,
            'entitas'     => $opt['entitas'] ?? null,
            'entitas_id'  => $opt['entitas_id'] ?? null,
            'ringkasan'   => $this->potong($opt['ringkasan'] ?? null, 500),
            'sebelum'     => $this->keJson($opt['sebelum'] ?? null),
            'sesudah'     => $this->keJson($opt['sesudah'] ?? null),
            'catatan'     => $opt['catatan'] ?? null,
            'alasan'      => $opt['alasan'] ?? null,
            'dasar'       => $this->potong($opt['dasar'] ?? null, 255),
            'effective_from_saat_itu' => $opt['effective_from'] ?? null,
            'source_version_id'       => $opt['source_version_id'] ?? null,
            'oleh'      => $oleh,
            'oleh_nama' => $this->potong($nama, 150),
            'oleh_role' => $this->potong($role, 50),
            'ip'        => $this->ip(),
            'pada'      => date('Y-m-d H:i:s'),
        ]);
    }

    /** Riwayat satu versi, terbaru di atas. */
    public function riwayat(int $versiId): array
    {
        if (! $this->siap()) {
            return [];
        }

        return $this->db->table('version_submission_history')
            ->where('version_id', $versiId)
            ->orderBy('pada', 'DESC')
            ->orderBy('id', 'DESC')
            ->get()->getResultArray();
    }

    /* =========================================================
     * INTERNAL
     * =======================================================*/

    /**
     * Pelaku diambil dari sesi bila ada; di CLI sesi tidak ada sama sekali,
     * jadi seluruh pembacaannya dibungkus try/catch — perintah spark tidak
     * boleh gagal hanya karena ingin mencatat jejak.
     *
     * @return array{0:?int,1:?string,2:?string}
     */
    private function pelaku(array $opt): array
    {
        $oleh = $opt['oleh'] ?? null;
        $nama = $opt['oleh_nama'] ?? null;
        $role = $opt['oleh_role'] ?? null;

        if ($oleh !== null && $nama !== null) {
            return [(int) $oleh, $nama, $role];
        }

        try {
            if (function_exists('session')) {
                $s     = session();
                $oleh ??= $s->get('user_id') ?? $s->get('id');
                $nama ??= $s->get('nama') ?? $s->get('username') ?? $s->get('name');
                $role ??= $s->get('role');
            }
        } catch (Throwable $e) {
            // CLI atau sesi belum siap — biarkan null.
        }

        return [
            $oleh === null ? null : (int) $oleh,
            $nama === null ? null : (string) $nama,
            $role === null ? null : (string) $role,
        ];
    }

    private function ip(): ?string
    {
        try {
            if (function_exists('service')) {
                $req = service('request');

                if ($req !== null && method_exists($req, 'getIPAddress')) {
                    $ip = (string) $req->getIPAddress();

                    return $ip === '' ? null : $ip;
                }
            }
        } catch (Throwable $e) {
            // CLI
        }

        return null;
    }

    private function keJson($nilai): ?string
    {
        if ($nilai === null) {
            return null;
        }

        if (is_string($nilai)) {
            // Sudah JSON valid? pakai apa adanya; kalau bukan, bungkus.
            json_decode($nilai, true);

            return json_last_error() === JSON_ERROR_NONE
                ? $nilai
                : json_encode(['nilai' => $nilai], JSON_UNESCAPED_UNICODE);
        }

        $json = json_encode($nilai, JSON_UNESCAPED_UNICODE);

        return $json === false ? null : $json;
    }

    private function potong(?string $teks, int $maks): ?string
    {
        if ($teks === null) {
            return null;
        }

        // Kolomnya VARCHAR dan Config\Database menyetel strictOn=false, sehingga
        // kelebihan panjang akan DIPOTONG SENYAP oleh koneksi aplikasi. Dipotong
        // di sini supaya pemotongannya disengaja dan sadar batasnya.
        return mb_substr($teks, 0, $maks);
    }
}
