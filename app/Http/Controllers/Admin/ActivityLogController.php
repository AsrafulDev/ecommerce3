<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ActivityLogController extends Controller
{
    /**
     * Month-wise user activity / security log viewer.
     */
    public function index(Request $request): View
    {
        $month        = $request->input('month', now()->format('Y-m'));
        $userFilter   = $request->input('user');
        $moduleFilter = $request->input('module');
        $search       = trim((string) $request->input('search'));

        $logs = ActivityLog::with('user')
            ->when($month, function ($q, $m) {
                try {
                    $start = \Carbon\Carbon::createFromFormat('Y-m', $m)->startOfMonth();
                    $end   = $start->copy()->endOfMonth();
                    $q->whereBetween('created_at', [$start, $end]);
                } catch (\Throwable $e) {
                    // ignore invalid month
                }
            })
            ->when($userFilter, fn($q, $u) => $q->where('user_id', $u))
            ->when($moduleFilter, fn($q, $m) => $q->where('module', $m))
            ->when($search !== '', fn($q) => $q->where('description', 'like', "%{$search}%"))
            ->latest()
            ->paginate(30)
            ->withQueryString();

        $users   = User::orderBy('name')->get(['id', 'name']);
        $modules = ActivityLog::select('module')->distinct()->orderBy('module')->pluck('module');
        $months  = ActivityLog::selectRaw("DATE_FORMAT(created_at, '%Y-%m') as ym")
            ->distinct()->orderByDesc('ym')->pluck('ym');

        return view('backEnd.activity_log.index', compact(
            'logs', 'users', 'modules', 'months',
            'month', 'userFilter', 'moduleFilter', 'search'
        ));
    }
}
