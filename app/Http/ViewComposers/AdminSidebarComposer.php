<?php

namespace App\Http\ViewComposers;

use App\Models\Gesture;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class AdminSidebarComposer
{
    /**
     * Bind sidebar metrics to the view with caching to avoid repeated DB queries across all admin pages.
     */
    public function compose(View $view): void
    {
        $metrics = Cache::remember('admin_sidebar_counts', 30, function (): array {
            $rawGestureCounts = Gesture::query()
                ->selectRaw('level, status, count(*) as total')
                ->groupBy('level', 'status')
                ->get();

            $counts = [
                'kata' => ['published' => 0, 'draft' => 0],
                'kalimat' => ['published' => 0, 'draft' => 0],
                'angka' => ['published' => 0, 'draft' => 0],
                'abjad' => ['published' => 0, 'draft' => 0],
            ];

            foreach ($rawGestureCounts as $row) {
                if (isset($counts[$row->level][$row->status])) {
                    $counts[$row->level][$row->status] = (int) $row->total;
                }
            }

            $userRoles = User::query()
                ->selectRaw("case when role in ('admin', 'editor') then 'staff' else 'student' end as role_group, count(*) as total")
                ->groupBy('role_group')
                ->pluck('total', 'role_group');

            return [
                'counts' => $counts,
                'totalStaff' => (int) ($userRoles['staff'] ?? 0),
                'totalStudents' => (int) ($userRoles['student'] ?? 0),
            ];
        });

        $counts = $metrics['counts'];

        $view->with([
            'cntKataPub' => $counts['kata']['published'] ?? 0,
            'cntKataDraft' => $counts['kata']['draft'] ?? 0,
            'cntKalimatPub' => $counts['kalimat']['published'] ?? 0,
            'cntKalimatDraft' => $counts['kalimat']['draft'] ?? 0,
            'cntAngkaPub' => $counts['angka']['published'] ?? 0,
            'cntAngkaDraft' => $counts['angka']['draft'] ?? 0,
            'cntAbjadPub' => $counts['abjad']['published'] ?? 0,
            'cntAbjadDraft' => $counts['abjad']['draft'] ?? 0,
            'totalStaff' => $metrics['totalStaff'],
            'totalStudents' => $metrics['totalStudents'],
        ]);
    }
}
