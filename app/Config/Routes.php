<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

$routes->get('/', 'Home::index');
$routes->get('/login', 'LoginController::index');
$routes->post('/login/authenticate', 'LoginController::authenticate');
$routes->get('/logout', 'LoginController::logout');

/* * User Routes
 * These routes are for the general user section.
 */
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


/* * Admin Kabupaten Routes
 * These routes are for the Kabupaten (District) management section.
 */
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
$routes->post('adminkab/rkpd/update-status', 'RkpdController::updateStatus');

 
// PK Bupati Routes
$routes->get('adminkab/pk_bupati', 'PkBupatiController::index');
$routes->get('adminkab/pk_bupati/tambah', 'PkBupatiController::tambah');
$routes->post('adminkab/pk_bupati/save', 'PkBupatiController::save');
$routes->get('adminkab/pk_bupati/edit/(:num)', 'PkBupatiController::edit/$1');
$routes->post('adminkab/pk_bupati/update', 'PkBupatiController::update');
$routes->post('adminkab/pk_bupati/update/(:num)', 'PkBupatiController::update/$1');
$routes->post('adminkab/pk_bupati/delete/(:num)', 'PkBupatiController::delete/$1');
$routes->get('adminkab/pk_bupati/delete/(:num)', 'PkBupatiController::delete/$1');
$routes->delete('adminkab/pk_bupati/delete/(:num)', 'PkBupatiController::delete/$1');
$routes->get('adminkab/pk_bupati/cetak/(:num)', 'PkBupatiController::cetak/$1');


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


/*
 * Admin OPD Routes
 * These routes are for the OPD (Organisasi Perangkat Daerah) management section.
 */

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
$routes->post('adminopd/renstra/update-status', 'AdminOpd\RenstraController::updateStatus');


// PK Admin Routes
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

// PK OPD Routes
$routes->get('adminopd/pk_opd', 'AdminOpd\PkOpdController::index');
$routes->get('adminopd/pk_opd/tambah', 'AdminOpd\PkOpdController::create');
$routes->post('adminopd/pk_opd/save', 'AdminOpd\PkOpdController::save');
$routes->get('adminopd/pk_opd/edit/(:num)', 'AdminOpd\PkOpdController::edit/$1');
$routes->post('adminopd/pk_opd/update/(:num)', 'AdminOpd\PkOpdController::update/$1');
$routes->get('adminopd/pk_opd/delete/(:num)', 'AdminOpd\PkOpdController::delete/$1');
$routes->post('adminopd/pk_opd/getSasaranByJenis', 'AdminOpd\PkOpdController::getSasaranByJenis');
$routes->post('adminopd/pk_opd/getProgramKegiatanByJenis', 'AdminOpd\PkOpdController::getProgramKegiatanByJenis');
$routes->get('adminopd/pk_opd/cetak/(:num)', 'AdminOpd\PkOpdController::cetak/$1');
$routes->get('adminopd/pk_opd/cetak', 'AdminOpd\PkOpdController::view_cetak');


// RENJA Routes
$routes->get('adminopd/renja', 'AdminOpd\RenjaController::index');
$routes->get('adminopd/renja/tambah', 'AdminOpd\RenjaController::tambah');
$routes->get('adminopd/renja/edit/(:num)', 'AdminOpd\RenjaController::edit/$1');
$routes->post('adminopd/renja/save', 'AdminOpd\RenjaController::save');
$routes->post('adminopd/renja/update', 'AdminOpd\RenjaController::update');
$routes->get('adminopd/renja/delete/(:num)', 'AdminOpd\RenjaController::delete/$1');
$routes->post('adminopd/renja/delete/(:num)', 'AdminOpd\RenjaController::delete/$1');
$routes->delete('adminopd/renja/delete/(:num)', 'AdminOpd\RenjaController::delete/$1');
$routes->post('adminopd/renja/update-status', 'AdminOpd\RenjaController::updateStatus');
    
// IKU OPD Routes
$routes->get('adminopd/iku', 'AdminOpd\IkutController::index');
$routes->get('adminopd/iku/tambah', 'AdminOpd\IkutController::tambah');
$routes->post('adminopd/iku/save', 'AdminOpd\IkutController::save');
$routes->get('adminopd/iku/edit/(:num)', 'AdminOpd\IkutController::edit/$1');
$routes->post('adminopd/iku/update', 'AdminOpd\IkutController::update');
$routes->get('adminopd/iku/delete/(:num)', 'AdminOpd\IkutController::delete/$1');
$routes->post('adminopd/iku/delete/(:num)', 'AdminOpd\IkutController::delete/$1');
$routes->delete('adminopd/iku/delete/(:num)', 'AdminOpd\IkutController::delete/$1');
$routes->post('adminopd/iku/update-status', 'AdminOpd\IkutController::updateStatus');


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

// PK OPD Routes (New System)
$routes->get('adminopd/pk_opd', 'PkOpdController::index');
$routes->get('adminopd/pk_opd/create', 'PkOpdController::create');
$routes->post('adminopd/pk_opd/save', 'PkOpdController::save');
$routes->get('adminopd/pk_opd/edit/(:num)', 'PkOpdController::edit/$1');
$routes->post('adminopd/pk_opd/update/(:num)', 'PkOpdController::update/$1');
$routes->get('adminopd/pk_opd/delete/(:num)', 'PkOpdController::delete/$1');
$routes->post('adminopd/pk_opd/getSasaranByJenis', 'PkOpdController::getSasaranByJenis');
$routes->post('adminopd/pk_opd/getProgramKegiatanByJenis', 'PkOpdController::getProgramKegiatanByJenis');

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

// Kegiatan PK OPD Routes
$routes->get('adminopd/kegiatan_opd', 'AdminOpd\KegiatanPkController::index');
$routes->get('adminopd/kegiatan_opd/tambah', 'AdminOpd\KegiatanPkController::tambah');
$routes->get('adminopd/kegiatan_opd/edit/(:num)', 'AdminOpd\KegiatanPkController::edit/$1');
$routes->post('adminopd/kegiatan_opd/save', 'AdminOpd\KegiatanPkController::save');
$routes->post('adminopd/kegiatan_opd/update/(:num)', 'AdminOpd\KegiatanPkController::update/$1');
$routes->get('adminopd/kegiatan_opd/delete/(:num)', 'AdminOpd\KegiatanPkController::delete/$1');


$routes->get('adminopd/tentang_kami', 'AdminOpdController::tentang_kami');
