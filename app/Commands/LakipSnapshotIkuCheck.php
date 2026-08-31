<?php

namespace App\Commands;

use App\Models\LakipSnapshotModel;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Throwable;

/**
 * Uji pembekuan LAKIP bersumber IKU, ujung ke ujung.
 *
 *   php spark lakip:jaga-snapshot --db e-sakip_7
 *
 * Sebelum LAKIP boleh bersumber IKU, "sumber" bisa disimpulkan dari mode saja.
 * Sekarang tidak lagi, dan tiga tempat harus sepakat: yang MENULIS snapshot,
 * yang MEMBACANYA kembali, dan yang MEMBANDINGKANNYA dengan data hidup.
 * Ketidaksepakatan di antara ketiganya tidak melempar galat apa pun — ia
 * menghasilkan tabel kosong, atau lebih buruk: dokumen beku berisi angka
 * Renstra padahal yang dinilai operator adalah angka IKU.
 *
 * Perintah ini menulis, jadi ia MENOLAK berjalan di basis data aplikasi.
 */
class LakipSnapshotIkuCheck extends BaseCommand
{
    protected $group       = 'Versioning';
    protected $name        = 'lakip:jaga-snapshot';
    protected $description = 'Uji snapshot LAKIP bersumber IKU (tulis, baca ulang, bandingkan, kunci).';
    protected $usage       = 'lakip:jaga-snapshot --db <salinan>';
    protected $arguments   = [];
    protected $options     = ['--db' => 'basis data salinan (wajib)'];

    private const TAHUN = 2031;
    private const OPD   = 11;

    private $db;
    private int $lulus = 0;
    private int $gagal = 0;

    /** @var array<string, int[]> jejak baris yang dibuat, untuk dibersihkan */
    private array $jejak = [];

    public function run(array $params)
    {
        $namaDb = trim((string) ($params['db'] ?? CLI::getOption('db') ?: ''));
        $cfg    = config('Database')->default;

        if ($namaDb === '' || $namaDb === '1') {
            CLI::error('Wajib --db <salinan>. Perintah ini menulis ke basis data.');

            return EXIT_ERROR;
        }

        if ($namaDb === $cfg['database']) {
            CLI::error('Menolak berjalan di "' . $namaDb . '": itu basis data aplikasi. Pakai salinan.');

            return EXIT_ERROR;
        }

        $cfg['database'] = $namaDb;
        $this->db        = db_connect($cfg, false);

        CLI::write('Basis data : ' . CLI::color($this->db->getDatabase(), 'yellow'));
        CLI::newLine();

        try {
            $this->jalankan();
        } catch (Throwable $e) {
            $this->gagal('jalannya uji', $e->getMessage());
        } finally {
            $this->bersihkan();
        }

        CLI::newLine();
        CLI::write('LULUS: ' . CLI::color((string) $this->lulus, 'green')
            . '   GAGAL: ' . CLI::color((string) $this->gagal, $this->gagal > 0 ? 'red' : 'green'));

        return $this->gagal > 0 ? EXIT_ERROR : EXIT_SUCCESS;
    }

    /* =========================================================
     * JALANNYA UJI
     * =======================================================*/

