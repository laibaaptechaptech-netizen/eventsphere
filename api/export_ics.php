<?php
// api/export_ics.php - RFC-5545 Standard iCalendar (.ics) Generator
require_once __DIR__ . '/../config/config.php';

$event_id = (int)($_GET['event_id'] ?? 0);
$db = getDB();

$stmt = $db->prepare("
    SELECT e.*, v.name as venue_name, v.building
    FROM events e
    LEFT JOIN venues v ON e.venue_id = v.id
    WHERE e.id = ?
");
$stmt->execute([$event_id]);
$event = $stmt->fetch();

if (!$event) {
    die("Event not found");
}

$start_timestamp = strtotime($event['event_date'] . ' ' . $event['start_time']);
$end_timestamp = strtotime($event['event_date'] . ' ' . $event['end_time']);

$dtstart = gmdate('Ymd\THis\Z', $start_timestamp);
$dtend = gmdate('Ymd\THis\Z', $end_timestamp);
$dtstamp = gmdate('Ymd\THis\Z', time());
$uid = 'eventsphere_' . $event['id'] . '_' . md5($event['slug']) . '@eventsphere.edu';
$venue = clean_input(($event['venue_name'] ?? 'Main Campus') . ' (' . ($event['building'] ?? 'Tech Tower') . ')');
$summary = clean_input($event['title']);
$description = clean_input(substr(strip_tags($event['description']), 0, 300));
$url = BASE_URL . '/event_detail.php?id=' . $event['id'];

// Set download headers
header('Content-Type: text/calendar; charset=utf-8');
header('Content-Disposition: attachment; filename="' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $event['slug']) . '.ics"');

echo "BEGIN:VCALENDAR\r\n";
echo "VERSION:2.0\r\n";
echo "PRODID:-//EventSphere Campus Platform//EN\r\n";
echo "CALSCALE:GREGORIAN\r\n";
echo "METHOD:PUBLISH\r\n";
echo "BEGIN:VEVENT\r\n";
echo "UID:{$uid}\r\n";
echo "DTSTAMP:{$dtstamp}\r\n";
echo "DTSTART:{$dtstart}\r\n";
echo "DTEND:{$dtend}\r\n";
echo "SUMMARY:{$summary}\r\n";
echo "DESCRIPTION:{$description}\\n\\nEvent Details: {$url}\r\n";
echo "LOCATION:{$venue}\r\n";
echo "STATUS:CONFIRMED\r\n";
echo "BEGIN:VALARM\r\n";
echo "TRIGGER:-PT1H\r\n";
echo "ACTION:DISPLAY\r\n";
echo "DESCRIPTION:Event Reminder for {$summary}\r\n";
echo "END:VALARM\r\n";
echo "END:VEVENT\r\n";
echo "END:VCALENDAR\r\n";
exit;
