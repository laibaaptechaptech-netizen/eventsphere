<?php
// student/my_events.php - Manage Student Registrations, Passes & Cancellations
$page_title = "My Event Registrations";
require_once __DIR__ . '/../config/auth_check.php';
require_student();

$user = current_user();
$db = getDB();

$tab = clean_input($_GET['tab'] ?? 'all'); // 'all', 'confirmed', 'attended', 'waitlisted', 'cancelled'

$sql = "
    SELECT r.*, e.title, e.event_date, e.start_time, e.end_time, e.banner_image, e.description, e.registration_cutoff,
           v.name as venue_name, c.name as category_name, c.badge_color
    FROM registrations r
    JOIN events e ON r.event_id = e.id
    JOIN categories c ON e.category_id = c.id
    LEFT JOIN venues v ON e.venue_id = v.id
    WHERE r.user_id = ?
";
$params = [$user['id']];

if ($tab === 'confirmed') {
    $sql .= " AND r.status = 'confirmed'";
} elseif ($tab === 'attended') {
    $sql .= " AND r.status = 'attended'";
} elseif ($tab === 'waitlisted') {
    $sql .= " AND r.status = 'waitlisted'";
} elseif ($tab === 'cancelled') {
    $sql .= " AND r.status = 'cancelled'";
}

$sql .= " ORDER BY e.event_date ASC";

