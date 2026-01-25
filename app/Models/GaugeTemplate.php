<?php

namespace app\Models;

use PDO;

class GaugeTemplate {
    public static function getAll() {
        $pdo = \Database::getInstance()->getConnection();
        $sql = "SELECT * FROM gauge_templates ORDER BY name ASC";
        return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function create(array $data): bool {
        $pdo = \Database::getInstance()->getConnection();
        $sql = "INSERT INTO gauge_templates (name, description, html_code, css_code, js_code) 
                VALUES (:name, :description, :html_code, :css_code, :js_code)";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            ':name' => $data['name'],
            ':description' => $data['description'],
            ':html_code' => $data['html_code'],
            ':css_code' => $data['css_code'],
            ':js_code' => $data['js_code']
        ]);
    }

    /**
     * Mencari template berdasarkan ID.
     */
    public static function findById(int $id) {
        $pdo = \Database::getInstance()->getConnection();
        $sql = "SELECT * FROM gauge_templates WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Memperbarui data template.
     */
    public static function update(int $id, array $data): bool {
        $pdo = \Database::getInstance()->getConnection();
        $sql = "UPDATE gauge_templates SET 
                    name = :name,
                    description = :description,
                    html_code = :html_code,
                    css_code = :css_code,
                    js_code = :js_code
                WHERE id = :id AND is_core = 0"; // Hanya bisa update template kustom
        
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            ':name' => $data['name'],
            ':description' => $data['description'],
            ':html_code' => $data['html_code'],
            ':css_code' => $data['css_code'],
            ':js_code' => $data['js_code'],
            ':id' => $id
        ]);
    }

    /**
     * Menghapus template.
     */
    public static function delete(int $id): bool {
        $pdo = \Database::getInstance()->getConnection();
        $sql = "DELETE FROM gauge_templates WHERE id = :id AND is_core = 0";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }
}