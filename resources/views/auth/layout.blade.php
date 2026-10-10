<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#F6EFDD">
    <title>{{ $title }} | SigNesia</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@400;500;600;700&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* Tema sama dengan halaman utama: "warm playful scrapbook" (krem, tinta cokelat, neo-brutalism terang) */
        :root {
            --ink: #2B2118; --paper: #FFF9EA; --sage: #7FA87A; --mustard: #F2B45A; --coral: #E8735A;
            --bg: #F6EFDD; --cy: #C0432B; --mint: #3F7040; --t2: #5A4A3A;
            --font-heading: 'Fredoka', 'Nunito', system-ui, sans-serif;
            --font-body: 'Nunito', system-ui, sans-serif;
            --ease: cubic-bezier(.22, 1, .36, 1);
            --gutter: clamp(20px, 4.5vw, 72px); /* sama dengan margin samping halaman utama */
            --card-w: 440px;
            color-scheme: light;
        }
        * { box-sizing: border-box; }
        html { -webkit-text-size-adjust: 100%; }
        body {
            min-height: 100vh; min-height: 100svh; margin: 0;
            display: grid; grid-template-columns: minmax(0, 1fr) calc(var(--card-w) + 2 * var(--gutter));
            font-family: var(--font-body); font-size: 16px; color: var(--ink);
            background-color: var(--bg);
            background-image:
                linear-gradient(rgba(43,33,24,.055) 1px, transparent 1px),
                linear-gradient(90deg, rgba(43,33,24,.055) 1px, transparent 1px);
            background-size: 28px 28px;
        }
        ::selection { background: var(--mustard); color: var(--ink); }

        /* ---- Sisi kiri: langit, awan & bukit (sama seperti hero beranda) ---- */
        .story {
            position: relative; isolation: isolate; overflow: hidden;
            display: flex; flex-direction: column; justify-content: space-between;
            padding: 36px var(--gutter) 40px;
            background: linear-gradient(180deg, #BFE3EE 0%, #E8F2EC 48%, #F6EFDD 70%);
            border-right: 3px solid var(--ink);
        }
        .story::before {
            content: ""; position: absolute; inset: 0 -20% 0 -20%; z-index: -1; pointer-events: none; opacity: .95;
            background:
                radial-gradient(ellipse 120px 36px at 18% 22%, #fff 60%, transparent 62%),
                radial-gradient(ellipse 90px 28px at 24% 20%, #fff 60%, transparent 62%),
                radial-gradient(ellipse 150px 42px at 68% 30%, #fff 60%, transparent 62%),
                radial-gradient(ellipse 100px 30px at 74% 27%, #fff 60%, transparent 62%),
                radial-gradient(ellipse 110px 32px at 45% 14%, #fff 60%, transparent 62%);
            animation: drift 60s linear infinite alternate;
        }
        .story::after {
            content: ""; position: absolute; left: 0; right: 0; bottom: 0; height: 34%; z-index: -1; pointer-events: none;
            background:
                radial-gradient(ellipse 60% 100% at 20% 100%, #9CC48F 0 62%, transparent 63%),
                radial-gradient(ellipse 70% 90% at 78% 100%, #7FA87A 0 62%, transparent 63%),
                radial-gradient(ellipse 90% 60% at 50% 100%, #B9D5A8 0 62%, transparent 63%);
        }
        @keyframes drift { from { transform: translateX(-4%); } to { transform: translateX(4%); } }

        .brand {
            align-self: flex-start; display: inline-flex; align-items: center; gap: 10px;
            padding: 9px 18px; background: var(--paper); color: var(--ink);
            border: 2.5px solid var(--ink); border-radius: 999px; box-shadow: 3px 3px 0 var(--ink);
            font-size: 12px; font-weight: 800; letter-spacing: .14em; text-decoration: none;
            transition: transform .25s var(--ease), box-shadow .25s var(--ease), background-color .25s ease, color .25s ease;
        }
        .brand-symbol { color: var(--cy); transition: color .25s ease; }
        .brand:hover { background: var(--mint); color: var(--paper); transform: translate(-2px, -2px); box-shadow: 5px 5px 0 var(--ink); }
        .brand:hover .brand-symbol { color: var(--paper); }
        .brand:active { transform: translate(2px, 2px); box-shadow: 1px 1px 0 var(--ink); }

        .story-copy { max-width: 540px; }
        .eyebrow {
            display: flex; align-items: center; gap: 12px;
            color: var(--cy); font-size: 11px; font-weight: 800; letter-spacing: .18em; text-transform: uppercase;
        }
        .eyebrow::before { content: ""; width: 32px; height: 2px; background: var(--cy); }
        h1 {
            margin: 16px 0 18px; font-family: var(--font-heading); font-weight: 600;
            font-size: clamp(36px, 4.6vw, 64px); line-height: 1.02; letter-spacing: 0;
            color: var(--ink); text-shadow: 3px 3px 0 var(--mustard);
        }
        .story-copy p { max-width: 440px; margin: 0; color: var(--t2); font-size: 17px; font-weight: 600; line-height: 1.65; }
        .story-foot { color: var(--ink); font-size: 12px; font-weight: 800; letter-spacing: .14em; }

        /* ---- Sisi kanan: kartu formulir ---- */
        .form-side { display: grid; place-items: center; padding: 40px var(--gutter); }
        .form-wrap {
            position: relative; width: min(100%, var(--card-w));
            padding: clamp(26px, 4vw, 38px);
            background: var(--paper); border: 2.5px solid var(--ink); border-radius: 22px;
            box-shadow: 8px 8px 0 var(--ink);
        }
        .form-wrap::before { /* selotip kecil seperti kartu kamera di halaman Practice */
            content: ""; position: absolute; top: -15px; left: 50%; width: 112px; height: 26px; margin-left: -56px;
            background: rgba(242, 180, 90, .9); border: 2px solid var(--ink); border-radius: 4px; transform: rotate(-2deg);
        }
        .form-wrap h2 { margin: 6px 0 8px; font-family: var(--font-heading); font-size: clamp(28px, 4vw, 34px); font-weight: 600; line-height: 1.1; }
        .form-intro { margin: 0 0 26px; color: var(--t2); font-weight: 600; line-height: 1.55; }
        label { display: block; margin: 18px 0 8px; color: var(--ink); font-size: 12px; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; }
        input {
            width: 100%; min-height: 52px; padding: 12px 16px;
            background: #fff; color: var(--ink); font: inherit; font-weight: 600;
            border: 2.5px solid var(--ink); border-radius: 14px; box-shadow: 0 0 0 0 var(--ink);
            transition: transform .25s var(--ease), box-shadow .25s var(--ease), background-color .25s ease;
        }
        input:focus { outline: 2px solid transparent; background: #FFF3CF; transform: translate(-1px, -1px); box-shadow: 4px 4px 0 var(--ink); }
        button {
            width: 100%; min-height: 54px; margin-top: 28px; padding: 0 20px;
            background: var(--mustard); color: var(--ink); font: inherit; font-size: 14px; font-weight: 800; letter-spacing: .14em; text-transform: uppercase;
            border: 2.5px solid var(--ink); border-radius: 999px; box-shadow: 3px 3px 0 var(--ink); cursor: pointer;
            transition: transform .25s var(--ease), box-shadow .25s var(--ease), background-color .25s ease, color .25s ease;
        }
        button:hover { background: var(--mint); color: var(--paper); transform: translate(-2px, -2px); box-shadow: 5px 5px 0 var(--ink); }
        button:active { transform: translate(2px, 2px); box-shadow: 1px 1px 0 var(--ink); }
        button:focus-visible, .brand:focus-visible, .form-link:focus-visible { outline: 3px solid var(--ink); outline-offset: 3px; }
        .form-switch { margin: 24px 0 0; padding-top: 20px; border-top: 2px dashed rgba(43, 33, 24, .25); color: var(--t2); font-weight: 600; }
        .form-link { color: var(--cy); font-weight: 800; text-decoration: underline; text-underline-offset: 3px; transition: color .2s ease; }
        .form-link:hover { color: var(--mint); }
        .error { margin: 6px 0 0; color: var(--cy); font-size: 13px; font-weight: 700; }
        .notice {
            margin: 0 0 18px; padding: 12px 16px; background: #FFE3DB; color: #8F2D18;
            border: 2.5px solid var(--ink); border-radius: 14px; box-shadow: 3px 3px 0 var(--ink); font-size: 14px; font-weight: 700;
        }

        /* Tablet & HP: susunan atas-bawah, margin samping tetap mengikuti --gutter */
        @media (max-width: 960px) {
            body { grid-template-columns: 1fr; }
            .story { min-height: 300px; padding: calc(22px + env(safe-area-inset-top, 0px)) var(--gutter) 32px; border-right: 0; border-bottom: 3px solid var(--ink); }
            .story::after { height: 42%; }
            .story-copy { margin: 40px 0 28px; }
            .form-side { align-items: start; padding: 34px var(--gutter) calc(44px + env(safe-area-inset-bottom, 0px)); }
        }
        @media (max-width: 640px) {
            :root { --gutter: 16px; }
            .story { min-height: 280px; padding-bottom: 28px; }
            .story-copy { margin: 36px 0 24px; }
            h1 { font-size: clamp(32px, 9vw, 42px); }
            .story-copy p, .story-foot { display: none; }
            .form-wrap { box-shadow: 5px 5px 0 var(--ink); }
        }
        @media (prefers-reduced-motion: reduce) {
            .story::before { animation: none; }
            .brand, input, button { transition: none; }
        }
    </style>
</head>
<body>
    <section class="story">
        <a class="brand" href="{{ route('home') }}"><span class="brand-symbol">◇</span>SIGN LANGUAGE</a>
        <div class="story-copy">
            <span class="eyebrow">Belajar bisindo</span>
            <h1>Belajar bahasa isyarat, selangkah demi selangkah.</h1>
            <p>Masuk untuk menyimpan progres, nilai latihan, dan melanjutkan belajar dari perangkat mana pun.</p>
        </div>
        <span class="story-foot">BISINDO · SIBI · ASL</span>
    </section>
    <main class="form-side">
        <div class="form-wrap">@yield('content')</div>
    </main>
</body>
</html>
