<?php
// events.php - Comprehensive Event Catalog with Advanced Multi-Filters & Search
$page_title = "Browse Campus Events";
require_once __DIR__ . '/includes/header.php';

$db = getDB();

// Filter parameters from GET request
$search = clean_input($_GET['search'] ?? '');
$category_slug = clean_input($_GET['category'] ?? '');
$department = clean_input($_GET['department'] ?? '');
$timeframe = clean_input($_GET['timeframe'] ?? 'all'); // 'upcoming', 'ongoing', 'past', 'all'
$venue_id = clean_input($_GET['venue'] ?? '');

// Build Dynamic SQL Query
$sql = "
    SELECT e.*, c.name as category_name, c.slug as category_slug, c.badge_color, v.name as venue_name, u.name as organizer_name
    FROM events e
    JOIN categories c ON e.category_id = c.id
    LEFT JOIN venues v ON e.venue_id = v.id
    JOIN users u ON e.organizer_id = u.id
    WHERE e.status IN ('approved', 'completed')
";
$params = [];

if (!empty($search)) {
    $sql .= " AND (e.title LIKE ? OR e.description LIKE ? OR e.department LIKE ?)";
    $term = "%$search%";
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
}

if (!empty($category_slug)) {
    $sql .= " AND c.slug = ?";
    $params[] = $category_slug;
}

if (!empty($department)) {
    $sql .= " AND e.department = ?";
    $params[] = $department;
}

if (!empty($venue_id)) {
    $sql .= " AND e.venue_id = ?";
    $params[] = $venue_id;
}

if ($timeframe === 'upcoming') {
    $sql .= " AND e.event_date >= CURDATE()";
} elseif ($timeframe === 'past') {
    $sql .= " AND e.event_date < CURDATE()";
} elseif ($timeframe === 'today') {
    $sql .= " AND e.event_date = CURDATE()";
}

$sql .= " ORDER BY e.event_date ASC";

$events = [];
try {
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $events = $stmt->fetchAll();
} catch (Exception $e) {}

// Fetch All Categories for Filter
$all_categories = $db->query("SELECT * FROM categories ORDER BY id ASC")->fetchAll();

// Fetch Distinct Departments
$all_departments = $db->query("SELECT DISTINCT department FROM events WHERE department IS NOT NULL AND department != '' ORDER BY department ASC")->fetchAll(PDO::FETCH_COLUMN);

