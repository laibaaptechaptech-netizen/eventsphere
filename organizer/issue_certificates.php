<?php
// organizer/issue_certificates.php - Issue & Approve Official E-Certificates for Attended Students
$page_title = "Issue Event Certificates";
require_once __DIR__ . '/../config/auth_check.php';
require_organizer();

$user = current_user();
$db = getDB();

// Handle Issue Action
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reg_id = (int)($_POST['reg_id'] ?? 0);
    $cert_code = 'CERT-ESP-' . date('Y') . '-' . strtoupper(substr(md5(uniqid()), 0, 8));

    $stmt = $db->prepare("
        UPDATE registrations 
        SET certificate_issued = 1, certificate_code = ?, certificate_fee_paid = 1 
        WHERE id = ? AND status = 'attended'
    ");
    $stmt->execute([$cert_code, $reg_id]);

    // Fetch user id to notify
    $u_stmt = $db->prepare("SELECT r.user_id, e.title FROM registrations r JOIN events e ON r.event_id = e.id WHERE r.id = ?");
    $u_stmt->execute([$reg_id]);
    $item = $u_stmt->fetch();

    if ($item) {
        create_notification(
            $item['user_id'],
            '🎓 Official Certificate Issued!',
            'Your e-certificate for ' . $item['title'] . ' has been approved and issued by the organizer.',
            BASE_URL . '/student/certificates.php',
            'success'
        );
    }

    set_flash('success', 'Official certificate generated and issued to student with Code: ' . $cert_code);
    header("Location: " . BASE_URL . "/organizer/issue_certificates.php");
    exit;
}

// Fetch Attended Participants for Organizer's Events
$stmt = $db->prepare("
    SELECT r.*, e.title as event_title, e.event_date, u.name as student_name, u.email as student_email, u.enrolment_no, u.department as student_dept
    FROM registrations r
    JOIN events e ON r.event_id = e.id
    JOIN users u ON r.user_id = u.id
    WHERE (e.organizer_id = ? OR ? = 'admin') AND r.status = 'attended'
    ORDER BY r.checked_in_at DESC
");
$stmt->execute([$user['id'], $user['role']]);
$attendees = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <div class="mb-8">
        <div class="badge-neon badge-amber mb-2">Certification Operations</div>
        <h1 class="font-heading font-extrabold text-3xl text-white">Issue Official E-Certificates</h1>
        <p class="text-slate-400 text-xs mt-1">Generate tamper-proof verifiable certificates for students who attended your events.</p>
    </div>

    <div class="glass-panel-elevated p-6 border border-white/10">
        <?php if (empty($attendees)): ?>
            <div class="py-12 text-center text-slate-400 text-xs">
                No attended students found. Use the Live QR Scanner to check in attendees first.
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="text-slate-400 border-b border-white/10 uppercase tracking-wider text-[10px]">
                            <th class="py-3 px-4">Student Name</th>
                            <th class="py-3 px-4">Event Title</th>
                            <th class="py-3 px-4">Enrolment & Dept</th>
                            <th class="py-3 px-4">Status</th>
                            <th class="py-3 px-4">Certificate ID</th>
                            <th class="py-3 px-4 text-right">Issuance Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5 text-slate-300">
                        <?php foreach ($attendees as $att): ?>
                            <tr class="hover:bg-white/5 transition">
                                <td class="py-3.5 px-4 font-bold text-white"><?= htmlspecialchars($att['student_name']) ?></td>
                                <td class="py-3.5 px-4 text-cyan-300 line-clamp-1"><?= htmlspecialchars($att['event_title']) ?></td>
                                <td class="py-3.5 px-4">
                                    <div class="font-mono text-purple-300"><?= htmlspecialchars($att['enrolment_no'] ?? 'N/A') ?></div>
                                    <div class="text-[10px] text-slate-400"><?= htmlspecialchars($att['student_dept']) ?></div>
                                </td>
                                <td class="py-3.5 px-4">
                                    <span class="badge-neon <?= $att['certificate_issued'] ? 'badge-amber' : 'badge-cyan' ?> text-[10px]">
                                        <?= $att['certificate_issued'] ? 'ISSUED' : 'READY TO ISSUE' ?>
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 font-mono text-[11px] text-amber-400">
                                    <?= htmlspecialchars($att['certificate_code'] ?? 'Pending') ?>
                                </td>
                                <td class="py-3.5 px-4 text-right">
                                    <?php if (!$att['certificate_issued']): ?>
                                        <form method="POST" action="<?= BASE_URL ?>/organizer/issue_certificates.php" class="inline">
                                            <input type="hidden" name="reg_id" value="<?= $att['id'] ?>">
                                            <button type="submit" class="btn-neon-primary py-1.5 px-3 text-[11px] bg-gradient-to-r from-amber-500 to-yellow-600 border-amber-400 font-bold">
                                                <i class="fa-solid fa-award mr-1"></i> Issue Certificate
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-xs text-emerald-400 font-semibold"><i class="fa-solid fa-circle-check mr-1"></i> Active</span>
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
