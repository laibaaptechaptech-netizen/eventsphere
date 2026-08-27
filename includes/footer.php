<?php
// includes/footer.php - Sleek Glassmorphic Footer
require_once __DIR__ . '/../config/config.php';
?>
</main>

<!-- Modern 3D Glass Footer -->
<footer class="mt-20 border-t border-white/10 bg-dark-900/90 backdrop-blur-2xl relative overflow-hidden">
    <!-- Ambient glow in background -->
    <div class="absolute -bottom-20 left-1/4 w-96 h-96 bg-purple-600/10 rounded-full blur-3xl pointer-events-none"></div>
    <div class="absolute -bottom-20 right-1/4 w-96 h-96 bg-cyan-600/10 rounded-full blur-3xl pointer-events-none"></div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-14 relative z-10">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-10">
            <!-- Brand & Mission Column -->
            <div class="lg:col-span-2 space-y-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-purple-600 to-cyan-500 flex items-center justify-center text-white shadow-lg shadow-purple-500/25">
                        <i class="fa-solid fa-cube text-lg"></i>
                    </div>
                    <span class="font-heading font-extrabold text-2xl tracking-tight text-white">
                        Event<span class="text-purple-400">Sphere</span>
                    </span>
                </div>
                <p class="text-slate-400 text-sm leading-relaxed pr-6">
                    The next-generation campus event ecosystem powering seamless registrations, dynamic venue capacity, instant QR attendance, verifiable digital e-certificates, and immersive media galleries.
                </p>
                <div class="flex items-center gap-3 pt-2">
                    <a href="https://twitter.com" target="_blank" class="w-9 h-9 rounded-xl bg-slate-800/80 hover:bg-cyan-500/20 hover:text-cyan-400 border border-white/10 flex items-center justify-center text-slate-400 transition">
                        <i class="fa-brands fa-x-twitter"></i>
                    </a>
                    <a href="https://instagram.com" target="_blank" class="w-9 h-9 rounded-xl bg-slate-800/80 hover:bg-purple-500/20 hover:text-purple-400 border border-white/10 flex items-center justify-center text-slate-400 transition">
                        <i class="fa-brands fa-instagram"></i>
                    </a>
                    <a href="https://linkedin.com" target="_blank" class="w-9 h-9 rounded-xl bg-slate-800/80 hover:bg-blue-500/20 hover:text-blue-400 border border-white/10 flex items-center justify-center text-slate-400 transition">
                        <i class="fa-brands fa-linkedin-in"></i>
                    </a>
                    <a href="https://github.com" target="_blank" class="w-9 h-9 rounded-xl bg-slate-800/80 hover:bg-emerald-500/20 hover:text-emerald-400 border border-white/10 flex items-center justify-center text-slate-400 transition">
                        <i class="fa-brands fa-github"></i>
                    </a>
                </div>
            </div>

            <!-- Quick Exploration -->
            <div>
                <h4 class="font-heading font-bold text-white text-sm uppercase tracking-wider mb-4">Explore</h4>
                <ul class="space-y-2.5 text-sm text-slate-400">
                    <li><a href="<?= BASE_URL ?>/events.php" class="hover:text-cyan-400 transition">Browse Events</a></li>
                    <li><a href="<?= BASE_URL ?>/gallery.php" class="hover:text-cyan-400 transition">Media Gallery</a></li>
                    <li><a href="<?= BASE_URL ?>/about.php" class="hover:text-cyan-400 transition">About EventSphere</a></li>
                    <li><a href="<?= BASE_URL ?>/faqs.php" class="hover:text-cyan-400 transition">Campus FAQs</a></li>
                    <li><a href="<?= BASE_URL ?>/sitemap.php" class="text-amber-400 hover:text-amber-300 font-semibold transition flex items-center gap-1.5"><i class="fa-solid fa-sitemap text-xs"></i> Visual Sitemap</a></li>
                </ul>
            </div>

            <!-- Event Categories -->
            <div>
                <h4 class="font-heading font-bold text-white text-sm uppercase tracking-wider mb-4">Categories</h4>
                <ul class="space-y-2.5 text-sm text-slate-400">
                    <li><a href="<?= BASE_URL ?>/events.php?category=technical-fests" class="hover:text-purple-400 transition">Technical Fests</a></li>
                    <li><a href="<?= BASE_URL ?>/events.php?category=cultural-events" class="hover:text-purple-400 transition">Cultural Carnivals</a></li>
                    <li><a href="<?= BASE_URL ?>/events.php?category=sports-meets" class="hover:text-purple-400 transition">Sports Olympiads</a></li>
                    <li><a href="<?= BASE_URL ?>/events.php?category=workshops-seminars" class="hover:text-purple-400 transition">AI & Cloud Workshops</a></li>
                    <li><a href="<?= BASE_URL ?>/events.php?category=intercollegiate" class="hover:text-purple-400 transition">Intercollegiate</a></li>
                </ul>
            </div>

            <!-- User Portals -->
            <div>
                <h4 class="font-heading font-bold text-white text-sm uppercase tracking-wider mb-4">Access Portals</h4>
                <ul class="space-y-2.5 text-sm text-slate-400">
                    <li><a href="<?= BASE_URL ?>/student/dashboard.php" class="hover:text-emerald-400 transition">Participant Hub</a></li>
                    <li><a href="<?= BASE_URL ?>/organizer/dashboard.php" class="hover:text-cyan-400 transition">Organizer Portal</a></li>
                    <li><a href="<?= BASE_URL ?>/admin/dashboard.php" class="hover:text-purple-400 transition">Admin Command</a></li>
                    <li><a href="<?= BASE_URL ?>/contact.php" class="hover:text-white transition">Support & Inquiries</a></li>
                </ul>
            </div>
        </div>

        <!-- Bottom Copyright & SRS Credits -->
        <div class="border-t border-white/10 mt-12 pt-6 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-slate-500">
            <div>
                © <?= date('Y') ?> EventSphere Campus Ecosystem • Built with PHP 8.2 & MySQL.
            </div>
            <div class="flex items-center gap-4">
                <span class="text-slate-400">Aptech SRS Compliant</span>
                <span>•</span>
                <a href="<?= BASE_URL ?>/sitemap.php" class="text-slate-400 hover:text-cyan-400 transition">Architecture Sitemap</a>
                <span>•</span>
                <span class="text-emerald-400 font-semibold"><i class="fa-solid fa-circle-check mr-1"></i> System Online</span>
            </div>
        </div>
    </div>
</footer>

<!-- Universal Modals Include -->
<?php require_once __DIR__ . '/modals.php'; ?>

<!-- Main JS -->
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>

</body>
</html>
