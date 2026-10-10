<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Profil | Sign Language</title>
    <link href="https://fonts.googleapis.com/css2?family=Caveat:wght@600&family=Fredoka:wght@500;600;700&family=Nunito:wght@400;700;800&display=swap" rel="stylesheet">
    <style>
        /* Sementara: gaya sederhana. Akan diganti saat redesign tahap 2. */
        :root { font-family: "Nunito", system-ui, sans-serif; color: #2B2118; background: #F6EFDD; }
        * { box-sizing: border-box; }
        body { margin: 0; background-image: linear-gradient(rgba(43,33,24,.055) 1px, transparent 1px), linear-gradient(90deg, rgba(43,33,24,.055) 1px, transparent 1px); background-size: 28px 28px; }
        h1, h2, h3 { font-family: "Fredoka", "Nunito", sans-serif; }
        .bar { display: flex; justify-content: space-between; align-items: center; padding: 14px 24px; border-bottom: 2px solid #1f2a28; background: #fffaf0; }
        .bar a { color: inherit; font-weight: 700; text-decoration: none; }
        .wrap { max-width: 860px; margin: 0 auto; padding: 28px 20px 60px; display: grid; gap: 22px; }
        .card { background: #fffaf0; border: 2px solid #1f2a28; border-radius: 12px; padding: 20px; box-shadow: 4px 4px 0 #1f2a28; }
        h1, h2 { margin: 0 0 12px; }
        .grid { display: grid; gap: 14px; grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); }
        .bar-track { height: 12px; background: #e7dfcd; border: 2px solid #1f2a28; border-radius: 99px; overflow: hidden; }
        .bar-fill { height: 100%; background: #7fa87a; }
        label { display: block; margin: 12px 0 5px; font-weight: 600; font-size: 14px; }
        input { width: 100%; padding: 10px 12px; border: 2px solid #1f2a28; border-radius: 8px; font: inherit; background: #fff; }
        button, .btn { padding: 10px 16px; border: 2px solid #1f2a28; border-radius: 8px; background: #f2b45a; font: inherit; font-weight: 700; cursor: pointer; box-shadow: 3px 3px 0 #1f2a28; color: inherit; text-decoration: none; display: inline-block; }
        .notice { padding: 10px 14px; border: 2px solid #1f2a28; border-radius: 8px; background: #d9ead3; }
        .error { color: #a1281c; font-size: 13px; margin-top: 4px; }
        table { width: 100%; border-collapse: collapse; font-size: 14px; }
        th, td { text-align: left; padding: 8px 6px; border-bottom: 1px solid #d9d0bb; }
        small { color: #5d4d3c; }
        :focus-visible { outline: 3px solid #2B2118; outline-offset: 3px; }

        /* Jurnal progres: satu halaman per jenjang, stiker = gesture yang sudah dipelajari */
        .journal { display: grid; gap: 22px; }
        .page-j { position: relative; background: #FFF9EA; border: 2.5px solid #2B2118; border-radius: 6px 18px 18px 6px; padding: 22px 22px 22px 46px; box-shadow: 5px 5px 0 #2B2118;
            background-image: linear-gradient(rgba(43,33,24,.07) 1px, transparent 1px); background-size: 100% 30px; }
        .page-j::before { content: ""; position: absolute; left: 14px; top: 18px; bottom: 18px; width: 12px;
            background: radial-gradient(circle, #2B2118 4px, transparent 5px) 50% 0 / 12px 36px repeat-y; }
        .page-j h3 { margin: 0 0 4px; }
        .note { font-family: "Caveat", cursive; font-size: 22px; color: #C0432B; margin: 0 0 12px; }
        .stickers { display: flex; flex-wrap: wrap; gap: 14px; }
        .sticker { width: 108px; min-height: 92px; padding: 8px; text-align: center; background: #fff; border: 2.5px solid #2B2118; border-radius: 10px;
            box-shadow: 3px 3px 0 #2B2118; transform: rotate(var(--r, 0deg)); position: relative; animation: stick .5s cubic-bezier(.2,1.6,.4,1) both; animation-delay: var(--d, 0ms); }
        .sticker::before { content: ""; position: absolute; top: -9px; left: 50%; width: 46px; height: 16px; margin-left: -23px; background: rgba(242,180,90,.85); border: 1.5px solid rgba(43,33,24,.3); transform: rotate(-4deg); }
        .sticker img { width: 100%; height: 56px; object-fit: cover; border-radius: 6px; display: block; }
        .sticker .em { font-size: 34px; line-height: 56px; display: block; }
        .sticker b { display: block; font-family: "Fredoka", sans-serif; font-size: 13px; margin-top: 4px; }
        .slot { width: 108px; min-height: 92px; border: 2.5px dashed #7A6853; border-radius: 10px; display: grid; place-items: center; color: #7A6853; font-size: 12px; font-weight: 700; }
        @keyframes stick { from { transform: scale(1.6) rotate(-14deg); opacity: 0; } to { opacity: 1; } }
        @media (prefers-reduced-motion: reduce) { .sticker { animation: none; } }
    </style>
</head>
<body>
    <div class="bar">
        <a href="{{ route('home') }}">← SIGN LANGUAGE</a>
        <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit">Keluar</button></form>
    </div>

    <main class="wrap">
        <header>
            <h1>Halo, {{ $user->name }}</h1>
            <small>{{ $user->email }} · {{ strtoupper($user->role) }}</small>
            @if ($user->isStaff())
                <p><a class="btn" href="{{ route('admin.dashboard') }}">Buka panel admin</a></p>
            @endif
        </header>

        @if (session('status'))
            <p class="notice" role="status">{{ session('status') }}</p>
        @endif

        <section aria-labelledby="progres">
            <h2 id="progres">Jurnal belajarku</h2>
            <div class="journal">
                @foreach ($levels as $key => $level)
                    @php($pct = $level['total'] > 0 ? round($level['learned'] / $level['total'] * 100) : 0)
                    @php($items = $stickers[$key] ?? collect())
                    @php($emptySlots = min(max($level['total'] - $level['learned'], 0), 6))
                    <article class="page-j">
                        <h3>{{ $level['label'] }}</h3>
                        <p class="note">
                            @if ($level['total'] === 0) Halaman ini masih kosong, materi segera hadir.
                            @elseif ($level['learned'] === 0) Belum ada yang ditempel. Mulai dari satu gerakan dulu!
                            @elseif ($level['learned'] >= $level['total']) Halaman penuh. Hebat!
                            @else Sudah {{ $level['learned'] }} gerakan tertempel, tinggal {{ $level['total'] - $level['learned'] }} lagi.
                            @endif
                        </p>
                        @if ($level['total'] > 0)
                            <div class="bar-track" role="progressbar" aria-label="Progres {{ $level['label'] }}" aria-valuenow="{{ $pct }}" aria-valuemin="0" aria-valuemax="100" style="margin-bottom:16px"><div class="bar-fill" style="width: {{ $pct }}%"></div></div>
                        @endif
                        <div class="stickers">
                            @foreach ($items as $i => $g)
                                <div class="sticker" style="--r: {{ [-3, 2, -1.5, 3, -2.5][$i % 5] }}deg; --d: {{ min($i, 12) * 70 }}ms">
                                    @if ($g->image_path)
                                        <img src="{{ $g->imageUrl() }}" alt="">
                                    @else
                                        <span class="em" aria-hidden="true">{{ $g->emoji ?: '✋' }}</span>
                                    @endif
                                    <b>{{ $g->word }}</b>
                                </div>
                            @endforeach
                            @for ($i = 0; $i < $emptySlots; $i++)
                                <div class="slot" aria-hidden="true">?</div>
                            @endfor
                        </div>
                    </article>
                @endforeach
            </div>
        </section>

        <section class="card" aria-labelledby="nilai">
            <h2 id="nilai">Nilai latihan</h2>
            @if ($sessionCount > 0)
                <p>Rata-rata <strong>{{ round($averageScore) }}%</strong> dari {{ $sessionCount }} sesi.</p>
                <table>
                    <thead><tr><th>Tanggal</th><th>Jenjang</th><th>Benar</th><th>Nilai</th><th>Durasi</th></tr></thead>
                    <tbody>
                        @foreach ($sessions as $session)
                            <tr>
                                <td>{{ $session->created_at->format('d M Y H:i') }}</td>
                                <td>{{ \App\Models\Gesture::LEVELS[$session->level] ?? 'Campuran' }}</td>
                                <td>{{ $session->correct }}/{{ $session->total }}</td>
                                <td>{{ $session->score }}%@unless ($session->is_verified) <small>(mode simulasi)</small>@endunless</td>
                                <td>{{ intdiv($session->duration_seconds, 60) }}m {{ $session->duration_seconds % 60 }}s</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p>Belum ada sesi latihan. Coba menu Practice dulu.</p>
            @endif
        </section>

        <section class="card" aria-labelledby="akun">
            <h2 id="akun">Data akun</h2>
            <form method="POST" action="{{ route('profile.update') }}">
                @csrf
                @method('PATCH')
                <label for="name">Nama</label>
                <input id="name" name="name" value="{{ old('name', $user->name) }}" required>
                @error('name')<div class="error">{{ $message }}</div>@enderror

                <label for="email">Email</label>
                <input id="email" type="email" name="email" value="{{ old('email', $user->email) }}" required>
                @error('email')<div class="error">{{ $message }}</div>@enderror

                <label for="current_password">Kata sandi sekarang (isi hanya jika mau ganti sandi)</label>
                <input id="current_password" type="password" name="current_password" autocomplete="current-password">
                @error('current_password')<div class="error">{{ $message }}</div>@enderror

                <label for="password">Kata sandi baru</label>
                <input id="password" type="password" name="password" autocomplete="new-password">
                @error('password')<div class="error">{{ $message }}</div>@enderror

                <label for="password_confirmation">Ulangi kata sandi baru</label>
                <input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password">

                <p><button type="submit">Simpan perubahan</button></p>
            </form>
        </section>
    </main>
</body>
</html>
