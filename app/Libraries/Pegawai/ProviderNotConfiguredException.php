<?php

namespace App\Libraries\Pegawai;

use RuntimeException;

/**
 * Dilempar saat provider dipanggil padahal base URL / token belum diisi
 * (atau provider dinonaktifkan di .env).
 */
class ProviderNotConfiguredException extends RuntimeException
{
}
