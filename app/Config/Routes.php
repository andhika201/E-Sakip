<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

$routes->get('/', 'Home::index');
$routes->get('/unauthorized', 'Home::unauthorized');

// Profil pengguna (semua role yang sudah login)
$routes->get('/profile', 'ProfileController::index', ['filter' => 'auth']);

// Change Password (semua role yang sudah login)
$routes->get('/change-password', 'ChangePasswordController::index', ['filter' => 'auth']);
$routes->post('/change-password/update', 'ChangePasswordController::update', ['filter' => 'auth']);

// 2FA (TOTP authenticator)
$routes->get('/2fa/setup', 'TwoFactorController::setup', ['filter' => 'auth']);
$routes->post('/2fa/enable', 'TwoFactorController::enable', ['filter' => 'auth']);
$routes->post('/2fa/disable', 'TwoFactorController::disable', ['filter' => 'auth']);

// Analisis AI (Gemini) — semua role admin yang login
$routes->get('/analisis-ai', 'AiAnalysisController::index', ['filter' => 'auth']);
$routes->post('/analisis-ai/run', 'AiAnalysisController::run', ['filter' => 'auth']);
$routes->get('/2fa/verify', 'TwoFactorController::verify');   // langkah login (belum sesi penuh)
$routes->post('/2fa/verify', 'TwoFactorController::verifyPost');

// User Routes
$routes->get('/dashboard', 'UserController::index');
$routes->get('/rkpd', 'UserController::rkpd');
$routes->get('/rpjmd', 'UserController::rpjmd');
$routes->get('/lakip_kabupaten', 'UserController::lakip_kabupaten');
$routes->get('/pk_bupati', 'UserController::pk_bupati');
// RENJA publik dinonaktifkan: UserController::renja belum ada & belum ada sumber data (tabel renja).
// Menu RENJA di header juga disembunyikan. Aktifkan kembali setelah fitur diimplementasi.
// $routes->get('/renja', 'UserController::renja');
$routes->get('/renstra', 'UserController::renstra');
$routes->get('/rkt', 'UserController::rkt');
$routes->get('/lakip_opd', 'UserController::lakip_opd');
$routes->get('/iku_opd', 'UserController::iku_opd');
$routes->get('/pk_pimpinan', 'UserController::pk_pimpinan');
$routes->get('/pk_administrator', 'UserController::pk_administrator');
$routes->get('/pk_pengawas', 'UserController::pk_pengawas');
$routes->get('/tentang_kami', 'UserController::tentang_kami');

// Public Cascading Routes
$routes->get('/cascading_kabupaten', 'UserController::cascading_kabupaten');
$routes->get('/cascading_kabupaten/cetak', 'UserController::cascading_kabupaten_cetak');
$routes->get('/cascading_kabupaten/excel', 'UserController::cascading_kabupaten_excel');
$routes->get('/cascading_kabupaten/cetak-pohon', 'UserController::cascading_kabupaten_pohon');
$routes->get('/pohon_kinerja_kabupaten', 'UserController::pohon_kinerja_kabupaten');
$routes->get('/pohon_kinerja_kabupaten/cetak', 'UserController::cascading_kabupaten_pohon');
$routes->get('/cascading_opd', 'UserController::cascading_opd');
$routes->get('/cascading_opd/cetak', 'UserController::cascading_opd_cetak');
$routes->get('/cascading_opd/excel', 'UserController::cascading_opd_excel');
$routes->get('/cascading_opd/cetak-pohon', 'UserController::cascading_opd_pohon');
$routes->get('/pohon_kinerja_opd', 'UserController::pohon_kinerja_opd');
$routes->get('/pohon_kinerja_opd/cetak', 'UserController::cascading_opd_pohon');

$routes->get('/api-docs', 'ApiDocsController::index');

$routes->group('api', ['filter' => 'api-token'], static function ($routes) {
    $routes->get('perangkat-daerah', 'Api\PerangkatDaerahController::index');
    $routes->get('perangkat-daerah/(:num)', 'Api\PerangkatDaerahController::show/$1');
    $routes->get('perangkat-daerah/(:num)/iku', 'Api\PerangkatDaerahController::iku/$1');
    $routes->get('perangkat-daerah/(:num)/cascading', 'Api\PerangkatDaerahController::cascading/$1');
    $routes->get('perangkat-daerah/(:num)/pohon-kinerja', 'Api\PerangkatDaerahController::pohonKinerja/$1');
    $routes->get('iku', 'Api\PerangkatDaerahController::iku');
    $routes->get('cascading', 'Api\PerangkatDaerahController::cascading');
    $routes->get('pohon-kinerja', 'Api\PerangkatDaerahController::pohonKinerja');
});

