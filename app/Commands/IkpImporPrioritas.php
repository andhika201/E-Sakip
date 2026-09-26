<?php

namespace App\Commands;

use App\Models\Concerns\TransaksiAman;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use SQLite3;
use Throwable;

/**
 * Impor IKP & Rencana Inovasi dari prototipe PRIORITAS (SQLite) ke AKSARA+.
 *
 * =====================================================================
 * APA YANG DIIMPOR
 *
 *   Prioritas `ikp`             -> `ikp` (legacy_prioritas_ikp_id = ikp.id Prioritas)
 *   Prioritas `ikp_tahunan`     -> `ikp_target_tahunan`
 *   Prioritas `rencana_inovasi` -> `ikp_inovasi` (tahun = tahun PK Prioritas,
 *                                   legacy_prioritas_id = rencana_inovasi.id)
 *
 * OPD dipetakan lewat kolom Prioritas `opd.esakip_opd_id`, yang memang berisi
 * `opd.id` AKSARA (riset prioritas §8 — termasuk kembaran DP3AP2KB -> 211 dan
 * Gadingrejo -> 32 yang sudah benar).
 *
 * =====================================================================
 * APA YANG SENGAJA TIDAK DIIMPOR
 *
 *   * Target TRIWULAN Prioritas. MENGAPA: Prioritas tidak menyimpan metode
 *     indikator, dan datanya campur aduk — sebagian dijumlah, sebagian
 *     kumulatif tanpa penanda (IKP 454/455). Mengubahnya ke bulan tanpa tahu
 *     metodenya berarti mengarang angka. OPD memecah ulang target bulanan di
 *     AKSARA+ setelah memilih metode.
 *   * Metode perhitungan: selalu NULL. OPD WAJIB memilihnya sendiri.
 *   * Data pribadi (nama/NIP kepala, akun pengguna). Pejabat penanda tangan
 *     AKSARA+ selalu dibaca dari `pk.pihak_1/pihak_2` + `pegawai`.
 *   * Baris kotor hasil parser dokumen (baris judul kolom yang lolos, mis.
 *     output "PRIORITAS" / satuan "SATUAN").
 *
 * =====================================================================
 * MENGAPA UPSERT, BUKAN HAPUS-LALU-BUAT-ULANG
 *
 * RHK/SKP di eKin merujuk `ikp.id`. Prototipe Prioritas menghapus seluruh IKP
 * setiap kali dokumen diimpor ulang — id berganti dan rantai SKP putus. Di
 * sini baris dicari lewat `legacy_prioritas_ikp_id`:
 *   - belum ada   -> disisipkan;
 *   - sudah ada   -> hanya kolom yang MASIH KOSONG yang diisi (suntingan OPD
 *                    di AKSARA+ tidak ditimpa), kecuali diminta --timpa;
 *   - sudah dihapus OPD (soft delete) -> dilewati, tidak dihidupkan lagi.
 *
 * Basis data Prioritas dibuka READ-ONLY (SQLITE3_OPEN_READONLY): perintah ini
 * tidak pernah menulis ke berkas prototipe.
 */
class IkpImporPrioritas extends BaseCommand
{
    use TransaksiAman;

    protected $group       = 'SAKIP';
    protected $name        = 'ikp:impor-prioritas';
    protected $description = 'Impor IKP, target tahunan & rencana inovasi dari SQLite prototipe Prioritas (upsert, baca-saja).';
    protected $usage       = 'ikp:impor-prioritas [--db <berkas.sqlite>] [--opd <id>] [--kecuali <id,id>] [--kering] [--timpa]';
    protected $options     = [
        '--db'      => 'berkas SQLite Prioritas (bawaan: /var/www/prioritas/database/database.sqlite)',
        '--opd'     => 'hanya OPD AKSARA ini (opd.id; boleh beberapa, dipisah koma)',
        '--kecuali' => 'lewati OPD AKSARA ini (opd.id, dipisah koma), mis. 23,20,11',
        '--kering'  => 'pratinjau: hitung & tampilkan, TANPA menulis apa pun',
        '--timpa'   => 'baris yang sudah ada ditimpa dengan nilai Prioritas (bawaan: hanya mengisi kolom kosong)',
    ];

