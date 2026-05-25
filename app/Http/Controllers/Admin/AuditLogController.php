<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

class AuditLogController extends Controller
{
    /**
     * Display a listing of the audit logs.
     */
    public function index(Request $request)
    {
        // Only administrator can access this (enforced by middleware or gate, but we double check)
        if (!auth()->user()->can('audit-log-view-admin')) {
            abort(403, 'Unauthorized action.');
        }

        $query = Activity::with('causer')->latest();

        // Optional filtering by action type (log_name or description)
        if ($request->filled('event')) {
            $query->where('event', $request->event);
        }

        // Optional filtering by user
        if ($request->filled('causer_id')) {
            $query->where('causer_id', $request->causer_id)
                  ->where('causer_type', 'App\Models\User');
        }

        $logs = $query->paginate(20)->withQueryString();

        return view('admin.audit_log.index', compact('logs'));
    }
}
