<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Efisiensi Program dan Anggaran (tabel `lakip_efisiensi_program`).
 *
 * Sumber program & pagunya `program_pk` — tabel yang sama yang dipakai
 * Perjanjian Kinerja dan MONEV, jadi angkanya konsisten satu sistem.
 *
 * CATATAN PENTING soal kepemilikan OPD:
 * `program_pk`.`opd_id` hampir selalu NULL pada data ini (207 dari 209 baris),
 * jadi OPD sebuah program hanya bisa dijangkau lewat rantai Perjanjian Kinerja:
 *     pk (opd_id, tahun) -> pk_sasaran -> pk_indikator -> pk_program -> program_pk
 * Hal yang sama sudah dicatat di CascadingModel. Karena itu daftar program
 * per OPD DIBANGUN dari rantai tersebut, ditambah program yang kebetulan
 * memang punya `program_pk`.`opd_id` terisi.
 */
class LakipEfisiensiModel extends Model
{
    protected $table = 'lakip_efisiensi_program';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $protectFields = true;

    protected $allowedFields = [
        'program_id',
        'opd_id',
        'tahun',
        'anggaran',
        'realisasi',
        'efisiensi',
        'created_by',
        'updated_by',
    ];

    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    /** Tabelnya opsional: instalasi lama boleh belum menjalankan migrasinya. */
    public function siap(): bool
    {
        return $this->db->tableExists($this->table);
    }

    /**
     * Daftar program tahun tsb yang boleh dipakai OPD ini (untuk dropdown).
     *
     * @param int|null $opdId null/0 = tanpa batas OPD (mode kabupaten /
     *                        admin_kab yang melihat semua OPD)
     *
     * @return array<int, array<string, mixed>> [id, kode_program, program_kegiatan, anggaran]
     */
    public function programOptions(string $tahun, ?int $opdId = null): array
    {
        $b = $this->db->table('program_pk pr')
            ->select('pr.id, pr.kode_program, pr.program_kegiatan, pr.anggaran, pr.tahun_anggaran')
            ->where('pr.tahun_anggaran', $tahun);

        if (!empty($opdId)) {
            // OPD dijangkau lewat rantai PK; sebagian kecil program memang
            // sudah punya opd_id sendiri, keduanya diterima.
            $viaPk = $this->db->table('pk_program pp')
                ->select('pp.program_id')
                ->join('pk_indikator pi', 'pi.id = pp.pk_indikator_id', 'inner')
                ->join('pk_sasaran ps', 'ps.id = pi.pk_sasaran_id', 'inner')
                ->join('pk', 'pk.id = ps.pk_id', 'inner')
                ->where('pk.opd_id', (int) $opdId)
                ->getCompiledSelect();

            $b->groupStart()
                ->where('pr.opd_id', (int) $opdId)
                ->orWhere("pr.id IN ({$viaPk})", null, false)
                ->groupEnd();
        }

        return $b->groupBy('pr.id, pr.kode_program, pr.program_kegiatan, pr.anggaran, pr.tahun_anggaran')
            ->orderBy('pr.kode_program', 'ASC')
            ->orderBy('pr.program_kegiatan', 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * Satu program, HANYA bila memang boleh dipakai OPD & tahun tersebut.
     * Inilah gerbang otorisasi + sumber pagu yang sah (jangan percaya browser).
     */
    public function programSah(int $programId, string $tahun, ?int $opdId = null): ?array
    {
        foreach ($this->programOptions($tahun, $opdId) as $p) {
            if ((int) $p['id'] === $programId) {
                return $p;
            }
        }

        return null;
    }

    /**
     * Baris efisiensi satu tahun beserta nama programnya.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getByTahun(string $tahun, ?int $opdId = null): array
    {
        if (!$this->siap()) {
            return [];
        }

        $b = $this->db->table($this->table . ' e')
            ->select('e.*, pr.kode_program, pr.program_kegiatan, pr.anggaran AS anggaran_sumber')
            ->join('program_pk pr', 'pr.id = e.program_id', 'left')
            ->where('e.tahun', $tahun);

        // opd_id 0 = tingkat kabupaten. null = lintas OPD (admin_kab), tak difilter.
        if ($opdId !== null) {
            $b->where('e.opd_id', (int) $opdId);
        }

        return $b->orderBy('pr.kode_program', 'ASC')
            ->orderBy('e.id', 'ASC')
            ->get()
            ->getResultArray();
    }

    /** Cari baris kembar (program yang sama, tahun & OPD yang sama). */
    public function cariKembar(int $programId, string $tahun, int $opdId, ?int $kecualiId = null): ?array
    {
        if (!$this->siap()) {
            return null;
        }

        $b = $this->db->table($this->table)
            ->where('program_id', $programId)
            ->where('tahun', $tahun)
            ->where('opd_id', $opdId);

        if ($kecualiId !== null) {
            $b->where('id !=', $kecualiId);
        }

        return $b->get()->getRowArray() ?: null;
    }

    public function ambil(int $id): ?array
    {
        return $this->siap() ? $this->find($id) : null;
    }
}
