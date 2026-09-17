<?php

namespace App\Models\Versi;

use App\Services\Version\VersionScope;
use RuntimeException;

/**
 * Arsip isi versi RPJMD.
 *
 * Hierarki yang dibekukan:
 *
 *   misi (+ teks visi)
 *     ├─ tujuan
 *     │    ├─ indikator_tujuan ─ target_tujuan
 *     │    └─ sasaran
 *     │         └─ indikator_sasaran ─ target
 *
 * Periode RPJMD tidak punya tabel kepala — ia menempel pada
 * `rpjmd_misi.tahun_mulai/tahun_akhir` (temuan T3). Karena itu pembekuan
 * bermula dari misi yang periodenya persis sama dengan lingkup versi.
 */
class RpjmdVersiModel extends ArsipVersiModel
{
    public function modul(): string
    {
        return VersionScope::MODUL_RPJMD;
    }

    public function tabelArsip(): array
    {
        return [
            'rpjmd_versi_misi',
            'rpjmd_versi_tujuan',
            'rpjmd_versi_indikator_tujuan',
            'rpjmd_versi_target_tujuan',
            'rpjmd_versi_sasaran',
            'rpjmd_versi_indikator_sasaran',
            'rpjmd_versi_target',
        ];
    }

    /**
     * Peta tingkat arsip RPJMD. Akarnya misi, karena periode RPJMD memang
     * menempel di sana (temuan T3).
     */
    protected function petaKolom(): array
    {
        return [
            'misi' => [
                'tabel' => 'rpjmd_versi_misi', 'teks' => 'misi',
                'fk' => null, 'induk' => null, 'extra' => ['visi'],
            ],
            'tujuan' => [
                'tabel' => 'rpjmd_versi_tujuan', 'teks' => 'tujuan_rpjmd',
                'fk' => 'versi_misi_id', 'induk' => 'misi', 'extra' => [],
            ],
            'sasaran' => [
                'tabel' => 'rpjmd_versi_sasaran', 'teks' => 'sasaran_rpjmd',
                'fk' => 'versi_tujuan_id', 'induk' => 'tujuan', 'extra' => ['csf'],
            ],
            'indikator' => [
                'tabel' => 'rpjmd_versi_indikator_sasaran', 'teks' => 'indikator_sasaran',
                'fk' => 'versi_sasaran_id', 'induk' => 'sasaran',
                'extra' => ['definisi_op', 'satuan', 'jenis_indikator', 'baseline'],
            ],
            'target' => [
                'tabel' => 'rpjmd_versi_target',
                'fk' => 'versi_indikator_id', 'nilai' => 'target_tahunan',
            ],
        ];
    }

    /**
     * Misi baru butuh periode yang tidak ada di form — diambil dari lingkup
     * versi, karena periode RPJMD justru ditentukan di tingkat misi (T3).
     */
    protected function lengkapiBarisBaru(string $tingkat, array $row, int $versiId): array
    {
        if ($tingkat !== 'misi') {
            return $row;
        }

        $kepala = $this->db->table('dokumen_versi')
            ->select('periode_mulai, periode_akhir')
            ->where('id', $versiId)->get()->getRowArray();

        if ($kepala !== null) {
            $row['tahun_mulai'] = (int) $kepala['periode_mulai'];
            $row['tahun_akhir'] = (int) $kepala['periode_akhir'];
        }

        return $row;
    }

    /* =========================================================
     * LIVE -> ARSIP
     * =======================================================*/

    public function bekukanDariLive(int $versiId, VersionScope $scope): array
    {
        $this->pastikanDalamTransaksi('pembekuan arsip RPJMD');

        $now = $this->sekarang();
        $n   = ['misi' => 0, 'tujuan' => 0, 'indikator_tujuan' => 0, 'target_tujuan' => 0,
            'sasaran' => 0, 'indikator_sasaran' => 0, 'target' => 0];

        $misiList = $this->db->table('rpjmd_misi m')
            ->select('m.*, v.visi AS teks_visi')
            ->join('rpjmd_visi v', 'v.id = m.rpjmd_visi_id', 'left')
            ->where('m.tahun_mulai', $scope->periodeMulai())
            ->where('m.tahun_akhir', $scope->periodeAkhir())
            ->where('m.dihentikan_pada IS NULL', null, false)
            ->orderBy('m.rpjmd_visi_id', 'ASC')
            ->orderBy('m.id', 'ASC')
            ->get()->getResultArray();

        $urutMisi = 0;

        foreach ($misiList as $m) {
            $arsipMisiId = $this->sisip('rpjmd_versi_misi', [
                'version_id'      => $versiId,
                'source_misi_id'  => (int) $m['id'],
                'visi'            => $this->kosongJadiNull($m['teks_visi'] ?? null),
                'source_visi_id'  => $m['rpjmd_visi_id'] !== null ? (int) $m['rpjmd_visi_id'] : null,
                'misi'            => (string) $m['misi'],
                'tahun_mulai'     => (int) $m['tahun_mulai'],
                'tahun_akhir'     => (int) $m['tahun_akhir'],
                'urutan'          => $urutMisi++,
                'jenis_perubahan' => self::UBAH_TETAP,
                'created_at'      => $now,
            ]);
            $n['misi']++;

            $this->bekukanTujuan($versiId, (int) $m['id'], $arsipMisiId, $now, $n);
        }

        return $n;
    }

