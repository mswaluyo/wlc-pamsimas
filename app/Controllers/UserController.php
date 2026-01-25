<?php

namespace app\Controllers;

use app\Models\User;

class UserController {

    public function __construct() {
        if (!isset($_SESSION['user'])) {
            header('Location: ' . base_url('/login'));
            exit();
        }

        // Hanya Administrator yang boleh mengakses controller ini
        if ($_SESSION['user']['role'] !== 'Administrator') {
            http_response_code(403); // Forbidden
            echo "<h1>403 Forbidden</h1><p>Anda tidak memiliki hak akses ke halaman ini.</p>";
            exit();
        }
    }

    /**
     * Menampilkan daftar semua pengguna.
     */
    public function index() {
        $users = User::getAll();

        $data = [
            'title' => 'Manajemen Pengguna',
            'users' => $users
        ];

        view('users/index', $data);
    }

    /**
     * Menampilkan form untuk membuat pengguna baru.
     */
    public function create() {
        $data = [
            'title' => 'Tambah Pengguna Baru'
        ];
        view('users/create', $data);
    }

    /**
     * Menyimpan pengguna baru ke database.
     */
    public function store() {
        // Ambil data dari form
        $fullName = $_POST['full_name'] ?? '';
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        $passwordConfirmation = $_POST['password_confirmation'] ?? '';
        $role = $_POST['role'] ?? '';

        // Validasi sederhana
        if (empty($fullName) || empty($username) || empty($password)) {
            $_SESSION['error'] = 'Semua field wajib diisi.';
            header('Location: ' . base_url('/users/create'));
            exit();
        }

        if ($password !== $passwordConfirmation) {
            $_SESSION['error'] = 'Konfirmasi password tidak cocok.';
            header('Location: ' . base_url('/users/create'));
            exit();
        }

        if (User::findByUsername($username)) {
            $_SESSION['error'] = 'Username sudah digunakan.';
            header('Location: ' . base_url('/users/create'));
            exit();
        }

        // Hash password dan simpan pengguna
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        User::create($fullName, $username, $hashedPassword, $role);

        // Redirect ke halaman daftar pengguna
        header('Location: ' . base_url('/users'));
        exit();
    }

    /**
     * Menampilkan form untuk mengedit pengguna.
     */
    public function edit($id) {
        $user = User::findById((int)$id);
        if (!$user) {
            http_response_code(404);
            echo "Pengguna tidak ditemukan.";
            exit();
        }

        $data = [
            'title' => 'Edit Pengguna: ' . htmlspecialchars($user['full_name']),
            'user' => $user
        ];
        view('users/edit', $data);
    }

    /**
     * Memperbarui data pengguna.
     */
    public function update($id) {
        $fullName = $_POST['full_name'] ?? '';
        $role = $_POST['role'] ?? '';
        $password = $_POST['password'] ?? '';
        $passwordConfirmation = $_POST['password_confirmation'] ?? '';

        $data = [
            'full_name' => $fullName,
            'role' => $role
        ];

        // Jika password diisi, validasi dan update password
        if (!empty($password)) {
            if ($password !== $passwordConfirmation) {
                $_SESSION['error'] = 'Konfirmasi password tidak cocok.';
                header('Location: ' . base_url('/users/edit/' . $id));
                exit();
            }
            $data['password'] = password_hash($password, PASSWORD_DEFAULT);
        }

        User::update((int)$id, $data);

        header('Location: ' . base_url('/users'));
        exit();
    }

    /**
     * Menghapus pengguna.
     */
    public function delete($id) {
        // Mencegah penghapusan diri sendiri (opsional tapi disarankan)
        if ($_SESSION['user']['id'] != $id) {
            User::delete((int)$id);
        }
        header('Location: ' . base_url('/users'));
        exit();
    }
}
