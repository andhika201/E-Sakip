<?php

namespace App\Models\Opd;

use App\Models\Concerns\TransaksiAman;
use CodeIgniter\Model;
use RuntimeException;
use Throwable;

/**
 * REVISI IKU — versi dokumen IKU yang bernomor, bertanggal berlaku, dan beku.
 *
 * ---------------------------------------------------------------------
 * MASALAH YANG DISELESAIKAN
 *
 * IKU berumur ~5 tahun tapi bisa direvisi di tengah periode: target berubah,
 * rumusan sasaran diperbaiki, indikator ditambah, diganti, atau tidak dipakai
 * lagi. Sebelum modul ini, semua perubahan itu menimpa satu-satunya salinan
 * data yang hidup — dan IkuModel::updateComplete() bahkan MENGHAPUS indikator
 * yang hilang dari form. Akibatnya LAKIP tahun-tahun lampau ikut berubah
 * bunyinya setiap kali IKU disunting.
 *
 * ---------------------------------------------------------------------
 * BAGAIMANA VERSINYA DISIMPAN
 *
 * Tabel live (`iku_sasaran`/`iku_indikator`/`iku_target`/`iku_program`) TETAP
 * berisi versi yang SEDANG BERLAKU. Itu keputusan sadar: tabel-tabel itu
 * dibaca API publik, halaman publik, dan tiga model dashboard — membiarkannya
 * apa adanya membuat seluruh pembaca lama tidak perlu diubah sebaris pun.
 *
 * Tabel `iku_revisi_*` menyimpan dua hal sekaligus:
 *   * ARSIP BEKU tiap versi yang pernah berlaku (inilah sumber LAKIP), dan
 *   * ISI DRAFT yang sedang diusulkan tapi belum disahkan.
 *
 * Alurnya:
 *
 *   buatDraft()  salin kondisi live saat ini jadi titik awal usulan
 *                -> status 'draft'. TIDAK menyentuh tabel live sama sekali.
 *   sahkan()     terapkan isi draft ke tabel live, geser revisi sebelumnya
 *                jadi 'superseded', arsipnya tetap utuh selamanya.
 *   batalkan()   draft dibuang -> status 'batal' (tidak dihapus, jejaknya ada).
 *
 * ---------------------------------------------------------------------
 * INVARIANT YANG DIJAGA DI SINI
 *
 *  1. Lifecycle draft -> berlaku -> superseded/batal. Draft tidak pernah jadi
 *     sumber LAKIP: resolveEfektif() hanya melihat status 'berlaku'.
 *  2. Satu revisi efektif. UNIQUE index di basis data yang menjaminnya;
 *     resolveEfektif() memeriksa ULANG dan MENOLAK bila ada lebih dari satu,
 *     bukan memilih salah satu diam-diam.
 *  4. Perubahan indikator dibedakan: revisi / pengganti / tambahan baru, dan
 *     penggantian menyimpan lineage lewat `indikator_sebelumnya_id`.
 *  7. Semua operasi majemuk dibungkus transaksi; gagal di tengah = rollback
 *     penuh, tidak ada revisi setengah jadi.
 *  8. Indikator yang sudah direferensikan sejarah TIDAK PERNAH dihapus,
 *     hanya dipensiunkan (`dihentikan_pada`).
 * ---------------------------------------------------------------------
 */
class IkuRevisiModel extends Model
{
    /** transBegin/transCommit yang benar-benar bisa di-rollback (invariant 7). */
    use TransaksiAman;

    protected $table         = 'iku_revisi';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = [
        'opd_id', 'tahun_mulai', 'tahun_akhir', 'nomor', 'nama',
        'dasar_hukum', 'nomor_dasar', 'tanggal_dasar',
        'berlaku_mulai_tahun', 'berlaku_sampai_tahun', 'status', 'catatan',
        'dibuat_oleh', 'disahkan_oleh', 'disahkan_pada', 'dibekukan_pada',
        'created_at', 'updated_at',
    ];

    protected $useTimestamps = false;

    /** Status yang dikenal lifecycle. */
    public const STATUS_DRAFT      = 'draft';
    /** Diajukan penyusun, menunggu keputusan verifikator (§17). */
    public const STATUS_MENUNGGU   = 'menunggu';
    public const STATUS_BERLAKU    = 'berlaku';
    public const STATUS_SUPERSEDED = 'superseded';
    public const STATUS_BATAL      = 'batal';

    /** Jenis perubahan indikator — dipakai untuk memutus/menyambung tren. */
    public const UBAH_TETAP      = 'tetap';
    public const UBAH_REVISI     = 'revisi';
    public const UBAH_PENGGANTI  = 'pengganti';
    public const UBAH_BARU       = 'baru';
    public const UBAH_DIHENTIKAN = 'dihentikan';

    /**
     * Sama dengan IkuModel: `satuan` boleh berisi id numerik ke tabel master
     * `satuan`, boleh juga teks bebas. Arsip membekukan HASIL terjemahannya
     * supaya penggantian nama di tabel master tidak mengubah bunyi arsip.
     */
    private const SATUAN_JOIN   = "ind.satuan REGEXP '^[0-9]+$' AND sat.id = ind.satuan";
    private const SATUAN_SELECT = "COALESCE(sat.satuan, NULLIF(ind.satuan, ''))";

    /* =========================================================
     * KESIAPAN
     * =======================================================*/

    /**
     * Tabel revisi sudah ada atau belum.
     *
     * Meniru LakipAnalisisModel::siap(): seluruh modul harus tetap jalan
     * (tanpa fitur revisi) bila SQL 2026-08-18 belum dijalankan di server itu.
     */
    public function siap(): bool
    {
        static $siap = null;

        if ($siap !== null) {
            return $siap;
        }

        try {
            foreach (['iku_revisi', 'iku_revisi_sasaran', 'iku_revisi_indikator', 'iku_revisi_target'] as $t) {
                if (! $this->db->tableExists($t)) {
                    return $siap = false;
                }
            }

            return $siap = true;
        } catch (Throwable $e) {
            return $siap = false;
        }
    }

    /* =========================================================
     * PEMBACAAN
     * =======================================================*/

    /**
     * Daftar revisi satu lingkup (scope + periode), terbaru dulu.
     *
     * @param int|null $opdId NULL = tingkat kabupaten
     *
     * @return array<int, array<string, mixed>>
     */
    public function daftar(?int $opdId, ?int $tahunMulai = null, ?int $tahunAkhir = null): array
    {
        if (! $this->siap()) {
            return [];
        }

        $b = $this->db->table('iku_revisi')
            ->select('*')
            ->where('opd_key', $opdId === null ? 0 : $opdId);

        if ($tahunMulai !== null) {
            $b->where('tahun_mulai', $tahunMulai);
        }
        if ($tahunAkhir !== null) {
            $b->where('tahun_akhir', $tahunAkhir);
        }

        return $b->orderBy('tahun_mulai', 'DESC')
            ->orderBy('nomor', 'DESC')
            ->get()
            ->getResultArray();
    }

    /** Satu revisi berikut lingkupnya. */
    public function ambil(int $revisiId): ?array
    {
        if (! $this->siap() || $revisiId <= 0) {
            return null;
        }

        return $this->db->table('iku_revisi')->where('id', $revisiId)->get()->getRowArray() ?: null;
    }

    /**
     * REVISI YANG EFEKTIF PADA SATU TAHUN — inti invariant 2 / Case 11.
     *
     * Tidak pernah mengembalikan draft maupun revisi yang dibatalkan
     * (invariant 1).
     *
     * ---------------------------------------------------------------------
     * MENGAPA 'superseded' IKUT DIPERTIMBANGKAN
     *
     * 'superseded' berarti "bukan versi terkini", BUKAN "tidak pernah berlaku".
     * Revisi ke-0 yang berlaku 2090-2091 lalu digeser revisi ke-1 mulai 2092
     * tetap merupakan dokumen yang sah untuk tahun 2090 dan 2091 — dan LAKIP
     * kedua tahun itu wajib membacanya.
     *
     * Kalau hanya status 'berlaku' yang dilihat, tahun-tahun lampau justru
     * kehilangan sumbernya begitu sebuah revisi disahkan; persis kebalikan
     * dari tujuan modul ini.
     *
     * Yang memisahkan versi karena itu adalah JENDELA MASA BERLAKU
     * [berlaku_mulai_tahun .. berlaku_sampai_tahun], yang dibuat saling lepas
     * oleh sahkan(). UNIQUE index basis data menjaga sisi 'berlaku'-nya;
     * pemeriksaan jumlah di bawah menjaga sisa kemungkinan lainnya.
     * ---------------------------------------------------------------------
     *
     * Kalau ada lebih dari satu revisi 'berlaku' yang memayungi tahun yang
     * sama, method ini TIDAK memilih salah satu. Ia mengembalikan daftar
     * konflik supaya pemanggil menampilkan galat administratif — diam-diam
     * memilih versi berarti angka LAKIP jadi bergantung pada urutan baris,
     * dan itu persis yang dilarang.
     *
     * Basis data sebenarnya sudah mencegah keadaan ini lewat UNIQUE index
     * `uq_iku_revisi_efektif`. Pemeriksaan di sini adalah lapis kedua untuk
     * basis data lama yang indexnya belum terpasang.
     *
     * @return array{revisi: array<string,mixed>|null, konflik: array<int, array<string,mixed>>, pesan: string|null}
     */
    public function resolveEfektif(?int $opdId, int $tahun): array
    {
        $kosong = ['revisi' => null, 'konflik' => [], 'pesan' => null];

        if (! $this->siap() || $tahun <= 0) {
            return $kosong;
        }

        $kandidat = $this->db->table('iku_revisi')
            ->select('*')
            ->where('opd_key', $opdId === null ? 0 : $opdId)
            ->whereIn('status', [self::STATUS_BERLAKU, self::STATUS_SUPERSEDED])
            ->where('berlaku_mulai_tahun <=', $tahun)
            ->groupStart()
                ->where('berlaku_sampai_tahun IS NULL', null, false)
                ->orWhere('berlaku_sampai_tahun >=', $tahun)
            ->groupEnd()
            ->where('tahun_mulai <=', $tahun)
            ->where('tahun_akhir >=', $tahun)
            ->orderBy('berlaku_mulai_tahun', 'DESC')
            ->orderBy('nomor', 'DESC')
            ->get()
            ->getResultArray();

        if (empty($kandidat)) {
            // Belum ada revisi apa pun untuk lingkup ini. Bukan galat: modul
            // berjalan seperti sebelum fitur ini ada, membaca tabel live.
            return $kosong;
        }

        if (count($kandidat) > 1) {
            $nama = implode(', ', array_map(
                static fn ($r) => '"' . $r['nama'] . '" (revisi ke-' . $r['nomor'] . ')',
                $kandidat
            ));

            return [
                'revisi'  => null,
                'konflik' => $kandidat,
                'pesan'   => 'Konflik revisi IKU pada tahun ' . $tahun . ': terdapat '
                    . count($kandidat) . ' revisi yang sama-sama berlaku — ' . $nama
                    . '. Perbaiki dulu masa berlakunya (hanya satu revisi boleh berlaku'
                    . ' untuk satu tahun) sebelum data tahun ini dipakai.',
            ];
        }

        return ['revisi' => $kandidat[0], 'konflik' => [], 'pesan' => null];
    }

    /**
     * Isi satu revisi dalam bentuk yang SAMA dengan IkuModel::getMatrix(),
     * supaya view & pencetak IKU yang sudah ada bisa disuapi arsip tanpa diubah.
     *
     * Baris ber-jenis_perubahan 'dihentikan' adalah NISAN, bukan isi: ia
     * mencatat bahwa sebuah indikator berhenti dipakai pada revisi ini. Kalau
     * ikut dikembalikan, LAKIP tahun tersebut akan mencetak indikator yang
     * justru sudah dihentikan. Karena itu ia disaring di sini, tapi TETAP
     * tersimpan di tabel arsip — jejak "apa yang berubah" ada di sana, dan
     * halaman perbandingan revisi memanggilnya dengan $sertakanDihentikan.
     *
     * @param bool $sertakanDihentikan sertakan nisan (untuk tampilan perbandingan)
     *
     * @return array<int, array<string, mixed>>
     */
    public function isiRevisi(int $revisiId, bool $sertakanDihentikan = false): array
    {
        if (! $this->siap() || $revisiId <= 0) {
            return [];
        }

        $sasaranRows = $this->db->table('iku_revisi_sasaran')
            ->where('revisi_id', $revisiId)
            ->orderBy('urutan', 'ASC')
            ->orderBy('id', 'ASC')
            ->get()
            ->getResultArray();

        if (empty($sasaranRows)) {
            return [];
        }

        $bi = $this->db->table('iku_revisi_indikator')
            ->where('revisi_id', $revisiId);

        if (! $sertakanDihentikan) {
            $bi->where('jenis_perubahan !=', self::UBAH_DIHENTIKAN);
        }

        $indikatorRows = $bi->orderBy('urutan', 'ASC')->orderBy('id', 'ASC')->get()->getResultArray();

        $indikatorIds = array_map('intval', array_column($indikatorRows, 'id'));
        $targetMap    = $this->petaTargetRevisi($indikatorIds);
        $programMap   = $this->petaProgramRevisi($indikatorIds);

        $perSasaran = [];
        foreach ($indikatorRows as $ind) {
            // `satuan_nama` sudah dibekukan; nama kuncinya disamakan dengan
            // getMatrix() supaya view tidak perlu tahu ini arsip atau bukan.
            $ind['satuan_nama'] = $ind['satuan_nama'] ?? null;
            $ind['target']      = $targetMap[(int) $ind['id']] ?? [];
            $ind['program']     = $programMap[(int) $ind['id']] ?? [];

            $perSasaran[(int) $ind['revisi_sasaran_id']][] = $ind;
        }

        $hasil = [];
        foreach ($sasaranRows as $sas) {
            $sas['indikator'] = $perSasaran[(int) $sas['id']] ?? [];

            // Sasaran yang seluruh indikatornya dihentikan ikut hilang dari isi
            // revisi — sama seperti perilaku getMatrix() pada data berjalan.
            if (! $sertakanDihentikan
                && empty($sas['indikator'])
                && $sas['jenis_perubahan'] === self::UBAH_DIHENTIKAN) {
                continue;
            }

            $hasil[] = $sas;
        }

        return $hasil;
    }

    /* =========================================================
     * PEMBUATAN DRAFT
     * =======================================================*/

    /**
     * Buat draft revisi baru, berisi salinan kondisi IKU saat ini sebagai titik
     * awal usulan.
     *
     * Seluruhnya satu transaksi (invariant 7): kalau penyalinan indikator gagal
     * di tengah, kepala revisinya ikut dibatalkan — tidak ada revisi setengah
     * jadi yang menyesatkan.
     *
     * TIDAK menyentuh tabel live sama sekali (invariant 1 & 5 / Case 15).
     *
     * @param array{
     *     opd_id: int|null, tahun_mulai: int, tahun_akhir: int,
     *     nama?: string, dasar_hukum?: string|null, nomor_dasar?: string|null,
     *     tanggal_dasar?: string|null, berlaku_mulai_tahun: int,
     *     catatan?: string|null, dibuat_oleh?: int|null
     * } $data
     *
     * @return int id revisi draft yang dibuat
     */
    public function buatDraft(array $data): int
    {
        if (! $this->siap()) {
            throw new RuntimeException('Tabel revisi IKU belum tersedia. Jalankan db/update_2026-08-18_iku_revisi_lakip_snapshot.sql terlebih dahulu.');
        }

        $opdId      = isset($data['opd_id']) && $data['opd_id'] !== null ? (int) $data['opd_id'] : null;
        $tahunMulai = (int) $data['tahun_mulai'];
        $tahunAkhir = (int) $data['tahun_akhir'];
        $berlaku    = (int) $data['berlaku_mulai_tahun'];

        $this->validasiLingkupDraft($tahunMulai, $tahunAkhir, $berlaku);
        $this->tolakTahunBentrok($opdId, $tahunMulai, $tahunAkhir, $berlaku);

        return $this->dalamTransaksi(
            fn () => $this->buatDraftInti($data, $opdId, $tahunMulai, $tahunAkhir, $berlaku),
            'pembuatan draft revisi IKU'
        );
    }

    /**
     * Isi buatDraft() tanpa membuka transaksi sendiri.
     *
     * WAJIB dipanggil dari dalam transaksi (lihat TransaksiAman). Dipisahkan
     * supaya LakipPenyesuaianModel::usulkanRevisiIku() bisa membuat draft
     * revisi DAN menautkannya ke baris penyesuaian dalam SATU transaksi —
     * kalau penautannya gagal, draft yatim tidak ikut tertinggal (invariant 7).
     *
     * Membuka transaksi kedua di sini justru berbahaya: transaksi bersarang
     * CodeIgniter tidak melakukan rollback sungguhan.
     *
     * @internal dipakai lintas model, bukan bagian dari API publik modul IKU
     */
    public function buatDraftInti(array $data, ?int $opdId, int $tahunMulai, int $tahunAkhir, int $berlaku): int
    {
        // Divalidasi ulang di sini, bukan hanya di buatDraft(), karena method
        // ini juga dipanggil langsung dari modul penyesuaian LAKIP.
        $this->validasiLingkupDraft($tahunMulai, $tahunAkhir, $berlaku);

        {
            // Versi awal (revisi ke-0) dimaterialisasi lebih dulu supaya
            // kondisi IKU SEBELUM revisi pertama pun punya arsip beku. Tanpa
            // ini, LAKIP tahun-tahun sebelum revisi pertama tidak punya versi
            // yang bisa dirujuk.
            $this->pastikanBaseline($opdId, $tahunMulai, $tahunAkhir, $data['dibuat_oleh'] ?? null);

            $nomorBaru = $this->nomorBerikutnya($opdId, $tahunMulai, $tahunAkhir);

            $revisiId = $this->sisipkanKepala([
                'opd_id'              => $opdId,
                'tahun_mulai'         => $tahunMulai,
                'tahun_akhir'         => $tahunAkhir,
                'nomor'               => $nomorBaru,
                'nama'                => trim((string) ($data['nama'] ?? '')) !== ''
                    ? (string) $data['nama']
                    : ('Revisi ke-' . $nomorBaru . ' IKU ' . $tahunMulai . '-' . $tahunAkhir),
                'dasar_hukum'         => $this->kosongJadiNull($data['dasar_hukum'] ?? null),
                'nomor_dasar'         => $this->kosongJadiNull($data['nomor_dasar'] ?? null),
                'tanggal_dasar'       => $this->kosongJadiNull($data['tanggal_dasar'] ?? null),
                'berlaku_mulai_tahun' => $berlaku,
                'status'              => self::STATUS_DRAFT,
                'catatan'             => $this->kosongJadiNull($data['catatan'] ?? null),
                'dibuat_oleh'         => isset($data['dibuat_oleh']) ? (int) $data['dibuat_oleh'] : null,
            ]);

            // Titik awal usulan = kondisi live sekarang (versi yang berlaku).
            $this->bekukanLiveKeRevisi($revisiId, $opdId, $tahunMulai, $tahunAkhir, false);

            return $revisiId;
        }
    }

