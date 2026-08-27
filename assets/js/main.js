// assets/js/main.js - EventSphere Main Interactive Utilities & AJAX Handlers

// 1. Toast Notification Manager
function showToast(type = 'info', title = 'Notification', message = '') {
    let container = document.getElementById('toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.className = 'toast';

    let iconClass = 'fa-info-circle text-cyan-400';
    let borderClass = 'border-cyan-500/40';

    if (type === 'success') {
        iconClass = 'fa-check-circle text-emerald-400';
        borderClass = 'border-emerald-500/40';
    } else if (type === 'error') {
        iconClass = 'fa-triangle-exclamation text-rose-400';
        borderClass = 'border-rose-500/40';
    } else if (type === 'warning') {
        iconClass = 'fa-bell text-amber-400';
        borderClass = 'border-amber-500/40';
    }

    toast.classList.add(borderClass);
    toast.innerHTML = `
        <i class="fa-solid ${iconClass} text-xl flex-shrink-0"></i>
        <div class="flex-1 text-sm">
            <h5 class="font-bold text-white mb-0.5">${escapeHtml(title)}</h5>
            <p class="text-slate-300 text-xs">${escapeHtml(message)}</p>
        </div>
        <button onclick="this.parentElement.remove()" class="text-slate-400 hover:text-white text-sm ml-2">
            <i class="fa-solid fa-xmark"></i>
        </button>
    `;

    container.appendChild(toast);
    setTimeout(() => toast.classList.add('show'), 50);

    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 300);
    }, 4500);
}

function escapeHtml(str) {
    if (!str) return '';
    return str.toString().replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
}

// 2. Modal Controller
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('hidden');
        document.body.style.overflow = 'auto';
    }
}

// 3. Visitor Auth Prompt (For actions requiring login)
function promptVisitorLogin(actionName = 'perform this action') {
    const promptText = document.getElementById('visitorAuthPromptText');
    if (promptText) {
        promptText.innerText = `Please sign in or create an account to ${actionName}.`;
    }
    openModal('visitorAuthModal');
}

// 4. AJAX Bookmark Event
function toggleEventBookmark(btn, eventId) {
    fetch(`${BASE_URL}/api/bookmark_event.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ event_id: eventId })
    })
    .then(r => r.json())
    .then(data => {
        if (data.status === 'not_logged_in') {
            promptVisitorLogin('bookmark events for later');
        } else if (data.success) {
            const icon = btn.querySelector('i');
            if (data.bookmarked) {
                icon.classList.remove('fa-regular');
                icon.classList.add('fa-solid', 'text-amber-400');
                showToast('success', 'Bookmarked', 'Event added to your saved events.');
            } else {
                icon.classList.remove('fa-solid', 'text-amber-400');
                icon.classList.add('fa-regular');
                showToast('info', 'Removed', 'Event removed from bookmarks.');
            }
        }
    })
    .catch(() => showToast('error', 'Error', 'Could not update bookmark.'));
}

// 5. AJAX Save Media Item
function toggleSaveMedia(btn, mediaId) {
    fetch(`${BASE_URL}/api/save_media.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ media_id: mediaId })
    })
    .then(r => r.json())
    .then(data => {
        if (data.status === 'not_logged_in') {
            promptVisitorLogin('save media items to your profile');
        } else if (data.success) {
            const icon = btn.querySelector('i');
            if (data.saved) {
                icon.classList.remove('fa-regular');
                icon.classList.add('fa-solid', 'text-rose-500');
                showToast('success', 'Saved to Favorites', 'Media saved to your profile.');
            } else {
                icon.classList.remove('fa-solid', 'text-rose-500');
                icon.classList.add('fa-regular');
                showToast('info', 'Removed', 'Media removed from favorites.');
            }
        }
    })
    .catch(() => showToast('error', 'Error', 'Could not save media.'));
}

