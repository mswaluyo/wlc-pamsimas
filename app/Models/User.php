<?php

namespace app\Models;

class User {
    /**
     * Mengambil semua data pengguna dari database.
     * @return array Daftar pengguna.
     */
    public static function getAll(): array {
        $pdo = \Database::getInstance()->getConnection();
        // Ambil semua kolom kecuali password
        $sql = "SELECT id, username, full_name, role, created_at FROM users ORDER BY id ASC";
        $stmt = $pdo->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Mencari pengguna berdasarkan username.
     * @param string $username
     * @return mixed Data pengguna atau false jika tidak ditemukan.
     */
    public static function findByUsername(string $username) {
        $pdo = \Database::getInstance()->getConnection();
        $sql = "SELECT * FROM users WHERE username = :username";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':username' => $username]);
        return $stmt->fetch();
    }

    /**
     * Membuat pengguna baru di database.
     * @param string $fullName
     * @param string $username
     * @param string $hashedPassword
     * @param string $role
     * @return bool
     */
    public static function create(string $fullName, string $username, string $hashedPassword, string $role): bool {
        $pdo = \Database::getInstance()->getConnection();
        $sql = "INSERT INTO users (full_name, username, password, role) VALUES (:full_name, :username, :password, :role)";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            ':full_name' => $fullName, ':username' => $username, ':password' => $hashedPassword, ':role' => $role
        ]);
    }
}
