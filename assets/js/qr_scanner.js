// assets/js/qr_scanner.js - QR Code Scanner & Manual Attendance Marker

let html5QrCodeScanner = null;

function initQrScanner(onSuccessCallback) {
    const scannerElement = document.getElementById('qr-reader');
    if (!scannerElement || typeof Html5QrcodeScanner === 'undefined') return;

    const html5QrcodeScanner = new Html5QrcodeScanner(
        "qr-reader",
        { fps: 10, qrbox: { width: 250, height: 250 } },
        /* verbose= */ false
    );

    html5QrcodeScanner.render((decodedText, decodedResult) => {
        playScanBeep();
        if (onSuccessCallback) {
            onSuccessCallback(decodedText);
        }
        // html5QrcodeScanner.clear(); // Optional: stop scanning after success
    }, (errorMessage) => {
        // parse error, ignore
    });

    // Handle failure to start camera by checking if it rendered (optional, but it has its own UI)
    setTimeout(() => {
        if (!document.querySelector('#qr-reader video')) {
            const alertBox = document.getElementById('camera-fallback-alert');
            if (alertBox) alertBox.classList.remove('hidden');
        }
    }, 3000);
}

function playScanBeep() {
    try {
        const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
        const osc = audioCtx.createOscillator();
        const gain = audioCtx.createGain();
        osc.type = 'sine';
        osc.frequency.setValueAtTime(880, audioCtx.currentTime); // A5 note
        gain.gain.setValueAtTime(0.2, audioCtx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.01, audioCtx.currentTime + 0.15);
        osc.connect(gain);
        gain.connect(audioCtx.destination);
        osc.start();
        osc.stop(audioCtx.currentTime + 0.15);
    } catch (e) {
        // AudioContext not allowed or unsupported
    }
}
