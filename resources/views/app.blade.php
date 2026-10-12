<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#F6EFDD">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/logo.svg') }}">
    <link rel="alternate icon" href="{{ asset('favicon.ico') }}">
    <meta name="description" content="A clean, premium, immersive sign language learning, practice, translation, and dictionary platform.">
    <title>Signesia — Belajar Bisindo</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Caveat:wght@600&family=Fredoka:wght@400;500;600;700&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/theme.css') }}?v={{ @filemtime(public_path('css/theme.css')) ?: time() }}">
</head>
<body>
    <!-- Minimalist Translucent Header -->
    <header id="hd">
        <a href="#/" class="logo" aria-label="Signesia home">
            <img class="logo-symbol" src="{{ asset('images/logo.svg') }}" alt="" width="31" height="38">
            <span class="logo-text">Signesia</span>
            <span class="logo-badge">2026</span>
        </a>
        <nav class="top" aria-label="Primary Navigation">
            <a href="#/learn" data-page="learn">LEARN</a>
            <a href="#/practice" data-page="practice">PRACTICE</a>
            <a href="#/translate" data-page="translate">TRANSLATE</a>
            <a href="#/dictionary" data-page="dictionary">DICTIONARY</a>
        </nav>
        <div class="hd-actions">
            <button class="ibtn search-trigger" id="sb" aria-label="Cari gesture">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                <span class="sb-label">SEARCH</span>
            </button>
            <div class="hd-auth">
                @auth
                    <a href="{{ route('profile') }}" class="hd-auth-link" id="hd-profile">PROFIL</a>
                    <form method="POST" action="{{ route('logout') }}" class="hd-auth-form">
                        @csrf
                        <button type="submit" class="hd-auth-link">KELUAR</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="hd-auth-link">MASUK</a>
                    <a href="{{ route('register') }}" class="hd-auth-link hd-auth-cta">DAFTAR</a>
                @endauth
            </div>
        </div>
    </header>

    <!-- Main Dynamic Application Container -->
    <main id="app" tabindex="-1" role="main"></main>

    <!-- Editorial Footer -->
    <footer class="site-footer">
        <div class="footer-wrap">
            <div class="footer-left">
                <span class="meta cy">SIGNESIA | SIGN LANGUAGE</span>
                <p class="footer-desc">Pengenalan gestur lewat kamera berupa simulasi, dan kamus BISINDO.</p>
                <a class="footer-link" href="#/about">Tentang kami</a>
            </div>
            <div class="footer-meta-grid">
                <div>
                    <span class="meta">BELAJAR,</span>
                    <span class="val"></span>
                </div>
                <div>
                    <span class="meta">LATIHAN</span>
                    <span class="val"></span>
                </div>
                <div>
                    <span class="meta">KAMUS</span>
                    <span class="val" id="ft-total-signs">&mdash;</span>
                </div>
            </div>
        </div>
    </footer>

    <!-- Compact Mobile Bottom Navigation -->
    <nav class="bn" aria-label="Mobile Navigation">
        <a href="#/" data-page=""><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path></svg><span>HOME</span></a>
        <a href="#/learn" data-page="learn"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1-2.5-2.5Z"></path><path d="M6 6h10"></path><path d="M6 10h10"></path></svg><span>LEARN</span></a>
        <a href="#/practice" data-page="practice"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path><circle cx="12" cy="13" r="4"></circle></svg><span>PRACTICE</span></a>
        <a href="#/translate" data-page="translate"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m5 8 6 6"></path><path d="m4 14 6-6 2-3"></path><path d="M2 5h12"></path><path d="M7 2h1"></path><path d="m22 22-5-10-5 10"></path><path d="M14 18h6"></path></svg><span>TRANSLATE</span></a>
        <a href="#/dictionary" data-page="dictionary"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 4h6a4 4 0 0 1 4 4v13a3 3 0 0 0-3-3H2z"></path><path d="M22 4h-6a4 4 0 0 0-4 4v13a3 3 0 0 1 3-3h7z"></path></svg><span>DICT</span></a>
    </nav>

    <!-- Global Cinematic Search Modal Overlay -->
    <div class="ov" id="so" role="dialog" aria-modal="true" aria-label="Search gesture platform">
        <div class="ov-inner">
            <div class="ov-top-bar">
                <span class="meta cy">SEARCH SIGN LANGUAGE DATABASE</span>
                <button type="button" class="ov-close" id="so-close" aria-label="Tutup pencarian">ESC / TUTUP</button>
            </div>
            <div class="ov-input-box">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="search-icon"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                <input id="si" placeholder="SEARCH WORDS, GESTURES, OR LESSONS..." aria-label="Search sign language" autocomplete="off" spellcheck="false">
                <button class="clear-search-btn" id="sc-clear" aria-label="Clear search input">✕</button>
            </div>
            <div class="ov-filters">
                <span class="meta">POPULAR:</span>
                <button type="button" class="ov-tag" data-tag="halo">HALO</button>
                <button type="button" class="ov-tag" data-tag="terima kasih">TERIMA KASIH</button>
                <button type="button" class="ov-tag" data-tag="tolong">TOLONG</button>
                <button type="button" class="ov-tag" data-tag="makan">MAKAN</button>
                <button type="button" class="ov-tag" data-tag="apa kabar">APA KABAR</button>
            </div>
            <div id="sres" class="search-results-list" role="region" aria-live="polite"></div>
        </div>
    </div>

    <!-- Interactive Gesture Detail Drawer / Modal -->
    <div class="modal" id="md" role="dialog" aria-modal="true"></div>

    <!-- Subtle Toast System -->
    <div id="toast" role="status" aria-live="polite"></div>

    <script>
        window.SL_AUTH = {
            loggedIn: {{ auth()->check() ? 'true' : 'false' }},
            csrf: "{{ csrf_token() }}",
            progressUrl: "{{ url('/api/progress') }}",
            sessionUrl: "{{ url('/api/practice-sessions') }}"
        };
        window.GESTURES_URL = "{{ url('/api/gestures') }}";
        window.GESTURES_FALLBACK_URL = "{{ url('/api/index.php/api/gestures') }}";
        window.STATS_URL = "{{ url('/api/stats') }}";
        window.HERO_IMG = "{{ asset('images/hero.jpg') }}";
        window.PRACTICE_IMG = "{{ asset('images/practice.jpg') }}";
        window.TRANSLATE_IMG = "{{ asset('images/translate.jpg') }}";
        window.DICTIONARY_IMG = "{{ asset('images/dictionary.jpg') }}";
    </script>
    <!-- MediaPipe Hands for Real-time Computer Vision Hand Tracking -->
    <script src="https://cdn.jsdelivr.net/npm/@mediapipe/camera_utils/camera_utils.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/@mediapipe/hands/hands.js" crossorigin="anonymous"></script>

    <script src="{{ asset('js/dynamic.js') }}?v={{ filemtime(public_path('js/dynamic.js')) }}"></script>
    <script src="{{ asset('js/app.js') }}?v={{ filemtime(public_path('js/app.js')) }}"></script>
    <script src="{{ asset('js/anim.js') }}?v={{ filemtime(public_path('js/anim.js')) }}" defer></script>
</body>
</html>
