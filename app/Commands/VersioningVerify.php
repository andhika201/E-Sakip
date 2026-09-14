<?php

namespace App\Commands;

use App\Models\DokumenVersiModel;
use App\Models\Versi\RenstraVersiModel;
use App\Models\Versi\RpjmdVersiModel;
use App\Services\Version\ArsipRegistry;
use App\Services\Version\LakipRevisionService;
use App\Services\Version\LakipSourceService;
use App\Services\Version\IzinSuntingService;
use App\Services\Version\VersionApprovalService;
use App\Services\Version\VersionAuditService;
use App\Services\Version\VersionConflictException;
use App\Services\Version\VersionCorrectionService;
use App\Services\Version\VersionDeepCopyService;
use App\Services\Version\VersionResolver;
use App\Services\Version\VersionScope;
use App\Services\Version\VersionTimelineService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Throwable;

/**
 * Acceptance test timeline & approval versi.
 *
 *   php spark versi:verify
 *   php spark versi:verify --db dv_test
 *
 * Menjalankan §60 (timeline retrospektif), §61 (multiple version satu tahun),
 * §62 (same date conflict), §65 (approval), dan §7 (badge dihitung) terhadap
 * DATA UJI pada periode 2090-2094, lalu membersihkannya sendiri.
 *
 * ---------------------------------------------------------------------
 * MENGAPA ADA OPSI --db
 *
 * Supaya seluruh berkas ini bisa dijalankan terhadap basis SALINAN sebelum
 * menyentuh basis kerja. Versioning menulis ke tabel yang dibaca banyak modul;
 * menguji langsung di basis kerja hanya karena itu yang tertulis di .env adalah
 * risiko yang tidak perlu diambil.
 *
 * ---------------------------------------------------------------------
 * MENGAPA TIDAK DIBUNGKUS SATU TRANSAKSI LALU ROLLBACK
 *
 * Karena yang diuji ADALAH transaksinya. VersionApprovalService memakai
 * TransaksiAman yang sengaja MENOLAK berjalan di dalam transaksi lain —
 * membungkus uji ini dalam transaksi akan membuat seluruhnya gagal dengan
 * alasan yang justru benar. Pembersihannya karena itu eksplisit di blok akhir.
 * ---------------------------------------------------------------------
 */
class VersioningVerify extends BaseCommand
{
    protected $group       = 'SAKIP';
    protected $name        = 'versi:verify';
    protected $description = 'Uji §60-§65: timeline retrospektif, multi-versi setahun, konflik tanggal, approval, badge.';
    protected $usage       = 'versi:verify [--db <nama_basis>]';
    protected $options     = ['--db' => 'Nama basis data lain (mis. salinan uji). Kosong = sesuai .env'];

    private const MULAI = 2090;
    private const AKHIR = 2094;

    private $db;
    private DokumenVersiModel $model;
    private VersionResolver $resolver;
    private VersionTimelineService $timeline;
    private VersionApprovalService $approval;
    private VersionAuditService $audit;

    private int $lulus = 0;
    private int $gagal = 0;

    public function run(array $params)
    {
        $namaDb = $params['db'] ?? CLI::getOption('db');

        try {
            $this->db = $this->koneksi($namaDb);
        } catch (Throwable $e) {
            CLI::error('Gagal terhubung ke basis data: ' . $e->getMessage());

            return 1;
        }

        CLI::write('Basis data: ' . CLI::color($this->db->getDatabase(), 'yellow'));
        CLI::newLine();

        $this->model    = new DokumenVersiModel($this->db);
        $this->audit    = new VersionAuditService($this->db);
        $this->timeline = new VersionTimelineService($this->db, $this->model, $this->audit);
        $this->approval = new VersionApprovalService($this->db, $this->model, $this->timeline, $this->audit);
        $this->resolver = new VersionResolver($this->db, $this->model);

        if (! $this->model->siap()) {
            CLI::error('Tabel dokumen_versi belum ada.');
            CLI::write('Jalankan dulu, berurutan:');
            CLI::write('  db/update_2026-08-18_iku_revisi_lakip_snapshot.sql');
            CLI::write('  db/update_2026-08-20_versioning_dokumen.sql');

            return 1;
        }

        $this->bersihkan();

        try {
            $this->case60();
            $this->case61();
            $this->case62();
            $this->case65();
            $this->caseBadge();
            $this->caseDraftBukanSumber();
            $this->case63();
            $this->casePensiun();
            $this->caseRenstra();
            $this->caseLakip();
            $this->caseVerifikasi();
            $this->caseSunting();
            $this->caseUbahTanggal();
            $this->caseKoreksi();
            $this->caseSiklusRenstra();
            $this->caseFormVersiRenstra();
            $this->caseBacaVersiRenstra();
            $this->caseTampilanUtama();
            $this->caseIzinSunting();
            $this->caseGeserTanggalResmi();
            $this->casePenguncianRenstra();
            $this->caseSyncIkuDariVersi();
            $this->caseVerifikasiRevisiIku();
            $this->caseSyncKeDraftRevisi();
            $this->caseSelisihIkuRenstra();
            $this->casePraTinjauRevisiIku();
            $this->caseAjukanPengesahanIku();
            $this->caseKeteranganIku();
            $this->casePeriodeIku();
            $this->caseSumberLakipIku();
            $this->casePensiunKonsisten();
            $this->caseLakipDariIku();
        } catch (Throwable $e) {
            $this->gagal++;
            CLI::error('GALAT TAK TERDUGA: ' . $e->getMessage());
            CLI::write($e->getFile() . ':' . $e->getLine());
        } finally {
            $this->bersihkan();
        }

        CLI::newLine();
        CLI::write(
            'LULUS: ' . CLI::color((string) $this->lulus, 'green')
            . '   GAGAL: ' . CLI::color((string) $this->gagal, $this->gagal > 0 ? 'red' : 'green')
        );

        return $this->gagal > 0 ? 1 : 0;
    }

    /* =========================================================
     * §60 — TIMELINE RETROSPEKTIF
     * =======================================================*/
    private function case60(): void
    {
        CLI::write(CLI::color('§60 — Timeline retrospektif', 'cyan'));

        $scope = VersionScope::rpjmd(self::MULAI, self::AKHIR);

        $v1 = $this->buatDraft($scope, 1, 'V1', '2090-01-01');
        $this->terbitkan($v1);

        $v2 = $this->buatDraft($scope, 2, 'V2', '2093-01-01');
        $this->terbitkan($v2);

        // V3 dibuat BELAKANGAN tetapi berlaku di TENGAH histori.
        $v3 = $this->buatDraft($scope, 3, 'V3', '2091-07-01');
        $this->terbitkan($v3);

        $t = $this->timelineTeks($scope);

        $this->cek(
            'V1 2090-01-01 -> 2091-07-01',
            $t[$v1] === '2090-01-01..2091-07-01'
        );
        $this->cek(
            'V3 2091-07-01 -> 2093-01-01 (disisipkan ke tengah)',
            $t[$v3] === '2091-07-01..2093-01-01'
        );
        $this->cek(
            'V2 2093-01-01 -> terbuka',
            $t[$v2] === '2093-01-01..NULL'
        );

        // §3: "Jangan renumber version lama hanya karena version historis disisipkan."
        $nomor = [];
        foreach ($this->model->daftar($scope) as $r) {
            $nomor[(int) $r['id']] = (int) $r['version_no'];
        }
        $this->cek(
            'version_no tidak di-renumber (V1=1, V2=2, V3=3)',
            $nomor[$v1] === 1 && $nomor[$v2] === 2 && $nomor[$v3] === 3
        );

        // Invariant engine: tetap tepat satu versi terbuka.
        $this->cek('tepat satu versi terbuka', $this->jumlahTerbuka($scope) === 1);

        $this->hapusLingkup($scope);
    }

    /* =========================================================
     * §61 — MULTIPLE VERSION DALAM SATU TAHUN
     * =======================================================*/
    private function case61(): void
    {
        CLI::write(CLI::color('§61 — Multiple version dalam satu tahun', 'cyan'));

        $scope = VersionScope::renstra(999, self::MULAI, self::AKHIR);

        $this->terbitkan($this->buatDraft($scope, 1, 'V1', '2091-01-01'));
        $this->terbitkan($this->buatDraft($scope, 2, 'V2', '2091-03-15'));
        $this->terbitkan($this->buatDraft($scope, 3, 'V3', '2091-09-01'));

        $this->cek('resolver 2091-02-01 -> V1', $this->labelPada($scope, '2091-02-01') === 'V1');
        $this->cek('resolver 2091-05-01 -> V2', $this->labelPada($scope, '2091-05-01') === 'V2');
        $this->cek('resolver 2091-12-31 -> V3', $this->labelPada($scope, '2091-12-31') === 'V3');

        // Batas interval: tanggal pergantian dimiliki versi yang MULAI, bukan
        // yang berakhir. Inilah alasan intervalnya setengah terbuka.
        $this->cek('batas 2091-03-15 -> V2 (bukan ambigu)', $this->labelPada($scope, '2091-03-15') === 'V2');
        $this->cek('batas 2091-09-01 -> V3 (bukan ambigu)', $this->labelPada($scope, '2091-09-01') === 'V3');

        // Sebelum versi pertama berlaku: belum ada rujukan, dan itu BUKAN galat.
        $this->cek('sebelum V1 -> null', $this->labelPada($scope, '2090-06-01') === null);

        $this->hapusLingkup($scope);
    }

    /* =========================================================
     * §62 — SAME DATE CONFLICT
     * =======================================================*/
    private function case62(): void
    {
        CLI::write(CLI::color('§62 — Same date conflict', 'cyan'));

        $scope = VersionScope::iku(null, self::MULAI, self::AKHIR);

        $this->terbitkan($this->buatDraft($scope, 1, 'V1', '2091-01-01'));
        $v2 = $this->buatDraft($scope, 2, 'V2 tabrakan', '2091-01-01');

        // Ditolak lapisan aplikasi dengan pesan yang menjelaskan.
        $this->cekMelempar('publish kedua di tanggal sama DITOLAK aplikasi', function () use ($v2) {
            $this->approval->setujui($v2, null, true);
        });

        // Dan ditolak ENGINE juga, walau aplikasi dilewati sama sekali.
        $ditolakEngine = false;

        try {
            $this->db->table('dokumen_versi')->insert(array_merge($scope->kolomBaru(), [
                'version_no'     => 99,
                'label'          => 'V99 lewat belakang',
                'effective_from' => '2091-01-01',
                'status'         => DokumenVersiModel::STATUS_PUBLISHED,
                'created_at'     => date('Y-m-d H:i:s'),
            ]));
        } catch (Throwable $e) {
            $ditolakEngine = str_contains($e->getMessage(), '1062')
                || stripos($e->getMessage(), 'duplicate') !== false;
        }

        $this->cek('publish kedua di tanggal sama DITOLAK engine (1062)', $ditolakEngine);

        // Status versi yang gagal terbit tidak boleh ikut berubah.
        $sisa = $this->model->ambil($v2);
        $this->cek(
            'versi yang gagal terbit tetap draft (tidak setengah jadi)',
            $sisa !== null && $sisa['status'] === DokumenVersiModel::STATUS_DRAFT
        );

        $this->hapusLingkup($scope);
    }

    /* =========================================================
     * §65 — APPROVAL
     * =======================================================*/
    private function case65(): void
    {
        CLI::write(CLI::color('§65 — Approval OPD -> Admin Kabupaten', 'cyan'));

        $scope = VersionScope::renstra(998, self::MULAI, self::AKHIR);
        $id    = $this->buatDraft($scope, 1, 'V1 Renstra OPD', '2090-01-01');

        $this->approval->ajukan($id, 11);
        $this->cek('draft -> pending_approval', $this->status($id) === DokumenVersiModel::STATUS_PENDING);

        // Saat menunggu verifikasi, pemilik tidak boleh menyunting.
        $this->cek('pending: bolehSunting = false', ! $this->approval->bolehSunting($this->model->ambil($id)));

        // Kembalikan tanpa catatan harus ditolak.
        $this->cekMelempar('kembalikan tanpa catatan DITOLAK', function () use ($id) {
            $this->approval->kembalikan($id, '   ', 22);
        });

        $this->approval->kembalikan($id, 'Target tahun 2092 belum diisi.', 22);
        $this->cek('pending -> draft (dikembalikan)', $this->status($id) === DokumenVersiModel::STATUS_DRAFT);

        $this->approval->ajukan($id, 11);
        $this->approval->setujui($id, 22);
        $this->cek('pending -> published', $this->status($id) === DokumenVersiModel::STATUS_PUBLISHED);

        // §16: published immutable terhadap sunting normal.
        $terbit = $this->model->ambil($id);
        $this->cek('published: bolehSunting = false', ! $this->approval->bolehSunting($terbit));
        $this->cek('published: bolehKoreksi = true', $this->approval->bolehKoreksi($terbit));

        $this->cekMelempar('membatalkan versi published DITOLAK', function () use ($id) {
            $this->approval->batalkan($id, 'coba tarik');
        });

        // Riwayat harus bercerita, termasuk pengajuan ulang.
        $aksi = array_column($this->audit->riwayat($id), 'aksi');
        $this->cek(
            'riwayat memuat submitted, returned, resubmitted, published',
            in_array(VersionAuditService::AKSI_SUBMITTED, $aksi, true)
            && in_array(VersionAuditService::AKSI_RETURNED, $aksi, true)
            && in_array(VersionAuditService::AKSI_RESUBMITTED, $aksi, true)
            && in_array(VersionAuditService::AKSI_PUBLISHED, $aksi, true)
        );

        $catatan = '';
        foreach ($this->audit->riwayat($id) as $r) {
            if (($r['aksi'] ?? '') === VersionAuditService::AKSI_RETURNED) {
                $catatan = (string) ($r['catatan'] ?? '');
            }
        }
        $this->cek('catatan pengembalian tersimpan', str_contains($catatan, 'Target tahun 2092'));

        // §2.4: versi berjejak tidak boleh terhapus — dijamin FK RESTRICT.
        $ditolak = false;

        try {
            $this->db->table('dokumen_versi')->where('id', $id)->delete();
        } catch (Throwable $e) {
            $ditolak = true;
        }
        $this->cek('hapus versi berjejak DITOLAK engine (RESTRICT)', $ditolak);

        $this->hapusLingkup($scope);
    }

    /* =========================================================
     * §7 — BADGE DIHITUNG, BUKAN DISIMPAN
     * =======================================================*/
    private function caseBadge(): void
    {
        CLI::write(CLI::color('§7 — Badge dihitung dari timeline', 'cyan'));

        $lampau  = ['status' => 'published', 'effective_from' => '2000-01-01', 'effective_to' => '2001-01-01'];
        $kini    = ['status' => 'published', 'effective_from' => '2000-01-01', 'effective_to' => null];
        $depan   = ['status' => 'published', 'effective_from' => '2999-01-01', 'effective_to' => null];
        $draft   = ['status' => 'draft',     'effective_from' => '2000-01-01', 'effective_to' => null];
        $pending = ['status' => 'pending_approval', 'effective_from' => '2000-01-01', 'effective_to' => null];

        $this->cek('published + sudah lewat  -> HISTORICAL', $this->resolver->badge($lampau) === 'HISTORICAL');
        $this->cek('published + dalam range  -> CURRENT',    $this->resolver->badge($kini) === 'CURRENT');
        $this->cek('published + belum mulai  -> UPCOMING',   $this->resolver->badge($depan) === 'UPCOMING');
        $this->cek('draft                    -> DRAFT',      $this->resolver->badge($draft) === 'DRAFT');
        $this->cek('pending -> MENUNGGU VERIFIKASI', $this->resolver->badge($pending) === 'MENUNGGU VERIFIKASI');

        // effective_to EKSKLUSIF: hari terakhir berlaku = sehari sebelumnya.
        $this->cek(
            'rentang teks memakai hari terakhir, bukan tanggal eksklusif',
            str_contains($this->resolver->rentangTeks($lampau), '31 Des 2000')
        );
    }

    /* =========================================================
     * §2.11 — DRAFT TIDAK PERNAH JADI SUMBER
     * =======================================================*/
    private function caseDraftBukanSumber(): void
    {
        CLI::write(CLI::color('§2.11 — Draft & pending bukan sumber resmi', 'cyan'));

        $scope = VersionScope::iku(997, self::MULAI, self::AKHIR);

        $this->buatDraft($scope, 1, 'V1 draft', '2090-01-01');
        $this->cek('draft tidak terpilih resolver', $this->labelPada($scope, '2091-06-01') === null);

        $pending = $this->buatDraft($scope, 2, 'V2 pending', '2090-06-01');
        $this->approval->ajukan($pending, 11);
        $this->cek('pending tidak terpilih resolver', $this->labelPada($scope, '2091-06-01') === null);

        $this->cek(
            'dropdown sumber tidak memuat draft/pending',
            $this->resolver->pilihanSumber('iku', 'opd', 997, 2091) === []
        );

        $this->approval->setujui($pending, 22);
        $this->cek('setelah published baru terpilih', $this->labelPada($scope, '2091-06-01') === 'V2 pending');

        $this->hapusLingkup($scope);
    }

    /* =========================================================
     * §63 — DEEP COPY
     * =======================================================*/
    private function case63(): void
    {
        CLI::write(CLI::color('§63 — Deep copy RPJMD', 'cyan'));

        $scope = VersionScope::rpjmd(self::MULAI, self::AKHIR);
        $this->siapkanRpjmdUji();

        $copy  = new VersionDeepCopyService($this->db, $this->model, new ArsipRegistry($this->db), $this->audit);
        $arsip = new RpjmdVersiModel($this->db);

        $v1 = $copy->buatVersi($scope, ['label' => 'V1 beku', 'effective_from' => '2090-01-01']);
        $this->cek(
            'bekukanDariLive menyalin 1 misi / 1 tujuan / 2 sasaran / 2 indikator',
            ($v1['ringkasan']['misi'] ?? 0) === 1
            && ($v1['ringkasan']['tujuan'] ?? 0) === 1
            && ($v1['ringkasan']['sasaran'] ?? 0) === 2
            && ($v1['ringkasan']['indikator_sasaran'] ?? 0) === 2
        );
        $this->cek('target ikut terbekukan', ($v1['ringkasan']['target'] ?? 0) === 4);

        $this->approval->setujui($v1['version_id'], 1, true);

        $v2 = $copy->buatVersi($scope, [
            'label'                  => 'V2 salinan',
            'effective_from'         => '2092-01-01',
            'sumber'                 => VersionDeepCopyService::SUMBER_COPY,
            'copied_from_version_id' => $v1['version_id'],
        ]);

        $isi1 = $arsip->isi($v1['version_id']);
        $isi2 = $arsip->isi($v2['version_id']);

        $id1 = $this->kumpulkanIdArsip($isi1);
        $id2 = $this->kumpulkanIdArsip($isi2);

        $this->cek('salinan punya ID BARU seluruhnya', array_intersect($id1, $id2) === []);
        $this->cek('jumlah entitas identik', count($id1) === count($id2) && count($id1) > 0);
        $this->cek(
            'hierarki terjaga (1 misi > 1 tujuan > 2 sasaran)',
            count($isi2) === 1
            && count($isi2[0]['tujuan']) === 1
            && count($isi2[0]['tujuan'][0]['sasaran']) === 2
        );

        // §11 — lineage tersimpan, tidak mengandalkan pencocokan nama.
        $misi2 = $isi2[0];
        $this->cek('copied_from_id terisi pada salinan', (int) $misi2['copied_from_id'] === (int) $isi1[0]['id']);
        $this->cek(
            'copied_from_version_id terisi pada kepala versi',
            (int) ($this->model->ambil($v2['version_id'])['copied_from_version_id'] ?? 0) === $v1['version_id']
        );

        // §10 — menyunting salinan tidak boleh menyentuh versi asal.
        $this->db->table('rpjmd_versi_sasaran')
            ->where('id', (int) $isi2[0]['tujuan'][0]['sasaran'][0]['id'])
            ->update(['sasaran_rpjmd' => 'DIUBAH DI V2']);

        $isi1Lagi = $arsip->isi($v1['version_id']);
        $this->cek(
            'menyunting V2 tidak mengubah V1',
            $isi1Lagi[0]['tujuan'][0]['sasaran'][0]['sasaran_rpjmd']
                === $isi1[0]['tujuan'][0]['sasaran'][0]['sasaran_rpjmd']
        );

        // source_*_id ikut disalin — tanpa itu penerapan V2 akan menyisipkan
        // baris live kembar alih-alih memperbarui.
        $this->cek(
            'source_sasaran_id ikut disalin (penerapan tidak menduplikasi live)',
            ! empty($isi2[0]['tujuan'][0]['sasaran'][0]['source_sasaran_id'])
        );

        $this->cekMelempar('menyalin dari versi DRAFT ditolak', function () use ($copy, $scope) {
            $draft = $copy->buatVersi($scope, ['label' => 'draft', 'effective_from' => '2093-01-01']);
            $copy->buatVersi($scope, [
                'label'                  => 'salin dari draft',
                'effective_from'         => '2094-01-01',
                'sumber'                 => VersionDeepCopyService::SUMBER_COPY,
                'copied_from_version_id' => $draft['version_id'],
            ]);
        });

        $this->hapusLingkup($scope);
        $this->bersihkanRpjmdUji();
    }