// 6. Social Share Modal Opener & Native Share Support
function openShareModal(title, url, description) {
    title = title || 'Campus Event on EventSphere';
    url = url || window.location.href;
    description = description || 'Discover and register for campus events on EventSphere 3D!';

    const encodedTitle = encodeURIComponent(title);
    const encodedUrl = encodeURIComponent(url);
    const encodedDesc = encodeURIComponent(description);
    const hashtags = encodeURIComponent('EventSphere,CampusEvents,CollegeFest2026');

    const titleEl = document.getElementById('shareEventTitle');
    if (titleEl) titleEl.innerText = title;

    const inputEl = document.getElementById('shareCopyInput');
    if (inputEl) inputEl.value = url;

    const waEl = document.getElementById('shareWhatsapp');
    if (waEl) waEl.href = `https://api.whatsapp.com/send?text=${encodedTitle}%20-%20${encodedUrl}`;

    const twEl = document.getElementById('shareTwitter');
    if (twEl) twEl.href = `https://twitter.com/intent/tweet?text=${encodedTitle}&url=${encodedUrl}&hashtags=${hashtags}`;

    const fbEl = document.getElementById('shareFacebook');
    if (fbEl) fbEl.href = `https://www.facebook.com/sharer/sharer.php?u=${encodedUrl}`;

    const liEl = document.getElementById('shareLinkedin');
    if (liEl) liEl.href = `https://www.linkedin.com/sharing/share-offsite/?url=${encodedUrl}`;

    const emEl = document.getElementById('shareEmail');
    if (emEl) emEl.href = `mailto:?subject=${encodedTitle}&body=${encodedDesc}%0A%0AEvent%20Link:%20${encodedUrl}`;

    openModal('socialShareModal');
}

function copyShareUrl() {
    const input = document.getElementById('shareCopyInput');
    if (!input) return;

    input.select();
    input.setSelectionRange(0, 99999);

    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(input.value)
            .then(() => showToast('success', 'Link Copied!', 'Event URL copied to clipboard.'))
            .catch(() => fallbackCopy(input.value));
    } else {
        fallbackCopy(input.value);
    }
}

function fallbackCopy(text) {
    try {
        const textArea = document.createElement("textarea");
        textArea.value = text;
        textArea.style.position = "fixed";
        textArea.style.left = "-999999px";
        textArea.style.top = "-999999px";
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();
        const successful = document.execCommand('copy');
        document.body.removeChild(textArea);
        if (successful) {
            showToast('success', 'Link Copied!', 'Event URL copied to clipboard.');
        } else {
            showToast('info', 'Copy URL', 'Please select and copy the link manually.');
        }
    } catch (err) {
        showToast('info', 'Copy URL', 'Please select and copy the link manually.');
    }
}

// 7. Calendar Integrations
function addToGoogleCalendar(title, startIso, endIso, venue, details) {
    const formatCalDate = (iso) => new Date(iso).toISOString().replace(/-|:|\.\d\d\d/g, "");
    const startFormatted = formatCalDate(startIso);
    const endFormatted = formatCalDate(endIso);
    const url = `https://calendar.google.com/calendar/render?action=TEMPLATE&text=${encodeURIComponent(title)}&dates=${startFormatted}/${endFormatted}&details=${encodeURIComponent(details)}&location=${encodeURIComponent(venue)}`;
    window.open(url, '_blank');
}

function addToOutlookCalendar(title, startIso, endIso, venue, details) {
    const url = `https://outlook.live.com/calendar/0/deeplink/compose?subject=${encodeURIComponent(title)}&startdt=${encodeURIComponent(startIso)}&enddt=${encodeURIComponent(endIso)}&location=${encodeURIComponent(venue)}&body=${encodeURIComponent(details)}`;
    window.open(url, '_blank');
}

function downloadIcsCalendar(eventId) {
    window.location.href = `${BASE_URL}/api/export_ics.php?event_id=${eventId}`;
}

// 8. Auto-dismiss Flash Alerts on page load
document.addEventListener('DOMContentLoaded', () => {
    // Lightbox image viewer trigger
    document.querySelectorAll('[data-lightbox]').forEach(el => {
        el.addEventListener('click', (e) => {
            e.preventDefault();
            const src = el.getAttribute('href') || el.getAttribute('data-src');
            const title = el.getAttribute('data-title') || 'Gallery Media';
            
            const lbModal = document.getElementById('lightboxModal');
            if (lbModal) {
                document.getElementById('lightboxImg').src = src;
                document.getElementById('lightboxTitle').innerText = title;
                openModal('lightboxModal');
            }
        });
    });

    // 3D Card Tilt Effects
    if (typeof VanillaTilt !== 'undefined') {
        VanillaTilt.init(document.querySelectorAll(".tilt-card"), {
            max: 12,
            speed: 400,
            glare: true,
            "max-glare": 0.25,
        });
    }
});
