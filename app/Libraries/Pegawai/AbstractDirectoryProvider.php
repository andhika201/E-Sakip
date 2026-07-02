<?php

namespace App\Libraries\Pegawai;

use Config\PegawaiSync;

/**
 * Basis bersama untuk provider berbasis HTTP REST (SIMPEG, SIKASN).
 *
 * Subclass cukup menyediakan: key(), label(), base URL, token, path, dan
 * pemetaan field (fieldMap). Logika HTTP + normalisasi ada di sini.
 */
abstract class AbstractDirectoryProvider implements EmployeeDirectoryProvider
{
    protected PegawaiSync $config;

    public function __construct(?PegawaiSync $config = null)
    {
        $this->config = $config ?? config('PegawaiSync');
    }

    // ---- yang wajib disediakan subclass ----
    abstract public function key(): string;
    abstract public function label(): string;
    abstract protected function enabled(): bool;
    abstract protected function baseUrl(): string;
    abstract protected function token(): string;
    abstract protected function employeesPath(): string;
    abstract protected function employeePath(): string;

    /** @return array<string,string> peta field_ternormalisasi => field_api */
    abstract protected function fieldMap(): array;

    public function isConfigured(): bool
    {
        return $this->enabled()
            && trim($this->baseUrl()) !== ''
            && trim($this->token()) !== '';
    }

    public function fetchEmployees(): array
    {
        $this->assertConfigured();

        $payload = $this->httpGet($this->employeesPath());
        $rows    = $this->extractList($payload);

        $out = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $norm = $this->normalize($row);
            if ($norm['nip'] !== '') {
                $out[] = $norm;
            }
        }

        return $out;
    }

    public function fetchEmployee(string $nip): ?array
    {
        $this->assertConfigured();

        $path    = str_replace('{nip}', rawurlencode($nip), $this->employeePath());
        $payload = $this->httpGet($path);

        // respons bisa berupa object tunggal atau {data:{...}}
        $row = $payload['data'] ?? $payload;
        if (!is_array($row) || empty($row)) {
            return null;
        }

        $norm = $this->normalize($row);
        return $norm['nip'] !== '' ? $norm : null;
    }

    /* ===================== INTERNAL ===================== */

    protected function assertConfigured(): void
    {
        if (!$this->isConfigured()) {
            throw new ProviderNotConfiguredException(
                $this->label() . ' belum dikonfigurasi (cek base URL & token di .env).'
            );
        }
    }

    /**
     * Lakukan GET ke API dan kembalikan body JSON sebagai array.
     *
     * @return array<mixed>
     */
    protected function httpGet(string $path): array
    {
        $client = \Config\Services::curlrequest([
            'baseURI'     => rtrim($this->baseUrl(), '/') . '/',
            'timeout'     => $this->config->httpTimeout,
            'http_errors' => false,
        ]);

        $response = $client->get(ltrim($path, '/'), [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->token(),
                'Accept'        => 'application/json',
            ],
        ]);

        $status = $response->getStatusCode();
        if ($status < 200 || $status >= 300) {
            throw new \RuntimeException(
                $this->label() . " HTTP {$status} saat memanggil {$path}."
            );
        }

        $data = json_decode((string) $response->getBody(), true);
        if (!is_array($data)) {
            throw new \RuntimeException($this->label() . ' mengembalikan respons non-JSON.');
        }

        return $data;
    }

    /**
     * Ambil array daftar dari berbagai bentuk respons umum:
     *   [...], {data:[...]}, {data:{items:[...]}}, {result:[...]}
     *
     * @param array<mixed> $payload
     * @return array<mixed>
     */
    protected function extractList(array $payload): array
    {
        foreach (['data', 'result', 'items', 'rows'] as $key) {
            if (isset($payload[$key]) && is_array($payload[$key])) {
                $candidate = $payload[$key];
                // nested {data:{items:[...]}}
                if (isset($candidate['items']) && is_array($candidate['items'])) {
                    return $candidate['items'];
                }
                return $candidate;
            }
        }

        // anggap payload itu sendiri sudah berupa list
        return $payload;
    }

    /**
     * Petakan 1 baris respons API ke bentuk ternormalisasi.
     *
     * @param array<string,mixed> $row
     * @return array<string,mixed>
     */
    protected function normalize(array $row): array
    {
        $map = $this->fieldMap();
        $get = static function (string $apiKey) use ($row) {
            $val = $row[$apiKey] ?? null;
            if (is_string($val)) {
                $val = trim($val);
            }
            return $val;
        };

        $eselon = $get($map['eselon'] ?? 'eselon');

        return [
            'nip'          => (string) ($get($map['nip'] ?? 'nip') ?? ''),
            'nama'         => $get($map['nama'] ?? 'nama'),
            'nama_jabatan' => $get($map['nama_jabatan'] ?? 'nama_jabatan'),
            'nama_opd'     => $get($map['nama_opd'] ?? 'nama_opd'),
            'nama_pangkat' => $get($map['nama_pangkat'] ?? 'nama_pangkat'),
            'golongan'     => $get($map['golongan'] ?? 'golongan'),
            'eselon'       => is_numeric($eselon) ? (int) $eselon : null,
            'source'       => $this->key(),
        ];
    }
}
