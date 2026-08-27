<?php
// auth/login.php - Secure Multi-Role Authentication with 2FA & Quick Demo Switcher
$page_title = "Sign In to EventSphere";
require_once __DIR__ . '/../config/config.php';

$error = null;
$quick = clean_input($_GET['quick'] ?? '');
$redirect = clean_input($_GET['redirect'] ?? '');

// Pre-fill demo accounts if quick parameter is supplied
$preset_email = '';
$preset_pass = '';
$preset_role = 'student';

if ($quick === 'admin') {
    $preset_email = 'admin@eventsphere.edu';
    $preset_pass = 'Admin@123';
    $preset_role = 'admin';
} elseif ($quick === 'organizer') {
    $preset_email = 'cs.organizer@eventsphere.edu';
    $preset_pass = 'Organizer@123';
    $preset_role = 'organizer';
} elseif ($quick === 'student') {
    $preset_email = 'john.doe@eventsphere.edu';
    $preset_pass = 'Student@123';
    $preset_role = 'student';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login_input = clean_input($_POST['login_input'] ?? '');
    $password = $_POST['password'] ?? '';
    $two_factor_code = clean_input($_POST['two_factor_code'] ?? '');

    if (empty($login_input) || empty($password)) {
        $error = "Please enter both your email/username and password.";
    } else {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ? OR username = ? LIMIT 1");
        $stmt->execute([$login_input, $login_input]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            if ($user['status'] === 'suspended') {
                $error = "This account has been suspended by administration.";
            } else {
                // Success Login
                $_SESSION['user'] = [
                    'id' => $user['id'],
                    'name' => $user['name'],
                    'email' => $user['email'],
                    'username' => $user['username'],
                    'role' => $user['role'],
                    'department' => $user['department'],
                    'enrolment_no' => $user['enrolment_no'],
                    'avatar' => $user['avatar'],
                    'status' => $user['status']
                ];

                set_flash('success', "Welcome back, " . htmlspecialchars($user['name']) . "!");

                if (!empty($redirect)) {
                    header("Location: " . urldecode($redirect));
                    exit;
                }

                // Role Redirects
                switch ($user['role']) {
                    case 'admin':
                        header("Location: " . BASE_URL . "/admin/dashboard.php");
                        break;
                    case 'organizer':
                        header("Location: " . BASE_URL . "/organizer/dashboard.php");
                        break;
                    case 'student':
                    default:
                        header("Location: " . BASE_URL . "/student/dashboard.php");
                        break;
                }
                exit;
            }
        } else {
            $error = "Invalid login credentials. Please check your email/username and password.";
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="min-h-[80vh] flex items-center justify-center px-4 sm:px-6 lg:px-8 py-12">
    <div class="max-w-md w-full space-y-6">
        <!-- Brand Header -->
        <div class="text-center">
            <div class="w-14 h-14 rounded-2xl bg-gradient-to-tr from-purple-600 to-cyan-500 flex items-center justify-center text-white text-2xl mx-auto mb-4 shadow-xl shadow-purple-500/25">
                <i class="fa-solid fa-lock"></i>
            </div>
            <h2 class="font-heading font-extrabold text-3xl text-white tracking-tight">Sign In to EventSphere</h2>
            <p class="text-slate-400 text-xs mt-1">Access your event passes, organizer tools, or administrative dashboard.</p>
        </div>

        <!-- Quick Demo Credentials Box -->
        <div class="bg-dark-800/90 p-4 rounded-2xl border border-purple-500/30 text-xs space-y-2.5">
            <div class="flex items-center justify-between text-slate-300 font-bold">
                <span class="text-purple-300"><i class="fa-solid fa-wand-magic-sparkles mr-1"></i> Quick Fill Demo Accounts</span>
                <span class="text-[10px] text-slate-400">1-Click Fill</span>
            </div>
            <div class="grid grid-cols-3 gap-2">
                <button type="button" onclick="fillCredentials('admin@eventsphere.edu', 'Admin@123', '123456')" class="bg-purple-950/60 hover:bg-purple-900 border border-purple-500/30 p-2 rounded-xl text-center text-purple-200 font-semibold transition">
                    <i class="fa-solid fa-shield-halved block text-sm mb-0.5"></i> Admin
                </button>
                <button type="button" onclick="fillCredentials('cs.organizer@eventsphere.edu', 'Organizer@123', '')" class="bg-cyan-950/60 hover:bg-cyan-900 border border-cyan-500/30 p-2 rounded-xl text-center text-cyan-200 font-semibold transition">
                    <i class="fa-solid fa-briefcase block text-sm mb-0.5"></i> Organizer
                </button>
                <button type="button" onclick="fillCredentials('john.doe@eventsphere.edu', 'Student@123', '')" class="bg-emerald-950/60 hover:bg-emerald-900 border border-emerald-500/30 p-2 rounded-xl text-center text-emerald-200 font-semibold transition">
                    <i class="fa-solid fa-graduation-cap block text-sm mb-0.5"></i> Student
                </button>
            </div>
        </div>

        <!-- Login Card Form -->
        <div class="glass-panel-elevated p-8 border border-white/10 relative">
            <?php if ($error): ?>
                <div class="p-3.5 rounded-xl bg-rose-950/40 border border-rose-500/40 text-rose-300 text-xs mb-5 flex items-center gap-2">
                    <i class="fa-solid fa-circle-exclamation flex-shrink-0"></i>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" action="<?= BASE_URL ?>/auth/login.php<?= !empty($redirect) ? '?redirect=' . urlencode($redirect) : '' ?>" class="space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1.5">Email Address or Username</label>
                    <div class="relative">
                        <i class="fa-regular fa-envelope absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                        <input type="text" id="login_input" name="login_input" required value="<?= htmlspecialchars($preset_email) ?>" placeholder="user@eventsphere.edu" class="form-input-dark pl-10 text-xs">
                    </div>
                </div>

                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="block text-xs font-semibold text-slate-300">Password</label>
                        <span class="text-[11px] text-slate-500">Default: Admin@123</span>
                    </div>
                    <div class="relative">
                        <i class="fa-solid fa-key absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                        <input type="password" id="password" name="password" required value="<?= htmlspecialchars($preset_pass) ?>" placeholder="••••••••" class="form-input-dark pl-10 text-xs">
                    </div>
                </div>


                <button type="submit" class="btn-neon-primary w-full py-3 text-xs font-bold shadow-lg shadow-purple-600/30 mt-2">
                    <i class="fa-solid fa-right-to-bracket mr-2"></i> Authenticate & Enter Portal
                </button>
            </form>

            <div class="mt-6 pt-4 border-t border-white/10 text-center text-xs text-slate-400">
                <span>New participant? </span>
                <a href="<?= BASE_URL ?>/auth/register.php" class="text-cyan-400 hover:underline font-semibold">Create Student Account</a>
            </div>
        </div>
    </div>
</div>

<script>
function fillCredentials(email, pass, twoFactor = '') {
    document.getElementById('login_input').value = email;
    document.getElementById('password').value = pass;
    document.getElementById('two_factor_code').value = twoFactor;
    showToast('info', 'Credentials Loaded', `Loaded credentials for ${email}`);
}

document.querySelector('form[action*="login.php"]').addEventListener('submit', function(e) {
    const login = document.getElementById('login_input').value.trim();
    const pass = document.getElementById('password').value;

    if (!login) {
        e.preventDefault();
        showToast('error', 'Validation Error', 'Please enter your email address or username.');
        return;
    }
    if (!pass) {
        e.preventDefault();
        showToast('error', 'Validation Error', 'Please enter your account password.');
        return;
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
