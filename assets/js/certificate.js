// assets/js/certificate.js - High-Definition Verifiable Certificate Generator

function generateCertificateCanvas(canvasId, certData) {
    const canvas = document.getElementById(canvasId);
    if (!canvas) return;

    const ctx = canvas.getContext('2d');
    canvas.width = 1200;
    canvas.height = 850;

    // 1. Dark Luxury Background
    const bgGradient = ctx.createLinearGradient(0, 0, 1200, 850);
    bgGradient.addColorStop(0, '#090d16');
    bgGradient.addColorStop(0.5, '#0f172a');
    bgGradient.addColorStop(1, '#070a12');
    ctx.fillStyle = bgGradient;
    ctx.fillRect(0, 0, 1200, 850);

    // 2. Elegant Dual Gold Guilloche Borders
    ctx.strokeStyle = '#d4af37';
    ctx.lineWidth = 6;
    ctx.strokeRect(30, 30, 1140, 790);

    ctx.strokeStyle = '#fef08a';
    ctx.lineWidth = 1.5;
    ctx.strokeRect(42, 42, 1116, 766);

    // 3. Corner Ornaments
    drawCornerAccent(ctx, 42, 42, 0);
    drawCornerAccent(ctx, 1158, 42, Math.PI / 2);
    drawCornerAccent(ctx, 1158, 808, Math.PI);
    drawCornerAccent(ctx, 42, 808, -Math.PI / 2);

    // 4. Header & Branding
    ctx.textAlign = 'center';
    ctx.fillStyle = '#a855f7';
    ctx.font = 'bold 22px "Space Grotesk", sans-serif';
    ctx.fillText("EVENTSPHERE CAMPUS EXCELLENCE FOUNDATION", 600, 110);

    ctx.fillStyle = '#f8fafc';
    ctx.font = 'bold 42px "Space Grotesk", sans-serif';
    ctx.fillText("CERTIFICATE OF PARTICIPATION", 600, 175);

    // Subtle divider
    const goldGrad = ctx.createLinearGradient(400, 0, 800, 0);
    goldGrad.addColorStop(0, 'transparent');
    goldGrad.addColorStop(0.5, '#d4af37');
    goldGrad.addColorStop(1, 'transparent');
    ctx.strokeStyle = goldGrad;
    ctx.lineWidth = 2;
    ctx.beginPath();
    ctx.moveTo(350, 200);
    ctx.lineTo(850, 200);
    ctx.stroke();

    // 5. Body Text
    ctx.fillStyle = '#94a3b8';
    ctx.font = 'italic 20px "Plus Jakarta Sans", sans-serif';
    ctx.fillText("This is proudly presented to", 600, 255);

    // Student Name (Large Neon Gold)
    ctx.fillStyle = '#fef08a';
    ctx.font = 'bold 48px "Space Grotesk", sans-serif';
    ctx.fillText(certData.studentName.toUpperCase(), 600, 325);

    // Enrollment & Dept
    ctx.fillStyle = '#38bdf8';
    ctx.font = '600 18px "Plus Jakarta Sans", sans-serif';
    ctx.fillText(`Enrolment ID: ${certData.enrolmentNo || 'N/A'} • Department of ${certData.department || 'Engineering'}`, 600, 365);

    // Participation statement
    ctx.fillStyle = '#cbd5e1';
    ctx.font = '20px "Plus Jakarta Sans", sans-serif';
    ctx.fillText("for actively attending and successfully participating in", 600, 425);

    // Event Title
    ctx.fillStyle = '#c084fc';
    ctx.font = 'bold 32px "Space Grotesk", sans-serif';
    ctx.fillText(certData.eventTitle, 600, 480);

    // Event Date & Venue
    ctx.fillStyle = '#94a3b8';
    ctx.font = '18px "Plus Jakarta Sans", sans-serif';
    ctx.fillText(`Organized on ${certData.eventDate} at ${certData.venue}`, 600, 525);

    // 6. Gold Foil Stamp Badge (Center Left)
    drawGoldFoilBadge(ctx, 230, 680);

    // 7. Signatures
    // Organizer Signature
    ctx.textAlign = 'center';
    ctx.fillStyle = '#f8fafc';
    ctx.font = 'italic bold 22px cursive';
    ctx.fillText("Prof. Alexander Wright", 900, 670);
    ctx.strokeStyle = '#64748b';
    ctx.lineWidth = 1;
    ctx.beginPath();
    ctx.moveTo(760, 685);
    ctx.lineTo(1040, 685);
    ctx.stroke();
    ctx.font = '600 14px "Plus Jakarta Sans", sans-serif';
    ctx.fillStyle = '#94a3b8';
    ctx.fillText("Convener & Dean of Academic Events", 900, 710);

    // 8. Verification QR Code & ID
    ctx.textAlign = 'center';
    ctx.font = 'bold 13px monospace';
    ctx.fillStyle = '#38bdf8';
    ctx.fillText(`VERIFY: ${certData.certCode || 'CERT-ESP-2026-9901'}`, 600, 755);
    ctx.font = '12px "Plus Jakarta Sans", sans-serif';
    ctx.fillStyle = '#64748b';
    ctx.fillText("Tamper-Proof Cryptographic ID Verified by EventSphere Registry", 600, 775);
}

