<?php
// api/cancel_registration.php - Registration Cancellation & Waitlist Auto-Promotion Engine
require_once __DIR__ . '/../config/config.php';

if (!is_logged_in() || user_role() !== 'student') {
    set_flash('error', 'Authentication required.');
    header("Location: " . BASE_URL . "/auth/login.php");
    exit;
}

$user = current_user();
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reg_id = (int)($_POST['registration_id'] ?? 0);
    $csrf = $_POST['csrf_token'] ?? '';

    if (!verify_csrf($csrf)) {
        set_flash('error', 'Security token expired.');
        header("Location: " . BASE_URL . "/student/my_events.php");
        exit;
    }

    $stmt = $db->prepare("SELECT r.*, e.title, e.event_date FROM registrations r JOIN events e ON r.event_id = e.id WHERE r.id = ? AND r.user_id = ?");
    $stmt->execute([$reg_id, $user['id']]);
    $reg = $stmt->fetch();

    if ($reg) {
        $event_id = $reg['event_id'];

        // Update status to cancelled
        $update = $db->prepare("UPDATE registrations SET status = 'cancelled' WHERE id = ?");
        $update->execute([$reg_id]);

        // Auto-Promote Waitlisted Student (SRS Requirement 5)
        $promoted = promote_next_waitlisted($event_id);

        if ($promoted) {
            set_flash('info', 'Your registration was cancelled. The slot has been automatically reallocated to a waitlisted student.');
        } else {
            set_flash('info', 'Your registration has been cancelled successfully.');
        }
    } else {
        set_flash('error', 'Registration record not found.');
    }
}

header("Location: " . BASE_URL . "/student/my_events.php");
exit;
