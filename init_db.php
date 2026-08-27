<?php
// init_db.php - Automatic Database Migrations / Seeding for Railway
require_once __DIR__ . '/config/database.php';

echo "[init_db] Checking database connection and schema...\n";

try {
    $db = getDB();
    echo "[init_db] Connected to database '" . DB_NAME . "' on " . DB_HOST . ":" . DB_PORT . "\n";

    // Check if tables already exist (e.g. users table)
    $stmt = $db->query("SHOW TABLES LIKE 'users'");
    $tableExists = $stmt->fetch();

    if ($tableExists) {
        echo "[init_db] Schema already present ('users' table exists). Skipping initialization.\n";
        exit(0);
    }

    $sqlFile = __DIR__ . '/eventsphere.sql';
    if (!file_exists($sqlFile)) {
        echo "[init_db] Warning: eventsphere.sql not found at {$sqlFile}\n";
        exit(0);
    }

    echo "[init_db] Importing schema from {$sqlFile}...\n";
    $sqlContent = file_get_contents($sqlFile);

    // Filter out CREATE DATABASE and USE statements so it imports into the active database
    $lines = explode("\n", $sqlContent);
    $filteredLines = [];
    foreach ($lines as $line) {
        $trimmed = trim(strtoupper($line));
        if (str_starts_with($trimmed, 'CREATE DATABASE') || str_starts_with($trimmed, 'USE ')) {
            continue;
        }
        $filteredLines[] = $line;
    }

    $cleanSql = implode("\n", $filteredLines);

    // Execute multi-query using PDO or mysqli
    $db->exec($cleanSql);

    echo "[init_db] Database schema and seed data successfully initialized!\n";

} catch (Throwable $e) {
    echo "[init_db] Error during database initialization: " . $e->getMessage() . "\n";
    // We do not exit with fatal code so the web server can still attempt to run
}
