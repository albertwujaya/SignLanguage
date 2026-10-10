<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gestures', function (Blueprint $table) {
            $table->string('level')->default('kata')->index(); // abjad, kata, kalimat
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('image_path')->nullable();
            $table->string('video_path')->nullable();
        });

        // Data lama: huruf masuk jenjang abjad, frasa jadi kalimat, sisanya kata.
        DB::table('gestures')->where('category', 'alpha')->update(['level' => 'abjad']);
        DB::table('gestures')->where('slug', 'how-are-you')->update(['level' => 'kalimat']);
    }

    public function down(): void
    {
        Schema::table('gestures', function (Blueprint $table) {
            $table->dropColumn(['level', 'sort_order', 'image_path', 'video_path']);
        });
    }
};
