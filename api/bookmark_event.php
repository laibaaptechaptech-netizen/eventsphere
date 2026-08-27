<?php
// api/bookmark_event.php - AJAX Event Bookmark Toggle
header('Content-Type: application/json');
require_once __DIR__ . '/../config/config.php';

if (!is_logged_in()) {
    echo json_encode(['status' => 'not_logged_in']);
    exit;
}

$user = current_user();
$db = getDB();

$input = json_decode(file_get_contents('php://input'), true);
$event_id = (int)($input['event_id'] ?? 0);

if ($event_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid Event ID']);
    exit;
}

// Check existing bookmark
$stmt = $db->prepare("SELECT id FROM event_bookmarks WHERE user_id = ? AND event_id = ?");
$stmt->execute([$user['id'], $event_id]);
$existing = $stmt->fetch();

if ($existing) {
    // Remove
    $del = $db->prepare("DELETE FROM event_bookmarks WHERE id = ?");
    $del->execute([$existing['id']]);
    echo json_encode(['success' => true, 'bookmarked' => false]);
} else {
    // Add
    $ins = $db->prepare("INSERT INTO event_bookmarks (user_id, event_id, created_at) VALUES (?, ?, NOW())");
    $ins->execute([$user['id'], $event_id]);
    echo json_encode(['success' => true, 'bookmarked' => true]);
}