    private const DB_BAWAAN = '/var/www/prioritas/database/database.sqlite';

    /** Kolom `ikp` yang diisi dari Prioritas (hanya ini yang disentuh saat upsert). */
    private const KOLOM_IKP = [
        'kategori', 'program_unggulan_id', 'rpjmd_misi_id', 'sasaran_pembangunan_id',
        'outcome', 'indikator_outcome', 'program_opd', 'bidang_urusan', 'output_prioritas',
        'satuan_id', 'satuan_teks', 'target_5_tahun', 'target_5_tahun_teks', 'urutan',
    ];

    /** Panjang kolom VARCHAR tujuan — strictOn=false memotong diam-diam, jadi dipotong & dihitung di sini. */
    private const PANJANG = ['bidang_urusan' => 255, 'satuan_teks' => 100, 'target_5_tahun_teks' => 255, 'nama' => 255];

    /** Sinonim satuan yang aman (hanya ejaan lain dari satuan yang SAMA). */
    private const SINONIM_SATUAN = [
        '%'            => 'persen',
        'persentase'   => 'persen',
        'persen (%)'   => 'persen',
        'persen ( % )' => 'persen',
        'persen(%)'    => 'persen',
        'hektar'       => 'ha',
        'kilometer'    => 'km',
    ];

    /** @var \CodeIgniter\Database\BaseConnection dipakai TransaksiAman */
    protected $db;

    private SQLite3 $lite;
    private bool $kering = false;
    private bool $timpa  = false;
    private array $periode = ['awal' => 2025, 'akhir' => 2029];

    /** @var array<string,int> peta pencarian AKSARA */
    private array $puSlug = [];
    private array $puNama = [];
    private array $spNama = [];
    private array $satuan = [];
    /** @var array<int,int> nomor misi (1..n) -> rpjmd_misi.id */
    private array $misiNomor = [];
    /** @var array<int,string> id PU Prioritas -> slug; id sasaran Prioritas -> nama; id misi Prioritas -> nomor */
    private array $liteSlugPu = [];
    private array $liteNamaSp = [];
    private array $liteNomorMisi = [];

