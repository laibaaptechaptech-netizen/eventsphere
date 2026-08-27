<?php
// organizer/edit_event.php - Edit, Reschedule or Cancel Events + Auto-Dispatch Participant Alerts
$page_title = "Edit Event Details";
require_once __DIR__ . '/../config/auth_check.php';
require_organizer();

$user = current_user();
$db = getDB();
$event_id = (int)($_GET['id'] ?? 0);

// Fetch Event
$stmt = $db->prepare("SELECT * FROM events WHERE id = ? AND (organizer_id = ? OR ? = 'admin')");
$stmt->execute([$event_id, $user['id'], $user['role']]);
$event = $stmt->fetch();

if (!$event) {
    set_flash('error', 'Event not found or unauthorized.');
    header("Location: " . BASE_URL . "/organizer/dashboard.php");
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = clean_input($_POST['action'] ?? 'update');

    if ($action === 'cancel_event') {
        // Cancel event
        $stmt = $db->prepare("UPDATE events SET status = 'cancelled' WHERE id = ?");
        $stmt->execute([$event_id]);

        // Auto-Notify Registered Participants
        $reg_users = $db->prepare("SELECT user_id FROM registrations WHERE event_id = ? AND status IN ('confirmed', 'waitlisted')");
        $reg_users->execute([$event_id]);
        $users_to_notify = $reg_users->fetchAll(PDO::FETCH_COLUMN);

        foreach ($users_to_notify as $uid) {
            create_notification(
                $uid,
                '⚠️ Event Cancelled: ' . $event['title'],
                'The event scheduled for ' . format_event_date($event['event_date']) . ' has been cancelled by the organizer.',
                BASE_URL . '/student/my_events.php',
                'alert'
            );
        }

        set_flash('warning', 'Event cancelled. All registered participants have been notified automatically.');
        header("Location: " . BASE_URL . "/organizer/dashboard.php");
        exit;
    } else {
        // Update Event Details / Reschedule
        $title = clean_input($_POST['title'] ?? '');
        $description = clean_input($_POST['description'] ?? '');
        $event_date = clean_input($_POST['event_date'] ?? '');
        $start_time = clean_input($_POST['start_time'] ?? '');
        $end_time = clean_input($_POST['end_time'] ?? '');
        $max_capacity = (int)($_POST['max_capacity'] ?? 50);
        $venue_id = !empty($_POST['venue_id']) ? (int)$_POST['venue_id'] : null;
        $rulebook_file = clean_input($_POST['rulebook_file'] ?? ($event['rulebook_file'] ?? 'uploads/rules/sample_rulebook.pdf'));

        // Strong server-side validation
        if (empty($title) || strlen($title) < 5) {
            $errors[] = "Event title must be at least 5 characters long.";
        }
        if (empty($event_date)) {
            $errors[] = "Event date is required.";
        }
        if (empty($start_time) || empty($end_time)) {
            $errors[] = "Start time and end time are required.";
        } elseif (strtotime($event_date . ' ' . $end_time) <= strtotime($event_date . ' ' . $start_time)) {
            $errors[] = "Event end time must be after the start time.";
        }
        if ($max_capacity < 5 || $max_capacity > 2000) {
            $errors[] = "Max seating capacity must be between 5 and 2000.";
        }
        if (empty($description) || strlen($description) < 20) {
            $errors[] = "Event description and agenda must be at least 20 characters long.";
        }

        if (empty($errors)) {
            $is_rescheduled = ($event_date !== $event['event_date'] || $start_time !== $event['start_time']);

            $stmt = $db->prepare("
                UPDATE events 
                SET title = ?, description = ?, event_date = ?, start_time = ?, end_time = ?, max_capacity = ?, venue_id = ?, rulebook_file = ?
                WHERE id = ?
            ");
            $stmt->execute([$title, $description, $event_date, $start_time, $end_time, $max_capacity, $venue_id, $rulebook_file, $event_id]);

            if ($is_rescheduled) {
                // Notify registered students about rescheduling
                $reg_users = $db->prepare("SELECT user_id FROM registrations WHERE event_id = ? AND status IN ('confirmed', 'waitlisted')");
                $reg_users->execute([$event_id]);
                $users_to_notify = $reg_users->fetchAll(PDO::FETCH_COLUMN);

                foreach ($users_to_notify as $uid) {
                    create_notification(
                        $uid,
                        '🗓️ Schedule Updated: ' . $title,
                        'The event date/time has been rescheduled to ' . format_event_date($event_date) . ' at ' . format_event_time($start_time) . '. Please review your pass.',
                        BASE_URL . '/student/my_events.php',
                        'event_update'
                    );
                }
                set_flash('success', 'Event updated and rescheduled! Notification dispatched to all registered participants.');
            } else {
                set_flash('success', 'Event details updated successfully.');
            }

            header("Location: " . BASE_URL . "/organizer/dashboard.php");
            exit;
        }
    }
}

$venues = $db->query("SELECT * FROM venues WHERE is_active = 1 ORDER BY name ASC")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <div class="mb-8">
        <div class="badge-neon badge-amber mb-2">Event Modification</div>
        <h1 class="font-heading font-extrabold text-3xl text-white">Edit & Reschedule Event</h1>
        <p class="text-slate-400 text-xs mt-1">Modifications to schedule or venue automatically alert all confirmed participants.</p>
    </div>

    <div class="glass-panel-elevated p-8 border border-white/10 space-y-6">
        <form method="POST" action="<?= BASE_URL ?>/organizer/edit_event.php?id=<?= $event['id'] ?>" class="space-y-5">
            <input type="hidden" name="action" value="update">

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Event Title</label>
                <input type="text" name="title" value="<?= htmlspecialchars($event['title']) ?>" required class="form-input-dark text-xs">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Date</label>
                    <input type="date" name="event_date" value="<?= htmlspecialchars($event['event_date']) ?>" required class="form-input-dark text-xs">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Start Time</label>
                    <input type="time" name="start_time" value="<?= htmlspecialchars($event['start_time']) ?>" required class="form-input-dark text-xs">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">End Time</label>
                    <input type="time" name="end_time" value="<?= htmlspecialchars($event['end_time']) ?>" required class="form-input-dark text-xs">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Venue</label>
                    <select name="venue_id" class="form-input-dark text-xs">
                        <?php foreach ($venues as $v): ?>
                            <option value="<?= $v['id'] ?>" <?= $event['venue_id'] == $v['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($v['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Seating Capacity</label>
                    <input type="number" name="max_capacity" value="<?= htmlspecialchars($event['max_capacity']) ?>" required class="form-input-dark text-xs">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Official Rulebook & Agenda PDF Attachment</label>
                <select name="rulebook_file" class="form-input-dark text-xs">
                    <option value="uploads/rules/sample_rulebook.pdf" <?= ($event['rulebook_file'] === 'uploads/rules/sample_rulebook.pdf') ? 'selected' : '' ?>>Standard Collegiate Rulebook & Agenda (Default)</option>
                    <option value="uploads/rules/hacknova_rules.pdf" <?= ($event['rulebook_file'] === 'uploads/rules/hacknova_rules.pdf') ? 'selected' : '' ?>>HackNova AI Hackathon Specifications & Rulebook</option>
                    <option value="uploads/rules/symphonia_guidelines.pdf" <?= ($event['rulebook_file'] === 'uploads/rules/symphonia_guidelines.pdf') ? 'selected' : '' ?>>Symphonia Cultural Carnival Stage Guidelines</option>
                    <option value="uploads/rules/roboclash_specs.pdf" <?= ($event['rulebook_file'] === 'uploads/rules/roboclash_specs.pdf') ? 'selected' : '' ?>>RoboClash Combat Robot Specs & Safety Policy</option>
                    <option value="uploads/rules/workshop_syllabus.pdf" <?= ($event['rulebook_file'] === 'uploads/rules/workshop_syllabus.pdf') ? 'selected' : '' ?>>Technical Workshop Curriculum & Lab Syllabus</option>
                    <option value="uploads/rules/olympics_rulebook.pdf" <?= ($event['rulebook_file'] === 'uploads/rules/olympics_rulebook.pdf') ? 'selected' : '' ?>>Inter-Department Sports Olympiad Rulebook</option>
                    <option value="uploads/rules/gala_agenda.pdf" <?= ($event['rulebook_file'] === 'uploads/rules/gala_agenda.pdf') ? 'selected' : '' ?>>Annual Convocation Gala Protocol & Agenda</option>
                    <option value="uploads/rules/ctf_rules.pdf" <?= ($event['rulebook_file'] === 'uploads/rules/ctf_rules.pdf') ? 'selected' : '' ?>>CyberGuard CTF Tournament Defense Guidelines</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Description & Detailed Agenda *</label>
                <textarea name="description" rows="4" required class="form-input-dark text-xs"><?= htmlspecialchars($event['description']) ?></textarea>
            </div>

            <div class="flex items-center justify-between pt-4 border-t border-white/10">
                <a href="<?= BASE_URL ?>/organizer/dashboard.php" class="btn-glass text-xs py-2 px-4">Back</a>
                <button type="submit" class="btn-neon-primary text-xs py-2 px-6 font-bold">
                    <i class="fa-solid fa-floppy-disk mr-1.5"></i> Save & Auto-Notify Students
                </button>
            </div>
        </form>

        <!-- Cancel Event Section -->
        <div class="pt-6 border-t border-rose-500/20">
            <h4 class="font-bold text-xs uppercase text-rose-400 mb-1">Cancel Event Operations</h4>
            <p class="text-xs text-slate-400 mb-3">Cancelling this event will mark it as cancelled and alert all registered participants immediately.</p>
            <form method="POST" action="<?= BASE_URL ?>/organizer/edit_event.php?id=<?= $event['id'] ?>" onsubmit="return confirm('Are you sure you want to cancel this event? This will notify all registered students.');">
                <input type="hidden" name="action" value="cancel_event">
                <button type="submit" class="btn-glass py-2 px-4 text-xs text-rose-400 hover:text-rose-300 hover:bg-rose-950/40 border-rose-500/30">
                    <i class="fa-solid fa-ban mr-1.5"></i> Cancel This Event
                </button>
            </form>
        </div>
    </div>
</div>

<script>
document.querySelector('form[action*="edit_event"]').addEventListener('submit', function(e) {
    if (this.querySelector('[name="action"]').value === 'update') {
        const title = this.querySelector('[name="title"]').value.trim();
        const startTime = this.querySelector('[name="start_time"]').value;
        const endTime = this.querySelector('[name="end_time"]').value;
        const desc = this.querySelector('[name="description"]').value.trim();
        const capacity = parseInt(this.querySelector('[name="max_capacity"]').value);

        if (title.length < 5) {
            e.preventDefault();
            showToast('error', 'Validation Error', 'Title must be at least 5 characters long.');
            return;
        }
        if (startTime && endTime && startTime >= endTime) {
            e.preventDefault();
            showToast('error', 'Validation Error', 'End time must be after start time.');
            return;
        }
        if (isNaN(capacity) || capacity < 5 || capacity > 2000) {
            e.preventDefault();
            showToast('error', 'Validation Error', 'Capacity must be between 5 and 2000.');
            return;
        }
        if (desc.length < 20) {
            e.preventDefault();
            showToast('error', 'Validation Error', 'Description must be at least 20 characters.');
            return;
        }
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
