<?php
// Kelas untuk koneksi dan operasi database.

class Database {
    private static $instance = null;
    private $pdo;

    private function __construct() {
        // 1. Muat konfigurasi dari file .env
        $this->loadEnv();

        $dbHost = $_ENV['DB_HOST'] ?? getenv('DB_HOST');
        $dbName = $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?: ($_ENV['DB_DATABASE'] ?? getenv('DB_DATABASE'));
        $dbUser = $_ENV['DB_USER'] ?? getenv('DB_USER') ?: ($_ENV['DB_USERNAME'] ?? getenv('DB_USERNAME'));
        $dbPass = $_ENV['DB_PASS'] ?? getenv('DB_PASS') ?: ($_ENV['DB_PASSWORD'] ?? getenv('DB_PASSWORD'));

        // Validasi untuk memastikan variabel .env dimuat.
        if (empty($dbHost) || empty($dbName) || empty($dbUser)) {
            // Hentikan output apa pun yang mungkin sudah ada di buffer
            if (ob_get_level()) {
                ob_end_clean();
            }
            http_response_code(503);
            header('Content-Type: application/json');
            // Beri pesan yang lebih jelas
            echo json_encode(['status' => 'error', 'message' => 'Konfigurasi database (.env) tidak ditemukan atau tidak lengkap. Pastikan DB_HOST, DB_NAME (atau DB_DATABASE), dan DB_USER (atau DB_USERNAME) sudah diisi.']);
            exit();
        }

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
            
            // Deteksi apakah request adalah API atau Web biasa
            $isApi = (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '/api/') !== false) 
                     || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);

            http_response_code(503);
            
            if ($isApi) {
                header('Content-Type: application/json');
                echo json_encode(['status' => 'error', 'message' => 'Database Error: ' . $e->getMessage()]);
            } else {
                // Tampilan Error HTML Sederhana untuk User Web
                echo '<div style="font-family: sans-serif; text-align: center; padding: 50px;">';
                echo '<h1>Layanan Tidak Tersedia</h1>';
                echo '<p>Maaf, sistem sedang mengalami gangguan koneksi database.</p>';
                echo '</div>';
            }
            exit(); // Hentikan eksekusi skrip dengan anggun.
        }
    }

    /**
     * Fungsi manual untuk memuat file .env tanpa library tambahan
     */
    private function loadEnv() {
        // Asumsi file Database.php ada di folder /core, maka .env ada di folder root (naik satu level)
        $path = __DIR__ . '/../.env';

        if (!file_exists($path)) {
            return; // Jika tidak ada .env, gunakan default
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            // Lewati komentar
            if (strpos(trim($line), '#') === 0) {
                continue;
            }

            if (strpos($line, '=') !== false) {
                list($name, $value) = explode('=', $line, 2);
                $name = trim($name);
                $value = trim($value);

                // Set environment variable jika belum ada
                if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
                    if (function_exists('putenv')) {
                        putenv(sprintf('%s=%s', $name, $value));
                    }
                    $_ENV[$name] = $value;
                    $_SERVER[$name] = $value;
                }
            }
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
