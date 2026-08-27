<?php
// config/config.php - Application Configuration & Core Helpers

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/database.php';

// Application Base URL detection
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$scriptName = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));

// Root path detection for XAMPP subfolder /techwizz
$baseDir = rtrim(preg_replace('#/(admin|organizer|student|auth|api|includes|assets|uploads).*$#i', '', $scriptName), '/');
define('BASE_URL', $protocol . $host . $baseDir);
define('SITE_NAME', 'EventSphere 3D');
define('SITE_TAGLINE', 'Next-Gen Interactive Campus Event Platform');

// Helper: Sanitize string input
function clean_input($data) {
    return htmlspecialchars(trim($data ?? ''), ENT_QUOTES, 'UTF-8');
}

// Helper: Flash message handlers
function set_flash($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type, // 'success', 'error', 'info', 'warning'
        'message' => $message
    ];
}

function get_flash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// Helper: Current logged-in user
function current_user() {
    return $_SESSION['user'] ?? null;
}

function is_logged_in() {
    return isset($_SESSION['user']) && !empty($_SESSION['user']['id']);
}

function user_role() {
    return $_SESSION['user']['role'] ?? 'guest';
}

// Helper: Format Date & Time
function format_event_date($date_str) {
    return date('l, M d, Y', strtotime($date_str));
}

function format_event_time($time_str) {
    return date('h:i A', strtotime($time_str));
}

function time_ago($datetime) {
    $time = strtotime($datetime);
    $diff = time() - $time;
    if ($diff < 60) return 'Just now';
    if ($diff < 3600) return round($diff / 60) . ' mins ago';
    if ($diff < 86400) return round($diff / 3600) . ' hrs ago';
    if ($diff < 604800) return round($diff / 86400) . ' days ago';
    return date('M d, Y', $time);
}

// Helper: Get Live Seat Capacity & Registered Count
function get_event_capacity_info($event_id) {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT 
            e.max_capacity,
            e.event_date,
            e.registration_cutoff,
            e.status as event_status,
            COUNT(CASE WHEN r.status IN ('confirmed', 'attended') THEN 1 END) as confirmed_count,
            COUNT(CASE WHEN r.status = 'waitlisted' THEN 1 END) as waitlist_count
        FROM events e
        LEFT JOIN registrations r ON e.id = r.event_id
        WHERE e.id = ?
        GROUP BY e.id
    ");
    $stmt->execute([$event_id]);
    $data = $stmt->fetch();

    if (!$data) return null;

    $max = (int)$data['max_capacity'];
    $confirmed = (int)$data['confirmed_count'];
    $waitlisted = (int)$data['waitlist_count'];
    $remaining = max(0, $max - $confirmed);
    $percentage = $max > 0 ? min(100, round(($confirmed / $max) * 100)) : 0;
    
    $is_cutoff_passed = strtotime($data['registration_cutoff']) < time();
    $is_full = ($remaining <= 0);

    return [
        'max_capacity' => $max,
        'confirmed' => $confirmed,
        'waitlisted' => $waitlisted,
        'remaining' => $remaining,
        'percentage' => $percentage,
        'is_full' => $is_full,
        'is_cutoff_passed' => $is_cutoff_passed,
        'is_open' => ($data['event_status'] === 'approved' && !$is_cutoff_passed)
    ];
}

// Helper: Auto-promote waitlisted student when a slot opens
function promote_next_waitlisted($event_id) {
    $db = getDB();
    
    // Check if slot available
    $info = get_event_capacity_info($event_id);
    if (!$info || $info['remaining'] <= 0) {
        return false;
    }

    // Get earliest waitlisted registration
    $stmt = $db->prepare("
        SELECT r.id, r.user_id, e.title 
        FROM registrations r 
        JOIN events e ON r.event_id = e.id
        WHERE r.event_id = ? AND r.status = 'waitlisted' 
        ORDER BY r.created_at ASC 
        LIMIT 1
    ");
    $stmt->execute([$event_id]);
    $waitlisted = $stmt->fetch();

    if ($waitlisted) {
        // Promote to confirmed
        $update = $db->prepare("UPDATE registrations SET status = 'confirmed' WHERE id = ?");
        $update->execute([$waitlisted['id']]);

        // Send Notification to promoted student
        create_notification(
            $waitlisted['user_id'],
            '🎉 Seat Confirmed from Waitlist!',
            'Great news! A slot opened up for ' . $waitlisted['title'] . ' and your registration has been upgraded to CONFIRMED.',
            BASE_URL . '/student/my_events.php',
            'success'
        );
        return true;
    }
    return false;
}

// Helper: Send System / Targeted Notification
function create_notification($user_id, $title, $message, $link = '#', $type = 'info') {
    try {
        $db = getDB();
        $stmt = $db->prepare("
            INSERT INTO notifications (user_id, title, message, link, type, is_read, created_at) 
            VALUES (?, ?, ?, ?, ?, 0, NOW())
        ");
        $stmt->execute([$user_id, $title, $message, $link, $type]);
        return true;
    } catch (Exception $e) {
        return false;
    }
}

// Helper: Generate dynamic QR code URL (using high quality SVG QR API)
function get_qr_image_url($data, $size = 200) {
    return 'https://api.qrserver.com/v1/create-qr-code/?size=' . $size . 'x' . $size . '&color=8b5cf6&bgcolor=0f172a&data=' . urlencode($data);
}

// Helper: CSRF token generation & validation
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token ?? '');
}
