<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gesture_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('gesture_id')->constrained()->cascadeOnDelete();
            $table->timestamp('learned_at')->nullable();
            $table->unsignedInteger('attempts')->default(0);
            $table->unsignedTinyInteger('best_score')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'gesture_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gesture_progress');
    }
};
