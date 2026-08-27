<?php
// includes/modals.php - Universal Interactive Modals for EventSphere
?>

<!-- 1. Visitor Auth Prompt Modal -->
<div id="visitorAuthModal" class="hidden fixed inset-0 z-50 overflow-y-auto modal-backdrop-dark flex items-center justify-center p-4">
    <div class="glass-panel-elevated max-w-md w-full p-6 sm:p-8 text-center relative border border-purple-500/30 transform transition-all">
        <button onclick="closeModal('visitorAuthModal')" class="absolute top-4 right-4 text-slate-400 hover:text-white">
            <i class="fa-solid fa-xmark text-lg"></i>
        </button>

        <div class="w-16 h-16 rounded-2xl bg-purple-500/10 border border-purple-500/30 text-purple-400 flex items-center justify-center mx-auto mb-4 text-2xl shadow-lg shadow-purple-500/20">
            <i class="fa-solid fa-lock"></i>
        </div>

        <h3 class="font-heading font-bold text-2xl text-white mb-2">Authentication Required</h3>
        <p id="visitorAuthPromptText" class="text-sm text-slate-300 mb-6">
            Please log in or create a student account to register for events, submit reviews, or download certificates.
        </p>

        <div class="flex flex-col sm:flex-row items-center gap-3">
            <a href="<?= BASE_URL ?>/auth/login.php" class="btn-neon-primary w-full py-2.5 text-sm">
                <i class="fa-solid fa-right-to-bracket mr-1.5"></i> Sign In
            </a>
            <a href="<?= BASE_URL ?>/auth/register.php" class="btn-glass w-full py-2.5 text-sm">
                <i class="fa-solid fa-user-plus mr-1.5"></i> Register Now
            </a>
        </div>

        <div class="mt-6 pt-4 border-t border-white/10 text-xs text-slate-400">
            <span>Evaluating platform? </span>
            <a href="<?= BASE_URL ?>/auth/login.php?quick=student" class="text-cyan-400 hover:underline font-semibold">
                Quick Login as Demo Student
            </a>
        </div>
    </div>
</div>

<!-- 2. Social Media Share Modal -->
<div id="socialShareModal" class="hidden fixed inset-0 z-50 overflow-y-auto modal-backdrop-dark flex items-center justify-center p-4">
    <div class="glass-panel-elevated max-w-lg w-full p-6 sm:p-8 relative border border-cyan-500/30">
        <button onclick="closeModal('socialShareModal')" class="absolute top-4 right-4 text-slate-400 hover:text-white">
            <i class="fa-solid fa-xmark text-lg"></i>
        </button>

        <div class="flex items-center gap-3 mb-4">
            <div class="w-10 h-10 rounded-xl bg-cyan-500/10 border border-cyan-500/30 text-cyan-400 flex items-center justify-center text-lg">
                <i class="fa-solid fa-share-nodes"></i>
            </div>
            <div>
                <h3 class="font-heading font-bold text-xl text-white">Share Event</h3>
                <p id="shareEventTitle" class="text-xs text-slate-400 line-clamp-1">Event Title</p>
            </div>
        </div>

        <p class="text-xs text-slate-300 mb-4">Spread the word with auto-filled event details and hashtags across your social networks:</p>

        <!-- Social Grid -->
        <div class="grid grid-cols-2 sm:grid-cols-5 gap-2.5 mb-6">
            <a id="shareWhatsapp" href="#" target="_blank" rel="noopener noreferrer" class="flex flex-col items-center justify-center p-3 rounded-xl bg-emerald-950/40 hover:bg-emerald-900/60 border border-emerald-500/30 text-emerald-300 transition">
                <i class="fa-brands fa-whatsapp text-2xl mb-1 text-emerald-400"></i>
                <span class="text-xs font-semibold">WhatsApp</span>
            </a>
            <a id="shareTwitter" href="#" target="_blank" rel="noopener noreferrer" class="flex flex-col items-center justify-center p-3 rounded-xl bg-slate-800/80 hover:bg-slate-700 border border-white/10 text-slate-200 transition">
                <i class="fa-brands fa-x-twitter text-2xl mb-1 text-white"></i>
                <span class="text-xs font-semibold">X (Twitter)</span>
            </a>
            <a id="shareFacebook" href="#" target="_blank" rel="noopener noreferrer" class="flex flex-col items-center justify-center p-3 rounded-xl bg-blue-950/40 hover:bg-blue-900/60 border border-blue-500/30 text-blue-300 transition">
                <i class="fa-brands fa-facebook-f text-2xl mb-1 text-blue-400"></i>
                <span class="text-xs font-semibold">Facebook</span>
            </a>
            <a id="shareLinkedin" href="#" target="_blank" rel="noopener noreferrer" class="flex flex-col items-center justify-center p-3 rounded-xl bg-sky-950/40 hover:bg-sky-900/60 border border-sky-500/30 text-sky-300 transition">
                <i class="fa-brands fa-linkedin-in text-2xl mb-1 text-sky-400"></i>
                <span class="text-xs font-semibold">LinkedIn</span>
            </a>
            <a id="shareEmail" href="#" class="flex flex-col items-center justify-center p-3 rounded-xl bg-purple-950/40 hover:bg-purple-900/60 border border-purple-500/30 text-purple-300 transition">
                <i class="fa-solid fa-envelope text-2xl mb-1 text-purple-400"></i>
                <span class="text-xs font-semibold">Email</span>
            </a>
        </div>

        <!-- Copy Link Section -->
        <div>
            <label class="block text-xs font-semibold text-slate-400 mb-1.5">Direct Event Link</label>
            <div class="flex items-center gap-2">
                <input id="shareCopyInput" type="text" readonly class="form-input-dark text-xs py-2">
                <button type="button" onclick="copyShareUrl()" class="btn-neon-cyan text-xs py-2 px-4 whitespace-nowrap">
                    <i class="fa-solid fa-copy mr-1"></i> Copy Link
                </button>
            </div>
        </div>
    </div>