    public function run(array $params)
    {
        $this->kering = array_key_exists('kering', $params) || (bool) CLI::getOption('kering');
        $this->timpa  = array_key_exists('timpa', $params) || (bool) CLI::getOption('timpa');
        $berkas       = trim((string) (CLI::getOption('db') ?: self::DB_BAWAAN));
        $hanya        = $this->daftarId((string) (CLI::getOption('opd') ?: ''));
        $kecuali      = $this->daftarId((string) (CLI::getOption('kecuali') ?: ''));

        if (! is_file($berkas) || ! is_readable($berkas)) {
            CLI::error('Berkas SQLite Prioritas tidak ditemukan/terbaca: ' . $berkas);

            return EXIT_ERROR;
        }

        try {
            // READ-ONLY sungguhan: berkas prototipe tidak boleh tersentuh sedikit pun.
            $this->lite = new SQLite3($berkas, SQLITE3_OPEN_READONLY);
            $this->lite->enableExceptions(true);
        } catch (Throwable $e) {
            CLI::error('Gagal membuka SQLite (baca-saja): ' . $e->getMessage());

            return EXIT_ERROR;
        }

        $this->db = db_connect();
        helper('ikp');

        foreach (['ikp', 'ikp_target_tahunan', 'ikp_inovasi', 'ikp_program_unggulan', 'ikp_sasaran_pembangunan', 'satuan', 'rpjmd_misi', 'opd'] as $t) {
            if (! $this->db->tableExists($t)) {
                CLI::error('Tabel `' . $t . '` belum ada. Jalankan migrasi / db/update_2026-09-26_ikp_kinerja.sql dahulu.');

                return EXIT_ERROR;
            }
        }
        foreach (['ikp', 'ikp_tahunan', 'rencana_inovasi', 'perjanjian_kinerja', 'opd', 'misi', 'program_unggulan', 'sasaran_pembangunan'] as $t) {
            if (! $this->liteAda($t)) {
                CLI::error('Tabel Prioritas `' . $t . '` tidak ada di ' . $berkas . '. Bukan basis data Prioritas?');

                return EXIT_ERROR;
            }
        }

        $this->periode = $this->periodeAktif();
        $this->siapkanPeta();

        CLI::write('Sumber     : ' . $berkas . ' (baca-saja)');
        CLI::write('Tujuan     : ' . $this->db->getDatabase());
        CLI::write('Periode    : ' . $this->periode['awal'] . '–' . $this->periode['akhir']);
        CLI::write('Mode       : ' . ($this->kering ? CLI::color('KERING (pratinjau, tanpa menulis)', 'yellow') : CLI::color('TULIS', 'red'))
            . ($this->timpa ? ' + ' . CLI::color('TIMPA', 'red') : ' (hanya mengisi kolom kosong pada baris lama)'));
        if ($hanya !== []) {
            CLI::write('Hanya OPD  : ' . implode(',', $hanya));
        }
        if ($kecuali !== []) {
            CLI::write('Kecuali OPD: ' . implode(',', $kecuali));
        }
        CLI::newLine();

        $opdAksara = array_map('intval', array_column(
            $this->db->table('opd')->select('id')->get()->getResultArray(),
            'id'
        ));

        $pkRows = $this->liteSemua(
            'SELECT p.id, p.tahun, p.opd_id AS lite_opd_id, o.singkatan, o.esakip_opd_id
               FROM perjanjian_kinerja p JOIN opd o ON o.id = p.opd_id
              ORDER BY o.urutan, o.id, p.tahun'
        );

        $ringkas = [];
        $total   = ['ikp_baru' => 0, 'ikp_isi' => 0, 'ikp_tetap' => 0, 'lewat' => 0, 'thn_baru' => 0, 'thn_isi' => 0, 'ino_baru' => 0, 'ino_isi' => 0];
        $catatan = [];
        $gagal   = 0;

        foreach ($pkRows as $pk) {
            $opdId = $pk['esakip_opd_id'] !== null ? (int) $pk['esakip_opd_id'] : 0;
            $label = $pk['singkatan'] . ' (PK Prioritas #' . $pk['id'] . ', ' . $pk['tahun'] . ')';

            if ($opdId <= 0 || ! in_array($opdId, $opdAksara, true)) {
                $catatan[] = $label . ': OPD tidak terpetakan ke AKSARA (esakip_opd_id kosong/tidak dikenal) — dilewati.';

                continue;
            }
            if ($hanya !== [] && ! in_array($opdId, $hanya, true)) {
                continue;
            }
            if (in_array($opdId, $kecuali, true)) {
                continue;
            }

            try {
                $hasil = $this->kering
                    ? $this->imporPk($pk, $opdId)
                    : $this->dalamTransaksi(fn () => $this->imporPk($pk, $opdId), 'impor Prioritas ' . $pk['singkatan']);
            } catch (Throwable $e) {
                $gagal++;
                CLI::error('  GAGAL ' . $label . ': ' . $e->getMessage() . ' — seluruh perubahan OPD ini dibatalkan.');

                continue;
            }

            foreach ($hasil['catatan'] as $c) {
                $catatan[] = $pk['singkatan'] . ': ' . $c;
            }
            foreach ($total as $k => $_) {
                $total[$k] += $hasil[$k];
            }
            $ringkas[] = [
                $opdId,
                $pk['singkatan'],
                (string) $hasil['sumber'],
                $hasil['ikp_baru'] . ' / ' . $hasil['ikp_isi'] . ' / ' . $hasil['ikp_tetap'],
                (string) $hasil['lewat'],
                $hasil['thn_baru'] . ' / ' . $hasil['thn_isi'],
                $hasil['ino_baru'] . ' / ' . $hasil['ino_isi'],
                (string) $hasil['tanpa_pu'],
                (string) $hasil['target_teks'],
            ];
        }

        if ($ringkas !== []) {
            CLI::table($ringkas, [
                'OPD', 'Singkatan', 'IKP sumber', 'IKP baru/diisi/tetap', 'Dilewati',
                'Tahunan baru/diisi', 'Inovasi baru/diisi', 'Tanpa PU', 'Target teks',
            ]);
        } else {
            CLI::write('Tidak ada PK Prioritas yang cocok dengan filter.', 'yellow');
        }

        CLI::newLine();
        CLI::write(sprintf(
            'TOTAL  IKP baru %d · diisi %d · tetap %d · dilewati %d  |  tahunan baru %d · diisi %d  |  inovasi baru %d · diisi %d',
            $total['ikp_baru'], $total['ikp_isi'], $total['ikp_tetap'], $total['lewat'],
            $total['thn_baru'], $total['thn_isi'], $total['ino_baru'], $total['ino_isi']
        ), 'green');
        CLI::write('Kolom "Tanpa PU": IKP tanpa Program Unggulan terpetakan -> kategori program_prioritas.');
        CLI::write('Kolom "Target teks": target 5 tahun yang bukan angka (disimpan di target_5_tahun_teks saja).');
        CLI::write('Metode perhitungan semua IKP hasil impor = KOSONG; OPD wajib memilihnya sebelum memecah target bulanan.');

        if ($catatan !== []) {
            CLI::newLine();
            CLI::write('Catatan:', 'yellow');
            foreach ($catatan as $c) {
                CLI::write('  - ' . $c);
            }
        }
        if ($this->kering) {
            CLI::newLine();
            CLI::write('Mode kering: tidak ada yang ditulis. Ulangi tanpa --kering untuk menyimpan.', 'yellow');
        }

        $this->lite->close();

        return $gagal > 0 ? EXIT_ERROR : EXIT_SUCCESS;
    }

