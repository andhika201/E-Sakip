<?php

namespace App\Commands;

use App\Models\Opd\IkuRevisiModel;
use App\Services\Version\IzinSuntingService;
use App\Services\Version\VersionScope;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Throwable;

/**
 * Uji izin sunting revisi IKU yang sudah berlaku.
 *
 *   php spark iku:jaga-izin --db e-sakip_7
 *
 * Alurnya meminjam mesin Renstra apa adanya (`dokumen_izin_sunting` +
 * IzinSuntingService), jadi yang diuji di sini BUKAN mesinnya, melainkan
 * empat hal yang khas IKU dan tidak akan ketahuan dari uji Renstra:
 *
 *   1. Arsip yang sudah berlaku tetap tertutup tanpa izin — pintu terakhirnya
 *      ada di model, bukan hanya di controller.
 *   2. Izin melekat pada SATU revisi, bukan pada periodenya. Satu periode bisa
 *      berisi beberapa revisi; izin untuk revisi 2 tidak boleh ikut membuka
 *      revisi 1.
 *   3. Revisi `superseded` tetap tertutup walau ada izin: ia sudah digantikan,
 *      dan mengoreksinya berarti memperbaiki dokumen yang bukan acuan siapa pun
 *      sementara yang berlaku dibiarkan salah.
 *   4. Menyunting arsip TIDAK otomatis mengubah IKU berjalan. Penerapannya
 *      langkah tersendiri — kalau tidak, arsip dan tabel live bisa berbeda isi
 *      tanpa satu pun galat.
 *
 * Perintah ini menulis, jadi ia MENOLAK berjalan di basis data aplikasi.
 */
class IkuIzinSuntingCheck extends BaseCommand
{
    protected $group       = 'Versioning';
    protected $name        = 'iku:jaga-izin';
    protected $description = 'Uji izin sunting revisi IKU yang sudah berlaku.';
    protected $usage       = 'iku:jaga-izin --db <salinan>';
    protected $arguments   = [];
    protected $options     = ['--db' => 'basis data salinan (wajib)'];

    private const OPD   = 11;
    private const MULAI = 2040;
    private const AKHIR = 2044;

    private $db;
    private int $lulus = 0;
    private int $gagal = 0;

    /** @var array<string, int[]> */
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

