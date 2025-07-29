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


/* Admin Kabupaten Routes */
$routes->get('adminkab/dashboard', 'AdminKabupatenController::index');
$routes->post('adminkab/getDashboardData', 'AdminKabupatenController::getDashboardData');
$routes->get('adminkab/getStats', 'AdminKabupatenController::getStats');

// RPJMD Routes - Admin Management
$routes->get('adminkab/rpjmd', 'RpjmdController::index');
$routes->get('adminkab/rpjmd/tambah', 'RpjmdController::tambah');
$routes->get('adminkab/rpjmd/edit/(:num)', 'RpjmdController::edit/$1');
$routes->post('adminkab/rpjmd/save', 'RpjmdController::save');
$routes->post('adminkab/rpjmd/update', 'RpjmdController::update');
$routes->get('adminkab/rpjmd/delete/(:num)', 'RpjmdController::delete/$1');
$routes->post('adminkab/rpjmd/delete/(:num)', 'RpjmdController::delete/$1');
$routes->delete('adminkab/rpjmd/delete/(:num)', 'RpjmdController::delete/$1');
// RPJMD Status Management Routes
$routes->post('adminkab/rpjmd/update-status', 'RpjmdController::updateStatus');


// RKPD Routes
$routes->get('adminkab/rkpd', 'RkpdController::index');
$routes->get('adminkab/rkpd/tambah', 'RkpdController::tambah');
$routes->get('adminkab/rkpd/edit/(:num)', 'RkpdController::edit/$1');
$routes->post('adminkab/rkpd/save', 'RkpdController::save');
$routes->post('adminkab/rkpd/update', 'RkpdController::update');
$routes->get('adminkab/rkpd/delete/(:num)', 'RkpdController::delete/$1');
$routes->post('adminkab/rkpd/delete/(:num)', 'RkpdController::delete/$1');
$routes->delete('adminkab/rkpd/delete/(:num)', 'RkpdController::delete/$1');
// RKPD Status Management Routes
$routes->post('adminkab/rkpd/update-status', 'RkpdController::updateStatus');


// PK Bupati Routes
$routes->get('adminkab/pk_bupati', 'AdminKabupatenController::pk_bupati');
$routes->get('adminkab/pk_bupati/tambah', 'AdminKabupatenController::tambah_pk_bupati');
$routes->get('adminkab/pk_bupati/edit', 'AdminKabupatenController::edit_pk_bupati');
$routes->post('adminkab/pk_bupati/save', 'AdminKabupatenController::save_pk_bupati');


// Lakip Kabupaten Routes
$routes->get('adminkab/lakip_kabupaten', 'LakipKabupatenController::index');
$routes->get('adminkab/lakip_kabupaten/tambah', 'LakipKabupatenController::tambah');
$routes->post('adminkab/lakip_kabupaten/save', 'LakipKabupatenController::save');
$routes->get('adminkab/lakip_kabupaten/edit/(:num)', 'LakipKabupatenController::edit/$1');
$routes->post('adminkab/lakip_kabupaten/update/(:num)', 'LakipKabupatenController::update/$1');
$routes->get('adminkab/lakip_kabupaten/download/(:num)', 'LakipKabupatenController::download/$1');
$routes->post('adminkab/lakip_kabupaten/update-status', 'LakipKabupatenController::updateStatus');
$routes->get('adminkab/lakip_kabupaten/delete/(:num)', 'LakipKabupatenController::delete/$1');
$routes->post('adminkab/lakip_kabupaten/delete/(:num)', 'LakipKabupatenController::delete/$1');
$routes->delete('adminkab/lakip_kabupaten/delete/(:num)', 'LakipKabupatenController::delete/$1');


// Program PK Routes
$routes->get('adminkab/program_pk', 'ProgramPkController::index');
$routes->get('adminkab/program_pk/tambah', 'ProgramPkController::tambah');
$routes->get('adminkab/program_pk/edit/(:num)', 'ProgramPkController::edit/$1');
$routes->post('adminkab/program_pk/save', 'ProgramPkController::save');
$routes->post('adminkab/program_pk/update/(:num)', 'ProgramPkController::update/$1');
$routes->get('adminkab/program_pk/delete/(:num)', 'ProgramPkController::delete/$1');
$routes->get('adminopd/program-pk/search', 'AdminOpd\ProgramPkController::search');


// Tentang Kami Routes
$routes->get('adminkab/tentang_kami', 'AdminKabupatenController::tentang_kami');
$routes->get('adminkab/tentang_kami/edit', 'AdminKabupatenController::edit_tentang_kami');
$routes->post('adminkab/tentang_kami/save', 'AdminKabupatenController::save_tentang_kami');



// Admin OPD Routes
$routes->get('adminopd/dashboard', 'AdminOpdController::index');

