<?php
// organizer/announcements.php - Dispatch Targeted Broadcast Alerts to Event Registrants
$page_title = "Organizer Announcements";
require_once __DIR__ . '/../config/auth_check.php';
require_organizer();

$user = current_user();
$db = getDB();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = clean_input($_POST['title'] ?? '');
    $content = clean_input($_POST['content'] ?? '');
    $event_id = (int)($_POST['event_id'] ?? 0);

    if (empty($title) || empty($content) || $event_id <= 0) {
        $errors[] = "Please fill in title, content, and choose an event.";
    } else {
        // Save Announcement
        $stmt = $db->prepare("
            INSERT INTO announcements (title, content, target_role, event_id, created_by, is_active, created_at)
            VALUES (?, ?, 'event_registrants', ?, ?, 1, NOW())
        ");
        $stmt->execute([$title, $content, $event_id, $user['id']]);

        // Dispatch notifications to all confirmed/waitlisted students for this event
        $u_stmt = $db->prepare("SELECT user_id FROM registrations WHERE event_id = ? AND status IN ('confirmed', 'waitlisted')");
        $u_stmt->execute([$event_id]);
        $recipients = $u_stmt->fetchAll(PDO::FETCH_COLUMN);

        foreach ($recipients as $uid) {
            create_notification(
                $uid,
                '📢 ' . $title,
                $content,
                BASE_URL . '/student/my_events.php',
                'event_update'
            );
        }

        set_flash('success', 'Announcement published! Dispatched alerts to ' . count($recipients) . ' registered participants.');
        header("Location: " . BASE_URL . "/organizer/announcements.php");
        exit;
    }
}

// Fetch Organizer Events
$events = $db->prepare("SELECT id, title FROM events WHERE organizer_id = ? OR ? = 'admin' ORDER BY event_date DESC");
$events->execute([$user['id'], $user['role']]);
$organizer_events = $events->fetchAll();

// Fetch Past Announcements by this Organizer
$ann_stmt = $db->prepare("
    SELECT a.*, e.title as event_title 
    FROM announcements a 
    LEFT JOIN events e ON a.event_id = e.id 
    WHERE a.created_by = ? 
    ORDER BY a.created_at DESC
");
$ann_stmt->execute([$user['id']]);
$past_announcements = $ann_stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <div class="mb-8">
        <div class="badge-neon badge-purple mb-2">Participant Communications</div>
        <h1 class="font-heading font-extrabold text-3xl text-white">Broadcast Event Announcements</h1>
        <p class="text-slate-400 text-xs mt-1">Send immediate push updates regarding agenda schedules, lab rules, or room revisions.</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Form (1 col) -->
        <div class="glass-panel-elevated p-6 border border-white/10 space-y-4 self-start">
            <h3 class="font-heading font-bold text-lg text-white">Dispatch New Broadcast</h3>

            <?php if (!empty($errors)): ?>
                <div class="p-3 bg-rose-950/40 border border-rose-500/40 text-rose-300 text-xs rounded-xl">
                    <?= htmlspecialchars($errors[0]) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="<?= BASE_URL ?>/organizer/announcements.php" class="space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Target Event *</label>
                    <select name="event_id" required class="form-input-dark text-xs">
                        <option value="">-- Choose Event --</option>
                        <?php foreach ($organizer_events as $ev): ?>
                            <option value="<?= $ev['id'] ?>"><?= htmlspecialchars($ev['title']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Headline / Subject *</label>
                    <input type="text" name="title" required placeholder="e.g. Lab Hardware Setup & Discord Links" class="form-input-dark text-xs">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Message Content *</label>
                    <textarea name="content" rows="4" required placeholder="Type instructions or updates for registered participants..." class="form-input-dark text-xs"></textarea>
                </div>

                <button type="submit" class="btn-neon-primary w-full py-2.5 text-xs font-bold">
                    <i class="fa-solid fa-bullhorn mr-1.5"></i> Broadcast to Attendees
                </button>
            </form>
        </div>

        <!-- History Log (2 cols) -->
        <div class="lg:col-span-2 glass-panel p-6 border border-white/10">
            <h3 class="font-heading font-bold text-lg text-white mb-4">Past Broadcast Log</h3>

            <?php if (empty($past_announcements)): ?>
                <div class="py-12 text-center text-slate-400 text-xs">
                    No announcements published yet.
                </div>
            <?php else: ?>
                <div class="space-y-4">
                    <?php foreach ($past_announcements as $ann): ?>
                        <div class="p-4 rounded-xl bg-slate-900/60 border border-white/5 space-y-1.5">
                            <div class="flex items-center justify-between text-xs">
                                <span class="badge-neon badge-purple text-[10px]">
                                    <?= htmlspecialchars($ann['event_title'] ?? 'General') ?>
                                </span>
                                <span class="text-[10px] text-slate-500"><?= time_ago($ann['created_at']) ?></span>
                            </div>
                            <h4 class="font-bold text-sm text-white"><?= htmlspecialchars($ann['title']) ?></h4>
                            <p class="text-xs text-slate-300 leading-relaxed"><?= htmlspecialchars($ann['content']) ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
