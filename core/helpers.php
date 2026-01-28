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
    $url = '';

    // 1. Cek konstanta BASE_URL (jika didefinisikan manual di index.php)
    if (defined('BASE_URL')) {
        $url = BASE_URL;
    }
    // 2. Cek Environment Variable APP_URL (dari file .env) - Prioritas Utama
    elseif (($envUrl = getenv('APP_URL')) !== false && !empty($envUrl)) {
        $url = $envUrl;
    } elseif (isset($_ENV['APP_URL']) && !empty($_ENV['APP_URL'])) {
        $url = $_ENV['APP_URL'];
    } elseif (isset($_SERVER['APP_URL']) && !empty($_SERVER['APP_URL'])) {
        $url = $_SERVER['APP_URL'];
    }
    // 3. Deteksi Otomatis (Fallback jika .env belum dimuat)
    else {
        $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";
        
        // Dukungan untuk Reverse Proxy / Cloudflare
        if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
            $protocol = 'https';
        }

        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '');
        $scriptDir = str_replace('\\', '/', $scriptDir); // Normalisasi slash Windows
        
        if ($scriptDir !== '/') {
            $scriptDir = rtrim($scriptDir, '/');
        }

        $url = $protocol . "://" . $host . $scriptDir;
    }

    return rtrim($url, '/') . '/' . ltrim($path, '/');
}