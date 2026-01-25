<?php

namespace app\Models;

class EventLog {

    /**
     * Membuat entri log peristiwa baru dengan timestamp saat ini.
     *
     * @param int $controllerId ID controller.
     * @param string $eventType Jenis peristiwa (e.g., 'Device Reconnected').
     * @param string $message Deskripsi detail peristiwa.
     * @return bool
     */
    public static function create(int $controllerId, string $eventType, string $message): bool {
        $pdo = \Database::getInstance()->getConnection();
        
        $sql = "INSERT INTO event_logs (controller_id, event_type, message, event_time) 
                VALUES (:controller_id, :event_type, :message, :event_time)";
        
        $stmt = $pdo->prepare($sql);
        
        return $stmt->execute([
            ':controller_id' => $controllerId,
            ':event_type' => $eventType,
            ':message' => $message,
            ':event_time' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Membuat entri log peristiwa baru dengan timestamp spesifik (untuk log offline).
     *
     * @param int $controllerId
     * @param int $timestamp Unix timestamp dari peristiwa.
     * @param string $eventType
     * @param string $message
     * @return bool
     */
    public static function createWithTimestamp(int $controllerId, int $timestamp, string $eventType, string $message): bool {
        $pdo = \Database::getInstance()->getConnection();
        $sql = "INSERT INTO event_logs (controller_id, event_type, message, event_time) VALUES (?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([$controllerId, $eventType, $message, date('Y-m-d H:i:s', $timestamp)]);
    }
}