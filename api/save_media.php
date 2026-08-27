<?php
// api/save_media.php - AJAX Save Media to Profile Toggle
header('Content-Type: application/json');
require_once __DIR__ . '/../config/config.php';

if (!is_logged_in()) {
    echo json_encode(['status' => 'not_logged_in']);
    exit;
}

$user = current_user();
$db = getDB();

$input = json_decode(file_get_contents('php://input'), true);
$media_id = (int)($input['media_id'] ?? 0);

if ($media_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid Media ID']);
    exit;
}

$stmt = $db->prepare("SELECT id FROM saved_media WHERE user_id = ? AND media_id = ?");
$stmt->execute([$user['id'], $media_id]);
$existing = $stmt->fetch();

if ($existing) {
    $del = $db->prepare("DELETE FROM saved_media WHERE id = ?");
    $del->execute([$existing['id']]);
    echo json_encode(['success' => true, 'saved' => false]);
} else {
    $ins = $db->prepare("INSERT INTO saved_media (user_id, media_id, created_at) VALUES (?, ?, NOW())");
    $ins->execute([$user['id'], $media_id]);
    echo json_encode(['success' => true, 'saved' => true]);
}
