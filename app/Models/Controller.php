<?php

namespace app\Models;

use PDO;

class Controller {
    /**
     * Mengambil semua data controller beserta nama tangkinya.
     * @return array
     */
    public static function getAll() {
        // Menggunakan \Database karena kelas Database berada di namespace global.
        $pdo = \Database::getInstance()->getConnection();

        // Query untuk mengambil data controller dan menggabungkannya dengan nama tangki
        $sql = "
            SELECT
                c.id,
                c.mac_address,
                c.status,
                c.control_mode,
                c.last_update,
                c.rssi, 
                t.tank_name,
                t.height AS tank_height,
                c.full_tank_distance,
                sl.water_percentage AS latest_water_level
            FROM controllers c
            LEFT JOIN tank_configurations t ON c.tank_id = t.id
            LEFT JOIN (
                SELECT 
                    controller_id, 
                    water_percentage,
                    ROW_NUMBER() OVER(PARTITION BY controller_id ORDER BY record_time DESC) as rn
                FROM sensor_logs
            ) sl ON c.id = sl.controller_id AND sl.rn = 1
            ORDER BY t.tank_name ASC
        ";

        $stmt = $pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Mengambil data satu controller berdasarkan ID-nya.
     * @param int $id ID dari controller yang ingin diambil.
     * @return array|null Data controller atau null jika tidak ditemukan.
     */
    public static function findById(int $id) {
        $pdo = \Database::getInstance()->getConnection();

        $sql = "
            SELECT
                c.id,
                c.mac_address,
                c.status,
                c.control_mode,
                c.last_update,
                c.full_tank_distance,
                c.empty_tank_distance, -- PERBAIKAN: Ambil langsung dari tabel controllers
                c.firmware_version,
                c.firmware_build_date,
                c.trigger_percentage,
                c.rssi,
                c.on_duration,
                c.off_duration,
                t.tank_name, 
                p.pump_name,
                sl.water_percentage AS latest_water_level
            FROM controllers c
            LEFT JOIN tank_configurations t ON c.tank_id = t.id
            LEFT JOIN pumps p ON c.pump_id = p.id
            LEFT JOIN (
                SELECT 
                    controller_id, 
                    water_percentage,
                    ROW_NUMBER() OVER(PARTITION BY controller_id ORDER BY record_time DESC) as rn
                FROM sensor_logs
            ) sl ON c.id = sl.controller_id AND sl.rn = 1
            WHERE c.id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Mengambil data konfigurasi lengkap controller berdasarkan MAC address.
     * @param string $macAddress MAC address dari controller.
     * @return array|null Data konfigurasi atau null jika tidak ditemukan.
     */
    public static function findByMac(string $macAddress) {
        $pdo = \Database::getInstance()->getConnection();
        $sql = "
            SELECT
                c.id, -- Tambahkan ID untuk digunakan di controller
                c.status,
                c.control_mode,
                c.mode_update_command,
                c.last_update,
                c.full_tank_distance, 
                c.empty_tank_distance,
                c.trigger_percentage,
                c.on_duration,
                c.off_duration,
                c.restart_command,
                c.config_version
            FROM controllers c
            WHERE c.mac_address = :mac_address";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':mac_address' => $macAddress]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Mendaftarkan controller baru dan menghapusnya dari daftar terdeteksi.
     * @param array $data
     * @return bool
     */
    public static function create(array $data): bool {
        $pdo = \Database::getInstance()->getConnection();
        try {
            $pdo->beginTransaction();

            // 1. Masukkan ke tabel controllers
            // PERBAIKAN: Sesuaikan query INSERT dengan struktur tabel yang benar
            $sql = "INSERT INTO controllers (mac_address, tank_id, pump_id, sensor_id, full_tank_distance, empty_tank_distance, trigger_percentage, restart_command) VALUES (:mac_address, :tank_id, :pump_id, :sensor_id, :full_tank_distance, :empty_tank_distance, :trigger_percentage, :restart_command)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($data);

            return $pdo->commit();
        } catch (\Exception $e) {
            $pdo->rollBack();
            return false;
        }
    }

    /**
     * Mengambil semua MAC address yang sudah terdaftar di database.
     */
    public static function getAllMacAddresses(): array {
        $pdo = \Database::getInstance()->getConnection();
        $sql = "SELECT mac_address FROM controllers";
        $stmt = $pdo->query($sql);
        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }

    /**
     * Memperbarui data controller secara dinamis.
     * @param int $id ID dari controller.
     * @param array $data Data yang akan diperbarui dalam format ['kolom' => 'nilai'].
     * @return bool
     */
    public static function update(int $id, array $data): bool {
        if (empty($data)) {
            return false;
        }

        $pdo = \Database::getInstance()->getConnection();
        
        $setClauses = [];
        foreach (array_keys($data) as $key) {
            $setClauses[] = "$key = :$key";
        }
        
        $sql = "UPDATE controllers SET " . implode(', ', $setClauses) . " WHERE id = :id";
        
        $data['id'] = $id; // Tambahkan id ke array data untuk binding
        $stmt = $pdo->prepare($sql);
        return $stmt->execute($data);
    }

    /**
     * Menghapus data controller berdasarkan ID.
     * PERBAIKAN: Ini adalah implementasi yang benar dari fungsi delete.
     * @param int $id
     * @return bool
     */
    public static function delete(int $id): bool {
        $pdo = \Database::getInstance()->getConnection();
        $sql = "DELETE FROM controllers WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }
}
