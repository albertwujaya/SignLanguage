@if (session('status'))
    <p class="notice notice-success" role="status">{{ session('status') }}</p>
@endif

@if (session('error'))
    <p class="notice notice-error" role="alert">{{ session('error') }}</p>
@endif

@if ($errors->any())
    <div class="notice notice-error" role="alert">
        @if ($errors->count() > 1)
            <strong>Periksa kembali kesalahan berikut:</strong>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        @else
            {{ $errors->first() }}
        @endif
    </div>
@endif
