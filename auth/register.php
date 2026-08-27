<?php
// auth/register.php - Student Registration with Client-Side & Server-Side Regex Validation
$page_title = "Student Registration";
require_once __DIR__ . '/../config/config.php';

$errors = [];
$name = '';
$email = '';
$username = '';
$contact = '';
$department = 'Computer Science & Engineering';
$enrolment_no = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = clean_input($_POST['name'] ?? '');
    $email = clean_input($_POST['email'] ?? '');
    $username = clean_input($_POST['username'] ?? '');
    $contact = clean_input($_POST['contact'] ?? '');
    $department = clean_input($_POST['department'] ?? 'Computer Science & Engineering');
    $enrolment_no = clean_input($_POST['enrolment_no'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    $allowed_depts = [
        'Computer Science & Engineering',
        'Information Technology',
        'Robotics & Mechanical',
        'Arts & Humanities',
        'Business Administration',
        'Physical Education',
        'Central Administration'
    ];

    // Strong server-side validation
    if (empty($name) || strlen($name) < 3 || strlen($name) > 100 || !preg_match("/^[a-zA-Z\s\.\-']+$/", $name)) {
        $errors[] = "Full Name must be between 3 and 100 characters and contain only letters, spaces, hyphens, and periods.";
    }
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL) || !preg_match('/^[a-zA-Z][a-zA-Z0-9._-]*@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', $email) || strlen($email) > 150) {
        $errors[] = "Please provide a valid email address that does not start with a number.";
    }
    if (empty($username) || !preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username)) {
        $errors[] = "Username must be 3-20 characters and contain only alphanumeric characters and underscores.";
    }
    if (empty($contact) || !preg_match('/^[+]?[0-9\s\-]{8,20}$/', $contact)) {
        $errors[] = "Please provide a valid phone number (8-20 digits/symbols).";
    }
    if (empty($enrolment_no) || strlen($enrolment_no) < 3 || strlen($enrolment_no) > 50 || !preg_match('/^[a-zA-Z0-9\-\/]+$/', $enrolment_no)) {
        $errors[] = "Enrolment / Roll Number must be 3-50 alphanumeric characters (hyphens and slashes allowed).";
    }
    if (!in_array($department, $allowed_depts)) {
        $errors[] = "Please select a valid academic department.";
    }
    if (empty($password) || strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters long.";
    }
    if ($password !== $confirm_password) {
        $errors[] = "Password confirmation does not match.";
    }

    if (empty($errors)) {
        $db = getDB();
        
        // Check uniqueness of email and username
        $check = $db->prepare("SELECT id, email, username FROM users WHERE email = ? OR username = ?");
        $check->execute([$email, $username]);
        $existing = $check->fetch();

        if ($existing) {
            if ($existing['email'] === $email) {
                $errors[] = "An account with this email address already exists.";
                set_flash('error', 'An account with this email address already exists.');
            }
            if ($existing['username'] === $username) {
                $errors[] = "This username is already taken. Please choose another.";
                set_flash('error', 'This username is already taken.');
            }
        } else {
            $hashed = password_hash($password, PASSWORD_BCRYPT);
            $avatar = '';

            $insert = $db->prepare("
                INSERT INTO users (name, email, username, password, contact, department, enrolment_no, role, status, avatar, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'student', 'active', ?, NOW())
            ");
            $insert->execute([$name, $email, $username, $hashed, $contact, $department, $enrolment_no, $avatar]);
            $new_user_id = $db->lastInsertId();

            // Create welcome notification
            create_notification(
                $new_user_id,
                '🎉 Welcome to EventSphere!',
                'Your student account has been registered. Explore events and claim your passes now!',
                BASE_URL . '/events.php',
                'success'
            );

            set_flash('success', 'Registration successful! Please sign in to access your dashboard.');
            header("Location: " . BASE_URL . "/auth/login.php");
            exit;
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="max-w-xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <div class="text-center mb-8">
        <div class="w-14 h-14 rounded-2xl bg-gradient-to-tr from-cyan-500 to-purple-600 flex items-center justify-center text-white text-2xl mx-auto mb-4 shadow-xl shadow-cyan-500/25">
            <i class="fa-solid fa-user-plus"></i>
        </div>
        <h1 class="font-heading font-extrabold text-3xl text-white">Create Participant Account</h1>
        <p class="text-slate-400 text-xs mt-1">Join the campus ecosystem to register for events, download certificates, and submit reviews.</p>
    </div>

    <div class="glass-panel-elevated p-8 border border-white/10 relative">
        <?php if (!empty($errors)): ?>
            <div class="p-4 rounded-xl bg-rose-950/40 border border-rose-500/40 text-rose-300 text-xs mb-6 space-y-1">
                <?php foreach ($errors as $err): ?>
                    <div class="flex items-center gap-2">
                        <i class="fa-solid fa-circle-exclamation flex-shrink-0"></i>
                        <span><?= htmlspecialchars($err) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Client-Side Validated Registration Form -->
        <form method="POST" action="<?= BASE_URL ?>/auth/register.php" onsubmit="return validateRegisterForm(event)" class="space-y-4" id="regForm">
            <!-- Full Name -->
            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Full Name *</label>
                <input type="text" id="regName" name="name" required value="<?= htmlspecialchars($name) ?>" placeholder="e.g. Alex Morgan" class="form-input-dark text-xs">
                <span id="nameError" class="text-[11px] text-rose-400 hidden mt-1">Name must be at least 3 characters.</span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- University Email -->
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Email Address *</label>
                    <input type="email" id="regEmail" name="email" required value="<?= htmlspecialchars($email) ?>" placeholder="alex.m@student.edu" class="form-input-dark text-xs">
                    <span id="emailError" class="text-[11px] text-rose-400 hidden mt-1">Invalid email format.</span>
                </div>

                <!-- Username -->
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Username *</label>
                    <input type="text" id="regUsername" name="username" required value="<?= htmlspecialchars($username) ?>" placeholder="alexmorgan" class="form-input-dark text-xs">
                    <span id="userError" class="text-[11px] text-rose-400 hidden mt-1">Alphanumeric, 3-20 chars.</span>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- Contact Number -->
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Contact Number *</label>
                    <input type="text" id="regContact" name="contact" required value="<?= htmlspecialchars($contact) ?>" placeholder="+1-555-0199" class="form-input-dark text-xs">
                    <span id="contactError" class="text-[11px] text-rose-400 hidden mt-1">Valid phone number required.</span>
                </div>

                <!-- Enrolment Number -->
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Student Enrolment / Roll No *</label>
                    <input type="text" id="regEnrol" name="enrolment_no" required value="<?= htmlspecialchars($enrolment_no) ?>" placeholder="EN2026-CS-088" class="form-input-dark text-xs">
                    <span id="enrolError" class="text-[11px] text-rose-400 hidden mt-1">3-50 alphanumeric characters.</span>
                </div>
            </div>

            <!-- Department Selection -->
            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Academic Department</label>
                <select name="department" class="form-input-dark text-xs">
                    <option value="Computer Science & Engineering" <?= $department === 'Computer Science & Engineering' ? 'selected' : '' ?>>Computer Science & Engineering</option>
                    <option value="Information Technology" <?= $department === 'Information Technology' ? 'selected' : '' ?>>Information Technology</option>
                    <option value="Robotics & Mechanical" <?= $department === 'Robotics & Mechanical' ? 'selected' : '' ?>>Robotics & Mechanical</option>
                    <option value="Arts & Humanities" <?= $department === 'Arts & Humanities' ? 'selected' : '' ?>>Arts & Humanities</option>
                    <option value="Business Administration" <?= $department === 'Business Administration' ? 'selected' : '' ?>>Business Administration</option>
                    <option value="Physical Education" <?= $department === 'Physical Education' ? 'selected' : '' ?>>Physical Education</option>
                </select>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- Password -->
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Password *</label>
                    <input type="password" id="regPass" name="password" required placeholder="At least 6 chars" class="form-input-dark text-xs">
                    <span id="passError" class="text-[11px] text-rose-400 hidden mt-1">Minimum 6 characters.</span>
                </div>

                <!-- Confirm Password -->
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Confirm Password *</label>
                    <input type="password" id="regConfirmPass" name="confirm_password" required placeholder="Repeat password" class="form-input-dark text-xs">
                    <span id="confirmError" class="text-[11px] text-rose-400 hidden mt-1">Passwords do not match.</span>
                </div>
            </div>

            <button type="submit" class="btn-neon-primary w-full py-3 text-xs font-bold shadow-lg shadow-purple-600/30 mt-4">
                <i class="fa-solid fa-user-plus mr-2"></i> Register Student Account
            </button>
        </form>

        <div class="mt-6 pt-4 border-t border-white/10 text-center text-xs text-slate-400">
            <span>Already have an account? </span>
            <a href="<?= BASE_URL ?>/auth/login.php" class="text-cyan-400 hover:underline font-semibold">Sign In here</a>
        </div>
    </div>
</div>

<!-- Client-side Validation Script -->
<script>
function validateRegisterForm(e) {
    let isValid = true;

    const name = document.getElementById('regName').value.trim();
    const email = document.getElementById('regEmail').value.trim();
    const username = document.getElementById('regUsername').value.trim();
    const contact = document.getElementById('regContact').value.trim();
    const enrol = document.getElementById('regEnrol').value.trim();
    const pass = document.getElementById('regPass').value;
    const confirm = document.getElementById('regConfirmPass').value;

    const nameRegex = /^[a-zA-Z\s\.\-']{3,100}$/;
    const emailRegex = /^[a-zA-Z][a-zA-Z0-9._-]*@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
    const userRegex = /^[a-zA-Z0-9_]{3,20}$/;
    const phoneRegex = /^[+]?[0-9\s\-]{8,20}$/;
    const enrolRegex = /^[a-zA-Z0-9\-\/]{3,50}$/;

    // Name
    if (!nameRegex.test(name)) {
        document.getElementById('nameError').classList.remove('hidden');
        isValid = false;
    } else {
        document.getElementById('nameError').classList.add('hidden');
    }

    // Email
    if (!emailRegex.test(email)) {
        document.getElementById('emailError').classList.remove('hidden');
        isValid = false;
    } else {
        document.getElementById('emailError').classList.add('hidden');
    }

    // Username
    if (!userRegex.test(username)) {
        document.getElementById('userError').classList.remove('hidden');
        isValid = false;
    } else {
        document.getElementById('userError').classList.add('hidden');
    }

    // Contact
    if (!phoneRegex.test(contact)) {
        document.getElementById('contactError').classList.remove('hidden');
        isValid = false;
    } else {
        document.getElementById('contactError').classList.add('hidden');
    }

    // Enrolment
    if (!enrolRegex.test(enrol)) {
        document.getElementById('enrolError').classList.remove('hidden');
        isValid = false;
    } else {
        document.getElementById('enrolError').classList.add('hidden');
    }

    // Password
    if (pass.length < 6) {
        document.getElementById('passError').classList.remove('hidden');
        isValid = false;
    } else {
        document.getElementById('passError').classList.add('hidden');
    }

    // Confirm
    if (pass !== confirm) {
        document.getElementById('confirmError').classList.remove('hidden');
        isValid = false;
    } else {
        document.getElementById('confirmError').classList.add('hidden');
    }

    if (!isValid) {
        showToast('error', 'Validation Error', 'Please correct the highlighted fields in the form.');
        return false;
    }

    return true;
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
