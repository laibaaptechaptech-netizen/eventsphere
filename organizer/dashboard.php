<?php
// organizer/dashboard.php - College Staff Organizer Operations Hub
$page_title = "Organizer Dashboard";
require_once __DIR__ . '/../config/auth_check.php';
require_organizer();

$user = current_user();
$db = getDB();

// Fetch Organizer Stats
$my_events_count = 0;
$total_registrations = 0;
$total_checked_in = 0;
$avg_feedback = 0;

try {
    $stmt = $db->prepare("SELECT COUNT(*) FROM events WHERE organizer_id = ?");
    $stmt->execute([$user['id']]);
    $my_events_count = (int)$stmt->fetchColumn();

    $stmt = $db->prepare("
        SELECT COUNT(r.id) as reg_count,
               COUNT(CASE WHEN r.status = 'attended' THEN 1 END) as attended_count
        FROM registrations r
        JOIN events e ON r.event_id = e.id
        WHERE e.organizer_id = ?
    ");
    $stmt->execute([$user['id']]);
    $counts = $stmt->fetch();
    $total_registrations = (int)($counts['reg_count'] ?? 0);
    $total_checked_in = (int)($counts['attended_count'] ?? 0);

    $stmt = $db->prepare("
        SELECT AVG(f.overall_rating) as avg_score
        FROM feedback_reviews f
        JOIN events e ON f.event_id = e.id
        WHERE e.organizer_id = ?
    ");
    $stmt->execute([$user['id']]);
    $avg_feedback = round((float)($stmt->fetchColumn() ?? 5.0), 1);
} catch (Exception $e) {}

// Fetch Organizer's Events
$events = [];
try {
    $stmt = $db->prepare("
        SELECT e.*, c.name as category_name, c.badge_color, v.name as venue_name,
               COUNT(r.id) as reg_count,
               COUNT(CASE WHEN r.status = 'attended' THEN 1 END) as attended_count
        FROM events e
        JOIN categories c ON e.category_id = c.id
        LEFT JOIN venues v ON e.venue_id = v.id
        LEFT JOIN registrations r ON e.id = r.event_id
        WHERE e.organizer_id = ?
        GROUP BY e.id
        ORDER BY e.event_date DESC
    ");
    $stmt->execute([$user['id']]);
    $events = $stmt->fetchAll();
} catch (Exception $e) {}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <!-- Header Banner -->
    <div class="glass-panel-elevated p-8 mb-10 border border-purple-500/30 flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
        <div>
            <div class="badge-neon badge-purple mb-2">Faculty & Staff Operations</div>
            <h1 class="font-heading font-extrabold text-3xl text-white">Organizer Command Hub</h1>
            <p class="text-slate-400 text-xs mt-1">Convener: <span class="text-cyan-400 font-bold"><?= htmlspecialchars($user['name']) ?></span> • <?= htmlspecialchars($user['department']) ?></p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <a href="<?= BASE_URL ?>/organizer/create_event.php" class="btn-neon-primary text-xs py-2.5 px-5 shadow-lg shadow-purple-600/30">
                <i class="fa-solid fa-calendar-plus mr-1.5"></i> Propose New Event
            </a>
            <a href="<?= BASE_URL ?>/organizer/scan_qr.php" class="btn-neon-cyan text-xs py-2.5 px-4 shadow-lg shadow-cyan-600/30">
                <i class="fa-solid fa-qrcode mr-1.5"></i> Live QR Scanner
            </a>
        </div>
    </div>

    <!-- 4 KPI Stats Grid -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6 mb-10">
        <div class="glass-card-interactive tilt-card p-5 border border-purple-500/20">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-semibold text-slate-400 uppercase">My Events</span>
                <i class="fa-solid fa-calendar-days text-purple-400 text-sm"></i>
            </div>
            <div class="font-heading font-bold text-3xl text-white"><?= $my_events_count ?></div>
            <div class="text-[11px] text-purple-300 mt-1">Managed symposia</div>
        </div>

        <div class="glass-card-interactive tilt-card p-5 border border-cyan-500/20">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-semibold text-slate-400 uppercase">Total Registrants</span>
                <i class="fa-solid fa-users text-cyan-400 text-sm"></i>
            </div>
            <div class="font-heading font-bold text-3xl text-cyan-300"><?= $total_registrations ?></div>
            <div class="text-[11px] text-cyan-300 mt-1">Confirmed participants</div>
        </div>

        <div class="glass-card-interactive tilt-card p-5 border border-emerald-500/20">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-semibold text-slate-400 uppercase">QR Check-Ins</span>
                <i class="fa-solid fa-qrcode text-emerald-400 text-sm"></i>
            </div>
            <div class="font-heading font-bold text-3xl text-emerald-300"><?= $total_checked_in ?></div>
            <div class="text-[11px] text-emerald-300 mt-1">Gate scans logged</div>
        </div>

        <div class="glass-card-interactive tilt-card p-5 border border-amber-500/20">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-semibold text-slate-400 uppercase">Avg Sentiment</span>
                <i class="fa-solid fa-star text-amber-400 text-sm"></i>
            </div>
            <div class="font-heading font-bold text-3xl text-amber-300"><?= $avg_feedback ?> / 5.0</div>
            <div class="text-[11px] text-amber-300 mt-1">Star rating score</div>
        </div>
    </div>

    <!-- Quick Navigation Action Pills -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-10 text-xs">
        <a href="<?= BASE_URL ?>/organizer/manage_attendees.php" class="glass-panel p-4 rounded-xl hover:border-cyan-500/40 flex items-center gap-3 transition">
            <div class="w-9 h-9 rounded-lg bg-cyan-500/10 text-cyan-400 flex items-center justify-center text-base"><i class="fa-solid fa-users-gear"></i></div>
            <div><div class="font-bold text-white">Attendee Roster</div><div class="text-[10px] text-slate-400">Export & Manage</div></div>
        </a>
        <a href="<?= BASE_URL ?>/organizer/issue_certificates.php" class="glass-panel p-4 rounded-xl hover:border-amber-500/40 flex items-center gap-3 transition">
            <div class="w-9 h-9 rounded-lg bg-amber-500/10 text-amber-400 flex items-center justify-center text-base"><i class="fa-solid fa-certificate"></i></div>
            <div><div class="font-bold text-white">Issue Certificates</div><div class="text-[10px] text-slate-400">Approve & Generate</div></div>
        </a>
        <a href="<?= BASE_URL ?>/organizer/upload_gallery.php" class="glass-panel p-4 rounded-xl hover:border-emerald-500/40 flex items-center gap-3 transition">
            <div class="w-9 h-9 rounded-lg bg-emerald-500/10 text-emerald-400 flex items-center justify-center text-base"><i class="fa-solid fa-cloud-arrow-up"></i></div>
            <div><div class="font-bold text-white">Upload Media</div><div class="text-[10px] text-slate-400">Gallery Photos/Videos</div></div>
        </a>
        <a href="<?= BASE_URL ?>/organizer/announcements.php" class="glass-panel p-4 rounded-xl hover:border-purple-500/40 flex items-center gap-3 transition">
            <div class="w-9 h-9 rounded-lg bg-purple-500/10 text-purple-400 flex items-center justify-center text-base"><i class="fa-solid fa-bullhorn"></i></div>
            <div><div class="font-bold text-white">Broadcast Alerts</div><div class="text-[10px] text-slate-400">Notify Registrants</div></div>
        </a>
    </div>

    <!-- Managed Events Table -->
    <div class="glass-panel-elevated p-6 border border-white/10">
        <div class="flex items-center justify-between pb-4 border-b border-white/10 mb-6">
            <h3 class="font-heading font-bold text-xl text-white">My Managed Events Roster</h3>
            <span class="text-xs text-slate-400"><?= count($events) ?> Events Found</span>
        </div>

        <?php if (empty($events)): ?>
            <div class="py-12 text-center text-slate-400 text-xs">
                No events created yet. Click "Propose New Event" to launch your first campus festival or symposium.
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="text-slate-400 border-b border-white/10 uppercase tracking-wider text-[10px]">
                            <th class="py-3 px-4">Event Title</th>
                            <th class="py-3 px-4">Category / Dept</th>
                            <th class="py-3 px-4">Date & Venue</th>
                            <th class="py-3 px-4">Capacity / Reg</th>
                            <th class="py-3 px-4">Status</th>
                            <th class="py-3 px-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5 text-slate-300">
                        <?php foreach ($events as $ev): ?>
                            <tr class="hover:bg-white/5 transition">
                                <td class="py-3.5 px-4">
                                    <a href="<?= BASE_URL ?>/event_detail.php?id=<?= $ev['id'] ?>" class="font-bold text-white hover:text-cyan-300 line-clamp-1">
                                        <?= htmlspecialchars($ev['title']) ?>
                                    </a>
                                </td>
                                <td class="py-3.5 px-4">
                                    <span class="badge-neon badge-<?= $ev['badge_color'] ?? 'cyan' ?> text-[10px] mb-0.5">
                                        <?= htmlspecialchars($ev['category_name']) ?>
                                    </span>
                                    <div class="text-[10px] text-slate-400"><?= htmlspecialchars($ev['department']) ?></div>
                                </td>
                                <td class="py-3.5 px-4">
                                    <div><?= format_event_date($ev['event_date']) ?></div>
                                    <div class="text-slate-400 text-[10px]"><?= htmlspecialchars($ev['venue_name'] ?? 'Main Campus') ?></div>
                                </td>
                                <td class="py-3.5 px-4">
                                    <div class="font-bold text-cyan-300"><?= $ev['reg_count'] ?> / <?= $ev['max_capacity'] ?></div>
                                    <div class="text-emerald-400 text-[10px]"><?= $ev['attended_count'] ?> Attended</div>
                                </td>
                                <td class="py-3.5 px-4">
                                    <span class="badge-neon badge-<?= $ev['status'] === 'approved' ? 'emerald' : ($ev['status'] === 'pending' ? 'amber' : ($ev['status'] === 'completed' ? 'purple' : 'rose')) ?> text-[10px]">
                                        <?= strtoupper($ev['status']) ?>
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-right space-x-1.5 whitespace-nowrap">
                                    <a href="<?= BASE_URL ?>/organizer/manage_attendees.php?event_id=<?= $ev['id'] ?>" class="btn-glass py-1.5 px-2.5 text-[11px]" title="Manage Attendees">
                                        <i class="fa-solid fa-users text-cyan-400"></i>
                                    </a>
                                    <a href="<?= BASE_URL ?>/organizer/edit_event.php?id=<?= $ev['id'] ?>" class="btn-glass py-1.5 px-2.5 text-[11px]" title="Edit / Reschedule">
                                        <i class="fa-solid fa-pen-to-square text-amber-400"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
