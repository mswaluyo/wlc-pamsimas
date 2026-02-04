<?php

namespace app\Models;

class PumpLog {

    /**
     * Mengambil riwayat log pompa dengan paginasi dan nama tangki.
     */
    public static function getPaginatedHistory($limit, $offset, $controllerId = null) {
        $pdo = \Database::getInstance()->getConnection();
        // Join ke tabel controllers dan tank_configurations untuk dapat nama tangki
        // PERBAIKAN: Ambil control_mode dari tabel log (pl) agar historis akurat.
        // Gunakan COALESCE untuk fallback ke c.control_mode bagi data lama yang belum punya record mode.
        $sql = "SELECT pl.*, t.tank_name, COALESCE(pl.control_mode, c.control_mode) as control_mode 
                FROM pump_logs pl
                LEFT JOIN controllers c ON pl.controller_id = c.id
                LEFT JOIN tank_configurations t ON c.tank_id = t.id";
        
        if ($controllerId) {
            $sql .= " WHERE pl.controller_id = :controller_id";
        }

        $sql .= " ORDER BY pl.timestamp DESC LIMIT :limit OFFSET :offset";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':limit', (int)$limit, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, \PDO::PARAM_INT);
        if ($controllerId) {
            $stmt->bindValue(':controller_id', (int)$controllerId, \PDO::PARAM_INT);
        }
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    public static function countAll($controllerId = null) {
        $pdo = \Database::getInstance()->getConnection();
        $sql = "SELECT COUNT(*) FROM pump_logs";
        if ($controllerId) {
            $sql .= " WHERE controller_id = :controller_id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':controller_id' => $controllerId]);
            return $stmt->fetchColumn();
        }
        $stmt = $pdo->query($sql);
        return $stmt->fetchColumn();
    }

    /**
     * Mencatat log status pompa baru (Real-time).
     * Menghitung durasi dari status sebelumnya.
     */
    public static function create($controllerId, $isOn) {
        return self::createWithTimestamp($controllerId, time(), $isOn);
    }

    /**
     * Mencatat log dengan timestamp spesifik (untuk log offline atau real-time).
     */
    public static function createWithTimestamp($controllerId, $timestamp, $isOn) {
        $pdo = \Database::getInstance()->getConnection();
        $status = $isOn ? 'ON' : 'OFF';
        $recordTime = date('Y-m-d H:i:s', $timestamp);

        // CEK DUPLIKASI: Jangan simpan jika sudah ada log dengan timestamp yang sama persis
        // Ini mencegah spam log jika perangkat mengirim ulang data offline yang sama
        $stmtCheck = $pdo->prepare("SELECT id FROM pump_logs WHERE controller_id = :id AND timestamp = :ts LIMIT 1");
        $stmtCheck->execute([':id' => $controllerId, ':ts' => $recordTime]);
        if ($stmtCheck->fetch()) {
            return false; // Skip duplicate
        }

        // 0. Ambil mode saat ini dari controller untuk dicatat dalam sejarah
        $stmtMode = $pdo->prepare("SELECT control_mode FROM controllers WHERE id = :id");
        $stmtMode->execute([':id' => $controllerId]);
        $currentMode = $stmtMode->fetchColumn(); 

        // 1. Ambil log terakhir untuk menghitung durasi status sebelumnya
        // Menggunakan timestamp <= recordTime untuk menangani event di detik yang sama
        $stmt = $pdo->prepare("SELECT timestamp FROM pump_logs WHERE controller_id = :id AND timestamp <= :current_time ORDER BY timestamp DESC LIMIT 1");
        $stmt->execute([
            ':id' => $controllerId,
            ':current_time' => $recordTime
        ]);
        $lastLog = $stmt->fetch();
        
        $duration = null;
        if ($lastLog) {
            $lastTime = strtotime($lastLog['timestamp']);
            // Hitung selisih detik antara event sekarang dan event sebelumnya
            $duration = $timestamp - $lastTime;
            if ($duration < 0) $duration = 0; // Cegah durasi negatif
        }

        // 2. Simpan log baru
        // PERBAIKAN: Simpan juga control_mode ke dalam tabel log
        $sql = "INSERT INTO pump_logs (controller_id, pump_status, duration_seconds, timestamp, control_mode) VALUES (:controller_id, :pump_status, :duration, :timestamp, :control_mode)";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            ':controller_id' => $controllerId,
            ':pump_status' => $status,
            ':duration' => $duration,
            ':timestamp' => $recordTime,
            ':control_mode' => $currentMode
        ]);
    }

    /**
     * Menghapus log yang lebih tua dari jumlah hari tertentu secara otomatis.
     */
    public static function cleanupOldLogs($days = 30) {
        $pdo = \Database::getInstance()->getConnection();
        $sql = "DELETE FROM pump_logs WHERE timestamp < DATE_SUB(NOW(), INTERVAL :days DAY)";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':days', (int)$days, \PDO::PARAM_INT);
        $stmt->execute();
    }

    /**
     * Mengambil timestamp log terakhir untuk menghitung durasi status saat ini.
     */
    public static function getLastLogTime($controllerId) {
        $pdo = \Database::getInstance()->getConnection();
        // Tambahkan filter <= NOW() untuk mencegah log masa depan (akibat selisih jam) merusak timer
        $stmt = $pdo->prepare("SELECT timestamp FROM pump_logs WHERE controller_id = :id AND timestamp <= NOW() ORDER BY timestamp DESC LIMIT 1");
        $stmt->execute([':id' => $controllerId]);
        return $stmt->fetchColumn();
    }
}