    /* =========================================================
     * PENSIUN, BUKAN HAPUS — penjaga temuan T5
     * =======================================================*/
    private function casePensiun(): void
    {
        CLI::write(CLI::color('Pensiun bukan hapus (temuan T5)', 'cyan'));

        $scope = VersionScope::rpjmd(self::MULAI, self::AKHIR);
        [$sasaranA, $sasaranB] = $this->siapkanRpjmdUji();

        // Renstra OPD lain menggantung pada sasaran yang akan dibuang V2.
        $renstraTujuanId = $this->db->table('renstra_tujuan')->insert([
            'rpjmd_sasaran_id' => $sasaranB,
            'tujuan'           => 'UJI-VERSI tujuan renstra',
            'created_at'       => date('Y-m-d H:i:s'),
            'updated_at'       => date('Y-m-d H:i:s'),
        ]) ? (int) $this->db->insertID() : 0;

        $copy  = new VersionDeepCopyService($this->db, $this->model, new ArsipRegistry($this->db), $this->audit);
        $arsip = new RpjmdVersiModel($this->db);

        $v1 = $copy->buatVersi($scope, ['label' => 'V1', 'effective_from' => '2090-01-01']);
        $this->approval->setujui($v1['version_id'], 1, true);

        $v2 = $copy->buatVersi($scope, [
            'label'                  => 'V2 tanpa sasaran B',
            'effective_from'         => '2092-01-01',
            'sumber'                 => VersionDeepCopyService::SUMBER_COPY,
            'copied_from_version_id' => $v1['version_id'],
        ]);

        // Buang sasaran B dari ARSIP V2 — meniru operator yang menghapusnya di form.
        $isi2 = $arsip->isi($v2['version_id']);

        foreach ($isi2[0]['tujuan'][0]['sasaran'] as $s) {
            if ((int) $s['source_sasaran_id'] === $sasaranB) {
                $this->db->table('rpjmd_versi_sasaran')->where('id', (int) $s['id'])->delete();
            }
        }

        $this->approval->setujui($v2['version_id'], 1, true);

        $barisB = $this->db->table('rpjmd_sasaran')->where('id', $sasaranB)->get()->getRowArray();

        $this->cek('sasaran yang dibuang TIDAK dihapus dari live', $barisB !== null);
        $this->cek('sasaran dipensiunkan (dihentikan_pada terisi)', ! empty($barisB['dihentikan_pada']));
        $this->cek(
            'berlaku_sampai = tahun sebelum versi baru mulai (2091)',
            (int) ($barisB['berlaku_sampai'] ?? 0) === 2091
        );

        // INI INTINYA: rpjmd_sasaran -> renstra_tujuan ber-CASCADE. Kalau
        // penerapan versi menghapus alih-alih memensiunkan, baris Renstra ini
        // akan ikut lenyap tanpa jejak — dan di data nyata itu berarti Renstra
        // 38 OPD.
        $renstraSelamat = $this->db->table('renstra_tujuan')
            ->where('id', $renstraTujuanId)->countAllResults() > 0;
        $this->cek('Renstra yang menggantung padanya SELAMAT (T5)', $renstraSelamat);

        // Sasaran yang masih tercantum tidak boleh ikut terpensiun.
        $barisA = $this->db->table('rpjmd_sasaran')->where('id', $sasaranA)->get()->getRowArray();
        $this->cek('sasaran yang masih tercantum tetap hidup', empty($barisA['dihentikan_pada']));
        $this->cek('sasaran yang masih tercantum ditandai versi V2', (int) $barisA['version_id'] === $v2['version_id']);

        // Penerapan memperbarui baris live yang sama, bukan menyisipkan kembar.
        $jml = (int) $this->db->table('rpjmd_sasaran')
            ->like('sasaran_rpjmd', 'UJI-VERSI', 'after')->countAllResults();
        $this->cek('tidak ada baris live kembar setelah dua kali penerapan', $jml === 2);

        $this->db->table('renstra_tujuan')->where('id', $renstraTujuanId)->delete();
        $this->hapusLingkup($scope);
        $this->bersihkanRpjmdUji();
    }

    /* =========================================================
     * SIKLUS HIDUP RENSTRA BERJALAN (= Versi 1)
     * =======================================================*/
    private function caseSiklusRenstra(): void
    {
        CLI::write(CLI::color('Siklus hidup Renstra berjalan (= V1)', 'cyan'));

        $opd   = $this->opdUji('UJI-VERSI OPD Siklus');
        [, $rs] = $this->siapkanRpjmdUji();
        $tuj   = $this->sisipUji('renstra_tujuan', [
            'rpjmd_sasaran_id' => $rs, 'tujuan' => 'UJI-VERSI tujuan siklus',
        ]);
        $this->sisipRenstraSasaran($opd, $tuj, 'UJI-VERSI sasaran siklus');

        $scope = VersionScope::renstra($opd, self::MULAI, self::AKHIR);
        $arsip = new RenstraVersiModel($this->db);
        $copy  = new VersionDeepCopyService($this->db, $this->model, new ArsipRegistry($this->db), $this->audit);

        // --- belum ada versi: bebas disunting ---
        $this->cek('belum ada versi -> Renstra bebas disunting', $this->model->daftar($scope) === []);

        // --- ajukan: bekukan live lalu pending ---
        $vId = $this->model->sisipkan(array_merge($scope->kolomBaru(), [
            'version_no' => 1, 'label' => 'V1 — Renstra uji',
            'effective_from' => self::MULAI . '-01-01',
            'status' => DokumenVersiModel::STATUS_DRAFT, 'created_by' => 11,
        ]));

        $ringkas = $this->dalamTransaksiUji(fn () => $arsip->bekukanDariLive($vId, $scope));
        $this->cek('isi Renstra dibekukan ke arsip V1 saat diajukan',
            ($ringkas['sasaran'] ?? 0) === 1 && ($ringkas['indikator_sasaran'] ?? 0) === 1);

        $this->approval->ajukan($vId, 11);
        $this->cek('status menjadi menunggu verifikasi',
            $this->status($vId) === DokumenVersiModel::STATUS_PENDING);

        // --- tarik permohonan oleh penyusun sendiri ---
        $this->approval->tarikPengajuan($vId, 11);
        $this->cek('permohonan bisa ditarik penyusun',
            $this->status($vId) === DokumenVersiModel::STATUS_DRAFT);

        $ditarik = false;
        foreach ($this->audit->riwayat($vId) as $h) {
            if (str_contains((string) ($h['ringkasan'] ?? ''), 'ditarik oleh penyusun')) {
                $ditarik = true;
            }
        }
        $this->cek('penarikan tercatat pada jejak audit', $ditarik);

        $this->cekMelempar('menarik permohonan yang tidak menggantung DITOLAK', function () use ($vId) {
            $this->approval->tarikPengajuan($vId, 11);
        });

        // --- ajukan ulang lalu disetujui ---
        $this->approval->ajukan($vId, 11);
        $this->approval->setujui($vId, 22);
        $this->cek('setelah disetujui menjadi published',
            $this->status($vId) === DokumenVersiModel::STATUS_PUBLISHED);

        // Penerapan arsip ke live tidak boleh memensiunkan apa pun: isinya
        // memang berasal dari live yang sama.
        $sasaranHidup = (int) $this->db->table('renstra_sasaran')
            ->where('opd_id', $opd)->where('dihentikan_pada IS NULL', null, false)
            ->countAllResults();
        $this->cek('penetapan V1 tidak memensiunkan Renstra sendiri', $sasaranHidup === 1);

        // --- versi berikutnya: V2 dari salinan V1 ---
        $v2 = $copy->buatVersi($scope, [
            'label' => 'V2 — perubahan kebijakan',
            'effective_from' => (self::MULAI + 1) . '-01-01',
            'sumber' => VersionDeepCopyService::SUMBER_COPY,
            'copied_from_version_id' => $vId,
            'created_by' => 11,
        ]);
        $this->cek('V2 bisa dibuat menyalin V1', ($v2['ringkasan']['sasaran'] ?? 0) === 1);
        $this->cek('nomor versi berlanjut, bukan mengulang',
            (int) $this->model->ambil($v2['version_id'])['version_no'] === 2);

        // V1 tetap utuh setelah V2 dibuat — inilah nilai historisnya.
        $this->cek('V1 tetap punya isinya sendiri',
            ($arsip->ringkas($vId)['sasaran'] ?? 0) === 1);

        $this->hapusLingkup($scope);
        $this->bersihkanRenstraUji();
        $this->bersihkanRpjmdUji();
    }

    /* =========================================================
     * ARSIP RENSTRA — pembekuan MENDAKI dari sasaran ke tujuan
     * =======================================================*/
    private function caseRenstra(): void
    {
        CLI::write(CLI::color('Arsip Renstra (lingkup opd + periode)', 'cyan'));

        $opdA  = $this->opdUji('UJI-VERSI OPD A');
        $opdB  = $this->opdUji('UJI-VERSI OPD B');
        [, $rpjmdSasaran] = $this->siapkanRpjmdUji();

        // Satu tujuan dipakai DUA OPD — inilah keadaan yang membuat
        // renstra_tujuan berbahaya untuk dipensiunkan sepihak (temuan T4).
        $tujuanId = $this->sisipUji('renstra_tujuan', [
            'rpjmd_sasaran_id' => $rpjmdSasaran,
            'tujuan'           => 'UJI-VERSI tujuan bersama',
        ]);

        $sasaranA = $this->sisipRenstraSasaran($opdA, $tujuanId, 'UJI-VERSI sasaran OPD A');
        $sasaranB = $this->sisipRenstraSasaran($opdB, $tujuanId, 'UJI-VERSI sasaran OPD B');

        $scopeA = VersionScope::renstra($opdA, self::MULAI, self::AKHIR);
        $copy   = new VersionDeepCopyService($this->db, $this->model, new ArsipRegistry($this->db), $this->audit);
        $arsip  = new RenstraVersiModel($this->db);

        $v1 = $copy->buatVersi($scopeA, ['label' => 'V1 Renstra A', 'effective_from' => '2090-01-01']);

        $this->cek(
            'pembekuan mendaki: 1 tujuan ditemukan dari sasaran',
            ($v1['ringkasan']['tujuan'] ?? 0) === 1
        );
        $this->cek(
            'HANYA sasaran OPD A yang ikut, bukan OPD B',
            ($v1['ringkasan']['sasaran'] ?? 0) === 1
        );

        $isi = $arsip->isi($v1['version_id']);
        $this->cek(
            'teks rpjmd_sasaran induk ikut dibekukan',
            ! empty($isi[0]['rpjmd_sasaran_teks'])
        );
        $this->cek(
            'satuan diterjemahkan saat dibekukan',
            ($isi[0]['sasaran'][0]['indikator'][0]['satuan_nama'] ?? null) !== null
        );

        $this->approval->setujui($v1['version_id'], 1, true);

        // Versi baru yang membuang seluruh sasaran OPD A.
        $v2 = $copy->buatVersi($scopeA, [
            'label'                  => 'V2 kosong',
            'effective_from'         => '2092-01-01',
            'sumber'                 => VersionDeepCopyService::SUMBER_KOSONG,
        ]);
        $this->approval->setujui($v2['version_id'], 1, true);

        $barisA = $this->db->table('renstra_sasaran')->where('id', $sasaranA)->get()->getRowArray();
        $barisB = $this->db->table('renstra_sasaran')->where('id', $sasaranB)->get()->getRowArray();

        $this->cek('sasaran OPD A dipensiunkan', ! empty($barisA['dihentikan_pada']));
        $this->cek('sasaran OPD B TIDAK tersentuh (lingkup terjaga)', empty($barisB['dihentikan_pada']));

        // Tujuan masih dipakai OPD B, jadi TIDAK boleh ikut dipensiunkan.
        $tujuan = $this->db->table('renstra_tujuan')->where('id', $tujuanId)->get()->getRowArray();
        $this->cek(
            'tujuan yang masih dipakai OPD lain TIDAK dipensiunkan (T4)',
            empty($tujuan['dihentikan_pada'])
        );

        // Sekarang OPD B ikut dikosongkan -> tujuan menjadi yatim dan boleh pensiun.
        $scopeB = VersionScope::renstra($opdB, self::MULAI, self::AKHIR);
        $b1 = $copy->buatVersi($scopeB, ['label' => 'V1 Renstra B', 'effective_from' => '2090-01-01']);
        $this->approval->setujui($b1['version_id'], 1, true);
        $b2 = $copy->buatVersi($scopeB, [
            'label'          => 'V2 kosong',
            'effective_from' => '2092-01-01',
            'sumber'         => VersionDeepCopyService::SUMBER_KOSONG,
        ]);
        $this->approval->setujui($b2['version_id'], 1, true);

        $tujuan = $this->db->table('renstra_tujuan')->where('id', $tujuanId)->get()->getRowArray();
        $this->cek(
            'tujuan dipensiunkan setelah kehilangan SELURUH sasaran',
            ! empty($tujuan['dihentikan_pada'])
        );
        $this->cek('tujuan tidak dihapus, hanya dipensiunkan', $tujuan !== null);

        $this->hapusLingkup($scopeA);
        $this->hapusLingkup($scopeB);
        $this->bersihkanRenstraUji();
        $this->bersihkanRpjmdUji();
    }

    /* =========================================================
     * §68–§72 — SUMBER & REVISI LAKIP
     * =======================================================*/
    private function caseLakip(): void
    {
        CLI::write(CLI::color('§68-§72 — Sumber & revisi LAKIP', 'cyan'));

        $opd = $this->opdUji('UJI-VERSI OPD LAKIP');
        [, $rpjmdSasaran] = $this->siapkanRpjmdUji();

        $tujuanId = $this->sisipUji('renstra_tujuan', [
            'rpjmd_sasaran_id' => $rpjmdSasaran,
            'tujuan'           => 'UJI-VERSI tujuan lakip',
        ]);
        $this->sisipRenstraSasaran($opd, $tujuanId, 'UJI-VERSI sasaran lakip');

        $scopeRenstra = VersionScope::renstra($opd, self::MULAI, self::AKHIR);
        $copy   = new VersionDeepCopyService($this->db, $this->model, new ArsipRegistry($this->db), $this->audit);
        $sumber = new LakipSourceService($this->db, $this->resolver, $this->model);
        $revisi = new LakipRevisionService($this->db, $this->model, null, $sumber, null, $this->audit);

        // Dua versi Renstra: V1 mulai 2090, V2 mulai 2092.
        $rv1 = $copy->buatVersi($scopeRenstra, ['label' => 'Renstra V1', 'effective_from' => '2090-01-01']);
        $this->approval->setujui($rv1['version_id'], 1, true);

        $rv2 = $copy->buatVersi($scopeRenstra, [
            'label'                  => 'Renstra V2',
            'effective_from'         => '2092-01-01',
            'sumber'                 => VersionDeepCopyService::SUMBER_COPY,
            'copied_from_version_id' => $rv1['version_id'],
        ]);
        $this->approval->setujui($rv2['version_id'], 1, true);

        // §24 — sumber bawaan IKU, alternatif Renstra untuk OPD.
        $pilihan = $sumber->pilihanSumber(LakipSourceService::MODE_OPD, $opd, self::MULAI + 1);
        $this->cek('sumber bawaan LAKIP OPD = IKU (§24)', ($pilihan[0]['nilai'] ?? '') === 'iku' && $pilihan[0]['bawaan']);
        $this->cek('alternatif LAKIP OPD = Renstra', ($pilihan[1]['nilai'] ?? '') === 'renstra');

        $this->cekMelempar('RPJMD ditolak sebagai sumber LAKIP OPD (§25)', static function () use ($sumber) {
            $sumber->sumberSah('rpjmd', LakipSourceService::MODE_OPD);
        });

        // §26 — rekomendasi berdasarkan 31 Desember tahun laporan.
        $rek91 = $sumber->rekomendasi('renstra', LakipSourceService::MODE_OPD, $opd, 2091);
        $rek93 = $sumber->rekomendasi('renstra', LakipSourceService::MODE_OPD, $opd, 2093);
        $this->cek('LAKIP 2091 -> rekomendasi Renstra V1', (int) ($rek91['id'] ?? 0) === $rv1['version_id']);
        $this->cek('LAKIP 2093 -> rekomendasi Renstra V2', (int) ($rek93['id'] ?? 0) === $rv2['version_id']);

        // §27 — memilih versi selain rekomendasi wajib beralasan.
        $tanpaAlasan = $sumber->validasiPilihan('renstra', $rv2['version_id'], LakipSourceService::MODE_OPD, $opd, 2091);
        $this->cek('versi bukan rekomendasi TANPA alasan ditolak (§27)', $tanpaAlasan['galat'] !== []);

        $denganAlasan = $sumber->validasiPilihan(
            'renstra', $rv2['version_id'], LakipSourceService::MODE_OPD, $opd, 2091, 'Perubahan kebijakan bupati'
        );
        $this->cek('versi bukan rekomendasi DENGAN alasan diterima', $denganAlasan['galat'] === []);

        // §29 — pratinjau sebelum membekukan.
        $pra = $revisi->praTinjau('renstra', $rv1['version_id'], 2090, LakipSourceService::MODE_OPD, $opd);
        $this->cek('pratinjau menghasilkan baris', ($pra['ringkasan']['baris'] ?? 0) === 1);
        $this->cek('pratinjau menyebut berapa realisasi terbawa', isset($pra['ringkasan']['realisasi_terbawa']));

        // Siapkan LAKIP 2090 dari Renstra V1.
        $l1 = $revisi->siapkan('renstra', $rv1['version_id'], 2090, LakipSourceService::MODE_OPD, $opd, [
            'created_by' => 1,
        ]);
        $this->cek('LAKIP versi dibuat + snapshot terbentuk', $l1['version_id'] > 0 && $l1['snapshot_id'] > 0);

        $kepala = $this->db->table('lakip_snapshot')->where('id', $l1['snapshot_id'])->get()->getRowArray();
        $this->cek('snapshot mencatat source_type & source_version_id',
            $kepala['source_type'] === 'renstra' && (int) $kepala['source_version_id'] === $rv1['version_id']);
        $this->cek('snapshot mencatat tanggal rujukan 31 Des (§26)',
            (string) $kepala['source_reference_date'] === '2090-12-31');
        $this->cek('lakip_snapshot.version_id tertaut ke registri', (int) $kepala['version_id'] === $l1['version_id']);
        $this->cek('dokumen_versi.ref_id tertaut ke snapshot',
            (int) ($this->model->ambil($l1['version_id'])['ref_id'] ?? 0) === $l1['snapshot_id']);

        $barisAwal = $this->db->table('lakip_snapshot_baris')->where('snapshot_id', $l1['snapshot_id'])
            ->get()->getResultArray();
        $this->cek('baris snapshot membawa lineage arsip', ! empty($barisAwal[0]['source_indikator_id']));
        $targetAwal = (string) $barisAwal[0]['target'];

        // Isi realisasi pada snapshot, lalu terbitkan.
        $this->db->table('lakip_snapshot_baris')->where('id', (int) $barisAwal[0]['id'])
            ->update(['realisasi' => '77', 'status_lakip' => 'selesai']);
        $this->approval->setujui($l1['version_id'], 1, true);

        // §70/§31 — sumber berubah, snapshot TIDAK ikut berubah.
        $this->db->table('renstra_versi_target')
            ->where('versi_indikator_id', (int) $this->db->table('renstra_versi_indikator_sasaran')
                ->select('id')->where('version_id', $rv1['version_id'])->get()->getRowArray()['id'])
            ->update(['target' => '999']);

        $barisLagi = $this->db->table('lakip_snapshot_baris')->where('id', (int) $barisAwal[0]['id'])
            ->get()->getRowArray();
        $this->cek('sumber berubah, snapshot TIDAK ikut berubah (§70)', (string) $barisLagi['target'] === $targetAwal);

        // §33/§34 — revisi LAKIP membawa realisasi lewat lineage.
        $l2 = $revisi->buatRevisi($l1['version_id'], [
            'effective_from' => '2090-06-01',
            'created_by'     => 1,
        ]);

        $this->cek('revisi LAKIP dibuat (V2)', $l2['version_id'] > 0 && $l2['version_id'] !== $l1['version_id']);
        $this->cek('realisasi terbawa lewat lineage (§34)', ($l2['terbawa']['realisasi'] ?? 0) === 1);

        $barisV2 = $this->db->table('lakip_snapshot_baris')->where('snapshot_id', $l2['snapshot_id'])
            ->get()->getResultArray();
        $this->cek('nilai realisasi benar-benar tersalin', (string) ($barisV2[0]['realisasi'] ?? '') === '77');
        $this->cek('copied_from_item_id terisi (lineage, bukan nama)',
            (int) ($barisV2[0]['copied_from_item_id'] ?? 0) === (int) $barisAwal[0]['id']);
        $this->cek('baris V2 punya ID BARU', (int) $barisV2[0]['id'] !== (int) $barisAwal[0]['id']);

        // Invariant snapshot: tetap satu yang aktif.
        $aktif = (int) $this->db->table('lakip_snapshot')
            ->where('tahun', 2090)->where('mode', 'opd')->where('opd_id', $opd)
            ->where('aktif', 1)->countAllResults();
        $this->cek('tepat satu snapshot aktif setelah revisi', $aktif === 1);

        // §33 — revisi hanya dari LAKIP yang sudah ditetapkan.
        $this->cekMelempar('revisi dari LAKIP draft ditolak', static function () use ($revisi, $l2) {
            $revisi->buatRevisi($l2['version_id']);
        });

        $this->hapusSnapshotUji($opd);
        $this->hapusLingkup($scopeRenstra);
        $this->hapusLingkup(VersionScope::lakip(VersionScope::SCOPE_OPD, $opd, 2090));
        $this->bersihkanRenstraUji();
        $this->bersihkanRpjmdUji();
    }

    /* =========================================================
     * §17 — ANTREAN VERIFIKASI ADMIN KABUPATEN
     * =======================================================*/
    private function caseVerifikasi(): void
    {
        CLI::write(CLI::color('§17 — Antrean verifikasi lintas OPD', 'cyan'));

        $opdA = $this->opdUji('UJI-VERSI OPD Verif A');
        $opdB = $this->opdUji('UJI-VERSI OPD Verif B');

        $scopeA = VersionScope::renstra($opdA, self::MULAI, self::AKHIR);
        $scopeB = VersionScope::renstra($opdB, self::MULAI, self::AKHIR);
        $scopeK = VersionScope::rpjmd(self::MULAI, self::AKHIR);

        $a = $this->buatDraft($scopeA, 1, 'Renstra OPD A', '2090-01-01');
        $b = $this->buatDraft($scopeB, 1, 'Renstra OPD B', '2090-01-01');
        $k = $this->buatDraft($scopeK, 1, 'RPJMD Kab', '2090-01-01');

        // Belum diajukan -> belum boleh muncul di antrean.
        $this->cek('draft belum muncul di antrean', $this->idAntrean() === []);

        $this->approval->ajukan($a, 11);
        $this->approval->ajukan($b, 12);

        $antrean = $this->idAntrean();
        $this->cek('dua pengajuan OPD berbeda muncul bersama',
            in_array($a, $antrean, true) && in_array($b, $antrean, true));
        $this->cek('versi yang belum diajukan tetap tidak muncul', ! in_array($k, $antrean, true));

        // Penyaringan per modul: verifikator Renstra tidak melihat RPJMD.
        $this->approval->ajukan($k, 13);
        $hanyaRenstra = array_values(array_filter(
            $this->model->menungguVerifikasi(VersionScope::MODUL_RENSTRA),
            static fn ($r) => (int) $r['periode_mulai'] === self::MULAI
        ));
        $idRenstra = array_map(static fn ($r) => (int) $r['id'], $hanyaRenstra);
        $this->cek('saring per modul: RPJMD tidak ikut di antrean Renstra',
            ! in_array($k, $idRenstra, true) && in_array($a, $idRenstra, true));

        // Antrean membawa nama OPD supaya verifikator tahu ini milik siapa (§17).
        $adaNama = true;

        foreach ($hanyaRenstra as $r) {
            if (empty($r['nama_opd'])) {
                $adaNama = false;
            }
        }
        $this->cek('antrean menyertakan nama OPD pengaju', $adaNama);

        // Keputusan ganda: setelah disetujui, pengajuan hilang dari antrean dan
        // penyetujuan kedua ditolak (§53).
        $this->approval->setujui($a, 22);
        $this->cek('pengajuan yang sudah diputus hilang dari antrean',
            ! in_array($a, $this->idAntrean(), true));

        $this->cekMelempar('menyetujui dua kali ditolak', function () use ($a) {
            $this->approval->setujui($a, 22);
        });

        // Dikembalikan -> keluar antrean, kembali bisa disunting.
        $this->approval->kembalikan($b, 'Target 2092 belum diisi.', 22);
        $this->cek('pengajuan yang dikembalikan keluar dari antrean',
            ! in_array($b, $this->idAntrean(), true));
        $this->cek('yang dikembalikan menjadi draft lagi',
            $this->status($b) === DokumenVersiModel::STATUS_DRAFT);

        $this->hapusLingkup($scopeA);
        $this->hapusLingkup($scopeB);
        $this->hapusLingkup($scopeK);
        $this->bersihkanRenstraUji();
    }

