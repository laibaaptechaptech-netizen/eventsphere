<?php
// organizer/manage_attendees.php - Registrant Roster, Attendance Toggles & CSV Export
$page_title = "Manage Event Registrations";
require_once __DIR__ . '/../config/auth_check.php';
require_organizer();

$user = current_user();
$db = getDB();

$selected_event_id = (int)($_GET['event_id'] ?? 0);
$status_filter = clean_input($_GET['status'] ?? '');

// Handle Attendance / Status Toggle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = clean_input($_POST['action'] ?? '');
    $reg_id = (int)($_POST['reg_id'] ?? 0);

    if ($action === 'mark_attended') {
        $stmt = $db->prepare("UPDATE registrations SET status = 'attended', checked_in_at = NOW() WHERE id = ?");
        $stmt->execute([$reg_id]);
        set_flash('success', 'Participant marked as ATTENDED.');
    } elseif ($action === 'mark_confirmed') {
        $stmt = $db->prepare("UPDATE registrations SET status = 'confirmed', checked_in_at = NULL WHERE id = ?");
        $stmt->execute([$reg_id]);
        set_flash('info', 'Participant status updated to CONFIRMED.');
    } elseif ($action === 'reject_registrant') {
        $stmt = $db->prepare("UPDATE registrations SET status = 'cancelled' WHERE id = ?");
        $stmt->execute([$reg_id]);
        set_flash('warning', 'Registration rejected.');
    }
    header("Location: " . BASE_URL . "/organizer/manage_attendees.php?event_id=" . $selected_event_id);
    exit;
}

// Fetch Organizer Events for Selector
$events_stmt = $db->prepare("SELECT id, title FROM events WHERE organizer_id = ? OR ? = 'admin' ORDER BY event_date DESC");
$events_stmt->execute([$user['id'], $user['role']]);
$organizer_events = $events_stmt->fetchAll();

if ($selected_event_id === 0 && !empty($organizer_events)) {
    $selected_event_id = $organizer_events[0]['id'];
}

// Fetch Registrations for Selected Event
$sql = "
    SELECT r.*, u.name as student_name, u.email as student_email, u.contact as student_contact, u.department as student_dept, u.enrolment_no
    FROM registrations r
    JOIN users u ON r.user_id = u.id
    WHERE r.event_id = ?
";
$params = [$selected_event_id];

if (!empty($status_filter)) {
    $sql .= " AND r.status = ?";
    $params[] = $status_filter;
}

$sql .= " ORDER BY r.created_at ASC";

