@extends('admin.layout')

@section('title', 'Pratinjau: ' . $gesture->word)

@section('content')
    <div class="page-heading">
        <div>
            <p class="eyebrow">MODE PRATINJAU KONTEN</p>
            <h1>{{ $gesture->word }}</h1>
            <p class="page-lede">
                Status: 
                @if ($gesture->isPublished())
                    <span class="badge badge-success">Terbit (Published)</span>
                @else
                    <span class="badge badge-draft">Draft (Hanya terlihat di admin)</span>
                @endif
                · Jenjang: {{ $levels[$gesture->level] ?? $gesture->level }}
            </p>
        </div>
        <div class="toolbar-group">
            <a class="button button-primary" href="{{ route('admin.gestures.edit', $gesture) }}">Ubah Data</a>
            <form method="POST" action="{{ route('admin.gestures.toggle-status', $gesture) }}" style="display:inline">
                @csrf
                <button class="button button-quiet" type="submit">
                    {{ $gesture->isPublished() ? 'Jadikan Draft' : 'Terbitkan Sekarang' }}
                </button>
            </form>
            <a class="button button-quiet" href="{{ route('admin.gestures.index') }}">Kembali ke daftar</a>
        </div>
    </div>

    <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(320px, 1fr));gap:24px">
        {{-- Media Preview Box --}}
        <div class="data-form">
            <div class="section-heading" style="margin-bottom:14px">
                <div>
                    <p class="eyebrow">VISUAL &amp; MEDIA</p>
                    <h2>Media Gerakan</h2>
                </div>
            </div>

            @if ($gesture->video_path)
                <div style="margin-bottom:18px">
                    <label style="display:block;font-size:13px;font-weight:700;margin-bottom:6px;color:var(--t2)">Video Gerakan BISINDO</label>
                    <video src="{{ $gesture->videoFileUrl() }}" controls style="width:100%;max-height:280px;border-radius:var(--radius-md);background:#000;display:block;box-shadow:var(--shadow-soft)"></video>
                </div>
            @endif

            @if ($gesture->image_path)
                <div style="margin-bottom:18px">
                    <label style="display:block;font-size:13px;font-weight:700;margin-bottom:6px;color:var(--t2)">Gambar Ilustrasi Posisi</label>
                    <img src="{{ $gesture->imageUrl() }}" alt="{{ $gesture->word }}" style="width:100%;max-height:260px;object-fit:contain;border-radius:var(--radius-md);border:1.5px solid var(--line);background:#ffffff;display:block;box-shadow:var(--shadow-soft)">
                </div>
            @endif

            @if (! $gesture->image_path && ! $gesture->video_path)
                <p class="notice notice-error">Belum ada media gambar atau video yang diunggah untuk gesture ini.</p>
            @endif
        </div>

        {{-- Gesture Detail Box (As seen by learner) --}}
        <div class="data-form">
            <div class="section-heading" style="margin-bottom:14px">
                <div>
                    <p class="eyebrow">DETAIL KOSAKATA</p>
                    <h2>Informasi &amp; Panduan</h2>
                </div>
                <span class="category-label">{{ $gesture->category }}</span>
            </div>

            <div style="margin-bottom:16px">
                <strong style="color:var(--t3);font-size:12px;text-transform:uppercase;letter-spacing:.06em">Arti Bahasa Indonesia:</strong>
                <p style="margin:2px 0 0;font-size:17px;font-weight:700;color:var(--ink)">{{ $gesture->indonesian ?: '—' }}</p>
            </div>

            <div style="margin-bottom:16px">
                <strong style="color:var(--t3);font-size:12px;text-transform:uppercase;letter-spacing:.06em">Deskripsi:</strong>
                <p style="margin:4px 0 0;line-height:1.6;color:var(--ink)">{{ $gesture->description }}</p>
            </div>

            @if ($gesture->hand_position)
                <div style="margin-bottom:16px">
                    <strong style="color:var(--t3);font-size:12px;text-transform:uppercase;letter-spacing:.06em">Posisi Tangan:</strong>
                    <p style="margin:4px 0 0;line-height:1.5;color:var(--ink)">{{ $gesture->hand_position }}</p>
                </div>
            @endif

            @if ($gesture->movement)
                <div style="margin-bottom:16px">
                    <strong style="color:var(--t3);font-size:12px;text-transform:uppercase;letter-spacing:.06em">Gerakan:</strong>
                    <p style="margin:4px 0 0;line-height:1.5;color:var(--ink)">{{ $gesture->movement }}</p>
                </div>
            @endif

            @if ($gesture->common_mistake)
                <div style="margin-bottom:16px">
                    <strong style="color:var(--text-berry);font-size:12px;text-transform:uppercase;letter-spacing:.06em">Kesalahan Umum:</strong>
                    <p style="margin:4px 0 0;line-height:1.5;color:var(--text-berry);font-weight:600">{{ $gesture->common_mistake }}</p>
                </div>
            @endif

            @if ($gesture->visual_key)
                <div style="margin-bottom:16px">
                    <strong style="color:var(--t3);font-size:12px;text-transform:uppercase;letter-spacing:.06em">Kunci Visual:</strong>
                    <p style="margin:4px 0 0;line-height:1.5;color:var(--ink)">{{ $gesture->visual_key }}</p>
                </div>
            @endif
        </div>
    </div>
@endsection
