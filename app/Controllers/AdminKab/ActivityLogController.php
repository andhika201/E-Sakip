<?php

namespace App\Controllers\AdminKab;

use App\Controllers\BaseController;
use App\Models\ActivityLogModel;

/**
 * Halaman Log Aktivitas Pengguna (khusus super admin).
 * Mencatat login/logout/login gagal + semua aksi pengubah data (via ActivityLogFilter).
 */
class ActivityLogController extends BaseController
{
    /** Ambil filter dari query string. */
    private function getFilters(): array
    {
        return [
            'q'      => trim((string) $this->request->getGet('q')),
            'action' => trim((string) $this->request->getGet('action')),
            'module' => trim((string) $this->request->getGet('module')),
            'user'   => trim((string) $this->request->getGet('user')),
            'from'   => trim((string) $this->request->getGet('from')),
            'to'     => trim((string) $this->request->getGet('to')),
        ];
    }

    /** Terapkan filter + urutan ke model. */
    private function applyFilters(ActivityLogModel $model, array $f): void
    {
        $model->orderBy('id', 'DESC');
        if ($f['action'] !== '') { $model->where('action', $f['action']); }
        if ($f['module'] !== '') { $model->where('module', $f['module']); }
        if ($f['user'] !== '')   { $model->where('username', $f['user']); }
        if ($f['from'] !== '')   { $model->where('created_at >=', $f['from'] . ' 00:00:00'); }
        if ($f['to'] !== '')     { $model->where('created_at <=', $f['to'] . ' 23:59:59'); }
        if ($f['q'] !== '') {
            $model->groupStart()
                ->like('username', $f['q'])
                ->orLike('description', $f['q'])
                ->orLike('url', $f['q'])
                ->orLike('ip_address', $f['q'])
                ->groupEnd();
        }
    }

    public function index()
    {
        $model = new ActivityLogModel();
        $f     = $this->getFilters();
        $this->applyFilters($model, $f);

        $logs  = $model->paginate(25);
        $pager = $model->pager;

        // opsi dropdown filter (model fresh agar tidak terpengaruh where di atas)
        $meta = new ActivityLogModel();

        return view('adminKabupaten/log/index', [
            'title'   => 'Log Aktivitas Pengguna',
            'logs'    => $logs,
            'pager'   => $pager,
            'filters' => $f,
            'total'   => $pager->getTotal(),
            'actions' => $meta->distinctColumn('action'),
            'modules' => $meta->distinctColumn('module'),
            'users'   => $meta->distinctColumn('username'),
        ]);
    }

    /** Cetak PDF log aktivitas sesuai filter aktif (maks. 5000 baris). */
    public function pdf()
    {
        @set_time_limit(0);

        $model = new ActivityLogModel();
        $f     = $this->getFilters();
        $this->applyFilters($model, $f);

        $maxRows = 5000;
        $logs    = $model->findAll($maxRows);

        $html = view('adminKabupaten/log/pdf', [
            'logs'      => $logs,
            'filters'   => $f,
            'dicetak'   => date('d-m-Y H:i:s'),
            'oleh'      => session()->get('username'),
            'truncated' => count($logs) >= $maxRows,
            'maxRows'   => $maxRows,
        ]);

        $mpdf = new \Mpdf\Mpdf([
            'mode'              => 'utf-8',
            'format'            => 'A4-L',
            'default_font_size' => 8,
            'tempDir'           => sys_get_temp_dir(),
        ]);
        helper('setting');
        $mpdf->SetHTMLFooter(pdf_footer_aksara());
        pdf_watermark_aksara($mpdf); // watermark AKSARA halus di latar
        $mpdf->WriteHTML($html);

        $this->response->setHeader('Content-Type', 'application/pdf');
        $mpdf->Output('log-aktivitas-' . date('Ymd-His') . '.pdf', 'I');
        exit;
    }

    /** Hapus log lebih lama dari N hari (default 90). */
    public function clearOld()
    {
        $days = (int) ($this->request->getPost('days') ?: 90);
        if ($days < 1) { $days = 90; }

        $batas = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        $model = new ActivityLogModel();
        $jumlah = $model->where('created_at <', $batas)->countAllResults(false);
        $model->where('created_at <', $batas)->delete();

        helper('activity');
        log_activity('hapus', 'log', "Bersihkan {$jumlah} log lebih lama dari {$days} hari");

        return redirect()->to('adminkab/log-aktivitas')
            ->with('success', "{$jumlah} log lama (> {$days} hari) dihapus.");
    }
}
