<?php

namespace app\Models;

class AdminLog {
    /**
     * Mencatat aktivitas admin ke database.
     * @param int $user_id ID pengguna yang melakukan aksi.
     * @param string $action Nama aksi (misal: 'Backup Database').
     * @param string $details Detail tambahan (opsional).
     * @return bool
     */
    public static function create($user_id, $action, $details = '') {
        $pdo = \Database::getInstance()->getConnection();
        $sql = "INSERT INTO admin_logs (user_id, action, details, created_at) VALUES (:user_id, :action, :details, NOW())";
        
        try {
            $stmt = $pdo->prepare($sql);
            return $stmt->execute([
                ':user_id' => $user_id,
                ':action' => $action,
                ':details' => $details
            ]);
        } catch (\PDOException $e) {
            if ($e->getCode() == '42S02') { // SQLSTATE 42S02: Table not found
                self::ensureTableExists();
                // Coba lagi setelah membuat tabel
                return self::create($user_id, $action, $details);
            }
            throw $e;
        }
    }

    /**
     * Mengambil riwayat log admin dengan paginasi.
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public static function getPaginatedLogs(int $limit, int $offset) {
        $pdo = \Database::getInstance()->getConnection();
        $sql = "
            SELECT al.*, u.full_name 
            FROM admin_logs al
            LEFT JOIN users u ON al.user_id = u.id
            ORDER BY al.created_at DESC
            LIMIT :limit OFFSET :offset";
            
        try {
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':limit', $limit, \PDO::PARAM_INT);
            $stmt->bindParam(':offset', $offset, \PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            if ($e->getCode() == '42S02') { // SQLSTATE 42S02: Table not found
                self::ensureTableExists();
                // Coba lagi setelah membuat tabel
                return self::getPaginatedLogs($limit, $offset);
            }
            throw $e;
        }
    }

    /**
     * Menghitung total log admin.
     */
    public static function countAll(): int {
        $pdo = \Database::getInstance()->getConnection();
        try {
            return (int) $pdo->query("SELECT COUNT(*) FROM admin_logs")->fetchColumn();
        } catch (\PDOException $e) {
            if ($e->getCode() == '42S02') {
                self::ensureTableExists();
                return 0;
            }
            throw $e;
        }
    }

    private static function ensureTableExists() {
        $pdo = \Database::getInstance()->getConnection();
        $sql = "CREATE TABLE IF NOT EXISTS `admin_logs` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `user_id` int(11) NOT NULL,
          `action` varchar(255) NOT NULL,
          `details` text DEFAULT NULL,
          `created_at` datetime DEFAULT current_timestamp(),
          PRIMARY KEY (`id`),
          CONSTRAINT `fk_admin_logs_users` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        $pdo->exec($sql);
    }
}