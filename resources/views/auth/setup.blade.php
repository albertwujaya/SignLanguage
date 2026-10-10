@extends('auth.layout', ['title' => 'Buat admin'])

@section('content')
    <h2>Buat akun admin</h2>
    <p class="form-intro">Akun pertama mendapat akses administrator. Halaman setup akan tertutup setelah akun dibuat.</p>
    <form method="POST" action="{{ route('setup.store') }}">
        @csrf
        <label for="name">Nama</label>
        <input id="name" name="name" value="{{ old('name') }}" autocomplete="name" required>
        @error('name')<p class="error">{{ $message }}</p>@enderror

        <label for="email">Email</label>
        <input id="email" type="email" name="email" value="{{ old('email') }}" autocomplete="email" required>
        @error('email')<p class="error">{{ $message }}</p>@enderror

        <label for="password">Kata sandi</label>
        <input id="password" type="password" name="password" autocomplete="new-password" minlength="12" required>
        @error('password')<p class="error">{{ $message }}</p>@enderror

        <label for="password_confirmation">Ulangi kata sandi</label>
        <input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" minlength="12" required>
        <button type="submit">Buat admin</button>
    </form>
@endsection