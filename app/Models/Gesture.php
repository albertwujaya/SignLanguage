<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'slug',
    'word',
    'indonesian',
    'category',
    'level',
    'sort_order',
    'emoji',
    'difficulty',
    'status',
    'description',
    'hand_position',
    'movement',
    'common_mistake',
    'visual_key',
    'video_url',
    'image_path',
    'video_path',
])]
class Gesture extends Model
{
    use SoftDeletes;

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_DRAFT = 'draft';

    public const LEVELS = [
        'abjad' => 'Abjad',
        'angka' => 'Angka',
        'kata' => 'Kata',
        'kalimat' => 'Kalimat',
    ];

    public const CATEGORIES = ['greet', 'conv', 'days', 'verbs', 'num', 'alpha'];

    public const CATEGORY_LABELS = [
        'greet' => 'Basic Greetings',
        'conv' => 'Daily Conversation',
        'days' => 'Days of the Week',
        'verbs' => 'Verbs',
        'num' => 'Numbers',
        'alpha' => 'Alphabet',
    ];

    public const LEVEL_CATEGORIES = [
        'abjad' => ['alpha'],
        'angka' => ['num'],
        'kata' => ['greet', 'conv', 'days', 'verbs'],
        'kalimat' => ['greet', 'conv'],
    ];

    public function progress(): HasMany
    {
        return $this->hasMany(GestureProgress::class);
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(PracticeAttempt::class);
    }

    public function scopePublished(Builder $query): void
    {
        $query->where('status', self::STATUS_PUBLISHED);
    }

    public function isPublished(): bool
    {
        return ($this->status ?? self::STATUS_PUBLISHED) === self::STATUS_PUBLISHED;
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function imageUrl(): ?string
    {
        return $this->image_path
            ? Storage::disk('public')->url($this->image_path)
            : null;
    }

    public function videoFileUrl(): ?string
    {
        return $this->video_path
            ? Storage::disk('public')->url($this->video_path)
            : null;
    }
}
