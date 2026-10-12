@extends('admin.layout')

@section('title', $gesture->exists ? 'Ubah gesture' : 'Tambah gesture')

@section('content')
    <div class="page-heading">
        <div>
            <p class="eyebrow">KAMUS / {{ $gesture->exists ? 'UBAH DATA' : 'DATA BARU' }}</p>
            <h1>{{ $gesture->exists ? 'Ubah gesture' : 'Tambah gesture' }}</h1>
            <p class="page-lede">Isi informasi yang akan tampil pada kamus dan modul latihan BISINDO.</p>
        </div>
        <div class="toolbar-group">
            @if ($gesture->exists)
                <a class="button button-quiet" href="{{ route('admin.gestures.preview', $gesture) }}">Pratinjau</a>
            @endif
            <a class="button button-quiet" href="{{ route('admin.gestures.index') }}">Kembali ke daftar</a>
        </div>
    </div>

    <form class="data-form" method="POST" enctype="multipart/form-data" action="{{ $gesture->exists ? route('admin.gestures.update', $gesture) : route('admin.gestures.store') }}">
        @csrf
        @if ($gesture->exists) @method('PUT') @endif
        <div class="form-grid">
            <div class="field">
                <label for="word">Istilah</label>
                <input id="word" name="word" value="{{ old('word', $gesture->word) }}" required>
            </div>
            <div class="field">
                <label for="indonesian">Arti dalam bahasa Indonesia</label>
                <input id="indonesian" name="indonesian" value="{{ old('indonesian', $gesture->indonesian) }}">
            </div>
            <div class="field">
                <label for="slug">Slug</label>
                <input id="slug" name="slug" value="{{ old('slug', $gesture->slug) }}" required>
                <small>Dipakai sebagai identitas unik di API dan URL.</small>
            </div>
            <div class="field">
                <label for="status">Status Publikasi</label>
                <select id="status" name="status" required>
                    <option value="published" @selected(old('status', $gesture->status ?? 'published') === 'published')>Terbit (Published - Tampil untuk pengguna)</option>
                    <option value="draft" @selected(old('status', $gesture->status) === 'draft')>Draft (Disembunyikan dari pengguna)</option>
                </select>
            </div>
            @php
                $currentLevelVal = old('level', $fixedLevel ?? $gesture->level ?? 'kata');
                $isAutoCategory = in_array($currentLevelVal, ['abjad', 'angka']);
                $autoCategoryVal = $currentLevelVal === 'abjad' ? 'alpha' : ($currentLevelVal === 'angka' ? 'num' : null);
            @endphp

            <div class="field">
                <label>Jenjang belajar</label>
                <div style="padding:10px 14px;background:var(--cream,#f6efdd);border:1px solid var(--line);border-radius:6px;font-weight:700;color:var(--dark,#163336)">
                    {{ $levels[$currentLevelVal] ?? ucfirst($currentLevelVal) }}
                </div>
                <input type="hidden" name="level" value="{{ $currentLevelVal }}">
                <small>Jenjang ditentukan otomatis berdasarkan menu tempat gesture dibuat.</small>
            </div>

            @if ($isAutoCategory)
                <input type="hidden" name="category" value="{{ old('category', $autoCategoryVal) }}">
            @else
                <div class="field">
                    <label for="category">Kategori</label>
                    <select id="category" name="category" required>
                        @foreach ($categories as $catKey)
                            <option value="{{ $catKey }}" @selected(old('category', $gesture->category) === $catKey)>
                                {{ $categoryLabels[$catKey] ?? $catKey }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif
            <div class="field">
                <label for="sort_order">Urutan dalam jenjang</label>
                <input id="sort_order" type="number" min="0" name="sort_order" value="{{ old('sort_order', $gesture->sort_order ?? 0) }}">
                <small>Angka kecil tampil lebih dulu (mis. A=1, B=2).</small>
            </div>
            <div class="field">
                <label for="difficulty">Tingkat kesulitan</label>
                <select id="difficulty" name="difficulty" required>
                    @foreach ($difficulties as $difficulty)
                        <option value="{{ $difficulty }}" @selected(old('difficulty', $gesture->difficulty ?: 'Easy') === $difficulty)>{{ $difficulty }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label for="emoji">Emoji (opsional)</label>
                <input id="emoji" name="emoji" value="{{ old('emoji', $gesture->emoji) }}" maxlength="32">
            </div>
            <div class="field field-wide">
                <label for="description">Deskripsi</label>
                <textarea id="description" name="description" rows="3" required>{{ old('description', $gesture->description) }}</textarea>
            </div>
            <div class="field">
                <label for="hand_position">Posisi tangan</label>
                <textarea id="hand_position" name="hand_position" rows="3">{{ old('hand_position', $gesture->hand_position) }}</textarea>
            </div>
            <div class="field">
                <label for="movement">Gerakan</label>
                <textarea id="movement" name="movement" rows="3">{{ old('movement', $gesture->movement) }}</textarea>
            </div>
            <div class="field">
                <label for="common_mistake">Kesalahan umum</label>
                <textarea id="common_mistake" name="common_mistake" rows="3">{{ old('common_mistake', $gesture->common_mistake) }}</textarea>
            </div>
            <div class="field">
                <label for="visual_key">Kunci visual</label>
                <input id="visual_key" name="visual_key" value="{{ old('visual_key', $gesture->visual_key) }}">
            </div>
            <div class="field">
                <label for="image">Gambar gerakan tangan (JPG/PNG/WebP, maks 3 MB)</label>
                @if ($gesture->image_path)
                    <img src="{{ $gesture->imageUrl() }}" alt="Gambar saat ini" style="max-width:180px;border-radius:6px;display:block;margin-bottom:8px">
                    <label style="font-weight:400"><input type="checkbox" name="remove_image" value="1" style="width:auto;min-height:0"> Hapus gambar ini</label>
                @endif
                <input id="image" type="file" name="image" accept="image/jpeg,image/png,image/webp">
            </div>
            <div class="field">
                <label for="video">Video gerakan (MP4/WebM, maks 30 MB)</label>
                @if ($gesture->video_path)
                    <video src="{{ $gesture->videoFileUrl() }}" controls muted style="max-width:240px;border-radius:6px;display:block;margin-bottom:8px"></video>
                    <label style="font-weight:400"><input type="checkbox" name="remove_video" value="1" style="width:auto;min-height:0"> Hapus video ini</label>
                @endif
                <input id="video" type="file" name="video" accept="video/mp4,video/webm">
            </div>
            <div class="field field-wide">
                <label for="video_url">URL video eksternal (opsional)</label>
                <input id="video_url" type="url" name="video_url" value="{{ old('video_url', $gesture->video_url) }}" placeholder="https://">
            </div>
        </div>
        <div class="form-actions">
            <a class="button button-quiet" href="{{ route('admin.gestures.index') }}">Batal</a>
            <button class="button button-primary" type="submit">Simpan gesture</button>
        </div>
    </form>
@endsection