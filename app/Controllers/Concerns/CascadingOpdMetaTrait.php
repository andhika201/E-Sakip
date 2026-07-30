<?php

namespace App\Controllers\Concerns;

/**
 * Meta tampilan matriks Cascading Perangkat Daerah (hasil
 * CascadingModel::getCascadingMatrixByOpd) — SATU sumber untuk semua pemakai:
 *
 *   - AdminOpd\CascadingController   (pengelola data OPD)
 *   - AdminKab\CascadingController   (mode OPD, monitoring lintas OPD)
 *   - UserController                 (halaman publik)
 *
 * Sebelumnya tiap controller punya salinan sendiri, dan salinan admin_kab
 * maupun publik ketinggalan saat jenjang PELAKSANA ditambahkan (lihat migrasi
 * 2026-07-27-000009) sehingga Pelaksana tidak pernah tampil di luar admin_opd.
 * Trait ini menutup celah itu sekaligus mencegah terulang.
 *
 * Jenjang yang ditangani:
 *   Tujuan/Sasaran RPJMD -> Tujuan Renstra (+Indikator Tujuan)
 *   -> Sasaran/Indikator ES II (renstra_sasaran / renstra_indikator_sasaran)
 *   -> Sasaran/Indikator ES III  (cascading_sasaran_opd level=es3)
 *   -> Sasaran/Indikator ES IV   (cascading_sasaran_opd level=es4)
 *   -> Sasaran/Indikator PELAKSANA (cascading_sasaran_opd level=pelaksana)
 */
trait CascadingOpdMetaTrait
{
    /** Kunci meta rowspan/firstShow — dipakai juga oleh view sebagai kontrak. */
    protected const CASC_META_KEYS = [
        'tujuan',
        'sasaran',
        'tujuan_renstra',
        'indikator_tujuan',
        'sasaran_renstra',
        'indikator',
        'es3',
        'es3_indikator',
        'es4',
        // Indikator ES IV bisa menaungi beberapa baris Pelaksana -> butuh rowspan sendiri.
        'es4_indikator',
        'pelaksana',
    ];

    /**
     * Beri id semu pada jenjang yang kosong agar baris tanpa data tidak saling
     * tumpang tindih saat dihitung rowspan-nya.
     *
     * @param array<int, array<string, mixed>> $rows
     */
    protected function cascOpdPreprocessEmptyIds(array &$rows): void
    {
        foreach ($rows as $index => &$r) {
            if (empty($r['tujuan_id'])) {
                $r['tujuan_id'] = 'empty_tujuan_' . $index;
            }
            if (empty($r['sasaran_id'])) {
                $r['sasaran_id'] = 'empty_sasaran_' . $index;
            }
            if (empty($r['renstra_tujuan_id'])) {
                $r['renstra_tujuan_id'] = 'empty_rt_' . $index;
            }
            if (empty($r['indikator_tujuan_id'])) {
                $r['indikator_tujuan_id'] = 'empty_it_' . $r['renstra_tujuan_id'];
            }
            if (empty($r['renstra_sasaran_id'])) {
                $r['renstra_sasaran_id'] = 'empty_rs_' . $index;
            }
            if (empty($r['indikator_id'])) {
                $r['indikator_id'] = 'empty_ris_' . $index;
            }
        }
        unset($r);
    }

    /** Kunci gabungan indikator ES III (sasaran ES III + indikatornya). */
    protected function cascOpdEs3IndikatorKey(array $r): string
    {
        return ($r['es3_id'] ?? '') . '_' . ($r['es3_indikator_id'] ?? null);
    }

    /** Kunci gabungan indikator ES IV (sasaran ES IV + indikatornya). */
    protected function cascOpdEs4IndikatorKey(array $r): string
    {
        return ($r['es4_id'] ?? '') . '_' . ($r['es4_indikator_id'] ?? null);
    }

