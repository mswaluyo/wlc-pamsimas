<?php

namespace app\Controllers\Api;

use app\Models\Controller;
use app\Models\SensorLog;
use app\Models\PumpLog;
use app\Models\EventLog;
use app\Models\DetectedDevice;

class DeviceApiController {

    /**
     * Inisialisasi Controller
     */
    public function __construct() {
        $timezone = $_ENV['TIMEZONE'] ?? getenv('TIMEZONE') ?? 'Asia/Jakarta';
        date_default_timezone_set($timezone);
    }

    /**
     * Helper: Memvalidasi API Key dari header request.
     */
    private function validateApiKey() {
        // PERBAIKAN: Bypass validasi jika request berasal dari Administrator yang sudah login (Web Dashboard)
        if (isset($_SESSION['user']) && $_SESSION['user']['role'] === 'Administrator') {
            return;
        }

        $apiKey = $_SERVER['HTTP_X_API_KEY'] ?? null;
        // Ambil dari .env atau gunakan default yang sama dengan firmware
        $serverKey = $_ENV['DEVICE_API_KEY'] ?? $_SERVER['DEVICE_API_KEY'] ?? getenv('DEVICE_API_KEY') ?: 'P4mS1m4s-T1rt0-Arg0-2025'; 

        if (!$apiKey || $apiKey !== $serverKey) {
            http_response_code(401); // Unauthorized
            echo json_encode(['error' => 'Unauthorized: Invalid or missing API Key']);
            exit();
        }
    }

    /**
     * Menerima dan mencatat data sensor dari perangkat.
     * Ini adalah endpoint untuk HTTP POST dari sendSensorData().
     */
    public function log() {
        $this->validateApiKey(); // Validasi keamanan

        $json_data = file_get_contents('php://input');

        if (!$json_data) {
            http_response_code(400); // Bad Request
            echo json_encode(['error' => 'No data received']);
            return;
        }

        $data = json_decode($json_data, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            http_response_code(400); // Bad Request
            echo json_encode(['error' => 'Invalid JSON format']);
            return;
        }

        $mac_address = $data['mac_address'] ?? null;
        if (!$mac_address) {
            http_response_code(400);
            echo json_encode(['error' => 'MAC address is required']);
            return;
        }

        // Perbarui waktu terakhir terlihat untuk deteksi perangkat
        DetectedDevice::updateLastSeen($mac_address);

        // Cari controller_id berdasarkan MAC address
        $controller = Controller::findByMac($mac_address);
        if (!$controller) {
            http_response_code(404); // Not Found
            echo json_encode(['error' => 'Device not registered']);
            return;
        }

        try {
            // --- LOGIKA BARU: Cek durasi offline sebelum update ---
            $offlineThreshold = 60; // Ubah ke 1 menit (60 detik) agar lebih responsif
            $lastSeenTimestamp = strtotime($controller['last_update']);
            $currentTime = time();
    
            if (($currentTime - $lastSeenTimestamp) > $offlineThreshold) {
                $offlineDurationSeconds = $currentTime - $lastSeenTimestamp;
                $hours = floor($offlineDurationSeconds / 3600);
                $minutes = floor(($offlineDurationSeconds % 3600) / 60);
                $seconds = $offlineDurationSeconds % 60;
                $durationString = sprintf('%02d jam, %02d menit, %02d detik', $hours, $minutes, $seconds);
                EventLog::create($controller['id'], 'Device Reconnected', 'Perangkat kembali online setelah offline selama ' . $durationString);
            }
            // --- AKHIR LOGIKA BARU ---
    
            // Simpan log sensor
            SensorLog::create([
                'controller_id' => $controller['id'],
                'water_percentage' => $data['water_percentage'] ?? null,
                'water_level' => $data['water_level_cm'] ?? 0.0,
                'rssi' => $data['rssi'] ?? null,
                'record_time' => date('Y-m-d H:i:s')
            ]);
    
            // Perbarui RSSI dan last_update di tabel controller secara terpisah.
            Controller::update($controller['id'], [
                'rssi' => $data['rssi'] ?? null,
                'last_update' => date('Y-m-d H:i:s')
            ]);

            // --- UPDATE AGGREGATION TABLES ---
            $this->updateAggregatedLogs($controller['id'], $data['water_level_cm'] ?? 0.0, $data['water_percentage'] ?? null, $data['rssi'] ?? null);

            // --- AUTO CLEANUP: Hapus log pompa lama (> 30 hari) ---
            // Dijalankan dengan probabilitas 1% setiap kali ada data masuk untuk menghemat resource
            if (rand(1, 100) === 1) {
                PumpLog::cleanupOldLogs(30);
            }

        } catch (\PDOException $e) {
            http_response_code(500); // Internal Server Error
            echo json_encode(['error' => 'Database operation failed.']);
            return;
        }

        http_response_code(200); // OK
        echo json_encode(['status' => 'success']);
    }

