<?php

namespace app\Models;

use app\Models\Controller; // Pastikan untuk mengimpor Controller

class DetectedDevice {

    /**
     * Mendapatkan path absolut ke file penyimpanan JSON.
     * @return string
     */
    private static function getFilePath(): string {
        // Pastikan ROOT_PATH sudah didefinisikan di index.php Anda
        if (!defined('ROOT_PATH')) {
            // Fallback jika tidak ada, meskipun seharusnya ada
            define('ROOT_PATH', dirname(__DIR__, 2));
        }
        
        $storagePath = ROOT_PATH . '/storage';
        if (!is_dir($storagePath)) {
            mkdir($storagePath, 0775, true); // Buat folder 'storage' jika belum ada
        }
        
        return $storagePath . '/detected_macs.json';
    }

    /**
     * Memperbarui timestamp terakhir kali sebuah MAC address terlihat.
     * Ini adalah FUNGSI YANG HILANG.
     * @param string $macAddress
     */
    public static function updateLastSeen(string $macAddress) {
        $filePath = self::getFilePath();
        $devices = [];

        if (file_exists($filePath)) {
            $devices = json_decode(file_get_contents($filePath), true) ?? [];
        }

        // Perbarui timestamp untuk MAC address yang diberikan
        $devices[$macAddress] = time();

        // Tulis kembali ke file
        file_put_contents($filePath, json_encode($devices, JSON_PRETTY_PRINT));
    }

    /**
     * Mengambil MAC address yang aktif dan belum terdaftar.
     * @return array
     */
    public static function getActiveUnregistered(): array {
        $filePath = self::getFilePath();
        $activePeriod = 300; // Anggap aktif jika terlihat dalam 5 menit (300 detik)

        // 1. Baca dari file
        $detectedDevices = [];
        if (file_exists($filePath)) {
            $detectedDevices = json_decode(file_get_contents($filePath), true) ?? [];
        }

        // 2. Filter perangkat yang masih aktif
        $activeMacs = [];
        foreach ($detectedDevices as $mac => $timestamp) {
            if ((time() - $timestamp) < $activePeriod) {
                $activeMacs[] = $mac;
            }
        }

        if (empty($activeMacs)) {
            return [];
        }

        // 3. Ambil semua MAC yang sudah terdaftar di database
        $registeredMacs = Controller::getAllMacAddresses();

        // 4. Bandingkan untuk menemukan yang belum terdaftar
        return array_values(array_diff($activeMacs, $registeredMacs));
    }
}
