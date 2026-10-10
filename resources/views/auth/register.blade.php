@extends('auth.layout', ['title' => 'Daftar'])

@section('content')
    <h2>Buat akun</h2>
    <p class="form-intro">Gratis. Progres dan nilai latihanmu tersimpan di akun.</p>
    @if ($errors->any())
        <div class="notice" role="alert">{{ $errors->first() }}</div>
    @endif
    <form method="POST" action="{{ route('register.store') }}">
        @csrf
        <label for="name">Nama</label>
        <input id="name" name="name" value="{{ old('name') }}" autocomplete="name" required>

        <label for="email">Email</label>
        <input id="email" type="email" name="email" value="{{ old('email') }}" autocomplete="email" required>

        <label for="password">Kata sandi (min. 8 karakter)</label>
        <input id="password" type="password" name="password" autocomplete="new-password" required>

        <label for="password_confirmation">Ulangi kata sandi</label>
        <input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" required>
        <button type="submit">Daftar</button>
    </form>
    <p class="form-switch">Sudah punya akun? <a href="{{ route('login') }}" class="form-link">Masuk</a></p>
@endsection
