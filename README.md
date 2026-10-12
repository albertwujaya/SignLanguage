# SIGN LANGUAGE — 2026 DIGITAL PLATFORM

A modern, futuristic, cinematic sign language learning, practice, translation, and dictionary platform. Built with an editorial dark teal atmosphere, oversized geometric typography, translucent glass UI, and real-time computer vision gesture intelligence.

---

## 1. Core Philosophy & Design Identity

- **Atmosphere**: Deep dark teal & obsidian (`#071A1D`, `#0B292C`, `#0E3B3D`, `#123F42`)
- **Accents**: Electric cyan (`#18D8D0`) and soft mint (`#A8F3E5`)
- **Typography**: Space Grotesk (headings & oversized editorial text), Sora, and Inter
- **Aesthetic**: 2026 creative technology platform, cinematic photography, high contrast, subtle grain, translucent glassmorphism (`backdrop-filter: blur(18px)`), and 12-column asymmetric composition
- **Accessibility**: High contrast ratios, keyboard navigation (`⌘K` / `Ctrl+K`, `Esc`), visible focus rings, ARIA roles, and `prefers-reduced-motion` compliance

---

## 2. Platform Architecture

```
SIGN LANGUAGE
│
├── HOME
│   ├── Cinematic Hero (Video/Photo Background, Asymmetric Layout)
│   ├── Live Status (REAL-TIME RECOGNITION ● READY)
│   └── 4 Major Editorial Feature Panels (01 Learn, 02 Practice, 03 Translate, 04 Dictionary)
│
├── LEARN
│   ├── Curriculum Overview (Basic Greetings, Daily Conversation, Needs & Requests, Emergency)
│   ├── Horizontal Category Cards with Interactive Progress Bars
│   └── Lesson Detail
│       ├── Interactive 3D Flip Learning Card (Front: Gesture vector, Back: How to Sign)
│       ├── Hand Position, Movement Direction, Common Mistakes
│       ├── Prev / Next / Mark as Learned (Persistent in LocalStorage)
│       └── "Practice This Gesture" Quick Action
│
├── PRACTICE
│   ├── Dominant Camera Viewport (Real Webcam or AI Vision Simulator)
│   ├── Thin Cyan Gesture Tracking Bounding Box & Landmark Overlay
│   ├── Live Target Gesture Card (e.g. SIGN: "THANK YOU", BISINDO: TERIMA KASIH)
│   ├── 10-Step Session Progress Dots (● ● ● ○ ○ ○ ○ ○ ○ ○)
│   ├── Instant Test Match Action & Pose Confidence Display
│   └── Session Summary Screen (Accuracy %, Gestures Practiced, Elapsed Time)
│
├── TRANSLATE (3 Modalities)
│   ├── GESTURE → TEXT: Continuous camera recognition appending gestures into sentences
│   ├── SENTENCE BUILDER: Interactive movable gesture blocks [ HELLO ] [ TODAY ] (drag & drop, reorder, remove, dictionary insert)
│   └── TEXT → VOICE: Minimalist text area, bilingual synthesis (Indonesian & English), cadence/pitch selectors, and animated soundwave equalizer
│
└── DICTIONARY
    ├── Global & Local Search Bar (Words, Indonesian terms, Descriptions)
    ├── Filter Chips (All, Greeting, Conversation, Needs, Emergency, Numbers, Alphabet)
    ├── Gesture Cards with Biomechanical Vector Diagrams & Favorite Bookmarking
    └── Gesture Detail Modal Drawer with Step-by-Step Biomechanics & Direct Practice Launcher
```

---

## 3. Technology Stack

- **Backend**: Laravel 13 (PHP 8.2+ via XAMPP)
- **Database**: SQLite with complete migration & seeder (32 gestures with BISINDO)
- **Frontend**: Vanilla JavaScript (ES6+ modular SPA with hash routing), modern CSS variables, and Web Audio API
- **Computer Vision**: HTML5 MediaDevices Webcam integration + Canvas-rendered 21-point hand landmark skeleton
- **Speech Synthesis**: Web Speech API (`SpeechSynthesisUtterance`) with support for Indonesian (`id-ID`) and English (`en-US`)

---

## 4. How to Run

### Step 1: Ensure PHP is in your PATH (if using XAMPP on Windows)
In PowerShell:
```powershell
$env:PATH = "C:\xampp\php;" + $env:PATH
```

### Step 2: Navigate to the Laravel project directory
```powershell
cd SignLanguage
```

### Step 3: Start the server
```powershell
php artisan serve --port=8000
```

Open your browser at:
```
http://127.0.0.1:8000
```

---

## 5. API Endpoints

| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/api/gestures` | Returns all gestures (supports `?q=` search and `?category=`) |
| `GET` | `/api/gestures/{slug}` | Returns single gesture details |
| `GET` | `/api/stats` | Returns total vocabulary counts, category breakdown, and AI status |

---

## 6. Gesture Vocabulary Included (32 Signs)

1. **Basic Greetings**: HELLO (Halo), THANK YOU (Terima Kasih), GOODBYE (Selamat Tinggal), PLEASE (Tolong/Silakan), NICE TO MEET YOU (Senang Bertemu Denganmu), WELCOME (Sama-sama)
2. **Daily Conversation**: HOW ARE YOU (Apa Kabar), TODAY (Hari Ini), YES (Ya), NO (Tidak), UNDERSTAND (Mengerti), NAME (Nama), GOOD (Bagus/Baik)
3. **Needs & Requests**: WATER (Air), EAT (Makan), BATHROOM (Toilet), WANT (Mau/Ingin), SLEEP (Tidur)
4. **Emergency**: HELP (Tolong), STOP (Berhenti), DOCTOR (Dokter), DANGER (Bahaya), POLICE (Polisi), MEDICINE (Obat)
5. **Numbers**: ONE (Satu), TWO (Dua), THREE (Tiga)
6. **Alphabet**: A, B, C, L, Y (Fingerspelling postures)

---

## 7. Keyboard Shortcuts

- `Ctrl + K` or `⌘ + K`: Open Global Cinematic Search Overlay
- `Esc`: Close Search Overlay or Detail Modal
- `Space` / `Enter`: Flip Learning Card front/back
