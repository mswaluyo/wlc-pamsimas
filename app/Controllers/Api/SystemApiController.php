<?php

namespace app\Controllers\Api;

class SystemApiController {

    /**
     * Melakukan backup database otomatis dan menyimpannya ke server.
     * Endpoint ini dilindungi oleh BACKUP_TOKEN.
     */
    public function autoBackup() {
        header('Content-Type: application/json');

        // 1. Validasi Token Keamanan
        $token = $_GET['token'] ?? '';
        $validToken = getenv('BACKUP_TOKEN');

        if (empty($validToken) || $token !== $validToken) {
            http_response_code(403);
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized: Invalid or missing token.']);
            return;
        }

        // 2. Persiapan Folder Penyimpanan
        $storagePath = defined('ROOT_PATH') ? ROOT_PATH . '/storage/backups' : dirname(__DIR__, 3) . '/storage/backups';
        if (!is_dir($storagePath)) {
            mkdir($storagePath, 0755, true);
        }

        // 3. Nama File
        $dbName = getenv('DB_NAME') ?: 'wlc_db';
        $filename = 'autobackup_' . $dbName . '_' . date('Y-m-d_H-i-s') . '.sql';
        $filePath = $storagePath . '/' . $filename;

        try {
            $pdo = \Database::getInstance()->getConnection();
            
            // Buffer output untuk ditulis ke file
            ob_start();

            // --- Logika Dump Database ---
            $tables = [];
            $result = $pdo->query('SHOW TABLES');
            while ($row = $result->fetch(\PDO::FETCH_NUM)) {
                $tables[] = $row[0];
            }
            
            echo "SET FOREIGN_KEY_CHECKS=0;\n\n";

            foreach ($tables as $table) {
                $row = $pdo->query('SHOW CREATE TABLE ' . $table)->fetch(\PDO::FETCH_NUM);
                echo "DROP TABLE IF EXISTS `{$table}`;\n";
                echo $row[1] . ";\n\n";
                
                $rows = $pdo->query('SELECT * FROM ' . $table);
                while ($row = $rows->fetch(\PDO::FETCH_ASSOC)) {
                    $keys = array_map(function ($key) { return "`$key`"; }, array_keys($row));
                    $values = array_map(function ($value) use ($pdo) {
                        return ($value === null) ? 'NULL' : $pdo->quote($value);
                    }, array_values($row));
                    
                    echo "INSERT INTO `{$table}` (" . implode(', ', $keys) . ") VALUES (" . implode(', ', $values) . ");\n";
                }
                echo "\n";
            }
            
            echo "SET FOREIGN_KEY_CHECKS=1;\n";
            // --- Akhir Logika Dump ---

            $sqlContent = ob_get_clean();
            
            if (file_put_contents($filePath, $sqlContent) === false) {
                throw new \Exception("Gagal menulis file ke storage.");
            }

            // 4. Rotasi Backup (Hapus file lama, sisakan 10 terakhir)
            $files = glob($storagePath . '/autobackup_*.sql');
            if (count($files) > 10) {
                // Urutkan berdasarkan waktu modifikasi (terlama di awal)
                array_multisort(array_map('filemtime', $files), SORT_ASC, $files);
                // Hapus file berlebih
                $filesToDelete = array_slice($files, 0, count($files) - 10);
                foreach ($filesToDelete as $file) {
                    unlink($file);
                }
            }

            echo json_encode(['status' => 'success', 'message' => 'Backup created successfully.', 'file' => $filename]);

        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }
}