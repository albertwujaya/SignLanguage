@extends('admin.layout')

@section('title', 'Tempat Sampah Gesture')

@section('content')
    <div class="page-heading">
        <div>
            <p class="eyebrow">ARSIP / TERHAPUS</p>
            <h1>Tempat Sampah Gesture</h1>
            <p class="page-lede">{{ $gestures->total() }} gesture berada di tempat sampah (soft deleted).</p>
        </div>
        <div class="toolbar-group">
            <a class="button button-quiet" href="{{ route('admin.gestures.index') }}">Kembali ke daftar aktif</a>
        </div>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Istilah &amp; Slug</th>
                    <th>Jenjang</th>
                    <th>Kategori</th>
                    <th>Waktu Dihapus</th>
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
                        <td>{{ $levels[$gesture->level] ?? $gesture->level }}</td>
                        <td><span class="category-label">{{ $gesture->category }}</span></td>
                        <td>{{ $gesture->deleted_at ? $gesture->deleted_at->translatedFormat('d M Y H:i') : '—' }}</td>
                        <td class="row-actions">
                            <form method="POST" action="{{ route('admin.gestures.restore', $gesture->id) }}" style="display:inline">
                                @csrf
                                <button class="text-action" type="submit">Pulihkan</button>
                            </form>

                            <form method="POST" action="{{ route('admin.gestures.force-delete', $gesture->id) }}" data-confirm="PERINGATAN: Menghapus permanen gesture '{{ $gesture->word }}' akan menghapus seluruh file media gambar dan video dari server serta tidak dapat dibatalkan. Lanjutkan?" data-confirm-title="Hapus Permanen Gesture" data-confirm-btn="Hapus Permanen">
                                @csrf
                                @method('DELETE')
                                <button class="text-action text-danger" type="submit">Hapus Permanen</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="empty-state">Tempat sampah kosong. Tidak ada gesture yang dihapus.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="pagination">{{ $gestures->links() }}</div>
@endsection
