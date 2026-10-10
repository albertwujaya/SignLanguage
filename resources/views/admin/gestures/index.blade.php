@extends('admin.layout')

@section('title', 'Data Gesture')

@section('content')
    <div class="page-heading">
        <div>
            <p class="eyebrow">KAMUS / DATA</p>
            <h1>Data Gesture</h1>
            <p class="page-lede">{{ $gestures->total() }} kosakata ditampilkan · {{ $publishedCount }} terbit · {{ $draftCount }} draft</p>
        </div>
        <div class="toolbar-group">
            <a class="button button-primary" href="{{ route('admin.gestures.create') }}">
                <span aria-hidden="true">+</span> Tambah Gesture
            </a>
        </div>
    </div>

    {{-- Filter & Search Toolbar --}}
    <form method="GET" action="{{ route('admin.gestures.index') }}" class="toolbar-bar" style="background:var(--white);padding:14px 16px;border:1px solid var(--line);border-radius:6px;margin-bottom:20px">
        <div class="toolbar-group" style="flex:1;min-width:240px">
            <label class="sr-only" for="q">Cari gesture</label>
            <input id="q" type="search" name="q" value="{{ request('q') }}" placeholder="Cari kata, arti, atau slug..." style="max-width:260px">
            
            <select name="level" aria-label="Filter jenjang" style="max-width:150px">
                <option value="">Semua Jenjang</option>
                @foreach ($levels as $key => $label)
                    <option value="{{ $key }}" @selected(request('level') === $key)>{{ $label }}</option>
                @endforeach
            </select>

            <select name="status" aria-label="Filter status" style="max-width:140px">
                <option value="">Semua Status</option>
                <option value="published" @selected(request('status') === 'published')>Terbit (Published)</option>
                <option value="draft" @selected(request('status') === 'draft')>Draft</option>
            </select>
        </div>
        <div class="toolbar-group">
            <button class="button button-primary button-sm" type="submit">Terapkan</button>
            @if (request()->hasAny(['q', 'level', 'status']))
                <a class="button button-quiet button-sm" href="{{ route('admin.gestures.index') }}">Reset</a>
            @endif
        </div>
    </form>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Istilah &amp; Slug</th>
                    <th>Terjemahan</th>
                    <th>Status</th>
                    <th>Jenjang</th>
                    <th>Kategori</th>
                    <th>Media</th>
                    <th>Kesulitan</th>
                    <th class="actions-heading">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($gestures as $gesture)
                    <tr>
                        <td>
                            <strong>{{ $gesture->word }}</strong>
                            <small class="secondary">{{ $gesture->slug }}</small>
                        </td>
                        <td>{{ $gesture->indonesian ?: '—' }}</td>
                        <td>
                            @if ($gesture->isPublished())
                                <span class="badge badge-success">Terbit</span>
                            @else
                                <span class="badge badge-draft">Draft</span>
                            @endif
                        </td>
                        <td>{{ $levels[$gesture->level] ?? $gesture->level }}</td>
                        <td><span class="category-label">{{ $gesture->category }}</span></td>
                        <td>
                            {{ $gesture->image_path ? 'Gambar' : '' }}
                            {{ $gesture->image_path && $gesture->video_path ? ' + ' : '' }}
                            {{ $gesture->video_path ? 'Video' : '' }}
                            {{ ! $gesture->image_path && ! $gesture->video_path ? '—' : '' }}
                        </td>
                        <td>{{ $gesture->difficulty }}</td>
                        <td class="row-actions">
                            <a class="icon-action" href="{{ route('admin.gestures.preview', $gesture) }}" title="Pratinjau" aria-label="Pratinjau {{ $gesture->word }}">
                                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/></svg>
                            </a>
                            <a class="icon-action" href="{{ route('admin.gestures.edit', $gesture) }}" title="Ubah" aria-label="Ubah {{ $gesture->word }}">
                                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                            </a>

                            {{-- Toggle Status Button --}}
                            <form method="POST" action="{{ route('admin.gestures.toggle-status', $gesture) }}" style="display:inline">
                                @csrf
                                @php($toggleLabel = $gesture->isPublished() ? 'Jadikan Draft' : 'Terbitkan')
                                <button class="icon-action" type="submit" title="{{ $toggleLabel }}" aria-label="{{ $toggleLabel }} {{ $gesture->word }}">
                                    @if ($gesture->isPublished())
                                        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 14 4 9l5-5"/><path d="M4 9h10.5a5.5 5.5 0 0 1 0 11H11"/></svg>
                                    @else
                                        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m22 2-7 20-4-9-9-4Z"/><path d="M22 2 11 13"/></svg>
                                    @endif
                                </button>
                            </form>

                            @if (auth()->user()->isAdmin())
                                <form method="POST" action="{{ route('admin.gestures.destroy', $gesture) }}" data-confirm="Apakah kamu yakin ingin memindahkan gesture '{{ $gesture->word }}' ke tempat sampah?" data-confirm-title="Pindahkan ke Tempat Sampah" data-confirm-btn="Pindahkan ke Sampah">
                                    @csrf
                                    @method('DELETE')
                                    <button class="icon-action icon-danger" type="submit" title="Hapus" aria-label="Hapus {{ $gesture->word }}">
                                        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><path d="M10 11v6"/><path d="M14 11v6"/></svg>
                                    </button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="empty-state">Belum ada gesture yang sesuai kriteria pencarian/filter.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="pagination">{{ $gestures->links() }}</div>
@endsection