<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

$routes->get('/', 'Home::index');
$routes->get('/login', 'LoginController::index');
$routes->post('/login/authenticate', 'LoginController::authenticate');
$routes->get('/logout', 'LoginController::logout');
$routes->get('/unauthorized', 'Home::unauthorized');

/* * User Routes
 * These routes are for the general user section.
 */
 // User Routes
$routes->get('/dashboard', 'UserController::index');
$routes->get('/rpjmd', 'UserController::rpjmd');
$routes->get('/rkpd', 'UserController::rkpd');
$routes->get('/lakip_kabupaten', 'UserController::lakipKabupaten');
$routes->get('/lakip_kabupaten/download/(:num)', 'UserController::downloadLakip/$1');
$routes->get('/pk_bupati', 'UserController::pkBupati');
$routes->get('/renja', 'UserController::renja');
$routes->get('/renstra', 'UserController::renstra');
$routes->get('/iku_opd', 'UserController::ikuOpd');
$routes->get('/pk_jpt', 'UserController::pkJpt');
$routes->get('/pk_administrator', 'UserController::pkAdministrator');
$routes->get('/pk_pengawas', 'UserController::pkPengawas');
$routes->get('/lakip_opd', 'UserController::lakipOpd');
$routes->get('/tentang_kami', 'UserController::tentang_kami');


/* * Admin Kabupaten Routes
 * These routes are for the Kabupaten (District) management section.
 */
/* Admin Kabupaten Routes */
$routes->group('adminkab', ['filter' => 'auth:admin_kabupaten'], function($routes) {
    $routes->get('dashboard', 'AdminKabupatenController::index');
    $routes->post('getDashboardData', 'AdminKabupatenController::getDashboardData');
    $routes->get('getStats', 'AdminKabupatenController::getStats');

    // RPJMD Routes - Admin Management
    $routes->get('rpjmd', 'RpjmdController::index');
    $routes->get('rpjmd/tambah', 'RpjmdController::tambah');
    $routes->get('rpjmd/edit/(:num)', 'RpjmdController::edit/$1');
    $routes->post('rpjmd/save', 'RpjmdController::save');
    $routes->post('rpjmd/update', 'RpjmdController::update');
    $routes->get('rpjmd/delete/(:num)', 'RpjmdController::delete/$1');
    $routes->post('rpjmd/delete/(:num)', 'RpjmdController::delete/$1');
    $routes->delete('rpjmd/delete/(:num)', 'RpjmdController::delete/$1');
    $routes->post('rpjmd/update-status', 'RpjmdController::updateStatus');

    // RKPD Routes
    $routes->get('rkpd', 'RkpdController::index');
    $routes->get('rkpd/tambah', 'RkpdController::tambah');
    $routes->get('rkpd/edit/(:num)', 'RkpdController::edit/$1');
    $routes->post('rkpd/save', 'RkpdController::save');
    $routes->post('rkpd/update', 'RkpdController::update');
    $routes->get('rkpd/delete/(:num)', 'RkpdController::delete/$1');
    $routes->post('rkpd/delete/(:num)', 'RkpdController::delete/$1');
    $routes->delete('rkpd/delete/(:num)', 'RkpdController::delete/$1');
    $routes->post('rkpd/update-status', 'RkpdController::updateStatus');

    // PK Bupati Routes
    $routes->get('pk_bupati', 'PkBupatiController::index');
    $routes->get('pk_bupati/tambah', 'PkBupatiController::tambah');
    $routes->post('pk_bupati/save', 'PkBupatiController::save');
    $routes->get('pk_bupati/edit/(:num)', 'PkBupatiController::edit/$1');
    $routes->post('pk_bupati/update', 'PkBupatiController::update');
    $routes->post('pk_bupati/update/(:num)', 'PkBupatiController::update/$1');
    $routes->post('pk_bupati/delete/(:num)', 'PkBupatiController::delete/$1');
    $routes->get('pk_bupati/delete/(:num)', 'PkBupatiController::delete/$1');
    $routes->delete('pk_bupati/delete/(:num)', 'PkBupatiController::delete/$1');
    $routes->get('pk_bupati/cetak/(:num)', 'PkBupatiController::cetak/$1');

    // Lakip Kabupaten Routes
    $routes->get('lakip_kabupaten', 'LakipKabupatenController::index');
    $routes->get('lakip_kabupaten/tambah', 'LakipKabupatenController::tambah');
    $routes->post('lakip_kabupaten/save', 'LakipKabupatenController::save');
    $routes->get('lakip_kabupaten/edit/(:num)', 'LakipKabupatenController::edit/$1');
    $routes->post('lakip_kabupaten/update/(:num)', 'LakipKabupatenController::update/$1');
    $routes->get('lakip_kabupaten/download/(:num)', 'LakipKabupatenController::download/$1');
    $routes->post('lakip_kabupaten/update-status', 'LakipKabupatenController::updateStatus');
    $routes->get('lakip_kabupaten/delete/(:num)', 'LakipKabupatenController::delete/$1');
    $routes->post('lakip_kabupaten/delete/(:num)', 'LakipKabupatenController::delete/$1');
    $routes->delete('lakip_kabupaten/delete/(:num)', 'LakipKabupatenController::delete/$1');

    // Program PK Routes
    $routes->get('program_pk', 'ProgramPkController::index');
    $routes->get('program_pk/tambah', 'ProgramPkController::tambah');
    $routes->get('program_pk/edit/(:num)', 'ProgramPkController::edit/$1');
    $routes->post('program_pk/save', 'ProgramPkController::save');
    $routes->post('program_pk/update/(:num)', 'ProgramPkController::update/$1');
    $routes->get('program_pk/delete/(:num)', 'ProgramPkController::delete/$1');

    // Tentang Kami Routes
    $routes->get('tentang_kami', 'AdminKabupatenController::tentangKami');
});

