<?php

namespace app\Models;

class Sensor {
    /**
     * Mengambil semua data sensor dari database.
     * @return array
     */
    public static function getAll(): array {
        $pdo = \Database::getInstance()->getConnection();
        $sql = "SELECT id, sensor_name, sensor_type, full_tank_distance, trigger_percentage, created_at FROM sensors ORDER BY id ASC";
        $stmt = $pdo->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Mencari sensor berdasarkan ID.
     * @param int $id
     * @return mixed
     */
    public static function findById(int $id) {
        $pdo = \Database::getInstance()->getConnection();
        $sql = "SELECT * FROM sensors WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    /**
     * Membuat sensor baru.
     * @param array $data
     * @return bool
     */
    public static function create(array $data): bool {
        $pdo = \Database::getInstance()->getConnection();
        $sql = "INSERT INTO sensors (sensor_name, sensor_type, full_tank_distance, trigger_percentage) VALUES (:sensor_name, :sensor_type, :full_tank_distance, :trigger_percentage)";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute($data);
    }

    /**
     * Memperbarui data sensor.
     * @param int $id
     * @param array $data
     * @return bool
     */
    public static function update(int $id, array $data): bool {
        $data['id'] = $id;
        $pdo = \Database::getInstance()->getConnection();
        $sql = "UPDATE sensors SET sensor_name = :sensor_name, sensor_type = :sensor_type, full_tank_distance = :full_tank_distance, trigger_percentage = :trigger_percentage WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute($data);
    }
}