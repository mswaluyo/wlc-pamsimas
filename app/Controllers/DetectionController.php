<?php

namespace app\Controllers;

class DetectionController {

    public function __construct() {
        if (!isset($_SESSION['user'])) {
            header('Location: ' . base_url('/login'));
            exit();
        }
    }

    public function index() {
        $data = [
            'title' => 'Deteksi Perangkat Real-time',
            'page_scripts' => ['js/detection.js'] // Memuat script khusus halaman ini
        ];
        view('detection/index', $data);
    }
}