    private function jalankan(): void
    {
        $model = new LakipSnapshotModel($this->db);

        if (! $model->siap()) {
            $this->gagal('tabel snapshot tersedia', 'lakip_snapshot belum ada di salinan ini');

            return;
        }

        [$revisiId, $arsipIndId, $indikatorLiveId] = $this->benihIku();
        $lakipId = $this->benihLakip($indikatorLiveId);

        $scope = ['tahun' => (string) self::TAHUN, 'mode' => 'opd', 'opdScope' => self::OPD];
        $bahan = $this->bahanIku($revisiId, $indikatorLiveId, $lakipId, $arsipIndId);

        // --- 1. MENULIS ---
        $snapshotId = $model->siapkan($scope, $bahan, null);
        $this->jejak['lakip_snapshot'][] = $snapshotId;

        $kepala = $this->db->table('lakip_snapshot')->where('id', $snapshotId)->get()->getRowArray();

        $this->samaDengan('kepala mencatat sumber IKU', 'iku', (string) $kepala['source_type']);
        $this->samaDengan('kepala mencatat versi sumber', $revisiId, (int) $kepala['source_version_id']);
        $this->samaDengan('kepala mencatat revisi IKU efektif', $revisiId, (int) $kepala['sumber_iku_revisi_id']);

        $baris = $this->db->table('lakip_snapshot_baris')
            ->where('snapshot_id', $snapshotId)->get()->getResultArray();

        $this->samaDengan('satu baris dibekukan', 1, count($baris));

        $b = $baris[0] ?? [];

        $this->samaDengan('baris memakai kolom kunci IKU', $indikatorLiveId, (int) ($b['iku_indikator_id'] ?? 0));
        // `??` memperlakukan null sama dengan "tidak ada", jadi ia TIDAK bisa
        // dipakai membedakan kolom yang kosong dari kolom yang hilang. Yang
        // diuji di sini justru kolomnya ada tetapi nilainya NULL.
        $this->samaDengan('kolom kunci Renstra ada di hasil', true, array_key_exists('renstra_target_id', $b));
        $this->samaDengan('kolom kunci Renstra dibiarkan kosong', null, $b['renstra_target_id']);
        $this->samaDengan('kolom kunci RPJMD dibiarkan kosong', null, $b['rpjmd_target_id']);
        $this->samaDengan('jejak ke arsip revisi tercatat', $arsipIndId, (int) ($b['iku_revisi_indikator_id'] ?? 0));
        $this->samaDengan('baris menandai sumbernya', 'iku', (string) ($b['sumber'] ?? ''));
        $this->samaDengan('realisasi ikut beku', '76', (string) ($b['realisasi'] ?? ''));

        // --- 2. MEMBACA ULANG ---
        $isi = $model->rehidrasi($snapshotId);

        $this->samaDengan('rehidrasi mengembalikan satu baris', 1, count($isi['rows']));
        $this->samaDengan(
            'kunci baris hasil rehidrasi = indikator berjalan',
            $indikatorLiveId,
            (int) ($isi['rows'][0]['target_id'] ?? 0)
        );
        $this->samaDengan(
            'tautan ke arsip revisi ikut pulang',
            $arsipIndId,
            (int) ($isi['rows'][0]['arsip_id'] ?? 0)
        );
        $this->samaDengan(
            'peta LAKIP dikunci indikator berjalan',
            true,
            isset($isi['lakipMap'][$indikatorLiveId])
        );
        $this->samaDengan(
            'peta menyebut sumbernya IKU',
            'iku',
            (string) ($isi['lakipMap'][$indikatorLiveId]['source_type'] ?? '')
        );

        // --- 3. MEMBANDINGKAN ---
        $sama = $model->bandingkan($snapshotId, $bahan);

        $this->samaDengan('bandingkan: tidak ada yang berubah', 1, (int) $sama['sama']);
        $this->samaDengan('bandingkan: tidak ada baris baru', 0, count($sama['baru']));
        $this->samaDengan('bandingkan: tidak ada baris hilang', 0, count($sama['hilang']));

        $bahanBeda = $bahan;
        $bahanBeda['lakipMap'][$indikatorLiveId]['capaian_tahun_ini'] = '91';
        $beda = $model->bandingkan($snapshotId, $bahanBeda);

        $this->samaDengan('bandingkan: realisasi berubah terdeteksi', 1, count($beda['berubah']));

        // --- 4. MENGUNCI ---
        $model->finalkan($snapshotId, null);

        $this->samaDengan(
            'status jadi final',
            'final',
            (string) $this->db->table('lakip_snapshot')->where('id', $snapshotId)
                ->get()->getRowArray()['status']
        );

        $this->harusGagal(
            'snapshot final menolak disinkronkan',
            static fn () => $model->sinkronkan($scope, $bahan, null),
            'difinalkan'
        );

        // --- 5. PENYESUAIAN KEBIJAKAN, satu-satunya pintu setelah final ---
        $this->ujiPenyesuaian($scope, $snapshotId, $indikatorLiveId);

        // --- 6. ANALISIS FAKTOR, menempel pada baris yang sama ---
        $this->ujiAnalisis($scope, $indikatorLiveId);

        // --- 7. BENCHMARK PROVINSI/NASIONAL, menempel pada indikator ---
        $this->ujiBenchmark($scope, $indikatorLiveId);
    }

