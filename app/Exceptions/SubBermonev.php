<?php

namespace App\Exceptions;

/**
 * Sub Rencana Aksi tidak boleh dibuang karena capaian MONEV-nya sudah tersimpan.
 *
 * =====================================================================
 * MENGAPA BUKAN RuntimeException POLOS
 *
 * Pesan teksnya saja tidak cukup. Setelah penolakan, form rencana aksi
 * dirender ulang dari old() — yaitu dari kiriman yang ditolak — sehingga sub
 * yang gagal dibuang justru TIDAK TAMPIL lagi di layar, padahal masih ada di
 * basis data. Pemakai tidak melihat apa yang harus "dikembalikan", menekan
 * Simpan lagi, dan ditolak dengan pesan yang sama. Di log aktivitas pola itu
 * terbaca jelas: satu OPD gagal 12 kali dalam semalam.
 *
 * Karena itu galat ini membawa DATA sub yang ditolak (id, teks, baris,
 * capaiannya), supaya controller bisa mengembalikannya ke form dan menautkan
 * langsung ke layar MONEV tempat capaiannya dikosongkan.
 *
 * Turunan AturanBisnis: pesannya memang untuk dibaca pengguna.
 */
class SubBermonev extends AturanBisnis
{
    /**
     * @var list<array{id: int, teks: string, baris_rencana: int, capaian: array<int, string>}>
     *      capaian hanya memuat triwulan yang terisi, dikunci 1..4
     */
    private array $sub;

    /**
     * @param list<array{id: int, teks: string, baris_rencana: int, capaian: array<int, string>}> $sub
     */
    public function __construct(array $sub, string $pesan)
    {
        parent::__construct($pesan);
        $this->sub = array_values($sub);
    }

    /**
     * @return list<array{id: int, teks: string, baris_rencana: int, capaian: array<int, string>}>
     */
    public function sub(): array
    {
        return $this->sub;
    }

    /** Daftar id sub yang ditolak. @return list<int> */
    public function idSub(): array
    {
        return array_map(static fn (array $s): int => (int) $s['id'], $this->sub);
    }

    /**
     * Ringkasan capaian satu sub untuk ditampilkan: "TW I: 0, TW II: 25".
     *
     * @param array<int, string> $capaian
     */
    public static function ringkasCapaian(array $capaian): string
    {
        $romawi = [1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV'];
        $bagian = [];

        foreach ([1, 2, 3, 4] as $q) {
            if (array_key_exists($q, $capaian)) {
                $bagian[] = 'TW ' . $romawi[$q] . ': ' . $capaian[$q];
            }
        }

        return implode(', ', $bagian);
    }
}
