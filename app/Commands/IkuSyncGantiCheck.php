<?php

namespace App\Commands;

use App\Models\Opd\IkuModel;
use App\Models\Opd\IkuRevisiModel;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Throwable;

/**
 * Uji SYNC GANTI TOTAL saat membuat versi IKU baru.
 *
 *   php spark iku:sync-ganti-check --db <salinan>
 *
 * =====================================================================
 * APA YANG DIJAGA DI SINI
 *
 * `buatDraft()` mengisi draft baru dengan salinan IKU yang BERLAKU sekarang.
 * Sesudah itu sync menuliskan isi Renstra/RPJMD ke draft yang sama. Bila sync
 * hanya MENAMBAHKAN yang kurang, hasilnya gabungan: baris IKU lama yang sudah
 * tidak ada di sumber ikut terbawa dari versi ke versi, dan versi "baru" tidak
 * pernah benar-benar mencerminkan sumbernya.
 *
 * Yang diuji di sini: hasilnya SAMA DENGAN SUMBER — tidak kurang, dan yang
 * lebih penting, tidak lebih.
 *
 * Tiga hal yang paling mudah salah:
 *
 *   1. KANDIDAT 'sama' HILANG. Tanda baru/berubah/sama dihitung terhadap IKU
 *      BERJALAN, bukan terhadap isi draft. Draft yang sudah dikosongkan tetap
 *      akan melewatkan kandidat bertanda 'sama' bila keranjangnya dibangun
 *      `keranjangSyncPenuh()` — sehingga draft justru berisi HANYA yang
 *      berbeda: kebalikan dari yang diminta. Diuji dengan membandingkan kedua
 *      keranjang secara langsung.
 *
 *   2. KETERANGAN OPERATOR IKUT TERBUANG. `definisi`, `rumusan_perhitungan`,
 *      `sumber_data`, dan `penanggung_jawab` tidak ada di Renstra maupun
 *      RPJMD; keempatnya diketik tangan di IKU. Menulis ulang isi dari sumber
 *      mengosongkan semuanya — pada basis data kerja itu 108 dari 143
 *      indikator.
 *
 *   3. YANG BUKAN DRAFT IKUT TERKENA. Mengosongkan isi revisi berstatus
 *      berlaku/superseded berarti merusak arsip resmi yang dibaca LAKIP.
 *
 * =====================================================================
 * FIXTURE
 *
 * Memakai OPD pertama yang ada — seperti IkuSyncPenuhCheck — dengan seluruh
 * baris bertanda UJI-GANTI pada periode 2091-2095, dibuang lagi di akhir baik
 * sukses maupun gagal.
 */
class IkuSyncGantiCheck extends BaseCommand
{
    protected $group       = 'SAKIP';
    protected $name        = 'iku:sync-ganti-check';
    protected $description = 'Uji sync ganti total ke draft versi IKU baru.';
    protected $usage       = 'iku:sync-ganti-check --db <nama>';
    protected $options     = ['--db' => 'basis data salinan (WAJIB, perintah ini menulis)'];

    private const TANDA = 'UJI-GANTI';
    private const TM    = 2091;
    private const TA    = 2095;

    private int $lulus = 0;
    private int $gagal = 0;

    private function cek(string $nama, bool $ok, string $detail = ''): void
    {
        if ($ok) {
            $this->lulus++;
            CLI::write('  ' . CLI::color('LULUS', 'green') . '  ' . $nama);
        } else {
            $this->gagal++;
            CLI::write('  ' . CLI::color('GAGAL', 'red') . '  ' . $nama
                . ($detail !== '' ? ' -> ' . $detail : ''));
        }
    }

