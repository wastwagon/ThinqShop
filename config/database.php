<?php
/**
 * Database Configuration
 * ThinQShopping Platform
 */

// Load environment variables
require_once __DIR__ . '/env-loader.php';

class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    private $charset;
    private $conn;

    public function __construct() {
        $this->host = $_ENV['DB_HOST'] ?? 'localhost';
        $this->db_name = $_ENV['DB_NAME'] ?? 'thinqshopping_db';
        $this->username = $_ENV['DB_USER'] ?? 'root';
        $this->password = $_ENV['DB_PASS'] ?? '';
        $this->charset = $_ENV['DB_CHARSET'] ?? 'utf8mb4';
    }

    public function getConnection() {
        $this->conn = null;

        try {
            // For MariaDB compatibility, don't use charset in DSN
            $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->db_name;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ];

            $this->conn = new PDO($dsn, $this->username, $this->password, $options);
            
            // Set character set after connection
            $this->conn->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
            $this->conn->exec("SET CHARACTER SET utf8mb4");
            
            // Set timezone to UTC for consistency
            try {
                $this->conn->exec("SET time_zone = '+00:00'");
            } catch (PDOException $e) {
                // If timezone setting fails, continue anyway
                error_log("Warning: Could not set database timezone: " . $e->getMessage());
            }
        } catch(PDOException $e) {
            error_log("Database Connection Error: " . $e->getMessage());
            if ($_ENV['APP_DEBUG'] ?? false) {
                die("Database Connection Error: " . $e->getMessage());
            } else {
                die("Database connection failed. Please contact support.");
            }
        }

        return $this->conn;
    }

    /**
     * Test database connection
     */
    public function testConnection() {
        try {
            $conn = $this->getConnection();
            return $conn !== null;
        } catch(Exception $e) {
            return false;
        }
    }
}
