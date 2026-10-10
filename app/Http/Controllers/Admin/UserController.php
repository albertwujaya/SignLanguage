<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRoleRequest;
use App\Models\AdminActivityLog;
use App\Models\Gesture;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $totalPublished = Gesture::published()->count();

        $query = User::query()
            ->withCount([
                'gestureProgress as learned_count' => function ($q): void {
                    $q->whereNotNull('learned_at')->whereHas('gesture', fn ($g) => $g->published());
                },
                'practiceSessions as sessions_count',
            ])
            ->withAvg('practiceSessions as avg_score', 'score');

        // Pencarian nama atau email
        if ($request->filled('q')) {
            $search = mb_strtolower(trim((string) $request->q));
            $query->where(function ($sub) use ($search): void {
                $sub->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(email) LIKE ?', ["%{$search}%"]);
            });
        }

        // Filter role
        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        // Filter status progress
        if ($request->filled('status')) {
            if ($request->status === 'started') {
                $query->where(function ($sub): void {
                    $sub->has('gestureProgress')
                        ->orHas('practiceSessions');
                });
            } elseif ($request->status === 'not_started') {
                $query->doesntHave('gestureProgress')
                    ->doesntHave('practiceSessions');
            }
        }

        // Sorting
        $sort = $request->get('sort', 'name');
        $direction = $request->get('dir', 'asc');
        $direction = in_array(strtolower($direction), ['asc', 'desc'], true) ? $direction : 'asc';

        match ($sort) {
            'progress' => $query->orderBy('learned_count', 'desc')->orderBy('name', 'asc'),
            'score' => $query->orderBy('avg_score', 'desc')->orderBy('name', 'asc'),
            'created_at' => $query->orderBy('created_at', 'desc'),
            'last_active' => $query->orderByRaw('last_active_at IS NULL, last_active_at DESC'),
            default => $query->orderBy('name', $direction),
        };

        $users = $query->paginate(20)->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
            'totalPublished' => $totalPublished,
            'adminCount' => User::query()->where('role', 'admin')->count(),
        ]);
    }

    public function show(User $user): View
    {
        $totalPublished = Gesture::published()->count();

        // Hitung progress per jenjang
        $totalsByLevel = Gesture::published()
            ->selectRaw('level, count(*) as total')
            ->groupBy('level')
            ->pluck('total', 'level');

        $learnedByLevel = $user->gestureProgress()
            ->whereNotNull('learned_at')
            ->join('gestures', 'gestures.id', '=', 'gesture_progress.gesture_id')
            ->where('gestures.status', Gesture::STATUS_PUBLISHED)
            ->whereNull('gestures.deleted_at')
            ->selectRaw('gestures.level as level, count(*) as total')
            ->groupBy('gestures.level')
            ->pluck('total', 'level');

        $levelStats = collect(Gesture::LEVELS)->map(function (string $label, string $key) use ($totalsByLevel, $learnedByLevel) {
            $total = (int) ($totalsByLevel[$key] ?? 0);
            $learned = (int) ($learnedByLevel[$key] ?? 0);
            $percent = $total > 0 ? round(($learned / $total) * 100, 1) : 0;

            return [
                'key' => $key,
                'label' => $label,
                'total' => $total,
                'learned' => $learned,
                'percent' => $percent,
            ];
        });

        // Daftar detail gesture yang dikuasai
        $learnedGestures = $user->gestureProgress()
            ->whereNotNull('learned_at')
            ->whereHas('gesture', fn ($q) => $q->published())
            ->with('gesture')
            ->orderBy('learned_at', 'desc')
            ->get();

        // Statistik sesi latihan
        $sessions = $user->practiceSessions()->latest()->paginate(15);
        $sessionsCount = $user->practiceSessions()->count();
        $avgScore = $sessionsCount > 0 ? round((float) $user->practiceSessions()->avg('score'), 1) : null;
        $maxScore = $user->practiceSessions()->max('score');
        $minScore = $user->practiceSessions()->min('score');

        $totalLearned = $learnedGestures->count();
        $totalProgressPercent = $totalPublished > 0 ? round(($totalLearned / $totalPublished) * 100, 1) : 0;

        return view('admin.users.show', [
            'user' => $user,
            'totalPublished' => $totalPublished,
            'totalLearned' => $totalLearned,
            'totalProgressPercent' => $totalProgressPercent,
            'levelStats' => $levelStats,
            'learnedGestures' => $learnedGestures,
            'sessions' => $sessions,
            'sessionsCount' => $sessionsCount,
            'avgScore' => $avgScore,
            'maxScore' => $maxScore,
            'minScore' => $minScore,
        ]);
    }

    public function exportCsv(): StreamedResponse
    {
        $totalPublished = Gesture::published()->count();

        $users = User::query()
            ->withCount([
                'gestureProgress as learned_count' => function ($q): void {
                    $q->whereNotNull('learned_at')->whereHas('gesture', fn ($g) => $g->published());
                },
                'practiceSessions as sessions_count',
            ])
            ->withAvg('practiceSessions as avg_score', 'score')
            ->orderBy('name')
            ->get();

        $headers = [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="users_progress_'.date('Y-m-d_His').'.csv"',
        ];

        $callback = function () use ($users, $totalPublished): void {
            $file = fopen('php://output', 'w');
            // Add UTF-8 BOM for Excel compatibility
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($file, [
                'ID',
                'Nama',
                'Email',
                'Role',
                'Tanggal Daftar',
                'Terakhir Aktif',
                'Gesture Dikuasai',
                'Total Gesture Terbit',
                'Progres (%)',
                'Rata-rata Nilai',
                'Jumlah Sesi Latihan',
            ]);

            foreach ($users as $user) {
                $percent = $totalPublished > 0 ? round(($user->learned_count / $totalPublished) * 100, 1) : 0;
                fputcsv($file, [
                    $user->id,
                    $user->name,
                    $user->email,
                    $user->role ?: 'user',
                    $user->created_at ? $user->created_at->format('Y-m-d H:i:s') : '—',
                    $user->last_active_at ? $user->last_active_at->format('Y-m-d H:i:s') : '—',
                    $user->learned_count,
                    $totalPublished,
                    $percent.'%',
                    $user->avg_score !== null ? round((float) $user->avg_score, 1) : '—',
                    $user->sessions_count,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $user = User::create($validated + ['role' => 'editor']);

        AdminActivityLog::record('create', $user, "Menambahkan akun editor {$user->name} ({$user->email})");

        return to_route('admin.users.index')->with('status', 'Akun editor berhasil dibuat.');
    }

    public function updateRole(UpdateUserRoleRequest $request, User $user): RedirectResponse
    {
        abort_if($request->user()->is($user), 403);

        $validated = $request->validated();

        if ($user->role === 'admin' && $validated['role'] !== 'admin') {
            $this->ensureAnotherAdminExists($user);
        }

        $oldRole = $user->role;
        $user->update(['role' => $validated['role']]);

        AdminActivityLog::record('update', $user, "Mengubah role {$user->name} dari {$oldRole} menjadi {$user->role}");

        return to_route('admin.users.index')->with('status', 'Role akun berhasil diperbarui.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        abort_if($request->user()->is($user), 403);

        if ($user->role === 'admin') {
            $this->ensureAnotherAdminExists($user);
        }

        $name = $user->name;
        $user->delete();

        AdminActivityLog::record('delete', null, "Menghapus akun {$name}");

        return to_route('admin.users.index')->with('status', 'Akun berhasil dihapus.');
    }

    private function ensureAnotherAdminExists(User $user): void
    {
        if (! User::query()->where('role', 'admin')->where('id', '!=', $user->id)->exists()) {
            throw ValidationException::withMessages([
                'role' => 'Sisakan setidaknya satu akun admin aktif.',
            ]);
        }
    }
}
