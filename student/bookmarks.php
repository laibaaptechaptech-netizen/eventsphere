<?php
// student/bookmarks.php - Student Saved Events & Favorite Media Collection
$page_title = "My Saved Events & Media";
require_once __DIR__ . '/../config/auth_check.php';
require_student();

$user = current_user();
$db = getDB();

// Fetch Bookmarked Events
$stmt = $db->prepare("
    SELECT e.*, c.name as category_name, c.badge_color, v.name as venue_name
    FROM event_bookmarks b
    JOIN events e ON b.event_id = e.id
    JOIN categories c ON e.category_id = c.id
    LEFT JOIN venues v ON e.venue_id = v.id
    WHERE b.user_id = ?
    ORDER BY b.id DESC
");
$stmt->execute([$user['id']]);
$bookmarked_events = $stmt->fetchAll();

// Fetch Saved Gallery Media
$stmt2 = $db->prepare("
    SELECT mg.*, c.name as category_name 
    FROM saved_media sm
    JOIN media_gallery mg ON sm.media_id = mg.id
    JOIN categories c ON mg.category_id = c.id
    WHERE sm.user_id = ?
    ORDER BY sm.id DESC
");
$stmt2->execute([$user['id']]);
$saved_media = $stmt2->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <div class="mb-8">
        <div class="badge-neon badge-rose mb-2">Personal Collection</div>
        <h1 class="font-heading font-extrabold text-3xl text-white">Saved Events & Media</h1>
        <p class="text-slate-400 text-xs mt-1">Manage events you've bookmarked for future participation and your favorite gallery photos.</p>
    </div>

    <!-- Section 1: Bookmarked Events -->
    <div class="mb-14">
        <div class="flex items-center justify-between mb-6 pb-3 border-b border-white/10">
            <h3 class="font-heading font-bold text-xl text-white flex items-center gap-2">
                <i class="fa-solid fa-bookmark text-amber-400"></i> Bookmarked Events (<?= count($bookmarked_events) ?>)
            </h3>
        </div>

        <?php if (empty($bookmarked_events)): ?>
            <div class="glass-panel p-8 text-center max-w-md mx-auto">
                <p class="text-xs text-slate-400 mb-3">No events bookmarked yet. Click the bookmark icon on any event card to save it here.</p>
                <a href="<?= BASE_URL ?>/events.php" class="btn-glass text-xs py-2 px-4">Browse Events</a>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($bookmarked_events as $event): ?>
                    <div class="glass-card-interactive tilt-card p-5 rounded-2xl border border-white/10 flex flex-col justify-between">
                        <div>
                            <div class="flex items-center justify-between mb-3">
                                <span class="badge-neon badge-<?= $event['badge_color'] ?? 'cyan' ?> text-[10px]">
                                    <?= htmlspecialchars($event['category_name']) ?>
                                </span>
                                <button onclick="toggleEventBookmark(this, <?= $event['id'] ?>)" class="text-amber-400 hover:text-rose-400 transition" title="Remove Bookmark">
                                    <i class="fa-solid fa-bookmark text-sm"></i>
                                </button>
                            </div>
                            <h4 class="font-heading font-bold text-base text-white mb-2 line-clamp-1">
                                <a href="<?= BASE_URL ?>/event_detail.php?id=<?= $event['id'] ?>" class="hover:text-cyan-300">
                                    <?= htmlspecialchars($event['title']) ?>
                                </a>
                            </h4>
                            <div class="text-xs text-slate-400 space-y-1 mb-4">
                                <div><i class="fa-regular fa-calendar text-cyan-400 mr-1"></i> <?= format_event_date($event['event_date']) ?></div>
                                <div><i class="fa-solid fa-location-dot text-rose-400 mr-1"></i> <?= htmlspecialchars($event['venue_name'] ?? 'Main Campus') ?></div>
                            </div>
                        </div>
                        <a href="<?= BASE_URL ?>/event_detail.php?id=<?= $event['id'] ?>" class="btn-neon-primary w-full py-2 text-xs text-center">
                            View & Register
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Section 2: Saved Media Collection -->
    <div>
        <div class="flex items-center justify-between mb-6 pb-3 border-b border-white/10">
            <h3 class="font-heading font-bold text-xl text-white flex items-center gap-2">
                <i class="fa-solid fa-heart text-rose-500"></i> Favorite Media Gallery (<?= count($saved_media) ?>)
            </h3>
        </div>

        <?php if (empty($saved_media)): ?>
            <div class="glass-panel p-8 text-center max-w-md mx-auto">
                <p class="text-xs text-slate-400 mb-3">No photos or videos saved yet. Click the heart icon in the Media Gallery to add favorites.</p>
                <a href="<?= BASE_URL ?>/gallery.php" class="btn-glass text-xs py-2 px-4">Explore Media Gallery</a>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <?php foreach ($saved_media as $media): ?>
                    <div class="glass-card-interactive group rounded-2xl overflow-hidden border border-white/10">
                        <div class="relative h-48 overflow-hidden bg-slate-900">
                            <img src="<?= htmlspecialchars($media['thumbnail_url'] ?? $media['media_url']) ?>" alt="<?= htmlspecialchars($media['title']) ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                            <button onclick="toggleSaveMedia(this, <?= $media['id'] ?>)" class="absolute top-2 right-2 w-7 h-7 rounded-full bg-dark-900/80 border border-white/20 flex items-center justify-center text-rose-500 hover:text-white transition">
                                <i class="fa-solid fa-heart text-xs"></i>
                            </button>
                        </div>
                        <div class="p-3 bg-dark-900/90 flex items-center justify-between">
                            <span class="text-xs font-semibold text-white line-clamp-1"><?= htmlspecialchars($media['title']) ?></span>
                            <a href="<?= htmlspecialchars($media['media_url']) ?>" data-lightbox data-title="<?= htmlspecialchars($media['title']) ?>" class="text-cyan-400 hover:text-cyan-300 text-xs ml-2">
                                <i class="fa-solid fa-expand"></i>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
