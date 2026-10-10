<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'level', 'total', 'correct', 'score', 'duration_seconds', 'is_verified'])]
class PracticeSession extends Model
{
    protected function casts(): array
    {
        return ['is_verified' => 'boolean'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
