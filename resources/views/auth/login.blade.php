@extends('auth.layout', ['title' => 'Masuk'])

@section('content')
    <h2>Masuk</h2>
    <p class="form-intro">Lanjutkan belajar, atau kelola kosakata jika kamu bagian dari tim.</p>
    @if ($errors->any())
        <div class="notice" role="alert">{{ $errors->first() }}</div>
    @endif
    <form method="POST" action="{{ route('login.store') }}">
        @csrf
        <label for="email">Email</label>
        <input id="email" type="email" name="email" value="{{ old('email') }}" autocomplete="email" required>

        <label for="password">Kata sandi</label>
        <input id="password" type="password" name="password" autocomplete="current-password" required>
        <button type="submit">Masuk</button>
    </form>
    @if (\App\Models\User::query()->exists())
        <p class="form-switch">Belum punya akun? <a href="{{ route('register') }}" class="form-link">Daftar</a></p>
    @endif
@endsection
