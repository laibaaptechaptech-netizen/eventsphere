<?php
// health.php - Lightweight Railway Healthcheck Endpoint
header('Content-Type: application/json');

$dbStatus = 'disconnected';
$dbMessage = '';

try {
    require_once __DIR__ . '/config/database.php';
    $db = getDB();
    $db->query("SELECT 1");
    $dbStatus = 'connected';
} catch (Throwable $e) {
    $dbMessage = $e->getMessage();
}

http_response_code(200);
echo json_encode([
    'status' => 'ok',
    'timestamp' => date('c'),
    'database' => [
        'status' => $dbStatus,
        'message' => $dbMessage
    ]
]);
