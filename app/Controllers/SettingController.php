<?php

namespace app\Controllers;

use app\Models\Tank;
use app\Models\Pump;
use app\Models\Sensor;
use app\Models\IndicatorSetting; // Tambahkan ini
use app\Models\GaugeTemplate; // Tambahkan ini
use app\Models\AdminLog;

class SettingController {

    public function __construct() {
        if (!isset($_SESSION['user'])) {
            header('Location: ' . base_url('/login'));
            exit();
        }

        if ($_SESSION['user']['role'] !== 'Administrator') {
            http_response_code(403); // Forbidden
            echo "<h1>403 Forbidden</h1><p>Anda tidak memiliki hak akses ke halaman ini.</p>";
            exit();
        }
    }

    /**
     * Menampilkan halaman pengaturan.
     */
    public function tanks() {
        $data = [
            'title' => 'Pengaturan Tangki',
            'tanks' => Tank::getAll()
        ];

        view('settings/tanks', $data);
    }

    /**
     * Menampilkan form untuk menambah tangki baru.
     */
    public function createTank() {
        $data = [
            'title' => 'Tambah Tangki Baru',
            'form_action' => base_url('/settings/tanks/create')
        ];
        view('settings/tank_form', $data);
    }

    /**
     * Menyimpan tangki baru ke database.
     */
    public function storeTank() {
        $data = [
            'tank_name' => $_POST['tank_name'] ?? '',
            'height' => $_POST['height'] ?? 0,
            'tank_shape' => $_POST['tank_shape'] ?? 'kotak',
            'length' => $_POST['length'] ?? null,
            'width' => $_POST['width'] ?? null,
            'diameter' => $_POST['diameter'] ?? null,
        ];

        Tank::create($data);
        header('Location: ' . base_url('/settings/tanks'));
        exit();
    }

    /**
     * Menampilkan form untuk mengedit tangki.
     */
    public function editTank($id) {
        $tank = Tank::findById($id);
        if (!$tank) {
            http_response_code(404);
            echo "Tangki tidak ditemukan.";
            exit();
        }

        $data = [
            'title' => 'Edit Tangki: ' . htmlspecialchars($tank['tank_name']),
            'tank' => $tank,
            'form_action' => base_url('/settings/tanks/edit/' . $id)
        ];
        view('settings/tank_form', $data);
    }

    /**
     * Memperbarui data tangki di database.
     */
    public function updateTank($id) {
        $data = [
            'tank_name' => $_POST['tank_name'] ?? '',
            'height' => $_POST['height'] ?? 0,
            'tank_shape' => $_POST['tank_shape'] ?? 'kotak',
            'length' => $_POST['length'] ?? null,
            'width' => $_POST['width'] ?? null,
            'diameter' => $_POST['diameter'] ?? null,
        ];

        if (empty($data['tank_name'])) {
            // Handle error
            header('Location: ' . base_url('/settings/tanks/edit/' . $id));
            exit();
        }

        Tank::update((int)$id, $data);
        header('Location: ' . base_url('/settings/tanks'));
        exit();
    }

    /**
     * Menampilkan halaman pengaturan pompa.
     */
    public function pumps() {
        $data = [
            'title' => 'Pengaturan Pompa',
            'pumps' => Pump::getAll()
        ];

        view('settings/pumps', $data);
    }

    /**
     * Menampilkan form untuk menambah pompa baru.
     */
    public function createPump() {
        $data = [
            'title' => 'Tambah Pompa Baru',
            'form_action' => base_url('/settings/pumps/create')
        ];
        view('settings/pump_form', $data);
    }

    /**
     * Menyimpan pompa baru ke database.
     */
    public function storePump() {
        $data = [
            'pump_name' => $_POST['pump_name'] ?? '',
            'flow_rate_lps' => $_POST['flow_rate_lps'] ?? 0,
            'power_watt' => $_POST['power_watt'] ?? 0,
            'delay_seconds' => $_POST['delay_seconds'] ?? 0,
        ];

        Pump::create($data);
        header('Location: ' . base_url('/settings/pumps'));
        exit();
    }

