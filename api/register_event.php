<?php
// api/register_event.php - Event Registration Handler with Slot Checks & Waitlist Auto-Routing
require_once __DIR__ . '/../config/config.php';

if (!is_logged_in() || user_role() !== 'student') {
    set_flash('error', 'Please log in as a student to register for events.');
    header("Location: " . BASE_URL . "/auth/login.php");
    exit;
}

$user = current_user();
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $event_id = (int)($_POST['event_id'] ?? 0);
    $csrf = $_POST['csrf_token'] ?? '';

    if (!verify_csrf($csrf)) {
        set_flash('error', 'Security validation token mismatch.');
        header("Location: " . BASE_URL . "/event_detail.php?id=" . $event_id);
        exit;
    }

    // Check if event is valid & approved
    $stmt = $db->prepare("SELECT * FROM events WHERE id = ? AND status = 'approved'");
    $stmt->execute([$event_id]);
    $event = $stmt->fetch();

    if (!$event) {
        set_flash('error', 'Event not found or registration is closed.');
        header("Location: " . BASE_URL . "/events.php");
        exit;
    }

    // Check if cutoff has passed
    if (strtotime($event['registration_cutoff']) < time()) {
        set_flash('error', 'Registration cutoff deadline for this event has passed.');
        header("Location: " . BASE_URL . "/event_detail.php?id=" . $event_id);
        exit;
    }

    // Check if already registered
    $chk_stmt = $db->prepare("SELECT * FROM registrations WHERE event_id = ? AND user_id = ?");
    $chk_stmt->execute([$event_id, $user['id']]);
    $existing_reg = $chk_stmt->fetch();

    if ($existing_reg) {
        if ($existing_reg['status'] === 'cancelled') {
            // Re-activate registration
            $cap_info = get_event_capacity_info($event_id);
            $new_status = $cap_info['is_full'] ? 'waitlisted' : 'confirmed';
            
            $reactivate = $db->prepare("UPDATE registrations SET status = ?, created_at = NOW() WHERE id = ?");
            $reactivate->execute([$new_status, $existing_reg['id']]);

            set_flash('success', "Your registration has been reactivated ({$new_status})!");
            header("Location: " . BASE_URL . "/student/my_events.php");
            exit;
        } else {
            set_flash('info', 'You are already registered for this event.');
            header("Location: " . BASE_URL . "/student/my_events.php");
            exit;
        }
    }

    // Check Capacity & Determine Confirmed vs Waitlisted
    $cap_info = get_event_capacity_info($event_id);
    $status = $cap_info['is_full'] ? 'waitlisted' : 'confirmed';

    // Generate unique Pass Code & Cryptographic QR Token
    $reg_code = 'REG-' . strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $event['title']), 0, 4)) . '-' . rand(1000, 9999);
    $qr_token = 'QR_ES_' . strtoupper(bin2hex(random_bytes(10)));

    try {
        $insert = $db->prepare("
            INSERT INTO registrations (event_id, user_id, registration_code, status, qr_token, certificate_fee_paid, created_at)
            VALUES (?, ?, ?, ?, ?, 0, NOW())
        ");
        $insert->execute([$event_id, $user['id'], $reg_code, $status, $qr_token]);

        if ($status === 'confirmed') {
            create_notification(
                $user['id'],
                '🎟️ Seat Confirmed: ' . $event['title'],
                'Your pass code is ' . $reg_code . '. Present your QR pass at the venue entrance.',
                BASE_URL . '/student/my_events.php',
                'success'
            );
            set_flash('success', '🎉 Registration Confirmed! Your digital entry pass with QR code has been generated.');
        } else {
            create_notification(
                $user['id'],
                '⏳ Waitlist Joined: ' . $event['title'],
                'You have been placed in the queue. You will be automatically promoted if a slot becomes available.',
                BASE_URL . '/student/my_events.php',
                'alert'
            );
            set_flash('warning', 'Slots are currently full! You have been placed on the automated Waitlist.');
        }

        header("Location: " . BASE_URL . "/student/my_events.php");
        exit;
    } catch (Exception $e) {
        set_flash('error', 'Registration failed: ' . $e->getMessage());
        header("Location: " . BASE_URL . "/event_detail.php?id=" . $event_id);
        exit;
    }
}

header("Location: " . BASE_URL . "/events.php");
exit;