    /* =========================================================
     * §9/§11 — SUNTING ISI DRAFT
     * =======================================================*/
    private function caseSunting(): void
    {
        CLI::write(CLI::color('§9/§11 — Sunting isi draft', 'cyan'));

        $scope = VersionScope::rpjmd(self::MULAI, self::AKHIR);
        [$sasaranA] = $this->siapkanRpjmdUji();

        $copy  = new VersionDeepCopyService($this->db, $this->model, new ArsipRegistry($this->db), $this->audit);
        $arsip = new RpjmdVersiModel($this->db);

        $v = $copy->buatVersi($scope, ['label' => 'V1', 'effective_from' => '2090-01-01']);
        $id = $v['version_id'];

        $isi     = $arsip->isi($id);
        $sasaran = $isi[0]['tujuan'][0]['sasaran'][0];
        $ind     = $sasaran['indikator'][0];
        $indLain = $isi[0]['tujuan'][0]['sasaran'][1]['indikator'][0];

        // --- ubah teks + target ---
        $this->dalamTransaksiUji(fn () => $arsip->simpanSuntingan($id, [
            'indikator' => [
                (int) $ind['id'] => [
                    'teks'     => 'INDIKATOR DIUBAH',
                    'baseline' => '12',
                    'target'   => [self::MULAI => '95', self::MULAI + 1 => ''],
                ],
            ],
        ]));

        $isi2 = $arsip->isi($id);
        $ind2 = $isi2[0]['tujuan'][0]['sasaran'][0]['indikator'][0];

        $this->cek('teks indikator tersimpan', $ind2['indikator_sasaran'] === 'INDIKATOR DIUBAH');
        $this->cek('baseline tersimpan', (string) $ind2['baseline'] === '12');
        $this->cek('target diubah', (string) ($ind2['target'][0]['target_tahunan'] ?? '') === '95');
        $this->cek('target dikosongkan berarti dihapus dari versi', count($ind2['target']) === 1);

        // --- silsilah: pengganti WAJIB menyebut asalnya (§11) ---
        $this->cekMelempar('"pengganti" tanpa asal-usul DITOLAK', function () use ($arsip, $id, $ind) {
            $this->dalamTransaksiUji(fn () => $arsip->simpanSuntingan($id, [
                'indikator' => [(int) $ind['id'] => ['jenis_perubahan' => 'pengganti']],
            ]));
        });

        $this->dalamTransaksiUji(fn () => $arsip->simpanSuntingan($id, [
            'indikator' => [
                (int) $ind['id'] => [
                    'jenis_perubahan'         => 'pengganti',
                    'indikator_sebelumnya_id' => (int) $indLain['id'],
                    'perubahan_substansial'   => 1,
                ],
            ],
        ]));

        $ind3 = $arsip->isi($id)[0]['tujuan'][0]['sasaran'][0]['indikator'][0];
        $this->cek('"pengganti" dengan asal-usul diterima', $ind3['jenis_perubahan'] === 'pengganti');
        $this->cek('penanda tren terputus tersimpan', (int) $ind3['perubahan_substansial'] === 1);

        // --- kolom silsilah TIDAK boleh ditulis dari form ---
        $sumberAwal = $ind3['source_indikator_id'];
        $this->dalamTransaksiUji(fn () => $arsip->simpanSuntingan($id, [
            'indikator' => [(int) $ind['id'] => ['source_indikator_id' => 999999, 'version_id' => 123]],
        ]));
        $ind4 = $arsip->isi($id)[0]['tujuan'][0]['sasaran'][0]['indikator'][0];
        $this->cek('source_indikator_id tidak bisa ditimpa dari form',
            (int) $ind4['source_indikator_id'] === (int) $sumberAwal);

        // --- tambah indikator baru ---
        $this->dalamTransaksiUji(fn () => $arsip->simpanSuntingan($id, [
            'baru' => ['indikator' => [(int) $sasaran['id'] => [
                ['teks' => 'INDIKATOR BARU', 'satuan' => 'Persen', 'target' => [self::MULAI => '50']],
            ]]],
        ]));

        $indList = $arsip->isi($id)[0]['tujuan'][0]['sasaran'][0]['indikator'];
        $this->cek('indikator baru bertambah', count($indList) === 2);
        $baru = $indList[1];
        $this->cek('indikator baru bertanda "baru"', $baru['jenis_perubahan'] === 'baru');
        $this->cek('target indikator baru tersimpan',
            (string) ($baru['target'][0]['target_tahunan'] ?? '') === '50');

        // --- pagar lingkup: id versi lain tidak boleh tersentuh ---
        $vLain = $copy->buatVersi($scope, ['label' => 'V2', 'effective_from' => '2092-01-01']);
        $indLuar = $arsip->isi($vLain['version_id'])[0]['tujuan'][0]['sasaran'][0]['indikator'][0];
        $teksLuar = $indLuar['indikator_sasaran'];

        $this->dalamTransaksiUji(fn () => $arsip->simpanSuntingan($id, [
            'indikator' => [(int) $indLuar['id'] => ['teks' => 'DIBAJAK DARI VERSI LAIN']],
        ]));

        $indLuar2 = $arsip->isi($vLain['version_id'])[0]['tujuan'][0]['sasaran'][0]['indikator'][0];
        $this->cek('baris versi lain TIDAK bisa disunting lewat id',
            $indLuar2['indikator_sasaran'] === $teksLuar);

        // --- hapus indikator dari draft ---
        $this->dalamTransaksiUji(fn () => $arsip->simpanSuntingan($id, [
            'hapus' => ['indikator:' . (int) $baru['id']],
        ]));
        $this->cek('indikator dibuang dari draft',
            count($arsip->isi($id)[0]['tujuan'][0]['sasaran'][0]['indikator']) === 1);

        // --- "dihentikan" -> dipensiunkan saat versi ditetapkan ---
        $this->dalamTransaksiUji(fn () => $arsip->simpanSuntingan($id, [
            'indikator' => [(int) $indLain['id'] => ['jenis_perubahan' => 'dihentikan']],
        ]));
        $this->approval->setujui($id, 1, true);

        $liveDihentikan = $this->db->table('rpjmd_indikator_sasaran')
            ->where('id', (int) $indLain['source_indikator_id'])->get()->getRowArray();
        $this->cek('indikator bertanda "dihentikan" DIPENSIUNKAN di data berjalan',
            ! empty($liveDihentikan['dihentikan_pada']));
        $this->cek('indikator "dihentikan" tidak dihapus', $liveDihentikan !== null);

        $this->hapusLingkup($scope);
        $this->bersihkanRpjmdUji();
    }

    /* =========================================================
     * UBAH TANGGAL BERLAKU — menyisipkan versi mundur
     * =======================================================*/
    private function caseUbahTanggal(): void
    {
        CLI::write(CLI::color('Ubah tanggal berlaku & sisip versi mundur', 'cyan'));

        $scope    = VersionScope::rpjmd(self::MULAI, self::AKHIR);
        $timeline = new VersionTimelineService($this->db, $this->model, $this->audit);

        // Meniru keadaan sesudah migrasi: baseline V1 published, arsip kosong,
        // menempati 1 Januari awal periode.
        $baseline = $this->buatDraft($scope, 1, 'V1 — Kondisi Awal', '2090-01-01');
        $this->approval->setujui($baseline, 1, true);

        // Versi mundur yang seharusnya berlaku sejak awal periode: TERTAHAN.
        $mundur = $this->buatDraft($scope, 2, 'Versi mundur', '2090-01-01');
        $this->cekMelempar('versi mundur di tanggal yang sama DITOLAK', function () use ($mundur) {
            $this->approval->setujui($mundur, 1, true);
        });

        // Draft boleh diubah tanggalnya tanpa dibatalkan.
        $this->dalamTransaksiUji(fn () => $timeline->ubahTanggalBerlaku($mundur, '2091-01-01'));
        $this->cek('tanggal DRAFT bisa diubah tanpa membatalkan versi',
            (string) $this->model->ambil($mundur)['effective_from'] === '2091-01-01');

        // Baseline otomatis (arsip kosong) boleh digeser.
        $this->dalamTransaksiUji(fn () => $timeline->ubahTanggalBerlaku(
            $baseline, '2092-01-01', 'Digeser agar versi historis bisa disisipkan'
        ));
        $this->cek('tanggal BASELINE kosong bisa diperbaiki',
            (string) $this->model->ambil($baseline)['effective_from'] === '2092-01-01');

        // Sekarang versi mundur bisa ditetapkan, dan timeline tersusun benar.
        $this->approval->setujui($mundur, 1, true);

        $t = [];
        foreach ($this->model->publishedUrutMaju($scope) as $r) {
            $t[(int) $r['id']] = $r['effective_from'] . '..' . ($r['effective_to'] ?? 'NULL');
        }

        $this->cek('versi mundur berlaku lebih dulu', ($t[$mundur] ?? '') === '2091-01-01..2092-01-01');
        $this->cek('baseline tergeser ke belakang', ($t[$baseline] ?? '') === '2092-01-01..NULL');
        $this->cek('resolver 2091 -> versi mundur', $this->labelPada($scope, '2091-06-01') === 'Versi mundur');
        $this->cek('resolver 2092 -> baseline', $this->labelPada($scope, '2092-06-01') === 'V1 — Kondisi Awal');
        $this->cek('tetap satu versi terbuka', $this->jumlahTerbuka($scope) === 1);

        // Alasan perubahan tanggal tercatat pada jejak audit.
        $adaJejak = false;
        foreach ($this->audit->riwayat($baseline) as $h) {
            if (str_contains((string) ($h['ringkasan'] ?? ''), 'Tanggal mulai berlaku diubah')) {
                $adaJejak = str_contains((string) ($h['alasan'] ?? ''), 'versi historis');
            }
        }
        $this->cek('perubahan tanggal tercatat beserta alasannya', $adaJejak);

        // Di luar periode ditolak.
        $this->cekMelempar('tanggal di luar periode DITOLAK', function () use ($timeline, $mundur) {
            $this->dalamTransaksiUji(fn () => $timeline->ubahTanggalBerlaku($mundur, '2085-01-01'));
        });

        // Bentrok dengan published lain ditolak.
        $this->cekMelempar('digeser ke tanggal yang sudah dipakai DITOLAK', function () use ($timeline, $mundur) {
            $this->dalamTransaksiUji(fn () => $timeline->ubahTanggalBerlaku($mundur, '2092-01-01'));
        });

        // Penanda baseline = dibuat migrasi (created_by kosong), BUKAN manusia.
        $this->cek('versi buatan manusia TIDAK boleh digeser',
            ! $this->approval->bolehPerbaikiTanggalBaseline(
                ['version_no' => 1, 'status' => 'published', 'created_by' => 7]
            ));
        $this->cek('versi published non-baseline TIDAK boleh digeser',
            ! $this->approval->bolehPerbaikiTanggalBaseline(
                ['version_no' => 2, 'status' => 'published', 'created_by' => null]
            ));
        $this->cek('baseline migrasi BOLEH digeser',
            $this->approval->bolehPerbaikiTanggalBaseline(
                ['version_no' => 1, 'status' => 'published', 'created_by' => null]
            ));
        $this->cek('baseline berisi tetap boleh digeser, tapi TIDAK boleh diisi ulang',
            ! $this->approval->bolehIsiBaseline(
                ['version_no' => 1, 'status' => 'published', 'created_by' => null], false
            ));
        $this->cek('baseline kosong boleh diisi dari kondisi berjalan',
            $this->approval->bolehIsiBaseline(
                ['version_no' => 1, 'status' => 'published', 'created_by' => null], true
            ));

        $this->hapusLingkup($scope);
    }

    /* =========================================================
     * §20/§21 — PERMINTAAN KOREKSI
     * =======================================================*/
    private function caseKoreksi(): void
    {
        CLI::write(CLI::color('§20/§21 — Permintaan koreksi', 'cyan'));

        $scope = VersionScope::rpjmd(self::MULAI, self::AKHIR);
        $this->siapkanRpjmdUji();

        $copy    = new VersionDeepCopyService($this->db, $this->model, new ArsipRegistry($this->db), $this->audit);
        $arsip   = new RpjmdVersiModel($this->db);
        $koreksi = new VersionCorrectionService($this->db, $this->model, new ArsipRegistry($this->db), $this->audit);

        $v   = $copy->buatVersi($scope, ['label' => 'V1', 'effective_from' => '2090-01-01']);
        $id  = $v['version_id'];
        $ind = $arsip->isi($id)[0]['tujuan'][0]['sasaran'][0]['indikator'][0];

        // Draft tidak lewat jalur koreksi — cukup disunting langsung.
        $this->cekMelempar('koreksi pada DRAFT ditolak', static function () use ($koreksi, $id, $ind) {
            $koreksi->ajukan($id, [
                'entity_type' => 'rpjmd_versi_indikator_sasaran', 'entity_id' => (int) $ind['id'],
                'field' => 'indikator_sasaran', 'requested_value' => 'x', 'reason' => 'y',
            ]);
        });

        $this->approval->setujui($id, 1, true);

        // --- daftar putih (§20) ---
        $this->cek('kolom teks masuk daftar putih',
            $koreksi->bolehKoreksi('rpjmd_versi_indikator_sasaran', 'indikator_sasaran'));
        $this->cek('satuan/baseline/target masuk daftar putih sebagai KELAS NILAI',
            $koreksi->kelasKolom('rpjmd_versi_indikator_sasaran', 'satuan') === VersionCorrectionService::KELAS_NILAI
            && $koreksi->kelasKolom('rpjmd_versi_indikator_sasaran', 'baseline') === VersionCorrectionService::KELAS_NILAI
            && $koreksi->kelasKolom('rpjmd_versi_target', 'target_tahunan') === VersionCorrectionService::KELAS_NILAI);
        $this->cek('kolom teks berkelas TEKS',
            $koreksi->kelasKolom('rpjmd_versi_indikator_sasaran', 'indikator_sasaran')
                === VersionCorrectionService::KELAS_TEKS);
        $this->cek('effective_from tetap tidak boleh dikoreksi',
            ! $koreksi->bolehKoreksi('dokumen_versi', 'effective_from'));
        $this->cek('hierarki (penunjuk induk) tetap tidak boleh dikoreksi',
            ! $koreksi->bolehKoreksi('rpjmd_versi_sasaran', 'versi_tujuan_id'));

        // Koreksi NILAI wajib berdasar tertulis, bukan sekadar beralasan.
        $this->cekMelempar('koreksi nilai TANPA dasar DITOLAK', static function () use ($koreksi, $id, $ind) {
            $koreksi->ajukan($id, [
                'entity_type' => 'rpjmd_versi_indikator_sasaran', 'entity_id' => (int) $ind['id'],
                'field' => 'satuan', 'requested_value' => 'Rupiah', 'reason' => 'salah satuan',
            ]);
        });

        $this->cekMelempar('koreksi tanpa alasan DITOLAK', static function () use ($koreksi, $id, $ind) {
            $koreksi->ajukan($id, [
                'entity_type' => 'rpjmd_versi_indikator_sasaran', 'entity_id' => (int) $ind['id'],
                'field' => 'indikator_sasaran', 'requested_value' => 'Baru', 'reason' => '   ',
            ]);
        });

        // --- alur normal ---
        $teksLama = (string) $ind['indikator_sasaran'];
        $teksBaru = 'UJI-VERSI indikator A (ejaan diperbaiki)';

        $kid = $koreksi->ajukan($id, [
            'entity_type' => 'rpjmd_versi_indikator_sasaran', 'entity_id' => (int) $ind['id'],
            'field' => 'indikator_sasaran', 'requested_value' => $teksBaru,
            'reason' => 'salah ketik', 'dasar' => 'Nota dinas 1/2090',
        ], 11);

        $baris = $koreksi->ambil($kid);
        $this->cek('permintaan tersimpan berstatus pending',
            $baris['status'] === VersionCorrectionService::STATUS_PENDING);
        $this->cek('nilai lama DIBEKUKAN saat diajukan', (string) $baris['old_value'] === $teksLama);

        $this->cekMelempar('dua permintaan menggantung pada kolom sama DITOLAK',
            static function () use ($koreksi, $id, $ind) {
                $koreksi->ajukan($id, [
                    'entity_type' => 'rpjmd_versi_indikator_sasaran', 'entity_id' => (int) $ind['id'],
                    'field' => 'indikator_sasaran', 'requested_value' => 'Lain lagi', 'reason' => 'z',
                ]);
            });

        // Anti-IDOR: entitas milik versi lain tidak boleh dikoreksi lewat id tebakan.
        $vLain   = $copy->buatVersi($scope, ['label' => 'V2', 'effective_from' => '2092-01-01']);
        $indLuar = $arsip->isi($vLain['version_id'])[0]['tujuan'][0]['sasaran'][0]['indikator'][0];

        $this->cekMelempar('entitas milik versi lain DITOLAK', static function () use ($koreksi, $id, $indLuar) {
            $koreksi->ajukan($id, [
                'entity_type' => 'rpjmd_versi_indikator_sasaran', 'entity_id' => (int) $indLuar['id'],
                'field' => 'indikator_sasaran', 'requested_value' => 'bajak', 'reason' => 'z',
            ]);
        });

        // --- setujui & terapkan ---
        $hasil   = $koreksi->setujui($kid, 22);
        $indBaru = $arsip->isi($id)[0]['tujuan'][0]['sasaran'][0]['indikator'][0];

        $this->cek('arsip terkoreksi', $indBaru['indikator_sasaran'] === $teksBaru);
        $this->cek('koreksi ikut diterapkan ke data berjalan', $hasil['ke_live'] === true);

        $live = $this->db->table('rpjmd_indikator_sasaran')
            ->where('id', (int) $ind['source_indikator_id'])->get()->getRowArray();
        $this->cek('nilai di tabel live ikut berubah', (string) $live['indikator_sasaran'] === $teksBaru);

        $this->cek('status menjadi approved',
            $koreksi->ambil($kid)['status'] === VersionCorrectionService::STATUS_APPROVED);

        $this->cekMelempar('menyetujui dua kali DITOLAK', static function () use ($koreksi, $kid) {
            $koreksi->setujui($kid, 22);
        });

        // §21 — audit menyimpan old/new.
        $adaJejak = false;

        foreach ($this->audit->riwayat($id) as $h) {
            if (($h['aksi'] ?? '') === VersionAuditService::AKSI_CORRECTION_APPROVED) {
                $adaJejak = str_contains((string) $h['sebelum'], $teksLama)
                    && str_contains((string) $h['sesudah'], 'ejaan diperbaiki');
            }
        }
        $this->cek('audit menyimpan nilai lama DAN baru', $adaJejak);

        // --- pengembalian wajib bercatatan ---
        $kid2 = $koreksi->ajukan($id, [
            'entity_type' => 'dokumen_versi', 'entity_id' => $id,
            'field' => 'catatan', 'requested_value' => 'catatan baru', 'reason' => 'melengkapi',
        ], 11);

        $this->cekMelempar('mengembalikan tanpa catatan DITOLAK', static function () use ($koreksi, $kid2) {
            $koreksi->kembalikan($kid2, '  ', 22);
        });

        $koreksi->kembalikan($kid2, 'Catatan kurang jelas.', 22);
        $this->cek('status menjadi returned',
            $koreksi->ambil($kid2)['status'] === VersionCorrectionService::STATUS_RETURNED);
        $this->cek('catatan pengembalian tersimpan',
            str_contains((string) $koreksi->ambil($kid2)['review_note'], 'kurang jelas'));

        // --- KOREKSI NILAI: satuan, baseline, target ---
        $kSatuan = $koreksi->ajukan($id, [
            'entity_type' => 'rpjmd_versi_indikator_sasaran', 'entity_id' => (int) $ind['id'],
            'field' => 'satuan', 'requested_value' => 'Rasio',
            'reason' => 'satuan salah input', 'dasar' => 'Berita acara 2/2090',
        ], 11);
        $koreksi->setujui($kSatuan, 22);

        $indSat = $arsip->isi($id)[0]['tujuan'][0]['sasaran'][0]['indikator'][0];
        $this->cek('koreksi SATUAN diterapkan', (string) $indSat['satuan'] === 'Rasio');
        $this->cek('satuan_nama ikut disegarkan', (string) $indSat['satuan_nama'] === 'Rasio');

        $kBase = $koreksi->ajukan($id, [
            'entity_type' => 'rpjmd_versi_indikator_sasaran', 'entity_id' => (int) $ind['id'],
            'field' => 'baseline', 'requested_value' => '77',
            'reason' => 'baseline tertukar', 'dasar' => 'Nota dinas 3/2090',
        ], 11);
        $koreksi->setujui($kBase, 22);
        $this->cek('koreksi BASELINE diterapkan',
            (string) $arsip->isi($id)[0]['tujuan'][0]['sasaran'][0]['indikator'][0]['baseline'] === '77');

        // Target: entitasnya baris arsip target, bukan indikatornya.
        $tg = $arsip->isi($id)[0]['tujuan'][0]['sasaran'][0]['indikator'][0]['target'][0];

        $kTarget = $koreksi->ajukan($id, [
            'entity_type' => 'rpjmd_versi_target', 'entity_id' => (int) $tg['id'],
            'field' => 'target_tahunan', 'requested_value' => '85',
            'reason' => 'salah ketik 80 menjadi 8', 'dasar' => 'Berita acara 4/2090',
        ], 11);
        $hasilT = $koreksi->setujui($kTarget, 22);

        $tgBaru = $arsip->isi($id)[0]['tujuan'][0]['sasaran'][0]['indikator'][0]['target'][0];
        $this->cek('koreksi TARGET diterapkan pada arsip', (string) $tgBaru['target_tahunan'] === '85');
        $this->cek('koreksi target ikut ke data berjalan', $hasilT['ke_live'] === true);

        $liveT = $this->db->table('rpjmd_target')
            ->where('indikator_sasaran_id', (int) $ind['source_indikator_id'])
            ->where('tahun', (int) $tg['tahun'])->get()->getRowArray();
        $this->cek('nilai target di tabel live ikut berubah', (string) $liveT['target_tahunan'] === '85');

        // Pagar lingkup untuk tabel target (tidak punya version_id sendiri).
        $tgLuar = $arsip->isi($vLain['version_id'])[0]['tujuan'][0]['sasaran'][0]['indikator'][0]['target'][0];

        $this->cekMelempar('target milik versi lain DITOLAK', static function () use ($koreksi, $id, $tgLuar) {
            $koreksi->ajukan($id, [
                'entity_type' => 'rpjmd_versi_target', 'entity_id' => (int) $tgLuar['id'],
                'field' => 'target_tahunan', 'requested_value' => '1', 'reason' => 'z', 'dasar' => 'd',
            ]);
        });

        // --- GARIS YANG TIDAK DILEWATI: versi sudah dipakai LAKIP ---
        $this->db->table('lakip_snapshot')->insert([
            'tahun' => 2090, 'mode' => 'kabupaten', 'opd_id' => 0, 'versi' => 1,
            'label' => 'UJI-VERSI snapshot', 'status' => 'draft', 'aktif' => 1,
            'source_type' => 'rpjmd', 'source_version_id' => $id,
            'dibuat_pada' => date('Y-m-d H:i:s'),
        ]);
        $snapId = (int) $this->db->insertID();

        $this->cek('terdeteksi dipakai LAKIP', $koreksi->dipakaiLakip($id) === 1);

        $this->cekMelempar('koreksi NILAI ditolak setelah dipakai LAKIP',
            static function () use ($koreksi, $id, $ind) {
                $koreksi->ajukan($id, [
                    'entity_type' => 'rpjmd_versi_indikator_sasaran', 'entity_id' => (int) $ind['id'],
                    'field' => 'baseline', 'requested_value' => '99',
                    'reason' => 'ubah lagi', 'dasar' => 'Nota 5/2090',
                ]);
            });

        // Koreksi TEKS tetap boleh — yang dijaga adalah angka yang sudah dilaporkan.
        $kTeks = $koreksi->ajukan($id, [
            'entity_type' => 'rpjmd_versi_sasaran',
            'entity_id'   => (int) $arsip->isi($id)[0]['tujuan'][0]['sasaran'][0]['id'],
            'field' => 'sasaran_rpjmd', 'requested_value' => 'UJI-VERSI sasaran A (ejaan)',
            'reason' => 'salah ketik',
        ], 11);
        $this->cek('koreksi TEKS tetap boleh walau sudah dipakai LAKIP', $kTeks > 0);

        $this->db->table('lakip_snapshot')->where('id', $snapId)->delete();
        $this->hapusLingkup($scope);
        $this->bersihkanRpjmdUji();
    }