$routes->group(
    'adminkab',
    ['filter' => 'auth:admin_kab,admin,admin_inspektorat'],
    function ($routes) {
        $routes->get('pk/(:any)/edit/(:num)', 'AdminOpd\PkController::edit/$1/$2');
        $routes->get('pk/(:any)/tambah', 'AdminOpd\PkController::tambah/$1');
        // capaian_pk = fitur lama yang sudah tidak ada method-nya (digantikan PkRenaksiController/monev).
        // Rute dinonaktifkan agar tidak memicu error "method not found" bila di-hit langsung.
        // $routes->get('capaian_pk/(:any)/(:num)', 'AdminOpd\PkController::edit_capaian/$1/$2');
        // $routes->post('capaian_pk/(:any)/setcapaian/(:num)', 'AdminOpd\PkController::update_capaian/$1/$2');
        $routes->post('pk/(:any)/update/(:num)', 'AdminOpd\PkController::update/$1/$2');
        $routes->get('pk/(:any)/cetak/(:num)', 'AdminOpd\PkController::cetak/$1/$2');
        $routes->post('pk/(:any)/save', 'AdminOpd\PkController::save/$1');
        $routes->get('pk/(:any)', 'AdminOpd\PkController::index/$1');
        // $routes->get('capaian_pk/(:any)', 'AdminOpd\PkController::capaian_pk/$1');
        $routes->match(['get', 'post', 'delete'], 'pk/(:any)/delete/(:num)', 'AdminOpd\PkController::delete/$1/$2');
        // (pk_bupati/cetak dihapus: controller AdminKab\PkBupatiController tidak ada & tidak ada link.
        //  Cetak PK Bupati dilayani via pk/(:any)/cetak/(:num) dan AdminOpd\PkRenaksiController.)

        $routes->get('dashboard', 'AdminKabupatenController::dashboard');
        $routes->post('dashboard/data', 'AdminKabupatenController::getDashboardData');

        // Evaluasi Kinerja (Inspektorat) — placeholder
        $routes->get('evaluasi_inspektorat', 'AdminKabupatenController::evaluasi_inspektorat');

        // Lakip
        $routes->get('lakip', 'AdminKab\LakipController::index');
        $routes->get('lakip/cetak', 'AdminKab\LakipController::cetak');
        $routes->get('lakip/tambah/(:num)', 'AdminKab\LakipController::tambah/$1');
        $routes->post('lakip/save', 'AdminKab\LakipController::save');
        $routes->get('lakip/edit/(:num)', 'AdminKab\LakipController::edit/$1');
        $routes->post('lakip/update/', 'AdminKab\LakipController::update');
        // download & update-status: method tidak ada; ubah status LAKIP dilayani lakip/status/(:num)/(:segment)
        // $routes->get('lakip/download/(:num)', 'AdminKab\LakipController::download/$1');
        // $routes->post('lakip/update-status', 'AdminKab\LakipController::updateStatus');
        $routes->match(['get', 'post', 'delete'], 'lakip/delete/(:num)', 'AdminKab\LakipController::delete/$1');
        // ubah status lakip
        $routes->get('lakip/status/(:num)/(:segment)', 'AdminKab\LakipController::status/$1/$2');

        // iku
        $routes->get('iku/edit/(:num)', 'AdminKab\IkuController::edit/$1');
        $routes->get('iku', 'AdminKab\IkuController::index');
        $routes->get('iku/tambah/(:num)', 'AdminKab\IkuController::tambah/$1');
        $routes->post('iku/save', 'AdminKab\IkuController::save');
        $routes->post('iku/update', 'AdminKab\IkuController::update');
        // route untuk ubah status IKU
        $routes->post('iku/change_status/(:num)', 'AdminKab\IkuController::change_status/$1');

        // Catatan: seluruh rute Pegawai (kelola + sinkron SIMPEG/SIKASN) dipindah
        // ke grup khusus super admin (auth:admin) di bawah.

        // RPJMD
        $routes->get('rpjmd', 'RpjmdController::index');
        $routes->get('rpjmd/tambah', 'RpjmdController::tambah');
        $routes->get('rpjmd/edit/(:num)', 'RpjmdController::edit/$1');
        $routes->post('rpjmd/save', 'RpjmdController::save');
        $routes->post('rpjmd/update', 'RpjmdController::update');
        $routes->match(['get', 'post', 'delete'], 'rpjmd/delete/(:num)', 'RpjmdController::delete/$1');
        $routes->post('rpjmd/update-status', 'RpjmdController::updateStatus');

        // Cascading
        $routes->get('cascading', 'AdminKab\CascadingController::index');
        $routes->get('cascading/tambah/(:num)', 'AdminKab\CascadingController::tambah/$1');
        $routes->get('cascading/get-pk-program-by-opd', 'AdminKab\CascadingController::getPkProgramByOpd');
        $routes->post('cascading/save', 'AdminKab\CascadingController::save');
        $routes->get('cascading/cetak', 'AdminKab\CascadingController::cetak');
        $routes->get('cascading/excel', 'AdminKab\CascadingController::excel');
        $routes->post('cascading/save-csf', 'AdminKab\CascadingController::saveCsf');
        $routes->get('cascading/cetak-pohon', 'AdminKab\CascadingController::cetakPohon');

        // RKPD (read-only: turunan RKT). Hanya index yang aktif.
        // Rute tulis di bawah dinonaktifkan karena method controllernya tidak ada
        // (RkpdController hanya punya index()); view tambah/edit RKPD adalah orphan.
        $routes->get('rkpd', 'RkpdController::index');
        // $routes->get('rkpd/tambah', 'RkpdController::tambah');
        // $routes->get('rkpd/edit/(:num)', 'RkpdController::edit/$1');
        // $routes->post('rkpd/save', 'RkpdController::save');
        // $routes->post('rkpd/update', 'RkpdController::update');
        // $routes->match(['get', 'post', 'delete'], 'rkpd/delete/(:num)', 'RkpdController::delete/$1');
        // $routes->post('rkpd/update-status', 'RkpdController::updateStatus');

        // (rkt kabupaten dihapus: controller AdminKab\RktController tidak ada & tidak ada menu.
        //  RKT hanya pada tingkat OPD -> lihat grup adminopd (adminopd/rkt).)

        // MONEV (PK Bupati / renaksi). URL bersih: adminkab/monev.
        // Modul monev lama (AdminKab\MonevController) sudah dihapus; kini dilayani PkRenaksiController.
        $routes->get('monev/input/(:num)', 'AdminOpd\PkRenaksiController::monevForm/bupati/$1');
        $routes->post('monev/save', 'AdminOpd\PkRenaksiController::monevSave/bupati');
        $routes->get('monev/cetak', 'AdminOpd\PkRenaksiController::cetak/bupati');
        $routes->get('monev', 'AdminOpd\PkRenaksiController::monev/bupati');

        // Target & Rencana Aksi (PK Bupati) - URL bersih: adminkab/target_renaksi
        $routes->get('target_renaksi/cetak', 'AdminOpd\PkRenaksiController::cetakRenaksi/bupati');
        $routes->get('target_renaksi/tambah', 'AdminOpd\PkRenaksiController::tambah/bupati');
        $routes->post('target_renaksi/save', 'AdminOpd\PkRenaksiController::save/bupati');
        // Kelola Perangkat Daerah pendukung PK Bupati (override manual) - spesifik sebelum edit/(:num)
        $routes->post('target_renaksi/pd/save', 'AdminOpd\PkRenaksiController::savePd/bupati');
        $routes->get('target_renaksi/pd/(:num)', 'AdminOpd\PkRenaksiController::kelolaPd/bupati/$1');
        $routes->get('target_renaksi/edit/(:num)', 'AdminOpd\PkRenaksiController::edit/bupati/$1');
        $routes->post('target_renaksi/update/(:num)', 'AdminOpd\PkRenaksiController::update/bupati/$1');
        $routes->get('target_renaksi', 'AdminOpd\PkRenaksiController::index/bupati');

        // Rencana Aksi & MONEV PK Bupati (jenis = bupati). Route spesifik sebelum (:any).
        $routes->get('renaksi_pk/(:any)/tambah', 'AdminOpd\PkRenaksiController::tambah/$1');
        $routes->get('renaksi_pk/(:any)/cetak', 'AdminOpd\PkRenaksiController::cetakRenaksi/$1');
        $routes->post('renaksi_pk/(:any)/save', 'AdminOpd\PkRenaksiController::save/$1');
        $routes->get('renaksi_pk/(:any)/edit/(:num)', 'AdminOpd\PkRenaksiController::edit/$1/$2');
        $routes->post('renaksi_pk/(:any)/update/(:num)', 'AdminOpd\PkRenaksiController::update/$1/$2');
        $routes->get('renaksi_pk/(:any)', 'AdminOpd\PkRenaksiController::index/$1');
        $routes->get('monev_pk/(:any)/input/(:num)', 'AdminOpd\PkRenaksiController::monevForm/$1/$2');
        $routes->post('monev_pk/(:any)/save', 'AdminOpd\PkRenaksiController::monevSave/$1');
        $routes->get('monev_pk/(:any)/cetak', 'AdminOpd\PkRenaksiController::cetak/$1');
        $routes->get('monev_pk/(:any)', 'AdminOpd\PkRenaksiController::monev/$1');


        // target
        $routes->get('target/cetak', 'AdminKab\TargetController::cetak');
        $routes->get('target', 'AdminKab\TargetController::index');
        $routes->get('target/tambah', 'AdminKab\TargetController::tambah');
        $routes->post('target/save', 'AdminKab\TargetController::save');
        $routes->get('target/edit/(:num)', 'AdminKab\TargetController::edit/$1');
        $routes->post('target/update/(:num)', 'AdminKab\TargetController::update/$1');

        // (Lakip Kabupaten dihapus: controller LakipKabupatenController tidak ada & tidak ada menu.
        //  LAKIP tingkat kabupaten dilayani AdminKab\LakipController -> adminkab/lakip.)

        // Program PK (master) dipindah ke grup super admin (auth:admin) di bawah.

        // Tentang Kami
        $routes->get('tentang_kami', 'AdminKabupatenController::tentang_kami');
        $routes->get('tentang_kami/edit', 'AdminKabupatenController::edit_tentang_kami');
        $routes->post('tentang_kami/save', 'AdminKabupatenController::save_tentang_kami');
    }
);


