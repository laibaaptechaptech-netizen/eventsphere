<?php
// index.php - EventSphere 3D Interactive Home Page
$page_title = "Next-Gen 3D Campus Event Ecosystem";
require_once __DIR__ . '/includes/header.php';

$db = getDB();

// Fetch Platform Metrics
$total_events = 0;
$total_students = 0;
$total_registrations = 0;
$total_certificates = 0;
try {
    $total_events = (int)$db->query("SELECT COUNT(*) FROM events WHERE status IN ('approved', 'completed')")->fetchColumn();
    $total_students = (int)$db->query("SELECT COUNT(*) FROM users WHERE role = 'student'")->fetchColumn();
    $total_registrations = (int)$db->query("SELECT COUNT(*) FROM registrations WHERE status IN ('confirmed', 'attended')")->fetchColumn();
    $total_certificates = (int)$db->query("SELECT COUNT(*) FROM registrations WHERE certificate_issued = 1")->fetchColumn();
} catch (Exception $e) {}

// Fetch Featured & Upcoming Events
$featured_events = [];
try {
    $stmt = $db->query("
        SELECT e.*, c.name as category_name, c.slug as category_slug, c.badge_color, v.name as venue_name, u.name as organizer_name
        FROM events e
        JOIN categories c ON e.category_id = c.id
        LEFT JOIN venues v ON e.venue_id = v.id
        JOIN users u ON e.organizer_id = u.id
        WHERE e.status = 'approved' AND e.event_date >= CURDATE()
        ORDER BY e.featured DESC, e.event_date ASC
        LIMIT 3
    ");
    $featured_events = $stmt->fetchAll();
} catch (Exception $e) {}

// Fetch Categories with Event Count
$categories = [];
try {
    $stmt = $db->query("
        SELECT c.*, COUNT(e.id) as event_count
        FROM categories c
        LEFT JOIN events e ON c.id = e.category_id AND e.status = 'approved'
        GROUP BY c.id
        ORDER BY c.id ASC
    ");
    $categories = $stmt->fetchAll();
} catch (Exception $e) {}

// Fetch Recent Gallery Highlights
$gallery_items = [];
try {
    $stmt = $db->query("
        SELECT mg.*, c.name as category_name 
        FROM media_gallery mg
        JOIN categories c ON mg.category_id = c.id
        WHERE mg.is_approved = 1
        ORDER BY mg.id DESC
        LIMIT 4
    ");
    $gallery_items = $stmt->fetchAll();
} catch (Exception $e) {}
?>

<!-- 1. Interactive 3D WebGL Hero Section -->
<section class="relative min-h-[90vh] flex items-center justify-center overflow-hidden py-16">
    <!-- Three.js Canvas Container -->
    <div id="hero-3d-canvas"></div>

    <!-- Hero Content Overlay -->
    <div class="relative z-10 max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <!-- Live Tag Badge -->
        <div class="inline-flex items-center gap-2.5 px-4 py-1.5 rounded-full bg-slate-900/80 border border-purple-500/30 backdrop-blur-xl mb-6 shadow-xl shadow-purple-500/10">
            <span class="pulse-live"></span>
            <span class="text-xs font-bold uppercase tracking-widest text-cyan-300">
                Official Campus Event Portal 2026
            </span>
        </div>

        <h1 class="font-heading font-extrabold text-4xl sm:text-6xl lg:text-7xl tracking-tight text-white mb-6 leading-none">
            Where Campus Innovation Meets <br class="hidden sm:inline">
            <span class="text-gradient-purple">Dimensional</span> <span class="text-gradient-cyan">Reality</span>
        </h1>

        <p class="max-w-2xl mx-auto text-base sm:text-lg text-slate-300 leading-relaxed mb-10">
            Discover, register, and experience flagship technical fests, electrifying cultural carnivals, esports olympiads, and hands-on masterclasses with real-time seat availability and instant QR check-ins.
        </p>

        <!-- CTA Action Buttons -->
        <div class="flex flex-col sm:flex-row items-center justify-center gap-4 mb-16">
            <a href="<?= BASE_URL ?>/events.php" class="btn-neon-primary text-base py-3.5 px-8 w-full sm:w-auto shadow-xl shadow-purple-600/30">
                <i class="fa-solid fa-compass mr-2"></i> Explore All Events
            </a>
            <?php if (!$current_user): ?>
                <a href="<?= BASE_URL ?>/auth/register.php" class="btn-glass text-base py-3.5 px-8 w-full sm:w-auto">
                    <i class="fa-solid fa-user-plus mr-2 text-cyan-400"></i> Join as Participant
                </a>
            <?php else: ?>
                <a href="<?= BASE_URL ?>/student/dashboard.php" class="btn-glass text-base py-3.5 px-8 w-full sm:w-auto">
                    <i class="fa-solid fa-gauge-high mr-2 text-cyan-400"></i> My Dashboard
                </a>
            <?php endif; ?>
        </div>

        <!-- 3D Live Metrics Grid -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 max-w-4xl mx-auto">
            <div class="glass-card-interactive tilt-card p-4 sm:p-5 text-center">
                <div class="font-heading font-extrabold text-2xl sm:text-3xl text-gradient-cyan mb-1"><?= $total_events ?>+</div>
                <div class="text-xs uppercase tracking-wider font-semibold text-slate-400">Active Events</div>
            </div>
            <div class="glass-card-interactive tilt-card p-4 sm:p-5 text-center">
                <div class="font-heading font-extrabold text-2xl sm:text-3xl text-gradient-purple mb-1"><?= $total_students ?>+</div>
                <div class="text-xs uppercase tracking-wider font-semibold text-slate-400">Registered Students</div>
            </div>
            <div class="glass-card-interactive tilt-card p-4 sm:p-5 text-center">
                <div class="font-heading font-extrabold text-2xl sm:text-3xl text-gradient-gold mb-1"><?= $total_registrations ?>+</div>
                <div class="text-xs uppercase tracking-wider font-semibold text-slate-400">Slot Check-ins</div>
            </div>
            <div class="glass-card-interactive tilt-card p-4 sm:p-5 text-center">
                <div class="font-heading font-extrabold text-2xl sm:text-3xl text-emerald-400 mb-1"><?= $total_certificates ?>+</div>
                <div class="text-xs uppercase tracking-wider font-semibold text-slate-400">E-Certificates</div>
            </div>
        </div>
    </div>
</section>

<!-- 2. Explore Event Categories (SRS Subcategories) -->
<section class="py-16 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative">
    <div class="text-center max-w-2xl mx-auto mb-12">
        <div class="badge-neon badge-purple mb-3">Dimensional Spectrum</div>
        <h2 class="font-heading font-bold text-3xl sm:text-4xl text-white mb-3">Explore Event Disciplines</h2>
        <p class="text-slate-400 text-sm">Categorized campus experiences curated for technical mastery, creative arts, and athletic excellence.</p>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($categories as $cat): ?>
            <a href="<?= BASE_URL ?>/events.php?category=<?= urlencode($cat['slug']) ?>" class="glass-card-interactive tilt-card p-6 block group border border-white/5 hover:border-purple-500/40">
                <div class="flex items-start justify-between mb-4">
                    <div class="w-12 h-12 rounded-xl bg-slate-800 border border-white/10 flex items-center justify-center text-xl text-cyan-400 group-hover:scale-110 group-hover:text-purple-400 transition">
                        <i class="fa-solid <?= htmlspecialchars($cat['icon']) ?>"></i>
                    </div>
                    <span class="badge-neon badge-cyan"><?= $cat['event_count'] ?> Events</span>
                </div>
                <h3 class="font-heading font-bold text-lg text-white group-hover:text-cyan-300 transition mb-2">
                    <?= htmlspecialchars($cat['name']) ?>
                </h3>
                <p class="text-slate-400 text-xs leading-relaxed mb-4">
                    <?= htmlspecialchars($cat['description']) ?>
                </p>
                <div class="flex items-center text-xs font-semibold text-purple-400 group-hover:text-purple-300 transition">
                    <span>Browse Category</span>
                    <i class="fa-solid fa-arrow-right ml-1.5 group-hover:translate-x-1 transition-transform"></i>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
</section>

<!-- 3. Featured & Upcoming Events with Real-Time Slot Tracker -->
<section class="py-16 bg-slate-900/40 border-y border-white/5 relative">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col md:flex-row md:items-end justify-between mb-12 gap-4">
            <div>
                <div class="badge-neon badge-cyan mb-2">Upcoming Showcases</div>
                <h2 class="font-heading font-bold text-3xl sm:text-4xl text-white">Flagship Campus Events</h2>
                <p class="text-slate-400 text-sm mt-1">Live registration slots, agenda timelines, and venue allocations.</p>
            </div>
            <a href="<?= BASE_URL ?>/events.php" class="btn-glass text-xs sm:text-sm py-2.5 px-5 self-start md:self-auto">
                View All Events <i class="fa-solid fa-arrow-right ml-2 text-cyan-400"></i>
            </a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php if (empty($featured_events)): ?>
                <div class="col-span-3 text-center py-12 glass-panel">
                    <p class="text-slate-400 text-sm">No upcoming events scheduled right now. Check back soon!</p>
                </div>
            <?php else: ?>
                <?php foreach ($featured_events as $event): 
                    $cap_info = get_event_capacity_info($event['id']);
                ?>
                    <div class="glass-card-interactive tilt-card flex flex-col h-full border border-white/10 group">
                        <!-- Banner Image Container -->
                        <div class="relative h-48 w-full overflow-hidden bg-slate-800">
                            <img src="<?= htmlspecialchars($event['banner_image'] ?? 'https://images.unsplash.com/photo-1504384308090-c894fdcc538d?auto=format&fit=crop&w=800&q=80') ?>" alt="<?= htmlspecialchars($event['title']) ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                            <div class="absolute inset-0 bg-gradient-to-t from-dark-900 via-transparent to-black/40"></div>
                            
                            <!-- Category Badge -->
                            <span class="absolute top-3 left-3 badge-neon badge-<?= $event['badge_color'] ?? 'cyan' ?>">
                                <?= htmlspecialchars($event['category_name']) ?>
                            </span>

                            <!-- Bookmark Button -->
                            <button onclick="toggleEventBookmark(this, <?= $event['id'] ?>)" class="absolute top-3 right-3 w-8 h-8 rounded-full bg-dark-900/80 hover:bg-dark-900 border border-white/20 flex items-center justify-center text-slate-300 hover:text-amber-400 transition" title="Save Event">
                                <i class="fa-regular fa-bookmark text-xs"></i>
                            </button>

                            <!-- Date Overlay Badge -->
                            <div class="absolute bottom-3 left-3 flex items-center gap-2 bg-dark-900/90 backdrop-blur-md px-3 py-1 rounded-lg border border-white/10 text-xs">
                                <i class="fa-regular fa-calendar text-cyan-400"></i>
                                <span class="font-semibold text-white"><?= format_event_date($event['event_date']) ?></span>
                            </div>
                        </div>

                        <!-- Card Body -->
                        <div class="p-5 flex-1 flex flex-col justify-between">
                            <div>
                                <div class="flex items-center gap-2 text-xs text-slate-400 mb-2">
                                    <i class="fa-solid fa-location-dot text-rose-400"></i>
                                    <span class="truncate"><?= htmlspecialchars($event['venue_name'] ?? $event['custom_venue_name'] ?? 'Campus Grounds') ?></span>
                                </div>

                                <h3 class="font-heading font-bold text-lg text-white group-hover:text-cyan-300 transition line-clamp-2 mb-2">
                                    <a href="<?= BASE_URL ?>/event_detail.php?id=<?= $event['id'] ?>">
                                        <?= htmlspecialchars($event['title']) ?>
                                    </a>
                                </h3>

                                <p class="text-slate-400 text-xs line-clamp-2 leading-relaxed mb-4">
                                    <?= htmlspecialchars($event['description']) ?>
                                </p>
                            </div>

                            <!-- Live Capacity & Seat Indicator -->
                            <div class="pt-3 border-t border-white/5 mt-auto">
                                <div class="flex items-center justify-between text-xs mb-1.5">
                                    <span class="text-slate-400">Slot Availability:</span>
                                    <?php if ($cap_info['is_full']): ?>
                                        <span class="text-amber-400 font-bold flex items-center gap-1">
                                            <i class="fa-solid fa-users"></i> Waitlist Active (<?= $cap_info['waitlisted'] ?> waiting)
                                        </span>
                                    <?php else: ?>
                                        <span class="text-emerald-400 font-bold">
                                            <?= $cap_info['remaining'] ?> slots left (of <?= $cap_info['max_capacity'] ?>)
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <!-- Progress Bar -->
                                <div class="w-full h-1.5 bg-slate-800 rounded-full overflow-hidden mb-4">
                                    <div class="h-full rounded-full transition-all duration-500 <?= $cap_info['percentage'] > 80 ? 'bg-amber-500' : 'bg-gradient-to-r from-cyan-500 to-purple-500' ?>" style="width: <?= $cap_info['percentage'] ?>%"></div>
                                </div>

                                <!-- Action Buttons -->
                                <div class="flex items-center gap-2">
                                    <a href="<?= BASE_URL ?>/event_detail.php?id=<?= $event['id'] ?>" class="btn-neon-primary w-full py-2 text-xs">
                                        View Details & Pass
                                    </a>
                                    <button onclick="openShareModal('<?= htmlspecialchars(addslashes($event['title'])) ?>', '<?= BASE_URL ?>/event_detail.php?id=<?= $event['id'] ?>', '<?= htmlspecialchars(addslashes(substr($event['description'], 0, 100))) ?>')" class="w-9 h-9 rounded-xl bg-slate-800 hover:bg-slate-700 border border-white/10 flex items-center justify-center text-slate-300 hover:text-white transition flex-shrink-0" title="Share">
                                        <i class="fa-solid fa-share-nodes text-xs"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- View More Flagship Events CTA Button -->
        <div class="mt-12 text-center">
            <a href="<?= BASE_URL ?>/events.php" class="btn-neon-primary inline-flex items-center gap-2.5 py-3.5 px-8 text-sm font-bold shadow-xl shadow-purple-600/30 group">
                <span>View More Events</span>
                <i class="fa-solid fa-arrow-right group-hover:translate-x-1.5 transition-transform"></i>
            </a>
        </div>
    </div>
</section>

<!-- 4. Media Gallery Highlights -->
<section class="py-16 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="flex flex-col sm:flex-row sm:items-end justify-between mb-12 gap-4">
        <div>
            <div class="badge-neon badge-emerald mb-2">Campus Life Visuals</div>
            <h2 class="font-heading font-bold text-3xl sm:text-4xl text-white">Media Gallery Highlights</h2>
            <p class="text-slate-400 text-sm mt-1">High-definition memories captured from past collegiate tournaments and festivals.</p>
        </div>
        <a href="<?= BASE_URL ?>/gallery.php" class="btn-glass text-xs sm:text-sm py-2.5 px-5 self-start sm:self-auto">
            Full Gallery <i class="fa-solid fa-arrow-right ml-2 text-emerald-400"></i>
        </a>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <?php foreach ($gallery_items as $media): ?>
            <div class="glass-card-interactive group relative rounded-2xl overflow-hidden border border-white/10">
                <img src="<?= htmlspecialchars($media['thumbnail_url'] ?? $media['media_url']) ?>" alt="<?= htmlspecialchars($media['title']) ?>" class="w-full h-56 object-cover group-hover:scale-110 transition-transform duration-500">
                <div class="absolute inset-0 bg-gradient-to-t from-dark-900 via-dark-900/40 to-transparent opacity-80 group-hover:opacity-95 transition-opacity"></div>
                
                <div class="absolute bottom-0 inset-x-0 p-4">
                    <span class="badge-neon badge-purple text-[10px] mb-1.5"><?= htmlspecialchars($media['category_name']) ?></span>
                    <h4 class="font-heading font-bold text-sm text-white line-clamp-1 mb-2"><?= htmlspecialchars($media['title']) ?></h4>
                    
                    <div class="flex items-center justify-between">
                        <a href="<?= htmlspecialchars($media['media_url']) ?>" data-lightbox data-title="<?= htmlspecialchars($media['title']) ?>" class="text-xs font-semibold text-cyan-400 hover:text-cyan-300 flex items-center gap-1">
                            <i class="fa-solid fa-expand"></i> View High-Res
                        </a>
                        <button onclick="toggleSaveMedia(this, <?= $media['id'] ?>)" class="w-7 h-7 rounded-lg bg-dark-900/80 hover:bg-dark-900 border border-white/20 flex items-center justify-center text-slate-300 hover:text-rose-400 transition" title="Save Media">
                            <i class="fa-regular fa-heart text-xs"></i>
                        </button>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- Three.js Hero Background Init -->
<script src="<?= BASE_URL ?>/assets/js/three_hero.js"></script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
