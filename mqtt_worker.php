<?php
/**
 * MQTT Worker untuk WLC (Water Level Control)
 * Jalankan via CLI: php mqtt_worker.php
 */

require_once __DIR__ . '/vendor/autoload.php'; 
require_once __DIR__ . '/core/Database.php';
require_once __DIR__ . '/core/helpers.php';

// Load Models & Controllers manual
spl_autoload_register(function ($class) {
    $file = __DIR__ . '/' . str_replace('\\', '/', $class) . '.php';
    if (file_exists($file)) require_once $file;
});

use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\ConnectionSettings;
use app\Controllers\Api\TokenDeviceController;

// 1. Inisialisasi Database untuk memicu loadEnv() dari .env
$dbInstance = Database::getInstance();

// 2. Ambil konfigurasi MQTT dari Environment ($_ENV)
$server   = $_ENV['MQTT_HOST'] ?? '127.0.0.1';
$port     = (int)($_ENV['MQTT_PORT'] ?? 1883);
$mqtt_user = $_ENV['MQTT_USER'] ?? '';
$mqtt_pass = $_ENV['MQTT_PASS'] ?? '';
$clientId = 'wlc_server_worker_' . uniqid(); // Uniqid mencegah tabrakan session

// 3. Konfigurasi Koneksi MQTT
$connectionSettings = (new ConnectionSettings)
    ->setUsername($mqtt_user)
    ->setPassword($mqtt_pass)
    ->setKeepAliveInterval(60)
    ->setLastWillTopic('wlc/server/status')
    ->setLastWillMessage('offline')
    ->setLastWillQualityOfService(1);

$mqtt = new MqttClient($server, $port, $clientId);

// 4. Inisialisasi Controller
$apiController = new TokenDeviceController();

try {
    // Mencoba terhubung ke Broker
    $mqtt->connect($connectionSettings, true);
    echo "✅ MQTT Worker Terhubung ke $server:$port\n";
    echo "🚀 Listening for data on topic: wlc/+/up\n\n";

    // 5. Subscribe ke topik perangkat: wlc/{TOKEN}/up
    $mqtt->subscribe('wlc/+/up', function ($topic, $message) use ($apiController, $mqtt) {
        $timestamp = date('Y-m-d H:i:s');
        echo "[$timestamp] Data masuk di $topic\n";

        // Ekstrak Token dari Topik (wlc/{TOKEN}/up)
        $parts = explode('/', $topic);
        if (count($parts) !== 3) return;

        $token = $parts[1];
        $data = json_decode($message, true);

        if ($data) {
            // A. Simpan Log ke Database via Controller
            $apiController->processLog($token, $data);

            // B. Ambil data terbaru dari DB untuk dikirim balik ke perangkat
            $controller = \app\Models\Controller::findByToken($token);

            if ($controller) {
                // Tentukan min_run_time dari data pompa
                $minRunTime = 185;
                if (!empty($controller['pump_id'])) {
                    $pump = \app\Models\Pump::findById($controller['pump_id']);
                    if ($pump && isset($pump['delay_seconds'])) {
                        $minRunTime = (int)$pump['delay_seconds'];
                    }
                }

                // Susun payload balasan (sama dengan struktur API)
                $responsePayload = [
                    'status'                => $controller['status'],
                    'control_mode'          => $controller['control_mode'],
                    'mode_update_command'   => (int)($controller['mode_update_command'] ?? 0),
                    'config_update_command' => (int)($controller['config_update_command'] ?? 0),
                    'restart_command'       => (int)($controller['restart_command'] ?? 0),
                    'reset_logs_command'    => (int)($controller['reset_logs_command'] ?? 0),
                    'on_duration'           => (int)($controller['on_duration'] ?? 5),
                    'off_duration'          => (int)($controller['off_duration'] ?? 15),
                    'full_tank_distance'    => (int)($controller['full_tank_distance'] ?? 30),
                    'empty_tank_distance'   => (int)($controller['empty_tank_distance'] ?? 100),
                    'trigger_percentage'    => (int)($controller['trigger_percentage'] ?? 70),
                    'sensor_debounce'       => 5,
                    'min_run_time'          => $minRunTime
                ];

                // Publish balasan ke wlc/{TOKEN}/down
                $mqtt->publish("wlc/$token/down", json_encode($responsePayload), 0);
                echo "   ➡️ Balasan terkirim ke wlc/$token/down\n";
            }
        } else {
            echo "   ⚠️ Format JSON tidak valid.\n";
        }
    }, 0);

    // Menjaga skrip tetap berjalan (loop)
    $mqtt->loop(true);

} catch (Exception $e) {
    echo "❌ Terjadi Kesalahan: " . $e->getMessage() . "\n";
    exit(1);
}