    /**
     * Angka pembanding Provinsi/Nasional menempel pada INDIKATOR, dan indikator
     * di layar LAKIP OPD kini bisa berasal dari IKU maupun Renstra.
     *
     * Sebelum kolomnya ada, mengisi benchmark untuk baris IKU bukan tertukar
     * diam-diam melainkan buntu: `indikatorSah()` mencarinya di tabel Renstra,
     * dan foreign key menolak id yang tidak ada di sana.
     */
    private function ujiBenchmark(array $scope, int $indikatorLiveId): void
    {
        $model = new \App\Models\LakipBenchmarkModel($this->db);

        if (! $model->punyaKolomSumber()) {
            $this->gagal(
                'kolom sumber benchmark terpasang',
                'jalankan db/update_2026-08-26_benchmark_sumber_iku.sql di salinan ini'
            );

            return;
        }

        $this->samaDengan(
            'kolom kunci mengikuti sumber IKU',
            'iku_indikator_id',
            \App\Models\LakipBenchmarkModel::kolomIndikator('opd', 'iku')
        );
        $this->samaDengan(
            'kolom kunci tetap Renstra bila sumbernya Renstra',
            'renstra_indikator_id',
            \App\Models\LakipBenchmarkModel::kolomIndikator('opd', 'renstra')
        );
        // Sejak §24 (commit "version untuk iku admin_kab"), sumber IKU
        // diperiksa SEBELUM mode: IKU Kabupaten juga menulis ke kolom
        // iku_indikator_id, bukan meminjam kolom milik dokumen RPJMD.
        // Pemeriksa lama mengunci aturan sebelumnya dan gagal palsu.
        $this->samaDengan(
            'kabupaten + sumber IKU memakai kolom IKU (§24)',
            'iku_indikator_id',
            \App\Models\LakipBenchmarkModel::kolomIndikator('kabupaten', 'iku')
        );
        $this->samaDengan(
            'kabupaten + sumber RPJMD tetap kolom RPJMD',
            'rpjmd_indikator_id',
            \App\Models\LakipBenchmarkModel::kolomIndikator('kabupaten', 'rpjmd')
        );

        // Indikator IKU berjalan kini dianggap sah — dulu selalu null.
        $sah = $model->indikatorSah(
            $indikatorLiveId, 'opd', (string) $scope['tahun'], (int) $scope['opdScope'], 'iku'
        );

        $this->samaDengan(
            'indikator IKU berjalan lolos pemeriksaan',
            $indikatorLiveId,
            (int) ($sah['indikator_id'] ?? 0)
        );

        // Milik OPD lain tetap ditolak — pemeriksaan lingkup tidak ikut longgar.
        $this->samaDengan(
            'indikator IKU milik OPD lain ditolak',
            null,
            $model->indikatorSah(
                $indikatorLiveId, 'opd', (string) $scope['tahun'], (int) $scope['opdScope'] + 1, 'iku'
            )
        );

        $now = date('Y-m-d H:i:s');

        $this->db->table('lakip_benchmark')->insert([
            'iku_indikator_id' => $indikatorLiveId,
            'source_type'      => 'iku',
            'opd_id'           => (int) $scope['opdScope'],
            'tahun'            => (int) $scope['tahun'],
            'nilai_provinsi'   => 70.5,
            'nilai_nasional'   => 68.25,
            'created_at'       => $now,
            'updated_at'       => $now,
        ]);
        $idBm = (int) $this->db->insertID();
        $this->jejak['lakip_benchmark'][] = $idBm;

        $petaIku = $model->getByTahunKeyedByIndikator(
            (string) $scope['tahun'], 'opd', (int) $scope['opdScope'], 'iku'
        );
        $petaRen = $model->getByTahunKeyedByIndikator(
            (string) $scope['tahun'], 'opd', (int) $scope['opdScope'], 'renstra'
        );

        $this->samaDengan(
            'benchmark IKU terbaca pada kunci indikator berjalan',
            $idBm,
            (int) ($petaIku[$indikatorLiveId]['id'] ?? 0)
        );
        $this->samaDengan(
            'benchmark IKU tidak bocor ke peta Renstra',
            0,
            (int) ($petaRen[$indikatorLiveId]['id'] ?? 0)
        );

        $this->samaDengan(
            'pencarian baris lama mengikuti sumber',
            $idBm,
            (int) ($model->cariByIndikator($indikatorLiveId, (string) $scope['tahun'], 'opd', 'iku')['id'] ?? 0)
        );
        $this->samaDengan(
            'pencarian sumber Renstra tidak menemukan baris IKU',
            null,
            $model->cariByIndikator($indikatorLiveId, (string) $scope['tahun'], 'opd', 'renstra')
        );
    }

