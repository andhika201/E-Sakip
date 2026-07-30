<?php

namespace App\Models\Opd;

use CodeIgniter\Model;
use Throwable;

/**
 * IKU STANDALONE.
 *
 * Sejak 2026-07-27 IKU tidak lagi menempel ke RENSTRA/RPJMD. Sasaran, indikator,
 * satuan, dan target per tahun diinput sendiri di modul IKU:
 *
 *   iku_sasaran (opd_id NULL = tingkat kabupaten)
 *     └─ iku_indikator
 *          ├─ iku_target   (target per tahun)
 *          └─ iku_program  (program pendukung, tampilannya masih dinonaktifkan)
 *
 * Pemilik data ditentukan `iku_sasaran.opd_id`:
 *   * NULL   -> IKU Pemerintah Kabupaten (admin_kab)
 *   * terisi -> IKU OPD / Kecamatan      (admin_opd, admin_kecamatan)
 *
 * Tabel lama `iku` + `iku_program_pendukung` masih ada sebagai cadangan tapi
 * sudah tidak dipakai model ini.
 */
class IkuModel extends Model
{
    protected $table         = 'iku_sasaran';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = [
        'opd_id',
        'sasaran',
        'tahun_mulai',
        'tahun_akhir',
        'urutan',
        'created_at',
        'updated_at',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * `iku_indikator.satuan` menyimpan id numerik ke tabel `satuan` bila dipilih
     * dari dropdown, atau teks bebas bila diketik manual — pola yang sama dipakai
     * modul RPJMD. Join & COALESCE ini membuat keduanya tetap terbaca.
     */
    private const SATUAN_JOIN   = "ind.satuan REGEXP '^[0-9]+$' AND sat.id = ind.satuan";
    private const SATUAN_SELECT = "COALESCE(sat.satuan, NULLIF(ind.satuan, ''))";

    /* =========================================================
     * OPSI FILTER
     * =======================================================*/

    /**
     * Daftar periode yang benar-benar ada di data IKU (bukan lagi dari
     * renstra_sasaran / rpjmd_misi).
     *
     * @param string   $level 'kabupaten' | 'opd' | 'semua'
     * @param int|null $opdId batasi ke satu OPD (dipakai admin_opd/kecamatan)
     *
     * @return array<string, array{period: string, years: int[], tahun_mulai: int, tahun_akhir: int}>
     */
    public function getPeriodeOptions(string $level = 'semua', ?int $opdId = null): array
    {
        $builder = $this->db->table('iku_sasaran')
            ->select('DISTINCT tahun_mulai, tahun_akhir', false)
            ->orderBy('tahun_mulai', 'DESC');

        $this->applyScope($builder, $level, $opdId);

        $periodes = [];
        foreach ($builder->get()->getResultArray() as $row) {
            if (empty($row['tahun_mulai']) || empty($row['tahun_akhir'])) {
                continue;
            }

            $mulai = (int) $row['tahun_mulai'];
            $akhir = (int) $row['tahun_akhir'];
            if ($akhir < $mulai) {
                $akhir = $mulai;
            }

            $periodes[$mulai . '-' . $akhir] = [
                'period'      => $mulai . ' - ' . $akhir,
                'years'       => range($mulai, $akhir),
                'tahun_mulai' => $mulai,
                'tahun_akhir' => $akhir,
            ];
        }

        return $periodes;
    }

    /** Opsi satuan untuk dropdown form. */
    public function getSatuanOptions(): array
    {
        return $this->db->table('satuan')
            ->select('id, satuan')
            ->orderBy('satuan', 'ASC')
            ->get()
            ->getResultArray();
    }

    /* =========================================================
     * PEMBACAAN DATA
     * =======================================================*/

    /**
     * Matriks IKU: sasaran beserta indikator, target per tahun, dan program.
     *
     * @param array{
     *     level?: string, opd_id?: int|null, tahun_mulai?: int|null,
     *     tahun_akhir?: int|null, status?: string|null
     * } $opt
     *
     * @return array<int, array<string, mixed>>
     */
    public function getMatrix(array $opt = []): array
    {
        $level  = $opt['level'] ?? 'semua';
        $opdId  = $opt['opd_id'] ?? null;
        $mulai  = $opt['tahun_mulai'] ?? null;
        $akhir  = $opt['tahun_akhir'] ?? null;
        $status = $opt['status'] ?? null;

        $builder = $this->db->table('iku_sasaran sas')
            ->select('sas.id, sas.opd_id, sas.sasaran, sas.tahun_mulai, sas.tahun_akhir, sas.urutan, o.nama_opd')
            ->join('opd o', 'o.id = sas.opd_id', 'left')
            ->orderBy('sas.opd_id IS NULL', 'DESC', false)
            ->orderBy('o.nama_opd', 'ASC')
            ->orderBy('sas.urutan', 'ASC')
            ->orderBy('sas.id', 'ASC');

        $this->applyScope($builder, $level, $opdId, 'sas.');

        if ($mulai !== null) {
            $builder->where('sas.tahun_mulai', (int) $mulai);
        }
        if ($akhir !== null) {
            $builder->where('sas.tahun_akhir', (int) $akhir);
        }

        $sasaranRows = $builder->get()->getResultArray();
        if (empty($sasaranRows)) {
            return [];
        }

        $sasaranIds = array_column($sasaranRows, 'id');

        // --- indikator ---
        $indikatorBuilder = $this->db->table('iku_indikator ind')
            ->select('ind.*, ' . self::SATUAN_SELECT . ' AS satuan_nama', false)
            ->join('satuan sat', self::SATUAN_JOIN, 'left', false)
            ->whereIn('ind.iku_sasaran_id', $sasaranIds)
            ->orderBy('ind.urutan', 'ASC')
            ->orderBy('ind.id', 'ASC');

        if ($status !== null && $status !== '') {
            $indikatorBuilder->where('ind.status', $status);
        }

        $indikatorRows = $indikatorBuilder->get()->getResultArray();
        $indikatorIds  = array_column($indikatorRows, 'id');

        // --- target & program ---
        $targetMap  = $this->getTargetMap($indikatorIds);
        $programMap = $this->getProgramMap($indikatorIds);

        $indikatorPerSasaran = [];
        foreach ($indikatorRows as $ind) {
            $ind['target']  = $targetMap[$ind['id']] ?? [];
            $ind['program'] = $programMap[$ind['id']] ?? [];

            $indikatorPerSasaran[$ind['iku_sasaran_id']][] = $ind;
        }

        $hasil = [];
        foreach ($sasaranRows as $sasaran) {
            $sasaran['nama_opd']  = $sasaran['nama_opd'] ?? null;
            $sasaran['indikator'] = $indikatorPerSasaran[$sasaran['id']] ?? [];

            // Filter status membuat sasaran bisa kehilangan seluruh indikatornya —
            // sasaran seperti itu tidak perlu ikut ditampilkan.
            if ($status !== null && $status !== '' && empty($sasaran['indikator'])) {
                continue;
            }

            $hasil[] = $sasaran;
        }

        return $hasil;
    }

    /**
     * Satu sasaran + indikator + target + program, untuk halaman edit.
     */
    public function getSasaranDetail(int $sasaranId): ?array
    {
        $sasaran = $this->db->table('iku_sasaran sas')
            ->select('sas.*, o.nama_opd')
            ->join('opd o', 'o.id = sas.opd_id', 'left')
            ->where('sas.id', $sasaranId)
            ->get()
            ->getRowArray();

        if (!$sasaran) {
            return null;
        }

        $indikator = $this->db->table('iku_indikator ind')
            ->select('ind.*, ' . self::SATUAN_SELECT . ' AS satuan_nama', false)
            ->join('satuan sat', self::SATUAN_JOIN, 'left', false)
            ->where('ind.iku_sasaran_id', $sasaranId)
            ->orderBy('ind.urutan', 'ASC')
            ->orderBy('ind.id', 'ASC')
            ->get()
            ->getResultArray();

        $indikatorIds = array_column($indikator, 'id');
        $targetMap    = $this->getTargetMap($indikatorIds);
        $programMap   = $this->getProgramMap($indikatorIds);

        foreach ($indikator as &$ind) {
            $ind['target']  = $targetMap[$ind['id']] ?? [];
            $ind['program'] = $programMap[$ind['id']] ?? [];
        }
        unset($ind);

        $sasaran['indikator'] = $indikator;

        return $sasaran;
    }

    /**
     * opd_id pemilik sebuah sasaran IKU, untuk cek otorisasi lintas-OPD (IDOR).
     *
     * opd_id NULL punya arti (IKU kabupaten), jadi "tidak ketemu" dibedakan
     * lewat flag `found` — bukan lewat return null.
     *
     * @return array{found: bool, opd_id: int|null}
     */
    public function getSasaranOwner(int $sasaranId): array
    {
        $row = $this->db->table('iku_sasaran')
            ->select('opd_id')
            ->where('id', $sasaranId)
            ->get()
            ->getRowArray();

        if (!$row) {
            return ['found' => false, 'opd_id' => null];
        }

        return [
            'found'  => true,
            'opd_id' => $row['opd_id'] !== null ? (int) $row['opd_id'] : null,
        ];
    }

    /**
     * Sama seperti getSasaranOwner() tapi lewat id indikator.
     *
     * @return array{found: bool, opd_id: int|null, iku_sasaran_id: int|null}
     */
    public function getIndikatorOwner(int $indikatorId): array
    {
        $row = $this->db->table('iku_indikator ind')
            ->select('sas.id AS iku_sasaran_id, sas.opd_id')
            ->join('iku_sasaran sas', 'sas.id = ind.iku_sasaran_id')
            ->where('ind.id', $indikatorId)
            ->get()
            ->getRowArray();

        if (!$row) {
            return ['found' => false, 'opd_id' => null, 'iku_sasaran_id' => null];
        }

        return [
            'found'          => true,
            'opd_id'         => $row['opd_id'] !== null ? (int) $row['opd_id'] : null,
            'iku_sasaran_id' => (int) $row['iku_sasaran_id'],
        ];
    }

    /* =========================================================
     * SYNC DARI RPJMD / RENSTRA
     *
     * IKU tetap berdiri sendiri — sync hanya MENYALIN data sekali jalan,
     * tidak membuat relasi permanen ke rpjmd/renstra. Karena itu deteksi
     * "sudah pernah disalin" memakai pencocokan teks yang dinormalkan,
     * bukan kolom foreign key.
     * =======================================================*/

    /**
     * Periode yang tersedia di sumber (RPJMD: dari rpjmd_misi, RENSTRA: dari
     * renstra_sasaran milik OPD ybs).
     *
     * @return array<string, array{period: string, years: int[], tahun_mulai: int, tahun_akhir: int}>
     */
    public function getPeriodeSumber(string $sumber, ?int $opdId = null): array
    {
        if ($sumber === 'rpjmd') {
            $rows = $this->db->table('rpjmd_misi')
                ->select('DISTINCT tahun_mulai, tahun_akhir', false)
                ->orderBy('tahun_mulai', 'DESC')
                ->get()
                ->getResultArray();
        } else {
            $builder = $this->db->table('renstra_sasaran')
                ->select('DISTINCT tahun_mulai, tahun_akhir', false)
                ->orderBy('tahun_mulai', 'DESC');

            if ($opdId !== null) {
                $builder->where('opd_id', $opdId);
            }

            $rows = $builder->get()->getResultArray();
        }

        $periodes = [];
        foreach ($rows as $row) {
            if (empty($row['tahun_mulai']) || empty($row['tahun_akhir'])) {
                continue;
            }

            $mulai = (int) $row['tahun_mulai'];
            $akhir = max((int) $row['tahun_akhir'], $mulai);

            $periodes[$mulai . '-' . $akhir] = [
                'period'      => $mulai . ' - ' . $akhir,
                'years'       => range($mulai, $akhir),
                'tahun_mulai' => $mulai,
                'tahun_akhir' => $akhir,
            ];
        }

        return $periodes;
    }

    /**
     * Daftar kandidat sync: sasaran + indikator + target dari RPJMD/RENSTRA,
     * lengkap dengan penanda mana yang sudah pernah masuk ke IKU.
     *
     * @param string   $sumber 'rpjmd' | 'renstra'
     * @param int|null $opdId  wajib untuk 'renstra'; untuk 'rpjmd' selalu null (tingkat kabupaten)
     *
     * @return array<int, array<string, mixed>>
     */
    public function getKandidatSync(string $sumber, ?int $opdId, int $tahunMulai, int $tahunAkhir): array
    {
        $sasaranRows = $sumber === 'rpjmd'
            ? $this->kandidatSasaranRpjmd($tahunMulai, $tahunAkhir)
            : $this->kandidatSasaranRenstra((int) $opdId, $tahunMulai, $tahunAkhir);

        if (empty($sasaranRows)) {
            return [];
        }

        $indikatorRows = $sumber === 'rpjmd'
            ? $this->kandidatIndikatorRpjmd(array_column($sasaranRows, 'sumber_id'))
            : $this->kandidatIndikatorRenstra(array_column($sasaranRows, 'sumber_id'));

        $indikatorPerSasaran = [];
        foreach ($indikatorRows as $ind) {
            $indikatorPerSasaran[$ind['sumber_sasaran_id']][] = $ind;
        }

        // Peta IKU yang sudah ada pada lingkup & periode yang sama, untuk
        // menandai duplikat.
        $ikuTerpasang = $this->petaIkuTerpasang($opdId, $tahunMulai, $tahunAkhir);

        $hasil = [];
        foreach ($sasaranRows as $sasaran) {
            $kunciSasaran = $this->normalkanTeks($sasaran['sasaran']);
            $ikuSasaran   = $ikuTerpasang[$kunciSasaran] ?? null;

            $daftarIndikator = [];
            foreach ($indikatorPerSasaran[$sasaran['sumber_id']] ?? [] as $ind) {
                $ind['sudah_ada'] = $ikuSasaran !== null
                    && isset($ikuSasaran['indikator'][$this->normalkanTeks($ind['indikator'])]);

                $daftarIndikator[] = $ind;
            }

            $sasaran['iku_sasaran_id'] = $ikuSasaran['id'] ?? null;
            $sasaran['sudah_ada']      = $ikuSasaran !== null;
            $sasaran['indikator']      = $daftarIndikator;
            $sasaran['jumlah_baru']    = count(array_filter($daftarIndikator, static fn($i) => !$i['sudah_ada']));

            $hasil[] = $sasaran;
        }

        return $hasil;
    }

    /**
     * Impor indikator terpilih ke tabel IKU.
     *
     * Data yang disalin diambil ulang dari DB berdasarkan id sumber — isi form
     * tidak pernah dipercaya. Sasaran IKU yang sudah ada dipakai ulang (indikator
     * baru ditempelkan ke sana) supaya tidak muncul sasaran kembar, dan indikator
     * yang sudah ada dilewati supaya definisi/formula yang sudah diketik manual
     * di IKU tidak tertimpa.
     *
     * @param array<int, int[]> $pilihan [id_sasaran_sumber => [id_indikator_sumber, ...]]
     *
     * @return array{sasaran_baru: int, indikator_baru: int, target: int, dilewati: int}
     */
    public function importSync(string $sumber, ?int $opdId, array $pilihan, int $tahunMulai, int $tahunAkhir): array
    {
        $kandidat = $this->getKandidatSync($sumber, $opdId, $tahunMulai, $tahunAkhir);
        $stat     = ['sasaran_baru' => 0, 'indikator_baru' => 0, 'target' => 0, 'dilewati' => 0];

        $db = $this->db;
        $db->transBegin();

        try {
            foreach ($kandidat as $sasaran) {
                $idSumber = (int) $sasaran['sumber_id'];
                if (empty($pilihan[$idSumber])) {
                    continue;
                }

                $dipilih = array_map('intval', (array) $pilihan[$idSumber]);

                $indikatorDiimpor = array_values(array_filter(
                    $sasaran['indikator'],
                    static fn($ind) => in_array((int) $ind['sumber_id'], $dipilih, true)
                ));

                if (empty($indikatorDiimpor)) {
                    continue;
                }

                // Indikator yang sudah ada di IKU dilewati, bukan ditimpa.
                $indikatorBaru = array_values(array_filter($indikatorDiimpor, static fn($ind) => !$ind['sudah_ada']));
                $stat['dilewati'] += count($indikatorDiimpor) - count($indikatorBaru);

                if (empty($indikatorBaru)) {
                    continue;
                }

                $sasaranId = $sasaran['iku_sasaran_id'];

                if ($sasaranId === null) {
                    $sasaranId = $this->insertSasaran([
                        'opd_id'      => $opdId,
                        'sasaran'     => $sasaran['sasaran'],
                        'tahun_mulai' => $tahunMulai,
                        'tahun_akhir' => $tahunAkhir,
                        'urutan'      => $this->urutanBerikutSasaran($opdId, $tahunMulai, $tahunAkhir),
                    ]);
                    $stat['sasaran_baru']++;
                }

                $urutan = $this->urutanBerikutIndikator((int) $sasaranId);

                foreach ($indikatorBaru as $ind) {
                    // Target di luar periode terpilih tidak ikut dibawa.
                    $target = [];
                    foreach ($ind['target'] as $tahun => $nilai) {
                        if ($tahun >= $tahunMulai && $tahun <= $tahunAkhir) {
                            $target[$tahun] = $nilai;
                        }
                    }

                    $this->insertIndikator((int) $sasaranId, [
                        'indikator'       => $ind['indikator'],
                        'definisi'        => $ind['definisi'],
                        'satuan'          => $ind['satuan'],
                        'jenis_indikator' => $ind['jenis_indikator'],
                        'baseline'        => $ind['baseline'],
                        'status'          => 'draft',
                        'target'          => $target,
                    ], $urutan++);

                    $stat['indikator_baru']++;
                    $stat['target'] += count($target);
                }
            }

            if ($db->transStatus() === false) {
                throw new \RuntimeException('Transaksi sync IKU gagal.');
            }

            $db->transCommit();

            return $stat;
        } catch (Throwable $e) {
            $db->transRollback();
            throw $e;
        }
    }

    /* ------------------ helper sync ------------------ */

    /** Sasaran RPJMD pada satu periode (periode berasal dari rpjmd_misi). */
    private function kandidatSasaranRpjmd(int $tahunMulai, int $tahunAkhir): array
    {
        $rows = $this->db->table('rpjmd_sasaran rs')
            ->select('rs.id AS sumber_id, rs.sasaran_rpjmd AS sasaran, rs.status, rtuj.tujuan_rpjmd AS induk')
            ->join('rpjmd_tujuan rtuj', 'rtuj.id = rs.tujuan_id')
            ->join('rpjmd_misi rmis', 'rmis.id = rtuj.misi_id')
            ->where('rmis.tahun_mulai', $tahunMulai)
            ->where('rmis.tahun_akhir', $tahunAkhir)
            ->orderBy('rmis.id', 'ASC')
            ->orderBy('rtuj.id', 'ASC')
            ->orderBy('rs.id', 'ASC')
            ->get()
            ->getResultArray();

        return array_map(static fn($r) => $r + ['sumber_id' => (int) $r['sumber_id']], $rows);
    }

    /** Sasaran RENSTRA satu OPD pada satu periode. */
    private function kandidatSasaranRenstra(int $opdId, int $tahunMulai, int $tahunAkhir): array
    {
        $rows = $this->db->table('renstra_sasaran rs')
            ->select('rs.id AS sumber_id, rs.sasaran, rs.status, rtuj.tujuan AS induk')
            ->join('renstra_tujuan rtuj', 'rtuj.id = rs.renstra_tujuan_id', 'left')
            ->where('rs.opd_id', $opdId)
            ->where('rs.tahun_mulai', $tahunMulai)
            ->where('rs.tahun_akhir', $tahunAkhir)
            ->orderBy('rs.id', 'ASC')
            ->get()
            ->getResultArray();

        return array_map(static fn($r) => $r + ['sumber_id' => (int) $r['sumber_id']], $rows);
    }

    /** @param int[] $sasaranIds */
    private function kandidatIndikatorRpjmd(array $sasaranIds): array
    {
        if (empty($sasaranIds)) {
            return [];
        }

        $rows = $this->db->table('rpjmd_indikator_sasaran ris')
            ->select("
                ris.id                                      AS sumber_id,
                ris.sasaran_id                              AS sumber_sasaran_id,
                ris.indikator_sasaran                       AS indikator,
                ris.definisi_op                             AS definisi,
                ris.satuan                                  AS satuan,
                COALESCE(sat.satuan, NULLIF(ris.satuan, '')) AS satuan_nama,
                ris.jenis_indikator,
                ris.baseline
            ", false)
            ->join('satuan sat', "ris.satuan REGEXP '^[0-9]+$' AND sat.id = ris.satuan", 'left', false)
            ->whereIn('ris.sasaran_id', $sasaranIds)
            ->orderBy('ris.id', 'ASC')
            ->get()
            ->getResultArray();

        return $this->lengkapiTargetSumber($rows, 'rpjmd_target', 'indikator_sasaran_id', 'target_tahunan');
    }

    /** @param int[] $sasaranIds */
    private function kandidatIndikatorRenstra(array $sasaranIds): array
    {
        if (empty($sasaranIds)) {
            return [];
        }

        $rows = $this->db->table('renstra_indikator_sasaran ris')
            ->select("
                ris.id                                      AS sumber_id,
                ris.renstra_sasaran_id                      AS sumber_sasaran_id,
                ris.indikator_sasaran                       AS indikator,
                NULL                                        AS definisi,
                ris.satuan                                  AS satuan,
                COALESCE(sat.satuan, NULLIF(ris.satuan, '')) AS satuan_nama,
                ris.jenis_indikator,
                ris.baseline
            ", false)
            ->join('satuan sat', "ris.satuan REGEXP '^[0-9]+$' AND sat.id = ris.satuan", 'left', false)
            ->whereIn('ris.renstra_sasaran_id', $sasaranIds)
            ->orderBy('ris.id', 'ASC')
            ->get()
            ->getResultArray();

        return $this->lengkapiTargetSumber($rows, 'renstra_target', 'renstra_indikator_id', 'target');
    }

    /** Tempelkan target per tahun ke tiap baris indikator sumber. */
    private function lengkapiTargetSumber(array $rows, string $tabel, string $kolomFk, string $kolomNilai): array
    {
        if (empty($rows)) {
            return [];
        }

        $ids = array_map('intval', array_column($rows, 'sumber_id'));

        $targetRows = $this->db->table($tabel)
            ->select($kolomFk . ', tahun, ' . $kolomNilai . ' AS nilai')
            ->whereIn($kolomFk, $ids)
            ->orderBy('tahun', 'ASC')
            ->get()
            ->getResultArray();

        $targetMap = [];
        foreach ($targetRows as $t) {
            $targetMap[(int) $t[$kolomFk]][(int) $t['tahun']] = $t['nilai'];
        }

        foreach ($rows as &$row) {
            $row['sumber_id']         = (int) $row['sumber_id'];
            $row['sumber_sasaran_id'] = (int) $row['sumber_sasaran_id'];
            $row['target']            = $targetMap[$row['sumber_id']] ?? [];
        }
        unset($row);

        return $rows;
    }

    /**
     * Peta IKU yang sudah ada pada satu lingkup + periode, dikunci teks
     * ternormalkan supaya bisa dipakai mendeteksi duplikat.
     *
     * @return array<string, array{id: int, indikator: array<string, int>}>
     */
    private function petaIkuTerpasang(?int $opdId, int $tahunMulai, int $tahunAkhir): array
    {
        $builder = $this->db->table('iku_sasaran')
            ->select('id, sasaran')
            ->where('tahun_mulai', $tahunMulai)
            ->where('tahun_akhir', $tahunAkhir);

        if ($opdId === null) {
            $builder->where('opd_id IS NULL', null, false);
        } else {
            $builder->where('opd_id', $opdId);
        }

        $sasaranRows = $builder->get()->getResultArray();
        if (empty($sasaranRows)) {
            return [];
        }

        $indikatorRows = $this->db->table('iku_indikator')
            ->select('id, iku_sasaran_id, indikator')
            ->whereIn('iku_sasaran_id', array_column($sasaranRows, 'id'))
            ->get()
            ->getResultArray();

        $indikatorPerSasaran = [];
        foreach ($indikatorRows as $ind) {
            $indikatorPerSasaran[(int) $ind['iku_sasaran_id']][$this->normalkanTeks($ind['indikator'])] = (int) $ind['id'];
        }

        $peta = [];
        foreach ($sasaranRows as $sasaran) {
            $kunci = $this->normalkanTeks($sasaran['sasaran']);

            // Kalau ada sasaran IKU dengan teks kembar, yang pertama yang dipakai.
            if (isset($peta[$kunci])) {
                continue;
            }

            $peta[$kunci] = [
                'id'        => (int) $sasaran['id'],
                'indikator' => $indikatorPerSasaran[(int) $sasaran['id']] ?? [],
            ];
        }

        return $peta;
    }

    /** Normalisasi teks untuk pencocokan duplikat: huruf kecil, spasi dirapatkan. */
    private function normalkanTeks(?string $teks): string
    {
        return mb_strtolower(trim(preg_replace('/\s+/u', ' ', (string) $teks)));
    }

    private function urutanBerikutSasaran(?int $opdId, int $tahunMulai, int $tahunAkhir): int
    {
        $builder = $this->db->table('iku_sasaran')
            ->selectMax('urutan')
            ->where('tahun_mulai', $tahunMulai)
            ->where('tahun_akhir', $tahunAkhir);

        if ($opdId === null) {
            $builder->where('opd_id IS NULL', null, false);
        } else {
            $builder->where('opd_id', $opdId);
        }

        return (int) ($builder->get()->getRowArray()['urutan'] ?? 0) + 1;
    }

    private function urutanBerikutIndikator(int $sasaranId): int
    {
        $row = $this->db->table('iku_indikator')
            ->selectMax('urutan')
            ->where('iku_sasaran_id', $sasaranId)
            ->get()
            ->getRowArray();

        return (int) ($row['urutan'] ?? 0) + 1;
    }

    /* =========================================================
     * SIMPAN / UBAH / HAPUS
     * =======================================================*/

    /**
     * Simpan satu sasaran IKU beserta seluruh indikator, target, dan programnya.
     *
     * @param array{
     *     opd_id: int|null, sasaran: string, tahun_mulai: int, tahun_akhir: int,
     *     urutan?: int, indikator: array<int, array<string, mixed>>
     * } $data
     *
     * @return int id sasaran yang dibuat
     */
    public function createComplete(array $data): int
    {
        $db = $this->db;
        $db->transBegin();

        try {
            $sasaranId = $this->insertSasaran($data);

            foreach (array_values($data['indikator'] ?? []) as $urutan => $indikator) {
                $this->insertIndikator($sasaranId, $indikator, $urutan);
            }

            if ($db->transStatus() === false) {
                throw new \RuntimeException('Transaksi penyimpanan IKU gagal.');
            }

            $db->transCommit();

            return $sasaranId;
        } catch (Throwable $e) {
            $db->transRollback();
            throw $e;
        }
    }

    /**
     * Perbarui satu sasaran IKU beserta isinya.
     *
     * Indikator yang punya `id` diperbarui, yang tanpa `id` ditambahkan, dan yang
     * hilang dari kiriman form dihapus (beserta target & programnya lewat FK
     * ON DELETE CASCADE).
     */
    public function updateComplete(int $sasaranId, array $data): bool
    {
        $db = $this->db;
        $db->transBegin();

        try {
            $sasaranData = [
                'sasaran'     => $data['sasaran'],
                'tahun_mulai' => (int) $data['tahun_mulai'],
                'tahun_akhir' => (int) $data['tahun_akhir'],
                'updated_at'  => date('Y-m-d H:i:s'),
            ];

            // opd_id hanya ikut diubah kalau memang dikirim (admin_kab bisa
            // memindah IKU antar-OPD; admin_opd tidak pernah mengirimnya).
            if (array_key_exists('opd_id', $data)) {
                $sasaranData['opd_id'] = $data['opd_id'] !== null ? (int) $data['opd_id'] : null;
            }

            $db->table('iku_sasaran')->where('id', $sasaranId)->update($sasaranData);

            $idLama = array_column(
                $db->table('iku_indikator')
                    ->select('id')
                    ->where('iku_sasaran_id', $sasaranId)
                    ->get()
                    ->getResultArray(),
                'id'
            );
            $idLama = array_map('intval', $idLama);

            $idDipakai = [];

            foreach (array_values($data['indikator'] ?? []) as $urutan => $indikator) {
                $indikatorId = (int) ($indikator['id'] ?? 0);

                if ($indikatorId > 0 && in_array($indikatorId, $idLama, true)) {
                    $this->updateIndikator($indikatorId, $indikator, $urutan);
                    $idDipakai[] = $indikatorId;
                    continue;
                }

                $idDipakai[] = $this->insertIndikator($sasaranId, $indikator, $urutan);
            }

            $idDihapus = array_diff($idLama, $idDipakai);
            if (!empty($idDihapus)) {
                $db->table('iku_indikator')->whereIn('id', $idDihapus)->delete();
            }

            if ($db->transStatus() === false) {
                throw new \RuntimeException('Transaksi perubahan IKU gagal.');
            }

            $db->transCommit();

            return true;
        } catch (Throwable $e) {
            $db->transRollback();
            throw $e;
        }
    }

    /** Hapus satu sasaran IKU (indikator/target/program ikut lewat FK CASCADE). */
    public function deleteComplete(int $sasaranId): bool
    {
        return (bool) $this->db->table('iku_sasaran')->where('id', $sasaranId)->delete();
    }

    /** Hapus satu indikator IKU saja. */
    public function deleteIndikator(int $indikatorId): bool
    {
        return (bool) $this->db->table('iku_indikator')->where('id', $indikatorId)->delete();
    }

    /**
     * Toggle status satu indikator: draft <-> selesai.
     *
     * @return string|null status baru, atau null bila indikator tidak ada
     */
    public function toggleStatusIndikator(int $indikatorId): ?string
    {
        $row = $this->db->table('iku_indikator')
            ->select('status')
            ->where('id', $indikatorId)
            ->get()
            ->getRowArray();

        if (!$row) {
            return null;
        }

        $statusBaru = strtolower(trim((string) $row['status'])) === 'selesai' ? 'draft' : 'selesai';

        $this->db->table('iku_indikator')
            ->where('id', $indikatorId)
            ->update(['status' => $statusBaru, 'updated_at' => date('Y-m-d H:i:s')]);

        return $statusBaru;
    }

    /** Ubah status seluruh indikator dalam satu sasaran sekaligus. */
    public function setStatusSasaran(int $sasaranId, string $status): bool
    {
        $status = strtolower(trim($status)) === 'selesai' ? 'selesai' : 'draft';

        return (bool) $this->db->table('iku_indikator')
            ->where('iku_sasaran_id', $sasaranId)
            ->update(['status' => $status, 'updated_at' => date('Y-m-d H:i:s')]);
    }

    /* =========================================================
     * HELPER PRIVAT
     * =======================================================*/

    /**
     * Batasi builder ke lingkup pemilik data.
     *
     * 'kabupaten' -> opd_id IS NULL
     * 'opd'       -> opd_id IS NOT NULL (opsional dipersempit ke satu OPD)
     * 'semua'     -> tanpa batas, kecuali $opdId diisi
     */
    private function applyScope($builder, string $level, ?int $opdId, string $prefix = ''): void
    {
        $kolom = $prefix . 'opd_id';

        if ($level === 'kabupaten') {
            $builder->where($kolom . ' IS NULL', null, false);
            return;
        }

        if ($level === 'opd') {
            if ($opdId !== null) {
                $builder->where($kolom, $opdId);
            } else {
                $builder->where($kolom . ' IS NOT NULL', null, false);
            }
            return;
        }

        if ($opdId !== null) {
            $builder->where($kolom, $opdId);
        }
    }

    /**
     * @param int[] $indikatorIds
     *
     * @return array<int, array<int, string|null>> [indikator_id => [tahun => target]]
     */
    private function getTargetMap(array $indikatorIds): array
    {
        if (empty($indikatorIds)) {
            return [];
        }

        $rows = $this->db->table('iku_target')
            ->select('iku_indikator_id, tahun, target')
            ->whereIn('iku_indikator_id', $indikatorIds)
            ->orderBy('tahun', 'ASC')
            ->get()
            ->getResultArray();

        $map = [];
        foreach ($rows as $row) {
            $map[(int) $row['iku_indikator_id']][(int) $row['tahun']] = $row['target'];
        }

        return $map;
    }

    /**
     * @param int[] $indikatorIds
     *
     * @return array<int, array<int, array{id: int, program: string}>>
     */
    private function getProgramMap(array $indikatorIds): array
    {
        if (empty($indikatorIds)) {
            return [];
        }

        $rows = $this->db->table('iku_program')
            ->select('id, iku_indikator_id, program')
            ->whereIn('iku_indikator_id', $indikatorIds)
            ->orderBy('urutan', 'ASC')
            ->orderBy('id', 'ASC')
            ->get()
            ->getResultArray();

        $map = [];
        foreach ($rows as $row) {
            $map[(int) $row['iku_indikator_id']][] = [
                'id'      => (int) $row['id'],
                'program' => (string) $row['program'],
            ];
        }

        return $map;
    }

    private function insertSasaran(array $data): int
    {
        $this->db->table('iku_sasaran')->insert([
            'opd_id'      => isset($data['opd_id']) && $data['opd_id'] !== null ? (int) $data['opd_id'] : null,
            'sasaran'     => $data['sasaran'],
            'tahun_mulai' => (int) $data['tahun_mulai'],
            'tahun_akhir' => (int) $data['tahun_akhir'],
            'urutan'      => (int) ($data['urutan'] ?? 0),
            'created_at'  => date('Y-m-d H:i:s'),
            'updated_at'  => date('Y-m-d H:i:s'),
        ]);

        return (int) $this->db->insertID();
    }

    private function insertIndikator(int $sasaranId, array $indikator, int $urutan): int
    {
        $this->db->table('iku_indikator')->insert(
            $this->siapkanIndikator($indikator, $urutan) + [
                'iku_sasaran_id' => $sasaranId,
                'created_at'     => date('Y-m-d H:i:s'),
                'updated_at'     => date('Y-m-d H:i:s'),
            ]
        );

        $indikatorId = (int) $this->db->insertID();

        $this->simpanTarget($indikatorId, $indikator['target'] ?? []);
        $this->simpanProgram($indikatorId, $indikator['program'] ?? []);

        return $indikatorId;
    }

    private function updateIndikator(int $indikatorId, array $indikator, int $urutan): void
    {
        $this->db->table('iku_indikator')
            ->where('id', $indikatorId)
            ->update($this->siapkanIndikator($indikator, $urutan) + ['updated_at' => date('Y-m-d H:i:s')]);

        $this->db->table('iku_target')->where('iku_indikator_id', $indikatorId)->delete();
        $this->simpanTarget($indikatorId, $indikator['target'] ?? []);

        $this->db->table('iku_program')->where('iku_indikator_id', $indikatorId)->delete();
        $this->simpanProgram($indikatorId, $indikator['program'] ?? []);
    }

    /**
     * Bersihkan input satu indikator jadi kolom tabel.
     *
     * Semua kolom teks di `iku_indikator` NULLABLE, jadi string kosong sengaja
     * dinormalkan jadi NULL — bukan sebaliknya.
     */
    private function siapkanIndikator(array $indikator, int $urutan): array
    {
        $status = strtolower(trim((string) ($indikator['status'] ?? 'draft')));

        return [
            'indikator'           => trim((string) ($indikator['indikator'] ?? '')),
            'definisi'            => $this->nullJikaKosong($indikator['definisi'] ?? null),
            'rumusan_perhitungan' => $this->nullJikaKosong($indikator['rumusan_perhitungan'] ?? null),
            'satuan'              => $this->nullJikaKosong($indikator['satuan'] ?? null),
            'sumber_data'         => $this->nullJikaKosong($indikator['sumber_data'] ?? null),
            'penanggung_jawab'    => $this->nullJikaKosong($indikator['penanggung_jawab'] ?? null),
            'jenis_indikator'     => $this->nullJikaKosong($indikator['jenis_indikator'] ?? null),
            'baseline'            => $this->nullJikaKosong($indikator['baseline'] ?? null),
            'urutan'              => $urutan,
            'status'              => $status === 'selesai' ? 'selesai' : 'draft',
        ];
    }

    /** @param array<int|string, mixed> $target [tahun => nilai] */
    private function simpanTarget(int $indikatorId, array $target): void
    {
        $baris = [];

        foreach ($target as $tahun => $nilai) {
            $tahun = (int) $tahun;
            if ($tahun <= 0) {
                continue;
            }

            $baris[$tahun] = [
                'iku_indikator_id' => $indikatorId,
                'tahun'            => $tahun,
                'target'           => $this->nullJikaKosong($nilai),
                'created_at'       => date('Y-m-d H:i:s'),
                'updated_at'       => date('Y-m-d H:i:s'),
            ];
        }

        if (!empty($baris)) {
            $this->db->table('iku_target')->insertBatch(array_values($baris));
        }
    }

    /** @param array<int, string> $program */
    private function simpanProgram(int $indikatorId, array $program): void
    {
        $baris = [];

        foreach (array_values($program) as $urutan => $nama) {
            $nama = trim((string) (is_array($nama) ? ($nama['program'] ?? '') : $nama));
            if ($nama === '') {
                continue;
            }

            $baris[] = [
                'iku_indikator_id' => $indikatorId,
                'program'          => $nama,
                'urutan'           => $urutan,
                'created_at'       => date('Y-m-d H:i:s'),
                'updated_at'       => date('Y-m-d H:i:s'),
            ];
        }

        if (!empty($baris)) {
            $this->db->table('iku_program')->insertBatch($baris);
        }
    }

    private function nullJikaKosong($nilai): ?string
    {
        if ($nilai === null) {
            return null;
        }

        $nilai = trim((string) $nilai);

        return $nilai === '' ? null : $nilai;
    }
}
