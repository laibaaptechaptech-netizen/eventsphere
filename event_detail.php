<?php
// event_detail.php - Rich Single Event Showcase & Registration Portal
require_once __DIR__ . '/config/config.php';

$db = getDB();
$event_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Fetch Event Details
$stmt = $db->prepare("
    SELECT e.*, c.name as category_name, c.slug as category_slug, c.badge_color,
           v.name as venue_name, v.building as venue_building, v.location_details as venue_details, v.max_capacity as venue_max,
           u.name as organizer_name, u.email as organizer_email, u.department as organizer_dept, u.avatar as organizer_avatar
    FROM events e
    JOIN categories c ON e.category_id = c.id
    LEFT JOIN venues v ON e.venue_id = v.id
    JOIN users u ON e.organizer_id = u.id
    WHERE e.id = ? AND e.status IN ('approved', 'completed')
");
$stmt->execute([$event_id]);
$event = $stmt->fetch();

if (!$event) {
    set_flash('error', 'Event not found or has not been approved yet.');
    header("Location: " . BASE_URL . "/events.php");
    exit;
}

$page_title = $event['title'];
require_once __DIR__ . '/includes/header.php';

// Capacity Info
$cap_info = get_event_capacity_info($event['id']);
$is_past = strtotime($event['event_date']) < strtotime(date('Y-m-d'));

// Check current user's registration status
$user_reg = null;
if ($current_user) {
    $reg_stmt = $db->prepare("SELECT * FROM registrations WHERE event_id = ? AND user_id = ?");
    $reg_stmt->execute([$event['id'], $current_user['id']]);
    $user_reg = $reg_stmt->fetch();
}

// Fetch Reviews & Aggregates
$reviews_stmt = $db->prepare("
    SELECT r.*, u.name as reviewer_name, u.avatar as reviewer_avatar 
    FROM feedback_reviews r
    JOIN users u ON r.user_id = u.id
    WHERE r.event_id = ? AND r.is_approved = 1
    ORDER BY r.created_at DESC
");
$reviews_stmt->execute([$event['id']]);
$reviews = $reviews_stmt->fetchAll();

// Calculate Average Ratings
$avg_overall = 0;
$avg_venue = 0;
$avg_coord = 0;
$avg_tech = 0;
$avg_hosp = 0;
$review_count = count($reviews);

if ($review_count > 0) {
    foreach ($reviews as $rev) {
        $avg_overall += $rev['overall_rating'];
        $avg_venue += $rev['rating_venue'];
        $avg_coord += $rev['rating_coordination'];
        $avg_tech += $rev['rating_technical'];
        $avg_hosp += $rev['rating_hospitality'];
    }
    $avg_overall = round($avg_overall / $review_count, 1);
    $avg_venue = round($avg_venue / $review_count, 1);
    $avg_coord = round($avg_coord / $review_count, 1);
    $avg_tech = round($avg_tech / $review_count, 1);
    $avg_hosp = round($avg_hosp / $review_count, 1);
}

// ISO Date for Calendar Integration
$start_iso = date('Y-m-d\TH:i:s', strtotime($event['event_date'] . ' ' . $event['start_time']));
$end_iso = date('Y-m-d\TH:i:s', strtotime($event['event_date'] . ' ' . $event['end_time']));
$venue_full = ($event['venue_name'] ?? 'Main Auditorium') . ' - ' . ($event['venue_building'] ?? 'Campus Central');
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <!-- Breadcrumb -->
    <nav class="flex items-center gap-2 text-xs text-slate-400 mb-6">
        <a href="<?= BASE_URL ?>/index.php" class="hover:text-white">Home</a>
        <i class="fa-solid fa-chevron-right text-[10px]"></i>
        <a href="<?= BASE_URL ?>/events.php" class="hover:text-white">Events</a>
        <i class="fa-solid fa-chevron-right text-[10px]"></i>
        <span class="text-cyan-400 line-clamp-1"><?= htmlspecialchars($event['title']) ?></span>
    </nav>

    <!-- Main Grid: Left Details & Right Action Sidebar -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-10">
        <!-- Left Column: Banner, Details, Agenda, Reviews (2 cols) -->
        <div class="lg:col-span-2 space-y-8">
            <!-- Event Hero Banner Card -->
            <div class="glass-panel overflow-hidden border border-white/10 relative">
                <div class="relative h-72 sm:h-96 w-full bg-slate-900">
                    <img src="<?= htmlspecialchars($event['banner_image'] ?? 'https://images.unsplash.com/photo-1504384308090-c894fdcc538d?auto=format&fit=crop&w=1200&q=80') ?>" alt="<?= htmlspecialchars($event['title']) ?>" class="w-full h-full object-cover">
                    <div class="absolute inset-0 bg-gradient-to-t from-dark-900 via-dark-900/40 to-transparent"></div>
                    
                    <div class="absolute top-4 left-4 flex items-center gap-2">
                        <span class="badge-neon badge-<?= $event['badge_color'] ?? 'cyan' ?>">
                            <?= htmlspecialchars($event['category_name']) ?>
                        </span>
                        <span class="badge-neon badge-purple">
                            Dept: <?= htmlspecialchars($event['department']) ?>
                        </span>
                    </div>

                    <div class="absolute top-4 right-4 flex items-center gap-2">
                        <button onclick="toggleEventBookmark(this, <?= $event['id'] ?>)" class="w-10 h-10 rounded-xl bg-dark-900/80 hover:bg-dark-900 border border-white/20 flex items-center justify-center text-slate-200 hover:text-amber-400 transition" title="Save Event">
                            <i class="fa-regular fa-bookmark"></i>
                        </button>
                        <button onclick="openShareModal('<?= htmlspecialchars(addslashes($event['title'])) ?>', '<?= BASE_URL ?>/event_detail.php?id=<?= $event['id'] ?>', '<?= htmlspecialchars(addslashes(substr($event['description'], 0, 120))) ?>')" class="w-10 h-10 rounded-xl bg-dark-900/80 hover:bg-dark-900 border border-white/20 flex items-center justify-center text-slate-200 hover:text-cyan-400 transition" title="Share Event">
                            <i class="fa-solid fa-share-nodes"></i>
                        </button>
                    </div>

                    <div class="absolute bottom-6 left-6 right-6">
                        <h1 class="font-heading font-extrabold text-2xl sm:text-4xl text-white mb-2 leading-tight">
                            <?= htmlspecialchars($event['title']) ?>
                        </h1>
                        <div class="flex flex-wrap items-center gap-4 text-xs text-slate-300">
                            <span class="flex items-center gap-1.5 text-cyan-300">
                                <i class="fa-regular fa-calendar"></i> <?= format_event_date($event['event_date']) ?>
                            </span>
                            <span class="flex items-center gap-1.5 text-purple-300">
                                <i class="fa-regular fa-clock"></i> <?= format_event_time($event['start_time']) ?> - <?= format_event_time($event['end_time']) ?>
                            </span>
                            <span class="flex items-center gap-1.5 text-emerald-300">
                                <i class="fa-solid fa-location-dot"></i> <?= htmlspecialchars($event['venue_name'] ?? 'Main Campus') ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Comprehensive Event Description -->
            <div class="glass-panel p-6 sm:p-8 space-y-6">
                <div>
                    <h3 class="font-heading font-bold text-xl text-white mb-3 flex items-center gap-2">
                        <i class="fa-solid fa-circle-info text-cyan-400"></i> Event Overview & Guidelines
                    </h3>
                    <div class="text-slate-300 text-sm leading-relaxed whitespace-pre-line">
                        <?= htmlspecialchars($event['description']) ?>
                    </div>
                </div>



                    <!-- Official Program Agenda & Timeline Breakdown -->
                    <div class="space-y-3">
                        <h4 class="font-heading font-bold text-sm uppercase tracking-wider text-cyan-400 flex items-center gap-2">
                            <i class="fa-solid fa-clock-rotate-left"></i> Official Agenda & Session Schedule
                        </h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-xs">
                            <div class="p-3.5 rounded-xl bg-slate-900/60 border border-white/5 space-y-1">
                                <div class="flex items-center justify-between text-slate-300 font-semibold">
                                    <span class="text-cyan-300 font-mono"><i class="fa-regular fa-clock mr-1"></i> 09:00 AM – 10:00 AM</span>
                                    <span class="badge-neon badge-purple text-[10px]">Gate In</span>
                                </div>
                                <div class="font-bold text-white text-xs">Attendee Check-in & Security Verification</div>
                                <p class="text-slate-400 text-[11px] leading-relaxed">Present digital QR pass, collect participant identification lanyard, and claim event registration kit.</p>
                            </div>

                            <div class="p-3.5 rounded-xl bg-slate-900/60 border border-white/5 space-y-1">
                                <div class="flex items-center justify-between text-slate-300 font-semibold">
                                    <span class="text-cyan-300 font-mono"><i class="fa-regular fa-clock mr-1"></i> 10:00 AM – 10:45 AM</span>
                                    <span class="badge-neon badge-cyan text-[10px]">Main Stage</span>
                                </div>
                                <div class="font-bold text-white text-xs">Inaugural Keynote & Competition Briefing</div>
                                <p class="text-slate-400 text-[11px] leading-relaxed">Welcome address by organizing chairs, technical rulebook overview, and problem statement release.</p>
                            </div>

                            <div class="p-3.5 rounded-xl bg-slate-900/60 border border-white/5 space-y-1">
                                <div class="flex items-center justify-between text-slate-300 font-semibold">
                                    <span class="text-cyan-300 font-mono"><i class="fa-regular fa-clock mr-1"></i> 11:00 AM – 01:30 PM</span>
                                    <span class="badge-neon badge-emerald text-[10px]">Round 1</span>
                                </div>
                                <div class="font-bold text-white text-xs">Technical Sprints / Workshop Sessions</div>
                                <p class="text-slate-400 text-[11px] leading-relaxed">Core development round, lab experiments, and mentor checkpoints across designated computing clusters.</p>
                            </div>

                            <div class="p-3.5 rounded-xl bg-slate-900/60 border border-white/5 space-y-1">
                                <div class="flex items-center justify-between text-slate-300 font-semibold">
                                    <span class="text-cyan-300 font-mono"><i class="fa-regular fa-clock mr-1"></i> 01:30 PM – 02:30 PM</span>
                                    <span class="badge-neon badge-amber text-[10px]">Atrium</span>
                                </div>
                                <div class="font-bold text-white text-xs">Networking Lunch & Industry Connect</div>
                                <p class="text-slate-400 text-[11px] leading-relaxed">Complimentary luncheon provided for all confirmed attendees with sponsor company booths.</p>
                            </div>

                            <div class="p-3.5 rounded-xl bg-slate-900/60 border border-white/5 space-y-1">
                                <div class="flex items-center justify-between text-slate-300 font-semibold">
                                    <span class="text-cyan-300 font-mono"><i class="fa-regular fa-clock mr-1"></i> 02:30 PM – 05:00 PM</span>
                                    <span class="badge-neon badge-rose text-[10px]">Judging</span>
                                </div>
                                <div class="font-bold text-white text-xs">Live Demonstration & Jury Evaluation</div>
                                <p class="text-slate-400 text-[11px] leading-relaxed">Final project submissions, 5-minute live pitch to jury panel, and technical stress-testing.</p>
                            </div>

                            <div class="p-3.5 rounded-xl bg-slate-900/60 border border-white/5 space-y-1">
                                <div class="flex items-center justify-between text-slate-300 font-semibold">
                                    <span class="text-cyan-300 font-mono"><i class="fa-regular fa-clock mr-1"></i> 05:30 PM – 06:30 PM</span>
                                    <span class="badge-neon badge-purple text-[10px]">Awards</span>
                                </div>
                                <div class="font-bold text-white text-xs">Valedictory Gala & Certificate Rollout</div>
                                <p class="text-slate-400 text-[11px] leading-relaxed">Winner prize announcements, felicitation of participants, and issuance of digital e-certificates.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Official Competition Rulebook & Regulations -->
                    <div class="space-y-3">
                        <h4 class="font-heading font-bold text-sm uppercase tracking-wider text-purple-400 flex items-center gap-2">
                            <i class="fa-solid fa-scale-balanced"></i> Competition Rulebook & Regulations
                        </h4>
                        <div class="bg-dark-900/80 p-4 rounded-xl border border-white/10 space-y-2.5 text-xs text-slate-300">
                            <div class="flex items-start gap-2.5">
                                <i class="fa-solid fa-check-circle text-cyan-400 mt-0.5 flex-shrink-0"></i>
                                <div>
                                    <span class="font-bold text-white">Eligibility & Credentials:</span> Valid university student ID and confirmed digital EventSphere pass required for gate admission.
                                </div>
                            </div>
                            <div class="flex items-start gap-2.5">
                                <i class="fa-solid fa-check-circle text-cyan-400 mt-0.5 flex-shrink-0"></i>
                                <div>
                                    <span class="font-bold text-white">Academic Integrity & Originality:</span> All code, design assets, and competition deliverables must be created within event hours. Plagiarism will lead to immediate disqualification.
                                </div>
                            </div>
                            <div class="flex items-start gap-2.5">
                                <i class="fa-solid fa-check-circle text-cyan-400 mt-0.5 flex-shrink-0"></i>
                                <div>
                                    <span class="font-bold text-white">Equipment & Infrastructure:</span> Participants must bring personal laptops and adapters. High-speed campus Wi-Fi (SSID: EventSphere-Secure), power sockets, and test clusters are provided.
                                </div>
                            </div>
                            <div class="flex items-start gap-2.5">
                                <i class="fa-solid fa-check-circle text-cyan-400 mt-0.5 flex-shrink-0"></i>
                                <div>
                                    <span class="font-bold text-white">Evaluation Rubric:</span> Submissions are scored on Innovation (30%), Technical Execution (25%), Practical Utility (25%), and Presentation (20%).
                                </div>
                            </div>
                            <div class="flex items-start gap-2.5">
                                <i class="fa-solid fa-check-circle text-cyan-400 mt-0.5 flex-shrink-0"></i>
                                <div>
                                    <span class="font-bold text-white">E-Certificate Entitlement:</span> Official verifiable e-certificates are exclusively awarded to registered participants who complete mandatory gate check-in and attend the event.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            

            

            <!-- Registration & Ticket Pass Box -->
            <div class="glass-panel-elevated p-6 border border-purple-500/30 ">
                <div class="badge-neon badge-purple mb-3">Seat Management</div>
                <h3 class="font-heading font-bold text-xl text-white mb-4">Registration Status</h3>

                <!-- Dynamic Live Capacity Progress -->
                <div class="bg-dark-900/80 p-4 rounded-xl border border-white/10 mb-6 space-y-3">
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-slate-400">Total Seating Capacity:</span>
                        <span class="font-bold text-white"><?= $cap_info['max_capacity'] ?> Seats</span>
                    </div>
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-slate-400">Confirmed Attendees:</span>
                        <span class="font-bold text-cyan-400"><?= $cap_info['confirmed'] ?></span>
                    </div>
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-slate-400">Remaining Slots:</span>
                        <?php if ($cap_info['is_full']): ?>
                            <span class="font-bold text-rose-400">0 (Waitlist Open)</span>
                        <?php else: ?>
                            <span class="font-bold text-emerald-400"><?= $cap_info['remaining'] ?> Left</span>
                        <?php endif; ?>
                    </div>

                    <!-- Visual Capacity Gauge -->
                    <div class="w-full h-2 bg-slate-800 rounded-full overflow-hidden">
                        <div class="h-full rounded-full transition-all duration-500 <?= $cap_info['percentage'] > 85 ? 'bg-rose-500' : 'bg-gradient-to-r from-emerald-400 via-cyan-400 to-purple-500' ?>" style="width: <?= $cap_info['percentage'] ?>%"></div>
                    </div>
                </div>

                <!-- Registration Action Logic -->
                <div class="space-y-3 mb-6">
                    <?php if ($is_past): ?>
                        <div class="bg-slate-800 p-3.5 rounded-xl text-center text-xs text-slate-300 font-semibold border border-white/10">
                            <i class="fa-solid fa-clock-rotate-left mr-1"></i> This event has concluded.
                        </div>
                    <?php elseif ($user_reg): ?>
                        <!-- User Already Registered -->
                        <div class="p-4 rounded-xl border <?php
                            if ($user_reg['status'] === 'confirmed') echo 'bg-emerald-950/40 border-emerald-500/40 text-emerald-300';
                            elseif ($user_reg['status'] === 'waitlisted') echo 'bg-amber-950/40 border-amber-500/40 text-amber-300';
                            elseif ($user_reg['status'] === 'attended') echo 'bg-cyan-950/40 border-cyan-500/40 text-cyan-300';
                            else echo 'bg-rose-950/40 border-rose-500/40 text-rose-300';
                        ?>">
                            <div class="font-bold text-sm mb-1 flex items-center justify-between">
                                <span>Status: <?= strtoupper($user_reg['status']) ?></span>
                                <i class="fa-solid fa-circle-check"></i>
                            </div>
                            <div class="text-xs opacity-80 mb-3">Pass Code: <?= htmlspecialchars($user_reg['registration_code']) ?></div>
                            
                            <a href="<?= BASE_URL ?>/student/my_events.php" class="btn-glass w-full py-2 text-xs text-center block">
                                <i class="fa-solid fa-qrcode mr-1"></i> View Entry QR Pass
                            </a>
                        </div>
                    <?php elseif (!$current_user): ?>
                        <!-- Visitor Mode -->
                        <button onclick="promptVisitorLogin('register for <?= htmlspecialchars(addslashes($event['title'])) ?>')" class="btn-neon-primary w-full py-3 text-sm font-bold shadow-lg shadow-purple-600/30">
                            <i class="fa-solid fa-ticket mr-1.5"></i> Register for Event
                        </button>
                        <p class="text-[11px] text-center text-slate-400">Sign in with your student ID to claim your seat.</p>
                    <?php elseif ($current_user['role'] === 'student'): ?>
                        <!-- Student Registration Button -->
                        <?php if ($cap_info['is_cutoff_passed']): ?>
                            <div class="bg-rose-950/40 border border-rose-500/40 p-3 rounded-xl text-center text-xs text-rose-300 font-semibold">
                                Registration deadline has passed.
                            </div>
                        <?php elseif ($cap_info['is_full']): ?>
                            <form action="<?= BASE_URL ?>/api/register_event.php" method="POST">
                                <input type="hidden" name="event_id" value="<?= $event['id'] ?>">
                                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                <button type="submit" class="btn-neon-cyan w-full py-3 text-sm font-bold bg-gradient-to-r from-amber-500 to-yellow-600 border-amber-400 shadow-lg shadow-amber-500/20">
                                    <i class="fa-solid fa-user-clock mr-1.5"></i> Join Event Waitlist
                                </button>
                            </form>
                            <p class="text-[11px] text-center text-amber-300 mt-1">Slots full! You will be auto-promoted if a seat opens.</p>
                        <?php else: ?>
                            <form action="<?= BASE_URL ?>/api/register_event.php" method="POST">
                                <input type="hidden" name="event_id" value="<?= $event['id'] ?>">
                                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                <button type="submit" class="btn-neon-primary w-full py-3 text-sm font-bold shadow-lg shadow-purple-600/30">
                                    <i class="fa-solid fa-ticket mr-1.5"></i> Confirm Registration
                                </button>
                            </form>
                            <p class="text-[11px] text-center text-emerald-400 mt-1"><i class="fa-solid fa-bolt mr-1"></i> Instant digital pass with QR verification</p>
                        <?php endif; ?>
                    <?php else: ?>
                        <!-- Organizer / Admin View -->
                        <a href="<?= BASE_URL ?>/organizer/manage_attendees.php?event_id=<?= $event['id'] ?>" class="btn-glass w-full py-2.5 text-xs text-center block">
                            <i class="fa-solid fa-users-gear mr-1.5 text-cyan-400"></i> Manage Attendees & Check-ins
                        </a>
                    <?php endif; ?>
                </div>

            <!-- Multi-Dimensional Peer Reviews & Feedback Section -->
            <div class="glass-panel p-6 sm:p-8 space-y-6">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-6 border-b border-white/10">
                    <div>
                        <div class="badge-neon badge-emerald mb-2">Participant Sentiment</div>
                        <h3 class="font-heading font-bold text-2xl text-white">Ratings & Feedback</h3>
                        <p class="text-slate-400 text-xs mt-0.5">Reviews submitted by verified attendees, volunteers, and student guests.</p>
                    </div>
                    <?php if ($user_reg && $user_reg['status'] === 'attended'): ?>
                        <a href="<?= BASE_URL ?>/student/feedback.php?event_id=<?= $event['id'] ?>" class="btn-neon-primary text-xs py-2.5 px-4">
                            <i class="fa-solid fa-star mr-1"></i> Write a Review
                        </a>
                    <?php elseif (!$current_user): ?>
                        <button onclick="promptVisitorLogin('submit feedback reviews')" class="btn-glass text-xs py-2 px-4">
                            <i class="fa-solid fa-star mr-1 text-amber-400"></i> Submit Review
                        </button>
                    <?php endif; ?>
                </div>

                <!-- Rating Analytics Breakdown -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 bg-slate-900/60 p-5 rounded-2xl border border-white/5">
                    <div class="text-center md:border-r md:border-white/10 flex flex-col justify-center">
                        <div class="font-heading font-extrabold text-5xl text-amber-400 mb-1"><?= $avg_overall ?></div>
                        <div class="flex items-center justify-center gap-1 text-amber-400 text-sm mb-1">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="fa-<?= $i <= round($avg_overall) ? 'solid' : 'regular' ?> fa-star"></i>
                            <?php endfor; ?>
                        </div>
                        <div class="text-xs text-slate-400 font-medium"><?= $review_count ?> Total Reviews</div>
                    </div>

                    <!-- Component Breakdown Bars -->
                    <div class="md:col-span-2 space-y-2.5 text-xs">
                        <div>
                            <div class="flex justify-between text-slate-300 mb-1">
                                <span>Venue & Acoustics</span>
                                <span class="font-bold text-cyan-400"><?= $avg_venue ?> / 5.0</span>
                            </div>
                            <div class="w-full h-1.5 bg-slate-800 rounded-full overflow-hidden">
                                <div class="h-full bg-cyan-400 rounded-full" style="width: <?= ($avg_venue / 5) * 100 ?>%"></div>
                            </div>
                        </div>
                        <div>
                            <div class="flex justify-between text-slate-300 mb-1">
                                <span>Event Coordination</span>
                                <span class="font-bold text-purple-400"><?= $avg_coord ?> / 5.0</span>
                            </div>
                            <div class="w-full h-1.5 bg-slate-800 rounded-full overflow-hidden">
                                <div class="h-full bg-purple-400 rounded-full" style="width: <?= ($avg_coord / 5) * 100 ?>%"></div>
                            </div>
                        </div>
                        <div>
                            <div class="flex justify-between text-slate-300 mb-1">
                                <span>Technical Arrangements</span>
                                <span class="font-bold text-emerald-400"><?= $avg_tech ?> / 5.0</span>
                            </div>
                            <div class="w-full h-1.5 bg-slate-800 rounded-full overflow-hidden">
                                <div class="h-full bg-emerald-400 rounded-full" style="width: <?= ($avg_tech / 5) * 100 ?>%"></div>
                            </div>
                        </div>
                        <div>
                            <div class="flex justify-between text-slate-300 mb-1">
                                <span>Hospitality & Refreshments</span>
                                <span class="font-bold text-amber-400"><?= $avg_hosp ?> / 5.0</span>
                            </div>
                            <div class="w-full h-1.5 bg-slate-800 rounded-full overflow-hidden">
                                <div class="h-full bg-amber-400 rounded-full" style="width: <?= ($avg_hosp / 5) * 100 ?>%"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Reviews Feed -->
                <div class="space-y-4 pt-2">
                    <?php if (empty($reviews)): ?>
                        <div class="py-8 text-center text-slate-400 text-xs">
                            No reviews submitted yet. Attend this event to share your ratings!
                        </div>
                    <?php else: ?>
                        <?php foreach ($reviews as $rev): ?>
                            <div class="p-4 rounded-xl bg-slate-900/40 border border-white/5 space-y-2">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2.5">
                                        <img src="<?= htmlspecialchars($rev['reviewer_avatar'] ?? 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=80&q=80') ?>" class="w-7 h-7 rounded-full object-cover">
                                        <span class="font-bold text-xs text-white"><?= htmlspecialchars($rev['reviewer_name']) ?></span>
                                        <span class="badge-neon badge-purple text-[10px]"><?= htmlspecialchars($rev['user_type']) ?></span>
                                    </div>
                                    <span class="text-[11px] text-slate-500"><?= time_ago($rev['created_at']) ?></span>
                                </div>
                                <div class="flex items-center gap-1 text-amber-400 text-xs">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="fa-<?= $i <= $rev['overall_rating'] ? 'solid' : 'regular' ?> fa-star"></i>
                                    <?php endfor; ?>
                                </div>
                                <p class="text-xs text-slate-300 leading-relaxed"><?= htmlspecialchars($rev['comments']) ?></p>
                                <?php if (!empty($rev['suggestions'])): ?>
                                    <div class="text-[11px] text-cyan-300/80 bg-cyan-950/20 p-2 rounded-lg border border-cyan-500/20">
                                        <span class="font-semibold">Suggestion:</span> <?= htmlspecialchars($rev['suggestions']) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Right Column: Registration Box, Live Capacity Gauge & Calendar Sync (1 col) -->
        <div class="space-y-6">
                        <!-- Organizer Profile Card -->
            <div class="glass-panel sticky top-24 p-6 flex flex-col items-start gap-4">
                <div class="flex items-center gap-4">
                    <img src="<?= htmlspecialchars($event['organizer_avatar'] ?? 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&w=150&q=80') ?>" class="w-14 h-14 rounded-2xl object-cover border border-purple-500/40 shadow-lg shadow-purple-500/20">
                    <div>
                        <div class="text-[11px] uppercase tracking-wider font-bold text-purple-400">Organizing Convener</div>
                        <div class="font-heading font-bold text-lg text-white"><?= htmlspecialchars($event['organizer_name']) ?></div>
                        <div class="text-xs text-slate-400"><?= htmlspecialchars($event['organizer_dept']) ?> � <?= htmlspecialchars($event['organizer_email']) ?></div>
                    </div>
                </div>
                <a href="mailto:<?= htmlspecialchars($event['organizer_email']) ?>" class="btn-glass text-xs py-2 px-3 w-full text-center mt-2">
                    <i class="fa-solid fa-envelope mr-1 text-cyan-400"></i> Contact Organizer
                </a>
            </div>


                <!-- Calendar Sync Integration (Google, Outlook, Apple .ics) -->
                <div class="pt-4 border-t border-white/10 space-y-2.5">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-400">Sync to Calendar</label>
                    <div class="grid grid-cols-3 gap-2">
                        <button onclick="addToGoogleCalendar('<?= htmlspecialchars(addslashes($event['title'])) ?>', '<?= $start_iso ?>', '<?= $end_iso ?>', '<?= htmlspecialchars(addslashes($venue_full)) ?>', '<?= htmlspecialchars(addslashes(substr($event['description'], 0, 100))) ?>')" class="btn-glass py-2 px-1 text-[11px] flex items-center justify-center gap-1 hover:text-cyan-400" title="Add to Google Calendar">
                            <i class="fa-brands fa-google text-cyan-400"></i> Google
                        </button>
                        <button onclick="addToOutlookCalendar('<?= htmlspecialchars(addslashes($event['title'])) ?>', '<?= $start_iso ?>', '<?= $end_iso ?>', '<?= htmlspecialchars(addslashes($venue_full)) ?>', '<?= htmlspecialchars(addslashes(substr($event['description'], 0, 100))) ?>')" class="btn-glass py-2 px-1 text-[11px] flex items-center justify-center gap-1 hover:text-blue-400" title="Add to Outlook Calendar">
                            <i class="fa-brands fa-microsoft text-blue-400"></i> Outlook
                        </button>
                        <button onclick="downloadIcsCalendar(<?= $event['id'] ?>)" class="btn-glass py-2 px-1 text-[11px] flex items-center justify-center gap-1 hover:text-emerald-400" title="Download Apple/iCal .ICS">
                            <i class="fa-brands fa-apple text-emerald-400"></i> .ICS
                        </button>
                    </div>
                </div>

                <!-- Social Share Trigger -->
                <div class="mt-4 pt-4 border-t border-white/10 text-center">
                    <button onclick="openShareModal('<?= htmlspecialchars(addslashes($event['title'])) ?>', '<?= BASE_URL ?>/event_detail.php?id=<?= $event['id'] ?>', '<?= htmlspecialchars(addslashes(substr($event['description'], 0, 100))) ?>')" class="text-xs text-slate-400 hover:text-white transition flex items-center justify-center gap-1.5 mx-auto">
                        <i class="fa-solid fa-share-nodes text-cyan-400"></i> Share this event with classmates
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
