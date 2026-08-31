<?php

namespace App\Commands;

use App\Models\Opd\IkuRevisiModel;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Database;
use Throwable;

/**
 * Uji IkuRevisiModel::ubahTahunBerlaku().
 *
 *   php spark iku:berlaku-check
 *
 * Mengubah tahun berlaku revisi yang SUDAH BERLAKU harus menjahit ulang
 * timeline (revisi sebelumnya ditutup di tahun-1), dan penolakan-penolakannya
 * harus jatuh di tempat yang benar. Semua fixture memakai lingkup Kabupaten
 * periode 2090-2094 yang mustahil dipakai data sungguhan, dan dibersihkan
 * kembali di akhir — sukses maupun gagal.
 */
class IkuBerlakuCheck extends BaseCommand
{
    protected $group       = 'SAKIP';
    protected $name        = 'iku:berlaku-check';
    protected $description = 'Uji ubah tahun berlaku revisi IKU: jahitan timeline & penolakannya.';

    private const TM = 2090;
    private const TA = 2094;

    private int $lulus = 0;
    private int $gagal = 0;

    private function cek(string $nama, bool $ok, string $detail = ''): void
    {
        if ($ok) {
            $this->lulus++;
            CLI::write('  ' . CLI::color('LULUS', 'green') . '  ' . $nama);
        } else {
            $this->gagal++;
            CLI::write('  ' . CLI::color('GAGAL', 'red') . '  ' . $nama . ($detail !== '' ? ' -> ' . $detail : ''));
        }
    }

    /** Sisipkan kepala revisi fixture; kolom generated dibiarkan dihitung DB. */
    private function fixture(object $db, int $nomor, string $status, int $mulai, ?int $sampai): int
    {
        $db->table('iku_revisi')->insert([
            'opd_id'               => null,
            'tahun_mulai'          => self::TM,
            'tahun_akhir'          => self::TA,
            'nomor'                => $nomor,
            'nama'                 => 'UJI-BERLAKU rev-' . $nomor,
            'berlaku_mulai_tahun'  => $mulai,
            'berlaku_sampai_tahun' => $sampai,
            'status'               => $status,
        ]);

        return (int) $db->connID->insert_id;
    }

    private function baris(object $db, int $id): array
    {
        return $db->table('iku_revisi')->where('id', $id)->get()->getRowArray();
    }

    /** Pesan penolakan diuji ADA-nya, bukan redaksinya. */
    private function ditolak(callable $aksi): bool
    {
        try {
            $aksi();

            return false;
        } catch (Throwable) {
            return true;
        }
    }