    /** Model arsip menolak berjalan di luar transaksi — dibungkus di sini. */
    /* =========================================================
     * FORM "TAMBAH RENSTRA" -> ARSIP VERSI
     *
     * Yang diuji bukan tampilannya, melainkan perjalanan datanya: bentuk POST
     * form masuk ke arsip, lalu keluar lagi dalam bentuk yang sama untuk
     * mengisi ulang form. Bila kedua arah itu tidak cocok, menyunting sebuah
     * tujuan akan diam-diam kehilangan isian — kegagalan yang tidak pernah
     * memunculkan pesan galat.
     * =======================================================*/
    private function caseFormVersiRenstra(): void
    {
        CLI::write(CLI::color('Form Versi Renstra (bentuk sama dengan Tambah Renstra)', 'cyan'));

        $opd    = $this->opdUji('UJI-VERSI OPD Form');
        [, $rs] = $this->siapkanRpjmdUji();

        $scope = VersionScope::renstra($opd, self::MULAI, self::AKHIR);
        $arsip = new RenstraVersiModel($this->db);

        $vId = $this->model->sisipkan(array_merge($scope->kolomBaru(), [
            'version_no' => 1, 'label' => 'V1 — uji form',
            'effective_from' => self::MULAI . '-01-01',
            'status' => DokumenVersiModel::STATUS_DRAFT, 'created_by' => 11,
        ]));

        // Persis bentuk yang dikirim app/Views/adminOpd/renstra/tambah_renstra.php.
        $post = [
            'rpjmd_sasaran_id' => $rs,
            'tujuan_renstra'   => 'UJI-VERSI tujuan dari form',
            // Sengaja SALAH: periode harus diambil dari lingkup versi, bukan form.
            'tahun_mulai'      => 1999,
            'tahun_akhir'      => 2001,
            'indikator_tujuan' => [
                ['indikator_tujuan' => 'IT-1', 'target_tahunan' => [
                    ['tahun' => self::MULAI, 'target' => '10'],
                    ['tahun' => self::MULAI + 1, 'target' => '20'],
                    // Di luar periode: harus diabaikan, bukan disimpan.
                    ['tahun' => self::AKHIR + 5, 'target' => '99'],
                    ['tahun' => 0, 'target' => ''],
                ]],
            ],
            'sasaran_renstra' => [
                ['sasaran' => 'S-1', 'indikator_sasaran' => [
                    [
                        'indikator_sasaran' => 'IS-1',
                        'satuan'            => '1',
                        'jenis_indikator'   => 'positif',
                        'baseline'          => '5',
                        'target_tahunan'    => [
                            ['tahun' => self::MULAI, 'target' => '70'],
                            ['tahun' => self::MULAI + 1, 'target' => '80'],
                        ],
                    ],
                    // Baris kosong dari tombol "Tambah" yang tidak jadi diisi.
                    ['indikator_sasaran' => '   ', 'target_tahunan' => []],
                ]],
                ['sasaran' => '', 'indikator_sasaran' => []],
            ],
        ];

        $tujuanId = $this->dalamTransaksiUji(
            fn () => $arsip->simpanTujuanDariForm($vId, $scope, $post)
        );

        $isi = $arsip->isi($vId);
        $t   = $isi[0] ?? [];

        $this->cek('tujuan tersimpan ke arsip', ($t['tujuan'] ?? '') === 'UJI-VERSI tujuan dari form');
        $this->cek('sasaran RPJMD ikut dibekukan teksnya', ! empty($t['rpjmd_sasaran_teks']));
        $this->cek('indikator tujuan tersimpan', count($t['indikator_tujuan'] ?? []) === 1);
        $this->cek('target di luar periode diabaikan',
            count($t['indikator_tujuan'][0]['target'] ?? []) === 2);
        $this->cek('sasaran kosong tidak ikut tersimpan', count($t['sasaran'] ?? []) === 1);
        $this->cek('indikator kosong tidak ikut tersimpan',
            count($t['sasaran'][0]['indikator'] ?? []) === 1);
        $this->cek('periode sasaran diambil dari lingkup, bukan dari form',
            (int) ($t['sasaran'][0]['tahun_mulai'] ?? 0) === self::MULAI
            && (int) ($t['sasaran'][0]['tahun_akhir'] ?? 0) === self::AKHIR);
        $this->cek('kepemilikan sasaran diambil dari lingkup',
            (int) ($t['sasaran'][0]['opd_id'] ?? 0) === $opd);
        $this->cek('satuan diterjemahkan saat dibekukan',
            ($t['sasaran'][0]['indikator'][0]['satuan_nama'] ?? null) !== null);

        // --- pulang-pergi: arsip -> bentuk form ---
        $ulang = $arsip->tujuanUntukForm($vId, $tujuanId);

        $this->cek('bentuk isian ulang memakai kunci yang sama dengan POST',
            isset($ulang['tujuan_renstra'], $ulang['indikator_tujuan'], $ulang['sasaran_renstra']));
        $this->cek('target dikembalikan sebagai larik berurut, bukan objek',
            array_keys($ulang['indikator_tujuan'][0]['target_tahunan']) === [0, 1]);
        $this->cek('nilai target utuh setelah pulang-pergi',
            ($ulang['sasaran_renstra'][0]['indikator_sasaran'][0]['target_tahunan'][1]['target'] ?? '') === '80');
        $this->cek('baseline utuh setelah pulang-pergi',
            ($ulang['sasaran_renstra'][0]['indikator_sasaran'][0]['baseline'] ?? '') === '5');

        // --- menyunting: tulis ulang, bukan menggandakan ---
        $post['tujuan_renstra']                = 'UJI-VERSI tujuan disunting';
        $post['sasaran_renstra'][0]['sasaran'] = 'S-1 disunting';

        $this->dalamTransaksiUji(
            fn () => $arsip->simpanTujuanDariForm($vId, $scope, $post, $tujuanId)
        );

        $isi2 = $arsip->isi($vId);

        $this->cek('menyunting tidak menggandakan tujuan', count($isi2) === 1);
        $this->cek('teks tujuan terbarui', ($isi2[0]['tujuan'] ?? '') === 'UJI-VERSI tujuan disunting');
        $this->cek('anak lama tidak tertinggal', count($isi2[0]['sasaran'] ?? []) === 1);
        $this->cek('teks sasaran terbarui', ($isi2[0]['sasaran'][0]['sasaran'] ?? '') === 'S-1 disunting');

        $sisaTarget = (int) $this->db->table('renstra_versi_target')
            ->join('renstra_versi_indikator_sasaran i', 'i.id = renstra_versi_target.versi_indikator_id')
            ->where('i.version_id', $vId)->countAllResults();
        $this->cek('target lama ikut terbuang saat ditulis ulang', $sisaTarget === 2);

        // --- tujuan wajib diisi ---
        $this->cekMelempar('tujuan kosong DITOLAK', function () use ($arsip, $vId, $scope, $post) {
            $post['tujuan_renstra'] = '  ';
            $this->dalamTransaksiUji(fn () => $arsip->simpanTujuanDariForm($vId, $scope, $post));
        });

        // --- menyunting tujuan milik versi lain ditolak ---
        $this->cekMelempar('tujuan dari versi lain DITOLAK', function () use ($arsip, $scope, $post, $tujuanId) {
            $lain = $this->model->sisipkan(array_merge($scope->kolomBaru(), [
                'version_no' => 2, 'label' => 'V2 — uji form',
                'effective_from' => (self::MULAI + 1) . '-01-01',
                'status' => DokumenVersiModel::STATUS_DRAFT, 'created_by' => 11,
            ]));

            $this->dalamTransaksiUji(
                fn () => $arsip->simpanTujuanDariForm($lain, $scope, $post, $tujuanId)
            );
        });

        $this->cek('urutan tujuan berjalan otomatis', (int) ($isi2[0]['urutan'] ?? -1) === 0);

        $this->hapusLingkup($scope);
        $this->bersihkanRenstraUji();
        $this->bersihkanRpjmdUji();
    }

    /* =========================================================
     * ARSIP DIBACA SEPERTI DATA BERJALAN
     *
     * Menu Renstra memakai satu tabel untuk kondisi berjalan maupun versi
     * tersimpan. Itu hanya aman bila bentuk datanya benar-benar sama; kalau
     * meleset, tabelnya tidak melempar galat — ia hanya menampilkan kolom
     * kosong, dan tak seorang pun tahu ada yang hilang.
     * =======================================================*/
    private function caseBacaVersiRenstra(): void
    {
        CLI::write(CLI::color('Membaca versi Renstra dengan bentuk data yang sama', 'cyan'));

        $opd    = $this->opdUji('UJI-VERSI OPD Baca');
        [, $rs] = $this->siapkanRpjmdUji();
        $tuj    = $this->sisipUji('renstra_tujuan', [
            'rpjmd_sasaran_id' => $rs, 'tujuan' => 'UJI-VERSI tujuan baca',
        ]);
        $this->sisipRenstraSasaran($opd, $tuj, 'UJI-VERSI sasaran baca');

        $scope = VersionScope::renstra($opd, self::MULAI, self::AKHIR);
        $arsip = new RenstraVersiModel($this->db);

        $vId = $this->model->sisipkan(array_merge($scope->kolomBaru(), [
            'version_no' => 1, 'label' => 'V1 — uji baca',
            'effective_from' => self::MULAI . '-01-01',
            'status' => DokumenVersiModel::STATUS_DRAFT, 'created_by' => 11,
        ]));

        $this->dalamTransaksiUji(fn () => $arsip->bekukanDariLive($vId, $scope));

        $live  = (new \App\Models\Opd\RenstraModel($this->db))->getFilteredRenstra(
            $opd, null, null, null, null, self::MULAI . '-' . self::AKHIR
        );
        $versi = $arsip->bacaSepertiLive($vId);

        $this->cek('jumlah tujuan sama dengan pembacaan data berjalan',
            count($versi) === count($live) && count($versi) === 1);

        $kunciLive  = array_keys($live[0]);
        $kunciVersi = array_keys($versi[0]);

        // `dari_arsip` memang hanya ada di sisi arsip — itulah penandanya.
        $this->cek('seluruh kunci tingkat tujuan ada di kedua sisi',
            array_diff($kunciLive, $kunciVersi) === []);
        $this->cek('arsip menandai dirinya sendiri', ($versi[0]['dari_arsip'] ?? false) === true);

        $sLive  = array_values($live[0]['sasaran'])[0];
        $sVersi = array_values($versi[0]['sasaran'])[0];

        $this->cek('kunci tingkat sasaran sama',
            array_diff(array_keys($sLive), array_keys($sVersi)) === []);
        $this->cek('teks sasaran sama', $sVersi['sasaran'] === $sLive['sasaran']);

        $iLive  = array_values($sLive['indikator'])[0];
        $iVersi = array_values($sVersi['indikator'])[0];

        $this->cek('kunci tingkat indikator sama',
            array_diff(array_keys($iLive), array_keys($iVersi)) === []);
        $this->cek('nama satuan ikut terbaca, bukan hanya kodenya',
            $iVersi['satuan'] === $iLive['satuan'] && $iVersi['satuan'] !== '');
        $this->cek('baseline sama', (string) $iVersi['baseline'] === (string) $iLive['baseline']);

        // Target harus berupa peta tahun => nilai, sebab tampilan mengambilnya
        // dengan $targets[$tahun]; daftar berindeks 0 akan tampil kosong.
        $this->cek('target dipetakan per tahun, bukan berindeks urut',
            ($iVersi['targets'][self::MULAI] ?? null) === ($iLive['targets'][self::MULAI] ?? null)
            && ($iVersi['targets'][self::MULAI] ?? '') !== '');

        $this->cek('status dikosongkan pada arsip', $sVersi['status'] === '');

        // --- saringan teks bekerja di sisi arsip ---
        $this->cek('saringan tujuan yang cocok tetap memberi hasil',
            count($arsip->bacaSepertiLive($vId, null, 'tujuan baca')) === 1);
        $this->cek('saringan tujuan yang tidak cocok menyaring habis',
            $arsip->bacaSepertiLive($vId, null, 'tidak ada ini') === []);
        $this->cek('saringan sasaran RPJMD bekerja',
            count($arsip->bacaSepertiLive($vId, 'UJI-VERSI', null)) === 1);

        $this->hapusLingkup($scope);
        $this->bersihkanRenstraUji();
        $this->bersihkanRpjmdUji();
    }

    /* =========================================================
     * TUNJUKAN "TAMPILAN UTAMA"
     *
     * Ini jawaban KEDUA atas "versi mana yang dipakai", di samping rentang
     * tanggal. Dua jawaban atas satu pertanyaan hanya aman selama tiga hal
     * dijaga: hanya satu tunjukan per dokumen, hanya versi resmi yang boleh
     * ditunjuk, dan tunjukan tidak boleh diam-diam menggeser tanggal berlaku.
     * Ketiganya diuji di bawah.
     * =======================================================*/
    private function caseTampilanUtama(): void
    {
        CLI::write(CLI::color('Tunjukan tampilan utama', 'cyan'));

        if (! $this->model->siapTampilan()) {
            CLI::write('  ' . CLI::color('LEWAT', 'yellow')
                . '  kolom tunjukan belum ada — jalankan db/update_2026-08-23_tampilan_utama_versi.sql');

            return;
        }

        $scope = VersionScope::rpjmd(self::MULAI, self::AKHIR);

        $v1 = $this->buatDraft($scope, 1, 'V1 tunjukan', self::MULAI . '-01-01');
        $this->terbitkan($v1);

        $this->cek('awalnya tidak ada tunjukan', $this->model->tampilanUtama($scope) === null);

        // --- draft tidak boleh ditunjuk ---
        $draft = $this->buatDraft($scope, 2, 'V2 tunjukan', (self::MULAI + 1) . '-01-01');

        $this->cekMelempar('menunjuk versi DRAFT ditolak', function () use ($draft, $scope) {
            $this->model->tetapkanTampilanUtama($draft, $scope, 11);
        });

        // --- versi published boleh ---
        $this->model->tetapkanTampilanUtama($v1, $scope, 11);
        $tunjuk = $this->model->tampilanUtama($scope);

        $this->cek('versi published bisa ditunjuk', $tunjuk !== null && (int) $tunjuk['id'] === $v1);
        $this->cek('pelaku dan waktunya tercatat',
            (int) ($tunjuk['tampilan_oleh'] ?? 0) === 11 && ! empty($tunjuk['tampilan_pada']));

        // --- menunjuk tidak menggeser tanggal berlaku ---
        $barisV1 = $this->model->ambil($v1);
        $this->cek('tanggal berlaku tidak ikut berubah saat ditunjuk',
            (string) $barisV1['effective_from'] === self::MULAI . '-01-01');

        // --- pindah tunjukan: lepas dulu, baru pasang ---
        $this->terbitkan($draft);
        $this->model->tetapkanTampilanUtama($draft, $scope, 22);

        $tunjuk2 = $this->model->tampilanUtama($scope);
        $this->cek('tunjukan berpindah, bukan bertambah',
            $tunjuk2 !== null && (int) $tunjuk2['id'] === $draft);
        $this->cek('versi lama otomatis lepas',
            (int) $this->model->ambil($v1)['tampilan_utama'] === 0);

        $jml = (int) $this->db->table('dokumen_versi')
            ->where($scope->kondisi())->where('tampilan_utama', 1)->countAllResults();
        $this->cek('hanya SATU tunjukan per dokumen', $jml === 1);

        // --- engine menolak tunjukan kedua, bukan sekadar kode aplikasi ---
        $this->cekMelempar('UNIQUE menolak tunjukan kedua di lingkup sama', function () use ($v1) {
            $this->db->table('dokumen_versi')->where('id', $v1)->update(['tampilan_utama' => 1]);
        });

        // --- lingkup lain punya tunjukannya sendiri ---
        $opd   = $this->opdUji('UJI-VERSI OPD Tunjuk');
        $lain  = VersionScope::renstra($opd, self::MULAI, self::AKHIR);
        $vLain = $this->buatDraft($lain, 1, 'V1 renstra tunjukan', self::MULAI . '-01-01');
        $this->terbitkan($vLain);
        $this->model->tetapkanTampilanUtama($vLain, $lain, 11);

        $this->cek('dokumen lain boleh punya tunjukan sendiri',
            (int) $this->model->tampilanUtama($lain)['id'] === $vLain
            && (int) $this->model->tampilanUtama($scope)['id'] === $draft);

        // --- versi milik lingkup lain tidak bisa ditunjuk lewat lingkup ini ---
        $this->cekMelempar('versi dari lingkup lain DITOLAK', function () use ($vLain, $scope) {
            $this->model->tetapkanTampilanUtama($vLain, $scope, 11);
        });

        // --- melepas mengembalikan ke kondisi berjalan ---
        $this->model->lepasTampilanUtama($scope);
        $this->cek('tunjukan bisa dilepas', $this->model->tampilanUtama($scope) === null);
        $this->cek('melepas tidak menyentuh dokumen lain',
            $this->model->tampilanUtama($lain) !== null);

        $this->model->lepasTampilanVersi($vLain);
        $this->cek('melepas per-versi juga bekerja', $this->model->tampilanUtama($lain) === null);

        $this->hapusLingkup($lain);
        $this->hapusLingkup($scope);
        $this->bersihkanRenstraUji();
    }

    /* =========================================================
     * IZIN SUNTING DOKUMEN YANG SUDAH DITETAPKAN
     *
     * Yang paling perlu dibuktikan bukan "izinnya bekerja", melainkan
     * BATASNYA: bahwa membuka kunci tidak ikut membuka arsip. Sebab kalau
     * arsip ikut berubah, seluruh alasan fitur versi ini ada ikut hilang,
     * dan kehilangan itu tidak akan memunculkan satu pun pesan galat.
     * =======================================================*/
    private function caseIzinSunting(): void
    {
        CLI::write(CLI::color('Izin sunting dokumen yang sudah ditetapkan', 'cyan'));

        $izin = new IzinSuntingService($this->db);

        if (! $izin->siap()) {
            CLI::write('  ' . CLI::color('LEWAT', 'yellow')
                . '  tabel belum ada — jalankan db/update_2026-08-24_izin_sunting.sql');

            return;
        }

        $opd    = $this->opdUji('UJI-VERSI OPD Izin');
        [, $rs] = $this->siapkanRpjmdUji();
        $tuj    = $this->sisipUji('renstra_tujuan', [
            'rpjmd_sasaran_id' => $rs, 'tujuan' => 'UJI-VERSI tujuan izin',
        ]);
        $this->sisipRenstraSasaran($opd, $tuj, 'UJI-VERSI sasaran izin');

        $scope = VersionScope::renstra($opd, self::MULAI, self::AKHIR);
        $arsip = new RenstraVersiModel($this->db);

        $vId = $this->model->sisipkan(array_merge($scope->kolomBaru(), [
            'version_no' => 1, 'label' => 'V1 — uji izin',
            'effective_from' => self::MULAI . '-01-01',
            'status' => DokumenVersiModel::STATUS_DRAFT, 'created_by' => 11,
        ]));

        $this->dalamTransaksiUji(fn () => $arsip->bekukanDariLive($vId, $scope));
        $this->approval->ajukan($vId, 11);
        $this->approval->setujui($vId, 22);

        $sidikArsipAwal = $this->sidikArsip($arsip, $vId);

        $this->cek('sebelum meminta, dokumen belum boleh disunting',
            ! $izin->bolehSunting($scope));

        // --- alasan wajib ---
        $this->cekMelempar('permohonan tanpa alasan DITOLAK', static function () use ($izin, $scope) {
            $izin->ajukan($scope, '   ', 11);
        });

        $izinId = $izin->ajukan($scope, 'target indikator salah ketik', 11, $vId);

        $this->cek('permohonan tercatat menunggu keputusan',
            $izin->berjalan($scope)['status'] === IzinSuntingService::STATUS_PENDING);
        $this->cek('menunggu keputusan BELUM membuka kunci', ! $izin->bolehSunting($scope));

        // --- satu permohonan berjalan saja ---
        $this->cekMelempar('permohonan kedua saat masih menggantung DITOLAK',
            static function () use ($izin, $scope) {
                $izin->ajukan($scope, 'permohonan kedua', 11);
            });

        // --- penolakan wajib bercatatan ---
        $this->cekMelempar('menolak tanpa catatan DITOLAK', static function () use ($izin, $izinId) {
            $izin->tolak($izinId, 22, '  ');
        });

        // --- disetujui: kunci terbuka ---
        $izin->setujui($izinId, 22, 'Silakan perbaiki.');
        $this->cek('setelah disetujui, kunci terbuka', $izin->bolehSunting($scope));
        $this->cek('pemutus tercatat',
            (int) $izin->ambil($izinId)['diputus_oleh'] === 22);

        // --- INI POKOKNYA: arsip versi TIDAK ikut terbuka ---
        $this->cek('arsip versi yang sudah ditetapkan TIDAK berubah',
            $this->sidikArsip($arsip, $vId) === $sidikArsipAwal);
        $this->cek('status versi tetap published',
            $this->status($vId) === DokumenVersiModel::STATUS_PUBLISHED);

        // --- keputusan tidak bisa diulang ---
        $this->cekMelempar('menyetujui dua kali DITOLAK', static function () use ($izin, $izinId) {
            $izin->setujui($izinId, 22);
        });

        // --- selesai saat versi berikutnya diajukan ---
        $izin->selesaikan($scope);
        $this->cek('izin ditutup setelah dipakai', $izin->berjalan($scope) === null);
        $this->cek('kunci tertutup kembali', ! $izin->bolehSunting($scope));
        $this->cek('waktu selesai tercatat', ! empty($izin->ambil($izinId)['selesai_pada']));

        // --- boleh mengajukan lagi sesudah yang lama ditutup ---
        $izin2 = $izin->ajukan($scope, 'perbaikan berikutnya', 11, $vId);
        $this->cek('boleh mengajukan lagi setelah yang lama ditutup',
            $izin->berjalan($scope) !== null);

        // --- pencabutan ---
        $izin->setujui($izin2, 22);
        $izin->cabut($izin2, 22, 'Cukup.');
        $this->cek('izin yang sudah diberikan bisa dicabut', ! $izin->bolehSunting($scope));

        $this->cekMelempar('mencabut izin yang tidak berlaku DITOLAK',
            static function () use ($izin, $izin2) {
                $izin->cabut($izin2, 22);
            });

        $this->cek('riwayat menyimpan seluruh permohonan',
            count($izin->riwayat($scope)) === 2);

        $this->hapusLingkup($scope);
        $this->bersihkanRenstraUji();
        $this->bersihkanRpjmdUji();
        $this->db->table('dokumen_izin_sunting')->where('opd_key', $opd)->delete();
    }

