<?php

namespace app\Controllers;

use app\Models\Controller;
use app\Models\IndicatorSetting;
use app\Models\Tank;
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
        $users = User::getAll();

        // Hitung statistik
        $totalControllers = count($controllers);
        $onlineControllers = 0;
        foreach ($controllers as $controller) {
            // Anggap online jika update dalam 5 menit terakhir (300 detik)
            if (strtotime($controller['last_update']) > (time() - 300)) {
                $onlineControllers++;
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
            'active_template' => $activeTemplateData // Kirim data template yang sudah diproses
        ];

        view('dashboard/index', $data);
    }
}