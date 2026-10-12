<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\GestureRequest;
use App\Models\AdminActivityLog;
use App\Models\Gesture;
use App\Models\PracticeAttempt;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class GestureController extends Controller
{
    public function index(Request $request): View
    {
        $query = Gesture::query()
            ->when($request->level, fn ($q, $v) => $q->where('level', $v))
            ->when($request->status, fn ($q, $v) => $q->where('status', $v))
            ->when($request->q, function ($q, $v): void {
                $search = mb_strtolower($v);

                $q->where(function ($sub) use ($search): void {
                    $sub->whereRaw('LOWER(word) LIKE ?', ["%{$search}%"])
                        ->orWhereRaw('LOWER(indonesian) LIKE ?', ["%{$search}%"])
                        ->orWhereRaw('LOWER(slug) LIKE ?', ["%{$search}%"]);
                });
            })
            ->orderBy('level')
            ->orderBy('sort_order')
            ->orderBy('word');

        $countsByLevel = [];
        foreach (Gesture::LEVELS as $lvlKey => $lvlLabel) {
            $countsByLevel[$lvlKey] = [
                'published' => Gesture::where('level', $lvlKey)->published()->count(),
                'draft' => Gesture::where('level', $lvlKey)->where('status', Gesture::STATUS_DRAFT)->count(),
                'total' => Gesture::where('level', $lvlKey)->count(),
            ];
        }

        return view('admin.gestures.index', [
            'gestures' => $query->paginate(15)->withQueryString(),
            'levels' => Gesture::LEVELS,
            'currentLevel' => $request->level,
            'countsByLevel' => $countsByLevel,
            'totalCount' => Gesture::count(),
            'publishedCount' => Gesture::published()->count(),
            'draftCount' => Gesture::where('status', Gesture::STATUS_DRAFT)->count(),
            'trashedCount' => Gesture::onlyTrashed()->count(),
            'categoryLabels' => Gesture::CATEGORY_LABELS,
        ]);
    }

    public function create(Request $request): View
    {
        $level = $request->query('level', 'kata');
        if (! array_key_exists($level, Gesture::LEVELS)) {
            $level = 'kata';
        }

        $allowedCategories = Gesture::LEVEL_CATEGORIES[$level] ?? Gesture::CATEGORIES;
        $defaultCategory = $allowedCategories[0] ?? 'greet';

        return view('admin.gestures.form', [
            'gesture' => new Gesture([
                'status' => Gesture::STATUS_PUBLISHED,
                'level' => $level,
                'category' => $defaultCategory,
            ]),
            'categories' => $allowedCategories,
            'categoryLabels' => Gesture::CATEGORY_LABELS,
            'difficulties' => GestureRequest::DIFFICULTIES,
            'levels' => Gesture::LEVELS,
            'fixedLevel' => $level,
            'levelCategories' => Gesture::LEVEL_CATEGORIES,
        ]);
    }

    public function store(GestureRequest $request): RedirectResponse
    {
        $data = $this->handleMediaUploads($request);
        $data['status'] = $data['status'] ?? Gesture::STATUS_PUBLISHED;

        $gesture = Gesture::create($data);

        AdminActivityLog::record('create', $gesture, "Menambahkan gesture '{$gesture->word}' ({$gesture->level})");

        return to_route('admin.gestures.index')->with('status', 'Gesture berhasil ditambahkan.');
    }

    public function edit(Gesture $gesture): View
    {
        $level = $gesture->level ?: 'kata';
        $allowedCategories = Gesture::LEVEL_CATEGORIES[$level] ?? Gesture::CATEGORIES;

        return view('admin.gestures.form', [
            'gesture' => $gesture,
            'categories' => $allowedCategories,
            'categoryLabels' => Gesture::CATEGORY_LABELS,
            'difficulties' => GestureRequest::DIFFICULTIES,
            'levels' => Gesture::LEVELS,
            'fixedLevel' => $level,
            'levelCategories' => Gesture::LEVEL_CATEGORIES,
        ]);
    }

    public function update(GestureRequest $request, Gesture $gesture): RedirectResponse
    {
        $data = $this->handleMediaUploads($request, $gesture);
        $gesture->update($data);

        AdminActivityLog::record('update', $gesture, "Memperbarui gesture '{$gesture->word}'");

        return to_route('admin.gestures.index')->with('status', 'Gesture berhasil diperbarui.');
    }

    public function destroy(Gesture $gesture): RedirectResponse
    {
        $word = $gesture->word;
        // Soft delete: arsipkan, media tidak langsung dihapus agar bisa dipulihkan
        $gesture->delete();

        AdminActivityLog::record('delete', $gesture, "Memindahkan gesture '{$word}' ke tempat sampah");

        return to_route('admin.gestures.index')->with('status', "Gesture '{$word}' dipindahkan ke tempat sampah.");
    }

    public function toggleStatus(Gesture $gesture): RedirectResponse
    {
        $newStatus = $gesture->status === Gesture::STATUS_PUBLISHED
            ? Gesture::STATUS_DRAFT
            : Gesture::STATUS_PUBLISHED;

        $gesture->update(['status' => $newStatus]);

        $action = $newStatus === Gesture::STATUS_PUBLISHED ? 'publish' : 'draft';
        $label = $newStatus === Gesture::STATUS_PUBLISHED ? 'diterbitkan' : 'dijadikan draft';

        AdminActivityLog::record($action, $gesture, "Mengubah status gesture '{$gesture->word}' menjadi {$label}");

        return back()->with('status', "Status gesture '{$gesture->word}' berhasil diubah menjadi {$label}.");
    }

    public function preview(Gesture $gesture): View
    {
        return view('admin.gestures.preview', [
            'gesture' => $gesture,
            'levels' => Gesture::LEVELS,
        ]);
    }

    public function completeness(): View
    {
        $levelsSummary = collect(Gesture::LEVELS)->map(function (string $label, string $key) {
            $total = Gesture::where('level', $key)->count();
            $complete = Gesture::where('level', $key)
                ->whereNotNull('image_path')
                ->whereNotNull('video_path')
                ->whereNotNull('description')
                ->where('description', '!=', '')
                ->count();

            return [
                'key' => $key,
                'label' => $label,
                'total' => $total,
                'complete' => $complete,
                'incomplete' => $total - $complete,
                'percent' => $total > 0 ? round(($complete / $total) * 100, 1) : 0,
            ];
        });

        $incompleteGestures = Gesture::query()
            ->where(function ($q): void {
                $q->whereNull('image_path')
                    ->orWhereNull('video_path')
                    ->orWhereNull('description')
                    ->orWhere('description', '');
            })
            ->orderBy('level')
            ->orderBy('word')
            ->paginate(20);

        return view('admin.gestures.completeness', [
            'levelsSummary' => $levelsSummary,
            'incompleteGestures' => $incompleteGestures,
        ]);
    }

    public function analytics(): View
    {
        // Gesture Tersulit (persentase benar terendah, min 2 percobaan agar relevan)
        $hardest = PracticeAttempt::select('gesture_id')
            ->selectRaw('count(*) as total_attempts')
            ->selectRaw('sum(case when is_correct then 1 else 0 end) as correct_attempts')
            ->selectRaw('sum(case when not is_correct then 1 else 0 end) as wrong_attempts')
            ->selectRaw('round((sum(case when is_correct then 1.0 else 0.0 end) / count(*)) * 100, 1) as accuracy')
            ->groupBy('gesture_id')
            ->havingRaw('count(*) >= ?', [2])
            ->orderBy('accuracy', 'asc')
            ->orderBy('total_attempts', 'desc')
            ->limit(20)
            ->with(['gesture' => fn ($q) => $q->withTrashed()])
            ->get();

        // Gesture Terpopuler (paling sering dilatih)
        $popular = PracticeAttempt::select('gesture_id')
            ->selectRaw('count(*) as total_attempts')
            ->selectRaw('sum(case when is_correct then 1 else 0 end) as correct_attempts')
            ->selectRaw('round((sum(case when is_correct then 1.0 else 0.0 end) / count(*)) * 100, 1) as accuracy')
            ->groupBy('gesture_id')
            ->orderBy('total_attempts', 'desc')
            ->limit(20)
            ->with(['gesture' => fn ($q) => $q->withTrashed()])
            ->get();

        $totalAttempts = PracticeAttempt::count();

        return view('admin.gestures.analytics', [
            'hardest' => $hardest,
            'popular' => $popular,
            'totalAttempts' => $totalAttempts,
        ]);
    }

    public function trash(): View
    {
        $trashed = Gesture::onlyTrashed()
            ->orderBy('deleted_at', 'desc')
            ->paginate(15);

        return view('admin.gestures.trash', [
            'gestures' => $trashed,
            'levels' => Gesture::LEVELS,
        ]);
    }

    public function restore(int $id): RedirectResponse
    {
        $gesture = Gesture::onlyTrashed()->findOrFail($id);
        $word = $gesture->word;
        $gesture->restore();

        AdminActivityLog::record('restore', $gesture, "Memulihkan gesture '{$word}' dari tempat sampah");

        return to_route('admin.gestures.trash')->with('status', "Gesture '{$word}' berhasil dipulihkan.");
    }

    public function forceDelete(int $id): RedirectResponse
    {
        $gesture = Gesture::onlyTrashed()->findOrFail($id);
        $word = $gesture->word;

        // Bersihkan file media fisik pada disk public saat hapus permanen
        $this->deleteFile($gesture->image_path);
        $this->deleteFile($gesture->video_path);

        $gesture->forceDelete();

        AdminActivityLog::record('force_delete', null, "Menghapus permanen gesture '{$word}' beserta media");

        return to_route('admin.gestures.trash')->with('status', "Gesture '{$word}' berhasil dihapus permanen.");
    }

    /**
     * @return array<string, mixed>
     */
    private function handleMediaUploads(GestureRequest $request, ?Gesture $gesture = null): array
    {
        $data = $request->validated();

        foreach (['image' => 'image_path', 'video' => 'video_path'] as $input => $column) {
            unset($data[$input], $data['remove_'.$input]);

            if ($request->hasFile($input)) {
                $this->deleteFile($gesture?->{$column});
                $data[$column] = $request->file($input)->store('gestures/'.$input.'s', 'public');
            } elseif ($gesture && $request->boolean('remove_'.$input)) {
                $this->deleteFile($gesture->{$column});
                $data[$column] = null;
            }
        }

        $data['sort_order'] = $data['sort_order'] ?? 0;

        return $data;
    }

    private function deleteFile(?string $path): void
    {
        if ($path) {
            Storage::disk('public')->delete($path);
        }
    }
}
