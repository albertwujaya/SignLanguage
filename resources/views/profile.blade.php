<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/logo.svg') }}">
    <link rel="alternate icon" href="{{ asset('favicon.ico') }}">
    <title>Profil {{ $user->name }} | Signesia</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Caveat:wght@600&family=Fredoka:wght@500;600;700&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --ink: #2B2118;
            --paper: #FFF9EA;
            --surface: #FFFAF0;
            --bg: #F6EFDD;
            --mustard: #F2B45A;
            --coral: #E8735A;
            --forest: #2F7A59;
            --muted: #7A6853;
            --line: #2B2118;
            font-family: "Nunito", system-ui, sans-serif;
            color: var(--ink);
            background: var(--bg);
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            background-color: var(--bg);
            background-image: linear-gradient(rgba(43,33,24,.055) 1px, transparent 1px), linear-gradient(90deg, rgba(43,33,24,.055) 1px, transparent 1px);
            background-size: 28px 28px;
            color: var(--ink);
        }
        h1, h2, h3, h4 { font-family: "Fredoka", "Nunito", sans-serif; margin: 0; }

        /* ---- TOP NAVIGATION BAR ---- */
        .bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px clamp(16px, 4vw, 36px);
            border-bottom: 2.5px solid var(--ink);
            background: var(--surface);
            gap: 16px;
        }
        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 9px 20px;
            background: #fff;
            color: var(--ink);
            border: 2px solid var(--ink);
            border-radius: 999px;
            box-shadow: 3px 3px 0 var(--ink);
            font-family: "Fredoka", sans-serif;
            font-weight: 700;
            font-size: 14px;
            text-decoration: none;
            transition: transform .15s ease, box-shadow .15s ease;
        }
        .btn-back:hover {
            transform: translate(-1px, -1px);
            box-shadow: 4px 4px 0 var(--ink);
            background: var(--paper);
        }
        .brand-badge-group {
            display: inline-flex;
            align-items: center;
            gap: 14px;
        }
        .brand-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-family: "Fredoka", sans-serif;
            font-weight: 700;
            font-size: 15px;
            color: var(--ink);
            text-decoration: none;
        }
        .btn-logout {
            padding: 8px 16px;
            background: #fff;
            color: var(--ink);
            border: 2px solid var(--ink);
            border-radius: 8px;
            font-family: "Nunito", sans-serif;
            font-weight: 700;
            font-size: 13px;
            cursor: pointer;
            box-shadow: 2px 2px 0 var(--ink);
            transition: transform .15s ease;
        }
        .btn-logout:hover {
            transform: translate(-1px, -1px);
            background: #fef2f2;
            color: #b91c1c;
        }

        /* ---- MAIN WRAPPER ---- */
        .wrap {
            max-width: 900px;
            margin: 0 auto;
            padding: clamp(24px, 4vw, 40px) 20px 80px;
            display: flex;
            flex-direction: column;
            gap: 28px;
        }

        /* ---- PROFILE HEADER HERO ---- */
        .profile-hero {
            background: var(--surface);
            border: 2.5px solid var(--ink);
            border-radius: 14px;
            padding: 24px 28px;
            box-shadow: 4px 4px 0 var(--ink);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            flex-wrap: wrap;
        }
        .profile-user-left {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        .profile-avatar {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            background: var(--mustard);
            border: 2.5px solid var(--ink);
            box-shadow: 2.5px 2.5px 0 var(--ink);
            display: grid;
            place-items: center;
            font-family: "Fredoka", sans-serif;
            font-size: 26px;
            font-weight: 700;
            color: var(--ink);
            flex-shrink: 0;
        }
        .profile-info h1 {
            font-size: clamp(22px, 3.5vw, 32px);
            color: var(--ink);
            line-height: 1.15;
            margin-bottom: 4px;
        }
        .profile-meta-row {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
            font-size: 13px;
            color: var(--muted);
        }
        .badge-role {
            display: inline-block;
            padding: 2px 10px;
            border-radius: 999px;
            font-family: "Fredoka", sans-serif;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .06em;
            border: 1.5px solid var(--ink);
        }
        .role-admin { background: var(--mustard); color: var(--ink); }
        .role-editor { background: #E8D5C4; color: var(--ink); }
        .role-student { background: #D5E8D4; color: #274E13; }

        .btn-admin-cta {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 18px;
            background: var(--mustard);
            color: var(--ink);
            border: 2px solid var(--ink);
            border-radius: 8px;
            font-family: "Fredoka", sans-serif;
            font-weight: 700;
            font-size: 13px;
            text-decoration: none;
            box-shadow: 3px 3px 0 var(--ink);
            transition: transform .15s ease, box-shadow .15s ease;
        }
        .btn-admin-cta:hover {
            transform: translate(-1px, -1px);
            box-shadow: 4px 4px 0 var(--ink);
        }

        /* ---- CARDS ---- */
        .card {
            background: var(--surface);
            border: 2.5px solid var(--ink);
            border-radius: 14px;
            padding: 24px;
            box-shadow: 4px 4px 0 var(--ink);
        }
        .card-header {
            margin-bottom: 18px;
        }
        .card-header h2 {
            font-size: 22px;
            color: var(--ink);
        }

        /* ---- JURNAL BELAJARKU (SCRAPBOOK PAGES) ---- */
        .journal-container {
            display: flex;
            flex-direction: column;
            gap: 24px;
        }
        .page-j {
            position: relative;
            background: var(--paper);
            border: 2.5px solid var(--ink);
            border-radius: 6px 18px 18px 6px;
            padding: 22px 22px 26px 46px;
            box-shadow: 5px 5px 0 var(--ink);
            background-image: linear-gradient(rgba(43,33,24,.07) 1px, transparent 1px);
            background-size: 100% 30px;
        }
        .page-j::before {
            content: "";
            position: absolute;
            left: 14px;
            top: 18px;
            bottom: 18px;
            width: 12px;
            background: radial-gradient(circle, var(--ink) 4px, transparent 5px) 50% 0 / 12px 36px repeat-y;
        }
        .page-j-header {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 4px;
        }
        .page-j h3 { font-size: 20px; color: var(--ink); }
        .page-j-stat { font-family: "Fredoka", sans-serif; font-size: 13px; font-weight: 700; color: var(--muted); }
        .note { font-family: "Caveat", cursive; font-size: 20px; color: var(--coral); margin: 0 0 12px; }

        .bar-track {
            height: 12px;
            background: #e7dfcd;
            border: 2px solid var(--ink);
            border-radius: 99px;
            overflow: hidden;
            margin-bottom: 18px;
        }
        .bar-fill { height: 100%; background: var(--forest); }

        /* Grid Stiker di dalam Jurnal */
        .stickers-shelf {
            display: flex;
            flex-wrap: wrap;
            gap: 14px;
        }

        /* 1. Stiker yang SUDAH DITEMPEL (Aktif) */
        .sticker-card {
            width: 110px;
            min-height: 108px;
            padding: 8px;
            text-align: center;
            background: #fff;
            border: 2.5px solid var(--ink);
            border-radius: 10px;
            box-shadow: 3px 3px 0 var(--ink);
            transform: rotate(var(--r, 0deg));
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: space-between;
            transition: transform .2s ease;
        }
        .sticker-card:hover {
            transform: scale(1.05) rotate(0deg);
            z-index: 2;
        }
        .sticker-card::before {
            content: "";
            position: absolute;
            top: -9px;
            left: 50%;
            width: 44px;
            height: 15px;
            margin-left: -22px;
            background: rgba(242,180,90,.85);
            border: 1.5px solid rgba(43,33,24,.3);
            transform: rotate(-3deg);
            z-index: 1;
        }
        .sticker-thumb {
            width: 100%;
            height: 52px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            overflow: hidden;
            background: #fafaf5;
        }
        .sticker-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
        .sticker-thumb .em {
            font-size: 32px;
            line-height: 1;
        }
        .sticker-thumb .char-badge {
            font-family: "Fredoka", sans-serif;
            font-size: 24px;
            font-weight: 700;
            color: var(--ink);
            background: var(--mustard);
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: grid;
            place-items: center;
            border: 2px solid var(--ink);
        }
        .sticker-name {
            display: block;
            font-family: "Fredoka", sans-serif;
            font-size: 12px;
            font-weight: 700;
            color: var(--ink);
            margin-top: 4px;
            line-height: 1.2;
            word-break: break-word;
        }
        .sticker-status {
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .05em;
            padding: 1px 6px;
            border-radius: 4px;
            margin-top: 3px;
        }
        .status-collected {
            background: #dcfce7;
            color: #15803d;
        }

        /* 2. Stiker TARGET / Placeholder (Agar tidak kosong melompong bertanda tanya) */
        .sticker-target {
            width: 110px;
            min-height: 108px;
            padding: 8px;
            text-align: center;
            background: rgba(255,255,255,0.55);
            border: 2px dashed #9E8C76;
            border-radius: 10px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: space-between;
            position: relative;
            transform: rotate(var(--r, 0deg));
            transition: background .2s ease;
        }
        .sticker-target:hover {
            background: rgba(255,255,255,0.9);
            border-color: var(--ink);
        }
        .sticker-target .sticker-thumb {
            opacity: 0.65;
            background: rgba(0,0,0,0.02);
        }
        .sticker-target .sticker-name {
            color: var(--muted);
        }
        .status-target {
            background: #f1ede1;
            color: var(--muted);
            border: 1px solid #d9d0bb;
        }

        /* ---- PROGRESS PENILAIAN & NILAI LATIHAN ---- */
        .score-summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 14px;
            margin-bottom: 20px;
        }
        .score-stat-box {
            background: var(--paper);
            border: 2px solid var(--ink);
            border-radius: 10px;
            padding: 14px 18px;
            box-shadow: 2px 2px 0 var(--ink);
        }
        .score-stat-box .stat-label {
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: var(--muted);
            margin-bottom: 4px;
        }
        .score-stat-box .stat-value {
            font-family: "Fredoka", sans-serif;
            font-size: 26px;
            font-weight: 700;
            color: var(--ink);
        }

        .table-wrap {
            overflow-x: auto;
            border: 2px solid var(--ink);
            border-radius: 10px;
            background: #fff;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13.5px;
            text-align: left;
        }
        thead {
            background: #F4EEDF;
            border-bottom: 2px solid var(--ink);
        }
        th {
            padding: 12px 14px;
            font-family: "Fredoka", sans-serif;
            font-size: 13px;
            font-weight: 700;
            color: var(--ink);
            white-space: nowrap;
        }
        td {
            padding: 12px 14px;
            border-bottom: 1px solid #ebd9c2;
            color: var(--ink);
            vertical-align: middle;
        }
        tbody tr:last-child td { border-bottom: none; }
        tbody tr:hover { background: #FFFDF7; }

        .badge-level {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 999px;
            font-family: "Fredoka", sans-serif;
            font-size: 11px;
            font-weight: 600;
            background: #F2E8D5;
            color: var(--ink);
            border: 1.5px solid #8A735E;
        }
        .badge-score-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 3px 10px;
            border-radius: 6px;
            font-weight: 800;
            font-size: 12.5px;
        }
        .score-high { background: #dcfce7; color: #15803d; border: 1px solid #86efac; }
        .score-mid { background: #fef3c7; color: #b45309; border: 1px solid #fde68a; }
        .score-low { background: #fee2e2; color: #b91c1c; border: 1px solid #fca5a5; }

        .tag-sim {
            font-size: 10px;
            color: var(--muted);
            background: #eee6d6;
            padding: 1px 6px;
            border-radius: 4px;
            margin-left: 4px;
        }

        /* ---- FORM EDIT DATA AKUN ---- */
        .account-form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }
        @media (max-width: 640px) {
            .account-form-grid {
                grid-template-columns: 1fr;
            }
            .account-form-grid .field[style*="grid-column"] {
                grid-column: 1 !important;
            }
        }
        .field { margin: 0; }
        label { display: block; margin-bottom: 6px; font-weight: 700; font-size: 13px; }
        input {
            width: 100%;
            padding: 10px 14px;
            border: 2px solid var(--ink);
            border-radius: 8px;
            font: inherit;
            background: #fff;
            color: var(--ink);
        }
        .btn-submit {
            padding: 11px 22px;
            border: 2px solid var(--ink);
            border-radius: 8px;
            background: var(--mustard);
            font-family: "Fredoka", sans-serif;
            font-weight: 700;
            font-size: 14px;
            cursor: pointer;
            box-shadow: 3px 3px 0 var(--ink);
            color: var(--ink);
            transition: transform .15s ease, box-shadow .15s ease;
        }
        .btn-submit:hover {
            transform: translate(-1px, -1px);
            box-shadow: 4px 4px 0 var(--ink);
        }

        .error { color: #b91c1c; font-size: 12px; margin-top: 4px; font-weight: 600; }
        .notice {
            padding: 12px 16px;
            border: 2px solid var(--forest);
            border-radius: 8px;
            background: #dcfce7;
            color: #166534;
            font-weight: 600;
            font-size: 14px;
        }
    </style>
</head>
<body>
    {{-- BAR ATAS DENGAN TOMBOL BACK PROPORSIONAL --}}
    <nav class="bar" aria-label="Navigasi atas">
        <a href="{{ route('home') }}" class="btn-back">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
            <span>Kembali ke Beranda</span>
        </a>
        <div class="brand-badge-group">
            <a href="{{ route('home') }}" class="brand-pill" title="Signesia">
                <img src="{{ asset('images/logo.svg') }}" alt="" width="22" height="26">
                <span>Signesia</span>
            </a>
            <form method="POST" action="{{ route('logout') }}" style="margin:0">
                @csrf
                <button type="submit" class="btn-logout">Keluar</button>
            </form>
        </div>
    </nav>

    <main class="wrap">
        {{-- HEADER PROFIL --}}
        <header class="profile-hero">
            <div class="profile-user-left">
                <div class="profile-avatar" aria-hidden="true">
                    {{ strtoupper(substr($user->name, 0, 1)) }}
                </div>
                <div class="profile-info">
                    <h1>Halo, {{ $user->name }}</h1>
                    <div class="profile-meta-row">
                        <span>{{ $user->email }}</span>
                        <span>·</span>
                        <span class="badge-role role-{{ $user->role }}">{{ strtoupper($user->role) }}</span>
                    </div>
                </div>
            </div>
            @if ($user->isStaff())
                <a class="btn-admin-cta" href="{{ route('admin.dashboard') }}">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="7" height="7" rx="1.5"></rect><rect x="14" y="3" width="7" height="7" rx="1.5"></rect><rect x="14" y="14" width="7" height="7" rx="1.5"></rect><rect x="3" y="14" width="7" height="7" rx="1.5"></rect></svg>
                    Buka Panel Admin
                </a>
            @endif
        </header>

        @if (session('status'))
            <p class="notice" role="status">{{ session('status') }}</p>
        @endif

        {{-- JURNAL BELAJARKU DENGAN STIKER ISI --}}
        <section aria-labelledby="progres">
            <div class="card-header">
                <h2 id="progres">Jurnal belajarku</h2>
            </div>
            <div class="journal-container">
                @foreach ($levels as $key => $level)
                    @php
                        $pct = $level['total'] > 0 ? round($level['learned'] / $level['total'] * 100) : 0;
                        $items = $stickers[$key] ?? collect();
                        $targetPlaceholders = match($key) {
                            'abjad' => [
                                ['title' => 'Huruf A', 'symbol' => 'A'],
                                ['title' => 'Huruf B', 'symbol' => 'B'],
                                ['title' => 'Huruf C', 'symbol' => 'C'],
                                ['title' => 'Huruf D', 'symbol' => 'D'],
                                ['title' => 'Huruf E', 'symbol' => 'E'],
                            ],
                            'angka' => [
                                ['title' => 'Angka 1', 'symbol' => '1'],
                                ['title' => 'Angka 2', 'symbol' => '2'],
                                ['title' => 'Angka 3', 'symbol' => '3'],
                                ['title' => 'Angka 4', 'symbol' => '4'],
                                ['title' => 'Angka 5', 'symbol' => '5'],
                            ],
                            'kata' => [
                                ['title' => 'Minggu', 'symbol' => '📅'],
                                ['title' => 'Makan', 'symbol' => '🍽️'],
                                ['title' => 'Tidur', 'symbol' => '😴'],
                                ['title' => 'Bagus', 'symbol' => '✨'],
                                ['title' => 'Air', 'symbol' => '💧'],
                            ],
                            default => [
                                ['title' => 'Terima Kasih', 'symbol' => '🙏'],
                                ['title' => 'Apa Kabar', 'symbol' => '💬'],
                                ['title' => 'Sama-sama', 'symbol' => '✨'],
                                ['title' => 'Selamat Tinggal', 'symbol' => '🖐️'],
                            ]
                        };
                        $emptySlotsCount = max(count($targetPlaceholders) - $items->count(), 0);
                    @endphp

                    <article class="page-j">
                        <div class="page-j-header">
                            <h3>{{ $level['label'] }}</h3>
                            <span class="page-j-stat">{{ $level['learned'] }} / {{ max($level['total'], $items->count() + $emptySlotsCount) }} Dikuasai</span>
                        </div>
                        <p class="note">
                            @if ($level['total'] === 0) Materi segera hadir di jenjang ini.
                            @elseif ($level['learned'] === 0) Belum ada yang ditempel. Mulai latihan untuk kumpulkan stikermu!
                            @elseif ($level['learned'] >= $level['total']) Halaman penuh dan semua stiker tertempel. Hebat!
                            @else Sudah {{ $level['learned'] }} stiker tertempel, kumpulkan {{ $level['total'] - $level['learned'] }} lagi!
                            @endif
                        </p>
                        
                        <div class="bar-track" role="progressbar" aria-label="Progres {{ $level['label'] }}" aria-valuenow="{{ $pct }}" aria-valuemin="0" aria-valuemax="100">
                            <div class="bar-fill" style="width: {{ $pct }}%"></div>
                        </div>

                        <div class="stickers-shelf">
                            {{-- Stiker yang SUDAH DIKOLEKSI --}}
                            @foreach ($items as $i => $g)
                                <div class="sticker-card" style="--r: {{ [-3, 2, -1.5, 3, -2.5][$i % 5] }}deg" title="{{ $g->word }} — Sudah Ditempel">
                                    <div class="sticker-thumb">
                                        @if ($g->image_path)
                                            <img src="{{ $g->imageUrl() }}" alt="{{ $g->word }}">
                                        @elseif ($g->emoji)
                                            <span class="em" aria-hidden="true">{{ $g->emoji }}</span>
                                        @else
                                            <div class="char-badge">{{ strtoupper(substr($g->word, 0, 1)) }}</div>
                                        @endif
                                    </div>
                                    <span class="sticker-name">{{ $g->word }}</span>
                                    <span class="sticker-status status-collected">Ditempel</span>
                                </div>
                            @endforeach

                            {{-- Stiker TARGET/CONTOH (Sticker huruf 'A', kata 'Minggu', dsb. agar card terisi dan tidak kosong bertanda tanya) --}}
                            @for ($j = 0; $j < $emptySlotsCount; $j++)
                                @php
                                    $sample = $targetPlaceholders[$j % count($targetPlaceholders)];
                                    $rot = [1.5, -2, 2.5, -1.5, 2][$j % 5];
                                @endphp
                                <div class="sticker-target" style="--r: {{ $rot }}deg" title="{{ $sample['title'] }} — Belum Ditempel">
                                    <div class="sticker-thumb">
                                        @if (mb_strlen($sample['symbol']) === 1 && !preg_match('/\p{So}|\p{Cn}/u', $sample['symbol']))
                                            <div class="char-badge" style="background:#e9dfcc;border-color:#a18d78;color:#7a6853">{{ $sample['symbol'] }}</div>
                                        @else
                                            <span class="em" aria-hidden="true" style="filter:grayscale(100%) opacity(0.55)">{{ $sample['symbol'] }}</span>
                                        @endif
                                    </div>
                                    <span class="sticker-name">{{ $sample['title'] }}</span>
                                    <span class="sticker-status status-target">Target</span>
                                </div>
                            @endfor
                        </div>
                    </article>
                @endforeach
            </div>
        </section>

        {{-- NILAI LATIHAN & PROGRESS PENILAIAN RAPI --}}
        <section class="card" aria-labelledby="nilai">
            <div class="card-header">
                <h2 id="nilai">Nilai &amp; Riwayat Latihan</h2>
            </div>

            @if ($sessionCount > 0)
                <div class="score-summary-grid">
                    <div class="score-stat-box">
                        <div class="stat-label">Rata-rata Skor</div>
                        <div class="stat-value" style="color: {{ $averageScore >= 80 ? 'var(--forest)' : ($averageScore >= 60 ? '#b45309' : 'var(--coral)') }}">
                            {{ round($averageScore) }}%
                        </div>
                    </div>
                    <div class="score-stat-box">
                        <div class="stat-label">Total Sesi Selesai</div>
                        <div class="stat-value">{{ $sessionCount }} <span style="font-size:16px;font-weight:600;color:var(--muted)">sesi</span></div>
                    </div>
                </div>

                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Jenjang</th>
                                <th>Ketepatan</th>
                                <th>Nilai</th>
                                <th>Durasi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($sessions as $session)
                                @php
                                    $scoreNum = (int) $session->score;
                                    $scoreClass = $scoreNum >= 80 ? 'score-high' : ($scoreNum >= 60 ? 'score-mid' : 'score-low');
                                @endphp
                                <tr>
                                    <td>
                                        <strong>{{ $session->created_at->format('d M Y') }}</strong>
                                        <small style="display:block;color:var(--muted)">{{ $session->created_at->format('H:i') }} WIB</small>
                                    </td>
                                    <td>
                                        <span class="badge-level">
                                            {{ \App\Models\Gesture::LEVELS[$session->level] ?? 'Campuran' }}
                                        </span>
                                    </td>
                                    <td>
                                        <strong>{{ $session->correct }}</strong> <span style="color:var(--muted)">/ {{ $session->total }}</span>
                                    </td>
                                    <td>
                                        <span class="badge-score-pill {{ $scoreClass }}">
                                            {{ $session->score }}%
                                        </span>
                                        @unless ($session->is_verified)
                                            <span class="tag-sim" title="Latihan dalam mode simulasi">Simulasi</span>
                                        @endunless
                                    </td>
                                    <td>
                                        {{ intdiv($session->duration_seconds, 60) }}m {{ $session->duration_seconds % 60 }}s
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div style="text-align:center;padding:36px 20px;background:var(--paper);border:2px dashed var(--muted);border-radius:10px">
                    <p style="font-size:16px;margin:0 0 14px;color:var(--muted)">Belum ada riwayat sesi latihan.</p>
                    <a class="btn-submit" href="{{ url('/#/practice') }}" style="text-decoration:none;display:inline-block">Mulai Latihan Sekarang</a>
                </div>
            @endif
        </section>

        {{-- FORM PENGATURAN DATA AKUN --}}
        <section class="card" aria-labelledby="akun">
            <div class="card-header">
                <h2 id="akun">Pengaturan Data Akun</h2>
            </div>
            <form method="POST" action="{{ route('profile.update') }}">
                @csrf
                @method('PATCH')

                <div class="account-form-grid">
                    <div class="field">
                        <label for="name">Nama Lengkap</label>
                        <input id="name" name="name" value="{{ old('name', $user->name) }}" required>
                        @error('name')<div class="error">{{ $message }}</div>@enderror
                    </div>

                    <div class="field">
                        <label for="email">Alamat Email</label>
                        <input id="email" type="email" name="email" value="{{ old('email', $user->email) }}" required>
                        @error('email')<div class="error">{{ $message }}</div>@enderror
                    </div>

                    <div class="field" style="grid-column: 1 / -1">
                        <label for="current_password">Kata Sandi Saat Ini</label>
                        <input id="current_password" type="password" name="current_password" autocomplete="current-password" placeholder="Isi kata sandi saat ini jika ingin mengganti sandi">
                        @error('current_password')<div class="error">{{ $message }}</div>@enderror
                    </div>

                    <div class="field">
                        <label for="password">Kata Sandi Baru</label>
                        <input id="password" type="password" name="password" autocomplete="new-password" placeholder="Minimal 8 karakter">
                        @error('password')<div class="error">{{ $message }}</div>@enderror
                    </div>

                    <div class="field">
                        <label for="password_confirmation">Ulangi Kata Sandi Baru</label>
                        <input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" placeholder="Ketik ulang kata sandi baru">
                    </div>
                </div>

                <div style="margin-top:20px">
                    <button class="btn-submit" type="submit">Simpan Perubahan Akun</button>
                </div>
            </form>
        </section>
    </main>
</body>
</html>
