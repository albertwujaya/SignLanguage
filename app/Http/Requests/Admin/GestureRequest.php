<?php

namespace App\Http\Requests\Admin;

use App\Models\Gesture;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class GestureRequest extends FormRequest
{
    public const CATEGORIES = Gesture::CATEGORIES;

    public const CATEGORY_LABELS = Gesture::CATEGORY_LABELS;

    public const LEVEL_CATEGORIES = Gesture::LEVEL_CATEGORIES;

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
            'image_path' => ['nullable', 'string', 'max:255'],
            'video_path' => ['nullable', 'string', 'max:255'],
            'remove_image' => ['nullable', 'boolean'],
            'remove_video' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Validasi tambahan: pastikan kombinasi level–category valid.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v): void {
            $level = $this->input('level');
            $category = $this->input('category');

            if ($level && $category && isset(self::LEVEL_CATEGORIES[$level])) {
                $allowed = self::LEVEL_CATEGORIES[$level];
                if (! in_array($category, $allowed, true)) {
                    $levelLabel = Gesture::LEVELS[$level] ?? $level;
                    $allowedLabels = implode(', ', array_map(
                        fn (string $c) => self::CATEGORY_LABELS[$c] ?? $c,
                        $allowed
                    ));
                    $v->errors()->add(
                        'category',
                        "Jenjang \"{$levelLabel}\" hanya boleh memakai kategori: {$allowedLabels}."
                    );
                }
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'category.required' => 'Kategori wajib dipilih.',
            'level.required' => 'Jenjang wajib dipilih.',
        ];
    }
}
