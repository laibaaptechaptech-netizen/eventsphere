<?php
// organizer/upload_gallery.php - Upload Photos & Videos to Campus Media Gallery
$page_title = "Upload Gallery Media";
require_once __DIR__ . '/../config/auth_check.php';
require_organizer();

$user = current_user();
$db = getDB();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = clean_input($_POST['title'] ?? '');
    $category_id = (int)($_POST['category_id'] ?? 1);
    $event_id = !empty($_POST['event_id']) ? (int)$_POST['event_id'] : null;
    $media_type = clean_input($_POST['media_type'] ?? 'image');
    $media_url = clean_input($_POST['media_url'] ?? '');
    $thumbnail_url = clean_input($_POST['thumbnail_url'] ?? $media_url);
    $academic_year = clean_input($_POST['academic_year'] ?? '2025-2026');
    $department = clean_input($_POST['department'] ?? $user['department']);

    if (empty($title) || strlen($title) < 3 || strlen($title) > 200) {
        $errors[] = "Media title must be between 3 and 200 characters.";
    }
    if (empty($media_url) || !filter_var($media_url, FILTER_VALIDATE_URL)) {
        $errors[] = "Please provide a valid high-resolution media URL.";
    }
    if (!in_array($media_type, ['image', 'video'])) {
        $errors[] = "Invalid media type selected.";
    }

    if (empty($errors)) {
        $stmt = $db->prepare("
            INSERT INTO media_gallery (event_id, category_id, title, media_type, media_url, thumbnail_url, department, academic_year, uploaded_by, is_approved, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())
        ");
        $stmt->execute([$event_id, $category_id, $title, $media_type, $media_url, $thumbnail_url, $department, $academic_year, $user['id']]);

        set_flash('success', 'Media uploaded to Campus Gallery successfully!');
        header("Location: " . BASE_URL . "/gallery.php");
        exit;
    }
}

$categories = $db->query("SELECT * FROM categories ORDER BY id ASC")->fetchAll();
$events = $db->prepare("SELECT id, title FROM events WHERE organizer_id = ? OR ? = 'admin' ORDER BY event_date DESC");
$events->execute([$user['id'], $user['role']]);
$organizer_events = $events->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <div class="mb-8">
        <div class="badge-neon badge-emerald mb-2">Media Management</div>
        <h1 class="font-heading font-extrabold text-3xl text-white">Upload Campus Media</h1>
        <p class="text-slate-400 text-xs mt-1">Showcase high-definition photographs and video recordings from past festivals and tournaments.</p>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="p-4 rounded-xl bg-rose-950/40 border border-rose-500/40 text-rose-300 text-xs mb-6">
            <?= htmlspecialchars($errors[0]) ?>
        </div>
    <?php endif; ?>

    <div class="glass-panel-elevated p-8 border border-white/10">
        <form method="POST" action="<?= BASE_URL ?>/organizer/upload_gallery.php" class="space-y-5">
            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1.5">Media Title *</label>
                <input type="text" name="title" required placeholder="e.g. HackNova Midnight Coding Sprint & Mentorship Session" class="form-input-dark text-xs">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1.5">Category *</label>
                    <select name="category_id" class="form-input-dark text-xs">
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1.5">Related Event (Optional)</label>
                    <select name="event_id" class="form-input-dark text-xs">
                        <option value="">-- General Campus Media --</option>
                        <?php foreach ($organizer_events as $ev): ?>
                            <option value="<?= $ev['id'] ?>"><?= htmlspecialchars($ev['title']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1.5">Media Type</label>
                    <select name="media_type" class="form-input-dark text-xs">
                        <option value="image">High-Definition Image</option>
                        <option value="video">Video Recording URL</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1.5">Academic Year</label>
                    <input type="text" name="academic_year" value="2025-2026" class="form-input-dark text-xs">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1.5">High-Resolution Image / Video URL *</label>
                <input type="url" name="media_url" required value="https://images.unsplash.com/photo-1504384308090-c894fdcc538d?auto=format&fit=crop&w=1200&q=80" class="form-input-dark text-xs">
            </div>

            <div class="flex items-center justify-between pt-4 border-t border-white/10">
                <a href="<?= BASE_URL ?>/gallery.php" class="btn-glass text-xs py-2 px-4">Cancel</a>
                <button type="submit" class="btn-neon-primary text-xs py-2.5 px-6 font-bold">
                    <i class="fa-solid fa-cloud-arrow-up mr-1.5"></i> Publish to Media Gallery
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.querySelector('form[action*="upload_gallery.php"]').addEventListener('submit', function(e) {
    const title = this.querySelector('[name="title"]').value.trim();
    const mediaUrl = this.querySelector('[name="media_url"]').value.trim();

    if (title.length < 3) {
        e.preventDefault();
        showToast('error', 'Validation Error', 'Title must be at least 3 characters long.');
        return;
    }
    if (!mediaUrl.startsWith('http://') && !mediaUrl.startsWith('https://')) {
        e.preventDefault();
        showToast('error', 'Validation Error', 'Please enter a valid HTTP/HTTPS media URL.');
        return;
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
