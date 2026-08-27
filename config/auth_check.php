<?php
// config/auth_check.php - Role-based Access Control Middleware

require_once __DIR__ . '/config.php';

function require_login($allowed_roles = []) {
    if (!is_logged_in()) {
        set_flash('warning', 'Please sign in to access this page.');
        $current_url = urlencode($_SERVER['REQUEST_URI'] ?? '');
        header("Location: " . BASE_URL . "/auth/login.php?redirect=" . $current_url);
        exit;
    }

    $user = current_user();

    // Check if account is suspended
    if (($user['status'] ?? 'active') === 'suspended') {
        set_flash('error', 'Your account has been suspended. Please contact the administrator.');
        header("Location: " . BASE_URL . "/auth/logout.php");
        exit;
    }

    if (!empty($allowed_roles)) {
        if (is_string($allowed_roles)) {
            $allowed_roles = [$allowed_roles];
        }

        if (!in_array($user['role'], $allowed_roles)) {
            set_flash('error', 'Access denied. You do not have sufficient permissions for this area.');
            
            // Redirect to appropriate dashboard
            switch ($user['role']) {
                case 'admin':
                    header("Location: " . BASE_URL . "/admin/dashboard.php");
                    break;
                case 'organizer':
                    header("Location: " . BASE_URL . "/organizer/dashboard.php");
                    break;
                case 'student':
                default:
                    header("Location: " . BASE_URL . "/student/dashboard.php");
                    break;
            }
            exit;
        }
    }
}

function require_admin() {
    require_login(['admin']);
}

function require_organizer() {
    require_login(['organizer', 'admin']);
}

function require_student() {
    require_login(['student']);
}
