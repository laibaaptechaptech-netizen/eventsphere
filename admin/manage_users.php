<?php
// admin/manage_users.php - User Directory, Role Assignment & Account Security
$page_title = "User Management";
require_once __DIR__ . '/../config/auth_check.php';
require_admin();

$user = current_user();
$db = getDB();

// Handle User Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = clean_input($_POST['action'] ?? '');
    $target_user_id = (int)($_POST['user_id'] ?? 0);

    if ($target_user_id > 0 && $target_user_id !== $user['id']) {
        if ($action === 'change_role') {
            $new_role = clean_input($_POST['new_role'] ?? 'student');
            $stmt = $db->prepare("UPDATE users SET role = ? WHERE id = ?");
            $stmt->execute([$new_role, $target_user_id]);
            set_flash('success', 'User role updated to ' . strtoupper($new_role) . '.');
        } elseif ($action === 'toggle_status') {
            $stmt = $db->prepare("UPDATE users SET status = IF(status = 'active', 'suspended', 'active') WHERE id = ?");
            $stmt->execute([$target_user_id]);
            set_flash('info', 'User active status toggled.');
        } elseif ($action === 'reset_password') {
            $new_hash = password_hash('Student@123', PASSWORD_BCRYPT);
            $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$new_hash, $target_user_id]);
            set_flash('success', 'Password reset to default (Student@123).');
        } elseif ($action === 'delete_user') {
            $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$target_user_id]);
            set_flash('warning', 'User account permanently deleted.');
        }
    }
    header("Location: " . BASE_URL . "/admin/manage_users.php");
    exit;
}

// Fetch All Users
$role_filter = clean_input($_GET['role'] ?? '');
$search_query = clean_input($_GET['q'] ?? '');

$sql = "SELECT * FROM users WHERE 1=1";
$params = [];

if (!empty($role_filter)) {
    $sql .= " AND role = ?";
    $params[] = $role_filter;
}
if (!empty($search_query)) {
    $sql .= " AND (name LIKE ? OR email LIKE ? OR username LIKE ? OR department LIKE ?)";
    $term = "%$search_query%";
    $params[] = $term; $params[] = $term; $params[] = $term; $params[] = $term;
}