    private function bekukanTujuan(int $versiId, int $misiId, int $arsipMisiId, string $now, array &$n): void
    {
        $rows = $this->db->table('rpjmd_tujuan')
            ->where('misi_id', $misiId)
            ->where('dihentikan_pada IS NULL', null, false)
            ->orderBy('id', 'ASC')->get()->getResultArray();

        $urut = 0;

        foreach ($rows as $t) {
            $arsipTujuanId = $this->sisip('rpjmd_versi_tujuan', [
                'version_id'       => $versiId,
                'versi_misi_id'    => $arsipMisiId,
                'source_tujuan_id' => (int) $t['id'],
                'tujuan_rpjmd'     => (string) $t['tujuan_rpjmd'],
                'urutan'           => $urut++,
                'jenis_perubahan'  => self::UBAH_TETAP,
                'created_at'       => $now,
            ]);
            $n['tujuan']++;

            $this->bekukanIndikatorTujuan($versiId, (int) $t['id'], $arsipTujuanId, $now, $n);
            $this->bekukanSasaran($versiId, (int) $t['id'], $arsipTujuanId, $now, $n);
        }
    }

    private function bekukanIndikatorTujuan(int $versiId, int $tujuanId, int $arsipTujuanId, string $now, array &$n): void
    {
        $rows = $this->db->table('rpjmd_indikator_tujuan')
            ->where('tujuan_id', $tujuanId)
            ->where('dihentikan_pada IS NULL', null, false)
            ->orderBy('id', 'ASC')->get()->getResultArray();

        $urut = 0;

        foreach ($rows as $i) {
            $arsipIndId = $this->sisip('rpjmd_versi_indikator_tujuan', [
                'version_id'          => $versiId,
                'versi_tujuan_id'     => $arsipTujuanId,
                'source_indikator_id' => (int) $i['id'],
                'indikator_tujuan'    => (string) $i['indikator_tujuan'],
                'urutan'              => $urut++,
                'jenis_perubahan'     => self::UBAH_TETAP,
                'created_at'          => $now,
            ]);
            $n['indikator_tujuan']++;

            foreach ($this->db->table('rpjmd_target_tujuan')
                ->where('indikator_tujuan_id', (int) $i['id'])
                ->orderBy('tahun', 'ASC')->get()->getResultArray() as $tg) {
                $this->sisip('rpjmd_versi_target_tujuan', [
                    'versi_indikator_tujuan_id' => $arsipIndId,
                    'tahun'                     => (int) $tg['tahun'],
                    'target_tahunan'            => $this->kosongJadiNull($tg['target_tahunan']),
                    'created_at'                => $now,
                ]);
                $n['target_tujuan']++;
            }
        }
    }

    private function bekukanSasaran(int $versiId, int $tujuanId, int $arsipTujuanId, string $now, array &$n): void
    {
        $rows = $this->db->table('rpjmd_sasaran')
            ->where('tujuan_id', $tujuanId)
            ->where('dihentikan_pada IS NULL', null, false)
            ->orderBy('id', 'ASC')->get()->getResultArray();

        $urut = 0;

        foreach ($rows as $s) {
            $arsipSasaranId = $this->sisip('rpjmd_versi_sasaran', [
                'version_id'        => $versiId,
                'versi_tujuan_id'   => $arsipTujuanId,
                'source_sasaran_id' => (int) $s['id'],
                'sasaran_rpjmd'     => (string) $s['sasaran_rpjmd'],
                'csf'               => $this->kosongJadiNull($s['csf'] ?? null),
                'urutan'            => $urut++,
                'jenis_perubahan'   => self::UBAH_TETAP,
                'created_at'        => $now,
            ]);
            $n['sasaran']++;

            $this->bekukanIndikatorSasaran($versiId, (int) $s['id'], $arsipSasaranId, $now, $n);
        }
    }

