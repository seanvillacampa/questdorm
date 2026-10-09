<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $query = AuditLog::with('user')
            ->orderByDesc('created_at');

        if ($request->filled('search')) {
            $s = '%'.$request->search.'%';
            $query->where(fn ($q) =>
                $q->where('description', 'like', $s)
                  ->orWhere('record_id', 'like', $s)
                  ->orWhereHas('user', fn ($u) => $u->where('name', 'like', $s))
            );
        }
        if ($request->filled('action') && $request->action !== 'all') {
            $query->where('action', $request->action);
        }
        if ($request->filled('user_id') && $request->user_id !== 'all') {
            $query->where('user_id', $request->user_id);
        }
        if ($request->filled('range')) {
            $query->where('created_at', '>=', match($request->range) {
                '7d'  => now()->subDays(7),
                '30d' => now()->subDays(30),
                '90d' => now()->subDays(90),
                default => now()->subDays(7),
            });
        } else {
            $query->where('created_at', '>=', now()->subDays(7));
        }

        $logs       = $query->paginate(15)->withQueryString();
        $actions    = AuditLog::distinct()->orderBy('action')->pluck('action');
        $staffUsers = \App\Models\User::role(['owner','employee'])->orderBy('name')->get();

        return view('audit-logs.index', compact('logs', 'actions', 'staffUsers'));
    }
}
