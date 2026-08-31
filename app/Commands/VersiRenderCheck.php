<?php

namespace App\Commands;

use App\Models\DokumenVersiModel;
use App\Services\Version\ArsipRegistry;
use App\Services\Version\VersionAuditService;
use App\Services\Version\VersionCompareService;
use App\Services\Version\VersionResolver;
use App\Services\Version\VersionScope;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\SiteURI;
use CodeIgniter\HTTP\UserAgent;
use Config\Services;
use Throwable;

/**
 * Render seluruh view versi dengan data nyata, untuk menangkap galat runtime
 * yang tidak terlihat oleh `php -l`: variabel yang belum diset, pemanggilan
 * method pada null, dan include template yang salah jalur.
 *
 *   php spark versi:render --db dv_test
 */
class VersiRenderCheck extends BaseCommand
{
    protected $group       = 'SAKIP';
    protected $name        = 'versi:render';
    protected $description = 'Render view versi (index/form/lihat/banding) dengan data nyata.';
    protected $usage       = 'versi:render [--db <nama_basis>] [--modul rpjmd|renstra]';
    protected $options     = [
        '--db'    => 'Nama basis data lain (mis. salinan uji).',
        '--modul' => 'rpjmd (bawaan) atau renstra.',
    ];

    public function run(array $params)
    {
        $namaDb = $params['db'] ?? CLI::getOption('db');
        $modul  = $params['modul'] ?? CLI::getOption('modul') ?? VersionScope::MODUL_RPJMD;

        $cfg = config('Database')->default;

        if ($namaDb !== null && trim((string) $namaDb) !== '') {
            $cfg['database'] = trim((string) $namaDb);
        }

        $db = db_connect($cfg, false);
        CLI::write('Basis data: ' . CLI::color($db->getDatabase(), 'yellow'));

        // Cangkang halaman memilih direktori template dari role.
        session()->set(['role' => $modul === 'renstra' ? 'admin_opd' : 'admin_kab', 'isLoggedIn' => true]);

        // View memanggil csrf_field(); Security menuntut IncomingRequest,
        // sedangkan CLI menyediakan CLIRequest. Tanpa suntikan ini seluruh
        // render gagal karena keterbatasan CLI, bukan karena view-nya salah.
        Services::injectMock('request', new IncomingRequest(
            config('App'),
            new SiteURI(config('App')),
            null,
            new UserAgent()
        ));

        $versi    = new DokumenVersiModel($db);
        $resolver = new VersionResolver($db, $versi);

        if (! $versi->siap()) {
            CLI::error('Tabel dokumen_versi belum ada di basis tersebut.');

            return 1;
        }

        $lingkup = $versi->daftarLingkup($modul);

        if ($lingkup === []) {
            CLI::error('Tidak ada versi ' . $modul . ' untuk dirender.');

            return 1;
        }

        $l     = $lingkup[0];
        $scope = new VersionScope(
            $modul,
            (string) $l['scope'],
            $l['opd_id'] !== null ? (int) $l['opd_id'] : null,
            (int) $l['periode_mulai'],
            (int) $l['periode_akhir']
        );

        $nama    = $modul === 'renstra' ? 'Renstra' : 'RPJMD';
        $baseUrl = $modul === 'renstra' ? 'adminopd/renstra' : 'adminkab/rpjmd';
        $daftar  = $versi->daftar($scope);

        foreach ($daftar as &$d) {
            $d['badge']   = $resolver->badge($d);
            $d['rentang'] = $resolver->rentangTeks($d);
        }
        unset($d);

        $arsip = (new ArsipRegistry($db))->untuk($modul);
        $id    = (int) $daftar[0]['id'];

        // Baseline hasil SQL belum berisi arsip, sehingga yang terender hanya
        // cabang "versi kosong". Dengan --isi dibuat satu draft berisi salinan
        // data berjalan supaya cabang yang benar-benar dilihat pemakai ikut
        // teruji; draft itu dibuang lagi di akhir.
        $draftSementara = null;

        if (in_array('--isi', $_SERVER['argv'] ?? [], true)) {
            try {
                $hasil = (new \App\Services\Version\VersionDeepCopyService($db, $versi, new ArsipRegistry($db)))
                    ->buatVersi($scope, [
                        'label' => '[UJI RENDER] salinan kondisi berjalan',
                        // Bukan 1 Januari: tanggal itu sudah dipakai baseline V1,
                        // dan §6 melarang dua versi published mulai di tanggal sama.
                        'effective_from' => min($scope->periodeMulai() + 1, $scope->periodeAkhir()) . '-07-01',
                    ]);

                $draftSementara = (int) $hasil['version_id'];
                $id             = $draftSementara;

                $daftar = $versi->daftar($scope);

                foreach ($daftar as &$dd) {
                    $dd['badge']   = $resolver->badge($dd);
                    $dd['rentang'] = $resolver->rentangTeks($dd);
                }
                unset($dd);

                // Taruh draft berisi itu di posisi pertama agar dipakai render.
                usort($daftar, static fn ($p, $q) => ((int) $q['id'] === $draftSementara ? 1 : 0)
                    <=> ((int) $p['id'] === $draftSementara ? 1 : 0));

                CLI::write('  ' . CLI::color('INFO ', 'blue') . 'draft uji dibuat: '
                    . implode(', ', array_map(
                        static fn ($k, $v) => $k . '=' . $v,
                        array_keys($hasil['ringkasan']),
                        $hasil['ringkasan']
                    )));
            } catch (Throwable $e) {
                CLI::write('  ' . CLI::color('LEWAT', 'yellow') . '  --isi gagal: ' . $e->getMessage());
            }
        }

        // Draft uji WAJIB terbuang meski perakitan data atau render gagal di
        // tengah jalan. Tanpa finally, sekali galat akan meninggalkan versi
        // yatim di basis data yang dipakai bersama.
        $gagal = 0;

        try {
            $uji = [
                'versi/index' => [
                    'title' => 'Versi ' . $nama, 'judulHalaman' => 'Versi Dokumen ' . $nama,
                    'namaDokumen' => $nama, 'baseUrl' => $baseUrl,
                    'bolehBuat' => true, 'bolehAjukan' => true, 'bolehTetapkan' => true,
                    'blok' => [[
                        'periode'  => $scope->periodeMulai() . '-' . $scope->periodeAkhir(),
                        'scope'    => $scope, 'daftar' => $daftar,
                        'sekarang' => $daftar[0], 'konflik' => null,
                    ]],
                ],
                'versi/form' => [
                    'title' => 'Buat', 'judulHalaman' => 'Buat Versi Baru — ' . $nama,
                    'namaDokumen' => $nama, 'baseUrl' => $baseUrl, 'scope' => $scope,
                    'periode' => $scope->periodeMulai() . '-' . $scope->periodeAkhir(),
                    'nomorBerikut' => $versi->nomorBerikutnya($scope), 'sumberSalin' => $daftar,
                ],
                'versi/lihat' => [
                    'title' => $daftar[0]['label'], 'judulHalaman' => $daftar[0]['label'],
                    'namaDokumen' => $nama, 'baseUrl' => $baseUrl,
                    'versi' => $daftar[0], 'scope' => $scope,
                    'badge' => $daftar[0]['badge'], 'rentang' => $daftar[0]['rentang'],
                    'isi' => $arsip->isi($id), 'ringkas' => $arsip->ringkas($id),
                    'riwayat' => (new VersionAuditService($db))->riwayat($id),
                    'praTinjau' => null, 'galatValidasi' => [],
                    'bolehSunting' => false, 'bolehAjukan' => false,
                    'bolehTetapkan' => false, 'bolehBatalkan' => false,
                    'bolehKeterangan' => true, 'bolehTanggalBaseline' => false,
                    'bolehIsiBaseline' => true, 'dampakKosong' => '3 sasaran dan 7 indikator',
                    'bolehKoreksi' => true, 'koreksiTertunda' => [],
                    'daftarBanding' => $daftar,
                ],
            ];

            if ($draftSementara !== null) {
                // Cabang tombol per-tujuan hanya menyala pada draft yang boleh
                // disunting; tanpa render kedua ini, `$aksiTujuan` tidak pernah
                // benar-benar dijalankan dan galat di dalamnya lolos.
                $uji['versi/lihat (draft)'] = ['__view' => 'versi/lihat'] + array_merge(
                    $uji['versi/lihat'],
                    ['bolehSunting' => true, 'versi' => $versi->ambil($draftSementara)]
                );

                // Cabang tunjukan tampilan utama, dengan sengaja dibuat MENYIMPANG
                // dari versi yang berlaku menurut tanggal — justru peringatan
                // selisih itulah yang paling perlu dipastikan muncul.
                $uji['versi/lihat (tunjukan menyimpang)'] = ['__view' => 'versi/lihat'] + array_merge(
                    $uji['versi/lihat'],
                    [
                        'versi'               => $versi->ambil($draftSementara),
                        'bolehTunjuk'         => true,
                        'sudahDitunjuk'       => true,
                        'versiMenurutTanggal' => $daftar[1] ?? $daftar[0],
                    ]
                );

                $uji['versi/sunting'] = [
                    'title' => 'Sunting', 'judulHalaman' => 'Sunting Draft',
                    'namaDokumen' => $nama, 'baseUrl' => $baseUrl,
                    'versi' => $versi->ambil($draftSementara), 'scope' => $scope,
                    'modul' => $modul,
                    'isi' => $arsip->isi($draftSementara),
                    'tahun' => range($scope->periodeMulai(), $scope->periodeAkhir()),
                    'satuanOpsi' => $db->table('satuan')->select('id, satuan')->get()->getResultArray(),
                    'indikatorAsal' => [],
                ];
            }

            if ($draftSementara !== null) {
                $uji['versi/keterangan'] = [
                    'title' => 'Ubah Keterangan', 'judulHalaman' => 'Ubah Keterangan Versi',
                    'namaDokumen' => $nama, 'baseUrl' => $baseUrl,
                    'versi' => $versi->ambil($draftSementara), 'scope' => $scope,
                    'bolehPenuh' => true, 'bolehTanggal' => false,
                    'timeline' => $versi->publishedUrutMaju($scope),
                ];
            }

            // Halaman koreksi dirender memakai versi PUBLISHED (baseline), karena
            // koreksi memang hanya berlaku untuk versi yang sudah ditetapkan.
            $terbit = null;

            foreach ($daftar as $d) {
                if (($d['status'] ?? '') === 'published') {
                    $terbit = $d;
                    break;
                }
            }

            if ($terbit !== null) {
                $uji['versi/koreksi'] = [
                    'title' => 'Ajukan Koreksi', 'judulHalaman' => 'Ajukan Koreksi',
                    'namaDokumen' => $nama, 'baseUrl' => $baseUrl,
                    'versi' => $terbit,
                    'isi' => $arsip->isi((int) $terbit['id']),
                    'daftarPutih' => (new \App\Services\Version\VersionCorrectionService($db))->daftarPutih(),
                    'riwayat' => (new \App\Services\Version\VersionCorrectionService($db))->daftar((int) $terbit['id']),
                    'dipakaiLakip' => 0,
                ];
            }

            // Halaman verifikasi butuh pengajuan yang benar-benar berstatus
            // pending_approval, jadi draft uji ini diajukan. Statusnya tidak perlu
            // dikembalikan: barisnya memang dibuang di akhir.
            if ($draftSementara !== null) {
                try {
                    (new \App\Services\Version\VersionApprovalService($db, $versi))
                        ->ajukan($draftSementara, null);

                    $pengajuan = $versi->ambil($draftSementara);
                    $arsipUji  = (new ArsipRegistry($db))->untuk($modul);

                    $antrean = [];

                    foreach ($versi->menungguVerifikasi() as $p) {
                        $p['rentang']   = $resolver->rentangTeks($p);
                        $p['lama_hari'] = 0;
                        $antrean[]      = $p;
                    }

                    $uji['versi/verifikasi_index'] = [
                        'title' => 'Verifikasi', 'judulHalaman' => 'Verifikasi Pengajuan Versi Dokumen',
                        'antrean' => $antrean, 'koreksi' => [], 'modulBoleh' => [$modul],
                        'izinSunting' => [[
                            'id' => 1, 'modul' => $modul, 'opd_key' => (int) $scope->opdKey(),
                            'nama_opd' => 'OPD Uji', 'periode_mulai' => $scope->periodeMulai(),
                            'periode_akhir' => $scope->periodeAkhir(),
                            'alasan' => 'target indikator salah ketik',
                            'diminta_nama' => 'admin_opd', 'diminta_pada' => date('Y-m-d H:i:s'),
                        ]],
                        // Daftar izin yang SUDAH disetujui: satu-satunya tempat
                        // tombol Cabut dirender. Rutenya sudah lama ada tetapi
                        // tidak pernah punya layar, sehingga izin yang tak
                        // pernah ditutup memblokir lingkupnya tanpa jalan keluar.
                        'izinBerjalan' => [[
                            'id' => 2, 'modul' => $modul, 'opd_key' => (int) $scope->opdKey(),
                            'nama_opd' => 'OPD Uji', 'periode_mulai' => $scope->periodeMulai(),
                            'periode_akhir' => $scope->periodeAkhir(), 'version_id' => 9,
                            'alasan' => 'perbaikan rumusan',
                            'diputus_nama' => 'admin_kab', 'diputus_pada' => date('Y-m-d H:i:s'),
                        ]],
                        '__memuat' => ['Izin Sunting yang Sedang Terbuka', 'Cabut Izin'],
                    ];

                    $uji['versi/verifikasi_lihat'] = [
                        'title' => 'Verifikasi', 'judulHalaman' => 'Verifikasi Pengajuan',
                        'versi' => $pengajuan, 'scope' => $scope,
                        'namaOpd' => $pengajuan['opd_id'] === null ? 'Tingkat Kabupaten' : 'OPD Uji',
                        'badge' => $resolver->badge($pengajuan),
                        'rentang' => $resolver->rentangTeks($pengajuan),
                        'isi' => $arsipUji->isi($draftSementara),
                        'ringkas' => $arsipUji->ringkas($draftSementara),
                        'riwayat' => (new VersionAuditService($db))->riwayat($draftSementara),
                        'pembanding' => null, 'diff' => null, 'asal' => null,
                    ];
                } catch (Throwable $e) {
                    CLI::write('  ' . CLI::color('LEWAT', 'yellow') . '  verifikasi: ' . $e->getMessage());
                }
            }

            // Banding butuh dua versi; kalau cuma ada satu, bandingkan dengan dirinya
            // sendiri — yang justru menguji cabang "semua tetap".
            try {
                $b = (new VersionCompareService($db, $versi, new ArsipRegistry($db)))
                    ->banding($id, (int) ($daftar[1]['id'] ?? $id));

                $uji['versi/banding'] = [
                    'title' => 'Bandingkan', 'judulHalaman' => 'Bandingkan Versi ' . $nama,
                    'namaDokumen' => $nama, 'baseUrl' => $baseUrl, 'hasil' => $b,
                ];
            } catch (Throwable $e) {
                CLI::write('  ' . CLI::color('LEWAT', 'yellow') . '  versi/banding — ' . $e->getMessage());
            }

            // Form "Tambah Renstra" dipakai bersama oleh dua alur (lihat
            // RenstraVersiIsiTrait). Keduanya dirender: kosong dan terisi. Yang
            // terisi memakai tujuan arsip sungguhan, sehingga bentuk data yang
            // dikirim `tujuanUntukForm()` ikut teruji, bukan hanya markupnya.
            if ($modul === VersionScope::MODUL_RENSTRA && $draftSementara !== null) {
                $renstraArsip = new \App\Models\Versi\RenstraVersiModel($db);
                $tujuanArsip  = $db->table('renstra_versi_tujuan')
                    ->where('version_id', $draftSementara)->limit(1)->get()->getRowArray();

                $dasar = [
                    'title'          => 'Uji Render Form Renstra',
                    'rpjmd_sasaran'  => $db->table('rpjmd_sasaran')->select('id, sasaran_rpjmd')
                        ->limit(20)->get()->getResultArray(),
                    'satuan_options' => $db->table('satuan')->select('id, satuan')->get()->getResultArray(),
                    'current_opd'    => $db->table('opd')->where('id', $scope->opdId())->get()->getRowArray() ?? [],
                ];

                $uji['tambah_renstra (kosong)'] = ['__view' => 'adminOpd/renstra/tambah_renstra'] + $dasar;

                $uji['tambah_renstra (versi)'] = ['__view' => 'adminOpd/renstra/tambah_renstra'] + array_merge($dasar, [
                    'judulForm'    => 'Tambah Tujuan — uji',
                    'formAction'   => base_url($baseUrl . '/versi/tujuan/simpan/' . $draftSementara),
                    'kembaliUrl'   => base_url($baseUrl . '/versi/lihat/' . $draftSementara),
                    'hiddenExtra'  => ['arsip_tujuan_id' => ''],
                    'periodeKunci' => ['mulai' => $scope->periodeMulai(), 'akhir' => $scope->periodeAkhir()],
                    'catatanForm'  => 'Uji render.',
                ]);

                if ($tujuanArsip !== null) {
                    $uji['tambah_renstra (sunting)'] = ['__view' => 'adminOpd/renstra/tambah_renstra']
                        + array_merge($uji['tambah_renstra (versi)'], [
                            'hiddenExtra' => ['arsip_tujuan_id' => (int) $tujuanArsip['id']],
                            'isiAwal'     => $renstraArsip->tujuanUntukForm($draftSementara, (int) $tujuanArsip['id']),
                        ]);
                }
            }

            // Menu Renstra sekarang bisa membaca ARSIP sebuah versi memakai tabel
            // yang sama dengan kondisi berjalan. Keduanya dirender supaya cabang
            // "baca arsip" — yang memadamkan kolom Status/Aksi dan seluruh tombol
            // tulis — tidak pernah lolos tanpa dijalankan.
            if ($modul === VersionScope::MODUL_RENSTRA && $draftSementara !== null) {
                $barisVersi = $versi->ambil($draftSementara);
                $periodeTxt = $scope->periodeMulai() . '-' . $scope->periodeAkhir();
                $opdBaris   = $db->table('opd')->where('id', $scope->opdId())->get()->getRowArray() ?? [];

                $isiVersi = (new \App\Models\Versi\RenstraVersiModel($db))
                    ->bacaSepertiLive($draftSementara);

                $dasarMenu = [
                    'title'          => 'Renstra uji',
                    'current_opd'    => $opdBaris,
                    'periode_master' => [[
                        'tahun_mulai' => $scope->periodeMulai(),
                        'tahun_akhir' => $scope->periodeAkhir(),
                    ]],
                    'filters' => [
                        'misi' => '', 'tujuan' => '', 'rpjmd' => '',
                        'periode' => $periodeTxt, 'status' => '',
                    ],
                    'siklus'        => null,
                    'versi_pilihan' => [$barisVersi],
                ];

                // Bentuk data kedua cabang memang identik (itulah gunanya
                // bacaSepertiLive), jadi isi yang sama dipakai untuk menguji
                // cabang "kondisi berjalan" — yang menyalakan kembali kolom
                // Status/Aksi dan panel siklus.
                $uji['renstra menu (berjalan)'] = ['__view' => 'adminOpd/renstra/renstra']
                    + array_merge($dasarMenu, [
                        'renstra_data'  => $isiVersi,
                        'filter_source' => $isiVersi,
                        'versi_aktif'   => null,
                    ]);

                $uji['renstra menu (versi)'] = ['__view' => 'adminOpd/renstra/renstra']
                    + array_merge($dasarMenu, [
                        'renstra_data'  => $isiVersi,
                        'filter_source' => $isiVersi,
                        'versi_aktif'   => $barisVersi,
                    ]);

                // Tampilan yang datang dari TUNJUKAN, dan tunjukannya menyimpang
                // dari versi yang berlaku menurut tanggal. Inilah keadaan yang
                // paling mudah menyesatkan, jadi paling perlu diuji.
                // Tiga keadaan panel siklus. Yang terakhir paling penting:
                // "sedang disunting" menampilkan data berjalan yang SUDAH
                // menyimpang dari versi resmi, dan panel harus mengatakannya.
                $siklusUji = static fn (array $ubah): array => array_merge([
                    'versi' => null, 'status' => 'published', 'terkunci' => true,
                    'alasan' => 'Renstra sudah ditetapkan berlaku, sehingga terkunci.',
                    'boleh_ajukan' => false, 'boleh_tarik' => false,
                    'izin' => null, 'boleh_minta_izin' => false, 'sedang_disunting' => false,
                ], $ubah);

                $uji['renstra menu (terkunci)'] = ['__view' => 'adminOpd/renstra/renstra']
                    + array_merge($dasarMenu, [
                        'renstra_data'  => $isiVersi,
                        'filter_source' => $isiVersi,
                        'versi_aktif'   => null,
                        'siklus'        => $siklusUji([
                            'versi' => $barisVersi, 'boleh_minta_izin' => true,
                        ]),
                    ]);

                $uji['renstra menu (izin ditolak)'] = ['__view' => 'adminOpd/renstra/renstra']
                    + array_merge($dasarMenu, [
                        'renstra_data'  => $isiVersi,
                        'filter_source' => $isiVersi,
                        'versi_aktif'   => null,
                        'siklus'        => $siklusUji([
                            'versi' => $barisVersi, 'boleh_minta_izin' => true,
                            'izin'  => [
                                'id' => 1, 'status' => 'ditolak',
                                'alasan' => 'salah ketik target',
                                'catatan_keputusan' => 'Sertakan bukti perhitungannya.',
                            ],
                        ]),
                    ]);

                $uji['renstra menu (sedang disunting)'] = ['__view' => 'adminOpd/renstra/renstra']
                    + array_merge($dasarMenu, [
                        'renstra_data'  => $isiVersi,
                        'filter_source' => $isiVersi,
                        'versi_aktif'   => null,
                        'siklus'        => $siklusUji([
                            'versi' => $barisVersi, 'terkunci' => false, 'alasan' => null,
                            'boleh_ajukan' => true, 'sedang_disunting' => true,
                            'izin' => [
                                'id' => 2, 'status' => 'disetujui',
                                'alasan' => 'target indikator salah ketik',
                                'catatan_keputusan' => null,
                            ],
                        ]),
                    ]);

                // REGRESI: panel siklus tersembunyi (karena sedang membaca versi)
                // SEMENTARA keadaannya masih menawarkan izin sunting. Perpaduan
                // inilah yang dulu meledak "Undefined variable $skMulai", sebab
                // modalnya memakai nilai yang lahir di dalam panel.
                $uji['renstra menu (baca versi + boleh minta izin)'] = ['__view' => 'adminOpd/renstra/renstra']
                    + array_merge($dasarMenu, [
                        'renstra_data'  => $isiVersi,
                        'filter_source' => $isiVersi,
                        'versi_aktif'   => $barisVersi,
                        'siklus'        => $siklusUji([
                            'versi' => $barisVersi, 'boleh_minta_izin' => true,
                        ]),
                    ]);

                $uji['renstra menu (tunjukan menyimpang)'] = ['__view' => 'adminOpd/renstra/renstra']
                    + array_merge($dasarMenu, [
                        'renstra_data'          => $isiVersi,
                        'filter_source'         => $isiVersi,
                        'versi_aktif'           => $barisVersi,
                        'versi_dari_tunjukan'   => true,
                        'versi_menurut_tanggal' => $daftar[1] ?? $daftar[0],
                    ]);

                $uji['renstra cetak (versi)'] = ['__view' => 'adminOpd/renstra/renstra_cetak'] + [
                    'renstra_data' => $isiVersi,
                    'versi_aktif'  => $barisVersi,
                    'filters'      => $dasarMenu['filters'],
                    'periode'      => $periodeTxt,
                    'tahun_mulai'  => $scope->periodeMulai(),
                    'tahun_akhir'  => $scope->periodeAkhir(),
                    'nama_opd'     => $opdBaris['nama_opd'] ?? '',
                ];
            }

                // Halaman keputusan revisi IKU. Dirender dengan dampak yang
                // TIDAK kosong — cabang "akan dipensiunkan" adalah yang paling
                // penting muncul, dan justru yang paling mudah luput diuji
                // karena keadaan normalnya kosong.
                $uji['iku/verifikasi_revisi'] = [
                    'title'        => 'Verifikasi Revisi IKU',
                    'judulHalaman' => 'Verifikasi Revisi IKU — OPD Uji',
                    'namaOpd'      => 'OPD Uji',
                    'bolehPutus'   => true,
                    'years'        => range($scope->periodeMulai(), $scope->periodeAkhir()),
                    'revisi'       => [
                        'id' => 1, 'nomor' => 'UJI/001', 'nama' => 'Revisi uji render',
                        'status' => 'menunggu', 'opd_key' => (int) $scope->opdKey(),
                        'tahun_mulai' => $scope->periodeMulai(),
                        'tahun_akhir' => $scope->periodeAkhir(),
                        'berlaku_mulai_tahun' => $scope->periodeMulai() + 1,
                        'dasar_hukum' => 'SK Kepala Dinas', 'nomor_dasar' => '12/2026',
                        'catatan' => 'Menyesuaikan target tahun berjalan.',
                        'submitted_at' => date('Y-m-d H:i:s'),
                    ],
                    'isi' => [[
                        'sasaran'   => 'Sasaran uji render',
                        'indikator' => [[
                            'indikator' => 'Indikator uji render', 'satuan_nama' => 'Persen',
                            'jenis_perubahan' => 'revisi',
                            'target' => [$scope->periodeMulai() => '80'],
                        ]],
                    ], [
                        'sasaran'   => 'Sasaran tanpa indikator',
                        'indikator' => [],
                    ]],
                    'praTinjau' => [
                        'pembanding' => ['id' => 9, 'nomor' => 'UJI/000', 'nama' => 'Kondisi Awal'],
                        'tahun'      => ['mulai' => $scope->periodeMulai() + 1, 'sampai' => null],
                        'digeser'    => [['id' => 9]],
                        'baru'       => [['indikator' => 'Indikator baru', 'sasaran' => 'Sasaran uji render']],
                        'berubah'    => [[
                            'indikator' => 'Indikator uji render',
                            'sasaran'   => 'Sasaran uji render',
                            'selisih'   => ['target ' . $scope->periodeMulai() => ['lama' => '70', 'baru' => '80']],
                        ]],
                        'dihentikan' => [['indikator' => 'Indikator lama', 'sasaran' => 'Sasaran uji render']],
                    ],
                ];

                // ============ HALAMAN MODUL IKU ============
                //
                // Galat "Invalid file" pada include hanya muncul saat halamannya
                // benar-benar dibuka — satu berkas partial yang salah nama bisa
                // bersembunyi berbulan-bulan di halaman yang jarang diakses.
                // Karena itu seluruh layar IKU ikut dirender di sini, bukan
                // hanya yang berkaitan langsung dengan versi.
                $tahunIku = range($scope->periodeMulai(), $scope->periodeAkhir());
                $periodeIku = $scope->periodeMulai() . '-' . $scope->periodeAkhir();

                $satuanIku = $db->table('satuan')->select('id, satuan')->limit(20)->get()->getResultArray();

                $indikatorIku = [[
                    'id' => 1, 'indikator' => 'Indikator uji render', 'definisi' => 'Definisi uji',
                    'rumusan_perhitungan' => 'A / B x 100', 'satuan' => '1', 'satuan_nama' => 'Persen',
                    'sumber_data' => 'Laporan bidang', 'penanggung_jawab' => 'Bidang Uji',
                    'jenis_indikator' => 'positif', 'baseline' => '5', 'status' => 'selesai',
                    'target' => [$scope->periodeMulai() => '70'],
                ]];

                $sasaranIku = [[
                    'id' => 1, 'sasaran' => 'Sasaran uji render', 'nama_opd' => 'OPD Uji',
                    'tahun_mulai' => $scope->periodeMulai(), 'tahun_akhir' => $scope->periodeAkhir(),
                    'indikator' => $indikatorIku,
                ]];

                $revisiIku = [
                    'id' => 1, 'nomor' => 'UJI/001', 'nama' => 'Revisi uji render',
                    'status' => 'draft', 'opd_key' => (int) $scope->opdKey(),
                    'tahun_mulai' => $scope->periodeMulai(), 'tahun_akhir' => $scope->periodeAkhir(),
                    'berlaku_mulai_tahun' => $scope->periodeMulai() + 1,
                    'berlaku_sampai_tahun' => null, 'dasar_hukum' => 'SK Kepala Dinas',
                    'nomor_dasar' => '12/2026', 'catatan' => null, 'submitted_at' => null,
                    'disahkan_pada' => null, 'dibekukan_pada' => null, 'disahkan_oleh' => null,
                    'created_at' => date('Y-m-d H:i:s'),
                ];

                // Bentuk isi revisi: sama dengan isiRevisi(), targetnya berkunci tahun
                // dan bernilai baris (bukan skalar) — beda dari getMatrix().
                $isiRevisiUji = [[
                    'id' => 1, 'sasaran' => 'Sasaran uji render',
                    'indikator' => [array_merge($indikatorIku[0], [
                        'jenis_perubahan' => 'tetap', 'indikator_sebelumnya_id' => null,
                        'perubahan_substansial' => 0, 'catatan_perubahan' => null,
                        'target' => [$scope->periodeMulai() => ['target' => '70']],
                    ])],
                ]];

                $uji['iku menu (belum disahkan)'] = ['__view' => 'adminOpd/iku/iku'] + [
                    'title' => 'Indikator Kinerja Utama', 'role' => 'admin_opd',
                    'iku_data' => $sasaranIku, 'years' => $tahunIku,
                    'grouped_data' => [$periodeIku => ['period' => $periodeIku, 'punya_iku' => true]],
                    'selected_periode' => $periodeIku, 'is_lintas_opd' => false,
                    'revisiMenunggu' => null, 'revisiBerlaku' => null,
                ];

                // Periode yang Renstra-nya ada tetapi IKU-nya belum: tabel kosong,
                // dan yang harus muncul justru ajakan Sync.
                $uji['iku menu (periode belum ada IKU)'] = ['__view' => 'adminOpd/iku/iku'] + [
                    'title' => 'Indikator Kinerja Utama', 'role' => 'admin_opd',
                    'iku_data' => [], 'years' => $tahunIku,
                    'grouped_data' => [$periodeIku => ['period' => $periodeIku, 'punya_iku' => false]],
                    'selected_periode' => $periodeIku, 'is_lintas_opd' => false,
                    'revisiMenunggu' => null, 'revisiBerlaku' => null,
                ];

                $uji['iku menu (menunggu verifikasi)'] = ['__view' => 'adminOpd/iku/iku'] + [
                    'title' => 'Indikator Kinerja Utama', 'role' => 'admin_opd',
                    'iku_data' => $sasaranIku, 'years' => $tahunIku,
                    'grouped_data' => [$periodeIku => ['period' => $periodeIku, 'punya_iku' => true]],
                    'selected_periode' => $periodeIku, 'is_lintas_opd' => false,
                    'revisiMenunggu' => $revisiIku + ['submitted_at' => date('Y-m-d H:i:s')],
                    'revisiBerlaku' => null,
                ];

                $uji['iku menu (sudah disahkan)'] = ['__view' => 'adminOpd/iku/iku'] + [
                    'title' => 'Indikator Kinerja Utama', 'role' => 'admin_opd',
                    'iku_data' => $sasaranIku, 'years' => $tahunIku,
                    'grouped_data' => [$periodeIku => ['period' => $periodeIku, 'punya_iku' => true]],
                    'selected_periode' => $periodeIku, 'is_lintas_opd' => false,
                    'revisiMenunggu' => null, 'revisiBerlaku' => $revisiIku,
                ];

                $uji['iku keterangan (edit)'] = ['__view' => 'adminOpd/iku/edit_iku'] + [
                    'title' => 'Keterangan IKU', 'role' => 'admin_opd',
                    'iku' => $sasaranIku[0], 'satuan_options' => $satuanIku,
                    'opd_list' => [], 'is_lintas_opd' => false,
                ];

                $uji['iku revisi (daftar)'] = ['__view' => 'iku/revisi_index'] + [
                    'title' => 'Revisi IKU', 'role' => 'admin_opd', 'baseUrl' => 'adminopd/iku',
                    'daftar' => [
                        $revisiIku,
                        array_merge($revisiIku, ['id' => 2, 'status' => 'menunggu']),
                        array_merge($revisiIku, ['id' => 3, 'status' => 'berlaku']),
                    ],
                    'konflik' => [], 'bolehRevisi' => true, 'bolehSahkan' => false,
                    'perluVerifikasi' => true,
                ];

                // Daftar dengan KEADAAN IZIN per baris: tiga aksi yang berbeda
                // tegas pada revisi berlaku. Tanpa fixture ini ketiganya tidak
                // tersentuh render mana pun, karena baris 'berlaku' pada
                // fixture di atas sengaja tidak membawa izin_keadaan.
                $uji['iku revisi (daftar + aksi izin)'] = ['__view' => 'iku/revisi_index'] + [
                    'title' => 'Revisi IKU', 'role' => 'admin_opd', 'baseUrl' => 'adminopd/iku',
                    'daftar' => [
                        array_merge($revisiIku, ['id' => 3, 'status' => 'berlaku',
                            'izin_keadaan' => ['boleh_minta' => true, 'izin' => null,
                                'boleh_tarik' => false, 'sedang_disunting' => false]]),
                        array_merge($revisiIku, ['id' => 4, 'status' => 'berlaku',
                            'izin_keadaan' => ['boleh_minta' => false, 'izin' => ['id' => 7],
                                'boleh_tarik' => true, 'sedang_disunting' => false]]),
                        array_merge($revisiIku, ['id' => 5, 'status' => 'berlaku',
                            'izin_keadaan' => ['boleh_minta' => false, 'izin' => ['id' => 8],
                                'boleh_tarik' => false, 'sedang_disunting' => true]]),
                    ],
                    'konflik' => [], 'bolehRevisi' => true, 'bolehSahkan' => false,
                    'perluVerifikasi' => true,
                    // Ketiga aksi WAJIB benar-benar tercetak. Tanpa penegasan
                    // ini, render yang "berhasil" tetap bisa menghasilkan
                    // kolom aksi kosong — persis keluhan yang melahirkannya.
                    '__memuat' => ['Ajukan Izin Sunting', 'izin menunggu keputusan', 'izin terbuka'],
                ];

                // Form pembuatan revisi belum pernah punya fixture sama sekali,
                // padahal ia memuat dua blok yang seluruhnya digerakkan JS:
                // pemilih tahun berlaku dan pemilih versi Renstra.
                $periodeUji = [
                    '2025-2029' => [
                        'period' => '2025 - 2029', 'years' => range(2025, 2029),
                        'tahun_mulai' => 2025, 'tahun_akhir' => 2029,
                        'terpakai' => [
                            2025 => ['id' => 1, 'nomor' => '0', 'nama' => 'Kondisi Awal', 'status' => 'berlaku'],
                        ],
                        'bebas' => [2026, 2027, 2028, 2029],
                    ],
                ];

                $uji['iku revisi (buat)'] = ['__view' => 'iku/revisi_form'] + [
                    'title' => 'Buat Revisi IKU', 'role' => 'admin_opd',
                    'baseUrl' => 'adminopd/iku', 'periodeOpsi' => $periodeUji,
                    'versiRenstra' => [],
                    // Lingkup tanpa versi Renstra: blok sync TIDAK boleh muncul.
                    '__tanpa' => ['Sekalian salin isi Renstra ke draft ini'],
                ];

                $uji['iku revisi (buat + sync renstra)'] = ['__view' => 'iku/revisi_form'] + [
                    'title' => 'Buat Revisi IKU', 'role' => 'admin_opd',
                    'baseUrl' => 'adminopd/iku', 'periodeOpsi' => $periodeUji,
                    'versiRenstra' => [
                        '2025-2029' => [[
                            'id' => 324, 'version_no' => 2, 'label' => 'RENSTRA OPD #20 2025-2029',
                            'effective_from' => '2025-06-01', 'effective_to' => null,
                            'jumlah_sasaran' => 1,
                        ]],
                    ],
                    '__memuat' => ['Sekalian salin isi Renstra ke draft ini', 'renstra_versi'],
                ];

                $uji['iku revisi (sunting draft)'] = ['__view' => 'iku/revisi_sunting'] + [
                    'title' => 'Sunting Draft', 'role' => 'admin_opd', 'baseUrl' => 'adminopd/iku',
                    'revisi' => $revisiIku, 'isi' => $isiRevisiUji, 'years' => $tahunIku,
                    'satuan_options' => $satuanIku,
                    'indikatorLive' => [['id' => 9, 'indikator' => 'Indikator lama yang digantikan']],
                ];

                $uji['iku revisi (lihat)'] = ['__view' => 'iku/revisi_lihat'] + [
                    'title' => 'Isi Revisi', 'role' => 'admin_opd', 'baseUrl' => 'adminopd/iku',
                    'revisi' => $revisiIku, 'isi' => $isiRevisiUji, 'years' => $tahunIku,
                ];

                // Panel izin sunting: empat keadaan yang tombolnya berbeda
                // tegas. Panel ini hanya muncul pada revisi berlaku/superseded,
                // jadi tanpa fixture khusus ia TIDAK tersentuh render mana pun
                // — persis jenis layar yang dulu melahirkan "Undefined
                // variable" di produksi.
                $revisiBerlaku = array_merge($revisiIku, ['status' => 'berlaku']);

                $dasarLihat = [
                    'title' => 'Isi Revisi', 'role' => 'admin_opd', 'baseUrl' => 'adminopd/iku',
                    'revisi' => $revisiBerlaku, 'isi' => $isiRevisiUji, 'years' => $tahunIku,
                ];

                $keadaanKosong = [
                    'terkunci' => true, 'izin' => null, 'boleh_minta' => false,
                    'boleh_tarik' => false, 'sedang_disunting' => false, 'alasan' => 'Terkunci.',
                ];

                $izinContoh = [
                    'id' => 7, 'alasan' => 'Salah ketik indikator 1.',
                    'diminta_nama' => 'Operator Uji', 'diminta_pada' => '2026-08-24 09:00:00',
                ];

                $uji['iku revisi (terkunci, boleh minta izin)'] = ['__view' => 'iku/revisi_lihat']
                    + array_merge($dasarLihat, [
                        'keadaanIzin' => array_merge($keadaanKosong, ['boleh_minta' => true]),
                    ]);

                $uji['iku revisi (izin menunggu keputusan)'] = ['__view' => 'iku/revisi_lihat']
                    + array_merge($dasarLihat, [
                        'keadaanIzin' => array_merge($keadaanKosong, [
                            'izin' => $izinContoh, 'boleh_tarik' => true,
                        ]),
                    ]);

                $uji['iku revisi (izin disetujui, terbuka)'] = ['__view' => 'iku/revisi_lihat']
                    + array_merge($dasarLihat, [
                        'keadaanIzin' => [
                            'terkunci' => false, 'izin' => $izinContoh, 'boleh_minta' => false,
                            'boleh_tarik' => false, 'sedang_disunting' => true,
                            'alasan' => 'Izin sudah disetujui.',
                        ],
                    ]);

                // Form sunting punya DUA nada berbeda: draft biasa, dan
                // perbaikan arsip berlaku di bawah izin. Keduanya dirender.
                $uji['iku revisi (sunting di bawah izin)'] = ['__view' => 'iku/revisi_sunting']
                    + array_merge($uji['iku revisi (sunting draft)'], [
                        'revisi'      => $revisiBerlaku,
                        'keadaanIzin' => [
                            'terkunci' => false, 'izin' => $izinContoh, 'boleh_minta' => false,
                            'boleh_tarik' => false, 'sedang_disunting' => true, 'alasan' => null,
                        ],
                    ]);

                $uji['iku revisi (superseded, terkunci permanen)'] = ['__view' => 'iku/revisi_lihat']
                    + array_merge($dasarLihat, [
                        'revisi'      => array_merge($revisiIku, ['status' => 'superseded']),
                        'keadaanIzin' => $keadaanKosong,
                    ]);

                // ARSIP kini ikut bisa dimintakan izin, dan tombolnya berbunyi
                // berbeda: ia tidak pernah diterapkan ke IKU berjalan.
                $uji['iku revisi (arsip, boleh minta izin)'] = ['__view' => 'iku/revisi_lihat']
                    + array_merge($dasarLihat, [
                        'revisi'      => array_merge($revisiIku, ['status' => 'superseded']),
                        'keadaanIzin' => array_merge($keadaanKosong, [
                            'boleh_minta' => true, 'arsip' => true,
                        ]),
                    ]);

                $uji['iku revisi (arsip, izin terbuka)'] = ['__view' => 'iku/revisi_lihat']
                    + array_merge($dasarLihat, [
                        'revisi'      => array_merge($revisiIku, ['status' => 'superseded']),
                        'keadaanIzin' => [
                            'terkunci' => false, 'izin' => $izinContoh, 'boleh_minta' => false,
                            'boleh_tarik' => false, 'sedang_disunting' => true,
                            'arsip' => true, 'alasan' => 'Izin perbaikan arsip disetujui.',
                        ],
                        // Tombolnya WAJIB berbunyi "Tutup Izin", bukan
                        // "Terapkan ke IKU Berjalan" — menerapkan arsip justru
                        // memundurkan dokumen yang sedang dipakai.
                        '__memuat' => ['Selesai &amp; Tutup Izin'],
                        '__tanpa'  => ['Terapkan ke IKU Berjalan'],
                    ]);

                // ---------------------------------------------------------
                // Blok-blok yang lahir 2026-08-31. Sama seperti panel izin di
                // atas: tanpa fixture sendiri, ketiganya TIDAK tersentuh
                // render mana pun, karena masing-masing hanya muncul saat satu
                // variabel tertentu terisi.
                // ---------------------------------------------------------

                // "Tambah Sasaran" hanya dirender bila ada Tujuan Renstra yang
                // bisa dipilih; fixture lain tidak memasoknya sama sekali.
                $uji['iku revisi (sunting + tambah sasaran)'] = ['__view' => 'iku/revisi_sunting']
                    + array_merge($uji['iku revisi (sunting draft)'], [
                        'tujuanOptions' => [
                            ['id' => 3, 'tujuan' => 'Meningkatnya tata kelola pemerintahan'],
                        ],
                    ]);

                // Pemilih "Ubah Tahun" beserta tahun yang sudah terpakai.
                $uji['iku revisi (ubah tahun berlaku)'] = ['__view' => 'iku/revisi_lihat']
                    + array_merge($dasarLihat, [
                        'keadaanIzin'      => $keadaanKosong,
                        'bolehUbahBerlaku' => true,
                        'tahunBebas'       => [2027, 2028],
                        'tahunTerpakai'    => [
                            2026 => ['id' => 5, 'nomor' => '1', 'nama' => 'Revisi ke-1', 'status' => 'berlaku'],
                        ],
                    ]);

                $uji['iku revisi (boleh minta hapus)'] = ['__view' => 'iku/revisi_lihat']
                    + array_merge($dasarLihat, [
                        'keadaanIzin'  => $keadaanKosong,
                        'keadaanHapus' => [
                            'boleh_minta' => true, 'permohonan' => null,
                            'penghalang' => [], 'boleh_tarik' => false,
                            'alasan' => 'Penghapusan versi tidak bisa dibatalkan.',
                        ],
                    ]);

                $uji['iku revisi (permohonan hapus menunggu)'] = ['__view' => 'iku/revisi_lihat']
                    + array_merge($dasarLihat, [
                        'keadaanIzin'  => $keadaanKosong,
                        'keadaanHapus' => [
                            'boleh_minta' => false,
                            'permohonan'  => $izinContoh + ['status' => 'pending'],
                            'penghalang'  => [], 'boleh_tarik' => true,
                            'alasan' => 'Menunggu keputusan Admin Kabupaten.',
                        ],
                    ]);

                $uji['iku revisi (hapus terhalang rujukan)'] = ['__view' => 'iku/revisi_lihat']
                    + array_merge($dasarLihat, [
                        'keadaanIzin'  => $keadaanKosong,
                        'keadaanHapus' => [
                            'boleh_minta' => false, 'permohonan' => null,
                            'penghalang'  => ['baris realisasi LAKIP' => 4],
                            'boleh_tarik' => false,
                            'alasan' => 'Belum bisa dihapus — masih dirujuk: 4 baris realisasi LAKIP.',
                        ],
                    ]);

                // Layar sync: tiga keadaan yang perilakunya berbeda tegas.
                // Yang ketiga (sudah ada revisi berlaku) mengubah muara hasil
                // sync dari tabel berjalan menjadi draft revisi — cabang yang
                // paling menentukan dan paling mudah luput diuji.
                $kandidatSync = [[
                    'sumber_id' => 11, 'sumber_live_id' => 11,
                    'sasaran' => 'Sasaran sumber uji', 'induk' => 'Tujuan sumber uji',
                    'status' => 'selesai', 'iku_sasaran_id' => null, 'sudah_ada' => false,
                    'jumlah_baru' => 1, 'jumlah_berubah' => 1,
                    'indikator' => [
                        [
                            'sumber_id' => 21, 'sumber_live_id' => 21, 'sumber_sasaran_id' => 11,
                            'indikator' => 'Indikator baru dari sumber', 'definisi' => null,
                            'satuan' => '1', 'satuan_nama' => 'Persen', 'jenis_indikator' => 'positif',
                            'baseline' => '1', 'target' => [$scope->periodeMulai() => '70'],
                            'sudah_ada' => false, 'banding' => 'baru', 'selisih' => [], 'iku_id' => null,
                        ],
                        [
                            'sumber_id' => 22, 'sumber_live_id' => 22, 'sumber_sasaran_id' => 11,
                            'indikator' => 'Indikator yang targetnya bergeser', 'definisi' => null,
                            'satuan' => '1', 'satuan_nama' => 'Persen', 'jenis_indikator' => 'positif',
                            'baseline' => '2', 'target' => [$scope->periodeMulai() => '95'],
                            'sudah_ada' => true, 'banding' => 'berubah', 'iku_id' => 5,
                            'selisih' => [
                                'target ' . $scope->periodeMulai() => ['iku' => '70', 'sumber' => '95'],
                                'baseline' => ['iku' => '1', 'sumber' => '2'],
                            ],
                        ],
                        [
                            'sumber_id' => 23, 'sumber_live_id' => 23, 'sumber_sasaran_id' => 11,
                            'indikator' => 'Indikator yang sama persis', 'definisi' => null,
                            'satuan' => '1', 'satuan_nama' => 'Persen', 'jenis_indikator' => 'positif',
                            'baseline' => '3', 'target' => [$scope->periodeMulai() => '60'],
                            'sudah_ada' => true, 'banding' => 'sama', 'selisih' => [], 'iku_id' => 6,
                        ],
                    ],
                ]];

                $dasarSync = [
                    'title' => 'Sync IKU dari Renstra', 'role' => 'admin_opd',
                    'kandidat' => $kandidatSync,
                    'daftar_periode' => [$periodeIku => ['period' => $periodeIku]],
                    'periode' => ['key' => $periodeIku, 'period' => $periodeIku,
                                  'tahun_mulai' => $scope->periodeMulai(),
                                  'tahun_akhir' => $scope->periodeAkhir(),
                                  'years' => $tahunIku],
                    'years' => $tahunIku, 'nama_opd' => 'OPD Uji',
                    'sumber_label' => 'Renstra',
                    'action_url' => base_url('adminopd/iku/sync/simpan'),
                    'back_url'   => base_url('adminopd/iku'),
                    'filter_url' => base_url('adminopd/iku/sync'),
                    'versi_tersedia' => [], 'versi_dipilih' => null,
                    'tanpa_padanan' => [], 'ke_revisi' => false,
                    'revisi_berlaku' => null, 'draft_tersedia' => [],
                ];

                $versiSumberUji = [
                    'id' => 1, 'version_no' => 2, 'label' => 'Renstra hasil penyuntingan',
                    'effective_from' => $scope->periodeMulai() . '-01-01', 'effective_to' => null,
                    'jumlah_sasaran' => 3,
                ];

                $uji['iku sync (kondisi berjalan)'] = ['__view' => 'templates/iku/_sync', '__partial' => true] + $dasarSync;

                $uji['iku sync (pilih versi)'] = ['__view' => 'templates/iku/_sync', '__partial' => true]
                    + array_merge($dasarSync, [
                        'versi_tersedia' => [$versiSumberUji],
                        'versi_dipilih'  => $versiSumberUji,
                        'tanpa_padanan'  => [[
                            'iku_id' => 7, 'sasaran' => 'Sasaran uji render',
                            'indikator' => 'Indikator khas IKU', 'satuan' => 'Persen',
                            'dari_sumber' => false,
                        ]],
                    ]);

                $uji['iku sync (muara draft revisi)'] = ['__view' => 'templates/iku/_sync', '__partial' => true]
                    + array_merge($dasarSync, [
                        'versi_tersedia' => [$versiSumberUji],
                        'versi_dipilih'  => $versiSumberUji,
                        'ke_revisi'      => true,
                        'revisi_berlaku' => $revisiIku,
                        'draft_tersedia' => [$revisiIku],
                    ]);

                $uji['iku sync (belum ada draft penampung)'] = ['__view' => 'templates/iku/_sync', '__partial' => true]
                    + array_merge($dasarSync, [
                        'ke_revisi'      => true,
                        'revisi_berlaku' => $revisiIku,
                        'draft_tersedia' => [],
                    ]);

                // ============ LAYAR LAKIP (sumber IKU) ============
                $sumberLakipUji = [
                    'sumber' => 'iku',
                    'versi'  => ['id' => 1, 'version_no' => 1, 'label' => 'IKU uji render',
                                 'rekomendasi' => true],
                    'daftar_versi' => [
                        ['id' => 1, 'version_no' => 1, 'label' => 'IKU uji render', 'rekomendasi' => true],
                        ['id' => 2, 'version_no' => 2, 'label' => 'IKU revisi kedua', 'rekomendasi' => false],
                    ],
                    'pilihan_sumber' => [
                        ['nilai' => 'iku', 'label' => 'IKU Perangkat Daerah', 'bawaan' => true,
                         'tersedia' => true, 'jml_versi' => 2],
                        ['nilai' => 'renstra', 'label' => 'Renstra Perangkat Daerah', 'bawaan' => false,
                         'tersedia' => true, 'jml_versi' => 1],
                    ],
                    'alasan_wajib' => false,
                    'catatan'      => null,
                ];

                $barisLakipUji = [[
                    'sasaran' => 'Sasaran uji render',
                    'indikator_sasaran' => [[
                        'arsip_id' => 1, 'indikator_id' => 7, 'target_id' => 7,
                        'indikator_sasaran' => 'Indikator uji render', 'satuan' => 'Persen',
                        'jenis_indikator' => 'positif', 'sasaran' => 'Sasaran uji render',
                        'sasaran_id' => 3, 'tahun' => $scope->periodeMulai(),
                        'target_tahun_ini' => '80', 'opd_id' => (int) $scope->opdKey(),
                        'nama_opd' => 'OPD Uji',
                    ]],
                ]];

                $dasarLakip = [
                    'title' => 'LAKIP OPD - Uji', 'role' => 'admin_opd', 'mode' => 'opd',
                    'opdInfo' => ['nama_opd' => 'OPD Uji'],
                    'availableYears' => [$scope->periodeMulai()],
                    'dataSource' => $barisLakipUji,
                    'lakipMap' => [7 => [
                        'id' => 1, 'capaian_tahun_ini' => '76', 'target_hitung' => '80',
                        'capaian_hitung' => '95', 'status' => 'draft',
                        'source_type' => 'iku', 'source_entity_id' => 7,
                    ]],
                    'qsBase' => '?tahun=' . $scope->periodeMulai(),
                    'tahunAktif' => (string) $scope->periodeMulai(),
                    'filters' => ['tahun' => (string) $scope->periodeMulai(), 'status' => null],
                    'sumberLakip' => $sumberLakipUji,
                ];

                $uji['lakip menu (sumber iku)'] = ['__view' => 'adminOpd/lakip/lakip'] + $dasarLakip;

                $uji['lakip menu (versi bukan rekomendasi)'] = ['__view' => 'adminOpd/lakip/lakip']
                    + array_merge($dasarLakip, [
                        'sumberLakip' => array_merge($sumberLakipUji, [
                            'versi'        => ['id' => 2, 'version_no' => 2, 'label' => 'IKU revisi kedua',
                                               'rekomendasi' => false],
                            'alasan_wajib' => true,
                        ]),
                    ]);

                $uji['lakip menu (jatuh ke renstra)'] = ['__view' => 'adminOpd/lakip/lakip']
                    + array_merge($dasarLakip, [
                        'sumberLakip' => array_merge($sumberLakipUji, [
                            'sumber'  => 'renstra',
                            'catatan' => 'Belum ada versi IKU yang berlaku untuk tahun '
                                . $scope->periodeMulai() . ', jadi sumbernya sementara memakai RENSTRA.',
                        ]),
                    ]);

                $uji['lakip tambah (sumber iku)'] = ['__view' => 'adminOpd/lakip/tambah_lakip'] + [
                    'title' => 'Tambah LAKIP', 'role' => 'admin_opd',
                    'indikator' => [
                        'id' => 7, 'indikator_sasaran' => 'Indikator uji render',
                        'satuan' => 'Persen', 'jenis_indikator' => 'positif',
                        'sasaran' => 'Sasaran uji render',
                    ],
                    'target' => ['id' => 7, 'target' => '80', 'tahun' => $scope->periodeMulai(),
                                 'indikator_sasaran' => 'Indikator uji render',
                                 'satuan' => 'Persen', 'sasaran' => 'Sasaran uji render'],
                    'opdInfo' => ['nama_opd' => 'OPD Uji'],
                    'tahun' => (string) $scope->periodeMulai(),
                    'qsBase' => '', 'validation' => \Config\Services::validation(),
                    'sumberLakipForm' => [
                        'sumber' => 'iku', 'versi_id' => 1, 'entity_id' => 7, 'target_beku' => '80',
                    ],
                ];

                // ============ PANEL SNAPSHOT LAKIP ============
                //
                // Panel ini menyembunyikan dirinya bila $snapshotSiap false —
                // itulah sebabnya empat render LAKIP di atas TIDAK menyentuhnya
                // sama sekali. Variabel yang hilang di dalamnya (mis. pemilih
                // sumber yang hanya ada di layar OPD) baru meledak di layar
                // asli. Render di bawah menyalakan panelnya.
                $panelDasar = [
                    'snapshotSiap'       => true,
                    'snapshotDaftar'     => [],
                    'snapshotDipakai'    => false,
                    'snapshotTerkunci'   => false,
                    'snapshotLintasOpd'  => false,
                    'snapshotPesan'      => null,
                    'penyesuaianPeta'    => [],
                    'penyesuaianRiwayat' => [],
                    'bolehSnapshot'      => true,
                    'bolehFinalisasi'    => true,
                    'bolehPenyesuaian'   => true,
                    'snapshotBase'       => 'adminopd/lakip',
                    'addendumScope'      => [
                        'tahun'    => (string) $scope->periodeMulai(),
                        'mode'     => 'opd',
                        'opdScope' => (int) $scope->opdKey(),
                        'canWrite' => true,
                    ],
                ];

                $snapBeku = [
                    'id' => 1, 'versi' => 1, 'tahun' => (string) $scope->periodeMulai(),
                    'mode' => 'opd', 'opd_id' => (int) $scope->opdKey(),
                    'status' => 'draft', 'aktif' => 1,
                    'source_type' => 'iku', 'source_version_id' => 1,
                    'difinalkan_pada' => null,
                ];

                /* Dua panel di bawah tabel utama membaca `indikatorRows`
                   (baris DATAR), bukan `dataSource` yang sudah dikelompokkan.
                   Ketika baris datar itu tidak diteruskan, tabel utama tetap
                   terisi sementara keduanya melapor "belum ada indikator" —
                   kontradiksi yang tampak seperti data hilang. Fixture ini
                   menegaskan isinya, bukan cuma bahwa halamannya jadi. */
                $barisDatarUji = $barisLakipUji[0]['indikator_sasaran'];

                $uji['lakip panel bawah (terisi dari IKU)'] = [
                    '__view'   => 'adminOpd/lakip/lakip',
                    '__memuat' => ['Indikator uji render'],
                    '__tanpa'  => [
                        'Belum ada indikator pada tahun ini',
                        'Belum ada indikator pada tahun ' . $scope->periodeMulai() . ' untuk dibandingkan',
                    ],
                ] + array_merge($dasarLakip, $panelDasar, [
                    'snapshotAktif'      => null,
                    'indikatorRows'      => $barisDatarUji,
                    'addendumBase'       => 'adminopd/lakip',
                    'benchmarkSiap'      => true,
                    'benchmarkCanManage' => true,
                    'benchmarkCanView'   => true,
                    'benchmarkList'      => [[
                        'indikator_id'  => 7,
                        'nama'          => 'Indikator uji render',
                        'satuan'        => 'Persen',
                        'nilai_opd'     => 76.0,
                        'nilai_provinsi'=> 70.5,
                        'nilai_nasional'=> 68.25,
                        'sumber_provinsi' => 'BPS Lampung',
                        'sumber_nasional' => 'BPS RI',
                        'catatan'       => '',
                        'updated_at'    => '2026-08-24 09:00:00',
                        'benchmark_id'  => 1,
                        'ada_benchmark' => true,
                    ]],
                ]);

                /* admin_opd kini boleh mengisi benchmark lingkupnya sendiri
                   (`lakip_benchmark.manage_own`). Tombolnya harus benar-benar
                   dirender — izinnya sudah ada di basis data sejak migrasi
                   2026-08-20, tetapi kodenya lama tidak pernah membacanya. */
                $uji['lakip benchmark (opd boleh isi sendiri)'] = [
                    '__view'   => 'adminOpd/lakip/lakip',
                    '__memuat' => ['bm-modal'],
                ] + array_merge($dasarLakip, $panelDasar, [
                    'snapshotAktif'      => null,
                    'indikatorRows'      => $barisDatarUji,
                    'addendumBase'       => 'adminopd/lakip',
                    'benchmarkSiap'      => true,
                    'benchmarkCanManage' => true,
                    'benchmarkCanView'   => true,
                    'benchmarkList'      => [[
                        'indikator_id' => 7, 'nama' => 'Indikator uji render',
                        'satuan' => 'Persen', 'nilai_opd' => null,
                        'nilai_provinsi' => null, 'nilai_nasional' => null,
                        'sumber_provinsi' => '', 'sumber_nasional' => '', 'catatan' => '',
                        'updated_at' => '', 'benchmark_id' => 0, 'ada_benchmark' => false,
                    ]],
                ]);

                // (a) belum ada snapshot — tombol "Siapkan" tampil
                $uji['lakip snapshot (belum ada)'] = ['__view' => 'adminOpd/lakip/lakip']
                    + array_merge($dasarLakip, $panelDasar, ['snapshotAktif' => null]);

                // (b) snapshot draft bersumber IKU, layar juga IKU -> tidak ada peringatan
                $uji['lakip snapshot (draft, sumber sama)'] = ['__view' => 'adminOpd/lakip/lakip']
                    + array_merge($dasarLakip, $panelDasar, ['snapshotAktif' => $snapBeku]);

                // (c) snapshot dibekukan dari IKU tetapi layar menampilkan Renstra:
                //     peringatan "Sinkronkan akan menggantinya" harus muncul
                $uji['lakip snapshot (sumber layar beda)'] = ['__view' => 'adminOpd/lakip/lakip']
                    + array_merge($dasarLakip, $panelDasar, [
                        'snapshotAktif' => $snapBeku,
                        'sumberLakip'   => array_merge($sumberLakipUji, ['sumber' => 'renstra']),
                    ]);

                // (d) tahun terkunci
                $uji['lakip snapshot (terkunci)'] = ['__view' => 'adminOpd/lakip/lakip']
                    + array_merge($dasarLakip, $panelDasar, [
                        'snapshotAktif'     => array_merge($snapBeku, [
                            'status' => 'final', 'difinalkan_pada' => '2026-01-31 09:00:00',
                        ]),
                        'snapshotTerkunci'  => true,
                        'snapshotDipakai'   => true,
                    ]);

                // (e) panel yang sama di layar AdminKab, yang TIDAK punya
                //     $sumberLakip sama sekali — persis kondisi yang dulu
                //     melahirkan "Undefined variable".
                $uji['lakip snapshot (adminkab tanpa pemilih sumber)'] = ['__view' => 'lakip/snapshot_panel', '__partial' => true]
                    + array_merge($panelDasar, [
                        'snapshotAktif' => array_merge($snapBeku, [
                            'mode' => 'kabupaten', 'opd_id' => 0,
                            'source_type' => 'rpjmd', 'source_version_id' => 3,
                        ]),
                        'snapshotBase'  => 'adminkab/lakip',
                        'addendumScope' => [
                            'tahun'    => (string) $scope->periodeMulai(),
                            'mode'     => 'kabupaten',
                            'opdScope' => null,
                            'canWrite' => true,
                        ],
                    ]);

            foreach ($uji as $view => $data) {
                try {
                    // Satu view boleh diuji lebih dari sekali dengan keadaan
                    // berbeda; kuncinya dibuat unik, jalur aslinya di `__view`.
                    $jalur = $data['__view'] ?? $view;

                    // Partial tidak punya cangkang <html>; menuntutnya di sini
                    // akan menandai berkas yang justru sehat sebagai gagal.
                    $partial = ! empty($data['__partial']);

                    /* `__memuat` / `__tanpa`: penegasan ISI, bukan sekadar
                       "halamannya jadi". Render yang hanya memeriksa cangkang
                       HTML meloloskan panel yang tampil rapi tetapi kosong —
                       persis cacat "Belum ada indikator pada tahun ini" yang
                       muncul saat baris datar tidak diteruskan ke view. */
                    $memuat = (array) ($data['__memuat'] ?? []);
                    $tanpa  = (array) ($data['__tanpa'] ?? []);

                    unset($data['__view'], $data['__partial'], $data['__memuat'], $data['__tanpa']);

                    // Sebagian template menulis langsung ke keluaran; dibungkus
                    // buffer supaya HTML-nya tidak membanjiri terminal.
                    /* `saveData: false` WAJIB. Bawaan CI4 menyimpan data
                       render ke instance View yang dipakai bersama, sehingga
                       variabel dari fixture sebelumnya bocor ke fixture
                       berikutnya. Efeknya menyesatkan dua arah: layar yang
                       seharusnya kosong tampak terisi, dan cacat "variabel
                       tidak terdefinisi" tertutupi oleh sisa render tetangga. */
                    ob_start();
                    $html = view($jalur, $data, ['saveData' => false]);
                    ob_end_clean();

                    if (! $partial && ! str_contains($html, '</html>')) {
                        throw new \RuntimeException('HTML tidak lengkap (cangkang tidak tertutup)');
                    }

                    if ($partial && trim($html) === '') {
                        throw new \RuntimeException('Partial tidak menghasilkan apa pun');
                    }

                    foreach ($memuat as $petik) {
                        if (! str_contains($html, $petik)) {
                            throw new \RuntimeException('tidak memuat: "' . $petik . '"');
                        }
                    }

                    foreach ($tanpa as $petik) {
                        if (str_contains($html, $petik)) {
                            throw new \RuntimeException('seharusnya TIDAK memuat: "' . $petik . '"');
                        }
                    }

                    CLI::write('  ' . CLI::color('OK   ', 'green') . $view
                        . '  (' . number_format(strlen($html)) . ' byte)');
                } catch (Throwable $e) {
                    while (ob_get_level() > 0) {
                        ob_end_clean();
                    }

                    $gagal++;
                    CLI::write('  ' . CLI::color('GAGAL', 'red') . ' ' . $view . ' — ' . $e->getMessage());
                    CLI::write('        ' . basename($e->getFile()) . ':' . $e->getLine());
                }
            }
        } finally {
            if ($draftSementara !== null) {
                // Jejak audit ber-FK RESTRICT, jadi harus dibuang lebih dulu.
                $db->table('version_submission_history')->where('version_id', $draftSementara)->delete();
                $db->table('dokumen_versi')->where('id', $draftSementara)->delete();
                CLI::write('  ' . CLI::color('INFO ', 'blue') . 'draft uji dibersihkan.');
            }
        }

        return $gagal > 0 ? 1 : 0;
    }
}
