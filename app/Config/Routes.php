<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

$routes->get('/', 'Home::index');

// User Routes
$routes->get('/dashboard', 'UserController::index');
$routes->get('/rkpd', 'UserController::rkpd');
$routes->get('/rpjmd', 'UserController::rpjmd');
$routes->get('/lakip_kabupaten', 'UserController::lakip_kabupaten');
$routes->get('/pk_bupati', 'UserController::pk_bupati');
$routes->get('/renja', 'UserController::renja');
$routes->get('/renstra', 'UserController::renstra');
$routes->get('/lakip_opd', 'UserController::lakip_opd');
$routes->get('/iku_opd', 'UserController::iku_opd');
$routes->get('/pk_pimpinan', 'UserController::pk_pimpinan');
$routes->get('/pk_administrator', 'UserController::pk_administrator');
$routes->get('/pk_pengawas', 'UserController::pk_pengawas');
$routes->get('/tentang_kami', 'UserController::tentang_kami');

$routes->group(
    'adminkab',
    ['filter' => 'auth:admin_kab,admin'],
    function ($routes) {
        $routes->get('pk/(:any)/edit/(:num)', 'AdminOpd\PkController::edit/$1/$2');
        $routes->get('pk/(:any)/tambah', 'AdminOpd\PkController::tambah/$1');
        $routes->get('capaian_pk/(:any)/(:num)', 'AdminOpd\PkController::edit_capaian/$1/$2');
        $routes->post('capaian_pk/(:any)/setcapaian/(:num)', 'AdminOpd\PkController::update_capaian/$1/$2');
        $routes->post('pk/(:any)/update/(:num)', 'AdminOpd\PkController::update/$1/$2');
        $routes->get('pk/(:any)/cetak/(:num)', 'AdminOpd\PkController::cetak/$1/$2');
        $routes->post('pk/(:any)/save', 'AdminOpd\PkController::save/$1');
        $routes->get('pk/(:any)', 'AdminOpd\PkController::index/$1');
        $routes->get('capaian_pk/(:any)', 'AdminOpd\PkController::capaian_pk/$1');
        $routes->match(['get', 'post', 'delete'], 'pk/(:any)/delete/(:num)', 'AdminOpd\PkController::delete/$1/$2');
        $routes->get('pk_bupati/cetak', 'AdminKab\PkBupatiController::view_cetak');


        $routes->get('dashboard', 'AdminKabupatenController::index');
        $routes->post('getDashboardData', 'AdminKabupatenController::getDashboardData');
        $routes->get('getStats', 'AdminKabupatenController::getStats');

        // RPJMD
        $routes->get('rpjmd', 'RpjmdController::index');
        $routes->get('rpjmd/tambah', 'RpjmdController::tambah');
        $routes->get('rpjmd/edit/(:num)', 'RpjmdController::edit/$1');
        $routes->post('rpjmd/save', 'RpjmdController::save');
        $routes->post('rpjmd/update', 'RpjmdController::update');
        $routes->match(['get', 'post', 'delete'], 'rpjmd/delete/(:num)', 'RpjmdController::delete/$1');
        $routes->post('rpjmd/update-status', 'RpjmdController::updateStatus');

        // RKPD
        $routes->get('rkpd', 'RkpdController::index');
        $routes->get('rkpd/tambah', 'RkpdController::tambah');
        $routes->get('rkpd/edit/(:num)', 'RkpdController::edit/$1');
        $routes->post('rkpd/save', 'RkpdController::save');
        $routes->post('rkpd/update', 'RkpdController::update');
        $routes->match(['get', 'post', 'delete'], 'rkpd/delete/(:num)', 'RkpdController::delete/$1');
        $routes->post('rkpd/update-status', 'RkpdController::updateStatus');

        // Capaian PK
        $routes->get('capaian_pk/tambah', 'CapaianPkController::tambah');
        $routes->get('capaian_pk/edit/(:num)', 'CapaianPkController::edit/$1');
        $routes->post('capaian_pk/save', 'CapaianPkController::save');
        $routes->post('capaian_pk/update/(:num)', 'CapaianPkController::update/$1');
        $routes->get('program_pk/delete/(:num)', 'ProgramPkController::delete/$1');
        // target
        $routes->get('target', 'TargetController::index');
        $routes->get('target/tambah', 'TargetController::tambah');
        $routes->post('target/save', 'TargetController::save');
        $routes->get('target/edit/(:num)', 'TargetController::edit/$1');
        $routes->post('target/update/(:num)', 'TargetController::update/$1');

        //MONEV
        $routes->get('monev', 'MonevController::index');
        $routes->get('monev/tambah', 'MonevController::tambah');
        $routes->post('monev/save', 'MonevController::save');
        $routes->get('monev/edit/(:num)', 'MonevController::edit/$1');
        $routes->post('monev/update/(:num)', 'MonevController::update/$1');

        // Lakip Kabupaten
        $routes->get('lakip_kabupaten', 'LakipKabupatenController::index');
        $routes->get('lakip_kabupaten/tambah', 'LakipKabupatenController::tambah');
        $routes->post('lakip_kabupaten/save', 'LakipKabupatenController::save');
        $routes->get('lakip_kabupaten/edit/(:num)', 'LakipKabupatenController::edit/$1');
        $routes->post('lakip_kabupaten/update/(:num)', 'LakipKabupatenController::update/$1');
        $routes->get('lakip_kabupaten/download/(:num)', 'LakipKabupatenController::download/$1');
        $routes->post('lakip_kabupaten/update-status', 'LakipKabupatenController::updateStatus');
        $routes->match(['get', 'post', 'delete'], 'lakip_kabupaten/delete/(:num)', 'LakipKabupatenController::delete/$1');

        // Program PK
        $routes->get('program_pk', 'ProgramPkController::index');
        $routes->get('program_pk/tambah', 'ProgramPkController::tambah');
        $routes->get('program_pk/edit/(:num)', 'ProgramPkController::edit/$1');
        $routes->post('program_pk/save', 'ProgramPkController::save');
        $routes->post('program_pk/update/(:num)', 'ProgramPkController::update/$1');
        $routes->get('program_pk/delete/(:num)', 'ProgramPkController::delete/$1');

        // Tentang Kami
        $routes->get('tentang_kami', 'AdminKabupatenController::tentang_kami');
        $routes->get('tentang_kami/edit', 'AdminKabupatenController::edit_tentang_kami');
        $routes->post('tentang_kami/save', 'AdminKabupatenController::save_tentang_kami');
    }
);


