<?php
// admin/manage_events.php - Event Proposals Approval Workflow (Approve / Reject / Changes)
$page_title = "Event Approvals & Moderation";
require_once __DIR__ . '/../config/auth_check.php';
require_admin();

$user = current_user();
$db = getDB();

// Handle Approval / Rejection Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = clean_input($_POST['action'] ?? '');
    $event_id = (int)($_POST['event_id'] ?? 0);
    $admin_notes = clean_input($_POST['admin_notes'] ?? '');

    $e_stmt = $db->prepare("SELECT organizer_id, title FROM events WHERE id = ?");
    $e_stmt->execute([$event_id]);
    $event_data = $e_stmt->fetch();

    if ($event_data) {
        if ($action === 'approve') {
            $stmt = $db->prepare("UPDATE events SET status = 'approved', admin_notes = ? WHERE id = ?");
            $stmt->execute([$admin_notes, $event_id]);

            create_notification(
                $event_data['organizer_id'],
                '🎉 Event Approved & Live!',
                'Your proposal for "' . $event_data['title'] . '" has been APPROVED by the administrator and is now live.',
                BASE_URL . '/event_detail.php?id=' . $event_id,
                'success'
            );
            set_flash('success', 'Event approved! It is now published live for student registrations.');
        } elseif ($action === 'reject') {
            $stmt = $db->prepare("UPDATE events SET status = 'rejected', admin_notes = ? WHERE id = ?");
            $stmt->execute([$admin_notes, $event_id]);

            create_notification(
                $event_data['organizer_id'],
                '❌ Event Proposal Rejected',
                'Your proposal for "' . $event_data['title'] . '" was not approved. Note: ' . $admin_notes,
                BASE_URL . '/organizer/dashboard.php',
                'alert'
            );
            set_flash('warning', 'Event rejected and archived.');
        } elseif ($action === 'feature_toggle') {
            $stmt = $db->prepare("UPDATE events SET featured = NOT featured WHERE id = ?");
            $stmt->execute([$event_id]);
            set_flash('info', 'Featured showcase status toggled.');
        }
    }
    header("Location: " . BASE_URL . "/admin/manage_events.php");
    exit;
}

// Fetch All Events by Status
$status_filter = clean_input($_GET['status'] ?? 'all');
$sql = "
    SELECT e.*, u.name as organizer_name, u.email as organizer_email, c.name as category_name, c.badge_color, v.name as venue_name
    FROM events e
    JOIN users u ON e.organizer_id = u.id
    JOIN categories c ON e.category_id = c.id
    LEFT JOIN venues v ON e.venue_id = v.id
";