    /**
     * Memberikan status dan konfigurasi lengkap ke perangkat.
     * Ini adalah endpoint untuk HTTP GET.
     */
    public function status() {
        $this->validateApiKey(); // Validasi keamanan

        $mac_address = $_GET['mac'] ?? null;
        if (!$mac_address) {
            http_response_code(400);
            echo json_encode(['error' => 'MAC address is required']);
            return;
        }

        DetectedDevice::updateLastSeen($mac_address);

        $controller = Controller::findByMac($mac_address);
        if (!$controller) {
            http_response_code(200); // Kirim 200 OK agar perangkat tahu statusnya
            echo json_encode(['status' => 'unregistered']);
            return;
        }

        // PERBAIKAN: Update 'last_update' setiap kali perangkat melakukan polling status (Heartbeat).
        // Ini memastikan perangkat tetap dianggap "Online" di dashboard meskipun interval kirim data sensornya lama.
        Controller::update($controller['id'], [
            'last_update' => date('Y-m-d H:i:s')
        ]);

        // --- LOGIKA BARU: Safety Cutoff (Watchdog) ---
        // Jika pompa ON di mode AUTO melebihi durasi yang ditentukan (+ toleransi), matikan paksa.
        // Ini mengatasi masalah "fase istirahat tidak berfungsi" jika perangkat gagal mematikan pompa sendiri.
        if ($controller['status'] === 'ON' && $controller['control_mode'] === 'AUTO') {
            $lastLogTime = PumpLog::getLastLogTime($controller['id']);
            if ($lastLogTime) {
                $onDurationMinutes = (int)($controller['on_duration'] ?? 5);
                $onDurationSeconds = $onDurationMinutes * 60;
                $startTime = strtotime($lastLogTime);
                $runTime = time() - $startTime;
                
                // Beri toleransi 5 menit atau 20% dari durasi, mana yang lebih besar
                // Toleransi ini penting agar tidak bentrok dengan timer internal perangkat
                $tolerance = max(300, $onDurationSeconds * 0.2);
                
                if ($runTime > ($onDurationSeconds + $tolerance)) {
                    // Force OFF di Database
                    Controller::update($controller['id'], ['status' => 'OFF']);
                    PumpLog::create($controller['id'], false); // Catat log OFF
                    EventLog::create($controller['id'], 'Safety Cutoff', "Pompa dimatikan paksa oleh server. Nyala: " . round($runTime/60) . "m, Batas: {$onDurationMinutes}m");
                    
                    // Update variabel lokal agar respon JSON ke perangkat menyuruh OFF
                    $controller['status'] = 'OFF';
                }
            }
        }

        // Bersihkan buffer output untuk mencegah karakter tambahan merusak JSON
        if (ob_get_level()) ob_clean();

        header('Content-Type: application/json');

        // --- LOGIKA BARU: Ambil Waktu Tunda (delay_seconds) dari tabel Pumps ---
        $minRunTime = 185; // Default fallback jika data pompa tidak ditemukan
        if (!empty($controller['pump_id'])) {
            $pump = \app\Models\Pump::findById($controller['pump_id']);
            if ($pump && isset($pump['delay_seconds'])) {
                $minRunTime = (int)$pump['delay_seconds'];
            }
        }

        // PERBAIKAN: Kirim hanya data yang relevan untuk status singkat dan lengkap.
        // Ini memastikan bendera perintah selalu disertakan.
        // PERBAIKAN KRUSIAL: Gunakan null coalescing operator (??) untuk memberikan nilai default 0 jika kunci tidak ada.
        // Ini akan mencegah error "Undefined array key".
        $responseData = [
            'status' => $controller['status'],
            'control_mode' => $controller['control_mode'],
            'mode_update_command' => (int)($controller['mode_update_command'] ?? 0),
            'config_update_command' => (int)($controller['config_update_command'] ?? 0),
            'restart_command' => (int)($controller['restart_command'] ?? 0),
            'reset_logs_command' => (int)($controller['reset_logs_command'] ?? 0),
            // Sertakan juga data konfigurasi lengkap, karena endpoint ini dipakai oleh fetchControlStatus juga.
            'on_duration' => (int)($controller['on_duration'] ?? 5),
            'off_duration' => (int)($controller['off_duration'] ?? 15),
            'full_tank_distance' => (int)($controller['full_tank_distance'] ?? 30),
            'empty_tank_distance' => (int)($controller['empty_tank_distance'] ?? 100),
            'trigger_percentage' => (int)($controller['trigger_percentage'] ?? 70),
            'sensor_debounce' => 5, // Default 5 detik untuk menahan gelombang air
            'min_run_time' => $minRunTime // Menggunakan nilai dari database (tabel pumps)
        ];

        echo json_encode($responseData);
    }

