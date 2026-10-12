@extends('admin.layout')

@php
    $isStaff = ($currentTab ?? 'students') === 'staff';
@endphp

@section('title', $isStaff ? 'Tim Pengelola & Staf' : 'Progres Belajar Siswa')

@section('content')
    <div class="page-heading">
        <div>
            <p class="eyebrow">PENGGUNA &amp; PROGRES</p>
            <h1>{{ $isStaff ? 'Tim Pengelola (Admin & Editor)' : 'Progres Belajar Siswa' }}</h1>
            <p class="page-lede">
                @if ($isStaff)
                    Kelola akun pengelola materi: {{ $adminCount }} admin dan {{ $editorCount }} editor aktif.
                @else
                    Memantau pencapaian dan nilai {{ $userCount }} siswa terdaftar · {{ $totalPublished }} materi terbit.
                @endif
            </p>
        </div>
        <div class="toolbar-group">
            <a class="button button-quiet" href="{{ route('admin.users.export') }}">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                Ekspor CSV
            </a>
        </div>
    </div>

    {{-- Tabs Pemisah: Siswa vs Tim Pengelola --}}
    <div style="display:flex;gap:10px;border-bottom:2px solid var(--line);margin-bottom:24px" role="tablist">
        <a href="{{ route('admin.users.index') }}" 
           style="padding:10px 18px;font-family:var(--font-heading);font-weight:700;font-size:14px;text-decoration:none;border-bottom:3px solid {{ !$isStaff ? 'var(--mustard,#F2B45A)' : 'transparent' }};color:{{ !$isStaff ? 'var(--ink,#2B2118)' : 'var(--t3)' }};margin-bottom:-2px">
            👥 Progres Siswa ({{ $userCount }})
        </a>
        <a href="{{ route('admin.users.index', ['tab' => 'staff']) }}" 
           style="padding:10px 18px;font-family:var(--font-heading);font-weight:700;font-size:14px;text-decoration:none;border-bottom:3px solid {{ $isStaff ? 'var(--mustard,#F2B45A)' : 'transparent' }};color:{{ $isStaff ? 'var(--ink,#2B2118)' : 'var(--t3)' }};margin-bottom:-2px">
            🛡️ Tim Pengelola &amp; Staf ({{ $adminCount + $editorCount }})
        </a>
    </div>

    {{-- Bagian Tim Pengelola: Info Akun Admin & Form Tambah Editor (HANYA tampil di tab staff) --}}
    @if ($isStaff)
        <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(320px, 1fr));gap:20px;margin-bottom:24px">
            {{-- Panel 1: Info Akun Admin & Staf Aktif --}}
            <section style="background:var(--white);padding:20px;border:1px solid var(--line);border-radius:8px">
                <div class="section-heading" style="margin-bottom:14px">
                    <div>
                        <p class="eyebrow">INFO TIM PENGELOLA</p>
                        <h2 style="margin:4px 0 6px;font-size:18px">Daftar Akun Admin &amp; Staf</h2>
                    </div>
                    <p style="color:var(--muted);font-size:13px;margin:0">
                        Total <strong>{{ $adminCount }}</strong> Admin dan <strong>{{ $editorCount }}</strong> Editor aktif.
                    </p>
                </div>

                <div style="display:flex;flex-direction:column;gap:10px;max-height:280px;overflow-y:auto;padding-right:4px">
                    @forelse ($staffMembers as $staff)
                        <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 12px;background:var(--bg-surface,#FAFAF7);border:1px solid var(--line);border-radius:6px;gap:10px">
                            <div style="display:flex;align-items:center;gap:10px;min-width:0">
                                <div style="width:34px;height:34px;border-radius:50%;background:var(--mustard,#F2B45A);color:var(--ink,#2B2118);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:14px;flex-shrink:0" aria-hidden="true">
                                    {{ strtoupper(substr($staff->name, 0, 1)) }}
                                </div>
                                <div style="min-width:0;overflow:hidden">
                                    <div style="font-weight:700;font-size:14px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                                        {{ $staff->name }}
                                        @if ($staff->is(auth()->user()))
                                            <span style="font-size:11px;font-weight:600;color:var(--forest,#2F7A59)">(Anda)</span>
                                        @endif
                                    </div>
                                    <div style="font-size:12px;color:var(--t3);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $staff->email }}</div>
                                </div>
                            </div>
                            <div style="flex-shrink:0;text-align:right">
                                @if ($staff->role === 'admin')
                                    <span class="badge badge-warning" style="font-size:11px">ADMIN</span>
                                @else
                                    <span class="badge badge-draft" style="font-size:11px">EDITOR</span>
                                @endif
                                <div style="font-size:11px;color:var(--t3);margin-top:2px">
                                    {{ $staff->last_active_at ? $staff->last_active_at->diffForHumans() : 'Belum aktif' }}
                                </div>
                            </div>
                        </div>
                    @empty
                        <p style="color:var(--t3);font-size:13px">Belum ada akun pengelola.</p>
                    @endforelse
                </div>
            </section>

            {{-- Panel 2: Tambah Editor / Staf Baru --}}
            <section class="account-create" style="background:var(--white);padding:20px;border:1px solid var(--line);border-radius:8px" aria-labelledby="create-account-title">
                <div class="section-heading" style="margin-bottom:14px">
                    <div>
                        <p class="eyebrow">AKUN PENGELOLA BARU</p>
                        <h2 id="create-account-title" style="margin:4px 0 6px;font-size:18px">Tambah Editor / Staf</h2>
                    </div>
                    <p style="color:var(--muted);font-size:13px;margin:0">
                        Akun baru otomatis mendapat hak akses editor mengelola gesture.
                    </p>
                </div>

                @if ($errors->any())
                    <div class="flash flash-error" style="margin-bottom:14px;padding:10px 12px;background:#fef2f2;border:1px solid #f87171;border-radius:6px;color:#991b1b" role="alert">
                        <strong>Gagal membuat akun:</strong>
                        <ul style="margin:4px 0 0 16px;padding:0;font-size:13px">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form class="account-form" method="POST" action="{{ route('admin.users.store') }}">
                    @csrf
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                        <div class="field" style="margin:0">
                            <label for="name" style="font-size:13px">Nama</label>
                            <input id="name" name="name" value="{{ old('name') }}" autocomplete="name" required style="width:100%">
                        </div>
                        <div class="field" style="margin:0">
                            <label for="email" style="font-size:13px">Email</label>
                            <input id="email" type="email" name="email" value="{{ old('email') }}" autocomplete="email" required style="width:100%">
                        </div>
                        <div class="field" style="margin:0">
                            <label for="password" style="font-size:13px">Kata sandi awal (min 12)</label>
                            <input id="password" type="password" name="password" autocomplete="new-password" minlength="12" required style="width:100%">
                        </div>
                        <div class="field" style="margin:0">
                            <label for="password_confirmation" style="font-size:13px">Ulangi kata sandi</label>
                            <input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" minlength="12" required style="width:100%">
                        </div>
                    </div>
                    <div class="form-actions" style="margin-top:14px">
                        <button class="button button-primary" type="submit">Buat akun editor</button>
                    </div>
                </form>
            </section>
        </div>
    @endif

    {{-- Filter & Search Toolbar --}}
    <form method="GET" action="{{ route('admin.users.index') }}" class="toolbar-bar" style="background:var(--white);padding:14px 16px;border:1px solid var(--line);border-radius:6px;margin-bottom:20px">
        @if ($isStaff)
            <input type="hidden" name="tab" value="staff">
        @endif

        <div class="toolbar-group" style="flex:1;min-width:240px">
            <label class="sr-only" for="q">Cari Pengguna</label>
            <input id="q" type="search" name="q" value="{{ request('q') }}" placeholder="Cari nama atau email..." style="max-width:280px">

            @if ($isStaff)
                <select name="role" aria-label="Filter role" style="max-width:140px">
                    <option value="">Semua Staf</option>
                    <option value="admin" @selected(request('role') === 'admin')>Admin</option>
                    <option value="editor" @selected(request('role') === 'editor')>Editor</option>
                </select>
            @else
                <select name="status" aria-label="Filter progres" style="max-width:160px">
                    <option value="">Semua Progres</option>
                    <option value="started" @selected(request('status') === 'started')>Sudah Mulai Belajar</option>
                    <option value="not_started" @selected(request('status') === 'not_started')>Belum Mulai Belajar</option>
                </select>
            @endif
        </div>
        <div class="toolbar-group">
            <select name="sort" aria-label="Urutkan berdasarkan" style="max-width:160px">
                <option value="name" @selected(request('sort') === 'name')>Urut: Nama (A-Z)</option>
                @if (! $isStaff)
                    <option value="progress" @selected(request('sort') === 'progress')>Urut: Progres Tertinggi</option>
                    <option value="score" @selected(request('sort') === 'score')>Urut: Nilai Tertinggi</option>
                @endif
                <option value="created_at" @selected(request('sort') === 'created_at')>Urut: Terdaftar Baru</option>
                <option value="last_active" @selected(request('sort') === 'last_active')>Urut: Terakhir Aktif</option>
            </select>
            <button class="button button-primary button-sm" type="submit">Terapkan</button>
            @if (request()->hasAny(['q', 'role', 'status', 'sort']))
                <a class="button button-quiet button-sm" href="{{ route('admin.users.index', array_filter(['tab' => $isStaff ? 'staff' : null])) }}">Reset</a>
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
                    @if (! $isStaff)
                        <th>Gesture Dikuasai</th>
                        <th>Progres Belajar</th>
                        <th>Rata-rata Nilai</th>
                    @endif
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
                                <span class="category-label">Siswa</span>
                            @endif
                        </td>
                        <td>
                            <div>{{ $user->created_at ? $user->created_at->translatedFormat('d M Y') : '—' }}</div>
                            <small class="secondary">
                                Aktif: {{ $user->last_active_at ? $user->last_active_at->diffForHumans() : 'Belum aktif' }}
                            </small>
                        </td>
                        @if (! $isStaff)
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
                        @endif
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
                        <td colspan="{{ $isStaff ? 4 : 7 }}" class="empty-state">Tidak ada data pengguna yang sesuai pencarian/filter.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="pagination">{{ $users->links() }}</div>
@endsection