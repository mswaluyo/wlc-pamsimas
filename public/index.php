<?php
// Front-Controller: Titik masuk tunggal untuk semua request.

// Mulai atau lanjutkan session di setiap request
// Konfigurasi session agar login bertahan selama 30 hari (2592000 detik)
session_start([
    'cookie_lifetime' => 2592000,
    'gc_maxlifetime' => 2592000,
]);

// PERBAIKAN: Atur zona waktu secara global di titik masuk aplikasi.
date_default_timezone_set('Asia/Jakarta');

define('ROOT_PATH', dirname(__DIR__));

// --- Maintenance Mode Check ---
$maintenanceFile = ROOT_PATH . '/.maintenance';
if (file_exists($maintenanceFile)) {
    // Izinkan akses jika:
    // 1. User adalah Administrator
    // 2. Sedang mengakses halaman login (untuk admin login)
    // 3. Request ke API (agar perangkat IoT tetap jalan)
    
    $isAdmin = isset($_SESSION['user']) && $_SESSION['user']['role'] === 'Administrator';
    $requestUri = $_SERVER['REQUEST_URI'];
    $isAllowedRoute = (strpos($requestUri, '/login') !== false) || (strpos($requestUri, '/api/') !== false);

    if (!$isAdmin && !$isAllowedRoute) {
        http_response_code(503);
        die('<div style="text-align:center; padding:50px; font-family:sans-serif; color:#333;"><h1>503 Service Unavailable</h1><p>Sistem sedang dalam perbaikan (Maintenance Mode).<br>Silakan coba beberapa saat lagi.</p><hr><small>Administrator dapat login melalui <a href="login">halaman login</a>.</small></div>');
    }
}

// --- Load .env file manually (Parser Sederhana) ---
if (file_exists(ROOT_PATH . '/.env')) {
    $lines = file(ROOT_PATH . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue; // Lewati komentar
        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);
            putenv(sprintf('%s=%s', $name, $value));
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
}

// Autoloader sederhana untuk memuat kelas secara otomatis
spl_autoload_register(function ($className) {
    // Mengonversi namespace (jika ada) menjadi path direktori
    $file = ROOT_PATH . '/' . str_replace('\\', '/', $className) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// Memuat kelas inti secara manual
require_once ROOT_PATH . '/core/Database.php';
require_once ROOT_PATH . '/core/Router.php';
require_once ROOT_PATH . '/core/helpers.php'; // Memuat fungsi helper

// Inisialisasi router
$router = new Router();

// Memuat definisi rute dari file terpisah
require_once ROOT_PATH . '/routes/web.php';
require_once ROOT_PATH . '/routes/api.php';

// // ================= DEBUGGING =================
// echo "Rute GET yang terdaftar: ";
// var_dump($router->getRoutes()['GET']);
// // ============= AKHIR DEBUGGING =============

// Mendapatkan URI mentah dari request dan metode HTTP
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

// --- Logika Pembersihan URI yang Disederhanakan untuk Produksi ---
// Logika Pembersihan URI (Mendukung Sub-folder XAMPP)
$scriptName = dirname($_SERVER['SCRIPT_NAME']);
$scriptName = str_replace('\\', '/', $scriptName); // Normalisasi slash untuk Windows


if (strpos($requestUri, $scriptName) === 0 && $scriptName !== '/') {
    $requestUri = substr($requestUri, strlen($scriptName));
}
$cleanUri = $requestUri ?: '/';

define('CURRENT_ROUTE_URI', $cleanUri); // Membuat URI bersih tersedia secara global

// Mencocokkan rute dan menjalankannya menggunakan URI yang sudah bersih
$router->dispatch($cleanUri, $method);