    /**
     * Menerima perintah dari perangkat (misalnya, mengubah mode, status pompa).
     * Ini adalah endpoint untuk HTTP POST dari sendControlCommand().
     */
    public function update() {
        $this->validateApiKey(); // Validasi keamanan

        $json_data = file_get_contents('php://input');
        if (!$json_data) {
            http_response_code(400);
            return;
        }
        $data = json_decode($json_data, true);

        $mac_address = $data['mac'] ?? null;
        $action = $data['action'] ?? null;
        $value = $data['value'] ?? null;

        if (!$mac_address || !$action) {
            http_response_code(400);
            return;
        }

        $controller = Controller::findByMac($mac_address);
        if (!$controller) {
            http_response_code(404);
            return;
        }

        // PERBAIKAN: Selalu perbarui last_update setiap kali perangkat melapor (via /api/update)
        // Ini mencegah status berkedip jadi "Offline" saat perangkat sibuk memproses perintah.
        $updateData = ['last_update' => date('Y-m-d H:i:s')];

        switch ($action) {
            case 'set_mode':
                $updateData['control_mode'] = $value;
                // PERBAIKAN: Tambahkan bendera perintah agar perangkat tahu ada pembaruan mode.
                $updateData['mode_update_command'] = 1;
                EventLog::create($controller['id'], 'Mode Change', 'Mode diubah menjadi ' . $value);
                break;
            case 'set_status':
                $updateData['status'] = $value;
                // PERBAIKAN: Hanya catat log jika status benar-benar berubah untuk mencegah spam log
                // yang menyebabkan timer durasi di dashboard ter-reset terus menerus (0, 1, 0...).
                if ($controller['status'] !== $value) {
                    PumpLog::create($controller['id'], ($value === 'ON'));
                }
                break;
            case 'report_version':
                $updateData['firmware_version'] = $value;
                break;
            case 'report_event':
                // Hitung durasi offline (baik karena mati listrik maupun hilang sinyal)
                $powerLossThreshold = 60; // Anggap offline signifikan jika lebih dari 1 menit
                $lastSeenTimestamp = strtotime($controller['last_update']);
                $currentTime = time();
                $offlineDuration = $currentTime - $lastSeenTimestamp;
                
                $durationString = '';
                if ($offlineDuration > $powerLossThreshold) {
                    $hours = floor($offlineDuration / 3600);
                    $minutes = floor(($offlineDuration % 3600) / 60);
                    $seconds = $offlineDuration % 60;
                    $durationString = sprintf('%02d jam, %02d menit, %02d detik', $hours, $minutes, $seconds);
                }

                if ($value === 'boot') {
                    if ($offlineDuration > $powerLossThreshold) {
                        EventLog::create($controller['id'], 'Power On', 'Perangkat menyala kembali setelah mati/offline selama ' . $durationString);
                    } else {
                        EventLog::create($controller['id'], 'Power On', 'Perangkat menyala kembali (Restart cepat).');
                    }

                    // PERBAIKAN: Otomatis reset flag restart_command saat perangkat melapor baru menyala (boot).
                    // Ini mencegah bootloop jika perangkat gagal mengirim konfirmasi 'reset_restart' sebelum reboot.
                    $updateData['restart_command'] = 0;
                } elseif ($value === 'network_recovered') {
                    if ($offlineDuration > $powerLossThreshold) {
                        EventLog::create($controller['id'], 'Connection Recovered', 'Koneksi internet pulih setelah terputus selama ' . $durationString);
                    } else {
                        EventLog::create($controller['id'], 'Connection Recovered', 'Koneksi internet pulih (Gangguan sesaat).');
                    }
                } else {
                    EventLog::create($controller['id'], 'Device Event', 'Laporan: ' . $value);
                }
                break;
            case 'reset_restart':
                $updateData['restart_command'] = 0;
                break;
            case 'reset_logs_completed':
                $updateData['reset_logs_command'] = 0;
                EventLog::create($controller['id'], 'System', 'Log offline berhasil dihapus dari perangkat (Remote Clear).');
                break;
            case 'reset_config_update':
                $updateData['config_update_command'] = 0;
                break;
        }

        // Jalankan update (minimal akan mengupdate last_update)
        Controller::update($controller['id'], $updateData);

        // PERBAIKAN: Setelah perangkat mengambil perintah mode, reset benderanya.
        if ($action === 'reset_mode_update') {
            Controller::update($controller['id'], ['mode_update_command' => 0]);
        }

        http_response_code(200);
        echo json_encode(['status' => 'success']);
    }

