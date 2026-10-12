<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('ADMIN_EMAIL');
        $password = env('ADMIN_PASSWORD');

        // Selaras dengan aturan /setup: password admin minimal 12 karakter
        if (! $email || ! $password || strlen($password) < 12) {
            $this->command?->error('Isi ADMIN_EMAIL dan ADMIN_PASSWORD (min. 12 karakter) di .env, lalu jalankan lagi.');

            return;
        }

        $user = User::firstOrNew(['email' => $email]);
        $user->name = env('ADMIN_NAME', 'Admin');
        $user->role = 'admin';

        if (! $user->exists) {
            $user->password = $password; // di-hash otomatis oleh cast 'hashed' di User.php
        }

        $user->save();
    }
}