    /**
     * Sidik jari isi arsip — untuk membuktikan ia benar-benar tidak tersentuh.
     *
     * Membandingkan jumlah baris saja tidak cukup: mengganti satu teks target
     * tidak mengubah jumlah apa pun.
     */
    private function sidikArsip(RenstraVersiModel $arsip, int $versiId): string
    {
        return md5(json_encode($arsip->isi($versiId)));
    }

    /* =========================================================
     * MENGGESER TANGGAL VERSI YANG SUDAH DITETAPKAN
     *
     * Yang dijaga di sini ada dua dan keduanya mudah tertukar:
     *   1. menggeser tanggal TIDAK BOLEH menyentuh isi arsip
     *   2. menggeser tanggal HARUS menghitung ulang batas versi tetangganya
     *
     * Melewatkan yang kedua menghasilkan garis waktu berlubang atau tumpang
     * tindih yang tidak melempar galat apa pun — ia hanya menjawab pertanyaan
     * "versi mana yang berlaku" dengan jawaban yang salah.
     * =======================================================*/
    private function caseGeserTanggalResmi(): void
    {
        CLI::write(CLI::color('Menggeser tanggal versi yang sudah ditetapkan', 'cyan'));

        $scope = VersionScope::rpjmd(self::MULAI, self::AKHIR);

        // V1 sengaja TIDAK di awal periode: menyisakan ruang di depannya supaya
        // pertukaran urutan benar-benar bisa diuji.
        $v1 = $this->buatDraft($scope, 1, 'V1 geser', (self::MULAI + 1) . '-01-01');
        $this->terbitkan($v1);

        $v2 = $this->buatDraft($scope, 2, 'V2 geser', (self::MULAI + 2) . '-01-01');
        $this->terbitkan($v2);

        $awal = $this->model->ambil($v1);

        $this->cek('sebelum digeser, V1 berakhir saat V2 mulai',
            (string) $awal['effective_to'] === (self::MULAI + 2) . '-01-01');

        // --- geser V2 maju: batas V1 ikut bergerak ---
        $this->dalamTransaksiUji(fn () => $this->timeline->ubahTanggalBerlaku(
            $v2, (self::MULAI + 3) . '-07-01', 'Perbaikan tanggal SK'
        ));

        $this->cek('menggeser V2 ikut menggeser batas akhir V1',
            (string) $this->model->ambil($v1)['effective_to'] === (self::MULAI + 3) . '-07-01');
        $this->cek('V2 memakai tanggal barunya',
            (string) $this->model->ambil($v2)['effective_from'] === (self::MULAI + 3) . '-07-01');
        $this->cek('V2 tetap versi terbuka', $this->jumlahTerbuka($scope) === 1);
        $this->cek('keduanya tetap published',
            $this->status($v1) === DokumenVersiModel::STATUS_PUBLISHED
            && $this->status($v2) === DokumenVersiModel::STATUS_PUBLISHED);

        // --- tidak ada celah maupun tumpang tindih ---
        $this->cek('garis waktu tetap bersambung tanpa celah',
            (string) $this->model->ambil($v1)['effective_to']
            === (string) $this->model->ambil($v2)['effective_from']);

        // --- geser MUNDUR melewati V1: urutannya bertukar, tetap utuh ---
        $this->dalamTransaksiUji(fn () => $this->timeline->ubahTanggalBerlaku(
            $v2, self::MULAI . '-01-01', null
        ));

        $this->cek('bisa digeser mundur melewati versi lain',
            (string) $this->model->ambil($v2)['effective_from'] === self::MULAI . '-01-01');
        $this->cek('setelah bertukar urutan, V2 yang jadi tertutup',
            (string) $this->model->ambil($v2)['effective_to'] === (self::MULAI + 1) . '-01-01');
        $this->cek('dan V1 yang jadi terbuka',
            $this->model->ambil($v1)['effective_to'] === null);
        $this->cek('setelah bertukar urutan, tetap satu versi terbuka',
            $this->jumlahTerbuka($scope) === 1);

        // --- yang ditolak tetap ditolak ---
        $this->cekMelempar('menggeser ke tanggal yang sudah dipakai versi resmi lain DITOLAK',
            function () use ($v2) {
                $this->dalamTransaksiUji(fn () => $this->timeline->ubahTanggalBerlaku(
                    $v2, (self::MULAI + 1) . '-01-01', null
                ));
            });

        $this->cekMelempar('menggeser ke luar periode DITOLAK', function () use ($v2) {
            $this->dalamTransaksiUji(fn () => $this->timeline->ubahTanggalBerlaku(
                $v2, (self::AKHIR + 5) . '-01-01', null
            ));
        });

        $this->hapusLingkup($scope);
    }

    /* =========================================================
     * PENGUNCIAN RENSTRA BERJALAN
     *
     * Dua lubang yang ditutup di sini, keduanya diam-diam:
     *
     *   1. Membuat draft versi baru dulu MEMBUKA kunci Renstra berjalan,
     *      sebab keadaan ditentukan versi ber-nomor tertinggi apa pun
     *      statusnya. Siapa pun yang boleh membuat versi bisa melewati
     *      seluruh alur izin sunting dengan dua klik.
     *
     *   2. Penjaga MELOLOSKAN tujuan milik OPD lain: tidak ketemu di lingkup
     *      sendiri -> periode (0,0) -> "tidak terkunci" -> boleh menulis.
     *
     * Keduanya tidak pernah melempar galat. Yang pertama hanya membuat tombol
     * bermunculan, yang kedua hanya menulis ke dokumen yang salah.
     * =======================================================*/
    private function casePenguncianRenstra(): void
    {
        CLI::write(CLI::color('Penguncian Renstra berjalan', 'cyan'));

        if ($this->db->getDatabase() !== config('Database')->default['database']) {
            CLI::write('  ' . CLI::color('LEWAT', 'yellow')
                . '  keadaan siklus memakai koneksi bawaan; jalankan tanpa --db.');

            return;
        }

        $opd    = $this->opdUji('UJI-VERSI OPD Kunci');
        [, $rs] = $this->siapkanRpjmdUji();
        $tuj    = $this->sisipUji('renstra_tujuan', [
            'rpjmd_sasaran_id' => $rs, 'tujuan' => 'UJI-VERSI tujuan kunci',
        ]);
        $this->sisipRenstraSasaran($opd, $tuj, 'UJI-VERSI sasaran kunci');

        session()->set('opd_id', $opd);

        $uji   = $this->harnessRenstra();
        $scope = VersionScope::renstra($opd, self::MULAI, self::AKHIR);
        $arsip = new RenstraVersiModel($this->db);

        // --- belum ada versi ---
        $k = $uji->keadaan(self::MULAI, self::AKHIR);
        $this->cek('belum ada versi -> bebas disunting', ! $k['terkunci'] && $k['boleh_ajukan']);

        // --- baseline published tapi arsipnya kosong: TIDAK mengunci ---
        $baseline = $this->model->sisipkan(array_merge($scope->kolomBaru(), [
            'version_no' => 1, 'label' => 'V1 — baseline kosong',
            'effective_from' => self::MULAI . '-01-01',
            'status' => DokumenVersiModel::STATUS_PUBLISHED, 'created_by' => null,
        ]));

        $k = $uji->keadaan(self::MULAI, self::AKHIR);
        $this->cek('baseline berarsip kosong TIDAK mengunci', ! $k['terkunci']);
        $this->cek('baseline dikenali sebagai versi dasar, bukan versi resmi',
            $k['versi_resmi'] === null && (int) $k['versi_dasar']['id'] === $baseline);

        // --- baseline diisi lalu ditetapkan: barulah mengunci ---
        $this->dalamTransaksiUji(fn () => $arsip->bekukanDariLive($baseline, $scope));

        $k = $uji->keadaan(self::MULAI, self::AKHIR);
        $this->cek('setelah berisi, versi resmi mengunci Renstra', $k['terkunci']);
        $this->cek('versi resmi dikenali', (int) $k['versi_resmi']['id'] === $baseline);
        $this->cek('ditawari mengajukan izin sunting', $k['boleh_minta_izin']);

        // ===== LUBANG 1: draft baru TIDAK BOLEH membuka kunci =====
        $draft = $this->model->sisipkan(array_merge($scope->kolomBaru(), [
            'version_no' => 2, 'label' => 'V2 — draft usulan',
            'effective_from' => (self::MULAI + 1) . '-01-01',
            'status' => DokumenVersiModel::STATUS_DRAFT, 'created_by' => 11,
        ]));

        // (dari, ke) — bukan sebaliknya; tertukar berarti draft tetap kosong
        // dan uji di bawahnya akan lulus karena alasan yang salah.
        $this->dalamTransaksiUji(fn () => $arsip->salinDariVersi($baseline, $draft));

        $k = $uji->keadaan(self::MULAI, self::AKHIR);
        $this->cek('draft berisi TIDAK membuka kunci Renstra berjalan', $k['terkunci']);
        $this->cek('yang mengunci tetap versi resmi, bukan draft',
            (int) $k['versi']['id'] === $baseline);
        $this->cek('draft berisi dikenali tersendiri',
            (int) $k['draft_berisi']['id'] === $draft);

        // --- izin sunting membuka kunci, TAPI draft berisi menghalangi pengajuan ---
        $izin   = new IzinSuntingService($this->db);
        $izinId = $izin->ajukan($scope, 'perbaikan target', 11, $baseline);
        $izin->setujui($izinId, 22);

        $k = $uji->keadaan(self::MULAI, self::AKHIR);
        $this->cek('izin sunting membuka kunci', ! $k['terkunci'] && $k['sedang_disunting']);
        $this->cek('draft berisi menghalangi pengajuan dari menu Renstra',
            ! $k['boleh_ajukan'] && $k['alasan'] !== null);

        // --- draft dibuang: pengajuan kembali terbuka ---
        $this->db->table('dokumen_versi')->where('id', $draft)->delete();

        $k = $uji->keadaan(self::MULAI, self::AKHIR);
        $this->cek('tanpa draft berisi, pengajuan terbuka lagi', $k['boleh_ajukan']);

        // --- pending mengalahkan segalanya ---
        $izin->cabut($izinId, 22);
        $this->db->table('dokumen_versi')->where('id', $baseline)
            ->update(['status' => DokumenVersiModel::STATUS_PENDING]);

        $k = $uji->keadaan(self::MULAI, self::AKHIR);
        $this->cek('menunggu verifikasi mengunci dan menawarkan tarik',
            $k['terkunci'] && $k['boleh_tarik'] && ! $k['boleh_minta_izin']);

        // ===== LUBANG 2: periode tidak sah berarti TOLAK, bukan bebas =====
        $k = $uji->keadaan(0, 0);
        $this->cek('periode tidak sah -> TERKUNCI, bukan bebas',
            $k['terkunci'] && ! $k['boleh_ajukan']);

        // --- kepemilikan tujuan ---
        $this->cek('tujuan sendiri dikenali milik sendiri', $uji->tujuanMilikSaya($tuj));

        $opdLain = $this->opdUji('UJI-VERSI OPD Kunci Lain');
        $tujLain = $this->sisipUji('renstra_tujuan', [
            'rpjmd_sasaran_id' => $rs, 'tujuan' => 'UJI-VERSI tujuan OPD lain',
        ]);
        $this->sisipRenstraSasaran($opdLain, $tujLain, 'UJI-VERSI sasaran OPD lain');

        $this->cek('tujuan OPD lain DITOLAK', ! $uji->tujuanMilikSaya($tujLain));
        $this->cek('tujuan yang tidak ada DITOLAK', ! $uji->tujuanMilikSaya(999999));

        session()->remove('opd_id');

        $this->db->table('dokumen_izin_sunting')->where('opd_key', $opd)->delete();
        $this->hapusLingkup($scope);
        $this->hapusLingkup(VersionScope::renstra($opdLain, self::MULAI, self::AKHIR));
        $this->bersihkanRenstraUji();
        $this->bersihkanRpjmdUji();
    }

    /**
     * Kelas uji yang memakai trait siklus Renstra apa adanya.
     *
     * Menyalin logikanya ke dalam uji akan membuat uji ini menguji salinan,
     * bukan kode yang sungguh berjalan — dan salinan tidak ikut berubah ketika
     * yang asli diperbaiki.
     */
    private function harnessRenstra(): object
    {
        return new class {
            use \App\Controllers\Concerns\RenstraSiklusTrait;

            public function keadaan(int $tm, int $ta): array
            {
                return $this->renstraKeadaan($tm, $ta);
            }

            public function tujuanMilikSaya(int $id): bool
            {
                return $this->renstraTujuanMilikSaya($id);
            }
        };
    }

    /* =========================================================
     * SYNC IKU DARI VERSI RENSTRA TERTENTU
     *
     * Yang diuji bukan "sync jalan", melainkan JEJAKNYA. IKU yang tersalin
     * tanpa mencatat asalnya tetap tampak benar di layar — kekeliruannya baru
     * muncul bertahun kemudian, ketika seseorang bertanya angka ini dulu
     * diambil dari Renstra yang mana dan tidak ada satu pun baris yang bisa
     * menjawabnya.
     * =======================================================*/
    private function caseSyncIkuDariVersi(): void
    {
        CLI::write(CLI::color('Sync IKU dari versi Renstra tertentu', 'cyan'));

        $opd    = $this->opdUji('UJI-VERSI OPD Iku');
        [, $rs] = $this->siapkanRpjmdUji();
        $tuj    = $this->sisipUji('renstra_tujuan', [
            'rpjmd_sasaran_id' => $rs, 'tujuan' => 'UJI-VERSI tujuan iku',
        ]);
        $sasaranLive = $this->sisipRenstraSasaran($opd, $tuj, 'UJI-VERSI sasaran iku');

        $indLive = (int) $this->db->table('renstra_indikator_sasaran')
            ->select('id')->where('renstra_sasaran_id', $sasaranLive)
            ->get()->getRowArray()['id'];

        $scope = VersionScope::renstra($opd, self::MULAI, self::AKHIR);
        $arsip = new RenstraVersiModel($this->db);

        $vId = $this->model->sisipkan(array_merge($scope->kolomBaru(), [
            'version_no' => 1, 'label' => 'V1 — sumber IKU',
            'effective_from' => self::MULAI . '-01-01',
            'status' => DokumenVersiModel::STATUS_PUBLISHED, 'created_by' => 11,
        ]));

        $this->dalamTransaksiUji(fn () => $arsip->bekukanDariLive($vId, $scope));

        $iku = new \App\Models\Opd\IkuModel($this->db);

        // --- versi ditawarkan sebagai sumber ---
        $tersedia = $iku->versiRenstraTersedia($opd, self::MULAI, self::AKHIR);
        $this->cek('versi published berisi ditawarkan sebagai sumber',
            count($tersedia) === 1 && (int) $tersedia[0]['id'] === $vId);
        $this->cek('jumlah sasaran versi ikut dilaporkan',
            (int) $tersedia[0]['jumlah_sasaran'] === 1);

        // --- kandidat dari ARSIP membawa id berjalan sebagai jejak ---
        $kandidat = $iku->getKandidatSync('renstra', $opd, self::MULAI, self::AKHIR, $vId);
        $this->cek('kandidat terbaca dari arsip versi', count($kandidat) === 1);

        $sasaranArsip = $kandidat[0];
        $indArsip     = $sasaranArsip['indikator'][0] ?? null;

        $this->cek('sasaran arsip menyimpan id berjalan asalnya',
            (int) $sasaranArsip['sumber_live_id'] === $sasaranLive);
        $this->cek('indikator arsip menyimpan id berjalan asalnya',
            $indArsip !== null && (int) $indArsip['sumber_live_id'] === $indLive);
        $this->cek('sumber_id arsip BUKAN id berjalan',
            (int) $sasaranArsip['sumber_id'] !== $sasaranLive);
        $this->cek('target ikut terbaca dari arsip',
            ! empty($indArsip['target'][self::MULAI]));

        // --- impor: jejak asal tercatat ---
        $pilihan = [(int) $sasaranArsip['sumber_id'] => [(int) $indArsip['sumber_id']]];
        $stat    = $iku->importSync('renstra', $opd, $pilihan, self::MULAI, self::AKHIR, $vId);

        $this->cek('satu sasaran & satu indikator tersalin',
            ($stat['sasaran_baru'] ?? 0) === 1 && ($stat['indikator_baru'] ?? 0) === 1);

        $ikuSasaran = $this->db->table('iku_sasaran')->where('opd_id', $opd)->get()->getRowArray();
        $ikuInd     = $this->db->table('iku_indikator')
            ->where('iku_sasaran_id', $ikuSasaran['id'])->get()->getRowArray();

        $this->cek('sasaran IKU mencatat modul asalnya',
            ($ikuSasaran['source_type'] ?? null) === 'renstra');
        $this->cek('sasaran IKU mencatat VERSI asalnya',
            (int) ($ikuSasaran['source_version_id'] ?? 0) === $vId);
        $this->cek('sasaran IKU menunjuk baris berjalan asalnya',
            (int) ($ikuSasaran['source_sasaran_id'] ?? 0) === $sasaranLive);
        $this->cek('indikator IKU mencatat versi & baris berjalan asalnya',
            (int) ($ikuInd['source_version_id'] ?? 0) === $vId
            && (int) ($ikuInd['source_indikator_id'] ?? 0) === $indLive);

        // --- menyunting IKU TIDAK BOLEH menghapus jejaknya ---
        $iku->updateComplete((int) $ikuSasaran['id'], [
            'sasaran'     => 'UJI-VERSI sasaran iku (disunting)',
            'tahun_mulai' => self::MULAI,
            'tahun_akhir' => self::AKHIR,
            'indikator'   => [[
                'id'        => (int) $ikuInd['id'],
                'indikator' => 'UJI-VERSI indikator disunting',
                'target'    => [self::MULAI => '80'],
            ]],
        ]);

        $indSesudah = $this->db->table('iku_indikator')->where('id', $ikuInd['id'])->get()->getRowArray();

        $this->cek('menyunting indikator TIDAK menghapus jejak asalnya',
            (int) ($indSesudah['source_version_id'] ?? 0) === $vId
            && (int) ($indSesudah['source_indikator_id'] ?? 0) === $indLive);

        // --- sumber berjalan: baris pensiun tidak ikut ditawarkan ---
        $this->db->table('renstra_indikator_sasaran')->where('id', $indLive)
            ->update(['dihentikan_pada' => date('Y-m-d H:i:s')]);

        $berjalan = $iku->getKandidatSync('renstra', $opd, self::MULAI, self::AKHIR);
        $jumlahInd = 0;

        foreach ($berjalan as $s) {
            $jumlahInd += count($s['indikator'] ?? []);
        }

        $this->cek('indikator yang dipensiunkan tidak ditawarkan dari kondisi berjalan',
            $jumlahInd === 0);
        $this->cek('arsip versi TIDAK ikut terpengaruh pensiun di data berjalan',
            count($iku->getKandidatSync('renstra', $opd, self::MULAI, self::AKHIR, $vId)[0]['indikator']) === 1);

        // --- bersih-bersih ---
        $this->db->table('iku_target')->where('iku_indikator_id', $ikuInd['id'])->delete();
        $this->db->table('iku_indikator')->where('iku_sasaran_id', $ikuSasaran['id'])->delete();
        $this->db->table('iku_sasaran')->where('opd_id', $opd)->delete();

        $this->hapusLingkup($scope);
        $this->bersihkanRenstraUji();
        $this->bersihkanRpjmdUji();
    }

