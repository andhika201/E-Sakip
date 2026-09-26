<?php

namespace App\Controllers\AdminOpd;

use App\Controllers\BaseController;
use App\Exceptions\AturanBisnis;
use App\Models\CascadingModel;
use App\Models\Concerns\TransaksiAman;
use App\Models\Ikp\CascadingIndikatorTargetModel;
use App\Models\Ikp\CascadingPemilikModel;
use App\Models\Ikp\IkpModel;
use App\Models\OpdModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use Throwable;

/**
 * =====================================================================
 * PEMILIK KINERJA (SAMPAI PELAKSANA) — AKSARA+
 *
 * Satu layar untuk menuntaskan "SAKIP sampai pelaksana": setiap simpul
 * pohon kinerja OPD (Eselon III → Eselon IV/JF → Pelaksana) diberi PEMILIK
 * (pegawai nyata, per tahun), dan setiap indikatornya diberi SATUAN dan
 * TARGET TAHUNAN. Dari sinilah eKin menarik RHK tiap pegawai.
 *
 * Yang dibaca/ditulis:
 *   - pohon : cascading_sasaran_opd + cascading_indikator_opd (BACA SAJA;
 *             struktur pohon tetap dikelola menu Cascading)
 *   - pemilik simpul       : cascading_pemilik (per tahun)
 *   - satuan indikator     : cascading_indikator_opd.satuan (kolom lama yang
 *                            selama ini kosong di seluruh baris)
 *   - target tahunan       : cascading_indikator_target (per tahun, + metode,
 *                            + tautan opsional ke IKP)
 *
 * MENGAPA pemilik Eselon II tidak disimpan di sini: Eselon II bukan baris
 * cascading (ia = IKU OPD), dan pemiliknya sudah tercatat resmi sebagai
 * pihak_1 PK JPT (atau PK Camat) tahun itu. Menyalinnya ke cascading_pemilik
 * hanya membuat dua sumber kebenaran yang cepat atau lambat berselisih.
 *
 * MENGAPA layar ini TIDAK menumpang CascadingController: controller itu
 * sudah 1.800+ baris dengan peta izin sendiri (cascading_opd.*). Pemilik
 * kinerja punya izin sendiri (pemilik_kinerja.view|update), sehingga admin
 * bisa diberi hak menetapkan pemilik tanpa ikut bisa membongkar pohon.
 *
 * Aturan simpul tersembunyi mengikuti CascadingModel::getCascadingMatrixByOpd
 * (akar IKU): Eselon III hanya yang berjangkar indikator IKU yang BELUM
 * dihentikan (`dihentikan_pada IS NULL`) pada periode terpilih; Eselon IV
 * lewat `es3_indikator_id` = indikator Eselon III; Pelaksana lewat
 * `es3_indikator_id` = indikator Eselon IV (kolom itu bermakna "indikator
 * induk"). Simpul di bawah indikator IKU yang dihentikan TIDAK tampil di
 * menu Cascading, maka juga tidak tampil di sini.
 * =====================================================================
 */
class PemilikKinerjaController extends BaseController
{
    use TransaksiAman;

    protected $helpers = ['cascading_label', 'ikp'];

    /** @var \CodeIgniter\Database\BaseConnection */
    protected $db;

    /**
     * OPD yang pegawainya tercatat di id OPD lain (lihat kritik 0.10):
     * BKPSDM (8) → pegawai di 210; Kec. Gadingrejo (32) → 213; DP3AP2KB (211)
     * → sebagian masih di 13. Tanpa peta ini daftar pegawai BKPSDM kosong.
     */
    public const ALIAS_OPD = [8 => [8, 210], 32 => [32, 213], 211 => [211, 13]];

    public const PERAN = [
        'penanggung_jawab' => 'Penanggung Jawab',
        'anggota'          => 'Anggota',
    ];

    /** Label singkat metode untuk tampilan (kosakata sama dengan IkpModel::METODE). */
    private const METODE_SINGKAT = [
        'sum'         => 'Akumulasi',
        'trend_naik'  => 'Posisi akhir, naik',
        'trend_turun' => 'Posisi akhir, turun',
        'trend_flat'  => 'Dipertahankan',
    ];

    private const LEVEL_SIMPUL = ['es3', 'es4', 'pelaksana'];

    /** Batas satu kali kirim massal (menerima usulan PK). */
    private const MAKS_ITEM = 200;