    /**
     * Impor satu PK Prioritas (IKP + tahunan + inovasi) ke satu OPD AKSARA.
     *
     * @return array<string, mixed>
     */
    private function imporPk(array $pk, int $opdId): array
    {
        $h = ['sumber' => 0, 'ikp_baru' => 0, 'ikp_isi' => 0, 'ikp_tetap' => 0, 'lewat' => 0,
              'thn_baru' => 0, 'thn_isi' => 0, 'ino_baru' => 0, 'ino_isi' => 0,
              'tanpa_pu' => 0, 'target_teks' => 0, 'catatan' => []];
        $sekarang = date('Y-m-d H:i:s');

        $baris = $this->liteSemua(
            'SELECT * FROM ikp WHERE perjanjian_kinerja_id = :pk ORDER BY urutan, id',
            [':pk' => (int) $pk['id']]
        );
        $h['sumber'] = count($baris);

        foreach ($baris as $r) {
            $legacy = (int) $r['id'];

            if ($this->barisJudul($r)) {
                $h['lewat']++;
                $h['catatan'][] = 'IKP Prioritas #' . $legacy . ' adalah baris judul kolom yang lolos parser — dilewati.';

                continue;
            }

            $data = $this->petakanIkp($r, $h);

            $lama = $this->db->table('ikp')->where('legacy_prioritas_ikp_id', $legacy)
                ->orderBy('id', 'ASC')->get()->getRowArray();

            if ($lama !== null && (int) $lama['opd_id'] !== $opdId) {
                $h['lewat']++;
                $h['catatan'][] = 'IKP Prioritas #' . $legacy . ' sudah terimpor ke OPD lain (ikp.id ' . $lama['id'] . ') — dilewati.';

                continue;
            }
            if ($lama !== null && $lama['dihapus_pada'] !== null) {
                $h['lewat']++;
                $h['catatan'][] = 'IKP Prioritas #' . $legacy . ' sudah dihapus OPD (ikp.id ' . $lama['id'] . ') — tidak dihidupkan lagi.';

                continue;
            }

            if ($lama === null) {
                $ikpId = 0;
                if (! $this->kering) {
                    $this->db->table('ikp')->insert($data + [
                        'opd_id'                  => $opdId,
                        'periode_awal'            => $this->periode['awal'],
                        'periode_akhir'           => $this->periode['akhir'],
                        'metode'                  => null,   // OPD wajib memilih sendiri
                        'legacy_prioritas_ikp_id' => $legacy,
                        'created_at'              => $sekarang,
                        'updated_at'              => $sekarang,
                    ]);
                    $ikpId = (int) $this->db->insertID();
                }
                $h['ikp_baru']++;
            } else {
                $ikpId = (int) $lama['id'];
                $ubah  = $this->ubahan($lama, $data);
                if ($ubah !== []) {
                    if (! $this->kering) {
                        $this->db->table('ikp')->where('id', $ikpId)->update($ubah + ['updated_at' => $sekarang]);
                    }
                    $h['ikp_isi']++;
                } else {
                    $h['ikp_tetap']++;
                }
            }

            // ---------- target tahunan (hanya tahun di dalam periode) ----------
            $tahunan = $this->liteSemua(
                'SELECT tahun, target FROM ikp_tahunan WHERE ikp_id = :id ORDER BY tahun',
                [':id' => $legacy]
            );
            foreach ($tahunan as $t) {
                $th    = (int) $t['tahun'];
                $nilai = $t['target'] === null ? null : (float) $t['target'];
                if ($th < $this->periode['awal'] || $th > $this->periode['akhir'] || $nilai === null || abs($nilai) < 1e-12) {
                    continue; // Prioritas: 0 = belum ada target
                }
                $ada = ($ikpId > 0)
                    ? $this->db->table('ikp_target_tahunan')->where(['ikp_id' => $ikpId, 'tahun' => $th])->get()->getRowArray()
                    : null;
                if ($ada === null) {
                    if (! $this->kering) {
                        $this->db->table('ikp_target_tahunan')->insert([
                            'ikp_id' => $ikpId, 'tahun' => $th, 'target' => $nilai, 'target_teks' => null,
                            'created_at' => $sekarang, 'updated_at' => $sekarang,
                        ]);
                    }
                    $h['thn_baru']++;
                } elseif ($ada['target'] === null || ($this->timpa && abs((float) $ada['target'] - $nilai) > 1e-9)) {
                    if (! $this->kering) {
                        $this->db->table('ikp_target_tahunan')->where('id', (int) $ada['id'])
                            ->update(['target' => $nilai, 'updated_at' => $sekarang]);
                    }
                    $h['thn_isi']++;
                }
            }
        }

        // ---------- rencana inovasi (Lampiran III) ----------
        $inovasi = $this->liteSemua(
            'SELECT id, nama, deskripsi, urutan FROM rencana_inovasi WHERE perjanjian_kinerja_id = :pk ORDER BY urutan, id',
            [':pk' => (int) $pk['id']]
        );
        foreach ($inovasi as $r) {
            $nama = $this->potong(ikp_rapikan_teks((string) $r['nama']), self::PANJANG['nama'], $h, 'nama inovasi #' . $r['id']);
            if ($nama === '') {
                continue;
            }
            $data = [
                'nama'      => $nama,
                'deskripsi' => $this->teks($r['deskripsi']),
                'urutan'    => (int) $r['urutan'],
            ];
            $lama = $this->db->table('ikp_inovasi')->where('legacy_prioritas_id', (int) $r['id'])->get()->getRowArray();

            if ($lama === null) {
                if (! $this->kering) {
                    $this->db->table('ikp_inovasi')->insert($data + [
                        'opd_id'              => $opdId,
                        'tahun'               => (int) $pk['tahun'],
                        'ikp_id'              => null,
                        'legacy_prioritas_id' => (int) $r['id'],
                        'created_at'          => $sekarang,
                        'updated_at'          => $sekarang,
                    ]);
                }
                $h['ino_baru']++;
            } elseif ((int) $lama['opd_id'] !== $opdId) {
                $h['catatan'][] = 'Inovasi Prioritas #' . $r['id'] . ' sudah ada di OPD lain — dilewati.';
            } else {
                $ubah = $this->ubahan($lama, $data);
                if ($ubah !== []) {
                    if (! $this->kering) {
                        $this->db->table('ikp_inovasi')->where('id', (int) $lama['id'])->update($ubah + ['updated_at' => $sekarang]);
                    }
                    $h['ino_isi']++;
                }
            }
        }

        return $h;
    }

