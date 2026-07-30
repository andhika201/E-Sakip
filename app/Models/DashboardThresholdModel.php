<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Ambang status capaian kinerja (tabel `dashboard_status_thresholds`).
 *
 * SATU-SATUNYA sumber rentang & warna status capaian. Jangan menyalin angka
 * rentangnya ke controller/view/JavaScript — pakai getAchievementStatus() di
 * app/Helpers/dashboard_status_helper.php yang membaca model ini.
 */
class DashboardThresholdModel extends Model
{
    protected $table          = 'dashboard_status_thresholds';
    protected $primaryKey     = 'id';
    protected $returnType     = 'array';
    protected $useTimestamps  = true;
    protected $createdField   = 'created_at';
    protected $updatedField   = 'updated_at';
    protected $dateFormat     = 'datetime';
    protected $protectFields  = true;
    protected $allowedFields  = [
        'code', 'name', 'min_value', 'max_value', 'color', 'icon',
        'sort_order', 'is_active', 'effective_from', 'created_by', 'updated_by',
    ];

    /**
     * Kode status yang dikenal aplikasi. `code` TIDAK boleh diubah lewat form —
     * kode inilah yang dirujuk kode program (mis. "critical" untuk insight
     * indikator kritis), namanya boleh diganti bebas oleh Super Admin.
     */
    public const KNOWN_CODES = ['critical', 'attention', 'near_target', 'achieved', 'exceeded'];

    /** Palet warna terbatas — form hanya boleh memilih dari sini (bukan CSS bebas). */
    public const COLORS = [
        'merah'  => ['label' => 'Merah',  'hex' => '#d64545', 'soft' => '#fdecec', 'bs' => 'danger'],
        'oranye' => ['label' => 'Oranye', 'hex' => '#e07b39', 'soft' => '#fdf0e6', 'bs' => 'warning'],
        'kuning' => ['label' => 'Kuning', 'hex' => '#d9a520', 'soft' => '#fcf6e3', 'bs' => 'warning'],
        'hijau'  => ['label' => 'Hijau',  'hex' => '#0a8f50', 'soft' => '#e8f5ee', 'bs' => 'success'],
        'biru'   => ['label' => 'Biru',   'hex' => '#3f6296', 'soft' => '#eaf0f8', 'bs' => 'primary'],
        'abu'    => ['label' => 'Abu-abu', 'hex' => '#8a968f', 'soft' => '#f1f3f2', 'bs' => 'secondary'],
    ];

    /** Ikon yang boleh dipilih (Font Awesome 6, sudah dimuat template). */
    public const ICONS = [
        'fa-circle-exclamation'   => 'Tanda seru (lingkaran)',
        'fa-triangle-exclamation' => 'Tanda seru (segitiga)',
        'fa-circle-half-stroke'   => 'Lingkaran separuh',
        'fa-circle-check'         => 'Centang',
        'fa-arrow-trend-up'       => 'Panah naik',
        'fa-arrow-trend-down'     => 'Panah turun',
        'fa-flag'                 => 'Bendera',
        'fa-star'                 => 'Bintang',
    ];

    /** Konfigurasi bawaan — dipakai seeder, tombol reset, dan fallback saat tabel belum ada. */
    public const DEFAULTS = [
        ['code' => 'critical',    'name' => 'Kritis',           'min_value' => 0,      'max_value' => 59.99,  'color' => 'merah',  'icon' => 'fa-circle-exclamation',   'sort_order' => 1],
        ['code' => 'attention',   'name' => 'Perlu Perhatian',  'min_value' => 60,     'max_value' => 79.99,  'color' => 'oranye', 'icon' => 'fa-triangle-exclamation', 'sort_order' => 2],
        ['code' => 'near_target', 'name' => 'Mendekati Target', 'min_value' => 80,     'max_value' => 94.99,  'color' => 'kuning', 'icon' => 'fa-circle-half-stroke',   'sort_order' => 3],
        ['code' => 'achieved',    'name' => 'Tercapai',         'min_value' => 95,     'max_value' => 105,    'color' => 'hijau',  'icon' => 'fa-circle-check',         'sort_order' => 4],
        ['code' => 'exceeded',    'name' => 'Melampaui Target', 'min_value' => 105.01, 'max_value' => null,   'color' => 'biru',   'icon' => 'fa-arrow-trend-up',       'sort_order' => 5],
    ];

    /**
     * Seluruh baris untuk halaman konfigurasi (termasuk yang nonaktif).
     *
     * @return array<int, array<string, mixed>>
     */
    public function semua(): array
    {
        if (!$this->db->tableExists($this->table)) {
            return $this->bawaanSebagaiBaris();
        }

        $rows = $this->orderBy('sort_order', 'ASC')->orderBy('id', 'ASC')->findAll();

        return $rows !== [] ? $rows : $this->bawaanSebagaiBaris();
    }

    /**
     * Ambang AKTIF, terurut dari rentang terendah. Inilah yang dipakai
     * getAchievementStatus(). Bila tabel/isian belum ada, jatuh ke bawaan
     * supaya dashboard tetap berjalan (tidak pernah melempar error).
     *
     * @return array<int, array<string, mixed>>
     */
    public function aktif(): array
    {
        $rows = array_values(array_filter($this->semua(), static fn ($r) => (int) ($r['is_active'] ?? 1) === 1));

        usort($rows, static function ($a, $b) {
            $am = $a['min_value'] === null ? -INF : (float) $a['min_value'];
            $bm = $b['min_value'] === null ? -INF : (float) $b['min_value'];
            return $am <=> $bm ?: ((int) ($a['sort_order'] ?? 0) <=> (int) ($b['sort_order'] ?? 0));
        });

        return $rows;
    }

