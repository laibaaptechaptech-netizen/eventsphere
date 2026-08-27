<?php
// contact.php - Campus Support, Office Coordinates & Inquiry Submission Form
$page_title = "Contact & Campus Support";
require_once __DIR__ . '/includes/header.php';

$message_sent = false;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = clean_input($_POST['name'] ?? '');
    $email = clean_input($_POST['email'] ?? '');
    $subject = clean_input($_POST['subject'] ?? 'General Inquiry');
    $message = clean_input($_POST['message'] ?? '');

    if (empty($name) || strlen($name) < 3 || strlen($name) > 100 || !preg_match("/^[a-zA-Z\s\.\-']+$/", $name)) {
        $error = "Full name must be between 3 and 100 characters and contain only valid letters.";
    } elseif (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL) || !preg_match('/^[a-zA-Z][a-zA-Z0-9._-]*@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', $email) || strlen($email) > 150) {
        $error = "Please provide a valid email address that does not start with a number.";
    } elseif (empty($subject) || strlen($subject) < 3 || strlen($subject) > 200) {
        $error = "Subject must be between 3 and 200 characters.";
    } elseif (empty($message) || strlen($message) < 10 || strlen($message) > 2000) {
        $error = "Message must be between 10 and 2000 characters.";
    } else {
        try {
            $db = getDB();
            $stmt = $db->prepare("INSERT INTO contact_inquiries (name, email, subject, message, status, created_at) VALUES (?, ?, ?, ?, 'new', NOW())");
            $stmt->execute([$name, $email, $subject, $message]);
            $message_sent = true;
            set_flash('success', 'Thank you! Your message has been received by the EventSphere Secretariat.');
        } catch (Exception $e) {
            $error = "Database Error: Could not send inquiry. " . $e->getMessage();
        }
    }
}
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <div class="text-center max-w-2xl mx-auto mb-16">
        <div class="badge-neon badge-cyan mb-2">Campus Connect</div>
        <h1 class="font-heading font-extrabold text-3xl sm:text-5xl text-white mb-3">Get in Touch</h1>
        <p class="text-slate-400 text-sm">Have inquiries regarding event registrations, sponsorships, or volunteer opportunities? Reach out below.</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-10">
        <!-- Contact Information Cards (1 col) -->
        <div class="space-y-6">
            <div class="glass-panel p-6 border border-white/10">
                <div class="w-10 h-10 rounded-xl bg-purple-500/10 border border-purple-500/30 text-purple-400 flex items-center justify-center text-lg mb-4">
                    <i class="fa-solid fa-location-dot"></i>
                </div>
                <h3 class="font-heading font-bold text-white text-base mb-1">Campus Headquarters</h3>
                <p class="text-slate-400 text-xs leading-relaxed">
                    Tech Nexus Tower, Level 4<br>
                    Department of Student Affairs & Innovation<br>
                    University Campus East Gate
                </p>
            </div>

            <div class="glass-panel p-6 border border-white/10">
                <div class="w-10 h-10 rounded-xl bg-cyan-500/10 border border-cyan-500/30 text-cyan-400 flex items-center justify-center text-lg mb-4">
                    <i class="fa-solid fa-envelope"></i>
                </div>
                <h3 class="font-heading font-bold text-white text-base mb-1">Email Coordinates</h3>
                <p class="text-slate-400 text-xs leading-relaxed">
                    General Inquiries: <a href="mailto:support@eventsphere.edu" class="text-cyan-400 hover:underline">support@eventsphere.edu</a><br>
                    Organizer Helpdesk: <a href="mailto:organizer-desk@eventsphere.edu" class="text-cyan-400 hover:underline">organizer-desk@eventsphere.edu</a>
                </p>
            </div>

            <div class="glass-panel p-6 border border-white/10">
                <div class="w-10 h-10 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 flex items-center justify-center text-lg mb-4">
                    <i class="fa-solid fa-phone"></i>
                </div>
                <h3 class="font-heading font-bold text-white text-base mb-1">Helpline Hours</h3>
                <p class="text-slate-400 text-xs leading-relaxed">
                    Toll Free: +1 (800) 555-SPHERE<br>
                    Monday – Saturday: 08:30 AM – 06:00 PM<br>
                    Emergency Event Ops: 24/7 during Flagship Fests
                </p>
            </div>
        </div>

        <!-- Contact Submission Form (2 cols) -->
        <div class="lg:col-span-2">
            <div class="glass-panel-elevated p-8 sm:p-10 border border-purple-500/20">
                <h3 class="font-heading font-bold text-2xl text-white mb-2">Send an Official Inquiry</h3>
                <p class="text-slate-400 text-xs mb-8">All incoming messages are logged into the central admin desk with automatic response tracking.</p>

                <?php if ($error): ?>
                    <div class="p-4 rounded-xl bg-rose-950/40 border border-rose-500/40 text-rose-300 text-xs mb-6">
                        <i class="fa-solid fa-circle-exclamation mr-1"></i> <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <?php if ($message_sent): ?>
                    <div class="p-6 rounded-xl bg-emerald-950/40 border border-emerald-500/40 text-emerald-300 text-center space-y-2 mb-6">
                        <div class="text-2xl"><i class="fa-solid fa-circle-check"></i></div>
                        <h4 class="font-bold text-base text-white">Inquiry Successfully Submitted</h4>
                        <p class="text-xs">Your inquiry reference ticket has been created in the database. Our conveners will follow up shortly.</p>
                    </div>
                <?php endif; ?>

                <form method="POST" action="<?= BASE_URL ?>/contact.php" class="space-y-5">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1.5">Your Full Name *</label>
                            <input type="text" name="name" required placeholder="e.g. Sarah Jenkins" value="<?= $current_user ? htmlspecialchars($current_user['name']) : '' ?>" class="form-input-dark text-xs">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1.5">Email Address *</label>
                            <input type="email" name="email" required placeholder="e.g. sarah.j@student.edu" value="<?= $current_user ? htmlspecialchars($current_user['email']) : '' ?>" class="form-input-dark text-xs">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1.5">Inquiry Subject</label>
                        <select name="subject" class="form-input-dark text-xs">
                            <option value="Event Registration Assistance">Event Registration Assistance</option>
                            <option value="Certificate Verification & Re-issue">Certificate Verification & Re-issue</option>
                            <option value="Sponsorship & External Delegation">Sponsorship & External Delegation</option>
                            <option value="Volunteer Application">Volunteer Application</option>
                            <option value="Technical Glitch or Bug Report">Technical Glitch or Bug Report</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1.5">Detailed Message *</label>
                        <textarea name="message" rows="5" required placeholder="Please describe your questions, requirements, or delegation details..." class="form-input-dark text-xs"></textarea>
                    </div>

                    <button type="submit" class="btn-neon-primary py-3 px-8 text-xs font-bold w-full sm:w-auto">
                        <i class="fa-solid fa-paper-plane mr-2"></i> Submit Inquiry to Desk
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.querySelector('form[action*="contact.php"]').addEventListener('submit', function(e) {
    const name = this.querySelector('[name="name"]').value.trim();
    const email = this.querySelector('[name="email"]').value.trim();
    const message = this.querySelector('[name="message"]').value.trim();

    const nameRegex = /^[a-zA-Z\s\.\-']{3,100}$/;
    const emailRegex = /^[a-zA-Z][a-zA-Z0-9._-]*@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;

    if (!nameRegex.test(name)) {
        e.preventDefault();
        showToast('error', 'Validation Error', 'Full Name must be at least 3 characters and contain valid letters.');
        return;
    }
    if (!emailRegex.test(email)) {
        e.preventDefault();
        showToast('error', 'Validation Error', 'Please enter a valid email address.');
        return;
    }
    if (message.length < 10) {
        e.preventDefault();
        showToast('error', 'Validation Error', 'Please enter a message with at least 10 characters.');
        return;
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
