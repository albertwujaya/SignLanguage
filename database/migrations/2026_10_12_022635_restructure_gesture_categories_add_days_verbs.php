<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Tahap 1 — Restrukturisasi Kategori Gesture:
 *
 *  - emerg  →  days   (hari-hari dalam seminggu)
 *  - needs  →  verbs  (kata kerja sehari-hari)
 *  - help   dipindah dari emerg ke greet
 *  - Kartu lama yang tidak masuk daftar baru di-soft-delete
 *  - Kartu baru (hari & kata kerja) dibuat via firstOrCreate
 *
 * Rollback HANYA mengubah balik data; kartu baru tetap ada sebagai draft
 * sehingga riwayat progres tidak rusak.
 */
return new class extends Migration
{
    /** Placeholder deskripsi untuk kartu baru (aturan keras 1). */
    private const PLACEHOLDER_DESC = 'Deskripsi gerakan belum diisi. Menunggu verifikasi dari referensi BISINDO resmi.';

    public function up(): void
    {
        // 1. Pindahkan 'help' dari emerg → greet
        DB::table('gestures')
            ->where('slug', 'help')
            ->whereNull('deleted_at')
            ->update(['category' => 'greet', 'updated_at' => now()]);

        // 2. Ganti kategori kartu yang dipertahankan: eat & sleep (needs → verbs)
        DB::table('gestures')
            ->whereIn('slug', ['eat', 'sleep'])
            ->whereNull('deleted_at')
            ->update(['category' => 'verbs', 'updated_at' => now()]);

        // 3. Soft-delete kartu lama yang tidak ada di daftar baru
        //    (stop, doctor, danger, police, medicine, water, bathroom, want)
        $slugsToDelete = ['stop', 'doctor', 'danger', 'police', 'medicine', 'water', 'bathroom', 'want'];
        DB::table('gestures')
            ->whereIn('slug', $slugsToDelete)
            ->whereNull('deleted_at')
            ->update(['deleted_at' => now(), 'updated_at' => now()]);

        // 4. Tambah kartu hari-hari (kategori days, level kata)
        $days = [
            ['slug' => 'monday',    'word' => 'MONDAY',    'indonesian' => 'SENIN',   'sort_order' => 1],
            ['slug' => 'tuesday',   'word' => 'TUESDAY',   'indonesian' => 'SELASA',  'sort_order' => 2],
            ['slug' => 'wednesday', 'word' => 'WEDNESDAY', 'indonesian' => 'RABU',    'sort_order' => 3],
            ['slug' => 'thursday',  'word' => 'THURSDAY',  'indonesian' => 'KAMIS',   'sort_order' => 4],
            ['slug' => 'friday',    'word' => 'FRIDAY',    'indonesian' => 'JUMAT',   'sort_order' => 5],
            ['slug' => 'saturday',  'word' => 'SATURDAY',  'indonesian' => 'SABTU',   'sort_order' => 6],
            ['slug' => 'sunday',    'word' => 'SUNDAY',    'indonesian' => 'MINGGU',  'sort_order' => 7],
        ];

        foreach ($days as $day) {
            $existing = DB::table('gestures')->where('slug', $day['slug'])->first();
            if (! $existing) {
                DB::table('gestures')->insert(array_merge($day, [
                    'category'    => 'days',
                    'level'       => 'kata',
                    'difficulty'  => 'Easy',
                    'status'      => 'draft',
                    'description' => self::PLACEHOLDER_DESC,
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]));
            }
        }

        // 5. Tambah kartu kata kerja (kategori verbs, level kata)
        //    eat & sleep sudah ada — lewati, tambah yang baru saja
        $verbs = [
            ['slug' => 'drink',   'word' => 'DRINK',  'indonesian' => 'MINUM',   'sort_order' => 2],
            ['slug' => 'wake-up', 'word' => 'WAKE UP', 'indonesian' => 'BANGUN',  'sort_order' => 4],
            ['slug' => 'come',    'word' => 'COME',   'indonesian' => 'DATANG',  'sort_order' => 5],
            ['slug' => 'study',   'word' => 'STUDY',  'indonesian' => 'BELAJAR', 'sort_order' => 6],
        ];

        // Perbaiki sort_order eat & sleep yang sudah ada
        DB::table('gestures')->where('slug', 'eat')->whereNull('deleted_at')
            ->update(['sort_order' => 1, 'updated_at' => now()]);
        DB::table('gestures')->where('slug', 'sleep')->whereNull('deleted_at')
            ->update(['sort_order' => 3, 'updated_at' => now()]);

        foreach ($verbs as $verb) {
            $existing = DB::table('gestures')->where('slug', $verb['slug'])->first();
            if (! $existing) {
                DB::table('gestures')->insert(array_merge($verb, [
                    'category'    => 'verbs',
                    'level'       => 'kata',
                    'difficulty'  => 'Easy',
                    'status'      => 'draft',
                    'description' => self::PLACEHOLDER_DESC,
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]));
            }
        }
    }

    public function down(): void
    {
        // Kembalikan 'help' ke emerg
        DB::table('gestures')
            ->where('slug', 'help')
            ->whereNull('deleted_at')
            ->update(['category' => 'emerg', 'updated_at' => now()]);

        // Kembalikan eat & sleep ke needs
        DB::table('gestures')
            ->whereIn('slug', ['eat', 'sleep'])
            ->whereNull('deleted_at')
            ->update(['category' => 'needs', 'updated_at' => now()]);

        // Pulihkan kartu yang di-soft-delete
        $slugsToRestore = ['stop', 'doctor', 'danger', 'police', 'medicine', 'water', 'bathroom', 'want'];
        DB::table('gestures')
            ->whereIn('slug', $slugsToRestore)
            ->whereNotNull('deleted_at')
            ->update(['deleted_at' => null, 'updated_at' => now()]);

        // Catatan: kartu baru (hari & kata kerja) TIDAK dihapus permanen agar progres aman.
        // Kalau perlu, hapus manual dari panel admin > Tempat Sampah.
    }
};
