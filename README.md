# 🤟 SIGNESIA — Platform Belajar & Terjemah BISINDO Interaktif Berbasis AI

**Signesia** adalah platform web modern, ramah pengguna, dan interaktif untuk belajar, berlatih, dan menerjemahkan **Bahasa Isyarat Indonesia (BISINDO)** secara langsung di peramban (*browser*) tanpa perlu memasang aplikasi tambahan. Dilengkapi dengan kecerdasan buatan (*computer vision*) real-time menggunakan **MediaPipe Hands** dan model neural network kustom, serta rekaman video peraga asli untuk mempermudah siapa saja mempelajari BISINDO secara mandiri.

---

## 🎨 Identitas Visual & Desain

* **Tema Estetika**: *Warm Editorial Scrapbook & Clean Neobrutalism* yang ramah, hangat, dan inklusif.
* **Palet Warna Utama**:
  * Kertas Dasar: Krem Hangat (`#F6EFDD`, `#FFFDF7`)
  * Tinta & Garis: Cokelat Kopi Tua / Tinta Karakter (`#2B2118`)
  * Aksen & Status: Kuning Mustard (`#F2B45A`), Hijau Sage (`#7FA87A`), Merah Koral (`#C0432B`), dan Cyan Hangat (`#2A8C82`)
* **Tipografi**: Kombinasi font tegas dan bersahabat (Fredoka / Space Grotesk untuk judul, Nunito / Inter untuk isi teks).
* **Responsif & Mobile-First**:
  * Dirancang khusus untuk pengalaman layar sentuh (*mobile app-like experience*).
  * Menu navigasi bawah (*bottom bar*) yang ringkas dan nyaman diakses ibu jari.
  * Kartu beranda 2x2 yang kompak, kontrol kartu belajar adaptif (*icon buttons* navigasi `◀` dan `▶`), dan feed kamera horizontal yang presisi.

---

## 🚀 Fitur Utama

```
SIGNESIA
│
├── 🏠 BERANDA (HOME)
│   ├── Animasi Hero Interaktif (Adegan langit, awan parallax, dan sentuhan tangan)
│   ├── Kartu "Kata Hari Ini" untuk kosakata harian cepat
│   ├── Indikator Status AI Real-Time (Ready / Loading)
│   └── 4 Kartu Fitur Cepat (01 Learn, 02 Practice, 03 Translate, 04 Dictionary)
│
├── 📖 BELAJAR (LEARN)
│   ├── Filter Jenjang (Semua, Abjad, Angka, Kata, Kalimat)
│   ├── Kartu Kategori Kurikulum (Basic Greetings, Daily Conversation, Days, Verbs, Numbers, Alphabet)
│   └── Kartu Belajar Interaktif 3D Flip Card:
│       ├── Sisi Depan: Video rekaman peraga asli BISINDO + audio feedback
│       ├── Sisi Belakang: Panduan posisi tangan, arah gerakan, dan kesalahan umum
│       ├── Navigasi Geser / Tombol Cepat (Sebelumnya, Tandai Dipelajari, Berikutnya)
│       └── Tombol Pintas Langsung Menuju Latihan Kamera
│
├── 🎯 LATIHAN (PRACTICE)
│   ├── Viewport Kamera Cerdas (Kamera langsung atau Simulator Landmark Tangan)
│   ├── Pelacakan 21 Titik Landmark Tangan secara Real-Time dengan MediaPipe
│   ├── Kartu Target Gestur & Petunjuk Visual
│   ├── Evaluasi AI Otomatis (Model MLP untuk Huruf & Angka, Model Dinamis untuk Kata)
│   ├── Sesi Latihan 10 Langkah dengan Indikator Titik Kemajuan
│   ├── Tombol "Test Match" & Deteksi Kepercayaan Pose AI
│   └── Kartu Ringkasan Sesi (Akurasi %, Waktu, Jumlah Gestur Dikuasai)
│
├── 🔄 TERJEMAH (TRANSLATE — 3 Modalitas)
│   ├── Gestur → Teks: Deteksi gerakan tangan via kamera yang langsung disusun menjadi kata
│   ├── Penyusun Kalimat (Sentence Tray): Sisipkan kata, hapus kata per kata, atau bersihkan baki kalimat
│   └── Teks → Suara (Text-to-Speech): Sintesis suara bilingual (Indonesia & Inggris) via Web Speech API
│
├── 📚 KAMUS (DICTIONARY)
│   ├── Kolom Pencarian Cepat + Modal Pencarian Global (Pintasan Ctrl+K)
│   ├── Filter Kategori (Semua, Sapaan, Percakapan, Hari, Kata Kerja, Angka, Abjad)
│   ├── Kartu Kosakata dengan Video Tutorial & Penanda Favorit (Bookmark)
│   └── Drawer Modal Detail Gestur: Video footage jernih, deskripsi lengkap, & tombol aksi latihan
│
├── 👥 TENTANG KAMI (ABOUT US)
│   ├── Informasi Proyek, Latar Belakang Lomba Kampus, dan Transparansi Pengembangan
│   ├── Kartu 2x2 Fitur Utama Platform
│   ├── Profil Anggota Tim Pengembang dengan Kartu List Kompak
│   └── Rincian Teknologi yang Digunakan
│
├── 👤 AKUN & SINKRONISASI PROGRES
│   ├── Registrasi & Login Pengguna
│   ├── Jurnal Belajar di Halaman Profil (Daftar gestur yang sudah dikuasai per jenjang)
│   └── Sinkronisasi Progres Otomatis antara LocalStorage dan Database Server
│
└── 🛠️ PANEL ADMIN (GESTURE MANAGEMENT)
    ├── Dashboard Kelola Gestur (`/admin/gestures`)
    ├── Tambah, Ubah, dan Hapus (Soft-Delete) Data Kosakata BISINDO
    ├── Unggah File Video Asli (MP4/WebM) dan Gambar Poster (WebP/PNG/JPG)
    └── Pratinjau (*Preview*) Media dan Pembaruan Urutan Gestur
```

