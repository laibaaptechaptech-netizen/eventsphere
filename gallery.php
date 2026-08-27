<?php
// gallery.php - High-Definition Campus Media Gallery with Categorized Filter & Lightbox
$page_title = "Campus Media Gallery";
require_once __DIR__ . '/includes/header.php';

$db = getDB();

// Filter parameters
$cat_filter = clean_input($_GET['category'] ?? '');
$dept_filter = clean_input($_GET['department'] ?? '');
$year_filter = clean_input($_GET['year'] ?? '');

$sql = "
    SELECT mg.*, c.name as category_name, c.slug as category_slug, u.name as uploader_name, e.title as event_title
    FROM media_gallery mg
    JOIN categories c ON mg.category_id = c.id
    JOIN users u ON mg.uploaded_by = u.id
    LEFT JOIN events e ON mg.event_id = e.id
    WHERE mg.is_approved = 1
";
$params = [];

if (!empty($cat_filter)) {
    $sql .= " AND c.slug = ?";
    $params[] = $cat_filter;
}
if (!empty($dept_filter)) {
    $sql .= " AND mg.department = ?";
    $params[] = $dept_filter;
}
if (!empty($year_filter)) {
    $sql .= " AND mg.academic_year = ?";
    $params[] = $year_filter;
}

$sql .= " ORDER BY mg.id DESC";

$media_items = [];
try {
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $media_items = $stmt->fetchAll();
} catch (Exception $e) {}

$categories = $db->query("SELECT * FROM categories ORDER BY id ASC")->fetchAll();
$years = $db->query("SELECT DISTINCT academic_year FROM media_gallery ORDER BY academic_year DESC")->fetchAll(PDO::FETCH_COLUMN);
$departments = $db->query("SELECT DISTINCT department FROM media_gallery WHERE department != '' ORDER BY department ASC")->fetchAll(PDO::FETCH_COLUMN);
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <!-- Header -->
    <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="badge-neon badge-emerald mb-2">Visual Showcase</div>
            <h1 class="font-heading font-extrabold text-3xl sm:text-4xl text-white">Campus Media Gallery</h1>
            <p class="text-slate-400 text-sm mt-1">Explore moments, concert stages, robotics tournaments, and academic honors.</p>
        </div>
        <?php if ($user_role === 'organizer' || $user_role === 'admin'): ?>
            <a href="<?= BASE_URL ?>/organizer/upload_gallery.php" class="btn-neon-primary text-xs py-2.5 px-4 self-start md:self-auto">
                <i class="fa-solid fa-cloud-arrow-up mr-1.5"></i> Upload Media
            </a>
        <?php endif; ?>
    </div>

    <!-- Category / Department / Year Filter Pills -->
    <div class="glass-panel p-4 mb-8 border border-white/10 flex flex-wrap items-center justify-between gap-4">
        <div class="flex flex-wrap items-center gap-2">
            <a href="<?= BASE_URL ?>/gallery.php" class="px-3 py-1.5 rounded-lg text-xs font-semibold transition <?= empty($cat_filter) ? 'bg-purple-600 text-white shadow-lg shadow-purple-500/20' : 'bg-slate-800 text-slate-300 hover:bg-slate-700' ?>">
                All Categories
            </a>
            <?php foreach ($categories as $cat): ?>
                <a href="<?= BASE_URL ?>/gallery.php?category=<?= urlencode($cat['slug']) ?>" class="px-3 py-1.5 rounded-lg text-xs font-semibold transition <?= $cat_filter === $cat['slug'] ? 'bg-purple-600 text-white shadow-lg shadow-purple-500/20' : 'bg-slate-800 text-slate-300 hover:bg-slate-700' ?>">
                    <?= htmlspecialchars($cat['name']) ?>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- Academic Year Dropdown Filter -->
        <form method="GET" action="<?= BASE_URL ?>/gallery.php" class="flex items-center gap-2">
            <?php if (!empty($cat_filter)): ?>
                <input type="hidden" name="category" value="<?= htmlspecialchars($cat_filter) ?>">
            <?php endif; ?>
            <select name="year" onchange="this.form.submit()" class="form-input-dark text-xs py-1.5 px-3 rounded-lg w-auto">
                <option value="">All Academic Years</option>
                <?php foreach ($years as $yr): ?>
                    <option value="<?= htmlspecialchars($yr) ?>" <?= $year_filter === $yr ? 'selected' : '' ?>>
                        Year <?= htmlspecialchars($yr) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>

    <!-- Gallery Media Grid -->
    <?php if (empty($media_items)): ?>
        <div class="glass-panel p-12 text-center max-w-lg mx-auto">
            <div class="w-16 h-16 rounded-2xl bg-slate-800 border border-white/10 flex items-center justify-center mx-auto mb-4 text-slate-400 text-2xl">
                <i class="fa-regular fa-images"></i>
            </div>
            <h3 class="font-heading font-bold text-xl text-white mb-2">No Media in This Category</h3>
            <p class="text-slate-400 text-xs mb-4">No photos or videos match the selected filters.</p>
            <a href="<?= BASE_URL ?>/gallery.php" class="btn-glass text-xs py-2 px-4">View All Media</a>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($media_items as $media): ?>
                <div class="glass-card-interactive group relative rounded-2xl overflow-hidden border border-white/10 flex flex-col justify-between">
                    <div class="relative h-64 overflow-hidden bg-slate-900">
                        <img src="<?= htmlspecialchars($media['thumbnail_url'] ?? $media['media_url']) ?>" alt="<?= htmlspecialchars($media['title']) ?>" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                        <div class="absolute inset-0 bg-gradient-to-t from-dark-900 via-transparent to-black/30"></div>

                        <span class="absolute top-3 left-3 badge-neon badge-purple text-[10px]">
                            <?= htmlspecialchars($media['category_name']) ?>
                        </span>

                        <span class="absolute top-3 right-3 text-[10px] bg-dark-900/80 px-2 py-0.5 rounded text-slate-300 font-semibold border border-white/10">
                            <?= htmlspecialchars($media['academic_year']) ?>
                        </span>

                        <button onclick="toggleSaveMedia(this, <?= $media['id'] ?>)" class="absolute bottom-3 right-3 w-8 h-8 rounded-full bg-dark-900/80 hover:bg-dark-900 border border-white/20 flex items-center justify-center text-slate-300 hover:text-rose-400 transition" title="Save to Favorites">
                            <i class="fa-regular fa-heart text-xs"></i>
                        </button>
                    </div>

                    <div class="p-4 bg-dark-900/80 flex-1 flex flex-col justify-between">
                        <div>
                            <h4 class="font-heading font-bold text-sm text-white line-clamp-1 mb-1">
                                <?= htmlspecialchars($media['title']) ?>
                            </h4>
                            <div class="text-[11px] text-slate-400 flex items-center gap-2">
                                <span>Dept: <?= htmlspecialchars($media['department']) ?></span>
                                <?php if (!empty($media['event_title'])): ?>
                                    <span>•</span>
                                    <span class="text-cyan-400 line-clamp-1"><?= htmlspecialchars($media['event_title']) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="pt-3 border-t border-white/5 mt-3 flex items-center justify-between">
                            <a href="<?= htmlspecialchars($media['media_url']) ?>" data-lightbox data-title="<?= htmlspecialchars($media['title']) ?>" class="text-xs font-semibold text-cyan-400 hover:text-cyan-300 flex items-center gap-1.5">
                                <i class="fa-solid fa-expand"></i> Lightbox Preview
                            </a>
                            <span class="text-[10px] text-slate-500">By <?= htmlspecialchars($media['uploader_name']) ?></span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