    /* =========================================================
     * PENGESAHAN REVISI IKU OPD LEWAT ADMIN KABUPATEN
     *
     * Sebelum ini jalurnya BUNTU: `iku_opd.revisi_sahkan` dicabut dari OPD
     * supaya pengesahan berpindah ke Kabupaten, tetapi jalur Kabupatennya
     * tidak pernah dibuat. OPD bisa menyusun revisi lalu mentok selamanya —
     * tanpa satu pun pesan galat, karena tombolnya memang tidak muncul.
     * =======================================================*/
    private function caseVerifikasiRevisiIku(): void
    {
        CLI::write(CLI::color('Pengesahan revisi IKU OPD', 'cyan'));

        $rev = new \App\Models\Opd\IkuRevisiModel($this->db);

        if (! $rev->siap()) {
            CLI::write('  ' . CLI::color('LEWAT', 'yellow') . '  tabel iku_revisi belum ada.');

            return;
        }

        $opd = $this->opdUji('UJI-VERSI OPD RevIku');
        // IKU berjalan sederhana sebagai bahan revisi.
        $sasaranId = $this->sisipUji('iku_sasaran', [
            'opd_id' => $opd, 'sasaran' => 'UJI-VERSI sasaran iku rev',
            'tahun_mulai' => self::MULAI, 'tahun_akhir' => self::AKHIR, 'urutan' => 0,
        ]);
        $indId = $this->sisipUji('iku_indikator', [
            'iku_sasaran_id' => $sasaranId, 'indikator' => 'UJI-VERSI indikator iku rev',
            'satuan' => 'Persen', 'urutan' => 0, 'status' => 'selesai',
        ]);
        $this->db->table('iku_target')->insert([
            'iku_indikator_id' => $indId, 'tahun' => self::MULAI, 'target' => '70',
            'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $revisiId = $rev->buatDraft([
            'opd_id'              => $opd,
            'tahun_mulai'         => self::MULAI,
            'tahun_akhir'         => self::AKHIR,
            'nomor'               => 'UJI-VERSI/001',
            'nama'                => 'Revisi uji',
            // BUKAN tahun awal periode: pembuatan draft ikut membekukan IKU
            // berjalan sebagai revisi dasar yang sudah memakai tahun itu, dan
            // dua revisi tidak boleh mulai berlaku pada tahun yang sama.
            'berlaku_mulai_tahun' => self::MULAI + 1,
            'dibuat_oleh'         => 11,
        ]);

        $this->cek('revisi lahir sebagai draft',
            $rev->ambil($revisiId)['status'] === \App\Models\Opd\IkuRevisiModel::STATUS_DRAFT);
        $this->cek('draft belum muncul di antrean verifikasi',
            $this->antreanIkuMemuat($rev, $revisiId) === false);

        // --- diajukan ---
        $rev->ajukan($revisiId, 11);

        $sesudah = $rev->ambil($revisiId);
        $this->cek('status menjadi menunggu',
            $sesudah['status'] === \App\Models\Opd\IkuRevisiModel::STATUS_MENUNGGU);
        $this->cek('pengaju dan waktunya tercatat',
            (int) $sesudah['submitted_by'] === 11 && ! empty($sesudah['submitted_at']));
        $this->cek('muncul di antrean verifikasi Kabupaten',
            $this->antreanIkuMemuat($rev, $revisiId) === true);

        // --- yang tidak boleh ---
        $this->cekMelempar('mengajukan dua kali DITOLAK', static function () use ($rev, $revisiId) {
            $rev->ajukan($revisiId, 11);
        });

        $this->cekMelempar('membatalkan saat menggantung DITOLAK', static function () use ($rev, $revisiId) {
            $rev->batalkan($revisiId, 11);
        });

        $this->cekMelempar('mengembalikan tanpa catatan DITOLAK', static function () use ($rev, $revisiId) {
            $rev->kembalikan($revisiId, '   ', 22);
        });

        // --- dikembalikan verifikator ---
        $rev->kembalikan($revisiId, 'Sertakan rumusan perhitungannya.', 22);

        $kembali = $rev->ambil($revisiId);
        $this->cek('dikembalikan menjadi draft lagi',
            $kembali['status'] === \App\Models\Opd\IkuRevisiModel::STATUS_DRAFT);
        $this->cek('catatan pengembalian tersimpan',
            str_contains((string) $kembali['catatan'], 'rumusan perhitungannya'));
        $this->cek('jejak pengajuan dibersihkan saat dikembalikan',
            $kembali['submitted_at'] === null);
        $this->cek('keluar dari antrean setelah dikembalikan',
            $this->antreanIkuMemuat($rev, $revisiId) === false);

        // --- diajukan ulang lalu disahkan ---
        $rev->ajukan($revisiId, 11);
        $rev->tarikPengajuan($revisiId);
        $this->cek('pengajuan bisa ditarik penyusunnya',
            $rev->ambil($revisiId)['status'] === \App\Models\Opd\IkuRevisiModel::STATUS_DRAFT);

        $this->cekMelempar('menarik yang tidak menggantung DITOLAK', static function () use ($rev, $revisiId) {
            $rev->tarikPengajuan($revisiId);
        });

        $rev->ajukan($revisiId, 11);
        $hasil = $rev->sahkan($revisiId, 22);

        $this->cek('revisi menunggu BISA disahkan verifikator',
            $rev->ambil($revisiId)['status'] === \App\Models\Opd\IkuRevisiModel::STATUS_BERLAKU);
        $this->cek('pengesahan mengembalikan ringkasan dampaknya',
            isset($hasil['revisi_id'], $hasil['digeser']));
        $this->cek('keluar dari antrean setelah disahkan',
            $this->antreanIkuMemuat($rev, $revisiId) === false);

        // --- bersih-bersih ---
        // Hanya iku_revisi_sasaran & _indikator yang punya `revisi_id`;
        // target dan program menggantung pada indikatornya, jadi dibuang lewat
        // induknya. Membuang berdasarkan OPD, bukan satu revisi, sebab
        // pembuatan draft ikut melahirkan revisi dasar.
        $idRevisi = array_column(
            $this->db->table('iku_revisi')->select('id')->where('opd_id', $opd)->get()->getResultArray(),
            'id'
        );

        if ($idRevisi !== []) {
            $idInd = array_column(
                $this->db->table('iku_revisi_indikator')->select('id')
                    ->whereIn('revisi_id', $idRevisi)->get()->getResultArray(),
                'id'
            );

            if ($idInd !== []) {
                $this->db->table('iku_revisi_target')->whereIn('revisi_indikator_id', $idInd)->delete();
                $this->db->table('iku_revisi_program')->whereIn('revisi_indikator_id', $idInd)->delete();
            }

            $this->db->table('iku_revisi_indikator')->whereIn('revisi_id', $idRevisi)->delete();
            $this->db->table('iku_revisi_sasaran')->whereIn('revisi_id', $idRevisi)->delete();
        }

        $this->db->table('iku_revisi')->where('opd_id', $opd)->delete();
        $this->db->table('iku_target')->where('iku_indikator_id', $indId)->delete();
        $this->db->table('iku_indikator')->where('iku_sasaran_id', $sasaranId)->delete();
        $this->db->table('iku_sasaran')->where('opd_id', $opd)->delete();
        $this->bersihkanRenstraUji();
    }

    private function antreanIkuMemuat(\App\Models\Opd\IkuRevisiModel $rev, int $revisiId): bool
    {
        foreach ($rev->menungguVerifikasi() as $r) {
            if ((int) $r['id'] === $revisiId) {
                return true;
            }
        }

        return false;
    }

    /* =========================================================
     * SYNC BERMUARA KE DRAFT REVISI, DAN JEJAKNYA SELAMAT
     *
     * Dua hal yang diuji, dan keduanya gagal tanpa gejala bila salah:
     *
     *   1. Sesudah ada revisi berlaku, sync TIDAK BOLEH menyentuh tabel live.
     *      Kalau bocor, dokumen resmi berubah tanpa persetujuan siapa pun dan
     *      tambahannya tidak muncul di arsip revisi mana pun.
     *
     *   2. Jejak "dari Renstra versi berapa" harus selamat melewati
     *      pembekuan DAN penerapan. Kalau hilang, kolomnya cuma jadi NULL —
     *      tidak ada galat, tidak ada yang tahu ia pernah terisi.
     * =======================================================*/
    private function caseSyncKeDraftRevisi(): void
    {
        CLI::write(CLI::color('Sync IKU bermuara ke draft revisi', 'cyan'));

        $rev = new \App\Models\Opd\IkuRevisiModel($this->db);
        $iku = new \App\Models\Opd\IkuModel($this->db);

        if (! $rev->siap() || ! $this->db->fieldExists('source_ref_id', 'iku_revisi_indikator')) {
            CLI::write('  ' . CLI::color('LEWAT', 'yellow')
                . '  jalankan db/update_2026-08-25_jejak_sumber_revisi_iku.sql lebih dulu.');

            return;
        }

        $opd    = $this->opdUji('UJI-VERSI OPD Muara');
        [, $rs] = $this->siapkanRpjmdUji();
        $tuj    = $this->sisipUji('renstra_tujuan', [
            'rpjmd_sasaran_id' => $rs, 'tujuan' => 'UJI-VERSI tujuan muara',
        ]);
        $this->sisipRenstraSasaran($opd, $tuj, 'UJI-VERSI sasaran muara');

        $scope = VersionScope::renstra($opd, self::MULAI, self::AKHIR);
        $arsip = new RenstraVersiModel($this->db);

        $vId = $this->model->sisipkan(array_merge($scope->kolomBaru(), [
            'version_no' => 1, 'label' => 'V1 — sumber muara',
            'effective_from' => self::MULAI . '-01-01',
            'status' => DokumenVersiModel::STATUS_PUBLISHED, 'created_by' => 11,
        ]));
        $this->dalamTransaksiUji(fn () => $arsip->bekukanDariLive($vId, $scope));

        // --- tahap 1: belum ada revisi berlaku -> sync ke tabel berjalan ---
        $this->cek('belum ada revisi berlaku', $rev->revisiBerlaku($opd, self::MULAI, self::AKHIR) === null);

        $kandidat = $iku->getKandidatSync('renstra', $opd, self::MULAI, self::AKHIR, $vId);
        $pilihan  = [
            (int) $kandidat[0]['sumber_id'] => [(int) $kandidat[0]['indikator'][0]['sumber_id']],
        ];
        $iku->importSync('renstra', $opd, $pilihan, self::MULAI, self::AKHIR, $vId);

        $ikuSasaran = $this->db->table('iku_sasaran')->where('opd_id', $opd)->get()->getRowArray();
        $this->cek('sync pertama masuk ke tabel berjalan', $ikuSasaran !== null);

        // --- tahap 2: revisi dibuat & disahkan -> IKU jadi resmi ---
        $revisiId = $rev->buatDraft([
            'opd_id' => $opd, 'tahun_mulai' => self::MULAI, 'tahun_akhir' => self::AKHIR,
            'nomor' => 'UJI/001', 'nama' => 'Revisi muara',
            'berlaku_mulai_tahun' => self::MULAI + 1, 'dibuat_oleh' => 11,
        ]);

        // Baseline otomatis membekukan IKU berjalan — jejaknya harus ikut.
        $arsipBaseline = $this->db->table('iku_revisi_indikator')
            ->where('source_version_id', $vId)->get()->getRowArray();

        $this->cek('pembekuan ke arsip MEMBAWA jejak versi Renstra',
            $arsipBaseline !== null);
        $this->cek('arsip menunjuk baris Renstra berjalan asalnya',
            $arsipBaseline !== null && ! empty($arsipBaseline['source_ref_id']));

        $this->cek('sekarang ada revisi berlaku',
            $rev->revisiBerlaku($opd, self::MULAI, self::AKHIR) !== null);
        $this->cek('draft tersedia untuk menampung sync',
            count($rev->draftTersedia($opd, self::MULAI, self::AKHIR)) === 1);

        // --- tahap 3: sync berikutnya WAJIB ke draft ---
        $sebelum = (int) $this->db->table('iku_indikator')
            ->join('iku_sasaran', 'iku_sasaran.id = iku_indikator.iku_sasaran_id')
            ->where('iku_sasaran.opd_id', $opd)->countAllResults();

        // Indikator kedua di Renstra, supaya ada yang benar-benar baru.
        $indBaru = $this->sisipUji('renstra_indikator_sasaran', [
            'renstra_sasaran_id' => (int) $this->db->table('renstra_sasaran')
                ->select('id')->where('opd_id', $opd)->get()->getRowArray()['id'],
            'indikator_sasaran'  => 'UJI-VERSI indikator kedua',
            'satuan' => $this->satuanUji(), 'baseline' => '0', 'jenis_indikator' => 'positif',
        ]);

        $kandidat2 = $iku->getKandidatSync('renstra', $opd, self::MULAI, self::AKHIR);
        $pilihan2  = [];

        foreach ($kandidat2 as $s) {
            $pilihan2[(int) $s['sumber_id']] = array_column($s['indikator'], 'sumber_id');
        }

        $stat = $rev->imporKandidat($revisiId, $kandidat2, $pilihan2, 'renstra', null);

        $sesudah = (int) $this->db->table('iku_indikator')
            ->join('iku_sasaran', 'iku_sasaran.id = iku_indikator.iku_sasaran_id')
            ->where('iku_sasaran.opd_id', $opd)->countAllResults();

        $this->cek('impor ke draft TIDAK menyentuh tabel berjalan', $sebelum === $sesudah);
        $this->cek('indikator baru masuk ke arsip draft', ($stat['indikator_baru'] ?? 0) >= 1);
        $this->cek('yang sudah ada di draft dilewati, bukan digandakan',
            ($stat['dilewati'] ?? 0) >= 1);

        // --- tahap 4: draft disahkan -> jejak sampai ke tabel berjalan ---
        $rev->ajukan($revisiId, 11);
        $rev->sahkan($revisiId, 22);

        $liveBaru = $this->db->table('iku_indikator ind')
            ->select('ind.*')
            ->join('iku_sasaran s', 's.id = ind.iku_sasaran_id')
            ->where('s.opd_id', $opd)
            ->where('ind.indikator', 'UJI-VERSI indikator kedua')
            ->get()->getRowArray();

        $this->cek('indikator draft diterapkan ke tabel berjalan', $liveBaru !== null);
        $this->cek('jejak sumber ikut sampai ke tabel berjalan',
            $liveBaru !== null && ($liveBaru['source_type'] ?? null) === 'renstra');

        $liveLama = $this->db->table('iku_indikator ind')
            ->select('ind.*')
            ->join('iku_sasaran s', 's.id = ind.iku_sasaran_id')
            ->where('s.opd_id', $opd)
            ->where('ind.source_version_id', $vId)
            ->get()->getRowArray();

        $this->cek('jejak versi Renstra baris lama SELAMAT melewati pengesahan',
            $liveLama !== null);

        // --- impor ke revisi yang sudah disahkan DITOLAK ---
        $this->cekMelempar('impor ke revisi yang sudah berlaku DITOLAK',
            static function () use ($rev, $revisiId, $kandidat2, $pilihan2) {
                $rev->imporKandidat($revisiId, $kandidat2, $pilihan2, 'renstra', null);
            });

        $this->bersihkanIkuUji($opd);
        $this->hapusLingkup($scope);
        $this->bersihkanRenstraUji();
        $this->bersihkanRpjmdUji();
    }

    /** Buang seluruh jejak IKU milik satu OPD uji, beserta arsip revisinya. */
    private function bersihkanIkuUji(int $opd): void
    {
        $idRevisi = array_column(
            $this->db->table('iku_revisi')->select('id')->where('opd_id', $opd)->get()->getResultArray(),
            'id'
        );

        if ($idRevisi !== []) {
            $idInd = array_column(
                $this->db->table('iku_revisi_indikator')->select('id')
                    ->whereIn('revisi_id', $idRevisi)->get()->getResultArray(),
                'id'
            );

            if ($idInd !== []) {
                $this->db->table('iku_revisi_target')->whereIn('revisi_indikator_id', $idInd)->delete();
                $this->db->table('iku_revisi_program')->whereIn('revisi_indikator_id', $idInd)->delete();
            }

            $this->db->table('iku_revisi_indikator')->whereIn('revisi_id', $idRevisi)->delete();
            $this->db->table('iku_revisi_sasaran')->whereIn('revisi_id', $idRevisi)->delete();
            $this->db->table('iku_revisi')->whereIn('id', $idRevisi)->delete();
        }

        $idSasaran = array_column(
            $this->db->table('iku_sasaran')->select('id')->where('opd_id', $opd)->get()->getResultArray(),
            'id'
        );

        if ($idSasaran !== []) {
            $idInd = array_column(
                $this->db->table('iku_indikator')->select('id')
                    ->whereIn('iku_sasaran_id', $idSasaran)->get()->getResultArray(),
                'id'
            );

            if ($idInd !== []) {
                $this->db->table('iku_target')->whereIn('iku_indikator_id', $idInd)->delete();
                $this->db->table('iku_indikator')->whereIn('iku_sasaran_id', $idSasaran)->delete();
            }

            $this->db->table('iku_sasaran')->whereIn('id', $idSasaran)->delete();
        }
    }

    /* =========================================================
     * SELISIH IKU TERHADAP SEBUAH VERSI RENSTRA
     *
     * Sebelumnya sync hanya mengenal dua keadaan: "baru" dan "sudah ada".
     * Akibatnya target yang berubah di Renstra TIDAK PERNAH terlihat — sync
     * melewatinya sebagai "sudah ada", dan penyusun tidak punya cara mengetahui
     * ada selisih selain membandingkan sendiri baris demi baris.
     * =======================================================*/
    private function caseSelisihIkuRenstra(): void
    {
        CLI::write(CLI::color('Selisih IKU terhadap versi Renstra', 'cyan'));

        $opd    = $this->opdUji('UJI-VERSI OPD Selisih');
        [, $rs] = $this->siapkanRpjmdUji();
        $tuj    = $this->sisipUji('renstra_tujuan', [
            'rpjmd_sasaran_id' => $rs, 'tujuan' => 'UJI-VERSI tujuan selisih',
        ]);
        $sasaranLive = $this->sisipRenstraSasaran($opd, $tuj, 'UJI-VERSI sasaran selisih');

        $indLive = (int) $this->db->table('renstra_indikator_sasaran')
            ->select('id')->where('renstra_sasaran_id', $sasaranLive)
            ->get()->getRowArray()['id'];

        $iku = new \App\Models\Opd\IkuModel($this->db);

        // Salin dulu apa adanya, supaya IKU dan Renstra berangkat identik.
        $kandidat = $iku->getKandidatSync('renstra', $opd, self::MULAI, self::AKHIR);
        $iku->importSync('renstra', $opd, [
            (int) $kandidat[0]['sumber_id'] => [(int) $kandidat[0]['indikator'][0]['sumber_id']],
        ], self::MULAI, self::AKHIR);

        $awal = $iku->getKandidatSync('renstra', $opd, self::MULAI, self::AKHIR);
        $this->cek('sesudah disalin, indikator dinilai SAMA',
            ($awal[0]['indikator'][0]['banding'] ?? null) === 'sama');
        $this->cek('tidak ada yang dihitung baru', (int) $awal[0]['jumlah_baru'] === 0);
        $this->cek('tidak ada IKU tanpa padanan', $iku->ikuTanpaPadananSumber() === []);

        // --- target Renstra digeser ---
        $this->db->table('renstra_target')
            ->where('renstra_indikator_id', $indLive)->where('tahun', self::MULAI)
            ->update(['target' => '99']);

        $geser = $iku->getKandidatSync('renstra', $opd, self::MULAI, self::AKHIR);
        $ind   = $geser[0]['indikator'][0];

        $this->cek('target yang bergeser terbaca BERUBAH', $ind['banding'] === 'berubah');
        $this->cek('selisihnya menyebut tahun & kedua nilainya',
            ($ind['selisih']['target ' . self::MULAI]['iku'] ?? null) === '75'
            && ($ind['selisih']['target ' . self::MULAI]['sumber'] ?? null) === '99');
        $this->cek('yang berubah TIDAK dihitung sebagai baru', (int) $geser[0]['jumlah_baru'] === 0);
        $this->cek('jumlah berubah dilaporkan', (int) $geser[0]['jumlah_berubah'] === 1);

        // --- baseline juga dibandingkan ---
        $this->db->table('renstra_indikator_sasaran')->where('id', $indLive)
            ->update(['baseline' => '12']);

        $ind2 = $iku->getKandidatSync('renstra', $opd, self::MULAI, self::AKHIR)[0]['indikator'][0];
        $this->cek('baseline yang berbeda ikut terbaca',
            isset($ind2['selisih']['baseline']));

        // --- pencocokan lewat SILSILAH, bukan teks ---
        $this->db->table('renstra_indikator_sasaran')->where('id', $indLive)
            ->update(['indikator_sasaran' => 'UJI-VERSI indikator DIRAPIKAN']);

        $ind3 = $iku->getKandidatSync('renstra', $opd, self::MULAI, self::AKHIR)[0]['indikator'][0];
        $this->cek('redaksi berubah TIDAK membuatnya tampak sebagai indikator baru',
            $ind3['banding'] === 'berubah');
        $this->cek('padanannya tetap indikator IKU yang sama',
            ! empty($ind3['iku_id']));

        // --- indikator IKU yang tidak ada di sumber ---
        $sasaranIku = (int) $this->db->table('iku_sasaran')
            ->select('id')->where('opd_id', $opd)->get()->getRowArray()['id'];

        $this->sisipUji('iku_indikator', [
            'iku_sasaran_id' => $sasaranIku, 'indikator' => 'UJI-VERSI indikator khas IKU',
            'satuan' => $this->satuanUji(), 'urutan' => 5, 'status' => 'draft',
        ]);

        $iku->getKandidatSync('renstra', $opd, self::MULAI, self::AKHIR);
        $tanpa = $iku->ikuTanpaPadananSumber();

        $this->cek('indikator khas IKU dilaporkan tanpa padanan', count($tanpa) === 1);
        $this->cek('yang dilaporkan memang indikator itu',
            ($tanpa[0]['indikator'] ?? '') === 'UJI-VERSI indikator khas IKU');
        $this->cek('ditandai bukan berasal dari dokumen perencanaan',
            ($tanpa[0]['dari_sumber'] ?? true) === false);

        $this->bersihkanIkuUji($opd);
        $this->bersihkanRenstraUji();
        $this->bersihkanRpjmdUji();
    }