    /** Cermin IkuFormTrait::keranjangSyncPenuh() — hanya baru & berubah. */
    private function keranjangPenuh(array $kandidat): array
    {
        $baru = [];
        $ubah = [];

        foreach ($kandidat as $s) {
            foreach ($s['indikator'] ?? [] as $i) {
                if (($i['banding'] ?? '') === 'baru') {
                    $baru[(int) $s['sumber_id']][] = (int) $i['sumber_id'];
                } elseif (($i['banding'] ?? '') === 'berubah') {
                    $ubah[(int) $s['sumber_id']][] = (int) $i['sumber_id'];
                }
            }
        }

        return [$baru, $ubah];
    }

    /** Cermin IkuFormTrait::keranjangSyncSemua() — seluruh isi sumber. */
    private function keranjangSemua(array $kandidat): array
    {
        $baru = [];

        foreach ($kandidat as $s) {
            foreach ($s['indikator'] ?? [] as $i) {
                $baru[(int) $s['sumber_id']][] = (int) $i['sumber_id'];
            }
        }

        return [$baru, []];
    }

    public function run(array $params)
    {
        $namaDb = trim((string) (CLI::getOption('db') ?: ''));

        if ($namaDb === '' || $namaDb === '1') {
            CLI::error('Wajib --db <salinan>. Perintah ini menulis ke basis data.');

            return EXIT_ERROR;
        }

        if ($namaDb === config('Database')->default['database']) {
            CLI::error('Salinan harus BERBEDA dari basis data aplikasi.');

            return EXIT_ERROR;
        }

        $cfg             = config('Database')->default;
        $cfg['database'] = $namaDb;
        $db              = db_connect($cfg, false);

        CLI::write('Basis data: ' . $db->getDatabase(), 'yellow');

        $opdId = (int) ($db->table('opd')->select('id')->orderBy('id', 'ASC')
            ->get()->getRowArray()['id'] ?? 0);

        if ($opdId <= 0) {
            CLI::error('Tidak ada OPD untuk dijadikan fixture.');

            return EXIT_ERROR;
        }

        CLI::write('OPD uji   : #' . $opdId);
        CLI::newLine();

        $this->bersihkan($db, $opdId);

        try {
            $this->jalankan($db, $opdId);
            $this->ujiSilsilahRpjmd($db);
        } catch (Throwable $e) {
            $this->gagal++;
            CLI::error('Galat tak terduga: ' . $e->getMessage());
            CLI::write('  ' . $e->getFile() . ':' . $e->getLine());
        } finally {
            $this->bersihkan($db, $opdId);
        }

        CLI::newLine();
        CLI::write('LULUS ' . $this->lulus . '   GAGAL ' . $this->gagal,
            $this->gagal === 0 ? 'green' : 'red');

        return $this->gagal === 0 ? EXIT_SUCCESS : EXIT_ERROR;
    }