    /**
     * Analisis faktor menjelaskan MENGAPA sebuah capaian tercapai atau tidak,
     * jadi ia menempel pada baris — dan baris LAKIP kini bisa bersumber IKU
     * maupun Renstra. Id keduanya hidup di ruang angka yang sama, sehingga
     * tanpa penanda sumber analisis milik satu sumber akan muncul pada sumber
     * yang lain begitu operator berganti pilihan.
     */
    private function ujiAnalisis(array $scope, int $indikatorLiveId): void
    {
        $model = new \App\Models\LakipAnalisisModel($this->db);

        if (! $model->punyaKolomSumber()) {
            $this->gagal(
                'kolom sumber analisis terpasang',
                'jalankan db/update_2026-08-25_izin_sunting_iku_dan_analisis_iku.sql di salinan ini'
            );

            return;
        }

        $now = date('Y-m-d H:i:s');

        // `renstra_target_id` ber-FOREIGN KEY ke `renstra_target`, jadi baris
        // Renstra pada uji ini WAJIB memakai id yang benar-benar ada. Itu juga
        // temuan tersendiri: sebelum kolom IKU ada, menulis id indikator IKU ke
        // kolom itu bukan tertukar diam-diam melainkan ditolak mentah oleh FK —
        // analisis untuk baris IKU memang mustahil disimpan.
        $renstraTargetNyata = (int) ($this->db->table('renstra_target')
            ->select('id')->orderBy('id', 'ASC')->limit(1)
            ->get()->getRowArray()['id'] ?? 0);

        if ($renstraTargetNyata <= 0) {
            $this->gagal('ada renstra_target untuk pembanding', 'tabel renstra_target kosong di salinan ini');

            return;
        }

        $benih = function (string $sumber, string $kolom, string $teks) use ($indikatorLiveId, $renstraTargetNyata, $scope, $now): int {
            $this->db->table('lakip_analisis_faktor')->insert([
                'renstra_target_id' => $kolom === 'renstra_target_id' ? $renstraTargetNyata : null,
                'rpjmd_target_id'   => null,
                'iku_indikator_id'  => $kolom === 'iku_indikator_id' ? $indikatorLiveId : null,
                'source_type'       => $sumber,
                'opd_id'            => (int) $scope['opdScope'],
                'tahun'             => (int) $scope['tahun'],
                'faktor_pendukung'  => $teks,
                'created_at'        => $now,
                'updated_at'        => $now,
            ]);

            $id = (int) $this->db->insertID();
            $this->jejak['lakip_analisis_faktor'][] = $id;

            return $id;
        };

        // Dua analisis, id baris SAMA, sumber berbeda — kasus yang dulu tertukar.
        $idIku     = $benih('iku', 'iku_indikator_id', 'Analisis untuk baris IKU.');
        $idRenstra = $benih('renstra', 'renstra_target_id', 'Analisis untuk baris Renstra.');

        $petaIku = $model->getByTahunGrouped(
            (string) $scope['tahun'], 'opd', (int) $scope['opdScope'], 'iku'
        );
        $petaRen = $model->getByTahunGrouped(
            (string) $scope['tahun'], 'opd', (int) $scope['opdScope'], 'renstra'
        );

        $this->samaDengan(
            'analisis sumber IKU terbaca pada kunci indikator berjalan',
            $idIku,
            (int) ($petaIku[$indikatorLiveId][0]['id'] ?? 0)
        );
        $this->samaDengan(
            'peta IKU hanya memuat satu baris',
            1,
            count($petaIku[$indikatorLiveId] ?? [])
        );
        $this->samaDengan(
            'analisis Renstra terbaca pada kunci target Renstra',
            $idRenstra,
            (int) ($petaRen[$renstraTargetNyata][0]['id'] ?? 0)
        );
        $this->samaDengan(
            'analisis Renstra tidak bocor ke peta IKU',
            0,
            (int) ($petaIku[$renstraTargetNyata][0]['id'] ?? 0)
        );
        $this->samaDengan(
            'analisis IKU tidak bocor ke peta Renstra',
            0,
            (int) ($petaRen[$indikatorLiveId][0]['id'] ?? 0)
        );

        // Baris lama (sebelum migrasi) tidak punya source_type; ia harus tetap
        // terbaca sebagai Renstra, bukan menghilang.
        $this->db->table('lakip_analisis_faktor')->insert([
            'renstra_target_id' => $renstraTargetNyata,
            'source_type'       => null,
            'opd_id'            => (int) $scope['opdScope'],
            'tahun'             => (int) $scope['tahun'],
            'faktor_pendukung'  => 'Baris warisan tanpa source_type.',
            'created_at'        => $now,
            'updated_at'        => $now,
        ]);
        $idWarisan = (int) $this->db->insertID();
        $this->jejak['lakip_analisis_faktor'][] = $idWarisan;

        $petaRen2 = $model->getByTahunGrouped(
            (string) $scope['tahun'], 'opd', (int) $scope['opdScope'], 'renstra'
        );

        $this->samaDengan(
            'baris warisan tanpa sumber tetap terbaca sebagai Renstra',
            2,
            count($petaRen2[$renstraTargetNyata] ?? [])
        );

        $petaIku2 = $model->getByTahunGrouped(
            (string) $scope['tahun'], 'opd', (int) $scope['opdScope'], 'iku'
        );

        $this->samaDengan(
            'baris warisan tidak ikut ke peta IKU',
            0,
            count($petaIku2[$renstraTargetNyata] ?? [])
        );
        $this->samaDengan(
            'analisis IKU tetap utuh setelah baris warisan masuk',
            1,
            count($petaIku2[$indikatorLiveId] ?? [])
        );
    }

