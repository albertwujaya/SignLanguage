<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
use App\Models\Gesture;
use App\Models\GestureProgress;
use App\Models\PracticeAttempt;
use App\Models\PracticeSession;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $totalUsers = User::count();
        $activeThisWeek = User::where(function ($q) {
            $q->where('last_active_at', '>=', now()->subDays(7))
                ->orWhere('updated_at', '>=', now()->subDays(7));
        })->count();

        $totalPublishedGestures = Gesture::published()->count();

        // Rata-rata progres pengguna
        $avgProgressPercent = 0;
        if ($totalUsers > 0 && $totalPublishedGestures > 0) {
            $totalLearnedAll = GestureProgress::whereNotNull('learned_at')
                ->whereHas('gesture', fn ($q) => $q->published())
                ->count();
            $avgProgressPercent = round(($totalLearnedAll / ($totalUsers * $totalPublishedGestures)) * 100, 1);
        }

        $totalSessions = PracticeSession::count();
        $avgScore = round((float) PracticeSession::avg('score'), 1);

        $gestureStats = [
            'total' => Gesture::count(),
            'published' => $totalPublishedGestures,
            'draft' => Gesture::where('status', Gesture::STATUS_DRAFT)->count(),
            'trashed' => Gesture::onlyTrashed()->count(),
        ];

        // Kelengkapan konten ringkas
        $incompleteCount = Gesture::query()
            ->whereNull('image_path')
            ->orWhereNull('video_path')
            ->orWhereNull('description')
            ->orWhere('description', '')
            ->count();

        // Gesture tersulit ringkas (min 3 attempt)
        $hardestGestures = PracticeAttempt::select('gesture_id')
            ->selectRaw('count(*) as total_attempts')
            ->selectRaw('sum(case when is_correct then 1 else 0 end) as correct_attempts')
            ->selectRaw('round((sum(case when is_correct then 1.0 else 0.0 end) / count(*)) * 100, 1) as accuracy')
            ->groupBy('gesture_id')
            ->havingRaw('count(*) >= ?', [3])
            ->orderBy('accuracy', 'asc')
            ->limit(5)
            ->with('gesture')
            ->get();

        // Aktivitas admin terkini
        $recentLogs = AdminActivityLog::with('user')->latest()->limit(5)->get();

        return view('admin.dashboard', [
            'totalUsers' => $totalUsers,
            'activeThisWeek' => $activeThisWeek,
            'avgProgressPercent' => $avgProgressPercent,
            'totalSessions' => $totalSessions,
            'avgScore' => $avgScore,
            'gestureStats' => $gestureStats,
            'incompleteCount' => $incompleteCount,
            'hardestGestures' => $hardestGestures,
            'recentLogs' => $recentLogs,
        ]);
    }
}
