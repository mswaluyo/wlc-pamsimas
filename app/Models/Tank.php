<?php

namespace app\Models;

class Tank {
    /**
     * Mengambil semua data konfigurasi tangki dari database.
     * @return array
     */
    public static function getAll(): array {
        $pdo = \Database::getInstance()->getConnection();
        $sql = "
            SELECT 
                tc.id,
                tc.tank_name,
                tc.tank_shape,
                tc.height,
                trd.length,
                trd.width,
                tcd.diameter
            FROM tank_configurations tc
            LEFT JOIN tank_rectangular_dimensions trd ON tc.rectangular_dim_id = trd.id
            LEFT JOIN tank_circular_dimensions tcd ON tc.circular_dim_id = tcd.id
            ORDER BY tc.id ASC
        ";
        $stmt = $pdo->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Mencari konfigurasi tangki berdasarkan ID.
     * @param int $id
     * @return mixed
     */
    public static function findById(int $id) {
        $pdo = \Database::getInstance()->getConnection();
        $sql = "
            SELECT 
                tc.id, tc.tank_name, tc.tank_shape, tc.height,
                tc.rectangular_dim_id, tc.circular_dim_id,
                trd.length, trd.width,
                tcd.diameter
            FROM tank_configurations tc
            LEFT JOIN tank_rectangular_dimensions trd ON tc.rectangular_dim_id = trd.id
            LEFT JOIN tank_circular_dimensions tcd ON tc.circular_dim_id = tcd.id
            WHERE tc.id = :id
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    /**
     * Membuat konfigurasi tangki baru menggunakan transaksi.
     * @param array $data
     * @return bool
     */
    public static function create(array $data): bool {
        $pdo = \Database::getInstance()->getConnection();
        try {
            $pdo->beginTransaction();

            $rectDimId = null;
            $circDimId = null;

            if ($data['tank_shape'] === 'kotak') {
                $sql = "INSERT INTO tank_rectangular_dimensions (length, width) VALUES (:length, :width)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([':length' => $data['length'], ':width' => $data['width']]);
                $rectDimId = $pdo->lastInsertId();
            } else if ($data['tank_shape'] === 'bulat') {
                $sql = "INSERT INTO tank_circular_dimensions (diameter) VALUES (:diameter)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([':diameter' => $data['diameter']]);
                $circDimId = $pdo->lastInsertId();
            }

            $sql = "INSERT INTO tank_configurations (tank_name, tank_shape, height, rectangular_dim_id, circular_dim_id) VALUES (:tank_name, :tank_shape, :height, :rectangular_dim_id, :circular_dim_id)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':tank_name' => $data['tank_name'],
                ':tank_shape' => $data['tank_shape'],
                ':height' => $data['height'],
                ':rectangular_dim_id' => $rectDimId,
                ':circular_dim_id' => $circDimId
            ]);

            return $pdo->commit();
        } catch (\Exception $e) {
            $pdo->rollBack();
            // Sebaiknya log error $e->getMessage() di sini
            return false;
        }
    }

    /**
     * Memperbarui konfigurasi tangki menggunakan transaksi.
     * @param int $id
     * @param array $data
     * @return bool
     */
    public static function update(int $id, array $data): bool {
        $pdo = \Database::getInstance()->getConnection();
        try {
            $pdo->beginTransaction();

            // Dapatkan data lama untuk referensi
            $oldTank = self::findById($id);

            $rectDimId = $oldTank['rectangular_dim_id'];
            $circDimId = $oldTank['circular_dim_id'];

            // Logika untuk memperbarui atau mengubah dimensi
            if ($data['tank_shape'] === 'kotak') {
                if ($oldTank['tank_shape'] === 'bulat' && $circDimId) {
                    // Hapus dimensi bulat lama, buat dimensi kotak baru
                    $pdo->prepare("DELETE FROM tank_circular_dimensions WHERE id = ?")->execute([$circDimId]);
                    $circDimId = null;
                }
                if ($rectDimId) {
                    $pdo->prepare("UPDATE tank_rectangular_dimensions SET length = ?, width = ? WHERE id = ?")->execute([$data['length'], $data['width'], $rectDimId]);
                } else {
                    $stmt = $pdo->prepare("INSERT INTO tank_rectangular_dimensions (length, width) VALUES (?, ?)");
                    $stmt->execute([$data['length'], $data['width']]);
                    $rectDimId = $pdo->lastInsertId();
                }
            } else if ($data['tank_shape'] === 'bulat') {
                if ($oldTank['tank_shape'] === 'kotak' && $rectDimId) {
                    // Hapus dimensi kotak lama, buat dimensi bulat baru
                    $pdo->prepare("DELETE FROM tank_rectangular_dimensions WHERE id = ?")->execute([$rectDimId]);
                    $rectDimId = null;
                }
                if ($circDimId) {
                    $pdo->prepare("UPDATE tank_circular_dimensions SET diameter = ? WHERE id = ?")->execute([$data['diameter'], $circDimId]);
                } else {
                    $stmt = $pdo->prepare("INSERT INTO tank_circular_dimensions (diameter) VALUES (?)");
                    $stmt->execute([$data['diameter']]);
                    $circDimId = $pdo->lastInsertId();
                }
            }

            $sql = "UPDATE tank_configurations SET tank_name = ?, tank_shape = ?, height = ?, rectangular_dim_id = ?, circular_dim_id = ? WHERE id = ?";
            $pdo->prepare($sql)->execute([$data['tank_name'], $data['tank_shape'], $data['height'], $rectDimId, $circDimId, $id]);

            return $pdo->commit();
        } catch (\Exception $e) {
            $pdo->rollBack();
            return false;
        }
    }
}