    /**
     * Setelah tahun dikunci, penyesuaian kebijakan adalah SATU-SATUNYA jalur
     * koreksi. Kalau ia salah kolom, tahun terkunci jadi benar-benar buntu.
     */
    private function ujiPenyesuaian(array $scope, int $snapshotId, int $indikatorLiveId): void
    {
        $model = new \App\Models\LakipPenyesuaianModel($this->db);

        if (! $this->db->fieldExists('source_type', 'lakip_penyesuaian')) {
            $this->gagal(
                'kolom sumber penyesuaian terpasang',
                'jalankan db/update_2026-08-24_penyesuaian_sumber_iku.sql di salinan ini'
            );

            return;
        }

        $isiDasar = [
            'jenis'             => 'realisasi',
            'nilai_asli'        => '76',
            'nilai_disesuaikan' => '80',
            'dasar_kebijakan'   => 'SK Uji 1/2031',
            'alasan'            => 'Uji penjaga sumber penyesuaian.',
        ];

        $idIku = $model->simpan($scope, $isiDasar + ['sumber' => 'iku', 'target_id' => $indikatorLiveId], null);
        $this->jejak['lakip_penyesuaian'][] = $idIku;

        $baris = $this->db->table('lakip_penyesuaian')->where('id', $idIku)->get()->getRowArray();

        $this->samaDengan('penyesuaian IKU memakai kolom kunci IKU', $indikatorLiveId, (int) $baris['iku_indikator_id']);
        $this->samaDengan('penyesuaian IKU tidak menodai kolom Renstra', null, $baris['renstra_target_id']);
        $this->samaDengan('penyesuaian IKU mencatat sumbernya', 'iku', (string) $baris['source_type']);
        $this->samaDengan('penyesuaian tercatat setelah final', 1, (int) $baris['setelah_final']);

        // Tautan ke baris beku: inilah yang dulu putus, karena pencariannya
        // memakai renstra_target_id yang pada snapshot IKU selalu NULL.
        $barisBeku = $this->db->table('lakip_snapshot_baris')
            ->select('id')->where('snapshot_id', $snapshotId)->get()->getRowArray();

        $this->samaDengan(
            'penyesuaian tertaut ke baris snapshot',
            (int) $barisBeku['id'],
            (int) $baris['snapshot_baris_id']
        );

        // Angka id yang sama, sumber berbeda: dulu keduanya dianggap satu baris
        // oleh UNIQUE, sehingga menyimpan yang satu mematikan yang lain.
        $idRenstra = $model->simpan(
            $scope,
            $isiDasar + ['sumber' => 'renstra', 'target_id' => $indikatorLiveId],
            null
        );
        $this->jejak['lakip_penyesuaian'][] = $idRenstra;

        $this->samaDengan(
            'penyesuaian IKU tetap aktif setelah penyesuaian Renstra ber-id sama',
            1,
            (int) $this->db->table('lakip_penyesuaian')->where('id', $idIku)
                ->get()->getRowArray()['aktif']
        );

        $petaIku = $model->petaAktif((string) $scope['tahun'], 'opd', (int) $scope['opdScope'], 'iku');
        $petaRen = $model->petaAktif((string) $scope['tahun'], 'opd', (int) $scope['opdScope'], 'renstra');

        $this->samaDengan(
            'peta sumber IKU memuat penyesuaian IKU',
            $idIku,
            (int) ($petaIku[$indikatorLiveId]['realisasi']['id'] ?? 0)
        );
        $this->samaDengan(
            'peta sumber Renstra tidak memuat penyesuaian IKU',
            $idRenstra,
            (int) ($petaRen[$indikatorLiveId]['realisasi']['id'] ?? 0)
        );
    }

