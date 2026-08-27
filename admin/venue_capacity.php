<?php
// admin/venue_capacity.php - Dynamic Venue Capacity Management & Seating Utilization (SRS Item 5)
$page_title = "Dynamic Venue Capacities";
require_once __DIR__ . '/../config/auth_check.php';
require_admin();

$user = current_user();
$db = getDB();

// Handle Capacity Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $event_id = (int)($_POST['event_id'] ?? 0);
    $new_capacity = (int)($_POST['max_capacity'] ?? 50);

    if ($event_id > 0 && $new_capacity > 0) {
        $stmt = $db->prepare("UPDATE events SET max_capacity = ? WHERE id = ?");
        $stmt->execute([$new_capacity, $event_id]);

        // Trigger waitlist auto-promotion if capacity was increased!
        $promoted = promote_next_waitlisted($event_id);

        if ($promoted) {
            set_flash('success', "Seating capacity updated to {$new_capacity} seats! A waitlisted student was automatically promoted to Confirmed.");
        } else {
            set_flash('success', "Seating capacity updated to {$new_capacity} seats.");
        }
    }
    header("Location: " . BASE_URL . "/admin/venue_capacity.php");
    exit;
}

// Fetch All Events with Capacity & Waitlist Metrics
$stmt = $db->query("
    SELECT e.*, c.name as category_name, v.name as venue_name, v.building,
           COUNT(CASE WHEN r.status IN ('confirmed', 'attended') THEN 1 END) as confirmed_count,
           COUNT(CASE WHEN r.status = 'waitlisted' THEN 1 END) as waitlist_count
    FROM events e
    JOIN categories c ON e.category_id = c.id
    LEFT JOIN venues v ON e.venue_id = v.id
    LEFT JOIN registrations r ON e.id = r.event_id
    WHERE e.status = 'approved'
    GROUP BY e.id
    ORDER BY e.event_date ASC
");
$events = $stmt->fetchAll();

// Fetch Venues Master Table
$venues = $db->query("SELECT * FROM venues ORDER BY id ASC")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="badge-neon badge-cyan mb-2">SRS Item 5 & 6</div>
            <h1 class="font-heading font-extrabold text-3xl text-white">Dynamic Venue Capacity & Seating</h1>
            <p class="text-slate-400 text-xs mt-1">Live seat utilization tracking, configurable event thresholds, and automated waitlist promotion engines.</p>
        </div>
        <div class="text-xs text-emerald-400 bg-emerald-950/40 px-4 py-2 rounded-xl border border-emerald-500/30">
            <i class="fa-solid fa-shield-check mr-1.5"></i> Automatic Enforcement Active
        </div>
    </div>

    <!-- Campus Venue Auditoriums Overview Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-10">
        <?php foreach ($venues as $venue): ?>
            <div class="glass-card-interactive p-5 rounded-2xl border border-white/10 space-y-2">
                <div class="flex items-center justify-between">
                    <span class="badge-neon badge-purple text-[10px]"><?= htmlspecialchars($venue['building'] ?? 'Tech Nexus') ?></span>
                    <span class="text-xs text-cyan-400 font-bold font-mono">Max: <?= $venue['max_capacity'] ?> Seats</span>
                </div>
                <h4 class="font-heading font-bold text-base text-white"><?= htmlspecialchars($venue['name']) ?></h4>
                <p class="text-slate-400 text-xs line-clamp-2"><?= htmlspecialchars($venue['location_details']) ?></p>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Live Event Seating Utilization Table -->
    <div class="glass-panel-elevated p-6 border border-white/10">
        <h3 class="font-heading font-bold text-xl text-white mb-6">Live Event Capacity & Waitlist Balancer</h3>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="text-slate-400 border-b border-white/10 uppercase tracking-wider text-[10px]">
                        <th class="py-3 px-4">Event Title</th>
                        <th class="py-3 px-4">Allocated Venue</th>
                        <th class="py-3 px-4">Seating Progress</th>
                        <th class="py-3 px-4">Confirmed / Limit</th>
                        <th class="py-3 px-4">Waitlist Queue</th>
                        <th class="py-3 px-4 text-right">Adjust Capacity</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5 text-slate-300">
                    <?php foreach ($events as $ev): 
                        $confirmed = (int)$ev['confirmed_count'];
                        $max = (int)$ev['max_capacity'];
                        $pct = $max > 0 ? min(100, round(($confirmed / $max) * 100)) : 0;
                        $is_full = ($confirmed >= $max);
                    ?>
                        <tr class="hover:bg-white/5 transition">
                            <td class="py-4 px-4 font-bold text-white max-w-xs">
                                <a href="<?= BASE_URL ?>/event_detail.php?id=<?= $ev['id'] ?>" class="hover:text-cyan-300">
                                    <?= htmlspecialchars($ev['title']) ?>
                                </a>
                                <div class="text-[10px] text-slate-400"><?= format_event_date($ev['event_date']) ?></div>
                            </td>
                            <td class="py-4 px-4">
                                <div class="text-white"><?= htmlspecialchars($ev['venue_name'] ?? 'Main Campus') ?></div>
                                <div class="text-[10px] text-slate-400"><?= htmlspecialchars($ev['building'] ?? 'Central') ?></div>
                            </td>
                            <td class="py-4 px-4 w-48">
                                <div class="flex justify-between text-[11px] mb-1">
                                    <span class="<?= $is_full ? 'text-rose-400 font-bold' : 'text-cyan-400' ?>"><?= $pct ?>% Occupied</span>
                                    <span class="text-slate-400"><?= max(0, $max - $confirmed) ?> left</span>
                                </div>
                                <div class="w-full h-2 bg-slate-800 rounded-full overflow-hidden">
                                    <div class="h-full rounded-full transition-all <?= $pct >= 90 ? 'bg-rose-500' : 'bg-gradient-to-r from-emerald-400 to-cyan-400' ?>" style="width: <?= $pct ?>%"></div>
                                </div>
                            </td>
                            <td class="py-4 px-4">
                                <span class="font-bold text-white text-sm"><?= $confirmed ?></span>
                                <span class="text-slate-400">/ <?= $max ?> seats</span>
                            </td>
                            <td class="py-4 px-4">
                                <?php if ($ev['waitlist_count'] > 0): ?>
                                    <span class="badge-neon badge-amber text-[10px]">
                                        <i class="fa-solid fa-users-line mr-1"></i> <?= $ev['waitlist_count'] ?> In Queue
                                    </span>
                                <?php else: ?>
                                    <span class="text-slate-500 text-[11px]">0 waiting</span>
                                <?php endif; ?>
                            </td>
                            <td class="py-4 px-4 text-right">
                                <form method="POST" action="<?= BASE_URL ?>/admin/venue_capacity.php" class="inline-flex items-center gap-2">
                                    <input type="hidden" name="event_id" value="<?= $ev['id'] ?>">
                                    <input type="number" name="max_capacity" min="5" max="1000" value="<?= $ev['max_capacity'] ?>" class="form-input-dark text-xs py-1 px-2 w-20 text-center rounded-lg">
                                    <button type="submit" class="btn-glass py-1 px-3 text-[11px] text-cyan-300 hover:text-white" title="Save New Capacity Limit">
                                        Update
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
