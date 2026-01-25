<?php

namespace app\Models;

class SensorLog {
    /**
     * Membuat entri log sensor baru di database.
     * Fungsi ini dirancang khusus untuk tabel `sensor_logs`.
     *
     * @param array $data Data log yang akan disimpan.
     * @return bool
     */
    public static function create(array $data): bool {
        $pdo = \Database::getInstance()->getConnection();
        
        // Tentukan apakah timestamp disediakan (untuk log offline) atau tidak (untuk log real-time)
        $sql = "INSERT INTO sensor_logs (controller_id, water_percentage, water_level, rssi, record_time) 
                VALUES (:controller_id, :water_percentage, :water_level, :rssi, :record_time)";
        
        $stmt = $pdo->prepare($sql);
        
        return $stmt->execute([
            ':controller_id' => $data['controller_id'],
            ':water_percentage' => $data['water_percentage'],
            ':water_level' => $data['water_level'],
            ':rssi' => $data['rssi'],
            // Jika record_time tidak ada, gunakan waktu server saat ini
            ':record_time' => $data['record_time'] ?? date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Mengambil log sensor dengan paginasi.
     *
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public static function getPaginatedLogs(int $limit, int $offset): array {
        $pdo = \Database::getInstance()->getConnection();
        $sql = "
            SELECT 
                sl.record_time,
                sl.water_percentage,
                t.tank_name
            FROM sensor_logs sl
            JOIN controllers c ON sl.controller_id = c.id
            LEFT JOIN tank_configurations t ON c.tank_id = t.id
            ORDER BY sl.record_time DESC
            LIMIT :limit OFFSET :offset";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':limit', $limit, \PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Menghitung total jumlah log sensor.
     */
    public static function countAll(): int {
        $pdo = \Database::getInstance()->getConnection();
        return (int) $pdo->query("SELECT COUNT(*) FROM sensor_logs")->fetchColumn();
    }
}