// Renstra Routes
$routes->get('adminopd/renstra', 'AdminOpd\RenstraController::index');
$routes->get('adminopd/renstra/tambah', 'AdminOpd\RenstraController::tambah_renstra');
$routes->get('adminopd/renstra/edit/(:num)', 'AdminOpd\RenstraController::edit/$1');
$routes->post('adminopd/renstra/save', 'AdminOpd\RenstraController::save');
$routes->post('adminopd/renstra/update/(:num)', 'AdminOpd\RenstraController::update/$1');
$routes->get('adminopd/renstra/delete/(:num)', 'AdminOpd\RenstraController::delete/$1');
$routes->post('adminopd/renstra/delete/(:num)', 'AdminOpd\RenstraController::delete/$1');
$routes->delete('adminopd/renstra/delete/(:num)', 'AdminOpd\RenstraController::delete/$1');
// Renstra Status Management Routes
$routes->post('adminopd/renstra/update-status', 'AdminOpd\RenstraController::updateStatus');


// RENJA Routes
$routes->get('adminopd/renja', 'AdminOpd\RenjaController::index');
$routes->get('adminopd/renja/tambah', 'AdminOpd\RenjaController::tambah');
$routes->get('adminopd/renja/edit/(:num)', 'AdminOpd\RenjaController::edit/$1');
$routes->post('adminopd/renja/save', 'AdminOpd\RenjaController::save');
$routes->post('adminopd/renja/update', 'AdminOpd\RenjaController::update');
$routes->get('adminopd/renja/delete/(:num)', 'AdminOpd\RenjaController::delete/$1');
$routes->post('adminopd/renja/delete/(:num)', 'AdminOpd\RenjaController::delete/$1');
$routes->delete('adminopd/renja/delete/(:num)', 'AdminOpd\RenjaController::delete/$1');
// RENJA Status Management Routes
$routes->post('adminopd/renja/update-status', 'AdminOpd\RenjaController::updateStatus');
// $routes->get('adminopd/renja/toggle-status/(:num)', 'AdminOpd\RenjaController::toggleStatus/$1');

    
// IKU Routes
$routes->get('adminopd/iku', 'AdminOpdController::iku');
$routes->get('adminopd/iku/tambah', 'AdminOpdController::tambah_iku');
$routes->get('adminopd/iku/edit', 'AdminOpdController::edit_iku');
$routes->post('adminopd/iku/save', 'AdminOpdController::save_iku');

// PK Administrator Routes
$routes->get('adminopd/pk_admin', 'AdminOpd\PkAdminController::index');
$routes->get('adminopd/pk_admin/tambah', 'AdminOpd\PkAdminController::tambah');
$routes->post('adminopd/pk_admin/save', 'AdminOpd\PkAdminController::save');
$routes->get('adminopd/pk_admin/edit/(:num)', 'AdminOpd\PkAdminController::edit/$1');
$routes->post('adminopd/pk_admin/update', 'AdminOpd\PkAdminController::update');
$routes->post('adminopd/pk_admin/delete/(:num)', 'AdminOpd\PkAdminController::delete/$1');
$routes->get('adminopd/pk_admin/delete/(:num)', 'AdminOpd\PkAdminController::delete/$1');
$routes->delete('adminopd/pk_admin/delete/(:num)', 'AdminOpd\PkAdminController::delete/$1');
$routes->get('adminopd/pk_admin/cetak/(:num)', 'AdminOpd\PkAdminController::cetak/$1');
$routes->get('adminopd/pk_admin/cetak', 'AdminOpd\PkAdminController::view_cetak');

// Lakip Opd Routes
$routes->get('adminopd/lakip_opd', 'AdminOpd\LakipOpdController::index');
$routes->get('adminopd/lakip_opd/tambah', 'AdminOpd\LakipOpdController::tambah');
$routes->post('adminopd/lakip_opd/save', 'AdminOpd\LakipOpdController::save');
$routes->get('adminopd/lakip_opd/edit/(:num)', 'AdminOpd\LakipOpdController::edit/$1');
$routes->post('adminopd/lakip_opd/update/(:num)', 'AdminOpd\LakipOpdController::update/$1');
$routes->get('adminopd/lakip_opd/download/(:num)', 'AdminOpd\LakipOpdController::download/$1');
$routes->post('adminopd/lakip_opd/update-status', 'AdminOpd\LakipOpdController::updateStatus');
$routes->get('adminopd/lakip_opd/delete/(:num)', 'AdminOpd\LakipOpdController::delete/$1');
$routes->post('adminopd/lakip_opd/delete/(:num)', 'AdminOpd\LakipOpdController::delete/$1');
$routes->delete('adminopd/lakip_opd/delete/(:num)', 'AdminOpd\LakipOpdController::delete/$1');


$routes->get('adminopd/tentang_kami', 'AdminOpdController::tentang_kami');

$routes->get('/login', 'LoginController::index');
$routes->post('/login/authenticate', 'LoginController::authenticate');
$routes->get('/logout', 'LoginController::logout');
