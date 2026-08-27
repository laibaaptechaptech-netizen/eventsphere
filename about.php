<?php
// about.php - About EventSphere Campus Platform & Organizing Leadership
$page_title = "About EventSphere";
require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <!-- Hero Section -->
    <div class="text-center max-w-3xl mx-auto mb-16">
        <div class="badge-neon badge-purple mb-3">Campus Evolution</div>
        <h1 class="font-heading font-extrabold text-4xl sm:text-5xl text-white mb-4">
            Empowering Campus Life Through <span class="text-gradient-purple">Next-Gen Tech</span>
        </h1>
        <p class="text-slate-300 text-base sm:text-lg leading-relaxed">
            EventSphere is the unified digital backbone of the university, transforming how over 10,000+ students, faculty members, and academic departments discover, organize, and experience collegiate excellence.
        </p>
    </div>

    <!-- 4 Core Pillars Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-20">
        <div class="glass-card-interactive tilt-card p-6 border border-cyan-500/20">
            <div class="w-12 h-12 rounded-xl bg-cyan-500/10 border border-cyan-500/30 text-cyan-400 flex items-center justify-center text-xl mb-4">
                <i class="fa-solid fa-chart-pie"></i>
            </div>
            <h3 class="font-heading font-bold text-lg text-white mb-2">Dynamic Capacity</h3>
            <p class="text-slate-400 text-xs leading-relaxed">
                Automated seat capacity enforcement, real-time live availability gauges, and intelligent waitlist auto-promotion engines.
            </p>
        </div>

        <div class="glass-card-interactive tilt-card p-6 border border-purple-500/20">
            <div class="w-12 h-12 rounded-xl bg-purple-500/10 border border-purple-500/30 text-purple-400 flex items-center justify-center text-xl mb-4">
                <i class="fa-solid fa-qrcode"></i>
            </div>
            <h3 class="font-heading font-bold text-lg text-white mb-2">Instant QR Check-ins</h3>
            <p class="text-slate-400 text-xs leading-relaxed">
                Cryptographic digital entry passes scanned in sub-seconds via browser webcam for high-throughput venue check-in.
            </p>
        </div>

        <div class="glass-card-interactive tilt-card p-6 border border-amber-500/20">
            <div class="w-12 h-12 rounded-xl bg-amber-500/10 border border-amber-500/30 text-amber-400 flex items-center justify-center text-xl mb-4">
                <i class="fa-solid fa-award"></i>
            </div>
            <h3 class="font-heading font-bold text-lg text-white mb-2">Verifiable E-Certificates</h3>
            <p class="text-slate-400 text-xs leading-relaxed">
                Automated attendance-verified digital certificates with tamper-proof cryptographic IDs and instant high-res downloads.
            </p>
        </div>

        <div class="glass-card-interactive tilt-card p-6 border border-emerald-500/20">
            <div class="w-12 h-12 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 flex items-center justify-center text-xl mb-4">
                <i class="fa-solid fa-users-viewfinder"></i>
            </div>
            <h3 class="font-heading font-bold text-lg text-white mb-2">4-Tier Governance</h3>
            <p class="text-slate-400 text-xs leading-relaxed">
                Seamless role separation across Normal Visitors, Registered Participants, Staff Organizers, and System Administrators.
            </p>
        </div>
    </div>

    <!-- Leadership & Organizing Deans -->
    <div class="mb-20">
        <div class="text-center max-w-2xl mx-auto mb-12">
            <div class="badge-neon badge-cyan mb-2">Organizing Council</div>
            <h2 class="font-heading font-bold text-3xl text-white mb-2">Academic & Faculty Leadership</h2>
            <p class="text-slate-400 text-sm">The visionary mentors driving institutional innovation across all campus departments.</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
            <div class="glass-card-interactive p-6 text-center border border-white/10">
                <img src="https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=200&q=80" class="w-24 h-24 rounded-2xl object-cover mx-auto mb-4 border-2 border-purple-500/50 shadow-lg shadow-purple-500/20">
                <h4 class="font-heading font-bold text-lg text-white">System Administrator</h4>
                <div class="text-xs text-purple-400 font-semibold mb-2">Dean of Digital Operations</div>
                <p class="text-slate-400 text-xs leading-relaxed">Oversees platform-wide governance, 2FA security compliance, and executive participation analytics.</p>
            </div>

            <div class="glass-card-interactive p-6 text-center border border-white/10">
                <img src="https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&w=200&q=80" class="w-24 h-24 rounded-2xl object-cover mx-auto mb-4 border-2 border-cyan-500/50 shadow-lg shadow-cyan-500/20">
                <h4 class="font-heading font-bold text-lg text-white">Prof. Alexander Wright</h4>
                <div class="text-xs text-cyan-400 font-semibold mb-2">Convener, CS & Robotics Fests</div>
                <p class="text-slate-400 text-xs leading-relaxed">Head organizer for flagship technical hackathons, AI bootcamps, and autonomous combat championships.</p>
            </div>

            <div class="glass-card-interactive p-6 text-center border border-white/10">
                <img src="https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?auto=format&fit=crop&w=200&q=80" class="w-24 h-24 rounded-2xl object-cover mx-auto mb-4 border-2 border-emerald-500/50 shadow-lg shadow-emerald-500/20">
                <h4 class="font-heading font-bold text-lg text-white">Dr. Elena Rostova</h4>
                <div class="text-xs text-emerald-400 font-semibold mb-2">Dean of Cultural & Sports Affairs</div>
                <p class="text-slate-400 text-xs leading-relaxed">Leads pan-university musical galas, theatre fests, intercollegiate debates, and sports olympiads.</p>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
