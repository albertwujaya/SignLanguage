<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LogController extends Controller
{
    public function index(Request $request): View
    {
        $query = AdminActivityLog::with('user')->latest();

        if ($request->filled('admin_id')) {
            $query->where('user_id', $request->admin_id);
        }

        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        $logs = $query->paginate(25)->withQueryString();

        $admins = User::whereIn('role', ['admin', 'editor'])->orderBy('name')->get();
        $actions = AdminActivityLog::select('action')->distinct()->pluck('action');

        return view('admin.logs.index', [
            'logs' => $logs,
            'admins' => $admins,
            'actions' => $actions,
        ]);
    }
}
