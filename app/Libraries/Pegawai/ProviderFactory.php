<?php

namespace App\Libraries\Pegawai;

/**
 * Registry provider data pegawai. Tambah sumber baru di sini.
 */
class ProviderFactory
{
    /** @return array<string, EmployeeDirectoryProvider> */
    public static function all(): array
    {
        return [
            'simpeg' => new SimpegProvider(),
            'sikasn' => new SikasnProvider(),
        ];
    }

    public static function make(string $key): ?EmployeeDirectoryProvider
    {
        return self::all()[$key] ?? null;
    }
}