    /**
     * Satu baris `ikp` Prioritas -> kolom `ikp` AKSARA+ (tanpa opd/periode/legacy).
     *
     * @return array<string, mixed>
     */
    private function petakanIkp(array $r, array &$h): array
    {
        $puId = $this->cariPu($r);
        if ($puId === null) {
            $h['tanpa_pu']++;
        }

        $targetTeks  = $this->teks($r['target_5_tahun']);
        $targetAngka = $targetTeks === null ? null : ikp_angka_baca($targetTeks, true);
        if ($targetTeks !== null && $targetAngka === null) {
            $h['target_teks']++;
        }

        [$satuanId, $satuanTeks] = $this->cariSatuan($this->teks($r['satuan']));

        $misiId = null;
        if ($r['misi_id'] !== null && isset($this->liteNomorMisi[(int) $r['misi_id']])) {
            $misiId = $this->misiNomor[$this->liteNomorMisi[(int) $r['misi_id']]] ?? null;
        }

        return [
            // Program unggulan terpetakan -> IKP Program Unggulan Bupati; sisanya
            // program prioritas OPD. Penugasan khusus/tambahan tidak dikenal
            // Prioritas, jadi tidak pernah dihasilkan impor.
            'kategori'               => $puId !== null ? 'program_unggulan' : 'program_prioritas',
            'program_unggulan_id'    => $puId,
            'rpjmd_misi_id'          => $misiId,
            'sasaran_pembangunan_id' => $this->cariSasaran($r),
            'outcome'                => $this->teks($r['outcome']),
            'indikator_outcome'      => $this->teks($r['indikator']),
            'program_opd'            => $this->teks($r['program_opd']),
            'bidang_urusan'          => $this->potong($this->teks($r['bidang_urusan']), self::PANJANG['bidang_urusan'], $h, 'bidang urusan IKP #' . $r['id']),
            'output_prioritas'       => (string) $this->teks($r['output_prioritas']),
            'satuan_id'              => $satuanId,
            'satuan_teks'            => $this->potong($satuanTeks, self::PANJANG['satuan_teks'], $h, 'satuan IKP #' . $r['id']),
            'target_5_tahun'         => $targetAngka,
            'target_5_tahun_teks'    => $this->potong($targetTeks, self::PANJANG['target_5_tahun_teks'], $h, 'target IKP #' . $r['id']),
            'urutan'                 => (int) $r['urutan'],
        ];
    }