    /* =========================================================
     * BENIH
     * =======================================================*/

    /** @return array{0:int,1:int,2:int} [revisiId, arsipIndikatorId, indikatorLiveId] */
    private function benihIku(): array
    {
        $now = date('Y-m-d H:i:s');

        // Indikator IKU BERJALAN: inilah yang jadi kunci realisasi, dan yang
        // harus tersimpan di snapshot — bukan id arsipnya.
        $this->db->table('iku_sasaran')->insert([
            'opd_id'      => self::OPD,
            'sasaran'     => '[UJI SNAPSHOT] sasaran',
            'tahun_mulai' => self::TAHUN,
            'tahun_akhir' => self::TAHUN,
            'created_at'  => $now,
            'updated_at'  => $now,
        ]);
        $sasaranLiveId = (int) $this->db->insertID();
        $this->jejak['iku_sasaran'][] = $sasaranLiveId;

        $this->db->table('iku_indikator')->insert([
            'iku_sasaran_id' => $sasaranLiveId,
            'indikator'      => '[UJI SNAPSHOT] indikator',
            'satuan'         => 'Persen',
            'urutan'         => 1,
            'created_at'     => $now,
            'updated_at'     => $now,
        ]);
        $indikatorLiveId = (int) $this->db->insertID();
        $this->jejak['iku_indikator'][] = $indikatorLiveId;

        // Arsip revisi yang membekukan redaksi indikator itu.
        $this->db->table('iku_revisi')->insert([
            'opd_id'              => self::OPD,
            'tahun_mulai'         => self::TAHUN,
            'tahun_akhir'         => self::TAHUN,
            'nomor'               => 9901,
            'nama'                => '[UJI SNAPSHOT] revisi',
            'berlaku_mulai_tahun' => self::TAHUN,
            'status'              => 'berlaku',
            'created_at'          => $now,
            'updated_at'          => $now,
        ]);
        $revisiId = (int) $this->db->insertID();
        $this->jejak['iku_revisi'][] = $revisiId;

        $this->db->table('iku_revisi_sasaran')->insert([
            'revisi_id'         => $revisiId,
            'sumber_sasaran_id' => $sasaranLiveId,
            'sasaran'           => '[UJI SNAPSHOT] sasaran',
            'tahun_mulai'       => self::TAHUN,
            'tahun_akhir'       => self::TAHUN,
            'urutan'            => 1,
            'jenis_perubahan'   => 'tetap',
            'created_at'        => $now,
            'updated_at'        => $now,
        ]);
        $arsipSasId = (int) $this->db->insertID();
        $this->jejak['iku_revisi_sasaran'][] = $arsipSasId;

        $this->db->table('iku_revisi_indikator')->insert([
            'revisi_id'             => $revisiId,
            'revisi_sasaran_id'     => $arsipSasId,
            'sumber_indikator_id'   => $indikatorLiveId,
            'indikator'             => '[UJI SNAPSHOT] indikator',
            'satuan_nama'           => 'Persen',
            'jenis_indikator'       => 'positif',
            'urutan'                => 1,
            'status'                => 'aktif',
            'jenis_perubahan'       => 'tetap',
            'perubahan_substansial' => 1,
            'created_at'            => $now,
            'updated_at'            => $now,
        ]);
        $arsipIndId = (int) $this->db->insertID();
        $this->jejak['iku_revisi_indikator'][] = $arsipIndId;

        $this->db->table('iku_revisi_target')->insert([
            'revisi_indikator_id' => $arsipIndId,
            'tahun'               => self::TAHUN,
            'target'              => '80',
            'created_at'          => $now,
            'updated_at'          => $now,
        ]);
        $this->jejak['iku_revisi_target'][] = (int) $this->db->insertID();

        return [$revisiId, $arsipIndId, $indikatorLiveId];
    }

