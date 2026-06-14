<?php

namespace App\Libraries\Pegawai;

use Config\PegawaiSync;

/**
 * Klien HTTP untuk SIMPEG "Presensi Sync API".
 * Spec: api_dokumentasi_presensi.md
 *
 * - Base URL : https://<host>/api/presensi  (diisi di .env: simpeg.baseUrl)
 * - Auth     : header Authorization: Bearer <token>  (juga kirim X-API-TOKEN)
 * - Envelope : { success: bool, data: [...] , meta?: {...} }
 * - Endpoint : /opd, /pangkat, /jabatan, /pegawai (paginated), /pegawai/{nip}
 */
class SimpegClient
{
    protected PegawaiSync $config;

    public function __construct(?PegawaiSync $config = null)
    {
        $this->config = $config ?? config('PegawaiSync');
    }

    public function isConfigured(): bool
    {
        return $this->config->simpegEnabled
            && trim($this->config->simpegBaseUrl) !== ''
            && trim($this->config->simpegToken) !== '';
    }

    /**
     * Nilai opsi 'verify' untuk CurlRequest:
     * - path CA bundle eksplisit (config) bila ada,
     * - jika tidak, writable/cacert.pem bila tersedia (bundle terbaru),
     * - jika tidak, true/false sesuai simpegVerifySsl (default true = pakai php.ini).
     *
     * @return string|bool
     */
    protected function sslVerify()
    {
        // opt-out eksplisit (dev) menang
        if ($this->config->simpegVerifySsl === false) {
            return false;
        }
        // CA bundle eksplisit dari config
        $bundle = trim($this->config->simpegCaBundle);
        if ($bundle !== '' && is_file($bundle)) {
            return $bundle;
        }
        // bundle bawaan: simpeg_cacert.pem (Mozilla root + intermediate Let's Encrypt R12)
        // lalu cacert.pem biasa
        foreach (['simpeg_cacert.pem', 'cacert.pem'] as $f) {
            if (is_file(WRITEPATH . $f)) {
                return WRITEPATH . $f;
            }
        }
        return true;
    }

    protected function assertConfigured(): void
    {
        if (!$this->isConfigured()) {
            throw new ProviderNotConfiguredException(
                'SIMPEG belum dikonfigurasi (isi simpeg.baseUrl & simpeg.token di .env).'
            );
        }
    }

    /**
     * GET ke endpoint API, kembalikan body JSON terdekode.
     *
     * @param array<string,mixed> $query
     * @return array<string,mixed>
     */
    protected function get(string $path, array $query = []): array
    {
        $this->assertConfigured();

        $client = \Config\Services::curlrequest([
            'baseURI'     => rtrim($this->config->simpegBaseUrl, '/') . '/',
            'http_errors' => false,
        ]);

        // verify & timeout dipasang per-request (opsi konstruktor tidak selalu diterapkan)
        $res = $client->get(ltrim($path, '/'), [
            'query'   => $query,
            'timeout' => $this->config->httpTimeout,
            'verify'  => $this->sslVerify(),
            'headers' => [
                'Authorization' => 'Bearer ' . $this->config->simpegToken,
                'X-API-TOKEN'   => $this->config->simpegToken,
                'Accept'        => 'application/json',
            ],
        ]);

        $status = $res->getStatusCode();
        $body   = (string) $res->getBody();
        $json   = json_decode($body, true);

        if ($status === 401) {
            throw new \RuntimeException('SIMPEG menolak token (401). Periksa simpeg.token.');
        }
        if ($status === 429) {
            throw new \RuntimeException('SIMPEG rate limit (429). Coba lagi sebentar.');
        }
        if ($status < 200 || $status >= 300) {
            throw new \RuntimeException("SIMPEG HTTP {$status} saat memanggil /{$path}.");
        }
        if (!is_array($json)) {
            throw new \RuntimeException('SIMPEG mengembalikan respons non-JSON.');
        }
        if (isset($json['success']) && $json['success'] === false) {
            throw new \RuntimeException('SIMPEG: ' . ($json['message'] ?? 'permintaan gagal') . '.');
        }

        return $json;
    }

    /** @return array<int, array<string,mixed>> master OPD */
    public function opd(): array
    {
        return $this->get('opd')['data'] ?? [];
    }

    /** @return array<int, array<string,mixed>> master pangkat (PNS + PPPK) */
    public function pangkat(?string $jenis = null): array
    {
        $q = $jenis ? ['jenis' => $jenis] : [];
        return $this->get('pangkat', $q)['data'] ?? [];
    }

    /** @return array<int, array<string,mixed>> master jabatan (struktural/fungsional/pelaksana) */
    public function jabatan(?string $kategori = null): array
    {
        $q = $kategori ? ['kategori' => $kategori] : [];
        return $this->get('jabatan', $q)['data'] ?? [];
    }

    /**
     * Satu halaman pegawai.
     *
     * @return array{meta: array<string,mixed>, data: array<int, array<string,mixed>>}
     */
    public function pegawaiPage(int $page = 1, int $perPage = 500, ?string $updatedSince = null, $opdId = null): array
    {
        $q = ['per_page' => $perPage, 'page' => $page];
        if ($updatedSince) { $q['updated_since'] = $updatedSince; }
        if ($opdId)        { $q['opd_id'] = $opdId; }

        $json = $this->get('pegawai', $q);
        return [
            'meta' => $json['meta'] ?? ['current_page' => $page, 'last_page' => $page],
            'data' => $json['data'] ?? [],
        ];
    }

    /**
     * Seluruh pegawai (loop semua halaman).
     *
     * @return array<int, array<string,mixed>>
     */
    public function allPegawai(?string $updatedSince = null, int $perPage = 500): array
    {
        $all  = [];
        $page = 1;
        do {
            $res  = $this->pegawaiPage($page, $perPage, $updatedSince);
            $rows = $res['data'];
            $all  = array_merge($all, $rows);
            $last = (int) ($res['meta']['last_page'] ?? $page);
            $page++;
        } while ($page <= $last && !empty($rows));

        return $all;
    }

    /** Total pegawai di sumber (dari meta), murah untuk pratinjau. */
    public function pegawaiTotal(?string $updatedSince = null): int
    {
        $res = $this->pegawaiPage(1, 1, $updatedSince);
        return (int) ($res['meta']['total'] ?? count($res['data']));
    }

    /** @return array<string,mixed>|null detail pegawai by NIP */
    public function pegawaiByNip(string $nip): ?array
    {
        try {
            $json = $this->get('pegawai/' . rawurlencode($nip));
        } catch (\RuntimeException $e) {
            return null;
        }
        $row = $json['data'] ?? null;
        return is_array($row) && !empty($row) ? $row : null;
    }
}
