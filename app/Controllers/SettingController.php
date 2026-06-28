<?php

namespace App\Controllers;

use App\Controllers\BaseController;

class SettingController extends BaseController
{
    protected $db;

    /** Field teks yang dikelola */
    private array $textKeys = [
        'app_name', 'app_long_name', 'instansi', 'instansi_address',
        'instansi_phone', 'instansi_email', 'dev_name', 'serial_number',
        'seo_description', 'seo_keywords', 'seo_author',
    ];

    /** Field file (upload) yang dikelola */
    private array $fileKeys = ['app_logo', 'favicon', 'dev_logo'];

    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }

    public function index()
    {
        $settings = [];
        foreach ($this->db->table('app_settings')->get()->getResultArray() as $row) {
            $settings[$row['skey']] = $row['svalue'];
        }

        return view('adminKabupaten/setting/index', ['settings' => $settings]);
    }

    public function save()
    {
        // --- Teks ---
        foreach ($this->textKeys as $key) {
            $val = $this->request->getPost($key);
            if ($val !== null) {
                $this->set($key, trim($val));
            }
        }

        // --- File (logo, favicon, logo pengembang) ---
        $allowed = ['png', 'jpg', 'jpeg', 'webp', 'svg', 'ico', 'gif'];
        $uploadDir = FCPATH . 'uploads';
        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0775, true);
        }

        foreach ($this->fileKeys as $key) {
            $file = $this->request->getFile($key);
            if ($file && $file->isValid() && !$file->hasMoved()) {
                $ext = strtolower($file->getExtension() ?: $file->getClientExtension());
                if (!in_array($ext, $allowed, true)) {
                    return redirect()->back()->withInput()
                        ->with('error', 'Format file ' . esc($key) . ' tidak didukung (gunakan PNG/JPG/WEBP/SVG/ICO).');
                }
                if ($file->getSizeByUnit('mb') > 3) {
                    return redirect()->back()->withInput()
                        ->with('error', 'Ukuran file ' . esc($key) . ' maksimal 3 MB.');
                }
                $newName = $key . '_' . bin2hex(random_bytes(5)) . '.' . $ext;
                $file->move($uploadDir, $newName);
                $this->set($key, 'uploads/' . $newName);
            }
        }

        return redirect()->to(base_url('adminkab/pengaturan'))
            ->with('success', 'Pengaturan aplikasi berhasil disimpan.');
    }

    private function set(string $key, ?string $value): void
    {
        $this->db->query(
            'INSERT INTO app_settings (skey, svalue) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE svalue = VALUES(svalue)',
            [$key, $value]
        );
    }
}
