<?php
// organizer/create_event.php - Propose New Campus Event (Enters 'Pending Approval' Workflow)
$page_title = "Propose New Event";
require_once __DIR__ . '/../config/auth_check.php';
require_organizer();

$user = current_user();
$db = getDB();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = clean_input($_POST['title'] ?? '');
    $category_id = (int)($_POST['category_id'] ?? 1);
    $department = clean_input($_POST['department'] ?? $user['department']);
    $description = clean_input($_POST['description'] ?? '');
    $venue_id = !empty($_POST['venue_id']) ? (int)$_POST['venue_id'] : null;
    $custom_venue = clean_input($_POST['custom_venue_name'] ?? '');
    $event_date = clean_input($_POST['event_date'] ?? '');
    $start_time = clean_input($_POST['start_time'] ?? '');
    $end_time = clean_input($_POST['end_time'] ?? '');
    $cutoff = clean_input($_POST['registration_cutoff'] ?? ($event_date . ' 23:59:59'));
    $max_capacity = (int)($_POST['max_capacity'] ?? 50);
    $fee_amount = (float)($_POST['fee_amount'] ?? 0.00);
    $certificate_fee = (float)($_POST['certificate_fee'] ?? 150.00);
    $banner_url = clean_input($_POST['banner_url'] ?? 'https://images.unsplash.com/photo-1504384308090-c894fdcc538d?auto=format&fit=crop&w=1200&q=80');
    $rulebook_file = clean_input($_POST['rulebook_file'] ?? 'uploads/rules/sample_rulebook.pdf');

    // Strong server-side validation
    if (empty($title) || strlen($title) < 5) {
        $errors[] = "Event title must be at least 5 characters long.";
    }
    if (empty($event_date) || strtotime($event_date) < strtotime(date('Y-m-d'))) {
        $errors[] = "Event date must be today or in the future.";
    }
    if (empty($start_time) || empty($end_time)) {
        $errors[] = "Start time and end time are required.";
    } elseif (strtotime($event_date . ' ' . $end_time) <= strtotime($event_date . ' ' . $start_time)) {
        $errors[] = "Event end time must be after the start time.";
    }
    if ($max_capacity < 5 || $max_capacity > 2000) {
        $errors[] = "Max seating capacity must be between 5 and 2000.";
    }
    if ($fee_amount < 0 || $certificate_fee < 0) {
        $errors[] = "Fees cannot be negative values.";
    }
    if (empty($description) || strlen($description) < 20) {
        $errors[] = "Event description and agenda must be at least 20 characters long.";
    }
    if (empty($banner_url) || !filter_var($banner_url, FILTER_VALIDATE_URL)) {
        $errors[] = "Please provide a valid banner image URL.";
    }

    if (empty($errors)) {
        // Generate unique slug
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title))) . '-' . rand(100, 999);

        try {
            $stmt = $db->prepare("
                INSERT INTO events 
                (title, slug, category_id, organizer_id, department, description, venue_id, custom_venue_name, event_date, start_time, end_time, registration_cutoff, max_capacity, fee_amount, certificate_fee, banner_image, rulebook_file, status, admin_notes, featured, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'Submitted by Organizer. Awaiting Admin Approval.', 0, NOW())
            ");
            $stmt->execute([
                $title, $slug, $category_id, $user['id'], $department, $description, $venue_id, $custom_venue, $event_date, $start_time, $end_time, $cutoff, $max_capacity, $fee_amount, $certificate_fee, $banner_url, $rulebook_file
            ]);

            set_flash('success', 'Event proposed successfully! It has entered "Pending Approval" state and will go live once reviewed by an Admin.');
            header("Location: " . BASE_URL . "/organizer/dashboard.php");
            exit;
        } catch (Exception $e) {
            $errors[] = "Database Error: Could not create event. " . $e->getMessage();
        }
    }
}

