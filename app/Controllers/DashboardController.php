<?php

namespace app\Controllers;

use app\Models\Controller;
use app\Models\IndicatorSetting;
use app\Models\Tank;
use app\Models\Pump;
use app\Models\Sensor;
use app\Models\User;
use app\Models\PumpLog;

class DashboardController {

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

    public function index() {
        $controllers = Controller::getAll();
        $tanks = Tank::getAll();
        $pumps = Pump::getAll();
        $sensors = Sensor::getAll();
        $users = User::getAll();

        // Hitung statistik
        $totalControllers = count($controllers);
        $onlineControllers = 0;
        
        // Gunakan referensi (&) agar kita bisa memodifikasi array asli untuk tampilan awal
        foreach ($controllers as &$controller) {
            // PERBAIKAN: Tingkatkan toleransi menjadi 120 detik agar status lebih stabil
            $isOnline = (strtotime($controller['last_update']) > (time() - 120));
            $controller['is_online'] = $isOnline;

            if ($isOnline) {
                $onlineControllers++;
            }

            // Hitung durasi status pompa saat ini (detik) agar Timer Gauge tidak "-" saat load awal
            $lastLogTime = PumpLog::getLastLogTime($controller['id']);
            
            // Fix Timezone: Pastikan parsing waktu sesuai konfigurasi .env
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
            
            $controller['last_pump_change_timestamp'] = $ts;
            
            $duration = $ts ? (time() - $ts) : 0;
            $controller['current_pump_duration'] = max(0, $duration); // Cegah nilai negatif akibat selisih timezone
        }
        unset($controller); // Hapus referensi

        // --- LOGIKA BARU: Cek Sinkronisasi ---
        // Indexing data master untuk lookup cepat
        $tanksMap = [];
        foreach ($tanks as $t) $tanksMap[$t['id']] = $t;
        
        $pumpsMap = [];
        foreach ($pumps as $p) $pumpsMap[$p['id']] = $p;
        
        $sensorsMap = [];
        foreach ($sensors as $s) $sensorsMap[$s['id']] = $s;

        $outOfSyncDevices = [];

        foreach ($controllers as $c) {
            $isSync = true;
            $details = [];

            // Cek Tangki
            if (isset($tanksMap[$c['tank_id']]) && $c['empty_tank_distance'] != $tanksMap[$c['tank_id']]['height']) {
                $isSync = false;
                $details[] = 'Tinggi Tangki';
            }
            // Cek Sensor
            if (isset($sensorsMap[$c['sensor_id']])) {
                if ($c['full_tank_distance'] != $sensorsMap[$c['sensor_id']]['full_tank_distance']) { $isSync = false; $details[] = 'Jarak Penuh'; }
                if ($c['trigger_percentage'] != $sensorsMap[$c['sensor_id']]['trigger_percentage']) { $isSync = false; $details[] = 'Pemicu'; }
            }
            // Cek Pompa
            if (isset($pumpsMap[$c['pump_id']])) {
                if ($c['on_duration'] != (int)($pumpsMap[$c['pump_id']]['on_duration_seconds'] / 60)) { $isSync = false; $details[] = 'Durasi Nyala'; }
                if ($c['off_duration'] != (int)($pumpsMap[$c['pump_id']]['off_duration_seconds'] / 60)) { $isSync = false; $details[] = 'Durasi Mati'; }
            }

            if (!$isSync) {
                $c['sync_issues'] = implode(', ', array_unique($details));
                $outOfSyncDevices[] = $c;
            }
        }

        // Ambil pengaturan indikator untuk menentukan template mana yang aktif
        $indicatorSettings = IndicatorSetting::getSettings();
        $activeTemplateData = null;

        if ($indicatorSettings && isset($indicatorSettings['active_template_id'])) {
            $activeTemplate = \app\Models\GaugeTemplate::findById($indicatorSettings['active_template_id']);
            if ($activeTemplate) {
                $html_content = $activeTemplate['html_code'] ?? '';
                $clean_html = '';

                // Hanya proses jika ada konten HTML
                if (!empty($html_content)) {
                    // Pastikan HTML didecode jika tersimpan sebagai entitas di database
                    $html_content = html_entity_decode($html_content);

                    // Ekstrak hanya konten di dalam <body> untuk menghindari konflik
                    $doc = new \DOMDocument();
                    @$doc->loadHTML('<?xml encoding="utf-8" ?>' . $html_content); // Tambahkan header untuk encoding
                    $body = $doc->getElementsByTagName('body')->item(0);
                    if ($body) {
                        foreach ($body->childNodes as $child) {
                            $clean_html .= $doc->saveHTML($child);
                        }
                    } else { $clean_html = $html_content; } // Fallback jika tidak ada body
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
            'title' => 'Dashboard',
            'stats' => [
                'total_controllers' => $totalControllers,
                'online_controllers' => $onlineControllers,
                'total_tanks' => count($tanks),
                'total_users' => count($users)
            ],
            'controllers' => $controllers,
            'indicator_settings' => $indicatorSettings,
            'active_template' => $activeTemplateData, // Kirim data template yang sudah diproses
            'out_of_sync_devices' => $outOfSyncDevices, // Data perangkat yang tidak sinkron
            'page_scripts' => ['js/dashboard-live.js?v=' . microtime(true)] // Tambahkan script live update dengan cache busting yang lebih kuat
        ];

        view('dashboard/index', $data);
    }
}