    private function benihLakip(int $indikatorLiveId): int
    {
        $now = date('Y-m-d H:i:s');

        $this->db->table('lakip')->insert([
            'tahun'             => self::TAHUN,
            'opd_id'            => self::OPD,
            'mode'              => 'opd',
            'source_type'       => 'iku',
            'source_entity_id'  => $indikatorLiveId,
            'target_hitung'     => '80',
            'target_lalu'       => '',
            'capaian_lalu'      => '',
            'capaian_tahun_ini' => '76',
            'capaian_hitung'    => '95',
            'status'            => 'draft',
            'created_at'        => $now,
            'updated_at'        => $now,
        ]);

        $id = (int) $this->db->insertID();
        $this->jejak['lakip'][] = $id;

        return $id;
    }

    /**
     * Bahan snapshot dalam bentuk yang dihasilkan
     * LakipOpdController::barisHidupUntukSnapshot() untuk sumber IKU.
     */
    private function bahanIku(int $revisiId, int $indikatorLiveId, int $lakipId, int $arsipIndId): array
    {
        return [
            'rows' => [[
                'arsip_id'              => $arsipIndId,
                'indikator_id'          => $indikatorLiveId,
                'target_id'             => $indikatorLiveId,
                'indikator_sasaran'     => '[UJI SNAPSHOT] indikator',
                'satuan'                => 'Persen',
                'jenis_indikator'       => 'positif',
                'perubahan_substansial' => 1,
                'sasaran_id'            => 1,
                'sasaran'               => '[UJI SNAPSHOT] sasaran',
                'tahun'                 => self::TAHUN,
                'target_tahun_ini'      => '80',
                'opd_id'                => self::OPD,
                'nama_opd'              => 'OPD Uji',
            ]],
            'lakipMap' => [$indikatorLiveId => [
                'id'                => $lakipId,
                'capaian_tahun_ini' => '76',
                'target_hitung'     => '80',
                'target_lalu'       => '',
                'capaian_lalu'      => '',
                'capaian_hitung'    => '95',
                'status'            => 'draft',
            ]],
            'analisisMap'     => [],
            'efisiensiRows'   => [],
            'filter_status'   => '',
            'iku_revisi_id'   => $revisiId,
            'sumber_type'     => 'iku',
            'sumber_versi_id' => $revisiId,
        ];
    }

