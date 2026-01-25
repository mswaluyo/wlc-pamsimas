<?php

namespace app\Models;

use PDO;

class PumpLog {
    /**
     * Membuat log status pompa baru dan menghitung durasi jika pompa dimatikan.
     * Disesuaikan untuk kolom 'pump_status' dan 'timestamp'.
     * @param int $controller_id
     * @param bool $is_on
     * @return bool
     */
    public static function create(int $controller_id, bool $is_on): bool {
        $pdo = \Database::getInstance()->getConnection();
        $duration = null;

        // Jika status baru adalah OFF, hitung durasinya.
        if (!$is_on) {
            // Cari log 'ON' terakhir yang belum memiliki pasangan 'OFF'.
            $sql_find_on = "
                SELECT timestamp FROM pump_logs 
                WHERE controller_id = :controller_id AND (pump_status = 1 OR pump_status = 'ON')
                AND NOT EXISTS (
                    SELECT 1 FROM pump_logs AS pl2 WHERE pl2.controller_id = pump_logs.controller_id AND pl2.pump_status = 0 AND pl2.timestamp > pump_logs.timestamp
                )
                ORDER BY timestamp DESC LIMIT 1";
            $stmt_find = $pdo->prepare($sql_find_on);
            $stmt_find->execute([':controller_id' => $controller_id]);
            $last_on_log = $stmt_find->fetch(PDO::FETCH_ASSOC);

            if ($last_on_log) {
                $on_time = strtotime($last_on_log['timestamp']);
                $off_time = time(); // Waktu saat ini
                $duration = $off_time - $on_time;
            }
        }

        // Masukkan log baru ke database.
        $sql_insert = "INSERT INTO pump_logs (controller_id, pump_status, duration_seconds) VALUES (:controller_id, :status, :duration)";
        $stmt_insert = $pdo->prepare($sql_insert);
        return $stmt_insert->execute([
            ':controller_id' => $controller_id,
            ':status' => $is_on ? 1 : 0,
            ':duration' => $duration
        ]);
    }

    /**
     * Membuat log dari data offline dengan timestamp yang sudah ada.
     * Disesuaikan untuk kolom 'pump_status' dan 'timestamp'.
     * @param int $controller_id
     * @param int $timestamp_unix
     * @param bool $is_on
     * @return bool
     */
    public static function createWithTimestamp(int $controller_id, int $timestamp_unix, bool $is_on): bool {
        $pdo = \Database::getInstance()->getConnection();
        $duration = null;
        $record_time_mysql = date('Y-m-d H:i:s', $timestamp_unix);

        if (!$is_on) {
            // Cari log 'ON' terakhir sebelum timestamp log 'OFF' ini yang belum memiliki pasangan 'OFF'.
            $sql_find_on = "
                SELECT timestamp FROM pump_logs 
                WHERE controller_id = :controller_id AND (pump_status = 1 OR pump_status = 'ON') AND timestamp < :record_time
                AND NOT EXISTS (
                    SELECT 1 FROM pump_logs AS pl2 WHERE pl2.controller_id = pump_logs.controller_id AND pl2.pump_status = 0 AND pl2.timestamp > pump_logs.timestamp AND pl2.timestamp < :record_time
                )
                ORDER BY timestamp DESC LIMIT 1";
            $stmt_find = $pdo->prepare($sql_find_on);
            $stmt_find->execute([':controller_id' => $controller_id, ':record_time' => $record_time_mysql]);
            $last_on_log = $stmt_find->fetch(PDO::FETCH_ASSOC);

            if ($last_on_log) {
                $on_time = strtotime($last_on_log['timestamp']);
                $duration = $timestamp_unix - $on_time;
            }
        }

        $sql_insert = "INSERT INTO pump_logs (controller_id, pump_status, duration_seconds, timestamp) VALUES (:controller_id, :status, :duration, :timestamp)";
        $stmt_insert = $pdo->prepare($sql_insert);
        return $stmt_insert->execute([
            ':controller_id' => $controller_id,
            ':status' => $is_on ? 1 : 0,
            ':duration' => $duration,
            ':timestamp' => $record_time_mysql
        ]);
    }

    /**
     * Mengambil riwayat log pompa dengan durasi dan paginasi.
     * Disesuaikan untuk kolom 'pump_status' dan 'timestamp'.
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public static function getPaginatedHistory(int $limit, int $offset): array {
        $pdo = \Database::getInstance()->getConnection();
        $sql = "
            SELECT 
                pl.timestamp AS record_time, -- Gunakan alias agar view tidak perlu diubah
                pl.pump_status AS status, -- Gunakan alias agar view tidak perlu diubah
                pl.duration_seconds,
                t.tank_name
            FROM pump_logs pl
            JOIN controllers c ON pl.controller_id = c.id
            LEFT JOIN tank_configurations t ON c.tank_id = t.id
            ORDER BY pl.timestamp DESC
            LIMIT :limit OFFSET :offset";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Menghitung total jumlah log pompa.
     */
    public static function countAll(): int {
        $pdo = \Database::getInstance()->getConnection();
        return (int) $pdo->query("SELECT COUNT(*) FROM pump_logs")->fetchColumn();
    }
}