$registrations = [];
try {
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $registrations = $stmt->fetchAll();
} catch (Exception $e) {}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="badge-neon badge-cyan mb-2">Participant Roster</div>
            <h1 class="font-heading font-extrabold text-3xl text-white">Manage Event Registrations</h1>
            <p class="text-slate-400 text-xs mt-1">Review applicant rosters, mark manual gate attendance, or export official reports.</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="<?= BASE_URL ?>/organizer/scan_qr.php" class="btn-neon-cyan text-xs py-2 px-4 shadow-lg shadow-cyan-600/20">
                <i class="fa-solid fa-qrcode mr-1.5"></i> Camera QR Scanner
            </a>
            <a href="<?= BASE_URL ?>/api/export_report.php?type=attendees&event_id=<?= $selected_event_id ?>" class="btn-glass text-xs py-2 px-4">
                <i class="fa-solid fa-file-csv mr-1.5 text-emerald-400"></i> Export CSV Roster
            </a>
        </div>
    </div>

    <!-- Event Selector & Status Filter Bar -->
    <div class="glass-panel p-4 mb-8 border border-white/10 flex flex-wrap items-center justify-between gap-4">
        <form method="GET" action="<?= BASE_URL ?>/organizer/manage_attendees.php" class="flex flex-wrap items-center gap-3 w-full md:w-auto">
            <div>
                <label class="text-xs font-semibold text-slate-400 mr-2">Select Event:</label>
                <select name="event_id" onchange="this.form.submit()" class="form-input-dark text-xs py-1.5 px-3 rounded-lg w-auto">
                    <?php foreach ($organizer_events as $ev): ?>
                        <option value="<?= $ev['id'] ?>" <?= $selected_event_id == $ev['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($ev['title']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="text-xs font-semibold text-slate-400 mr-2">Filter Status:</label>
                <select name="status" onchange="this.form.submit()" class="form-input-dark text-xs py-1.5 px-3 rounded-lg w-auto">
                    <option value="">All Statuses</option>
                    <option value="confirmed" <?= $status_filter === 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                    <option value="attended" <?= $status_filter === 'attended' ? 'selected' : '' ?>>Attended</option>
                    <option value="waitlisted" <?= $status_filter === 'waitlisted' ? 'selected' : '' ?>>Waitlisted</option>
                    <option value="cancelled" <?= $status_filter === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                </select>
            </div>
        </form>

        <div class="text-xs text-slate-400">
            Total in Roster: <span class="font-bold text-cyan-400"><?= count($registrations) ?></span>
        </div>
    </div>

    <!-- Attendees Table -->
    <div class="glass-panel-elevated p-6 border border-white/10">
        <?php if (empty($registrations)): ?>
            <div class="py-12 text-center text-slate-400 text-xs">
                No registrants found for this event matching the selected status.
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="text-slate-400 border-b border-white/10 uppercase tracking-wider text-[10px]">
                            <th class="py-3 px-4">Pass Code</th>
                            <th class="py-3 px-4">Participant Details</th>
                            <th class="py-3 px-4">Department & Enrolment</th>
                            <th class="py-3 px-4">Status</th>
                            <th class="py-3 px-4">Check-in Time</th>
                            <th class="py-3 px-4 text-right">Attendance Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5 text-slate-300">
                        <?php foreach ($registrations as $reg): ?>
                            <tr class="hover:bg-white/5 transition">
                                <td class="py-3.5 px-4 font-mono font-bold text-purple-300">
                                    <?= htmlspecialchars($reg['registration_code']) ?>
                                </td>
                                <td class="py-3.5 px-4">
                                    <div class="font-bold text-white"><?= htmlspecialchars($reg['student_name']) ?></div>
                                    <div class="text-[10px] text-slate-400"><?= htmlspecialchars($reg['student_email']) ?> • <?= htmlspecialchars($reg['student_contact'] ?? '') ?></div>
                                </td>
                                <td class="py-3.5 px-4">
                                    <div><?= htmlspecialchars($reg['student_dept']) ?></div>
                                    <div class="text-[10px] text-cyan-400 font-mono"><?= htmlspecialchars($reg['enrolment_no'] ?? 'N/A') ?></div>
                                </td>
                                <td class="py-3.5 px-4">
                                    <span class="badge-neon badge-<?= $reg['status'] === 'attended' ? 'cyan' : ($reg['status'] === 'confirmed' ? 'emerald' : ($reg['status'] === 'waitlisted' ? 'amber' : 'rose')) ?> text-[10px]">
                                        <?= strtoupper($reg['status']) ?>
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-[11px] text-slate-400">
                                    <?= $reg['checked_in_at'] ? date('M d, h:i A', strtotime($reg['checked_in_at'])) : '<span class="text-slate-600">Pending Gate Scan</span>' ?>
                                </td>
                                <td class="py-3.5 px-4 text-right space-x-1.5 whitespace-nowrap">
                                    <?php if ($reg['status'] === 'confirmed'): ?>
                                        <form method="POST" action="<?= BASE_URL ?>/organizer/manage_attendees.php" class="inline">
                                            <input type="hidden" name="action" value="mark_attended">
                                            <input type="hidden" name="reg_id" value="<?= $reg['id'] ?>">
                                            <button type="submit" class="btn-neon-cyan py-1 px-3 text-[11px]">
                                                <i class="fa-solid fa-check mr-1"></i> Mark Attended
                                            </button>
                                        </form>
                                    <?php elseif ($reg['status'] === 'attended'): ?>
                                        <form method="POST" action="<?= BASE_URL ?>/organizer/manage_attendees.php" class="inline">
                                            <input type="hidden" name="action" value="mark_confirmed">
                                            <input type="hidden" name="reg_id" value="<?= $reg['id'] ?>">
                                            <button type="submit" class="btn-glass py-1 px-2 text-[10px] text-slate-400">
                                                Revert
                                            </button>
                                        </form>
                                    <?php endif; ?>

                                    <?php if ($reg['status'] !== 'cancelled'): ?>
                                        <form method="POST" action="<?= BASE_URL ?>/organizer/manage_attendees.php" onsubmit="return confirm('Reject this registration?');" class="inline">
                                            <input type="hidden" name="action" value="reject_registrant">
                                            <input type="hidden" name="reg_id" value="<?= $reg['id'] ?>">
                                            <button type="submit" class="text-rose-400 hover:text-rose-300 p-1.5 text-xs" title="Reject Registration">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