    /** Periode & tahun berlaku masuk akal. */
    /**
     * Tolak sejak AWAL bila tahun mulai berlakunya sudah dipakai revisi lain.
     *
     * =====================================================================
     * MENGAPA DIPERIKSA DI SINI, BUKAN CUKUP DI sahkan()
     *
     * `sahkan()` memang menolak dua revisi yang mulai di tahun sama, dan basis
     * data pun akan menolaknya. Tetapi penolakan di sana datang TERLALU LAMBAT
     * dan pada ORANG YANG SALAH: penyusun sudah selesai menyunting, sudah
     * mengajukan, dan yang menabrak galatnya adalah Admin Kabupaten saat
     * hendak mengesahkan.
     *
     * Diperiksa di sini, kekeliruannya ketahuan pada detik draft dibuat, oleh
     * orang yang bisa langsung memperbaikinya.
     */
    private function tolakTahunBentrok(?int $opdId, int $tahunMulai, int $tahunAkhir, int $berlaku): void
    {
        $bentrok = $this->lingkup($opdId, $tahunMulai, $tahunAkhir)
            ->whereIn('status', [self::STATUS_BERLAKU, self::STATUS_SUPERSEDED, self::STATUS_MENUNGGU])
            ->where('berlaku_mulai_tahun', $berlaku)
            ->get()->getRowArray();

        if ($bentrok !== null) {
            // Menolak saja memaksa operator menebak; disebutkan sekalian
            // siapa yang memakai tahun itu DAN tahun mana yang masih kosong.
            $bebas = $this->tahunBerlakuBebas($opdId, $tahunMulai, $tahunAkhir);

            throw new RuntimeException(
                'Tahun ' . $berlaku . ' sudah dipakai revisi lain ("' . $bentrok['nama'] . '", '
                . 'revisi ke-' . $bentrok['nomor'] . ', status ' . $bentrok['status'] . '). '
                . 'Satu tahun hanya boleh dipayungi satu revisi. '
                . ($bebas === []
                    ? 'Seluruh tahun pada periode ini sudah terpakai — geser dulu tahun berlaku salah satu revisi yang ada.'
                    : 'Tahun yang masih kosong: ' . implode(', ', $bebas) . '.')
            );
        }
    }

    /**
     * Tahun mulai berlaku yang SUDAH dipakai pada satu lingkup, beserta
     * pemakainya.
     *
     * Dipakai layar untuk dua hal sekaligus: menyodorkan tahun yang masih
     * kosong, dan menyebut revisi mana yang menghalangi. Menolak tanpa
     * menyebut keduanya memaksa operator menebak.
     *
     * @return array<int,array{id:int,nomor:string,nama:string,status:string}>
     *         [tahun => revisi pemakainya]
     */
    public function tahunBerlakuTerpakai(?int $opdId, int $tahunMulai, int $tahunAkhir, ?int $kecuali = null): array
    {
        if (! $this->siap()) {
            return [];
        }

        $b = $this->lingkup($opdId, $tahunMulai, $tahunAkhir)
            ->select('id, nomor, nama, status, berlaku_mulai_tahun')
            ->whereIn('status', [self::STATUS_BERLAKU, self::STATUS_SUPERSEDED, self::STATUS_MENUNGGU]);

        if ($kecuali !== null) {
            $b->where('id !=', $kecuali);
        }

        $peta = [];

        foreach ($b->get()->getResultArray() as $r) {
            $peta[(int) $r['berlaku_mulai_tahun']] = [
                'id'     => (int) $r['id'],
                'nomor'  => (string) $r['nomor'],
                'nama'   => (string) $r['nama'],
                'status' => (string) $r['status'],
            ];
        }

        return $peta;
    }

    /**
     * Tahun yang MASIH BEBAS dipakai sebuah revisi pada satu lingkup.
     *
     * Tahun PERTAMA periode ikut dihitung. Dulu ia selalu dikecualikan sebagai
     * "jatah Kondisi Awal", padahal yang membuatnya tidak bisa dipakai bukan
     * jatah melainkan kenyataan bahwa Kondisi Awal sedang menempatinya — dan
     * itu sudah tertangkap $terpakai. Mengecualikannya secara buta membuat
     * tahun itu HILANG dari daftar bahkan ketika benar-benar kosong, sehingga
     * pemakai tidak punya cara menempatkan revisi di awal periode dan tidak
     * diberi tahu alasannya.
     *
     * @return int[]
     */
    public function tahunBerlakuBebas(?int $opdId, int $tahunMulai, int $tahunAkhir, ?int $kecuali = null): array
    {
        $terpakai = $this->tahunBerlakuTerpakai($opdId, $tahunMulai, $tahunAkhir, $kecuali);

        $bebas = [];

        foreach (range($tahunMulai, $tahunAkhir) as $th) {
            if (! isset($terpakai[$th])) {
                $bebas[] = $th;
            }
        }

        return $bebas;
    }

    private function validasiLingkupDraft(int $tahunMulai, int $tahunAkhir, int $berlaku): void
    {
        if ($tahunMulai <= 0 || $tahunAkhir < $tahunMulai) {
            throw new RuntimeException('Periode IKU tidak valid.');
        }

        if ($berlaku < $tahunMulai || $berlaku > $tahunAkhir) {
            throw new RuntimeException(
                'Tahun mulai berlaku harus berada di dalam periode ' . $tahunMulai . '-' . $tahunAkhir . '.'
            );
        }
    }

    /**
     * Pastikan lingkup ini punya revisi ke-0 (kondisi awal) yang sudah berlaku.
     *
     * Dipanggil dari dalam transaksi pemanggil — sengaja tidak membuka
     * transaksi sendiri supaya rollback-nya menyeluruh.
     *
     * @return int|null id baseline, atau null bila sudah ada revisi lain
     */
    public function pastikanBaseline(?int $opdId, int $tahunMulai, int $tahunAkhir, ?int $userId = null): ?int
    {
        $sudahAda = $this->db->table('iku_revisi')
            ->where('opd_key', $opdId === null ? 0 : $opdId)
            ->where('tahun_mulai', $tahunMulai)
            ->where('tahun_akhir', $tahunAkhir)
            ->countAllResults();

        if ($sudahAda > 0) {
            return null;
        }

        $baselineId = $this->sisipkanKepala([
            'opd_id'              => $opdId,
            'tahun_mulai'         => $tahunMulai,
            'tahun_akhir'         => $tahunAkhir,
            'nomor'               => 0,
            'nama'                => 'Kondisi Awal IKU ' . $tahunMulai . '-' . $tahunAkhir,
            'berlaku_mulai_tahun' => $tahunMulai,
            'status'              => self::STATUS_BERLAKU,
            'catatan'             => 'Dibekukan otomatis dari kondisi IKU yang berlaku saat revisi pertama dibuat.',
            'dibuat_oleh'         => $userId,
            'disahkan_oleh'       => $userId,
            'disahkan_pada'       => date('Y-m-d H:i:s'),
        ]);

        $this->bekukanLiveKeRevisi($baselineId, $opdId, $tahunMulai, $tahunAkhir, false);

        return $baselineId;
    }

    /**
     * Pulihkan Kondisi Awal (revisi ke-0) yang HILANG pada lingkup yang sudah
     * punya revisi bernomor.
     *
     * pastikanBaseline() sengaja menolak bekerja begitu ada baris lain di
     * lingkup itu — asumsi wajarnya: kalau revisi ke-1 ada, baseline pasti
     * sudah dibuat lebih dulu. Asumsi itu bisa meleset (baris terhapus di luar
     * alur aplikasi), dan akibatnya nyata: tahun-tahun SEBELUM revisi pertama
     * tidak dipayungi siapa pun, sehingga LAKIP bersumber IKU menolak melayani
     * tahun-tahun itu ("belum ada versi").
     *
     * Baseline pulihan langsung DIJAHIT ke timeline yang ada: bila sudah ada
     * revisi berlaku/superseded, ia lahir berstatus superseded dan ditutup
     * tepat sebelum revisi paling awal — meniru persis apa yang akan
     * dilakukan sahkan() seandainya urutannya normal.
     *
     * CATATAN JUJUR: isinya dibekukan dari IKU BERJALAN SEKARANG. Bila revisi
     * sesudahnya sempat mengubah isi indikator, arsip pulihan ini memotret
     * keadaan sesudah perubahan itu — koreksinya lewat izin sunting Kondisi
     * Awal, bukan dengan menebak-nebak keadaan lampau secara otomatis.
     *
     * @return int|null id baseline pulihan, atau null bila tidak ada yang
     *                  perlu dipulihkan (baseline masih ada / lingkup kosong)
     */
    public function pulihkanBaseline(?int $opdId, int $tahunMulai, int $tahunAkhir, ?int $userId = null): ?int
    {
        if (! $this->siap()) {
            return null;
        }

        return $this->dalamTransaksi(function () use ($opdId, $tahunMulai, $tahunAkhir, $userId) {
            $adaNol = $this->lingkup($opdId, $tahunMulai, $tahunAkhir)
                ->where('nomor', 0)
                ->countAllResults();

            $bernomor = $this->lingkup($opdId, $tahunMulai, $tahunAkhir)
                ->where('nomor >', 0)
                ->countAllResults();

            if ($adaNol > 0 || $bernomor === 0) {
                return null; // sehat, atau memang belum pernah berrevisi
            }

            // Revisi efektif paling awal menentukan sampai kapan baseline
            // memayungi. Draft/menunggu tidak dihitung: mereka belum pernah
            // menggeser siapa pun.
            $paling = $this->lingkup($opdId, $tahunMulai, $tahunAkhir)
                ->whereIn('status', [self::STATUS_BERLAKU, self::STATUS_SUPERSEDED])
                ->selectMin('berlaku_mulai_tahun')
                ->get()->getRowArray();

            $batas = $paling !== null && $paling['berlaku_mulai_tahun'] !== null
                ? (int) $paling['berlaku_mulai_tahun']
                : null;

            $baselineId = $this->sisipkanKepala([
                'opd_id'              => $opdId,
                'tahun_mulai'         => $tahunMulai,
                'tahun_akhir'         => $tahunAkhir,
                'nomor'               => 0,
                'nama'                => 'Kondisi Awal IKU ' . $tahunMulai . '-' . $tahunAkhir,
                'berlaku_mulai_tahun' => $tahunMulai,
                // Ada revisi efektif di atasnya -> baseline bukan versi
                // terkini, persis seperti bila digeser sahkan().
                'status'              => $batas !== null ? self::STATUS_SUPERSEDED : self::STATUS_BERLAKU,
                'catatan'             => 'Dipulihkan otomatis: revisi bernomor ditemukan tanpa Kondisi Awal. '
                    . 'Isi dibekukan dari IKU berjalan saat pemulihan.',
                'dibuat_oleh'         => $userId,
                'disahkan_oleh'       => $userId,
                'disahkan_pada'       => date('Y-m-d H:i:s'),
            ]);

            if ($batas !== null) {
                $this->db->table('iku_revisi')->where('id', $baselineId)->update([
                    'berlaku_sampai_tahun' => $batas - 1,
                ]);
            }

            $this->bekukanLiveKeRevisi($baselineId, $opdId, $tahunMulai, $tahunAkhir, false);

            return $baselineId;
        }, 'pemulihan Kondisi Awal IKU');
    }

    /* =========================================================
     * SUNTINGAN DRAFT
     * =======================================================*/

    /**
     * Simpan suntingan isi draft.
     *
     * Hanya menyentuh tabel arsip revisi — tabel live tidak disentuh sama
     * sekali sampai sahkan() dipanggil (invariant 1 & 5).
     *
     * @param array $baris [id arsip indikator => field yang disunting]
     * @param array $baru  indikator tambahan
     */
    /**
     * Apakah kartu indikator baru ini sudah tersentuh pemakai?
     *
     * Dipakai untuk membedakan "kartu kosong yang batal diisi" (boleh dibuang)
     * dari "kartu terisi yang lupa nama indikatornya" (harus ditolak, bukan
     * dibuang diam-diam).
     */
    private function kartuBaruAdaIsinya(array $isi): bool
    {
        foreach (['definisi', 'rumusan_perhitungan', 'satuan', 'sumber_data',
                  'penanggung_jawab', 'baseline', 'catatan_perubahan'] as $k) {
            if (trim((string) ($isi[$k] ?? '')) !== '') {
                return true;
            }
        }

        foreach ((array) ($isi['target'] ?? []) as $t) {
            if (trim((string) $t) !== '') {
                return true;
            }
        }

        return ! empty($isi['perubahan_substansial'])
            || (int) ($isi['indikator_sebelumnya_id'] ?? 0) > 0;
    }

    /**
     * @param bool $izinBerlaku benar bila pemanggil sudah memastikan ada IZIN
     *                          SUNTING yang disetujui untuk revisi berlaku ini.
     *                          Dibuat sebagai argumen tegas, bukan disimpulkan
     *                          di sini: model tidak boleh memutuskan sendiri
     *                          bahwa sebuah arsip resmi boleh dibongkar.
     */
    public function simpanSuntinganDraft(
        int $revisiId,
        array $baris,
        array $baru = [],
        bool $izinBerlaku = false,
        array $sasaranBaru = []
    ): void {
        $revisi = $this->ambil($revisiId);

        if (! $revisi) {
            throw new RuntimeException('Revisi tidak ditemukan.');
        }

        // Pintu terakhir. Controller sudah memeriksa izinnya, tetapi model
        // tetap menolak sendiri bila tidak diberi tahu — supaya pemanggil baru
        // yang lupa memeriksa tidak diam-diam ikut membongkar arsip.
        //
        // =================================================================
        // ARSIP ('superseded') IKUT BISA DIPERBAIKI DI BAWAH IZIN
        //
        // Dulu hanya 'berlaku' yang boleh. Tetapi justru arsip-lah yang
        // dibaca LAKIP tahun-tahun lampau: salah ketik indikator pada versi
        // yang sudah digantikan tetap tercetak di laporan tahun itu, dan
        // tidak ada satu pun jalan membetulkannya.
        //
        // Yang membedakan keduanya BUKAN boleh-tidaknya disunting, melainkan
        // apa yang terjadi sesudahnya: hasil suntingan revisi 'berlaku'
        // diterapkan ke IKU berjalan, sedangkan arsip berhenti di arsip. Itu
        // diatur di controller (revisiSelesaikanIzin), bukan di sini.
        // =================================================================
        if ($izinBerlaku && in_array($revisi['status'], [self::STATUS_BERLAKU, self::STATUS_SUPERSEDED], true)) {
            // lolos: penyuntingan di bawah izin yang sudah disetujui
        } elseif ($revisi['status'] !== self::STATUS_DRAFT) {
            throw new RuntimeException(
                'Revisi berstatus ' . $revisi['status'] . ' hanya bisa disunting di bawah izin '
                . 'yang sudah disetujui Admin Kabupaten.'
            );
        }

        $tahunMulai = (int) $revisi['tahun_mulai'];
        $tahunAkhir = (int) $revisi['tahun_akhir'];

        $this->dalamTransaksi(function () use ($revisiId, $baris, $baru, $sasaranBaru, $tahunMulai, $tahunAkhir) {
            $db  = $this->db;
            $now = date('Y-m-d H:i:s');

            foreach ($baris as $arsipId => $isi) {
                $arsipId = (int) $arsipId;

                if ($arsipId <= 0 || ! is_array($isi)) {
                    continue;
                }

                // Pencegah IDOR: baris arsip milik revisi LAIN tidak boleh ikut
                // tersunting lewat id yang ditebak.
                $adaBaris = $db->table('iku_revisi_indikator')
                    ->where('id', $arsipId)->where('revisi_id', $revisiId)->get()->getRowArray();

                if (! $adaBaris) {
                    continue;
                }

                $jenis     = $this->jenisPerubahanSah($isi['jenis_perubahan'] ?? self::UBAH_TETAP);
                $pengganti = isset($isi['indikator_sebelumnya_id']) && (int) $isi['indikator_sebelumnya_id'] > 0
                    ? (int) $isi['indikator_sebelumnya_id']
                    : null;

                // Lineage hanya berarti pada jenis PENGGANTI. Di form, dropdown
                // "indikator yang digantikan" cuma DISEMBUNYIKAN saat jenis lain
                // dipilih — nilainya tetap ikut terkirim. Tanpa pembersihan ini,
                // baris ber-jenis "revisi" bisa membawa asal-usul yang sudah
                // tidak berlaku, dan itu ikut tersalin ke tabel live saat
                // disahkan — riwayat indikator menunjuk ke leluhur yang salah.
                if ($jenis !== self::UBAH_PENGGANTI) {
                    $pengganti = null;
                }

                // Invariant 4: penggantian TANPA lineage tidak berarti apa-apa —
                // tren antar tahun jadi tidak bisa ditelusuri asal-usulnya.
                if ($jenis === self::UBAH_PENGGANTI && $pengganti === null) {
                    throw new RuntimeException(
                        'Indikator "' . mb_substr((string) ($isi['indikator'] ?? $adaBaris['indikator']), 0, 60)
                        . '" ditandai sebagai PENGGANTI, tetapi indikator yang digantikan belum dipilih.'
                    );
                }

                // `?? $adaBaris` TIDAK menangkap string kosong: kuncinya ada,
                // hanya nilainya kosong. Tanpa penjagaan ini, mengosongkan
                // textarea lalu menyimpan akan menulis indikator tanpa nama ke
                // arsip — dan saat disahkan, ke IKU berjalan dan LAKIP.
                $teksArsip = isset($isi['indikator'])
                    ? trim((string) $isi['indikator'])
                    : (string) $adaBaris['indikator'];

                if ($teksArsip === '') {
                    throw new RuntimeException(
                        'Ada indikator yang teksnya dikosongkan. Isi nama indikatornya, '
                        . 'atau tandai baris itu sebagai DIHENTIKAN bila memang tidak dipakai lagi.'
                    );
                }

                $satuan = isset($isi['satuan']) ? trim((string) $isi['satuan']) : $adaBaris['satuan'];

                $db->table('iku_revisi_indikator')->where('id', $arsipId)->update([
                    'indikator'               => $teksArsip,
                    'definisi'                => $this->kosongJadiNull($isi['definisi'] ?? $adaBaris['definisi']),
                    'rumusan_perhitungan'     => $this->kosongJadiNull($isi['rumusan_perhitungan'] ?? $adaBaris['rumusan_perhitungan']),
                    'satuan'                  => $this->kosongJadiNull($satuan),
                    'satuan_nama'             => $this->namaSatuan($satuan),
                    'sumber_data'             => $this->kosongJadiNull($isi['sumber_data'] ?? $adaBaris['sumber_data']),
                    'penanggung_jawab'        => $this->kosongJadiNull($isi['penanggung_jawab'] ?? $adaBaris['penanggung_jawab']),
                    'jenis_indikator'         => $this->kosongJadiNull($isi['jenis_indikator'] ?? $adaBaris['jenis_indikator']),
                    'baseline'                => $this->kosongJadiNull($isi['baseline'] ?? $adaBaris['baseline']),
                    'jenis_perubahan'         => $jenis,
                    'indikator_sebelumnya_id' => $pengganti,
                    'perubahan_substansial'   => ! empty($isi['perubahan_substansial']) ? 1 : 0,
                    'catatan_perubahan'       => $this->kosongJadiNull($isi['catatan_perubahan'] ?? null),
                    'updated_at'              => $now,
                ]);

                $this->simpanTargetArsip($arsipId, (array) ($isi['target'] ?? []), $tahunMulai, $tahunAkhir, $now);
            }

            foreach ((array) $baru as $isi) {
                if (! is_array($isi)) {
                    continue;
                }

                $sasaranArsipId = (int) ($isi['revisi_sasaran_id'] ?? 0);

                // Pencegah IDOR: sasaran tujuan harus milik revisi ini.
                $sasaranSah = $sasaranArsipId > 0 && $db->table('iku_revisi_sasaran')
                    ->where('id', $sasaranArsipId)->where('revisi_id', $revisiId)->countAllResults() > 0;

                $this->sisipkanIndikatorBaru(
                    $revisiId, $sasaranArsipId, $sasaranSah, $isi, $tahunMulai, $tahunAkhir, $now
                );
            }

            // =========================================================
            // SASARAN BARU (SASARAN MANDIRI) — DITAMBAHKAN DI SINI, BUKAN
            // LEWAT PINTU TERSENDIRI YANG MENULIS LANGSUNG KE TABEL LIVE
            //
            // Sebelumnya sasaran mandiri lahir lewat IkuController::save()
            // yang memanggil IkuModel::createComplete() — menulis LANGSUNG ke
            // `iku_sasaran`, di luar alur revisi. Akibatnya IKU berjalan
            // berubah tanpa revisi dan tanpa pengesahan, sementara arsip
            // revisi mana pun tidak pernah memuatnya: sasaran itu tampil di
            // IKU berjalan tetapi TIDAK ADA di dokumen penilaian tahun mana
            // pun, sehingga LAKIP tidak bisa menilainya.
            //
            // Di sini ia menjadi bagian dari draft, ikut diperiksa Admin
            // Kabupaten, dan baru menyentuh tabel live saat revisinya disahkan
            // lewat terapkanKeLive() — yang memang sudah tahu cara
            // MELAHIRKAN sasaran live dari baris arsip ber-sumber_sasaran_id
            // NULL, jadi tidak ada yang perlu diubah di sisi itu.
            // =========================================================
            $this->sisipkanSasaranBaru($revisiId, (array) $sasaranBaru, $tahunMulai, $tahunAkhir, $now);

            return true;
        }, 'penyimpanan suntingan draft revisi');
    }

