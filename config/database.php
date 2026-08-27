<?php
// config/database.php - Robust Database Connection for EventSphere (Railway + Localhost)

$dbUrl = getenv('MYSQL_URL') ?: getenv('DATABASE_URL') ?: ($_ENV['MYSQL_URL'] ?? $_ENV['DATABASE_URL'] ?? null);

if ($dbUrl) {
    $dbParts = parse_url($dbUrl);
    $dbHost = $dbParts['host'] ?? 'localhost';
    $dbPort = $dbParts['port'] ?? 3306;
    $dbUser = $dbParts['user'] ?? 'root';
    $dbPass = $dbParts['pass'] ?? '';
    $dbName = ltrim($dbParts['path'] ?? 'eventsphere_db', '/');
} else {
    $dbHost = getenv('MYSQLHOST') ?: getenv('MYSQL_HOST') ?: getenv('DB_HOST') ?: 'localhost';
    $dbPort = (int)(getenv('MYSQLPORT') ?: getenv('MYSQL_PORT') ?: getenv('DB_PORT') ?: 3306);
    $dbUser = getenv('MYSQLUSER') ?: getenv('MYSQL_USER') ?: getenv('DB_USER') ?: 'root';
    $dbPass = getenv('MYSQLPASSWORD') ?: getenv('MYSQL_PASSWORD') ?: getenv('DB_PASS') ?: getenv('DB_PASSWORD') ?: '';
    $dbName = getenv('MYSQLDATABASE') ?: getenv('MYSQL_DATABASE') ?: getenv('DB_NAME') ?: 'eventsphere_db';
}

define('DB_HOST', $dbHost);
define('DB_USER', $dbUser);
define('DB_PASS', $dbPass);
define('DB_NAME', $dbName);
define('DB_PORT', $dbPort);

class Database {
    private static $instance = null;
    private $pdo;

    private function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];

            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log("DB Connection Error: " . $e->getMessage());
            if (basename($_SERVER['SCRIPT_NAME'] ?? '') === 'health.php') {
                throw $e;
            }
            http_response_code(200);
            die('<div style="font-family:sans-serif;text-align:center;padding:50px;background:#0f172a;color:#f87171;min-height:100vh;">
                <h2>⚠️ Database Connection Pending</h2>
                <p style="color:#94a3b8;">Unable to connect to MySQL database (' . htmlspecialchars(DB_HOST) . ':' . DB_PORT . ').</p>
                <p style="color:#94a3b8;">Please ensure Railway MySQL environment variables (<code>MYSQLHOST</code>, <code>MYSQLPASSWORD</code>, etc.) are attached to this web service.</p>
            </div>');
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

function getDB(): PDO {
    return Database::getInstance()->getConnection();
}