    private function bersihkan(): void
    {
        foreach (($this->jejak['lakip_snapshot'] ?? []) as $id) {
            $this->db->table('lakip_snapshot_program')->where('snapshot_id', $id)->delete();
            $this->db->table('lakip_snapshot_analisis')->where('snapshot_id', $id)->delete();
            $this->db->table('lakip_snapshot_baris')->where('snapshot_id', $id)->delete();
            $this->db->table('lakip_snapshot')->where('id', $id)->delete();
        }

        // Urutan hapus mengikuti ketergantungan, dari daun ke akar.
        foreach (['lakip_benchmark', 'lakip_analisis_faktor', 'lakip_penyesuaian', 'lakip', 'iku_revisi_target', 'iku_revisi_indikator', 'iku_revisi_sasaran',
                  'iku_revisi', 'iku_indikator', 'iku_sasaran'] as $tabel) {
            $id = $this->jejak[$tabel] ?? [];

            if ($id !== []) {
                $this->db->table($tabel)->whereIn('id', $id)->delete();
            }
        }
    }

    /* =========================================================
     * ALAT
     * =======================================================*/

    private function harusGagal(string $judul, callable $aksi, string $petikPesan): void
    {
        try {
            $aksi();
            $this->gagal($judul, 'berhasil tanpa galat');
        } catch (Throwable $e) {
            if (mb_stripos($e->getMessage(), $petikPesan) === false) {
                $this->gagal($judul, 'galatnya bukan yang dimaksud: ' . $e->getMessage());

                return;
            }

            $this->lulus($judul);
        }
    }

    private function samaDengan(string $judul, $harap, $dapat): void
    {
        if ($harap === $dapat) {
            $this->lulus($judul);

            return;
        }

        $this->gagal($judul, 'harap ' . var_export($harap, true) . ', dapat ' . var_export($dapat, true));
    }

    private function lulus(string $judul): void
    {
        $this->lulus++;
        CLI::write('  ' . CLI::color('OK  ', 'green') . $judul);
    }

    private function gagal(string $judul, string $sebab): void
    {
        $this->gagal++;
        CLI::write('  ' . CLI::color('GAGAL', 'red') . ' ' . $judul . ' — ' . $sebab);
    }
}
