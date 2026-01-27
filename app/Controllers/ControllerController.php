<?php

namespace app\Controllers;

use app\Models\Controller;
use app\Models\EventLog; // Asumsi Anda akan membuat model ini
use app\Models\DetectedDevice; // Kita akan gunakan model ini lagi
use app\Models\Tank;
use app\Models\Pump;
use app\Models\Sensor;

class ControllerController {

    public function __construct() {
        if (!isset($_SESSION['user'])) {
            header('Location: ' . base_url('/login'));
            exit();
        }
    }

    /**
     * Menampilkan daftar semua perangkat kontroler.
     */
    public function index() {

        $controllers = Controller::getAll();

        // Logika Filter: Jika ada parameter ?status=online di URL
        if (isset($_GET['status']) && $_GET['status'] === 'online') {
            $controllers = array_filter($controllers, function($device) {
                // Anggap online jika update terakhir kurang dari 1 menit (60 detik) yang lalu
                return strtotime($device['last_update']) > (time() - 60);
            });
        }

        $data = [
            'title'       => isset($_GET['status']) && $_GET['status'] === 'online' ? 'Perangkat Online' : 'Pengaturan Perangkat',
            'controllers' => $controllers,
            // Data tambahan untuk form modal
            'tanks'       => Tank::getAll(),
            'pumps'       => Pump::getAll(),
            'sensors'     => Sensor::getAll(),
            'active_devices' => $this->getUnregisteredActiveDevices() // Data untuk deteksi otomatis
        ];

        view('controllers/index', $data);
    }

    public function show($id) {
        $controller = Controller::findById($id);

        if (!$controller) {
            // Handle 404 Not Found jika controller tidak ditemukan
            http_response_code(404);
            echo "Controller tidak ditemukan.";
            return;
        }

        // Ambil log peristiwa terbaru
        $pdo = \Database::getInstance()->getConnection();
        $sql = "SELECT * FROM event_logs WHERE controller_id = :id ORDER BY event_time DESC LIMIT 50";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        $eventLogs = $stmt->fetchAll();

        // --- LOGIKA BARU: Ambil template gauge yang aktif ---
        $indicatorSettings = \app\Models\IndicatorSetting::getSettings();
        $activeTemplateData = null;

        if ($indicatorSettings && isset($indicatorSettings['active_template_id'])) {
            $activeTemplate = \app\Models\GaugeTemplate::findById($indicatorSettings['active_template_id']);
            if ($activeTemplate) {
                $html_content = $activeTemplate['html_code'] ?? '';
                $clean_html = '';

                if (!empty($html_content)) {
                    // Pastikan HTML didecode jika tersimpan sebagai entitas di database
                    $html_content = html_entity_decode($html_content);

                    $doc = new \DOMDocument();
                    @$doc->loadHTML('<?xml encoding="utf-8" ?>' . $html_content);
                    $body = $doc->getElementsByTagName('body')->item(0);
                    if ($body) {
                        foreach ($body->childNodes as $child) {
                            $clean_html .= $doc->saveHTML($child);
                        }
                    } else { $clean_html = $html_content; }
                }

                $activeTemplateData = [
                    'id' => $activeTemplate['id'],
                    'html' => $clean_html,
                    'css' => $activeTemplate['css_code'] ?? '',
                    'js' => $activeTemplate['js_code'] ?? ''
                ];
            }
        }

        $data = [
            'title' => 'Detail Kontroler: ' . ($controller['tank_name'] ?? $controller['mac_address']),
            'controller' => $controller,
            'eventLogs' => $eventLogs,
            'active_template' => $activeTemplateData // Kirim data template ke view
        ];

        view('controllers/show', $data);
    }

    /**
     * Menampilkan form untuk mendaftarkan perangkat baru.
     */
    public function showRegistrationForm() {
        // Pastikan hanya admin yang bisa mengakses
        if ($_SESSION['user']['role'] !== 'Administrator') {
            http_response_code(403);
            echo "<h1>403 Forbidden</h1><p>Anda tidak memiliki hak akses ke halaman ini.</p>";
            exit();
        }

        $mac_address = $_GET['mac'] ?? '';

        $data = [
            'title' => 'Daftarkan Perangkat Baru',
            'mac_address' => $mac_address,
            'tanks' => Tank::getAll(),
            'pumps' => Pump::getAll(),
            'sensors' => Sensor::getAll()
        ];
        view('controllers/register', $data);
    }

