<?php
// api/export_report.php - CSV Data Exporter for Attendees & Platform Reports
require_once __DIR__ . '/../config/config.php';

if (!is_logged_in() || !in_array(user_role(), ['admin', 'organizer'])) {
    die("Access Denied");
}

$db = getDB();
$type = clean_input($_GET['type'] ?? 'general');

header('Content-Type: text/csv; charset=utf-8');

if ($type === 'attendees') {
    $event_id = (int)($_GET['event_id'] ?? 0);
    header('Content-Disposition: attachment; filename="EventSphere_Attendees_' . date('Ymd_His') . '.csv"');
    $output = fopen('php://output', 'w');

    fputcsv($output, ['Registration Code', 'Student Name', 'Email', 'Contact', 'Department', 'Enrolment No', 'Status', 'Checked-in At', 'Certificate Issued']);

    $stmt = $db->prepare("
        SELECT r.registration_code, u.name, u.email, u.contact, u.department, u.enrolment_no, r.status, r.checked_in_at, r.certificate_issued
        FROM registrations r
        JOIN users u ON r.user_id = u.id
        WHERE r.event_id = ?
        ORDER BY r.created_at ASC
    ");
    $stmt->execute([$event_id]);
    while ($row = $stmt->fetch()) {
        fputcsv($output, [
            $row['registration_code'],
            $row['name'],
            $row['email'],
            $row['contact'],
            $row['department'],
            $row['enrolment_no'],
            strtoupper($row['status']),
            $row['checked_in_at'] ?? 'Pending',
            $row['certificate_issued'] ? 'Yes' : 'No'
        ]);
    }
    fclose($output);
    exit;
} else {
    // General Participation Report
    header('Content-Disposition: attachment; filename="EventSphere_Master_Analytics_' . date('Ymd_His') . '.csv"');
    $output = fopen('php://output', 'w');

    fputcsv($output, ['Event ID', 'Title', 'Date', 'Department', 'Max Capacity', 'Confirmed Registrations', 'Attended Check-ins', 'Waitlist Count', 'Status']);

    $stmt = $db->query("
        SELECT e.id, e.title, e.event_date, e.department, e.max_capacity, e.status,
               COUNT(r.id) as total_registered,
               COUNT(CASE WHEN r.status = 'attended' THEN 1 END) as total_attended,
               COUNT(CASE WHEN r.status = 'waitlisted' THEN 1 END) as total_waitlisted
        FROM events e
        LEFT JOIN registrations r ON e.id = r.event_id
        GROUP BY e.id
        ORDER BY e.event_date DESC
    ");
    while ($row = $stmt->fetch()) {
        fputcsv($output, [
            $row['id'],
            $row['title'],
            $row['event_date'],
            $row['department'],
            $row['max_capacity'],
            $row['total_registered'],
            $row['total_attended'],
            $row['total_waitlisted'],
            strtoupper($row['status'])
        ]);
    }
    fclose($output);
    exit;
}