    /**
     * Kolom yang perlu ditulis pada baris lama.
     * Bawaan: hanya kolom yang kosong (NULL/''), agar suntingan OPD di AKSARA+
     * tidak tertimpa. --timpa: semua kolom impor yang nilainya berbeda — tetapi
     * nilai kosong dari Prioritas tidak pernah menghapus isian AKSARA+.
     *
     * @return array<string, mixed>
     */
    private function ubahan(array $lama, array $baru): array
    {
        $ubah = [];
        foreach ($baru as $k => $v) {
            if ($v === null || $v === '') {
                continue;
            }
            $kosong = ! array_key_exists($k, $lama) || $lama[$k] === null || $lama[$k] === '';
            if ($kosong) {
                $ubah[$k] = $v;
            } elseif ($this->timpa && ! $this->samaNilai($lama[$k], $v)) {
                $ubah[$k] = $v;
            }
        }

        // Satuan adalah PASANGAN (satuan_id ATAU satuan_teks). Bila OPD sudah
        // mengisi salah satunya, jangan tempelkan yang lain dari Prioritas —
        // dua satuan berbeda pada satu IKP lebih buruk daripada satu yang lama.
        if (array_key_exists('satuan_id', $baru) || array_key_exists('satuan_teks', $baru)) {
            $adaSatuan = ! empty($lama['satuan_id']) || (string) ($lama['satuan_teks'] ?? '') !== '';
            unset($ubah['satuan_id'], $ubah['satuan_teks']);
            $beda = (int) ($lama['satuan_id'] ?? 0) !== (int) ($baru['satuan_id'] ?? 0)
                || (string) ($lama['satuan_teks'] ?? '') !== (string) ($baru['satuan_teks'] ?? '');
            if ((! $adaSatuan || $this->timpa) && $beda && ($baru['satuan_id'] !== null || $baru['satuan_teks'] !== null)) {
                $ubah['satuan_id']   = $baru['satuan_id'];
                $ubah['satuan_teks'] = $baru['satuan_teks'];
            }
        }

        // `kategori` NOT NULL (tak pernah kosong) — tanpa --timpa jangan diubah;
        // tetapi bila PU baru saja terisi pada IKP berkategori program_prioritas
        // hasil impor, kategori ikut naik agar tidak bertentangan dengan PU-nya.
        if (! $this->timpa && isset($ubah['program_unggulan_id'])
            && ($lama['kategori'] ?? '') === 'program_prioritas' && ($baru['kategori'] ?? '') === 'program_unggulan') {
            $ubah['kategori'] = 'program_unggulan';
        }

        return $ubah;
    }

