<?php
// student/dashboard.php - Participant Hub & Digital Ticket Passes
$page_title = "Participant Dashboard";
require_once __DIR__ . '/../config/auth_check.php';
require_student();

$user = current_user();
$db = getDB();

// Fetch Student Metrics
$registered_count = 0;
$attended_count = 0;
$cert_count = 0;
$bookmark_count = 0;
try {
    $stmt = $db->prepare("SELECT COUNT(*) FROM registrations WHERE user_id = ? AND status = 'confirmed'");
    $stmt->execute([$user['id']]);
    $registered_count = (int)$stmt->fetchColumn();

    $stmt = $db->prepare("SELECT COUNT(*) FROM registrations WHERE user_id = ? AND status = 'attended'");
    $stmt->execute([$user['id']]);
    $attended_count = (int)$stmt->fetchColumn();

    $stmt = $db->prepare("SELECT COUNT(*) FROM registrations WHERE user_id = ? AND status = 'attended' AND certificate_issued = 1");
    $stmt->execute([$user['id']]);
    $cert_count = (int)$stmt->fetchColumn();

    $stmt = $db->prepare("SELECT COUNT(*) FROM event_bookmarks WHERE user_id = ?");
    $stmt->execute([$user['id']]);
    $bookmark_count = (int)$stmt->fetchColumn();
} catch (Exception $e) {}