    private function bekukanIndikatorSasaran(int $versiId, int $sasaranId, int $arsipSasaranId, string $now, array &$n): void
    {
        $rows = $this->db->table('rpjmd_indikator_sasaran')
            ->where('sasaran_id', $sasaranId)
            ->where('dihentikan_pada IS NULL', null, false)
            ->orderBy('id', 'ASC')->get()->getResultArray();

        $urut = 0;

        foreach ($rows as $i) {
            $arsipIndId = $this->sisip('rpjmd_versi_indikator_sasaran', [
                'version_id'            => $versiId,
                'versi_sasaran_id'      => $arsipSasaranId,
                'source_indikator_id'   => (int) $i['id'],
                'indikator_sasaran'     => (string) $i['indikator_sasaran'],
                'definisi_op'           => $this->kosongJadiNull($i['definisi_op'] ?? null),
                'satuan'                => $this->kosongJadiNull($i['satuan'] ?? null),
                // Diterjemahkan SAAT dibekukan: master satuan bisa berubah nama
                // atau dihapus, dan arsip tidak boleh ikut berubah karenanya.
                'satuan_nama'           => $this->namaSatuan($i['satuan'] ?? null),
                'jenis_indikator'       => $this->kosongJadiNull($i['jenis_indikator'] ?? null),
                'baseline'              => $this->kosongJadiNull($i['baseline'] ?? null),
                'urutan'                => $urut++,
                'jenis_perubahan'       => $this->jenisPerubahanSah($i['jenis_perubahan'] ?? null),
                'perubahan_substansial' => (int) ($i['perubahan_substansial'] ?? 0),
                'created_at'            => $now,
            ]);
            $n['indikator_sasaran']++;

            foreach ($this->db->table('rpjmd_target')
                ->where('indikator_sasaran_id', (int) $i['id'])
                ->orderBy('tahun', 'ASC')->get()->getResultArray() as $tg) {
                $this->sisip('rpjmd_versi_target', [
                    'versi_indikator_id' => $arsipIndId,
                    'tahun'              => (int) $tg['tahun'],
                    'target_tahunan'     => $this->kosongJadiNull($tg['target_tahunan']),
                    'created_at'         => $now,
                ]);
                $n['target']++;
            }
        }
    }

    /* =========================================================
     * ARSIP -> ARSIP (DEEP COPY §10)
     * =======================================================*/

