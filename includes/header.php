<?php
// includes/header.php - Dark 3D Responsive Navigation & Layout Header
require_once __DIR__ . '/../config/config.php';


$current_user = current_user();
$user_role = user_role();
$db = getDB();

$current_page_name = basename($_SERVER["PHP_SELF"]);
function get_nav_class($page_name, $current_page_name, $extra = "") {
    $base = "px-3 py-2 rounded-lg transition " . $extra . " ";
    if ($page_name === $current_page_name) {
        return $base . "text-white bg-white/10 border border-white/10 font-bold shadow-[inset_0_1px_0_rgba(255,255,255,0.1)]";
    }
    return $base . "hover:text-white hover:bg-white/5";
}


// Fetch active ticker announcements
$ticker_announcements = [];
try {
    $stmt = $db->query("SELECT title, content FROM announcements WHERE is_active = 1 ORDER BY id DESC LIMIT 5");
    $ticker_announcements = $stmt->fetchAll();
} catch (Exception $e) {}

// Fetch unread notifications count for logged-in user
$unread_notif_count = 0;
$latest_notifications = [];
if ($current_user) {
    try {
        $stmt = $db->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
        $stmt->execute([$current_user['id']]);
        $unread_notif_count = (int)$stmt->fetchColumn();

        $stmt2 = $db->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
        $stmt2->execute([$current_user['id']]);
        $latest_notifications = $stmt2->fetchAll();
    } catch (Exception $e) {}
}

$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? htmlspecialchars($page_title) . ' | ' : '' ?><?= SITE_NAME ?></title>
    
    <!-- Tailwind CSS (CDN for utility speed) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        dark: {
                            900: '#070913',
                            800: '#0f172a',
                            700: '#1e293b',
                            600: '#334155'
                        },
                        neon: {
                            purple: '#a855f7',
                            cyan: '#06b6d4',
                            emerald: '#10b981',
                            rose: '#f43f5e',
                            amber: '#f59e0b'
                        }
                    },
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'sans-serif'],
                        heading: ['"Space Grotesk"', 'sans-serif']
                    }
                }
            }
        }
    </script>

    <!-- FontAwesome Pro 6 Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Three.js (for 3D hero background) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>

    <!-- Vanilla Tilt (for 3D card tilt) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/vanilla-tilt/1.8.0/vanilla-tilt.min.js"></script>

    <!-- HTML5 QR Code Scanner Library -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html5-qrcode/2.3.8/html5-qrcode.min.js"></script>

    <!-- Custom Dark 3D Styles -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">

    <script>
        const BASE_URL = "<?= BASE_URL ?>";
    </script>
</head>
<body class="bg-dark-900 text-slate-100 flex flex-col min-h-screen">

<!-- Top Role Demo Quick Switcher Banner (SRS Evaluation Helper) -->
<div class="bg-gradient-to-r from-purple-950 via-slate-900 to-cyan-950 border-b border-white/10 px-4 py-1.5 text-xs text-slate-300 flex flex-wrap items-center justify-between gap-2 z-50">
    <div class="flex items-center gap-2">
        <span class="flex h-2 w-2 relative">
            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
            <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
        </span>
        <span class="font-medium text-slate-200">Current Persona:</span>
        <?php if (!$current_user): ?>
            <span class="bg-slate-800 text-cyan-400 px-2 py-0.5 rounded font-semibold border border-cyan-500/30">
                <i class="fa-solid fa-user-astronaut mr-1"></i> Visitor / Normal Student
            </span>
        <?php else: ?>
            <span class="bg-purple-900/50 text-purple-300 px-2 py-0.5 rounded font-semibold border border-purple-500/30">
                <i class="fa-solid fa-id-badge mr-1"></i> <?= ucfirst($current_user['role']) ?>: <?= htmlspecialchars($current_user['name']) ?>
            </span>
        <?php endif; ?>
    </div>
    <div class="flex items-center gap-3 text-[11px]">
        <span class="text-slate-400">Quick Switch Demo:</span>
        <a href="<?= BASE_URL ?>/auth/login.php?quick=admin" class="hover:text-purple-300 transition underline">Admin</a> •
        <a href="<?= BASE_URL ?>/auth/login.php?quick=organizer" class="hover:text-cyan-300 transition underline">Organizer</a> •
        <a href="<?= BASE_URL ?>/auth/login.php?quick=student" class="hover:text-emerald-300 transition underline">Student</a> •
        <?php if ($current_user): ?>
            <a href="<?= BASE_URL ?>/auth/logout.php" class="hover:text-rose-400 transition underline text-rose-300"><i class="fa-solid fa-arrow-right-from-bracket mr-1"></i>Sign Out</a>
        <?php else: ?>
            <a href="<?= BASE_URL ?>/auth/login.php" class="hover:text-white transition font-semibold text-cyan-400">Sign In</a>
        <?php endif; ?>
    </div>
