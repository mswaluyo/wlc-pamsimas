<?php

namespace app\Models;

class Pump {
    /**
     * Mengambil semua data pompa dari database.
     * @return array
     */
    public static function getAll(): array {
        $pdo = \Database::getInstance()->getConnection();
        $sql = "SELECT id, pump_name, flow_rate_lps, power_watt, delay_seconds FROM pumps ORDER BY id ASC";
        $stmt = $pdo->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Mencari pompa berdasarkan ID.
     * @param int $id
     * @return mixed
     */
    public static function findById(int $id) {
        $pdo = \Database::getInstance()->getConnection();
        $sql = "SELECT * FROM pumps WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    /**
     * Membuat pompa baru.
     * @param array $data
     * @return bool
     */
    public static function create(array $data): bool {
        $pdo = \Database::getInstance()->getConnection();
        $sql = "INSERT INTO pumps (pump_name, flow_rate_lps, power_watt, delay_seconds) VALUES (:pump_name, :flow_rate_lps, :power_watt, :delay_seconds)";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute($data);
    }

    /**
     * Memperbarui data pompa.
     * @param int $id
     * @param array $data
     * @return bool
     */
    public static function update(int $id, array $data): bool {
        $data['id'] = $id;
        $pdo = \Database::getInstance()->getConnection();
        $sql = "UPDATE pumps SET pump_name = :pump_name, flow_rate_lps = :flow_rate_lps, power_watt = :power_watt, delay_seconds = :delay_seconds WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute($data);
    }
}