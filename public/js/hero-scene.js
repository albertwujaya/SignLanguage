/**
 * Adegan hero gaya Ghibli: langit, awan berlapis (parallax), bukit, rumput
 * bergoyang, partikel biji dandelion, dan dua tangan simbolis yang saling
 * menjangkau. Murni SVG/CSS/canvas, tanpa aset video wajib.
 *
 * Dipicu lewat MutationObserver (pola sama seperti anim.js) karena hero
 * di-render ulang oleh SPA setiap kali route berpindah ke "/".
 */
(function () {
    'use strict';

    const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const small = window.matchMedia('(max-width: 640px)').matches;

    // --- Markup helper: 3 lapis awan sederhana (bentuk gumpalan elips) ---
    function cloudSvg(w, h, color) {
        return `<svg viewBox="0 0 ${w} ${h}" preserveAspectRatio="none" aria-hidden="true">
            <ellipse cx="${w*0.28}" cy="${h*0.62}" rx="${w*0.26}" ry="${h*0.34}" fill="${color}"/>
            <ellipse cx="${w*0.52}" cy="${h*0.42}" rx="${w*0.30}" ry="${h*0.42}" fill="${color}"/>
            <ellipse cx="${w*0.76}" cy="${h*0.60}" rx="${w*0.24}" ry="${h*0.32}" fill="${color}"/>
            <ellipse cx="${w*0.5}" cy="${h*0.74}" rx="${w*0.46}" ry="${h*0.26}" fill="${color}"/>
        </svg>`;
    }

    function hillPath(d, color) {
        return `<svg viewBox="0 0 1000 300" preserveAspectRatio="none" style="width:100%;height:100%;display:block" aria-hidden="true">
            <path d="${d}" fill="${color}"/>
        </svg>`;
    }

    // Satu bentuk tangan generik (simbol koneksi, bukan bentuk jari isyarat
    // tertentu): telapak membulat + 4 tonjolan jari + 1 ibu jari.
    function handPath() {
        return 'M18,150 C8,120 10,70 36,46 C42,30 58,20 70,30 C76,14 96,10 104,26 '
            + 'C112,10 134,12 138,30 C148,18 168,24 168,44 C184,40 198,58 190,78 '
            + 'C206,92 204,120 186,132 C190,160 170,182 140,182 L70,182 '
            + 'C40,182 18,178 18,150 Z';
    }

    function buildScene(container, videoCfg) {
        if (videoCfg) {
            container.innerHTML = `
            <div class="gh-video-window" aria-hidden="true">
                <video autoplay muted loop playsinline preload="metadata"
                    poster="${videoCfg.poster || ''}" style="width:100%;height:100%;object-fit:cover;filter:saturate(1.04) sepia(.08) contrast(1.02);border-radius:inherit;">
                    ${videoCfg.webm ? `<source src="${videoCfg.webm}" type="video/webm">` : ''}
                    <source src="${videoCfg.mp4}" type="video/mp4">
                </video>
            </div>`;
            const wrap = container.querySelector('.gh-video-window');
            Object.assign(wrap.style, {
                position: 'absolute', left: '50%', bottom: '10%', transform: 'translateX(-50%)',
                width: 'min(640px, 70vw)', aspectRatio: '16/10', borderRadius: '28px 28px 40px 40px',
                overflow: 'hidden', border: '1px solid rgba(74,49,42,.16)',
                boxShadow: '0 14px 30px rgba(74,49,42,.16)'
            });
            return;
        }

        const grassCount = small ? 14 : 26;
        let grass = '';
        for (let i = 0; i < grassCount; i++) {
            const left = (i / (grassCount - 1)) * 100;
            const hgt = 26 + Math.round(Math.random() * 20);
            const dly = (Math.random() * 3).toFixed(2);
            const dur = (3 + Math.random() * 1.6).toFixed(2);
            const r1 = -3 - Math.random() * 4, r2 = 3 + Math.random() * 4;
            grass += `<svg class="gh-blade" style="position:absolute;left:${left}%;bottom:0;width:6px;height:${hgt}px;animation-delay:${dly}s;animation-duration:${dur}s;--r1:${r1}deg;--r2:${r2}deg" viewBox="0 0 6 ${hgt}" aria-hidden="true"><path d="M3,${hgt} C0,${hgt*0.5} 1,${hgt*0.2} 3,0 C5,${hgt*0.2} 6,${hgt*0.5} 3,${hgt}" fill="#295C33"/></svg>`;
        }
        let flowers = '';
        const flowerCount = small ? 4 : 7;
        for (let i = 0; i < flowerCount; i++) {
            const left = 4 + Math.random() * 92;
            const colors = ['#FDE9B4', '#E98BAB', '#F06158'];
            const c = colors[i % colors.length];
            const dly = (Math.random() * 3).toFixed(2);
            flowers += `<svg class="gh-flower" style="left:${left}%;width:14px;height:22px;animation-delay:${dly}s" viewBox="0 0 14 22" aria-hidden="true">
                <line x1="7" y1="22" x2="7" y2="9" stroke="#506628" stroke-width="2"/>
                <circle cx="7" cy="6" r="2.4" fill="#E39901"/>
                <circle cx="3.2" cy="4.4" r="2.1" fill="${c}"/><circle cx="10.8" cy="4.4" r="2.1" fill="${c}"/>
                <circle cx="3.2" cy="7.6" r="2.1" fill="${c}"/><circle cx="10.8" cy="7.6" r="2.1" fill="${c}"/>
            </svg>`;
        }

        container.innerHTML = `
        <div class="gh-scene">
            <div class="gh-sun"></div>
            <div class="gh-clouds">
                <div class="gh-cloud" data-speed="7" style="top:10%;left:4%;width:180px;height:70px;opacity:.85">${cloudSvg(180,70,'#FFFFFF')}</div>
                <div class="gh-cloud" data-speed="14" style="top:16%;left:58%;width:230px;height:90px;opacity:.9">${cloudSvg(230,90,'#FFFFFF')}</div>
                <div class="gh-cloud" data-speed="22" style="top:6%;left:34%;width:140px;height:56px;opacity:.75">${cloudSvg(140,56,'#FFFFFF')}</div>
            </div>
            <div class="gh-hills" aria-hidden="true">
                <div class="gh-hill" style="height:100%;opacity:.9">${hillPath('M0,180 C180,110 380,140 560,120 C760,98 900,140 1000,120 L1000,300 L0,300 Z', 'var(--hill-1)')}</div>
                <div class="gh-hill" style="height:78%;opacity:.95">${hillPath('M0,160 C220,200 420,100 640,140 C820,170 920,120 1000,140 L1000,300 L0,300 Z', 'var(--hill-2)')}</div>
                <div class="gh-hill" style="height:52%">${hillPath('M0,140 C240,80 480,170 700,120 C860,86 940,140 1000,110 L1000,300 L0,300 Z', 'var(--hill-3)')}</div>
                <div class="gh-hill" style="height:26%">${hillPath('M0,200 C260,140 520,220 780,160 C880,140 950,190 1000,170 L1000,300 L0,300 Z', 'var(--hill-4)')}</div>
            </div>
            <canvas id="gh-particles" aria-hidden="true"></canvas>
            <div class="gh-grass-strip" aria-hidden="true">${grass}${flowers}</div>
            <div class="gh-hands-wrap" id="gh-hands" aria-hidden="true">
                <svg viewBox="0 0 420 220">
                    <circle class="gh-touch-glow" cx="210" cy="110" r="46" fill="#FDE9B4" opacity="0"/>
                    <g class="gh-hand-l" style="transform-origin:0 110px">
                        <path class="gh-hand-fill" d="${handPath()}" transform="translate(10,20)"/>
                        <path class="gh-hand-path" d="${handPath()}" transform="translate(10,20)" pathLength="1"/>
                    </g>
                    <g class="gh-hand-r" style="transform-origin:420px 110px">
                        <path class="gh-hand-fill" d="${handPath()}" transform="translate(252,20) scale(-1,1) translate(-206,0)"/>
                        <path class="gh-hand-path" d="${handPath()}" transform="translate(252,20) scale(-1,1) translate(-206,0)" pathLength="1"/>
                    </g>
                </svg>
            </div>
        </div>`;
    }

    // --- Partikel biji dandelion (canvas, performa dijaga) ---
    function startParticles(canvas, host) {
        const ctx = canvas.getContext('2d');
        const dpr = Math.min(window.devicePixelRatio || 1, 2);
        const max = small ? 16 : 36;
        let parts = [], raf = null, running = false;

        function resize() {
            const r = host.getBoundingClientRect();
            canvas.width = r.width * dpr;
            canvas.height = r.height * dpr;
            canvas.style.width = r.width + 'px';
            canvas.style.height = r.height + 'px';
            ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
        }

        function spawn() {
            const r = host.getBoundingClientRect();
            return {
                x: Math.random() * r.width,
                y: r.height + 10,
                vx: -0.15 + Math.random() * 0.3,
                vy: -0.35 - Math.random() * 0.4,
                r: 2 + Math.random() * 2.4,
                a: 0,
                life: 0,
                maxLife: 400 + Math.random() * 400
            };
        }

        function tick() {
            if (!running) return;
            const r = host.getBoundingClientRect();
            ctx.clearRect(0, 0, r.width, r.height);
            if (parts.length < max && Math.random() < 0.06) parts.push(spawn());
            parts.forEach(p => {
                p.life++;
                p.x += p.vx + Math.sin(p.life * 0.02) * 0.2;
                p.y += p.vy;
                p.a = Math.min(1, p.life / 40) * Math.max(0, 1 - p.life / p.maxLife);
                ctx.globalAlpha = p.a * 0.8;
                ctx.fillStyle = '#FFFBF1';
                ctx.beginPath();
                ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
                ctx.fill();
            });
            parts = parts.filter(p => p.life < p.maxLife && p.y > -20);
            raf = requestAnimationFrame(tick);
        }

        resize();
        window.addEventListener('resize', resize);
        return {
            start() { if (running) return; running = true; tick(); },
            stop() { running = false; if (raf) cancelAnimationFrame(raf); }
        };
    }

    // --- Timeline dua tangan: gambar -> mendekat -> sentuh+glow -> idle -> ulang ---
    function startHandTimeline(wrap) {
        let timers = [], running = false;
        function clear() { timers.forEach(clearTimeout); timers = []; }
        function cycle() {
            wrap.classList.remove('draw', 'approach', 'touch', 'idle');
            void wrap.offsetWidth;
            wrap.classList.add('draw');
            timers.push(setTimeout(() => wrap.classList.add('approach'), 1500));
            timers.push(setTimeout(() => { wrap.classList.add('touch'); burstAtHands(wrap); }, 4000));
            timers.push(setTimeout(() => wrap.classList.add('idle'), 4600));
            timers.push(setTimeout(cycle, 8000));
        }
        return {
            start() { if (running) return; running = true; cycle(); },
            stop() { running = false; clear(); }
        };
    }

    function burstAtHands(wrap) {
        if (reduce) return;
        const rect = wrap.getBoundingClientRect();
        const colors = ['#FDE9B4', '#E98BAB', '#F06158', '#FFFBF1'];
        for (let i = 0; i < 14; i++) {
            const b = document.createElement('i');
            b.setAttribute('aria-hidden', 'true');
            Object.assign(b.style, {
                position: 'fixed', left: (rect.left + rect.width / 2) + 'px', top: (rect.top + rect.height * 0.5) + 'px',
                width: '8px', height: '10px', borderRadius: '50% 0 50% 50%', background: colors[i % colors.length],
                zIndex: 50, pointerEvents: 'none',
                transform: `translate(-50%,-50%) rotate(${Math.random()*360}deg)`,
                transition: 'transform 900ms cubic-bezier(.2,.7,.3,1), opacity 900ms ease'
            });
            document.body.appendChild(b);
            requestAnimationFrame(() => {
                const dx = (Math.random() * 160 - 80);
                const dy = -40 - Math.random() * 80;
                b.style.transform = `translate(${dx}px,${dy}px) rotate(${Math.random()*500}deg)`;
                b.style.opacity = '0';
            });
            setTimeout(() => b.remove(), 950);
        }
    }

    function parallax(container) {
        const clouds = [...container.querySelectorAll('.gh-cloud')];
        let mx = 0, my = 0, raf = null, running = false;
        function onMove(e) {
            const r = container.getBoundingClientRect();
            mx = ((e.clientX - r.left) / r.width - 0.5) * 2;
            my = ((e.clientY - r.top) / r.height - 0.5) * 2;
        }
        let t = 0;
        function tick() {
            if (!running) return;
            t += 1;
            clouds.forEach(c => {
                const speed = parseFloat(c.dataset.speed) || 10;
                const drift = (t / (speed * 18)) % 1;
                c.style.transform = `translate(${drift * 40 - 20 + mx * speed * 0.6}px, ${my * speed * 0.3}px)`;
            });
            raf = requestAnimationFrame(tick);
        }
        window.addEventListener('mousemove', onMove, { passive: true });
        return {
            start() { if (running) return; running = true; tick(); },
            stop() { running = false; if (raf) cancelAnimationFrame(raf); }
        };
    }

    let activeControllers = [];
    function teardown() { activeControllers.forEach(c => c.stop()); activeControllers = []; }

    function init() {
        const host = document.getElementById('gh-hero-media');
        if (!host || host.dataset.ghInit) return;
        host.dataset.ghInit = '1';

        const hasVideo = !!window.HERO_VIDEO;
        buildScene(host, hasVideo ? { mp4: window.HERO_VIDEO, webm: window.HERO_VIDEO_WEBM, poster: window.HERO_POSTER } : null);

        if (hasVideo) return; // video menggantikan seluruh adegan kode

        const canvas = host.querySelector('#gh-particles');
        const handsWrap = host.querySelector('#gh-hands');
        const particles = canvas ? startParticles(canvas, host) : null;
        const hands = handsWrap ? startHandTimeline(handsWrap) : null;
        const cloudCtl = parallax(host);

        if (reduce) {
            // Satu frame statis yang bagus: tangan sudah tergambar, terisi, dan bersentuhan.
            if (handsWrap) handsWrap.classList.add('draw', 'approach', 'touch', 'idle');
            return;
        }

        activeControllers = [particles, hands, cloudCtl].filter(Boolean);

        const hero = host.closest('.hero');
        const io = 'IntersectionObserver' in window ? new IntersectionObserver((entries) => {
            entries.forEach(e => {
                if (e.isIntersecting && !document.hidden) activeControllers.forEach(c => c.start());
                else activeControllers.forEach(c => c.stop());
            });
        }, { threshold: 0.05 }) : null;
        if (io && hero) io.observe(hero);
        else activeControllers.forEach(c => c.start());

        document.addEventListener('visibilitychange', () => {
            if (document.hidden) activeControllers.forEach(c => c.stop());
            else if (!io) activeControllers.forEach(c => c.start());
        });
    }

    new MutationObserver(() => {
        const host = document.getElementById('gh-hero-media');
        if (!host) { teardown(); return; }
        init();
    }).observe(document.getElementById('app') || document.body, { childList: true, subtree: true });

    init();
})();