</div>

<!-- Live Announcement Marquee Ticker -->
<?php if (!empty($ticker_announcements)): ?>
<div class="ticker-wrap">
    <div class="flex items-center gap-2 px-4 text-xs font-bold text-cyan-400 flex-shrink-0 bg-dark-900/90 z-10 border-r border-white/10 pr-3">
        <i class="fa-solid fa-bullhorn animate-bounce"></i> LIVE UPDATES:
    </div>
    <div class="ticker-move text-xs text-slate-300 flex items-center gap-8">
        <?php foreach ($ticker_announcements as $ann): ?>
            <span class="flex items-center gap-2">
                <span class="font-bold text-white"><?= htmlspecialchars($ann['title']) ?></span> - 
                <span><?= htmlspecialchars($ann['content']) ?></span>
                <span class="text-purple-400 mx-3">✦</span>
            </span>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Main Glassmorphism Navbar -->
<header class="sticky top-0 z-40 bg-dark-900/80 backdrop-blur-xl border-b border-white/10 shadow-2xl transition-all">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-20">
            <!-- Brand Logo with 3D Hologram Glow -->
            <a href="<?= BASE_URL ?>/index.php" class="flex items-center gap-3 group">
                <div class="w-11 h-11 rounded-xl bg-gradient-to-tr from-purple-600 via-indigo-600 to-cyan-400 flex items-center justify-center text-white shadow-lg shadow-purple-500/30 group-hover:scale-105 group-hover:rotate-6 transition-transform duration-300">
                    <i class="fa-solid fa-cube text-xl"></i>
                </div>
                <div>
                    <div class="font-heading font-extrabold text-2xl tracking-tight bg-gradient-to-r from-white via-slate-100 to-cyan-300 bg-clip-text text-transparent">
                        Event<span class="text-purple-400">Sphere</span>
                    </div>
                    <div class="text-[10px] uppercase tracking-widest text-cyan-400 font-semibold -mt-1">
                        Campus 3D Ecosystem
                    </div>
                </div>
            </a>

            <!-- Desktop Navigation Links -->
            <nav class="hidden md:flex items-center gap-1 font-medium text-sm text-slate-300">
                <a href="<?= BASE_URL ?>/index.php" class="<?= get_nav_class("index.php", $current_page_name, "flex items-center gap-1.5") ?>">
                    <i class="fa-solid fa-house text-xs text-purple-400"></i> Home
                </a>
                <a href="<?= BASE_URL ?>/events.php" class="<?= get_nav_class("events.php", $current_page_name, "flex items-center gap-1.5") ?>">
                    <i class="fa-solid fa-calendar-days text-xs text-cyan-400"></i> All Events
                </a>
                <a href="<?= BASE_URL ?>/gallery.php" class="<?= get_nav_class("gallery.php", $current_page_name, "flex items-center gap-1.5") ?>">
                    <i class="fa-solid fa-images text-xs text-emerald-400"></i> Media Gallery
                </a>
                <a href="<?= BASE_URL ?>/about.php" class="<?= get_nav_class("about.php", $current_page_name, "") ?>">
                    About Us
                </a>
                <a href="<?= BASE_URL ?>/contact.php" class="<?= get_nav_class("contact.php", $current_page_name, "") ?>">
                    Contact
                </a>
                <a href="<?= BASE_URL ?>/faqs.php" class="<?= get_nav_class("faqs.php", $current_page_name, "") ?>">
                    FAQs
                </a>
            </nav>

            <!-- Right Actions: Notifications & User Auth / Profile -->
            <div class="flex items-center gap-3">
                <?php if ($current_user): ?>
                    <!-- Notification Bell Dropdown -->
                    <div class="relative" id="notifDropdownContainer">
                        <button onclick="toggleNotifDropdown()" class="w-10 h-10 rounded-xl bg-slate-800/80 hover:bg-slate-700/80 border border-white/10 flex items-center justify-center text-slate-300 hover:text-white transition relative">
                            <i class="fa-regular fa-bell"></i>
                            <?php if ($unread_notif_count > 0): ?>
                                <span class="absolute -top-1 -right-1 bg-rose-500 text-white text-[10px] font-bold w-5 h-5 rounded-full flex items-center justify-center border-2 border-dark-900 animate-pulse">
                                    <?= $unread_notif_count ?>
                                </span>
                            <?php endif; ?>
                        </button>
                        
                        <!-- Notifications Popup -->
                        <div id="notifDropdown" class="hidden absolute right-0 mt-3 w-80 sm:w-96 bg-dark-800 border border-white/10 rounded-2xl shadow-2xl backdrop-blur-2xl z-50 p-4">
                            <div class="flex items-center justify-between pb-3 border-b border-white/10">
                                <h4 class="font-bold text-sm text-white flex items-center gap-2">
                                    <i class="fa-solid fa-bell text-purple-400"></i> Notifications
                                </h4>
                                <span class="text-xs text-slate-400"><?= $unread_notif_count ?> unread</span>
                            </div>
                            <div class="divide-y divide-white/5 max-h-72 overflow-y-auto mt-2">
                                <?php if (empty($latest_notifications)): ?>
                                    <div class="py-6 text-center text-slate-400 text-xs">
                                        No recent notifications.
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($latest_notifications as $notif): 
                                        $notif_link = str_starts_with($notif['link'], 'http') ? $notif['link'] : BASE_URL . '/' . ltrim($notif['link'], '/');
                                    ?>
                                        <a href="<?= htmlspecialchars($notif_link) ?>" class="block py-3 hover:bg-white/5 px-2 rounded-lg transition <?= !$notif['is_read'] ? 'bg-purple-950/20' : '' ?>">
                                            <div class="flex items-start gap-2.5">
                                                <div class="w-2 h-2 rounded-full mt-1.5 <?= !$notif['is_read'] ? 'bg-cyan-400' : 'bg-slate-600' ?>"></div>
                                                <div class="flex-1">
                                                    <div class="font-semibold text-xs text-slate-200"><?= htmlspecialchars($notif['title']) ?></div>
                                                    <div class="text-[11px] text-slate-400 mt-0.5 line-clamp-2"><?= htmlspecialchars($notif['message']) ?></div>
                                                    <div class="text-[10px] text-slate-500 mt-1"><?= time_ago($notif['created_at']) ?></div>
                                                </div>
                                            </div>
                                        </a>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- User Profile Dropdown / Dashboard Button -->
                    <div class="relative" id="userMenuContainer">
                        <button onclick="toggleUserMenu()" class="flex items-center gap-2.5 bg-slate-800/80 hover:bg-slate-700/80 border border-white/10 px-3 py-1.5 rounded-xl transition">
                            <img src="<?= htmlspecialchars(!empty($current_user['avatar']) ? $current_user['avatar'] : 'https://ui-avatars.com/api/?name='.urlencode($current_user['name']).'&background=0D8ABC&color=fff') ?>" class="w-7 h-7 rounded-lg object-cover border border-purple-400/50">
                            <span class="text-sm font-semibold text-slate-200 hidden sm:inline"><?= htmlspecialchars(explode(' ', $current_user['name'])[0]) ?></span>
                            <i class="fa-solid fa-chevron-down text-[10px] text-slate-400"></i>
                        </button>

                        <!-- Menu Dropdown -->
                        <div id="userMenuDropdown" class="hidden absolute right-0 mt-3 w-60 bg-dark-800 border border-white/10 rounded-2xl shadow-2xl backdrop-blur-2xl z-50 p-2 text-sm">
                            <div class="px-3 py-2.5 border-b border-white/10 mb-1">
                                <p class="font-bold text-white"><?= htmlspecialchars($current_user['name']) ?></p>
                                <p class="text-xs text-cyan-400 capitalize"><?= $current_user['role'] ?> • <?= htmlspecialchars($current_user['department']) ?></p>
                            </div>

                            <?php if ($user_role === 'student'): ?>
                                <a href="<?= BASE_URL ?>/student/dashboard.php" class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-slate-300 hover:text-white hover:bg-white/5 transition">
                                    <i class="fa-solid fa-chart-pie text-purple-400 w-4"></i> Student Dashboard
                                </a>
                                <a href="<?= BASE_URL ?>/student/my_events.php" class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-slate-300 hover:text-white hover:bg-white/5 transition">
                                    <i class="fa-solid fa-ticket text-cyan-400 w-4"></i> My Event Passes
                                </a>
                                <a href="<?= BASE_URL ?>/student/certificates.php" class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-slate-300 hover:text-white hover:bg-white/5 transition">
                                    <i class="fa-solid fa-award text-amber-400 w-4"></i> E-Certificates
                                </a>
                                <a href="<?= BASE_URL ?>/student/bookmarks.php" class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-slate-300 hover:text-white hover:bg-white/5 transition">
                                    <i class="fa-solid fa-bookmark text-rose-400 w-4"></i> Saved & Favorites
                                </a>
                                <a href="<?= BASE_URL ?>/student/profile.php" class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-slate-300 hover:text-white hover:bg-white/5 transition">
                                    <i class="fa-solid fa-user-gear text-emerald-400 w-4"></i> Profile Settings
                                </a>
                            <?php elseif ($user_role === 'organizer'): ?>
                                <a href="<?= BASE_URL ?>/organizer/dashboard.php" class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-slate-300 hover:text-white hover:bg-white/5 transition">
                                    <i class="fa-solid fa-gauge-high text-purple-400 w-4"></i> Organizer Dashboard
                                </a>
                                <a href="<?= BASE_URL ?>/organizer/create_event.php" class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-slate-300 hover:text-white hover:bg-white/5 transition">
                                    <i class="fa-solid fa-calendar-plus text-cyan-400 w-4"></i> Create New Event
                                </a>
                                <a href="<?= BASE_URL ?>/organizer/scan_qr.php" class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-slate-300 hover:text-white hover:bg-white/5 transition">
                                    <i class="fa-solid fa-qrcode text-emerald-400 w-4"></i> Live QR Scanner Pass
                                </a>
                                <a href="<?= BASE_URL ?>/organizer/manage_attendees.php" class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-slate-300 hover:text-white hover:bg-white/5 transition">
                                    <i class="fa-solid fa-users-gear text-amber-400 w-4"></i> Manage Registrations
                                </a>
                                <a href="<?= BASE_URL ?>/organizer/issue_certificates.php" class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-slate-300 hover:text-white hover:bg-white/5 transition">
                                    <i class="fa-solid fa-certificate text-purple-400 w-4"></i> Issue Certificates
                                </a>
                                <a href="<?= BASE_URL ?>/organizer/upload_gallery.php" class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-slate-300 hover:text-white hover:bg-white/5 transition">
                                    <i class="fa-solid fa-cloud-arrow-up text-rose-400 w-4"></i> Upload Media Gallery
                                </a>
                            <?php elseif ($user_role === 'admin'): ?>
                                <a href="<?= BASE_URL ?>/admin/dashboard.php" class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-slate-300 hover:text-white hover:bg-white/5 transition">
                                    <i class="fa-solid fa-shield-halved text-purple-400 w-4"></i> Admin Command Center
                                </a>
                                <a href="<?= BASE_URL ?>/admin/manage_events.php" class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-slate-300 hover:text-white hover:bg-white/5 transition">
                                    <i class="fa-solid fa-list-check text-cyan-400 w-4"></i> Event Approvals
                                </a>
                                <a href="<?= BASE_URL ?>/admin/venue_capacity.php" class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-slate-300 hover:text-white hover:bg-white/5 transition">
                                    <i class="fa-solid fa-chart-column text-emerald-400 w-4"></i> Dynamic Capacities
                                </a>
                                <a href="<?= BASE_URL ?>/admin/manage_users.php" class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-slate-300 hover:text-white hover:bg-white/5 transition">
                                    <i class="fa-solid fa-users text-amber-400 w-4"></i> User Management
                                </a>
                                <a href="<?= BASE_URL ?>/admin/moderate_content.php" class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-slate-300 hover:text-white hover:bg-white/5 transition">
                                    <i class="fa-solid fa-comments text-rose-400 w-4"></i> Content Moderation
                                </a>
                                <a href="<?= BASE_URL ?>/admin/reports.php" class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-slate-300 hover:text-white hover:bg-white/5 transition">
                                    <i class="fa-solid fa-file-invoice text-indigo-400 w-4"></i> Analytics & Reports
                                </a>
                            <?php endif; ?>

                            <div class="border-t border-white/10 my-1"></div>
                            <a href="<?= BASE_URL ?>/auth/logout.php" class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-rose-400 hover:bg-rose-500/10 transition font-semibold">
                                <i class="fa-solid fa-arrow-right-from-bracket w-4"></i> Sign Out
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="<?= BASE_URL ?>/auth/login.php" class="btn-glass text-xs sm:text-sm py-2 px-4">
                        <i class="fa-solid fa-right-to-bracket mr-1.5"></i> Sign In
                    </a>
                    <a href="<?= BASE_URL ?>/auth/register.php" class="btn-neon-primary text-xs sm:text-sm py-2 px-4">
                        <i class="fa-solid fa-user-plus mr-1.5"></i> Register
                    </a>
                <?php endif; ?>

                <!-- Mobile Menu Button -->
                <button onclick="toggleMobileMenu()" class="md:hidden w-10 h-10 rounded-xl bg-slate-800/80 border border-white/10 flex items-center justify-center text-slate-300 hover:text-white">
                    <i class="fa-solid fa-bars"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Mobile Drawer Navigation -->
        <div id="mobileMenu" class="hidden md:hidden bg-dark-900/95 border-b border-white/10 px-4 pt-2 pb-6 space-y-2 backdrop-blur-xl">
        <a href="<?= BASE_URL ?>/index.php" class="<?= get_nav_class("index.php", $current_page_name, "block") ?>">Home</a>
        <a href="<?= BASE_URL ?>/events.php" class="<?= get_nav_class("events.php", $current_page_name, "block") ?>">All Events</a>
        <a href="<?= BASE_URL ?>/gallery.php" class="<?= get_nav_class("gallery.php", $current_page_name, "block") ?>">Media Gallery</a>
        <a href="<?= BASE_URL ?>/about.php" class="<?= get_nav_class("about.php", $current_page_name, "block") ?>">About Us</a>
        <a href="<?= BASE_URL ?>/contact.php" class="<?= get_nav_class("contact.php", $current_page_name, "block") ?>">Contact</a>
        <a href="<?= BASE_URL ?>/faqs.php" class="<?= get_nav_class("faqs.php", $current_page_name, "block") ?>">FAQs</a>
    </div>
