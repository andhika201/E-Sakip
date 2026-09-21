<?php

namespace App\Models;

use CodeIgniter\Model;
use RuntimeException;

/** Source binding document-level untuk flow LAKIP baru. */
class LakipDokumenModel extends Model
{
    protected $table = 'lakip_dokumen';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $allowedFields = [
        'tahun', 'mode', 'opd_id', 'source_type', 'source_version_id',
        'source_override_reason', 'status_input', 'created_by',
    ];

    public function siap(): bool
    {
        return $this->db->tableExists($this->table);
    }

    public function kabupaten(int $tahun): ?array
    {
        if (! $this->siap()) {
            return null;
        }

        return $this->where('tahun', $tahun)
            ->where('mode', 'kabupaten')
            ->where('opd_id', 0)
            ->first();
    }

    /**
     * Bind IKU secara eksplisit. Perubahan versi diblok setelah dokumen
     * memiliki baris; operator harus memakai flow perubahan yang terkontrol.
     */
    public function ikatIkuKabupaten(int $tahun, int $revisiId, ?int $userId, ?string $alasan = null): array
    {
        if (! $this->siap()) {
            throw new RuntimeException('Struktur source binding LAKIP belum dipasang. Jalankan migration terlebih dahulu.');
        }
        if ($tahun < 1900 || $revisiId <= 0) {
            throw new RuntimeException('Tahun atau versi IKU tidak sah.');
        }

        $this->db->transStart();
        $dokumen = $this->kabupaten($tahun);

        if ($dokumen !== null
            && ((string) $dokumen['source_type'] !== 'iku' || (int) $dokumen['source_version_id'] !== $revisiId)) {
            $jumlahBaris = $this->db->fieldExists('lakip_dokumen_id', 'lakip')
                ? $this->db->table('lakip')->where('lakip_dokumen_id', (int) $dokumen['id'])->countAllResults()
                : 0;

            if ($jumlahBaris > 0) {
                $this->db->transRollback();
                throw new RuntimeException('IKU acuan tidak dapat diganti karena dokumen sudah memiliki realisasi. Data lama tidak dihapus otomatis.');
            }

            $this->update((int) $dokumen['id'], [
                'source_type' => 'iku',
                'source_version_id' => $revisiId,
                'source_override_reason' => trim((string) $alasan) ?: null,
            ]);
            $dokumen = $this->find((int) $dokumen['id']);
        } elseif ($dokumen === null) {
            $this->insert([
                'tahun' => $tahun,
                'mode' => 'kabupaten',
                'opd_id' => 0,
                'source_type' => 'iku',
                'source_version_id' => $revisiId,
                'source_override_reason' => trim((string) $alasan) ?: null,
                'created_by' => $userId,
            ]);
            $dokumen = $this->find((int) $this->getInsertID());
        }

        $this->db->transComplete();
        if (! $this->db->transStatus() || $dokumen === null) {
            throw new RuntimeException('Source binding LAKIP gagal disimpan.');
        }

        return $dokumen;
    }

    /**
     * Bind RPJMD secara eksplisit sebagai fallback untuk LAKIP Kabupaten.
     */
    public function ikatRpjmdKabupaten(int $tahun, ?int $userId, ?string $alasan = null): array
    {
        if (! $this->siap()) {
            throw new RuntimeException('Struktur source binding LAKIP belum dipasang. Jalankan migration terlebih dahulu.');
        }
        if ($tahun < 1900) {
            throw new RuntimeException('Tahun LAKIP tidak sah.');
        }

        $this->db->transStart();
        $dokumen = $this->kabupaten($tahun);

        if ($dokumen !== null && (string) $dokumen['source_type'] !== 'rpjmd') {
            $jumlahBaris = $this->db->fieldExists('lakip_dokumen_id', 'lakip')
                ? $this->db->table('lakip')->where('lakip_dokumen_id', (int) $dokumen['id'])->countAllResults()
                : 0;

            if ($jumlahBaris > 0) {
                $this->db->transRollback();
                throw new RuntimeException('Sumber acuan tidak dapat diganti karena dokumen sudah memiliki realisasi. Data lama tidak dihapus otomatis.');
            }

            $this->update((int) $dokumen['id'], [
                'source_type' => 'rpjmd',
                'source_version_id' => null,
                'source_override_reason' => trim((string) $alasan) ?: null,
            ]);
            $dokumen = $this->find((int) $dokumen['id']);
        } elseif ($dokumen === null) {
            $this->insert([
                'tahun' => $tahun,
                'mode' => 'kabupaten',
                'opd_id' => 0,
                'source_type' => 'rpjmd',
                'source_version_id' => null,
                'source_override_reason' => trim((string) $alasan) ?: null,
                'created_by' => $userId,
            ]);
            $dokumen = $this->find((int) $this->getInsertID());
        }

        $this->db->transComplete();
        if (! $this->db->transStatus() || $dokumen === null) {
            throw new RuntimeException('Source binding LAKIP gagal disimpan.');
        }

        return $dokumen;
    }
}
