@extends('admin.layout')

@section('title', 'Data & Progres Pengguna')

@section('content')
    <div class="page-heading">
        <div>
            <p class="eyebrow">PENGGUNA &amp; PROGRES</p>
            <h1>Daftar pengguna &amp; progres</h1>
            <p class="page-lede">{{ $users->total() }} akun terdaftar · {{ $adminCount }} admin · {{ $totalPublished }} gesture terbit aktif</p>
        </div>
        <div class="toolbar-group">
            <a class="button button-quiet" href="{{ route('admin.users.export') }}">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                Ekspor CSV
            </a>
        </div>
    </div>

    {{-- Filter & Search Toolbar --}}
    <form method="GET" action="{{ route('admin.users.index') }}" class="toolbar-bar" style="background:var(--white);padding:14px 16px;border:1px solid var(--line);border-radius:6px;margin-bottom:20px">
        <div class="toolbar-group" style="flex:1;min-width:240px">
            <label class="sr-only" for="q">Cari Pengguna</label>
            <input id="q" type="search" name="q" value="{{ request('q') }}" placeholder="Cari nama atau email..." style="max-width:280px">
            <select name="role" aria-label="Filter role" style="max-width:140px">
                <option value="">Semua Role</option>
                <option value="admin" @selected(request('role') === 'admin')>Admin</option>
                <option value="editor" @selected(request('role') === 'editor')>Editor</option>
                <option value="user" @selected(request('role') === 'user')>Pengguna Biasa</option>
            </select>
            <select name="status" aria-label="Filter progres" style="max-width:160px">
                <option value="">Semua Progres</option>
                <option value="started" @selected(request('status') === 'started')>Sudah Mulai Belajar</option>
                <option value="not_started" @selected(request('status') === 'not_started')>Belum Mulai Belajar</option>
            </select>
        </div>
        <div class="toolbar-group">
            <select name="sort" aria-label="Urutkan berdasarkan" style="max-width:160px">
                <option value="name" @selected(request('sort') === 'name')>Urut: Nama (A-Z)</option>
                <option value="progress" @selected(request('sort') === 'progress')>Urut: Progres Tertinggi</option>
                <option value="score" @selected(request('sort') === 'score')>Urut: Nilai Tertinggi</option>
                <option value="created_at" @selected(request('sort') === 'created_at')>Urut: Terdaftar Baru</option>
                <option value="last_active" @selected(request('sort') === 'last_active')>Urut: Terakhir Aktif</option>
            </select>
            <button class="button button-primary button-sm" type="submit">Terapkan</button>
            @if (request()->hasAny(['q', 'role', 'status', 'sort']))
                <a class="button button-quiet button-sm" href="{{ route('admin.users.index') }}">Reset</a>
            @endif
        </div>
    </form>

    {{-- Users Table --}}
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Pengguna</th>
                    <th>Role</th>
                    <th>Terdaftar / Aktif</th>
                    <th>Gesture Dikuasai</th>
                    <th>Progres Belajar</th>
                    <th>Rata-rata Nilai</th>
                    <th class="actions-heading">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($users as $user)
                    @php
                        $progressPercent = $totalPublished > 0 ? round(($user->learned_count / $totalPublished) * 100, 1) : 0;
                    @endphp
                    <tr>
                        <td>
                            <strong>{{ $user->name }}</strong>
                            <small class="secondary">{{ $user->email }}</small>
                            @if ($user->is(auth()->user()))
                                <span class="badge badge-success" style="margin-top:3px">Akun Kamu</span>
                            @endif
                        </td>
                        <td>
                            @if ($user->role === 'admin')
                                <span class="badge badge-warning">Admin</span>
                            @elseif ($user->role === 'editor')
                                <span class="badge badge-draft">Editor</span>
                            @else
                                <span class="category-label">Pengguna</span>
                            @endif
                        </td>
                        <td>
                            <div>{{ $user->created_at ? $user->created_at->translatedFormat('d M Y') : '—' }}</div>
                            <small class="secondary">
                                Aktif: {{ $user->last_active_at ? $user->last_active_at->diffForHumans() : 'Belum aktif' }}
                            </small>
                        </td>
                        <td>
                            <strong>{{ $user->learned_count }}</strong>
                            <small class="secondary">dari {{ $totalPublished }} gesture</small>
                        </td>
                        <td style="min-width:170px">
                            <div class="progress-container">
                                <div class="progress-track" aria-hidden="true">
                                    <div class="progress-fill" style="width: {{ min(100, $progressPercent) }}%"></div>
                                </div>
                                <span class="progress-text">{{ $progressPercent }}%</span>
                            </div>
                            <small class="secondary">{{ $user->learned_count }}/{{ $totalPublished }} dikuasai</small>
                        </td>
                        <td>
                            @if ($user->avg_score !== null)
                                <strong>{{ round((float) $user->avg_score, 1) }}%</strong>
                                <small class="secondary">{{ $user->sessions_count }} sesi latihan</small>
                            @else
                                <span class="secondary">Belum latihan</span>
                            @endif
                        </td>
                        <td class="row-actions">
                            <a class="text-action" href="{{ route('admin.users.show', $user) }}">Detail</a>
                            @unless ($user->is(auth()->user()))
                                <form method="POST" action="{{ route('admin.users.destroy', $user) }}" onsubmit="return confirm('Hapus akun {{ $user->name }}?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="text-action text-danger" type="submit">Hapus</button>
                                </form>
                            @endunless
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="empty-state">Tidak ada data pengguna yang sesuai pencarian/filter.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="pagination">{{ $users->links() }}</div>

    {{-- Add Editor Section --}}
    <section class="account-create" style="margin-top:40px" aria-labelledby="create-account-title">
        <div class="section-heading">
            <div>
                <p class="eyebrow">AKUN PENGELOLA BARU</p>
                <h2 id="create-account-title">Tambah Editor / Staf</h2>
            </div>
            <p>Akun baru otomatis mendapat akses editor untuk mengelola data gesture.</p>
        </div>
        <form class="account-form" method="POST" action="{{ route('admin.users.store') }}">
            @csrf
            <div class="field">
                <label for="name">Nama</label>
                <input id="name" name="name" value="{{ old('name') }}" autocomplete="name" required>
            </div>
            <div class="field">
                <label for="email">Email</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" autocomplete="email" required>
            </div>
            <div class="field">
                <label for="password">Kata sandi awal</label>
                <input id="password" type="password" name="password" autocomplete="new-password" minlength="12" required>
            </div>
            <div class="field">
                <label for="password_confirmation">Ulangi kata sandi</label>
                <input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" minlength="12" required>
            </div>
            <div class="form-actions">
                <button class="button button-primary" type="submit">Buat akun editor</button>
            </div>
        </form>
    </section>
@endsection