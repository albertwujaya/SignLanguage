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
        $activeThisWeek = User::where(function ($q): void {
            $q->where('last_active_at', '>=', now()->subDays(7))
                ->orWhere('updated_at', '>=', now()->subDays(7));
        })->count();

        // Ambil status gesture dalam 1 query terkelompok
        $gestureStatusCounts = Gesture::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $totalPublishedGestures = (int) ($gestureStatusCounts[Gesture::STATUS_PUBLISHED] ?? 0);
        $totalDraftGestures = (int) ($gestureStatusCounts[Gesture::STATUS_DRAFT] ?? 0);
        $totalTrashedGestures = Gesture::onlyTrashed()->count();

        // Rata-rata progres pengguna
        $avgProgressPercent = 0;
        if ($totalUsers > 0 && $totalPublishedGestures > 0) {
            $totalLearnedAll = GestureProgress::whereNotNull('learned_at')
                ->whereExists(function ($sub): void {
                    $sub->selectRaw(1)
                        ->from('gestures')
                        ->whereColumn('gestures.id', 'gesture_progress.gesture_id')
                        ->where('gestures.status', Gesture::STATUS_PUBLISHED)
                        ->whereNull('gestures.deleted_at');
                })
                ->count();
            $avgProgressPercent = round(($totalLearnedAll / ($totalUsers * $totalPublishedGestures)) * 100, 1);
        }

        // Ambil sesi dan rata-rata skor dalam 1 query gabungan
        $sessionStats = PracticeSession::selectRaw('count(*) as total, avg(score) as avg_score')->first();
        $totalSessions = (int) ($sessionStats->total ?? 0);
        $avgScore = round((float) ($sessionStats->avg_score ?? 0), 1);

        $gestureStats = [
            'total' => $totalPublishedGestures + $totalDraftGestures,
            'published' => $totalPublishedGestures,
            'draft' => $totalDraftGestures,
            'trashed' => $totalTrashedGestures,
        ];

        // Kelengkapan konten ringkas (dikelompokkan agar aman terhadap soft deletes dan efisien)
        $incompleteCount = Gesture::query()
            ->where(function ($q): void {
                $q->whereNull('image_path')
                    ->orWhereNull('video_path')
                    ->orWhereNull('description')
                    ->orWhere('description', '');
            })
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
            ->with(['gesture' => fn ($q) => $q->select('id', 'word', 'slug')])
            ->get();

        // Aktivitas admin terkini (hanya pilih atribut yang diperlukan)
        $recentLogs = AdminActivityLog::with('user:id,name,role')->latest()->limit(5)->get();

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
