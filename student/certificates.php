<?php
// student/certificates.php - Verifiable Digital E-Certificate Hub & Fee Payment Modal (SRS Item 9)
$page_title = "My E-Certificates";
require_once __DIR__ . '/../config/auth_check.php';
require_student();

$user = current_user();
$db = getDB();

// Handle Simulated Certificate Fee Submission (SRS Item 9)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_fee') {
    $reg_id = (int)($_POST['registration_id'] ?? 0);
    $txn_id = clean_input($_POST['txn_id'] ?? ('TXN_' . strtoupper(bin2hex(random_bytes(6)))));
    
    // Strict participation check: user must have 'attended' status
    $check = $db->prepare("SELECT r.*, e.title FROM registrations r JOIN events e ON r.event_id = e.id WHERE r.id = ? AND r.user_id = ? AND r.status = 'attended'");
    $check->execute([$reg_id, $user['id']]);
    $reg_item = $check->fetch();

    if ($reg_item) {
        $cert_code = 'CERT-ESP-' . date('Y') . '-' . strtoupper(substr(md5(uniqid()), 0, 8));
        $stmt = $db->prepare("
            UPDATE registrations 
            SET certificate_fee_paid = 1, certificate_fee_txn = ?, certificate_issued = 1, certificate_code = ?
            WHERE id = ?
        ");
        $stmt->execute([$txn_id, $cert_code, $reg_id]);

        create_notification(
            $user['id'],
            '🎓 Certificate Ready for Download!',
            'Your certificate for ' . $reg_item['title'] . ' has been generated with Verification Code: ' . $cert_code,
            BASE_URL . '/student/certificates.php',
            'success'
        );

        set_flash('success', 'Certificate fee details recorded! Your verifiable e-certificate is now unlocked.');
        header("Location: " . BASE_URL . "/student/certificates.php");
        exit;
    } else {
        set_flash('error', 'E-certificates are only available for events you have participated in and attended.');
        header("Location: " . BASE_URL . "/student/certificates.php");
        exit;
    }
}

