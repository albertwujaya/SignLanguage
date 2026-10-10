/* Animasi: muncul saat discroll, stempel saat "learned", konfeti saat sesi selesai.
   Berjalan di atas SPA lewat MutationObserver. Hormati prefers-reduced-motion. */
(function () {
    'use strict';
    const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const app = document.getElementById('app');
    if (!app) return;

    const REVEAL = '.page-header, .cat-row, .gesture-card-item, .feat-item, .stat-cell, .lesson-step-block, .flip-scene, .practice-target-card';

    const io = 'IntersectionObserver' in window && !reduce
        ? new IntersectionObserver((entries) => {
            entries.forEach((e) => { if (e.isIntersecting) { e.target.classList.add('in'); io.unobserve(e.target); } });
        }, { threshold: 0.12 })
        : null;

    function reveal() {
        let i = 0;
        app.querySelectorAll(REVEAL).forEach((el) => {
            if (el.dataset.rv) return;
            el.dataset.rv = '1';
            if (!io) return;
            el.style.setProperty('--d', Math.min(i++ % 8, 7) * 60 + 'ms');
            el.classList.add('rv');
            io.observe(el);
        });
    }

    function confetti() {
        if (reduce) return;
        const colors = ['#F2B45A', '#E8735A', '#7FA87A', '#9CCBDD', '#FFF9EA'];
        for (let i = 0; i < 36; i++) {
            const b = document.createElement('i');
            b.className = 'confetti-bit';
            b.style.left = Math.random() * 100 + 'vw';
            b.style.background = colors[i % colors.length];
            b.style.setProperty('--dx', (Math.random() * 160 - 80) + 'px');
            b.style.setProperty('--rot', (Math.random() * 900 - 450) + 'deg');
            b.style.animationDelay = Math.random() * 0.4 + 's';
            document.body.appendChild(b);
            setTimeout(() => b.remove(), 2400);
        }
    }

    // Setiap render SPA: pasang reveal, dan deteksi layar "sesi selesai"
    new MutationObserver(() => {
        reveal();
        const done = app.querySelector('.session-complete-wrap');
        if (done && !done.dataset.fx) { done.dataset.fx = '1'; confetti(); }
    }).observe(app, { childList: true, subtree: true });
    reveal();

    // Stempel pop saat tombol "learned" ditekan
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('#btn-mark-learned');
        if (!btn || reduce) return;
        setTimeout(() => {
            btn.classList.remove('stamp'); void btn.offsetWidth; btn.classList.add('stamp');
            if (/LEARNED/.test(btn.textContent) && !/UN/.test(btn.textContent)) confetti();
        }, 30);
    });

    // Header: bayangan setelah scroll sudah ditangani app.js (kelas .s)
})();
