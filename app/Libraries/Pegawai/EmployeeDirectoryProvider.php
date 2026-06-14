<?php

namespace App\Libraries\Pegawai;

/**
 * Kontrak penyedia data pegawai dari sistem eksternal (SIMPEG, SIKASN, dll).
 *
 * Semua method fetch* mengembalikan data pegawai yang SUDAH ternormalisasi
 * ke bentuk array berikut (lihat AbstractDirectoryProvider::normalize()):
 *
 *   [
 *     'nip'          => string,        // wajib, kunci pencocokan ke tabel pegawai
 *     'nama'         => string|null,
 *     'nama_jabatan' => string|null,
 *     'nama_opd'     => string|null,
 *     'nama_pangkat' => string|null,
 *     'golongan'     => string|null,
 *     'eselon'       => int|null,
 *     'source'       => string,        // 'simpeg' | 'sikasn'
 *   ]
 *
 * Dengan kontrak ini, menambah sumber baru cukup membuat 1 kelas implementasi
 * tanpa menyentuh service/controller.
 */
interface EmployeeDirectoryProvider
{
    /** Identifier mesin, mis. 'simpeg' | 'sikasn'. */
    public function key(): string;

    /** Label untuk ditampilkan ke pengguna. */
    public function label(): string;

    /** True bila base URL & token sudah terisi dan provider diaktifkan. */
    public function isConfigured(): bool;

    /**
     * Ambil seluruh pegawai dari sumber.
     *
     * @return array<int, array<string, mixed>> daftar pegawai ternormalisasi
     * @throws ProviderNotConfiguredException bila belum dikonfigurasi
     */
    public function fetchEmployees(): array;

    /**
     * Ambil satu pegawai berdasarkan NIP.
     *
     * @throws ProviderNotConfiguredException bila belum dikonfigurasi
     */
    public function fetchEmployee(string $nip): ?array;
}