    /**
     * Satu kartu indikator baru -> satu baris arsip.
     *
     * Dipisah dari simpanSuntinganDraft() supaya indikator yang menempel pada
     * sasaran LAMA dan yang menempel pada sasaran BARU melewati aturan yang
     * sama persis — dua salinan logika ini pernah menjadi cara paling mudah
     * bagi keduanya untuk menyimpang tanpa ketahuan.
     *
     * @param bool $sasaranSah hasil pemeriksaan kepemilikan yang WAJIB
     *                         dilakukan pemanggil; diteruskan, bukan diulang,
     *                         karena sasaran baru belum tentu ada di basis
     *                         data saat pemeriksaan pertama dilakukan.
     */
    private function sisipkanIndikatorBaru(
        int $revisiId,
        int $sasaranArsipId,
        bool $sasaranSah,
        array $isi,
        int $tahunMulai,
        int $tahunAkhir,
        string $now
    ): void {
        $db   = $this->db;
        $teks = trim((string) ($isi['indikator'] ?? ''));

        if ($teks === '') {
            // Kartu yang benar-benar kosong memang boleh diabaikan —
            // pemakai menekan "Tambah Indikator" lalu berubah pikiran.
            // Tetapi kartu yang SEBAGIAN terisi tidak boleh hilang
            // diam-diam sementara pemakai menerima pesan "tersimpan":
            // ia baru tahu isiannya lenyap saat membuka draft lagi.
            if ($this->kartuBaruAdaIsinya($isi)) {
                throw new RuntimeException(
                    'Ada indikator baru yang sudah diisi sebagian tetapi nama indikatornya '
                    . 'masih kosong. Isi kolom "Indikator", atau kosongkan seluruh kartunya.'
                );
            }

            return;
        }

        if (! $sasaranSah) {
            throw new RuntimeException(
                'Indikator baru "' . mb_substr($teks, 0, 60) . '" tidak terhubung ke sasaran '
                . 'mana pun dalam revisi ini, jadi tidak bisa disimpan.'
            );
        }

        $jenis     = $this->jenisPerubahanSah($isi['jenis_perubahan'] ?? self::UBAH_BARU);
        $pengganti = isset($isi['indikator_sebelumnya_id']) && (int) $isi['indikator_sebelumnya_id'] > 0
            ? (int) $isi['indikator_sebelumnya_id']
            : null;

        if ($jenis !== self::UBAH_PENGGANTI) {
            $pengganti = null;
        }

        if ($jenis === self::UBAH_PENGGANTI && $pengganti === null) {
            throw new RuntimeException(
                'Indikator baru "' . mb_substr($teks, 0, 60) . '" ditandai sebagai PENGGANTI, '
                . 'tetapi indikator yang digantikan belum dipilih.'
            );
        }

        $satuan = trim((string) ($isi['satuan'] ?? ''));

        $db->table('iku_revisi_indikator')->insert([
            'revisi_id'               => $revisiId,
            'revisi_sasaran_id'       => $sasaranArsipId,
            'sumber_indikator_id'     => null,
            'indikator'               => $teks,
            'definisi'                => $this->kosongJadiNull($isi['definisi'] ?? null),
            'rumusan_perhitungan'     => $this->kosongJadiNull($isi['rumusan_perhitungan'] ?? null),
            'satuan'                  => $this->kosongJadiNull($satuan),
            'satuan_nama'             => $this->namaSatuan($satuan),
            'sumber_data'             => $this->kosongJadiNull($isi['sumber_data'] ?? null),
            'penanggung_jawab'        => $this->kosongJadiNull($isi['penanggung_jawab'] ?? null),
            'jenis_indikator'         => $this->kosongJadiNull($isi['jenis_indikator'] ?? null),
            'baseline'                => $this->kosongJadiNull($isi['baseline'] ?? null),
            'urutan'                  => (int) ($isi['urutan'] ?? 999),
            'status'                  => 'draft',
            'jenis_perubahan'         => $jenis,
            'indikator_sebelumnya_id' => $pengganti,
            'perubahan_substansial'   => ! empty($isi['perubahan_substansial']) ? 1 : 0,
            'catatan_perubahan'       => $this->kosongJadiNull($isi['catatan_perubahan'] ?? null),
            'created_at'              => $now,
            'updated_at'              => $now,
        ]);

        $this->simpanTargetArsip(
            (int) $db->insertID(),
            (array) ($isi['target'] ?? []),
            $tahunMulai,
            $tahunAkhir,
            $now
        );
    }

    /**
     * Sasaran baru (SASARAN MANDIRI) -> baris arsip, beserta indikatornya.
     *
     * ---------------------------------------------------------------------
     * BENTUK MASUKANNYA
     *
     *   sasaran_baru[K][sasaran]           teks sasaran (wajib)
     *   sasaran_baru[K][renstra_tujuan_id] tujuan Renstra penaungnya (wajib)
     *   sasaran_baru[K][indikator][N][...] kartu indikator, bentuknya SAMA
     *                                      persis dengan `baru[...]`
     *
     * Indikatornya SENGAJA disarangkan di dalam sasarannya, bukan dikirim
     * lewat `baru[]` dengan kunci sementara. Sasaran baru belum punya id saat
     * form dikirim; menyarangkannya membuat hubungan "indikator ini milik
     * sasaran itu" terbaca langsung dari bentuk datanya, tanpa lapisan
     * penerjemah kunci-sementara yang bisa salah memetakan tanpa bergejala.
     *
     * ---------------------------------------------------------------------
     * MENGAPA TUJUAN RENSTRA WAJIB
     *
     * Alasan yang sama dengan pintu lamanya (IkuController::tambah()): tanpa
     * tujuan, sasaran ini muncul di Cascading dengan kolom-kolom kosong, di
     * luar blok Tujuan mana pun. Kewajibannya ditegakkan DI SINI, bukan hanya
     * di form, karena form bisa dilewati.
     *
     * `sumber_sasaran_id` dibiarkan NULL — itulah penanda "belum punya baris
     * live". terapkanKeLive() membaca penanda itu untuk MELAHIRKAN sasaran
     * live saat revisinya disahkan, lalu mengisi balik id-nya ke sini.
     */
    private function sisipkanSasaranBaru(
        int $revisiId,
        array $sasaranBaru,
        int $tahunMulai,
        int $tahunAkhir,
        string $now
    ): void {
        if ($sasaranBaru === []) {
            return;
        }

        $db = $this->db;

        // Teks yang SUDAH ada di draft ini, untuk menolak kembar. Dibaca sekali
        // di depan, lalu ditambahi tiap sasaran baru yang lolos — dua kartu
        // baru berteks sama dalam satu penyimpanan pun ikut tertangkap.
        $sudahAda = [];

        foreach ($db->table('iku_revisi_sasaran')->select('sasaran')
            ->where('revisi_id', $revisiId)->get()->getResultArray() as $r) {
            $sudahAda[$this->kunciTeks($r['sasaran'])] = true;
        }

        // Sasaran baru ditaruh di EKOR dokumen. Urutan 0 akan membuatnya
        // memimpin di depan sasaran hasil Renstra di layar Cascading.
        $urutan = (int) ($db->table('iku_revisi_sasaran')
            ->selectMax('urutan')->where('revisi_id', $revisiId)
            ->get()->getRowArray()['urutan'] ?? 0);

        foreach ($sasaranBaru as $isi) {
            if (! is_array($isi)) {
                continue;
            }

            $teks      = trim((string) ($isi['sasaran'] ?? ''));
            $indikator = (array) ($isi['indikator'] ?? []);

            if ($teks === '') {
                // Kartu kosong = pemakai menekan "Tambah Sasaran" lalu berubah
                // pikiran. Tetapi kartu yang indikatornya sudah diisi tidak
                // boleh hilang diam-diam di balik pesan "tersimpan".
                foreach ($indikator as $kartu) {
                    if (is_array($kartu)
                        && (trim((string) ($kartu['indikator'] ?? '')) !== ''
                            || $this->kartuBaruAdaIsinya($kartu))) {
                        throw new RuntimeException(
                            'Ada sasaran baru yang indikatornya sudah diisi tetapi nama sasarannya '
                            . 'masih kosong. Isi kolom "Sasaran", atau kosongkan seluruh kartunya.'
                        );
                    }
                }

                continue;
            }

            $kunci = $this->kunciTeks($teks);

            if (isset($sudahAda[$kunci])) {
                throw new RuntimeException(
                    'Sasaran "' . mb_substr($teks, 0, 60) . '" sudah ada pada revisi ini. '
                    . 'Tambahkan indikatornya pada sasaran yang sudah ada, bukan membuat sasaran kembar.'
                );
            }

            $tujuanId = (int) ($isi['renstra_tujuan_id'] ?? 0);

            if ($tujuanId <= 0 && $this->db->fieldExists('renstra_tujuan_id', 'iku_revisi_sasaran')) {
                throw new RuntimeException(
                    'Sasaran baru "' . mb_substr($teks, 0, 60) . '" belum memilih Tujuan Renstra. '
                    . 'Tanpa itu sasaran ini tidak punya tempat di Cascading.'
                );
            }

            $db->table('iku_revisi_sasaran')->insert($this->kolomTujuanArsip($isi) + [
                'revisi_id'         => $revisiId,
                // NULL = belum punya baris live; terapkanKeLive() yang
                // melahirkannya saat revisi disahkan.
                'sumber_sasaran_id' => null,
                'sasaran'           => $teks,
                'tahun_mulai'       => $tahunMulai,
                'tahun_akhir'       => $tahunAkhir,
                'urutan'            => ++$urutan,
                'jenis_perubahan'   => self::UBAH_BARU,
                'catatan_perubahan' => $this->kosongJadiNull($isi['catatan_perubahan'] ?? null),
                // Penanda "lahir di IKU". Dipakai Sync mode Ganti untuk TIDAK
                // menghapusnya, sama seperti sasaran mandiri lewat pintu lama.
                'source_type'       => 'iku',
                'source_version_id' => null,
                'source_ref_id'     => null,
                'created_at'        => $now,
                'updated_at'        => $now,
            ]);

            $sasaranArsipId    = (int) $db->insertID();
            $sudahAda[$kunci]  = true;
            $adaIndikator      = false;

            foreach ($indikator as $kartu) {
                if (! is_array($kartu)) {
                    continue;
                }

                if (trim((string) ($kartu['indikator'] ?? '')) !== '') {
                    $adaIndikator = true;
                }

                // Sasarannya baru saja dibuat di transaksi ini, jadi
                // kepemilikannya pasti — tidak ada id tebakan yang bisa masuk.
                $this->sisipkanIndikatorBaru(
                    $revisiId, $sasaranArsipId, true, $kartu, $tahunMulai, $tahunAkhir, $now
                );
            }

            // Sasaran tanpa indikator tidak berarti apa-apa: ia tidak bisa
            // dinilai LAKIP, dan saat disahkan ia melahirkan baris live kosong
            // yang hanya menambah baris di Cascading tanpa isi.
            if (! $adaIndikator) {
                throw new RuntimeException(
                    'Sasaran baru "' . mb_substr($teks, 0, 60) . '" belum punya satu indikator pun. '
                    . 'Tambahkan minimal satu indikator, atau kosongkan kartunya.'
                );
            }
        }
    }

    /** Tulis target arsip satu indikator; tahun di luar periode dibuang. */
    private function simpanTargetArsip(int $arsipIndikatorId, array $target, int $tahunMulai, int $tahunAkhir, string $now): void
    {
        foreach ($target as $tahun => $nilai) {
            $tahun = (int) $tahun;

            if ($tahun < $tahunMulai || $tahun > $tahunAkhir) {
                continue;
            }

            $nilai = is_scalar($nilai) ? trim((string) $nilai) : null;

            $ada = $this->db->table('iku_revisi_target')
                ->where('revisi_indikator_id', $arsipIndikatorId)
                ->where('tahun', $tahun)
                ->get()->getRowArray();

            if ($ada) {
                $this->db->table('iku_revisi_target')->where('id', (int) $ada['id'])->update([
                    'target'     => $nilai,
                    'updated_at' => $now,
                ]);

                continue;
            }

            $this->db->table('iku_revisi_target')->insert([
                'revisi_indikator_id' => $arsipIndikatorId,
                'tahun'               => $tahun,
                'target'              => $nilai,
                'created_at'          => $now,
                'updated_at'          => $now,
            ]);
        }
    }

    private function jenisPerubahanSah($nilai): string
    {
        $nilai = strtolower(trim((string) $nilai));

        $sah = [
            self::UBAH_TETAP, self::UBAH_REVISI, self::UBAH_PENGGANTI,
            self::UBAH_BARU, self::UBAH_DIHENTIKAN,
        ];

        return in_array($nilai, $sah, true) ? $nilai : self::UBAH_TETAP;
    }

    /**
     * Terjemahkan `satuan` (id numerik atau teks bebas) jadi namanya, untuk
     * dibekukan di arsip. Pola yang sama dengan SATUAN_SELECT pada getMatrix().
     */
    private function namaSatuan(?string $satuan): ?string
    {
        $satuan = trim((string) $satuan);

        if ($satuan === '') {
            return null;
        }

        if (! preg_match('/^[0-9]+$/', $satuan)) {
            return $satuan;
        }

        $row = $this->db->table('satuan')->select('satuan')->where('id', (int) $satuan)->get()->getRowArray();

        return $row['satuan'] ?? $satuan;
    }

    /* =========================================================
     * PENGESAHAN
     * =======================================================*/