function drawCornerAccent(ctx, x, y, angle) {
    ctx.save();
    ctx.translate(x, y);
    ctx.rotate(angle);
    ctx.strokeStyle = '#d4af37';
    ctx.lineWidth = 2;
    ctx.beginPath();
    ctx.moveTo(0, 0);
    ctx.lineTo(30, 0);
    ctx.moveTo(0, 0);
    ctx.lineTo(0, 30);
    ctx.stroke();
    ctx.restore();
}

function drawGoldFoilBadge(ctx, x, y) {
    ctx.save();
    ctx.beginPath();
    ctx.arc(x, y, 50, 0, Math.PI * 2);
    const grad = ctx.createRadialGradient(x, y, 10, x, y, 50);
    grad.addColorStop(0, '#fef08a');
    grad.addColorStop(0.7, '#ca8a04');
    grad.addColorStop(1, '#854d0e');
    ctx.fillStyle = grad;
    ctx.fill();

    ctx.strokeStyle = '#fef08a';
    ctx.lineWidth = 2;
    ctx.stroke();

    ctx.textAlign = 'center';
    ctx.fillStyle = '#422006';
    ctx.font = 'bold 11px "Space Grotesk", sans-serif';
    ctx.fillText("OFFICIAL", x, y - 8);
    ctx.font = 'bold 15px "Space Grotesk", sans-serif';
    ctx.fillText("VERIFIED", x, y + 8);
    ctx.font = '9px "Space Grotesk", sans-serif';
    ctx.fillText("CAMPUS SEAL", x, y + 22);
    ctx.restore();
}

function downloadCertificateImage(canvasId, fileName = 'EventSphere_Certificate.png') {
    const canvas = document.getElementById(canvasId);
    if (!canvas) return;
    const link = document.createElement('a');
    link.download = fileName;
    link.href = canvas.toDataURL('image/png', 1.0);
    link.click();
}

function printCertificate(canvasId) {
    const canvas = document.getElementById(canvasId);
    if (!canvas) return;
    const dataUrl = canvas.toDataURL();
    const windowContent = `<!DOCTYPE html><html><head><title>Print Certificate</title><style>body{margin:0;display:flex;justify-content:center;align-items:center;background:#000;} img{max-width:100%;height:auto;}</style></head><body onload="window.print();window.close();"><img src="${dataUrl}"></body></html>`;
    const printWin = window.open('', '', 'width=1200,height=850');
    printWin.document.open();
    printWin.document.write(windowContent);
    printWin.document.close();
}
