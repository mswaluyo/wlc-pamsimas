<?php

namespace app\Controllers;

use app\Models\PumpLog;
use app\Models\SensorLog; // PERBAIKAN: Tambahkan model SensorLog
use app\Models\AdminLog;
use app\Models\EventLog; // Tambahkan model EventLog

class LogController {

    public function __construct() {
        if (!isset($_SESSION['user'])) {
            header('Location: ' . base_url('/login'));
            exit();
        }
    }

    public function pumpHistory() {
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limitParam = isset($_GET['limit']) ? $_GET['limit'] : 25;

        if ($limitParam === 'all') {
            $limit = 1000000; // Angka besar untuk mengambil semua
        } else {
            $limit = (int)$limitParam;
            if ($limit < 1) $limit = 25;
        }
        $offset = ($page - 1) * $limit;

        $logs = PumpLog::getPaginatedHistory($limit, $offset);
        $totalLogs = PumpLog::countAll();

        $data = [
            'title' => 'Riwayat Aktivitas Pompa',
            'logs' => $logs,
            'limit' => $limitParam,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => ceil($totalLogs / $limit),
                'base_url' => base_url('/logs/pumps'),
                'limit' => $limitParam
            ]
        ];

        view('logs/pump_history', $data);
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
}