    public function run(array $params)
    {
        $db    = Database::connect();
        $model = new IkuRevisiModel();

        CLI::write('Basis data: ' . $db->getDatabase(), 'yellow');
        CLI::newLine();

        if (! $db->tableExists('iku_revisi')) {
            CLI::error('Tabel iku_revisi belum ada. Jalankan migrasi 2026-08-18 dulu.');

            return 1;
        }

        // Lingkup fixture harus kosong sebelum mulai.
        $db->table('iku_revisi')->where('tahun_mulai', self::TM)->where('tahun_akhir', self::TA)->delete();

        try {
            // Timeline awal: rev0 2090-2091 (superseded), rev1 2092-kini (berlaku),
            // plus satu draft usulan.
            $rev0  = $this->fixture($db, 0, 'superseded', self::TM, 2091);
            $rev1  = $this->fixture($db, 1, 'berlaku', 2092, null);
            $draft = $this->fixture($db, 2, 'draft', 2093, null);

            CLI::write('== Penolakan yang wajib ==', 'cyan');

            // Sejak tahun berlaku boleh dipilih bebas, hanya DUA hal yang
            // ditolak: tahun yang sudah dipayungi revisi lain, dan tahun di
            // luar periode. Tiga larangan lama (Kondisi Awal tak boleh
            // digeser, tahun pertama terlarang, tak boleh melompati tetangga)
            // sengaja dicabut — lihat ubahTahunBerlaku(); yang menjaga
            // kerapatan sekarang adalah jahitUlangTimeline().
            $this->cek('tahun yang sudah dipakai revisi lain ditolak',
                $this->ditolak(fn () => $model->ubahTahunBerlaku($rev1, self::TM))); // 2090 milik rev0

            $this->cek('tahun di luar periode ditolak',
                $this->ditolak(fn () => $model->ubahTahunBerlaku($rev1, self::TA + 1)));

            CLI::newLine();
            CLI::write('== Kondisi Awal kini BOLEH digeser ==', 'cyan');

            $hasil = $model->ubahTahunBerlaku($rev0, 2091);
            $this->cek('Kondisi Awal berhasil digeser',
                (int) $this->baris($db, $rev0)['berlaku_mulai_tahun'] === 2091);
            $this->cek('awal periode yang jadi tanpa payung DIPERINGATKAN',
                ! empty($hasil['peringatan']));

            $kembali = $model->ubahTahunBerlaku($rev0, self::TM);
            $this->cek('dikembalikan ke awal periode, peringatan hilang',
                empty($kembali['peringatan']));

            CLI::newLine();
            CLI::write('== Draft ==', 'cyan');

            $model->ubahTahunBerlaku($draft, 2094);
            $this->cek('tahun draft berubah', (int) $this->baris($db, $draft)['berlaku_mulai_tahun'] === 2094);
            $this->cek('draft tidak menyentuh timeline berlaku',
                (int) $this->baris($db, $rev0)['berlaku_sampai_tahun'] === 2091
                && $this->baris($db, $rev1)['berlaku_sampai_tahun'] === null);

            $this->cek('draft ditolak pindah ke tahun yang dipakai revisi berlaku',
                $this->ditolak(fn () => $model->ubahTahunBerlaku($draft, 2092)));

            CLI::newLine();
            CLI::write('== Revisi BERLAKU: timeline dijahit ulang ==', 'cyan');

            // Maju: 2092 -> 2093. rev0 harus memanjang sampai 2092.
            $hasil = $model->ubahTahunBerlaku($rev1, 2093);
            $this->cek('tahun berlaku maju tersimpan', (int) $this->baris($db, $rev1)['berlaku_mulai_tahun'] === 2093);
            $this->cek('revisi sebelumnya memanjang mengisi celah',
                (int) $this->baris($db, $rev0)['berlaku_sampai_tahun'] === 2092);
            $this->cek('id revisi yang digeser dilaporkan', $hasil['digeser'] === [$rev0]);

            // Mundur: 2093 -> 2091. rev0 harus memendek sampai 2090.
            $model->ubahTahunBerlaku($rev1, 2091);
            $this->cek('tahun berlaku mundur tersimpan', (int) $this->baris($db, $rev1)['berlaku_mulai_tahun'] === 2091);
            $this->cek('revisi sebelumnya memendek, tidak tumpang tindih',
                (int) $this->baris($db, $rev0)['berlaku_sampai_tahun'] === 2090);

            // Setiap tahun periode tetap dipayungi TEPAT satu revisi tak-batal.
            $rapat = true;
            foreach (range(self::TM, self::TA) as $th) {
                $n = $db->table('iku_revisi')
                    ->where('tahun_mulai', self::TM)->where('tahun_akhir', self::TA)
                    ->whereIn('status', ['berlaku', 'superseded'])
                    ->where('berlaku_mulai_tahun <=', $th)
                    ->groupStart()
                        ->where('berlaku_sampai_tahun IS NULL', null, false)
                        ->orWhere('berlaku_sampai_tahun >=', $th)
                    ->groupEnd()
                    ->countAllResults();

                if ($n !== 1) {
                    $rapat = false;
                    break;
                }
            }
            $this->cek('timeline rapat: tiap tahun dipayungi tepat satu revisi', $rapat);

            CLI::newLine();
            CLI::write('== Melompati tetangga kini BOLEH ==', 'cyan');

            // Keadaan sekarang: rev0 mulai 2090, rev1 mulai 2091.
            // rev0 dipindah ke 2093 — MELEWATI rev1. Dulu ditolak; kini
            // diizinkan, dan garis waktunya dijahit ulang dari nol sehingga
            // urutan payung mengikuti TAHUN, bukan nomor revisi.
            $model->ubahTahunBerlaku($rev0, 2093);

            $this->cek('revisi boleh melompati tetangganya',
                (int) $this->baris($db, $rev0)['berlaku_mulai_tahun'] === 2093);
            $this->cek('yang kini lebih awal ditutup tepat sebelum penerusnya',
                (int) $this->baris($db, $rev1)['berlaku_sampai_tahun'] === 2092);
            $this->cek('yang kini paling akhir dibiarkan terbuka',
                $this->baris($db, $rev0)['berlaku_sampai_tahun'] === null);

            CLI::newLine();
            CLI::write('== Status lain ==', 'cyan');

            $db->table('iku_revisi')->where('id', $draft)->update(['status' => 'menunggu']);
            $this->cek('revisi menunggu ditolak (tarik pengajuan dulu)',
                $this->ditolak(fn () => $model->ubahTahunBerlaku($draft, 2094)));
        } finally {
            $db->table('iku_revisi')->where('tahun_mulai', self::TM)->where('tahun_akhir', self::TA)->delete();
        }

        CLI::newLine();
        CLI::write('LULUS: ' . CLI::color((string) $this->lulus, 'green')
            . '   GAGAL: ' . CLI::color((string) $this->gagal, $this->gagal > 0 ? 'red' : 'green'));

        return $this->gagal > 0 ? 1 : 0;
    }
}
