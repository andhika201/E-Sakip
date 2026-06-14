<?php

namespace App\Libraries\Pegawai;

/**
 * Provider data pegawai/ASN dari SIKASN.
 * Endpoint, token, dan pemetaan field diatur via Config\PegawaiSync / .env.
 */
class SikasnProvider extends AbstractDirectoryProvider
{
    public function key(): string
    {
        return 'sikasn';
    }

    public function label(): string
    {
        return 'SIKASN';
    }

    protected function enabled(): bool
    {
        return $this->config->sikasnEnabled;
    }

    protected function baseUrl(): string
    {
        return $this->config->sikasnBaseUrl;
    }

    protected function token(): string
    {
        return $this->config->sikasnToken;
    }

    protected function employeesPath(): string
    {
        return $this->config->sikasnEmployeesPath;
    }

    protected function employeePath(): string
    {
        return $this->config->sikasnEmployeePath;
    }

    protected function fieldMap(): array
    {
        return $this->config->sikasnFieldMap;
    }
}