    /**
     * Jumlah baris per sel hierarki (nilai rowspan).
     *
     * @param array<int, array<string, mixed>> $rows
     *
     * @return array<string, array<string, int>>
     */
    protected function cascOpdRowspanMeta(array $rows): array
    {
        $meta = array_fill_keys(self::CASC_META_KEYS, []);

        foreach ($rows as $r) {
            foreach ([
                'tujuan'           => $r['tujuan_id'] ?? null,
                'sasaran'          => $r['sasaran_id'] ?? null,
                'tujuan_renstra'   => $r['renstra_tujuan_id'] ?? null,
                'indikator_tujuan' => $r['indikator_tujuan_id'] ?? null,
                'sasaran_renstra'  => $r['renstra_sasaran_id'] ?? null,
                'indikator'        => $r['indikator_id'] ?? null,
            ] as $kunci => $id) {
                $meta[$kunci][$id] = ($meta[$kunci][$id] ?? 0) + 1;
            }

            if (!empty($r['es3_id'])) {
                $meta['es3'][$r['es3_id']] = ($meta['es3'][$r['es3_id']] ?? 0) + 1;
            }

            $k3 = $this->cascOpdEs3IndikatorKey($r);
            $meta['es3_indikator'][$k3] = ($meta['es3_indikator'][$k3] ?? 0) + 1;

            if (!empty($r['es4_id'])) {
                $meta['es4'][$r['es4_id']] = ($meta['es4'][$r['es4_id']] ?? 0) + 1;

                $k4 = $this->cascOpdEs4IndikatorKey($r);
                $meta['es4_indikator'][$k4] = ($meta['es4_indikator'][$k4] ?? 0) + 1;
            }

            if (!empty($r['pelaksana_id'])) {
                $meta['pelaksana'][$r['pelaksana_id']] = ($meta['pelaksana'][$r['pelaksana_id']] ?? 0) + 1;
            }
        }

        return $meta;
    }

    /**
     * Index baris pertama tiap sel hierarki (penentu di baris mana sel dicetak).
     *
     * @param array<int, array<string, mixed>> $rows
     *
     * @return array<string, array<string, int>>
     */
    protected function cascOpdFirstShowMeta(array $rows): array
    {
        $shown = array_fill_keys(self::CASC_META_KEYS, []);

        foreach ($rows as $index => $r) {
            foreach ([
                'tujuan'           => $r['tujuan_id'] ?? null,
                'sasaran'          => $r['sasaran_id'] ?? null,
                'tujuan_renstra'   => $r['renstra_tujuan_id'] ?? null,
                'indikator_tujuan' => $r['indikator_tujuan_id'] ?? null,
                'sasaran_renstra'  => $r['renstra_sasaran_id'] ?? null,
                'indikator'        => $r['indikator_id'] ?? null,
            ] as $kunci => $id) {
                if (!isset($shown[$kunci][$id])) {
                    $shown[$kunci][$id] = $index;
                }
            }

            if (!empty($r['es3_id']) && !isset($shown['es3'][$r['es3_id']])) {
                $shown['es3'][$r['es3_id']] = $index;
            }

            $k3 = $this->cascOpdEs3IndikatorKey($r);
            if (!isset($shown['es3_indikator'][$k3])) {
                $shown['es3_indikator'][$k3] = $index;
            }

            if (!empty($r['es4_id'])) {
                if (!isset($shown['es4'][$r['es4_id']])) {
                    $shown['es4'][$r['es4_id']] = $index;
                }
                $k4 = $this->cascOpdEs4IndikatorKey($r);
                if (!isset($shown['es4_indikator'][$k4])) {
                    $shown['es4_indikator'][$k4] = $index;
                }
            }

            if (!empty($r['pelaksana_id']) && !isset($shown['pelaksana'][$r['pelaksana_id']])) {
                $shown['pelaksana'][$r['pelaksana_id']] = $index;
            }
        }

        return $shown;
    }

