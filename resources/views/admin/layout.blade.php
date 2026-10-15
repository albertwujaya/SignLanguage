<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#F6EFDD">
    <title>@yield('title') | Signesia Admin</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/logo.svg') }}">
    <link rel="alternate icon" href="{{ asset('favicon.ico') }}">
    {{-- Preload primary fonts for zero layout shift (CLS) & instant render --}}
    <link rel="preload" href="{{ asset('fonts/fredoka-700.woff2') }}" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="{{ asset('fonts/nunito-400.woff2') }}" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="{{ asset('fonts/nunito-700.woff2') }}" as="font" type="font/woff2" crossorigin>
    <link rel="stylesheet" href="{{ asset('css/fonts.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
</head>
<body class="admin-body">
    <div class="admin-shell">
        {{-- Sidebar Navigasi Kiri --}}
        @include('admin.partials.sidebar')

        {{-- Area Konten Utama --}}
        <div class="admin-content-area">
            {{-- Topbar Mobile & Ringkas --}}
            <header class="admin-topbar">
                <button id="sidebarOpenBtn" class="mobile-toggle-btn" type="button" aria-label="Buka menu navigasi" aria-expanded="false" aria-controls="adminSidebar">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
                </button>
                <div class="topbar-crumb">
                    <span class="crumb-root">Studio Admin</span>
                    <span class="crumb-separator" aria-hidden="true">/</span>
                    <span class="crumb-current">@yield('title')</span>
                </div>
                <div class="topbar-user">
                    <span class="user-greeting">Halo, <strong>{{ auth()->user()->name }}</strong></span>
                    <span class="role-badge">{{ strtoupper(auth()->user()->role) }}</span>
                </div>
            </header>

            {{-- Main Content --}}
            <main id="mainContent" class="admin-main" tabindex="-1">
                @include('admin.partials.flash')
                @yield('content')
            </main>
        </div>
    </div>

    {{-- Accessible Confirmation Modal Dialog --}}
    <div id="confirmModal" class="admin-modal-backdrop" style="display:none" role="dialog" aria-modal="true" aria-labelledby="confirmTitle" aria-describedby="confirmText">
        <div class="admin-modal" tabindex="-1">
            <h3 id="confirmTitle">Konfirmasi Tindakan</h3>
            <p id="confirmText">Apakah kamu yakin ingin melanjutkan?</p>
            <div class="modal-actions">
                <button id="cancelConfirmBtn" class="button button-quiet" type="button">Batal</button>
                <button id="acceptConfirmBtn" class="button button-danger" type="button">Ya, Lanjutkan</button>
            </div>
        </div>
    </div>

    <script>
        (function() {
            // Sidebar Drawer Toggle for Mobile
            var sidebar = document.getElementById('adminSidebar');
            var openBtn = document.getElementById('sidebarOpenBtn');
            var closeBtn = document.getElementById('sidebarCloseBtn');
            var backdrop = document.getElementById('sidebarBackdrop');

            function openSidebar() {
                if (!sidebar) return;
                sidebar.classList.add('is-open');
                if (backdrop) backdrop.classList.add('is-visible');
                if (openBtn) openBtn.setAttribute('aria-expanded', 'true');
                if (closeBtn) closeBtn.focus();
            }

            function closeSidebar() {
                if (!sidebar) return;
                sidebar.classList.remove('is-open');
                if (backdrop) backdrop.classList.remove('is-visible');
                if (openBtn) {
                    openBtn.setAttribute('aria-expanded', 'false');
                    openBtn.focus();
                }
            }

            if (openBtn) openBtn.addEventListener('click', openSidebar);
            if (closeBtn) closeBtn.addEventListener('click', closeSidebar);
            if (backdrop) backdrop.addEventListener('click', closeSidebar);

            // Confirmation Modal Logic
            var activeForm = null;
            var modal = document.getElementById('confirmModal');
            var cancelBtn = document.getElementById('cancelConfirmBtn');
            var acceptBtn = document.getElementById('acceptConfirmBtn');
            var titleEl = document.getElementById('confirmTitle');
            var textEl = document.getElementById('confirmText');

            document.addEventListener('submit', function(e) {
                var form = e.target;
                var msg = form.getAttribute('data-confirm');
                if (msg) {
                    e.preventDefault();
                    activeForm = form;
                    titleEl.textContent = form.getAttribute('data-confirm-title') || 'Konfirmasi Tindakan';
                    textEl.textContent = msg;
                    acceptBtn.textContent = form.getAttribute('data-confirm-btn') || 'Ya, Lanjutkan';
                    if (form.getAttribute('data-confirm-danger') === 'false') {
                        acceptBtn.className = 'button button-primary';
                    } else {
                        acceptBtn.className = 'button button-danger';
                    }
                    modal.style.display = 'grid';
                    acceptBtn.focus();
                }
            });

            function closeModal() {
                if (modal) modal.style.display = 'none';
                activeForm = null;
            }

            if (cancelBtn) cancelBtn.addEventListener('click', closeModal);
            if (acceptBtn) {
                acceptBtn.addEventListener('click', function() {
                    if (activeForm) {
                        var formToSubmit = activeForm;
                        activeForm = null;
                        formToSubmit.removeAttribute('data-confirm');
                        formToSubmit.submit();
                    }
                    closeModal();
                });
            }

            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    if (modal && modal.style.display !== 'none') {
                        closeModal();
                    } else if (sidebar && sidebar.classList.contains('is-open')) {
                        closeSidebar();
                    }
                }
            });

            if (modal) {
                modal.addEventListener('click', function(e) {
                    if (e.target === modal) closeModal();
                });
            }
        })();
    </script>
</body>
</html>