// Fetch Strictly Attended Events Only (Participation Mandatory)
$stmt = $db->prepare("
    SELECT r.*, e.title as event_title, e.event_date, e.certificate_fee,
           v.name as venue_name, c.name as category_name
    FROM registrations r
    JOIN events e ON r.event_id = e.id
    JOIN categories c ON e.category_id = c.id
    LEFT JOIN venues v ON e.venue_id = v.id
    WHERE r.user_id = ? AND r.status = 'attended'
    ORDER BY e.event_date DESC
");
$stmt->execute([$user['id']]);
$certificates = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <!-- Header -->
    <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="badge-neon badge-amber mb-2">Verified Credentials</div>
            <h1 class="font-heading font-extrabold text-3xl text-white">E-Certificate Center</h1>
            <p class="text-slate-400 text-xs mt-1">Official tamper-proof certificates of participation verified by EventSphere Registry.</p>
        </div>
        <div class="text-xs text-amber-400 bg-amber-950/40 px-4 py-2 rounded-xl border border-amber-500/30">
            <i class="fa-solid fa-circle-check mr-1.5"></i> <?= count($certificates) ?> Certificates Issued / Available
        </div>
    </div>

    <!-- Certificate List & Interactive Canvas Preview -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Left: Certificate Items (1 col) -->
        <div class="space-y-4">
            <h3 class="font-heading font-bold text-lg text-white">Eligible Event Credentials</h3>

            <?php if (empty($certificates)): ?>
                <div class="glass-panel p-8 text-center">
                    <div class="w-12 h-12 rounded-xl bg-slate-800 border border-white/10 flex items-center justify-center mx-auto mb-3 text-slate-400 text-lg">
                        <i class="fa-solid fa-award"></i>
                    </div>
                    <h4 class="font-bold text-white text-sm mb-1">No Certificates Yet</h4>
                    <p class="text-slate-400 text-xs">Attend upcoming campus events and get your check-in scanned by organizers to unlock certificates.</p>
                </div>
            <?php else: ?>
                <?php foreach ($certificates as $idx => $cert): 
                    $is_unlocked = ($cert['certificate_issued'] == 1 && $cert['certificate_fee_paid'] == 1);
                ?>
                    <div class="glass-card-interactive p-5 rounded-2xl border <?= $is_unlocked ? 'border-amber-500/40 bg-amber-950/10' : 'border-white/10' ?> space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="badge-neon <?= $is_unlocked ? 'badge-amber' : 'badge-cyan' ?> text-[10px]">
                                <?= $is_unlocked ? '★ VERIFIED & READY' : 'ATTENDANCE CONFIRMED' ?>
                            </span>
                            <?php if ($is_unlocked): ?>
                                <span class="text-[10px] text-amber-400 font-mono font-bold">
                                    <?= htmlspecialchars($cert['certificate_code']) ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <h4 class="font-heading font-bold text-base text-white line-clamp-1">
                            <?= htmlspecialchars($cert['event_title']) ?>
                        </h4>

                        <div class="text-xs text-slate-400 space-y-1">
                            <div><i class="fa-regular fa-calendar text-cyan-400 mr-1.5"></i> <?= format_event_date($cert['event_date']) ?></div>
                            <div><i class="fa-solid fa-location-dot text-rose-400 mr-1.5"></i> <?= htmlspecialchars($cert['venue_name'] ?? 'Main Campus') ?></div>
                        </div>

                        <div class="pt-3 border-t border-white/5 flex items-center gap-2">
                            <?php if ($is_unlocked): ?>
                                <button onclick="renderCertificateToCanvas(<?= htmlspecialchars(json_encode([
                                    'studentName' => $user['name'],
                                    'enrolmentNo' => $user['enrolment_no'],
                                    'department' => $user['department'],
                                    'eventTitle' => $cert['event_title'],
                                    'eventDate' => format_event_date($cert['event_date']),
                                    'venue' => $cert['venue_name'] ?? 'Main Auditorium',
                                    'certCode' => $cert['certificate_code']
                                ])) ?>)" class="btn-neon-primary w-full py-2 text-xs bg-gradient-to-r from-amber-500 to-yellow-600 border-amber-400 text-white font-bold">
                                    <i class="fa-solid fa-eye mr-1.5"></i> Preview & Download
                                </button>
                            <?php else: ?>
                                <button onclick="openFeeModal(<?= $cert['id'] ?>, '<?= htmlspecialchars(addslashes($cert['event_title'])) ?>', '<?= $cert['certificate_fee'] ?>')" class="btn-neon-cyan w-full py-2 text-xs">
                                    <i class="fa-solid fa-receipt mr-1.5"></i> Submit Fee Details ($<?= $cert['certificate_fee'] ?>)
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Right: Live Canvas Certificate Renderer (2 cols) -->
        <div class="lg:col-span-2 space-y-4">
            <?php 
                $has_unlocked = false;
                foreach ($certificates as $c) {
                    if ($c['certificate_issued'] == 1) {
                        $has_unlocked = true;
                        break;
                    }
                }
            ?>
            <?php if (!empty($certificates) && $has_unlocked): ?>
                <div class="flex items-center justify-between">
                    <h3 class="font-heading font-bold text-lg text-white">Digital Certificate Preview</h3>
                    <div class="flex items-center gap-2" id="certActionButtons">
                        <button onclick="downloadCertificateImage('certCanvas', '<?= preg_replace('/[^a-zA-Z0-9]/', '_', $user['name']) ?>_Certificate.png')" class="btn-neon-primary text-xs py-2 px-4 shadow-lg shadow-purple-600/30">
                            <i class="fa-solid fa-download mr-1.5"></i> Download High-Res PNG
                        </button>
                        <button onclick="printCertificate('certCanvas')" class="btn-glass text-xs py-2 px-3">
                            <i class="fa-solid fa-print mr-1"></i> Print / PDF
                        </button>
                    </div>
                </div>

                <!-- Canvas Container -->
                <div class="glass-panel p-3 border border-white/10 rounded-2xl overflow-hidden flex items-center justify-center bg-black/40">
                    <canvas id="certCanvas" class="w-full h-auto rounded-xl shadow-2xl border border-amber-500/30 max-w-full"></canvas>
                </div>
            <?php else: ?>
                <div class="glass-panel p-12 text-center border border-white/10 rounded-2xl flex flex-col items-center justify-center min-h-[380px] space-y-4">
                    <div class="w-16 h-16 rounded-2xl bg-amber-500/10 border border-amber-500/30 text-amber-400 flex items-center justify-center text-3xl shadow-xl shadow-amber-500/10">
                        <i class="fa-solid fa-award"></i>
                    </div>
                    <div class="max-w-md space-y-2">
                        <h3 class="font-heading font-bold text-xl text-white">No Participated Event Certificates</h3>
                        <p class="text-slate-400 text-xs leading-relaxed">
                            Official e-certificates are exclusively generated for events where your attendance has been verified via the on-site QR scanner. Register for upcoming events and participate to receive your verified digital credentials.
                        </p>
                    </div>
                    <a href="<?= BASE_URL ?>/events.php" class="btn-neon-primary text-xs py-2.5 px-6 font-bold shadow-lg shadow-purple-600/30">
                        <i class="fa-solid fa-compass mr-1.5"></i> Browse Upcoming Events
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Certificate Script Dependencies -->
<script src="<?= BASE_URL ?>/assets/js/certificate.js"></script>

