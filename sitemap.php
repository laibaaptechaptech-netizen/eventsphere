<?php
// sitemap.php - Interactive Visual 3D Platform Architecture & Sitemap (SRS Req 10)
$page_title = "Platform Sitemap & Navigation Flow";
require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <!-- Header -->
    <div class="text-center max-w-3xl mx-auto mb-16">
        <div class="badge-neon badge-amber mb-3">SRS Requirement 10</div>
        <h1 class="font-heading font-extrabold text-3xl sm:text-5xl text-white mb-4">
            EventSphere Visual <span class="text-gradient-gold">Sitemap</span> & Architecture
        </h1>
        <p class="text-slate-300 text-sm sm:text-base leading-relaxed">
            A comprehensive visual map detailing the complete navigation flow, role-based access hierarchy, and system module routing across all user tiers.
        </p>
    </div>

    <!-- 4 Role Quadrants Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-16">
        <!-- Tier 1: Normal Student (Visitor) -->
        <div class="glass-panel-elevated p-6 sm:p-8 border border-cyan-500/30 space-y-4 relative overflow-hidden">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-cyan-500/10 border border-cyan-500/30 text-cyan-400 flex items-center justify-center text-lg">
                        <i class="fa-solid fa-user-astronaut"></i>
                    </div>
                    <div>
                        <h3 class="font-heading font-bold text-lg text-white">1. Normal Student (Visitor)</h3>
                        <p class="text-xs text-cyan-400 font-semibold">Public Unrestricted Tier</p>
                    </div>
                </div>
                <span class="badge-neon badge-cyan">Guest Access</span>
            </div>
            
            <p class="text-slate-400 text-xs leading-relaxed">
                Non-authenticated students exploring campus life with access control prompts on restricted operations.
            </p>

            <ul class="space-y-2 text-xs text-slate-300 pt-2 border-t border-white/5">
                <li class="flex items-center gap-2">
                    <i class="fa-solid fa-link text-cyan-400"></i>
                    <a href="<?= BASE_URL ?>/index.php" class="hover:underline font-semibold">Home Page (3D Hero, Marquee, Stats)</a>
                </li>
                <li class="flex items-center gap-2">
                    <i class="fa-solid fa-link text-cyan-400"></i>
                    <a href="<?= BASE_URL ?>/events.php" class="hover:underline">All Events Catalog (Multi-Filters & Search)</a>
                </li>
                <li class="flex items-center gap-2">
                    <i class="fa-solid fa-link text-cyan-400"></i>
                    <a href="<?= BASE_URL ?>/event_detail.php?id=1" class="hover:underline">Event Detail & Live Slot Gauge</a>
                </li>
                <li class="flex items-center gap-2">
                    <i class="fa-solid fa-link text-cyan-400"></i>
                    <a href="<?= BASE_URL ?>/gallery.php" class="hover:underline">Categorized Media Gallery & Lightboxes</a>
                </li>
                <li class="flex items-center gap-2">
                    <i class="fa-solid fa-link text-cyan-400"></i>
                    <a href="<?= BASE_URL ?>/about.php" class="hover:underline">About Us & Organizing Council</a>
                </li>
                <li class="flex items-center gap-2">
                    <i class="fa-solid fa-link text-cyan-400"></i>
                    <a href="<?= BASE_URL ?>/contact.php" class="hover:underline">Contact Desk & Inquiry Form</a>
                </li>
                <li class="flex items-center gap-2">
                    <i class="fa-solid fa-link text-cyan-400"></i>
                    <a href="<?= BASE_URL ?>/faqs.php" class="hover:underline">Searchable Campus FAQs</a>
                </li>
            </ul>
        </div>

        <!-- Tier 2: Participant (Registered Student) -->
        <div class="glass-panel-elevated p-6 sm:p-8 border border-emerald-500/30 space-y-4 relative overflow-hidden">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 flex items-center justify-center text-lg">
                        <i class="fa-solid fa-graduation-cap"></i>
                    </div>
                    <div>
                        <h3 class="font-heading font-bold text-lg text-white">2. Participant (Registered Student)</h3>
                        <p class="text-xs text-emerald-400 font-semibold">Student Portal Tier</p>
                    </div>
                </div>
                <span class="badge-neon badge-emerald">Student Role</span>
            </div>
            
            <p class="text-slate-400 text-xs leading-relaxed">
                Active students registered for events, ticket pass holders, and feedback contributors.
            </p>

            <ul class="space-y-2 text-xs text-slate-300 pt-2 border-t border-white/5">
                <li class="flex items-center gap-2">
                    <i class="fa-solid fa-link text-emerald-400"></i>
                    <a href="<?= BASE_URL ?>/student/dashboard.php" class="hover:underline font-semibold">Student Hub (Metrics & QR Passes)</a>
                </li>
                <li class="flex items-center gap-2">
                    <i class="fa-solid fa-link text-emerald-400"></i>
                    <a href="<?= BASE_URL ?>/student/my_events.php" class="hover:underline">My Registrations & Auto-Waitlist Cancel</a>
                </li>
                <li class="flex items-center gap-2">
                    <i class="fa-solid fa-link text-emerald-400"></i>
                    <a href="<?= BASE_URL ?>/student/certificates.php" class="hover:underline">Verifiable E-Certificates & Fee Payment</a>
                </li>
                <li class="flex items-center gap-2">
                    <i class="fa-solid fa-link text-emerald-400"></i>
                    <a href="<?= BASE_URL ?>/student/feedback.php" class="hover:underline">Multi-Component Star Rating & Feedback</a>
                </li>
                <li class="flex items-center gap-2">
                    <i class="fa-solid fa-link text-emerald-400"></i>
                    <a href="<?= BASE_URL ?>/student/bookmarks.php" class="hover:underline">Bookmarks & Favorite Saved Media</a>
                </li>
                <li class="flex items-center gap-2">
                    <i class="fa-solid fa-link text-emerald-400"></i>
                    <a href="<?= BASE_URL ?>/student/profile.php" class="hover:underline">Profile Details & Password Security</a>
                </li>
            </ul>
        </div>

        <!-- Tier 3: Organizer (College Staff) -->
        <div class="glass-panel-elevated p-6 sm:p-8 border border-purple-500/30 space-y-4 relative overflow-hidden">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-purple-500/10 border border-purple-500/30 text-purple-400 flex items-center justify-center text-lg">
                        <i class="fa-solid fa-briefcase"></i>
                    </div>
                    <div>
                        <h3 class="font-heading font-bold text-lg text-white">3. Organizer (College Staff)</h3>
                        <p class="text-xs text-purple-400 font-semibold">Faculty & Staff Portal Tier</p>
                    </div>
                </div>
                <span class="badge-neon badge-purple">Organizer Role</span>
            </div>
            
            <p class="text-slate-400 text-xs leading-relaxed">
                Department conveners and event managers handling event creation, check-ins, and certificates.
            </p>

            <ul class="space-y-2 text-xs text-slate-300 pt-2 border-t border-white/5">
                <li class="flex items-center gap-2">
                    <i class="fa-solid fa-link text-purple-400"></i>
                    <a href="<?= BASE_URL ?>/organizer/dashboard.php" class="hover:underline font-semibold">Organizer Dashboard & Analytics</a>
                </li>
                <li class="flex items-center gap-2">
                    <i class="fa-solid fa-link text-purple-400"></i>
                    <a href="<?= BASE_URL ?>/organizer/create_event.php" class="hover:underline">Create Event ('Pending Approval' Workflow)</a>
                </li>
                <li class="flex items-center gap-2">
                    <i class="fa-solid fa-link text-purple-400"></i>
                    <a href="<?= BASE_URL ?>/organizer/scan_qr.php" class="hover:underline">Live In-Browser QR Scanner Attendance Pass</a>
                </li>
                <li class="flex items-center gap-2">
                    <i class="fa-solid fa-link text-purple-400"></i>
                    <a href="<?= BASE_URL ?>/organizer/manage_attendees.php" class="hover:underline">Manage Registrations & Export CSV</a>
                </li>
                <li class="flex items-center gap-2">
                    <i class="fa-solid fa-link text-purple-400"></i>
                    <a href="<?= BASE_URL ?>/organizer/issue_certificates.php" class="hover:underline">Issue Official E-Certificates</a>
                </li>
                <li class="flex items-center gap-2">
                    <i class="fa-solid fa-link text-purple-400"></i>
                    <a href="<?= BASE_URL ?>/organizer/upload_gallery.php" class="hover:underline">Upload High-Res Media to Gallery</a>
                </li>
                <li class="flex items-center gap-2">
                    <i class="fa-solid fa-link text-purple-400"></i>
                    <a href="<?= BASE_URL ?>/organizer/announcements.php" class="hover:underline">Broadcast Messages to Registrants</a>
                </li>
            </ul>
        </div>

        <!-- Tier 4: Admin (System Administrator) -->
        <div class="glass-panel-elevated p-6 sm:p-8 border border-amber-500/30 space-y-4 relative overflow-hidden">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-amber-500/10 border border-amber-500/30 text-amber-400 flex items-center justify-center text-lg">
                        <i class="fa-solid fa-shield-halved"></i>
                    </div>
                    <div>
                        <h3 class="font-heading font-bold text-lg text-white">4. Admin (System Administrator)</h3>
                        <p class="text-xs text-amber-400 font-semibold">Elevated 2FA Command Tier</p>
                    </div>
                </div>
                <span class="badge-neon badge-amber">Admin Role</span>
            </div>
            
            <p class="text-slate-400 text-xs leading-relaxed">
                Central governance, event approvals, dynamic venue capacity enforcement, and report exports.
            </p>

            <ul class="space-y-2 text-xs text-slate-300 pt-2 border-t border-white/5">
                <li class="flex items-center gap-2">
                    <i class="fa-solid fa-link text-amber-400"></i>
                    <a href="<?= BASE_URL ?>/admin/dashboard.php" class="hover:underline font-semibold">Admin Command Center (KPIs & Logs)</a>
                </li>
                <li class="flex items-center gap-2">
                    <i class="fa-solid fa-link text-amber-400"></i>
                    <a href="<?= BASE_URL ?>/admin/manage_events.php" class="hover:underline">Event Proposals (Approve / Reject / Changes)</a>
                </li>
                <li class="flex items-center gap-2">
                    <i class="fa-solid fa-link text-amber-400"></i>
                    <a href="<?= BASE_URL ?>/admin/venue_capacity.php" class="hover:underline">Dynamic Venue Capacity & Seating Limits</a>
                </li>
                <li class="flex items-center gap-2">
                    <i class="fa-solid fa-link text-amber-400"></i>
                    <a href="<?= BASE_URL ?>/admin/manage_users.php" class="hover:underline">User Management, Role Upgrades & Password Reset</a>
                </li>
                <li class="flex items-center gap-2">
                    <i class="fa-solid fa-link text-amber-400"></i>
                    <a href="<?= BASE_URL ?>/admin/moderate_content.php" class="hover:underline">Content & Review Moderation Desk</a>
                </li>
                <li class="flex items-center gap-2">
                    <i class="fa-solid fa-link text-amber-400"></i>
                    <a href="<?= BASE_URL ?>/admin/broadcast.php" class="hover:underline">System-Wide Broadcast Alerts</a>
                </li>
                <li class="flex items-center gap-2">
                    <i class="fa-solid fa-link text-amber-400"></i>
                    <a href="<?= BASE_URL ?>/admin/reports.php" class="hover:underline">Analytics Reports Generator (CSV / PDF)</a>
                </li>
            </ul>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
