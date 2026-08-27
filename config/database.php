<?php
// config/database.php - Robust Database Connection for EventSphere
// On Railway: reads MYSQLHOST / MYSQLUSER / MYSQLPASSWORD / MYSQLDATABASE / MYSQLPORT
// On local XAMPP: falls back to localhost / root / '' / eventsphere_db / 3306

define('DB_HOST', getenv('MYSQLHOST')     ?: getenv('DB_HOST')  ?: 'localhost');
define('DB_USER', getenv('MYSQLUSER')     ?: getenv('DB_USER')  ?: 'root');
define('DB_PASS', getenv('MYSQLPASSWORD') ?: getenv('DB_PASS')  ?: '');
define('DB_NAME', getenv('MYSQLDATABASE') ?: getenv('DB_NAME')  ?: 'eventsphere_db');
define('DB_PORT', (int)(getenv('MYSQLPORT') ?: getenv('DB_PORT') ?: 3306));

class Database {
    private static $instance = null;
    private $pdo;

    private function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST
                 . ";port="      . DB_PORT
                 . ";dbname="    . DB_NAME
                 . ";charset=utf8mb4";

            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];

            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, $options);

        } catch (PDOException $e) {
            error_log("DB Connection Error: " . $e->getMessage());
            http_response_code(500);
            die('<h2 style="font-family:sans-serif;color:#c0392b;">Database connection failed. Please try again later.</h2>');
        }
    }

    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection(): PDO {
        return $this->pdo;
    }
}

// Convenience wrapper used throughout the app
function getDB(): PDO {
    return Database::getInstance()->getConnection();
}
