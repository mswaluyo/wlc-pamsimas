<?php

namespace app\Controllers;

use app\Models\PumpLog;
use app\Models\SensorLog; // PERBAIKAN: Tambahkan model SensorLog
use app\Models\AdminLog;
use app\Models\EventLog; // Tambahkan model EventLog

class LogController {

    public function __construct() {
        $timezone = $_ENV['TIMEZONE'] ?? getenv('TIMEZONE') ?? 'Asia/Jakarta';
        date_default_timezone_set($timezone);

        if (!isset($_SESSION['user'])) {
            header('Location: ' . base_url('/login'));
            exit();
        }

        // FITUR REMEMBER ME: Perpanjang durasi session cookie menjadi 30 hari
        if (session_status() === PHP_SESSION_ACTIVE && !empty($_SESSION['remember_me'])) {
            $params = session_get_cookie_params();
            setcookie(session_name(), session_id(), time() + (86400 * 30), $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
        }
    }

    public function pumpHistory() {
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limitParam = isset($_GET['limit']) ? $_GET['limit'] : 25;
        $deviceId = isset($_GET['device_id']) && $_GET['device_id'] !== 'all' ? (int)$_GET['device_id'] : null;

        if ($limitParam === 'all') {
            $limit = 1000000; // Angka besar untuk mengambil semua
        } else {
            $limit = (int)$limitParam;
            if ($limit < 1) $limit = 25;
        }
        $offset = ($page - 1) * $limit;

        $logs = PumpLog::getPaginatedHistory($limit, $offset, $deviceId);

        $totalLogs = PumpLog::countAll($deviceId);
        
        // Ambil daftar controller untuk dropdown filter
        $controllers = \app\Models\Controller::getAll();

        $data = [
            'title' => 'Riwayat Aktivitas Pompa',
            'logs' => $logs,
            'limit' => $limitParam,
            'controllers' => $controllers,
            'selected_device' => $deviceId,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => ceil($totalLogs / $limit),
                'base_url' => base_url('/logs/pumps'),
                'limit' => $limitParam,
                'device_id' => $deviceId
            ]
        ];

        view('logs/pump_history', $data);
    }

    /**
     * Aksi untuk membersihkan log pompa yang duplikat/spam.
     */
    public function cleanupPumps() {
        if ($_SESSION['user']['role'] !== 'Administrator') {
            http_response_code(403);
            exit("Akses ditolak.");
        }

        $deletedCount = PumpLog::removeRedundantLogs();
        $_SESSION['success_message'] = "Berhasil membersihkan $deletedCount data log pompa yang duplikat/spam.";
        header('Location: ' . base_url('/logs/pumps'));
        exit();
    }

    /**
     * Menampilkan halaman riwayat log sensor.
     */
    public function sensorLogs() {
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limitParam = isset($_GET['limit']) ? $_GET['limit'] : 25;

        if ($limitParam === 'all') {
            $limit = 1000000;
        } else {
            $limit = (int)$limitParam;
            if ($limit < 1) $limit = 25;
        }
        $offset = ($page - 1) * $limit;

        $logs = SensorLog::getPaginatedLogs($limit, $offset);

        $totalLogs = SensorLog::countAll();

        $data = [
            'title' => 'Riwayat Data Sensor',
            'logs' => $logs,
            'limit' => $limitParam,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => ceil($totalLogs / $limit),
                'base_url' => base_url('/logs/sensors'),
                'limit' => $limitParam
            ]
        ];

        view('logs/sensors', $data); // PERBAIKAN: Gunakan file view yang sudah ada
    }

    /**
     * Menampilkan halaman audit trail (log admin).
     */
    public function adminLogs() {
        if ($_SESSION['user']['role'] !== 'Administrator') {
            http_response_code(403);
            exit("Akses ditolak.");
        }

        // Logika paginasi untuk admin logs (jika diakses langsung, meski sekarang via maintenance)
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limitParam = isset($_GET['limit']) ? $_GET['limit'] : 25;

        if ($limitParam === 'all') {
            $limit = 1000000;
        } else {
            $limit = (int)$limitParam;
            if ($limit < 1) $limit = 25;
        }
        $offset = ($page - 1) * $limit;

        $data = [
            'title' => 'Audit Trail (Log Admin)',
            'logs' => AdminLog::getPaginatedLogs($limit, $offset),
            'limit' => $limitParam,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => ceil(AdminLog::countAll() / $limit),
                'base_url' => base_url('/logs/admin'),
                'limit' => $limitParam
            ]
        ];

        view('logs/admin', $data);
    }

    /**
     * Menampilkan halaman log kejadian (Power On, Error, dll).
     */
    public function eventLogs() {
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limitParam = isset($_GET['limit']) ? $_GET['limit'] : 25;

        if ($limitParam === 'all') {
            $limit = 1000000;
        } else {
            $limit = (int)$limitParam;
            if ($limit < 1) $limit = 25;
        }
        $offset = ($page - 1) * $limit;

        $logs = EventLog::getPaginatedLogs($limit, $offset);
        $totalLogs = EventLog::countAll();

        $data = [
            'title' => 'Log Kejadian & Power',
            'logs' => $logs,
            'limit' => $limitParam,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => ceil($totalLogs / $limit),
                'base_url' => base_url('/logs/events'),
                'limit' => $limitParam
            ]
        ];

        view('logs/events', $data);
    }

    /**
     * Menampilkan halaman terminal monitoring real-time.
     */
    public function terminal() {
        $data = [
            'title' => 'Terminal Monitoring',
            'page_scripts' => ['js/terminal-monitor.js']
        ];
        view('logs/terminal', $data);
    }
}