    private function jalankan(): void
    {
        $izin = new IzinSuntingService($this->db);

        if (! $izin->siap()) {
            $this->gagal('tabel izin sunting tersedia', 'dokumen_izin_sunting belum ada di salinan ini');

            return;
        }

        $model = new IkuRevisiModel($this->db);
        $scope = new VersionScope(
            VersionScope::MODUL_IKU,
            VersionScope::SCOPE_OPD,
            self::OPD,
            self::MULAI,
            self::AKHIR
        );

        [$revisiSatu, $arsipSatu] = $this->benihRevisi(1, IkuRevisiModel::STATUS_SUPERSEDED);
        [$revisiDua, $arsipDua]   = $this->benihRevisi(2, IkuRevisiModel::STATUS_BERLAKU);

        $suntingan = [$arsipDua => ['indikator' => 'Indikator hasil perbaikan', 'jenis_perubahan' => 'revisi']];

        // --- 1. tanpa izin, arsip tetap tertutup ---
        $this->harusGagal(
            'arsip berlaku menolak disunting tanpa izin',
            static fn () => $model->simpanSuntinganDraft($revisiDua, $suntingan),
            'di bawah izin'
        );

        // --- 2. izin diajukan lalu disetujui ---
        $izinId = $izin->ajukan($scope, 'Salah ketik pada indikator 1.', null, $revisiDua);
        $this->jejak['dokumen_izin_sunting'][] = $izinId;

        $berjalan = $izin->berjalan($scope);

        $this->samaDengan('permohonan tercatat menunggu', IzinSuntingService::STATUS_PENDING,
            (string) ($berjalan['status'] ?? ''));
        $this->samaDengan('permohonan melekat pada revisi yang diminta', $revisiDua,
            (int) ($berjalan['version_id'] ?? 0));

        // Menunggu keputusan BUKAN izin. Kalau di sini sudah terbuka, seluruh
        // antrean verifikasi jadi hiasan.
        $this->harusGagal(
            'menunggu keputusan belum membuka kunci',
            static fn () => $model->simpanSuntinganDraft($revisiDua, $suntingan),
            'di bawah izin'
        );

        $izin->setujui($izinId, null, 'Silakan diperbaiki.');

        $this->samaDengan('izin berstatus disetujui', IzinSuntingService::STATUS_DISETUJUI,
            (string) ($izin->berjalan($scope)['status'] ?? ''));

        // --- 3. dengan izin, penyuntingan lolos ---
        $model->simpanSuntinganDraft($revisiDua, $suntingan, [], true);

        $this->samaDengan(
            'isi arsip berubah setelah disunting',
            'Indikator hasil perbaikan',
            (string) $this->db->table('iku_revisi_indikator')->where('id', $arsipDua)
                ->get()->getRowArray()['indikator']
        );

        // --- 4. IKU berjalan BELUM ikut berubah ---
        $liveSebelum = (string) $this->db->table('iku_indikator')
            ->where('revisi_id', $revisiDua)->get()->getRowArray()['indikator'];

        $this->samaDengan(
            'IKU berjalan belum ikut berubah sebelum diterapkan',
            'Indikator revisi 2',
            $liveSebelum
        );

        $model->terapkanUlang($revisiDua);

        $this->samaDengan(
            'IKU berjalan mengikuti arsip setelah diterapkan ulang',
            'Indikator hasil perbaikan',
            (string) $this->db->table('iku_indikator')
                ->where('revisi_id', $revisiDua)->get()->getRowArray()['indikator']
        );

        // --- 5. ARSIP (superseded) ikut bisa dibetulkan di bawah izin ---
        //
        // Aturan lamanya: superseded terkunci PERMANEN. Itu dicabut, karena
        // arsip-lah yang dibaca LAKIP tahun-tahun lampau — salah ketik di sana
        // tetap tercetak pada laporan tahun itu, dan membetulkannya di revisi
        // terkini tidak mengubah apa pun.
        //
        // Yang tetap dijaga ada dua, dan keduanya diuji di bawah:
        //   * tanpa izin, arsip TETAP menolak disunting;
        //   * hasil suntingan arsip TIDAK pernah menyentuh IKU berjalan
        //     (terapkanUlang menolaknya — lihat pemeriksaan terakhir).
        $this->harusGagal(
            'arsip menolak disunting TANPA izin',
            static fn () => $model->simpanSuntinganDraft(
                $revisiSatu,
                [$arsipSatu => ['indikator' => 'Coba bongkar arsip lama', 'jenis_perubahan' => 'revisi']],
                [],
                false
            ),
            'di bawah izin'
        );

        $model->simpanSuntinganDraft(
            $revisiSatu,
            [$arsipSatu => ['indikator' => 'Arsip lama dibetulkan', 'jenis_perubahan' => 'revisi']],
            [],
            true
        );

        $this->samaDengan(
            'arsip BISA dibetulkan di bawah izin',
            'Arsip lama dibetulkan',
            (string) $this->db->table('iku_revisi_indikator')
                ->where('id', $arsipSatu)->get()->getRowArray()['indikator']
        );

        $this->samaDengan(
            'IKU berjalan TIDAK ikut berubah oleh perbaikan arsip',
            0,
            (int) $this->db->table('iku_indikator')
                ->like('indikator', 'Arsip lama dibetulkan', 'after')->countAllResults()
        );

        // --- 6. izin ditutup, kunci kembali terpasang ---
        $izin->selesaikan($scope);

        $this->samaDengan('tidak ada izin berjalan setelah ditutup', null, $izin->berjalan($scope));

        $this->harusGagal(
            'kunci kembali terpasang setelah izin ditutup',
            static fn () => $model->simpanSuntinganDraft($revisiDua, $suntingan),
            'di bawah izin'
        );

        // --- 7. terapkanUlang menolak revisi yang bukan berlaku ---
        $this->harusGagal(
            'terapkan ulang menolak revisi superseded',
            static fn () => $model->terapkanUlang($revisiSatu),
            'sedang berlaku'
        );
    }