    /**
     * Pohon Kinerja Perangkat Daerah dari baris matriks yang sama.
     *
     * Bentuk keluaran (dipakai adminOpd/cascading/_pohon_opd_tree):
     *   tujuan RPJMD -> sasarans -> tujuan_renstras -> es2s -> es3s -> es4s -> pelaksanas
     *
     * @param array<int, array<string, mixed>> $rows
     *
     * @return array<string, mixed>
     */
    protected function cascOpdTree(array $rows): array
    {
        $tree = [];

        foreach ($rows as $r) {
            $tId = rtrim('_' . ($r['tujuan_id'] ?? 'none'), '_');
            if (!isset($tree[$tId])) {
                $tree[$tId] = [
                    'nama'     => $r['tujuan_rpjmd'] ?: '(Tanpa Tujuan RPJMD)',
                    'sasarans' => [],
                ];
            }

            $sId = rtrim('_' . ($r['sasaran_id'] ?? 'none'), '_');
            if (!isset($tree[$tId]['sasarans'][$sId])) {
                $tree[$tId]['sasarans'][$sId] = [
                    'nama'            => $r['sasaran_rpjmd'] ?: '(Tanpa Sasaran RPJMD)',
                    'tujuan_renstras' => [],
                ];
            }

            $rtId = rtrim('_' . ($r['renstra_tujuan_id'] ?? 'none'), '_');
            if (!isset($tree[$tId]['sasarans'][$sId]['tujuan_renstras'][$rtId])) {
                $tree[$tId]['sasarans'][$sId]['tujuan_renstras'][$rtId] = [
                    'nama'             => $r['renstra_tujuan'] ?: '(Tanpa Tujuan Renstra)',
                    'indikator_tujuan' => [],
                    'es2s'             => [],
                ];
            }
            $rtNode = &$tree[$tId]['sasarans'][$sId]['tujuan_renstras'][$rtId];

            $itId = $r['indikator_tujuan_id'] ?? null;
            if (!empty($itId) && !empty($r['indikator_tujuan'])) {
                $rtNode['indikator_tujuan'][$itId] = $r['indikator_tujuan'];
            }

            // Tanpa Sasaran ES II tidak ada cabang yang bisa digantung.
            if (empty($r['renstra_sasaran_id']) && empty($r['renstra_sasaran'])) {
                unset($rtNode);
                continue;
            }

            $rsId = rtrim('_' . ($r['renstra_sasaran_id'] ?? 'none'), '_');
            if (!isset($rtNode['es2s'][$rsId])) {
                $rtNode['es2s'][$rsId] = [
                    'nama'       => $r['renstra_sasaran'] ?: '(Tanpa Sasaran ES.II)',
                    'csf'        => $r['csf_es2'] ?? null,
                    'indikators' => [],
                    'es3s'       => [],
                ];
            }
            $es2Node = &$rtNode['es2s'][$rsId];

            if (!empty($r['indikator_id']) && is_numeric($r['indikator_id'])) {
                $es2Node['indikators'][$r['indikator_id']] = $r['indikator_sasaran'];
            }

            $es3Id = $r['es3_id'] ?? null;
            if (empty($es3Id)) {
                unset($es2Node, $rtNode);
                continue;
            }

            if (!isset($es2Node['es3s'][$es3Id])) {
                $es2Node['es3s'][$es3Id] = [
                    'nama'       => $r['es3_sasaran'],
                    'csf'        => $r['csf_es3'] ?? null,
                    'indikators' => [],
                    'es4s'       => [],
                ];
            }
            $es3Node = &$es2Node['es3s'][$es3Id];

            if (!empty($r['es3_indikator_id'])) {
                $es3Node['indikators'][$r['es3_indikator_id']] = $r['es3_indikator'];
            }

            $es4Id = $r['es4_id'] ?? null;
            if (empty($es4Id)) {
                unset($es3Node, $es2Node, $rtNode);
                continue;
            }

            if (!isset($es3Node['es4s'][$es4Id])) {
                $es3Node['es4s'][$es4Id] = [
                    'nama'       => $r['es4_sasaran'],
                    'csf'        => $r['csf_es4'] ?? null,
                    'indikators' => [],
                    'pelaksanas' => [],
                ];
            }
            $es4Node = &$es3Node['es4s'][$es4Id];

            if (!empty($r['es4_indikator_id'])) {
                $es4Node['indikators'][$r['es4_indikator_id']] = $r['es4_indikator'];
            }

            // Cabang PELAKSANA — jenjang terakhir, di bawah Eselon IV / JF.
            $pelId = $r['pelaksana_id'] ?? null;
            if (!empty($pelId)) {
                if (!isset($es4Node['pelaksanas'][$pelId])) {
                    $es4Node['pelaksanas'][$pelId] = [
                        'nama'       => $r['pelaksana_sasaran'],
                        'csf'        => $r['csf_pelaksana'] ?? null,
                        'indikators' => [],
                    ];
                }
                if (!empty($r['pelaksana_indikator_id'])) {
                    $es4Node['pelaksanas'][$pelId]['indikators'][$r['pelaksana_indikator_id']] = $r['pelaksana_indikator'];
                }
            }

            unset($es4Node, $es3Node, $es2Node, $rtNode);
        }

        return $tree;
    }
}