    private function jalankan($db, int $opdId): void
    {
        $iku    = new IkuModel($db);
        $revisi = new IkuRevisiModel($db);

        // ---- Renstra sumber: 1 sasaran, 2 indikator (A & B) --------------
        // `renstra_tujuan.rpjmd_sasaran_id` ber-foreign key ke rpjmd_sasaran,
        // jadi tidak bisa diisi NULL sembarangan — dipinjam satu yang ada,
        // seperti yang dilakukan IkuSyncPenuhCheck.
        $rpjmdSasaran = $db->table('rpjmd_sasaran')->select('id')
            ->orderBy('id', 'ASC')->get()->getRowArray();

        $db->table('renstra_tujuan')->insert([
            'tujuan'           => self::TANDA . ' Tujuan',
            'rpjmd_sasaran_id' => $rpjmdSasaran['id'] ?? null,
        ]);
        $tujuanId = (int) $db->insertID();

        $db->table('renstra_sasaran')->insert([
            'renstra_tujuan_id' => $tujuanId, 'opd_id' => $opdId,
            'sasaran'           => self::TANDA . ' Sasaran',
            'tahun_mulai'       => self::TM, 'tahun_akhir' => self::TA,
        ]);
        $sasaranId = (int) $db->insertID();

        foreach (['A', 'B'] as $n) {
            $db->table('renstra_indikator_sasaran')->insert([
                'renstra_sasaran_id' => $sasaranId,
                'indikator_sasaran'  => self::TANDA . ' Indikator ' . $n,
                'satuan'             => '%',
            ]);
            $indId = (int) $db->insertID();

            $db->table('renstra_target')->insert([
                'renstra_indikator_id' => $indId, 'tahun' => self::TM, 'target' => '10',
            ]);
        }

        // ---- IKU berjalan diisi dari Renstra, lalu DITAMBAHI satu sendiri --
        $kandidat = $iku->getKandidatSync('renstra', $opdId, self::TM, self::TA);
        [$baru, $ubah] = $this->keranjangSemua($kandidat);
        $iku->importSync('renstra', $opdId, $baru, self::TM, self::TA, null, $ubah);

        $sasIku = (int) ($db->table('iku_sasaran')->select('id')
            ->where('opd_id', $opdId)->where('tahun_mulai', self::TM)
            ->get()->getRowArray()['id'] ?? 0);

        // Indikator yang LAHIR di IKU — tidak punya padanan di Renstra.
        // Inilah yang harus HILANG sesudah ganti total.
        $db->table('iku_indikator')->insert([
            'iku_sasaran_id' => $sasIku,
            'indikator'      => self::TANDA . ' Indikator MANDIRI',
            'satuan'         => '%',
            'source_type'    => 'iku',
            'urutan'         => 99,
        ]);

        // Keterangan ketikan operator pada indikator A.
        $idA = (int) ($db->table('iku_indikator')->select('id')
            ->where('iku_sasaran_id', $sasIku)
            ->like('indikator', self::TANDA . ' Indikator A', 'after')
            ->get()->getRowArray()['id'] ?? 0);

        $db->table('iku_indikator')->where('id', $idA)->update([
            'definisi'            => 'DEFINISI KETIKAN OPERATOR',
            'rumusan_perhitungan' => 'RUMUS KETIKAN OPERATOR',
            'sumber_data'         => 'SUMBER KETIKAN OPERATOR',
            'penanggung_jawab'    => 'PJ KETIKAN OPERATOR',
        ]);

        // ---- Draft versi baru: terisi salinan IKU berjalan ----------------
        $draftId = $revisi->buatDraft([
            'opd_id'              => $opdId,
            'tahun_mulai'         => self::TM,
            'tahun_akhir'         => self::TA,
            'nama'                => self::TANDA . ' draft',
            'berlaku_mulai_tahun' => self::TM,
        ]);

        $hitungInd = static fn (int $rev): int => $db->table('iku_revisi_indikator')
            ->where('revisi_id', $rev)->countAllResults();

        CLI::write('== Titik awal: draft = salinan IKU berjalan ==', 'yellow');
        $this->cek('draft berisi 3 indikator (A, B, MANDIRI)',
            $hitungInd($draftId) === 3, (string) $hitungInd($draftId));

        // ---- Keranjang: inilah bedanya ------------------------------------
        CLI::newLine();
        CLI::write('== Keranjang: penuh vs semua ==', 'yellow');

        $kandidat2 = $iku->getKandidatSync('renstra', $opdId, self::TM, self::TA);
        [$penuhBaru, $penuhUbah] = $this->keranjangPenuh($kandidat2);
        [$semuaBaru]             = $this->keranjangSemua($kandidat2);

        $jml = static function (array $k): int {
            $n = 0;

            foreach ($k as $daftar) {
                $n += count($daftar);
            }

            return $n;
        };

        // IKU berjalan sudah sama dengan Renstra, jadi seluruh kandidat
        // bertanda 'sama' dan keranjang penuh KOSONG.
        $this->cek('keranjangSyncPenuh kosong saat IKU sudah sama dengan sumber',
            $jml($penuhBaru) + $jml($penuhUbah) === 0,
            $jml($penuhBaru) . '+' . $jml($penuhUbah));
        $this->cek('keranjangSyncSemua tetap memuat 2 indikator',
            $jml($semuaBaru) === 2, (string) $jml($semuaBaru));

        // ---- GANTI TOTAL --------------------------------------------------
        CLI::newLine();
        CLI::write('== Ganti total ==', 'yellow');

        $keterangan = $revisi->keteranganDraft($draftId);
        $this->cek('keterangan operator terekam sebelum dikosongkan',
            ($keterangan['silsilah'] !== [] || $keterangan['teks'] !== []));

        $dibuang = $revisi->kosongkanIsiDraft($draftId);

        $this->cek('kosongkanIsiDraft melaporkan 3 indikator dibuang',
            (int) $dibuang['indikator'] === 3, (string) $dibuang['indikator']);
        $this->cek('draft benar-benar kosong sesudahnya', $hitungInd($draftId) === 0);

        $revisi->imporKandidat($draftId, $kandidat2, $semuaBaru, 'renstra', null, []);
        $pulih = $revisi->kembalikanKeteranganDraft($draftId, $keterangan);

        $isi = $db->table('iku_revisi_indikator')->select('indikator, definisi,
                rumusan_perhitungan, sumber_data, penanggung_jawab')
            ->where('revisi_id', $draftId)->get()->getResultArray();

        $teks = array_column($isi, 'indikator');

        $this->cek('draft kini berisi TEPAT 2 indikator, sesuai Renstra',
            count($isi) === 2, count($isi) . ' -> ' . implode(' | ', $teks));

        $adaMandiri = false;

        foreach ($teks as $t) {
            if (str_contains((string) $t, 'MANDIRI')) {
                $adaMandiri = true;
            }
        }

        $this->cek('indikator MANDIRI (tanpa padanan Renstra) TIDAK ikut terbawa',
            $adaMandiri === false);

        // =============================================================
        // TIDAK BOLEH ADA LABEL "Tetap"
        //
        // Kolom PERUBAHAN pada layar versi membaca `jenis_perubahan`. Label
        // "Tetap" berarti baris itu SUDAH ADA di draft sebelum sync dan hanya
        // diteruskan — yaitu warisan IKU lama. Pada ganti total draftnya
        // dikosongkan lebih dulu, jadi setiap baris memang benar-benar baru
        // bagi versi ini.
        //
        // Diuji lewat kolomnya, bukan lewat jumlah baris: draft bisa saja
        // berjumlah benar tetapi tetap membawa satu baris warisan yang
        // kebetulan juga ada di sumber.
        // =============================================================
        $label = $db->table('iku_revisi_indikator')
            ->select('jenis_perubahan, COUNT(*) AS n')
            ->where('revisi_id', $draftId)
            ->groupBy('jenis_perubahan')
            ->get()->getResultArray();

        $adaTetap = 0;
        $ringkas  = [];

        foreach ($label as $l) {
            $ringkas[] = $l['jenis_perubahan'] . '=' . $l['n'];

            if ($l['jenis_perubahan'] === 'tetap') {
                $adaTetap = (int) $l['n'];
            }
        }

        $this->cek('tidak ada indikator berlabel "Tetap" sesudah ganti total',
            $adaTetap === 0, implode(' ', $ringkas));

        $labelSas = $db->table('iku_revisi_sasaran')
            ->where('revisi_id', $draftId)->where('jenis_perubahan', 'tetap')->countAllResults();

        $this->cek('tidak ada sasaran berlabel "Tetap" sesudah ganti total',
            $labelSas === 0, (string) $labelSas);

        foreach (['A', 'B'] as $n) {
            $ada = false;

            foreach ($teks as $t) {
                if (str_contains((string) $t, 'Indikator ' . $n)) {
                    $ada = true;
                }
            }

            $this->cek('indikator ' . $n . ' dari Renstra MASUK', $ada);
        }

        // ---- Keterangan operator selamat ----------------------------------
        CLI::newLine();
        CLI::write('== Keterangan operator ==', 'yellow');

        $barisA = null;

        foreach ($isi as $r) {
            if (str_contains((string) $r['indikator'], 'Indikator A')) {
                $barisA = $r;
            }
        }

        $this->cek('kembalikanKeteranganDraft melaporkan pemulihan', $pulih > 0, (string) $pulih);
        $this->cek('definisi operator kembali',
            ($barisA['definisi'] ?? '') === 'DEFINISI KETIKAN OPERATOR',
            (string) ($barisA['definisi'] ?? '-'));
        $this->cek('rumusan operator kembali',
            ($barisA['rumusan_perhitungan'] ?? '') === 'RUMUS KETIKAN OPERATOR');
        $this->cek('sumber data operator kembali',
            ($barisA['sumber_data'] ?? '') === 'SUMBER KETIKAN OPERATOR');
        $this->cek('penanggung jawab operator kembali',
            ($barisA['penanggung_jawab'] ?? '') === 'PJ KETIKAN OPERATOR');

        // ---- IKU berjalan tidak tersentuh ---------------------------------
        CLI::newLine();
        CLI::write('== IKU berjalan tidak disentuh ==', 'yellow');

        $liveInd = $db->table('iku_indikator')->where('iku_sasaran_id', $sasIku)->countAllResults();

        $this->cek('IKU berjalan tetap 3 indikator (termasuk MANDIRI)',
            $liveInd === 3, (string) $liveInd);

        // ---- Yang bukan draft ditolak -------------------------------------
        CLI::newLine();
        CLI::write('== Hanya draft yang boleh dikosongkan ==', 'yellow');

        // Dipakai 'menunggu', bukan 'berlaku': kunci unik uq_iku_revisi_efektif
        // hanya berlaku bagi revisi yang BERLAKU, dan revisi baseline sudah
        // menempati tahun yang sama — bentrokannya akan menguji basis data,
        // bukan penjaga yang sedang diuji di sini.
        $db->table('iku_revisi')->where('id', $draftId)->update(['status' => 'menunggu']);

        $ditolak = false;

        try {
            $revisi->kosongkanIsiDraft($draftId);
        } catch (Throwable $e) {
            $ditolak = str_contains($e->getMessage(), 'Hanya draft');
        }

        $this->cek('revisi berstatus bukan draft DITOLAK', $ditolak);
        $this->cek('isinya tetap utuh sesudah penolakan', $hitungInd($draftId) === 2);

        $db->table('iku_revisi')->where('id', $draftId)->update(['status' => 'draft']);
    }

    private function bersihkan($db, int $opdId): void
    {
        foreach ($db->table('iku_revisi')->where('opd_id', $opdId)
            ->like('nama', self::TANDA, 'after')->get()->getResultArray() as $r) {
            $db->table('iku_revisi_indikator')->where('revisi_id', (int) $r['id'])->delete();
            $db->table('iku_revisi_sasaran')->where('revisi_id', (int) $r['id'])->delete();
        }

        $db->table('iku_revisi')->where('opd_id', $opdId)
            ->where('tahun_mulai', self::TM)->delete();

        $sasIku = $db->table('iku_sasaran')->select('id')
            ->where('opd_id', $opdId)->where('tahun_mulai', self::TM)->get()->getResultArray();

        foreach ($sasIku as $s) {
            $ind = $db->table('iku_indikator')->select('id')
                ->where('iku_sasaran_id', $s['id'])->get()->getResultArray();

            foreach ($ind as $i) {
                $db->table('iku_target')->where('iku_indikator_id', $i['id'])->delete();
                $db->table('iku_program')->where('iku_indikator_id', $i['id'])->delete();
            }

            $db->table('iku_indikator')->where('iku_sasaran_id', $s['id'])->delete();
        }

        $db->table('iku_sasaran')->where('opd_id', $opdId)
            ->where('tahun_mulai', self::TM)->delete();

        $sasRen = $db->table('renstra_sasaran')->select('id')
            ->where('opd_id', $opdId)->where('tahun_mulai', self::TM)->get()->getResultArray();

        foreach ($sasRen as $s) {
            $ind = $db->table('renstra_indikator_sasaran')->select('id')
                ->where('renstra_sasaran_id', $s['id'])->get()->getResultArray();

            foreach ($ind as $i) {
                $db->table('renstra_target')->where('renstra_indikator_id', $i['id'])->delete();
            }

            $db->table('renstra_indikator_sasaran')->where('renstra_sasaran_id', $s['id'])->delete();
        }

        $db->table('renstra_sasaran')->where('opd_id', $opdId)
            ->where('tahun_mulai', self::TM)->delete();
        $db->table('renstra_tujuan')->like('tujuan', self::TANDA, 'after')->delete();
    }

    /**
     * Kandidat dari RPJMD BERJALAN wajib membawa silsilahnya.
     *
     * =====================================================================
     * MENGAPA INI DIUJI TERPISAH
     *
     * `sumber_live_id` menautkan kandidat ke baris RPJMD yang menurunkannya.
     * Ia diteruskan imporKandidat() ke `iku_revisi_indikator.source_ref_id`,
     * lalu oleh pengesahan ke `iku_indikator.source_indikator_id` — dan
     * Cascading Kabupaten menjembatani RPJMD ke IKU LEWAT kolom terakhir itu.
     *
     * Selama ini hanya jalur Renstra dan jalur ARSIP RPJMD yang memilihnya;
     * jalur RPJMD BERJALAN terlewat. Kelalaian itu tidak menimbulkan galat
     * apa pun: sync tetap berhasil, indikatornya tetap masuk, hanya
     * silsilahnya kosong — dan cascading diam-diam jatuh kembali ke teks
     * RPJMD seolah IKU tidak pernah ada.
     *
     * Pada 6 September 2026 hal itu benar-benar terjadi pada data kerja: 10
     * sasaran dan 15 indikator IKU Kabupaten masuk tanpa satu pun silsilah.
     */
    private function ujiSilsilahRpjmd($db): void
    {
        CLI::newLine();
        CLI::write('== Silsilah kandidat RPJMD berjalan ==', 'yellow');

        $iku = new IkuModel($db);

        // Periode diambil dari isi RPJMD yang ada, bukan dipatok, supaya uji
        // ini tetap berarti di basis data mana pun.
        $periode = $db->table('rpjmd_misi')
            ->select('tahun_mulai, tahun_akhir')
            ->groupBy('tahun_mulai, tahun_akhir')
            ->orderBy('tahun_mulai', 'DESC')
            ->get()->getRowArray();

        if ($periode === null) {
            CLI::write('  (dilewati: tidak ada periode RPJMD)');

            return;
        }

        $kandidat = $iku->getKandidatSync(
            'rpjmd', null, (int) $periode['tahun_mulai'], (int) $periode['tahun_akhir']
        );

        if ($kandidat === []) {
            CLI::write('  (dilewati: RPJMD berjalan kosong pada periode itu)');

            return;
        }

        $sasTanpa = 0;
        $indAda   = 0;
        $indTanpa = 0;

        foreach ($kandidat as $sasaran) {
            if (empty($sasaran['sumber_live_id'])) {
                $sasTanpa++;
            }

            foreach ($sasaran['indikator'] ?? [] as $ind) {
                $indAda++;

                if (empty($ind['sumber_live_id'])) {
                    $indTanpa++;
                }
            }
        }

        $this->cek('setiap sasaran RPJMD berjalan membawa sumber_live_id',
            $sasTanpa === 0, $sasTanpa . ' dari ' . count($kandidat) . ' kosong');
        $this->cek('setiap indikator RPJMD berjalan membawa sumber_live_id',
            $indTanpa === 0, $indTanpa . ' dari ' . $indAda . ' kosong');
    }
}