    public function salinDariVersi(int $dariVersiId, int $keVersiId): array
    {
        $this->pastikanDalamTransaksi('penyalinan arsip RPJMD');

        if ($dariVersiId === $keVersiId) {
            throw new RuntimeException('Versi asal dan tujuan penyalinan tidak boleh sama.');
        }

        $now = $this->sekarang();
        $n   = ['misi' => 0, 'tujuan' => 0, 'indikator_tujuan' => 0, 'target_tujuan' => 0,
            'sasaran' => 0, 'indikator_sasaran' => 0, 'target' => 0];

        foreach ($this->barisArsip('rpjmd_versi_misi', $dariVersiId) as $m) {
            $misiBaru = $this->sisip('rpjmd_versi_misi', [
                'version_id'        => $keVersiId,
                'source_misi_id'    => $m['source_misi_id'],
                'copied_from_id'    => (int) $m['id'],
                'visi'              => $m['visi'],
                'source_visi_id'    => $m['source_visi_id'],
                'misi'              => $m['misi'],
                'tahun_mulai'       => $m['tahun_mulai'],
                'tahun_akhir'       => $m['tahun_akhir'],
                'urutan'            => $m['urutan'],
                'jenis_perubahan'   => self::UBAH_TETAP,
                'catatan_perubahan' => $m['catatan_perubahan'],
                'created_at'        => $now,
            ]);
            $n['misi']++;

            foreach ($this->anak('rpjmd_versi_tujuan', 'versi_misi_id', (int) $m['id']) as $t) {
                $tujuanBaru = $this->sisip('rpjmd_versi_tujuan', [
                    'version_id'        => $keVersiId,
                    'versi_misi_id'     => $misiBaru,
                    'source_tujuan_id'  => $t['source_tujuan_id'],
                    'copied_from_id'    => (int) $t['id'],
                    'tujuan_rpjmd'      => $t['tujuan_rpjmd'],
                    'urutan'            => $t['urutan'],
                    'jenis_perubahan'   => self::UBAH_TETAP,
                    'catatan_perubahan' => $t['catatan_perubahan'],
                    'created_at'        => $now,
                ]);
                $n['tujuan']++;

                foreach ($this->anak('rpjmd_versi_indikator_tujuan', 'versi_tujuan_id', (int) $t['id']) as $it) {
                    $itBaru = $this->sisip('rpjmd_versi_indikator_tujuan', [
                        'version_id'          => $keVersiId,
                        'versi_tujuan_id'     => $tujuanBaru,
                        'source_indikator_id' => $it['source_indikator_id'],
                        'copied_from_id'      => (int) $it['id'],
                        'indikator_tujuan'    => $it['indikator_tujuan'],
                        'urutan'              => $it['urutan'],
                        'jenis_perubahan'     => self::UBAH_TETAP,
                        'created_at'          => $now,
                    ]);
                    $n['indikator_tujuan']++;

                    foreach ($this->anak('rpjmd_versi_target_tujuan', 'versi_indikator_tujuan_id', (int) $it['id']) as $tg) {
                        $this->sisip('rpjmd_versi_target_tujuan', [
                            'versi_indikator_tujuan_id' => $itBaru,
                            'tahun'                     => $tg['tahun'],
                            'target_tahunan'            => $tg['target_tahunan'],
                            // Nilai versi asal jadi pembanding "sebelum" di versi baru (§23).
                            'target_sebelumnya'         => $tg['target_tahunan'],
                            'created_at'                => $now,
                        ]);
                        $n['target_tujuan']++;
                    }
                }

                foreach ($this->anak('rpjmd_versi_sasaran', 'versi_tujuan_id', (int) $t['id']) as $s) {
                    $sasaranBaru = $this->sisip('rpjmd_versi_sasaran', [
                        'version_id'        => $keVersiId,
                        'versi_tujuan_id'   => $tujuanBaru,
                        'source_sasaran_id' => $s['source_sasaran_id'],
                        'copied_from_id'    => (int) $s['id'],
                        'sasaran_rpjmd'     => $s['sasaran_rpjmd'],
                        'csf'               => $s['csf'],
                        'urutan'            => $s['urutan'],
                        'jenis_perubahan'   => self::UBAH_TETAP,
                        'catatan_perubahan' => $s['catatan_perubahan'],
                        'created_at'        => $now,
                    ]);
                    $n['sasaran']++;

                    foreach ($this->anak('rpjmd_versi_indikator_sasaran', 'versi_sasaran_id', (int) $s['id']) as $i) {
                        $indBaru = $this->sisip('rpjmd_versi_indikator_sasaran', [
                            'version_id'            => $keVersiId,
                            'versi_sasaran_id'      => $sasaranBaru,
                            'source_indikator_id'   => $i['source_indikator_id'],
                            'copied_from_id'        => (int) $i['id'],
                            'indikator_sasaran'     => $i['indikator_sasaran'],
                            'definisi_op'           => $i['definisi_op'],
                            'satuan'                => $i['satuan'],
                            'satuan_nama'           => $i['satuan_nama'],
                            'jenis_indikator'       => $i['jenis_indikator'],
                            'baseline'              => $i['baseline'],
                            'urutan'                => $i['urutan'],
                            // Salinan lahir 'tetap': operator yang menyatakan apa
                            // yang berubah, bukan sistem yang menebaknya (§11).
                            'jenis_perubahan'       => self::UBAH_TETAP,
                            'perubahan_substansial' => 0,
                            'created_at'            => $now,
                        ]);
                        $n['indikator_sasaran']++;

                        foreach ($this->anak('rpjmd_versi_target', 'versi_indikator_id', (int) $i['id']) as $tg) {
                            $this->sisip('rpjmd_versi_target', [
                                'versi_indikator_id' => $indBaru,
                                'tahun'              => $tg['tahun'],
                                'target_tahunan'     => $tg['target_tahunan'],
                                'target_sebelumnya'  => $tg['target_tahunan'],
                                'created_at'         => $now,
                            ]);
                            $n['target']++;
                        }
                    }
                }
            }
        }

        return $n;
    }

    /* =========================================================
     * BACA ISI
     * =======================================================*/

    public function isi(int $versiId): array
    {
        if (! $this->siap()) {
            return [];
        }

        $misi = $this->barisArsip('rpjmd_versi_misi', $versiId);

        if ($misi === []) {
            return [];
        }

        $tujuan     = $this->petaAnak('rpjmd_versi_tujuan', 'versi_misi_id', $versiId);
        $indTujuan  = $this->petaAnak('rpjmd_versi_indikator_tujuan', 'versi_tujuan_id', $versiId);
        $sasaran    = $this->petaAnak('rpjmd_versi_sasaran', 'versi_tujuan_id', $versiId);
        $indSasaran = $this->petaAnak('rpjmd_versi_indikator_sasaran', 'versi_sasaran_id', $versiId);

        $targetTujuan = $this->petaTarget(
            'rpjmd_versi_target_tujuan',
            'versi_indikator_tujuan_id',
            $this->kolomId(array_merge(...array_values($indTujuan) ?: [[]]))
        );
        $target = $this->petaTarget(
            'rpjmd_versi_target',
            'versi_indikator_id',
            $this->kolomId(array_merge(...array_values($indSasaran) ?: [[]]))
        );

        foreach ($misi as &$m) {
            $m['tujuan'] = $tujuan[(int) $m['id']] ?? [];

            foreach ($m['tujuan'] as &$t) {
                $t['indikator_tujuan'] = $indTujuan[(int) $t['id']] ?? [];

                foreach ($t['indikator_tujuan'] as &$it) {
                    $it['target'] = $targetTujuan[(int) $it['id']] ?? [];
                }
                unset($it);

                $t['sasaran'] = $sasaran[(int) $t['id']] ?? [];

                foreach ($t['sasaran'] as &$s) {
                    $s['indikator'] = $indSasaran[(int) $s['id']] ?? [];

                    foreach ($s['indikator'] as &$i) {
                        $i['target'] = $target[(int) $i['id']] ?? [];
                    }
                    unset($i);
                }
                unset($s);
            }
            unset($t);
        }
        unset($m);

        return $misi;
    }