// ===== SUPER ADMIN: Master Data (satu tampilan tabbed) — khusus role 'admin' =====
$routes->group('adminkab', ['filter' => 'auth:admin'], function ($routes) {
    // Pengaturan Aplikasi — KHUSUS super admin
    $routes->get('pengaturan', 'SettingController::index');
    $routes->post('pengaturan/save', 'SettingController::save');

    // Master Program / Kegiatan / Sub Kegiatan PK (per tahun) — KHUSUS super admin
    $routes->get('program_pk', 'ProgramPkController::index');
    $routes->get('program_pk/tambah', 'ProgramPkController::tambah');
    $routes->get('program_pk/import', 'ProgramPkController::import');
    $routes->get('program_pk/template', 'ProgramPkController::template');
    $routes->post('program_pk/import/process', 'ProgramPkController::processImport');
    $routes->get('program_pk/edit/(:num)', 'ProgramPkController::edit/$1');
    $routes->post('program_pk/save', 'ProgramPkController::save');
    $routes->post('program_pk/update/(:num)', 'ProgramPkController::update/$1');
    $routes->post('program_pk/delete/(:num)', 'ProgramPkController::delete/$1');

    $routes->get('master', 'SuperAdmin\MasterController::index');
    $routes->get('master/pegawai-data', 'SuperAdmin\MasterController::pegawaiData'); // DataTables server-side

    // Kelola Pegawai (jabatan & OPD) — KHUSUS super admin
    $routes->get('pegawai', 'AdminKab\PegawaiController::index');
    $routes->get('pegawai/edit/(:num)', 'AdminKab\PegawaiController::edit/$1');
    $routes->post('pegawai/update/(:num)', 'AdminKab\PegawaiController::update/$1');
    $routes->get('pegawai/jabatan', 'AdminKab\PegawaiController::jabatan');
    $routes->post('pegawai/jabatan/update/(:num)', 'AdminKab\PegawaiController::updateJabatan/$1');

    // Sinkron SIMPEG/SIKASN — KHUSUS super admin (OPD, Pangkat, Jabatan, Pegawai)
    $routes->get('pegawai/sync', 'AdminKab\PegawaiController::sync');
    $routes->post('pegawai/sync/run', 'AdminKab\PegawaiController::runSync');

    // Log Aktivitas Pengguna — KHUSUS super admin
    $routes->get('log-aktivitas', 'AdminKab\ActivityLogController::index');
    $routes->get('log-aktivitas/pdf', 'AdminKab\ActivityLogController::pdf');
    $routes->post('log-aktivitas/clear', 'AdminKab\ActivityLogController::clearOld');

    $routes->post('master/pegawai/save', 'SuperAdmin\MasterController::pegawaiSave');
    $routes->match(['get', 'post', 'delete'], 'master/pegawai/delete/(:num)', 'SuperAdmin\MasterController::pegawaiDelete/$1');

    $routes->post('master/pangkat/save', 'SuperAdmin\MasterController::pangkatSave');
    $routes->match(['get', 'post', 'delete'], 'master/pangkat/delete/(:num)', 'SuperAdmin\MasterController::pangkatDelete/$1');

    $routes->post('master/jabatan/save', 'SuperAdmin\MasterController::jabatanSave');
    $routes->match(['get', 'post', 'delete'], 'master/jabatan/delete/(:num)', 'SuperAdmin\MasterController::jabatanDelete/$1');

    $routes->post('master/opd/save', 'SuperAdmin\MasterController::opdSave');
    $routes->match(['get', 'post', 'delete'], 'master/opd/delete/(:num)', 'SuperAdmin\MasterController::opdDelete/$1');

    $routes->post('master/user/save', 'SuperAdmin\MasterController::userSave');
    $routes->match(['get', 'post', 'delete'], 'master/user/delete/(:num)', 'SuperAdmin\MasterController::userDelete/$1');

    $routes->post('master/role/save', 'SuperAdmin\MasterController::roleSave');
    $routes->match(['get', 'post', 'delete'], 'master/role/delete/(:num)', 'SuperAdmin\MasterController::roleDelete/$1');
    $routes->post('master/role/permissions', 'SuperAdmin\MasterController::rolePermSave');

    $routes->post('master/satuan/save', 'SuperAdmin\MasterController::satuanSave');
    $routes->match(['get', 'post', 'delete'], 'master/satuan/delete/(:num)', 'SuperAdmin\MasterController::satuanDelete/$1');
});

