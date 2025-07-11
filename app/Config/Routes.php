<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

$routes->get('/', 'Home::index');

 // User Routes
$routes->get('/dashboard', 'User::index');
$routes->get('/rkt', 'User::rkt');
$routes->get('/rpjmd', 'User::rpjmd');
$routes->get('/lakip_kabupaten', 'User::lakip_kabupaten');
$routes->get('/pk_bupati', 'User::pk_bupati');
$routes->get('/renja', 'User::renja');
$routes->get('/renstra', 'User::renstra');
$routes->get('/lakip_opd', 'User::lakip_opd');
$routes->get('/iku_opd', 'User::iku_opd');
$routes->get('/pk_pimpinan', 'User::pk_pimpinan');
$routes->get('/pk_administrator', 'User::pk_administrator');
$routes->get('/pk_pengawas', 'User::pk_pengawas');
$routes->get('/tentang_kami', 'User::tentang_kami');


/* Admin Kabupaten Routes */
$routes->get('adminkab/dashboard', 'AdminKabupaten::index');

// RPJMD Routes - Admin Management
$routes->get('adminkab/rpjmd', 'RpjmdController::index');
$routes->get('adminkab/rpjmd/tambah', 'RpjmdController::tambah');
$routes->get('adminkab/rpjmd/edit/(:num)', 'RpjmdController::edit/$1');
$routes->post('adminkab/rpjmd/save', 'RpjmdController::save');
$routes->post('adminkab/rpjmd/update', 'RpjmdController::update');
$routes->get('adminkab/rpjmd/delete/(:num)', 'RpjmdController::delete/$1');
$routes->post('adminkab/rpjmd/delete/(:num)', 'RpjmdController::delete/$1');
$routes->delete('adminkab/rpjmd/delete/(:num)', 'RpjmdController::delete/$1');

// RPJMD API Routes for AJAX
$routes->get('adminkab/rpjmd/api/tujuan/(:num)', 'RpjmdController::get_tujuan_by_misi/$1');
$routes->get('adminkab/rpjmd/api/sasaran/(:num)', 'RpjmdController::get_sasaran_by_tujuan/$1');
$routes->get('adminkab/rpjmd/api/indikator/(:num)', 'RpjmdController::get_indikator_by_sasaran/$1');

// RPJMD API Routes for AJAX
$routes->get('adminkab/rpjmd/api/tujuan/(:num)', 'RpjmdController::get_tujuan_by_misi/$1');
$routes->get('adminkab/rpjmd/api/sasaran/(:num)', 'RpjmdController::get_sasaran_by_tujuan/$1');
$routes->get('adminkab/rpjmd/api/indikator/(:num)', 'RpjmdController::get_indikator_by_sasaran/$1');

// RPJMD Utility Routes
$routes->get('adminkab/rpjmd/search', 'RpjmdController::search');
$routes->get('adminkab/rpjmd/export', 'RpjmdController::export');

// RKT Routes
$routes->get('adminkab/rkt', 'AdminKabupaten::rkt');
$routes->get('adminkab/rkt/tambah', 'AdminKabupaten::tambah_rkt');
$routes->get('adminkab/rkt/edit', 'AdminKabupaten::edit_rkt');
$routes->post('adminkab/rkt/save', 'AdminKabupaten::save_rkt');

// PK Bupati Routes
$routes->get('adminkab/pk_bupati', 'AdminKabupaten::pk_bupati');
$routes->get('adminkab/pk_bupati/tambah', 'AdminKabupaten::tambah_pk_bupati');
$routes->get('adminkab/pk_bupati/edit', 'AdminKabupaten::edit_pk_bupati');
$routes->post('adminkab/pk_bupati/save', 'AdminKabupaten::save_pk_bupati');

// Lakip Kabupaten Routes
$routes->get('adminkab/lakip_kabupaten', 'AdminKabupaten::lakip_kabupaten');
$routes->get('adminkab/lakip_kabupaten/tambah', 'AdminKabupaten::tambah_lakip_kabupaten');
$routes->get('adminkab/lakip_kabupaten/edit', 'AdminKabupaten::edit_lakip_kabupaten');
$routes->post('adminkab/lakip_kabupaten/save', 'AdminKabupaten::save_lakip_kabupaten');

// Tentang Kami Routes
$routes->get('adminkab/tentang_kami', 'AdminKabupaten::tentang_kami');
$routes->get('adminkab/tentang_kami/edit', 'AdminKabupaten::edit_tentang_kami');
$routes->post('adminkab/tentang_kami/save', 'AdminKabupaten::save_tentang_kami');


// Admin OPD Routes
$routes->get('adminopd/dashboard', 'AdminOpd::index');

// Renstra Routes
$routes->get('adminopd/renstra', 'AdminOpd::renstra');
$routes->get('adminopd/renstra/tambah', 'AdminOpd::tambah_renstra');
$routes->get('adminopd/renstra/edit', 'AdminOpd::edit_renstra');
$routes->post('adminopd/renstra/save', 'AdminOpd::save_renstra');

// Renja Routes
$routes->get('adminopd/renja', 'AdminOpd::renja');
$routes->get('adminopd/renja/tambah', 'AdminOpd::tambah_renja');
$routes->get('adminopd/renja/edit', 'AdminOpd::edit_renja');
$routes->post('adminopd/renja/save', 'AdminOpd::save_renja');

// IKU Routes
$routes->get('adminopd/iku', 'AdminOpd::iku');
$routes->get('adminopd/iku/tambah', 'AdminOpd::tambah_iku');
$routes->get('adminopd/iku/edit', 'AdminOpd::edit_iku');
$routes->post('adminopd/iku/save', 'AdminOpd::save_iku');

$routes->get('adminopd/pk_bupati', 'AdminOpd::pk_bupati');
$routes->get('adminopd/lakip_kabupaten', 'AdminOpd::lakip_kabupaten');
$routes->get('adminopd/tentang_kami', 'AdminOpd::tentang_kami');

$routes->get('/login', 'Login::index');
$routes->post('/login/authenticate', 'Login::authenticate');
$routes->get('/logout', 'Login::logout');
