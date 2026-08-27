<?php
// faqs.php - Interactive Searchable Frequently Asked Questions
$page_title = "Campus FAQs";
require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <!-- Header -->
    <div class="text-center max-w-2xl mx-auto mb-12">
        <div class="badge-neon badge-purple mb-2">Knowledge Base</div>
        <h1 class="font-heading font-extrabold text-3xl sm:text-5xl text-white mb-3">Frequently Asked Questions</h1>
        <p class="text-slate-400 text-sm">Find instant answers about visitor access, student registrations, QR check-ins, and certificate issuance.</p>
    </div>

    <!-- Live FAQ Filter Search -->
    <div class="glass-panel p-4 mb-10 border border-white/10 relative">
        <i class="fa-solid fa-magnifying-glass absolute left-6 top-1/2 -translate-y-1/2 text-cyan-400 text-sm"></i>
        <input type="text" id="faqSearchInput" onkeyup="filterFaqs()" placeholder="Type a keyword to filter FAQs (e.g. 'certificate', 'qr code', 'waitlist', 'login')..." class="form-input-dark pl-12 py-3 text-xs sm:text-sm rounded-xl">
    </div>

    <!-- Categorized FAQ Accordions -->
    <div class="space-y-6" id="faqContainer">
        <!-- Section 1: Normal Visitor & Student Registration -->
        <div class="faq-group">
            <h3 class="font-heading font-bold text-lg text-cyan-400 mb-3 flex items-center gap-2">
                <i class="fa-solid fa-user-astronaut"></i> Visitor & Participant Access
            </h3>
            <div class="space-y-3">
                <details class="faq-item glass-panel p-4 border border-white/10 rounded-xl cursor-pointer group">
                    <summary class="font-bold text-sm text-slate-200 flex items-center justify-between group-hover:text-cyan-300">
                        <span>Can I explore upcoming campus events without creating an account?</span>
                        <i class="fa-solid fa-chevron-down text-xs text-slate-400 group-open:rotate-180 transition-transform"></i>
                    </summary>
                    <p class="mt-3 text-xs text-slate-400 leading-relaxed pt-2 border-t border-white/5">
                        Yes! As a Normal Student (Visitor), you have unrestricted public access to browse upcoming, ongoing, and past event catalogs, filter by department or category, view high-definition media galleries, and review FAQs without logging in.
                    </p>
                </details>

                <details class="faq-item glass-panel p-4 border border-white/10 rounded-xl cursor-pointer group">
                    <summary class="font-bold text-sm text-slate-200 flex items-center justify-between group-hover:text-cyan-300">
                        <span>What happens if an unregistered visitor attempts to register for an event?</span>
                        <i class="fa-solid fa-chevron-down text-xs text-slate-400 group-open:rotate-180 transition-transform"></i>
                    </summary>
                    <p class="mt-3 text-xs text-slate-400 leading-relaxed pt-2 border-t border-white/5">
                        The platform enforces role-based access control and will immediately display an interactive Auth Modal prompting you to either sign in with your credentials or complete the 1-minute student registration form.
                    </p>
                </details>

                <details class="faq-item glass-panel p-4 border border-white/10 rounded-xl cursor-pointer group">
                    <summary class="font-bold text-sm text-slate-200 flex items-center justify-between group-hover:text-cyan-300">
                        <span>How does the dynamic venue capacity & automated waitlist work?</span>
                        <i class="fa-solid fa-chevron-down text-xs text-slate-400 group-open:rotate-180 transition-transform"></i>
                    </summary>
                    <p class="mt-3 text-xs text-slate-400 leading-relaxed pt-2 border-t border-white/5">
                        Each event venue has a configured seating threshold. Once all confirmed seats are claimed, registrations switch to 'Waitlist Mode'. If an existing registrant cancels before the cutoff, the system's promotion algorithm automatically promotes the earliest waitlisted student to 'Confirmed' and dispatches a notification!
                    </p>
                </details>
            </div>
        </div>

        <!-- Section 2: Check-in, Passes & E-Certificates -->
        <div class="faq-group">
            <h3 class="font-heading font-bold text-lg text-purple-400 mb-3 flex items-center gap-2">
                <i class="fa-solid fa-qrcode"></i> QR Check-ins & Verifiable Certificates
            </h3>
            <div class="space-y-3">
                <details class="faq-item glass-panel p-4 border border-white/10 rounded-xl cursor-pointer group">
                    <summary class="font-bold text-sm text-slate-200 flex items-center justify-between group-hover:text-purple-300">
                        <span>How do I check in on the day of the event?</span>
                        <i class="fa-solid fa-chevron-down text-xs text-slate-400 group-open:rotate-180 transition-transform"></i>
                    </summary>
                    <p class="mt-3 text-xs text-slate-400 leading-relaxed pt-2 border-t border-white/5">
                        Upon registration confirmation, a unique dynamic QR code is generated in your Student Dashboard. Simply present this pass on your phone or printed ticket at the venue gate, where staff scan it via their in-browser QR scanner to mark your attendance in real time.
                    </p>
                </details>

                <details class="faq-item glass-panel p-4 border border-white/10 rounded-xl cursor-pointer group">
                    <summary class="font-bold text-sm text-slate-200 flex items-center justify-between group-hover:text-purple-300">
                        <span>How do I claim and download my official e-certificate?</span>
                        <i class="fa-solid fa-chevron-down text-xs text-slate-400 group-open:rotate-180 transition-transform"></i>
                    </summary>
                    <p class="mt-3 text-xs text-slate-400 leading-relaxed pt-2 border-t border-white/5">
                        Once your attendance is verified by event organizers post-event, navigate to your Student Hub > E-Certificates. Enter your simulated certificate fee transaction details (as per SRS item 9), and your official high-definition certificate with holographic seal and verification ID will be generated for 1-click PNG/PDF export.
                    </p>
                </details>
            </div>
        </div>

        <!-- Section 3: Organizer & Administrator Governance -->
        <div class="faq-group">
            <h3 class="font-heading font-bold text-lg text-emerald-400 mb-3 flex items-center gap-2">
                <i class="fa-solid fa-shield-halved"></i> Governance & College Staff
            </h3>
            <div class="space-y-3">
                <details class="faq-item glass-panel p-4 border border-white/10 rounded-xl cursor-pointer group">
                    <summary class="font-bold text-sm text-slate-200 flex items-center justify-between group-hover:text-emerald-300">
                        <span>Why are newly created events marked as 'Pending Approval'?</span>
                        <i class="fa-solid fa-chevron-down text-xs text-slate-400 group-open:rotate-180 transition-transform"></i>
                    </summary>
                    <p class="mt-3 text-xs text-slate-400 leading-relaxed pt-2 border-t border-white/5">
                        To maintain institutional standards and venue safety compliance, all events proposed by College Organizers enter a moderation queue. System Administrators review the schedule, venue capacity, and rulebook before approving the event to go live.
                    </p>
                </details>

                <details class="faq-item glass-panel p-4 border border-white/10 rounded-xl cursor-pointer group">
                    <summary class="font-bold text-sm text-slate-200 flex items-center justify-between group-hover:text-emerald-300">
                        <span>Can organizers broadcast direct notifications to registered attendees?</span>
                        <i class="fa-solid fa-chevron-down text-xs text-slate-400 group-open:rotate-180 transition-transform"></i>
                    </summary>
                    <p class="mt-3 text-xs text-slate-400 leading-relaxed pt-2 border-t border-white/5">
                        Yes. Organizers have a dedicated broadcast center to dispatch instant notifications to all confirmed participants regarding venue changes, schedule updates, or lab instructions.
                    </p>
                </details>
            </div>
        </div>
    </div>
</div>

<script>
function filterFaqs() {
    const query = document.getElementById('faqSearchInput').value.toLowerCase();
    const items = document.querySelectorAll('.faq-item');
    
    items.forEach(item => {
        const text = item.innerText.toLowerCase();
        if (text.includes(query)) {
            item.style.display = 'block';
            if (query.length > 2) item.setAttribute('open', 'true');
        } else {
            item.style.display = 'none';
        }
    });
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