    /**
     * Menampilkan form untuk mengedit pompa.
     */
    public function editPump($id) {
        $pump = Pump::findById($id);
        if (!$pump) {
            http_response_code(404);
            echo "Pompa tidak ditemukan.";
            exit();
        }

        $data = [
            'title' => 'Edit Pompa: ' . htmlspecialchars($pump['pump_name']),
            'pump' => $pump,
            'form_action' => base_url('/settings/pumps/edit/' . $id)
        ];
        view('settings/pump_form', $data);
    }

    /**
     * Memperbarui data pompa di database.
     */
    public function updatePump($id) {
        $data = [
            'pump_name' => $_POST['pump_name'] ?? '',
            'flow_rate_lps' => $_POST['flow_rate_lps'] ?? 0,
            'power_watt' => $_POST['power_watt'] ?? 0,
            'delay_seconds' => $_POST['delay_seconds'] ?? 0,
        ];

        Pump::update((int)$id, $data);
        header('Location: ' . base_url('/settings/pumps'));
        exit();
    }

    /**
     * Menampilkan halaman pengaturan sensor per perangkat.
     */
    public function sensors() {
        $data = [
            'title' => 'Pengaturan Sensor',
            // Menggunakan model Sensor yang baru
            'sensors' => Sensor::getAll()
        ];

        view('settings/sensors', $data);
    }

    /**
     * Menampilkan form untuk menambah sensor baru.
     */
    public function createSensor() {
        $data = [
            'title' => 'Tambah Sensor Baru',
            'form_action' => base_url('/settings/sensors/create')
        ];
        view('settings/sensor_form', $data);
    }

    /**
     * Menyimpan sensor baru ke database.
     */
    public function storeSensor() {
        $data = [
            'sensor_name' => $_POST['sensor_name'] ?? '',
            'sensor_type' => $_POST['sensor_type'] ?? '',
            'full_tank_distance' => $_POST['full_tank_distance'] ?? 0,
            'trigger_percentage' => $_POST['trigger_percentage'] ?? 0,
        ];

        // Validasi sederhana
        if (empty($data['sensor_name'])) {
            $_SESSION['error'] = 'Nama sensor wajib diisi.';
            header('Location: ' . base_url('/settings/sensors/create'));
            exit();
        }

        Sensor::create($data);
        header('Location: ' . base_url('/settings/sensors'));
        exit();
    }

    /**
     * Menampilkan form untuk mengedit sensor.
     */
    public function editSensor($id) {
        $sensor = Sensor::findById($id);
        if (!$sensor) {
            http_response_code(404);
            echo "Sensor tidak ditemukan.";
            exit();
        }

        $data = [
            'title' => 'Edit Sensor: ' . htmlspecialchars($sensor['sensor_name']),
            'sensor' => $sensor,
            'form_action' => base_url('/settings/sensors/edit/' . $id)
        ];
        view('settings/sensor_form', $data);
    }

    /**
     * Memperbarui data sensor di database.
     */
    public function updateSensor($id) {
        $data = [
            'sensor_name' => $_POST['sensor_name'] ?? '',
            'sensor_type' => $_POST['sensor_type'] ?? '',
            'full_tank_distance' => $_POST['full_tank_distance'] ?? 0,
            'trigger_percentage' => $_POST['trigger_percentage'] ?? 0, // PERBAIKAN: Tambahkan koma yang hilang
        ];

        Sensor::update((int)$id, $data);
        header('Location: ' . base_url('/settings/sensors'));
        exit();
    }

