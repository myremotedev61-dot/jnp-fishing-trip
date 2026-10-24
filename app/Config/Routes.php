<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'AdminController::login');

// --------------------------------------------------------------------
// DASHBOARD ADMINISTRATOR & PEMILIK USAHA
// --------------------------------------------------------------------
$routes->get('admin/login', 'AdminController::login');
$routes->post('admin/do-login', 'AdminController::doLogin');
$routes->get('admin/logout', 'AdminController::logout');

$routes->group('admin', ['filter' => 'admin-auth'], function ($routes) {
    $routes->get('dashboard', 'AdminController::dashboard');
    
    // Kapal
    $routes->get('kapal', 'AdminController::kapal');
    $routes->post('kapal/save', 'AdminController::saveKapal');
    $routes->get('kapal/toggle/(:num)', 'AdminController::toggleKapalStatus/$1');

    // Trips
    $routes->get('trips', 'AdminController::trips');
    $routes->post('trips/save', 'AdminController::saveTrip');

    // Peserta & Persetujuan
    $routes->get('peserta', 'AdminController::peserta');
    $routes->match(['get', 'post'], 'peserta/(:num)/setujui', 'AdminController::setujuiPeserta/$1');
    $routes->match(['get', 'post'], 'peserta/(:num)/tolak', 'AdminController::tolakPeserta/$1');
    $routes->match(['get', 'post'], 'peserta/(:num)/batal', 'AdminController::prosesBatalPeserta/$1');
    $routes->get('persetujuan', 'AdminController::persetujuan');

    // Pembayaran
    $routes->get('pembayaran', 'AdminController::pembayaran');
    $routes->match(['get', 'post'], 'pembayaran/(:num)/verifikasi', 'AdminController::verifikasiPembayaranAksi/$1');
    $routes->match(['get', 'post'], 'pembayaran/(:num)/tolak', 'AdminController::tolakPembayaranAksi/$1');
    $routes->get('pembayaran/(:num)/bukti', 'AdminController::viewBuktiAdmin/$1');

    // Pembatalan & Refund
    $routes->get('pembatalan', 'AdminController::pembatalan');
    $routes->post('pembatalan/refund', 'AdminController::prosesRefund');

    // Laporan & Pengaturan
    $routes->get('laporan', 'AdminController::laporan');
    $routes->get('pengaturan', 'AdminController::pengaturan');
    $routes->post('pengaturan/save', 'AdminController::savePengaturan');
});

// --------------------------------------------------------------------
// REST API ROUTES
// --------------------------------------------------------------------
$routes->group('api', function ($routes) {

    // === Public Endpoints (Tanpa Auth) ===
    $routes->group('auth', function ($routes) {
        $routes->post('register', 'Api\AuthController::register');
        $routes->post('login', 'Api\AuthController::login');
    });

    // Jadwal Trip, Kapal, & Pengaturan Usaha (Public)
    $routes->get('trips', 'Api\TripController::index');
    $routes->get('trips/(:num)', 'Api\TripController::detail/$1');
    $routes->get('trips/(:num)/kuota', 'Api\TripController::getQuota/$1');
    $routes->get('kapal', 'Api\TripController::kapalList');
    $routes->get('pengaturan', 'Api\TripController::pengaturan');

    // === Protected Endpoints (Membutuhkan Header Authorization: Bearer <token>) ===
    $routes->group('', ['filter' => 'api-auth'], function ($routes) {
        // Auth Pengguna
        $routes->get('auth/me', 'Api\AuthController::me');
        $routes->post('auth/logout', 'Api\AuthController::logout');
        $routes->put('auth/profile', 'Api\AuthController::updateProfile');
        $routes->put('auth/change-password', 'Api\AuthController::changePassword');
        $routes->post('auth/fcm-token', 'Api\AuthController::registerFcmToken');

        // Pendaftaran Trip
        $routes->post('pendaftaran', 'Api\PendaftaranController::create');
        $routes->get('pendaftaran/saya', 'Api\PendaftaranController::myRegistrations');
        $routes->get('pendaftaran/(:num)', 'Api\PendaftaranController::detail/$1');
        $routes->post('pendaftaran/(:num)/peserta', 'Api\PendaftaranController::addPeserta/$1');
        $routes->match(['put', 'post'], 'peserta/(:num)', 'Api\PendaftaranController::updatePeserta/$1');

        // Pembayaran
        $routes->post('pembayaran', 'Api\PembayaranController::create');
        $routes->get('pembayaran/saya', 'Api\PembayaranController::myPayments');
        $routes->get('pembayaran/(:num)', 'Api\PembayaranController::detail/$1');
        $routes->get('pembayaran/(:num)/bukti', 'Api\PembayaranController::viewBukti/$1');

        // Notifikasi
        $routes->get('notifikasi', 'Api\NotifikasiController::index');
        $routes->put('notifikasi/(:num)/read', 'Api\NotifikasiController::markAsRead/$1');
        $routes->put('notifikasi/read-all', 'Api\NotifikasiController::markAllAsRead');
    });
});