    public function ringkas(int $versiId): array
    {
        if (! $this->siap()) {
            return [];
        }

        $out = [];

        foreach ([
            'misi'              => 'rpjmd_versi_misi',
            'tujuan'            => 'rpjmd_versi_tujuan',
            'indikator_tujuan'  => 'rpjmd_versi_indikator_tujuan',
            'sasaran'           => 'rpjmd_versi_sasaran',
            'indikator_sasaran' => 'rpjmd_versi_indikator_sasaran',
        ] as $nama => $tabel) {
            $out[$nama] = (int) $this->db->table($tabel)->where('version_id', $versiId)->countAllResults();
        }

        return $out;
    }

    public function hitungLiveAktif(VersionScope $scope): array
    {
        $misi = $this->kolomId($this->db->table('rpjmd_misi')->select('id')
            ->where('tahun_mulai', $scope->periodeMulai())
            ->where('tahun_akhir', $scope->periodeAkhir())
            ->where('dihentikan_pada IS NULL', null, false)
            ->get()->getResultArray());

        if ($misi === []) {
            return ['misi' => 0, 'sasaran' => 0, 'indikator' => 0];
        }

        $tujuan = $this->kolomId($this->db->table('rpjmd_tujuan')->select('id')
            ->whereIn('misi_id', $misi)
            ->where('dihentikan_pada IS NULL', null, false)->get()->getResultArray());

        $sasaran = $tujuan === [] ? [] : $this->kolomId($this->db->table('rpjmd_sasaran')->select('id')
            ->whereIn('tujuan_id', $tujuan)
            ->where('dihentikan_pada IS NULL', null, false)->get()->getResultArray());

        $indikator = $sasaran === [] ? 0 : (int) $this->db->table('rpjmd_indikator_sasaran')
            ->whereIn('sasaran_id', $sasaran)
            ->where('dihentikan_pada IS NULL', null, false)->countAllResults();

        return ['misi' => count($misi), 'sasaran' => count($sasaran), 'indikator' => $indikator];
    }

