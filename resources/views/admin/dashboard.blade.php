@extends('admin.layout')

@section('title', 'Dashboard')

@section('content')
    <div class="page-heading">
        <div>
            <p class="eyebrow">RINGKASAN SISTEM</p>
            <h1>Dashboard Admin</h1>
            <p class="page-lede">Pantau perkembangan pembelajaran BISINDO, konten kosakata, dan aktivitas pengguna.</p>
        </div>
        <div class="toolbar-group">
            <a class="button button-primary" href="{{ route('admin.gestures.create') }}">
                <span aria-hidden="true">+</span> Tambah Gesture
            </a>
        </div>
    </div>

    {{-- Main Metrics --}}
    <div class="metrics-grid">
        <div class="metric-card">
            <p class="metric-label">Total Pengguna</p>
            <p class="metric-value">{{ $totalUsers }}</p>
            <p class="metric-sub">{{ $activeThisWeek }} pengguna aktif 7 hari terakhir</p>
        </div>
        <div class="metric-card">
            <p class="metric-label">Rata-rata Progres</p>
            <p class="metric-value">{{ $avgProgressPercent }}%</p>
            <p class="metric-sub">Rerata penguasaan semua pengguna</p>
        </div>
        <div class="metric-card">
            <p class="metric-label">Total Gesture</p>
            <p class="metric-value">{{ $gestureStats['published'] }}</p>
            <p class="metric-sub">{{ $gestureStats['draft'] }} draft · {{ $gestureStats['trashed'] }} di tempat sampah</p>
        </div>
        <div class="metric-card">
            <p class="metric-label">Sesi Latihan</p>
            <p class="metric-value">{{ $totalSessions }}</p>
            <p class="metric-sub">Rata-rata skor evaluasi: {{ $avgScore }}%</p>
        </div>
    </div>

    {{-- Content Quality & Analytics Overview Grid --}}
    <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(340px, 1fr));gap:20px;margin-top:28px">
        {{-- Kelengkapan Konten Card --}}
        <div class="data-form">
            <div class="section-heading" style="margin-bottom:12px">
                <div>
                    <p class="eyebrow">KUALITAS KONTEN</p>
                    <h2>Kelengkapan Media</h2>
                </div>
                <a class="text-action" href="{{ route('admin.gestures.completeness') }}">Periksa &rarr;</a>
            </div>
            <p style="margin:0 0 16px;color:var(--muted);font-size:13px">
                @if ($incompleteCount > 0)
                    Terdapat <strong>{{ $incompleteCount }}</strong> gesture yang belum memiliki media gambar, video, atau deskripsi lengkap.
                @else
                    Semua gesture terbit telah memiliki media gambar dan video lengkap.
                @endif
            </p>
            <a class="button button-quiet button-sm" href="{{ route('admin.gestures.completeness') }}">Buka Dashboard Kelengkapan</a>
        </div>

        {{-- Gesture Tersulit Card --}}
        <div class="data-form">
            <div class="section-heading" style="margin-bottom:12px">
                <div>
                    <p class="eyebrow">ANALISIS LATIHAN</p>
                    <h2>Gesture Tersulit</h2>
                </div>
                <a class="text-action" href="{{ route('admin.gestures.analytics') }}">Lihat Semua &rarr;</a>
            </div>
            @if ($hardestGestures->isNotEmpty())
                <ul style="margin:0;padding-left:18px;font-size:13px;color:var(--ink)">
                    @foreach ($hardestGestures as $item)
                        <li style="margin-bottom:6px">
                            <strong>{{ $item->gesture?->word ?? 'Gesture #' . $item->gesture_id }}</strong>
                            <span class="secondary" style="display:inline"> — Akurasi: {{ $item->accuracy }}% ({{ $item->total_attempts }} percobaan)</span>
                        </li>
                    @endforeach
                </ul>
            @else
                <p style="margin:0;color:var(--muted);font-size:13px">Belum ada cukup data percobaan latihan untuk menentukan tingkat kesulitan.</p>
            @endif
        </div>
    </div>

    {{-- Log Aktivitas Terkini --}}
    <div class="section-heading" style="margin-top:36px">
        <div>
            <p class="eyebrow">AUDIT TRAIL</p>
            <h2>Aktivitas Admin Terkini</h2>
        </div>
        <a class="text-action" href="{{ route('admin.logs.index') }}">Semua Log &rarr;</a>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Waktu</th>
                    <th>Admin</th>
                    <th>Aksi</th>
                    <th>Ringkasan Aktivitas</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($recentLogs as $log)
                    <tr>
                        <td style="white-space:nowrap">{{ $log->created_at ? $log->created_at->translatedFormat('d M Y H:i') : '—' }}</td>
                        <td><strong>{{ $log->user?->name ?? 'Sistem' }}</strong></td>
                        <td><span class="badge badge-draft">{{ strtoupper($log->action) }}</span></td>
                        <td>{{ $log->summary }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="empty-state">Belum ada catatan aktivitas admin.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