$routes->group('adminopd', ['filter' => 'auth:admin_opd,admin_kab,admin'], function ($routes) {
    // PK Generic Controller (slash-based, for compatibility with button href)
    $routes->get('pk/(:any)/edit/(:num)', 'AdminOpd\PkController::edit/$1/$2');
    $routes->post('pk/(:any)/update/(:num)', 'AdminOpd\PkController::update/$1/$2');
    $routes->get('pk/(:any)/cetak/(:num)', 'AdminOpd\PkController::cetak/$1/$2');
    $routes->get('pk/(:any)/tambah', 'AdminOpd\PkController::tambah/$1');
    $routes->post('pk/(:any)/save', 'AdminOpd\PkController::save/$1');
    $routes->get('pk/(:any)', 'AdminOpd\PkController::index/$1');
    $routes->match(['get', 'post', 'delete'], 'pk/(:any)/delete/(:num)', 'AdminOpd\PkController::delete/$1/$2');
    $routes->get('capaian_pk/(:any)', 'AdminOpd\PkController::capaian_pk/$1');

    $routes->get('dashboard', 'AdminOpdController::index');

    // Renstra
    $routes->get('renstra', 'AdminOpd\RenstraController::index');
    $routes->get('renstra/tambah', 'AdminOpd\RenstraController::tambah_renstra');
    $routes->get('renstra/edit/(:num)', 'AdminOpd\RenstraController::edit/$1');
    $routes->post('renstra/save', 'AdminOpd\RenstraController::save');
    $routes->post('renstra/update/(:num)', 'AdminOpd\RenstraController::update/$1');
    $routes->match(['get', 'post', 'delete'], 'renstra/delete/(:num)', 'AdminOpd\RenstraController::delete/$1');
    $routes->post('renstra/update-status', 'AdminOpd\RenstraController::updateStatus');

    // RENJA
    $routes->get('renja', 'AdminOpd\RenjaController::index');
    $routes->get('renja/tambah', 'AdminOpd\RenjaController::tambah');
    $routes->get('renja/edit/(:num)', 'AdminOpd\RenjaController::edit/$1');
    $routes->post('renja/save', 'AdminOpd\RenjaController::save');
    $routes->post('renja/update', 'AdminOpd\RenjaController::update');
    $routes->match(['get', 'post', 'delete'], 'renja/delete/(:num)', 'AdminOpd\RenjaController::delete/$1');
    $routes->post('renja/update-status', 'AdminOpd\RenjaController::updateStatus');

    // IKU
    $routes->get('iku/edit/(:num)', 'AdminOpd\IkuController::edit/$1');
    $routes->get('iku', 'AdminOpd\IkuController::index');
    $routes->get('iku/tambah/(:num)', 'AdminOpd\IkuController::tambah/$1');
    $routes->post('iku/save', 'AdminOpd\IkuController::save');
    // $routes->post('iku/update/(:num)', 'AdminOpd\IkuController::update/$1');
    $routes->post('iku/update', 'AdminOpd\IkuController::update');
    $routes->match(['get', 'post', 'delete'], 'iku/delete/(:num)', 'AdminOpd\IkuController::delete/$1');

    // target
    $routes->get('target', 'AdminOpd\TargetController::index');
    $routes->get('target/tambah', 'AdminOpd\TargetController::tambah');
    $routes->post('target/save', 'AdminOpd\TargetController::save');
    $routes->get('target/edit/(:num)', 'AdminOpd\TargetController::edit/$1');
    $routes->post('target/update/(:num)', 'AdminOpd\TargetController::update/$1');

    //MONEV
    $routes->get('monev', 'AdminOpd\MonevController::index');
    $routes->get('monev/tambah', 'AdminOpd\MonevController::tambah');
    $routes->post('monev/save', 'AdminOpd\MonevController::save');
    $routes->get('monev/edit/(:num)', 'AdminOpd\MonevController::edit/$1');
    $routes->post('monev/update/(:num)', 'AdminOpd\MonevController::update/$1');

    // Lakip OPD
    $routes->get('lakip_opd', 'AdminOpd\LakipOpdController::index');
    $routes->get('lakip_opd/tambah', 'AdminOpd\LakipOpdController::tambah');
    $routes->post('lakip_opd/save', 'AdminOpd\LakipOpdController::save');
    $routes->get('lakip_opd/edit/(:num)', 'AdminOpd\LakipOpdController::edit/$1');
    $routes->post('lakip_opd/update/(:num)', 'AdminOpd\LakipOpdController::update/$1');
    $routes->get('lakip_opd/download/(:num)', 'AdminOpd\LakipOpdController::download/$1');
    $routes->post('lakip_opd/update-status', 'AdminOpd\LakipOpdController::updateStatus');
    $routes->match(['get', 'post', 'delete'], 'lakip_opd/delete/(:num)', 'AdminOpd\LakipOpdController::delete/$1');

    // Tentang Kami
    $routes->get('tentang_kami', 'AdminOpdController::tentang_kami');

    // Program PK Search
    $routes->get('program-pk/search', 'AdminOpd\ProgramPkController::search');
});


$routes->get('/login', 'LoginController::index');
$routes->post('/login/authenticate', 'LoginController::authenticate');
$routes->get('/logout', 'LoginController::logout');