    /**
     * =====================================================================
     * MENGAPA VERSI INI LEBIH SEDIKIT DARI MENU RPJMD?
     *
     * Menu RPJMD (RpjmdModel::getCompleteRpjmdStructure) menampilkan SELURUH
     * baris — termasuk yang sudah dihentikan (`dihentikan_pada` terisi) —
     * tanpa penanda apa pun. Pembekuan versi (bekukanDariLive) hanya
     * mengambil baris yang masih hidup pada periode lingkupnya. Dua layar itu
     * benar menurut aturannya masing-masing, tetapi pemakai melihat "15 di
     * menu, 11 di versi" dan mengira pembekuannya gagal.
     *
     * Fungsi ini menyebut baris yang tidak ikut, satu per satu, dengan
     * alasannya: dihentikan sendiri, ikut induknya yang dihentikan, misinya
     * di luar periode versi, atau (untuk draft yang sudah disunting/disalin)
     * memang tidak ada di versi ini. Yang dijawab adalah "kenapa", bukan
     * sekadar "berapa".
     * =====================================================================
     */
    public function barisLiveTakTerbekukan(int $versiId, VersionScope $scope): array
    {
        if (! $this->siap()) {
            return [];
        }

        $adaDiArsip = array_map('intval', $this->kolomId(
            $this->db->table('rpjmd_versi_indikator_sasaran')
                ->select('source_indikator_id AS id')
                ->where('version_id', $versiId)
                ->where('source_indikator_id IS NOT NULL', null, false)
                ->get()->getResultArray()
        ));

        $rows = $this->db->table('rpjmd_indikator_sasaran i')
            ->select('i.id, i.indikator_sasaran, i.dihentikan_pada, i.berlaku_sampai, i.alasan_dihentikan, '
                . 's.id AS sasaran_id, s.sasaran_rpjmd, s.dihentikan_pada AS s_stop, '
                . 't.id AS tujuan_id, t.tujuan_rpjmd, t.dihentikan_pada AS t_stop, '
                . 'm.id AS misi_id, m.misi, m.dihentikan_pada AS m_stop, m.tahun_mulai, m.tahun_akhir')
            ->join('rpjmd_sasaran s', 's.id = i.sasaran_id', 'left')
            ->join('rpjmd_tujuan t', 't.id = s.tujuan_id', 'left')
            ->join('rpjmd_misi m', 'm.id = t.misi_id', 'left')
            ->orderBy('m.id', 'ASC')->orderBy('t.id', 'ASC')->orderBy('s.id', 'ASC')->orderBy('i.id', 'ASC')
            ->get()->getResultArray();

        $mulai = $scope->periodeMulai();
        $akhir = $scope->periodeAkhir();
        $out   = [];

        foreach ($rows as $r) {
            if (in_array((int) $r['id'], $adaDiArsip, true)) {
                continue;
            }

            $periodeMisi = (int) $r['tahun_mulai'] . '-' . (int) $r['tahun_akhir'];

            if ($r['misi_id'] === null) {
                $alasan = 'Indikator ini tidak tersambung ke misi mana pun (sasaran/tujuan/misinya sudah tidak ada).';
            } elseif ((int) $r['tahun_mulai'] !== $mulai || (int) $r['tahun_akhir'] !== $akhir) {
                $alasan = 'Misinya berperiode ' . $periodeMisi . ', bukan ' . $mulai . '-' . $akhir
                    . ' — ia milik versi periode lain.';
            } elseif ($r['dihentikan_pada'] !== null) {
                $alasan = 'Indikator ini sudah DIHENTIKAN pada ' . $this->tanggalRingkas($r['dihentikan_pada'])
                    . ($r['berlaku_sampai'] !== null ? ' (berlaku sampai ' . (int) $r['berlaku_sampai'] . ')' : '')
                    . ($r['alasan_dihentikan'] ? ': ' . $r['alasan_dihentikan'] : '.');
            } elseif ($r['s_stop'] !== null) {
                $alasan = 'Sasarannya sudah dihentikan pada ' . $this->tanggalRingkas($r['s_stop']) . '.';
            } elseif ($r['t_stop'] !== null) {
                $alasan = 'Tujuannya sudah dihentikan pada ' . $this->tanggalRingkas($r['t_stop']) . '.';
            } elseif ($r['m_stop'] !== null) {
                $alasan = 'Misinya sudah dihentikan pada ' . $this->tanggalRingkas($r['m_stop']) . '.';
            } else {
                $alasan = 'Masih hidup di RPJMD berjalan, tetapi tidak ada di versi ini — '
                    . 'dihapus saat draft disunting, atau versi ini disalin dari versi lain / dimulai dari kosong.';
            }

            $out[] = [
                'tingkat'         => 'indikator_sasaran',
                'id'              => (int) $r['id'],
                'teks'            => (string) $r['indikator_sasaran'],
                'induk'           => (string) ($r['sasaran_rpjmd'] ?? '-'),
                'misi'            => (string) ($r['misi'] ?? '-'),
                'alasan'          => $alasan,
                'dihentikan_pada' => $r['dihentikan_pada'] ?? $r['s_stop'] ?? $r['t_stop'] ?? $r['m_stop'],
                'berlaku_sampai'  => $r['berlaku_sampai'] !== null ? (int) $r['berlaku_sampai'] : null,
            ];
        }

        return $out;
    }

    private function tanggalRingkas(?string $tanggal): string
    {
        if ($tanggal === null || $tanggal === '') {
            return '-';
        }

        $ts = strtotime($tanggal);

        return $ts ? date('d M Y', $ts) : $tanggal;
    }

    /* =========================================================
     * ARSIP -> LIVE (upsert + PENSIUN)
     * =======================================================*/