$routes->group('adminopd', ['filter' => 'auth:admin_opd,admin,admin_kecamatan'], function ($routes) {
    // PK Generic Controller (slash-based, for compatibility with button href)
    $routes->get('pk/(:any)/edit/(:num)', 'AdminOpd\PkController::edit/$1/$2');
    $routes->post('pk/(:any)/update/(:num)', 'AdminOpd\PkController::update/$1/$2');
    $routes->get('pk/(:any)/cetak/(:num)', 'AdminOpd\PkController::cetak/$1/$2');
    $routes->get('pk/(:any)/tambah', 'AdminOpd\PkController::tambah/$1');
    $routes->post('pk/(:any)/save', 'AdminOpd\PkController::save/$1');
    $routes->get('pk/(:any)', 'AdminOpd\PkController::index/$1');
    $routes->match(['get', 'post', 'delete'], 'pk/(:any)/delete/(:num)', 'AdminOpd\PkController::delete/$1/$2');
    // capaian_pk = fitur lama tanpa method (digantikan monev/PkRenaksiController)
    // $routes->get('capaian_pk/(:any)', 'AdminOpd\PkController::capaian_pk/$1');

    $routes->get('dashboard', 'AdminOpdController::index');

    // Renstra
    $routes->get('renstra', 'AdminOpd\RenstraController::index');
    $routes->get('renstra/cetak', 'AdminOpd\RenstraController::cetak');
    $routes->get('renstra/tambah', 'AdminOpd\RenstraController::tambah_renstra');
    $routes->get('renstra/edit/(:num)', 'AdminOpd\RenstraController::edit/$1');
    $routes->post('renstra/save', 'AdminOpd\RenstraController::save');
    $routes->post('renstra/update/(:num)', 'AdminOpd\RenstraController::update/$1');
    $routes->match(['get', 'post', 'delete'], 'renstra/delete/(:num)', 'AdminOpd\RenstraController::delete/$1');
    $routes->post('renstra/update-status', 'AdminOpd\RenstraController::updateStatus');
    $routes->get('renstra/edit-tujuan/(:num)', 'AdminOpd\RenstraController::editTujuan/$1');
    $routes->post('renstra/update-tujuan/(:num)', 'AdminOpd\RenstraController::updateTujuan/$1');

    // RKT
    $routes->get('rkt', 'AdminOpd\RktController::index');
    $routes->get('rkt/cetak', 'AdminOpd\RktController::cetak');
    $routes->get('rkt/tambah/(:num)', 'AdminOpd\RktController::tambah/$1');
    $routes->get('rkt/edit/(:num)', 'AdminOpd\RktController::edit/$1');
    $routes->post('rkt/save', 'AdminOpd\RktController::save');
    $routes->post('rkt/update', 'AdminOpd\RktController::update');
    $routes->post('rkt/delete-indikator', 'AdminOpd\RktController::deleteByIndicator');
    // rkt/delete/(:num): method delete() tidak ada; hapus RKT dilakukan via rkt/delete-indikator (deleteByIndicator)
    // $routes->match(['get', 'post', 'delete'], 'rkt/delete/(:num)', 'AdminOpd\RktController::delete/$1');
    $routes->post('rkt/update-status', 'AdminOpd\RktController::updateStatus');

    // IKU
    $routes->get('iku/edit/(:num)', 'AdminOpd\IkuController::edit/$1');
    $routes->get('iku/cetak', 'AdminOpd\IkuController::cetak');
    $routes->get('iku', 'AdminOpd\IkuController::index');
    $routes->get('iku/tambah/(:num)', 'AdminOpd\IkuController::tambah/$1');
    $routes->post('iku/save', 'AdminOpd\IkuController::save');
    $routes->post('iku/update', 'AdminOpd\IkuController::update');
    $routes->match(['get', 'post', 'delete'], 'iku/delete/(:num)', 'AdminOpd\IkuController::delete/$1');
    $routes->post('iku/change_status/(:num)', 'AdminOpd\IkuController::change_status/$1');


    // target
    $routes->get('target', 'AdminOpd\TargetController::index');
    $routes->get('target/tambah', 'AdminOpd\TargetController::tambah');
    $routes->post('target/save', 'AdminOpd\TargetController::save');
    $routes->get('target/edit/(:num)', 'AdminOpd\TargetController::edit/$1');
    $routes->post('target/update/(:num)', 'AdminOpd\TargetController::update/$1');

    // MONEV (PK Eselon II/III/IV / renaksi). URL bersih: adminopd/monev.
    // Modul monev lama (AdminOpd\MonevController) sudah dihapus; kini dilayani PkRenaksiController.
    $routes->get('monev/input/(:num)', 'AdminOpd\PkRenaksiController::monevForm/es3/$1');
    $routes->post('monev/save', 'AdminOpd\PkRenaksiController::monevSave/es3');
    $routes->get('monev/cetak', 'AdminOpd\PkRenaksiController::cetak/es3');
    $routes->get('monev', 'AdminOpd\PkRenaksiController::monev/es3');

    // Target & Rencana Aksi (PK Eselon II/III/IV) - URL bersih: adminopd/target_renaksi
    $routes->get('target_renaksi/cetak', 'AdminOpd\PkRenaksiController::cetakRenaksi/es3');
    $routes->get('target_renaksi/tambah', 'AdminOpd\PkRenaksiController::tambah/es3');
    $routes->post('target_renaksi/save', 'AdminOpd\PkRenaksiController::save/es3');
    $routes->get('target_renaksi/edit/(:num)', 'AdminOpd\PkRenaksiController::edit/es3/$1');
    $routes->post('target_renaksi/update/(:num)', 'AdminOpd\PkRenaksiController::update/es3/$1');
    $routes->get('target_renaksi', 'AdminOpd\PkRenaksiController::index/es3');

    // Rencana Aksi & MONEV PK Eselon III (jenis = es3). Route spesifik sebelum (:any).
    $routes->get('renaksi_pk/(:any)/tambah', 'AdminOpd\PkRenaksiController::tambah/$1');
    $routes->get('renaksi_pk/(:any)/cetak', 'AdminOpd\PkRenaksiController::cetakRenaksi/$1');
    $routes->post('renaksi_pk/(:any)/save', 'AdminOpd\PkRenaksiController::save/$1');
    $routes->get('renaksi_pk/(:any)/edit/(:num)', 'AdminOpd\PkRenaksiController::edit/$1/$2');
    $routes->post('renaksi_pk/(:any)/update/(:num)', 'AdminOpd\PkRenaksiController::update/$1/$2');
    $routes->get('renaksi_pk/(:any)', 'AdminOpd\PkRenaksiController::index/$1');
    $routes->get('monev_pk/(:any)/input/(:num)', 'AdminOpd\PkRenaksiController::monevForm/$1/$2');
    $routes->post('monev_pk/(:any)/save', 'AdminOpd\PkRenaksiController::monevSave/$1');
    $routes->get('monev_pk/(:any)/cetak', 'AdminOpd\PkRenaksiController::cetak/$1');
    $routes->get('monev_pk/(:any)', 'AdminOpd\PkRenaksiController::monev/$1');


    // Lakip OPD
    $routes->get('lakip', 'AdminOpd\LakipOpdController::index');
    $routes->get('lakip/cetak', 'AdminOpd\LakipOpdController::cetak');
    $routes->get('lakip/tambah/(:num)', 'AdminOpd\LakipOpdController::tambah/$1');
    $routes->post('lakip/save', 'AdminOpd\LakipOpdController::save');
    $routes->get('lakip/edit/(:num)', 'AdminOpd\LakipOpdController::edit/$1');
    $routes->post('lakip/update/', 'AdminOpd\LakipOpdController::update');
    // download & update-status: method tidak ada; ubah status LAKIP dilayani lakip/status/(:num)/(:segment)
    // $routes->get('lakip/download/(:num)', 'AdminOpd\LakipOpdController::download/$1');
    // $routes->post('lakip/update-status', 'AdminOpd\LakipOpdController::updateStatus');
    $routes->match(['get', 'post', 'delete'], 'lakip/delete/(:num)', 'AdminOpd\LakipOpdController::delete/$1');
    // ubah status lakip
    $routes->get('lakip/status/(:num)/(:segment)', 'AdminOpd\LakipOpdController::status/$1/$2');

    // Tentang Kami
    $routes->get('tentang_kami', 'AdminOpdController::tentang_kami');

    // Evaluasi Kinerja: Evaluasi Inspektorat (stub)
    $routes->get('evaluasi_inspektorat', 'AdminOpdController::evaluasi_inspektorat');

    // Cascading
    // $routes->get('cascading', 'AdminOpd\CascadingController::index');
    // $routes->get('cascading/tambah/(:num)', 'AdminOpd\CascadingController::tambah/$1');
    // $routes->get('cascading/get-pk-program-by-opd', 'AdminOpd\CascadingController::getPkProgramByOpd');
    $routes->post('cascading/save', 'AdminOpd\CascadingController::save');
    $routes->post('cascading/savecsf', 'AdminOpd\CascadingController::saveCsf');


    // Cascading
    $routes->get('cascading', 'AdminOpd\CascadingController::index');
    $routes->get('cascading/table', 'AdminOpd\CascadingController::partialTable'); // partial tabel utk refresh AJAX
    // ESS III
    $routes->get('cascading/tambah-es3/(:num)', 'AdminOpd\CascadingController::tambahEs3/$1');
    $routes->post('cascading/save-es3', 'AdminOpd\CascadingController::saveEs3');
    $routes->get('cascading/edit-es3/(:num)', 'AdminOpd\CascadingController::editEs3/$1');
    $routes->post('cascading/update-es3/(:num)', 'AdminOpd\CascadingController::updateEs3/$1');
    $routes->post('cascading/delete-es3/(:num)', 'AdminOpd\CascadingController::deleteEs3/$1');
    // ESS IV
    $routes->get('cascading/tambah-es4/(:num)', 'AdminOpd\CascadingController::tambahEs4/$1');
    $routes->post('cascading/save-es4', 'AdminOpd\CascadingController::saveEs4');
    $routes->get('cascading/edit-es4/(:num)', 'AdminOpd\CascadingController::editEs4/$1');
    $routes->post('cascading/update-es4/(:num)', 'AdminOpd\CascadingController::updateEs4/$1');
    $routes->post('cascading/delete-es4/(:num)', 'AdminOpd\CascadingController::deleteEs4/$1');

    $routes->get('cascading/cetak', 'AdminOpd\CascadingController::cetak');
    $routes->get('cascading/excel', 'AdminOpd\CascadingController::excel');
    $routes->get('cascading/cetakpohon', 'AdminOpd\CascadingController::cetakPohon');
});
$routes->get('/login', 'LoginController::index');
$routes->post('/login/authenticate', 'LoginController::authenticate');
$routes->get('/logout', 'LoginController::logout');
