<?php

namespace app\Controllers;

use app\Models\GaugeTemplate;

class TemplateController {

    public function __construct() {
        // Autentikasi dan otorisasi
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'Administrator') {
            http_response_code(403);
            echo "<h1>403 Forbidden</h1><p>Anda tidak memiliki hak akses ke halaman ini.</p>";
            exit();
        }
    }

    /**
     * Menampilkan halaman daftar semua template.
     */
    public function index() {
        $data = [
            'title' => 'Manajemen Template',
            'templates' => GaugeTemplate::getAll(),
            // Hapus 'page_styles' agar tidak memuat CSS template secara global
        ];
        view('templates/index', $data);
    }

    public function create() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Validasi input dasar
            if (empty($_POST['name']) || empty($_POST['html_code']) || empty($_POST['css_code'])) {
                $_SESSION['error_message'] = 'Nama, Kode HTML, dan Kode CSS wajib diisi.';
                header('Location: ' . base_url('/templates/create'));
                exit();
            }

            $data = [
                'name' => $_POST['name'],
                'description' => $_POST['description'] ?? '',
                'html_code' => $_POST['html_code'],
                'css_code' => $_POST['css_code'],
                'js_code' => $_POST['js_code'] ?? null,
            ];

            GaugeTemplate::create($data);

            header('Location: ' . base_url('/templates'));
            exit();
        }

        $data = [
            'title' => 'Tambah Template Gauge Baru'
        ];
        view('templates/form', $data);
    }

    /**
     * Menampilkan form untuk mengedit template.
     */
    public function edit($id) {
        $template = GaugeTemplate::findById((int)$id);

        if (!$template || $template['is_core']) {
            // Jika template tidak ada atau merupakan template bawaan, larang akses.
            http_response_code(403);
            echo "<h1>403 Forbidden</h1><p>Template ini tidak dapat diubah.</p>";
            exit();
        }

        $data = [
            'title' => 'Edit Template: ' . htmlspecialchars($template['name']),
            'template' => $template,
            'form_action' => base_url('/templates/update/' . $id),
            'is_edit' => true
        ];
        view('templates/form', $data);
    }

    /**
     * Memproses pembaruan data template.
     */
    public function update($id) {
        $template = GaugeTemplate::findById((int)$id);
        if (!$template || $template['is_core']) {
            http_response_code(403);
            exit("Akses ditolak.");
        }

        $data = [
            'name' => $_POST['name'],
            'description' => $_POST['description'] ?? '',
            'html_code' => $_POST['html_code'],
            'css_code' => $_POST['css_code'],
            'js_code' => $_POST['js_code'] ?? null,
        ];
        GaugeTemplate::update((int)$id, $data);

        header('Location: ' . base_url('/templates'));
        exit();
    }

    /**
     * Menghapus template.
     */
    public function delete($id) {
        $template = GaugeTemplate::findById((int)$id);
        if ($template && !$template['is_core']) {
            // Tidak perlu lagi menghapus file fisik
            GaugeTemplate::delete((int)$id);
        }
        header('Location: ' . base_url('/templates'));
        exit();
    }
}