    public function terapkanKeLive(int $versiId, VersionScope $scope, int $berlakuMulaiTahun): array
    {
        $this->pastikanDalamTransaksi('penerapan arsip RPJMD ke tabel live');

        $now = $this->sekarang();
        $n   = ['dibuat' => 0, 'diperbarui' => 0, 'dipensiunkan' => 0, 'target_live_tak_tercantum' => 0];

        $dipakai = ['misi' => [], 'tujuan' => [], 'ind_tujuan' => [], 'sasaran' => [], 'ind_sasaran' => []];

        foreach ($this->barisArsip('rpjmd_versi_misi', $versiId) as $m) {
            $visiId  = $this->pastikanVisi($m, $now);
            $misiId  = $this->upsert('rpjmd_misi', $m['source_misi_id'], [
                'rpjmd_visi_id' => $visiId,
                'misi'          => (string) $m['misi'],
                'tahun_mulai'   => (int) $m['tahun_mulai'],
                'tahun_akhir'   => (int) $m['tahun_akhir'],
                'version_id'    => $versiId,
            ], ['status' => 'selesai', 'created_at' => $now], $now, $n);

            $this->tautkanArsip('rpjmd_versi_misi', (int) $m['id'], 'source_misi_id', $misiId);
            $dipakai['misi'][] = $misiId;

            foreach ($this->anak('rpjmd_versi_tujuan', 'versi_misi_id', (int) $m['id']) as $t) {
                $tujuanId = $this->upsert('rpjmd_tujuan', $t['source_tujuan_id'], [
                    'misi_id'      => $misiId,
                    'tujuan_rpjmd' => (string) $t['tujuan_rpjmd'],
                    'version_id'   => $versiId,
                ], ['created_at' => $now], $now, $n);

                $this->tautkanArsip('rpjmd_versi_tujuan', (int) $t['id'], 'source_tujuan_id', $tujuanId);
                $dipakai['tujuan'][] = $tujuanId;

                foreach ($this->anak('rpjmd_versi_indikator_tujuan', 'versi_tujuan_id', (int) $t['id']) as $it) {
                    $itId = $this->upsert('rpjmd_indikator_tujuan', $it['source_indikator_id'], [
                        'tujuan_id'        => $tujuanId,
                        'indikator_tujuan' => (string) $it['indikator_tujuan'],
                        'version_id'       => $versiId,
                    ], ['created_at' => $now], $now, $n);

                    $this->tautkanArsip('rpjmd_versi_indikator_tujuan', (int) $it['id'], 'source_indikator_id', $itId);
                    $dipakai['ind_tujuan'][] = $itId;

                    $this->terapkanTarget(
                        'rpjmd_target_tujuan',
                        'indikator_tujuan_id',
                        'target_tahunan',
                        $itId,
                        $this->anak('rpjmd_versi_target_tujuan', 'versi_indikator_tujuan_id', (int) $it['id']),
                        'target_tahunan',
                        $now,
                        $n
                    );
                }

                foreach ($this->anak('rpjmd_versi_sasaran', 'versi_tujuan_id', (int) $t['id']) as $s) {
                    $sasaranId = $this->upsert('rpjmd_sasaran', $s['source_sasaran_id'], [
                        'tujuan_id'     => $tujuanId,
                        'sasaran_rpjmd' => (string) $s['sasaran_rpjmd'],
                        'csf'           => $s['csf'],
                        'version_id'    => $versiId,
                    ], ['status' => 'selesai', 'created_at' => $now], $now, $n);

                    $this->tautkanArsip('rpjmd_versi_sasaran', (int) $s['id'], 'source_sasaran_id', $sasaranId);
                    $dipakai['sasaran'][] = $sasaranId;

                    foreach ($this->anak('rpjmd_versi_indikator_sasaran', 'versi_sasaran_id', (int) $s['id']) as $i) {
                        // Indikator yang DINYATAKAN dihentikan sengaja tidak
                        // didaftarkan sebagai "dipakai", sehingga penyapuan di
                        // bawah memensiunkannya. Menerapkannya sebagai baris
                        // aktif akan bertentangan dengan penandaannya sendiri.
                        if ($this->jenisPerubahanSah($i['jenis_perubahan']) === self::UBAH_DIHENTIKAN) {
                            continue;
                        }

                        $indId = $this->upsert('rpjmd_indikator_sasaran', $i['source_indikator_id'], [
                            'sasaran_id'            => $sasaranId,
                            'indikator_sasaran'     => (string) $i['indikator_sasaran'],
                            'definisi_op'           => $i['definisi_op'],
                            'satuan'                => $this->nullJadiKosong($i['satuan']),
                            'jenis_indikator'       => $i['jenis_indikator'],
                            'baseline'              => $i['baseline'],
                            'jenis_perubahan'       => $this->jenisPerubahanSah($i['jenis_perubahan']),
                            'perubahan_substansial' => (int) $i['perubahan_substansial'],
                            'version_id'            => $versiId,
                        ], ['created_at' => $now], $now, $n);

                        // Lineage indikator pengganti: arsip menyimpan id ARSIP
                        // pendahulunya; tabel live butuh id LIVE-nya.
                        $this->tautkanLineage('rpjmd_indikator_sasaran', 'rpjmd_versi_indikator_sasaran', $indId, $i);
                        $this->tautkanArsip('rpjmd_versi_indikator_sasaran', (int) $i['id'], 'source_indikator_id', $indId);
                        $dipakai['ind_sasaran'][] = $indId;

                        $this->terapkanTarget(
                            'rpjmd_target',
                            'indikator_sasaran_id',
                            'target_tahunan',
                            $indId,
                            $this->anak('rpjmd_versi_target', 'versi_indikator_id', (int) $i['id']),
                            'target_tahunan',
                            $now,
                            $n
                        );
                    }
                }
            }
        }

        $n['dipensiunkan'] = $this->pensiunkanYangHilang($versiId, $scope, $dipakai, $berlakuMulaiTahun, $now);

        return $n;
    }

