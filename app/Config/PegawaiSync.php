<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Konfigurasi integrasi data pegawai dengan SIMPEG & SIKASN.
 *
 * Nilai diisi lewat .env (lihat .env / SIMPEG_SIKASN_INTEGRATION.md). Selama
 * kredensial belum diisi, provider menandai dirinya "belum dikonfigurasi" dan
 * fitur sinkron otomatis nonaktif — fitur edit manual tetap berjalan.
 *
 * Contoh .env:
 *   simpeg.enabled = true
 *   simpeg.baseUrl = https://simpeg.example.go.id/api
 *   simpeg.token   = xxxxx
 *   simpeg.employeesPath = /pegawai
 *   sikasn.enabled = true
 *   sikasn.baseUrl = https://sikasn.example.go.id/api
 *   sikasn.token   = yyyyy
 *   sikasn.employeesPath = /asn
 */
class PegawaiSync extends BaseConfig
{
    // ===================== SIMPEG =====================
    public bool   $simpegEnabled       = false;
    public string $simpegBaseUrl       = '';
    public string $simpegToken         = '';
    public string $simpegEmployeesPath = '/pegawai';
    public string $simpegEmployeePath  = '/pegawai/{nip}';

    /**
     * Pemetaan nama field respons API SIMPEG -> field ternormalisasi.
     * Sesuaikan kanan-nya dengan dokumentasi API yang Anda terima.
     */
    public array $simpegFieldMap = [
        'nip'          => 'nip',
        'nama'         => 'nama',
        'nama_jabatan' => 'jabatan',
        'nama_opd'     => 'opd',
        'nama_pangkat' => 'pangkat',
        'golongan'     => 'golongan',
        'eselon'       => 'eselon',
    ];

    // ===================== SIKASN =====================
    public bool   $sikasnEnabled       = false;
    public string $sikasnBaseUrl       = '';
    public string $sikasnToken         = '';
    public string $sikasnEmployeesPath = '/asn';
    public string $sikasnEmployeePath  = '/asn/{nip}';

    public array $sikasnFieldMap = [
        'nip'          => 'nip',
        'nama'         => 'nama_lengkap',
        'nama_jabatan' => 'jabatan',
        'nama_opd'     => 'unit_kerja',
        'nama_pangkat' => 'pangkat',
        'golongan'     => 'golongan',
        'eselon'       => 'eselon',
    ];

    // Timeout HTTP (detik)
    public int $httpTimeout = 20;

    // Verifikasi SSL. Default true (aman). Set false hanya untuk dev jika perlu.
    public bool $simpegVerifySsl = true;
    // Path CA bundle (cacert.pem). Kosong = pakai writable/cacert.pem bila ada,
    // jika tidak ada pakai bawaan php.ini (curl.cainfo).
    public string $simpegCaBundle = '';

    public function __construct()
    {
        parent::__construct();

        // Override dari .env bila ada
        $this->simpegEnabled       = (bool) (env('simpeg.enabled', $this->simpegEnabled));
        $this->simpegBaseUrl       = (string) (env('simpeg.baseUrl', $this->simpegBaseUrl));
        $this->simpegToken         = (string) (env('simpeg.token', $this->simpegToken));
        $this->simpegEmployeesPath = (string) (env('simpeg.employeesPath', $this->simpegEmployeesPath));
        $this->simpegEmployeePath  = (string) (env('simpeg.employeePath', $this->simpegEmployeePath));

        $this->sikasnEnabled       = (bool) (env('sikasn.enabled', $this->sikasnEnabled));
        $this->sikasnBaseUrl       = (string) (env('sikasn.baseUrl', $this->sikasnBaseUrl));
        $this->sikasnToken         = (string) (env('sikasn.token', $this->sikasnToken));
        $this->sikasnEmployeesPath = (string) (env('sikasn.employeesPath', $this->sikasnEmployeesPath));
        $this->sikasnEmployeePath  = (string) (env('sikasn.employeePath', $this->sikasnEmployeePath));

        $this->httpTimeout = (int) (env('pegawaiSync.httpTimeout', $this->httpTimeout));

        $this->simpegVerifySsl = (bool) (env('simpeg.verifySsl', $this->simpegVerifySsl));
        $this->simpegCaBundle  = (string) (env('simpeg.caBundle', $this->simpegCaBundle));
    }
}