    /**
     * Menerima data log yang disimpan saat perangkat offline.
     */
    public function logOffline() {
        $this->validateApiKey(); // Validasi keamanan

        $json_data = file_get_contents('php://input');
        $data = json_decode($json_data, true);
        
        // Validasi JSON
        if (json_last_error() !== JSON_ERROR_NONE) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid JSON: ' . json_last_error_msg()]);
            return;
        }

        $mac_address = $data['mac_address'] ?? null;
        if (!$mac_address) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing MAC address']);
            return;
        }

        $controller = Controller::findByMac($mac_address);
        if (!$controller) {
            http_response_code(404);
            echo json_encode(['error' => 'Device not found']);
            return;
        }

        $controller_id = $controller['id'];

        if (isset($data['sensor_logs']) && is_array($data['sensor_logs'])) {
            foreach ($data['sensor_logs'] as $log) {
                // Format baru: [timestamp, percentage, cm, rssi]
                if (count($log) === 4) {
                    // PERBAIKAN: Tambahkan kembali logika yang hilang untuk menyimpan log sensor offline.
                    SensorLog::create([
                        'controller_id' => $controller_id, 
                        'water_percentage' => $log[1], 
                        'water_level' => $log[2], // PERBAIKAN: Sesuaikan dengan nama kolom di DB
                        'rssi' => $log[3], 
                        // PERBAIKAN: Jika timestamp adalah 0, gunakan waktu server saat ini.
                        'record_time' => ($log[0] == 0) ? date('Y-m-d H:i:s') : date('Y-m-d H:i:s', $log[0])
                    ]);

                    // Update Aggregation Tables untuk log offline juga
                    $recordTime = ($log[0] == 0) ? date('Y-m-d H:i:s') : date('Y-m-d H:i:s', $log[0]);
                    $this->updateAggregatedLogs($controller_id, $log[2], $log[1], $log[3], $recordTime);
                }
            }
        }
        if (isset($data['pump_logs']) && is_array($data['pump_logs'])) {
            foreach ($data['pump_logs'] as $log) {
                // Format: [timestamp, status (0 atau 1)]
                if (count($log) >= 2) {
                    // PERBAIKAN: Jika timestamp adalah 0, gunakan waktu server saat ini.
                    $timestamp = ($log[0] == 0) ? time() : $log[0];
                    PumpLog::createWithTimestamp($controller_id, $timestamp, (bool)$log[1]);
                }
            }
        }
        if (isset($data['event_logs']) && is_array($data['event_logs'])) {
            foreach ($data['event_logs'] as $log) {
                // Format: ["timestamp,event_name"]
                $parts = explode(',', $log[0], 2); // Batasi pemisahan menjadi 2 bagian
                if (count($parts) == 2) {
                    // PERBAIKAN: Jika timestamp adalah 0, gunakan waktu server saat ini.
                    $timestamp = ($parts[0] == 0) ? time() : $parts[0];
                    EventLog::createWithTimestamp($controller_id, $timestamp, 'Offline Event', 'Laporan dari perangkat: ' . trim($parts[1]));
                }
            }
        }

        // PERBAIKAN: Setelah memproses semua log, perbarui status RSSI utama
        // dengan nilai terakhir dari log sensor offline.
        if (isset($data['sensor_logs']) && !empty($data['sensor_logs'])) {
            $lastSensorLog = end($data['sensor_logs']);
            $lastRssi = $lastSensorLog[3] ?? null; // PERBAIKAN: Ambil nilai RSSI dari elemen ke-4 (indeks 3)
            Controller::update($controller_id, [
                'rssi' => $lastRssi,
                'last_update' => date('Y-m-d H:i:s') // PERBAIKAN: Perbarui juga timestamp
            ]);
        }

        // PERBAIKAN: Catat event bahwa log offline berhasil diterima dan diproses
        if ((isset($data['sensor_logs']) && !empty($data['sensor_logs'])) || 
            (isset($data['pump_logs']) && !empty($data['pump_logs'])) || 
            (isset($data['event_logs']) && !empty($data['event_logs']))) {
            EventLog::create($controller_id, 'Offline Logs', 'Server berhasil menerima dan memproses data offline.');
        }

        http_response_code(200);
        echo json_encode(['status' => 'offline logs processed']);
    }

    /**
     * Mengirim perintah reboot ke perangkat.
     * Ini adalah fungsi helper, bukan endpoint API.
     */
    public static function sendRebootCommand(string $macAddress) {
        $controller = Controller::findByMac($macAddress);
        if ($controller) {
            Controller::update($controller['id'], ['restart_command' => 1]);
            sleep(3); // Beri jeda agar perangkat sempat mengambil perintah sebelum dihapus
        }
    }

    /**
     * Endpoint untuk mendeteksi perangkat di jaringan yang belum terdaftar.
     */
    public function getDetectedDevices() {
        $activeMacs = DetectedDevice::getActiveUnregistered();
        header('Content-Type: application/json');
        echo json_encode($activeMacs);
    }

    /**
     * Menyediakan data lengkap untuk live update dashboard.
     */
    public function getDashboardData() {
        // PERBAIKAN: Pastikan hanya user yang sudah login yang bisa mengambil data dashboard
        if (!isset($_SESSION['user'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }

        // Logika ini diambil dari DashboardController
        $controllers = \app\Models\Controller::getAll();
        $tanks = \app\Models\Tank::getAll();
        $users = \app\Models\User::getAll();

        $totalControllers = count($controllers);
        $onlineControllers = 0;
        
        // Gunakan referensi (&) agar kita bisa menambahkan flag is_online ke array asli
        foreach ($controllers as &$controller) {
            // PERBAIKAN: Tingkatkan toleransi menjadi 120 detik (2 menit) untuk mencegah flicker status
            $isOnline = (strtotime($controller['last_update']) > (time() - 120));
            $controller['is_online'] = $isOnline; // Kirim status ini ke frontend

            // Hitung durasi status pompa saat ini (detik)
            $lastLogTime = \app\Models\PumpLog::getLastLogTime($controller['id']);
            
            // Fix Timezone: Pastikan parsing waktu sesuai konfigurasi .env agar durasi akurat
            $ts = 0;
            if ($lastLogTime) {
                try {
                    $timezone = $_ENV['TIMEZONE'] ?? 'Asia/Jakarta';
                    $dt = new \DateTime($lastLogTime, new \DateTimeZone($timezone));
                    $ts = $dt->getTimestamp();
                } catch (\Exception $e) {
                    $ts = strtotime($lastLogTime);
                }
            }
            
            $controller['last_pump_change_timestamp'] = $ts; // Kirim timestamp absolut
            
            $duration = $ts ? (time() - $ts) : 0;
            $controller['current_pump_duration'] = max(0, $duration); // Cegah nilai negatif
            
            if ($isOnline) {
                $onlineControllers++;
            }
        }
        unset($controller); // Hapus referensi

        $data = [
            'server_time' => date('d M Y, H:i:s'), // Kirim waktu server saat ini
            'server_timestamp' => time(), // Kirim timestamp server untuk sinkronisasi
            'stats' => [
                'total_controllers' => $totalControllers,
                'online_controllers' => $onlineControllers,
                'total_tanks' => count($tanks),
                'total_users' => count($users)
            ],
            'controllers' => $controllers,
            'indicator_settings' => \app\Models\IndicatorSetting::getSettings()
        ];

        header('Content-Type: application/json');
        echo json_encode($data);
    }

    /**
     * Menyediakan HTML dan CSS lengkap untuk preview template.
     */
    public function getTemplatePreview($id) {
        $template = \app\Models\GaugeTemplate::findById((int)$id);
        if (!$template) {
            http_response_code(404);
            echo "Template tidak ditemukan.";
            exit();
        }

        $html_content = $template['html_code'] ?? '';
        $css_content = $template['css_code'] ?? '';
        $js_content = $template['js_code'] ?? '';

        $clean_html = '';
        if (!empty($html_content)) {
            // Pastikan HTML didecode jika tersimpan sebagai entitas di database
            $html_content = html_entity_decode($html_content);

            // Ekstrak hanya konten di dalam <body> untuk menghindari konflik
            $doc = new \DOMDocument();
            @$doc->loadHTML('<?xml encoding="utf-8" ?>' . $html_content);
            $body = $doc->getElementsByTagName('body')->item(0);
            if ($body) {
                foreach ($body->childNodes as $child) {
                    $clean_html .= $doc->saveHTML($child);
                }
            } else { $clean_html = $html_content; } // Fallback
        }

        if (empty($clean_html)) {
            http_response_code(404);
            echo "Konten HTML template tidak valid atau kosong.";
            exit();
        }

        // Ganti placeholder dengan nilai dummy
        $final_html = str_replace(
            ['{{CONTROLLER_ID}}', '{{TANK_NAME}}'],
            ['preview', ''],
            $clean_html
        );
        
        // Gabungkan menjadi satu dokumen HTML lengkap untuk ditampilkan di iframe
        header('Content-Type: text/html; charset=utf-8');
        echo "<!DOCTYPE html><html><head><style>{$css_content}</style></head><body>{$final_html}<script src=\"https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js\"></script><script src=\"https://cdn3.devexpress.com/jslib/17.1.6/js/dx.all.js\"></script><script>{$js_content}</script></body></html>";
    }

    /**
     * Mengambil riwayat data sensor untuk grafik.
     * Parameter GET: id (controller_id), range (menit)
     */
    public function getSensorHistory() {
        // PERBAIKAN: Endpoint ini hanya untuk browser, jadi hanya periksa session.
        // Jangan validasi API Key karena browser tidak mengirimkannya.
        if (!isset($_SESSION['user'])) {
            http_response_code(401); // Unauthorized
            echo json_encode(['error' => 'Unauthorized: Login required.']);
            return;
        }

        $controller_id = $_GET['id'] ?? null;
        $range_minutes = (int)($_GET['range'] ?? 60); // Default 1 jam

        if (!$controller_id) {
            http_response_code(400);
            echo json_encode(['error' => 'Controller ID required']);
            return;
        }

        try {
            $pdo = \Database::getInstance()->getConnection();
            
            // PERBAIKAN: Hitung waktu mulai menggunakan PHP untuk menghindari masalah Timezone MySQL vs PHP
            $startTime = date('Y-m-d H:i:s', strtotime("-{$range_minutes} minutes"));

            // --- LOGIKA ADAPTIF: Raw vs Aggregated ---
            // Jika menggunakan tabel agregasi (minute/hourly), kita harus menghitung persentase
            // secara manual karena tabel tersebut tidak memiliki kolom 'water_percentage'.
            
            // PERBAIKAN: Gunakan data agregasi untuk rentang > 10 menit (misal 30m, 1 jam) agar loading cepat.
            // Live (5 menit) tetap menggunakan data RAW agar halus.
            if ($range_minutes > 10) {
                // --- MODE AGREGASI (Data Lama) ---
                // 1. Tentukan tabel dan kolom waktu
                if ($range_minutes >= 1440) {
                    $tableName = 'hourly_sensor_logs';
                    $timeCol = 'hour_timestamp';
                } else {
                    $tableName = 'minute_sensor_logs';
                    $timeCol = 'minute_timestamp';
                }

                // 2. Ambil konfigurasi tangki untuk perhitungan persentase
                $controller = Controller::findById($controller_id);
                $emptyDist = (float)($controller['empty_tank_distance'] ?? 100);
                $fullDist = (float)($controller['full_tank_distance'] ?? 30);

                // 3. Query data (Hanya ambil rata-rata level air dalam cm)
                $sql = "SELECT {$timeCol} as record_time, avg_water_level as water_level 
                        FROM {$tableName} 
                        WHERE controller_id = :id 
                        AND {$timeCol} >= :start_time 
                        ORDER BY {$timeCol} ASC LIMIT 5000";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute([':id' => $controller_id, ':start_time' => $startTime]);
                $rawData = $stmt->fetchAll(\PDO::FETCH_ASSOC);

                // 4. Hitung persentase di PHP
                $sensorData = [];
                foreach ($rawData as $row) {
                    $levelCm = (float)$row['water_level'];
                    // Rumus: ((Kosong - Sekarang) / (Kosong - Penuh)) * 100
                    $pct = 0;
                    if ($emptyDist != $fullDist) {
                        $pct = 100 * ($emptyDist - $levelCm) / ($emptyDist - $fullDist);
                        $pct = max(0, min(100, round($pct))); // Batasi 0-100
                    }
                    
                    $sensorData[] = [
                        'record_time' => $row['record_time'],
                        'water_level' => $levelCm,
                        'water_percentage' => $pct
                    ];
                }
            } else {
                // --- MODE RAW (Live / < 1 Jam) ---
                // Tabel sensor_logs memiliki kolom water_percentage, jadi langsung ambil saja.
                $sql = "SELECT record_time, water_level, water_percentage 
                        FROM sensor_logs 
                        WHERE controller_id = :id 
                        AND record_time >= :start_time 
                        ORDER BY record_time ASC LIMIT 5000";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute([':id' => $controller_id, ':start_time' => $startTime]);
                $sensorData = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            }

            // Ambil data status pompa dengan penanganan error (Try-Catch)
            $pumpData = [];
            try {
                // PERBAIKAN: Gunakan nama kolom yang benar (timestamp, pump_status)
                // Alias ke record_time dan status agar kompatibel dengan JavaScript
                $sqlPump = "SELECT timestamp as record_time, pump_status as status, duration_seconds 
                            FROM pump_logs 
                            WHERE controller_id = :id 
                            AND timestamp >= :start_time 
                            ORDER BY timestamp ASC";
                            
                $stmtPump = $pdo->prepare($sqlPump);
                $stmtPump->execute([':id' => $controller_id, ':start_time' => $startTime]);
                $pumpData = $stmtPump->fetchAll();
            } catch (\Exception $e) {
                // Jika gagal, biarkan array kosong agar grafik utama tetap jalan
                $pumpData = [];
            }

            header('Content-Type: application/json');
            echo json_encode(['sensors' => $sensorData, 'pumps' => $pumpData]);
        } catch (\Exception $e) {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Server Error: ' . $e->getMessage()]);
        }
    }

    /**
     * Endpoint untuk mengambil event log terbaru untuk terminal monitoring.
     */
    public function getTerminalEvents() {
        // Hanya izinkan user login (session) untuk keamanan
        if (!isset($_SESSION['user'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }

        $limit = $_GET['limit'] ?? 50;
        // Menggunakan model EventLog yang sudah di-use di atas
        $events = EventLog::getPaginatedLogs((int)$limit, 0);
        
        header('Content-Type: application/json');
        echo json_encode($events);
    }

    /**
     * Helper: Memperbarui tabel agregasi (minute, hourly, daily) dengan teknik "Last Value".
     * Ini memastikan tabel agregasi selalu terisi tanpa perlu cron job.
     */
    private function updateAggregatedLogs($controllerId, $waterLevel, $waterPercentage, $rssi, $recordTime = null) {
        $pdo = \Database::getInstance()->getConnection();
        $recordTime = $recordTime ?? date('Y-m-d H:i:s');
        $timestamp = strtotime($recordTime);

        // PERBAIKAN: Sesuaikan query dengan struktur tabel database yang ada (wlc_db.sql)
        // Tabel agregasi hanya memiliki kolom: id, controller_id, avg_water_level, timestamp
        // Kita TIDAK menyimpan water_percentage atau rssi di sini.

        // 1. Minute Log
        $minuteTime = date('Y-m-d H:i:00', $timestamp);
        $sql = "INSERT INTO minute_sensor_logs (controller_id, minute_timestamp, avg_water_level) 
                VALUES (?, ?, ?) 
                ON DUPLICATE KEY UPDATE avg_water_level = VALUES(avg_water_level)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$controllerId, $minuteTime, $waterLevel]);

        // 2. Hourly Log
        $hourTime = date('Y-m-d H:00:00', $timestamp);
        $sql = "INSERT INTO hourly_sensor_logs (controller_id, hour_timestamp, avg_water_level) 
                VALUES (?, ?, ?) 
                ON DUPLICATE KEY UPDATE avg_water_level = VALUES(avg_water_level)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$controllerId, $hourTime, $waterLevel]);

        // 3. Daily Log (Kolom day_timestamp bertipe DATE)
        $dayTime = date('Y-m-d', $timestamp);
        $sql = "INSERT INTO daily_sensor_logs (controller_id, day_timestamp, avg_water_level) 
                VALUES (?, ?, ?) 
                ON DUPLICATE KEY UPDATE avg_water_level = VALUES(avg_water_level)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$controllerId, $dayTime, $waterLevel]);
    }
}