    /**
     * Pensiunkan baris live yang tidak lagi tercantum di arsip.
     *
     * TIDAK ADA DELETE di sini, dan itu bukan kehati-hatian berlebihan:
     * rpjmd_sasaran -> renstra_tujuan CASCADE merambat ke Renstra 38 OPD,
     * lalu ke target_rencana (Renaksi) dan lakip_analisis_faktor.
     *
     * Penyapuan DIBATASI PERIODE lingkup — satu kabupaten lazim punya beberapa
     * periode RPJMD sekaligus, dan tanpa batas ini menetapkan versi 2025-2029
     * akan memensiunkan seluruh RPJMD 2020-2024. Pembatasan yang sama sudah
     * terbukti perlu di IkuRevisiModel::pensiunkanYangHilang().
     */
    private function pensiunkanYangHilang(
        int $versiId,
        VersionScope $scope,
        array $dipakai,
        int $berlakuMulaiTahun,
        string $now
    ): int {
        $alasan = $this->alasanPensiun($versiId);
        $sampai = $berlakuMulaiTahun - 1;
        $total  = 0;

        // --- misi dalam periode ini ---
        $misiHidup = $this->kolomId($this->db->table('rpjmd_misi')->select('id')
            ->where('tahun_mulai', $scope->periodeMulai())
            ->where('tahun_akhir', $scope->periodeAkhir())
            ->where('dihentikan_pada IS NULL', null, false)
            ->get()->getResultArray());

        $misiHilang = array_values(array_diff($misiHidup, $dipakai['misi']));
        $total += $this->pensiunkan('rpjmd_misi', $misiHilang, $sampai, $alasan, $now);

        // Turunan di bawah misi yang dipensiunkan ikut pensiun, ditambah
        // turunan yang hilang dari misi yang masih hidup.
        $tujuanHilang = $this->hilangDiBawah('rpjmd_tujuan', 'misi_id', $misiHidup, $misiHilang, $dipakai['tujuan']);
        $total += $this->pensiunkan('rpjmd_tujuan', $tujuanHilang, $sampai, $alasan, $now);

        $tujuanHidup = $this->kolomId($this->db->table('rpjmd_tujuan')->select('id')
            ->whereIn('misi_id', $misiHidup ?: [0])
            ->where('dihentikan_pada IS NULL', null, false)->get()->getResultArray());

        $itHilang = $this->hilangDiBawah('rpjmd_indikator_tujuan', 'tujuan_id', $tujuanHidup, $tujuanHilang, $dipakai['ind_tujuan']);
        $total += $this->pensiunkan('rpjmd_indikator_tujuan', $itHilang, $sampai, $alasan, $now);

        $sasaranHilang = $this->hilangDiBawah('rpjmd_sasaran', 'tujuan_id', $tujuanHidup, $tujuanHilang, $dipakai['sasaran']);
        $total += $this->pensiunkan('rpjmd_sasaran', $sasaranHilang, $sampai, $alasan, $now);

        $sasaranHidup = $this->kolomId($this->db->table('rpjmd_sasaran')->select('id')
            ->whereIn('tujuan_id', $tujuanHidup ?: [0])
            ->where('dihentikan_pada IS NULL', null, false)->get()->getResultArray());

        $indHilang = $this->hilangDiBawah('rpjmd_indikator_sasaran', 'sasaran_id', $sasaranHidup, $sasaranHilang, $dipakai['ind_sasaran']);
        $total += $this->pensiunkan('rpjmd_indikator_sasaran', $indHilang, $sampai, $alasan, $now, true);

        return $total;
    }

    /** Visi tidak punya periode; dipakai ulang bila masih ada, dibuat bila belum. */
    private function pastikanVisi(array $arsipMisi, string $now): ?int
    {
        $id   = $arsipMisi['source_visi_id'] !== null ? (int) $arsipMisi['source_visi_id'] : null;
        $teks = $this->kosongJadiNull($arsipMisi['visi'] ?? null);

        if ($id !== null) {
            $ada = $this->db->table('rpjmd_visi')->where('id', $id)->countAllResults() > 0;

            if ($ada) {
                return $id;
            }
        }

        if ($teks === null) {
            return null;
        }

        $cocok = $this->db->table('rpjmd_visi')->select('id')->where('visi', $teks)->get()->getRowArray();

        if ($cocok !== null) {
            return (int) $cocok['id'];
        }

        // rpjmd_visi.created_at/updated_at bertipe TIMESTAMP NOT NULL tanpa
        // default — harus diisi eksplisit.
        return $this->sisip('rpjmd_visi', ['visi' => $teks, 'created_at' => $now, 'updated_at' => $now]);
    }
}
