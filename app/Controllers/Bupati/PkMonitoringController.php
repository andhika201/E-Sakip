<?php

namespace App\Controllers\Bupati;

use App\Controllers\BaseController;
use App\Models\OpdModel;
use App\Models\UserPublicModel;

/**
 * Monitoring PERJANJIAN KINERJA — read-only, khusus role Bupati.
 *
 * Halaman dokumen PK milik OPD (AdminOpd\PkController) menentukan OPD dari
 * SESI, sedangkan Bupati tidak terikat satu OPD. Karena itu halaman ini memakai
 * query pembacaan lintas OPD yang sudah ada (App\Models\UserPublicModel) —
 * bukan menduplikasi query baru:
 *
 *   - PK Bupati                  : UserPublicModel::getPkBupatiData()
 *   - PK JPT / Administrator /
 *     Pengawas / Camat (per OPD) : UserPublicModel::getPkDataByJenis()
 *
 * Tidak ada satu pun aksi tulis di controller ini.
 */
class PkMonitoringController extends BaseController
{
    /** Segmen URL yang dikenali => [jenis data pk.jenis, judul halaman]. */
    private const SEGMEN = [
        'bupati'        => ['bupati',        'Perjanjian Kinerja Bupati'],
        'es3'           => ['jpt',           'Perjanjian Kinerja Pimpinan Perangkat Daerah (JPT)'],
        'jpt'           => ['jpt',           'Perjanjian Kinerja Pimpinan Perangkat Daerah (JPT)'],
        'kecamatan'     => ['camat',         'Perjanjian Kinerja Camat'],
        'administrator' => ['administrator', 'Perjanjian Kinerja Administrator (Eselon III)'],
        'pengawas'      => ['pengawas',      'Perjanjian Kinerja Pengawas (Eselon IV)'],
    ];

    protected $helpers = ['format'];

    /** GET bupati/pk/(:segment) */
    public function index($segmen = 'bupati')
    {
        $segmen = strtolower(trim((string) $segmen));
        if (!isset(self::SEGMEN[$segmen])) {
            return redirect()->to(base_url('bupati/pk/bupati'))
                ->with('error', 'Jenis Perjanjian Kinerja tidak dikenal.');
        }
        [$jenis, $judul] = self::SEGMEN[$segmen];

        $db    = \Config\Database::connect();
        $model = new UserPublicModel();

        // Tahun yang benar-benar punya dokumen PK jenis ini.
        $tahunList = array_map('intval', array_column(
            $db->table('pk')->select('tahun')->where('jenis', $jenis)
                ->groupBy('tahun')->orderBy('tahun', 'DESC')->get()->getResultArray(),
            'tahun'
        ));

        $tahunReq = (int) ($this->request->getGet('tahun') ?? 0);
        $tahun    = in_array($tahunReq, $tahunList, true)
            ? $tahunReq
            : ($tahunList[0] ?? null);

        // Daftar OPD sah -> sekaligus penjaga IDOR: opd_id di luar daftar diabaikan.
        $opdList = $db->table('opd')->select('id, nama_opd')
            ->whereNotIn('id', OpdModel::EXCLUDED_OPD_IDS)
            ->orderBy('nama_opd', 'ASC')->get()->getResultArray();
        $opdSah  = array_map('intval', array_column($opdList, 'id'));

        $opdRaw    = $this->request->getGet('opd_id');
        $opdFilter = 'all';
        if ($opdRaw !== null && $opdRaw !== '' && $opdRaw !== 'all') {
            $opdFilter = in_array((int) $opdRaw, $opdSah, true) ? (int) $opdRaw : 'all';
        }

        $sasaranBupati = [];
        $rows          = [];
        if ($tahun !== null) {
            if ($jenis === 'bupati') {
                $sasaranBupati = $model->getPkBupatiData((string) $tahun);
            } else {
                $rows = $model->getPkDataByJenis($jenis, (string) $tahun, $opdFilter);
            }
        }

        return view('bupati/pk_monitoring', [
            'title'       => $judul,
            'segmen'      => $segmen,
            'jenis'       => $jenis,
            'judul'       => $judul,
            'tahun'       => $tahun,
            'tahunList'   => $tahunList,
            'opdList'     => $opdList,
            'opdFilter'   => $opdFilter,
            'rows'        => $rows,
            'sasaranBupati' => $sasaranBupati,
        ]);
    }
}
