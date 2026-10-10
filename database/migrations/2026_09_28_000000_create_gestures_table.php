<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gestures', function (Blueprint $t) {
            $t->id();
            $t->string('slug')->unique();
            $t->string('word');
            $t->string('indonesian')->nullable();
            $t->string('category')->index(); // greet, conv, needs, emerg, num, alpha
            $t->string('emoji')->nullable();
            $t->string('difficulty')->default('Easy');
            $t->text('description');
            $t->text('hand_position')->nullable();
            $t->text('movement')->nullable();
            $t->text('common_mistake')->nullable();
            $t->string('visual_key')->nullable();
            $t->string('video_url')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gestures');
    }
};
