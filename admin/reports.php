<?php
// admin/reports.php - Comprehensive Analytics Reports Generator (Participation, Feedback, Growth, Certificates)
$page_title = "Analytics & Reports Generator";
require_once __DIR__ . '/../config/auth_check.php';
require_admin();

$user = current_user();
$db = getDB();

// 1. Participation & Attendance Report Data
$stmt = $db->query("
    SELECT e.id, e.title, e.event_date, e.department, e.max_capacity,
           COUNT(r.id) as total_registered,
           COUNT(CASE WHEN r.status = 'attended' THEN 1 END) as total_attended,
           COUNT(CASE WHEN r.status = 'waitlisted' THEN 1 END) as total_waitlisted,
           COUNT(CASE WHEN r.status = 'cancelled' THEN 1 END) as total_cancelled
    FROM events e
    LEFT JOIN registrations r ON e.id = r.event_id
    GROUP BY e.id
    ORDER BY e.event_date DESC
");
$participation_report = $stmt->fetchAll();

// 2. Feedback Trends Report Data
$stmt2 = $db->query("
    SELECT e.title, COUNT(f.id) as review_count,
           ROUND(AVG(f.overall_rating), 1) as avg_overall,
           ROUND(AVG(f.rating_venue), 1) as avg_venue,
           ROUND(AVG(f.rating_coordination), 1) as avg_coord,
           ROUND(AVG(f.rating_technical), 1) as avg_tech,
           ROUND(AVG(f.rating_hospitality), 1) as avg_hosp
    FROM events e
    LEFT JOIN feedback_reviews f ON e.id = f.event_id
    GROUP BY e.id
    HAVING review_count > 0
    ORDER BY avg_overall DESC
");
$feedback_report = $stmt2->fetchAll();

// 3. Certificate Issuance Report
$stmt3 = $db->query("
    SELECT r.certificate_code, r.certificate_fee_txn, r.created_at,
           u.name as student_name, u.enrolment_no, u.department,
           e.title as event_title, e.certificate_fee
    FROM registrations r
    JOIN users u ON r.user_id = u.id
    JOIN events e ON r.event_id = e.id
    WHERE r.certificate_issued = 1
    ORDER BY r.created_at DESC
");
$cert_report = $stmt3->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="badge-neon badge-indigo mb-2">Executive Reporting</div>
            <h1 class="font-heading font-extrabold text-3xl text-white">Platform Analytics & Reports</h1>
            <p class="text-slate-400 text-xs mt-1">Export high-fidelity participation logs, attendee metrics, and certificate verification records.</p>
        </div>
        <div class="flex items-center gap-3">
            <button onclick="window.print()" class="btn-glass text-xs py-2 px-4">
                <i class="fa-solid fa-print mr-1.5 text-cyan-400"></i> Print / Export PDF
            </button>
            <a href="<?= BASE_URL ?>/api/export_report.php?type=general" class="btn-neon-primary text-xs py-2 px-4 shadow-lg shadow-purple-600/30">
                <i class="fa-solid fa-file-csv mr-1.5"></i> Export CSV Dataset
            </a>
        </div>
    </div>

    <!-- Report 1: Participation & Gate Check-in Summary -->
    <div class="glass-panel-elevated p-6 mb-10 border border-white/10">
        <div class="flex items-center justify-between pb-4 border-b border-white/10 mb-4">
            <h3 class="font-heading font-bold text-lg text-white flex items-center gap-2">
                <i class="fa-solid fa-chart-line text-cyan-400"></i> Event Participation & Attendance Turnout
            </h3>
            <span class="text-xs text-slate-400"><?= count($participation_report) ?> Events Audited</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="text-slate-400 border-b border-white/10 uppercase tracking-wider text-[10px]">
                        <th class="py-3 px-4">Event Title</th>
                        <th class="py-3 px-4">Date & Dept</th>
                        <th class="py-3 px-4">Cap</th>
                        <th class="py-3 px-4">Registered</th>
                        <th class="py-3 px-4">Attended (Gate)</th>
                        <th class="py-3 px-4">Waitlist</th>
                        <th class="py-3 px-4">Turnout %</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5 text-slate-300">
                    <?php foreach ($participation_report as $pr): 
                        $reg = (int)$pr['total_registered'];
                        $att = (int)$pr['total_attended'];
                        $turnout = $reg > 0 ? round(($att / $reg) * 100) : 0;
                    ?>
                        <tr class="hover:bg-white/5 transition">
                            <td class="py-3.5 px-4 font-bold text-white"><?= htmlspecialchars($pr['title']) ?></td>
                            <td class="py-3.5 px-4">
                                <div><?= format_event_date($pr['event_date']) ?></div>
                                <div class="text-[10px] text-slate-400"><?= htmlspecialchars($pr['department']) ?></div>
                            </td>
                            <td class="py-3.5 px-4 font-mono"><?= $pr['max_capacity'] ?></td>
                            <td class="py-3.5 px-4 font-bold text-cyan-300"><?= $pr['total_registered'] ?></td>
                            <td class="py-3.5 px-4 font-bold text-emerald-300"><?= $pr['total_attended'] ?></td>
                            <td class="py-3.5 px-4 text-amber-400"><?= $pr['total_waitlisted'] ?></td>
                            <td class="py-3.5 px-4">
                                <span class="badge-neon <?= $turnout >= 70 ? 'badge-emerald' : 'badge-amber' ?> text-[10px]">
                                    <?= $turnout ?>%
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Report 2: Feedback & Sentiment Analysis Trends -->
    <div class="glass-panel-elevated p-6 mb-10 border border-white/10">
        <div class="flex items-center justify-between pb-4 border-b border-white/10 mb-4">
            <h3 class="font-heading font-bold text-lg text-white flex items-center gap-2">
                <i class="fa-solid fa-star text-amber-400"></i> Event Feedback & Component Sentiment Trends
            </h3>
            <span class="text-xs text-slate-400"><?= count($feedback_report) ?> Reviewed Events</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="text-slate-400 border-b border-white/10 uppercase tracking-wider text-[10px]">
                        <th class="py-3 px-4">Event</th>
                        <th class="py-3 px-4">Reviews Count</th>
                        <th class="py-3 px-4">Overall Score</th>
                        <th class="py-3 px-4">Venue Rating</th>
                        <th class="py-3 px-4">Coordination</th>
                        <th class="py-3 px-4">Technical Arrangements</th>
                        <th class="py-3 px-4">Hospitality</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5 text-slate-300">
                    <?php foreach ($feedback_report as $fr): ?>
                        <tr class="hover:bg-white/5 transition">
                            <td class="py-3.5 px-4 font-bold text-white"><?= htmlspecialchars($fr['title']) ?></td>
                            <td class="py-3.5 px-4 text-cyan-300 font-mono"><?= $fr['review_count'] ?></td>
                            <td class="py-3.5 px-4 font-bold text-amber-400"><?= $fr['avg_overall'] ?> / 5.0</td>
                            <td class="py-3.5 px-4 text-slate-300"><?= $fr['avg_venue'] ?> ★</td>
                            <td class="py-3.5 px-4 text-slate-300"><?= $fr['avg_coord'] ?> ★</td>
                            <td class="py-3.5 px-4 text-slate-300"><?= $fr['avg_tech'] ?> ★</td>
                            <td class="py-3.5 px-4 text-slate-300"><?= $fr['avg_hosp'] ?> ★</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Report 3: Official Certificate Issuance Ledger -->
    <div class="glass-panel-elevated p-6 border border-white/10">
        <div class="flex items-center justify-between pb-4 border-b border-white/10 mb-4">
            <h3 class="font-heading font-bold text-lg text-white flex items-center gap-2">
                <i class="fa-solid fa-award text-amber-400"></i> Verifiable Certificate Issuance Registry
            </h3>
            <span class="text-xs text-amber-400 font-semibold"><?= count($cert_report) ?> Verified Certificates</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="text-slate-400 border-b border-white/10 uppercase tracking-wider text-[10px]">
                        <th class="py-3 px-4">Certificate ID</th>
                        <th class="py-3 px-4">Student Recipient</th>
                        <th class="py-3 px-4">Department & Enrolment</th>
                        <th class="py-3 px-4">Event</th>
                        <th class="py-3 px-4">Simulated Txn ID</th>
                        <th class="py-3 px-4">Issued Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5 text-slate-300">
                    <?php foreach ($cert_report as $cr): ?>
                        <tr class="hover:bg-white/5 transition">
                            <td class="py-3.5 px-4 font-mono font-bold text-amber-400"><?= htmlspecialchars($cr['certificate_code']) ?></td>
                            <td class="py-3.5 px-4 font-bold text-white"><?= htmlspecialchars($cr['student_name']) ?></td>
                            <td class="py-3.5 px-4">
                                <div><?= htmlspecialchars($cr['department']) ?></div>
                                <div class="text-[10px] text-cyan-400 font-mono"><?= htmlspecialchars($cr['enrolment_no']) ?></div>
                            </td>
                            <td class="py-3.5 px-4 text-purple-300"><?= htmlspecialchars($cr['event_title']) ?></td>
                            <td class="py-3.5 px-4 font-mono text-[11px] text-slate-400"><?= htmlspecialchars($cr['certificate_fee_txn'] ?? 'N/A') ?></td>
                            <td class="py-3.5 px-4 text-slate-400 text-[11px]"><?= date('M d, Y', strtotime($cr['created_at'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
