<?php
// organizer/scan_qr.php - Live Browser Camera QR Scanner & Gate Attendance Pass Verifier
$page_title = "Live QR Attendance Scanner";
require_once __DIR__ . '/../config/auth_check.php';
require_organizer();

$user = current_user();
$db = getDB();

$scan_result = null;
$error = null;

// Handle QR Token / Pass Code Check-in POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $qr_token = clean_input($_POST['qr_token'] ?? '');

    if (empty($qr_token)) {
        $error = "Please provide or scan a valid QR pass code.";
    } else {
        $stmt = $db->prepare("
            SELECT r.*, e.title as event_title, e.event_date, u.name as student_name, u.email as student_email, u.enrolment_no, u.department as student_dept, u.avatar
            FROM registrations r
            JOIN events e ON r.event_id = e.id
            JOIN users u ON r.user_id = u.id
            WHERE r.qr_token = ? OR r.registration_code = ?
            LIMIT 1
        ");
        $stmt->execute([$qr_token, $qr_token]);
        $reg = $stmt->fetch();

        if (!$reg) {
            $error = "Invalid pass token: No matching registration found in database.";
        } else {
            if ($reg['status'] === 'attended') {
                $scan_result = [
                    'type' => 'already_checked_in',
                    'data' => $reg,
                    'message' => 'ALREADY CHECKED IN at ' . date('h:i A', strtotime($reg['checked_in_at']))
                ];
            } elseif ($reg['status'] === 'cancelled') {
                $error = "Access Denied: This registration was CANCELLED.";
            } else {
                // Mark Attended
                $update = $db->prepare("UPDATE registrations SET status = 'attended', checked_in_at = NOW() WHERE id = ?");
                $update->execute([$reg['id']]);
                
                $reg['checked_in_at'] = date('Y-m-d H:i:s');
                $scan_result = [
                    'type' => 'success',
                    'data' => $reg,
                    'message' => 'ATTENDANCE VERIFIED & LOGGED!'
                ];

                create_notification(
                    $reg['user_id'],
                    '✅ Gate Check-in Confirmed!',
                    'You have been checked in for ' . $reg['event_title'] . ' at ' . date('h:i A'),
                    BASE_URL . '/student/my_events.php',
                    'success'
                );
            }
        }
    }
}