</header>

<!-- Flash Alerts Notification System -->
<?php if ($flash): ?>
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
    <div class="p-4 rounded-xl flex items-center justify-between border backdrop-blur-xl <?php
        if ($flash['type'] === 'success') echo 'bg-emerald-950/40 border-emerald-500/40 text-emerald-300';
        elseif ($flash['type'] === 'error') echo 'bg-rose-950/40 border-rose-500/40 text-rose-300';
        elseif ($flash['type'] === 'warning') echo 'bg-amber-950/40 border-amber-500/40 text-amber-300';
        else echo 'bg-cyan-950/40 border-cyan-500/40 text-cyan-300';
    ?>">
        <div class="flex items-center gap-3">
            <i class="fa-solid <?php
                if ($flash['type'] === 'success') echo 'fa-circle-check text-emerald-400';
                elseif ($flash['type'] === 'error') echo 'fa-circle-xmark text-rose-400';
                elseif ($flash['type'] === 'warning') echo 'fa-triangle-exclamation text-amber-400';
                else echo 'fa-circle-info text-cyan-400';
            ?> text-lg"></i>
            <span class="text-sm font-medium"><?= htmlspecialchars($flash['message']) ?></span>
        </div>
        <button onclick="this.parentElement.remove()" class="text-slate-400 hover:text-white">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>
</div>
<?php endif; ?>

<script>
function toggleNotifDropdown() {
    document.getElementById('notifDropdown')?.classList.toggle('hidden');
    document.getElementById('userMenuDropdown')?.classList.add('hidden');
}
function toggleUserMenu() {
    document.getElementById('userMenuDropdown')?.classList.toggle('hidden');
    document.getElementById('notifDropdown')?.classList.add('hidden');
}
function toggleMobileMenu() {
    document.getElementById('mobileMenu')?.classList.toggle('hidden');
}
// Close dropdowns on outside click
document.addEventListener('click', (e) => {
    if (!document.getElementById('notifDropdownContainer')?.contains(e.target)) {
        document.getElementById('notifDropdown')?.classList.add('hidden');
    }
    if (!document.getElementById('userMenuContainer')?.contains(e.target)) {
        document.getElementById('userMenuDropdown')?.classList.add('hidden');
    }
});
</script>

<main class="flex-grow">