    private function samaNilai($lama, $baru): bool
    {
        if (is_float($baru) || is_int($baru)) {
            return is_numeric($lama) && abs((float) $lama - (float) $baru) < 1e-9;
        }

        return (string) $lama === (string) $baru;
    }

    /** Baris judul kolom yang lolos parser dokumen (mis. DISNAKERTRANS #488). */
    private function barisJudul(array $r): bool
    {
        $kunci = static fn ($v) => strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $v));
        $out   = $kunci($r['output_prioritas']);

        return in_array($out, ['PRIORITAS', 'OUTPUTPRIORITAS', ''], true)
            || $kunci($r['satuan']) === 'SATUAN'
            || $kunci($r['target_5_tahun']) === 'TARGET5TAHUN';
    }

    private function cariPu(array $r): ?int
    {
        // 1) id PU Prioritas -> slug -> id AKSARA (slug sama di kedua sisi)
        if ($r['program_unggulan_id'] !== null) {
            $slug = $this->liteSlugPu[(int) $r['program_unggulan_id']] ?? null;
            if ($slug !== null && isset($this->puSlug[$slug])) {
                return $this->puSlug[$slug];
            }
        }
        // 2) teks PU ("Pringsewu Bersih" / "BERSIH")
        $teks = $this->kunciTeks($r['program_unggulan_teks'] ?? '');
        if ($teks === '') {
            return null;
        }
        if (isset($this->puNama[$teks])) {
            return $this->puNama[$teks];
        }
        $tanpa = trim(preg_replace('/^pringsewu\s+/', '', $teks));

        return $this->puSlug[str_replace(' ', '-', $tanpa)] ?? null;
    }

    private function cariSasaran(array $r): ?int
    {
        $kandidat = [$this->kunciTeks($r['sasaran_pembangunan_teks'] ?? '')];
        if ($r['sasaran_pembangunan_id'] !== null && isset($this->liteNamaSp[(int) $r['sasaran_pembangunan_id']])) {
            $kandidat[] = $this->kunciTeks($this->liteNamaSp[(int) $r['sasaran_pembangunan_id']]);
        }
        foreach ($kandidat as $k) {
            if ($k !== '' && isset($this->spNama[$k])) {
                return $this->spNama[$k];
            }
        }
        // Salah ketik kecil ("infrastuktur") — kemiripan tinggi saja, bukan tebakan.
        foreach ($kandidat as $k) {
            if ($k === '') {
                continue;
            }
            foreach ($this->spNama as $nama => $id) {
                similar_text($k, $nama, $persen);
                if ($persen >= 95.0) {
                    return $id;
                }
            }
        }

        return null;
    }

    /** @return array{0: ?int, 1: ?string} [satuan_id, satuan_teks] */
    private function cariSatuan(?string $teks): array
    {
        if ($teks === null) {
            return [null, null];
        }
        $k = $this->kunciTeks($teks);
        $k = self::SINONIM_SATUAN[$k] ?? $k;
        if (isset($this->satuan[$k])) {
            return [$this->satuan[$k], null];
        }

        return [null, $teks];
    }

    private function siapkanPeta(): void
    {
        foreach ($this->db->table('ikp_program_unggulan')->get()->getResultArray() as $p) {
            $this->puSlug[(string) $p['slug']]            = (int) $p['id'];
            $this->puNama[$this->kunciTeks($p['nama'])] = (int) $p['id'];
        }
        foreach ($this->db->table('ikp_sasaran_pembangunan')->get()->getResultArray() as $s) {
            $this->spNama[$this->kunciTeks($s['nama'])] = (int) $s['id'];
        }
        // Satuan kembar nama (mis. "Ton" 12 & 50): pakai id terkecil bertipe
        // angka/persen — predikat butuh skala, tidak cocok untuk teks bebas.
        $rows = $this->db->table('satuan')->orderBy('id', 'ASC')->get()->getResultArray();
        foreach ($rows as $s) {
            $k = $this->kunciTeks($s['satuan']);
            if ($k === '' || isset($this->satuan[$k]) || ($s['tipe'] ?? '') === 'predikat') {
                continue;
            }
            $this->satuan[$k] = (int) $s['id'];
        }

        // Misi: nomor 1..n Prioritas = urutan rpjmd_misi periode aktif (id naik).
        $b = $this->db->table('rpjmd_misi')->select('id')
            ->where('tahun_mulai', $this->periode['awal'])
            ->where('tahun_akhir', $this->periode['akhir']);
        if ($this->db->fieldExists('dihentikan_pada', 'rpjmd_misi')) {
            $b->where('dihentikan_pada', null);
        }
        $n = 1;
        foreach ($b->orderBy('id', 'ASC')->get()->getResultArray() as $m) {
            $this->misiNomor[$n++] = (int) $m['id'];
        }

        foreach ($this->liteSemua('SELECT id, slug FROM program_unggulan') as $p) {
            $this->liteSlugPu[(int) $p['id']] = (string) $p['slug'];
        }
        foreach ($this->liteSemua('SELECT id, nama FROM sasaran_pembangunan') as $s) {
            $this->liteNamaSp[(int) $s['id']] = (string) $s['nama'];
        }
        foreach ($this->liteSemua('SELECT id, nomor FROM misi') as $m) {
            $this->liteNomorMisi[(int) $m['id']] = (int) $m['nomor'];
        }
    }

    /** @return array{awal:int, akhir:int} */
    private function periodeAktif(): array
    {
        if (class_exists(\App\Services\IkpRekapService::class)) {
            try {
                $p = (new \App\Services\IkpRekapService())->periodeAktif();
                if (! empty($p['awal']) && ! empty($p['akhir'])) {
                    return ['awal' => (int) $p['awal'], 'akhir' => (int) $p['akhir']];
                }
            } catch (Throwable $e) {
                CLI::write('  (periode aktif gagal dibaca: ' . $e->getMessage() . ' — memakai 2025–2029)', 'yellow');
            }
        }

        return ['awal' => 2025, 'akhir' => 2029];
    }

    private function kunciTeks(?string $v): string
    {
        return mb_strtolower(ikp_rapikan_teks((string) $v), 'UTF-8');
    }

    private function teks($v): ?string
    {
        $t = ikp_rapikan_teks($v === null ? null : (string) $v);

        return $t === '' ? null : $t;
    }

    private function potong(?string $v, int $maks, array &$h, string $apa): ?string
    {
        if ($v === null || mb_strlen($v, 'UTF-8') <= $maks) {
            return $v;
        }
        $h['catatan'][] = ucfirst($apa) . ' lebih dari ' . $maks . ' huruf — dipotong.';

        return mb_substr($v, 0, $maks, 'UTF-8');
    }

    /** @return list<int> */
    private function daftarId(string $v): array
    {
        $out = [];
        foreach (explode(',', $v) as $p) {
            $p = trim($p);
            if ($p !== '' && ctype_digit($p) && (int) $p > 0) {
                $out[] = (int) $p;
            }
        }

        return array_values(array_unique($out));
    }

    private function liteAda(string $tabel): bool
    {
        $st = $this->lite->prepare("SELECT 1 FROM sqlite_master WHERE type = 'table' AND name = :n");
        $st->bindValue(':n', $tabel, SQLITE3_TEXT);

        return $st->execute()->fetchArray() !== false;
    }

    /** @return list<array<string, mixed>> */
    private function liteSemua(string $sql, array $ikat = []): array
    {
        $st = $this->lite->prepare($sql);
        foreach ($ikat as $k => $v) {
            $st->bindValue($k, $v, is_int($v) ? SQLITE3_INTEGER : SQLITE3_TEXT);
        }
        $res = $st->execute();
        $out = [];
        while (($row = $res->fetchArray(SQLITE3_ASSOC)) !== false) {
            $out[] = $row;
        }

        return $out;
    }
}
