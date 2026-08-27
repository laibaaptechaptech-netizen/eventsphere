<?php
// admin/broadcast.php - System-Wide Broadcast Alerts & Role-Targeted Notices
$page_title = "System Announcements";
require_once __DIR__ . '/../config/auth_check.php';
require_admin();

$user = current_user();
$db = getDB();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = clean_input($_POST['title'] ?? '');
    $content = clean_input($_POST['content'] ?? '');
    $target_role = clean_input($_POST['target_role'] ?? 'all');

    $allowed_roles = ['all', 'student', 'organizer'];

    if (empty($title) || strlen($title) < 3 || strlen($title) > 200) {
        $errors[] = "Headline title must be between 3 and 200 characters.";
    }
    if (empty($content) || strlen($content) < 10 || strlen($content) > 2000) {
        $errors[] = "Announcement message content must be between 10 and 2000 characters.";
    }
    if (!in_array($target_role, $allowed_roles)) {
        $errors[] = "Invalid target audience selected.";
    }

    if (empty($errors)) {
        $stmt = $db->prepare("
            INSERT INTO announcements (title, content, target_role, created_by, is_active, created_at)
            VALUES (?, ?, ?, ?, 1, NOW())
        ");
        $stmt->execute([$title, $content, $target_role, $user['id']]);

        // Dispatch notifications to target audience
        $sql = "SELECT id FROM users WHERE status = 'active'";
        if ($target_role === 'student') $sql .= " AND role = 'student'";
        if ($target_role === 'organizer') $sql .= " AND role = 'organizer'";

        $target_users = $db->query($sql)->fetchAll(PDO::FETCH_COLUMN);

        foreach ($target_users as $uid) {
            create_notification(
                $uid,
                '🔔 ' . $title,
                $content,
                BASE_URL . '/index.php',
                'alert'
            );
        }

        set_flash('success', 'System broadcast dispatched to ' . count($target_users) . ' active users and displayed on the live marquee.');
        header("Location: " . BASE_URL . "/admin/broadcast.php");
        exit;
    }
}

// Fetch Past Broadcasts
$announcements = $db->query("
    SELECT a.*, u.name as sender_name 
    FROM announcements a 
    JOIN users u ON a.created_by = u.id 
    ORDER BY a.created_at DESC
")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <div class="mb-8">
        <div class="badge-neon badge-purple mb-2">Campus Announcements</div>
        <h1 class="font-heading font-extrabold text-3xl text-white">System Broadcast Center</h1>
        <p class="text-slate-400 text-xs mt-1">Publish platform-wide notices, emergency bulletins, and live marquee ticker updates.</p>
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

            <form method="POST" action="<?= BASE_URL ?>/admin/broadcast.php" class="space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Target Audience</label>
                    <select name="target_role" class="form-input-dark text-xs">
                        <option value="all">Entire Campus (All Users & Guests)</option>
                        <option value="student">Registered Students Only</option>
                        <option value="organizer">Faculty Organizers Only</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Notice Headline *</label>
                    <input type="text" name="title" required placeholder="e.g. ⚡ Campus Convocation Schedule Update" class="form-input-dark text-xs">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Detailed Message *</label>
                    <textarea name="content" rows="4" required placeholder="Type the official campus broadcast message here..." class="form-input-dark text-xs"></textarea>
                </div>

                <button type="submit" class="btn-neon-primary w-full py-2.5 text-xs font-bold">
                    <i class="fa-solid fa-bullhorn mr-1.5"></i> Publish Broadcast
                </button>
            </form>
        </div>

        <!-- History Log (2 cols) -->
        <div class="lg:col-span-2 glass-panel p-6 border border-white/10">
            <h3 class="font-heading font-bold text-lg text-white mb-4">Broadcast History Log</h3>

            <div class="space-y-4">
                <?php foreach ($announcements as $ann): ?>
                    <div class="p-4 rounded-xl bg-slate-900/60 border border-white/5 space-y-1.5">
                        <div class="flex items-center justify-between text-xs">
                            <span class="badge-neon badge-purple text-[10px]">Target: <?= strtoupper($ann['target_role']) ?></span>
                            <span class="text-[10px] text-slate-500"><?= time_ago($ann['created_at']) ?></span>
                        </div>
                        <h4 class="font-bold text-sm text-white"><?= htmlspecialchars($ann['title']) ?></h4>
                        <p class="text-xs text-slate-300 leading-relaxed"><?= htmlspecialchars($ann['content']) ?></p>
                        <div class="text-[10px] text-slate-500 pt-1">Dispatched by <?= htmlspecialchars($ann['sender_name']) ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<script>
document.querySelector('form[action*="broadcast.php"]').addEventListener('submit', function(e) {
    const title = this.querySelector('[name="title"]').value.trim();
    const content = this.querySelector('[name="content"]').value.trim();

    if (title.length < 3) {
        e.preventDefault();
        showToast('error', 'Validation Error', 'Headline title must be at least 3 characters long.');
        return;
    }
    if (content.length < 10) {
        e.preventDefault();
        showToast('error', 'Validation Error', 'Announcement message must be at least 10 characters long.');
        return;
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
