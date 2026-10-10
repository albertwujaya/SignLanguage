@extends('admin.layout')

@section('title', 'Dashboard Kelengkapan Konten')

@section('content')
    <div class="page-heading">
        <div>
            <p class="eyebrow">AUDIT KUALITAS</p>
            <h1>Kelengkapan Konten Gesture</h1>
            <p class="page-lede">Pantau kosakata yang belum memiliki media gambar, video, atau deskripsi panduan.</p>
        </div>
        <div class="toolbar-group">
            <a class="button button-quiet" href="{{ route('admin.gestures.index') }}">Kembali ke daftar</a>
        </div>
    </div>

    {{-- Level Completeness Breakdown Cards --}}
    <div class="metrics-grid">
        @foreach ($levelsSummary as $lvl)
            <div class="metric-card">
                <p class="metric-label">{{ $lvl['label'] }}</p>
                <p class="metric-value">{{ $lvl['complete'] }} / {{ $lvl['total'] }}</p>
                <div class="progress-track" style="margin:10px 0 6px" aria-hidden="true">
                    <div class="progress-fill" style="width: {{ min(100, $lvl['percent']) }}%"></div>
                </div>
                <p class="metric-sub">
                    <strong>{{ $lvl['percent'] }}%</strong> lengkap · {{ $lvl['incomplete'] }} perlu dilengkapi
                </p>
            </div>
        @endforeach
    </div>

    {{-- Incomplete Gestures List --}}
    <div class="section-heading" style="margin-top:36px">
        <div>
            <p class="eyebrow">PERLU TINDAKAN</p>
            <h2>Daftar Gesture Belum Lengkap ({{ $incompleteGestures->total() }})</h2>
        </div>
        <p>Klik tombol "Lengkapi" untuk langsung mengunggah media atau mengisi deskripsi.</p>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Istilah &amp; Slug</th>
                    <th>Jenjang</th>
                    <th>Status</th>
                    <th>Kekurangan Konten</th>
                    <th class="actions-heading">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($incompleteGestures as $g)
                    @php
                        $missing = [];
                        if (! $g->image_path) $missing[] = 'Belum ada gambar';
                        if (! $g->video_path) $missing[] = 'Belum ada video';
                        if (! $g->description || trim($g->description) === '') $missing[] = 'Deskripsi kosong';
                    @endphp
                    <tr>
                        <td>
                            <strong>{{ $g->word }}</strong>
                            <small class="secondary">{{ $g->slug }}</small>
                        </td>
                        <td>{{ \App\Models\Gesture::LEVELS[$g->level] ?? $g->level }}</td>
                        <td>
                            @if ($g->isPublished())
                                <span class="badge badge-success">Terbit</span>
                            @else
                                <span class="badge badge-draft">Draft</span>
                            @endif
                        </td>
                        <td>
                            <div style="display:flex;gap:6px;flex-wrap:wrap">
                                @foreach ($missing as $m)
                                    <span class="badge badge-danger">{{ $m }}</span>
                                @endforeach
                            </div>
                        </td>
                        <td class="row-actions">
                            <a class="text-action" href="{{ route('admin.gestures.edit', $g) }}">Lengkapi</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="empty-state">Luar biasa! Semua gesture saat ini memiliki media dan deskripsi yang lengkap.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="pagination">{{ $incompleteGestures->links() }}</div>
@endsection