$sql .= " ORDER BY created_at DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="badge-neon badge-purple mb-2">User Governance</div>
            <h1 class="font-heading font-extrabold text-3xl text-white">Manage System Users</h1>
            <p class="text-slate-400 text-xs mt-1">Assign roles (upgrade students to organizers), suspend inactive accounts, and manage credentials.</p>
        </div>
        <div class="text-xs text-slate-400 bg-slate-800/80 px-4 py-2 rounded-xl border border-white/10">
            Total Users: <span class="font-bold text-cyan-400"><?= count($users) ?></span>
        </div>
    </div>

    <!-- Filter & Search Bar -->
    <div class="glass-panel p-4 mb-8 border border-white/10">
        <form method="GET" action="<?= BASE_URL ?>/admin/manage_users.php" class="flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-2 flex-1 min-w-[240px]">
                <i class="fa-solid fa-magnifying-glass text-slate-400 text-xs"></i>
                <input type="text" name="q" value="<?= htmlspecialchars($search_query) ?>" placeholder="Search by name, email, department, username..." class="form-input-dark text-xs py-2">
            </div>

            <div class="flex items-center gap-3">
                <select name="role" onchange="this.form.submit()" class="form-input-dark text-xs py-2 px-3 w-auto">
                    <option value="">All Roles</option>
                    <option value="student" <?= $role_filter === 'student' ? 'selected' : '' ?>>Students</option>
                    <option value="organizer" <?= $role_filter === 'organizer' ? 'selected' : '' ?>>College Organizers</option>
                    <option value="admin" <?= $role_filter === 'admin' ? 'selected' : '' ?>>Administrators</option>
                </select>
                <button type="submit" class="btn-neon-primary text-xs py-2 px-4">Search</button>
            </div>
        </form>
    </div>

    <!-- Users Table -->
    <div class="glass-panel-elevated p-6 border border-white/10">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="text-slate-400 border-b border-white/10 uppercase tracking-wider text-[10px]">
                        <th class="py-3 px-4">User Details</th>
                        <th class="py-3 px-4">Role</th>
                        <th class="py-3 px-4">Department & Enrolment</th>
                        <th class="py-3 px-4">Status</th>
                        <th class="py-3 px-4">Registered</th>
                        <th class="py-3 px-4 text-right">Governance Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5 text-slate-300">
                    <?php foreach ($users as $u): ?>
                        <tr class="hover:bg-white/5 transition">
                            <td class="py-3.5 px-4">
                                <div class="flex items-center gap-3">
                                    <img src="<?= htmlspecialchars(!empty($u['avatar']) ? $u['avatar'] : 'https://ui-avatars.com/api/?name='.urlencode($u['name']).'&background=0D8ABC&color=fff') ?>" class="w-8 h-8 rounded-lg object-cover border border-purple-500/40">
                                    <div>
                                        <div class="font-bold text-white"><?= htmlspecialchars($u['name']) ?></div>
                                        <div class="text-[10px] text-slate-400"><?= htmlspecialchars($u['email']) ?> • @<?= htmlspecialchars($u['username']) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td class="py-3.5 px-4">
                                <form method="POST" action="<?= BASE_URL ?>/admin/manage_users.php" class="inline">
                                    <input type="hidden" name="action" value="change_role">
                                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                    <select name="new_role" onchange="this.form.submit()" class="form-input-dark text-[11px] py-1 px-2 rounded font-semibold capitalize <?= $u['role'] === 'admin' ? 'text-purple-300' : ($u['role'] === 'organizer' ? 'text-cyan-300' : 'text-emerald-300') ?>">
                                        <option value="student" <?= $u['role'] === 'student' ? 'selected' : '' ?>>Student</option>
                                        <option value="organizer" <?= $u['role'] === 'organizer' ? 'selected' : '' ?>>Organizer</option>
                                        <option value="admin" <?= $u['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                                    </select>
                                </form>
                            </td>
                            <td class="py-3.5 px-4">
                                <div><?= htmlspecialchars($u['department']) ?></div>
                                <div class="text-[10px] text-slate-400 font-mono"><?= htmlspecialchars($u['enrolment_no'] ?? 'N/A') ?></div>
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="badge-neon badge-<?= $u['status'] === 'active' ? 'emerald' : 'rose' ?> text-[10px]">
                                    <?= strtoupper($u['status']) ?>
                                </span>
                            </td>
                            <td class="py-3.5 px-4 text-[11px] text-slate-400">
                                <?= date('M d, Y', strtotime($u['created_at'])) ?>
                            </td>
                            <td class="py-3.5 px-4 text-right space-x-1.5 whitespace-nowrap">
                                <?php if ($u['id'] !== $user['id']): ?>
                                    <form method="POST" action="<?= BASE_URL ?>/admin/manage_users.php" class="inline">
                                        <input type="hidden" name="action" value="toggle_status">
                                        <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                        <button type="submit" class="btn-glass py-1 px-2 text-[10px] <?= $u['status'] === 'active' ? 'text-amber-400' : 'text-emerald-400' ?>" title="Suspend/Activate User">
                                            <i class="fa-solid <?= $u['status'] === 'active' ? 'fa-ban' : 'fa-check' ?>"></i>
                                        </button>
                                    </form>

                                    <form method="POST" action="<?= BASE_URL ?>/admin/manage_users.php" onsubmit="return confirm('Reset password for <?= htmlspecialchars(addslashes($u['name'])) ?> to default (Student@123)?');" class="inline">
                                        <input type="hidden" name="action" value="reset_password">
                                        <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                        <button type="submit" class="btn-glass py-1 px-2 text-[10px] text-cyan-400" title="Reset Password">
                                            <i class="fa-solid fa-key"></i>
                                        </button>
                                    </form>

                                    <form method="POST" action="<?= BASE_URL ?>/admin/manage_users.php" onsubmit="return confirm('Permanently delete user account <?= htmlspecialchars(addslashes($u['name'])) ?>?');" class="inline">
                                        <input type="hidden" name="action" value="delete_user">
                                        <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                        <button type="submit" class="text-rose-400 hover:text-rose-300 p-1 text-xs" title="Delete User">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <span class="text-[10px] text-purple-400 font-semibold">Current Account</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
