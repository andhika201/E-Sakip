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
    /**
     * Periode yang bisa dipilih di menu IKU.
     *
     * =====================================================================
     * DIGABUNG DENGAN PERIODE DOKUMEN SUMBERNYA
     *
     * Membaca `iku_sasaran` saja melahirkan telur-dan-ayam: periode yang
     * Renstra-nya sudah ada tetapi IKU-nya belum tidak pernah muncul di
     * dropdown — sehingga pemakai tidak punya jalan memilihnya, dan karena
     * tidak bisa dipilih, IKU-nya tidak pernah lahir.
     *
     * Karena itu periode dari dokumen sumbernya ikut digabung: Renstra untuk
     * lingkup OPD, RPJMD untuk tingkat Kabupaten. Periode yang IKU-nya masih
     * kosong tetap muncul, dan layarnya menawarkan "Sync dari Renstra".
     *
     * Pola yang sama dipakai DokumenVersiTrait::versiPeriodeTersedia().
     *
     * @param bool $sertakanSumber setel false bila memang hanya periode yang
     *                             SUDAH punya IKU yang relevan
     */
    public function getPeriodeOptions(
        string $level = 'semua',
        ?int $opdId = null,
        bool $sertakanSumber = true
    ): array {
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
                'punya_iku'   => true,
            ];
        }

        if ($sertakanSumber) {
            $sumber = $level === 'kabupaten' ? 'rpjmd' : 'renstra';

            foreach ($this->getPeriodeSumber($sumber, $level === 'opd' ? $opdId : null) as $kunci => $p) {
                if (! isset($periodes[$kunci])) {
                    $periodes[$kunci] = $p + ['punya_iku' => false];
                }
            }

            // Terbaru di atas, sama seperti sebelum digabung.
            uasort($periodes, static fn ($a, $b) => $b['tahun_mulai'] <=> $a['tahun_mulai']);
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

        // Baris yang dipensiunkan revisi IKU disembunyikan dari "versi yang
        // sedang berlaku". Ini TIDAK mengubah perilaku lama: `dihentikan_pada`
        // selalu NULL selama fitur revisi belum dipakai, jadi kondisi ini
        // tidak menyaring apa pun pada data yang sudah ada.
        // Kirim 'sertakan_dihentikan' => true untuk melihat riwayat lengkap.
        $sertakanPensiun = ! empty($opt['sertakan_dihentikan']);

        $builder = $this->db->table('iku_sasaran sas')
            ->select('sas.id, sas.opd_id, sas.sasaran, sas.tahun_mulai, sas.tahun_akhir, sas.urutan, o.nama_opd')
            ->join('opd o', 'o.id = sas.opd_id', 'left')
            ->orderBy('sas.opd_id IS NULL', 'DESC', false)
            ->orderBy('o.nama_opd', 'ASC')
            ->orderBy('sas.urutan', 'ASC')
            ->orderBy('sas.id', 'ASC');

        if (! $sertakanPensiun && $this->punyaKolomPensiun('iku_sasaran')) {
            $builder->where('sas.dihentikan_pada IS NULL', null, false);
        }

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

        if (! $sertakanPensiun && $this->punyaKolomPensiun('iku_indikator')) {
            $indikatorBuilder->where('ind.dihentikan_pada IS NULL', null, false);
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
    public function getKandidatSync(
        string $sumber,
        ?int $opdId,
        int $tahunMulai,
        int $tahunAkhir,
        ?int $renstraVersiId = null
    ): array {
        // Versi Renstra dipilih -> baca ARSIPnya. Tanpa itu -> kondisi berjalan.
        $dariVersi = $renstraVersiId !== null && $renstraVersiId > 0;

        if ($dariVersi && $sumber === 'rpjmd') {
            $sasaranRows = $this->kandidatSasaranRpjmdVersi($renstraVersiId);
        } elseif ($dariVersi) {
            $sasaranRows = $this->kandidatSasaranRenstraVersi($renstraVersiId, (int) $opdId);
        } elseif ($sumber === 'rpjmd') {
            $sasaranRows = $this->kandidatSasaranRpjmd($tahunMulai, $tahunAkhir);
        } else {
            $sasaranRows = $this->kandidatSasaranRenstra((int) $opdId, $tahunMulai, $tahunAkhir);
        }

        if (empty($sasaranRows)) {
            return [];
        }

        if ($dariVersi && $sumber === 'rpjmd') {
            $indikatorRows = $this->kandidatIndikatorRpjmdVersi(array_column($sasaranRows, 'sumber_id'));
        } elseif ($dariVersi) {
            $indikatorRows = $this->kandidatIndikatorRenstraVersi(array_column($sasaranRows, 'sumber_id'));
        } elseif ($sumber === 'rpjmd') {
            $indikatorRows = $this->kandidatIndikatorRpjmd(array_column($sasaranRows, 'sumber_id'));
        } else {
            $indikatorRows = $this->kandidatIndikatorRenstra(array_column($sasaranRows, 'sumber_id'));
        }

        $indikatorPerSasaran = [];
        foreach ($indikatorRows as $ind) {
            $indikatorPerSasaran[$ind['sumber_sasaran_id']][] = $ind;
        }

        // Peta IKU yang sudah ada pada lingkup & periode yang sama, untuk
        // menandai duplikat.
        $ikuTerpasang = $this->petaIkuTerpasang($opdId, $tahunMulai, $tahunAkhir);

        // Indeks silsilah: id indikator RENSTRA berjalan -> baris IKU yang
        // berasal darinya. Dipakai mencocokkan sebelum teks (lihat
        // bandingkanIndikator).
        $petaSilsilah = [];
        // Indeks silsilah SASARAN: id sasaran Renstra berjalan -> sasaran IKU
        // yang lahir darinya. Sebelumnya sasaran hanya dicocokkan lewat teks,
        // sehingga merapikan redaksi satu sasaran membuat sync menganggapnya
        // sasaran baru — lalu menyalin ulang seluruh indikator di bawahnya.
        $petaSilsilahSasaran = [];

        foreach ($ikuTerpasang as $sas) {
            if (! empty($sas['source_sasaran_id'])) {
                $petaSilsilahSasaran[(int) $sas['source_sasaran_id']] = $sas;
            }

            foreach ($sas['indikator'] as $ind) {
                if (! empty($ind['source_indikator_id'])) {
                    $petaSilsilah[(int) $ind['source_indikator_id']] = $ind;
                }
            }
        }

        $tahun    = range($tahunMulai, $tahunAkhir);
        $terpakai = [];
        $hasil    = [];

        foreach ($sasaranRows as $sasaran) {
            $kunciSasaran = $this->normalkanTeks($sasaran['sasaran']);
            // Silsilah dulu, teks belakangan — sama seperti indikator.
            $sasaranLiveId = (int) ($sasaran['sumber_live_id'] ?? 0);
            $ikuSasaran    = $petaSilsilahSasaran[$sasaranLiveId]
                ?? ($ikuTerpasang[$kunciSasaran] ?? null);

            $daftarIndikator = [];

            foreach ($indikatorPerSasaran[$sasaran['sumber_id']] ?? [] as $ind) {
                $liveId  = ! empty($ind['sumber_live_id']) ? (int) $ind['sumber_live_id'] : 0;
                $padanan = $petaSilsilah[$liveId] ?? null;

                if ($padanan === null && $ikuSasaran !== null) {
                    $padanan = $ikuSasaran['indikator'][$this->normalkanTeks($ind['indikator'])] ?? null;
                }

                $banding = $this->bandingkanIndikator($ind, $padanan, $tahun);

                $ind['sudah_ada'] = $banding['status'] !== 'baru';
                $ind['banding']   = $banding['status'];
                $ind['selisih']   = $banding['selisih'];
                $ind['iku_id']    = $banding['iku_id'];

                if ($banding['iku_id'] !== null) {
                    $terpakai[] = $banding['iku_id'];
                }

                $daftarIndikator[] = $ind;
            }

            $sasaran['iku_sasaran_id'] = $ikuSasaran['id'] ?? null;
            $sasaran['sudah_ada']      = $ikuSasaran !== null;
            $sasaran['indikator']      = $daftarIndikator;
            $sasaran['jumlah_baru']    = count(array_filter($daftarIndikator,
                static fn ($i) => $i['banding'] === 'baru'));
            $sasaran['jumlah_berubah'] = count(array_filter($daftarIndikator,
                static fn ($i) => $i['banding'] === 'berubah'));

            $hasil[] = $sasaran;
        }

        // Disimpan sebagai baris semu di ujung daftar supaya pemanggil lama
        // yang hanya memakai `$kandidat` tidak perlu berubah, sementara yang
        // butuh daftar "tidak ada di sumber" bisa membacanya lewat
        // ikuTanpaPadanan() secara terpisah.
        $this->ikuTanpaPadananTerakhir = $this->ikuTanpaPadanan($ikuTerpasang, $terpakai);

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
     * @param array<int, int[]> $pilihan  [id_sasaran_sumber => [id_indikator_sumber, ...]] yang DITAMBAHKAN
     * @param array<int, int[]> $perbarui  yang isinya DIAMBIL ULANG dari sumber (menimpa nilai IKU)
     *
     * @return array{sasaran_baru: int, indikator_baru: int, target: int, dilewati: int, diperbarui: int}
     */
    public function importSync(
        string $sumber,
        ?int $opdId,
        array $pilihan,
        int $tahunMulai,
        int $tahunAkhir,
        ?int $renstraVersiId = null,
        array $perbarui = []
    ): array {
        $kandidat = $this->getKandidatSync($sumber, $opdId, $tahunMulai, $tahunAkhir, $renstraVersiId);
        $stat     = ['sasaran_baru' => 0, 'indikator_baru' => 0, 'target' => 0,
                     'dilewati' => 0, 'diperbarui' => 0, 'ditautkan' => 0];

        $db = $this->db;
        $db->transBegin();

        try {
            // Sebelum apa pun disalin: pasang silsilah pada baris IKU lama yang
            // ketemu padanannya lewat TEKS tetapi belum menyimpan asalnya.
            //
            // Tanpa langkah ini pencocokan teks tidak pernah menjadi tautan
            // permanen: begitu redaksi indikator dirapikan sedikit saja, sync
            // berikutnya kehilangan jejaknya dan menyalin ulang indikator yang
            // sebenarnya sudah ada — inilah asal data kembar yang selama ini
            // muncul. Hanya kolom yang MASIH KOSONG yang diisi; tidak ada isi
            // yang ditimpa, sehingga aman dijalankan berkali-kali.
            $stat['ditautkan'] = $this->tautkanSilsilah($kandidat, $sumber, $renstraVersiId);

            foreach ($kandidat as $sasaran) {
                $idSumber = (int) $sasaran['sumber_id'];

                if (empty($pilihan[$idSumber]) && empty($perbarui[$idSumber])) {
                    continue;
                }

                $dipilih   = array_map('intval', (array) ($pilihan[$idSumber] ?? []));
                $diperbaru = array_map('intval', (array) ($perbarui[$idSumber] ?? []));

                // ---- Ambil ulang isi indikator yang sudah ada di IKU --------
                // Dulu keranjang ini DIBUANG di jalur ini (hanya jalur draft
                // revisi yang memakainya), sehingga mencentang "ambil
                // perubahan" melapor sukses tanpa menulis apa pun.
                foreach ($sasaran['indikator'] as $ind) {
                    if (! in_array((int) $ind['sumber_id'], $diperbaru, true)) {
                        continue;
                    }

                    $ikuId = (int) ($ind['iku_id'] ?? 0);

                    if ($ikuId <= 0) {
                        continue;
                    }

                    $target = [];
                    foreach ($ind['target'] as $tahun => $nilai) {
                        if ($tahun >= $tahunMulai && $tahun <= $tahunAkhir) {
                            $target[$tahun] = $nilai;
                        }
                    }

                    $this->timpaDariSumber($ikuId, $ind, $target);

                    $stat['diperbarui']++;
                    $stat['target'] += count($target);
                }

                $indikatorDiimpor = array_values(array_filter(
                    $sasaran['indikator'],
                    static fn($ind) => in_array((int) $ind['sumber_id'], $dipilih, true)
                ));

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
                        // Jejak asal. Tanpa ini, sesudah sync tidak ada yang
                        // bisa menjawab "IKU ini datang dari Renstra yang mana".
                        'source_type'       => $sumber,
                        'source_version_id' => $renstraVersiId,
                        'source_sasaran_id' => $sasaran['sumber_live_id'] ?? null,
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
                        // `source_indikator_id` menunjuk id BERJALAN, bukan id
                        // arsip: LAKIP menautkan realisasinya lewat id berjalan.
                        'source_type'        => $sumber,
                        'source_version_id'  => $renstraVersiId,
                        'source_indikator_id' => $ind['sumber_live_id'] ?? null,
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
    /* =========================================================
     * SUMBER SYNC: ARSIP SEBUAH VERSI RENSTRA
     *
     * =====================================================================
     * MENGAPA IKU BOLEH MEMILIH VERSI
     *
     * IKU bukan salinan Renstra, melainkan PILIHAN indikator utama yang
     * diambil darinya lalu hidup sendiri. Karena itu ia tidak ikut berubah
     * ketika Renstra berganti versi — dan justru itu yang dikehendaki.
     *
     * Konsekuensinya: penyusun harus bisa menyebut Renstra versi MANA yang
     * jadi titik tolak. Tanpa itu, "IKU ini berasal dari mana" hanya bisa
     * dijawab dengan menebak.
     *
     * =====================================================================
     * DUA ID YANG BERBEDA, DAN JANGAN TERTUKAR
     *
     * `sumber_id`      -> id baris ARSIP; dipakai kotak centang di form sync
     * `sumber_live_id` -> id baris BERJALAN yang dibekukan arsip itu; inilah
     *                     yang disimpan sebagai jejak, sebab LAKIP menautkan
     *                     realisasinya lewat id berjalan.
     *
     * Untuk baris arsip yang disusun tangan di form versi dan belum pernah
     * diterapkan, `sumber_live_id` memang NULL — dan itu jujur: baris itu
     * belum punya padanan di data berjalan.
     * =======================================================*/

    /** @return array<int,array<string,mixed>> */
    /** Sasaran dari ARSIP sebuah versi RPJMD (kembaran kandidatSasaranRenstraVersi). */
    private function kandidatSasaranRpjmdVersi(int $versiId): array
    {
        $rows = $this->db->table('rpjmd_versi_sasaran rvs')
            ->select('rvs.id AS sumber_id, rvs.source_sasaran_id AS sumber_live_id,
                      rvs.sasaran_rpjmd AS sasaran, rvt.tujuan_rpjmd AS induk')
            ->join('rpjmd_versi_tujuan rvt', 'rvt.id = rvs.versi_tujuan_id', 'left')
            ->where('rvs.version_id', $versiId)
            ->orderBy('rvt.urutan', 'ASC')->orderBy('rvs.urutan', 'ASC')->orderBy('rvs.id', 'ASC')
            ->get()->getResultArray();

        return array_map(static fn ($r) => $r + [
            'sumber_id'      => (int) $r['sumber_id'],
            'sumber_live_id' => $r['sumber_live_id'] !== null ? (int) $r['sumber_live_id'] : null,
            // Arsip tidak menyimpan status pengerjaan; versi yang ditetapkan
            // memang tidak lagi dikerjakan.
            'status'         => 'selesai',
        ], $rows);
    }

    /** @param int[] $sasaranIds @return array<int,array<string,mixed>> */
    private function kandidatIndikatorRpjmdVersi(array $sasaranIds): array
    {
        if ($sasaranIds === []) {
            return [];
        }

        $rows = $this->db->table('rpjmd_versi_indikator_sasaran rvi')
            ->select("
                rvi.id                     AS sumber_id,
                rvi.source_indikator_id    AS sumber_live_id,
                rvi.versi_sasaran_id       AS sumber_sasaran_id,
                rvi.indikator_sasaran      AS indikator,
                rvi.definisi_op            AS definisi,
                rvi.satuan                 AS satuan,
                COALESCE(rvi.satuan_nama, NULLIF(rvi.satuan, '')) AS satuan_nama,
                rvi.jenis_indikator,
                rvi.baseline
            ", false)
            ->whereIn('rvi.versi_sasaran_id', $sasaranIds)
            ->orderBy('rvi.urutan', 'ASC')->orderBy('rvi.id', 'ASC')
            ->get()->getResultArray();

        foreach ($rows as &$r) {
            $r['sumber_live_id'] = $r['sumber_live_id'] !== null ? (int) $r['sumber_live_id'] : null;
        }
        unset($r);

        return $this->lengkapiTargetSumber($rows, 'rpjmd_versi_target', 'versi_indikator_id', 'target_tahunan');
    }

    private function kandidatSasaranRenstraVersi(int $versiId, int $opdId): array
    {
        $rows = $this->db->table('renstra_versi_sasaran rvs')
            ->select('rvs.id AS sumber_id, rvs.source_sasaran_id AS sumber_live_id,
                      rvs.sasaran, rvt.tujuan AS induk')
            ->join('renstra_versi_tujuan rvt', 'rvt.id = rvs.versi_tujuan_id', 'left')
            ->where('rvs.version_id', $versiId)
            ->where('rvs.opd_id', $opdId)
            ->orderBy('rvt.urutan', 'ASC')->orderBy('rvs.urutan', 'ASC')->orderBy('rvs.id', 'ASC')
            ->get()->getResultArray();

        return array_map(static fn ($r) => $r + [
            'sumber_id'     => (int) $r['sumber_id'],
            'sumber_live_id' => $r['sumber_live_id'] !== null ? (int) $r['sumber_live_id'] : null,
            // Arsip tidak menyimpan status pengerjaan; versi yang ditetapkan
            // memang tidak lagi dikerjakan.
            'status'        => 'selesai',
        ], $rows);
    }

    /** @param int[] $sasaranIds @return array<int,array<string,mixed>> */
    private function kandidatIndikatorRenstraVersi(array $sasaranIds): array
    {
        if ($sasaranIds === []) {
            return [];
        }

        $rows = $this->db->table('renstra_versi_indikator_sasaran rvi')
            ->select("
                rvi.id                     AS sumber_id,
                rvi.source_indikator_id    AS sumber_live_id,
                rvi.versi_sasaran_id       AS sumber_sasaran_id,
                rvi.indikator_sasaran      AS indikator,
                NULL                       AS definisi,
                rvi.satuan                 AS satuan,
                COALESCE(rvi.satuan_nama, NULLIF(rvi.satuan, '')) AS satuan_nama,
                rvi.jenis_indikator,
                rvi.baseline
            ", false)
            ->whereIn('rvi.versi_sasaran_id', $sasaranIds)
            ->orderBy('rvi.urutan', 'ASC')->orderBy('rvi.id', 'ASC')
            ->get()->getResultArray();

        foreach ($rows as &$r) {
            $r['sumber_live_id'] = $r['sumber_live_id'] !== null ? (int) $r['sumber_live_id'] : null;
        }
        unset($r);

        return $this->lengkapiTargetSumber($rows, 'renstra_versi_target', 'versi_indikator_id', 'target');
    }

    /**
     * Versi Renstra yang boleh dijadikan sumber sync.
     *
     * Hanya yang SUDAH DITETAPKAN dan BERISI. Draft belum resmi, dan versi
     * berarsip kosong hanya akan menyodorkan daftar kandidat kosong yang
     * tampak seperti Renstra-nya hilang.
     *
     * @return array<int,array<string,mixed>>
     */
    /**
     * Versi RPJMD yang boleh dijadikan sumber sync IKU Kabupaten.
     *
     * Kembaran versiRenstraTersedia() untuk lingkup kabupaten: hanya versi
     * yang SUDAH DITETAPKAN dan BERISI. RPJMD tidak punya opd_key, jadi
     * lingkupnya kabupaten (0).
     *
     * @return array<int,array<string,mixed>>
     */
    public function versiRpjmdTersedia(int $tahunMulai, int $tahunAkhir): array
    {
        if (! $this->db->tableExists('dokumen_versi') || ! $this->db->tableExists('rpjmd_versi_sasaran')) {
            return [];
        }

        return $this->db->table('dokumen_versi d')
            ->select('d.id, d.version_no, d.label, d.effective_from, d.effective_to,
                      COUNT(rvs.id) AS jumlah_sasaran')
            ->join('rpjmd_versi_sasaran rvs', 'rvs.version_id = d.id', 'inner')
            ->where('d.modul', 'rpjmd')
            ->where('d.periode_mulai', $tahunMulai)
            ->where('d.periode_akhir', $tahunAkhir)
            ->where('d.status', 'published')
            ->groupBy('d.id')
            ->orderBy('d.version_no', 'DESC')
            ->get()->getResultArray();
    }

    public function versiRenstraTersedia(int $opdId, int $tahunMulai, int $tahunAkhir): array
    {
        if (! $this->db->tableExists('dokumen_versi') || ! $this->db->tableExists('renstra_versi_sasaran')) {
            return [];
        }

        $rows = $this->db->table('dokumen_versi d')
            ->select('d.id, d.version_no, d.label, d.effective_from, d.effective_to,
                      COUNT(rvs.id) AS jumlah_sasaran')
            ->join('renstra_versi_sasaran rvs', 'rvs.version_id = d.id', 'inner')
            ->where('d.modul', 'renstra')
            ->where('d.opd_key', $opdId)
            ->where('d.periode_mulai', $tahunMulai)
            ->where('d.periode_akhir', $tahunAkhir)
            ->where('d.status', 'published')
            ->groupBy('d.id')
            ->orderBy('d.version_no', 'DESC')
            ->get()->getResultArray();

        // =============================================================
        // LABEL SIAP TAMPIL — DISUSUN SEKALI DI SINI
        //
        // `label` yang tersimpan SUDAH memuat nomor versinya ("V2 — RENSTRA
        // OPD #20 2025-2029"). Layar yang menambahkan "V{n} — " sendiri karena
        // itu mencetak "V2 — V2 — RENSTRA ...".
        //
        // Diperbaiki di sini, bukan di tiap layar: dua layar memakai daftar
        // yang sama, dan menambalnya satu per satu berarti cacat yang sama
        // bisa hidup lagi di layar ketiga.
        //
        // Prefiks tetap ditambahkan bila label memang belum memuatnya —
        // label bisa saja diketik operator tanpa nomor versi.
        // =============================================================
        foreach ($rows as &$r) {
            $label  = trim((string) $r['label']);
            $awalan = 'V' . (int) $r['version_no'];

            $r['label_tampil'] = ($label === '' || stripos($label, $awalan) !== 0)
                ? trim($awalan . ' — ' . $label, " —")
                : $label;
        }
        unset($r);

        return $rows;
    }

    private function kandidatSasaranRenstra(int $opdId, int $tahunMulai, int $tahunAkhir): array
    {
        $rows = $this->db->table('renstra_sasaran rs')
            ->select('rs.id AS sumber_id, rs.id AS sumber_live_id, rs.sasaran, rs.status, rtuj.tujuan AS induk')
            ->join('renstra_tujuan rtuj', 'rtuj.id = rs.renstra_tujuan_id', 'left')
            ->where('rs.opd_id', $opdId)
            ->where('rs.tahun_mulai', $tahunMulai)
            ->where('rs.tahun_akhir', $tahunAkhir)
            // Baris yang sudah dipensiunkan versi terbaru tidak lagi bagian
            // Renstra; menawarkannya untuk disalin ke IKU berarti menghidupkan
            // kembali sesuatu yang sudah dinyatakan berhenti.
            ->where('rs.dihentikan_pada IS NULL', null, false)
            ->orderBy('rs.id', 'ASC')
            ->get()
            ->getResultArray();

        return array_map(static fn($r) => $r + [
            'sumber_id'      => (int) $r['sumber_id'],
            'sumber_live_id' => (int) $r['sumber_live_id'],
        ], $rows);
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
                ris.id                                      AS sumber_live_id,
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
            ->where('ris.dihentikan_pada IS NULL', null, false)
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
    /**
     * Indikator IKU yang tidak punya padanan pada sumber terakhir yang dibaca.
     *
     * Disimpan sebagai keadaan model, bukan diselipkan ke dalam daftar
     * kandidat: menyelipkannya berarti setiap pemanggil lama harus tahu cara
     * membedakan baris semu dari baris sungguhan.
     *
     * @var array<int,array<string,mixed>>
     */
    private array $ikuTanpaPadananTerakhir = [];

    /** @return array<int,array<string,mixed>> */
    public function ikuTanpaPadananSumber(): array
    {
        return $this->ikuTanpaPadananTerakhir;
    }

    /**
     * Sasaran IKU dengan teks yang sama pada periode & OPD yang sama.
     *
     * =====================================================================
     * MENGAPA PERLU DIPERIKSA
     *
     * `importSync()` mencocokkan sasaran lewat teks dan memakai ulang yang
     * sudah ada, tetapi `createComplete()` — jalur "Tambah IKU" manual —
     * SELALU menyisipkan baris baru. Jadi mengetik sasaran yang sama persis
     * setelah sync akan melahirkan dua sasaran kembar dengan indikator
     * terbelah di antara keduanya.
     *
     * Kembarnya tidak melanggar apa pun di basis data dan tidak memunculkan
     * galat; ia hanya membuat tabel IKU menampilkan sasaran yang sama dua kali,
     * dan sync berikutnya akan memakai yang pertama saja.
     *
     * @return int|null id sasaran kembar, atau null bila tidak ada
     */
    public function sasaranKembar(?int $opdId, string $sasaran, int $tahunMulai, int $tahunAkhir, ?int $kecuali = null): ?int
    {
        $kunci = $this->normalkanTeks($sasaran);

        if ($kunci === '') {
            return null;
        }

        $b = $this->db->table('iku_sasaran')
            ->select('id, sasaran')
            ->where('tahun_mulai', $tahunMulai)
            ->where('tahun_akhir', $tahunAkhir);

        $opdId === null ? $b->where('opd_id IS NULL', null, false) : $b->where('opd_id', $opdId);

        if ($kecuali !== null) {
            $b->where('id !=', $kecuali);
        }

        if ($this->punyaKolomPensiun('iku_sasaran')) {
            $b->where('dihentikan_pada IS NULL', null, false);
        }

        foreach ($b->get()->getResultArray() as $r) {
            if ($this->normalkanTeks($r['sasaran']) === $kunci) {
                return (int) $r['id'];
            }
        }

        return null;
    }

    private const SATUAN_JOIN_IKU   = "ind.satuan REGEXP '^[0-9]+$' AND sat.id = ind.satuan";
    private const SATUAN_SELECT_IKU = "COALESCE(sat.satuan, NULLIF(ind.satuan, ''))";

    private function petaIkuTerpasang(?int $opdId, int $tahunMulai, int $tahunAkhir): array
    {
        $builder = $this->db->table('iku_sasaran')
            // `source_sasaran_id` ikut dibaca supaya sasaran bisa dicocokkan
            // lewat silsilah, bukan hanya teks — lihat catatan di
            // bandingkanIndikator(), alasannya sama persis untuk sasaran:
            // begitu redaksi sasaran dirapikan, pencocokan teks gagal dan
            // SELURUH sasaran beserta indikatornya tersalin ulang.
            ->select('id, sasaran, source_sasaran_id')
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

        // Indikator yang sudah dipensiunkan SENGAJA tidak dihitung "sudah
        // terpasang". Kalau ikut dihitung, indikator yang pernah dihentikan
        // akan memblokir impor ulang indikator bernama sama dari RPJMD/Renstra
        // selamanya — padahal menghidupkannya kembali justru hal yang wajar.
        $indikatorBuilder = $this->db->table('iku_indikator ind')
            ->select('ind.id, ind.iku_sasaran_id, ind.indikator, ind.satuan, ind.baseline,
                      ind.jenis_indikator, ind.source_type, ind.source_indikator_id, '
                      . self::SATUAN_SELECT_IKU . ' AS satuan_nama', false)
            ->join('satuan sat', self::SATUAN_JOIN_IKU, 'left', false)
            ->whereIn('ind.iku_sasaran_id', array_column($sasaranRows, 'id'));

        if ($this->punyaKolomPensiun('iku_indikator')) {
            $indikatorBuilder->where('ind.dihentikan_pada IS NULL', null, false);
        }

        $indikatorRows = $indikatorBuilder->get()->getResultArray();

        // Target per tahun ikut diambil sekali untuk seluruh indikator: tanpa
        // ini, membandingkan target berarti satu query per baris.
        $target = [];

        if ($indikatorRows !== []) {
            foreach ($this->db->table('iku_target')
                ->select('iku_indikator_id, tahun, target')
                ->whereIn('iku_indikator_id', array_column($indikatorRows, 'id'))
                ->get()->getResultArray() as $t) {
                $target[(int) $t['iku_indikator_id']][(int) $t['tahun']] = $t['target'];
            }
        }

        $indikatorPerSasaran = [];

        foreach ($indikatorRows as $ind) {
            $ind['target'] = $target[(int) $ind['id']] ?? [];
            $indikatorPerSasaran[(int) $ind['iku_sasaran_id']][$this->normalkanTeks($ind['indikator'])] = $ind;
        }

        $peta = [];
        foreach ($sasaranRows as $sasaran) {
            $kunci = $this->normalkanTeks($sasaran['sasaran']);

            // Kalau ada sasaran IKU dengan teks kembar, yang pertama yang dipakai.
            if (isset($peta[$kunci])) {
                continue;
            }

            $peta[$kunci] = [
                'id'                => (int) $sasaran['id'],
                'sasaran'           => $sasaran['sasaran'],
                'source_sasaran_id' => $sasaran['source_sasaran_id'] ?? null,
                'indikator'         => $indikatorPerSasaran[(int) $sasaran['id']] ?? [],
            ];
        }

        return $peta;
    }

    /**
     * Bandingkan satu indikator kandidat dengan padanannya di IKU berjalan.
     *
     * =====================================================================
     * MENCOCOKKAN LEWAT SILSILAH DULU, BARU TEKS
     *
     * Kalau indikator IKU menyimpan `source_indikator_id`, itulah padanan yang
     * pasti — ia menunjuk baris Renstra yang menjadi asalnya. Mencocokkan lewat
     * teks baru dipakai bila silsilahnya tidak ada, misalnya indikator yang
     * dulu diketik manual.
     *
     * Bedanya nyata: begitu redaksi indikator dirapikan di salah satu sisi,
     * pencocokan teks langsung gagal dan indikator yang sama akan tampak
     * sebagai "baru" — lalu tersalin dua kali.
     *
     * @return array{status:string, iku_id:?int, selisih:array<string,array{iku:?string,sumber:?string}>}
     */
    private function bandingkanIndikator(array $kandidat, ?array $padanan, array $tahun): array
    {
        if ($padanan === null) {
            return ['status' => 'baru', 'iku_id' => null, 'selisih' => []];
        }

        $selisih = [];

        $banding = static function (string $nama, $iku, $sumber) use (&$selisih): void {
            $a = trim((string) $iku);
            $b = trim((string) $sumber);

            if ($a !== $b) {
                $selisih[$nama] = ['iku' => $a, 'sumber' => $b];
            }
        };

        // `satuan_nama` dibandingkan, bukan `satuan`: yang satu id, yang lain
        // teks, dan indikator yang sama bisa menyimpan keduanya secara berbeda
        // tanpa benar-benar berbeda.
        $banding('satuan', $padanan['satuan_nama'] ?? $padanan['satuan'] ?? null,
            $kandidat['satuan_nama'] ?? $kandidat['satuan'] ?? null);
        $banding('baseline', $padanan['baseline'] ?? null, $kandidat['baseline'] ?? null);
        $banding('jenis_indikator', $padanan['jenis_indikator'] ?? null, $kandidat['jenis_indikator'] ?? null);

        foreach ($tahun as $th) {
            $banding('target ' . $th,
                $padanan['target'][$th] ?? null,
                $kandidat['target'][$th] ?? null);
        }

        return [
            'status'  => $selisih === [] ? 'sama' : 'berubah',
            'iku_id'  => (int) $padanan['id'],
            'selisih' => $selisih,
        ];
    }

    /**
     * Indikator IKU yang TIDAK punya padanan pada sumber terpilih.
     *
     * Ini bukan daftar kesalahan. IKU memang boleh memuat indikator yang tidak
     * ada di Renstra — itulah gunanya ia dokumen tersendiri. Tetapi penyusun
     * berhak tahu mana saja yang berdiri sendiri, terutama bila sumbernya
     * baru saja diganti ke versi lain.
     *
     * @return array<int,array<string,mixed>>
     */
    private function ikuTanpaPadanan(array $peta, array $terpakai): array
    {
        $keluar = [];

        foreach ($peta as $sasaran) {
            foreach ($sasaran['indikator'] as $ind) {
                if (! in_array((int) $ind['id'], $terpakai, true)) {
                    $keluar[] = [
                        'iku_id'      => (int) $ind['id'],
                        'sasaran'     => $sasaran['sasaran'],
                        'indikator'   => $ind['indikator'],
                        'satuan'      => $ind['satuan_nama'] ?? $ind['satuan'] ?? '',
                        'dari_sumber' => ! empty($ind['source_type']),
                    ];
                }
            }
        }

        return $keluar;
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
    /**
     * Perbarui HANYA keterangan indikator: definisi, rumusan, sumber data,
     * penanggung jawab.
     *
     * =====================================================================
     * MENGAPA METHOD TERSENDIRI, BUKAN updateComplete()
     *
     * `updateComplete()` menulis ulang sasaran, indikator, satuan, dan seluruh
     * targetnya dari apa pun yang dikirim form. Dipakai untuk mengisi empat
     * kolom keterangan, ia akan MENGHAPUS target dan indikator yang tidak ikut
     * terkirim — dan tidak ada satu pun galat yang menandainya.
     *
     * Method ini menyentuh empat kolom itu saja. Bahkan bila POST dikarang
     * memuat target atau satuan, tidak ada jalannya sampai ke basis data.
     *
     * @param array $perIndikator [id indikator => [definisi, rumusan_perhitungan,
     *                            sumber_data, penanggung_jawab]]
     *
     * @return int jumlah indikator yang diperbarui
     */
    public function perbaruiKeterangan(int $sasaranId, array $perIndikator): int
    {
        if ($sasaranId <= 0 || $perIndikator === []) {
            return 0;
        }

        // Pencegah IDOR: hanya indikator milik sasaran ini yang boleh tersentuh,
        // sekalipun id indikator OPD lain ikut dikirim.
        $milik = array_map('intval', array_column(
            $this->db->table('iku_indikator')->select('id')
                ->where('iku_sasaran_id', $sasaranId)->get()->getResultArray(),
            'id'
        ));

        if ($milik === []) {
            return 0;
        }

        $jumlah = 0;
        $now    = date('Y-m-d H:i:s');

        foreach ($perIndikator as $id => $isi) {
            $id = (int) $id;

            if (! in_array($id, $milik, true) || ! is_array($isi)) {
                continue;
            }

            $this->db->table('iku_indikator')->where('id', $id)->update([
                'definisi'            => $this->nullJikaKosong($isi['definisi'] ?? null),
                'rumusan_perhitungan' => $this->nullJikaKosong($isi['rumusan_perhitungan'] ?? null),
                'sumber_data'         => $this->nullJikaKosong($isi['sumber_data'] ?? null),
                'penanggung_jawab'    => $this->nullJikaKosong($isi['penanggung_jawab'] ?? null),
                'updated_at'          => $now,
            ]);

            $jumlah++;
        }

        return $jumlah;
    }

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
                // Dulu baris ini langsung DELETE, dan target + program ikut
                // musnah lewat FK ON DELETE CASCADE. Itulah tempat sejarah IKU
                // selama ini rusak: mengosongkan satu textarea di form sudah
                // cukup untuk menghapus permanen indikator beserta seluruh
                // target tahunannya.
                //
                // Sekarang indikator yang sudah dirujuk sejarah (arsip revisi,
                // lineage penggantian, atau baris snapshot LAKIP) hanya
                // DIPENSIUNKAN. Yang belum pernah dirujuk tetap boleh dihapus
                // supaya salah ketik biasa masih bisa dibereskan (invariant 8).
                $this->hapusAtauPensiunkanIndikator(array_values($idDihapus));
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

    /**
     * Hapus satu sasaran IKU — ATAU pensiunkan, bila sudah dirujuk sejarah.
     *
     * Sasaran yang pernah masuk arsip revisi, atau yang salah satu indikatornya
     * pernah dirujuk revisi/snapshot LAKIP, TIDAK boleh hilang: menghapusnya
     * akan menyeret indikator, target, dan program di bawahnya lewat FK
     * ON DELETE CASCADE, dan dokumen tahun-tahun lampau kehilangan asal-usulnya
     * (invariant 8).
     *
     * Sasaran yang belum pernah dirujuk tetap dihapus seperti biasa, supaya
     * kesalahan input yang baru dibuat masih bisa dibereskan.
     */
    public function deleteComplete(int $sasaranId): bool
    {
        $sasaranId = (int) $sasaranId;
        if ($sasaranId <= 0) {
            return false;
        }

        $revisi = $this->revisi();

        $indikatorIds = array_map('intval', array_column(
            $this->db->table('iku_indikator')->select('id')
                ->where('iku_sasaran_id', $sasaranId)->get()->getResultArray(),
            'id'
        ));

        $sasaranDirujuk   = ! empty($revisi->sasaranDirujukSejarah([$sasaranId]));
        $indikatorDirujuk = $revisi->indikatorDirujukSejarah($indikatorIds);

        if (! $sasaranDirujuk && empty($indikatorDirujuk)) {
            return (bool) $this->db->table('iku_sasaran')->where('id', $sasaranId)->delete();
        }

        $now    = date('Y-m-d H:i:s');
        $alasan = 'Dihapus dari daftar IKU berjalan, tetapi tetap disimpan karena sudah dirujuk dokumen/laporan terdahulu.';

        $this->db->table('iku_sasaran')->where('id', $sasaranId)->update([
            'dihentikan_pada'   => $now,
            'alasan_dihentikan' => $alasan,
            'updated_at'        => $now,
        ]);

        if (! empty($indikatorIds)) {
            $revisi->pensiunkanIndikator($indikatorIds, $alasan);
        }

        return true;
    }

    /** Hapus satu indikator IKU saja — atau pensiunkan bila sudah dirujuk sejarah. */
    public function deleteIndikator(int $indikatorId): bool
    {
        $indikatorId = (int) $indikatorId;
        if ($indikatorId <= 0) {
            return false;
        }

        return $this->hapusAtauPensiunkanIndikator([$indikatorId]) >= 0;
    }

    /**
     * Bagi sekumpulan indikator menjadi "boleh dihapus" dan "harus dipensiunkan".
     *
     * @param int[] $indikatorIds
     *
     * @return int jumlah yang dipensiunkan (sisanya dihapus)
     */
    private function hapusAtauPensiunkanIndikator(array $indikatorIds): int
    {
        $indikatorIds = array_values(array_unique(array_filter(array_map('intval', $indikatorIds))));

        if (empty($indikatorIds)) {
            return 0;
        }

        $revisi  = $this->revisi();
        $dirujuk = $revisi->indikatorDirujukSejarah($indikatorIds);
        $bebas   = array_values(array_diff($indikatorIds, $dirujuk));

        if (! empty($dirujuk)) {
            $revisi->pensiunkanIndikator(
                $dirujuk,
                'Dihapus dari daftar IKU berjalan, tetapi tetap disimpan karena sudah dirujuk dokumen/laporan terdahulu.'
            );
        }

        if (! empty($bebas)) {
            $this->db->table('iku_indikator')->whereIn('id', $bebas)->delete();
        }

        return count($dirujuk);
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

    /** @var IkuRevisiModel|null dibuat seperlunya, bukan di konstruktor */
    private ?IkuRevisiModel $revisiModel = null;

    private function revisi(): IkuRevisiModel
    {
        return $this->revisiModel ??= new IkuRevisiModel();
    }

    /**
     * Kolom `dihentikan_pada` sudah terpasang atau belum.
     *
     * Modul IKU harus tetap jalan di server yang belum menjalankan
     * db/update_2026-08-18_iku_revisi_lakip_snapshot.sql — di sana fitur
     * pensiun sekadar tidak aktif, bukan membuat halaman IKU mati total.
     *
     * Hasilnya di-cache per-request karena dipanggil di jalur baca panas.
     */
    private function punyaKolomPensiun(string $tabel): bool
    {
        static $cache = [];

        if (isset($cache[$tabel])) {
            return $cache[$tabel];
        }

        try {
            return $cache[$tabel] = in_array('dihentikan_pada', $this->db->getFieldNames($tabel), true);
        } catch (Throwable $e) {
            return $cache[$tabel] = false;
        }
    }

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

    /**
     * Nomor urut berikutnya bagi sasaran baru pada satu OPD & periode.
     *
     * Di akar IKU, matriks Cascading mengurutkan baris Eselon II lewat
     * `iku_sasaran.urutan` LEBIH DULU daripada tujuan/sasaran Renstra. Sasaran
     * mandiri yang lahir dengan urutan 0 karena itu MEMIMPIN dokumen,
     * mendahului sasaran hasil Renstra — urutan yang keliru untuk dokumen
     * resmi. Ia diberi nomor di ekor sejak lahir, bukan ditambal di query.
     */
    public function urutanBerikutnya(?int $opdId, int $tahunMulai, int $tahunAkhir): int
    {
        $b = $this->db->table('iku_sasaran')
            ->selectMax('urutan', 'maks')
            ->where('tahun_mulai', $tahunMulai)
            ->where('tahun_akhir', $tahunAkhir);

        $opdId === null ? $b->where('opd_id IS NULL', null, false) : $b->where('opd_id', $opdId);

        return (int) ($b->get()->getRowArray()['maks'] ?? 0) + 1;
    }

    /**
     * Kolom penunjuk tujuan bagi SASARAN MANDIRI, bila DB-nya sudah dimigrasi.
     *
     * Dipisah supaya `insertSasaran()` tetap bekerja pada basis data yang belum
     * menjalankan db/update_2026-08-29_sasaran_iku_mandiri.sql.
     *
     * @return array<string, int|null>
     */
    private function kolomTujuanMandiri(array $data): array
    {
        if (! $this->db->fieldExists('renstra_tujuan_id', 'iku_sasaran')) {
            return [];
        }

        return [
            'renstra_tujuan_id' => ! empty($data['renstra_tujuan_id'])
                ? (int) $data['renstra_tujuan_id'] : null,
        ];
    }

    /**
     * Tujuan Renstra milik satu OPD pada satu periode, untuk dropdown.
     *
     * `renstra_tujuan` tidak menyimpan `opd_id`; kepemilikannya dicapai lewat
     * sasaran di bawahnya. DISTINCT karena satu tujuan biasanya menaungi
     * beberapa sasaran.
     *
     * @return array<int, array{id:int, tujuan:string}>
     */
    public function tujuanRenstraOpd(int $opdId, int $tahunMulai, int $tahunAkhir): array
    {
        return $this->db->table('renstra_tujuan rt')
            ->select('rt.id, rt.tujuan')
            ->distinct()
            ->join('renstra_sasaran rs', 'rs.renstra_tujuan_id = rt.id')
            ->where('rs.opd_id', $opdId)
            ->where('rs.tahun_mulai', $tahunMulai)
            ->where('rs.tahun_akhir', $tahunAkhir)
            ->orderBy('rt.id', 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * Adakah sasaran Renstra yang teksnya sama pada OPD & periode ini?
     *
     * Penjaga terhadap alasan pintu tambah manual dulu ditutup: sasaran kembar
     * di sebelah hasil sync. Kalau sasarannya MEMANG ada di Renstra, jalan yang
     * benar adalah Sync — bukan mengetiknya lagi di sini.
     */
    public function sasaranAdaDiRenstra(int $opdId, int $tahunMulai, int $tahunAkhir, string $teks): bool
    {
        $rapi = static fn (string $x): string => trim(preg_replace('/\s+/', ' ', $x));

        return $this->db->table('renstra_sasaran')
            ->where('opd_id', $opdId)
            ->where('tahun_mulai', $tahunMulai)
            ->where('tahun_akhir', $tahunAkhir)
            ->where("TRIM(REGEXP_REPLACE(sasaran, '[[:space:]]+', ' ')) = "
                . $this->db->escape($rapi($teks)), null, false)
            ->countAllResults() > 0;
    }

    private function insertSasaran(array $data): int
    {
        $this->db->table('iku_sasaran')->insert([
            'opd_id'      => isset($data['opd_id']) && $data['opd_id'] !== null ? (int) $data['opd_id'] : null,
            'sasaran'     => $data['sasaran'],
            'tahun_mulai' => (int) $data['tahun_mulai'],
            'tahun_akhir' => (int) $data['tahun_akhir'],
            'urutan'      => (int) ($data['urutan'] ?? 0),
            'source_type'       => $data['source_type'] ?? null,
            'source_version_id' => isset($data['source_version_id']) && $data['source_version_id']
                ? (int) $data['source_version_id'] : null,
            'source_sasaran_id' => isset($data['source_sasaran_id']) && $data['source_sasaran_id']
                ? (int) $data['source_sasaran_id'] : null,
            'created_at'  => date('Y-m-d H:i:s'),
            'updated_at'  => date('Y-m-d H:i:s'),
        ] + $this->kolomTujuanMandiri($data));

        return (int) $this->db->insertID();
    }

    private function insertIndikator(int $sasaranId, array $indikator, int $urutan): int
    {
        $this->db->table('iku_indikator')->insert(
            $this->siapkanIndikator($indikator, $urutan) + [
                'iku_sasaran_id' => $sasaranId,
                // Jejak asal ditulis SEKALI di sini, bukan lewat
                // siapkanIndikator() yang juga melayani pembaruan.
                'source_type'         => $indikator['source_type'] ?? null,
                'source_version_id'   => ! empty($indikator['source_version_id'])
                    ? (int) $indikator['source_version_id'] : null,
                'source_indikator_id' => ! empty($indikator['source_indikator_id'])
                    ? (int) $indikator['source_indikator_id'] : null,
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
    /**
     * Ambil ulang isi satu indikator IKU dari sumbernya.
     *
     * SENGAJA hanya menimpa medan yang memang MILIK sumber: teks indikator,
     * satuan, baseline, jenis, dan target. Definisi operasional, rumusan
     * perhitungan, sumber data, dan penanggung jawab TIDAK disentuh — keempatnya
     * diketik di IKU dan tidak punya padanan di Renstra/RPJMD; menimpanya
     * dengan NULL berarti menghapus pekerjaan orang tanpa diminta.
     *
     * Jejak asal (`source_*`) juga tidak ditulis ulang: ia ditulis sekali saat
     * baris lahir dan tetap menunjuk indikator sumber yang sama.
     */
    /**
     * Ubah REDAKSI sasaran IKU. Hanya kolom `sasaran` yang disentuh.
     *
     * =====================================================================
     * MENGAPA INI AMAN SEKARANG, DAN DULU TIDAK
     *
     * Dulu segala pencocokan IKU bertumpu pada TEKS: sync mengenali sasaran
     * dengan membandingkan kalimatnya. Mengubah satu kata membuat sync
     * kehilangan jejaknya, menganggapnya sasaran baru, lalu menyalin ulang
     * seluruh indikator di bawahnya — lahirlah kembar.
     *
     * Sejak setiap sasaran menyimpan `source_sasaran_id`, pencocokan berjalan
     * lewat ID. Redaksinya boleh berubah sesuka hati tanpa memutus apa pun:
     * sync tetap mengenalinya, jangkar cascading menunjuk id, dan arsip revisi
     * maupun LAKIP menyimpan teksnya sendiri yang sudah beku.
     *
     * =====================================================================
     * YANG DIJAGA
     *
     * Redaksi baru tidak boleh KEMBAR dengan sasaran lain pada OPD dan periode
     * yang sama. Dua sasaran berteks sama membuat layar IKU dan pratinjau sync
     * mustahil dibaca — dan pada basis data yang belum punya silsilah lengkap,
     * ia menghidupkan kembali persoalan kembar yang baru saja ditutup.
     *
     * @return array{ok: bool, pesan: string}
     */
    public function renameSasaran(int $sasaranId, string $teksBaru): array
    {
        $baru = trim(preg_replace('/\s+/', ' ', $teksBaru));

        if ($baru === '') {
            return ['ok' => false, 'pesan' => 'Sasaran tidak boleh dikosongkan.'];
        }

        $lama = $this->db->table('iku_sasaran')
            ->select('id, opd_id, tahun_mulai, tahun_akhir, sasaran')
            ->where('id', $sasaranId)->get()->getRowArray();

        if ($lama === null) {
            return ['ok' => false, 'pesan' => 'Sasaran IKU tidak ditemukan.'];
        }

        if (trim(preg_replace('/\s+/', ' ', (string) $lama['sasaran'])) === $baru) {
            return ['ok' => false, 'pesan' => ''];   // tidak berubah, bukan galat
        }

        $kembar = $this->db->table('iku_sasaran')
            ->where('tahun_mulai', $lama['tahun_mulai'])
            ->where('tahun_akhir', $lama['tahun_akhir'])
            ->where('id !=', $sasaranId)
            ->where("TRIM(REGEXP_REPLACE(sasaran, '[[:space:]]+', ' ')) = " . $this->db->escape($baru), null, false);

        $lama['opd_id'] === null
            ? $kembar->where('opd_id IS NULL', null, false)
            : $kembar->where('opd_id', $lama['opd_id']);

        if ($kembar->countAllResults() > 0) {
            return ['ok' => false, 'pesan' => 'Sudah ada sasaran lain dengan redaksi yang sama pada periode ini.'];
        }

        $this->db->table('iku_sasaran')->where('id', $sasaranId)->update([
            'sasaran'    => $baru,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return ['ok' => true, 'pesan' => 'Redaksi sasaran IKU diperbarui.'];
    }

    /**
     * MODE GANTI: buang isi IKU yang tidak ada pada sumber.
     *
     * =====================================================================
     * APA YANG MEMBEDAKANNYA DARI SYNC BIASA
     *
     * Sync biasa hanya MENAMBAH dan MEMPERBARUI. Indikator IKU yang tidak
     * ada di Renstra dibiarkan — dan memang itu sah, sebab IKU boleh memuat
     * indikator di luar Renstra. Mode ganti membuat IKU menjadi cerminan
     * sumbernya: yang tidak ada di sana, dibuang.
     *
     * =====================================================================
     * YANG TIDAK PERNAH DIBUANG
     *
     * Baris yang sudah dipakai TIDAK boleh hilang, karena yang ikut hilang
     * bukan barisnya saja melainkan pekerjaan orang lain:
     *
     *   * dijangkar baris CASCADING  -> sasaran Eselon III/IV/Pelaksana di
     *                                   bawahnya kehilangan induk dan lenyap
     *                                   dari layar;
     *   * punya realisasi LAKIP      -> capaian yang sudah diinput putus dari
     *                                   indikatornya;
     *   * termuat di ARSIP REVISI    -> dokumen yang sudah disahkan berubah
     *                                   isinya surut ke belakang;
     *   * punya program pendukung    -> kaitan ke program ikut terbuang.
     *
     * Baris seperti itu DIPERTAHANKAN dan dilaporkan, bukan dipaksa hilang.
     * "Mengganti" tidak boleh berarti "menghapus jejak orang lain".
     *
     * Sasaran hanya ikut dibuang bila seluruh indikatornya sudah habis —
     * cangkang kosong, tanpa isi yang tertinggal.
     *
     * @return array{dibuang_indikator:int, dibuang_sasaran:int,
     *               dipertahankan:array<int,array{id:int,indikator:string,alasan:string}>}
     */
    public function buangTanpaPadanan(?int $opdId, int $tahunMulai, int $tahunAkhir, bool $kerjakan): array
    {
        $hasil = ['dibuang_indikator' => 0, 'dibuang_sasaran' => 0, 'dipertahankan' => []];

        $b = $this->db->table('iku_indikator ii')
            ->select('ii.id, ii.indikator, ii.iku_sasaran_id')
            ->join('iku_sasaran s', 's.id = ii.iku_sasaran_id')
            ->where('s.tahun_mulai', $tahunMulai)
            ->where('s.tahun_akhir', $tahunAkhir)
            ->where('ii.source_indikator_id IS NULL', null, false);

        // SASARAN MANDIRI DIKECUALIKAN.
        //
        // Indikator di bawah sasaran yang LAHIR di IKU memang tidak punya
        // padanan di Renstra — itu bukan kecelakaan, itu maksudnya. Tanpa
        // pengecualian ini, mode Ganti akan menghapus tepat apa yang baru saja
        // sengaja dibuat penggunanya, pada sync berikutnya.
        //
        // Ditandai lewat `source_type`, bukan lewat `renstra_tujuan_id`:
        // penunjuk tujuan bisa menjadi NULL sendiri bila tujuan Renstra-nya
        // dihapus (ON DELETE SET NULL), dan sasarannya akan mendadak terlihat
        // seperti yatim biasa.
        if ($this->db->fieldExists('source_type', 'iku_sasaran')) {
            $b->groupStart()
                ->where('s.source_type IS NULL', null, false)
                ->orWhere('s.source_type !=', 'iku')
                ->groupEnd();
        }

        // ...DAN INDIKATOR MANDIRI, sekalipun sasarannya hasil sync.
        //
        // Kasus nyatanya: `Indeks Ketahanan Pangan (IKP)` meringkas LIMA
        // indikator RPJMD (Produksi Padi, Jagung, Daging, Telur, Perikanan)
        // menjadi satu indeks. Ia sengaja tidak punya padanan satu-lawan-satu,
        // dan keempat penjaga lain tidak menolongnya: belum dijangkar
        // cascading, belum dipakai LAKIP, belum punya arsip revisi maupun
        // program. Tanpa baris ini, satu centang Ganti menghapusnya.
        if ($this->db->fieldExists('source_type', 'iku_indikator')) {
            $b->groupStart()
                ->where('ii.source_type IS NULL', null, false)
                ->orWhere('ii.source_type !=', 'iku')
                ->groupEnd();
        }

        $opdId === null ? $b->where('s.opd_id IS NULL', null, false) : $b->where('s.opd_id', $opdId);

        if ($this->punyaKolomPensiun('iku_indikator')) {
            $b->where('ii.dihentikan_pada IS NULL', null, false);
        }

        $calon = $b->get()->getResultArray();

        if ($calon === []) {
            return $hasil;
        }

        $buang = [];

        foreach ($calon as $c) {
            $id     = (int) $c['id'];
            $alasan = $this->alasanDipertahankan($id);

            if ($alasan !== null) {
                $hasil['dipertahankan'][] = ['id' => $id, 'indikator' => $c['indikator'], 'alasan' => $alasan];

                continue;
            }

            $buang[] = $id;
        }

        if ($buang === []) {
            return $hasil;
        }

        $hasil['dibuang_indikator'] = count($buang);

        if (! $kerjakan) {
            return $hasil;
        }

        $sasaranTersentuh = array_values(array_unique(array_map(
            static fn ($c) => (int) $c['iku_sasaran_id'],
            array_filter($calon, static fn ($c) => in_array((int) $c['id'], $buang, true))
        )));

        // Target & program ikut lewat ON DELETE CASCADE.
        $this->db->table('iku_indikator')->whereIn('id', $buang)->delete();

        // Cangkang sasaran yang isinya habis.
        foreach ($sasaranTersentuh as $sid) {
            $sisa = $this->db->table('iku_indikator')->where('iku_sasaran_id', $sid)->countAllResults();

            if ($sisa === 0) {
                $this->db->table('iku_sasaran')->where('id', $sid)->delete();
                $hasil['dibuang_sasaran']++;
            }
        }

        return $hasil;
    }

    /** Alasan sebuah indikator IKU tidak boleh dibuang, atau null bila aman. */
    private function alasanDipertahankan(int $ikuIndikatorId): ?string
    {
        if ($this->db->fieldExists('iku_indikator_id', 'cascading_sasaran_opd')
            && $this->db->table('cascading_sasaran_opd')
                ->where('iku_indikator_id', $ikuIndikatorId)->countAllResults() > 0) {
            return 'dijangkar baris cascading';
        }

        if ($this->db->fieldExists('source_entity_id', 'lakip')
            && $this->db->table('lakip')->where('source_type', 'iku')
                ->where('source_entity_id', $ikuIndikatorId)->countAllResults() > 0) {
            return 'punya realisasi LAKIP';
        }

        if ($this->db->tableExists('iku_revisi_indikator')
            && $this->db->table('iku_revisi_indikator')
                ->where('sumber_indikator_id', $ikuIndikatorId)->countAllResults() > 0) {
            return 'termuat di arsip revisi';
        }

        if ($this->db->tableExists('iku_program')
            && $this->db->table('iku_program')
                ->where('iku_indikator_id', $ikuIndikatorId)->countAllResults() > 0) {
            return 'punya program pendukung';
        }

        return null;
    }

    /**
     * Pasang silsilah pada baris IKU yang padanannya ketemu tetapi belum
     * menyimpan asal-usulnya.
     *
     * =====================================================================
     * MENGAPA PERLU
     *
     * Pencocokan sync bertingkat: silsilah dulu (`source_*_id` menunjuk id,
     * jadi tetap benar walau redaksinya berubah), teks belakangan. Masalahnya,
     * pencocokan lewat TEKS dulu tidak pernah menuliskan hasilnya ke mana pun.
     * Akibatnya baris IKU yang diketik manual selamanya hanya bertaut lewat
     * teks — dan begitu ada yang merapikan satu huruf di salah satu sisi,
     * sync berikutnya menganggapnya indikator baru lalu menyalinnya lagi.
     * Itulah asal data kembar.
     *
     * Sekali baris ditautkan, redaksinya boleh berubah sesuka hati tanpa
     * membuat sync kehilangan jejak.
     *
     * =====================================================================
     * YANG DIJAGA
     *
     * Hanya kolom yang MASIH KOSONG yang diisi. Tautan yang sudah ada — baik
     * hasil sync sebelumnya maupun pemetaan manual — tidak pernah ditimpa,
     * sehingga metode ini aman dijalankan berulang kali.
     *
     * @param array<int,array<string,mixed>> $kandidat hasil getKandidatSync()
     *
     * @return int jumlah baris yang baru ditautkan (sasaran + indikator)
     */
    private function tautkanSilsilah(array $kandidat, string $sumber, ?int $renstraVersiId): int
    {
        $jml = 0;

        foreach ($kandidat as $sasaran) {
            $ikuSasaranId = (int) ($sasaran['iku_sasaran_id'] ?? 0);
            $asalSasaran  = (int) ($sasaran['sumber_live_id'] ?? 0);

            if ($ikuSasaranId > 0 && $asalSasaran > 0) {
                $this->db->table('iku_sasaran')
                    ->where('id', $ikuSasaranId)
                    ->where('source_sasaran_id IS NULL', null, false)
                    ->update([
                        'source_sasaran_id' => $asalSasaran,
                        'source_type'       => $sumber,
                        'source_version_id' => $renstraVersiId,
                    ]);

                $jml += $this->db->affectedRows();
            }

            foreach ($sasaran['indikator'] ?? [] as $ind) {
                $ikuIndId  = (int) ($ind['iku_id'] ?? 0);
                $asalIndId = (int) ($ind['sumber_live_id'] ?? 0);

                if ($ikuIndId <= 0 || $asalIndId <= 0) {
                    continue;
                }

                $this->db->table('iku_indikator')
                    ->where('id', $ikuIndId)
                    ->where('source_indikator_id IS NULL', null, false)
                    ->update([
                        'source_indikator_id' => $asalIndId,
                        'source_type'         => $sumber,
                        'source_version_id'   => $renstraVersiId,
                    ]);

                $jml += $this->db->affectedRows();
            }
        }

        return $jml;
    }

    private function timpaDariSumber(int $ikuIndikatorId, array $ind, array $target): void
    {
        $this->db->table('iku_indikator')->where('id', $ikuIndikatorId)->update([
            'indikator'       => trim((string) ($ind['indikator'] ?? '')),
            'satuan'          => $this->nullJikaKosong($ind['satuan'] ?? null),
            'jenis_indikator' => $this->nullJikaKosong($ind['jenis_indikator'] ?? null),
            'baseline'        => $this->nullJikaKosong($ind['baseline'] ?? null),
            'updated_at'      => date('Y-m-d H:i:s'),
        ]);

        $this->db->table('iku_target')->where('iku_indikator_id', $ikuIndikatorId)->delete();
        $this->simpanTarget($ikuIndikatorId, $target);
    }

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
            // Kolom `source_*` SENGAJA tidak di sini. Method ini dipakai juga
            // oleh updateIndikator(), sehingga menyunting IKU akan menimpanya
            // dengan NULL — jejak asalnya terhapus justru saat orang merapikan
            // kalimatnya. Jejak hanya ditulis sekali, saat baris lahir.
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
