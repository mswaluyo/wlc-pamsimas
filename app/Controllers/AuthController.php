<?php

namespace app\Controllers;

use app\Models\User;

class AuthController {

    public function __construct() {
    }

    /**
     * Menampilkan halaman form login.
     */
    public function showLoginForm() {
        // Jika sudah login, redirect ke dashboard
        if (isset($_SESSION['user'])) {
            header('Location: ' . base_url('/'));
            exit();
        }
        // Tampilkan view login tanpa menggunakan layout utama
        view('auth/login', [], false);
    }

    /**
     * Memproses data dari form login.
     */
    public function login() {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';

        $user = User::findByUsername($username);

        // Verifikasi user ada dan password cocok
        if ($user && password_verify($password, $user['password'])) {
            // Simpan informasi user ke session, jangan simpan password
            $_SESSION['user'] = [
                'id' => $user['id'],
                'full_name' => $user['full_name'],
                'role' => $user['role']
            ];
            header('Location: ' . base_url('/')); // Redirect ke dashboard
            exit();
        } else {
            // Jika gagal, kembali ke halaman login dengan pesan error
            $_SESSION['error'] = 'Username atau password salah.';
            header('Location: ' . base_url('/login'));
            exit();
        }
    }

    /**
     * Menghancurkan session dan logout pengguna.
     */
    public function logout() {
        session_unset();
        session_destroy();
        header('Location: ' . base_url('/login'));
        exit();
    }
}