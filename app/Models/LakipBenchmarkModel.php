<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Benchmark Provinsi Lampung & Nasional untuk indikator LAKIP
 * (tabel `lakip_benchmark`).
 *
 * Penambatnya INDIKATOR + tahun, bukan target:
 *   mode 'kabupaten' -> rpjmd_indikator_id   (rpjmd_indikator_sasaran.id)
 *   mode 'opd'       -> renstra_indikator_id (renstra_indikator_sasaran.id)
 *
 * Satu indikator hanya punya satu baris pembanding per tahun, sehingga tidak
 * ada indikator duplikat khusus benchmark. Nilai Kabupaten/OPD sengaja TIDAK
 * disimpan di sini — tetap dibaca dari `lakip.capaian_tahun_ini`.
 */
class LakipBenchmarkModel extends Model
{
    protected $table = 'lakip_benchmark';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $protectFields = true;

    protected $allowedFields = [
        'rpjmd_indikator_id',
        'renstra_indikator_id',
        // Kolom sumber IKU (migrasi 2026-08-26). WAJIB terdaftar: dengan
        // $protectFields aktif, kolom tak terdaftar dibuang diam-diam oleh
        // insert()/update() — benchmark bersumber IKU pernah tersimpan tanpa
        // satu pun kolom kunci sehingga tiap simpan ulang mencetak baris yatim
        // baru. Instalasi lama tetap aman: jalur tulis menolak lebih dulu
        // lewat punyaKolomSumber().
        'iku_indikator_id',
        'source_type',
        'opd_id',
        'tahun',
        'nilai_provinsi',
        'nilai_nasional',
        'sumber_provinsi',
        'sumber_nasional',
        'catatan',
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

    /** Kolom penambat sesuai mode halaman LAKIP. */
    /**
     * Kolom kunci indikator untuk satu lingkup.
     *
     * @param string|null $sumber sumber dokumen ('iku'|'renstra'|'rpjmd').
     *                            Wajib diperhatikan sejak layar LAKIP OPD bisa
     *                            menampilkan IKU: mode 'opd' berlaku untuk
     *                            Renstra DAN IKU, sedangkan id keduanya hidup
     *                            di ruang angka yang sama.
     */
    public static function kolomIndikator(string $mode, ?string $sumber = null): string
    {
        // Sumber IKU diperiksa SEBELUM mode: IKU Kabupaten (§24) juga lewat
        // sini, dan memeriksa mode lebih dulu membuat id indikator IKU
        // kabupaten tertulis ke rpjmd_indikator_id — kolom milik dokumen lain.
        if ($sumber === 'iku') {
            return 'iku_indikator_id';
        }

        return $mode === 'kabupaten' ? 'rpjmd_indikator_id' : 'renstra_indikator_id';
    }

    /** Sumber baris yang sah untuk satu mode. */
    public static function sumberSah(string $mode, $diminta): string
    {
        $sumber = trim((string) ($diminta ?? ''));

        // IKU sah di KEDUA tingkat — Kabupaten memakai IKU Kabupaten (§24).
        if ($sumber === 'iku') {
            return 'iku';
        }

        if ($mode === 'kabupaten') {
            return 'rpjmd';
        }

        return 'renstra';
    }

    /** Apakah kolom sumber sudah terpasang (migrasi 2026-08-26). */
    public function punyaKolomSumber(): bool
    {
        return $this->siap() && $this->db->fieldExists('source_type', $this->table);
    }

    /**
     * Benchmark satu tahun untuk seluruh indikator pada lingkup tertentu,
     * dikunci per indikator_id — satu query untuk seluruh halaman.
     *
     * @param int|null $opdId null = lintas OPD (tidak difilter)
     *
     * @return array<int, array<string, mixed>> [indikator_id => baris benchmark]
     */
    public function getByTahunKeyedByIndikator(
        string $tahun,
        string $mode,
        ?int $opdId = null,
        ?string $sumber = null
    ): array {
        if ($tahun === '' || !$this->siap()) {
            return [];
        }

        $kolom = self::kolomIndikator($mode, self::sumberSah($mode, $sumber));

        $b = $this->db->table($this->table)
            ->where('tahun', $tahun)
            ->where($kolom . ' IS NOT NULL', null, false);

        // Mode kabupaten selalu opd_id 0. Mode OPD hanya difilter bila OPD-nya
        // memang dipilih; null berarti lintas OPD (halaman baca saja).
        if ($mode === 'kabupaten') {
            $b->where('opd_id', 0);
        } elseif (!empty($opdId)) {
            $b->where('opd_id', (int) $opdId);
        }

        $peta = [];
        foreach ($b->get()->getResultArray() as $baris) {
            $peta[(int) $baris[$kolom]] = $baris;
        }

        return $peta;
    }

    /** Satu baris benchmark milik indikator + tahun tertentu. */
    public function cariByIndikator(int $indikatorId, string $tahun, string $mode, ?string $sumber = null): ?array
    {
        if ($indikatorId <= 0 || $tahun === '' || !$this->siap()) {
            return null;
        }

        return $this->db->table($this->table)
            ->where(self::kolomIndikator($mode, self::sumberSah($mode, $sumber)), $indikatorId)
            ->where('tahun', $tahun)
            ->get()
            ->getRowArray() ?: null;
    }

    /** Satu baris berdasarkan id. */
    public function ambil(int $id): ?array
    {
        if ($id <= 0 || !$this->siap()) {
            return null;
        }

        return $this->db->table($this->table)->where('id', $id)->get()->getRowArray() ?: null;
    }

    /**
     * Pastikan indikator memang ada pada tahun & lingkup tersebut.
     * Pencegah IDOR: id indikator dari request tidak pernah dipercaya.
     *
     * @return array<string, mixed>|null [indikator_id, indikator_sasaran, satuan, opd_id]
     */
    public function indikatorSah(
        int $indikatorId,
        string $mode,
        string $tahun,
        ?int $opdScope,
        ?string $sumber = null
    ): ?array {
        if ($indikatorId <= 0 || $tahun === '') {
            return null;
        }

        // Sumber IKU: id yang dikirim adalah id INDIKATOR IKU BERJALAN. Tanpa
        // cabang ini, mengisi angka pembanding untuk baris IKU selalu ditolak
        // sebagai "tidak ditemukan" — layar menampilkan indikatornya, tetapi
        // server mencarinya di tabel Renstra.
        if (self::sumberSah($mode, $sumber) === 'iku') {
            $b = $this->db->table('iku_indikator ii')
                ->select('ii.id AS indikator_id, ii.indikator AS indikator_sasaran, ii.satuan, isa.opd_id')
                ->join('iku_sasaran isa', 'isa.id = ii.iku_sasaran_id', 'left')
                ->where('ii.id', $indikatorId);

            // Indikator yang SUDAH DIHENTIKAN tetap sah untuk LAKIP tahun
            // pelaporan yang masih dalam masa berlakunya. LAKIP selalu
            // melaporkan tahun yang sudah lewat: begitu revisi IKU disahkan,
            // indikator lama dicap `dihentikan_pada`, padahal tabel utama —
            // yang mengikuti versi dokumen terpilih — masih menampilkannya.
            // Menuntut `dihentikan_pada IS NULL` membuat SELURUH tombol pada
            // tahun itu mati: layar menawarkan baris yang server selalu tolak.
            // `berlaku_sampai` adalah batas resminya; bila kosong, tahun saat
            // dihentikan dipakai sebagai penggantinya. Saringan lingkup
            // (opd_id) di bawah TIDAK dilonggarkan.
            if ($this->db->fieldExists('dihentikan_pada', 'iku_indikator')) {
                $kolomBatas = $this->db->fieldExists('berlaku_sampai', 'iku_indikator')
                    ? 'COALESCE(ii.berlaku_sampai, YEAR(ii.dihentikan_pada))'
                    : 'YEAR(ii.dihentikan_pada)';

                $b->where(
                    '(ii.dihentikan_pada IS NULL OR ' . $kolomBatas . ' >= ' . (int) $tahun . ')',
                    null,
                    false
                );
            }

            // Lingkup: IKU Kabupaten hidup di iku_sasaran.opd_id NULL,
            // IKU OPD di opd_id pemiliknya. Tanpa saringan kabupaten, id
            // indikator IKU OPD mana pun lolos sebagai "milik kabupaten".
            if ($mode === 'kabupaten') {
                $b->where('isa.opd_id IS NULL', null, false);
            } elseif (!empty($opdScope)) {
                $b->where('isa.opd_id', (int) $opdScope);
            }

            return $b->get()->getRowArray() ?: null;
        }

        if ($mode === 'kabupaten') {
            // Indikator RPJMD dianggap sah bila punya target pada tahun tsb.
            return $this->db->table('rpjmd_indikator_sasaran ris')
                ->select('ris.id AS indikator_id, ris.indikator_sasaran, ris.satuan, 0 AS opd_id')
                ->join('rpjmd_target rpj', 'rpj.indikator_sasaran_id = ris.id', 'inner')
                ->where('ris.id', $indikatorId)
                ->where('rpj.tahun', $tahun)
                ->groupBy('ris.id, ris.indikator_sasaran, ris.satuan')
                ->get()
                ->getRowArray() ?: null;
        }

        $b = $this->db->table('renstra_indikator_sasaran ris')
            ->select('ris.id AS indikator_id, ris.indikator_sasaran, s.satuan AS satuan, rs.opd_id')
            ->join('renstra_target rt', 'rt.renstra_indikator_id = ris.id', 'inner')
            ->join('renstra_sasaran rs', 'rs.id = ris.renstra_sasaran_id', 'left')
            ->join('satuan s', 's.id = ris.satuan', 'left')
            ->where('ris.id', $indikatorId)
            ->where('rt.tahun', $tahun);

        if (!empty($opdScope)) {
            $b->where('rs.opd_id', (int) $opdScope);
        }

        return $b->groupBy('ris.id, ris.indikator_sasaran, s.satuan, rs.opd_id')
            ->get()
            ->getRowArray() ?: null;
    }
}