<script>
function renderCertificateToCanvas(data) {
    const canvas = document.getElementById('certCanvas');
    if (!canvas) return;
    generateCertificateCanvas('certCanvas', data);
    showToast('success', 'Certificate Loaded', `Displaying verifiable certificate for ${data.eventTitle}`);
}

function openFeeModal(regId, title, fee) {
    document.getElementById('feeRegId').value = regId;
    document.getElementById('feeEventTitle').innerText = title;
    document.getElementById('feeAmountDisplay').innerText = `$${fee} / ₹${parseFloat(fee) * 10}`;
    openModal('certFeeModal');
}

function submitCertFeePayment(e) {
    e.preventDefault();
    const form = document.getElementById('certFeeForm');
    const formData = new FormData(form);
    const txn = (formData.get('txn_id') || '').trim();
    const contact = (formData.get('contact') || '').trim();

    if (txn.length < 4) {
        showToast('error', 'Validation Error', 'Please enter a valid Transaction / UTR reference number (at least 4 characters).');
        return;
    }
    if (contact.length < 8) {
        showToast('error', 'Validation Error', 'Please provide a valid contact number (at least 8 digits) for receipt verification.');
        return;
    }
    
    // Create simulated form submit POST
    const hiddenForm = document.createElement('form');
    hiddenForm.method = 'POST';
    hiddenForm.action = '<?= BASE_URL ?>/student/certificates.php';
    
    const actInput = document.createElement('input');
    actInput.type = 'hidden';
    actInput.name = 'action';
    actInput.value = 'submit_fee';
    hiddenForm.appendChild(actInput);

    const regInput = document.createElement('input');
    regInput.type = 'hidden';
    regInput.name = 'registration_id';
    regInput.value = formData.get('registration_id');
    hiddenForm.appendChild(regInput);

    const txnInput = document.createElement('input');
    txnInput.type = 'hidden';
    txnInput.name = 'txn_id';
    txnInput.value = txn;
    hiddenForm.appendChild(txnInput);

    document.body.appendChild(hiddenForm);
    hiddenForm.submit();
}

// Auto-render first attended certificate on load if available and issued
document.addEventListener('DOMContentLoaded', () => {
    <?php if (!empty($certificates) && $certificates[0]['certificate_issued'] == 1): ?>
        renderCertificateToCanvas(<?= json_encode([
            'studentName' => $user['name'],
            'enrolmentNo' => $user['enrolment_no'],
            'department' => $user['department'],
            'eventTitle' => $certificates[0]['event_title'],
            'eventDate' => format_event_date($certificates[0]['event_date']),
            'venue' => $certificates[0]['venue_name'] ?? 'Main Auditorium',
            'certCode' => $certificates[0]['certificate_code'] ?? 'CERT-ESP-2025-04291'
        ]) ?>);
    <?php endif; ?>
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
