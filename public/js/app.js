/**
 * SIGN LANGUAGE — 2026 DIGITAL PLATFORM ENGINE
 * Modern Futuristic Sign Language Learning, Practice, Translation & Dictionary
 */

(function () {
    'use strict';

    // Categories Configuration
    const CATS = [
        ['greet', 'BASIC GREETINGS',   'Start with the signs you use every day in standard interaction.'],
        ['conv',  'DAILY CONVERSATION', 'Keep a natural, fluent exchange going with ease and clarity.'],
        ['days',  'DAYS OF THE WEEK',   'Name the days of the week with clear and confident gestures.'],
        ['verbs', 'VERBS',              'Express everyday actions: eat, drink, sleep, study and more.'],
        ['num',   'NUMBERS',            'Count, price, and quantity: the numbers you reach for all the time.'],
        ['alpha', 'ALPHABET',           'Spell names and new words letter by letter with the manual alphabet.']
    ];

    const CN = {
        greet: 'Basic Greetings',
        conv:  'Daily Conversation',
        days:  'Days of the Week',
        verbs: 'Verbs',
        num:   'Numbers',
        alpha: 'Alphabet'
    };

    // State
    let GESTURES = [];
    const app = document.getElementById('app');
    const fav = new Set(JSON.parse(localStorage.getItem('sl_fav') || '[]'));
    const learned = new Set(JSON.parse(localStorage.getItem('sl_learned') || '[]'));
    
    let cameraStream = null;
    let cameraTimer = null;
    let landmarkAnimId = null;
    let practiceKeyHandler = null; // pintasan Spasi halaman Practice
    let audioCtx = null;

    // Helper functions
    const $ = (s, r = document) => r.querySelector(s);
    const $$ = (s, r = document) => [...r.querySelectorAll(s)];
    const pad = n => String(n).padStart(2, '0');
    const escapeHtml = s => String(s ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    const sortGestures = arr => [...arr].sort((a, b) => {
        const sa = Number(a.sort_order ?? 0);
        const sb = Number(b.sort_order ?? 0);
        if (sa > 0 && sb > 0 && sa !== sb) return sa - sb;
        if (sa > 0 && (!sb || sb === 0)) return -1;
        if (sb > 0 && (!sa || sa === 0)) return 1;
        return (a.w || '').localeCompare(b.w || '', undefined, { sensitivity: 'base' });
    });
    const byCat = c => sortGestures(GESTURES.filter(g => g.c === c));
    // Teks arti utama = bahasa Indonesia (BISINDO). Bahasa Inggris hanya pendamping kecil.
    const idw = g => (g && (g.id_word || g.w)) || '';
    const enw = g => (g && g.id_word && g.id_word !== g.w) ? g.w : '';
    // Nilai difficulty di database tetap 'Easy'/'Medium'/'Hard' (dipakai form admin); hanya tampilannya yang diterjemahkan.
    const DIFF_ID = { Easy: 'Mudah', Medium: 'Sedang', Hard: 'Sulit' };
    const diffLabel = d => DIFF_ID[d] || d || '';
    const idwById = (id, fallback) => {
        const g = GESTURES.find(x => x.id === id);
        return g ? idw(g) : fallback;
    };
    // Filter jenjang belajar (abjad / kata / kalimat) untuk halaman Learn
    let learnLevel = 'all';
    const LEVEL_LABELS = { abjad: 'ABJAD', angka: 'ANGKA', kata: 'KATA', kalimat: 'KALIMAT' };
    const byCatL = c => sortGestures(GESTURES.filter(g => g.c === c && (learnLevel === 'all' || g.level === learnLevel)));

    // Audio Feedback Generator using Web Audio API
    function playBeep(type = 'success') {
        try {
            if (!audioCtx) audioCtx = new (window.AudioContext || window.webkitAudioContext)();
            if (audioCtx.state === 'suspended') audioCtx.resume();
            
            const now = audioCtx.currentTime;
            const osc = audioCtx.createOscillator();
            const gain = audioCtx.createGain();
            osc.connect(gain);
            gain.connect(audioCtx.destination);

            if (type === 'success') {
                osc.type = 'sine';
                osc.frequency.setValueAtTime(520, now);
                osc.frequency.exponentialRampToValueAtTime(780, now + 0.15);
                gain.gain.setValueAtTime(0.08, now);
                gain.gain.exponentialRampToValueAtTime(0.001, now + 0.2);
                osc.start(now);
                osc.stop(now + 0.2);
            } else if (type === 'flip') {
                osc.type = 'triangle';
                osc.frequency.setValueAtTime(320, now);
                osc.frequency.exponentialRampToValueAtTime(440, now + 0.08);
                gain.gain.setValueAtTime(0.04, now);
                gain.gain.exponentialRampToValueAtTime(0.001, now + 0.1);
                osc.start(now);
                osc.stop(now + 0.1);
            } else if (type === 'fail') {
                osc.type = 'sawtooth';
                osc.frequency.setValueAtTime(300, now);
                osc.frequency.linearRampToValueAtTime(220, now + 0.2);
                gain.gain.setValueAtTime(0.05, now);
                gain.gain.exponentialRampToValueAtTime(0.001, now + 0.25);
                osc.start(now);
                osc.stop(now + 0.25);
            }
        } catch (e) {
            // Audio context not allowed or unsupported
        }
    }

    // Toast Notification System
    function toast(message) {
        const t = document.getElementById('toast');
        if (!t) return;
        t.textContent = message;
        t.classList.add('on');
        clearTimeout(t._timeout);
        t._timeout = setTimeout(() => t.classList.remove('on'), 2500);
    }

    const AUTH = window.SL_AUTH || { loggedIn: false };

    function saveState() {
        localStorage.setItem('sl_fav', JSON.stringify([...fav]));
        // Pengguna login: progres disimpan di server, bukan di browser.
        if (!AUTH.loggedIn) {
            localStorage.setItem('sl_learned', JSON.stringify([...learned]));
        }
    }

    function apiRequest(url, method, body) {
        return fetch(url, {
            method,
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': AUTH.csrf || ''
            },
            credentials: 'same-origin',
            body: body ? JSON.stringify(body) : undefined
        }).then(res => {
            if (!res.ok) throw new Error('HTTP ' + res.status);
            return res.json();
        });
    }

    function syncLearned(slug, isLearned) {
        if (!AUTH.loggedIn) return;
        apiRequest(`${AUTH.progressUrl}/${encodeURIComponent(slug)}/learned`, isLearned ? 'PUT' : 'DELETE')
            .catch(() => toast('Progres belum tersimpan ke akun. Coba lagi.'));
    }

    function saveSession(correct, total, seconds, level = null, attempts = []) {
        if (!AUTH.loggedIn) return;
        const payload = { total, correct, duration_seconds: seconds };
        if (level) payload.level = level;
        if (attempts && attempts.length) payload.attempts = attempts;
        apiRequest(AUTH.sessionUrl, 'POST', payload)
            .then(() => toast('Sesi latihan dan progres tersimpan di akunmu'))
            .catch(() => toast('Sesi latihan belum tersimpan. Coba lagi.'));
    }

    // =========================================================================
    // GESTURE MEDIA RENDERER
    // Prioritas Tampilan:
    // 1. video_file ada -> tampilkan video (<video>)
    // 2. else image ada -> tampilkan gambar (<img>)
    // 3. else -> ilustrasi vektor SVG lama (warna lewat CSS variable)
    // =========================================================================
    function renderGestureMedia(g, opts = {}) {
        if (!g) return '';
        const video = g.video_file || g.video_path || '';
        const image = g.image || g.image_path || '';
        const vkey = g.vkey || g.visual_key || '';
        const word = g.w || g.word || '';
        const label = idw(g) || word;
        const cls = opts.className || '';
        const isMini = Boolean(opts.mini);

        if (video) {
            const posterAttr = image ? ` poster="${escapeHtml(image)}"` : '';
            return `
            <div class="gesture-media-wrap ${cls}">
                <video class="gesture-media-video"
                    src="${escapeHtml(video)}"
                    ${posterAttr}
                    autoplay
                    loop
                    muted
                    playsinline
                    preload="metadata"
                    aria-label="Video gerakan ${escapeHtml(label)}"
                    onerror="this.onerror=null; this.closest('.gesture-media-wrap').innerHTML='<div class=\\'mini-svg-diagram fallback-avatar\\'><span>${escapeHtml((word || label || '?')[0].toUpperCase())}</span></div>';"
                ></video>
            </div>
            `;
        }

        if (image) {
            return `
            <div class="gesture-media-wrap ${cls}">
                <img class="gesture-media-image"
                    src="${escapeHtml(image)}"
                    alt="Poster gerakan ${escapeHtml(label)}"
                    loading="lazy"
                    onerror="this.onerror=null; this.closest('.gesture-media-wrap').innerHTML='<div class=\\'mini-svg-diagram fallback-avatar\\'><span>${escapeHtml((word || label || '?')[0].toUpperCase())}</span></div>';"
                >
            </div>
            `;
        }

        // CADANGAN: Ilustrasi vektor SVG lama
        return isMini
            ? `<div class="mini-svg-diagram">${getGestureSVG(vkey, word)}</div>`
            : getGestureSVG(vkey, word);
    }

    // =========================================================================
    // HIGH-TECH SVG GESTURE VECTOR DIAGRAM ENGINE (CADANGAN)
    // Renders glowing hand skeletons, motion arrows, and key points
    // Warna diatur melalui CSS Variables (--cy, --mint, --paper)
    // =========================================================================
    function getGestureSVG(vkey, word = '') {
        const strokeColor = 'var(--cy, #18D8D0)';
        const mintColor = 'var(--mint, #A8F3E5)';
        const nodeFill = 'var(--paper, #F4F8F7)';

        // Base hand joint diagrams customized per visual key
        switch (vkey) {
            case 'open_palm_wave':
                return `
                <svg class="sign-svg-diagram" viewBox="0 0 200 200" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <linearGradient id="g_wave" x1="0" y1="0" x2="1" y2="1"><stop offset="0%" stop-color="${strokeColor}"/><stop offset="100%" stop-color="${mintColor}"/></linearGradient>
                    </defs>
                    <!-- Motion Vector Arcs -->
                    <path d="M40 70 Q 70 30 110 35" stroke="${mintColor}" stroke-width="2" stroke-dasharray="4 4" opacity="0.75"/>
                    <path d="M110 35 L104 42 M110 35 L112 45" stroke="${mintColor}" stroke-width="2" stroke-linecap="round"/>
                    <!-- Palm base & Wrist -->
                    <path d="M85 160 L100 135 L115 160" stroke="${strokeColor}" stroke-width="2.5" stroke-linecap="round"/>
                    <!-- Metacarpal plate -->
                    <polygon points="75,135 125,135 130,95 70,95" fill="rgba(24,216,208,0.06)" stroke="${strokeColor}" stroke-width="2"/>
                    <!-- Thumb -->
                    <path d="M75 125 L50 110 L45 85" stroke="${strokeColor}" stroke-width="2.5" stroke-linecap="round"/>
                    <!-- Fingers (B-Hand Open) -->
                    <path d="M78 95 L74 55 L72 32" stroke="${strokeColor}" stroke-width="2.5" stroke-linecap="round"/>
                    <path d="M92 95 L92 48 L92 24" stroke="url(#g_wave)" stroke-width="3" stroke-linecap="round"/>
                    <path d="M107 95 L108 52 L109 28" stroke="${strokeColor}" stroke-width="2.5" stroke-linecap="round"/>
                    <path d="M122 95 L125 60 L128 40" stroke="${strokeColor}" stroke-width="2.5" stroke-linecap="round"/>
                    <!-- Joint Nodes -->
                    <circle cx="72" cy="32" r="3.5" fill="${nodeFill}"/>
                    <circle cx="92" cy="24" r="4.5" fill="${mintColor}"/>
                    <circle cx="109" cy="28" r="3.5" fill="${nodeFill}"/>
                    <circle cx="128" cy="40" r="3.5" fill="${nodeFill}"/>
                    <circle cx="45" cy="85" r="3.5" fill="${strokeColor}"/>
                </svg>`;

            case 'chin_forward':
                return `
                <svg class="sign-svg-diagram" viewBox="0 0 200 200" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <!-- Face profile cue -->
                    <path d="M60 40 Q 55 80 65 110 Q 75 130 90 135" stroke="rgba(102,131,132,0.4)" stroke-width="1.5" stroke-dasharray="3 3"/>
                    <!-- Trajectory Arrow -->
                    <path d="M100 130 Q 140 130 165 145" stroke="${mintColor}" stroke-width="2.5" stroke-dasharray="6 3"/>
                    <polygon points="168,146 156,140 160,152" fill="${mintColor}"/>
                    <!-- Hand flat at chin moving forward -->
                    <rect x="80" y="115" width="45" height="25" rx="5" fill="rgba(24,216,208,0.12)" stroke="${strokeColor}" stroke-width="2"/>
                    <path d="M125 120 L155 115" stroke="${strokeColor}" stroke-width="3" stroke-linecap="round"/>
                    <path d="M125 128 L158 126" stroke="${strokeColor}" stroke-width="3" stroke-linecap="round"/>
                    <circle cx="155" cy="115" r="4" fill="${mintColor}"/>
                    <circle cx="158" cy="126" r="4" fill="${strokeColor}"/>
                </svg>`;

            case 'farewell_fold':
                return `
                <svg class="sign-svg-diagram" viewBox="0 0 200 200" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <!-- Curved Motion arc -->
                    <path d="M140 40 C 120 70, 80 70, 60 40" stroke="${mintColor}" stroke-width="2" stroke-dasharray="4 4"/>
                    <!-- Palm -->
                    <rect x="75" y="90" width="50" height="45" rx="8" fill="rgba(24,216,208,0.08)" stroke="${strokeColor}" stroke-width="2"/>
                    <!-- Folding Fingers -->
                    <path d="M82 90 Q 82 55 95 65" stroke="${strokeColor}" stroke-width="3" stroke-linecap="round"/>
                    <path d="M98 90 Q 98 48 112 58" stroke="${strokeColor}" stroke-width="3" stroke-linecap="round"/>
                    <path d="M114 90 Q 114 55 125 68" stroke="${strokeColor}" stroke-width="3" stroke-linecap="round"/>
                    <!-- Wrist -->
                    <path d="M85 135 L100 165 L115 135" stroke="${strokeColor}" stroke-width="2"/>
                    <circle cx="95" cy="65" r="3.5" fill="${mintColor}"/>
                    <circle cx="112" cy="58" r="4" fill="${mintColor}"/>
                </svg>`;

            case 'fist_nod':
                return `
                <svg class="sign-svg-diagram" viewBox="0 0 200 200" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <!-- Vertical Nod Vector -->
                    <path d="M150 70 L150 130" stroke="${mintColor}" stroke-width="2" stroke-dasharray="4 3"/>
                    <polygon points="150,135 145,124 155,124" fill="${mintColor}"/>
                    <!-- Solid Fist Structure (S-Hand) -->
                    <rect x="70" y="75" width="55" height="50" rx="12" fill="rgba(24,216,208,0.12)" stroke="${strokeColor}" stroke-width="2.5"/>
                    <path d="M72 90 L120 90 M72 105 L120 105" stroke="${strokeColor}" stroke-width="1.5"/>
                    <!-- Thumb across knuckles -->
                    <path d="M68 110 C 65 85, 95 85, 110 95" stroke="${mintColor}" stroke-width="3" stroke-linecap="round"/>
                    <circle cx="110" cy="95" r="4" fill="${nodeFill}"/>
                    <!-- Wrist Joint -->
                    <path d="M80 125 L97 160 L115 125" stroke="${strokeColor}" stroke-width="2"/>
                </svg>`;

            case 'finger_snap_no':
                return `
                <svg class="sign-svg-diagram" viewBox="0 0 200 200" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <!-- Snapping impact burst -->
                    <circle cx="130" cy="100" r="14" stroke="${mintColor}" stroke-width="1.5" stroke-dasharray="3 3"/>
                    <!-- Two Fingers snapping down to thumb -->
                    <path d="M60 130 L90 115" stroke="${strokeColor}" stroke-width="3"/>
                    <path d="M90 115 L130 96" stroke="${strokeColor}" stroke-width="3" stroke-linecap="round"/>
                    <path d="M88 122 L128 104" stroke="${strokeColor}" stroke-width="3" stroke-linecap="round"/>
                    <!-- Thumb -->
                    <path d="M85 140 L115 125 L128 106" stroke="${mintColor}" stroke-width="3" stroke-linecap="round"/>
                    <circle cx="130" cy="100" r="4.5" fill="${mintColor}"/>
                </svg>`;

            case 'palm_fist_lift':
                return `
                <svg class="sign-svg-diagram" viewBox="0 0 200 200" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <!-- Upward lift arrow -->
                    <path d="M150 140 L150 60" stroke="${mintColor}" stroke-width="2.5" stroke-dasharray="4 3"/>
                    <polygon points="150,52 144,66 156,66" fill="${mintColor}"/>
                    <!-- Flat supporting base palm -->
                    <path d="M50 145 L150 145 L145 155 L55 155 Z" fill="rgba(24,216,208,0.15)" stroke="${strokeColor}" stroke-width="2"/>
                    <!-- Thumbs-up fist resting on palm -->
                    <rect x="80" y="95" width="40" height="45" rx="8" fill="rgba(14,59,61,0.6)" stroke="${strokeColor}" stroke-width="2"/>
                    <!-- Vertical Thumb -->
                    <path d="M95 95 L95 60 L105 60 L105 95" fill="rgba(24,216,208,0.2)" stroke="${strokeColor}" stroke-width="2.5" stroke-linecap="round"/>
                    <circle cx="100" cy="58" r="4.5" fill="${mintColor}"/>
                </svg>`;

            case 'blade_chop_stop':
                return `
                <svg class="sign-svg-diagram" viewBox="0 0 200 200" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <!-- Downward chop action -->
                    <path d="M100 45 L100 115" stroke="${strokeColor}" stroke-width="2.5" stroke-dasharray="4 2"/>
                    <!-- Horizontal base palm -->
                    <rect x="50" y="125" width="100" height="18" rx="4" fill="rgba(24,216,208,0.12)" stroke="${strokeColor}" stroke-width="2"/>
                    <!-- Vertical Chopping Hand -->
                    <path d="M92 40 L92 124 L108 124 L108 40 Z" fill="rgba(24,216,208,0.2)" stroke="${mintColor}" stroke-width="2.5"/>
                    <circle cx="100" cy="125" r="5" fill="#FF7D6B"/>
                </svg>`;

            default:
                // Default high-tech geometric hand skeleton
                return `
                <svg class="sign-svg-diagram" viewBox="0 0 200 200" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="100" cy="100" r="75" stroke="rgba(24,216,208,0.15)" stroke-width="1" stroke-dasharray="4 4"/>
                    <circle cx="100" cy="100" r="45" stroke="rgba(168,243,229,0.15)" stroke-width="1"/>
                    <!-- Hand Landmark skeleton -->
                    <path d="M100 155 L100 110 M100 110 L75 80 M100 110 L92 60 M100 110 L108 60 M100 110 L125 80" stroke="${strokeColor}" stroke-width="2" stroke-linecap="round"/>
                    <circle cx="100" cy="155" r="4" fill="${strokeColor}"/>
                    <circle cx="100" cy="110" r="5" fill="${mintColor}"/>
                    <circle cx="75" cy="80" r="4" fill="${nodeFill}"/>
                    <circle cx="92" cy="60" r="4" fill="${nodeFill}"/>
                    <circle cx="108" cy="60" r="4" fill="${nodeFill}"/>
                    <circle cx="125" cy="80" r="4" fill="${nodeFill}"/>
                    <!-- Word text watermark -->
                    <text x="100" y="180" fill="var(--t3)" font-family="Space Grotesk" font-size="11" letter-spacing="2" text-anchor="middle">${word}</text>
                </svg>`;
        }
    }

    // =========================================================================
    // WEBCAM & AI VISION LANDMARK SIMULATOR
    // Real camera feed + interactive canvas landmark tracking layer
    // =========================================================================
    function stopCamera() {
        if (cameraTimer) {
            clearInterval(cameraTimer);
            cameraTimer = null;
        }
        if (landmarkAnimId) {
            cancelAnimationFrame(landmarkAnimId);
            landmarkAnimId = null;
        }
        if (cameraStream) {
            cameraStream.getTracks().forEach(t => t.stop());
            cameraStream = null;
        }
    }

    let mediapipeHands = null;
    let cameraUtil = null;
    window.latestRealGesture = null;

    // Heuristic Gesture Classifier based on 21 MediaPipe Hand Landmarks
    function classifyHandLandmarks(landmarks, secondHand = null) {
        if (!landmarks || landmarks.length < 21) return null;

        // Landmarks: 0=wrist, 4=thumb_tip, 8=index_tip, 12=middle_tip, 16=ring_tip, 20=pinky_tip
        // PIPs: 2=thumb_mcp, 6=index_pip, 10=middle_pip, 14=ring_pip, 18=pinky_pip
        const wrist = landmarks[0];
        const isExtended = (tipIdx, pipIdx) => {
            const dTip = Math.hypot(landmarks[tipIdx].x - wrist.x, landmarks[tipIdx].y - wrist.y);
            const dPip = Math.hypot(landmarks[pipIdx].x - wrist.x, landmarks[pipIdx].y - wrist.y);
            return dTip > dPip * 1.15;
        };

        const indexUp = isExtended(8, 6);
        const middleUp = isExtended(12, 10);
        const ringUp = isExtended(16, 14);
        const pinkyUp = isExtended(20, 18);

        const distThumbPinky = Math.hypot(landmarks[4].x - landmarks[17].x, landmarks[4].y - landmarks[17].y);
        const distThumbBase = Math.hypot(landmarks[2].x - landmarks[17].x, landmarks[2].y - landmarks[17].y);
        const thumbOut = distThumbPinky > distThumbBase * 1.25;

        const thumbUpward = landmarks[4].y < landmarks[3].y && landmarks[3].y < landmarks[2].y;
        const indexThumbDist = Math.hypot(landmarks[4].x - landmarks[8].x, landmarks[4].y - landmarks[8].y);
        const middleThumbDist = Math.hypot(landmarks[4].x - landmarks[12].x, landmarks[4].y - landmarks[12].y);
        const indexMiddleDist = Math.hypot(landmarks[8].x - landmarks[12].x, landmarks[8].y - landmarks[12].y);

        // 1. Dua tangan bertemu / salam / terima kasih (🙏)
        if (secondHand && secondHand.length >= 21) {
            const distWrists = Math.hypot(wrist.x - secondHand[0].x, wrist.y - secondHand[0].y);
            if (distWrists < 0.28) {
                return { id: 'thank-you', w: 'THANK YOU', id_word: 'TERIMA KASIH', e: '🙏', conf: 96 };
            }
        }

        // 2. HALO (👋) — Telapak tangan terbuka 5 jari mengarah ke kamera dengan jari terbuka renggang
        if (indexUp && middleUp && ringUp && pinkyUp && thumbOut && indexMiddleDist > 0.032) {
            return { id: 'hello', w: 'HELLO', id_word: 'HALO', e: '👋', conf: 94 };
        }

        // 3. BERHENTI (✋) — Telapak tangan terbuka 5 jari rapat menghadap ke depan
        if (indexUp && middleUp && ringUp && pinkyUp && indexMiddleDist <= 0.032) {
            return { id: 'stop', w: 'STOP', id_word: 'BERHENTI', e: '✋', conf: 94 };
        }

        // 4. BAGUS / BAIK (👌) — OK Gesture (jempol & telunjuk bersentuhan, 3 jari lain naik)
        if (indexThumbDist < 0.075 && middleUp && ringUp) {
            return { id: 'good', w: 'GOOD', id_word: 'BAGUS / BAIK', e: '👌', conf: 97 };
        }

        // 5. TERIMA KASIH / JEMPOL (👍) — Jempol ke atas, jari lain menggenggam
        if (thumbUpward && !indexUp && !middleUp && !ringUp && !pinkyUp) {
            return { id: 'thank-you', w: 'THANK YOU', id_word: 'TERIMA KASIH', e: '👍', conf: 96 };
        }

        // 6. TOLONG (🤙) — Shaka / Call me sign (jempol & kelingking terbuka, jari tengah menutup)
        if (thumbOut && pinkyUp && !indexUp && !middleUp && !ringUp) {
            return { id: 'help', w: 'HELP', id_word: 'TOLONG', e: '🤙', conf: 94 };
        }

        // 7. SENANG BERTEMU DENGANMU / CINTA (🤟) — ILY sign (jempol, telunjuk, kelingking naik)
        if (indexUp && pinkyUp && thumbOut && !middleUp && !ringUp) {
            return { id: 'nice-to-meet-you', w: 'NICE TO MEET YOU', id_word: 'SENANG BERTEMU DENGANMU', e: '🤟', conf: 95 };
        }

        // 8. APA KABAR / DUA (✌️) — Peace / V sign
        if (indexUp && middleUp && !ringUp && !pinkyUp && !thumbOut && indexMiddleDist > 0.04) {
            return { id: 'how-are-you', w: 'HOW ARE YOU', id_word: 'APA KABAR', e: '✌️', conf: 96 };
        }

        // 9. AIR (💧) — W sign (3 jari tengah berdiri: telunjuk, tengah, manis)
        if (indexUp && middleUp && ringUp && !pinkyUp && !thumbOut) {
            return { id: 'water', w: 'WATER', id_word: 'AIR', e: '💧', conf: 94 };
        }

        // 10. HARI INI (☝️) — Satu jari telunjuk menunjuk
        if (indexUp && !middleUp && !ringUp && !pinkyUp && !thumbOut) {
            return { id: 'today', w: 'TODAY', id_word: 'HARI INI', e: '☝️', conf: 93 };
        }

        // 11. YA (✊) — Kepalan tangan (semua jari menggenggam)
        if (!indexUp && !middleUp && !ringUp && !pinkyUp && !thumbOut) {
            return { id: 'yes', w: 'YES', id_word: 'YA', e: '✊', conf: 90 };
        }

        // 12. MAKAN (🍽️) — Jari-jari menguncup di dekat wajah/mulut
        if (indexThumbDist < 0.065 && middleThumbDist < 0.065 && !pinkyUp) {
            return { id: 'eat', w: 'EAT', id_word: 'MAKAN', e: '🍽️', conf: 92 };
        }

        // 13. MINUM (🥤) — Tangan melengkung seperti cangkir (C-shape)
        if (thumbOut && !indexUp && landmarks[4].y > landmarks[8].y && indexThumbDist > 0.07 && indexThumbDist < 0.2) {
            return { id: 'drink', w: 'DRINK', id_word: 'MINUM', e: '🥤', conf: 91 };
        }

        // 14. TIDUR (😴) — Telapak tangan horizontal miring
        const isHorizontal = Math.abs(landmarks[0].y - landmarks[9].y) < Math.abs(landmarks[0].x - landmarks[9].x);
        if (isHorizontal && indexUp && middleUp && ringUp) {
            return { id: 'sleep', w: 'SLEEP', id_word: 'TIDUR', e: '😴', conf: 92 };
        }

        return null;
    }

    // =========================================================================
    // MODEL HURUF (MediaPipe Tasks Vision + MLP, semua jalan di browser)
    // =========================================================================
    const MODEL_BASE = window.MODEL_BASE || '/models';
    const TASKS_VER = '0.10.14';
    const TASKS_CDN = `https://cdn.jsdelivr.net/npm/@mediapipe/tasks-vision@${TASKS_VER}`;
    const LETTER = { ready: false, model: null, loading: null, landmarker: null, lmLoading: null };

    // Huruf target diambil dari kata kartu (w). Sesuaikan kalau kartu abjadmu menyimpan huruf di field lain.
    const letterOf = g => {
        const s = ((g && g.w) || '').trim().toUpperCase();
        return s.length === 1 ? s : '';
    };

    // Model angka (dilatih terpisah: python ml/scripts/train.py angka)
    const NUM = { ready: false, model: null, loading: null };
    const NUMBER_WORDS = { ONE: '1', TWO: '2', THREE: '3', FOUR: '4', FIVE: '5', SIX: '6', SEVEN: '7', EIGHT: '8', NINE: '9', TEN: '10' };
    const numberOf = g => (g && g.level === 'angka') ? (NUMBER_WORDS[((g.w || '').trim().toUpperCase())] || '') : '';

    // Model gerakan dinamis (kata-kata: hello, thank-you, eat, drink, study, come, dsb.)
    const DYN = { ready: false, loading: null };
    function loadDynamicModel() {
        if (DYN.loading) return DYN.loading;
        if (typeof DynamicGesture !== 'undefined' && DynamicGesture.ready) {
            DYN.ready = true;
            return Promise.resolve(true);
        }
        if (typeof DynamicGesture !== 'undefined') {
            DYN.loading = DynamicGesture.load(`${MODEL_BASE}/dynamic_model.json`)
                .then(ok => { DYN.ready = ok; return ok; })
                .catch(e => { console.warn('Model kata dinamis belum tersedia:', e); DYN.loading = null; return false; });
            return DYN.loading;
        }
        return Promise.resolve(false);
    }

    window.recogMode = 'kata'; // 'kata' (default Translate), 'huruf' (abjad), atau 'angka'

    function loadNumberModel() {
        if (NUM.loading) return NUM.loading;
        NUM.loading = fetch(`${MODEL_BASE}/number_model.json`)
            .then(r => { if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); })
            .then(m => { NUM.model = m; NUM.ready = true; return true; })
            .catch(e => { console.warn('Model angka belum tersedia:', e); NUM.loading = null; return false; });
        return NUM.loading;
    }

    function buildFeatures(res) {
        const hands = res.landmarks;
        const sides = res.handedness || res.handednesses;
        const all = hands.flat();
        let cx = 0, cy = 0, cz = 0;
        all.forEach(p => { cx += p.x; cy += p.y; cz += p.z; });
        cx /= all.length; cy /= all.length; cz /= all.length;
        let scale = 0;
        all.forEach(p => { scale = Math.max(scale, Math.abs(p.x - cx), Math.abs(p.y - cy)); });
        if (!scale) scale = 1;
        const slots = { Left: new Array(63).fill(0), Right: new Array(63).fill(0) };
        hands.forEach((h, k) => {
            const flat = [];
            h.forEach(p => flat.push((p.x - cx) / scale, (p.y - cy) / scale, (p.z - cz) / scale));
            slots[sides[k][0].categoryName] = flat;
        });
        return slots.Left.concat(slots.Right);
    }

    function predictLetter(x, m = LETTER.model) {
        let a = x.map((v, i) => (v - m.mean[i]) / m.scale[i]);
        m.layers.forEach((L, li) => {
            const out = L.b.slice();
            for (let i = 0; i < a.length; i++) {
                const ai = a[i];
                if (ai === 0) continue;
                const row = L.W[i];
                for (let j = 0; j < out.length; j++) out[j] += ai * row[j];
            }
            a = li < m.layers.length - 1 ? out.map(v => Math.max(0, v)) : out;
        });
        const mx = Math.max(...a);
        const ex = a.map(v => Math.exp(v - mx));
        const sum = ex.reduce((s, v) => s + v, 0);
        let k = 0;
        ex.forEach((v, i) => { if (v > ex[k]) k = i; });
        return { letter: m.labels[k], p: ex[k] / sum };
    }

    function loadLetterModel() {
        if (LETTER.loading) return LETTER.loading;
        LETTER.loading = fetch(`${MODEL_BASE}/letter_model.json`)
            .then(r => { if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); })
            .then(m => { LETTER.model = m; LETTER.ready = true; return true; })
            .catch(e => { console.warn('Model huruf belum tersedia:', e); LETTER.loading = null; return false; });
        return LETTER.loading;
    }

    function loadHandLandmarker() {
        if (LETTER.lmLoading) return LETTER.lmLoading;
        LETTER.lmLoading = (async () => {
            try {
                const vision = await import(`${TASKS_CDN}/vision_bundle.mjs`);
                const files = await vision.FilesetResolver.forVisionTasks(`${TASKS_CDN}/wasm`);
                const make = delegate => vision.HandLandmarker.createFromOptions(files, {
                    baseOptions: { modelAssetPath: `${MODEL_BASE}/hand_landmarker.task`, delegate },
                    runningMode: 'VIDEO',
                    numHands: 2
                });
                try { LETTER.landmarker = await make('GPU'); }
                catch (e) { LETTER.landmarker = await make('CPU'); }
                return LETTER.landmarker;
            } catch (e) {
                console.warn('HandLandmarker gagal dimuat:', e);
                LETTER.lmLoading = null;
                return null;
            }
        })();
        return LETTER.lmLoading;
    }

    const HAND_LINKS = [
        [0,1],[1,2],[2,3],[3,4],[0,5],[5,6],[6,7],[7,8],[5,9],[9,10],[10,11],[11,12],
        [9,13],[13,14],[14,15],[15,16],[13,17],[17,18],[18,19],[19,20],[0,17]
    ];

    // Tracking sungguhan. Mengembalikan false kalau gagal, supaya jatuh ke mode simulasi.
    async function startRealTracking(containerEl, videoEl) {
        const lm = await loadHandLandmarker();
        if (!lm) return false;
        await Promise.all([loadLetterModel(), loadNumberModel(), loadDynamicModel()]);
        if (!cameraStream) return true;

        let canvas = containerEl.querySelector('.cam-landmark-canvas');
        if (!canvas) {
            canvas = document.createElement('canvas');
            canvas.className = 'cam-landmark-canvas';
            containerEl.appendChild(canvas);
        }
        const ctx = canvas.getContext('2d');
        const recent = [];
        let lastVideoTime = -1;

        const loop = () => {
            if (!containerEl.isConnected || !cameraStream) return;
            landmarkAnimId = requestAnimationFrame(loop);
            if (videoEl.readyState < 2 || videoEl.currentTime === lastVideoTime) return;
            lastVideoTime = videoEl.currentTime;

            const res = lm.detectForVideo(videoEl, performance.now());
            const w = canvas.width = containerEl.clientWidth || 640;
            const h = canvas.height = containerEl.clientHeight || 400;
            ctx.clearRect(0, 0, w, h);
            const label = $('#trans-detected-label');

            if (res.landmarks && res.landmarks.length) {
                res.landmarks.forEach(lms => {
                    ctx.strokeStyle = 'rgba(0, 78, 47, 0.85)';
                    ctx.lineWidth = 3;
                    ctx.lineCap = 'round';
                    HAND_LINKS.forEach(([i, j]) => {
                        ctx.beginPath();
                        ctx.moveTo(lms[i].x * w, lms[i].y * h);
                        ctx.lineTo(lms[j].x * w, lms[j].y * h);
                        ctx.stroke();
                    });
                    lms.forEach((pt, idx) => {
                        ctx.beginPath();
                        ctx.arc(pt.x * w, pt.y * h, [4, 8, 12, 16, 20].includes(idx) ? 6 : 4, 0, Math.PI * 2);
                        ctx.fillStyle = [4, 8, 12, 16, 20].includes(idx) ? '#EEC6AA' : '#004B71';
                        ctx.fill();
                    });
                });

                const useNum = window.recogMode === 'angka' && NUM.ready;
                const useAlpha = window.recogMode === 'huruf' && LETTER.ready;
                if (useNum || useAlpha) {
                    if (recent.mode !== window.recogMode) { recent.length = 0; recent.mode = window.recogMode; }
                    recent.push(predictLetter(buildFeatures(res), useNum ? NUM.model : LETTER.model));
                    if (recent.length > 6) recent.shift();
                    const counts = {};
                    recent.forEach(r => { counts[r.letter] = (counts[r.letter] || 0) + 1; });
                    const top = Object.keys(counts).sort((a, b) => counts[b] - counts[a])[0];
                    const ps = recent.filter(r => r.letter === top).map(r => r.p);
                    const conf = Math.round((ps.reduce((s, v) => s + v, 0) / ps.length) * 100);
                    const card = useNum ? GESTURES.find(g => numberOf(g) === top) : GESTURES.find(g => g.level === 'abjad' && letterOf(g) === top);
                    window.latestRealGesture = { id: card ? card.id : top.toLowerCase(), w: top, letter: top, id_word: card ? idw(card) : top, e: card ? card.e : '', conf };
                } else {
                    // MODE: KATA (SEMUA KATA)
                    if (window.DynamicGesture && window.DynamicGesture.ready) {
                        window.DynamicGesture.push(res);
                    }

                    // 1. Cek heuristik bentuk tangan (mis. Halo, Berhenti, Bagus, Terima Kasih, dsb.)
                    let detected = classifyHandLandmarks(res.landmarks[0], res.landmarks.length > 1 ? res.landmarks[1] : null);

                    // 2. Jika tidak ada bentuk ikonik statis, cek model neural network dinamis
                    if (!detected && window.DynamicGesture && window.DynamicGesture.ready) {
                        const dyn = window.DynamicGesture.predict();
                        if (dyn && dyn.p >= 0.65) {
                            const card = GESTURES.find(g => g.id === dyn.label || g.slug === dyn.label || ((g.w || '').toLowerCase() === dyn.label.toLowerCase()));
                            if (card) {
                                detected = {
                                    id: card.id,
                                    w: card.w,
                                    id_word: idw(card),
                                    e: card.e || '💬',
                                    conf: Math.round(dyn.p * 100)
                                };
                            }
                        }
                    }

                    window.latestRealGesture = detected;
                }
                const g = window.latestRealGesture;
                if (label && g) {
                    const text = g.id_word || idwById(g.id, g.w) || g.letter || g.w;
                    label.textContent = `${text} (${g.conf}% KEYAKINAN)`;
                }
            } else {
                recent.length = 0;
                window.latestRealGesture = null;
                if (label && label.textContent !== 'READY TO RECOGNIZE') label.textContent = 'MENCARI TANGAN...';
            }
        };
        loop();
        return true;
    }

    async function initCamera(containerEl) {
        if (!containerEl) return false;
        containerEl.querySelectorAll('.cam-error-overlay').forEach(v => v.remove());
        try {
            // Stream yang masih hidup dipakai ulang (mis. pindah langkah Practice).
            // Yang harus dibuat ulang hanya elemen <video> di viewport yang baru.
            const alive = cameraStream && cameraStream.getTracks().some(t => t.readyState === 'live');
            if (!alive) {
                if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                    throw new Error('BROWSER_UNSUPPORTED');
                }
                try {
                    cameraStream = await navigator.mediaDevices.getUserMedia({
                        video: { facingMode: 'user', width: { ideal: 1280 }, height: { ideal: 720 } }
                    });
                } catch (constraintErr) {
                    if (constraintErr.name === 'NotAllowedError' || constraintErr.name === 'PermissionDeniedError') {
                        throw constraintErr;
                    }
                    console.warn('Constraint kamera ideal gagal, mencoba fallback { video: true }...', constraintErr);
                    cameraStream = await navigator.mediaDevices.getUserMedia({ video: true });
                }
            }
            containerEl.querySelectorAll('video').forEach(v => v.remove());
            containerEl.querySelectorAll('.cam-error-overlay').forEach(v => v.remove());
            if (landmarkAnimId) { cancelAnimationFrame(landmarkAnimId); landmarkAnimId = null; }
            const video = document.createElement('video');
            video.autoplay = true;
            video.muted = true;
            video.playsInline = true;
            video.setAttribute('aria-label', 'Live webcam feed for gesture detection');
            video.srcObject = cameraStream;
            containerEl.prepend(video);
            await video.play().catch(() => {});

            const simBadge = containerEl.querySelector('#btn-toggle-sim');
            if (simBadge) {
                simBadge.textContent = 'MODE: KAMERA AKTIF';
                simBadge.style.background = '';
                simBadge.style.color = '';
            }

            startLandmarkTracking(containerEl, video);
            return true;
        } catch (e) {
            console.error('initCamera gagal:', e);
            containerEl.querySelectorAll('video').forEach(v => v.remove());

            let errorTitle = 'Kamera Tidak Dapat Dibuka';
            let errorDesc = 'Terjadi kendala saat mengakses webcam.';

            if (e.message === 'BROWSER_UNSUPPORTED' || !navigator.mediaDevices) {
                errorTitle = 'Browser Tidak Mengizinkan Kamera';
                errorDesc = 'Akses kamera tidak diizinkan di URL ini. Pastikan membuka via http://127.0.0.1:8000 atau https.';
            } else if (e.name === 'NotReadableError' || e.name === 'TrackStartError') {
                errorTitle = 'Kamera Sedang Digunakan Aplikasi Lain';
                errorDesc = 'Webcam sedang dipakai oleh aplikasi lain (seperti tab <b>Google Meet</b>, Zoom, atau aplikasi Kamera Windows). Silakan matikan kamera di Google Meet/aplikasi tersebut, lalu coba lagi.';
            } else if (e.name === 'NotAllowedError' || e.name === 'PermissionDeniedError') {
                errorTitle = 'Izin Kamera Diblokir Browser';
                errorDesc = 'Izin webcam belum diberikan. Klik ikon <b>Gembok / Setelan Situs</b> di samping address bar Chrome (127.0.0.1:8000), pilih <b>Izinkan (Allow) Kamera</b>, lalu klik Coba Lagi.';
            } else if (e.name === 'NotFoundError' || e.name === 'DevicesNotFoundError') {
                errorTitle = 'Kamera / Webcam Tidak Ditemukan';
                errorDesc = 'Perangkat webcam tidak terdeteksi di laptop/PC ini. Pastikan kamera terpasang dan aktif.';
            }

            toast(errorTitle);

            const simBadge = containerEl.querySelector('#btn-toggle-sim');
            if (simBadge) {
                simBadge.textContent = 'MODE: KAMERA ERROR';
                simBadge.style.background = 'rgba(239, 68, 68, 0.15)';
                simBadge.style.color = '#b91c1c';
            }

            containerEl.querySelectorAll('.cam-error-overlay').forEach(v => v.remove());
            const errorOverlay = document.createElement('div');
            errorOverlay.className = 'cam-error-overlay';
            errorOverlay.style.cssText = 'position:absolute;inset:0;background:rgba(253,248,240,0.96);z-index:10;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:24px;text-align:center;color:var(--ink);border-radius:10px;';
            errorOverlay.innerHTML = `
                <div style="font-size:36px;margin-bottom:8px">📷⚠️</div>
                <h3 style="font-size:17px;font-weight:700;margin:0 0 8px;color:#c0392b">${escapeHtml(errorTitle)}</h3>
                <p style="font-size:13px;line-height:1.5;max-width:380px;margin:0 0 16px;color:var(--t2);">${errorDesc}</p>
                <div style="display:flex;gap:8px;flex-wrap:wrap;justify-content:center;">
                    <button class="btn p" id="btn-cam-retry" style="font-size:12px;padding:8px 16px;">🔄 COBA BUKA KAMERA LAGI</button>
                    <button class="btn" id="btn-cam-usesim" style="font-size:12px;padding:8px 14px;">GUNAKAN MODE SIMULASI</button>
                </div>
            `;
            containerEl.appendChild(errorOverlay);

            const retryBtn = errorOverlay.querySelector('#btn-cam-retry');
            if (retryBtn) {
                retryBtn.onclick = () => {
                    errorOverlay.remove();
                    initCamera(containerEl);
                };
            }
            const simBtn = errorOverlay.querySelector('#btn-cam-usesim');
            if (simBtn) {
                simBtn.onclick = () => {
                    errorOverlay.remove();
                    if (simBadge) {
                        simBadge.textContent = 'MODE: SIMULASI AKTIF';
                        simBadge.style.background = '#e0f2fe';
                        simBadge.style.color = '#0369a1';
                    }
                    startLandmarkTracking(containerEl, null, true);
                    toast('Mode simulasi aktif.');
                };
            }

            return false;
        }
    }

    // MediaPipe & Canvas Landmark Skeleton Overlay
    function startLandmarkTracking(containerEl, videoEl = null, isSimulatedOnly = false) {
        if (videoEl && !isSimulatedOnly) {
            startRealTracking(containerEl, videoEl).then(ok => {
                if (!ok) startLandmarkTracking(containerEl, videoEl, true);
            });
        return;
        }
        let canvas = containerEl.querySelector('.cam-landmark-canvas');
        if (!canvas) {
            canvas = document.createElement('canvas');
            canvas.className = 'cam-landmark-canvas';
            containerEl.appendChild(canvas);
        }
        const ctx = canvas.getContext('2d');
        let t = 0;
        let isUsingMediaPipe = false;

        // Try initializing MediaPipe Hands
        if (videoEl && typeof window.Hands !== 'undefined' && !isSimulatedOnly) {
            try {
                if (!mediapipeHands) {
                    mediapipeHands = new window.Hands({
                        locateFile: (file) => `https://cdn.jsdelivr.net/npm/@mediapipe/hands/${file}`
                    });
                    mediapipeHands.setOptions({
                        maxNumHands: 2,
                        modelComplexity: 1,
                        minDetectionConfidence: 0.5,
                        minTrackingConfidence: 0.5
                    });
                }

                mediapipeHands.onResults((results) => {
                    if (!containerEl.isConnected) return;
                    const w = canvas.width = containerEl.clientWidth || 640;
                    const h = canvas.height = containerEl.clientHeight || 400;
                    ctx.clearRect(0, 0, w, h);

                    const vw = videoEl.videoWidth || w;
                    const vh = videoEl.videoHeight || h;
                    const s = Math.max(w / vw, h / vh);
                    const ox = (w - vw * s) / 2;
                    const oy = (h - vh * s) / 2;
                    const px = p => p.x * vw * s + ox;
                    const py = p => p.y * vh * s + oy;

                    if (results.multiHandLandmarks && results.multiHandLandmarks.length > 0) {
                        const lms = results.multiHandLandmarks[0];
                        
                        // Draw real hand bones
                        const connections = [
                            [0,1],[1,2],[2,3],[3,4],
                            [0,5],[5,6],[6,7],[7,8],
                            [5,9],[9,10],[10,11],[11,12],
                            [9,13],[13,14],[14,15],[15,16],
                            [13,17],[17,18],[18,19],[19,20],[0,17]
                        ];

                        ctx.strokeStyle = 'rgba(24, 216, 208, 0.85)';
                        ctx.lineWidth = 3;
                        ctx.lineCap = 'round';
                        connections.forEach(([i, j]) => {
                            ctx.beginPath();
                            ctx.moveTo(px(lms[i]), py(lms[i]));
                            ctx.lineTo(px(lms[j]), py(lms[j]));                      
                            ctx.stroke();
                        });

                        // Draw real hand landmark points
                        lms.forEach((pt, idx) => {
                            ctx.beginPath();
                            ctx.arc(px(pt), py(pt), [4, 8, 12, 16, 20].includes(idx) ? 6 : 4, 0, Math.PI * 2);
                            ctx.fillStyle = [4, 8, 12, 16, 20].includes(idx) ? '#A8F3E5' : '#18D8D0';
                            ctx.shadowColor = '#18D8D0';
                            ctx.shadowBlur = 10;
                            ctx.fill();
                            ctx.shadowBlur = 0;
                        });

                        // Classify gesture from real hand keypoints
                        const classified = classifyHandLandmarks(lms);
                        if (classified) {
                            window.latestRealGesture = classified;
                            const label = $('#trans-detected-label');
                            if (label) {
                                label.textContent = `${idwById(classified.id, classified.w)} (${classified.conf}% CONFIDENCE)`;
                            }
                        }
                    } else {
                        window.latestRealGesture = null;
                        const label = $('#trans-detected-label');
                        if (label && label.textContent !== 'READY TO RECOGNIZE') {
                            label.textContent = 'SEARCHING FOR HAND...';
                        }
                    }
                });

                if (typeof window.Camera !== 'undefined') {
                    cameraUtil = new window.Camera(videoEl, {
                        onFrame: async () => {
                            if (videoEl && containerEl.isConnected) {
                                await mediapipeHands.send({ image: videoEl });
                            }
                        },
                        width: 640,
                        height: 480
                    });
                    cameraUtil.start();
                    isUsingMediaPipe = true;
                }
            } catch (err) {
                console.warn('MediaPipe error, falling back to simulated landmarks:', err);
                isUsingMediaPipe = false;
            }
        }

        // Fallback procedural animation loop
        function renderFrame() {
            if (!containerEl.isConnected) return;
            if (isUsingMediaPipe) return; // MediaPipe handles rendering when active

            const w = canvas.width = containerEl.clientWidth || 640;
            const h = canvas.height = containerEl.clientHeight || 400;
            ctx.clearRect(0, 0, w, h);

            t += 0.04;
            const centerX = w * 0.5 + Math.sin(t * 0.8) * (w * 0.04);
            const centerY = h * 0.52 + Math.cos(t * 1.1) * (h * 0.03);
            const scale = Math.min(w, h) * 0.42;

            // Hand keypoints (21 landmarks)
            const wrist = { x: centerX, y: centerY + scale * 0.5 };
            const palm = { x: centerX, y: centerY };
            const thumbMCP = { x: centerX - scale * 0.28, y: centerY + scale * 0.15 };
            const thumbTip = { x: centerX - scale * 0.42 + Math.sin(t * 1.5) * 6, y: centerY - scale * 0.08 };
            
            const indexMCP = { x: centerX - scale * 0.18, y: centerY - scale * 0.12 };
            const indexTip = { x: centerX - scale * 0.18 + Math.cos(t) * 4, y: centerY - scale * 0.52 };

            const midMCP = { x: centerX, y: centerY - scale * 0.16 };
            const midTip = { x: centerX, y: centerY - scale * 0.56 };

            const ringMCP = { x: centerX + scale * 0.16, y: centerY - scale * 0.13 };
            const ringTip = { x: centerX + scale * 0.16, y: centerY - scale * 0.48 };

            const pinkyMCP = { x: centerX + scale * 0.3, y: centerY - scale * 0.06 };
            const pinkyTip = { x: centerX + scale * 0.32, y: centerY - scale * 0.38 };

            const bones = [
                [wrist, palm],
                [palm, thumbMCP], [thumbMCP, thumbTip],
                [palm, indexMCP], [indexMCP, indexTip],
                [palm, midMCP], [midMCP, midTip],
                [palm, ringMCP], [ringMCP, ringTip],
                [palm, pinkyMCP], [pinkyMCP, pinkyTip]
            ];

            // Draw connecting skeleton bones
            ctx.strokeStyle = 'rgba(24, 216, 208, 0.45)';
            ctx.lineWidth = 2.5;
            ctx.lineCap = 'round';
            bones.forEach(([p1, p2]) => {
                ctx.beginPath();
                ctx.moveTo(p1.x, p1.y);
                ctx.lineTo(p2.x, p2.y);
                ctx.stroke();
            });

            // Draw glowing joint nodes
            const points = [wrist, palm, thumbMCP, thumbTip, indexMCP, indexTip, midMCP, midTip, ringMCP, ringTip, pinkyMCP, pinkyTip];
            points.forEach((pt, idx) => {
                ctx.beginPath();
                ctx.arc(pt.x, pt.y, idx % 2 === 1 ? 5 : 3.5, 0, Math.PI * 2);
                ctx.fillStyle = idx % 2 === 1 ? '#A8F3E5' : '#18D8D0';
                ctx.shadowColor = '#18D8D0';
                ctx.shadowBlur = 10;
                ctx.fill();
                ctx.shadowBlur = 0;
            });

            landmarkAnimId = requestAnimationFrame(renderFrame);
        }

        if (!isUsingMediaPipe) {
            renderFrame();
        }
    }

    // =========================================================================
    // ROUTING SYSTEM (Single Page Architecture with Hash Navigation)
    // =========================================================================
    function route() {
        stopCamera();
        closeModal();
        const hash = location.hash || '#/';
        const [page, param, slug] = hash.slice(2).split('/');

        // Highlight active nav links
        $$('nav.top a, .bn a').forEach(el => {
            const target = el.getAttribute('data-page');
            el.classList.toggle('on', target === (page || ''));
        });

        const routes = {
            '': home,
            'learn': () => learn(param, slug),
            'practice': () => practice(param),
            'translate': translate,
            'dictionary': dictionary,
            'about': about
        };

        if (NEEDS_DATA.includes(page)) {
            if (dataState === 'loading') { loadingView(); return; }
            if (GESTURES.length === 0) { emptyView(); window.scrollTo({ top: 0 }); return; }
        }

        (routes[page] || home)();

        if (dataState === 'fallback' && NEEDS_DATA.includes(page)) {
            const pageEl = app.querySelector('.page');
            if (pageEl) pageEl.insertAdjacentHTML('afterbegin', fallbackBanner());
        }
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    window.addEventListener('hashchange', route);
    window.addEventListener('scroll', () => {
        const header = $('#hd');
        if (header) header.classList.toggle('s', window.scrollY > 40);
    }, { passive: true });

    // =========================================================================
    // ABOUT VIEW (Tentang proyek, status pengembangan, tim, dan kontak)
    // =========================================================================
    function about() {
        // GANTI: nama & peran anggota tim (sementara masih placeholder)
        const team = [
            { name: 'Albert Hans', role: 'mengatur deployment dan kontribusi dataset' },
            { name: 'Nada Gaming', role: 'membuat konsep dan dasar website serta kontribusi dataset' },
            { name: 'Dini Sleepy', role: 'improvisasi tampilan website dan kontribusi dataset' }
        ];

        // GANTI: kontak tim (sementara masih placeholder)
        const contacts = [
            { label: 'Email', value: 'email@example.com', href: 'mailto:email@example.com' },
            { label: 'Instagram', value: '@username-tim' },
            { label: 'GitHub', value: 'github.com/username-tim' }
        ];

        const features = [
            { title: 'Learn', text: 'Kartu belajar per kategori, lengkap dengan posisi tangan, gerakan, dan kesalahan yang sering terjadi.', link: '#/learn', cta: 'Mulai belajar' },
            { title: 'Practice', text: 'Latihan dengan kamera. Tirukan gestur yang diminta, lalu lihat hasilnya.', link: '#/practice', cta: 'Coba latihan' },
            { title: 'Translate', text: 'Susun gestur menjadi kalimat, lalu baca atau dengarkan hasilnya.', link: '#/translate', cta: 'Buka Translate' },
            { title: 'Dictionary', text: 'Cari gestur berdasarkan kata atau kategori, dan simpan yang jadi favoritmu.', link: '#/dictionary', cta: 'Buka kamus' }
        ];

        const tones = ['mustard', 'sage', 'coral'];

        app.innerHTML = `
        <section class="page wrap about" aria-label="Tentang kami">
            <div class="page-header">
                <div class="meta">ABOUT</div>
                <h1 class="big">ABOUT US</h1>
                <p class="about-lead">Kami membuat tempat belajar BISINDO yang bisa langsung dicoba di browser.</p>
            </div>

            <div class="about-block about-intro">
                <p class="about-text">Signesia adalah proyek yang kami ikutkan dalam lomba di kampus. Tujuannya untuk membantu orang yang ingin mulai belajar Bahasa Isyarat Indonesia (BISINDO), dari abjad dan kata sehari-hari sampai kalimat pendek, tanpa perlu memasang aplikasi.</p>
                <p class="about-text">Kami sendiri masih belajar. Karena itu halaman ini sengaja jujur tentang bagian yang sudah jalan dan bagian yang masih dalam proses.</p>
            </div>

            <div class="about-block">
                <h2>Apa yang bisa kamu lakukan di sini</h2>
                <div class="about-features">
                    ${features.map(f => `
                        <article class="about-card">
                            <h3>${f.title}</h3>
                            <p>${f.text}</p>
                            <a class="btn sm" href="${f.link}">${f.cta}</a>
                        </article>`).join('')}
                </div>
            </div>

            <div class="about-block">
                <aside class="about-note" aria-labelledby="about-note-title">
                    <h2 id="about-note-title">Masih dalam pengembangan</h2>
                    <dl>
                        <div><dt>Konten</dt><dd>Deskripsi gerakan belum diperiksa oleh penutur BISINDO atau juru bahasa isyarat. Bisa jadi ada yang kurang tepat.</dd></div>
                        <div><dt>Sumber</dt><dd>Video YouTube dan referensi di internet. Website: intelektuli.id + disabilitasku.id dan YouTube.</dd></div>
                        <div><dt>Kamera</dt><dd>Pengenalan gestur masih tahap awal dan sebagian masih berupa simulasi.</dd></div>
                    </dl>
                    <p>Kalau kamu menemukan yang keliru, kabari kami lewat kontak di bawah. Koreksi dari komunitas Tuli sangat berarti bagi kami.</p>
                </aside>
            </div>

            <div class="about-block">
                <h2>Tim kami</h2>
                <ul class="about-team">
                    ${team.map((m, i) => `
                        <li class="about-member">
                            <span class="about-avatar tone-${tones[i % tones.length]}" aria-hidden="true">${m.name.charAt(0).toUpperCase()}</span>
                            <div>
                                <strong>${m.name}</strong>
                                <span>${m.role}</span>
                            </div>
                        </li>`).join('')}
                </ul>
            </div>

            <div class="about-block">
                <h2>Dibuat dengan</h2>
                <p class="about-text">Laravel untuk sisi server, dan MediaPipe Hands untuk melacak tangan lewat kamera.</p>
            </div>

        </section>`;
    }

    // =========================================================================
    // HOME VIEW (Cinematic Editorial Landing Page)
    // =========================================================================
    function home() {
        const heroImg = window.HERO_IMG || 'images/hero.jpg';
        const practiceImg = window.PRACTICE_IMG || 'images/practice.jpg';
        const translateImg = window.TRANSLATE_IMG || 'images/translate.jpg';
        const dictionaryImg = window.DICTIONARY_IMG || 'images/dictionary.jpg';

        const features = [
            {
                num: '01',
                tag: 'LEARN',
                title: 'LEARN',
                desc: 'Master sign language through structured visual lessons, interactive 3D flashcards, and step-by-step biomechanical cues.',
                img: heroImg,
                link: '#/learn',
                badge: '32 LESSONS AVAILABLE'
            },
            {
                num: '02',
                tag: 'PRACTICE',
                title: 'PRACTICE',
                desc: 'Hone your gesture precision with real-time camera tracking, automated landmark confidence scoring, and guided session feedback.',
                img: practiceImg,
                link: '#/practice',
                badge: 'CV TRACKING 60 FPS'
            },
            {
                num: '03',
                tag: 'TRANSLATE',
                title: 'TRANSLATE',
                desc: 'Bridge communication gaps instantaneously. Convert continuous sign gestures into written words, build visual sentences, and synthesize natural spoken voice.',
                img: translateImg,
                link: '#/translate',
                badge: 'BILINGUAL SYNTHESIS'
            },
            {
                num: '04',
                tag: 'DICTIONARY',
                title: 'DICTIONARY',
                desc: 'Explore the complete encyclopedic gesture repository. Filter by category, verify hand shapes, and bookmark signs into your personal repertoire.',
                img: dictionaryImg,
                link: '#/dictionary',
                badge: 'COMPREHENSIVE VOCABULARY'
            }
        ];

        app.innerHTML = `
        <!-- HERO SECTION -->
        <section class="hero wrap" aria-label="Hero Introduction">
            <div class="hero-bg-media">
                <img src="${heroImg}" alt="Cinematic person communicating with sign language" loading="eager">
            </div>
            <div class="hero-overlay"></div>

            <div class="hero-grid">
                <div class="hero-l">
                    <div class="hero-status-floating">
                        <span class="live-dot"></span>
                        <span class="meta">REAL-TIME RECOGNITION</span>
                    </div>
                    <div class="hero-meta-kicker">
                        <span class="kicker-line"></span>
                        <span class="meta cy">LEARN. PRACTICE. COMMUNICATE.</span>
                    </div>
                    <h1 class="big">Signesia</h1>
                </div>
                <div class="hero-r">
                    <p class="hero-description">
                        An interactive platform for learning and practicing sign language through visual learning and real-time gesture recognition.
                    </p>
                    <div class="hero-actions">
                        <a href="#/learn" class="btn p">START LEARNING</a>
                        <a href="#/practice" class="btn">TRY PRACTICE</a>
                    </div>
                </div>
            </div>

            <div class="scroll-indicator" aria-hidden="true">
                <div class="line"></div>
                <span class="meta">SCROLL TO EXPLORE</span>
            </div>
        </section>

        <!-- MAJOR FEATURE EDITORIAL SECTIONS -->
        <section class="feature-editorial-list wrap" aria-label="Core Platform Modules">
            ${features.map(f => `
                <article class="feat-item" data-link="${f.link}" tabindex="0" role="link" aria-label="Buka fitur ${f.title}">
                    <div class="feat-num" aria-hidden="true">${f.num}</div>
                    <div class="feat-content">
                        <div class="meta cy">${f.num} / ${f.tag}</div>
                        <h2 class="h2">${f.title}</h2>
                        <p class="feat-desc-desktop">${f.desc}</p>
                        <a href="${f.link}" class="btn">EXPLORE</a>
                    </div>
                    <a href="${f.link}" class="feat-visual" aria-label="Open ${f.tag} Module">
                        <img src="${f.img}" alt="${f.title} visual demonstration" loading="lazy">
                        <div class="feat-visual-overlay">
                            <span class="pill cyan">${f.badge}</span>
                        </div>
                    </a>
                </article>
            `).join('')}
        </section>
        `;
    }

    // =========================================================================
    // LEARN VIEW — Overview > Daftar Kata per Kategori > Flip Card Detail
    // Tahap 2: #/learn → pilih kategori, #/learn/<cat> → daftar kata,
    //          #/learn/<cat>/<slug> → flip card detail
    // =========================================================================
    function learn(param, slug) {
        // #/learn/<cat>/<slug> → tampilkan flip card
        if (param && slug) {
            const cat = CATS.find(c => c[0] === param);
            const gesture = GESTURES.find(g => g.id === slug);
            if (cat && gesture) {
                return renderLessonCard(cat, gesture);
            }
        }

        // #/learn/<cat> → tampilkan daftar kata kategori itu
        if (param) {
            const cat = CATS.find(c => c[0] === param);
            if (cat) return renderCategoryWordList(cat);
        }

        // #/learn → tampilkan daftar semua kategori
        const totalLearned = learned.size;
        const totalSigns = GESTURES.length || 32;
        const totalProgress = Math.round((totalLearned / totalSigns) * 100);

        app.innerHTML = `
        <section class="page wrap" aria-label="Learning Curriculum">
            <div class="page-header">
                <div class="meta">01 / LEARN</div>
                <h1 class="big">LEARN</h1>
                <p class="sub">Build your vocabulary, one gesture at a time.</p>
                <div class="learn-header-progress" style="display: flex; gap: 16px; align-items: center; flex-wrap: wrap; margin-top: 14px;">
                    <span class="pill cyan">TOTAL PROGRESS: ${totalProgress}%</span>
                    <span class="meta">${totalLearned} OF ${totalSigns} GESTURES MASTERED</span>
                </div>
            </div>

            <div class="level-tabs" role="tablist" aria-label="Jenjang belajar">
                ${['all', 'abjad', 'angka', 'kata', 'kalimat'].map(l => `<button class="level-tab${learnLevel === l ? ' on' : ''}" role="tab" aria-selected="${learnLevel === l}" data-level="${l}">${l === 'all' ? 'SEMUA' : LEVEL_LABELS[l]}</button>`).join('')}
            </div>
            ${learnLevel !== 'all' && !GESTURES.some(g => g.level === learnLevel) ? `<p class="sub" style="margin:18px 0">Belum ada materi jenjang ${LEVEL_LABELS[learnLevel].toLowerCase()}. Admin bisa menambahkannya dari panel admin.</p>` : ''}

            <div class="category-list" role="list">
                ${CATS.filter(c => learnLevel === 'all' || byCatL(c[0]).length).map((c, idx) => {
                    const list = byCatL(c[0]);
                    const count = list.length;
                    const done = list.filter(g => learned.has(g.id)).length;
                    const pct = count ? Math.round((done / count) * 100) : 0;
                    const isEmpty = count === 0;
                    const doneAll = !isEmpty && done === count;
                    const statusBadge = isEmpty
                        ? `<span class="cat-status-badge cat-status-empty">Segera Hadir</span>`
                        : doneAll
                        ? `<span class="cat-status-badge cat-status-done">Selesai</span>`
                        : done > 0
                        ? `<span class="cat-status-badge cat-status-progress">Sedang Belajar</span>`
                        : `<span class="cat-status-badge cat-status-new">Belum Dimulai</span>`;

                    return `
                    <div class="cat-row${isEmpty ? ' is-disabled' : ''}" data-cat="${c[0]}" role="listitem" tabindex="${isEmpty ? -1 : 0}" aria-label="${c[1]}, ${count} gesture">
                        <div class="cat-row-num">${pad(idx + 1)}</div>
                        <div class="cat-row-title">
                            <h3>${c[1]}</h3>
                            <span class="desc">${c[2]}</span>
                        </div>
                        <div class="cat-row-meta">
                            <div class="cat-count-pill">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/></svg>
                                ${isEmpty ? 'Belum ada materi' : `${count} gesture`}
                            </div>
                            ${statusBadge}
                        </div>
                        <div class="cat-progress-wrap">
                            <div class="cat-progress-bar" role="progressbar" aria-valuenow="${pct}" aria-valuemin="0" aria-valuemax="100">
                                <b style="width: ${pct}%;"></b>
                            </div>
                            <div class="cat-progress-label"><span>${done}/${count}</span><span class="meta cy">${pct}%</span></div>
                        </div>
                        <div class="cat-arrow">→</div>
                    </div>
                    `;
                }).join('')}
            </div>
        </section>
        `;

        $$('.cat-row').forEach(row => {
            const go = () => { location.hash = '#/learn/' + row.dataset.cat; };
            row.onclick = go;
            row.onkeydown = e => { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); go(); } };
        });
    }

    // Daftar Kata per Kategori (Tahap 2)
    function renderCategoryWordList(cat) {
        const list = byCatL(cat[0]);

        // Keadaan kosong
        if (!list.length) {
            app.innerHTML = `
            <section class="page wrap" aria-label="${cat[1]}">
                <div class="lesson-top-bar">
                    <a href="#/learn" class="lesson-back-btn"><span>←</span> BACK TO CURRICULUM</a>
                    <div class="meta cy">${cat[1]}</div>
                </div>
                <div style="text-align:center;padding:80px 20px">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="var(--t3)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1-2.5-2.5Z"/><path d="M6 6h10"/><path d="M6 10h10"/></svg>
                    <h2 style="margin:20px 0 8px">Materi segera hadir</h2>
                    <p style="color:var(--t2)">Kategori ini belum memiliki materi yang terbit. Cek kembali nanti.</p>
                    <a class="btn sm" href="#/learn" style="margin-top:24px;display:inline-block">← Kembali</a>
                </div>
            </section>`;
            return;
        }

        const done = list.filter(g => learned.has(g.id)).length;
        const pct  = Math.round((done / list.length) * 100);

        app.innerHTML = `
        <section class="page wrap" aria-label="${cat[1]} — daftar kata">
            <div class="lesson-top-bar">
                <a href="#/learn" class="lesson-back-btn"><span>←</span> BACK TO CURRICULUM</a>
                <div class="meta cy">${cat[1]} — ${done}/${list.length} dipelajari</div>
            </div>

            <div class="page-header" style="padding-top:24px">
                <h2 class="h2">${cat[1]}</h2>
                <p class="sub">${cat[2]}</p>
                <div style="display:flex;gap:16px;align-items:center;flex-wrap:wrap;margin-top:12px">
                    <div class="cat-progress-bar" role="progressbar" aria-valuenow="${pct}" aria-valuemin="0" aria-valuemax="100" style="width:180px">
                        <b style="width:${pct}%"></b>
                    </div>
                    <span class="meta cy">${pct}% SELESAI</span>
                    <a class="btn sm" href="#/practice/${cat[0]}" style="margin-left:auto">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
                        Latihan kategori ini
                    </a>
                </div>
            </div>

            <ul class="word-list-grid" role="list" aria-label="Daftar kata ${cat[1]}">
                ${list.map(g => {
                    const isLearned = learned.has(g.id);
                    const thumbMedia = g.image
                        ? `<img class="wli-thumb" src="${escapeHtml(g.image)}" alt="Stiker gerakan ${escapeHtml(idw(g))}" loading="lazy" onerror="this.onerror=null; this.outerHTML='<span class=\\'wli-thumb wli-thumb-placeholder\\'>${escapeHtml(g.e || (idw(g)[0] ? idw(g)[0].toUpperCase() : '👋'))}</span>';">`
                        : `<span class="wli-thumb wli-thumb-placeholder">${escapeHtml(g.e || (idw(g)[0] ? idw(g)[0].toUpperCase() : '👋'))}</span>`;

                    return `
                    <li role="listitem">
                        <a class="word-list-item${isLearned ? ' is-learned' : ''}"
                           href="#/learn/${cat[0]}/${g.id}"
                           aria-label="${idw(g)}${isLearned ? ' — sudah dipelajari' : ''}">
                            <span class="wli-check" aria-hidden="true">
                                ${isLearned
                                    ? `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>`
                                    : `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/></svg>`
                                }
                            </span>
                            ${thumbMedia}
                            <div class="wli-text">
                                <span class="wli-word">${idw(g)}</span>
                                ${enw(g) ? `<span class="wli-en">${enw(g)}</span>` : ''}
                            </div>
                            <div class="wli-meta">
                                <span class="wli-diff pill">${diffLabel(g.diff)}</span>
                                <span class="wli-arrow" aria-hidden="true">→</span>
                            </div>
                        </a>
                    </li>`;
                }).join('')}
            </ul>
        </section>`;
    }

    // Flip Card Detail satu kata (Tahap 2)
    function renderLessonCard(cat, current) {
        const list   = byCatL(cat[0]);
        const index  = list.findIndex(g => g.id === current.id);
        const isDone = learned.has(current.id);
        const prevG  = index > 0 ? list[index - 1] : null;
        const nextG  = index < list.length - 1 ? list[index + 1] : null;

        app.innerHTML = `
        <section class="page wrap" aria-label="Interactive Lesson Flashcard">
            <div class="lesson-top-bar">
                <a href="#/learn/${cat[0]}" class="lesson-back-btn">
                    <span>←</span> KEMBALI KE DAFTAR
                </a>
                <div class="meta cy">
                    ${cat[1]} / KATA ${pad(index + 1)} DARI ${pad(list.length)}
                </div>
            </div>

            <div class="flip-scene">
                <div class="flip-card" id="lesson-flip-card" role="button" tabindex="0" aria-label="Kartu belajar ${idw(current)}. Tekan untuk membalik.">
                    <!-- FRONT FACE -->
                    <div class="flip-face flip-front">
                        <div class="flip-header">
                            <span class="meta">${cat[1]}</span>
                            <span class="pill cyan">${diffLabel(current.diff)}</span>
                        </div>
                        <div class="sign-anim-box">
                            ${renderGestureMedia(current, { className: 'flip-card-media' })}
                        </div>
                        <div class="sign-title-group">
                            <div>
                                <h2>${idw(current)}</h2>
                                ${enw(current) ? `<div class="sign-id-trans">ENGLISH: ${enw(current)}</div>` : ''}
                            </div>
                            <div class="tap-reveal-hint">
                                <span>TAP TO REVEAL</span>
                                <span>↻</span>
                            </div>
                        </div>
                    </div>

                    <!-- BACK FACE (HOW TO SIGN) -->
                    <div class="flip-face flip-back">
                        <div class="flip-header">
                            <span class="meta cy">HOW TO SIGN</span>
                            <span class="pill cyan">KESULITAN: ${diffLabel(current.diff)}</span>
                        </div>
                        <div class="how-to-sign-grid">
                            ${renderGestureMedia(current, { mini: true, className: 'flip-back-media' })}
                            <div>
                                <h3 class="how-to-title">${idw(current)}</h3>
                                <p style="color: var(--t2); font-size: 14px; margin: 0;">${current.desc}</p>
                            </div>
                        </div>
                        <div class="lesson-steps-grid">
                            <div class="lesson-step-block">
                                <div class="meta cy">HAND POSITION</div>
                                <p>${current.pos || 'Standard upright hand orientation.'}</p>
                            </div>
                            <div class="lesson-step-block">
                                <div class="meta cy">MOVEMENT DIRECTION</div>
                                <p>${current.mv || 'Smooth deliberate motion in neutral space.'}</p>
                            </div>
                            <div class="lesson-step-block">
                                <div class="meta meta-warn">COMMON MISTAKE</div>
                                <p>${current.mis || 'Failing to isolate finger movements cleanly.'}</p>
                            </div>
                            <div class="lesson-step-block">
                                <div class="meta cy">CULTURAL STANDARD</div>
                                <p>BISINDO Recognized Dialect Representation.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Controls Bar -->
            <div class="lesson-controls">
                <button class="btn" id="btn-prev-lesson" ${!prevG ? 'disabled' : ''} aria-label="Kata sebelumnya">SEBELUMNYA</button>
                <button class="btn p" id="btn-mark-learned" aria-pressed="${isDone}">
                    ${isDone ? 'SUDAH DIPELAJARI ✓' : 'TANDAI SUDAH DIPELAJARI'}
                </button>
                <button class="btn" id="btn-next-lesson" ${!nextG ? 'disabled' : ''} aria-label="Kata berikutnya">BERIKUTNYA</button>
            </div>

            <div class="lesson-controls" style="margin-top: 14px; gap: 12px;">
                <a class="btn sm" href="#/practice/${cat[0]}" aria-label="Latihan semua kata di kategori ${cat[1]}">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
                    LATIHAN KATEGORI INI
                </a>
                <a class="btn sm" href="#/practice/${current.id}" aria-label="Latihan gestur ${idw(current)} saja">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><polygon points="10 8 16 12 10 16 10 8"/></svg>
                    LATIHAN GESTUR INI
                </a>
            </div>
        </section>
        `;

        const card = $('#lesson-flip-card');
        const flipAction = () => {
            card.classList.toggle('is-flipped');
            playBeep('flip');
        };
        card.onclick  = flipAction;
        card.onkeydown = e => { if (e.key === ' ' || e.key === 'Enter') { e.preventDefault(); flipAction(); } };

        const prevBtn = $('#btn-prev-lesson');
        const nextBtn = $('#btn-next-lesson');
        const markBtn = $('#btn-mark-learned');

        if (prevBtn && prevG) prevBtn.onclick = () => { location.hash = `#/learn/${cat[0]}/${prevG.id}`; };
        if (nextBtn && nextG) nextBtn.onclick = () => { location.hash = `#/learn/${cat[0]}/${nextG.id}`; };

        markBtn.onclick = () => {
            if (learned.has(current.id)) {
                learned.delete(current.id);
                markBtn.textContent = 'TANDAI SUDAH DIPELAJARI';
                markBtn.setAttribute('aria-pressed', 'false');
                toast(`${idw(current)} ditandai belum dipelajari`);
            } else {
                learned.add(current.id);
                markBtn.innerHTML = 'SUDAH DIPELAJARI ✓';
                markBtn.setAttribute('aria-pressed', 'true');
                playBeep('success');
                toast(`${idw(current)} ditandai sudah dipelajari`);
            }
            syncLearned(current.id, learned.has(current.id));
            saveState();
        };
    }

    // =========================================================================
    // PRACTICE VIEW — Tahap 3: Pemilihan Kategori & Latihan per Kategori
    // =========================================================================
    function practice(param) {
        // Jika param adalah kategori yang dikenal ATAU sebuah gesture slug
        let targetCategory = null;
        let singleTargetGesture = null;

        if (param) {
            const foundCat = CATS.find(c => c[0] === param);
            if (foundCat) {
                targetCategory = foundCat;
            } else {
                const foundGesture = GESTURES.find(g => g.id === param);
                if (foundGesture) {
                    singleTargetGesture = foundGesture;
                    targetCategory = CATS.find(c => c[0] === foundGesture.c) || null;
                }
            }
        }

        // Screen 1: Layar Pilih Kategori jika belum ada kategori yang dipilih
        if (!targetCategory && !singleTargetGesture) {
            app.innerHTML = `
            <section class="page wrap" aria-label="Pilih Kategori Latihan">
                <div class="page-header practice-header">
                    <div class="meta">02 / PRACTICE</div>
                    <h1 class="big">PRACTICE</h1>
                    <p class="sub">Pilih kategori untuk memulai sesi latihan mandiri berbasis kamera.</p>
                </div>

                <div class="category-list" role="list">
                    ${CATS.map((c, idx) => {
                        const list = byCatL(c[0]);
                        const count = list.length;
                        const isAvailable = count > 0;
                        const sampleBadges = list.slice(0, 4).map(g => `<span class="cat-gesture-tag">${idw(g)}</span>`).join('');
                        const moreBadge = count > 4 ? `<span class="cat-gesture-tag">+${count - 4} MORE</span>` : '';

                        return `
                        <div class="cat-row ${isAvailable ? '' : 'is-disabled'}"
                             ${isAvailable ? `data-practice-cat="${c[0]}" tabindex="0" role="button"` : 'aria-disabled="true"'}
                             style="${isAvailable ? 'cursor:pointer;' : 'opacity:0.55;cursor:not-allowed;'}"
                             aria-label="${c[1]} — ${isAvailable ? count + ' kartu terbit' : 'Belum ada kartu terbit'}">
                            <div class="cat-row-num">${pad(idx + 1)}</div>
                            <div class="cat-row-title">
                                <h3>${c[1]}</h3>
                                <span class="desc">${c[2]}</span>
                            </div>
                            <div class="cat-badges-preview">
                                ${isAvailable
                                    ? `<span class="cat-gesture-tag" style="font-weight:700">${count} GESTURE</span>${sampleBadges}${moreBadge}`
                                    : `<span class="meta" style="color:var(--t3)">Belum ada kartu terbit (materi segera hadir)</span>`
                                }
                            </div>
                            <div class="cat-arrow">
                                ${isAvailable ? 'MULAI →' : '—'}
                            </div>
                        </div>
                        `;
                    }).join('')}
                </div>
            </section>
            `;

            $$('[data-practice-cat]').forEach(el => {
                const go = () => { location.hash = '#/practice/' + el.dataset.practiceCat; };
                el.onclick = go;
                el.onkeydown = e => { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); go(); } };
            });
            return;
        }

        // Screen 2: Kategori dipilih tapi tidak punya kartu terbit
        const rawPool = targetCategory ? byCatL(targetCategory[0]) : (singleTargetGesture ? [singleTargetGesture] : []);
        if (rawPool.length === 0) {
            app.innerHTML = `
            <section class="page wrap" aria-label="Kategori belum tersedia">
                <div class="lesson-top-bar">
                    <a href="#/practice" class="lesson-back-btn"><span>←</span> PILIH KATEGORI LAIN</a>
                    <div class="meta cy">${targetCategory ? targetCategory[1] : ''}</div>
                </div>
                <div style="text-align:center;padding:80px 20px">
                    <h2 style="margin:20px 0 8px">Kategori Belum Bisa Dipilih</h2>
                    <p style="color:var(--t2)">Kategori ini belum memiliki kartu gesture yang berstatus terbit. Materi segera hadir.</p>
                    <a class="btn sm" href="#/practice" style="margin-top:24px;display:inline-block">← Pilih Kategori Lain</a>
                </div>
            </section>
            `;
            return;
        }

        // Pembentukan pool per kategori tanpa pencampuran
        // Diacak tanpa pengulangan, panjang = min(10, pool.length)
        let shuffled = [...rawPool];
        if (singleTargetGesture) {
            shuffled = [singleTargetGesture, ...rawPool.filter(g => g.id !== singleTargetGesture.id)];
        } else {
            for (let i = shuffled.length - 1; i > 0; i--) {
                const j = Math.floor(Math.random() * (i + 1));
                [shuffled[i], shuffled[j]] = [shuffled[j], shuffled[i]];
            }
        }

        const sessionLength = Math.min(10, shuffled.length);
        const sessionItems = shuffled.slice(0, sessionLength);
        const categoryKey = targetCategory ? targetCategory[0] : (singleTargetGesture ? singleTargetGesture.c : null);
        const categoryLabel = targetCategory ? targetCategory[1] : (CN[categoryKey] || 'PRACTICE');

        let currentIndex = 0;
        let sessionResults = [];
        let startTime = 0;
        let isRunning = false;
        let isSimulating = false;
        let sessionSaved = false;
        let autoRun = false;

        // Spasi = mulai / jeda
        if (practiceKeyHandler) document.removeEventListener('keydown', practiceKeyHandler);
        practiceKeyHandler = (e) => {
            if (!$('#practice-viewport-container')) {
                document.removeEventListener('keydown', practiceKeyHandler);
                practiceKeyHandler = null;
                return;
            }
            if (e.code !== 'Space' || e.repeat) return;
            const tag = ((e.target && e.target.tagName) || '').toLowerCase();
            if (['input', 'textarea', 'select', 'button', 'a'].includes(tag)) return;
            e.preventDefault();
            const btn = $(autoRun ? '#btn-cam-pause' : '#btn-cam-start');
            if (btn) btn.click();
        };
        document.addEventListener('keydown', practiceKeyHandler);

        app.innerHTML = `
        <section class="page wrap" aria-label="Real-time Gesture Practice">
            <div class="lesson-top-bar" style="margin-bottom:12px">
                <a href="#/practice" class="lesson-back-btn"><span>←</span> GANTI KATEGORI</a>
                <div class="meta cy">${categoryLabel} (${sessionLength} GESTURE)</div>
            </div>

            <div class="page-header" style="padding-top:10px">
                <div class="meta">02 / PRACTICE</div>
                <h1 class="big">${categoryLabel}</h1>
                <p class="sub">Latihan terfokus kategori ${categoryLabel}. Sesi kamera mandiri dengan pengenalan langsung.</p>
            </div>

            <div id="practice-viewport-container"></div>
        </section>
        `;

        function renderPracticeStep() {
            const container = $('#practice-viewport-container');
            if (!container) return;

            clearInterval(cameraTimer);
            cameraTimer = null;
            isRunning = false;

            // Session Complete Screen
            if (currentIndex >= sessionLength) {
                autoRun = false;
                stopCamera();
                const passedCount = sessionResults.filter(Boolean).length;
                const accuracy = Math.round((passedCount / sessionLength) * 100);
                const totalSeconds = startTime ? Math.round((Date.now() - startTime) / 1000) : 0;
                if (!sessionSaved) {
                    sessionSaved = true;
                    const sessionLevel = (sessionItems[0] && sessionItems[0].level) || 'kata';
                    const attempts = sessionItems.map((item, idx) => ({
                        slug: item.id,
                        is_correct: !!sessionResults[idx]
                    }));
                    saveSession(passedCount, sessionLength, totalSeconds, sessionLevel, attempts);
                }

                container.innerHTML = `
                <div class="session-complete-wrap">
                    <div class="meta cy">SESI LATIHAN ${categoryLabel} SELESAI</div>
                    <h2 class="h2" style="margin: 16px 0 8px;">SESI SELESAI</h2>
                    <p style="color: var(--t2); font-size: 16px;">Mode simulasi: pencocokan gerakan belum memakai model pengenalan sungguhan, jadi skor ini hanya latihan.</p>

                    <div class="session-stats-grid">
                        <div class="stat-cell">
                            <b>${passedCount} / ${sessionLength}</b>
                            <span class="meta">GESTURE BERHASIL</span>
                        </div>
                        <div class="stat-cell">
                            <b>${accuracy}%</b>
                            <span class="meta">AKURASI</span>
                        </div>
                        <div class="stat-cell">
                            <b>${pad(Math.floor(totalSeconds / 60))}:${pad(totalSeconds % 60)}</b>
                            <span class="meta">WAKTU SESI</span>
                        </div>
                    </div>

                    <div class="lesson-controls">
                        <button class="btn p" id="btn-practice-again">LATIHAN LAGI</button>
                        <a class="btn" href="#/practice">PILIH KATEGORI LAIN</a>
                        <a class="btn" href="#/learn">LIHAT PROGRES</a>
                    </div>
                </div>
                `;

                $('#btn-practice-again').onclick = () => practice(param);
                return;
            }

            const currentSign = sessionItems[currentIndex];

            container.innerHTML = `
            <div class="practice-layout">
                <!-- CAMERA VIEWPORT -->
                <div>
                    <div class="cam-viewport-wrap" id="camera-viewport">
                        <div class="cam-live-indicator meta">
                            <span class="live-dot"></span> LIVE CAMERA
                        </div>
                        <button class="cam-sim-badge" id="btn-toggle-sim" title="Switch between real camera and CV simulation mode">
                            MODE: CAMERA READY
                        </button>
                        <div class="cam-countdown" id="cam-countdown" role="status" aria-live="assertive" hidden></div>
                        <div class="cam-tracking-overlay" id="cam-tracking-box"></div>
                        <div class="cam-result-card" id="cam-result-badge">
                            <div>
                                <span class="meta">DETECTED GESTURE</span>
                                <b id="cam-detected-word">WAITING FOR POSE...</b>
                            </div>
                            <div style="text-align: right;">
                                <span class="meta">CONFIDENCE</span>
                                <b class="cy" id="cam-detected-conf">--%</b>
                            </div>
                        </div>
                    </div>

                    <div class="cam-controls-bar">
                        <button class="btn p" id="btn-cam-start">START</button>
                        <button class="btn" id="btn-cam-pause">PAUSE</button>
                        <button class="btn" id="btn-cam-reset">RESET</button>
                        <button class="btn" id="btn-force-match" title="Trigger instant AI match verification">TEST MATCH ✓</button>
                    </div>
                </div>

                <!-- TARGET SIGN INFORMATION -->
                <div class="practice-target-card">
                    <div class="meta cy">${categoryLabel} • STEP ${pad(currentIndex + 1)} / ${pad(sessionLength)}</div>
                    
                    <div style="margin-top: 24px;">
                        <span class="meta">CURRENT TARGET SIGN</span>
                        <h2 class="practice-target-word">
                            "${idw(currentSign)}"
                        </h2>
                        ${enw(currentSign) ? `<div class="meta cy" style="margin-bottom: 12px;">ENGLISH: ${enw(currentSign)}</div>` : ''}
                    </div>

                    <div class="sign-anim-box" style="margin: 16px 0; min-height: 160px;">
                        ${renderGestureMedia(currentSign, { className: 'practice-target-media' })}
                    </div>

                    <p style="color: var(--t2); font-size: 14px; line-height: 1.5; margin: 0 0 20px;">
                        ${currentSign.desc}
                    </p>

                    <div class="meta">SESSION PROGRESS</div>
                    <div class="practice-session-dots" aria-label="Progress dots for ${sessionLength} practice signs">
                        ${sessionItems.map((_, k) => `
                            <span class="session-dot ${k === currentIndex ? 'active' : ''} ${k < currentIndex ? (sessionResults[k] ? 'pass' : 'fail') : ''}"></span>
                        `).join('')}
                    </div>

                    <div class="practice-sim-selector">
                        <span class="meta">GANTI SOAL DI KATEGORI INI:</span>
                        <div style="display: flex; gap: 8px; flex-wrap: wrap; margin-top: 10px;">
                            ${sessionItems.slice(0, 6).map(g => `
                                <button class="btn sm" data-sign-id="${g.id}">${idw(g)}</button>
                            `).join('')}
                        </div>
                    </div>
                </div>
            </div>
            `;

            // Setup buttons & camera detection loop
            let isEvaluating = false;
            let holdCount = 0;

            const evaluateGesture = (forcePass = false) => {
                if (!isRunning && !forcePass) return;
                if (isEvaluating) return;

                const trackingBox = $('#cam-tracking-box');
                const detectedWord = $('#cam-detected-word');
                const detectedConf = $('#cam-detected-conf');
                if (!trackingBox) return;

                const isNum = !!numberOf(currentSign);
                const targetLetter = isNum ? numberOf(currentSign) : letterOf(currentSign);
                window.recogMode = isNum ? 'angka' : (targetLetter ? 'huruf' : 'kata');
                const useModel = !forcePass && (isNum ? NUM.ready : LETTER.ready) && targetLetter;
                let pass = forcePass;
                let confidence = 88;

                if (useModel) {
                    const g = window.latestRealGesture;
                    if (!g) {
                        holdCount = 0;
                        detectedWord.textContent = 'TANGAN BELUM TERLIHAT';
                        detectedConf.textContent = '--%';
                        return;
                    }
                    confidence = g.conf;
                    detectedWord.textContent = g.letter;
                    detectedConf.textContent = `${confidence}%`;
                    holdCount = (g.letter === targetLetter && g.conf >= 70) ? holdCount + 1 : 0;
                    if (holdCount < 4) return;
                    pass = true;
                } else if (!forcePass && window.latestRealGesture) {
                    const g = window.latestRealGesture;
                    const matchesWord = (g.id === currentSign.id || 
                        (currentSign.slug && g.id === currentSign.slug) ||
                        (currentSign.w && g.w && g.w.toLowerCase() === currentSign.w.toLowerCase()));
                    if (!matchesWord) {
                        holdCount = 0;
                        detectedWord.textContent = g.id_word || idwById(g.id, g.w);
                        detectedConf.textContent = `${g.conf || 80}%`;
                        return;
                    }
                    confidence = g.conf || 95;
                    holdCount++;
                    if (holdCount < 3) return;
                    pass = true;
                } else if (!forcePass) {
                    holdCount = 0;
                    detectedWord.textContent = 'MENUNGGU GERAKAN...';
                    detectedConf.textContent = '--%';
                    pass = false;
                    return;
                }
                isEvaluating = true;

                if (pass) {
                    trackingBox.className = 'cam-tracking-overlay match-ok';
                    detectedWord.textContent = idw(currentSign);
                    detectedConf.textContent = `${confidence}%`;
                    detectedConf.className = 'mint';
                    playBeep('success');

                    // Simpan progres lokal dan sinkronisasi ke server untuk stiker profil
                    if (currentSign && currentSign.id) {
                        learned.add(currentSign.id);
                        syncLearned(currentSign.id, true);
                        saveState();
                    }

                    setTimeout(() => {
                        sessionResults[currentIndex] = true;
                        currentIndex++;
                        isEvaluating = false;
                        renderPracticeStep();
                    }, 1500);
                } else {
                    trackingBox.className = 'cam-tracking-overlay match-fail';
                    detectedWord.textContent = 'SESUAIKAN POSISI TANGAN';
                    detectedConf.textContent = `${confidence}%`;
                    detectedConf.className = '';
                    playBeep('fail');

                    setTimeout(() => {
                        if ($('#cam-tracking-box')) {
                            $('#cam-tracking-box').className = 'cam-tracking-overlay';
                            detectedWord.textContent = `MENUNGGU: ${idw(currentSign)}`;
                        }
                        isEvaluating = false;
                    }, 1400);
                }
            };

            let countdownToken = 0;
            const runCountdown = (seconds) => new Promise(resolve => {
                const el = $('#cam-countdown');
                const token = ++countdownToken;
                if (!el) return resolve(false);
                let n = seconds;
                el.hidden = false;
                const tick = () => {
                    if (token !== countdownToken || !el.isConnected) return resolve(false);
                    if (n === 0) {
                        el.textContent = 'MULAI!';
                        setTimeout(() => {
                            if (token === countdownToken) el.hidden = true;
                            resolve(token === countdownToken);
                        }, 500);
                        return;
                    }
                    el.textContent = String(n--);
                    setTimeout(tick, 1000);
                };
                tick();
            });

            const beginListening = async (seconds) => {
                autoRun = true;
                if (!startTime) startTime = Date.now();
                await Promise.all([loadLetterModel(), loadNumberModel(), loadDynamicModel()]);
                const vp = $('#camera-viewport');
                if (!vp) return;
                if (!vp.querySelector('video')) {
                    const ok = await initCamera(vp);
                    if (!ok) {
                        autoRun = false;
                        isRunning = false;
                        return;
                    }
                }
                if (!autoRun || !$('#cam-tracking-box')) return;
                const ok = await runCountdown(seconds);
                if (!ok || !autoRun) return;
                isRunning = true;
                holdCount = 0;
                clearInterval(cameraTimer);
                cameraTimer = setInterval(evaluateGesture, 300);
                const word = $('#cam-detected-word');
                if (word) word.textContent = `LISTENING FOR: ${idw(currentSign)}`;
            };

            const simBadge = $('#btn-toggle-sim');
            if (simBadge) {
                simBadge.onclick = () => {
                    const isSim = simBadge.textContent.includes('SIMULASI');
                    const vp = $('#camera-viewport');
                    if (isSim) {
                        initCamera(vp);
                    } else {
                        if (cameraStream) {
                            cameraStream.getTracks().forEach(t => t.stop());
                            cameraStream = null;
                        }
                        if (vp) {
                            vp.querySelectorAll('video').forEach(v => v.remove());
                            vp.querySelectorAll('.cam-error-overlay').forEach(v => v.remove());
                            simBadge.textContent = 'MODE: SIMULASI AKTIF';
                            simBadge.style.background = '#e0f2fe';
                            simBadge.style.color = '#0369a1';
                            startLandmarkTracking(vp, null, true);
                            toast('Mode simulasi aktif.');
                        }
                    }
                };
            }

            $('#btn-cam-start').onclick = () => {
                toast('Recognition active. Show gesture to camera.');
                beginListening(3);
            };

            $('#btn-cam-pause').onclick = () => {
                autoRun = false;
                isRunning = false;
                countdownToken++;
                const cd = $('#cam-countdown');
                if (cd) cd.hidden = true;
                clearInterval(cameraTimer);
                toast('Practice paused');
            };

            $('#btn-cam-reset').onclick = () => {
                autoRun = false;
                stopCamera();
                currentIndex = 0;
                sessionResults = [];
                startTime = 0;
                isRunning = false;
                renderPracticeStep();
            };

            $('#btn-force-match').onclick = () => {
                evaluateGesture(true);
            };

            $$('[data-sign-id]').forEach(b => {
                b.onclick = () => {
                    const selected = sessionItems.find(g => g.id === b.dataset.signId);
                    if (selected) {
                        sessionItems[currentIndex] = selected;
                        renderPracticeStep();
                    }
                };
            });

            if (autoRun) {
                beginListening(2);
            } else if ($('#camera-viewport') && !$('#camera-viewport video')) {
                initCamera($('#camera-viewport'));
            }
        }

        renderPracticeStep();
    }

    // =========================================================================
    // TRANSLATE VIEW (Gesture -> Text, Sentence Builder, Text -> Voice)
    // =========================================================================
    function translate() {
        window.recogMode = 'kata';
        let activeTab = 0; // 0: Gesture -> Text, 1: Sentence Builder, 2: Text -> Voice
        let sentenceWords = [
            GESTURES.find(g => g.id === 'hello') || { w: 'HELLO', e: '👋' },
            GESTURES.find(g => g.id === 'how-are-you') || { w: 'HOW ARE YOU', e: '💬' },
            GESTURES.find(g => g.id === 'today') || { w: 'TODAY', e: '☀️' }
        ];

        function getFullSentence() {
            if (!sentenceWords.length) return '';
            const raw = sentenceWords.map(s => idw(s).toLowerCase()).join(' ');
            return raw.charAt(0).toUpperCase() + raw.slice(1) + '.';
        }

        app.innerHTML = `
        <section class="page wrap" aria-label="Sign Language Translation Suite">
            <div class="page-header">
                <div class="meta">03 / TRANSLATE</div>
                <h1 class="big">TRANSLATE</h1>
                <p class="sub">Turn signs into words, and words into voice with fluid accuracy.</p>
            </div>

            <div class="translate-tabs" role="tablist" aria-label="Translation Modalities">
                <button class="translate-tab-btn is-active" data-tab="0" role="tab">GESTURE → TEXT</button>
                <button class="translate-tab-btn" data-tab="1" role="tab">SENTENCE BUILDER</button>
                <button class="translate-tab-btn" data-tab="2" role="tab">TEXT → VOICE</button>
            </div>

            <div id="translate-content-panel"></div>
        </section>
        `;

        function renderTabContent() {
            const panel = $('#translate-content-panel');
            if (!panel) return;

            // Highlight active tab
            $$('.translate-tab-btn').forEach((btn, idx) => {
                btn.classList.toggle('is-active', idx === activeTab);
            });

            // -------------------------------------------------------------
            // MODE 2: TEXT -> VOICE
            // -------------------------------------------------------------
            if (activeTab === 2) {
                panel.innerHTML = `
                <div style="max-width: 900px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                        <span class="meta cy">NEURAL SPEECH SYNTHESIZER</span>
                        <div class="soundwave-anim" id="soundwave-visualizer" aria-hidden="true">
                            <span class="soundwave-bar"></span>
                            <span class="soundwave-bar"></span>
                            <span class="soundwave-bar"></span>
                            <span class="soundwave-bar"></span>
                            <span class="soundwave-bar"></span>
                        </div>
                    </div>

                    <textarea class="tts-textarea" id="tts-input" placeholder="Type something to convert into natural speech..." aria-label="Speech text input">Hello, thank you for communicating with sign language today.</textarea>
                    <div class="tts-controls-grid">
                        <div class="tts-field-group">
                            <label class="meta" for="tts-lang-select">LANGUAGE</label>
                            <select id="tts-lang-select">
                                <option value="id-ID">Indonesian (Bahasa Indonesia)</option>
                                <option value="en-US" selected>English (United States)</option>
                                <option value="en-GB">English (United Kingdom)</option>
                            </select>
                        </div>
                        <div class="tts-field-group">
                            <label class="meta" for="tts-voice-speed">VOICE CADENCE</label>
                            <select id="tts-voice-speed">
                                <option value="1">Natural Speed (1.0x)</option>
                                <option value="0.85">Deliberate & Clear (0.85x)</option>
                                <option value="1.15">Brisk Conversational (1.15x)</option>
                            </select>
                        </div>
                        <div class="tts-field-group">
                            <label class="meta" for="tts-pitch">TONE PITCH</label>
                            <select id="tts-pitch">
                                <option value="1">Natural Neutral</option>
                                <option value="1.1">Warm Clear</option>
                                <option value="0.9">Deep Resonant</option>
                            </select>
                        </div>
                    </div>

                    <div class="lesson-controls" style="justify-content: flex-start; gap: 14px;">
                        <button class="btn p" id="btn-tts-play">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor"><polygon points="5 3 19 12 5 21 5 3"></polygon></svg>
                            PLAY
                        </button>
                        <button class="btn" id="btn-tts-pause">PAUSE</button>
                        <button class="btn" id="btn-tts-clear">CLEAR</button>
                    </div>
                </div>
                `;

                const input = $('#tts-input');
                const visualizer = $('#soundwave-visualizer');

                $('#btn-tts-play').onclick = () => {
                    const text = input.value.trim();
                    if (!text) return toast('Please enter text to synthesize');
                    
                    window.speechSynthesis.cancel();
                    const utterance = new SpeechSynthesisUtterance(text);
                    utterance.lang = $('#tts-lang-select').value;
                    utterance.rate = parseFloat($('#tts-voice-speed').value);
                    utterance.pitch = parseFloat($('#tts-pitch').value);

                    utterance.onstart = () => visualizer.classList.add('is-speaking');
                    utterance.onend = () => visualizer.classList.remove('is-speaking');
                    utterance.onerror = () => visualizer.classList.remove('is-speaking');

                    window.speechSynthesis.speak(utterance);
                    toast('Synthesizing speech audio...');
                };

                $('#btn-tts-pause').onclick = () => {
                    if (window.speechSynthesis.speaking) {
                        if (window.speechSynthesis.paused) {
                            window.speechSynthesis.resume();
                            visualizer.classList.add('is-speaking');
                        } else {
                            window.speechSynthesis.pause();
                            visualizer.classList.remove('is-speaking');
                        }
                    }
                };

                $('#btn-tts-clear').onclick = () => {
                    window.speechSynthesis.cancel();
                    input.value = '';
                    visualizer.classList.remove('is-speaking');
                };
                return;
            }

            // -------------------------------------------------------------
            // MODES 0 & 1: GESTURE -> TEXT & SENTENCE BUILDER
            // -------------------------------------------------------------
            panel.innerHTML = `
            <div>
                ${activeTab === 0 ? `
                    <div class="practice-layout" style="margin-bottom: 32px;">
                        <div>
                            <div class="cam-viewport-wrap" id="trans-cam-box" style="aspect-ratio: 16 / 9;">
                                <div class="cam-live-indicator meta"><span class="live-dot"></span>LIVE GESTURE STREAM</div>
                                <div class="cam-tracking-overlay"></div>
                                <div class="cam-result-card">
                                    <div>
                                        <span class="meta">DETECTED GESTURE</span>
                                        <b id="trans-detected-label">READY TO RECOGNIZE</b>
                                    </div>
                                    <div>
                                        <span class="pill cyan">CONTINUOUS AI</span>
                                    </div>
                                </div>
                            </div>
                            <div class="cam-controls-bar">
                                <button class="btn p" id="btn-trans-cam-start">START CAMERA</button>
                                <button class="btn" id="btn-trans-mode">MODE: KATA (SEMUA KATA)</button>
                                <button class="btn" id="btn-trans-add" style="background: var(--c-primary, #004E2F); color: #fff;">+ MASUKKAN KE KALIMAT</button>
                                <button class="btn" id="btn-trans-simulate">+ SIMULATE GESTURE</button>
                            </div>
                        </div>

                        <div>
                            <div class="practice-target-card">
                                <span class="meta cy">RECOGNITION QUEUE</span>
                                <h3 style="font-size: 24px; margin: 12px 0 8px;">LIVE VOCABULARY CAPTURE</h3>
                                <p style="color: var(--t2); font-size: 14px; margin-bottom: 20px;">
                                    Signs recognized in real time are automatically appended to your sentence tray below.
                                </p>
                                <span class="meta">QUICK INSERT:</span>
                                <div style="display: flex; gap: 6px; flex-wrap: wrap; margin-top: 10px;">
                                    ${(GESTURES.filter(g => g.level === 'kata' || g.level === 'kalimat').length ? GESTURES.filter(g => g.level === 'kata' || g.level === 'kalimat') : GESTURES).slice(0, 8).map(g => `
                                        <button class="btn sm" data-add-sign="${g.id}">${idw(g)}</button>
                                    `).join('')}
                                </div>
                            </div>
                        </div>
                    </div>
                ` : `
                    <div style="margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 14px;">
                        <span class="meta cy">VISUAL SENTENCE CONSTRUCTION TRAY</span>
                        <div style="display: flex; gap: 10px; align-items: center;">
                            <span class="meta">INSERT GESTURE:</span>
                            <select id="select-dict-add" style="background: var(--bg2); border: 1px solid var(--line); border-radius: var(--radius-sm); padding: 8px 12px; color: var(--t1); font-family: var(--font-heading); font-size: 11px;">
                                <option value="">+ Choose gesture...</option>
                                ${GESTURES.map(g => `<option value="${g.id}">${idw(g)} (${enw(g) || CN[g.c]})</option>`).join('')}
                            </select>
                        </div>
                    </div>
                `}

                <div class="meta">INTERACTIVE SENTENCE BLOCKS (DRAG OR REMOVE)</div>
                <div class="sentence-builder-tray" id="sentence-tray" role="region" aria-label="Sentence block items"></div>

                <div class="meta" style="margin-top: 24px;">TRANSLATED RESULT</div>
                <div class="translation-result-display" id="translation-output" aria-live="polite"></div>

                <div class="lesson-controls" style="justify-content: flex-start; gap: 12px;">
                    <button class="btn" id="btn-trans-clear">CLEAR</button>
                    <button class="btn" id="btn-trans-undo">UNDO</button>
                    <button class="btn p" id="btn-trans-speak">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"></polygon><path d="M19.07 4.93a10 10 0 0 1 0 14.14M15.54 8.46a5 5 0 0 1 0 7.07"></path></svg>
                        SPEAK
                    </button>
                    <button class="btn" id="btn-trans-copy">COPY TEXT</button>
                </div>
            </div>
            `;

            // Draw sentence blocks
            function refreshSentenceTray() {
                const tray = $('#sentence-tray');
                const output = $('#translation-output');
                if (!tray || !output) return;

                if (!sentenceWords.length) {
                    tray.innerHTML = `<span class="meta" style="color: var(--t3); align-self: center;">NO GESTURES CAPTURED YET • SHOW GESTURE TO CAMERA OR ADD FROM LIST</span>`;
                    output.textContent = '...';
                    return;
                }

                tray.innerHTML = sentenceWords.map((item, idx) => `
                    <div class="sentence-block-chip" draggable="true" data-index="${idx}">
                        ${item.image ? `<img src="${escapeHtml(item.image)}" alt="" class="sentence-chip-thumb">` : `<span>${item.e || '◇'}</span>`}
                        <b>${idw(item)}</b>
                        <button class="remove-chip-btn" data-del-index="${idx}" aria-label="Remove ${idw(item)}">✕</button>
                    </div>
                `).join('');

                output.textContent = getFullSentence();

                // Delete chip button
                $$('[data-del-index]').forEach(btn => {
                    btn.onclick = () => {
                        sentenceWords.splice(parseInt(btn.dataset.delIndex), 1);
                        refreshSentenceTray();
                    };
                });

                // Drag-and-drop reordering
                let draggedIdx = null;
                $$('.sentence-block-chip').forEach(chip => {
                    chip.ondragstart = () => { draggedIdx = parseInt(chip.dataset.index); };
                    chip.ondragover = e => e.preventDefault();
                    chip.ondrop = () => {
                        const targetIdx = parseInt(chip.dataset.index);
                        if (draggedIdx !== null && draggedIdx !== targetIdx) {
                            const [moved] = sentenceWords.splice(draggedIdx, 1);
                            sentenceWords.splice(targetIdx, 0, moved);
                            refreshSentenceTray();
                        }
                    };
                });
            }

            refreshSentenceTray();

            // Handlers for Camera Mode
            if (activeTab === 0) {
                const camBox = $('#trans-cam-box');
                const startBtn = $('#btn-trans-cam-start');
                const simBtn = $('#btn-trans-simulate');
                const modeBtn = $('#btn-trans-mode');
                const addBtn = $('#btn-trans-add');

                const modes = ['kata', 'huruf', 'angka'];
                const modeLabels = {
                    'kata': 'MODE: KATA (SEMUA KATA)',
                    'huruf': 'MODE: HURUF (A-Z)',
                    'angka': 'MODE: ANGKA (0-9)'
                };

                if (modeBtn) {
                    modeBtn.textContent = modeLabels[window.recogMode] || 'MODE: KATA (SEMUA KATA)';
                    modeBtn.onclick = () => {
                        const idx = modes.indexOf(window.recogMode);
                        window.recogMode = modes[(idx + 1) % modes.length];
                        modeBtn.textContent = modeLabels[window.recogMode];
                        toast('Mode pengenalan diubah ke: ' + window.recogMode.toUpperCase());
                        const label = $('#trans-detected-label');
                        if (label) label.textContent = 'READY TO RECOGNIZE';
                    };
                }

                if (addBtn) {
                    addBtn.onclick = () => appendDetectedGesture();
                }

                const appendDetectedGesture = (forceSign = null) => {
                    let sign = forceSign;
                    if (!sign && window.latestRealGesture) {
                        const g = window.latestRealGesture;
                        sign = GESTURES.find(x => x.id === g.id) || {
                            id: g.id,
                            w: g.w || g.letter,
                            id_word: g.id_word || idwById(g.id, g.w) || g.w,
                            e: g.e || '◇'
                        };
                    }
                    if (sign) {
                        sentenceWords.push(sign);
                        const label = $('#trans-detected-label');
                        if (label) {
                            const wordText = idw(sign) || sign.w;
                            label.textContent = `${wordText} (${window.latestRealGesture ? window.latestRealGesture.conf : 94}% KEYAKINAN)`;
                        }
                        playBeep('success');
                        refreshSentenceTray();
                        toast(`"${idw(sign) || sign.w}" dimasukkan ke kalimat`);
                    } else {
                        toast('Belum ada gerakan terdeteksi untuk dimasukkan ke kalimat.');
                    }
                };

                let isTransRunning = false;
                startBtn.onclick = async () => {
                    if (isTransRunning) {
                        isTransRunning = false;
                        startBtn.textContent = 'START CAMERA';
                        startBtn.classList.add('p');
                        toast('Penerjemah dijeda.');
                        return;
                    }

                    if (!cameraStream) {
                        const ok = await initCamera(camBox);
                        if (!ok) return;
                    }
                    isTransRunning = true;
                    startBtn.textContent = 'PAUSE CAMERA';
                    startBtn.classList.remove('p');
                    toast('Kamera penerjemah aktif. Tunjukkan gerakan tangan.');
                };

                simBtn.onclick = () => {
                    const pool = GESTURES.filter(g => g.level === 'kata' || g.level === 'kalimat');
                    const sim = (pool.length ? pool : GESTURES)[Math.floor(Math.random() * (pool.length || GESTURES.length))];
                    appendDetectedGesture(sim);
                };

                const resultCard = camBox.querySelector('.cam-result-card');
                if (resultCard) {
                    resultCard.style.cursor = 'pointer';
                    resultCard.title = 'Klik untuk masukkan gerakan yang terdeteksi ke kalimat';
                    resultCard.onclick = () => appendDetectedGesture();
                }

                $$('[data-add-sign]').forEach(b => {
                    b.onclick = () => {
                        const g = GESTURES.find(x => x.id === b.dataset.addSign);
                        if (g) {
                            sentenceWords.push(g);
                            refreshSentenceTray();
                        }
                    };
                });
            } else {
                // Dictionary select handler
                const selectAdd = $('#select-dict-add');
                if (selectAdd) {
                    selectAdd.onchange = e => {
                        const g = GESTURES.find(x => x.id === e.target.value);
                        if (g) {
                            sentenceWords.push(g);
                            refreshSentenceTray();
                        }
                        e.target.value = '';
                    };
                }
            }

            // Bottom action buttons
            $('#btn-trans-clear').onclick = () => {
                sentenceWords = [];
                refreshSentenceTray();
            };

            $('#btn-trans-undo').onclick = () => {
                sentenceWords.pop();
                refreshSentenceTray();
            };

            $('#btn-trans-speak').onclick = () => {
                const text = getFullSentence();
                if (!text) return toast('Nothing to speak');
                window.speechSynthesis.cancel();
                const u = new SpeechSynthesisUtterance(text);
                u.lang = 'en-US';
                u.rate = 1.0;
                window.speechSynthesis.speak(u);
                toast(`Speaking: "${text}"`);
            };

            $('#btn-trans-copy').onclick = () => {
                const text = getFullSentence();
                if (!text) return toast('Nothing to copy');
                navigator.clipboard.writeText(text).then(
                    () => toast('Sentence copied to clipboard'),
                    () => toast('Clipboard permission denied')
                );
            };
        }

        $$('.translate-tab-btn').forEach(btn => {
            btn.onclick = () => {
                stopCamera();
                activeTab = parseInt(btn.dataset.tab);
                renderTabContent();
            };
        });

        renderTabContent();
    }

    // =========================================================================
    // DICTIONARY VIEW (Search, Filters, Gesture Grid, and Detail Modal)
    // =========================================================================
    function dictionary() {
        let activeFilter = 'all';
        let searchQuery = '';

        app.innerHTML = `
        <section class="page wrap" aria-label="Sign Language Gesture Dictionary">
            <div class="page-header">
                <div class="meta">04 / DICTIONARY</div>
                <h1 class="big">DICTIONARY</h1>
                <p class="sub">Explore every recognized gesture in our neural database.</p>
                
                <input class="dictionary-search-input" id="dict-query-input" placeholder="SEARCH A WORD, E.G. 'HALO'..." aria-label="Search gesture dictionary">

                <div class="dict-filter-chips" role="tablist">
                    ${[
                        ['all', 'ALL'],
                        ['greet', 'GREETING'],
                        ['conv', 'CONVERSATION'],
                        ['days', 'DAYS'],
                        ['verbs', 'VERBS'],
                        ['num', 'NUMBERS'],
                        ['alpha', 'ALPHABET']
                    ].map(f => `
                        <button class="dict-filter-btn ${f[0] === activeFilter ? 'is-active' : ''}" data-filter="${f[0]}">${f[1]}</button>
                    `).join('')}
                </div>
            </div>

            <div id="dict-grid-container"></div>
        </section>
        `;

        function refreshGrid() {
            const container = $('#dict-grid-container');
            if (!container) return;

            $$('.dict-filter-btn').forEach(b => {
                b.classList.toggle('is-active', b.dataset.filter === activeFilter);
            });

            const filtered = sortGestures(GESTURES.filter(g => {
                const matchesFilter = (activeFilter === 'all' || g.c === activeFilter);
                const q = searchQuery.toLowerCase().trim();
                const matchesSearch = !q ||
                    g.w.toLowerCase().includes(q) ||
                    (g.id_word && g.id_word.toLowerCase().includes(q)) ||
                    (g.desc && g.desc.toLowerCase().includes(q)) ||
                    CN[g.c].toLowerCase().includes(q);
                return matchesFilter && matchesSearch;
            }));

            if (!filtered.length) {
                container.innerHTML = `
                <div style="padding: 64px 0; text-align: center;">
                    <div class="meta" style="font-size: 14px;">NO MATCHING GESTURES FOUND</div>
                    <p style="color: var(--t2); margin-top: 8px;">Try searching for "HALO", "AIR", "TOLONG", or select another category filter.</p>
                </div>
                `;
                return;
            }

            container.innerHTML = `
            <div class="gesture-grid">
                ${filtered.map(g => {
                    const isFavorited = fav.has(g.id);
                    return `
                    <div class="gesture-card-item" data-gesture-id="${g.id}" tabindex="0" role="button" aria-label="${idw(g)} gesture details">
                        <div class="gesture-card-top">
                            <span class="pill">${CN[g.c] || g.c}</span>
                            <button class="fav-toggle-btn ${isFavorited ? 'is-fav' : ''}" data-fav-id="${g.id}" aria-label="Toggle favorite for ${idw(g)}">
                                ${isFavorited ? '★' : '☆'}
                            </button>
                        </div>

                        <div class="gesture-card-preview">
                            ${renderGestureMedia(g, { className: 'dict-card-media' })}
                        </div>

                        <div class="gesture-card-meta">
                            <h3>${idw(g)}</h3>
                            <div class="gesture-card-id-name">${enw(g) ? 'ENGLISH: ' + enw(g) : ''}</div>
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 12px;">
                                <span class="meta">KESULITAN: ${diffLabel(g.diff)}</span>
                                <span class="meta cy">EXPLORE →</span>
                            </div>
                        </div>
                    </div>
                    `;
                }).join('')}
            </div>
            `;

            // Open Detail Modal
            $$('.gesture-card-item').forEach(card => {
                const open = () => openGestureModal(card.dataset.gestureId);
                card.onclick = e => {
                    if (e.target.closest('.fav-toggle-btn')) return;
                    open();
                };
                card.onkeydown = e => {
                    if (e.key === 'Enter') open();
                };
            });

            // Favorite toggle button
            $$('.fav-toggle-btn').forEach(btn => {
                btn.onclick = e => {
                    e.stopPropagation();
                    const gid = btn.dataset.favId;
                    if (fav.has(gid)) {
                        fav.delete(gid);
                        btn.classList.remove('is-fav');
                        btn.textContent = '☆';
                        toast('Removed from favorites');
                    } else {
                        fav.add(gid);
                        btn.classList.add('is-fav');
                        btn.textContent = '★';
                        playBeep('success');
                        toast('Added to favorites');
                    }
                    saveState();
                };
            });
        }

        $$('.dict-filter-btn').forEach(b => {
            b.onclick = () => {
                activeFilter = b.dataset.filter;
                refreshGrid();
            };
        });

        const queryInput = $('#dict-query-input');
        if (queryInput) {
            queryInput.oninput = e => {
                searchQuery = e.target.value;
                refreshGrid();
            };
        }

        refreshGrid();
    }

    // =========================================================================
    // GESTURE DETAIL MODAL DRAWER
    // =========================================================================
    function openGestureModal(id) {
        const gesture = GESTURES.find(g => g.id === id);
        if (!gesture) return;

        const modal = $('#md');
        const isFav = fav.has(id);

        modal.innerHTML = `
        <div class="modal-box" role="dialog" aria-modal="true" aria-label="${idw(gesture)} details">
            <div class="modal-top-bar">
                <div class="modal-heading-group">
                    <span class="pill cyan">${CN[gesture.c] || gesture.c}</span>
                    <h2 class="modal-title">${idw(gesture)}</h2>
                </div>
                <button class="clear-search-btn" id="modal-close-btn" aria-label="Close dialog">✕</button>
            </div>

            <div class="modal-visual-side">
                ${renderGestureMedia(gesture, { className: 'modal-gesture-media' })}
                <div class="meta cy modal-media-label">
                    ${gesture.video_file ? 'VIDEO FOOTAGE GERAKAN' : (gesture.image ? 'POSTER GERAKAN' : 'BIOMECHANICAL VECTOR DIAGRAM')}
                </div>
            </div>

            <div class="modal-info-side">
                ${enw(gesture) ? `<div class="meta cy" style="margin-bottom: 14px;">ENGLISH: ${enw(gesture)}</div>` : ''}

                <div class="modal-detail-row">
                    <span class="meta cy">DESCRIPTION</span>
                    <p>${gesture.desc}</p>
                </div>

                <div class="modal-detail-row">
                    <span class="meta cy">HAND POSITION</span>
                    <p>${gesture.pos || 'Standard position in neutral space.'}</p>
                </div>

                <div class="modal-detail-row">
                    <span class="meta cy">MOVEMENT TRAJECTORY</span>
                    <p>${gesture.mv || 'Deliberate controlled path.'}</p>
                </div>

                <div class="modal-detail-row">
                    <span class="meta meta-warn">COMMON MISTAKE</span>
                    <p>${gesture.mis || 'Failing to isolate joints cleanly.'}</p>
                </div>

                <div class="modal-actions">
                    <a href="#/practice/${gesture.id}" class="btn p" id="btn-modal-practice">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path><circle cx="12" cy="13" r="4"></circle></svg>
                        PRACTICE
                    </a>
                    <button class="btn" id="btn-modal-fav">
                        ${isFav ? 'FAVORITED ★' : 'ADD TO FAVORITES ☆'}
                    </button>
                    <button class="btn" id="btn-modal-done">CLOSE</button>
                </div>
            </div>
        </div>
        `;

        modal.classList.add('on');

        const close = () => closeModal();
        $('#modal-close-btn').onclick = close;
        $('#btn-modal-done').onclick = close;
        $('#btn-modal-practice').onclick = () => { closeModal(); };

        $('#btn-modal-fav').onclick = e => {
            if (fav.has(id)) {
                fav.delete(id);
                e.target.textContent = 'ADD TO FAVORITES ☆';
                toast('Removed from favorites');
            } else {
                fav.add(id);
                e.target.textContent = 'FAVORITED ★';
                playBeep('success');
                toast('Added to favorites');
            }
            saveState();
        };

        modal.onclick = e => {
            if (e.target === modal) close();
        };
    }

    function closeModal() {
        const modal = $('#md');
        if (modal) modal.classList.remove('on');
    }

    // =========================================================================
    // CINEMATIC GLOBAL SEARCH OVERLAY
    // =========================================================================
    const searchOverlay = $('#so');
    const searchInput = $('#si');
    const searchTrigger = $('#sb');
    const searchClear = $('#sc-clear');
    const searchResults = $('#sres');

    function openSearch() {
        if (!searchOverlay) return;
        searchOverlay.classList.add('on');
        searchInput.value = '';
        renderSearchResults('');
        setTimeout(() => searchInput.focus(), 80);
    }

    function closeSearch() {
        if (searchOverlay) searchOverlay.classList.remove('on');
    }

    function renderSearchResults(query) {
        if (!searchResults) return;
        const q = query.toLowerCase().trim();

        const words = GESTURES.filter(g =>
            !q || g.w.toLowerCase().includes(q) || (g.id_word && g.id_word.toLowerCase().includes(q))
        ).slice(0, 8);

        const cats = CATS.filter(c =>
            !q || c[1].toLowerCase().includes(q) || c[2].toLowerCase().includes(q)
        );

        if (!words.length && !cats.length) {
            searchResults.innerHTML = `
            <div style="padding: 24px; text-align: center;">
                <span class="meta">NO RESULTS MATCHING "${query.toUpperCase()}"</span>
            </div>
            `;
            return;
        }

        let html = '';

        if (cats.length && q) {
            html += `<div class="meta cy" style="margin: 8px 0;">LESSON CATEGORIES</div>`;
            cats.forEach(c => {
                html += `
                <button class="search-result-item" data-goto-cat="${c[0]}">
                    <div>
                        <b style="font-family: var(--font-heading); font-size: 16px;">${c[1]}</b>
                        <span style="display: block; color: var(--t2); font-size: 12px;">${c[2]}</span>
                    </div>
                    <span class="meta cy">LESSONS →</span>
                </button>
                `;
            });
        }

        if (words.length) {
            html += `<div class="meta cy" style="margin: 12px 0 6px;">RECOGNIZED GESTURES</div>`;
            words.forEach(g => {
                const thumb = g.image
                    ? `<img src="${escapeHtml(g.image)}" alt="" style="width:28px;height:28px;border-radius:6px;object-fit:cover;border:1.5px solid var(--ink);flex-shrink:0;">`
                    : `<span style="font-size: 20px;">${g.e || '◇'}</span>`;

                html += `
                <button class="search-result-item" data-goto-gesture="${g.id}">
                    <div style="display: flex; align-items: center; gap: 14px;">
                        ${thumb}
                        <div>
                            <b style="font-family: var(--font-heading); font-size: 16px;">${idw(g)}</b>
                            ${enw(g) ? `<span style="color: var(--cy); font-size: 11px; margin-left: 8px;">(${enw(g)})</span>` : ''}
                        </div>
                    </div>
                    <div style="text-align: right;">
                        <span class="meta">${CN[g.c] || g.c}</span>
                    </div>
                </button>
                `;
            });
        }

        searchResults.innerHTML = html;

        $$('[data-goto-gesture]').forEach(b => {
            b.onclick = () => {
                closeSearch();
                openGestureModal(b.dataset.gotoGesture);
            };
        });

        $$('[data-goto-cat]').forEach(b => {
            b.onclick = () => {
                closeSearch();
                location.hash = '#/learn/' + b.dataset.gotoCat;
            };
        });
    }

    if (searchTrigger) searchTrigger.onclick = openSearch;
    const searchClose = $('#so-close');
    if (searchClose) searchClose.onclick = closeSearch;
    if (searchClear) searchClear.onclick = () => {
        searchInput.value = '';
        renderSearchResults('');
        searchInput.focus();
    };

    if (searchInput) {
        searchInput.oninput = e => renderSearchResults(e.target.value);
    }

    function updatePopularTags() {
        const filtersContainer = document.querySelector('.ov-filters');
        if (!filtersContainer || !GESTURES || !GESTURES.length) return;

        const candidates = ['halo', 'terima kasih', 'tolong', 'makan', 'apa kabar', 'selamat tinggal', 'hari ini', 'nama', 'tidur'];
        const matched = [];

        candidates.forEach(cand => {
            const found = GESTURES.find(g => {
                const idw = (g.id_word || '').toLowerCase();
                const enw = (g.w || '').toLowerCase();
                return idw.includes(cand) || enw.includes(cand);
            });
            if (found && matched.length < 5) {
                const cleanWord = (found.id_word || found.w).split('/')[0].trim().toUpperCase();
                if (!matched.some(m => m.tag === cand)) {
                    matched.push({ tag: cand, label: cleanWord });
                }
            }
        });

        if (matched.length < 5) {
            GESTURES.forEach(g => {
                if (matched.length >= 5) return;
                const cleanWord = (g.id_word || g.w).split('/')[0].trim().toUpperCase();
                const cand = cleanWord.toLowerCase();
                if (!matched.some(m => m.label === cleanWord)) {
                    matched.push({ tag: cand, label: cleanWord });
                }
            });
        }

        if (matched.length > 0) {
            let html = '<span class="meta">POPULAR:</span>';
            matched.forEach(item => {
                html += `<button type="button" class="ov-tag" data-tag="${escapeHtml(item.tag)}">${escapeHtml(item.label)}</button>`;
            });
            filtersContainer.innerHTML = html;

            filtersContainer.querySelectorAll('.ov-tag').forEach(tagBtn => {
                tagBtn.onclick = () => {
                    const tag = tagBtn.dataset.tag;
                    if (searchInput) {
                        searchInput.value = tag;
                        renderSearchResults(tag);
                    }
                };
            });
        }
    }

    $$('.ov-tag').forEach(tagBtn => {
        tagBtn.onclick = () => {
            const tag = tagBtn.dataset.tag;
            searchInput.value = tag;
            renderSearchResults(tag);
        };
    });

    // Keyboard Shortcuts (Ctrl+K to open search, Esc to close)
    window.addEventListener('keydown', e => {
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            openSearch();
        } else if (e.key === 'Escape') {
            closeSearch();
            closeModal();
        }
    });

    // =========================================================================
    // INITIALIZATION & API BOOTSTRAP
    // =========================================================================
    document.addEventListener('click', e => {
        const tab = e.target.closest('.level-tab');
        if (!tab) return;
        learnLevel = tab.dataset.level;
        if (location.hash.startsWith('#/learn/')) location.hash = '#/learn';
        learn();
    });

    // =========================================================================
    // PEMUATAN DATA: loading, error (data contoh), dan kosong
    // =========================================================================
    const gesturesUrl = window.GESTURES_URL || '/api/gestures';
    const NEEDS_DATA = ['learn', 'practice', 'translate', 'dictionary'];
    let dataState = 'loading'; // 'loading' | 'ready' | 'fallback'

    // Data contoh: hanya dipakai kalau server tidak bisa dihubungi. Bukan isi kamus yang sebenarnya.
    const FALLBACK_GESTURES = [
            { id: 'hello',     w: 'HELLO',     id_word: 'HALO',          c: 'greet', level: 'kata',    e: '👋', diff: 'Easy',   desc: 'Raise open hand near forehead and move outward.',      pos: 'Flat open hand, palm facing outward.',     mv: 'Smooth outward arc away from forehead.',        mis: 'Waving side-to-side casually.',        vkey: 'open_palm_wave' },
            { id: 'thank-you', w: 'THANK YOU', id_word: 'TERIMA KASIH',  c: 'greet', level: 'kalimat', e: '🙏', diff: 'Easy',   desc: 'Touch fingertips to chin, then move forward.',         pos: 'Flat hand facing inward at chin.',         mv: 'Forward and slightly down toward the person.',  mis: 'Starting too far from the chin.',      vkey: 'chin_forward' },
            { id: 'goodbye',   w: 'GOODBYE',   id_word: 'SELAMAT TINGGAL', c: 'greet', level: 'kalimat', e: '🖐️', diff: 'Easy',   desc: 'Raise open hand and fold fingers into palm.',          pos: 'Open palm facing outward.',               mv: 'Controlled finger fold cadence.',               mis: 'Moving entire arm violently.',         vkey: 'farewell_fold' },
            { id: 'please',    w: 'PLEASE',    id_word: 'TOLONG / SILAKAN', c: 'greet', level: 'kata', e: '🤲', diff: 'Easy', desc: 'Flat palm against center of chest, circular motion.',  pos: 'Flat palm on chest center.',              mv: 'Clockwise circular rubbing.',                   mis: 'Hovering in air without touching chest.', vkey: 'chest_circle' },
            { id: 'help',      w: 'HELP',      id_word: 'TOLONG',        c: 'greet', level: 'kata',    e: '🆘', diff: 'Easy',   desc: 'Thumbs-up fist on flat palm, lift upward.',            pos: 'Fist on flat horizontal palm.',           mv: 'Firm supportive upward lift.',                  mis: 'Leaving supporting palm behind.',      vkey: 'palm_fist_lift' },
            { id: 'how-are-you', w: 'HOW ARE YOU', id_word: 'APA KABAR', c: 'conv', level: 'kalimat', e: '💬', diff: 'Medium', desc: 'Roll curved hands open, then point forward.',          pos: 'Both hands curved, roll open.',           mv: 'Roll open and point to person.',                mis: 'Omitting pointing at the end.',        vkey: 'how_roll_point' },
            { id: 'today',     w: 'TODAY',     id_word: 'HARI INI',      c: 'conv',  level: 'kata',    e: '☀️', diff: 'Medium', desc: 'Y-shape hands drop downward twice.',                    pos: 'Both hands forming Y-shape.',             mv: 'Double short downward drop.',                   mis: 'Making movement too exaggerated.',     vkey: 'y_drop_double' },
            { id: 'yes',       w: 'YES',       id_word: 'YA',            c: 'conv',  level: 'kata',    e: '👍', diff: 'Easy',   desc: 'Closed fist nods up and down from the wrist.',         pos: 'Closed fist knuckles forward.',           mv: 'Controlled wrist nod downward.',                mis: 'Moving whole forearm.',               vkey: 'fist_nod' },
            { id: 'no',        w: 'NO',        id_word: 'TIDAK',         c: 'conv',  level: 'kata',    e: '🙅', diff: 'Easy',   desc: 'Index and middle finger snap onto thumb.',             pos: 'Two fingers extended parallel.',          mv: 'Quick assertive pinch twice.',                  mis: 'Using only one finger.',              vkey: 'finger_snap_no' },
            { id: 'eat',       w: 'EAT',       id_word: 'MAKAN',         c: 'verbs', level: 'kata',    e: '🍽️', diff: 'Easy',  desc: 'Clustered fingertips tap mouth.',                       pos: 'Fingertips together touching thumb.',      mv: 'Gentle repeated tap to lips.',                  mis: 'Opening palm while tapping.',         vkey: 'cluster_mouth_tap' },
            { id: 'sleep',     w: 'SLEEP',     id_word: 'TIDUR',         c: 'verbs', level: 'kata',    e: '😴', diff: 'Easy',   desc: 'Pull hand down closing fingers near face.',            pos: 'Open palm at eye level.',                 mv: 'Close fingers while pulling down.',             mis: 'Pulling without closing fingers.',    vkey: 'face_descend_close' },
            { id: 'one',       w: 'ONE',       id_word: 'SATU',          c: 'num',   level: 'angka',   e: '1️⃣', diff: 'Easy',  desc: 'Index finger pointing straight up.',                   pos: 'Index up, thumb locks other fingers.',    mv: 'Hold steady in neutral space.',                 mis: 'Showing thumb instead of index.',     vkey: 'digit_one' }
    ];

    const pageName = () => (location.hash || '#/').slice(2).split('/')[0];

    function loadingView() {
        app.innerHTML = `
        <section class="page wrap state-view" role="status" aria-live="polite">
            <div class="state-card">
                <div class="state-loader" aria-hidden="true"><span></span><span></span><span></span></div>
                <h1>Memuat gestur&hellip;</h1>
                <p>Mengambil daftar gestur dari server.</p>
            </div>
        </section>`;
    }

    function emptyView() {
        app.innerHTML = `
        <section class="page wrap state-view" role="status">
            <div class="state-card">
                <h1>Belum ada gestur</h1>
                <p>Server terhubung, tapi daftar gestur masih kosong. Coba muat ulang sebentar lagi.</p>
                <button type="button" class="btn p" data-retry>Muat ulang</button>
                <details>
                    <summary>Info untuk pengelola</summary>
                    <p>Isi data lewat halaman admin, atau jalankan <code>php artisan db:seed --class=GestureSeeder</code>.</p>
                </details>
            </div>
        </section>`;
    }

    function fallbackBanner() {
        return `
        <div class="data-banner" role="alert">
            <p>Data dari server belum bisa dimuat, jadi yang tampil hanya ${GESTURES.length} gestur contoh dalam bahasa Inggris.</p>
            <button type="button" class="btn sm" data-retry>Coba lagi</button>
        </div>`;
    }

    async function loadGestures() {
        dataState = 'loading';
        if (NEEDS_DATA.includes(pageName())) loadingView();

        const ctrl = new AbortController();
        const timer = setTimeout(() => ctrl.abort(), 10000);
        try {
            let res = await fetch(gesturesUrl, { headers: { 'Accept': 'application/json' }, signal: ctrl.signal });
            if (!res.ok && res.status === 404 && !gesturesUrl.includes('index.php')) {
                // Vercel serverless workaround jika rute rewrite diarahkan via /api/index.php
                const fallbackUrl = gesturesUrl.replace('/api/gestures', '/api/index.php/api/gestures');
                res = await fetch(fallbackUrl, { headers: { 'Accept': 'application/json' }, signal: ctrl.signal });
            }
            if (!res.ok) throw new Error('HTTP ' + res.status);
            const data = await res.json();
            if (!Array.isArray(data)) throw new Error('Format data tidak dikenali');
            GESTURES = data;
            dataState = 'ready';
        } catch (err) {
            console.warn('Gagal memuat ' + gesturesUrl + ', memakai data contoh:', err);
            GESTURES = FALLBACK_GESTURES;
            dataState = 'fallback';
        } finally {
            clearTimeout(timer);
        }

        updatePopularTags();

        const countEl = document.getElementById('ft-total-signs');
        if (countEl) countEl.textContent = `${GESTURES.length} GESTUR` + (dataState === 'fallback' ? ' (CONTOH)' : '');

        if (AUTH.loggedIn && dataState === 'ready') {
            try {
                const res = await fetch(AUTH.progressUrl, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' });
                const p = res.ok ? await res.json() : { learned: [] };
                learned.clear();
                (p.learned || []).forEach(slug => learned.add(slug));
            } catch (e) { /* progres gagal dimuat: lanjut tanpa progres */ }
        }

        if (NEEDS_DATA.includes(pageName())) route();
    }

    document.addEventListener('click', e => {
        if (e.target.closest('[data-retry]')) loadGestures();
        const feat = e.target.closest('.feat-item');
        if (feat && !e.target.closest('a')) {
            const link = feat.getAttribute('data-link');
            if (link) location.hash = link;
        }
    });

    // Beranda dan About tidak butuh data gestur, jadi langsung tampil tanpa menunggu.
    if (!NEEDS_DATA.includes(pageName())) route();
    loadGestures();

})();
