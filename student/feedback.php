<?php
// student/feedback.php - Multi-Component Event Review & Star Rating Submission
$page_title = "Submit Event Review";
require_once __DIR__ . '/../config/auth_check.php';
require_student();

$user = current_user();
$db = getDB();
$preselected_event_id = (int)($_GET['event_id'] ?? 0);

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $event_id = (int)($_POST['event_id'] ?? 0);
    $user_type = clean_input($_POST['user_type'] ?? 'Participant');
    $overall_rating = max(1, min(5, (int)($_POST['overall_rating'] ?? 5)));
    $rating_venue = max(1, min(5, (int)($_POST['rating_venue'] ?? 5)));
    $rating_coordination = max(1, min(5, (int)($_POST['rating_coordination'] ?? 5)));
    $rating_technical = max(1, min(5, (int)($_POST['rating_technical'] ?? 5)));
    $rating_hospitality = max(1, min(5, (int)($_POST['rating_hospitality'] ?? 5)));
    $comments = clean_input($_POST['comments'] ?? '');
    $suggestions = clean_input($_POST['suggestions'] ?? '');

    $allowed_types = ['Participant', 'Volunteer', 'Guest Student', 'Faculty'];

    if ($event_id <= 0) {
        $errors[] = "Please select a valid event to review.";
    } else {
        // Verify event exists
        $ev_chk = $db->prepare("SELECT id FROM events WHERE id = ? AND status IN ('approved', 'completed')");
        $ev_chk->execute([$event_id]);
        if (!$ev_chk->fetch()) {
            $errors[] = "Selected event was not found.";
        }
    }
    if (!in_array($user_type, $allowed_types)) {
        $errors[] = "Please select a valid persona type.";
    }
    if (empty($comments) || strlen($comments) < 5 || strlen($comments) > 1000) {
        $errors[] = "Written experience review must be between 5 and 1000 characters.";
    }
    if (strlen($suggestions) > 1000) {
        $errors[] = "Suggestions cannot exceed 1000 characters.";
    }

    if (empty($errors)) {
        try {
            $stmt = $db->prepare("
                INSERT INTO feedback_reviews 
                (event_id, user_id, user_type, overall_rating, rating_venue, rating_coordination, rating_technical, rating_hospitality, comments, suggestions, is_approved, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())
            ");
            $stmt->execute([
                $event_id, $user['id'], $user_type, $overall_rating, $rating_venue, $rating_coordination, $rating_technical, $rating_hospitality, $comments, $suggestions
            ]);

            set_flash('success', 'Thank you! Your feedback review has been submitted.');
            header("Location: " . BASE_URL . "/event_detail.php?id=" . $event_id);
            exit;
        } catch (Exception $e) {
            $errors[] = "Database Error: Could not save review. " . $e->getMessage();
        }
    }
}