    /** @return array{0:int,1:int} [revisiId, arsipIndikatorId] */
    private function benihRevisi(int $nomor, string $status): array
    {
        $now = date('Y-m-d H:i:s');

        $this->db->table('iku_sasaran')->insert([
            'opd_id'      => self::OPD,
            'sasaran'     => '[UJI IZIN] sasaran ' . $nomor,
            'tahun_mulai' => self::MULAI,
            'tahun_akhir' => self::AKHIR,
            'urutan'      => $nomor,
            'created_at'  => $now,
            'updated_at'  => $now,
        ]);
        $sasaranLiveId = (int) $this->db->insertID();
        $this->jejak['iku_sasaran'][] = $sasaranLiveId;

        $this->db->table('iku_revisi')->insert([
            'opd_id'               => self::OPD,
            'tahun_mulai'          => self::MULAI,
            'tahun_akhir'          => self::AKHIR,
            'nomor'                => 9910 + $nomor,
            'nama'                 => '[UJI IZIN] revisi ' . $nomor,
            'berlaku_mulai_tahun'  => self::MULAI + $nomor - 1,
            'berlaku_sampai_tahun' => $status === IkuRevisiModel::STATUS_SUPERSEDED ? self::MULAI : null,
            'status'               => $status,
            'created_at'           => $now,
            'updated_at'           => $now,
        ]);
        $revisiId = (int) $this->db->insertID();
        $this->jejak['iku_revisi'][] = $revisiId;

        $this->db->table('iku_indikator')->insert([
            'iku_sasaran_id' => $sasaranLiveId,
            'indikator'      => 'Indikator revisi ' . $nomor,
            'satuan'         => 'Persen',
            'urutan'         => 1,
            'revisi_id'      => $revisiId,
            'created_at'     => $now,
            'updated_at'     => $now,
        ]);
        $indikatorLiveId = (int) $this->db->insertID();
        $this->jejak['iku_indikator'][] = $indikatorLiveId;

        $this->db->table('iku_revisi_sasaran')->insert([
            'revisi_id'         => $revisiId,
            'sumber_sasaran_id' => $sasaranLiveId,
            'sasaran'           => '[UJI IZIN] sasaran ' . $nomor,
            'tahun_mulai'       => self::MULAI,
            'tahun_akhir'       => self::AKHIR,
            'urutan'            => 1,
            'jenis_perubahan'   => 'tetap',
            'created_at'        => $now,
            'updated_at'        => $now,
        ]);
        $arsipSasId = (int) $this->db->insertID();
        $this->jejak['iku_revisi_sasaran'][] = $arsipSasId;

        $this->db->table('iku_revisi_indikator')->insert([
            'revisi_id'           => $revisiId,
            'revisi_sasaran_id'   => $arsipSasId,
            'sumber_indikator_id' => $indikatorLiveId,
            'indikator'           => 'Indikator revisi ' . $nomor,
            'satuan'              => 'Persen',
            'urutan'              => 1,
            'status'              => 'aktif',
            'jenis_perubahan'     => 'tetap',
            'created_at'          => $now,
            'updated_at'          => $now,
        ]);

        return [$revisiId, (int) $this->db->insertID()];
    }

    private function bersihkan(): void
    {
        foreach (($this->jejak['iku_revisi'] ?? []) as $id) {
            $idInd = array_column($this->db->table('iku_revisi_indikator')
                ->select('id')->where('revisi_id', $id)->get()->getResultArray(), 'id');

            if ($idInd !== []) {
                $this->db->table('iku_revisi_target')->whereIn('revisi_indikator_id', $idInd)->delete();
            }

            $this->db->table('iku_revisi_indikator')->where('revisi_id', $id)->delete();
            $this->db->table('iku_revisi_sasaran')->where('revisi_id', $id)->delete();
        }

        // `terapkanUlang` bisa melahirkan baris live baru di luar jejak awal;
        // pembersihannya karena itu memakai revisi_id, bukan daftar id.
        foreach (($this->jejak['iku_revisi'] ?? []) as $id) {
            $this->db->table('iku_indikator')->where('revisi_id', $id)->delete();
            $this->db->table('iku_sasaran')->where('revisi_id', $id)->delete();
        }

        foreach (['dokumen_izin_sunting', 'iku_indikator', 'iku_sasaran', 'iku_revisi'] as $tabel) {
            $id = $this->jejak[$tabel] ?? [];

            if ($id !== []) {
                $this->db->table($tabel)->whereIn('id', $id)->delete();
            }
        }
    }

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
