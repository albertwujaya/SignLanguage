# Redesign Ghibli — Catatan Serah Terima

Paket ini berisi **file yang diubah/ditambahkan saja** (bukan seluruh project),
supaya kamu tinggal timpa ke folder project Laravel yang sudah ada. Struktur
foldernya sama persis dengan project aslimu, jadi tinggal copy-paste dan
timpa (replace).

## 1. Cara pasang

1. Salin isi folder `public/` dan `resources/` dari paket ini ke project asli, timpa file yang sama.
2. Bersihkan cache: `php artisan optimize:clear` (opsional tapi disarankan).
3. Jalankan `php artisan test` — **belum sempat kujalankan di sisiku, lihat bagian 4.**
4. Buka halaman Home, Learn, Practice, Translate, Dictionary, Login, Daftar, Profil, dan Admin. Cek di lebar 360px, 768px, 1280px.
5. Aktifkan "reduce motion" di OS/browser, cek hero tetap tampil satu frame statis yang bagus (tangan sudah tergambar, terisi warna, dan bersentuhan).

## 2. File yang diubah

| File | Perubahan |
|---|---|
| `public/css/theme.css` | Ditulis ulang total: token warna Ghibli, radius besar (14–28px), bayangan lembut berlapis, komponen (tombol/kartu/form/overlay/nav/kamera) di-restyle lewat variable, bukan hardcode. |
| `public/css/admin.css` | Token `:root` diganti ke palet Ghibli, radius dibesarkan, warna notice/field disesuaikan. Fungsi dan struktur tidak diubah. |
| `public/js/app.js` | Markup hero diganti (dari `<img>` statis jadi kontainer kosong yang diisi `hero-scene.js`), ditambah kartu "Kata hari ini". Warna hardcode lama (`#18D8D0`, `#A8F3E5`, dll) di SVG diagram gerakan tangan dan overlay kamera canvas dipetakan ke palet baru. Tidak ada logika/fitur yang dihapus. |
| `public/js/anim.js` | Warna confetti diganti ke palet hangat (bentuk & animasi confetti sendiri sudah diubah lewat `theme.css` jadi menyerupai kelopak). |
| `resources/views/app.blade.php` | `theme-color` diganti krem, tambah pengecekan `file_exists` untuk video hero opsional, tambah `<script src=".../hero-scene.js">`. |
| `resources/views/auth/layout.blade.php` | Ditulis ulang: dua kolom terang, ilustrasi statis (langit + bukit + 2 awan dengan animasi kecil), form di kartu kertas. Semua field/route/error dipertahankan. |
| `resources/views/auth/login.blade.php` | Warna link hardcode diganti class `.link-accent`. |
| `resources/views/auth/register.blade.php` | Sama seperti login. |
| `resources/views/profile.blade.php` | Hanya blok `<style>` yang diganti warnanya ke palet Ghibli. Struktur "jurnal belajar" (halaman per jenjang, stiker, slot kosong) tidak diubah. |
| `resources/views/admin/layout.blade.php` | Tambah `<link>` ke `fonts.css` (supaya Fredoka/Nunito benar-benar termuat), `theme-color` diganti. |

## 3. File baru

- `public/js/hero-scene.js` — adegan hero: langit, 3 lapis awan parallax, 4 lapis bukit SVG, rumput & bunga bergoyang, partikel biji dandelion (canvas, maks 40 partikel, DPR dibatasi 2, dikurangi di layar kecil), dan dua tangan simbolis dengan timeline gambar → mendekat → sentuh+glow+semburan kelopak → idle → ulang (total ±8 detik). Otomatis berhenti saat tab disembunyikan atau hero di luar layar. Kalau `public/media/hero-hands.mp4` ada, video itu dipakai menggantikan seluruh adegan kode (dicek lewat Blade, tidak error kalau tidak ada).

## 4. Yang BELUM bisa aku verifikasi

Sandbox tempat aku mengerjakan ini **tidak punya PHP**, jadi beberapa langkah wajib di instruksi awal tidak bisa kujalankan:

- `php artisan test` (17 test) — belum dijalankan sama sekali. Tolong jalankan ini duluan setelah menimpa file, sebelum lanjut apa pun.
- Screenshot di 360/768/1280px — tidak bisa, tidak ada browser yang terhubung ke server Laravel di sisiku.
- Cek console browser bebas error — sama, tidak bisa.
- Pengukuran kontras otomatis — aku hitung manual berdasarkan kombinasi warna (teks kecil hanya pakai 4 warna aman sesuai permintaanmu), tapi belum divalidasi dengan alat seperti axe atau Lighthouse.

Yang SUDAH kucek di sisiku: semua file JS lulus `node --check` (tidak ada error syntax), kurung kurawal CSS seimbang, direktif Blade (`@if/@endif` dll) seimbang di semua file yang kuubah.

## 5. Aset yang masih perlu kamu tambahkan (opsional)

- `public/media/hero-hands.mp4` (+ `.webm` dan poster `.webp`) kalau mau pakai video asli alih-alih adegan kode. Tidak wajib — kalau tidak ada, adegan SVG otomatis dipakai.
- Ikon SVG/Phosphor untuk UI baru kalau ke depannya mau menambah ikon (spesifikasi minta tidak pakai emoji sebagai ikon UI baru — ikon yang sudah ada di navbar/bottom-nav sebelumnya sudah SVG, jadi ini aman, tidak ada yang berubah).

## 6. Stretch goal yang belum dikerjakan

- "Tiap gesture yang dikuasai menumbuhkan satu tanaman kecil di halaman jenjang" (disebut opsional di instruksi) — belum diimplementasi, supaya fokus dulu ke 5 tahap wajib.
