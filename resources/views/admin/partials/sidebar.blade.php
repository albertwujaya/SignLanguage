<aside id="adminSidebar" class="admin-sidebar" aria-label="Menu Navigasi Admin">
    <div class="sidebar-brand">
        <a class="brand-link" href="{{ route('admin.dashboard') }}">
            <span class="brand-symbol">◇</span>
            <span class="brand-text">
                SIGN LANGUAGE
                <small class="brand-badge">STUDIO</small>
            </span>
        </a>
        <button id="sidebarCloseBtn" class="sidebar-close-btn" type="button" aria-label="Tutup menu navigasi">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
        </button>
    </div>

    <nav class="sidebar-nav">
        {{-- Grup 1: Ringkasan --}}
        <div class="nav-group">
            <span class="nav-group-title">Ringkasan</span>
            <a href="{{ route('admin.dashboard') }}" @class(['nav-link', 'active' => request()->routeIs('admin.dashboard')]) @if(request()->routeIs('admin.dashboard')) aria-current="page" @endif>
                <svg class="nav-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="7" height="7" rx="1.5"></rect><rect x="14" y="3" width="7" height="7" rx="1.5"></rect><rect x="14" y="14" width="7" height="7" rx="1.5"></rect><rect x="3" y="14" width="7" height="7" rx="1.5"></rect></svg>
                <span>Dashboard</span>
            </a>
        </div>

        {{-- Grup 2: Konten & Kamus --}}
        <div class="nav-group">
            <span class="nav-group-title">Konten &amp; Kamus</span>
            <a href="{{ route('admin.gestures.index') }}" @class(['nav-link', 'active' => request()->routeIs('admin.gestures.index', 'admin.gestures.create', 'admin.gestures.edit', 'admin.gestures.preview')]) @if(request()->routeIs('admin.gestures.index', 'admin.gestures.create', 'admin.gestures.edit', 'admin.gestures.preview')) aria-current="page" @endif>
                <svg class="nav-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path></svg>
                <span>Daftar Gesture</span>
            </a>
            <a href="{{ route('admin.gestures.completeness') }}" @class(['nav-link', 'active' => request()->routeIs('admin.gestures.completeness')]) @if(request()->routeIs('admin.gestures.completeness')) aria-current="page" @endif>
                <svg class="nav-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 11l3 3L22 4"></path><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path></svg>
                <span>Kelengkapan Media</span>
            </a>
            @if (auth()->user()->isAdmin())
                <a href="{{ route('admin.gestures.trash') }}" @class(['nav-link', 'active' => request()->routeIs('admin.gestures.trash')]) @if(request()->routeIs('admin.gestures.trash')) aria-current="page" @endif>
                    <svg class="nav-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                    <span>Tempat Sampah</span>
                </a>
            @endif
        </div>

        {{-- Grup 3: Pengguna & Pembelajaran --}}
        <div class="nav-group">
            <span class="nav-group-title">Pengguna &amp; Progres</span>
            @if (auth()->user()->isAdmin())
                <a href="{{ route('admin.users.index') }}" @class(['nav-link', 'active' => request()->routeIs('admin.users.*')]) @if(request()->routeIs('admin.users.*')) aria-current="page" @endif>
                    <svg class="nav-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                    <span>Data Pengguna</span>
                </a>
            @endif
            <a href="{{ route('admin.gestures.analytics') }}" @class(['nav-link', 'active' => request()->routeIs('admin.gestures.analytics')]) @if(request()->routeIs('admin.gestures.analytics')) aria-current="page" @endif>
                <svg class="nav-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>
                <span>Analisis Latihan</span>
            </a>
        </div>

        {{-- Grup 4: Sistem & Audit --}}
        <div class="nav-group">
            <span class="nav-group-title">Sistem</span>
            <a href="{{ route('admin.logs.index') }}" @class(['nav-link', 'active' => request()->routeIs('admin.logs.*')]) @if(request()->routeIs('admin.logs.*')) aria-current="page" @endif>
                <svg class="nav-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                <span>Log Aktivitas</span>
            </a>
            <a href="{{ url('/') }}" class="nav-link" target="_blank" rel="noopener">
                <svg class="nav-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg>
                <span>Lihat Situs Publik</span>
            </a>
        </div>
    </nav>

    <div class="sidebar-user">
        <div class="user-avatar" aria-hidden="true">
            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
        </div>
        <div class="user-info">
            <span class="user-name">{{ auth()->user()->name }}</span>
            <span class="user-role">{{ strtoupper(auth()->user()->role) }}</span>
        </div>
        <form method="POST" action="{{ route('logout') }}" class="user-logout-form">
            @csrf
            <button class="logout-icon-btn" type="submit" title="Keluar dari akun">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
            </button>
        </form>
    </div>
</aside>
<div id="sidebarBackdrop" class="sidebar-backdrop" aria-hidden="true"></div>
