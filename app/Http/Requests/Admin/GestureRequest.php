<?php

namespace App\Http\Requests\Admin;

use App\Models\Gesture;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GestureRequest extends FormRequest
{
    public const CATEGORIES = ['greet', 'conv', 'needs', 'emerg', 'num', 'alpha'];

    public const DIFFICULTIES = ['Easy', 'Medium', 'Hard'];

    public function authorize(): bool
    {
        return $this->user()?->isStaff() ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $gesture = $this->route('gesture');
        $gestureId = $gesture instanceof Gesture ? $gesture->id : $gesture;

        return [
            'slug' => ['required', 'string', 'max:255', Rule::unique('gestures', 'slug')->ignore($gestureId)],
            'word' => ['required', 'string', 'max:255'],
            'indonesian' => ['nullable', 'string', 'max:255'],
            'category' => ['required', Rule::in(self::CATEGORIES)],
            'level' => ['required', Rule::in(array_keys(Gesture::LEVELS))],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'emoji' => ['nullable', 'string', 'max:32'],
            'difficulty' => ['required', Rule::in(self::DIFFICULTIES)],
            'description' => ['required', 'string'],
            'hand_position' => ['nullable', 'string'],
            'movement' => ['nullable', 'string'],
            'common_mistake' => ['nullable', 'string'],
            'visual_key' => ['nullable', 'string', 'max:255'],
            'video_url' => ['nullable', 'url:http,https', 'max:255'],
            'status' => ['nullable', Rule::in(['published', 'draft'])],
            // SVG sengaja tidak diizinkan (bisa memuat script).
            'image' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
            'video' => ['nullable', 'file', 'mimetypes:video/mp4,video/webm', 'max:30720'],
            'remove_image' => ['nullable', 'boolean'],
            'remove_video' => ['nullable', 'boolean'],
        ];
    }
}