---

## 💻 Teknologi yang Digunakan

### Backend & Server
* **Framework**: Laravel 12 (PHP 8.2+)
* **Database**: SQLite (ringan, portabel, tanpa ketergantungan konfigurasi database eksternal)
* **Arsitektur**: Model-View-Controller (MVC) dengan RESTful API endpoints dan Blade Templating
* **Keamanan & Autentikasi**: Laravel Session & Middleware, Validasi Formulir, Proteksi CSRF

### Frontend & Pengalaman Pengguna
* **Arsitektur SPA**: Vanilla JavaScript modular (ES6+) dengan sistem *hash-based routing* (`#/learn`, `#/practice`, dsb.)
* **CSS & Desain**: Modern CSS Variables, Neobrutalist Paper Theme, CSS Grid & Flexbox responsif
* **Suara & Interaksi**: Web Audio API untuk efek suara haptic/beep dan Web Speech API (`SpeechSynthesisUtterance`)

### Kecerdasan Buatan & Computer Vision
* **Deteksi Tangan**: **Google MediaPipe Hands** (`@mediapipe/camera_utils` dan `@mediapipe/hands`) — 100% diproses di sisi klien (*client-side*) tanpa membebani server
* **Model Klasifikasi Kustom**:
  * `public/models/letter_model.json`: Klasifikasi MLP landmark tangan untuk 26 huruf alfabet (A–Z)
  * `public/models/number_model.json`: Klasifikasi MLP untuk angka 1–10
  * `public/models/dynamic_model.json`: Klasifikasi sekuensial untuk gestur dinamis (halo, terima kasih, makan, minum, dsb.)

---

## 📦 Kategori & Kosakata Gestur BISINDO

Platform ini memuat data kosakata BISINDO yang terbagi ke dalam kategori:
1. **Alphabet (Abjad Manual)**: Huruf lengkap A sampai Z (26 gestur ejaan jari)
2. **Numbers (Angka)**: Angka 1 sampai 10 (10 gestur)
3. **Days of the Week (Nama-Nama Hari)**: Senin, Selasa, Rabu, Kamis, Jumat, Sabtu, Minggu (7 gestur)
4. **Verbs (Kata Kerja Sehari-hari)**: Makan, Minum, Belajar, Datang, Tidur, dll.
5. **Basic Greetings (Sapaan Dasar)**: Halo, Terima Kasih, Selamat Tinggal, Tolong, Sama-sama, dll.
6. **Daily Conversation (Percakapan Sehari-hari)**: Apa Kabar, Hari Ini, Ya, Tidak, Mengerti, Nama, dll.

