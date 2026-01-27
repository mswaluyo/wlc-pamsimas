<?php

/**
 * Fungsi helper untuk memuat file view.
 *
 * @param string $viewPath Path relatif ke folder app/Views (misal: 'dashboard/index').
 * @param array $data Data yang akan diteruskan ke view.
 * @param bool $useLayout Apakah akan menggunakan layout utama (main.php).
 * @return void
 */
function view($viewPath, $data = [], $useLayout = true) {
    // PERBAIKAN: Variabel ini harus bernama $content agar bisa dibaca oleh main.php
    $content = ROOT_PATH . '/app/Views/' . str_replace('.', '/', $viewPath) . '.php';

    if (file_exists($content)) {
        extract($data);

        if ($useLayout) {
            require_once ROOT_PATH . '/app/Views/layouts/main.php';
        } else {
            require_once $content;
        }
    } else {
        // Handle error: view file not found
        echo "Error: View file '{$content}' not found.";
    }
}

/**
 * Memeriksa apakah path yang diberikan cocok dengan URI saat ini atau merupakan bagian darinya.
 * Digunakan untuk menandai tautan navigasi sebagai 'aktif'.
 * @param string $path Path yang akan dibandingkan (misal: '/', '/controllers').
 * @param bool $exactMatch Jika true, harus cocok persis. Jika false, bisa cocok sebagian (misal: /controllers cocok dengan /controllers/1).
 * @return string 'active' jika cocok, string kosong jika tidak.
 */
function isActive(string $path, bool $exactMatch = true): string {
    if (!defined('CURRENT_ROUTE_URI')) return '';
    if ($exactMatch) return (CURRENT_ROUTE_URI === $path) ? 'active' : '';
    return (strpos(CURRENT_ROUTE_URI, $path) === 0 && (strlen(CURRENT_ROUTE_URI) === strlen($path) || substr(CURRENT_ROUTE_URI, strlen($path), 1) === '/')) ? 'active' : '';
}

/**
 * Menghasilkan URL lengkap yang benar (termasuk /public).
 * @param string $path Path relatif (misal: '/dashboard').
 * @return string URL lengkap (misal: 'http://localhost/wlc/public/dashboard').
 */
function base_url($path = '') {
    $url = defined('BASE_URL') ? BASE_URL : '';

    // Deteksi otomatis jika berjalan di HTTPS (termasuk Cloudflare/Proxy)
    $isHttps = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') 
            || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
            || (isset($_SERVER['REQUEST_SCHEME']) && $_SERVER['REQUEST_SCHEME'] === 'https');

    if ($isHttps && strpos($url, 'http://') === 0) {
        $url = str_replace('http://', 'https://', $url);
    }

    return rtrim($url, '/') . '/' . ltrim($path, '/');
}