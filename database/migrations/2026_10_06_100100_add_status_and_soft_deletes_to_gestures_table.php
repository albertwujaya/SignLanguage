<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gestures', function (Blueprint $table) {
            $table->string('status')->default('published')->index()->after('difficulty');
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('gestures', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropColumn('status');
        });
    }
};
