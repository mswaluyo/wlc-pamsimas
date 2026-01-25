<?php

namespace app\Models;

use PDO;

class Pump {
    /**
     * Mengambil semua data pompa.
     */
    public static function getAll() {
        $pdo = \Database::getInstance()->getConnection();
        $stmt = $pdo->query("SELECT * FROM pumps ORDER BY pump_name ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Mengambil data satu pompa berdasarkan ID.
     */
    public static function findById($id) {
        $pdo = \Database::getInstance()->getConnection();
        $stmt = $pdo->prepare("SELECT * FROM pumps WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Membuat data pompa baru secara dinamis.
     */
    public static function create(array $data) {
        $pdo = \Database::getInstance()->getConnection();
        
        // Buat daftar kolom dan placeholder secara otomatis dari array data
        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        
        $sql = "INSERT INTO pumps ($columns) VALUES ($placeholders)";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute($data);
    }

    /**
     * Memperbarui data pompa secara dinamis.
     * Ini memperbaiki error "Invalid parameter number".
     */
    public static function update($id, array $data) {
        $pdo = \Database::getInstance()->getConnection();
        
        // Buat klausa SET secara otomatis (misal: pump_name = :pump_name)
        $setClauses = [];
        foreach (array_keys($data) as $key) {
            $setClauses[] = "$key = :$key";
        }
        
        $sql = "UPDATE pumps SET " . implode(', ', $setClauses) . " WHERE id = :id";
        
        // Tambahkan ID ke array data agar cocok dengan placeholder :id di WHERE
        $data['id'] = $id;
        
        $stmt = $pdo->prepare($sql);
        return $stmt->execute($data);
    }

    /**
     * Menghapus data pompa.
     */
    public static function delete($id) {
        $pdo = \Database::getInstance()->getConnection();
        $stmt = $pdo->prepare("DELETE FROM pumps WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }
}