    /**
     * Menyimpan data pendaftaran perangkat baru.
     */
    public function storeRegistration() {
        // Pastikan hanya admin yang bisa mengakses
        if ($_SESSION['user']['role'] !== 'Administrator') {
            http_response_code(403);
            echo "<h1>403 Forbidden</h1><p>Anda tidak memiliki hak akses ke halaman ini.</p>";
            exit();
        }

        $mac_address = $_POST['mac_address'] ?? '';
        $tank_id = $_POST['tank_id'] ?? null;
        $pump_id = $_POST['pump_id'] ?? null;
        $sensor_id = $_POST['sensor_id'] ?? null;

        // Validasi dasar
        if (empty($mac_address) || empty($tank_id) || empty($pump_id) || empty($sensor_id)) {
            // Redirect kembali jika ada data yang kurang
            header('Location: ' . base_url('/controllers/register?mac=' . urlencode($mac_address)));
            exit();
        }

        // Ambil pengaturan dari sensor yang dipilih untuk disalin ke controller
        $sensor = Sensor::findById((int)$sensor_id);
        if (!$sensor) {
            // Perbaikan: Arahkan kembali ke halaman daftar controller jika sensor tidak valid.
            header('Location: ' . base_url('/controllers'));
            exit();
        }

        // Ambil data tangki yang dipilih untuk mendapatkan tinggi tangki
        $tank = Tank::findById((int)$tank_id);
        if (!$tank) {
            // Handle error jika tangki tidak ditemukan
            header('Location: ' . base_url('/controllers/register?mac=' . urlencode($mac_address)));
            exit();
        }

        // Ambil data pompa yang dipilih untuk mendapatkan durasi default
        $pump = Pump::findById((int)$pump_id);
        if (!$pump) {
            // Handle error jika pompa tidak ditemukan
            header('Location: ' . base_url('/controllers/register?mac=' . urlencode($mac_address)));
            exit();
        }

        $data = [
            'mac_address' => $mac_address,
            'tank_id' => (int)$tank_id,
            'pump_id' => (int)$pump_id,
            'sensor_id' => (int)$sensor_id,
            'full_tank_distance' => (int)$sensor['full_tank_distance'],
            'empty_tank_distance' => (int)$tank['height'], // Perbaikan: Nama parameter sudah benar, pastikan tipe data integer
            'trigger_percentage' => $sensor['trigger_percentage'],
            'on_duration' => (int)($pump['on_duration_seconds'] / 60),
            'off_duration' => (int)($pump['off_duration_seconds'] / 60),
            // Tambahkan perintah restart otomatis setelah pendaftaran
            'restart_command' => 1
        ];

        Controller::create($data);

        // Set flash message untuk notifikasi sukses
        $_SESSION['success_message'] = "Perangkat dengan MAC address " . htmlspecialchars($mac_address) . " berhasil didaftarkan.";

        // Redirect ke halaman daftar perangkat
        header('Location: ' . base_url('/controllers'));
        exit();
    }

    /**
     * Menghapus perangkat dan mengirim perintah reboot.
     */
    public function delete($id) {
        if ($_SESSION['user']['role'] !== 'Administrator') {
            http_response_code(403);
            echo "<h1>403 Forbidden</h1><p>Anda tidak memiliki hak akses untuk tindakan ini.</p>";
            exit();
        }

        $controller = Controller::findById((int)$id);

        if ($controller) {
            // Hapus perangkat dari database. Ini adalah tindakan utama.
            $isDeleted = Controller::delete((int)$id);
            if ($isDeleted) {
                $_SESSION['success_message'] = "Perangkat dengan MAC " . htmlspecialchars($controller['mac_address']) . " telah berhasil dihapus dari sistem.";
            } else {
                $_SESSION['error_message'] = "Terjadi kesalahan saat mencoba menghapus perangkat dari database.";
            }
        } else {
            $_SESSION['error_message'] = "Gagal menghapus: Perangkat tidak ditemukan.";
        }

        header('Location: ' . base_url('/controllers'));
        exit();
    }

