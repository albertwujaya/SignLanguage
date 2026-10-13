<?php

namespace App\Http\Controllers;

use App\Models\Gesture;
use App\Models\GestureProgress;
use App\Models\PracticeAttempt;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Throwable;

class ProgressController extends Controller
{
    // GET /api/progress -> slug yang sudah dipelajari milik user login
    public function index(Request $request): JsonResponse
    {
        $learned = $request->user()->gestureProgress()
            ->whereNotNull('learned_at')
            ->join('gestures', 'gestures.id', '=', 'gesture_progress.gesture_id')
            ->where('gestures.status', Gesture::STATUS_PUBLISHED)
            ->whereNull('gestures.deleted_at')
            ->pluck('gestures.slug');

        return response()->json(['learned' => $learned->values()]);
    }

    // PUT /api/progress/{slug}/learned  (tandai sudah dipelajari)
    public function learn(Request $request, string $slug): JsonResponse
    {
        $gesture = Gesture::published()->where('slug', $slug)->firstOrFail();

        GestureProgress::updateOrCreate(
            ['user_id' => $request->user()->id, 'gesture_id' => $gesture->id],
            ['learned_at' => now()],
        );

        $request->user()->forceFill(['last_active_at' => now()])->saveQuietly();

        return response()->json(['slug' => $slug, 'learned' => true]);
    }

    // DELETE /api/progress/{slug}/learned  (batalkan)
    public function unlearn(Request $request, string $slug): JsonResponse
    {
        $gesture = Gesture::where('slug', $slug)->firstOrFail();

        GestureProgress::where('user_id', $request->user()->id)
            ->where('gesture_id', $gesture->id)
            ->update(['learned_at' => null]);

        $request->user()->forceFill(['last_active_at' => now()])->saveQuietly();

        return response()->json(['slug' => $slug, 'learned' => false]);
    }

    // POST /api/practice-sessions
    public function storeSession(Request $request): JsonResponse
    {
        $data = $request->validate([
            'level' => ['nullable', Rule::in(array_keys(Gesture::LEVELS))],
            'total' => ['required', 'integer', 'min:1', 'max:100'],
            'correct' => ['required', 'integer', 'min:0', 'lte:total'],
            'duration_seconds' => ['nullable', 'integer', 'min:0', 'max:86400'],
            'attempts' => ['nullable', 'array'],
            'attempts.*.gesture_id' => ['nullable', 'integer'],
            'attempts.*.slug' => ['nullable', 'string'],
            'attempts.*.is_correct' => ['required_with:attempts', 'boolean'],
        ]);

        $session = $request->user()->practiceSessions()->create([
            'level' => $data['level'] ?? null,
            'total' => $data['total'],
            'correct' => $data['correct'],
            'score' => (int) round($data['correct'] / $data['total'] * 100),
            'duration_seconds' => $data['duration_seconds'] ?? 0,
            // Server yang menentukan: belum ada pengenalan gerakan sungguhan.
            'is_verified' => false,
        ]);

        $request->user()->forceFill(['last_active_at' => now()])->saveQuietly();

        // Safe attempt logging & progress marking
        if (! empty($data['attempts'])) {
            try {
                foreach ($data['attempts'] as $item) {
                    $gestureId = $item['gesture_id'] ?? null;
                    if (! $gestureId && ! empty($item['slug'])) {
                        $gestureId = Gesture::where('slug', $item['slug'])->value('id');
                    }

                    if ($gestureId) {
                        PracticeAttempt::create([
                            'user_id' => $request->user()->id,
                            'gesture_id' => $gestureId,
                            'is_correct' => (bool) $item['is_correct'],
                        ]);

                        if (! empty($item['is_correct'])) {
                            GestureProgress::updateOrCreate(
                                ['user_id' => $request->user()->id, 'gesture_id' => $gestureId],
                                ['learned_at' => now()],
                            );
                        }
                    }
                }
            } catch (Throwable) {
                // Jangan menggagalkan respon Practice jika pencatatan attempt gagal
            }
        } elseif (! empty($data['level']) && $data['correct'] > 0) {
            try {
                $gestures = Gesture::published()
                    ->where('level', $data['level'])
                    ->orderBy('sort_order')
                    ->take($data['correct'])
                    ->get();

                foreach ($gestures as $gesture) {
                    PracticeAttempt::create([
                        'user_id' => $request->user()->id,
                        'gesture_id' => $gesture->id,
                        'is_correct' => true,
                    ]);

                    GestureProgress::updateOrCreate(
                        ['user_id' => $request->user()->id, 'gesture_id' => $gesture->id],
                        ['learned_at' => now()],
                    );
                }
            } catch (Throwable) {
            }
        }

        return response()->json(['id' => $session->id, 'score' => $session->score], 201);
    }
}