    /* =========================================================
     * PRATINJAU PENGESAHAN REVISI IKU
     *
     * Yang paling perlu dijamin bukan daftar "baru" dan "berubah" — itu
     * terbaca juga dari dokumennya. Yang hanya bisa diketahui dari sini adalah
     * daftar DIPENSIUNKAN: indikator yang hilang justru karena tidak tertulis
     * di dokumen yang sedang dibaca.
     * =======================================================*/
    private function casePraTinjauRevisiIku(): void
    {
        CLI::write(CLI::color('Pratinjau pengesahan revisi IKU', 'cyan'));

        $rev = new \App\Models\Opd\IkuRevisiModel($this->db);

        if (! $rev->siap()) {
            CLI::write('  ' . CLI::color('LEWAT', 'yellow') . '  tabel iku_revisi belum ada.');

            return;
        }

        $opd = $this->opdUji('UJI-VERSI OPD Pratinjau');

        $sasaranId = $this->sisipUji('iku_sasaran', [
            'opd_id' => $opd, 'sasaran' => 'UJI-VERSI sasaran pratinjau',
            'tahun_mulai' => self::MULAI, 'tahun_akhir' => self::AKHIR, 'urutan' => 0,
        ]);

        $indA = $this->sisipUji('iku_indikator', [
            'iku_sasaran_id' => $sasaranId, 'indikator' => 'UJI-VERSI indikator A',
            'satuan' => $this->satuanUji(), 'baseline' => '1', 'urutan' => 0, 'status' => 'selesai',
        ]);
        $indB = $this->sisipUji('iku_indikator', [
            'iku_sasaran_id' => $sasaranId, 'indikator' => 'UJI-VERSI indikator B',
            'satuan' => $this->satuanUji(), 'baseline' => '2', 'urutan' => 1, 'status' => 'selesai',
        ]);

        foreach ([$indA => '70', $indB => '80'] as $id => $nilai) {
            $this->db->table('iku_target')->insert([
                'iku_indikator_id' => $id, 'tahun' => self::MULAI, 'target' => $nilai,
                'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }

        // Membuat draft ikut membekukan IKU berjalan sebagai revisi dasar.
        $draftId = $rev->buatDraft([
            'opd_id' => $opd, 'tahun_mulai' => self::MULAI, 'tahun_akhir' => self::AKHIR,
            'nomor' => 'UJI/002', 'nama' => 'Revisi pratinjau',
            'berlaku_mulai_tahun' => self::MULAI + 1, 'dibuat_oleh' => 11,
        ]);

        $awal = $rev->praTinjauPengesahan($draftId);

        $this->cek('pembanding = revisi dasar yang sedang berlaku',
            $awal['pembanding'] !== null);
        $this->cek('salinan apa adanya: tidak ada yang baru maupun berubah',
            $awal['baru'] === [] && $awal['berubah'] === []);
        $this->cek('tidak ada yang dipensiunkan', $awal['dihentikan'] === []);
        $this->cek('tahun mulai berlakunya terbaca',
            (int) $awal['tahun']['mulai'] === self::MULAI + 1);

        // --- indikator B dibuang dari draft -> harus terbaca DIPENSIUNKAN ---
        $arsipB = $this->db->table('iku_revisi_indikator')
            ->where('revisi_id', $draftId)->where('sumber_indikator_id', $indB)
            ->get()->getRowArray();

        $this->db->table('iku_revisi_target')
            ->where('revisi_indikator_id', (int) $arsipB['id'])->delete();
        $this->db->table('iku_revisi_indikator')->where('id', (int) $arsipB['id'])->delete();

        $buang = $rev->praTinjauPengesahan($draftId);

        $this->cek('indikator yang dibuang terbaca akan DIPENSIUNKAN',
            count($buang['dihentikan']) === 1);
        $this->cek('yang dilaporkan memang indikator B',
            ($buang['dihentikan'][0]['indikator'] ?? '') === 'UJI-VERSI indikator B');

        // --- target A digeser -> terbaca BERUBAH, bukan baru ---
        $arsipA = $this->db->table('iku_revisi_indikator')
            ->where('revisi_id', $draftId)->where('sumber_indikator_id', $indA)
            ->get()->getRowArray();

        $this->db->table('iku_revisi_target')
            ->where('revisi_indikator_id', (int) $arsipA['id'])->where('tahun', self::MULAI)
            ->update(['target' => '95']);

        $ubah = $rev->praTinjauPengesahan($draftId);

        $this->cek('target yang digeser terbaca BERUBAH', count($ubah['berubah']) === 1);
        $this->cek('selisihnya menyebut kedua nilainya',
            ($ubah['berubah'][0]['selisih']['target ' . self::MULAI]['lama'] ?? null) === '70'
            && ($ubah['berubah'][0]['selisih']['target ' . self::MULAI]['baru'] ?? null) === '95');
        $this->cek('yang berubah tidak ikut terhitung baru', $ubah['baru'] === []);

        // --- redaksi dirapikan: tetap dianggap indikator yang sama ---
        $this->db->table('iku_revisi_indikator')->where('id', (int) $arsipA['id'])
            ->update(['indikator' => 'UJI-VERSI indikator A (dirapikan)']);

        $rapi = $rev->praTinjauPengesahan($draftId);

        $this->cek('redaksi dirapikan TIDAK terbaca sebagai indikator baru',
            $rapi['baru'] === []);
        $this->cek('dan TIDAK terbaca sebagai dipensiunkan lalu muncul lagi',
            count($rapi['dihentikan']) === 1);

        // --- indikator benar-benar baru ---
        $this->db->table('iku_revisi_indikator')->insert([
            'revisi_id' => $draftId, 'revisi_sasaran_id' => (int) $arsipA['revisi_sasaran_id'],
            'sumber_indikator_id' => null, 'indikator' => 'UJI-VERSI indikator C',
            'satuan' => $this->satuanUji(), 'urutan' => 9, 'status' => 'draft',
            'jenis_perubahan' => 'baru',
            'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $tambah = $rev->praTinjauPengesahan($draftId);
        $this->cek('indikator tanpa silsilah terbaca BARU', count($tambah['baru']) === 1);

        $this->bersihkanIkuUji($opd);
        $this->bersihkanRenstraUji();
    }

    /* =========================================================
     * MENGAJUKAN PENGESAHAN IKU LANGSUNG DARI ISI BERJALAN
     *
     * Sebelum ini satu-satunya pintu menuju pengesahan adalah menu Revisi —
     * dan menu itu KOSONG sampai seseorang membuat revisi. IKU hasil sync
     * karena itu tidak punya jalan sama sekali menuju verifikasi, meski
     * seluruh mesinnya sudah ada.
     * =======================================================*/
    private function caseAjukanPengesahanIku(): void
    {
        CLI::write(CLI::color('Ajukan pengesahan IKU dari isi berjalan', 'cyan'));

        $rev = new \App\Models\Opd\IkuRevisiModel($this->db);

        if (! $rev->siap()) {
            CLI::write('  ' . CLI::color('LEWAT', 'yellow') . '  tabel iku_revisi belum ada.');

            return;
        }

        $opd = $this->opdUji('UJI-VERSI OPD AjukanIku');

        // --- IKU masih kosong: pengajuan ditolak ---
        $this->cekMelempar('IKU kosong tidak bisa diajukan', static function () use ($rev, $opd) {
            $rev->bekukanDanAjukan($opd, self::MULAI, self::AKHIR, 11);
        });

        $sasaranId = $this->sisipUji('iku_sasaran', [
            'opd_id' => $opd, 'sasaran' => 'UJI-VERSI sasaran ajukan',
            'tahun_mulai' => self::MULAI, 'tahun_akhir' => self::AKHIR, 'urutan' => 0,
        ]);
        $indId = $this->sisipUji('iku_indikator', [
            'iku_sasaran_id' => $sasaranId, 'indikator' => 'UJI-VERSI indikator ajukan',
            'satuan' => $this->satuanUji(), 'urutan' => 0, 'status' => 'selesai',
        ]);
        $this->db->table('iku_target')->insert([
            'iku_indikator_id' => $indId, 'tahun' => self::MULAI, 'target' => '70',
            'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->cek('belum ada pengajuan menggantung',
            $rev->berjalanMenunggu($opd, self::MULAI, self::AKHIR) === null);

        $revisiId = $rev->bekukanDanAjukan($opd, self::MULAI, self::AKHIR, 11);
        $revisi   = $rev->ambil($revisiId);

        $this->cek('revisi lahir langsung berstatus menunggu',
            $revisi['status'] === \App\Models\Opd\IkuRevisiModel::STATUS_MENUNGGU);
        $this->cek('TIDAK langsung berlaku tanpa diperiksa siapa pun',
            $rev->revisiBerlaku($opd, self::MULAI, self::AKHIR) === null);
        $this->cek('pengaju tercatat', (int) $revisi['submitted_by'] === 11);
        $this->cek('muncul di antrean verifikasi Kabupaten',
            $this->antreanIkuMemuat($rev, $revisiId));

        // Isinya benar-benar dibekukan, bukan sekadar kepala revisi.
        $isi = $rev->isiRevisi($revisiId);
        $this->cek('isi IKU ikut dibekukan ke arsip',
            count($isi) === 1 && count($isi[0]['indikator']) === 1);

        // --- pengajuan kedua ditolak selagi yang pertama menggantung ---
        $this->cekMelempar('pengajuan kedua saat masih menggantung DITOLAK',
            static function () use ($rev, $opd) {
                $rev->bekukanDanAjukan($opd, self::MULAI, self::AKHIR, 11);
            });

        // --- disahkan: menjadi berlaku ---
        $rev->sahkan($revisiId, 22);
        $this->cek('sesudah disahkan menjadi berlaku',
            (int) ($rev->revisiBerlaku($opd, self::MULAI, self::AKHIR)['id'] ?? 0) === $revisiId);


        // --- sesudah ada yang berlaku, pintu pengajuan dari menu IKU TERTUTUP ---
        $this->cekMelempar('mengajukan lagi sesudah ada revisi berlaku DITOLAK',
            static function () use ($rev, $opd) {
                $rev->bekukanDanAjukan($opd, self::MULAI, self::AKHIR, 11);
            });

        $sebelumJumlah = (int) $this->db->table('iku_revisi')->where('opd_id', $opd)->countAllResults();

        try {
            $rev->bekukanDanAjukan($opd, self::MULAI, self::AKHIR, 11);
        } catch (Throwable $e) {
            // memang harus gagal
        }

        $this->cek('penolakan itu tidak meninggalkan revisi setengah jadi',
            (int) $this->db->table('iku_revisi')->where('opd_id', $opd)->countAllResults() === $sebelumJumlah);

        $this->bersihkanIkuUji($opd);
        $this->bersihkanRenstraUji();
    }

    /* =========================================================
     * MENYUNTING KETERANGAN IKU TIDAK BOLEH MENYENTUH SUBSTANSINYA
     *
     * Sasaran, indikator, satuan, dan target berasal dari Renstra dan berubah
     * lewat revisi. Halaman keterangan hanya mengisi empat kolom yang memang
     * tidak pernah ada di Renstra.
     *
     * Yang diuji di sini adalah BATASNYA, bukan penyimpanannya — sebab
     * `updateComplete()` yang dulu dipakai menulis ulang seluruh isi dari apa
     * pun yang dikirim, dan kehilangan target karenanya tidak memunculkan
     * galat apa pun.
     * =======================================================*/
    private function caseKeteranganIku(): void
    {
        CLI::write(CLI::color('Sunting keterangan IKU', 'cyan'));

        $iku = new \App\Models\Opd\IkuModel($this->db);
        $opd = $this->opdUji('UJI-VERSI OPD Keterangan');

        $sasaranId = $this->sisipUji('iku_sasaran', [
            'opd_id' => $opd, 'sasaran' => 'UJI-VERSI sasaran keterangan',
            'tahun_mulai' => self::MULAI, 'tahun_akhir' => self::AKHIR, 'urutan' => 0,
        ]);
        $indId = $this->sisipUji('iku_indikator', [
            'iku_sasaran_id' => $sasaranId, 'indikator' => 'UJI-VERSI indikator keterangan',
            'satuan' => $this->satuanUji(), 'baseline' => '5', 'urutan' => 0, 'status' => 'selesai',
        ]);
        $this->db->table('iku_target')->insert([
            'iku_indikator_id' => $indId, 'tahun' => self::MULAI, 'target' => '70',
            'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'),
        ]);

        // OPD lain, untuk menguji id asing.
        $opdLain = $this->opdUji('UJI-VERSI OPD Keterangan Lain');
        $sasLain = $this->sisipUji('iku_sasaran', [
            'opd_id' => $opdLain, 'sasaran' => 'UJI-VERSI sasaran opd lain',
            'tahun_mulai' => self::MULAI, 'tahun_akhir' => self::AKHIR, 'urutan' => 0,
        ]);
        $indLain = $this->sisipUji('iku_indikator', [
            'iku_sasaran_id' => $sasLain, 'indikator' => 'UJI-VERSI indikator opd lain',
            'satuan' => $this->satuanUji(), 'urutan' => 0, 'status' => 'draft',
        ]);

        $jumlah = $iku->perbaruiKeterangan($sasaranId, [
            $indId => [
                'definisi'            => 'Definisi uji',
                'rumusan_perhitungan' => 'A / B x 100',
                'sumber_data'         => 'Laporan bidang',
                'penanggung_jawab'    => 'Bidang Uji',
            ],
            // Id milik OPD lain ikut dikirim — harus diabaikan.
            $indLain => ['definisi' => 'SEHARUSNYA TIDAK MASUK'],
        ]);

        $this->cek('hanya indikator milik sasaran ini yang diperbarui', $jumlah === 1);

        $sesudah = $this->db->table('iku_indikator')->where('id', $indId)->get()->getRowArray();

        $this->cek('definisi tersimpan', $sesudah['definisi'] === 'Definisi uji');
        $this->cek('rumusan tersimpan', $sesudah['rumusan_perhitungan'] === 'A / B x 100');
        $this->cek('sumber data tersimpan', $sesudah['sumber_data'] === 'Laporan bidang');
        $this->cek('penanggung jawab tersimpan', $sesudah['penanggung_jawab'] === 'Bidang Uji');

        // --- yang TIDAK boleh berubah ---
        $this->cek('teks indikator tidak tersentuh',
            $sesudah['indikator'] === 'UJI-VERSI indikator keterangan');
        $this->cek('satuan tidak tersentuh', (string) $sesudah['satuan'] === $this->satuanUji());
        $this->cek('baseline tidak tersentuh', (string) $sesudah['baseline'] === '5');

        $target = $this->db->table('iku_target')->where('iku_indikator_id', $indId)->get()->getResultArray();
        $this->cek('target TIDAK ikut terhapus',
            count($target) === 1 && (string) $target[0]['target'] === '70');

        $indikatorLain = $this->db->table('iku_indikator')->where('id', $indLain)->get()->getRowArray();
        $this->cek('indikator OPD lain tidak ikut tersunting',
            $indikatorLain['definisi'] === null);

        // --- masukan kosong tidak melakukan apa-apa ---
        $this->cek('masukan kosong tidak menyentuh apa pun',
            $iku->perbaruiKeterangan($sasaranId, []) === 0);
        $this->cek('sasaran tak sah ditolak',
            $iku->perbaruiKeterangan(0, [$indId => ['definisi' => 'x']]) === 0);

        // --- sasaran kembar tertahan ---
        $this->cek('sasaran dengan teks sama dikenali kembar',
            $iku->sasaranKembar($opd, 'UJI-VERSI SASARAN KETERANGAN  ', self::MULAI, self::AKHIR) === $sasaranId);
        $this->cek('sasaran berbeda tidak dianggap kembar',
            $iku->sasaranKembar($opd, 'UJI-VERSI sasaran lain sekali', self::MULAI, self::AKHIR) === null);
        $this->cek('periode berbeda tidak dianggap kembar',
            $iku->sasaranKembar($opd, 'UJI-VERSI sasaran keterangan', self::MULAI + 10, self::AKHIR + 10) === null);

        $this->bersihkanIkuUji($opd);
        $this->bersihkanIkuUji($opdLain);
        $this->bersihkanRenstraUji();
    }

    /* =========================================================
     * PERIODE YANG DITAWARKAN MENU IKU
     *
     * Membaca `iku_sasaran` saja melahirkan telur-dan-ayam: periode yang
     * Renstra-nya sudah ada tetapi IKU-nya belum tidak pernah muncul di
     * dropdown, sehingga tidak bisa dipilih — dan karena tidak bisa dipilih,
     * IKU-nya tidak pernah lahir.
     * =======================================================*/
    private function casePeriodeIku(): void
    {
        CLI::write(CLI::color('Periode yang ditawarkan menu IKU', 'cyan'));

        $iku    = new \App\Models\Opd\IkuModel($this->db);
        $opd    = $this->opdUji('UJI-VERSI OPD Periode');
        [, $rs] = $this->siapkanRpjmdUji();
        $tuj    = $this->sisipUji('renstra_tujuan', [
            'rpjmd_sasaran_id' => $rs, 'tujuan' => 'UJI-VERSI tujuan periode',
        ]);

        // Renstra ada, IKU belum.
        $this->sisipRenstraSasaran($opd, $tuj, 'UJI-VERSI sasaran periode');

        $kunci  = self::MULAI . '-' . self::AKHIR;
        $opsi   = $iku->getPeriodeOptions('opd', $opd);

        $this->cek('periode Renstra muncul walau IKU-nya masih kosong',
            isset($opsi[$kunci]));
        $this->cek('ditandai belum punya IKU',
            ($opsi[$kunci]['punya_iku'] ?? true) === false);
        $this->cek('tahun-tahunnya lengkap',
            ($opsi[$kunci]['years'] ?? []) === range(self::MULAI, self::AKHIR));

        // Tanpa penggabungan, daftarnya memang kosong — itu keadaan lama.
        $this->cek('tanpa penggabungan sumber, daftarnya kosong',
            $iku->getPeriodeOptions('opd', $opd, false) === []);

        // Sesudah IKU terisi, periodenya ditandai sudah punya IKU.
        $sasaranId = $this->sisipUji('iku_sasaran', [
            'opd_id' => $opd, 'sasaran' => 'UJI-VERSI sasaran iku periode',
            'tahun_mulai' => self::MULAI, 'tahun_akhir' => self::AKHIR, 'urutan' => 0,
        ]);

        $opsi2 = $iku->getPeriodeOptions('opd', $opd);

        $this->cek('sesudah IKU terisi, penandanya berubah',
            ($opsi2[$kunci]['punya_iku'] ?? false) === true);
        $this->cek('tidak tergandakan meski ada di kedua sumber',
            count(array_keys($opsi2, $opsi2[$kunci], true)) === 1);

        // Periode yang HANYA punya IKU tetap muncul.
        $this->sisipUji('iku_sasaran', [
            'opd_id' => $opd, 'sasaran' => 'UJI-VERSI sasaran iku lawas',
            'tahun_mulai' => self::MULAI - 10, 'tahun_akhir' => self::AKHIR - 10, 'urutan' => 1,
        ]);

        $opsi3 = $iku->getPeriodeOptions('opd', $opd);

        $this->cek('periode yang hanya punya IKU tetap muncul',
            isset($opsi3[(self::MULAI - 10) . '-' . (self::AKHIR - 10)]));
        $this->cek('urutannya terbaru di atas',
            array_key_first($opsi3) === $kunci);

        $this->db->table('iku_sasaran')->where('opd_id', $opd)->delete();
        $this->bersihkanRenstraUji();
        $this->bersihkanRpjmdUji();
    }

    /* =========================================================
     * SUMBER LAKIP: VERSI IKU
     *
     * LakipSourceService semula mencari versi IKU di `dokumen_versi`. Versi
     * IKU tidak pernah ada di sana — ia hidup di `iku_revisi`. Akibatnya
     * daftar pilihannya selalu kosong, tanpa galat: dropdown yang seharusnya
     * berisi versi tampak seolah OPD-nya memang belum punya IKU.
     * =======================================================*/
    private function caseSumberLakipIku(): void
    {
        CLI::write(CLI::color('Pilihan versi IKU untuk sumber LAKIP', 'cyan'));

        $rev = new \App\Models\Opd\IkuRevisiModel($this->db);

        if (! $rev->siap()) {
            CLI::write('  ' . CLI::color('LEWAT', 'yellow') . '  tabel iku_revisi belum ada.');

            return;
        }

        $opd    = $this->opdUji('UJI-VERSI OPD SumberLakip');
        $sumber = new \App\Services\Version\LakipSourceService($this->db);

        $this->cek('belum ada revisi -> daftar kosong',
            $sumber->pilihanVersi('iku', 'opd', $opd, self::MULAI + 1) === []);

        // Dua revisi resmi: satu untuk tahun awal, satu mulai tahun ketiga.
        $r1 = $this->sisipUji('iku_revisi', [
            'opd_id' => $opd, 'tahun_mulai' => self::MULAI, 'tahun_akhir' => self::AKHIR,
            'nomor' => 1, 'nama' => 'UJI-VERSI IKU pertama', 'status' => 'superseded',
            'berlaku_mulai_tahun' => self::MULAI, 'berlaku_sampai_tahun' => self::MULAI + 1,
        ]);
        $r2 = $this->sisipUji('iku_revisi', [
            'opd_id' => $opd, 'tahun_mulai' => self::MULAI, 'tahun_akhir' => self::AKHIR,
            'nomor' => 2, 'nama' => 'UJI-VERSI IKU kedua', 'status' => 'berlaku',
            'berlaku_mulai_tahun' => self::MULAI + 2, 'berlaku_sampai_tahun' => null,
        ]);

        // Sejak 14 Sep 2026: SEMUA revisi resmi yang periodenya memuat tahun
        // laporan ditawarkan (pemakai bebas memilih, §27 mewajibkan alasan bila
        // bukan rekomendasi); masa berlaku hanya menentukan REKOMENDASI.
        $cariId = static function (array $daftar, int $id): ?array {
            foreach ($daftar as $v) {
                if ((int) $v['id'] === $id) {
                    return $v;
                }
            }

            return null;
        };

        // Tahun pertama: keduanya ditawarkan; yang berlaku (revisi 1) direkomendasikan.
        $th1 = $sumber->pilihanVersi('iku', 'opd', $opd, self::MULAI);

        $this->cek('tahun awal menawarkan kedua revisi resmi', count($th1) === 2);
        $this->cek('revisi pertama direkomendasikan pada tahun awal',
            ($cariId($th1, $r1)['rekomendasi'] ?? null) === true);
        $this->cek('revisi kedua TIDAK direkomendasikan pada tahun awal',
            ($cariId($th1, $r2)['rekomendasi'] ?? null) === false);
        $this->cek('revisi lama diberi lencana HISTORICAL', ($cariId($th1, $r1)['badge'] ?? '') === 'HISTORICAL');
        $this->cek('yang direkomendasikan hanya satu',
            count(array_filter($th1, static fn ($v) => ! empty($v['rekomendasi']))) === 1);

        // Tahun ketiga: keduanya ditawarkan; revisi 2 yang direkomendasikan.
        $th3 = $sumber->pilihanVersi('iku', 'opd', $opd, self::MULAI + 2);

        $this->cek('tahun ketiga menawarkan kedua revisi resmi', count($th3) === 2);
        $this->cek('revisi kedua direkomendasikan pada tahun ketiga',
            ($cariId($th3, $r2)['rekomendasi'] ?? null) === true);
        $this->cek('revisi berjalan diberi lencana CURRENT', ($cariId($th3, $r2)['badge'] ?? '') === 'CURRENT');
        $this->cek('teks masa berlaku tersedia untuk dropdown',
            ($cariId($th3, $r2)['masa'] ?? '') === 'berlaku ' . (self::MULAI + 2) . '–…');

        // Bentuknya harus sama dengan pilihan sumber lain supaya tampilan
        // tidak perlu tahu registrinya yang mana.
        foreach (['id', 'version_no', 'label', 'effective_from', 'effective_to',
                  'rekomendasi', 'badge'] as $kunci) {
            $this->cek('bentuk pilihan memuat "' . $kunci . '"', array_key_exists($kunci, $th3[0]));
        }

        // Tahun di luar periode dokumennya tidak dilayani sama sekali.
        $this->cek('tahun di luar periode IKU tidak dilayani',
            $sumber->pilihanVersi('iku', 'opd', $opd, self::AKHIR + 5) === []);

        // Draft & pengajuan tidak pernah ditawarkan.
        $this->sisipUji('iku_revisi', [
            'opd_id' => $opd, 'tahun_mulai' => self::MULAI, 'tahun_akhir' => self::AKHIR,
            'nomor' => 3, 'nama' => 'UJI-VERSI IKU draft', 'status' => 'draft',
            'berlaku_mulai_tahun' => self::MULAI + 3,
        ]);

        $this->cek('draft tidak ikut ditawarkan',
            count($sumber->pilihanVersi('iku', 'opd', $opd, self::MULAI + 3)) === 2);

        $this->db->table('iku_revisi')->where('opd_id', $opd)->delete();
        $this->bersihkanRenstraUji();
    }

    /* =========================================================
     * MENU RENSTRA DAN SYNC IKU HARUS MENJAWAB SAMA
     *
     * Keduanya membaca tabel berjalan yang sama. Sebelum diperbaiki, Sync
     * menyaring baris yang dipensiunkan sedangkan menu Renstra tidak —
     * sehingga layar Renstra menampilkan sasaran yang versi terakhir sudah
     * menyatakan berhenti, lalu Sync melaporkan "tidak ada sasaran pada
     * periode ini". Tidak ada galat; hanya dua jawaban yang bertentangan,
     * dan yang keliru justru yang tampak paling meyakinkan.
     * =======================================================*/
    private function casePensiunKonsisten(): void
    {
        CLI::write(CLI::color('Baris pensiun disembunyikan menu Renstra & Sync', 'cyan'));

        $opd    = $this->opdUji('UJI-VERSI OPD Pensiun');
        [, $rs] = $this->siapkanRpjmdUji();
        $tuj    = $this->sisipUji('renstra_tujuan', [
            'rpjmd_sasaran_id' => $rs, 'tujuan' => 'UJI-VERSI tujuan pensiun',
        ]);
        $sasaranId = $this->sisipRenstraSasaran($opd, $tuj, 'UJI-VERSI sasaran pensiun');

        $renstra = new \App\Models\Opd\RenstraModel($this->db);
        $iku     = new \App\Models\Opd\IkuModel($this->db);
        $periode = self::MULAI . '-' . self::AKHIR;

        $hitungIndikator = static function (array $pohon): int {
            $n = 0;

            foreach ($pohon as $t) {
                foreach ($t['sasaran'] ?? [] as $s) {
                    $n += count($s['indikator'] ?? []);
                }
            }

            return $n;
        };

        $sebelumRenstra = $hitungIndikator($renstra->getFilteredRenstra($opd, null, null, null, null, $periode));
        $sebelumSync    = 0;

        foreach ($iku->getKandidatSync('renstra', $opd, self::MULAI, self::AKHIR) as $s) {
            $sebelumSync += count($s['indikator'] ?? []);
        }

        $this->cek('sebelum dipensiunkan, keduanya melihat isi yang sama',
            $sebelumRenstra === 1 && $sebelumSync === 1);

        // --- sasaran dipensiunkan, persis seperti yang dilakukan penetapan versi ---
        $this->db->table('renstra_sasaran')->where('id', $sasaranId)->update([
            'dihentikan_pada'   => date('Y-m-d H:i:s'),
            'alasan_dihentikan' => 'UJI-VERSI: tidak lagi tercantum pada versi yang ditetapkan.',
        ]);

        $sesudahRenstra = $hitungIndikator($renstra->getFilteredRenstra($opd, null, null, null, null, $periode));
        $sesudahSync    = 0;

        foreach ($iku->getKandidatSync('renstra', $opd, self::MULAI, self::AKHIR) as $s) {
            $sesudahSync += count($s['indikator'] ?? []);
        }

        $this->cek('menu Renstra TIDAK lagi menampilkan sasaran yang dipensiunkan',
            $sesudahRenstra === 0);
        $this->cek('Sync IKU juga tidak menampilkannya', $sesudahSync === 0);
        $this->cek('keduanya menjawab sama', $sesudahRenstra === $sesudahSync);

        // --- barisnya TETAP ADA, hanya tidak ditampilkan ---
        $masihAda = (int) $this->db->table('renstra_sasaran')
            ->where('id', $sasaranId)->countAllResults();

        $this->cek('barisnya tetap tersimpan, bukan dihapus', $masihAda === 1);

        // --- indikator yang dipensiunkan sendirian juga hilang dari daftar ---
        $this->db->table('renstra_sasaran')->where('id', $sasaranId)
            ->update(['dihentikan_pada' => null, 'alasan_dihentikan' => null]);

        $this->db->table('renstra_indikator_sasaran')
            ->where('renstra_sasaran_id', $sasaranId)
            ->update(['dihentikan_pada' => date('Y-m-d H:i:s')]);

        $indPensiun = $hitungIndikator($renstra->getFilteredRenstra($opd, null, null, null, null, $periode));

        $this->cek('indikator yang dipensiunkan ikut disembunyikan', $indPensiun === 0);

        // Sasarannya sendiri masih hidup, jadi tetap muncul sebagai baris tanpa indikator.
        $pohon = $renstra->getFilteredRenstra($opd, null, null, null, null, $periode);
        $this->cek('sasarannya sendiri tetap terbaca', $pohon !== []);

        $this->bersihkanRenstraUji();
        $this->bersihkanRpjmdUji();
    }

    /* =========================================================
     * LAKIP BERSUMBER IKU
     *
     * Yang diuji: tabel LAKIP dibangun dari ARSIP revisi IKU yang dipilih —
     * bukan dari IKU berjalan — sehingga laporan tahun lampau tidak ikut
     * berubah ketika IKU direvisi kemudian. Dan realisasi menempel pada
     * indikator BERJALAN, sehingga berganti versi tampilan tidak menghapus
     * capaian yang sudah diisi.
     * =======================================================*/
    private function caseLakipDariIku(): void
    {
        CLI::write(CLI::color('LAKIP bersumber IKU', 'cyan'));

        $rev = new \App\Models\Opd\IkuRevisiModel($this->db);

        if (! $rev->siap() || ! $this->db->fieldExists('source_entity_id', 'lakip')) {
            CLI::write('  ' . CLI::color('LEWAT', 'yellow') . '  tabel belum siap.');

            return;
        }

        $lakip = new \App\Models\LakipModel($this->db);
        $opd   = $this->opdUji('UJI-VERSI OPD LakipIku');
        $tahun = self::MULAI + 1;

        // IKU berjalan: satu sasaran, satu indikator.
        $sasaranId = $this->sisipUji('iku_sasaran', [
            'opd_id' => $opd, 'sasaran' => 'UJI-VERSI sasaran lakip',
            'tahun_mulai' => self::MULAI, 'tahun_akhir' => self::AKHIR, 'urutan' => 0,
        ]);
        $indId = $this->sisipUji('iku_indikator', [
            'iku_sasaran_id' => $sasaranId, 'indikator' => 'UJI-VERSI indikator lakip',
            'satuan' => $this->satuanUji(), 'urutan' => 0, 'status' => 'selesai',
        ]);
        $this->db->table('iku_target')->insert([
            'iku_indikator_id' => $indId, 'tahun' => $tahun, 'target' => '80',
            'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $revisiId = $rev->bekukanDanAjukan($opd, self::MULAI, self::AKHIR, 11);
        $rev->sahkan($revisiId, 22);

        // --- tabel LAKIP dibangun dari arsip revisi ---
        $baris = $lakip->getIndexIkuTargets($revisiId, $tahun, $opd);

        $this->cek('satu baris terbaca dari arsip revisi', count($baris) === 1);
        $this->cek('indikatornya menunjuk baris IKU BERJALAN',
            (int) $baris[0]['indikator_id'] === $indId);
        $this->cek('target tahun itu ikut terbaca', (string) $baris[0]['target_tahun_ini'] === '80');
        $this->cek('bentuknya sama dengan sumber Renstra',
            isset($baris[0]['sasaran'], $baris[0]['satuan'], $baris[0]['target_id']));

        // --- IKU berjalan diubah: arsip TIDAK ikut berubah ---
        $this->db->table('iku_target')->where('iku_indikator_id', $indId)
            ->where('tahun', $tahun)->update(['target' => '95']);

        $sesudah = $lakip->getIndexIkuTargets($revisiId, $tahun, $opd);

        $this->cek('mengubah IKU berjalan TIDAK mengubah isi laporan',
            (string) $sesudah[0]['target_tahun_ini'] === '80');

        // --- realisasi menempel pada indikator berjalan ---
        $this->db->table('lakip')->insert([
            'tahun' => $tahun, 'opd_id' => $opd, 'mode' => 'opd',
            'source_type' => 'iku', 'source_version_id' => $revisiId, 'source_entity_id' => $indId,
            'capaian_tahun_ini' => '76', 'target_hitung' => '80', 'capaian_hitung' => '95',
            'status' => 'draft', 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $peta = $lakip->getLakipMapIku($tahun, null, $opd);

        $this->cek('realisasi terbaca lewat id indikator berjalan', isset($peta[$indId]));
        $this->cek('capaian tersimpan utuh', ($peta[$indId]['capaian_tahun_ini'] ?? '') === '76');
        $this->cek('target ikut dibekukan pada barisnya',
            ($peta[$indId]['target_hitung'] ?? '') === '80');

        // --- revisi kedua: realisasi TETAP terbaca ---
        $revisi2 = $rev->buatDraft([
            'opd_id' => $opd, 'tahun_mulai' => self::MULAI, 'tahun_akhir' => self::AKHIR,
            'nomor' => 'UJI/L2', 'nama' => 'UJI-VERSI revisi lakip kedua',
            'berlaku_mulai_tahun' => $tahun + 1, 'dibuat_oleh' => 11,
        ]);
        $rev->ajukan($revisi2, 11);
        $rev->sahkan($revisi2, 22);

        $petaSesudah = $lakip->getLakipMapIku($tahun, null, $opd);

        $this->cek('realisasi TIDAK hilang setelah ada revisi baru',
            isset($petaSesudah[$indId])
            && ($petaSesudah[$indId]['capaian_tahun_ini'] ?? '') === '76');

        // --- rincian satu indikator untuk form ---
        $detail = $lakip->getIkuTargetDetail($revisiId, $indId, $tahun);

        $this->cek('rincian indikator terbaca untuk form', $detail !== null);
        $this->cek('rincian memuat pemilik OPD-nya', (int) ($detail['opd_id'] ?? 0) === $opd);
        $this->cek('indikator milik revisi lain tidak terbaca',
            $lakip->getIkuTargetDetail($revisiId, 999999, $tahun) === null);

        // --- bersih-bersih ---
        $this->db->table('lakip')->where('opd_id', $opd)->delete();
        $this->bersihkanIkuUji($opd);
        $this->bersihkanRenstraUji();
    }

    private function dalamTransaksiUji(callable $kerja)
    {
        $this->db->transBegin();
        $this->db->resetTransStatus();

        try {
            $hasil = $kerja();
            $this->db->transCommit();

            return $hasil;
        } catch (Throwable $e) {
            $this->db->transRollback();

            throw $e;
        }
    }

    /** @return int[] id pengajuan pada periode uji */
    private function idAntrean(): array
    {
        $out = [];

        foreach ($this->model->menungguVerifikasi() as $r) {
            if ((int) $r['periode_mulai'] === self::MULAI && (int) $r['periode_akhir'] === self::AKHIR) {
                $out[] = (int) $r['id'];
            }
        }

        return $out;
    }

    private function hapusSnapshotUji(int $opdId): void
    {
        try {
            $ids = $this->kolomIdBaris($this->db->table('lakip_snapshot')
                ->select('id')->where('opd_id', $opdId)->get()->getResultArray());

            if ($ids === []) {
                return;
            }

            foreach (['lakip_benchmark_item', 'lakip_snapshot_analisis', 'lakip_snapshot_program', 'lakip_snapshot_baris'] as $t) {
                if ($this->db->tableExists($t)) {
                    $this->db->table($t)->whereIn('snapshot_id', $ids)->delete();
                }
            }

            $this->db->table('lakip_snapshot')->whereIn('id', $ids)->delete();
        } catch (Throwable $e) {
            // biarkan
        }
    }

    /** @return int[] */
    private function kolomIdBaris(array $rows): array
    {
        return array_map('intval', array_column($rows, 'id'));
    }

    private function opdUji(string $nama): int
    {
        $ada = $this->db->table('opd')->select('id')->where('nama_opd', $nama)->get()->getRowArray();

        if ($ada !== null) {
            return (int) $ada['id'];
        }

        return $this->sisipUji('opd', ['nama_opd' => $nama]);
    }

    private function sisipRenstraSasaran(int $opdId, int $tujuanId, string $sasaran): int
    {
        $now = date('Y-m-d H:i:s');

        $sid = $this->sisipUji('renstra_sasaran', [
            'opd_id'            => $opdId,
            'renstra_tujuan_id' => $tujuanId,
            'sasaran'           => $sasaran,
            'status'            => 'selesai',
            'tahun_mulai'       => self::MULAI,
            'tahun_akhir'       => self::AKHIR,
        ]);

        $iid = $this->sisipUji('renstra_indikator_sasaran', [
            'renstra_sasaran_id' => $sid,
            'indikator_sasaran'  => 'UJI-VERSI indikator',
            // id satuan, bukan teksnya — itulah yang benar-benar dikirim form.
            'satuan'             => $this->satuanUji(),
            'baseline'           => '0',
            'jenis_indikator'    => 'positif',
        ]);

        $this->db->table('renstra_target')->insert([
            'renstra_indikator_id' => $iid, 'tahun' => self::MULAI, 'target' => '75',
            'created_at' => $now, 'updated_at' => $now,
        ]);

        return $sid;
    }

    /**
     * Satu id satuan sungguhan dari master.
     *
     * Memakai teks ('Persen') akan membuat uji ini menipu: join di
     * `getFilteredRenstra()` mencocokkan `satuan.id`, jadi teks selalu gagal
     * cocok dan kolom satuan ikut kosong di kedua sisi — cocok, tapi
     * sama-sama salah.
     */
    private function satuanUji(): string
    {
        $row = $this->db->table('satuan')->select('id')->orderBy('id', 'ASC')
            ->limit(1)->get()->getRowArray();

        return (string) ($row['id'] ?? 'Persen');
    }

    private function sisipUji(string $tabel, array $data): int
    {
        $now = date('Y-m-d H:i:s');

        if ($this->db->fieldExists('created_at', $tabel)) {
            $data['created_at'] = $now;
        }

        if ($this->db->fieldExists('updated_at', $tabel)) {
            $data['updated_at'] = $now;
        }

        $this->db->table($tabel)->insert($data);

        return (int) $this->db->insertID();
    }

    private function bersihkanRenstraUji(): void
    {
        try {
            $this->db->table('renstra_sasaran')->like('sasaran', 'UJI-VERSI', 'after')->delete();
            $this->db->table('renstra_tujuan')->like('tujuan', 'UJI-VERSI', 'after')->delete();
            $this->db->table('opd')->like('nama_opd', 'UJI-VERSI', 'after')->delete();
        } catch (Throwable $e) {
            // biarkan
        }
    }

    /* =========================================================
     * DATA UJI RPJMD
     * =======================================================*/

    /** @return array{0:int,1:int} id dua sasaran uji */
    private function siapkanRpjmdUji(): array
    {
        $this->bersihkanRpjmdUji();
        $now = date('Y-m-d H:i:s');

        $this->db->table('rpjmd_visi')->insert([
            'visi' => 'UJI-VERSI visi', 'created_at' => $now, 'updated_at' => $now,
        ]);
        $visiId = (int) $this->db->insertID();

        $this->db->table('rpjmd_misi')->insert([
            'rpjmd_visi_id' => $visiId, 'misi' => 'UJI-VERSI misi', 'status' => 'selesai',
            'tahun_mulai' => self::MULAI, 'tahun_akhir' => self::AKHIR,
            'created_at' => $now, 'updated_at' => $now,
        ]);
        $misiId = (int) $this->db->insertID();

        $this->db->table('rpjmd_tujuan')->insert([
            'misi_id' => $misiId, 'tujuan_rpjmd' => 'UJI-VERSI tujuan',
            'created_at' => $now, 'updated_at' => $now,
        ]);
        $tujuanId = (int) $this->db->insertID();

        $sasaran = [];

        foreach (['A', 'B'] as $tanda) {
            $this->db->table('rpjmd_sasaran')->insert([
                'tujuan_id' => $tujuanId, 'status' => 'selesai',
                'sasaran_rpjmd' => 'UJI-VERSI sasaran ' . $tanda,
                'created_at' => $now, 'updated_at' => $now,
            ]);
            $sid = (int) $this->db->insertID();
            $sasaran[] = $sid;

            $this->db->table('rpjmd_indikator_sasaran')->insert([
                'sasaran_id' => $sid, 'indikator_sasaran' => 'UJI-VERSI indikator ' . $tanda,
                'satuan' => 'Persen', 'jenis_indikator' => 'positif',
                'created_at' => $now, 'updated_at' => $now,
            ]);
            $iid = (int) $this->db->insertID();

            foreach ([self::MULAI, self::MULAI + 1] as $th) {
                $this->db->table('rpjmd_target')->insert([
                    'indikator_sasaran_id' => $iid, 'tahun' => $th, 'target_tahunan' => '80',
                    'created_at' => $now, 'updated_at' => $now,
                ]);
            }
        }

        return $sasaran;
    }

    private function bersihkanRpjmdUji(): void
    {
        try {
            // Cukup dari akar: seluruh turunan RPJMD ber-ON DELETE CASCADE.
            $this->db->table('renstra_tujuan')->like('tujuan', 'UJI-VERSI', 'after')->delete();
            $this->db->table('rpjmd_misi')->like('misi', 'UJI-VERSI', 'after')->delete();
            $this->db->table('rpjmd_visi')->like('visi', 'UJI-VERSI', 'after')->delete();
        } catch (Throwable $e) {
            // biarkan
        }
    }

    /** Seluruh id baris arsip dalam satu pohon, ditandai tabelnya. @return string[] */
    private function kumpulkanIdArsip(array $isi): array
    {
        $out = [];

        foreach ($isi as $m) {
            $out[] = 'misi:' . $m['id'];

            foreach ($m['tujuan'] ?? [] as $t) {
                $out[] = 'tujuan:' . $t['id'];

                foreach ($t['indikator_tujuan'] ?? [] as $it) {
                    $out[] = 'indtuj:' . $it['id'];

                    foreach ($it['target'] ?? [] as $tg) {
                        $out[] = 'targettuj:' . $tg['id'];
                    }
                }

                foreach ($t['sasaran'] ?? [] as $s) {
                    $out[] = 'sasaran:' . $s['id'];

                    foreach ($s['indikator'] ?? [] as $i) {
                        $out[] = 'ind:' . $i['id'];

                        foreach ($i['target'] ?? [] as $tg) {
                            $out[] = 'target:' . $tg['id'];
                        }
                    }
                }
            }
        }

        return $out;
    }

    /* =========================================================
     * BANTU
     * =======================================================*/

    private function koneksi(?string $nama)
    {
        if ($nama === null || trim($nama) === '') {
            return db_connect();
        }

        $cfg = config('Database')->default;
        $cfg['database'] = trim($nama);

        return db_connect($cfg, false);
    }

    private function buatDraft(VersionScope $scope, int $nomor, string $label, string $mulai): int
    {
        return $this->model->sisipkan(array_merge($scope->kolomBaru(), [
            'version_no'     => $nomor,
            'label'          => $label,
            'effective_from' => $mulai,
            'status'         => DokumenVersiModel::STATUS_DRAFT,
        ]));
    }

    /** Terbitkan langsung (jalur dokumen Kabupaten §18). */
    private function terbitkan(int $id): void
    {
        $this->approval->setujui($id, 1, true);
    }

    private function status(int $id): string
    {
        return (string) ($this->model->ambil($id)['status'] ?? '');
    }

    /** @return array<int,string> id => "from..to" */
    private function timelineTeks(VersionScope $scope): array
    {
        $out = [];

        foreach ($this->model->publishedUrutMaju($scope) as $r) {
            $out[(int) $r['id']] = $r['effective_from'] . '..' . ($r['effective_to'] ?? 'NULL');
        }

        return $out;
    }

    private function labelPada(VersionScope $scope, string $tanggal): ?string
    {
        try {
            $v = $this->resolver->getEffectiveVersion($scope, $tanggal);
        } catch (VersionConflictException $e) {
            return '<<KONFLIK>>';
        }

        return $v === null ? null : (string) $v['label'];
    }

    private function jumlahTerbuka(VersionScope $scope): int
    {
        $n = 0;

        foreach ($this->model->publishedUrutMaju($scope) as $r) {
            if ($r['effective_to'] === null) {
                $n++;
            }
        }

        return $n;
    }

    private function hapusLingkup(VersionScope $scope): void
    {
        $ids = array_column($this->model->daftar($scope), 'id');

        if ($ids === []) {
            return;
        }

        // Audit dulu: FK-nya RESTRICT, dan itu memang yang diinginkan.
        $this->db->table('version_submission_history')->whereIn('version_id', $ids)->delete();
        $this->db->table('version_correction_requests')->whereIn('version_id', $ids)->delete();
        $this->db->table('dokumen_versi')->whereIn('id', $ids)->delete();
    }

    /** Sapu bersih seluruh sisa data uji periode 2090-2094. */
    private function bersihkan(): void
    {
        try {
            $ids = array_column(
                $this->db->table('dokumen_versi')
                    ->select('id')
                    ->where('periode_mulai', self::MULAI)
                    ->where('periode_akhir', self::AKHIR)
                    ->get()->getResultArray(),
                'id'
            );

            if ($ids === []) {
                return;
            }

            $this->db->table('version_submission_history')->whereIn('version_id', $ids)->delete();
            $this->db->table('version_correction_requests')->whereIn('version_id', $ids)->delete();
            $this->db->table('dokumen_versi')->whereIn('id', $ids)->delete();
        } catch (Throwable $e) {
            // Basis belum siap — biarkan run() yang melaporkannya.
        }
    }

    private function cek(string $judul, bool $lulus): void
    {
        if ($lulus) {
            $this->lulus++;
            CLI::write('  ' . CLI::color('LULUS', 'green') . '  ' . $judul);
        } else {
            $this->gagal++;
            CLI::write('  ' . CLI::color('GAGAL', 'red') . '  ' . $judul);
        }
    }

    private function cekMelempar(string $judul, callable $kerja): void
    {
        try {
            $kerja();
            $this->cek($judul, false);
        } catch (Throwable $e) {
            $this->cek($judul, true);
        }
    }
}
