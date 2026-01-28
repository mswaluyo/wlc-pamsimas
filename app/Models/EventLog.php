<?php

namespace app\Models;

use PDO;

class EventLog {
    
    /**
     * Mencatat event baru.
     */
    public static function create(int $controllerId, string $eventType, string $message): bool {
        $pdo = \Database::getInstance()->getConnection();
        $sql = "INSERT INTO event_logs (controller_id, event_type, message) VALUES (:controller_id, :event_type, :message)";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            ':controller_id' => $controllerId,
            ':event_type' => $eventType,
            ':message' => $message
        ]);
    }

    /**
     * Mencatat event dengan timestamp khusus (untuk log offline).
     */
    public static function createWithTimestamp(int $controllerId, int $timestamp, string $eventType, string $message): bool {
        $pdo = \Database::getInstance()->getConnection();
        $recordTime = date('Y-m-d H:i:s', $timestamp);
        
        $sql = "INSERT INTO event_logs (controller_id, event_type, message, event_time) VALUES (:controller_id, :event_type, :message, :event_time)";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            ':controller_id' => $controllerId,
            ':event_type' => $eventType,
            ':message' => $message,
            ':event_time' => $recordTime
        ]);
    }

    /**
     * Mengambil log event dengan paginasi.
     */
    public static function getPaginatedLogs(int $limit, int $offset): array {
        $pdo = \Database::getInstance()->getConnection();
        $sql = "
            SELECT 
                el.id,
                el.event_time,
                el.event_type,
                el.message,
                c.mac_address,
                t.tank_name
            FROM event_logs el
            JOIN controllers c ON el.controller_id = c.id
            LEFT JOIN tank_configurations t ON c.tank_id = t.id
            ORDER BY el.event_time DESC
            LIMIT :limit OFFSET :offset";
            
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Menghitung total jumlah log.
     */
    public static function countAll(): int {
        $pdo = \Database::getInstance()->getConnection();
        return (int) $pdo->query("SELECT COUNT(*) FROM event_logs")->fetchColumn();
    }

    /**
     * Mengambil log terbaru untuk controller tertentu (digunakan di detail page).
     */
    public static function getRecentByController(int $controllerId, int $limit = 50): array {
        $pdo = \Database::getInstance()->getConnection();
        $sql = "SELECT * FROM event_logs WHERE controller_id = :id ORDER BY event_time DESC LIMIT :limit";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':id', $controllerId, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}