$categories = $db->query("SELECT * FROM categories ORDER BY id ASC")->fetchAll();
$venues = $db->query("SELECT * FROM venues WHERE is_active = 1 ORDER BY name ASC")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <div class="mb-8">
        <div class="badge-neon badge-purple mb-2">SRS Event Workflow</div>
        <h1 class="font-heading font-extrabold text-3xl text-white">Propose New Campus Event</h1>
        <p class="text-slate-400 text-xs mt-1">Proposed events enter a 'Pending Approval' queue and go live upon Administrator review.</p>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="p-4 rounded-xl bg-rose-950/40 border border-rose-500/40 text-rose-300 text-xs mb-6 space-y-1">
            <?php foreach ($errors as $err): ?>
                <div><i class="fa-solid fa-circle-exclamation mr-1"></i> <?= htmlspecialchars($err) ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="glass-panel-elevated p-8 border border-white/10">
        <form method="POST" action="<?= BASE_URL ?>/organizer/create_event.php" class="space-y-6">
            <!-- Event Title -->
            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1.5">Event Title *</label>
                <input type="text" name="title" required placeholder="e.g. AI Odyssey 2026: Hands-on Quantum Algorithms" class="form-input-dark text-xs">
            </div>

            <!-- Category & Department Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1.5">Event Category *</label>
                    <select name="category_id" required class="form-input-dark text-xs">
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1.5">Organizing Department *</label>
                    <select name="department" class="form-input-dark text-xs">
                        <option value="Computer Science & Engineering">Computer Science & Engineering</option>
                        <option value="Information Technology">Information Technology</option>
                        <option value="Robotics & Mechanical">Robotics & Mechanical</option>
                        <option value="Arts & Humanities">Arts & Humanities</option>
                        <option value="Physical Education">Physical Education</option>
                        <option value="Central Administration">Central Administration</option>
                    </select>
                </div>
            </div>

            <!-- Venue Selection & Max Seating Limit -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-slate-300 mb-1.5">Campus Venue Allocation</label>
                    <select name="venue_id" class="form-input-dark text-xs">
                        <option value="">-- Select Allocated Venue --</option>
                        <?php foreach ($venues as $v): ?>
                            <option value="<?= $v['id'] ?>"><?= htmlspecialchars($v['name']) ?> (Max: <?= $v['max_capacity'] ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1.5">Max Seating Capacity *</label>
                    <input type="number" name="max_capacity" min="5" max="1000" value="60" required class="form-input-dark text-xs">
                </div>
            </div>

            <!-- Schedule Dates & Times -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1.5">Event Date *</label>
                    <input type="date" name="event_date" required value="<?= date('Y-m-d', strtotime('+7 days')) ?>" class="form-input-dark text-xs">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1.5">Start Time *</label>
                    <input type="time" name="start_time" required value="10:00" class="form-input-dark text-xs">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1.5">End Time *</label>
                    <input type="time" name="end_time" required value="17:00" class="form-input-dark text-xs">
                </div>
            </div>

            <!-- Fees & Cutoff -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1.5">Registration Fee ($/₹)</label>
                    <input type="number" step="0.01" name="fee_amount" value="0.00" class="form-input-dark text-xs">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1.5">Certificate Fee ($/₹)</label>
                    <input type="number" step="0.01" name="certificate_fee" value="150.00" class="form-input-dark text-xs">
                </div>
            </div>

            <!-- Rulebook & Official Agenda Attachment -->
            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1.5">Official Rulebook & Agenda PDF Attachment</label>
                <select name="rulebook_file" class="form-input-dark text-xs">
                    <option value="uploads/rules/sample_rulebook.pdf">Standard Collegiate Rulebook & Agenda (Default)</option>
                    <option value="uploads/rules/hacknova_rules.pdf">HackNova AI Hackathon Specifications & Rulebook</option>
                    <option value="uploads/rules/symphonia_guidelines.pdf">Symphonia Cultural Carnival Stage Guidelines</option>
                    <option value="uploads/rules/roboclash_specs.pdf">RoboClash Combat Robot Specs & Safety Policy</option>
                    <option value="uploads/rules/workshop_syllabus.pdf">Technical Workshop Curriculum & Lab Syllabus</option>
                    <option value="uploads/rules/olympics_rulebook.pdf">Inter-Department Sports Olympiad Rulebook</option>
                    <option value="uploads/rules/gala_agenda.pdf">Annual Convocation Gala Protocol & Agenda</option>
                    <option value="uploads/rules/ctf_rules.pdf">CyberGuard CTF Tournament Defense Guidelines</option>
                </select>
            </div>

            <!-- Banner URL -->
            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1.5">Banner Image URL *</label>
                <input type="url" name="banner_url" required value="https://images.unsplash.com/photo-1504384308090-c894fdcc538d?auto=format&fit=crop&w=1200&q=80" class="form-input-dark text-xs">
            </div>

            <div class="pt-4 border-t border-white/10 flex items-center justify-between">
                <a href="<?= BASE_URL ?>/organizer/dashboard.php" class="btn-glass text-xs py-2.5 px-5">Cancel</a>
                <button type="submit" class="btn-neon-primary text-xs py-2.5 px-8 font-bold shadow-lg shadow-purple-600/30">
                    <i class="fa-solid fa-paper-plane mr-2"></i> Submit Proposal to Admin
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.querySelector('form').addEventListener('submit', function(e) {
    const title = this.querySelector('[name="title"]').value.trim();
    const eventDate = this.querySelector('[name="event_date"]').value;
    const startTime = this.querySelector('[name="start_time"]').value;
    const endTime = this.querySelector('[name="end_time"]').value;
    const desc = this.querySelector('[name="description"]').value.trim();
    const capacity = parseInt(this.querySelector('[name="max_capacity"]').value);

    if (title.length < 5) {
        e.preventDefault();
        showToast('error', 'Validation Error', 'Event title must be at least 5 characters long.');
        return;
    }
    if (new Date(eventDate) < new Date(new Date().toDateString())) {
        e.preventDefault();
        showToast('error', 'Validation Error', 'Event date cannot be in the past.');
        return;
    }
    if (startTime && endTime && startTime >= endTime) {
        e.preventDefault();
        showToast('error', 'Validation Error', 'End time must be after start time.');
        return;
    }
    if (isNaN(capacity) || capacity < 5 || capacity > 2000) {
        e.preventDefault();
        showToast('error', 'Validation Error', 'Seating capacity must be between 5 and 2000.');
        return;
    }
    if (desc.length < 20) {
        e.preventDefault();
        showToast('error', 'Validation Error', 'Please write a detailed description (minimum 20 characters).');
        return;
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
