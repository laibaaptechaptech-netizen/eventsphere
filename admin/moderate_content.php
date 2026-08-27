<?php
// admin/moderate_content.php - Review & Gallery Media Moderation Desk
$page_title = "Moderate Platform Content";
require_once __DIR__ . '/../config/auth_check.php';
require_admin();

$user = current_user();
$db = getDB();

// Handle Review Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = clean_input($_POST['action'] ?? '');
    
    if ($action === 'toggle_review') {
        $review_id = (int)($_POST['review_id'] ?? 0);
        $stmt = $db->prepare("UPDATE feedback_reviews SET is_approved = NOT is_approved WHERE id = ?");
        $stmt->execute([$review_id]);
        set_flash('info', 'Review visibility updated.');
    } elseif ($action === 'delete_review') {
        $review_id = (int)($_POST['review_id'] ?? 0);
        $stmt = $db->prepare("DELETE FROM feedback_reviews WHERE id = ?");
        $stmt->execute([$review_id]);
        set_flash('warning', 'Review deleted.');
    } elseif ($action === 'delete_media') {
        $media_id = (int)($_POST['media_id'] ?? 0);
        $stmt = $db->prepare("DELETE FROM media_gallery WHERE id = ?");
        $stmt->execute([$media_id]);
        set_flash('warning', 'Gallery media deleted.');
    }
    header("Location: " . BASE_URL . "/admin/moderate_content.php");
    exit;
}

// Fetch Reviews
$reviews = $db->query("
    SELECT r.*, u.name as reviewer_name, e.title as event_title
    FROM feedback_reviews r
    JOIN users u ON r.user_id = u.id
    JOIN events e ON r.event_id = e.id
    ORDER BY r.created_at DESC
")->fetchAll();

// Fetch Media
$gallery = $db->query("
    SELECT mg.*, u.name as uploader_name, c.name as category_name
    FROM media_gallery mg
    JOIN users u ON mg.uploaded_by = u.id
    JOIN categories c ON mg.category_id = c.id
    ORDER BY mg.created_at DESC
")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <div class="mb-8">
        <div class="badge-neon badge-rose mb-2">Quality & Compliance</div>
        <h1 class="font-heading font-extrabold text-3xl text-white">Content & Review Moderation</h1>
        <p class="text-slate-400 text-xs mt-1">Ensure institutional compliance across student feedback comments, ratings, and media uploads.</p>
    </div>

    <!-- Section 1: Feedback Reviews Moderation -->
    <div class="glass-panel-elevated p-6 mb-12 border border-white/10">
        <div class="flex items-center justify-between pb-4 border-b border-white/10 mb-6">
            <h3 class="font-heading font-bold text-xl text-white flex items-center gap-2">
                <i class="fa-solid fa-comments text-cyan-400"></i> Participant Feedback Reviews (<?= count($reviews) ?>)
            </h3>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="text-slate-400 border-b border-white/10 uppercase tracking-wider text-[10px]">
                        <th class="py-3 px-4">Student</th>
                        <th class="py-3 px-4">Event</th>
                        <th class="py-3 px-4">Ratings</th>
                        <th class="py-3 px-4">Comments & Suggestions</th>
                        <th class="py-3 px-4">Status</th>
                        <th class="py-3 px-4 text-right">Moderation Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5 text-slate-300">
                    <?php foreach ($reviews as $rev): ?>
                        <tr class="hover:bg-white/5 transition">
                            <td class="py-3.5 px-4 font-bold text-white"><?= htmlspecialchars($rev['reviewer_name']) ?></td>
                            <td class="py-3.5 px-4 text-cyan-300 line-clamp-1"><?= htmlspecialchars($rev['event_title']) ?></td>
                            <td class="py-3.5 px-4 text-amber-400 font-bold">
                                <?= $rev['overall_rating'] ?> ★ (V:<?= $rev['rating_venue'] ?>, C:<?= $rev['rating_coordination'] ?>, T:<?= $rev['rating_technical'] ?>)
                            </td>
                            <td class="py-3.5 px-4 max-w-sm">
                                <div class="text-xs text-slate-200"><?= htmlspecialchars($rev['comments']) ?></div>
                                <?php if ($rev['suggestions']): ?>
                                    <div class="text-[10px] text-cyan-400 mt-0.5">Tip: <?= htmlspecialchars($rev['suggestions']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="badge-neon badge-<?= $rev['is_approved'] ? 'emerald' : 'amber' ?> text-[10px]">
                                    <?= $rev['is_approved'] ? 'PUBLISHED' : 'HIDDEN' ?>
                                </span>
                            </td>
                            <td class="py-3.5 px-4 text-right space-x-2 whitespace-nowrap">
                                <form method="POST" action="<?= BASE_URL ?>/admin/moderate_content.php" class="inline">
                                    <input type="hidden" name="action" value="toggle_review">
                                    <input type="hidden" name="review_id" value="<?= $rev['id'] ?>">
                                    <button type="submit" class="btn-glass py-1 px-2.5 text-[11px] <?= $rev['is_approved'] ? 'text-amber-400' : 'text-emerald-400' ?>">
                                        <i class="fa-solid <?= $rev['is_approved'] ? 'fa-eye-slash' : 'fa-eye' ?> mr-1"></i>
                                        <?= $rev['is_approved'] ? 'Hide' : 'Approve' ?>
                                    </button>
                                </form>
                                <form method="POST" action="<?= BASE_URL ?>/admin/moderate_content.php" onsubmit="return confirm('Delete this review?');" class="inline">
                                    <input type="hidden" name="action" value="delete_review">
                                    <input type="hidden" name="review_id" value="<?= $rev['id'] ?>">
                                    <button type="submit" class="text-rose-400 hover:text-rose-300 p-1 text-xs">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Section 2: Media Gallery Moderation -->
    <div class="glass-panel-elevated p-6 border border-white/10">
        <h3 class="font-heading font-bold text-xl text-white mb-6 flex items-center gap-2">
            <i class="fa-solid fa-images text-purple-400"></i> Media Gallery Items (<?= count($gallery) ?>)
        </h3>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <?php foreach ($gallery as $g): ?>
                <div class="glass-card-interactive rounded-2xl overflow-hidden border border-white/10">
                    <div class="h-44 relative bg-slate-900">
                        <img src="<?= htmlspecialchars($g['thumbnail_url'] ?? $g['media_url']) ?>" alt="Media" class="w-full h-full object-cover">
                        <form method="POST" action="<?= BASE_URL ?>/admin/moderate_content.php" onsubmit="return confirm('Delete this media?');" class="absolute top-2 right-2">
                            <input type="hidden" name="action" value="delete_media">
                            <input type="hidden" name="media_id" value="<?= $g['id'] ?>">
                            <button type="submit" class="w-7 h-7 rounded-full bg-rose-600/90 text-white flex items-center justify-center text-xs shadow-lg">
                                <i class="fa-solid fa-trash-can"></i>
                            </button>
                        </form>
                    </div>
                    <div class="p-3 bg-dark-900/80 text-xs">
                        <div class="font-bold text-white line-clamp-1"><?= htmlspecialchars($g['title']) ?></div>
                        <div class="text-[10px] text-slate-400 mt-0.5">By <?= htmlspecialchars($g['uploader_name']) ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