</div>

<!-- 3. Simulated Certificate Fee Payment Modal (SRS Req 9) -->
<div id="certFeeModal" class="hidden fixed inset-0 z-50 overflow-y-auto modal-backdrop-dark flex items-center justify-center p-4">
    <div class="glass-panel-elevated max-w-md w-full p-6 sm:p-8 relative border border-amber-500/30">
        <button onclick="closeModal('certFeeModal')" class="absolute top-4 right-4 text-slate-400 hover:text-white">
            <i class="fa-solid fa-xmark text-lg"></i>
        </button>

        <div class="flex items-center gap-3 mb-4">
            <div class="w-12 h-12 rounded-2xl bg-amber-500/10 border border-amber-500/30 text-amber-400 flex items-center justify-center text-xl">
                <i class="fa-solid fa-receipt"></i>
            </div>
            <div>
                <h3 class="font-heading font-bold text-xl text-white">Certificate Fee Payment</h3>
                <p class="text-xs text-amber-400 font-semibold">Simulated Verification Form (SRS Item 9)</p>
            </div>
        </div>

        <form id="certFeeForm" onsubmit="submitCertFeePayment(event)">
            <input type="hidden" id="feeRegId" name="registration_id" value="">
            
            <div class="bg-dark-900/80 p-3.5 rounded-xl border border-white/10 mb-4 text-xs space-y-1">
                <div class="flex justify-between text-slate-300">
                    <span>Event Name:</span>
                    <span id="feeEventTitle" class="font-bold text-white">HackNova 2026</span>
                </div>
                <div class="flex justify-between text-slate-300">
                    <span>Official E-Certificate Fee:</span>
                    <span id="feeAmountDisplay" class="font-bold text-amber-400">$15.00 / ₹150</span>
                </div>
                <div class="flex justify-between text-slate-400 text-[11px] pt-1">
                    <span>Processing:</span>
                    <span class="text-emerald-400 font-semibold">Simulated (SRS Non-Gateway Mode)</span>
                </div>
            </div>

            <div class="space-y-3 mb-6">
                <div>
                    <label class="block text-xs font-medium text-slate-300 mb-1">Payment Method</label>
                    <select id="feePaymentMode" name="payment_mode" class="form-input-dark text-xs" required>
                        <option value="UPI / QR Scanner">UPI (GooglePay / PhonePe / Paytm)</option>
                        <option value="Debit/Credit Card">Debit / Credit Card</option>
                        <option value="Net Banking">Campus Net Banking Portal</option>
                        <option value="Institutional Waiver">Academic Fee Waiver Token</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-300 mb-1">Transaction Reference / UTR Number</label>
                    <input type="text" id="feeTxnId" name="txn_id" placeholder="e.g. TXN_UPI_9827182761" class="form-input-dark text-xs" required>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-300 mb-1">Student Contact / WhatsApp for E-Receipt</label>
                    <input type="text" id="feeContact" name="contact" placeholder="+1-555-0142" class="form-input-dark text-xs" required>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <button type="button" onclick="closeModal('certFeeModal')" class="btn-glass w-full py-2.5 text-xs">
                    Cancel
                </button>
                <button type="submit" class="btn-neon-primary w-full py-2.5 text-xs font-bold text-white bg-gradient-to-r from-amber-500 to-yellow-600 border-amber-400">
                    <i class="fa-solid fa-lock mr-1.5"></i> Confirm Fee Details
                </button>
            </div>
        </form>
    </div>
</div>

<!-- 4. Media Lightbox Modal -->
<div id="lightboxModal" class="hidden fixed inset-0 z-50 modal-backdrop-dark flex items-center justify-center p-4">
    <div class="max-w-4xl w-full relative bg-dark-900 border border-white/10 rounded-2xl overflow-hidden shadow-2xl p-2">
        <button onclick="closeModal('lightboxModal')" class="absolute top-4 right-4 z-20 w-9 h-9 rounded-full bg-dark-900/80 hover:bg-rose-600 text-white flex items-center justify-center border border-white/20 transition">
            <i class="fa-solid fa-xmark"></i>
        </button>
        <div class="w-full flex items-center justify-center bg-black/50 rounded-xl overflow-hidden min-h-[300px] max-h-[75vh]">
            <img id="lightboxImg" src="" alt="Event Media" class="max-h-[75vh] w-auto object-contain">
        </div>
        <div class="p-3 text-center">
            <h4 id="lightboxTitle" class="font-semibold text-sm text-white">Event Gallery</h4>
        </div>
    </div>
</div>