    /** @return array<int, array<string, mixed>> */
    private function bawaanSebagaiBaris(): array
    {
        $out = [];
        foreach (self::DEFAULTS as $i => $row) {
            $out[] = $row + ['id' => $i + 1, 'is_active' => 1, 'effective_from' => null];
        }
        return $out;
    }

    /**
     * Validasi seluruh konfigurasi sebagai satu kesatuan.
     *
     * Aturan (lihat juga dokumentasi di view):
     *  - min_value <= max_value
     *  - rentang aktif tidak boleh tumpang tindih
     *  - tidak boleh ada celah antar rentang aktif (toleransi 0,01)
     *  - hanya satu rentang aktif yang boleh max_value NULL, dan ia harus paling atas
     *  - warna wajib dari palet COLORS
     *
     * @param array<int, array<string, mixed>> $rows
     *
     * @return string[] daftar pesan kesalahan (kosong = sah)
     */
    public function validasiRentang(array $rows): array
    {
        $errors = [];

        $aktif = [];
        foreach ($rows as $r) {
            $nama = trim((string) ($r['name'] ?? ''));
            if ($nama === '') {
                $errors[] = 'Nama status wajib diisi (kode: ' . esc((string) ($r['code'] ?? '-')) . ').';
            }
            if (!array_key_exists((string) ($r['color'] ?? ''), self::COLORS)) {
                $errors[] = 'Warna status "' . esc($nama) . '" tidak dikenal.';
            }
            $icon = (string) ($r['icon'] ?? '');
            if ($icon !== '' && !array_key_exists($icon, self::ICONS)) {
                $errors[] = 'Ikon status "' . esc($nama) . '" tidak dikenal.';
            }

            $min = $r['min_value'];
            $max = $r['max_value'];
            if ($min !== null && $max !== null && (float) $min > (float) $max) {
                $errors[] = 'Status "' . esc($nama) . '": nilai minimum tidak boleh lebih besar dari maksimum.';
            }

            if ((int) ($r['is_active'] ?? 0) === 1) {
                $aktif[] = $r;
            }
        }

        if ($aktif === []) {
            $errors[] = 'Minimal satu status harus aktif.';
            return $errors;
        }

        usort($aktif, static function ($a, $b) {
            $am = $a['min_value'] === null ? -INF : (float) $a['min_value'];
            $bm = $b['min_value'] === null ? -INF : (float) $b['min_value'];
            return $am <=> $bm;
        });

        $tanpaBatasAtas = array_values(array_filter($aktif, static fn ($r) => $r['max_value'] === null));
        if (count($tanpaBatasAtas) > 1) {
            $errors[] = 'Hanya satu status aktif yang boleh dibiarkan tanpa batas maksimum.';
        }
        if (count($tanpaBatasAtas) === 1 && ($aktif[count($aktif) - 1]['code'] ?? null) !== $tanpaBatasAtas[0]['code']) {
            $errors[] = 'Status tanpa batas maksimum harus menjadi rentang tertinggi.';
        }

        for ($i = 1; $i < count($aktif); $i++) {
            $sebelum = $aktif[$i - 1];
            $kini    = $aktif[$i];

            if ($sebelum['max_value'] === null) {
                $errors[] = 'Status "' . esc((string) $sebelum['name']) . '" tanpa batas maksimum menutup rentang di atasnya.';
                continue;
            }

            $batasAtas = (float) $sebelum['max_value'];
            $batasBawah = $kini['min_value'] === null ? -INF : (float) $kini['min_value'];

            if ($batasBawah <= $batasAtas) {
                $errors[] = 'Rentang "' . esc((string) $sebelum['name']) . '" dan "' . esc((string) $kini['name'])
                    . '" saling tumpang tindih.';
                continue;
            }
            // Celah lebih besar dari satu langkah desimal (0,01) = ada persentase
            // yang tidak punya status sama sekali.
            if (($batasBawah - $batasAtas) > 0.011) {
                $errors[] = 'Ada celah antara "' . esc((string) $sebelum['name']) . '" (maks '
                    . $batasAtas . ') dan "' . esc((string) $kini['name']) . '" (min ' . $batasBawah . ').';
            }
        }

        return $errors;
    }

    /**
     * Simpan seluruh konfigurasi (sudah tervalidasi) dalam satu transaksi.
     *
     * @param array<int, array<string, mixed>> $rows
     */
    public function simpanSemua(array $rows, ?int $userId = null): void
    {
        $this->db->transStart();

        foreach ($rows as $r) {
            $data = [
                'name'       => (string) $r['name'],
                'min_value'  => $r['min_value'],
                'max_value'  => $r['max_value'],
                'color'      => (string) $r['color'],
                'icon'       => ($r['icon'] ?? '') !== '' ? (string) $r['icon'] : null,
                'sort_order' => (int) ($r['sort_order'] ?? 0),
                'is_active'  => (int) ($r['is_active'] ?? 0),
                'updated_by' => $userId,
                'updated_at' => date('Y-m-d H:i:s'),
            ];

            $ada = $this->where('code', (string) $r['code'])->first();
            if ($ada) {
                $this->db->table($this->table)->where('id', (int) $ada['id'])->update($data);
                continue;
            }

            $this->db->table($this->table)->insert($data + [
                'code'       => (string) $r['code'],
                'created_by' => $userId,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }

        $this->db->transComplete();
    }

    /** Kembalikan seluruh ambang ke konfigurasi bawaan. */
    public function resetToDefault(?int $userId = null): void
    {
        $rows = [];
        foreach (self::DEFAULTS as $d) {
            $rows[] = $d + ['is_active' => 1];
        }
        $this->simpanSemua($rows, $userId);
    }
}