// Fetch Recent Check-ins today
$recent_checkins = [];
try {
    $stmt = $db->query("
        SELECT r.*, u.name as student_name, u.enrolment_no, e.title as event_title
        FROM registrations r
        JOIN users u ON r.user_id = u.id
        JOIN events e ON r.event_id = e.id
        WHERE r.status = 'attended' AND r.checked_in_at IS NOT NULL
        ORDER BY r.checked_in_at DESC
        LIMIT 6
    ");
    $recent_checkins = $stmt->fetchAll();
} catch (Exception $e) {}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="badge-neon badge-cyan mb-2">Gate Operations</div>
            <h1 class="font-heading font-extrabold text-3xl text-white">Live QR Attendance Scanner</h1>
            <p class="text-slate-400 text-xs mt-1">Scan student digital entry passes via camera or enter pass codes for instantaneous gate check-in.</p>
        </div>
        <a href="<?= BASE_URL ?>/organizer/manage_attendees.php" class="btn-glass text-xs py-2 px-4 self-start md:self-auto">
            <i class="fa-solid fa-list-check mr-1.5 text-cyan-400"></i> Full Attendee Roster
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Left: In-Browser Webcam Scanner & Manual Input (2 cols) -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Camera Scanner Card -->
            <div class="glass-panel-elevated p-6 border border-purple-500/30 text-center relative">
                <div class="flex items-center justify-between mb-4 pb-3 border-b border-white/10">
                    <h3 class="font-heading font-bold text-base text-white flex items-center gap-2">
                        <i class="fa-solid fa-camera text-cyan-400"></i> Active Camera Feed
                    </h3>
                    <span class="pulse-live"></span>
                </div>

                <div id="qr-reader" class="mx-auto rounded-2xl overflow-hidden bg-black max-w-sm w-full border-2 border-purple-500/40 shadow-2xl mb-4 min-h-[260px] flex items-center justify-center text-slate-500 text-xs">
                    Initializing camera...
                </div>

                <div id="camera-fallback-alert" class="hidden p-3 bg-amber-950/40 border border-amber-500/30 rounded-xl text-xs text-amber-300 mb-4">
                    <i class="fa-solid fa-triangle-exclamation mr-1"></i> Camera permission needed. You can also type pass codes in manual input below.
                </div>

                <!-- Manual Pass Code / Token Form -->
                <form id="qrScanForm" method="POST" action="<?= BASE_URL ?>/organizer/scan_qr.php" class="pt-4 border-t border-white/10 flex items-center gap-2">
                    <div class="relative flex-1">
                        <i class="fa-solid fa-barcode absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                        <input type="text" id="manualQrInput" name="qr_token" required placeholder="Enter Pass Code (e.g. REG-HN26-4091 or QR Token)" class="form-input-dark pl-10 text-xs font-mono">
                    </div>
                    <button type="submit" class="btn-neon-primary text-xs py-2.5 px-6 font-bold shadow-lg shadow-purple-600/30">
                        <i class="fa-solid fa-bolt mr-1"></i> Check In
                    </button>
                </form>
            </div>

            <!-- Scan Result Display Feedback -->
            <?php if ($error): ?>
                <div class="glass-panel p-5 border border-rose-500/40 bg-rose-950/20 text-rose-300 flex items-center gap-4 animate-shake">
                    <div class="w-12 h-12 rounded-xl bg-rose-500/20 text-rose-400 flex items-center justify-center text-2xl flex-shrink-0">
                        <i class="fa-solid fa-xmark"></i>
                    </div>
                    <div>
                        <h4 class="font-bold text-sm text-white">Scan Error / Access Denied</h4>
                        <p class="text-xs text-rose-300/90"><?= htmlspecialchars($error) ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($scan_result): 
                $data = $scan_result['data'];
                $is_already = ($scan_result['type'] === 'already_checked_in');
            ?>
                <div class="glass-panel p-6 border <?= $is_already ? 'border-amber-500/40 bg-amber-950/20' : 'border-emerald-500/40 bg-emerald-950/20' ?> rounded-2xl flex flex-col sm:flex-row items-center gap-5">
                    <img src="<?= htmlspecialchars(!empty($data['avatar']) ? $data['avatar'] : 'https://ui-avatars.com/api/?name='.urlencode($data['student_name']).'&background=0D8ABC&color=fff&size=150') ?>" class="w-20 h-20 rounded-2xl object-cover border-2 <?= $is_already ? 'border-amber-400' : 'border-emerald-400' ?> flex-shrink-0">
                    <div class="space-y-1 text-center sm:text-left flex-1">
                        <div class="badge-neon <?= $is_already ? 'badge-amber' : 'badge-emerald' ?> text-[10px] mb-1">
                            <i class="fa-solid fa-circle-check mr-1"></i> <?= $scan_result['message'] ?>
                        </div>
                        <h3 class="font-heading font-bold text-xl text-white"><?= htmlspecialchars($data['student_name']) ?></h3>
                        <div class="text-xs text-slate-300">
                            Enrolment: <span class="font-mono text-cyan-400 font-bold"><?= htmlspecialchars($data['enrolment_no'] ?? 'N/A') ?></span> • <?= htmlspecialchars($data['student_dept']) ?>
                        </div>
                        <div class="text-xs text-purple-300 font-semibold">
                            Event: <?= htmlspecialchars($data['event_title']) ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Right: Recent Scans Log (1 col) -->
        <div class="space-y-6">
            <div class="glass-panel p-6 border border-white/10">
                <div class="flex items-center justify-between pb-3 border-b border-white/10 mb-4">
                    <h4 class="font-heading font-bold text-base text-white flex items-center gap-2">
                        <i class="fa-solid fa-clock-rotate-left text-cyan-400"></i> Recent Gate Scans
                    </h4>
                </div>

                <div class="space-y-3">
                    <?php if (empty($recent_checkins)): ?>
                        <div class="text-center py-6 text-slate-400 text-xs">No check-ins logged yet today.</div>
                    <?php else: ?>
                        <?php foreach ($recent_checkins as $chk): ?>
                            <div class="p-3 rounded-xl bg-slate-900/60 border border-white/5 space-y-1">
                                <div class="flex items-center justify-between text-xs">
                                    <span class="font-bold text-white"><?= htmlspecialchars($chk['student_name']) ?></span>
                                    <span class="text-[10px] text-emerald-400 font-semibold font-mono"><?= date('h:i:s A', strtotime($chk['checked_in_at'])) ?></span>
                                </div>
                                <div class="text-[11px] text-slate-400 flex items-center justify-between">
                                    <span><?= htmlspecialchars($chk['enrolment_no'] ?? 'Student') ?></span>
                                    <span class="text-purple-300 line-clamp-1 truncate max-w-[120px]"><?= htmlspecialchars($chk['event_title']) ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="<?= BASE_URL ?>/assets/js/qr_scanner.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    initQrScanner((decodedText) => {
        document.getElementById('manualQrInput').value = decodedText;
        showToast('info', 'QR Scanned', `Processing pass: ${decodedText}`);
        document.getElementById('qrScanForm').submit();
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
