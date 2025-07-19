<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

$routes->get('/', 'Home::index');

// ==========================
// 🔐 AUTH ROUTES
// ==========================
$routes->get('/login', 'LoginController::index');
$routes->post('/login/authenticate', 'LoginController::authenticate');
$routes->get('/logout', 'LoginController::logout');

// ==========================
// 👤 USER ROUTES
// ==========================
$routes->group('', function($routes) {
    $routes->get('/dashboard', 'UserController::index');
    $routes->get('/rkt', 'UserController::rkt');
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
    $routes->get('/rpjmd_tes', 'UserController::rpjmd_tes');
});

// ==========================
// 🛡️ ADMIN KABUPATEN (With Auth)
// ==========================
$routes->group('adminkab', ['filter' => 'auth'], function($routes) {
    $routes->get('dashboard', 'AdminKabupatenController::index');

    // RPJMD
    $routes->get('rpjmd', 'RpjmdController::index');
    $routes->get('rpjmd/tambah', 'RpjmdController::tambah');
    $routes->get('rpjmd/edit/(:num)', 'RpjmdController::edit/$1');
    $routes->post('rpjmd/save', 'RpjmdController::save');
    $routes->post('rpjmd/update', 'RpjmdController::update');
    $routes->match(['get', 'post', 'delete'], 'rpjmd/delete/(:num)', 'RpjmdController::delete/$1');
    $routes->post('rpjmd/update-status', 'RpjmdController::updateStatus');
    $routes->get('rpjmd/toggle-status/(:num)', 'RpjmdController::toggleStatus/$1');

    // RKT
    $routes->get('rkt', 'AdminKabupatenController::rkt');
    $routes->get('rkt/tambah', 'AdminKabupatenController::tambah_rkt');
    $routes->get('rkt/edit', 'AdminKabupatenController::edit_rkt');
    $routes->post('rkt/save', 'AdminKabupatenController::save_rkt');

    // PK Bupati
    $routes->get('pk_bupati', 'AdminKabupatenController::pk_bupati');
    $routes->get('pk_bupati/tambah', 'AdminKabupatenController::tambah_pk_bupati');
    $routes->get('pk_bupati/edit', 'AdminKabupatenController::edit_pk_bupati');
    $routes->post('pk_bupati/save', 'AdminKabupatenController::save_pk_bupati');

    // Lakip
    $routes->get('lakip_kabupaten', 'AdminKabupatenController::lakip_kabupaten');
    $routes->get('lakip_kabupaten/tambah', 'AdminKabupatenController::tambah_lakip_kabupaten');
    $routes->get('lakip_kabupaten/edit', 'AdminKabupatenController::edit_lakip_kabupaten');
    $routes->post('lakip_kabupaten/save', 'AdminKabupatenController::save_lakip_kabupaten');

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
});

// ==========================
// 🛡️ ADMIN OPD (With Auth)
// ==========================
$routes->group('adminopd', ['filter' => 'auth'], function($routes) {
    $routes->get('dashboard', 'AdminOpdController::index');

    // Renstra
    $routes->get('renstra', 'AdminOpd\RenstraController::index');
    $routes->get('renstra/tambah', 'AdminOpd\RenstraController::tambah_renstra');
    $routes->get('renstra/edit/(:num)', 'AdminOpd\RenstraController::edit_renstra/$1');
    $routes->post('renstra/save', 'AdminOpd\RenstraController::save');
    $routes->post('renstra/update/(:num)', 'AdminOpd\RenstraController::update/$1');
    $routes->delete('renstra/delete/(:num)', 'AdminOpd\RenstraController::delete/$1');

    // Renja
    $routes->get('renja', 'AdminOpdController::renja');
    $routes->get('renja/tambah', 'AdminOpdController::tambah_renja');
    $routes->get('renja/edit', 'AdminOpdController::edit_renja');
    $routes->post('renja/save', 'AdminOpdController::save_renja');

    // IKU
    $routes->get('iku', 'AdminOpdController::iku');
    $routes->get('iku/tambah', 'AdminOpdController::tambah_iku');
    $routes->get('iku/edit', 'AdminOpdController::edit_iku');
    $routes->post('iku/save', 'AdminOpdController::save_iku');

    // PK Admin
    $routes->get('pk_admin', 'AdminOpd\PkAdminController::index');
    $routes->get('pk_admin/tambah', 'AdminOpd\PkAdminController::tambah');
    $routes->post('pk_admin/save', 'AdminOpd\PkAdminController::save');
    $routes->get('pk_admin/edit/(:num)', 'AdminOpd\PkAdminController::edit/$1');
    $routes->post('pk_admin/update', 'AdminOpd\PkAdminController::update');
    $routes->match(['get', 'post', 'delete'], 'pk_admin/delete/(:num)', 'AdminOpd\PkAdminController::delete/$1');
    $routes->get('pk_admin/cetak/(:num)', 'AdminOpd\PkAdminController::cetak/$1');

    // Tentang Kami
    $routes->get('tentang_kami', 'AdminOpdController::tentang_kami');

    // Search
    $routes->get('program-pk/search', 'AdminOpd\ProgramPkController::search');
});
