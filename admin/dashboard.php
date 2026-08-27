<?php
// admin/dashboard.php - System Administrator Command Center & Live Campus Analytics
$page_title = "Admin Command Center";
require_once __DIR__ . '/../config/auth_check.php';
require_admin();

$user = current_user();
$db = getDB();

// High-Level KPIs
$user_counts = [];
$event_counts = [];
$total_registrations = 0;
$total_certificates = 0;

try {
    $u_stmt = $db->query("SELECT role, COUNT(*) as cnt FROM users GROUP BY role");
    while ($row = $u_stmt->fetch()) {
        $user_counts[$row['role']] = (int)$row['cnt'];
    }

    $e_stmt = $db->query("SELECT status, COUNT(*) as cnt FROM events GROUP BY status");
    while ($row = $e_stmt->fetch()) {
        $event_counts[$row['status']] = (int)$row['cnt'];
    }

    $total_registrations = (int)$db->query("SELECT COUNT(*) FROM registrations WHERE status IN ('confirmed', 'attended')")->fetchColumn();
    $total_certificates = (int)$db->query("SELECT COUNT(*) FROM registrations WHERE certificate_issued = 1")->fetchColumn();
} catch (Exception $e) {}

// Pending Event Proposals for Admin Action
$pending_events = [];
try {
    $stmt = $db->query("
        SELECT e.*, u.name as organizer_name, c.name as category_name, v.name as venue_name
        FROM events e
        JOIN users u ON e.organizer_id = u.id
        JOIN categories c ON e.category_id = c.id
        LEFT JOIN venues v ON e.venue_id = v.id
        WHERE e.status = 'pending'
        ORDER BY e.created_at DESC
    ");
    $pending_events = $stmt->fetchAll();
} catch (Exception $e) {}

// Department Leaderboard (Participation by Department)
$dept_stats = [];
try {
    $stmt = $db->query("
        SELECT department, COUNT(id) as total_events, SUM(max_capacity) as total_seats
        FROM events
        WHERE department IS NOT NULL AND department != ''
        GROUP BY department
        ORDER BY total_events DESC
    ");
    $dept_stats = $stmt->fetchAll();
} catch (Exception $e) {}

// Recent Activity Log
$recent_regs = [];
try {
    $stmt = $db->query("
        SELECT r.*, u.name as student_name, e.title as event_title
        FROM registrations r
        JOIN users u ON r.user_id = u.id
        JOIN events e ON r.event_id = e.id
        ORDER BY r.created_at DESC
        LIMIT 6
    ");
    $recent_regs = $stmt->fetchAll();
} catch (Exception $e) {}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <!-- Header Banner -->
    <div class="glass-panel-elevated p-8 mb-10 border border-purple-500/30 flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
        <div>
            <div class="badge-neon badge-purple mb-2">System Governance Tier</div>
            <h1 class="font-heading font-extrabold text-3xl text-white">Administrator Command Center</h1>
            <p class="text-slate-400 text-xs mt-1">Logged in as: <span class="text-purple-300 font-bold"><?= htmlspecialchars($user['name']) ?></span> • 2FA Authenticated</p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <a href="<?= BASE_URL ?>/admin/manage_events.php" class="btn-neon-primary text-xs py-2.5 px-4">
                <i class="fa-solid fa-list-check mr-1.5"></i> Review Proposals (<?= count($pending_events) ?>)
            </a>
            <a href="<?= BASE_URL ?>/admin/reports.php" class="btn-glass text-xs py-2.5 px-4">
                <i class="fa-solid fa-file-invoice mr-1.5 text-cyan-400"></i> Analytics Reports
            </a>
        </div>
    </div>

    <!-- 4 KPI Metrics Grid -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6 mb-10">
        <div class="glass-card-interactive tilt-card p-5 border border-purple-500/20">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-semibold text-slate-400 uppercase">Total Students</span>
                <i class="fa-solid fa-graduation-cap text-purple-400 text-sm"></i>
            </div>
            <div class="font-heading font-bold text-3xl text-white"><?= $user_counts['student'] ?? 0 ?></div>
            <div class="text-[11px] text-purple-300 mt-1">Registered participants</div>
        </div>

        <div class="glass-card-interactive tilt-card p-5 border border-cyan-500/20">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-semibold text-slate-400 uppercase">Live Events</span>
                <i class="fa-solid fa-calendar-check text-cyan-400 text-sm"></i>
            </div>
            <div class="font-heading font-bold text-3xl text-cyan-300"><?= $event_counts['approved'] ?? 0 ?></div>
            <div class="text-[11px] text-cyan-300 mt-1"><?= $event_counts['pending'] ?? 0 ?> pending approval</div>
        </div>

        <div class="glass-card-interactive tilt-card p-5 border border-emerald-500/20">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-semibold text-slate-400 uppercase">Total Registrations</span>
                <i class="fa-solid fa-ticket text-emerald-400 text-sm"></i>
            </div>
            <div class="font-heading font-bold text-3xl text-emerald-300"><?= $total_registrations ?></div>
            <div class="text-[11px] text-emerald-300 mt-1">Confirmed passes</div>
        </div>

        <div class="glass-card-interactive tilt-card p-5 border border-amber-500/20">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-semibold text-slate-400 uppercase">E-Certificates</span>
                <i class="fa-solid fa-award text-amber-400 text-sm"></i>
            </div>
            <div class="font-heading font-bold text-3xl text-amber-300"><?= $total_certificates ?></div>
            <div class="text-[11px] text-amber-300 mt-1">Official credentials issued</div>
        </div>
    </div>

    <!-- Quick Navigation Action Bar -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-10 text-xs">
        <a href="<?= BASE_URL ?>/admin/manage_events.php" class="glass-panel p-4 rounded-xl hover:border-purple-500/40 flex items-center gap-3 transition">
            <div class="w-9 h-9 rounded-lg bg-purple-500/10 text-purple-400 flex items-center justify-center text-base"><i class="fa-solid fa-list-check"></i></div>
            <div><div class="font-bold text-white">Event Approvals</div><div class="text-[10px] text-slate-400">Review & Publish</div></div>
        </a>
        <a href="<?= BASE_URL ?>/admin/venue_capacity.php" class="glass-panel p-4 rounded-xl hover:border-cyan-500/40 flex items-center gap-3 transition">
            <div class="w-9 h-9 rounded-lg bg-cyan-500/10 text-cyan-400 flex items-center justify-center text-base"><i class="fa-solid fa-chart-column"></i></div>
            <div><div class="font-bold text-white">Venue Capacities</div><div class="text-[10px] text-slate-400">Dynamic Seating</div></div>
        </a>
        <a href="<?= BASE_URL ?>/admin/manage_users.php" class="glass-panel p-4 rounded-xl hover:border-amber-500/40 flex items-center gap-3 transition">
            <div class="w-9 h-9 rounded-lg bg-amber-500/10 text-amber-400 flex items-center justify-center text-base"><i class="fa-solid fa-users"></i></div>
            <div><div class="font-bold text-white">User Governance</div><div class="text-[10px] text-slate-400">Roles & Security</div></div>
        </a>
        <a href="<?= BASE_URL ?>/admin/moderate_content.php" class="glass-panel p-4 rounded-xl hover:border-emerald-500/40 flex items-center gap-3 transition">
            <div class="w-9 h-9 rounded-lg bg-emerald-500/10 text-emerald-400 flex items-center justify-center text-base"><i class="fa-solid fa-comments"></i></div>
            <div><div class="font-bold text-white">Moderate Reviews</div><div class="text-[10px] text-slate-400">Content Quality</div></div>
        </a>
    </div>

    <!-- Pending Approval Action Box (If any exist) -->
    <?php if (!empty($pending_events)): ?>
        <div class="glass-panel-elevated p-6 mb-10 border border-amber-500/40">
            <div class="flex items-center justify-between pb-4 border-b border-white/10 mb-4">
                <div class="flex items-center gap-2">
                    <span class="pulse-live"></span>
                    <h3 class="font-heading font-bold text-lg text-white">Pending Event Proposals (<?= count($pending_events) ?>)</h3>
                </div>
                <a href="<?= BASE_URL ?>/admin/manage_events.php" class="text-xs text-amber-400 hover:underline font-semibold">View All Proposals</a>
            </div>

            <div class="space-y-3">
                <?php foreach ($pending_events as $pev): ?>
                    <div class="p-4 rounded-xl bg-slate-900/60 border border-white/5 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                        <div>
                            <div class="flex items-center gap-2 mb-1">
                                <span class="badge-neon badge-amber text-[10px]">Awaiting Review</span>
                                <span class="text-xs text-slate-400">Proposed by <span class="text-cyan-400 font-bold"><?= htmlspecialchars($pev['organizer_name']) ?></span></span>
                            </div>
                            <h4 class="font-bold text-base text-white"><?= htmlspecialchars($pev['title']) ?></h4>
                            <div class="text-xs text-slate-400 mt-0.5">
                                Date: <?= format_event_date($pev['event_date']) ?> • Venue: <?= htmlspecialchars($pev['venue_name'] ?? 'Custom Venue') ?> • Capacity: <?= $pev['max_capacity'] ?> Seats
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <a href="<?= BASE_URL ?>/admin/manage_events.php" class="btn-neon-primary py-1.5 px-4 text-xs font-bold">
                                Review & Approve
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Two Columns: Department Leaderboard & Live Registrations Stream -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Department Leaderboard -->
        <div class="glass-panel p-6 border border-white/10">
            <h3 class="font-heading font-bold text-lg text-white mb-4 flex items-center gap-2">
                <i class="fa-solid fa-trophy text-amber-400"></i> Department Showcases
            </h3>
            <div class="space-y-3">
                <?php foreach ($dept_stats as $ds): ?>
                    <div class="p-3.5 rounded-xl bg-slate-900/60 border border-white/5 flex items-center justify-between">
                        <div>
                            <div class="font-bold text-xs text-white"><?= htmlspecialchars($ds['department']) ?></div>
                            <div class="text-[10px] text-slate-400"><?= $ds['total_seats'] ?> Total Seating Capacity</div>
                        </div>
                        <span class="badge-neon badge-purple text-xs">
                            <?= $ds['total_events'] ?> Events
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Recent Registrations Stream -->
        <div class="glass-panel p-6 border border-white/10">
            <h3 class="font-heading font-bold text-lg text-white mb-4 flex items-center gap-2">
                <i class="fa-solid fa-bolt text-cyan-400"></i> Live Participation Stream
            </h3>
            <div class="space-y-3">
                <?php foreach ($recent_regs as $rr): ?>
                    <div class="p-3.5 rounded-xl bg-slate-900/60 border border-white/5 flex items-center justify-between text-xs">
                        <div>
                            <div class="font-bold text-white"><?= htmlspecialchars($rr['student_name']) ?></div>
                            <div class="text-[11px] text-slate-400 line-clamp-1 truncate max-w-[200px]"><?= htmlspecialchars($rr['event_title']) ?></div>
                        </div>
                        <div class="text-right">
                            <span class="badge-neon badge-<?= $rr['status'] === 'attended' ? 'cyan' : ($rr['status'] === 'confirmed' ? 'emerald' : 'amber') ?> text-[10px]">
                                <?= strtoupper($rr['status']) ?>
                            </span>
                            <div class="text-[10px] text-slate-500 mt-0.5"><?= time_ago($rr['created_at']) ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
