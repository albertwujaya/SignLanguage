@extends('admin.layout')

@section('title', 'Detail Pengguna: ' . $user->name)

@section('content')
    <div class="page-heading">
        <div>
            <p class="eyebrow">DETAIL PENGGUNA</p>
            <h1>{{ $user->name }}</h1>
            <p class="page-lede">{{ $user->email }} · Terdaftar {{ $user->created_at ? $user->created_at->translatedFormat('d F Y') : '—' }}</p>
        </div>
        <div class="toolbar-group">
            <a class="button button-quiet" href="{{ route('admin.users.index') }}">Kembali ke daftar</a>
        </div>
    </div>

    {{-- Top Summary Metric Cards --}}
    <div class="metrics-grid">
        <div class="metric-card">
            <p class="metric-label">Total Progres</p>
            <p class="metric-value">{{ $totalProgressPercent }}%</p>
            <p class="metric-sub">{{ $totalLearned }} dari {{ $totalPublished }} gesture dikuasai</p>
        </div>
        <div class="metric-card">
            <p class="metric-label">Rata-rata Nilai</p>
            <p class="metric-value">{{ $avgScore !== null ? $avgScore . '%' : '—' }}</p>
            <p class="metric-sub">
                @if ($sessionsCount > 0)
                    Tertinggi {{ $maxScore }}% · Terendah {{ $minScore }}%
                @else
                    Belum ada sesi latihan
                @endif
            </p>
        </div>
        <div class="metric-card">
            <p class="metric-label">Jumlah Sesi Latihan</p>
            <p class="metric-value">{{ $sessionsCount }}</p>
            <p class="metric-sub">Terakhir aktif: {{ $user->last_active_at ? $user->last_active_at->diffForHumans() : 'Belum tercatat' }}</p>
        </div>
        <div class="metric-card">
            <p class="metric-label">Role Akun</p>
            <p class="metric-value" style="font-size:20px;text-transform:uppercase">{{ $user->role ?: 'Pengguna' }}</p>
            <p class="metric-sub">
                @if ($user->is(auth()->user()))
                    <span class="badge badge-success">Akun kamu</span>
                @elseif (auth()->user()->isAdmin())
                    <form class="inline-form" method="POST" action="{{ route('admin.users.role', $user) }}" style="margin-top:6px">
                        @csrf
                        @method('PATCH')
                        <select name="role" aria-label="Ubah role" style="min-height:28px;padding:2px 6px">
                            <option value="editor" @selected($user->role === 'editor')>Editor</option>
                            <option value="admin" @selected($user->role === 'admin')>Admin</option>
                        </select>
                        <button class="button button-primary button-sm" type="submit">Ubah</button>
                    </form>
                @endif
            </p>
        </div>
    </div>

    {{-- Progress Per Level (Abjad / Kata / Kalimat) --}}
    <div class="data-form" style="margin-bottom:30px">
        <div class="section-heading">
            <div>
                <p class="eyebrow">CAPAIAN JENJANG</p>
                <h2>Progres per Jenjang Belajar</h2>
            </div>
            <p>Penguasaan kosakata BISINDO berdasarkan kategori jenjang pembelajaran.</p>
        </div>

        <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(280px, 1fr));gap:18px">
            @foreach ($levelStats as $lvl)
                <div style="padding:16px 18px;border:1.5px solid var(--line);border-radius:var(--radius-md);background:var(--bg-surface)">
                    <div style="display:flex;justify-content:space-between;align-items:baseline;margin-bottom:8px">
                        <strong style="font-family:var(--font-heading);font-size:16px">{{ $lvl['label'] }}</strong>
                        <span style="font-weight:800;color:var(--text-forest)">{{ $lvl['learned'] }}/{{ $lvl['total'] }} ({{ $lvl['percent'] }}%)</span>
                    </div>
                    <div class="progress-track" style="height:10px" aria-hidden="true">
                        <div class="progress-fill" style="width: {{ min(100, $lvl['percent']) }}%"></div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Daftar Gesture yang Dikuasai --}}
    <div class="section-heading" style="margin-top:36px">
        <div>
            <p class="eyebrow">DAFTAR KOSAKATA</p>
            <h2>Gesture yang Dikuasai ({{ $learnedGestures->count() }})</h2>
        </div>
        <p>Kosakata yang telah ditandai selesai dipelajari oleh pengguna.</p>
    </div>
    <div class="table-wrap" style="margin-bottom:36px">
        <table>
            <thead>
                <tr>
                    <th>Gesture</th>
                    <th>Arti Bahasa Indonesia</th>
                    <th>Jenjang</th>
                    <th>Kategori</th>
                    <th>Tanggal Dipelajari</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($learnedGestures as $prog)
                    <tr>
                        <td><strong>{{ $prog->gesture?->word ?? '—' }}</strong><small class="secondary">{{ $prog->gesture?->slug }}</small></td>
                        <td>{{ $prog->gesture?->indonesian ?: '—' }}</td>
                        <td><span class="badge badge-draft">{{ \App\Models\Gesture::LEVELS[$prog->gesture?->level] ?? $prog->gesture?->level }}</span></td>
                        <td><span class="category-label">{{ $prog->gesture?->category }}</span></td>
                        <td>{{ $prog->learned_at ? $prog->learned_at->translatedFormat('d M Y H:i') : '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="empty-state">Pengguna ini belum menandai gesture yang dikuasai.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Riwayat Sesi Latihan & Nilai --}}
    <div class="section-heading">
        <div>
            <p class="eyebrow">EVALUASI &amp; SKOR</p>
            <h2>Riwayat Sesi Latihan ({{ $sessionsCount }})</h2>
        </div>
        <p>Catatan hasil evaluasi latihan kuis/practice pengguna.</p>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Waktu Latihan</th>
                    <th>Jenjang</th>
                    <th>Total Soal</th>
                    <th>Jawaban Benar</th>
                    <th>Skor Akhir</th>
                    <th>Durasi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($sessions as $session)
                    <tr>
                        <td>{{ $session->created_at ? $session->created_at->translatedFormat('d M Y H:i:s') : '—' }}</td>
                        <td>{{ \App\Models\Gesture::LEVELS[$session->level] ?? ($session->level ?: 'Campuran') }}</td>
                        <td>{{ $session->total }}</td>
                        <td><strong style="color:var(--text-forest)">{{ $session->correct }}</strong></td>
                        <td>
                            <strong style="font-size:15px;color:{{ $session->score >= 70 ? 'var(--text-forest)' : 'var(--text-berry)' }}">{{ $session->score }}%</strong>
                        </td>
                        <td>{{ $session->duration_seconds ? gmdate('i:s', $session->duration_seconds) : '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="empty-state">Belum ada riwayat sesi latihan untuk pengguna ini.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($sessions->hasPages())
        <div class="pagination">{{ $sessions->links() }}</div>
    @endif
@endsection
