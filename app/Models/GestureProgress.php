<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'gesture_id', 'learned_at', 'attempts', 'best_score'])]
class GestureProgress extends Model
{
    protected $table = 'gesture_progress';

    protected function casts(): array
    {
        return ['learned_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function gesture(): BelongsTo
    {
        return $this->belongsTo(Gesture::class);
    }
}
