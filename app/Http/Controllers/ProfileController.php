<?php

namespace App\Http\Controllers;

use App\Models\Gesture;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function show(Request $request): View
    {
        $user = $request->user();

        $totals = Gesture::published()
            ->selectRaw('level, count(*) as total')
            ->groupBy('level')
            ->pluck('total', 'level');

        $learned = $user->gestureProgress()
            ->whereNotNull('learned_at')
            ->join('gestures', 'gestures.id', '=', 'gesture_progress.gesture_id')
            ->where('gestures.status', Gesture::STATUS_PUBLISHED)
            ->whereNull('gestures.deleted_at')
            ->selectRaw('gestures.level as level, count(*) as total')
            ->groupBy('gestures.level')
            ->pluck('total', 'level');

        $levels = collect(Gesture::LEVELS)->map(fn (string $label, string $key) => [
            'label' => $label,
            'learned' => (int) ($learned[$key] ?? 0),
            'total' => (int) ($totals[$key] ?? 0),
        ]);

        $sessions = $user->practiceSessions()->latest()->limit(10)->get();

        $stickers = $user->gestureProgress()
            ->whereNotNull('learned_at')
            ->whereHas('gesture', fn ($q) => $q->published())
            ->with('gesture')
            ->orderBy('learned_at')
            ->get()
            ->pluck('gesture')
            ->filter()
            ->groupBy('level');

        $learnedGestureIds = $user->gestureProgress()
            ->whereNotNull('learned_at')
            ->pluck('gesture_id')
            ->all();

        $catalogByLevel = Gesture::published()
            ->orderBy('sort_order')
            ->orderBy('word')
            ->get()
            ->groupBy('level');

        return view('profile', [
            'user' => $user,
            'levels' => $levels,
            'sessions' => $sessions,
            'stickers' => $stickers,
            'learnedGestureIds' => $learnedGestureIds,
            'catalogByLevel' => $catalogByLevel,
            'averageScore' => $user->practiceSessions()->avg('score'),
            'sessionCount' => $user->practiceSessions()->count(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user)],
            'current_password' => ['nullable', 'required_with:password', 'current_password'],
            'password' => ['nullable', 'confirmed', Password::min(8)],
        ]);

        $user->fill([
            'name' => $validated['name'],
            'email' => $validated['email'],
        ]);

        if (! empty($validated['password'])) {
            $user->password = $validated['password'];
        }

        $user->save();

        return to_route('profile')->with('status', 'Profil berhasil diperbarui.');
    }
}