    /**
     * Sahkan draft: terapkan isinya ke tabel live, geser revisi sebelumnya.
     *
     * Satu transaksi penuh (invariant 7). Urutannya penting:
     *   1. kunci & periksa status (hanya draft yang boleh disahkan);
     *   2. geser revisi lama jadi 'superseded' DULU — kalau tidak, UNIQUE
     *      index bisa menolak pengesahan ini;
     *   3. terapkan isi arsip ke tabel live, indikator yang hilang DIPENSIUNKAN
     *      bukan dihapus (invariant 8);
     *   4. tandai draft jadi 'berlaku'.
     *
     * @return array{revisi_id:int, digeser:int[], indikator_dipensiunkan:int}
     */
    public function sahkan(int $revisiId, ?int $userId = null): array
    {
        if (! $this->siap()) {
            throw new RuntimeException('Tabel revisi IKU belum tersedia.');
        }

        return $this->dalamTransaksi(function () use ($revisiId, $userId) {
            $db = $this->db;

            $revisi = $db->table('iku_revisi')->where('id', $revisiId)->get()->getRowArray();

            if (! $revisi) {
                throw new RuntimeException('Revisi tidak ditemukan.');
            }
            // Dua pintu masuk yang sah, dan keduanya disengaja:
            //   draft    -> IKU Kabupaten, yang memang disahkan penyusunnya
            //   menunggu -> IKU OPD, sesudah diperiksa Admin Kabupaten
            if (! in_array($revisi['status'], [self::STATUS_DRAFT, self::STATUS_MENUNGGU], true)) {
                throw new RuntimeException(
                    'Hanya revisi berstatus draft atau menunggu yang bisa disahkan. '
                    . 'Status sekarang: ' . $revisi['status'] . '.'
                );
            }

            $opdKey  = (int) $revisi['opd_key'];
            $opdId   = $revisi['opd_id'] !== null ? (int) $revisi['opd_id'] : null;
            $berlaku = (int) $revisi['berlaku_mulai_tahun'];

            // --- 2. geser revisi lain dalam lingkup yang sama ---------------
            $lain = $db->table('iku_revisi')
                ->where('opd_key', $opdKey)
                ->where('tahun_mulai', (int) $revisi['tahun_mulai'])
                ->where('tahun_akhir', (int) $revisi['tahun_akhir'])
                ->where('status', self::STATUS_BERLAKU)
                ->where('id !=', $revisiId)
                ->orderBy('berlaku_mulai_tahun', 'ASC')
                ->get()
                ->getResultArray();

            $digeser  = [];
            $batasAtas = null;

            foreach ($lain as $r) {
                $mulaiLain = (int) $r['berlaku_mulai_tahun'];

                if ($mulaiLain === $berlaku) {
                    // Tepat bertabrakan. Basis data akan menolaknya juga, tapi
                    // pesan di sini jauh lebih berguna bagi operator.
                    throw new RuntimeException(
                        'Sudah ada revisi yang berlaku mulai tahun ' . $berlaku . ' pada lingkup ini ("'
                        . $r['nama'] . '"). Ubah tahun mulai berlaku, atau batalkan revisi tersebut lebih dulu.'
                    );
                }

                if ($mulaiLain < $berlaku) {
                    // Revisi lama: ditutup satu tahun sebelum revisi ini mulai.
                    if ($r['berlaku_sampai_tahun'] === null || (int) $r['berlaku_sampai_tahun'] >= $berlaku) {
                        $db->table('iku_revisi')->where('id', (int) $r['id'])->update([
                            'status'               => self::STATUS_SUPERSEDED,
                            'berlaku_sampai_tahun' => $berlaku - 1,
                            'updated_at'           => date('Y-m-d H:i:s'),
                        ]);
                        $digeser[] = (int) $r['id'];
                    }
                    continue;
                }

                // Ada revisi yang berlaku LEBIH DULU dari yang sedang disahkan
                // (pengesahan menyusul / di luar urutan). Revisi inilah yang
                // harus dibatasi, bukan yang sudah berlaku itu.
                $batasAtas = $batasAtas === null ? ($mulaiLain - 1) : min($batasAtas, $mulaiLain - 1);
            }

            // --- 3. terapkan arsip ke tabel live ---------------------------
            $dipensiunkan = $this->terapkanKeLive(
                $revisiId,
                $opdId,
                $berlaku,
                (int) $revisi['tahun_mulai'],
                (int) $revisi['tahun_akhir']
            );

            // --- 4. tandai berlaku -----------------------------------------
            $db->table('iku_revisi')->where('id', $revisiId)->update([
                'status'               => self::STATUS_BERLAKU,
                'berlaku_sampai_tahun' => $batasAtas,
                'disahkan_oleh'        => $userId,
                'disahkan_pada'        => date('Y-m-d H:i:s'),
                'dibekukan_pada'       => $revisi['dibekukan_pada'] ?? date('Y-m-d H:i:s'),
                'updated_at'           => date('Y-m-d H:i:s'),
            ]);

            // =============================================================
            // --- 5. JAHIT ULANG GARIS WAKTU: PENUTUP YANG WAJIB ---
            //
            // Langkah 2 di atas hanya melihat baris ber-status 'berlaku', dan
            // hanya menurunkan yang mulai berlakunya LEBIH AWAL. Itu benar
            // selama revisi selalu disahkan berurutan — asumsi yang RUNTUH
            // sejak tahun berlaku boleh dipilih bebas.
            //
            // Bila revisi yang disahkan mulai LEBIH AWAL daripada revisi yang
            // sudah berlaku, cabang `$batasAtas` cuma membatasi yang baru dan
            // TIDAK menurunkan siapa pun — hasilnya DUA baris berstatus
            // 'berlaku' sekaligus. Ditambah `berlaku_sampai_tahun` warisan
            // yang tidak ikut dihitung ulang, jendelanya bisa tumpang tindih,
            // dan resolveEfektif() menolak melayani tahun yang bertabrakan itu
            // ("Konflik masa berlaku revisi" di layar).
            //
            // Terreproduksi: Kondisi Awal di 2027, lalu sahkan revisi 2025 ->
            // dua-duanya 'berlaku'.
            //
            // jahitUlangTimeline() adalah SATU-SATUNYA otoritas atas jendela
            // dan status. Dijalankan di sini, urutan pengesahan tidak lagi
            // menentukan kebenaran hasilnya.
            // =============================================================
            $digeser = array_values(array_unique(array_merge(
                $digeser,
                $this->jahitUlangTimeline($opdId, (int) $revisi['tahun_mulai'], (int) $revisi['tahun_akhir'], $revisiId)
            )));

            // Bila sesudah dijahit ternyata BUKAN revisi ini yang terkini,
            // tabel live harus mengikuti yang terkini — bukan isi revisi yang
            // baru saja disahkan. Mengesahkan dokumen untuk tahun lampau tidak
            // boleh memundurkan IKU berjalan.
            $terkini = $this->lingkup($opdId, (int) $revisi['tahun_mulai'], (int) $revisi['tahun_akhir'])
                ->where('status', self::STATUS_BERLAKU)
                ->get()->getRowArray();

            if ($terkini !== null && (int) $terkini['id'] !== $revisiId) {
                $this->terapkanKeLive(
                    (int) $terkini['id'],
                    $opdId,
                    (int) $terkini['berlaku_mulai_tahun'],
                    (int) $revisi['tahun_mulai'],
                    (int) $revisi['tahun_akhir']
                );
            }

            return [
                'revisi_id'              => $revisiId,
                'digeser'                => $digeser,
                'indikator_dipensiunkan' => $dipensiunkan,
            ];
        }, 'pengesahan revisi IKU');
    }

    /**
     * Ubah tahun mulai berlaku sebuah revisi.
     *
     * =====================================================================
     * SATU-SATUNYA LARANGAN ADALAH TAHUN KEMBAR
     *
     * Tahun berlaku boleh dipilih BEBAS di dalam periode, untuk revisi mana
     * pun — draft maupun yang sedang berlaku, termasuk Kondisi Awal. Yang
     * ditolak hanya satu: tahun yang sudah dipayungi revisi lain, sebab
     * "revisi mana yang dipakai tahun ini" harus punya tepat satu jawaban.
     *
     * Tiga larangan lama dicabut, karena semuanya menjaga URUTAN, bukan
     * kebenaran:
     *
     *   * Kondisi Awal tidak boleh digeser — dicabut. Ia memang jangkar awal
     *     periode, tetapi menggesernya bukan kerusakan diam-diam: tahun yang
     *     lalu tak berpayung membuat LAKIP jatuh ke Renstra DENGAN
     *     PEMBERITAHUAN di layar (LakipSumberTrait), dan langkahnya bisa
     *     dibatalkan dengan menggesernya kembali. Peringatannya dikembalikan
     *     lewat kunci `peringatan` agar pemanggil menyampaikannya.
     *   * Tahun pertama periode terlarang bagi revisi bernomor — dicabut.
     *     Bila Kondisi Awal sudah pindah, tahun itu memang bebas.
     *   * Tidak boleh melompati tetangga — dicabut. Urutan nomor revisi
     *     adalah urutan PENYUSUNAN, bukan urutan berlaku; memaksa keduanya
     *     sama hanya membuat penyusun harus menggeser tiga revisi demi
     *     membetulkan satu.
     *
     * Yang menggantikan ketiganya: seluruh garis waktu DIJAHIT ULANG dari nol
     * sesudah perubahan (lihat jahitUlangTimeline()), sehingga tiap tahun
     * tetap dipayungi tepat satu revisi berapa pun urutan nomornya.
     *
     * Realisasi LAKIP TIDAK tersentuh: capaian menempel pada indikator
     * berjalan (sumber_indikator_id), bukan pada revisinya, jadi tahun yang
     * berpindah payung tetap membaca realisasi yang sama.
     *
     * @return array{dari:int, ke:int, digeser:array<int,int>, peringatan:?string}
     */
    public function ubahTahunBerlaku(int $revisiId, int $tahunBaru): array
    {
        if (! $this->siap()) {
            throw new RuntimeException('Tabel revisi IKU belum tersedia.');
        }

        return $this->dalamTransaksi(function () use ($revisiId, $tahunBaru) {
            $db     = $this->db;
            $revisi = $db->table('iku_revisi')->where('id', $revisiId)->get()->getRowArray();

            if (! $revisi) {
                throw new RuntimeException('Revisi tidak ditemukan.');
            }

            $status = (string) $revisi['status'];

            // =============================================================
            // 'superseded' IKUT BOLEH DIUBAH
            //
            // 'superseded' berarti "bukan versi terkini", BUKAN "tidak pernah
            // berlaku": ia tetap dokumen resmi bagi tahun-tahun yang
            // dipayunginya, dan resolveEfektif() memang membacanya. Begitu ada
            // dua revisi, semua kecuali yang terbaru berstatus ini — mengunci
            // mereka berarti masa berlaku yang telanjur salah tidak pernah bisa
            // dibetulkan, justru pada versi yang paling sering perlu dirapikan.
            //
            // Yang tetap ditolak:
            //   'menunggu' — sedang di meja verifikator; menggesernya di
            //                belakang punggung mereka mengubah apa yang sedang
            //                diperiksa. Tarik pengajuannya dulu.
            //   'batal'    — tidak punya tempat di garis waktu sama sekali.
            // =============================================================
            if (! in_array($status, [self::STATUS_DRAFT, self::STATUS_BERLAKU, self::STATUS_SUPERSEDED], true)) {
                throw new RuntimeException(
                    $status === self::STATUS_MENUNGGU
                        ? 'Revisi ini sedang menunggu keputusan Admin Kabupaten. Tarik pengajuannya '
                          . 'dulu bila tahun berlakunya hendak diubah.'
                        : 'Revisi berstatus ' . $status . ' tidak punya masa berlaku yang bisa diubah.'
                );
            }

            $tahunMulai = (int) $revisi['tahun_mulai'];
            $tahunAkhir = (int) $revisi['tahun_akhir'];
            $dari       = (int) $revisi['berlaku_mulai_tahun'];
            $opdId      = $revisi['opd_id'] !== null ? (int) $revisi['opd_id'] : null;

            $this->validasiLingkupDraft($tahunMulai, $tahunAkhir, $tahunBaru);

            if ($tahunBaru === $dari) {
                return ['dari' => $dari, 'ke' => $tahunBaru, 'digeser' => [], 'peringatan' => null];
            }

            // SATU-SATUNYA larangan: tahun tujuan sudah dipayungi revisi lain.
            // Pengajuan yang masih menggantung ikut dihitung, supaya bentroknya
            // ketahuan sekarang — bukan saat pengesahan, di meja orang lain.
            $bentrok = $this->lingkup($opdId, $tahunMulai, $tahunAkhir)
                ->whereIn('status', [self::STATUS_BERLAKU, self::STATUS_SUPERSEDED, self::STATUS_MENUNGGU])
                ->where('berlaku_mulai_tahun', $tahunBaru)
                ->where('id !=', $revisiId)
                ->get()->getRowArray();

            if ($bentrok !== null) {
                $bebas = $this->tahunBerlakuBebas($opdId, $tahunMulai, $tahunAkhir, $revisiId);

                throw new RuntimeException(
                    'Tahun ' . $tahunBaru . ' sudah dipakai "' . $bentrok['nama'] . '" '
                    . '(revisi ke-' . $bentrok['nomor'] . ', status ' . $bentrok['status'] . '). '
                    . 'Satu tahun hanya boleh dipayungi satu revisi. '
                    . ($bebas === []
                        ? 'Seluruh tahun lain pada periode ini juga terpakai — geser dulu salah satunya.'
                        : 'Tahun yang masih kosong: ' . implode(', ', $bebas) . '.')
                );
            }

            $db->table('iku_revisi')->where('id', $revisiId)->update([
                'berlaku_mulai_tahun' => $tahunBaru,
                'updated_at'          => date('Y-m-d H:i:s'),
            ]);

            // Draft belum punya tempat di garis waktu (berlaku_sampai_tahun-nya
            // baru diisi saat disahkan), jadi tidak ada yang perlu dijahit.
            $digeser = $status === self::STATUS_DRAFT
                ? []
                : $this->jahitUlangTimeline($opdId, $tahunMulai, $tahunAkhir, $revisiId);

            return [
                'dari'       => $dari,
                'ke'         => $tahunBaru,
                'digeser'    => $digeser,
                'peringatan' => $this->peringatanAwalPeriode($opdId, $tahunMulai, $tahunAkhir),
            ];
        }, 'ubah tahun berlaku revisi IKU');
    }

    /**
     * Siapa saja yang masih merujuk sebuah revisi — penghalang penghapusan.
     *
     * =====================================================================
     * MENGAPA HARUS DIHITUNG SENDIRI
     *
     * `iku_revisi.id` dirujuk beberapa tabel TANPA foreign key, jadi basis
     * data TIDAK akan menahan penghapusan. Tanpa daftar ini, menghapus sebuah
     * revisi meninggalkan angka yang menunjuk ke ketiadaan — dan tak satu pun
     * layar melaporkannya sebagai galat; mereka hanya berhenti menemukan
     * sumbernya.
     *
     * Yang PUNYA foreign key ON DELETE CASCADE (arsip isi revisi) sengaja
     * tidak didaftar: memang seharusnya ikut terhapus.
     *
     * `lakip_snapshot_baris.iku_revisi_indikator_id` menunjuk ke arsip
     * indikator yang CASCADE — jadi ia ikut dihitung lewat revisinya, bukan
     * lewat id revisi langsung.
     *
     * @return array<string,int> [keterangan => jumlah baris] yang tidak kosong
     */
    public function penghalangHapus(int $revisiId): array
    {
        $db  = $this->db;
        $ada = [];

        $hitung = static function (string $tabel, callable $kondisi) use ($db): int {
            if (! $db->tableExists($tabel)) {
                return 0;
            }

            return $kondisi($db->table($tabel));
        };

        $n = $hitung('lakip', static fn ($b) => $b
            ->where('source_type', 'iku')->where('source_version_id', $revisiId)->countAllResults());

        if ($n > 0) {
            $ada['baris realisasi LAKIP yang dinilai terhadap versi ini'] = $n;
        }

        if ($db->tableExists('lakip_snapshot')
            && $db->fieldExists('sumber_iku_revisi_id', 'lakip_snapshot')) {
            $n = $db->table('lakip_snapshot')->where('sumber_iku_revisi_id', $revisiId)->countAllResults();

            if ($n > 0) {
                $ada['snapshot LAKIP yang dibekukan dari versi ini'] = $n;
            }
        }

        if ($db->tableExists('lakip_snapshot_baris')
            && $db->fieldExists('iku_revisi_indikator_id', 'lakip_snapshot_baris')) {
            $n = (int) ($db->query(
                'SELECT COUNT(*) AS n FROM lakip_snapshot_baris b
                   JOIN iku_revisi_indikator ri ON ri.id = b.iku_revisi_indikator_id
                  WHERE ri.revisi_id = ?',
                [$revisiId]
            )->getRowArray()['n'] ?? 0);

            if ($n > 0) {
                $ada['baris snapshot LAKIP yang menunjuk indikator arsip versi ini'] = $n;
            }
        }

        if ($db->tableExists('lakip_penyesuaian')) {
            foreach (['iku_revisi_id', 'usul_revisi_iku'] as $kolom) {
                if (! $db->fieldExists($kolom, 'lakip_penyesuaian')) {
                    continue;
                }

                $n = $db->table('lakip_penyesuaian')->where($kolom, $revisiId)->countAllResults();

                if ($n > 0) {
                    $ada['penyesuaian kebijakan LAKIP (' . $kolom . ')'] = $n;
                }
            }
        }

        return $ada;
    }

    /**
     * HAPUS sebuah versi IKU beserta seluruh arsip isinya.
     *
     * =====================================================================
     * TIDAK BISA DIBATALKAN — DAN KARENA ITU BERPENJAGA BANYAK
     *
     * Arsip isi (`iku_revisi_sasaran/_indikator/_target/_program`) ikut
     * terhapus lewat foreign key ON DELETE CASCADE. Baris LIVE tidak ikut
     * terhapus: `iku_sasaran.revisi_id` dan `iku_indikator.revisi_id`
     * ber-ON DELETE SET NULL, jadi isinya tetap ada, hanya kehilangan
     * keterangan "versi mana yang terakhir menuliskannya".
     *
     * Yang ditolak:
     *   * status 'menunggu' — sedang di meja verifikator;
     *   * masih ada yang merujuknya (lihat penghalangHapus()).
     *
     * =====================================================================
     * MENGHAPUS VERSI YANG SEDANG BERLAKU
     *
     * Diizinkan, tetapi TIDAK cukup sekadar menghapus barisnya: tabel live
     * masih berisi tulisan versi itu, sementara sesudah penghapusan LAKIP
     * akan membaca arsip versi SEBELUMNYA. Layar dan arsip jadi berbeda isi,
     * dan tidak ada yang melaporkannya.
     *
     * Karena itu penerus terdekat diangkat menjadi 'berlaku' dan diterapkan
     * ulang ke tabel live, sehingga IKU berjalan kembali sama dengan dokumen
     * yang menaunginya. Bila tidak ada penerus sama sekali, tabel live
     * dibiarkan apa adanya — itu keadaan "belum pernah ada versi", persis
     * seperti sebelum modul revisi dipasang.
     *
     * @return array{nomor:int, nama:string, status_lama:string,
     *               penerus:?int, digeser:int[], peringatan:?string}
     */
    public function hapusRevisi(int $revisiId): array
    {
        if (! $this->siap()) {
            throw new RuntimeException('Tabel revisi IKU belum tersedia.');
        }

        return $this->dalamTransaksi(function () use ($revisiId) {
            $db     = $this->db;
            $revisi = $db->table('iku_revisi')->where('id', $revisiId)->get()->getRowArray();

            if (! $revisi) {
                throw new RuntimeException('Revisi tidak ditemukan.');
            }

            $status = (string) $revisi['status'];

            if ($status === self::STATUS_MENUNGGU) {
                throw new RuntimeException(
                    'Revisi ini sedang menunggu keputusan Admin Kabupaten. Tarik pengajuannya '
                    . 'dulu sebelum meminta penghapusan.'
                );
            }

            $penghalang = $this->penghalangHapus($revisiId);

            if ($penghalang !== []) {
                $rinci = [];

                foreach ($penghalang as $apa => $n) {
                    $rinci[] = $n . ' ' . $apa;
                }

                throw new RuntimeException(
                    'Versi ini masih dirujuk data lain, jadi tidak bisa dihapus: '
                    . implode('; ', $rinci) . '. Menghapusnya akan membuat rujukan itu '
                    . 'menunjuk ke dokumen yang tidak ada lagi.'
                );
            }

            $opdId      = $revisi['opd_id'] !== null ? (int) $revisi['opd_id'] : null;
            $tahunMulai = (int) $revisi['tahun_mulai'];
            $tahunAkhir = (int) $revisi['tahun_akhir'];

            $this->tutupIzinRevisi($revisiId);
            $db->table('iku_revisi')->where('id', $revisiId)->delete();

            // Garis waktu DULU: ia yang menentukan siapa kini versi terkini,
            // sekaligus membetulkan statusnya. Penerus tidak lagi ditebak di
            // sini — menebaknya terpisah berarti dua aturan "siapa yang
            // berlaku" yang bisa menyimpang.
            $digeser = $this->jahitUlangTimeline($opdId, $tahunMulai, $tahunAkhir);

            $penerus = $this->lingkup($opdId, $tahunMulai, $tahunAkhir)
                ->where('status', self::STATUS_BERLAKU)
                ->get()->getRowArray();

            $penerusId = $penerus !== null ? (int) $penerus['id'] : null;

            // Kembalikan tabel live agar sama dengan dokumen yang kini
            // menaunginya. Tanpa ini layar menampilkan tulisan versi yang baru
            // saja ditiadakan. Hanya perlu bila yang dihapus memang sedang
            // berlaku — arsip lama tidak pernah menyentuh tabel live.
            //
            // terapkanKeLive() dipanggil LANGSUNG, bukan lewat terapkanUlang():
            // yang terakhir membuka transaksinya sendiri, sedangkan kita sudah
            // berada di dalam satu. TransaksiAman menolak transaksi bersarang
            // dengan tegas — CodeIgniter tidak bisa membatalkannya sungguhan,
            // jadi kegagalan di tengah akan meninggalkan penghapusan setengah
            // jadi yang tidak bisa dikembalikan.
            if ($status === self::STATUS_BERLAKU && $penerus !== null) {
                $this->terapkanKeLive(
                    $penerusId,
                    $opdId,
                    (int) $penerus['berlaku_mulai_tahun'],
                    $tahunMulai,
                    $tahunAkhir
                );
            }

            return [
                'nomor'       => (int) $revisi['nomor'],
                'nama'        => (string) $revisi['nama'],
                'status_lama' => $status,
                'penerus'     => $status === self::STATUS_BERLAKU ? $penerusId : null,
                'digeser'     => $digeser,
                'peringatan'  => $this->peringatanAwalPeriode($opdId, $tahunMulai, $tahunAkhir),
            ];
        }, 'penghapusan revisi IKU');
    }

