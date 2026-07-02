<?php

namespace App\Libraries\Pegawai;

/**
 * Provider data pegawai dari SIMPEG.
 * Endpoint, token, dan pemetaan field diatur via Config\PegawaiSync / .env.
 */
class SimpegProvider extends AbstractDirectoryProvider
{
    public function key(): string
    {
        return 'simpeg';
    }

    public function label(): string
    {
        return 'SIMPEG';
    }

    protected function enabled(): bool
    {
        return $this->config->simpegEnabled;
    }

    protected function baseUrl(): string
    {
        return $this->config->simpegBaseUrl;
    }

    protected function token(): string
    {
        return $this->config->simpegToken;
    }

    protected function employeesPath(): string
    {
        return $this->config->simpegEmployeesPath;
    }

    protected function employeePath(): string
    {
        return $this->config->simpegEmployeePath;
    }

    protected function fieldMap(): array
    {
        return $this->config->simpegFieldMap;
    }
}
