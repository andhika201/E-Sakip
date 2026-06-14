<?php

namespace App\Models;

use CodeIgniter\Model;

class PegawaiModel extends Model
{
    protected $table = 'pegawai';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    // NOTE: nama kolom mengikuti skema tabel `pegawai` yang sebenarnya
    // (opd_id / jabatan_id / pangkat_id), bukan id_opd/id_jabatan/id_pangkat.
    protected $allowedFields = [
        'nama_pegawai',
        'nip_pegawai',
        'tanggal_lahir',
        'opd_id',
        'jabatan_id',
        'pangkat_id',
        'atasan_id',
        'level',
        'url_foto_pegawai',
        'no_whatsapp',
        'kategori',
        'status',
        'edited_by',
        'created_at',
        'updated_at',
    ];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected array $casts = [];
    protected array $castHandlers = [];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = 'deleted_at';

    // Validation
    protected $validationRules = [];
    protected $validationMessages = [];
    protected $skipValidation = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert = [];
    protected $afterInsert = [];
    protected $beforeUpdate = [];
    protected $afterUpdate = [];
    protected $beforeFind = [];
    protected $afterFind = [];
    protected $beforeDelete = [];
    protected $afterDelete = [];

    /**
     * Get all pegawai records.
     *
     * @return array
     */
    public function getAllPegawai()
    {

        return $this->findAll();
    }

    public function getPegawaiByOpdWithLevel($opdId)
    {
        return $this->select('id, nama_pegawai, level, opd_id')
            ->where('opd_id', $opdId)
            ->orderBy('nama_pegawai', 'ASC')
            ->findAll();
    }
    public function getLevelByPegawaiId($pegawaiId)
    {
        return $this->select('level')
            ->where('id', $pegawaiId)
            ->first();
    }

    public function getPegawaiDenganJabatan($opdId, $jenis)
    {
        $builder = $this->db->table('pegawai p')
            ->select('p.id, p.nama_pegawai, p.nip_pegawai, j.nama_jabatan')
            ->join('jabatan j', 'j.id = p.jabatan_id', 'left')
            ->orderBy('p.nama_pegawai', 'ASC');

        if ($jenis === 'jpt') {
            $builder->groupStart()
                ->where('p.opd_id', $opdId)
                ->orWhere('p.opd_id', 46)
                ->groupEnd();
        } else {
            $builder->where('p.opd_id', $opdId);
        }

        return $builder->get()->getResultArray();
    }

    /* =========================================================
     * MANAJEMEN PEGAWAI (EDIT MANUAL JABATAN & OPD)
     * =======================================================*/

    /**
     * Daftar pegawai + nama OPD, jabatan, pangkat.
     * Filter opsional: OPD dan kata kunci (nama / NIP).
     */
    public function getPegawaiList(?int $opdId = null, ?string $search = null): array
    {
        $builder = $this->db->table('pegawai p')
            ->select('
                p.id, p.nama_pegawai, p.nip_pegawai, p.level,
                p.opd_id, p.jabatan_id, p.pangkat_id,
                o.nama_opd, o.singkatan,
                j.nama_jabatan, j.eselon,
                pk.nama_pangkat, pk.golongan
            ')
            ->join('opd o', 'o.id = p.opd_id', 'left')
            ->join('jabatan j', 'j.id = p.jabatan_id', 'left')
            ->join('pangkat pk', 'pk.id = p.pangkat_id', 'left');

        if ($opdId !== null) {
            $builder->where('p.opd_id', $opdId);
        }

        if ($search !== null && $search !== '') {
            $builder->groupStart()
                ->like('p.nama_pegawai', $search)
                ->orLike('p.nip_pegawai', $search)
                ->groupEnd();
        }

        return $builder->orderBy('o.nama_opd', 'ASC')
            ->orderBy('p.nama_pegawai', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function getPegawaiDetail(int $id): ?array
    {
        return $this->db->table('pegawai p')
            ->select('
                p.*,
                o.nama_opd, j.nama_jabatan, pk.nama_pangkat
            ')
            ->join('opd o', 'o.id = p.opd_id', 'left')
            ->join('jabatan j', 'j.id = p.jabatan_id', 'left')
            ->join('pangkat pk', 'pk.id = p.pangkat_id', 'left')
            ->where('p.id', $id)
            ->get()
            ->getRowArray() ?: null;
    }

    /** Daftar jabatan (opsional difilter per OPD). */
    public function getJabatanList(?int $opdId = null): array
    {
        $builder = $this->db->table('jabatan j')
            ->select('j.id, j.nama_jabatan, j.opd_id, j.eselon, o.nama_opd')
            ->join('opd o', 'o.id = j.opd_id', 'left');

        if ($opdId !== null) {
            $builder->where('j.opd_id', $opdId);
        }

        return $builder->orderBy('o.nama_opd', 'ASC')
            ->orderBy('j.nama_jabatan', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function getJabatanById(int $id): ?array
    {
        return $this->db->table('jabatan')
            ->where('id', $id)
            ->get()
            ->getRowArray() ?: null;
    }

    /** Opsi pangkat untuk dropdown. */
    public function getPangkatOptions(): array
    {
        return $this->db->table('pangkat')
            ->select('id, nama_pangkat, golongan')
            ->orderBy('id', 'ASC')
            ->get()
            ->getResultArray();
    }
}
