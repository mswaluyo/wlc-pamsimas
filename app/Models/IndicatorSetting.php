<?php

namespace app\Models;

use PDO;

class IndicatorSetting {
    /**
     * Mengambil pengaturan indikator saat ini (selalu baris pertama).
     * @return array|null
     */
    public static function getSettings() {
        $pdo = \Database::getInstance()->getConnection();
        $sql = "SELECT * FROM indicator_settings WHERE id = 1 LIMIT 1";
        $stmt = $pdo->query($sql);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Memperbarui pengaturan indikator.
     * @param array $data
     * @return bool
     */
    public static function updateSettings(array $data): bool {
        $pdo = \Database::getInstance()->getConnection();
        $sql = "UPDATE indicator_settings SET 
                    threshold_low = :threshold_low,
                    color_low = :color_low,
                    threshold_medium = :threshold_medium,
                    color_medium = :color_medium,
                    color_high = :color_high,
                    active_template_id = :active_template_id
                WHERE id = 1";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute($data);
    }
}