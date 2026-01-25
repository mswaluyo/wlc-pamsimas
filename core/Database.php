<?php
// Kelas untuk koneksi dan operasi database.

class Database {
    private static $instance = null;
    private $pdo;

    private function __construct() {
        $dbHost = getenv('DB_HOST') ?: 'localhost';
        $dbName = getenv('DB_NAME') ?: 'wlc_db';
        $dbUser = getenv('DB_USER') ?: 'root';
        $dbPass = getenv('DB_PASS') ?: '';

        $dsn = 'mysql:host=' . $dbHost . ';dbname=' . $dbName . ';charset=utf8mb4';
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $this->pdo = new PDO($dsn, $dbUser, $dbPass, $options);
        } catch (PDOException $e) {
            // Hentikan output apa pun yang mungkin sudah ada di buffer
            if (ob_get_level()) {
                ob_end_clean();
            }
            // Pada lingkungan produksi, sebaiknya log error ini, bukan menampilkannya.
            // Alih-alih membuat crash, kirim respons error 503 Service Unavailable.
            http_response_code(503);
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Layanan database tidak tersedia.']);
            exit(); // Hentikan eksekusi skrip dengan anggun.
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->pdo;
    }
}
