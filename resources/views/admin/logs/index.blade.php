@extends('admin.layout')

@section('title', 'Log Aktivitas Admin')

@section('content')
    <div class="page-heading">
        <div>
            <p class="eyebrow">AUDIT TRAIL</p>
            <h1>Log Aktivitas Admin</h1>
            <p class="page-lede">{{ $logs->total() }} catatan aktivitas admin terekam.</p>
        </div>
        <div class="toolbar-group">
            <a class="button button-quiet" href="{{ route('admin.dashboard') }}">Ke Dashboard</a>
        </div>
    </div>

    {{-- Filter Toolbar --}}
    <form method="GET" action="{{ route('admin.logs.index') }}" class="toolbar-bar" style="background:var(--white);padding:14px 16px;border:1px solid var(--line);border-radius:6px;margin-bottom:20px">
        <div class="toolbar-group" style="flex:1;min-width:240px">
            <select name="admin_id" aria-label="Filter Admin" style="max-width:200px">
                <option value="">Semua Admin / Staf</option>
                @foreach ($admins as $admin)
                    <option value="{{ $admin->id }}" @selected(request('admin_id') == $admin->id)>{{ $admin->name }}</option>
                @endforeach
            </select>

            <select name="action" aria-label="Filter Aksi" style="max-width:180px">
                <option value="">Semua Jenis Aksi</option>
                @foreach ($actions as $act)
                    <option value="{{ $act }}" @selected(request('action') === $act)>{{ strtoupper($act) }}</option>
                @endforeach
            </select>
        </div>
        <div class="toolbar-group">
            <button class="button button-primary button-sm" type="submit">Terapkan Filter</button>
            @if (request()->hasAny(['admin_id', 'action']))
                <a class="button button-quiet button-sm" href="{{ route('admin.logs.index') }}">Reset</a>
            @endif
        </div>
    </form>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Waktu Kejadian</th>
                    <th>Pelaksana</th>
                    <th>Jenis Aksi</th>
                    <th>Ringkasan Tindakan</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($logs as $log)
                    <tr>
                        <td style="white-space:nowrap">{{ $log->created_at ? $log->created_at->translatedFormat('d M Y H:i:s') : '—' }}</td>
                        <td>
                            <strong>{{ $log->user?->name ?? 'Sistem / Akun Terhapus' }}</strong>
                            <small class="secondary">{{ $log->user?->email }}</small>
                        </td>
                        <td>
                            @php
                                $badgeClass = match($log->action) {
                                    'create', 'publish', 'restore' => 'badge-success',
                                    'delete', 'force_delete' => 'badge-danger',
                                    'draft' => 'badge-warning',
                                    default => 'badge-draft',
                                };
                            @endphp
                            <span class="badge {{ $badgeClass }}">{{ strtoupper($log->action) }}</span>
                        </td>
                        <td>{{ $log->summary }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="empty-state">Belum ada catatan log aktivitas yang sesuai.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="pagination">{{ $logs->links() }}</div>
@endsection