$registrations = [];
try {
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $registrations = $stmt->fetchAll();
} catch (Exception $e) {}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <!-- Page Header -->
    <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="badge-neon badge-cyan mb-2">Participant Hub</div>
            <h1 class="font-heading font-extrabold text-3xl text-white">My Event Registrations</h1>
            <p class="text-slate-400 text-xs mt-1">Manage active digital ticket passes, track attendance history, or cancel registrations.</p>
        </div>
        <a href="<?= BASE_URL ?>/events.php" class="btn-neon-primary text-xs py-2.5 px-5 self-start md:self-auto">
            <i class="fa-solid fa-plus mr-1.5"></i> Register for More Events
        </a>
    </div>

    <!-- Status Filter Tabs -->
    <div class="flex items-center gap-2 overflow-x-auto pb-2 mb-8 border-b border-white/10 text-xs font-semibold">
        <a href="<?= BASE_URL ?>/student/my_events.php?tab=all" class="px-4 py-2 rounded-xl transition whitespace-nowrap <?= $tab === 'all' ? 'bg-purple-600 text-white shadow-lg shadow-purple-500/20' : 'bg-slate-800/80 text-slate-300 hover:bg-slate-700' ?>">
            All Passes
        </a>
        <a href="<?= BASE_URL ?>/student/my_events.php?tab=confirmed" class="px-4 py-2 rounded-xl transition whitespace-nowrap <?= $tab === 'confirmed' ? 'bg-emerald-600 text-white shadow-lg shadow-emerald-500/20' : 'bg-slate-800/80 text-slate-300 hover:bg-slate-700' ?>">
            Confirmed Passes
        </a>
        <a href="<?= BASE_URL ?>/student/my_events.php?tab=waitlisted" class="px-4 py-2 rounded-xl transition whitespace-nowrap <?= $tab === 'waitlisted' ? 'bg-amber-600 text-white shadow-lg shadow-amber-500/20' : 'bg-slate-800/80 text-slate-300 hover:bg-slate-700' ?>">
            Waitlisted
        </a>
        <a href="<?= BASE_URL ?>/student/my_events.php?tab=attended" class="px-4 py-2 rounded-xl transition whitespace-nowrap <?= $tab === 'attended' ? 'bg-cyan-600 text-white shadow-lg shadow-cyan-500/20' : 'bg-slate-800/80 text-slate-300 hover:bg-slate-700' ?>">
            Attended & Verified
        </a>
        <a href="<?= BASE_URL ?>/student/my_events.php?tab=cancelled" class="px-4 py-2 rounded-xl transition whitespace-nowrap <?= $tab === 'cancelled' ? 'bg-rose-600 text-white shadow-lg shadow-rose-500/20' : 'bg-slate-800/80 text-slate-300 hover:bg-slate-700' ?>">
            Cancelled
        </a>
    </div>

    <!-- Registrations List Grid -->
    <?php if (empty($registrations)): ?>
        <div class="glass-panel p-12 text-center max-w-lg mx-auto">
            <div class="w-16 h-16 rounded-2xl bg-slate-800 border border-white/10 flex items-center justify-center mx-auto mb-4 text-slate-400 text-2xl">
                <i class="fa-solid fa-ticket"></i>
            </div>
            <h3 class="font-heading font-bold text-xl text-white mb-2">No Registrations in this View</h3>
            <p class="text-slate-400 text-xs mb-6">Explore the upcoming event calendar and secure your participation pass.</p>
            <a href="<?= BASE_URL ?>/events.php" class="btn-neon-primary text-xs py-2 px-6">Explore All Events</a>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <?php foreach ($registrations as $reg): 
                $can_cancel = ($reg['status'] === 'confirmed' || $reg['status'] === 'waitlisted') && strtotime($reg['event_date']) >= strtotime(date('Y-m-d'));
            ?>
                <div class="glass-card-interactive p-6 rounded-2xl border border-white/10 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-3">
                            <span class="badge-neon badge-<?= $reg['status'] === 'confirmed' ? 'emerald' : ($reg['status'] === 'attended' ? 'cyan' : ($reg['status'] === 'waitlisted' ? 'amber' : 'rose')) ?>">
                                <i class="fa-solid <?= $reg['status'] === 'attended' ? 'fa-circle-check' : ($reg['status'] === 'confirmed' ? 'fa-ticket' : 'fa-clock') ?> mr-1"></i>
                                <?= strtoupper($reg['status']) ?>
                            </span>
                            <span class="text-xs font-mono font-bold text-purple-300 bg-purple-950/40 px-2 py-0.5 rounded border border-purple-500/20">
                                <?= htmlspecialchars($reg['registration_code']) ?>
                            </span>
                        </div>

                        <h3 class="font-heading font-bold text-lg text-white mb-2 line-clamp-1">
                            <a href="<?= BASE_URL ?>/event_detail.php?id=<?= $reg['event_id'] ?>" class="hover:text-cyan-300 transition">
                                <?= htmlspecialchars($reg['title']) ?>
                            </a>
                        </h3>

                        <div class="space-y-1.5 text-xs text-slate-300 mb-4">
                            <div class="flex items-center gap-2">
                                <i class="fa-regular fa-calendar text-cyan-400 w-4"></i>
                                <span><?= format_event_date($reg['event_date']) ?> (<?= format_event_time($reg['start_time']) ?>)</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <i class="fa-solid fa-location-dot text-rose-400 w-4"></i>
                                <span><?= htmlspecialchars($reg['venue_name'] ?? 'Main Campus') ?></span>
                            </div>
                            <?php if ($reg['checked_in_at']): ?>
                                <div class="flex items-center gap-2 text-emerald-400">
                                    <i class="fa-solid fa-qrcode w-4"></i>
                                    <span>Checked in at: <?= date('M d, Y h:i A', strtotime($reg['checked_in_at'])) ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Pass Actions -->
                    <div class="pt-4 border-t border-white/5 space-y-2.5">
                        <div class="flex items-center gap-2">
                            <?php if ($reg['status'] === 'confirmed' || $reg['status'] === 'attended'): ?>
                                <button onclick="showTicketPassModal('<?= htmlspecialchars(addslashes($reg['title'])) ?>', '<?= htmlspecialchars($reg['registration_code']) ?>', '<?= htmlspecialchars($reg['qr_token']) ?>', '<?= format_event_date($reg['event_date']) ?>', '<?= htmlspecialchars($reg['venue_name'] ?? 'Campus') ?>')" class="btn-neon-cyan flex-1 py-2 text-xs">
                                    <i class="fa-solid fa-qrcode mr-1"></i> View Entry QR
                                </button>
                            <?php endif; ?>

                            <?php if ($reg['status'] === 'attended'): ?>
                                <a href="<?= BASE_URL ?>/student/certificates.php" class="btn-neon-primary flex-1 py-2 text-xs text-center">
                                    <i class="fa-solid fa-award mr-1"></i> Get Certificate
                                </a>
                                <a href="<?= BASE_URL ?>/student/feedback.php?event_id=<?= $reg['event_id'] ?>" class="btn-glass py-2 px-3 text-xs" title="Submit Review">
                                    <i class="fa-solid fa-star text-amber-400"></i>
                                </a>
                            <?php endif; ?>

                            <?php if ($can_cancel): ?>
                                <form action="<?= BASE_URL ?>/api/cancel_registration.php" method="POST" onsubmit="return confirm('Are you sure you want to cancel your registration? If waitlisted students are in queue, the slot will be automatically transferred.');" class="inline">
                                    <input type="hidden" name="registration_id" value="<?= $reg['id'] ?>">
                                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                    <button type="submit" class="btn-glass py-2 px-3 text-xs text-rose-400 hover:text-rose-300 hover:bg-rose-950/40 border-rose-500/30" title="Cancel Registration">
                                        <i class="fa-solid fa-ban mr-1"></i> Cancel
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Modal: Digital QR Entry Pass Full View -->
<div id="ticketPassModal" class="hidden fixed inset-0 z-50 overflow-y-auto modal-backdrop-dark flex items-center justify-center p-4">
    <div class="glass-panel-elevated max-w-sm w-full p-6 text-center relative border border-purple-500/40">
        <button onclick="closeModal('ticketPassModal')" class="absolute top-4 right-4 text-slate-400 hover:text-white">
            <i class="fa-solid fa-xmark text-lg"></i>
        </button>

        <div class="badge-neon badge-purple mb-2">Verified Digital Entry Pass</div>
        <h3 id="ticketModalTitle" class="font-heading font-bold text-lg text-white mb-4 line-clamp-1">Event Title</h3>

        <div class="w-52 h-52 bg-white rounded-2xl p-3 mx-auto mb-4 shadow-2xl shadow-purple-500/20">
            <img id="ticketModalQr" src="" alt="QR Pass" class="w-full h-full object-contain">
        </div>

        <div class="bg-dark-900/80 p-3 rounded-xl border border-white/10 text-xs space-y-1 mb-4">
            <div class="flex justify-between text-slate-400">
                <span>Pass Code:</span>
                <span id="ticketModalCode" class="font-bold text-cyan-400 font-mono">REG-HN26-4091</span>
            </div>
            <div class="flex justify-between text-slate-400">
                <span>Date:</span>
                <span id="ticketModalDate" class="text-slate-200">Sep 15, 2026</span>
            </div>
            <div class="flex justify-between text-slate-400">
                <span>Venue:</span>
                <span id="ticketModalVenue" class="text-slate-200">Cyberdome</span>
            </div>
        </div>

        <button onclick="window.print()" class="btn-glass w-full py-2 text-xs">
            <i class="fa-solid fa-print mr-1"></i> Print / Save Pass
        </button>
    </div>
</div>

<script>
function showTicketPassModal(title, code, token, date, venue) {
    document.getElementById('ticketModalTitle').innerText = title;
    document.getElementById('ticketModalCode').innerText = code;
    document.getElementById('ticketModalDate').innerText = date;
    document.getElementById('ticketModalVenue').innerText = venue;
    document.getElementById('ticketModalQr').src = `https://api.qrserver.com/v1/create-qr-code/?size=250x250&color=0f172a&bgcolor=ffffff&data=${encodeURIComponent(token)}`;
    openModal('ticketPassModal');
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
