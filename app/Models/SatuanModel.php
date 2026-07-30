<?php

namespace App\Models;

use CodeIgniter\Model;

class SatuanModel extends Model
{
    protected $table = 'satuan';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = ['satuan', 'tipe'];

    protected $useTimestamps = false;

    /** Tipe satuan: hanya `predikat` yang mengubah perilaku form input. */
    public const TIPE = [
        'angka'    => 'Angka',
        'persen'   => 'Persen',
        'predikat' => 'Predikat (pakai skala)',
    ];

    protected $validationRules = [
        'satuan' => 'required|string|max_length[100]',
    ];
    protected $validationMessages = [
        'satuan' => [
            'required' => 'Nama satuan harus diisi',
        ],
    ];

    public function getAllSatuan(): array
    {
        return $this->orderBy('satuan', 'ASC')->findAll();
    }

    /* =========================================================
     *  SKALA PREDIKAT (tabel satuan_skala)
     *
     *  Untuk satuan bertipe `predikat`, tiap kode (mis. WTP) punya SKOR.
     *  Skor itulah yang dipakai rumus Capaian Total —
     *  lihat app/Helpers/capaian_helper.php.
     * =======================================================*/

    /** Tabelnya opsional: instalasi lama boleh belum menjalankan migrasinya. */
    private function skalaSiap(): bool
    {
        return $this->db->tableExists('satuan_skala');
    }

    /**
     * Skala satu satuan, urut dari skor terendah.
     *
     * @return array<int, array<string, mixed>>
     */
    public function skalaBySatuan(?int $satuanId): array
    {
        if (empty($satuanId) || !$this->skalaSiap()) {
            return [];
        }

        return $this->db->table('satuan_skala')
            ->select('id, satuan_id, kode, label, nilai, urutan')
            ->where('satuan_id', $satuanId)
            ->orderBy('urutan', 'ASC')
            ->orderBy('nilai', 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * Skala SEMUA satuan sekaligus, dikelompokkan per satuan_id.
     *
     * @return array<int, array<int, array<string, mixed>>>
     */
    public function semuaSkala(): array
    {
        if (!$this->skalaSiap()) {
            return [];
        }

        $rows = $this->db->table('satuan_skala')
            ->select('id, satuan_id, kode, label, nilai, urutan')
            ->orderBy('satuan_id', 'ASC')
            ->orderBy('urutan', 'ASC')
            ->orderBy('nilai', 'ASC')
            ->get()
            ->getResultArray();

        $map = [];
        foreach ($rows as $row) {
            $map[(int) $row['satuan_id']][] = $row;
        }

        return $map;
    }

    /**
     * Tulis ulang skala sebuah satuan (hapus lalu isi lagi).
     *
     * Ditulis ulang total — bukan diff — karena skalanya cuma beberapa baris
     * dan kode-nya boleh diubah; yang disimpan di target/capaian adalah TEKS
     * kode, bukan id, sehingga id baris tidak perlu dipertahankan.
     *
     * @param array<int, array{kode: string, label?: string|null, nilai: float|string}> $skala
     */
    public function simpanSkala(int $satuanId, array $skala): void
    {
        if (!$this->skalaSiap()) {
            return;
        }

        $tabel = $this->db->table('satuan_skala');
        $tabel->where('satuan_id', $satuanId)->delete();

        $baris = [];
        $urut  = 0;
        $sudah = [];
        foreach ($skala as $item) {
            $kode = trim((string) ($item['kode'] ?? ''));
            if ($kode === '' || mb_strlen($kode) > 50) {
                continue;
            }

            // Kode ganda ditolak diam-diam: UNIQUE (satuan_id, kode) di DB.
            $kunci = mb_strtolower($kode);
            if (isset($sudah[$kunci])) {
                continue;
            }
            $sudah[$kunci] = true;

            $nilai = str_replace(',', '.', trim((string) ($item['nilai'] ?? '')));
            if (!is_numeric($nilai)) {
                continue;
            }

            $baris[] = [
                'satuan_id' => $satuanId,
                'kode'      => $kode,
                'label'     => ($item['label'] ?? '') === '' ? null : mb_substr(trim((string) $item['label']), 0, 255),
                'nilai'     => (float) $nilai,
                'urutan'    => ++$urut,
            ];
        }

        if ($baris !== []) {
            $tabel->insertBatch($baris);
        }
    }
}