    /** Peta izin per aksi. Aksi yang tidak terdaftar = 404 (tolak bawaan). */
    private const PETA_IZIN = [
        'index'     => 'pemilik_kinerja.view',
        'pegawai'   => 'pemilik_kinerja.view',
        'save'      => 'pemilik_kinerja.update',
        // MENGAPA .delete (bukan .update): ModulePermissionFilter membaca kata
        // "delete" di path pemilik-kinerja/delete/{id} sebagai aksi hapus dan
        // menuntut pemilik_kinerja.delete. Peta di sini disamakan supaya satu
        // izin yang menentukan, bukan dua izin yang harus sama-sama dimiliki.
        'delete'    => 'pemilik_kinerja.delete',
        'indikator' => 'pemilik_kinerja.update',
    ];

    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }

    public function _remap(string $method, ...$params)
    {
        if (str_starts_with($method, '_') || ! isset(self::PETA_IZIN[$method])
            || ! method_exists($this, $method)
            || ! (new \ReflectionMethod($this, $method))->isPublic()) {
            throw PageNotFoundException::forPageNotFound();
        }

        $izin = self::PETA_IZIN[$method];
        if (! user_can($izin)) {
            $pesan = str_ends_with($izin, '.view')
                ? 'Anda tidak memiliki akses ke halaman Pemilik Kinerja.'
                : 'Anda hanya dapat melihat Pemilik Kinerja. Perubahan memerlukan izin ubah.';

            return $this->mintaJson()
                ? $this->gagal($pesan, 403)
                : redirect()->to(base_url(($this->request->getUri()->getSegment(1) === 'adminkab' ? 'adminkab' : 'adminopd') . '/dashboard'))
                    ->with('error', $pesan);
        }

        return $this->$method(...$params);
    }

    // =================================================================
    // HALAMAN
    // =================================================================

    public function index()
    {
        $lingkup = $this->lingkup();
        $siap    = (new CascadingPemilikModel())->siap() && (new CascadingIndikatorTargetModel())->siap();

        // Area pemanggil. Bawaannya adminopd; bila rute baca-saja kelak juga
        // didaftarkan di grup adminkab (admin_kab / inspektorat), tautan dan
        // formulir di halaman ikut area itu, bukan melompat ke /adminopd yang
        // menolak role tersebut.
        $area = $this->request->getUri()->getSegment(1) === 'adminkab' ? 'adminkab' : 'adminopd';

        $data = [
            'title'       => 'Pemilik Kinerja (sampai Pelaksana)',
            'area'        => $area,
            'urlDasar'    => base_url($area . '/pemilik-kinerja'),
            'lingkup'     => $lingkup,
            'siap'        => $siap,
            'opd'         => null,
            'bolehUbah'   => user_can('pemilik_kinerja.update'),
            'bolehHapus'  => user_can('pemilik_kinerja.delete'),
            // debug=false: di mode development toolbar menyisipkan komentar
            // <!-- DEBUG-VIEW --> ke setiap view; di dalam <style> komentar itu
            // merusak aturan CSS pertama (variabel warna .pmk hilang).
            'shellCss'    => view('pemilik_kinerja/_gaya', [], ['saveData' => false, 'debug' => false]),
        ];

        if (! $siap || $lingkup['opdId'] === null) {
            return view('pemilik_kinerja/index', $data);
        }

        $opdId = $lingkup['opdId'];
        $opd   = $this->db->table('opd')->select('id, nama_opd, singkatan, jenis')
            ->where('id', $opdId)->get()->getRowArray();
        if (! $opd) {
            $data['lingkup']['alasan'] = 'Perangkat daerah tidak ditemukan.';
            $data['lingkup']['opdId']  = null;

            return view('pemilik_kinerja/index', $data);
        }

        $kecamatan = $this->modusKecamatan($opd);
        [$tahun, $periode, $daftarTahun] = $this->pilihTahun($opdId, self::bil($this->request->getGet('tahun')));

        $akarIku = (new CascadingModel())->akarAktif() === 'iku';
        $pohon   = $akarIku ? $this->muatPohon($opdId, $periode, $tahun) : $this->pohonKosong();

        $es2Pemilik = $this->pemilikEs2($opdId, $tahun);
        $usulan     = $akarIku ? $this->usulanDariPk($opdId, $tahun, $kecamatan, $pohon) : [];
        $roster     = $this->rosterOpd($opdId, $tahun, $es2Pemilik);

        $data = array_merge($data, [
            'opd'          => $opd,
            'kecamatan'    => $kecamatan,
            'akarIku'      => $akarIku,
            'tahun'        => $tahun,
            'periode'      => $periode,
            'daftarTahun'  => $daftarTahun,
            'label'        => $this->labelLevel($kecamatan),
            'pohon'        => $pohon,
            'es2Pemilik'   => $es2Pemilik,
            'usulan'       => $usulan,
            'roster'       => $roster,
            'statistik'    => $this->statistik($pohon, $roster),
            'ikpOpsi'      => $this->opsiIkp($opdId, $tahun),
            'satuanOpsi'   => $this->opsiSatuan($opdId),
            'metodeOpsi'   => IkpModel::METODE,
            'metodeSingkat' => self::METODE_SINGKAT,
            'peranOpsi'    => self::PERAN,
            'kategoriIkp'  => IkpModel::KATEGORI,
        ]);

        return view('pemilik_kinerja/index', $data);
    }

    /**
     * Select2: pegawai untuk dijadikan pemilik simpul.
     *
     * Tanpa kata kunci (atau < 3 huruf): pegawai OPD sendiri (termasuk id
     * alias OPD). Dengan >= 3 huruf: OPD sendiri lebih dulu, lalu pegawai OPD
     * lain — seperti PkController::pegawaiSearch — karena ada pegawai yang
     * tercatat di OPD lain (alias, penugasan lintas OPD, opd_id = 0).
     */
    public function pegawai()
    {
        $lingkup = $this->lingkup();
        if ($lingkup['opdId'] === null) {
            return $this->response->setJSON(['results' => []]);
        }

        $q = $this->request->getGet('q');
        $q = is_string($q) ? trim(mb_substr($q, 0, 60)) : '';
        $alias = $this->aliasOpd($lingkup['opdId']);

        $sendiri = $this->queryPegawai()
            ->whereIn('p.opd_id', $alias);
        if ($q !== '') {
            $sendiri->groupStart()->like('p.nama_pegawai', $q)->orLike('p.nip_pegawai', $q)
                ->orLike('j.nama_jabatan', $q)->groupEnd();
        }
        $barisSendiri = $sendiri->orderBy('urut_kategori', 'ASC')->orderBy('p.nama_pegawai', 'ASC')
            ->limit(60)->get()->getResultArray();

        $barisLain = [];
        if (mb_strlen($q) >= 3) {
            $barisLain = $this->queryPegawai()
                ->whereNotIn('p.opd_id', $alias)
                ->groupStart()->like('p.nama_pegawai', $q)->orLike('p.nip_pegawai', $q)->groupEnd()
                ->orderBy('p.nama_pegawai', 'ASC')->limit(30)->get()->getResultArray();
        }

        $susun = fn (array $r): array => [
            'id'       => (int) $r['id'],
            'text'     => $r['nama_pegawai'] . ((string) ($r['nama_jabatan'] ?? '') !== '' ? ' — ' . $r['nama_jabatan'] : ''),
            'nama'     => (string) $r['nama_pegawai'],
            'nip'      => (string) ($r['nip_pegawai'] ?? ''),
            'jabatan'  => (string) ($r['nama_jabatan'] ?? ''),
            'kategori' => $r['kategori'] ?? null,
            'opd'      => (string) (($r['singkatan'] ?? '') !== '' ? $r['singkatan'] : ($r['nama_opd'] ?? '')),
        ];

        $hasil = [];
        if ($barisSendiri !== []) {
            $hasil[] = ['text' => 'Pegawai perangkat daerah ini', 'children' => array_map($susun, $barisSendiri)];
        }
        if ($barisLain !== []) {
            $hasil[] = ['text' => 'Pegawai perangkat daerah lain', 'children' => array_map($susun, $barisLain)];
        }

        return $this->response->setJSON(['results' => $hasil]);
    }

    // =================================================================
    // TULIS (JSON)
    // =================================================================

    /**
     * Tambah pemilik ke simpul (satu, atau massal dari usulan PK), atau ubah
     * peran pemilik yang sudah ada (`ganti_peran: true`).
     *
     * Payload: {tahun, node_id, pegawai_id, peran, ganti_peran?}
     *      atau {tahun, sumber:'pk', items:[{node_id, pegawai_id, peran}, …]}
     */
    public function save()
    {
        try {
            $in    = $this->bacaJson();
            $tahun = $this->tahunDariInput($in['tahun'] ?? null);
            $sumber = ($in['sumber'] ?? 'manual') === 'pk' ? 'pk' : 'manual';

            $items = isset($in['items']) && is_array($in['items'])
                ? array_values($in['items'])
                : [['node_id' => $in['node_id'] ?? null, 'pegawai_id' => $in['pegawai_id'] ?? null, 'peran' => $in['peran'] ?? null]];

            if ($items === []) {
                throw new AturanBisnis('Tidak ada pemilik yang dikirim.');
            }
            if (count($items) > self::MAKS_ITEM) {
                throw new AturanBisnis('Terlalu banyak sekaligus (maksimal ' . self::MAKS_ITEM . ').');
            }

            $gantiPeran = ! empty($in['ganti_peran']) && count($items) === 1;
            $model      = new CascadingPemilikModel();

            $hasil = $this->dalamTransaksi(function () use ($items, $tahun, $sumber, $gantiPeran, $model) {
                $tersimpan = [];
                $dilewati  = [];

                foreach ($items as $it) {
                    $it        = is_array($it) ? $it : [];
                    $nodeId    = self::bil($it['node_id'] ?? null);
                    $pegawaiId = self::bil($it['pegawai_id'] ?? null);
                    $peran     = is_string($it['peran'] ?? null) ? $it['peran'] : 'penanggung_jawab';

                    if (! isset(self::PERAN[$peran])) {
                        throw new AturanBisnis('Peran tidak dikenal.');
                    }
                    $simpul  = $this->simpulTerjangkau($nodeId);
                    $pegawai = $this->pegawaiById($pegawaiId);
                    if ($pegawai === null) {
                        throw new AturanBisnis('Pegawai tidak ditemukan.');
                    }

                    $ada = $model->where([
                        'cascading_sasaran_id' => $nodeId, 'tahun' => $tahun, 'pegawai_id' => $pegawaiId,
                    ])->first();

                    if ($ada) {
                        if ($gantiPeran) {
                            if ($ada['peran'] !== $peran) {
                                $model->update((int) $ada['id'], ['peran' => $peran]);
                            }
                            $tersimpan[] = (int) $ada['id'];
                            continue;
                        }
                        if (count($items) === 1) {
                            throw new AturanBisnis($pegawai['nama_pegawai'] . ' sudah menjadi pemilik simpul ini pada tahun ' . $tahun . '.');
                        }
                        $dilewati[] = ['node_id' => $nodeId, 'pegawai_id' => $pegawaiId, 'alasan' => 'sudah menjadi pemilik'];
                        continue;
                    }

                    if ($gantiPeran) {
                        throw new AturanBisnis('Pemilik yang perannya hendak diubah tidak ditemukan. Muat ulang halaman.');
                    }

                    $id = $model->insert([
                        'cascading_sasaran_id' => $nodeId,
                        'opd_id'               => (int) $simpul['opd_id'],
                        'tahun'                => $tahun,
                        'pegawai_id'           => $pegawaiId,
                        // Snapshot jabatan saat ditetapkan: jabatan pegawai bisa
                        // berganti (mutasi), tetapi penetapan tahun ini tetap
                        // terbaca sebagaimana adanya.
                        'jabatan_teks'         => mb_substr((string) ($pegawai['nama_jabatan'] ?? ''), 0, 255) ?: null,
                        'peran'                => $peran,
                        'is_plt'               => (int) ($pegawai['is_plt'] ?? 0) === 1 ? 1 : 0,
                        'sumber'               => $sumber,
                    ]);
                    if (! $id) {
                        throw new \RuntimeException('Gagal menyimpan pemilik simpul.');
                    }
                    $tersimpan[] = (int) $id;
                }

                return [$tersimpan, $dilewati];
            }, 'simpan pemilik kinerja');

            [$ids, $dilewati] = $hasil;
            $pemilik = $ids === [] ? [] : $this->pemilikByIds($ids);

            return $this->sukses([
                'pemilik'  => $pemilik,
                'dilewati' => $dilewati,
            ], $this->pesanSimpan(count($ids), count($dilewati), $gantiPeran));
        } catch (Throwable $e) {
            return $this->gagal(pesanGalat($e, 'opd.pemilik_kinerja.simpan'), $e instanceof AturanBisnis ? 422 : 500);
        }
    }

    /** Lepas satu pemilik dari simpulnya. */
    public function delete($id = null)
    {
        try {
            $id  = (int) $id;
            $row = $this->db->table('cascading_pemilik cp')
                ->select('cp.id, cp.pegawai_id, cp.cascading_sasaran_id, cs.opd_id AS opd_simpul')
                ->join('cascading_sasaran_opd cs', 'cs.id = cp.cascading_sasaran_id', 'inner')
                ->where('cp.id', $id)->get()->getRowArray();

            // Kepemilikan diperiksa terhadap baris DB (OPD simpulnya), bukan
            // terhadap nilai yang dikirim peramban.
            if (! $row || ! $this->bolehMenyentuhOpd((int) $row['opd_simpul'])) {
                throw new AturanBisnis('Data pemilik tidak ditemukan atau bukan milik perangkat daerah Anda.');
            }

            $this->dalamTransaksi(function () use ($id) {
                $this->db->table('cascading_pemilik')->where('id', $id)->delete();
            }, 'lepas pemilik kinerja');

            return $this->sukses([
                'id'         => $id,
                'pegawai_id' => (int) $row['pegawai_id'],
                'node_id'    => (int) $row['cascading_sasaran_id'],
            ], 'Pemilik dilepas dari simpul.');
        } catch (Throwable $e) {
            return $this->gagal(pesanGalat($e, 'opd.pemilik_kinerja.hapus'), $e instanceof AturanBisnis ? 422 : 500);
        }
    }

    /**
     * Simpan satuan + target tahunan + metode + tautan IKP satu indikator.
     *
     * Payload: {tahun, indikator_id, satuan, target, metode, ikp_id}
     */
    public function indikator()
    {
        try {
            $in    = $this->bacaJson();
            $tahun = $this->tahunDariInput($in['tahun'] ?? null);
            $indId = self::bil($in['indikator_id'] ?? null);

            $ind = $this->db->table('cascading_indikator_opd ci')
                ->select('ci.id, ci.indikator, cs.id AS node_id, cs.opd_id, cs.level')
                ->join('cascading_sasaran_opd cs', 'cs.id = ci.cascading_sasaran_id', 'inner')
                ->where('ci.id', $indId)->get()->getRowArray();
            if (! $ind || ! in_array($ind['level'], self::LEVEL_SIMPUL, true)
                || ! $this->bolehMenyentuhOpd((int) $ind['opd_id'])) {
                throw new AturanBisnis('Indikator tidak ditemukan atau bukan milik perangkat daerah Anda.');
            }

            $satuan = ikp_rapikan_teks((string) ($in['satuan'] ?? ''));
            if (mb_strlen($satuan) > 50) {
                throw new AturanBisnis('Satuan terlalu panjang (maksimal 50 karakter).');
            }
            if (preg_match('/[<>]/', $satuan)) {
                throw new AturanBisnis('Satuan tidak boleh memuat tanda < atau >.');
            }

            $targetTeks = ikp_rapikan_teks((string) ($in['target'] ?? ''));
            if (ikp_angka_kosong($targetTeks)) {
                $targetTeks = '';
            }
            if (mb_strlen($targetTeks) > 100) {
                throw new AturanBisnis('Target terlalu panjang (maksimal 100 karakter).');
            }
            if (preg_match('/[<>]/', $targetTeks)) {
                throw new AturanBisnis('Target tidak boleh memuat tanda < atau >.');
            }
            // Angka dibaca dengan aturan yang sama dengan IKP (titik = ribuan,
            // koma = desimal). Target berupa predikat/teks (mis. "WTP", "Baik")
            // tetap boleh: disimpan sebagai teks, angkanya NULL. Nol = sah
            // (mis. target "0 kasus" untuk indikator yang makin kecil makin baik).
            $targetAngka = ($targetTeks !== '' && ikp_angka_sah($targetTeks))
                ? ikp_angka_baca($targetTeks, false) : null;

            $metode = (string) ($in['metode'] ?? 'sum');
            if (! isset(IkpModel::METODE[$metode])) {
                throw new AturanBisnis('Metode perhitungan tidak dikenal.');
            }

            $ikpId = self::bil($in['ikp_id'] ?? null);
            $ikpId = $ikpId > 0 ? $ikpId : null;
            if ($ikpId !== null) {
                $ikp = $this->db->table('ikp')->select('id, opd_id, dihapus_pada')
                    ->where('id', $ikpId)->get()->getRowArray();
                if (! $ikp || (int) $ikp['opd_id'] !== (int) $ind['opd_id'] || $ikp['dihapus_pada'] !== null) {
                    throw new AturanBisnis('IKP yang dipilih tidak ditemukan pada perangkat daerah ini.');
                }
            }

            $this->dalamTransaksi(function () use ($indId, $tahun, $satuan, $targetTeks, $targetAngka, $metode, $ikpId) {
                // Satuan melekat pada indikator (bukan per tahun): kolom lama
                // `cascading_indikator_opd.satuan`. Menu Cascading hanya pernah
                // menulis kolom `indikator` saat menyunting, jadi isian ini aman.
                $this->db->table('cascading_indikator_opd')->where('id', $indId)
                    ->update(['satuan' => $satuan !== '' ? $satuan : null]);

                $model = new CascadingIndikatorTargetModel();
                $ada   = $model->where(['cascading_indikator_id' => $indId, 'tahun' => $tahun])->first();

                if ($targetTeks === '' && $ikpId === null) {
                    // Tanpa target dan tanpa tautan IKP, baris target tahun ini
                    // tidak bermakna apa pun — dihapus supaya "kosong" benar-benar
                    // terbaca kosong oleh eKin, bukan baris hantu bermetode 'sum'.
                    if ($ada) {
                        $model->delete((int) $ada['id']);
                    }

                    return;
                }

                $baris = [
                    'target'      => $targetAngka,
                    'target_teks' => $targetTeks !== '' ? $targetTeks : null,
                    'metode'      => $metode,
                    'ikp_id'      => $ikpId,
                ];
                if ($ada) {
                    $model->update((int) $ada['id'], $baris);
                } else {
                    $model->insert($baris + ['cascading_indikator_id' => $indId, 'tahun' => $tahun]);
                }
            }, 'simpan target indikator cascading');

            $baru = $this->indikatorTerkini([$indId], $tahun)[$indId] ?? null;

            return $this->sukses(['indikator' => $baru], 'Satuan & target indikator tersimpan.');
        } catch (Throwable $e) {
            return $this->gagal(pesanGalat($e, 'opd.pemilik_kinerja.indikator'), $e instanceof AturanBisnis ? 422 : 500);
        }
    }

    // =================================================================
    // LINGKUP & TAHUN
    // =================================================================

    /**
     * OPD yang sedang dilihat.
     *
     * admin_opd / admin_kecamatan: SELALU dari sesi; opd_id kiriman diabaikan.
     * admin (super admin): ?opd_id= divalidasi terhadap daftar OPD aktif;
     * tanpa itu, jatuh ke opd_id sesinya (bila ada), selain itu diminta memilih.
     *
     * @return array{opdId:?int, bolehPilih:bool, daftarOpd:array, alasan:?string}
     */
    private function lingkup(): array
    {
        $peran = (string) session('role');
        $sesi  = (int) (session('opd_id') ?? 0);

        if (in_array($peran, ['admin_opd', 'admin_kecamatan'], true)) {
            return [
                'opdId'      => $sesi > 0 ? $sesi : null,
                'bolehPilih' => false,
                'daftarOpd'  => [],
                'alasan'     => $sesi > 0 ? null : 'Akun Anda belum terhubung ke perangkat daerah. Hubungi administrator.',
            ];
        }

        $daftar = $this->db->table('opd')->select('id, nama_opd, singkatan, jenis')
            ->whereNotIn('id', OpdModel::EXCLUDED_OPD_IDS)
            ->orderBy('nama_opd', 'ASC')->get()->getResultArray();
        $sah = array_map('intval', array_column($daftar, 'id'));

        $minta = self::bil($this->request->getGet('opd_id'));
        $opdId = null;
        if ($minta > 0 && in_array($minta, $sah, true)) {
            $opdId = $minta;
        } elseif ($sesi > 0 && in_array($sesi, $sah, true)) {
            $opdId = $sesi;
        }

        return [
            'opdId'      => $opdId,
            'bolehPilih' => true,
            'daftarOpd'  => $daftar,
            'alasan'     => $opdId === null ? 'Pilih perangkat daerah terlebih dahulu.' : null,
        ];
    }

    /** Boleh menulis ke simpul milik OPD ini? Diperiksa terhadap baris DB. */
    private function bolehMenyentuhOpd(int $opdIdBaris): bool
    {
        $peran = (string) session('role');
        if (in_array($peran, ['admin_opd', 'admin_kecamatan'], true)) {
            $sesi = (int) (session('opd_id') ?? 0);

            return $sesi > 0 && $sesi === $opdIdBaris;
        }

        return $peran === 'admin';
    }

    /** @return int[] */
    private function aliasOpd(int $opdId): array
    {
        return self::ALIAS_OPD[$opdId] ?? [$opdId];
    }

    /**
     * Kecamatan menggeser label satu tingkat (Camat = Eselon III, dst.).
     * Aturannya sama dengan CascadingModel::programPkByEs3: OPD yang punya PK
     * 'camat' diperlakukan sebagai kecamatan; ditambah opd.jenis bila sudah diisi.
     */
    private function modusKecamatan(array $opd): bool
    {
        if (($opd['jenis'] ?? '') === OpdModel::JENIS_KECAMATAN) {
            return true;
        }

        return $this->db->table('pk')->where('opd_id', (int) $opd['id'])
            ->where('jenis', 'camat')->countAllResults() > 0;
    }

    /**
     * Label jenjang. Untuk kecamatan hasilnya sama dengan casc_relabel() +
     * casc_pelaksana_label() milik role admin_kecamatan; di sini ditentukan
     * dari JENIS OPD supaya super admin yang membuka kecamatan melihat label
     * yang sama dengan admin kecamatannya.
     *
     * @return array<string, string>
     */
    private function labelLevel(bool $kecamatan): array
    {
        return $kecamatan
            ? ['es2' => 'Eselon III (Camat)', 'es3' => 'Eselon IV', 'es4' => 'Pelaksana / JF', 'pelaksana' => 'Staf Pelaksana',
               'pk_es2' => 'PK Camat']
            : ['es2' => 'Eselon II', 'es3' => 'Eselon III', 'es4' => 'Eselon IV / JF', 'pelaksana' => 'Pelaksana',
               'pk_es2' => 'PK JPT'];
    }

    /**
     * Tahun terpilih + periode IKU yang memuatnya.
     *
     * Periode dibaca dari iku_sasaran OPD (IKU berdiri sendiri sejak
     * 2026-07-27), cadangan rpjmd_misi. Tahun bawaan = tahun berjalan bila ada
     * dalam periode; selain itu dijepit ke periode terakhir.
     *
     * @return array{0:int, 1:array{awal:int, akhir:int}, 2:int[]}
     */
    private function pilihTahun(int $opdId, int $minta): array
    {
        $daftar = $this->periodeTersedia($opdId);
        // MENGAPA WIB: AKSARA berjalan UTC; 1 Januari 00.00–06.59 WIB masih
        // tahun lama menurut date('Y'). Tahun bawaan mengikuti kalender pengguna.
        $kini   = (int) (new \DateTimeImmutable('now', new \DateTimeZone('Asia/Jakarta')))->format('Y');
        $tahun  = $minta > 0 ? $minta : $kini;

        foreach ($daftar as $p) {
            if ($tahun >= $p['awal'] && $tahun <= $p['akhir']) {
                return [$tahun, $p, range($p['awal'], $p['akhir'])];
            }
        }

        $p     = end($daftar);
        $tahun = max($p['awal'], min($p['akhir'], $kini));

        return [$tahun, $p, range($p['awal'], $p['akhir'])];
    }

    /** @return array<int, array{awal:int, akhir:int}> urut naik; minimal satu */
    private function periodeTersedia(int $opdId): array
    {
        $rows = $this->db->table('iku_sasaran')->select('tahun_mulai, tahun_akhir')
            ->where('opd_id', $opdId)->where('tahun_mulai IS NOT NULL', null, false)
            ->groupBy(['tahun_mulai', 'tahun_akhir'])->orderBy('tahun_mulai', 'ASC')
            ->get()->getResultArray();

        if ($rows === []) {
            $rows = $this->db->table('rpjmd_misi')->select('tahun_mulai, tahun_akhir')
                ->groupBy(['tahun_mulai', 'tahun_akhir'])->orderBy('tahun_mulai', 'ASC')
                ->get()->getResultArray();
        }

        $hasil = [];
        foreach ($rows as $r) {
            if ((int) $r['tahun_mulai'] > 0 && (int) $r['tahun_akhir'] >= (int) $r['tahun_mulai']) {
                $hasil[] = ['awal' => (int) $r['tahun_mulai'], 'akhir' => (int) $r['tahun_akhir']];
            }
        }

        return $hasil !== [] ? $hasil : [['awal' => 2025, 'akhir' => 2029]];
    }

    /** Tahun kiriman tulis: 4 digit dan berada dalam salah satu periode perencanaan. */
    private function tahunDariInput($nilai): int
    {
        $tahun = self::bil($nilai);
        if ($tahun < 1900 || $tahun > 2200) {
            throw new AturanBisnis('Tahun tidak valid.');
        }

        $ada = $this->db->table('rpjmd_misi')->where('tahun_mulai <=', $tahun)
            ->where('tahun_akhir >=', $tahun)->countAllResults() > 0
            || $this->db->table('iku_sasaran')->where('tahun_mulai <=', $tahun)
                ->where('tahun_akhir >=', $tahun)->countAllResults() > 0;
        if (! $ada) {
            throw new AturanBisnis('Tahun ' . $tahun . ' di luar periode perencanaan.');
        }

        return $tahun;
    }

    // =================================================================
    // POHON
    // =================================================================

    private function pohonKosong(): array
    {
        return ['es2' => [], 'simpul' => [], 'indikator' => []];
    }

    /**
     * Muat pohon OPD untuk satu periode & tahun.
     *
     * Enam query datar (per jenjang: simpul lalu indikatornya), ditambah
     * pemilik & target sekali jalan — tidak ada query per simpul, sehingga
     * pohon 150+ simpul tetap ringan.
     *
     * @return array{es2: array, simpul: array<int, array>, indikator: array<int, array>}
     */
    private function muatPohon(int $opdId, array $periode, int $tahun): array
    {
        $db = $this->db;

        // ---------- Eselon II: sasaran & indikator IKU OPD (yang belum dihentikan)
        $es2Rows = $db->table('iku_sasaran iks')
            ->select("iks.id AS sasaran_id, iks.sasaran, iki.id AS indikator_id, iki.indikator,
                      COALESCE(siku.satuan, NULLIF(iki.satuan, '')) AS satuan", false)
            ->join('iku_indikator iki', 'iki.iku_sasaran_id = iks.id AND iki.dihentikan_pada IS NULL', 'inner', false)
            ->join('satuan siku', "siku.id = iki.satuan AND iki.satuan REGEXP '^[0-9]+$'", 'left', false)
            ->where('iks.opd_id', $opdId)
            ->where('iks.tahun_mulai', $periode['awal'])
            ->where('iks.tahun_akhir', $periode['akhir'])
            ->orderBy('iks.urutan', 'ASC')->orderBy('iks.id', 'ASC')
            ->orderBy('iki.urutan', 'ASC')->orderBy('iki.id', 'ASC')
            ->get()->getResultArray();

        $indIkuIds = array_map('intval', array_column($es2Rows, 'indikator_id'));
        $targetIku = [];
        if ($indIkuIds !== []) {
            foreach ($db->table('iku_target')->select('iku_indikator_id, target')
                ->whereIn('iku_indikator_id', $indIkuIds)->where('tahun', $tahun)
                ->orderBy('id', 'ASC')->get()->getResultArray() as $t) {
                $targetIku[(int) $t['iku_indikator_id']] ??= $t['target'];
            }
        }

        $es2 = [];
        foreach ($es2Rows as $r) {
            $sid = (int) $r['sasaran_id'];
            $es2[$sid] ??= ['id' => $sid, 'sasaran' => (string) $r['sasaran'], 'indikator' => []];
            $iid = (int) $r['indikator_id'];
            $es2[$sid]['indikator'][$iid] = [
                'id'     => $iid,
                'nama'   => (string) $r['indikator'],
                'satuan' => (string) ($r['satuan'] ?? ''),
                'target' => isset($targetIku[$iid]) ? (string) $targetIku[$iid] : '',
                'anak'   => [],
            ];
        }

        // ---------- Eselon III → IV → Pelaksana
        $simpul    = [];
        $indikator = [];

        $es3 = $indIkuIds === [] ? [] : $db->table('cascading_sasaran_opd')
            ->select('id, opd_id, level, iku_indikator_id, es3_indikator_id, nama_sasaran')
            ->where('level', 'es3')->where('opd_id', $opdId)
            ->whereIn('iku_indikator_id', $indIkuIds)
            ->orderBy('id', 'ASC')->get()->getResultArray();
        $ind3 = $this->ambilIndikator(array_column($es3, 'id'));

        $es4 = $ind3 === [] ? [] : $db->table('cascading_sasaran_opd')
            ->select('id, opd_id, level, iku_indikator_id, es3_indikator_id, nama_sasaran')
            ->where('level', 'es4')->whereIn('es3_indikator_id', array_column($ind3, 'id'))
            ->orderBy('id', 'ASC')->get()->getResultArray();
        $ind4 = $this->ambilIndikator(array_column($es4, 'id'));

        $pel = $ind4 === [] ? [] : $db->table('cascading_sasaran_opd')
            ->select('id, opd_id, level, iku_indikator_id, es3_indikator_id, nama_sasaran')
            ->where('level', 'pelaksana')->whereIn('es3_indikator_id', array_column($ind4, 'id'))
            ->orderBy('id', 'ASC')->get()->getResultArray();
        $indPel = $this->ambilIndikator(array_column($pel, 'id'));

        foreach ([$es3, $es4, $pel] as $baris) {
            foreach ($baris as $r) {
                $id          = (int) $r['id'];
                $simpul[$id] = [
                    'id'             => $id,
                    'level'          => (string) $r['level'],
                    'opd_id'         => (int) $r['opd_id'],
                    'sasaran'        => (string) $r['nama_sasaran'],
                    'induk_iku_id'   => $r['level'] === 'es3' ? (int) $r['iku_indikator_id'] : null,
                    'induk_ind_id'   => $r['level'] === 'es3' ? null : (int) $r['es3_indikator_id'],
                    'induk_simpul'   => null,
                    'indikator'      => [],
                    'pemilik'        => [],
                    'anak'           => [],   // [indikator_id => [simpul_id, …]]
                ];
            }
        }

        foreach (array_merge($ind3, $ind4, $indPel) as $r) {
            $iid = (int) $r['id'];
            $sid = (int) $r['cascading_sasaran_id'];
            if (! isset($simpul[$sid])) {
                continue;
            }
            $simpul[$sid]['indikator'][] = $iid;
            $indikator[$iid] = [
                'id'          => $iid,
                'simpul_id'   => $sid,
                'nama'        => (string) $r['indikator'],
                'satuan'      => (string) ($r['satuan'] ?? ''),
                'target'      => null,
                'target_teks' => '',
                'metode'      => 'sum',
                'ikp_id'      => null,
                'ikp_nama'    => '',
                'ikp_dihapus' => false,
            ];
        }

        // Kaitkan anak ke induk lewat indikator induk.
        foreach ($simpul as $id => $s) {
            if ($s['level'] === 'es3') {
                $iku = $s['induk_iku_id'];
                foreach ($es2 as $sid => $g) {
                    if (isset($g['indikator'][$iku])) {
                        $es2[$sid]['indikator'][$iku]['anak'][] = $id;
                        break;
                    }
                }
                continue;
            }
            $indukInd = $s['induk_ind_id'];
            $indukSimpul = $indikator[$indukInd]['simpul_id'] ?? null;
            if ($indukSimpul !== null && isset($simpul[$indukSimpul])) {
                $simpul[$id]['induk_simpul'] = $indukSimpul;
                $simpul[$indukSimpul]['anak'][$indukInd][] = $id;
            }
        }

        // Pemilik & target tahun terpilih.
        if ($simpul !== []) {
            foreach ($this->pemilikUntukSimpul(array_keys($simpul), $tahun) as $p) {
                $simpul[$p['node_id']]['pemilik'][] = $p;
            }
        }
        if ($indikator !== []) {
            foreach ($this->indikatorTerkini(array_keys($indikator), $tahun) as $iid => $v) {
                $indikator[$iid] = array_merge($indikator[$iid], $v);
            }
        }

        foreach ($es2 as &$g) {
            $g['indikator'] = array_values($g['indikator']);
        }
        unset($g);

        return ['es2' => array_values($es2), 'simpul' => $simpul, 'indikator' => $indikator];
    }

    /** @param array<int|string> $simpulIds */
    private function ambilIndikator(array $simpulIds): array
    {
        if ($simpulIds === []) {
            return [];
        }

        return $this->db->table('cascading_indikator_opd')
            ->select('id, cascading_sasaran_id, indikator, satuan')
            ->whereIn('cascading_sasaran_id', array_map('intval', $simpulIds))
            ->orderBy('id', 'ASC')->get()->getResultArray();
    }

    /**
     * Satuan + target tahun terpilih untuk sekumpulan indikator.
     *
     * @return array<int, array> [indikator_id => field]
     */
    private function indikatorTerkini(array $indIds, int $tahun): array
    {
        if ($indIds === []) {
            return [];
        }
        $rows = $this->db->table('cascading_indikator_opd ci')
            ->select('ci.id, ci.indikator, ci.satuan, cit.target, cit.target_teks, cit.metode, cit.ikp_id,
                      ikp.output_prioritas AS ikp_nama, ikp.dihapus_pada AS ikp_dihapus')
            ->join('cascading_indikator_target cit', 'cit.cascading_indikator_id = ci.id AND cit.tahun = ' . (int) $tahun, 'left', false)
            ->join('ikp', 'ikp.id = cit.ikp_id', 'left')
            ->whereIn('ci.id', array_map('intval', $indIds))
            ->get()->getResultArray();

        $hasil = [];
        foreach ($rows as $r) {
            $target = $r['target'] !== null ? (float) $r['target'] : null;
            $teks   = (string) ($r['target_teks'] ?? '');
            if ($teks === '' && $target !== null) {
                $teks = ikp_fmt($target, 4);
            }
            $hasil[(int) $r['id']] = [
                'id'          => (int) $r['id'],
                'nama'        => (string) $r['indikator'],
                'satuan'      => (string) ($r['satuan'] ?? ''),
                'target'      => $target,
                'target_teks' => $teks,
                'metode'      => (string) ($r['metode'] ?? '') !== '' ? (string) $r['metode'] : 'sum',
                'ikp_id'      => $r['ikp_id'] !== null ? (int) $r['ikp_id'] : null,
                'ikp_nama'    => (string) ($r['ikp_nama'] ?? ''),
                'ikp_dihapus' => $r['ikp_dihapus'] !== null,
            ];
        }

        return $hasil;
    }

    // =================================================================
    // PEMILIK & PEGAWAI
    // =================================================================

    private function queryPegawai()
    {
        return $this->db->table('pegawai p')
            ->select("p.id, p.nama_pegawai, p.nip_pegawai, p.opd_id, p.is_plt, p.status,
                      j.nama_jabatan, o.nama_opd, o.singkatan,
                      CASE WHEN j.simpeg_id LIKE 'struktural-%' THEN 'struktural'
                           WHEN j.simpeg_id LIKE 'fungsional-%' THEN 'fungsional'
                           WHEN j.simpeg_id LIKE 'pelaksana-%' THEN 'pelaksana'
                           ELSE NULL END AS kategori,
                      CASE WHEN j.simpeg_id LIKE 'struktural-%' THEN 1
                           WHEN j.simpeg_id LIKE 'fungsional-%' THEN 2
                           WHEN j.simpeg_id LIKE 'pelaksana-%' THEN 3
                           ELSE 4 END AS urut_kategori", false)
            ->join('jabatan j', 'j.id = p.jabatan_id', 'left')
            ->join('opd o', 'o.id = p.opd_id', 'left');
    }

    private function pegawaiById(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        return $this->queryPegawai()->where('p.id', $id)->get()->getRowArray() ?: null;
    }

    /**
     * Simpul yang boleh diberi pemilik oleh pengguna ini.
     *
     * @throws AturanBisnis
     */
    private function simpulTerjangkau(int $nodeId): array
    {
        $s = $nodeId > 0
            ? $this->db->table('cascading_sasaran_opd')->select('id, opd_id, level')
                ->where('id', $nodeId)->get()->getRowArray()
            : null;

        if (! $s || ! $this->bolehMenyentuhOpd((int) $s['opd_id'])) {
            throw new AturanBisnis('Simpul tidak ditemukan atau bukan milik perangkat daerah Anda.');
        }
        if (! in_array($s['level'], self::LEVEL_SIMPUL, true)) {
            throw new AturanBisnis('Pemilik hanya ditetapkan untuk Eselon III, Eselon IV/JF, dan Pelaksana.');
        }

        return $s;
    }

    /** @return array<int, array> baris pemilik siap tampil */
    private function pemilikUntukSimpul(array $nodeIds, int $tahun): array
    {
        return $this->bentukPemilik(
            $this->dasarPemilik()->where('cp.tahun', $tahun)
                ->whereIn('cp.cascading_sasaran_id', array_map('intval', $nodeIds))
                ->get()->getResultArray()
        );
    }

    /** @return array<int, array> */
    private function pemilikByIds(array $ids): array
    {
        return $this->bentukPemilik(
            $this->dasarPemilik()->whereIn('cp.id', array_map('intval', $ids))->get()->getResultArray()
        );
    }

    private function dasarPemilik()
    {
        return $this->db->table('cascading_pemilik cp')
            ->select('cp.id, cp.cascading_sasaran_id, cp.opd_id, cp.pegawai_id, cp.jabatan_teks, cp.peran,
                      cp.is_plt, cp.sumber, p.nama_pegawai, p.nip_pegawai, p.opd_id AS pegawai_opd,
                      j.nama_jabatan, o.singkatan, o.nama_opd')
            ->join('pegawai p', 'p.id = cp.pegawai_id', 'left')
            ->join('jabatan j', 'j.id = p.jabatan_id', 'left')
            ->join('opd o', 'o.id = p.opd_id', 'left')
            ->orderBy("FIELD(cp.peran, 'penanggung_jawab', 'anggota')", '', false)
            ->orderBy('cp.id', 'ASC');
    }

    private function bentukPemilik(array $rows): array
    {
        $hasil = [];
        foreach ($rows as $r) {
            $alias   = $this->aliasOpd((int) $r['opd_id']);
            $opdLain = $r['pegawai_opd'] !== null && ! in_array((int) $r['pegawai_opd'], $alias, true);
            $hasil[] = [
                'id'         => (int) $r['id'],
                'node_id'    => (int) $r['cascading_sasaran_id'],
                'pegawai_id' => (int) $r['pegawai_id'],
                'nama'       => (string) ($r['nama_pegawai'] ?? ('Pegawai #' . $r['pegawai_id'] . ' (tidak ditemukan)')),
                'jabatan'    => (string) (($r['jabatan_teks'] ?? '') !== '' ? $r['jabatan_teks'] : ($r['nama_jabatan'] ?? '')),
                'peran'      => (string) $r['peran'],
                'sumber'     => (string) $r['sumber'],
                'is_plt'     => (int) $r['is_plt'] === 1,
                'opd_lain'   => $opdLain ? (string) (($r['singkatan'] ?? '') !== '' ? $r['singkatan'] : ($r['nama_opd'] ?? 'OPD lain')) : '',
            ];
        }

        return $hasil;
    }

    /**
     * Pemilik Eselon II = pihak_1 PK JPT (atau PK Camat) OPD itu, tahun itu.
     * Hanya dibaca — sumber resminya tetap menu Perjanjian Kinerja.
     */
    private function pemilikEs2(int $opdId, int $tahun): array
    {
        $rows = $this->db->table('pk')
            ->select('pk.id AS pk_id, pk.jenis, pk.pihak_1, pk.is_plt_pihak_1, pk.is_plh_pihak_1,
                      pk.jabatan_pihak_1_manual, p.nama_pegawai, j.nama_jabatan')
            ->join('pegawai p', 'p.id = pk.pihak_1', 'left')
            ->join('jabatan j', 'j.id = p.jabatan_id', 'left')
            ->where('pk.opd_id', $opdId)->where('pk.tahun', $tahun)
            ->whereIn('pk.jenis', ['jpt', 'camat'])
            ->orderBy('pk.id', 'ASC')->get()->getResultArray();

        $hasil = [];
        foreach ($rows as $r) {
            if ((int) $r['pihak_1'] <= 0) {
                continue;
            }
            $jab = (string) (($r['jabatan_pihak_1_manual'] ?? '') !== '' ? $r['jabatan_pihak_1_manual'] : ($r['nama_jabatan'] ?? ''));
            $hasil[] = [
                'pk_id'      => (int) $r['pk_id'],
                'jenis'      => (string) $r['jenis'],
                'pegawai_id' => (int) $r['pihak_1'],
                'nama'       => (string) ($r['nama_pegawai'] ?? ('Pegawai #' . $r['pihak_1'])),
                'jabatan'    => $jab,
                'plt'        => (int) $r['is_plt_pihak_1'] === 1 ? 'Plt.' : ((int) $r['is_plh_pihak_1'] === 1 ? 'Plh.' : ''),
            ];
        }

        return $hasil;
    }

    /**
     * Roster pegawai OPD (termasuk id alias) + jumlah peran tiap orang tahun
     * ini — dasar metrik "matriks 0" e-Kinerja: pegawai yang belum punya satu
     * pun simpul di pohon kinerja.
     *
     * Peran dihitung dari SELURUH cascading_pemilik tahun itu (termasuk simpul
     * di OPD lain), ditambah pemilik Eselon II lewat PK.
     */
    private function rosterOpd(int $opdId, int $tahun, array $es2Pemilik): array
    {
        $alias = $this->aliasOpd($opdId);
        $rows  = $this->queryPegawai()->whereIn('p.opd_id', $alias)
            ->orderBy('urut_kategori', 'ASC')->orderBy('p.nama_pegawai', 'ASC')
            ->get()->getResultArray();

        $jumlahPeran = [];
        foreach ($this->db->table('cascading_pemilik cp')
            ->select('cp.pegawai_id, COUNT(*) AS n')
            ->join('pegawai p', 'p.id = cp.pegawai_id', 'inner')
            ->whereIn('p.opd_id', $alias)->where('cp.tahun', $tahun)
            ->groupBy('cp.pegawai_id')->get()->getResultArray() as $r) {
            $jumlahPeran[(int) $r['pegawai_id']] = (int) $r['n'];
        }
        foreach ($es2Pemilik as $e) {
            $jumlahPeran[$e['pegawai_id']] = ($jumlahPeran[$e['pegawai_id']] ?? 0) + 1;
        }

        $pegawai = [];
        foreach ($rows as $r) {
            $pegawai[] = [
                'id'       => (int) $r['id'],
                'nama'     => (string) $r['nama_pegawai'],
                'nip'      => (string) ($r['nip_pegawai'] ?? ''),
                'jabatan'  => (string) ($r['nama_jabatan'] ?? ''),
                'kategori' => $r['kategori'] ?? 'lainnya',
                'status'   => (string) ($r['status'] ?? ''),
                'peran'    => $jumlahPeran[(int) $r['id']] ?? 0,
            ];
        }

        return ['pegawai' => $pegawai, 'jumlahPeran' => $jumlahPeran];
    }

    // =================================================================
    // USULAN PEMILIK DARI PK
    // =================================================================

    /**
     * Usulan pemilik simpul dari Perjanjian Kinerja tahun terpilih.
     *
     * Pencocokan teks SAMA PERSIS dengan CascadingModel::programPkByEs3:
     * LOWER(TRIM(REGEXP_REPLACE(teks, spasi+, ' '))); cocok indikator menang,
     * cocok sasaran hanya cadangan. Eselon III ↔ PK administrator, Eselon IV
     * ↔ PK pengawas; kecamatan bergeser satu tingkat (Eselon III kecamatan ↔
     * PK pengawas, di bawahnya tidak ada PK).
     *
     * MENGAPA hanya usulan: PK adalah dokumen tanda tangan, sedangkan simpul
     * adalah struktur kinerja; teks yang kebetulan sama belum tentu orang
     * yang sama. Admin yang memutuskan — tidak ada yang tersimpan diam-diam.
     */
    private function usulanDariPk(int $opdId, int $tahun, bool $kecamatan, array $pohon): array
    {
        $jenisPerLevel = $kecamatan ? ['es3' => 'pengawas'] : ['es3' => 'administrator', 'es4' => 'pengawas'];

        $nodeIds = [];
        foreach ($pohon['simpul'] as $s) {
            if (isset($jenisPerLevel[$s['level']])) {
                $nodeIds[] = $s['id'];
            }
        }
        if ($nodeIds === []) {
            return [];
        }

        $norm = static fn (string $kol): string => "LOWER(TRIM(REGEXP_REPLACE({$kol}, '[[:space:]]+', ' ')))";

        $pkRows = $this->db->table('pk')
            ->select('pk.id AS pk_id, pk.jenis, pk.pihak_1, p.nama_pegawai, j.nama_jabatan')
            ->select($norm('ps.sasaran') . ' AS k_sas', false)
            ->select($norm('pi.indikator') . ' AS k_ind', false)
            ->join('pk_sasaran ps', 'ps.pk_id = pk.id AND ps.jenis = pk.jenis', 'inner')
            ->join('pk_indikator pi', 'pi.pk_sasaran_id = ps.id', 'inner')
            ->join('pegawai p', 'p.id = pk.pihak_1', 'left')
            ->join('jabatan j', 'j.id = p.jabatan_id', 'left')
            ->where('pk.opd_id', $opdId)->where('pk.tahun', $tahun)
            ->whereIn('pk.jenis', array_values(array_unique($jenisPerLevel)))
            ->where('pk.pihak_1 >', 0)
            ->get()->getResultArray();
        if ($pkRows === []) {
            return [];
        }

        $nodeRows = $this->db->table('cascading_sasaran_opd cs')
            ->select('cs.id AS node_id, cs.level')
            ->select($norm('cs.nama_sasaran') . ' AS k_sas', false)
            ->select($norm('ci.indikator') . ' AS k_ind', false)
            ->join('cascading_indikator_opd ci', 'ci.cascading_sasaran_id = cs.id', 'left')
            ->whereIn('cs.id', $nodeIds)
            ->get()->getResultArray();

        // Indeks PK per jenis → teks → pk.
        $pkInd = [];
        $pkSas = [];
        $pkInfo = [];
        foreach ($pkRows as $r) {
            $pid = (int) $r['pk_id'];
            $pkInfo[$pid] = $r;
            if (($r['k_ind'] ?? '') !== '') {
                $pkInd[$r['jenis']][$r['k_ind']][$pid] = $pid;
            }
            if (($r['k_sas'] ?? '') !== '') {
                $pkSas[$r['jenis']][$r['k_sas']][$pid] = $pid;
            }
        }

        $cocokInd = [];
        $cocokSas = [];
        foreach ($nodeRows as $r) {
            $node  = (int) $r['node_id'];
            $jenis = $jenisPerLevel[$r['level']] ?? null;
            if ($jenis === null) {
                continue;
            }
            foreach ($pkInd[$jenis][$r['k_ind'] ?? ''] ?? [] as $pid) {
                $cocokInd[$node][$pid] = $pid;
            }
            foreach ($pkSas[$jenis][$r['k_sas'] ?? ''] ?? [] as $pid) {
                $cocokSas[$node][$pid] = $pid;
            }
        }

        $usulan = [];
        foreach ($nodeIds as $node) {
            $cara = ! empty($cocokInd[$node]) ? 'indikator' : (! empty($cocokSas[$node]) ? 'sasaran' : null);
            if ($cara === null) {
                continue;
            }
            $pks = $cara === 'indikator' ? $cocokInd[$node] : $cocokSas[$node];

            $sudah = array_column($pohon['simpul'][$node]['pemilik'], 'pegawai_id');
            $orang = [];
            foreach ($pks as $pid) {
                $info = $pkInfo[$pid];
                $peg  = (int) $info['pihak_1'];
                if (in_array($peg, $sudah, true) || isset($orang[$peg])) {
                    continue;
                }
                $orang[$peg] = [
                    'node_id'    => $node,
                    'pegawai_id' => $peg,
                    'nama'       => (string) ($info['nama_pegawai'] ?? ('Pegawai #' . $peg)),
                    'jabatan'    => (string) ($info['nama_jabatan'] ?? ''),
                    'pk_id'      => $pid,
                    'jenis_pk'   => (string) $info['jenis'],
                    'cocok'      => $cara,
                ];
            }
            // Banyak calon untuk satu simpul (mis. lima Irban dengan teks PK
            // yang sama) = pencocokan teks tidak bisa memutuskan siapa. Usulan
            // semacam itu ditandai agar tidak ikut "Terima semua".
            foreach ($orang as $o) {
                $usulan[] = $o + ['jumlah_calon' => count($orang)];
            }
        }

        return $usulan;
    }

    // =================================================================
    // RINGKASAN & OPSI
    // =================================================================

    /** Cakupan awal (JS menghitung ulang setelah tiap perubahan). */
    private function statistik(array $pohon, array $roster): array
    {
        $level = [];
        foreach (self::LEVEL_SIMPUL as $l) {
            $level[$l] = ['simpul' => 0, 'berpemilik' => 0, 'indikator' => 0, 'indikatorLengkap' => 0];
        }
        foreach ($pohon['simpul'] as $s) {
            $level[$s['level']]['simpul']++;
            if ($s['pemilik'] !== []) {
                $level[$s['level']]['berpemilik']++;
            }
            foreach ($s['indikator'] as $iid) {
                $level[$s['level']]['indikator']++;
                if (self::indikatorLengkap($pohon['indikator'][$iid])) {
                    $level[$s['level']]['indikatorLengkap']++;
                }
            }
        }

        $tanpaPeran = 0;
        foreach ($roster['pegawai'] as $p) {
            if ($p['peran'] === 0) {
                $tanpaPeran++;
            }
        }

        return [
            'level'      => $level,
            'pegawai'    => count($roster['pegawai']),
            'tanpaPeran' => $tanpaPeran,
        ];
    }

    /** Lengkap = bersatuan + bertarget (angka atau teks) tahun ini. */
    public static function indikatorLengkap(array $ind): bool
    {
        return trim((string) ($ind['satuan'] ?? '')) !== ''
            && ($ind['target'] !== null || trim((string) ($ind['target_teks'] ?? '')) !== '');
    }

    /** IKP aktif OPD pada tahun itu — pilihan tautan indikator ↔ IKP. */
    private function opsiIkp(int $opdId, int $tahun): array
    {
        if (! (new IkpModel())->siap()) {
            return [];
        }
        $rows = $this->db->table('ikp i')
            ->select('i.id, i.kategori, i.output_prioritas, i.metode, i.satuan_teks, s.satuan,
                      tt.target, tt.target_teks')
            ->join('satuan s', 's.id = i.satuan_id', 'left')
            ->join('ikp_target_tahunan tt', 'tt.ikp_id = i.id AND tt.tahun = ' . (int) $tahun, 'left', false)
            ->where('i.opd_id', $opdId)->where('i.dihapus_pada', null)
            ->where('i.periode_awal <=', $tahun)->where('i.periode_akhir >=', $tahun)
            ->orderBy('i.urutan', 'ASC')->orderBy('i.id', 'ASC')
            ->get()->getResultArray();

        $hasil = [];
        foreach ($rows as $r) {
            $target = $r['target'] !== null ? ikp_fmt((float) $r['target'], 4) : '';
            $hasil[] = [
                'id'       => (int) $r['id'],
                'nama'     => (string) $r['output_prioritas'],
                'kategori' => (string) $r['kategori'],
                'satuan'   => (string) (($r['satuan'] ?? '') !== '' ? $r['satuan'] : ($r['satuan_teks'] ?? '')),
                'metode'   => (string) ($r['metode'] ?? ''),
                'target'   => (string) (($r['target_teks'] ?? '') !== '' ? $r['target_teks'] : $target),
            ];
        }

        return $hasil;
    }

    /** Nama satuan untuk datalist: master `satuan` + yang sudah dipakai OPD ini. */
    private function opsiSatuan(int $opdId): array
    {
        $nama = array_column($this->db->table('satuan')->select('satuan')->get()->getResultArray(), 'satuan');
        $dipakai = $this->db->table('cascading_indikator_opd ci')->select('ci.satuan')
            ->join('cascading_sasaran_opd cs', 'cs.id = ci.cascading_sasaran_id', 'inner')
            ->where('cs.opd_id', $opdId)->where('ci.satuan IS NOT NULL', null, false)->where('ci.satuan !=', '')
            ->groupBy('ci.satuan')->get()->getResultArray();

        $semua = [];
        foreach (array_merge($nama, array_column($dipakai, 'satuan')) as $s) {
            $s = trim((string) $s);
            if ($s !== '') {
                $semua[mb_strtolower($s)] ??= $s;
            }
        }
        natcasesort($semua);

        return array_values($semua);
    }

    // =================================================================
    // JSON
    // =================================================================

    private function mintaJson(): bool
    {
        return $this->request->isAJAX()
            || str_contains(strtolower($this->request->getHeaderLine('Accept')), 'application/json')
            || str_contains(strtolower($this->request->getHeaderLine('Content-Type')), 'application/json');
    }

    private function bacaJson(): array
    {
        if (! str_contains(strtolower($this->request->getHeaderLine('Content-Type')), 'application/json')) {
            $in = $this->request->getPost();

            return is_array($in) ? $in : [];
        }
        try {
            $in = $this->request->getJSON(true);
        } catch (Throwable $e) {
            throw new AturanBisnis('Format data kiriman tidak dikenali.');
        }

        return is_array($in) ? $in : [];
    }

    /**
     * Bilangan bulat positif dari masukan apa pun; selain itu 0.
     * MENGAPA: `(int)` pada array = 1 dan `(string)` pada array memicu
     * peringatan — keduanya bisa dipancing lewat ?tahun[]= atau JSON.
     */
    private static function bil($v): int
    {
        if (is_int($v)) {
            return max(0, $v);
        }

        return is_string($v) && ctype_digit($v) && strlen($v) < 10 ? (int) $v : 0;
    }

    private function pesanSimpan(int $tersimpan, int $dilewati, bool $gantiPeran): string
    {
        if ($gantiPeran) {
            return 'Peran pemilik diperbarui.';
        }
        $p = $tersimpan === 1 ? 'Pemilik ditambahkan.' : $tersimpan . ' pemilik ditambahkan.';
        if ($dilewati > 0) {
            $p .= ' ' . $dilewati . ' dilewati karena sudah tercatat.';
        }

        return $p;
    }

    private function sukses(array $data, string $pesan)
    {
        return $this->response->setJSON([
            'status'   => 'success',
            'message'  => $pesan,
            'data'     => $data,
            'csrfHash' => csrf_hash(),
        ]);
    }

    private function gagal(string $pesan, int $kode = 422)
    {
        return $this->response->setStatusCode($kode)->setJSON([
            'status'   => 'error',
            'message'  => $pesan,
            'csrfHash' => csrf_hash(),
        ]);
    }
}