    /**
     * Menampilkan dan memproses halaman gabungan untuk Pengaturan Tampilan.
     */
    public function displaySettings() {
        // Jika form disubmit (metode POST)
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'threshold_low' => (int)$_POST['threshold_low'],
                'color_low' => $_POST['color_low'],
                'threshold_medium' => (int)$_POST['threshold_medium'],
                'color_medium' => $_POST['color_medium'],
                'color_high' => $_POST['color_high'],
                'active_template_id' => (int)$_POST['active_template_id'],
            ];
            IndicatorSetting::updateSettings($data);
            $_SESSION['success_message'] = 'Pengaturan indikator berhasil diperbarui.';
            header('Location: ' . base_url('/settings/display'));
            exit();
        }

        // Jika hanya menampilkan halaman (metode GET)
        $data = [
            'title' => 'Pengaturan Tampilan',
            'settings' => IndicatorSetting::getSettings(),
            'templates' => GaugeTemplate::getAll(),
            // PERBAIKAN: Tentukan file JS yang akan dimuat untuk halaman ini
            'page_scripts' => ['js/indicator-settings.js']
        ];
        view('settings/indicators', $data);
    }

    /**
     * Mereset pengaturan tampilan ke nilai default.
     */
    public function resetDisplaySettings() {
        // Nilai default (sesuai dengan palet warna di style.css)
        $defaults = [
            'threshold_low' => 30,
            'color_low' => '#e74c3c', // Merah
            'threshold_medium' => 70,
            'color_medium' => '#f39c12', // Oranye
            'color_high' => '#27ae60', // Hijau
        ];

        // Ambil setting saat ini untuk mempertahankan template aktif
        $currentSettings = IndicatorSetting::getSettings();
        $defaults['active_template_id'] = $currentSettings['active_template_id'] ?? 1;

        IndicatorSetting::updateSettings($defaults);
        $_SESSION['success_message'] = 'Pengaturan warna dan batas level berhasil direset ke default.';
        header('Location: ' . base_url('/settings/display'));
        exit();
    }

    /**
     * Menampilkan dan memproses halaman pengaturan indikator.
     * Metode ini sekarang usang dan akan dihapus nanti.
     * Untuk sementara, arahkan ke halaman baru.
     */
    public function indicators() {
        header('Location: ' . base_url('/settings/display'));
        exit();
    }

    /**
     * Memproses restore database dari file .sql yang diupload.
     */
    public function restoreDatabase() {
        if ($_SESSION['user']['role'] !== 'Administrator') {
            http_response_code(403);
            exit("Akses ditolak.");
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['backup_file'])) {
            $file = $_FILES['backup_file'];

            if ($file['error'] !== UPLOAD_ERR_OK || pathinfo($file['name'], PATHINFO_EXTENSION) !== 'sql') {
                $_SESSION['error_message'] = "File tidak valid. Harap upload file .sql.";
                header('Location: ' . base_url('/settings/maintenance'));
                exit();
            }

            // Tingkatkan batas waktu dan memori untuk file besar
            set_time_limit(0);
            ini_set('memory_limit', '-1');

            $pdo = \Database::getInstance()->getConnection();
            
            try {
                // Nonaktifkan pemeriksaan foreign key sementara
                $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

                // Baca file baris per baris untuk menghindari masalah memori
                $sqlLines = file($file['tmp_name']);
                $templine = '';
                
                foreach ($sqlLines as $line) {
                    // Lewati komentar
                    if (substr($line, 0, 2) == '--' || $line == '') continue;

                    $templine .= $line;
                    // Jika baris diakhiri dengan titik koma, eksekusi query
                    if (substr(trim($line), -1, 1) == ';') {
                        $pdo->exec($templine);
                        $templine = '';
                    }
                }

                $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
                $_SESSION['success_message'] = "Database berhasil dipulihkan.";
                AdminLog::create($_SESSION['user']['id'], 'Restore Database', 'Restore berhasil dari file: ' . $file['name']);
            } catch (\Exception $e) {
                $pdo->exec("SET FOREIGN_KEY_CHECKS = 1"); // Pastikan dikembalikan
                $_SESSION['error_message'] = "Gagal restore: " . $e->getMessage();
                AdminLog::create($_SESSION['user']['id'], 'Restore Database Failed', 'Gagal restore dari file: ' . $file['name'] . '. Error: ' . $e->getMessage());
            }
        }
        
        header('Location: ' . base_url('/settings/maintenance'));
        exit();
    }

    /**
     * Membuat dan mengirimkan file backup database (.sql).
     */
    public function backupDatabase() {
        if ($_SESSION['user']['role'] !== 'Administrator') {
            http_response_code(403);
            exit("Akses ditolak.");
        }

        // Catat aktivitas backup
        AdminLog::create($_SESSION['user']['id'], 'Backup Database', 'Melakukan backup manual (download .sql)');

        $dbName = getenv('DB_NAME') ?: 'wlc_db';
        $filename = 'backup_' . $dbName . '_' . date('Y-m-d_H-i-s') . '.sql';

        // Set header agar browser menganggap ini sebagai file download
        header('Content-Type: application/sql');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $pdo = \Database::getInstance()->getConnection();
        
        // Ambil daftar semua tabel
        $tables = [];
        $result = $pdo->query('SHOW TABLES');
        while ($row = $result->fetch(\PDO::FETCH_NUM)) {
            $tables[] = $row[0];
        }
        
        // Nonaktifkan pemeriksaan foreign key di awal file
        echo "SET FOREIGN_KEY_CHECKS=0;\n\n";

        foreach ($tables as $table) {
            // 1. Dump Struktur Tabel (CREATE TABLE)
            $row = $pdo->query('SHOW CREATE TABLE ' . $table)->fetch(\PDO::FETCH_NUM);
            echo "DROP TABLE IF EXISTS `{$table}`;\n";
            echo $row[1] . ";\n\n";
            
            // 2. Dump Data Tabel (INSERT INTO)
            $rows = $pdo->query('SELECT * FROM ' . $table);
            while ($row = $rows->fetch(\PDO::FETCH_ASSOC)) {
                $keys = array_map(function ($key) { return "`$key`"; }, array_keys($row));
                $values = array_map(function ($value) use ($pdo) {
                    return ($value === null) ? 'NULL' : $pdo->quote($value);
                }, array_values($row));
                
                echo "INSERT INTO `{$table}` (" . implode(', ', $keys) . ") VALUES (" . implode(', ', $values) . ");\n";
            }
            echo "\n";
        }
        
        echo "SET FOREIGN_KEY_CHECKS=1;\n";
        exit;
    }

    /**
     * Menampilkan halaman pengaturan maintenance mode.
     */
    public function maintenance() {
        if ($_SESSION['user']['role'] !== 'Administrator') {
            http_response_code(403);
            exit("Akses ditolak.");
        }
        
        $isMaintenance = file_exists(ROOT_PATH . '/.maintenance');
        
        // Paginasi untuk Audit Trail
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limitParam = isset($_GET['limit']) ? $_GET['limit'] : 25;

        if ($limitParam === 'all') {
            $limit = 1000000;
        } else {
            $limit = (int)$limitParam;
            if ($limit < 1) $limit = 25;
        }
        $offset = ($page - 1) * $limit;
        
        $logs = AdminLog::getPaginatedLogs($limit, $offset);
        $totalLogs = AdminLog::countAll();
        
        $data = [
            'title' => 'Maintenance & Database',
            'isMaintenance' => $isMaintenance,
            'logs' => $logs,
            'current_page' => $page,
            'total_pages' => ceil($totalLogs / $limit),
            'limit' => $limitParam
        ];
        view('settings/maintenance', $data);
    }

    /**
     * Mengaktifkan atau menonaktifkan maintenance mode.
     */
    public function toggleMaintenance() {
        if ($_SESSION['user']['role'] !== 'Administrator') {
            http_response_code(403);
            exit("Akses ditolak.");
        }

        $file = ROOT_PATH . '/.maintenance';
        
        if (file_exists($file)) {
            unlink($file);
            $_SESSION['success_message'] = "Maintenance Mode dinonaktifkan. Aplikasi dapat diakses publik.";
        } else {
            file_put_contents($file, 'Maintenance mode active since ' . date('Y-m-d H:i:s'));
            $_SESSION['success_message'] = "Maintenance Mode diaktifkan. Hanya Administrator yang dapat mengakses aplikasi.";
        }
        
        header('Location: ' . base_url('/settings/maintenance'));
        exit();
    }
}