    /**
     * Helper untuk mengambil perangkat aktif yang belum terdaftar dari file.
     * Logika ini dipindahkan dari DeviceApiController agar bisa dipakai di sini.
     */
    private function getUnregisteredActiveDevices(): array {
        $filePath = ROOT_PATH . '/storage/detected_macs.json';
        $activePeriod = 60; // Ubah ke 60 detik agar deteksi lebih real-time

        // 1. Baca dari file sementara
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

    /**
     * Memicu perintah ke perangkat untuk mengambil konfigurasi baru.
     */
    public function applySettings($id) {
        if ($_SESSION['user']['role'] !== 'Administrator') {
            http_response_code(403);
            exit("Akses ditolak.");
        }

        $controller = Controller::findById((int)$id);
        if ($controller) {
            // Set flag di database
            Controller::update((int)$id, ['config_update_command' => 1]);
            $_SESSION['success_message'] = "Perintah 'Terapkan Pengaturan' telah dikirim ke perangkat " . htmlspecialchars($controller['tank_name'] ?? $controller['mac_address']) . ".";
        } else {
            $_SESSION['error_message'] = "Perangkat tidak ditemukan.";
        }
        header('Location: ' . base_url('/controllers'));
        exit();
    }

    /**
     * PERBAIKAN: Menyinkronkan data operasional controller dengan data master
     * dari tabel tanks, pumps, dan sensors.
     */
    public function syncWithMasterData($id) {
        if ($_SESSION['user']['role'] !== 'Administrator') {
            http_response_code(403);
            exit("Akses ditolak.");
        }

        // PERBAIKAN: Ambil data controller menggunakan query langsung untuk memastikan semua kolom (termasuk foreign keys) terambil.
        // Ini untuk mengatasi masalah di mana Model::findById() mungkin tidak mengambil semua kolom.
        $pdo = \Database::getInstance()->getConnection();
        $stmt = $pdo->prepare("SELECT * FROM controllers WHERE id = ?");
        $stmt->execute([(int)$id]);
        $controller = $stmt->fetch();

        if (!$controller) {
            $_SESSION['error_message'] = "Gagal sinkronisasi: Perangkat tidak ditemukan.";
            header('Location: ' . base_url('/controllers'));
            exit();
        }

        // Sekarang $controller['tank_id'] dijamin ada.
        $tank = Tank::findById((int)$controller['tank_id']);
        $pump = Pump::findById($controller['pump_id']);
        $sensor = Sensor::findById($controller['sensor_id']);

        if (!$tank || !$pump || !$sensor) {
            $_SESSION['error_message'] = "Gagal sinkronisasi: Salah satu data master (Tangki, Pompa, atau Sensor) tidak ditemukan.";
            header('Location: ' . base_url('/controllers'));
            exit();
        }

        // Siapkan array untuk laporan perubahan
        $changesReport = [];

        // Bandingkan dan siapkan data untuk diupdate
        $updateData = [
            'config_update_command' => 1 // <-- KIRIM PERINTAH UPDATE KE PERANGKAT
        ];

        // Cek dan catat perubahan
        if ($controller['empty_tank_distance'] != $tank['height']) {
            $changesReport[] = ['setting' => 'Tinggi Tangki (Empty Distance)', 'old_value' => $controller['empty_tank_distance'] . ' cm', 'new_value' => $tank['height'] . ' cm'];
            $updateData['empty_tank_distance'] = (int)$tank['height'];
        }
        if ($controller['full_tank_distance'] != $sensor['full_tank_distance']) {
            $changesReport[] = ['setting' => 'Jarak Tangki Penuh', 'old_value' => $controller['full_tank_distance'] . ' cm', 'new_value' => $sensor['full_tank_distance'] . ' cm'];
            $updateData['full_tank_distance'] = (int)$sensor['full_tank_distance'];
        }
        if ($controller['trigger_percentage'] != $sensor['trigger_percentage']) {
            $changesReport[] = ['setting' => 'Pemicu Pompa', 'old_value' => $controller['trigger_percentage'] . ' %', 'new_value' => $sensor['trigger_percentage'] . ' %'];
            $updateData['trigger_percentage'] = (int)$sensor['trigger_percentage'];
        }
        if ($controller['on_duration'] != ($pump['on_duration_seconds'] / 60)) {
            $changesReport[] = ['setting' => 'Durasi Pompa Nyala', 'old_value' => $controller['on_duration'] . ' menit', 'new_value' => ($pump['on_duration_seconds'] / 60) . ' menit'];
            $updateData['on_duration'] = (int)($pump['on_duration_seconds'] / 60);
        }
        if ($controller['off_duration'] != ($pump['off_duration_seconds'] / 60)) {
            $changesReport[] = ['setting' => 'Durasi Pompa Istirahat', 'old_value' => $controller['off_duration'] . ' menit', 'new_value' => ($pump['off_duration_seconds'] / 60) . ' menit'];
            $updateData['off_duration'] = (int)($pump['off_duration_seconds'] / 60);
        }

        // Lakukan update
        if (count($updateData) > 1) { // Lebih dari 1 karena config_update_command selalu ada
            Controller::update((int)$id, $updateData);
        }

        // Kirim respons JSON kembali ke JavaScript
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'success',
            'message' => 'Sinkronisasi untuk perangkat ' . htmlspecialchars($controller['tank_name'] ?? $controller['mac_address']) . ' selesai.',
            'changes' => $changesReport
        ]);
    }
}
