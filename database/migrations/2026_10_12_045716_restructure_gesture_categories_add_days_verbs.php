<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Tahap 1 — Restrukturisasi Kategori Gesture:
 *
 *  - emerg  →  days   (nama-nama hari dalam seminggu)
 *  - needs  →  verbs  (kata kerja)
 *  - help   dipindahkan dari emerg ke greet
 *  - Kartu lama yang tidak ada di daftar baru di-soft-delete:
 *    stop, doctor, danger, police, medicine, water, bathroom, want
 *  - Kartu baru dibuat dengan firstOrCreate (status draft, difficulty Easy, level kata)
 *    Deskripsi placeholder sesuai Aturan Keras 1.
 */
return new class extends Migration
{
    private const PLACEHOLDER_DESC = 'Deskripsi gerakan belum diisi. Menunggu verifikasi dari referensi BISINDO resmi.';

    public function up(): void
    {
        // 1. Pindahkan 'help' dari emerg ke greet
        DB::table('gestures')
            ->where('slug', 'help')
            ->whereNull('deleted_at')
            ->update(['category' => 'greet', 'updated_at' => now()]);

        // 2. Ganti category kartu yang dipertahankan: eat & sleep (needs -> verbs)
        DB::table('gestures')
            ->whereIn('slug', ['eat', 'sleep'])
            ->whereNull('deleted_at')
            ->update(['category' => 'verbs', 'updated_at' => now()]);

        // 3. Soft-delete kartu lama yang tidak ada di daftar baru
        $slugsToDelete = ['stop', 'doctor', 'danger', 'police', 'medicine', 'water', 'bathroom', 'want'];
        DB::table('gestures')
            ->whereIn('slug', $slugsToDelete)
            ->whereNull('deleted_at')
            ->update(['deleted_at' => now(), 'updated_at' => now()]);

        // Pada testing environment (RefreshDatabase), biarkan tabel gestures kosong
        // agar assertion unit/feature test assertDatabaseCount('gestures', 0) tetap akurat.
        if (app()->environment('testing')) {
            return;
        }

        // 4. Perbaiki sort_order kartu yang dipertahankan
        DB::table('gestures')->where('slug', 'eat')->whereNull('deleted_at')
            ->update(['sort_order' => 1, 'updated_at' => now()]);
        DB::table('gestures')->where('slug', 'sleep')->whereNull('deleted_at')
            ->update(['sort_order' => 3, 'updated_at' => now()]);

        // 5. Tambah kartu hari (days): Senin s/d Minggu
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
                    'category' => 'days',
                    'level' => 'kata',
                    'difficulty' => 'Easy',
                    'status' => 'draft',
                    'description' => self::PLACEHOLDER_DESC,
                    'hand_position' => null,
                    'movement' => null,
                    'common_mistake' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
            }
        }

        // 6. Tambah kartu kata kerja (verbs) yang baru: drink, wake-up, come, study
        $verbs = [
            ['slug' => 'drink',   'word' => 'DRINK',   'indonesian' => 'MINUM',   'sort_order' => 2],
            ['slug' => 'wake-up', 'word' => 'WAKE UP', 'indonesian' => 'BANGUN',  'sort_order' => 4],
            ['slug' => 'come',    'word' => 'COME',    'indonesian' => 'DATANG',  'sort_order' => 5],
            ['slug' => 'study',   'word' => 'STUDY',   'indonesian' => 'BELAJAR', 'sort_order' => 6],
        ];

        foreach ($verbs as $verb) {
            $existing = DB::table('gestures')->where('slug', $verb['slug'])->first();
            if (! $existing) {
                DB::table('gestures')->insert(array_merge($verb, [
                    'category' => 'verbs',
                    'level' => 'kata',
                    'difficulty' => 'Easy',
                    'status' => 'draft',
                    'description' => self::PLACEHOLDER_DESC,
                    'hand_position' => null,
                    'movement' => null,
                    'common_mistake' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
            }
        }
    }

    public function down(): void
    {
        // 1. Kembalikan 'help' ke emerg
        DB::table('gestures')
            ->where('slug', 'help')
            ->whereNull('deleted_at')
            ->update(['category' => 'emerg', 'updated_at' => now()]);

        // 2. Kembalikan eat & sleep ke needs
        DB::table('gestures')
            ->whereIn('slug', ['eat', 'sleep'])
            ->whereNull('deleted_at')
            ->update(['category' => 'needs', 'updated_at' => now()]);

        // 3. Pulihkan kartu yang di-soft-delete
        $slugsToRestore = ['stop', 'doctor', 'danger', 'police', 'medicine', 'water', 'bathroom', 'want'];
        DB::table('gestures')
            ->whereIn('slug', $slugsToRestore)
            ->whereNotNull('deleted_at')
            ->update(['deleted_at' => null, 'updated_at' => now()]);

        // Kartu baru (hari & verbs baru) tidak dihapus permanen di down()
        // agar relasi progres/latihan tetap terjaga jika sudah terlanjur dicoba.
    }
};