// Fetch all events attended by the student or all approved events
$events_stmt = $db->query("SELECT id, title, event_date FROM events WHERE status IN ('approved', 'completed') ORDER BY event_date DESC");
$all_events = $events_stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <div class="text-center mb-8">
        <div class="badge-neon badge-amber mb-2">Participant Feedback</div>
        <h1 class="font-heading font-extrabold text-3xl text-white">Rate & Review Campus Event</h1>
        <p class="text-slate-400 text-xs mt-1">Help organizers improve future hackathons, cultural festivals, and workshops.</p>
    </div>

    <div class="glass-panel-elevated p-8 border border-white/10 relative">
        <?php if (!empty($errors)): ?>
            <div class="p-4 rounded-xl bg-rose-950/40 border border-rose-500/40 text-rose-300 text-xs mb-6 space-y-1">
                <?php foreach ($errors as $err): ?>
                    <div><i class="fa-solid fa-circle-exclamation mr-1"></i> <?= htmlspecialchars($err) ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?= BASE_URL ?>/student/feedback.php" class="space-y-6">
            <!-- Event Picker -->
            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1.5">Select Event *</label>
                <select name="event_id" required class="form-input-dark text-xs">
                    <option value="">-- Choose an Event --</option>
                    <?php foreach ($all_events as $ev): ?>
                        <option value="<?= $ev['id'] ?>" <?= ($preselected_event_id == $ev['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($ev['title']) ?> (<?= format_event_date($ev['event_date']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- User Type Selection (SRS requirement: Participant, Volunteer, Guest Student, Faculty) -->
            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1.5">Your User Persona Type *</label>
                <select name="user_type" class="form-input-dark text-xs">
                    <option value="Participant">Participant (Registered Student)</option>
                    <option value="Volunteer">Volunteer / Student Coordinator</option>
                    <option value="Guest Student">Guest Student (Inter-University)</option>
                    <option value="Faculty">Faculty & Academic Observer</option>
                </select>
            </div>

            <!-- Overall Star Rating -->
            <div class="bg-dark-900/80 p-4 rounded-xl border border-white/10 text-center">
                <label class="block text-xs font-bold text-slate-200 uppercase tracking-wider mb-2">Overall Experience Rating *</label>
                <div class="flex items-center justify-center gap-3 text-2xl text-amber-400 cursor-pointer" id="overallStars">
                    <i class="fa-solid fa-star star-btn" onclick="setRating('overall', 1)"></i>
                    <i class="fa-solid fa-star star-btn" onclick="setRating('overall', 2)"></i>
                    <i class="fa-solid fa-star star-btn" onclick="setRating('overall', 3)"></i>
                    <i class="fa-solid fa-star star-btn" onclick="setRating('overall', 4)"></i>
                    <i class="fa-solid fa-star star-btn" onclick="setRating('overall', 5)"></i>
                </div>
                <input type="hidden" name="overall_rating" id="input_overall" value="5">
            </div>

            <!-- Component Breakdown Ratings (SRS requirement: Venue, Coordination, Tech, Hospitality) -->
            <div class="space-y-4 pt-2">
                <h4 class="font-bold text-xs uppercase tracking-wider text-cyan-400">Rate Specific Components</h4>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- Venue -->
                    <div class="p-3 rounded-xl bg-slate-900/40 border border-white/5">
                        <div class="flex justify-between text-xs text-slate-300 mb-1.5">
                            <span>Venue & Stage Setup</span>
                            <span class="font-bold text-amber-400" id="label_venue">5 ★</span>
                        </div>
                        <input type="range" name="rating_venue" min="1" max="5" value="5" oninput="updateRangeLabel('venue', this.value)" class="w-full accent-cyan-400">
                    </div>

                    <!-- Coordination -->
                    <div class="p-3 rounded-xl bg-slate-900/40 border border-white/5">
                        <div class="flex justify-between text-xs text-slate-300 mb-1.5">
                            <span>Organizer Coordination</span>
                            <span class="font-bold text-amber-400" id="label_coord">5 ★</span>
                        </div>
                        <input type="range" name="rating_coordination" min="1" max="5" value="5" oninput="updateRangeLabel('coord', this.value)" class="w-full accent-purple-400">
                    </div>

                    <!-- Technical Arrangements -->
                    <div class="p-3 rounded-xl bg-slate-900/40 border border-white/5">
                        <div class="flex justify-between text-xs text-slate-300 mb-1.5">
                            <span>Technical / Lab Arrangements</span>
                            <span class="font-bold text-amber-400" id="label_tech">5 ★</span>
                        </div>
                        <input type="range" name="rating_technical" min="1" max="5" value="5" oninput="updateRangeLabel('tech', this.value)" class="w-full accent-emerald-400">
                    </div>

                    <!-- Hospitality -->
                    <div class="p-3 rounded-xl bg-slate-900/40 border border-white/5">
                        <div class="flex justify-between text-xs text-slate-300 mb-1.5">
                            <span>Hospitality & Refreshments</span>
                            <span class="font-bold text-amber-400" id="label_hosp">5 ★</span>
                        </div>
                        <input type="range" name="rating_hospitality" min="1" max="5" value="5" oninput="updateRangeLabel('hosp', this.value)" class="w-full accent-amber-400">
                    </div>
                </div>
            </div>

            <!-- Written Comments & Suggestions -->
            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1.5">Written Experience Review *</label>
                <textarea name="comments" rows="3" required placeholder="What did you love most about this event?" class="form-input-dark text-xs"></textarea>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1.5">Constructive Suggestions for Improvement</label>
                <textarea name="suggestions" rows="2" placeholder="Any suggestions regarding schedule, food, lab hardware, or audio?" class="form-input-dark text-xs"></textarea>
            </div>

            <button type="submit" class="btn-neon-primary w-full py-3 text-xs font-bold shadow-lg shadow-purple-600/30">
                <i class="fa-solid fa-paper-plane mr-2"></i> Submit Official Review
            </button>
        </form>
    </div>
</div>

<script>
function setRating(type, val) {
    document.getElementById('input_' + type).value = val;
    const container = document.getElementById(type + 'Stars');
    const stars = container.querySelectorAll('.star-btn');
    stars.forEach((star, idx) => {
        if (idx < val) {
            star.classList.remove('fa-regular');
            star.classList.add('fa-solid');
        } else {
            star.classList.remove('fa-solid');
            star.classList.add('fa-regular');
        }
    });
}

function updateRangeLabel(id, val) {
    document.getElementById('label_' + id).innerText = val + ' ★';
}

document.querySelector('form[action*="feedback.php"]').addEventListener('submit', function(e) {
    const eventId = this.querySelector('[name="event_id"]').value;
    const comments = this.querySelector('[name="comments"]').value.trim();

    if (!eventId) {
        e.preventDefault();
        showToast('error', 'Validation Error', 'Please select an event to review.');
        return;
    }
    if (comments.length < 5) {
        e.preventDefault();
        showToast('error', 'Validation Error', 'Please write a review of at least 5 characters.');
        return;
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