    /**
     * Jahit ulang SELURUH garis waktu masa berlaku pada satu lingkup.
     *
     * =====================================================================
     * MENGAPA MENYELURUH, BUKAN MENAMBAL TETANGGA
     *
     * Versi lama hanya menyentuh tetangga terdekat, dan karena itu ia HARUS
     * melarang sebuah revisi melompati tetangganya — kalau melompat, tambalan
     * setempat meninggalkan tahun yang dipayungi dua revisi sekaligus, dan
     * resolveEfektif() menolak melayani tahun itu.
     *
     * Menjahit dari nol menghapus kebutuhan larangan tersebut: urutkan seluruh
     * revisi resmi menurut tahun mulainya, lalu tiap revisi ditutup tepat
     * sebelum tahun mulai revisi berikutnya. Yang paling akhir dibiarkan
     * terbuka (NULL) — artinya "belum ada revisi sesudahnya", bukan "berlaku
     * selamanya".
     *
     * Hasilnya rapat menurut definisi, berapa pun urutan nomornya. Itu pula
     * yang membuat tahun berlaku bisa dipilih bebas.
     *
     * @return int[] id revisi yang masa berlakunya ikut berubah
     */
    private function jahitUlangTimeline(?int $opdId, int $tahunMulai, int $tahunAkhir, int $kecuali = 0): array
    {
        $baris = $this->lingkup($opdId, $tahunMulai, $tahunAkhir)
            ->whereIn('status', [self::STATUS_BERLAKU, self::STATUS_SUPERSEDED])
            ->orderBy('berlaku_mulai_tahun', 'ASC')
            ->orderBy('nomor', 'ASC')
            ->get()->getResultArray();

        $digeser = [];
        $jumlah  = count($baris);

        foreach ($baris as $i => $r) {
            $batas = $i + 1 < $jumlah
                ? (int) $baris[$i + 1]['berlaku_mulai_tahun'] - 1
                : null;

            $lama = $r['berlaku_sampai_tahun'] !== null ? (int) $r['berlaku_sampai_tahun'] : null;

            if ($lama === $batas) {
                continue;
            }

            $this->db->table('iku_revisi')->where('id', (int) $r['id'])->update([
                'berlaku_sampai_tahun' => $batas,
                'updated_at'           => date('Y-m-d H:i:s'),
            ]);

            // Revisi yang tahunnya baru saja diubah bukan "ikut tergeser";
            // ia yang menggeser. Dikeluarkan supaya laporan ke layar jujur.
            if ((int) $r['id'] !== $kecuali) {
                $digeser[] = (int) $r['id'];
            }
        }

        // =================================================================
        // STATUS IKUT DIJAHIT, BUKAN HANYA JENDELA TAHUNNYA
        //
        // Versi awal method ini hanya membetulkan `berlaku_sampai_tahun` dan
        // membiarkan `status` apa adanya. Sejak tahun berlaku boleh dipilih
        // bebas, itu melahirkan keadaan yang tidak masuk akal: revisi yang
        // memayungi tahun-tahun TERAKHIR berstatus 'superseded', sementara
        // revisi yang memayungi tahun-tahun AWAL berstatus 'berlaku'.
        //
        // Bukan sekadar janggal di layar. `revisiBerlaku()` mengurutkan
        // `berlaku_mulai_tahun DESC` — jadi ia dan kolom status saling
        // bertentangan; dan `revisiKeadaanIzin()` menutup permanen revisi
        // ber-status 'superseded', sehingga izin sunting yang menempel padanya
        // tidak akan pernah bisa diselesaikan NAUPUN ditarik, dan seluruh
        // lingkup itu terkunci dari permohonan baru untuk selamanya.
        //
        // 'berlaku' berarti "versi terkini", dan yang terkini adalah yang
        // mulai berlakunya paling akhir. Itulah yang ditegakkan di sini.
        //
        // URUTANNYA PENTING: turunkan dulu, naikkan belakangan. UNIQUE index
        // `uq_iku_revisi_efektif` memuat `berlaku_key` yang lahir dari status,
        // sehingga dua baris 'berlaku' sesaat pun ditolak basis data.
        // =================================================================
        if ($baris !== []) {
            $terkini = (int) $baris[$jumlah - 1]['id'];

            foreach ($baris as $r) {
                if ((int) $r['id'] !== $terkini && $r['status'] === self::STATUS_BERLAKU) {
                    $this->db->table('iku_revisi')->where('id', (int) $r['id'])->update([
                        'status'     => self::STATUS_SUPERSEDED,
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);

                    // Izin sunting yang menempel pada revisi yang baru saja
                    // turun pangkat tidak bisa dikerjakan lagi — tutup, jangan
                    // ditinggalkan menggantung memblokir lingkupnya.
                    $this->tutupIzinRevisi((int) $r['id']);
                }
            }

            if ($baris[$jumlah - 1]['status'] === self::STATUS_SUPERSEDED) {
                $this->db->table('iku_revisi')->where('id', $terkini)->update([
                    'status'     => self::STATUS_BERLAKU,
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
            }
        }

        return $digeser;
    }

    /**
     * Tutup permohonan izin yang masih menggantung pada sebuah revisi.
     *
     * Dipanggil saat revisi itu turun pangkat jadi arsip: izin yang menempel
     * padanya sudah kehilangan gunanya, dan bila dibiarkan ia memblokir
     * SELURUH lingkup dari permohonan baru — sebab hanya satu permohonan
     * boleh menggantung per dokumen.
     *
     * Ditulis langsung ke tabelnya, bukan lewat IzinSuntingService: method ini
     * berjalan di dalam transaksi milik model, dan layanan itu membuka
     * jalurnya sendiri. Kolomnya sengaja dijaga `tableExists`, karena modul
     * izin bisa saja belum terpasang di basis data lama.
     */
    private function tutupIzinRevisi(int $revisiId): void
    {
        if (! $this->db->tableExists('dokumen_izin_sunting')) {
            return;
        }

        $this->db->table('dokumen_izin_sunting')
            ->where('modul', 'iku')
            ->where('version_id', $revisiId)
            ->whereIn('status', ['pending', 'disetujui'])
            ->update([
                'status'            => 'selesai',
                'catatan_keputusan' => 'Ditutup otomatis: revisi ini bukan lagi versi terkini.',
                'selesai_pada'      => date('Y-m-d H:i:s'),
                'updated_at'        => date('Y-m-d H:i:s'),
            ]);
    }

    /**
     * Adakah tahun di AWAL periode yang kini tidak dipayungi revisi mana pun?
     *
     * Bukan galat, dan karena itu tidak dilempar sebagai pengecualian: LAKIP
     * tahun tanpa payung IKU jatuh ke Renstra/RPJMD dengan pemberitahuan di
     * layar, dan keadaannya bisa dibatalkan dengan menggeser revisinya
     * kembali. Tetapi ia harus DIKATAKAN, bukan dibiarkan ditemukan sendiri
     * berbulan kemudian saat LAKIP disusun.
     */
    private function peringatanAwalPeriode(?int $opdId, int $tahunMulai, int $tahunAkhir): ?string
    {
        $paling = $this->lingkup($opdId, $tahunMulai, $tahunAkhir)
            ->selectMin('berlaku_mulai_tahun', 'awal')
            ->whereIn('status', [self::STATUS_BERLAKU, self::STATUS_SUPERSEDED])
            ->get()->getRowArray();

        $awal = isset($paling['awal']) ? (int) $paling['awal'] : 0;

        if ($awal <= $tahunMulai) {
            return null;
        }

        $tanpa = range($tahunMulai, $awal - 1);

        return 'Tahun ' . implode(', ', $tanpa) . ' kini TIDAK dipayungi versi IKU mana pun. '
            . 'LAKIP tahun itu akan dinilai memakai Renstra/RPJMD sampai ada revisi yang '
            . 'berlaku mulai ' . $tahunMulai . '.';
    }

    /**
     * Bekukan ULANG arsip sebuah revisi dari IKU berjalan.
     *
     * Dipakai ketika arsip revisi tidak lagi mencerminkan IKU yang sebenarnya
     * berlaku pada masanya — misalnya sesudah periode IKU dirapikan, ketika
     * revisi lahir di periode yang isinya belum lengkap.
     *
     * BUKAN operasi rutin. Arsip revisi memang dimaksudkan beku; membekukan
     * ulang berarti menyatakan "potret yang lama diambil dari keadaan yang
     * keliru". Karena itu jejaknya ditulis pada catatan revisi, bukan
     * dikerjakan diam-diam.
     *
     * Revisi berstatus `batal` ditolak: tidak ada gunanya memotret ulang
     * sesuatu yang sudah dinyatakan tidak berlaku.
     */
    public function bekukanUlangArsip(int $revisiId): void
    {
        if (! $this->siap()) {
            throw new RuntimeException('Tabel revisi IKU belum tersedia.');
        }

        $revisi = $this->ambil($revisiId);

        if ($revisi === null) {
            throw new RuntimeException('Revisi tidak ditemukan.');
        }

        if ($revisi['status'] === self::STATUS_BATAL) {
            throw new RuntimeException('Revisi yang dibatalkan tidak dibekukan ulang.');
        }

        $this->dalamTransaksi(function () use ($revisiId, $revisi) {
            $opdId = $revisi['opd_id'] !== null ? (int) $revisi['opd_id'] : null;

            $this->kosongkanArsipRevisi($revisiId);

            $this->bekukanLiveKeRevisi(
                $revisiId,
                $opdId,
                (int) $revisi['tahun_mulai'],
                (int) $revisi['tahun_akhir'],
                false
            );

            $catatan = trim((string) ($revisi['catatan'] ?? ''));
            $tambahan = 'Arsip dibekukan ulang pada ' . date('Y-m-d H:i:s')
                . ' karena potret sebelumnya diambil saat periode IKU belum dirapikan.';

            $this->db->table('iku_revisi')->where('id', $revisiId)->update([
                'catatan'    => $catatan === '' ? $tambahan : ($catatan . ' ' . $tambahan),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }, 'pembekuan ulang arsip revisi IKU');
    }

    /** Buang isi arsip sebuah revisi (kepalanya tetap). */
    private function kosongkanArsipRevisi(int $revisiId): void
    {
        $sasaranIds = array_column(
            $this->db->table('iku_revisi_sasaran')->select('id')
                ->where('revisi_id', $revisiId)->get()->getResultArray(),
            'id'
        );

        if ($sasaranIds !== []) {
            $indikatorIds = array_column(
                $this->db->table('iku_revisi_indikator')->select('id')
                    ->whereIn('revisi_sasaran_id', $sasaranIds)->get()->getResultArray(),
                'id'
            );

            if ($indikatorIds !== []) {
                $this->db->table('iku_revisi_target')->whereIn('revisi_indikator_id', $indikatorIds)->delete();

                if ($this->db->tableExists('iku_revisi_program')) {
                    $this->db->table('iku_revisi_program')->whereIn('revisi_indikator_id', $indikatorIds)->delete();
                }

                $this->db->table('iku_revisi_indikator')->whereIn('id', $indikatorIds)->delete();
            }

            $this->db->table('iku_revisi_sasaran')->whereIn('id', $sasaranIds)->delete();
        }
    }

    /** Buang draft. Barisnya tetap disimpan supaya jejak usulan tidak hilang. */
    /* =========================================================
     * PENGAJUAN & KEPUTUSAN (§17)
     *
     * =====================================================================
     * MENGAPA ADA STATUS MENUNGGU
     *
     * Sebelumnya alurnya draft -> berlaku, disahkan sendiri oleh penyusunnya.
     * Untuk IKU tingkat Kabupaten itu masuk akal: dokumennya milik mereka.
     * Untuk IKU OPD tidak — dokumen yang mengikat sebuah OPD sebaiknya
     * diperiksa pihak di luarnya, sebagaimana Renstra.
     *
     * `menunggu` disisipkan di antara keduanya. Ia tidak menyentuh
     * `berlaku_key` (yang hanya bereaksi pada 'berlaku'), sehingga jaminan
     * "satu revisi berlaku per lingkup" tetap utuh apa adanya.
     * =======================================================*/

    /**
     * Penyusun mengajukan draft untuk disahkan.
     *
     * @throws RuntimeException bila bukan draft atau isinya kosong
     */
    public function ajukan(int $revisiId, ?int $userId = null): bool
    {
        $revisi = $this->wajibDraft($revisiId, 'diajukan');

        // Revisi kosong ditolak di sini, bukan di tampilan: mengesahkan revisi
        // tanpa isi berarti memensiunkan seluruh IKU lingkup itu, dan verifikator
        // tidak semestinya diminta menebak apakah itu memang disengaja.
        $jumlah = (int) $this->db->table('iku_revisi_indikator')
            ->where('revisi_id', $revisiId)->countAllResults();

        if ($jumlah < 1) {
            throw new RuntimeException(
                'Revisi ini belum berisi indikator apa pun, jadi belum ada yang bisa diperiksa.'
            );
        }

        // =================================================================
        // TAHUN BERLAKU DIPERIKSA ULANG DI SINI, BUKAN HANYA SAAT DRAFT LAHIR
        //
        // tolakTahunBentrok() sudah dijalankan waktu draft dibuat, tetapi
        // keadaan bisa BERGESER di antara "dibuat" dan "diajukan":
        //
        //   * revisi lain disahkan lebih dulu dan mengambil tahun itu;
        //   * revisi lain diajukan lebih dulu (status 'menunggu');
        //   * tahun berlaku draft ini sendiri digeser lewat ubahTahunBerlaku().
        //
        // Tanpa pemeriksaan ini, bentrokannya baru ketahuan di meja Admin
        // Kabupaten saat hendak mengesahkan — pada orang yang tidak bisa
        // memperbaikinya, dan sesudah antrean verifikasi terlanjur terisi
        // pengajuan yang memang tidak mungkin disahkan.
        // =================================================================
        $this->tolakTahunBentrok(
            $revisi['opd_id'] !== null ? (int) $revisi['opd_id'] : null,
            (int) $revisi['tahun_mulai'],
            (int) $revisi['tahun_akhir'],
            (int) $revisi['berlaku_mulai_tahun']
        );

        return (bool) $this->db->table('iku_revisi')->where('id', $revisiId)->update([
            'status'       => self::STATUS_MENUNGGU,
            'submitted_by' => $userId,
            'submitted_at' => date('Y-m-d H:i:s'),
            'updated_at'   => date('Y-m-d H:i:s'),
        ]);
    }

    /** Penyusun menarik pengajuannya kembali selagi belum diputuskan. */
    public function tarikPengajuan(int $revisiId): bool
    {
        $revisi = $this->ambil($revisiId);

        if (! $revisi) {
            throw new RuntimeException('Revisi tidak ditemukan.');
        }

        if ($revisi['status'] !== self::STATUS_MENUNGGU) {
            throw new RuntimeException('Tidak ada pengajuan yang menggantung pada revisi ini.');
        }

        return (bool) $this->db->table('iku_revisi')->where('id', $revisiId)->update([
            'status'       => self::STATUS_DRAFT,
            'submitted_by' => null,
            'submitted_at' => null,
            'updated_at'   => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Verifikator mengembalikan pengajuan beserta catatannya.
     *
     * Catatan WAJIB. Pengembalian tanpa alasan memaksa penyusun menebak apa
     * yang harus diperbaiki, dan biasanya berakhir dengan pengajuan yang sama
     * dikirim ulang.
     */
    public function kembalikan(int $revisiId, string $catatan, ?int $userId = null): bool
    {
        $revisi  = $this->ambil($revisiId);
        $catatan = trim($catatan);

        if (! $revisi) {
            throw new RuntimeException('Revisi tidak ditemukan.');
        }

        if ($revisi['status'] !== self::STATUS_MENUNGGU) {
            throw new RuntimeException('Hanya pengajuan yang menggantung yang bisa dikembalikan.');
        }

        if ($catatan === '') {
            throw new RuntimeException('Catatan pengembalian wajib diisi.');
        }

        $lama = trim((string) ($revisi['catatan'] ?? ''));

        return (bool) $this->db->table('iku_revisi')->where('id', $revisiId)->update([
            'status'       => self::STATUS_DRAFT,
            'submitted_by' => null,
            'submitted_at' => null,
            // Catatan ditumpuk, tidak ditimpa: riwayat bolak-balik pengembalian
            // adalah bagian dari jejak keputusan.
            'catatan'      => trim($lama . "\n\n[Dikembalikan " . date('d M Y H:i') . '] ' . $catatan),
            'updated_at'   => date('Y-m-d H:i:s'),
        ]);
    }

    /** Antrean revisi IKU yang menunggu keputusan, lintas OPD. */
    public function menungguVerifikasi(): array
    {
        if (! $this->siap()) {
            return [];
        }

        return $this->db->table('iku_revisi r')
            ->select('r.*, o.nama_opd')
            ->join('opd o', 'o.id = r.opd_id', 'left')
            ->where('r.status', self::STATUS_MENUNGGU)
            ->where('r.opd_id IS NOT NULL', null, false)
            ->orderBy('r.submitted_at', 'ASC')
            ->get()->getResultArray();
    }

    private function wajibDraft(int $revisiId, string $aksi): array
    {
        $revisi = $this->ambil($revisiId);

        if (! $revisi) {
            throw new RuntimeException('Revisi tidak ditemukan.');
        }

        if ($revisi['status'] !== self::STATUS_DRAFT) {
            throw new RuntimeException(
                'Hanya revisi berstatus draft yang bisa ' . $aksi
                . '. Status sekarang: ' . $revisi['status'] . '.'
            );
        }

        return $revisi;
    }

    /* =========================================================
     * SYNC MASUK KE DRAFT REVISI
     *
     * =====================================================================
     * MENGAPA TIDAK LANGSUNG KE TABEL LIVE
     *
     * Selama IKU belum punya revisi yang berlaku, ia memang masih disusun —
     * menyalin langsung ke tabel live sama wajarnya dengan mengetik manual.
     *
     * Begitu ada revisi yang SUDAH DISAHKAN, keadaannya berubah: menambah
     * indikator langsung ke live berarti mengubah dokumen resmi tanpa
     * sepengetahuan siapa pun, dan tambahan itu tidak akan muncul di arsip
     * revisi mana pun. Sesudah titik itu, sync harus bermuara ke DRAFT dan
     * ikut antre disahkan seperti perubahan lainnya.
     * =======================================================*/

    /** Revisi yang sedang berlaku pada satu lingkup, bila ada. */
    public function revisiBerlaku(?int $opdId, int $tahunMulai, int $tahunAkhir): ?array
    {
        if (! $this->siap()) {
            return null;
        }

        $baris = $this->lingkup($opdId, $tahunMulai, $tahunAkhir)
            ->where('status', self::STATUS_BERLAKU)
            ->orderBy('berlaku_mulai_tahun', 'DESC')
            ->get()->getRowArray();

        return $baris ?: null;
    }

    /**
     * Draft yang masih bisa menampung hasil sync.
     *
     * Yang berstatus `menunggu` sengaja TIDAK ikut: isinya sedang diperiksa,
     * dan menambahinya diam-diam berarti verifikator memutuskan sesuatu yang
     * bukan lagi yang ia baca.
     *
     * @return array<int,array<string,mixed>>
     */
    public function draftTersedia(?int $opdId, int $tahunMulai, int $tahunAkhir): array
    {
        if (! $this->siap()) {
            return [];
        }

        return $this->lingkup($opdId, $tahunMulai, $tahunAkhir)
            ->where('status', self::STATUS_DRAFT)
            ->orderBy('berlaku_mulai_tahun', 'ASC')->orderBy('id', 'ASC')
            ->get()->getResultArray();
    }

    /**
     * Masukkan kandidat sync terpilih ke dalam arsip sebuah draft revisi.
     *
     * Tabel live TIDAK disentuh sama sekali — itu baru terjadi saat draft ini
     * disahkan.
     *
     * @param array $kandidat hasil IkuModel::getKandidatSync()
     * @param array $pilihan  [sumber_sasaran_id => [sumber_indikator_id, ...]]
     *
     * @return array{sasaran_baru:int, indikator_baru:int, target:int, dilewati:int}
     */
    /**
     * @param array $perbarui [sumber_sasaran_id => [sumber_indikator_id, ...]]
     *                        indikator yang PERUBAHANNYA diambil, bukan sekadar
     *                        ditambahkan. Dipisah dari $pilihan dengan sengaja:
     *                        menimpa nilai yang sudah ada adalah keputusan lain
     *                        daripada menambah baris baru, dan tidak boleh
     *                        terjadi hanya karena kotak yang sama tercentang.
     */
    public function imporKandidat(
        int $revisiId,
        array $kandidat,
        array $pilihan,
        string $sumber,
        ?int $renstraVersiId = null,
        array $perbarui = []
    ): array {
        $revisi = $this->ambil($revisiId);

        if (! $revisi) {
            throw new RuntimeException('Draft revisi tidak ditemukan.');
        }

        if ($revisi['status'] !== self::STATUS_DRAFT) {
            throw new RuntimeException(
                'Hanya draft yang bisa ditambahi hasil sync. Status sekarang: ' . $revisi['status'] . '.'
            );
        }

        return $this->dalamTransaksi(function () use ($revisiId, $revisi, $kandidat, $pilihan, $sumber, $renstraVersiId, $perbarui) {
            $db   = $this->db;
            $now  = date('Y-m-d H:i:s');
            $stat = ['sasaran_baru' => 0, 'indikator_baru' => 0, 'target' => 0,
                     'dilewati' => 0, 'diperbarui' => 0];

            // Peta isi draft sekarang, untuk menandai yang sudah ada. Dibaca
            // sekali di awal, bukan per baris, supaya tidak jadi N+1.
            $sasaranAda = [];

            foreach ($db->table('iku_revisi_sasaran')->where('revisi_id', $revisiId)
                ->get()->getResultArray() as $sa) {
                $sasaranAda[$this->kunciTeks($sa['sasaran'])] = $sa;
            }

            $indikatorAda = [];

            foreach ($db->table('iku_revisi_indikator')->where('revisi_id', $revisiId)
                ->get()->getResultArray() as $ia) {
                $indikatorAda[(int) $ia['revisi_sasaran_id']][$this->kunciTeks($ia['indikator'])] = (int) $ia['id'];
            }

            $urutanSasaran = count($sasaranAda);

            foreach ($kandidat as $sasaran) {
                $idSumber = (int) $sasaran['sumber_id'];

                if (empty($pilihan[$idSumber]) && empty($perbarui[$idSumber])) {
                    continue;
                }

                $dipilih  = array_map('intval', (array) ($pilihan[$idSumber] ?? []));
                $diperbaru = array_map('intval', (array) ($perbarui[$idSumber] ?? []));
                $kunciS   = $this->kunciTeks((string) $sasaran['sasaran']);

                if (isset($sasaranAda[$kunciS])) {
                    $arsipSasaranId = (int) $sasaranAda[$kunciS]['id'];
                } else {
                    $db->table('iku_revisi_sasaran')->insert([
                        'revisi_id'         => $revisiId,
                        'sumber_sasaran_id' => null,
                        'source_type'       => $sumber,
                        'source_version_id' => $renstraVersiId,
                        'source_ref_id'     => $sasaran['sumber_live_id'] ?? null,
                        'sasaran'           => $sasaran['sasaran'],
                        'tahun_mulai'       => (int) $revisi['tahun_mulai'],
                        'tahun_akhir'       => (int) $revisi['tahun_akhir'],
                        'urutan'            => $urutanSasaran++,
                        'jenis_perubahan'   => self::UBAH_BARU,
                        'created_at'        => $now,
                        'updated_at'        => $now,
                    ]);

                    $arsipSasaranId          = (int) $db->insertID();
                    $sasaranAda[$kunciS]     = ['id' => $arsipSasaranId];
                    $stat['sasaran_baru']++;
                }

                $urutanInd = count($indikatorAda[$arsipSasaranId] ?? []);

                foreach ($sasaran['indikator'] ?? [] as $ind) {
                    $sumberIndId = (int) $ind['sumber_id'];
                    $mintaTambah = in_array($sumberIndId, $dipilih, true);
                    $mintaUbah   = in_array($sumberIndId, $diperbaru, true);

                    if (! $mintaTambah && ! $mintaUbah) {
                        continue;
                    }

                    $kunciI = $this->kunciTeks((string) $ind['indikator']);
                    $adaId  = $indikatorAda[$arsipSasaranId][$kunciI] ?? null;

                    if ($adaId !== null) {
                        // Sudah ada di draft. Ditimpa HANYA bila perubahannya
                        // memang diminta; kalau tidak, dilewati seperti dulu.
                        if ($mintaUbah) {
                            $this->timpaIndikatorDraft($adaId, $ind, $revisi, $now);
                            $stat['diperbarui']++;
                        } else {
                            $stat['dilewati']++;
                        }

                        continue;
                    }

                    $db->table('iku_revisi_indikator')->insert([
                        'revisi_id'             => $revisiId,
                        'revisi_sasaran_id'     => $arsipSasaranId,
                        'sumber_indikator_id'   => null,
                        'source_type'           => $sumber,
                        'source_version_id'     => $renstraVersiId,
                        'source_ref_id'         => $ind['sumber_live_id'] ?? null,
                        'indikator'             => $ind['indikator'],
                        'definisi'              => $ind['definisi'] ?? null,
                        'satuan'                => $ind['satuan'] ?? null,
                        'satuan_nama'           => $ind['satuan_nama'] ?? null,
                        'jenis_indikator'       => $ind['jenis_indikator'] ?? null,
                        'baseline'              => $ind['baseline'] ?? null,
                        'urutan'                => $urutanInd++,
                        'status'                => 'draft',
                        'jenis_perubahan'       => self::UBAH_BARU,
                        'perubahan_substansial' => 0,
                        'created_at'            => $now,
                        'updated_at'            => $now,
                    ]);

                    $arsipIndId = (int) $db->insertID();

                    $indikatorAda[$arsipSasaranId][$kunciI] = $arsipIndId;
                    $stat['indikator_baru']++;

                    foreach ($ind['target'] ?? [] as $tahun => $nilai) {
                        // Target di luar periode revisi tidak ikut dibawa.
                        if ((int) $tahun < (int) $revisi['tahun_mulai']
                            || (int) $tahun > (int) $revisi['tahun_akhir']
                            || trim((string) $nilai) === '') {
                            continue;
                        }

                        $db->table('iku_revisi_target')->insert([
                            'revisi_indikator_id' => $arsipIndId,
                            'tahun'               => (int) $tahun,
                            'target'              => $nilai,
                            'created_at'          => $now,
                            'updated_at'          => $now,
                        ]);

                        $stat['target']++;
                    }
                }
            }

            return $stat;
        }, 'impor hasil sync ke draft revisi');
    }

    /**
     * Timpa satu indikator arsip dengan nilai dari sumber.
     *
     * `jenis_perubahan` ditandai `revisi`, bukan `tetap`: perubahannya nyata
     * dan verifikator perlu melihat mana saja yang bergeser. Kalau baris itu
     * memang baris baru dalam draft ini, penandanya dibiarkan `baru`.
     */
    private function timpaIndikatorDraft(int $arsipIndId, array $ind, array $revisi, string $now): void
    {
        $db   = $this->db;
        $lama = $db->table('iku_revisi_indikator')->where('id', $arsipIndId)->get()->getRowArray();

        $db->table('iku_revisi_indikator')->where('id', $arsipIndId)->update([
            'satuan'          => $ind['satuan'] ?? null,
            'satuan_nama'     => $ind['satuan_nama'] ?? null,
            'jenis_indikator' => $ind['jenis_indikator'] ?? null,
            'baseline'        => $ind['baseline'] ?? null,
            'jenis_perubahan' => ($lama['jenis_perubahan'] ?? self::UBAH_TETAP) === self::UBAH_BARU
                ? self::UBAH_BARU
                : self::UBAH_REVISI,
            'updated_at'      => $now,
        ]);

        // Target ditulis ulang seluruhnya, dengan nilai lamanya disimpan pada
        // `target_sebelumnya` supaya layar banding revisi bisa menunjukkan
        // pergeserannya tanpa menebak.
        $lamaTarget = [];

        foreach ($db->table('iku_revisi_target')->where('revisi_indikator_id', $arsipIndId)
            ->get()->getResultArray() as $t) {
            $lamaTarget[(int) $t['tahun']] = $t['target'];
        }

        $db->table('iku_revisi_target')->where('revisi_indikator_id', $arsipIndId)->delete();

        foreach ($ind['target'] ?? [] as $tahun => $nilai) {
            if ((int) $tahun < (int) $revisi['tahun_mulai']
                || (int) $tahun > (int) $revisi['tahun_akhir']
                || trim((string) $nilai) === '') {
                continue;
            }

            $db->table('iku_revisi_target')->insert([
                'revisi_indikator_id' => $arsipIndId,
                'tahun'               => (int) $tahun,
                'target'              => $nilai,
                'target_sebelumnya'   => $lamaTarget[(int) $tahun] ?? null,
                'created_at'          => $now,
                'updated_at'          => $now,
            ]);
        }
    }

    /* =========================================================
     * PRATINJAU PENGESAHAN (untuk verifikator)
     *
     * =====================================================================
     * MENGAPA INI ADA
     *
     * Menyetujui dokumen yang tidak bisa dibaca bukan verifikasi. Sampai
     * sekarang antrean hanya menampilkan nomor, nama, dan tahun berlaku —
     * verifikator menekan "Sahkan" tanpa pernah melihat apa yang disahkannya,
     * termasuk indikator mana yang akan DIPENSIUNKAN karenanya.
     *
     * Perbandingannya dilakukan ARSIP LAWAN ARSIP, bukan arsip lawan tabel
     * berjalan. Tabel berjalan bisa saja sudah bergeser oleh hal lain, dan
     * yang perlu dijawab di sini adalah "apa bedanya dengan dokumen yang
     * berlaku sekarang", bukan "apa bedanya dengan keadaan sesaat ini".
     * =======================================================*/

    /**
     * @return array{
     *     pembanding:?array, tahun:array{mulai:int,sampai:?int},
     *     digeser:array<int,array<string,mixed>>,
     *     baru:array<int,array<string,mixed>>,
     *     berubah:array<int,array<string,mixed>>,
     *     dihentikan:array<int,array<string,mixed>>
     * }
     */
    public function praTinjauPengesahan(int $revisiId): array
    {
        $kosong = [
            'pembanding' => null,
            'tahun'      => ['mulai' => 0, 'sampai' => null],
            'digeser'    => [],
            'baru'       => [],
            'berubah'    => [],
            'dihentikan' => [],
        ];

        $revisi = $this->ambil($revisiId);

        if (! $revisi) {
            return $kosong;
        }

        $opdId  = $revisi['opd_id'] !== null ? (int) $revisi['opd_id'] : null;
        $mulai  = (int) $revisi['berlaku_mulai_tahun'];

        // Revisi lain yang berlaku pada lingkup yang sama; yang mulainya lebih
        // awal akan ditutup di tahun ini, yang lebih akhir menutup yang ini.
        $lain = $this->lingkup($opdId, (int) $revisi['tahun_mulai'], (int) $revisi['tahun_akhir'])
            ->where('status', self::STATUS_BERLAKU)
            ->where('id !=', $revisiId)
            ->orderBy('berlaku_mulai_tahun', 'ASC')
            ->get()->getResultArray();

        $pembanding = null;
        $batasAtas  = null;
        $digeser    = [];

        foreach ($lain as $r) {
            $mulaiLain = (int) $r['berlaku_mulai_tahun'];

            if ($mulaiLain < $mulai) {
                // Kandidat pembanding: yang terdekat di bawah tahun ini.
                $pembanding = $r;
                $digeser[]  = $r;
            } elseif ($batasAtas === null || $mulaiLain < $batasAtas) {
                $batasAtas = $mulaiLain;
            }
        }

        $baruIsi = $this->indeksArsip($revisiId);
        $lamaIsi = $pembanding !== null ? $this->indeksArsip((int) $pembanding['id']) : [];

        $baru = $berubah = $dihentikan = [];

        foreach ($baruIsi as $kunci => $b) {
            $l = $lamaIsi[$kunci] ?? null;

            if ($l === null) {
                $baru[] = $b;

                continue;
            }

            $selisih = $this->selisihArsip($l, $b);

            if ($selisih !== []) {
                $berubah[] = $b + ['selisih' => $selisih];
            }
        }

        foreach ($lamaIsi as $kunci => $l) {
            if (! isset($baruIsi[$kunci])) {
                $dihentikan[] = $l;
            }
        }

        return [
            'pembanding' => $pembanding,
            'tahun'      => ['mulai' => $mulai, 'sampai' => $batasAtas],
            'digeser'    => $digeser,
            'baru'       => $baru,
            'berubah'    => $berubah,
            'dihentikan' => $dihentikan,
        ];
    }

    /**
     * Indikator sebuah revisi, berkunci silsilah lalu teks.
     *
     * Kuncinya memakai `sumber_indikator_id` bila ada — itu id indikator IKU
     * berjalan, satu-satunya penanda yang tidak ikut berubah ketika redaksinya
     * dirapikan. Tanpa itu, indikator yang cuma diperbaiki ejaannya akan
     * terbaca sebagai "yang lama dihentikan, yang baru muncul".
     *
     * @return array<string,array<string,mixed>>
     */
    private function indeksArsip(int $revisiId): array
    {
        $rows = $this->db->table('iku_revisi_indikator i')
            ->select('i.id, i.indikator, i.satuan, i.satuan_nama, i.baseline, i.jenis_indikator,
                      i.sumber_indikator_id, i.jenis_perubahan, s.sasaran')
            ->join('iku_revisi_sasaran s', 's.id = i.revisi_sasaran_id', 'left')
            ->where('i.revisi_id', $revisiId)
            ->orderBy('s.urutan', 'ASC')->orderBy('i.urutan', 'ASC')
            ->get()->getResultArray();

        if ($rows === []) {
            return [];
        }

        $target = [];

        foreach ($this->db->table('iku_revisi_target')
            ->select('revisi_indikator_id, tahun, target')
            ->whereIn('revisi_indikator_id', array_column($rows, 'id'))
            ->get()->getResultArray() as $t) {
            $target[(int) $t['revisi_indikator_id']][(int) $t['tahun']] = $t['target'];
        }

        $keluar = [];

        foreach ($rows as $r) {
            // Baris nisan "dihentikan" bukan isi dokumen; ia penanda perubahan.
            if ($r['jenis_perubahan'] === self::UBAH_DIHENTIKAN) {
                continue;
            }

            $r['target'] = $target[(int) $r['id']] ?? [];

            $kunci = ! empty($r['sumber_indikator_id'])
                ? 'live:' . (int) $r['sumber_indikator_id']
                : 'teks:' . $this->kunciTeks($r['indikator']);

            $keluar[$kunci] = $r;
        }

        return $keluar;
    }

    /** @return array<string,array{lama:?string,baru:?string}> */
    private function selisihArsip(array $lama, array $baru): array
    {
        $selisih = [];

        $banding = static function (string $nama, $a, $b) use (&$selisih): void {
            $a = trim((string) $a);
            $b = trim((string) $b);

            if ($a !== $b) {
                $selisih[$nama] = ['lama' => $a, 'baru' => $b];
            }
        };

        $banding('indikator', $lama['indikator'], $baru['indikator']);
        $banding('satuan', $lama['satuan_nama'] ?? $lama['satuan'], $baru['satuan_nama'] ?? $baru['satuan']);
        $banding('baseline', $lama['baseline'], $baru['baseline']);
        $banding('jenis_indikator', $lama['jenis_indikator'], $baru['jenis_indikator']);

        foreach (array_unique(array_merge(
            array_keys($lama['target'] ?? []),
            array_keys($baru['target'] ?? [])
        )) as $tahun) {
            $banding('target ' . $tahun, $lama['target'][$tahun] ?? null, $baru['target'][$tahun] ?? null);
        }

        return $selisih;
    }

    /**
     * Bekukan IKU berjalan menjadi sebuah revisi, lalu ajukan untuk disahkan.
     *
     * =====================================================================
     * MENGAPA BUKAN buatDraft()
     *
     * `buatDraft()` melahirkan DUA baris: revisi dasar yang langsung berstatus
     * `berlaku` (membekukan keadaan sekarang) plus draft kosong untuk disunting.
     * Itu benar ketika IKU-nya memang sudah resmi dan hendak diubah.
     *
     * Yang dibutuhkan di sini berbeda: IKU baru saja disusun — hasil sync atau
     * ketikan — dan BELUM pernah disahkan siapa pun. Memakai buatDraft() akan
     * menjadikannya `berlaku` tanpa satu pun pemeriksaan, persis yang hendak
     * dihindari. Maka isinya dibekukan ke satu revisi berstatus `menunggu`,
     * dan barulah Admin Kabupaten yang menjadikannya berlaku.
     *
     * @return int id revisi yang diajukan
     */
    public function bekukanDanAjukan(
        ?int $opdId,
        int $tahunMulai,
        int $tahunAkhir,
        ?int $userId = null,
        array $keterangan = []
    ): int {
        if (! $this->siap()) {
            throw new RuntimeException('Tabel revisi IKU belum tersedia.');
        }

        if ($this->berjalanMenunggu($opdId, $tahunMulai, $tahunAkhir) !== null) {
            throw new RuntimeException(
                'Sudah ada revisi IKU yang menunggu keputusan Admin Kabupaten pada periode ini.'
            );
        }

        // Sesudah ada revisi yang berlaku, pintu ini DITUTUP.
        //
        // Method ini membekukan tabel berjalan menjadi revisi baru. Dipakai
        // berulang sesudah pengesahan, ia akan melahirkan revisi demi revisi
        // yang isinya sama persis dengan yang sudah berlaku — riwayat penuh
        // baris yang tidak menandai perubahan apa pun.
        //
        // Perubahan berikutnya berjalan lewat menu Versi IKU: buat draft,
        // sunting apa yang berubah, lalu ajukan. Dengan begitu setiap revisi
        // yang lahir memang menandai sesuatu.
        if ($this->revisiBerlaku($opdId, $tahunMulai, $tahunAkhir) !== null) {
            throw new RuntimeException(
                'IKU periode ini sudah punya revisi yang berlaku. Perubahan berikutnya '
                . 'dibuat lewat menu Versi IKU — buat revisi, sunting yang berubah, lalu ajukan.'
            );
        }

        $adaIsi = $this->db->table('iku_sasaran')
            ->where('tahun_mulai', $tahunMulai)->where('tahun_akhir', $tahunAkhir);

        $opdId === null
            ? $adaIsi->where('opd_id IS NULL', null, false)
            : $adaIsi->where('opd_id', $opdId);

        if ($adaIsi->countAllResults() < 1) {
            throw new RuntimeException('IKU periode ini masih kosong, jadi belum ada yang bisa disahkan.');
        }

        return $this->dalamTransaksi(function () use ($opdId, $tahunMulai, $tahunAkhir, $userId, $keterangan) {
            $nomor   = $this->nomorBerikutnya($opdId, $tahunMulai, $tahunAkhir);
            $berlaku = $this->tahunBerlakuBerikutnya($opdId, $tahunMulai, $tahunAkhir);

            $revisiId = $this->sisipkanKepala([
                'opd_id'              => $opdId,
                'tahun_mulai'         => $tahunMulai,
                'tahun_akhir'         => $tahunAkhir,
                'nomor'               => $keterangan['nomor'] ?? (string) $nomor,
                'nama'                => $keterangan['nama']
                    ?? ('IKU ' . $tahunMulai . '-' . $tahunAkhir . ' (revisi ' . $nomor . ')'),
                'dasar_hukum'         => $keterangan['dasar_hukum'] ?? null,
                'nomor_dasar'         => $keterangan['nomor_dasar'] ?? null,
                'tanggal_dasar'       => $keterangan['tanggal_dasar'] ?? null,
                'catatan'             => $keterangan['catatan'] ?? null,
                'berlaku_mulai_tahun' => $berlaku,
                'status'              => self::STATUS_DRAFT,
                'dibuat_oleh'         => $userId,
            ]);

            $this->bekukanLiveKeRevisi($revisiId, $opdId, $tahunMulai, $tahunAkhir, false);

            $this->db->table('iku_revisi')->where('id', $revisiId)->update([
                'status'       => self::STATUS_MENUNGGU,
                'submitted_by' => $userId,
                'submitted_at' => date('Y-m-d H:i:s'),
                'updated_at'   => date('Y-m-d H:i:s'),
            ]);

            return $revisiId;
        }, 'pengajuan pengesahan IKU');
    }

    /** Revisi pada lingkup ini yang sedang menunggu keputusan, bila ada. */
    public function berjalanMenunggu(?int $opdId, int $tahunMulai, int $tahunAkhir): ?array
    {
        if (! $this->siap()) {
            return null;
        }

        $baris = $this->lingkup($opdId, $tahunMulai, $tahunAkhir)
            ->where('status', self::STATUS_MENUNGGU)
            ->get()->getRowArray();

        return $baris ?: null;
    }

    /**
     * Tahun mulai berlaku yang belum dipakai revisi lain.
     *
     * Dua revisi tidak boleh mulai berlaku pada tahun yang sama — kalau itu
     * terjadi, "revisi mana yang dipakai tahun ini" tidak punya jawaban.
     * Dimulai dari tahun berjalan bila masih di dalam periode, sebab revisi
     * biasanya menyangkut tahun yang sedang berjalan, bukan tahun yang lewat.
     */
    private function tahunBerlakuBerikutnya(?int $opdId, int $tahunMulai, int $tahunAkhir): int
    {
        $dipakai = [];

        foreach ($this->lingkup($opdId, $tahunMulai, $tahunAkhir)->get()->getResultArray() as $r) {
            $dipakai[(int) $r['berlaku_mulai_tahun']] = true;
        }

        $mulai = max($tahunMulai, min((int) date('Y'), $tahunAkhir));

        for ($t = $mulai; $t <= $tahunAkhir; $t++) {
            if (! isset($dipakai[$t])) {
                return $t;
            }
        }

        // Mundur mencari celah di tahun-tahun awal periode.
        for ($t = $tahunMulai; $t < $mulai; $t++) {
            if (! isset($dipakai[$t])) {
                return $t;
            }
        }

        throw new RuntimeException(
            'Seluruh tahun pada periode ' . $tahunMulai . '-' . $tahunAkhir
            . ' sudah dipakai revisi lain. Batalkan salah satu revisi lebih dulu.'
        );
    }

    /** Query dasar satu lingkup revisi. */
    private function lingkup(?int $opdId, int $tahunMulai, int $tahunAkhir)
    {
        $b = $this->db->table('iku_revisi')
            ->where('tahun_mulai', $tahunMulai)
            ->where('tahun_akhir', $tahunAkhir);

        return $opdId === null
            ? $b->where('opd_id IS NULL', null, false)
            : $b->where('opd_id', $opdId);
    }

    /**
     * Kolom `renstra_tujuan_id` untuk baris arsip sasaran — bila kolomnya ada.
     *
     * Dijaga `fieldExists` mengikuti pola IkuModel::kolomTujuanMandiri():
     * basis data yang belum menjalankan
     * db/update_2026-08-31_sasaran_mandiri_dalam_revisi.sql tetap berjalan
     * seperti sebelumnya, hanya tanpa penurunan tujuan.
     *
     * Menerima baris live (`renstra_tujuan_id`) maupun larik form.
     *
     * @param array<string,mixed> $sumber
     * @return array<string,int|null>
     */
    private function kolomTujuanArsip(array $sumber): array
    {
        if (! $this->db->fieldExists('renstra_tujuan_id', 'iku_revisi_sasaran')) {
            return [];
        }

        return [
            'renstra_tujuan_id' => ! empty($sumber['renstra_tujuan_id'])
                ? (int) $sumber['renstra_tujuan_id'] : null,
        ];
    }

    /**
     * Kolom `renstra_tujuan_id` untuk baris LIVE, diambil dari arsipnya.
     *
     * Dipisah dari kolomTujuanArsip() karena tabel yang diperiksa berbeda:
     * arsip bisa saja sudah punya kolomnya sementara live belum, atau
     * sebaliknya, pada basis data yang urutan migrasinya tidak seragam.
     *
     * @param array<string,mixed> $arsip
     * @return array<string,int|null>
     */
    private function kolomTujuanLive(array $arsip): array
    {
        if (! $this->db->fieldExists('renstra_tujuan_id', 'iku_sasaran')
            || ! array_key_exists('renstra_tujuan_id', $arsip)) {
            return [];
        }

        return [
            'renstra_tujuan_id' => ! empty($arsip['renstra_tujuan_id'])
                ? (int) $arsip['renstra_tujuan_id'] : null,
        ];
    }

    /** Kunci pembanding teks: beda spasi & huruf besar bukan indikator berbeda. */
    private function kunciTeks(?string $teks): string
    {
        return mb_strtolower(trim(preg_replace('/\s+/u', ' ', (string) $teks)));
    }

    public function batalkan(int $revisiId, ?int $userId = null): bool
    {
        if (! $this->siap()) {
            return false;
        }

        $revisi = $this->ambil($revisiId);

        if (! $revisi) {
            throw new RuntimeException('Revisi tidak ditemukan.');
        }
        if ($revisi['status'] !== self::STATUS_DRAFT) {
            throw new RuntimeException('Hanya draft yang bisa dibatalkan. Revisi yang sudah berlaku harus digeser oleh revisi berikutnya.');
        }

        return (bool) $this->db->table('iku_revisi')->where('id', $revisiId)->update([
            'status'     => self::STATUS_BATAL,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /* =========================================================
     * PENERAPAN ARSIP -> TABEL LIVE
     * =======================================================*/

    /**
     * Salin isi arsip revisi ke tabel live.
     *
     * Dipanggil HANYA dari dalam transaksi sahkan().
     *
     * Indikator live yang tidak lagi ada di arsip TIDAK dihapus — hanya
     * dipensiunkan. Inilah pengganti DELETE yang selama ini merusak sejarah:
     * target & program lamanya ikut terjaga karena barisnya masih ada.
     *
     * @return int jumlah indikator yang dipensiunkan
     */
    /**
     * Terapkan ulang isi revisi yang SUDAH berlaku ke tabel live.
     *
     * Dipakai setelah revisi berlaku disunting di bawah izin. Tanpa langkah
     * ini, arsipnya berubah tetapi IKU berjalan tetap memuat teks dan target
     * lama — dua sumber kebenaran yang berbeda, tanpa satu pun galat.
     *
     * Laporan LAKIP yang sudah difinalkan TIDAK ikut berubah: angkanya sudah
     * disalin ke `lakip_snapshot_baris`, bukan dibaca ulang dari sini.
     */
    public function terapkanUlang(int $revisiId): int
    {
        $revisi = $this->ambil($revisiId);

        if (! $revisi) {
            throw new RuntimeException('Revisi tidak ditemukan.');
        }

        if ($revisi['status'] !== self::STATUS_BERLAKU) {
            throw new RuntimeException(
                'Hanya revisi yang sedang berlaku yang bisa diterapkan ulang ke IKU berjalan.'
            );
        }

        return $this->dalamTransaksi(
            fn () => $this->terapkanKeLive(
                $revisiId,
                $revisi['opd_id'] === null ? null : (int) $revisi['opd_id'],
                (int) $revisi['berlaku_mulai_tahun'],
                (int) $revisi['tahun_mulai'],
                (int) $revisi['tahun_akhir']
            ),
            'penerapan ulang revisi IKU'
        );
    }

    private function terapkanKeLive(
        int $revisiId,
        ?int $opdId,
        int $berlakuMulai,
        int $tahunMulai,
        int $tahunAkhir
    ): int {
        $db  = $this->db;
        $now = date('Y-m-d H:i:s');

        $arsipSasaran = $db->table('iku_revisi_sasaran')
            ->where('revisi_id', $revisiId)
            ->orderBy('urutan', 'ASC')->orderBy('id', 'ASC')
            ->get()->getResultArray();

        $sasaranLiveDipakai   = [];
        $indikatorLiveDipakai = [];

        foreach ($arsipSasaran as $urutanSasaran => $as) {
            $sasaranLiveId = $as['sumber_sasaran_id'] !== null ? (int) $as['sumber_sasaran_id'] : 0;

            $isiSasaran = [
                'sasaran'         => $as['sasaran'],
                'tahun_mulai'     => (int) $as['tahun_mulai'],
                'tahun_akhir'     => (int) $as['tahun_akhir'],
                'urutan'          => $urutanSasaran,
                'revisi_id'       => $revisiId,
                // Dikembalikan ke live, bukan dibiarkan. Baris live BARU yang
                // lahir dari pengesahan ini tidak punya jejak dari mana pun
                // selain arsipnya.
                'source_type'       => $as['source_type'] ?? null,
                'source_version_id' => $as['source_version_id'] ?? null,
                'source_sasaran_id' => $as['source_ref_id'] ?? null,
                'updated_at'      => $now,
            ] + $this->kolomTujuanLive($as);

            if ($sasaranLiveId > 0 && $db->table('iku_sasaran')->where('id', $sasaranLiveId)->countAllResults() > 0) {
                // Sasaran yang dihidupkan kembali oleh revisi ini tidak boleh
                // tetap berstatus pensiun.
                $isiSasaran['dihentikan_pada']   = null;
                $isiSasaran['berlaku_sampai']    = null;
                $isiSasaran['alasan_dihentikan'] = null;
                $db->table('iku_sasaran')->where('id', $sasaranLiveId)->update($isiSasaran);
            } else {
                $isiSasaran['opd_id']     = $opdId;
                $isiSasaran['created_at'] = $now;
                $db->table('iku_sasaran')->insert($isiSasaran);
                $sasaranLiveId = (int) $db->insertID();

                $db->table('iku_revisi_sasaran')->where('id', (int) $as['id'])
                    ->update(['sumber_sasaran_id' => $sasaranLiveId]);
            }

            $sasaranLiveDipakai[] = $sasaranLiveId;

            $arsipIndikator = $db->table('iku_revisi_indikator')
                ->where('revisi_sasaran_id', (int) $as['id'])
                ->orderBy('urutan', 'ASC')->orderBy('id', 'ASC')
                ->get()->getResultArray();

            foreach ($arsipIndikator as $urutanInd => $ai) {
                if ($ai['jenis_perubahan'] === self::UBAH_DIHENTIKAN) {
                    // Baris penanda "tidak dipakai lagi": tidak diterapkan ke
                    // live sebagai indikator aktif; pemensiunannya ditangani
                    // pada penyapuan di bawah.
                    continue;
                }

                $indLiveId = $ai['sumber_indikator_id'] !== null ? (int) $ai['sumber_indikator_id'] : 0;

                $isiInd = [
                    'iku_sasaran_id'          => $sasaranLiveId,
                    'indikator'               => $ai['indikator'],
                    'definisi'                => $ai['definisi'],
                    'rumusan_perhitungan'     => $ai['rumusan_perhitungan'],
                    'satuan'                  => $ai['satuan'],
                    'sumber_data'             => $ai['sumber_data'],
                    'penanggung_jawab'        => $ai['penanggung_jawab'],
                    'jenis_indikator'         => $ai['jenis_indikator'],
                    'baseline'                => $ai['baseline'],
                    'urutan'                  => $urutanInd,
                    'status'                  => $ai['status'],
                    'revisi_id'               => $revisiId,
                    'source_type'             => $ai['source_type'] ?? null,
                    'source_version_id'       => $ai['source_version_id'] ?? null,
                    'source_indikator_id'     => $ai['source_ref_id'] ?? null,
                    'jenis_perubahan'         => $ai['jenis_perubahan'],
                    'indikator_sebelumnya_id' => $ai['indikator_sebelumnya_id'] !== null
                        ? (int) $ai['indikator_sebelumnya_id'] : null,
                    'perubahan_substansial'   => (int) $ai['perubahan_substansial'],
                    'updated_at'              => $now,
                ];

                if ($indLiveId > 0 && $db->table('iku_indikator')->where('id', $indLiveId)->countAllResults() > 0) {
                    $isiInd['dihentikan_pada']   = null;
                    $isiInd['berlaku_sampai']    = null;
                    $isiInd['alasan_dihentikan'] = null;
                    $db->table('iku_indikator')->where('id', $indLiveId)->update($isiInd);
                } else {
                    $isiInd['created_at'] = $now;
                    $db->table('iku_indikator')->insert($isiInd);
                    $indLiveId = (int) $db->insertID();

                    $db->table('iku_revisi_indikator')->where('id', (int) $ai['id'])
                        ->update(['sumber_indikator_id' => $indLiveId]);
                }

                $indikatorLiveDipakai[] = $indLiveId;

                $this->terapkanTargetProgram((int) $ai['id'], $indLiveId, $now);
            }
        }

        // --- penyapuan: yang tidak lagi disebut arsip -> PENSIUN, bukan hapus
        $dipensiunkan = $this->pensiunkanYangHilang(
            $opdId,
            $tahunMulai,
            $tahunAkhir,
            $sasaranLiveDipakai,
            $indikatorLiveDipakai,
            $revisiId,
            $berlakuMulai,
            $now
        );

        return $dipensiunkan;
    }

    /** Salin target & program satu indikator dari arsip ke tabel live. */
    private function terapkanTargetProgram(int $arsipIndikatorId, int $indLiveId, string $now): void
    {
        $db = $this->db;

        $targets = $db->table('iku_revisi_target')
            ->where('revisi_indikator_id', $arsipIndikatorId)
            ->orderBy('tahun', 'ASC')
            ->get()->getResultArray();

        foreach ($targets as $t) {
            $tahun = (int) $t['tahun'];

            $ada = $db->table('iku_target')
                ->where('iku_indikator_id', $indLiveId)
                ->where('tahun', $tahun)
                ->get()->getRowArray();

            if ($ada) {
                $db->table('iku_target')->where('id', (int) $ada['id'])
                    ->update(['target' => $t['target'], 'updated_at' => $now]);
            } else {
                $db->table('iku_target')->insert([
                    'iku_indikator_id' => $indLiveId,
                    'tahun'            => $tahun,
                    'target'           => $t['target'],
                    'created_at'       => $now,
                    'updated_at'       => $now,
                ]);
            }
        }

        // Program pendukung tidak punya sejarah yang perlu dijaga (tidak pernah
        // dirujuk LAKIP), jadi boleh disusun ulang apa adanya.
        $db->table('iku_program')->where('iku_indikator_id', $indLiveId)->delete();

        $programs = $db->table('iku_revisi_program')
            ->where('revisi_indikator_id', $arsipIndikatorId)
            ->orderBy('urutan', 'ASC')
            ->get()->getResultArray();

        foreach ($programs as $urutan => $p) {
            $db->table('iku_program')->insert([
                'iku_indikator_id' => $indLiveId,
                'program'          => $p['program'],
                'urutan'           => $urutan,
                'created_at'       => $now,
            ]);
        }
    }

    /**
     * Pensiunkan sasaran/indikator live yang tidak lagi disebut revisi ini.
     *
     * INVARIANT 8 — tidak ada hard-delete. `berlaku_sampai` diisi satu tahun
     * sebelum revisi ini mulai berlaku, sehingga LAKIP tahun-tahun sebelumnya
     * tetap melihatnya sebagai indikator yang sah pada masanya.
     */
    private function pensiunkanYangHilang(
        ?int $opdId,
        int $tahunMulai,
        int $tahunAkhir,
        array $sasaranDipakai,
        array $indikatorDipakai,
        int $revisiId,
        int $berlakuMulai,
        string $now
    ): int {
        $db = $this->db;

        // PENTING: penyapuan WAJIB dibatasi periode revisi, bukan hanya pemilik.
        //
        // Satu OPD (dan tingkat kabupaten) lazim punya beberapa periode IKU
        // sekaligus — 2020-2024 di samping 2025-2029. Tanpa batas periode,
        // mengesahkan revisi satu periode akan memensiunkan seluruh IKU periode
        // lain milik pemilik yang sama, karena tidak satu pun dari mereka
        // tercantum di arsip revisi ini.
        //
        // Pola pembatasan periode ini konsisten dengan getMatrix() dan
        // petaIkuTerpasang() yang juga mencocokkan tahun_mulai DAN tahun_akhir
        // secara persis.
        $bSasaran = $db->table('iku_sasaran')
            ->select('id')
            ->where('tahun_mulai', $tahunMulai)
            ->where('tahun_akhir', $tahunAkhir);

        $opdId === null
            ? $bSasaran->where('opd_id IS NULL', null, false)
            : $bSasaran->where('opd_id', $opdId);

        $semuaSasaran = array_map('intval', array_column(
            $bSasaran->where('dihentikan_pada IS NULL', null, false)->get()->getResultArray(),
            'id'
        ));

        $sasaranHilang = array_values(array_diff($semuaSasaran, array_map('intval', $sasaranDipakai)));

        // Indikator hilang dicari dari sasaran yang MASIH dipakai; indikator di
        // bawah sasaran yang dipensiunkan ikut terbawa oleh blok berikutnya.
        $indikatorHilang = [];

        if (! empty($sasaranDipakai)) {
            $semuaIndikator = array_map('intval', array_column(
                $db->table('iku_indikator')->select('id')
                    ->whereIn('iku_sasaran_id', array_map('intval', $sasaranDipakai))
                    ->where('dihentikan_pada IS NULL', null, false)
                    ->get()->getResultArray(),
                'id'
            ));

            $indikatorHilang = array_values(array_diff($semuaIndikator, array_map('intval', $indikatorDipakai)));
        }

        if (! empty($sasaranHilang)) {
            $indikatorIkut = array_map('intval', array_column(
                $db->table('iku_indikator')->select('id')
                    ->whereIn('iku_sasaran_id', $sasaranHilang)
                    ->where('dihentikan_pada IS NULL', null, false)
                    ->get()->getResultArray(),
                'id'
            ));

            $indikatorHilang = array_values(array_unique(array_merge($indikatorHilang, $indikatorIkut)));

            $db->table('iku_sasaran')->whereIn('id', $sasaranHilang)->update([
                'dihentikan_pada'   => $now,
                'berlaku_sampai'    => $berlakuMulai - 1,
                'alasan_dihentikan' => 'Tidak lagi tercantum pada revisi yang disahkan (revisi id ' . $revisiId . ').',
                'updated_at'        => $now,
            ]);
        }

        if (! empty($indikatorHilang)) {
            $db->table('iku_indikator')->whereIn('id', $indikatorHilang)->update([
                'dihentikan_pada'   => $now,
                'berlaku_sampai'    => $berlakuMulai - 1,
                'jenis_perubahan'   => self::UBAH_DIHENTIKAN,
                'alasan_dihentikan' => 'Tidak lagi tercantum pada revisi yang disahkan (revisi id ' . $revisiId . ').',
                'updated_at'        => $now,
            ]);
        }

        return count($indikatorHilang);
    }

    /* =========================================================
     * PEMBEKUAN LIVE -> ARSIP
     * =======================================================*/

    /**
     * Salin kondisi tabel live satu lingkup ke dalam arsip sebuah revisi.
     *
     * @param bool $sertakanPensiun ikut menyalin baris yang sudah dipensiunkan
     */
    private function bekukanLiveKeRevisi(
        int $revisiId,
        ?int $opdId,
        int $tahunMulai,
        int $tahunAkhir,
        bool $sertakanPensiun = false
    ): void {
        $db  = $this->db;
        $now = date('Y-m-d H:i:s');

        $b = $db->table('iku_sasaran')
            ->select('*')
            ->where('tahun_mulai', $tahunMulai)
            ->where('tahun_akhir', $tahunAkhir);

        $opdId === null ? $b->where('opd_id IS NULL', null, false) : $b->where('opd_id', $opdId);

        if (! $sertakanPensiun) {
            $b->where('dihentikan_pada IS NULL', null, false);
        }

        $sasaranRows = $b->orderBy('urutan', 'ASC')->orderBy('id', 'ASC')->get()->getResultArray();

        foreach ($sasaranRows as $sas) {
            $db->table('iku_revisi_sasaran')->insert($this->kolomTujuanArsip($sas) + [
                'revisi_id'         => $revisiId,
                'sumber_sasaran_id' => (int) $sas['id'],
                // Jejak asal ikut dibekukan. Tanpa ini, membekukan IKU ke
                // sebuah revisi justru MEMBUANG keterangan dari Renstra versi
                // mana ia dulu diambil — dan hilangnya tidak bergejala.
                'source_type'       => $sas['source_type'] ?? null,
                'source_version_id' => $sas['source_version_id'] ?? null,
                'source_ref_id'     => $sas['source_sasaran_id'] ?? null,
                'sasaran'           => $sas['sasaran'],
                'tahun_mulai'       => (int) $sas['tahun_mulai'],
                'tahun_akhir'       => (int) $sas['tahun_akhir'],
                'urutan'            => (int) $sas['urutan'],
                'jenis_perubahan'   => self::UBAH_TETAP,
                'created_at'        => $now,
                'updated_at'        => $now,
            ]);

            $arsipSasaranId = (int) $db->insertID();

            $bi = $db->table('iku_indikator ind')
                ->select('ind.*, ' . self::SATUAN_SELECT . ' AS satuan_nama', false)
                ->join('satuan sat', self::SATUAN_JOIN, 'left', false)
                ->where('ind.iku_sasaran_id', (int) $sas['id']);

            if (! $sertakanPensiun) {
                $bi->where('ind.dihentikan_pada IS NULL', null, false);
            }

            $indikatorRows = $bi->orderBy('ind.urutan', 'ASC')->orderBy('ind.id', 'ASC')->get()->getResultArray();

            foreach ($indikatorRows as $ind) {
                $db->table('iku_revisi_indikator')->insert([
                    'revisi_id'               => $revisiId,
                    'revisi_sasaran_id'       => $arsipSasaranId,
                    'sumber_indikator_id'     => (int) $ind['id'],
                    'source_type'             => $ind['source_type'] ?? null,
                    'source_version_id'       => $ind['source_version_id'] ?? null,
                    'source_ref_id'           => $ind['source_indikator_id'] ?? null,
                    'indikator'               => $ind['indikator'],
                    'definisi'                => $ind['definisi'],
                    'rumusan_perhitungan'     => $ind['rumusan_perhitungan'],
                    'satuan'                  => $ind['satuan'],
                    // Nama satuan dibekukan di sini — kalau master `satuan`
                    // kelak diganti namanya, arsip tetap berbunyi seperti saat
                    // dokumen ini berlaku.
                    'satuan_nama'             => $ind['satuan_nama'],
                    'sumber_data'             => $ind['sumber_data'],
                    'penanggung_jawab'        => $ind['penanggung_jawab'],
                    'jenis_indikator'         => $ind['jenis_indikator'],
                    'baseline'                => $ind['baseline'],
                    'urutan'                  => (int) $ind['urutan'],
                    'status'                  => $ind['status'],
                    'jenis_perubahan'         => self::UBAH_TETAP,
                    'indikator_sebelumnya_id' => $ind['indikator_sebelumnya_id'] ?? null,
                    'perubahan_substansial'   => (int) ($ind['perubahan_substansial'] ?? 0),
                    'created_at'              => $now,
                    'updated_at'              => $now,
                ]);

                $arsipIndikatorId = (int) $db->insertID();

                $targets = $db->table('iku_target')
                    ->where('iku_indikator_id', (int) $ind['id'])
                    ->orderBy('tahun', 'ASC')
                    ->get()->getResultArray();

                foreach ($targets as $t) {
                    $db->table('iku_revisi_target')->insert([
                        'revisi_indikator_id' => $arsipIndikatorId,
                        'tahun'               => (int) $t['tahun'],
                        'target'              => $t['target'],
                        'created_at'          => $now,
                        'updated_at'          => $now,
                    ]);
                }

                $programs = $db->table('iku_program')
                    ->where('iku_indikator_id', (int) $ind['id'])
                    ->orderBy('urutan', 'ASC')
                    ->get()->getResultArray();

                foreach ($programs as $p) {
                    $db->table('iku_revisi_program')->insert([
                        'revisi_indikator_id' => $arsipIndikatorId,
                        'program'             => $p['program'],
                        'urutan'              => (int) $p['urutan'],
                        'created_at'          => $now,
                    ]);
                }
            }
        }

        $db->table('iku_revisi')->where('id', $revisiId)->update([
            'dibekukan_pada' => $now,
            'updated_at'     => $now,
        ]);
    }

    /* =========================================================
     * PEMERIKSAAN REFERENSI SEJARAH  (invariant 8)
     * =======================================================*/

    /**
     * Dari sekumpulan id indikator live, mana yang sudah dirujuk data historis?
     *
     * Indikator yang masuk daftar ini TIDAK BOLEH di-hard-delete. Pemanggilnya
     * (IkuModel::updateComplete) memensiunkannya sebagai gantinya.
     *
     * Empat sumber rujukan diperiksa:
     *   1. arsip revisi mana pun yang menyalin indikator ini;
     *   2. arsip revisi yang mencatatnya sebagai indikator yang DIGANTIKAN;
     *   3. indikator live lain yang mencatatnya sebagai pendahulunya (lineage);
     *   4. baris snapshot LAKIP yang menambatkan diri padanya.
     *
     * Nomor 3 penting untuk Case 14: menghapus indikator A setelah B
     * menggantikannya akan memutus jejak A -> B, dan tren tahun-tahun
     * sebelumnya kehilangan asal-usulnya.
     *
     * @param int[] $indikatorIds
     *
     * @return int[] id yang terbukti dirujuk sejarah
     */
    public function indikatorDirujukSejarah(array $indikatorIds): array
    {
        $indikatorIds = array_values(array_unique(array_filter(array_map('intval', $indikatorIds))));

        if (empty($indikatorIds)) {
            return [];
        }

        $dirujuk = [];

        $sumber = [
            ['iku_revisi_indikator', 'sumber_indikator_id'],
            ['iku_revisi_indikator', 'indikator_sebelumnya_id'],
            ['iku_indikator',        'indikator_sebelumnya_id'],
            ['lakip_snapshot_baris', 'iku_indikator_id'],
            // Baris CASCADING yang berjangkar pada indikator ini.
            //
            // Sejak matriks Cascading berakar pada IKU, jangkar ini bukan
            // sekadar penanda sumber: ia satu-satunya yang menahan baris
            // Eselon III/IV/Pelaksana tetap tampil. FK-nya ON DELETE SET NULL,
            // jadi menghapus indikatornya TIDAK menghapus baris cascading —
            // tetapi baris itu kehilangan induknya dan lenyap dari layar,
            // membawa serta isian operator di bawahnya. Tidak ada galat, tidak
            // ada peringatan; datanya masih di basis data tetapi tak terjangkau.
            //
            // Karena itu indikator yang sedang menopang cascading diperlakukan
            // sama dengan yang dirujuk sejarah: DIPENSIUNKAN, bukan dihapus.
            ['cascading_sasaran_opd', 'iku_indikator_id'],
        ];

        foreach ($sumber as [$tabel, $kolom]) {
            // Kolom opsional: basis data yang belum menjalankan migrasi
            // 2026-08-27 belum punya jangkar IKU pada cascading.
            if ($tabel === 'cascading_sasaran_opd' && ! $this->db->fieldExists($kolom, $tabel)) {
                continue;
            }

            // Tabel opsional: server yang belum menjalankan SQL 2026-08-18
            // hanya kehilangan lapisan pemeriksaan ini, bukan seluruh modul.
            if (! $this->db->tableExists($tabel)) {
                continue;
            }

            $b = $this->db->table($tabel)->select($kolom . ' AS ref')->whereIn($kolom, $indikatorIds);

            if ($tabel === 'iku_indikator') {
                // Baris yang menunjuk dirinya sendiri bukan rujukan sejarah.
                $b->where($kolom . ' != id', null, false);
            }

            foreach ($b->get()->getResultArray() as $r) {
                $dirujuk[(int) $r['ref']] = true;
            }
        }

        return array_keys($dirujuk);
    }

    /**
     * Padanan indikatorDirujukSejarah() untuk sasaran.
     *
     * @param int[] $sasaranIds
     *
     * @return int[]
     */
    public function sasaranDirujukSejarah(array $sasaranIds): array
    {
        $sasaranIds = array_values(array_unique(array_filter(array_map('intval', $sasaranIds))));

        if (empty($sasaranIds) || ! $this->db->tableExists('iku_revisi_sasaran')) {
            return [];
        }

        $rows = $this->db->table('iku_revisi_sasaran')
            ->select('sumber_sasaran_id AS ref')
            ->whereIn('sumber_sasaran_id', $sasaranIds)
            ->get()
            ->getResultArray();

        return array_values(array_unique(array_map(static fn ($r) => (int) $r['ref'], $rows)));
    }

    /**
     * Pensiunkan indikator tanpa menghapusnya.
     *
     * Dipakai IkuModel saat form IKU biasa menghilangkan sebuah indikator yang
     * ternyata sudah dirujuk sejarah.
     *
     * @param int[] $indikatorIds
     */
    public function pensiunkanIndikator(array $indikatorIds, string $alasan, ?int $berlakuSampai = null): int
    {
        $indikatorIds = array_values(array_unique(array_filter(array_map('intval', $indikatorIds))));

        if (empty($indikatorIds)) {
            return 0;
        }

        $isi = [
            'dihentikan_pada'   => date('Y-m-d H:i:s'),
            'jenis_perubahan'   => self::UBAH_DIHENTIKAN,
            'alasan_dihentikan' => $alasan,
            'updated_at'        => date('Y-m-d H:i:s'),
        ];

        if ($berlakuSampai !== null) {
            $isi['berlaku_sampai'] = $berlakuSampai;
        }

        $this->db->table('iku_indikator')
            ->whereIn('id', $indikatorIds)
            ->where('dihentikan_pada IS NULL', null, false)
            ->update($isi);

        return count($indikatorIds);
    }

    /* =========================================================
     * HELPER
     * =======================================================*/

    private function sisipkanKepala(array $isi): int
    {
        $isi['created_at'] = $isi['created_at'] ?? date('Y-m-d H:i:s');
        $isi['updated_at'] = $isi['updated_at'] ?? date('Y-m-d H:i:s');

        // opd_key & berlaku_key adalah generated column — jangan pernah dikirim.
        unset($isi['opd_key'], $isi['berlaku_key']);

        $this->db->table('iku_revisi')->insert($isi);

        return (int) $this->db->insertID();
    }

    private function nomorBerikutnya(?int $opdId, int $tahunMulai, int $tahunAkhir): int
    {
        $row = $this->db->table('iku_revisi')
            ->selectMax('nomor', 'maks')
            ->where('opd_key', $opdId === null ? 0 : $opdId)
            ->where('tahun_mulai', $tahunMulai)
            ->where('tahun_akhir', $tahunAkhir)
            ->get()
            ->getRowArray();

        return ((int) ($row['maks'] ?? 0)) + 1;
    }

    /** @return array<int, array<int, array<string, mixed>>> [id arsip indikator => [tahun => baris]] */
    private function petaTargetRevisi(array $indikatorIds): array
    {
        if (empty($indikatorIds)) {
            return [];
        }

        $rows = $this->db->table('iku_revisi_target')
            ->whereIn('revisi_indikator_id', $indikatorIds)
            ->orderBy('tahun', 'ASC')
            ->get()
            ->getResultArray();

        $peta = [];
        foreach ($rows as $r) {
            $peta[(int) $r['revisi_indikator_id']][(int) $r['tahun']] = $r;
        }

        return $peta;
    }

    /** @return array<int, array<int, array<string, mixed>>> */
    private function petaProgramRevisi(array $indikatorIds): array
    {
        if (empty($indikatorIds)) {
            return [];
        }

        $rows = $this->db->table('iku_revisi_program')
            ->whereIn('revisi_indikator_id', $indikatorIds)
            ->orderBy('urutan', 'ASC')
            ->get()
            ->getResultArray();

        $peta = [];
        foreach ($rows as $r) {
            $peta[(int) $r['revisi_indikator_id']][] = $r;
        }

        return $peta;
    }

    private function kosongJadiNull($nilai): ?string
    {
        if ($nilai === null) {
            return null;
        }

        $nilai = trim((string) $nilai);

        return $nilai === '' ? null : $nilai;
    }
}