Setiap gestur dilengkapi dengan video tutorial peraga asli, deskripsi cara melakukan, orientasi posisi tangan, arah gerakan, dan peringatan kesalahan umum.

---

## 🛠️ Panduan Menjalankan Proyek Secara Lokal

### Prasyarat
* PHP >= 8.2 (dilengkapi ekstensi `pdo_sqlite`, `mbstring`, `fileinfo`)
* Composer (manajer dependensi PHP)
* Web browser modern (Google Chrome, Microsoft Edge, atau Firefox) yang mendukung akses kamera (*Webcam API*)

### Langkah Instalasi

1. **Clone repositori**:
   ```bash
   git clone https://github.com/nahdazahirah/SignLanguage.git
   cd SignLanguage
   ```

2. **Pasang dependensi Composer**:
   ```bash
   composer install
   ```

3. **Siapkan berkas konfigurasi `.env`**:
   ```bash
   copy .env.example .env
   php artisan key:generate
   ```

4. **Siapkan Database SQLite & Jalankan Migrasi serta Seeder**:
   ```bash
   # Buat database jika belum ada
   type nul > database\database.sqlite

   # Jalankan migrasi dan isi data awal gestur
   php artisan migrate --seed
   ```

5. **Jalankan Server Lokal**:
   ```bash
   php artisan serve
   ```

6. **Buka aplikasi di peramban**:
   Kunjungi [http://127.0.0.1:8000](http://127.0.0.1:8000) di browser Anda.

---

## 🧪 Menjalankan Pengujian (Tests)

Platform ini dilengkapi dengan rangkaian pengujian otomatis unit dan fitur (*Feature & Unit Tests*) berbasis PHPUnit:

```bash
php artisan test --compact
```
*(Seluruh 38 pengujian telah diverifikasi lulus / passed).*

---

## 🌐 Endpoint API

| Metode | URL Endpoint | Deskripsi |
|---|---|---|
| `GET` | `/api/gestures` | Mengambil seluruh daftar kosakata gestur (mendukung filter `?category=` dan pencarian `?q=`) |
| `GET` | `/api/gestures/{slug}` | Mengambil data detail satu gestur spesifik beserta media |
| `GET` | `/api/stats` | Mengambil ringkasan statistik total gestur, jumlah kategori, dan status AI |
| `GET` | `/api/progress` | Mengambil daftar gestur yang telah dipelajari oleh user yang login |
| `POST` | `/api/progress` | Menyimpan status gestur yang telah dipelajari ke akun pengguna |

---

## ⌨️ Pintasan Keyboard (*Keyboard Shortcuts*)

* `Ctrl + K` (atau `⌘ + K` di macOS): Membuka dialog pencarian cepat global (*Quick Search Overlay*).
* `Esc`: Menutup modal pencarian atau modal detail kartu gestur.
* `Spasi` / `Enter`: Membalik kartu belajar 3D (*Flip Card*) di halaman Learn antara video depan dan instruksi belakang.

---

## 👥 Tim Pengembang

Proyek **Signesia** dirancang dan dikembangkan dengan dedikasi untuk memperluas aksesibilitas dan inklusi bahasa isyarat di Indonesia:
* **Albert Hans** — Pengaturan deployment, infrastruktur sistem, dan kontribusi dataset.
* **Nada Gaming** — Perumusan konsep, arsitektur dasar website, dan kontribusi dataset.
* **Dini Sleepy** — Improvisasi visual, interaksi antarmuka pengguna, dan kontribusi dataset.

---

## 📄 Lisensi

Proyek ini dikembangkan untuk tujuan edukasi dan kemanusiaan di bawah lisensi terbuka [MIT License](LICENSE).