/*
 * Admin OPD Routes
 * These routes are for the OPD (Organisasi Perangkat Daerah) management section.
 */

// Admin OPD Routes
$routes->group('adminopd', ['filter' => 'auth:admin_opd'], function($routes) {

    $routes->get('dashboard', 'AdminOpdController::index');

    // Renstra Routes
    $routes->get('renstra', 'AdminOpd\RenstraController::index');
    $routes->get('renstra/tambah', 'AdminOpd\RenstraController::tambah_renstra');
    $routes->get('renstra/edit/(:num)', 'AdminOpd\RenstraController::edit/$1');
    $routes->post('renstra/save', 'AdminOpd\RenstraController::save');
    $routes->post('renstra/update/(:num)', 'AdminOpd\RenstraController::update/$1');
    $routes->get('renstra/delete/(:num)', 'AdminOpd\RenstraController::delete/$1');
    $routes->post('renstra/delete/(:num)', 'AdminOpd\RenstraController::delete/$1');
    $routes->delete('renstra/delete/(:num)', 'AdminOpd\RenstraController::delete/$1');
    $routes->post('renstra/update-status', 'AdminOpd\RenstraController::updateStatus');

    // PK OPD Routes
    $routes->get('pk_opd', 'AdminOpd\PkOpdController::index');
    $routes->get('pk_opd/tambah', 'AdminOpd\PkOpdController::tambah');
    $routes->post('pk_opd/save', 'AdminOpd\PkOpdController::save');
    $routes->get('pk_opd/edit/(:num)', 'AdminOpd\PkOpdController::edit/$1');
    $routes->post('pk_opd/update', 'AdminOpd\PkOpdController::update');
    $routes->post('pk_opd/delete/(:num)', 'AdminOpd\PkOpdController::delete/$1');
    $routes->get('pk_opd/delete/(:num)', 'AdminOpd\PkOpdController::delete/$1');
    $routes->delete('pk_opd/delete/(:num)', 'AdminOpd\PkOpdController::delete/$1');
    $routes->get('pk_opd/cetak/(:num)', 'AdminOpd\PkOpdController::cetak/$1');
    $routes->get('pk_opd/cetak', 'AdminOpd\PkOpdController::view_cetak');

    // RENJA Routes
    $routes->get('renja', 'AdminOpd\RenjaController::index');
    $routes->get('renja/tambah', 'AdminOpd\RenjaController::tambah');
    $routes->get('renja/edit/(:num)', 'AdminOpd\RenjaController::edit/$1');
    $routes->post('renja/save', 'AdminOpd\RenjaController::save');
    $routes->post('renja/update', 'AdminOpd\RenjaController::update');
    $routes->get('renja/delete/(:num)', 'AdminOpd\RenjaController::delete/$1');
    $routes->post('renja/delete/(:num)', 'AdminOpd\RenjaController::delete/$1');
    $routes->delete('renja/delete/(:num)', 'AdminOpd\RenjaController::delete/$1');
    $routes->post('renja/update-status', 'AdminOpd\RenjaController::updateStatus');
        
    // IKU OPD Routes
    $routes->get('iku_opd', 'AdminOpd\IkuOpdController::index');
    $routes->get('iku_opd/tambah', 'AdminOpd\IkuOpdController::tambah');
    $routes->post('iku_opd/save', 'AdminOpd\IkuOpdController::save');
    $routes->get('iku_opd/edit/(:num)', 'AdminOpd\IkuOpdController::edit/$1');
    $routes->post('iku_opd/update/(:num)', 'AdminOpd\IkuOpdController::update/$1');
    $routes->get('iku_opd/delete/(:num)', 'AdminOpd\IkuOpdController::delete/$1');
    $routes->post('iku_opd/delete/(:num)', 'AdminOpd\IkuOpdController::delete/$1');
    $routes->delete('iku_opd/delete/(:num)', 'AdminOpd\IkuOpdController::delete/$1');
    $routes->post('iku_opd/update-status', 'AdminOpd\IkuOpdController::updateStatus');

    // Lakip Opd Routes
    $routes->get('lakip_opd', 'AdminOpd\LakipOpdController::index');
    $routes->get('lakip_opd/tambah', 'AdminOpd\LakipOpdController::tambah');
    $routes->post('lakip_opd/save', 'AdminOpd\LakipOpdController::save');
    $routes->get('lakip_opd/edit/(:num)', 'AdminOpd\LakipOpdController::edit/$1');
    $routes->post('lakip_opd/update/(:num)', 'AdminOpd\LakipOpdController::update/$1');
    $routes->get('lakip_opd/download/(:num)', 'AdminOpd\LakipOpdController::download/$1');
    $routes->post('lakip_opd/update-status', 'AdminOpd\LakipOpdController::updateStatus');
    $routes->get('lakip_opd/delete/(:num)', 'AdminOpd\LakipOpdController::delete/$1');
    $routes->post('lakip_opd/delete/(:num)', 'AdminOpd\LakipOpdController::delete/$1');
    $routes->delete('lakip_opd/delete/(:num)', 'AdminOpd\LakipOpdController::delete/$1');

    // Tentang Kami Routes
    $routes->get('tentang_kami', 'AdminOpdController::tentang_kami');
});