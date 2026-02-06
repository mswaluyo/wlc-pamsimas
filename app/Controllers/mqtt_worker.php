<?php
/**
 * MQTT Worker untuk WLC
 * Jalankan via CLI: php mqtt_worker.php
 * Membutuhkan library: composer require php-mqtt/client
 */

require_once __DIR__ . '/vendor/autoload.php'; // Pastikan composer autoload ada
require_once __DIR__ . '/core/Database.php';
require_once __DIR__ . '/core/helpers.php';

// Load Models & Controllers manual karena tidak lewat index.php
spl_autoload_register(function ($class) {
    $file = __DIR__ . '/' . str_replace('\\', '/', $class) . '.php';
    if (file_exists($file)) require_once $file;
});

use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\ConnectionSettings;
use app\Controllers\Api\TokenDeviceController;

$server   = 'localhost'; // Ganti dengan IP Broker MQTT (misal Mosquitto)
$port     = 1883;
$clientId = 'wlc_server_worker';

$mqtt = new MqttClient($server, $port, $clientId);

// Koneksi ke Database & Controller
$apiController = new TokenDeviceController();

$mqtt->connect();
echo "MQTT Worker Connected. Listening for data...\n";

// Subscribe ke semua topik perangkat: wlc/{TOKEN}/up
$mqtt->subscribe('wlc/+/up', function ($topic, $message) use ($apiController) {
    echo "Received on $topic: $message\n";
    
    // Ekstrak Token dari Topik
    // Format: wlc/{TOKEN}/up
    $parts = explode('/', $topic);
    if (count($parts) !== 3) return;
    
    $token = $parts[1];
    $data = json_decode($message, true);
    
    if ($data) {
        // Proses data menggunakan logika yang sama dengan HTTP
        $apiController->processLog($token, $data);
    }
}, 0);

$mqtt->loop(true);