<?php

namespace App\Http\Controllers;

use App\Models\Gesture;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GestureController extends Controller
{
    // GET /api/gestures?q=hel&category=greet&level=abjad
    public function index(Request $request): JsonResponse
    {
        $items = Gesture::query()
            ->when(! ($request->user()?->isStaff() && $request->boolean('preview')), function ($q): void {
                $q->published();
            })
            ->when($request->q, function ($q, $v): void {
                $search = mb_strtolower($v);

                $q->where(function ($sub) use ($search): void {
                    $sub->whereRaw('LOWER(word) LIKE ?', ["%{$search}%"])
                        ->orWhereRaw('LOWER(indonesian) LIKE ?', ["%{$search}%"])
                        ->orWhereRaw('LOWER(description) LIKE ?', ["%{$search}%"]);
                });
            })
            ->when($request->category && $request->category !== 'all', function ($q) use ($request): void {
                $q->where('category', $request->category);
            })
            ->when($request->level && $request->level !== 'all', function ($q) use ($request): void {
                $q->where('level', $request->level);
            })
            ->orderBy('sort_order')
            ->orderBy('word')
            ->orderBy('id')
            ->get();

        return response()->json($items->map(fn ($g) => $this->present($g))->values());
    }

    /**
     * @return array<string, mixed>
     */
    private function present(Gesture $g): array
    {
        return [
            'id' => $g->slug,
            'w' => $g->word,
            'id_word' => $g->indonesian,
            'c' => $g->category,
            'e' => $g->emoji,
            'diff' => $g->difficulty,
            'desc' => $g->description,
            'pos' => $g->hand_position,
            'mv' => $g->movement,
            'mis' => $g->common_mistake,
            'vkey' => $g->visual_key,
            'video' => $g->video_url,
            'level' => $g->level,
            'status' => $g->status,
            'sort_order' => (int) $g->sort_order,
            'image' => $g->imageUrl(),
            'video_file' => $g->videoFileUrl(),
        ];
    }

    // GET /api/gestures/{slug}
    public function show(Request $request, string $slug): JsonResponse
    {
        $query = Gesture::where('slug', $slug);
        if (! ($request->user()?->isStaff() && $request->boolean('preview'))) {
            $query->published();
        }

        $g = $query->firstOrFail();

        return response()->json($this->present($g));
    }

    // GET /api/stats
    public function stats(): JsonResponse
    {
        $counts = Gesture::published()
            ->selectRaw('category, count(*) as count')
            ->groupBy('category')
            ->pluck('count', 'category');

        return response()->json([
            'total' => Gesture::published()->count(),
            'categories' => $counts,
            'engine' => 'SIGN-AI Neural Vision 2026',
            'status' => 'READY',
        ]);
    }
}
