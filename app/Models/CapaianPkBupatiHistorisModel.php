<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Capaian PK Bupati tahunan yang diinput MANUAL, untuk tahun sebelum
 * KabupatenDashboardService::TAHUN_MULAI_ENGINE.
 *
 * Tahun-tahun itu memakai indikator RPJMD/RPD lama yang tidak tersambung ke
 * data sekarang, jadi mesin tidak dipaksa menghitungnya — angkanya diketik
 * langsung supaya grafik tren tahunan di dashboard tetap bisa tampil.
 *
 * Skema: db/update_2026-09-26_capaian_pk_bupati_historis.sql.
 */
class CapaianPkBupatiHistorisModel extends Model
{
    protected $table          = 'capaian_pk_bupati_historis';
    protected $primaryKey     = 'id';
    protected $returnType     = 'array';
    protected $useTimestamps  = true;
    protected $createdField   = 'created_at';
    protected $updatedField   = 'updated_at';
    protected $dateFormat     = 'datetime';
    protected $protectFields  = true;
    protected $allowedFields  = ['tahun', 'capaian', 'keterangan', 'updated_by'];

    /** Tabel belum dibuat (SQL belum dijalankan di server) = anggap kosong, jangan 500. */
    public function tersedia(): bool
    {
        return $this->db->tableExists($this->table);
    }

    /**
     * @return array<int, array<string, mixed>> [tahun => baris]
     */
    public function petaTahun(int $dari, int $sampai): array
    {
        if (!$this->tersedia()) {
            return [];
        }

        $peta = [];
        foreach ($this->where('tahun >=', $dari)->where('tahun <=', $sampai)->findAll() as $r) {
            $peta[(int) $r['tahun']] = $r;
        }

        return $peta;
    }

    /**
     * Simpan satu set tahun sekaligus: nilai null = hapus baris tahun itu.
     *
     * @param array<int, array{capaian: float|null, keterangan: string|null}> $isi [tahun => ...]
     */
    public function simpanSemua(array $isi, ?int $userId): void
    {
        $this->db->transStart();

        foreach ($isi as $tahun => $r) {
            $ada = $this->where('tahun', $tahun)->first();

            if ($r['capaian'] === null) {
                if ($ada) {
                    $this->delete($ada['id']);
                }
                continue;
            }

            $data = [
                'tahun'      => $tahun,
                'capaian'    => $r['capaian'],
                'keterangan' => $r['keterangan'],
                'updated_by' => $userId,
            ];
            $ada ? $this->update($ada['id'], $data) : $this->insert($data);
        }

        $this->db->transComplete();
        if (!$this->db->transStatus()) {
            throw new \RuntimeException('Transaksi dibatalkan oleh basis data.');
        }
    }
}