// Fetch Active Upcoming Registered Passes
$active_passes = [];
try {
    $stmt = $db->prepare("
        SELECT r.*, e.title, e.event_date, e.start_time, e.end_time, e.banner_image, e.description,
               v.name as venue_name, c.name as category_name, c.badge_color
        FROM registrations r
        JOIN events e ON r.event_id = e.id
        JOIN categories c ON e.category_id = c.id
        LEFT JOIN venues v ON e.venue_id = v.id
        WHERE r.user_id = ? AND r.status IN ('confirmed', 'waitlisted')
        ORDER BY e.event_date ASC
    ");
    $stmt->execute([$user['id']]);
    $active_passes = $stmt->fetchAll();
} catch (Exception $e) {}

// Fetch Recent Notifications
$notifications = [];
try {
    $stmt = $db->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 4");
    $stmt->execute([$user['id']]);
    $notifications = $stmt->fetchAll();
} catch (Exception $e) {}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <!-- Welcome Banner Header -->
    <div class="glass-panel-elevated p-8 mb-10 border border-purple-500/30 relative overflow-hidden">
        <div class="absolute right-0 top-0 w-80 h-80 bg-purple-600/10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="relative z-10 flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
            <div class="flex items-center gap-4">
                <img src="<?= htmlspecialchars(!empty($user['avatar']) ? $user['avatar'] : 'https://ui-avatars.com/api/?name='.urlencode($user['name']).'&background=0D8ABC&color=fff&size=150') ?>" class="w-16 h-16 rounded-2xl object-cover border-2 border-purple-500/50 shadow-xl shadow-purple-500/20">
                <div>
                    <div class="badge-neon badge-emerald mb-1">Student Participant</div>
                    <h1 class="font-heading font-extrabold text-2xl sm:text-3xl text-white">
                        Welcome back, <?= htmlspecialchars($user['name']) ?>!
                    </h1>
                    <p class="text-xs text-slate-400">
                        Enrolment: <span class="text-cyan-400 font-semibold"><?= htmlspecialchars($user['enrolment_no'] ?? 'N/A') ?></span> • Department: <?= htmlspecialchars($user['department']) ?>
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <a href="<?= BASE_URL ?>/events.php" class="btn-neon-primary text-xs py-2.5 px-5">
                    <i class="fa-solid fa-compass mr-1.5"></i> Discover Events
                </a>
                <a href="<?= BASE_URL ?>/student/certificates.php" class="btn-glass text-xs py-2.5 px-4">
                    <i class="fa-solid fa-award mr-1.5 text-amber-400"></i> Certificates (<?= $cert_count ?>)
                </a>
            </div>
        </div>
    </div>

    <!-- 4 Stats Metric Cards Grid -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6 mb-10">
        <div class="glass-card-interactive tilt-card p-5 border border-cyan-500/20">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Active Passes</span>
                <div class="w-8 h-8 rounded-lg bg-cyan-500/10 text-cyan-400 flex items-center justify-center text-sm">
                    <i class="fa-solid fa-ticket"></i>
                </div>
            </div>
            <div class="font-heading font-bold text-3xl text-cyan-300"><?= $registered_count ?></div>
            <div class="text-[11px] text-slate-400 mt-1">Confirmed upcoming entries</div>
        </div>

        <div class="glass-card-interactive tilt-card p-5 border border-purple-500/20">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Attended</span>
                <div class="w-8 h-8 rounded-lg bg-purple-500/10 text-purple-400 flex items-center justify-center text-sm">
                    <i class="fa-solid fa-calendar-check"></i>
                </div>
            </div>
            <div class="font-heading font-bold text-3xl text-purple-300"><?= $attended_count ?></div>
            <div class="text-[11px] text-slate-400 mt-1">QR check-ins verified</div>
        </div>

        <div class="glass-card-interactive tilt-card p-5 border border-amber-500/20">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">E-Certificates</span>
                <div class="w-8 h-8 rounded-lg bg-amber-500/10 text-amber-400 flex items-center justify-center text-sm">
                    <i class="fa-solid fa-award"></i>
                </div>
            </div>
            <div class="font-heading font-bold text-3xl text-amber-300"><?= $cert_count ?></div>
            <div class="text-[11px] text-slate-400 mt-1">Official verifiable awards</div>
        </div>

        <div class="glass-card-interactive tilt-card p-5 border border-rose-500/20">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Saved Items</span>
                <div class="w-8 h-8 rounded-lg bg-rose-500/10 text-rose-400 flex items-center justify-center text-sm">
                    <i class="fa-solid fa-bookmark"></i>
                </div>
            </div>
            <div class="font-heading font-bold text-3xl text-rose-300"><?= $bookmark_count ?></div>
            <div class="text-[11px] text-slate-400 mt-1">Bookmarked experiences</div>
        </div>
    </div>

    <!-- Main Content Area: Active Passes & Notifications Feed -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Left: Active Passes Grid (2 cols) -->
        <div class="lg:col-span-2 space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="font-heading font-bold text-xl text-white">My Active Event Passes</h3>
                    <p class="text-slate-400 text-xs">Present QR passes at the venue gate for instant check-in.</p>
                </div>
                <a href="<?= BASE_URL ?>/student/my_events.php" class="text-xs text-cyan-400 hover:underline">
                    View All (<?= count($active_passes) ?>)
                </a>
            </div>

            <?php if (empty($active_passes)): ?>
                <div class="glass-panel p-10 text-center">
                    <div class="w-14 h-14 rounded-2xl bg-slate-800 border border-white/10 flex items-center justify-center mx-auto mb-3 text-slate-400 text-xl">
                        <i class="fa-solid fa-ticket"></i>
                    </div>
                    <h4 class="font-bold text-white text-base mb-1">No Active Event Passes</h4>
                    <p class="text-slate-400 text-xs mb-4">You have not registered for any upcoming events yet.</p>
                    <a href="<?= BASE_URL ?>/events.php" class="btn-neon-primary text-xs py-2 px-5">
                        Browse Upcoming Events
                    </a>
                </div>
            <?php else: ?>
                <div class="space-y-4">
                    <?php foreach ($active_passes as $pass): ?>
                        <div class="glass-card-interactive p-5 rounded-2xl border border-white/10 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-5">
                            <div class="flex items-start gap-4 flex-1">
                                <!-- Pass Dynamic QR Preview -->
                                <div class="w-20 h-20 bg-dark-900 rounded-xl p-1.5 border border-purple-500/40 flex-shrink-0">
                                    <img src="<?= get_qr_image_url($pass['qr_token'], 150) ?>" alt="QR Pass" class="w-full h-full object-contain">
                                </div>
                                <div class="space-y-1">
                                    <div class="flex items-center gap-2">
                                        <span class="badge-neon badge-<?= $pass['status'] === 'confirmed' ? 'emerald' : 'amber' ?> text-[10px]">
                                            <?= strtoupper($pass['status']) ?>
                                        </span>
                                        <span class="text-xs text-purple-400 font-bold font-mono">
                                            <?= htmlspecialchars($pass['registration_code']) ?>
                                        </span>
                                    </div>
                                    <h4 class="font-heading font-bold text-base text-white line-clamp-1">
                                        <a href="<?= BASE_URL ?>/event_detail.php?id=<?= $pass['event_id'] ?>" class="hover:text-cyan-300 transition">
                                            <?= htmlspecialchars($pass['title']) ?>
                                        </a>
                                    </h4>
                                    <div class="text-xs text-slate-400 flex flex-wrap items-center gap-3">
                                        <span><i class="fa-regular fa-calendar text-cyan-400 mr-1"></i> <?= format_event_date($pass['event_date']) ?></span>
                                        <span><i class="fa-solid fa-location-dot text-rose-400 mr-1"></i> <?= htmlspecialchars($pass['venue_name'] ?? 'Auditorium') ?></span>
                                    </div>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="flex sm:flex-col items-center gap-2 w-full sm:w-auto">
                                <button onclick="showTicketPassModal('<?= htmlspecialchars(addslashes($pass['title'])) ?>', '<?= htmlspecialchars($pass['registration_code']) ?>', '<?= htmlspecialchars($pass['qr_token']) ?>', '<?= format_event_date($pass['event_date']) ?>', '<?= htmlspecialchars($pass['venue_name'] ?? 'Main Campus') ?>')" class="btn-neon-cyan text-xs py-2 px-4 w-full sm:w-auto whitespace-nowrap">
                                    <i class="fa-solid fa-qrcode mr-1"></i> View QR Pass
                                </button>
                                <a href="<?= BASE_URL ?>/event_detail.php?id=<?= $pass['event_id'] ?>" class="btn-glass text-xs py-2 px-3 w-full sm:w-auto text-center">
                                    Details
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Right: Notifications & Quick Profile (1 col) -->
        <div class="space-y-6">
            <!-- Notifications Feed -->
            <div class="glass-panel p-6 border border-white/10">
                <div class="flex items-center justify-between pb-3 border-b border-white/10 mb-4">
                    <h4 class="font-heading font-bold text-base text-white flex items-center gap-2">
                        <i class="fa-solid fa-bell text-purple-400"></i> Recent Alerts
                    </h4>
                </div>

                <div class="space-y-3">
                    <?php if (empty($notifications)): ?>
                        <div class="text-center py-6 text-slate-400 text-xs">No alerts to display.</div>
                    <?php else: ?>
                        <?php foreach ($notifications as $notif): ?>
                            <div class="p-3 rounded-xl bg-slate-900/60 border border-white/5 space-y-1">
                                <div class="flex items-center justify-between text-xs">
                                    <span class="font-bold text-slate-200"><?= htmlspecialchars($notif['title']) ?></span>
                                    <span class="text-[10px] text-slate-500"><?= time_ago($notif['created_at']) ?></span>
                                </div>
                                <p class="text-xs text-slate-400 leading-relaxed"><?= htmlspecialchars($notif['message']) ?></p>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Quick Profile Overview -->
            <div class="glass-panel p-6 border border-white/10 space-y-4">
                <h4 class="font-heading font-bold text-base text-white">Student Account</h4>
                <div class="space-y-2 text-xs text-slate-300">
                    <div class="flex justify-between py-1.5 border-b border-white/5">
                        <span class="text-slate-400">Username:</span>
                        <span class="font-mono text-white"><?= htmlspecialchars($user['username']) ?></span>
                    </div>
                    <div class="flex justify-between py-1.5 border-b border-white/5">
                        <span class="text-slate-400">Email:</span>
                        <span class="text-white"><?= htmlspecialchars($user['email']) ?></span>
                    </div>
                    <div class="flex justify-between py-1.5 border-b border-white/5">
                        <span class="text-slate-400">Status:</span>
                        <span class="text-emerald-400 font-bold">Active Participant</span>
                    </div>
                </div>
                <a href="<?= BASE_URL ?>/student/profile.php" class="btn-glass w-full py-2 text-xs text-center block">
                    <i class="fa-solid fa-user-pen mr-1"></i> Edit Profile Details
                </a>
            </div>
        </div>
    </div>
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

        <p class="text-[11px] text-slate-400 mb-4">Keep this screen active or save offline for rapid gate check-in.</p>
        <button onclick="window.print()" class="btn-glass w-full py-2 text-xs">
            <i class="fa-solid fa-print mr-1"></i> Print / Save Ticket
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
