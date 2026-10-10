<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('practice_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('level')->nullable();
            $table->unsignedSmallInteger('total');
            $table->unsignedSmallInteger('correct');
            $table->unsignedTinyInteger('score'); // persen 0-100
            $table->unsignedInteger('duration_seconds')->default(0);
            // false selama pencocokan gerakan masih simulasi (belum model sungguhan)
            $table->boolean('is_verified')->default(false);
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('practice_sessions');
    }
};
