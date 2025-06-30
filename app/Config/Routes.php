<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');
$routes->get('/dashboard', 'User::dashboard');
$routes->get('/rkt', 'User::rkt');
$routes->get('/rpjmd', 'User::rpjmd');
$routes->get('/iku_kabupaten', 'User::iku_kabupaten');
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