if ($status_filter !== 'all') {
    $sql .= " WHERE e.status = ?";
    $stmt = $db->prepare($sql . " ORDER BY e.created_at DESC");
    $stmt->execute([$status_filter]);
} else {
    $stmt = $db->query($sql . " ORDER BY (e.status = 'pending') DESC, e.created_at DESC");
}
$all_events = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="badge-neon badge-purple mb-2">Event Moderation</div>
            <h1 class="font-heading font-extrabold text-3xl text-white">Event Approval Workflow</h1>
            <p class="text-slate-400 text-xs mt-1">Review event proposals submitted by organizers, verify venue limits, and publish them live.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="<?= BASE_URL ?>/admin/manage_events.php?status=pending" class="px-3 py-1.5 rounded-lg text-xs font-semibold <?= $status_filter === 'pending' ? 'bg-amber-500 text-black font-bold' : 'bg-slate-800 text-amber-300' ?>">
                Pending Proposals
            </a>
            <a href="<?= BASE_URL ?>/admin/manage_events.php?status=approved" class="px-3 py-1.5 rounded-lg text-xs font-semibold <?= $status_filter === 'approved' ? 'bg-emerald-600 text-white' : 'bg-slate-800 text-emerald-300' ?>">
                Approved
            </a>
            <a href="<?= BASE_URL ?>/admin/manage_events.php?status=all" class="px-3 py-1.5 rounded-lg text-xs font-semibold <?= $status_filter === 'all' ? 'bg-purple-600 text-white' : 'bg-slate-800 text-slate-300' ?>">
                All Events
            </a>
        </div>
    </div>

    <!-- Events Moderation Table -->
    <div class="glass-panel-elevated p-6 border border-white/10">
        <?php if (empty($all_events)): ?>
            <div class="py-12 text-center text-slate-400 text-xs">
                No events found in this filter category.
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="text-slate-400 border-b border-white/10 uppercase tracking-wider text-[10px]">
                            <th class="py-3 px-4">Event Details</th>
                            <th class="py-3 px-4">Organizer</th>
                            <th class="py-3 px-4">Date & Venue</th>
                            <th class="py-3 px-4">Capacity</th>
                            <th class="py-3 px-4">Status</th>
                            <th class="py-3 px-4 text-right">Approval Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5 text-slate-300">
                        <?php foreach ($all_events as $ev): ?>
                            <tr class="hover:bg-white/5 transition">
                                <td class="py-3.5 px-4 max-w-xs">
                                    <div class="flex items-center gap-2 mb-1">
                                        <span class="badge-neon badge-<?= $ev['badge_color'] ?? 'cyan' ?> text-[10px]">
                                            <?= htmlspecialchars($ev['category_name']) ?>
                                        </span>
                                        <?php if ($ev['featured']): ?>
                                            <span class="badge-neon badge-amber text-[10px]"><i class="fa-solid fa-star"></i> Featured</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="font-bold text-white text-sm line-clamp-1"><?= htmlspecialchars($ev['title']) ?></div>
                                    <div class="text-[11px] text-slate-400 line-clamp-1"><?= htmlspecialchars($ev['description']) ?></div>
                                </td>
                                <td class="py-3.5 px-4">
                                    <div class="font-bold text-white"><?= htmlspecialchars($ev['organizer_name']) ?></div>
                                    <div class="text-[10px] text-cyan-400"><?= htmlspecialchars($ev['department']) ?></div>
                                </td>
                                <td class="py-3.5 px-4">
                                    <div><?= format_event_date($ev['event_date']) ?></div>
                                    <div class="text-[10px] text-slate-400"><?= htmlspecialchars($ev['venue_name'] ?? 'Custom Venue') ?></div>
                                </td>
                                <td class="py-3.5 px-4">
                                    <div class="font-bold text-cyan-300"><?= $ev['max_capacity'] ?> Seats</div>
                                    <div class="text-[10px] text-slate-400">Fee: $<?= $ev['fee_amount'] ?></div>
                                </td>
                                <td class="py-3.5 px-4">
                                    <span class="badge-neon badge-<?= $ev['status'] === 'approved' ? 'emerald' : ($ev['status'] === 'pending' ? 'amber' : ($ev['status'] === 'completed' ? 'purple' : 'rose')) ?> text-[10px]">
                                        <?= strtoupper($ev['status']) ?>
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-right space-x-1.5 whitespace-nowrap">
                                    <?php if ($ev['status'] === 'pending'): ?>
                                        <form method="POST" action="<?= BASE_URL ?>/admin/manage_events.php" class="inline">
                                            <input type="hidden" name="action" value="approve">
                                            <input type="hidden" name="event_id" value="<?= $ev['id'] ?>">
                                            <button type="submit" class="btn-neon-primary py-1 px-3 text-[11px] font-bold" title="Approve & Publish">
                                                <i class="fa-solid fa-check mr-1"></i> Approve
                                            </button>
                                        </form>
                                        <form method="POST" action="<?= BASE_URL ?>/admin/manage_events.php" class="inline">
                                            <input type="hidden" name="action" value="reject">
                                            <input type="hidden" name="event_id" value="<?= $ev['id'] ?>">
                                            <input type="hidden" name="admin_notes" value="Proposal does not satisfy compliance standards.">
                                            <button type="submit" class="btn-glass py-1 px-2.5 text-[11px] text-rose-400 hover:bg-rose-950/40" title="Reject Proposal">
                                                <i class="fa-solid fa-xmark mr-1"></i> Reject
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <form method="POST" action="<?= BASE_URL ?>/admin/manage_events.php" class="inline">
                                            <input type="hidden" name="action" value="feature_toggle">
                                            <input type="hidden" name="event_id" value="<?= $ev['id'] ?>">
                                            <button type="submit" class="btn-glass py-1 px-2.5 text-[10px] <?= $ev['featured'] ? 'text-amber-400' : 'text-slate-400' ?>" title="Toggle Featured Showcase">
                                                <i class="fa-solid fa-star"></i>
                                            </button>
                                        </form>
                                        <a href="<?= BASE_URL ?>/event_detail.php?id=<?= $ev['id'] ?>" class="btn-glass py-1 px-2.5 text-[10px]" title="View Live Page">
                                            <i class="fa-solid fa-arrow-up-right-from-square text-cyan-400"></i>
                                        </a>
                                    <?php endif; ?>
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
