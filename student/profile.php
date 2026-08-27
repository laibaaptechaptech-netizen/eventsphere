<?php
// student/profile.php - Student Profile Settings & Password Management
$page_title = "My Profile Settings";
require_once __DIR__ . '/../config/auth_check.php';
require_login();

$user = current_user();
$db = getDB();
$errors = [];
$success_msg = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = clean_input($_POST['action'] ?? 'update_profile');

    if ($action === 'update_profile') {
        $name = clean_input($_POST['name'] ?? '');
        $contact = clean_input($_POST['contact'] ?? '');
        $department = clean_input($_POST['department'] ?? 'Computer Science & Engineering');
        $enrolment_no = clean_input($_POST['enrolment_no'] ?? '');
        $avatar = clean_input($_POST['avatar'] ?? $user['avatar']);

        if (empty($name) || strlen($name) < 3 || strlen($name) > 100 || !preg_match("/^[a-zA-Z\s\.\-']+$/", $name)) {
            $errors[] = "Full Name must be between 3 and 100 characters and contain only letters, spaces, hyphens, and periods.";
        }
        if (!empty($contact) && !preg_match('/^[+]?[0-9\s\-]{8,20}$/', $contact)) {
            $errors[] = "Please provide a valid contact phone number (8-20 digits/symbols).";
        }
        if (!empty($enrolment_no) && (strlen($enrolment_no) < 3 || strlen($enrolment_no) > 50 || !preg_match('/^[a-zA-Z0-9\-\/]+$/', $enrolment_no))) {
            $errors[] = "Enrolment / Roll number must be 3-50 alphanumeric characters.";
        }
        if (!empty($avatar) && !filter_var($avatar, FILTER_VALIDATE_URL)) {
            $errors[] = "Please provide a valid avatar image URL.";
        }

        if (empty($errors)) {
            $stmt = $db->prepare("UPDATE users SET name = ?, contact = ?, department = ?, enrolment_no = ?, avatar = ? WHERE id = ?");
            $stmt->execute([$name, $contact, $department, $enrolment_no, $avatar, $user['id']]);
            
            // Refresh session data
            $_SESSION['user']['name'] = $name;
            $_SESSION['user']['contact'] = $contact;
            $_SESSION['user']['department'] = $department;
            $_SESSION['user']['enrolment_no'] = $enrolment_no;
            $_SESSION['user']['avatar'] = $avatar;
            $user = current_user();

            set_flash('success', 'Profile details updated successfully.');
            header("Location: " . BASE_URL . "/student/profile.php");
            exit;
        }
    } elseif ($action === 'change_password') {
        $current_pass = $_POST['current_password'] ?? '';
        $new_pass = $_POST['new_password'] ?? '';
        $confirm_pass = $_POST['confirm_password'] ?? '';

        $stmt = $db->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$user['id']]);
        $hash = $stmt->fetchColumn();

        if (!password_verify($current_pass, $hash)) {
            $errors[] = "Current password is incorrect.";
        } elseif (strlen($new_pass) < 6) {
            $errors[] = "New password must be at least 6 characters long.";
        } elseif ($new_pass !== $confirm_pass) {
            $errors[] = "New password confirmation does not match.";
        } else {
            $new_hash = password_hash($new_pass, PASSWORD_BCRYPT);
            $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$new_hash, $user['id']]);

            set_flash('success', 'Password changed successfully!');
            header("Location: " . BASE_URL . "/student/profile.php");
            exit;
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <div class="mb-8">
        <div class="badge-neon badge-emerald mb-2">Account Management</div>
        <h1 class="font-heading font-extrabold text-3xl text-white">Profile Settings</h1>
        <p class="text-slate-400 text-xs mt-1">Update your contact details, academic department, and security preferences.</p>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="p-4 rounded-xl bg-rose-950/40 border border-rose-500/40 text-rose-300 text-xs mb-6 space-y-1">
            <?php foreach ($errors as $err): ?>
                <div><i class="fa-solid fa-circle-exclamation mr-1"></i> <?= htmlspecialchars($err) ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        <!-- Left: Avatar Preview (1 col) -->
        <div class="glass-panel p-6 border border-white/10 text-center space-y-4 self-start">
            <img src="<?= htmlspecialchars(!empty($user['avatar']) ? $user['avatar'] : 'https://ui-avatars.com/api/?name='.urlencode($user['name']).'&background=0D8ABC&color=fff&size=200') ?>" alt="Avatar" class="w-28 h-28 rounded-2xl object-cover mx-auto border-2 border-purple-500/50 shadow-xl shadow-purple-500/20">
            <div>
                <h3 class="font-heading font-bold text-lg text-white"><?= htmlspecialchars($user['name']) ?></h3>
                <div class="text-xs text-cyan-400 font-semibold capitalize"><?= $user['role'] ?></div>
                <div class="text-xs text-slate-400 mt-1"><?= htmlspecialchars($user['email']) ?></div>
            </div>
            <div class="p-3 bg-dark-900/80 rounded-xl border border-white/5 text-xs text-slate-400 text-left space-y-1">
                <div><span class="text-slate-500">Username:</span> <?= htmlspecialchars($user['username']) ?></div>
                <div><span class="text-slate-500">Enrolment:</span> <?= htmlspecialchars($user['enrolment_no'] ?? 'N/A') ?></div>
                <div><span class="text-slate-500">Dept:</span> <?= htmlspecialchars($user['department']) ?></div>
            </div>
        </div>

        <!-- Right: Profile Form & Password Form (2 cols) -->
        <div class="md:col-span-2 space-y-8">
            <!-- Edit Details Form -->
            <div class="glass-panel-elevated p-6 border border-white/10">
                <h3 class="font-heading font-bold text-lg text-white mb-4">Personal & Academic Details</h3>
                <form method="POST" action="<?= BASE_URL ?>/student/profile.php" class="space-y-4">
                    <input type="hidden" name="action" value="update_profile">

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Full Name</label>
                        <input type="text" name="name" value="<?= htmlspecialchars($user['name']) ?>" required class="form-input-dark text-xs">
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1">Contact Phone</label>
                            <input type="text" name="contact" value="<?= htmlspecialchars($user['contact'] ?? '') ?>" class="form-input-dark text-xs">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1">Enrolment / Roll Number</label>
                            <input type="text" name="enrolment_no" value="<?= htmlspecialchars($user['enrolment_no'] ?? '') ?>" class="form-input-dark text-xs">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Academic Department</label>
                        <select name="department" class="form-input-dark text-xs">
                            <option value="Computer Science & Engineering" <?= ($user['department'] === 'Computer Science & Engineering') ? 'selected' : '' ?>>Computer Science & Engineering</option>
                            <option value="Information Technology" <?= ($user['department'] === 'Information Technology') ? 'selected' : '' ?>>Information Technology</option>
                            <option value="Robotics & Mechanical" <?= ($user['department'] === 'Robotics & Mechanical') ? 'selected' : '' ?>>Robotics & Mechanical</option>
                            <option value="Arts & Humanities" <?= ($user['department'] === 'Arts & Humanities') ? 'selected' : '' ?>>Arts & Humanities</option>
                            <option value="Business Administration" <?= ($user['department'] === 'Business Administration') ? 'selected' : '' ?>>Business Administration</option>
                            <option value="Physical Education" <?= ($user['department'] === 'Physical Education') ? 'selected' : '' ?>>Physical Education</option>
                            <option value="Central Administration" <?= ($user['department'] === 'Central Administration') ? 'selected' : '' ?>>Central Administration</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Avatar Image URL</label>
                        <input type="url" name="avatar" value="<?= htmlspecialchars($user['avatar']) ?>" class="form-input-dark text-xs">
                    </div>

                    <button type="submit" class="btn-neon-primary text-xs py-2.5 px-6 font-bold">
                        <i class="fa-solid fa-floppy-disk mr-1.5"></i> Save Profile Changes
                    </button>
                </form>
            </div>

            <!-- Change Password Form -->
            <div class="glass-panel-elevated p-6 border border-white/10">
                <h3 class="font-heading font-bold text-lg text-white mb-4">Security & Password</h3>
                <form method="POST" action="<?= BASE_URL ?>/student/profile.php" class="space-y-4">
                    <input type="hidden" name="action" value="change_password">

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Current Password</label>
                        <input type="password" name="current_password" required placeholder="••••••••" class="form-input-dark text-xs">
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1">New Password</label>
                            <input type="password" name="new_password" required placeholder="Minimum 6 characters" class="form-input-dark text-xs">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1">Confirm New Password</label>
                            <input type="password" name="confirm_password" required placeholder="Repeat new password" class="form-input-dark text-xs">
                        </div>
                    </div>

                    <button type="submit" class="btn-glass text-xs py-2.5 px-6 font-bold text-cyan-300 hover:text-white">
                        <i class="fa-solid fa-key mr-1.5"></i> Update Password
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.querySelector('form[action*="profile.php"] input[name="action"][value="update_profile"]').closest('form').addEventListener('submit', function(e) {
    const name = this.querySelector('[name="name"]').value.trim();
    const contact = this.querySelector('[name="contact"]').value.trim();
    const nameRegex = /^[a-zA-Z\s\.\-']{3,100}$/;
    const phoneRegex = /^[+]?[0-9\s\-]{8,20}$/;

    if (!nameRegex.test(name)) {
        e.preventDefault();
        showToast('error', 'Validation Error', 'Full Name must be at least 3 characters and contain only letters.');
        return;
    }
    if (contact && !phoneRegex.test(contact)) {
        e.preventDefault();
        showToast('error', 'Validation Error', 'Please enter a valid contact phone number.');
        return;
    }
});

document.querySelector('form[action*="profile.php"] input[name="action"][value="change_password"]').closest('form').addEventListener('submit', function(e) {
    const newPass = this.querySelector('[name="new_password"]').value;
    const confirmPass = this.querySelector('[name="confirm_password"]').value;

    if (newPass.length < 6) {
        e.preventDefault();
        showToast('error', 'Validation Error', 'New password must be at least 6 characters long.');
        return;
    }
    if (newPass !== confirmPass) {
        e.preventDefault();
        showToast('error', 'Validation Error', 'New password confirmation does not match.');
        return;
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