// Fetch Venues
$all_venues = $db->query("SELECT * FROM venues WHERE is_active = 1 ORDER BY name ASC")->fetchAll();
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <!-- Header Title -->
    <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="badge-neon badge-cyan mb-2">Campus Catalog</div>
            <h1 class="font-heading font-extrabold text-3xl sm:text-4xl text-white">Explore Campus Events</h1>
            <p class="text-slate-400 text-sm mt-1">Browse scheduled symposiums, cultural nights, hackathons, and tournaments.</p>
        </div>
        <div class="text-xs text-slate-400 bg-slate-800/80 px-4 py-2 rounded-xl border border-white/10 self-start md:self-auto">
            Showing <span class="font-bold text-cyan-400"><?= count($events) ?></span> total events found
        </div>
    </div>

    <!-- Advanced Filter & Search Bar Panel -->
    <div class="glass-panel p-6 mb-10 border border-white/10">
        <form method="GET" action="<?= BASE_URL ?>/events.php" class="space-y-4">
            <!-- Keyword Search Row -->
            <div class="relative">
                <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search by event title, keyword, tech stack, or topic..." class="form-input-dark pl-11 py-3 text-sm rounded-xl">
            </div>

            <!-- Filter Dropdowns Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Category Filter -->
                <div>
                    <label class="block text-xs font-semibold text-slate-400 mb-1.5">Category</label>
                    <select name="category" class="form-input-dark text-xs py-2.5 rounded-xl">
                        <option value="">All Categories</option>
                        <?php foreach ($all_categories as $cat): ?>
                            <option value="<?= htmlspecialchars($cat['slug']) ?>" <?= $category_slug === $cat['slug'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Department Filter -->
                <div>
                    <label class="block text-xs font-semibold text-slate-400 mb-1.5">Department</label>
                    <select name="department" class="form-input-dark text-xs py-2.5 rounded-xl">
                        <option value="">All Departments</option>
                        <?php foreach ($all_departments as $dept): ?>
                            <option value="<?= htmlspecialchars($dept) ?>" <?= $department === $dept ? 'selected' : '' ?>>
                                <?= htmlspecialchars($dept) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Timeframe Filter -->
                <div>
                    <label class="block text-xs font-semibold text-slate-400 mb-1.5">Date Range</label>
                    <select name="timeframe" class="form-input-dark text-xs py-2.5 rounded-xl">
                        <option value="all" <?= $timeframe === 'all' ? 'selected' : '' ?>>All Dates</option>
                        <option value="upcoming" <?= $timeframe === 'upcoming' ? 'selected' : '' ?>>Upcoming Events</option>
                        <option value="today" <?= $timeframe === 'today' ? 'selected' : '' ?>>Happening Today</option>
                        <option value="past" <?= $timeframe === 'past' ? 'selected' : '' ?>>Past Completed</option>
                    </select>
                </div>

                <!-- Venue Filter -->
                <div>
                    <label class="block text-xs font-semibold text-slate-400 mb-1.5">Campus Venue</label>
                    <select name="venue" class="form-input-dark text-xs py-2.5 rounded-xl">
                        <option value="">All Venues</option>
                        <?php foreach ($all_venues as $ven): ?>
                            <option value="<?= $ven['id'] ?>" <?= $venue_id == $ven['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($ven['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center justify-between pt-2 border-t border-white/5">
                <a href="<?= BASE_URL ?>/events.php" class="text-xs text-slate-400 hover:text-white transition">
                    <i class="fa-solid fa-rotate-left mr-1"></i> Reset Filters
                </a>
                <button type="submit" class="btn-neon-cyan text-xs py-2 px-6">
                    <i class="fa-solid fa-filter mr-1.5"></i> Apply Filters
                </button>
            </div>
        </form>
    </div>

    <!-- Events Grid -->
    <?php if (empty($events)): ?>
        <div class="glass-panel p-12 text-center max-w-lg mx-auto">
            <div class="w-16 h-16 rounded-2xl bg-slate-800 border border-white/10 flex items-center justify-center mx-auto mb-4 text-slate-400 text-2xl">
                <i class="fa-regular fa-calendar-xmark"></i>
            </div>
            <h3 class="font-heading font-bold text-xl text-white mb-2">No Matching Events Found</h3>
            <p class="text-slate-400 text-xs mb-6">Try adjusting your keyword search or filter criteria to view other scheduled campus activities.</p>
            <a href="<?= BASE_URL ?>/events.php" class="btn-glass text-xs py-2 px-4">Clear All Filters</a>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php foreach ($events as $event): 
                $cap_info = get_event_capacity_info($event['id']);
                $is_past = strtotime($event['event_date']) < strtotime(date('Y-m-d'));
            ?>
                <div class="glass-card-interactive tilt-card flex flex-col h-full border border-white/10 group">
                    <!-- Banner Image -->
                    <div class="relative h-48 w-full overflow-hidden bg-slate-800">
                        <img src="<?= htmlspecialchars($event['banner_image'] ?? 'https://images.unsplash.com/photo-1504384308090-c894fdcc538d?auto=format&fit=crop&w=800&q=80') ?>" alt="<?= htmlspecialchars($event['title']) ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                        <div class="absolute inset-0 bg-gradient-to-t from-dark-900 via-transparent to-black/40"></div>
                        
                        <!-- Category Badge -->
                        <span class="absolute top-3 left-3 badge-neon badge-<?= $event['badge_color'] ?? 'cyan' ?>">
                            <?= htmlspecialchars($event['category_name']) ?>
                        </span>

                        <!-- Bookmark Button -->
                        <button onclick="toggleEventBookmark(this, <?= $event['id'] ?>)" class="absolute top-3 right-3 w-8 h-8 rounded-full bg-dark-900/80 hover:bg-dark-900 border border-white/20 flex items-center justify-center text-slate-300 hover:text-amber-400 transition" title="Bookmark Event">
                            <i class="fa-regular fa-bookmark text-xs"></i>
                        </button>

                        <!-- Status / Date Badge -->
                        <div class="absolute bottom-3 left-3 flex items-center gap-2 bg-dark-900/90 backdrop-blur-md px-3 py-1 rounded-lg border border-white/10 text-xs">
                            <i class="fa-regular fa-calendar text-cyan-400"></i>
                            <span class="font-semibold text-white"><?= format_event_date($event['event_date']) ?></span>
                        </div>

                        <?php if ($is_past): ?>
                            <span class="absolute bottom-3 right-3 badge-neon badge-purple">
                                <i class="fa-solid fa-flag-checkered"></i> Completed
                            </span>
                        <?php endif; ?>
                    </div>

                    <!-- Card Body -->
                    <div class="p-5 flex-1 flex flex-col justify-between">
                        <div>
                            <div class="flex items-center justify-between text-xs text-slate-400 mb-2">
                                <span class="truncate flex items-center gap-1.5">
                                    <i class="fa-solid fa-location-dot text-rose-400"></i>
                                    <?= htmlspecialchars($event['venue_name'] ?? $event['custom_venue_name'] ?? 'Main Campus') ?>
                                </span>
                                <span class="text-cyan-400 font-semibold flex-shrink-0">
                                    <?= format_event_time($event['start_time']) ?>
                                </span>
                            </div>

                            <h3 class="font-heading font-bold text-lg text-white group-hover:text-cyan-300 transition line-clamp-2 mb-2">
                                <a href="<?= BASE_URL ?>/event_detail.php?id=<?= $event['id'] ?>">
                                    <?= htmlspecialchars($event['title']) ?>
                                </a>
                            </h3>

                            <div class="text-[11px] font-semibold text-purple-300 mb-2">
                                Dept: <?= htmlspecialchars($event['department']) ?>
                            </div>

                            <p class="text-slate-400 text-xs line-clamp-2 leading-relaxed mb-4">
                                <?= htmlspecialchars($event['description']) ?>
                            </p>
                        </div>

                        <!-- Capacity & Actions -->
                        <div class="pt-3 border-t border-white/5 mt-auto">
                            <div class="flex items-center justify-between text-xs mb-1.5">
                                <span class="text-slate-400">Slots:</span>
                                <?php if ($is_past): ?>
                                    <span class="text-slate-400 font-semibold">Event Concluded</span>
                                <?php elseif ($cap_info['is_full']): ?>
                                    <span class="text-amber-400 font-bold">Waitlist Open (<?= $cap_info['waitlisted'] ?> waiting)</span>
                                <?php else: ?>
                                    <span class="text-emerald-400 font-bold"><?= $cap_info['remaining'] ?> slots left (of <?= $cap_info['max_capacity'] ?>)</span>
                                <?php endif; ?>
                            </div>

                            <!-- Progress Bar -->
                            <div class="w-full h-1.5 bg-slate-800 rounded-full overflow-hidden mb-4">
                                <div class="h-full rounded-full <?= $cap_info['percentage'] > 80 ? 'bg-amber-500' : 'bg-gradient-to-r from-cyan-500 to-purple-500' ?>" style="width: <?= $cap_info['percentage'] ?>%"></div>
                            </div>

                            <div class="flex items-center gap-2">
                                <a href="<?= BASE_URL ?>/event_detail.php?id=<?= $event['id'] ?>" class="btn-neon-primary w-full py-2 text-xs">
                                    View Details & Register
                                </a>
                                <button onclick="openShareModal('<?= htmlspecialchars(addslashes($event['title'])) ?>', '<?= BASE_URL ?>/event_detail.php?id=<?= $event['id'] ?>', '<?= htmlspecialchars(addslashes(substr($event['description'], 0, 100))) ?>')" class="w-9 h-9 rounded-xl bg-slate-800 hover:bg-slate-700 border border-white/10 flex items-center justify-center text-slate-300 hover:text-white transition flex-shrink-0" title="Share">
                                    <i class="fa-solid fa-share-nodes text-xs"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
