<?php

namespace app\Controllers;

use app\Models\Controller;
use app\Models\IndicatorSetting;
use app\Models\Tank;
use app\Models\Pump;
use app\Models\Sensor;
use app\Models\User;

class DashboardController {

    public function __construct() {
        if (!isset($_SESSION['user'])) {
            header('Location: ' . base_url('/login'));
            exit();
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
        foreach ($controllers as $controller) {
            // Anggap online jika update dalam 1 menit terakhir (60 detik)
            if (strtotime($controller['last_update']) > (time() - 60)) {
                $onlineControllers++;
            }
        }

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
            'out_of_sync_devices' => $outOfSyncDevices // Data perangkat yang tidak sinkron
        ];

        view('dashboard/index', $data);
    }
}