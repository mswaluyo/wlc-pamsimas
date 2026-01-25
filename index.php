<?php
// Redirect traffic dari root folder ke folder public dengan mempertahankan path
$uri = $_SERVER['REQUEST_URI'];
$scriptDir = dirname($_SERVER['SCRIPT_NAME']);
$scriptDir = str_replace('\\', '/', $scriptDir); // Normalisasi slash untuk Windows

// Hapus trailing slash dari scriptDir jika ada
if ($scriptDir !== '/' && strlen($scriptDir) > 1) {
    $scriptDir = rtrim($scriptDir, '/');
}

// Ambil path relatif (misal: mengubah /wlc/detect menjadi /detect)
$relativePath = (strpos($uri, $scriptDir) === 0) ? substr($uri, strlen($scriptDir)) : $uri;

// Redirect ke folder public dengan path yang sesuai
header('Location: ' . $scriptDir . '/public' . $relativePath);
exit;